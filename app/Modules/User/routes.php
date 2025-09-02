<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    // User CRUD routes
    Route::get('/', 'UserController@index')->name('user.index');
    Route::get('/create', 'UserController@create')->name('user.create');
    Route::post('/', 'UserController@store')->name('user.store');
    Route::get('/{id}', 'UserController@show')->name('user.show');
    Route::get('/{id}/edit', 'UserController@edit')->name('user.edit');
    Route::put('/{id}', 'UserController@update')->name('user.update');
    Route::delete('/{id}', 'UserController@destroy')->name('user.destroy');
    
    // AJAX and API routes
    Route::patch('/{id}/status', 'UserController@updateStatus')->name('user.update-status');
    Route::delete('/ajax/{id}', 'UserController@destroyAjax')->name('user.destroy.ajax');
    
    // Bulk operations
    Route::post('/bulk-action', 'UserController@bulkAction')->name('user.bulk-action');
    Route::post('/sync', 'UserController@syncFirebaseUsers')->name('user.sync');
    


    // Export and statistics
    Route::get('/export/csv', 'UserController@export')->name('user.export');
    Route::get('/stats/overview', 'UserController@statistics')->name('user.statistics');
});