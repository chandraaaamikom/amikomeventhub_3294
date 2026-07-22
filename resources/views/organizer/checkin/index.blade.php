@extends('layouts.organizer')

@section('content')
<div class="max-w-4xl mx-auto font(['Plus Jakarta Sans'])">
    <div class="flex flex-col md:flex-row items-center justify-between gap-4 mb-8">
        <div>
            <span class="text-xs font-black text-indigo-600 uppercase tracking-widest block mb-1">Validasi Tiket Masuk</span>
            <h1 class="text-2xl font-black text-slate-900">QR Check-in Scanner</h1>
        </div>
        
        <div class="flex items-center gap-3 bg-white p-3 rounded-2xl border border-slate-200 shadow-sm">
            <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                </svg>
            </div>
            <div>
                <p class="text-xs text-slate-500 uppercase tracking-wider font-bold">Mode Pemindaian</p>
                <p class="text-sm font-black text-slate-900">Kamera HP Aktif</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Kolom Kiri: Kamera Scanner -->
        <div class="lg:col-span-2 bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-100 flex flex-col items-center">
            <div id="reader" class="w-full overflow-hidden rounded-2xl border border-slate-200 bg-slate-950" style="max-width: 500px;"></div>
            <p class="text-xs text-slate-500 mt-4 text-center">Posisikan QR Code di dalam kotak kamera untuk memindai otomatis.</p>
        </div>

        <!-- Kolom Kanan: Hasil Pindai & Log Status -->
        <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-100 flex flex-col justify-between">
            <div>
                <h3 class="text-md font-black text-slate-900 uppercase tracking-widest border-b border-slate-100 pb-3 mb-4">Status Pemindaian</h3>
                
                <div id="status-box" class="bg-slate-50 border border-slate-200 rounded-2xl p-4 text-center text-slate-600 transition-all">
                    Siap memindai tiket...
                </div>

                <div id="result-detail" class="mt-6 space-y-3 hidden">
                    <div class="bg-indigo-50 rounded-2xl p-4 border border-indigo-100">
                        <p class="text-xs font-bold text-indigo-500 uppercase tracking-wider">Nama Peserta</p>
                        <p id="attendee-name" class="text-lg font-black text-indigo-900">-</p>
                    </div>
                </div>
            </div>

            <button onclick="resetScanner()" class="mt-6 w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm uppercase tracking-widest py-4 rounded-2xl transition-all">
                Reset Ulang Scanner
            </button>
        </div>
    </div>
</div>

<!-- Ambil library HTML5 QR Code via CDN secara aman -->
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
    let html5QrcodeScanner;
    let isProcessing = false;

    function onScanSuccess(decodedText, decodedResult) {
        if (isProcessing) return;
        isProcessing = true;

        const statusBox = document.getElementById('status-box');
        const resultDetail = document.getElementById('result-detail');
        const attendeeName = document.getElementById('attendee-name');

        statusBox.className = "bg-indigo-50 border border-indigo-200 rounded-2xl p-4 text-center text-indigo-700 font-bold animate-pulse";
        statusBox.innerText = "Memverifikasi kode...";

        // Mengirimkan kode via AJAX POST ke backend
        fetch("{{ route('organizer.checkin.process') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({ code: decodedText })
        })
        .then(response => response.json().then(data => ({ status: response.status, body: data })))
        .then(res => {
            if (res.status === 200 && res.body.success) {
                // Check-in Sukses
                statusBox.className = "bg-emerald-50 border border-emerald-200 rounded-2xl p-4 text-center text-emerald-700 font-black";
                statusBox.innerText = res.body.message;
                
                attendeeName.innerText = res.body.attendee;
                resultDetail.classList.remove('hidden');
                
                // Alert suara sukses bawaan sistem browser jika didukung
                if ('speechSynthesis' in window) {
                    let msg = new SpeechSynthesisUtterance('Sukses');
                    window.speechSynthesis.speak(msg);
                }
            } else {
                // Gagal / Double Entry / Lintas Tenant
                statusBox.className = "bg-rose-50 border border-rose-200 rounded-2xl p-4 text-center text-rose-700 font-black";
                statusBox.innerText = res.body.message || "Gagal memproses tiket.";
                resultDetail.classList.add('hidden');
                
                if ('speechSynthesis' in window) {
                    let msg = new SpeechSynthesisUtterance('Gagal');
                    window.speechSynthesis.speak(msg);
                }
            }
            
            // Beri jeda 3 detik sebelum mengizinkan pemindaian kode berikutnya
            setTimeout(() => { isProcessing = false; }, 3000);
        })
        .catch(error => {
            console.error("Error:", error);
            statusBox.className = "bg-rose-50 border border-rose-200 rounded-2xl p-4 text-center text-rose-700 font-black";
            statusBox.innerText = "Koneksi terputus atau terjadi kesalahan server.";
            setTimeout(() => { isProcessing = false; }, 3000);
        });
    }

    function onScanFailure(error) {
        // Abaikan kegagalan frame pembacaan berkala untuk menjaga performa
    }

    function startScanner() {
        html5QrcodeScanner = new Html5QrcodeScanner("reader", { 
            fps: 10, 
            qrbox: { width: 250, height: 250 },
            rememberLastUsedCamera: true
        });
        html5QrcodeScanner.render(onScanSuccess, onScanFailure);
    }

    function resetScanner() {
        document.getElementById('status-box').className = "bg-slate-50 border border-slate-200 rounded-2xl p-4 text-center text-slate-600";
        document.getElementById('status-box').innerText = "Siap memindai tiket...";
        document.getElementById('result-detail').classList.add('hidden');
        isProcessing = false;
    }

    document.addEventListener("DOMContentLoaded", function() {
        startScanner();
    });
</script>
@endsection