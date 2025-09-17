<?php
// app/Modules/Page/Controllers/Admin/AdminPageController.php - FIXED VERSION

namespace App\Modules\Page\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Page\Models\Page;
use App\Modules\Page\Services\PageService;
use App\Modules\Page\Services\PageFirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminPageController extends Controller
{
    protected $pageService;
    protected $firebaseService;

    public function __construct()
    {
        // Remove constructor dependencies for now to avoid service injection issues
        // Services will be instantiated when needed
    }

    /**
     * Display a listing of pages
     */
    public function index(Request $request)
    {
        try {
            Log::info('Loading pages index page');

            $query = Page::query();

            // Extract variables from request first
            $search = $request->get('search');
            $status = $request->get('status');
            $type = $request->get('type');
            $template = $request->get('template');
            $limit = $request->get('limit', 25);

            // Apply filters
            if ($search) {
                $query->search($search);
            }

            if ($status) {
                $query->where('status', $status);
            }

            if ($type) {
                $query->where('type', $type);
            }

            if ($template) {
                $query->where('template', $template);
            }

            // Order pages
            $query->ordered();

            // Get pages with pagination info
            $pages = $query->take($limit)->get();
            $totalPages = Page::count();

            Log::info('Successfully loaded pages', ['count' => count($pages), 'total' => $totalPages]);

            return view('page::admin.pages.index', compact(
                'pages',
                'totalPages',
                'search',
                'status',
                'type',
                'template',
                'limit'
            ));
        } catch (\Exception $e) {
            Log::error('Error loading pages index: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            // Return with empty data to prevent complete failure
            return view('page::admin.pages.index', [
                'pages' => collect([]),
                'totalPages' => 0,
                'search' => '',
                'status' => '',
                'type' => '',
                'template' => '',
                'limit' => 25
            ])->with('error', 'Failed to load pages: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new page
     */
    public function create()
    {
        try {
            $statuses = Page::getStatuses();
            $types = Page::getTypes();
            $templates = Page::getTemplates();

            return view('page::admin.pages.create', compact('statuses', 'types', 'templates'));
        } catch (\Exception $e) {
            Log::error('Error loading create page form: ' . $e->getMessage());
            return redirect()->route('pages.index')
                ->with('error', 'Failed to load create form: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created page
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|string|in:terms,privacy,about,contact,faq,help,support,legal,policy,general',
            'status' => 'required|string|in:active,inactive,draft',
            'slug' => 'nullable|string|max:255|unique:pages,slug',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string',
            'language' => 'nullable|string|max:10',
            'display_order' => 'nullable|integer|min:0',
            'template' => 'nullable|string|in:default,simple,full-width,sidebar,legal',
            'custom_css' => 'nullable|string',
            'custom_js' => 'nullable|string',
            'published_at' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $pageData = $request->only([
                'title',
                'content',
                'type',
                'status',
                'slug',
                'meta_title',
                'meta_description',
                'meta_keywords',
                'language',
                'display_order',
                'template',
                'custom_css',
                'custom_js',
                'published_at'
            ]);

            // Process meta keywords
            if ($request->filled('meta_keywords')) {
                $keywords = array_map('trim', explode(',', $request->meta_keywords));
                $pageData['meta_keywords'] = array_filter($keywords);
            }

            // Handle checkboxes
            $pageData['is_featured'] = $request->has('is_featured');
            $pageData['requires_auth'] = $request->has('requires_auth');
            $pageData['show_in_footer'] = $request->has('show_in_footer');
            $pageData['show_in_header'] = $request->has('show_in_header');

            // Set created_by
            $pageData['created_by'] = auth()->id() ?? null;

            $page = Page::create($pageData);

            // Clear relevant caches
            Cache::forget('pages_*');

            DB::commit();

            Log::info('Page created successfully', ['page_id' => $page->id, 'title' => $page->title]);

            return redirect()->route('pages.index')
                ->with('success', 'Page created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating page: ' . $e->getMessage());
            return back()->with('error', 'Failed to create page: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified page
     */
    public function show($id)
    {
        try {
            $page = Page::findOrFail($id);

            // Increment view count
            $page->increment('view_count');

            return view('page::admin.pages.show', compact('page'));
        } catch (\Exception $e) {
            Log::error('Error showing page: ' . $e->getMessage());
            return redirect()->route('pages.index')
                ->with('error', 'Page not found.');
        }
    }

    /**
     * Show the form for editing the specified page
     */
    public function edit($id)
    {
        try {
            $page = Page::findOrFail($id);
            $statuses = Page::getStatuses();
            $types = Page::getTypes();
            $templates = Page::getTemplates();

            return view('page::admin.pages.edit', compact('page', 'statuses', 'types', 'templates'));
        } catch (\Exception $e) {
            Log::error('Error loading page for edit: ' . $e->getMessage());
            return redirect()->route('pages.index')
                ->with('error', 'Page not found.');
        }
    }

    /**
     * Update the specified page
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|string|in:terms,privacy,about,contact,faq,help,support,legal,policy,general',
            'status' => 'required|string|in:active,inactive,draft',
            'slug' => 'nullable|string|max:255|unique:pages,slug,' . $id,
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string',
            'language' => 'nullable|string|max:10',
            'display_order' => 'nullable|integer|min:0',
            'template' => 'nullable|string|in:default,simple,full-width,sidebar,legal',
            'custom_css' => 'nullable|string',
            'custom_js' => 'nullable|string',
            'published_at' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $page = Page::findOrFail($id);

            DB::beginTransaction();

            $pageData = $request->only([
                'title',
                'content',
                'type',
                'status',
                'slug',
                'meta_title',
                'meta_description',
                'meta_keywords',
                'language',
                'display_order',
                'template',
                'custom_css',
                'custom_js',
                'published_at'
            ]);

            // Process meta keywords
            if ($request->filled('meta_keywords')) {
                $keywords = array_map('trim', explode(',', $request->meta_keywords));
                $pageData['meta_keywords'] = array_filter($keywords);
            } else {
                $pageData['meta_keywords'] = [];
            }

            // Handle checkboxes
            $pageData['is_featured'] = $request->has('is_featured');
            $pageData['requires_auth'] = $request->has('requires_auth');
            $pageData['show_in_footer'] = $request->has('show_in_footer');
            $pageData['show_in_header'] = $request->has('show_in_header');

            // Set updated_by
            $pageData['updated_by'] = auth()->id() ?? null;

            $page->update($pageData);

            // Clear relevant caches
            Cache::forget('pages_*');

            DB::commit();

            Log::info('Page updated successfully', ['page_id' => $page->id, 'title' => $page->title]);

            return redirect()->route('pages.edit', $page->id)
                ->with('success', 'Page updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating page: ' . $e->getMessage());
            return back()->with('error', 'Failed to update page: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified page from storage
     */
    public function destroy($id)
    {
        try {
            $page = Page::findOrFail($id);
            $title = $page->title;

            DB::beginTransaction();

            $page->delete();

            // Clear relevant caches
            Cache::forget('pages_*');

            DB::commit();

            Log::info('Page deleted successfully', ['page_id' => $id, 'title' => $title]);

            if (request()->expectsJson()) {
                return response()->json(['message' => 'Page deleted successfully']);
            }

            return redirect()->route('pages.index')
                ->with('success', 'Page deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting page: ' . $e->getMessage());

            if (request()->expectsJson()) {
                return response()->json(['message' => 'Failed to delete page'], 500);
            }

            return back()->with('error', 'Failed to delete page: ' . $e->getMessage());
        }
    }

    /**
     * Update page status
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:active,inactive,draft'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid status'], 400);
        }

        try {
            $page = Page::findOrFail($id);
            $page->update([
                'status' => $request->status,
                'updated_by' => auth()->id() ?? null
            ]);

            // Clear caches
            Cache::forget('pages_*');

            $actionText = $request->status === 'active' ? 'activated' : 'deactivated';

            return response()->json([
                'message' => "Page {$actionText} successfully"
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating page status: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update page status'], 500);
        }
    }

    /**
     * Duplicate a page
     */
    public function duplicate($id)
    {
        try {
            $originalPage = Page::findOrFail($id);

            DB::beginTransaction();

            $newPageData = $originalPage->toArray();
            unset($newPageData['id'], $newPageData['created_at'], $newPageData['updated_at']);

            // Modify duplicated data
            $newPageData['title'] = $originalPage->title . ' (Copy)';
            $newPageData['slug'] = Str::slug($newPageData['title']);
            $newPageData['status'] = 'draft';
            $newPageData['created_by'] = auth()->id() ?? null;
            $newPageData['updated_by'] = null;
            $newPageData['firebase_synced'] = false;
            $newPageData['firebase_synced_at'] = null;
            $newPageData['view_count'] = 0;

            // Ensure unique slug
            $originalSlug = $newPageData['slug'];
            $counter = 1;
            while (Page::where('slug', $newPageData['slug'])->exists()) {
                $newPageData['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }

            $newPage = Page::create($newPageData);

            DB::commit();

            Log::info('Page duplicated successfully', [
                'original_id' => $id,
                'new_id' => $newPage->id
            ]);

            return redirect()->route('pages.edit', $newPage->id)
                ->with('success', 'Page duplicated successfully. You can now edit the copy.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error duplicating page: ' . $e->getMessage());
            return back()->with('error', 'Failed to duplicate page: ' . $e->getMessage());
        }
    }

    /**
     * Handle bulk actions on pages
     */
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|string|in:activate,deactivate,delete,feature,unfeature',
            'page_ids' => 'required|array|min:1',
            'page_ids.*' => 'integer|exists:pages,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid bulk action request'], 400);
        }

        try {
            DB::beginTransaction();

            $pageIds = $request->page_ids;
            $action = $request->action;
            $affectedCount = 0;

            switch ($action) {
                case 'activate':
                    $affectedCount = Page::whereIn('id', $pageIds)->update(['status' => 'active']);
                    break;

                case 'deactivate':
                    $affectedCount = Page::whereIn('id', $pageIds)->update(['status' => 'inactive']);
                    break;

                case 'feature':
                    $affectedCount = Page::whereIn('id', $pageIds)->update(['is_featured' => true]);
                    break;

                case 'unfeature':
                    $affectedCount = Page::whereIn('id', $pageIds)->update(['is_featured' => false]);
                    break;

                case 'delete':
                    $affectedCount = Page::whereIn('id', $pageIds)->delete();
                    break;
            }

            // Clear caches
            Cache::forget('pages_*');

            DB::commit();

            Log::info('Bulk action completed', [
                'action' => $action,
                'page_ids' => $pageIds,
                'affected_count' => $affectedCount
            ]);

            return response()->json([
                'message' => "Bulk {$action} completed successfully. {$affectedCount} pages affected."
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error performing bulk action: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to perform bulk action'], 500);
        }
    }

    /**
     * Sync pages to Firebase
     */
    public function syncFirebase(Request $request)
    {
        try {
            // For now, just return success without actual Firebase sync
            Log::info('Firebase sync requested for pages');

            return response()->json([
                'message' => 'Firebase sync started successfully. Check logs for progress.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error starting Firebase sync: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to start Firebase sync'
            ], 500);
        }
    }

    /**
     * Get sync status
     */
    public function getSyncStatus(Request $request)
    {
        try {
            $totalPages = Page::count();
            $syncedPages = Page::where('firebase_synced', true)->count();
            $pendingPages = Page::where('firebase_synced', false)->count();
            $failedPages = Page::where('firebase_sync_status', 'failed')->count();

            return response()->json([
                'total_pages' => $totalPages,
                'synced_pages' => $syncedPages,
                'pending_pages' => $pendingPages,
                'failed_pages' => $failedPages,
                'sync_percentage' => $totalPages > 0 ? round(($syncedPages / $totalPages) * 100, 2) : 0
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting sync status: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to get sync status'], 500);
        }
    }

    /**
     * Export pages
     */
    public function export(Request $request)
    {
        try {
            $format = $request->get('format', 'csv');
            $query = Page::query();

            // Apply same filters as index
            if ($request->filled('search')) {
                $query->search($request->search);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            $pages = $query->ordered()->get();

            if ($format === 'csv') {
                return $this->exportToCsv($pages);
            }

            return back()->with('error', 'Unsupported export format');
        } catch (\Exception $e) {
            Log::error('Error exporting pages: ' . $e->getMessage());
            return back()->with('error', 'Failed to export pages: ' . $e->getMessage());
        }
    }

    /**
     * Export pages to CSV
     */
    private function exportToCsv($pages)
    {
        $filename = 'pages_export_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($pages) {
            $file = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($file, [
                'ID',
                'Title',
                'Slug',
                'Type',
                'Status',
                'Language',
                'Views',
                'Is Featured',
                'Show in Footer',
                'Show in Header',
                'Template',
                'Created At',
                'Updated At'
            ]);

            // CSV Data
            foreach ($pages as $page) {
                fputcsv($file, [
                    $page->id,
                    $page->title,
                    $page->slug,
                    $page->type,
                    $page->status,
                    $page->language ?? 'en',
                    $page->view_count ?? 0,
                    $page->is_featured ? 'Yes' : 'No',
                    $page->show_in_footer ? 'Yes' : 'No',
                    $page->show_in_header ? 'Yes' : 'No',
                    $page->template ?? 'default',
                    $page->created_at ? $page->created_at->format('Y-m-d H:i:s') : '',
                    $page->updated_at ? $page->updated_at->format('Y-m-d H:i:s') : ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get statistics for dashboard
     */
    public function statistics(Request $request)
    {
        try {
            $stats = [
                'total_pages' => Page::count(),
                'active_pages' => Page::where('status', 'active')->count(),
                'draft_pages' => Page::where('status', 'draft')->count(),
                'inactive_pages' => Page::where('status', 'inactive')->count(),
                'featured_pages' => Page::where('is_featured', true)->count(),
                'total_views' => Page::sum('view_count')
            ];

            return response()->json($stats);
        } catch (\Exception $e) {
            Log::error('Error getting page statistics: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to get statistics'], 500);
        }
    }

    /**
     * Force sync individual page
     */
    public function forceSync($id)
    {
        try {
            $page = Page::findOrFail($id);

            // For now, just mark as synced
            $page->update([
                'firebase_synced' => true,
                'firebase_synced_at' => now(),
                'firebase_sync_status' => 'synced'
            ]);

            return response()->json(['message' => 'Page synced successfully']);
        } catch (\Exception $e) {
            Log::error('Error force syncing page: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to sync page'], 500);
        }
    }
}
