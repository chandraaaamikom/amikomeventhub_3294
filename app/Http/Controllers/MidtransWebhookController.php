<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\TicketingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Satu-satunya pintu notifikasi pembayaran Midtrans.
 * Dikecualikan dari CSRF di bootstrap/app.php.
 */
class MidtransWebhookController extends Controller
{
    public function __construct(protected TicketingService $ticketing)
    {
    }

    public function handle(Request $request)
    {
        $notification = json_decode($request->getContent());

        if (! $notification || blank($notification->order_id ?? null)) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        // Signature WAJIB diverifikasi. Endpoint ini publik: tanpa verifikasi,
        // siapa pun bisa menandai transaksi lunas tanpa membayar.
        if (! $this->signatureIsValid($notification)) {
            Log::warning('Midtrans webhook: signature tidak valid', [
                'order_id' => $notification->order_id,
                'ip'       => $request->ip(),
            ]);

            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $transaction = Transaction::with('transactionItems')
            ->where('order_id', $notification->order_id)
            ->first();

        if (! $transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        $status = $notification->transaction_status ?? null;
        $fraud  = $notification->fraud_status ?? null;

        Log::info("Midtrans webhook: {$transaction->order_id} → {$status}");

        match (true) {
            // Kartu kredit yang ditahan sistem anti-fraud: belum boleh diluluskan.
            $status === 'capture' && $fraud === 'challenge' => $this->markPending($transaction),

            $status === 'capture', $status === 'settlement' => $this->ticketing->fulfill($transaction),

            $status === 'pending' => $this->markPending($transaction),

            // 'expire' dari Midtrans = pembeli kehabisan waktu.
            $status === 'expire' => $this->ticketing->release($transaction, Transaction::STATUS_EXPIRED),

            in_array($status, ['deny', 'cancel', 'failure'], true) => $this->ticketing->release($transaction, Transaction::STATUS_FAILED),

            default => Log::warning("Midtrans webhook: status tidak dikenal '{$status}' untuk {$transaction->order_id}"),
        };

        // Selalu 200 supaya Midtrans berhenti mengirim ulang.
        return response()->json(['message' => 'OK']);
    }

    protected function signatureIsValid(object $notification): bool
    {
        $expected = hash(
            'sha512',
            $notification->order_id
                . ($notification->status_code ?? '')
                . ($notification->gross_amount ?? '')
                . config('midtrans.server_key')
        );

        return hash_equals($expected, $notification->signature_key ?? '');
    }

    protected function markPending(Transaction $transaction): void
    {
        // Jangan turunkan status transaksi yang sudah lunas.
        if ($transaction->stock_applied || $transaction->status === Transaction::STATUS_SUCCESS) {
            return;
        }

        $transaction->update(['status' => Transaction::STATUS_PENDING]);
    }
}