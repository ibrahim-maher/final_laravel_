<?php
// config/complaint.php

return [
    /*
    |--------------------------------------------------------------------------
    | Complaint Module Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the complaint management
    | module that integrates with Firestore.
    |
    */

    // Cache settings
    'cache_ttl' => env('COMPLAINT_CACHE_TTL', 300), // 5 minutes
    'statistics_cache_ttl' => env('COMPLAINT_STATISTICS_CACHE_TTL', 600), // 10 minutes

    // Firestore collection name
    'firestore_collection' => env('COMPLAINT_FIRESTORE_COLLECTION', 'complaints'),

    // Pagination settings
    'default_per_page' => env('COMPLAINT_DEFAULT_PER_PAGE', 25),
    'max_per_page' => env('COMPLAINT_MAX_PER_PAGE', 100),

    // Priority escalation settings (hours after which complaint becomes overdue)
    'overdue_hours' => [
        'urgent' => 2,
        'high' => 8,
        'medium' => 24,
        'low' => 72,
    ],

    // Export settings
    'export_max_records' => env('COMPLAINT_EXPORT_MAX_RECORDS', 1000),
    'export_formats' => ['csv', 'excel'],

    // Bulk action settings
    'bulk_action_max_items' => env('COMPLAINT_BULK_ACTION_MAX_ITEMS', 100),

    // Status workflow
    'allowed_status_transitions' => [
        'pending' => ['in_progress', 'resolved', 'closed'],
        'in_progress' => ['pending', 'resolved', 'closed'],
        'resolved' => ['closed', 'in_progress'], // Allow reopening
        'closed' => [], // Cannot change from closed
    ],

    // Default filters
    'default_filters' => [
        'status' => null,
        'priority' => null,
        'order_type' => null,
        'category' => null,
        'sort_by' => 'created_at',
        'sort_direction' => 'desc',
    ],

    // Auto-refresh settings for dashboard
    'auto_refresh_interval' => env('COMPLAINT_AUTO_REFRESH_INTERVAL', 30), // seconds

    // Notification settings
    'notifications' => [
        'urgent_complaints' => env('COMPLAINT_NOTIFY_URGENT', true),
        'overdue_complaints' => env('COMPLAINT_NOTIFY_OVERDUE', true),
        'new_complaints' => env('COMPLAINT_NOTIFY_NEW', false),
    ],

    // Admin notes settings
    'admin_notes' => [
        'max_length' => 1000,
        'timestamp_format' => 'Y-m-d H:i:s',
    ],
];