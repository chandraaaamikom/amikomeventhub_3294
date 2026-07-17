@extends('layouts.organizer')

@section('title', 'Event Saya')

@section('content')
<header class="flex flex-col gap-4 md:flex-row md:justify-between md:items-center mb-10">
    <div>
        <h1 class="text-3xl font-black">Event Saya</h1>
        <p class="text-slate-500 font-medium">Kelola acara milik {{ $organization->name }}.</p>
    </div>
    <a href="{{ route('organizer.events.create') }}"
       class="px-6 py-3 bg-indigo-600 text-white rounded-2xl font-bold shadow-lg hover:bg-indigo-700 transition text-center">
        + Tambah Event Baru
    </a>
</header>

<form method="GET" class="mb-6 flex gap-3">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari judul event..."
           class="flex-1 rounded-2xl border border-slate-200 px-5 py-3 outline-none focus:border-indigo-600">
    <button type="submit" class="rounded-2xl bg-slate-900 px-6 py-3 text-white font-bold hover:bg-slate-800 transition">Cari</button>
    @if(request('search'))
        <a href="{{ route('organizer.events.index') }}" class="rounded-2xl border border-slate-200 px-6 py-3 font-bold text-slate-600 hover:bg-slate-50 transition">Reset</a>
    @endif
</form>

<div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 text-slate-400 uppercase text-[10px] font-black tracking-widest">
                <tr>
                    <th class="px-8 py-4">Poster</th>
                    <th class="px-8 py-4">Event</th>
                    <th class="px-8 py-4">Harga</th>
                    <th class="px-8 py-4">Stok</th>
                    <th class="px-8 py-4">Status</th>
                    <th class="px-8 py-4">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y border-t">
                @forelse($events as $event)
                <tr class="hover:bg-slate-50/50 transition">
                    <td class="px-8 py-6">
                        <img src="{{ asset('storage/' . $event->poster_path) }}"
                             alt="{{ $event->title }}"
                             class="w-16 h-20 rounded-xl object-cover shadow-sm bg-slate-100">
                    </td>

                    <td class="px-8 py-6 max-w-xs">
                        <p class="font-black text-slate-800 truncate">{{ $event->title }}</p>
                        <p class="text-xs text-slate-400">
                            {{ $event->category->name ?? '-' }} • {{ $event->date->translatedFormat('d M Y, H:i') }}
                        </p>
                        <p class="text-xs text-slate-400 truncate">{{ $event->location }}</p>
                    </td>

                    <td class="px-8 py-6 whitespace-nowrap">
                        @if($event->isFree())
                            <span class="font-bold text-emerald-600">Gratis</span>
                        @else
                            <p class="font-bold text-indigo-600">Rp {{ number_format($event->price, 0, ',', '.') }}</p>
                        @endif
                    </td>

                    <td class="px-8 py-6 whitespace-nowrap">
                        <p class="font-bold text-slate-700">{{ $event->available_stock }} tersedia</p>
                        @if($event->reserved_stock > 0)
                            <p class="text-xs text-amber-600 font-semibold">{{ $event->reserved_stock }} dikunci checkout</p>
                        @endif
                        <p class="text-xs text-slate-400">Stok fisik: {{ $event->stock }}</p>
                    </td>

                    <td class="px-8 py-6 whitespace-nowrap">
                        @if($event->date->isPast())
                            <span class="px-3 py-1 bg-slate-100 text-slate-500 rounded-lg text-xs font-bold uppercase">Selesai</span>
                        @elseif($event->isSoldOut())
                            <span class="px-3 py-1 bg-rose-100 text-rose-700 rounded-lg text-xs font-bold uppercase">Habis</span>
                        @else
                            <span class="px-3 py-1 bg-green-100 text-green-700 rounded-lg text-xs font-bold uppercase">Aktif</span>
                        @endif
                    </td>

                    <td class="px-8 py-6">
                        <div class="flex gap-2">
                            <a href="{{ route('events.show', $event->id) }}" target="_blank"
                               title="Lihat halaman publik"
                               class="p-2.5 bg-slate-50 text-slate-500 rounded-xl hover:bg-slate-200 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>

                            <a href="{{ route('organizer.events.edit', $event->id) }}"
                               title="Edit"
                               class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl hover:bg-indigo-600 hover:text-white transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                          stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </a>

                            <form action="{{ route('organizer.events.destroy', $event->id) }}" method="POST"
                                  onsubmit="return confirm('Hapus event {{ addslashes($event->title) }}? Tindakan ini tidak bisa dibatalkan.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Hapus"
                                        class="p-2.5 bg-rose-50 text-rose-600 rounded-xl hover:bg-rose-600 hover:text-white transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                              stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-8 py-16 text-center">
                        <p class="text-slate-500 font-medium mb-4">
                            @if(request('search'))
                                Tidak ada event yang cocok dengan pencarian "{{ request('search') }}".
                            @else
                                {{ $organization->name }} belum memiliki event.
                            @endif
                        </p>
                        @unless(request('search'))
                            <a href="{{ route('organizer.events.create') }}" class="text-indigo-600 font-bold hover:underline">Buat event pertama</a>
                        @endunless
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection