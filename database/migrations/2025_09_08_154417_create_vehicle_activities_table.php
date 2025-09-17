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
        Schema::create('vehicle_activities', function (Blueprint $table) {
            $table->id();
            
            // Vehicle and driver relationships
            $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');
            $table->string('driver_firebase_uid')->index();
            
            // Activity information
            $table->string('activity_type', 50);
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            
            // Priority and status
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->boolean('is_read')->default(false);
            
            // Tracking
            $table->string('created_by')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['vehicle_id', 'activity_type']);
            $table->index(['driver_firebase_uid', 'is_read']);
            $table->index(['priority', 'created_at']);
            $table->index(['activity_type', 'created_at']);
            
            // Foreign key constraints
            $table->foreign('driver_firebase_uid')
                  ->references('firebase_uid')
                  ->on('drivers')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_activities');
    }
};