{{-- resources/views/taxsetting/admin/tax-settings/edit.blade.php --}}

@extends('admin::layouts.admin')

@section('title', 'Edit Tax Setting')
@section('page-title', 'Edit Tax Setting')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-primary">Edit Tax Setting</h1>
        <p class="text-gray-600 mt-1">Update tax configuration and calculation settings</p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('tax-settings.show', $taxSetting->id) }}"
            class="bg-info text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors">
            <i class="fas fa-eye mr-2"></i>View Details
        </a>
        <a href="{{ route('tax-settings.index') }}"
            class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back to List
        </a>
    </div>
</div>

@if($errors->any())
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
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

<form method="POST" action="{{ route('tax-settings.update', $taxSetting->id) }}" class="space-y-6">
    @csrf
    @method('PUT')

    <!-- Basic Information -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-info-circle mr-2 text-blue-500"></i>Basic Information
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                    Tax Setting Name <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" id="name"
                    value="{{ old('name', $taxSetting->name) }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Enter tax setting name" required>
                @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="priority_order" class="block text-sm font-medium text-gray-700 mb-2">
                    Priority Order
                </label>
                <input type="number" name="priority_order" id="priority_order"
                    value="{{ old('priority_order', $taxSetting->priority_order) }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="1" min="1">
                @error('priority_order')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-4">
            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                Description
            </label>
            <textarea name="description" id="description" rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Enter tax setting description">{{ old('description', $taxSetting->description) }}</textarea>
            @error('description')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <!-- Tax Configuration -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-calculator mr-2 text-green-500"></i>Tax Configuration
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="tax_type" class="block text-sm font-medium text-gray-700 mb-2">
                    Tax Type <span class="text-red-500">*</span>
                </label>
                <select name="tax_type" id="tax_type"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    onchange="updateTaxFields()" required>
                    @foreach($taxTypes as $key => $label)
                    <option value="{{ $key }}" {{ old('tax_type', $taxSetting->tax_type) === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                    @endforeach
                </select>
                @error('tax_type')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="calculation_method" class="block text-sm font-medium text-gray-700 mb-2">
                    Calculation Method <span class="text-red-500">*</span>
                </label>
                <select name="calculation_method" id="calculation_method"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                    @foreach($calculationMethods as $key => $label)
                    <option value="{{ $key }}" {{ old('calculation_method', $taxSetting->calculation_method) === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                    @endforeach
                </select>
                @error('calculation_method')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
            <div id="rate_field" style="display: none;">
                <label for="rate" class="block text-sm font-medium text-gray-700 mb-2">
                    Tax Rate (%) <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="number" name="rate" id="rate" step="0.0001" min="0" max="100"
                        value="{{ old('rate', $taxSetting->rate) }}"
                        class="w-full px-3 py-2 pr-8 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="0.0000">
                    <span class="absolute right-3 top-2 text-gray-500">%</span>
                </div>
                @error('rate')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div id="fixed_amount_field" style="display: none;">
                <label for="fixed_amount" class="block text-sm font-medium text-gray-700 mb-2">
                    Fixed Amount ($) <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-2 text-gray-500">$</span>
                    <input type="number" name="fixed_amount" id="fixed_amount" step="0.01" min="0"
                        value="{{ old('fixed_amount', $taxSetting->fixed_amount) }}"
                        class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="0.00">
                </div>
                @error('fixed_amount')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
            <div>
                <label for="minimum_taxable_amount" class="block text-sm font-medium text-gray-700 mb-2">
                    Minimum Taxable Amount ($)
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-2 text-gray-500">$</span>
                    <input type="number" name="minimum_taxable_amount" id="minimum_taxable_amount" step="0.01" min="0"
                        value="{{ old('minimum_taxable_amount', $taxSetting->minimum_taxable_amount) }}"
                        class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="0.00">
                </div>
                @error('minimum_taxable_amount')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="maximum_tax_amount" class="block text-sm font-medium text-gray-700 mb-2">
                    Maximum Tax Amount ($)
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-2 text-gray-500">$</span>
                    <input type="number" name="maximum_tax_amount" id="maximum_tax_amount" step="0.01" min="0"
                        value="{{ old('maximum_tax_amount', $taxSetting->maximum_tax_amount) }}"
                        class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="0.00">
                </div>
                @error('maximum_tax_amount')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-4">
            <div class="flex items-center">
                <input type="checkbox" name="is_inclusive" id="is_inclusive" value="1"
                    {{ old('is_inclusive', $taxSetting->is_inclusive) ? 'checked' : '' }}
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <label for="is_inclusive" class="ml-2 block text-sm text-gray-900">
                    Tax is inclusive (already included in price)
                </label>
            </div>
        </div>
    </div>

    <!-- Applicability Settings -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-filter mr-2 text-purple-500"></i>Applicability Settings
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="applicable_to" class="block text-sm font-medium text-gray-700 mb-2">
                    Applicable To <span class="text-red-500">*</span>
                </label>
                <select name="applicable_to" id="applicable_to"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                    @foreach($applicableToOptions as $key => $label)
                    <option value="{{ $key }}" {{ old('applicable_to', $taxSetting->applicable_to) === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                    @endforeach
                </select>
                @error('applicable_to')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="is_active" class="block text-sm font-medium text-gray-700 mb-2">
                    Status
                </label>
                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                        {{ old('is_active', $taxSetting->is_active) ? 'checked' : '' }}
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="is_active" class="ml-2 block text-sm text-gray-900">
                        Active (tax setting is enabled)
                    </label>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
            <div>
                <label for="applicable_zones" class="block text-sm font-medium text-gray-700 mb-2">
                    Applicable Zones
                </label>
                <input type="text" name="applicable_zones_text" id="applicable_zones_text"
                    value="{{ old('applicable_zones_text', is_array($taxSetting->applicable_zones) ? implode(', ', $taxSetting->applicable_zones) : '') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="zone_1, zone_2, zone_3">
                <input type="hidden" name="applicable_zones" id="applicable_zones">
                <p class="mt-1 text-xs text-gray-500">Enter zone IDs separated by commas (leave empty for all zones)</p>
            </div>

            <div>
                <label for="excluded_zones" class="block text-sm font-medium text-gray-700 mb-2">
                    Excluded Zones
                </label>
                <input type="text" name="excluded_zones_text" id="excluded_zones_text"
                    value="{{ old('excluded_zones_text', is_array($taxSetting->excluded_zones) ? implode(', ', $taxSetting->excluded_zones) : '') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="zone_4, zone_5">
                <input type="hidden" name="excluded_zones" id="excluded_zones">
                <p class="mt-1 text-xs text-gray-500">Enter zone IDs to exclude (leave empty for no exclusions)</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
            <div>
                <label for="applicable_vehicle_types" class="block text-sm font-medium text-gray-700 mb-2">
                    Applicable Vehicle Types
                </label>
                <input type="text" name="applicable_vehicle_types_text" id="applicable_vehicle_types_text"
                    value="{{ old('applicable_vehicle_types_text', is_array($taxSetting->applicable_vehicle_types) ? implode(', ', $taxSetting->applicable_vehicle_types) : '') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="sedan, suv, van">
                <input type="hidden" name="applicable_vehicle_types" id="applicable_vehicle_types">
                <p class="mt-1 text-xs text-gray-500">Enter vehicle types separated by commas (leave empty for all types)</p>
            </div>

            <div>
                <label for="excluded_vehicle_types" class="block text-sm font-medium text-gray-700 mb-2">
                    Excluded Vehicle Types
                </label>
                <input type="text" name="excluded_vehicle_types_text" id="excluded_vehicle_types_text"
                    value="{{ old('excluded_vehicle_types_text', is_array($taxSetting->excluded_vehicle_types) ? implode(', ', $taxSetting->excluded_vehicle_types) : '') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="motorcycle, bicycle">
                <input type="hidden" name="excluded_vehicle_types" id="excluded_vehicle_types">
                <p class="mt-1 text-xs text-gray-500">Enter vehicle types to exclude (leave empty for no exclusions)</p>
            </div>
        </div>
    </div>

    <!-- Date Settings -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-calendar mr-2 text-red-500"></i>Date Settings
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="starts_at" class="block text-sm font-medium text-gray-700 mb-2">
                    Start Date
                </label>
                <input type="datetime-local" name="starts_at" id="starts_at"
                    value="{{ old('starts_at', $taxSetting->starts_at?->format('Y-m-d\TH:i')) }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                @error('starts_at')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-2">
                    End Date
                </label>
                <input type="datetime-local" name="expires_at" id="expires_at"
                    value="{{ old('expires_at', $taxSetting->expires_at?->format('Y-m-d\TH:i')) }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                @error('expires_at')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <!-- Firebase Sync Option -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-cloud-upload-alt mr-2 text-orange-500"></i>Firebase Sync
        </h3>

        <div class="flex items-center">
            <input type="checkbox" name="sync_immediately" id="sync_immediately" value="1"
                {{ old('sync_immediately') ? 'checked' : '' }}
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
            <label for="sync_immediately" class="ml-2 block text-sm text-gray-900">
                Sync immediately to Firebase (otherwise will be queued for background sync)
            </label>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="flex justify-end space-x-4">
        <a href="{{ route('tax-settings.index') }}"
            class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
            Cancel
        </a>
        <button type="submit"
            class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
            <i class="fas fa-save mr-2"></i>Update Tax Setting
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        updateTaxFields();
        setupArrayFieldHandlers();
    });

    function updateTaxFields() {
        const taxType = document.getElementById('tax_type').value;
        const rateField = document.getElementById('rate_field');
        const fixedAmountField = document.getElementById('fixed_amount_field');
        const rateInput = document.getElementById('rate');
        const fixedAmountInput = document.getElementById('fixed_amount');

        // Hide all fields first
        rateField.style.display = 'none';
        fixedAmountField.style.display = 'none';

        // Remove required attributes
        rateInput.removeAttribute('required');
        fixedAmountInput.removeAttribute('required');

        // Show appropriate fields based on tax type
        switch (taxType) {
            case 'percentage':
                rateField.style.display = 'block';
                rateInput.setAttribute('required', 'required');
                break;
            case 'fixed':
                fixedAmountField.style.display = 'block';
                fixedAmountInput.setAttribute('required', 'required');
                break;
            case 'hybrid':
                rateField.style.display = 'block';
                fixedAmountField.style.display = 'block';
                rateInput.setAttribute('required', 'required');
                fixedAmountInput.setAttribute('required', 'required');
                break;
        }
    }

    function setupArrayFieldHandlers() {
        const arrayFields = [
            'applicable_zones',
            'excluded_zones',
            'applicable_vehicle_types',
            'excluded_vehicle_types'
        ];

        arrayFields.forEach(function(fieldName) {
            const textInput = document.getElementById(fieldName + '_text');
            const hiddenInput = document.getElementById(fieldName);

            if (textInput && hiddenInput) {
                textInput.addEventListener('input', function() {
                    const values = this.value.split(',').map(v => v.trim()).filter(v => v);
                    hiddenInput.value = JSON.stringify(values);
                });

                // Initial conversion
                const values = textInput.value.split(',').map(v => v.trim()).filter(v => v);
                hiddenInput.value = JSON.stringify(values);
            }
        });
    }
</script>
@endpush