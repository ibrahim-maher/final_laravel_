{{-- resources/views/foq/admin/foqs/show.blade.php --}}

@extends('admin::layouts.admin')

@section('title', 'View FOQ')
@section('page-title', 'View FOQ')

@push('styles')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-primary">FOQ Details</h1>
        <p class="text-gray-600 mt-1">View and manage frequently offered question</p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('foqs.edit', $foq->id) }}" 
           class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
            <i class="fas fa-edit mr-2"></i>Edit FOQ
        </a>
        <a href="{{ route('foqs.duplicate', $foq->id) }}" 
           class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            <i class="fas fa-copy mr-2"></i>Duplicate
        </a>
        <a href="{{ route('foqs.index') }}" 
           class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back to FOQs
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
        <!-- Question and Answer -->
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                    <h2 class="text-xl font-semibold text-gray-900 mb-2">
                        {{ $foq->question }}
                        @if($foq->is_featured)
                            <i class="fas fa-star text-yellow-500 ml-2" title="Featured FOQ"></i>
                        @endif
                    </h2>
                    <div class="flex items-center space-x-4 text-sm text-gray-500">
                        <span>
                            <i class="fas fa-tag mr-1"></i>{{ ucfirst($foq->category) }}
                        </span>
                        <span>
                            <i class="fas fa-layer-group mr-1"></i>{{ ucfirst($foq->type) }}
                        </span>
                        <span>
                            <i class="fas fa-flag mr-1"></i>{{ ucfirst($foq->priority) }}
                        </span>
                    </div>
                </div>
                <div class="ml-4">
                    @if($foq->status === 'active')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            <i class="fas fa-check-circle mr-1"></i>Active
                        </span>
                    @elseif($foq->status === 'inactive')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                            <i class="fas fa-times-circle mr-1"></i>Inactive
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                            <i class="fas fa-edit mr-1"></i>Draft
                        </span>
                    @endif
                </div>
            </div>

            <div class="border-t pt-4">
                <h3 class="text-lg font-medium text-gray-900 mb-3">Answer</h3>
                <div class="prose max-w-none text-gray-700">
                    {!! $foq->answer !!}
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Performance Statistics</h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ number_format($foq->view_count ?? 0) }}</div>
                    <div class="text-sm text-gray-500">Total Views</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">{{ $foq->helpful_count ?? 0 }}</div>
                    <div class="text-sm text-gray-500">Helpful Votes</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-red-600">{{ $foq->not_helpful_count ?? 0 }}</div>
                    <div class="text-sm text-gray-500">Not Helpful Votes</div>
                </div>
            </div>

            @if(($foq->helpful_count ?? 0) > 0 || ($foq->not_helpful_count ?? 0) > 0)
                <div class="mt-4 pt-4 border-t">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Helpfulness Ratio</span>
                        <span class="text-lg font-semibold text-gray-900">{{ $foq->helpfulness_ratio ?? 0 }}%</span>
                    </div>
                    <div class="mt-2 bg-gray-200 rounded-full h-2">
                        <div class="bg-green-500 h-2 rounded-full" 
                             style="width: {{ $foq->helpfulness_ratio ?? 0 }}%"></div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Actions -->
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                @if($foq->status === 'active')
                    <button onclick="toggleStatus('{{ $foq->id }}', 'inactive')" 
                            class="flex items-center justify-center px-4 py-2 bg-yellow-100 text-yellow-800 rounded-lg hover:bg-yellow-200 transition-colors">
                        <i class="fas fa-ban mr-2"></i>Deactivate
                    </button>
                @else
                    <button onclick="toggleStatus('{{ $foq->id }}', 'active')" 
                            class="flex items-center justify-center px-4 py-2 bg-green-100 text-green-800 rounded-lg hover:bg-green-200 transition-colors">
                        <i class="fas fa-check-circle mr-2"></i>Activate
                    </button>
                @endif

                @if($foq->is_featured)
                    <button onclick="toggleFeature('{{ $foq->id }}', false)" 
                            class="flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-800 rounded-lg hover:bg-gray-200 transition-colors">
                        <i class="fas fa-star-half-alt mr-2"></i>Unfeature
                    </button>
                @else
                    <button onclick="toggleFeature('{{ $foq->id }}', true)" 
                            class="flex items-center justify-center px-4 py-2 bg-yellow-100 text-yellow-800 rounded-lg hover:bg-yellow-200 transition-colors">
                        <i class="fas fa-star mr-2"></i>Feature
                    </button>
                @endif

                <button onclick="deleteFoq('{{ $foq->id }}', '{{ addslashes($foq->question) }}')" 
                        class="flex items-center justify-center px-4 py-2 bg-red-100 text-red-800 rounded-lg hover:bg-red-200 transition-colors">
                    <i class="fas fa-trash mr-2"></i>Delete
                </button>

                <button onclick="recordFeedback('{{ $foq->id }}', true)" 
                        class="flex items-center justify-center px-4 py-2 bg-green-100 text-green-800 rounded-lg hover:bg-green-200 transition-colors">
                    <i class="fas fa-thumbs-up mr-2"></i>Test Helpful
                </button>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Basic Information -->
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Information</h3>
            
            <div class="space-y-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">ID</dt>
                    <dd class="text-sm text-gray-900">{{ $foq->id }}</dd>
                </div>

                @if($foq->slug)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">URL Slug</dt>
                        <dd class="text-sm text-gray-900 font-mono">{{ $foq->slug }}</dd>
                    </div>
                @endif

                <div>
                    <dt class="text-sm font-medium text-gray-500">Language</dt>
                    <dd class="text-sm text-gray-900">{{ strtoupper($foq->language ?? 'EN') }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-500">Display Order</dt>
                    <dd class="text-sm text-gray-900">{{ $foq->display_order ?? 0 }}</dd>
                </div>
            </div>
        </div>

        <!-- Dates -->
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Timestamps</h3>
            
            <div class="space-y-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Created</dt>
                    <dd class="text-sm text-gray-900">
                        {{ $foq->created_at ? $foq->created_at->format('M j, Y g:i A') : 'Unknown' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                    <dd class="text-sm text-gray-900">
                        {{ $foq->updated_at ? $foq->updated_at->format('M j, Y g:i A') : 'Unknown' }}
                    </dd>
                </div>

                @if($foq->published_at)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Published</dt>
                        <dd class="text-sm text-gray-900">
                            {{ $foq->published_at->format('M j, Y g:i A') }}
                        </dd>
                    </div>
                @endif
            </div>
        </div>

        <!-- Creator Information -->
        @if($foq->created_by || $foq->updated_by)
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Contributors</h3>
                
                <div class="space-y-4">
                    @if($foq->created_by)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Created By</dt>
                            <dd class="text-sm text-gray-900">{{ $foq->created_by }}</dd>
                        </div>
                    @endif

                    @if($foq->updated_by)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Last Updated By</dt>
                            <dd class="text-sm text-gray-900">{{ $foq->updated_by }}</dd>
                        </div>
                    @endif
                </div>
            </div>
        @endif
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

    async function toggleStatus(foqId, status) {
        const actionText = status === 'active' ? 'activate' : 'deactivate';
        if (!confirm(`Are you sure you want to ${actionText} this FOQ?`)) {
            return;
        }
        
        try {
            const response = await fetch(`{{ route('foqs.update-status', ['id' => ':id']) }}`.replace(':id', foqId), {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: status })
            });
            
            if (response.ok) {
                const result = await response.json();
                showNotification(result.message || `FOQ ${actionText}d successfully`, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                const result = await response.json();
                showNotification(`Failed to ${actionText} FOQ: ` + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Toggle status error:', error);
            showNotification(`Error ${actionText}ing FOQ: Connection failed`, 'error');
        }
    }

    async function deleteFoq(foqId, question) {
        if (!confirm(`Are you sure you want to delete FOQ "${question}"? This action cannot be undone.`)) {
            return;
        }
        
        try {
            const response = await fetch(`{{ route('foqs.destroy', ['id' => ':id']) }}`.replace(':id', foqId), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            
            if (response.ok) {
                const result = await response.json();
                showNotification(result.message || 'FOQ deleted successfully', 'success');
                setTimeout(() => window.location.href = '{{ route("foqs.index") }}', 1500);
            } else {
                const result = await response.json();
                showNotification('Failed to delete FOQ: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Delete error:', error);
            showNotification('Error deleting FOQ: Connection failed', 'error');
        }
    }

    async function recordFeedback(foqId, helpful) {
        try {
            const response = await fetch(`{{ route('foqs.feedback', ['id' => ':id']) }}`.replace(':id', foqId), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ helpful: helpful })
            });
            
            if (response.ok) {
                const result = await response.json();
                showNotification(result.message || 'Feedback recorded!', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                const result = await response.json();
                showNotification('Failed to record feedback: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Feedback error:', error);
            showNotification('Error recording feedback: Connection failed', 'error');
        }
    }

    // Initialize page
    document.addEventListener('DOMContentLoaded', function() {
        const foqId = '{{ $foq->id }}';
        console.log('FOQ Details page loaded for ID:', foqId);
    });
</script>
@endpush