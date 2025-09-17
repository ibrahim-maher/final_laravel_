<?php

// Create a dedicated testing command
// File: app/Console/Commands/TestFirebaseSync.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FirebaseSyncService;
use App\Modules\Driver\Models\Driver;
use App\Models\FirebaseSyncLog;

class TestFirebaseSync extends Command
{
    protected $signature = 'firebase:test-sync {--cleanup : Clean up test data after testing}';
    protected $description = 'Test Firebase synchronization functionality';

    protected $syncService;
    protected $testDrivers = [];

    public function __construct(FirebaseSyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    public function handle()
    {
        $this->info('🔥 Starting Firebase Sync Tests...');
        $this->newLine();

        try {
            // Test 1: Firebase Connection
            $this->testConnection();
            
            // Test 2: Create and Sync Driver
            $this->testCreateDriver();
            
            // Test 3: Update Driver
            $this->testUpdateDriver();
            
            // Test 4: Check Firebase Data
            $this->testFirebaseData();
            
            // Test 5: Sync Statistics
            $this->testSyncStatistics();
            
            // Test 6: Error Handling
            $this->testErrorHandling();
            
            // Clean up if requested
            if ($this->option('cleanup')) {
                $this->cleanupTestData();
            }
            
            $this->newLine();
            $this->info('✅ All Firebase sync tests completed!');
            
        } catch (\Exception $e) {
            $this->error('❌ Test failed: ' . $e->getMessage());
            $this->line('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }

    protected function testConnection()
    {
        $this->info('1. Testing Firebase Connection...');
        
        $healthCheck = $this->syncService->testConnection();
        
        if ($healthCheck['status'] === 'healthy') {
            $this->line('   ✅ Firebase connection successful');
        } else {
            $this->error('   ❌ Firebase connection failed: ' . $healthCheck['message']);
            throw new \Exception('Firebase connection test failed');
        }
    }

    protected function testCreateDriver()
    {
        $this->info('2. Testing Driver Creation and Sync...');
        
        $testDriver = Driver::create([
            'firebase_uid' => 'test_driver_' . time() . '_' . rand(1000, 9999),
            'name' => 'Test Driver ' . rand(1, 100),
            'email' => 'test' . time() . '@example.com',
            'phone' => '555-' . rand(1000, 9999),
            'license_number' => 'TEST' . rand(10000, 99999),
            'license_expiry' => now()->addYear()->format('Y-m-d'),
            'status' => 'pending',
            'verification_status' => 'pending',
            'availability_status' => 'offline'
        ]);
        
        $this->testDrivers[] = $testDriver;
        
        // Wait for async job or sync manually
        $this->line('   📤 Syncing driver to Firebase...');
        $syncResult = $this->syncService->syncModel($testDriver, 'create');
        
        if ($syncResult) {
            $this->line('   ✅ Driver created and synced successfully');
            $this->line('   📋 Driver ID: ' . $testDriver->firebase_uid);
        } else {
            $this->error('   ❌ Driver sync failed');
            throw new \Exception('Driver creation sync failed');
        }
    }

    protected function testUpdateDriver()
    {
        $this->info('3. Testing Driver Update and Sync...');
        
        if (empty($this->testDrivers)) {
            throw new \Exception('No test drivers available for update test');
        }
        
        $driver = $this->testDrivers[0];
        $originalName = $driver->name;
        $newName = 'Updated Test Driver ' . time();
        
        $driver->update(['name' => $newName]);
        
        $this->line('   📝 Updated driver name from "' . $originalName . '" to "' . $newName . '"');
        
        // Sync the update
        $syncResult = $this->syncService->syncModel($driver, 'update');
        
        if ($syncResult) {
            $this->line('   ✅ Driver update synced successfully');
        } else {
            $this->error('   ❌ Driver update sync failed');
            throw new \Exception('Driver update sync failed');
        }
    }

    protected function testFirebaseData()
    {
        $this->info('4. Testing Firebase Data Retrieval...');
        
        if (empty($this->testDrivers)) {
            throw new \Exception('No test drivers available for data test');
        }
        
        $driver = $this->testDrivers[0];
        
        // Check if document exists
        $exists = $this->syncService->documentExists('drivers', $driver->firebase_uid);
        
        if ($exists) {
            $this->line('   ✅ Document exists in Firebase');
        } else {
            $this->error('   ❌ Document not found in Firebase');
            throw new \Exception('Document not found in Firebase');
        }
        
        // Get document data
        $firebaseData = $this->syncService->getDocument('drivers', $driver->firebase_uid);
        
        if ($firebaseData) {
            $this->line('   ✅ Successfully retrieved document data');
            $this->line('   📊 Firebase data keys: ' . implode(', ', array_keys($firebaseData)));
            
            // Verify data integrity
            if (isset($firebaseData['name']) && $firebaseData['name'] === $driver->name) {
                $this->line('   ✅ Data integrity check passed');
            } else {
                $this->error('   ❌ Data integrity check failed');
                $this->line('   Expected name: ' . $driver->name);
                $this->line('   Firebase name: ' . ($firebaseData['name'] ?? 'null'));
            }
        } else {
            $this->error('   ❌ Failed to retrieve document data');
            throw new \Exception('Failed to retrieve Firebase document');
        }
    }

    protected function testSyncStatistics()
    {
        $this->info('5. Testing Sync Statistics...');
        
        $stats = $this->syncService->getSyncStats();
        
        $this->line('   📊 Total syncs: ' . $stats['total_syncs']);
        $this->line('   ✅ Successful syncs: ' . $stats['successful_syncs']);
        $this->line('   ❌ Failed syncs: ' . $stats['failed_syncs']);
        $this->line('   📈 Success rate: ' . $stats['success_rate'] . '%');
        
        if ($stats['total_syncs'] > 0) {
            $this->line('   ✅ Sync statistics retrieved successfully');
        } else {
            $this->warn('   ⚠️  No sync records found (this might be normal for first run)');
        }
    }

    protected function testErrorHandling()
    {
        $this->info('6. Testing Error Handling...');
        
        // Test with invalid document ID
        $exists = $this->syncService->documentExists('drivers', 'invalid_document_id');
        
        if (!$exists) {
            $this->line('   ✅ Correctly handles non-existent documents');
        } else {
            $this->error('   ❌ Error handling test failed');
        }
        
        // Check recent failures
        $failures = $this->syncService->getRecentFailures(5);
        $this->line('   📋 Recent failures count: ' . count($failures));
    }

    protected function cleanupTestData()
    {
        $this->info('🧹 Cleaning up test data...');
        
        foreach ($this->testDrivers as $driver) {
            try {
                // Delete from Firebase
                $this->syncService->syncModel($driver, 'delete');
                
                // Delete from local database
                $driver->delete();
                
                $this->line('   🗑️  Deleted test driver: ' . $driver->firebase_uid);
            } catch (\Exception $e) {
                $this->warn('   ⚠️  Failed to delete driver ' . $driver->firebase_uid . ': ' . $e->getMessage());
            }
        }
        
        $this->line('   ✅ Cleanup completed');
    }
}

// Register the command in app/Console/Kernel.php
// protected $commands = [
//     \App\Console\Commands\TestFirebaseSync::class,
// ];
