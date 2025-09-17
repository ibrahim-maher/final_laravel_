<?php
// database/migrations/2024_01_01_000000_create_pages_table.php

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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();

            // Core content fields
            $table->string('title');
            $table->longText('content');
            $table->string('slug')->unique();
            $table->enum('type', [
                'terms',
                'privacy',
                'about',
                'contact',
                'faq',
                'help',
                'support',
                'legal',
                'policy',
                'general'
            ])->default('general');
            $table->enum('status', ['active', 'inactive', 'draft'])->default('active');

            // SEO fields
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();

            // Display settings
            $table->string('language', 10)->default('en');
            $table->integer('display_order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('requires_auth')->default(false);
            $table->boolean('show_in_footer')->default(false);
            $table->boolean('show_in_header')->default(false);

            // Template and customization
            $table->enum('template', [
                'default',
                'simple',
                'full-width',
                'sidebar',
                'legal'
            ])->default('default');
            $table->text('custom_css')->nullable();
            $table->text('custom_js')->nullable();

            // Statistics
            $table->unsignedBigInteger('view_count')->default(0);

            // Publishing
            $table->timestamp('published_at')->nullable();

            // User tracking
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            // Firebase sync fields
            $table->boolean('firebase_synced')->default(false);
            $table->timestamp('firebase_synced_at')->nullable();
            $table->enum('firebase_sync_status', ['pending', 'synced', 'failed'])->default('pending');
            $table->text('firebase_sync_error')->nullable();
            $table->integer('firebase_sync_attempts')->default(0);
            $table->timestamp('firebase_last_attempt_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['status', 'published_at']);
            $table->index(['type', 'status']);
            $table->index(['show_in_footer', 'status']);
            $table->index(['show_in_header', 'status']);
            $table->index(['is_featured', 'status']);
            $table->index(['display_order', 'created_at']);
            $table->index('firebase_synced');
            $table->index('view_count');

            // Foreign key constraints (if user table exists)
            // $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            // $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
