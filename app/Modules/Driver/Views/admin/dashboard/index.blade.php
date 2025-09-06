@extends('admin::layouts.admin')

@section('title', 'Admin Dashboard')
@section('page-title', 'Admin Dashboard')

@section('content')
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-primary">Admin Dashboard</h1>
            <p class="text-gray-600 mt-1">Overview of the Driver Management System</p>
        </div>
        <div class="flex gap-3">
            <button onclick="refreshDashboard()" 
                    class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors" 
                    id="refreshBtn">
                <i class="fas fa-sync mr-2"></i>Refresh
            </button>
            <a href="{{ route('admin.dashboard.export') }}" 
               class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                <i class="fas fa-download mr-2"></i>Export Data
            </a>
        </div>
    </div>
</div>

@if(isset($error))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ $error }}
    </div>
@endif

<!-- Quick Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Drivers -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                <i class="fas fa-users text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Total Drivers</p>
                <p class="text-2xl font-bold text-gray-900" id="total-drivers">
                    {{ $quickStats['total_drivers'] ?? 0 }}
                </p>
                <p class="text-xs text-gray-500">
                    {{ $quickStats['recent_registrations'] ?? 0 }} new this week
                </p>
            </div>
        </div>
    </div>

    <!-- Active Drivers -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                <i class="fas fa-user-check text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Active Drivers</p>
                <p class="text-2xl font-bold text-gray-900" id="active-drivers">
                    {{ $quickStats['active_drivers'] ?? 0 }}
                </p>
                <p class="text-xs text-gray-500">
                    {{ $quickStats['verified_drivers'] ?? 0 }} verified
                </p>
            </div>
        </div>
    </div>

    <!-- Total Rides -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                <i class="fas fa-car text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Total Rides</p>
                <p class="text-2xl font-bold text-gray-900" id="total-rides">
                    {{ number_format($quickStats['total_rides'] ?? 0) }}
                </p>
                <p class="text-xs text-gray-500">All time</p>
            </div>
        </div>
    </div>

    <!-- Total Earnings -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                <i class="fas fa-dollar-sign text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Total Earnings</p>
                <p class="text-2xl font-bold text-gray-900" id="total-earnings">
                    ${{ number_format($quickStats['total_earnings'] ?? 0, 2) }}
                </p>
                <p class="text-xs text-gray-500">All time</p>
            </div>
        </div>
    </div>
</div>

<!-- Real-time Metrics -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Current Activity -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-pulse mr-2 text-green-500"></i>Real-time Activity
        </h3>
        <div class="space-y-4">
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Online Drivers</span>
                <span class="font-semibold text-green-600" id="online-drivers">-</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Ongoing Rides</span>
                <span class="font-semibold text-blue-600" id="ongoing-rides">-</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Pending Verifications</span>
                <span class="font-semibold text-orange-600" id="pending-verifications">-</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">System Alerts</span>
                <span class="font-semibold text-red-600" id="system-alerts">-</span>
            </div>
        </div>
    </div>

    <!-- Pending Items -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-clock mr-2 text-orange-500"></i>Pending Items
        </h3>
        <div class="space-y-3">
            @if(isset($pendingItems) && count($pendingItems) > 0)
                @foreach($pendingItems as $key => $count)
                    @if($count > 0)
                        <div class="flex justify-between items-center p-2 bg-orange-50 rounded">
                            <span class="text-sm text-gray-700">{{ ucwords(str_replace('_', ' ', $key)) }}</span>
                            <span class="bg-orange-100 text-orange-800 text-xs px-2 py-1 rounded-full">{{ $count }}</span>
                        </div>
                    @endif
                @endforeach
            @else
                <p class="text-sm text-gray-500 text-center py-4">No pending items</p>
            @endif
        </div>
    </div>

    <!-- System Health -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-heartbeat mr-2 text-red-500"></i>System Health
        </h3>
        <div class="space-y-4" id="system-health">
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Database</span>
                <span class="w-3 h-3 bg-gray-300 rounded-full"></span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Storage</span>
                <span class="w-3 h-3 bg-gray-300 rounded-full"></span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">API Status</span>
                <span class="w-3 h-3 bg-gray-300 rounded-full"></span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Background Jobs</span>
                <span class="w-3 h-3 bg-gray-300 rounded-full"></span>
            </div>
        </div>
    </div>
