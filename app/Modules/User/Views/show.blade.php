@extends('admin::layouts.admin')

@section('title', 'User Details')
@section('page-title', 'User Details')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-primary">User Details</h1>
        <p class="text-gray-600 mt-1">View complete user information and account history</p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('user.edit', $user['id']) }}" 
           class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
            <i class="fas fa-edit mr-2"></i>Edit User
        </a>
        @if(($user['status'] ?? 'active') === 'active')
            <button onclick="toggleUserStatus('{{ $user['id'] }}', 'inactive')" 
                    class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-colors">
                <i class="fas fa-ban mr-2"></i>Deactivate
            </button>
        @else
            <button onclick="toggleUserStatus('{{ $user['id'] }}', 'active')" 
                    class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                <i class="fas fa-check-circle mr-2"></i>Activate
            </button>
        @endif
        <button onclick="deleteUser('{{ $user['id'] }}', '{{ $user['name'] ?? $user['email'] }}')" 
                class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
            <i class="fas fa-trash mr-2"></i>Delete
        </button>
        <a href="{{ route('user.index') }}" 
           class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back to Users
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

<!-- User Profile Card -->
<div class="bg-white rounded-lg shadow-sm border overflow-hidden mb-6">
    <div class="p-6 border-b bg-gradient-to-r from-primary to-blue-600">
        <div class="flex items-center gap-6">
            <div class="w-24 h-24 bg-white rounded-full flex items-center justify-center text-primary text-4xl font-bold shadow-lg">
                {{ strtoupper(substr($user['name'] ?? 'U', 0, 1)) }}
            </div>
            <div class="text-white">
                <h2 class="text-3xl font-bold">{{ $user['name'] ?? 'No Name' }}</h2>
                <p class="text-blue-100 text-lg">{{ $user['email'] ?? 'No Email' }}</p>
                <div class="flex gap-3 mt-3">
                    @if(($user['status'] ?? 'active') === 'active')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            <i class="fas fa-check-circle mr-1"></i>Active
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                            <i class="fas fa-times-circle mr-1"></i>Inactive
                        </span>
                    @endif
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                        <i class="fas fa-user-tag mr-1"></i>{{ ucfirst($user['role'] ?? 'user') }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- User Information -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
            <div class="p-6 border-b">
                <h3 class="text-xl font-semibold">
                    <i class="fas fa-user mr-2 text-primary"></i>Personal Information
                </h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-medium text-gray-900 mb-2">
                            <i class="fas fa-id-card mr-2 text-gray-400"></i>Full Name
                        </h4>
                        <p class="text-gray-700 bg-gray-50 px-3 py-2 rounded">
                            {{ $user['name'] ?? 'Not provided' }}
                        </p>
                    </div>
                    
                    <div>
                        <h4 class="font-medium text-gray-900 mb-2">
                            <i class="fas fa-envelope mr-2 text-gray-400"></i>Email Address
                        </h4>
                        <p class="text-gray-700 bg-gray-50 px-3 py-2 rounded">
                            {{ $user['email'] ?? 'Not provided' }}
                        </p>
                    </div>

                    <div>
                        <h4 class="font-medium text-gray-900 mb-2">
                            <i class="fas fa-phone mr-2 text-gray-400"></i>Phone Number
                        </h4>
                        <p class="text-gray-700 bg-gray-50 px-3 py-2 rounded">
                            {{ $user['phone'] ?? 'Not provided' }}
                        </p>
                    </div>

                    <div>
                        <h4 class="font-medium text-gray-900 mb-2">
                            <i class="fas fa-toggle-on mr-2 text-gray-400"></i>Account Status
                        </h4>
                        <div class="bg-gray-50 px-3 py-2 rounded">
                            @if(($user['status'] ?? 'active') === 'active')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle mr-1"></i>Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <i class="fas fa-times-circle mr-1"></i>Inactive
                                </span>
                            @endif
                        </div>
                    </div>

                    <div>
                        <h4 class="font-medium text-gray-900 mb-2">
                            <i class="fas fa-user-tag mr-2 text-gray-400"></i>User Role
                        </h4>
                        <div class="bg-gray-50 px-3 py-2 rounded">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ ucfirst($user['role'] ?? 'user') }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <h4 class="font-medium text-gray-900 mb-2">
                            <i class="fas fa-fingerprint mr-2 text-gray-400"></i>User ID
                        </h4>
                        <p class="text-gray-700 bg-gray-50 px-3 py-2 rounded font-mono text-sm">
                            {{ $user['id'] ?? 'Unknown' }}
                        </p>
                    </div>
                </div>

                @if(isset($user['address']) && $user['address'])
                <div class="mt-6">
                    <h4 class="font-medium text-gray-900 mb-2">
                        <i class="fas fa-map-marker-alt mr-2 text-gray-400"></i>Address
                    </h4>
                    <p class="text-gray-700 bg-gray-50 px-3 py-2 rounded">
                        {{ $user['address'] }}
                    </p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Account Statistics & Timeline -->
    <div class="space-y-6">
        <!-- Quick Stats -->
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
            <div class="p-6 border-b">
                <h3 class="text-lg font-semibold">
                    <i class="fas fa-chart-line mr-2 text-primary"></i>Quick Stats
                </h3>
            </div>
            <div class="p-6 space-y-4">
                @php
                    $createdAt = null;
                    $updatedAt = null;
                    
                    // Handle created_at
                    if (isset($user['created_at'])) {
                        if (is_array($user['created_at'])) {
                            if (isset($user['created_at']['_seconds'])) {
                                $createdAt = \Carbon\Carbon::createFromTimestamp($user['created_at']['_seconds']);
                            } elseif (isset($user['created_at']['seconds'])) {
                                $createdAt = \Carbon\Carbon::createFromTimestamp($user['created_at']['seconds']);
                            }
                        } elseif (is_string($user['created_at']) || is_numeric($user['created_at'])) {
                            try {
                                $createdAt = \Carbon\Carbon::parse($user['created_at']);
                            } catch (\Exception $e) {
                                $createdAt = null;
                            }
                        }
                    }
                    
                    // Handle updated_at
                    if (isset($user['updated_at'])) {
                        if (is_array($user['updated_at'])) {
                            if (isset($user['updated_at']['_seconds'])) {
                                $updatedAt = \Carbon\Carbon::createFromTimestamp($user['updated_at']['_seconds']);
                            } elseif (isset($user['updated_at']['seconds'])) {
                                $updatedAt = \Carbon\Carbon::createFromTimestamp($user['updated_at']['seconds']);
                            }
                        } elseif (is_string($user['updated_at']) || is_numeric($user['updated_at'])) {
                            try {
                                $updatedAt = \Carbon\Carbon::parse($user['updated_at']);
                            } catch (\Exception $e) {
                                $updatedAt = null;
                            }
                        }
                    }
                    
                    $accountAge = $createdAt ? $createdAt->diffInDays(now()) : null;
                @endphp

                <div class="flex items-center justify-between p-3 bg-blue-50 rounded">
                    <div>
                        <p class="text-sm text-gray-600">Account Age</p>
                        <p class="font-semibold text-blue-600">
                            {{ $accountAge ? $accountAge . ' days' : 'Unknown' }}
                        </p>
                    </div>
                    <i class="fas fa-calendar-alt text-blue-400"></i>
                </div>

                <div class="flex items-center justify-between p-3 bg-green-50 rounded">
                    <div>
                        <p class="text-sm text-gray-600">Account Type</p>
                        <p class="font-semibold text-green-600">
                            {{ ucfirst($user['role'] ?? 'Standard') }} User
                        </p>
                    </div>
                    <i class="fas fa-crown text-green-400"></i>
                </div>

                <div class="flex items-center justify-between p-3 bg-purple-50 rounded">
                    <div>
                        <p class="text-sm text-gray-600">Data Source</p>
                        <p class="font-semibold text-purple-600">
                            {{ isset($user['firebase_uid']) ? 'Firebase' : 'Manual' }}
                        </p>
                    </div>
                    <i class="fas fa-database text-purple-400"></i>
                </div>
            </div>
        </div>

        <!-- Account Timeline -->
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
            <div class="p-6 border-b">
                <h3 class="text-lg font-semibold">
                    <i class="fas fa-history mr-2 text-primary"></i>Account Timeline
                </h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @if($createdAt)
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-user-plus text-green-600 text-xs"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Account Created</p>
                            <p class="text-sm text-gray-600">{{ $createdAt->format('M d, Y h:i A') }}</p>
                            <p class="text-xs text-gray-500">{{ $createdAt->diffForHumans() }}</p>
                        </div>
                    </div>
                    @endif

                    @if($updatedAt && $createdAt && !$updatedAt->equalTo($createdAt))
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-edit text-blue-600 text-xs"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Last Updated</p>
                            <p class="text-sm text-gray-600">{{ $updatedAt->format('M d, Y h:i A') }}</p>
                            <p class="text-xs text-gray-500">{{ $updatedAt->diffForHumans() }}</p>
                        </div>
                    </div>
                    @endif

                    @if(($user['status'] ?? 'active') === 'active')
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-check-circle text-green-600 text-xs"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Account Active</p>
                            <p class="text-sm text-gray-600">User can access the system</p>
                        </div>
                    </div>
                    @else
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-times-circle text-red-600 text-xs"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Account Inactive</p>
                            <p class="text-sm text-gray-600">User access is restricted</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Actions Card -->
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
            <div class="p-6 border-b">
                <h3 class="text-lg font-semibold">
                    <i class="fas fa-cogs mr-2 text-primary"></i>Quick Actions
                </h3>
            </div>
            <div class="p-6 space-y-3">
                <a href="{{ route('user.edit', $user['id']) }}" 
                   class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors flex items-center justify-center">
                    <i class="fas fa-edit mr-2"></i>Edit User
                </a>
                
                @if(($user['status'] ?? 'active') === 'active')
                    <button onclick="toggleUserStatus('{{ $user['id'] }}', 'inactive')" 
                            class="w-full bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-ban mr-2"></i>Deactivate Account
                    </button>
                @else
                    <button onclick="toggleUserStatus('{{ $user['id'] }}', 'active')" 
                            class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-check-circle mr-2"></i>Activate Account
                    </button>
                @endif
                
                <button onclick="deleteUser('{{ $user['id'] }}', '{{ $user['name'] ?? $user['email'] }}')" 
                        class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors flex items-center justify-center">
                    <i class="fas fa-trash mr-2"></i>Delete User
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Additional Information -->
@if(isset($user['created_by']) || isset($user['updated_by']))
<div class="bg-white rounded-lg shadow-sm border mt-6">
    <div class="p-6 border-b">
        <h3 class="text-lg font-semibold">
            <i class="fas fa-user-friends mr-2 text-primary"></i>Admin Information
        </h3>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @if(isset($user['created_by']))
            <div>
                <h4 class="font-medium text-gray-900 mb-2">Created By</h4>
                <p class="text-gray-700 bg-gray-50 px-3 py-2 rounded">
                    {{ $user['created_by'] }}
                </p>
            </div>
            @endif

            @if(isset($user['updated_by']))
            <div>
                <h4 class="font-medium text-gray-900 mb-2">Last Updated By</h4>
                <p class="text-gray-700 bg-gray-50 px-3 py-2 rounded">
                    {{ $user['updated_by'] }}
                </p>
            </div>
            @endif
        </div>
    </div>
