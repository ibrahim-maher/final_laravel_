@extends('admin::layouts.admin')

@section('title', 'Ride Management')
@section('page-title', 'Ride Management')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-primary">Ride Management</h1>
        <p class="text-gray-600 mt-1">Manage and monitor ride bookings (Total: {{ $totalRides }})</p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('admin.rides.create') }}" 
           class="bg-success text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors">
            <i class="fas fa-plus mr-2"></i>Create Ride
        </a>
        <a href="{{ route('admin.rides.statistics') }}" 
           class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            <i class="fas fa-chart-bar mr-2"></i>Statistics
        </a>
        <a href="{{ route('admin.dashboard') }}" 
           class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
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

<!-- Quick Stats -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                <i class="fas fa-route text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Total Rides</p>
                <p class="text-2xl font-bold text-gray-900">{{ $totalRides }}</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                <i class="fas fa-check-circle text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Completed</p>
                <p class="text-2xl font-bold text-gray-900">
                    {{ $rides->where('status', 'completed')->count() }}
                </p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                <i class="fas fa-clock text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">In Progress</p>
                <p class="text-2xl font-bold text-gray-900">
                    {{ $rides->whereIn('status', ['in_progress', 'accepted', 'driver_arrived'])->count() }}
                </p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100 text-red-600 mr-4">
                <i class="fas fa-times-circle text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Cancelled</p>
                <p class="text-2xl font-bold text-gray-900">
                    {{ $rides->where('status', 'cancelled')->count() }}
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Filters and Search -->
<div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
    <form method="GET" action="{{ route('admin.rides.index') }}" class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-64">
            <input type="text" name="search" value="{{ $search ?? '' }}" 
                   placeholder="Search by ride ID, driver, passenger, or location..."
                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
        </div>
        <div class="min-w-48">
            <select name="status" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Status</option>
                @foreach($rideStatuses as $key => $label)
                    <option value="{{ $key }}" {{ ($status ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-48">
            <select name="ride_type" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Ride Types</option>
                @foreach($rideTypes as $key => $label)
                    <option value="{{ $key }}" {{ ($ride_type ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-48">
            <select name="payment_status" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Payment Status</option>
                @foreach($paymentStatuses as $key => $label)
                    <option value="{{ $key }}" {{ ($payment_status ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-40">
            <input type="date" name="date_from" value="{{ $date_from ?? '' }}" 
                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
        </div>
        <div class="min-w-40">
            <input type="date" name="date_to" value="{{ $date_to ?? '' }}" 
                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
        </div>
        <div class="min-w-32">
            <select name="limit" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="25" {{ ($limit ?? 50) == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ ($limit ?? 50) == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ ($limit ?? 50) == 100 ? 'selected' : '' }}>100</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-search mr-2"></i>Search
            </button>
            <a href="{{ route('admin.rides.index') }}" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                <i class="fas fa-times mr-2"></i>Clear
            </a>
        </div>
    </form>
</div>

<!-- Rides Table -->
<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    <div class="p-6 border-b">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold">
                <i class="fas fa-route mr-2 text-primary"></i>Rides List
            </h2>
            <div class="flex gap-2">
                <button onclick="exportRides('csv')" class="text-primary hover:bg-blue-50 px-3 py-1 rounded">
                    <i class="fas fa-file-csv mr-1"></i>CSV Export
                </button>
            </div>
        </div>
    </div>
    
    @if(count($rides) > 0)
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Ride Info
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Driver & Passenger
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Route
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status & Type
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Fare & Distance
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Date & Time
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($rides as $ride)
                @php
                    $rideId = $ride['id'] ?? $ride['ride_id'] ?? 'Unknown';
                    $driverName = $ride['driver_name'] ?? 'Unknown Driver';
                    $passengerName = $ride['passenger_name'] ?? 'Unknown Passenger';
                    $rideStatus = $ride['status'] ?? 'unknown';
                    $rideType = $ride['ride_type'] ?? 'standard';
                    $paymentStatus = $ride['payment_status'] ?? 'pending';
                    
                    $createdAt = null;
                    $createdDisplay = 'Unknown';
                    $createdHuman = 'Date not available';
                    
                    if (!empty($ride['created_at'])) {
                        try {
                            $createdAt = \Carbon\Carbon::parse($ride['created_at']);
                            $createdDisplay = $createdAt->format('M d, Y g:i A');
                            $createdHuman = $createdAt->diffForHumans();
                        } catch (\Exception $e) {
                            // Handle date parsing error
                        }
                    }
                    
                    $fare = $ride['actual_fare'] ?? $ride['estimated_fare'] ?? 0;
                    $distance = $ride['distance_km'] ?? 0;
                    $duration = $ride['duration_minutes'] ?? 0;
                @endphp
                <tr data-ride-id="{{ $rideId }}" class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div>
                            <div class="font-medium text-gray-900">{{ $rideId }}</div>
                            <div class="text-sm text-gray-500">
                                {{ ucfirst(str_replace('_', ' ', $rideType)) }}
                            </div>
                            @if(!empty($ride['special_requests']))
                                <div class="text-xs text-blue-600">
                                    <i class="fas fa-info-circle mr-1"></i>Special requests
                                </div>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="space-y-1">
                            <div class="text-sm">
                                <span class="font-medium text-gray-700">Driver:</span>
                                <span class="text-gray-900">{{ $driverName }}</span>
                            </div>
                            <div class="text-sm">
                                <span class="font-medium text-gray-700">Passenger:</span>
                                <span class="text-gray-900">{{ $passengerName }}</span>
                            </div>
                            @if(!empty($ride['passenger_phone']))
                                <div class="text-xs text-gray-500">{{ $ride['passenger_phone'] }}</div>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="space-y-2 text-sm">
                            <div>
                                <div class="font-medium text-gray-700 flex items-center">
                                    <i class="fas fa-circle text-green-500 mr-2 text-xs"></i>From
                                </div>
                                <div class="text-gray-600 ml-4">{{ $ride['pickup_address'] ?? 'Unknown pickup' }}</div>
                            </div>
                            <div>
                                <div class="font-medium text-gray-700 flex items-center">
                                    <i class="fas fa-circle text-red-500 mr-2 text-xs"></i>To
                                </div>
                                <div class="text-gray-600 ml-4">{{ $ride['dropoff_address'] ?? 'Unknown destination' }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="space-y-2">
                            <!-- Ride Status -->
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
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <div class="space-y-1">
                            <div class="text-gray-900 font-medium">${{ number_format($fare, 2) }}</div>
                            @if($distance > 0)
                                <div class="text-gray-500">{{ number_format($distance, 1) }} km</div>
                            @endif
                            @if($duration > 0)
                                <div class="text-gray-500">{{ $duration }} min</div>
                            @endif
                            @if(!empty($ride['payment_method']))
                                <div class="text-xs text-gray-500">{{ ucfirst($ride['payment_method']) }}</div>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <div>{{ $createdDisplay }}</div>
                        <div class="text-xs text-gray-500">{{ $createdHuman }}</div>
                        @if(!empty($ride['completed_at']) && $rideStatus === 'completed')
                            <div class="text-xs text-green-600">
                                Completed: {{ \Carbon\Carbon::parse($ride['completed_at'])->format('g:i A') }}
                            </div>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.rides.edit', $rideId) }}" 
                               class="text-green-600 hover:text-green-800 p-1" title="Edit Ride">
                                <i class="fas fa-edit"></i>
                            </a>
                            @if(in_array($rideStatus, ['pending', 'requested', 'accepted']))
                                <button onclick="updateRideStatus('{{ $rideId }}', 'in_progress')" 
                                        class="text-blue-600 hover:text-blue-800 p-1" title="Start Ride">
                                    <i class="fas fa-play"></i>
                                </button>
                            @endif
                            @if(in_array($rideStatus, ['in_progress', 'driver_arrived']))
                                <button onclick="completeRide('{{ $rideId }}')" 
                                        class="text-green-600 hover:text-green-800 p-1" title="Complete Ride">
                                    <i class="fas fa-check"></i>
                                </button>
                            @endif
                            @if(!in_array($rideStatus, ['completed', 'cancelled']))
                                <button onclick="cancelRide('{{ $rideId }}')" 
                                        class="text-red-600 hover:text-red-800 p-1" title="Cancel Ride">
                                    <i class="fas fa-times"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <!-- Pagination Info -->
    <div class="px-6 py-3 border-t bg-gray-50">
        <div class="flex justify-between items-center">
            <div class="text-sm text-gray-700">
                Showing {{ count($rides) }} of {{ $totalRides }} rides
            </div>
            <div class="text-sm text-gray-500">
                Filtered results based on current criteria
            </div>
        </div>
    </div>
    @else
    <div class="text-center py-12">
        <i class="fas fa-route text-4xl text-gray-400 mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No Rides Found</h3>
        <p class="text-gray-500 mb-4">
            @if(isset($search) && $search)
                No rides match your search criteria "{{ $search }}". Try adjusting your filters.
            @else
                No rides found in the system. Create some rides to get started.
            @endif
        </p>
        <div class="flex justify-center gap-3">
            <a href="{{ route('admin.rides.create') }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>Create First Ride
            </a>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function showNotification(message, type = 'info') {
        const alertClass = {
            'success': 'bg-green-100 border-green-400 text-green-700',
            'error': 'bg-red-100 border-red-400 text-red-700',
            'info': 'bg-blue-100 border-blue-400 text-blue-700',
            'warning': 'bg-yellow-100 border-yellow-400 text-yellow-700'
        }[type] || 'bg-gray-100 border-gray-400 text-gray-700';

        const notification = document.createElement('div');
        notification.className = `${alertClass} px-4 py-3 rounded mb-4 fixed top-4 right-4 z-50 min-w-80 shadow-lg`;
        notification.innerHTML = `
            <div class="flex justify-between items-center">
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-lg">&times;</button>
            </div>
        `;
        
        document.body.appendChild(notification);
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    async function updateRideStatus(rideId, status) {
        const statusText = status.replace('_', ' ');
        if (!confirm(`Are you sure you want to ${statusText} this ride?`)) {
            return;
        }
        
        try {
            const response = await fetch(`{{ route('admin.rides.update-status', '') }}/${rideId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: status })
            });
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                showNotification(`Ride ${statusText} successfully`, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showNotification(`Failed to ${statusText} ride: ` + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Update status error:', error);
            showNotification(`Error ${statusText} ride: Connection failed`, 'error');
        }
    }

    async function completeRide(rideId) {
        if (!confirm('Are you sure you want to complete this ride?')) {
            return;
        }
        
        // Optionally collect completion data
        const actualFare = prompt('Enter actual fare (optional):');
        const completionData = {};
        
        if (actualFare && !isNaN(parseFloat(actualFare))) {
            completionData.actual_fare = parseFloat(actualFare);
        }
        
        try {
            const response = await fetch(`{{ route('admin.rides.complete', '') }}/${rideId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(completionData)
            });
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                showNotification('Ride completed successfully', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showNotification('Failed to complete ride: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Complete ride error:', error);
            showNotification('Error completing ride: Connection failed', 'error');
        }
    }

    async function cancelRide(rideId) {
        const reason = prompt('Please provide a cancellation reason:');
        if (!reason) return;
        
        try {
            const response = await fetch(`{{ route('admin.rides.cancel', '') }}/${rideId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ cancellation_reason: reason })
            });
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                showNotification('Ride cancelled successfully', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showNotification('Failed to cancel ride: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Cancel ride error:', error);
            showNotification('Error cancelling ride: Connection failed', 'error');
        }
    }

    async function exportRides(format) {
        try {
            const url = new URL('{{ route("admin.rides.export") }}');
            url.searchParams.append('format', format);
            
            // Add current filters
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('search')) url.searchParams.append('search', urlParams.get('search'));
            if (urlParams.get('status')) url.searchParams.append('status', urlParams.get('status'));
            if (urlParams.get('ride_type')) url.searchParams.append('ride_type', urlParams.get('ride_type'));
            if (urlParams.get('payment_status')) url.searchParams.append('payment_status', urlParams.get('payment_status'));
            if (urlParams.get('date_from')) url.searchParams.append('date_from', urlParams.get('date_from'));
            if (urlParams.get('date_to')) url.searchParams.append('date_to', urlParams.get('date_to'));
            
            window.open(url.toString(), '_blank');
            showNotification('Export started. Download will begin shortly.', 'info');
            
        } catch (error) {
            console.error('Export error:', error);
            showNotification('Error exporting rides', 'error');
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Ride management page initialized');
    });
</script>
@endpush="{{ route('admin.rides.show', $rideId) }}" 
                               class="text-primary hover:text-blue-700 p-1" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href