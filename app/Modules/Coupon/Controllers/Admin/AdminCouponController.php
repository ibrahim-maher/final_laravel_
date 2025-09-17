<?php
// app/Modules/Coupon/Controllers/Admin/AdminCouponController.php

namespace App\Modules\Coupon\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Modules\Coupon\Services\CouponService;
use App\Modules\Coupon\Models\Coupon;
use App\Modules\Coupon\Models\CouponUsage;

class AdminCouponController extends Controller
{
    protected $couponService;

    public function __construct(CouponService $couponService)
    {
        $this->couponService = $couponService;
    }

    // ============ DASHBOARD AND LISTING ============

    /**
     * Display coupon management dashboard
     */
    public function index(Request $request)
    {
        try {
            $filters = [
                'search' => $request->get('search'),
                'status' => $request->get('status'),
                'coupon_type' => $request->get('coupon_type'),
                'discount_type' => $request->get('discount_type'),
                'expiry_status' => $request->get('expiry_status'),
                'limit' => min($request->get('limit', 25), 50)
            ];

            // Get coupons
            $coupons = $this->couponService->getAllCoupons($filters);
            
            // Get total count
            $totalCoupons = $this->couponService->getTotalCouponsCount();
            
            // Get statistics
            $statistics = $this->couponService->getCouponStatistics();
            
            Log::info('Admin coupon dashboard accessed', [
                'admin' => session('firebase_user.email'),
                'filters' => $filters,
                'result_count' => $coupons->count(),
                'total_coupons' => $totalCoupons
            ]);
            
            return view('coupon::admin.coupons.index', compact(
                'coupons', 
                'totalCoupons', 
                'statistics'
            ) + $filters);
            
        } catch (\Exception $e) {
            Log::error('Error loading admin coupon dashboard: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Error loading coupon dashboard: ' . $e->getMessage());
        }
    }

    /**
     * Show detailed coupon information
     */
    public function show(string $code)
    {
        try {
            $coupon = $this->couponService->getCouponByCode($code);
            
            if (!$coupon) {
                return redirect()->route('coupons.index')
                    ->with('error', 'Coupon not found.');
            }

            // Get usage history
            $usageHistory = $this->couponService->getCouponUsageHistory($code, ['limit' => 50]);
            
            Log::info('Admin viewed coupon details', [
                'admin' => session('firebase_user.email'),
                'coupon_code' => $code
            ]);
            
            return view('coupon::admin.coupons.show', compact('coupon', 'usageHistory'));
            
        } catch (\Exception $e) {
            Log::error('Error loading coupon details: ' . $e->getMessage());
            return redirect()->route('coupons.index')
                ->with('error', 'Error loading coupon details.');
        }
    }

    // ============ CRUD OPERATIONS ============

    /**
     * Show form for creating new coupon
     */
    public function create()
    {
        return view('coupon::admin.coupons.create', [
            'statuses' => Coupon::getStatuses(),
            'couponTypes' => Coupon::getCouponTypes(),
            'discountTypes' => Coupon::getDiscountTypes(),
            'applicableToOptions' => Coupon::getApplicableToOptions()
        ]);
    }

