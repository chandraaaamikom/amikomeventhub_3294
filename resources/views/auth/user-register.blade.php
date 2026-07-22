<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pengguna - AmikomEventHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-100 p-6">
    <div class="w-full max-w-md rounded-[2rem] bg-white p-8 shadow-2xl">
        <div class="mb-8 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-600 text-2xl font-bold text-white">AH</div>
            <h1 class="text-2xl font-black">Buat Akun</h1>
            <p class="text-slate-500">Daftar untuk menyimpan keranjang dan melihat tiket Anda.</p>
        </div>

        <form action="{{ route('user.register.post') }}" method="POST" class="space-y-5">
            @csrf
            <div>
                <label for="name" class="mb-2 block text-sm font-bold text-slate-700">Nama lengkap</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name" class="w-full rounded-2xl border border-slate-200 px-5 py-4 outline-none focus:border-indigo-600 focus:ring-2 focus:ring-indigo-200">
                @error('name') <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="email" class="mb-2 block text-sm font-bold text-slate-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="w-full rounded-2xl border border-slate-200 px-5 py-4 outline-none focus:border-indigo-600 focus:ring-2 focus:ring-indigo-200">
                @error('email') <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password" class="mb-2 block text-sm font-bold text-slate-700">Password</label>
                <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password" class="w-full rounded-2xl border border-slate-200 px-5 py-4 outline-none focus:border-indigo-600 focus:ring-2 focus:ring-indigo-200">
                <p class="mt-2 text-xs text-slate-400">Minimal 8 karakter.</p>
                @error('password') <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password_confirmation" class="mb-2 block text-sm font-bold text-slate-700">Konfirmasi password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password" class="w-full rounded-2xl border border-slate-200 px-5 py-4 outline-none focus:border-indigo-600 focus:ring-2 focus:ring-indigo-200">
            </div>
            <button type="submit" class="w-full rounded-2xl bg-indigo-600 px-6 py-4 font-black text-white transition hover:bg-indigo-700">Daftar</button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-500">
            Sudah punya akun? <a href="{{ route('user.login') }}" class="font-semibold text-indigo-600 hover:underline">Masuk</a>
        </p>
        <p class="mt-3 text-center text-sm text-slate-500">
            <a href="{{ route('home') }}" class="font-semibold text-indigo-600 hover:underline">Kembali ke beranda</a>
        </p>
    </div>
</body>
</html>
