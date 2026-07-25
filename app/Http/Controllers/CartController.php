<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class CartController extends Controller
{
    protected function hasCartTable(): bool
    {
        return Schema::hasTable('cart_items');
    }

    protected function syncSessionToDatabase()
    {
        if (!Auth::check() || !$this->hasCartTable()) {
            return;
        }

        $cart = session('cart', []);
        if (empty($cart)) {
            return;
        }

        $user = Auth::user();
        foreach ($cart as $eventId => $item) {
            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $event = Event::find($eventId);
            if (!$event) {
                continue;
            }
            $cartItem = CartItem::firstOrNew(['user_id' => $user->id, 'event_id' => $event->id]);
            $cartItem->quantity = min($event->stock, $cartItem->quantity + $quantity);
            $cartItem->save();
        }

        session()->forget('cart');
    }

    protected function getCartData()
    {
        $items = collect();

        if (Auth::check() && $this->hasCartTable()) {
            $this->syncSessionToDatabase();
            $cartItems = CartItem::with('event')->where('user_id', Auth::id())->get();
            foreach ($cartItems as $item) {
                if (!$item->event) {
                    continue;
                }
                $quantity = max(1, (int) $item->quantity);
                $items->push([
                    'event' => $item->event,
                    'quantity' => $quantity,
                    'subTotal' => $item->event->currentPrice() * $quantity,
                    'serviceFee' => $item->event->isFree() ? 0 : 5000,
                    'total' => ($item->event->currentPrice() * $quantity) + ($item->event->isFree() ? 0 : 5000),
                ]);
            }
        } else {
            $cart = session('cart', []);
            foreach ($cart as $eventId => $item) {
                $event = Event::find($eventId);
                if (!$event) {
                    continue;
                }
                $quantity = max(1, (int) ($item['quantity'] ?? 1));
                $items->push([
                    'event' => $event,
                    'quantity' => $quantity,
                    'subTotal' => $event->currentPrice() * $quantity,
                    'serviceFee' => $event->isFree() ? 0 : 5000,
                    'total' => ($event->currentPrice() * $quantity) + ($event->isFree() ? 0 : 5000),
                ]);
            }
        }

        return $items;
    }

    public function index()
    {
        $items = $this->getCartData();
        $grandTotal = $items->sum('total');
        $totalQuantity = $items->sum('quantity');

        return view('cart', compact('items', 'grandTotal', 'totalQuantity'));
    }

    public function add(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $event = Event::findOrFail($id);
        $quantity = (int) $request->quantity;

        if ($quantity > $event->stock) {
            return back()->with('error', 'Jumlah tiket yang dimasukkan melebihi stok tersedia.');
        }

        if (Auth::check() && $this->hasCartTable()) {
            $cartItem = CartItem::firstOrNew(['user_id' => Auth::id(), 'event_id' => $event->id]);
            $cartItem->quantity = min($event->stock, $cartItem->quantity + $quantity);
            $cartItem->save();
        } else {
            $cart = session('cart', []);
            $existingQuantity = isset($cart[$id]) ? (int) $cart[$id]['quantity'] : 0;
            $newQuantity = min($event->stock, $existingQuantity + $quantity);
            $cart[$id] = ['quantity' => $newQuantity];
            session(['cart' => $cart]);
        }

        return redirect()->route('cart.index')->with('success', 'Tiket berhasil ditambahkan ke keranjang.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $event = Event::findOrFail($id);
        $quantity = (int) $request->quantity;

        if ($quantity > $event->stock) {
            return back()->with('error', 'Jumlah tiket tidak boleh melebihi stok yang tersedia.');
        }

        if (Auth::check() && $this->hasCartTable()) {
            $cartItem = CartItem::where('user_id', Auth::id())->where('event_id', $id)->first();
            if (!$cartItem) {
                return back()->with('error', 'Item keranjang tidak ditemukan.');
            }
            $cartItem->quantity = $quantity;
            $cartItem->save();
        } else {
            $cart = session('cart', []);
            if (!isset($cart[$id])) {
                return back()->with('error', 'Item keranjang tidak ditemukan.');
            }
            $cart[$id]['quantity'] = $quantity;
            session(['cart' => $cart]);
        }

        return back()->with('success', 'Jumlah tiket di keranjang berhasil diperbarui.');
    }

    public function remove($id)
    {
        if (Auth::check() && $this->hasCartTable()) {
            $cartItem = CartItem::where('user_id', Auth::id())->where('event_id', $id)->first();
            if ($cartItem) {
                $cartItem->delete();
                return back()->with('success', 'Item berhasil dihapus dari keranjang.');
            }
            return back()->with('error', 'Item keranjang tidak ditemukan.');
        }

        $cart = session('cart', []);

        if (isset($cart[$id])) {
            unset($cart[$id]);
            session(['cart' => $cart]);
            return back()->with('success', 'Item berhasil dihapus dari keranjang.');
        }

        return back()->with('error', 'Item keranjang tidak ditemukan.');
    }
}
