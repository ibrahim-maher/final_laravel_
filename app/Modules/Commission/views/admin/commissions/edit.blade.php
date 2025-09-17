{{-- resources/views/commission/admin/commissions/edit.blade.php --}}

@extends('admin::layouts.admin')

@section('title', 'Edit Commission')
@section('page-title', 'Edit Commission')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-primary">Edit Commission</h1>
        <p class="text-gray-600 mt-1">Update commission structure and payout settings</p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('commissions.show', $commission->id) }}"
            class="bg-info text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors">
            <i class="fas fa-eye mr-2"></i>View Details
        </a>
        <a href="{{ route('commissions.index') }}"
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

<form method="POST" action="{{ route('commissions.update', $commission->id) }}" class="space-y-6">
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
                    Commission Name <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" id="name"
                    value="{{ old('name', $commission->name) }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Enter commission name" required>
                @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="priority_order" class="block text-sm font-medium text-gray-700 mb-2">
                    Priority Order
                </label>
                <input type="number" name="priority_order" id="priority_order"
                    value="{{ old('priority_order', $commission->priority_order) }}"
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
                placeholder="Enter commission description">{{ old('description', $commission->description) }}</textarea>
            @error('description')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <!-- Commission Configuration -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-percentage mr-2 text-green-500"></i>Commission Configuration
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="commission_type" class="block text-sm font-medium text-gray-700 mb-2">
                    Commission Type <span class="text-red-500">*</span>
                </label>
                <select name="commission_type" id="commission_type"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    onchange="updateCommissionFields()" required>
                    @foreach($commissionTypes as $key => $label)
                    <option value="{{ $key }}" {{ old('commission_type', $commission->commission_type) === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                    @endforeach
                </select>
                @error('commission_type')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="recipient_type" class="block text-sm font-medium text-gray-700 mb-2">
                    Recipient Type <span class="text-red-500">*</span>
                </label>
                <select name="recipient_type" id="recipient_type"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                    @foreach($recipientTypes as $key => $label)
                    <option value="{{ $key }}" {{ old('recipient_type', $commission->recipient_type) === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                    @endforeach
                </select>
                @error('recipient_type')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
            <div>
                <label for="calculation_method" class="block text-sm font-medium text-gray-700 mb-2">
                    Calculation Method <span class="text-red-500">*</span>
                </label>
                <select name="calculation_method" id="calculation_method"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                    @foreach($calculationMethods as $key => $label)
                    <option value="{{ $key }}" {{ old('calculation_method', $commission->calculation_method) === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                    @endforeach
                </select>
                @error('calculation_method')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="applicable_to" class="block text-sm font-medium text-gray-700 mb-2">
                    Applicable To <span class="text-red-500">*</span>
                </label>
                <select name="applicable_to" id="applicable_to"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                    @foreach($applicableToOptions as $key => $label)
                    <option value="{{ $key }}" {{ old('applicable_to', $commission->applicable_to) === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                    @endforeach
                </select>
                @error('applicable_to')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
            <div id="rate_field" style="display: none;">
                <label for="rate" class="block text-sm font-medium text-gray-700 mb-2">
                    Commission Rate (%) <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="number" name="rate" id="rate" step="0.0001" min="0" max="100"
                        value="{{ old('rate', $commission->rate) }}"
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
                        value="{{ old('fixed_amount', $commission->fixed_amount) }}"
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
                <label for="minimum_commission" class="block text-sm font-medium text-gray-700 mb-2">
                    Minimum Commission ($)
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-2 text-gray-500">$</span>
                    <input type="number" name="minimum_commission" id="minimum_commission" step="0.01" min="0"
                        value="{{ old('minimum_commission', $commission->minimum_commission) }}"
                        class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="0.00">
                </div>
                @error('minimum_commission')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="maximum_commission" class="block text-sm font-medium text-gray-700 mb-2">
                    Maximum Commission ($)
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-2 text-gray-500">$</span>
                    <input type="number" name="maximum_commission" id="maximum_commission" step="0.01" min="0"
                        value="{{ old('maximum_commission', $commission->maximum_commission) }}"
                        class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="0.00">
                </div>
                @error('maximum_commission')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <!-- Payout Settings -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-credit-card mr-2 text-purple-500"></i>Payout Settings
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="payment_frequency" class="block text-sm font-medium text-gray-700 mb-2">
                    Payment Frequency <span class="text-red-500">*</span>
                </label>
                <select name="payment_frequency" id="payment_frequency"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                    @foreach($paymentFrequencies as $key => $label)
                    <option value="{{ $key }}" {{ old('payment_frequency', $commission->payment_frequency) === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                    @endforeach
                </select>
                @error('payment_frequency')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="minimum_payout_amount" class="block text-sm font-medium text-gray-700 mb-2">
                    Minimum Payout Amount ($)
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-2 text-gray-500">$</span>
                    <input type="number" name="minimum_payout_amount" id="minimum_payout_amount" step="0.01" min="0"
                        value="{{ old('minimum_payout_amount', $commission->minimum_payout_amount ?? '10.00') }}"
                        class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="10.00">
                </div>
                @error('minimum_payout_amount')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-4">
            <div class="flex items-center">
                <input type="checkbox" name="auto_payout" id="auto_payout" value="1"
                    {{ old('auto_payout', $commission->auto_payout) ? 'checked' : '' }}
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <label for="auto_payout" class="ml-2 block text-sm text-gray-900">
                    Enable automatic payouts
                </label>
            </div>
            <p class="mt-1 text-xs text-gray-500">When enabled, payouts will be processed automatically based on the payment frequency</p>
        </div>
    </div>

    <!-- Tier Settings -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-layer-group mr-2 text-indigo-500"></i>Tier Settings
        </h3>

        <div class="mb-4">
            <div class="flex items-center">
                <input type="checkbox" name="tier_based" id="tier_based" value="1"
                    {{ old('tier_based', $commission->tier_based) ? 'checked' : '' }}
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                    onchange="toggleTierRules()">
                <label for="tier_based" class="ml-2 block text-sm text-gray-900">
                    Enable tier-based commission calculation
                </label>
            </div>
            <p class="mt-1 text-xs text-gray-500">Use different commission rates based on amount tiers</p>
        </div>

        <div id="tier_rules_section" style="display: {{ old('tier_based', $commission->tier_based) ? 'block' : 'none' }};">
            <label for="tier_rules_text" class="block text-sm font-medium text-gray-700 mb-2">
                Tier Rules (JSON format)
            </label>
            <textarea name="tier_rules_text" id="tier_rules_text" rows="6"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm"
                placeholder='[{"min_amount": 0, "max_amount": 100, "rate": 5, "type": "percentage"}, {"min_amount": 100, "max_amount": 500, "rate": 7.5, "type": "percentage"}]'>{{ old('tier_rules_text', is_array($commission->tier_rules) ? json_encode($commission->tier_rules, JSON_PRETTY_PRINT) : $commission->tier_rules) }}</textarea>
            <input type="hidden" name="tier_rules" id="tier_rules">
            <p class="mt-1 text-xs text-gray-500">Define tier rules in JSON format with min_amount, max_amount, rate, and type fields</p>
        </div>
    </div>

    <!-- Applicability Settings -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-filter mr-2 text-orange-500"></i>Applicability Settings
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="applicable_zones" class="block text-sm font-medium text-gray-700 mb-2">
                    Applicable Zones
                </label>
                <input type="text" name="applicable_zones_text" id="applicable_zones_text"
                    value="{{ old('applicable_zones_text', is_array($commission->applicable_zones) ? implode(', ', $commission->applicable_zones) : '') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="zone_1, zone_2, zone_3">
                <p class="mt-1 text-xs text-gray-500">Enter zone IDs separated by commas (leave empty for all zones)</p>
            </div>

            <div>
                <label for="excluded_zones" class="block text-sm font-medium text-gray-700 mb-2">
                    Excluded Zones
                </label>
                <input type="text" name="excluded_zones_text" id="excluded_zones_text"
                    value="{{ old('excluded_zones_text', is_array($commission->excluded_zones) ? implode(', ', $commission->excluded_zones) : '') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="zone_4, zone_5">
                <p class="mt-1 text-xs text-gray-500">Enter zone IDs to exclude (leave empty for no exclusions)</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
            <div>
                <label for="applicable_vehicle_types" class="block text-sm font-medium text-gray-700 mb-2">
                    Applicable Vehicle Types
                </label>
                <input type="text" name="applicable_vehicle_types_text" id="applicable_vehicle_types_text"
                    value="{{ old('applicable_vehicle_types_text', is_array($commission->applicable_vehicle_types) ? implode(', ', $commission->applicable_vehicle_types) : '') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="sedan, suv, van">
                <p class="mt-1 text-xs text-gray-500">Enter vehicle types separated by commas (leave empty for all types)</p>
            </div>

            <div>
                <label for="excluded_vehicle_types" class="block text-sm font-medium text-gray-700 mb-2">
                    Excluded Vehicle Types
                </label>
                <input type="text" name="excluded_vehicle_types_text" id="excluded_vehicle_types_text"
                    value="{{ old('excluded_vehicle_types_text', is_array($commission->excluded_vehicle_types) ? implode(', ', $commission->excluded_vehicle_types) : '') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="motorcycle, bicycle">
                <p class="mt-1 text-xs text-gray-500">Enter vehicle types to exclude (leave empty for no exclusions)</p>
            </div>
        </div>
    </div>

    <!-- Date Settings -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-calendar mr-2 text-red-500"></i>Date Settings & Status
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="starts_at" class="block text-sm font-medium text-gray-700 mb-2">
                    Start Date
                </label>
                <input type="datetime-local" name="starts_at" id="starts_at"
                    value="{{ old('starts_at', $commission->starts_at ? $commission->starts_at->format('Y-m-d\TH:i') : '') }}"
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
                    value="{{ old('expires_at', $commission->expires_at ? $commission->expires_at->format('Y-m-d\TH:i') : '') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                @error('expires_at')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-4">
            <div class="flex items-center">
                <input type="checkbox" name="is_active" id="is_active" value="1"
                    {{ old('is_active', $commission->is_active) ? 'checked' : '' }}
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <label for="is_active" class="ml-2 block text-sm text-gray-900">
                    Active (commission is enabled)
                </label>
            </div>
        </div>
    </div>

    <!-- Firebase Sync Option -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-cloud-upload-alt mr-2 text-yellow-500"></i>Firebase Sync
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
        <a href="{{ route('commissions.index') }}"
            class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
            Cancel
        </a>
        <button type="submit"
            class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
            <i class="fas fa-save mr-2"></i>Update Commission
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        updateCommissionFields();
        toggleTierRules();
        setupArrayFieldHandlers();
    });

    function updateCommissionFields() {
        const commissionType = document.getElementById('commission_type').value;
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

        // Show appropriate fields based on commission type
        switch (commissionType) {
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

    function toggleTierRules() {
        const tierBased = document.getElementById('tier_based').checked;
        const tierRulesSection = document.getElementById('tier_rules_section');

        if (tierBased) {
            tierRulesSection.style.display = 'block';
        } else {
            tierRulesSection.style.display = 'none';
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

            if (textInput) {
                textInput.addEventListener('input', function() {
                    const values = this.value.split(',').map(v => v.trim()).filter(v => v);

                    // Remove existing hidden inputs for this field
                    const existingInputs = document.querySelectorAll(`input[name="${fieldName}[]"]`);
                    existingInputs.forEach(input => input.remove());

                    // Create individual hidden inputs for each value
                    if (values.length > 0) {
                        values.forEach(function(value, index) {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = fieldName + '[]';
                            input.value = value;
                            textInput.parentNode.appendChild(input);
                        });
                    }
                });

                // Initial conversion
                textInput.dispatchEvent(new Event('input'));
            }
        });

        // Handle tier rules - this one should remain JSON
        const tierRulesText = document.getElementById('tier_rules_text');
        const tierRulesHidden = document.getElementById('tier_rules');

        if (tierRulesText && tierRulesHidden) {
            tierRulesText.addEventListener('input', function() {
                try {
                    if (this.value.trim() === '') {
                        tierRulesHidden.value = '';
                        this.setCustomValidity('');
                        return;
                    }

                    const parsed = JSON.parse(this.value);
                    tierRulesHidden.value = JSON.stringify(parsed);
                    this.setCustomValidity('');
                } catch (e) {
                    this.setCustomValidity('Invalid JSON format');
                }
            });
        }
    }
</script>
@endpush