{{-- resources/views/complaint/admin/complaints/show.blade.php --}}

@extends('admin::layouts.admin')

@section('title', 'Complaint Details - ' . $complaint->title)
@section('page-title', 'Complaint Details')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-primary">Complaint Details</h1>
        <p class="text-gray-600 mt-1">View and manage complaint information</p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('complaints.index') }}" 
           class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back to Complaints
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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Complaint Information -->
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b">
                <h2 class="text-lg font-semibold text-gray-900">Complaint Information</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Complaint ID</label>
                        <p class="text-sm text-gray-900 font-mono">{{ $complaint->id }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Order Type</label>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            {{ $complaint->order_type === 'ride' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                            <i class="fas {{ $complaint->order_type === 'ride' ? 'fa-car' : 'fa-box' }} mr-1"></i>
                            {{ ucfirst($complaint->order_type) }}
                        </span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $complaint->status_badge }}">
                            @if($complaint->status === 'pending')
                                <i class="fas fa-clock mr-1"></i>
                            @elseif($complaint->status === 'in_progress')
                                <i class="fas fa-spinner mr-1"></i>
                            @elseif($complaint->status === 'resolved')
                                <i class="fas fa-check mr-1"></i>
                            @else
                                <i class="fas fa-times mr-1"></i>
                            @endif
                            {{ ucfirst(str_replace('_', ' ', $complaint->status)) }}
                        </span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $complaint->priority_badge }}">
                            @if($complaint->priority === 'urgent')
                                <i class="fas fa-fire mr-1"></i>
                            @elseif($complaint->priority === 'high')
                                <i class="fas fa-exclamation mr-1"></i>
                            @elseif($complaint->priority === 'medium')
                                <i class="fas fa-minus mr-1"></i>
                            @else
                                <i class="fas fa-arrow-down mr-1"></i>
                            @endif
                            {{ ucfirst($complaint->priority) }}
                        </span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <p class="text-sm text-gray-900">
                            {{ \App\Modules\Complaint\Models\Complaint::getCategories()[$complaint->category] ?? ucfirst($complaint->category) }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Created</label>
                        <p class="text-sm text-gray-900">
                            {{ $complaint->created_at ? $complaint->created_at->format('M d, Y \a\t H:i') : 'N/A' }}
                            <span class="text-gray-500">({{ $complaint->time_ago }})</span>
                        </p>
                    </div>
                </div>

                @if($complaint->is_overdue)
                    <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                            <span class="text-red-800 font-medium">This complaint is overdue and requires immediate attention.</span>
                        </div>
                    </div>
                @endif

                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                    <p class="text-lg text-gray-900 font-medium">{{ $complaint->title }}</p>
                </div>

                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ $complaint->description ?? 'No description provided.' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Information -->
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b">
                <h2 class="text-lg font-semibold text-gray-900">User Information</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Complaint By</label>
                        <p class="text-sm text-gray-900">{{ $complaint->complaint_by ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contact Information</label>
                        <p class="text-sm text-gray-900">{{ $complaint->contact_info ?? 'N/A' }}</p>
                    </div>
                    @if($complaint->user_name)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">User Name</label>
                        <p class="text-sm text-gray-900">{{ $complaint->user_name }}</p>
                    </div>
                    @endif
                    @if($complaint->user_id)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">User ID</label>
                        <p class="text-sm text-gray-900 font-mono">{{ $complaint->user_id }}</p>
                    </div>
                    @endif
                    @if($complaint->driver_name)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Driver Name</label>
                        <p class="text-sm text-gray-900">{{ $complaint->driver_name }}</p>
                    </div>
                    @endif
                    @if($complaint->driver_id)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Driver ID</label>
                        <p class="text-sm text-gray-900 font-mono">{{ $complaint->driver_id }}</p>
                    </div>
                    @endif
                    @if($complaint->order_id)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Order ID</label>
                        <p class="text-sm text-gray-900 font-mono">{{ $complaint->order_id }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Admin Notes -->
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b">
                <h2 class="text-lg font-semibold text-gray-900">Admin Notes</h2>
            </div>
            <div class="p-6">
                @if($complaint->admin_notes)
                    <div class="bg-gray-50 rounded-lg p-4 mb-4">
                        <pre class="text-sm text-gray-900 whitespace-pre-wrap font-sans">{{ $complaint->admin_notes }}</pre>
                    </div>
                @else
                    <p class="text-gray-500 italic mb-4">No admin notes added yet.</p>
                @endif

                <!-- Add Notes Form -->
                <form id="addNotesForm" class="space-y-4">
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Add Notes</label>
                        <textarea 
                            id="notes" 
                            name="notes" 
                            rows="4" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                            placeholder="Add your notes here..."
                            maxlength="1000"
                        ></textarea>
                        <p class="text-xs text-gray-500 mt-1">Maximum 1000 characters</p>
                    </div>
                    <div>
                        <button 
                            type="submit" 
                            class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
                            id="addNotesBtn"
                        >
                            <i class="fas fa-plus mr-2"></i>Add Notes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b">
                <h2 class="text-lg font-semibold text-gray-900">Quick Actions</h2>
            </div>
            <div class="p-6 space-y-3">
                @if($complaint->status !== 'in_progress')
                <button 
                    onclick="updateStatus('in_progress')" 
                    class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
                >
                    <i class="fas fa-spinner mr-2"></i>Set In Progress
                </button>
                @endif

                @if($complaint->status !== 'resolved')
                <button 
                    onclick="updateStatus('resolved')" 
                    class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors"
                >
                    <i class="fas fa-check mr-2"></i>Mark Resolved
                </button>
                @endif

                @if($complaint->status !== 'closed')
                <button 
                    onclick="updateStatus('closed')" 
                    class="w-full bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors"
                >
                    <i class="fas fa-times mr-2"></i>Close Complaint
                </button>
                @endif

                @if($complaint->status !== 'pending')
                <button 
                    onclick="updateStatus('pending')" 
                    class="w-full bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-colors"
                >
                    <i class="fas fa-clock mr-2"></i>Set Pending
                </button>
                @endif
            </div>
        </div>

        <!-- Resolution Information -->
        @if($complaint->status === 'resolved' && $complaint->resolved_at)
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
            <div class="px-6 py-4 bg-green-50 border-b">
                <h2 class="text-lg font-semibold text-green-900">Resolution Details</h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Resolved At</label>
                        <p class="text-sm text-gray-900">
                            {{ $complaint->resolved_at ? $complaint->resolved_at->format('M d, Y \a\t H:i') : 'N/A' }}
                        </p>
                    </div>
                    @if($complaint->resolved_by)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Resolved By</label>
                        <p class="text-sm text-gray-900">{{ $complaint->resolved_by }}</p>
                    </div>
                    @endif
                    @if($complaint->created_at && $complaint->resolved_at)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Resolution Time</label>
                        <p class="text-sm text-gray-900">
                            {{ $complaint->created_at->diffForHumans($complaint->resolved_at, true) }}
                        </p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- Complaint Statistics -->
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b">
                <h2 class="text-lg font-semibold text-gray-900">Complaint Info</h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Priority Level</span>
                        <span class="text-sm font-medium text-gray-900">{{ ucfirst($complaint->priority) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Category</span>
                        <span class="text-sm font-medium text-gray-900">
                            {{ \App\Modules\Complaint\Models\Complaint::getCategories()[$complaint->category] ?? ucfirst($complaint->category) }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Age</span>
                        <span class="text-sm font-medium text-gray-900">{{ $complaint->time_ago }}</span>
                    </div>
                    @if($complaint->is_overdue)
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-red-600">Status</span>
                        <span class="text-sm font-medium text-red-900">Overdue</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function showNotification(message, type = 'info') {
        const alertClass = {
            'success': 'bg-green-100 border-green-400 text-green-700',
            'error': 'bg-red-100 border-red-400 text-red-700',
            'info': 'bg-blue-100 border-blue-400 text-blue-700',
            'warning': 'bg-yellow-100 border-yellow-400 text-yellow-700'
        }[type] || 'bg-gray-100 border-gray-400 text-gray-700';

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

    async function updateStatus(status) {
        if (!confirm(`Are you sure you want to change the status to "${status.replace('_', ' ')}"?`)) {
            return;
        }

        try {
            const response = await fetch(`{{ route('complaints.update-status', $complaint->id) }}`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: status })
            });
            
            if (response.ok) {
                showNotification(`Complaint status updated to ${status.replace('_', ' ')}`, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                const result = await response.json();
                showNotification('Failed to update status: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Update status error:', error);
            showNotification('Error updating status: Connection failed', 'error');
        }
    }

    // Handle add notes form
    document.getElementById('addNotesForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const notes = document.getElementById('notes').value.trim();
        if (!notes) {
            showNotification('Please enter some notes', 'warning');
            return;
        }

        const btn = document.getElementById('addNotesBtn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Adding...';

        try {
            const response = await fetch(`{{ route('complaints.add-notes', $complaint->id) }}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ notes: notes })
            });
            
            if (response.ok) {
                showNotification('Notes added successfully', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                const result = await response.json();
                showNotification('Failed to add notes: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Add notes error:', error);
            showNotification('Error adding notes: Connection failed', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        console.log('Complaint details page initialized');
    });
</script>
@endpush