    /**
     * Store newly created coupon with auto-sync option
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'nullable|string|max:50|unique:coupons,code',
            'description' => 'required|string|max:500',
            'coupon_type' => 'required|in:' . implode(',', array_keys(Coupon::getCouponTypes())),
            'discount_type' => 'required|in:' . implode(',', array_keys(Coupon::getDiscountTypes())),
            'discount_value' => 'required|numeric|min:0|max:' . ($request->discount_type === 'percentage' ? '100' : '999999'),
            'minimum_amount' => 'nullable|numeric|min:0',
            'maximum_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'user_usage_limit' => 'nullable|integer|min:1',
            'starts_at' => 'required|date|after_or_equal:today',
            'expires_at' => 'required|date|after:starts_at',
            'status' => 'required|in:' . implode(',', array_keys(Coupon::getStatuses())),
            'applicable_to' => 'required|in:' . implode(',', array_keys(Coupon::getApplicableToOptions())),
            'excluded_users' => 'nullable|array',
            'included_users' => 'nullable|array',
            'applicable_zones' => 'nullable|array',
            'excluded_zones' => 'nullable|array',
            'applicable_vehicle_types' => 'nullable|array',
            'excluded_vehicle_types' => 'nullable|array',
            'first_ride_only' => 'boolean',
            'returning_user_only' => 'boolean',
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
            
            // Generate code if not provided
            if (empty($data['code'])) {
                $data['code'] = $this->couponService->generateCouponCode();
            }

            // Check if immediate sync is requested
            $syncImmediately = $request->boolean('sync_immediately', false);
            
            $coupon = $this->couponService->createCoupon($data, $syncImmediately);
            
            if (!$coupon) {
                return redirect()->back()
                    ->with('error', 'Failed to create coupon.')
                    ->withInput();
            }

            $message = 'Coupon created successfully!';
            if ($syncImmediately) {
                $message .= ' Firebase sync completed.';
            } else {
                $message .= ' Firebase sync queued.';
            }

            Log::info('Admin created coupon', [
                'admin' => session('firebase_user.email'),
                'coupon_code' => $coupon->code,
                'sync_method' => $syncImmediately ? 'immediate' : 'queued'
            ]);

            return redirect()->route('coupons.show', $coupon->code)
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Error creating coupon: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->with('error', 'Error creating coupon: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show form for editing coupon
     */
    public function edit(string $code)
    {
        try {
            $coupon = $this->couponService->getCouponByCode($code);
            
            if (!$coupon) {
                return redirect()->route('coupons.index')
                    ->with('error', 'Coupon not found.');
            }

            return view('coupon::admin.coupons.edit', [
                'coupon' => $coupon,
                'statuses' => Coupon::getStatuses(),
                'couponTypes' => Coupon::getCouponTypes(),
                'discountTypes' => Coupon::getDiscountTypes(),
                'applicableToOptions' => Coupon::getApplicableToOptions()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error loading coupon for edit: ' . $e->getMessage());
            return redirect()->route('coupons.index')
                ->with('error', 'Error loading coupon for editing.');
        }
    }

    /**
     * Update coupon information with sync option
     */
    public function update(Request $request, string $code)
    {
        $validator = Validator::make($request->all(), [
            'description' => 'required|string|max:500',
            'coupon_type' => 'required|in:' . implode(',', array_keys(Coupon::getCouponTypes())),
            'discount_type' => 'required|in:' . implode(',', array_keys(Coupon::getDiscountTypes())),
            'discount_value' => 'required|numeric|min:0|max:' . ($request->discount_type === 'percentage' ? '100' : '999999'),
            'minimum_amount' => 'nullable|numeric|min:0',
            'maximum_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'user_usage_limit' => 'nullable|integer|min:1',
            'starts_at' => 'required|date',
            'expires_at' => 'required|date|after:starts_at',
            'status' => 'required|in:' . implode(',', array_keys(Coupon::getStatuses())),
            'applicable_to' => 'required|in:' . implode(',', array_keys(Coupon::getApplicableToOptions())),
            'excluded_users' => 'nullable|array',
            'included_users' => 'nullable|array',
            'applicable_zones' => 'nullable|array',
            'excluded_zones' => 'nullable|array',
            'applicable_vehicle_types' => 'nullable|array',
            'excluded_vehicle_types' => 'nullable|array',
            'first_ride_only' => 'boolean',
            'returning_user_only' => 'boolean',
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
            $result = $this->couponService->updateCoupon($code, $data, $syncImmediately);

            if ($result) {
                Log::info('Admin updated coupon', [
                    'admin' => session('firebase_user.email'),
                    'coupon_code' => $code,
                    'sync_method' => $syncImmediately ? 'immediate' : 'queued'
                ]);
                
                $message = 'Coupon updated successfully!';
                if ($syncImmediately) {
                    $message .= ' Firebase sync completed.';
                } else {
                    $message .= ' Firebase sync queued.';
                }
                
                return redirect()->route('coupons.show', $code)
                    ->with('success', $message);
            } else {
                return redirect()->back()
                    ->with('error', 'Failed to update coupon.')
                    ->withInput();
            }

        } catch (\Exception $e) {
            Log::error('Error updating coupon: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error updating coupon: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Delete coupon
     */
    public function destroy(string $code)
    {
        try {
            $coupon = $this->couponService->getCouponByCode($code);
            
            if (!$coupon) {
                return redirect()->route('coupons.index')
                    ->with('error', 'Coupon not found.');
            }

            $result = $this->couponService->deleteCoupon($code);

            if ($result) {
                Log::info('Admin deleted coupon', [
                    'admin' => session('firebase_user.email'),
                    'coupon_code' => $code
                ]);
                
                return redirect()->route('coupons.index')
                    ->with('success', 'Coupon deleted successfully!');
            } else {
                return redirect()->back()
                    ->with('error', 'Failed to delete coupon.');
            }

        } catch (\Exception $e) {
            Log::error('Error deleting coupon: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error deleting coupon: ' . $e->getMessage());
        }
    }

    // ============ AJAX ENDPOINTS ============

    /**
     * Update coupon status (AJAX)
     */
    public function updateStatus(Request $request, string $code)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:' . implode(',', array_keys(Coupon::getStatuses()))
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Invalid status'
            ], 400);
        }

        try {
            $result = $this->couponService->updateCouponStatus(
                $code, 
                $request->status,
                session('firebase_user.uid')
            );

            if ($result) {
                Log::info('Admin updated coupon status', [
                    'admin' => session('firebase_user.email'),
                    'coupon_code' => $code,
                    'status' => $request->status
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Coupon status updated successfully!'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update coupon status'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error updating coupon status: ' . $e->getMessage());
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
                    $result = $this->couponService->checkSyncHealth();
                    return response()->json([
                        'success' => true,
                        'data' => $result,
                        'message' => 'Sync health check completed'
                    ]);
                    
                case 'force':
                    $result = $this->couponService->forceSyncAll();
                    break;
                    
                default:
                    $result = $this->couponService->runAutoSync();
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
            $stats = $this->couponService->checkSyncHealth();
            
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

    /**
     * Validate coupon (AJAX)
     */
    public function validateCoupon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
            'user_id' => 'required|string',
            'amount' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Invalid input'
            ], 400);
        }

        try {
            $context = [];
            if ($request->has('amount')) {
                $context['amount'] = $request->amount;
            }

            $result = $this->couponService->validateCoupon(
                $request->code,
                $request->user_id,
                $context
            );

            return response()->json([
                'success' => true,
                'valid' => $result['valid'],
                'message' => $result['message'],
                'coupon' => $result['coupon'] ? [
                    'code' => $result['coupon']->code,
                    'description' => $result['coupon']->description,
                    'discount_type' => $result['coupon']->discount_type,
                    'discount_value' => $result['coupon']->discount_value,
                    'minimum_amount' => $result['coupon']->minimum_amount,
                    'maximum_discount' => $result['coupon']->maximum_discount
                ] : null
            ]);

        } catch (\Exception $e) {
            Log::error('Error validating coupon: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error validating coupon: ' . $e->getMessage()
            ]);
        }
    }

    // ============ BULK OPERATIONS ============

    /**
     * Bulk operations on coupons
     */
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:enable,disable,delete',
            'coupon_codes' => 'required|array|min:1',
            'coupon_codes.*' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Invalid input'
            ], 400);
        }

        try {
            $result = $this->couponService->performBulkAction(
                $request->action, 
                $request->coupon_codes
            );

            if ($result['success']) {
                Log::info('Admin performed bulk action on coupons', [
                    'admin' => session('firebase_user.email'),
                    'action' => $request->action,
                    'count' => count($request->coupon_codes)
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
     * Bulk create coupons
     */
    public function bulkCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'count' => 'required|integer|min:1|max:100',
            'code_prefix' => 'nullable|string|max:10',
            'description' => 'required|string|max:500',
            'coupon_type' => 'required|in:' . implode(',', array_keys(Coupon::getCouponTypes())),
            'discount_type' => 'required|in:' . implode(',', array_keys(Coupon::getDiscountTypes())),
            'discount_value' => 'required|numeric|min:0',
            'minimum_amount' => 'nullable|numeric|min:0',
            'maximum_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'user_usage_limit' => 'nullable|integer|min:1',
            'starts_at' => 'required|date|after_or_equal:today',
            'expires_at' => 'required|date|after:starts_at',
            'status' => 'required|in:' . implode(',', array_keys(Coupon::getStatuses()))
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $baseData = $request->except(['count', 'code_prefix']);
            $baseData['created_by'] = session('firebase_user.uid');

            $result = $this->couponService->bulkCreateCoupons(
                $baseData,
                $request->count,
                $request->code_prefix ?? ''
            );

            if ($result['success']) {
                Log::info('Admin bulk created coupons', [
                    'admin' => session('firebase_user.email'),
                    'count' => $request->count,
                    'created' => $result['created']
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Successfully created {$result['created']} coupons",
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
            Log::error('Error bulk creating coupons: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating coupons: ' . $e->getMessage()
            ]);
        }
    }

    // ============ IMPORT/EXPORT ============

    /**
     * Export coupons data
     */
    public function export(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'format' => 'required|in:csv,excel',
            'status' => 'nullable|in:' . implode(',', array_keys(Coupon::getStatuses())),
            'coupon_type' => 'nullable|in:' . implode(',', array_keys(Coupon::getCouponTypes())),
            'created_from' => 'nullable|date',
            'created_to' => 'nullable|date|after_or_equal:created_from'
        ]);

        if ($validator->fails()) {
            return redirect()->route('coupons.index')
                ->with('error', 'Invalid export parameters.');
        }

        try {
            $filters = [
                'status' => $request->status,
                'coupon_type' => $request->coupon_type,
                'created_from' => $request->created_from,
                'created_to' => $request->created_to
            ];

            $coupons = $this->couponService->exportCoupons($filters);
            
            $filename = 'coupons_export_' . now()->format('Y_m_d_H_i_s') . '.csv';
            
            Log::info('Admin exported coupons', [
                'admin' => session('firebase_user.email'),
                'count' => count($coupons),
                'filters' => $filters
            ]);

            return $this->generateCsvExport($coupons, $filename);

        } catch (\Exception $e) {
            Log::error('Error exporting coupons: ' . $e->getMessage());
            return redirect()->route('coupons.index')
                ->with('error', 'Error exporting coupons: ' . $e->getMessage());
        }
    }

    // ============ STATISTICS ============

    /**
     * Enhanced statistics with sync information
     */
    public function statistics()
    {
        try {
            $statistics = $this->couponService->getCouponStatistics();
            $syncHealth = $this->couponService->checkSyncHealth();
            
            return view('coupon::admin.coupons.statistics', compact('statistics', 'syncHealth'));

        } catch (\Exception $e) {
            Log::error('Error loading coupon statistics: ' . $e->getMessage());
            return redirect()->route('coupons.index')
                ->with('error', 'Error loading statistics.');
        }
    }

    /**
 * Disable coupon
 */
public function disable(string $code)
{
    try {
        $result = $this->couponService->updateCouponStatus($code, 'disabled', session('firebase_user.uid'));
        
        if ($result) {
            return redirect()->back()->with('success', 'Coupon disabled successfully!');
        } else {
            return redirect()->back()->with('error', 'Failed to disable coupon.');
        }
    } catch (\Exception $e) {
        Log::error('Error disabling coupon: ' . $e->getMessage());
        return redirect()->back()->with('error', 'Error disabling coupon.');
    }
}

/**
 * Enable coupon
 */
public function enable(string $code)
{
    try {
        $result = $this->couponService->updateCouponStatus($code, 'enabled', session('firebase_user.uid'));
        
        if ($result) {
            return redirect()->back()->with('success', 'Coupon enabled successfully!');
        } else {
            return redirect()->back()->with('error', 'Failed to enable coupon.');
        }
    } catch (\Exception $e) {
        Log::error('Error enabling coupon: ' . $e->getMessage());
        return redirect()->back()->with('error', 'Error enabling coupon.');
    }
}

/**
 * Force sync single coupon to Firebase
 */
public function forceSync(string $code)
{
    try {
        $coupon = $this->couponService->getCouponByCode($code);
        
        if (!$coupon) {
            return response()->json(['success' => false, 'message' => 'Coupon not found'], 404);
        }

        // Force immediate sync
        $result = $this->couponService->syncCouponImmediately($coupon, 'update');
        
        if ($result) {
            return response()->json(['success' => true, 'message' => 'Coupon synced successfully!']);
        } else {
            return response()->json(['success' => false, 'message' => 'Sync failed']);
        }
    } catch (\Exception $e) {
        Log::error('Error force syncing coupon: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => 'Sync failed: ' . $e->getMessage()]);
    }
}

/**
 * Duplicate coupon (placeholder)
 */
public function duplicate(string $code)
{
    // Implement coupon duplication logic
    return redirect()->route('coupons.create')->with('info', 'Duplication feature coming soon!');
}

/**
 * Show coupon analytics (placeholder)
 */
public function analytics(string $code)
{
    // Implement analytics view
    return redirect()->route('coupons.show', $code)->with('info', 'Analytics feature coming soon!');
}

    // ============ HELPER METHODS ============

    /**
     * Generate bulk action message
     */
    private function getBulkActionMessage(string $action, int $processed, int $failed): string
    {
        $messages = [
            'enable' => "Enabled {$processed} coupons",
            'disable' => "Disabled {$processed} coupons",
            'delete' => "Deleted {$processed} coupons"
        ];

        $message = $messages[$action] ?? "Processed {$processed} coupons";
        
        if ($failed > 0) {
            $message .= " ({$failed} failed)";
        }

        return $message;
    }

    /**
     * Generate CSV export
     */
    private function generateCsvExport(array $coupons, string $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($coupons) {
            $file = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($file, [
                'Code', 'Description', 'Coupon Type', 'Discount Type', 'Discount Value',
                'Minimum Amount', 'Maximum Discount', 'Usage Limit', 'Used Count',
                'User Usage Limit', 'Starts At', 'Expires At', 'Status', 'Is Active',
                'Firebase Synced', 'Created At'
            ]);

            // CSV Data
            foreach ($coupons as $coupon) {
                fputcsv($file, [
                    $coupon['code'] ?? '',
                    $coupon['description'] ?? '',
                    $coupon['coupon_type'] ?? '',
                    $coupon['discount_type'] ?? '',
                    $coupon['discount_value'] ?? '',
                    $coupon['minimum_amount'] ?? '',
                    $coupon['maximum_discount'] ?? '',
                    $coupon['usage_limit'] ?? '',
                    $coupon['used_count'] ?? '',
                    $coupon['user_usage_limit'] ?? '',
                    $coupon['starts_at'] ?? '',
                    $coupon['expires_at'] ?? '',
                    $coupon['status'] ?? '',
                    $coupon['is_active'] ?? '',
                    $coupon['firebase_synced'] ?? '',
                    $coupon['created_at'] ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}