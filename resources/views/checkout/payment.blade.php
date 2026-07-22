@extends('layouts.app')

@section('content')
<main class="max-w-3xl mx-auto px-6 py-20 text-center">
    <div class="bg-white rounded-3xl border border-slate-200 p-12 shadow-sm inline-block w-full max-w-md">
        <div class="w-20 h-20 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3-1.343 3-3-1.343-3-3-3zm0 0V5m0 14v-3m9-7c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z"></path>
            </svg>
        </div>
        <h2 class="text-2xl font-black mb-2">Selesaikan Pembayaran</h2>
        <p class="text-slate-500 mb-6">Pesanan <strong>{{ $transaction->order_id }}</strong></p>

        {{-- Hitung mundur reservasi (Soal 2 — Reserved Ticket) --}}
        <div id="countdown-box" class="p-4 rounded-2xl bg-amber-50 border border-amber-200 mb-6">
            <p class="text-xs font-bold uppercase tracking-wider text-amber-700 mb-1">Kuota tiket dikunci untuk Anda</p>
            <p class="text-3xl font-black text-amber-700 tabular-nums" id="countdown">--:--</p>
            <p class="text-xs text-amber-600 mt-1">Selesaikan sebelum waktu habis</p>
        </div>

        <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100 mb-8 text-left">
            @foreach($transaction->transactionItems as $item)
                <div class="flex justify-between text-sm mb-2">
                    <span class="text-slate-600">{{ $item->quantity }}× {{ $item->title }}</span>
                    <span class="font-semibold text-slate-700">Rp {{ number_format($item->sub_total, 0, ',', '.') }}</span>
                </div>
            @endforeach
            <div class="flex justify-between pt-3 mt-3 border-t border-slate-200">
                <span class="font-bold text-slate-700">Total Tagihan</span>
                <span class="text-xl font-black text-indigo-600">Rp {{ number_format($transaction->total_price, 0, ',', '.') }}</span>
            </div>
            <p class="text-xs text-slate-400 mt-3">Email: {{ $transaction->customer_email }}</p>
        </div>

        <button id="pay-button" class="w-full py-5 bg-indigo-600 text-white rounded-2xl font-black text-xl shadow-xl shadow-indigo-200 hover:bg-indigo-700 transition">
            Bayar Sekarang
        </button>

        <div id="expired-box" class="hidden p-6 rounded-2xl bg-rose-50 border border-rose-200">
            <p class="font-bold text-rose-700">Waktu pembayaran habis</p>
            <p class="text-sm text-rose-600 mt-1">Kuota telah dilepas kembali. Silakan pesan ulang.</p>
            <a href="{{ route('home') }}" class="mt-4 inline-block rounded-2xl bg-rose-600 px-6 py-3 text-white font-bold hover:bg-rose-700 transition">Kembali ke Beranda</a>
        </div>
    </div>
</main>

<script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ config('midtrans.client_key') }}"></script>
<script>
    const expiresAt = new Date("{{ optional($transaction->expires_at)->toIso8601String() }}").getTime();
    const countdownEl = document.getElementById('countdown');
    const payButton = document.getElementById('pay-button');
    const countdownBox = document.getElementById('countdown-box');
    const expiredBox = document.getElementById('expired-box');

    function tick() {
        const remaining = Math.floor((expiresAt - Date.now()) / 1000);

        if (isNaN(expiresAt) || remaining <= 0) {
            countdownEl.textContent = '00:00';
            countdownBox.classList.add('hidden');
            payButton.classList.add('hidden');
            expiredBox.classList.remove('hidden');
            clearInterval(timer);
            return;
        }

        const m = String(Math.floor(remaining / 60)).padStart(2, '0');
        const s = String(remaining % 60).padStart(2, '0');
        countdownEl.textContent = `${m}:${s}`;
    }

    tick();
    const timer = setInterval(tick, 1000);

    payButton.onclick = function () {
        snap.pay('{{ $transaction->snap_token }}', {
            onSuccess: () => window.location.href = "{{ route('checkout.success', $transaction->order_id) }}",
            onPending: () => window.location.href = "{{ route('checkout.success', $transaction->order_id) }}",
            onError:   () => alert('Pembayaran gagal. Silakan coba lagi.'),
            onClose:   () => alert('Anda menutup jendela pembayaran sebelum selesai.'),
        });
    };
</script>
@endsection