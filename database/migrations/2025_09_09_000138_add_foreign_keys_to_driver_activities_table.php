<?php
// database/migrations/2025_09_07_220532_add_foreign_keys_to_driver_activities_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add foreign key for driver_firebase_uid if drivers table exists
        if (Schema::hasTable('drivers') && Schema::hasTable('driver_activities')) {
            try {
                Schema::table('driver_activities', function (Blueprint $table) {
                    $table->foreign('driver_firebase_uid')
                          ->references('firebase_uid')
                          ->on('drivers')
                          ->onDelete('cascade');
                });
            } catch (\Exception $e) {
                // Log the error but don't fail the migration
                \Log::warning('Could not add driver foreign key to driver_activities: ' . $e->getMessage());
            }
        }

        // Add foreign key for vehicle_id if vehicles table exists
        if (Schema::hasTable('vehicles') && Schema::hasTable('driver_activities')) {
            try {
                Schema::table('driver_activities', function (Blueprint $table) {
                    $table->foreign('vehicle_id')
                          ->references('id')
                          ->on('vehicles')
                          ->onDelete('set null');
                });
            } catch (\Exception $e) {
                // Log the error but don't fail the migration
                \Log::warning('Could not add vehicle foreign key to driver_activities: ' . $e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('driver_activities', function (Blueprint $table) {
            // Drop foreign keys if they exist
            try {
                $table->dropForeign(['driver_firebase_uid']);
            } catch (\Exception $e) {
                // Foreign key might not exist
            }
            
            try {
                $table->dropForeign(['vehicle_id']);
            } catch (\Exception $e) {
                // Foreign key might not exist
            }
        });
    }
};