<?php
// routes/complaint.php

use Illuminate\Support\Facades\Route;
use App\Modules\Complaint\Controllers\Admin\AdminComplaintController;

Route::prefix('complaint')->group(function () {
    // Main complaint routes
    Route::get('complaints', [AdminComplaintController::class, 'index'])->name('complaints.index');

    Route::get('complaints/export/csv', [AdminComplaintController::class, 'export'])->name('complaints.export');
    
    // AJAX and API routes
    Route::post('complaints/bulk-action', [AdminComplaintController::class, 'bulkAction'])->name('complaints.bulk-action');
    Route::post('complaints/refresh', [AdminComplaintController::class, 'refresh'])->name('complaints.refresh');
    Route::get('complaints/health-check', [AdminComplaintController::class, 'healthCheck'])->name('complaints.health-check');
    
    // Individual complaint routes (ID-specific)
    Route::get('complaints/{id}', [AdminComplaintController::class, 'show'])->name('complaints.show');
    Route::patch('complaints/{id}/status', [AdminComplaintController::class, 'updateStatus'])->name('complaints.update-status');
    Route::post('complaints/{id}/notes', [AdminComplaintController::class, 'addNotes'])->name('complaints.add-notes');
    
    // Test route (optional)
    Route::get('test', function() {
        return 'Complaint test route';
    })->name('complaint.test');
});