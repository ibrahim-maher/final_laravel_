<?php

use Illuminate\Support\Facades\Route;

// Remove admin middleware temporarily for testing
Route::middleware(['web'])->group(function () {
    Route::get('/settings', 'AdminController@settings')->name('admin.settings');
    Route::get('/test-firestore', 'AdminController@testFirestore')->name('admin.test-firestore');

    // All user-related routes have been moved to User module
});
