<?php
// app/Modules/Foq/Controllers/Admin/AdminFoqController.php

namespace App\Modules\Foq\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Modules\Foq\Services\FoqService;
use App\Modules\Foq\Models\Foq;

class AdminFoqController extends Controller
{
    protected $foqService;

    public function __construct(FoqService $foqService)
    {
        $this->foqService = $foqService;
    }

    // ============ DASHBOARD AND LISTING ============

    /**
     * Display FOQ management dashboard
     */
    public function index(Request $request)
    {
        try {
            $filters = [
                'search' => $request->get('search'),
                'status' => $request->get('status'),
                'category' => $request->get('category'),
                'type' => $request->get('type'),
                'priority' => $request->get('priority'),
                'is_featured' => $request->get('is_featured'),
                'limit' => min($request->get('limit', 25), 50)
            ];

            // Get FOQs
            $foqs = $this->foqService->getAllFoqs($filters);
            
            // Get total count
            $totalFoqs = $this->foqService->getTotalFoqsCount();
            
            // Get statistics
            $statistics = $this->foqService->getFoqStatistics();
            
            Log::info('Admin FOQ dashboard accessed', [
                'admin' => session('firebase_user.email'),
                'filters' => $filters,
                'result_count' => $foqs->count(),
                'total_foqs' => $totalFoqs
            ]);
            
            return view('foq::admin.foqs.index', compact(
                'foqs', 
                'totalFoqs', 
                'statistics'
            ) + $filters);
            
        } catch (\Exception $e) {
            Log::error('Error loading admin FOQ dashboard: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Error loading FOQ dashboard: ' . $e->getMessage());
        }
    }

    /**
     * Show detailed FOQ information
     */
    public function show(int $id)
    {
        try {
            $foq = $this->foqService->getFoqById($id);
            
            if (!$foq) {
                return redirect()->route('foqs.index')
                    ->with('error', 'FOQ not found.');
            }

            Log::info('Admin viewed FOQ details', [
                'admin' => session('firebase_user.email'),
                'foq_id' => $id
            ]);
            
            return view('foq::admin.foqs.show', compact('foq'));
            
        } catch (\Exception $e) {
            Log::error('Error loading FOQ details: ' . $e->getMessage());
            return redirect()->route('foqs.index')
                ->with('error', 'Error loading FOQ details.');
        }
    }

    // ============ CRUD OPERATIONS ============

    /**
     * Show form for creating new FOQ
     */
    public function create()
    {
        return view('foq::admin.foqs.create', [
            'statuses' => Foq::getStatuses(),
            'categories' => Foq::getCategories(),
            'types' => Foq::getTypes(),
            'priorities' => Foq::getPriorities(),
            'userTypes' => Foq::getUserTypes(),
            'platforms' => Foq::getPlatforms()
        ]);
    }