</div>

<!-- Management Sections -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Driver Management -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-users mr-2 text-blue-500"></i>Driver Management
            </h3>
            <a href="{{ route('admin.drivers.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">
                View All <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <a href="{{ route('admin.drivers.create') }}" 
               class="p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-colors text-center">
                <i class="fas fa-plus text-2xl text-gray-400 mb-2"></i>
                <p class="text-sm font-medium text-gray-700">Add Driver</p>
            </a>
            <a href="{{ route('admin.drivers.index', ['verification_status' => 'pending']) }}" 
               class="p-4 bg-orange-50 border border-orange-200 rounded-lg hover:bg-orange-100 transition-colors text-center">
                <i class="fas fa-clock text-2xl text-orange-500 mb-2"></i>
                <p class="text-sm font-medium text-gray-700">Pending Verification</p>
                <p class="text-xs text-orange-600">{{ $pendingItems['pending_driver_verifications'] ?? 0 }} drivers</p>
            </a>
        </div>
    </div>

    <!-- Document Management -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-file-alt mr-2 text-green-500"></i>Document Management
            </h3>
            <a href="{{ route('admin.documents.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">
                View All <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <a href="{{ route('admin.documents.verification-queue') }}" 
               class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg hover:bg-yellow-100 transition-colors text-center">
                <i class="fas fa-file-check text-2xl text-yellow-500 mb-2"></i>
                <p class="text-sm font-medium text-gray-700">Verification Queue</p>
                <p class="text-xs text-yellow-600">{{ $pendingItems['pending_document_verifications'] ?? 0 }} documents</p>
            </a>
            <a href="{{ route('admin.documents.index', ['expiry_status' => 'expired']) }}" 
               class="p-4 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors text-center">
                <i class="fas fa-exclamation-triangle text-2xl text-red-500 mb-2"></i>
                <p class="text-sm font-medium text-gray-700">Expired Documents</p>
                <p class="text-xs text-red-600">{{ $pendingItems['expired_documents'] ?? 0 }} expired</p>
            </a>
        </div>
    </div>
</div>

