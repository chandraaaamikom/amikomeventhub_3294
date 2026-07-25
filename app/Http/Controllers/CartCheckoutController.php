<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Event;
use App\Models\Coupon;
use App\Models\Transaction;
use App\Services\TicketingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Midtrans\Config as MidtransConfig;
use Midtrans\Snap;
use RuntimeException;

class CartCheckoutController extends Controller
{
    public function __construct(protected TicketingService $ticketing)
    {
        MidtransConfig::$serverKey    = config('midtrans.server_key');
        MidtransConfig::$isProduction = (bool) config('midtrans.is_production');
        MidtransConfig::$isSanitized  = true;
        MidtransConfig::$is3ds        = true;
    }

    public function checkout(Request $request)
    {
        $items = $this->getCartItems($request->query('selected_ids', []));

        if (empty($items)) {
            return redirect()->route('cart.index')
                ->with('error', 'Pilih setidaknya satu item untuk checkout atau gunakan Checkout Semua.');
        }

        $totalAmount = 0;
        foreach ($items as $item) {
            $totalAmount += ($item['event']->currentPrice() * $item['quantity'])
                + ($item['event']->isFree() ? 0 : TicketingService::SERVICE_FEE);
        }

        $selectedIds = collect($items)->pluck('event.id')->all();

        return view('cart-checkout', compact('items', 'totalAmount', 'selectedIds'));
    }

