<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Driver\Controllers\DriverController;

Route::prefix('driver')->middleware(['auth'])->group(function () {
    Route::get('/', [DriverController::class, 'index'])->name('driver.index');
    Route::get('/create', [DriverController::class, 'create'])->name('driver.create');
    Route::post('/', [DriverController::class, 'store'])->name('driver.store');
    Route::get('/{id}', [DriverController::class, 'show'])->name('driver.show');
    Route::get('/{id}/edit', [DriverController::class, 'edit'])->name('driver.edit');
});