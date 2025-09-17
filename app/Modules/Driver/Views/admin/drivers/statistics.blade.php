@extends('admin::layouts.admin')

@section('title', 'Driver Statistics')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Driver Statistics Dashboard</h1>
            <p class="mt-1 text-gray-600">Comprehensive analytics and insights for driver management</p>
        </div>
        <div class="flex items-center space-x-3 mt-4 lg:mt-0">
            <button onclick="refreshStatistics()" class="inline-flex items-center px-4 py-2 border border-primary text-primary bg-white hover:bg-primary hover:text-white rounded-lg transition-colors duration-200">
                <i class="fas fa-sync-alt mr-2"></i>
                Refresh
            </button>
            <div class="relative">
                <button id="exportDropdown" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors duration-200">
                    <i class="fas fa-download mr-2"></i>
                    Export
                    <i class="fas fa-chevron-down ml-2"></i>
                </button>
                <div id="exportMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-10">
                    <a href="{{ route('admin.drivers.export', ['format' => 'csv']) }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-50 rounded-t-lg">Export as CSV</a>
                    <a href="{{ route('admin.drivers.export', ['format' => 'excel']) }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-50">Export as Excel</a>
                    <hr class="border-gray-200">
                    <button onclick="printStatistics()" class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-50 rounded-b-lg">Print Report</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Drivers Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-lg transition-shadow duration-200">
            <div class="flex items-center">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">Total Drivers</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($statistics['total_drivers'] ?? 0) }}</p>
                </div>
                <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-primary text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Active Drivers Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-lg transition-shadow duration-200">
            <div class="flex items-center">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">Active Drivers</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($statistics['active_drivers'] ?? 0) }}</p>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ $statistics['total_drivers'] > 0 ? round(($statistics['active_drivers'] ?? 0) / $statistics['total_drivers'] * 100, 1) : 0 }}% of total
                    </p>
                </div>
                <div class="w-12 h-12 bg-success/10 rounded-lg flex items-center justify-center">
                    <i class="fas fa-user-check text-success text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Verified Drivers Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-lg transition-shadow duration-200">
            <div class="flex items-center">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">Verified Drivers</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($statistics['verified_drivers'] ?? 0) }}</p>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ $statistics['total_drivers'] > 0 ? round(($statistics['verified_drivers'] ?? 0) / $statistics['total_drivers'] * 100, 1) : 0 }}% verified
                    </p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-shield-alt text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Pending Verification Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-lg transition-shadow duration-200">
            <div class="flex items-center">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">Pending Verification</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($statistics['pending_verification'] ?? 0) }}</p>
                    <p class="text-sm text-gray-500 mt-1">Require attention</p>
                </div>
                <div class="w-12 h-12 bg-warning/10 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-warning text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Driver Status Distribution -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900">Driver Status Distribution</h3>
                <button onclick="exportChart('statusChart')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-download"></i>
                </button>
            </div>
            <div class="relative h-64">
                <canvas id="statusChart" class="w-full h-full"></canvas>
            </div>
            <div class="flex justify-center space-x-6 mt-4">
                <div class="flex items-center text-sm">
                    <div class="w-3 h-3 bg-success rounded-full mr-2"></div>
                    <span class="text-gray-600">Active</span>
                </div>
                <div class="flex items-center text-sm">
                    <div class="w-3 h-3 bg-gray-400 rounded-full mr-2"></div>
                    <span class="text-gray-600">Inactive</span>
                </div>
                <div class="flex items-center text-sm">
                    <div class="w-3 h-3 bg-warning rounded-full mr-2"></div>
                    <span class="text-gray-600">Suspended</span>
                </div>
                <div class="flex items-center text-sm">
                    <div class="w-3 h-3 bg-blue-500 rounded-full mr-2"></div>
                    <span class="text-gray-600">Pending</span>
                </div>
            </div>
        </div>

        <!-- Registration Trends -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900">Driver Registrations (Last 12 Months)</h3>
                <button onclick="exportChart('registrationChart')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-download"></i>
                </button>
            </div>
            <div class="relative h-64">
                <canvas id="registrationChart" class="w-full h-full"></canvas>
            </div>
        </div>
    </div>

    <!-- Main Content Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Performance Metrics -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Performance Metrics</h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-3 text-sm font-medium text-gray-500 uppercase tracking-wide">Metric</th>
                            <th class="text-right py-3 text-sm font-medium text-gray-500 uppercase tracking-wide">Current Month</th>
                            <th class="text-right py-3 text-sm font-medium text-gray-500 uppercase tracking-wide">Previous Month</th>
                            <th class="text-right py-3 text-sm font-medium text-gray-500 uppercase tracking-wide">Change</th>
                            <th class="text-right py-3 text-sm font-medium text-gray-500 uppercase tracking-wide">Trend</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr class="hover:bg-gray-50">
                            <td class="py-4 text-sm font-medium text-gray-900">Total Rides Completed</td>
                            <td class="py-4 text-sm text-gray-900 text-right">{{ number_format($systemAnalytics['current_month_rides'] ?? 0) }}</td>
                            <td class="py-4 text-sm text-gray-900 text-right">{{ number_format($systemAnalytics['previous_month_rides'] ?? 0) }}</td>
                            <td class="py-4 text-sm text-right">
                                <span class="{{ ($systemAnalytics['rides_change'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ ($systemAnalytics['rides_change'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($systemAnalytics['rides_change'] ?? 0, 1) }}%
                                </span>
                            </td>
                            <td class="py-4 text-right">
                                <i class="fas fa-arrow-{{ ($systemAnalytics['rides_change'] ?? 0) >= 0 ? 'up text-success' : 'down text-danger' }}"></i>
                            </td>
                        </tr>
                        <tr class="hover:bg-gray-50">
                            <td class="py-4 text-sm font-medium text-gray-900">Average Rating</td>
                            <td class="py-4 text-sm text-gray-900 text-right">{{ number_format($systemAnalytics['current_avg_rating'] ?? 0, 2) }}</td>
                            <td class="py-4 text-sm text-gray-900 text-right">{{ number_format($systemAnalytics['previous_avg_rating'] ?? 0, 2) }}</td>
                            <td class="py-4 text-sm text-right">
                                <span class="{{ ($systemAnalytics['rating_change'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ ($systemAnalytics['rating_change'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($systemAnalytics['rating_change'] ?? 0, 2) }}
                                </span>
                            </td>
                            <td class="py-4 text-right">
                                <i class="fas fa-arrow-{{ ($systemAnalytics['rating_change'] ?? 0) >= 0 ? 'up text-success' : 'down text-danger' }}"></i>
                            </td>
                        </tr>
                        <tr class="hover:bg-gray-50">
                            <td class="py-4 text-sm font-medium text-gray-900">New Registrations</td>
                            <td class="py-4 text-sm text-gray-900 text-right">{{ number_format($systemAnalytics['current_month_registrations'] ?? 0) }}</td>
                            <td class="py-4 text-sm text-gray-900 text-right">{{ number_format($systemAnalytics['previous_month_registrations'] ?? 0) }}</td>
                            <td class="py-4 text-sm text-right">
                                <span class="{{ ($systemAnalytics['registration_change'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ ($systemAnalytics['registration_change'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($systemAnalytics['registration_change'] ?? 0, 1) }}%
                                </span>
                            </td>
                            <td class="py-4 text-right">
                                <i class="fas fa-arrow-{{ ($systemAnalytics['registration_change'] ?? 0) >= 0 ? 'up text-success' : 'down text-danger' }}"></i>
                            </td>
                        </tr>
                        <tr class="hover:bg-gray-50">
                            <td class="py-4 text-sm font-medium text-gray-900">Active Driver Ratio</td>
                            <td class="py-4 text-sm text-gray-900 text-right">{{ number_format($systemAnalytics['current_active_ratio'] ?? 0, 1) }}%</td>
                            <td class="py-4 text-sm text-gray-900 text-right">{{ number_format($systemAnalytics['previous_active_ratio'] ?? 0, 1) }}%</td>
                            <td class="py-4 text-sm text-right">
                                <span class="{{ ($systemAnalytics['active_ratio_change'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ ($systemAnalytics['active_ratio_change'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($systemAnalytics['active_ratio_change'] ?? 0, 1) }}%
                                </span>
                            </td>
                            <td class="py-4 text-right">
                                <i class="fas fa-arrow-{{ ($systemAnalytics['active_ratio_change'] ?? 0) >= 0 ? 'up text-success' : 'down text-danger' }}"></i>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions & Top Performers -->
        <div class="space-y-6">
            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                <div class="space-y-3">
                    <a href="{{ route('admin.drivers.index', ['verification_status' => 'pending']) }}" 
                       class="flex items-center justify-between w-full p-3 text-left bg-warning/10 hover:bg-warning/20 rounded-lg transition-colors duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-clock text-warning mr-3"></i>
                            <span class="text-sm font-medium text-gray-900">Pending Verifications</span>
                        </div>
                        <span class="text-sm font-bold text-warning">{{ $statistics['pending_verification'] ?? 0 }}</span>
                    </a>
                    
                    <a href="{{ route('admin.drivers.index', ['status' => 'inactive']) }}" 
                       class="flex items-center justify-between w-full p-3 text-left bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-user-times text-gray-600 mr-3"></i>
                            <span class="text-sm font-medium text-gray-900">Inactive Drivers</span>
                        </div>
                        <span class="text-sm font-bold text-gray-600">{{ $statistics['inactive_drivers'] ?? 0 }}</span>
                    </a>
                    
                    <a href="{{ route('admin.drivers.index', ['status' => 'suspended']) }}" 
                       class="flex items-center justify-between w-full p-3 text-left bg-danger/10 hover:bg-danger/20 rounded-lg transition-colors duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-ban text-danger mr-3"></i>
                            <span class="text-sm font-medium text-gray-900">Suspended Drivers</span>
                        </div>
                        <span class="text-sm font-bold text-danger">{{ $statistics['suspended_drivers'] ?? 0 }}</span>
                    </a>
                    
                    <hr class="border-gray-200">
                    
                    <a href="{{ route('admin.drivers.create') }}" 
                       class="flex items-center w-full p-3 text-left bg-primary hover:bg-primary/90 text-white rounded-lg transition-colors duration-200">
                        <i class="fas fa-plus mr-3"></i>
                        <span class="text-sm font-medium">Add New Driver</span>
                    </a>
                    
                    <a href="{{ route('admin.drivers.export', ['format' => 'csv']) }}" 
                       class="flex items-center w-full p-3 text-left bg-success hover:bg-success/90 text-white rounded-lg transition-colors duration-200">
                        <i class="fas fa-download mr-3"></i>
                        <span class="text-sm font-medium">Export All Drivers</span>
                    </a>
                </div>
            </div>

            <!-- Top Performers -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Performers This Month</h3>
                @if(isset($systemAnalytics['top_performers']) && count($systemAnalytics['top_performers']) > 0)
                    <div class="space-y-4">
                        @foreach($systemAnalytics['top_performers'] as $performer)
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center text-white font-semibold">
                                    {{ substr($performer['name'] ?? 'N', 0, 1) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $performer['name'] ?? 'Unknown' }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ $performer['rides'] ?? 0 }} rides • {{ number_format($performer['rating'] ?? 0, 1) }} ⭐
                                    </p>
                                </div>
                                <a href="{{ route('admin.drivers.show', $performer['firebase_uid'] ?? '#') }}" 
                                   class="text-xs bg-primary/10 text-primary px-3 py-1 rounded-full hover:bg-primary/20 transition-colors duration-200">
                                    View
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <i class="fas fa-chart-line text-4xl text-gray-300 mb-3"></i>
                        <p class="text-gray-500">No performance data available</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Regional Statistics -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">Regional Driver Distribution</h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 text-sm font-medium text-gray-500 uppercase tracking-wide">Region/City</th>
                        <th class="text-right py-3 text-sm font-medium text-gray-500 uppercase tracking-wide">Total Drivers</th>
                        <th class="text-right py-3 text-sm font-medium text-gray-500 uppercase tracking-wide">Active</th>
                        <th class="text-right py-3 text-sm font-medium text-gray-500 uppercase tracking-wide">Verified</th>
                        <th class="text-right py-3 text-sm font-medium text-gray-500 uppercase tracking-wide">Completion Rate</th>
                        <th class="text-right py-3 text-sm font-medium text-gray-500 uppercase tracking-wide">Avg Rating</th>
                        <th class="text-right py-3 text-sm font-medium text-gray-500 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @if(isset($systemAnalytics['regional_stats']) && count($systemAnalytics['regional_stats']) > 0)
                        @foreach($systemAnalytics['regional_stats'] as $region)
                            <tr class="hover:bg-gray-50">
                                <td class="py-4 text-sm font-medium text-gray-900">{{ $region['city'] ?? 'Unknown' }}</td>
                                <td class="py-4 text-sm text-gray-900 text-right">{{ number_format($region['total_drivers'] ?? 0) }}</td>
                                <td class="py-4 text-right">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success/10 text-success">
                                        {{ $region['active_drivers'] ?? 0 }}
                                    </span>
                                    <div class="text-xs text-gray-500 mt-1">
                                        ({{ $region['total_drivers'] > 0 ? round(($region['active_drivers'] ?? 0) / $region['total_drivers'] * 100, 1) : 0 }}%)
                                    </div>
                                </td>
                                <td class="py-4 text-right">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ $region['verified_drivers'] ?? 0 }}
                                    </span>
                                </td>
                                <td class="py-4 text-right">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-primary h-2 rounded-full" style="width: {{ $region['completion_rate'] ?? 0 }}%"></div>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">{{ number_format($region['completion_rate'] ?? 0, 1) }}%</div>
                                </td>
                                <td class="py-4 text-right">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-warning/10 text-warning">
                                        {{ number_format($region['avg_rating'] ?? 0, 1) }} ⭐
                                    </span>
                                </td>
                                <td class="py-4 text-right">
                                    <a href="{{ route('admin.drivers.index', ['city' => $region['city'] ?? '']) }}" 
                                       class="text-primary hover:text-primary/80 text-sm font-medium">
                                        View Drivers
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="7" class="py-12 text-center">
                                <i class="fas fa-map-marker-alt text-4xl text-gray-300 mb-3"></i>
                                <p class="text-gray-500">No regional data available</p>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Export dropdown functionality
    const exportButton = document.getElementById('exportDropdown');
    const exportMenu = document.getElementById('exportMenu');
    
    exportButton.addEventListener('click', function() {
        exportMenu.classList.toggle('hidden');
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        if (!exportButton.contains(event.target) && !exportMenu.contains(event.target)) {
            exportMenu.classList.add('hidden');
        }
    });

    // Status Distribution Doughnut Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Active', 'Inactive', 'Suspended', 'Pending'],
            datasets: [{
                data: [
                    {{ $statistics['active_drivers'] ?? 0 }},
                    {{ $statistics['inactive_drivers'] ?? 0 }},
                    {{ $statistics['suspended_drivers'] ?? 0 }},
                    {{ $statistics['pending_drivers'] ?? 0 }}
                ],
                backgroundColor: ['#10b981', '#6b7280', '#f59e0b', '#3b82f6'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: 'white',
                    bodyColor: 'white',
                    borderColor: 'rgba(255, 255, 255, 0.2)',
                    borderWidth: 1,
                    cornerRadius: 8,
                    padding: 12
                }
            },
            cutout: '60%'
        }
    });

    // Registration Trends Line Chart
    const registrationCtx = document.getElementById('registrationChart').getContext('2d');
    const registrationChart = new Chart(registrationCtx, {
        type: 'line',
        data: {
            labels: @json($systemAnalytics['registration_months'] ?? []),
            datasets: [{
                label: 'Registrations',
                data: @json($systemAnalytics['registration_data'] ?? []),
                borderColor: '#003366',
                backgroundColor: 'rgba(0, 51, 102, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#003366',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: 'white',
                    bodyColor: 'white',
                    borderColor: 'rgba(255, 255, 255, 0.2)',
                    borderWidth: 1,
                    cornerRadius: 8,
                    padding: 12
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#6b7280'
                    }
                },
                y: {
                    grid: {
                        color: 'rgba(107, 114, 128, 0.2)'
                    },
                    ticks: {
                        color: '#6b7280'
                    }
                }
            }
        }
    });
});

function refreshStatistics() {
    location.reload();
}

function exportChart(chartId) {
    const canvas = document.getElementById(chartId);
    const url = canvas.toDataURL('image/png');
    const a = document.createElement('a');
    a.href = url;
    a.download = chartId + '_chart.png';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

function printStatistics() {
    window.print();
}
</script>
@endpush

@push('styles')
<style>
@media print {
    .no-print {
        display: none !important;
    }
}
</style>
@endpush
@endsection