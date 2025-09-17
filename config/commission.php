<?php
// config/commission.php

return [
    /*
    |--------------------------------------------------------------------------
    | Commission Module Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the Commission module including Firebase sync,
    | caching, payout settings, and business logic.
    |
    */

    // Firebase sync settings
    'auto_sync_firebase' => env('COMMISSION_AUTO_SYNC_FIREBASE', true),
    'sync_batch_size' => env('COMMISSION_SYNC_BATCH_SIZE', 10),
    'immediate_sync_threshold' => env('COMMISSION_IMMEDIATE_SYNC_THRESHOLD', 5),
    'sync_retry_attempts' => env('COMMISSION_SYNC_RETRY_ATTEMPTS', 3),
    'sync_retry_delay' => env('COMMISSION_SYNC_RETRY_DELAY', 60), // seconds

    // Cache settings
    'cache_ttl' => env('COMMISSION_CACHE_TTL', 300), // 5 minutes
    'statistics_cache_ttl' => env('COMMISSION_STATISTICS_CACHE_TTL', 600), // 10 minutes
    'calculation_cache_ttl' => env('COMMISSION_CALCULATION_CACHE_TTL', 60), // 1 minute

    // Default commission settings
    'default_calculation_method' => env('COMMISSION_DEFAULT_CALCULATION_METHOD', 'gross'),
    'default_payment_frequency' => env('COMMISSION_DEFAULT_PAYMENT_FREQUENCY', 'weekly'),
    'max_commission_rate' => env('COMMISSION_MAX_COMMISSION_RATE', 100), // percentage
    'max_fixed_amount' => env('COMMISSION_MAX_FIXED_AMOUNT', 999999), // currency units

    // Validation rules
    'validation' => [
        'name_max_length' => 255,
        'description_max_length' => 500,
        'rate_decimal_places' => 4,
        'amount_decimal_places' => 2,
    ],

    // Payout settings
    'payout' => [
        'default_minimum_payout' => env('COMMISSION_DEFAULT_MINIMUM_PAYOUT', 10.00),
        'max_payout_amount' => env('COMMISSION_MAX_PAYOUT_AMOUNT', 10000.00),
        'auto_payout_enabled' => env('COMMISSION_AUTO_PAYOUT_ENABLED', true),
        'payout_processing_delay' => env('COMMISSION_PAYOUT_PROCESSING_DELAY', 24), // hours
        'payout_retry_attempts' => env('COMMISSION_PAYOUT_RETRY_ATTEMPTS', 3),
    ],

    // Business logic settings
    'business' => [
        'allow_negative_commission' => env('COMMISSION_ALLOW_NEGATIVE_COMMISSION', false),
        'enable_tier_based_commission' => env('COMMISSION_ENABLE_TIER_BASED', true),
        'tier_calculation_limit' => env('COMMISSION_TIER_CALCULATION_LIMIT', 10),
        'enable_zone_restrictions' => env('COMMISSION_ENABLE_ZONE_RESTRICTIONS', true),
        'enable_vehicle_type_restrictions' => env('COMMISSION_ENABLE_VEHICLE_TYPE_RESTRICTIONS', true),
        'enable_recipient_verification' => env('COMMISSION_ENABLE_RECIPIENT_VERIFICATION', true),
    ],

    // Supported zones (customize based on your application)
    'available_zones' => [
        'zone_1' => 'Downtown',
        'zone_2' => 'Suburbs',
        'zone_3' => 'Airport',
        'zone_4' => 'Industrial',
        'zone_5' => 'Residential',
    ],

    // Supported vehicle types (customize based on your application)
    'available_vehicle_types' => [
        'sedan' => 'Sedan',
        'suv' => 'SUV',
        'van' => 'Van',
        'motorcycle' => 'Motorcycle',
        'bicycle' => 'Bicycle',
        'truck' => 'Truck',
    ],

    // Supported service types
    'available_services' => [
        'ride' => 'Ride Sharing',
        'delivery' => 'Delivery',
        'rental' => 'Vehicle Rental',
        'shuttle' => 'Shuttle Service',
    ],

    // Recipient types configuration
    'recipient_types' => [
        'driver' => [
            'name' => 'Driver',
            'auto_payout' => true,
            'minimum_payout' => 10.00,
            'payment_methods' => ['bank_transfer', 'digital_wallet', 'cash'],
        ],
        'company' => [
            'name' => 'Company',
            'auto_payout' => false,
            'minimum_payout' => 100.00,
            'payment_methods' => ['bank_transfer', 'check'],
        ],
        'partner' => [
            'name' => 'Partner',
            'auto_payout' => true,
            'minimum_payout' => 50.00,
            'payment_methods' => ['bank_transfer', 'digital_wallet'],
        ],
        'referrer' => [
            'name' => 'Referrer',
            'auto_payout' => true,
            'minimum_payout' => 5.00,
            'payment_methods' => ['digital_wallet', 'cash'],
        ],
    ],

    // Calculation methods configuration
    'calculation_methods' => [
        'gross' => [
            'name' => 'Gross Amount',
            'description' => 'Calculate commission on total trip amount including taxes and fees',
        ],
        'net' => [
            'name' => 'Net Amount',
            'description' => 'Calculate commission on net amount after taxes and fees',
        ],
        'trip_fare' => [
            'name' => 'Trip Fare Only',
            'description' => 'Calculate commission only on base trip fare',
        ],
        'base_fare' => [
            'name' => 'Base Fare Only',
            'description' => 'Calculate commission only on base fare excluding distance/time charges',
        ],
    ],

    // Logging settings
    'logging' => [
        'log_calculations' => env('COMMISSION_LOG_CALCULATIONS', false),
        'log_payouts' => env('COMMISSION_LOG_PAYOUTS', true),
        'log_sync_operations' => env('COMMISSION_LOG_SYNC_OPERATIONS', true),
        'log_bulk_operations' => env('COMMISSION_LOG_BULK_OPERATIONS', true),
    ],

    // Performance settings
    'performance' => [
        'bulk_operation_batch_size' => env('COMMISSION_BULK_OPERATION_BATCH_SIZE', 25),
        'max_concurrent_syncs' => env('COMMISSION_MAX_CONCURRENT_SYNCS', 5),
        'payout_batch_size' => env('COMMISSION_PAYOUT_BATCH_SIZE', 50),
    ],

    // Feature flags
    'features' => [
        'enable_tier_based_commission' => env('COMMISSION_ENABLE_TIER_BASED', true),
        'enable_auto_payout' => env('COMMISSION_ENABLE_AUTO_PAYOUT', true),
        'enable_payout_scheduling' => env('COMMISSION_ENABLE_PAYOUT_SCHEDULING', true),
        'enable_commission_caps' => env('COMMISSION_ENABLE_COMMISSION_CAPS', true),
        'enable_expiration_dates' => env('COMMISSION_ENABLE_EXPIRATION_DATES', true),
        'enable_referral_commissions' => env('COMMISSION_ENABLE_REFERRAL_COMMISSIONS', true),
    ],

    // Integration settings
    'integrations' => [
        'payment_gateway' => env('COMMISSION_PAYMENT_GATEWAY', 'stripe'),
        'accounting_system' => env('COMMISSION_ACCOUNTING_SYSTEM', null),
        'notification_service' => env('COMMISSION_NOTIFICATION_SERVICE', 'firebase'),
    ],
];