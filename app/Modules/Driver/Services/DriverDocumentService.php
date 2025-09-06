<?php

namespace App\Modules\Driver\Services;

use App\Modules\Driver\Models\DriverDocument;
use App\Modules\Driver\Models\DriverActivity;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class DriverDocumentService extends BaseService
{
    protected $collection = 'driver_documents';

    /**
     * Get driver documents
     */
    public function getDriverDocuments(string $driverFirebaseUid, array $filters = []): array
    {
        try {
            Log::info('Getting documents for driver', ['firebase_uid' => $driverFirebaseUid]);
            
            $allDocuments = $this->getAllDocuments(['limit' => $filters['limit'] ?? 1000]);
            
            $driverDocuments = $this->filterByField($allDocuments, 'driver_firebase_uid', $driverFirebaseUid);
            
            return $this->applyFilters($driverDocuments, $filters);
        } catch (\Exception $e) {
            Log::error('Error getting driver documents: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Upload driver document
     */
    public function uploadDocument(string $driverFirebaseUid, UploadedFile $file, array $documentData): ?array
    {
        try {
            Log::info('Uploading document for driver', [
                'firebase_uid' => $driverFirebaseUid,
                'document_type' => $documentData['document_type'] ?? 'unknown'
            ]);
            
            // Store file
            $filePath = "drivers/{$driverFirebaseUid}/documents/" . time() . '_' . $file->getClientOriginalName();
            $storedPath = Storage::disk('public')->put($filePath, file_get_contents($file));
            
            if (!$storedPath) {
                Log::error('Failed to store document file');
                return null;
            }
            
            // Prepare document data
            $documentRecord = $this->prepareDocumentData($driverFirebaseUid, $file, $documentData, $filePath);
            
            $result = $this->createDocument($documentRecord);
            
            if ($result) {
                // Create activity
                $this->createDriverActivity($driverFirebaseUid, DriverActivity::TYPE_DOCUMENT_UPLOAD, [
                    'title' => 'Document Uploaded',
                    'description' => "Document uploaded: " . ($documentData['document_name'] ?? $file->getClientOriginalName()),
                    'metadata' => [
                        'document_id' => $result['id'] ?? null,
                        'document_type' => $documentData['document_type']
                    ]
                ]);
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Error uploading document: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get document by ID
     */
    public function getDocumentById(string $documentId): ?array
    {
        return parent::getDocumentById($documentId);
    }

    /**
     * Update document
     */
    public function updateDocument(string $documentId, array $documentData): bool
    {
        try {
            Log::info('Updating document', ['document_id' => $documentId]);
            
            return parent::updateDocument($documentId, $documentData);
        } catch (\Exception $e) {
            Log::error('Error updating document: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete document
     */
    public function deleteDocument(string $documentId): bool
    {
        try {
            Log::info('Deleting document', ['document_id' => $documentId]);
            
            // Get document data before deletion to remove file
            $document = parent::getDocumentById($documentId);
            
            $result = parent::deleteDocument($documentId);
            
            if ($result && $document) {
                // Delete file if exists
                if (isset($document['file_path'])) {
                    Storage::disk('public')->delete($document['file_path']);
                }
                
                // Create activity
                if (isset($document['driver_firebase_uid'])) {
                    $this->createDriverActivity($document['driver_firebase_uid'], DriverActivity::TYPE_DOCUMENT_UPLOAD, [
                        'title' => 'Document Deleted',
                        'description' => "Document deleted: " . ($document['document_name'] ?? 'Unknown'),
                        'metadata' => ['document_id' => $documentId]
                    ]);
                }
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Error deleting document: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify document
     */
    public function verifyDocument(string $documentId, string $verifiedBy = null, string $notes = null): bool
    {
        try {
            Log::info('Verifying document', ['document_id' => $documentId]);
            
            $updateData = [
                'verification_status' => DriverDocument::VERIFICATION_VERIFIED,
                'verification_date' => now()->toDateTimeString(),
                'verified_by' => $verifiedBy,
                'verification_notes' => $notes
            ];
            
            $result = $this->updateDocument($documentId, $updateData);
            
            if ($result) {
                // Get document to create activity
                $document = $this->getDocumentById($documentId);
                if ($document && isset($document['driver_firebase_uid'])) {
                    $this->createDriverActivity($document['driver_firebase_uid'], DriverActivity::TYPE_DOCUMENT_UPLOAD, [
                        'title' => 'Document Verified',
                        'description' => "Document has been verified: " . ($document['document_name'] ?? 'Unknown'),
                        'metadata' => [
                            'document_id' => $documentId,
                            'verified_by' => $verifiedBy
                        ]
                    ]);
                }
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Error verifying document: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Reject document
     */
    public function rejectDocument(string $documentId, string $rejectedBy = null, string $reason = null): bool
    {
        try {
            Log::info('Rejecting document', ['document_id' => $documentId]);
            
            $updateData = [
                'verification_status' => DriverDocument::VERIFICATION_REJECTED,
                'verification_date' => now()->toDateTimeString(),
                'verified_by' => $rejectedBy,
                'verification_notes' => $reason
            ];
            
            $result = $this->updateDocument($documentId, $updateData);
            
            if ($result) {
                // Get document to create activity
                $document = $this->getDocumentById($documentId);
                if ($document && isset($document['driver_firebase_uid'])) {
                    $this->createDriverActivity($document['driver_firebase_uid'], DriverActivity::TYPE_DOCUMENT_UPLOAD, [
                        'title' => 'Document Rejected',
                        'description' => "Document has been rejected: " . ($document['document_name'] ?? 'Unknown'),
                        'metadata' => [
                            'document_id' => $documentId,
                            'rejected_by' => $rejectedBy,
                            'reason' => $reason
                        ]
                    ]);
                }
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Error rejecting document: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get documents by type
     */
    public function getDocumentsByType(string $documentType): array
    {
        return $this->getAllDocuments(['document_type' => $documentType, 'limit' => 1000]);
    }

    /**
     * Get documents by verification status
     */
    public function getDocumentsByVerificationStatus(string $verificationStatus): array
    {
        return $this->getAllDocuments(['verification_status' => $verificationStatus, 'limit' => 1000]);
    }

    /**
     * Get pending documents
     */
    public function getPendingDocuments(): array
    {
        return $this->getDocumentsByVerificationStatus(DriverDocument::VERIFICATION_PENDING);
    }

    /**
     * Get verified documents for driver
     */
    public function getVerifiedDocumentsForDriver(string $driverFirebaseUid): array
    {
        return $this->getDriverDocuments($driverFirebaseUid, [
            'verification_status' => DriverDocument::VERIFICATION_VERIFIED
        ]);
    }

    /**
     * Get expired documents
     */
    public function getExpiredDocuments(): array
    {
        $allDocuments = $this->getAllDocuments(['limit' => 10000]);
        $today = now()->format('Y-m-d');
        
        return array_filter($allDocuments, function($document) use ($today) {
            return isset($document['expiry_date']) && 
                   $document['expiry_date'] < $today &&
                   ($document['status'] ?? '') === DriverDocument::STATUS_ACTIVE;
        });
    }

    /**
     * Get documents expiring soon
     */
    public function getDocumentsExpiringSoon(int $days = 30): array
    {
        $allDocuments = $this->getAllDocuments(['limit' => 10000]);
        $cutoffDate = now()->addDays($days)->format('Y-m-d');
        $today = now()->format('Y-m-d');
        
        return array_filter($allDocuments, function($document) use ($today, $cutoffDate) {
            return isset($document['expiry_date']) && 
                   $document['expiry_date'] >= $today &&
                   $document['expiry_date'] <= $cutoffDate &&
                   ($document['status'] ?? '') === DriverDocument::STATUS_ACTIVE;
        });
    }

    /**
     * Get required documents for driver
     */
    public function getRequiredDocumentsForDriver(string $driverFirebaseUid): array
    {
        return $this->getDriverDocuments($driverFirebaseUid, [
            'is_required' => true
        ]);
    }

    /**
     * Check if driver has all required documents
     */
    public function hasAllRequiredDocuments(string $driverFirebaseUid): bool
    {
        $requiredTypes = DriverDocument::getRequiredDocumentTypes();
        $driverDocuments = $this->getVerifiedDocumentsForDriver($driverFirebaseUid);
        
        $driverDocumentTypes = array_unique(array_column($driverDocuments, 'document_type'));
        
        foreach ($requiredTypes as $requiredType) {
            if (!in_array($requiredType, $driverDocumentTypes)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get missing required documents for driver
     */
    public function getMissingRequiredDocuments(string $driverFirebaseUid): array
    {
        $requiredTypes = DriverDocument::getRequiredDocumentTypes();
        $driverDocuments = $this->getVerifiedDocumentsForDriver($driverFirebaseUid);
        
        $driverDocumentTypes = array_unique(array_column($driverDocuments, 'document_type'));
        
        return array_diff($requiredTypes, $driverDocumentTypes);
    }

    /**
     * Prepare document data for creation
     */
    private function prepareDocumentData(string $driverFirebaseUid, UploadedFile $file, array $documentData, string $filePath): array
    {
        return [
            'driver_firebase_uid' => $driverFirebaseUid,
            'document_type' => $documentData['document_type'],
            'document_category' => DriverDocument::getCategoryForType($documentData['document_type']),
            'document_name' => $documentData['document_name'] ?? $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_url' => Storage::disk('public')->url($filePath),
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'file_type' => $file->getClientOriginalExtension(),
            'mime_type' => $file->getMimeType(),
            'document_number' => $documentData['document_number'] ?? null,
            'issue_date' => $documentData['issue_date'] ?? null,
            'expiry_date' => $documentData['expiry_date'] ?? null,
            'issuing_authority' => $documentData['issuing_authority'] ?? null,
            'issuing_country' => $documentData['issuing_country'] ?? null,
            'issuing_state' => $documentData['issuing_state'] ?? null,
            'verification_status' => DriverDocument::VERIFICATION_PENDING,
            'is_required' => DriverDocument::isDocumentTypeRequired($documentData['document_type']),
            'is_sensitive' => DriverDocument::isDocumentTypeSensitive($documentData['document_type']),
            'status' => DriverDocument::STATUS_ACTIVE
        ];
    }

    /**
     * Create driver activity
     */
    private function createDriverActivity(string $firebaseUid, string $type, array $data): void
    {
        try {
            DriverActivity::createActivity($firebaseUid, $type, $data);
        } catch (\Exception $e) {
            Log::warning('Failed to create driver activity', [
                'firebase_uid' => $firebaseUid,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check if document matches search query
     */
    protected function matchesSearch(array $document, string $search): bool
    {
        $searchableFields = ['document_name', 'document_type', 'document_number', 'issuing_authority'];
        
        foreach ($searchableFields as $field) {
            if (isset($document[$field]) && stripos($document[$field], $search) !== false) {
                return true;
            }
        }
        
        return false;
    }
}