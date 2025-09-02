@extends('admin::layouts.admin')

@section('title', 'User Statistics')
@section('page-title', 'User Statistics')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">User Statistics</h1>
            <p class="text-sm text-gray-600">Overview of user activity and demographics</p>
        </div>
        <a href="{{ route('user.index') }}" 
           class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back to Users
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Users</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total_users'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-user-check text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Active Users</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['active_users'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-user-times text-red-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Inactive Users</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['inactive_users'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-star text-purple-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Premium Users</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['premium_users'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Users by Role -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Users by Role</h3>
            <div class="space-y-4">
                @foreach($stats['users_by_role'] as $role => $count)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-4 h-4 rounded-full 
                                {{ $role === 'admin' ? 'bg-red-500' : ($role === 'premium' ? 'bg-purple-500' : 'bg-blue-500') }}
                                mr-3"></div>
                            <span class="text-sm font-medium text-gray-900">{{ ucfirst($role) }}</span>
                        </div>
                        <div class="flex items-center">
                            <span class="text-sm font-bold text-gray-900 mr-2">{{ $count }}</span>
                            <div class="w-20 bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full
                                    {{ $role === 'admin' ? 'bg-red-500' : ($role === 'premium' ? 'bg-purple-500' : 'bg-blue-500') }}"
                                     style="width: {{ $stats['total_users'] > 0 ? ($count / $stats['total_users'] * 100) : 0 }}%"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Users by Status -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Users by Status</h3>
            <div class="space-y-4">
                @foreach($stats['users_by_status'] as $status => $count)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-4 h-4 rounded-full 
                                {{ $status === 'active' ? 'bg-green-500' : 'bg-red-500' }}
                                mr-3"></div>
                            <span class="text-sm font-medium text-gray-900">{{ ucfirst($status) }}</span>
                        </div>
                        <div class="flex items-center">
                            <span class="text-sm font-bold text-gray-900 mr-2">{{ $count }}</span>
                            <div class="w-20 bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full
                                    {{ $status === 'active' ? 'bg-green-500' : 'bg-red-500' }}"
                                     style="width: {{ $stats['total_users'] > 0 ? ($count / $stats['total_users'] * 100) : 0 }}%"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center">
                <div class="text-3xl font-bold text-green-600">{{ $stats['recent_registrations'] }}</div>
                <div class="text-sm text-gray-600">New Registrations</div>
                <div class="text-xs text-gray-500">Last 7 days</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-blue-600">{{ $stats['admin_users'] }}</div>
                <div class="text-sm text-gray-600">Admin Users</div>
                <div class="text-xs text-gray-500">Total count</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-purple-600">
                    {{ $stats['total_users'] > 0 ? round(($stats['active_users'] / $stats['total_users']) * 100) : 0 }}%
                </div>
                <div class="text-sm text-gray-600">Active Rate</div>
                <div class="text-xs text-gray-500">Active vs Total</div>
            </div>
        </div>
    </div>
</div>
@endsection