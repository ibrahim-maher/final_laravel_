<?php
// app/Modules/TaxSetting/Controllers/Admin/AdminTaxSettingController.php

namespace App\Modules\TaxSetting\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Modules\TaxSetting\Services\TaxSettingService;
use App\Modules\TaxSetting\Models\TaxSetting;

class AdminTaxSettingController extends Controller
{
    protected $taxSettingService;

    public function __construct(TaxSettingService $taxSettingService)
    {
        $this->taxSettingService = $taxSettingService;
    }

    // ============ DASHBOARD AND LISTING ============

    /**
     * Display tax setting management dashboard
     */
    public function index(Request $request)
    {
        try {
            $filters = [
                'search' => $request->get('search'),
                'tax_type' => $request->get('tax_type'),
                'calculation_method' => $request->get('calculation_method'),
                'applicable_to' => $request->get('applicable_to'),
                'is_active' => $request->get('is_active'),
                'limit' => min($request->get('limit', 25), 50)
            ];

            // Get tax settings
            $taxSettings = $this->taxSettingService->getAllTaxSettings($filters);

            // Get total count
            $totalTaxSettings = $this->taxSettingService->getTotalTaxSettingsCount();

            // Get statistics
            $statistics = $this->taxSettingService->getTaxSettingStatistics();

            Log::info('Admin tax setting dashboard accessed', [
                'admin' => session('firebase_user.email'),
                'filters' => $filters,
                'result_count' => $taxSettings->count(),
                'total_tax_settings' => $totalTaxSettings
            ]);

            return view('taxsetting::admin.tax_settings.index', compact(
                'taxSettings',
                'totalTaxSettings',
                'statistics'
            ) + $filters);
        } catch (\Exception $e) {
            Log::error('Error loading admin tax setting dashboard: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Error loading tax setting dashboard: ' . $e->getMessage());
        }
    }
    public function export(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'format' => 'required|in:csv',
            'search' => 'nullable|string|max:255',
            'tax_type' => 'nullable|in:' . implode(',', array_keys(TaxSetting::getTaxTypes())),
            'calculation_method' => 'nullable|in:' . implode(',', array_keys(TaxSetting::getCalculationMethods())),
            'applicable_to' => 'nullable|in:' . implode(',', array_keys(TaxSetting::getApplicableToOptions())),
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->route('tax-settings.index')
                ->with('error', 'Invalid export parameters.');
        }

        try {
            $filters = [
                'search' => $request->search,
                'tax_type' => $request->tax_type,
                'calculation_method' => $request->calculation_method,
                'applicable_to' => $request->applicable_to,
                'is_active' => $request->is_active
            ];

            $filename = 'tax_settings_export_' . now()->format('Y_m_d_H_i_s') . '.csv';

            Log::info('Admin exported tax settings', [
                'admin' => session('firebase_user.email'),
                'format' => $request->format,
                'filters' => $filters
            ]);

            return Excel::download(new TaxSettingsExport($filters), $filename);
        } catch (\Exception $e) {
            Log::error('Error exporting tax settings: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('tax-settings.index')
                ->with('error', 'Error exporting tax settings: ' . $e->getMessage());
        }
    }

    /**
     * Show detailed tax setting information
     */
    public function show(int $id)
    {
        try {
            $taxSetting = $this->taxSettingService->getTaxSettingById($id);

            if (!$taxSetting) {
                return redirect()->route('tax-settings.index')
                    ->with('error', 'Tax setting not found.');
            }

            Log::info('Admin viewed tax setting details', [
                'admin' => session('firebase_user.email'),
                'tax_setting_id' => $id
            ]);

            return view('taxsetting::admin.tax-settings.show', compact('taxSetting'));
        } catch (\Exception $e) {
            Log::error('Error loading tax setting details: ' . $e->getMessage());
            return redirect()->route('tax-settings.index')
                ->with('error', 'Error loading tax setting details.');
        }
    }

    // ============ CRUD OPERATIONS ============

