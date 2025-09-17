<?php
// database/migrations/2024_01_15_000000_add_additional_firebase_sync_fields_to_coupons_table.php

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
        Schema::table('coupons', function (Blueprint $table) {
            // Only add fields that don't exist yet
            // firebase_synced and firebase_synced_at already exist
            
            $table->string('firebase_sync_status', 50)->default('pending')->after('firebase_synced_at');
            $table->text('firebase_sync_error')->nullable()->after('firebase_sync_status');
            $table->integer('firebase_sync_attempts')->default(0)->after('firebase_sync_error');
            $table->timestamp('firebase_last_attempt_at')->nullable()->after('firebase_sync_attempts');
            
            // Add missing performance indexes (some may already exist, but Laravel will skip duplicates)
            $table->index('firebase_sync_status', 'idx_coupons_sync_status');
            $table->index(['firebase_synced', 'created_at'], 'idx_coupons_sync_created');
            $table->index(['firebase_synced', 'updated_at'], 'idx_coupons_sync_updated');
            
            // General performance indexes (some may exist, Laravel will handle duplicates)
            $table->index('used_count', 'idx_coupons_used_count');
            $table->index('discount_type', 'idx_coupons_discount_type');
            
            // Composite indexes for common queries
            $table->index(['status', 'expires_at'], 'idx_coupons_status_expires');
            $table->index(['coupon_type', 'status'], 'idx_coupons_type_status');
            $table->index(['status', 'created_at'], 'idx_coupons_status_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            // Drop indexes first (only the ones we added)
            $table->dropIndex('idx_coupons_sync_status');
            $table->dropIndex('idx_coupons_sync_created');
            $table->dropIndex('idx_coupons_sync_updated');
            $table->dropIndex('idx_coupons_used_count');
            $table->dropIndex('idx_coupons_discount_type');
            $table->dropIndex('idx_coupons_status_expires');
            $table->dropIndex('idx_coupons_type_status');
            $table->dropIndex('idx_coupons_status_created');
            
            // Drop only the new columns (keep firebase_synced and firebase_synced_at)
            $table->dropColumn([
                'firebase_sync_status',
                'firebase_sync_error',
                'firebase_sync_attempts',
                'firebase_last_attempt_at'
            ]);
        });
    }
};