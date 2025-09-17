{{-- app/Modules/TaxSetting/views/admin/tax_settings/show.blade.php --}}

@extends('admin::layouts.admin')

@section('title', 'Tax Setting Details')
@section('page-title', 'Tax Setting Details')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-primary">Tax Setting Details</h1>
        <p class="text-gray-600 mt-1">View details for {{ $taxSetting->name }}</p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('tax-settings.edit', $taxSetting->id) }}"
            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            <i class="fas fa-edit mr-2"></i>Edit Tax Setting
        </a>
        <a href="{{ route('tax-settings.index') }}"
            class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back to List
        </a>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm border p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
        <i class="fas fa-info-circle mr-2 text-blue-500"></i>Basic Information
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tax Setting Name</label>
            <p class="text-gray-900">{{ $taxSetting->name }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Priority Order</label>
            <p class="text-gray-900">{{ $taxSetting->priority_order ?? 'Not set' }}</p>
        </div>
    </div>
    <div class="mt-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
        <p class="text-gray-900">{{ $taxSetting->description ?? 'No description provided' }}</p>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm border p-6 mt-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
        <i class="fas fa-calculator mr-2 text-green-500"></i>Tax Configuration
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tax Type</label>
            <p class="text-gray-900">{{ ucfirst($taxSetting->tax_type) }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Calculation Method</label>
            <p class="text-gray-900">{{ ucfirst($taxSetting->calculation_method) }}</p>
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tax Rate (%)</label>
            <p class="text-gray-900">{{ $taxSetting->rate ? number_format($taxSetting->rate, 4) . '%' : 'Not applicable' }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Fixed Amount ($)</label>
            <p class="text-gray-900">{{ $taxSetting->fixed_amount ? '$' . number_format($taxSetting->fixed_amount, 2) : 'Not applicable' }}</p>
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Minimum Taxable Amount ($)</label>
            <p class="text-gray-900">{{ $taxSetting->minimum_taxable_amount ? '$' . number_format($taxSetting->minimum_taxable_amount, 2) : 'Not set' }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Maximum Tax Amount ($)</label>
            <p class="text-gray-900">{{ $taxSetting->maximum_tax_amount ? '$' . number_format($taxSetting->maximum_tax_amount, 2) : 'Not set' }}</p>
        </div>
    </div>
    <div class="mt-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Tax Inclusive</label>
        <p class="text-gray-900">{{ $taxSetting->is_inclusive ? 'Yes' : 'No' }}</p>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm border p-6 mt-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
        <i class="fas fa-filter mr-2 text-purple-500"></i>Applicability Settings
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Applicable To</label>
            <p class="text-gray-900">{{ ucfirst(str_replace('_', ' ', $taxSetting->applicable_to)) }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <p class="text-gray-900">{{ $taxSetting->is_active ? 'Active' : 'Inactive' }}</p>
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Applicable Zones</label>
            <p class="text-gray-900">{{ is_array($taxSetting->applicable_zones) && !empty($taxSetting->applicable_zones) ? implode(', ', $taxSetting->applicable_zones) : 'All zones' }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Excluded Zones</label>
            <p class="text-gray-900">{{ is_array($taxSetting->excluded_zones) && !empty($taxSetting->excluded_zones) ? implode(', ', $taxSetting->excluded_zones) : 'None' }}</p>
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Applicable Vehicle Types</label>
            <p class="text-gray-900">{{ is_array($taxSetting->applicable_vehicle_types) && !empty($taxSetting->applicable_vehicle_types) ? implode(', ', $taxSetting->applicable_vehicle_types) : 'All vehicle types' }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Excluded Vehicle Types</label>
            <p class="text-gray-900">{{ is_array($taxSetting->excluded_vehicle_types) && !empty($taxSetting->excluded_vehicle_types) ? implode(', ', $taxSetting->excluded_vehicle_types) : 'None' }}</p>
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Applicable Services</label>
            <p class="text-gray-900">{{ is_array($taxSetting->applicable_services) && !empty($taxSetting->applicable_services) ? implode(', ', $taxSetting->applicable_services) : 'All services' }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Excluded Services</label>
            <p class="text-gray-900">{{ is_array($taxSetting->excluded_services) && !empty($taxSetting->excluded_services) ? implode(', ', $taxSetting->excluded_services) : 'None' }}</p>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm border p-6 mt-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
        <i class="fas fa-calendar mr-2 text-red-500"></i>Date Settings
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
            <p class="text-gray-900">{{ $taxSetting->starts_at ? $taxSetting->starts_at->format('Y-m-d H:i') : 'Not set' }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
            <p class="text-gray-900">{{ $taxSetting->expires_at ? $taxSetting->expires_at->format('Y-m-d H:i') : 'Not set' }}</p>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm border p-6 mt-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
        <i class="fas fa-cloud-upload-alt mr-2 text-orange-500"></i>Firebase Sync Status
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Synced to Firebase</label>
            <p class="text-gray-900">{{ $taxSetting->firebase_synced ? 'Yes' : 'No' }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Last Synced At</label>
            <p class="text-gray-900">{{ $taxSetting->firebase_synced_at ? $taxSetting->firebase_synced_at->format('Y-m-d H:i') : 'Never' }}</p>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm border p-6 mt-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
        <i class="fas fa-user mr-2 text-gray-500"></i>Metadata
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Created By</label>
            <p class="text-gray-900">{{ $taxSetting->created_by ?? 'Unknown' }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Updated By</label>
            <p class="text-gray-900">{{ $taxSetting->updated_by ?? 'Unknown' }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Created At</label>
            <p class="text-gray-900">{{ $taxSetting->created_at->format('Y-m-d H:i') }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Updated At</label>
            <p class="text-gray-900">{{ $taxSetting->updated_at->format('Y-m-d H:i') }}</p>
        </div>
    </div>
</div>
@endsection