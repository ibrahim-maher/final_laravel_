<?php

namespace App\Modules\Driver\Models;

use Carbon\Carbon;

class DriverActivity
{
    // Activity data fields
    protected $attributes = [];
    
    protected $fillable = [
        'driver_firebase_uid',
        'activity_type',
        'activity_category',
        'title',
        'description',
        'metadata',
        'location_latitude',
        'location_longitude',
        'location_address',
        'ip_address',
        'user_agent',
        'device_type',
        'app_version',
        'status',
        'priority',
        'read_at',
        'archived_at',
        'related_entity_type',
        'related_entity_id',
        'created_at',
        'updated_at'
    ];

    // Activity type constants
    const TYPE_LOGIN = 'login';
    const TYPE_LOGOUT = 'logout';
    const TYPE_STATUS_CHANGE = 'status_change';
    const TYPE_LOCATION_UPDATE = 'location_update';
    const TYPE_RIDE_REQUEST = 'ride_request';
    const TYPE_RIDE_ACCEPT = 'ride_accept';
    const TYPE_RIDE_DECLINE = 'ride_decline';
    const TYPE_RIDE_START = 'ride_start';
    const TYPE_RIDE_COMPLETE = 'ride_complete';
    const TYPE_RIDE_CANCEL = 'ride_cancel';
    const TYPE_PROFILE_UPDATE = 'profile_update';
    const TYPE_DOCUMENT_UPLOAD = 'document_upload';
    const TYPE_VEHICLE_UPDATE = 'vehicle_update';
    const TYPE_PAYMENT_UPDATE = 'payment_update';
    const TYPE_RATING_RECEIVED = 'rating_received';
    const TYPE_EARNINGS_UPDATE = 'earnings_update';
    const TYPE_VIOLATION = 'violation';
    const TYPE_SYSTEM_NOTIFICATION = 'system_notification';

    // Activity category constants
    const CATEGORY_AUTH = 'authentication';
    const CATEGORY_RIDE = 'ride';
    const CATEGORY_PROFILE = 'profile';
    const CATEGORY_VEHICLE = 'vehicle';
    const CATEGORY_PAYMENT = 'payment';
    const CATEGORY_LOCATION = 'location';
    const CATEGORY_SYSTEM = 'system';
    const CATEGORY_SECURITY = 'security';

    // Priority constants
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_READ = 'read';
    const STATUS_ARCHIVED = 'archived';

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
     * Check if activity is read
     */
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    /**
     * Check if activity is archived
     */
    public function isArchived(): bool
    {
        return !is_null($this->archived_at);
    }

    /**
     * Mark activity as read
     */
    public function markAsRead(): void
    {
        $this->read_at = now()->toISOString();
        $this->status = self::STATUS_READ;
        $this->updated_at = now()->toISOString();
    }

    /**
     * Archive activity
     */
    public function archive(): void
    {
        $this->archived_at = now()->toISOString();
        $this->status = self::STATUS_ARCHIVED;
        $this->updated_at = now()->toISOString();
    }