    /**
     * Show form for creating new tax setting
     */
    public function create()
    {
        return view('taxsetting::admin.tax_settings.create', [
            'taxTypes' => TaxSetting::getTaxTypes(),
            'calculationMethods' => TaxSetting::getCalculationMethods(),
            'applicableToOptions' => TaxSetting::getApplicableToOptions()
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'tax_type' => 'required|in:' . implode(',', array_keys(TaxSetting::getTaxTypes())),
            'calculation_method' => 'required|in:' . implode(',', array_keys(TaxSetting::getCalculationMethods())),
            'rate' => 'required_if:tax_type,percentage,hybrid|nullable|numeric|min:0|max:100',
            'fixed_amount' => 'required_if:tax_type,fixed,hybrid|nullable|numeric|min:0|max:999999',
            'minimum_taxable_amount' => 'nullable|numeric|min:0',
            'maximum_tax_amount' => 'nullable|numeric|min:0',
            'applicable_to' => 'required|in:' . implode(',', array_keys(TaxSetting::getApplicableToOptions())),
            'applicable_zones' => 'nullable|array',
            'excluded_zones' => 'nullable|array',
            'applicable_vehicle_types' => 'nullable|array',
            'excluded_vehicle_types' => 'nullable|array',
            'applicable_services' => 'nullable|array',
            'excluded_services' => 'nullable|array',
            'is_inclusive' => 'boolean',
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

            $syncImmediately = $request->boolean('sync_immediately', false);
            $taxSetting = $this->taxSettingService->createTaxSetting($data, $syncImmediately);

            if (!$taxSetting) {
                return redirect()->back()
                    ->with('error', 'Failed to create tax setting.')
                    ->withInput();
            }

            $message = 'Tax setting created successfully!';
            if ($syncImmediately) {
                $message .= ' Firebase sync completed.';
            } else {
                $message .= ' Firebase sync queued.';
            }

            Log::info('Admin created tax setting', [
                'admin' => session('firebase_user.email'),
                'tax_setting_id' => $taxSetting->id,
                'sync_method' => $syncImmediately ? 'immediate' : 'queued'
            ]);

            return redirect()->route('tax-settings.show', $taxSetting->id)
                ->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Error creating tax setting: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->with('error', 'Error creating tax setting: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show form for editing tax setting
     */
    public function edit(int $id)
    {
        try {
            $taxSetting = $this->taxSettingService->getTaxSettingById($id);

            if (!$taxSetting) {
                return redirect()->route('tax-settings.index')
                    ->with('error', 'Tax setting not found.');
            }

            return view('taxsetting::admin.tax-settings.edit', [
                'taxSetting' => $taxSetting,
                'taxTypes' => TaxSetting::getTaxTypes(),
                'calculationMethods' => TaxSetting::getCalculationMethods(),
                'applicableToOptions' => TaxSetting::getApplicableToOptions()
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading tax setting for edit: ' . $e->getMessage());
            return redirect()->route('tax-settings.index')
                ->with('error', 'Error loading tax setting for editing.');
        }
    }

    /**
     * Update tax setting information with sync option
     */
    public function update(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'tax_type' => 'required|in:' . implode(',', array_keys(TaxSetting::getTaxTypes())),
            'calculation_method' => 'required|in:' . implode(',', array_keys(TaxSetting::getCalculationMethods())),
            'rate' => 'required_if:tax_type,percentage,hybrid|nullable|numeric|min:0|max:100',
            'fixed_amount' => 'required_if:tax_type,fixed,hybrid|nullable|numeric|min:0|max:999999',
            'minimum_taxable_amount' => 'nullable|numeric|min:0',
            'maximum_tax_amount' => 'nullable|numeric|min:0',
            'applicable_to' => 'required|in:' . implode(',', array_keys(TaxSetting::getApplicableToOptions())),
            'applicable_zones' => 'nullable|array',
            'excluded_zones' => 'nullable|array',
            'applicable_vehicle_types' => 'nullable|array',
            'excluded_vehicle_types' => 'nullable|array',
            'applicable_services' => 'nullable|array',
            'excluded_services' => 'nullable|array',
            'is_inclusive' => 'boolean',
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
            $result = $this->taxSettingService->updateTaxSetting($id, $data, $syncImmediately);

            if ($result) {
                Log::info('Admin updated tax setting', [
                    'admin' => session('firebase_user.email'),
                    'tax_setting_id' => $id,
                    'sync_method' => $syncImmediately ? 'immediate' : 'queued'
                ]);

                $message = 'Tax setting updated successfully!';
                if ($syncImmediately) {
                    $message .= ' Firebase sync completed.';
                } else {
                    $message .= ' Firebase sync queued.';
                }

                return redirect()->route('tax-settings.show', $id)
                    ->with('success', $message);
            } else {
                return redirect()->back()
                    ->with('error', 'Failed to update tax setting.')
                    ->withInput();
            }
        } catch (\Exception $e) {
            Log::error('Error updating tax setting: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error updating tax setting: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Delete tax setting
     */
    public function destroy(int $id)
    {
        try {
            $taxSetting = $this->taxSettingService->getTaxSettingById($id);

            if (!$taxSetting) {
                return redirect()->route('tax-settings.index')
                    ->with('error', 'Tax setting not found.');
            }

            $result = $this->taxSettingService->deleteTaxSetting($id);

            if ($result) {
                Log::info('Admin deleted tax setting', [
                    'admin' => session('firebase_user.email'),
                    'tax_setting_id' => $id
                ]);

                return redirect()->route('tax-settings.index')
                    ->with('success', 'Tax setting deleted successfully!');
            } else {
                return redirect()->back()
                    ->with('error', 'Failed to delete tax setting.');
            }
        } catch (\Exception $e) {
            Log::error('Error deleting tax setting: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error deleting tax setting: ' . $e->getMessage());
        }
    }

    // ============ AJAX ENDPOINTS ============

    /**
     * Update tax setting status (AJAX)
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
            $result = $this->taxSettingService->updateTaxSettingStatus(
                $id,
                $request->boolean('is_active'),
                session('firebase_user.uid')
            );

            if ($result) {
                Log::info('Admin updated tax setting status', [
                    'admin' => session('firebase_user.email'),
                    'tax_setting_id' => $id,
                    'is_active' => $request->boolean('is_active')
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Tax setting status updated successfully!'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update tax setting status'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error updating tax setting status: ' . $e->getMessage());
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
                    $result = $this->taxSettingService->checkSyncHealth();
                    return response()->json([
                        'success' => true,
                        'data' => $result,
                        'message' => 'Sync health check completed'
                    ]);

                case 'force':
                    $result = $this->taxSettingService->forceSyncAll();
                    break;

                default:
                    $result = $this->taxSettingService->runAutoSync();
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
     * Calculate tax preview (AJAX)
     */
    public function calculateTax(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'tax_setting_id' => 'required|integer|exists:tax_settings,id',
            'context' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input'
            ], 400);
        }

        try {
            $result = $this->taxSettingService->calculateTax(
                $request->tax_setting_id,
                $request->amount,
                $request->context ?? []
            );

            return response()->json([
                'success' => true,
                'tax_amount' => $result['tax_amount'],
                'applicable' => $result['applicable'],
                'total_amount' => $result['total_amount'],
                'breakdown' => $result['breakdown'] ?? null
            ]);
        } catch (\Exception $e) {
            Log::error('Error calculating tax: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error calculating tax: ' . $e->getMessage()
            ]);
        }
    }

    // ============ BULK OPERATIONS ============

    /**
     * Bulk operations on tax settings
     */
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:activate,deactivate,delete',
            'tax_setting_ids' => 'required|array|min:1',
            'tax_setting_ids.*' => 'required|integer|exists:tax_settings,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input'
            ], 400);
        }

        try {
            $result = $this->taxSettingService->performBulkAction(
                $request->action,
                $request->tax_setting_ids
            );

            if ($result['success']) {
                Log::info('Admin performed bulk action on tax settings', [
                    'admin' => session('firebase_user.email'),
                    'action' => $request->action,
                    'count' => count($request->tax_setting_ids)
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

    // ============ STATISTICS ============

    /**
     * Enhanced statistics with sync information
     */
    public function statistics()
    {
        try {
            $statistics = $this->taxSettingService->getTaxSettingStatistics();
            $syncHealth = $this->taxSettingService->checkSyncHealth();

            return view('taxsetting::admin.tax-settings.statistics', compact('statistics', 'syncHealth'));
        } catch (\Exception $e) {
            Log::error('Error loading tax setting statistics: ' . $e->getMessage());
            return redirect()->route('tax-settings.index')
                ->with('error', 'Error loading statistics.');
        }
    }

    // ============ HELPER METHODS ============

    /**
     * Generate bulk action message
     */
    private function getBulkActionMessage(string $action, int $processed, int $failed): string
    {
        $messages = [
            'activate' => "Activated {$processed} tax settings",
            'deactivate' => "Deactivated {$processed} tax settings",
            'delete' => "Deleted {$processed} tax settings"
        ];

        $message = $messages[$action] ?? "Processed {$processed} tax settings";

        if ($failed > 0) {
            $message .= " ({$failed} failed)";
        }

        return $message;
    }
}