    public function process(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'coupon_code' => 'nullable|string|max:30',
        ]);

        $items = $this->getCartItems($request->input('selected_ids', []));

        if (empty($items)) {
            return redirect()->route('cart.index')
                ->with('error', 'Pilih setidaknya satu item untuk checkout.');
        }

        $coupon = null;
        if ($request->filled('coupon_code')) {
            $coupon = Coupon::where('code', strtoupper(trim($request->coupon_code)))->first();
            if (! $coupon) return back()->withInput()->with('error', 'Kode kupon tidak ditemukan.');
        }

        // Kunci kuota semua item sekaligus. Bila satu event kehabisan stok,
        // seluruh transaksi dibatalkan dan kuncian ikut batal (DB::transaction).
        try {
            $transaction = $this->ticketing->reserve(
                lines: array_map(fn ($i) => ['event' => $i['event'], 'quantity' => $i['quantity']], $items),
                customer: $request->only('name', 'email', 'phone'),
                userId: Auth::id(),
                coupon: $coupon,
            );
        } catch (RuntimeException $e) {
            return redirect()->route('cart.index')->with('error', $e->getMessage());
        }

        // Semua event gratis: stok langsung dipotong dan e-ticket diterbitkan,
        // tanpa Snap/Midtrans dan tanpa biaya layanan.
        if (collect($items)->every(fn ($item) => $item['event']->isFree())) {
            $this->ticketing->fulfill($transaction);
            $this->clearCart(collect($items)->pluck('event.id')->all());

            return redirect()->route('ticket', $transaction->order_id)
                ->with('success', 'Pendaftaran event gratis berhasil. E-ticket Anda sudah diterbitkan.');
        }

        if (blank(config('midtrans.server_key'))) {
            $this->ticketing->release($transaction, Transaction::STATUS_FAILED);

            return back()->with('error', 'MIDTRANS_SERVER_KEY belum diset. Periksa .env Anda.');
        }

        try {
            $snapToken = $this->buildSnapToken($transaction);
        } catch (\Throwable $e) {
            $this->ticketing->release($transaction, Transaction::STATUS_FAILED);
            Log::error('Midtrans Snap error (cart): ' . $e->getMessage());

            return redirect()->route('cart.index')
                ->with('error', 'Gagal membuat token pembayaran. Silakan coba lagi.');
        }

        $transaction->update(['snap_token' => $snapToken]);

        $this->clearCart(collect($items)->pluck('event.id')->all());

        return redirect()->route('checkout.payment', $transaction->order_id)
            ->with('success', 'Kuota tiket Anda dikunci selama ' . TicketingService::RESERVATION_MINUTES . ' menit. Selesaikan pembayaran sebelum waktu habis.');
    }

    protected function buildSnapToken(Transaction $transaction): string
    {
        $itemDetails = [];

        foreach ($transaction->transactionItems as $item) {
            $itemDetails[] = [
                'id'       => 'event-' . $item->event_id,
                'price'    => $item->price,
                'quantity' => $item->quantity,
                'name'     => mb_substr($item->title, 0, 50),
            ];

            if ($item->price > 0) {
                $itemDetails[] = [
                    'id'       => 'fee-' . $item->event_id,
                    'price'    => TicketingService::SERVICE_FEE,
                    'quantity' => 1,
                    'name'     => 'Biaya Layanan',
                ];
            }
        }

        if ($transaction->discount_amount > 0) {
            $itemDetails[] = ['id' => 'discount-' . $transaction->id, 'price' => -$transaction->discount_amount, 'quantity' => 1, 'name' => 'Diskon kupon'];
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

    // --- Keranjang ----------------------------------------------------------

    protected function hasCartTable(): bool
    {
        return Schema::hasTable('cart_items');
    }

    protected function syncSessionToDatabase(): void
    {
        if (! Auth::check() || ! $this->hasCartTable()) {
            return;
        }

        $cart = session('cart', []);

        if (empty($cart)) {
            return;
        }

        foreach ($cart as $eventId => $item) {
            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $event = Event::find($eventId);

            if (! $event) {
                continue;
            }

            $cartItem = CartItem::firstOrNew(['user_id' => Auth::id(), 'event_id' => $event->id]);
            $cartItem->quantity = min($event->available_stock, $cartItem->quantity + $quantity);
            $cartItem->save();
        }

        session()->forget('cart');
    }

    protected function getCartItems($selectedIds = null): array
    {
        $items = [];

        if (Auth::check() && $this->hasCartTable()) {
            $this->syncSessionToDatabase();

            foreach (CartItem::with('event')->where('user_id', Auth::id())->get() as $cartItem) {
                if (! $cartItem->event) {
                    continue;
                }

                $items[] = [
                    'event'    => $cartItem->event,
                    'quantity' => max(1, (int) $cartItem->quantity),
                    'price'    => $cartItem->event->currentPrice(),
                    'subTotal' => $cartItem->event->currentPrice() * max(1, (int) $cartItem->quantity),
                ];
            }
        } else {
            foreach (session('cart', []) as $eventId => $item) {
                $event = Event::find($eventId);

                if (! $event) {
                    continue;
                }

                $quantity = max(1, (int) ($item['quantity'] ?? 1));

                $items[] = [
                    'event'    => $event,
                    'quantity' => $quantity,
                    'price'    => $event->currentPrice(),
                    'subTotal' => $event->currentPrice() * $quantity,
                ];
            }
        }

        // Event yang sudah lewat tidak boleh ikut checkout.
        $items = array_values(array_filter($items, fn ($i) => ! $i['event']->date->isPast()));

        return $this->filterCartItems($items, $selectedIds);
    }

    protected function filterCartItems(array $items, $selectedIds): array
    {
        if (empty($selectedIds)) {
            return $items;
        }

        $selected = array_map('intval', (array) $selectedIds);

        return array_values(array_filter(
            $items,
            fn ($item) => in_array($item['event']->id, $selected, true)
        ));
    }

    /**
     * Hanya item yang benar-benar di-checkout yang dihapus.
     */
    protected function clearCart(array $eventIds): void
    {
        if (Auth::check() && $this->hasCartTable()) {
            CartItem::where('user_id', Auth::id())->whereIn('event_id', $eventIds)->delete();
        }

        $cart = session('cart', []);

        foreach ($eventIds as $id) {
            unset($cart[$id]);
        }

        session(['cart' => $cart]);
    }
}
