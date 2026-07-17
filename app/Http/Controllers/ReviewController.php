<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Review;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    /**
     * Soal 1b: rating 1-5 + testimoni, hanya untuk pembeli tiket,
     * dan baru dibuka satu hari setelah acara selesai.
     */
    public function store(Request $request, Event $event)
    {
        $user = Auth::user();

        // Gerbang 1: acara harus sudah selesai + lewat H+1.
        if (! $event->reviewsAreOpen()) {
            return back()->with(
                'error',
                'Ulasan baru dapat diberikan mulai ' . $event->reviewsOpenAt()->translatedFormat('d F Y, H:i') . '.'
            );
        }

        // Gerbang 2: harus benar-benar membeli tiket event ini, dan lunas.
        $purchase = $this->findPurchase($user->id, $event->id);

        if (! $purchase) {
            return back()->with(
                'error',
                'Hanya pembeli tiket yang dapat memberikan ulasan untuk event ini.'
            );
        }

        // Gerbang 3: satu ulasan per orang per event.
        if (Review::where('user_id', $user->id)->where('event_id', $event->id)->exists()) {
            return back()->with('error', 'Anda sudah pernah mengulas event ini.');
        }

        $data = $request->validate([
            'rating'  => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], [
            'rating.required' => 'Pilih rating bintang terlebih dahulu.',
            'rating.between'  => 'Rating harus antara 1 sampai 5 bintang.',
            'comment.max'     => 'Testimoni maksimal 1000 karakter.',
        ]);

        Review::create([
            'user_id'         => $user->id,
            'event_id'        => $event->id,
            // Snapshot: reputasi melekat ke penyelenggara saat acara berlangsung.
            'organization_id' => $event->organization_id,
            'transaction_id'  => $purchase->transaction_id,
            'rating'          => $data['rating'],
            'comment'         => $data['comment'] ?? null,
        ]);

        return back()->with('success', 'Terima kasih! Ulasan Anda sudah tayang.');
    }

    public function update(Request $request, Review $review)
    {
        abort_if($review->user_id !== Auth::id(), 403);

        $data = $request->validate([
            'rating'  => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $review->update($data);

        return back()->with('success', 'Ulasan Anda berhasil diperbarui.');
    }

    public function destroy(Review $review)
    {
        abort_if($review->user_id !== Auth::id(), 403);

        $review->delete();

        return back()->with('success', 'Ulasan Anda telah dihapus.');
    }

    /**
     * Bukti pembelian: item transaksi lunas atas nama user ini untuk event ini.
     */
    protected function findPurchase(int $userId, int $eventId): ?TransactionItem
    {
        return TransactionItem::where('event_id', $eventId)
            ->whereHas('transaction', function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->where('status', Transaction::STATUS_SUCCESS);
            })
            ->first();
    }
}