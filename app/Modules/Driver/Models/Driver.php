<?php

namespace App\Modules\Driver\Models;

use Carbon\Carbon;

class Driver
{
    // Driver data fields
    protected $attributes = [];
    
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
     * Constructor
     */
    public function __construct(array $data = [])
    {
        $this->attributes = $data;
    }

    /**
     * Get attribute value
     */
    public function __get($key)
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Set attribute value
     */
    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Check if attribute exists
     */
    public function __isset($key)
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * Create instance from Firestore data
     */
    public static function fromFirestore(array $data): self
    {
        return new static($data);
    }

    /**
     * Check if driver is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if driver is verified
     */
    public function isVerified(): bool
    {
        return $this->verification_status === self::VERIFICATION_VERIFIED;
    }

    /**
     * Check if driver is available
     */
    public function isAvailable(): bool
    {
        return $this->availability_status === self::AVAILABILITY_AVAILABLE && $this->isActive();
    }

    /**
     * Get driver's full name
     */
    public function getFullNameAttribute(): string
    {
        return $this->name ?? 'Unknown Driver';
    }

    /**
     * Get driver's age
     */
    public function getAgeAttribute(): ?int
    {
        if (!$this->date_of_birth) {
            return null;
        }
        
        return Carbon::parse($this->date_of_birth)->age;
    }

    /**
     * Get completion rate
     */
    public function getCompletionRateAttribute(): float
    {
        if (($this->total_rides ?? 0) == 0) {
            return 0;
        }
        
        return round((($this->completed_rides ?? 0) / $this->total_rides) * 100, 2);
    }

    /**
     * Get cancellation rate
     */
    public function getCancellationRateAttribute(): float
    {
        if (($this->total_rides ?? 0) == 0) {
            return 0;
        }
        
        return round((($this->cancelled_rides ?? 0) / $this->total_rides) * 100, 2);
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        $colors = [
            self::STATUS_ACTIVE => 'success',
            self::STATUS_INACTIVE => 'secondary',
            self::STATUS_SUSPENDED => 'danger',
            self::STATUS_PENDING => 'warning'
        ];

        return $colors[$this->status ?? self::STATUS_PENDING] ?? 'secondary';
    }

    /**
     * Get verification status badge color
     */
    public function getVerificationColorAttribute(): string
    {
        $colors = [
            self::VERIFICATION_VERIFIED => 'success',
            self::VERIFICATION_PENDING => 'warning',
            self::VERIFICATION_REJECTED => 'danger'
        ];

        return $colors[$this->verification_status ?? self::VERIFICATION_PENDING] ?? 'secondary';
    }

    /**
     * Get available driver statuses
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_SUSPENDED => 'Suspended',
            self::STATUS_PENDING => 'Pending'
        ];
    }

    /**
     * Get available verification statuses
     */
    public static function getVerificationStatuses(): array
    {
        return [
            self::VERIFICATION_PENDING => 'Pending',
            self::VERIFICATION_VERIFIED => 'Verified',
            self::VERIFICATION_REJECTED => 'Rejected'
        ];
    }

    /**
     * Get available availability statuses
     */
    public static function getAvailabilityStatuses(): array
    {
        return [
            self::AVAILABILITY_AVAILABLE => 'Available',
            self::AVAILABILITY_BUSY => 'Busy',
            self::AVAILABILITY_OFFLINE => 'Offline'
        ];
    }

