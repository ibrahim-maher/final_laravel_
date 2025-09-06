<?php

namespace App\Modules\Driver\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Ride extends Model
{
    protected $collection = 'rides';
    
    protected $fillable = [
        'ride_id',
        'driver_firebase_uid',
        'passenger_firebase_uid',
        'vehicle_id',
        'pickup_address',
        'pickup_latitude',
        'pickup_longitude',
        'dropoff_address',
        'dropoff_latitude',
        'dropoff_longitude',
        'status',
        'ride_type',
        'requested_at',
        'accepted_at',
        'started_at',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
        'cancelled_by',
        'distance_km',
        'duration_minutes',
        'estimated_fare',
        'actual_fare',
        'base_fare',
        'distance_fare',
        'time_fare',
        'surge_multiplier',
        'surge_fare',
        'tolls',
        'taxes',
        'tips',
        'discount',
        'total_amount',
        'driver_earnings',
        'commission',
        'payment_method',
        'payment_status',
        'driver_rating',
        'passenger_rating',
        'driver_feedback',
        'passenger_feedback',
        'route_polyline',
        'weather_condition',
        'traffic_condition',
        'special_requests',
        'promocode_used',
        'created_at',
        'updated_at'
    ];

    protected $dates = [
        'requested_at',
        'accepted_at',
        'started_at',
        'completed_at',
        'cancelled_at',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'pickup_latitude' => 'decimal:8',
        'pickup_longitude' => 'decimal:8',
        'dropoff_latitude' => 'decimal:8',
        'dropoff_longitude' => 'decimal:8',
        'distance_km' => 'decimal:2',
        'duration_minutes' => 'integer',
        'estimated_fare' => 'decimal:2',
        'actual_fare' => 'decimal:2',
        'base_fare' => 'decimal:2',
        'distance_fare' => 'decimal:2',
        'time_fare' => 'decimal:2',
        'surge_multiplier' => 'decimal:2',
        'surge_fare' => 'decimal:2',
        'tolls' => 'decimal:2',
        'taxes' => 'decimal:2',
        'tips' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'driver_earnings' => 'decimal:2',
        'commission' => 'decimal:2',
        'driver_rating' => 'decimal:1',
        'passenger_rating' => 'decimal:1',
        'special_requests' => 'array'
    ];

    // Ride status constants
    const STATUS_PENDING = 'pending';
    const STATUS_REQUESTED = 'requested';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_DRIVER_ARRIVED = 'driver_arrived';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    // Ride type constants
    const TYPE_STANDARD = 'standard';
    const TYPE_PREMIUM = 'premium';
    const TYPE_SHARED = 'shared';
    const TYPE_XL = 'xl';
    const TYPE_DELIVERY = 'delivery';
    const TYPE_SCHEDULED = 'scheduled';

    // Payment status constants
    const PAYMENT_PENDING = 'pending';
    const PAYMENT_COMPLETED = 'completed';
    const PAYMENT_FAILED = 'failed';
    const PAYMENT_REFUNDED = 'refunded';

    // Cancellation reasons
    const CANCEL_DRIVER_NO_SHOW = 'driver_no_show';
    const CANCEL_PASSENGER_NO_SHOW = 'passenger_no_show';
    const CANCEL_DRIVER_REQUEST = 'driver_request';
    const CANCEL_PASSENGER_REQUEST = 'passenger_request';
    const CANCEL_SYSTEM = 'system';
    const CANCEL_EMERGENCY = 'emergency';

