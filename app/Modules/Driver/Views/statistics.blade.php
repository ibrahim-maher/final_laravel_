@extends('admin::layouts.admin')

@section('title', 'Driver Statistics')
@section('page-title', 'Driver Statistics')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-primary">Driver Statistics</h1>
        <p class="text-gray-600 mt-1">Comprehensive analytics and insights about your drivers</p>
    </div>
    <div class="flex gap-3">
        <button onclick="refreshStats()" id="refreshBtn"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            <i class="fas fa-sync mr-2"></i>Refresh Data
        </button>
        <a href="{{ route('driver.index') }}" 
           class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back to Drivers
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

<!-- Overview Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Drivers -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Total Drivers</p>
                <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['total_drivers'] ?? 0) }}</p>
                <p class="text-sm text-gray-500">
                    +{{ number_format($stats['recent_registrations'] ?? 0) }} this week
                </p>
            </div>
            <div class="w-12 h-12 bg-primary bg-opacity-10 rounded-lg flex items-center justify-center">
                <i class="fas fa-users text-2xl text-primary"></i>
            </div>
        </div>
    </div>

    <!-- Active Drivers -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Active Drivers</p>
                <p class="text-3xl font-bold text-green-600">{{ number_format($stats['active_drivers'] ?? 0) }}</p>
                <p class="text-sm text-gray-500">
                    {{ $stats['total_drivers'] > 0 ? round(($stats['active_drivers'] / $stats['total_drivers']) * 100, 1) : 0 }}% of total
                </p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-check-circle text-2xl text-green-600"></i>
            </div>
        </div>
    </div>

    <!-- Verified Drivers -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Verified Drivers</p>
                <p class="text-3xl font-bold text-blue-600">{{ number_format($stats['verified_drivers'] ?? 0) }}</p>
                <p class="text-sm text-gray-500">
                    {{ $stats['total_drivers'] > 0 ? round(($stats['verified_drivers'] / $stats['total_drivers']) * 100, 1) : 0 }}% verified
                </p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-shield-check text-2xl text-blue-600"></i>
            </div>
        </div>
    </div>

    <!-- Available Drivers -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Available Now</p>
                <p class="text-3xl font-bold text-yellow-600">{{ number_format($stats['available_drivers'] ?? 0) }}</p>
                <p class="text-sm text-gray-500">Ready for rides</p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-circle text-2xl text-yellow-600"></i>
            </div>
        </div>
    </div>
</div>

<!-- Performance Metrics -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Average Rating -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Average Rating</h3>
            <i class="fas fa-star text-yellow-400"></i>
        </div>
        <div class="text-center">
            <div class="text-4xl font-bold text-yellow-600 mb-2">
                {{ number_format($stats['average_rating'] ?? 0, 1) }}
            </div>
            <div class="flex justify-center mb-2">
                @for($i = 1; $i <= 5; $i++)
                    @if($i <= floor($stats['average_rating'] ?? 0))
                        <i class="fas fa-star text-yellow-400"></i>
                    @elseif($i - 0.5 <= ($stats['average_rating'] ?? 0))
                        <i class="fas fa-star-half-alt text-yellow-400"></i>
                    @else
                        <i class="far fa-star text-gray-300"></i>
                    @endif
                @endfor
            </div>
            <p class="text-sm text-gray-500">Based on all driver ratings</p>
        </div>
    </div>

    <!-- Total Rides -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Total Rides</h3>
            <i class="fas fa-route text-green-500"></i>
        </div>
        <div class="text-center">
            <div class="text-4xl font-bold text-green-600 mb-2">
                {{ number_format($stats['total_rides'] ?? 0) }}
            </div>
            <p class="text-sm text-gray-500">Completed by all drivers</p>
        </div>
    </div>

    <!-- Total Earnings -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Total Earnings</h3>
            <i class="fas fa-dollar-sign text-green-500"></i>
        </div>
        <div class="text-center">
            <div class="text-4xl font-bold text-green-600 mb-2">
                ${{ number_format($stats['total_earnings'] ?? 0, 0) }}
            </div>
            <p class="text-sm text-gray-500">Driver earnings to date</p>
        </div>
    </div>
