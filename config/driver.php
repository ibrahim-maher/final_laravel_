<?php
// config/driver.php

return [
    'cache' => [
        'enabled' => env('DRIVER_CACHE_ENABLED', true),
        'ttl' => env('DRIVER_CACHE_TTL', 300), // 5 minutes
        'prefix' => env('DRIVER_CACHE_PREFIX', 'driver_'),
    ],

    'sync' => [
        'enabled' => env('FIRESTORE_SYNC_ENABLED', true),
        'queue' => env('FIRESTORE_SYNC_QUEUE', 'firestore-sync'),
        'batch_size' => env('FIRESTORE_SYNC_BATCH_SIZE', 100),
        'retry_attempts' => env('FIRESTORE_SYNC_RETRY', 3),
    ],

    'pagination' => [
        'default_limit' => env('DRIVER_DEFAULT_LIMIT', 25),
        'max_limit' => env('DRIVER_MAX_LIMIT', 100),
    ],

    'search' => [
        'min_query_length' => 2,
        'max_results' => 50,
    ],

    'location' => [
        'default_radius_km' => 10,
        'max_radius_km' => 50,
    ],

    'performance' => [
        'use_redis_cache' => env('USE_REDIS_CACHE', true),
        'use_database_transactions' => true,
        'eager_load_relationships' => true,
    ],
    'google_maps' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],
];

// .env additions
/*
Add these to your .env file:

# Driver Module Configuration
DRIVER_CACHE_ENABLED=true
DRIVER_CACHE_TTL=300
DRIVER_CACHE_PREFIX=driver_

# Firestore Sync Configuration  
FIRESTORE_SYNC_ENABLED=true
FIRESTORE_SYNC_QUEUE=firestore-sync
FIRESTORE_SYNC_BATCH_SIZE=100
FIRESTORE_SYNC_RETRY=3

# Performance Settings
USE_REDIS_CACHE=true
DRIVER_DEFAULT_LIMIT=25
DRIVER_MAX_LIMIT=100

# Queue Configuration (add to existing queue config)
QUEUE_CONNECTION=redis
REDIS_CLIENT=predis
*/

// config/queue.php additions
/*
Add this connection to your config/queue.php connections array:


*/