<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = ['code', 'type', 'value', 'minimum_purchase', 'usage_limit', 'used_count', 'starts_at', 'ends_at', 'is_active'];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean'];

    public function isUsableFor(int $amount): bool
    {
        return $this->is_active
            && $amount >= $this->minimum_purchase
            && (! $this->starts_at || now()->greaterThanOrEqualTo($this->starts_at))
            && (! $this->ends_at || now()->lessThanOrEqualTo($this->ends_at))
            && ($this->usage_limit === null || $this->used_count < $this->usage_limit);
    }

    public function discountFor(int $amount): int
    {
        $discount = $this->type === 'percent'
            ? (int) floor($amount * $this->value / 100)
            : $this->value;

        return min($amount, $discount);
    }
}
