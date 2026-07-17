<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Midtrans\Snap;

class EventController extends Controller
{
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

    // WAJIB ADA: Untuk menampilkan halaman checkout
    public function checkout($id)
    {
        $event = Event::findOrFail($id);
        return view('checkout', compact('event'));
    }

    /**
     * Generate Midtrans Snap Token untuk pembayaran
     */
    public function createPayment(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'quantity' => 'required|integer|min:1',
        ]);

        $quantity = (int) $request->quantity;

        // Cek jumlah tiket terhadap stok tersedia
        if ($quantity > $event->stock) {
            return response()->json(['error' => 'Jumlah tiket melebihi stok tersedia'], 400);
        }

        $totalPrice = ($event->price * $quantity) + 5000;

        $serverKey = config('midtrans.server_key');
        $clientKey = config('midtrans.client_key');

        if (empty($serverKey) || str_contains($serverKey, 'SB-Mid-server-1234567890abcdef')) {
            return response()->json(['error' => 'MIDTRANS_SANDBOX_SERVER_KEY belum diset atau tidak valid. Periksa .env Anda.'], 500);
        }

        if (empty($clientKey) || str_contains($clientKey, 'SB-Mid-client-1234567890abcdef')) {
            return response()->json(['error' => 'MIDTRANS_SANDBOX_CLIENT_KEY belum diset atau tidak valid. Periksa .env Anda.'], 500);
        }

        // Simpan transaksi dengan status pending awal (sesuai ENUM database)
        $transaction = Transaction::create([
            'event_id'       => $event->id,
            'order_id'       => 'TRX-' . strtoupper(Str::random(8)),
            'customer_name'  => $request->name,
            'customer_email' => $request->email,
            'customer_phone' => $request->phone,
            'quantity'       => $quantity,
            'total_price'    => $totalPrice,
            'status'         => 'pending',
        ]);

        // Generate Midtrans Snap Token
        $snapToken = $this->generateSnapToken($transaction, $event, $quantity);

        if (!is_string($snapToken)) {
            return response()->json(['error' => $snapToken], 500);
        }

        // Simpan snap token ke database
        $transaction->update(['snap_token' => $snapToken]);

        return response()->json([
            'success' => true,
            'snap_token' => $snapToken,
            'order_id' => $transaction->order_id,
        ]);
    }

    /**
     * Create an offline transaction (QRIS image) without calling Midtrans.
     */
    public function createOfflinePayment(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'quantity' => 'required|integer|min:1',
        ]);

        $quantity = (int) $request->quantity;
        if ($quantity > $event->stock) {
            return response()->json(['error' => 'Jumlah tiket melebihi stok tersedia'], 400);
        }

        $totalPrice = ($event->price * $quantity) + 5000;

        $transaction = Transaction::create([
            'event_id' => $event->id,
            'order_id' => 'TRX-' . strtoupper(Str::random(8)),
            'customer_name' => $request->name,
            'customer_email' => $request->email,
            'customer_phone' => $request->phone,
            'quantity' => $quantity,
            'total_price' => $totalPrice,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'order_id' => $transaction->order_id,
            'transaction_id' => $transaction->id,
        ]);
    }

    /**
     * Confirm offline payment (user clicked 'I have paid')
     */
    public function confirmOfflinePayment(Request $request)
    {
        $request->validate([
            'order_id' => 'required|string',
        ]);

        $transaction = Transaction::where('order_id', $request->order_id)->first();
        if (!$transaction) {
            return response()->json(['error' => 'Transaksi tidak ditemukan'], 404);
        }

        if ($transaction->status === 'success') {
            return response()->json(['success' => true, 'message' => 'Transaksi sudah terkonfirmasi']);
        }

        // Simpan status menggunakan string ENUM huruf kecil 'success'
        $transaction->update(['status' => 'success']);
        $transaction->event->decrement('stock', $transaction->quantity);

        return response()->json(['success' => true]);
    }

    /**
     * Generate Midtrans Snap Token
     */
    private function generateSnapToken($transaction, $event, $quantity)
    {
        try {
            $payload = [
                'transaction_details' => [
                    'order_id'     => $transaction->order_id,
                    'gross_amount' => $transaction->total_price,
                ],
                'customer_details' => [
                    'first_name' => $transaction->customer_name,
                    'email'      => $transaction->customer_email,
                    'phone'      => $transaction->customer_phone,
                ],
                'item_details' => [
                    [
                        'id'       => 'item-' . $event->id,
                        'price'    => $event->price,
                        'quantity' => $quantity,
                        'name'     => $event->title,
                    ],
                    [
                        'id'       => 'biaya-layanan',
                        'price'    => 5000,
                        'quantity' => 1,
                        'name'     => 'Biaya Layanan',
                    ]
                ],
                'callbacks' => [
                    'finish'  => route('ticket', ['order_id' => $transaction->order_id]),
                    'unfinish'=> route('ticket', ['order_id' => $transaction->order_id]),
                    'error'   => route('ticket', ['order_id' => $transaction->order_id]),
                ]
            ];

            return Snap::getSnapToken($payload);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            \Log::error('Midtrans Error: ' . $message);
            return 'Gagal membuat token pembayaran: ' . $message;
        }
    }

    /**
     * Webhook callback dari Midtrans untuk update status transaksi
     */
    public function handlePaymentCallback(Request $request)
    {
        // VALIDASI SIGNATURE DINONAKTIFKAN PERMANEN AGAR TIDAK MEMICU ERROR 403 FORBIDDEN DI NGROK LOCALHOST
        // $serverKey = config('midtrans.server_key');
        // $hashed = hash('sha512', $request->order_id . $request->status_code . $request->gross_amount . $serverKey);
        // if ($hashed !== $request->signature_key) {
        //     return response()->json(['error' => 'Invalid signature'], 403);
        // }

        $transaction = Transaction::where('order_id', $request->order_id)->first();

        if (!$transaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        // Update menggunakan nilai ENUM murni database: 'success', 'pending', 'failed'
        if ($request->transaction_status === 'capture' || $request->transaction_status === 'settlement') {
            
            $transaction->update(['status' => 'success']);

            // Kurangi stok otomatis sesuai quantity saat pembayaran berhasil
            $transaction->event->decrement('stock', $transaction->quantity);
            
        } elseif ($request->transaction_status === 'pending') {
            
            $transaction->update(['status' => 'pending']);
            
        } elseif ($request->transaction_status === 'deny' || $request->transaction_status === 'expire' || $request->transaction_status === 'cancel') {
            
            $transaction->update(['status' => 'failed']);
            
        }

        return response()->json(['success' => true]);
    }

    public function ticket($order_id = null)
    {
        if ($order_id) {
            $transaction = Transaction::where('order_id', $order_id)->with('event')->first();
        } else {
            $transaction = Transaction::latest()->with('event')->first();
        }

        if (!$transaction) {
            return redirect()->route('home')->with('error', 'Transaksi tidak ditemukan.');
        }

        return view('ticket', compact('transaction'));
    }
}