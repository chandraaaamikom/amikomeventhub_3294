<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Review;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Menyiapkan data demo Soal 1b.
 *
 * Transaksi lama lahir dari checkout tamu, jadi user_id-nya null dan tidak ada
 * seorang pun yang berhak mengulas. Seeder ini mengadopsi sebagian transaksi
 * lunas ke akun peserta demo, lalu menulis ulasan atas nama mereka —
 * melewati ReviewController, tapi tetap menghormati aturannya.
 */
class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $reviewers = $this->reviewers();

        if ($reviewers->isEmpty()) {
            $this->command->warn('Tidak ada akun peserta. Jalankan OrganizationSeeder dulu.');

            return;
        }

        // Hanya event yang sudah lewat H+1 yang boleh diulas (syarat soal).
        $eligibleEvents = Event::where('date', '<', now()->subDay())->get();

        if ($eligibleEvents->isEmpty()) {
            $this->command->warn('Tidak ada event yang sudah selesai lebih dari 1 hari. Tidak ada ulasan yang dibuat.');

            return;
        }

        $created = 0;

        foreach ($eligibleEvents as $index => $event) {
            // Dua pengulas per event, digilir agar namanya bervariasi.
            foreach ([0, 1] as $slot) {
                $user = $reviewers[($index + $slot) % $reviewers->count()];

                if (Review::where('user_id', $user->id)->where('event_id', $event->id)->exists()) {
                    continue;
                }

                $item = $this->adoptPurchase($user, $event);

                if (! $item) {
                    continue;
                }

                [$rating, $comment] = $this->sample($index + $slot);

                Review::create([
                    'user_id'         => $user->id,
                    'event_id'        => $event->id,
                    'organization_id' => $event->organization_id,
                    'transaction_id'  => $item->transaction_id,
                    'rating'          => $rating,
                    'comment'         => $comment,
                    'created_at'      => $event->date->copy()->addDays(rand(1, 5)),
                ]);

                $created++;
            }
        }

        $this->command->info("{$created} ulasan demo dibuat.");
    }

    /**
     * Akun yang dipakai sebagai pengulas: peserta demo + user biasa lain
     * (termasuk yang masuk lewat Google).
     */
    protected function reviewers()
    {
        $peserta = User::firstOrCreate(
            ['email' => 'peserta@amikom.ac.id'],
            [
                'name' => 'Peserta Demo',
                'password' => bcrypt('password'),
                'role' => User::ROLE_USER,
            ]
        );

        $extra = collect([
            ['email' => 'rani.demo@amikom.ac.id', 'name' => 'Rani Puspita'],
            ['email' => 'bagas.demo@amikom.ac.id', 'name' => 'Bagas Prayoga'],
            ['email' => 'intan.demo@amikom.ac.id', 'name' => 'Intan Maharani'],
        ])->map(fn ($u) => User::firstOrCreate(
            ['email' => $u['email']],
            ['name' => $u['name'], 'password' => bcrypt('password'), 'role' => User::ROLE_USER]
        ));

        return collect([$peserta])->concat($extra)->values();
    }

    /**
     * Cari transaksi lunas untuk event ini yang belum punya pemilik,
     * lalu tempelkan ke user. Bila tidak ada, buatkan transaksi demo.
     */
    protected function adoptPurchase(User $user, Event $event): ?TransactionItem
    {
        // Sudah punya pembelian? pakai itu.
        $existing = TransactionItem::where('event_id', $event->id)
            ->whereHas('transaction', fn ($q) => $q
                ->where('user_id', $user->id)
                ->where('status', Transaction::STATUS_SUCCESS))
            ->first();

        if ($existing) {
            return $existing;
        }

        // Adopsi transaksi tamu yang lunas.
        $orphan = TransactionItem::where('event_id', $event->id)
            ->whereHas('transaction', fn ($q) => $q
                ->whereNull('user_id')
                ->where('status', Transaction::STATUS_SUCCESS))
            ->first();

        if ($orphan) {
            $orphan->transaction->forceFill(['user_id' => $user->id])->save();

            return $orphan;
        }

        // Tidak ada yang bisa diadopsi — buat transaksi demo yang utuh.
        return DB::transaction(function () use ($user, $event) {
            $price = max(1, (int) $event->price);
            $paidAt = $event->date->copy()->subDays(rand(3, 14));

            $transaction = Transaction::create([
                'user_id'        => $user->id,
                'organization_id'=> $event->organization_id,
                'event_id'       => $event->id,
                'order_id'       => 'DEMO-' . strtoupper(\Illuminate\Support\Str::random(8)),
                'customer_name'  => $user->name,
                'customer_email' => $user->email,
                'customer_phone' => '08120000' . rand(1000, 9999),
                'quantity'       => 1,
                'total_price'    => $price + 5000,
                'status'         => Transaction::STATUS_SUCCESS,
                'paid_at'        => $paidAt,
                // Stok sengaja TIDAK dipotong untuk data demo,
                // jadi tandai sudah diproses agar webhook tidak menyentuhnya.
                'stock_applied'  => true,
                'created_at'     => $paidAt,
                'updated_at'     => $paidAt,
            ]);

            return TransactionItem::create([
                'transaction_id'  => $transaction->id,
                'event_id'        => $event->id,
                'organization_id' => $event->organization_id,
                'title'           => $event->title,
                'price'           => $price,
                'quantity'        => 1,
                'sub_total'       => $price,
            ]);
        });
    }

    protected function sample(int $seed): array
    {
        $samples = [
            [5, 'Acaranya rapi dan tepat waktu. Materinya benar-benar aplikatif, bukan sekadar teori. Panitianya juga sigap saat registrasi.'],
            [4, 'Secara keseluruhan bagus, pembicaranya berkualitas. Sayang ruangannya agak penuh jadi harus datang lebih awal untuk dapat tempat nyaman.'],
            [5, 'Salah satu acara kampus terbaik yang pernah saya ikuti. Sesi tanya jawabnya panjang dan pertanyaan peserta dijawab tuntas.'],
            [4, 'Worth it untuk harga segini. Konsumsinya oke, dapat sertifikat juga. Semoga tahun depan durasinya ditambah.'],
            [3, 'Materinya menarik tapi terasa terburu-buru di bagian akhir. Mungkin karena mulainya telat sekitar 20 menit.'],
            [5, 'Panitianya komunikatif dari awal sampai hari-H. Informasi teknis dikirim jelas, jadi tidak bingung waktu datang.'],
            [4, 'Suasananya seru dan networking-nya dapat. Pendaftaran lewat web-nya juga gampang, tiketnya langsung masuk.'],
            [5, 'Puas banget. Sound system dan pencahayaannya niat, tidak terasa seperti acara mahasiswa biasa.'],
            [4, 'Bagus, cuma antrean masuk agak panjang di awal. Selebihnya lancar dan sesuai ekspektasi.'],
            [3, 'Cukup oke untuk pemula. Buat yang sudah pernah ikut acara serupa mungkin materinya terasa mengulang.'],
        ];

        return $samples[$seed % count($samples)];
    }
}