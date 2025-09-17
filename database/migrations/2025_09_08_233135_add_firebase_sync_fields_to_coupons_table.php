<?php
// database/migrations/2024_01_15_000000_add_firebase_sync_fields_safely.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tables that need Firebase sync fields
        $tables = [
            'coupons',
            'drivers', 
            'vehicles',
            'driver_documents',
            'driver_activities'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                $this->addMissingFirebaseSyncFields($tableName);
                $this->addPerformanceIndexes($tableName);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['coupons', 'drivers', 'vehicles', 'driver_documents', 'driver_activities'];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                $this->removeSyncFields($tableName);
            }
        }
    }

    /**
     * Add missing Firebase sync fields to a table
     */
    private function addMissingFirebaseSyncFields(string $tableName): void
    {
        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            // Only add firebase_synced if it doesn't exist
            if (!Schema::hasColumn($tableName, 'firebase_synced')) {
                $table->boolean('firebase_synced')->default(false);
            }
            
            // Only add firebase_synced_at if it doesn't exist
            if (!Schema::hasColumn($tableName, 'firebase_synced_at')) {
                $table->timestamp('firebase_synced_at')->nullable();
            }
            
            // Add sync status field if it doesn't exist
            if (!Schema::hasColumn($tableName, 'firebase_sync_status')) {
                $table->string('firebase_sync_status', 50)->default('pending');
            }
            
            // Add error tracking if it doesn't exist
            if (!Schema::hasColumn($tableName, 'firebase_sync_error')) {
                $table->text('firebase_sync_error')->nullable();
            }
            
            // Add retry attempts if it doesn't exist
            if (!Schema::hasColumn($tableName, 'firebase_sync_attempts')) {
                $table->integer('firebase_sync_attempts')->default(0);
            }
            
            // Add last attempt timestamp if it doesn't exist
            if (!Schema::hasColumn($tableName, 'firebase_last_attempt_at')) {
                $table->timestamp('firebase_last_attempt_at')->nullable();
            }
        });
    }

    /**
     * Add performance indexes to a table
     */
    private function addPerformanceIndexes(string $tableName): void
    {
        try {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $indexes = $this->getIndexesForTable($tableName);
                
                foreach ($indexes as $indexName => $columns) {
                    if (!$this->indexExists($tableName, $indexName)) {
                        try {
                            $table->index($columns, $indexName);
                        } catch (\Exception $e) {
                            // Index might already exist with different name
                        }
                    }
                }
            });
        } catch (\Exception $e) {
            // Continue if indexing fails
        }
    }

    /**
     * Remove sync fields from a table
     */
    private function removeSyncFields(string $tableName): void
    {
        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            $columns = [
                'firebase_sync_status',
                'firebase_sync_error',
                'firebase_sync_attempts', 
                'firebase_last_attempt_at'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn($tableName, $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Get indexes for each table
     */
    private function getIndexesForTable(string $tableName): array
    {
        $commonIndexes = [
            "{$tableName}_firebase_synced_index" => 'firebase_synced',
            "{$tableName}_sync_status_index" => 'firebase_sync_status',
        ];

        $specificIndexes = [
            'coupons' => [
                'coupons_status_expires_index' => ['status', 'expires_at'],
                'coupons_type_status_index' => ['coupon_type', 'status'],
                'coupons_discount_type_index' => 'discount_type',
                'coupons_used_count_index' => 'used_count',
            ],
            'drivers' => [
                'drivers_status_verification_index' => ['status', 'verification_status'],
                'drivers_availability_index' => 'availability_status',
                'drivers_rating_index' => 'rating',
            ],
            'vehicles' => [
                'vehicles_driver_primary_index' => ['driver_firebase_uid', 'is_primary'],
                'vehicles_status_verification_index' => ['status', 'verification_status'],
                'vehicles_type_index' => 'vehicle_type',
            ],
            'driver_documents' => [
                'driver_docs_type_status_index' => ['document_type', 'verification_status'],
                'driver_docs_expiry_index' => 'expiry_date',
            ],
            'driver_activities' => [
                'driver_activities_type_index' => 'activity_type',
                'driver_activities_status_index' => 'status',
                'driver_activities_priority_index' => 'priority',
            ]
        ];

        return array_merge($commonIndexes, $specificIndexes[$tableName] ?? []);
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $indexes = Schema::getConnection()
                            ->getDoctrineSchemaManager()
                            ->listTableIndexes($table);
            
            return array_key_exists($indexName, $indexes);
        } catch (\Exception $e) {
            return false;
        }
    }
};