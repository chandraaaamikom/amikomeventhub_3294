@extends('layouts.organizer')

@section('title', 'Dashboard Panitia')

@section('content')
<header class="mb-10">
    <h1 class="text-3xl font-black text-slate-800">Dashboard {{ $organization->name }}</h1>
    <p class="text-slate-500 font-medium">Ringkasan penjualan dan performa event organisasi Anda.</p>
</header>

{{-- Kartu ringkasan --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
    <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
        <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mb-4">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <p class="text-slate-400 text-sm font-bold uppercase mb-1">Pendapatan Bersih</p>
        <h3 class="text-2xl font-black text-slate-800">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</h3>
        <p class="text-xs text-slate-400 mt-2">Di luar biaya layanan platform</p>
    </div>

    <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
        <div class="w-12 h-12 bg-green-50 text-green-600 rounded-2xl flex items-center justify-center mb-4">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
            </svg>
        </div>
        <p class="text-slate-400 text-sm font-bold uppercase mb-1">Tiket Terjual</p>
        <h3 class="text-2xl font-black text-slate-800">{{ number_format($ticketsSold, 0, ',', '.') }}</h3>
    </div>

    <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
        <div class="w-12 h-12 bg-orange-50 text-orange-600 rounded-2xl flex items-center justify-center mb-4">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
        </div>
        <p class="text-slate-400 text-sm font-bold uppercase mb-1">Event Mendatang</p>
        <h3 class="text-2xl font-black text-slate-800">{{ $activeEvents }} <span class="text-base font-bold text-slate-400">/ {{ $totalEvents }}</span></h3>
    </div>

    <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
        <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center mb-4">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
            </svg>
        </div>
        <p class="text-slate-400 text-sm font-bold uppercase mb-1">Rating Rata-rata</p>
        <h3 class="text-2xl font-black text-slate-800">
            {{ $reviewCount > 0 ? number_format($averageRating, 1) : '—' }}
            <span class="text-base font-bold text-slate-400">({{ $reviewCount }} ulasan)</span>
        </h3>
    </div>
</div>

{{-- Grafik pendapatan 6 bulan --}}
<div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-8 mb-10">
    <div class="flex justify-between items-end mb-8">
        <div>
            <h3 class="font-black text-xl text-slate-800">Pendapatan 6 Bulan Terakhir</h3>
            <p class="text-slate-500 text-sm font-medium">Dihitung dari tanggal pembayaran lunas.</p>
        </div>
        @if($pendingOrders > 0)
            <span class="px-4 py-2 rounded-2xl bg-orange-50 text-orange-700 text-sm font-bold">
                {{ $pendingOrders }} pesanan pending
            </span>
        @endif
    </div>

    @php $maxRevenue = max(collect($revenueByMonth)->max('total'), 1); @endphp

    <div class="flex items-end justify-between gap-4 h-56">
        @foreach($revenueByMonth as $bulan)
            <div class="flex-1 flex flex-col items-center justify-end h-full gap-3 group">
                <span class="text-xs font-bold text-slate-600 opacity-0 group-hover:opacity-100 transition">
                    Rp {{ number_format($bulan['total'], 0, ',', '.') }}
                </span>
                <div class="w-full rounded-t-2xl bg-indigo-600 hover:bg-indigo-700 transition-all"
                     style="height: {{ $bulan['total'] > 0 ? max(4, round($bulan['total'] / $maxRevenue * 100)) : 2 }}%">
                </div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wide">{{ $bulan['label'] }}</span>
            </div>
        @endforeach
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-[1.3fr_1fr] gap-8">
    {{-- Performa per event --}}
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="p-8 border-b flex justify-between items-center">
            <h3 class="font-black text-xl text-slate-800">Performa Event</h3>
            <a href="{{ route('organizer.events.index') }}" class="text-indigo-600 font-bold hover:underline transition">Kelola</a>
        </div>
        <div class="divide-y">
            @forelse($eventPerformance as $row)
                <div class="p-6 hover:bg-slate-50 transition">
                    <div class="flex justify-between items-start gap-4 mb-3">
                        <div class="min-w-0">
                            <p class="font-bold text-slate-800 truncate">{{ $row['event']->title }}</p>
                            <p class="text-xs text-slate-400">
                                {{ $row['event']->date->translatedFormat('d M Y') }}
                                @if($row['event']->reviews_count > 0)
                                    • {{ number_format($row['event']->reviews_avg_rating, 1) }} ★ ({{ $row['event']->reviews_count }})
                                @endif
                            </p>
                        </div>
                        <p class="font-black text-indigo-600 whitespace-nowrap">Rp {{ number_format($row['revenue'], 0, ',', '.') }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex-1 h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full bg-indigo-600" style="width: {{ $row['fill_rate'] }}%"></div>
                        </div>
                        <span class="text-xs font-bold text-slate-500 whitespace-nowrap">
                            {{ $row['sold'] }}/{{ $row['capacity'] }} ({{ $row['fill_rate'] }}%)
                        </span>
                    </div>
                </div>
            @empty
                <div class="p-10 text-center text-slate-500 font-medium">
                    Belum ada event. <a href="{{ route('organizer.events.create') }}" class="text-indigo-600 font-bold hover:underline">Buat event pertama</a>.
                </div>
            @endforelse
        </div>
    </div>

    {{-- Transaksi terakhir --}}
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="p-8 border-b flex justify-between items-center">
            <h3 class="font-black text-xl text-slate-800">Transaksi Terakhir</h3>
            <a href="{{ route('organizer.transactions.index') }}" class="text-indigo-600 font-bold hover:underline transition">Lihat Semua</a>
        </div>
        <div class="divide-y">
            @forelse($recentTransactions as $trx)
                @php $subTotal = $trx->transactionItems->sum('sub_total'); @endphp
                <div class="p-6 hover:bg-slate-50 transition">
                    <div class="flex justify-between items-start gap-4">
                        <div class="min-w-0">
                            <p class="font-bold text-slate-700 truncate">{{ $trx->customer_name }}</p>
                            <p class="text-xs text-slate-400 truncate">{{ $trx->order_id }} • {{ $trx->created_at->format('d M y H:i') }}</p>
                            <p class="text-xs text-slate-500 mt-1 truncate">
                                {{ $trx->transactionItems->pluck('title')->join(', ') }}
                            </p>
                        </div>
                        <div class="text-right whitespace-nowrap">
                            <p class="font-black text-indigo-600">Rp {{ number_format($subTotal, 0, ',', '.') }}</p>
                            @if($trx->status === 'success')
                                <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded-lg text-[10px] font-bold uppercase">Lunas</span>
                            @elseif($trx->status === 'pending')
                                <span class="px-2 py-0.5 bg-orange-100 text-orange-700 rounded-lg text-[10px] font-bold uppercase">Pending</span>
                            @else
                                <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-lg text-[10px] font-bold uppercase">{{ $trx->status }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-10 text-center text-slate-500 font-medium">Belum ada transaksi.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection