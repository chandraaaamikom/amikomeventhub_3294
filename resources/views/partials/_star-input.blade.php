{{-- Input bintang: radio button asli, tetap jalan tanpa JavaScript. --}}
<div class="flex items-center gap-1" id="stars-{{ $uid }}">
    @for($i = 1; $i <= 5; $i++)
        <label class="cursor-pointer">
            <input type="radio" name="rating" value="{{ $i }}" class="sr-only peer"
                   {{ (int) $current === $i ? 'checked' : '' }}
                   onchange="paintStars('{{ $uid }}', {{ $i }})">
            <svg class="w-9 h-9 transition-transform hover:scale-110 {{ $i <= (int) $current ? 'text-amber-400' : 'text-slate-200' }}"
                 data-star="{{ $i }}" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
            </svg>
        </label>
    @endfor
    <span class="ml-3 text-sm font-bold text-slate-400" id="star-label-{{ $uid }}"></span>
</div>

<script>
    window.paintStars = window.paintStars || function (uid, value) {
        const labels = ['', 'Buruk', 'Kurang', 'Cukup', 'Bagus', 'Sangat Bagus'];
        document.querySelectorAll(`#stars-${uid} svg[data-star]`).forEach(svg => {
            const on = parseInt(svg.dataset.star, 10) <= value;
            svg.classList.toggle('text-amber-400', on);
            svg.classList.toggle('text-slate-200', !on);
        });
        const label = document.getElementById(`star-label-${uid}`);
        if (label) label.textContent = labels[value] ?? '';
    };
</script>