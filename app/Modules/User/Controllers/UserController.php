<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Modules\User\Services\UserService;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        if (!session('firebase_user')) {
            return redirect()->route('login');
        }

        Log::info('User index accessed by: ' . session('firebase_user.email'));

        try {
            $filters = [
                'search' => $request->get('search'),
                'status' => $request->get('status'),
                'limit' => $request->get('limit', 50)
            ];

            $users = $this->userService->getAllUsers($filters);
            $totalUsers = $this->userService->getTotalUsersCount();
            
            Log::info('Retrieved users list', ['count' => count($users), 'total' => $totalUsers]);
            
        } catch (\Exception $e) {
            Log::error('Error getting users list: ' . $e->getMessage());
            $users = [];
            $totalUsers = 0;
        }

        return view('user::index', compact('users', 'totalUsers', 'filters'));
    }

    /**
     * Show the form for creating a new user
     */
    public function create()
    {
        if (!session('firebase_user')) {
            return redirect()->route('login');
        }

        Log::info('User create form accessed by: ' . session('firebase_user.email'));
        
        return view('user::create');
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        if (!session('firebase_user')) {
            return redirect()->route('login');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
            'role' => 'required|in:user,premium,admin',
        ]);

        try {
            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'status' => $request->status,
                'role' => $request->role,
                'created_by' => session('firebase_user.uid'),
            ];

            $result = $this->userService->createUser($userData);

            if ($result) {
                Log::info('User created successfully', ['email' => $request->email]);
                return redirect()->route('user.index')->with('success', 'User created successfully!');
            } else {
                return redirect()->back()->with('error', 'Failed to create user.')->withInput();
            }

        } catch (\Exception $e) {
            Log::error('Error creating user: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error creating user: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified user
     */
    public function show(string $id)
    {
        if (!session('firebase_user')) {
            return redirect()->route('login');
        }

        try {
            $user = $this->userService->getUserById($id);
            
            if (!$user) {
                return redirect()->route('user.index')->with('error', 'User not found.');
            }

            Log::info('User details viewed', ['user_id' => $id]);
            
            return view('user::show', compact('user'));
            
        } catch (\Exception $e) {
            Log::error('Error getting user details: ' . $e->getMessage());
            return redirect()->route('user.index')->with('error', 'Error loading user details.');
        }
    }

    /**
     * Show the form for editing the specified user
     */
    public function edit(string $id)
    {
        if (!session('firebase_user')) {
            return redirect()->route('login');
        }

        try {
            $user = $this->userService->getUserById($id);
            
            if (!$user) {
                return redirect()->route('user.index')->with('error', 'User not found.');
            }

            return view('user::edit', compact('user'));
            
        } catch (\Exception $e) {
            Log::error('Error getting user for edit: ' . $e->getMessage());
            return redirect()->route('user.index')->with('error', 'Error loading user for editing.');
        }
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, string $id)
    {
        if (!session('firebase_user')) {
            return redirect()->route('login');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
            'role' => 'required|in:user,premium,admin',
        ]);

        try {
            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'status' => $request->status,
                'role' => $request->role,
                'updated_by' => session('firebase_user.uid'),
            ];

            $result = $this->userService->updateUser($id, $userData);

            if ($result) {
                Log::info('User updated successfully', ['user_id' => $id]);
                return redirect()->route('user.index')->with('success', 'User updated successfully!');
            } else {
                return redirect()->back()->with('error', 'Failed to update user.')->withInput();
            }

        } catch (\Exception $e) {
            Log::error('Error updating user: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error updating user: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified user
     */
    public function destroy(string $id)
    {
        if (!session('firebase_user')) {
            return redirect()->route('login');
        }

        try {
            $result = $this->userService->deleteUser($id);

            if ($result) {
                Log::info('User deleted successfully', ['user_id' => $id]);
                return redirect()->route('user.index')->with('success', 'User deleted successfully!');
            } else {
                return redirect()->back()->with('error', 'Failed to delete user.');
            }

        } catch (\Exception $e) {
            Log::error('Error deleting user: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error deleting user: ' . $e->getMessage());
        }
    }

    /**
     * Update user status (AJAX)
     */
    public function updateStatus(Request $request, string $id)
    {
        if (!session('firebase_user')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'status' => 'required|in:active,inactive'
        ]);

        try {
            $result = $this->userService->updateUserStatus($id, $request->status);

            if ($result) {
                Log::info('User status updated', ['user_id' => $id, 'status' => $request->status]);
                return response()->json(['success' => true, 'message' => 'Status updated successfully']);
            } else {
                return response()->json(['success' => false, 'message' => 'Failed to update status']);
            }

        } catch (\Exception $e) {
            Log::error('Error updating user status: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

 
    /**
     * Get user statistics
     */
    public function statistics()
    {
        if (!session('firebase_user')) {
            return redirect()->route('login');
        }

        try {
            $stats = $this->userService->getUserStatistics();
            
            return view('user::statistics', compact('stats'));

        } catch (\Exception $e) {
            Log::error('Error getting user statistics: ' . $e->getMessage());
            return redirect()->route('user.index')->with('error', 'Error loading statistics.');
        }
    }

     public function syncFirebaseUsers(Request $request)
    {
        if (!session('firebase_user')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            Log::info('Firebase users sync initiated by: ' . session('firebase_user.email'));

            $result = $this->userService->syncFirebaseUsers();

            if ($result['success']) {
                Log::info('Firebase users synced successfully', ['synced_count' => $result['synced_count']]);
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => "Successfully synced {$result['synced_count']} users from Firebase",
                        'synced_count' => $result['synced_count']
                    ]);
                }
                
                return redirect()->route('user.index')->with('success', "Successfully synced {$result['synced_count']} users from Firebase");
            } else {
                Log::error('Firebase users sync failed', ['error' => $result['message']]);
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $result['message']
                    ]);
                }
                
                return redirect()->route('user.index')->with('error', 'Failed to sync users: ' . $result['message']);
            }

        } catch (\Exception $e) {
            Log::error('Firebase users sync exception: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error syncing users: ' . $e->getMessage()
                ]);
            }
            
            return redirect()->route('user.index')->with('error', 'Error syncing users: ' . $e->getMessage());
        }
    }

    /**
     * Perform bulk actions on selected users
     */
    public function bulkAction(Request $request)
    {
        if (!session('firebase_user')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'required|string'
        ]);

        try {
            $action = $request->action;
            $userIds = $request->user_ids;
            
            Log::info('Bulk action initiated', [
                'action' => $action,
                'user_count' => count($userIds),
                'initiated_by' => session('firebase_user.email')
            ]);

            $result = $this->userService->performBulkAction($action, $userIds);

            if ($result['success']) {
                $message = $this->getBulkActionMessage($action, $result['processed_count'], $result['failed_count']);
                
                Log::info('Bulk action completed', [
                    'action' => $action,
                    'processed' => $result['processed_count'],
                    'failed' => $result['failed_count']
                ]);

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'processed_count' => $result['processed_count'],
                    'failed_count' => $result['failed_count']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Bulk action exception: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error performing bulk action: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Handle AJAX delete request for better UX
     */
    public function destroyAjax(string $id)
    {
        if (!session('firebase_user')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            // Get user info before deletion for logging
            $user = $this->userService->getUserById($id);
            $userEmail = $user['email'] ?? 'Unknown';

            $result = $this->userService->deleteUser($id);

            if ($result) {
                Log::info('User deleted successfully via AJAX', ['user_id' => $id, 'user_email' => $userEmail]);
                return response()->json([
                    'success' => true,
                    'message' => 'User deleted successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete user'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error deleting user via AJAX: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting user: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get bulk action success message
     */
    private function getBulkActionMessage(string $action, int $processed, int $failed): string
    {
        $actionPast = [
            'activate' => 'activated',
            'deactivate' => 'deactivated',
            'delete' => 'deleted'
        ][$action];

        $message = "Successfully {$actionPast} {$processed} users";
        
        if ($failed > 0) {
            $message .= " ({$failed} failed)";
        }
        
        return $message . ".";
    }

    /**
     * Export users with filters (enhanced version)
     */
    public function export(Request $request)
    {
        if (!session('firebase_user')) {
            return redirect()->route('login');
        }

        try {
            // Get filters from request
            $filters = [
                'search' => $request->get('search'),
                'status' => $request->get('status'),
                'limit' => $request->get('limit', 1000)
            ];

            $users = $this->userService->getAllUsers($filters);
            $format = $request->get('format', 'csv');

            $filename = 'users_export_' . now()->format('Y_m_d_H_i_s') . '.' . $format;
            
            if ($format === 'csv') {
                $headers = [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => "attachment; filename=\"$filename\"",
                ];

                $callback = function() use ($users) {
                    $file = fopen('php://output', 'w');
                    
                    // Add CSV headers
                    fputcsv($file, [
                        'ID', 'Name', 'Email', 'Phone', 'Address', 
                        'Status', 'Role', 'Created At', 'Updated At'
                    ]);

                    foreach ($users as $user) {
                        fputcsv($file, [
                            $user['id'] ?? '',
                            $user['name'] ?? '',
                            $user['email'] ?? '',
                            $user['phone'] ?? '',
                            $user['address'] ?? '',
                            $user['status'] ?? 'active',
                            $user['role'] ?? 'user',
                            $user['created_at'] ?? '',
                            $user['updated_at'] ?? '',
                        ]);
                    }

                    fclose($file);
                };

                Log::info('Users exported to CSV', [
                    'user_count' => count($users),
                    'exported_by' => session('firebase_user.email'),
                    'filters' => $filters
                ]);

                return response()->stream($callback, 200, $headers);
            }

            // Default fallback
            return redirect()->route('user.index')->with('error', 'Unsupported export format.');

        } catch (\Exception $e) {
            Log::error('Error exporting users: ' . $e->getMessage());
            return redirect()->route('user.index')->with('error', 'Error exporting users: ' . $e->getMessage());
        }
    }
}