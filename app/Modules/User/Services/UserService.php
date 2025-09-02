<?php

namespace App\Modules\User\Services;

use App\Services\FirestoreService;
use Illuminate\Support\Facades\Log;

class UserService
{
    protected $firestoreService;
    protected $collection = 'users';

    public function __construct(FirestoreService $firestoreService)
    {
        $this->firestoreService = $firestoreService;
    }

    /**
     * Get all users with optional filters
     */
    public function getAllUsers(array $filters = []): array
    {
        try {
            Log::info('Getting all users with filters', $filters);
            
            $limit = $filters['limit'] ?? 50;
            $users = $this->firestoreService->collection($this->collection)->getAll($limit);
            
            // Sanitize user data
            $users = array_map([$this, 'sanitizeUserData'], $users);
            
            // Apply search filter
            if (!empty($filters['search'])) {
                $search = strtolower($filters['search']);
                $users = array_filter($users, function($user) use ($search) {
                    return stripos($user['name'] ?? '', $search) !== false || 
                           stripos($user['email'] ?? '', $search) !== false ||
                           stripos($user['phone'] ?? '', $search) !== false;
                });
            }

            // Apply status filter
            if (!empty($filters['status'])) {
                $users = array_filter($users, function($user) use ($filters) {
                    return ($user['status'] ?? 'active') === $filters['status'];
                });
            }

            Log::debug('Retrieved users', ['count' => count($users)]);
            return array_values($users); // Re-index array

        } catch (\Exception $e) {
            Log::error('Error getting all users: ' . $e->getMessage(), [
                'filters' => $filters,
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Get user by ID
     */
    public function getUserById(string $id): ?array
    {
        try {
            Log::info('Getting user by ID', ['id' => $id]);
            
            $user = $this->firestoreService->collection($this->collection)->find($id);
            
            if ($user) {
                $user = $this->sanitizeUserData($user);
                Log::debug('User found and sanitized', ['user_id' => $id, 'user_data' => $user]);
            }
            
            return $user;

        } catch (\Exception $e) {
            Log::error('Error getting user by ID: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Create new user
     */
    public function createUser(array $userData): ?array
    {
        try {
            Log::info('Creating new user', ['email' => $userData['email'] ?? 'unknown']);
            
            // Validate required fields
            if (empty($userData['email'])) {
                Log::warning('Cannot create user without email');
                return null;
            }
            
            // Set defaults
            $userData['status'] = $userData['status'] ?? 'active';
            $userData['role'] = $userData['role'] ?? 'user';
            $userData['name'] = $userData['name'] ?? 'New User';
            
            // Add metadata
            $userData['created_at'] = now()->toDateTimeString();
            $userData['updated_at'] = now()->toDateTimeString();
            
            $result = $this->firestoreService->collection($this->collection)->create($userData);
            
            if ($result) {
                $result = $this->sanitizeUserData($result);
                Log::info('User created successfully', ['user_id' => $result['id'] ?? 'unknown']);
            }
            
            return $result;

        } catch (\Exception $e) {
            Log::error('Error creating user: ' . $e->getMessage(), [
                'user_data' => $userData,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Update user
     */
    public function updateUser(string $id, array $userData): bool
    {
        try {
            Log::info('Updating user', ['id' => $id]);
            
            // Add update timestamp
            $userData['updated_at'] = now()->toDateTimeString();
            
            $result = $this->firestoreService->collection($this->collection)->update($id, $userData);
            
            if ($result) {
                Log::info('User updated successfully', ['user_id' => $id]);
            }
            
            return $result;

        } catch (\Exception $e) {
            Log::error('Error updating user: ' . $e->getMessage(), [
                'id' => $id,
                'user_data' => $userData,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Delete user
     */
    public function deleteUser(string $id): bool
    {
        try {
            Log::info('Deleting user', ['id' => $id]);
            
            $result = $this->firestoreService->collection($this->collection)->delete($id);
            
            if ($result) {
                Log::info('User deleted successfully', ['user_id' => $id]);
            }
            
            return $result;

        } catch (\Exception $e) {
            Log::error('Error deleting user: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Update user status
     */
    public function updateUserStatus(string $id, string $status): bool
    {
        try {
            Log::info('Updating user status', ['id' => $id, 'status' => $status]);
            
            $updateData = [
                'status' => $status,
                'updated_at' => now()->toDateTimeString()
            ];
            
            return $this->firestoreService->collection($this->collection)->update($id, $updateData);

        } catch (\Exception $e) {
            Log::error('Error updating user status: ' . $e->getMessage(), [
                'id' => $id,
                'status' => $status,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Get total users count
     */
    public function getTotalUsersCount(): int
    {
        try {
            return $this->firestoreService->collection($this->collection)->count();

        } catch (\Exception $e) {
            Log::error('Error getting total users count: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get user by email
     */
    public function getUserByEmail(string $email): ?array
    {
        try {
            Log::info('Getting user by email', ['email' => $email]);
            
            $users = $this->firestoreService->collection($this->collection)
                ->where('email', '==', $email)
                ->get();
            
            if (!empty($users)) {
                $user = $this->sanitizeUserData($users[0]);
                return $user;
            }
            
            return null;

        } catch (\Exception $e) {
            Log::error('Error getting user by email: ' . $e->getMessage(), [
                'email' => $email,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Check if user exists
     */
    public function userExists(string $id): bool
    {
        try {
            return $this->firestoreService->collection($this->collection)->exists($id);

        } catch (\Exception $e) {
            Log::error('Error checking if user exists: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Get user statistics
     */
    public function getUserStatistics(): array
    {
        try {
            $users = $this->getAllUsers(['limit' => 1000]);
            
            $stats = [
                'total_users' => count($users),
                'active_users' => 0,
                'inactive_users' => 0,
                'premium_users' => 0,
                'admin_users' => 0,
                'users_by_role' => [],
                'users_by_status' => [],
                'recent_registrations' => 0
            ];

            $oneWeekAgo = now()->subWeek();

            foreach ($users as $user) {
                // Count by status
                $status = $user['status'] ?? 'active';
                if ($status === 'active') {
                    $stats['active_users']++;
                } else {
                    $stats['inactive_users']++;
                }

                // Count by role
                $role = $user['role'] ?? 'user';
                $stats['users_by_role'][$role] = ($stats['users_by_role'][$role] ?? 0) + 1;
                
                if ($role === 'premium') {
                    $stats['premium_users']++;
                } elseif ($role === 'admin') {
                    $stats['admin_users']++;
                }

                // Count by status
                $stats['users_by_status'][$status] = ($stats['users_by_status'][$status] ?? 0) + 1;

                // Count recent registrations
                if (isset($user['created_at'])) {
                    try {
                        $createdAt = \Carbon\Carbon::parse($user['created_at']);
                        if ($createdAt->gte($oneWeekAgo)) {
                            $stats['recent_registrations']++;
                        }
                    } catch (\Exception $e) {
                        Log::debug('Could not parse created_at for user statistics', [
                            'user_id' => $user['id'] ?? 'unknown',
                            'created_at' => $user['created_at']
                        ]);
                    }
                }
            }

            Log::debug('User statistics calculated', $stats);
            return $stats;

        } catch (\Exception $e) {
            Log::error('Error getting user statistics: ' . $e->getMessage());
            return [
                'total_users' => 0,
                'active_users' => 0,
                'inactive_users' => 0,
                'premium_users' => 0,
                'admin_users' => 0,
                'users_by_role' => [],
                'users_by_status' => [],
                'recent_registrations' => 0
            ];
        }
    }

    /**
     * Search users
     */
    public function searchUsers(string $query, int $limit = 50): array
    {
        try {
            Log::info('Searching users', ['query' => $query]);
            
            $allUsers = $this->firestoreService->collection($this->collection)->getAll();
            $searchLower = strtolower($query);
            
            // Sanitize users first
            $allUsers = array_map([$this, 'sanitizeUserData'], $allUsers);
            
            $results = array_filter($allUsers, function($user) use ($searchLower) {
                return stripos($user['name'] ?? '', $searchLower) !== false || 
                       stripos($user['email'] ?? '', $searchLower) !== false ||
                       stripos($user['phone'] ?? '', $searchLower) !== false;
            });

            return array_slice(array_values($results), 0, $limit);

        } catch (\Exception $e) {
            Log::error('Error searching users: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get users by role
     */
    public function getUsersByRole(string $role): array
    {
        try {
            Log::info('Getting users by role', ['role' => $role]);
            
            $allUsers = $this->firestoreService->collection($this->collection)->getAll();
            $allUsers = array_map([$this, 'sanitizeUserData'], $allUsers);
            
            return array_filter($allUsers, function($user) use ($role) {
                return ($user['role'] ?? 'user') === $role;
            });

        } catch (\Exception $e) {
            Log::error('Error getting users by role: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get users by status
     */
    public function getUsersByStatus(string $status): array
    {
        try {
            Log::info('Getting users by status', ['status' => $status]);
            
            $allUsers = $this->firestoreService->collection($this->collection)->getAll();
            $allUsers = array_map([$this, 'sanitizeUserData'], $allUsers);
            
            return array_filter($allUsers, function($user) use ($status) {
                return ($user['status'] ?? 'active') === $status;
            });

        } catch (\Exception $e) {
            Log::error('Error getting users by status: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Sync Firebase users
     */
    public function syncFirebaseUsers(): array
    {
        try {
            Log::info('Starting Firebase users sync');

            // For this implementation, we'll create some sample users
            // In a real implementation, you would fetch from Firebase Auth API
            $sampleUsers = $this->generateSampleUsers();
            
            $syncedCount = 0;
            $failedCount = 0;

            foreach ($sampleUsers as $userData) {
                try {
                    // Check if user already exists
                    $existingUser = $this->getUserByEmail($userData['email']);

                    if ($existingUser) {
                        // Update existing user
                        $updateData = array_merge($userData, ['updated_at' => now()->toDateTimeString()]);
                        unset($updateData['created_at']); // Don't overwrite creation date
                        
                        if ($this->updateUser($existingUser['id'], $updateData)) {
                            $syncedCount++;
                        } else {
                            $failedCount++;
                        }
                    } else {
                        // Create new user
                        if ($this->createUser($userData)) {
                            $syncedCount++;
                        } else {
                            $failedCount++;
                        }
                    }

                } catch (\Exception $e) {
                    Log::warning('Failed to sync user', ['email' => $userData['email'], 'error' => $e->getMessage()]);
                    $failedCount++;
                }
            }

            Log::info('Firebase users sync completed', [
                'synced' => $syncedCount,
                'failed' => $failedCount
            ]);

            return [
                'success' => true,
                'synced_count' => $syncedCount,
                'failed_count' => $failedCount,
                'message' => "Synced {$syncedCount} users successfully"
            ];

        } catch (\Exception $e) {
            Log::error('Firebase users sync failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Perform bulk action on multiple users
     */
    public function performBulkAction(string $action, array $userIds): array
    {
        try {
            Log::info('Performing bulk action', ['action' => $action, 'user_count' => count($userIds)]);

            $processedCount = 0;
            $failedCount = 0;
            $errors = [];

            foreach ($userIds as $userId) {
                try {
                    $success = false;

                    switch ($action) {
                        case 'activate':
                            $success = $this->updateUserStatus($userId, 'active');
                            break;
                        case 'deactivate':
                            $success = $this->updateUserStatus($userId, 'inactive');
                            break;
                        case 'delete':
                            $success = $this->deleteUser($userId);
                            break;
                    }

                    if ($success) {
                        $processedCount++;
                    } else {
                        $failedCount++;
                        $errors[] = "Failed to {$action} user {$userId}";
                    }

                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = "Error with user {$userId}: " . $e->getMessage();
                    Log::warning('Bulk action failed for user', ['user_id' => $userId, 'action' => $action, 'error' => $e->getMessage()]);
                }
            }

            Log::info('Bulk action completed', [
                'action' => $action,
                'processed' => $processedCount,
                'failed' => $failedCount
            ]);

            return [
                'success' => true,
                'processed_count' => $processedCount,
                'failed_count' => $failedCount,
                'errors' => $errors
            ];

        } catch (\Exception $e) {
            Log::error('Bulk action exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Bulk action failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Sanitize user data to ensure consistent format
     */
    private function sanitizeUserData(array $user): array
    {
        // Ensure required fields have default values
        $sanitized = [
            'id' => $user['id'] ?? 'unknown',
            'name' => !empty($user['name']) ? trim($user['name']) : 'Unknown User',
            'email' => !empty($user['email']) ? trim(strtolower($user['email'])) : 'No Email',
            'phone' => !empty($user['phone']) ? trim($user['phone']) : null,
            'address' => !empty($user['address']) ? trim($user['address']) : null,
            'status' => !empty($user['status']) ? $user['status'] : 'active',
            'role' => !empty($user['role']) ? $user['role'] : 'user',
            'created_at' => $user['created_at'] ?? null,
            'updated_at' => $user['updated_at'] ?? null,
            'created_by' => $user['created_by'] ?? null,
            'updated_by' => $user['updated_by'] ?? null,
        ];

        // Log data before and after sanitization for debugging
        if (($sanitized['name'] === 'Unknown User' || $sanitized['email'] === 'No Email') && !empty($user)) {
            Log::warning('User data sanitization resulted in default values', [
                'original' => $user,
                'sanitized' => $sanitized
            ]);
        }

        return $sanitized;
    }

    /**
     * Generate sample users for sync demonstration
     */
    private function generateSampleUsers(): array
    {
        return [
            [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'phone' => '+1234567890',
                'status' => 'active',
                'role' => 'user',
                'created_at' => now()->subDays(30)->toDateTimeString(),
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane.smith@example.com',
                'phone' => '+1234567891',
                'status' => 'active',
                'role' => 'premium',
                'created_at' => now()->subDays(15)->toDateTimeString(),
            ],
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'phone' => '+1234567892',
                'status' => 'active',
                'role' => 'admin',
                'created_at' => now()->subDays(60)->toDateTimeString(),
            ],
            [
                'name' => 'Test User',
                'email' => 'test.user@example.com',
                'phone' => '+1234567893',
                'status' => 'inactive',
                'role' => 'user',
                'created_at' => now()->subDays(7)->toDateTimeString(),
            ],
            [
                'name' => 'Sarah Wilson',
                'email' => 'sarah.wilson@example.com',
                'phone' => '+1234567894',
                'status' => 'active',
                'role' => 'user',
                'created_at' => now()->subDays(3)->toDateTimeString(),
            ]
        ];
    }
}