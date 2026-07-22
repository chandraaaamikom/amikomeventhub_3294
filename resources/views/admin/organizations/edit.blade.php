@extends('layouts.admin')

@section('content')
<div class="max-w-5xl p-8">
    <a href="{{ route('admin.organizations.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-indigo-600 hover:text-indigo-800">
        &larr; Kembali ke organisasi
    </a>

    <div class="mt-6 mb-8">
        <h1 class="text-3xl font-black text-slate-900">Kelola {{ $organization->name }}</h1>
        <p class="mt-2 text-slate-500">Perbarui profil tenant dan kelola anggota timnya.</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <form action="{{ route('admin.organizations.update', $organization) }}" method="POST" enctype="multipart/form-data" class="space-y-6 rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')
            <h2 class="text-lg font-black text-slate-900">Profil organisasi</h2>

            <div>
                <label for="name" class="mb-2 block text-sm font-bold text-slate-700">Nama organisasi</label>
                <input id="name" name="name" type="text" value="{{ old('name', $organization->name) }}" required class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-indigo-600">
                @error('name') <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="description" class="mb-2 block text-sm font-bold text-slate-700">Deskripsi</label>
                <textarea id="description" name="description" rows="4" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-indigo-600">{{ old('description', $organization->description) }}</textarea>
                @error('description') <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="contact_email" class="mb-2 block text-sm font-bold text-slate-700">Email kontak</label>
                    <input id="contact_email" name="contact_email" type="email" value="{{ old('contact_email', $organization->contact_email) }}" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-indigo-600">
                    @error('contact_email') <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="contact_phone" class="mb-2 block text-sm font-bold text-slate-700">Nomor telepon</label>
                    <input id="contact_phone" name="contact_phone" type="tel" value="{{ old('contact_phone', $organization->contact_phone) }}" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-indigo-600">
                    @error('contact_phone') <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="logo" class="mb-2 block text-sm font-bold text-slate-700">Ganti logo</label>
                @if($organization->logoUrl()) <img src="{{ $organization->logoUrl() }}" alt="Logo {{ $organization->name }}" class="mb-3 h-16 w-16 rounded-2xl object-cover"> @endif
                <input id="logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm text-slate-500">
                @error('logo') <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="w-full rounded-xl bg-indigo-600 px-6 py-3 font-bold text-white hover:bg-indigo-700">Simpan Perubahan</button>
        </form>

        <div class="space-y-6">
            <section class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-900">Tambah anggota</h2>
                <form action="{{ route('admin.organizations.members.add', $organization) }}" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <select name="user_id" required class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-indigo-600">
                        <option value="">Pilih pengguna</option>
                        @foreach($candidates as $candidate)
                            <option value="{{ $candidate->id }}">{{ $candidate->name }} — {{ $candidate->email }}</option>
                        @endforeach
                    </select>
                    <select name="role" required class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-indigo-600">
                        <option value="owner">Ketua</option>
                        <option value="staff">Staf</option>
                    </select>
                    <button type="submit" class="w-full rounded-xl bg-slate-900 px-5 py-3 font-bold text-white hover:bg-slate-800">Tambahkan anggota</button>
                </form>
            </section>

            <section class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-900">Anggota saat ini</h2>
                <div class="mt-4 space-y-3">
                    @forelse($members as $member)
                        <div class="flex items-center justify-between gap-3 rounded-2xl bg-slate-50 px-4 py-3">
                            <div class="min-w-0">
                                <p class="truncate font-bold text-slate-800">{{ $member->name }}</p>
                                <p class="text-xs text-slate-500">{{ $member->pivot->role === 'owner' ? 'Ketua' : 'Staf' }} · {{ $member->email }}</p>
                            </div>
                            <form action="{{ route('admin.organizations.members.remove', [$organization, $member]) }}" method="POST" onsubmit="return confirm('Keluarkan anggota ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-xl px-3 py-2 text-sm font-bold text-rose-600 hover:bg-rose-50">Hapus</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Belum ada anggota.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
