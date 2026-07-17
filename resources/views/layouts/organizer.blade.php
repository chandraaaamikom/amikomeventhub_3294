<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Panel Panitia') - AmikomEventHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
    @yield('extra-styles')
</head>

<body class="bg-slate-50 text-slate-900 flex min-h-screen">

    <aside class="w-64 bg-indigo-900 text-indigo-100 flex flex-col p-6 space-y-8 sticky top-0 h-screen">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-indigo-900 font-bold text-xl">
                    AH</div>
                <span class="text-xl font-bold text-white tracking-tight">AmikomEventHub</span>
            </div>

            {{-- Identitas tenant: panitia harus selalu sadar sedang bekerja atas nama siapa --}}
            <div class="mt-6 rounded-2xl bg-indigo-800/60 border border-indigo-700 p-4">
                <p class="text-[10px] font-bold uppercase tracking-widest text-indigo-400 mb-1">Organisasi</p>
                <p class="font-bold text-white leading-snug">{{ $organization->name }}</p>
                @if(auth()->user()->isSuperadmin())
                    <span class="mt-2 inline-block px-2 py-0.5 rounded-lg bg-amber-400 text-amber-900 text-[10px] font-black uppercase tracking-wider">
                        Mode Pengawas
                    </span>
                @endif
            </div>
        </div>

        <nav class="flex-1 space-y-2">
            <p class="text-[10px] font-bold uppercase tracking-widest text-indigo-400 mb-4 px-2">Menu Panitia</p>

            <a href="{{ route('organizer.dashboard') }}"
                class="flex items-center gap-3 px-4 py-3 {{ request()->routeIs('organizer.dashboard') ? 'bg-indigo-800 text-white' : 'text-indigo-300 hover:bg-indigo-800 hover:text-white' }} rounded-xl font-bold transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z">
                    </path>
                </svg>
                Dashboard
            </a>

            <a href="{{ route('organizer.events.index') }}"
                class="flex items-center gap-3 px-4 py-3 {{ request()->routeIs('organizer.events.*') ? 'bg-indigo-800 text-white' : 'text-indigo-300 hover:bg-indigo-800 hover:text-white' }} rounded-xl font-bold transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                    </path>
                </svg>
                Event Saya
            </a>

            <a href="{{ route('organizer.transactions.index') }}"
                class="flex items-center gap-3 px-4 py-3 {{ request()->routeIs('organizer.transactions.*') ? 'bg-indigo-800 text-white' : 'text-indigo-300 hover:bg-indigo-800 hover:text-white' }} rounded-xl font-bold transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                    </path>
                </svg>
                Penjualan
            </a>

            <a href="{{ route('organizations.show', $organization->slug) }}" target="_blank"
                class="flex items-center gap-3 px-4 py-3 text-indigo-300 hover:bg-indigo-800 hover:text-white rounded-xl font-bold transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14">
                    </path>
                </svg>
                Profil Publik
            </a>
        </nav>

        <div class="pt-6 border-t border-indigo-800 space-y-3">
            <div class="flex items-center gap-3 px-2">
                <img src="{{ auth()->user()->avatarUrl() }}" alt="{{ auth()->user()->name }}"
                    class="w-9 h-9 rounded-xl object-cover">
                <div class="min-w-0">
                    <p class="text-sm font-bold text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-[11px] text-indigo-400 truncate">{{ auth()->user()->email }}</p>
                </div>
            </div>
            <form action="{{ route('user.logout') }}" method="POST">
                @csrf
                <button type="submit"
                    class="w-full flex items-center gap-3 px-4 py-3 text-indigo-300 hover:text-white transition font-medium text-left outline-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                        </path>
                    </svg>
                    Keluar
                </button>
            </form>
        </div>
    </aside>

    <main class="flex-1 p-10 overflow-y-auto">
        @if(session('success'))
            <div class="mb-6 rounded-3xl border border-emerald-200 bg-emerald-50 px-6 py-4 text-emerald-700 font-semibold">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-6 rounded-3xl border border-rose-200 bg-rose-50 px-6 py-4 text-rose-700 font-semibold">
                {{ session('error') }}
            </div>
        @endif
        @if($errors->any())
            <div class="mb-6 rounded-3xl border border-rose-200 bg-rose-50 px-6 py-4 text-rose-700">
                <p class="font-bold mb-2">Periksa kembali isian Anda:</p>
                <ul class="list-disc list-inside space-y-1 text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    @yield('extra-scripts')
</body>

</html>