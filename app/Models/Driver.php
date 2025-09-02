<?php

namespace App\Modules\Driver\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Driver extends Model
{
    protected $collection = 'drivers';
    
    protected $fillable = [
        'firebase_uid',
        'name',
        'email',
        'phone',
        'photo_url',
        'date_of_birth',
        'gender',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'emergency_contact_name',
        'emergency_contact_phone',
        'license_number',
        'license_expiry',
        'license_class',
        'license_state',
        'status',
        'verification_status',
        'verification_date',
        'availability_status',
        'rating',
        'total_rides',
        'completed_rides',
        'cancelled_rides',
        'total_earnings',
        'current_location_lat',
        'current_location_lng',
        'last_location_update',
        'join_date',
        'last_active',
        'background_check_status',
        'background_check_date',
        'drug_test_status',
        'drug_test_date',
        'insurance_provider',
        'insurance_policy_number',
        'insurance_expiry',
        'bank_account_number',
        'bank_routing_number',
        'bank_account_holder_name',
        'tax_id',
        'created_at',
        'updated_at'
    ];

    protected $dates = [
        'date_of_birth',
        'license_expiry',
        'verification_date',
        'last_location_update',
        'join_date',
        'last_active',
        'background_check_date',
        'drug_test_date',
        'insurance_expiry',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'rating' => 'float',
        'total_rides' => 'integer',
        'completed_rides' => 'integer',
        'cancelled_rides' => 'integer',
        'total_earnings' => 'decimal:2',
        'current_location_lat' => 'decimal:8',
        'current_location_lng' => 'decimal:8'
    ];

    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_PENDING = 'pending';

    // Verification status constants
    const VERIFICATION_PENDING = 'pending';
    const VERIFICATION_VERIFIED = 'verified';
    const VERIFICATION_REJECTED = 'rejected';

    // Availability status constants
    const AVAILABILITY_AVAILABLE = 'available';
    const AVAILABILITY_BUSY = 'busy';
    const AVAILABILITY_OFFLINE = 'offline';

    /**
     * Get driver's vehicles
     */
    public function vehicles()
    {
        return $this->hasMany(Vehicle::class, 'driver_firebase_uid', 'firebase_uid');
    }

    /**
     * Get driver's rides
     */
    public function rides()
    {
        return $this->hasMany(Ride::class, 'driver_firebase_uid', 'firebase_uid');
    }

    /**
     * Get driver's activities
     */
    public function activities()
    {
        return $this->hasMany(DriverActivity::class, 'driver_firebase_uid', 'firebase_uid');
    }

    /**
     * Get driver's documents
     */
    public function documents()
    {
        return $this->hasMany(DriverDocument::class, 'driver_firebase_uid', 'firebase_uid');
    }

    /**
     * Get driver's licenses
     */
    public function licenses()
    {
        return $this->hasMany(DriverLicense::class, 'driver_firebase_uid', 'firebase_uid');
    }

    /**
     * Check if driver is active
     */
    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if driver is verified
     */
    public function isVerified()
    {
        return $this->verification_status === self::VERIFICATION_VERIFIED;
    }

    /**
     * Check if driver is available
     */
    public function isAvailable()
    {
        return $this->availability_status === self::AVAILABILITY_AVAILABLE && $this->isActive();
    }

    /**
     * Get driver's full name
     */
    public function getFullNameAttribute()
    {
        return $this->name;
    }

    /**
     * Get driver's age
     */
    public function getAgeAttribute()
    {
        if (!$this->date_of_birth) {
            return null;
        }
        
        return Carbon::parse($this->date_of_birth)->age;
    }

    /**
     * Get completion rate
     */
    public function getCompletionRateAttribute()
    {
        if ($this->total_rides == 0) {
            return 0;
        }
        
        return round(($this->completed_rides / $this->total_rides) * 100, 2);
    }

    /**
     * Get cancellation rate
     */
    public function getCancellationRateAttribute()
    {
        if ($this->total_rides == 0) {
            return 0;
        }
        
        return round(($this->cancelled_rides / $this->total_rides) * 100, 2);
    }

    /**
     * Get primary vehicle
     */
    public function getPrimaryVehicle()
    {
        return $this->vehicles()->where('is_primary', true)->first() ?? $this->vehicles()->first();
    }

    /**
     * Get average weekly earnings
     */
    public function getWeeklyEarnings()
    {
        // This would typically be calculated from ride data
        // For now, return a simple calculation
        return $this->total_earnings / max(1, $this->total_rides) * 7; // Rough estimate
    }

    /**
     * Update driver location
     */
    public function updateLocation($latitude, $longitude)
    {
        $this->current_location_lat = $latitude;
        $this->current_location_lng = $longitude;
        $this->last_location_update = now();
        $this->save();
    }

    /**
     * Update last active timestamp
     */
    public function updateLastActive()
    {
        $this->last_active = now();
        $this->save();
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute()
    {
        $colors = [
            self::STATUS_ACTIVE => 'success',
            self::STATUS_INACTIVE => 'secondary',
            self::STATUS_SUSPENDED => 'danger',
            self::STATUS_PENDING => 'warning'
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
     * Scope: Active drivers
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope: Verified drivers
     */
    public function scopeVerified($query)
    {
        return $query->where('verification_status', self::VERIFICATION_VERIFIED);
    }

    /**
     * Scope: Available drivers
     */
    public function scopeAvailable($query)
    {
        return $query->where('availability_status', self::AVAILABILITY_AVAILABLE)
                    ->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope: Search by name, email, or phone
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%")
              ->orWhere('phone', 'like', "%{$term}%")
              ->orWhere('license_number', 'like', "%{$term}%");
        });
    }
}