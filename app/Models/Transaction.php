<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED  = 'failed';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'user_id',
        'organization_id',
        'event_id',
        'order_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'quantity',
        'total_price',
        'status',
        'snap_token',
        'items',
        'expires_at',
        'paid_at',
        'stock_applied',
    ];

    protected $casts = [
        // Kolom JSON lama. Dipertahankan supaya view lama tidak pecah,
        // tapi sumber kebenaran yang baru adalah relasi transactionItems().
        'items' => 'array',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'stock_applied' => 'boolean',
        'total_price' => 'integer',
        'quantity' => 'integer',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function transactionItems()
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->expires_at !== null
            && now()->greaterThan($this->expires_at);
    }

    public function secondsUntilExpiry(): int
    {
        if ($this->expires_at === null) {
            return 0;
        }

        return max(0, now()->diffInSeconds($this->expires_at, false));
    }

    public function scopeReleasable($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now());
    }
}