{{-- app/Modules/Commission/views/admin/commissions/show.blade.php --}}

@extends('admin::layouts.admin')

@section('title', 'Commission Details')
@section('page-title', 'Commission Details')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-primary">Commission Details</h1>
        <p class="text-gray-600 mt-1">View details for {{ $commission->name }}</p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('commissions.edit', $commission->id) }}"
            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            <i class="fas fa-edit mr-2"></i>Edit Commission
        </a>
        <a href="{{ route('commissions.index') }}"
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
            <label class="block text-sm font-medium text-gray-700 mb-1">Commission Name</label>
            <p class="text-gray-900">{{ $commission->name }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Priority Order</label>
            <p class="text-gray-900">{{ $commission->priority_order ?? 'Not set' }}</p>
        </div>
    </div>
    <div class="mt-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
        <p class="text-gray-900">{{ $commission->description ?? 'No description provided' }}</p>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm border p-6 mt-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
        <i class="fas fa-percentage mr-2 text-green-500"></i>Commission Configuration
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Commission Type</label>
            <p class="text-gray-900">{{ ucfirst($commission->commission_type) }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Recipient Type</label>
            <p class="text-gray-900">{{ ucfirst($commission->recipient_type) }}</p>
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Calculation Method</label>
            <p class="text-gray-900">{{ ucfirst($commission->calculation_method) }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Applicable To</label>
            <p class="text-gray-900">{{ ucfirst(str_replace('_', ' ', $commission->applicable_to)) }}</p>
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Rate (%)</label>
            <p class="text-gray-900">{{ $commission->rate ? number_format($commission->rate, 4) . '%' : 'Not applicable' }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Fixed Amount ($)</label>
            <p class="text-gray-900">{{ $commission->fixed_amount ? '$' . number_format($commission->fixed_amount, 2) : 'Not applicable' }}</p>
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Payment Frequency</label>
            <p class="text-gray-900">{{ ucfirst($commission->payment_frequency) }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Auto Payout</label>
            <p class="text-gray-900">{{ $commission->auto_payout ? 'Enabled' : 'Disabled' }}</p>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm border p-6 mt-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
        <i class="fas fa-filter mr-2 text-purple-500"></i>Applicability Settings
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <p class="text-gray-900">{{ $commission->is_active ? 'Active' : 'Inactive' }}</p>
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
            <p class="text-gray-900">{{ $commission->starts_at ? $commission->starts_at->format('Y-m-d H:i') : 'Not set' }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
            <p class="text-gray-900">{{ $commission->expires_at ? $commission->expires_at->format('Y-m-d H:i') : 'Not set' }}</p>
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
            <p class="text-gray-900">{{ $commission->firebase_synced ? 'Yes' : 'No' }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Last Synced At</label>
            <p class="text-gray-900">{{ $commission->firebase_synced_at ? $commission->firebase_synced_at->format('Y-m-d H:i') : 'Never' }}</p>
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
            <p class="text-gray-900">{{ $commission->created_by ?? 'Unknown' }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Updated By</label>
            <p class="text-gray-900">{{ $commission->updated_by ?? 'Unknown' }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Created At</label>
            <p class="text-gray-900">{{ $commission->created_at->format('Y-m-d H:i') }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Updated At</label>
            <p class="text-gray-900">{{ $commission->updated_at->format('Y-m-d H:i') }}</p>
        </div>
    </div>
</div>
@endsection