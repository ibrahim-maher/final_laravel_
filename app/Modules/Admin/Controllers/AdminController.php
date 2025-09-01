<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\FirestoreService;

class AdminController extends Controller
{
    protected $firestoreService;

    public function __construct()
    {
        $this->firestoreService = new FirestoreService();
    }

    /**
     * Admin dashboard
     */
    public function dashboard()
    {
        if (!session('firebase_user')) {
            Log::warning('Unauthorized admin dashboard access attempt');
            return redirect()->route('login');
        }

        Log::info('Admin dashboard accessed by: ' . session('firebase_user.email'));
        
        // Get system stats with better error handling
        $stats = [
            'total_users' => 0,
            'total_documents' => 0,
            'total_drivers' => 0,
            'active_sessions' => 0,
        ];

        try {
            $stats['total_users'] = $this->firestoreService->collection('users')->count();
            Log::debug('Successfully got users count: ' . $stats['total_users']);
        } catch (\Exception $e) {
            Log::warning('Error getting users count: ' . $e->getMessage());
        }

        try {
            $stats['total_documents'] = $this->firestoreService->collection('documents')->count();
            Log::debug('Successfully got documents count: ' . $stats['total_documents']);
        } catch (\Exception $e) {
            Log::warning('Error getting documents count: ' . $e->getMessage());
        }

        try {
            $stats['total_drivers'] = $this->firestoreService->collection('drivers')->count();
            Log::debug('Successfully got drivers count: ' . $stats['total_drivers']);
        } catch (\Exception $e) {
            Log::warning('Error getting drivers count: ' . $e->getMessage());
        }

        try {
            $stats['active_sessions'] = $this->firestoreService->collection('user_sessions')->count();
            Log::debug('Successfully got sessions count: ' . $stats['active_sessions']);
        } catch (\Exception $e) {
            Log::warning('Error getting sessions count: ' . $e->getMessage());
        }
        
        return view('admin::dashboard', compact('stats'));
    }

    /**
     * Admin users management
     */
    public function users()
    {
        if (!session('firebase_user')) {
            return redirect()->route('login');
        }

        Log::info('Admin users page accessed by: ' . session('firebase_user.email'));
        
        try {
            $users = $this->firestoreService->collection('admin_users')->getAll();
            Log::debug('Successfully retrieved ' . count($users) . ' admin users');
        } catch (\Exception $e) {
            Log::error('Error getting admin users: ' . $e->getMessage());
            $users = [];
        }
        
        return view('admin::users', compact('users'));
    }

    /**
     * System settings
     */
    public function settings()
    {
        if (!session('firebase_user')) {
            return redirect()->route('login');
        }

        Log::info('Admin settings accessed by: ' . session('firebase_user.email'));
        
        return view('admin::settings');
    }

    /**
     * Test Firestore connection
     */
    public function testFirestore()
    {
        if (!session('firebase_user')) {
            return redirect()->route('login');
        }

        try {
            // Test creating a document
            $testData = [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'test_timestamp' => now()->toDateTimeString()
            ];

            $result = $this->firestoreService->collection('test_collection')->create($testData);
            
            if ($result) {
                Log::info('Firestore test successful', ['document_id' => $result['id']]);
                return response()->json([
                    'success' => true,
                    'message' => 'Firestore connection successful',
                    'document_id' => $result['id']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create test document'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Firestore test failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Firestore test failed: ' . $e->getMessage()
            ]);
        }
    }
}