</div>

<!-- Status Breakdown Charts -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Driver Status Distribution -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-chart-pie mr-2 text-primary"></i>Driver Status Distribution
        </h3>
        <div class="space-y-4">
            @php
                $statusColors = [
                    'active' => ['bg-green-500', 'text-green-700'],
                    'inactive' => ['bg-red-500', 'text-red-700'],
                    'suspended' => ['bg-orange-500', 'text-orange-700'],
                    'pending' => ['bg-yellow-500', 'text-yellow-700']
                ];
            @endphp
            
            @foreach($stats['drivers_by_status'] ?? [] as $status => $count)
                @php
                    $percentage = $stats['total_drivers'] > 0 ? round(($count / $stats['total_drivers']) * 100, 1) : 0;
                    $colors = $statusColors[$status] ?? ['bg-gray-500', 'text-gray-700'];
                @endphp
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-4 h-4 {{ $colors[0] }} rounded-full mr-3"></div>
                        <span class="text-sm font-medium {{ $colors[1] }}">{{ ucfirst($status) }}</span>
                    </div>
                    <div class="flex items-center">
                        <span class="text-sm text-gray-600 mr-2">{{ number_format($count) }}</span>
                        <span class="text-sm text-gray-500">({{ $percentage }}%)</span>
                    </div>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="{{ $colors[0] }} h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Verification Status Distribution -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-shield-check mr-2 text-primary"></i>Verification Status
        </h3>
        <div class="space-y-4">
            @php
                $verificationColors = [
                    'verified' => ['bg-blue-500', 'text-blue-700'],
                    'pending' => ['bg-yellow-500', 'text-yellow-700'],
                    'rejected' => ['bg-red-500', 'text-red-700']
                ];
            @endphp
            
            @foreach($stats['drivers_by_verification'] ?? [] as $verification => $count)
                @php
                    $percentage = $stats['total_drivers'] > 0 ? round(($count / $stats['total_drivers']) * 100, 1) : 0;
                    $colors = $verificationColors[$verification] ?? ['bg-gray-500', 'text-gray-700'];
                @endphp
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-4 h-4 {{ $colors[0] }} rounded-full mr-3"></div>
                        <span class="text-sm font-medium {{ $colors[1] }}">{{ ucfirst($verification) }}</span>
                    </div>
                    <div class="flex items-center">
                        <span class="text-sm text-gray-600 mr-2">{{ number_format($count) }}</span>
                        <span class="text-sm text-gray-500">({{ $percentage }}%)</span>
                    </div>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="{{ $colors[0] }} h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Availability Status -->
<div class="bg-white rounded-lg shadow-sm border p-6 mb-8">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
        <i class="fas fa-signal mr-2 text-primary"></i>Current Availability Status
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @php
            $availabilityData = [
                'available' => [
                    'count' => $stats['available_drivers'] ?? 0,
                    'color' => 'green',
                    'icon' => 'fa-circle',
                    'label' => 'Available'
                ],
                'busy' => [
                    'count' => $stats['busy_drivers'] ?? 0,
                    'color' => 'yellow',
                    'icon' => 'fa-circle',
                    'label' => 'Busy'
                ],
                'offline' => [
                    'count' => $stats['offline_drivers'] ?? 0,
                    'color' => 'gray',
                    'icon' => 'fa-circle',
                    'label' => 'Offline'
                ]
            ];
        @endphp
        
        @foreach($availabilityData as $status => $data)
            @php
                $percentage = $stats['total_drivers'] > 0 ? round(($data['count'] / $stats['total_drivers']) * 100, 1) : 0;
            @endphp
            <div class="text-center p-4 border rounded-lg">
                <div class="w-16 h-16 bg-{{ $data['color'] }}-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fas {{ $data['icon'] }} text-2xl text-{{ $data['color'] }}-600"></i>
                </div>
                <h4 class="text-lg font-semibold text-gray-900">{{ $data['label'] }}</h4>
                <p class="text-2xl font-bold text-{{ $data['color'] }}-600 mb-1">{{ number_format($data['count']) }}</p>
                <p class="text-sm text-gray-500">{{ $percentage }}% of drivers</p>
            </div>
        @endforeach
    </div>
