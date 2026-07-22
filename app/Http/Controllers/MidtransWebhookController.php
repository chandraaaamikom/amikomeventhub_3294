<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\TicketingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MidtransWebhookController extends Controller
{
    public function __construct(protected TicketingService $ticketing)
    {
    }

    public function handle(Request $request)
    {
        $payload = $request->all();

        $orderId      = $payload['order_id'] ?? null;
        $statusCode   = $payload['status_code'] ?? null;
        $grossAmount  = $payload['gross_amount'] ?? null;
        $signatureKey = $payload['signature_key'] ?? null;

        if (! $orderId || ! $signatureKey) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        // Signature WAJIB. Path config yang benar: midtrans.server_key
        $serverKey = config('midtrans.server_key');
        $localSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        if (! hash_equals($localSignature, $signatureKey)) {
            Log::warning("Midtrans webhook: signature tidak valid untuk {$orderId}", ['ip' => $request->ip()]);
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        // Cari via order_id (string), BUKAN id (angka).
        $transaction = Transaction::with('transactionItems')
            ->where('order_id', $orderId)
            ->first();

        if (! $transaction) {
            Log::warning("Midtrans webhook: transaksi tidak ditemukan untuk {$orderId}");
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        $status = $payload['transaction_status'] ?? null;
        $fraud  = $payload['fraud_status'] ?? 'accept';

        Log::info("Midtrans webhook: {$orderId} → {$status}");

        match (true) {
            $status === 'capture' && $fraud === 'challenge' => $this->markPending($transaction),
            $status === 'capture', $status === 'settlement' => $this->ticketing->fulfill($transaction),
            $status === 'pending' => $this->markPending($transaction),
            $status === 'expire'  => $this->ticketing->release($transaction, Transaction::STATUS_EXPIRED),
            in_array($status, ['deny', 'cancel', 'failure'], true) => $this->ticketing->release($transaction, Transaction::STATUS_FAILED),
            default => Log::warning("Midtrans webhook: status tidak dikenal '{$status}' untuk {$orderId}"),
        };

        // Selalu 200 supaya Midtrans berhenti retry.
        return response()->json(['message' => 'OK'], 200);
    }

    protected function markPending(Transaction $transaction): void
    {
        if ($transaction->stock_applied || $transaction->status === Transaction::STATUS_SUCCESS) {
            return;
        }

        $transaction->update(['status' => Transaction::STATUS_PENDING]);
    }
}