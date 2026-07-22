<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\TicketingService;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{
    /**
     * Halaman "selesaikan pembayaran" — memuat Snap dengan hitung mundur 15 menit.
     */
    public function payment($order_id)
    {
        $transaction = Transaction::with('transactionItems')
            ->where('order_id', $order_id)
            ->firstOrFail();

        // Reservasi yang sudah kedaluwarsa tidak boleh dibayar lagi —
        // kuotanya kemungkinan sudah dilepas ke pembeli lain.
        if ($transaction->isExpired()) {
            return redirect()->route('home')
                ->with('error', 'Waktu pembayaran untuk pesanan ini telah habis. Silakan pesan ulang.');
        }

        if ($transaction->status === Transaction::STATUS_SUCCESS) {
            return redirect()->route('ticket', $transaction->order_id)
                ->with('success', 'Pembayaran sudah lunas.');
        }

        return view('payment', compact('transaction'));
    }

    /**
     * Halaman terima kasih. Status sebenarnya ditentukan webhook, bukan di sini.
     */
    public function success($order_id)
    {
        $transaction = Transaction::with(['transactionItems', 'tickets'])
            ->where('order_id', $order_id)
            ->firstOrFail();

        return view('success', compact('transaction'));
    }
}