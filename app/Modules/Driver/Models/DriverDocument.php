<?php

namespace App\Modules\Driver\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;
use App\Traits\FirebaseSyncable;

class DriverDocument extends Model
{
    use HasFactory, FirebaseSyncable;

    protected $fillable = [
        'driver_firebase_uid',
        'vehicle_id',
        'document_type',
        'document_name',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'original_name',
        'upload_status',
        'verification_status',
        'verification_date',
        'verified_by',
        'verification_notes',
        'expiry_date',
        'is_required',
        'uploaded_by',
        'firebase_synced',
        'firebase_synced_at'
    ];

    protected $casts = [
        'verification_date' => 'datetime',
        'expiry_date' => 'date',
        'file_size' => 'integer',
        'is_required' => 'boolean',
        'firebase_synced' => 'boolean',
        'firebase_synced_at' => 'datetime',
    ];

    // Firebase sync configuration
    protected $firebaseCollection = 'driver_documents';
    protected $firebaseKey = 'id';

    // Document type constants
    const TYPE_PROFILE_PHOTO = 'profile_photo';
    const TYPE_DRIVERS_LICENSE = 'drivers_license';
    const TYPE_VEHICLE_REGISTRATION = 'vehicle_registration';
    const TYPE_VEHICLE_PHOTO = 'vehicle_photo';
    const TYPE_INSURANCE_CERTIFICATE = 'insurance_certificate';
    const TYPE_INSPECTION_CERTIFICATE = 'inspection_certificate';
    const TYPE_BACKGROUND_CHECK = 'background_check';
    const TYPE_BANK_STATEMENT = 'bank_statement';
    const TYPE_TAX_DOCUMENT = 'tax_document';
    const TYPE_MEDICAL_CERTIFICATE = 'medical_certificate';
    const TYPE_OTHER = 'other';

    // Upload status constants
    const UPLOAD_PENDING = 'pending';
    const UPLOAD_SUCCESS = 'success';
    const UPLOAD_FAILED = 'failed';

    // Verification status constants
    const VERIFICATION_PENDING = 'pending';
    const VERIFICATION_VERIFIED = 'verified';
    const VERIFICATION_REJECTED = 'rejected';

    // Relationships
    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_firebase_uid', 'firebase_uid');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    // Scopes
    public function scopeVerified($query)
    {
        return $query->where('verification_status', self::VERIFICATION_VERIFIED);
    }

    public function scopePending($query)
    {
        return $query->where('verification_status', self::VERIFICATION_PENDING);
    }

