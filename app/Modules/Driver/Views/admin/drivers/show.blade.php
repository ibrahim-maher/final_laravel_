@extends('admin::layouts.admin')

@section('title', 'Driver Details - ' . $driver['name'])
@section('page-title', 'Driver Details')

@push('styles')
<style>
    .glass-effect {
        background: rgba(255, 255, 255, 0.25);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.18);
    }
    
    .gradient-border {
        background: linear-gradient(45deg, #003366, #FFA500);
        padding: 2px;
        border-radius: 1rem;
    }
    
    .gradient-border-content {
        background: white;
        border-radius: 0.875rem;
    }
    
    .status-badge {
        position: relative;
        overflow: hidden;
    }
    
    .status-badge::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        animation: shimmer 2s infinite;
    }
    
    @keyframes shimmer {
        0% { left: -100%; }
        100% { left: 100%; }
    }
    
    .metric-card {
        transition: all 0.3s ease;
    }
    
    .metric-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    
    .tab-content {
        animation: fadeIn 0.5s ease-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }
    
    .animate-float {
        animation: float 3s ease-in-out infinite;
    }
    
    .animate-slide-in-right {
        animation: slideInRight 0.3s ease-out;
    }
    
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    .animate-fade-in {
        animation: fadeIn 0.3s ease-out;
    }
    
    .animate-bounce-soft {
        animation: bounceSoft 1s ease-out;
    }
    
    @keyframes bounceSoft {
        0% { transform: translateY(-20px); opacity: 0; }
        100% { transform: translateY(0); opacity: 1; }
    }
    
    .animate-pulse-glow {
        animation: pulseGlow 2s infinite;
    }
    
    @keyframes pulseGlow {
        0%, 100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4); }
        50% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
    }
</style>
@endpush

