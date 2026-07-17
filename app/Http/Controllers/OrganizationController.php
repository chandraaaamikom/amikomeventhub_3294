<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Transaction;
use App\Models\TransactionItem;

class OrganizationController extends Controller
{
    public function show(Organization $organization)
    {
        // Route model binding memakai slug (lihat Organization::getRouteKeyName()).
        abort_if(! $organization->is_active, 404);

        $upcomingEvents = $organization->events()
            ->with('category')
            ->where('date', '>=', now())
            ->orderBy('date')
            ->get();

        $pastEvents = $organization->events()
            ->with('category')
            ->where('date', '<', now())
            ->orderByDesc('date')
            ->take(6)
            ->get();

        $reviews = $organization->reviews()
            ->with(['user:id,name,avatar', 'event:id,title'])
            ->latest()
            ->take(20)
            ->get();

        $ratingBreakdown = collect(range(5, 1))->mapWithKeys(fn ($star) => [
            $star => $organization->reviews()->where('rating', $star)->count(),
        ]);

        $stats = [
            'events'  => $organization->events()->count(),
            'tickets' => (int) TransactionItem::where('organization_id', $organization->id)
                ->whereHas('transaction', fn ($q) => $q->where('status', Transaction::STATUS_SUCCESS))
                ->sum('quantity'),
            'rating'  => $organization->averageRating(),
            'reviews' => $organization->reviews()->count(),
        ];

        return view('organizations.show', compact(
            'organization', 'upcomingEvents', 'pastEvents', 'reviews', 'ratingBreakdown', 'stats'
        ));
    }
}