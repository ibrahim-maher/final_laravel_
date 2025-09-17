<?php
// app/Modules/Commission/routes.php

use Illuminate\Support\Facades\Route;
use App\Modules\Commission\Controllers\Admin\AdminCommissionController;

Route::prefix('commission')->group(function () {
    // Main commission routes
    Route::get('commissions', [AdminCommissionController::class, 'index'])->name('commissions.index');
    Route::get('commissions/create', [AdminCommissionController::class, 'create'])->name('commissions.create');
    Route::post('commissions', [AdminCommissionController::class, 'store'])->name('commissions.store');
    Route::get('commissions/statistics/dashboard', [AdminCommissionController::class, 'statistics'])->name('commissions.statistics');
    Route::get('commissions/export', [AdminCommissionController::class, 'export'])->name('commissions.export');

    // AJAX and API routes
    Route::post('commissions/bulk-action', [AdminCommissionController::class, 'bulkAction'])->name('commissions.bulk-action');
    Route::post('commissions/sync-firebase', [AdminCommissionController::class, 'syncFirebase'])->name('commissions.sync-firebase');
    Route::post('commissions/calculate-commission', [AdminCommissionController::class, 'calculateCommission'])->name('commissions.calculate-commission');

    // Individual commission routes (ID-specific)
    Route::get('commissions/{id}', [AdminCommissionController::class, 'show'])->name('commissions.show');
    Route::get('commissions/{id}/edit', [AdminCommissionController::class, 'edit'])->name('commissions.edit');
    Route::put('commissions/{id}', [AdminCommissionController::class, 'update'])->name('commissions.update');
    Route::delete('commissions/{id}', [AdminCommissionController::class, 'destroy'])->name('commissions.destroy');
    Route::patch('commissions/{id}/status', [AdminCommissionController::class, 'updateStatus'])->name('commissions.update-status');

    // Test route
    Route::get('test', function () {
        return 'Commission test route';
    })->name('commission.test');
});
