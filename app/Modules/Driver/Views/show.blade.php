@extends('admin::layouts.admin')

@section('title', 'Driver Details - ' . $driver['name'])
@section('page-title', 'Driver Details')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-primary">Driver Details</h1>
        <p class="text-gray-600 mt-1">Complete information for {{ $driver['name'] }}</p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('driver.edit', $driver['firebase_uid']) }}" 
           class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            <i class="fas fa-edit mr-2"></i>Edit Driver
        </a>
        <a href="{{ route('driver.index') }}" 
           class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back to List
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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Driver Profile Card -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="text-center mb-6">
                @if(!empty($driver['photo_url']))
                    <img src="{{ $driver['photo_url'] }}" alt="{{ $driver['name'] }}" 
                         class="w-24 h-24 rounded-full mx-auto mb-4 object-cover">
                @else
                    <div class="w-24 h-24 bg-primary rounded-full flex items-center justify-center text-white text-2xl font-bold mx-auto mb-4">
                        {{ strtoupper(substr($driver['name'], 0, 1)) }}
                    </div>
                @endif
                <h2 class="text-xl font-semibold text-gray-900">{{ $driver['name'] }}</h2>
                <p class="text-gray-600">{{ $driver['email'] }}</p>
                <p class="text-gray-500">ID: {{ $driver['firebase_uid'] }}</p>
            </div>

            <!-- Status Badges -->
            <div class="space-y-2 mb-6">
                @if($driver['status'] === 'active')
                    <span class="block w-full text-center px-3 py-2 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        <i class="fas fa-check-circle mr-1"></i>Active Driver
                    </span>
                @elseif($driver['status'] === 'suspended')
                    <span class="block w-full text-center px-3 py-2 rounded-full text-sm font-medium bg-orange-100 text-orange-800">
                        <i class="fas fa-pause-circle mr-1"></i>Suspended
                    </span>
                @elseif($driver['status'] === 'pending')
                    <span class="block w-full text-center px-3 py-2 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                        <i class="fas fa-clock mr-1"></i>Pending Approval
                    </span>
                @else
                    <span class="block w-full text-center px-3 py-2 rounded-full text-sm font-medium bg-red-100 text-red-800">
                        <i class="fas fa-times-circle mr-1"></i>Inactive
                    </span>
                @endif

                @if($driver['verification_status'] === 'verified')
                    <span class="block w-full text-center px-3 py-2 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                        <i class="fas fa-shield-check mr-1"></i>Verified
                    </span>
                @elseif($driver['verification_status'] === 'rejected')
                    <span class="block w-full text-center px-3 py-2 rounded-full text-sm font-medium bg-red-100 text-red-800">
                        <i class="fas fa-shield-times mr-1"></i>Verification Rejected
                    </span>
                @else
                    <span class="block w-full text-center px-3 py-2 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                        <i class="fas fa-shield-question mr-1"></i>Verification Pending
                    </span>
                @endif

                @if($driver['availability_status'] === 'available')
                    <span class="block w-full text-center px-3 py-2 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        <i class="fas fa-circle mr-1"></i>Available
                    </span>
                @elseif($driver['availability_status'] === 'busy')
                    <span class="block w-full text-center px-3 py-2 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                        <i class="fas fa-circle mr-1"></i>Busy
                    </span>
                @else
                    <span class="block w-full text-center px-3 py-2 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                        <i class="fas fa-circle mr-1"></i>Offline
                    </span>
                @endif
            </div>

            <!-- Quick Actions -->
            <div class="space-y-2">
                @if($driver['status'] !== 'active')
                    <button onclick="toggleDriverStatus('{{ $driver['firebase_uid'] }}', 'activate')" 
                            class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-check mr-2"></i>Activate Driver
                    </button>
                @else
                    <button onclick="toggleDriverStatus('{{ $driver['firebase_uid'] }}', 'deactivate')" 
                            class="w-full bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-colors">
                        <i class="fas fa-ban mr-2"></i>Deactivate Driver
                    </button>
                @endif

                @if($driver['verification_status'] !== 'verified')
                    <button onclick="toggleDriverStatus('{{ $driver['firebase_uid'] }}', 'verify')" 
                            class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-shield-check mr-2"></i>Verify Driver
                    </button>
                @endif

                @if($driver['status'] !== 'suspended')
                    <button onclick="toggleDriverStatus('{{ $driver['firebase_uid'] }}', 'suspend')" 
                            class="w-full bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 transition-colors">
                        <i class="fas fa-pause mr-2"></i>Suspend Driver
                    </button>
                @endif
            </div>
        </div>

        <!-- Performance Stats -->
        @if(isset($rideStats) && count($rideStats) > 0)
        <div class="bg-white rounded-lg shadow-sm border p-6 mt-6">
            <h3 class="text-lg font-semibold mb-4">
                <i class="fas fa-chart-line mr-2 text-primary"></i>Performance Stats
            </h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Rating</span>
                    <span class="font-semibold">
                        <i class="fas fa-star text-yellow-400 mr-1"></i>
                        {{ number_format($rideStats['average_rating'] ?? 0, 1) }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Total Rides</span>
                    <span class="font-semibold">{{ number_format($rideStats['total_rides'] ?? 0) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Completed</span>
                    <span class="font-semibold text-green-600">{{ number_format($rideStats['completed_rides'] ?? 0) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Completion Rate</span>
                    <span class="font-semibold">{{ number_format($rideStats['completion_rate'] ?? 0, 1) }}%</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Total Earnings</span>
                    <span class="font-semibold text-green-600">${{ number_format($rideStats['total_earnings'] ?? 0, 2) }}</span>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Personal Information -->
        <div class="bg-white rounded-lg shadow-sm border">
            <div class="p-6 border-b">
                <h3 class="text-lg font-semibold">
                    <i class="fas fa-user mr-2 text-primary"></i>Personal Information
                </h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                        <p class="text-gray-900">{{ $driver['name'] }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <p class="text-gray-900">{{ $driver['email'] }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                        <p class="text-gray-900">{{ $driver['phone'] ?? 'Not provided' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date of Birth</label>
                        <p class="text-gray-900">{{ $driver['date_of_birth'] ? \Carbon\Carbon::parse($driver['date_of_birth'])->format('M d, Y') : 'Not provided' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Gender</label>
                        <p class="text-gray-900">{{ $driver['gender'] ? ucfirst($driver['gender']) : 'Not specified' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Join Date</label>
                        <p class="text-gray-900">{{ $driver['join_date'] ? \Carbon\Carbon::parse($driver['join_date'])->format('M d, Y') : 'Unknown' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Address Information -->
        <div class="bg-white rounded-lg shadow-sm border">
            <div class="p-6 border-b">
                <h3 class="text-lg font-semibold">
                    <i class="fas fa-map-marker-alt mr-2 text-primary"></i>Address Information
                </h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                        <p class="text-gray-900">{{ $driver['address'] ?? 'Not provided' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
                        <p class="text-gray-900">{{ $driver['city'] ?? 'Not provided' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">State</label>
                        <p class="text-gray-900">{{ $driver['state'] ?? 'Not provided' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Postal Code</label>
                        <p class="text-gray-900">{{ $driver['postal_code'] ?? 'Not provided' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                        <p class="text-gray-900">{{ $driver['country'] ?? 'Not provided' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- License Information -->
        <div class="bg-white rounded-lg shadow-sm border">
            <div class="p-6 border-b">
                <h3 class="text-lg font-semibold">
                    <i class="fas fa-id-card mr-2 text-primary"></i>License Information
                </h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">License Number</label>
                        <p class="text-gray-900">{{ $driver['license_number'] ?? 'Not provided' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">License Expiry</label>
                        <p class="text-gray-900">
                            {{ $driver['license_expiry'] ? \Carbon\Carbon::parse($driver['license_expiry'])->format('M d, Y') : 'Not provided' }}
                            @if($driver['license_expiry'])
                                @php
                                    $expiry = \Carbon\Carbon::parse($driver['license_expiry']);
                                    $isExpired = $expiry->isPast();
                                    $isExpiringSoon = $expiry->diffInDays(now()) <= 30;
                                @endphp
                                @if($isExpired)
                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>Expired
                                    </span>
                                @elseif($isExpiringSoon)
                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <i class="fas fa-clock mr-1"></i>Expiring Soon
                                    </span>
                                @endif
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs for Additional Information -->
        <div class="bg-white rounded-lg shadow-sm border">
            <div class="border-b">
                <nav class="flex -mb-px">
                    <button onclick="showTab('vehicles')" id="tab-vehicles" 
                            class="tab-button px-6 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        <i class="fas fa-car mr-2"></i>Vehicles ({{ count($vehicles) }})
                    </button>
                    <button onclick="showTab('rides')" id="tab-rides"
                            class="tab-button px-6 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        <i class="fas fa-route mr-2"></i>Recent Rides ({{ count($rides) }})
                    </button>
                    <button onclick="showTab('documents')" id="tab-documents"
                            class="tab-button px-6 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        <i class="fas fa-file-alt mr-2"></i>Documents ({{ count($documents) }})
                    </button>
                    <button onclick="showTab('activities')" id="tab-activities"
                            class="tab-button px-6 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        <i class="fas fa-history mr-2"></i>Activities ({{ count($activities) }})
                    </button>
                </nav>
            </div>

            <!-- Vehicles Tab -->
            <div id="content-vehicles" class="tab-content p-6">
                @if(count($vehicles) > 0)
                    <div class="space-y-4">
                        @foreach($vehicles as $vehicle)
                        <div class="border rounded-lg p-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-semibold text-gray-900">
                                        {{ $vehicle['year'] ?? '' }} {{ $vehicle['make'] ?? '' }} {{ $vehicle['model'] ?? '' }}
                                    </h4>
                                    <p class="text-gray-600">{{ $vehicle['color'] ?? 'Color not specified' }}</p>
                                    <p class="text-sm text-gray-500">
                                        License Plate: {{ $vehicle['license_plate'] ?? 'Not provided' }}
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        Type: {{ ucfirst($vehicle['vehicle_type'] ?? 'Unknown') }} | 
                                        Seats: {{ $vehicle['seats'] ?? 'Unknown' }}
                                    </p>
                                </div>
                                <div class="flex flex-col items-end gap-2">
                                    @if(($vehicle['status'] ?? 'active') === 'active')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i>Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            <i class="fas fa-times-circle mr-1"></i>Inactive
                                        </span>
                                    @endif
                                    
                                    @if(($vehicle['is_primary'] ?? false))
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <i class="fas fa-star mr-1"></i>Primary
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <i class="fas fa-car text-4xl text-gray-400 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Vehicles</h3>
                        <p class="text-gray-500">This driver hasn't added any vehicles yet.</p>
                    </div>
                @endif
            </div>

            <!-- Rides Tab -->
            <div id="content-rides" class="tab-content p-6 hidden">
                @if(count($rides) > 0)
                    <div class="space-y-4">
                        @foreach($rides as $ride)
                        <div class="border rounded-lg p-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-semibold text-gray-900">
                                        Ride #{{ substr($ride['id'] ?? 'unknown', 0, 8) }}
                                    </h4>
                                    <p class="text-gray-600">
                                        {{ $ride['pickup_address'] ?? 'Unknown pickup' }} → {{ $ride['destination_address'] ?? 'Unknown destination' }}
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        {{ isset($ride['created_at']) ? \Carbon\Carbon::parse($ride['created_at'])->format('M d, Y H:i') : 'Unknown date' }}
                                    </p>
                                    @if(isset($ride['distance_km']) || isset($ride['duration_minutes']))
                                        <p class="text-sm text-gray-500">
                                            @if(isset($ride['distance_km']))
                                                {{ number_format($ride['distance_km'], 1) }}km
                                            @endif
                                            @if(isset($ride['duration_minutes']))
                                                | {{ $ride['duration_minutes'] }}min
                                            @endif
                                        </p>
                                    @endif
                                </div>
                                <div class="flex flex-col items-end gap-2">
                                    @php
                                        $rideStatus = $ride['status'] ?? 'unknown';
                                        $statusClasses = [
                                            'completed' => 'bg-green-100 text-green-800',
                                            'cancelled' => 'bg-red-100 text-red-800',
                                            'in_progress' => 'bg-blue-100 text-blue-800',
                                            'pending' => 'bg-yellow-100 text-yellow-800'
                                        ];
                                        $statusClass = $statusClasses[$rideStatus] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                                        {{ ucfirst(str_replace('_', ' ', $rideStatus)) }}
                                    </span>
                                    
                                    @if(isset($ride['driver_earnings']) && $ride['driver_earnings'] > 0)
                                        <span class="text-sm font-medium text-green-600">
                                            ${{ number_format($ride['driver_earnings'], 2) }}
                                        </span>
                                    @endif
                                    
                                    @if(isset($ride['driver_rating']) && $ride['driver_rating'] > 0)
                                        <span class="text-sm text-gray-500">
                                            <i class="fas fa-star text-yellow-400"></i>
                                            {{ number_format($ride['driver_rating'], 1) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <i class="fas fa-route text-4xl text-gray-400 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Rides</h3>
                        <p class="text-gray-500">This driver hasn't completed any rides yet.</p>
                    </div>
                @endif
            </div>

            <!-- Documents Tab -->
            <div id="content-documents" class="tab-content p-6 hidden">
                @if(count($documents) > 0)
                    <div class="space-y-4">
                        @foreach($documents as $document)
                        <div class="border rounded-lg p-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-semibold text-gray-900">
                                        {{ $document['document_name'] ?? 'Untitled Document' }}
                                    </h4>
                                    <p class="text-gray-600">{{ ucfirst(str_replace('_', ' ', $document['document_type'] ?? 'unknown')) }}</p>
                                    @if(!empty($document['document_number']))
                                        <p class="text-sm text-gray-500">Number: {{ $document['document_number'] }}</p>
                                    @endif
                                    @if(!empty($document['expiry_date']))
                                        <p class="text-sm text-gray-500">
                                            Expires: {{ \Carbon\Carbon::parse($document['expiry_date'])->format('M d, Y') }}
                                        </p>
                                    @endif
                                    <p class="text-sm text-gray-500">
                                        Uploaded: {{ isset($document['created_at']) ? \Carbon\Carbon::parse($document['created_at'])->format('M d, Y') : 'Unknown' }}
                                    </p>
                                </div>
                                <div class="flex flex-col items-end gap-2">
                                    @php
                                        $docStatus = $document['verification_status'] ?? 'pending';
                                        $statusClasses = [
                                            'verified' => 'bg-green-100 text-green-800',
                                            'rejected' => 'bg-red-100 text-red-800',
                                            'pending' => 'bg-yellow-100 text-yellow-800'
                                        ];
                                        $statusClass = $statusClasses[$docStatus] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                                        @if($docStatus === 'verified')
                                            <i class="fas fa-check-circle mr-1"></i>Verified
                                        @elseif($docStatus === 'rejected')
                                            <i class="fas fa-times-circle mr-1"></i>Rejected
                                        @else
                                            <i class="fas fa-clock mr-1"></i>Pending
                                        @endif
                                    </span>
                                    
                                    @if(!empty($document['file_url']))
                                        <a href="{{ $document['file_url'] }}" target="_blank" 
                                           class="text-primary hover:text-blue-700 text-sm">
                                            <i class="fas fa-download mr-1"></i>View
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <i class="fas fa-file-alt text-4xl text-gray-400 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Documents</h3>
                        <p class="text-gray-500">This driver hasn't uploaded any documents yet.</p>
                    </div>
                @endif
            </div>

            <!-- Activities Tab -->
            <div id="content-activities" class="tab-content p-6 hidden">
                @if(count($activities) > 0)
                    <div class="space-y-4">
                        @foreach($activities as $activity)
                        <div class="border-l-4 border-blue-500 pl-4 py-2">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-semibold text-gray-900">
                                        {{ $activity['activity_title'] ?? 'Activity' }}
                                    </h4>
                                    <p class="text-gray-600">{{ $activity['activity_description'] ?? 'No description' }}</p>
                                    <p class="text-sm text-gray-500">
                                        {{ isset($activity['created_at']) ? \Carbon\Carbon::parse($activity['created_at'])->format('M d, Y H:i') : 'Unknown time' }}
                                    </p>
                                </div>
                                <div>
                                    @php
                                        $activityType = $activity['activity_type'] ?? 'general';
                                        $typeClasses = [
                                            'ride_completed' => 'bg-green-100 text-green-800',
                                            'document_uploaded' => 'bg-blue-100 text-blue-800',
                                            'status_change' => 'bg-yellow-100 text-yellow-800',
                                            'profile_update' => 'bg-purple-100 text-purple-800'
                                        ];
                                        $typeClass = $typeClasses[$activityType] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $typeClass }}">
                                        {{ ucfirst(str_replace('_', ' ', $activityType)) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <i class="fas fa-history text-4xl text-gray-400 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Activities</h3>
                        <p class="text-gray-500">No recent activity found for this driver.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function showTab(tabName) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        
        // Remove active classes from all tabs
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('border-primary', 'text-primary');
            button.classList.add('border-transparent', 'text-gray-500');
        });
        
        // Show selected tab content
        document.getElementById('content-' + tabName).classList.remove('hidden');
        
        // Make selected tab active
        const activeTab = document.getElementById('tab-' + tabName);
        activeTab.classList.remove('border-transparent', 'text-gray-500');
        activeTab.classList.add('border-primary', 'text-primary');
    }

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

    async function toggleDriverStatus(driverId, action) {
        const actionText = action === 'activate' ? 'activate' : action === 'verify' ? 'verify' : action === 'suspend' ? 'suspend' : 'deactivate';
        if (!confirm(`Are you sure you want to ${actionText} this driver?`)) {
            return;
        }
        
        try {
            const response = await fetch(`{{ route('driver.toggle-status', '') }}/${driverId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ action: action })
            });
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                showNotification(`Driver ${actionText}d successfully`, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showNotification(`Failed to ${actionText} driver: ` + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Toggle status error:', error);
            showNotification(`Error ${actionText}ing driver: Connection failed`, 'error');
        }
    }
    
    // Initialize the first tab as active
    document.addEventListener('DOMContentLoaded', function() {
        showTab('vehicles');
    });
</script>
@endpush