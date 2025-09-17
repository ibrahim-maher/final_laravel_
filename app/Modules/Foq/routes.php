<?php
// app/Modules/Foq/routes.php

use Illuminate\Support\Facades\Route;
use App\Modules\Foq\Controllers\Admin\AdminFoqController;

Route::prefix('foq')->group(function () {
    // Main FOQ routes
    Route::get('foqs', [AdminFoqController::class, 'index'])->name('foqs.index');
    Route::get('foqs/create', [AdminFoqController::class, 'create'])->name('foqs.create');
    Route::post('foqs', [AdminFoqController::class, 'store'])->name('foqs.store');
    Route::get('foqs/statistics/dashboard', [AdminFoqController::class, 'statistics'])->name('foqs.statistics');
    Route::get('foqs/export/csv', [AdminFoqController::class, 'export'])->name('foqs.export');
    
    // AJAX and API routes
    Route::post('foqs/bulk-create', [AdminFoqController::class, 'bulkCreate'])->name('foqs.bulk-create');
    Route::post('foqs/bulk-action', [AdminFoqController::class, 'bulkAction'])->name('foqs.bulk-action');
    Route::post('foqs/sync-firebase', [AdminFoqController::class, 'syncFirebase'])->name('foqs.sync-firebase');
    Route::get('foqs/sync-status', [AdminFoqController::class, 'getSyncStatus'])->name('foqs.sync-status');
    
    // Individual FOQ routes (ID-specific)
    Route::get('foqs/{id}', [AdminFoqController::class, 'show'])->name('foqs.show')->where('id', '[0-9]+');
    Route::get('foqs/{id}/edit', [AdminFoqController::class, 'edit'])->name('foqs.edit')->where('id', '[0-9]+');
    Route::put('foqs/{id}', [AdminFoqController::class, 'update'])->name('foqs.update')->where('id', '[0-9]+');
    Route::delete('foqs/{id}', [AdminFoqController::class, 'destroy'])->name('foqs.destroy')->where('id', '[0-9]+');
    Route::patch('foqs/{id}/status', [AdminFoqController::class, 'updateStatus'])->name('foqs.update-status')->where('id', '[0-9]+');
    
    // Additional FOQ actions
    Route::patch('foqs/{id}/activate', [AdminFoqController::class, 'activate'])->name('foqs.activate')->where('id', '[0-9]+');
    Route::patch('foqs/{id}/deactivate', [AdminFoqController::class, 'deactivate'])->name('foqs.deactivate')->where('id', '[0-9]+');
    Route::post('foqs/{id}/force-sync', [AdminFoqController::class, 'forceSync'])->name('foqs.force-sync')->where('id', '[0-9]+');
    Route::get('foqs/{id}/duplicate', [AdminFoqController::class, 'duplicate'])->name('foqs.duplicate')->where('id', '[0-9]+');
    Route::post('foqs/{id}/feedback', [AdminFoqController::class, 'recordFeedback'])->name('foqs.feedback')->where('id', '[0-9]+');
    
    // Test route
    Route::get('test', function() {
        return 'FOQ module test route';
    })->name('foq.test');
});