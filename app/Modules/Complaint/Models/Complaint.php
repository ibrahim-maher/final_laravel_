<?php
// app/Modules/Complaint/Models/Complaint.php

namespace App\Modules\Complaint\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Complaint extends Model
{
    use HasFactory;

    // Remove 'id' from fillable - Laravel manages this automatically
    protected $fillable = [
        'order_type',
        'driver_name',
        'user_name',
        'title',
        'complaint_by',
        'created_at',
        'status',
        'description',
        'priority',
        'category',
        'resolved_at',
        'resolved_by',
        'admin_notes',
        'contact_info',
        'order_id',
        'driver_id',
        'user_id'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    // Tell Laravel not to use auto-incrementing IDs since we're using Firestore IDs
    public $incrementing = false;
    protected $keyType = 'string';

    // Constants for status
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_CLOSED = 'closed';

    // Constants for priority
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    // Constants for order type
    const ORDER_TYPE_RIDE = 'ride';
    const ORDER_TYPE_DELIVERY = 'delivery';

    // Accessors
    public function getStatusBadgeAttribute()
    {
        $badges = [
            self::STATUS_PENDING => 'bg-yellow-100 text-yellow-800',
            self::STATUS_IN_PROGRESS => 'bg-blue-100 text-blue-800',
            self::STATUS_RESOLVED => 'bg-green-100 text-green-800',
            self::STATUS_CLOSED => 'bg-gray-100 text-gray-800'
        ];

        return $badges[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    public function getPriorityBadgeAttribute()
    {
        $badges = [
            self::PRIORITY_LOW => 'bg-gray-100 text-gray-800',
            self::PRIORITY_MEDIUM => 'bg-yellow-100 text-yellow-800',
            self::PRIORITY_HIGH => 'bg-orange-100 text-orange-800',
            self::PRIORITY_URGENT => 'bg-red-100 text-red-800'
        ];

        return $badges[$this->priority] ?? 'bg-gray-100 text-gray-800';
    }

    public function getTimeAgoAttribute()
    {
        return $this->created_at ? $this->created_at->diffForHumans() : '';
    }

    public function getIsOverdueAttribute()
    {
        if (!$this->created_at || in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED])) {
            return false;
        }

        $overdueHours = [
            self::PRIORITY_URGENT => 2,
            self::PRIORITY_HIGH => 8,
            self::PRIORITY_MEDIUM => 24,
            self::PRIORITY_LOW => 72
        ];

        $maxHours = $overdueHours[$this->priority] ?? 24;
        return $this->created_at->diffInHours(now()) > $maxHours;
    }

    // Static methods
    public static function getStatuses()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_CLOSED => 'Closed'
        ];
    }

    public static function getPriorities()
    {
        return [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_MEDIUM => 'Medium',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_URGENT => 'Urgent'
        ];
    }

    public static function getOrderTypes()
    {
        return [
            self::ORDER_TYPE_RIDE => 'Ride',
            self::ORDER_TYPE_DELIVERY => 'Delivery'
        ];
    }

    public static function getCategories()
    {
        return [
            'service_quality' => 'Service Quality',
            'payment_issue' => 'Payment Issue',
            'driver_behavior' => 'Driver Behavior',
            'app_technical' => 'App Technical Issue',
            'safety_concern' => 'Safety Concern',
            'pricing_dispute' => 'Pricing Dispute',
            'cancellation' => 'Cancellation Issue',
            'other' => 'Other'
        ];
    }

    // Convert from Firestore data - THIS IS THE KEY FIX
    public static function fromFirestoreData($data)
    {
        $complaint = new self([
            'order_type' => $data['order_type'] ?? '',
            'driver_name' => $data['driver_name'] ?? '',
            'user_name' => $data['user_name'] ?? '',
            'title' => $data['title'] ?? '',
            'complaint_by' => $data['complaint_by'] ?? '',
            'created_at' => isset($data['created_at']) ? Carbon::parse($data['created_at']) : null,
            'status' => $data['status'] ?? self::STATUS_PENDING,
            'description' => $data['description'] ?? '',
            'priority' => $data['priority'] ?? self::PRIORITY_MEDIUM,
            'category' => $data['category'] ?? 'other',
            'resolved_at' => isset($data['resolved_at']) ? Carbon::parse($data['resolved_at']) : null,
            'resolved_by' => $data['resolved_by'] ?? '',
            'admin_notes' => $data['admin_notes'] ?? '',
            'contact_info' => $data['contact_info'] ?? '',
            'order_id' => $data['order_id'] ?? '',
            'driver_id' => $data['driver_id'] ?? '',
            'user_id' => $data['user_id'] ?? ''
        ]);

        // Set the ID separately - this is crucial!
        $complaint->id = $data['id'] ?? '';
        
        // Mark as existing record so Laravel doesn't try to auto-increment
        $complaint->exists = true;

        return $complaint;
    }

    // Convert to Firestore format
    public function toFirestoreArray()
    {
        return [
            'id' => $this->id ?? '',
            'order_type' => $this->order_type ?? '',
            'driver_name' => $this->driver_name ?? '',
            'user_name' => $this->user_name ?? '',
            'title' => $this->title ?? '',
            'complaint_by' => $this->complaint_by ?? '',
            'created_at' => $this->created_at ? $this->created_at->toISOString() : null,
            'status' => $this->status ?? self::STATUS_PENDING,
            'description' => $this->description ?? '',
            'priority' => $this->priority ?? self::PRIORITY_MEDIUM,
            'category' => $this->category ?? 'other',
            'resolved_at' => $this->resolved_at ? $this->resolved_at->toISOString() : null,
            'resolved_by' => $this->resolved_by ?? '',
            'admin_notes' => $this->admin_notes ?? '',
            'contact_info' => $this->contact_info ?? '',
            'order_id' => $this->order_id ?? '',
            'driver_id' => $this->driver_id ?? '',
            'user_id' => $this->user_id ?? '',
            'updated_at' => now()->toISOString()
        ];
    }

    public function getFirebaseCollection()
    {
        return 'complaints';
    }

    public function getFirebaseDocumentId()
    {
        return $this->id;
    }
}