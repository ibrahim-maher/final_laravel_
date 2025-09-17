<?php
// database/migrations/2024_01_01_000003_create_commission_payouts_table.php

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
        Schema::create('commission_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_id')->constrained()->onDelete('cascade');
            $table->string('recipient_id'); // Can be driver_id, partner_id, etc.
            $table->enum('recipient_type', ['driver', 'company', 'partner', 'referrer']);
            $table->decimal('amount', 10, 2);
            $table->enum('payout_method', ['bank_transfer', 'digital_wallet', 'cash', 'check'])->default('bank_transfer');
            $table->timestamp('payout_date');
            $table->timestamp('processed_date')->nullable();
            $table->enum('status', ['pending', 'processing', 'processed', 'failed', 'cancelled'])->default('pending');
            $table->string('transaction_id')->nullable();
            $table->string('reference_number')->unique();
            $table->json('metadata')->nullable(); // Store payment gateway response, etc.
            $table->text('notes')->nullable();
            $table->string('created_by')->nullable();
            $table->string('processed_by')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['recipient_id', 'recipient_type']);
            $table->index(['status', 'payout_date']);
            $table->index('payout_date');
            $table->index('reference_number');
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_payouts');
    }
};
