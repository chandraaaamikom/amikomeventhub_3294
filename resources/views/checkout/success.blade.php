@extends('layouts.app')

@section('content')
<div class="py-12 bg-slate-50 min-h-screen font(['Plus Jakarta Sans'])">
    <div class="max-w-3xl mx-auto px-4">
        <div class="bg-white rounded-[2.5rem] p-8 md:p-12 shadow-sm border border-slate-100 text-center">
            
            <div class="mx-auto w-16 h-16 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mb-6">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>

            <span class="text-xs font-black text-indigo-600 uppercase tracking-widest block mb-2">Pembayaran Berhasil</span>
            <h1 class="text-3xl font-black text-slate-900 mb-4">Terima Kasih Atas Order Anda</h1>
            <p class="text-slate-600 mb-8">Tiket elektronik Anda telah diterbitkan. Silakan simpan kode QR di bawah ini untuk dibawa ke lokasi acara.</p>

            <div class="border-t border-b border-dashed border-slate-200 py-6 my-6 text-left">
                <div class="flex justify-between mb-2">
                    <span class="text-slate-500">ID Transaksi</span>
                    <span class="font-mono font-bold text-slate-900">{{ $transaction->id }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Total Bayar</span>
                    <span class="font-black text-indigo-600">Rp {{ number_format($transaction->total_price, 0, ',', '.') }}</span>
                </div>
            </div>

            <h3 class="text-lg font-black text-slate-900 mb-6 text-left">Tiket Masuk Anda</h3>
            
            <div class="space-y-6 text-left">
                @foreach($transaction->tickets as $ticket)
                    <div class="bg-slate-50 rounded-2xl p-6 border border-slate-100 flex flex-col md:flex-row items-center justify-between gap-6">
                        <div>
                            <span class="text-[10px] font-black bg-indigo-100 text-indigo-700 px-2.5 py-1 rounded-full uppercase tracking-widest block w-max mb-2">
                                {{ $ticket->event->title ?? 'Event' }}
                            </span>
                            <h4 class="text-md font-bold text-slate-900 mb-1">Pemilik Tiket: {{ $transaction->user->name }}</h4>
                            <p class="text-xs text-slate-500 font-mono">CODE: {{ $ticket->code }}</p>
                            
                            @if($ticket->checked_in_at)
                                <span class="mt-3 inline-flex items-center text-xs text-amber-600 font-bold bg-amber-50 px-3 py-1 rounded-xl">
                                    Sudah Digunakan pada {{ $ticket->checked_in_at }}
                                </span>
                            @endif
                        </div>
                        <div class="bg-white p-3 rounded-2xl border border-slate-200 shadow-sm flex-shrink-0">
                            {!! QrCode::size(120)->margin(1)->generate($ticket->code) !!}
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-10">
                <a href="{{ url('/') }}" class="inline-block bg-indigo-600 hover:bg-indigo-900 text-white font-black text-sm uppercase tracking-widest px-8 py-4 rounded-2xl transition-all shadow-sm">
                    Kembali ke Beranda
                </a>
            </div>

        </div>
    </div>
</div>
@endsection