{{-- resources/views/commission/admin/commissions/index.blade.php --}}

@extends('admin::layouts.admin')

@section('title', 'Commission Management')
@section('page-title', 'Commission Management')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-primary">Commissions</h1>
        <p class="text-gray-600 mt-1">Manage commission structures and payouts (Total: {{ $totalCommissions }})</p>
    </div>
    <div class="flex gap-3">
        <button onclick="syncFirebaseCommissions()"
            class="bg-warning text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition-colors"
            id="syncBtn">
            <i class="fas fa-sync mr-2"></i>Sync Firebase
        </button>
        <a href="{{ route('commissions.create') }}"
            class="bg-success text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors">
            <i class="fas fa-plus mr-2"></i>Create Commission
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
    <form method="GET" action="{{ route('commissions.index') }}" class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-64">
            <input type="text" name="search" value="{{ $search ?? '' }}"
                placeholder="Search by name, description, or recipient type"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
        </div>
        <div class="min-w-48">
            <select name="commission_type" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Commission Types</option>
                <option value="percentage" {{ ($commission_type ?? '') === 'percentage' ? 'selected' : '' }}>Percentage</option>
                <option value="fixed" {{ ($commission_type ?? '') === 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                <option value="hybrid" {{ ($commission_type ?? '') === 'hybrid' ? 'selected' : '' }}>Hybrid</option>
            </select>
        </div>
        <div class="min-w-48">
            <select name="recipient_type" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Recipients</option>
                <option value="driver" {{ ($recipient_type ?? '') === 'driver' ? 'selected' : '' }}>Driver</option>
                <option value="company" {{ ($recipient_type ?? '') === 'company' ? 'selected' : '' }}>Company</option>
                <option value="partner" {{ ($recipient_type ?? '') === 'partner' ? 'selected' : '' }}>Partner</option>
                <option value="referrer" {{ ($recipient_type ?? '') === 'referrer' ? 'selected' : '' }}>Referrer</option>
            </select>
        </div>
        <div class="min-w-48">
            <select name="calculation_method" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Calculation Methods</option>
                <option value="gross" {{ ($calculation_method ?? '') === 'gross' ? 'selected' : '' }}>Gross Amount</option>
                <option value="net" {{ ($calculation_method ?? '') === 'net' ? 'selected' : '' }}>Net Amount</option>
                <option value="trip_fare" {{ ($calculation_method ?? '') === 'trip_fare' ? 'selected' : '' }}>Trip Fare</option>
                <option value="base_fare" {{ ($calculation_method ?? '') === 'base_fare' ? 'selected' : '' }}>Base Fare</option>
            </select>
        </div>
        <div class="min-w-48">
            <select name="payment_frequency" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Frequencies</option>
                <option value="instant" {{ ($payment_frequency ?? '') === 'instant' ? 'selected' : '' }}>Instant</option>
                <option value="daily" {{ ($payment_frequency ?? '') === 'daily' ? 'selected' : '' }}>Daily</option>
                <option value="weekly" {{ ($payment_frequency ?? '') === 'weekly' ? 'selected' : '' }}>Weekly</option>
                <option value="monthly" {{ ($payment_frequency ?? '') === 'monthly' ? 'selected' : '' }}>Monthly</option>
            </select>
        </div>
        <div class="min-w-32">
            <select name="is_active" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Status</option>
                <option value="1" {{ ($is_active ?? '') === '1' ? 'selected' : '' }}>Active</option>
                <option value="0" {{ ($is_active ?? '') === '0' ? 'selected' : '' }}>Inactive</option>
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
            <a href="{{ route('commissions.index') }}" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors">
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
                <span id="selectedCount">0</span> commissions selected
            </span>
            <div class="flex gap-2">
                <button onclick="bulkAction('activate')" class="bg-success text-white px-3 py-1 rounded text-sm hover:bg-green-600">
                    <i class="fas fa-check mr-1"></i>Activate
                </button>
                <button onclick="bulkAction('deactivate')" class="bg-warning text-white px-3 py-1 rounded text-sm hover:bg-yellow-600">
                    <i class="fas fa-ban mr-1"></i>Deactivate
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

<!-- Commissions Table -->
<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    @if(count($commissions) > 0)
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left">
                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()"
                            class="rounded border-gray-300 text-primary focus:ring-primary">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Name
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Type
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Recipient
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Rate/Amount
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Payment Frequency
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Auto Payout
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
                @foreach($commissions as $commission)
                <tr data-commission-id="{{ $commission->id }}" class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <input type="checkbox" class="commission-checkbox rounded border-gray-300 text-primary focus:ring-primary"
                            value="{{ $commission->id }}" onchange="updateBulkActions()">
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900 font-medium">
                        {{ $commission->name }}
                        @if($commission->description)
                        <div class="text-xs text-gray-500 mt-1">{{ Str::limit($commission->description, 50) }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            {{ $commission->commission_type === 'percentage' ? 'bg-blue-100 text-blue-800' : 
                               ($commission->commission_type === 'fixed' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800') }}">
                            {{ ucfirst($commission->commission_type) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            {{ $commission->recipient_type === 'driver' ? 'bg-blue-100 text-blue-800' : 
                               ($commission->recipient_type === 'company' ? 'bg-gray-100 text-gray-800' : 
                               ($commission->recipient_type === 'partner' ? 'bg-purple-100 text-purple-800' : 'bg-orange-100 text-orange-800')) }}">
                            {{ ucfirst($commission->recipient_type) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ $commission->formatted_rate }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ ucfirst($commission->payment_frequency) }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        @if($commission->auto_payout)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="fas fa-check mr-1"></i>Enabled
                        </span>
                        @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            <i class="fas fa-times mr-1"></i>Disabled
                        </span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($commission->is_active)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="fas fa-check-circle mr-1"></i>Active
                        </span>
                        @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <i class="fas fa-times-circle mr-1"></i>Inactive
                        </span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('commissions.show', $commission->id) }}"
                                class="text-primary hover:text-blue-700 p-1" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('commissions.edit', $commission->id) }}"
                                class="text-green-600 hover:text-green-800 p-1" title="Edit Commission">
                                <i class="fas fa-edit"></i>
                            </a>
                            @if($commission->is_active)
                            <button onclick="toggleCommissionStatus('{{ $commission->id }}', false)"
                                class="text-yellow-600 hover:text-yellow-800 p-1" title="Deactivate Commission">
                                <i class="fas fa-ban"></i>
                            </button>
                            @else
                            <button onclick="toggleCommissionStatus('{{ $commission->id }}', true)"
                                class="text-green-600 hover:text-green-800 p-1" title="Activate Commission">
                                <i class="fas fa-check-circle"></i>
                            </button>
                            @endif
                            <button onclick="deleteCommission('{{ $commission->id }}', '{{ addslashes($commission->name) }}')"
                                class="text-red-600 hover:text-red-800 p-1" title="Delete Commission">
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
                Showing {{ count($commissions) }} of {{ $totalCommissions }} commissions
                <div class="text-xs text-gray-500 mt-1">
                    Rows per page: {{ $limit ?? 25 }}
                    <span class="ml-4">1-{{ count($commissions) }} of {{ $totalCommissions }}</span>
                </div>
            </div>
            <div class="flex gap-2">
                <button onclick="exportCommissions('csv')" class="text-primary hover:bg-blue-50 px-3 py-1 rounded">
                    <i class="fas fa-file-csv mr-1"></i>CSV Export
                </button>
            </div>
        </div>
    </div>
    @else
    <div class="text-center py-12">
        <i class="fas fa-percentage text-4xl text-gray-400 mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No Commissions Found</h3>
        <p class="text-gray-500 mb-4">
            @if(isset($search) && $search)
            No commissions match your search criteria "{{ $search }}". Try adjusting your filters.
            @else
            No commissions found in the system. Create some commission structures to get started.
            @endif
        </p>
        <div class="flex justify-center gap-3">
            <a href="{{ route('commissions.create') }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>Create First Commission
            </a>
            <button onclick="syncFirebaseCommissions()" class="bg-warning text-white px-4 py-2 rounded-lg hover:bg-yellow-600">
                <i class="fas fa-sync mr-2"></i>Sync Firebase Commissions
            </button>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    let selectedCommissions = new Set();

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

    async function syncFirebaseCommissions() {
        if (!confirm('This will sync commissions to Firebase in the background. Continue?')) {
            return;
        }

        const btn = document.getElementById('syncBtn');
        const originalText = btn.innerHTML;
        showLoading(btn);

        try {
            const response = await fetch('{{ route("commissions.sync-firebase") }}', {
                method: 'POST',
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

    async function deleteCommission(commissionId, displayName) {
        if (!confirm(`Are you sure you want to delete commission "${displayName}"? This action cannot be undone.`)) {
            return;
        }

        try {
            const response = await fetch('{{ route("commissions.destroy", ["id" => ":id"]) }}'.replace(':id', commissionId), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                showNotification('Commission deleted successfully', 'success');
                const row = document.querySelector(`tr[data-commission-id="${commissionId}"]`);
                if (row) row.remove();

                // Update totals
                setTimeout(() => window.location.reload(), 1000);
            } else {
                const result = await response.json();
                showNotification('Failed to delete commission: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Delete error:', error);
            showNotification('Error deleting commission: Connection failed', 'error');
        }
    }

    async function toggleCommissionStatus(commissionId, isActive) {
        const actionText = isActive ? 'activate' : 'deactivate';
        if (!confirm(`Are you sure you want to ${actionText} this commission?`)) {
            return;
        }

        try {
            const response = await fetch('{{ route("commissions.update-status", ["id" => ":id"]) }}'.replace(':id', commissionId), {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    is_active: isActive
                })
            });

            if (response.ok) {
                showNotification(`Commission ${actionText}d successfully`, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                const result = await response.json();
                showNotification(`Failed to ${actionText} commission: ` + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Toggle status error:', error);
            showNotification(`Error ${actionText}ing commission: Connection failed`, 'error');
        }
    }

    function toggleSelectAll() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.commission-checkbox');

        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAll.checked;
            if (selectAll.checked) {
                selectedCommissions.add(checkbox.value);
            } else {
                selectedCommissions.delete(checkbox.value);
            }
        });

        updateBulkActions();
    }

    function updateBulkActions() {
        const checkboxes = document.querySelectorAll('.commission-checkbox:checked');
        const bulkBar = document.getElementById('bulkActionsBar');
        const selectedCount = document.getElementById('selectedCount');

        selectedCommissions.clear();
        checkboxes.forEach(checkbox => selectedCommissions.add(checkbox.value));

        if (selectedCommissions.size > 0) {
            bulkBar.style.display = 'block';
            selectedCount.textContent = selectedCommissions.size;
        } else {
            bulkBar.style.display = 'none';
        }

        const selectAll = document.getElementById('selectAll');
        const allCheckboxes = document.querySelectorAll('.commission-checkbox');
        selectAll.checked = selectedCommissions.size === allCheckboxes.length && allCheckboxes.length > 0;
    }

    function clearSelection() {
        selectedCommissions.clear();
        document.querySelectorAll('.commission-checkbox').forEach(checkbox => checkbox.checked = false);
        document.getElementById('selectAll').checked = false;
        document.getElementById('bulkActionsBar').style.display = 'none';
    }

    async function bulkAction(action) {
        if (selectedCommissions.size === 0) return;

        const actionText = action === 'delete' ? 'delete' : action;
        if (!confirm(`Are you sure you want to ${actionText} ${selectedCommissions.size} selected commissions?`)) {
            return;
        }

        try {
            const response = await fetch('{{ route("commissions.bulk-action") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    action: action,
                    commission_ids: Array.from(selectedCommissions)
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

    async function exportCommissions(format) {
        try {
            const url = new URL('{{ route("commissions.export") }}');
            url.searchParams.append('format', format);

            // Add current filters
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('search')) url.searchParams.append('search', urlParams.get('search'));
            if (urlParams.get('commission_type')) url.searchParams.append('commission_type', urlParams.get('commission_type'));
            if (urlParams.get('recipient_type')) url.searchParams.append('recipient_type', urlParams.get('recipient_type'));
            if (urlParams.get('calculation_method')) url.searchParams.append('calculation_method', urlParams.get('calculation_method'));
            if (urlParams.get('payment_frequency')) url.searchParams.append('payment_frequency', urlParams.get('payment_frequency'));
            if (urlParams.get('is_active')) url.searchParams.append('is_active', urlParams.get('is_active'));

            window.open(url.toString(), '_blank');
            showNotification('Export started. Download will begin shortly.', 'info');

        } catch (error) {
            console.error('Export error:', error);
            showNotification('Error exporting commissions', 'error');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        console.log('Commission management page initialized');
        updateBulkActions();
    });
</script>
@endpush