<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FirebaseAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user is authenticated via Firebase
        if (!session('firebase_user')) {
            Log::warning('Unauthenticated access attempt to: ' . $request->url());
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            
            return redirect()->route('login')->with('error', 'Please log in to continue.');
        }

        // Verify the session has required fields
        $firebaseUser = session('firebase_user');
        if (!isset($firebaseUser['uid']) || !isset($firebaseUser['email'])) {
            Log::warning('Invalid Firebase session data');
            session()->forget('firebase_user');
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Invalid session'], 401);
            }
            
            return redirect()->route('login')->with('error', 'Session expired. Please log in again.');
        }

        // Add user data to request for easy access
        $request->merge(['firebase_user' => $firebaseUser]);

        return $next($request);
    }
}