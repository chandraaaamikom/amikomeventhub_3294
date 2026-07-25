@extends('layouts.app')
@section('content')
 <main class="max-w-7xl mx-auto px-6 py-12 grid grid-cols-1 lg:grid-cols-3 gap-12">
        <!-- Left: Poster -->
        <div class="lg:col-span-1">
            <div class="sticky top-32">
                <img src="{{ asset('storage/' . $event->poster_path) }}" alt="{{ $event->title }}"
                    class="w-full rounded-[2.5rem] shadow-2xl border-8 border-white">
                <div class="mt-8 p-6 bg-white rounded-3xl border border-slate-100 shadow-sm">
                    <h4 class="font-bold mb-4">Kategori</h4>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 font-bold">
                            {{ strtoupper(substr($event->category->name, 0, 2)) }}
                        </div>
                        <div>
                            <p class="font-bold text-slate-800">{{ $event->category->name }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Details -->
        <div class="lg:col-span-2 space-y-12">
            @if(session('success'))
                <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700 mb-6">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="rounded-3xl border border-rose-200 bg-rose-50 p-4 text-rose-700 mb-6">
                    {{ session('error') }}
                </div>
            @endif
            @if($errors->any())
                <div class="rounded-3xl border border-rose-200 bg-rose-50 p-4 text-rose-700 mb-6">
                    <ul class="list-disc list-inside space-y-1 text-sm">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="space-y-4">
                <span class="px-4 py-1.5 bg-indigo-100 text-indigo-700 rounded-full text-sm font-bold uppercase tracking-wider">
                    {{ $event->category->name }}
                </span>
                <h1 class="text-4xl md:text-5xl font-black leading-tight">{{ $event->title }}</h1>
                <div class="flex flex-wrap gap-6 text-slate-500 font-medium">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                            </path>
                        </svg>
                        <span>{{ \Carbon\Carbon::parse($event->date)->translatedFormat('l, d F Y, H:i') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                            </path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span>{{ $event->location }}</span>
                    </div>
                </div>
            </div>

            <div class="prose prose-slate max-w-none">
                <h3 class="text-2xl font-bold mb-4">Deskripsi Event</h3>
                <p class="text-lg text-slate-600 leading-relaxed">
                    {{ $event->description }}
                </p>
            </div>

            <div
                class="bg-indigo-600 rounded-[2.5rem] p-8 md:p-12 text-white shadow-2xl shadow-indigo-200 relative overflow-hidden">
                <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-8">
                    <div>
                        <p class="text-indigo-200 font-bold uppercase tracking-widest text-sm mb-2">Harga Tiket</p>
                        <h2 class="text-5xl font-black">
                            @if($event->isFree())
                                Gratis
                            @else
                                {{ $event->isFree() ? 'Gratis' : 'Rp ' . number_format($event->currentPrice(), 0, ',', '.') }} <span class="text-lg font-medium text-indigo-200">{{ $event->isFree() ? '' : '/ orang' }}</span>
                                @if(! $event->isFree())<span class="block text-xs font-medium text-indigo-200">Harga {{ $event->currentPriceLabel() }}</span>@endif
                            @endif
                        </h2>
                        <p class="mt-4 text-slate-100 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Sisa stok: <span class="font-bold text-black underline bg-white/20 px-2 py-1 rounded-xl">{{ $event->available_stock }} Tiket lagi!</span>
                        </p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-[1fr_auto] items-center">
                        @if($event->isSoldOut())
                            <div class="rounded-2xl bg-white/20 px-8 py-5 text-center font-black text-xl">Tiket Habis</div>
                        @elseif($event->date->isPast())
                            <div class="rounded-2xl bg-white/20 px-8 py-5 text-center font-black text-xl">Acara Telah Selesai</div>
                        @else
                            <form action="{{ route('cart.add', $event->id) }}" method="POST" class="flex items-center gap-3">
                                @csrf
                                <input type="number" name="quantity" min="1" max="{{ $event->available_stock }}" value="1"
                                    class="w-24 rounded-2xl border border-slate-200 px-4 py-3 text-center text-black outline-none focus:border-indigo-600" required>
                                <button type="submit"
                                    class="rounded-2xl bg-white px-5 py-4 text-indigo-600 font-black hover:bg-indigo-50 transition shadow-xl">
                                    Tambah Keranjang
                                </button>
                            </form>
                            <a href="{{ route('checkout', $event->id) }}"
                                class="inline-block px-10 py-5 bg-white text-indigo-600 rounded-2xl font-black text-xl hover:scale-105 transition-transform shadow-xl">
                                Pesan Sekarang
                            </a>
                        @endif
                    </div>
                </div>
                <!-- Decoration -->
                <div class="absolute -right-20 -bottom-20 w-64 h-64 bg-white opacity-10 rounded-full"></div>
                <div class="absolute -left-10 -top-10 w-32 h-32 bg-indigo-400 opacity-20 rounded-full"></div>
            </div>

            <div class="space-y-4">
                <h3 class="text-xl font-bold">Kebijakan Tiket</h3>
                <ul class="space-y-3 text-slate-500">
                    <li class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-green-500 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                        E-Ticket akan dikirimkan otomatis setelah pembayaran berhasil.
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-green-500 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                        Tiket dapat discan di pintu masuk (Check-in).
                    </li>
                    <li class="flex items-start gap-2 text-rose-500">
                        <svg class="w-5 h-5 text-rose-500 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Tiket yang sudah dibeli tidak dapat direfund.
                    </li>
                </ul>
            </div>

            {{-- Penyelenggara (Soal 1c) --}}
            @if($event->organization)
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-4">Diselenggarakan oleh</p>
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-4 min-w-0">
                            @if($event->organization->logoUrl())
                                <img src="{{ $event->organization->logoUrl() }}" alt="{{ $event->organization->name }}"
                                     class="w-14 h-14 rounded-2xl object-cover shrink-0">
                            @else
                                <div class="w-14 h-14 rounded-2xl bg-indigo-100 text-indigo-600 flex items-center justify-center font-black text-lg shrink-0">
                                    {{ strtoupper(substr($event->organization->name, 0, 2)) }}
                                </div>
                            @endif
                            <div class="min-w-0">
                                <p class="font-bold text-slate-800 truncate">{{ $event->organization->name }}</p>
                                @php $orgReviewCount = $event->organization->reviews()->count(); @endphp
                                @if($orgReviewCount > 0)
                                    <p class="text-sm text-slate-500">
                                        {{ number_format($event->organization->averageRating(), 1) }} ★
                                        dari {{ $orgReviewCount }} ulasan
                                    </p>
                                @endif
                            </div>
                        </div>
                        <a href="{{ route('organizations.show', $event->organization->slug) }}"
                           class="px-5 py-2.5 bg-indigo-50 text-indigo-600 rounded-xl font-bold hover:bg-indigo-600 hover:text-white transition whitespace-nowrap">
                            Lihat Profil
                        </a>
                    </div>
                </div>
            @endif

            {{-- Ulasan (Soal 1b) --}}
            <div class="space-y-6">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h3 class="text-2xl font-bold">Ulasan Peserta</h3>
                        @if($reviews->count() > 0)
                            <div class="flex items-center gap-2 mt-2">
                                <div class="flex gap-0.5">
                                    @for($i = 1; $i <= 5; $i++)
                                        <svg class="w-5 h-5 {{ $i <= round($ratingAverage) ? 'text-amber-400' : 'text-slate-200' }}" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    @endfor
                                </div>
                                <span class="font-bold text-slate-700">{{ number_format($ratingAverage, 1) }}</span>
                                <span class="text-slate-400">({{ $reviews->count() }} ulasan)</span>
                            </div>
                        @endif
                    </div>
                </div>

                @include('partials._review-form')

                @if($reviews->count() > 0)
                    <div class="space-y-4">
                        @foreach($reviews as $review)
                            @continue(auth()->check() && $review->user_id === auth()->id())
                            <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                                <div class="flex items-start gap-4">
                                    <img src="{{ $review->user->avatarUrl() }}" alt="{{ $review->user->name }}"
                                         class="w-11 h-11 rounded-2xl object-cover shrink-0">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <p class="font-bold text-slate-900">{{ $review->user->name }}</p>
                                            <span class="text-xs text-slate-400">{{ $review->created_at->diffForHumans() }}</span>
                                        </div>
                                        <div class="flex gap-0.5 mt-1 mb-3">
                                            @for($i = 1; $i <= 5; $i++)
                                                <svg class="w-4 h-4 {{ $i <= $review->rating ? 'text-amber-400' : 'text-slate-200' }}" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            @endfor
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
                @endif
            </div>
        </div>
    </main>
    @endsection
