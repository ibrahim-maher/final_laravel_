<?php

namespace App\Modules\Driver\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DriverDocument extends Model
{
    protected $collection = 'driver_documents';
    
    protected $fillable = [
        'driver_firebase_uid',
        'document_type',
        'document_category',
        'document_name',
        'file_path',
        'file_url',
        'file_name',
        'file_size',
        'file_type',
        'mime_type',
        'document_number',
        'issue_date',
        'expiry_date',
        'issuing_authority',
        'issuing_country',
        'issuing_state',
        'verification_status',
        'verification_date',
        'verification_notes',
        'verified_by',
        'rejection_reason',
        'is_required',
        'is_sensitive',
        'download_count',
        'last_downloaded_at',
        'metadata',
        'tags',
        'status',
        'created_at',
        'updated_at'
    ];

    protected $dates = [
        'issue_date',
        'expiry_date',
        'verification_date',
        'last_downloaded_at',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'file_size' => 'integer',
        'download_count' => 'integer',
        'is_required' => 'boolean',
        'is_sensitive' => 'boolean',
        'metadata' => 'array',
        'tags' => 'array'
    ];

    // Document type constants
    const TYPE_DRIVERS_LICENSE = 'drivers_license';
    const TYPE_VEHICLE_REGISTRATION = 'vehicle_registration';
    const TYPE_INSURANCE_CERTIFICATE = 'insurance_certificate';
    const TYPE_BACKGROUND_CHECK = 'background_check';
    const TYPE_DRUG_TEST = 'drug_test';
    const TYPE_VEHICLE_INSPECTION = 'vehicle_inspection';
    const TYPE_PROFILE_PHOTO = 'profile_photo';
    const TYPE_VEHICLE_PHOTOS = 'vehicle_photos';
    const TYPE_BANK_STATEMENT = 'bank_statement';
    const TYPE_TAX_DOCUMENT = 'tax_document';
    const TYPE_IDENTITY_PROOF = 'identity_proof';
    const TYPE_ADDRESS_PROOF = 'address_proof';
    const TYPE_MEDICAL_CERTIFICATE = 'medical_certificate';
    const TYPE_COMMERCIAL_LICENSE = 'commercial_license';
    const TYPE_VEHICLE_PERMIT = 'vehicle_permit';
    const TYPE_OTHER = 'other';

    // Document category constants
    const CATEGORY_IDENTITY = 'identity';
    const CATEGORY_LICENSE = 'license';
    const CATEGORY_VEHICLE = 'vehicle';
    const CATEGORY_INSURANCE = 'insurance';
    const CATEGORY_FINANCIAL = 'financial';
    const CATEGORY_LEGAL = 'legal';
    const CATEGORY_MEDICAL = 'medical';
    const CATEGORY_PHOTO = 'photo';
    const CATEGORY_OTHER = 'other';

    // Verification status constants
    const VERIFICATION_PENDING = 'pending';
    const VERIFICATION_UNDER_REVIEW = 'under_review';
    const VERIFICATION_VERIFIED = 'verified';
    const VERIFICATION_REJECTED = 'rejected';
    const VERIFICATION_EXPIRED = 'expired';

    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_DELETED = 'deleted';

