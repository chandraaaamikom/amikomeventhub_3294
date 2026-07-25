@extends('layouts.admin')

@section('title', 'Admin Dashboard')
@section('page_title', 'Dashboard Ringkasan')

@section('content')
<header class="flex justify-between items-center mb-10">
    <div>
        <h1 class="text-3xl font-black text-slate-800">Dashboard Ringkasan</h1>
        <p class="text-slate-500 font-medium">Selamat datang kembali, Admin!</p>
    </div>
    <div class="flex items-center gap-4">
        <div class="text-right hidden md:block">
            <p class="font-bold text-slate-800">Admin Super</p>
            <p class="text-xs text-slate-400">Penyelenggara Utama</p>
        </div>
        <div class="w-12 h-12 bg-white rounded-2xl shadow-sm border flex items-center justify-center p-1">
            <img src="https://ui-avatars.com/api/?name=Admin+Super&background=6366f1&color=fff" class="rounded-xl">
        </div>
    </div>
</header>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
    <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
        <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mb-4">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <p class="text-slate-400 text-sm font-bold uppercase mb-1">Total Pendapatan</p>
        <h3 class="text-2xl font-black text-slate-800">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</h3>
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <p class="text-slate-400 text-sm font-bold uppercase mb-1">Event Aktif</p>
        <h3 class="text-2xl font-black text-slate-800">{{ $activeEvents }} Event</h3>
    </div>

    <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
        <div class="w-12 h-12 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center mb-4">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <p class="text-slate-400 text-sm font-bold uppercase mb-1">Pesanan Pending</p>
        <h3 class="text-2xl font-black text-slate-800">{{ $pendingOrders }} Pesanan</h3>
    </div>
</div>

{{-- Grafik ekosistem (Soal 2: Dashboard Admin Berbasis Grafik) --}}
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-10">
    @php $maxUsers = max(collect($usersByMonth)->max('total'), 1); @endphp
    <section class="bg-white rounded-3xl border border-slate-100 shadow-sm p-8">
        <div class="mb-8">
            <h3 class="font-black text-xl text-slate-800">Pertumbuhan Pengguna</h3>
            <p class="text-sm text-slate-500 mt-1">Pengguna baru dalam 6 bulan terakhir.</p>
        </div>
        <div class="flex items-end justify-between gap-4 h-56">
            @foreach($usersByMonth as $month)
                <div class="flex-1 h-full flex flex-col items-center justify-end gap-3 group">
                    <span class="text-xs font-bold text-slate-600 opacity-0 group-hover:opacity-100 transition">{{ $month['total'] }} user</span>
                    <div class="w-full rounded-t-2xl bg-indigo-600 hover:bg-indigo-700 transition"
                         style="height: {{ $month['total'] > 0 ? max(4, round($month['total'] / $maxUsers * 100)) : 2 }}%"></div>
                    <span class="text-xs font-bold text-slate-400 uppercase">{{ $month['label'] }}</span>
                </div>
            @endforeach
        </div>
    </section>

    @php $maxCategoryEvents = max(collect($eventsByCategory)->max('total'), 1); @endphp
    <section class="bg-white rounded-3xl border border-slate-100 shadow-sm p-8">
        <div class="mb-8">
            <h3 class="font-black text-xl text-slate-800">Statistik Event per Kategori</h3>
            <p class="text-sm text-slate-500 mt-1">Distribusi event yang tersedia di platform.</p>
        </div>
        <div class="space-y-5">
            @forelse($eventsByCategory as $category)
                <div>
                    <div class="flex justify-between gap-4 text-sm mb-2">
                        <span class="font-bold text-slate-700 truncate">{{ $category['label'] }}</span>
                        <span class="font-black text-indigo-600">{{ $category['total'] }} event</span>
                    </div>
                    <div class="h-3 rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-full rounded-full bg-indigo-600" style="width: {{ round($category['total'] / $maxCategoryEvents * 100) }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-slate-500 text-center py-14">Belum ada event untuk ditampilkan.</p>
            @endforelse
        </div>
    </section>
</div>

<div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="p-8 border-b flex justify-between items-center">
        <h3 class="font-black text-xl text-slate-800">Transaksi Terakhir</h3>
        <a href="{{ route('admin.transactions.index') }}" class="text-indigo-600 font-bold hover:underline transition">Lihat Semua</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 text-slate-400 uppercase text-[10px] font-black tracking-widest">
                <tr>
                    <th class="px-8 py-4 w-1/4">Tgl Transaksi</th>
                    <th class="px-8 py-4 w-1/4">Pembeli</th>
                    <th class="px-8 py-4 w-1/4">Event</th>
                    <th class="px-8 py-4 w-[10%]">Status</th>
                    <th class="px-8 py-4 text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y border-t">
                @forelse($recentTransactions as $trx)
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-8 py-6 text-sm text-slate-600 max-w-xs break-all">
                        {{ $trx->created_at->format('d M y - H:i') }}<br>
                        <span class="text-xs text-slate-400">{{ $trx->order_id }}</span>
                    </td>
                    <td class="px-8 py-6">
                        <p class="font-bold uppercase tracking-wide text-sm text-slate-700 truncate max-w-[150px]">
                            {{ $trx->name ?? $trx->customer_name ?? 'Pembeli' }}
                        </p>
                        <p class="text-xs text-slate-400 truncate max-w-[150px]">
                            {{ $trx->email ?? $trx->customer_email ?? '-' }}
                        </p>
                    </td>
                    <td class="px-8 py-6 font-medium text-slate-600 max-w-xs truncate">
                        {{ $trx->event->title ?? 'Event Tidak Ditemukan' }}
                    </td>
                    <td class="px-8 py-6 whitespace-nowrap">
                        @if(strtolower($trx->status) == 'success' || strtolower($trx->status) == 'settlement')
                            <span class="px-3 py-1 bg-green-100 text-green-700 rounded-lg text-xs font-bold uppercase">Success</span>
                        @elseif(strtolower($trx->status) == 'pending')
                            <span class="px-3 py-1 bg-orange-100 text-orange-700 rounded-lg text-xs font-bold uppercase">Pending</span>
                        @else
                            <span class="px-3 py-1 bg-red-100 text-red-700 rounded-lg text-xs font-bold uppercase">{{ $trx->status }}</span>
                        @endif
                    </td>
                    <td class="px-8 py-6 font-black text-indigo-600 whitespace-nowrap text-right">
                        Rp {{ number_format($trx->total_price, 0, ',', '.') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-8 py-10 text-center text-slate-500 font-medium">Belum ada transaksi resmi.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
