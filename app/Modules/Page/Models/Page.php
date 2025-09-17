<?php
// app/Modules/Page/Models/Page.php

namespace App\Modules\Page\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'slug',
        'type',
        'status',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'language',
        'display_order',
        'is_featured',
        'requires_auth',
        'show_in_footer',
        'show_in_header',
        'template',
        'custom_css',
        'custom_js',
        'view_count',
        'published_at',
        'created_by',
        'updated_by',
        'firebase_synced',
        'firebase_synced_at',
        'firebase_sync_status',
        'firebase_sync_error',
        'firebase_sync_attempts',
        'firebase_last_attempt_at'
    ];

    protected $casts = [
        'meta_keywords' => 'array',
        'view_count' => 'integer',
        'display_order' => 'integer',
        'is_featured' => 'boolean',
        'requires_auth' => 'boolean',
        'show_in_footer' => 'boolean',
        'show_in_header' => 'boolean',
        'firebase_synced' => 'boolean',
        'firebase_synced_at' => 'datetime',
        'firebase_last_attempt_at' => 'datetime',
        'firebase_sync_attempts' => 'integer',
        'published_at' => 'datetime',
    ];

    // Constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_DRAFT = 'draft';

    const TYPE_TERMS = 'terms';
    const TYPE_PRIVACY = 'privacy';
    const TYPE_ABOUT = 'about';
    const TYPE_CONTACT = 'contact';
    const TYPE_FAQ = 'faq';
    const TYPE_HELP = 'help';
    const TYPE_SUPPORT = 'support';
    const TYPE_LEGAL = 'legal';
    const TYPE_POLICY = 'policy';
    const TYPE_GENERAL = 'general';

    const TEMPLATE_DEFAULT = 'default';
    const TEMPLATE_SIMPLE = 'simple';
    const TEMPLATE_FULL_WIDTH = 'full-width';
    const TEMPLATE_SIDEBAR = 'sidebar';
    const TEMPLATE_LEGAL = 'legal';

    // Accessors
    public function getIsActiveAttribute()
    {
        return $this->status === self::STATUS_ACTIVE &&
            ($this->published_at === null || $this->published_at <= now());
    }

    public function getReadingTimeAttribute()
    {
        $words = str_word_count(strip_tags($this->content));
        $minutes = ceil($words / 200); // Average reading speed
        return $minutes;
    }

    public function getExcerptAttribute()
    {
        return Str::limit(strip_tags($this->content), 200);
    }

    public function getWordCountAttribute()
    {
        return str_word_count(strip_tags($this->content));
    }

    public function getMetaTitleAttribute($value)
    {
        return $value ?: $this->title;
    }

    // Firebase configuration
    public function getFirebaseCollection()
    {
        return 'pages';
    }

    public function getFirebaseDocumentId()
    {
        return (string) $this->id;
    }

    public function toFirebaseArray()
    {
        return [
            'id' => $this->id,
            'title' => $this->title ?? '',
            'content' => $this->content ?? '',
            'slug' => $this->slug ?? '',
            'type' => $this->type ?? 'general',
            'status' => $this->status ?? 'active',
            'meta_title' => $this->meta_title ?? $this->title ?? '',
            'meta_description' => $this->meta_description ?? '',
            'meta_keywords_json' => $this->getArrayAsJson('meta_keywords'),
            'meta_keywords_count' => count($this->getCleanArray('meta_keywords')),
            'language' => $this->language ?? 'en',
            'display_order' => (int) ($this->display_order ?? 0),
            'is_featured' => (bool) ($this->is_featured ?? false),
            'requires_auth' => (bool) ($this->requires_auth ?? false),
            'show_in_footer' => (bool) ($this->show_in_footer ?? false),
            'show_in_header' => (bool) ($this->show_in_header ?? false),
            'template' => $this->template ?? 'default',
            'view_count' => (int) ($this->view_count ?? 0),
            'published_at' => $this->published_at ? $this->published_at->toISOString() : null,
            'is_active' => $this->is_active,
            'reading_time' => $this->reading_time,
            'excerpt' => $this->excerpt,
            'word_count' => $this->word_count,
            'created_at' => $this->created_at ? $this->created_at->toISOString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toISOString() : null,
            'sync_updated_at' => now()->toISOString(),
            'last_modified_by' => $this->updated_by ?? 'system',
        ];
    }

    /**
     * Get array as JSON string for Firebase storage
     */
    private function getArrayAsJson($fieldName)
    {
        $array = $this->getCleanArray($fieldName);
        return json_encode($array);
    }

    /**
     * Get clean array from database field
     */
    private function getCleanArray($fieldName)
    {
        try {
            $rawValue = $this->getOriginal($fieldName);

            if ($rawValue === null || $rawValue === '' || $rawValue === '[]' || $rawValue === 'null') {
                return [];
            }

            $castValue = $this->getAttribute($fieldName);
            if (is_array($castValue)) {
                return $this->cleanArrayValues($castValue);
            }

            if (is_string($rawValue)) {
                $decoded = json_decode($rawValue, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $this->cleanArrayValues($decoded);
                }

                $csvArray = array_map('trim', explode(',', $rawValue));
                return $this->cleanArrayValues($csvArray);
            }

            return [];
        } catch (Exception $e) {
            Log::warning("Error processing array field {$fieldName} for Page {$this->id}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Clean array values
     */
    private function cleanArrayValues($array)
    {
        if (!is_array($array)) {
            return [];
        }

        $cleaned = [];
        foreach ($array as $item) {
            if (!is_null($item) && $item !== '' && $item !== false && $item !== 'null') {
                $cleaned[] = (string) trim($item);
            }
        }

        return array_values(array_unique(array_filter($cleaned)));
    }

    public function markAsSynced()
    {
        $this->update([
            'firebase_synced' => true,
            'firebase_synced_at' => now(),
            'firebase_sync_status' => 'synced',
            'firebase_sync_error' => null
        ]);
    }

    public function markSyncFailed($error)
    {
        $this->update([
            'firebase_synced' => false,
            'firebase_sync_status' => 'failed',
            'firebase_sync_error' => $error,
            'firebase_sync_attempts' => ($this->firebase_sync_attempts ?? 0) + 1,
            'firebase_last_attempt_at' => now()
        ]);
    }

    // Helper methods for getting arrays
    public function getMetaKeywordsArray()
    {
        return $this->getCleanArray('meta_keywords');
    }

    // Boot method to handle model events
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($page) {
            // Generate slug if not provided
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);

                // Ensure unique slug
                $originalSlug = $page->slug;
                $counter = 1;
                while (static::where('slug', $page->slug)->where('id', '!=', $page->id)->exists()) {
                    $page->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }

            // Auto-set published_at if status is active and not set
            if ($page->status === self::STATUS_ACTIVE && !$page->published_at) {
                $page->published_at = now();
            }

            // Clean array fields
            $arrayFields = ['meta_keywords'];
            foreach ($arrayFields as $field) {
                if ($page->isDirty($field)) {
                    $value = $page->getAttribute($field);

                    if (is_null($value) || $value === '' || $value === 'null') {
                        $page->setAttribute($field, []);
                    } elseif (is_string($value)) {
                        $decoded = json_decode($value, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $page->setAttribute($field, array_values(array_filter($decoded)));
                        } else {
                            $csvArray = array_map('trim', explode(',', $value));
                            $page->setAttribute($field, array_values(array_filter($csvArray)));
                        }
                    } elseif (is_array($value)) {
                        $page->setAttribute($field, array_values(array_filter($value)));
                    } else {
                        $page->setAttribute($field, []);
                    }
                }
            }
        });
    }

    // Static methods
    public static function getStatuses()
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_DRAFT => 'Draft'
        ];
    }

    public static function getTypes()
    {
        return [
            self::TYPE_TERMS => 'Terms & Conditions',
            self::TYPE_PRIVACY => 'Privacy Policy',
            self::TYPE_ABOUT => 'About Us',
            self::TYPE_CONTACT => 'Contact',
            self::TYPE_FAQ => 'FAQ',
            self::TYPE_HELP => 'Help',
            self::TYPE_SUPPORT => 'Support',
            self::TYPE_LEGAL => 'Legal',
            self::TYPE_POLICY => 'Policy',
            self::TYPE_GENERAL => 'General'
        ];
    }

    public static function getTemplates()
    {
        return [
            self::TEMPLATE_DEFAULT => 'Default',
            self::TEMPLATE_SIMPLE => 'Simple',
            self::TEMPLATE_FULL_WIDTH => 'Full Width',
            self::TEMPLATE_SIDEBAR => 'With Sidebar',
            self::TEMPLATE_LEGAL => 'Legal Document'
        ];
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForFooter($query)
    {
        return $query->where('show_in_footer', true);
    }

    public function scopeForHeader($query)
    {
        return $query->where('show_in_header', true);
    }

    public function scopeUnsynced($query)
    {
        return $query->where('firebase_synced', false);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
                ->orWhere('content', 'like', "%{$search}%")
                ->orWhere('type', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%");
        });
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order', 'asc')
            ->orderBy('created_at', 'desc');
    }
}
