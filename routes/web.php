<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FirebaseAuthController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [FirebaseAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [FirebaseAuthController::class, 'login']);

Route::get('/register', [FirebaseAuthController::class, 'showRegister'])->name('register');
Route::post('/register', [FirebaseAuthController::class, 'register']);

Route::post('/logout', [FirebaseAuthController::class, 'logout'])->name('logout');

Route::get('/dashboard', [FirebaseAuthController::class, 'dashboard'])->name('dashboard');
Route::get('/user/profile', function () {
    if (!session('firebase_user')) {
        return redirect()->route('login');
    }
    return redirect()->route('admin.dashboard'); // Temporary redirect to admin
})->name('user.profile');
