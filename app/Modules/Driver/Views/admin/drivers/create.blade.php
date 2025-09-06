@extends('admin::layouts.admin')

@section('title', 'Create Driver')
@section('page-title', 'Create Driver')

@section('content')
<div class="mb-6">
    <div class="flex items-center space-x-4">
        <a href="{{ route('admin.drivers.index') }}" 
           class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-primary">Create New Driver</h1>
            <p class="text-gray-600 mt-1">Add a new driver with vehicle and document information</p>
        </div>
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

<form action="{{ route('admin.drivers.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6" id="driverForm">
    @csrf
    
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
                    <div class="w-8 h-8 bg-gray-200 text-gray-600 rounded-full flex items-center justify-center text-sm font-medium">2</div>
                    <span class="ml-2 text-sm font-medium text-gray-500">Vehicle</span>
                </div>
                <div class="w-16 h-1 bg-gray-200"></div>
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-gray-200 text-gray-600 rounded-full flex items-center justify-center text-sm font-medium">3</div>
                    <span class="ml-2 text-sm font-medium text-gray-500">Documents</span>
                </div>
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
                    <i class="fas fa-info-circle text-gray-400 ml-1" title="Unique identifier from Firebase Auth"></i>
                </label>
                <div class="flex">
                    <input type="text" name="firebase_uid" id="firebase_uid" 
                           value="{{ old('firebase_uid') }}" required
                           class="flex-1 px-4 py-2 border rounded-l-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="Enter Firebase UID">
                    <button type="button" id="generateUid" 
                            class="px-4 py-2 bg-gray-500 text-white rounded-r-lg hover:bg-gray-600 transition-colors"
                            title="Generate sample UID">
                        <i class="fas fa-magic"></i>
                    </button>
                </div>
                @error('firebase_uid')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                    Full Name <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" id="name" 
                       value="{{ old('name') }}" required
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
                       value="{{ old('email') }}" required
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
                       value="{{ old('phone') }}"
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
                       value="{{ old('date_of_birth') }}"
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
                    <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Male</option>
                    <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female</option>
                    <option value="other" {{ old('gender') === 'other' ? 'selected' : '' }}>Other</option>
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
                          placeholder="Enter street address">{{ old('address') }}</textarea>
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
                           value="{{ old('city') }}"
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
                           value="{{ old('state') }}"
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
                           value="{{ old('postal_code') }}"
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
                           value="{{ old('country', 'USA') }}"
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
                       value="{{ old('license_number') }}" required
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
                       value="{{ old('license_expiry') }}" required
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
                        <option value="{{ $key }}" {{ old('license_class') === $key ? 'selected' : '' }}>
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
                        <option value="{{ $key }}" {{ old('license_type') === $key ? 'selected' : '' }}>
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
                       value="{{ old('issuing_state') }}"
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                       placeholder="Enter license issuing state">
                @error('issuing_state')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <!-- Vehicle Information (Optional) -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-car mr-2 text-primary"></i>Vehicle Information
            </h3>
            <div class="flex items-center">
                <input type="checkbox" id="add_vehicle" name="add_vehicle" value="1" 
                       {{ old('add_vehicle') ? 'checked' : '' }}
                       class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                <label for="add_vehicle" class="ml-2 text-sm font-medium text-gray-700">
                    Add vehicle information
                </label>
            </div>
        </div>
        
        <div id="vehicle_section" class="space-y-6" style="display: {{ old('add_vehicle') ? 'block' : 'none' }};">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="vehicle_make" class="block text-sm font-medium text-gray-700 mb-2">
                        Make
                    </label>
                    <input type="text" name="vehicle_make" id="vehicle_make" 
                           value="{{ old('vehicle_make') }}"
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
                           value="{{ old('vehicle_model') }}"
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
                            <option value="{{ $year }}" {{ old('vehicle_year') == $year ? 'selected' : '' }}>
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
                           value="{{ old('vehicle_color') }}"
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
                           value="{{ old('vehicle_license_plate') }}"
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
                           value="{{ old('vehicle_vin') }}"
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
                            <option value="{{ $seats }}" {{ old('vehicle_seats', '4') == $seats ? 'selected' : '' }}>
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
                            <option value="{{ $key }}" {{ old('vehicle_type') === $key ? 'selected' : '' }}>
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
                            <option value="{{ $key }}" {{ old('fuel_type') === $key ? 'selected' : '' }}>
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
                               value="{{ old('registration_number') }}"
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
                               value="{{ old('registration_expiry') }}"
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
                               value="{{ old('insurance_provider') }}"
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
                               value="{{ old('insurance_policy_number') }}"
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
                               value="{{ old('insurance_expiry') }}"
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

    <!-- Document Upload Section -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-upload mr-2 text-primary"></i>Document Upload
            <span class="text-sm font-normal text-gray-500">(Optional - can be added later)</span>
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Profile Photo -->
            <div>
                <label for="profile_photo" class="block text-sm font-medium text-gray-700 mb-2">
                    Profile Photo
                    <span class="text-gray-500 text-xs">(JPEG, PNG - Max 5MB)</span>
                </label>
                <div class="flex items-center space-x-4">
                    <input type="file" name="profile_photo" id="profile_photo" 
                           accept="image/jpeg,image/png,image/jpg"
                           onchange="handleFileUpload(this, 'profile_photo_preview')"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <div id="profile_photo_preview" class="mt-2 text-sm text-gray-600"></div>
                </div>
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
        
        <div class="mt-4 p-3 bg-blue-50 rounded-lg">
            <p class="text-sm text-blue-800">
                <i class="fas fa-info-circle mr-2"></i>
                Documents can be uploaded now or added later from the driver's profile page. All documents are encrypted and stored securely.
            </p>
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
                        <option value="{{ $key }}" {{ old('status', 'pending') === $key ? 'selected' : '' }}>
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
                        <option value="{{ $key }}" {{ old('verification_status', 'pending') === $key ? 'selected' : '' }}>
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
                        <option value="{{ $key }}" {{ old('availability_status', 'offline') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('availability_status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
        
        <!-- Status Help Text -->
        <div class="mt-4 p-4 bg-blue-50 rounded-lg">
            <h4 class="text-sm font-medium text-blue-800 mb-2">Status Guidelines:</h4>
            <ul class="text-sm text-blue-700 space-y-1">
                <li><strong>Driver Status:</strong> Controls overall account access and functionality</li>
                <li><strong>Verification Status:</strong> Indicates document and background check completion</li>
                <li><strong>Availability Status:</strong> Current working status for ride acceptance</li>
            </ul>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex justify-between items-center">
            <div class="text-sm text-gray-600">
                <i class="fas fa-info-circle mr-1"></i>
                All required fields must be completed before saving
            </div>
            <div class="flex space-x-4">
                <a href="{{ route('admin.drivers.index') }}" 
                   class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
                <button type="submit" id="submitBtn"
                        class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Create Driver
                </button>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Generate Firebase UID button
        document.getElementById('generateUid').addEventListener('click', function() {
            const uid = 'driver_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            document.getElementById('firebase_uid').value = uid;
        });
        
        // Auto-generate Firebase UID if empty
        const firebaseUidInput = document.getElementById('firebase_uid');
        if (!firebaseUidInput.value) {
            const uid = 'driver_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            firebaseUidInput.value = uid;
        }
        
        // Vehicle section toggle
        const addVehicleCheckbox = document.getElementById('add_vehicle');
        const vehicleSection = document.getElementById('vehicle_section');
        
        addVehicleCheckbox.addEventListener('change', function() {
            if (this.checked) {
                vehicleSection.style.display = 'block';
                updateProgressStep(2, true);
            } else {
                vehicleSection.style.display = 'none';
                updateProgressStep(2, false);
                // Clear vehicle form data
                clearVehicleForm();
            }
        });
        
        // Real-time validation
        const form = document.getElementById('driverForm');
        const submitBtn = document.getElementById('submitBtn');
        
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
        
        // Date of birth validation
        const dobField = document.getElementById('date_of_birth');
        dobField.addEventListener('change', function() {
            if (this.value) {
                const birthDate = new Date(this.value);
                const today = new Date();
                const age = Math.floor((today - birthDate) / (365.25 * 24 * 60 * 60 * 1000));
                
                if (age < 18) {
                    this.classList.add('border-red-500');
                    showFieldError(this, 'Driver must be at least 18 years old');
                } else {
                    this.classList.remove('border-red-500');
                    hideFieldError(this);
                }
            }
        });
        
        // License expiry validation
        const licenseExpiryField = document.getElementById('license_expiry');
        licenseExpiryField.addEventListener('change', function() {
            if (this.value) {
                const expiryDate = new Date(this.value);
                const today = new Date();
                
                if (expiryDate <= today) {
                    this.classList.add('border-red-500');
                    showFieldError(this, 'License expiry date must be in the future');
                } else {
                    this.classList.remove('border-red-500');
                    hideFieldError(this);
                }
            }
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
        
        // License plate formatting
        const licensePlateField = document.getElementById('vehicle_license_plate');
        if (licensePlateField) {
            licensePlateField.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
        }
        
        // Insurance/Registration expiry validation
        const expiryFields = ['insurance_expiry', 'registration_expiry'];
        expiryFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.addEventListener('change', function() {
                    if (this.value) {
                        const expiryDate = new Date(this.value);
                        const today = new Date();
                        
                        if (expiryDate <= today) {
                            this.classList.add('border-red-500');
                            showFieldError(this, 'Expiry date must be in the future');
                        } else {
                            this.classList.remove('border-red-500');
                            hideFieldError(this);
                        }
                    }
                });
            }
        });
        
        // Form submission validation
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const errors = [];
            
            // Check required fields
            const requiredFields = form.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('border-red-500');
                    errors.push(`${getFieldLabel(field)} is required`);
                } else {
                    field.classList.remove('border-red-500');
                }
            });
            
            // Additional validations
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailField.value && !emailRegex.test(emailField.value)) {
                isValid = false;
                errors.push('Please enter a valid email address');
            }
            
            if (dobField.value) {
                const age = Math.floor((new Date() - new Date(dobField.value)) / (365.25 * 24 * 60 * 60 * 1000));
                if (age < 18) {
                    isValid = false;
                    errors.push('Driver must be at least 18 years old');
                }
            }
            
            if (licenseExpiryField.value && new Date(licenseExpiryField.value) <= new Date()) {
                isValid = false;
                errors.push('License expiry date must be in the future');
            }
            
            // Vehicle validation if enabled
            if (addVehicleCheckbox.checked) {
                const vehicleMake = document.getElementById('vehicle_make');
                const vehicleModel = document.getElementById('vehicle_model');
                const vehicleYear = document.getElementById('vehicle_year');
                
                if (!vehicleMake.value.trim()) {
                    isValid = false;
                    errors.push('Vehicle make is required when adding vehicle');
                    vehicleMake.classList.add('border-red-500');
                }
                
                if (!vehicleModel.value.trim()) {
                    isValid = false;
                    errors.push('Vehicle model is required when adding vehicle');
                    vehicleModel.classList.add('border-red-500');
                }
                
                if (!vehicleYear.value) {
                    isValid = false;
                    errors.push('Vehicle year is required when adding vehicle');
                    vehicleYear.classList.add('border-red-500');
                }
            }
            
            if (!isValid) {
                e.preventDefault();
                showErrorSummary(errors);
                return false;
            }
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Creating Driver...';
        });
        
        // Progress step management
        function updateProgressStep(step, active) {
            const steps = document.querySelectorAll('.w-8.h-8');
            const stepTexts = document.querySelectorAll('.text-sm.font-medium');
            
            if (steps[step - 1] && stepTexts[step - 1]) {
                if (active) {
                    steps[step - 1].classList.remove('bg-gray-200', 'text-gray-600');
                    steps[step - 1].classList.add('bg-primary', 'text-white');
                    stepTexts[step - 1].classList.remove('text-gray-500');
                    stepTexts[step - 1].classList.add('text-gray-900');
                } else {
                    steps[step - 1].classList.remove('bg-primary', 'text-white');
                    steps[step - 1].classList.add('bg-gray-200', 'text-gray-600');
                    stepTexts[step - 1].classList.remove('text-gray-900');
                    stepTexts[step - 1].classList.add('text-gray-500');
                }
            }
        }
        
        // Clear vehicle form
        function clearVehicleForm() {
            const vehicleFields = [
                'vehicle_make', 'vehicle_model', 'vehicle_year', 'vehicle_color',
                'vehicle_license_plate', 'vehicle_vin', 'vehicle_seats', 
                'vehicle_type', 'fuel_type', 'registration_number', 
                'registration_expiry', 'insurance_provider', 
                'insurance_policy_number', 'insurance_expiry'
            ];
            
            vehicleFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.value = '';
                    field.classList.remove('border-red-500', 'border-yellow-500');
                    hideFieldError(field);
                }
            });
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
            form.insertAdjacentHTML('beforebegin', errorHtml);
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        console.log('Enhanced driver create form initialized');
    });

    // File upload handling function
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

    // Handle multiple file uploads (for vehicle photos)
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
                // Clear the input to prevent submission of invalid files
                setTimeout(() => {
                    input.value = '';
                    previewElement.innerHTML = '<span class="text-red-600">Please select valid files only</span>';
                }, 3000);
            }
        } else {
            previewElement.innerHTML = '';
        }
    }

    // Format file size helper
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
</script>
@endpush