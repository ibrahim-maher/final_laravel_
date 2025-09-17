<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Coupon\Controllers\Admin\AdminCouponController;

Route::prefix('coupon')->group(function () {
    // Main coupon routes (these match your current route names)
    Route::get('coupons', [AdminCouponController::class, 'index'])->name('coupons.index');
    Route::get('coupons/create', [AdminCouponController::class, 'create'])->name('coupons.create');
    Route::post('coupons', [AdminCouponController::class, 'store'])->name('coupons.store');
    Route::get('coupons/statistics/dashboard', [AdminCouponController::class, 'statistics'])->name('coupons.statistics');
    Route::get('coupons/export/csv', [AdminCouponController::class, 'export'])->name('coupons.export');
    
    // AJAX and API routes
    Route::post('coupons/bulk-create', [AdminCouponController::class, 'bulkCreate'])->name('coupons.bulk-create');
    Route::post('coupons/bulk-action', [AdminCouponController::class, 'bulkAction'])->name('coupons.bulk-action');
    Route::post('coupons/sync-firebase', [AdminCouponController::class, 'syncFirebase'])->name('coupons.sync-firebase');
    Route::post('coupons/validate', [AdminCouponController::class, 'validateCoupon'])->name('coupons.validate');
    
    // Individual coupon routes (code-specific)
    Route::get('coupons/{code}', [AdminCouponController::class, 'show'])->name('coupons.show');
    Route::get('coupons/{code}/edit', [AdminCouponController::class, 'edit'])->name('coupons.edit');
    Route::put('coupons/{code}', [AdminCouponController::class, 'update'])->name('coupons.update');
    Route::delete('coupons/{code}', [AdminCouponController::class, 'destroy'])->name('coupons.destroy');
    Route::patch('coupons/{code}/status', [AdminCouponController::class, 'updateStatus'])->name('coupons.update-status');
    
    // Test route (keep if you have it)
    Route::get('test', function() {
        return 'Coupon test route';
    })->name('coupon.test');
});
    // ADD THESE MISSING ROUTES:
    Route::patch('coupons/{code}/disable', [AdminCouponController::class, 'disable'])->name('coupons.disable');
    Route::patch('coupons/{code}/enable', [AdminCouponController::class, 'enable'])->name('coupons.enable');
    Route::post('coupons/{code}/force-sync', [AdminCouponController::class, 'forceSync'])->name('coupons.force-sync');
    Route::get('coupons/{code}/duplicate', [AdminCouponController::class, 'duplicate'])->name('coupons.duplicate');
    Route::get('coupons/{code}/analytics', [AdminCouponController::class, 'analytics'])->name('coupons.analytics');
    