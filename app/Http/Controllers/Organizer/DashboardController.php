<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        /** @var Organization $organization */
        $organization = $request->attributes->get('organization');

        // --- Ringkasan ------------------------------------------------------
        $paidItems = TransactionItem::where('organization_id', $organization->id)
            ->whereHas('transaction', fn ($q) => $q->where('status', Transaction::STATUS_SUCCESS));

        $totalRevenue = (int) (clone $paidItems)->sum('sub_total');
        $ticketsSold  = (int) (clone $paidItems)->sum('quantity');

        $activeEvents = $organization->events()->where('date', '>=', now())->count();
        $totalEvents  = $organization->events()->count();

        $pendingOrders = Transaction::where('status', Transaction::STATUS_PENDING)
            ->whereHas('transactionItems', fn ($q) => $q->where('organization_id', $organization->id))
            ->count();

        // --- Grafik pendapatan 6 bulan terakhir -----------------------------
        $revenueByMonth = $this->revenueLastSixMonths($organization);

        // --- Performa per event ---------------------------------------------
        $eventPerformance = $organization->events()
            ->withCount(['reviews'])
            ->withAvg('reviews', 'rating')
            ->orderByDesc('date')
            ->take(8)
            ->get()
            ->map(function ($event) use ($organization) {
                $sold = (int) TransactionItem::where('organization_id', $organization->id)
                    ->where('event_id', $event->id)
                    ->whereHas('transaction', fn ($q) => $q->where('status', Transaction::STATUS_SUCCESS))
                    ->sum('quantity');

                $revenue = (int) TransactionItem::where('organization_id', $organization->id)
                    ->where('event_id', $event->id)
                    ->whereHas('transaction', fn ($q) => $q->where('status', Transaction::STATUS_SUCCESS))
                    ->sum('sub_total');

                return [
                    'event'       => $event,
                    'sold'        => $sold,
                    'revenue'     => $revenue,
                    'capacity'    => $event->stock + $sold,
                    'fill_rate'   => ($event->stock + $sold) > 0
                        ? round($sold / ($event->stock + $sold) * 100)
                        : 0,
                ];
            });

        // --- Transaksi terakhir milik tenant ini ----------------------------
        $recentTransactions = Transaction::whereHas(
                'transactionItems',
                fn ($q) => $q->where('organization_id', $organization->id)
            )
            ->with(['transactionItems' => fn ($q) => $q->where('organization_id', $organization->id)])
            ->latest()
            ->take(5)
            ->get();

        // --- Rating ---------------------------------------------------------
        $averageRating = $organization->averageRating();
        $reviewCount   = $organization->reviews()->count();

        return view('organizer.dashboard', compact(
            'organization',
            'totalRevenue',
            'ticketsSold',
            'activeEvents',
            'totalEvents',
            'pendingOrders',
            'revenueByMonth',
            'eventPerformance',
            'recentTransactions',
            'averageRating',
            'reviewCount'
        ));
    }

    /**
     * Pendapatan 6 bulan terakhir, dikelompokkan berdasarkan paid_at.
     * Bulan tanpa transaksi tetap muncul dengan nilai 0 agar grafiknya utuh.
     */
    protected function revenueLastSixMonths(Organization $organization): array
    {
        $start = now()->copy()->subMonths(5)->startOfMonth();

        $rows = TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->where('transaction_items.organization_id', $organization->id)
            ->where('transactions.status', Transaction::STATUS_SUCCESS)
            ->whereNotNull('transactions.paid_at')
            ->where('transactions.paid_at', '>=', $start)
            ->selectRaw("DATE_FORMAT(transactions.paid_at, '%Y-%m') as bulan")
            ->selectRaw('SUM(transaction_items.sub_total) as total')
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $result = [];

        for ($i = 0; $i < 6; $i++) {
            $month = $start->copy()->addMonths($i);
            $key   = $month->format('Y-m');

            $result[] = [
                'label' => $month->translatedFormat('M Y'),
                'total' => (int) ($rows[$key] ?? 0),
            ];
        }

        return $result;
    }
}