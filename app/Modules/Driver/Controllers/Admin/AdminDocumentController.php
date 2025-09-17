<?php

namespace App\Modules\Driver\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Modules\Driver\Services\DriverService;
use App\Modules\Driver\Services\DriverDocumentService;


use App\Modules\Driver\Models\DriverDocument;

class AdminDocumentController extends Controller
{
    protected $driverService;
    protected $DriverDocumentService;

    public function __construct(DriverService $driverService, DriverDocumentService $DriverDocumentService)
    {
        $this->driverService = $driverService;
        $this->DriverDocumentService = $DriverDocumentService;
    }

    /**
     * Display document management dashboard
     */
    public function index(Request $request)
    {
        try {
            $filters = [
                'search' => $request->get('search'),
                'document_type' => $request->get('document_type'),
                'verification_status' => $request->get('verification_status'),
                'expiry_status' => $request->get('expiry_status'),
                'limit' => $request->get('limit', 50)
            ];

            // Get all documents from all drivers
            $allDrivers = $this->driverService->getAllDrivers(['limit' => 1000]);
            $documents = collect();

            foreach ($allDrivers as $driver) {
                $driverDocuments = $this->driverService->getDriverDocuments($driver['firebase_uid']);
                foreach ($driverDocuments as $document) {
                    $document['driver_name'] = $driver['name'];
                    $document['driver_email'] = $driver['email'];
                    $documents->push($document);
                }
            }

            // Apply filters
            if (!empty($filters['search'])) {
                $search = strtolower($filters['search']);
                $documents = $documents->filter(function ($document) use ($search) {
                    return stripos($document['document_name'] ?? '', $search) !== false ||
                        stripos($document['document_number'] ?? '', $search) !== false ||
                        stripos($document['driver_name'] ?? '', $search) !== false ||
                        stripos($document['issuing_authority'] ?? '', $search) !== false;
                });
            }

            if (!empty($filters['document_type'])) {
                $documents = $documents->where('document_type', $filters['document_type']);
            }

            if (!empty($filters['verification_status'])) {
                $documents = $documents->where('verification_status', $filters['verification_status']);
            }

            if (!empty($filters['expiry_status'])) {
                $now = now();
                $documents = $documents->filter(function ($document) use ($filters, $now) {
                    if (!isset($document['expiry_date']) || !$document['expiry_date']) {
                        return $filters['expiry_status'] === 'no_expiry';
                    }

                    $expiryDate = \Carbon\Carbon::parse($document['expiry_date']);

                    switch ($filters['expiry_status']) {
                        case 'expired':
                            return $expiryDate->isPast();
                        case 'expiring_soon':
                            return $expiryDate->isFuture() && $expiryDate->lte($now->copy()->addDays(30));
                        case 'valid':
                            return $expiryDate->gt($now->copy()->addDays(30));
                        default:
                            return true;
                    }
                });
            }

            // Paginate
            $currentPage = $request->get('page', 1);
            $perPage = $filters['limit'];
            $documents = $documents->forPage($currentPage, $perPage);

            $totalDocuments = $documents->count();

            // Get summary statistics
            $pendingDocuments = $this->driverService->getPendingDocuments();
            $expiredDocuments = $this->driverService->getExpiredDocuments();
            $expiringSoonDocuments = $this->driverService->getDocumentsExpiringSoon();

            Log::info('Admin document dashboard accessed', [
                'admin' => session('firebase_user.email'),
                'filters' => $filters
            ]);

            return view('driver::admin.documents.index', compact(
                'documents',
                'totalDocuments',
                'pendingDocuments',
                'expiredDocuments',
                'expiringSoonDocuments'
            ) + $filters + [
                'documentTypes' => DriverDocument::getDocumentTypes(),
                'verificationStatuses' => $this->getVerificationStatuses(),
                'expiryStatuses' => $this->getExpiryStatuses()
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading admin document dashboard: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error loading document dashboard.');
        }
    }

    /**
     * Show detailed document information
     */
    public function show(string $documentId)
    {
        try {
            $document = $this->DriverDocumentService->getDocumentById($documentId);

            if (!$document) {
                return redirect()->route('admin.documents.index')
                    ->with('error', 'Document not found.');
            }

            // Get driver information
            $driver = $this->driverService->getDriverById($document['driver_firebase_uid']);

            Log::info('Admin viewed document details', [
                'admin' => session('firebase_user.email'),
                'document_id' => $documentId
            ]);

            return view('driver::admin.documents.show', compact(
                'document',
                'driver'
            ));
        } catch (\Exception $e) {
            Log::error('Error loading document details: ' . $e->getMessage());
            return redirect()->route('admin.documents.index')
                ->with('error', 'Error loading document details.');
        }
    }
    /**
     * Helper: Get expiry statuses
     */
    private function getExpiryStatuses(): array
    {
        return [
            'expired' => 'Expired',
            'expiring_soon' => 'Expiring Soon',
            'valid' => 'Valid',
            'no_expiry' => 'No Expiry Date'
        ];
    }
    /**
     * Show form for uploading new document
     */
    public function create(Request $request)
    {
        $driverFirebaseUid = $request->get('driver_firebase_uid');
        $driver = null;

        if ($driverFirebaseUid) {
            $driver = $this->driverService->getDriverById($driverFirebaseUid);
        }

        return view('driver::admin.documents.create', [
            'driver' => $driver,
            'documentTypes' => DriverDocument::getDocumentTypes(),
            'verificationStatuses' => $this->getVerificationStatuses(),
            'drivers' => $this->driverService->getAllDrivers(['limit' => 1000])
        ]);
    }

    /**
     * Store newly uploaded document
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'driver_firebase_uid' => 'required|string',
            'document_type' => 'required|in:' . implode(',', array_keys(DriverDocument::getDocumentTypes())),
            'document_name' => 'nullable|string|max:255',
            'document_number' => 'nullable|string|max:100',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:today',
            'issuing_authority' => 'nullable|string|max:255',
            'issuing_country' => 'nullable|string|max:100',
            'issuing_state' => 'nullable|string|max:100',
            'verification_status' => 'required|in:pending,verified,rejected',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $result = $this->driverService->uploadDocument(
                $request->driver_firebase_uid,
                $request->file('file'),
                $request->only([
                    'document_type',
                    'document_name',
                    'document_number',
                    'issue_date',
                    'expiry_date',
                    'issuing_authority',
                    'issuing_country',
                    'issuing_state'
                ])
            );

            if ($result) {
                // Set verification status if not pending
                if ($request->verification_status !== 'pending') {
                    if ($request->verification_status === 'verified') {
                        $this->driverService->verifyDocument(
                            $result['id'],
                            session('firebase_user.uid'),
                            'Verified by admin during upload'
                        );
                    } else {
                        $this->driverService->rejectDocument(
                            $result['id'],
                            session('firebase_user.uid'),
                            'Rejected by admin during upload'
                        );
                    }
                }

                Log::info('Admin uploaded document', [
                    'admin' => session('firebase_user.email'),
                    'document_id' => $result['id'] ?? 'unknown',
                    'driver_id' => $request->driver_firebase_uid
                ]);

                return redirect()->route('admin.documents.index')
                    ->with('success', 'Document uploaded successfully!');
            } else {
                return redirect()->back()
                    ->with('error', 'Failed to upload document.')
                    ->withInput();
            }
        } catch (\Exception $e) {
            Log::error('Error uploading document: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error uploading document: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show form for editing document
     */
    public function edit(string $documentId)
    {
        try {
            $document = $this->DriverDocumentService->getDocumentById($documentId);

            if (!$document) {
                return redirect()->route('admin.documents.index')
                    ->with('error', 'Document not found.');
            }

            $driver = $this->driverService->getDriverById($document['driver_firebase_uid']);

            return view('driver::admin.documents.edit', [
                'document' => $document,
                'driver' => $driver,
                'documentTypes' => DriverDocument::getDocumentTypes(),
                'verificationStatuses' => $this->getVerificationStatuses()
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading document for edit: ' . $e->getMessage());
            return redirect()->route('admin.documents.index')
                ->with('error', 'Error loading document for editing.');
        }
    }

    /**
     * Update document information
     */
    public function update(Request $request, string $documentId)
    {
        $validator = Validator::make($request->all(), [
            'document_type' => 'required|in:' . implode(',', array_keys(DriverDocument::getDocumentTypes())),
            'document_name' => 'nullable|string|max:255',
            'document_number' => 'nullable|string|max:100',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:today',
            'issuing_authority' => 'nullable|string|max:255',
            'issuing_country' => 'nullable|string|max:100',
            'issuing_state' => 'nullable|string|max:100',
            'verification_status' => 'required|in:pending,verified,rejected',
            'verification_notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $documentData = $request->except(['verification_status', 'verification_notes']);
            $documentData['updated_by'] = session('firebase_user.uid');

            $result = $this->DriverDocumentService->updateDocument($documentId, $documentData);

            // Handle verification status change
            $currentDocument = $this->DriverDocumentService->getDocumentById($documentId);
            if ($currentDocument && $currentDocument['verification_status'] !== $request->verification_status) {
                if ($request->verification_status === 'verified') {
                    $this->driverService->verifyDocument(
                        $documentId,
                        session('firebase_user.uid'),
                        $request->verification_notes
                    );
                } elseif ($request->verification_status === 'rejected') {
                    $this->driverService->rejectDocument(
                        $documentId,
                        session('firebase_user.uid'),
                        $request->verification_notes
                    );
                }
            }

            if ($result) {
                Log::info('Admin verified document', [
                    'admin' => session('firebase_user.email'),
                    'document_id' => $documentId
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Document verified successfully!'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to verify document'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error verifying document: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error verifying document: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Reject document (AJAX)
     */
    public function reject(Request $request, string $documentId)
    {
        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Rejection reason is required',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $result = $this->driverService->rejectDocument(
                $documentId,
                session('firebase_user.uid'),
                $request->rejection_reason
            );

            if ($result) {
                Log::info('Admin rejected document', [
                    'admin' => session('firebase_user.email'),
                    'document_id' => $documentId
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Document rejected successfully!'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to reject document'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error rejecting document: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error rejecting document: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Download document
     */
    public function download(string $documentId)
    {
        try {
            $document = $this->DriverDocumentService->getDocumentById($documentId);

            if (!$document) {
                return redirect()->route('admin.documents.index')
                    ->with('error', 'Document not found.');
            }

            if (!isset($document['file_path']) || !Storage::disk('public')->exists($document['file_path'])) {
                return redirect()->back()
                    ->with('error', 'Document file not found.');
            }

            Log::info('Admin downloaded document', [
                'admin' => session('firebase_user.email'),
                'document_id' => $documentId
            ]);

            return Storage::disk('public')->download(
                $document['file_path'],
                $document['file_name'] ?? 'document'
            );
        } catch (\Exception $e) {
            Log::error('Error downloading document: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error downloading document: ' . $e->getMessage());
        }
    }

    /**
     * Bulk operations on documents
     */
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:verify,reject,delete',
            'document_ids' => 'required|array|min:1',
            'document_ids.*' => 'required|string',
            'rejection_reason' => 'required_if:action,reject|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $action = $request->action;
            $documentIds = $request->document_ids;

            $processedCount = 0;
            $failedCount = 0;

            foreach ($documentIds as $documentId) {
                try {
                    $success = $this->executeBulkDocumentAction($action, $documentId, $request);
                    if ($success) {
                        $processedCount++;
                    } else {
                        $failedCount++;
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    Log::warning('Bulk document action failed', [
                        'document_id' => $documentId,
                        'action' => $action,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Admin performed bulk document action', [
                'admin' => session('firebase_user.email'),
                'action' => $action,
                'processed' => $processedCount,
                'failed' => $failedCount
            ]);

            return response()->json([
                'success' => true,
                'message' => $this->getBulkDocumentActionMessage($action, $processedCount, $failedCount),
                'processed_count' => $processedCount,
                'failed_count' => $failedCount
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk document action error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error performing bulk action: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Document verification queue
     */
    public function verificationQueue()
    {
        try {
            $pendingDocuments = $this->driverService->getPendingDocuments();
            $expiredDocuments = $this->driverService->getExpiredDocuments();
            $expiringSoonDocuments = $this->driverService->getDocumentsExpiringSoon();

            // Add driver information to documents
            foreach ($pendingDocuments as &$document) {
                $driver = $this->driverService->getDriverById($document['driver_firebase_uid']);
                $document['driver_name'] = $driver['name'] ?? 'Unknown';
                $document['driver_email'] = $driver['email'] ?? 'Unknown';
            }

            return view('driver::admin.documents.verification-queue', compact(
                'pendingDocuments',
                'expiredDocuments',
                'expiringSoonDocuments'
            ));
        } catch (\Exception $e) {
            Log::error('Error loading document verification queue: ' . $e->getMessage());
            return redirect()->route('admin.documents.index')
                ->with('error', 'Error loading verification queue.');
        }
    }

    /**
     * Document statistics
     */
    public function statistics()
    {
        try {
            $statistics = $this->driverService->getSystemAnalytics()['document_statistics'] ?? [];

            return view('driver::admin.documents.statistics', compact('statistics'));
        } catch (\Exception $e) {
            Log::error('Error loading document statistics: ' . $e->getMessage());
            return redirect()->route('admin.documents.index')
                ->with('error', 'Error loading statistics.');
        }
    }

    /**
     * Helper: Execute bulk document action
     */
    private function executeBulkDocumentAction(string $action, string $documentId, Request $request): bool
    {
        switch ($action) {
            case 'verify':
                return $this->driverService->verifyDocument(
                    $documentId,
                    session('firebase_user.uid'),
                    'Bulk verification by admin'
                );
            case 'reject':
                return $this->driverService->rejectDocument(
                    $documentId,
                    session('firebase_user.uid'),
                    $request->rejection_reason
                );
            case 'delete':
                return $this->DriverDocumentService->deleteDocument($documentId);
            default:
                return false;
        }
    }

    /**
     * Helper: Get bulk document action message
     */
    private function getBulkDocumentActionMessage(string $action, int $processed, int $failed): string
    {
        $actionPast = [
            'verify' => 'verified',
            'reject' => 'rejected',
            'delete' => 'deleted'
        ][$action];

        $message = "Successfully {$actionPast} {$processed} documents";

        if ($failed > 0) {
            $message .= " ({$failed} failed)";
        }

        return $message . ".";
    }

    /**
     * Helper: Get verification statuses
     */
    private function getVerificationStatuses(): array
    {
        return [
            'pending' => 'Pending Verification',
            'verified' => 'Verified',
            'rejected' => 'Rejected'
        ];
    }



    /**
     * Delete document
     */
    public function destroy(string $documentId)
    {
        try {
            $document = $this->DriverDocumentService->getDocumentById($documentId);

            if (!$document) {
                return redirect()->route('admin.documents.index')
                    ->with('error', 'Document not found.');
            }

            $result = $this->DriverDocumentService->deleteDocument($documentId);

            if ($result) {
                Log::info('Admin deleted document', [
                    'admin' => session('firebase_user.email'),
                    'document_id' => $documentId
                ]);

                return redirect()->route('admin.documents.index')
                    ->with('success', 'Document deleted successfully!');
            } else {
                return redirect()->back()
                    ->with('error', 'Failed to delete document.');
            }
        } catch (\Exception $e) {
            Log::error('Error deleting document: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error deleting document: ' . $e->getMessage());
        }
    }

    /**
     * Verify document (AJAX)
     */
    public function verify(Request $request, string $documentId)
    {
        $validator = Validator::make($request->all(), [
            'verification_notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $result = $this->driverService->verifyDocument(
                $documentId,
                session('firebase_user.uid'),
                $request->verification_notes
            );

            if ($result) {
                Log::info('Admin verified document', [
                    'admin' => session('firebase_user.email'),
                    'document_id' => $documentId
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Document verified successfully!',
                    'redirect' => route('admin.documents.show', $documentId)
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to verify document.'
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error verifying document: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error verifying document: ' . $e->getMessage()
            ], 500);
        }
    }
}
