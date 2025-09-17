<?php

namespace App\Modules\Driver\Services;

use App\Modules\Driver\Models\DriverDocument;
use App\Modules\Driver\Models\Driver;
use App\Modules\Driver\Models\Vehicle;
use App\Services\FirestoreService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;

class DriverDocumentService
{
    protected $firestoreService;

    public function __construct()
    {
        $this->firestoreService = new FirestoreService();
    }

    /**
     * Get driver documents
     */
    public function getDriverDocuments($driverFirebaseUid)
    {
        try {
            return DriverDocument::where('driver_firebase_uid', $driverFirebaseUid)
                ->whereNull('vehicle_id')
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            Log::error('Error getting driver documents: ' . $e->getMessage(), [
                'driver_uid' => $driverFirebaseUid
            ]);
            return collect([]);
        }
    }

    /**
     * Get vehicle documents
     */
    public function getVehicleDocuments($vehicleId)
    {
        try {
            return DriverDocument::where('vehicle_id', $vehicleId)
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            Log::error('Error getting vehicle documents: ' . $e->getMessage(), [
                'vehicle_id' => $vehicleId
            ]);
            return collect([]);
        }
    }

    /**
     * Get document by ID
     */
    public function getDocumentById($documentId)
    {
        try {
            return DriverDocument::find($documentId);
        } catch (\Exception $e) {
            Log::error('Error getting document by ID: ' . $e->getMessage(), [
                'document_id' => $documentId
            ]);
            return null;
        }
    }

