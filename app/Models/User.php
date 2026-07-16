<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use App\Models\CartItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SUPERADMIN = 'superadmin';
    public const ROLE_ORGANIZER  = 'organizer';
    public const ROLE_USER       = 'user';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'google_id',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class)->withPivot('role')->withTimestamps();
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // --- Helper hak akses ---------------------------------------------------

    public function isSuperadmin(): bool
    {
        // 'admin' dipertahankan agar akun lama sebelum migrasi tetap bisa masuk.
        return in_array($this->role, [self::ROLE_SUPERADMIN, 'admin'], true);
    }

    public function isOrganizer(): bool
    {
        return $this->role === self::ROLE_ORGANIZER;
    }

    /**
     * Organisasi yang sedang dikelola. Superadmin sengaja mengembalikan null:
     * ia pengawas ekosistem, bukan pemilik salah satu tenant.
     */
    public function currentOrganization(): ?Organization
    {
        return $this->organizations()->first();
    }

    public function belongsToOrganization(int $organizationId): bool
    {
        return $this->organizations()->whereKey($organizationId)->exists();
    }

    public function ownsOrganization(int $organizationId): bool
    {
        return $this->organizations()
            ->whereKey($organizationId)
            ->wherePivot('role', 'owner')
            ->exists();
    }

    public function avatarUrl(): string
    {
        return $this->avatar
            ?: 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=6366f1&color=fff';
    }
}