<!-- Recent Activities -->
<div class="bg-white rounded-lg shadow-sm border">
    <div class="p-6 border-b">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-history mr-2 text-purple-500"></i>Recent System Activities
            </h3>
            <a href="{{ route('admin.activities.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">
                View All <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
    </div>
    <div class="p-6">
        @if(isset($recentActivities) && count($recentActivities) > 0)
            <div class="space-y-4">
                @foreach($recentActivities as $activity)
                    <div class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg">
                        <div class="flex-shrink-0">
                            @switch($activity['activity_type'] ?? 'general')
                                @case('driver_registration')
                                    <i class="fas fa-user-plus text-green-500"></i>
                                    @break
                                @case('document_upload')
                                    <i class="fas fa-file-upload text-blue-500"></i>
                                    @break
                                @case('ride_completed')
                                    <i class="fas fa-check-circle text-green-500"></i>
                                    @break
                                @case('verification')
                                    <i class="fas fa-shield-check text-blue-500"></i>
                                    @break
                                @default
                                    <i class="fas fa-info-circle text-gray-500"></i>
                            @endswitch
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-900">{{ $activity['title'] ?? 'System Activity' }}</p>
                            <p class="text-xs text-gray-500">
                                {{ $activity['driver_name'] ?? 'System' }} • 
                                {{ \Carbon\Carbon::parse($activity['created_at'] ?? now())->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 text-center py-8">No recent activities</p>
        @endif
    </div>
</div>

<!-- Quick Actions -->
<div class="fixed bottom-6 right-6 z-50">
    <div class="relative">
        <button onclick="toggleQuickActions()" 
                class="bg-primary text-white p-4 rounded-full shadow-lg hover:bg-blue-700 transition-colors" 
                id="quickActionsBtn">
            <i class="fas fa-plus text-xl"></i>
        </button>
        <div id="quickActionsMenu" class="absolute bottom-16 right-0 bg-white rounded-lg shadow-xl border p-2 min-w-48 hidden">
            <a href="{{ route('admin.drivers.create') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">
                <i class="fas fa-user-plus mr-3"></i>Add Driver
            </a>
            <a href="{{ route('admin.documents.create') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">
                <i class="fas fa-file-plus mr-3"></i>Upload Document
            </a>
            <a href="{{ route('admin.vehicles.create') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">
                <i class="fas fa-car mr-3"></i>Add Vehicle
            </a>
            <a href="{{ route('admin.rides.create') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">
                <i class="fas fa-route mr-3"></i>Create Ride
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let quickActionsVisible = false;

    function toggleQuickActions() {
        const menu = document.getElementById('quickActionsMenu');
        const btn = document.getElementById('quickActionsBtn');
        
        quickActionsVisible = !quickActionsVisible;
        
        if (quickActionsVisible) {
            menu.classList.remove('hidden');
            btn.querySelector('i').className = 'fas fa-times text-xl';
        } else {
            menu.classList.add('hidden');
            btn.querySelector('i').className = 'fas fa-plus text-xl';
        }
    }

    // Close quick actions when clicking outside
    document.addEventListener('click', function(event) {
        const menu = document.getElementById('quickActionsMenu');
        const btn = document.getElementById('quickActionsBtn');
        
        if (!menu.contains(event.target) && !btn.contains(event.target) && quickActionsVisible) {
            toggleQuickActions();
        }
    });

    async function refreshDashboard() {
        const btn = document.getElementById('refreshBtn');
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Refreshing...';
        
        try {
            // Refresh real-time metrics
            await Promise.all([
                updateRealTimeMetrics(),
                updateSystemHealth()
            ]);
            
            showNotification('Dashboard refreshed successfully', 'success');
        } catch (error) {
            console.error('Refresh error:', error);
            showNotification('Error refreshing dashboard', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }

    async function updateRealTimeMetrics() {
        try {
            const response = await fetch('', {
                headers: {
                    'Accept': 'application/json'
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    document.getElementById('online-drivers').textContent = data.data.active_drivers || 0;
                    document.getElementById('ongoing-rides').textContent = data.data.ongoing_rides || 0;
                    document.getElementById('pending-verifications').textContent = data.data.pending_verifications || 0;
                    document.getElementById('system-alerts').textContent = data.data.system_alerts || 0;
                }
            }
        } catch (error) {
            console.error('Error updating real-time metrics:', error);
        }
    }

    async function updateSystemHealth() {
        try {
            const response = await fetch('{{ route("admin.dashboard.system-health") }}', {
                headers: {
                    'Accept': 'application/json'
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    const healthContainer = document.getElementById('system-health');
                    const indicators = healthContainer.querySelectorAll('.w-3.h-3');
                    
                    const healthData = data.data;
                    const services = ['database_status', 'storage_status', 'api_status', 'background_jobs'];
                    
                    services.forEach((service, index) => {
                        if (indicators[index] && healthData[service]) {
                            const status = healthData[service].status;
                            indicators[index].className = 'w-3 h-3 rounded-full ' + getHealthStatusColor(status);
                        }
                    });
                }
            }
        } catch (error) {
            console.error('Error updating system health:', error);
        }
    }

    function getHealthStatusColor(status) {
        switch (status) {
            case 'healthy':
                return 'bg-green-500';
            case 'warning':
                return 'bg-yellow-500';
            case 'error':
                return 'bg-red-500';
            default:
                return 'bg-gray-300';
        }
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

    // Auto-refresh every 30 seconds
    setInterval(() => {
        updateRealTimeMetrics();
        updateSystemHealth();
    }, 30000);

    // Initial load
    document.addEventListener('DOMContentLoaded', function() {
        updateRealTimeMetrics();
        updateSystemHealth();
    });
</script>
@endpush