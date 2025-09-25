<?php

namespace App\Modules\Driver\Services;

use App\Modules\Driver\Models\DriverDocument;
use App\Modules\Driver\Models\Driver;
use App\Modules\Driver\Models\Vehicle;
use App\Modules\Driver\Models\DriverActivity;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class DriverDocumentService
{
    /**
     * Get driver documents
     */
    public function getDriverDocuments($driverFirebaseUid)
    {
        return DriverDocument::where('driver_firebase_uid', $driverFirebaseUid)
            ->whereNull('vehicle_id')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get vehicle documents
     */
    public function getVehicleDocuments($vehicleId)
    {
        return DriverDocument::where('vehicle_id', $vehicleId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get document by ID
     */
    public function getDocumentById($documentId)
    {
        return DriverDocument::find($documentId);
    }

    /**
     * Upload driver document
     */
    public function uploadDocument($driverFirebaseUid, UploadedFile $file, array $documentData = [])
    {
        try {
            // Verify driver exists
            $driver = Driver::where('firebase_uid', $driverFirebaseUid)->first();
            if (!$driver) {
                throw new \Exception('Driver not found');
            }

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
                'verification_status' => DriverDocument::VERIFICATION_PENDING,
                'uploaded_by' => $documentData['uploaded_by'] ?? session('firebase_user.uid', 'system')
            ]);

            // Create activity
            DriverActivity::createActivity($driverFirebaseUid, DriverActivity::TYPE_DOCUMENT_UPDATE, [
                'title' => 'Document Uploaded',
                'description' => "Document uploaded: {$document->document_name}",
                'metadata' => [
                    'document_id' => $document->id,
                    'document_type' => $document->document_type,
                    'file_name' => $document->file_name
                ]
            ]);

            Log::info('Driver document uploaded successfully', [
                'driver_id' => $driverFirebaseUid,
                'document_id' => $document->id,
                'document_type' => $document->document_type,
                'file_size' => $document->file_size
            ]);

            return $document;
        } catch (\Exception $e) {
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
        try {
            // Verify vehicle exists
            $vehicle = Vehicle::find($vehicleId);
            if (!$vehicle) {
                throw new \Exception('Vehicle not found');
            }

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
                'file_path' => $path,
                'file_name' => $filename,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'original_name' => $file->getClientOriginalName(),
                'upload_status' => DriverDocument::UPLOAD_SUCCESS,
                'verification_status' => DriverDocument::VERIFICATION_PENDING,
                'expiry_date' => $documentData['expiry_date'] ?? null,
                'uploaded_by' => $documentData['uploaded_by'] ?? session('firebase_user.uid', 'system')
            ]);

            // Create activity for both driver and vehicle
            DriverActivity::createActivity($vehicle->driver_firebase_uid, DriverActivity::TYPE_DOCUMENT_UPDATE, [
                'title' => 'Vehicle Document Uploaded',
                'description' => "Vehicle document uploaded: {$document->document_name}",
                'metadata' => [
                    'document_id' => $document->id,
                    'document_type' => $document->document_type,
                    'vehicle_id' => $vehicleId,
                    'vehicle_info' => $vehicle->full_name,
                    'file_name' => $document->file_name
                ]
            ]);

            Log::info('Vehicle document uploaded successfully', [
                'vehicle_id' => $vehicleId,
                'driver_id' => $vehicle->driver_firebase_uid,
                'document_id' => $document->id,
                'document_type' => $document->document_type,
                'file_size' => $document->file_size
            ]);

            return $document;
        } catch (\Exception $e) {
            Log::error('Error uploading vehicle document: ' . $e->getMessage(), [
                'vehicle_id' => $vehicleId,
                'file_name' => $file->getClientOriginalName()
            ]);
            throw $e;
        }
    }

    /**
     * Update document verification status
     */
    public function updateVerificationStatus($documentId, $status, $adminUid = null, $notes = null)
    {
        $document = $this->getDocumentById($documentId);

        if (!$document) {
            return false;
        }

        $updateData = [
            'verification_status' => $status,
            'verified_at' => $status === DriverDocument::VERIFICATION_VERIFIED ? now() : null,
            'verified_by' => $adminUid
        ];

        if ($notes) {
            $updateData['verification_notes'] = $notes;
        }

        $document->update($updateData);

        // Create activity
        $activityType = $document->vehicle_id ? 'Vehicle Document Verified' : 'Document Verified';
        $description = "Document verification status changed to: " . ucfirst($status);

        if ($document->vehicle_id && $document->vehicle) {
            $description .= " for vehicle: " . $document->vehicle->full_name;
        }

        DriverActivity::createActivity($document->driver_firebase_uid, DriverActivity::TYPE_VERIFICATION_UPDATE, [
            'title' => $activityType,
            'description' => $description,
            'metadata' => [
                'document_id' => $document->id,
                'document_type' => $document->document_type,
                'vehicle_id' => $document->vehicle_id,
                'new_status' => $status,
                'admin_uid' => $adminUid,
                'notes' => $notes
            ]
        ]);

        return true;
    }

    /**
     * Delete document
     */
    public function deleteDocument($documentId)
    {
        $document = $this->getDocumentById($documentId);

        if (!$document) {
            return false;
        }

        try {
            // Delete physical file
            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            // Create activity before deletion
            $activityType = $document->vehicle_id ? 'Vehicle Document Removed' : 'Document Removed';
            $description = "Document removed: {$document->document_name}";

            if ($document->vehicle_id && $document->vehicle) {
                $description .= " from vehicle: " . $document->vehicle->full_name;
            }

            DriverActivity::createActivity($document->driver_firebase_uid, DriverActivity::TYPE_DOCUMENT_UPDATE, [
                'title' => $activityType,
                'description' => $description,
                'metadata' => [
                    'document_id' => $document->id,
                    'document_type' => $document->document_type,
                    'vehicle_id' => $document->vehicle_id,
                    'file_name' => $document->file_name
                ]
            ]);

            // Delete record
            $document->delete();

            Log::info('Document deleted successfully', [
                'document_id' => $documentId,
                'driver_id' => $document->driver_firebase_uid,
                'vehicle_id' => $document->vehicle_id
            ]);

            return true;
        } catch (\Exception $e) {
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
        $query = DriverDocument::where('document_type', $type);

        if ($driverFirebaseUid) {
            $query->where('driver_firebase_uid', $driverFirebaseUid);
        }

        if ($vehicleId) {
            $query->where('vehicle_id', $vehicleId);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get expired documents
     */
    public function getExpiredDocuments($driverFirebaseUid = null)
    {
        $query = DriverDocument::where('expiry_date', '<', now())
            ->whereNotNull('expiry_date');

        if ($driverFirebaseUid) {
            $query->where('driver_firebase_uid', $driverFirebaseUid);
        }

        return $query->orderBy('expiry_date', 'asc')->get();
    }

    /**
     * Get documents expiring soon
     */
    public function getDocumentsExpiringSoon($days = 30, $driverFirebaseUid = null)
    {
        $query = DriverDocument::whereBetween('expiry_date', [now(), now()->addDays($days)])
            ->whereNotNull('expiry_date');

        if ($driverFirebaseUid) {
            $query->where('driver_firebase_uid', $driverFirebaseUid);
        }

        return $query->orderBy('expiry_date', 'asc')->get();
    }

    /**
     * Get pending verification documents
     */
    public function getPendingVerificationDocuments($driverFirebaseUid = null)
    {
        $query = DriverDocument::where('verification_status', DriverDocument::VERIFICATION_PENDING);

        if ($driverFirebaseUid) {
            $query->where('driver_firebase_uid', $driverFirebaseUid);
        }

        return $query->orderBy('created_at', 'desc')->get();
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
     * Get document URL
     */
    public function getDocumentUrl($documentId)
    {
        $document = $this->getDocumentById($documentId);

        if (!$document || !$document->file_path) {
            return null;
        }

        return Storage::disk('public')->url($document->file_path);
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

        return Storage::disk('public')->download(
            $document->file_path,
            $document->original_name
        );
    }

    /**
     * Get document statistics - FIXED VERSION
     */
    public function getDocumentStatistics($driverFirebaseUid = null)
    {
        if ($driverFirebaseUid) {
            $totalDocuments = DriverDocument::where('driver_firebase_uid', $driverFirebaseUid)->count();
            $verifiedDocuments = DriverDocument::where('driver_firebase_uid', $driverFirebaseUid)
                ->where('verification_status', DriverDocument::VERIFICATION_VERIFIED)
                ->count();
            $pendingDocuments = DriverDocument::where('driver_firebase_uid', $driverFirebaseUid)
                ->where('verification_status', DriverDocument::VERIFICATION_PENDING)
                ->count();
            $rejectedDocuments = DriverDocument::where('driver_firebase_uid', $driverFirebaseUid)
                ->where('verification_status', DriverDocument::VERIFICATION_REJECTED)
                ->count();
            $expiredDocuments = DriverDocument::where('driver_firebase_uid', $driverFirebaseUid)
                ->where('expiry_date', '<', now())
                ->whereNotNull('expiry_date')
                ->count();
            $expiringSoonDocuments = DriverDocument::where('driver_firebase_uid', $driverFirebaseUid)
                ->whereBetween('expiry_date', [now(), now()->addDays(30)])
                ->count();
        } else {
            $totalDocuments = DriverDocument::count();
            $verifiedDocuments = DriverDocument::where('verification_status', DriverDocument::VERIFICATION_VERIFIED)->count();
            $pendingDocuments = DriverDocument::where('verification_status', DriverDocument::VERIFICATION_PENDING)->count();
            $rejectedDocuments = DriverDocument::where('verification_status', DriverDocument::VERIFICATION_REJECTED)->count();
            $expiredDocuments = DriverDocument::where('expiry_date', '<', now())->whereNotNull('expiry_date')->count();
            $expiringSoonDocuments = DriverDocument::whereBetween('expiry_date', [now(), now()->addDays(30)])->count();
        }

        return [
            'total_documents' => $totalDocuments,
            'verified_documents' => $verifiedDocuments,
            'pending_documents' => $pendingDocuments,
            'rejected_documents' => $rejectedDocuments,
            'expired_documents' => $expiredDocuments,
            'expiring_soon_documents' => $expiringSoonDocuments,
            'verification_rate' => $totalDocuments > 0 ? round(($verifiedDocuments / $totalDocuments) * 100, 2) : 0
        ];
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
}
