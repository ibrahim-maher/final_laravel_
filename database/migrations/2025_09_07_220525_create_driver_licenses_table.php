<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('driver_licenses', function (Blueprint $table) {
            $table->id();
            $table->string('driver_firebase_uid');
            $table->string('license_number')->unique();
            $table->string('license_class', 20)->nullable();
            $table->string('license_type', 20)->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date');
            $table->string('issuing_state', 50)->nullable();
            $table->string('issuing_country', 50)->default('US');
            $table->string('issuing_authority')->nullable();
            $table->enum('status', ['valid', 'expired', 'suspended', 'revoked', 'pending_renewal'])
                  ->default('valid');
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])
                  ->default('pending');
            $table->timestamp('verification_date')->nullable();
            $table->string('verified_by')->nullable();
            $table->json('restrictions')->nullable();
            $table->json('endorsements')->nullable();
            $table->integer('points')->default(0);
            $table->boolean('is_primary')->default(true);
            $table->unsignedBigInteger('document_id')->nullable();
            $table->string('front_image_url')->nullable();
            $table->string('back_image_url')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('firebase_synced')->default(false);
            $table->timestamp('firebase_synced_at')->nullable();
            $table->timestamps();
            
            $table->foreign('driver_firebase_uid')->references('firebase_uid')->on('drivers')->onDelete('cascade');
            $table->foreign('document_id')->references('id')->on('driver_documents')->onDelete('set null');
            $table->index('status');
            $table->index('verification_status');
            $table->index('expiry_date');
            $table->index('firebase_synced');
        });
    }

    public function down()
    {
        Schema::dropIfExists('driver_licenses');
    }
};