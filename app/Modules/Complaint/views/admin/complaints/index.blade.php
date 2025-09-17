{{-- resources/views/complaint/admin/complaints/index.blade.php --}}

@extends('admin::layouts.admin')

@section('title', 'Complaint Management')
@section('page-title', 'Complaint Management')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-primary">Complaints</h1>
        <p class="text-gray-600 mt-1">Manage customer complaints and support requests (Total: {{ $totalComplaints }})</p>
    </div>
    <div class="flex gap-3">
        <button onclick="refreshComplaints()" 
                class="bg-warning text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition-colors" 
                id="refreshBtn">
            <i class="fas fa-sync mr-2"></i>Refresh Data
        </button>
    
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

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center">
            <div class="p-2 rounded-full bg-yellow-100">
                <i class="fas fa-clock text-yellow-600"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Pending</p>
                <p class="text-2xl font-semibold text-gray-900">{{ $statistics['pending_complaints'] ?? 0 }}</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center">
            <div class="p-2 rounded-full bg-blue-100">
                <i class="fas fa-spinner text-blue-600"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">In Progress</p>
                <p class="text-2xl font-semibold text-gray-900">{{ $statistics['in_progress_complaints'] ?? 0 }}</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center">
            <div class="p-2 rounded-full bg-green-100">
                <i class="fas fa-check text-green-600"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Resolved</p>
                <p class="text-2xl font-semibold text-gray-900">{{ $statistics['resolved_complaints'] ?? 0 }}</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center">
            <div class="p-2 rounded-full bg-red-100">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Urgent</p>
                <p class="text-2xl font-semibold text-gray-900">{{ $statistics['urgent_complaints'] ?? 0 }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters and Search -->
<div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
    <form method="GET" action="{{ route('complaints.index') }}" class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-64">
            <input type="text" name="search" value="{{ $search ?? '' }}" 
                   placeholder="Search complaints by title, description, or ID"
                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
        </div>
        <div class="min-w-48">
            <select name="status" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Status</option>
                @foreach(\App\Modules\Complaint\Models\Complaint::getStatuses() as $key => $label)
                    <option value="{{ $key }}" {{ ($status ?? '') === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="min-w-48">
            <select name="priority" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Priorities</option>
                @foreach(\App\Modules\Complaint\Models\Complaint::getPriorities() as $key => $label)
                    <option value="{{ $key }}" {{ ($priority ?? '') === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="min-w-48">
            <select name="order_type" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Types</option>
                @foreach(\App\Modules\Complaint\Models\Complaint::getOrderTypes() as $key => $label)
                    <option value="{{ $key }}" {{ ($order_type ?? '') === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="min-w-32">
            <select name="limit" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="25" {{ ($limit ?? 25) == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ ($limit ?? 25) == 50 ? 'selected' : '' }}>50</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-search mr-2"></i>Filter
            </button>
            <a href="{{ route('complaints.index') }}" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors">
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
                <span id="selectedCount">0</span> complaints selected
            </span>
            <div class="flex gap-2">
                <button onclick="bulkAction('in_progress')" class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">
                    <i class="fas fa-spinner mr-1"></i>In Progress
                </button>
                <button onclick="bulkAction('resolve')" class="bg-success text-white px-3 py-1 rounded text-sm hover:bg-green-600">
                    <i class="fas fa-check mr-1"></i>Resolve
                </button>
                <button onclick="bulkAction('close')" class="bg-gray-600 text-white px-3 py-1 rounded text-sm hover:bg-gray-700">
                    <i class="fas fa-times mr-1"></i>Close
                </button>
            </div>
        </div>
        <button onclick="clearSelection()" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>

<!-- Complaints Table -->
<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    @if(count($complaints) > 0)
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left">
                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" 
                               class="rounded border-gray-300 text-primary focus:ring-primary">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Order Type
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Title
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Complaint By
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Priority
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Created
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($complaints as $complaint)
                <tr data-complaint-id="{{ $complaint->id }}" class="hover:bg-gray-50 {{ $complaint->is_overdue ? 'bg-red-50' : '' }}">
                    <td class="px-6 py-4">
                        <input type="checkbox" class="complaint-checkbox rounded border-gray-300 text-primary focus:ring-primary" 
                               value="{{ $complaint->id }}" onchange="updateBulkActions()">
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            {{ $complaint->order_type === 'ride' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                            <i class="fas {{ $complaint->order_type === 'ride' ? 'fa-car' : 'fa-box' }} mr-1"></i>
                            {{ ucfirst($complaint->order_type) }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ Str::limit($complaint->title, 40) }}</div>
                        <div class="text-sm text-gray-500">ID: {{ $complaint->id }}</div>
                        @if($complaint->is_overdue)
                            <div class="text-xs text-red-600 font-medium">
                                <i class="fas fa-exclamation-triangle mr-1"></i>Overdue
                            </div>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <div class="text-gray-900">{{ $complaint->complaint_by ?? 'N/A' }}</div>
                        @if($complaint->user_name)
                            <div class="text-gray-500">User: {{ $complaint->user_name }}</div>
                        @endif
                        @if($complaint->driver_name)
                            <div class="text-gray-500">Driver: {{ $complaint->driver_name }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $complaint->priority_badge }}">
                            @if($complaint->priority === 'urgent')
                                <i class="fas fa-fire mr-1"></i>
                            @elseif($complaint->priority === 'high')
                                <i class="fas fa-exclamation mr-1"></i>
                            @elseif($complaint->priority === 'medium')
                                <i class="fas fa-minus mr-1"></i>
                            @else
                                <i class="fas fa-arrow-down mr-1"></i>
                            @endif
                            {{ ucfirst($complaint->priority) }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $complaint->status_badge }}">
                            @if($complaint->status === 'pending')
                                <i class="fas fa-clock mr-1"></i>
                            @elseif($complaint->status === 'in_progress')
                                <i class="fas fa-spinner mr-1"></i>
                            @elseif($complaint->status === 'resolved')
                                <i class="fas fa-check mr-1"></i>
                            @else
                                <i class="fas fa-times mr-1"></i>
                            @endif
                            {{ ucfirst(str_replace('_', ' ', $complaint->status)) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ $complaint->created_at ? $complaint->created_at->format('M d, Y') : 'N/A' }}
                        <div class="text-xs text-gray-500">
                            {{ $complaint->time_ago }}
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('complaints.show', $complaint->id) }}" 
                               class="text-primary hover:text-blue-700 p-1" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" class="text-gray-600 hover:text-gray-800 p-1" title="Change Status">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <div x-show="open" @click.away="open = false" 
                                     class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border">
                                    <div class="py-1">
                                        <button onclick="updateComplaintStatus('{{ $complaint->id }}', 'pending')" 
                                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <i class="fas fa-clock mr-2"></i>Set Pending
                                        </button>
                                        <button onclick="updateComplaintStatus('{{ $complaint->id }}', 'in_progress')" 
                                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <i class="fas fa-spinner mr-2"></i>Set In Progress
                                        </button>
                                        <button onclick="updateComplaintStatus('{{ $complaint->id }}', 'resolved')" 
                                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <i class="fas fa-check mr-2"></i>Mark Resolved
                                        </button>
                                        <button onclick="updateComplaintStatus('{{ $complaint->id }}', 'closed')" 
                                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <i class="fas fa-times mr-2"></i>Close
                                        </button>
                                    </div>
                                </div>
                            </div>
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
                Showing {{ count($complaints) }} of {{ $totalComplaints }} complaints
                <div class="text-xs text-gray-500 mt-1">
                    Rows per page: {{ $limit ?? 25 }}
                </div>
            </div>
            <div class="flex gap-2">
                <button onclick="exportComplaints('csv')" class="text-primary hover:bg-blue-50 px-3 py-1 rounded">
                    <i class="fas fa-file-csv mr-1"></i>Export CSV
                </button>
            </div>
        </div>
    </div>
    @else
    <div class="text-center py-12">
        <i class="fas fa-exclamation-triangle text-4xl text-gray-400 mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No Complaints Found</h3>
        <p class="text-gray-500 mb-4">
            @if(isset($search) && $search)
                No complaints match your search criteria "{{ $search }}". Try adjusting your filters.
            @else
                No complaints found in Firestore. This could mean there are no complaints or there's a connection issue.
            @endif
        </p>
        <button onclick="refreshComplaints()" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            <i class="fas fa-sync mr-2"></i>Refresh Data
        </button>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
    let selectedComplaints = new Set();

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

   async function refreshComplaints() {
    const btn = document.getElementById('refreshBtn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Refreshing...';
    
    try {
        // Fix the URL to match your route structure
        const response = await fetch('/complaint/complaints/refresh', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });
        
        if (response.ok) {
            showNotification('Data refreshed successfully!', 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showNotification('Failed to refresh data', 'error');
        }
    } catch (error) {
        console.error('Refresh error:', error);
        showNotification('Error refreshing data: Connection failed', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}
async function updateComplaintStatus(complaintId, status) {
    console.log('=== DEBUG updateComplaintStatus ===');
    console.log('Raw complaintId:', complaintId);
    console.log('Type of complaintId:', typeof complaintId);
    console.log('Status:', status);
    
    // Check if complaintId is valid
    if (!complaintId || complaintId === '0' || complaintId === '' || complaintId === 0) {
        console.error('Invalid complaint ID detected:', complaintId);
        showNotification('Error: Invalid complaint ID - ' + complaintId, 'error');
        return;
    }
    
    try {
        const url = `/complaint/complaints/${complaintId}/status`;
        console.log('Making request to URL:', url);
        
        const response = await fetch(url, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ status: status })
        });
        
        console.log('Response status:', response.status);
        
        if (response.ok) {
            const result = await response.json();
            console.log('Success result:', result);
            showNotification(`Complaint status updated to ${status.replace('_', ' ')}`, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            const result = await response.json();
            console.error('Error result:', result);
            showNotification('Failed to update status: ' + (result.message || 'Unknown error'), 'error');
        }
    } catch (error) {
        console.error('Update status error:', error);
        showNotification('Error updating status: Connection failed', 'error');
    }
}


    function toggleSelectAll() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.complaint-checkbox');
        
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAll.checked;
            if (selectAll.checked) {
                selectedComplaints.add(checkbox.value);
            } else {
                selectedComplaints.delete(checkbox.value);
            }
        });
        
        updateBulkActions();
    }

   async function bulkAction(action) {
    if (selectedComplaints.size === 0) return;
    
    const actionText = action.replace('_', ' ');
    if (!confirm(`Are you sure you want to ${actionText} ${selectedComplaints.size} selected complaints?`)) {
        return;
    }
    
    try {
        // Fix the URL to match your route structure
        const response = await fetch('/complaint/complaints/bulk-action', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                action: action,
                complaint_ids: Array.from(selectedComplaints)
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
    function clearSelection() {
        selectedComplaints.clear();
        document.querySelectorAll('.complaint-checkbox').forEach(checkbox => checkbox.checked = false);
        document.getElementById('selectAll').checked = false;
        document.getElementById('bulkActionsBar').style.display = 'none';
    }

    async function bulkAction(action) {
        if (selectedComplaints.size === 0) return;
        
        const actionText = action.replace('_', ' ');
        if (!confirm(`Are you sure you want to ${actionText} ${selectedComplaints.size} selected complaints?`)) {
            return;
        }
        
        try {
            const response = await fetch('{{ route("complaints.bulk-action") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    action: action,
                    complaint_ids: Array.from(selectedComplaints)
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

   async function exportComplaints(format) {
    try {
        // Fix the URL to match your route structure
        const url = new URL('/complaint/complaints/export/csv', window.location.origin);
        url.searchParams.append('format', format);
        
        // Add current filters
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('search')) url.searchParams.append('search', urlParams.get('search'));
        if (urlParams.get('status')) url.searchParams.append('status', urlParams.get('status'));
        if (urlParams.get('priority')) url.searchParams.append('priority', urlParams.get('priority'));
        if (urlParams.get('order_type')) url.searchParams.append('order_type', urlParams.get('order_type'));
        
        window.open(url.toString(), '_blank');
        showNotification('Export started. Download will begin shortly.', 'info');
        
    } catch (error) {
        console.error('Export error:', error);
        showNotification('Error exporting complaints', 'error');
    }
}

function updateBulkActions() {
    const checkboxes = document.querySelectorAll('.complaint-checkbox:checked');
    const bulkBar = document.getElementById('bulkActionsBar');
    const selectedCount = document.getElementById('selectedCount');
    
    selectedComplaints.clear();
    checkboxes.forEach(checkbox => selectedComplaints.add(checkbox.value));
    
    if (selectedComplaints.size > 0) {
        bulkBar.style.display = 'block';
        selectedCount.textContent = selectedComplaints.size;
    } else {
        bulkBar.style.display = 'none';
    }
    
    const selectAll = document.getElementById('selectAll');
    const allCheckboxes = document.querySelectorAll('.complaint-checkbox');
    selectAll.checked = selectedComplaints.size === allCheckboxes.length && allCheckboxes.length > 0;
}
    
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Complaint management page initialized');
        updateBulkActions();
    });
</script>
@endpush