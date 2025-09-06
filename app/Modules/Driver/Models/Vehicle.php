<?php

namespace App\Modules\Driver\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Vehicle extends Model
{
    protected $collection = 'vehicles';
    
    protected $fillable = [
        'driver_firebase_uid',
        'make',
        'model',
        'year',
        'color',
        'license_plate',
        'vin',
        'vehicle_type',
        'fuel_type',
        'transmission',
        'doors',
        'seats',
        'is_primary',
        'status',
        'verification_status',
        'registration_number',
        'registration_expiry',
        'registration_state',
        'insurance_provider',
        'insurance_policy_number',
        'insurance_expiry',
        'inspection_date',
        'inspection_expiry',
        'inspection_certificate',
        'mileage',
        'condition_rating',
        'photos',
        'features',
        'notes',
        'created_at',
        'updated_at'
    ];

    protected $dates = [
        'registration_expiry',
        'insurance_expiry',
        'inspection_date',
        'inspection_expiry',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'year' => 'integer',
        'doors' => 'integer',
        'seats' => 'integer',
        'is_primary' => 'boolean',
        'mileage' => 'integer',
        'condition_rating' => 'float',
        'photos' => 'array',
        'features' => 'array'
    ];

    // Vehicle status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_MAINTENANCE = 'maintenance';
    const STATUS_SUSPENDED = 'suspended';

    // Verification status constants
    const VERIFICATION_PENDING = 'pending';
    const VERIFICATION_VERIFIED = 'verified';
    const VERIFICATION_REJECTED = 'rejected';

    // Vehicle type constants
    const TYPE_SEDAN = 'sedan';
    const TYPE_SUV = 'suv';
    const TYPE_HATCHBACK = 'hatchback';
    const TYPE_PICKUP = 'pickup';
    const TYPE_VAN = 'van';
    const TYPE_MOTORCYCLE = 'motorcycle';
    const TYPE_BICYCLE = 'bicycle';
    const TYPE_SCOOTER = 'scooter';

    // Fuel type constants
    const FUEL_GASOLINE = 'gasoline';
    const FUEL_DIESEL = 'diesel';
    const FUEL_ELECTRIC = 'electric';
    const FUEL_HYBRID = 'hybrid';
    const FUEL_CNG = 'cng';
    const FUEL_LPG = 'lpg';

    /**
     * Get the driver that owns the vehicle
     */
    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_firebase_uid', 'firebase_uid');
    }

    /**
     * Get rides using this vehicle
     */
    public function rides()
    {
        return $this->hasMany(Ride::class, 'vehicle_id', 'id');
    }

    /**
     * Check if vehicle is active
     */
    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if vehicle is verified
     */
    public function isVerified()
    {
        return $this->verification_status === self::VERIFICATION_VERIFIED;
    }

    /**
     * Check if vehicle registration is expired
     */
    public function isRegistrationExpired()
    {
        return $this->registration_expiry && Carbon::parse($this->registration_expiry)->isPast();
    }

    /**
     * Check if vehicle insurance is expired
     */
    public function isInsuranceExpired()
    {
        return $this->insurance_expiry && Carbon::parse($this->insurance_expiry)->isPast();
    }

    /**
     * Check if vehicle inspection is expired
     */
    public function isInspectionExpired()
    {
        return $this->inspection_expiry && Carbon::parse($this->inspection_expiry)->isPast();
    }

    /**
     * Get vehicle's full name
     */
    public function getFullNameAttribute()
    {
        return "{$this->year} {$this->make} {$this->model}";
    }

    /**
     * Get vehicle age in years
     */
    public function getAgeAttribute()
    {
        return now()->year - $this->year;
    }

    /**
     * Get days until registration expires
     */
    public function getRegistrationExpiryDaysAttribute()
    {
        if (!$this->registration_expiry) {
            return null;
        }
        
        return Carbon::parse($this->registration_expiry)->diffInDays(now(), false);
    }

    /**
     * Get days until insurance expires
     */
    public function getInsuranceExpiryDaysAttribute()
    {
        if (!$this->insurance_expiry) {
            return null;
        }
        
        return Carbon::parse($this->insurance_expiry)->diffInDays(now(), false);
    }

    /**
     * Get days until inspection expires
     */
    public function getInspectionExpiryDaysAttribute()
    {
        if (!$this->inspection_expiry) {
            return null;
        }
        
        return Carbon::parse($this->inspection_expiry)->diffInDays(now(), false);
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute()
    {
        $colors = [
            self::STATUS_ACTIVE => 'success',
            self::STATUS_INACTIVE => 'secondary',
            self::STATUS_MAINTENANCE => 'warning',
            self::STATUS_SUSPENDED => 'danger'
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    /**
     * Get verification status badge color
     */
    public function getVerificationColorAttribute()
    {
        $colors = [
            self::VERIFICATION_VERIFIED => 'success',
            self::VERIFICATION_PENDING => 'warning',
            self::VERIFICATION_REJECTED => 'danger'
        ];

        return $colors[$this->verification_status] ?? 'secondary';
    }

    /**
     * Get primary photo URL
     */
    public function getPrimaryPhotoAttribute()
    {
        if (is_array($this->photos) && count($this->photos) > 0) {
            return $this->photos[0];
        }
        
        return null;
    }

    /**
     * Scope: Active vehicles
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope: Verified vehicles
     */
    public function scopeVerified($query)
    {
        return $query->where('verification_status', self::VERIFICATION_VERIFIED);
    }

    /**
     * Scope: Primary vehicles
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope: Expiring soon (within 30 days)
     */
    public function scopeExpiringSoon($query)
    {
        $thirtyDaysFromNow = now()->addDays(30);
        
        return $query->where(function ($q) use ($thirtyDaysFromNow) {
            $q->where('registration_expiry', '<=', $thirtyDaysFromNow)
              ->orWhere('insurance_expiry', '<=', $thirtyDaysFromNow)
              ->orWhere('inspection_expiry', '<=', $thirtyDaysFromNow);
        });
    }

    /**
     * Scope: Search by make, model, or license plate
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('make', 'like', "%{$term}%")
              ->orWhere('model', 'like', "%{$term}%")
              ->orWhere('license_plate', 'like', "%{$term}%")
              ->orWhere('vin', 'like', "%{$term}%");
        });
    }

    /**
     * Get available vehicle types
     */
    public static function getVehicleTypes()
    {
        return [
            self::TYPE_SEDAN => 'Sedan',
            self::TYPE_SUV => 'SUV',
            self::TYPE_HATCHBACK => 'Hatchback',
            self::TYPE_PICKUP => 'Pickup Truck',
            self::TYPE_VAN => 'Van',
            self::TYPE_MOTORCYCLE => 'Motorcycle',
            self::TYPE_BICYCLE => 'Bicycle',
            self::TYPE_SCOOTER => 'Scooter'
        ];
    }

    /**
     * Get available fuel types
     */
    public static function getFuelTypes()
    {
        return [
            self::FUEL_GASOLINE => 'Gasoline',
            self::FUEL_DIESEL => 'Diesel',
            self::FUEL_ELECTRIC => 'Electric',
            self::FUEL_HYBRID => 'Hybrid',
            self::FUEL_CNG => 'CNG',
            self::FUEL_LPG => 'LPG'
        ];
    }
}