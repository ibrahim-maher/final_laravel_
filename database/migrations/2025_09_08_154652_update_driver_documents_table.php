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
        Schema::table('driver_documents', function (Blueprint $table) {
            // Add vehicle support
            $table->foreignId('vehicle_id')->nullable()->after('driver_firebase_uid');
            
            // Add additional tracking fields if they don't exist
            if (!Schema::hasColumn('driver_documents', 'uploaded_by')) {
                $table->string('uploaded_by')->nullable()->after('expiry_date');
            }
            
            // Note: firebase_synced columns already exist in the original migration, so skip them
            
            // Add new indexes (avoid duplicates)
            $table->index(['vehicle_id', 'document_type']);
            $table->index(['driver_firebase_uid', 'vehicle_id']);
            $table->index(['verification_status', 'expiry_date']);
            // Skip firebase_synced index - it already exists
            
            // Add foreign key constraint for vehicle_id
            $table->foreign('vehicle_id')
                  ->references('id')
                  ->on('vehicles')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('driver_documents', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['vehicle_id']);
            
            // Drop indexes
            $table->dropIndex(['vehicle_id', 'document_type']);
            $table->dropIndex(['driver_firebase_uid', 'vehicle_id']);
            $table->dropIndex(['verification_status', 'expiry_date']);
            
            // Drop columns
            $table->dropColumn(['vehicle_id', 'uploaded_by']);
        });
    }
};