    /**
     * Get the driver this document belongs to
     */
    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_firebase_uid', 'firebase_uid');
    }

    /**
     * Check if document is verified
     */
    public function isVerified()
    {
        return $this->verification_status === self::VERIFICATION_VERIFIED;
    }

    /**
     * Check if document is pending verification
     */
    public function isPending()
    {
        return $this->verification_status === self::VERIFICATION_PENDING;
    }

    /**
     * Check if document is rejected
     */
    public function isRejected()
    {
        return $this->verification_status === self::VERIFICATION_REJECTED;
    }

    /**
     * Check if document is expired
     */
    public function isExpired()
    {
        if (!$this->expiry_date) {
            return false;
        }
        
        return Carbon::parse($this->expiry_date)->isPast();
    }

    /**
     * Check if document expires soon (within 30 days)
     */
    public function expiresSoon($days = 30)
    {
        if (!$this->expiry_date) {
            return false;
        }
        
        return Carbon::parse($this->expiry_date)->isBefore(now()->addDays($days));
    }

    /**
     * Get days until expiry
     */
    public function getDaysUntilExpiryAttribute()
    {
        if (!$this->expiry_date) {
            return null;
        }
        
        return Carbon::parse($this->expiry_date)->diffInDays(now(), false);
    }

    /**
     * Get file size in human readable format
     */
    public function getFileSizeFormattedAttribute()
    {
        if (!$this->file_size) {
            return 'Unknown';
        }
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->file_size;
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get verification status badge color
     */
    public function getVerificationColorAttribute()
    {
        $colors = [
            self::VERIFICATION_PENDING => 'warning',
            self::VERIFICATION_UNDER_REVIEW => 'info',
            self::VERIFICATION_VERIFIED => 'success',
            self::VERIFICATION_REJECTED => 'danger',
            self::VERIFICATION_EXPIRED => 'secondary'
        ];

        return $colors[$this->verification_status] ?? 'secondary';
    }

    /**
     * Get document type icon
     */
    public function getIconAttribute()
    {
        $icons = [
            self::TYPE_DRIVERS_LICENSE => 'fas fa-id-card',
            self::TYPE_VEHICLE_REGISTRATION => 'fas fa-file-contract',
            self::TYPE_INSURANCE_CERTIFICATE => 'fas fa-shield-alt',
            self::TYPE_BACKGROUND_CHECK => 'fas fa-user-check',
            self::TYPE_DRUG_TEST => 'fas fa-vial',
            self::TYPE_VEHICLE_INSPECTION => 'fas fa-search',
            self::TYPE_PROFILE_PHOTO => 'fas fa-portrait',
            self::TYPE_VEHICLE_PHOTOS => 'fas fa-images',
            self::TYPE_BANK_STATEMENT => 'fas fa-university',
            self::TYPE_TAX_DOCUMENT => 'fas fa-receipt',
            self::TYPE_IDENTITY_PROOF => 'fas fa-passport',
            self::TYPE_ADDRESS_PROOF => 'fas fa-home',
            self::TYPE_MEDICAL_CERTIFICATE => 'fas fa-heartbeat',
            self::TYPE_COMMERCIAL_LICENSE => 'fas fa-truck',
            self::TYPE_VEHICLE_PERMIT => 'fas fa-certificate',
            self::TYPE_OTHER => 'fas fa-file'
        ];

        return $icons[$this->document_type] ?? 'fas fa-file';
    }

    /**
     * Get document category color
     */
    public function getCategoryColorAttribute()
    {
        $colors = [
            self::CATEGORY_IDENTITY => 'primary',
            self::CATEGORY_LICENSE => 'success',
            self::CATEGORY_VEHICLE => 'warning',
            self::CATEGORY_INSURANCE => 'info',
            self::CATEGORY_FINANCIAL => 'success',
            self::CATEGORY_LEGAL => 'danger',
            self::CATEGORY_MEDICAL => 'primary',
            self::CATEGORY_PHOTO => 'secondary',
            self::CATEGORY_OTHER => 'dark'
        ];

        return $colors[$this->document_category] ?? 'secondary';
    }

    /**
     * Verify document
     */
    public function verify($verifiedBy = null, $notes = null)
    {
        $this->verification_status = self::VERIFICATION_VERIFIED;
        $this->verification_date = now();
        $this->verified_by = $verifiedBy;
        $this->verification_notes = $notes;
        $this->rejection_reason = null;
        $this->save();
    }

    /**
     * Reject document
     */
    public function reject($reason, $verifiedBy = null, $notes = null)
    {
        $this->verification_status = self::VERIFICATION_REJECTED;
        $this->verification_date = now();
        $this->verified_by = $verifiedBy;
        $this->verification_notes = $notes;
        $this->rejection_reason = $reason;
        $this->save();
    }

    /**
     * Mark document as under review
     */
    public function markUnderReview($verifiedBy = null)
    {
        $this->verification_status = self::VERIFICATION_UNDER_REVIEW;
        $this->verified_by = $verifiedBy;
        $this->save();
    }

    /**
     * Increment download count
     */
    public function incrementDownloadCount()
    {
        $this->download_count = ($this->download_count ?? 0) + 1;
        $this->last_downloaded_at = now();
        $this->save();
    }

    /**
     * Check if document type is required
     */
    public static function isDocumentTypeRequired($type)
    {
        $requiredTypes = [
            self::TYPE_DRIVERS_LICENSE,
            self::TYPE_VEHICLE_REGISTRATION,
            self::TYPE_INSURANCE_CERTIFICATE,
            self::TYPE_PROFILE_PHOTO,
            self::TYPE_VEHICLE_PHOTOS
        ];

        return in_array($type, $requiredTypes);
    }

    /**
     * Check if document type is sensitive
     */
    public static function isDocumentTypeSensitive($type)
    {
        $sensitiveTypes = [
            self::TYPE_DRIVERS_LICENSE,
            self::TYPE_BACKGROUND_CHECK,
            self::TYPE_BANK_STATEMENT,
            self::TYPE_TAX_DOCUMENT,
            self::TYPE_MEDICAL_CERTIFICATE
        ];

        return in_array($type, $sensitiveTypes);
    }

    /**
     * Scope: Verified documents
     */
    public function scopeVerified($query)
    {
        return $query->where('verification_status', self::VERIFICATION_VERIFIED);
    }

    /**
     * Scope: Pending documents
     */
    public function scopePending($query)
    {
        return $query->where('verification_status', self::VERIFICATION_PENDING);
    }

    /**
     * Scope: Rejected documents
     */
    public function scopeRejected($query)
    {
        return $query->where('verification_status', self::VERIFICATION_REJECTED);
    }

    /**
     * Scope: Expired documents
     */
    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now());
    }

    /**
     * Scope: Expiring soon
     */
    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }

    /**
     * Scope: Required documents
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope: Sensitive documents
     */
    public function scopeSensitive($query)
    {
        return $query->where('is_sensitive', true);
    }

    /**
     * Scope: By document type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('document_type', $type);
    }

    /**
     * Scope: By category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('document_category', $category);
    }

    /**
     * Scope: Active documents
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope: Search documents
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('document_name', 'like', "%{$term}%")
              ->orWhere('file_name', 'like', "%{$term}%")
              ->orWhere('document_number', 'like', "%{$term}%")
              ->orWhere('issuing_authority', 'like', "%{$term}%");
        });
    }

    /**
     * Get available document types
     */
    public static function getDocumentTypes()
    {
        return [
            self::TYPE_DRIVERS_LICENSE => 'Driver\'s License',
            self::TYPE_VEHICLE_REGISTRATION => 'Vehicle Registration',
            self::TYPE_INSURANCE_CERTIFICATE => 'Insurance Certificate',
            self::TYPE_BACKGROUND_CHECK => 'Background Check',
            self::TYPE_DRUG_TEST => 'Drug Test Report',
            self::TYPE_VEHICLE_INSPECTION => 'Vehicle Inspection',
            self::TYPE_PROFILE_PHOTO => 'Profile Photo',
            self::TYPE_VEHICLE_PHOTOS => 'Vehicle Photos',
            self::TYPE_BANK_STATEMENT => 'Bank Statement',
            self::TYPE_TAX_DOCUMENT => 'Tax Document',
            self::TYPE_IDENTITY_PROOF => 'Identity Proof',
            self::TYPE_ADDRESS_PROOF => 'Address Proof',
            self::TYPE_MEDICAL_CERTIFICATE => 'Medical Certificate',
            self::TYPE_COMMERCIAL_LICENSE => 'Commercial License',
            self::TYPE_VEHICLE_PERMIT => 'Vehicle Permit',
            self::TYPE_OTHER => 'Other'
        ];
    }

    /**
     * Get available document categories
     */
    public static function getDocumentCategories()
    {
        return [
            self::CATEGORY_IDENTITY => 'Identity',
            self::CATEGORY_LICENSE => 'License',
            self::CATEGORY_VEHICLE => 'Vehicle',
            self::CATEGORY_INSURANCE => 'Insurance',
            self::CATEGORY_FINANCIAL => 'Financial',
            self::CATEGORY_LEGAL => 'Legal',
            self::CATEGORY_MEDICAL => 'Medical',
            self::CATEGORY_PHOTO => 'Photo',
            self::CATEGORY_OTHER => 'Other'
        ];
    }

    /**
     * Get category for document type
     */
    public static function getCategoryForType($type)
    {
        $categoryMap = [
            self::TYPE_DRIVERS_LICENSE => self::CATEGORY_LICENSE,
            self::TYPE_VEHICLE_REGISTRATION => self::CATEGORY_VEHICLE,
            self::TYPE_INSURANCE_CERTIFICATE => self::CATEGORY_INSURANCE,
            self::TYPE_BACKGROUND_CHECK => self::CATEGORY_LEGAL,
            self::TYPE_DRUG_TEST => self::CATEGORY_MEDICAL,
            self::TYPE_VEHICLE_INSPECTION => self::CATEGORY_VEHICLE,
            self::TYPE_PROFILE_PHOTO => self::CATEGORY_PHOTO,
            self::TYPE_VEHICLE_PHOTOS => self::CATEGORY_PHOTO,
            self::TYPE_BANK_STATEMENT => self::CATEGORY_FINANCIAL,
            self::TYPE_TAX_DOCUMENT => self::CATEGORY_FINANCIAL,
            self::TYPE_IDENTITY_PROOF => self::CATEGORY_IDENTITY,
            self::TYPE_ADDRESS_PROOF => self::CATEGORY_IDENTITY,
            self::TYPE_MEDICAL_CERTIFICATE => self::CATEGORY_MEDICAL,
            self::TYPE_COMMERCIAL_LICENSE => self::CATEGORY_LICENSE,
            self::TYPE_VEHICLE_PERMIT => self::CATEGORY_VEHICLE,
            self::TYPE_OTHER => self::CATEGORY_OTHER
        ];

        return $categoryMap[$type] ?? self::CATEGORY_OTHER;
    }
}