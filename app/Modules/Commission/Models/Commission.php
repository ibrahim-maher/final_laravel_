<?php
// app/Modules/Commission/Models/Commission.php

namespace App\Modules\Commission\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class Commission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'commission_type',
        'recipient_type',
        'calculation_method',
        'rate',
        'fixed_amount',
        'minimum_amount',
        'maximum_amount',
        'minimum_commission',
        'maximum_commission',
        'applicable_to',
        'applicable_zones',
        'excluded_zones',
        'applicable_vehicle_types',
        'excluded_vehicle_types',
        'applicable_services',
        'excluded_services',
        'tier_based',
        'tier_rules',
        'payment_frequency',
        'auto_payout',
        'minimum_payout_amount',
        'is_active',
        'priority_order',
        'starts_at',
        'expires_at',
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
        'rate' => 'decimal:4',
        'fixed_amount' => 'decimal:2',
        'minimum_amount' => 'decimal:2',
        'maximum_amount' => 'decimal:2',
        'minimum_commission' => 'decimal:2',
        'maximum_commission' => 'decimal:2',
        'minimum_payout_amount' => 'decimal:2',
        'applicable_zones' => 'array',
        'excluded_zones' => 'array',
        'applicable_vehicle_types' => 'array',
        'excluded_vehicle_types' => 'array',
        'applicable_services' => 'array',
        'excluded_services' => 'array',
        'tier_based' => 'boolean',
        'tier_rules' => 'array',
        'auto_payout' => 'boolean',
        'is_active' => 'boolean',
        'priority_order' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'firebase_synced' => 'boolean',
        'firebase_synced_at' => 'datetime',
        'firebase_last_attempt_at' => 'datetime',
        'firebase_sync_attempts' => 'integer',
    ];

    // Constants
    const COMMISSION_TYPE_PERCENTAGE = 'percentage';
    const COMMISSION_TYPE_FIXED = 'fixed';
    const COMMISSION_TYPE_HYBRID = 'hybrid';

    const RECIPIENT_DRIVER = 'driver';
    const RECIPIENT_COMPANY = 'company';
    const RECIPIENT_PARTNER = 'partner';
    const RECIPIENT_REFERRER = 'referrer';

    const CALCULATION_GROSS = 'gross';
    const CALCULATION_NET = 'net';
    const CALCULATION_TRIP_FARE = 'trip_fare';
    const CALCULATION_BASE_FARE = 'base_fare';

    const APPLICABLE_ALL = 'all';
    const APPLICABLE_RIDES = 'rides';
    const APPLICABLE_DELIVERY = 'delivery';
    const APPLICABLE_SPECIFIC = 'specific';

    const PAYMENT_DAILY = 'daily';
    const PAYMENT_WEEKLY = 'weekly';
    const PAYMENT_MONTHLY = 'monthly';
    const PAYMENT_INSTANT = 'instant';

    // Relationships
    public function payouts()
    {
        return $this->hasMany(CommissionPayout::class);
    }

    // Accessors
    public function getIsValidAttribute()
    {
        return $this->is_active &&
               ($this->starts_at === null || $this->starts_at <= now()) &&
               ($this->expires_at === null || $this->expires_at > now());
    }

    public function getFormattedRateAttribute()
    {
        if ($this->commission_type === self::COMMISSION_TYPE_PERCENTAGE) {
            return $this->rate . '%';
        } elseif ($this->commission_type === self::COMMISSION_TYPE_FIXED) {
            return '$' . number_format($this->fixed_amount, 2);
        }
        return $this->rate . '% + $' . number_format($this->fixed_amount, 2);
    }

    // Calculate commission amount
    public function calculateCommission($amount, $context = [])
    {
        if (!$this->is_valid || $amount < ($this->minimum_amount ?? 0)) {
            return 0;
        }

        // Check if commission applies to this context
        if (!$this->appliesTo($context)) {
            return 0;
        }

        $commissionAmount = 0;

        // Handle tier-based calculations
        if ($this->tier_based && !empty($this->tier_rules)) {
            $commissionAmount = $this->calculateTieredCommission($amount, $context);
        } else {
            $commissionAmount = $this->calculateSimpleCommission($amount);
        }

        // Apply commission limits
        if ($this->minimum_commission && $commissionAmount < $this->minimum_commission) {
            $commissionAmount = $this->minimum_commission;
        }

        if ($this->maximum_commission && $commissionAmount > $this->maximum_commission) {
            $commissionAmount = $this->maximum_commission;
        }

        return round($commissionAmount, 2);
    }

    private function calculateSimpleCommission($amount)
    {
        switch ($this->commission_type) {
            case self::COMMISSION_TYPE_PERCENTAGE:
                return ($amount * $this->rate) / 100;
            case self::COMMISSION_TYPE_FIXED:
                return $this->fixed_amount;
            case self::COMMISSION_TYPE_HYBRID:
                return ($amount * $this->rate) / 100 + $this->fixed_amount;
            default:
                return 0;
        }
    }

    private function calculateTieredCommission($amount, $context = [])
    {
        $tierRules = $this->getCleanArray('tier_rules');
        $totalCommission = 0;

        foreach ($tierRules as $tier) {
            if (!is_array($tier) || !isset($tier['min_amount'])) {
                continue;
            }

            $minAmount = $tier['min_amount'];
            $maxAmount = $tier['max_amount'] ?? PHP_INT_MAX;
            $tierRate = $tier['rate'] ?? 0;
            $tierFixed = $tier['fixed_amount'] ?? 0;

            if ($amount >= $minAmount && $amount <= $maxAmount) {
                $applicableAmount = min($amount, $maxAmount) - $minAmount;
                
                if ($tier['type'] === 'percentage') {
                    $totalCommission += ($applicableAmount * $tierRate) / 100;
                } else {
                    $totalCommission += $tierFixed;
                }
                
                break; // Use first matching tier
            }
        }

        return $totalCommission;
    }

    // Check if commission applies to context
    public function appliesTo($context = [])
    {
        // Check service type
        if ($this->applicable_to !== self::APPLICABLE_ALL) {
            $serviceType = $context['service_type'] ?? 'rides';
            if ($this->applicable_to !== $serviceType) {
                return false;
            }
        }

        // Check zones
        if (!empty($context['zone'])) {
            $applicableZones = $this->getCleanArray('applicable_zones');
            $excludedZones = $this->getCleanArray('excluded_zones');

            if (!empty($applicableZones) && !in_array($context['zone'], $applicableZones)) {
                return false;
            }

            if (!empty($excludedZones) && in_array($context['zone'], $excludedZones)) {
                return false;
            }
        }

        // Check vehicle types
        if (!empty($context['vehicle_type'])) {
            $applicableVehicleTypes = $this->getCleanArray('applicable_vehicle_types');
            $excludedVehicleTypes = $this->getCleanArray('excluded_vehicle_types');

            if (!empty($applicableVehicleTypes) && !in_array($context['vehicle_type'], $applicableVehicleTypes)) {
                return false;
            }

            if (!empty($excludedVehicleTypes) && in_array($context['vehicle_type'], $excludedVehicleTypes)) {
                return false;
            }
        }

        return true;
    }

    // Firebase conversion
    public function toFirebaseArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name ?? '',
            'description' => $this->description ?? '',
            'commission_type' => $this->commission_type ?? 'percentage',
            'recipient_type' => $this->recipient_type ?? 'driver',
            'calculation_method' => $this->calculation_method ?? 'gross',
            'rate' => (float) ($this->rate ?? 0),
            'fixed_amount' => (float) ($this->fixed_amount ?? 0),
            'minimum_amount' => (float) ($this->minimum_amount ?? 0),
            'maximum_amount' => $this->maximum_amount ? (float) $this->maximum_amount : null,
            'minimum_commission' => $this->minimum_commission ? (float) $this->minimum_commission : null,
            'maximum_commission' => $this->maximum_commission ? (float) $this->maximum_commission : null,
            'applicable_to' => $this->applicable_to ?? 'all',
            'applicable_zones_json' => $this->getArrayAsJson('applicable_zones'),
            'excluded_zones_json' => $this->getArrayAsJson('excluded_zones'),
            'applicable_vehicle_types_json' => $this->getArrayAsJson('applicable_vehicle_types'),
            'excluded_vehicle_types_json' => $this->getArrayAsJson('excluded_vehicle_types'),
            'applicable_services_json' => $this->getArrayAsJson('applicable_services'),
            'excluded_services_json' => $this->getArrayAsJson('excluded_services'),
            'tier_based' => (bool) ($this->tier_based ?? false),
            'tier_rules_json' => $this->getArrayAsJson('tier_rules'),
            'payment_frequency' => $this->payment_frequency ?? 'weekly',
            'auto_payout' => (bool) ($this->auto_payout ?? false),
            'minimum_payout_amount' => (float) ($this->minimum_payout_amount ?? 0),
            'is_active' => (bool) ($this->is_active ?? true),
            'priority_order' => (int) ($this->priority_order ?? 1),
            'starts_at' => $this->starts_at ? $this->starts_at->toISOString() : null,
            'expires_at' => $this->expires_at ? $this->expires_at->toISOString() : null,
            'created_at' => $this->created_at ? $this->created_at->toISOString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toISOString() : null,
            'sync_updated_at' => now()->toISOString(),
        ];
    }

    // Helper methods (same as TaxSetting)
    private function getArrayAsJson($fieldName)
    {
        $array = $this->getCleanArray($fieldName);
        return json_encode($array);
    }

    private function getCleanArray($fieldName)
    {
        try {
            $rawValue = $this->getOriginal($fieldName);
            
            if ($rawValue === null || $rawValue === '' || $rawValue === '[]' || $rawValue === 'null') {
                return [];
            }
            
            $castValue = $this->getAttribute($fieldName);
            if (is_array($castValue)) {
                return $this->cleanArrayValues($castValue);
            }
            
            if (is_string($rawValue)) {
                $decoded = json_decode($rawValue, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $this->cleanArrayValues($decoded);
                }
                
                $csvArray = array_map('trim', explode(',', $rawValue));
                return $this->cleanArrayValues($csvArray);
            }
            
            return [];
            
        } catch (Exception $e) {
            Log::warning("Error processing array field {$fieldName} for commission {$this->id}: " . $e->getMessage());
            return [];
        }
    }

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
        return 'commissions';
    }

    public function getFirebaseDocumentId()
    {
        return (string) $this->id;
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

    // Static methods
    public static function getCommissionTypes()
    {
        return [
            self::COMMISSION_TYPE_PERCENTAGE => 'Percentage',
            self::COMMISSION_TYPE_FIXED => 'Fixed Amount',
            self::COMMISSION_TYPE_HYBRID => 'Hybrid (Percentage + Fixed)'
        ];
    }

    public static function getRecipientTypes()
    {
        return [
            self::RECIPIENT_DRIVER => 'Driver',
            self::RECIPIENT_COMPANY => 'Company',
            self::RECIPIENT_PARTNER => 'Partner',
            self::RECIPIENT_REFERRER => 'Referrer'
        ];
    }

    public static function getCalculationMethods()
    {
        return [
            self::CALCULATION_GROSS => 'Gross Amount',
            self::CALCULATION_NET => 'Net Amount',
            self::CALCULATION_TRIP_FARE => 'Trip Fare Only',
            self::CALCULATION_BASE_FARE => 'Base Fare Only'
        ];
    }

    public static function getApplicableToOptions()
    {
        return [
            self::APPLICABLE_ALL => 'All Services',
            self::APPLICABLE_RIDES => 'Rides Only',
            self::APPLICABLE_DELIVERY => 'Delivery Only',
            self::APPLICABLE_SPECIFIC => 'Specific Services'
        ];
    }

    public static function getPaymentFrequencies()
    {
        return [
            self::PAYMENT_INSTANT => 'Instant',
            self::PAYMENT_DAILY => 'Daily',
            self::PAYMENT_WEEKLY => 'Weekly',
            self::PAYMENT_MONTHLY => 'Monthly'
        ];
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    });
    }

    public function scopeUnsynced($query)
    {
        return $query->where('firebase_synced', false);
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority_order', 'asc');
    }

    public function scopeByRecipient($query, $recipientType)
    {
        return $query->where('recipient_type', $recipientType);
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($commission) {
            // Set default priority if not set
            if (!$commission->priority_order) {
                $maxPriority = static::max('priority_order') ?? 0;
                $commission->priority_order = $maxPriority + 1;
            }

            // Clean array fields
            $arrayFields = [
                'applicable_zones', 'excluded_zones', 'applicable_vehicle_types', 
                'excluded_vehicle_types', 'applicable_services', 'excluded_services', 'tier_rules'
            ];

            foreach ($arrayFields as $field) {
                if ($commission->isDirty($field)) {
                    $value = $commission->getAttribute($field);
                    
                    if (is_null($value) || $value === '' || $value === 'null') {
                        $commission->setAttribute($field, []);
                    } elseif (is_string($value)) {
                        $decoded = json_decode($value, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $commission->setAttribute($field, array_values(array_filter($decoded)));
                        } else {
                            $csvArray = array_map('trim', explode(',', $value));
                            $commission->setAttribute($field, array_values(array_filter($csvArray)));
                        }
                    } elseif (is_array($value)) {
                        $commission->setAttribute($field, array_values(array_filter($value)));
                    } else {
                        $commission->setAttribute($field, []);
                    }
                }
            }
        });
    }
}