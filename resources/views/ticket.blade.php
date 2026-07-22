@extends('layouts.app')
@section('content')
<main class="max-w-3xl mx-auto px-6 py-20">
    <div class="text-center mb-10">
        <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <h1 class="text-4xl font-black text-slate-900 mb-2">
            @if($transaction->status === 'success')
                Tiket Anda Siap!
            @else
                Pesanan Diterima
            @endif
        </h1>
        <p class="text-slate-500">
            @if($transaction->status === 'success')
                Tunjukkan QR di bawah ini kepada petugas saat check-in.
            @else
                Pembayaran Anda sedang diproses. QR tiket muncul setelah pembayaran lunas.
            @endif
        </p>
    </div>

    {{-- Ringkasan pesanan --}}
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-8 mb-8">
        <div class="flex justify-between items-start mb-6 pb-6 border-b border-slate-100">
            <div>
                <p class="text-xs uppercase tracking-[0.25em] text-slate-400 mb-1">Order ID</p>
                <p class="text-lg font-black text-slate-900">{{ $transaction->order_id }}</p>
            </div>
            @if($transaction->status === 'success')
                <span class="px-4 py-2 rounded-full bg-green-100 text-green-700 font-bold text-sm">Lunas</span>
            @elseif($transaction->status === 'pending')
                <span class="px-4 py-2 rounded-full bg-orange-100 text-orange-700 font-bold text-sm">Menunggu Pembayaran</span>
            @else
                <span class="px-4 py-2 rounded-full bg-rose-100 text-rose-700 font-bold text-sm">{{ ucfirst($transaction->status) }}</span>
            @endif
        </div>

        <div class="grid gap-2 text-sm">
            <div class="flex justify-between"><span class="text-slate-500">Nama Pembeli</span><span class="font-semibold text-slate-700">{{ $transaction->customer_name }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">Email</span><span class="font-semibold text-slate-700">{{ $transaction->customer_email }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">Total Bayar</span><span class="font-black text-indigo-600">Rp {{ number_format($transaction->total_price, 0, ',', '.') }}</span></div>
        </div>
    </div>

    {{-- QR per tiket (Soal 2) --}}
    @if($transaction->status === 'success' && $transaction->tickets->count() > 0)
        <div class="space-y-6">
            <h2 class="text-2xl font-black text-slate-900">
                {{ $transaction->tickets->count() }} E-Ticket
            </h2>

            @foreach($transaction->tickets as $index => $ticket)
                <div class="bg-white rounded-3xl border-2 border-dashed border-slate-200 shadow-sm overflow-hidden">
                    <div class="grid md:grid-cols-[auto_1fr]">
                        {{-- QR --}}
                        <div class="flex items-center justify-center bg-slate-50 p-8">
                            <div class="bg-white p-4 rounded-2xl shadow-sm">
                                {!! QrCode::size(180)->margin(1)->generate($ticket->code) !!}
                            </div>
                        </div>

                        {{-- Detail --}}
                        <div class="p-8 flex flex-col justify-center">
                            <p class="text-xs uppercase tracking-widest text-slate-400 mb-1">Tiket #{{ $index + 1 }}</p>
                            <h3 class="text-xl font-black text-slate-900 mb-3">{{ $ticket->event->title ?? 'Event' }}</h3>

                            @if($ticket->event)
                                <div class="space-y-1 text-sm text-slate-500 mb-4">
                                    <p>{{ \Carbon\Carbon::parse($ticket->event->date)->translatedFormat('l, d F Y • H:i') }}</p>
                                    <p>{{ $ticket->event->location }}</p>
                                </div>
                            @endif

                            @if($ticket->checked_in_at)
                                <span class="inline-flex w-fit items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-100 text-slate-500 text-xs font-bold uppercase">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Sudah check-in {{ $ticket->checked_in_at->format('d M, H:i') }}
                                </span>
                            @else
                                <span class="inline-flex w-fit items-center gap-2 px-3 py-1.5 rounded-lg bg-green-50 text-green-600 text-xs font-bold uppercase">
                                    Belum digunakan
                                </span>
                            @endif

                            <p class="mt-4 text-[10px] text-slate-300 font-mono break-all">{{ $ticket->code }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="text-center mt-10">
        <a href="{{ route('home') }}" class="inline-block px-10 py-4 bg-indigo-600 text-white rounded-2xl font-bold hover:bg-indigo-700 transition">Kembali ke Beranda</a>
    </div>
</main>
@endsection