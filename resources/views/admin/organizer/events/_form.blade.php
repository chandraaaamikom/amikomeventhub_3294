@csrf

<div class="grid gap-6 md:grid-cols-2">
    <div class="md:col-span-2">
        <label class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-wide">Judul Event</label>
        <input type="text" name="title" value="{{ old('title', $event->title ?? '') }}" required
               class="w-full rounded-2xl border-2 border-slate-100 px-5 py-4 outline-none focus:border-indigo-600 font-medium">
    </div>

    <div>
        <label class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-wide">Kategori</label>
        <select name="category_id" required
                class="w-full rounded-2xl border-2 border-slate-100 px-5 py-4 outline-none focus:border-indigo-600 font-medium bg-white">
            <option value="">— Pilih kategori —</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}"
                    {{ (string) old('category_id', $event->category_id ?? '') === (string) $category->id ? 'selected' : '' }}>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-wide">Tanggal & Jam</label>
        <input type="datetime-local" name="date" required
               value="{{ old('date', isset($event) ? $event->date->format('Y-m-d\TH:i') : '') }}"
               class="w-full rounded-2xl border-2 border-slate-100 px-5 py-4 outline-none focus:border-indigo-600 font-medium">
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-wide">Lokasi</label>
        <input type="text" name="location" value="{{ old('location', $event->location ?? '') }}" required
               placeholder="Contoh: Auditorium Unit 6, Kampus AMIKOM"
               class="w-full rounded-2xl border-2 border-slate-100 px-5 py-4 outline-none focus:border-indigo-600 font-medium">
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-wide">Deskripsi</label>
        <textarea name="description" rows="5" required
                  class="w-full rounded-2xl border-2 border-slate-100 px-5 py-4 outline-none focus:border-indigo-600 font-medium">{{ old('description', $event->description ?? '') }}</textarea>
    </div>

    <div>
        <label class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-wide">Harga Tiket (Rp)</label>
        <input type="number" name="price" min="0" step="1000" value="{{ old('price', $event->price ?? 0) }}" required
               class="w-full rounded-2xl border-2 border-slate-100 px-5 py-4 outline-none focus:border-indigo-600 font-medium">
        <p class="mt-2 text-xs text-slate-400">Isi 0 untuk event gratis. Pembeli tetap dikenai biaya layanan platform Rp 5.000.</p>
    </div>

    <div>
        <label class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-wide">Stok Tiket</label>
        <input type="number" name="stock" min="{{ $event->reserved_stock ?? 0 }}" value="{{ old('stock', $event->stock ?? 100) }}" required
               class="w-full rounded-2xl border-2 border-slate-100 px-5 py-4 outline-none focus:border-indigo-600 font-medium">
        @if(isset($event) && $event->reserved_stock > 0)
            <p class="mt-2 text-xs text-amber-600 font-semibold">
                {{ $event->reserved_stock }} tiket sedang dalam proses checkout — stok tidak bisa diturunkan di bawah angka ini.
            </p>
        @endif
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-wide">
            Poster {{ isset($event) ? '(kosongkan bila tidak diganti)' : '' }}
        </label>

        @isset($event)
            @if($event->poster_path)
                <img src="{{ asset('storage/' . $event->poster_path) }}" alt="Poster saat ini"
                     class="mb-4 w-32 h-40 rounded-2xl object-cover shadow-sm bg-slate-100">
            @endif
        @endisset

        <input type="file" name="poster" accept="image/jpeg,image/png,image/webp" {{ isset($event) ? '' : 'required' }}
               class="w-full rounded-2xl border-2 border-dashed border-slate-200 px-5 py-4 outline-none focus:border-indigo-600 font-medium file:mr-4 file:rounded-xl file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:font-bold file:text-indigo-600">
        <p class="mt-2 text-xs text-slate-400">JPG, PNG, atau WEBP. Maksimal 2 MB. Rasio potret (3:4) paling pas.</p>
    </div>
</div>

<div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-end">
    <a href="{{ route('organizer.events.index') }}"
       class="rounded-2xl border border-slate-200 px-8 py-4 font-bold text-slate-600 hover:bg-slate-50 transition text-center">
        Batal
    </a>
    <button type="submit"
            class="rounded-2xl bg-indigo-600 px-8 py-4 text-white font-black hover:bg-indigo-700 transition">
        {{ isset($event) ? 'Simpan Perubahan' : 'Buat Event' }}
    </button>
</div>