</div>

<!-- Quick Actions -->
<div class="bg-white rounded-lg shadow-sm border p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
        <i class="fas fa-bolt mr-2 text-primary"></i>Quick Actions
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('driver.index', ['status' => 'pending']) }}" 
           class="flex items-center p-4 border rounded-lg hover:bg-gray-50 transition-colors">
            <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center mr-3">
                <i class="fas fa-clock text-yellow-600"></i>
            </div>
            <div>
                <p class="font-medium text-gray-900">Pending Drivers</p>
                <p class="text-sm text-gray-500">{{ number_format($stats['pending_verification'] ?? 0) }} awaiting review</p>
            </div>
        </a>
        
        <a href="{{ route('driver.index', ['verification_status' => 'pending']) }}" 
           class="flex items-center p-4 border rounded-lg hover:bg-gray-50 transition-colors">
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                <i class="fas fa-shield-question text-blue-600"></i>
            </div>
            <div>
                <p class="font-medium text-gray-900">Need Verification</p>
                <p class="text-sm text-gray-500">Review documents</p>
            </div>
        </a>
        
        <a href="{{ route('driver.index', ['status' => 'suspended']) }}" 
           class="flex items-center p-4 border rounded-lg hover:bg-gray-50 transition-colors">
            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center mr-3">
                <i class="fas fa-pause-circle text-orange-600"></i>
            </div>
            <div>
                <p class="font-medium text-gray-900">Suspended</p>
                <p class="text-sm text-gray-500">{{ number_format($stats['suspended_drivers'] ?? 0) }} suspended drivers</p>
            </div>
        </a>
        
        <button onclick="syncFirebaseDrivers()" 
                class="flex items-center p-4 border rounded-lg hover:bg-gray-50 transition-colors">
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                <i class="fas fa-sync text-purple-600"></i>
            </div>
            <div>
                <p class="font-medium text-gray-900">Sync Firebase</p>
                <p class="text-sm text-gray-500">Update from Firebase</p>
            </div>
        </button>
    </div>
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

    function showLoading(button) {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Loading...';
    }

    function hideLoading(button, originalText) {
        button.disabled = false;
        button.innerHTML = originalText;
    }

    async function refreshStats() {
        const btn = document.getElementById('refreshBtn');
        const originalText = btn.innerHTML;
        showLoading(btn);
        
        try {
            const response = await fetch('{{ route("driver.api.statistics") }}', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });
            
            if (response.ok) {
                showNotification('Statistics refreshed successfully!', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showNotification('Failed to refresh statistics', 'error');
            }
        } catch (error) {
            console.error('Refresh error:', error);
            showNotification('Error refreshing statistics: Connection failed', 'error');
        } finally {
            hideLoading(btn, originalText);
        }
    }

    async function syncFirebaseDrivers() {
        if (!confirm('This will sync drivers from Firebase. This may take some time. Continue?')) {
            return;
        }
        
        try {
            const response = await fetch('{{ route("driver.sync") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                showNotification('Drivers synced successfully! Refreshing statistics...', 'success');
                setTimeout(() => window.location.reload(), 2000);
            } else {
                showNotification('Failed to sync drivers: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Sync error:', error);
            showNotification('Error syncing drivers: Connection failed', 'error');
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Driver statistics page initialized');
        
        // Auto-refresh every 5 minutes
        setInterval(() => {
            console.log('Auto-refreshing statistics...');
            refreshStats();
        }, 300000); // 5 minutes
    });
</script>
@endpush