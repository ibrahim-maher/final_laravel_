<?php
// app/Modules/Complaint/Controllers/Admin/AdminComplaintController.php

namespace App\Modules\Complaint\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Modules\Complaint\Services\ComplaintService;
use App\Modules\Complaint\Models\Complaint;

class AdminComplaintController extends Controller
{
    protected $complaintService;

    public function __construct(ComplaintService $complaintService)
    {
        $this->complaintService = $complaintService;
    }

    /**
     * Display complaint management dashboard
     */
    public function index(Request $request)
    {
        try {
            $filters = [
                'search' => $request->get('search'),
                'status' => $request->get('status'),
                'priority' => $request->get('priority'),
                'order_type' => $request->get('order_type'),
                'category' => $request->get('category'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
                'overdue' => $request->get('overdue'),
                'limit' => min($request->get('limit', 25), 50),
                'page' => $request->get('page', 1),
                'sort_by' => $request->get('sort_by', 'created_at'),
                'sort_direction' => $request->get('sort_direction', 'desc')
            ];

            // Get complaints from Firestore
            $complaints = $this->complaintService->getAllComplaints($filters);
            
            // Get total count
            $totalComplaints = $this->complaintService->getTotalComplaintsCount();
            
            // Get statistics
            $statistics = $this->complaintService->getComplaintStatistics();
            
            Log::info('Admin complaint dashboard accessed', [
                'admin' => session('firebase_user.email'),
                'filters' => $filters,
                'result_count' => $complaints->count(),
                'total_complaints' => $totalComplaints
            ]);
            
            return view('complaint::admin.complaints.index', compact(
                'complaints', 
                'totalComplaints', 
                'statistics'
            ) + $filters);
            
        } catch (\Exception $e) {
            Log::error('Error loading admin complaint dashboard: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Error loading complaint dashboard: ' . $e->getMessage());
        }
    }

    /**
     * Show detailed complaint information
     */
    public function show(string $id)
    {
        try {
            $complaint = $this->complaintService->getComplaintById($id);
            
            if (!$complaint) {
                return redirect()->route('complaints.index')
                    ->with('error', 'Complaint not found.');
            }
            
            Log::info('Admin viewed complaint details', [
                'admin' => session('firebase_user.email'),
                'complaint_id' => $id
            ]);
            
            return view('complaint::admin.complaints.show', compact('complaint'));
            
        } catch (\Exception $e) {
            Log::error('Error loading complaint details: ' . $e->getMessage());
            return redirect()->route('complaints.index')
                ->with('error', 'Error loading complaint details.');
        }
    }

    /**
     * Update complaint status (AJAX)
     */
    public function updateStatus(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:' . implode(',', array_keys(Complaint::getStatuses()))
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Invalid status'
            ], 400);
        }

        try {
            $result = $this->complaintService->updateComplaintStatus(
                $id, 
                $request->status,
                session('firebase_user.email')
            );

            if ($result) {
                Log::info('Admin updated complaint status', [
                    'admin' => session('firebase_user.email'),
                    'complaint_id' => $id,
                    'status' => $request->status
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Complaint status updated successfully!'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update complaint status'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error updating complaint status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating status: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Add admin notes to complaint (AJAX)
     */
    public function addNotes(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'required|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Notes are required and must be less than 1000 characters'
            ], 400);
        }

        try {
            $result = $this->complaintService->addAdminNotes(
                $id, 
                $request->notes,
                session('firebase_user.email')
            );

            if ($result) {
                Log::info('Admin added notes to complaint', [
                    'admin' => session('firebase_user.email'),
                    'complaint_id' => $id
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Notes added successfully!'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to add notes'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error adding admin notes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error adding notes: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Bulk operations on complaints
     */
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:resolve,close,in_progress,pending',
            'complaint_ids' => 'required|array|min:1',
            'complaint_ids.*' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Invalid input'
            ], 400);
        }

        try {
            $result = $this->complaintService->bulkUpdateStatus(
                $request->complaint_ids, 
                $request->action,
                session('firebase_user.email')
            );

            if ($result['success']) {
                Log::info('Admin performed bulk action on complaints', [
                    'admin' => session('firebase_user.email'),
                    'action' => $request->action,
                    'count' => count($request->complaint_ids)
                ]);

                return response()->json([
                    'success' => true,
                    'message' => $this->getBulkActionMessage(
                        $request->action, 
                        $result['processed'], 
                        $result['failed']
                    ),
                    'processed_count' => $result['processed'],
                    'failed_count' => $result['failed']
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
     * Export complaints data
     */
    public function export(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'format' => 'required|in:csv,excel',
            'status' => 'nullable|in:' . implode(',', array_keys(Complaint::getStatuses())),
            'priority' => 'nullable|in:' . implode(',', array_keys(Complaint::getPriorities())),
            'order_type' => 'nullable|in:' . implode(',', array_keys(Complaint::getOrderTypes())),
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from'
        ]);

        if ($validator->fails()) {
            return redirect()->route('complaints.index')
                ->with('error', 'Invalid export parameters.');
        }

        try {
            $filters = [
                'status' => $request->status,
                'priority' => $request->priority,
                'order_type' => $request->order_type,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to
            ];

            $complaints = $this->complaintService->exportComplaints($filters);
            
            $filename = 'complaints_export_' . now()->format('Y_m_d_H_i_s') . '.csv';
            
            Log::info('Admin exported complaints', [
                'admin' => session('firebase_user.email'),
                'count' => count($complaints),
                'filters' => $filters
            ]);

            return $this->generateCsvExport($complaints, $filename);

        } catch (\Exception $e) {
            Log::error('Error exporting complaints: ' . $e->getMessage());
            return redirect()->route('complaints.index')
                ->with('error', 'Error exporting complaints: ' . $e->getMessage());
        }
    }

    /**
     * Show statistics page
     */
    public function statistics()
    {
        try {
            $statistics = $this->complaintService->getComplaintStatistics();
            $healthCheck = $this->complaintService->healthCheck();
            
            return view('complaint::admin.complaints.statistics', compact('statistics', 'healthCheck'));

        } catch (\Exception $e) {
            Log::error('Error loading complaint statistics: ' . $e->getMessage());
            return redirect()->route('complaints.index')
                ->with('error', 'Error loading statistics.');
        }
    }

    /**
     * Health check endpoint
     */
    public function healthCheck()
    {
        try {
            $result = $this->complaintService->healthCheck();
            
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in health check: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Health check failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Refresh complaints from Firestore
     */
    public function refresh(Request $request)
    {
        try {
            // Clear cache to force fresh data
            $this->complaintService->clearCache();
            
            Log::info('Admin refreshed complaints data', [
                'admin' => session('firebase_user.email')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Complaints data refreshed successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Error refreshing complaints: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error refreshing data: ' . $e->getMessage()
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
            'resolve' => "Resolved {$processed} complaints",
            'close' => "Closed {$processed} complaints",
            'in_progress' => "Set {$processed} complaints to In Progress",
            'pending' => "Set {$processed} complaints to Pending"
        ];

        $message = $messages[$action] ?? "Processed {$processed} complaints";
        
        if ($failed > 0) {
            $message .= " ({$failed} failed)";
        }

        return $message;
    }

    /**
     * Generate CSV export
     */
    private function generateCsvExport(array $complaints, string $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($complaints) {
            $file = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($file, [
                'ID', 'Title', 'Description', 'Order Type', 'Status', 'Priority', 
                'Category', 'Complaint By', 'Driver Name', 'User Name', 'Contact Info',
                'Order ID', 'Created At', 'Resolved At', 'Resolved By', 'Admin Notes'
            ]);

            // CSV Data
            foreach ($complaints as $complaint) {
                fputcsv($file, [
                    $complaint['id'] ?? '',
                    $complaint['title'] ?? '',
                    $complaint['description'] ?? '',
                    $complaint['order_type'] ?? '',
                    $complaint['status'] ?? '',
                    $complaint['priority'] ?? '',
                    $complaint['category'] ?? '',
                    $complaint['complaint_by'] ?? '',
                    $complaint['driver_name'] ?? '',
                    $complaint['user_name'] ?? '',
                    $complaint['contact_info'] ?? '',
                    $complaint['order_id'] ?? '',
                    $complaint['created_at'] ?? '',
                    $complaint['resolved_at'] ?? '',
                    $complaint['resolved_by'] ?? '',
                    $complaint['admin_notes'] ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}