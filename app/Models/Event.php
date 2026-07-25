<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'category_id',
        'title',
        'description',
        'date',
        'location',
        'price',
        'early_bird_price',
        'early_bird_ends_at',
        'presale_price',
        'presale_ends_at',
        'stock',
        'reserved_stock',
        'poster_path',
    ];

    protected $casts = [
        'date' => 'datetime',
        'price' => 'integer',
        'early_bird_price' => 'integer',
        'early_bird_ends_at' => 'datetime',
        'presale_price' => 'integer',
        'presale_ends_at' => 'datetime',
        'stock' => 'integer',
        'reserved_stock' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function transactionItems()
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Kuota yang benar-benar bisa dijual: stok fisik dikurangi yang sedang
     * dikunci oleh checkout yang belum lunas.
     */
    public function getAvailableStockAttribute(): int
    {
        return max(0, $this->stock - $this->reserved_stock);
    }

    public function isSoldOut(): bool
    {
        return $this->available_stock <= 0;
    }

    public function isFree(): bool
    {
        return $this->price <= 0;
    }

    /** Harga yang berlaku saat ini: Early Bird -> Presale -> Regular. */
    public function currentPrice(): int
    {
        if ($this->isFree()) return 0;
        if ($this->early_bird_price !== null && $this->early_bird_ends_at && now()->lessThanOrEqualTo($this->early_bird_ends_at)) return $this->early_bird_price;
        if ($this->presale_price !== null && $this->presale_ends_at && now()->lessThanOrEqualTo($this->presale_ends_at)) return $this->presale_price;
        return $this->price;
    }

    public function currentPriceLabel(): string
    {
        if ($this->isFree()) return 'Gratis';
        if ($this->early_bird_price !== null && $this->early_bird_ends_at && now()->lessThanOrEqualTo($this->early_bird_ends_at)) return 'Early Bird';
        if ($this->presale_price !== null && $this->presale_ends_at && now()->lessThanOrEqualTo($this->presale_ends_at)) return 'Presale';
        return 'Regular';
    }

    /**
     * Review baru boleh masuk mulai H+1 setelah acara selesai (syarat soal UAS).
     */
    public function reviewsOpenAt(): \Illuminate\Support\Carbon
    {
        return $this->date->copy()->addDay();
    }

    public function reviewsAreOpen(): bool
    {
        return now()->greaterThanOrEqualTo($this->reviewsOpenAt());
    }

    public function averageRating(): float
    {
        return round((float) $this->reviews()->avg('rating'), 2);
    }
}
