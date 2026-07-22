<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class OrganizationController extends Controller
{
    public function index(Request $request)
    {
        $organizations = Organization::query()
            ->withCount('events')
            ->when($request->query('search'), fn ($q, $s) => $q->where('name', 'LIKE', "%{$s}%"))
            ->orderBy('name')
            ->get()
            ->map(function ($org) {
                $org->revenue = (int) TransactionItem::where('organization_id', $org->id)
                    ->whereHas('transaction', fn ($q) => $q->where('status', Transaction::STATUS_SUCCESS))
                    ->sum('sub_total');
                $org->owner_names = $org->members()->wherePivot('role', 'owner')->pluck('name')->join(', ');

                return $org;
            });

        return view('admin.organizations.index', compact('organizations'));
    }

    public function create()
    {
        // Kandidat pemilik: user yang belum jadi superadmin.
        $candidates = User::where('role', '!=', User::ROLE_SUPERADMIN)->orderBy('name')->get();

        return view('admin.organizations.create', compact('candidates'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'logo'          => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'owner_id'      => ['nullable', Rule::exists('users', 'id')],
        ]);

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('logos', 'public');
        }

        $organization = Organization::create([
            'name'          => $data['name'],
            'description'   => $data['description'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'logo_path'     => $data['logo_path'] ?? null,
            'is_active'     => true,
        ]);

        // Angkat pemilik + naikkan perannya jadi organizer.
        if (! empty($data['owner_id'])) {
            $this->assignOwner($organization, (int) $data['owner_id']);
        }

        return redirect()->route('admin.organizations.index')
            ->with('success', "Organisasi \"{$organization->name}\" berhasil dibuat.");
    }

    public function edit(Organization $organization)
    {
        $candidates = User::where('role', '!=', User::ROLE_SUPERADMIN)->orderBy('name')->get();
        $members = $organization->members()->get();

        return view('admin.organizations.edit', compact('organization', 'candidates', 'members'));
    }

    public function update(Request $request, Organization $organization)
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'logo'          => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('logo')) {
            if ($organization->logo_path) {
                Storage::disk('public')->delete($organization->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('logos', 'public');
        }

        unset($data['logo']);
        $organization->update($data);

        return redirect()->route('admin.organizations.index')
            ->with('success', "Organisasi \"{$organization->name}\" berhasil diperbarui.");
    }

    /**
     * Aktif/nonaktif — inilah "gigi" pengawas. Tenant nonaktif: panitianya
     * tidak bisa masuk panel, event & profilnya hilang dari publik.
     */
    public function toggle(Organization $organization)
    {
        $organization->update(['is_active' => ! $organization->is_active]);

        $state = $organization->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Organisasi \"{$organization->name}\" berhasil {$state}.");
    }

    public function assignMember(Request $request, Organization $organization)
    {
        $data = $request->validate([
            'user_id' => ['required', Rule::exists('users', 'id')],
            'role'    => ['required', Rule::in(['owner', 'staff'])],
        ]);

        $this->assignOwner($organization, (int) $data['user_id'], $data['role']);

        return back()->with('success', 'Anggota berhasil ditambahkan ke organisasi.');
    }

    public function removeMember(Organization $organization, User $user)
    {
        $organization->members()->detach($user->id);

        // Bila user tak lagi memegang organisasi apa pun, turunkan perannya.
        if ($user->organizations()->count() === 0 && $user->role === User::ROLE_ORGANIZER) {
            $user->update(['role' => User::ROLE_USER]);
        }

        return back()->with('success', 'Anggota berhasil dikeluarkan dari organisasi.');
    }

    protected function assignOwner(Organization $organization, int $userId, string $role = 'owner'): void
    {
        $organization->members()->syncWithoutDetaching([$userId => ['role' => $role]]);

        $user = User::find($userId);
        // Superadmin tidak diturunkan; selain itu, jadikan organizer.
        if ($user && ! $user->isSuperadmin()) {
            $user->update(['role' => User::ROLE_ORGANIZER]);
        }
    }
}