<?php

namespace App\Modules\Driver\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DriverActivity extends Model
{
    protected $collection = 'driver_activities';
    
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

    protected $dates = [
        'read_at',
        'archived_at',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'location_latitude' => 'decimal:8',
        'location_longitude' => 'decimal:8',
        'read_at' => 'datetime',
        'archived_at' => 'datetime'
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
     * Get the driver this activity belongs to
     */
    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_firebase_uid', 'firebase_uid');
    }

    /**
     * Check if activity is read
     */
    public function isRead()
    {
        return !is_null($this->read_at);
    }

    /**
     * Check if activity is archived
     */
    public function isArchived()
    {
        return !is_null($this->archived_at);
    }

    /**
     * Mark activity as read
     */
    public function markAsRead()
    {
        $this->read_at = now();
        $this->status = self::STATUS_READ;
        $this->save();
    }

    /**
     * Archive activity
     */
    public function archive()
    {
        $this->archived_at = now();
        $this->status = self::STATUS_ARCHIVED;
        $this->save();
    }

    /**
     * Get activity age in human readable format
     */
    public function getAgeAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get priority badge color
     */
    public function getPriorityColorAttribute()
    {
        $colors = [
            self::PRIORITY_LOW => 'secondary',
            self::PRIORITY_NORMAL => 'primary',
            self::PRIORITY_HIGH => 'warning',
            self::PRIORITY_URGENT => 'danger'
        ];

        return $colors[$this->priority] ?? 'secondary';
    }

    /**
     * Get category badge color
     */
    public function getCategoryColorAttribute()
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

        return $colors[$this->activity_category] ?? 'secondary';
    }

    /**
     * Get activity icon based on type
     */
    public function getIconAttribute()
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

        return $icons[$this->activity_type] ?? 'fas fa-circle';
    }

    /**
     * Create a new activity record
     */
    public static function createActivity($driverFirebaseUid, $type, $data = [])
    {
        $activity = new static();
        $activity->driver_firebase_uid = $driverFirebaseUid;
        $activity->activity_type = $type;
        $activity->activity_category = self::getCategoryForType($type);
        $activity->title = $data['title'] ?? self::getDefaultTitle($type);
        $activity->description = $data['description'] ?? null;
        $activity->metadata = $data['metadata'] ?? [];
        $activity->location_latitude = $data['location_latitude'] ?? null;
        $activity->location_longitude = $data['location_longitude'] ?? null;
        $activity->location_address = $data['location_address'] ?? null;
        $activity->ip_address = $data['ip_address'] ?? request()->ip();
        $activity->user_agent = $data['user_agent'] ?? request()->userAgent();
        $activity->device_type = $data['device_type'] ?? self::detectDeviceType();
        $activity->app_version = $data['app_version'] ?? null;
        $activity->status = self::STATUS_ACTIVE;
        $activity->priority = $data['priority'] ?? self::getPriorityForType($type);
        $activity->related_entity_type = $data['related_entity_type'] ?? null;
        $activity->related_entity_id = $data['related_entity_id'] ?? null;
        $activity->save();

        return $activity;
    }

    /**
     * Get category based on activity type
     */
    public static function getCategoryForType($type)
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
    public static function getDefaultTitle($type)
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
    public static function getPriorityForType($type)
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
     * Detect device type from user agent
     */
    public static function detectDeviceType($userAgent = null)
    {
        $userAgent = $userAgent ?? request()->userAgent();
        
        if (preg_match('/mobile|android|iphone|ipod|blackberry|windows phone/i', $userAgent)) {
            return 'mobile';
        } elseif (preg_match('/tablet|ipad/i', $userAgent)) {
            return 'tablet';
        }
        
        return 'desktop';
    }

    /**
     * Scope: Unread activities
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope: Read activities
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope: Archived activities
     */
    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    /**
     * Scope: Active (not archived) activities
     */
    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }

    /**
     * Scope: By category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('activity_category', $category);
    }

    /**
     * Scope: By type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('activity_type', $type);
    }

    /**
     * Scope: By priority
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope: High priority activities
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }

    /**
     * Scope: Today's activities
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope: Recent activities (last 24 hours)
     */
    public function scopeRecent($query)
    {
        return $query->where('created_at', '>=', now()->subDay());
    }

    /**
     * Scope: Search activities
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%")
              ->orWhere('activity_type', 'like', "%{$term}%");
        });
    }

    /**
     * Get available activity types
     */
    public static function getActivityTypes()
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
    public static function getCategories()
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
    public static function getPriorities()
    {
        return [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_URGENT => 'Urgent'
        ];
    }
}