@section('content')
<!-- Header Section with Enhanced Gradient Background -->
<div class="relative bg-gradient-to-br from-primary via-blue-800 to-secondary overflow-hidden mb-6 rounded-2xl shadow-xl">
    <!-- Animated background patterns -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute top-0 left-0 w-96 h-96 bg-white rounded-full mix-blend-multiply filter blur-xl animate-float"></div>
        <div class="absolute top-0 right-0 w-96 h-96 bg-secondary rounded-full mix-blend-multiply filter blur-xl animate-float" style="animation-delay: 1s;"></div>
        <div class="absolute bottom-0 left-1/2 w-96 h-96 bg-blue-300 rounded-full mix-blend-multiply filter blur-xl animate-float" style="animation-delay: 2s;"></div>
    </div>
    
    <div class="relative px-6 py-8">
        <!-- Breadcrumb Navigation -->
        <div class="mb-6">
            <nav class="flex items-center space-x-2 text-white/80 text-sm">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-white transition-colors">
                    <i class="fas fa-home"></i>
                </a>
                <i class="fas fa-chevron-right text-xs"></i>
                <a href="{{ route('admin.drivers.index') }}" class="hover:text-white transition-colors">Drivers</a>
                <i class="fas fa-chevron-right text-xs"></i>
                <span class="text-white font-medium">{{ $driver['name'] }}</span>
            </nav>
        </div>

        <!-- Main Header -->
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">
            <div class="flex items-center space-x-6">
                <a href="{{ route('admin.drivers.index') }}" class="text-white/80 hover:text-white transition-colors group">
                    <i class="fas fa-arrow-left text-2xl group-hover:scale-110 transition-transform"></i>
                </a>
                
                <!-- Driver Avatar and Info -->
                <div class="flex items-center space-x-6">
                    <div class="relative">
                        <div class="w-20 h-20 rounded-full bg-gradient-to-br from-secondary to-orange-400 p-1">
                            @if(!empty($driver['photo_url']))
                                <img src="{{ $driver['photo_url'] }}" alt="{{ $driver['name'] }}" 
                                     class="w-full h-full rounded-full object-cover">
                            @else
                                <div class="w-full h-full rounded-full bg-white flex items-center justify-center text-2xl font-bold text-primary">
                                    {{ strtoupper(substr($driver['name'], 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        @if(isset($driver['availability_status']) && $driver['availability_status'] === 'available')
                            <div class="absolute -bottom-1 -right-1 w-6 h-6 bg-green-500 rounded-full border-2 border-white animate-pulse"></div>
                        @endif
                    </div>
                    
                    <div>
                        <h1 class="text-4xl font-bold text-white mb-2">{{ $driver['name'] }}</h1>
                        <p class="text-white/80 text-lg">Driver ID: {{ $driver['firebase_uid'] }}</p>
                        
                        <!-- Status Badges -->
                        <div class="flex items-center space-x-3 mt-3">
                            @if($driver['status'] === 'active')
                                <span class="status-badge inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-500/90 text-white">
                                    <i class="fas fa-check-circle mr-2"></i>Active
                                </span>
                            @elseif($driver['status'] === 'suspended')
                                <span class="status-badge inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-orange-500/90 text-white">
                                    <i class="fas fa-pause-circle mr-2"></i>Suspended
                                </span>
                            @elseif($driver['status'] === 'pending')
                                <span class="status-badge inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-500/90 text-white">
                                    <i class="fas fa-clock mr-2"></i>Pending
                                </span>
                            @else
                                <span class="status-badge inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-500/90 text-white">
                                    <i class="fas fa-times-circle mr-2"></i>Inactive
                                </span>
                            @endif

                            @if($driver['verification_status'] === 'verified')
                                <span class="status-badge inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-500/90 text-white">
                                    <i class="fas fa-shield-check mr-2"></i>Verified
                                </span>
                            @elseif($driver['verification_status'] === 'rejected')
                                <span class="status-badge inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-500/90 text-white">
                                    <i class="fas fa-shield-times mr-2"></i>Rejected
                                </span>
                            @else
                                <span class="status-badge inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-500/90 text-white">
                                    <i class="fas fa-shield-question mr-2"></i>Pending Verification
                                </span>
                            @endif

                            @if(isset($driver['availability_status']))
                                @if($driver['availability_status'] === 'available')
                                    <span class="status-badge inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-500/90 text-white">
                                        <i class="fas fa-circle mr-2"></i>Available
                                    </span>
                                @elseif($driver['availability_status'] === 'busy')
                                    <span class="status-badge inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-500/90 text-white">
                                        <i class="fas fa-circle mr-2"></i>Busy
                                    </span>
                                @else
                                    <span class="status-badge inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-500/90 text-white">
                                        <i class="fas fa-circle mr-2"></i>Offline
                                    </span>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.drivers.edit', $driver['firebase_uid']) }}" 
                   class="bg-gradient-to-r from-green-600 to-green-700 text-white px-6 py-3 rounded-xl font-medium transition-all duration-300 hover:scale-105 shadow-lg hover:shadow-xl">
                    <i class="fas fa-edit mr-2"></i>Edit Driver
                </a>
               
            </div>
        </div>
    </div>
</div>

<!-- Success/Error Messages -->
@if(session('success'))
    <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded-r-lg shadow-sm mb-6 animate-slide-in-left">
        <div class="flex items-center">
            <i class="fas fa-check-circle text-green-500 mr-3"></i>
            <p class="text-green-700 font-medium">{{ session('success') }}</p>
        </div>
    </div>
@endif

@if(session('error'))
    <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-r-lg shadow-sm mb-6 animate-slide-in-left">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
            <p class="text-red-700 font-medium">{{ session('error') }}</p>
        </div>
    </div>
@endif

<!-- Profile Completion Banner -->
@if(session('completion_status') || isset($profileCompletion))
@endif

<!-- Driver Overview Grid -->
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
    <!-- Enhanced Driver Profile Card -->
    <div class="lg:col-span-3 bg-white rounded-2xl shadow-xl border border-gray-100 p-8 hover:shadow-2xl transition-all duration-500 animate-fade-in">
        <div class="flex flex-col md:flex-row items-start space-y-6 md:space-y-0 md:space-x-8">
            <div class="flex-shrink-0">
                <div class="relative group">
                    <div class="w-32 h-32 rounded-2xl bg-gradient-to-br from-primary to-blue-600 p-1 group-hover:scale-105 transition-transform duration-300">
                        @if(!empty($driver['photo_url']))
                            <img src="{{ $driver['photo_url'] }}" alt="{{ $driver['name'] }}" 
                                 class="w-full h-full rounded-xl object-cover">
                        @else
                            <div class="w-full h-full rounded-xl bg-white flex items-center justify-center text-3xl font-bold text-primary">
                                {{ strtoupper(substr($driver['name'], 0, 1)) }}
                            </div>
                        @endif
                    </div>
                    @if(isset($driver['availability_status']) && $driver['availability_status'] === 'available')
                        <div class="absolute -bottom-2 -right-2 w-8 h-8 bg-green-500 rounded-full border-3 border-white animate-pulse shadow-lg"></div>
                    @endif
                </div>
            </div>
            
            <div class="flex-1">
                <h2 class="text-3xl font-bold text-gray-900 mb-3">{{ $driver['name'] }}</h2>
                
                <div class="space-y-3">
                    <div class="flex items-center text-gray-600 hover:text-gray-800 transition-colors">
                        <i class="fas fa-envelope mr-3 text-blue-500 w-5"></i>
                        <span>{{ $driver['email'] }}</span>
                    </div>
                    @if(!empty($driver['phone']))
                        <div class="flex items-center text-gray-600 hover:text-gray-800 transition-colors">
                            <i class="fas fa-phone mr-3 text-green-500 w-5"></i>
                            <span>{{ $driver['phone'] }}</span>
                        </div>
                    @endif
                    @if(!empty($driver['license_number']))
                        <div class="flex items-center text-gray-600 hover:text-gray-800 transition-colors">
                            <i class="fas fa-id-card mr-3 text-purple-500 w-5"></i>
                            <span>License: {{ $driver['license_number'] }}</span>
                        </div>
                    @endif
                    @if(!empty($driver['city']) || !empty($driver['state']))
                        <div class="flex items-center text-gray-600 hover:text-gray-800 transition-colors">
                            <i class="fas fa-map-marker-alt mr-3 text-red-500 w-5"></i>
                            <span>{{ $driver['city'] }}{{ !empty($driver['city']) && !empty($driver['state']) ? ', ' : '' }}{{ $driver['state'] }}</span>
                        </div>
                    @endif
                    <div class="flex items-center text-gray-600 hover:text-gray-800 transition-colors">
                        <i class="fas fa-calendar mr-3 text-orange-500 w-5"></i>
                        <span>Joined {{ \Carbon\Carbon::parse($driver['join_date'] ?? $driver['created_at'] ?? now())->format('M d, Y') }}
                              ({{ \Carbon\Carbon::parse($driver['join_date'] ?? $driver['created_at'] ?? now())->diffForHumans() }})</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Performance Metrics -->
    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-6 hover:shadow-2xl transition-all duration-500 animate-slide-in-right">
        <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
            <i class="fas fa-chart-line mr-3 text-blue-500"></i>
            Performance
        </h3>
        
        <div class="space-y-6">
            <div class="metric-card p-4 bg-gradient-to-r from-yellow-50 to-orange-50 rounded-xl border border-yellow-200">
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-600">Rating</span>
                    <div class="flex items-center">
                        <i class="fas fa-star text-yellow-500 mr-2"></i>
                        <span class="text-xl font-bold text-gray-900">{{ number_format($driver['rating'] ?? $rideStats['average_rating'] ?? 0, 1) }}</span>
                    </div>
                </div>
            </div>
            
            <div class="metric-card p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border border-blue-200">
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-600">Total Rides</span>
                    <span class="text-xl font-bold text-gray-900">{{ number_format($driver['total_rides'] ?? $rideStats['total_rides'] ?? 0) }}</span>
                </div>
            </div>
            
            <div class="metric-card p-4 bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl border border-green-200">
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-600">Earnings</span>
                    <span class="text-xl font-bold text-gray-900">${{ number_format($driver['total_earnings'] ?? $rideStats['total_earnings'] ?? 0, 2) }}</span>
                </div>
            </div>
            
            @if(isset($performanceMetrics))
                <div class="metric-card p-4 bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl border border-purple-200">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-600">Completion</span>
                        <span class="text-xl font-bold text-gray-900">{{ number_format($performanceMetrics['completion_rate'] ?? 0, 1) }}%</span>
                    </div>
                </div>
                
                @if(isset($performanceMetrics['acceptance_rate']))
                    <div class="metric-card p-4 bg-gradient-to-r from-indigo-50 to-blue-50 rounded-xl border border-indigo-200">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-600">Acceptance</span>
                            <span class="text-xl font-bold text-gray-900">{{ number_format($performanceMetrics['acceptance_rate'], 1) }}%</span>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

<!-- Enhanced Tabbed Content -->
<div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden animate-bounce-soft">
    <!-- Tab Navigation with Enhanced Design -->
    <div class="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
        <nav class="flex space-x-0" aria-label="Tabs">
            <button onclick="showTab('personal')" id="personal-tab" 
                    class="tab-button flex-1 border-b-3 border-blue-500 py-6 px-6 text-sm font-semibold text-blue-600 bg-blue-50 transition-all duration-300 hover:bg-blue-100">
                <i class="fas fa-user mr-2"></i>Personal Info
            </button>
            <button onclick="showTab('vehicles')" id="vehicles-tab" 
                    class="tab-button flex-1 border-b-3 border-transparent py-6 px-6 text-sm font-semibold text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-all duration-300">
                <i class="fas fa-car mr-2"></i>Vehicles ({{ count($vehicles) }})
            </button>
            <button onclick="showTab('documents')" id="documents-tab" 
                    class="tab-button flex-1 border-b-3 border-transparent py-6 px-6 text-sm font-semibold text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-all duration-300">
                <i class="fas fa-file-alt mr-2"></i>Documents ({{ count($documents) }})
            </button>
            <button onclick="showTab('rides')" id="rides-tab" 
                    class="tab-button flex-1 border-b-3 border-transparent py-6 px-6 text-sm font-semibold text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-all duration-300">
                <i class="fas fa-route mr-2"></i>Recent Rides ({{ count($rides) }})
            </button>
            <button onclick="showTab('activities')" id="activities-tab" 
                    class="tab-button flex-1 border-b-3 border-transparent py-6 px-6 text-sm font-semibold text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-all duration-300">
                <i class="fas fa-history mr-2"></i>Activities ({{ count($activities) }})
            </button>
        </nav>
    </div>

    <!-- Personal Info Tab -->
    <div id="personal-content" class="tab-content p-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Basic Information Card -->
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl p-6 border border-blue-200">
                <h4 class="text-lg font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-info-circle mr-3 text-blue-500"></i>
                    Basic Information
                </h4>
                <dl class="space-y-4">
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <dt class="text-sm font-medium text-gray-500 mb-1">Full Name</dt>
                        <dd class="text-lg font-semibold text-gray-900">{{ $driver['name'] ?? 'Not provided' }}</dd>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <dt class="text-sm font-medium text-gray-500 mb-1">Email</dt>
                        <dd class="text-lg font-semibold text-gray-900">{{ $driver['email'] ?? 'Not provided' }}</dd>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <dt class="text-sm font-medium text-gray-500 mb-1">Phone</dt>
                        <dd class="text-lg font-semibold text-gray-900">{{ $driver['phone'] ?? 'Not provided' }}</dd>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <dt class="text-sm font-medium text-gray-500 mb-1">Date of Birth</dt>
                        <dd class="text-lg font-semibold text-gray-900">
                            {{ !empty($driver['date_of_birth']) ? \Carbon\Carbon::parse($driver['date_of_birth'])->format('M d, Y') : 'Not provided' }}
                        </dd>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <dt class="text-sm font-medium text-gray-500 mb-1">Gender</dt>
                        <dd class="text-lg font-semibold text-gray-900">{{ ucfirst($driver['gender'] ?? 'Not provided') }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Location & License Card -->
            <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl p-6 border border-green-200">
                <h4 class="text-lg font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-map-marked-alt mr-3 text-green-500"></i>
                    Location & License
                </h4>
                <dl class="space-y-4">
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <dt class="text-sm font-medium text-gray-500 mb-1">Address</dt>
                        <dd class="text-lg font-semibold text-gray-900">{{ $driver['address'] ?? 'Not provided' }}</dd>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <dt class="text-sm font-medium text-gray-500 mb-1">City</dt>
                        <dd class="text-lg font-semibold text-gray-900">{{ $driver['city'] ?? 'Not provided' }}</dd>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <dt class="text-sm font-medium text-gray-500 mb-1">State/Province</dt>
                        <dd class="text-lg font-semibold text-gray-900">{{ $driver['state'] ?? 'Not provided' }}</dd>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <dt class="text-sm font-medium text-gray-500 mb-1">Postal Code</dt>
                        <dd class="text-lg font-semibold text-gray-900">{{ $driver['postal_code'] ?? 'Not provided' }}</dd>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <dt class="text-sm font-medium text-gray-500 mb-1">Country</dt>
                        <dd class="text-lg font-semibold text-gray-900">{{ $driver['country'] ?? 'Not provided' }}</dd>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <dt class="text-sm font-medium text-gray-500 mb-1">License Number</dt>
                        <dd class="text-lg font-semibold text-gray-900">{{ $driver['license_number'] ?? 'Not provided' }}</dd>
                    </div>
                    @if(!empty($driver['license_expiry']))
                        <div class="bg-white rounded-lg p-4 shadow-sm">
                            <dt class="text-sm font-medium text-gray-500 mb-1">License Expiry</dt>
                            <dd class="text-lg font-semibold text-gray-900">
                                {{ \Carbon\Carbon::parse($driver['license_expiry'])->format('M d, Y') }}
                                @if(\Carbon\Carbon::parse($driver['license_expiry'])->isPast())
                                    <span class="text-red-600 font-medium">(Expired)</span>
                                @elseif(\Carbon\Carbon::parse($driver['license_expiry'])->lte(now()->addDays(30)))
                                    <span class="text-orange-600 font-medium">(Expiring Soon)</span>
                                @else
                                    <span class="text-green-600 font-medium">(Valid)</span>
                                @endif
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>
    <!-- Vehicles Tab -->
    <div id="vehicles-content" class="tab-content p-8 hidden">
        <div class="flex justify-between items-center mb-6">
            <h4 class="text-xl font-bold text-gray-900">Registered Vehicles</h4>
            <button onclick="openAddVehicleModal()" 
                    class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-3 rounded-xl font-medium transition-all duration-300 hover:scale-105 shadow-lg hover:shadow-xl">
                <i class="fas fa-plus mr-2"></i>Add Vehicle
            </button>
        </div>
        
        @if(count($vehicles) > 0)
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @foreach($vehicles as $vehicle)
                    <div class="{{ ($vehicle['is_primary'] ?? false) ? 'gradient-border' : 'bg-white rounded-2xl border border-gray-200 shadow-lg' }} hover:shadow-xl transition-all duration-300">
                        <div class="{{ ($vehicle['is_primary'] ?? false) ? 'gradient-border-content' : '' }} p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h5 class="text-xl font-bold text-gray-900 mb-2">
                                        {{ $vehicle['year'] ?? '' }} {{ $vehicle['make'] ?? '' }} {{ $vehicle['model'] ?? '' }}
                                        @if($vehicle['is_primary'] ?? false)
                                            <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full ml-2">Primary</span>
                                        @endif
                                    </h5>
                                    <p class="text-gray-600 font-medium">{{ $vehicle['license_plate'] ?? 'No plate' }}</p>
                                </div>
                                <div class="flex space-x-2">
                                    <button onclick="viewVehicle('{{ $vehicle['id'] ?? '' }}')" 
                                            class="text-blue-600 hover:text-blue-800 p-2 rounded-lg hover:bg-blue-50 transition-all">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="editVehicle('{{ $vehicle['id'] ?? '' }}')" 
                                            class="text-green-600 hover:text-green-800 p-2 rounded-lg hover:bg-green-50 transition-all">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <span class="text-gray-500 font-medium">Type:</span>
                                    <p class="text-gray-900 font-semibold">{{ ucfirst($vehicle['vehicle_type'] ?? 'Unknown') }}</p>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <span class="text-gray-500 font-medium">Color:</span>
                                    <p class="text-gray-900 font-semibold">{{ $vehicle['color'] ?? 'Unknown' }}</p>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <span class="text-gray-500 font-medium">Seats:</span>
                                    <p class="text-gray-900 font-semibold">{{ $vehicle['seats'] ?? 'Unknown' }}</p>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <span class="text-gray-500 font-medium">Fuel:</span>
                                    <p class="text-gray-900 font-semibold">{{ ucfirst($vehicle['fuel_type'] ?? 'Unknown') }}</p>
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-3 mt-4">
                                @if(($vehicle['status'] ?? '') === 'active')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ ucfirst($vehicle['status'] ?? 'Unknown') }}
                                    </span>
                                @endif
                                
                                @if(($vehicle['verification_status'] ?? '') === 'verified')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        Verified
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        {{ ucfirst($vehicle['verification_status'] ?? 'Pending') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-car text-4xl text-gray-400"></i>
                </div>
                <h5 class="text-xl font-medium text-gray-900 mb-2">No Vehicles Registered</h5>
                <p class="text-gray-500 mb-6">This driver hasn't registered any vehicles yet.</p>
                <button onclick="openAddVehicleModal()" 
                        class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-3 rounded-xl font-medium transition-all duration-300 hover:scale-105 shadow-lg hover:shadow-xl">
                    <i class="fas fa-plus mr-2"></i>Add First Vehicle
                </button>
            </div>
        @endif
    </div>

    <!-- Documents Tab -->
    <div id="documents-content" class="tab-content p-8 hidden">
        <div class="flex justify-between items-center mb-6">
            <h4 class="text-xl font-bold text-gray-900">Documents</h4>
            <button onclick="openUploadDocumentModal()" 
                    class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-3 rounded-xl font-medium transition-all duration-300 hover:scale-105 shadow-lg hover:shadow-xl">
                <i class="fas fa-plus mr-2"></i>Upload Document
            </button>
        </div>
        
        @if(count($documents) > 0)
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @foreach($documents as $document)
                    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-lg hover:shadow-xl transition-all duration-300">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h5 class="text-lg font-bold text-gray-900 mb-2 flex items-center">
                                    @switch($document['document_type'] ?? 'document')
                                        @case('drivers_license')
                                            <i class="fas fa-id-card mr-2 text-blue-500"></i>
                                            @break
                                        @case('insurance')
                                            <i class="fas fa-shield-alt mr-2 text-green-500"></i>
                                            @break
                                        @case('vehicle_registration')
                                            <i class="fas fa-file-certificate mr-2 text-purple-500"></i>
                                            @break
                                        @case('background_check')
                                            <i class="fas fa-user-check mr-2 text-orange-500"></i>
                                            @break
                                        @default
                                            <i class="fas fa-file-alt mr-2 text-gray-500"></i>
                                    @endswitch
                                    {{ $document['document_name'] ?? ucfirst(str_replace('_', ' ', $document['document_type'] ?? 'Document')) }}
                                </h5>
                                @if(($document['verification_status'] ?? '') === 'verified')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-check mr-1"></i>Verified
                                    </span>
                                @elseif(($document['verification_status'] ?? '') === 'rejected')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <i class="fas fa-times mr-1"></i>Rejected
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <i class="fas fa-clock mr-1"></i>Pending
                                    </span>
                                @endif
                            </div>
                            <div class="flex space-x-2">
                                <button onclick="viewDocument('{{ $document['id'] ?? '' }}')" 
                                        class="text-blue-600 hover:text-blue-800 p-2 rounded-lg hover:bg-blue-50 transition-all">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="downloadDocument('{{ $document['id'] ?? '' }}')" 
                                        class="text-green-600 hover:text-green-800 p-2 rounded-lg hover:bg-green-50 transition-all">
                                    <i class="fas fa-download"></i>
                                </button>
                                @if(($document['verification_status'] ?? '') === 'pending')
                                    <button onclick="verifyDocument('{{ $document['id'] ?? '' }}')" 
                                            class="text-blue-600 hover:text-blue-800 p-2 rounded-lg hover:bg-blue-50 transition-all" title="Verify Document">
                                        <i class="fas fa-shield-check"></i>
                                    </button>
                                    <button onclick="rejectDocument('{{ $document['id'] ?? '' }}')" 
                                            class="text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 transition-all" title="Reject Document">
                                        <i class="fas fa-shield-times"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                        
                        <div class="space-y-3 text-sm">
                            @if(!empty($document['document_number']))
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Number:</span>
                                    <span class="font-semibold">{{ $document['document_number'] }}</span>
                                </div>
                            @endif
                            @if(!empty($document['expiry_date']))
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Expires:</span>
                                    <span class="font-semibold {{ \Carbon\Carbon::parse($document['expiry_date'])->isPast() ? 'text-red-600' : (\Carbon\Carbon::parse($document['expiry_date'])->lte(now()->addDays(30)) ? 'text-orange-600' : 'text-green-600') }}">
                                        {{ \Carbon\Carbon::parse($document['expiry_date'])->format('M d, Y') }}
                                    </span>
                                </div>
                            @endif
                            <div class="flex justify-between">
                                <span class="text-gray-500">Uploaded:</span>
                                <span class="font-semibold">{{ \Carbon\Carbon::parse($document['created_at'] ?? now())->format('M d, Y') }}</span>
                            </div>
                            @if(!empty($document['file_size']))
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Size:</span>
                                    <span class="font-semibold">{{ number_format($document['file_size'] / 1024, 1) }} KB</span>
                                </div>
                            @endif
                            @if(!empty($document['rejection_reason']))
                                <div class="bg-red-50 rounded-lg p-3 mt-3">
                                    <p class="text-red-600 text-xs"><span class="font-medium">Rejection Reason:</span> {{ $document['rejection_reason'] }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-file-alt text-4xl text-gray-400"></i>
                </div>
                <h5 class="text-xl font-medium text-gray-900 mb-2">No Documents Uploaded</h5>
                <p class="text-gray-500 mb-6">This driver hasn't uploaded any documents yet.</p>
                <button onclick="openUploadDocumentModal()" 
                        class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-3 rounded-xl font-medium transition-all duration-300 hover:scale-105 shadow-lg hover:shadow-xl">
                    <i class="fas fa-plus mr-2"></i>Upload First Document
                </button>
            </div>
        @endif
    </div>

    <!-- Rides Tab -->
    <div id="rides-content" class="tab-content p-8 hidden">
        <div class="flex justify-between items-center mb-6">
            <h4 class="text-xl font-bold text-gray-900">Recent Rides</h4>
            <button onclick="openCreateRideModal()" 
                    class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-3 rounded-xl font-medium transition-all duration-300 hover:scale-105 shadow-lg hover:shadow-xl">
                <i class="fas fa-plus mr-2"></i>Create Ride
            </button>
        </div>
        
        @if(count($rides) > 0)
            <div class="space-y-6">
                @foreach($rides as $ride)
                    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-lg hover:shadow-xl transition-all duration-300">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h5 class="text-lg font-bold text-gray-900 mb-2">
                                    Ride #{{ $ride['ride_id'] ?? $ride['id'] ?? $loop->iteration }}
                                </h5>
                                <p class="text-gray-600">
                                    {{ \Carbon\Carbon::parse($ride['created_at'] ?? $ride['ride_date'] ?? now())->format('M d, Y g:i A') }}
                                </p>
                            </div>
                            <div class="flex items-center space-x-3">
                                @switch($ride['status'] ?? 'unknown')
                                    @case('completed')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Completed
                                        </span>
                                        @break
                                    @case('cancelled')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Cancelled
                                        </span>
                                        @break
                                    @case('in_progress')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            In Progress
                                        </span>
                                        @break
                                    @case('pending')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Pending
                                        </span>
                                        @break
                                    @default
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            {{ ucfirst($ride['status'] ?? 'Unknown') }}
                                        </span>
                                @endswitch
                                <button onclick="viewRide('{{ $ride['id'] ?? $ride['ride_id'] ?? '' }}')" 
                                        class="text-blue-600 hover:text-blue-800 p-2 rounded-lg hover:bg-blue-50 transition-all">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="space-y-3">
                                <div>
                                    <p class="font-medium text-gray-700 mb-1">From:</p>
                                    <p class="text-gray-600">{{ $ride['pickup_address'] ?? $ride['pickup_location'] ?? 'Unknown pickup location' }}</p>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-700 mb-1">To:</p>
                                    <p class="text-gray-600">{{ $ride['dropoff_address'] ?? $ride['destination'] ?? 'Unknown dropoff location' }}</p>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Passenger:</span>
                                    <span class="font-semibold">{{ $ride['passenger_name'] ?? $ride['customer_name'] ?? 'Unknown' }}</span>
                                </div>
                                @if(!empty($ride['distance_km']) || !empty($ride['distance']))
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Distance:</span>
                                        <span class="font-semibold">{{ number_format($ride['distance_km'] ?? $ride['distance'] ?? 0, 1) }} km</span>
                                    </div>
                                @endif
                                @if(!empty($ride['actual_fare']) || !empty($ride['estimated_fare']) || !empty($ride['fare']))
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Fare:</span>
                                        <span class="font-semibold text-green-600">${{ number_format($ride['actual_fare'] ?? $ride['fare'] ?? $ride['estimated_fare'] ?? 0, 2) }}</span>
                                    </div>
                                @endif
                                @if(!empty($ride['duration_minutes']) || !empty($ride['duration']))
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Duration:</span>
                                        <span class="font-semibold">{{ $ride['duration_minutes'] ?? $ride['duration'] ?? 'N/A' }} min</span>
                                    </div>
                                @endif
                                @if(!empty($ride['rating']))
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Rating:</span>
                                        <span class="font-semibold flex items-center">
                                            <i class="fas fa-star text-yellow-400 mr-1"></i>{{ number_format($ride['rating'], 1) }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>
                        @if(!empty($ride['notes']) || !empty($ride['comments']))
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <p class="text-sm text-gray-600">
                                    <span class="font-medium">Notes:</span> {{ $ride['notes'] ?? $ride['comments'] }}
                                </p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="mt-8 text-center">
                <a href="{{ route('admin.rides.index', ['search' => $driver['email']]) }}" 
                   class="text-blue-600 hover:text-blue-800 font-medium">
                    View All Rides <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        @else
            <div class="text-center py-12">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-route text-4xl text-gray-400"></i>
                </div>
                <h5 class="text-xl font-medium text-gray-900 mb-2">No Rides Found</h5>
                <p class="text-gray-500">This driver hasn't completed any rides yet.</p>
            </div>
        @endif
    </div>

    <!-- Activities Tab -->
    <div id="activities-content" class="tab-content p-8 hidden">
        <h4 class="text-xl font-bold text-gray-900 mb-6">Recent Activities</h4>
        
        @if(count($activities) > 0)
            <div class="space-y-4">
                @foreach($activities as $activity)
                    <div class="flex items-start space-x-4 p-4 rounded-xl border transition-all duration-300 hover:shadow-md
                        @switch($activity['activity_type'] ?? $activity['type'] ?? 'general')
                            @case('driver_registration')
                            @case('registration')
                                bg-gradient-to-r from-green-50 to-emerald-50 border-green-200
                                @break
                            @case('document_upload')
                            @case('document')
                                bg-gradient-to-r from-blue-50 to-indigo-50 border-blue-200
                                @break
                            @case('ride_completed')
                            @case('ride')
                                bg-gradient-to-r from-green-50 to-emerald-50 border-green-200
                                @break
                            @case('verification')
                            @case('verified')
                                bg-gradient-to-r from-purple-50 to-pink-50 border-purple-200
                                @break
                            @case('vehicle_registration')
                            @case('vehicle')
                                bg-gradient-to-r from-purple-50 to-pink-50 border-purple-200
                                @break
                            @case('status_change')
                                bg-gradient-to-r from-orange-50 to-red-50 border-orange-200
                                @break
                            @case('profile_update')
                                bg-gradient-to-r from-blue-50 to-indigo-50 border-blue-200
                                @break
                            @default
                                bg-gradient-to-r from-gray-50 to-slate-50 border-gray-200
                        @endswitch
                    ">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center
                                @switch($activity['activity_type'] ?? $activity['type'] ?? 'general')
                                    @case('driver_registration')
                                    @case('registration')
                                        bg-green-500
                                        @break
                                    @case('document_upload')
                                    @case('document')
                                        bg-blue-500
                                        @break
                                    @case('ride_completed')
                                    @case('ride')
                                        bg-green-500
                                        @break
                                    @case('verification')
                                    @case('verified')
                                        bg-purple-500
                                        @break
                                    @case('vehicle_registration')
                                    @case('vehicle')
                                        bg-purple-500
                                        @break
                                    @case('status_change')
                                        bg-orange-500
                                        @break
                                    @case('profile_update')
                                        bg-blue-500
                                        @break
                                    @default
                                        bg-gray-500
                                @endswitch
                            ">
                                @switch($activity['activity_type'] ?? $activity['type'] ?? 'general')
                                    @case('driver_registration')
                                    @case('registration')
                                        <i class="fas fa-user-plus text-white"></i>
                                        @break
                                    @case('document_upload')
                                    @case('document')
                                        <i class="fas fa-file-upload text-white"></i>
                                        @break
                                    @case('ride_completed')
                                    @case('ride')
                                        <i class="fas fa-check-circle text-white"></i>
                                        @break
                                    @case('verification')
                                    @case('verified')
                                        <i class="fas fa-shield-check text-white"></i>
                                        @break
                                    @case('vehicle_registration')
                                    @case('vehicle')
                                        <i class="fas fa-car text-white"></i>
                                        @break
                                    @case('status_change')
                                        <i class="fas fa-exchange-alt text-white"></i>
                                        @break
                                    @case('profile_update')
                                        <i class="fas fa-user-edit text-white"></i>
                                        @break
                                    @default
                                        <i class="fas fa-info-circle text-white"></i>
                                @endswitch
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900">{{ $activity['title'] ?? $activity['description'] ?? 'Activity' }}</p>
                            @if(!empty($activity['description']) && isset($activity['title']))
                                <p class="text-xs text-gray-600 mt-1">{{ $activity['description'] }}</p>
                            @endif
                            @if(!empty($activity['details']))
                                <p class="text-xs text-gray-600 mt-1">{{ $activity['details'] }}</p>
                            @endif
                            <div class="flex items-center justify-between mt-2">
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
            <div class="mt-8 text-center">
                <a href="{{ route('admin.activities.index', ['driver_firebase_uid' => $driver['firebase_uid']]) }}" 
                   class="text-blue-600 hover:text-blue-800 font-medium">
                    View All Activities <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        @else
            <div class="text-center py-12">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-history text-4xl text-gray-400"></i>
                </div>
                <h5 class="text-xl font-medium text-gray-900 mb-2">No Activities Found</h5>
                <p class="text-gray-500">No recent activities for this driver.</p>
            </div>
        @endif
    </div>
</div>

<!-- Document Rejection Modal -->
<div id="rejectDocumentModal" class="fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full hidden z-50 backdrop-blur-sm">
    <div class="relative top-20 mx-auto p-0 border-0 w-96 shadow-2xl rounded-2xl bg-white animate-bounce-soft">
        <div class="p-8">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-shield-times text-red-600 text-xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900">Reject Document</h3>
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Reason for rejection</label>
                <textarea id="rejectionReason" 
                          class="w-full p-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all duration-200" 
                          rows="4" 
                          placeholder="Please provide a detailed reason for rejecting this document..."></textarea>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button onclick="closeRejectModal()" 
                        class="px-6 py-3 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 font-medium transition-all duration-200">
                    Cancel
                </button>
                <button onclick="confirmRejectDocument()" 
                        class="px-6 py-3 bg-red-600 text-white rounded-xl hover:bg-red-700 font-medium transition-all duration-200 shadow-lg hover:shadow-xl">
                    Reject Document
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Floating Action Button for Mobile -->
<div class="fixed bottom-6 right-6 lg:hidden z-40">
    <button onclick="toggleMobileMenu()" class="w-14 h-14 bg-gradient-to-r from-primary to-blue-700 text-white rounded-full shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-110 animate-pulse-glow">
        <i class="fas fa-ellipsis-v"></i>
    </button>
</div>

<!-- Mobile Quick Actions Menu -->
<div id="mobileMenu" class="fixed bottom-24 right-6 w-56 bg-white rounded-2xl shadow-xl border border-gray-100 hidden z-30 animate-slide-in-right">
    <div class="p-2">
        <a href="{{ route('admin.drivers.edit', $driver['firebase_uid']) }}" 
           class="flex items-center w-full px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 rounded-xl transition-colors">
            <i class="fas fa-edit mr-3 text-green-500"></i>Edit Driver
        </a>
        <button onclick="exportDriverData('{{ $driver['firebase_uid'] }}')" 
                class="flex items-center w-full px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 rounded-xl transition-colors">
            <i class="fas fa-download mr-3 text-blue-500"></i>Export Data
        </button>
        <button onclick="sendNotification('{{ $driver['firebase_uid'] }}')" 
                class="flex items-center w-full px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 rounded-xl transition-colors">
            <i class="fas fa-bell mr-3 text-orange-500"></i>Send Notification
        </button>
        <div class="border-t border-gray-100 my-1"></div>
        @if($driver['status'] === 'active')
            <button onclick="toggleDriverStatus('{{ $driver['firebase_uid'] }}', 'deactivate')" 
                    class="flex items-center w-full px-4 py-3 text-sm text-red-600 hover:bg-red-50 rounded-xl transition-colors">
                <i class="fas fa-ban mr-3"></i>Deactivate
            </button>
        @else
            <button onclick="toggleDriverStatus('{{ $driver['firebase_uid'] }}', 'activate')" 
                    class="flex items-center w-full px-4 py-3 text-sm text-green-600 hover:bg-green-50 rounded-xl transition-colors">
                <i class="fas fa-check-circle mr-3"></i>Activate
            </button>
        @endif
    </div>
</div>

<!-- Toast Notification Container -->
<div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

@endsection

@push('scripts')
<script>
    let actionsMenuVisible = false;
    let mobileMenuVisible = false;
    let documentToReject = null;

    function toggleActionsMenu() {
        const menu = document.getElementById('actionsMenu');
        actionsMenuVisible = !actionsMenuVisible;
        
        if (actionsMenuVisible) {
            menu.classList.remove('hidden');
            menu.classList.add('animate-fade-in');
        } else {
            menu.classList.add('hidden');
            menu.classList.remove('animate-fade-in');
        }
    }

    function toggleMobileMenu() {
        const menu = document.getElementById('mobileMenu');
        mobileMenuVisible = !mobileMenuVisible;
        
        if (mobileMenuVisible) {
            menu.classList.remove('hidden');
        } else {
            menu.classList.add('hidden');
        }
    }

    // Close menus when clicking outside
    document.addEventListener('click', function(event) {
        const actionsMenu = document.getElementById('actionsMenu');
        const mobileMenu = document.getElementById('mobileMenu');
        
        if (!event.target.closest('#actionsMenu') && !event.target.closest('button[onclick="toggleActionsMenu()"]')) {
            if (actionsMenuVisible) {
                toggleActionsMenu();
            }
        }
        
        if (!event.target.closest('#mobileMenu') && !event.target.closest('button[onclick="toggleMobileMenu()"]')) {
            if (mobileMenuVisible) {
                toggleMobileMenu();
            }
        }
    });

    function showTab(tabName) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        
        // Remove active styles from all tabs
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('border-blue-500', 'text-blue-600', 'bg-blue-50');
            button.classList.add('border-transparent', 'text-gray-500');
        });
        
        // Show selected tab content
        const selectedContent = document.getElementById(tabName + '-content');
        selectedContent.classList.remove('hidden');
        
        // Add active styles to selected tab
        const activeTab = document.getElementById(tabName + '-tab');
        activeTab.classList.remove('border-transparent', 'text-gray-500');
        activeTab.classList.add('border-blue-500', 'text-blue-600', 'bg-blue-50');
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
                showToast(`Driver ${actionText}d successfully`, 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast(`Failed to ${actionText} driver: ` + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Toggle status error:', error);
            showToast(`Error ${actionText}ing driver: Connection failed`, 'error');
        }
    }

    async function deleteDriver(driverId, driverName) {
        if (!confirm(`Are you sure you want to delete driver "${driverName}"? This action cannot be undone.`)) {
            return;
        }
        
        try {
            const response = await fetch(`/driver/admin/drivers/${driverId}`, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            
            if (response.ok) {
                showToast('Driver deleted successfully', 'success');
                setTimeout(() => window.location.href = '{{ route("admin.drivers.index") }}', 1500);
            } else {
                const result = await response.json();
                showToast('Failed to delete driver: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Delete error:', error);
            showToast('Error deleting driver: Connection failed', 'error');
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
                showToast('Document verified successfully', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast('Failed to verify document: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Verify error:', error);
            showToast('Error verifying document: Connection failed', 'error');
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
            showToast('Please provide a reason for rejection', 'error');
            return;
        }
        
        if (!documentToReject) {
            showToast('No document selected', 'error');
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
                showToast('Document rejected successfully', 'success');
                closeRejectModal();
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast('Failed to reject document: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Reject error:', error);
            showToast('Error rejecting document: Connection failed', 'error');
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
                showToast('Notification sent successfully', 'success');
            } else {
                showToast('Failed to send notification: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Send notification error:', error);
            showToast('Error sending notification: Connection failed', 'error');
        }
    }

    async function exportDriverData(driverId) {
        try {
            showToast('Exporting driver data...', 'info');
            
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
                
                showToast('Driver data exported successfully', 'success');
            } else {
                showToast('Failed to export driver data', 'error');
            }
        } catch (error) {
            console.error('Export error:', error);
            showToast('Error exporting driver data', 'error');
        }
    }

    function showToast(message, type = 'info') {
        const toastContainer = document.getElementById('toast-container');
        
        const toastTypes = {
            'success': 'bg-green-50 border-green-200 text-green-800',
            'error': 'bg-red-50 border-red-200 text-red-800',
            'info': 'bg-blue-50 border-blue-200 text-blue-800',
            'warning': 'bg-yellow-50 border-yellow-200 text-yellow-800'
        };

        const iconTypes = {
            'success': 'fas fa-check-circle text-green-500',
            'error': 'fas fa-exclamation-circle text-red-500',
            'info': 'fas fa-info-circle text-blue-500',
            'warning': 'fas fa-exclamation-triangle text-yellow-500'
        };

        const toast = document.createElement('div');
        toast.className = `${toastTypes[type]} border rounded-xl px-4 py-3 shadow-lg max-w-sm animate-slide-in-right`;
        toast.innerHTML = `
            <div class="flex items-center">
                <i class="${iconTypes[type]} mr-3"></i>
                <span class="font-medium">${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-lg hover:scale-110 transition-transform">&times;</button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 5000);
    }

    // Modal functions - Future implementations or placeholder
    function openAddVehicleModal() {
        showToast('Add Vehicle feature coming soon', 'info');
    }

    function openUploadDocumentModal() {
        showToast('Upload Document feature coming soon', 'info');
    }

    function openCreateRideModal() {
        showToast('Create Ride feature coming soon', 'info');
    }

    function viewVehicle(vehicleId) {
        showToast('View Vehicle feature coming soon', 'info');
    }

    function editVehicle(vehicleId) {
        showToast('Edit Vehicle feature coming soon', 'info');
    }

    function viewDocument(documentId) {
        showToast('View Document feature coming soon', 'info');
    }

    function downloadDocument(documentId) {
        showToast('Download Document feature coming soon', 'info');
    }

    function viewRide(rideId) {
        showToast('View Ride feature coming soon', 'info');
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

        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (!document.getElementById('rejectDocumentModal').classList.contains('hidden')) {
                    closeRejectModal();
                }
                if (actionsMenuVisible) {
                    toggleActionsMenu();
                }
                if (mobileMenuVisible) {
                    toggleMobileMenu();
                }
            }
        });

        // Show welcome toast
        setTimeout(() => {
            showToast('Driver details loaded successfully', 'success');
        }, 500);
    });

    // Add loading states for buttons
    function addLoadingState(button, duration = 2000) {
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Loading...';
        button.disabled = true;
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        }, duration);
    }

    // Add click handlers for action buttons
    document.addEventListener('click', function(e) {
        if (e.target.closest('button') && e.target.closest('button').textContent.includes('Export Data')) {
            addLoadingState(e.target.closest('button'));
        }
    });
</script>
@endpush