{{-- resources/views/foq/admin/foqs/index.blade.php --}}

@extends('admin::layouts.admin')

@section('title', 'FOQ Management')
@section('page-title', 'FOQ Management')

@push('styles')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-primary">Frequently Offered Questions</h1>
        <p class="text-gray-600 mt-1">Manage FAQ, guides, and help content (Total: {{ $totalFoqs }})</p>
    </div>
    <div class="flex gap-3">
        <button onclick="syncFirebaseFoqs()" 
                class="bg-warning text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition-colors" 
                id="syncBtn">
            <i class="fas fa-sync mr-2"></i>Sync Firebase
        </button>
        <a href="{{ route('foqs.create') }}" 
           class="bg-success text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors">
            <i class="fas fa-plus mr-2"></i>Create FOQ
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
    <form method="GET" action="{{ route('foqs.index') }}" class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-64">
            <input type="text" name="search" value="{{ $search ?? '' }}" 
                   placeholder="Search questions, answers, and categories"
                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
        </div>
        <div class="min-w-48">
            <select name="status" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Status</option>
                <option value="active" {{ ($status ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ ($status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="draft" {{ ($status ?? '') === 'draft' ? 'selected' : '' }}>Draft</option>
            </select>
        </div>
        <div class="min-w-48">
            <select name="category" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Categories</option>
                <option value="general" {{ ($category ?? '') === 'general' ? 'selected' : '' }}>General</option>
                <option value="account" {{ ($category ?? '') === 'account' ? 'selected' : '' }}>Account</option>
                <option value="payment" {{ ($category ?? '') === 'payment' ? 'selected' : '' }}>Payment</option>
                <option value="rides" {{ ($category ?? '') === 'rides' ? 'selected' : '' }}>Rides</option>
                <option value="delivery" {{ ($category ?? '') === 'delivery' ? 'selected' : '' }}>Delivery</option>
                <option value="technical" {{ ($category ?? '') === 'technical' ? 'selected' : '' }}>Technical</option>
                <option value="safety" {{ ($category ?? '') === 'safety' ? 'selected' : '' }}>Safety</option>
            </select>
        </div>
        <div class="min-w-48">
            <select name="type" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Types</option>
                <option value="faq" {{ ($type ?? '') === 'faq' ? 'selected' : '' }}>FAQ</option>
                <option value="guide" {{ ($type ?? '') === 'guide' ? 'selected' : '' }}>Guide</option>
                <option value="troubleshoot" {{ ($type ?? '') === 'troubleshoot' ? 'selected' : '' }}>Troubleshoot</option>
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
            <a href="{{ route('foqs.index') }}" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors">
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
                <span id="selectedCount">0</span> FOQs selected
            </span>
            <div class="flex gap-2">
                <button onclick="bulkAction('activate')" class="bg-success text-white px-3 py-1 rounded text-sm hover:bg-green-600">
                    <i class="fas fa-check mr-1"></i>Activate
                </button>
                <button onclick="bulkAction('deactivate')" class="bg-warning text-white px-3 py-1 rounded text-sm hover:bg-yellow-600">
                    <i class="fas fa-ban mr-1"></i>Deactivate
                </button>
                <button onclick="bulkAction('feature')" class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">
                    <i class="fas fa-star mr-1"></i>Feature
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

<!-- FOQs Table -->
<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    @if(count($foqs) > 0)
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left">
                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" 
                               class="rounded border-gray-300 text-primary focus:ring-primary">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Question
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Category
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Type
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Priority
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Stats
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($foqs as $foq)
                <tr data-foq-id="{{ $foq->id }}" class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <input type="checkbox" class="foq-checkbox rounded border-gray-300 text-primary focus:ring-primary" 
                               value="{{ $foq->id }}" onchange="updateBulkActions()">
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-start">
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ Str::limit($foq->question, 60) }}
                                    @if($foq->is_featured)
                                        <i class="fas fa-star text-yellow-500 ml-2" title="Featured"></i>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    {{ $foq->excerpt ?? Str::limit(strip_tags($foq->answer), 80) }}
                                </div>
                                @if($foq->external_link)
                                    <div class="text-xs text-blue-600 mt-1">
                                        <i class="fas fa-external-link-alt mr-1"></i>External Link
                                    </div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            @if($foq->category === 'general') bg-gray-100 text-gray-800
                            @elseif($foq->category === 'account') bg-blue-100 text-blue-800
                            @elseif($foq->category === 'payment') bg-green-100 text-green-800
                            @elseif($foq->category === 'rides') bg-purple-100 text-purple-800
                            @elseif($foq->category === 'delivery') bg-orange-100 text-orange-800
                            @elseif($foq->category === 'technical') bg-red-100 text-red-800
                            @elseif($foq->category === 'safety') bg-yellow-100 text-yellow-800
                            @endif">
                            {{ ucfirst($foq->category) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ ucfirst($foq->type) }}
                    </td>
                    <td class="px-6 py-4">
                        @if($foq->priority === 'high')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                High
                            </span>
                        @elseif($foq->priority === 'normal')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Normal
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                Low
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($foq->status === 'active')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>Active
                            </span>
                        @elseif($foq->status === 'inactive')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                <i class="fas fa-times-circle mr-1"></i>Inactive
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                <i class="fas fa-edit mr-1"></i>Draft
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        <div class="space-y-1">
                            <div>Views: {{ number_format($foq->view_count ?? 0) }}</div>
                            @if(($foq->helpful_count ?? 0) > 0 || ($foq->not_helpful_count ?? 0) > 0)
                                <div class="text-xs">
                                    👍 {{ $foq->helpful_count ?? 0 }} 👎 {{ $foq->not_helpful_count ?? 0 }}
                                    ({{ $foq->helpfulness_ratio ?? 0 }}%)
                                </div>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                      
                            <a href="{{ route('foqs.edit', $foq->id) }}" 
                               class="text-green-600 hover:text-green-800 p-1" title="Edit FOQ">
                                <i class="fas fa-edit"></i>
                            </a>
                            @if($foq->status === 'active')
                                <button onclick="toggleFoqStatus({{ $foq->id }}, 'inactive')" 
                                        class="text-yellow-600 hover:text-yellow-800 p-1" title="Deactivate FOQ">
                                    <i class="fas fa-ban"></i>
                                </button>
                            @else
                                <button onclick="toggleFoqStatus({{ $foq->id }}, 'active')" 
                                        class="text-green-600 hover:text-green-800 p-1" title="Activate FOQ">
                                    <i class="fas fa-check-circle"></i>
                                </button>
                            @endif
                            <a href="{{ route('foqs.duplicate', $foq->id) }}" 
                               class="text-blue-600 hover:text-blue-800 p-1" title="Duplicate FOQ">
                                <i class="fas fa-copy"></i>
                            </a>
                            <button onclick="deleteFoq({{ $foq->id }}, '{{ addslashes($foq->question) }}')" 
                                    class="text-red-600 hover:text-red-800 p-1" title="Delete FOQ">
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
                Showing {{ count($foqs) }} of {{ $totalFoqs }} FOQs
                <div class="text-xs text-gray-500 mt-1">
                    Rows per page: {{ $limit ?? 25 }}
                    <span class="ml-4">1-{{ count($foqs) }} of {{ $totalFoqs }}</span>
                </div>
            </div>
            <div class="flex gap-2">
                <button onclick="exportFoqs('csv')" class="text-primary hover:bg-blue-50 px-3 py-1 rounded">
                    <i class="fas fa-file-csv mr-1"></i>CSV Export
                </button>
            </div>
        </div>
    </div>
    @else
    <div class="text-center py-12">
        <i class="fas fa-question-circle text-4xl text-gray-400 mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No FOQs Found</h3>
        <p class="text-gray-500 mb-4">
            @if(isset($search) && $search)
                No FOQs match your search criteria "{{ $search }}". Try adjusting your filters.
            @else
                No FOQs found in the system. Create some FOQs to get started.
            @endif
        </p>
        <div class="flex justify-center gap-3">
            <a href="{{ route('foqs.create') }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>Create First FOQ
            </a>
            <button onclick="syncFirebaseFoqs()" class="bg-warning text-white px-4 py-2 rounded-lg hover:bg-yellow-600">
                <i class="fas fa-sync mr-2"></i>Sync Firebase FOQs
            </button>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // FOQ Management JavaScript
    let selectedFoqs = new Set();

    // Route URLs for JavaScript
    const routes = {
        syncFirebase: '{{ route("foqs.sync-firebase") }}',
        updateStatus: '{{ route("foqs.update-status", ["id" => ":id"]) }}',
        destroy: '{{ route("foqs.destroy", ["id" => ":id"]) }}',
        bulkAction: '{{ route("foqs.bulk-action") }}',
        export: '{{ route("foqs.export") }}'
    };

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

    async function syncFirebaseFoqs() {
        if (!confirm('This will sync FOQs to Firebase in the background. Continue?')) {
            return;
        }
        
        const btn = document.getElementById('syncBtn');
        const originalText = btn.innerHTML;
        showLoading(btn);
        
        try {
            const response = await fetch(routes.syncFirebase, {
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

    async function deleteFoq(foqId, question) {
        if (!confirm(`Are you sure you want to delete FOQ "${question}"? This action cannot be undone.`)) {
            return;
        }
        
        try {
            const url = routes.destroy.replace(':id', foqId);
            const response = await fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            
            if (response.ok) {
                const result = await response.json();
                showNotification(result.message || 'FOQ deleted successfully', 'success');
                const row = document.querySelector(`tr[data-foq-id="${foqId}"]`);
                if (row) row.remove();
                
                setTimeout(() => window.location.reload(), 1000);
            } else {
                const result = await response.json();
                showNotification('Failed to delete FOQ: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Delete error:', error);
            showNotification('Error deleting FOQ: Connection failed', 'error');
        }
    }

    async function toggleFoqStatus(foqId, status) {
        const actionText = status === 'active' ? 'activate' : 'deactivate';
        if (!confirm(`Are you sure you want to ${actionText} this FOQ?`)) {
            return;
        }
        
        try {
            const url = routes.updateStatus.replace(':id', foqId);
            const response = await fetch(url, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: status })
            });
            
            if (response.ok) {
                const result = await response.json();
                showNotification(result.message || `FOQ ${actionText}d successfully`, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                const result = await response.json();
                showNotification(`Failed to ${actionText} FOQ: ` + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Toggle status error:', error);
            showNotification(`Error ${actionText}ing FOQ: Connection failed`, 'error');
        }
    }

    function toggleSelectAll() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.foq-checkbox');
        
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAll.checked;
            if (selectAll.checked) {
                selectedFoqs.add(parseInt(checkbox.value));
            } else {
                selectedFoqs.delete(parseInt(checkbox.value));
            }
        });
        
        updateBulkActions();
    }

    function updateBulkActions() {
        const checkboxes = document.querySelectorAll('.foq-checkbox:checked');
        const bulkBar = document.getElementById('bulkActionsBar');
        const selectedCount = document.getElementById('selectedCount');
        
        selectedFoqs.clear();
        checkboxes.forEach(checkbox => selectedFoqs.add(parseInt(checkbox.value)));
        
        if (selectedFoqs.size > 0) {
            bulkBar.style.display = 'block';
            selectedCount.textContent = selectedFoqs.size;
        } else {
            bulkBar.style.display = 'none';
        }
        
        const selectAll = document.getElementById('selectAll');
        const allCheckboxes = document.querySelectorAll('.foq-checkbox');
        selectAll.checked = selectedFoqs.size === allCheckboxes.length && allCheckboxes.length > 0;
        selectAll.indeterminate = selectedFoqs.size > 0 && selectedFoqs.size < allCheckboxes.length;
    }

    function clearSelection() {
        selectedFoqs.clear();
        document.querySelectorAll('.foq-checkbox').forEach(checkbox => checkbox.checked = false);
        document.getElementById('selectAll').checked = false;
        document.getElementById('selectAll').indeterminate = false;
        document.getElementById('bulkActionsBar').style.display = 'none';
    }

    async function bulkAction(action) {
        if (selectedFoqs.size === 0) return;
        
        const actionText = action === 'delete' ? 'delete' : action;
        if (!confirm(`Are you sure you want to ${actionText} ${selectedFoqs.size} selected FOQs?`)) {
            return;
        }
        
        try {
            const response = await fetch(routes.bulkAction, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    action: action,
                    foq_ids: Array.from(selectedFoqs)
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

    async function exportFoqs(format) {
        try {
            const url = new URL(routes.export);
            url.searchParams.append('format', format);
            
            // Add current filters
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('search')) url.searchParams.append('search', urlParams.get('search'));
            if (urlParams.get('status')) url.searchParams.append('status', urlParams.get('status'));
            if (urlParams.get('category')) url.searchParams.append('category', urlParams.get('category'));
            if (urlParams.get('type')) url.searchParams.append('type', urlParams.get('type'));
            
            window.open(url.toString(), '_blank');
            showNotification('Export started. Download will begin shortly.', 'info');
            
        } catch (error) {
            console.error('Export error:', error);
            showNotification('Error exporting FOQs', 'error');
        }
    }
    
    // Initialize page
    document.addEventListener('DOMContentLoaded', function() {
        console.log('FOQ management page initialized');
        updateBulkActions();
        
        // Auto-refresh sync status every 30 seconds if sync is in progress
        setInterval(checkSyncStatus, 30000);
    });
    
    async function checkSyncStatus() {
        try {
            const response = await fetch('{{ route("foqs.sync-status") }}', {
                headers: {
                    'Accept': 'application/json'
                }
            });
            
            if (response.ok) {
                const result = await response.json();
                // Update UI based on sync status if needed
                console.log('Sync status:', result);
            }
        } catch (error) {
            // Silently handle sync status check errors
            console.log('Sync status check failed:', error);
        }
    }
</script>
@endpush