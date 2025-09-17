@extends('admin::layouts.admin')

@section('title', 'Edit Vehicle')
@section('page-title', 'Edit Vehicle')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-primary">Edit Vehicle</h1>
        <p class="text-gray-600 mt-1">{{ ($vehicle->year ?? 'Unknown') }} {{ ($vehicle->make ?? 'Unknown') }} {{ ($vehicle->model ?? 'Model') }} - {{ $vehicle->license_plate ?? 'No Plate' }}</p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('admin.vehicles.show', $vehicle->id) }}"
            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            <i class="fas fa-eye mr-2"></i>View Details
        </a>
        <a href="{{ route('admin.vehicles.index') }}"
            class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back to Vehicles
        </a>
    </div>
</div>

@if ($errors->any())
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
    <div class="font-bold">Please fix the following errors:</div>
    <ul class="mt-2 list-disc list-inside">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('admin.vehicles.update', $vehicle->id) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <!-- Vehicle Basic Information -->
    <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">
            <i class="fas fa-car mr-2 text-primary"></i>Basic Vehicle Information
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Driver Selection -->
            <div class="md:col-span-2 lg:col-span-3">
                <label for="driver_firebase_uid" class="block text-sm font-medium text-gray-700 mb-2">
                    Driver <span class="text-red-500">*</span>
                </label>
                <select name="driver_firebase_uid" id="driver_firebase_uid" required
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('driver_firebase_uid') border-red-500 @enderror">
                    <option value="">Select a driver...</option>
                    @if(isset($drivers))
                    @foreach($drivers as $driver)
                    <option value="{{ $driver->firebase_uid }}"
                        {{ (old('driver_firebase_uid', $vehicle->driver_firebase_uid) == $driver->firebase_uid) ? 'selected' : '' }}>
                        {{ $driver->name }} ({{ $driver->email }})
                    </option>
                    @endforeach
                    @endif
                </select>
                @error('driver_firebase_uid')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Make -->
            <div>
                <label for="make" class="block text-sm font-medium text-gray-700 mb-2">
                    Make <span class="text-red-500">*</span>
                </label>
                <input type="text" name="make" id="make" required
                    value="{{ old('make', $vehicle->make) }}"
                    placeholder="e.g., Toyota, Honda, Ford"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('make') border-red-500 @enderror">
                @error('make')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Model -->
            <div>
                <label for="model" class="block text-sm font-medium text-gray-700 mb-2">
                    Model <span class="text-red-500">*</span>
                </label>
                <input type="text" name="model" id="model" required
                    value="{{ old('model', $vehicle->model) }}"
                    placeholder="e.g., Camry, Civic, Focus"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('model') border-red-500 @enderror">
                @error('model')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Year -->
            <div>
                <label for="year" class="block text-sm font-medium text-gray-700 mb-2">
                    Year <span class="text-red-500">*</span>
                </label>
                <input type="number" name="year" id="year" required
                    value="{{ old('year', $vehicle->year) }}"
                    min="1990" max="{{ date('Y') + 1 }}"
                    placeholder="e.g., {{ date('Y') }}"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('year') border-red-500 @enderror">
                @error('year')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Color -->
            <div>
                <label for="color" class="block text-sm font-medium text-gray-700 mb-2">
                    Color
                </label>
                <input type="text" name="color" id="color"
                    value="{{ old('color', $vehicle->color) }}"
                    placeholder="e.g., White, Black, Silver"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('color') border-red-500 @enderror">
                @error('color')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- License Plate -->
            <div>
                <label for="license_plate" class="block text-sm font-medium text-gray-700 mb-2">
                    License Plate <span class="text-red-500">*</span>
                </label>
                <input type="text" name="license_plate" id="license_plate" required
                    value="{{ old('license_plate', $vehicle->license_plate) }}"
                    placeholder="e.g., ABC123"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('license_plate') border-red-500 @enderror">
                @error('license_plate')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- VIN -->
            <div>
                <label for="vin" class="block text-sm font-medium text-gray-700 mb-2">
                    VIN (Vehicle Identification Number)
                </label>
                <input type="text" name="vin" id="vin"
                    value="{{ old('vin', $vehicle->vin) }}"
                    maxlength="17"
                    placeholder="17-character VIN"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('vin') border-red-500 @enderror">
                @error('vin')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <!-- Vehicle Specifications -->
    <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">
            <i class="fas fa-cogs mr-2 text-primary"></i>Vehicle Specifications
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Vehicle Type -->
            <div>
                <label for="vehicle_type" class="block text-sm font-medium text-gray-700 mb-2">
                    Vehicle Type <span class="text-red-500">*</span>
                </label>
                <select name="vehicle_type" id="vehicle_type" required
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('vehicle_type') border-red-500 @enderror">
                    <option value="">Select type...</option>
                    <option value="sedan" {{ old('vehicle_type', $vehicle->vehicle_type) == 'sedan' ? 'selected' : '' }}>Sedan</option>
                    <option value="suv" {{ old('vehicle_type', $vehicle->vehicle_type) == 'suv' ? 'selected' : '' }}>SUV</option>
                    <option value="hatchback" {{ old('vehicle_type', $vehicle->vehicle_type) == 'hatchback' ? 'selected' : '' }}>Hatchback</option>
                    <option value="pickup" {{ old('vehicle_type', $vehicle->vehicle_type) == 'pickup' ? 'selected' : '' }}>Pickup Truck</option>
                    <option value="van" {{ old('vehicle_type', $vehicle->vehicle_type) == 'van' ? 'selected' : '' }}>Van</option>
                    <option value="motorcycle" {{ old('vehicle_type', $vehicle->vehicle_type) == 'motorcycle' ? 'selected' : '' }}>Motorcycle</option>
                    <option value="bicycle" {{ old('vehicle_type', $vehicle->vehicle_type) == 'bicycle' ? 'selected' : '' }}>Bicycle</option>
                    <option value="scooter" {{ old('vehicle_type', $vehicle->vehicle_type) == 'scooter' ? 'selected' : '' }}>Scooter</option>
                </select>
                @error('vehicle_type')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Fuel Type -->
            <div>
                <label for="fuel_type" class="block text-sm font-medium text-gray-700 mb-2">
                    Fuel Type
                </label>
                <select name="fuel_type" id="fuel_type"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('fuel_type') border-red-500 @enderror">
                    <option value="">Select fuel type...</option>
                    <option value="gasoline" {{ old('fuel_type', $vehicle->fuel_type) == 'gasoline' ? 'selected' : '' }}>Gasoline</option>
                    <option value="diesel" {{ old('fuel_type', $vehicle->fuel_type) == 'diesel' ? 'selected' : '' }}>Diesel</option>
                    <option value="electric" {{ old('fuel_type', $vehicle->fuel_type) == 'electric' ? 'selected' : '' }}>Electric</option>
                    <option value="hybrid" {{ old('fuel_type', $vehicle->fuel_type) == 'hybrid' ? 'selected' : '' }}>Hybrid</option>
                    <option value="cng" {{ old('fuel_type', $vehicle->fuel_type) == 'cng' ? 'selected' : '' }}>CNG</option>
                    <option value="lpg" {{ old('fuel_type', $vehicle->fuel_type) == 'lpg' ? 'selected' : '' }}>LPG</option>
                </select>
                @error('fuel_type')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Seats -->
            <div>
                <label for="seats" class="block text-sm font-medium text-gray-700 mb-2">
                    Number of Seats
                </label>
                <input type="number" name="seats" id="seats"
                    value="{{ old('seats', $vehicle->seats ?? 4) }}"
                    min="2" max="50"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('seats') border-red-500 @enderror">
                @error('seats')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Primary Vehicle -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Primary Vehicle
                </label>
                <div class="flex items-center">
                    <input type="checkbox" name="is_primary" id="is_primary" value="1"
                        {{ old('is_primary', $vehicle->is_primary) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-primary focus:ring-primary">
                    <label for="is_primary" class="ml-2 text-sm text-gray-700">
                        Set as primary vehicle for this driver
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- Registration & Insurance -->
    <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">
            <i class="fas fa-file-alt mr-2 text-primary"></i>Registration & Insurance
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Registration Number -->
            <div>
                <label for="registration_number" class="block text-sm font-medium text-gray-700 mb-2">
                    Registration Number
                </label>
                <input type="text" name="registration_number" id="registration_number"
                    value="{{ old('registration_number', $vehicle->registration_number) }}"
                    placeholder="Vehicle registration number"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('registration_number') border-red-500 @enderror">
                @error('registration_number')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Registration Expiry -->
            <div>
                <label for="registration_expiry" class="block text-sm font-medium text-gray-700 mb-2">
                    Registration Expiry Date
                </label>
                <input type="date" name="registration_expiry" id="registration_expiry"
                    value="{{ old('registration_expiry', $vehicle->registration_expiry ? $vehicle->registration_expiry->format('Y-m-d') : '') }}"
                    min="{{ date('Y-m-d') }}"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('registration_expiry') border-red-500 @enderror">
                @error('registration_expiry')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Insurance Provider -->
            <div>
                <label for="insurance_provider" class="block text-sm font-medium text-gray-700 mb-2">
                    Insurance Provider
                </label>
                <input type="text" name="insurance_provider" id="insurance_provider"
                    value="{{ old('insurance_provider', $vehicle->insurance_provider) }}"
                    placeholder="e.g., State Farm, Allstate"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('insurance_provider') border-red-500 @enderror">
                @error('insurance_provider')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Insurance Policy Number -->
            <div>
                <label for="insurance_policy_number" class="block text-sm font-medium text-gray-700 mb-2">
                    Insurance Policy Number
                </label>
                <input type="text" name="insurance_policy_number" id="insurance_policy_number"
                    value="{{ old('insurance_policy_number', $vehicle->insurance_policy_number) }}"
                    placeholder="Insurance policy number"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('insurance_policy_number') border-red-500 @enderror">
                @error('insurance_policy_number')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Insurance Expiry -->
            <div>
                <label for="insurance_expiry" class="block text-sm font-medium text-gray-700 mb-2">
                    Insurance Expiry Date
                </label>
                <input type="date" name="insurance_expiry" id="insurance_expiry"
                    value="{{ old('insurance_expiry', $vehicle->insurance_expiry ? $vehicle->insurance_expiry->format('Y-m-d') : '') }}"
                    min="{{ date('Y-m-d') }}"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('insurance_expiry') border-red-500 @enderror">
                @error('insurance_expiry')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <!-- Status & Verification -->
    <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">
            <i class="fas fa-shield-check mr-2 text-primary"></i>Status & Verification
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Status -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                    Status <span class="text-red-500">*</span>
                </label>
                <select name="status" id="status" required
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('status') border-red-500 @enderror">
                    <option value="active" {{ old('status', $vehicle->status) == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $vehicle->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="suspended" {{ old('status', $vehicle->status) == 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="maintenance" {{ old('status', $vehicle->status) == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                </select>
                @error('status')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Verification Status -->
            <div>
                <label for="verification_status" class="block text-sm font-medium text-gray-700 mb-2">
                    Verification Status <span class="text-red-500">*</span>
                </label>
                <select name="verification_status" id="verification_status" required
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('verification_status') border-red-500 @enderror">
                    <option value="pending" {{ old('verification_status', $vehicle->verification_status) == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="verified" {{ old('verification_status', $vehicle->verification_status) == 'verified' ? 'selected' : '' }}>Verified</option>
                    <option value="rejected" {{ old('verification_status', $vehicle->verification_status) == 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
                @error('verification_status')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <!-- Existing Documents -->
    @if(isset($documents) && count($documents) > 0)
    <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">
            <i class="fas fa-file-check mr-2 text-primary"></i>Existing Documents
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($documents as $document)
            <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center">
                        <i class="fas fa-file-alt text-2xl text-gray-400 mr-3"></i>
                        <div>
                            <h4 class="font-medium text-gray-900">{{ $document->document_name ?? 'Document' }}</h4>
                            <p class="text-sm text-gray-500">{{ ucfirst($document->document_type ?? 'unknown') }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        @if(($document->verification_status ?? 'pending') === 'verified')
                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">Verified</span>
                        @elseif(($document->verification_status ?? 'pending') === 'rejected')
                        <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs">Rejected</span>
                        @else
                        <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs">Pending</span>
                        @endif
                    </div>
                </div>
                <div class="text-sm text-gray-600 mb-3">
                    Uploaded: {{ $document->created_at ? $document->created_at->format('M d, Y') : 'Unknown' }}
                </div>
                <div class="flex gap-2">
                    @if($document->file_url ?? false)
                    <a href="{{ $document->file_url }}" target="_blank"
                        class="text-blue-600 hover:text-blue-800 text-sm">
                        <i class="fas fa-eye mr-1"></i>View
                    </a>
                    @endif
                    <button onclick="deleteDocument('{{ $document->id ?? '' }}')"
                        class="text-red-600 hover:text-red-800 text-sm">
                        <i class="fas fa-trash mr-1"></i>Delete
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Upload New Documents -->
    <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">
            <i class="fas fa-upload mr-2 text-primary"></i>Upload New Documents
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Vehicle Registration Document -->
            <div>
                <label for="vehicle_registration_doc" class="block text-sm font-medium text-gray-700 mb-2">
                    Vehicle Registration Document
                </label>
                <input type="file" name="vehicle_registration_doc" id="vehicle_registration_doc"
                    accept=".jpg,.jpeg,.png,.pdf"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('vehicle_registration_doc') border-red-500 @enderror">
                <p class="mt-1 text-xs text-gray-500">Leave empty to keep existing document. Accepted formats: JPG, PNG, PDF (Max: 5MB)</p>
                @error('vehicle_registration_doc')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Insurance Certificate -->
            <div>
                <label for="insurance_certificate" class="block text-sm font-medium text-gray-700 mb-2">
                    Insurance Certificate
                </label>
                <input type="file" name="insurance_certificate" id="insurance_certificate"
                    accept=".jpg,.jpeg,.png,.pdf"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('insurance_certificate') border-red-500 @enderror">
                <p class="mt-1 text-xs text-gray-500">Leave empty to keep existing document. Accepted formats: JPG, PNG, PDF (Max: 5MB)</p>
                @error('insurance_certificate')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Vehicle Photos -->
            <div class="md:col-span-2">
                <label for="vehicle_photos" class="block text-sm font-medium text-gray-700 mb-2">
                    Additional Vehicle Photos
                </label>
                <input type="file" name="vehicle_photos[]" id="vehicle_photos"
                    accept=".jpg,.jpeg,.png"
                    multiple
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('vehicle_photos.*') border-red-500 @enderror">
                <p class="mt-1 text-xs text-gray-500">Upload additional photos of the vehicle (JPG, PNG only, Max: 5MB each)</p>
                @error('vehicle_photos.*')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex justify-between items-center">
            <button type="button" onclick="window.history.back()"
                class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Cancel
            </button>

            <div class="flex gap-3">
                <button type="submit"
                    class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Update Vehicle
                </button>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-format license plate input
        const licensePlateInput = document.getElementById('license_plate');
        if (licensePlateInput) {
            licensePlateInput.addEventListener('input', function(e) {
                this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            });
        }

        // Auto-format VIN input
        const vinInput = document.getElementById('vin');
        if (vinInput) {
            vinInput.addEventListener('input', function(e) {
                this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 17);
            });
        }

        // File upload preview
        const fileInputs = document.querySelectorAll('input[type="file"]');
        fileInputs.forEach(input => {
            input.addEventListener('change', function(e) {
                const files = e.target.files;
                const container = e.target.parentElement;

                // Remove existing preview
                const existingPreview = container.querySelector('.file-preview');
                if (existingPreview) {
                    existingPreview.remove();
                }

                if (files.length > 0) {
                    const preview = document.createElement('div');
                    preview.className = 'file-preview mt-2 text-sm text-green-600';
                    preview.innerHTML = `<i class="fas fa-check mr-1"></i>${files.length} file(s) selected`;
                    container.appendChild(preview);
                }
            });
        });

        // Form validation
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const requiredFields = form.querySelectorAll('[required]');
                let isValid = true;

                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.classList.add('border-red-500');
                        isValid = false;
                    } else {
                        field.classList.remove('border-red-500');
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                }
            });
        }
    });

    async function deleteDocument(documentId) {
        if (!confirm('Are you sure you want to delete this document? This action cannot be undone.')) {
            return;
        }

        try {
            const response = await fetch(`{{ url('admin/documents') }}/${documentId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (response.ok && result.success) {
                showNotification('Document deleted successfully', 'success');
                // Remove the document card from the DOM
                const documentCard = event.target.closest('.border.rounded-lg');
                if (documentCard) {
                    documentCard.remove();
                }
            } else {
                showNotification('Failed to delete document: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Delete document error:', error);
            showNotification('Error deleting document: Connection failed', 'error');
        }
    }

    function showNotification(message, type = 'info') {
        const alertClass = {
            'success': 'bg-green-100 border-green-400 text-green-700',
            'error': 'bg-red-100 border-red-400 text-red-700',
            'info': 'bg-blue-100 border-blue-400 text-blue-700',
            'warning': 'bg-yellow-100 border-yellow-400 text-yellow-700'
        } [type] || 'bg-gray-100 border-gray-400 text-gray-700';

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
</script>
@endpush