    public function scopeRejected($query)
    {
        return $query->where('verification_status', self::VERIFICATION_REJECTED);
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now())
                     ->whereNotNull('expiry_date');
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->whereBetween('expiry_date', [now(), now()->addDays($days)])
                     ->whereNotNull('expiry_date');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('document_type', $type);
    }

    public function scopeDriverDocuments($query)
    {
        return $query->whereNull('vehicle_id');
    }

    public function scopeVehicleDocuments($query)
    {
        return $query->whereNotNull('vehicle_id');
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeUnsynced($query)
    {
        return $query->where('firebase_synced', false);
    }

    // Accessors
    public function getFileUrlAttribute()
    {
        if (!$this->file_path) {
            return null;
        }

        return Storage::disk('public')->url($this->file_path);
    }

    public function getFileSizeHumanAttribute()
    {
        if (!$this->file_size) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    public function getStatusBadgeClassAttribute()
    {
        $classes = [
            self::VERIFICATION_VERIFIED => 'badge-success',
            self::VERIFICATION_PENDING => 'badge-warning',
            self::VERIFICATION_REJECTED => 'badge-danger'
        ];

        return $classes[$this->verification_status] ?? 'badge-secondary';
    }

    public function getUploadStatusBadgeClassAttribute()
    {
        $classes = [
            self::UPLOAD_SUCCESS => 'badge-success',
            self::UPLOAD_PENDING => 'badge-warning',
            self::UPLOAD_FAILED => 'badge-danger'
        ];

        return $classes[$this->upload_status] ?? 'badge-secondary';
    }

    public function getIsImageAttribute()
    {
        return in_array($this->mime_type, [
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'
        ]);
    }

    public function getIsPdfAttribute()
    {
        return $this->mime_type === 'application/pdf';
    }

    // Helper methods
    public function isVerified()
    {
        return $this->verification_status === self::VERIFICATION_VERIFIED;
    }

    public function isPending()
    {
        return $this->verification_status === self::VERIFICATION_PENDING;
    }

    public function isRejected()
    {
        return $this->verification_status === self::VERIFICATION_REJECTED;
    }

    public function isExpired()
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function isExpiringSoon($days = 30)
    {
        return $this->expiry_date && 
               $this->expiry_date->between(now(), now()->addDays($days));
    }

    public function isDriverDocument()
    {
        return is_null($this->vehicle_id);
    }

    public function isVehicleDocument()
    {
        return !is_null($this->vehicle_id);
    }

    public function fileExists()
    {
        return $this->file_path && Storage::disk('public')->exists($this->file_path);
    }

    public function getDownloadUrl()
    {
        if (!$this->fileExists()) {
            return null;
        }

        return route('admin.documents.download', $this->id);
    }

    // Firebase sync
    public function toFirebaseArray()
    {
        return [
            'id' => $this->id,
            'driver_firebase_uid' => $this->driver_firebase_uid ?? '',
            'vehicle_id' => $this->vehicle_id,
            'document_type' => $this->document_type ?? '',
            'document_name' => $this->document_name ?? '',
            'file_name' => $this->file_name ?? '',
            'file_size' => $this->file_size ?? 0,
            'mime_type' => $this->mime_type ?? '',
            'original_name' => $this->original_name ?? '',
            'upload_status' => $this->upload_status ?? 'pending',
            'verification_status' => $this->verification_status ?? 'pending',
            'verification_date' => $this->verification_date ? $this->verification_date->toISOString() : null,
            'verified_by' => $this->verified_by ?? '',
            'verification_notes' => $this->verification_notes ?? '',
            'expiry_date' => $this->expiry_date ? $this->expiry_date->format('Y-m-d') : '',
            'is_required' => $this->is_required ?? false,
            'uploaded_by' => $this->uploaded_by ?? '',
            'created_at' => $this->created_at ? $this->created_at->toISOString() : '',
            'updated_at' => $this->updated_at ? $this->updated_at->toISOString() : '',
            'sync_updated_at' => now()->toISOString(),
        ];
    }

    // Static methods
    public static function getDocumentTypes()
    {
        return [
            self::TYPE_PROFILE_PHOTO => 'Profile Photo',
            self::TYPE_DRIVERS_LICENSE => 'Driver\'s License',
            self::TYPE_VEHICLE_REGISTRATION => 'Vehicle Registration',
            self::TYPE_VEHICLE_PHOTO => 'Vehicle Photo',
            self::TYPE_INSURANCE_CERTIFICATE => 'Insurance Certificate',
            self::TYPE_INSPECTION_CERTIFICATE => 'Inspection Certificate',
            self::TYPE_BACKGROUND_CHECK => 'Background Check',
            self::TYPE_BANK_STATEMENT => 'Bank Statement',
            self::TYPE_TAX_DOCUMENT => 'Tax Document',
            self::TYPE_MEDICAL_CERTIFICATE => 'Medical Certificate',
            self::TYPE_OTHER => 'Other'
        ];
    }

    public static function getDriverDocumentTypes()
    {
        return [
            self::TYPE_PROFILE_PHOTO => 'Profile Photo',
            self::TYPE_DRIVERS_LICENSE => 'Driver\'s License',
            self::TYPE_BACKGROUND_CHECK => 'Background Check',
            self::TYPE_BANK_STATEMENT => 'Bank Statement',
            self::TYPE_TAX_DOCUMENT => 'Tax Document',
            self::TYPE_MEDICAL_CERTIFICATE => 'Medical Certificate',
            self::TYPE_OTHER => 'Other'
        ];
    }

    public static function getVehicleDocumentTypes()
    {
        return [
            self::TYPE_VEHICLE_REGISTRATION => 'Vehicle Registration',
            self::TYPE_VEHICLE_PHOTO => 'Vehicle Photo',
            self::TYPE_INSURANCE_CERTIFICATE => 'Insurance Certificate',
            self::TYPE_INSPECTION_CERTIFICATE => 'Inspection Certificate',
            self::TYPE_OTHER => 'Other'
        ];
    }

    public static function getUploadStatuses()
    {
        return [
            self::UPLOAD_PENDING => 'Pending',
            self::UPLOAD_SUCCESS => 'Success',
            self::UPLOAD_FAILED => 'Failed'
        ];
    }

    public static function getVerificationStatuses()
    {
        return [
            self::VERIFICATION_PENDING => 'Pending',
            self::VERIFICATION_VERIFIED => 'Verified',
            self::VERIFICATION_REJECTED => 'Rejected'
        ];
    }

    public static function getRequiredDriverDocuments()
    {
        return [
            self::TYPE_PROFILE_PHOTO,
            self::TYPE_DRIVERS_LICENSE,
            self::TYPE_BACKGROUND_CHECK
        ];
    }

    public static function getRequiredVehicleDocuments()
    {
        return [
            self::TYPE_VEHICLE_REGISTRATION,
            self::TYPE_VEHICLE_PHOTO,
            self::TYPE_INSURANCE_CERTIFICATE
        ];
    }

    public static function getAllowedMimeTypes()
    {
        return [
            'image/jpeg',
            'image/jpg', 
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
    }

    public static function getMaxFileSize()
    {
        return 5120; // 5MB in KB
    }
}