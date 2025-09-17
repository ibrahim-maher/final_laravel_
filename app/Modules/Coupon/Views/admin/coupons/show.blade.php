{{-- resources/views/coupon/admin/coupons/show.blade.php --}}

@extends('admin::layouts.admin')

@section('title', 'Coupon Details: ' . $coupon->code)
@section('page-title', 'Coupon Details')

@section('content')
<div>
    <h1 class="text-3xl font-bold text-primary">Coupon Details</h1>
    <nav class="text-sm text-gray-600 mt-1">
        <a href="{{ route('admin.dashboard') }}">Dashboard</a> > 
        <a href="{{ route('coupons.index') }}">Coupons</a> > 
        <span class="text-gray-400">{{ $coupon->code }}</span>
    </nav>
</div>

@if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        {{ session('error') }}
    </div>
@endif

<div class="bg-white rounded-lg shadow-sm border">
    <!-- Header Section -->
    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-start">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">{{ $coupon->code }}</h2>
            <p class="text-gray-600 mt-1">{{ $coupon->description }}</p>
            <div class="flex items-center gap-4 mt-3">
                <!-- Status Badge -->
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                    @if($coupon->status === 'enabled') bg-green-100 text-green-800
                    @elseif($coupon->status === 'disabled') bg-gray-100 text-gray-800
                    @elseif($coupon->status === 'expired') bg-red-100 text-red-800
                    @elseif($coupon->status === 'exhausted') bg-yellow-100 text-yellow-800
                    @endif">
                    {{ ucfirst($coupon->status) }}
                </span>

                <!-- Active Status -->
                @if($coupon->is_active)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        Active
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                        Inactive
                    </span>
                @endif

                <!-- Firebase Sync Status -->
                @if($coupon->firebase_synced)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        ✓ Synced
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                        ⏳ Pending Sync
                    </span>
                @endif
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-3">
            <a href="{{ route('coupons.edit', $coupon->code) }}" 
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                Edit
            </a>
            
            @if($coupon->status === 'enabled')
                <form action="{{ route('coupons.disable', $coupon->code) }}" method="POST" class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" 
                            class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors"
                            onclick="return confirm('Are you sure you want to disable this coupon?')">
                        Disable
                    </button>
                </form>
            @else
                <form action="{{ route('coupons.enable', $coupon->code) }}" method="POST" class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" 
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        Enable
                    </button>
                </form>
            @endif

            <form action="{{ route('coupons.destroy', $coupon->code) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" 
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
                        onclick="return confirm('Are you sure you want to delete this coupon? This action cannot be undone.')">
                    Delete
                </button>
            </form>
        </div>
    </div>

    <!-- Content Section -->
    <div class="p-6">
        <!-- Basic Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Discount</h3>
                <div class="mt-2">
                    <p class="text-2xl font-bold text-gray-900">
                        @if($coupon->discount_type === 'percentage')
                            {{ $coupon->discount_value }}%
                        @else
                            ${{ number_format($coupon->discount_value, 2) }}
                        @endif
                    </p>
                    <p class="text-sm text-gray-600">{{ ucfirst($coupon->discount_type) }} discount</p>
                </div>
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Usage</h3>
                <div class="mt-2">
                    <p class="text-2xl font-bold text-gray-900">
                        {{ $coupon->used_count }}
                        @if($coupon->usage_limit)
                            / {{ $coupon->usage_limit }}
                        @endif
                    </p>
                    <p class="text-sm text-gray-600">
                        @if($coupon->usage_limit)
                            {{ $coupon->usage_percentage }}% used
                        @else
                            Unlimited usage
                        @endif
                    </p>
                </div>
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Type</h3>
                <div class="mt-2">
                    <p class="text-2xl font-bold text-gray-900">{{ ucfirst($coupon->coupon_type) }}</p>
                    <p class="text-sm text-gray-600">Service type</p>
                </div>
            </div>
        </div>

        <!-- Detailed Information -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Left Column -->
            <div class="space-y-6">
                <!-- Dates -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Validity Period</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-sm font-medium text-gray-500">Starts At:</span>
                            <span class="text-sm text-gray-900">{{ $coupon->starts_at->format('M d, Y h:i A') }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-sm font-medium text-gray-500">Expires At:</span>
                            <span class="text-sm text-gray-900">{{ $coupon->expires_at->format('M d, Y h:i A') }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm font-medium text-gray-500">Time Remaining:</span>
                            <span class="text-sm text-gray-900">
                                @if($coupon->expires_at > now())
                                    {{ $coupon->expires_at->diffForHumans() }}
                                @else
                                    <span class="text-red-600">Expired {{ $coupon->expires_at->diffForHumans() }}</span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Limits -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Limits & Restrictions</h3>
                    <div class="space-y-3">
                        @if($coupon->minimum_amount)
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-sm font-medium text-gray-500">Minimum Amount:</span>
                            <span class="text-sm text-gray-900">${{ number_format($coupon->minimum_amount, 2) }}</span>
                        </div>
                        @endif

                        @if($coupon->maximum_discount)
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-sm font-medium text-gray-500">Maximum Discount:</span>
                            <span class="text-sm text-gray-900">${{ number_format($coupon->maximum_discount, 2) }}</span>
                        </div>
                        @endif

                        @if($coupon->user_usage_limit)
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-sm font-medium text-gray-500">Per User Limit:</span>
                            <span class="text-sm text-gray-900">{{ $coupon->user_usage_limit }} uses</span>
                        </div>
                        @endif

                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm font-medium text-gray-500">Applicable To:</span>
                            <span class="text-sm text-gray-900">
                                @switch($coupon->applicable_to)
                                    @case('all')
                                        All Users
                                        @break
                                    @case('new_users')
                                        New Users Only
                                        @break
                                    @case('existing_users')
                                        Existing Users Only
                                        @break
                                    @case('specific_users')
                                        Specific Users
                                        @break
                                @endswitch
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                <!-- Special Options -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Special Options</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between py-2 border-b border-gray-100">
                            <span class="text-sm font-medium text-gray-500">First Ride Only:</span>
                            <span class="text-sm">
                                @if($coupon->first_ride_only)
                                    <span class="text-green-600 font-medium">Yes</span>
                                @else
                                    <span class="text-gray-500">No</span>
                                @endif
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-2">
                            <span class="text-sm font-medium text-gray-500">Returning Users Only:</span>
                            <span class="text-sm">
                                @if($coupon->returning_user_only)
                                    <span class="text-green-600 font-medium">Yes</span>
                                @else
                                    <span class="text-gray-500">No</span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                <!-- User Arrays (if any) -->
                @if($coupon->getExcludedUsersArray() || $coupon->getIncludedUsersArray())
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">User Restrictions</h3>
                    <div class="space-y-4">
                        @if($coupon->getIncludedUsersArray())
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Included Users:</h4>
                            <div class="bg-green-50 rounded-lg p-3">
                                <div class="flex flex-wrap gap-2">
                                    @foreach($coupon->getIncludedUsersArray() as $userId)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            {{ $userId }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($coupon->getExcludedUsersArray())
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Excluded Users:</h4>
                            <div class="bg-red-50 rounded-lg p-3">
                                <div class="flex flex-wrap gap-2">
                                    @foreach($coupon->getExcludedUsersArray() as $userId)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            {{ $userId }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                <!-- System Information -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">System Information</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-sm font-medium text-gray-500">Created:</span>
                            <span class="text-sm text-gray-900">{{ $coupon->created_at->format('M d, Y h:i A') }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-sm font-medium text-gray-500">Last Updated:</span>
                            <span class="text-sm text-gray-900">{{ $coupon->updated_at->format('M d, Y h:i A') }}</span>
                        </div>
                        @if($coupon->firebase_synced_at)
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-sm font-medium text-gray-500">Firebase Sync:</span>
                            <span class="text-sm text-gray-900">{{ $coupon->firebase_synced_at->format('M d, Y h:i A') }}</span>
                        </div>
                        @endif
                        @if($coupon->firebase_sync_attempts > 0)
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm font-medium text-gray-500">Sync Attempts:</span>
                            <span class="text-sm text-gray-900">{{ $coupon->firebase_sync_attempts }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Usage History Section -->
@if($coupon->used_count > 0)
<div class="bg-white rounded-lg shadow-sm border mt-6">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Recent Usage History</h3>
        <p class="text-sm text-gray-600 mt-1">Last {{ min($coupon->used_count, 10) }} uses of this coupon</p>
    </div>
    <div class="p-6">
        @if(isset($usageHistory) && $usageHistory->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Discount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Used At</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($usageHistory as $usage)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $usage->user_id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${{ number_format($usage->discount_amount, 2) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${{ number_format($usage->order_amount, 2) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $usage->used_at->format('M d, Y h:i A') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-8">
            <p class="text-gray-500">No usage history available</p>
        </div>
        @endif
    </div>
</div>
@endif


@endsection

@push('scripts')
<script>
async function forceSyncToFirebase() {
    if (!confirm('Force sync this coupon to Firebase?')) return;
    
    try {
        const response = await fetch('{{ route("coupons.force-sync", $coupon->code) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            alert('Coupon synced to Firebase successfully!');
            location.reload();
        } else {
            alert('Error: ' + (result.message || 'Sync failed'));
        }
    } catch (error) {
        console.error('Sync error:', error);
        alert('Error syncing coupon: Connection failed');
    }
}
</script>
@endpush