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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            
            // Driver relationship
            $table->string('driver_firebase_uid')->index();
            
            // Basic vehicle information
            $table->string('make', 100);
            $table->string('model', 100);
            $table->integer('year');
            $table->string('color', 50)->nullable();
            $table->string('license_plate', 20)->unique();
            $table->string('vin', 17)->nullable()->unique();
            
            // Vehicle specifications
            $table->string('vehicle_type', 50);
            $table->string('fuel_type', 50)->nullable();
            $table->string('transmission', 50)->default('automatic');
            $table->integer('doors')->default(4);
            $table->integer('seats')->default(4);
            $table->boolean('is_primary')->default(false);
            
            // Status and verification
            $table->enum('status', ['active', 'inactive', 'maintenance', 'suspended'])->default('inactive');
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->timestamp('verification_date')->nullable();
            $table->string('verified_by')->nullable();
            $table->text('verification_notes')->nullable();
            
            // Registration information
            $table->string('registration_number', 50)->nullable();
            $table->date('registration_expiry')->nullable();
            $table->string('registration_state', 50)->nullable();
            
            // Insurance information
            $table->string('insurance_provider', 100)->nullable();
            $table->string('insurance_policy_number', 50)->nullable();
            $table->date('insurance_expiry')->nullable();
            
            // Inspection information
            $table->date('inspection_date')->nullable();
            $table->date('inspection_expiry')->nullable();
            $table->string('inspection_certificate')->nullable();
            
            // Vehicle condition and maintenance
            $table->integer('mileage')->nullable();
            $table->decimal('condition_rating', 3, 2)->nullable();
            $table->date('last_maintenance_date')->nullable();
            $table->date('next_maintenance_due')->nullable();
            
            // Additional information
            $table->json('photos')->nullable();
            $table->json('features')->nullable();
            $table->text('notes')->nullable();
            
            // Admin tracking
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            
            // Firebase sync tracking
            $table->boolean('firebase_synced')->default(false);
            $table->timestamp('firebase_synced_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['driver_firebase_uid', 'is_primary']);
            $table->index(['status', 'verification_status']);
            $table->index(['vehicle_type', 'fuel_type']);
            $table->index(['registration_expiry']);
            $table->index(['insurance_expiry']);
            $table->index(['firebase_synced']);
            
            // Foreign key constraint
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
        Schema::dropIfExists('vehicles');
    }
};