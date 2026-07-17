<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Services\TicketingService;
use Illuminate\Console\Command;

/**
 * Soal 2 — Reserved Ticket: "Jika expired, stok otomatis dilepas kembali."
 */
class ReleaseExpiredReservations extends Command
{
    protected $signature = 'tickets:release-expired';

    protected $description = 'Lepaskan kuota tiket dari checkout yang melewati batas 15 menit.';

    public function handle(TicketingService $ticketing): int
    {
        $expired = Transaction::releasable()->with('transactionItems')->get();

        if ($expired->isEmpty()) {
            return self::SUCCESS;
        }

        foreach ($expired as $transaction) {
            $ticketing->release($transaction, Transaction::STATUS_EXPIRED);
            $this->line("Dilepas: {$transaction->order_id} ({$transaction->quantity} tiket)");
        }

        $this->info("{$expired->count()} reservasi kedaluwarsa dilepas.");

        return self::SUCCESS;
    }
}