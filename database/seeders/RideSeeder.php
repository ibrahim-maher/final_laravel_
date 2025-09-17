<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Driver\Models\Ride;
use Illuminate\Support\Str;
use Carbon\Carbon;

class RideSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = \Faker\Factory::create();

        for ($i = 1; $i <= 20; $i++) {
            Ride::create([
                'ride_id'              => strtoupper(Str::random(8)),
                'passenger_name' => $faker->name,

                'driver_firebase_uid'  => 'driver_' . $faker->uuid,
                'passenger_firebase_uid' => 'passenger_' . $faker->uuid,
                'vehicle_id'           => $faker->numberBetween(1, 10),
                'pickup_address'       => $faker->address,
                'pickup_latitude'      => $faker->latitude(29.0, 31.5), // Egypt range example
                'pickup_longitude'     => $faker->longitude(30.0, 32.5),
                'dropoff_address'      => $faker->address,
                'dropoff_latitude'     => $faker->latitude(29.0, 31.5),
                'dropoff_longitude'    => $faker->longitude(30.0, 32.5),
                'status'               => $faker->randomElement([
                    Ride::STATUS_REQUESTED,
                    Ride::STATUS_ACCEPTED,
                    Ride::STATUS_IN_PROGRESS,
                    Ride::STATUS_COMPLETED,
                    Ride::STATUS_CANCELLED,
                ]),
                'ride_type'            => $faker->randomElement(array_keys(Ride::getRideTypes())),
                'requested_at'         => Carbon::now()->subMinutes(rand(30, 300)),
                'accepted_at'          => Carbon::now()->subMinutes(rand(20, 250)),
                'started_at'           => Carbon::now()->subMinutes(rand(10, 200)),
                'completed_at'         => Carbon::now()->subMinutes(rand(1, 50)),
                'cancelled_at'         => null,
                'cancellation_reason'  => null,
                'cancelled_by'         => null,
                'distance_km'          => $faker->randomFloat(2, 1, 30),
                'duration_minutes'     => $faker->numberBetween(5, 90),
                'estimated_fare'       => $faker->randomFloat(2, 20, 200),
                'actual_fare'          => $faker->randomFloat(2, 20, 200),
                'base_fare'            => 5.00,
                'distance_fare'        => $faker->randomFloat(2, 10, 100),
                'time_fare'            => $faker->randomFloat(2, 5, 50),
                'surge_multiplier'     => $faker->randomElement([1, 1.2, 1.5, 2]),
                'surge_fare'           => $faker->randomFloat(2, 0, 50),
                'tolls'                => $faker->randomFloat(2, 0, 20),
                'taxes'                => $faker->randomFloat(2, 0, 20),
                'tips'                 => $faker->randomFloat(2, 0, 50),
                'discount'             => $faker->randomFloat(2, 0, 30),
                'total_amount'         => $faker->randomFloat(2, 30, 300),
                'driver_earnings'      => $faker->randomFloat(2, 20, 200),
                'commission'           => $faker->randomFloat(2, 5, 50),
                'payment_method'       => $faker->randomElement(['cash', 'card', 'wallet']),
                'payment_status'       => $faker->randomElement([
                    Ride::PAYMENT_PENDING,
                    Ride::PAYMENT_COMPLETED,
                    Ride::PAYMENT_FAILED
                ]),
                'driver_rating'        => $faker->randomFloat(1, 3, 5),
                'passenger_rating'     => $faker->randomFloat(1, 3, 5),
                'driver_feedback'      => $faker->sentence,
                'passenger_feedback'   => $faker->sentence,
                'route_polyline'       => null,
                'weather_condition'    => $faker->randomElement(['clear', 'rainy', 'cloudy']),
                'traffic_condition'    => $faker->randomElement(['light', 'moderate', 'heavy']),
                'special_requests'     => [],
                'promocode_used'       => $faker->randomElement([null, 'PROMO10', 'WELCOME5']),
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
        }
    }
}
