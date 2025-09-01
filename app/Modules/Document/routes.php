<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Document\Controllers\DocumentController;

Route::prefix('document')->middleware(['auth'])->group(function () {
    Route::get('/', [DocumentController::class, 'index'])->name('document.index');
    Route::get('/create', [DocumentController::class, 'create'])->name('document.create');
    Route::post('/', [DocumentController::class, 'store'])->name('document.store');
    Route::get('/{id}', [DocumentController::class, 'show'])->name('document.show');
});