</div>
@endif

<!-- Hidden Forms -->
<form id="deleteUserForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

@endsection

@push('scripts')
<script>
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

    async function deleteUser(userId, userName) {
        if (!confirm(`Are you sure you want to delete user "${userName}"? This action cannot be undone.`)) {
            return;
        }
        
        // Show additional confirmation for delete action
        const confirmed = confirm('This will permanently delete all user data. Are you absolutely sure?');
        if (!confirmed) return;
        
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
                showNotification('User deleted successfully. Redirecting...', 'success');
                setTimeout(() => {
                    window.location.href = '{{ route("user.index") }}';
                }, 1500);
            } else {
                showNotification('Failed to delete user: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Delete error:', error);
            showNotification('Error deleting user: Connection failed', 'error');
        }
    }

    // Copy user ID to clipboard
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            showNotification('User ID copied to clipboard', 'success');
        }).catch(err => {
            console.error('Failed to copy: ', err);
            showNotification('Failed to copy to clipboard', 'error');
        });
    }

    // Add click handler for user ID
    document.addEventListener('DOMContentLoaded', function() {
        const userIdElement = document.querySelector('.font-mono');
        if (userIdElement) {
            userIdElement.style.cursor = 'pointer';
            userIdElement.title = 'Click to copy';
            userIdElement.addEventListener('click', function() {
                copyToClipboard(this.textContent.trim());
            });
        }

        console.log('User details page initialized');
    });
</script>
@endpush