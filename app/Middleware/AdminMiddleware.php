<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is logged in
        if (!session('firebase_user')) {
            Log::warning('Unauthorized admin access attempt - not logged in');
            return redirect()->route('login')->with('error', 'Please log in to access admin area.');
        }

        // For now, let's allow any logged-in user to access admin (for testing)
        // You can implement proper admin checking later
        $firebaseUser = session('firebase_user');
        Log::info('Admin access granted to: ' . $firebaseUser['email']);

