<?php
// database/migrations/2024_01_01_000001_create_tax_settings_table.php

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
        Schema::create('tax_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('tax_type', ['percentage', 'fixed', 'hybrid'])->default('percentage');
            $table->enum('calculation_method', ['simple', 'compound', 'cascading'])->default('simple');
            $table->decimal('rate', 8, 4)->default(0); // Supports up to 9999.9999%
            $table->decimal('fixed_amount', 10, 2)->default(0); // Supports up to 99,999,999.99
            $table->decimal('minimum_taxable_amount', 10, 2)->nullable();
            $table->decimal('maximum_tax_amount', 10, 2)->nullable();
            $table->enum('applicable_to', ['all', 'rides', 'delivery', 'specific'])->default('all');
            $table->json('applicable_zones')->nullable();
            $table->json('excluded_zones')->nullable();
            $table->json('applicable_vehicle_types')->nullable();
            $table->json('excluded_vehicle_types')->nullable();
            $table->json('applicable_services')->nullable();
            $table->json('excluded_services')->nullable();
            $table->boolean('is_inclusive')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('priority_order')->default(1);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();

            // Firebase sync fields
            $table->boolean('firebase_synced')->default(false);
            $table->timestamp('firebase_synced_at')->nullable();
            $table->string('firebase_sync_status')->nullable(); // synced, failed, pending
            $table->text('firebase_sync_error')->nullable();
            $table->integer('firebase_sync_attempts')->default(0);
            $table->timestamp('firebase_last_attempt_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['is_active', 'priority_order']);
            $table->index(['tax_type', 'applicable_to']);
            $table->index(['starts_at', 'expires_at']);
            $table->index('firebase_synced');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_settings');
    }
};
