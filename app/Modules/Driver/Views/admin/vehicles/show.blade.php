@extends('admin::layouts.admin')

@section('title', 'Vehicle Details')
@section('page-title', 'Vehicle Details')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-primary">
            {{ ($vehicle->year ?? 'Unknown') }} {{ ($vehicle->make ?? 'Unknown') }} {{ ($vehicle->model ?? 'Model') }}
        </h1>
        <p class="text-gray-600 mt-1">License Plate: {{ $vehicle->license_plate ?? 'No Plate' }}</p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('admin.vehicles.edit', $vehicle->id) }}"
            class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
            <i class="fas fa-edit mr-2"></i>Edit Vehicle
        </a>
        <a href="{{ route('admin.vehicles.index') }}"
            class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back to Vehicles
        </a>
    </div>
</div>

@if(session('success'))
<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
    <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
    <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
</div>
@endif

<!-- Quick Stats -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                <i class="fas fa-road text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Total Rides</p>
                <p class="text-2xl font-bold text-gray-900">{{ $rideStats['total_rides'] ?? 0 }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                <i class="fas fa-check-circle text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Completion Rate</p>
                <p class="text-2xl font-bold text-gray-900">{{ $rideStats['completion_rate'] ?? 0 }}%</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                <i class="fas fa-dollar-sign text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Total Earnings</p>
                <p class="text-2xl font-bold text-gray-900">${{ number_format($rideStats['total_earnings'] ?? 0, 2) }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                <i class="fas fa-percentage text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Completion %</p>
                <p class="text-2xl font-bold text-gray-900">{{ $completionStatus ?? 0 }}%</p>
            </div>
        </div>
    </div>
</div>

<!-- Vehicle Overview -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Main Vehicle Info -->
    <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border p-6">
        <div class="flex justify-between items-start mb-4">
            <h2 class="text-xl font-semibold">
                <i class="fas fa-car mr-2 text-primary"></i>Vehicle Information
            </h2>

        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-4">
                <div class="flex justify-between">
                    <span class="font-medium text-gray-700">Make:</span>
                    <span class="text-gray-900">{{ $vehicle->make ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium text-gray-700">Model:</span>
                    <span class="text-gray-900">{{ $vehicle->model ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium text-gray-700">Year:</span>
                    <span class="text-gray-900">{{ $vehicle->year ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium text-gray-700">Color:</span>
                    <span class="text-gray-900">{{ $vehicle->color ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium text-gray-700">License Plate:</span>
                    <span class="text-gray-900 font-mono">{{ $vehicle->license_plate ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium text-gray-700">VIN:</span>
                    <span class="text-gray-900 font-mono text-sm">{{ $vehicle->vin ?? 'N/A' }}</span>
                </div>
            </div>

            <div class="space-y-4">
                <div class="flex justify-between">
                    <span class="font-medium text-gray-700">Vehicle Type:</span>
                    <span class="text-gray-900">{{ ucfirst($vehicle->vehicle_type ?? 'unknown') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium text-gray-700">Fuel Type:</span>
                    <span class="text-gray-900">{{ ucfirst($vehicle->fuel_type ?? 'N/A') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium text-gray-700">Seats:</span>
                    <span class="text-gray-900">{{ $vehicle->seats ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium text-gray-700">Primary Vehicle:</span>
                    <span class="text-gray-900">
                        @if($vehicle->is_primary ?? false)
                        <span class="text-blue-600"><i class="fas fa-star mr-1"></i>Yes</span>
                        @else
                        <span class="text-gray-500">No</span>
                        @endif
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium text-gray-700">Status:</span>
                    <span id="vehicle-status">
                        @if(($vehicle->status ?? 'inactive') === 'active')
                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">
                            <i class="fas fa-check-circle mr-1"></i>Active
                        </span>
                        @elseif(($vehicle->status ?? 'inactive') === 'suspended')
                        <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs">
                            <i class="fas fa-ban mr-1"></i>Suspended
                        </span>
                        @elseif(($vehicle->status ?? 'inactive') === 'maintenance')
                        <span class="bg-orange-100 text-orange-800 px-2 py-1 rounded-full text-xs">
                            <i class="fas fa-wrench mr-1"></i>Maintenance
                        </span>
                        @else
                        <span class="bg-gray-100 text-gray-800 px-2 py-1 rounded-full text-xs">
                            <i class="fas fa-pause-circle mr-1"></i>Inactive
                        </span>
                        @endif
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium text-gray-700">Verification:</span>
                    <span>
                        @if(($vehicle->verification_status ?? 'pending') === 'verified')
                        <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs">
                            <i class="fas fa-shield-check mr-1"></i>Verified
                        </span>
                        @elseif(($vehicle->verification_status ?? 'pending') === 'rejected')
                        <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs">
                            <i class="fas fa-shield-times mr-1"></i>Rejected
                        </span>
                        @else
                        <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs">
                            <i class="fas fa-clock mr-1"></i>Pending
                        </span>
                        @endif
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Driver Information -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h3 class="text-lg font-semibold mb-4">
            <i class="fas fa-user mr-2 text-primary"></i>Driver Information
        </h3>

        @if($driver ?? false)
        <div class="space-y-3">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-primary rounded-full flex items-center justify-center text-white font-bold mr-3">
                    {{ substr($driver->name ?? 'U', 0, 1) }}
                </div>
                <div>
                    <div class="font-medium text-gray-900">{{ $driver->name ?? 'Unknown Driver' }}</div>
                    <div class="text-sm text-gray-500">{{ $driver->email ?? 'No email' }}</div>
                </div>
            </div>

            <div class="pt-3 border-t space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Phone:</span>
                    <span class="text-gray-900">{{ $driver->phone ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Status:</span>
                    <span class="text-gray-900">
                        @if(($driver->status ?? 'inactive') === 'active')
                        <span class="text-green-600">Active</span>
                        @else
                        <span class="text-gray-500">Inactive</span>
                        @endif
                    </span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Joined:</span>
                    <span class="text-gray-900">{{ $driver->created_at ? $driver->created_at->format('M Y') : 'Unknown' }}</span>
                </div>
            </div>

            <div class="pt-3">
                <a href="{{ route('admin.drivers.show', $driver->firebase_uid ?? '#') }}"
                    class="block w-full text-center bg-primary text-white py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm">
                    <i class="fas fa-user mr-1"></i>View Driver Profile
                </a>
            </div>
        </div>
        @else
        <div class="text-center text-gray-500 py-4">
            <i class="fas fa-user-slash text-2xl mb-2"></i>
            <p>Driver information not available</p>
        </div>
        @endif
    </div>
</div>

<!-- Registration & Insurance -->
<div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
    <h2 class="text-xl font-semibold mb-4">
        <i class="fas fa-file-alt mr-2 text-primary"></i>Registration & Insurance
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Registration Info -->
        <div class="space-y-4">
            <h3 class="font-medium text-gray-900 border-b pb-2">Registration Details</h3>

            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Registration Number:</span>
                    <span class="text-gray-900">{{ $vehicle->registration_number ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Registration Expiry:</span>
                    <span class="text-gray-900">
                        @if($vehicle->registration_expiry)
                        {{ $vehicle->registration_expiry->format('M d, Y') }}
                        @if($vehicle->registration_expiry->isPast())
                        <span class="text-red-600 text-sm ml-1">(Expired)</span>
                        @elseif($vehicle->registration_expiry->diffInDays() <= 30)
                            <span class="text-yellow-600 text-sm ml-1">(Expires Soon)</span>
                    @endif
                    @else
                    N/A
                    @endif
                    </span>
                </div>
            </div>
        </div>

        <!-- Insurance Info -->
        <div class="space-y-4">
            <h3 class="font-medium text-gray-900 border-b pb-2">Insurance Details</h3>

            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Insurance Provider:</span>
                    <span class="text-gray-900">{{ $vehicle->insurance_provider ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Policy Number:</span>
                    <span class="text-gray-900">{{ $vehicle->insurance_policy_number ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Insurance Expiry:</span>
                    <span class="text-gray-900">
                        @if($vehicle->insurance_expiry)
                        {{ $vehicle->insurance_expiry->format('M d, Y') }}
                        @if($vehicle->insurance_expiry->isPast())
                        <span class="text-red-600 text-sm ml-1">(Expired)</span>
                        @elseif($vehicle->insurance_expiry->diffInDays() <= 30)
                            <span class="text-yellow-600 text-sm ml-1">(Expires Soon)</span>
                    @endif
                    @else
                    N/A
                    @endif
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabbed Content -->
<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    <div class="border-b">
        <nav class="flex -mb-px">
            <button onclick="switchTab('rides')"
                class="tab-button active px-6 py-3 border-b-2 border-primary text-primary font-medium text-sm">
                <i class="fas fa-road mr-2"></i>Rides ({{ count($rides ?? []) }})
            </button>
            <button onclick="switchTab('documents')"
                class="tab-button px-6 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium text-sm">
                <i class="fas fa-file mr-2"></i>Documents ({{ count($documents ?? []) }})
            </button>
            <button onclick="switchTab('activities')"
                class="tab-button px-6 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium text-sm">
                <i class="fas fa-history mr-2"></i>Activities ({{ count($activities ?? []) }})
            </button>
            <button onclick="switchTab('maintenance')"
                class="tab-button px-6 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium text-sm">
                <i class="fas fa-wrench mr-2"></i>Maintenance
            </button>
        </nav>
    </div>

    <!-- Tab Content -->
    <div class="p-6">
        <!-- Rides Tab -->
        <div id="rides-tab" class="tab-content">
            @if(isset($rides) && count($rides) > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ride ID</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Passenger</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Route</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fare</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($rides as $ride)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-sm font-mono">{{ Str::limit($ride->id ?? 'N/A', 8) }}</td>
                            <td class="px-4 py-2 text-sm">{{ $ride->passenger_name ?? 'Unknown' }}</td>
                            <td class="px-4 py-2 text-sm">
                                <div>{{ Str::limit($ride->pickup_address ?? 'Unknown pickup', 30) }}</div>
                                <div class="text-gray-500">to {{ Str::limit($ride->destination_address ?? 'Unknown destination', 30) }}</div>
                            </td>
                            <td class="px-4 py-2 text-sm">
                                @if(($ride->status ?? 'unknown') === 'completed')
                                <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">Completed</span>
                                @elseif(($ride->status ?? 'unknown') === 'cancelled')
                                <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs">Cancelled</span>
                                @else
                                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs">{{ ucfirst($ride->status ?? 'Unknown') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-sm">${{ number_format($ride->total_fare ?? 0, 2) }}</td>
                            <td class="px-4 py-2 text-sm">{{ $ride->created_at ? $ride->created_at->format('M d, Y') : 'N/A' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-8">
                <i class="fas fa-road text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Rides Found</h3>
                <p class="text-gray-500">This vehicle hasn't completed any rides yet.</p>
            </div>
            @endif
        </div>

        <!-- Documents Tab -->
        <div id="documents-tab" class="tab-content hidden">
            @if(isset($documents) && count($documents) > 0)
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
                        <button onclick="verifyDocument('{{ $document->id ?? '' }}')"
                            class="text-green-600 hover:text-green-800 text-sm">
                            <i class="fas fa-check mr-1"></i>Verify
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-8">
                <i class="fas fa-file text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Documents Found</h3>
                <p class="text-gray-500">No documents have been uploaded for this vehicle yet.</p>
            </div>
            @endif
        </div>

        <!-- Activities Tab -->
        <div id="activities-tab" class="tab-content hidden">
            @if(isset($activities) && count($activities) > 0)
            <div class="space-y-4">
                @foreach($activities as $activity)
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-clock text-blue-600 text-sm"></i>
                    </div>
                    <div class="flex-grow">
                        <div class="flex items-center justify-between">
                            <h4 class="font-medium text-gray-900">{{ $activity->title ?? 'Activity' }}</h4>
                            <span class="text-sm text-gray-500">{{ $activity->created_at ? $activity->created_at->diffForHumans() : 'Unknown time' }}</span>
                        </div>
                        <p class="text-sm text-gray-600 mt-1">{{ $activity->description ?? 'No description' }}</p>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-8">
                <i class="fas fa-history text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Activities Found</h3>
                <p class="text-gray-500">No activities recorded for this vehicle yet.</p>
            </div>
            @endif
        </div>

        <!-- Maintenance Tab -->
        <div id="maintenance-tab" class="tab-content hidden">
            <div class="text-center py-8">
                <i class="fas fa-wrench text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Maintenance Records</h3>
                <p class="text-gray-500">Maintenance tracking feature coming soon.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function switchTab(tabName) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.add('hidden');
        });

        // Remove active class from all tab buttons
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('active', 'border-primary', 'text-primary');
            button.classList.add('border-transparent', 'text-gray-500');
        });

        // Show selected tab content
        document.getElementById(tabName + '-tab').classList.remove('hidden');

        // Add active class to selected tab button
        event.target.classList.add('active', 'border-primary', 'text-primary');
        event.target.classList.remove('border-transparent', 'text-gray-500');
    }

    async function updateVehicleStatus(action) {
        if (!confirm(`Are you sure you want to ${action} this vehicle?`)) {
            return;
        }

        try {
            const response = await fetch(`{{ route('admin.vehicles.update-status', $vehicle->id) }}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    status: action
                })
            });

            const result = await response.json();

            if (response.ok && result.success) {
                showNotification(`Vehicle ${action}d successfully`, 'success');

                // Update status display
                const statusElement = document.getElementById('vehicle-status');
                if (statusElement) {
                    let statusHtml = '';
                    const newStatus = result.new_status || action;

                    switch (newStatus) {
                        case 'active':
                            statusHtml = '<span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs"><i class="fas fa-check-circle mr-1"></i>Active</span>';
                            break;
                        case 'inactive':
                            statusHtml = '<span class="bg-gray-100 text-gray-800 px-2 py-1 rounded-full text-xs"><i class="fas fa-pause-circle mr-1"></i>Inactive</span>';
                            break;
                        case 'suspended':
                            statusHtml = '<span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs"><i class="fas fa-ban mr-1"></i>Suspended</span>';
                            break;
                        case 'maintenance':
                            statusHtml = '<span class="bg-orange-100 text-orange-800 px-2 py-1 rounded-full text-xs"><i class="fas fa-wrench mr-1"></i>Maintenance</span>';
                            break;
                    }
                    statusElement.innerHTML = statusHtml;
                }

            } else {
                showNotification(`Failed to ${action} vehicle: ` + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Update status error:', error);
            showNotification(`Error ${action}ing vehicle: Connection failed`, 'error');
        }
    }

    async function verifyDocument(documentId) {
        if (!confirm('Are you sure you want to verify this document?')) {
            return;
        }

        try {
            const response = await fetch(`{{ url('admin/documents') }}/${documentId}/verify`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (response.ok && result.success) {
                showNotification('Document verified successfully', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showNotification('Failed to verify document: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Verify document error:', error);
            showNotification('Error verifying document: Connection failed', 'error');
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

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize first tab as active
        document.querySelector('.tab-button').click();
    });
</script>
@endpush