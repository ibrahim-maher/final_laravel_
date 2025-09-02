@extends('admin::layouts.admin')

@section('title', 'Create User')
@section('page-title', 'Create User')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-primary">Create New User</h1>
        <p class="text-gray-600 mt-1">Add a new user to the system with their information and settings</p>
    </div>
    <div class="flex gap-3">
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

<!-- Create User Form -->
<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    <div class="p-6 border-b">
        <h2 class="text-xl font-semibold">
            <i class="fas fa-user-plus mr-2 text-primary"></i>User Information
        </h2>
        <p class="text-gray-600 mt-1">Fill in the details to create a new user account</p>
    </div>
    
    <form method="POST" action="{{ route('user.store') }}" class="p-6 space-y-6" id="createUserForm">
        @csrf
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                    Full Name <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       name="name" 
                       id="name" 
                       value="{{ old('name') }}"
                       required
                       placeholder="Enter user's full name"
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
                       value="{{ old('email') }}"
                       required
                       placeholder="user@example.com"
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
                       value="{{ old('phone') }}"
                       placeholder="+1 (555) 123-4567"
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent {{ $errors->has('phone') ? 'border-red-500' : 'border-gray-300' }}">
                @error('phone')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Status -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                    Account Status <span class="text-red-500">*</span>
                </label>
                <select name="status" 
                        id="status" 
                        required
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent {{ $errors->has('status') ? 'border-red-500' : 'border-gray-300' }}">
                    <option value="">Select Status</option>
                    <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>
                        Active
                    </option>
                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>
                        Inactive
                    </option>
                </select>
                @error('status')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
                <p class="text-sm text-gray-500 mt-1">
                    Active users can access the system immediately
                </p>
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
                    <option value="">Select Role</option>
                    <option value="user" {{ old('role') === 'user' ? 'selected' : '' }}>
                        User - Standard access
                    </option>
                    <option value="premium" {{ old('role') === 'premium' ? 'selected' : '' }}>
                        Premium User - Enhanced features
                    </option>
                    <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>
                        Administrator - Full system access
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
                      rows="4"
                      placeholder="Enter user's full address (optional)..."
                      class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent {{ $errors->has('address') ? 'border-red-500' : 'border-gray-300' }}">{{ old('address') }}</textarea>
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
                        class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors"
                        id="submitBtn">
                    <i class="fas fa-plus mr-2"></i>Create User
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Information Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
    <!-- User Roles Info -->
    <div class="bg-white rounded-lg shadow-sm border">
        <div class="p-6">
            <h3 class="text-lg font-semibold mb-4">
                <i class="fas fa-user-tag mr-2 text-blue-600"></i>User Roles
            </h3>
            <div class="space-y-3">
                <div class="flex items-start gap-3">
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">User</span>
                    <div>
                        <p class="text-sm text-gray-700">Standard user access</p>
                        <p class="text-xs text-gray-500">Basic features and functionality</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-purple-100 text-purple-800">Premium</span>
                    <div>
                        <p class="text-sm text-gray-700">Enhanced user access</p>
                        <p class="text-xs text-gray-500">Additional features and priority support</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800">Admin</span>
                    <div>
                        <p class="text-sm text-gray-700">Administrator access</p>
                        <p class="text-xs text-gray-500">Full system control and management</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Info -->
    <div class="bg-white rounded-lg shadow-sm border">
        <div class="p-6">
            <h3 class="text-lg font-semibold mb-4">
                <i class="fas fa-toggle-on mr-2 text-green-600"></i>Account Status
            </h3>
            <div class="space-y-3">
                <div class="flex items-start gap-3">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <i class="fas fa-check-circle mr-1"></i>Active
                    </span>
                    <div>
                        <p class="text-sm text-gray-700">User can log in and use the system</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        <i class="fas fa-times-circle mr-1"></i>Inactive
                    </span>
                    <div>
                        <p class="text-sm text-gray-700">User access is restricted</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tips -->
    <div class="bg-white rounded-lg shadow-sm border">
        <div class="p-6">
            <h3 class="text-lg font-semibold mb-4">
                <i class="fas fa-lightbulb mr-2 text-yellow-600"></i>Tips
            </h3>
            <div class="space-y-2 text-sm text-gray-600">
                <p><i class="fas fa-check text-green-500 mr-2"></i>Use a valid email address for notifications</p>
                <p><i class="fas fa-check text-green-500 mr-2"></i>Phone numbers help with account recovery</p>
                <p><i class="fas fa-check text-green-500 mr-2"></i>Set status to "Active" for immediate access</p>
                <p><i class="fas fa-check text-green-500 mr-2"></i>Choose the most restrictive role needed</p>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('createUserForm');
        const submitBtn = document.getElementById('submitBtn');
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        const phoneInput = document.getElementById('phone');
        const statusSelect = document.getElementById('status');
        const roleSelect = document.getElementById('role');

        // Real-time validation
        nameInput.addEventListener('blur', function() {
            validateName(this);
        });

        emailInput.addEventListener('blur', function() {
            validateEmail(this);
        });

        phoneInput.addEventListener('input', function() {
            formatPhone(this);
        });

        // Form submission with loading state
        form.addEventListener('submit', function(e) {
            const isValid = validateForm();
            if (!isValid) {
                e.preventDefault();
                return;
            }

            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Creating User...';
        });

        function validateName(input) {
            const value = input.value.trim();
            if (value.length < 2) {
                setFieldError(input, 'Name must be at least 2 characters long');
                return false;
            } else if (value.length > 255) {
                setFieldError(input, 'Name must not exceed 255 characters');
                return false;
            } else {
                setFieldSuccess(input);
                return true;
            }
        }

        function validateEmail(input) {
            const value = input.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (!value) {
                setFieldError(input, 'Email is required');
                return false;
            } else if (!emailRegex.test(value)) {
                setFieldError(input, 'Please enter a valid email address');
                return false;
            } else if (value.length > 255) {
                setFieldError(input, 'Email must not exceed 255 characters');
                return false;
            } else {
                setFieldSuccess(input);
                return true;
            }
        }

        function formatPhone(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.length >= 10) {
                if (value.startsWith('1')) {
                    // US format with country code
                    value = value.replace(/(\d{1})(\d{3})(\d{3})(\d{4})/, '+$1 ($2) $3-$4');
                } else {
                    // US format without country code
                    value = value.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
                }
            }
            input.value = value;
        }

        function validateForm() {
            let isValid = true;

            // Validate required fields
            if (!validateName(nameInput)) isValid = false;
            if (!validateEmail(emailInput)) isValid = false;

            // Validate selects
            if (!statusSelect.value) {
                setFieldError(statusSelect, 'Please select a status');
                isValid = false;
            } else {
                setFieldSuccess(statusSelect);
            }

            if (!roleSelect.value) {
                setFieldError(roleSelect, 'Please select a role');
                isValid = false;
            } else {
                setFieldSuccess(roleSelect);
            }

            return isValid;
        }

        function setFieldError(field, message) {
            field.classList.add('border-red-500');
            field.classList.remove('border-gray-300', 'border-green-500');
            
            // Remove existing error message
            const existingError = field.parentNode.querySelector('.field-error');
            if (existingError) {
                existingError.remove();
            }

            // Add error message
            const errorElement = document.createElement('p');
            errorElement.className = 'text-red-500 text-sm mt-1 field-error';
            errorElement.textContent = message;
            field.parentNode.appendChild(errorElement);
        }

        function setFieldSuccess(field) {
            field.classList.remove('border-red-500');
            field.classList.add('border-green-500');
            
            // Remove error message
            const existingError = field.parentNode.querySelector('.field-error');
            if (existingError) {
                existingError.remove();
            }
        }

        // Auto-focus first field
        nameInput.focus();

        console.log('Create user form initialized');
    });
</script>
@endpush