@extends('admin::layouts.admin')

@section('title', 'Ride Details')
@section('page-title', 'Ride Details')

@php
// Safely extract all data to prevent array-to-string errors
$rideId = is_string($ride['ride_id'] ?? $ride['id'] ?? '') ? ($ride['ride_id'] ?? $ride['id'] ?? '') : '';
$driverName = is_string($ride['driver_name'] ?? '') ? $ride['driver_name'] : 'Unknown Driver';
$passengerName = is_string($ride['passenger_name'] ?? '') ? $ride['passenger_name'] : 'Unknown Passenger';
$rideStatus = is_string($ride['status'] ?? '') ? $ride['status'] : 'pending';
$rideType = is_string($ride['ride_type'] ?? '') ? $ride['ride_type'] : 'standard';
$paymentStatus = is_string($ride['payment_status'] ?? '') ? $ride['payment_status'] : 'pending';

// Safe coordinates with validation
$pickupLat = is_numeric($ride['pickup_latitude'] ?? null) ? (float)$ride['pickup_latitude'] : 37.7749;
$pickupLng = is_numeric($ride['pickup_longitude'] ?? null) ? (float)$ride['pickup_longitude'] : -122.4194;
$dropoffLat = is_numeric($ride['dropoff_latitude'] ?? null) ? (float)$ride['dropoff_latitude'] : 37.7849;
$dropoffLng = is_numeric($ride['dropoff_longitude'] ?? null) ? (float)$ride['dropoff_longitude'] : -122.4094;

// Safe addresses for JavaScript
$pickupAddress = str_replace(['"', "'", "\n", "\r"], ['\"', "\'", ' ', ' '], $ride['pickup_address'] ?? 'Unknown pickup location');
$dropoffAddress = str_replace(['"', "'", "\n", "\r"], ['\"', "\'", ' ', ' '], $ride['dropoff_address'] ?? 'Unknown dropoff location');

// Safe numeric values
$estimatedFare = is_numeric($ride['estimated_fare'] ?? 0) ? (float)$ride['estimated_fare'] : 0;
$actualFare = is_numeric($ride['actual_fare'] ?? 0) ? (float)$ride['actual_fare'] : 0;
$distanceKm = is_numeric($ride['distance_km'] ?? 0) ? (float)$ride['distance_km'] : 0;
$durationMinutes = is_numeric($ride['duration_minutes'] ?? 0) ? (int)$ride['duration_minutes'] : 0;
$driverRating = is_numeric($ride['driver_rating'] ?? 0) ? (float)$ride['driver_rating'] : 0;
$passengerRating = is_numeric($ride['passenger_rating'] ?? 0) ? (float)$ride['passenger_rating'] : 0;

// Format dates safely
$createdAt = null;
$createdDisplay = 'Unknown';
$createdHuman = 'Date not available';

if (!empty($ride['created_at'])) {
try {
$createdAt = \Carbon\Carbon::parse($ride['created_at']);
$createdDisplay = $createdAt->format('M d, Y g:i A');
$createdHuman = $createdAt->diffForHumans();
} catch (\Exception $e) {
// Handle date parsing error silently
}
}

$completedAt = '';
if (!empty($ride['completed_at']) && $rideStatus === 'completed') {
try {
$completedAt = \Carbon\Carbon::parse($ride['completed_at'])->format('g:i A');
} catch (\Exception $e) {
$completedAt = '';
}
}
@endphp

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-primary">Ride Details</h1>
        <p class="text-gray-600 mt-1">Viewing ride: {{ $rideId }}</p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('admin.rides.index') }}"
            class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back to Rides
        </a>
    </div>
</div>

@if(session('success'))
<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
    <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
    <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
</div>
@endif

