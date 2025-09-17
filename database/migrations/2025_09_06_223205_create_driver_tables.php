<?php
// database/migrations/2024_01_01_000001_create_drivers_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('firebase_uid')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('photo_url')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 50)->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('license_number')->nullable();
            $table->date('license_expiry')->nullable();
            $table->string('license_class', 20)->nullable();
            $table->string('license_type', 20)->nullable();
            $table->string('license_state', 50)->nullable();
            $table->string('issuing_state', 50)->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended', 'pending'])->default('pending');
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->timestamp('verification_date')->nullable();
            $table->string('verified_by')->nullable();
            $table->text('verification_notes')->nullable();
            $table->enum('availability_status', ['available', 'busy', 'offline'])->default('offline');
            $table->decimal('rating', 3, 2)->default(5.00);
            $table->integer('total_rides')->default(0);
            $table->integer('completed_rides')->default(0);
            $table->integer('cancelled_rides')->default(0);
            $table->decimal('total_earnings', 10, 2)->default(0.00);
            $table->decimal('current_location_lat', 10, 8)->nullable();
            $table->decimal('current_location_lng', 11, 8)->nullable();
            $table->string('current_address')->nullable();
            $table->timestamp('last_location_update')->nullable();
            $table->timestamp('join_date')->nullable();
            $table->timestamp('last_active')->nullable();
            $table->enum('background_check_status', ['pending', 'passed', 'failed'])->nullable();
            $table->date('background_check_date')->nullable();
            $table->enum('drug_test_status', ['pending', 'passed', 'failed'])->nullable();
            $table->date('drug_test_date')->nullable();
            $table->string('insurance_provider')->nullable();
            $table->string('insurance_policy_number')->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_routing_number')->nullable();
            $table->string('bank_account_holder_name')->nullable();
            $table->string('tax_id')->nullable();
            $table->boolean('firebase_synced')->default(false);
            $table->timestamp('firebase_synced_at')->nullable();
            $table->timestamps();
            
            $table->index('status');
            $table->index('verification_status');
            $table->index('availability_status');
            $table->index('firebase_synced');
            $table->index(['current_location_lat', 'current_location_lng']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('drivers');
    }
};