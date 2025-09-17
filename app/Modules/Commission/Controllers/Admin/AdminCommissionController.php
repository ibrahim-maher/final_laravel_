<?php
// app/Modules/Commission/Controllers/Admin/AdminCommissionController.php

namespace App\Modules\Commission\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Modules\Commission\Services\CommissionService;
use App\Modules\Commission\Models\Commission;

class AdminCommissionController extends Controller
{
    protected $commissionService;

    public function __construct(CommissionService $commissionService)
    {
        $this->commissionService = $commissionService;
    }

    // ============ DASHBOARD AND LISTING ============

    /**
     * Display commission management dashboard
     */
    public function index(Request $request)
    {
        try {
            $filters = [
                'search' => $request->get('search'),
                'commission_type' => $request->get('commission_type'),
                'recipient_type' => $request->get('recipient_type'),
                'calculation_method' => $request->get('calculation_method'),
                'payment_frequency' => $request->get('payment_frequency'),
                'is_active' => $request->get('is_active'),
                'limit' => min($request->get('limit', 25), 50)
            ];

            // Get commissions
            $commissions = $this->commissionService->getAllCommissions($filters);
            
            // Get total count
            $totalCommissions = $this->commissionService->getTotalCommissionsCount();
            
            // Get statistics
            $statistics = $this->commissionService->getCommissionStatistics();
            
            Log::info('Admin commission dashboard accessed', [
                'admin' => session('firebase_user.email'),
                'filters' => $filters,
                'result_count' => $commissions->count(),
                'total_commissions' => $totalCommissions
            ]);
            
            return view('commission::admin.commissions.index', compact(
                'commissions', 
                'totalCommissions', 
                'statistics'
            ) + $filters);
            
        } catch (\Exception $e) {
            Log::error('Error loading admin commission dashboard: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Error loading commission dashboard: ' . $e->getMessage());
        }
    }

    /**
     * Show detailed commission information
     */
    public function show(int $id)
    {
        try {
            $commission = $this->commissionService->getCommissionById($id);
            
            if (!$commission) {
                return redirect()->route('commissions.index')
                    ->with('error', 'Commission not found.');
            }

            // Get payout history
            $payoutHistory = $this->commissionService->getCommissionPayoutHistory($id, ['limit' => 50]);
            
            Log::info('Admin viewed commission details', [
                'admin' => session('firebase_user.email'),
                'commission_id' => $id
            ]);
            
            return view('commission::admin.commissions.show', compact('commission', 'payoutHistory'));
            
        } catch (\Exception $e) {
            Log::error('Error loading commission details: ' . $e->getMessage());
            return redirect()->route('commissions.index')
                ->with('error', 'Error loading commission details.');
        }
    }

    // ============ CRUD OPERATIONS ============

    /**
     * Show form for creating new commission
     */
    public function create()
    {
        return view('commission::admin.commissions.create', [
            'commissionTypes' => Commission::getCommissionTypes(),
            'recipientTypes' => Commission::getRecipientTypes(),
            'calculationMethods' => Commission::getCalculationMethods(),
            'applicableToOptions' => Commission::getApplicableToOptions(),
            'paymentFrequencies' => Commission::getPaymentFrequencies()
        ]);
    }

