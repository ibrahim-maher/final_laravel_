<?php

namespace App\Modules\Driver\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\FirebaseSyncable;

class Vehicle extends Model
{
    use HasFactory, FirebaseSyncable;

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
        'verification_date',
        'verified_by',
        'verification_notes',
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
        'last_maintenance_date',
        'next_maintenance_due',
        'created_by',
        'updated_by',
        'firebase_synced',
        'firebase_synced_at'
    ];

    protected $casts = [
        'year' => 'integer',
        'doors' => 'integer',
        'seats' => 'integer',
        'is_primary' => 'boolean',
        'verification_date' => 'datetime',
        'registration_expiry' => 'date',
        'insurance_expiry' => 'date',
        'inspection_date' => 'date',
        'inspection_expiry' => 'date',
        'last_maintenance_date' => 'date',
        'next_maintenance_due' => 'date',
        'mileage' => 'integer',
        'condition_rating' => 'decimal:2',
        'photos' => 'array',
        'features' => 'array',
        'firebase_synced' => 'boolean',
        'firebase_synced_at' => 'datetime',
    ];

    // Firebase sync configuration
    protected $firebaseCollection = 'vehicles';
    protected $firebaseKey = 'id';

    // Constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_MAINTENANCE = 'maintenance';
    const STATUS_SUSPENDED = 'suspended';

    const VERIFICATION_PENDING = 'pending';
    const VERIFICATION_VERIFIED = 'verified';
    const VERIFICATION_REJECTED = 'rejected';

    const TYPE_SEDAN = 'sedan';
    const TYPE_SUV = 'suv';
    const TYPE_HATCHBACK = 'hatchback';
    const TYPE_PICKUP = 'pickup';
    const TYPE_VAN = 'van';
    const TYPE_MOTORCYCLE = 'motorcycle';
    const TYPE_BICYCLE = 'bicycle';
    const TYPE_SCOOTER = 'scooter';

    const FUEL_GASOLINE = 'gasoline';
    const FUEL_DIESEL = 'diesel';
    const FUEL_ELECTRIC = 'electric';
    const FUEL_HYBRID = 'hybrid';
    const FUEL_CNG = 'cng';
    const FUEL_LPG = 'lpg';

    // Relationships
    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_firebase_uid', 'firebase_uid');
    }

    public function rides()
    {
        return $this->hasMany(Ride::class, 'vehicle_id');
    }

    public function documents()
    {
        return $this->hasMany(DriverDocument::class, 'vehicle_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeVerified($query)
    {
        return $query->where('verification_status', self::VERIFICATION_VERIFIED);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeUnsynced($query)
    {
        return $query->where('firebase_synced', false);
    }

    public function scopeExpiredRegistration($query)
    {
        return $query->where('registration_expiry', '<', now());
    }

    public function scopeExpiredInsurance($query)
    {
        return $query->where('insurance_expiry', '<', now());
    }

    public function scopeExpiringRegistrationSoon($query, $days = 30)
    {
        return $query->whereBetween('registration_expiry', [now(), now()->addDays($days)]);
    }

    public function scopeExpiringInsuranceSoon($query, $days = 30)
    {
        return $query->whereBetween('insurance_expiry', [now(), now()->addDays($days)]);
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return "{$this->year} {$this->make} {$this->model}";
    }

    public function getAgeAttribute()
    {
        return now()->year - $this->year;
    }

    public function getStatusBadgeClassAttribute()
    {
        $classes = [
            self::STATUS_ACTIVE => 'badge-success',
            self::STATUS_INACTIVE => 'badge-secondary',
            self::STATUS_MAINTENANCE => 'badge-warning',
            self::STATUS_SUSPENDED => 'badge-danger'
        ];

        return $classes[$this->status] ?? 'badge-secondary';
    }

    public function getVerificationBadgeClassAttribute()
    {
        $classes = [
            self::VERIFICATION_VERIFIED => 'badge-success',
            self::VERIFICATION_PENDING => 'badge-warning',
            self::VERIFICATION_REJECTED => 'badge-danger'
        ];

        return $classes[$this->verification_status] ?? 'badge-secondary';
    }

    // Helper methods
    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isVerified()
    {
        return $this->verification_status === self::VERIFICATION_VERIFIED;
    }

    public function isPrimary()
    {
        return $this->is_primary;
    }

    public function isRegistrationExpired()
    {
        return $this->registration_expiry && $this->registration_expiry->isPast();
    }

    public function isInsuranceExpired()
    {
        return $this->insurance_expiry && $this->insurance_expiry->isPast();
    }

    public function isRegistrationExpiringSoon($days = 30)
    {
        return $this->registration_expiry && 
               $this->registration_expiry->between(now(), now()->addDays($days));
    }

    public function isInsuranceExpiringSoon($days = 30)
    {
        return $this->insurance_expiry && 
               $this->insurance_expiry->between(now(), now()->addDays($days));
    }

    public function needsMaintenance()
    {
        return $this->next_maintenance_due && $this->next_maintenance_due->isPast();
    }

    // Firebase sync
    public function toFirebaseArray()
    {
        return [
            // Basic vehicle info
            'id' => $this->id,
            'driver_firebase_uid' => $this->driver_firebase_uid ?? '',
            'make' => $this->make ?? '',
            'model' => $this->model ?? '',
            'year' => $this->year ?? 0,
            'color' => $this->color ?? '',
            'license_plate' => $this->license_plate ?? '',
            'vin' => $this->vin ?? '',
            
            // Vehicle specifications
            'vehicle_type' => $this->vehicle_type ?? '',
            'fuel_type' => $this->fuel_type ?? '',
            'transmission' => $this->transmission ?? '',
            'doors' => $this->doors ?? 4,
            'seats' => $this->seats ?? 4,
            'is_primary' => $this->is_primary ?? false,
            
            // Status and verification
            'status' => $this->status ?? 'inactive',
            'verification_status' => $this->verification_status ?? 'pending',
            'verification_date' => $this->verification_date ? $this->verification_date->toISOString() : null,
            'verified_by' => $this->verified_by ?? '',
            'verification_notes' => $this->verification_notes ?? '',
            
            // Registration and insurance
            'registration_number' => $this->registration_number ?? '',
            'registration_expiry' => $this->registration_expiry ? $this->registration_expiry->format('Y-m-d') : '',
            'registration_state' => $this->registration_state ?? '',
            'insurance_provider' => $this->insurance_provider ?? '',
            'insurance_policy_number' => $this->insurance_policy_number ?? '',
            'insurance_expiry' => $this->insurance_expiry ? $this->insurance_expiry->format('Y-m-d') : '',
            
            // Inspection and maintenance
            'inspection_date' => $this->inspection_date ? $this->inspection_date->format('Y-m-d') : '',
            'inspection_expiry' => $this->inspection_expiry ? $this->inspection_expiry->format('Y-m-d') : '',
            'last_maintenance_date' => $this->last_maintenance_date ? $this->last_maintenance_date->format('Y-m-d') : '',
            'next_maintenance_due' => $this->next_maintenance_due ? $this->next_maintenance_due->format('Y-m-d') : '',
            
            // Vehicle condition and features
            'mileage' => $this->mileage ?? 0,
            'condition_rating' => $this->condition_rating ?? 0,
            'photos' => $this->photos ?? [],
            'features' => $this->features ?? [],
            'notes' => $this->notes ?? '',
            
            // Timestamps
            'created_at' => $this->created_at ? $this->created_at->toISOString() : '',
            'updated_at' => $this->updated_at ? $this->updated_at->toISOString() : '',
            'sync_updated_at' => now()->toISOString(),
            'last_modified_by' => $this->updated_by ?? 'system',
        ];
    }

    // Static methods
    public static function getStatuses()
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_MAINTENANCE => 'Maintenance',
            self::STATUS_SUSPENDED => 'Suspended'
        ];
    }

    public static function getVerificationStatuses()
    {
        return [
            self::VERIFICATION_PENDING => 'Pending',
            self::VERIFICATION_VERIFIED => 'Verified',
            self::VERIFICATION_REJECTED => 'Rejected'
        ];
    }

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

    public static function getTransmissionTypes()
    {
        return [
            'manual' => 'Manual',
            'automatic' => 'Automatic',
            'cvt' => 'CVT',
            'semi_automatic' => 'Semi-Automatic'
        ];
    }
}