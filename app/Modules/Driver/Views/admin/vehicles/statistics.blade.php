@extends('admin::layouts.admin')

@section('title', 'Vehicle Statistics')
@section('page-title', 'Vehicle Statistics')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-primary">Vehicle Statistics</h1>
        <p class="text-gray-600 mt-1">Comprehensive analytics and insights for vehicle management</p>
    </div>
    <div class="flex gap-3">
        <button onclick="exportStatistics()"
            class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
            <i class="fas fa-download mr-2"></i>Export Report
        </button>
        <a href="{{ route('admin.vehicles.index') }}"
            class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back to Vehicles
        </a>
    </div>
</div>

<!-- Overview Stats -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                <i class="fas fa-car text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Total Vehicles</p>
                <p class="text-2xl font-bold text-gray-900">{{ $statistics['total_vehicles'] ?? 0 }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                <i class="fas fa-check-circle text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Active Vehicles</p>
                <p class="text-2xl font-bold text-gray-900">{{ $statistics['active_vehicles'] ?? 0 }}</p>
                <p class="text-xs text-gray-500">
                    {{ $statistics['total_vehicles'] > 0 ? round(($statistics['active_vehicles'] ?? 0) / $statistics['total_vehicles'] * 100, 1) : 0 }}% of total
                </p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                <i class="fas fa-shield-check text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Verified Vehicles</p>
                <p class="text-2xl font-bold text-gray-900">{{ $statistics['verified_vehicles'] ?? 0 }}</p>
                <p class="text-xs text-gray-500">
                    {{ $statistics['total_vehicles'] > 0 ? round(($statistics['verified_vehicles'] ?? 0) / $statistics['total_vehicles'] * 100, 1) : 0 }}% verified
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
                <p class="text-sm font-medium text-gray-600">Pending Verification</p>
                <p class="text-2xl font-bold text-gray-900">{{ $statistics['pending_verification'] ?? 0 }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Status Breakdown -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h2 class="text-xl font-semibold mb-4">
            <i class="fas fa-chart-pie mr-2 text-primary"></i>Vehicle Status Distribution
        </h2>

        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-green-500 rounded mr-3"></div>
                    <span class="text-sm font-medium">Active</span>
                </div>
                <div class="flex items-center">
                    <span class="text-sm text-gray-600 mr-2">{{ $statistics['active_vehicles'] ?? 0 }}</span>
                    <span class="text-xs text-gray-500">
                        ({{ $statistics['total_vehicles'] > 0 ? round(($statistics['active_vehicles'] ?? 0) / $statistics['total_vehicles'] * 100, 1) : 0 }}%)
                    </span>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-gray-500 rounded mr-3"></div>
                    <span class="text-sm font-medium">Inactive</span>
                </div>
                <div class="flex items-center">
                    <span class="text-sm text-gray-600 mr-2">{{ ($statistics['total_vehicles'] ?? 0) - ($statistics['active_vehicles'] ?? 0) - ($statistics['maintenance_vehicles'] ?? 0) - ($statistics['suspended_vehicles'] ?? 0) }}</span>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-orange-500 rounded mr-3"></div>
                    <span class="text-sm font-medium">Maintenance</span>
                </div>
                <div class="flex items-center">
                    <span class="text-sm text-gray-600 mr-2">{{ $statistics['maintenance_vehicles'] ?? 0 }}</span>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-red-500 rounded mr-3"></div>
                    <span class="text-sm font-medium">Suspended</span>
                </div>
                <div class="flex items-center">
                    <span class="text-sm text-gray-600 mr-2">{{ $statistics['suspended_vehicles'] ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h2 class="text-xl font-semibold mb-4">
            <i class="fas fa-chart-bar mr-2 text-primary"></i>Vehicle Types
        </h2>

        <div class="space-y-4">
            @if(isset($statistics['vehicle_types_breakdown']) && count($statistics['vehicle_types_breakdown']) > 0)
            @foreach($statistics['vehicle_types_breakdown'] as $type => $count)
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-{{ $type === 'sedan' ? 'car' : ($type === 'suv' ? 'truck' : 'car-side') }} text-gray-400 mr-3"></i>
                    <span class="text-sm font-medium">{{ ucfirst($type) }}</span>
                </div>
                <div class="flex items-center">
                    <span class="text-sm text-gray-600 mr-2">{{ $count }}</span>
                    <span class="text-xs text-gray-500">
                        ({{ $statistics['total_vehicles'] > 0 ? round($count / $statistics['total_vehicles'] * 100, 1) : 0 }}%)
                    </span>
                </div>
            </div>
            @endforeach
            @else
            <p class="text-gray-500 text-center py-4">No vehicle type data available</p>
            @endif
        </div>
    </div>
</div>

<!-- Registration & Insurance Alerts -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h2 class="text-xl font-semibold mb-4">
            <i class="fas fa-exclamation-triangle mr-2 text-yellow-600"></i>Registration Alerts
        </h2>

        <div class="space-y-3">
            <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-times-circle text-red-600 mr-2"></i>
                    <span class="text-sm font-medium text-red-800">Expired Registration</span>
                </div>
                <span class="text-sm font-bold text-red-600">{{ $statistics['expired_registrations'] ?? 0 }}</span>
            </div>

            <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-clock text-yellow-600 mr-2"></i>
                    <span class="text-sm font-medium text-yellow-800">Expiring Soon (30 days)</span>
                </div>
                <span class="text-sm font-bold text-yellow-600">{{ $statistics['expiring_soon_registration'] ?? 0 }}</span>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h2 class="text-xl font-semibold mb-4">
            <i class="fas fa-shield-alt mr-2 text-blue-600"></i>Insurance Alerts
        </h2>

        <div class="space-y-3">
            <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-times-circle text-red-600 mr-2"></i>
                    <span class="text-sm font-medium text-red-800">Expired Insurance</span>
                </div>
                <span class="text-sm font-bold text-red-600">{{ $statistics['expired_insurance'] ?? 0 }}</span>
            </div>

            <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-clock text-yellow-600 mr-2"></i>
                    <span class="text-sm font-medium text-yellow-800">Expiring Soon (30 days)</span>
                </div>
                <span class="text-sm font-bold text-yellow-600">{{ $statistics['expiring_soon_insurance'] ?? 0 }}</span>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
    <h2 class="text-xl font-semibold mb-4">
        <i class="fas fa-chart-line mr-2 text-primary"></i>Recent Activity
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="text-center">
            <div class="text-3xl font-bold text-green-600">{{ $statistics['recent_registrations'] ?? 0 }}</div>
            <div class="text-sm text-gray-600">New vehicles (last 7 days)</div>
        </div>

        <div class="text-center">
            <div class="text-3xl font-bold text-blue-600">{{ $statistics['verified_vehicles'] ?? 0 }}</div>
            <div class="text-sm text-gray-600">Total verified vehicles</div>
        </div>

        <div class="text-center">
            <div class="text-3xl font-bold text-purple-600">
                {{ $statistics['total_vehicles'] > 0 ? round(($statistics['verified_vehicles'] ?? 0) / $statistics['total_vehicles'] * 100, 1) : 0 }}%
            </div>
            <div class="text-sm text-gray-600">Verification rate</div>
        </div>
    </div>
</div>

<!-- Fuel Type Distribution -->
@if(isset($statistics['fuel_types_breakdown']) && count($statistics['fuel_types_breakdown']) > 0)
<div class="bg-white rounded-lg shadow-sm border p-6">
    <h2 class="text-xl font-semibold mb-4">
        <i class="fas fa-gas-pump mr-2 text-primary"></i>Fuel Type Distribution
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($statistics['fuel_types_breakdown'] as $fuelType => $count)
        <div class="text-center p-4 border rounded-lg">
            <div class="text-2xl font-bold text-gray-900">{{ $count }}</div>
            <div class="text-sm text-gray-600">{{ ucfirst($fuelType ?: 'Unknown') }}</div>
            <div class="text-xs text-gray-500">
                {{ $statistics['total_vehicles'] > 0 ? round($count / $statistics['total_vehicles'] * 100, 1) : 0 }}% of fleet
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
    function exportStatistics() {
        // Create a simple CSV export of the statistics
        const stats = @json($statistics ?? []);

        let csvContent = "Vehicle Statistics Report\n";
        csvContent += "Generated: " + new Date().toLocaleString() + "\n\n";
        csvContent += "Metric,Value\n";
        csvContent += "Total Vehicles," + (stats.total_vehicles || 0) + "\n";
        csvContent += "Active Vehicles," + (stats.active_vehicles || 0) + "\n";
        csvContent += "Verified Vehicles," + (stats.verified_vehicles || 0) + "\n";
        csvContent += "Pending Verification," + (stats.pending_verification || 0) + "\n";
        csvContent += "Maintenance Vehicles," + (stats.maintenance_vehicles || 0) + "\n";
        csvContent += "Suspended Vehicles," + (stats.suspended_vehicles || 0) + "\n";
        csvContent += "Expired Registrations," + (stats.expired_registrations || 0) + "\n";
        csvContent += "Expired Insurance," + (stats.expired_insurance || 0) + "\n";
        csvContent += "Recent Registrations (7 days)," + (stats.recent_registrations || 0) + "\n";

        // Create and download file
        const blob = new Blob([csvContent], {
            type: 'text/csv;charset=utf-8;'
        });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', 'vehicle_statistics_' + new Date().toISOString().split('T')[0] + '.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        showNotification('Statistics report exported successfully', 'success');
    }

    function showNotification(message, type = 'info') {
        const alertClass = {
            'success': 'bg-green-100 border-green-400 text-green-700',
            'error': 'bg-red-100 border-red-400 text-red-700',
            'info': 'bg-blue-100 border-blue-400 text-blue-700',
            'warning': 'bg-yellow-100 border-yellow-400 text-yellow-700'
        } [type] || 'bg-gray-100 border-gray-400 text-gray-700';

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
</script>
@endpush