@extends('admin::layouts.admin')

@section('title', isset($driver) ? 'Edit Driver - ' . $driver['name'] : 'Add New Driver')
@section('page-title', isset($driver) ? 'Edit Driver' : 'Add New Driver')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-primary">
            {{ isset($driver) ? 'Edit Driver' : 'Add New Driver' }}
        </h1>
        <p class="text-gray-600 mt-1">
            {{ isset($driver) ? 'Update driver information and settings' : 'Create a new driver account' }}
        </p>
    </div>
    <div class="flex gap-3">
        @if(isset($driver))
            <a href="{{ route('driver.show', $driver['firebase_uid']) }}" 
               class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-eye mr-2"></i>View Driver
            </a>
        @endif
        <a href="{{ route('driver.index') }}" 
           class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back to List
        </a>
    </div>
</div>

@if($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium">Please correct the following errors:</h3>
                <ul class="mt-2 text-sm list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif

@if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
    </div>
@endif

<form method="POST" action="{{ isset($driver) ? route('driver.update', $driver['firebase_uid']) : route('driver.store') }}" 
      class="space-y-6">
    @csrf
    @if(isset($driver))
        @method('PUT')
    @endif

    <!-- Personal Information -->
    <div class="bg-white rounded-lg shadow-sm border">
        <div class="p-6 border-b">
            <h3 class="text-lg font-semibold">
                <i class="fas fa-user mr-2 text-primary"></i>Personal Information
            </h3>
            <p class="text-gray-600 text-sm mt-1">Basic driver details and contact information</p>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @if(!isset($driver))
                <div class="md:col-span-2">
                    <label for="firebase_uid" class="block text-sm font-medium text-gray-700 mb-2">
                        Firebase UID <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="firebase_uid" name="firebase_uid" 
                           value="{{ old('firebase_uid') }}" required
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="Enter Firebase UID">
                    <p class="text-sm text-gray-500 mt-1">The unique Firebase authentication ID for this driver</p>
                </div>
                @endif

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Full Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" 
                           value="{{ old('name', $driver['name'] ?? '') }}" required
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="Enter full name">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email Address <span class="text-red-500">*</span>
                    </label>
                    <input type="email" id="email" name="email" 
                           value="{{ old('email', $driver['email'] ?? '') }}" required
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="Enter email address">
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                        Phone Number
                    </label>
                    <input type="tel" id="phone" name="phone" 
                           value="{{ old('phone', $driver['phone'] ?? '') }}"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="Enter phone number">
                </div>

                <div>
                    <label for="date_of_birth" class="block text-sm font-medium text-gray-700 mb-2">
                        Date of Birth
                    </label>
                    <input type="date" id="date_of_birth" name="date_of_birth" 
                           value="{{ old('date_of_birth', $driver['date_of_birth'] ?? '') }}"
                           max="{{ now()->subYears(18)->format('Y-m-d') }}"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <p class="text-sm text-gray-500 mt-1">Driver must be at least 18 years old</p>
                </div>

                <div>
                    <label for="gender" class="block text-sm font-medium text-gray-700 mb-2">
                        Gender
                    </label>
                    <select id="gender" name="gender" 
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">Select gender</option>
                        <option value="male" {{ old('gender', $driver['gender'] ?? '') === 'male' ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ old('gender', $driver['gender'] ?? '') === 'female' ? 'selected' : '' }}>Female</option>
                        <option value="other" {{ old('gender', $driver['gender'] ?? '') === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Address Information -->
    <div class="bg-white rounded-lg shadow-sm border">
        <div class="p-6 border-b">
            <h3 class="text-lg font-semibold">
                <i class="fas fa-map-marker-alt mr-2 text-primary"></i>Address Information
            </h3>
            <p class="text-gray-600 text-sm mt-1">Driver's residential address</p>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                        Street Address
                    </label>
                    <input type="text" id="address" name="address" 
                           value="{{ old('address', $driver['address'] ?? '') }}"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="Enter street address">
                </div>

                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700 mb-2">
                        City
                    </label>
                    <input type="text" id="city" name="city" 
                           value="{{ old('city', $driver['city'] ?? '') }}"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="Enter city">
                </div>

                <div>
                    <label for="state" class="block text-sm font-medium text-gray-700 mb-2">
                        State/Province
                    </label>
                    <input type="text" id="state" name="state" 
                           value="{{ old('state', $driver['state'] ?? '') }}"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="Enter state or province">
                </div>

                <div>
                    <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-2">
                        Postal Code
                    </label>
                    <input type="text" id="postal_code" name="postal_code" 
                           value="{{ old('postal_code', $driver['postal_code'] ?? '') }}"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="Enter postal code">
                </div>

                <div>
                    <label for="country" class="block text-sm font-medium text-gray-700 mb-2">
                        Country
                    </label>
                    <input type="text" id="country" name="country" 
                           value="{{ old('country', $driver['country'] ?? '') }}"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="Enter country">
                </div>
            </div>
        </div>
    </div>

    <!-- License Information -->
    <div class="bg-white rounded-lg shadow-sm border">
        <div class="p-6 border-b">
            <h3 class="text-lg font-semibold">
                <i class="fas fa-id-card mr-2 text-primary"></i>Driver License Information
            </h3>
            <p class="text-gray-600 text-sm mt-1">Valid driver's license is required</p>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="license_number" class="block text-sm font-medium text-gray-700 mb-2">
                        License Number <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="license_number" name="license_number" 
                           value="{{ old('license_number', $driver['license_number'] ?? '') }}" required
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="Enter driver license number">
                </div>

                <div>
                    <label for="license_expiry" class="block text-sm font-medium text-gray-700 mb-2">
                        License Expiry Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" id="license_expiry" name="license_expiry" 
                           value="{{ old('license_expiry', $driver['license_expiry'] ?? '') }}" required
                           min="{{ now()->format('Y-m-d') }}"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <p class="text-sm text-gray-500 mt-1">License must be valid and not expired</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Settings -->
    <div class="bg-white rounded-lg shadow-sm border">
        <div class="p-6 border-b">
            <h3 class="text-lg font-semibold">
                <i class="fas fa-cog mr-2 text-primary"></i>Status Settings
            </h3>
            <p class="text-gray-600 text-sm mt-1">Driver account and verification status</p>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                        Driver Status <span class="text-red-500">*</span>
                    </label>
                    <select id="status" name="status" required
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="pending" {{ old('status', $driver['status'] ?? 'pending') === 'pending' ? 'selected' : '' }}>
                            Pending
                        </option>
                        <option value="active" {{ old('status', $driver['status'] ?? 'pending') === 'active' ? 'selected' : '' }}>
                            Active
                        </option>
                        <option value="inactive" {{ old('status', $driver['status'] ?? 'pending') === 'inactive' ? 'selected' : '' }}>
                            Inactive
                        </option>
                        <option value="suspended" {{ old('status', $driver['status'] ?? 'pending') === 'suspended' ? 'selected' : '' }}>
                            Suspended
                        </option>
                    </select>
                </div>

                <div>
                    <label for="verification_status" class="block text-sm font-medium text-gray-700 mb-2">
                        Verification Status <span class="text-red-500">*</span>
                    </label>
                    <select id="verification_status" name="verification_status" required
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="pending" {{ old('verification_status', $driver['verification_status'] ?? 'pending') === 'pending' ? 'selected' : '' }}>
                            Pending
                        </option>
                        <option value="verified" {{ old('verification_status', $driver['verification_status'] ?? 'pending') === 'verified' ? 'selected' : '' }}>
                            Verified
                        </option>
                        <option value="rejected" {{ old('verification_status', $driver['verification_status'] ?? 'pending') === 'rejected' ? 'selected' : '' }}>
                            Rejected
                        </option>
                    </select>
                </div>

                <div>
                    <label for="availability_status" class="block text-sm font-medium text-gray-700 mb-2">
                        Availability Status <span class="text-red-500">*</span>
                    </label>
                    <select id="availability_status" name="availability_status" required
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="offline" {{ old('availability_status', $driver['availability_status'] ?? 'offline') === 'offline' ? 'selected' : '' }}>
                            Offline
                        </option>
                        <option value="available" {{ old('availability_status', $driver['availability_status'] ?? 'offline') === 'available' ? 'selected' : '' }}>
                            Available
                        </option>
                        <option value="busy" {{ old('availability_status', $driver['availability_status'] ?? 'offline') === 'busy' ? 'selected' : '' }}>
                            Busy
                        </option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex justify-end space-x-4">
            <a href="{{ route('driver.index') }}" 
               class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-times mr-2"></i>Cancel
            </a>
            
            <button type="submit" 
                    class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-save mr-2"></i>
                {{ isset($driver) ? 'Update Driver' : 'Create Driver' }}
            </button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-generate Firebase UID if creating new driver
        @if(!isset($driver))
        const firebaseUidInput = document.getElementById('firebase_uid');
        if (firebaseUidInput && !firebaseUidInput.value) {
            // Generate a sample UID format for demonstration
            const timestamp = Date.now();
            const randomPart = Math.random().toString(36).substring(2, 15);
            firebaseUidInput.value = `driver_${timestamp}_${randomPart}`;
        }
        @endif

        // Form validation
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('border-red-500');
                    
                    // Remove error styling when user starts typing
                    field.addEventListener('input', function() {
                        this.classList.remove('border-red-500');
                    });
                } else {
                    field.classList.remove('border-red-500');
                }
            });
            
            // Validate email format
            const emailField = document.getElementById('email');
            if (emailField.value && !isValidEmail(emailField.value)) {
                isValid = false;
                emailField.classList.add('border-red-500');
                alert('Please enter a valid email address.');
            }
            
            // Validate license expiry
            const licenseExpiryField = document.getElementById('license_expiry');
            if (licenseExpiryField.value) {
                const expiryDate = new Date(licenseExpiryField.value);
                const today = new Date();
                
                if (expiryDate <= today) {
                    isValid = false;
                    licenseExpiryField.classList.add('border-red-500');
                    alert('License expiry date must be in the future.');
                }
            }
            
            // Validate date of birth (must be 18+ years old)
            const dobField = document.getElementById('date_of_birth');
            if (dobField.value) {
                const dob = new Date(dobField.value);
                const today = new Date();
                const age = today.getFullYear() - dob.getFullYear();
                const monthDiff = today.getMonth() - dob.getMonth();
                
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                    age--;
                }
                
                if (age < 18) {
                    isValid = false;
                    dobField.classList.add('border-red-500');
                    alert('Driver must be at least 18 years old.');
                }
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
        
        // Real-time validation feedback
        document.getElementById('email').addEventListener('blur', function() {
            if (this.value && !isValidEmail(this.value)) {
                this.classList.add('border-red-500');
            } else {
                this.classList.remove('border-red-500');
            }
        });
    });

    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
</script>
@endpush