    /**
     * Get the driver for this ride
     */
    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_firebase_uid', 'firebase_uid');
    }

    /**
     * Get the vehicle used for this ride
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id', 'id');
    }

    /**
     * Check if ride is active (in progress)
     */
    public function isActive()
    {
        return in_array($this->status, [
            self::STATUS_ACCEPTED,
            self::STATUS_DRIVER_ARRIVED,
            self::STATUS_IN_PROGRESS
        ]);
    }

    /**
     * Check if ride is completed
     */
    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if ride is cancelled
     */
    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Get ride duration in human readable format
     */
    public function getDurationFormattedAttribute()
    {
        if (!$this->duration_minutes) {
            return 'N/A';
        }

        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }

        return "{$minutes}m";
    }

    /**
     * Get actual ride duration (from started to completed)
     */
    public function getActualDurationAttribute()
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->started_at->diffInMinutes($this->completed_at);
    }

    /**
     * Get waiting time (from accepted to started)
     */
    public function getWaitingTimeAttribute()
    {
        if (!$this->accepted_at || !$this->started_at) {
            return null;
        }

        return $this->accepted_at->diffInMinutes($this->started_at);
    }

    /**
     * Get response time (from requested to accepted)
     */
    public function getResponseTimeAttribute()
    {
        if (!$this->requested_at || !$this->accepted_at) {
            return null;
        }

        return $this->requested_at->diffInMinutes($this->accepted_at);
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute()
    {
        $colors = [
            self::STATUS_REQUESTED => 'warning',
            self::STATUS_ACCEPTED => 'info',
            self::STATUS_DRIVER_ARRIVED => 'primary',
            self::STATUS_IN_PROGRESS => 'success',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CANCELLED => 'danger'
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    /**
     * Get payment status color
     */
    public function getPaymentStatusColorAttribute()
    {
        $colors = [
            self::PAYMENT_PENDING => 'warning',
            self::PAYMENT_COMPLETED => 'success',
            self::PAYMENT_FAILED => 'danger',
            self::PAYMENT_REFUNDED => 'info'
        ];

        return $colors[$this->payment_status] ?? 'secondary';
    }

    /**
     * Calculate distance using haversine formula
     */
    public static function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng/2) * sin($dLng/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $earthRadius * $c;
    }

    /**
     * Estimate fare based on distance and time
     */
    public function estimateFare($baseFare = 5.00, $perKmRate = 1.50, $perMinuteRate = 0.25)
    {
        $distanceFare = $this->distance_km * $perKmRate;
        $timeFare = $this->duration_minutes * $perMinuteRate;
        $surgeFare = ($baseFare + $distanceFare + $timeFare) * (($this->surge_multiplier ?? 1) - 1);

        return $baseFare + $distanceFare + $timeFare + $surgeFare;
    }

    /**
     * Scope: Active rides
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_ACCEPTED,
            self::STATUS_DRIVER_ARRIVED,
            self::STATUS_IN_PROGRESS
        ]);
    }

    /**
     * Scope: Completed rides
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope: Cancelled rides
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    /**
     * Scope: Today's rides
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope: This week's rides
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /**
     * Scope: This month's rides
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
    }

    /**
     * Scope: By driver
     */
    public function scopeByDriver($query, $driverFirebaseUid)
    {
        return $query->where('driver_firebase_uid', $driverFirebaseUid);
    }

    /**
     * Scope: Search by ride ID or addresses
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('ride_id', 'like', "%{$term}%")
              ->orWhere('pickup_address', 'like', "%{$term}%")
              ->orWhere('dropoff_address', 'like', "%{$term}%");
        });
    }

    /**
     * Get available ride types
     */
    public static function getRideTypes()
    {
        return [
            self::TYPE_STANDARD => 'Standard',
            self::TYPE_PREMIUM => 'Premium',
            self::TYPE_SHARED => 'Shared',
            self::TYPE_XL => 'XL',
            self::TYPE_DELIVERY => 'Delivery',
            self::TYPE_SCHEDULED => 'Scheduled'
        ];
    }

    /**
     * Get cancellation reasons
     */
    public static function getCancellationReasons()
    {
        return [
            self::CANCEL_DRIVER_NO_SHOW => 'Driver No Show',
            self::CANCEL_PASSENGER_NO_SHOW => 'Passenger No Show',
            self::CANCEL_DRIVER_REQUEST => 'Driver Requested',
            self::CANCEL_PASSENGER_REQUEST => 'Passenger Requested',
            self::CANCEL_SYSTEM => 'System Cancelled',
            self::CANCEL_EMERGENCY => 'Emergency'
        ];
    }
}