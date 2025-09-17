<?php
// app/Modules/Coupon/Models/CouponUsage.php

namespace App\Modules\Coupon\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\FirebaseSyncable;

class CouponUsage extends Model
{
    use HasFactory, FirebaseSyncable;

    protected $fillable = [
        'coupon_code',
        'user_id',
        'ride_id',
        'order_id',
        'discount_amount',
        'original_amount',
        'final_amount',
        'used_at',
        'firebase_synced',
        'firebase_synced_at'
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
        'original_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'used_at' => 'datetime',
        'firebase_synced' => 'boolean',
        'firebase_synced_at' => 'datetime',
    ];

    // Firebase sync configuration
    protected $firebaseCollection = 'coupon_usages';
    protected $firebaseKey = 'id';

    // Relationships
    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_code', 'code');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Scopes
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByCoupon($query, $couponCode)
    {
        return $query->where('coupon_code', $couponCode);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('used_at', '>=', now()->subDays($days));
    }

    public function scopeUnsynced($query)
    {
        return $query->where('firebase_synced', false);
    }

    // Accessors
    public function getSavingsPercentageAttribute()
    {
        if ($this->original_amount == 0) {
            return 0;
        }
        return round(($this->discount_amount / $this->original_amount) * 100, 2);
    }

    // Override toArray for Firebase sync
    public function toFirebaseArray()
    {
        return [
            'id' => $this->id,
            'coupon_code' => $this->coupon_code ?? '',
            'user_id' => $this->user_id ?? '',
            'ride_id' => $this->ride_id,
            'order_id' => $this->order_id,
            'discount_amount' => $this->discount_amount ?? 0,
            'original_amount' => $this->original_amount ?? 0,
            'final_amount' => $this->final_amount ?? 0,
            'savings_percentage' => $this->savings_percentage,
            'used_at' => $this->used_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'sync_updated_at' => now()->toISOString(),
        ];
    }

    // Firebase sync methods
    public function getFirebaseCollection()
    {
        return $this->firebaseCollection;
    }

    public function getFirebaseDocumentId()
    {
        return (string) $this->id;
    }

    public function markAsSynced()
    {
        $this->update([
            'firebase_synced' => true,
            'firebase_synced_at' => now()
        ]);
    }
}