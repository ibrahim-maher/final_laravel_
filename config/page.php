<?php
// app/Modules/Page/Config/page.php

return [

    /*
    |--------------------------------------------------------------------------
    | Page Module Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for the Page management module
    |
    */

    'module_name' => 'Page Management',
    'module_version' => '1.0.0',
    'module_description' => 'Manage static pages like Terms & Conditions, Privacy Policy, and other content',

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'status' => 'active',
        'type' => 'general',
        'template' => 'default',
        'language' => 'en',
        'display_order' => 0,
        'requires_auth' => false,
        'is_featured' => false,
        'show_in_footer' => false,
        'show_in_header' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Page Types Configuration
    |--------------------------------------------------------------------------
    */

    'types' => [
        'terms' => [
            'label' => 'Terms & Conditions',
            'description' => 'Legal terms and conditions for service usage',
            'icon' => 'fas fa-gavel',
            'suggested_template' => 'legal',
            'requires_auth' => false,
            'show_in_footer' => true,
        ],
        'privacy' => [
            'label' => 'Privacy Policy',
            'description' => 'Privacy policy and data protection information',
            'icon' => 'fas fa-shield-alt',
            'suggested_template' => 'legal',
            'requires_auth' => false,
            'show_in_footer' => true,
        ],
        'about' => [
            'label' => 'About Us',
            'description' => 'Company information and background',
            'icon' => 'fas fa-info-circle',
            'suggested_template' => 'default',
            'requires_auth' => false,
            'show_in_footer' => true,
        ],
        'contact' => [
            'label' => 'Contact',
            'description' => 'Contact information and forms',
            'icon' => 'fas fa-envelope',
            'suggested_template' => 'sidebar',
            'requires_auth' => false,
            'show_in_footer' => true,
        ],
        'faq' => [
            'label' => 'FAQ',
            'description' => 'Frequently asked questions',
            'icon' => 'fas fa-question-circle',
            'suggested_template' => 'default',
            'requires_auth' => false,
            'show_in_footer' => false,
        ],
        'help' => [
            'label' => 'Help',
            'description' => 'Help and support documentation',
            'icon' => 'fas fa-life-ring',
            'suggested_template' => 'sidebar',
            'requires_auth' => false,
            'show_in_footer' => true,
        ],
        'support' => [
            'label' => 'Support',
            'description' => 'Customer support information',
            'icon' => 'fas fa-headset',
            'suggested_template' => 'default',
            'requires_auth' => false,
            'show_in_footer' => false,
        ],
        'legal' => [
            'label' => 'Legal',
            'description' => 'Legal documents and notices',
            'icon' => 'fas fa-balance-scale',
            'suggested_template' => 'legal',
            'requires_auth' => false,
            'show_in_footer' => true,
        ],
        'policy' => [
            'label' => 'Policy',
            'description' => 'Company policies and procedures',
            'icon' => 'fas fa-clipboard-list',
            'suggested_template' => 'legal',
            'requires_auth' => false,
            'show_in_footer' => false,
        ],
        'general' => [
            'label' => 'General',
            'description' => 'General purpose pages',
            'icon' => 'fas fa-file-alt',
            'suggested_template' => 'default',
            'requires_auth' => false,
            'show_in_footer' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Template Configuration
    |--------------------------------------------------------------------------
    */

    'templates' => [
        'default' => [
            'label' => 'Default',
            'description' => 'Standard page layout with header and footer',
            'view' => 'page.templates.default',
            'supports_sidebar' => false,
        ],
        'simple' => [
            'label' => 'Simple',
            'description' => 'Clean, minimal layout',
            'view' => 'page.templates.simple',
            'supports_sidebar' => false,
        ],
        'full-width' => [
            'label' => 'Full Width',
            'description' => 'Full width layout without containers',
            'view' => 'page.templates.full-width',
            'supports_sidebar' => false,
        ],
        'sidebar' => [
            'label' => 'With Sidebar',
            'description' => 'Two-column layout with sidebar',
            'view' => 'page.templates.sidebar',
            'supports_sidebar' => true,
        ],
        'legal' => [
            'label' => 'Legal Document',
            'description' => 'Formatted for legal documents with proper typography',
            'view' => 'page.templates.legal',
            'supports_sidebar' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SEO Configuration
    |--------------------------------------------------------------------------
    */

    'seo' => [
        'meta_title_max_length' => 60,
        'meta_description_max_length' => 160,
        'meta_keywords_max_count' => 10,
        'auto_generate_meta' => true,
        'include_in_sitemap' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase Sync Configuration
    |--------------------------------------------------------------------------
    */

    'firebase' => [
        'enabled' => true,
        'collection_name' => 'pages',
        'batch_size' => 10,
        'max_retry_attempts' => 3,
        'sync_on_create' => true,
        'sync_on_update' => true,
        'sync_on_delete' => true,
        'retry_delay_minutes' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */

    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hour
        'prefix' => 'pages_',
        'tags' => ['pages', 'content'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Configuration
    |--------------------------------------------------------------------------
    */

    'export' => [
        'formats' => ['csv'],
        'max_records' => 1000,
        'include_content' => false, // Don't include full content in exports by default
        'filename_format' => 'pages_export_{date}',
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    */

    'validation' => [
        'title_max_length' => 255,
        'slug_max_length' => 255,
        'meta_title_max_length' => 255,
        'meta_description_max_length' => 500,
        'content_required' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Configuration
    |--------------------------------------------------------------------------
    */

    'ui' => [
        'items_per_page' => 25,
        'max_items_per_page' => 100,
        'show_word_count' => true,
        'show_reading_time' => true,
        'enable_bulk_actions' => true,
        'enable_search' => true,
        'enable_filters' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    */

    'security' => [
        'sanitize_content' => false, // Let TinyMCE handle content
        'allowed_html_tags' => null, // Allow all HTML tags in content
        'strip_dangerous_tags' => true,
        'validate_slug_uniqueness' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    */

    'features' => [
        'custom_css' => true,
        'custom_js' => true,
        'view_tracking' => true,
        'featured_pages' => true,
        'multi_language' => true,
        'scheduled_publishing' => true,
        'page_templates' => true,
        'seo_fields' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Configuration
    |--------------------------------------------------------------------------
    */

    'integrations' => [
        'analytics' => [
            'enabled' => true,
            'track_page_views' => true,
            'track_reading_time' => false,
        ],
        'search_engine' => [
            'enabled' => true,
            'index_content' => true,
            'boost_featured' => true,
        ],
    ],
];
