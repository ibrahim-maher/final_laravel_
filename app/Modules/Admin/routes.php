<?php

use Illuminate\Support\Facades\Route;

// Remove admin middleware temporarily for testing
Route::middleware(['web'])->group(function () {
    Route::get('/dashboard', 'AdminController@dashboard')->name('admin.dashboard');
    Route::get('/users', 'AdminController@users')->name('admin.users');
    Route::get('/settings', 'AdminController@settings')->name('admin.settings');
    
    // API routes for admin
    Route::post('/users/create', 'AdminController@createUser')->name('admin.users.create');
    Route::patch('/users/{userId}/status', 'AdminController@updateUserStatus')->name('admin.users.update-status');
    Route::get('/users/{userId}', 'AdminController@getUser')->name('admin.users.get');
    Route::put('/users/{userId}', 'AdminController@updateUser')->name('admin.users.update');
    Route::delete('/users/{userId}', 'AdminController@deleteUser')->name('admin.users.delete');
});
