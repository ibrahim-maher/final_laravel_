@extends('admin::layouts.admin')

@section('title', 'Driver Details - ' . $driver['name'])
@section('page-title', 'Driver Details')

@section('content')
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div class="flex items-center space-x-4">
            <a href="{{ route('admin.drivers.index') }}" 
               class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-primary">{{ $driver['name'] }}</h1>
                <p class="text-gray-600 mt-1">Driver ID: {{ $driver['firebase_uid'] }}</p>
            </div>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.drivers.edit', $driver['firebase_uid']) }}" 
               class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                <i class="fas fa-edit mr-2"></i>Edit Driver
            </a>
            <button onclick="exportDriverData('{{ $driver['firebase_uid'] }}')" 
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-download mr-2"></i>Export Data
            </button>
            <div class="relative">
                <button onclick="toggleActionsMenu()" 
                        class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-ellipsis-v mr-2"></i>Actions
                </button>
                <div id="actionsMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg border hidden z-10">
                    @if($driver['status'] === 'active')
                        <button onclick="toggleDriverStatus('{{ $driver['firebase_uid'] }}', 'deactivate')" 
                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-ban mr-2"></i>Deactivate Driver
                        </button>
                    @else
                        <button onclick="toggleDriverStatus('{{ $driver['firebase_uid'] }}', 'activate')" 
                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-check-circle mr-2"></i>Activate Driver
                        </button>
                    @endif
                    @if($driver['verification_status'] !== 'verified')
                        <button onclick="toggleDriverStatus('{{ $driver['firebase_uid'] }}', 'verify')" 
                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-shield-check mr-2"></i>Verify Driver
                        </button>
                    @endif
                    <button onclick="sendNotification('{{ $driver['firebase_uid'] }}')" 
                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-bell mr-2"></i>Send Notification
                    </button>
                    <div class="border-t border-gray-100"></div>
                    <button onclick="deleteDriver('{{ $driver['firebase_uid'] }}', '{{ addslashes($driver['name']) }}')" 
                            class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                        <i class="fas fa-trash mr-2"></i>Delete Driver
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
    </div>
@endif

@if(session('completion_status') || isset($profileCompletion))
    <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-6">
        <div class="flex items-center justify-between">
            <div>
                <i class="fas fa-info-circle mr-2"></i>Profile Completion Status
            </div>
            <div class="text-sm">
                {{ $profileCompletion['completion_percentage'] ?? 0 }}% Complete
            </div>
        </div>
        @if(isset($profileCompletion['missing_fields']) && count($profileCompletion['missing_fields']) > 0)
            <div class="mt-2 text-sm">
                Missing: {{ implode(', ', $profileCompletion['missing_fields']) }}
            </div>
        @endif
    </div>
@endif

