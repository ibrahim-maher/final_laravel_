<?php

namespace App\Modules\Driver\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VehicleActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'driver_firebase_uid',
        'activity_type',
        'title',
        'description',
        'metadata',
        'priority',
        'is_read',
        'created_by'
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_read' => 'boolean',
    ];

    // Activity type constants
    const TYPE_VEHICLE_UPDATE = 'vehicle_update';
    const TYPE_STATUS_CHANGE = 'status_change';
    const TYPE_VERIFICATION_UPDATE = 'verification_update';
    const TYPE_MAINTENANCE = 'maintenance';
    const TYPE_DOCUMENT_UPDATE = 'document_update';
    const TYPE_RIDE_COMPLETED = 'ride_completed';
    const TYPE_INSURANCE_EXPIRY = 'insurance_expiry';
    const TYPE_REGISTRATION_EXPIRY = 'registration_expiry';
    const TYPE_INSPECTION_DUE = 'inspection_due';

    // Priority constants
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    // Relationships
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_firebase_uid', 'firebase_uid');
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('activity_type', $type);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
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

    // Helper methods
    public function markAsRead()
    {
        $this->update(['is_read' => true]);
    }

    public function isUrgent()
    {
        return $this->priority === self::PRIORITY_URGENT;
    }

    public function isHigh()
    {
        return $this->priority === self::PRIORITY_HIGH;
    }

    // Static methods
    public static function createActivity($vehicleId, $type, array $data = [])
    {
        $vehicle = Vehicle::find($vehicleId);
        
        if (!$vehicle) {
            return null;
        }

        return self::create([
            'vehicle_id' => $vehicleId,
            'driver_firebase_uid' => $vehicle->driver_firebase_uid,
            'activity_type' => $type,
            'title' => $data['title'] ?? 'Vehicle Activity',
            'description' => $data['description'] ?? '',
            'metadata' => $data['metadata'] ?? [],
            'priority' => $data['priority'] ?? self::PRIORITY_NORMAL,
            'created_by' => $data['created_by'] ?? 'system'
        ]);
    }

    public static function getActivityTypes()
    {
        return [
            self::TYPE_VEHICLE_UPDATE => 'Vehicle Update',
            self::TYPE_STATUS_CHANGE => 'Status Change',
            self::TYPE_VERIFICATION_UPDATE => 'Verification Update',
            self::TYPE_MAINTENANCE => 'Maintenance',
            self::TYPE_DOCUMENT_UPDATE => 'Document Update',
            self::TYPE_RIDE_COMPLETED => 'Ride Completed',
            self::TYPE_INSURANCE_EXPIRY => 'Insurance Expiry',
            self::TYPE_REGISTRATION_EXPIRY => 'Registration Expiry',
            self::TYPE_INSPECTION_DUE => 'Inspection Due'
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
}