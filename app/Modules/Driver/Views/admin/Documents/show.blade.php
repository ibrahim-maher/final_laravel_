@extends('admin::layouts.admin')

@section('title', 'Document Details')
@section('page-title', 'Document Details')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-primary">Document Details</h1>
        <p class="text-gray-600 mt-1">Detailed information for document ID: {{ substr($document['id'], 0, 12) }}{{ strlen($document['id']) > 12 ? '...' : '' }}</p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('admin.documents.index') }}" 
           class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back to Documents
        </a>
        <a href="{{ route('admin.documents.edit', $document['id']) }}" 
           class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-colors">
            <i class="fas fa-edit mr-2"></i>Edit Document
        </a>
        <a href="{{ route('admin.documents.download', $document['id']) }}" 
           class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
            <i class="fas fa-download mr-2"></i>Download
        </a>
    </div>
</div>

@if (session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
    </div>
@endif

<div class="bg-white rounded-lg shadow-sm border p-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Document Information -->
        <div>
            <h2 class="text-xl font-semibold mb-4">Document Information</h2>
            <div class="space-y-3">
                <div>
                    <span class="text-sm font-medium text-gray-600">Document Name:</span>
                    <p class="text-gray-900">{{ $document['document_name'] ?? ucfirst(str_replace('_', ' ', $document['document_type'] ?? 'Document')) }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-600">Document Type:</span>
                    <p class="text-gray-900">{{ ucfirst(str_replace('_', ' ', $document['document_type'] ?? 'Unknown')) }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-600">Document Number:</span>
                    <p class="text-gray-900">{{ $document['document_number'] ?? 'N/A' }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-600">Verification Status:</span>
                    @if ($document['verification_status'] === 'verified')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="fas fa-check-circle mr-1"></i>Verified
                        </span>
                    @elseif ($document['verification_status'] === 'rejected')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <i class="fas fa-times-circle mr-1"></i>Rejected
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            <i class="fas fa-clock mr-1"></i>Pending
                        </span>
                    @endif
                </div>
                @if ($document['rejection_reason'])
                    <div>
                        <span class="text-sm font-medium text-gray-600">Rejection Reason:</span>
                        <p class="text-gray-900">{{ $document['rejection_reason'] }}</p>
                    </div>
                @endif
                @if ($document['verification_notes'])
                    <div>
                        <span class="text-sm font-medium text-gray-600">Verification Notes:</span>
                        <p class="text-gray-900">{{ $document['verification_notes'] }}</p>
                    </div>
                @endif
                <div>
                    <span class="text-sm font-medium text-gray-600">File Name:</span>
                    <p class="text-gray-900">{{ $document['file_name'] ?? 'N/A' }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-600">File Size:</span>
                    <p class="text-gray-900">{{ $document['file_size'] ? round($document['file_size'] / 1024, 2) . ' KB' : 'Unknown' }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-600">File Type:</span>
                    <p class="text-gray-900">{{ $document['file_type'] ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <!-- Issuing Details -->
        <div>
            <h2 class="text-xl font-semibold mb-4">Issuing Details</h2>
            <div class="space-y-3">
                <div>
                    <span class="text-sm font-medium text-gray-600">Issue Date:</span>
                    <p class="text-gray-900">{{ $document['issue_date'] ? \Carbon\Carbon::parse($document['issue_date'])->format('M d, Y') : 'N/A' }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-600">Expiry Date:</span>
                    <p class="text-gray-900">
                        {{ $document['expiry_date'] ? \Carbon\Carbon::parse($document['expiry_date'])->format('M d, Y') : 'No expiry' }}
                        @if ($document['expiry_date'])
                            @php
                                $expiryDate = \Carbon\Carbon::parse($document['expiry_date']);
                                $expiryStatus = $expiryDate->isPast() ? 'expired' : ($expiryDate->lte(now()->addDays(30)) ? 'expiring_soon' : 'valid');
                            @endphp
                            @if ($expiryStatus === 'expired')
                                <span class="text-xs text-red-600 font-medium ml-2">Expired</span>
                            @elseif ($expiryStatus === 'expiring_soon')
                                <span class="text-xs text-orange-600 font-medium ml-2">Expiring Soon</span>
                            @elseif ($expiryStatus === 'valid')
                                <span class="text-xs text-green-600 ml-2">Valid</span>
                            @endif
                        @endif
                    </p>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-600">Issuing Authority:</span>
                    <p class="text-gray-900">{{ $document['issuing_authority'] ?? 'N/A' }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-600">Issuing Country:</span>
                    <p class="text-gray-900">{{ $document['issuing_country'] ?? 'N/A' }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-600">Issuing State:</span>
                    <p class="text-gray-900">{{ $document['issuing_state'] ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <!-- Driver Information -->
        <div>
            <h2 class="text-xl font-semibold mb-4">Driver Information</h2>
            <div class="space-y-3">
                <div>
                    <span class="text-sm font-medium text-gray-600">Driver Name:</span>
                    <p class="text-gray-900">{{ $driver['name'] ?? 'Unknown Driver' }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-600">Email:</span>
                    <p class="text-gray-900">{{ $driver['email'] ?? 'N/A' }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-600">Firebase UID:</span>
                    <p class="text-gray-900">{{ $document['driver_firebase_uid'] ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <!-- Additional Information -->
        <div>
            <h2 class="text-xl font-semibold mb-4">Additional Information</h2>
            <div class="space-y-3">
                <div>
                    <span class="text-sm font-medium text-gray-600">Uploaded At:</span>
                    <p class="text-gray-900">
                        {{ $document['created_at'] ? \Carbon\Carbon::parse($document['created_at'])->format('M d, Y H:i') : 'Unknown' }}
                        @if ($document['created_at'])
                            <span class="text-xs text-gray-500">({{ \Carbon\Carbon::parse($document['created_at'])->diffForHumans() }})</span>
                        @endif
                    </p>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-600">Last Updated:</span>
                    <p class="text-gray-900">
                        {{ $document['updated_at'] ? \Carbon\Carbon::parse($document['updated_at'])->format('M d, Y H:i') : 'Unknown' }}
                    </p>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-600">Download Count:</span>
                    <p class="text-gray-900">{{ $document['download_count'] ?? 0 }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-600">Last Downloaded:</span>
                    <p class="text-gray-900">
                        {{ $document['last_downloaded_at'] ? \Carbon\Carbon::parse($document['last_downloaded_at'])->format('M d, Y H:i') : 'Never' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    @if ($document['verification_status'] === 'pending')
        <div class="mt-6 flex gap-3">
            <button onclick="verifyDocument('{{ $document['id'] }}')" 
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-check-circle mr-2"></i>Verify Document
            </button>
            <button onclick="rejectDocument('{{ $document['id'] }}')" 
                    class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                <i class="fas fa-times-circle mr-2"></i>Reject Document
            </button>
        </div>
    @endif
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

    async function verifyDocument(documentId) {
        if (!confirm('Are you sure you want to verify this document?')) {
            return;
        }
        
        try {
            const response = await fetch(`/driver/admin/documents/${documentId}/verify`, {
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
            console.error('Verify error:', error);
            showNotification('Error verifying document: Connection failed', 'error');
        }
    }

    async function rejectDocument(documentId) {
        const reason = prompt('Please provide a reason for rejection:');
        if (!reason) return;
        
        try {
            const response = await fetch(`/driver/admin/documents/${documentId}/reject`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ rejection_reason: reason })
            });
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                showNotification('Document rejected successfully', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showNotification('Failed to reject document: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Reject error:', error);
            showNotification('Error rejecting document: Connection failed', 'error');
        }
    }
</script>
@endpush