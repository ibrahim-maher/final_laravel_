@extends('admin::layouts.admin')

@section('title', 'Document Management')
@section('page-title', 'Document Management')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-primary">Document Management</h1>
        <p class="text-gray-600 mt-1">Manage driver documents and verification (Total: {{ $totalDocuments }})</p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('admin.documents.verification-queue') }}" 
           class="bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 transition-colors">
            <i class="fas fa-clock mr-2"></i>Verification Queue
            @if(count($pendingDocuments) > 0)
                <span class="bg-orange-800 text-xs px-2 py-1 rounded-full ml-2">{{ count($pendingDocuments) }}</span>
            @endif
        </a>
        <a href="{{ route('admin.documents.create') }}" 
           class="bg-success text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors">
            <i class="fas fa-plus mr-2"></i>Upload Document
        </a>
     
        <a href="{{ route('admin.dashboard') }}" 
           class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
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

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                <i class="fas fa-clock text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Pending Verification</p>
                <p class="text-2xl font-bold text-gray-900">{{ count($pendingDocuments) }}</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100 text-red-600 mr-4">
                <i class="fas fa-exclamation-triangle text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Expired</p>
                <p class="text-2xl font-bold text-gray-900">{{ count($expiredDocuments) }}</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-orange-100 text-orange-600 mr-4">
                <i class="fas fa-calendar-times text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Expiring Soon</p>
                <p class="text-2xl font-bold text-gray-900">{{ count($expiringSoonDocuments) }}</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                <i class="fas fa-file-alt text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Total Documents</p>
                <p class="text-2xl font-bold text-gray-900">{{ $totalDocuments }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters and Search -->
<div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
    <form method="GET" action="{{ route('admin.documents.index') }}" class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-64">
            <input type="text" name="search" value="{{ $search ?? '' }}" 
                   placeholder="Search by document name, number, driver name..."
                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
        </div>
        <div class="min-w-48">
            <select name="document_type" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Document Types</option>
                @foreach($documentTypes as $key => $label)
                    <option value="{{ $key }}" {{ ($document_type ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-48">
            <select name="verification_status" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Verification Status</option>
                @foreach($verificationStatuses as $key => $label)
                    <option value="{{ $key }}" {{ ($verification_status ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-48">
            <select name="expiry_status" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Expiry Status</option>
                @foreach($expiryStatuses as $key => $label)
                    <option value="{{ $key }}" {{ ($expiry_status ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-32">
            <select name="limit" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="25" {{ ($limit ?? 50) == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ ($limit ?? 50) == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ ($limit ?? 50) == 100 ? 'selected' : '' }}>100</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-search mr-2"></i>Search
            </button>
            <a href="{{ route('admin.documents.index') }}" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                <i class="fas fa-times mr-2"></i>Clear
            </a>
        </div>
    </form>
</div>

<!-- Bulk Actions -->
<div class="bg-white rounded-lg shadow-sm border mb-6" id="bulkActionsBar" style="display: none;">
    <div class="p-4 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <span class="text-sm text-gray-600">
                <span id="selectedCount">0</span> documents selected
            </span>
            <div class="flex gap-2">
                <button onclick="bulkAction('verify')" class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700">
                    <i class="fas fa-check mr-1"></i>Verify
                </button>
                <button onclick="bulkAction('reject')" class="bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700">
                    <i class="fas fa-times mr-1"></i>Reject
                </button>
                <button onclick="bulkAction('delete')" class="bg-gray-600 text-white px-3 py-1 rounded text-sm hover:bg-gray-700">
                    <i class="fas fa-trash mr-1"></i>Delete
                </button>
            </div>
        </div>
        <button onclick="clearSelection()" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>

<!-- Documents Table -->
<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    <div class="p-6 border-b">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold">
                <i class="fas fa-file-alt mr-2 text-primary"></i>Documents List
            </h2>
           
        </div>
    </div>
    
    @if(count($documents) > 0)
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left">
                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" 
                               class="rounded border-gray-300 text-primary focus:ring-primary">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Document Info
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Driver
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Type & Details
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Expiry
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Uploaded
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($documents as $document)
                @php
                    $documentId = $document['id'];
                    $documentName = $document['document_name'] ?? ucfirst(str_replace('_', ' ', $document['document_type'] ?? 'Document'));
                    $documentType = $document['document_type'] ?? 'unknown';
                    $verificationStatus = $document['verification_status'] ?? 'pending';
                    $driverName = $document['driver_name'] ?? 'Unknown Driver';
                    $driverEmail = $document['driver_email'] ?? 'Unknown';
                    
                    $uploadedAt = null;
                    $uploadedDisplay = 'Unknown';
                    $uploadedHuman = 'Date not available';
                    
                    if (!empty($document['created_at'])) {
                        try {
                            $uploadedAt = \Carbon\Carbon::parse($document['created_at']);
                            $uploadedDisplay = $uploadedAt->format('M d, Y');
                            $uploadedHuman = $uploadedAt->diffForHumans();
                        } catch (\Exception $e) {
                            // Handle date parsing error
                        }
                    }
                    
                    $expiryDate = null;
                    $expiryStatus = 'no_expiry';
                    $expiryDisplay = 'No expiry';
                    
                    if (!empty($document['expiry_date'])) {
                        try {
                            $expiryDate = \Carbon\Carbon::parse($document['expiry_date']);
                            $expiryDisplay = $expiryDate->format('M d, Y');
                            
                            if ($expiryDate->isPast()) {
                                $expiryStatus = 'expired';
                            } elseif ($expiryDate->lte(now()->addDays(30))) {
                                $expiryStatus = 'expiring_soon';
                            } else {
                                $expiryStatus = 'valid';
                            }
                        } catch (\Exception $e) {
                            // Handle date parsing error
                        }
                    }
                @endphp
                <tr data-document-id="{{ $documentId }}" class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <input type="checkbox" class="document-checkbox rounded border-gray-300 text-primary focus:ring-primary" 
                               value="{{ $documentId }}" onchange="updateBulkActions()">
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 mr-3">
                                @switch($documentType)
                                    @case('drivers_license')
                                        <i class="fas fa-id-card text-blue-500 text-xl"></i>
                                        @break
                                    @case('vehicle_registration')
                                        <i class="fas fa-car text-green-500 text-xl"></i>
                                        @break
                                    @case('insurance')
                                        <i class="fas fa-shield-alt text-purple-500 text-xl"></i>
                                        @break
                                    @case('passport')
                                        <i class="fas fa-passport text-indigo-500 text-xl"></i>
                                        @break
                                    @default
                                        <i class="fas fa-file-alt text-gray-500 text-xl"></i>
                                @endswitch
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">{{ $documentName }}</div>
                                @if(!empty($document['document_number']))
                                    <div class="text-sm text-gray-500">{{ $document['document_number'] }}</div>
                                @endif
                                <div class="text-xs text-gray-400">
                                    ID: {{ substr($documentId, 0, 12) }}{{ strlen($documentId) > 12 ? '...' : '' }}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">{{ $driverName }}</div>
                        <div class="text-sm text-gray-500">{{ $driverEmail }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">{{ ucfirst(str_replace('_', ' ', $documentType)) }}</div>
                        @if(!empty($document['issuing_authority']))
                            <div class="text-xs text-gray-500">{{ $document['issuing_authority'] }}</div>
                        @endif
                        @if(!empty($document['issuing_country']))
                            <div class="text-xs text-gray-500">{{ $document['issuing_country'] }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($verificationStatus === 'verified')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>Verified
                            </span>
                        @elseif($verificationStatus === 'rejected')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                <i class="fas fa-times-circle mr-1"></i>Rejected
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                <i class="fas fa-clock mr-1"></i>Pending
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">{{ $expiryDisplay }}</div>
                        @if($expiryStatus === 'expired')
                            <div class="text-xs text-red-600 font-medium">Expired</div>
                        @elseif($expiryStatus === 'expiring_soon')
                            <div class="text-xs text-orange-600 font-medium">Expiring Soon</div>
                        @elseif($expiryStatus === 'valid')
                            <div class="text-xs text-green-600">Valid</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        <div>{{ $uploadedDisplay }}</div>
                        <div class="text-xs text-gray-500">{{ $uploadedHuman }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.documents.show', $documentId) }}" 
                               class="text-primary hover:text-blue-700 p-1" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.documents.download', $documentId) }}" 
                               class="text-green-600 hover:text-green-800 p-1" title="Download">
                                <i class="fas fa-download"></i>
                            </a>
                            @if($verificationStatus === 'pending')
                                <button onclick="verifyDocument('{{ $documentId }}')" 
                                        class="text-blue-600 hover:text-blue-800 p-1" title="Verify Document">
                                    <i class="fas fa-check-circle"></i>
                                </button>
                                <button onclick="rejectDocument('{{ $documentId }}')" 
                                        class="text-red-600 hover:text-red-800 p-1" title="Reject Document">
                                    <i class="fas fa-times-circle"></i>
                                </button>
                            @endif
                            <a href="{{ route('admin.documents.edit', $documentId) }}" 
                               class="text-yellow-600 hover:text-yellow-800 p-1" title="Edit Document">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button onclick="deleteDocument('{{ $documentId }}', '{{ addslashes($documentName) }}')" 
                                    class="text-red-600 hover:text-red-800 p-1" title="Delete Document">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <!-- Pagination Info -->
    <div class="px-6 py-3 border-t bg-gray-50">
        <div class="flex justify-between items-center">
            <div class="text-sm text-gray-700">
                Showing {{ count($documents) }} of {{ $totalDocuments }} documents
            </div>
            <div class="text-sm text-gray-500">
                Filtered results based on current criteria
            </div>
        </div>
    </div>
    @else
    <div class="text-center py-12">
        <i class="fas fa-file-alt text-4xl text-gray-400 mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No Documents Found</h3>
        <p class="text-gray-500 mb-4">
            @if(isset($search) && $search)
                No documents match your search criteria "{{ $search }}". Try adjusting your filters.
            @else
                No documents found in the system. Upload some documents to get started.
            @endif
        </p>
        <div class="flex justify-center gap-3">
            <a href="{{ route('admin.documents.create') }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>Upload First Document
            </a>
        </div>
    </div>
    @endif
</div>
@endsection
@push('scripts')
<script>
    let selectedDocuments = new Set();

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

    async function deleteDocument(documentId, documentName) {
        if (!confirm(`Are you sure you want to delete document "${documentName}"? This action cannot be undone.`)) {
            return;
        }
        
        try {
            const response = await fetch(`/driver/admin/documents/${documentId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                showNotification('Document deleted successfully', 'success');
                const row = document.querySelector(`tr[data-document-id="${documentId}"]`);
                if (row) row.remove();
            } else {
                showNotification('Failed to delete document: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Delete error:', error);
            showNotification('Error deleting document: Connection failed', 'error');
        }
    }

    function toggleSelectAll() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.document-checkbox');
        
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAll.checked;
            if (selectAll.checked) {
                selectedDocuments.add(checkbox.value);
            } else {
                selectedDocuments.delete(checkbox.value);
            }
        });
        
        updateBulkActions();
    }

    function updateBulkActions() {
        const checkboxes = document.querySelectorAll('.document-checkbox:checked');
        const bulkBar = document.getElementById('bulkActionsBar');
        const selectedCount = document.getElementById('selectedCount');
        
        selectedDocuments.clear();
        checkboxes.forEach(checkbox => selectedDocuments.add(checkbox.value));
        
        if (selectedDocuments.size > 0) {
            bulkBar.style.display = 'block';
            selectedCount.textContent = selectedDocuments.size;
        } else {
            bulkBar.style.display = 'none';
        }
        
        const selectAll = document.getElementById('selectAll');
        const allCheckboxes = document.querySelectorAll('.document-checkbox');
        selectAll.checked = selectedDocuments.size === allCheckboxes.length && allCheckboxes.length > 0;
    }

    function clearSelection() {
        selectedDocuments.clear();
        document.querySelectorAll('.document-checkbox').forEach(checkbox => checkbox.checked = false);
        document.getElementById('selectAll').checked = false;
        document.getElementById('bulkActionsBar').style.display = 'none';
    }

    async function bulkAction(action) {
        if (selectedDocuments.size === 0) return;
        
        let confirmMessage = `Are you sure you want to ${action} ${selectedDocuments.size} selected documents?`;
        let requestData = { action: action, document_ids: Array.from(selectedDocuments) };
        
        if (action === 'reject') {
            const reason = prompt('Please provide a reason for rejection:');
            if (!reason) return;
            requestData.rejection_reason = reason;
        }
        
        if (!confirm(confirmMessage)) return;
        
        try {
            const response = await fetch('{{ route("admin.documents.bulk-action") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(requestData)
            });
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                showNotification(result.message || `Bulk ${action} completed successfully`, 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showNotification('Bulk action failed: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Bulk action error:', error);
            showNotification('Error performing bulk action: Connection failed', 'error');
        }
    }

    async function exportDocuments(format) {
        try {
            const url = new URL('');
            url.searchParams.append('format', format);
            
            // Add current filters
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('search')) url.searchParams.append('search', urlParams.get('search'));
            if (urlParams.get('document_type')) url.searchParams.append('document_type', urlParams.get('document_type'));
            if (urlParams.get('verification_status')) url.searchParams.append('verification_status', urlParams.get('verification_status'));
            if (urlParams.get('expiry_status')) url.searchParams.append('expiry_status', urlParams.get('expiry_status'));
            
            window.open(url.toString(), '_blank');
            showNotification('Export started. Download will begin shortly.', 'info');
            
        } catch (error) {
            console.error('Export error:', error);
            showNotification('Error exporting documents', 'error');
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Document management page initialized');
        updateBulkActions();
    });
</script>
@endpush