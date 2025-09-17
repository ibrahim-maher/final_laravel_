<?php
// app/Modules/Page/routes.php

use Illuminate\Support\Facades\Route;
use App\Modules\Page\Controllers\Admin\AdminPageController;

Route::prefix('page')->group(function () {
    // Main Page routes
    Route::get('pages', [AdminPageController::class, 'index'])->name('pages.index');
    Route::get('pages/create', [AdminPageController::class, 'create'])->name('pages.create');
    Route::post('pages', [AdminPageController::class, 'store'])->name('pages.store');
    Route::get('pages/statistics/dashboard', [AdminPageController::class, 'statistics'])->name('pages.statistics');
    Route::get('pages/export/csv', [AdminPageController::class, 'export'])->name('pages.export');

    // AJAX and API routes
    Route::post('pages/bulk-action', [AdminPageController::class, 'bulkAction'])->name('pages.bulk-action');
    Route::post('pages/sync-firebase', [AdminPageController::class, 'syncFirebase'])->name('pages.sync-firebase');
    Route::get('pages/sync-status', [AdminPageController::class, 'getSyncStatus'])->name('pages.sync-status');

    // Individual Page routes (ID-specific)
    Route::get('pages/{id}', [AdminPageController::class, 'show'])->name('pages.show')->where('id', '[0-9]+');
    Route::get('pages/{id}/edit', [AdminPageController::class, 'edit'])->name('pages.edit')->where('id', '[0-9]+');
    Route::put('pages/{id}', [AdminPageController::class, 'update'])->name('pages.update')->where('id', '[0-9]+');
    Route::delete('pages/{id}', [AdminPageController::class, 'destroy'])->name('pages.destroy')->where('id', '[0-9]+');
    Route::patch('pages/{id}/status', [AdminPageController::class, 'updateStatus'])->name('pages.update-status')->where('id', '[0-9]+');

    // Additional Page actions
    Route::patch('pages/{id}/activate', [AdminPageController::class, 'activate'])->name('pages.activate')->where('id', '[0-9]+');
    Route::patch('pages/{id}/deactivate', [AdminPageController::class, 'deactivate'])->name('pages.deactivate')->where('id', '[0-9]+');
    Route::post('pages/{id}/force-sync', [AdminPageController::class, 'forceSync'])->name('pages.force-sync')->where('id', '[0-9]+');
    Route::get('pages/{id}/duplicate', [AdminPageController::class, 'duplicate'])->name('pages.duplicate')->where('id', '[0-9]+');

    // Test route
    Route::get('test', function () {
        return 'Page module test route';
    })->name('page.test');
});
