@extends('admin::layouts.admin')

@section('title', 'User Management')
@section('page-title', 'User Management')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-primary">User Management</h1>
        <p class="text-gray-600 mt-1">Manage system users and accounts (Total: {{ $totalUsers }})</p>
    </div>
    <div class="flex gap-3">
        <button onclick="syncFirebaseUsers()" 
                class="bg-warning text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition-colors" 
                id="syncBtn">
            <i class="fas fa-sync mr-2"></i>Sync Firebase
        </button>
        <a href="{{ route('user.create') }}" 
           class="bg-success text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors">
            <i class="fas fa-plus mr-2"></i>Add User
        </a>
        <a href="{{ route('user.statistics') }}" 
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
    <form method="GET" action="{{ route('user.index') }}" class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-64">
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" 
                   placeholder="Search users by name, email, or phone..."
                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
        </div>
        <div class="min-w-48">
            <select name="status" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Status</option>
                <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        <div class="min-w-32">
            <select name="limit" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="25" {{ ($filters['limit'] ?? 50) == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ ($filters['limit'] ?? 50) == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ ($filters['limit'] ?? 50) == 100 ? 'selected' : '' }}>100</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-search mr-2"></i>Search
            </button>
            <a href="{{ route('user.index') }}" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors">
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
                <span id="selectedCount">0</span> users selected
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

