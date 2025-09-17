<?php

// routes/admin.php or routes/web.php (Admin section) - CORRECTED VERSION
use Illuminate\Support\Facades\Route;

use App\Modules\Driver\Controllers\Admin\AdminDashboardController;
use App\Modules\Driver\Controllers\Admin\AdminDriverController;
use App\Modules\Driver\Controllers\Admin\AdminVehicleController;
use App\Modules\Driver\Controllers\Admin\AdminDocumentController;
use App\Modules\Driver\Controllers\Admin\AdminRideController;
use App\Modules\Driver\Controllers\Admin\AdminActivityController;

// Admin Routes Group with Firebase Authentication and Admin Middleware
Route::group([
    'prefix' => 'admin',
    'as' => 'admin.',
], function () {

    // ============ ADMIN DASHBOARD ROUTES ============
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard.index');

    // Dashboard AJAX Routes
    Route::get('/dashboard/overview-stats', [AdminDashboardController::class, 'getOverviewStats'])->name('dashboard.overview-stats');
    Route::get('/dashboard/realtime-metrics', [AdminDashboardController::class, 'getRealTimeMetrics'])->name('dashboard.realtime-metrics');
    Route::get('/dashboard/system-health', [AdminDashboardController::class, 'getSystemHealth'])->name('dashboard.system-health');
    Route::post('/dashboard/export', [AdminDashboardController::class, 'exportDashboard'])->name('dashboard.export');

    // ============ DRIVER MANAGEMENT ROUTES ============
    Route::prefix('drivers')->as('drivers.')->group(function () {
        // Standard CRUD Routes
        Route::get('/', [AdminDriverController::class, 'index'])->name('index');
        Route::get('/create', [AdminDriverController::class, 'create'])->name('create');
        Route::post('/', [AdminDriverController::class, 'store'])->name('store');
        Route::get('/{firebaseUid}', [AdminDriverController::class, 'show'])->name('show');
        Route::get('/{firebaseUid}/edit', [AdminDriverController::class, 'edit'])->name('edit');
        Route::put('/{firebaseUid}', [AdminDriverController::class, 'update'])->name('update');
        Route::delete('/{firebaseUid}', [AdminDriverController::class, 'destroy'])->name('destroy');

        // Tab loading route
        Route::get('/{firebaseUid}/tab/{tab}', [AdminDriverController::class, 'loadTab'])->name('tab');

        // Status Management Routes
        Route::patch('/{firebaseUid}/status', [AdminDriverController::class, 'updateStatus'])->name('update-status');
        Route::patch('/{firebaseUid}/verification', [AdminDriverController::class, 'updateVerificationStatus'])->name('update-verification');
        Route::post('/{firebaseUid}/activate', [AdminDriverController::class, 'activate'])->name('activate');

        // Bulk Operations
        Route::post('/bulk-action', [AdminDriverController::class, 'bulkAction'])->name('bulk-action');

        // Import/Export Routes
        Route::post('/import', [AdminDriverController::class, 'import'])->name('import');
        Route::post('/export', [AdminDriverController::class, 'export'])->name('export');
        Route::post('/sync-firebase', [AdminDriverController::class, 'syncFirebase'])->name('sync-firebase');

        // Statistics
        Route::get('/statistics/dashboard', [AdminDriverController::class, 'statistics'])->name('statistics');
    });

    /*
    |--------------------------------------------------------------------------
    | Vehicle Management Routes - FIXED (removed duplicate admin/ prefix)
    |--------------------------------------------------------------------------
    */
    Route::prefix('vehicles')->name('vehicles.')->group(function () {

        // Dashboard and listing
        Route::get('/', [AdminVehicleController::class, 'index'])->name('index');
        Route::get('/statistics', [AdminVehicleController::class, 'statistics'])->name('statistics');

        // CRUD operations
        Route::get('/create', [AdminVehicleController::class, 'create'])->name('create');
        Route::post('/', [AdminVehicleController::class, 'store'])->name('store');
        Route::get('/{vehicle}', [AdminVehicleController::class, 'show'])->name('show');
        Route::get('/{vehicle}/edit', [AdminVehicleController::class, 'edit'])->name('edit');
        Route::put('/{vehicle}', [AdminVehicleController::class, 'update'])->name('update');
        Route::delete('/{vehicle}', [AdminVehicleController::class, 'destroy'])->name('destroy');

        // AJAX endpoints
        Route::post('/{vehicle}/status', [AdminVehicleController::class, 'updateStatus'])->name('update-status');
        Route::post('/{vehicle}/verification', [AdminVehicleController::class, 'updateVerificationStatus'])->name('update-verification');
        Route::get('/{vehicle}/tabs/{tab}', [AdminVehicleController::class, 'loadTab'])->name('load-tab');

        // Bulk operations
        Route::post('/bulk-action', [AdminVehicleController::class, 'bulkAction'])->name('bulk-action');

        // Import/Export
        Route::get('/export/csv', [AdminVehicleController::class, 'export'])->name('export');
        Route::post('/import', [AdminVehicleController::class, 'import'])->name('import');

        // Firebase sync
        Route::post('/sync-firebase', [AdminVehicleController::class, 'syncFirebase'])->name('sync-firebase');

        // Testing
        Route::get('/{vehicle}/test-cache', [AdminVehicleController::class, 'testCacheClearing'])->name('test-cache');
    });

    // ============ DOCUMENT MANAGEMENT ROUTES ============
    Route::prefix('documents')->as('documents.')->group(function () {
        // Standard CRUD Routes
        Route::get('/', [AdminDocumentController::class, 'index'])->name('index');
        Route::get('/create', [AdminDocumentController::class, 'create'])->name('create');
        Route::post('/', [AdminDocumentController::class, 'store'])->name('store');
        Route::get('/{documentId}', [AdminDocumentController::class, 'show'])->name('show');
        Route::get('/{documentId}/edit', [AdminDocumentController::class, 'edit'])->name('edit');
        Route::put('/{documentId}', [AdminDocumentController::class, 'update'])->name('update');
        Route::delete('/{documentId}', [AdminDocumentController::class, 'destroy'])->name('destroy');

        // Document Verification Routes
        Route::post('/{documentId}/verify', [AdminDocumentController::class, 'verify'])->name('verify');
        Route::post('/{documentId}/reject', [AdminDocumentController::class, 'reject'])->name('reject');
        Route::get('/{documentId}/download', [AdminDocumentController::class, 'download'])->name('download');

        // Bulk Operations
        Route::post('/bulk-action', [AdminDocumentController::class, 'bulkAction'])->name('bulk-action');

        // Special Views
        Route::get('/verification/queue', [AdminDocumentController::class, 'verificationQueue'])->name('verification-queue');
        Route::get('/statistics/dashboard', [AdminDocumentController::class, 'statistics'])->name('statistics');
    });

    // ============ RIDE MANAGEMENT ROUTES ============
    Route::prefix('rides')->as('rides.')->group(function () {
        // Standard CRUD Routes
        Route::get('/', [AdminRideController::class, 'index'])->name('index');
        Route::get('/create', [AdminRideController::class, 'create'])->name('create');
        Route::post('/', [AdminRideController::class, 'store'])->name('store');
        Route::get('/{rideId}', [AdminRideController::class, 'show'])->name('show');
        Route::get('/{rideId}/edit', [AdminRideController::class, 'edit'])->name('edit');
        Route::put('/{rideId}', [AdminRideController::class, 'update'])->name('update');

        // Ride Management Routes
        Route::patch('/{rideId}/status', [AdminRideController::class, 'updateStatus'])->name('update-status');
        Route::post('/{rideId}/complete', [AdminRideController::class, 'complete'])->name('complete');
        Route::post('/{rideId}/cancel', [AdminRideController::class, 'cancel'])->name('cancel');

        // Export Route
        Route::get('/export', [AdminRideController::class, 'export'])->name('export');

        // Statistics
        Route::get('/statistics/dashboard', [AdminRideController::class, 'statistics'])->name('statistics');
    });

    // ============ ACTIVITY MANAGEMENT ROUTES ============
    Route::prefix('activities')->as('activities.')->group(function () {
        // Standard CRUD Routes
        Route::get('/', [AdminActivityController::class, 'index'])->name('index');
        Route::get('/create', [AdminActivityController::class, 'create'])->name('create');
        Route::post('/', [AdminActivityController::class, 'store'])->name('store');
        Route::get('/{activityId}', [AdminActivityController::class, 'show'])->name('show');
        Route::get('/{activityId}/edit', [AdminActivityController::class, 'edit'])->name('edit');
        Route::put('/{activityId}', [AdminActivityController::class, 'update'])->name('update');
        Route::delete('/{activityId}', [AdminActivityController::class, 'destroy'])->name('destroy');

        // Activity Management Routes
        Route::post('/{activityId}/mark-read', [AdminActivityController::class, 'markAsRead'])->name('mark-read');
        Route::post('/{activityId}/archive', [AdminActivityController::class, 'archive'])->name('archive');

        // Bulk Operations
        Route::post('/bulk-action', [AdminActivityController::class, 'bulkAction'])->name('bulk-action');

        // Statistics
        Route::get('/statistics/dashboard', [AdminActivityController::class, 'statistics'])->name('statistics');
    });

    // ============ API ROUTES FOR AJAX CALLS ============
    Route::prefix('api')->as('api.')->group(function () {

        // Driver API Routes
        Route::prefix('drivers')->as('drivers.')->group(function () {
            Route::get('/', [AdminDriverController::class, 'index']); // For DataTables
            Route::get('/{firebaseUid}/vehicles', [AdminDriverController::class, 'vehicles']);
            Route::post('/{firebaseUid}/vehicles', [AdminDriverController::class, 'addVehicle']);
            Route::put('/vehicles/{vehicleId}', [AdminDriverController::class, 'updateVehicle']);
            Route::delete('/vehicles/{vehicleId}', [AdminDriverController::class, 'deleteVehicle']);
            Route::post('/{firebaseUid}/vehicles/{vehicleId}/primary', [AdminDriverController::class, 'setPrimaryVehicle']);
            Route::get('/{firebaseUid}/documents', [AdminDriverController::class, 'documents']);
            Route::post('/{firebaseUid}/documents', [AdminDriverController::class, 'uploadDocument']);
            Route::post('/documents/{documentId}/verify', [AdminDriverController::class, 'verifyDocument']);
            Route::post('/documents/{documentId}/reject', [AdminDriverController::class, 'rejectDocument']);
            Route::get('/{firebaseUid}/rides', [AdminDriverController::class, 'rides']);
            Route::get('/{firebaseUid}/activities', [AdminDriverController::class, 'activities']);
            Route::post('/activities/{activityId}/read', [AdminDriverController::class, 'markActivityRead']);
            Route::patch('/{firebaseUid}/location', [AdminDriverController::class, 'updateLocation']);
            Route::patch('/{firebaseUid}/availability', [AdminDriverController::class, 'updateAvailability']);
            Route::get('/nearby', [AdminDriverController::class, 'nearbyDrivers']);
            Route::get('/{firebaseUid}/dashboard', [AdminDriverController::class, 'dashboard']);
            Route::get('/statistics/api', [AdminDriverController::class, 'statisticsApi']);
            Route::get('/admin-dashboard', [AdminDriverController::class, 'adminDashboard']);
        });

        // Document API Routes
        Route::prefix('documents')->as('documents.')->group(function () {
            Route::get('/pending', [AdminDocumentController::class, 'pendingDocuments']);
        });
    });

    // ============ UTILITY ROUTES ============
    Route::prefix('utilities')->as('utilities.')->group(function () {
        // Clear cache routes
        Route::post('/clear-cache', function () {
            \Artisan::call('cache:clear');
            return response()->json(['success' => true, 'message' => 'Cache cleared successfully']);
        })->name('clear-cache');

        // System maintenance routes  
        Route::post('/maintenance-mode', function () {
            \Artisan::call('down');
            return response()->json(['success' => true, 'message' => 'Maintenance mode enabled']);
        })->name('maintenance-mode');

        Route::post('/maintenance-mode/disable', function () {
            \Artisan::call('up');
            return response()->json(['success' => true, 'message' => 'Maintenance mode disabled']);
        })->name('maintenance-mode.disable');
    });
});

// Legacy route redirects
Route::get('/dashboard', [App\Http\Controllers\FirebaseAuthController::class, 'dashboard'])->name('dashboard');
Route::get('/admin-dashboard', [AdminDriverController::class, 'adminDashboard'])->name('admin-dashboard');
