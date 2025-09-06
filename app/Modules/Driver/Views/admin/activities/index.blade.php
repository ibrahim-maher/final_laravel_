@extends('admin::layouts.admin')

@section('title', 'Activity Management')
@section('page-title', 'Activity Management')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-primary">Activity Management</h1>
        <p class="text-gray-600 mt-1">Monitor driver activities and system events (Total: {{ $totalActivities }})</p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('admin.activities.create') }}" 
           class="bg-success text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors">
            <i class="fas fa-plus mr-2"></i>Create Activity
        </a>
        <a href="{{ route('admin.activities.statistics') }}" 
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
                <i class="fas fa-history text-xl"></i>
            </div>
        </div>
        @endforeach
    </div>
    
    <!-- Pagination Info -->
    <div class="px-6 py-3 border-t bg-gray-50">
        <div class="flex justify-between items-center">
            <div class="text-sm text-gray-700">
                Showing {{ count($activities) }} of {{ $totalActivities }} activities
            </div>
            <div class="text-sm text-gray-500">
                Filtered results based on current criteria
            </div>
        </div>
    </div>
    @else
    <div class="text-center py-12">
        <i class="fas fa-history text-4xl text-gray-400 mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No Activities Found</h3>
        <p class="text-gray-500 mb-4">
            @if(isset($search) && $search)
                No activities match your search criteria "{{ $search }}". Try adjusting your filters.
            @else
                No activities found in the system. Create some activities to get started.
            @endif
        </p>
        <div class="flex justify-center gap-3">
            <a href="{{ route('admin.activities.create') }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>Create First Activity
            </a>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    let selectedActivities = new Set();

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

    async function markAsRead(activityId) {
        try {
            const response = await fetch(`{{ route('admin.activities.mark-as-read', '') }}/${activityId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                showNotification('Activity marked as read', 'success');
                const activityElement = document.querySelector(`div[data-activity-id="${activityId}"]`);
                if (activityElement) {
                    activityElement.classList.remove('bg-blue-50');
                    const title = activityElement.querySelector('h3');
                    if (title) title.classList.remove('font-semibold');
                    // Remove the mark as read button
                    const markReadBtn = activityElement.querySelector('button[onclick*="markAsRead"]');
                    if (markReadBtn) markReadBtn.remove();
                }
            } else {
                showNotification('Failed to mark activity as read: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Mark as read error:', error);
            showNotification('Error marking activity as read: Connection failed', 'error');
        }
    }

    async function archiveActivity(activityId) {
        if (!confirm('Are you sure you want to archive this activity?')) {
            return;
        }
        
        try {
            const response = await fetch(`{{ route('admin.activities.archive', '') }}/${activityId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                showNotification('Activity archived successfully', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showNotification('Failed to archive activity: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Archive error:', error);
            showNotification('Error archiving activity: Connection failed', 'error');
        }
    }

    async function deleteActivity(activityId, activityTitle) {
        if (!confirm(`Are you sure you want to delete activity "${activityTitle}"? This action cannot be undone.`)) {
            return;
        }
        
        try {
            const response = await fetch(`{{ route('admin.activities.destroy', '') }}/${activityId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                showNotification('Activity deleted successfully', 'success');
                const activityElement = document.querySelector(`div[data-activity-id="${activityId}"]`);
                if (activityElement) activityElement.remove();
            } else {
                showNotification('Failed to delete activity: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Delete error:', error);
            showNotification('Error deleting activity: Connection failed', 'error');
        }
    }

    function updateBulkActions() {
        const checkboxes = document.querySelectorAll('.activity-checkbox:checked');
        const bulkBar = document.getElementById('bulkActionsBar');
        const selectedCount = document.getElementById('selectedCount');
        
        selectedActivities.clear();
        checkboxes.forEach(checkbox => selectedActivities.add(checkbox.value));
        
        if (selectedActivities.size > 0) {
            bulkBar.style.display = 'block';
            selectedCount.textContent = selectedActivities.size;
        } else {
            bulkBar.style.display = 'none';
        }
    }

    function clearSelection() {
        selectedActivities.clear();
        document.querySelectorAll('.activity-checkbox').forEach(checkbox => checkbox.checked = false);
        document.getElementById('bulkActionsBar').style.display = 'none';
    }

    async function bulkAction(action) {
        if (selectedActivities.size === 0) return;
        
        const actionText = action === 'mark_read' ? 'mark as read' : action === 'archive' ? 'archive' : 'delete';
        if (!confirm(`Are you sure you want to ${actionText} ${selectedActivities.size} selected activities?`)) {
            return;
        }
        
        try {
            const response = await fetch('{{ route("admin.activities.bulk-action") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    action: action,
                    activity_ids: Array.from(selectedActivities)
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

    async function exportActivities(format) {
        try {
            const url = new URL('{{ route("admin.activities.export") }}');
            url.searchParams.append('format', format);
            
            // Add current filters
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('search')) url.searchParams.append('search', urlParams.get('search'));
            if (urlParams.get('activity_type')) url.searchParams.append('activity_type', urlParams.get('activity_type'));
            if (urlParams.get('activity_category')) url.searchParams.append('activity_category', urlParams.get('activity_category'));
            if (urlParams.get('priority')) url.searchParams.append('priority', urlParams.get('priority'));
            if (urlParams.get('driver_firebase_uid')) url.searchParams.append('driver_firebase_uid', urlParams.get('driver_firebase_uid'));
            if (urlParams.get('date_from')) url.searchParams.append('date_from', urlParams.get('date_from'));
            if (urlParams.get('date_to')) url.searchParams.append('date_to', urlParams.get('date_to'));
            
            window.open(url.toString(), '_blank');
            showNotification('Export started. Download will begin shortly.', 'info');
            
        } catch (error) {
            console.error('Export error:', error);
            showNotification('Error exporting activities', 'error');
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Activity management page initialized');
        updateBulkActions();
    });
</script>
@endpush>
            <div>
                <p class="text-sm font-medium text-gray-600">Total Activities</p>
                <p class="text-2xl font-bold text-gray-900">{{ $totalActivities }}</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                <i class="fas fa-user-plus text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Registrations</p>
                <p class="text-2xl font-bold text-gray-900">
                    {{ $activities->where('activity_type', 'driver_registration')->count() }}
                </p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                <i class="fas fa-shield-check text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Verifications</p>
                <p class="text-2xl font-bold text-gray-900">
                    {{ $activities->where('activity_type', 'verification')->count() }}
                </p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                <i class="fas fa-exclamation-triangle text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">High Priority</p>
                <p class="text-2xl font-bold text-gray-900">
                    {{ $activities->where('priority', 'high')->count() }}
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Filters and Search -->
<div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
    <form method="GET" action="{{ route('admin.activities.index') }}" class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-64">
            <input type="text" name="search" value="{{ $search ?? '' }}" 
                   placeholder="Search activities by title, description, or driver..."
                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
        </div>
        <div class="min-w-48">
            <select name="activity_type" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Activity Types</option>
                @foreach($activityTypes as $key => $label)
                    <option value="{{ $key }}" {{ ($activity_type ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-48">
            <select name="activity_category" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Categories</option>
                @foreach($categories as $key => $label)
                    <option value="{{ $key }}" {{ ($activity_category ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-48">
            <select name="priority" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Priorities</option>
                @foreach($priorities as $key => $label)
                    <option value="{{ $key }}" {{ ($priority ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-48">
            <select name="driver_firebase_uid" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Drivers</option>
                @foreach($drivers as $driver)
                    <option value="{{ $driver['firebase_uid'] }}" {{ ($driver_firebase_uid ?? '') === $driver['firebase_uid'] ? 'selected' : '' }}>
                        {{ $driver['name'] }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="min-w-40">
            <input type="date" name="date_from" value="{{ $date_from ?? '' }}" 
                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
        </div>
        <div class="min-w-40">
            <input type="date" name="date_to" value="{{ $date_to ?? '' }}" 
                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
        </div>
        <div class="min-w-32">
            <select name="limit" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="50" {{ ($limit ?? 100) == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ ($limit ?? 100) == 100 ? 'selected' : '' }}>100</option>
                <option value="200" {{ ($limit ?? 100) == 200 ? 'selected' : '' }}>200</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-search mr-2"></i>Search
            </button>
            <a href="{{ route('admin.activities.index') }}" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors">
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
                <span id="selectedCount">0</span> activities selected
            </span>
            <div class="flex gap-2">
                <button onclick="bulkAction('mark_read')" class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">
                    <i class="fas fa-eye mr-1"></i>Mark Read
                </button>
                <button onclick="bulkAction('archive')" class="bg-yellow-600 text-white px-3 py-1 rounded text-sm hover:bg-yellow-700">
                    <i class="fas fa-archive mr-1"></i>Archive
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

<!-- Activities List -->
<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    <div class="p-6 border-b">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold">
                <i class="fas fa-history mr-2 text-primary"></i>Activities List
            </h2>
            <div class="flex gap-2">
                <button onclick="exportActivities('csv')" class="text-primary hover:bg-blue-50 px-3 py-1 rounded">
                    <i class="fas fa-file-csv mr-1"></i>CSV Export
                </button>
            </div>
        </div>
    </div>
    
    @if(count($activities) > 0)
    <div class="divide-y divide-gray-200">
        @foreach($activities as $activity)
        @php
            $activityId = $activity['id'];
            $activityType = $activity['activity_type'] ?? 'general';
            $activityCategory = $activity['activity_category'] ?? 'system';
            $priority = $activity['priority'] ?? 'low';
            $driverName = $activity['driver_name'] ?? 'System';
            $title = $activity['title'] ?? 'Activity';
            $description = $activity['description'] ?? '';
            
            $createdAt = null;
            $createdDisplay = 'Unknown';
            $createdHuman = 'Date not available';
            
            if (!empty($activity['created_at'])) {
                try {
                    $createdAt = \Carbon\Carbon::parse($activity['created_at']);
                    $createdDisplay = $createdAt->format('M d, Y g:i A');
                    $createdHuman = $createdAt->diffForHumans();
                } catch (\Exception $e) {
                    // Handle date parsing error
                }
            }
            
            $isRead = $activity['is_read'] ?? false;
            $isArchived = $activity['is_archived'] ?? false;
        @endphp
        <div data-activity-id="{{ $activityId }}" class="p-6 hover:bg-gray-50 {{ $isRead ? '' : 'bg-blue-50' }}">
            <div class="flex items-start space-x-4">
                <div class="flex-shrink-0">
                    <input type="checkbox" class="activity-checkbox rounded border-gray-300 text-primary focus:ring-primary mt-1" 
                           value="{{ $activityId }}" onchange="updateBulkActions()">
                </div>
                <div class="flex-shrink-0">
                    @switch($activityType)
                        @case('driver_registration')
                            <div class="p-2 rounded-full bg-green-100">
                                <i class="fas fa-user-plus text-green-600"></i>
                            </div>
                            @break
                        @case('document_upload')
                            <div class="p-2 rounded-full bg-blue-100">
                                <i class="fas fa-file-upload text-blue-600"></i>
                            </div>
                            @break
                        @case('ride_completed')
                            <div class="p-2 rounded-full bg-green-100">
                                <i class="fas fa-check-circle text-green-600"></i>
                            </div>
                            @break
                        @case('verification')
                            <div class="p-2 rounded-full bg-purple-100">
                                <i class="fas fa-shield-check text-purple-600"></i>
                            </div>
                            @break
                        @case('vehicle_registration')
                            <div class="p-2 rounded-full bg-blue-100">
                                <i class="fas fa-car text-blue-600"></i>
                            </div>
                            @break
                        @case('payment')
                            <div class="p-2 rounded-full bg-yellow-100">
                                <i class="fas fa-dollar-sign text-yellow-600"></i>
                            </div>
                            @break
                        @case('support')
                            <div class="p-2 rounded-full bg-orange-100">
                                <i class="fas fa-life-ring text-orange-600"></i>
                            </div>
                            @break
                        @default
                            <div class="p-2 rounded-full bg-gray-100">
                                <i class="fas fa-info-circle text-gray-600"></i>
                            </div>
                    @endswitch
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center space-x-2 mb-1">
                        <h3 class="text-sm font-medium text-gray-900 {{ $isRead ? '' : 'font-semibold' }}">
                            {{ $title }}
                        </h3>
                        
                        <!-- Priority Badge -->
                        @if($priority === 'high')
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                <i class="fas fa-exclamation-triangle mr-1"></i>High
                            </span>
                        @elseif($priority === 'medium')
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                <i class="fas fa-exclamation-circle mr-1"></i>Medium
                            </span>
                        @endif
                        
                        <!-- Category Badge -->
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            {{ ucfirst($activityCategory) }}
                        </span>
                        
                        @if($isArchived)
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                <i class="fas fa-archive mr-1"></i>Archived
                            </span>
                        @endif
                    </div>
                    
                    @if($description)
                        <p class="text-sm text-gray-600 mb-2">{{ $description }}</p>
                    @endif
                    
                    <div class="flex items-center space-x-4 text-xs text-gray-500">
                        <span>
                            <i class="fas fa-user mr-1"></i>{{ $driverName }}
                        </span>
                        <span>
                            <i class="fas fa-clock mr-1"></i>{{ $createdHuman }}
                        </span>
                        @if(!empty($activity['location_address']))
                            <span>
                                <i class="fas fa-map-marker-alt mr-1"></i>{{ $activity['location_address'] }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="flex-shrink-0 flex items-center space-x-2">
                    <a href="{{ route('admin.activities.show', $activityId) }}" 
                       class="text-primary hover:text-blue-700 p-1" title="View Details">
                        <i class="fas fa-eye"></i>
                    </a>
                    @if(!$isRead)
                        <button onclick="markAsRead('{{ $activityId }}')" 
                                class="text-blue-600 hover:text-blue-800 p-1" title="Mark as Read">
                            <i class="fas fa-eye"></i>
                        </button>
                    @endif
                    @if(!$isArchived)
                        <button onclick="archiveActivity('{{ $activityId }}')" 
                                class="text-yellow-600 hover:text-yellow-800 p-1" title="Archive">
                            <i class="fas fa-archive"></i>
                        </button>
                    @endif
                    <a href="{{ route('admin.activities.edit', $activityId) }}" 
                       class="text-green-600 hover:text-green-800 p-1" title="Edit Activity">
                        <i class="fas fa-edit"></i>
                    </a>
                    <button onclick="deleteActivity('{{ $activityId }}', '{{ addslashes($title) }}')" 
                            class="text-red-600 hover:text-red-800 p-1" title="Delete Activity">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
        </div>
        