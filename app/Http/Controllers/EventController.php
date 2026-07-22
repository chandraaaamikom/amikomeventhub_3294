<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Transaction;
use App\Services\TicketingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Midtrans\Config as MidtransConfig;
use Midtrans\Snap;
use Midtrans\Transaction as MidtransTransaction;
use RuntimeException;

class EventController extends Controller
{
    public function __construct(protected TicketingService $ticketing)
    {
        MidtransConfig::$serverKey    = config('midtrans.server_key');
        MidtransConfig::$isProduction = (bool) config('midtrans.is_production');
        MidtransConfig::$isSanitized  = true;
        MidtransConfig::$is3ds        = true;
    }

    public function show($id)
    {
        $event = Event::with(['category', 'organization'])->findOrFail($id);

        $reviews = $event->reviews()
            ->with('user:id,name,avatar')
            ->latest()
            ->get();

        $ratingAverage = round((float) $reviews->avg('rating'), 2);

        return view('event-detail', compact('event', 'reviews', 'ratingAverage'));
    }

    public function checkout($id)
    {
        $event = Event::with('organization')->findOrFail($id);

        if ($event->date->isPast()) {
            return redirect()->route('events.show', $event->id)
                ->with('error', 'Event ini sudah selesai.');
        }

        if ($event->isSoldOut()) {
            return redirect()->route('events.show', $event->id)
                ->with('error', 'Tiket untuk event ini sudah habis.');
        }

        return view('checkout', compact('event'));
    }

    /**
     * Kunci kuota 15 menit (Soal 2), lalu buat Snap token.
     */
    public function createPayment(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255',
            'phone'    => 'required|string|max:20',
            'quantity' => 'required|integer|min:1',
        ]);

        if (blank(config('midtrans.server_key'))) {
            return response()->json(['error' => 'MIDTRANS_SERVER_KEY belum diset. Periksa .env Anda.'], 500);
        }

        try {
            $transaction = $this->ticketing->reserve(
                lines: [['event' => $event, 'quantity' => (int) $request->quantity]],
                customer: $request->only('name', 'email', 'phone'),
                userId: Auth::id(),
            );
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        try {
            $snapToken = $this->buildSnapToken($transaction);
        } catch (\Throwable $e) {
            // Gagal bikin token: kuota harus dilepas, jangan menggantung 15 menit.
            $this->ticketing->release($transaction, Transaction::STATUS_FAILED);
            Log::error('Midtrans Snap error: ' . $e->getMessage());

            return response()->json(['error' => 'Gagal membuat token pembayaran. Silakan coba lagi.'], 500);
        }

        $transaction->update(['snap_token' => $snapToken]);

        return response()->json([
            'success'    => true,
            'snap_token' => $snapToken,
            'order_id'   => $transaction->order_id,
            'expires_at' => $transaction->expires_at->toIso8601String(),
        ]);
    }

    /**
     * Sinkronkan hasil pembayaran dari API Midtrans.
     *
     * Webhook tetap menjadi sumber utama pada server publik. Endpoint ini adalah
     * fallback aman untuk localhost: Midtrans tidak dapat mengirim callback ke
     * alamat 127.0.0.1, sehingga browser meminta server memverifikasi hasilnya
     * langsung ke Midtrans setelah Snap mengembalikan status sukses.
     */
    public function syncPayment(string $orderId)
    {
        $transaction = Transaction::where('order_id', $orderId)->firstOrFail();

        try {
            $midtransStatus = MidtransTransaction::status($transaction->order_id);
        } catch (\Throwable $e) {
            Log::error("Midtrans status sync gagal untuk {$transaction->order_id}: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'status' => $transaction->status,
                'message' => 'Status pembayaran belum dapat diverifikasi. Silakan muat ulang tiket beberapa saat lagi.',
            ], 502);
        }

        $status = $midtransStatus->transaction_status ?? null;
        $fraudStatus = $midtransStatus->fraud_status ?? 'accept';
        $grossAmount = (int) ($midtransStatus->gross_amount ?? 0);

        if ($grossAmount !== (int) $transaction->total_price) {
            Log::warning("Midtrans status sync: nominal tidak cocok untuk {$transaction->order_id}");

            return response()->json(['success' => false, 'message' => 'Nominal pembayaran tidak cocok.'], 422);
        }

        if ($status === 'settlement' || ($status === 'capture' && $fraudStatus === 'accept')) {
            $this->ticketing->fulfill($transaction);
            $transaction->refresh();
        }

        return response()->json([
            'success' => $transaction->status === Transaction::STATUS_SUCCESS,
            'status' => $transaction->status,
        ]);
    }

    /**
     * Snap payload dibangun dari transaction_items, bukan dari request.
     */
    protected function buildSnapToken(Transaction $transaction): string
    {
        $itemDetails = [];

        foreach ($transaction->transactionItems as $item) {
            $itemDetails[] = [
                'id'       => 'event-' . $item->event_id,
                'price'    => $item->price,
                'quantity' => $item->quantity,
                'name'     => mb_substr($item->title, 0, 50), // Midtrans membatasi 50 karakter
            ];

            $itemDetails[] = [
                'id'       => 'fee-' . $item->event_id,
                'price'    => TicketingService::SERVICE_FEE,
                'quantity' => 1,
                'name'     => 'Biaya Layanan',
            ];
        }

        return Snap::getSnapToken([
            'transaction_details' => [
                'order_id'     => $transaction->order_id,
                'gross_amount' => $transaction->total_price,
            ],
            'customer_details' => [
                'first_name' => $transaction->customer_name,
                'email'      => $transaction->customer_email,
                'phone'      => $transaction->customer_phone,
            ],
            'item_details' => $itemDetails,
            'expiry' => [
                'unit'     => 'minute',
                'duration' => TicketingService::RESERVATION_MINUTES,
            ],
            'callbacks' => [
                'finish' => route('ticket', ['order_id' => $transaction->order_id]),
            ],
        ]);
    }

    public function ticket($order_id = null)
    {
        $query = Transaction::with(['transactionItems.event', 'tickets', 'event']);

        if ($order_id) {
            $transaction = $query->where('order_id', $order_id)->first();
        } elseif (Auth::check()) {
            $transaction = $query->where('user_id', Auth::id())->latest()->first();
        } else {
            $transaction = null;
        }

        if (! $transaction) {
            return redirect()->route('home')->with('error', 'Transaksi tidak ditemukan.');
        }

        return view('ticket', compact('transaction'));
    }
}