    /**
     * Upload driver document
     */
    public function uploadDocument($driverFirebaseUid, UploadedFile $file, array $documentData = [])
    {
        DB::beginTransaction();
        try {
            // Verify driver exists
            $driver = Driver::where('firebase_uid', $driverFirebaseUid)->first();
            if (!$driver) {
                throw new \Exception('Driver not found');
            }

            // Validate file
            $this->validateFile($file);

            // Generate unique filename
            $filename = $this->generateFilename($file, $driverFirebaseUid);

            // Store file
            $path = $file->storeAs('driver-documents/' . $driverFirebaseUid, $filename, 'public');

            if (!$path) {
                throw new \Exception('Failed to store file');
            }

            // Create document record
            $document = DriverDocument::create([
                'driver_firebase_uid' => $driverFirebaseUid,
                'vehicle_id' => null,
                'document_type' => $documentData['document_type'] ?? DriverDocument::TYPE_OTHER,
                'document_name' => $documentData['document_name'] ?? $file->getClientOriginalName(),
                'document_number' => $documentData['document_number'] ?? null,
                'issue_date' => $documentData['issue_date'] ?? null,
                'expiry_date' => $documentData['expiry_date'] ?? null,
                'issuing_authority' => $documentData['issuing_authority'] ?? null,
                'issuing_country' => $documentData['issuing_country'] ?? null,
                'issuing_state' => $documentData['issuing_state'] ?? null,
                'file_path' => $path,
                'file_name' => $filename,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'original_name' => $file->getClientOriginalName(),
                'upload_status' => DriverDocument::UPLOAD_SUCCESS,
                'verification_status' => $documentData['verification_status'] ?? DriverDocument::VERIFICATION_PENDING,
                'is_required' => $this->isRequiredDocument($documentData['document_type'] ?? DriverDocument::TYPE_OTHER),
                'uploaded_by' => $documentData['uploaded_by'] ?? session('firebase_user.uid', 'system')
            ]);

            // Sync to Firebase
            $this->syncDocumentToFirebase($document);

            // Create activity log
            $this->createDocumentActivity($driverFirebaseUid, 'document_uploaded', [
                'title' => 'Document Uploaded',
                'description' => "Document uploaded: {$document->document_name}",
                'document_id' => $document->id,
                'document_type' => $document->document_type,
                'file_name' => $document->file_name
            ]);

            DB::commit();

            Log::info('Driver document uploaded successfully', [
                'driver_id' => $driverFirebaseUid,
                'document_id' => $document->id,
                'document_type' => $document->document_type,
                'file_size' => $document->file_size
            ]);

            return $document;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error uploading driver document: ' . $e->getMessage(), [
                'driver_id' => $driverFirebaseUid,
                'file_name' => $file->getClientOriginalName()
            ]);
            throw $e;
        }
    }

    /**
     * Upload vehicle document
     */
    public function uploadVehicleDocument($vehicleId, UploadedFile $file, array $documentData = [])
    {
        DB::beginTransaction();
        try {
            // Verify vehicle exists
            $vehicle = Vehicle::find($vehicleId);
            if (!$vehicle) {
                throw new \Exception('Vehicle not found');
            }

            // Validate file
            $this->validateFile($file);

            // Generate unique filename
            $filename = $this->generateFilename($file, $vehicle->driver_firebase_uid, $vehicleId);

            // Store file
            $path = $file->storeAs(
                'vehicle-documents/' . $vehicle->driver_firebase_uid . '/' . $vehicleId,
                $filename,
                'public'
            );

            if (!$path) {
                throw new \Exception('Failed to store file');
            }

            // Create document record
            $document = DriverDocument::create([
                'driver_firebase_uid' => $vehicle->driver_firebase_uid,
                'vehicle_id' => $vehicleId,
                'document_type' => $documentData['document_type'] ?? DriverDocument::TYPE_VEHICLE_PHOTO,
                'document_name' => $documentData['document_name'] ?? $file->getClientOriginalName(),
                'document_number' => $documentData['document_number'] ?? null,
                'issue_date' => $documentData['issue_date'] ?? null,
                'expiry_date' => $documentData['expiry_date'] ?? null,
                'issuing_authority' => $documentData['issuing_authority'] ?? null,
                'issuing_country' => $documentData['issuing_country'] ?? null,
                'issuing_state' => $documentData['issuing_state'] ?? null,
                'file_path' => $path,
                'file_name' => $filename,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'original_name' => $file->getClientOriginalName(),
                'upload_status' => DriverDocument::UPLOAD_SUCCESS,
                'verification_status' => $documentData['verification_status'] ?? DriverDocument::VERIFICATION_PENDING,
                'is_required' => $this->isRequiredVehicleDocument($documentData['document_type'] ?? DriverDocument::TYPE_VEHICLE_PHOTO),
                'uploaded_by' => $documentData['uploaded_by'] ?? session('firebase_user.uid', 'system')
            ]);

            // Sync to Firebase
            $this->syncDocumentToFirebase($document);

            // Create activity log
            $this->createDocumentActivity($vehicle->driver_firebase_uid, 'vehicle_document_uploaded', [
                'title' => 'Vehicle Document Uploaded',
                'description' => "Vehicle document uploaded: {$document->document_name}",
                'document_id' => $document->id,
                'document_type' => $document->document_type,
                'vehicle_id' => $vehicleId,
                'vehicle_info' => $vehicle->full_name,
                'file_name' => $document->file_name
            ]);

            DB::commit();

            Log::info('Vehicle document uploaded successfully', [
                'vehicle_id' => $vehicleId,
                'driver_id' => $vehicle->driver_firebase_uid,
                'document_id' => $document->id,
                'document_type' => $document->document_type,
                'file_size' => $document->file_size
            ]);

            return $document;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error uploading vehicle document: ' . $e->getMessage(), [
                'vehicle_id' => $vehicleId,
                'file_name' => $file->getClientOriginalName()
            ]);
            throw $e;
        }
    }

    /**
     * Update document
     */
    public function updateDocument($documentId, array $data)
    {
        $document = $this->getDocumentById($documentId);

        if (!$document) {
            Log::error('Document not found for update', ['document_id' => $documentId]);
            return false;
        }

        try {
            $data['updated_at'] = now();
            $document->update($data);

            // Sync to Firebase
            $this->syncDocumentToFirebase($document);

            Log::info('Document updated successfully', [
                'document_id' => $documentId,
                'updated_fields' => array_keys($data)
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error updating document: ' . $e->getMessage(), [
                'document_id' => $documentId,
                'data' => $data
            ]);
            return false;
        }
    }

    /**
     * Update document verification status
     */
    public function updateVerificationStatus($documentId, $status, $adminUid = null, $notes = null)
    {
        $document = $this->getDocumentById($documentId);

        if (!$document) {
            Log::error('Document not found for verification update', ['document_id' => $documentId]);
            return false;
        }

        try {
            $updateData = [
                'verification_status' => $status,
                'verification_date' => $status === DriverDocument::VERIFICATION_VERIFIED ? now() : null,
                'verified_by' => $adminUid
            ];

            if ($notes) {
                $updateData['verification_notes'] = $notes;
            }

            $document->update($updateData);

            // Sync to Firebase
            $this->syncDocumentToFirebase($document);

            // Create activity
            $activityType = $document->vehicle_id ? 'vehicle_document_verified' : 'document_verified';
            $description = "Document verification status changed to: " . ucfirst($status);

            if ($document->vehicle_id && $document->vehicle) {
                $description .= " for vehicle: " . $document->vehicle->full_name;
            }

            $this->createDocumentActivity($document->driver_firebase_uid, $activityType, [
                'title' => $document->vehicle_id ? 'Vehicle Document Verified' : 'Document Verified',
                'description' => $description,
                'document_id' => $document->id,
                'document_type' => $document->document_type,
                'vehicle_id' => $document->vehicle_id,
                'new_status' => $status,
                'admin_uid' => $adminUid,
                'notes' => $notes
            ]);

            Log::info('Document verification status updated successfully', [
                'document_id' => $documentId,
                'new_status' => $status
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error updating document verification status: ' . $e->getMessage(), [
                'document_id' => $documentId,
                'status' => $status
            ]);
            return false;
        }
    }

    /**
     * Delete document
     */
    public function deleteDocument($documentId)
    {
        $document = $this->getDocumentById($documentId);

        if (!$document) {
            Log::error('Document not found for deletion', ['document_id' => $documentId]);
            return false;
        }

        DB::beginTransaction();
        try {
            // Delete physical file
            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            // Create activity before deletion
            $activityType = $document->vehicle_id ? 'vehicle_document_deleted' : 'document_deleted';
            $description = "Document removed: {$document->document_name}";

            if ($document->vehicle_id && $document->vehicle) {
                $description .= " from vehicle: " . $document->vehicle->full_name;
            }

            $this->createDocumentActivity($document->driver_firebase_uid, $activityType, [
                'title' => $document->vehicle_id ? 'Vehicle Document Removed' : 'Document Removed',
                'description' => $description,
                'document_id' => $document->id,
                'document_type' => $document->document_type,
                'vehicle_id' => $document->vehicle_id,
                'file_name' => $document->file_name
            ]);

            // Delete from Firebase
            $this->deleteDocumentFromFirebase($document->id);

            // Delete record
            $document->delete();

            DB::commit();

            Log::info('Document deleted successfully', [
                'document_id' => $documentId,
                'driver_id' => $document->driver_firebase_uid,
                'vehicle_id' => $document->vehicle_id
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting document: ' . $e->getMessage(), [
                'document_id' => $documentId
            ]);
            return false;
        }
    }

    /**
     * Get documents by type
     */
    public function getDocumentsByType($type, $driverFirebaseUid = null, $vehicleId = null)
    {
        try {
            $query = DriverDocument::where('document_type', $type);

            if ($driverFirebaseUid) {
                $query->where('driver_firebase_uid', $driverFirebaseUid);
            }

            if ($vehicleId) {
                $query->where('vehicle_id', $vehicleId);
            }

            return $query->orderBy('created_at', 'desc')->get();
        } catch (\Exception $e) {
            Log::error('Error getting documents by type: ' . $e->getMessage(), [
                'type' => $type,
                'driver_uid' => $driverFirebaseUid,
                'vehicle_id' => $vehicleId
            ]);
            return collect([]);
        }
    }

    /**
     * Get expired documents
     */
    public function getExpiredDocuments($driverFirebaseUid = null)
    {
        try {
            $query = DriverDocument::where('expiry_date', '<', now())
                ->whereNotNull('expiry_date');

            if ($driverFirebaseUid) {
                $query->where('driver_firebase_uid', $driverFirebaseUid);
            }

            return $query->orderBy('expiry_date', 'asc')->get();
        } catch (\Exception $e) {
            Log::error('Error getting expired documents: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Get documents expiring soon
     */
    public function getDocumentsExpiringSoon($days = 30, $driverFirebaseUid = null)
    {
        try {
            $query = DriverDocument::whereBetween('expiry_date', [now(), now()->addDays($days)])
                ->whereNotNull('expiry_date');

            if ($driverFirebaseUid) {
                $query->where('driver_firebase_uid', $driverFirebaseUid);
            }

            return $query->orderBy('expiry_date', 'asc')->get();
        } catch (\Exception $e) {
            Log::error('Error getting documents expiring soon: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Get pending verification documents
     */
    public function getPendingVerificationDocuments($driverFirebaseUid = null)
    {
        try {
            $query = DriverDocument::where('verification_status', DriverDocument::VERIFICATION_PENDING);

            if ($driverFirebaseUid) {
                $query->where('driver_firebase_uid', $driverFirebaseUid);
            }

            return $query->orderBy('created_at', 'desc')->get();
        } catch (\Exception $e) {
            Log::error('Error getting pending verification documents: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Get document URL
     */
    public function getDocumentUrl($documentId)
    {
        $document = $this->getDocumentById($documentId);

        if (!$document || !$document->file_path) {
            return null;
        }

        try {
            return Storage::disk('public')->url($document->file_path);
        } catch (\Exception $e) {
            Log::error('Error getting document URL: ' . $e->getMessage());
            return null;
        }
    }
        public function uploadDocument($driverFirebaseUid, UploadedFile $file, array $documentData = [])
    {
        DB::beginTransaction();
        try {
            // Verify driver exists
            $driver = Driver::where('firebase_uid', $driverFirebaseUid)->first();
            if (!$driver) {
                throw new \Exception('Driver not found');
            }

            // Validate file
            $this->validateFile($file);

            // Generate unique filename
            $filename = $this->generateFilename($file, $driverFirebaseUid);

            // Store file
            $path = $file->storeAs('driver-documents/' . $driverFirebaseUid, $filename, 'public');

            if (!$path) {
                throw new \Exception('Failed to store file');
            }

            // Create document record
            $document = DriverDocument::create([
                'driver_firebase_uid' => $driverFirebaseUid,
                'vehicle_id' => null,
                'document_type' => $documentData['document_type'] ?? DriverDocument::TYPE_OTHER,
                'document_name' => $documentData['document_name'] ?? $file->getClientOriginalName(),
                'document_number' => $documentData['document_number'] ?? null,
                'issue_date' => $documentData['issue_date'] ?? null,
                'expiry_date' => $documentData['expiry_date'] ?? null,
                'issuing_authority' => $documentData['issuing_authority'] ?? null,
                'issuing_country' => $documentData['issuing_country'] ?? null,
                'issuing_state' => $documentData['issuing_state'] ?? null,
                'file_path' => $path,
                'file_name' => $filename,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'original_name' => $file->getClientOriginalName(),
                'upload_status' => DriverDocument::UPLOAD_SUCCESS ?? 'success',
                'verification_status' => $documentData['verification_status'] ?? DriverDocument::VERIFICATION_PENDING ?? 'pending',
                'is_required' => $this->isRequiredDocument($documentData['document_type'] ?? DriverDocument::TYPE_OTHER ?? 'other'),
                'uploaded_by' => $documentData['uploaded_by'] ?? session('firebase_user.uid', 'system')
            ]);

            // Sync to Firebase
            $this->syncDocumentToFirebase($document);

            // Create activity log
            $this->createDocumentActivity($driverFirebaseUid, 'document_uploaded', [
                'title' => 'Document Uploaded',
                'description' => "Document uploaded: {$document->document_name}",
                'document_id' => $document->id,
                'document_type' => $document->document_type,
                'file_name' => $document->file_name
            ]);

            DB::commit();

            Log::info('Driver document uploaded successfully', [
                'driver_id' => $driverFirebaseUid,
                'document_id' => $document->id,
                'document_type' => $document->document_type,
                'file_size' => $document->file_size
            ]);

            return $document;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error uploading driver document: ' . $e->getMessage(), [
                'driver_id' => $driverFirebaseUid,
                'file_name' => $file->getClientOriginalName()
            ]);
            throw $e;
        }


    /**
     * Download document
     */
    public function downloadDocument($documentId)
    {
        $document = $this->getDocumentById($documentId);

        if (!$document || !Storage::disk('public')->exists($document->file_path)) {
            return null;
        }

        try {
            return Storage::disk('public')->download(
                $document->file_path,
                $document->original_name
            );
        } catch (\Exception $e) {
            Log::error('Error downloading document: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get document statistics
     */
    public function getDocumentStatistics($driverFirebaseUid = null)
    {
        try {
            if ($driverFirebaseUid) {
                $baseQuery = DriverDocument::where('driver_firebase_uid', $driverFirebaseUid);
            } else {
                $baseQuery = DriverDocument::query();
            }

            $totalDocuments = $baseQuery->count();
            $verifiedDocuments = (clone $baseQuery)->where('verification_status', DriverDocument::VERIFICATION_VERIFIED)->count();
            $pendingDocuments = (clone $baseQuery)->where('verification_status', DriverDocument::VERIFICATION_PENDING)->count();
            $rejectedDocuments = (clone $baseQuery)->where('verification_status', DriverDocument::VERIFICATION_REJECTED)->count();
            $expiredDocuments = (clone $baseQuery)->where('expiry_date', '<', now())->whereNotNull('expiry_date')->count();
            $expiringSoonDocuments = (clone $baseQuery)->whereBetween('expiry_date', [now(), now()->addDays(30)])->count();

            return [
                'total_documents' => $totalDocuments,
                'verified_documents' => $verifiedDocuments,
                'pending_documents' => $pendingDocuments,
                'rejected_documents' => $rejectedDocuments,
                'expired_documents' => $expiredDocuments,
                'expiring_soon_documents' => $expiringSoonDocuments,
                'verification_rate' => $totalDocuments > 0 ? round(($verifiedDocuments / $totalDocuments) * 100, 2) : 0
            ];
        } catch (\Exception $e) {
            Log::error('Error getting document statistics: ' . $e->getMessage());
            return $this->getDefaultStatistics();
        }
    }

    /**
     * Bulk verify documents
     */
    public function bulkVerifyDocuments(array $documentIds, $adminUid)
    {
        $processed = 0;
        $failed = 0;

        foreach ($documentIds as $documentId) {
            try {
                if ($this->updateVerificationStatus($documentId, DriverDocument::VERIFICATION_VERIFIED, $adminUid)) {
                    $processed++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                Log::error("Bulk verify failed for document {$documentId}: " . $e->getMessage());
                $failed++;
            }
        }

        return [
            'processed' => $processed,
            'failed' => $failed
        ];
    }

    /**
     * Bulk reject documents
     */
    public function bulkRejectDocuments(array $documentIds, $adminUid, $reason)
    {
        $processed = 0;
        $failed = 0;

        foreach ($documentIds as $documentId) {
            try {
                if ($this->updateVerificationStatus($documentId, DriverDocument::VERIFICATION_REJECTED, $adminUid, $reason)) {
                    $processed++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                Log::error("Bulk reject failed for document {$documentId}: " . $e->getMessage());
                $failed++;
            }
        }

        return [
            'processed' => $processed,
            'failed' => $failed
        ];
    }

    /**
     * Clean up orphaned files
     */
    public function cleanupOrphanedFiles()
    {
        $cleaned = 0;

        try {
            // Get all document file paths from database
            $documentPaths = DriverDocument::pluck('file_path')->toArray();

            // Get all files in storage directories
            $driverFiles = Storage::disk('public')->allFiles('driver-documents');
            $vehicleFiles = Storage::disk('public')->allFiles('vehicle-documents');
            $allFiles = array_merge($driverFiles, $vehicleFiles);

            // Find orphaned files
            $orphanedFiles = array_diff($allFiles, $documentPaths);

            // Delete orphaned files
            foreach ($orphanedFiles as $file) {
                if (Storage::disk('public')->exists($file)) {
                    Storage::disk('public')->delete($file);
                    $cleaned++;
                }
            }

            Log::info('Cleaned up orphaned files', ['count' => $cleaned]);
        } catch (\Exception $e) {
            Log::error('Error cleaning up orphaned files: ' . $e->getMessage());
        }

        return $cleaned;
    }

    /**
     * Get driver document completion status
     */
    public function getDriverDocumentCompletion($driverFirebaseUid)
    {
        try {
            $requiredTypes = DriverDocument::getRequiredDriverDocuments();
            $existingDocuments = $this->getDriverDocuments($driverFirebaseUid)
                ->where('verification_status', '!=', DriverDocument::VERIFICATION_REJECTED)
                ->pluck('document_type')
                ->unique();

            $completed = 0;
            $missing = [];

            foreach ($requiredTypes as $type) {
                if ($existingDocuments->contains($type)) {
                    $completed++;
                } else {
                    $missing[] = $type;
                }
            }

            $percentage = count($requiredTypes) > 0 ? round(($completed / count($requiredTypes)) * 100) : 0;

            return [
                'percentage' => $percentage,
                'completed' => $completed,
                'total_required' => count($requiredTypes),
                'missing_documents' => $missing
            ];
        } catch (\Exception $e) {
            Log::error('Error getting driver document completion: ' . $e->getMessage());
            return [
                'percentage' => 0,
                'completed' => 0,
                'total_required' => 0,
                'missing_documents' => []
            ];
        }
    }

    /**
     * Get vehicle document completion status
     */
    public function getVehicleDocumentCompletion($vehicleId)
    {
        try {
            $requiredTypes = DriverDocument::getRequiredVehicleDocuments();
            $existingDocuments = $this->getVehicleDocuments($vehicleId)
                ->where('verification_status', '!=', DriverDocument::VERIFICATION_REJECTED)
                ->pluck('document_type')
                ->unique();

            $completed = 0;
            $missing = [];

            foreach ($requiredTypes as $type) {
                if ($existingDocuments->contains($type)) {
                    $completed++;
                } else {
                    $missing[] = $type;
                }
            }

            $percentage = count($requiredTypes) > 0 ? round(($completed / count($requiredTypes)) * 100) : 0;

            return [
                'percentage' => $percentage,
                'completed' => $completed,
                'total_required' => count($requiredTypes),
                'missing_documents' => $missing
            ];
        } catch (\Exception $e) {
            Log::error('Error getting vehicle document completion: ' . $e->getMessage());
            return [
                'percentage' => 0,
                'completed' => 0,
                'total_required' => 0,
                'missing_documents' => []
            ];
        }
    }

    // ===================== PRIVATE HELPER METHODS =====================

    /**
     * Validate uploaded file
     */
    private function validateFile(UploadedFile $file)
    {
        $allowedMimeTypes = DriverDocument::getAllowedMimeTypes();
        $maxFileSize = DriverDocument::getMaxFileSize() * 1024; // Convert KB to bytes

        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            throw new \Exception('Invalid file type. Allowed types: ' . implode(', ', $allowedMimeTypes));
        }

        if ($file->getSize() > $maxFileSize) {
            throw new \Exception('File size exceeds maximum allowed size of ' . DriverDocument::getMaxFileSize() . 'KB');
        }

        if (!$file->isValid()) {
            throw new \Exception('Invalid file upload');
        }
    }

    /**
     * Generate unique filename
     */
    private function generateFilename(UploadedFile $file, $driverFirebaseUid, $vehicleId = null)
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('Y_m_d_H_i_s');
        $random = substr(md5(uniqid()), 0, 8);

        $prefix = $vehicleId ? "vehicle_{$vehicleId}" : "driver";

        return "{$prefix}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Check if document type is required for drivers
     */
    private function isRequiredDocument($documentType)
    {
        return in_array($documentType, DriverDocument::getRequiredDriverDocuments());
    }

    /**
     * Check if document type is required for vehicles
     */
    private function isRequiredVehicleDocument($documentType)
    {
        return in_array($documentType, DriverDocument::getRequiredVehicleDocuments());
    }

    /**
     * Sync document to Firebase
     */
    private function syncDocumentToFirebase($document)
    {
        try {
            if (method_exists($document, 'toFirebaseArray')) {
                $data = $document->toFirebaseArray();
                $this->firestoreService
                    ->collection('driver_documents')
                    ->create($data, $document->id);

                $document->update([
                    'firebase_synced' => true,
                    'firebase_synced_at' => now()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error syncing document to Firebase: ' . $e->getMessage(), [
                'document_id' => $document->id
            ]);
        }
    }

    /**
     * Delete document from Firebase
     */
    private function deleteDocumentFromFirebase($documentId)
    {
        try {
            $this->firestoreService
                ->collection('driver_documents')
                ->delete($documentId);
        } catch (\Exception $e) {
            Log::error('Error deleting document from Firebase: ' . $e->getMessage(), [
                'document_id' => $documentId
            ]);
        }
    }

    /**
     * Create document activity
     */
    private function createDocumentActivity($driverFirebaseUid, $type, $data)
    {
        try {
            $activity = array_merge($data, [
                'driver_firebase_uid' => $driverFirebaseUid,
                'activity_type' => $type,
                'created_at' => now()->format('Y-m-d H:i:s'),
                'created_by' => session('firebase_user.uid', 'system')
            ]);

            $this->firestoreService
                ->collection('document_activities')
                ->create($activity);
        } catch (\Exception $e) {
            Log::error('Error creating document activity: ' . $e->getMessage());
        }
    }

    /**
     * Get default statistics
     */
    private function getDefaultStatistics()
    {
        return [
            'total_documents' => 0,
            'verified_documents' => 0,
            'pending_documents' => 0,
            'rejected_documents' => 0,
            'expired_documents' => 0,
            'expiring_soon_documents' => 0,
            'verification_rate' => 0
        ];
    }

    /**
     * Get all documents for admin dashboard
     */
    public function getAllDocuments($limit = 1000)
    {
        try {
            return DriverDocument::with(['driver', 'vehicle'])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            Log::error('Error getting all documents: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Search documents
     */
    public function searchDocuments($searchTerm, array $filters = [])
    {
        try {
            $query = DriverDocument::with(['driver', 'vehicle']);

            // Apply search
            if (!empty($searchTerm)) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('document_name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('document_number', 'like', '%' . $searchTerm . '%')
                        ->orWhere('issuing_authority', 'like', '%' . $searchTerm . '%')
                        ->orWhereHas('driver', function ($dq) use ($searchTerm) {
                            $dq->where('name', 'like', '%' . $searchTerm . '%')
                                ->orWhere('email', 'like', '%' . $searchTerm . '%');
                        });
                });
            }

            // Apply filters
            if (!empty($filters['document_type'])) {
                $query->where('document_type', $filters['document_type']);
            }

            if (!empty($filters['verification_status'])) {
                $query->where('verification_status', $filters['verification_status']);
            }

            if (!empty($filters['driver_firebase_uid'])) {
                $query->where('driver_firebase_uid', $filters['driver_firebase_uid']);
            }

            if (!empty($filters['vehicle_id'])) {
                $query->where('vehicle_id', $filters['vehicle_id']);
            }

            return $query->orderBy('created_at', 'desc')
                ->limit($filters['limit'] ?? 100)
                ->get();
        } catch (\Exception $e) {
            Log::error('Error searching documents: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Export documents
     */
    public function exportDocuments(array $filters = [])
    {
        try {
            $documents = $this->searchDocuments('', $filters);

            return $documents->map(function ($document) {
                return [
                    'id' => $document->id,
                    'driver_name' => $document->driver->name ?? 'N/A',
                    'driver_email' => $document->driver->email ?? 'N/A',
                    'vehicle_info' => $document->vehicle ? $document->vehicle->full_name : 'N/A',
                    'document_type' => $document->document_type,
                    'document_name' => $document->document_name,
                    'document_number' => $document->document_number,
                    'verification_status' => $document->verification_status,
                    'upload_status' => $document->upload_status,
                    'issue_date' => $document->issue_date,
                    'expiry_date' => $document->expiry_date,
                    'issuing_authority' => $document->issuing_authority,
                    'file_size' => $document->file_size_human,
                    'uploaded_at' => $document->created_at,
                    'verified_at' => $document->verification_date
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error('Error exporting documents: ' . $e->getMessage());
            return [];
        }
    }
}