    /**
     * Store newly created FOQ with auto-sync option
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'category' => 'required|in:' . implode(',', array_keys(Foq::getCategories())),
            'priority' => 'required|in:' . implode(',', array_keys(Foq::getPriorities())),
            'status' => 'required|in:' . implode(',', array_keys(Foq::getStatuses())),
            'type' => 'required|in:' . implode(',', array_keys(Foq::getTypes())),
            'language' => 'required|string|max:5',
            'display_order' => 'nullable|integer|min:0',
            'is_featured' => 'boolean',
            'requires_auth' => 'boolean',
            'icon' => 'nullable|string|max:100',
            'external_link' => 'nullable|url',
            'meta_description' => 'nullable|string|max:300',
            'slug' => 'nullable|string|max:255|unique:foqs,slug',
            'tags' => 'nullable|array',
            'applicable_user_types' => 'nullable|array',
            'applicable_platforms' => 'nullable|array',
            'published_at' => 'nullable|date',
            'sync_immediately' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $data = $request->all();
            $data['created_by'] = session('firebase_user.uid');
            $data['display_order'] = $data['display_order'] ?? 0;

            // Check if immediate sync is requested
            $syncImmediately = $request->boolean('sync_immediately', false);
            
            $foq = $this->foqService->createFoq($data, $syncImmediately);
            
            if (!$foq) {
                return redirect()->back()
                    ->with('error', 'Failed to create FOQ.')
                    ->withInput();
            }

            $message = 'FOQ created successfully!';
            if ($syncImmediately) {
                $message .= ' Firebase sync completed.';
            } else {
                $message .= ' Firebase sync queued.';
            }

            Log::info('Admin created FOQ', [
                'admin' => session('firebase_user.email'),
                'foq_id' => $foq->id,
                'question' => $foq->question,
                'sync_method' => $syncImmediately ? 'immediate' : 'queued'
            ]);

            return redirect()->route('foqs.show', $foq->id)
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Error creating FOQ: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->with('error', 'Error creating FOQ: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show form for editing FOQ
     */
    public function edit(int $id)
    {
        try {
            $foq = $this->foqService->getFoqById($id);
            
            if (!$foq) {
                return redirect()->route('foqs.index')
                    ->with('error', 'FOQ not found.');
            }

            return view('foq::admin.foqs.edit', [
                'foq' => $foq,
                'statuses' => Foq::getStatuses(),
                'categories' => Foq::getCategories(),
                'types' => Foq::getTypes(),
                'priorities' => Foq::getPriorities(),
                'userTypes' => Foq::getUserTypes(),
                'platforms' => Foq::getPlatforms()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error loading FOQ for edit: ' . $e->getMessage());
            return redirect()->route('foqs.index')
                ->with('error', 'Error loading FOQ for editing.');
        }
    }

    /**
     * Update FOQ information with sync option
     */
    public function update(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'category' => 'required|in:' . implode(',', array_keys(Foq::getCategories())),
            'priority' => 'required|in:' . implode(',', array_keys(Foq::getPriorities())),
            'status' => 'required|in:' . implode(',', array_keys(Foq::getStatuses())),
            'type' => 'required|in:' . implode(',', array_keys(Foq::getTypes())),
            'language' => 'required|string|max:5',
            'display_order' => 'nullable|integer|min:0',
            'is_featured' => 'boolean',
            'requires_auth' => 'boolean',
            'icon' => 'nullable|string|max:100',
            'external_link' => 'nullable|url',
            'meta_description' => 'nullable|string|max:300',
            'slug' => 'nullable|string|max:255|unique:foqs,slug,' . $id,
            'tags' => 'nullable|array',
            'applicable_user_types' => 'nullable|array',
            'applicable_platforms' => 'nullable|array',
            'published_at' => 'nullable|date',
            'sync_immediately' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $data = $request->all();
            $data['updated_by'] = session('firebase_user.uid');

            $syncImmediately = $request->boolean('sync_immediately', false);
            $result = $this->foqService->updateFoq($id, $data, $syncImmediately);

            if ($result) {
                Log::info('Admin updated FOQ', [
                    'admin' => session('firebase_user.email'),
                    'foq_id' => $id,
                    'sync_method' => $syncImmediately ? 'immediate' : 'queued'
                ]);
                
                $message = 'FOQ updated successfully!';
                if ($syncImmediately) {
                    $message .= ' Firebase sync completed.';
                } else {
                    $message .= ' Firebase sync queued.';
                }
                
                return redirect()->route('foqs.show', $id)
                    ->with('success', $message);
            } else {
                return redirect()->back()
                    ->with('error', 'Failed to update FOQ.')
                    ->withInput();
            }

        } catch (\Exception $e) {
            Log::error('Error updating FOQ: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error updating FOQ: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Delete FOQ
     */
    public function destroy(int $id)
    {
        try {
            $foq = $this->foqService->getFoqById($id);
            
            if (!$foq) {
                return response()->json([
                    'success' => false,
                    'message' => 'FOQ not found.'
                ], 404);
            }

            $result = $this->foqService->deleteFoq($id);

            if ($result) {
                Log::info('Admin deleted FOQ', [
                    'admin' => session('firebase_user.email'),
                    'foq_id' => $id,
                    'question' => $foq->question
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'FOQ deleted successfully!'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete FOQ.'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error deleting FOQ: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting FOQ: ' . $e->getMessage()
            ], 500);
        }
    }

    // ============ AJAX ENDPOINTS ============

    /**
     * Update FOQ status (AJAX)
     */
    public function updateStatus(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:' . implode(',', array_keys(Foq::getStatuses()))
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Invalid status'
            ], 400);
        }

        try {
            $result = $this->foqService->updateFoqStatus(
                $id, 
                $request->status,
                session('firebase_user.uid')
            );

            if ($result) {
                Log::info('Admin updated FOQ status', [
                    'admin' => session('firebase_user.email'),
                    'foq_id' => $id,
                    'status' => $request->status
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'FOQ status updated successfully!'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update FOQ status'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error updating FOQ status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating status: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Enhanced Firebase sync with health check
     */
    public function syncFirebase(Request $request)
    {
        try {
            $action = $request->get('action', 'auto'); // auto, force, health
            
            switch ($action) {
                case 'health':
                    $result = $this->foqService->checkSyncHealth();
                    return response()->json([
                        'success' => true,
                        'data' => $result,
                        'message' => 'Sync health check completed'
                    ]);
                    
                case 'force':
                    $result = $this->foqService->forceSyncAll();
                    break;
                    
                default:
                    $result = $this->foqService->runAutoSync();
                    break;
            }

            if ($result['success']) {
                Log::info('Admin triggered Firebase sync', [
                    'admin' => session('firebase_user.email'),
                    'action' => $action,
                    'processed' => $result['processed'] ?? 0
                ]);

                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'processed' => $result['processed'] ?? 0,
                    'failed' => $result['failed'] ?? 0
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error in Firebase sync: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error starting sync: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get sync status for dashboard
     */
    public function getSyncStatus()
    {
        try {
            $stats = $this->foqService->checkSyncHealth();
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error getting sync status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get sync status'
            ]);
        }
    }

    // ============ BULK OPERATIONS ============

    /**
     * Bulk operations on FOQs
     */
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:activate,deactivate,draft,feature,unfeature,delete',
            'foq_ids' => 'required|array|min:1',
            'foq_ids.*' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Invalid input'
            ], 400);
        }

        try {
            $result = $this->foqService->performBulkAction(
                $request->action, 
                $request->foq_ids
            );

            if ($result['success']) {
                Log::info('Admin performed bulk action on FOQs', [
                    'admin' => session('firebase_user.email'),
                    'action' => $request->action,
                    'count' => count($request->foq_ids)
                ]);

                return response()->json([
                    'success' => true,
                    'message' => $this->getBulkActionMessage(
                        $request->action, 
                        $result['processed_count'], 
                        $result['failed_count']
                    ),
                    'processed_count' => $result['processed_count'],
                    'failed_count' => $result['failed_count']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Bulk action error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error performing bulk action: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Bulk create FOQs
     */
    public function bulkCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'foqs' => 'required|array|min:1|max:50',
            'foqs.*.question' => 'required|string|max:500',
            'foqs.*.answer' => 'required|string',
            'foqs.*.category' => 'required|in:' . implode(',', array_keys(Foq::getCategories())),
            'foqs.*.priority' => 'nullable|in:' . implode(',', array_keys(Foq::getPriorities())),
            'foqs.*.type' => 'nullable|in:' . implode(',', array_keys(Foq::getTypes())),
            'foqs.*.status' => 'nullable|in:' . implode(',', array_keys(Foq::getStatuses()))
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $foqsData = [];
            $createdBy = session('firebase_user.uid');

            foreach ($request->foqs as $foqData) {
                $foqsData[] = array_merge($foqData, [
                    'created_by' => $createdBy,
                    'priority' => $foqData['priority'] ?? 'normal',
                    'type' => $foqData['type'] ?? 'faq',
                    'status' => $foqData['status'] ?? 'active',
                    'language' => 'en',
                    'display_order' => 0
                ]);
            }

            $result = $this->foqService->bulkCreateFoqs($foqsData);

            if ($result['success']) {
                Log::info('Admin bulk created FOQs', [
                    'admin' => session('firebase_user.email'),
                    'count' => count($request->foqs),
                    'created' => $result['created']
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Successfully created {$result['created']} FOQs",
                    'created_count' => $result['created'],
                    'failed_count' => $result['failed']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error bulk creating FOQs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating FOQs: ' . $e->getMessage()
            ]);
        }
    }

    // ============ IMPORT/EXPORT ============

    /**
     * Export FOQs data
     */
    public function export(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'format' => 'required|in:csv,excel',
            'status' => 'nullable|in:' . implode(',', array_keys(Foq::getStatuses())),
            'category' => 'nullable|in:' . implode(',', array_keys(Foq::getCategories())),
            'type' => 'nullable|in:' . implode(',', array_keys(Foq::getTypes())),
            'created_from' => 'nullable|date',
            'created_to' => 'nullable|date|after_or_equal:created_from'
        ]);

        if ($validator->fails()) {
            return redirect()->route('foqs.index')
                ->with('error', 'Invalid export parameters.');
        }

        try {
            $filters = [
                'status' => $request->status,
                'category' => $request->category,
                'type' => $request->type,
                'created_from' => $request->created_from,
                'created_to' => $request->created_to
            ];

            $foqs = $this->foqService->exportFoqs($filters);
            
            $filename = 'foqs_export_' . now()->format('Y_m_d_H_i_s') . '.csv';
            
            Log::info('Admin exported FOQs', [
                'admin' => session('firebase_user.email'),
                'count' => count($foqs),
                'filters' => $filters
            ]);

            return $this->generateCsvExport($foqs, $filename);

        } catch (\Exception $e) {
            Log::error('Error exporting FOQs: ' . $e->getMessage());
            return redirect()->route('foqs.index')
                ->with('error', 'Error exporting FOQs: ' . $e->getMessage());
        }
    }

    // ============ STATISTICS ============

    /**
     * Enhanced statistics with sync information
     */
    public function statistics()
    {
        try {
            $statistics = $this->foqService->getFoqStatistics();
            $syncHealth = $this->foqService->checkSyncHealth();
            
            return view('foq::admin.foqs.statistics', compact('statistics', 'syncHealth'));

        } catch (\Exception $e) {
            Log::error('Error loading FOQ statistics: ' . $e->getMessage());
            return redirect()->route('foqs.index')
                ->with('error', 'Error loading statistics.');
        }
    }

    /**
     * Activate FOQ
     */
    public function activate(int $id)
    {
        try {
            $result = $this->foqService->updateFoqStatus($id, 'active', session('firebase_user.uid'));
            
            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'FOQ activated successfully!'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to activate FOQ.'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error activating FOQ: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error activating FOQ.'
            ], 500);
        }
    }

    /**
     * Deactivate FOQ
     */
    public function deactivate(int $id)
    {
        try {
            $result = $this->foqService->updateFoqStatus($id, 'inactive', session('firebase_user.uid'));
            
            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'FOQ deactivated successfully!'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to deactivate FOQ.'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error deactivating FOQ: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deactivating FOQ.'
            ], 500);
        }
    }

    /**
     * Force sync single FOQ to Firebase
     */
    public function forceSync(int $id)
    {
        try {
            $foq = $this->foqService->getFoqById($id);
            
            if (!$foq) {
                return response()->json(['success' => false, 'message' => 'FOQ not found'], 404);
            }

            // Force immediate sync
            $result = $this->foqService->syncFoqImmediately($foq, 'update');
            
            if ($result) {
                return response()->json(['success' => true, 'message' => 'FOQ synced successfully!']);
            } else {
                return response()->json(['success' => false, 'message' => 'Sync failed']);
            }
        } catch (\Exception $e) {
            Log::error('Error force syncing FOQ: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Sync failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Duplicate FOQ
     */
    public function duplicate(int $id)
    {
        try {
            $foq = $this->foqService->getFoqById($id);
            
            if (!$foq) {
                return redirect()->route('foqs.index')->with('error', 'FOQ not found.');
            }

            $data = $foq->toArray();
            unset($data['id'], $data['created_at'], $data['updated_at'], $data['slug']);
            $data['question'] = 'Copy of ' . $data['question'];
            $data['status'] = 'draft';
            $data['created_by'] = session('firebase_user.uid');

            $newFoq = $this->foqService->createFoq($data);

            if ($newFoq) {
                return redirect()->route('foqs.edit', $newFoq->id)
                    ->with('success', 'FOQ duplicated successfully!');
            } else {
                return redirect()->back()->with('error', 'Failed to duplicate FOQ.');
            }

        } catch (\Exception $e) {
            Log::error('Error duplicating FOQ: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error duplicating FOQ.');
        }
    }

    /**
     * Record feedback for FOQ
     */
    public function recordFeedback(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'helpful' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Invalid input'], 400);
        }

        try {
            $result = $this->foqService->recordFeedback($id, $request->boolean('helpful'));
            
            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Thank you for your feedback!'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to record feedback'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error recording feedback: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error recording feedback'
            ]);
        }
    }

    // ============ HELPER METHODS ============

    /**
     * Generate bulk action message
     */
    private function getBulkActionMessage(string $action, int $processed, int $failed): string
    {
        $messages = [
            'activate' => "Activated {$processed} FOQs",
            'deactivate' => "Deactivated {$processed} FOQs",
            'draft' => "Moved {$processed} FOQs to draft",
            'feature' => "Featured {$processed} FOQs",
            'unfeature' => "Unfeatured {$processed} FOQs",
            'delete' => "Deleted {$processed} FOQs"
        ];

        $message = $messages[$action] ?? "Processed {$processed} FOQs";
        
        if ($failed > 0) {
            $message .= " ({$failed} failed)";
        }

        return $message;
    }

    /**
     * Generate CSV export
     */
    private function generateCsvExport(array $foqs, string $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($foqs) {
            $file = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($file, [
                'ID', 'Question', 'Answer', 'Category', 'Priority', 'Status', 'Type',
                'Language', 'View Count', 'Helpful Count', 'Not Helpful Count',
                'Display Order', 'Is Featured', 'Requires Auth', 'Helpfulness Ratio',
                'Firebase Synced', 'Published At', 'Created At'
            ]);

            // CSV Data
            foreach ($foqs as $foq) {
                fputcsv($file, [
                    $foq['id'] ?? '',
                    $foq['question'] ?? '',
                    strip_tags($foq['answer'] ?? ''),
                    $foq['category'] ?? '',
                    $foq['priority'] ?? '',
                    $foq['status'] ?? '',
                    $foq['type'] ?? '',
                    $foq['language'] ?? '',
                    $foq['view_count'] ?? '',
                    $foq['helpful_count'] ?? '',
                    $foq['not_helpful_count'] ?? '',
                    $foq['display_order'] ?? '',
                    $foq['is_featured'] ?? '',
                    $foq['requires_auth'] ?? '',
                    $foq['helpfulness_ratio'] ?? '',
                    $foq['firebase_synced'] ?? '',
                    $foq['published_at'] ?? '',
                    $foq['created_at'] ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}