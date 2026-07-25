@extends('layouts.app')

@section('content')
<main class="max-w-5xl mx-auto px-6 py-20">
    <div class="mb-10">
        <h1 class="text-4xl font-black">Checkout Keranjang</h1>
        <p class="text-slate-500 mt-2">Bayar semua tiket yang ada di keranjang dalam satu transaksi.</p>
    </div>

    @if(session('error'))
        <div class="rounded-3xl border border-rose-200 bg-rose-50 p-6 text-rose-700 mb-8">{{ session('error') }}</div>
    @endif

    <div class="grid gap-8 lg:grid-cols-[1.3fr_0.7fr]">
        <section class="space-y-6 rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <h2 class="text-2xl font-black">Detail Pesanan</h2>
            @foreach($items as $item)
                <div class="rounded-3xl border border-slate-100 p-6">
                    <h3 class="text-xl font-bold mb-3">{{ $item['event']->title }}</h3>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="text-slate-600">Tanggal: {{ \Carbon\Carbon::parse($item['event']->date)->format('d M Y') }}</div>
                        <div class="text-slate-600">Jumlah: {{ $item['quantity'] }} tiket</div>
                        <div class="text-slate-600">Harga {{ $item['event']->currentPriceLabel() }}: Rp {{ number_format($item['price'],0,',','.') }}</div>
                        <div class="text-slate-600">Subtotal: Rp {{ number_format($item['subTotal'],0,',','.') }}</div>
                    </div>
                </div>
            @endforeach
        </section>

        <aside class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <h2 class="text-2xl font-black mb-6">Bayar Sekarang</h2>
            <div class="space-y-4 text-slate-600 mb-6">
                <div class="flex justify-between">
                    <span>Total Tiket</span>
                    <span>{{ collect($items)->sum('quantity') }} tiket</span>
                </div>
                <div class="flex justify-between">
                    <span>Biaya Layanan</span>
                    <span>Rp {{ number_format(collect($items)->sum(fn ($item) => $item['event']->isFree() ? 0 : 5000), 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-xl font-black text-slate-900 border-t border-slate-200 pt-4">
                    <span>Total Pembayaran</span>
                    <span>Rp {{ number_format($totalAmount, 0, ',', '.') }}</span>
                </div>
            </div>

            <form action="{{ route('cart.checkout.process') }}" method="POST" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Lengkap</label>
                    <input type="text" name="name" required class="w-full rounded-2xl border border-slate-200 px-5 py-4 outline-none focus:border-indigo-600">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Email</label>
                    <input type="email" name="email" required class="w-full rounded-2xl border border-slate-200 px-5 py-4 outline-none focus:border-indigo-600">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">No. WhatsApp</label>
                    <input type="text" name="phone" required class="w-full rounded-2xl border border-slate-200 px-5 py-4 outline-none focus:border-indigo-600">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Kode Kupon <span class="font-normal text-slate-400">(opsional)</span></label>
                    <input type="text" name="coupon_code" value="{{ old('coupon_code') }}" placeholder="Contoh: MAHASISWA50" class="w-full uppercase rounded-2xl border border-slate-200 px-5 py-4 outline-none focus:border-indigo-600">
                </div>
                <button type="submit" class="w-full rounded-2xl bg-indigo-600 px-5 py-4 text-white font-black hover:bg-indigo-700 transition">
                    {{ collect($items)->every(fn ($item) => $item['event']->isFree()) ? 'Daftar Gratis & Terbitkan Tiket' : 'Proses Pembayaran' }}
                </button>
            </form>
        </aside>
    </div>
</main>
@endsection
