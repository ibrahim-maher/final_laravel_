{{-- resources/views/coupon/admin/coupons/statistics.blade.php --}}

@extends('admin::layouts.admin')

@section('title', 'Coupon Statistics')
@section('page-title', 'Coupon Statistics')

@section('content')
<div>
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-primary">Coupon Statistics</h1>
            <nav class="text-sm text-gray-600 mt-1">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a> > 
                <a href="{{ route('coupons.index') }}">Coupons</a> > 
                <span class="text-gray-400">Statistics</span>
            </nav>
        </div>
        <div class="flex gap-3">
            <button onclick="refreshStats()" 
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-sync-alt mr-2"></i>Refresh
            </button>
            <a href="{{ route('coupons.export', ['format' => 'csv']) }}" 
               class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                <i class="fas fa-download mr-2"></i>Export Data
            </a>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Coupons -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Total Coupons</p>
                <p class="text-3xl font-bold text-gray-900">{{ number_format($statistics['total_coupons']) }}</p>
            </div>
            <div class="bg-blue-100 p-3 rounded-full">
                <i class="fas fa-ticket-alt text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Active Coupons -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Active Coupons</p>
                <p class="text-3xl font-bold text-green-600">{{ number_format($statistics['active_coupons']) }}</p>
            </div>
            <div class="bg-green-100 p-3 rounded-full">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
        </div>
        @if($statistics['total_coupons'] > 0)
            <p class="text-xs text-gray-500 mt-2">
                {{ round(($statistics['active_coupons'] / $statistics['total_coupons']) * 100, 1) }}% of total
            </p>
        @endif
    </div>

    <!-- Total Usage -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Total Usages</p>
                <p class="text-3xl font-bold text-purple-600">{{ number_format($statistics['total_usages']) }}</p>
            </div>
            <div class="bg-purple-100 p-3 rounded-full">
                <i class="fas fa-chart-line text-purple-600 text-xl"></i>
            </div>
        </div>
        <p class="text-xs text-gray-500 mt-2">{{ number_format($statistics['recent_usages']) }} in last 30 days</p>
    </div>

    <!-- Total Discount Given -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Total Savings</p>
                <p class="text-3xl font-bold text-orange-600">${{ number_format($statistics['total_discount_given'], 2) }}</p>
            </div>
            <div class="bg-orange-100 p-3 rounded-full">
                <i class="fas fa-dollar-sign text-orange-600 text-xl"></i>
            </div>
        </div>
        <p class="text-xs text-gray-500 mt-2">Avg: ${{ number_format($statistics['average_discount'], 2) }}</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Status Distribution -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Coupon Status Distribution</h3>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-green-500 rounded-full mr-3"></div>
                    <span class="text-sm text-gray-700">Active</span>
                </div>
                <div class="text-right">
                    <span class="font-semibold">{{ number_format($statistics['active_coupons']) }}</span>
                    @if($statistics['total_coupons'] > 0)
                        <span class="text-xs text-gray-500 ml-2">
                            ({{ round(($statistics['active_coupons'] / $statistics['total_coupons']) * 100, 1) }}%)
                        </span>
                    @endif
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-red-500 rounded-full mr-3"></div>
                    <span class="text-sm text-gray-700">Expired</span>
                </div>
                <div class="text-right">
                    <span class="font-semibold">{{ number_format($statistics['expired_coupons']) }}</span>
                    @if($statistics['total_coupons'] > 0)
                        <span class="text-xs text-gray-500 ml-2">
                            ({{ round(($statistics['expired_coupons'] / $statistics['total_coupons']) * 100, 1) }}%)
                        </span>
                    @endif
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-gray-500 rounded-full mr-3"></div>
                    <span class="text-sm text-gray-700">Disabled</span>
                </div>
                <div class="text-right">
                    <span class="font-semibold">{{ number_format($statistics['disabled_coupons']) }}</span>
                    @if($statistics['total_coupons'] > 0)
                        <span class="text-xs text-gray-500 ml-2">
                            ({{ round(($statistics['disabled_coupons'] / $statistics['total_coupons']) * 100, 1) }}%)
                        </span>
                    @endif
                </div>
            </div>

            @if($statistics['expiring_soon'] > 0)
                <div class="flex items-center justify-between border-t pt-4">
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-yellow-500 rounded-full mr-3"></div>
                        <span class="text-sm text-gray-700">Expiring Soon (7 days)</span>
                    </div>
                    <div class="text-right">
                        <span class="font-semibold text-yellow-600">{{ number_format($statistics['expiring_soon']) }}</span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Firebase Sync Status -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Firebase Sync Status</h3>
            <button onclick="checkSyncHealth()" 
                    class="text-sm text-blue-600 hover:text-blue-800">
                <i class="fas fa-sync-alt mr-1"></i>Check Health
            </button>
        </div>

        <div class="space-y-4">
            <!-- Sync Overview -->
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">Sync Progress</span>
                    <span class="text-sm text-gray-600">
                        {{ $syncHealth['synced_coupons'] ?? 0 }} / {{ $syncHealth['total_coupons'] ?? 0 }}
                    </span>
                </div>
                @php
                    $syncPercentage = ($syncHealth['total_coupons'] ?? 0) > 0 
                        ? round((($syncHealth['synced_coupons'] ?? 0) / ($syncHealth['total_coupons'] ?? 0)) * 100, 1) 
                        : 100;
                @endphp
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $syncPercentage }}%"></div>
                </div>
                <p class="text-xs text-gray-500 mt-1">{{ $syncPercentage }}% synced</p>
            </div>

            <!-- Pending Syncs -->
            @if(($syncHealth['pending_syncs'] ?? 0) > 0)
                <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-clock text-yellow-600 mr-2"></i>
                        <span class="text-sm text-yellow-800">Pending Syncs</span>
                    </div>
                    <span class="font-semibold text-yellow-800">{{ $syncHealth['pending_syncs'] }}</span>
                </div>
            @endif

            <!-- Old Pending -->
            @if(($syncHealth['old_pending'] ?? 0) > 0)
                <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                        <span class="text-sm text-red-800">Old Pending (>1hr)</span>
                    </div>
                    <span class="font-semibold text-red-800">{{ $syncHealth['old_pending'] }}</span>
                </div>
            @endif

            <!-- Sync Actions -->
            <div class="flex gap-2 pt-2">
                <button onclick="runAutoSync()" 
                        class="flex-1 px-3 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition-colors">
                    Auto Sync
                </button>
                <button onclick="forceSyncAll()" 
                        class="flex-1 px-3 py-2 bg-red-600 text-white text-sm rounded hover:bg-red-700 transition-colors">
                    Force Sync All
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Usage Analytics -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mt-8">
    <!-- Top Performing Coupons -->
    <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Usage Analytics</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="text-center p-4 bg-blue-50 rounded-lg">
                <p class="text-2xl font-bold text-blue-600">{{ number_format($statistics['total_usages']) }}</p>
                <p class="text-sm text-gray-600">Total Uses</p>
            </div>
            <div class="text-center p-4 bg-green-50 rounded-lg">
                <p class="text-2xl font-bold text-green-600">{{ number_format($statistics['recent_usages']) }}</p>
                <p class="text-sm text-gray-600">Last 30 Days</p>
            </div>
            <div class="text-center p-4 bg-purple-50 rounded-lg">
                @php
                    $usageRate = $statistics['total_coupons'] > 0 
                        ? round(($statistics['total_usages'] / $statistics['total_coupons']), 2) 
                        : 0;
                @endphp
                <p class="text-2xl font-bold text-purple-600">{{ $usageRate }}</p>
                <p class="text-sm text-gray-600">Avg Uses/Coupon</p>
            </div>
        </div>

        <!-- Usage Trends Chart Placeholder -->
        <div class="h-64 bg-gray-50 rounded-lg flex items-center justify-center">
            <div class="text-center text-gray-500">
                <i class="fas fa-chart-line text-4xl mb-2"></i>
                <p>Usage trends chart will be displayed here</p>
                <p class="text-sm">Integration with Chart.js recommended</p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
        
        <div class="space-y-3">
            <a href="{{ route('coupons.create') }}" 
               class="block w-full px-4 py-3 bg-blue-600 text-white text-center rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>Create New Coupon
            </a>
            
            <button onclick="openBulkCreateModal()" 
                    class="block w-full px-4 py-3 bg-green-600 text-white text-center rounded-lg hover:bg-green-700 transition-colors">
                <i class="fas fa-copy mr-2"></i>Bulk Create
            </button>
            
            <a href="{{ route('coupons.export', ['format' => 'csv']) }}" 
               class="block w-full px-4 py-3 bg-purple-600 text-white text-center rounded-lg hover:bg-purple-700 transition-colors">
                <i class="fas fa-download mr-2"></i>Export All
            </a>
            
            <button onclick="cleanupExpired()" 
                    class="block w-full px-4 py-3 bg-orange-600 text-white text-center rounded-lg hover:bg-orange-700 transition-colors">
                <i class="fas fa-trash-alt mr-2"></i>Cleanup Expired
            </button>
        </div>

        <!-- System Health -->
        <div class="mt-6 pt-4 border-t">
            <h4 class="font-medium text-gray-900 mb-3">System Health</h4>
            <div class="space-y-2">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">Database</span>
                    <span class="text-green-600">
                        <i class="fas fa-check-circle mr-1"></i>Healthy
                    </span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">Firebase Sync</span>
                    <span class="{{ ($syncHealth['pending_syncs'] ?? 0) > 0 ? 'text-yellow-600' : 'text-green-600' }}">
                        <i class="fas {{ ($syncHealth['pending_syncs'] ?? 0) > 0 ? 'fa-clock' : 'fa-check-circle' }} mr-1"></i>
                        {{ ($syncHealth['pending_syncs'] ?? 0) > 0 ? 'Syncing' : 'Synced' }}
                    </span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">Cache</span>
                    <span class="text-green-600">
                        <i class="fas fa-check-circle mr-1"></i>Active
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Unsynced Coupons Alert -->
@if(($statistics['unsynced_count'] ?? 0) > 0)
    <div class="mt-8 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle text-yellow-600 mr-3"></i>
            <div>
                <h4 class="font-medium text-yellow-800">Sync Attention Needed</h4>
                <p class="text-sm text-yellow-700 mt-1">
                    {{ $statistics['unsynced_count'] }} coupons are pending Firebase sync. 
                    <button onclick="runAutoSync()" class="underline hover:no-underline">Run sync now</button>
                </p>
            </div>
        </div>
    </div>
@endif

@endsection

@push('scripts')
<script>
// Refresh statistics
async function refreshStats() {
    window.location.reload();
}
</script>
@endpush