    /**
     * Format date attributes
     */
    public function getFormattedDate(string $attribute): ?string
    {
        $value = $this->attributes[$attribute] ?? null;
        if (!$value) return null;

        try {
            return Carbon::parse($value)->format('M d, Y');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Format datetime attributes
     */
    public function getFormattedDateTime(string $attribute): ?string
    {
        $value = $this->attributes[$attribute] ?? null;
        if (!$value) return null;

        try {
            return Carbon::parse($value)->format('M d, Y H:i');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get time ago format for attributes
     */
    public function getTimeAgo(string $attribute): ?string
    {
        $value = $this->attributes[$attribute] ?? null;
        if (!$value) return null;

        try {
            return Carbon::parse($value)->diffForHumans();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if license is expired
     */
    public function isLicenseExpired(): bool
    {
        if (!$this->license_expiry) return false;

        try {
            return Carbon::parse($this->license_expiry)->isPast();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if license expires soon (within 30 days)
     */
    public function licenseExpiresSoon(int $days = 30): bool
    {
        if (!$this->license_expiry) return false;

        try {
            return Carbon::parse($this->license_expiry)->isBefore(now()->addDays($days));
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get formatted total earnings
     */
    public function getFormattedEarnings(): string
    {
        return '' . number_format($this->total_earnings ?? 0, 2);
    }

    /**
     * Get formatted rating
     */
    public function getFormattedRating(): string
    {
        $rating = $this->rating ?? 0;
        return number_format($rating, 1) . '/5.0';
    }

    /**
     * Check if driver has valid location
     */
    public function hasValidLocation(): bool
    {
        return !empty($this->current_location_lat) && !empty($this->current_location_lng);
    }

    /**
     * Get distance from a given location (in km)
     */
    public function getDistanceFrom(float $latitude, float $longitude): ?float
    {
        if (!$this->hasValidLocation()) {
            return null;
        }

        $earthRadius = 6371; // km

        $dLat = deg2rad($latitude - $this->current_location_lat);
        $dLng = deg2rad($longitude - $this->current_location_lng);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($this->current_location_lat)) * cos(deg2rad($latitude)) *
             sin($dLng/2) * sin($dLng/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $earthRadius * $c;
    }

    /**
     * Update driver location
     */
    public function updateLocation(float $latitude, float $longitude, string $address = null): void
    {
        $this->current_location_lat = $latitude;
        $this->current_location_lng = $longitude;
        $this->last_location_update = now()->toISOString();
        
        if ($address) {
            $this->current_address = $address;
        }
        
        $this->updateLastActive();
    }

    /**
     * Update last active timestamp
     */
    public function updateLastActive(): void
    {
        $this->last_active = now()->toISOString();
        $this->updated_at = now()->toISOString();
    }

    /**
     * Validate driver data
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->firebase_uid)) {
            $errors[] = 'Firebase UID is required';
        }

        if (empty($this->name)) {
            $errors[] = 'Name is required';
        }

        if (empty($this->email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email must be a valid email address';
        }

        if (empty($this->license_number)) {
            $errors[] = 'License number is required';
        }

        if ($this->license_expiry && Carbon::parse($this->license_expiry)->isPast()) {
            $errors[] = 'License expiry date must be in the future';
        }

        if ($this->date_of_birth) {
            $age = Carbon::parse($this->date_of_birth)->age;
            if ($age < 18) {
                $errors[] = 'Driver must be at least 18 years old';
            }
        }

        return $errors;
    }

    /**
     * Prepare data for Firestore storage
     */
    public function prepareForFirestore(): array
    {
        $data = $this->attributes;
        
        // Convert numeric values
        if (isset($data['rating'])) {
            $data['rating'] = (float) $data['rating'];
        }
        
        if (isset($data['total_rides'])) {
            $data['total_rides'] = (int) $data['total_rides'];
        }
        
        if (isset($data['completed_rides'])) {
            $data['completed_rides'] = (int) $data['completed_rides'];
        }
        
        if (isset($data['cancelled_rides'])) {
            $data['cancelled_rides'] = (int) $data['cancelled_rides'];
        }
        
        if (isset($data['total_earnings'])) {
            $data['total_earnings'] = (float) $data['total_earnings'];
        }
        
        if (isset($data['current_location_lat'])) {
            $data['current_location_lat'] = (float) $data['current_location_lat'];
        }
        
        if (isset($data['current_location_lng'])) {
            $data['current_location_lng'] = (float) $data['current_location_lng'];
        }
        
        // Ensure required fields have defaults
        $data['status'] = $data['status'] ?? self::STATUS_PENDING;
        $data['verification_status'] = $data['verification_status'] ?? self::VERIFICATION_PENDING;
        $data['availability_status'] = $data['availability_status'] ?? self::AVAILABILITY_OFFLINE;
        $data['rating'] = $data['rating'] ?? 5.0;
        $data['total_rides'] = $data['total_rides'] ?? 0;
        $data['completed_rides'] = $data['completed_rides'] ?? 0;
        $data['cancelled_rides'] = $data['cancelled_rides'] ?? 0;
        $data['total_earnings'] = $data['total_earnings'] ?? 0.0;
        
        // Set timestamps
        if (!isset($data['created_at'])) {
            $data['created_at'] = now()->toISOString();
        }
        $data['updated_at'] = now()->toISOString();
        
        return $data;
    }
}