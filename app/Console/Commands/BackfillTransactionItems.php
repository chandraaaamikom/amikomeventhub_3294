<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillTransactionItems extends Command
{
    protected $signature = 'tickets:backfill-items {--dry-run : Tampilkan rencana tanpa menyimpan}';

    protected $description = 'Migrasikan transaksi lama (kolom JSON items / event_id tunggal) ke tabel transaction_items.';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $transactions = Transaction::doesntHave('transactionItems')->get();

        if ($transactions->isEmpty()) {
            $this->info('Tidak ada transaksi yang perlu di-backfill.');

            return self::SUCCESS;
        }

        $this->info("Ditemukan {$transactions->count()} transaksi tanpa item.");
        $created = 0;
        $skipped = 0;

        foreach ($transactions as $transaction) {
            $rows = $this->buildRows($transaction);

            if (empty($rows)) {
                $this->warn("  {$transaction->order_id}: tidak ada data item yang bisa dipulihkan — dilewati.");
                $skipped++;
                continue;
            }

            if ($dryRun) {
                foreach ($rows as $row) {
                    $this->line("  {$transaction->order_id}: {$row['quantity']}x {$row['title']} = Rp " . number_format($row['sub_total'], 0, ',', '.'));
                }
                $created += count($rows);
                continue;
            }

            DB::transaction(function () use ($transaction, $rows, &$created) {
                foreach ($rows as $row) {
                    TransactionItem::create($row + ['transaction_id' => $transaction->id]);
                    $created++;
                }

                // Lengkapi kolom baru yang belum terisi pada data lama.
                $updates = [];

                if ($transaction->organization_id === null) {
                    $orgIds = collect($rows)->pluck('organization_id')->filter()->unique();
                    // Hanya isi bila seluruh item berasal dari satu tenant.
                    if ($orgIds->count() === 1) {
                        $updates['organization_id'] = $orgIds->first();
                    }
                }

                if ($transaction->status === Transaction::STATUS_SUCCESS) {
                    if ($transaction->paid_at === null) {
                        $updates['paid_at'] = $transaction->updated_at ?? $transaction->created_at;
                    }
                    // Stok transaksi lama sudah dipotong oleh webhook versi lama.
                    $updates['stock_applied'] = true;
                }

                if (! empty($updates)) {
                    $transaction->forceFill($updates)->save();
                }
            });
        }

        if ($dryRun) {
            $this->newLine();
            $this->info("[DRY RUN] {$created} item akan dibuat, {$skipped} transaksi dilewati. Tidak ada yang disimpan.");

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info("Selesai: {$created} item dibuat, {$skipped} transaksi dilewati.");

        return self::SUCCESS;
    }

    /**
     * Dua sumber data lama: kolom JSON `items` (checkout keranjang),
     * atau pasangan event_id + quantity (checkout satuan).
     */
    protected function buildRows(Transaction $transaction): array
    {
        $rows = [];

        // Sumber 1: kolom JSON items
        if (is_array($transaction->items) && count($transaction->items) > 0) {
            foreach ($transaction->items as $item) {
                $eventId = $item['event_id'] ?? null;
                $event = $eventId ? Event::find($eventId) : null;

                $price = (int) ($item['price'] ?? 0);
                $quantity = max(1, (int) ($item['quantity'] ?? 1));

                $rows[] = [
                    'event_id'        => $eventId ?? $transaction->event_id,
                    'organization_id' => $event?->organization_id,
                    'title'           => $item['title'] ?? ($event?->title ?? 'Event tidak diketahui'),
                    'price'           => $price,
                    'quantity'        => $quantity,
                    'sub_total'       => (int) ($item['sub_total'] ?? $price * $quantity),
                ];
            }

            return array_values(array_filter($rows, fn ($r) => $r['event_id'] !== null));
        }

        // Sumber 2: transaksi satuan
        if ($transaction->event_id) {
            $event = Event::find($transaction->event_id);

            if (! $event) {
                return [];
            }

            $quantity = max(1, (int) $transaction->quantity);

            // Harga saat itu direkonstruksi dari total dikurangi biaya layanan.
            // Bila hasilnya janggal, pakai harga event sekarang sebagai perkiraan.
            $reconstructed = intdiv(max(0, $transaction->total_price - 5000), $quantity);
            $price = $reconstructed > 0 ? $reconstructed : (int) $event->price;

            $rows[] = [
                'event_id'        => $event->id,
                'organization_id' => $event->organization_id,
                'title'           => $event->title,
                'price'           => $price,
                'quantity'        => $quantity,
                'sub_total'       => $price * $quantity,
            ];
        }

        return $rows;
    }
}