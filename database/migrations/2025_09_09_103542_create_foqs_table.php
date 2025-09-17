<?php
// database/migrations/2024_01_01_000000_create_foqs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('foqs', function (Blueprint $table) {
            $table->id();
            $table->string('question')->index();
            $table->text('answer');
            $table->string('category')->default('general')->index();
            $table->string('priority')->default('normal')->index(); // high, normal, low
            $table->string('status')->default('active')->index(); // active, inactive, draft
            $table->string('type')->default('faq')->index(); // faq, guide, troubleshoot
            $table->json('tags')->nullable();
            $table->json('applicable_user_types')->nullable(); // customer, driver, merchant, etc.
            $table->json('applicable_platforms')->nullable(); // web, mobile, api
            $table->string('language')->default('en')->index();
            $table->integer('view_count')->default(0);
            $table->integer('helpful_count')->default(0);
            $table->integer('not_helpful_count')->default(0);
            $table->integer('display_order')->default(0)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('requires_auth')->default(false);
            $table->string('icon')->nullable();
            $table->string('external_link')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('slug')->unique()->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            
            // Firebase sync fields
            $table->boolean('firebase_synced')->default(false)->index();
            $table->timestamp('firebase_synced_at')->nullable();
            $table->string('firebase_sync_status')->default('pending')->index();
            $table->text('firebase_sync_error')->nullable();
            $table->integer('firebase_sync_attempts')->default(0);
            $table->timestamp('firebase_last_attempt_at')->nullable();
            
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['status', 'category']);
            $table->index(['is_featured', 'display_order']);
            $table->index(['created_at', 'status']);
            $table->fullText(['question', 'answer']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('foqs');
    }
};