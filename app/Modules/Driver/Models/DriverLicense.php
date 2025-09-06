<?php

namespace App\Modules\Driver\Models;

    use Illuminate\Database\Eloquent\Model;
    use Carbon\Carbon;

    class DriverLicense extends Model
    {
        protected $collection = 'driver_licenses';
        
        protected $fillable = [
            'driver_firebase_uid',
            'license_number',
            'license_class',
            'license_type',
            'issue_date',
            'expiry_date',
            'issuing_state',
            'issuing_country',
            'issuing_authority',
            'status',
            'verification_status',
            'verification_date',
            'verified_by',
            'restrictions',
            'endorsements',
            'points',
            'is_primary',
            'document_id',
            'front_image_url',
            'back_image_url',
            'metadata',
            'created_at',
            'updated_at'
        ];

        protected $dates = [
            'issue_date',
            'expiry_date',
            'verification_date',
            'created_at',
            'updated_at'
        ];

        protected $casts = [
            'points' => 'integer',
            'is_primary' => 'boolean',
            'restrictions' => 'array',
            'endorsements' => 'array',
            'metadata' => 'array'
        ];

        // License class constants
        const CLASS_A = 'class_a';
        const CLASS_B = 'class_b';
        const CLASS_C = 'class_c';
        const CLASS_D = 'class_d';
        const CLASS_M = 'class_m';
        const CLASS_CDL_A = 'cdl_a';
        const CLASS_CDL_B = 'cdl_b';
        const CLASS_CDL_C = 'cdl_c';

        // License type constants
        const TYPE_REGULAR = 'regular';
        const TYPE_COMMERCIAL = 'commercial';
        const TYPE_MOTORCYCLE = 'motorcycle';
        const TYPE_CHAUFFEUR = 'chauffeur';
        const TYPE_PROVISIONAL = 'provisional';
        const TYPE_RESTRICTED = 'restricted';

        // Status constants
        const STATUS_VALID = 'valid';
        const STATUS_EXPIRED = 'expired';
        const STATUS_SUSPENDED = 'suspended';
        const STATUS_REVOKED = 'revoked';
        const STATUS_PENDING_RENEWAL = 'pending_renewal';

        // Verification status constants
        const VERIFICATION_PENDING = 'pending';
        const VERIFICATION_VERIFIED = 'verified';
        const VERIFICATION_REJECTED = 'rejected';

        /**
         * Get the driver this license belongs to
         */
        public function driver()
        {
            return $this->belongsTo(Driver::class, 'driver_firebase_uid', 'firebase_uid');
        }

        /**
         * Get the associated document
         */
        public function document()
        {
            return $this->belongsTo(DriverDocument::class, 'document_id');
        }

        /**
         * Check if license is valid
         */
        public function isValid()
        {
            return $this->status === self::STATUS_VALID && !$this->isExpired();
        }

        /**
         * Check if license is expired
         */
        public function isExpired()
        {
            return $this->expiry_date && Carbon::parse($this->expiry_date)->isPast();
        }

        /**
         * Check if license expires soon (within 30 days)
         */
        public function expiresSoon($days = 30)
        {
            if (!$this->expiry_date) {
                return false;
            }
            
            return Carbon::parse($this->expiry_date)->isBefore(now()->addDays($days));
        }

        /**
         * Check if license is verified
         */
        public function isVerified()
        {
            return $this->verification_status === self::VERIFICATION_VERIFIED;
        }

        /**
         * Check if license is commercial
         */
        public function isCommercial()
        {
            return $this->license_type === self::TYPE_COMMERCIAL || 
                in_array($this->license_class, [self::CLASS_CDL_A, self::CLASS_CDL_B, self::CLASS_CDL_C]);
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
         * Get license age in years
         */
        public function getAgeAttribute()
        {
            if (!$this->issue_date) {
                return null;
            }
            
            return Carbon::parse($this->issue_date)->diffInYears(now());
        }

        /**
         * Get status badge color
         */
        public function getStatusColorAttribute()
        {
            if ($this->isExpired()) {
                return 'danger';
            }
            
            if ($this->expiresSoon()) {
                return 'warning';
            }
            
            $colors = [
                self::STATUS_VALID => 'success',
                self::STATUS_EXPIRED => 'danger',
                self::STATUS_SUSPENDED => 'warning',
                self::STATUS_REVOKED => 'danger',
                self::STATUS_PENDING_RENEWAL => 'info'
            ];

            return $colors[$this->status] ?? 'secondary';
        }

        /**
         * Get verification status color
         */
        public function getVerificationColorAttribute()
        {
            $colors = [
                self::VERIFICATION_PENDING => 'warning',
                self::VERIFICATION_VERIFIED => 'success',
                self::VERIFICATION_REJECTED => 'danger'
            ];

            return $colors[$this->verification_status] ?? 'secondary';
        }

        /**
         * Get formatted license class
         */
        public function getLicenseClassFormattedAttribute()
        {
            $classes = [
                self::CLASS_A => 'Class A',
                self::CLASS_B => 'Class B',
                self::CLASS_C => 'Class C',
                self::CLASS_D => 'Class D',
                self::CLASS_M => 'Class M (Motorcycle)',
                self::CLASS_CDL_A => 'CDL Class A',
                self::CLASS_CDL_B => 'CDL Class B',
                self::CLASS_CDL_C => 'CDL Class C'
            ];

            return $classes[$this->license_class] ?? ucfirst(str_replace('_', ' ', $this->license_class));
        }

        /**
         * Get formatted restrictions
         */
        public function getRestrictionsFormattedAttribute()
        {
            if (!$this->restrictions || !is_array($this->restrictions)) {
                return 'None';
            }
            
            return implode(', ', $this->restrictions);
        }

        /**
         * Get formatted endorsements
         */
        public function getEndorsementsFormattedAttribute()
        {
            if (!$this->endorsements || !is_array($this->endorsements)) {
                return 'None';
            }
            
            return implode(', ', $this->endorsements);
        }

        /**
         * Verify license
         */
        public function verify($verifiedBy = null)
        {
            $this->verification_status = self::VERIFICATION_VERIFIED;
            $this->verification_date = now();
            $this->verified_by = $verifiedBy;
            $this->save();
        }

        /**
         * Reject license verification
         */
        public function reject($verifiedBy = null)
        {
            $this->verification_status = self::VERIFICATION_REJECTED;
            $this->verification_date = now();
            $this->verified_by = $verifiedBy;
            $this->save();
        }

        /**
         * Update license status
         */
        public function updateStatus($status)
        {
            $this->status = $status;
            $this->save();
        }

        /**
         * Add restriction
         */
        public function addRestriction($restriction)
        {
            $restrictions = $this->restrictions ?? [];
            if (!in_array($restriction, $restrictions)) {
                $restrictions[] = $restriction;
                $this->restrictions = $restrictions;
                $this->save();
            }
        }

        /**
         * Remove restriction
         */
        public function removeRestriction($restriction)
        {
            $restrictions = $this->restrictions ?? [];
            $key = array_search($restriction, $restrictions);
            if ($key !== false) {
                unset($restrictions[$key]);
                $this->restrictions = array_values($restrictions);
                $this->save();
            }
        }

        /**
         * Add endorsement
         */
        public function addEndorsement($endorsement)
        {
            $endorsements = $this->endorsements ?? [];
            if (!in_array($endorsement, $endorsements)) {
                $endorsements[] = $endorsement;
                $this->endorsements = $endorsements;
                $this->save();
            }
        }

        /**
         * Remove endorsement
         */
        public function removeEndorsement($endorsement)
        {
            $endorsements = $this->endorsements ?? [];
            $key = array_search($endorsement, $endorsements);
            if ($key !== false) {
                unset($endorsements[$key]);
                $this->endorsements = array_values($endorsements);
                $this->save();
            }
        }

        /**
         * Scope: Valid licenses
         */
        public function scopeValid($query)
        {
            return $query->where('status', self::STATUS_VALID)
                        ->where('expiry_date', '>', now());
        }

        /**
         * Scope: Expired licenses
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
         * Scope: Verified licenses
         */
        public function scopeVerified($query)
        {
            return $query->where('verification_status', self::VERIFICATION_VERIFIED);
        }

        /**
         * Scope: Commercial licenses
         */
        public function scopeCommercial($query)
        {
            return $query->where(function ($q) {
                $q->where('license_type', self::TYPE_COMMERCIAL)
                ->orWhereIn('license_class', [self::CLASS_CDL_A, self::CLASS_CDL_B, self::CLASS_CDL_C]);
            });
        }

        /**
         * Scope: Primary licenses
         */
        public function scopePrimary($query)
        {
            return $query->where('is_primary', true);
        }

        /**
         * Scope: By class
         */
        public function scopeByClass($query, $class)
        {
            return $query->where('license_class', $class);
        }

        /**
         * Scope: By state
         */
        public function scopeByState($query, $state)
        {
            return $query->where('issuing_state', $state);
        }

        /**
         * Get available license classes
         */
        public static function getLicenseClasses()
        {
            return [
                self::CLASS_A => 'Class A - Heavy trucks, tractor-trailers',
                self::CLASS_B => 'Class B - Large trucks, buses, segmented buses',
                self::CLASS_C => 'Class C - Regular vehicles',
                self::CLASS_D => 'Class D - Regular driver license',
                self::CLASS_M => 'Class M - Motorcycles',
                self::CLASS_CDL_A => 'CDL Class A - Commercial heavy vehicles',
                self::CLASS_CDL_B => 'CDL Class B - Commercial large vehicles',
                self::CLASS_CDL_C => 'CDL Class C - Commercial regular vehicles'
            ];
        }

        /**
         * Get available license types
         */
        public static function getLicenseTypes()
        {
            return [
                self::TYPE_REGULAR => 'Regular License',
                self::TYPE_COMMERCIAL => 'Commercial License',
                self::TYPE_MOTORCYCLE => 'Motorcycle License',
                self::TYPE_CHAUFFEUR => 'Chauffeur License',
                self::TYPE_PROVISIONAL => 'Provisional License',
                self::TYPE_RESTRICTED => 'Restricted License'
            ];
        }

        /**
         * Get common restrictions
         */
        public static function getCommonRestrictions()
        {
            return [
                'corrective_lenses' => 'Must wear corrective lenses',
                'daylight_only' => 'Daylight driving only',
                'no_interstate' => 'No interstate driving',
                'automatic_only' => 'Automatic transmission only',
                'hearing_aid' => 'Must wear hearing aid',
                'left_mirror' => 'Must have left outside mirror',
                'right_mirror' => 'Must have right outside mirror',
                'speed_limit' => 'Speed restriction',
                'area_restriction' => 'Area restriction'
            ];
        }

        /**
         * Get common endorsements
         */
        public static function getCommonEndorsements()
        {
            return [
                'h' => 'H - Hazardous materials',
                'n' => 'N - Tank vehicles',
                'p' => 'P - Passenger vehicles',
                's' => 'S - School bus',
                't' => 'T - Double/triple trailers',
                'x' => 'X - Combination tank vehicle/hazmat'
            ];
        }
    }