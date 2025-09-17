{{-- Admin Pages Index View --}}{{-- resources/views/page/admin/pages/index.blade.php --}}

@extends('admin::layouts.admin')

@section('title', 'Page Management')
@section('page-title', 'Page Management')

@push('styles')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-primary">Page Management</h1>
        <p class="text-gray-600 mt-1">Manage static pages and content (Total: {{ $totalPages }})</p>
    </div>
    <div class="flex gap-3">
        <button onclick="syncFirebasePages()"
            class="bg-warning text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition-colors"
            id="syncBtn">
            <i class="fas fa-sync mr-2"></i>Sync Firebase
        </button>
        <a href="{{ route('pages.create') }}"
            class="bg-success text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors">
            <i class="fas fa-plus mr-2"></i>Create Page
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

<!-- Filters and Search -->
<div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
    <form method="GET" action="{{ route('pages.index') }}" class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-64">
            <input type="text" name="search" value="{{ $search ?? '' }}"
                placeholder="Search pages, content, and types"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
        </div>
        <div class="min-w-48">
            <select name="status" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Status</option>
                <option value="active" {{ ($status ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ ($status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="draft" {{ ($status ?? '') === 'draft' ? 'selected' : '' }}>Draft</option>
            </select>
        </div>
        <div class="min-w-48">
            <select name="type" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Types</option>
                <option value="terms" {{ ($type ?? '') === 'terms' ? 'selected' : '' }}>Terms & Conditions</option>
                <option value="privacy" {{ ($type ?? '') === 'privacy' ? 'selected' : '' }}>Privacy Policy</option>
                <option value="about" {{ ($type ?? '') === 'about' ? 'selected' : '' }}>About Us</option>
                <option value="contact" {{ ($type ?? '') === 'contact' ? 'selected' : '' }}>Contact</option>
                <option value="faq" {{ ($type ?? '') === 'faq' ? 'selected' : '' }}>FAQ</option>
                <option value="help" {{ ($type ?? '') === 'help' ? 'selected' : '' }}>Help</option>
                <option value="support" {{ ($type ?? '') === 'support' ? 'selected' : '' }}>Support</option>
                <option value="legal" {{ ($type ?? '') === 'legal' ? 'selected' : '' }}>Legal</option>
                <option value="policy" {{ ($type ?? '') === 'policy' ? 'selected' : '' }}>Policy</option>
                <option value="general" {{ ($type ?? '') === 'general' ? 'selected' : '' }}>General</option>
            </select>
        </div>
        <div class="min-w-48">
            <select name="template" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Templates</option>
                <option value="default" {{ ($template ?? '') === 'default' ? 'selected' : '' }}>Default</option>
                <option value="simple" {{ ($template ?? '') === 'simple' ? 'selected' : '' }}>Simple</option>
                <option value="full-width" {{ ($template ?? '') === 'full-width' ? 'selected' : '' }}>Full Width</option>
                <option value="sidebar" {{ ($template ?? '') === 'sidebar' ? 'selected' : '' }}>With Sidebar</option>
                <option value="legal" {{ ($template ?? '') === 'legal' ? 'selected' : '' }}>Legal Document</option>
            </select>
        </div>
        <div class="min-w-32">
            <select name="limit" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="25" {{ ($limit ?? 25) == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ ($limit ?? 25) == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ ($limit ?? 25) == 100 ? 'selected' : '' }}>100</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-search mr-2"></i>Filter
            </button>
            <a href="{{ route('pages.index') }}" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors">
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
                <span id="selectedCount">0</span> pages selected
            </span>
            <div class="flex gap-2">
                <button onclick="bulkAction('activate')" class="bg-success text-white px-3 py-1 rounded text-sm hover:bg-green-600">
                    <i class="fas fa-check mr-1"></i>Activate
                </button>
                <button onclick="bulkAction('deactivate')" class="bg-warning text-white px-3 py-1 rounded text-sm hover:bg-yellow-600">
                    <i class="fas fa-ban mr-1"></i>Deactivate
                </button>
                <button onclick="bulkAction('feature')" class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">
                    <i class="fas fa-star mr-1"></i>Feature
                </button>
                <button onclick="bulkAction('delete')" class="bg-danger text-white px-3 py-1 rounded text-sm hover:bg-red-600">
                    <i class="fas fa-trash mr-1"></i>Delete
                </button>
            </div>
        </div>
        <button onclick="clearSelection()" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>

<!-- Firebase Sync Status -->
<div id="syncStatus" class="hidden mb-4">
    <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded">
        <div class="flex items-center">
            <i class="fas fa-sync fa-spin mr-2"></i>
            <span>Firebase sync in progress... Check logs for detailed progress.</span>
        </div>
    </div>
</div>

<!-- Pages Table -->
<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    @if(count($pages) > 0)
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left">
                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()"
                            class="rounded border-gray-300 text-primary focus:ring-primary">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Page Title
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Type
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Template
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Stats
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($pages as $page)
                <tr data-page-id="{{ $page->id }}" class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <input type="checkbox" class="page-checkbox rounded border-gray-300 text-primary focus:ring-primary"
                            value="{{ $page->id }}" onchange="updateBulkActions()">
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-start">
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ Str::limit($page->title, 60) }}
                                    @if($page->is_featured)
                                    <i class="fas fa-star text-yellow-500 ml-2" title="Featured"></i>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    {{ $page->excerpt ?? Str::limit(strip_tags($page->content), 80) }}
                                </div>
                                <div class="text-xs text-blue-600 mt-1">
                                    <i class="fas fa-link mr-1"></i>{{ $page->slug }}
                                </div>
                                @if($page->show_in_footer || $page->show_in_header)
                                <div class="flex gap-2 mt-1">
                                    @if($page->show_in_footer)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                        Footer
                                    </span>
                                    @endif
                                    @if($page->show_in_header)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                        Header
                                    </span>
                                    @endif
                                </div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            @if($page->type === 'terms') bg-red-100 text-red-800
                            @elseif($page->type === 'privacy') bg-purple-100 text-purple-800
                            @elseif($page->type === 'about') bg-blue-100 text-blue-800
                            @elseif($page->type === 'contact') bg-green-100 text-green-800
                            @elseif($page->type === 'faq') bg-yellow-100 text-yellow-800
                            @elseif($page->type === 'help') bg-indigo-100 text-indigo-800
                            @elseif($page->type === 'support') bg-pink-100 text-pink-800
                            @elseif($page->type === 'legal') bg-gray-100 text-gray-800
                            @elseif($page->type === 'policy') bg-orange-100 text-orange-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            {{ ucfirst(str_replace('_', ' ', $page->type)) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ ucfirst(str_replace('-', ' ', $page->template ?? 'default')) }}
                    </td>
                    <td class="px-6 py-4">
                        @if($page->status === 'active')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="fas fa-check-circle mr-1"></i>Active
                        </span>
                        @elseif($page->status === 'inactive')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <i class="fas fa-times-circle mr-1"></i>Inactive
                        </span>
                        @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            <i class="fas fa-edit mr-1"></i>Draft
                        </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        <div class="space-y-1">
                            <div>Views: {{ number_format($page->view_count ?? 0) }}</div>
                            <div class="text-xs text-gray-500">
                                Words: {{ $page->word_count ?? 0 }}
                            </div>
                            <div class="text-xs text-gray-500">
                                Reading: {{ $page->reading_time ?? 0 }}min
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('pages.show', $page->id) }}"
                                class="text-blue-600 hover:text-blue-800 p-1" title="View Page">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('pages.edit', $page->id) }}"
                                class="text-green-600 hover:text-green-800 p-1" title="Edit Page">
                                <i class="fas fa-edit"></i>
                            </a>
                            @if($page->status === 'active')
                            <button onclick="togglePageStatus({{ $page->id }}, 'inactive')"
                                class="text-yellow-600 hover:text-yellow-800 p-1" title="Deactivate Page">
                                <i class="fas fa-ban"></i>
                            </button>
                            @else
                            <button onclick="togglePageStatus({{ $page->id }}, 'active')"
                                class="text-green-600 hover:text-green-800 p-1" title="Activate Page">
                                <i class="fas fa-check-circle"></i>
                            </button>
                            @endif
                            <a href="{{ route('pages.duplicate', $page->id) }}"
                                class="text-blue-600 hover:text-blue-800 p-1" title="Duplicate Page">
                                <i class="fas fa-copy"></i>
                            </a>
                            <button onclick="deletePage({{ $page->id }}, '{{ addslashes($page->title) }}')"
                                class="text-red-600 hover:text-red-800 p-1" title="Delete Page">
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
                Showing {{ count($pages) }} of {{ $totalPages }} pages
                <div class="text-xs text-gray-500 mt-1">
                    Rows per page: {{ $limit ?? 25 }}
                    <span class="ml-4">1-{{ count($pages) }} of {{ $totalPages }}</span>
                </div>
            </div>
            <div class="flex gap-2">
                <button onclick="exportPages('csv')" class="text-primary hover:bg-blue-50 px-3 py-1 rounded">
                    <i class="fas fa-file-csv mr-1"></i>CSV Export
                </button>
            </div>
        </div>
    </div>
    @else
    <div class="text-center py-12">
        <i class="fas fa-file-alt text-4xl text-gray-400 mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No Pages Found</h3>
        <p class="text-gray-500 mb-4">
            @if(isset($search) && $search)
            No pages match your search criteria "{{ $search }}". Try adjusting your filters.
            @else
            No pages found in the system. Create some pages to get started.
            @endif
        </p>
        <div class="flex justify-center gap-3">
            <a href="{{ route('pages.create') }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>Create First Page
            </a>
            <button onclick="syncFirebasePages()" class="bg-warning text-white px-4 py-2 rounded-lg hover:bg-yellow-600">
                <i class="fas fa-sync mr-2"></i>Sync Firebase Pages
            </button>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // Page Management JavaScript
    let selectedPages = new Set();

    // Route URLs for JavaScript
    const routes = {
        syncFirebase: '{{ route("pages.sync-firebase") }}',
        updateStatus: '{{ route("pages.update-status", ["id" => ":id"]) }}',
        destroy: '{{ route("pages.destroy", ["id" => ":id"]) }}',
        bulkAction: '{{ route("pages.bulk-action") }}',
        export: '{{ route("pages.export") }}'
    };

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

    async function syncFirebasePages() {
        if (!confirm('This will sync pages to Firebase in the background. Continue?')) {
            return;
        }

        const btn = document.getElementById('syncBtn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';

        try {
            const response = await fetch(routes.syncFirebase, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });

            if (response.ok) {
                const result = await response.json();
                showNotification(result.message || 'Sync started successfully!', 'success');
            } else {
                showNotification('Failed to start sync', 'error');
            }
        } catch (error) {
            console.error('Sync error:', error);
            showNotification('Error starting sync: Connection failed', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }

    async function deletePage(pageId, title) {
        if (!confirm(`Are you sure you want to delete page "${title}"? This action cannot be undone.`)) {
            return;
        }

        try {
            const url = routes.destroy.replace(':id', pageId);
            const response = await fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const result = await response.json();
                showNotification(result.message || 'Page deleted successfully', 'success');
                const row = document.querySelector(`tr[data-page-id="${pageId}"]`);
                if (row) row.remove();

                setTimeout(() => window.location.reload(), 1000);
            } else {
                const result = await response.json();
                showNotification('Failed to delete page: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Delete error:', error);
            showNotification('Error deleting page: Connection failed', 'error');
        }
    }

    async function togglePageStatus(pageId, status) {
        const actionText = status === 'active' ? 'activate' : 'deactivate';
        if (!confirm(`Are you sure you want to ${actionText} this page?`)) {
            return;
        }

        try {
            const url = routes.updateStatus.replace(':id', pageId);
            const response = await fetch(url, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    status: status
                })
            });

            if (response.ok) {
                const result = await response.json();
                showNotification(result.message || `Page ${actionText}d successfully`, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                const result = await response.json();
                showNotification(`Failed to ${actionText} page: ` + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Toggle status error:', error);
            showNotification(`Error ${actionText}ing page: Connection failed`, 'error');
        }
    }

    function toggleSelectAll() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.page-checkbox');

        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAll.checked;
            if (selectAll.checked) {
                selectedPages.add(parseInt(checkbox.value));
            } else {
                selectedPages.delete(parseInt(checkbox.value));
            }
        });

        updateBulkActions();
    }

    function updateBulkActions() {
        const checkboxes = document.querySelectorAll('.page-checkbox:checked');
        const bulkBar = document.getElementById('bulkActionsBar');
        const selectedCount = document.getElementById('selectedCount');

        selectedPages.clear();
        checkboxes.forEach(checkbox => selectedPages.add(parseInt(checkbox.value)));

        if (selectedPages.size > 0) {
            bulkBar.style.display = 'block';
            selectedCount.textContent = selectedPages.size;
        } else {
            bulkBar.style.display = 'none';
        }

        const selectAll = document.getElementById('selectAll');
        const allCheckboxes = document.querySelectorAll('.page-checkbox');
        selectAll.checked = selectedPages.size === allCheckboxes.length && allCheckboxes.length > 0;
        selectAll.indeterminate = selectedPages.size > 0 && selectedPages.size < allCheckboxes.length;
    }

    function clearSelection() {
        selectedPages.clear();
        document.querySelectorAll('.page-checkbox').forEach(checkbox => checkbox.checked = false);
        document.getElementById('selectAll').checked = false;
        document.getElementById('selectAll').indeterminate = false;
        document.getElementById('bulkActionsBar').style.display = 'none';
    }

    async function bulkAction(action) {
        if (selectedPages.size === 0) return;

        const actionText = action === 'delete' ? 'delete' : action;
        if (!confirm(`Are you sure you want to ${actionText} ${selectedPages.size} selected pages?`)) {
            return;
        }

        try {
            const response = await fetch(routes.bulkAction, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    action: action,
                    page_ids: Array.from(selectedPages)
                })
            });

            if (response.ok) {
                const result = await response.json();
                showNotification(result.message || `Bulk ${actionText} completed successfully`, 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                const result = await response.json();
                showNotification('Bulk action failed: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Bulk action error:', error);
            showNotification('Error performing bulk action: Connection failed', 'error');
        }
    }

    async function exportPages(format) {
        try {
            const url = new URL(routes.export);
            url.searchParams.append('format', format);

            // Add current filters
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('search')) url.searchParams.append('search', urlParams.get('search'));
            if (urlParams.get('status')) url.searchParams.append('status', urlParams.get('status'));
            if (urlParams.get('type')) url.searchParams.append('type', urlParams.get('type'));
            if (urlParams.get('template')) url.searchParams.append('template', urlParams.get('template'));

            window.open(url.toString(), '_blank');
            showNotification('Export started. Download will begin shortly.', 'info');

        } catch (error) {
            console.error('Export error:', error);
            showNotification('Error exporting pages', 'error');
        }
    }

    // Initialize page
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Page management page initialized');
        updateBulkActions();

        // Auto-refresh sync status every 30 seconds if sync is in progress
        setInterval(checkSyncStatus, 30000);
    });

    async function checkSyncStatus() {
        try {
            const response = await fetch('{{ route("pages.sync-status") }}', {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const result = await response.json();
                // Update UI based on sync status if needed
                console.log('Sync status:', result);
            }
        } catch (error) {
            // Silently handle sync status check errors
            console.log('Sync status check failed:', error);
        }
    }
</script>
@endpush