@extends('layouts.admin')

@section('content')
<div class="p-8">
    <div class="flex flex-col gap-4 md:flex-row md:justify-between md:items-center mb-8">
        <div>
            <h1 class="text-3xl font-black text-slate-900">Kelola Organisasi</h1>
            <p class="text-slate-500 font-medium">Semua tenant penyelenggara dalam ekosistem AmikomEventHub.</p>
        </div>
        <a href="{{ route('admin.organizations.create') }}"
           class="px-6 py-3 bg-indigo-600 text-white rounded-2xl font-bold shadow-lg hover:bg-indigo-700 transition text-center">
            + Tambah Organisasi
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-3xl border border-emerald-200 bg-emerald-50 px-6 py-4 text-emerald-700 font-semibold">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-6 rounded-3xl border border-rose-200 bg-rose-50 px-6 py-4 text-rose-700 font-semibold">{{ session('error') }}</div>
    @endif

    <form method="GET" class="mb-6 flex gap-3 max-w-md">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari organisasi..."
               class="flex-1 rounded-2xl border border-slate-200 px-5 py-3 outline-none focus:border-indigo-600">
        <button type="submit" class="rounded-2xl bg-slate-900 px-6 py-3 text-white font-bold hover:bg-slate-800 transition">Cari</button>
    </form>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @forelse($organizations as $org)
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 {{ $org->is_active ? '' : 'opacity-60' }}">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div class="flex items-center gap-4 min-w-0">
                        @if($org->logoUrl())
                            <img src="{{ $org->logoUrl() }}" alt="{{ $org->name }}" class="w-14 h-14 rounded-2xl object-cover shrink-0">
                        @else
                            <div class="w-14 h-14 rounded-2xl bg-indigo-100 text-indigo-600 flex items-center justify-center font-black text-lg shrink-0">
                                {{ strtoupper(substr($org->name, 0, 2)) }}
                            </div>
                        @endif
                        <div class="min-w-0">
                            <p class="font-black text-slate-900 truncate">{{ $org->name }}</p>
                            <p class="text-xs text-slate-400">/{{ $org->slug }}</p>
                        </div>
                    </div>
                    @if($org->is_active)
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold uppercase shrink-0">Aktif</span>
                    @else
                        <span class="px-3 py-1 bg-slate-200 text-slate-500 rounded-full text-xs font-bold uppercase shrink-0">Nonaktif</span>
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
                    <div>
                        <p class="text-slate-400 text-xs uppercase font-bold">Event</p>
                        <p class="font-black text-slate-800">{{ $org->events_count }}</p>
                    </div>
                    <div>
                        <p class="text-slate-400 text-xs uppercase font-bold">Pendapatan</p>
                        <p class="font-black text-indigo-600">Rp {{ number_format($org->revenue, 0, ',', '.') }}</p>
                    </div>
                </div>

                @if($org->owner_names)
                    <p class="text-xs text-slate-500 mb-4">Ketua: <span class="font-semibold text-slate-700">{{ $org->owner_names }}</span></p>
                @else
                    <p class="text-xs text-amber-600 mb-4 font-semibold">Belum ada ketua</p>
                @endif

                <div class="flex gap-2">
                    <a href="{{ route('admin.organizations.edit', $org->slug) }}"
                       class="flex-1 text-center px-4 py-2.5 bg-indigo-50 text-indigo-600 rounded-xl font-bold hover:bg-indigo-600 hover:text-white transition">
                        Kelola
                    </a>
                    <a href="{{ route('organizations.show', $org->slug) }}" target="_blank"
                       class="px-4 py-2.5 bg-slate-50 text-slate-500 rounded-xl font-bold hover:bg-slate-200 transition">
                        Profil
                    </a>
                    <form action="{{ route('admin.organizations.toggle', $org->slug) }}" method="POST"
                          onsubmit="return confirm('{{ $org->is_active ? 'Nonaktifkan' : 'Aktifkan' }} {{ addslashes($org->name) }}?')">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                class="px-4 py-2.5 rounded-xl font-bold transition {{ $org->is_active ? 'bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white' : 'bg-green-50 text-green-600 hover:bg-green-600 hover:text-white' }}">
                            {{ $org->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-100 p-12 text-center">
                <p class="text-slate-500 font-medium">Belum ada organisasi.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection