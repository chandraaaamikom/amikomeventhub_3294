@extends('layouts.admin')

@section('content')
<div class="max-w-3xl p-8">
    <a href="{{ route('admin.organizations.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-indigo-600 hover:text-indigo-800">
        &larr; Kembali ke organisasi
    </a>

    <div class="mt-6 mb-8">
        <h1 class="text-3xl font-black text-slate-900">Tambah Organisasi</h1>
        <p class="mt-2 text-slate-500">Buat tenant baru dan, bila diperlukan, tetapkan ketuanya.</p>
    </div>

    <form action="{{ route('admin.organizations.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6 rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
        @csrf

        <div>
            <label for="name" class="mb-2 block text-sm font-bold text-slate-700">Nama organisasi</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                   class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-indigo-600">
            @error('name') <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="description" class="mb-2 block text-sm font-bold text-slate-700">Deskripsi</label>
            <textarea id="description" name="description" rows="4" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-indigo-600">{{ old('description') }}</textarea>
            @error('description') <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <label for="contact_email" class="mb-2 block text-sm font-bold text-slate-700">Email kontak</label>
                <input id="contact_email" name="contact_email" type="email" value="{{ old('contact_email') }}" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-indigo-600">
                @error('contact_email') <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="contact_phone" class="mb-2 block text-sm font-bold text-slate-700">Nomor telepon</label>
                <input id="contact_phone" name="contact_phone" type="tel" value="{{ old('contact_phone') }}" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-indigo-600">
                @error('contact_phone') <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="logo" class="mb-2 block text-sm font-bold text-slate-700">Logo</label>
            <input id="logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm text-slate-500">
            <p class="mt-1 text-xs text-slate-400">PNG, JPG, atau WebP; maksimal 2 MB.</p>
            @error('logo') <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="owner_id" class="mb-2 block text-sm font-bold text-slate-700">Ketua organisasi</label>
            <select id="owner_id" name="owner_id" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-indigo-600">
                <option value="">Belum ditetapkan</option>
                @foreach($candidates as $candidate)
                    <option value="{{ $candidate->id }}" @selected((string) old('owner_id') === (string) $candidate->id)>{{ $candidate->name }} — {{ $candidate->email }}</option>
                @endforeach
            </select>
            @error('owner_id') <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('admin.organizations.index') }}" class="rounded-xl px-5 py-3 font-bold text-slate-600 hover:bg-slate-100">Batal</a>
            <button type="submit" class="rounded-xl bg-indigo-600 px-6 py-3 font-bold text-white hover:bg-indigo-700">Simpan Organisasi</button>
        </div>
    </form>
</div>
@endsection