    /**
     * Store newly created commission with auto-sync option
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'commission_type' => 'required|in:' . implode(',', array_keys(Commission::getCommissionTypes())),
            'recipient_type' => 'required|in:' . implode(',', array_keys(Commission::getRecipientTypes())),
            'calculation_method' => 'required|in:' . implode(',', array_keys(Commission::getCalculationMethods())),
            'rate' => 'required_if:commission_type,percentage,hybrid|nullable|numeric|min:0|max:100',
            'fixed_amount' => 'required_if:commission_type,fixed,hybrid|nullable|numeric|min:0|max:999999',
            'minimum_amount' => 'nullable|numeric|min:0',
            'maximum_amount' => 'nullable|numeric|min:0',
            'minimum_commission' => 'nullable|numeric|min:0',
            'maximum_commission' => 'nullable|numeric|min:0',
            'applicable_to' => 'required|in:' . implode(',', array_keys(Commission::getApplicableToOptions())),
            'applicable_zones' => 'nullable|array',
            'excluded_zones' => 'nullable|array',
            'applicable_vehicle_types' => 'nullable|array',
            'excluded_vehicle_types' => 'nullable|array',
            'applicable_services' => 'nullable|array',
            'excluded_services' => 'nullable|array',
            'tier_based' => 'boolean',
            'tier_rules' => 'nullable|array',
            'payment_frequency' => 'required|in:' . implode(',', array_keys(Commission::getPaymentFrequencies())),
            'auto_payout' => 'boolean',
            'minimum_payout_amount' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'priority_order' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date|after_or_equal:today',
            'expires_at' => 'nullable|date|after:starts_at',
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

            // Check if immediate sync is requested
            $syncImmediately = $request->boolean('sync_immediately', false);
            
            $commission = $this->commissionService->createCommission($data, $syncImmediately);
            
            if (!$commission) {
                return redirect()->back()
                    ->with('error', 'Failed to create commission.')
                    ->withInput();
            }

            $message = 'Commission created successfully!';
            if ($syncImmediately) {
                $message .= ' Firebase sync completed.';
            } else {
                $message .= ' Firebase sync queued.';
            }

            Log::info('Admin created commission', [
                'admin' => session('firebase_user.email'),
                'commission_id' => $commission->id,
                'sync_method' => $syncImmediately ? 'immediate' : 'queued'
            ]);

            return redirect()->route('commissions.show', $commission->id)
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Error creating commission: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->with('error', 'Error creating commission: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show form for editing commission
     */
    public function edit(int $id)
    {
        try {
            $commission = $this->commissionService->getCommissionById($id);
            
            if (!$commission) {
                return redirect()->route('commissions.index')
                    ->with('error', 'Commission not found.');
            }

            return view('commission::admin.commissions.edit', [
                'commission' => $commission,
                'commissionTypes' => Commission::getCommissionTypes(),
                'recipientTypes' => Commission::getRecipientTypes(),
                'calculationMethods' => Commission::getCalculationMethods(),
                'applicableToOptions' => Commission::getApplicableToOptions(),
                'paymentFrequencies' => Commission::getPaymentFrequencies()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error loading commission for edit: ' . $e->getMessage());
            return redirect()->route('commissions.index')
                ->with('error', 'Error loading commission for editing.');
        }
    }

    /**
     * Update commission information with sync option
     */
    public function update(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'commission_type' => 'required|in:' . implode(',', array_keys(Commission::getCommissionTypes())),
            'recipient_type' => 'required|in:' . implode(',', array_keys(Commission::getRecipientTypes())),
            'calculation_method' => 'required|in:' . implode(',', array_keys(Commission::getCalculationMethods())),
            'rate' => 'required_if:commission_type,percentage,hybrid|nullable|numeric|min:0|max:100',
            'fixed_amount' => 'required_if:commission_type,fixed,hybrid|nullable|numeric|min:0|max:999999',
            'minimum_amount' => 'nullable|numeric|min:0',
            'maximum_amount' => 'nullable|numeric|min:0',
            'minimum_commission' => 'nullable|numeric|min:0',
            'maximum_commission' => 'nullable|numeric|min:0',
            'applicable_to' => 'required|in:' . implode(',', array_keys(Commission::getApplicableToOptions())),
            'tier_based' => 'boolean',
            'tier_rules' => 'nullable|array',
            'payment_frequency' => 'required|in:' . implode(',', array_keys(Commission::getPaymentFrequencies())),
            'auto_payout' => 'boolean',
            'minimum_payout_amount' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'priority_order' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
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
            $result = $this->commissionService->updateCommission($id, $data, $syncImmediately);

            if ($result) {
                Log::info('Admin updated commission', [
                    'admin' => session('firebase_user.email'),
                    'commission_id' => $id,
                    'sync_method' => $syncImmediately ? 'immediate' : 'queued'
                ]);
                
                $message = 'Commission updated successfully!';
                if ($syncImmediately) {
                    $message .= ' Firebase sync completed.';
                } else {
                    $message .= ' Firebase sync queued.';
                }
                
                return redirect()->route('commissions.show', $id)
                    ->with('success', $message);
            } else {
                return redirect()->back()
                    ->with('error', 'Failed to update commission.')
                    ->withInput();
            }

        } catch (\Exception $e) {
            Log::error('Error updating commission: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error updating commission: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Delete commission
     */
    public function destroy(int $id)
    {
        try {
            $commission = $this->commissionService->getCommissionById($id);
            
            if (!$commission) {
                return redirect()->route('commissions.index')
                    ->with('error', 'Commission not found.');
            }

            $result = $this->commissionService->deleteCommission($id);

            if ($result) {
                Log::info('Admin deleted commission', [
                    'admin' => session('firebase_user.email'),
                    'commission_id' => $id
                ]);
                
                return redirect()->route('commissions.index')
                    ->with('success', 'Commission deleted successfully!');
            } else {
                return redirect()->back()
                    ->with('error', 'Failed to delete commission.');
            }

        } catch (\Exception $e) {
            Log::error('Error deleting commission: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error deleting commission: ' . $e->getMessage());
        }
    }

    // ============ AJAX ENDPOINTS ============

    /**
     * Update commission status (AJAX)
     */
    public function updateStatus(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'is_active' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Invalid status'
            ], 400);
        }

        try {
            $result = $this->commissionService->updateCommissionStatus(
                $id, 
                $request->boolean('is_active'),
                session('firebase_user.uid')
            );

            if ($result) {
                Log::info('Admin updated commission status', [
                    'admin' => session('firebase_user.email'),
                    'commission_id' => $id,
                    'is_active' => $request->boolean('is_active')
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Commission status updated successfully!'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update commission status'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error updating commission status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating status: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate commission preview (AJAX)
     */
    public function calculateCommission(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'commission_id' => 'required|integer|exists:commissions,id',
            'context' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Invalid input'
            ], 400);
        }

        try {
            $result = $this->commissionService->calculateCommission(
                $request->commission_id,
                $request->amount,
                $request->context ?? []
            );

            return response()->json([
                'success' => true,
                'commission_amount' => $result['commission_amount'],
                'applicable' => $result['applicable'],
                'net_amount' => $result['net_amount'],
                'breakdown' => $result['breakdown'] ?? null
            ]);

        } catch (\Exception $e) {
            Log::error('Error calculating commission: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error calculating commission: ' . $e->getMessage()
            ]);
        }
    }

    // ============ BULK OPERATIONS ============

    /**
     * Bulk operations on commissions
     */
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:activate,deactivate,delete',
            'commission_ids' => 'required|array|min:1',
            'commission_ids.*' => 'required|integer|exists:commissions,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Invalid input'
            ], 400);
        }

        try {
            $result = $this->commissionService->performBulkAction(
                $request->action, 
                $request->commission_ids
            );

            if ($result['success']) {
                Log::info('Admin performed bulk action on commissions', [
                    'admin' => session('firebase_user.email'),
                    'action' => $request->action,
                    'count' => count($request->commission_ids)
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

    // ============ HELPER METHODS ============

    /**
     * Generate bulk action message
     */
    private function getBulkActionMessage(string $action, int $processed, int $failed): string
    {
        $messages = [
            'activate' => "Activated {$processed} commissions",
            'deactivate' => "Deactivated {$processed} commissions",
            'delete' => "Deleted {$processed} commissions"
        ];

        $message = $messages[$action] ?? "Processed {$processed} commissions";
        
        if ($failed > 0) {
            $message .= " ({$failed} failed)";
        }

        return $message;
    }
}