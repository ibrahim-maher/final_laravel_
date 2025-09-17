<?php
// config/coupon.php

return [
    /*
    |--------------------------------------------------------------------------
    | Auto Sync Configuration
    |--------------------------------------------------------------------------
    |
    | Configure automatic Firebase synchronization behavior
    |
    */
    
    'auto_sync_firebase' => env('COUPON_AUTO_SYNC_FIREBASE', true),
    
    'sync_batch_size' => env('COUPON_SYNC_BATCH_SIZE', 10),
    
    'immediate_sync_threshold' => env('COUPON_IMMEDIATE_SYNC_THRESHOLD', 5),
    
    'sync_retry_attempts' => env('COUPON_SYNC_RETRY_ATTEMPTS', 3),
    
    'sync_delay_seconds' => env('COUPON_SYNC_DELAY_SECONDS', 2),
    
    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */
    
    'cache_ttl' => env('COUPON_CACHE_TTL', 300), // 5 minutes
    
    'statistics_cache_ttl' => env('COUPON_STATISTICS_CACHE_TTL', 600), // 10 minutes
    
    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    */
    
    'max_bulk_operations' => env('COUPON_MAX_BULK_OPERATIONS', 100),
    
    'max_export_limit' => env('COUPON_MAX_EXPORT_LIMIT', 1000),
    
    /*
    |--------------------------------------------------------------------------
    | Monitoring
    |--------------------------------------------------------------------------
    */
    
    'enable_performance_monitoring' => env('COUPON_PERFORMANCE_MONITORING', true),
    
    'log_slow_operations' => env('COUPON_LOG_SLOW_OPERATIONS', true),
    
    'slow_operation_threshold' => env('COUPON_SLOW_OPERATION_THRESHOLD', 5), // seconds
];