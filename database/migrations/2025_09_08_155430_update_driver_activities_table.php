<?php

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
        Schema::table('driver_activities', function (Blueprint $table) {
            // Add new relationship fields
            if (!Schema::hasColumn('driver_activities', 'vehicle_id')) {
                $table->foreignId('vehicle_id')->nullable()->after('related_entity_id');
            }
            
            if (!Schema::hasColumn('driver_activities', 'ride_id')) {
                $table->foreignId('ride_id')->nullable()->after('vehicle_id');
            }
            
            if (!Schema::hasColumn('driver_activities', 'document_id')) {
                $table->foreignId('document_id')->nullable()->after('ride_id');
            }

            // Add missing location and device fields if they don't exist
            if (!Schema::hasColumn('driver_activities', 'location_latitude')) {
                $table->decimal('location_latitude', 10, 8)->nullable()->after('metadata');
                $table->decimal('location_longitude', 11, 8)->nullable()->after('location_latitude');
                $table->string('location_address')->nullable()->after('location_longitude');
            }

            if (!Schema::hasColumn('driver_activities', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('location_address');
                $table->text('user_agent')->nullable()->after('ip_address');
                $table->string('device_type', 50)->nullable()->after('user_agent');
                $table->string('app_version', 20)->nullable()->after('device_type');
            }

            // Add Firebase sync fields if they don't exist
            if (!Schema::hasColumn('driver_activities', 'firebase_synced')) {
                $table->boolean('firebase_synced')->default(false)->after('created_by');
                $table->timestamp('firebase_synced_at')->nullable()->after('firebase_synced');
            }

            // Add new indexes for better performance
            $table->index(['driver_firebase_uid', 'activity_type', 'created_at'], 'idx_driver_type_created');
            $table->index(['activity_category', 'priority', 'created_at'], 'idx_category_priority_created');
            $table->index(['is_read', 'status', 'created_at'], 'idx_read_status_created');
            $table->index(['vehicle_id', 'created_at'], 'idx_vehicle_created');
            $table->index(['ride_id', 'created_at'], 'idx_ride_created');
            $table->index(['document_id', 'created_at'], 'idx_document_created');
            $table->index(['firebase_synced'], 'idx_firebase_synced');

            // Add foreign key constraints
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('set null');
            $table->foreign('ride_id')->references('id')->on('rides')->onDelete('set null');
            $table->foreign('document_id')->references('id')->on('driver_documents')->onDelete('set null');
        });

        // Update existing activity types to match new constants
        DB::table('driver_activities')->where('activity_type', 'vehicle_update')->update([
            'activity_category' => 'vehicle'
        ]);

        DB::table('driver_activities')->where('activity_type', 'document_upload')->update([
            'activity_category' => 'document'
        ]);

        DB::table('driver_activities')->where('activity_type', 'verification_update')->update([
            'activity_category' => 'verification'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('driver_activities', function (Blueprint $table) {
            // Drop foreign key constraints first
            $table->dropForeign(['vehicle_id']);
            $table->dropForeign(['ride_id']);
            $table->dropForeign(['document_id']);

            // Drop indexes
            $table->dropIndex('idx_driver_type_created');
            $table->dropIndex('idx_category_priority_created');
            $table->dropIndex('idx_read_status_created');
            $table->dropIndex('idx_vehicle_created');
            $table->dropIndex('idx_ride_created');
            $table->dropIndex('idx_document_created');
            $table->dropIndex('idx_firebase_synced');

            // Drop columns
            $table->dropColumn([
                'vehicle_id',
                'ride_id', 
                'document_id',
                'location_latitude',
                'location_longitude',
                'location_address',
                'ip_address',
                'user_agent',
                'device_type',
                'app_version',
                'firebase_synced',
                'firebase_synced_at'
            ]);
        });
    }
};