@extends('layouts.organizer')

@section('title', 'Penjualan')

@section('content')
<header class="mb-8">
    <h1 class="text-3xl font-black text-slate-900">Penjualan</h1>
    <p class="text-slate-500 font-medium">Transaksi yang memuat event milik {{ $organization->name }}.</p>
</header>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
        <p class="text-slate-400 text-sm font-bold uppercase mb-1">Pendapatan Bersih</p>
        <h3 class="text-2xl font-black text-slate-800">Rp {{ number_format($summary['revenue'], 0, ',', '.') }}</h3>
    </div>
    <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
        <p class="text-slate-400 text-sm font-bold uppercase mb-1">Tiket Terjual</p>
        <h3 class="text-2xl font-black text-slate-800">{{ number_format($summary['tickets'], 0, ',', '.') }}</h3>
    </div>
    <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
        <p class="text-slate-400 text-sm font-bold uppercase mb-1">Pesanan Pending</p>
        <h3 class="text-2xl font-black text-slate-800">{{ $summary['pending'] }}</h3>
    </div>
</div>

<form method="GET" class="mb-6 flex flex-col gap-3 sm:flex-row">
    <input type="text" name="search" value="{{ $search }}" placeholder="Cari order ID, nama, atau email..."
           class="flex-1 rounded-2xl border border-slate-200 px-5 py-3 outline-none focus:border-indigo-600">
    <select name="status" class="rounded-2xl border border-slate-200 px-5 py-3 outline-none focus:border-indigo-600 bg-white font-medium">
        <option value="">Semua status</option>
        <option value="success" {{ $status === 'success' ? 'selected' : '' }}>Lunas</option>
        <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Menunggu</option>
        <option value="failed" {{ $status === 'failed' ? 'selected' : '' }}>Gagal</option>
        <option value="expired" {{ $status === 'expired' ? 'selected' : '' }}>Kedaluwarsa</option>
    </select>
    <button type="submit" class="rounded-2xl bg-slate-900 px-6 py-3 text-white font-bold hover:bg-slate-800 transition">Filter</button>
    @if($search || $status)
        <a href="{{ route('organizer.transactions.index') }}" class="rounded-2xl border border-slate-200 px-6 py-3 font-bold text-slate-600 hover:bg-slate-50 transition text-center">Reset</a>
    @endif
</form>

<div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="px-8 py-5 text-xs font-bold uppercase tracking-wider text-slate-400">Order</th>
                    <th class="px-8 py-5 text-xs font-bold uppercase tracking-wider text-slate-400">Pembeli</th>
                    <th class="px-8 py-5 text-xs font-bold uppercase tracking-wider text-slate-400">Item (Organisasi Anda)</th>
                    <th class="px-8 py-5 text-xs font-bold uppercase tracking-wider text-slate-400 text-right">Subtotal</th>
                    <th class="px-8 py-5 text-xs font-bold uppercase tracking-wider text-slate-400">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $transaction)
                    @php $subTotal = $transaction->transactionItems->sum('sub_total'); @endphp
                    <tr class="border-b border-slate-50 hover:bg-slate-50 transition">
                        <td class="px-8 py-6 align-top">
                            <p class="font-bold text-slate-700 text-sm">{{ $transaction->order_id }}</p>
                            <p class="text-xs text-slate-400">{{ $transaction->created_at->format('d M Y H:i') }}</p>
                            @if($transaction->paid_at)
                                <p class="text-xs text-emerald-600 font-semibold">Lunas: {{ $transaction->paid_at->format('d M Y H:i') }}</p>
                            @endif
                        </td>
                        <td class="px-8 py-6 align-top max-w-[180px]">
                            <p class="font-bold text-slate-700 truncate">{{ $transaction->customer_name }}</p>
                            <p class="text-xs text-slate-400 truncate">{{ $transaction->customer_email }}</p>
                            <p class="text-xs text-slate-400">{{ $transaction->customer_phone }}</p>
                        </td>
                        <td class="px-8 py-6 align-top">
                            <div class="space-y-1">
                                @foreach($transaction->transactionItems as $item)
                                    <div class="flex gap-2 text-sm">
                                        <span class="font-bold text-slate-400 whitespace-nowrap">{{ $item->quantity }}×</span>
                                        <span class="text-slate-600">{{ $item->title }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-8 py-6 align-top text-right whitespace-nowrap">
                            <p class="font-black text-indigo-600">Rp {{ number_format($subTotal, 0, ',', '.') }}</p>
                            @if($subTotal !== $transaction->total_price)
                                <p class="text-[11px] text-slate-400 mt-1">dari total Rp {{ number_format($transaction->total_price, 0, ',', '.') }}</p>
                            @endif
                        </td>
                        <td class="px-8 py-6 align-top whitespace-nowrap">
                            @if($transaction->status === 'success')
                                <span class="px-3 py-1 bg-green-100 text-green-600 rounded-full text-xs font-bold">Lunas</span>
                            @elseif($transaction->status === 'pending')
                                <span class="px-3 py-1 bg-yellow-100 text-yellow-600 rounded-full text-xs font-bold">Menunggu</span>
                            @elseif($transaction->status === 'expired')
                                <span class="px-3 py-1 bg-slate-100 text-slate-500 rounded-full text-xs font-bold">Kedaluwarsa</span>
                            @else
                                <span class="px-3 py-1 bg-red-100 text-red-600 rounded-full text-xs font-bold">{{ ucfirst($transaction->status) }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-8 py-16 text-center text-slate-500 font-medium">
                            {{ $search || $status ? 'Tidak ada transaksi yang cocok dengan filter.' : 'Belum ada transaksi.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($transactions->hasPages())
        <div class="px-8 py-6 border-t border-slate-100">
            {{ $transactions->links() }}
        </div>
    @endif
</div>
@endsection
