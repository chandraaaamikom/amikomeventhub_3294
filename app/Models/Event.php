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
        'stock',
        'reserved_stock',
        'poster_path',
    ];

    protected $casts = [
        'date' => 'datetime',
        'price' => 'integer',
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