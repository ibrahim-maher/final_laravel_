<?php

namespace App\Modules\Driver\Services;

use App\Services\FirestoreService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

abstract class BaseService
{
    protected $firestoreService;
    protected $collection;

    public function __construct(FirestoreService $firestoreService)
    {
        $this->firestoreService = $firestoreService;
    }

    /**
     * Get all documents with optional filters
     */
    protected function getAllDocuments(array $filters = []): array
    {
        try {
            Log::info("BaseService: Getting documents from '{$this->collection}' with filters", $filters);
            
            $limit = $filters['limit'] ?? 50;
            $documents = $this->firestoreService->collection($this->collection)->getAll($limit, false);
            
            Log::info("BaseService: Retrieved " . count($documents) . " documents from Firestore");
            
            $filteredDocuments = $this->applyFilters($documents, $filters);
            
            Log::info("BaseService: After filtering: " . count($filteredDocuments) . " documents");
            
            return $filteredDocuments;
        } catch (\Exception $e) {
            Log::error("Error getting all documents from {$this->collection}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get document by ID
     */
    protected function getDocumentById(string $id): ?array
    {
        try {
            return $this->firestoreService->collection($this->collection)->find($id);
        } catch (\Exception $e) {
            Log::error("Error getting document from {$this->collection} by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create new document
     */
    protected function createDocument(array $data, string $id = null): ?array
    {
        try {
            $data['created_at'] = now()->toDateTimeString();
            $data['updated_at'] = now()->toDateTimeString();
            
            return $this->firestoreService->collection($this->collection)->create($data, $id);
        } catch (\Exception $e) {
            Log::error("Error creating document in {$this->collection}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update document
     */
    protected function updateDocument(string $id, array $data): bool
    {
        try {
            $data['updated_at'] = now()->toDateTimeString();
            
            Log::info("BaseService: Updating document in '{$this->collection}' with ID: {$id}", [
                'update_data' => array_keys($data)
            ]);
            
            return $this->firestoreService->collection($this->collection)->update($id, $data);
        } catch (\Exception $e) {
            Log::error("Error updating document in {$this->collection}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete document
     */
    protected function deleteDocument(string $id): bool
    {
        try {
            return $this->firestoreService->collection($this->collection)->delete($id);
        } catch (\Exception $e) {
            Log::error("Error deleting document from {$this->collection}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Count documents
     */
    public function countDocuments(): int
    {
        try {
            return $this->firestoreService->collection($this->collection)->count();
        } catch (\Exception $e) {
            Log::error("Error counting documents in {$this->collection}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Check if document exists
     */
    protected function documentExists(string $id): bool
    {
        try {
            return $this->firestoreService->collection($this->collection)->exists($id);
        } catch (\Exception $e) {
            Log::error("Error checking document existence in {$this->collection}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Apply filters to documents array - UPDATED WITH DRIVER-SPECIFIC FILTERS
     */
    protected function applyFilters(array $documents, array $filters): array
    {
        Log::info("BaseService: Starting filter application", [
            'total_documents' => count($documents),
            'filters' => $filters
        ]);

        $filtered = $documents;

        // Apply search filter
        if (!empty($filters['search'])) {
            $search = strtolower(trim($filters['search']));
            Log::info("BaseService: Applying search filter: '{$search}'");
            
            $beforeCount = count($filtered);
            $filtered = array_filter($filtered, function($doc) use ($search) {
                return $this->matchesSearch($doc, $search);
            });
            
            Log::info("BaseService: Search filter applied", [
                'before' => $beforeCount,
                'after' => count($filtered)
            ]);
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            Log::info("BaseService: Applying status filter: '{$filters['status']}'");
            
            $beforeCount = count($filtered);
            $filtered = array_filter($filtered, function($doc) use ($filters) {
                $docStatus = $doc['status'] ?? '';
                return $docStatus === $filters['status'];
            });
            
            Log::info("BaseService: Status filter applied", [
                'before' => $beforeCount,
                'after' => count($filtered),
                'target_status' => $filters['status']
            ]);
        }

        // Apply verification_status filter (DRIVER-SPECIFIC)
        if (!empty($filters['verification_status'])) {
            Log::info("BaseService: Applying verification_status filter: '{$filters['verification_status']}'");
            
            $beforeCount = count($filtered);
            $filtered = array_filter($filtered, function($doc) use ($filters) {
                $docVerificationStatus = $doc['verification_status'] ?? '';
                return $docVerificationStatus === $filters['verification_status'];
            });
            
            Log::info("BaseService: Verification status filter applied", [
                'before' => $beforeCount,
                'after' => count($filtered),
                'target_verification_status' => $filters['verification_status']
            ]);
        }

        // Apply availability_status filter (DRIVER-SPECIFIC)
        if (!empty($filters['availability_status'])) {
            Log::info("BaseService: Applying availability_status filter: '{$filters['availability_status']}'");
            
            $beforeCount = count($filtered);
            $filtered = array_filter($filtered, function($doc) use ($filters) {
                $docAvailabilityStatus = $doc['availability_status'] ?? '';
                return $docAvailabilityStatus === $filters['availability_status'];
            });
            
            Log::info("BaseService: Availability status filter applied", [
                'before' => $beforeCount,
                'after' => count($filtered),
                'target_availability_status' => $filters['availability_status']
            ]);
        }

        // Apply date range filters
        if (!empty($filters['created_from'])) {
            Log::info("BaseService: Applying created_from filter: '{$filters['created_from']}'");
            
            try {
                $dateFrom = Carbon::parse($filters['created_from'])->startOfDay();
                $beforeCount = count($filtered);
                
                $filtered = array_filter($filtered, function($doc) use ($dateFrom) {
                    $docDate = $doc['created_at'] ?? $doc['join_date'] ?? null;
                    if (!$docDate) return true;
                    
                    try {
                        return Carbon::parse($docDate)->gte($dateFrom);
                    } catch (\Exception $e) {
                        return true;
                    }
                });
                
                Log::info("BaseService: Created from filter applied", [
                    'before' => $beforeCount,
                    'after' => count($filtered)
                ]);
            } catch (\Exception $e) {
                Log::warning("BaseService: Invalid created_from date format: " . $e->getMessage());
            }
        }

        if (!empty($filters['created_to'])) {
            Log::info("BaseService: Applying created_to filter: '{$filters['created_to']}'");
            
            try {
                $dateTo = Carbon::parse($filters['created_to'])->endOfDay();
                $beforeCount = count($filtered);
                
                $filtered = array_filter($filtered, function($doc) use ($dateTo) {
                    $docDate = $doc['created_at'] ?? $doc['join_date'] ?? null;
                    if (!$docDate) return true;
                    
                    try {
                        return Carbon::parse($docDate)->lte($dateTo);
                    } catch (\Exception $e) {
                        return true;
                    }
                });
                
                Log::info("BaseService: Created to filter applied", [
                    'before' => $beforeCount,
                    'after' => count($filtered)
                ]);
            } catch (\Exception $e) {
                Log::warning("BaseService: Invalid created_to date format: " . $e->getMessage());
            }
        }

        // Apply limit last to respect filter results
        if (!empty($filters['limit']) && is_numeric($filters['limit'])) {
            $limit = (int)$filters['limit'];
            $beforeCount = count($filtered);
            $filtered = array_slice($filtered, 0, $limit);
            
            Log::info("BaseService: Limit applied", [
                'before' => $beforeCount,
                'after' => count($filtered),
                'limit' => $limit
            ]);
        }

        $finalResult = array_values($filtered); // Re-index array
        
        Log::info("BaseService: Filter application complete", [
            'original_count' => count($documents),
            'final_count' => count($finalResult)
        ]);

        return $finalResult;
    }

    /**
     * Check if document matches search query (override in child classes)
     */
    protected function matchesSearch(array $document, string $search): bool
    {
        // Default search fields - override in child classes for specific fields
        $searchableFields = ['name', 'email', 'phone', 'firebase_uid'];
        
        foreach ($searchableFields as $field) {
            if (isset($document[$field]) && stripos($document[$field], $search) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Filter documents by field value
     */
    protected function filterByField(array $documents, string $field, $value): array
    {
        return array_filter($documents, function($doc) use ($field, $value) {
            return ($doc[$field] ?? null) === $value;
        });
    }

    /**
     * Sort documents by field
     */
    protected function sortDocuments(array $documents, string $field, string $direction = 'asc'): array
    {
        usort($documents, function($a, $b) use ($field, $direction) {
            $valueA = $a[$field] ?? '';
            $valueB = $b[$field] ?? '';
            
            if ($direction === 'desc') {
                return $valueB <=> $valueA;
            }
            
            return $valueA <=> $valueB;
        });
        
        return $documents;
    }

    /**
     * Paginate documents
     */
    protected function paginateDocuments(array $documents, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        
        return [
            'data' => array_slice($documents, $offset, $perPage),
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => count($documents),
            'last_page' => ceil(count($documents) / $perPage)
        ];
    }
}