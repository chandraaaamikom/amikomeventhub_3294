<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $organization = $request->attributes->get('organization');

        $events = $organization->events()
            ->with('category')
            ->when($request->query('search'), fn ($q, $s) => $q->where('title', 'LIKE', "%{$s}%"))
            ->latest('date')
            ->get();

        return view('organizer.events.index', compact('organization', 'events'));
    }

    public function create(Request $request)
    {
        $organization = $request->attributes->get('organization');
        $categories = Category::orderBy('name')->get();

        return view('organizer.events.create', compact('organization', 'categories'));
    }

    public function store(Request $request)
    {
        $organization = $request->attributes->get('organization');

        $data = $this->validated($request, required: true);

        // organization_id TIDAK PERNAH diambil dari input. Selalu dari sesi.
        $data['organization_id'] = $organization->id;
        $data['reserved_stock'] = 0;
        $data['poster_path'] = $request->file('poster')->store('posters', 'public');

        $event = Event::create($data);

        return redirect()->route('organizer.events.index')
            ->with('success', "Event \"{$event->title}\" berhasil dibuat.");
    }

    public function edit(Request $request, Event $event)
    {
        $organization = $this->authorizeEvent($request, $event);
        $categories = Category::orderBy('name')->get();

        return view('organizer.events.edit', compact('organization', 'event', 'categories'));
    }

    public function update(Request $request, Event $event)
    {
        $organization = $this->authorizeEvent($request, $event);

        $data = $this->validated($request, required: false);

        // Stok baru tidak boleh di bawah yang sudah terjual + yang sedang dikunci.
        $sold = $this->soldCount($event);
        $minimum = $event->reserved_stock;

        if ($data['stock'] < $minimum) {
            return back()->withInput()->with(
                'error',
                "Stok tidak bisa diturunkan ke {$data['stock']}: ada {$minimum} tiket yang sedang dalam proses checkout."
            );
        }

        if ($request->hasFile('poster')) {
            if ($event->poster_path) {
                Storage::disk('public')->delete($event->poster_path);
            }
            $data['poster_path'] = $request->file('poster')->store('posters', 'public');
        }

        // organization_id sengaja tidak ikut: event tidak boleh berpindah tenant.
        $event->update($data);

        return redirect()->route('organizer.events.index')
            ->with('success', "Event \"{$event->title}\" berhasil diperbarui.");
    }

    public function destroy(Request $request, Event $event)
    {
        $this->authorizeEvent($request, $event);

        // Event yang sudah punya penjualan tidak boleh dihapus — riwayat uang
        // dan tiket peserta akan ikut hilang karena cascadeOnDelete.
        if ($this->soldCount($event) > 0) {
            return back()->with(
                'error',
                'Event ini sudah memiliki tiket terjual dan tidak dapat dihapus. Hubungi Superadmin bila perlu pembatalan.'
            );
        }

        if ($event->poster_path) {
            Storage::disk('public')->delete($event->poster_path);
        }

        $title = $event->title;
        $event->delete();

        return redirect()->route('organizer.events.index')
            ->with('success', "Event \"{$title}\" berhasil dihapus.");
    }

    // -----------------------------------------------------------------------

    /**
     * Pagar tenant. Tanpa ini, panitia HIMA SI bisa mengedit event UKM Musik
     * hanya dengan mengganti angka di URL.
     */
    protected function authorizeEvent(Request $request, Event $event): Organization
    {
        $organization = $request->attributes->get('organization');

        // Superadmin (mode pengawas) boleh melihat semuanya.
        if ($request->user()->isSuperadmin()) {
            return $event->organization ?? $organization;
        }

        if ($event->organization_id !== $organization->id) {
            // 404, bukan 403: jangan konfirmasi bahwa event itu ada.
            throw new NotFoundHttpException();
        }

        return $organization;
    }

    protected function soldCount(Event $event): int
    {
        return (int) TransactionItem::where('event_id', $event->id)
            ->whereHas('transaction', fn ($q) => $q->where('status', Transaction::STATUS_SUCCESS))
            ->sum('quantity');
    }

    protected function validated(Request $request, bool $required): array
    {
        return $request->validate([
            'category_id' => ['required', Rule::exists('categories', 'id')],
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'date'        => ['required', 'date'],
            'location'    => ['required', 'string', 'max:255'],
            'price'       => ['required', 'integer', 'min:0'],
            'early_bird_price' => ['nullable', 'integer', 'min:0', 'lte:price'],
            'early_bird_ends_at' => ['nullable', 'date'],
            'presale_price' => ['nullable', 'integer', 'min:0', 'lte:price'],
            'presale_ends_at' => ['nullable', 'date', 'after:early_bird_ends_at'],
            'stock'       => ['required', 'integer', 'min:0'],
            'poster'      => [$required ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'poster.required' => 'Poster event wajib diunggah.',
            'price.min'       => 'Harga tidak boleh negatif. Isi 0 untuk event gratis.',
        ]);
    }
}
