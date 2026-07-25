@extends('layouts.app')

@section('content')
<main class="max-w-7xl mx-auto px-6 py-20">

    {{-- Header penyelenggara --}}
    <section class="rounded-[2.5rem] bg-indigo-600 p-10 md:p-14 text-white shadow-2xl shadow-indigo-200 relative overflow-hidden mb-12">
        <div class="relative z-10 grid gap-8 lg:grid-cols-[1fr_auto] lg:items-center">
            <div class="flex flex-col sm:flex-row gap-6 sm:items-center">
                @if($organization->logoUrl())
                    <img src="{{ $organization->logoUrl() }}" alt="{{ $organization->name }}"
                         class="w-24 h-24 rounded-3xl object-cover bg-white shadow-xl shrink-0">
                @else
                    <div class="w-24 h-24 rounded-3xl bg-white text-indigo-600 flex items-center justify-center text-4xl font-black shadow-xl shrink-0">
                        {{ strtoupper(substr($organization->name, 0, 2)) }}
                    </div>
                @endif

                <div>
                    <p class="text-indigo-200 font-bold uppercase tracking-widest text-xs mb-2">Penyelenggara</p>
                    <h1 class="text-4xl md:text-5xl font-black leading-tight">{{ $organization->name }}</h1>
                    @if($organization->description)
                        <p class="mt-4 text-indigo-100 max-w-2xl leading-relaxed">{{ $organization->description }}</p>
                    @endif

                    <div class="mt-5 flex flex-wrap gap-x-6 gap-y-2 text-sm text-indigo-100">
                        @if($organization->contact_email)
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                {{ $organization->contact_email }}
                            </span>
                        @endif
                        @if($organization->contact_phone)
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                {{ $organization->contact_phone }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Statistik --}}
            <div class="grid grid-cols-3 gap-4 lg:gap-6 shrink-0">
                <div class="text-center">
                    <p class="text-3xl font-black">{{ $stats['events'] }}</p>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-indigo-200 mt-1">Event</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-black">{{ number_format($stats['tickets'], 0, ',', '.') }}</p>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-indigo-200 mt-1">Tiket Terjual</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-black">
                        {{ $stats['reviews'] > 0 ? number_format($stats['rating'], 1) : '—' }}
                    </p>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-indigo-200 mt-1">Rating</p>
                </div>
            </div>
        </div>

        <div class="absolute -right-20 -bottom-20 w-64 h-64 bg-white opacity-10 rounded-full"></div>
        <div class="absolute -left-10 -top-10 w-32 h-32 bg-indigo-400 opacity-20 rounded-full"></div>
    </section>

    {{-- Event mendatang --}}
    <section class="mb-16">
        <h2 class="text-3xl font-extrabold mb-2">Event Mendatang</h2>
        <p class="text-slate-500 font-medium mb-8">Acara yang sedang dibuka pendaftarannya.</p>

        @if($upcomingEvents->isEmpty())
            <div class="rounded-3xl border border-slate-200 bg-white p-12 text-center shadow-sm">
                <p class="text-slate-500 font-medium">Belum ada event mendatang dari penyelenggara ini.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($upcomingEvents as $event)
                    <div class="group bg-white rounded-3xl border border-slate-100 shadow-sm hover:shadow-2xl transition-all duration-300 overflow-hidden">
                        <div class="relative overflow-hidden aspect-[3/4]">
                            <img src="{{ asset('storage/' . $event->poster_path) }}" alt="{{ $event->title }}"
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            <div class="absolute top-4 left-4 px-3 py-1 bg-white/90 backdrop-blur rounded-lg text-xs font-bold uppercase text-indigo-600">
                                {{ $event->category->name ?? '-' }}
                            </div>
                            @if($event->isSoldOut())
                                <div class="absolute top-4 right-4 px-3 py-1 bg-rose-600 rounded-lg text-xs font-bold uppercase text-white">
                                    Habis
                                </div>
                            @endif
                        </div>

                        <div class="p-6">
                            <h3 class="text-xl font-bold mb-2 group-hover:text-indigo-600 transition">{{ $event->title }}</h3>
                            <div class="flex items-center gap-2 text-slate-500 text-sm mb-4">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>{{ $event->date->translatedFormat('d F Y, H:i') }}</span>
                            </div>

                            <div class="flex justify-between items-center pt-4 border-t">
                                <span class="text-2xl font-black text-indigo-600">
                                    {{ $event->isFree() ? 'Gratis' : 'Rp ' . number_format($event->currentPrice(), 0, ',', '.') }}
                                </span>
                                <a href="{{ route('events.show', $event->id) }}"
                                   class="px-5 py-2 bg-indigo-50 text-indigo-600 rounded-xl font-bold hover:bg-indigo-600 hover:text-white transition">
                                    Lihat Detail
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Riwayat review (Soal 1b) --}}
    <section class="mb-16">
        <h2 class="text-3xl font-extrabold mb-2">Ulasan Peserta</h2>
        <p class="text-slate-500 font-medium mb-8">Testimoni dari peserta yang telah mengikuti acara penyelenggara ini.</p>

        @if($stats['reviews'] === 0)
            <div class="rounded-3xl border border-slate-200 bg-white p-12 text-center shadow-sm">
                <p class="text-slate-500 font-medium">Belum ada ulasan.</p>
                <p class="text-sm text-slate-400 mt-2">Ulasan dapat diberikan peserta satu hari setelah acara selesai.</p>
            </div>
        @else
            <div class="grid gap-8 lg:grid-cols-[0.8fr_1.2fr]">
                {{-- Ringkasan rating --}}
                <aside class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm h-fit">
                    <div class="text-center pb-6 border-b border-slate-100">
                        <p class="text-6xl font-black text-slate-900">{{ number_format($stats['rating'], 1) }}</p>
                        <div class="flex justify-center gap-1 my-3">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="w-6 h-6 {{ $i <= round($stats['rating']) ? 'text-amber-400' : 'text-slate-200' }}"
                                     fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            @endfor
                        </div>
                        <p class="text-slate-500 font-medium">{{ $stats['reviews'] }} ulasan</p>
                    </div>

                    <div class="pt-6 space-y-3">
                        @foreach($ratingBreakdown as $star => $count)
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-bold text-slate-600 w-3">{{ $star }}</span>
                                <svg class="w-4 h-4 text-amber-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                <div class="flex-1 h-2 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full bg-amber-400"
                                         style="width: {{ $stats['reviews'] > 0 ? round($count / $stats['reviews'] * 100) : 0 }}%"></div>
                                </div>
                                <span class="text-xs font-bold text-slate-400 w-6 text-right">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                </aside>

                {{-- Daftar review --}}
                <div class="space-y-5">
                    @foreach($reviews as $review)
                        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                            <div class="flex items-start gap-4">
                                <img src="{{ $review->user->avatarUrl() }}" alt="{{ $review->user->name }}"
                                     class="w-11 h-11 rounded-2xl object-cover shrink-0">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="font-bold text-slate-900">{{ $review->user->name }}</p>
                                        <span class="text-xs text-slate-400">{{ $review->created_at->diffForHumans() }}</span>
                                    </div>

                                    <div class="flex items-center gap-2 mt-1 mb-3">
                                        <div class="flex gap-0.5">
                                            @for($i = 1; $i <= 5; $i++)
                                                <svg class="w-4 h-4 {{ $i <= $review->rating ? 'text-amber-400' : 'text-slate-200' }}"
                                                     fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            @endfor
                                        </div>
                                        @if($review->event)
                                            <span class="text-xs text-slate-400 truncate">• {{ $review->event->title }}</span>
                                        @endif
                                    </div>

                                    @if($review->comment)
                                        <p class="text-slate-600 leading-relaxed">{{ $review->comment }}</p>
                                    @else
                                        <p class="text-slate-400 italic text-sm">Peserta memberi rating tanpa komentar.</p>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        @endif
    </section>

    {{-- Event yang sudah lewat --}}
    @if($pastEvents->isNotEmpty())
        <section>
            <h2 class="text-3xl font-extrabold mb-2">Event Sebelumnya</h2>
            <p class="text-slate-500 font-medium mb-8">Jejak acara yang pernah diselenggarakan.</p>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach($pastEvents as $event)
                    <a href="{{ route('events.show', $event->id) }}"
                       class="flex gap-4 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm hover:shadow-lg transition">
                        <img src="{{ asset('storage/' . $event->poster_path) }}" alt="{{ $event->title }}"
                             class="w-16 h-20 rounded-2xl object-cover shrink-0 grayscale">
                        <div class="min-w-0">
                            <p class="font-bold text-slate-700 truncate">{{ $event->title }}</p>
                            <p class="text-xs text-slate-400">{{ $event->date->translatedFormat('d M Y') }}</p>
                            <p class="text-xs text-slate-400 truncate">{{ $event->location }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

</main>
@endsection
