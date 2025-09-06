@extends('admin::layouts.admin')

@section('title', 'Vehicle Management')
@section('page-title', 'Vehicle Management')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-primary">Vehicle Management</h1>
        <p class="text-gray-600 mt-1">Manage driver vehicles and verification (Total: {{ $totalVehicles }})</p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('admin.vehicles.create') }}" 
           class="bg-success text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors">
            <i class="fas fa-plus mr-2"></i>Add Vehicle
        </a>
        <a href="{{ route('admin.vehicles.statistics') }}" 
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
                <i class="fas fa-car text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Total Vehicles</p>
                <p class="text-2xl font-bold text-gray-900">{{ $totalVehicles }}</p>
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
                <p class="text-2xl font-bold text-gray-900">
                    {{ $vehicles->where('status', 'active')->count() }}
                </p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                <i class="fas fa-shield-check text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Verified</p>
                <p class="text-2xl font-bold text-gray-900">
                    {{ $vehicles->where('verification_status', 'verified')->count() }}
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
                <p class="text-2xl font-bold text-gray-900">
                    {{ $vehicles->where('verification_status', 'pending')->count() }}
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Filters and Search -->
<div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
    <form method="GET" action="{{ route('admin.vehicles.index') }}" class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-64">
            <input type="text" name="search" value="{{ $search ?? '' }}" 
                   placeholder="Search by make, model, license plate, or driver..."
                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
        </div>
        <div class="min-w-48">
            <select name="status" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Status</option>
                @foreach($statuses as $key => $label)
                    <option value="{{ $key }}" {{ ($status ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-48">
            <select name="verification_status" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Verification</option>
                @foreach($verificationStatuses as $key => $label)
                    <option value="{{ $key }}" {{ ($verification_status ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-48">
            <select name="vehicle_type" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Vehicle Types</option>
                @foreach($vehicleTypes as $key => $label)
                    <option value="{{ $key }}" {{ ($vehicle_type ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
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
            <a href="{{ route('admin.vehicles.index') }}" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                <i class="fas fa-times mr-2"></i>Clear
            </a>
        </div>
    </form>
</div>

<!-- Bulk Actions -->
<div class="bg-white rounded-lg shadow-sm border mb-6" id="bulkActionsBar" style="display: none;">
    <div class="p-4 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <span class="text-sm text-gray-600">
                <span id="selectedCount">0</span> vehicles selected
            </span>
            <div class="flex gap-2">
                <button onclick="bulkAction('verify')" class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">
                    <i class="fas fa-shield-check mr-1"></i>Verify
                </button>
                <button onclick="bulkAction('activate')" class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700">
                    <i class="fas fa-check mr-1"></i>Activate
                </button>
                <button onclick="bulkAction('deactivate')" class="bg-yellow-600 text-white px-3 py-1 rounded text-sm hover:bg-yellow-700">
                    <i class="fas fa-ban mr-1"></i>Deactivate
                </button>
                <button onclick="bulkAction('delete')" class="bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700">
                    <i class="fas fa-trash mr-1"></i>Delete
                </button>
            </div>
        </div>
        <button onclick="clearSelection()" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>

<!-- Vehicles Table -->
<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    <div class="p-6 border-b">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold">
                <i class="fas fa-car mr-2 text-primary"></i>Vehicles List
            </h2>
            <div class="flex gap-2">
                <button onclick="exportVehicles('csv')" class="text-primary hover:bg-blue-50 px-3 py-1 rounded">
                    <i class="fas fa-file-csv mr-1"></i>CSV Export
                </button>
            </div>
        </div>
    </div>
    
    @if(count($vehicles) > 0)
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left">
                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" 
                               class="rounded border-gray-300 text-primary focus:ring-primary">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Vehicle Info
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Driver
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Specifications
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Registration
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($vehicles as $vehicle)
                @php
                    $vehicleId = $vehicle['id'];
                    $driverName = $vehicle['driver_name'] ?? 'Unknown Driver';
                    $driverEmail = $vehicle['driver_email'] ?? 'Unknown';
                    $vehicleStatus = $vehicle['status'] ?? 'unknown';
                    $verificationStatus = $vehicle['verification_status'] ?? 'pending';
                    $isPrimary = $vehicle['is_primary'] ?? false;
                    
                    $make = $vehicle['make'] ?? 'Unknown';
                    $model = $vehicle['model'] ?? 'Unknown';
                    $year = $vehicle['year'] ?? 'Unknown';
                    $color = $vehicle['color'] ?? 'Unknown';
                    $licensePlate = $vehicle['license_plate'] ?? 'No plate';
                    $vehicleType = $vehicle['vehicle_type'] ?? 'unknown';
                    
                    $registeredAt = null;
                    $registeredDisplay = 'Unknown';
                    $registeredHuman = 'Date not available';
                    
                    if (!empty($vehicle['created_at'])) {
                        try {
                            $registeredAt = \Carbon\Carbon::parse($vehicle['created_at']);
                            $registeredDisplay = $registeredAt->format('M d, Y');
                            $registeredHuman = $registeredAt->diffForHumans();
                        } catch (\Exception $e) {
                            // Handle date parsing error
                        }
                    }
                @endphp
                <tr data-vehicle-id="{{ $vehicleId }}" class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <input type="checkbox" class="vehicle-checkbox rounded border-gray-300 text-primary focus:ring-primary" 
                               value="{{ $vehicleId }}" onchange="updateBulkActions()">
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 mr-3">
                                @switch($vehicleType)
                                    @case('sedan')
                                        <i class="fas fa-car text-blue-500 text-xl"></i>
                                        @break
                                    @case('suv')
                                        <i class="fas fa-truck text-green-500 text-xl"></i>
                                        @break
                                    @case('hatchback')
                                        <i class="fas fa-car-side text-purple-500 text-xl"></i>
                                        @break
                                    @case('motorcycle')
                                        <i class="fas fa-motorcycle text-orange-500 text-xl"></i>
                                        @break
                                    @default
                                        <i class="fas fa-car text-gray-500 text-xl"></i>
                                @endswitch
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">
                                    {{ $year }} {{ $make }} {{ $model }}
                                    @if($isPrimary)
                                        <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full ml-2">Primary</span>
                                    @endif
                                </div>
                                <div class="text-sm text-gray-500">{{ $licensePlate }}</div>
                                <div class="text-xs text-gray-400">
                                    ID: {{ substr($vehicleId, 0, 12) }}{{ strlen($vehicleId) > 12 ? '...' : '' }}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">{{ $driverName }}</div>
                        <div class="text-sm text-gray-500">{{ $driverEmail }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm space-y-1">
                            <div><span class="font-medium text-gray-700">Type:</span> {{ ucfirst($vehicleType) }}</div>
                            <div><span class="font-medium text-gray-700">Color:</span> {{ $color }}</div>
                            @if(!empty($vehicle['seats']))
                                <div><span class="font-medium text-gray-700">Seats:</span> {{ $vehicle['seats'] }}</div>
                            @endif
                            @if(!empty($vehicle['fuel_type']))
                                <div><span class="font-medium text-gray-700">Fuel:</span> {{ ucfirst($vehicle['fuel_type']) }}</div>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="space-y-2">
                            <!-- Vehicle Status -->
                            @if($vehicleStatus === 'active')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle mr-1"></i>Active
                                </span>
                            @elseif($vehicleStatus === 'maintenance')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                    <i class="fas fa-wrench mr-1"></i>Maintenance
                                </span>
                            @elseif($vehicleStatus === 'suspended')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <i class="fas fa-ban mr-1"></i>Suspended
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    <i class="fas fa-pause-circle mr-1"></i>Inactive
                                </span>
                            @endif
                            
                            <!-- Verification Status -->
                            @if($verificationStatus === 'verified')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <i class="fas fa-shield-check mr-1"></i>Verified
                                </span>
                            @elseif($verificationStatus === 'rejected')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <i class="fas fa-shield-times mr-1"></i>Rejected
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    <i class="fas fa-clock mr-1"></i>Pending
                                </span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        <div>{{ $registeredDisplay }}</div>
                        <div class="text-xs text-gray-500">{{ $registeredHuman }}</div>
                        @if(!empty($vehicle['vin']))
                            <div class="text-xs text-gray-500">VIN: {{ substr($vehicle['vin'], -4) }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.vehicles.show', $vehicleId) }}" 
                               class="text-primary hover:text-blue-700 p-1" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.vehicles.edit', $vehicleId) }}" 
                               class="text-green-600 hover:text-green-800 p-1" title="Edit Vehicle">
                                <i class="fas fa-edit"></i>
                            </a>
                            @if($verificationStatus !== 'verified')
                                <button onclick="updateVerificationStatus('{{ $vehicleId }}', 'verified')" 
                                        class="text-blue-600 hover:text-blue-800 p-1" title="Verify Vehicle">
                                    <i class="fas fa-shield-check"></i>
                                </button>
                            @endif
                            @if(!$isPrimary)
                                <button onclick="setPrimaryVehicle('{{ $vehicleId }}')" 
                                        class="text-purple-600 hover:text-purple-800 p-1" title="Set as Primary">
                                    <i class="fas fa-star"></i>
                                </button>
                            @endif
                            <button onclick="deleteVehicle('{{ $vehicleId }}', '{{ addslashes($year . ' ' . $make . ' ' . $model) }}')" 
                                    class="text-red-600 hover:text-red-800 p-1" title="Delete Vehicle">
                                <i class="fas fa-trash"></i>
                            </button>
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
                Showing {{ count($vehicles) }} of {{ $totalVehicles }} vehicles
            </div>
            <div class="text-sm text-gray-500">
                Filtered results based on current criteria
            </div>
        </div>
    </div>
    @else
    <div class="text-center py-12">
        <i class="fas fa-car text-4xl text-gray-400 mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No Vehicles Found</h3>
        <p class="text-gray-500 mb-4">
            @if(isset($search) && $search)
                No vehicles match your search criteria "{{ $search }}". Try adjusting your filters.
            @else
                No vehicles found in the system. Add some vehicles to get started.
            @endif
        </p>
        <div class="flex justify-center gap-3">
            <a href="{{ route('admin.vehicles.create') }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>Add First Vehicle
            </a>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    let selectedVehicles = new Set();

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

    async function updateVerificationStatus(vehicleId, status) {
        if (!confirm(`Are you sure you want to ${status} this vehicle?`)) {
            return;
        }
        
        try {
            const response = await fetch(`{{ route('admin.vehicles.update-verification-status', '') }}/${vehicleId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ verification_status: status })
            });
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                showNotification(`Vehicle ${status} successfully`, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showNotification(`Failed to ${status} vehicle: ` + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Update verification error:', error);
            showNotification(`Error ${status} vehicle: Connection failed`, 'error');
        }
    }

    async function setPrimaryVehicle(vehicleId) {
        if (!confirm('Are you sure you want to set this as the primary vehicle for this driver?')) {
            return;
        }
        
        try {
            const response = await fetch(`{{ route('admin.vehicles.set-primary', '') }}/${vehicleId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                showNotification('Primary vehicle set successfully', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showNotification('Failed to set primary vehicle: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Set primary error:', error);
            showNotification('Error setting primary vehicle: Connection failed', 'error');
        }
    }

    async function deleteVehicle(vehicleId, vehicleName) {
        if (!confirm(`Are you sure you want to delete vehicle "${vehicleName}"? This action cannot be undone.`)) {
            return;
        }
        
        try {
            const response = await fetch(`{{ route('admin.vehicles.destroy', '') }}/${vehicleId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                showNotification('Vehicle deleted successfully', 'success');
                const row = document.querySelector(`tr[data-vehicle-id="${vehicleId}"]`);
                if (row) row.remove();
            } else {
                showNotification('Failed to delete vehicle: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Delete error:', error);
            showNotification('Error deleting vehicle: Connection failed', 'error');
        }
    }

    function toggleSelectAll() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.vehicle-checkbox');
        
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAll.checked;
            if (selectAll.checked) {
                selectedVehicles.add(checkbox.value);
            } else {
                selectedVehicles.delete(checkbox.value);
            }
        });
        
        updateBulkActions();
    }

    function updateBulkActions() {
        const checkboxes = document.querySelectorAll('.vehicle-checkbox:checked');
        const bulkBar = document.getElementById('bulkActionsBar');
        const selectedCount = document.getElementById('selectedCount');
        
        selectedVehicles.clear();
        checkboxes.forEach(checkbox => selectedVehicles.add(checkbox.value));
        
        if (selectedVehicles.size > 0) {
            bulkBar.style.display = 'block';
            selectedCount.textContent = selectedVehicles.size;
        } else {
            bulkBar.style.display = 'none';
        }
        
        const selectAll = document.getElementById('selectAll');
        const allCheckboxes = document.querySelectorAll('.vehicle-checkbox');
        selectAll.checked = selectedVehicles.size === allCheckboxes.length && allCheckboxes.length > 0;
    }

    function clearSelection() {
        selectedVehicles.clear();
        document.querySelectorAll('.vehicle-checkbox').forEach(checkbox => checkbox.checked = false);
        document.getElementById('selectAll').checked = false;
        document.getElementById('bulkActionsBar').style.display = 'none';
    }

    async function bulkAction(action) {
        if (selectedVehicles.size === 0) return;
        
        const actionText = action === 'delete' ? 'delete' : action;
        if (!confirm(`Are you sure you want to ${actionText} ${selectedVehicles.size} selected vehicles?`)) {
            return;
        }
        
        try {
            const response = await fetch('{{ route("admin.vehicles.bulk-action") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    action: action,
                    vehicle_ids: Array.from(selectedVehicles)
                })
            });
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                showNotification(result.message || `Bulk ${actionText} completed successfully`, 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showNotification('Bulk action failed: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Bulk action error:', error);
            showNotification('Error performing bulk action: Connection failed', 'error');
        }
    }

    async function exportVehicles(format) {
        try {
            const url = new URL('{{ route("admin.vehicles.export") }}');
            url.searchParams.append('format', format);
            
            // Add current filters
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('search')) url.searchParams.append('search', urlParams.get('search'));
            if (urlParams.get('status')) url.searchParams.append('status', urlParams.get('status'));
            if (urlParams.get('verification_status')) url.searchParams.append('verification_status', urlParams.get('verification_status'));
            if (urlParams.get('vehicle_type')) url.searchParams.append('vehicle_type', urlParams.get('vehicle_type'));
            
            window.open(url.toString(), '_blank');
            showNotification('Export started. Download will begin shortly.', 'info');
            
        } catch (error) {
            console.error('Export error:', error);
            showNotification('Error exporting vehicles', 'error');
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Vehicle management page initialized');
        updateBulkActions();
    });
</script>
@endpush