<!-- Users Table -->
<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    <div class="p-6 border-b">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold">
                <i class="fas fa-users mr-2 text-primary"></i>Users List
            </h2>
            <div class="flex gap-2">
                <button onclick="exportUsers('csv')" class="text-primary hover:bg-blue-50 px-3 py-1 rounded">
                    <i class="fas fa-file-csv mr-1"></i>CSV Export
                </button>
            </div>
        </div>
    </div>
    
    @if(count($users) > 0)
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left">
                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" 
                               class="rounded border-gray-300 text-primary focus:ring-primary">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        User Info
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Contact
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Role
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
                @foreach($users as $user)
                @php
                    // Use sanitized data from the service
                    $userId = $user['id'];
                    $userName = $user['name'];
                    $userEmail = $user['email'];
                    $userPhone = $user['phone'];
                    $userStatus = $user['status'];
                    $userRole = $user['role'];
                    
                    // Simplified date handling
                    $createdAt = null;
                    $createdDisplay = 'Unknown';
                    $createdHuman = 'Date not available';
                    
                    if (!empty($user['created_at'])) {
                        try {
                            $createdAt = \Carbon\Carbon::parse($user['created_at']);
                            $createdDisplay = $createdAt->format('M d, Y');
                            $createdHuman = $createdAt->diffForHumans();
                        } catch (\Exception $e) {
                            // Log the error but continue with defaults
                            Log::debug('Date parsing error for user', [
                                'user_id' => $userId,
                                'created_at' => $user['created_at'],
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                    
                    // Display name for delete confirmation
                    $displayName = ($userName !== 'Unknown User') ? $userName : $userEmail;
                @endphp
                <tr data-user-id="{{ $userId }}" class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <input type="checkbox" class="user-checkbox rounded border-gray-300 text-primary focus:ring-primary" 
                               value="{{ $userId }}" onchange="updateBulkActions()">
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center text-white font-medium mr-3">
                                {{ strtoupper(substr($userName, 0, 1)) }}
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">{{ $userName }}</div>
                                <div class="text-sm text-gray-500">{{ $userEmail }}</div>
                                <div class="text-xs text-gray-400">
                                    ID: {{ substr($userId, 0, 12) }}{{ strlen($userId) > 12 ? '...' : '' }}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">{{ $userEmail }}</div>
                        @if($userPhone)
                            <div class="text-sm text-gray-500">{{ $userPhone }}</div>
                        @else
                            <div class="text-sm text-gray-400">No phone</div>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col gap-1">
                            @if($userStatus === 'active')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle mr-1"></i>Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <i class="fas fa-times-circle mr-1"></i>Inactive
                                </span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ ucfirst($userRole) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        <div>{{ $createdDisplay }}</div>
                        <div class="text-xs text-gray-500">{{ $createdHuman }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('user.show', $userId) }}" 
                               class="text-primary hover:text-blue-700 p-1" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('user.edit', $userId) }}" 
                               class="text-green-600 hover:text-green-800 p-1" title="Edit User">
                                <i class="fas fa-edit"></i>
                            </a>
                            @if($userStatus === 'active')
                                <button onclick="toggleUserStatus('{{ $userId }}', 'inactive')" 
                                        class="text-yellow-600 hover:text-yellow-800 p-1" title="Deactivate User">
                                    <i class="fas fa-ban"></i>
                                </button>
                            @else
                                <button onclick="toggleUserStatus('{{ $userId }}', 'active')" 
                                        class="text-green-600 hover:text-green-800 p-1" title="Activate User">
                                    <i class="fas fa-check-circle"></i>
                                </button>
                            @endif
                            <button onclick="deleteUser('{{ $userId }}', '{{ addslashes($displayName) }}')" 
                                    class="text-red-600 hover:text-red-800 p-1" title="Delete User">
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
                Showing {{ count($users) }} of {{ $totalUsers }} users
            </div>
            <div class="text-sm text-gray-500">
                Filtered results based on current criteria
            </div>
        </div>
    </div>
    @else
    <div class="text-center py-12">
        <i class="fas fa-users text-4xl text-gray-400 mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No Users Found</h3>
        <p class="text-gray-500 mb-4">
            @if(isset($filters['search']) && $filters['search'])
                No users match your search criteria "{{ $filters['search'] }}". Try adjusting your filters.
            @elseif(isset($filters['status']) && $filters['status'])
                No users found with status "{{ $filters['status'] }}". Try different filters.
            @else
                No users found in the system. Add some users to get started.
            @endif
        </p>
        <div class="flex justify-center gap-3">
            <a href="{{ route('user.create') }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>Add First User
            </a>
            @if(!isset($filters['search']) && !isset($filters['status']))
            <button onclick="syncFirebaseUsers()" class="bg-warning text-white px-4 py-2 rounded-lg hover:bg-yellow-600">
                <i class="fas fa-sync mr-2"></i>Sync Firebase Users
            </button>
            @endif
        </div>
    </div>
    @endif
</div>

<!-- JavaScript remains the same -->
@endsection

@push('scripts')
<script>
    // User management functions
    let selectedUsers = new Set();

    // Notification system
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

    async function syncFirebaseUsers() {
        if (!confirm('This will sync users from Firebase. This may take some time. Continue?')) {
            return;
        }
        
        const btn = document.getElementById('syncBtn');
        const originalText = btn.innerHTML;
        showLoading(btn);
        
        try {
            const response = await fetch('{{ route("user.sync") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                showNotification('Users synced successfully! Refreshing page...', 'success');
                setTimeout(() => window.location.reload(), 2000);
            } else {
                showNotification('Failed to sync users: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Sync error:', error);
            showNotification('Error syncing users: Connection failed', 'error');
        } finally {
            hideLoading(btn, originalText);
        }
    }

    async function deleteUser(userId, userName) {
        if (!confirm(`Are you sure you want to delete user "${userName}"? This action cannot be undone.`)) {
            return;
        }
        
        try {
            const response = await fetch(`/user/ajax/${userId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                showNotification('User deleted successfully', 'success');
                // Remove row from table
                const row = document.querySelector(`tr[data-user-id="${userId}"]`);
                if (row) row.remove();
                
                // Update counters
                updateUserCount(-1);
            } else {
                showNotification('Failed to delete user: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Delete error:', error);
            showNotification('Error deleting user: Connection failed', 'error');
        }
    }

    async function toggleUserStatus(userId, newStatus) {
        const actionText = newStatus === 'active' ? 'activate' : 'deactivate';
        if (!confirm(`Are you sure you want to ${actionText} this user?`)) {
            return;
        }
        
        try {
            const response = await fetch(`/user/${userId}/status`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: newStatus })
            });
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                showNotification(`User ${actionText}d successfully`, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showNotification(`Failed to ${actionText} user: ` + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Toggle status error:', error);
            showNotification(`Error ${actionText}ing user: Connection failed`, 'error');
        }
    }

    function toggleSelectAll() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.user-checkbox');
        
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAll.checked;
            if (selectAll.checked) {
                selectedUsers.add(checkbox.value);
            } else {
                selectedUsers.delete(checkbox.value);
            }
        });
        
        updateBulkActions();
    }

    function updateBulkActions() {
        const checkboxes = document.querySelectorAll('.user-checkbox:checked');
        const bulkBar = document.getElementById('bulkActionsBar');
        const selectedCount = document.getElementById('selectedCount');
        
        selectedUsers.clear();
        checkboxes.forEach(checkbox => selectedUsers.add(checkbox.value));
        
        if (selectedUsers.size > 0) {
            bulkBar.style.display = 'block';
            selectedCount.textContent = selectedUsers.size;
        } else {
            bulkBar.style.display = 'none';
        }
        
        // Update select all checkbox
        const selectAll = document.getElementById('selectAll');
        const allCheckboxes = document.querySelectorAll('.user-checkbox');
        selectAll.checked = selectedUsers.size === allCheckboxes.length && allCheckboxes.length > 0;
    }

    function clearSelection() {
        selectedUsers.clear();
        document.querySelectorAll('.user-checkbox').forEach(checkbox => checkbox.checked = false);
        document.getElementById('selectAll').checked = false;
        document.getElementById('bulkActionsBar').style.display = 'none';
    }

    async function bulkAction(action) {
        if (selectedUsers.size === 0) return;
        
        const actionText = action === 'delete' ? 'delete' : action;
        if (!confirm(`Are you sure you want to ${actionText} ${selectedUsers.size} selected users?`)) {
            return;
        }
        
        try {
            const response = await fetch('{{ route("user.bulk-action") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    action: action,
                    user_ids: Array.from(selectedUsers)
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

    async function exportUsers(format) {
        try {
            const url = new URL('{{ route("user.export") }}');
            url.searchParams.append('format', format);
            
            // Add current filters
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('search')) url.searchParams.append('search', urlParams.get('search'));
            if (urlParams.get('status')) url.searchParams.append('status', urlParams.get('status'));
            if (urlParams.get('limit')) url.searchParams.append('limit', urlParams.get('limit'));
            
            window.open(url.toString(), '_blank');
            showNotification('Export started. Download will begin shortly.', 'info');
            
        } catch (error) {
            console.error('Export error:', error);
            showNotification('Error exporting users', 'error');
        }
    }

    function updateUserCount(change) {
        // Update total user count display if needed
        const totalElement = document.querySelector('p:contains("Total:")');
        if (totalElement) {
            const currentCount = parseInt(totalElement.textContent.match(/\d+/)[0]);
            totalElement.textContent = totalElement.textContent.replace(/\d+/, currentCount + change);
        }
    }
    
    // Initialize page
    document.addEventListener('DOMContentLoaded', function() {
        console.log('User management page initialized');
        
        // Update bulk actions on page load if any checkboxes are checked
        updateBulkActions();
    });
</script>
@endpush