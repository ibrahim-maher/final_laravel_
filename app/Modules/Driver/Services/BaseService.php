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
     * Get all documents with optional filters - OPTIMIZED VERSION
     */
    protected function getAllDocuments(array $filters = []): array
    {
        try {
            Log::info("BaseService: Getting documents from '{$this->collection}' with filters", $filters);
            
            // Try Firestore native filtering first
            $documents = $this->getDocumentsWithNativeFiltering($filters);
            
            // Apply remaining filters in PHP (search, complex filters)
            $filteredDocuments = $this->applyRemainingFilters($documents, $filters);
            
            Log::info("BaseService: After filtering: " . count($filteredDocuments) . " documents");
            
            return $filteredDocuments;
        } catch (\Exception $e) {
            Log::error("Error getting all documents from {$this->collection}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get documents using Firestore native filtering
     */
    protected function getDocumentsWithNativeFiltering(array $filters = []): array
    {
        try {
            $limit = min($filters['limit'] ?? 25, 50);
            
            // Check if we can use native Firestore queries
            if ($this->canUseNativeFiltering($filters)) {
                return $this->executeNativeQuery($filters, $limit);
            }
            
            // Fallback to getting all documents
            return $this->firestoreService->collection($this->collection)->getAll($limit, false);
            
        } catch (\Exception $e) {
            Log::error("Error in native filtering: " . $e->getMessage());
            // Fallback to original method
            return $this->firestoreService->collection($this->collection)->getAll($limit, false);
        }
    }

    /**
     * Check if we can use Firestore native filtering
     */
    protected function canUseNativeFiltering(array $filters): bool
    {
        // Only use native filtering if we have simple equality filters
        $nativeFilterableFields = ['status', 'verification_status', 'availability_status'];
        
        foreach ($nativeFilterableFields as $field) {
            if (!empty($filters[$field])) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Execute native Firestore query
     */
    protected function executeNativeQuery(array $filters, int $limit): array
    {
        // Note: This is pseudocode - implement based on your FirestoreService capabilities
        // You'll need to add these methods to your FirestoreService class
        
        $query = $this->firestoreService->collection($this->collection);
        
        // Apply simple equality filters
        if (!empty($filters['status'])) {
            $query = $query->where('status', '=', $filters['status']);
        }
        
        if (!empty($filters['verification_status'])) {
            $query = $query->where('verification_status', '=', $filters['verification_status']);
        }
        
        if (!empty($filters['availability_status'])) {
            $query = $query->where('availability_status', '=', $filters['availability_status']);
        }
        
        // Add date range filters if supported
        if (!empty($filters['created_from'])) {
            $query = $query->where('created_at', '>=', $filters['created_from']);
        }
        
        if (!empty($filters['created_to'])) {
            $query = $query->where('created_at', '<=', $filters['created_to']);
        }
        
        return $query->limit($limit)->get();
    }

    /**
     * Apply remaining filters that can't be done at database level
     */
    protected function applyRemainingFilters(array $documents, array $filters): array
    {
        $filtered = $documents;

        // Apply search filter (can't be done natively in Firestore)
        if (!empty($filters['search'])) {
            $filtered = $this->applySearchFilter($filtered, trim($filters['search']));
        }

        // Apply date filters if not done natively
        if (!$this->canUseNativeFiltering($filters)) {
            $filtered = $this->applyDateFilters($filtered, $filters);
        }

        // Apply limit if not done natively
        if (!empty($filters['limit']) && count($filtered) > $filters['limit']) {
            $filtered = array_slice($filtered, 0, (int)$filters['limit']);
        }

        return array_values($filtered); // Re-index array
    }

    /**
     * Apply search filter to documents
     */
    protected function applySearchFilter(array $documents, string $search): array
    {
        if (empty($search)) {
            return $documents;
        }

        $search = strtolower($search);
        Log::info("BaseService: Applying search filter: '{$search}'");
        
        $beforeCount = count($documents);
        $filtered = array_filter($documents, function($doc) use ($search) {
            return $this->matchesSearch($doc, $search);
        });
        
        Log::info("BaseService: Search filter applied", [
            'before' => $beforeCount,
            'after' => count($filtered)
        ]);

        return $filtered;
    }

    /**
     * Apply date filters to documents
     */
    protected function applyDateFilters(array $documents, array $filters): array
    {
        $filtered = $documents;

        // Apply created_from filter
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

        // Apply created_to filter
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

        return $filtered;
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

    /**
     * LEGACY METHOD - Keep for backwards compatibility
     * Remove the old applyFilters method and replace with applyRemainingFilters
     */
    protected function applyFilters(array $documents, array $filters): array
    {
        Log::warning("BaseService: Using legacy applyFilters method, consider updating to use applyRemainingFilters");
        return $this->applyRemainingFilters($documents, $filters);
    }
}