<?php

namespace App\Modules\Driver\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Modules\Driver\Services\DriverService;
use App\Modules\Driver\Services\DriverRideService;
use App\Modules\Driver\Services\DriverVehicleService;   


use App\Modules\Driver\Models\Ride;

class AdminRideController extends Controller
{
    protected $driverService;
    protected $DriverRideService;
    protected $DriverVehicleService;

    public function __construct(DriverService $driverService, DriverRideService $DriverRideService, DriverVehicleService $DriverVehicleService)
    {
        $this->driverService = $driverService;
        $this->DriverRideService = $DriverRideService;
        $this->DriverVehicleService = $DriverVehicleService;


       
    }

    /**
     * Display ride management dashboard
     */
    public function index(Request $request)
    {
        try {
            $filters = [
                'search' => $request->get('search'),
                'status' => $request->get('status'),
                'ride_type' => $request->get('ride_type'),
                'payment_status' => $request->get('payment_status'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
                'limit' => $request->get('limit', 50)
            ];

            // Get all rides from all drivers
            $allDrivers = $this->driverService->getAllDrivers(['limit' => 1000]);
            $rides = collect();
            
            foreach ($allDrivers as $driver) {
                $driverRides = $this->driverService->getDriverRides($driver['firebase_uid']);
                foreach ($driverRides as $ride) {
                    $ride['driver_name'] = $driver['name'];
                    $ride['driver_email'] = $driver['email'];
                    $rides->push($ride);
                }
            }

            // Apply filters
            if (!empty($filters['search'])) {
                $search = strtolower($filters['search']);
                $rides = $rides->filter(function($ride) use ($search) {
                    return stripos($ride['ride_id'] ?? '', $search) !== false ||
                           stripos($ride['pickup_address'] ?? '', $search) !== false ||
                           stripos($ride['dropoff_address'] ?? '', $search) !== false ||
                           stripos($ride['driver_name'] ?? '', $search) !== false ||
                           stripos($ride['passenger_name'] ?? '', $search) !== false;
                });
            }

            if (!empty($filters['status'])) {
                $rides = $rides->where('status', $filters['status']);
            }

            if (!empty($filters['ride_type'])) {
                $rides = $rides->where('ride_type', $filters['ride_type']);
            }

            if (!empty($filters['payment_status'])) {
                $rides = $rides->where('payment_status', $filters['payment_status']);
            }

            if (!empty($filters['date_from'])) {
                $dateFrom = \Carbon\Carbon::parse($filters['date_from'])->startOfDay();
                $rides = $rides->filter(function($ride) use ($dateFrom) {
                    $rideDate = \Carbon\Carbon::parse($ride['created_at'] ?? now());
                    return $rideDate->gte($dateFrom);
                });
            }

            if (!empty($filters['date_to'])) {
                $dateTo = \Carbon\Carbon::parse($filters['date_to'])->endOfDay();
                $rides = $rides->filter(function($ride) use ($dateTo) {
                    $rideDate = \Carbon\Carbon::parse($ride['created_at'] ?? now());
                    return $rideDate->lte($dateTo);
                });
            }

            // Sort by creation date descending
            $rides = $rides->sortByDesc('created_at');

            // Paginate
            $currentPage = $request->get('page', 1);
            $perPage = $filters['limit'];
            $totalRides = $rides->count();
            $rides = $rides->forPage($currentPage, $perPage);

            Log::info('Admin ride dashboard accessed', [
                'admin' => session('firebase_user.email'),
                'filters' => $filters
            ]);
            
            return view('driver::admin.rides.index', compact(
                'rides', 
                'totalRides'
            ) + $filters + [
                'rideStatuses' => $this->getRideStatuses(),
                'rideTypes' => Ride::getRideTypes(),
                'paymentStatuses' => $this->getPaymentStatuses()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error loading admin ride dashboard: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error loading ride dashboard.');
        }
    }

    /**
     * Show detailed ride information
     */
    public function show(string $rideId)
    {
        try {
            $ride = $this->driverService->getRideById($rideId);
            
            if (!$ride) {
                return redirect()->route('admin.rides.index')
                    ->with('error', 'Ride not found.');
            }

            // Get driver information
            $driver = $this->driverService->getDriverById($ride['driver_firebase_uid']);
            
            // Get vehicle information if available
            $vehicle = null;
            if (isset($ride['vehicle_id'])) {
                $vehicle = $this->DriverVehicleService->getVehicleById($ride['vehicle_id']);
            }

            Log::info('Admin viewed ride details', [
                'admin' => session('firebase_user.email'),
                'ride_id' => $rideId
            ]);
            
            return view('driver::admin.rides.show', compact(
                'ride',
                'driver',
                'vehicle'
            ));
            
        } catch (\Exception $e) {
            Log::error('Error loading ride details: ' . $e->getMessage());
            return redirect()->route('admin.rides.index')
                ->with('error', 'Error loading ride details.');
        }
    }

    /**
     * Show form for creating new ride
     */
    public function create(Request $request)
    {
        $driverFirebaseUid = $request->get('driver_firebase_uid');
        $driver = null;
        $vehicles = collect();
        
        if ($driverFirebaseUid) {
            $driver = $this->driverService->getDriverById($driverFirebaseUid);
            $vehicles = collect($this->driverService->getDriverVehicles($driverFirebaseUid));
        }

        return view('driver::admin.rides.create', [
            'driver' => $driver,
            'vehicles' => $vehicles,
            'rideStatuses' => $this->getRideStatuses(),
            'rideTypes' => Ride::getRideTypes(),
            'paymentStatuses' => $this->getPaymentStatuses(),
            'drivers' => $this->driverService->getAllDrivers(['limit' => 1000])
        ]);
    }

    /**
     * Store newly created ride
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'driver_firebase_uid' => 'required|string',
            'passenger_firebase_uid' => 'nullable|string',
            'passenger_name' => 'required|string|max:255',
            'passenger_phone' => 'nullable|string|max:20',
            'vehicle_id' => 'nullable|string',
            'pickup_address' => 'required|string|max:500',
            'pickup_latitude' => 'nullable|numeric|between:-90,90',
            'pickup_longitude' => 'nullable|numeric|between:-180,180',
            'dropoff_address' => 'required|string|max:500',
            'dropoff_latitude' => 'nullable|numeric|between:-90,90',
            'dropoff_longitude' => 'nullable|numeric|between:-180,180',
            'status' => 'required|in:' . implode(',', array_keys($this->getRideStatuses())),
            'ride_type' => 'required|in:' . implode(',', array_keys(Ride::getRideTypes())),
            'estimated_fare' => 'nullable|numeric|min:0',
            'distance_km' => 'nullable|numeric|min:0',
            'duration_minutes' => 'nullable|integer|min:0',
            'payment_method' => 'nullable|string|max:50',
            'payment_status' => 'required|in:' . implode(',', array_keys($this->getPaymentStatuses())),
            'special_requests' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $rideData = $request->all();
            $rideData['ride_id'] = 'RIDE_' . strtoupper(uniqid());
            $rideData['created_by'] = session('firebase_user.uid');
            $rideData['requested_at'] = now()->toDateTimeString();

            // Set status timestamps based on current status
            switch ($rideData['status']) {
                case Ride::STATUS_ACCEPTED:
                    $rideData['accepted_at'] = now()->toDateTimeString();
                    break;
                case Ride::STATUS_IN_PROGRESS:
                    $rideData['accepted_at'] = now()->toDateTimeString();
                    $rideData['started_at'] = now()->toDateTimeString();
                    break;
                case Ride::STATUS_COMPLETED:
                    $rideData['accepted_at'] = now()->toDateTimeString();
                    $rideData['started_at'] = now()->toDateTimeString();
                    $rideData['completed_at'] = now()->toDateTimeString();
                    break;
                case Ride::STATUS_CANCELLED:
                    $rideData['cancelled_at'] = now()->toDateTimeString();
                    $rideData['cancelled_by'] = 'admin';
                    break;
            }

            $result = $this->driverService->createRide($rideData);

            if ($result) {
                Log::info('Admin created ride', [
                    'admin' => session('firebase_user.email'),
                    'ride_id' => $result['id'] ?? 'unknown',
                    'driver_id' => $request->driver_firebase_uid
                ]);
                
                return redirect()->route('admin.rides.index')
                    ->with('success', 'Ride created successfully!');
            } else {
                return redirect()->back()
                    ->with('error', 'Failed to create ride.')
                    ->withInput();
            }

        } catch (\Exception $e) {
            Log::error('Error creating ride: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error creating ride: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show form for editing ride
     */
    public function edit(string $rideId)
    {
        try {
            $ride = $this->driverService->getRideById($rideId);
            
            if (!$ride) {
                return redirect()->route('admin.rides.index')
                    ->with('error', 'Ride not found.');
            }

            $driver = $this->driverService->getDriverById($ride['driver_firebase_uid']);
            $vehicles = collect($this->driverService->getDriverVehicles($ride['driver_firebase_uid']));

            return view('driver::admin.rides.edit', [
                'ride' => $ride,
                'driver' => $driver,
                'vehicles' => $vehicles,
                'rideStatuses' => $this->getRideStatuses(),
                'rideTypes' => Ride::getRideTypes(),
                'paymentStatuses' => $this->getPaymentStatuses(),
                'cancellationReasons' => Ride::getCancellationReasons()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error loading ride for edit: ' . $e->getMessage());
            return redirect()->route('admin.rides.index')
                ->with('error', 'Error loading ride for editing.');
        }
    }

    /**
     * Update ride information
     */
    public function update(Request $request, string $rideId)
    {
        $validator = Validator::make($request->all(), [
            'passenger_name' => 'required|string|max:255',
            'passenger_phone' => 'nullable|string|max:20',
            'vehicle_id' => 'nullable|string',
            'pickup_address' => 'required|string|max:500',
            'pickup_latitude' => 'nullable|numeric|between:-90,90',
            'pickup_longitude' => 'nullable|numeric|between:-180,180',
            'dropoff_address' => 'required|string|max:500',
            'dropoff_latitude' => 'nullable|numeric|between:-90,90',
            'dropoff_longitude' => 'nullable|numeric|between:-180,180',
            'status' => 'required|in:' . implode(',', array_keys($this->getRideStatuses())),
            'ride_type' => 'required|in:' . implode(',', array_keys(Ride::getRideTypes())),
            'estimated_fare' => 'nullable|numeric|min:0',
            'actual_fare' => 'nullable|numeric|min:0',
            'distance_km' => 'nullable|numeric|min:0',
            'duration_minutes' => 'nullable|integer|min:0',
            'payment_method' => 'nullable|string|max:50',
            'payment_status' => 'required|in:' . implode(',', array_keys($this->getPaymentStatuses())),
            'driver_rating' => 'nullable|numeric|between:1,5',
            'passenger_rating' => 'nullable|numeric|between:1,5',
            'cancellation_reason' => 'nullable|string|max:255',
            'special_requests' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $rideData = $request->all();
            $rideData['updated_by'] = session('firebase_user.uid');

            $result = $this->driverService->updateRide($rideId, $rideData);

            if ($result) {
                Log::info('Admin updated ride', [
                    'admin' => session('firebase_user.email'),
                    'ride_id' => $rideId
                ]);
                
                return redirect()->route('admin.rides.show', $rideId)
                    ->with('success', 'Ride updated successfully!');
            } else {
                return redirect()->back()
                    ->with('error', 'Failed to update ride.')
                    ->withInput();
            }

        } catch (\Exception $e) {
            Log::error('Error updating ride: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error updating ride: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Update ride status (AJAX)
     */
    public function updateStatus(Request $request, string $rideId)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:' . implode(',', array_keys($this->getRideStatuses())),
            'reason' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status'
            ], 400);
        }

        try {
            $result = $this->driverService->updateRideStatus($rideId, $request->status);

            if ($result) {
                Log::info('Admin updated ride status', [
                    'admin' => session('firebase_user.email'),
                    'ride_id' => $rideId,
                    'status' => $request->status
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Ride status updated successfully!'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update ride status'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error updating ride status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating status: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Complete ride (AJAX)
     */
    public function complete(Request $request, string $rideId)
    {
        $validator = Validator::make($request->all(), [
            'actual_fare' => 'nullable|numeric|min:0',
            'driver_rating' => 'nullable|numeric|between:1,5',
            'passenger_rating' => 'nullable|numeric|between:1,5',
            'driver_feedback' => 'nullable|string|max:1000',
            'passenger_feedback' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid completion data',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $completionData = $request->only([
                'actual_fare', 'driver_rating', 'passenger_rating', 
                'driver_feedback', 'passenger_feedback'
            ]);

            $result = $this->driverService->completeRide($rideId, $completionData);

            if ($result) {
                Log::info('Admin completed ride', [
                    'admin' => session('firebase_user.email'),
                    'ride_id' => $rideId
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Ride completed successfully!'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to complete ride'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error completing ride: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error completing ride: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Cancel ride (AJAX)
     */
    public function cancel(Request $request, string $rideId)
    {
        $validator = Validator::make($request->all(), [
            'cancellation_reason' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Cancellation reason is required',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $result = $this->driverService->cancelRide($rideId, $request->cancellation_reason);

            if ($result) {
                Log::info('Admin cancelled ride', [
                    'admin' => session('firebase_user.email'),
                    'ride_id' => $rideId,
                    'reason' => $request->cancellation_reason
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Ride cancelled successfully!'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to cancel ride'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error cancelling ride: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling ride: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Ride statistics dashboard
     */
    public function statistics()
    {
        try {
            // Calculate ride statistics from all drivers
            $allDrivers = $this->driverService->getAllDrivers(['limit' => 1000]);
            $totalRides = 0;
            $completedRides = 0;
            $cancelledRides = 0;
            $totalEarnings = 0;
            $ridesByStatus = [];
            $ridesByType = [];

            foreach ($allDrivers as $driver) {
                $driverStats = $this->driverService->getDriverRideStatistics($driver['firebase_uid']);
                $totalRides += $driverStats['total_rides'] ?? 0;
                $completedRides += $driverStats['completed_rides'] ?? 0;
                $cancelledRides += $driverStats['cancelled_rides'] ?? 0;
                $totalEarnings += $driverStats['total_earnings'] ?? 0;
            }

            $statistics = [
                'total_rides' => $totalRides,
                'completed_rides' => $completedRides,
                'cancelled_rides' => $cancelledRides,
                'in_progress_rides' => $totalRides - $completedRides - $cancelledRides,
                'total_earnings' => $totalEarnings,
                'completion_rate' => $totalRides > 0 ? round(($completedRides / $totalRides) * 100, 2) : 0,
                'cancellation_rate' => $totalRides > 0 ? round(($cancelledRides / $totalRides) * 100, 2) : 0,
                'average_earnings_per_ride' => $completedRides > 0 ? round($totalEarnings / $completedRides, 2) : 0
            ];
            
            return view('driver::admin.rides.statistics', compact('statistics'));

        } catch (\Exception $e) {
            Log::error('Error loading ride statistics: ' . $e->getMessage());
            return redirect()->route('admin.rides.index')
                ->with('error', 'Error loading statistics.');
        }
    }

    /**
     * Helper: Get ride statuses
     */
    private function getRideStatuses(): array
    {
        return [
            'pending' => 'Pending',
            'requested' => 'Requested',
            'accepted' => 'Accepted',
            'driver_arrived' => 'Driver Arrived',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled'
        ];
    }

    /**
     * Helper: Get payment statuses
     */
    private function getPaymentStatuses(): array
    {
        return [
            'pending' => 'Pending',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'refunded' => 'Refunded'
        ];
    }
}