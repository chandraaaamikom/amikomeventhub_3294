<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Hitung Total Pendapatan (Status success atau settlement)
        $totalRevenue = Transaction::whereIn('status', ['success', 'settlement'])->sum('total_price');

        // 2. PERBAIKAN: Hitung akumulasi kuantitas tiket yang terjual menggunakan sum() agar akurat
        $ticketsSold = Transaction::whereIn('status', ['success', 'settlement'])->sum('quantity');

        // 3. SINKRONISASI: Mengubah nama variabel dari $totalEvents menjadi $activeEvents agar sesuai dengan view blade
        $activeEvents = Event::count();

        // 4. Hitung Pesanan Pending
        $pendingOrders = Transaction::where('status', 'pending')->count();

        // 5. Ambil 5 Transaksi Terakhir beserta relasi event
        $recentTransactions = Transaction::with('event')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Grafik Soal 2: pertumbuhan user dan sebaran event pada ekosistem.
        $months = collect(range(5, 0))->map(fn ($offset) => now()->startOfMonth()->subMonths($offset));
        $usersByMonth = $months->map(function (Carbon $month) {
            return [
                'label' => $month->translatedFormat('M'),
                'total' => User::whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])->count(),
            ];
        })->all();

        $eventsByCategory = Event::query()
            ->with('category:id,name')
            ->get()
            ->groupBy(fn (Event $event) => $event->category?->name ?? 'Tanpa kategori')
            ->map(fn ($events) => $events->count())
            ->sortDesc()
            ->take(6)
            ->map(fn ($total, $label) => ['label' => $label, 'total' => $total])
            ->values()
            ->all();

        // Kirim semua variabel yang sudah sinkron ke view
        return view('admin.dashboard', compact(
            'totalRevenue', 
            'ticketsSold', 
            'activeEvents', // Sudah disamakan namanya
            'pendingOrders', 
            'recentTransactions',
            'usersByMonth',
            'eventsByCategory'
        ));
    }

    public function transactions()
    {
        $transactions = Transaction::with('event')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('admin.transactions', compact('transactions'));
    }
}
