{{-- resources/views/coupon/admin/coupons/index.blade.php --}}

@extends('admin::layouts.admin')

@section('title', 'Coupon Management')
@section('page-title', 'Coupon Management')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-primary">Coupons</h1>
        <p class="text-gray-600 mt-1">Manage discount coupons and promotions (Total: {{ $totalCoupons }})</p>
    </div>
    <div class="flex gap-3">
        <button onclick="syncFirebaseCoupons()" 
                class="bg-warning text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition-colors" 
                id="syncBtn">
            <i class="fas fa-sync mr-2"></i>Sync Firebase
        </button>
        <a href="{{ route('coupons.create') }}" 
           class="bg-success text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors">
            <i class="fas fa-plus mr-2"></i>Create Coupons
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
    <form method="GET" action="{{ route('coupons.index') }}" class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-64">
            <input type="text" name="search" value="{{ $search ?? '' }}" 
                   placeholder="Search Coupons Type, Discount Type and Code"
                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
        </div>
        <div class="min-w-48">
            <select name="status" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Status</option>
                <option value="enabled" {{ ($status ?? '') === 'enabled' ? 'selected' : '' }}>Enabled</option>
                <option value="disabled" {{ ($status ?? '') === 'disabled' ? 'selected' : '' }}>Disabled</option>
                <option value="expired" {{ ($status ?? '') === 'expired' ? 'selected' : '' }}>Expired</option>
                <option value="exhausted" {{ ($status ?? '') === 'exhausted' ? 'selected' : '' }}>Exhausted</option>
            </select>
        </div>
        <div class="min-w-48">
            <select name="coupon_type" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Types</option>
                <option value="ride" {{ ($coupon_type ?? '') === 'ride' ? 'selected' : '' }}>Ride</option>
                <option value="delivery" {{ ($coupon_type ?? '') === 'delivery' ? 'selected' : '' }}>Delivery</option>
                <option value="both" {{ ($coupon_type ?? '') === 'both' ? 'selected' : '' }}>Both</option>
            </select>
        </div>
        <div class="min-w-48">
            <select name="discount_type" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Discount Types</option>
                <option value="percentage" {{ ($discount_type ?? '') === 'percentage' ? 'selected' : '' }}>Percentage</option>
                <option value="fixed" {{ ($discount_type ?? '') === 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
            </select>
        </div>
        <div class="min-w-32">
            <select name="limit" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="25" {{ ($limit ?? 25) == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ ($limit ?? 25) == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ ($limit ?? 25) == 100 ? 'selected' : '' }}>100</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-search mr-2"></i>Filter
            </button>
            <a href="{{ route('coupons.index') }}" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors">
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
                <span id="selectedCount">0</span> coupons selected
            </span>
            <div class="flex gap-2">
                <button onclick="bulkAction('enable')" class="bg-success text-white px-3 py-1 rounded text-sm hover:bg-green-600">
                    <i class="fas fa-check mr-1"></i>Enable
                </button>
                <button onclick="bulkAction('disable')" class="bg-warning text-white px-3 py-1 rounded text-sm hover:bg-yellow-600">
                    <i class="fas fa-ban mr-1"></i>Disable
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

<!-- Firebase Sync Status -->
<div id="syncStatus" class="hidden mb-4">
    <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded">
        <div class="flex items-center">
            <i class="fas fa-sync fa-spin mr-2"></i>
            <span>Firebase sync in progress... Check logs for detailed progress.</span>
        </div>
    </div>
</div>

<!-- Coupons Table -->
<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    @if(count($coupons) > 0)
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left">
                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" 
                               class="rounded border-gray-300 text-primary focus:ring-primary">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Coupons Type
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Discount
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Discount Type
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Description
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Code
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Expires At
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($coupons as $coupon)
                <tr data-coupon-code="{{ $coupon->code }}" class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <input type="checkbox" class="coupon-checkbox rounded border-gray-300 text-primary focus:ring-primary" 
                               value="{{ $coupon->code }}" onchange="updateBulkActions()">
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ ucfirst($coupon->coupon_type) }}
                    </td>
                    <td class="px-6 py-4 text-sm">
                        @if($coupon->discount_type === 'percentage')
                            {{ $coupon->discount_value }}%
                        @else
                            ${{ number_format($coupon->discount_value, 2) }}
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ ucfirst($coupon->discount_type) }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ Str::limit($coupon->description, 50) }}
                    </td>
                    <td class="px-6 py-4 text-sm font-mono text-gray-900">
                        {{ $coupon->code }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ $coupon->expires_at->format('M d, Y H:i') }}
                        <div class="text-xs text-gray-500">
                            {{ $coupon->expires_at->diffForHumans() }}
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        @if($coupon->status === 'enabled')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>Enabled
                            </span>
                        @elseif($coupon->status === 'disabled')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                <i class="fas fa-times-circle mr-1"></i>Disabled
                            </span>
                        @elseif($coupon->status === 'expired')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                <i class="fas fa-clock mr-1"></i>Expired
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                <i class="fas fa-battery-quarter mr-1"></i>Exhausted
                            </span>
                        @endif
                    </td>
                   
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('coupons.show', $coupon->code) }}" 
                               class="text-primary hover:text-blue-700 p-1" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('coupons.edit', $coupon->code) }}" 
                               class="text-green-600 hover:text-green-800 p-1" title="Edit Coupon">
                                <i class="fas fa-edit"></i>
                            </a>
                            @if($coupon->status === 'enabled')
                                <button onclick="toggleCouponStatus('{{ $coupon->code }}', 'disabled')" 
                                        class="text-yellow-600 hover:text-yellow-800 p-1" title="Disable Coupon">
                                    <i class="fas fa-ban"></i>
                                </button>
                            @else
                                <button onclick="toggleCouponStatus('{{ $coupon->code }}', 'enabled')" 
                                        class="text-green-600 hover:text-green-800 p-1" title="Enable Coupon">
                                    <i class="fas fa-check-circle"></i>
                                </button>
                            @endif
                            <button onclick="deleteCoupon('{{ $coupon->code }}', '{{ addslashes($coupon->code) }}')" 
                                    class="text-red-600 hover:text-red-800 p-1" title="Delete Coupon">
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
                Showing {{ count($coupons) }} of {{ $totalCoupons }} coupons
                <div class="text-xs text-gray-500 mt-1">
                    Rows per page: {{ $limit ?? 25 }}
                    <span class="ml-4">1-{{ count($coupons) }} of {{ $totalCoupons }}</span>
                </div>
            </div>
            <div class="flex gap-2">
                <button onclick="exportCoupons('csv')" class="text-primary hover:bg-blue-50 px-3 py-1 rounded">
                    <i class="fas fa-file-csv mr-1"></i>CSV Export
                </button>
            </div>
        </div>
    </div>
    @else
    <div class="text-center py-12">
        <i class="fas fa-ticket-alt text-4xl text-gray-400 mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No Coupons Found</h3>
        <p class="text-gray-500 mb-4">
            @if(isset($search) && $search)
                No coupons match your search criteria "{{ $search }}". Try adjusting your filters.
            @else
                No coupons found in the system. Create some coupons to get started.
            @endif
        </p>
        <div class="flex justify-center gap-3">
            <a href="{{ route('coupons.create') }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>Create First Coupon
            </a>
            <button onclick="syncFirebaseCoupons()" class="bg-warning text-white px-4 py-2 rounded-lg hover:bg-yellow-600">
                <i class="fas fa-sync mr-2"></i>Sync Firebase Coupons
            </button>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    let selectedCoupons = new Set();

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
        if (button) {
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
        }
    }

    function hideLoading(button, originalText) {
        if (button) {
            button.disabled = false;
            button.innerHTML = originalText;
        }
    }

  async function syncFirebaseCoupons() {
    if (!confirm('This will sync coupons to Firebase in the background. Continue?')) {
        return;
    }
    
    const btn = document.getElementById('syncBtn');
    const originalText = btn.innerHTML;
    showLoading(btn);
    
    try {
        // Use the correct URL path
const response = await fetch('{{ route("coupons.sync-firebase") }}', {            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });
        
        if (response.ok) {
            const result = await response.json();
            showNotification(result.message || 'Sync started successfully!', 'success');
        } else {
            showNotification('Failed to start sync', 'error');
        }
    } catch (error) {
        console.error('Sync error:', error);
        showNotification('Error starting sync: Connection failed', 'error');
    } finally {
        hideLoading(btn, originalText);
    }
}

    async function deleteCoupon(couponCode, displayCode) {
        if (!confirm(`Are you sure you want to delete coupon "${displayCode}"? This action cannot be undone.`)) {
            return;
        }
        
        try {
            const response = await fetch('{{ route("coupons.destroy", ["code" => ":code"]) }}'.replace(':code', couponCode), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            
            if (response.ok) {
                showNotification('Coupon deleted successfully', 'success');
                const row = document.querySelector(`tr[data-coupon-code="${couponCode}"]`);
                if (row) row.remove();
                
                // Update totals
                setTimeout(() => window.location.reload(), 1000);
            } else {
                const result = await response.json();
                showNotification('Failed to delete coupon: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Delete error:', error);
            showNotification('Error deleting coupon: Connection failed', 'error');
        }
    }

    async function toggleCouponStatus(couponCode, status) {
        const actionText = status === 'enabled' ? 'enable' : 'disable';
        if (!confirm(`Are you sure you want to ${actionText} this coupon?`)) {
            return;
        }
        
        try {
            const response = await fetch('{{ route("coupons.update-status", ["code" => ":code"]) }}'.replace(':code', couponCode), {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: status })
            });
            
            if (response.ok) {
                showNotification(`Coupon ${actionText}d successfully`, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                const result = await response.json();
                showNotification(`Failed to ${actionText} coupon: ` + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Toggle status error:', error);
            showNotification(`Error ${actionText}ing coupon: Connection failed`, 'error');
        }
    }

    function toggleSelectAll() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.coupon-checkbox');
        
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAll.checked;
            if (selectAll.checked) {
                selectedCoupons.add(checkbox.value);
            } else {
                selectedCoupons.delete(checkbox.value);
            }
        });
        
        updateBulkActions();
    }

    function updateBulkActions() {
        const checkboxes = document.querySelectorAll('.coupon-checkbox:checked');
        const bulkBar = document.getElementById('bulkActionsBar');
        const selectedCount = document.getElementById('selectedCount');
        
        selectedCoupons.clear();
        checkboxes.forEach(checkbox => selectedCoupons.add(checkbox.value));
        
        if (selectedCoupons.size > 0) {
            bulkBar.style.display = 'block';
            selectedCount.textContent = selectedCoupons.size;
        } else {
            bulkBar.style.display = 'none';
        }
        
        const selectAll = document.getElementById('selectAll');
        const allCheckboxes = document.querySelectorAll('.coupon-checkbox');
        selectAll.checked = selectedCoupons.size === allCheckboxes.length && allCheckboxes.length > 0;
    }

    function clearSelection() {
        selectedCoupons.clear();
        document.querySelectorAll('.coupon-checkbox').forEach(checkbox => checkbox.checked = false);
        document.getElementById('selectAll').checked = false;
        document.getElementById('bulkActionsBar').style.display = 'none';
    }

    async function bulkAction(action) {
        if (selectedCoupons.size === 0) return;
        
        const actionText = action === 'delete' ? 'delete' : action;
        if (!confirm(`Are you sure you want to ${actionText} ${selectedCoupons.size} selected coupons?`)) {
            return;
        }
        
        try {
            const response = await fetch('{{ route("coupons.bulk-action") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    action: action,
                    coupon_codes: Array.from(selectedCoupons)
                })
            });
            
            if (response.ok) {
                const result = await response.json();
                showNotification(result.message || `Bulk ${actionText} completed successfully`, 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                const result = await response.json();
                showNotification('Bulk action failed: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Bulk action error:', error);
            showNotification('Error performing bulk action: Connection failed', 'error');
        }
    }

    async function exportCoupons(format) {
        try {
            const url = new URL('{{ route("coupons.export") }}');
            url.searchParams.append('format', format);
            
            // Add current filters
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('search')) url.searchParams.append('search', urlParams.get('search'));
            if (urlParams.get('status')) url.searchParams.append('status', urlParams.get('status'));
            if (urlParams.get('coupon_type')) url.searchParams.append('coupon_type', urlParams.get('coupon_type'));
            if (urlParams.get('discount_type')) url.searchParams.append('discount_type', urlParams.get('discount_type'));
            
            window.open(url.toString(), '_blank');
            showNotification('Export started. Download will begin shortly.', 'info');
            
        } catch (error) {
            console.error('Export error:', error);
            showNotification('Error exporting coupons', 'error');
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Coupon management page initialized');
        updateBulkActions();
        
        // Auto-refresh every 30 seconds to update Firebase sync status
        setInterval(() => {
            // Only refresh if no active operations
            if (selectedCoupons.size === 0) {
                const xhr = new XMLHttpRequest();
                xhr.open('GET', window.location.href, true);
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        // Parse response and update Firebase sync status indicators
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(xhr.responseText, 'text/html');
                        const newSyncCells = doc.querySelectorAll('tbody tr td:nth-child(9)');
                        const currentSyncCells = document.querySelectorAll('tbody tr td:nth-child(9)');
                        
                        newSyncCells.forEach((newCell, index) => {
                            if (currentSyncCells[index]) {
                                currentSyncCells[index].innerHTML = newCell.innerHTML;
                            }
                        });
                    }
                };
                xhr.send();
            }
        }, 30000);
    });
</script>
@endpush