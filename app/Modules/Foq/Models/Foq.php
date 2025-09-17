<?php
// app/Modules/Foq/Models/Foq.php

namespace App\Modules\Foq\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class Foq extends Model
{
    use HasFactory;

    protected $fillable = [
        'question',
        'answer',
        'category',
        'priority',
        'status',
        'type',
        'tags',
        'applicable_user_types',
        'applicable_platforms',
        'language',
        'view_count',
        'helpful_count',
        'not_helpful_count',
        'display_order',
        'is_featured',
        'requires_auth',
        'icon',
        'external_link',
        'meta_description',
        'slug',
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
        'tags' => 'array',
        'applicable_user_types' => 'array',
        'applicable_platforms' => 'array',
        'view_count' => 'integer',
        'helpful_count' => 'integer',
        'not_helpful_count' => 'integer',
        'display_order' => 'integer',
        'is_featured' => 'boolean',
        'requires_auth' => 'boolean',
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

    const PRIORITY_HIGH = 'high';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_LOW = 'low';

    const TYPE_FAQ = 'faq';
    const TYPE_GUIDE = 'guide';
    const TYPE_TROUBLESHOOT = 'troubleshoot';

    const CATEGORY_GENERAL = 'general';
    const CATEGORY_ACCOUNT = 'account';
    const CATEGORY_PAYMENT = 'payment';
    const CATEGORY_RIDES = 'rides';
    const CATEGORY_DELIVERY = 'delivery';
    const CATEGORY_TECHNICAL = 'technical';
    const CATEGORY_SAFETY = 'safety';

    const USER_TYPE_CUSTOMER = 'customer';
    const USER_TYPE_DRIVER = 'driver';
    const USER_TYPE_MERCHANT = 'merchant';
    const USER_TYPE_ADMIN = 'admin';

    const PLATFORM_WEB = 'web';
    const PLATFORM_MOBILE = 'mobile';
    const PLATFORM_API = 'api';

    // Accessors
    public function getIsActiveAttribute()
    {
        return $this->status === self::STATUS_ACTIVE && 
               ($this->published_at === null || $this->published_at <= now());
    }

    public function getHelpfulnessRatioAttribute()
    {
        $total = $this->helpful_count + $this->not_helpful_count;
        return $total > 0 ? round(($this->helpful_count / $total) * 100, 2) : 0;
    }

    public function getReadingTimeAttribute()
    {
        $words = str_word_count(strip_tags($this->answer));
        $minutes = ceil($words / 200); // Average reading speed
        return $minutes;
    }

    public function getExcerptAttribute()
    {
        return Str::limit(strip_tags($this->answer), 150);
    }

    // Firebase configuration
    public function getFirebaseCollection()
    {
        return 'foqs';
    }

    public function getFirebaseDocumentId()
    {
        return (string) $this->id;
    }

    public function toFirebaseArray()
    {
        return [
            'id' => $this->id,
            'question' => $this->question ?? '',
            'answer' => $this->answer ?? '',
            'category' => $this->category ?? 'general',
            'priority' => $this->priority ?? 'normal',
            'status' => $this->status ?? 'active',
            'type' => $this->type ?? 'faq',
            'tags_json' => $this->getArrayAsJson('tags'),
            'applicable_user_types_json' => $this->getArrayAsJson('applicable_user_types'),
            'applicable_platforms_json' => $this->getArrayAsJson('applicable_platforms'),
            'tags_count' => count($this->getCleanArray('tags')),
            'applicable_user_types_count' => count($this->getCleanArray('applicable_user_types')),
            'applicable_platforms_count' => count($this->getCleanArray('applicable_platforms')),
            'language' => $this->language ?? 'en',
            'view_count' => (int) ($this->view_count ?? 0),
            'helpful_count' => (int) ($this->helpful_count ?? 0),
            'not_helpful_count' => (int) ($this->not_helpful_count ?? 0),
            'display_order' => (int) ($this->display_order ?? 0),
            'is_featured' => (bool) ($this->is_featured ?? false),
            'requires_auth' => (bool) ($this->requires_auth ?? false),
            'icon' => $this->icon ?? null,
            'external_link' => $this->external_link ?? null,
            'meta_description' => $this->meta_description ?? null,
            'slug' => $this->slug ?? null,
            'published_at' => $this->published_at ? $this->published_at->toISOString() : null,
            'is_active' => $this->is_active,
            'helpfulness_ratio' => $this->helpfulness_ratio,
            'reading_time' => $this->reading_time,
            'excerpt' => $this->excerpt,
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
            Log::warning("Error processing array field {$fieldName} for FOQ {$this->id}: " . $e->getMessage());
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
    public function getTagsArray()
    {
        return $this->getCleanArray('tags');
    }

    public function getApplicableUserTypesArray()
    {
        return $this->getCleanArray('applicable_user_types');
    }

    public function getApplicablePlatformsArray()
    {
        return $this->getCleanArray('applicable_platforms');
    }

    // Boot method to handle model events
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($foq) {
            // Generate slug if not provided
            if (empty($foq->slug)) {
                $foq->slug = Str::slug($foq->question);
                
                // Ensure unique slug
                $originalSlug = $foq->slug;
                $counter = 1;
                while (static::where('slug', $foq->slug)->where('id', '!=', $foq->id)->exists()) {
                    $foq->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }

            // Auto-set published_at if status is active and not set
            if ($foq->status === self::STATUS_ACTIVE && !$foq->published_at) {
                $foq->published_at = now();
            }

            // Clean array fields
            $arrayFields = ['tags', 'applicable_user_types', 'applicable_platforms'];
            foreach ($arrayFields as $field) {
                if ($foq->isDirty($field)) {
                    $value = $foq->getAttribute($field);
                    
                    if (is_null($value) || $value === '' || $value === 'null') {
                        $foq->setAttribute($field, []);
                    } elseif (is_string($value)) {
                        $decoded = json_decode($value, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $foq->setAttribute($field, array_values(array_filter($decoded)));
                        } else {
                            $csvArray = array_map('trim', explode(',', $value));
                            $foq->setAttribute($field, array_values(array_filter($csvArray)));
                        }
                    } elseif (is_array($value)) {
                        $foq->setAttribute($field, array_values(array_filter($value)));
                    } else {
                        $foq->setAttribute($field, []);
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

    public static function getPriorities()
    {
        return [
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_LOW => 'Low'
        ];
    }

    public static function getTypes()
    {
        return [
            self::TYPE_FAQ => 'FAQ',
            self::TYPE_GUIDE => 'Guide',
            self::TYPE_TROUBLESHOOT => 'Troubleshoot'
        ];
    }

    public static function getCategories()
    {
        return [
            self::CATEGORY_GENERAL => 'General',
            self::CATEGORY_ACCOUNT => 'Account',
            self::CATEGORY_PAYMENT => 'Payment',
            self::CATEGORY_RIDES => 'Rides',
            self::CATEGORY_DELIVERY => 'Delivery',
            self::CATEGORY_TECHNICAL => 'Technical',
            self::CATEGORY_SAFETY => 'Safety'
        ];
    }

    public static function getUserTypes()
    {
        return [
            self::USER_TYPE_CUSTOMER => 'Customer',
            self::USER_TYPE_DRIVER => 'Driver',
            self::USER_TYPE_MERCHANT => 'Merchant',
            self::USER_TYPE_ADMIN => 'Admin'
        ];
    }

    public static function getPlatforms()
    {
        return [
            self::PLATFORM_WEB => 'Web',
            self::PLATFORM_MOBILE => 'Mobile',
            self::PLATFORM_API => 'API'
        ];
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
                    ->where(function($q) {
                        $q->whereNull('published_at')
                          ->orWhere('published_at', '<=', now());
                    });
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeUnsynced($query)
    {
        return $query->where('firebase_synced', false);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('question', 'like', "%{$search}%")
              ->orWhere('answer', 'like', "%{$search}%")
              ->orWhere('category', 'like', "%{$search}%");
        });
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order', 'asc')
                    ->orderBy('priority', 'desc')
                    ->orderBy('created_at', 'desc');
    }
}