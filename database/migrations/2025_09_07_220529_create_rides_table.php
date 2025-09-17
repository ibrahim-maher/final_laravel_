<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('rides', function (Blueprint $table) {
            $table->id();
            $table->string('ride_id')->unique();
            $table->string('driver_firebase_uid');
            $table->string('passenger_firebase_uid')->nullable();
            $table->string('passenger_name');
            $table->string('passenger_phone')->nullable();
            
            // Temporarily remove foreign key constraint:
            $table->unsignedBigInteger('vehicle_id')->nullable();
            
            $table->string('pickup_address');
            $table->decimal('pickup_latitude', 10, 8);
            $table->decimal('pickup_longitude', 11, 8);
            $table->string('dropoff_address');
            $table->decimal('dropoff_latitude', 10, 8);
            $table->decimal('dropoff_longitude', 11, 8);
            $table->enum('status', [
                'pending', 'requested', 'accepted', 'driver_arrived',
                'in_progress', 'completed', 'cancelled'
            ])->default('pending');
            $table->enum('ride_type', [
                'standard', 'premium', 'shared', 'xl', 'delivery', 'scheduled'
            ])->default('standard');
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->string('cancelled_by', 50)->nullable();
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->decimal('estimated_fare', 10, 2)->nullable();
            $table->decimal('actual_fare', 10, 2)->nullable();
            $table->decimal('base_fare', 10, 2)->nullable();
            $table->decimal('distance_fare', 10, 2)->nullable();
            $table->decimal('time_fare', 10, 2)->nullable();
            $table->decimal('surge_multiplier', 3, 2)->default(1.00);
            $table->decimal('surge_fare', 10, 2)->nullable();
            $table->decimal('tolls', 10, 2)->nullable();
            $table->decimal('taxes', 10, 2)->nullable();
            $table->decimal('tips', 10, 2)->nullable();
            $table->decimal('discount', 10, 2)->nullable();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->decimal('driver_earnings', 10, 2)->nullable();
            $table->decimal('commission', 10, 2)->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->enum('payment_status', ['pending', 'completed', 'failed', 'refunded'])
                  ->default('pending');
            $table->decimal('driver_rating', 3, 2)->nullable();
            $table->decimal('passenger_rating', 3, 2)->nullable();
            $table->text('driver_feedback')->nullable();
            $table->text('passenger_feedback')->nullable();
            $table->text('route_polyline')->nullable();
            $table->string('weather_condition', 50)->nullable();
            $table->string('traffic_condition', 50)->nullable();
            $table->json('special_requests')->nullable();
            $table->string('promocode_used', 50)->nullable();
            $table->timestamp('status_updated_at')->nullable();
            $table->boolean('firebase_synced')->default(false);
            $table->timestamp('firebase_synced_at')->nullable();
            $table->timestamps();
            
            // Foreign key constraints and indexes
            $table->foreign('driver_firebase_uid')->references('firebase_uid')->on('drivers')->onDelete('cascade');
            $table->index('status');
            $table->index('ride_type');
            $table->index('payment_status');
            $table->index('requested_at');
            $table->index('firebase_synced');
            
            // DO NOT add another $table->foreignId('vehicle_id') or $table->unsignedBigInteger('vehicle_id') here!
        });
    }

    public function down()
    {
        Schema::dropIfExists('rides');
    }
};