@extends('admin::layouts.admin')

@section('title', 'Driver Management')
@section('page-title', 'Driver Management')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-primary">Driver Management</h1>
        <p class="text-gray-600 mt-1">Manage drivers and their information (Total: {{ $totalDrivers }})</p>
    </div>
    <div class="flex gap-3">
        <button onclick="syncFirebaseDrivers()" 
                class="bg-warning text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition-colors" 
                id="syncBtn">
            <i class="fas fa-sync mr-2"></i>Sync Firebase
        </button>
        <a href="{{ route('admin.drivers.create') }}" 
           class="bg-success text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors">
            <i class="fas fa-plus mr-2"></i>Add Driver
        </a>
        <a href="{{ route('admin.drivers.statistics') }}" 
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

<!-- Filters and Search -->
<div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
    <form method="GET" action="{{ route('admin.drivers.index') }}" class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-64">
            <input type="text" name="search" value="{{ $search ?? '' }}" 
                   placeholder="Search drivers by name, email, phone, or license..."
                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
        </div>
        <div class="min-w-48">
            <select name="status" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Status</option>
                <option value="active" {{ ($status ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ ($status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="suspended" {{ ($status ?? '') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                <option value="pending" {{ ($status ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
            </select>
        </div>
        <div class="min-w-48">
            <select name="verification_status" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Verification</option>
                <option value="verified" {{ ($verification_status ?? '') === 'verified' ? 'selected' : '' }}>Verified</option>
                <option value="pending" {{ ($verification_status ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="rejected" {{ ($verification_status ?? '') === 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
        </div>
        <div class="min-w-48">
            <select name="availability_status" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Availability</option>
                <option value="available" {{ ($availability_status ?? '') === 'available' ? 'selected' : '' }}>Available</option>
                <option value="busy" {{ ($availability_status ?? '') === 'busy' ? 'selected' : '' }}>Busy</option>
                <option value="offline" {{ ($availability_status ?? '') === 'offline' ? 'selected' : '' }}>Offline</option>
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
            <a href="{{ route('admin.drivers.index') }}" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors">
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
                <span id="selectedCount">0</span> drivers selected
            </span>
            <div class="flex gap-2">
                <button onclick="bulkAction('activate')" class="bg-success text-white px-3 py-1 rounded text-sm hover:bg-green-600">
                    <i class="fas fa-check mr-1"></i>Activate
                </button>
                <button onclick="bulkAction('deactivate')" class="bg-warning text-white px-3 py-1 rounded text-sm hover:bg-yellow-600">
                    <i class="fas fa-ban mr-1"></i>Deactivate
                </button>
                <button onclick="bulkAction('suspend')" class="bg-orange-500 text-white px-3 py-1 rounded text-sm hover:bg-orange-600">
                    <i class="fas fa-pause mr-1"></i>Suspend
                </button>
                <button onclick="bulkAction('verify')" class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600">
                    <i class="fas fa-shield-check mr-1"></i>Verify
                </button>
                <button onclick="bulkAction('delete')" class="bg-danger text-white px-3 py-1 rounded text-sm hover:bg-red-600">
                    <i class="fas fa-trash mr-1"></i>Delete
                </button>
            </div>
        </div>
        <button onclick="clearSelection()" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>

<!-- Drivers Table -->
<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    <div class="p-6 border-b">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold">
                <i class="fas fa-car mr-2 text-primary"></i>Drivers List
            </h2>
            <div class="flex gap-2">
                <button onclick="exportDrivers('csv')" class="text-primary hover:bg-blue-50 px-3 py-1 rounded">
                    <i class="fas fa-file-csv mr-1"></i>CSV Export
                </button>
            </div>
        </div>
    </div>
    
    @if(count($drivers) > 0)
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left">
                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" 
                               class="rounded border-gray-300 text-primary focus:ring-primary">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Driver Info
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Contact & License
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Performance
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Location
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Joined
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              @foreach($drivers as $driver)
@php
    // Handle both array and object access patterns
    $driverId = is_array($driver) ? $driver['firebase_uid'] : $driver->firebase_uid;
    $driverName = is_array($driver) ? ($driver['name'] ?? 'Unknown Driver') : ($driver->name ?? 'Unknown Driver');
    $driverEmail = is_array($driver) ? ($driver['email'] ?? '') : ($driver->email ?? '');
    $driverPhone = is_array($driver) ? ($driver['phone'] ?? '') : ($driver->phone ?? '');
    $driverStatus = is_array($driver) ? ($driver['status'] ?? 'pending') : ($driver->status ?? 'pending');
    $verificationStatus = is_array($driver) ? ($driver['verification_status'] ?? 'pending') : ($driver->verification_status ?? 'pending');
    $availabilityStatus = is_array($driver) ? ($driver['availability_status'] ?? 'offline') : ($driver->availability_status ?? 'offline');
    $rating = is_array($driver) ? ($driver['rating'] ?? 0) : ($driver->rating ?? 0);
    $totalRides = is_array($driver) ? ($driver['total_rides'] ?? 0) : ($driver->total_rides ?? 0);
    $totalEarnings = is_array($driver) ? ($driver['total_earnings'] ?? 0) : ($driver->total_earnings ?? 0);
    $city = is_array($driver) ? ($driver['city'] ?? '') : ($driver->city ?? '');
    $state = is_array($driver) ? ($driver['state'] ?? '') : ($driver->state ?? '');
    $photoUrl = is_array($driver) ? ($driver['photo_url'] ?? '') : ($driver->photo_url ?? '');
    $licenseNumber = is_array($driver) ? ($driver['license_number'] ?? '') : ($driver->license_number ?? '');
    
    $joinedAt = null;
    $joinedDisplay = 'Unknown';
    $joinedHuman = 'Date not available';
    
    // Handle join date - try multiple possible field names
    $joinDate = null;
    if (is_array($driver)) {
        $joinDate = $driver['join_date'] ?? $driver['created_at'] ?? null;
    } else {
        $joinDate = $driver->join_date ?? $driver->created_at ?? null;
    }
    
    if (!empty($joinDate)) {
        try {
            $joinedAt = \Carbon\Carbon::parse($joinDate);
            $joinedDisplay = $joinedAt->format('M d, Y');
            $joinedHuman = $joinedAt->diffForHumans();
        } catch (\Exception $e) {
            Log::debug('Date parsing error for driver', [
                'driver_id' => $driverId,
                'join_date' => $joinDate,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    $displayName = ($driverName !== 'Unknown Driver') ? $driverName : $driverEmail;
@endphp
<tr data-driver-id="{{ $driverId }}" class="hover:bg-gray-50">
    <td class="px-6 py-4">
        <input type="checkbox" class="driver-checkbox rounded border-gray-300 text-primary focus:ring-primary" 
               value="{{ $driverId }}" onchange="updateBulkActions()">
    </td>
    <td class="px-6 py-4">
        <div class="flex items-center">
            @if(!empty($photoUrl))
                <img src="{{ $photoUrl }}" alt="{{ $driverName }}" 
                     class="w-10 h-10 rounded-full mr-3">
            @else
                <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center text-white font-medium mr-3">
                    {{ strtoupper(substr($driverName, 0, 1)) }}
                </div>
            @endif
            <div>
                <div class="font-medium text-gray-900">{{ $driverName }}</div>
                <div class="text-sm text-gray-500">{{ $driverEmail }}</div>
                <div class="text-xs text-gray-400">
                    ID: {{ substr($driverId, 0, 12) }}{{ strlen($driverId) > 12 ? '...' : '' }}
                </div>
            </div>
        </div>
    </td>
    <td class="px-6 py-4">
        <div class="text-sm text-gray-900">{{ $driverEmail }}</div>
        @if($driverPhone)
            <div class="text-sm text-gray-500">{{ $driverPhone }}</div>
        @else
            <div class="text-sm text-gray-400">No phone</div>
        @endif
        @if(!empty($licenseNumber))
            <div class="text-xs text-gray-500">License: {{ $licenseNumber }}</div>
        @endif
    </td>
    <td class="px-6 py-4">
        <div class="flex flex-col gap-1">
            <!-- Driver Status -->
            @if($driverStatus === 'active')
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <i class="fas fa-check-circle mr-1"></i>Active
                </span>
            @elseif($driverStatus === 'suspended')
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                    <i class="fas fa-pause-circle mr-1"></i>Suspended
                </span>
            @elseif($driverStatus === 'pending')
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                    <i class="fas fa-clock mr-1"></i>Pending
                </span>
            @else
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                    <i class="fas fa-times-circle mr-1"></i>Inactive
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
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                    <i class="fas fa-shield-question mr-1"></i>Pending
                </span>
            @endif
            
            <!-- Availability Status -->
            @if($availabilityStatus === 'available')
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <i class="fas fa-circle mr-1"></i>Available
                </span>
            @elseif($availabilityStatus === 'busy')
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                    <i class="fas fa-circle mr-1"></i>Busy
                </span>
            @else
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                    <i class="fas fa-circle mr-1"></i>Offline
                </span>
            @endif
        </div>
    </td>
    <td class="px-6 py-4 text-sm">
        <div class="text-gray-900">
            <i class="fas fa-star text-yellow-400 mr-1"></i>{{ number_format($rating, 1) }}
        </div>
        <div class="text-gray-500">{{ $totalRides }} rides</div>
        <div class="text-gray-500">${{ number_format($totalEarnings, 2) }}</div>
    </td>
    <td class="px-6 py-4 text-sm">
        @if($city || $state)
            <div class="text-gray-900">{{ $city }}</div>
            @if($state)
                <div class="text-gray-500">{{ $state }}</div>
            @endif
        @else
            <div class="text-gray-400">No location</div>
        @endif
    </td>
    <td class="px-6 py-4 text-sm text-gray-900">
        <div>{{ $joinedDisplay }}</div>
        <div class="text-xs text-gray-500">{{ $joinedHuman }}</div>
    </td>
    <td class="px-6 py-4">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.drivers.show', $driverId) }}" 
               class="text-primary hover:text-blue-700 p-1" title="View Details">
                <i class="fas fa-eye"></i>
            </a>
            <a href="{{ route('admin.drivers.edit', $driverId) }}" 
               class="text-green-600 hover:text-green-800 p-1" title="Edit Driver">
                <i class="fas fa-edit"></i>
            </a>
            @if($driverStatus === 'active')
                <button onclick="toggleDriverStatus('{{ $driverId }}', 'deactivate')" 
                        class="text-yellow-600 hover:text-yellow-800 p-1" title="Deactivate Driver">
                    <i class="fas fa-ban"></i>
                </button>
            @else
                <button onclick="toggleDriverStatus('{{ $driverId }}', 'activate')" 
                        class="text-green-600 hover:text-green-800 p-1" title="Activate Driver">
                    <i class="fas fa-check-circle"></i>
                </button>
            @endif
            @if($verificationStatus !== 'verified')
                <button onclick="toggleDriverStatus('{{ $driverId }}', 'verify')" 
                        class="text-blue-600 hover:text-blue-800 p-1" title="Verify Driver">
                    <i class="fas fa-shield-check"></i>
                </button>
            @endif
            <button onclick="deleteDriver('{{ $driverId }}', '{{ addslashes($displayName) }}')" 
                    class="text-red-600 hover:text-red-800 p-1" title="Delete Driver">
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
                Showing {{ count($drivers) }} of {{ $totalDrivers }} drivers
            </div>
            <div class="text-sm text-gray-500">
                Filtered results based on current criteria
            </div>
        </div>
    </div>
    @else
    <div class="text-center py-12">
        <i class="fas fa-car text-4xl text-gray-400 mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No Drivers Found</h3>
        <p class="text-gray-500 mb-4">
            @if(isset($search) && $search)
                No drivers match your search criteria "{{ $search }}". Try adjusting your filters.
            @else
                No drivers found in the system. Add some drivers to get started.
            @endif
        </p>
        <div class="flex justify-center gap-3">
            <a href="{{ route('admin.drivers.create') }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>Add First Driver
            </a>
            <button onclick="syncFirebaseDrivers()" class="bg-warning text-white px-4 py-2 rounded-lg hover:bg-yellow-600">
                <i class="fas fa-sync mr-2"></i>Sync Firebase Drivers
            </button>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    let selectedDrivers = new Set();

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
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
    }

    function hideLoading(button, originalText) {
        button.disabled = false;
        button.innerHTML = originalText;
    }

    async function syncFirebaseDrivers() {
        if (!confirm('This will sync drivers from Firebase. This may take some time. Continue?')) {
            return;
        }
        
        const btn = document.getElementById('syncBtn');
        const originalText = btn.innerHTML;
        showLoading(btn);
        
        try {
            const response = await fetch('{{ route("admin.drivers.sync-firebase") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                showNotification('Drivers synced successfully! Refreshing page...', 'success');
                setTimeout(() => window.location.reload(), 2000);
            } else {
                showNotification('Failed to sync drivers: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Sync error:', error);
            showNotification('Error syncing drivers: Connection failed', 'error');
        } finally {
            hideLoading(btn, originalText);
        }
    }

    async function deleteDriver(driverId, driverName) {
        if (!confirm(`Are you sure you want to delete driver "${driverName}"? This action cannot be undone.`)) {
            return;
        }
        
        try {
            const response = await fetch('{{ route("admin.drivers.destroy", ["firebaseUid" => ":id"]) }}'.replace(':id', driverId), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                showNotification('Driver deleted successfully', 'success');
                const row = document.querySelector(`tr[data-driver-id="${driverId}"]`);
                if (row) row.remove();
            } else {
                showNotification('Failed to delete driver: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Delete error:', error);
            showNotification('Error deleting driver: Connection failed', 'error');
        }
    }

    async function toggleDriverStatus(driverId, action) {
        const actionText = action === 'activate' ? 'activate' : action === 'verify' ? 'verify' : 'deactivate';
        if (!confirm(`Are you sure you want to ${actionText} this driver?`)) {
            return;
        }
        
        try {
            const response = await fetch('{{ route("admin.drivers.update-status", ["firebaseUid" => ":id"]) }}'.replace(':id', driverId), {
                method: 'PATCH', // Changed from POST to PATCH
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: action }) // Send status as per controller expectation
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

    function toggleSelectAll() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.driver-checkbox');
        
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAll.checked;
            if (selectAll.checked) {
                selectedDrivers.add(checkbox.value);
            } else {
                selectedDrivers.delete(checkbox.value);
            }
        });
        
        updateBulkActions();
    }

    function updateBulkActions() {
        const checkboxes = document.querySelectorAll('.driver-checkbox:checked');
        const bulkBar = document.getElementById('bulkActionsBar');
        const selectedCount = document.getElementById('selectedCount');
        
        selectedDrivers.clear();
        checkboxes.forEach(checkbox => selectedDrivers.add(checkbox.value));
        
        if (selectedDrivers.size > 0) {
            bulkBar.style.display = 'block';
            selectedCount.textContent = selectedDrivers.size;
        } else {
            bulkBar.style.display = 'none';
        }
        
        const selectAll = document.getElementById('selectAll');
        const allCheckboxes = document.querySelectorAll('.driver-checkbox');
        selectAll.checked = selectedDrivers.size === allCheckboxes.length && allCheckboxes.length > 0;
    }

    function clearSelection() {
        selectedDrivers.clear();
        document.querySelectorAll('.driver-checkbox').forEach(checkbox => checkbox.checked = false);
        document.getElementById('selectAll').checked = false;
        document.getElementById('bulkActionsBar').style.display = 'none';
    }

    async function bulkAction(action) {
        if (selectedDrivers.size === 0) return;
        
        const actionText = action === 'delete' ? 'delete' : action;
        if (!confirm(`Are you sure you want to ${actionText} ${selectedDrivers.size} selected drivers?`)) {
            return;
        }
        
        try {
            const response = await fetch('{{ route("admin.drivers.bulk-action") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    action: action,
                    driver_ids: Array.from(selectedDrivers)
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

    async function exportDrivers(format) {
        try {
            const url = new URL('{{ route("admin.drivers.export") }}');
            url.searchParams.append('format', format);
            
            // Add current filters
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('search')) url.searchParams.append('search', urlParams.get('search'));
            if (urlParams.get('status')) url.searchParams.append('status', urlParams.get('status'));
            if (urlParams.get('verification_status')) url.searchParams.append('verification_status', urlParams.get('verification_status'));
            if (urlParams.get('availability_status')) url.searchParams.append('availability_status', urlParams.get('availability_status'));
            
            window.open(url.toString(), '_blank');
            showNotification('Export started. Download will begin shortly.', 'info');
            
        } catch (error) {
            console.error('Export error:', error);
            showNotification('Error exporting drivers', 'error');
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Driver management page initialized');
        updateBulkActions();
    });
</script>
@endpush