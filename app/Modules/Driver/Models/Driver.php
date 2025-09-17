<?php
// app/Modules/Driver/Models/Driver.php

namespace App\Modules\Driver\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Traits\FirebaseSyncable;

class Driver extends Model
{
    use HasFactory, FirebaseSyncable;

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
        'license_type',
        'license_state',
        'issuing_state',
        'status',
        'verification_status',
        'verification_date',
        'verified_by',
        'verification_notes',
        'availability_status',
        'rating',
        'total_rides',
        'completed_rides',
        'cancelled_rides',
        'total_earnings',
        'current_location_lat',
        'current_location_lng',
        'current_address',
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
        'firebase_synced',
        'firebase_synced_at'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'license_expiry' => 'date',
        'verification_date' => 'datetime',
        'last_location_update' => 'datetime',
        'join_date' => 'datetime',
        'last_active' => 'datetime',
        'background_check_date' => 'date',
        'drug_test_date' => 'date',
        'insurance_expiry' => 'date',
        'firebase_synced' => 'boolean',
        'firebase_synced_at' => 'datetime',
        'rating' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'current_location_lat' => 'decimal:8',
        'current_location_lng' => 'decimal:8',
    ];

    // Firebase sync configuration
    protected $firebaseCollection = 'drivers';
    protected $firebaseKey = 'firebase_uid';

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

    // Standard Eloquent relationships for SQL-based models
    public function vehicles()
    {
        return $this->hasMany(Vehicle::class, 'driver_firebase_uid', 'firebase_uid');
    }

    public function documents()
    {
        return $this->hasMany(DriverDocument::class, 'driver_firebase_uid', 'firebase_uid');
    }

    public function licenses()
    {
        return $this->hasMany(DriverLicense::class, 'driver_firebase_uid', 'firebase_uid');
    }

    public function activities()
    {
        return $this->hasMany(DriverActivity::class, 'driver_firebase_uid', 'firebase_uid');
    }

    // FIXED: Custom method for Firestore-based rides (replaces the problematic Eloquent relationship)
    public function getRides($filters = [])
    {
        try {
            $rideModel = new Ride();
            return $rideModel->getRidesByDriver($this->firebase_uid, $filters);
        } catch (\Exception $e) {
            Log::error('Error getting rides for driver: ' . $e->getMessage(), [
                'driver_uid' => $this->firebase_uid
            ]);
            return [];
        }
    }

    // FIXED: Custom method for getting active rides
    public function getActiveRides()
    {
        try {
            $rideModel = new Ride();
            return $rideModel->getActiveRidesForDriver($this->firebase_uid);
        } catch (\Exception $e) {
            Log::error('Error getting active rides for driver: ' . $e->getMessage(), [
                'driver_uid' => $this->firebase_uid
            ]);
            return [];
        }
    }

    // FIXED: Custom method for ride statistics
    public function getRideStatistics()
    {
        try {
            $rideModel = new Ride();
            $rides = $rideModel->getRidesByDriver($this->firebase_uid);
            
            return $this->calculateRideStatistics($rides);
        } catch (\Exception $e) {
            Log::error('Error getting ride statistics for driver: ' . $e->getMessage(), [
                'driver_uid' => $this->firebase_uid
            ]);
            return [
                'total_rides' => 0,
                'completed_rides' => 0,
                'cancelled_rides' => 0,
                'completion_rate' => 0,
                'total_earnings' => 0,
                'average_rating' => 0
            ];
        }
    }

    // Helper method to calculate statistics from rides array
    private function calculateRideStatistics($rides)
    {
        if (empty($rides)) {
            return [
                'total_rides' => 0,
                'completed_rides' => 0,
                'cancelled_rides' => 0,
                'completion_rate' => 0,
                'total_earnings' => 0,
                'average_rating' => 0
            ];
        }

        $stats = [
            'total_rides' => count($rides),
            'completed_rides' => 0,
            'cancelled_rides' => 0,
            'total_earnings' => 0,
            'total_rating' => 0,
            'rated_rides' => 0
        ];

        foreach ($rides as $ride) {
            if (($ride['status'] ?? '') === Ride::STATUS_COMPLETED) {
                $stats['completed_rides']++;
            }
            if (($ride['status'] ?? '') === Ride::STATUS_CANCELLED) {
                $stats['cancelled_rides']++;
            }

            // Calculate earnings
            if (!empty($ride['actual_fare'])) {
                $stats['total_earnings'] += (float) $ride['actual_fare'];
            } elseif (!empty($ride['estimated_fare'])) {
                $stats['total_earnings'] += (float) $ride['estimated_fare'];
            }

            // Calculate rating
            if (!empty($ride['driver_rating']) && $ride['driver_rating'] > 0) {
                $stats['total_rating'] += (float) $ride['driver_rating'];
                $stats['rated_rides']++;
            }
        }

        $stats['completion_rate'] = $stats['total_rides'] > 0 
            ? round(($stats['completed_rides'] / $stats['total_rides']) * 100, 2)
            : 0;

        $stats['average_rating'] = $stats['rated_rides'] > 0
            ? round($stats['total_rating'] / $stats['rated_rides'], 2)
            : 0;

        return $stats;
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

    public function scopeAvailable($query)
    {
        return $query->where('availability_status', self::AVAILABILITY_AVAILABLE)
                     ->where('status', self::STATUS_ACTIVE);
    }

    public function scopeNearby($query, $latitude, $longitude, $radius = 10)
    {
        $haversine = "(6371 * acos(cos(radians($latitude)) 
                     * cos(radians(current_location_lat)) 
                     * cos(radians(current_location_lng) 
                     - radians($longitude)) 
                     + sin(radians($latitude)) 
                     * sin(radians(current_location_lat))))";
        
        return $query->selectRaw("*, $haversine AS distance")
                     ->whereRaw("$haversine < ?", [$radius])
                     ->orderBy('distance');
    }

    public function scopeUnsynced($query)
    {
        return $query->where('firebase_synced', false);
    }

    // Accessors & Mutators
    public function getFullNameAttribute()
    {
        return $this->name ?? 'Unknown Driver';
    }

    public function getAgeAttribute()
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    public function getCompletionRateAttribute()
    {
        if ($this->total_rides == 0) return 0;
        return round(($this->completed_rides / $this->total_rides) * 100, 2);
    }

    public function getCancellationRateAttribute()
    {
        if ($this->total_rides == 0) return 0;
        return round(($this->cancelled_rides / $this->total_rides) * 100, 2);
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

    public function isAvailable()
    {
        return $this->availability_status === self::AVAILABILITY_AVAILABLE && $this->isActive();
    }

    public function updateLocation($latitude, $longitude, $address = null)
    {
        $this->update([
            'current_location_lat' => $latitude,
            'current_location_lng' => $longitude,
            'current_address' => $address,
            'last_location_update' => now(),
            'last_active' => now()
        ]);
    }

    // Override toArray for Firebase sync
    public function toFirebaseArray()
    {
        return [
            // Required fields
            'firebase_uid' => $this->firebase_uid ?? '',
            'name' => $this->name ?? '',
            'email' => $this->email ?? '',
            'phone' => $this->phone ?? '',
            
            // Status fields
            'status' => $this->status ?? 'pending',
            'verification_status' => $this->verification_status ?? 'pending',
            'availability_status' => $this->availability_status ?? 'offline',
            
            // Personal information
            'date_of_birth' => $this->date_of_birth ? $this->date_of_birth->format('Y-m-d') : '',
            'gender' => $this->gender ?? '',
            'address' => $this->address ?? '',
            'city' => $this->city ?? '',
            'state' => $this->state ?? '',
            'postal_code' => $this->postal_code ?? '',
            'country' => $this->country ?? '',
            
            // License information
            'license_number' => $this->license_number ?? '',
            'license_expiry' => $this->license_expiry ? $this->license_expiry->format('Y-m-d') : '',
            'license_class' => $this->license_class ?? '',
            'license_type' => $this->license_type ?? '',
            'issuing_state' => $this->issuing_state ?? '',
            
            // Performance metrics
            'rating' => $this->rating ?? 0,
            'total_rides' => $this->total_rides ?? 0,
            'completed_rides' => $this->completed_rides ?? 0,
            'cancelled_rides' => $this->cancelled_rides ?? 0,
            'total_earnings' => $this->total_earnings ?? 0,
            'completion_rate' => $this->completion_rate ?? 0,
            'cancellation_rate' => $this->cancellation_rate ?? 0,
            
            // Location data (if applicable)
            'latitude' => $this->latitude ?? null,
            'longitude' => $this->longitude ?? null,
            'location_address' => $this->location_address ?? '',
            
            // Timestamps
            'join_date' => $this->join_date ?? $this->created_at?->format('Y-m-d'),
            'last_active' => $this->last_active?->toISOString(),
            'verified_at' => $this->verified_at?->toISOString(),
            
            // Firebase metadata
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
            self::STATUS_SUSPENDED => 'Suspended',
            self::STATUS_PENDING => 'Pending'
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

    public static function getAvailabilityStatuses()
    {
        return [
            self::AVAILABILITY_AVAILABLE => 'Available',
            self::AVAILABILITY_BUSY => 'Busy',
            self::AVAILABILITY_OFFLINE => 'Offline'
        ];
    }
}