    /**
     * Get activity age in human readable format
     */
    public function getAgeAttribute(): string
    {
        try {
            return Carbon::parse($this->created_at)->diffForHumans();
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    public static function createActivity(string $firebaseUid, string $type, array $data): ?self
    {
        try {
            // Initialize FirestoreService
            $firestoreService = app(FirestoreService::class);

            // Prepare activity data
            $activityData = [
                'driver_firebase_uid' => $firebaseUid,
                'activity_type' => $type,
                'activity_category' => self::getCategoryForType($type),
                'title' => $data['title'] ?? self::getDefaultTitle($type),
                'description' => $data['description'] ?? '',
                'metadata' => $data['metadata'] ?? [],
                'priority' => $data['priority'] ?? self::getPriorityForType($type),
                'status' => self::STATUS_ACTIVE,
                'created_at' => now()->toISOString(),
                'updated_at' => now()->toISOString(),
            ];

            // Optional fields from $data
            if (isset($data['location_latitude'])) {
                $activityData['location_latitude'] = (float) $data['location_latitude'];
            }
            if (isset($data['location_longitude'])) {
                $activityData['location_longitude'] = (float) $data['location_longitude'];
            }
            if (isset($data['location_address'])) {
                $activityData['location_address'] = $data['location_address'];
            }
            if (isset($data['ip_address'])) {
                $activityData['ip_address'] = $data['ip_address'];
            }
            if (isset($data['user_agent'])) {
                $activityData['user_agent'] = $data['user_agent'];
            }
            if (isset($data['device_type'])) {
                $activityData['device_type'] = $data['device_type'];
            }
            if (isset($data['app_version'])) {
                $activityData['app_version'] = $data['app_version'];
            }
            if (isset($data['related_entity_type'])) {
                $activityData['related_entity_type'] = $data['related_entity_type'];
            }
            if (isset($data['related_entity_id'])) {
                $activityData['related_entity_id'] = $data['related_entity_id'];
            }

            // Save to Firestore (assuming a 'driver_activities' collection)
            $firestoreService->createDocument("driver_activities/{$firebaseUid}/activities", $activityData);

            // Return a new DriverActivity instance
            return new self($activityData);
        } catch (\Exception $e) {
            \Log::error('Failed to create driver activity', [
                'firebase_uid' => $firebaseUid,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
    /**
     * Get priority badge color
     */
    public function getPriorityColorAttribute(): string
    {
        $colors = [
            self::PRIORITY_LOW => 'secondary',
            self::PRIORITY_NORMAL => 'primary',
            self::PRIORITY_HIGH => 'warning',
            self::PRIORITY_URGENT => 'danger'
        ];

        return $colors[$this->priority ?? self::PRIORITY_NORMAL] ?? 'secondary';
    }

    /**
     * Get category badge color
     */
    public function getCategoryColorAttribute(): string
    {
        $colors = [
            self::CATEGORY_AUTH => 'info',
            self::CATEGORY_RIDE => 'success',
            self::CATEGORY_PROFILE => 'primary',
            self::CATEGORY_VEHICLE => 'warning',
            self::CATEGORY_PAYMENT => 'success',
            self::CATEGORY_LOCATION => 'info',
            self::CATEGORY_SYSTEM => 'secondary',
            self::CATEGORY_SECURITY => 'danger'
        ];

        return $colors[$this->activity_category ?? self::CATEGORY_SYSTEM] ?? 'secondary';
    }

    /**
     * Get activity icon based on type
     */
    public function getIconAttribute(): string
    {
        $icons = [
            self::TYPE_LOGIN => 'fas fa-sign-in-alt',
            self::TYPE_LOGOUT => 'fas fa-sign-out-alt',
            self::TYPE_STATUS_CHANGE => 'fas fa-toggle-on',
            self::TYPE_LOCATION_UPDATE => 'fas fa-map-marker-alt',
            self::TYPE_RIDE_REQUEST => 'fas fa-bell',
            self::TYPE_RIDE_ACCEPT => 'fas fa-check',
            self::TYPE_RIDE_DECLINE => 'fas fa-times',
            self::TYPE_RIDE_START => 'fas fa-play',
            self::TYPE_RIDE_COMPLETE => 'fas fa-flag-checkered',
            self::TYPE_RIDE_CANCEL => 'fas fa-ban',
            self::TYPE_PROFILE_UPDATE => 'fas fa-user-edit',
            self::TYPE_DOCUMENT_UPLOAD => 'fas fa-file-upload',
            self::TYPE_VEHICLE_UPDATE => 'fas fa-car',
            self::TYPE_PAYMENT_UPDATE => 'fas fa-credit-card',
            self::TYPE_RATING_RECEIVED => 'fas fa-star',
            self::TYPE_EARNINGS_UPDATE => 'fas fa-dollar-sign',
            self::TYPE_VIOLATION => 'fas fa-exclamation-triangle',
            self::TYPE_SYSTEM_NOTIFICATION => 'fas fa-info-circle'
        ];

        return $icons[$this->activity_type ?? ''] ?? 'fas fa-circle';
    }

    /**
     * Get category based on activity type
     */
    public static function getCategoryForType(string $type): string
    {
        $categoryMap = [
            self::TYPE_LOGIN => self::CATEGORY_AUTH,
            self::TYPE_LOGOUT => self::CATEGORY_AUTH,
            self::TYPE_STATUS_CHANGE => self::CATEGORY_PROFILE,
            self::TYPE_LOCATION_UPDATE => self::CATEGORY_LOCATION,
            self::TYPE_RIDE_REQUEST => self::CATEGORY_RIDE,
            self::TYPE_RIDE_ACCEPT => self::CATEGORY_RIDE,
            self::TYPE_RIDE_DECLINE => self::CATEGORY_RIDE,
            self::TYPE_RIDE_START => self::CATEGORY_RIDE,
            self::TYPE_RIDE_COMPLETE => self::CATEGORY_RIDE,
            self::TYPE_RIDE_CANCEL => self::CATEGORY_RIDE,
            self::TYPE_PROFILE_UPDATE => self::CATEGORY_PROFILE,
            self::TYPE_DOCUMENT_UPLOAD => self::CATEGORY_PROFILE,
            self::TYPE_VEHICLE_UPDATE => self::CATEGORY_VEHICLE,
            self::TYPE_PAYMENT_UPDATE => self::CATEGORY_PAYMENT,
            self::TYPE_RATING_RECEIVED => self::CATEGORY_RIDE,
            self::TYPE_EARNINGS_UPDATE => self::CATEGORY_PAYMENT,
            self::TYPE_VIOLATION => self::CATEGORY_SECURITY,
            self::TYPE_SYSTEM_NOTIFICATION => self::CATEGORY_SYSTEM
        ];

        return $categoryMap[$type] ?? self::CATEGORY_SYSTEM;
    }

    /**
     * Get default title for activity type
     */
    public static function getDefaultTitle(string $type): string
    {
        $titles = [
            self::TYPE_LOGIN => 'Driver Logged In',
            self::TYPE_LOGOUT => 'Driver Logged Out',
            self::TYPE_STATUS_CHANGE => 'Status Changed',
            self::TYPE_LOCATION_UPDATE => 'Location Updated',
            self::TYPE_RIDE_REQUEST => 'Ride Request Received',
            self::TYPE_RIDE_ACCEPT => 'Ride Accepted',
            self::TYPE_RIDE_DECLINE => 'Ride Declined',
            self::TYPE_RIDE_START => 'Ride Started',
            self::TYPE_RIDE_COMPLETE => 'Ride Completed',
            self::TYPE_RIDE_CANCEL => 'Ride Cancelled',
            self::TYPE_PROFILE_UPDATE => 'Profile Updated',
            self::TYPE_DOCUMENT_UPLOAD => 'Document Uploaded',
            self::TYPE_VEHICLE_UPDATE => 'Vehicle Information Updated',
            self::TYPE_PAYMENT_UPDATE => 'Payment Information Updated',
            self::TYPE_RATING_RECEIVED => 'Rating Received',
            self::TYPE_EARNINGS_UPDATE => 'Earnings Updated',
            self::TYPE_VIOLATION => 'Policy Violation',
            self::TYPE_SYSTEM_NOTIFICATION => 'System Notification'
        ];

        return $titles[$type] ?? 'Activity';
    }

    /**
     * Get priority for activity type
     */
    public static function getPriorityForType(string $type): string
    {
        $priorities = [
            self::TYPE_LOGIN => self::PRIORITY_LOW,
            self::TYPE_LOGOUT => self::PRIORITY_LOW,
            self::TYPE_STATUS_CHANGE => self::PRIORITY_NORMAL,
            self::TYPE_LOCATION_UPDATE => self::PRIORITY_LOW,
            self::TYPE_RIDE_REQUEST => self::PRIORITY_HIGH,
            self::TYPE_RIDE_ACCEPT => self::PRIORITY_HIGH,
            self::TYPE_RIDE_DECLINE => self::PRIORITY_NORMAL,
            self::TYPE_RIDE_START => self::PRIORITY_HIGH,
            self::TYPE_RIDE_COMPLETE => self::PRIORITY_HIGH,
            self::TYPE_RIDE_CANCEL => self::PRIORITY_HIGH,
            self::TYPE_PROFILE_UPDATE => self::PRIORITY_NORMAL,
            self::TYPE_DOCUMENT_UPLOAD => self::PRIORITY_NORMAL,
            self::TYPE_VEHICLE_UPDATE => self::PRIORITY_NORMAL,
            self::TYPE_PAYMENT_UPDATE => self::PRIORITY_NORMAL,
            self::TYPE_RATING_RECEIVED => self::PRIORITY_NORMAL,
            self::TYPE_EARNINGS_UPDATE => self::PRIORITY_NORMAL,
            self::TYPE_VIOLATION => self::PRIORITY_URGENT,
            self::TYPE_SYSTEM_NOTIFICATION => self::PRIORITY_NORMAL
        ];

        return $priorities[$type] ?? self::PRIORITY_NORMAL;
    }

    /**
     * Get available activity types
     */
    public static function getActivityTypes(): array
    {
        return [
            self::TYPE_LOGIN => 'Login',
            self::TYPE_LOGOUT => 'Logout',
            self::TYPE_STATUS_CHANGE => 'Status Change',
            self::TYPE_LOCATION_UPDATE => 'Location Update',
            self::TYPE_RIDE_REQUEST => 'Ride Request',
            self::TYPE_RIDE_ACCEPT => 'Ride Accept',
            self::TYPE_RIDE_DECLINE => 'Ride Decline',
            self::TYPE_RIDE_START => 'Ride Start',
            self::TYPE_RIDE_COMPLETE => 'Ride Complete',
            self::TYPE_RIDE_CANCEL => 'Ride Cancel',
            self::TYPE_PROFILE_UPDATE => 'Profile Update',
            self::TYPE_DOCUMENT_UPLOAD => 'Document Upload',
            self::TYPE_VEHICLE_UPDATE => 'Vehicle Update',
            self::TYPE_PAYMENT_UPDATE => 'Payment Update',
            self::TYPE_RATING_RECEIVED => 'Rating Received',
            self::TYPE_EARNINGS_UPDATE => 'Earnings Update',
            self::TYPE_VIOLATION => 'Violation',
            self::TYPE_SYSTEM_NOTIFICATION => 'System Notification'
        ];
    }

    /**
     * Get available categories
     */
    public static function getCategories(): array
    {
        return [
            self::CATEGORY_AUTH => 'Authentication',
            self::CATEGORY_RIDE => 'Rides',
            self::CATEGORY_PROFILE => 'Profile',
            self::CATEGORY_VEHICLE => 'Vehicle',
            self::CATEGORY_PAYMENT => 'Payment',
            self::CATEGORY_LOCATION => 'Location',
            self::CATEGORY_SYSTEM => 'System',
            self::CATEGORY_SECURITY => 'Security'
        ];
    }

    /**
     * Get available priorities
     */
    public static function getPriorities(): array
    {
        return [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_URGENT => 'Urgent'
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
     * Prepare data for Firestore storage
     */
    public function prepareForFirestore(): array
    {
        $data = $this->attributes;
        
        // Ensure metadata is an object/array
        if (isset($data['metadata']) && !is_array($data['metadata'])) {
            $data['metadata'] = [];
        }
        
        // Convert numeric values
        if (isset($data['location_latitude'])) {
            $data['location_latitude'] = (float) $data['location_latitude'];
        }
        
        if (isset($data['location_longitude'])) {
            $data['location_longitude'] = (float) $data['location_longitude'];
        }
        
        // Ensure required fields have defaults
        $data['status'] = $data['status'] ?? self::STATUS_ACTIVE;
        $data['priority'] = $data['priority'] ?? self::PRIORITY_NORMAL;
        $data['activity_category'] = $data['activity_category'] ?? self::getCategoryForType($data['activity_type'] ?? '');
        $data['metadata'] = $data['metadata'] ?? [];
        
        // Set timestamps
        if (!isset($data['created_at'])) {
            $data['created_at'] = now()->toISOString();
        }
        $data['updated_at'] = now()->toISOString();
        
        return $data;
    }
}