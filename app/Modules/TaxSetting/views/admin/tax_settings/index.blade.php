{{-- resources/views/taxsetting/admin/tax-settings/index.blade.php --}}

@extends('admin::layouts.admin')

@section('title', 'Tax Settings Management')
@section('page-title', 'Tax Settings Management')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-primary">Tax Settings</h1>
        <p class="text-gray-600 mt-1">Manage tax configurations and calculations (Total: {{ $totalTaxSettings }})</p>
    </div>
    <div class="flex gap-3">
        <button onclick="syncFirebaseTaxSettings()"
            class="bg-warning text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition-colors"
            id="syncBtn">
            <i class="fas fa-sync mr-2"></i>Sync Firebase
        </button>
        <a href="{{ route('tax-settings.create') }}"
            class="bg-success text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors">
            <i class="fas fa-plus mr-2"></i>Create Tax Setting
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
    <form method="GET" action="{{ route('tax-settings.index') }}" class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-64">
            <input type="text" name="search" value="{{ $search ?? '' }}"
                placeholder="Search by name, description, or tax type"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
        </div>
        <div class="min-w-48">
            <select name="tax_type" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Tax Types</option>
                <option value="percentage" {{ ($tax_type ?? '') === 'percentage' ? 'selected' : '' }}>Percentage</option>
                <option value="fixed" {{ ($tax_type ?? '') === 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                <option value="hybrid" {{ ($tax_type ?? '') === 'hybrid' ? 'selected' : '' }}>Hybrid</option>
            </select>
        </div>
        <div class="min-w-48">
            <select name="calculation_method" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Calculation Methods</option>
                <option value="simple" {{ ($calculation_method ?? '') === 'simple' ? 'selected' : '' }}>Simple</option>
                <option value="compound" {{ ($calculation_method ?? '') === 'compound' ? 'selected' : '' }}>Compound</option>
                <option value="cascading" {{ ($calculation_method ?? '') === 'cascading' ? 'selected' : '' }}>Cascading</option>
            </select>
        </div>
        <div class="min-w-48">
            <select name="applicable_to" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Services</option>
                <option value="all" {{ ($applicable_to ?? '') === 'all' ? 'selected' : '' }}>All Services</option>
                <option value="rides" {{ ($applicable_to ?? '') === 'rides' ? 'selected' : '' }}>Rides Only</option>
                <option value="delivery" {{ ($applicable_to ?? '') === 'delivery' ? 'selected' : '' }}>Delivery Only</option>
                <option value="specific" {{ ($applicable_to ?? '') === 'specific' ? 'selected' : '' }}>Specific Services</option>
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
            <a href="{{ route('tax-settings.index') }}" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors">
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
                <span id="selectedCount">0</span> tax settings selected
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

<!-- Tax Settings Table -->
<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    @if(count($taxSettings) > 0)
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
                        Tax Type
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Rate/Amount
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Calculation Method
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Applicable To
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Priority
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
                @foreach($taxSettings as $taxSetting)
                <tr data-tax-setting-id="{{ $taxSetting->id }}" class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <input type="checkbox" class="tax-setting-checkbox rounded border-gray-300 text-primary focus:ring-primary"
                            value="{{ $taxSetting->id }}" onchange="updateBulkActions()">
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900 font-medium">
                        {{ $taxSetting->name }}
                        @if($taxSetting->description)
                        <div class="text-xs text-gray-500 mt-1">{{ Str::limit($taxSetting->description, 50) }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            {{ $taxSetting->tax_type === 'percentage' ? 'bg-blue-100 text-blue-800' : 
                               ($taxSetting->tax_type === 'fixed' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800') }}">
                            {{ ucfirst($taxSetting->tax_type) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ $taxSetting->formatted_rate }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ ucfirst($taxSetting->calculation_method) }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ ucfirst(str_replace('_', ' ', $taxSetting->applicable_to)) }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            #{{ $taxSetting->priority_order }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        @if($taxSetting->is_active)
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
                            <a href="{{ route('tax-settings.show', $taxSetting->id) }}"
                                class="text-primary hover:text-blue-700 p-1" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('tax-settings.edit', $taxSetting->id) }}"
                                class="text-green-600 hover:text-green-800 p-1" title="Edit Tax Setting">
                                <i class="fas fa-edit"></i>
                            </a>
                            @if($taxSetting->is_active)
                            <button onclick="toggleTaxSettingStatus('{{ $taxSetting->id }}', false)"
                                class="text-yellow-600 hover:text-yellow-800 p-1" title="Deactivate Tax Setting">
                                <i class="fas fa-ban"></i>
                            </button>
                            @else
                            <button onclick="toggleTaxSettingStatus('{{ $taxSetting->id }}', true)"
                                class="text-green-600 hover:text-green-800 p-1" title="Activate Tax Setting">
                                <i class="fas fa-check-circle"></i>
                            </button>
                            @endif
                            <button onclick="deleteTaxSetting('{{ $taxSetting->id }}', '{{ addslashes($taxSetting->name) }}')"
                                class="text-red-600 hover:text-red-800 p-1" title="Delete Tax Setting">
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
                Showing {{ count($taxSettings) }} of {{ $totalTaxSettings }} tax settings
                <div class="text-xs text-gray-500 mt-1">
                    Rows per page: {{ $limit ?? 25 }}
                    <span class="ml-4">1-{{ count($taxSettings) }} of {{ $totalTaxSettings }}</span>
                </div>
            </div>
            <div class="flex gap-2">
                <button onclick="exportTaxSettings('csv')" class="text-primary hover:bg-blue-50 px-3 py-1 rounded">
                    <i class="fas fa-file-csv mr-1"></i>CSV Export
                </button>
            </div>
        </div>
    </div>
    @else
    <div class="text-center py-12">
        <i class="fas fa-calculator text-4xl text-gray-400 mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No Tax Settings Found</h3>
        <p class="text-gray-500 mb-4">
            @if(isset($search) && $search)
            No tax settings match your search criteria "{{ $search }}". Try adjusting your filters.
            @else
            No tax settings found in the system. Create some tax settings to get started.
            @endif
        </p>
        <div class="flex justify-center gap-3">
            <a href="{{ route('tax-settings.create') }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>Create First Tax Setting
            </a>
            <button onclick="syncFirebaseTaxSettings()" class="bg-warning text-white px-4 py-2 rounded-lg hover:bg-yellow-600">
                <i class="fas fa-sync mr-2"></i>Sync Firebase Tax Settings
            </button>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    let selectedTaxSettings = new Set();

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

    async function syncFirebaseTaxSettings() {
        if (!confirm('This will sync tax settings to Firebase in the background. Continue?')) {
            return;
        }

        const btn = document.getElementById('syncBtn');
        const originalText = btn.innerHTML;
        showLoading(btn);

        try {
            const response = await fetch('{{ route("tax-settings.sync-firebase") }}', {
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

    async function deleteTaxSetting(taxSettingId, displayName) {
        if (!confirm(`Are you sure you want to delete tax setting "${displayName}"? This action cannot be undone.`)) {
            return;
        }

        try {
            const response = await fetch('{{ route("tax-settings.destroy", ["id" => ":id"]) }}'.replace(':id', taxSettingId), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                showNotification('Tax setting deleted successfully', 'success');
                const row = document.querySelector(`tr[data-tax-setting-id="${taxSettingId}"]`);
                if (row) row.remove();

                // Update totals
                setTimeout(() => window.location.reload(), 1000);
            } else {
                const result = await response.json();
                showNotification('Failed to delete tax setting: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Delete error:', error);
            showNotification('Error deleting tax setting: Connection failed', 'error');
        }
    }

    async function toggleTaxSettingStatus(taxSettingId, isActive) {
        const actionText = isActive ? 'activate' : 'deactivate';
        if (!confirm(`Are you sure you want to ${actionText} this tax setting?`)) {
            return;
        }

        try {
            const response = await fetch('{{ route("tax-settings.update-status", ["id" => ":id"]) }}'.replace(':id', taxSettingId), {
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
                showNotification(`Tax setting ${actionText}d successfully`, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                const result = await response.json();
                showNotification(`Failed to ${actionText} tax setting: ` + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Toggle status error:', error);
            showNotification(`Error ${actionText}ing tax setting: Connection failed`, 'error');
        }
    }

    function toggleSelectAll() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.tax-setting-checkbox');

        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAll.checked;
            if (selectAll.checked) {
                selectedTaxSettings.add(checkbox.value);
            } else {
                selectedTaxSettings.delete(checkbox.value);
            }
        });

        updateBulkActions();
    }

    function updateBulkActions() {
        const checkboxes = document.querySelectorAll('.tax-setting-checkbox:checked');
        const bulkBar = document.getElementById('bulkActionsBar');
        const selectedCount = document.getElementById('selectedCount');

        selectedTaxSettings.clear();
        checkboxes.forEach(checkbox => selectedTaxSettings.add(checkbox.value));

        if (selectedTaxSettings.size > 0) {
            bulkBar.style.display = 'block';
            selectedCount.textContent = selectedTaxSettings.size;
        } else {
            bulkBar.style.display = 'none';
        }

        const selectAll = document.getElementById('selectAll');
        const allCheckboxes = document.querySelectorAll('.tax-setting-checkbox');
        selectAll.checked = selectedTaxSettings.size === allCheckboxes.length && allCheckboxes.length > 0;
    }

    function clearSelection() {
        selectedTaxSettings.clear();
        document.querySelectorAll('.tax-setting-checkbox').forEach(checkbox => checkbox.checked = false);
        document.getElementById('selectAll').checked = false;
        document.getElementById('bulkActionsBar').style.display = 'none';
    }

    async function bulkAction(action) {
        if (selectedTaxSettings.size === 0) return;

        const actionText = action === 'delete' ? 'delete' : action;
        if (!confirm(`Are you sure you want to ${actionText} ${selectedTaxSettings.size} selected tax settings?`)) {
            return;
        }

        try {
            const response = await fetch('{{ route("tax-settings.bulk-action") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    action: action,
                    tax_setting_ids: Array.from(selectedTaxSettings)
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

    async function exportTaxSettings(format) {
        try {
            const url = new URL('{{ route("tax-settings.export") }}');
            url.searchParams.append('format', format);

            // Add current filters
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('search')) url.searchParams.append('search', urlParams.get('search'));
            if (urlParams.get('tax_type')) url.searchParams.append('tax_type', urlParams.get('tax_type'));
            if (urlParams.get('calculation_method')) url.searchParams.append('calculation_method', urlParams.get('calculation_method'));
            if (urlParams.get('applicable_to')) url.searchParams.append('applicable_to', urlParams.get('applicable_to'));
            if (urlParams.get('is_active')) url.searchParams.append('is_active', urlParams.get('is_active'));

            window.open(url.toString(), '_blank');
            showNotification('Export started. Download will begin shortly.', 'info');

        } catch (error) {
            console.error('Export error:', error);
            showNotification('Error exporting tax settings', 'error');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        console.log('Tax Settings management page initialized');
        updateBulkActions();
    });
</script>
@endpush