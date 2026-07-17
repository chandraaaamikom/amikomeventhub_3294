{{-- Soal 1b: form rating bintang. Dipakai di halaman detail event. --}}
@auth
    @php
        $myReview = \App\Models\Review::where('user_id', auth()->id())
            ->where('event_id', $event->id)
            ->first();

        $hasPurchase = \App\Models\TransactionItem::where('event_id', $event->id)
            ->whereHas('transaction', fn ($q) => $q
                ->where('user_id', auth()->id())
                ->where('status', 'success'))
            ->exists();
    @endphp

    @if($myReview)
        {{-- Sudah mengulas: tampilkan ulasan sendiri + opsi ubah --}}
        <div class="rounded-3xl border-2 border-indigo-100 bg-indigo-50/50 p-8">
            <div class="flex items-start justify-between gap-4 mb-4">
                <div>
                    <h4 class="font-black text-lg text-slate-900">Ulasan Anda</h4>
                    <p class="text-sm text-slate-500">Ditulis {{ $myReview->created_at->diffForHumans() }}</p>
                </div>
                <button type="button" onclick="document.getElementById('edit-review-box').classList.toggle('hidden')"
                        class="text-sm font-bold text-indigo-600 hover:underline">Ubah</button>
            </div>

            <div class="flex gap-1 mb-3">
                @for($i = 1; $i <= 5; $i++)
                    <svg class="w-6 h-6 {{ $i <= $myReview->rating ? 'text-amber-400' : 'text-slate-200' }}" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                @endfor
            </div>

            @if($myReview->comment)
                <p class="text-slate-600 leading-relaxed">{{ $myReview->comment }}</p>
            @endif

            <div id="edit-review-box" class="hidden mt-6 pt-6 border-t border-indigo-100">
                <form action="{{ route('reviews.update', $myReview->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @include('partials._star-input', ['current' => $myReview->rating, 'uid' => 'edit'])
                    <textarea name="comment" rows="4" maxlength="1000" placeholder="Ceritakan pengalaman Anda..."
                              class="mt-4 w-full rounded-2xl border-2 border-slate-100 px-5 py-4 outline-none focus:border-indigo-600">{{ $myReview->comment }}</textarea>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <button type="submit" class="rounded-2xl bg-indigo-600 px-6 py-3 text-white font-black hover:bg-indigo-700 transition">Simpan Perubahan</button>
                    </div>
                </form>
                <form action="{{ route('reviews.destroy', $myReview->id) }}" method="POST" class="mt-3"
                      onsubmit="return confirm('Hapus ulasan Anda?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm font-bold text-rose-600 hover:underline">Hapus ulasan</button>
                </form>
            </div>
        </div>

    @elseif(! $hasPurchase)
        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-8 text-center">
            <p class="text-slate-500 font-medium">Hanya pembeli tiket yang dapat memberikan ulasan untuk event ini.</p>
        </div>

    @elseif(! $event->reviewsAreOpen())
        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-8 text-center">
            <p class="font-bold text-slate-700">Ulasan belum dibuka</p>
            <p class="text-sm text-slate-500 mt-2">
                Anda dapat memberikan ulasan mulai
                <span class="font-semibold text-slate-700">{{ $event->reviewsOpenAt()->translatedFormat('d F Y, H:i') }}</span>
                — satu hari setelah acara selesai.
            </p>
        </div>

    @else
        {{-- Berhak mengulas --}}
        <div class="rounded-3xl border-2 border-indigo-100 bg-white p-8 shadow-sm">
            <h4 class="font-black text-lg text-slate-900 mb-1">Bagikan Pengalaman Anda</h4>
            <p class="text-sm text-slate-500 mb-6">Ulasan Anda membantu peserta lain dan tampil di profil penyelenggara.</p>

            <form action="{{ route('reviews.store', $event->id) }}" method="POST">
                @csrf
                @include('partials._star-input', ['current' => old('rating', 0), 'uid' => 'new'])
                <textarea name="comment" rows="4" maxlength="1000" placeholder="Bagaimana acaranya? Apa yang paling berkesan?"
                          class="mt-4 w-full rounded-2xl border-2 border-slate-100 px-5 py-4 outline-none focus:border-indigo-600">{{ old('comment') }}</textarea>
                <button type="submit" class="mt-4 rounded-2xl bg-indigo-600 px-8 py-4 text-white font-black hover:bg-indigo-700 transition">
                    Kirim Ulasan
                </button>
            </form>
        </div>
    @endif
@else
    <div class="rounded-3xl border border-slate-200 bg-slate-50 p-8 text-center">
        <p class="text-slate-600 font-medium mb-4">Masuk untuk memberikan ulasan.</p>
        <a href="{{ route('user.login') }}" class="inline-block rounded-2xl bg-indigo-600 px-6 py-3 text-white font-bold hover:bg-indigo-700 transition">Login</a>
    </div>
@endauth