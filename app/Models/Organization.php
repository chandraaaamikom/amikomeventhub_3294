<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo_path',
        'contact_email',
        'contact_phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $organization) {
            if (blank($organization->slug)) {
                $organization->slug = static::uniqueSlug($organization->name);
            }
        });
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 2;

        while (static::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function members()
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    public function owners()
    {
        return $this->members()->wherePivot('role', 'owner');
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function transactionItems()
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Pendapatan bersih organisasi = jumlah sub_total item yang transaksinya lunas.
     * Biaya layanan Rp 5.000 milik platform, jadi tidak dihitung di sini.
     */
    public function revenue(): int
    {
        return (int) $this->transactionItems()
            ->whereHas('transaction', fn ($q) => $q->where('status', 'success'))
            ->sum('sub_total');
    }

    public function averageRating(): float
    {
        return round((float) $this->reviews()->avg('rating'), 2);
    }

    public function logoUrl(): ?string
    {
        if (blank($this->logo_path)) {
            return null;
        }

        return Str::startsWith($this->logo_path, ['http://', 'https://'])
            ? $this->logo_path
            : asset('storage/' . $this->logo_path);
    }
}