<?php
// app/Modules/TaxSetting/routes.php

use Illuminate\Support\Facades\Route;
use App\Modules\TaxSetting\Controllers\Admin\AdminTaxSettingController;

Route::prefix('tax-setting')->group(function () {
    // Main tax setting routes
    Route::get('tax-settings', [AdminTaxSettingController::class, 'index'])->name('tax-settings.index');
    Route::get('tax-settings/create', [AdminTaxSettingController::class, 'create'])->name('tax-settings.create');
    Route::post('tax-settings', [AdminTaxSettingController::class, 'store'])->name('tax-settings.store');
    Route::get('tax-settings/statistics/dashboard', [AdminTaxSettingController::class, 'statistics'])->name('tax-settings.statistics');

    // AJAX and API routes
    Route::post('tax-settings/bulk-action', [AdminTaxSettingController::class, 'bulkAction'])->name('tax-settings.bulk-action');
    Route::post('tax-settings/sync-firebase', [AdminTaxSettingController::class, 'syncFirebase'])->name('tax-settings.sync-firebase');
    Route::post('tax-settings/calculate-tax', [AdminTaxSettingController::class, 'calculateTax'])->name('tax-settings.calculate-tax');

    // Individual tax setting routes (ID-specific)
    Route::get('tax-settings/{id}', [AdminTaxSettingController::class, 'show'])->name('tax-settings.show');
    Route::get('tax-settings/{id}/edit', [AdminTaxSettingController::class, 'edit'])->name('tax-settings.edit');
    Route::put('tax-settings/{id}', [AdminTaxSettingController::class, 'update'])->name('tax-settings.update');
    Route::delete('tax-settings/{id}', [AdminTaxSettingController::class, 'destroy'])->name('tax-settings.destroy');
    Route::patch('tax-settings/{id}/status', [AdminTaxSettingController::class, 'updateStatus'])->name('tax-settings.update-status');

    // Export route
    Route::get('tax-settings/export', [AdminTaxSettingController::class, 'export'])->name('tax-settings.export');

    // Test route
    Route::get('test', function () {
        return 'Tax Setting test route';
    })->name('tax-setting.test');
});
