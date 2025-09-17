<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Core\Controllers\CoreController;

    Route::get('/settings', [CoreController::class, 'settings'])->name('core.settings');
    Route::put('/settings', [CoreController::class, 'updateSettings'])->name('core.settings.update');
    Route::get('/system-info', [CoreController::class, 'systemInfo'])->name('core.system-info');
