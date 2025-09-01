<?php

use Illuminate\Support\Facades\Route;

// User routes
Route::get('/profile', 'UserController@profile')->name('user.profile');
Route::post('/profile', 'UserController@updateProfile')->name('user.profile.update');
Route::get('/documents', 'UserController@documents')->name('user.documents');
Route::get('/settings', 'UserController@settings')->name('user.settings');