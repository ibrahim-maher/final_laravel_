{{-- resources/views/coupon/admin/coupons/create.blade.php --}}

@extends('admin::layouts.admin')

@section('title', 'Create Coupons')
@section('page-title', 'Create Coupons')

@section('content')
<div>
    <h1 class="text-3xl font-bold text-primary">Create Coupons</h1>
    <nav class="text-sm text-gray-600 mt-1">
        <a href="{{ route('admin.dashboard') }}">Dashboard</a> > 
        <a href="{{ route('coupons.index') }}">Coupons</a> > 
        <span class="text-gray-400">Create</span>
    </nav>
</div>

@if($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="bg-white rounded-lg shadow-sm border p-6">
    <h2 class="text-xl font-semibold mb-6">Create Coupons</h2>
    
    <form action="{{ route('coupons.store') }}" method="POST" class="space-y-6">
        @csrf
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Code -->
            <div>
                <label for="code" class="block text-sm font-medium text-gray-700 mb-2">Code</label>
                <input type="text" 
                       id="code" 
                       name="code" 
                       value="{{ old('code') }}"
                       placeholder="Leave empty for auto-generation"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                <p class="text-xs text-gray-500 mt-1">Leave empty to auto-generate a unique code</p>
            </div>

            <!-- Discount -->
            <div>
                <label for="discount_value" class="block text-sm font-medium text-gray-700 mb-2">Discount</label>
                <input type="number" 
                       id="discount_value" 
                       name="discount_value" 
                       value="{{ old('discount_value') }}"
                       step="0.01"
                       min="0"
                       required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Discount Type -->
            <div>
                <label for="discount_type" class="block text-sm font-medium text-gray-700 mb-2">Discount Type</label>
                <select id="discount_type" 
                        name="discount_type" 
                        required
                        onchange="updateDiscountTypeDisplay()"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="percentage" {{ old('discount_type', 'percentage') === 'percentage' ? 'selected' : '' }}>Percentage</option>
                    <option value="fixed" {{ old('discount_type') === 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                </select>
            </div>

            <!-- Coupon Type -->
            <div>
                <label for="coupon_type" class="block text-sm font-medium text-gray-700 mb-2">Coupon Type</label>
                <select id="coupon_type" 
                        name="coupon_type" 
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="ride" {{ old('coupon_type', 'ride') === 'ride' ? 'selected' : '' }}>Ride</option>
                    <option value="delivery" {{ old('coupon_type') === 'delivery' ? 'selected' : '' }}>Delivery</option>
                    <option value="both" {{ old('coupon_type') === 'both' ? 'selected' : '' }}>Both</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Expires At -->
            <div>
                <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-2">Expires At</label>
                <input type="datetime-local" 
                       id="expires_at" 
                       name="expires_at" 
                       value="{{ old('expires_at') }}"
                       required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>

            <!-- Starts At -->
            <div>
                <label for="starts_at" class="block text-sm font-medium text-gray-700 mb-2">Starts At</label>
                <input type="datetime-local" 
                       id="starts_at" 
                       name="starts_at" 
                       value="{{ old('starts_at', now()->format('Y-m-d\TH:i')) }}"
                       required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
        </div>

        <!-- Description -->
        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
            <textarea id="description" 
                      name="description" 
                      rows="4" 
                      required
                      placeholder="Enter coupon description..."
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">{{ old('description') }}</textarea>
        </div>

        <!-- Advanced Options -->
        <div class="border-t pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Advanced Options</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Minimum Amount -->
                <div>
                    <label for="minimum_amount" class="block text-sm font-medium text-gray-700 mb-2">Minimum Amount</label>
                    <input type="number" 
                           id="minimum_amount" 
                           name="minimum_amount" 
                           value="{{ old('minimum_amount') }}"
                           step="0.01"
                           min="0"
                           placeholder="0.00"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Minimum order amount required</p>
                </div>

                <!-- Maximum Discount -->
                <div>
                    <label for="maximum_discount" class="block text-sm font-medium text-gray-700 mb-2">Maximum Discount</label>
                    <input type="number" 
                           id="maximum_discount" 
                           name="maximum_discount" 
                           value="{{ old('maximum_discount') }}"
                           step="0.01"
                           min="0"
                           placeholder="No limit"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Maximum discount amount (for percentage)</p>
                </div>

                <!-- Usage Limit -->
                <div>
                    <label for="usage_limit" class="block text-sm font-medium text-gray-700 mb-2">Usage Limit</label>
                    <input type="number" 
                           id="usage_limit" 
                           name="usage_limit" 
                           value="{{ old('usage_limit') }}"
                           min="1"
                           placeholder="Unlimited"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Total number of times this coupon can be used</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                <!-- User Usage Limit -->
                <div>
                    <label for="user_usage_limit" class="block text-sm font-medium text-gray-700 mb-2">User Usage Limit</label>
                    <input type="number" 
                           id="user_usage_limit" 
                           name="user_usage_limit" 
                           value="{{ old('user_usage_limit') }}"
                           min="1"
                           placeholder="Unlimited per user"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Maximum uses per user</p>
                </div>

                <!-- Applicable To -->
                <div>
                    <label for="applicable_to" class="block text-sm font-medium text-gray-700 mb-2">Applicable To</label>
                    <select id="applicable_to" 
                            name="applicable_to" 
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="all" {{ old('applicable_to', 'all') === 'all' ? 'selected' : '' }}>All Users</option>
                        <option value="new_users" {{ old('applicable_to') === 'new_users' ? 'selected' : '' }}>New Users Only</option>
                        <option value="existing_users" {{ old('applicable_to') === 'existing_users' ? 'selected' : '' }}>Existing Users Only</option>
                        <option value="specific_users" {{ old('applicable_to') === 'specific_users' ? 'selected' : '' }}>Specific Users</option>
                    </select>
                </div>
            </div>

            <!-- Special Options -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                <div class="flex items-center">
                    <input type="checkbox" 
                           id="first_ride_only" 
                           name="first_ride_only" 
                           value="1"
                           {{ old('first_ride_only') ? 'checked' : '' }}
                           class="rounded border-gray-300 text-primary focus:ring-primary">
                    <label for="first_ride_only" class="ml-2 text-sm text-gray-700">First Ride Only</label>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" 
                           id="returning_user_only" 
                           name="returning_user_only" 
                           value="1"
                           {{ old('returning_user_only') ? 'checked' : '' }}
                           class="rounded border-gray-300 text-primary focus:ring-primary">
                    <label for="returning_user_only" class="ml-2 text-sm text-gray-700">Returning Users Only</label>
                </div>
            </div>
        </div>

        <!-- Status -->
        <div class="border-t pt-6">
            <div class="flex items-center">
                <input type="checkbox" 
                       id="enabled" 
                       name="status" 
                       value="enabled"
                       {{ old('status', 'enabled') === 'enabled' ? 'checked' : '' }}
                       class="rounded border-gray-300 text-primary focus:ring-primary">
                <label for="enabled" class="ml-2 text-sm text-gray-700">Enabled</label>
                <p class="ml-4 text-xs text-gray-500">Uncheck to create as disabled</p>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end gap-4 pt-6 border-t">
            <a href="{{ route('coupons.index') }}" 
               class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                Cancel
            </a>
            <button type="submit" 
                    class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-blue-700 transition-colors">
                Save
            </button>
        </div>
    </form>
</div>

<!-- Bulk Create Modal -->
<div id="bulkCreateModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="px-6 py-4 border-b">
                <h3 class="text-lg font-medium text-gray-900">Bulk Create Coupons</h3>
            </div>
            <form id="bulkCreateForm" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Number of Coupons</label>
                    <input type="number" name="count" min="1" max="100" value="10" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Code Prefix (Optional)</label>
                    <input type="text" name="code_prefix" maxlength="10" placeholder="e.g., SAVE"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-transparent">
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeBulkCreateModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-700">
                        Create Coupons
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function updateDiscountTypeDisplay() {
    const discountType = document.getElementById('discount_type').value;
    const discountValue = document.getElementById('discount_value');
    const maxDiscountDiv = document.getElementById('maximum_discount').closest('div');
    
    if (discountType === 'percentage') {
        discountValue.setAttribute('max', '100');
        discountValue.setAttribute('placeholder', 'e.g., 10 for 10%');
        maxDiscountDiv.style.display = 'block';
    } else {
        discountValue.removeAttribute('max');
        discountValue.setAttribute('placeholder', 'e.g., 5.00 for $5');
        maxDiscountDiv.style.display = 'none';
    }
}

function openBulkCreateModal() {
    document.getElementById('bulkCreateModal').classList.remove('hidden');
}

function closeBulkCreateModal() {
    document.getElementById('bulkCreateModal').classList.add('hidden');
}

document.getElementById('bulkCreateForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    // Copy main form data for bulk create
    const mainForm = document.querySelector('form[action*="store"]');
    const mainFormData = new FormData(mainForm);
    
    // Merge data
    for (let [key, value] of mainFormData.entries()) {
        if (key !== '_token' && !data.hasOwnProperty(key)) {
            data[key] = value;
        }
    }
    
    try {
        const response = await fetch('{{ route("coupons.bulk-create") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            alert(`Successfully created ${result.created_count} coupons!`);
            window.location.href = '{{ route("coupons.index") }}';
        } else {
            alert('Error: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Bulk create error:', error);
        alert('Error creating coupons: Connection failed');
    }
});

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    updateDiscountTypeDisplay();
    
    // Set default expiry date (30 days from now)
    const expiresAt = document.getElementById('expires_at');
    if (!expiresAt.value) {
        const futureDate = new Date();
        futureDate.setDate(futureDate.getDate() + 30);
        expiresAt.value = futureDate.toISOString().slice(0, 16);
    }
});
</script>
@endpush