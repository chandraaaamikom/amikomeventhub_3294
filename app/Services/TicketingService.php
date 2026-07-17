<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TicketingService
{
    /** Kuota dikunci selama ini sebelum dilepas kembali (Soal 2). */
    public const RESERVATION_MINUTES = 15;

    /** Biaya layanan platform per event dalam satu transaksi. */
    public const SERVICE_FEE = 5000;

    /**
     * Kunci kuota untuk sekumpulan item, lalu buat transaksi berstatus pending.
     *
     * $lines: array of ['event' => Event, 'quantity' => int]
     *
     * @throws RuntimeException bila stok tidak mencukupi
     */
    public function reserve(array $lines, array $customer, ?int $userId = null): Transaction
    {
        if (empty($lines)) {
            throw new RuntimeException('Tidak ada item untuk dipesan.');
        }

        return DB::transaction(function () use ($lines, $customer, $userId) {
            $totalPrice = 0;
            $totalQuantity = 0;
            $snapshots = [];

            foreach ($lines as $line) {
                $quantity = max(1, (int) $line['quantity']);

                // Kunci baris event sampai transaksi DB selesai. Inilah yang
                // mencegah dua pembeli lolos bersamaan pada stok terakhir.
                $event = Event::where('id', $line['event']->id)->lockForUpdate()->first();

                if (! $event) {
                    throw new RuntimeException('Event tidak ditemukan.');
                }

                $available = $event->stock - $event->reserved_stock;

                if ($quantity > $available) {
                    throw new RuntimeException(
                        "Stok \"{$event->title}\" tidak mencukupi. Tersisa {$available} tiket."
                    );
                }

                if ($event->date->isPast()) {
                    throw new RuntimeException("Event \"{$event->title}\" sudah selesai.");
                }

                // Kunci kuotanya. Stok fisik belum dipotong.
                $event->increment('reserved_stock', $quantity);

                $subTotal = (int) $event->price * $quantity;
                $totalPrice += $subTotal + self::SERVICE_FEE;
                $totalQuantity += $quantity;

                $snapshots[] = [
                    'event_id'        => $event->id,
                    'organization_id' => $event->organization_id,
                    'title'           => $event->title,
                    'price'           => (int) $event->price,
                    'quantity'        => $quantity,
                    'sub_total'       => $subTotal,
                ];
            }

            // Diisi hanya bila seluruh item berasal dari satu tenant.
            $orgIds = collect($snapshots)->pluck('organization_id')->filter()->unique();

            $transaction = Transaction::create([
                'user_id'         => $userId,
                'organization_id' => $orgIds->count() === 1 ? $orgIds->first() : null,
                // Dipertahankan demi kompatibilitas view lama.
                'event_id'        => $snapshots[0]['event_id'],
                'order_id'        => $this->generateOrderId(),
                'customer_name'   => $customer['name'],
                'customer_email'  => $customer['email'],
                'customer_phone'  => $customer['phone'],
                'quantity'        => $totalQuantity,
                'total_price'     => $totalPrice,
                'status'          => Transaction::STATUS_PENDING,
                'expires_at'      => now()->addMinutes(self::RESERVATION_MINUTES),
                'stock_applied'   => false,
                'items'           => $snapshots,
            ]);

            foreach ($snapshots as $snapshot) {
                TransactionItem::create($snapshot + ['transaction_id' => $transaction->id]);
            }

            return $transaction;
        });
    }

    /**
     * Pembayaran lunas: potong stok fisik, lepas kuncian, terbitkan tiket.
     * Aman dipanggil berulang — Midtrans mengirim notifikasi lebih dari sekali.
     */
    public function fulfill(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $transaction = Transaction::where('id', $transaction->id)->lockForUpdate()->first();

            if ($transaction->stock_applied) {
                Log::info("Transaksi {$transaction->order_id} sudah diproses, dilewati.");

                return;
            }

            foreach ($transaction->transactionItems as $item) {
                $event = Event::where('id', $item->event_id)->lockForUpdate()->first();

                if (! $event) {
                    continue;
                }

                // Potong stok fisik sebesar porsi item ini — bukan total transaksi.
                $event->decrement('stock', $item->quantity);

                // Kuncian dilepas karena kuotanya kini sudah jadi penjualan.
                $event->decrement('reserved_stock', min($item->quantity, $event->reserved_stock));

                $this->issueTickets($transaction, $item);
            }

            $transaction->forceFill([
                'status'        => Transaction::STATUS_SUCCESS,
                'paid_at'       => $transaction->paid_at ?? now(),
                'stock_applied' => true,
                'expires_at'    => null,
            ])->save();
        });
    }

    /**
     * Batal / gagal / kedaluwarsa: lepas kuncian tanpa menyentuh stok fisik.
     */
    public function release(Transaction $transaction, string $status = Transaction::STATUS_EXPIRED): void
    {
        DB::transaction(function () use ($transaction, $status) {
            $transaction = Transaction::where('id', $transaction->id)->lockForUpdate()->first();

            // Transaksi lunas tidak boleh dilepas — kuotanya sudah jadi stok terjual.
            if ($transaction->stock_applied || $transaction->status === Transaction::STATUS_SUCCESS) {
                return;
            }

            if ($transaction->expires_at === null && $transaction->status !== Transaction::STATUS_PENDING) {
                return; // sudah pernah dilepas
            }

            foreach ($transaction->transactionItems as $item) {
                $event = Event::where('id', $item->event_id)->lockForUpdate()->first();

                if (! $event) {
                    continue;
                }

                $event->decrement('reserved_stock', min($item->quantity, $event->reserved_stock));
            }

            $transaction->forceFill([
                'status'     => $status,
                'expires_at' => null,
            ])->save();
        });
    }

    /**
     * Satu baris tiket per lembar, masing-masing dengan UUID sendiri (Soal 2).
     */
    protected function issueTickets(Transaction $transaction, TransactionItem $item): void
    {
        $existing = Ticket::where('transaction_item_id', $item->id)->count();
        $needed = $item->quantity - $existing;

        for ($i = 0; $i < $needed; $i++) {
            Ticket::create([
                'transaction_id'      => $transaction->id,
                'transaction_item_id' => $item->id,
                'event_id'            => $item->event_id,
                'attendee_name'       => $transaction->customer_name,
            ]);
        }
    }

    protected function generateOrderId(): string
    {
        do {
            $orderId = 'TRX-' . now()->format('ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(6));
        } while (Transaction::where('order_id', $orderId)->exists());

        return $orderId;
    }
}