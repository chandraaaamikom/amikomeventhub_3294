@extends('layouts.app')

@section('content')
<main class="max-w-7xl mx-auto px-6 py-20">
    <div class="mb-10 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-4xl font-black">Keranjang Tiket</h1>
            <p class="text-slate-500 mt-2">Kelola jumlah tiket sebelum melanjutkan ke pembayaran.</p>
            <div class="mt-4 rounded-3xl bg-slate-100 p-4 text-slate-700">
                <p class="font-semibold">Perhatian:</p>
                <p class="text-sm">Klik tombol <span class="font-bold">Lanjutkan ke Checkout Keranjang</span> untuk membayar semua tiket di keranjang dalam satu transaksi.</p>
            </div>
        </div>
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl border border-slate-200 text-slate-700 hover:bg-slate-50 transition">
            Kembali ke Event
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-3xl border border-emerald-200 bg-emerald-50 px-6 py-4 text-emerald-700">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 rounded-3xl border border-rose-200 bg-rose-50 px-6 py-4 text-rose-700">
            {{ session('error') }}
        </div>
    @endif

    @if($items->isEmpty())
        <div class="rounded-3xl border border-slate-200 bg-white p-12 text-center shadow-sm">
            <h2 class="text-2xl font-bold mb-4">Keranjang Anda kosong</h2>
            <p class="text-slate-500">Tambahkan tiket dari halaman detail event lalu kembali ke sini untuk melanjutkan.</p>
        </div>
    @else
        <div class="grid grid-cols-1 xl:grid-cols-[1.7fr_1fr] gap-8">
            <div class="space-y-6" id="selectedCheckoutSection">
                @foreach($items as $item)
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex gap-4 items-start">
                                <div class="mt-2">
                                    <input type="checkbox" name="selected_ids[]" value="{{ $item['event']->id }}" id="select-item-{{ $item['event']->id }}" class="mt-2 h-5 w-5 text-indigo-600 rounded focus:ring-indigo-500" checked>
                                </div>
                                <img src="{{ asset('storage/' . $item['event']->poster_path) }}" alt="{{ $item['event']->title }}" class="h-28 w-28 rounded-3xl object-cover">
                                <div>
                                    <h3 class="text-xl font-bold">{{ $item['event']->title }}</h3>
                                    <p class="text-slate-500 mt-2">{{ \Carbon\Carbon::parse($item['event']->date)->format('d M Y') }}</p>
                                    <p class="text-slate-500">Stok tersisa: {{ $item['event']->stock }}</p>
                                </div>
                            </div>

                            <div class="grid gap-3 text-right">
                                <span class="text-slate-500">Rp {{ number_format($item['event']->currentPrice(), 0, ',', '.') }} / tiket <span class="text-xs">({{ $item['event']->currentPriceLabel() }})</span></span>
                                <span class="text-slate-900 font-bold">Subtotal: Rp {{ number_format($item['subTotal'], 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-4 lg:grid-cols-[1fr_auto_auto] lg:items-end">
                            <form action="{{ route('cart.update', $item['event']->id) }}" method="POST" class="grid gap-4 sm:grid-cols-[1fr_auto]">
                                @csrf
                                @method('PUT')
                                <label class="block text-sm font-semibold text-slate-700">Jumlah Tiket</label>
                                <div class="flex gap-3 items-center">
                                    <input type="number" name="quantity" min="1" max="{{ $item['event']->stock }}" value="{{ $item['quantity'] ?? 1 }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-indigo-600 text-black appearance-none" required>
                                    <button type="submit" class="rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white hover:bg-indigo-700 transition">Perbarui</button>
                                </div>
                            </form>

                            <div class="flex flex-wrap items-center gap-3 justify-end">
                                <form action="{{ route('cart.remove', $item['event']->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-3 text-sm font-semibold text-rose-700 hover:bg-rose-100 transition">Hapus</button>
                                </form>
                            </div>
                            <p class="mt-3 text-sm text-slate-500">Centang item untuk pilih tiket yang mau dicheckout terlebih dahulu.</p>
                        </div>
                    </div>
                @endforeach
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <button type="button" onclick="submitSelectedCheckout(event)" class="inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-8 py-4 text-white font-black hover:bg-indigo-700 transition">Checkout Item Terpilih</button>
                    <a href="{{ route('cart.checkout') }}" class="inline-flex items-center justify-center rounded-2xl bg-slate-200 px-8 py-4 text-slate-700 font-bold hover:bg-slate-300 transition">Checkout Semua Item</a>
                </div>
                <script>
                    function submitSelectedCheckout(event) {
                        event.preventDefault();
                        const selected = Array.from(document.querySelectorAll('#selectedCheckoutSection input[name="selected_ids[]"]:checked'));
                        if (selected.length === 0) {
                            alert('Pilih setidaknya satu item untuk checkout.');
                            return;
                        }
                        const params = new URLSearchParams();
                        selected.forEach(input => params.append('selected_ids[]', input.value));
                        window.location.href = '{{ route('cart.checkout') }}?' + params.toString();
                    }
                </script>
            </div>

            <aside class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-2xl font-black mb-4">Ringkasan Keranjang</h2>
                <div class="space-y-4 text-slate-600">
                    <div class="flex justify-between">
                        <span>Total Tiket</span>
                        <span>{{ $totalQuantity }} buah</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Biaya Layanan</span>
                        <span>Rp {{ number_format($items->sum('serviceFee'), 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-xl font-black text-slate-900 border-t border-slate-200 pt-4">
                        <span>Estimasi Total</span>
                        <span>Rp {{ number_format($grandTotal, 0, ',', '.') }}</span>
                    </div>
                </div>
                <p class="mt-6 text-sm text-slate-500">Pilih item yang ingin dibayar terlebih dahulu, atau gunakan tombol Checkout Semua Item.</p>
                <a href="{{ route('cart.checkout') }}" class="mt-6 inline-flex w-full items-center justify-center rounded-2xl bg-indigo-600 px-5 py-4 text-white font-black hover:bg-indigo-700 transition">Checkout Semua Item</a>
            </aside>
        </div>
    @endif
</main>
@endsection
