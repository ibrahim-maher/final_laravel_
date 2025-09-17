<?php

namespace App\Modules\Driver\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\FirebaseSyncable;
use Carbon\Carbon;

class DriverActivity extends Model
{
    use HasFactory, FirebaseSyncable;

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
        'is_read',
        'read_at',
        'archived_at',
        'related_entity_type',
        'related_entity_id',
        'vehicle_id',
        'ride_id',
        'document_id',
        'created_by',
        'firebase_synced',
        'firebase_synced_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'location_latitude' => 'decimal:8',
        'location_longitude' => 'decimal:8',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'archived_at' => 'datetime',
        'firebase_synced' => 'boolean',
        'firebase_synced_at' => 'datetime',
    ];

    // Firebase sync configuration
    protected $firebaseCollection = 'driver_activities';
    protected $firebaseKey = 'id';

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
    const TYPE_DOCUMENT_UPDATE = 'document_update';
    const TYPE_VEHICLE_UPDATE = 'vehicle_update';
    const TYPE_VEHICLE_ADD = 'vehicle_add';
    const TYPE_VEHICLE_REMOVE = 'vehicle_remove';
    const TYPE_PAYMENT_UPDATE = 'payment_update';
    const TYPE_RATING_RECEIVED = 'rating_received';
    const TYPE_EARNINGS_UPDATE = 'earnings_update';
    const TYPE_VERIFICATION_UPDATE = 'verification_update';
    const TYPE_VIOLATION = 'violation';
    const TYPE_SYSTEM_NOTIFICATION = 'system_notification';
    const TYPE_MAINTENANCE_REMINDER = 'maintenance_reminder';
    const TYPE_INSURANCE_EXPIRY = 'insurance_expiry';
    const TYPE_REGISTRATION_EXPIRY = 'registration_expiry';
    const TYPE_LICENSE_EXPIRY = 'license_expiry';

    // Activity category constants
    const CATEGORY_AUTH = 'authentication';
    const CATEGORY_RIDE = 'ride';
    const CATEGORY_PROFILE = 'profile';
    const CATEGORY_VEHICLE = 'vehicle';
    const CATEGORY_PAYMENT = 'payment';
    const CATEGORY_LOCATION = 'location';
    const CATEGORY_SYSTEM = 'system';
    const CATEGORY_SECURITY = 'security';
    const CATEGORY_DOCUMENT = 'document';
    const CATEGORY_VERIFICATION = 'verification';
    const CATEGORY_MAINTENANCE = 'maintenance';
    const CATEGORY_EXPIRY = 'expiry';

    // Priority constants
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_READ = 'read';
    const STATUS_ARCHIVED = 'archived';

    // Relationships
    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_firebase_uid', 'firebase_uid');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function ride()
    {
        return $this->belongsTo(Ride::class, 'ride_id');
    }

    public function document()
    {
        return $this->belongsTo(DriverDocument::class, 'document_id');
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('activity_type', $type);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('activity_category', $category);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
    }

    public function scopeUnsynced($query)
    {
        return $query->where('firebase_synced', false);
    }

    public function scopeForVehicle($query, $vehicleId)
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    public function scopeForRide($query, $rideId)
    {
        return $query->where('ride_id', $rideId);
    }

    public function scopeForDocument($query, $documentId)
    {
        return $query->where('document_id', $documentId);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // Accessors
    public function getPriorityBadgeClassAttribute()
    {
        $classes = [
            self::PRIORITY_LOW => 'badge-secondary',
            self::PRIORITY_NORMAL => 'badge-info',
            self::PRIORITY_HIGH => 'badge-warning',
            self::PRIORITY_URGENT => 'badge-danger'
        ];

        return $classes[$this->priority] ?? 'badge-secondary';
    }

    public function getCategoryBadgeClassAttribute()
    {
        $classes = [
            self::CATEGORY_AUTH => 'badge-primary',
            self::CATEGORY_RIDE => 'badge-success',
            self::CATEGORY_PROFILE => 'badge-info',
            self::CATEGORY_VEHICLE => 'badge-warning',
            self::CATEGORY_PAYMENT => 'badge-success',
            self::CATEGORY_LOCATION => 'badge-secondary',
            self::CATEGORY_SYSTEM => 'badge-dark',
            self::CATEGORY_SECURITY => 'badge-danger',
            self::CATEGORY_DOCUMENT => 'badge-primary',
            self::CATEGORY_VERIFICATION => 'badge-warning',
            self::CATEGORY_MAINTENANCE => 'badge-info',
            self::CATEGORY_EXPIRY => 'badge-danger'
        ];

        return $classes[$this->activity_category] ?? 'badge-secondary';
    }

    public function getStatusBadgeClassAttribute()
    {
        $classes = [
            self::STATUS_ACTIVE => 'badge-primary',
            self::STATUS_READ => 'badge-secondary',
            self::STATUS_ARCHIVED => 'badge-dark'
        ];

        return $classes[$this->status] ?? 'badge-secondary';
    }

    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format('M j, Y g:i A');
    }

    // Helper methods
    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
            'status' => self::STATUS_READ
        ]);
    }

    public function archive()
    {
        $this->update([
            'archived_at' => now(),
            'status' => self::STATUS_ARCHIVED
        ]);
    }

    public function isRead()
    {
        return $this->is_read;
    }

    public function isArchived()
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    public function isHighPriority()
    {
        return in_array($this->priority, [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }

    public function isUrgent()
    {
        return $this->priority === self::PRIORITY_URGENT;
    }

    public function hasLocation()
    {
        return !is_null($this->location_latitude) && !is_null($this->location_longitude);
    }

    public function getLocationString()
    {
        if ($this->location_address) {
            return $this->location_address;
        }

        if ($this->hasLocation()) {
            return "{$this->location_latitude}, {$this->location_longitude}";
        }

        return null;
    }

    // Firebase sync
    public function toFirebaseArray()
    {
        return [
            'id' => $this->id,
            'driver_firebase_uid' => $this->driver_firebase_uid ?? '',
            'activity_type' => $this->activity_type ?? '',
            'activity_category' => $this->activity_category ?? '',
            'title' => $this->title ?? '',
            'description' => $this->description ?? '',
            'metadata' => $this->metadata ?? [],
            'location_latitude' => $this->location_latitude,
            'location_longitude' => $this->location_longitude,
            'location_address' => $this->location_address ?? '',
            'ip_address' => $this->ip_address ?? '',
            'user_agent' => $this->user_agent ?? '',
            'device_type' => $this->device_type ?? '',
            'app_version' => $this->app_version ?? '',
            'status' => $this->status ?? 'active',
            'priority' => $this->priority ?? 'normal',
            'is_read' => $this->is_read ?? false,
            'read_at' => $this->read_at ? $this->read_at->toISOString() : null,
            'archived_at' => $this->archived_at ? $this->archived_at->toISOString() : null,
            'related_entity_type' => $this->related_entity_type ?? '',
            'related_entity_id' => $this->related_entity_id ?? '',
            'vehicle_id' => $this->vehicle_id,
            'ride_id' => $this->ride_id,
            'document_id' => $this->document_id,
            'created_by' => $this->created_by ?? 'system',
            'created_at' => $this->created_at ? $this->created_at->toISOString() : '',
            'updated_at' => $this->updated_at ? $this->updated_at->toISOString() : '',
            'sync_updated_at' => now()->toISOString(),
        ];
    }

    // Static methods
    public static function createActivity($firebaseUid, $type, array $data = [])
    {
        return self::create(array_merge([
            'driver_firebase_uid' => $firebaseUid,
            'activity_type' => $type,
            'activity_category' => self::getCategoryForType($type),
            'title' => $data['title'] ?? self::getDefaultTitle($type),
            'description' => $data['description'] ?? '',
            'priority' => $data['priority'] ?? self::getPriorityForType($type),
            'status' => self::STATUS_ACTIVE,
            'is_read' => false,
            'metadata' => $data['metadata'] ?? [],
            'created_by' => $data['created_by'] ?? 'system'
        ], $data));
    }

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
            self::TYPE_DOCUMENT_UPLOAD => self::CATEGORY_DOCUMENT,
            self::TYPE_DOCUMENT_UPDATE => self::CATEGORY_DOCUMENT,
            self::TYPE_VEHICLE_UPDATE => self::CATEGORY_VEHICLE,
            self::TYPE_VEHICLE_ADD => self::CATEGORY_VEHICLE,
            self::TYPE_VEHICLE_REMOVE => self::CATEGORY_VEHICLE,
            self::TYPE_PAYMENT_UPDATE => self::CATEGORY_PAYMENT,
            self::TYPE_RATING_RECEIVED => self::CATEGORY_RIDE,
            self::TYPE_EARNINGS_UPDATE => self::CATEGORY_PAYMENT,
            self::TYPE_VERIFICATION_UPDATE => self::CATEGORY_VERIFICATION,
            self::TYPE_VIOLATION => self::CATEGORY_SECURITY,
            self::TYPE_SYSTEM_NOTIFICATION => self::CATEGORY_SYSTEM,
            self::TYPE_MAINTENANCE_REMINDER => self::CATEGORY_MAINTENANCE,
            self::TYPE_INSURANCE_EXPIRY => self::CATEGORY_EXPIRY,
            self::TYPE_REGISTRATION_EXPIRY => self::CATEGORY_EXPIRY,
            self::TYPE_LICENSE_EXPIRY => self::CATEGORY_EXPIRY
        ];

        return $categoryMap[$type] ?? self::CATEGORY_SYSTEM;
    }

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
            self::TYPE_DOCUMENT_UPDATE => 'Document Updated',
            self::TYPE_VEHICLE_UPDATE => 'Vehicle Information Updated',
            self::TYPE_VEHICLE_ADD => 'Vehicle Added',
            self::TYPE_VEHICLE_REMOVE => 'Vehicle Removed',
            self::TYPE_PAYMENT_UPDATE => 'Payment Information Updated',
            self::TYPE_RATING_RECEIVED => 'Rating Received',
            self::TYPE_EARNINGS_UPDATE => 'Earnings Updated',
            self::TYPE_VERIFICATION_UPDATE => 'Verification Status Updated',
            self::TYPE_VIOLATION => 'Policy Violation',
            self::TYPE_SYSTEM_NOTIFICATION => 'System Notification',
            self::TYPE_MAINTENANCE_REMINDER => 'Maintenance Reminder',
            self::TYPE_INSURANCE_EXPIRY => 'Insurance Expiry Warning',
            self::TYPE_REGISTRATION_EXPIRY => 'Registration Expiry Warning',
            self::TYPE_LICENSE_EXPIRY => 'License Expiry Warning'
        ];

        return $titles[$type] ?? 'Activity';
    }

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
            self::TYPE_DOCUMENT_UPDATE => self::PRIORITY_NORMAL,
            self::TYPE_VEHICLE_UPDATE => self::PRIORITY_NORMAL,
            self::TYPE_VEHICLE_ADD => self::PRIORITY_NORMAL,
            self::TYPE_VEHICLE_REMOVE => self::PRIORITY_NORMAL,
            self::TYPE_PAYMENT_UPDATE => self::PRIORITY_NORMAL,
            self::TYPE_RATING_RECEIVED => self::PRIORITY_NORMAL,
            self::TYPE_EARNINGS_UPDATE => self::PRIORITY_NORMAL,
            self::TYPE_VERIFICATION_UPDATE => self::PRIORITY_HIGH,
            self::TYPE_VIOLATION => self::PRIORITY_URGENT,
            self::TYPE_SYSTEM_NOTIFICATION => self::PRIORITY_NORMAL,
            self::TYPE_MAINTENANCE_REMINDER => self::PRIORITY_HIGH,
            self::TYPE_INSURANCE_EXPIRY => self::PRIORITY_URGENT,
            self::TYPE_REGISTRATION_EXPIRY => self::PRIORITY_URGENT,
            self::TYPE_LICENSE_EXPIRY => self::PRIORITY_URGENT
        ];

        return $priorities[$type] ?? self::PRIORITY_NORMAL;
    }

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
            self::TYPE_DOCUMENT_UPDATE => 'Document Update',
            self::TYPE_VEHICLE_UPDATE => 'Vehicle Update',
            self::TYPE_VEHICLE_ADD => 'Vehicle Added',
            self::TYPE_VEHICLE_REMOVE => 'Vehicle Removed',
            self::TYPE_PAYMENT_UPDATE => 'Payment Update',
            self::TYPE_RATING_RECEIVED => 'Rating Received',
            self::TYPE_EARNINGS_UPDATE => 'Earnings Update',
            self::TYPE_VERIFICATION_UPDATE => 'Verification Update',
            self::TYPE_VIOLATION => 'Violation',
            self::TYPE_SYSTEM_NOTIFICATION => 'System Notification',
            self::TYPE_MAINTENANCE_REMINDER => 'Maintenance Reminder',
            self::TYPE_INSURANCE_EXPIRY => 'Insurance Expiry',
            self::TYPE_REGISTRATION_EXPIRY => 'Registration Expiry',
            self::TYPE_LICENSE_EXPIRY => 'License Expiry'
        ];
    }

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
            self::CATEGORY_SECURITY => 'Security',
            self::CATEGORY_DOCUMENT => 'Documents',
            self::CATEGORY_VERIFICATION => 'Verification',
            self::CATEGORY_MAINTENANCE => 'Maintenance',
            self::CATEGORY_EXPIRY => 'Expiry Alerts'
        ];
    }

    public static function getPriorities()
    {
        return [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_URGENT => 'Urgent'
        ];
    }

    public static function getStatuses()
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_READ => 'Read',
            self::STATUS_ARCHIVED => 'Archived'
        ];
    }

    // Bulk operations
    public static function markMultipleAsRead(array $activityIds)
    {
        return self::whereIn('id', $activityIds)->update([
            'is_read' => true,
            'read_at' => now(),
            'status' => self::STATUS_READ
        ]);
    }

    public static function archiveMultiple(array $activityIds)
    {
        return self::whereIn('id', $activityIds)->update([
            'archived_at' => now(),
            'status' => self::STATUS_ARCHIVED
        ]);
    }

    // Cleanup old activities
    public static function cleanupOldActivities($daysToKeep = 90)
    {
        $cutoffDate = now()->subDays($daysToKeep);
        
        return self::where('created_at', '<', $cutoffDate)
                   ->where('priority', '!=', self::PRIORITY_URGENT)
                   ->delete();
    }
}