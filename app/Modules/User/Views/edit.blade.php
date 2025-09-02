@extends('admin::layouts.admin')

@section('title', 'Edit User')
@section('page-title', 'Edit User')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-primary">Edit User</h1>
        <p class="text-gray-600 mt-1">Update user information and settings</p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('user.show', $user['id']) }}" 
           class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            <i class="fas fa-eye mr-2"></i>View Details
        </a>
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

@if($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <i class="fas fa-exclamation-triangle mr-2"></i>Please correct the following errors:
        <ul class="mt-2 ml-6 list-disc">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- User Info Card -->
<div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
    <div class="flex items-center gap-4 mb-4">
        <div class="w-16 h-16 bg-primary rounded-full flex items-center justify-center text-white text-2xl font-bold">
            {{ strtoupper(substr($user['name'] ?? 'U', 0, 1)) }}
        </div>
        <div>
            <h2 class="text-2xl font-bold text-gray-900">{{ $user['name'] ?? 'No Name' }}</h2>
            <p class="text-gray-600">{{ $user['email'] ?? 'No Email' }}</p>
            <div class="flex gap-2 mt-2">
                @if(($user['status'] ?? 'active') === 'active')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <i class="fas fa-check-circle mr-1"></i>Active
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        <i class="fas fa-times-circle mr-1"></i>Inactive
                    </span>
                @endif
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    {{ ucfirst($user['role'] ?? 'user') }}
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Edit Form -->
<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    <div class="p-6 border-b">
        <h2 class="text-xl font-semibold">
            <i class="fas fa-edit mr-2 text-primary"></i>User Information
        </h2>
        <p class="text-gray-600 mt-1">Update the user's profile information and settings</p>
    </div>
    
    <form method="POST" action="{{ route('user.update', $user['id']) }}" class="p-6 space-y-6">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                    Full Name <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       name="name" 
                       id="name" 
                       value="{{ old('name', $user['name'] ?? '') }}"
                       required
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent {{ $errors->has('name') ? 'border-red-500' : 'border-gray-300' }}">
                @error('name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                    Email Address <span class="text-red-500">*</span>
                </label>
                <input type="email" 
                       name="email" 
                       id="email" 
                       value="{{ old('email', $user['email'] ?? '') }}"
                       required
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent {{ $errors->has('email') ? 'border-red-500' : 'border-gray-300' }}">
                @error('email')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Phone -->
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                    Phone Number
                </label>
                <input type="tel" 
                       name="phone" 
                       id="phone" 
                       value="{{ old('phone', $user['phone'] ?? '') }}"
                       placeholder="+1 (555) 123-4567"
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent {{ $errors->has('phone') ? 'border-red-500' : 'border-gray-300' }}">
                @error('phone')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Status -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                    Status <span class="text-red-500">*</span>
                </label>
                <select name="status" 
                        id="status" 
                        required
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent {{ $errors->has('status') ? 'border-red-500' : 'border-gray-300' }}">
                    <option value="active" {{ old('status', $user['status'] ?? 'active') === 'active' ? 'selected' : '' }}>
                        Active
                    </option>
                    <option value="inactive" {{ old('status', $user['status'] ?? 'active') === 'inactive' ? 'selected' : '' }}>
                        Inactive
                    </option>
                </select>
                @error('status')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Role -->
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-2">
                    User Role <span class="text-red-500">*</span>
                </label>
                <select name="role" 
                        id="role" 
                        required
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent {{ $errors->has('role') ? 'border-red-500' : 'border-gray-300' }}">
                    <option value="user" {{ old('role', $user['role'] ?? 'user') === 'user' ? 'selected' : '' }}>
                        User
                    </option>
                    <option value="premium" {{ old('role', $user['role'] ?? 'user') === 'premium' ? 'selected' : '' }}>
                        Premium User
                    </option>
                    <option value="admin" {{ old('role', $user['role'] ?? 'user') === 'admin' ? 'selected' : '' }}>
                        Administrator
                    </option>
                </select>
                @error('role')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
                <p class="text-sm text-gray-500 mt-1">
                    Choose the appropriate role for this user's access level
                </p>
            </div>
        </div>

        <!-- Address -->
        <div>
            <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                Address
            </label>
            <textarea name="address" 
                      id="address" 
                      rows="3"
                      placeholder="Enter user's full address..."
                      class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent {{ $errors->has('address') ? 'border-red-500' : 'border-gray-300' }}">{{ old('address', $user['address'] ?? '') }}</textarea>
            @error('address')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-between pt-6 border-t">
            <div class="text-sm text-gray-500">
                <i class="fas fa-info-circle mr-1"></i>
                Fields marked with <span class="text-red-500">*</span> are required
            </div>
            <div class="flex gap-3">
                <a href="{{ route('user.index') }}" 
                   class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
                <button type="submit" 
                        class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Update User
                </button>
            </div>
        </div>
    </form>
</div>

<!-- User Activity Card (if you have activity tracking) -->
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
@endphp

<div class="bg-white rounded-lg shadow-sm border mt-6">
    <div class="p-6 border-b">
        <h3 class="text-lg font-semibold">
            <i class="fas fa-clock mr-2 text-primary"></i>Account Information
        </h3>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <h4 class="font-medium text-gray-900 mb-2">User ID</h4>
                <p class="text-sm text-gray-600">{{ $user['id'] ?? 'Unknown' }}</p>
            </div>
            <div>
                <h4 class="font-medium text-gray-900 mb-2">Created Date</h4>
                <p class="text-sm text-gray-600">
                    {{ $createdAt ? $createdAt->format('M d, Y h:i A') : 'Unknown' }}
                </p>
                @if($createdAt)
                    <p class="text-xs text-gray-500">{{ $createdAt->diffForHumans() }}</p>
                @endif
            </div>
            <div>
                <h4 class="font-medium text-gray-900 mb-2">Last Updated</h4>
                <p class="text-sm text-gray-600">
                    {{ $updatedAt ? $updatedAt->format('M d, Y h:i A') : 'Never' }}
                </p>
                @if($updatedAt)
                    <p class="text-xs text-gray-500">{{ $updatedAt->diffForHumans() }}</p>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Form validation
        const form = document.querySelector('form');
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        const phoneInput = document.getElementById('phone');

        // Real-time validation
        nameInput.addEventListener('blur', function() {
            if (this.value.length < 2) {
                this.classList.add('border-red-500');
                this.classList.remove('border-gray-300');
            } else {
                this.classList.remove('border-red-500');
                this.classList.add('border-gray-300');
            }
        });

        emailInput.addEventListener('blur', function() {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(this.value)) {
                this.classList.add('border-red-500');
                this.classList.remove('border-gray-300');
            } else {
                this.classList.remove('border-red-500');
                this.classList.add('border-gray-300');
            }
        });

        // Phone number formatting
        phoneInput.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length >= 10) {
                value = value.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
            }
            this.value = value;
        });

        // Form submission confirmation
        form.addEventListener('submit', function(e) {
            const userName = nameInput.value;
            if (!confirm(`Are you sure you want to update ${userName}'s information?`)) {
                e.preventDefault();
            }
        });

        console.log('User edit form initialized');
    });
</script>
@endpush