<!-- Quick Stats - Same style as index page -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                <i class="fas fa-route text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Ride ID</p>
                <p class="text-2xl font-bold text-gray-900">{{ $rideId }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                <i class="fas fa-dollar-sign text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Total Fare</p>
                <p class="text-2xl font-bold text-gray-900">${{ number_format($actualFare > 0 ? $actualFare : $estimatedFare, 2) }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                <i class="fas fa-road text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Distance</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($distanceKm, 1) }} km</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                <i class="fas fa-clock text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Duration</p>
                <p class="text-2xl font-bold text-gray-900">{{ $durationMinutes }} min</p>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left Column - Main Details -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Ride Information Card -->
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
            <div class="p-6 border-b">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold">
                        <i class="fas fa-info-circle mr-2 text-primary"></i>Ride Information
                    </h2>
                    <div class="flex gap-2">
                        <!-- Status Badge -->
                        @switch($rideStatus)
                        @case('completed')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="fas fa-check-circle mr-1"></i>Completed
                        </span>
                        @break
                        @case('cancelled')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <i class="fas fa-times-circle mr-1"></i>Cancelled
                        </span>
                        @break
                        @case('in_progress')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <i class="fas fa-car mr-1"></i>In Progress
                        </span>
                        @break
                        @case('accepted')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            <i class="fas fa-clock mr-1"></i>Accepted
                        </span>
                        @break
                        @case('driver_arrived')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                            <i class="fas fa-map-marker-alt mr-1"></i>Driver Arrived
                        </span>
                        @break
                        @default
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            <i class="fas fa-question-circle mr-1"></i>{{ ucfirst($rideStatus) }}
                        </span>
                        @endswitch

                        <!-- Payment Status -->
                        @switch($paymentStatus)
                        @case('completed')
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="fas fa-dollar-sign mr-1"></i>Paid
                        </span>
                        @break
                        @case('failed')
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <i class="fas fa-exclamation-triangle mr-1"></i>Failed
                        </span>
                        @break
                        @case('refunded')
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <i class="fas fa-undo mr-1"></i>Refunded
                        </span>
                        @break
                        @default
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            <i class="fas fa-clock mr-1"></i>Pending
                        </span>
                        @endswitch
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Driver & Passenger Info -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Driver & Passenger</h3>
                        <div class="space-y-3">
                            <div>
                                <span class="font-medium text-gray-700">Driver:</span>
                                <span class="text-gray-900 ml-2">{{ $driverName }}</span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700">Passenger:</span>
                                <span class="text-gray-900 ml-2">{{ $passengerName }}</span>
                            </div>
                            @if(!empty($ride['passenger_phone']))
                            <div>
                                <span class="font-medium text-gray-700">Phone:</span>
                                <span class="text-gray-900 ml-2">{{ $ride['passenger_phone'] }}</span>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Trip Details -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Trip Details</h3>
                        <div class="space-y-3">
                            <div>
                                <span class="font-medium text-gray-700">Type:</span>
                                <span class="text-gray-900 ml-2">{{ ucfirst(str_replace('_', ' ', $rideType)) }}</span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700">Created:</span>
                                <span class="text-gray-900 ml-2">{{ $createdDisplay }}</span>
                                <div class="text-xs text-gray-500 ml-2">{{ $createdHuman }}</div>
                            </div>
                            @if($completedAt)
                            <div>
                                <span class="font-medium text-gray-700">Completed:</span>
                                <span class="text-gray-900 ml-2">{{ $completedAt }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Route Information -->
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
            <div class="p-6 border-b">
                <h2 class="text-xl font-semibold">
                    <i class="fas fa-map-marked-alt mr-2 text-primary"></i>Route Information
                </h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div>
                        <div class="font-medium text-gray-700 flex items-center mb-2">
                            <i class="fas fa-circle text-green-500 mr-2 text-xs"></i>Pickup Location
                        </div>
                        <div class="text-gray-600 ml-4">{{ $ride['pickup_address'] ?? 'Unknown pickup location' }}</div>
                    </div>
                    <div>
                        <div class="font-medium text-gray-700 flex items-center mb-2">
                            <i class="fas fa-circle text-red-500 mr-2 text-xs"></i>Dropoff Location
                        </div>
                        <div class="text-gray-600 ml-4">{{ $ride['dropoff_address'] ?? 'Unknown dropoff location' }}</div>
                    </div>
                </div>

                <!-- Map Container -->
                <div class="mt-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-medium text-gray-900">Route Map</h3>
                        <button id="toggle-directions" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm">
                            <i class="fas fa-directions mr-2"></i>Show Directions
                        </button>
                    </div>

                    <!-- Map container with loading states -->
                    <div class="relative w-full h-96 bg-gray-100 rounded-lg mb-4 overflow-hidden">
                        <!-- Loading state -->
                        <div id="map-loading" class="absolute inset-0 flex items-center justify-center bg-gray-100 z-10">
                            <div class="text-center">
                                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mb-2"></div>
                                <p class="text-gray-600">Loading map...</p>
                            </div>
                        </div>

                        <!-- Error state -->
                        <div id="map-error" class="absolute inset-0 flex items-center justify-center bg-red-50 z-20 hidden">
                            <div class="text-center p-4">
                                <i class="fas fa-exclamation-triangle text-red-500 text-2xl mb-2"></i>
                                <p class="text-red-600 font-medium">Map failed to load</p>
                                <p class="text-red-500 text-sm mb-3">Please check your API key configuration</p>
                                <button onclick="retryMapLoad()" class="bg-red-600 text-white px-4 py-2 rounded text-sm hover:bg-red-700">
                                    <i class="fas fa-redo mr-2"></i>Retry
                                </button>
                            </div>
                        </div>

                        <!-- Actual map -->
                        <div id="map" class="w-full h-full"></div>
                    </div>

                    <!-- Directions panel -->
                    <div id="directions-panel" class="hidden bg-gray-50 p-4 rounded-lg border max-h-64 overflow-y-auto">
                        <div class="text-center p-4">
                            <i class="fas fa-spinner fa-spin mr-2"></i>Loading directions...
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Information -->
        @if(($ride['notes'] ?? '') || ($ride['special_requests'] ?? '') || ($ride['cancellation_reason'] ?? ''))
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
            <div class="p-6 border-b">
                <h2 class="text-xl font-semibold">
                    <i class="fas fa-sticky-note mr-2 text-primary"></i>Additional Information
                </h2>
            </div>
            <div class="p-6 space-y-4">
                @if($ride['notes'] ?? '')
                <div>
                    <h4 class="font-semibold text-blue-600 mb-2">Notes:</h4>
                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">{{ $ride['notes'] }}</div>
                </div>
                @endif

                @if($ride['special_requests'] ?? '')
                <div>
                    <h4 class="font-semibold text-amber-600 mb-2">Special Requests:</h4>
                    <div class="bg-amber-50 p-4 rounded-lg border border-amber-200">{{ $ride['special_requests'] }}</div>
                </div>
                @endif

                @if($ride['cancellation_reason'] ?? '')
                <div>
                    <h4 class="font-semibold text-red-600 mb-2">Cancellation Reason:</h4>
                    <div class="bg-red-50 p-4 rounded-lg border border-red-200">{{ $ride['cancellation_reason'] }}</div>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>

    <!-- Right Column - Actions & Details -->
    <div class="space-y-6">
        <!-- Payment Information -->
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
            <div class="p-6 border-b">
                <h2 class="text-xl font-semibold">
                    <i class="fas fa-credit-card mr-2 text-primary"></i>Payment Details
                </h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        <div class="text-sm text-gray-600">Estimated</div>
                        <div class="text-lg font-bold text-gray-900">${{ number_format($estimatedFare, 2) }}</div>
                    </div>
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        <div class="text-sm text-gray-600">Actual</div>
                        <div class="text-lg font-bold text-gray-900">${{ number_format($actualFare, 2) }}</div>
                    </div>
                </div>
                @if($ride['payment_method'] ?? '')
                <div class="text-center text-sm text-gray-500">
                    Method: {{ ucfirst($ride['payment_method']) }}
                </div>
                @endif
            </div>
        </div>

        <!-- Ratings -->
        @if($driverRating > 0 || $passengerRating > 0)
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
            <div class="p-6 border-b">
                <h2 class="text-xl font-semibold">
                    <i class="fas fa-star mr-2 text-primary"></i>Ratings
                </h2>
            </div>
            <div class="p-6 space-y-4">
                @if($driverRating > 0)
                <div>
                    <div class="text-sm text-gray-600 mb-1">Driver Rating</div>
                    <div class="flex items-center">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="fas fa-star {{ $i <= $driverRating ? 'text-yellow-400' : 'text-gray-300' }}"></i>
                            @endfor
                            <span class="ml-2 text-gray-600">{{ $driverRating }}/5</span>
                    </div>
                </div>
                @endif

                @if($passengerRating > 0)
                <div>
                    <div class="text-sm text-gray-600 mb-1">Passenger Rating</div>
                    <div class="flex items-center">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="fas fa-star {{ $i <= $passengerRating ? 'text-yellow-400' : 'text-gray-300' }}"></i>
                            @endfor
                            <span class="ml-2 text-gray-600">{{ $passengerRating }}/5</span>
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
            <div class="p-6 border-b">
                <h2 class="text-xl font-semibold">
                    <i class="fas fa-cogs mr-2 text-primary"></i>Quick Actions
                </h2>
            </div>
            <div class="p-6 space-y-3">
                @if(!in_array($rideStatus, ['completed', 'cancelled']))
                <button onclick="completeRide('{{ $rideId }}')"
                    class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-check mr-2"></i>Complete Ride
                </button>
                <button onclick="cancelRide('{{ $rideId }}')"
                    class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fas fa-times mr-2"></i>Cancel Ride
                </button>
                @endif

                <a href="{{ route('admin.rides.edit', $rideId) }}"
                    class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors inline-block text-center">
                    <i class="fas fa-edit mr-2"></i>Edit Ride
                </a>

                <button onclick="window.print()"
                    class="w-full bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-print mr-2"></i>Print Details
                </button>

                <button onclick="exportRideData()"
                    class="w-full bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                    <i class="fas fa-download mr-2"></i>Export Data
                </button>
            </div>
        </div>
    </div>
</div>

<!-- CSRF Token for AJAX requests -->
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@push('scripts')
<script>
    // CRITICAL FIX: Proper coordinate assignment without syntax errors
    let map, directionsService, directionsRenderer;
    let mapInitialized = false;

    // Define initMap globally before loading the API
    window.initMap = function() {
        console.log('Google Maps API loaded, initializing map...');

        try {
            // Hide loading state
            hideMapLoading();

            // FIXED: Proper coordinate variables
            const pickupLatLng = {
                lat: {
                    {
                        $pickupLat
                    }
                },
                lng: {
                    {
                        $pickupLng
                    }
                }
            };

            const dropoffLatLng = {
                lat: {
                    {
                        $dropoffLat
                    }
                },
                lng: {
                    {
                        $dropoffLng
                    }
                }
            };

            console.log('Pickup coordinates:', pickupLatLng);
            console.log('Dropoff coordinates:', dropoffLatLng);

            // Initialize map
            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 13,
                center: pickupLatLng,
                mapTypeId: google.maps.MapTypeId.ROADMAP,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: true,
                styles: [{
                    featureType: 'poi',
                    elementType: 'labels',
                    stylers: [{
                        visibility: 'off'
                    }]
                }]
            });

            // Initialize directions service and renderer
            directionsService = new google.maps.DirectionsService();
            directionsRenderer = new google.maps.DirectionsRenderer({
                draggable: false,
                panel: null
            });

            directionsRenderer.setMap(map);

            // Add markers
            addMarkers();

            // Calculate and display route
            calculateRoute();

            mapInitialized = true;
            console.log('Map initialized successfully');

        } catch (error) {
            console.error('Error initializing map:', error);
            showMapError('Failed to initialize map: ' + error.message);
        }

        function addMarkers() {
            try {
                // Pickup marker
                const pickupMarker = new google.maps.Marker({
                    position: pickupLatLng,
                    map: map,
                    title: 'Pickup Location',
                    icon: {
                        url: 'data:image/svg+xml;base64,' + btoa(`
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32">
                            <circle cx="16" cy="16" r="12" fill="#10B981" stroke="#fff" stroke-width="3"/>
                            <text x="16" y="21" text-anchor="middle" fill="white" font-family="Arial" font-size="14" font-weight="bold">P</text>
                        </svg>
                    `),
                        scaledSize: new google.maps.Size(32, 32)
                    }
                });

                // Dropoff marker
                const dropoffMarker = new google.maps.Marker({
                    position: dropoffLatLng,
                    map: map,
                    title: 'Dropoff Location',
                    icon: {
                        url: 'data:image/svg+xml;base64,' + btoa(`
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32">
                            <circle cx="16" cy="16" r="12" fill="#EF4444" stroke="#fff" stroke-width="3"/>
                            <text x="16" y="21" text-anchor="middle" fill="white" font-family="Arial" font-size="14" font-weight="bold">D</text>
                        </svg>
                    `),
                        scaledSize: new google.maps.Size(32, 32)
                    }
                });

                // Info windows
                const pickupInfo = new google.maps.InfoWindow({
                    content: `
                    <div class="p-2">
                        <strong>Pickup Location</strong><br>
                        <small class="text-gray-600">{{ $pickupAddress }}</small>
                    </div>
                `
                });

                const dropoffInfo = new google.maps.InfoWindow({
                    content: `
                    <div class="p-2">
                        <strong>Dropoff Location</strong><br>
                        <small class="text-gray-600">{{ $dropoffAddress }}</small>
                    </div>
                `
                });

                pickupMarker.addListener('click', () => {
                    dropoffInfo.close();
                    pickupInfo.open(map, pickupMarker);
                });

                dropoffMarker.addListener('click', () => {
                    pickupInfo.close();
                    dropoffInfo.open(map, dropoffMarker);
                });

            } catch (error) {
                console.error('Error adding markers:', error);
            }
        }

        function calculateRoute() {
            const request = {
                origin: pickupLatLng,
                destination: dropoffLatLng,
                travelMode: google.maps.TravelMode.DRIVING,
                unitSystem: google.maps.UnitSystem.METRIC,
                avoidHighways: false,
                avoidTolls: false
            };

            directionsService.route(request, (result, status) => {
                if (status === 'OK') {
                    directionsRenderer.setDirections(result);
                    updateDirectionsPanel(result);

                    // Fit map to show entire route
                    const bounds = new google.maps.LatLngBounds();
                    bounds.extend(pickupLatLng);
                    bounds.extend(dropoffLatLng);
                    map.fitBounds(bounds);
                } else {
                    console.error('Directions request failed due to ' + status);
                    const directionsPanel = document.getElementById('directions-panel');
                    if (directionsPanel) {
                        directionsPanel.innerHTML =
                            '<div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">Unable to load directions. Please check the coordinates.</div>';
                    }
                }
            });
        }

        function updateDirectionsPanel(directionsResult) {
            const route = directionsResult.routes[0];
            const leg = route.legs[0];

            let html = `
            <div class="mb-4 p-3 bg-white rounded-lg border">
                <div class="font-semibold text-gray-900 mb-2">Route Summary</div>
                <div class="text-sm text-gray-600">
                    Distance: <span class="font-medium">${leg.distance.text}</span> • 
                    Duration: <span class="font-medium">${leg.duration.text}</span>
                </div>
            </div>
            <div class="space-y-3">
        `;

            leg.steps.forEach((step, index) => {
                html += `
                <div class="flex gap-3 p-3 bg-white rounded-lg border">
                    <div class="bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-medium flex-shrink-0">
                        ${index + 1}
                    </div>
                    <div class="flex-grow">
                        <div class="text-sm text-gray-900 mb-1">${step.instructions}</div>
                        <div class="text-xs text-gray-500">${step.distance.text} • ${step.duration.text}</div>
                    </div>
                </div>
            `;
            });

            html += '</div>';

            const directionsPanel = document.getElementById('directions-panel');
            if (directionsPanel) {
                directionsPanel.innerHTML = html;
            }
        }
    };

    function hideMapLoading() {
        const loading = document.getElementById('map-loading');
        if (loading) loading.style.display = 'none';
    }

    function showMapError(message) {
        hideMapLoading();
        const error = document.getElementById('map-error');
        if (error) {
            error.classList.remove('hidden');
            error.querySelector('p').textContent = message;
        }
        console.error('Map error:', message);
    }

    function retryMapLoad() {
        document.getElementById('map-error').classList.add('hidden');
        document.getElementById('map-loading').style.display = 'flex';
        loadGoogleMapsAPI();
    }

    // Load Google Maps API asynchronously
    function loadGoogleMapsAPI() {
        if (window.google && window.google.maps) {
            initMap();
            return;
        }

        console.log('Loading Google Maps API...');

        // Remove existing script if any
        const existingScript = document.querySelector('script[src*="maps.googleapis.com"]');
        if (existingScript) {
            existingScript.remove();
        }

        const script = document.createElement('script');
        script.src = 'https://maps.googleapis.com/maps/api/js?key=AIzaSyDVm7wLX3Be0OHJypf0sDsIxSR3SnJ0Q4s&libraries=geometry,places&loading=async&callback=initMap';
        script.async = true;
        script.defer = true;

        script.onerror = function() {
            console.error('Failed to load Google Maps API');
            showMapError('Failed to load Google Maps API. Please check your API key configuration.');
        };

        document.head.appendChild(script);
    }

    // Action functions
    async function completeRide(rideId) {
        if (!confirm('Are you sure you want to complete this ride?')) {
            return;
        }

        const actualFare = prompt('Enter actual fare (optional):');
        const completionData = {};

        if (actualFare && !isNaN(parseFloat(actualFare))) {
            completionData.actual_fare = parseFloat(actualFare);
        }

        try {
            const response = await fetch(`/admin/rides/${rideId}/complete`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(completionData)
            });

            const data = await response.json();

            if (response.ok && data.success) {
                showNotification('Ride completed successfully', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showNotification('Failed to complete ride: ' + (data.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Error completing ride: Connection failed', 'error');
        }
    }

    async function cancelRide(rideId) {
        const reason = prompt('Please provide a cancellation reason:');
        if (!reason) return;

        try {
            const response = await fetch(`/admin/rides/${rideId}/cancel`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    cancellation_reason: reason
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                showNotification('Ride cancelled successfully', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showNotification('Failed to cancel ride: ' + (data.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Error cancelling ride: Connection failed', 'error');
        }
    }

    function exportRideData() {
        const rideData = {
            ride_id: '{{ $rideId }}',
            status: '{{ $rideStatus }}',
            pickup_address: '{{ $pickupAddress }}',
            dropoff_address: '{{ $dropoffAddress }}',
            passenger_name: '{{ str_replace(['
            "', "
            '"], ['\
            "', "\
            '"], $passengerName) }}',
            driver_name: '{{ str_replace(['
            "', "
            '"], ['\
            "', "\
            '"], $driverName) }}',
            fare: '{{ $actualFare > 0 ? $actualFare : $estimatedFare }}',
            distance: '{{ $distanceKm }}',
            duration: '{{ $durationMinutes }}',
            created_at: '{{ $ride["created_at"] ?? "" }}'
        };

        const jsonString = JSON.stringify(rideData, null, 2);
        const blob = new Blob([jsonString], {
            type: 'application/json'
        });
        const url = URL.createObjectURL(blob);

        const a = document.createElement('a');
        a.href = url;
        a.download = `ride_${rideData.ride_id}_details.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        showNotification('Ride data exported successfully', 'success');
    }

    function showNotification(message, type = 'info') {
        const alertClasses = {
            'success': 'bg-green-100 border-green-400 text-green-700',
            'error': 'bg-red-100 border-red-400 text-red-700',
            'info': 'bg-blue-100 border-blue-400 text-blue-700',
            'warning': 'bg-yellow-100 border-yellow-400 text-yellow-700'
        } [type] || 'bg-gray-100 border-gray-400 text-gray-700';

        const notification = document.createElement('div');
        notification.className = `${alertClasses} px-4 py-3 rounded-lg mb-4 fixed top-4 right-4 z-50 min-w-80 shadow-lg border`;
        notification.innerHTML = `
        <div class="flex justify-between items-center">
            <span class="flex items-center gap-2">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                ${message}
            </span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-lg hover:opacity-70">&times;</button>
        </div>
    `;

        document.body.appendChild(notification);
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    // Initialize page
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Ride details page initialized');

        // Load Google Maps
        loadGoogleMapsAPI();

        // Setup directions toggle
        const toggleButton = document.getElementById('toggle-directions');
        const directionsPanel = document.getElementById('directions-panel');

        if (toggleButton && directionsPanel) {
            toggleButton.addEventListener('click', function() {
                if (directionsPanel.classList.contains('hidden')) {
                    directionsPanel.classList.remove('hidden');
                    this.innerHTML = '<i class="fas fa-eye-slash mr-2"></i>Hide Directions';
                    this.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                    this.classList.add('bg-gray-600', 'hover:bg-gray-700');
                } else {
                    directionsPanel.classList.add('hidden');
                    this.innerHTML = '<i class="fas fa-directions mr-2"></i>Show Directions';
                    this.classList.remove('bg-gray-600', 'hover:bg-gray-700');
                    this.classList.add('bg-blue-600', 'hover:bg-blue-700');
                }
            });
        }
    });

    // Handle window resize for map
    window.addEventListener('resize', function() {
        if (window.google && window.google.maps && map) {
            google.maps.event.trigger(map, 'resize');
        }
    });
</script>
@endpush