<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Pengguna - AmikomEventHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-white rounded-[2rem] p-8 shadow-2xl">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-indigo-600 rounded-2xl flex items-center justify-center text-white font-bold text-2xl mx-auto mb-4">AH</div>
            <h1 class="text-2xl font-black">Login Pengguna</h1>
            <p class="text-slate-500">Masuk untuk menyimpan keranjang dan melihat tiket Anda.</p>
        </div>

        @if(session('error'))
            <div class="bg-rose-50 text-rose-700 p-4 rounded-xl mb-6 text-center font-semibold">{{ session('error') }}</div>
        @endif
        @if(session('success'))
            <div class="bg-emerald-50 text-emerald-700 p-4 rounded-xl mb-6 text-center font-semibold">{{ session('success') }}</div>
        @endif

        {{-- Soal 1a: Login Instan via Google (memangkas form pendaftaran manual) --}}
        <a href="{{ route('auth.google') }}"
           class="flex w-full items-center justify-center gap-3 rounded-2xl border-2 border-slate-200 px-6 py-4 font-bold text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition">
            <svg class="w-5 h-5" viewBox="0 0 48 48" aria-hidden="true">
                <path fill="#4285F4" d="M45.12 24.5c0-1.56-.14-3.06-.4-4.5H24v8.51h11.84c-.51 2.75-2.06 5.08-4.39 6.64v5.52h7.11c4.16-3.83 6.56-9.47 6.56-16.17z"/>
                <path fill="#34A853" d="M24 46c5.94 0 10.92-1.97 14.56-5.33l-7.11-5.52c-1.97 1.32-4.49 2.1-7.45 2.1-5.73 0-10.58-3.87-12.31-9.07H4.34v5.7C7.96 41.07 15.4 46 24 46z"/>
                <path fill="#FBBC05" d="M11.69 28.18C11.25 26.86 11 25.45 11 24s.25-2.86.69-4.18v-5.7H4.34C2.85 17.09 2 20.45 2 24s.85 6.91 2.34 9.88l7.35-5.7z"/>
                <path fill="#EA4335" d="M24 10.75c3.23 0 6.13 1.11 8.41 3.29l6.31-6.31C34.91 4.18 29.93 2 24 2 15.4 2 7.96 6.93 4.34 14.12l7.35 5.7c1.73-5.2 6.58-9.07 12.31-9.07z"/>
            </svg>
            Continue with Google
        </a>

        <div class="my-6 flex items-center gap-4">
            <div class="h-px flex-1 bg-slate-200"></div>
            <span class="text-xs font-bold uppercase tracking-widest text-slate-400">atau</span>
            <div class="h-px flex-1 bg-slate-200"></div>
        </div>

        <form action="{{ route('user.login.post') }}" method="POST" class="space-y-6">
            @csrf
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-wide">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-2xl border border-slate-200 px-5 py-4 outline-none focus:border-indigo-600 focus:ring-2 focus:ring-indigo-200">
            </div>
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-wide">Password</label>
                <input type="password" name="password" required class="w-full rounded-2xl border border-slate-200 px-5 py-4 outline-none focus:border-indigo-600 focus:ring-2 focus:ring-indigo-200">
            </div>
            <button type="submit" class="w-full rounded-2xl bg-indigo-600 px-6 py-4 text-white font-black hover:bg-indigo-700 transition">Masuk</button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-500">
            <a href="{{ route('home') }}" class="font-semibold text-indigo-600 hover:underline">Kembali ke beranda</a>
        </p>
    </div>
</body>
</html>