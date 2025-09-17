<?php
// app/Modules/Coupon/Models/Coupon.php - FINAL WORKING VERSION

namespace App\Modules\Coupon\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'coupon_type',
        'discount_type',
        'discount_value',
        'minimum_amount',
        'maximum_discount',
        'usage_limit',
        'used_count',
        'user_usage_limit',
        'expires_at',
        'starts_at',
        'status',
        'applicable_to',
        'excluded_users',
        'included_users',
        'applicable_zones',
        'excluded_zones',
        'applicable_vehicle_types',
        'excluded_vehicle_types',
        'first_ride_only',
        'returning_user_only',
        'created_by',
        'updated_by',
        'firebase_synced',
        'firebase_synced_at',
        'firebase_sync_status',
        'firebase_sync_error',
        'firebase_sync_attempts',
        'firebase_last_attempt_at'
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'minimum_amount' => 'decimal:2',
        'maximum_discount' => 'decimal:2',
        'expires_at' => 'datetime',
        'starts_at' => 'datetime',
        'excluded_users' => 'array',
        'included_users' => 'array',
        'applicable_zones' => 'array',
        'excluded_zones' => 'array',
        'applicable_vehicle_types' => 'array',
        'excluded_vehicle_types' => 'array',
        'first_ride_only' => 'boolean',
        'returning_user_only' => 'boolean',
        'firebase_synced' => 'boolean',
        'firebase_synced_at' => 'datetime',
        'firebase_last_attempt_at' => 'datetime',
        'firebase_sync_attempts' => 'integer',
    ];

    // Constants
    const STATUS_ENABLED = 'enabled';
    const STATUS_DISABLED = 'disabled';
    const STATUS_EXPIRED = 'expired';
    const STATUS_EXHAUSTED = 'exhausted';

    const TYPE_RIDE = 'ride';
    const TYPE_DELIVERY = 'delivery';
    const TYPE_BOTH = 'both';

    const DISCOUNT_PERCENTAGE = 'percentage';
    const DISCOUNT_FIXED = 'fixed';

    const APPLICABLE_ALL = 'all';
    const APPLICABLE_NEW_USERS = 'new_users';
    const APPLICABLE_EXISTING_USERS = 'existing_users';
    const APPLICABLE_SPECIFIC_USERS = 'specific_users';

    // Relationships
    public function usages()
    {
        return $this->hasMany(CouponUsage::class, 'coupon_code', 'code');
    }

    // Accessors
    public function getIsActiveAttribute()
    {
        return $this->status === self::STATUS_ENABLED &&
               $this->starts_at <= now() &&
               $this->expires_at > now() &&
               ($this->usage_limit === null || $this->used_count < $this->usage_limit);
    }

    public function getIsExpiredAttribute()
    {
        return $this->expires_at <= now();
    }

    public function getIsExhaustedAttribute()
    {
        return $this->usage_limit !== null && $this->used_count >= $this->usage_limit;
    }

    public function getRemainingUsagesAttribute()
    {
        if ($this->usage_limit === null) {
            return null;
        }
        return max(0, $this->usage_limit - $this->used_count);
    }

    public function getUsagePercentageAttribute()
    {
        if ($this->usage_limit === null) {
            return 0;
        }
        if ($this->usage_limit === 0) {
            return 100;
        }
        return round(($this->used_count / $this->usage_limit) * 100, 2);
    }

    // CRITICAL FIX: Convert arrays to string format for Firebase
    public function toFirebaseArray()
    {
        return [
            'code' => $this->code ?? '',
            'description' => $this->description ?? '',
            'coupon_type' => $this->coupon_type ?? 'ride',
            'discount_type' => $this->discount_type ?? 'percentage',
            'discount_value' => (float) ($this->discount_value ?? 0),
            'minimum_amount' => (float) ($this->minimum_amount ?? 0),
            'maximum_discount' => $this->maximum_discount ? (float) $this->maximum_discount : null,
            'usage_limit' => $this->usage_limit ? (int) $this->usage_limit : null,
            'used_count' => (int) ($this->used_count ?? 0),
            'user_usage_limit' => $this->user_usage_limit ? (int) $this->user_usage_limit : null,
            'expires_at' => $this->expires_at ? $this->expires_at->toISOString() : null,
            'starts_at' => $this->starts_at ? $this->starts_at->toISOString() : null,
            'status' => $this->status ?? 'enabled',
            'applicable_to' => $this->applicable_to ?? 'all',
            
            // CRITICAL: Store arrays as JSON strings to avoid FirestoreService array issues
            'excluded_users_json' => $this->getArrayAsJson('excluded_users'),
            'included_users_json' => $this->getArrayAsJson('included_users'),
            'applicable_zones_json' => $this->getArrayAsJson('applicable_zones'),
            'excluded_zones_json' => $this->getArrayAsJson('excluded_zones'),
            'applicable_vehicle_types_json' => $this->getArrayAsJson('applicable_vehicle_types'),
            'excluded_vehicle_types_json' => $this->getArrayAsJson('excluded_vehicle_types'),
            
            // Also include count for easy filtering in Firebase
            'excluded_users_count' => count($this->getCleanArray('excluded_users')),
            'included_users_count' => count($this->getCleanArray('included_users')),
            'applicable_zones_count' => count($this->getCleanArray('applicable_zones')),
            'excluded_zones_count' => count($this->getCleanArray('excluded_zones')),
            'applicable_vehicle_types_count' => count($this->getCleanArray('applicable_vehicle_types')),
            'excluded_vehicle_types_count' => count($this->getCleanArray('excluded_vehicle_types')),
            
            'first_ride_only' => (bool) ($this->first_ride_only ?? false),
            'returning_user_only' => (bool) ($this->returning_user_only ?? false),
            'is_active' => $this->is_active,
            'remaining_usages' => $this->remaining_usages,
            'usage_percentage' => $this->usage_percentage,
            'created_at' => $this->created_at ? $this->created_at->toISOString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toISOString() : null,
            'sync_updated_at' => now()->toISOString(),
            'last_modified_by' => $this->updated_by ?? 'system',
        ];
    }

    /**
     * Get array as JSON string for Firebase storage
     */
    private function getArrayAsJson($fieldName)
    {
        $array = $this->getCleanArray($fieldName);
        return json_encode($array);
    }

    /**
     * Get clean array from database field
     */
    private function getCleanArray($fieldName)
    {
        try {
            // Get the raw value from database first
            $rawValue = $this->getOriginal($fieldName);
            
            // Handle null or empty values
            if ($rawValue === null || $rawValue === '' || $rawValue === '[]' || $rawValue === 'null') {
                return [];
            }
            
            // If it's already an array from Laravel casting, use it
            $castValue = $this->getAttribute($fieldName);
            if (is_array($castValue)) {
                return $this->cleanArrayValues($castValue);
            }
            
            // Try to decode if it's a JSON string
            if (is_string($rawValue)) {
                $decoded = json_decode($rawValue, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $this->cleanArrayValues($decoded);
                }
                
                // Handle comma-separated values
                $csvArray = array_map('trim', explode(',', $rawValue));
                return $this->cleanArrayValues($csvArray);
            }
            
            // Fallback: return empty array
            return [];
            
        } catch (Exception $e) {
            Log::warning("Error processing array field {$fieldName} for coupon {$this->code}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Clean array values
     */
    private function cleanArrayValues($array)
    {
        if (!is_array($array)) {
            return [];
        }
        
        $cleaned = [];
        foreach ($array as $item) {
            if (!is_null($item) && $item !== '' && $item !== false && $item !== 'null') {
                $cleaned[] = (string) trim($item);
            }
        }
        
        return array_values(array_unique(array_filter($cleaned)));
    }

    // Firebase configuration
    public function getFirebaseCollection()
    {
        return 'coupons';
    }

    public function getFirebaseDocumentId()
    {
        return $this->code;
    }

    public function markAsSynced()
    {
        $this->update([
            'firebase_synced' => true,
            'firebase_synced_at' => now(),
            'firebase_sync_status' => 'synced',
            'firebase_sync_error' => null
        ]);
    }

    public function markSyncFailed($error)
    {
        $this->update([
            'firebase_synced' => false,
            'firebase_sync_status' => 'failed',
            'firebase_sync_error' => $error,
            'firebase_sync_attempts' => ($this->firebase_sync_attempts ?? 0) + 1,
            'firebase_last_attempt_at' => now()
        ]);
    }

    // User eligibility check
    public function canBeUsedBy($userId)
    {
        // Check if coupon is active
        if (!$this->is_active) {
            return ['valid' => false, 'reason' => 'Coupon is not active'];
        }

        // Check applicable_to restrictions
        switch ($this->applicable_to) {
            case self::APPLICABLE_NEW_USERS:
                // Add your new user logic here
                break;
            case self::APPLICABLE_EXISTING_USERS:
                // Add your existing user logic here
                break;
            case self::APPLICABLE_SPECIFIC_USERS:
                $includedUsers = $this->getCleanArray('included_users');
                if (!empty($includedUsers) && !in_array($userId, $includedUsers)) {
                    return ['valid' => false, 'reason' => 'User not eligible for this coupon'];
                }
                break;
        }

        // Check if user is excluded
        $excludedUsers = $this->getCleanArray('excluded_users');
        if (!empty($excludedUsers) && in_array($userId, $excludedUsers)) {
            return ['valid' => false, 'reason' => 'User is excluded from this coupon'];
        }

        // Check user usage limit
        if ($this->user_usage_limit) {
            $userUsageCount = $this->usages()->where('user_id', $userId)->count();
            if ($userUsageCount >= $this->user_usage_limit) {
                return ['valid' => false, 'reason' => 'User has reached usage limit for this coupon'];
            }
        }

        return ['valid' => true, 'reason' => 'Coupon can be used'];
    }

    // Helper methods for getting arrays (for use in your application logic)
    public function getExcludedUsersArray()
    {
        return $this->getCleanArray('excluded_users');
    }

    public function getIncludedUsersArray()
    {
        return $this->getCleanArray('included_users');
    }

    public function getApplicableZonesArray()
    {
        return $this->getCleanArray('applicable_zones');
    }

    public function getExcludedZonesArray()
    {
        return $this->getCleanArray('excluded_zones');
    }

    public function getApplicableVehicleTypesArray()
    {
        return $this->getCleanArray('applicable_vehicle_types');
    }

    public function getExcludedVehicleTypesArray()
    {
        return $this->getCleanArray('excluded_vehicle_types');
    }

    // Boot method to handle model events and fix array fields
    protected static function boot()
    {
        parent::boot();

        // Clean array fields before saving
        static::saving(function ($coupon) {
            // Auto-set status based on expiration
            if ($coupon->expires_at && $coupon->expires_at <= now()) {
                $coupon->status = self::STATUS_EXPIRED;
            }

            // Auto-set status based on usage limit
            if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
                $coupon->status = self::STATUS_EXHAUSTED;
            }

            // CRITICAL: Ensure array fields are properly formatted
            $arrayFields = [
                'excluded_users', 'included_users', 'applicable_zones', 
                'excluded_zones', 'applicable_vehicle_types', 'excluded_vehicle_types'
            ];

            foreach ($arrayFields as $field) {
                if ($coupon->isDirty($field)) {
                    $value = $coupon->getAttribute($field);
                    
                    // Convert to proper array format
                    if (is_null($value) || $value === '' || $value === 'null') {
                        $coupon->setAttribute($field, []);
                    } elseif (is_string($value)) {
                        // Handle JSON string
                        $decoded = json_decode($value, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $coupon->setAttribute($field, array_values(array_filter($decoded)));
                        } else {
                            // Handle comma-separated
                            $csvArray = array_map('trim', explode(',', $value));
                            $coupon->setAttribute($field, array_values(array_filter($csvArray)));
                        }
                    } elseif (is_array($value)) {
                        $coupon->setAttribute($field, array_values(array_filter($value)));
                    } else {
                        $coupon->setAttribute($field, []);
                    }
                }
            }
        });
    }

    // Static methods
    public static function getStatuses()
    {
        return [
            self::STATUS_ENABLED => 'Enabled',
            self::STATUS_DISABLED => 'Disabled',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_EXHAUSTED => 'Exhausted'
        ];
    }

    public static function getCouponTypes()
    {
        return [
            self::TYPE_RIDE => 'Ride',
            self::TYPE_DELIVERY => 'Delivery',
            self::TYPE_BOTH => 'Both'
        ];
    }

    public static function getDiscountTypes()
    {
        return [
            self::DISCOUNT_PERCENTAGE => 'Percentage',
            self::DISCOUNT_FIXED => 'Fixed Amount'
        ];
    }

    public static function getApplicableToOptions()
    {
        return [
            self::APPLICABLE_ALL => 'All Users',
            self::APPLICABLE_NEW_USERS => 'New Users Only',
            self::APPLICABLE_EXISTING_USERS => 'Existing Users Only',
            self::APPLICABLE_SPECIFIC_USERS => 'Specific Users'
        ];
    }

    // Scope for unsynced models
    public function scopeUnsynced($query)
    {
        return $query->where('firebase_synced', false);
    }

    // Scope for active coupons
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ENABLED)
                    ->where('starts_at', '<=', now())
                    ->where('expires_at', '>', now());
    }

    // Scope for expired coupons
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }
}