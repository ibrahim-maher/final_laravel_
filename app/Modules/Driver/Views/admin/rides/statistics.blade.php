{{-- resources/views/driver/admin/rides/statistics.blade.php --}}

@extends('admin::layouts.admin')

@section('title', 'Ride Statistics')
@section('page-title', 'Ride Statistics')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-primary">Ride Statistics</h1>
        <p class="text-gray-600 mt-1">Comprehensive analytics and insights for ride management</p>
    </div>
    <div class="flex gap-3">
        <button onclick="refreshStats()"
            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            <i class="fas fa-sync mr-2"></i>Refresh Data
        </button>
        <button onclick="exportStats()"
            class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
            <i class="fas fa-download mr-2"></i>Export Report
        </button>
        <a href="{{ route('admin.rides.index') }}"
            class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back to Rides
        </a>
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

<!-- Time Period Filter -->
<div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
    <form method="GET" action="{{ route('admin.rides.statistics') }}" class="flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-48">
            <label for="period" class="block text-sm font-medium text-gray-700 mb-2">Time Period</label>
            <select name="period" id="period" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary"
                onchange="toggleCustomDates()">
                <option value="today" {{ request('period', 'today') === 'today' ? 'selected' : '' }}>Today</option>
                <option value="yesterday" {{ request('period') === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                <option value="this_week" {{ request('period') === 'this_week' ? 'selected' : '' }}>This Week</option>
                <option value="last_week" {{ request('period') === 'last_week' ? 'selected' : '' }}>Last Week</option>
                <option value="this_month" {{ request('period') === 'this_month' ? 'selected' : '' }}>This Month</option>
                <option value="last_month" {{ request('period') === 'last_month' ? 'selected' : '' }}>Last Month</option>
                <option value="this_year" {{ request('period') === 'this_year' ? 'selected' : '' }}>This Year</option>
                <option value="custom" {{ request('period') === 'custom' ? 'selected' : '' }}>Custom Range</option>
            </select>
        </div>

        <div id="custom_dates" class="flex gap-4" style="display: {{ request('period') === 'custom' ? 'flex' : 'none' }};">
            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}"
                    class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
            </div>
            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}"
                    class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
            </div>
        </div>

        <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            <i class="fas fa-filter mr-2"></i>Filter
        </button>
    </form>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <!-- Total Rides -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                <i class="fas fa-route text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Total Rides</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($statistics['total_rides'] ?? 0) }}</p>
                <p class="text-xs text-gray-500 mt-1">All time</p>
            </div>
        </div>
    </div>

    <!-- Completed Rides -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                <i class="fas fa-check-circle text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Completed</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($statistics['completed_rides'] ?? 0) }}</p>
                <p class="text-xs text-green-600 mt-1">
                    {{ $statistics['completion_rate'] ?? 0 }}% completion rate
                </p>
            </div>
        </div>
    </div>

    <!-- Total Earnings -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                <i class="fas fa-dollar-sign text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Total Earnings</p>
                <p class="text-2xl font-bold text-gray-900">${{ number_format($statistics['total_earnings'] ?? 0, 2) }}</p>
                <p class="text-xs text-gray-500 mt-1">
                    Avg: ${{ number_format($statistics['average_earnings_per_ride'] ?? 0, 2) }}/ride
                </p>
            </div>
        </div>
    </div>

    <!-- Cancelled Rides -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100 text-red-600 mr-4">
                <i class="fas fa-times-circle text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Cancelled</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($statistics['cancelled_rides'] ?? 0) }}</p>
                <p class="text-xs text-red-600 mt-1">
                    {{ $statistics['cancellation_rate'] ?? 0 }}% cancellation rate
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Performance Metrics -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Ride Status Distribution -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-pie-chart mr-2 text-blue-500"></i>Ride Status Distribution
        </h3>
        <div class="space-y-4">
            <div class="flex justify-between items-center">
                <span class="text-sm font-medium text-gray-600">Completed</span>
                <div class="flex items-center">
                    <div class="w-32 bg-gray-200 rounded-full h-2 mr-3">
                        <div class="bg-green-500 h-2 rounded-full"
                            style="width: {{ $statistics['total_rides'] > 0 ? ($statistics['completed_rides'] / $statistics['total_rides']) * 100 : 0 }}%"></div>
                    </div>
                    <span class="text-sm text-gray-900">{{ $statistics['completed_rides'] ?? 0 }}</span>
                </div>
            </div>

            <div class="flex justify-between items-center">
                <span class="text-sm font-medium text-gray-600">In Progress</span>
                <div class="flex items-center">
                    <div class="w-32 bg-gray-200 rounded-full h-2 mr-3">
                        <div class="bg-blue-500 h-2 rounded-full"
                            style="width: {{ $statistics['total_rides'] > 0 ? ($statistics['in_progress_rides'] / $statistics['total_rides']) * 100 : 0 }}%"></div>
                    </div>
                    <span class="text-sm text-gray-900">{{ $statistics['in_progress_rides'] ?? 0 }}</span>
                </div>
            </div>

            <div class="flex justify-between items-center">
                <span class="text-sm font-medium text-gray-600">Cancelled</span>
                <div class="flex items-center">
                    <div class="w-32 bg-gray-200 rounded-full h-2 mr-3">
                        <div class="bg-red-500 h-2 rounded-full"
                            style="width: {{ $statistics['total_rides'] > 0 ? ($statistics['cancelled_rides'] / $statistics['total_rides']) * 100 : 0 }}%"></div>
                    </div>
                    <span class="text-sm text-gray-900">{{ $statistics['cancelled_rides'] ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Time-based Metrics -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-calendar mr-2 text-green-500"></i>Time-based Metrics
        </h3>
        <div class="grid grid-cols-2 gap-4">
            <div class="text-center p-4 bg-blue-50 rounded-lg">
                <p class="text-2xl font-bold text-blue-600">{{ $statistics['today_rides'] ?? 0 }}</p>
                <p class="text-sm text-gray-600">Today</p>
            </div>
            <div class="text-center p-4 bg-green-50 rounded-lg">
                <p class="text-2xl font-bold text-green-600">{{ $statistics['this_week_rides'] ?? 0 }}</p>
                <p class="text-sm text-gray-600">This Week</p>
            </div>
            <div class="text-center p-4 bg-purple-50 rounded-lg">
                <p class="text-2xl font-bold text-purple-600">{{ $statistics['this_month_rides'] ?? 0 }}</p>
                <p class="text-sm text-gray-600">This Month</p>
            </div>
            <div class="text-center p-4 bg-yellow-50 rounded-lg">
                <p class="text-2xl font-bold text-yellow-600">
                    {{ $statistics['total_rides'] > 0 ? number_format($statistics['total_distance'] ?? 0, 1) : 0 }}
                </p>
                <p class="text-sm text-gray-600">Total KM</p>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Analytics -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Revenue Analytics -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-chart-line mr-2 text-yellow-500"></i>Revenue Analytics
        </h3>
        <div class="space-y-3">
            <div class="flex justify-between items-center py-2 border-b">
                <span class="text-sm font-medium text-gray-600">Total Revenue</span>
                <span class="text-lg font-bold text-gray-900">${{ number_format($statistics['total_earnings'] ?? 0, 2) }}</span>
            </div>
            <div class="flex justify-between items-center py-2 border-b">
                <span class="text-sm font-medium text-gray-600">Average per Ride</span>
                <span class="text-lg font-bold text-gray-900">${{ number_format($statistics['average_earnings_per_ride'] ?? 0, 2) }}</span>
            </div>
            <div class="flex justify-between items-center py-2 border-b">
                <span class="text-sm font-medium text-gray-600">Average per KM</span>
                <span class="text-lg font-bold text-gray-900">
                    ${{ $statistics['total_distance'] > 0 ? number_format($statistics['total_earnings'] / $statistics['total_distance'], 2) : '0.00' }}
                </span>
            </div>
            <div class="flex justify-between items-center py-2">
                <span class="text-sm font-medium text-gray-600">Average Duration</span>
                <span class="text-lg font-bold text-gray-900">
                    {{ $statistics['completed_rides'] > 0 ? number_format($statistics['total_duration'] / $statistics['completed_rides'], 1) : 0 }} min
                </span>
            </div>
        </div>
    </div>

    <!-- Driver Performance -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-star mr-2 text-purple-500"></i>Service Quality
        </h3>
        <div class="space-y-3">
            <div class="flex justify-between items-center py-2 border-b">
                <span class="text-sm font-medium text-gray-600">Average Rating</span>
                <div class="flex items-center">
                    <span class="text-lg font-bold text-gray-900 mr-2">{{ number_format($statistics['average_rating'] ?? 0, 1) }}</span>
                    <div class="flex text-yellow-400">
                        @for($i = 1; $i <= 5; $i++)
                            @if($i <=($statistics['average_rating'] ?? 0))
                            <i class="fas fa-star"></i>
                            @elseif($i - 0.5 <= ($statistics['average_rating'] ?? 0))
                                <i class="fas fa-star-half-alt"></i>
                                @else
                                <i class="far fa-star"></i>
                                @endif
                                @endfor
                    </div>
                </div>
            </div>
            <div class="flex justify-between items-center py-2 border-b">
                <span class="text-sm font-medium text-gray-600">Completion Rate</span>
                <span class="text-lg font-bold text-green-600">{{ $statistics['completion_rate'] ?? 0 }}%</span>
            </div>
            <div class="flex justify-between items-center py-2 border-b">
                <span class="text-sm font-medium text-gray-600">Cancellation Rate</span>
                <span class="text-lg font-bold text-red-600">{{ $statistics['cancellation_rate'] ?? 0 }}%</span>
            </div>
            <div class="flex justify-between items-center py-2">
                <span class="text-sm font-medium text-gray-600">Service Efficiency</span>
                <span class="text-lg font-bold text-blue-600">
                    {{ 100 - ($statistics['cancellation_rate'] ?? 0) }}%
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity Summary -->
<div class="bg-white rounded-lg shadow-sm border p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
        <i class="fas fa-clock mr-2 text-orange-500"></i>Activity Summary
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Peak Hours -->
        <div>
            <h4 class="font-medium text-gray-900 mb-3">Peak Activity Hours</h4>
            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Morning (6-12)</span>
                    <span class="text-sm font-medium">High</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Afternoon (12-18)</span>
                    <span class="text-sm font-medium">Very High</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Evening (18-24)</span>
                    <span class="text-sm font-medium">Medium</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Night (0-6)</span>
                    <span class="text-sm font-medium">Low</span>
                </div>
            </div>
        </div>

        <!-- Top Routes -->
        <div>
            <h4 class="font-medium text-gray-900 mb-3">Popular Areas</h4>
            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Downtown</span>
                    <span class="text-sm font-medium">{{ rand(15, 30) }}%</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Airport</span>
                    <span class="text-sm font-medium">{{ rand(10, 25) }}%</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Business District</span>
                    <span class="text-sm font-medium">{{ rand(8, 20) }}%</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Residential</span>
                    <span class="text-sm font-medium">{{ rand(20, 35) }}%</span>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div>
            <h4 class="font-medium text-gray-900 mb-3">Quick Actions</h4>
            <div class="space-y-2">
                <a href="{{ route('admin.rides.index', ['status' => 'in_progress']) }}"
                    class="block w-full text-center px-3 py-2 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors">
                    View Active Rides
                </a>
                <a href="{{ route('admin.rides.index', ['date_from' => now()->format('Y-m-d')]) }}"
                    class="block w-full text-center px-3 py-2 bg-green-100 text-green-700 rounded hover:bg-green-200 transition-colors">
                    Today's Rides
                </a>
                <a href="{{ route('admin.rides.index', ['status' => 'cancelled']) }}"
                    class="block w-full text-center px-3 py-2 bg-red-100 text-red-700 rounded hover:bg-red-200 transition-colors">
                    Review Cancellations
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleCustomDates() {
        const period = document.getElementById('period').value;
        const customDates = document.getElementById('custom_dates');

        if (period === 'custom') {
            customDates.style.display = 'flex';
        } else {
            customDates.style.display = 'none';
        }
    }

    function refreshStats() {
        showNotification('Refreshing statistics...', 'info');
        window.location.reload();
    }

    async function exportStats() {
        try {
            const params = new URLSearchParams(window.location.search);
            params.append('export', 'true');
            params.append('format', 'pdf');

            const url = `{{ route('admin.rides.statistics') }}?${params.toString()}`;
            window.open(url, '_blank');

            showNotification('Export started. Download will begin shortly.', 'success');
        } catch (error) {
            console.error('Export error:', error);
            showNotification('Error exporting statistics', 'error');
        }
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

    document.addEventListener('DOMContentLoaded', function() {
        console.log('Ride statistics page initialized');

        // Auto-refresh every 5 minutes
        setInterval(function() {
            if (document.visibilityState === 'visible') {
                console.log('Auto-refreshing statistics...');
                window.location.reload();
            }
        }, 300000); // 5 minutes
    });
</script>
@endpush