<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Driver\Controllers\DriverController;

/*
|--------------------------------------------------------------------------
| Driver Module Routes
|--------------------------------------------------------------------------
|
| Here are the routes for the Driver module. These routes handle all
| driver-related functionality including management, vehicles, rides,
| documents, activities, and more.
|
*/

Route::middleware(['web', 'auth:firebase'])->group(function () {
    
    // Driver Management Routes
    Route::prefix('driver')->name('driver.')->group(function () {
        
        // Basic CRUD routes
        Route::get('/', [DriverController::class, 'index'])->name('index');
        Route::get('/create', [DriverController::class, 'create'])->name('create');
        Route::post('/', [DriverController::class, 'store'])->name('store');
        Route::get('/drivers/{firebaseUid}', [DriverController::class, 'show'])->name('show');
        Route::get('/drivers/{firebaseUid}/edit', [DriverController::class, 'edit'])->name('edit');
        Route::put('/drivers/{firebaseUid}', [DriverController::class, 'update'])->name('update');
        Route::delete('/drivers/{firebaseUid}', [DriverController::class, 'destroy'])->name('destroy');
        
        // Status Management Routes
        Route::post('/drivers/{firebaseUid}/toggle-status', [DriverController::class, 'toggleStatus'])->name('toggle-status');
        
        // Bulk Operations
        Route::post('/drivers/bulk-action', [DriverController::class, 'bulkAction'])->name('bulk-action');
        
        // Vehicle Management Routes
        Route::get('/drivers/{firebaseUid}/vehicles', [DriverController::class, 'vehicles'])->name('vehicles');
        Route::post('/drivers/{firebaseUid}/vehicles', [DriverController::class, 'addVehicle'])->name('add-vehicle');
        
        // Document Management Routes
        Route::get('/drivers/{firebaseUid}/documents', [DriverController::class, 'documents'])->name('documents');
        Route::post('/drivers/{firebaseUid}/documents', [DriverController::class, 'uploadDocument'])->name('upload-document');
        Route::post('/documents/{documentId}/verify', [DriverController::class, 'verifyDocument'])->name('verify-document');
        
        // Ride Management Routes
        Route::get('/drivers/{firebaseUid}/rides', [DriverController::class, 'rides'])->name('rides');
        
        // Activity Management Routes
        Route::get('/drivers/{firebaseUid}/activities', [DriverController::class, 'activities'])->name('activities');
        
        // Location Management Routes
        Route::post('/drivers/{firebaseUid}/location', [DriverController::class, 'updateLocation'])->name('update-location');
        Route::get('/nearby-drivers', [DriverController::class, 'nearbyDrivers'])->name('nearby-drivers');
        
        // Statistics Routes
        Route::get('/statistics', [DriverController::class, 'statistics'])->name('statistics');
        Route::get('/api/statistics', [DriverController::class, 'statisticsApi'])->name('api.statistics');
        
        // Sync and Import Routes
        Route::post('/sync-firebase', [DriverController::class, 'syncFirebase'])->name('sync');
        
        // Export Routes
        Route::post('/export', [DriverController::class, 'export'])->name('export');
    });
    
});

// API Routes for Mobile/External integrations
Route::middleware(['api'])->prefix('api/driver')->name('api.driver.')->group(function () {
    
    // Driver API endpoints
    Route::get('/drivers', [DriverController::class, 'index'])->name('index');
    Route::get('/drivers/{firebaseUid}', [DriverController::class, 'show'])->name('show');
    Route::post('/drivers/{firebaseUid}/location', [DriverController::class, 'updateLocation'])->name('update-location');
    Route::get('/nearby-drivers', [DriverController::class, 'nearbyDrivers'])->name('nearby-drivers');
    Route::get('/statistics', [DriverController::class, 'statisticsApi'])->name('statistics');
    
    // Vehicle API endpoints
    Route::get('/drivers/{firebaseUid}/vehicles', [DriverController::class, 'vehicles'])->name('vehicles');
    Route::post('/drivers/{firebaseUid}/vehicles', [DriverController::class, 'addVehicle'])->name('add-vehicle');
    
    // Document API endpoints
    Route::get('/drivers/{firebaseUid}/documents', [DriverController::class, 'documents'])->name('documents');
    Route::post('/drivers/{firebaseUid}/documents', [DriverController::class, 'uploadDocument'])->name('upload-document');
    
    // Ride API endpoints
    Route::get('/drivers/{firebaseUid}/rides', [DriverController::class, 'rides'])->name('rides');
    
    // Activity API endpoints
    Route::get('/drivers/{firebaseUid}/activities', [DriverController::class, 'activities'])->name('activities');
    
});