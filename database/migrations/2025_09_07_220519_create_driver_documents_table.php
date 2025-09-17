<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('driver_documents', function (Blueprint $table) {
            $table->id();
            $table->string('driver_firebase_uid');
            $table->enum('document_type', [
                'drivers_license', 'vehicle_registration', 'insurance_certificate',
                'background_check', 'drug_test', 'vehicle_inspection', 'profile_photo',
                'vehicle_photos', 'bank_statement', 'tax_document', 'identity_proof',
                'address_proof', 'medical_certificate', 'commercial_license',
                'vehicle_permit', 'other'
            ]);
            $table->enum('document_category', [
                'identity', 'license', 'vehicle', 'insurance', 'financial',
                'legal', 'medical', 'photo', 'other'
            ]);
            $table->string('document_name');
            $table->string('file_path');
            $table->string('file_url')->nullable();
            $table->string('file_name');
            $table->integer('file_size');
            $table->string('file_type', 10);
            $table->string('mime_type', 100);
            $table->string('document_number', 100)->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('issuing_authority')->nullable();
            $table->string('issuing_country', 100)->nullable();
            $table->string('issuing_state', 100)->nullable();
            $table->enum('verification_status', ['pending', 'under_review', 'verified', 'rejected', 'expired'])
                  ->default('pending');
            $table->timestamp('verification_date')->nullable();
            $table->text('verification_notes')->nullable();
            $table->string('verified_by')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_sensitive')->default(false);
            $table->integer('download_count')->default(0);
            $table->timestamp('last_downloaded_at')->nullable();
            $table->json('metadata')->nullable();
            $table->json('tags')->nullable();
            $table->enum('status', ['active', 'inactive', 'deleted'])->default('active');
            $table->boolean('firebase_synced')->default(false);
            $table->timestamp('firebase_synced_at')->nullable();
            $table->timestamps();
            
            $table->foreign('driver_firebase_uid')->references('firebase_uid')->on('drivers')->onDelete('cascade');
            $table->index('document_type');
            $table->index('verification_status');
            $table->index('expiry_date');
            $table->index('firebase_synced');
        });
    }

    public function down()
    {
        Schema::dropIfExists('driver_documents');
    }
};