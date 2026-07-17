<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $organization = $request->attributes->get('organization');

        $status = $request->query('status');
        $search = $request->query('search');

        $transactions = Transaction::query()
            // Pagar tenant: hanya transaksi yang punya item milik organisasi ini.
            ->whereHas('transactionItems', fn ($q) => $q->where('organization_id', $organization->id))
            // Relasi ikut difilter, supaya subtotal yang tampil hanya porsi tenant ini.
            ->with(['transactionItems' => fn ($q) => $q->where('organization_id', $organization->id)])
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->when($search, function ($q, $s) {
                $q->where(function ($sub) use ($s) {
                    $sub->where('order_id', 'LIKE', "%{$s}%")
                        ->orWhere('customer_name', 'LIKE', "%{$s}%")
                        ->orWhere('customer_email', 'LIKE', "%{$s}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // Ringkasan dihitung dari item, bukan total_price, agar bagian tenant
        // lain pada keranjang campuran tidak ikut terhitung.
        $baseItems = TransactionItem::where('organization_id', $organization->id);

        $summary = [
            'revenue' => (int) (clone $baseItems)
                ->whereHas('transaction', fn ($q) => $q->where('status', Transaction::STATUS_SUCCESS))
                ->sum('sub_total'),
            'tickets' => (int) (clone $baseItems)
                ->whereHas('transaction', fn ($q) => $q->where('status', Transaction::STATUS_SUCCESS))
                ->sum('quantity'),
            'pending' => (int) (clone $baseItems)
                ->whereHas('transaction', fn ($q) => $q->where('status', Transaction::STATUS_PENDING))
                ->distinct('transaction_id')
                ->count('transaction_id'),
        ];

        return view('organizer.transactions.index', compact(
            'organization', 'transactions', 'summary', 'status', 'search'
        ));
    }
}