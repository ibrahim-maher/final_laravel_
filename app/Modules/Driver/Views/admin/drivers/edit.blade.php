@extends('admin::layouts.admin')

@section('title', 'Edit Driver')
@section('page-title', 'Edit Driver')

@section('content')
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <a href="{{ route('admin.drivers.show', $driver['firebase_uid']) }}" 
               class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-primary">Edit Driver</h1>
                <p class="text-gray-600 mt-1">Update driver information, vehicle, and document details</p>
            </div>
        </div>
        
       
    </div>
</div>

<!-- Status Badge -->
<div class="mb-6">
    <div class="flex flex-wrap gap-3">
        <span class="px-3 py-1 rounded-full text-sm font-medium
            {{ $driver['status'] === 'active' ? 'bg-green-100 text-green-800' : 
               ($driver['status'] === 'inactive' ? 'bg-gray-100 text-gray-800' : 
               ($driver['status'] === 'suspended' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800')) }}">
            <i class="fas fa-user mr-1"></i>{{ ucfirst($driver['status']) }}
        </span>
        
        <span class="px-3 py-1 rounded-full text-sm font-medium
            {{ $driver['verification_status'] === 'verified' ? 'bg-green-100 text-green-800' : 
               ($driver['verification_status'] === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
            <i class="fas fa-shield-alt mr-1"></i>{{ ucfirst($driver['verification_status']) }}
        </span>
        
        <span class="px-3 py-1 rounded-full text-sm font-medium
            {{ $driver['availability_status'] === 'available' ? 'bg-green-100 text-green-800' : 
               ($driver['availability_status'] === 'busy' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') }}">
            <i class="fas fa-circle mr-1"></i>{{ ucfirst($driver['availability_status']) }}
        </span>
    </div>
</div>

@if($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
        <div class="flex">
            <div class="py-1">
                <i class="fas fa-exclamation-circle mr-2"></i>
            </div>
            <div>
                <p class="font-bold">Please correct the following errors:</p>
                <ul class="mt-2 list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif

@if(session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
    </div>
@endif

@if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
    </div>
@endif

<form action="{{ route('admin.drivers.update', $driver['firebase_uid']) }}" method="POST" enctype="multipart/form-data" class="space-y-6" id="driverEditForm">
    @csrf
    @method('PUT')
    
    <!-- Progress Steps -->
    <div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center text-sm font-medium">1</div>
                    <span class="ml-2 text-sm font-medium text-gray-900">Driver Info</span>
                </div>
                <div class="w-16 h-1 bg-gray-200"></div>
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center text-sm font-medium">2</div>
                    <span class="ml-2 text-sm font-medium text-gray-900">Vehicle</span>
                </div>
                <div class="w-16 h-1 bg-gray-200"></div>
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center text-sm font-medium">3</div>
                    <span class="ml-2 text-sm font-medium text-gray-900">Documents</span>
                </div>
            </div>
            <div class="text-sm text-gray-500">
                Last updated: {{ isset($driver['updated_at']) ? \Carbon\Carbon::parse($driver['updated_at'])->format('M j, Y g:i A') : 'Never' }}
            </div>
        </div>
    </div>

    <!-- Basic Information -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-user mr-2 text-primary"></i>Basic Information
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="firebase_uid" class="block text-sm font-medium text-gray-700 mb-2">
                    Firebase UID <span class="text-red-500">*</span>
                    <i class="fas fa-lock text-gray-400 ml-1" title="Cannot be changed"></i>
                </label>
                <input type="text" name="firebase_uid" id="firebase_uid" 
                       value="{{ $driver['firebase_uid'] }}" readonly
                       class="w-full px-4 py-2 border rounded-lg bg-gray-50 cursor-not-allowed"
                       placeholder="Firebase UID">
                <p class="mt-1 text-xs text-gray-500">Firebase UID cannot be changed</p>
            </div>
            
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                    Full Name <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" id="name" 
                       value="{{ old('name', $driver['name'] ?? '') }}" required
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                       placeholder="Enter full name">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                    Email Address <span class="text-red-500">*</span>
                </label>
                <input type="email" name="email" id="email" 
                       value="{{ old('email', $driver['email'] ?? '') }}" required
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                       placeholder="Enter email address">
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                    Phone Number
                </label>
                <input type="tel" name="phone" id="phone" 
                       value="{{ old('phone', $driver['phone'] ?? '') }}"
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                       placeholder="Enter phone number">
                @error('phone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label for="date_of_birth" class="block text-sm font-medium text-gray-700 mb-2">
                    Date of Birth
                    <span class="text-gray-500 text-xs">(Must be 18+ years old)</span>
                </label>
                <input type="date" name="date_of_birth" id="date_of_birth" 
                       value="{{ old('date_of_birth', isset($driver['date_of_birth']) ? \Carbon\Carbon::parse($driver['date_of_birth'])->format('Y-m-d') : '') }}"
                       max="{{ now()->subYears(18)->format('Y-m-d') }}"
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                @error('date_of_birth')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label for="gender" class="block text-sm font-medium text-gray-700 mb-2">
                    Gender
                </label>
                <select name="gender" id="gender" 
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">Select gender</option>
                    <option value="male" {{ old('gender', $driver['gender'] ?? '') === 'male' ? 'selected' : '' }}>Male</option>
                    <option value="female" {{ old('gender', $driver['gender'] ?? '') === 'female' ? 'selected' : '' }}>Female</option>
                    <option value="other" {{ old('gender', $driver['gender'] ?? '') === 'other' ? 'selected' : '' }}>Other</option>
                </select>
                @error('gender')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <!-- Address Information -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-map-marker-alt mr-2 text-primary"></i>Address Information
        </h3>
        <div class="grid grid-cols-1 gap-6">
            <div>
                <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                    Street Address
                </label>
                <textarea name="address" id="address" rows="2" 
                          class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                          placeholder="Enter street address">{{ old('address', $driver['address'] ?? '') }}</textarea>
                @error('address')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700 mb-2">
                        City
                    </label>
                    <input type="text" name="city" id="city" 
                           value="{{ old('city', $driver['city'] ?? '') }}"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="Enter city">
                    @error('city')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="state" class="block text-sm font-medium text-gray-700 mb-2">
                        State/Province
                    </label>
                    <input type="text" name="state" id="state" 
                           value="{{ old('state', $driver['state'] ?? '') }}"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="Enter state">
                    @error('state')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-2">
                        Postal Code
                    </label>
                    <input type="text" name="postal_code" id="postal_code" 
                           value="{{ old('postal_code', $driver['postal_code'] ?? '') }}"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="Enter postal code">
                    @error('postal_code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="country" class="block text-sm font-medium text-gray-700 mb-2">
                        Country
                    </label>
                    <input type="text" name="country" id="country" 
                           value="{{ old('country', $driver['country'] ?? 'USA') }}"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="Enter country">
                    @error('country')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <!-- License Information -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-id-card mr-2 text-primary"></i>License Information
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="license_number" class="block text-sm font-medium text-gray-700 mb-2">
                    License Number <span class="text-red-500">*</span>
                </label>
                <input type="text" name="license_number" id="license_number" 
                       value="{{ old('license_number', $driver['license_number'] ?? '') }}" required
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                       placeholder="Enter license number">
                @error('license_number')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label for="license_expiry" class="block text-sm font-medium text-gray-700 mb-2">
                    License Expiry Date <span class="text-red-500">*</span>
                </label>
                <input type="date" name="license_expiry" id="license_expiry" 
                       value="{{ old('license_expiry', isset($driver['license_expiry']) ? \Carbon\Carbon::parse($driver['license_expiry'])->format('Y-m-d') : '') }}" required
                       min="{{ now()->format('Y-m-d') }}"
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                @error('license_expiry')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label for="license_class" class="block text-sm font-medium text-gray-700 mb-2">
                    License Class
                </label>
                <select name="license_class" id="license_class" 
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">Select license class</option>
                    @foreach($licenseClasses as $key => $label)
                        <option value="{{ $key }}" {{ old('license_class', $driver['license_class'] ?? '') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('license_class')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label for="license_type" class="block text-sm font-medium text-gray-700 mb-2">
                    License Type
                </label>
                <select name="license_type" id="license_type" 
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">Select license type</option>
                    @foreach($licenseTypes as $key => $label)
                        <option value="{{ $key }}" {{ old('license_type', $driver['license_type'] ?? '') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('license_type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label for="issuing_state" class="block text-sm font-medium text-gray-700 mb-2">
                    License Issuing State
                </label>
                <input type="text" name="issuing_state" id="issuing_state" 
                       value="{{ old('issuing_state', $driver['issuing_state'] ?? '') }}"
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                       placeholder="Enter license issuing state">
                @error('issuing_state')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <!-- Vehicle Information -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-car mr-2 text-primary"></i>Vehicle Information
            </h3>
            @if(isset($vehicles) && count($vehicles) > 0)
                <div class="text-sm text-green-600">
                    <i class="fas fa-check-circle mr-1"></i>{{ count($vehicles) }} vehicle(s) registered
                </div>
            @else
                <div class="text-sm text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i>No vehicles registered
                </div>
            @endif
        </div>
        
        @php
            $primaryVehicle = isset($vehicles) && count($vehicles) > 0 ? $vehicles[0] : null;
        @endphp
        
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="vehicle_make" class="block text-sm font-medium text-gray-700 mb-2">
                        Make
                    </label>
                    <input type="text" name="vehicle_make" id="vehicle_make" 
                           value="{{ old('vehicle_make', $primaryVehicle['make'] ?? '') }}"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="e.g., Toyota, Honda">
                    @error('vehicle_make')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="vehicle_model" class="block text-sm font-medium text-gray-700 mb-2">
                        Model
                    </label>
                    <input type="text" name="vehicle_model" id="vehicle_model" 
                           value="{{ old('vehicle_model', $primaryVehicle['model'] ?? '') }}"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="e.g., Camry, Accord">
                    @error('vehicle_model')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="vehicle_year" class="block text-sm font-medium text-gray-700 mb-2">
                        Year
                    </label>
                    <select name="vehicle_year" id="vehicle_year" 
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">Select year</option>
                        @for($year = date('Y') + 1; $year >= 1990; $year--)
                            <option value="{{ $year }}" {{ old('vehicle_year', $primaryVehicle['year'] ?? '') == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endfor
                    </select>
                    @error('vehicle_year')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="vehicle_color" class="block text-sm font-medium text-gray-700 mb-2">
                        Color
                    </label>
                    <input type="text" name="vehicle_color" id="vehicle_color" 
                           value="{{ old('vehicle_color', $primaryVehicle['color'] ?? '') }}"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="e.g., Black, White, Red">
                    @error('vehicle_color')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="vehicle_license_plate" class="block text-sm font-medium text-gray-700 mb-2">
                        License Plate
                    </label>
                    <input type="text" name="vehicle_license_plate" id="vehicle_license_plate" 
                           value="{{ old('vehicle_license_plate', $primaryVehicle['license_plate'] ?? '') }}"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="Enter license plate number">
                    @error('vehicle_license_plate')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="vehicle_vin" class="block text-sm font-medium text-gray-700 mb-2">
                        VIN Number
                        <span class="text-gray-500 text-xs">(17 characters)</span>
                    </label>
                    <input type="text" name="vehicle_vin" id="vehicle_vin" 
                           value="{{ old('vehicle_vin', $primaryVehicle['vin'] ?? '') }}"
                           maxlength="17"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="Enter VIN number">
                    @error('vehicle_vin')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="vehicle_seats" class="block text-sm font-medium text-gray-700 mb-2">
                        Number of Seats
                    </label>
                    <select name="vehicle_seats" id="vehicle_seats" 
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">Select seats</option>
                        @for($seats = 2; $seats <= 8; $seats++)
                            <option value="{{ $seats }}" {{ old('vehicle_seats', $primaryVehicle['seats'] ?? '4') == $seats ? 'selected' : '' }}>
                                {{ $seats }} seats
                            </option>
                        @endfor
                    </select>
                    @error('vehicle_seats')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="vehicle_type" class="block text-sm font-medium text-gray-700 mb-2">
                        Vehicle Type
                    </label>
                    <select name="vehicle_type" id="vehicle_type" 
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">Select type</option>
                        @foreach($vehicleTypes as $key => $label)
                            <option value="{{ $key }}" {{ old('vehicle_type', $primaryVehicle['vehicle_type'] ?? '') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('vehicle_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="fuel_type" class="block text-sm font-medium text-gray-700 mb-2">
                        Fuel Type
                    </label>
                    <select name="fuel_type" id="fuel_type" 
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">Select fuel type</option>
                        @foreach($fuelTypes as $key => $label)
                            <option value="{{ $key }}" {{ old('fuel_type', $primaryVehicle['fuel_type'] ?? '') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('fuel_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            
            <!-- Vehicle Registration & Insurance -->
            <div class="border-t pt-6">
                <h4 class="text-md font-semibold text-gray-900 mb-4">Registration & Insurance</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="registration_number" class="block text-sm font-medium text-gray-700 mb-2">
                            Registration Number
                        </label>
                        <input type="text" name="registration_number" id="registration_number" 
                               value="{{ old('registration_number', $primaryVehicle['registration_number'] ?? '') }}"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                               placeholder="Enter registration number">
                        @error('registration_number')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="registration_expiry" class="block text-sm font-medium text-gray-700 mb-2">
                            Registration Expiry
                        </label>
                        <input type="date" name="registration_expiry" id="registration_expiry" 
                               value="{{ old('registration_expiry', isset($primaryVehicle['registration_expiry']) ? \Carbon\Carbon::parse($primaryVehicle['registration_expiry'])->format('Y-m-d') : '') }}"
                               min="{{ now()->format('Y-m-d') }}"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        @error('registration_expiry')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="insurance_provider" class="block text-sm font-medium text-gray-700 mb-2">
                            Insurance Provider
                        </label>
                        <input type="text" name="insurance_provider" id="insurance_provider" 
                               value="{{ old('insurance_provider', $primaryVehicle['insurance_provider'] ?? '') }}"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                               placeholder="Enter insurance provider">
                        @error('insurance_provider')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="insurance_policy_number" class="block text-sm font-medium text-gray-700 mb-2">
                            Policy Number
                        </label>
                        <input type="text" name="insurance_policy_number" id="insurance_policy_number" 
                               value="{{ old('insurance_policy_number', $primaryVehicle['insurance_policy_number'] ?? '') }}"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                               placeholder="Enter policy number">
                        @error('insurance_policy_number')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="insurance_expiry" class="block text-sm font-medium text-gray-700 mb-2">
                            Insurance Expiry
                        </label>
                        <input type="date" name="insurance_expiry" id="insurance_expiry" 
                               value="{{ old('insurance_expiry', isset($primaryVehicle['insurance_expiry']) ? \Carbon\Carbon::parse($primaryVehicle['insurance_expiry'])->format('Y-m-d') : '') }}"
                               min="{{ now()->format('Y-m-d') }}"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        @error('insurance_expiry')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Document Management Section -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-file-alt mr-2 text-primary"></i>Document Management
        </h3>
        
        <!-- Existing Documents -->
        @if(isset($documents) && count($documents) > 0)
            <div class="mb-6">
                <h4 class="text-md font-semibold text-gray-900 mb-3">Current Documents</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($documents as $document)
                        <div class="border rounded-lg p-4 bg-gray-50">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center">
                                    <i class="fas fa-file-{{ $document['file_type'] === 'pdf' ? 'pdf' : 'image' }} text-primary mr-2"></i>
                                    <span class="text-sm font-medium">{{ $document['document_name'] ?? $document['document_type'] }}</span>
                                </div>
                                <span class="px-2 py-1 text-xs rounded-full
                                    {{ $document['verification_status'] === 'verified' ? 'bg-green-100 text-green-800' : 
                                       ($document['verification_status'] === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                    {{ ucfirst($document['verification_status'] ?? 'pending') }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-600 mb-2">
                                Uploaded: {{ isset($document['created_at']) ? \Carbon\Carbon::parse($document['created_at'])->format('M j, Y') : 'Unknown' }}
                            </p>
                            <div class="flex space-x-2">
                                @if(isset($document['file_url']))
                                    <a href="{{ $document['file_url'] }}" target="_blank" 
                                       class="text-xs bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700">
                                        <i class="fas fa-eye mr-1"></i>View
                                    </a>
                                @endif
                                <button type="button" onclick="deleteDocument('{{ $document['id'] ?? '' }}')" 
                                        class="text-xs bg-red-600 text-white px-2 py-1 rounded hover:bg-red-700">
                                    <i class="fas fa-trash mr-1"></i>Delete
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
        
        <!-- Upload New Documents -->
        <div class="border-t pt-6">
            <h4 class="text-md font-semibold text-gray-900 mb-4">Upload New Documents</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Profile Photo -->
                <div>
                    <label for="profile_photo" class="block text-sm font-medium text-gray-700 mb-2">
                        Profile Photo
                        <span class="text-gray-500 text-xs">(JPEG, PNG - Max 5MB)</span>
                    </label>
                    <input type="file" name="profile_photo" id="profile_photo" 
                           accept="image/jpeg,image/png,image/jpg"
                           onchange="handleFileUpload(this, 'profile_photo_preview')"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <div id="profile_photo_preview" class="mt-2 text-sm text-gray-600"></div>
                    @error('profile_photo')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Driver's License Front -->
                <div>
                    <label for="license_front" class="block text-sm font-medium text-gray-700 mb-2">
                        Driver's License (Front)
                        <span class="text-gray-500 text-xs">(JPEG, PNG, PDF - Max 5MB)</span>
                    </label>
                    <input type="file" name="license_front" id="license_front" 
                           accept="image/jpeg,image/png,image/jpg,application/pdf"
                           onchange="handleFileUpload(this, 'license_front_preview')"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <div id="license_front_preview" class="mt-2 text-sm text-gray-600"></div>
                    @error('license_front')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Driver's License Back -->
                <div>
                    <label for="license_back" class="block text-sm font-medium text-gray-700 mb-2">
                        Driver's License (Back)
                        <span class="text-gray-500 text-xs">(JPEG, PNG, PDF - Max 5MB)</span>
                    </label>
                    <input type="file" name="license_back" id="license_back" 
                           accept="image/jpeg,image/png,image/jpg,application/pdf"
                           onchange="handleFileUpload(this, 'license_back_preview')"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <div id="license_back_preview" class="mt-2 text-sm text-gray-600"></div>
                    @error('license_back')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Vehicle Registration -->
                <div>
                    <label for="vehicle_registration_doc" class="block text-sm font-medium text-gray-700 mb-2">
                        Vehicle Registration
                        <span class="text-gray-500 text-xs">(JPEG, PNG, PDF - Max 5MB)</span>
                    </label>
                    <input type="file" name="vehicle_registration_doc" id="vehicle_registration_doc" 
                           accept="image/jpeg,image/png,image/jpg,application/pdf"
                           onchange="handleFileUpload(this, 'vehicle_registration_preview')"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <div id="vehicle_registration_preview" class="mt-2 text-sm text-gray-600"></div>
                    @error('vehicle_registration_doc')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Insurance Certificate -->
                <div>
                    <label for="insurance_certificate" class="block text-sm font-medium text-gray-700 mb-2">
                        Insurance Certificate
                        <span class="text-gray-500 text-xs">(JPEG, PNG, PDF - Max 5MB)</span>
                    </label>
                    <input type="file" name="insurance_certificate" id="insurance_certificate" 
                           accept="image/jpeg,image/png,image/jpg,application/pdf"
                           onchange="handleFileUpload(this, 'insurance_certificate_preview')"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <div id="insurance_certificate_preview" class="mt-2 text-sm text-gray-600"></div>
                    @error('insurance_certificate')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Vehicle Photos -->
                <div class="md:col-span-2">
                    <label for="vehicle_photos" class="block text-sm font-medium text-gray-700 mb-2">
                        Vehicle Photos
                        <span class="text-gray-500 text-xs">(JPEG, PNG - Max 5MB each, Multiple files allowed)</span>
                    </label>
                    <input type="file" name="vehicle_photos[]" id="vehicle_photos" 
                           accept="image/jpeg,image/png,image/jpg"
                           multiple
                           onchange="handleMultipleFileUpload(this, 'vehicle_photos_preview')"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <div id="vehicle_photos_preview" class="mt-2 text-sm text-gray-600"></div>
                    @error('vehicle_photos.*')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Status Settings -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-cog mr-2 text-primary"></i>Status Settings
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                    Driver Status <span class="text-red-500">*</span>
                </label>
                <select name="status" id="status" required
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" {{ old('status', $driver['status']) === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label for="verification_status" class="block text-sm font-medium text-gray-700 mb-2">
                    Verification Status <span class="text-red-500">*</span>
                </label>
                <select name="verification_status" id="verification_status" required
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    @foreach($verificationStatuses as $key => $label)
                        <option value="{{ $key }}" {{ old('verification_status', $driver['verification_status']) === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('verification_status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label for="availability_status" class="block text-sm font-medium text-gray-700 mb-2">
                    Availability Status <span class="text-red-500">*</span>
                </label>
                <select name="availability_status" id="availability_status" required
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    @foreach($availabilityStatuses as $key => $label)
                        <option value="{{ $key }}" {{ old('availability_status', $driver['availability_status']) === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('availability_status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
        
        <!-- Status Change Notes -->
        <div class="mt-6">
            <label for="admin_notes" class="block text-sm font-medium text-gray-700 mb-2">
                Admin Notes (Optional)
                <span class="text-gray-500 text-xs">Record reason for status changes</span>
            </label>
            <textarea name="admin_notes" id="admin_notes" rows="3" 
                      class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                      placeholder="Enter notes about changes made...">{{ old('admin_notes') }}</textarea>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex justify-between items-center">
            <div class="text-sm text-gray-600">
                <i class="fas fa-info-circle mr-1"></i>
                Changes will be logged and driver will be notified
            </div>
            <div class="flex space-x-4">
                <a href="{{ route('admin.drivers.show', $driver['firebase_uid']) }}" 
                   class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
                <button type="submit" id="submitBtn"
                        class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Update Driver
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Status Change Modal -->
<div id="statusModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Change Driver Status</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">New Status</label>
                    <select id="modalStatus" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                        <option value="activate">Activate</option>
                        <option value="deactivate">Deactivate</option>
                        <option value="suspend">Suspend</option>
                        <option value="verify">Verify</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Reason (Optional)</label>
                    <textarea id="modalReason" rows="3" class="w-full px-3 py-2 border rounded-lg" 
                              placeholder="Enter reason for status change..."></textarea>
                </div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <button onclick="closeStatusModal()" 
                        class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                    Cancel
                </button>
                <button onclick="executeStatusChange()" 
                        class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    Update Status
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 mb-4">
                <i class="fas fa-exclamation-triangle text-yellow-600"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2" id="confirmTitle">Confirm Action</h3>
            <p class="text-sm text-gray-500 mb-6" id="confirmMessage">Are you sure you want to perform this action?</p>
            <div class="flex justify-center space-x-3">
                <button onclick="closeConfirmModal()" 
                        class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                    Cancel
                </button>
                <button onclick="executeConfirmedAction()" id="confirmButton"
                        class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let currentAction = null;
    let currentDriverId = null;

    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('driverEditForm');
        const submitBtn = document.getElementById('submitBtn');
        
        // Real-time validation (same as create form)
        setupValidation();
        
        // Form submission
        form.addEventListener('submit', function(e) {
            let isValid = validateForm();
            
            if (!isValid) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Updating Driver...';
        });
        
        console.log('Driver edit form initialized');
    });

    // Status Management Functions
    function showStatusModal() {
        document.getElementById('statusModal').classList.remove('hidden');
    }

    function closeStatusModal() {
        document.getElementById('statusModal').classList.add('hidden');
        document.getElementById('modalReason').value = '';
    }

    function executeStatusChange() {
        const status = document.getElementById('modalStatus').value;
        const reason = document.getElementById('modalReason').value;
        const driverId = '{{ $driver["firebase_uid"] }}';
        
        updateDriverStatus(driverId, status, reason);
        closeStatusModal();
    }

    // Confirmation Functions
    function confirmAction(action, driverId) {
        currentAction = action;
        currentDriverId = driverId;
        
        const messages = {
            'activate': {
                title: 'Activate Driver',
                message: 'This will activate the driver account and allow them to accept rides.',
                buttonText: 'Activate',
                buttonClass: 'bg-green-600 hover:bg-green-700'
            },
            'verify': {
                title: 'Verify Driver',
                message: 'This will mark the driver as verified and update their verification status.',
                buttonText: 'Verify', 
                buttonClass: 'bg-blue-600 hover:bg-blue-700'
            },
            'suspend': {
                title: 'Suspend Driver',
                message: 'This will suspend the driver account and prevent them from accepting rides.',
                buttonText: 'Suspend',
                buttonClass: 'bg-red-600 hover:bg-red-700'
            }
        };
        
        const config = messages[action];
        if (config) {
            document.getElementById('confirmTitle').textContent = config.title;
            document.getElementById('confirmMessage').textContent = config.message;
            
            const confirmBtn = document.getElementById('confirmButton');
            confirmBtn.textContent = config.buttonText;
            confirmBtn.className = `${config.buttonClass} text-white px-4 py-2 rounded-lg transition-colors`;
            
            document.getElementById('confirmModal').classList.remove('hidden');
        }
    }

    function closeConfirmModal() {
        document.getElementById('confirmModal').classList.add('hidden');
        currentAction = null;
        currentDriverId = null;
    }

    function executeConfirmedAction() {
        if (currentAction && currentDriverId) {
            updateDriverStatus(currentDriverId, currentAction);
        }
        closeConfirmModal();
    }

    // AJAX Status Update
    function updateDriverStatus(driverId, status, reason = '') {
        fetch(`/admin/drivers/${driverId}/status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                status: status,
                reason: reason
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Success', data.message, 'success');
                // Refresh page to show updated status
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showNotification('Error', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error', 'Failed to update driver status', 'error');
        });
    }

    // Document Management
    function deleteDocument(documentId) {
        if (confirm('Are you sure you want to delete this document?')) {
            fetch(`/admin/drivers/documents/${documentId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Success', 'Document deleted successfully', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showNotification('Error', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error', 'Failed to delete document', 'error');
            });
        }
    }

    // Notification System
    function showNotification(title, message, type = 'info') {
        const colors = {
            success: 'bg-green-100 border-green-400 text-green-700',
            error: 'bg-red-100 border-red-400 text-red-700',
            info: 'bg-blue-100 border-blue-400 text-blue-700'
        };
        
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            info: 'fas fa-info-circle'
        };
        
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 border rounded-lg ${colors[type]} z-50 max-w-md`;
        notification.innerHTML = `
            <div class="flex items-center">
                <i class="${icons[type]} mr-2"></i>
                <div>
                    <div class="font-bold">${title}</div>
                    <div class="text-sm">${message}</div>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-lg">&times;</button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }

    // Validation Functions
    function setupValidation() {
        // Email validation
        const emailField = document.getElementById('email');
        emailField.addEventListener('blur', function() {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (this.value && !emailRegex.test(this.value)) {
                this.classList.add('border-red-500');
                showFieldError(this, 'Please enter a valid email address');
            } else {
                this.classList.remove('border-red-500');
                hideFieldError(this);
            }
        });
        
        // Date validations
        setupDateValidation('date_of_birth', function(date) {
            const age = Math.floor((new Date() - date) / (365.25 * 24 * 60 * 60 * 1000));
            return age >= 18 ? null : 'Driver must be at least 18 years old';
        });
        
        setupDateValidation('license_expiry', function(date) {
            return date > new Date() ? null : 'License expiry date must be in the future';
        });
        
        setupDateValidation('registration_expiry', function(date) {
            return date > new Date() ? null : 'Registration expiry date must be in the future';
        });
        
        setupDateValidation('insurance_expiry', function(date) {
            return date > new Date() ? null : 'Insurance expiry date must be in the future';
        });
        
        // VIN validation
        const vinField = document.getElementById('vehicle_vin');
        if (vinField) {
            vinField.addEventListener('input', function() {
                const vin = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                this.value = vin;
                
                if (vin.length > 0 && vin.length !== 17) {
                    this.classList.add('border-yellow-500');
                    showFieldError(this, 'VIN should be 17 characters');
                } else {
                    this.classList.remove('border-yellow-500');
                    hideFieldError(this);
                }
            });
        }
    }

    function setupDateValidation(fieldId, validator) {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('change', function() {
                if (this.value) {
                    const date = new Date(this.value);
                    const error = validator(date);
                    
                    if (error) {
                        this.classList.add('border-red-500');
                        showFieldError(this, error);
                    } else {
                        this.classList.remove('border-red-500');
                        hideFieldError(this);
                    }
                }
            });
        }
    }

    function validateForm() {
        let isValid = true;
        const errors = [];
        
        // Check required fields
        const requiredFields = document.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('border-red-500');
                errors.push(`${getFieldLabel(field)} is required`);
            } else {
                field.classList.remove('border-red-500');
            }
        });
        
        // Email validation
        const emailField = document.getElementById('email');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (emailField.value && !emailRegex.test(emailField.value)) {
            isValid = false;
            errors.push('Please enter a valid email address');
        }
        
        // Date validations
        const dobField = document.getElementById('date_of_birth');
        if (dobField.value) {
            const age = Math.floor((new Date() - new Date(dobField.value)) / (365.25 * 24 * 60 * 60 * 1000));
            if (age < 18) {
                isValid = false;
                errors.push('Driver must be at least 18 years old');
            }
        }
        
        const licenseExpiryField = document.getElementById('license_expiry');
        if (licenseExpiryField.value && new Date(licenseExpiryField.value) <= new Date()) {
            isValid = false;
            errors.push('License expiry date must be in the future');
        }
        
        if (!isValid) {
            showErrorSummary(errors);
        }
        
        return isValid;
    }

    // Helper functions
    function getFieldLabel(field) {
        const label = field.closest('.grid').querySelector(`label[for="${field.id}"]`);
        return label ? label.textContent.replace('*', '').trim() : field.name;
    }

    function showFieldError(field, message) {
        hideFieldError(field);
        const errorDiv = document.createElement('p');
        errorDiv.className = 'mt-1 text-sm text-red-600 field-error';
        errorDiv.textContent = message;
        field.parentNode.appendChild(errorDiv);
    }

    function hideFieldError(field) {
        const existingError = field.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
    }

    function showErrorSummary(errors) {
        const errorHtml = `
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6" id="error-summary">
                <div class="flex">
                    <div class="py-1">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                    </div>
                    <div>
                        <p class="font-bold">Please correct the following errors:</p>
                        <ul class="mt-2 list-disc list-inside">
                            ${errors.map(error => `<li>${error}</li>`).join('')}
                        </ul>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing error summary
        const existingError = document.getElementById('error-summary');
        if (existingError) {
            existingError.remove();
        }
        
        // Add new error summary
        const form = document.getElementById('driverEditForm');
        form.insertAdjacentHTML('beforebegin', errorHtml);
        
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // File upload handling functions (same as create form)
    function handleFileUpload(input, previewId) {
        const file = input.files[0];
        const previewElement = document.getElementById(previewId);
        
        if (file) {
            // Validate file size (5MB limit)
            const maxSize = 5 * 1024 * 1024; // 5MB in bytes
            if (file.size > maxSize) {
                previewElement.innerHTML = '<span class="text-red-600"><i class="fas fa-exclamation-triangle mr-1"></i>File too large (max 5MB)</span>';
                input.value = '';
                return;
            }
            
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
            if (!allowedTypes.includes(file.type)) {
                previewElement.innerHTML = '<span class="text-red-600"><i class="fas fa-exclamation-triangle mr-1"></i>Invalid file type</span>';
                input.value = '';
                return;
            }
            
            // Show success message
            previewElement.innerHTML = `<span class="text-green-600"><i class="fas fa-check mr-1"></i>${file.name} (${formatFileSize(file.size)})</span>`;
        } else {
            previewElement.innerHTML = '';
        }
    }

    function handleMultipleFileUpload(input, previewId) {
        const files = input.files;
        const previewElement = document.getElementById(previewId);
        
        if (files.length > 0) {
            let previewHtml = '';
            let hasErrors = false;
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                
                // Validate file size
                const maxSize = 5 * 1024 * 1024; // 5MB
                if (file.size > maxSize) {
                    previewHtml += `<div class="text-red-600"><i class="fas fa-exclamation-triangle mr-1"></i>${file.name} - Too large (max 5MB)</div>`;
                    hasErrors = true;
                    continue;
                }
                
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                if (!allowedTypes.includes(file.type)) {
                    previewHtml += `<div class="text-red-600"><i class="fas fa-exclamation-triangle mr-1"></i>${file.name} - Invalid type</div>`;
                    hasErrors = true;
                    continue;
                }
                
                previewHtml += `<div class="text-green-600"><i class="fas fa-check mr-1"></i>${file.name} (${formatFileSize(file.size)})</div>`;
            }
            
            previewElement.innerHTML = previewHtml;
            
            if (hasErrors) {
                setTimeout(() => {
                    input.value = '';
                    previewElement.innerHTML = '<span class="text-red-600">Please select valid files only</span>';
                }, 3000);
            }
        } else {
            previewElement.innerHTML = '';
        }
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Auto-save functionality (optional)
    let autoSaveTimeout;
    function enableAutoSave() {
        const form = document.getElementById('driverEditForm');
        const formFields = form.querySelectorAll('input, select, textarea');
        
        formFields.forEach(field => {
            field.addEventListener('input', function() {
                clearTimeout(autoSaveTimeout);
                autoSaveTimeout = setTimeout(() => {
                    saveFormData();
                }, 2000); // Save after 2 seconds of inactivity
            });
        });
    }

    function saveFormData() {
        const formData = new FormData(document.getElementById('driverEditForm'));
        localStorage.setItem('driver_edit_data_{{ $driver["firebase_uid"] }}', JSON.stringify(Object.fromEntries(formData)));
        
        // Show subtle save indicator
        const indicator = document.createElement('div');
        indicator.className = 'fixed bottom-4 right-4 bg-green-600 text-white px-3 py-1 rounded text-sm';
        indicator.innerHTML = '<i class="fas fa-save mr-1"></i>Draft saved';
        document.body.appendChild(indicator);
        
        setTimeout(() => {
            indicator.remove();
        }, 2000);
    }

    function loadSavedData() {
        const savedData = localStorage.getItem('driver_edit_data_{{ $driver["firebase_uid"] }}');
        if (savedData) {
            try {
                const data = JSON.parse(savedData);
                Object.entries(data).forEach(([key, value]) => {
                    const field = document.querySelector(`[name="${key}"]`);
                    if (field && !field.value) {
                        field.value = value;
                    }
                });
            } catch (e) {
                console.log('Could not load saved data');
            }
        }
    }

    // Initialize auto-save on page load
    // enableAutoSave();
    // loadSavedData();
</script>
@endpush    