<!-- Driver Overview -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Driver Profile -->
    <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-start space-x-6">
            <div class="flex-shrink-0">
                @if(!empty($driver['photo_url']))
                    <img src="{{ $driver['photo_url'] }}" alt="{{ $driver['name'] }}" 
                         class="w-24 h-24 rounded-full object-cover">
                @else
                    <div class="w-24 h-24 bg-primary rounded-full flex items-center justify-center text-white text-2xl font-bold">
                        {{ strtoupper(substr($driver['name'], 0, 1)) }}
                    </div>
                @endif
            </div>
            <div class="flex-1">
                <div class="flex items-center space-x-3 mb-2">
                    <h2 class="text-2xl font-bold text-gray-900">{{ $driver['name'] }}</h2>
                    <!-- Status Badges -->
                    @if($driver['status'] === 'active')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="fas fa-check-circle mr-1"></i>Active
                        </span>
                    @elseif($driver['status'] === 'suspended')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                            <i class="fas fa-pause-circle mr-1"></i>Suspended
                        </span>
                    @elseif($driver['status'] === 'pending')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            <i class="fas fa-clock mr-1"></i>Pending
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <i class="fas fa-times-circle mr-1"></i>Inactive
                        </span>
                    @endif

                    @if($driver['verification_status'] === 'verified')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <i class="fas fa-shield-check mr-1"></i>Verified
                        </span>
                    @elseif($driver['verification_status'] === 'rejected')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <i class="fas fa-shield-times mr-1"></i>Rejected
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            <i class="fas fa-shield-question mr-1"></i>Pending Verification
                        </span>
                    @endif

                    @if(isset($driver['availability_status']))
                        @if($driver['availability_status'] === 'available')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-circle mr-1"></i>Available
                            </span>
                        @elseif($driver['availability_status'] === 'busy')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                <i class="fas fa-circle mr-1"></i>Busy
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                <i class="fas fa-circle mr-1"></i>Offline
                            </span>
                        @endif
                    @endif
                </div>

                <div class="space-y-2">
                    <p class="text-gray-600">
                        <i class="fas fa-envelope mr-2"></i>{{ $driver['email'] }}
                    </p>
                    @if(!empty($driver['phone']))
                        <p class="text-gray-600">
                            <i class="fas fa-phone mr-2"></i>{{ $driver['phone'] }}
                        </p>
                    @endif
                    @if(!empty($driver['license_number']))
                        <p class="text-gray-600">
                            <i class="fas fa-id-card mr-2"></i>License: {{ $driver['license_number'] }}
                        </p>
                    @endif
                    @if(!empty($driver['city']) || !empty($driver['state']))
                        <p class="text-gray-600">
                            <i class="fas fa-map-marker-alt mr-2"></i>
                            {{ $driver['city'] }}{{ !empty($driver['city']) && !empty($driver['state']) ? ', ' : '' }}{{ $driver['state'] }}
                        </p>
                    @endif
                    <p class="text-gray-600">
                        <i class="fas fa-calendar mr-2"></i>
                        Joined {{ \Carbon\Carbon::parse($driver['join_date'] ?? $driver['created_at'] ?? now())->format('M d, Y') }}
                        ({{ \Carbon\Carbon::parse($driver['join_date'] ?? $driver['created_at'] ?? now())->diffForHumans() }})
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Metrics -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Performance Metrics</h3>
        <div class="space-y-4">
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Rating</span>
                <div class="flex items-center">
                    <i class="fas fa-star text-yellow-400 mr-1"></i>
                    <span class="font-semibold">{{ number_format($driver['rating'] ?? $rideStats['average_rating'] ?? 0, 1) }}</span>
                </div>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Total Rides</span>
                <span class="font-semibold">{{ number_format($driver['total_rides'] ?? $rideStats['total_rides'] ?? 0) }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Total Earnings</span>
                <span class="font-semibold">${{ number_format($driver['total_earnings'] ?? $rideStats['total_earnings'] ?? 0, 2) }}</span>
            </div>
            @if(isset($performanceMetrics))
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Completion Rate</span>
                    <span class="font-semibold">{{ number_format($performanceMetrics['completion_rate'] ?? 0, 1) }}%</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Average Trip Time</span>
                    <span class="font-semibold">{{ $performanceMetrics['avg_trip_time'] ?? 'N/A' }}</span>
                </div>
                @if(isset($performanceMetrics['acceptance_rate']))
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Acceptance Rate</span>
                        <span class="font-semibold">{{ number_format($performanceMetrics['acceptance_rate'], 1) }}%</span>
                    </div>
                @endif
                @if(isset($performanceMetrics['cancellation_rate']))
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Cancellation Rate</span>
                        <span class="font-semibold">{{ number_format($performanceMetrics['cancellation_rate'], 1) }}%</span>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

<!-- Tabbed Content -->
<div class="bg-white rounded-lg shadow-sm border">
    <div class="border-b">
        <nav class="flex space-x-8 px-6" aria-label="Tabs">
            <button onclick="showTab('personal')" id="personal-tab" 
                    class="tab-button border-b-2 border-blue-500 py-4 px-1 text-sm font-medium text-blue-600">
                <i class="fas fa-user mr-2"></i>Personal Info
            </button>
            <button onclick="showTab('vehicles')" id="vehicles-tab" 
                    class="tab-button border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                <i class="fas fa-car mr-2"></i>Vehicles ({{ count($vehicles) }})
            </button>
            <button onclick="showTab('documents')" id="documents-tab" 
                    class="tab-button border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                <i class="fas fa-file-alt mr-2"></i>Documents ({{ count($documents) }})
            </button>
            <button onclick="showTab('rides')" id="rides-tab" 
                    class="tab-button border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                <i class="fas fa-route mr-2"></i>Recent Rides ({{ count($rides) }})
            </button>
            <button onclick="showTab('activities')" id="activities-tab" 
                    class="tab-button border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                <i class="fas fa-history mr-2"></i>Activities ({{ count($activities) }})
            </button>
        </nav>
    </div>

    <!-- Personal Info Tab -->
    <div id="personal-content" class="tab-content p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h4 class="text-md font-semibold text-gray-900 mb-4">Basic Information</h4>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Full Name</dt>
                        <dd class="text-sm text-gray-900">{{ $driver['name'] ?? 'Not provided' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="text-sm text-gray-900">{{ $driver['email'] ?? 'Not provided' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Phone</dt>
                        <dd class="text-sm text-gray-900">{{ $driver['phone'] ?? 'Not provided' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Date of Birth</dt>
                        <dd class="text-sm text-gray-900">
                            {{ !empty($driver['date_of_birth']) ? \Carbon\Carbon::parse($driver['date_of_birth'])->format('M d, Y') : 'Not provided' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Gender</dt>
                        <dd class="text-sm text-gray-900">{{ ucfirst($driver['gender'] ?? 'Not provided') }}</dd>
                    </div>
                </dl>
            </div>

            <div>
                <h4 class="text-md font-semibold text-gray-900 mb-4">Location & License</h4>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Address</dt>
                        <dd class="text-sm text-gray-900">{{ $driver['address'] ?? 'Not provided' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">City</dt>
                        <dd class="text-sm text-gray-900">{{ $driver['city'] ?? 'Not provided' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">State/Province</dt>
                        <dd class="text-sm text-gray-900">{{ $driver['state'] ?? 'Not provided' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Postal Code</dt>
                        <dd class="text-sm text-gray-900">{{ $driver['postal_code'] ?? 'Not provided' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Country</dt>
                        <dd class="text-sm text-gray-900">{{ $driver['country'] ?? 'Not provided' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">License Number</dt>
                        <dd class="text-sm text-gray-900">{{ $driver['license_number'] ?? 'Not provided' }}</dd>
                    </div>
                    @if(!empty($driver['license_expiry']))
                        <div>
                            <dt class="text-sm font-medium text-gray-500">License Expiry</dt>
                            <dd class="text-sm text-gray-900">
                                {{ \Carbon\Carbon::parse($driver['license_expiry'])->format('M d, Y') }}
                                @if(\Carbon\Carbon::parse($driver['license_expiry'])->isPast())
                                    <span class="text-red-600 font-medium">(Expired)</span>
                                @elseif(\Carbon\Carbon::parse($driver['license_expiry'])->lte(now()->addDays(30)))
                                    <span class="text-orange-600 font-medium">(Expiring Soon)</span>
                                @endif
                            </dd>
                        </div>
                    @endif
                    @if(!empty($driver['license_class']))
                        <div>
                            <dt class="text-sm font-medium text-gray-500">License Class</dt>
                            <dd class="text-sm text-gray-900">{{ $driver['license_class'] }}</dd>
                        </div>
                    @endif
                    @if(!empty($driver['issuing_state']))
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Issuing State</dt>
                            <dd class="text-sm text-gray-900">{{ $driver['issuing_state'] }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    <!-- Vehicles Tab -->
    <div id="vehicles-content" class="tab-content p-6 hidden">
        <div class="flex justify-between items-center mb-4">
            <h4 class="text-md font-semibold text-gray-900">Registered Vehicles</h4>
            <button onclick="openAddVehicleModal()" 
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm">
                <i class="fas fa-plus mr-2"></i>Add Vehicle
            </button>
        </div>
        
        @if(count($vehicles) > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($vehicles as $vehicle)
                    <div class="border rounded-lg p-4 {{ ($vehicle['is_primary'] ?? false) ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }}">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h5 class="font-semibold text-gray-900">
                                    {{ $vehicle['year'] ?? '' }} {{ $vehicle['make'] ?? '' }} {{ $vehicle['model'] ?? '' }}
                                    @if($vehicle['is_primary'] ?? false)
                                        <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full ml-2">Primary</span>
                                    @endif
                                </h5>
                                <p class="text-sm text-gray-600">{{ $vehicle['license_plate'] ?? 'No plate' }}</p>
                            </div>
                            <div class="flex space-x-2">
                                <button onclick="viewVehicle('{{ $vehicle['id'] ?? '' }}')" 
                                        class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="editVehicle('{{ $vehicle['id'] ?? '' }}')" 
                                        class="text-green-600 hover:text-green-800">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>
                        <div class="space-y-1 text-sm">
                            <p><span class="text-gray-500">Type:</span> {{ ucfirst($vehicle['vehicle_type'] ?? 'Unknown') }}</p>
                            <p><span class="text-gray-500">Color:</span> {{ $vehicle['color'] ?? 'Unknown' }}</p>
                            <p><span class="text-gray-500">Seats:</span> {{ $vehicle['seats'] ?? 'Unknown' }}</p>
                            @if(!empty($vehicle['vin']))
                                <p><span class="text-gray-500">VIN:</span> {{ $vehicle['vin'] }}</p>
                            @endif
                            @if(!empty($vehicle['fuel_type']))
                                <p><span class="text-gray-500">Fuel:</span> {{ ucfirst($vehicle['fuel_type']) }}</p>
                            @endif
                            @if(!empty($vehicle['registration_number']))
                                <p><span class="text-gray-500">Registration:</span> {{ $vehicle['registration_number'] }}</p>
                            @endif
                            @if(!empty($vehicle['insurance_provider']))
                                <p><span class="text-gray-500">Insurance:</span> {{ $vehicle['insurance_provider'] }}</p>
                            @endif
                            <div class="flex items-center space-x-2 mt-2">
                                @if(($vehicle['status'] ?? '') === 'active')
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ ucfirst($vehicle['status'] ?? 'Unknown') }}
                                    </span>
                                @endif
                                
                                @if(($vehicle['verification_status'] ?? '') === 'verified')
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        Verified
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        {{ ucfirst($vehicle['verification_status'] ?? 'Pending') }}
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
                <h5 class="text-lg font-medium text-gray-900 mb-2">No Vehicles Registered</h5>
                <p class="text-gray-500 mb-4">This driver hasn't registered any vehicles yet.</p>
                <button onclick="openAddVehicleModal()" 
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Add First Vehicle
                </button>
            </div>
        @endif
    </div>

    <!-- Documents Tab -->
    <div id="documents-content" class="tab-content p-6 hidden">
        <div class="flex justify-between items-center mb-4">
            <h4 class="text-md font-semibold text-gray-900">Documents</h4>
            <button onclick="openUploadDocumentModal()" 
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm">
                <i class="fas fa-plus mr-2"></i>Upload Document
            </button>
        </div>
        
        @if(count($documents) > 0)
            <div class="space-y-4">
                @foreach($documents as $document)
                    <div class="border rounded-lg p-4">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-2">
                                    <h5 class="font-semibold text-gray-900">
                                        {{ $document['document_name'] ?? ucfirst(str_replace('_', ' ', $document['document_type'] ?? 'Document')) }}
                                    </h5>
                                    @if(($document['verification_status'] ?? '') === 'verified')
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check mr-1"></i>Verified
                                        </span>
                                    @elseif(($document['verification_status'] ?? '') === 'rejected')
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            <i class="fas fa-times mr-1"></i>Rejected
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            <i class="fas fa-clock mr-1"></i>Pending
                                        </span>
                                    @endif
                                </div>
                                <div class="text-sm text-gray-600 space-y-1">
                                    <p><span class="font-medium">Type:</span> {{ ucfirst(str_replace('_', ' ', $document['document_type'] ?? 'Unknown')) }}</p>
                                    @if(!empty($document['document_number']))
                                        <p><span class="font-medium">Number:</span> {{ $document['document_number'] }}</p>
                                    @endif
                                    @if(!empty($document['expiry_date']))
                                        <p><span class="font-medium">Expires:</span> 
                                            {{ \Carbon\Carbon::parse($document['expiry_date'])->format('M d, Y') }}
                                            @if(\Carbon\Carbon::parse($document['expiry_date'])->isPast())
                                                <span class="text-red-600 font-medium">(Expired)</span>
                                            @elseif(\Carbon\Carbon::parse($document['expiry_date'])->lte(now()->addDays(30)))
                                                <span class="text-orange-600 font-medium">(Expiring Soon)</span>
                                            @endif
                                        </p>
                                    @endif
                                    <p><span class="font-medium">Uploaded:</span> 
                                        {{ \Carbon\Carbon::parse($document['created_at'] ?? now())->format('M d, Y') }}
                                    </p>
                                    @if(!empty($document['file_size']))
                                        <p><span class="font-medium">Size:</span> {{ number_format($document['file_size'] / 1024, 1) }} KB</p>
                                    @endif
                                    @if(!empty($document['rejection_reason']))
                                        <p class="text-red-600"><span class="font-medium">Rejection Reason:</span> {{ $document['rejection_reason'] }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <button onclick="viewDocument('{{ $document['id'] ?? '' }}')" 
                                        class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="downloadDocument('{{ $document['id'] ?? '' }}')" 
                                        class="text-green-600 hover:text-green-800">
                                    <i class="fas fa-download"></i>
                                </button>
                                @if(($document['verification_status'] ?? '') === 'pending')
                                    <button onclick="verifyDocument('{{ $document['id'] ?? '' }}')" 
                                            class="text-blue-600 hover:text-blue-800" title="Verify Document">
                                        <i class="fas fa-shield-check"></i>
                                    </button>
                                    <button onclick="rejectDocument('{{ $document['id'] ?? '' }}')" 
                                            class="text-red-600 hover:text-red-800" title="Reject Document">
                                        <i class="fas fa-shield-times"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8">
                <i class="fas fa-file-alt text-4xl text-gray-400 mb-4"></i>
                <h5 class="text-lg font-medium text-gray-900 mb-2">No Documents Uploaded</h5>
                <p class="text-gray-500 mb-4">This driver hasn't uploaded any documents yet.</p>
                <button onclick="openUploadDocumentModal()" 
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Upload First Document
                </button>
            </div>
        @endif
    </div>

    <!-- Rides Tab -->
    <div id="rides-content" class="tab-content p-6 hidden">
        <div class="flex justify-between items-center mb-4">
            <h4 class="text-md font-semibold text-gray-900">Recent Rides</h4>
            <button onclick="openCreateRideModal()" 
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm">
                <i class="fas fa-plus mr-2"></i>Create Ride
            </button>
        </div>
        
        @if(count($rides) > 0)
            <div class="space-y-4">
                @foreach($rides as $ride)
                    <div class="border rounded-lg p-4">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h5 class="font-semibold text-gray-900">
                                    Ride #{{ $ride['ride_id'] ?? $ride['id'] ?? $loop->iteration }}
                                </h5>
                                <p class="text-sm text-gray-600">
                                    {{ \Carbon\Carbon::parse($ride['created_at'] ?? $ride['ride_date'] ?? now())->format('M d, Y g:i A') }}
                                </p>
                            </div>
                            <div class="flex items-center space-x-2">
                                @switch($ride['status'] ?? 'unknown')
                                    @case('completed')
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Completed
                                        </span>
                                        @break
                                    @case('cancelled')
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Cancelled
                                        </span>
                                        @break
                                    @case('in_progress')
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            In Progress
                                        </span>
                                        @break
                                    @case('pending')
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Pending
                                        </span>
                                        @break
                                    @default
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            {{ ucfirst($ride['status'] ?? 'Unknown') }}
                                        </span>
                                @endswitch
                                <button onclick="viewRide('{{ $ride['id'] ?? $ride['ride_id'] ?? '' }}')" 
                                        class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <p><span class="font-medium text-gray-700">From:</span></p>
                                <p class="text-gray-600">{{ $ride['pickup_address'] ?? $ride['pickup_location'] ?? 'Unknown pickup location' }}</p>
                            </div>
                            <div>
                                <p><span class="font-medium text-gray-700">To:</span></p>
                                <p class="text-gray-600">{{ $ride['dropoff_address'] ?? $ride['destination'] ?? 'Unknown dropoff location' }}</p>
                            </div>
                            <div>
                                <p><span class="font-medium text-gray-700">Passenger:</span> {{ $ride['passenger_name'] ?? $ride['customer_name'] ?? 'Unknown' }}</p>
                                @if(!empty($ride['distance_km']) || !empty($ride['distance']))
                                    <p><span class="font-medium text-gray-700">Distance:</span> {{ number_format($ride['distance_km'] ?? $ride['distance'] ?? 0, 1) }} km</p>
                                @endif
                            </div>
                            <div>
                                @if(!empty($ride['actual_fare']) || !empty($ride['estimated_fare']) || !empty($ride['fare']))
                                    <p><span class="font-medium text-gray-700">Fare:</span> 
                                        ${{ number_format($ride['actual_fare'] ?? $ride['fare'] ?? $ride['estimated_fare'] ?? 0, 2) }}
                                    </p>
                                @endif
                                @if(!empty($ride['duration_minutes']) || !empty($ride['duration']))
                                    <p><span class="font-medium text-gray-700">Duration:</span> {{ $ride['duration_minutes'] ?? $ride['duration'] ?? 'N/A' }} min</p>
                                @endif
                                @if(!empty($ride['rating']))
                                    <p><span class="font-medium text-gray-700">Rating:</span> 
                                        <i class="fas fa-star text-yellow-400"></i> {{ number_format($ride['rating'], 1) }}
                                    </p>
                                @endif
                            </div>
                        </div>
                        @if(!empty($ride['notes']) || !empty($ride['comments']))
                            <div class="mt-3 pt-3 border-t border-gray-200">
                                <p class="text-sm text-gray-600">
                                    <span class="font-medium">Notes:</span> {{ $ride['notes'] ?? $ride['comments'] }}
                                </p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="mt-4 text-center">
                <a href="{{ route('admin.rides.index', ['search' => $driver['email']]) }}" 
                   class="text-blue-600 hover:text-blue-800 text-sm">
                    View All Rides <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        @else
            <div class="text-center py-8">
                <i class="fas fa-route text-4xl text-gray-400 mb-4"></i>
                <h5 class="text-lg font-medium text-gray-900 mb-2">No Rides Found</h5>
                <p class="text-gray-500 mb-4">This driver hasn't completed any rides yet.</p>
            </div>
        @endif
    </div>

    <!-- Activities Tab -->
    <div id="activities-content" class="tab-content p-6 hidden">
        <h4 class="text-md font-semibold text-gray-900 mb-4">Recent Activities</h4>
        
        @if(count($activities) > 0)
            <div class="space-y-4">
                @foreach($activities as $activity)
                    <div class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg">
                        <div class="flex-shrink-0">
                            @switch($activity['activity_type'] ?? $activity['type'] ?? 'general')
                                @case('driver_registration')
                                @case('registration')
                                    <i class="fas fa-user-plus text-green-500"></i>
                                    @break
                                @case('document_upload')
                                @case('document')
                                    <i class="fas fa-file-upload text-blue-500"></i>
                                    @break
                                @case('ride_completed')
                                @case('ride')
                                    <i class="fas fa-check-circle text-green-500"></i>
                                    @break
                                @case('verification')
                                @case('verified')
                                    <i class="fas fa-shield-check text-blue-500"></i>
                                    @break
                                @case('vehicle_registration')
                                @case('vehicle')
                                    <i class="fas fa-car text-purple-500"></i>
                                    @break
                                @case('status_change')
                                    <i class="fas fa-exchange-alt text-orange-500"></i>
                                    @break
                                @case('profile_update')
                                    <i class="fas fa-user-edit text-blue-500"></i>
                                    @break
                                @default
                                    <i class="fas fa-info-circle text-gray-500"></i>
                            @endswitch
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-900">{{ $activity['title'] ?? $activity['description'] ?? 'Activity' }}</p>
                            @if(!empty($activity['description']) && isset($activity['title']))
                                <p class="text-xs text-gray-600 mt-1">{{ $activity['description'] }}</p>
                            @endif
                            @if(!empty($activity['details']))
                                <p class="text-xs text-gray-600 mt-1">{{ $activity['details'] }}</p>
                            @endif
                            <div class="flex items-center justify-between mt-1">
                                <p class="text-xs text-gray-500">
                                    {{ \Carbon\Carbon::parse($activity['created_at'] ?? $activity['timestamp'] ?? now())->diffForHumans() }}
                                </p>
                                @if(!empty($activity['admin_user']))
                                    <p class="text-xs text-gray-500">
                                        by {{ $activity['admin_user'] }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 text-center">
                <a href="{{ route('admin.activities.index', ['driver_firebase_uid' => $driver['firebase_uid']]) }}" 
                   class="text-blue-600 hover:text-blue-800 text-sm">
                    View All Activities <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        @else
            <div class="text-center py-8">
                <i class="fas fa-history text-4xl text-gray-400 mb-4"></i>
                <h5 class="text-lg font-medium text-gray-900 mb-2">No Activities Found</h5>
                <p class="text-gray-500">No recent activities for this driver.</p>
            </div>
        @endif
    </div>
</div>

<!-- Document Rejection Modal -->
<div id="rejectDocumentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Reject Document</h3>
            <textarea id="rejectionReason" 
                      class="w-full p-3 border border-gray-300 rounded-md" 
                      rows="4" 
                      placeholder="Enter reason for rejection..."></textarea>
            <div class="flex justify-end space-x-3 mt-4">
                <button onclick="closeRejectModal()" 
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                    Cancel
                </button>
                <button onclick="confirmRejectDocument()" 
                        class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                    Reject Document
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    let actionsMenuVisible = false;
    let documentToReject = null;

    function toggleActionsMenu() {
        const menu = document.getElementById('actionsMenu');
        actionsMenuVisible = !actionsMenuVisible;
        
        if (actionsMenuVisible) {
            menu.classList.remove('hidden');
        } else {
            menu.classList.add('hidden');
        }
    }

    // Close actions menu when clicking outside
    document.addEventListener('click', function(event) {
        const menu = document.getElementById('actionsMenu');
        const button = event.target.closest('button');
        
        if (!menu.contains(event.target) && button?.onclick !== toggleActionsMenu && actionsMenuVisible) {
            toggleActionsMenu();
        }
    });

    function showTab(tabName) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        
        // Remove active styles from all tabs
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('border-blue-500', 'text-blue-600');
            button.classList.add('border-transparent', 'text-gray-500');
        });
        
        // Show selected tab content
        document.getElementById(tabName + '-content').classList.remove('hidden');
        
        // Add active styles to selected tab
        const activeTab = document.getElementById(tabName + '-tab');
        activeTab.classList.remove('border-transparent', 'text-gray-500');
        activeTab.classList.add('border-blue-500', 'text-blue-600');
    }

    async function toggleDriverStatus(driverId, action) {
        const actionText = action === 'activate' ? 'activate' : action === 'verify' ? 'verify' : 'deactivate';
        if (!confirm(`Are you sure you want to ${actionText} this driver?`)) {
            return;
        }
        
        try {
            const response = await fetch(`/driver/admin/drivers/${driverId}/update-status`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: action })
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

    async function deleteDriver(driverId, driverName) {
        if (!confirm(`Are you sure you want to delete driver "${driverName}"? This action cannot be undone.`)) {
            return;
        }
        
        try {
            const response = await fetch(`/driver/admin/drivers/${driverId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            
            if (response.ok) {
                showNotification('Driver deleted successfully', 'success');
                setTimeout(() => window.location.href = '{{ route("admin.drivers.index") }}', 1500);
            } else {
                const result = await response.json();
                showNotification('Failed to delete driver: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Delete error:', error);
            showNotification('Error deleting driver: Connection failed', 'error');
        }
    }

    async function verifyDocument(documentId) {
        if (!confirm('Are you sure you want to verify this document?')) {
            return;
        }
        
        try {
            const response = await fetch(`/driver/admin/documents/${documentId}/verify`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                showNotification('Document verified successfully', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showNotification('Failed to verify document: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Verify error:', error);
            showNotification('Error verifying document: Connection failed', 'error');
        }
    }

    function rejectDocument(documentId) {
        documentToReject = documentId;
        document.getElementById('rejectDocumentModal').classList.remove('hidden');
        document.getElementById('rejectionReason').focus();
    }

    function closeRejectModal() {
        document.getElementById('rejectDocumentModal').classList.add('hidden');
        document.getElementById('rejectionReason').value = '';
        documentToReject = null;
    }

    async function confirmRejectDocument() {
        const reason = document.getElementById('rejectionReason').value.trim();
        
        if (!reason) {
            showNotification('Please provide a reason for rejection', 'error');
            return;
        }
        
        if (!documentToReject) {
            showNotification('No document selected', 'error');
            return;
        }
        
        try {
            const response = await fetch(`/driver/admin/documents/${documentToReject}/reject`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ reason: reason })
            });
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                showNotification('Document rejected successfully', 'success');
                closeRejectModal();
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showNotification('Failed to reject document: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Reject error:', error);
            showNotification('Error rejecting document: Connection failed', 'error');
        }
    }

    async function sendNotification(driverId) {
        const message = prompt('Enter notification message:');
        if (!message) return;
        
        try {
            const response = await fetch(`/driver/admin/drivers/${driverId}/send-notification`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ message: message })
            });
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                showNotification('Notification sent successfully', 'success');
            } else {
                showNotification('Failed to send notification: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Send notification error:', error);
            showNotification('Error sending notification: Connection failed', 'error');
        }
    }

    async function exportDriverData(driverId) {
        try {
            const response = await fetch(`/driver/admin/drivers/${driverId}/export`, {
                method: 'GET'
            });
            
            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `driver_${driverId}_data.csv`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                
                showNotification('Driver data exported successfully', 'success');
            } else {
                showNotification('Failed to export driver data', 'error');
            }
        } catch (error) {
            console.error('Export error:', error);
            showNotification('Error exporting driver data', 'error');
        }
    }

    // Modal functions - Future implementations
    function openAddVehicleModal() {
        showNotification('Add Vehicle feature coming soon', 'info');
    }

    function openUploadDocumentModal() {
        showNotification('Upload Document feature coming soon', 'info');
    }

    function openCreateRideModal() {
        showNotification('Create Ride feature coming soon', 'info');
    }

    function viewVehicle(vehicleId) {
        showNotification('View Vehicle feature coming soon', 'info');
    }

    function editVehicle(vehicleId) {
        showNotification('Edit Vehicle feature coming soon', 'info');
    }

    function viewDocument(documentId) {
        showNotification('View Document feature coming soon', 'info');
    }

    function downloadDocument(documentId) {
        showNotification('Download Document feature coming soon', 'info');
    }

    function viewRide(rideId) {
        showNotification('View Ride feature coming soon', 'info');
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

    // Initialize the page
    document.addEventListener('DOMContentLoaded', function() {
        // Show personal tab by default
        showTab('personal');
        
        // Add CSRF token meta tag if not present
        if (!document.querySelector('meta[name="csrf-token"]')) {
            const meta = document.createElement('meta');
            meta.name = 'csrf-token';
            meta.content = '{{ csrf_token() }}';
            document.getElementsByTagName('head')[0].appendChild(meta);
        }

        // Close modal when clicking outside
        document.getElementById('rejectDocumentModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRejectModal();
            }
        });
    });
</script>
@endpush