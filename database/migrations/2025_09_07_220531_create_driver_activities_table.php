<?php
// database/migrations/2025_09_07_220531_create_driver_activities_table.php

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
        Schema::create('driver_activities', function (Blueprint $table) {
            $table->id();
            $table->string('driver_firebase_uid')->index();
            $table->unsignedBigInteger('vehicle_id')->nullable()->index();
            
            $table->enum('activity_type', [
                'login', 'logout', 'status_change', 'location_update', 'ride_request',
                'ride_accept', 'ride_decline', 'ride_start', 'ride_complete', 'ride_cancel',
                'profile_update', 'document_upload', 'vehicle_update', 'payment_update',
                'rating_received', 'earnings_update', 'violation', 'system_notification'
            ])->index();
            
            $table->enum('activity_category', [
                'authentication', 'ride', 'profile', 'vehicle', 'payment',
                'location', 'system', 'security'
            ])->index();
            
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            
            // Location fields
            $table->decimal('location_latitude', 10, 8)->nullable();
            $table->decimal('location_longitude', 11, 8)->nullable();
            $table->string('location_address')->nullable();
            
            // Device/session info
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_type', 50)->nullable();
            $table->string('app_version', 20)->nullable();
            
            // Status and priority
            $table->enum('status', ['active', 'read', 'archived'])->default('active')->index();
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal')->index();
            $table->boolean('is_read')->default(false)->index();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            
            // Related entity tracking
            $table->string('related_entity_type', 50)->nullable();
            $table->string('related_entity_id')->nullable();
            
            // Admin tracking
            $table->string('created_by')->nullable();
            
            // Firebase sync fields
            $table->boolean('firebase_synced')->default(false)->index();
            $table->timestamp('firebase_synced_at')->nullable();
            
            $table->timestamps();
            
            // Composite indexes for better performance
            $table->index(['driver_firebase_uid', 'activity_type']);
            $table->index(['vehicle_id', 'activity_type']);
            $table->index(['status', 'priority']);
            $table->index(['activity_category', 'created_at']);
            $table->index(['is_read', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_activities');
    }
};