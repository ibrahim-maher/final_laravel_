<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class FirestoreService
{
    protected $projectId;
    protected $accessToken;
    protected $baseUrl;
    protected $currentCollection;

    public function __construct()
    {
        $this->projectId = env('FIREBASE_PROJECT_ID');
        $this->accessToken = $this->getAccessToken();
        $this->baseUrl = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents";
        
        Log::debug("FirestoreService initialized for project: {$this->projectId}");
    }

    /**
     * Get Firebase access token using service account with caching
     */
    private function getAccessToken(): string
    {
        $cacheKey = 'firebase_access_token';
        
        return Cache::remember($cacheKey, 3300, function () { // Cache for 55 minutes (tokens expire in 1 hour)
            try {
                // Create JWT for service account authentication
                $serviceAccount = [
                    'type' => 'service_account',
                    'project_id' => env('FIREBASE_PROJECT_ID'),
                    'private_key_id' => env('FIREBASE_PRIVATE_KEY_ID'),
                    'private_key' => str_replace('\\n', "\n", env('FIREBASE_PRIVATE_KEY')),
                    'client_email' => env('FIREBASE_CLIENT_EMAIL'),
                    'client_id' => env('FIREBASE_CLIENT_ID'),
                    'auth_uri' => env('FIREBASE_AUTH_URI', 'https://accounts.google.com/o/oauth2/auth'),
                    'token_uri' => env('FIREBASE_TOKEN_URI', 'https://oauth2.googleapis.com/token'),
                ];

                // Get access token from Firebase
                $response = Http::timeout(30)->asForm()->post($serviceAccount['token_uri'], [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $this->createJWT($serviceAccount)
                ]);

                if ($response->successful()) {
                    $tokenData = $response->json();
                    Log::debug('Firebase access token obtained successfully');
                    return $tokenData['access_token'];
                }

                Log::error('Failed to get Firebase access token', ['response' => $response->body()]);
                return '';
                
            } catch (\Exception $e) {
                Log::error('Error getting Firebase access token: ' . $e->getMessage());
                return '';
            }
        });
    }

    /**
     * Create JWT for service account
     */
    private function createJWT(array $serviceAccount): string
    {
        $header = json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT'
        ]);

        $now = time();
        $payload = json_encode([
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/datastore',
            'aud' => $serviceAccount['token_uri'],
            'exp' => $now + 3600,
            'iat' => $now
        ]);

        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signature = '';
        openssl_sign(
            $base64Header . '.' . $base64Payload,
            $signature,
            $serviceAccount['private_key'],
            'SHA256'
        );

        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64Header . '.' . $base64Payload . '.' . $base64Signature;
    }

    /**
     * Set the collection to work with
     */
    public function collection(string $collectionName): self
    {
        $this->currentCollection = $collectionName;
        Log::debug("FirestoreService: Working with collection '{$collectionName}'");
        return $this;
    }

    /**
     * Create a new document with retry logic
     */
    public function create(array $data, string $documentId = null): ?array
    {
        return $this->executeWithRetry(function() use ($data, $documentId) {
            $data['created_at'] = now()->toDateTimeString();
            $data['updated_at'] = now()->toDateTimeString();
            
            // Convert data to Firestore format
            $firestoreData = $this->convertToFirestoreFormat($data);
            
            $url = "{$this->baseUrl}/{$this->currentCollection}";
            if ($documentId) {
                $url .= "?documentId={$documentId}";
            }

            $response = Http::timeout(30)
                ->withToken($this->accessToken)
                ->post($url, [
                    'fields' => $firestoreData
                ]);

            if ($response->successful()) {
                $result = $response->json();
                $id = $documentId ?: basename($result['name']);
                
                Log::info("Document created in '{$this->currentCollection}' with ID: {$id}");
                
                $returnData = $this->convertFromFirestoreFormat($result['fields']);
                $returnData['id'] = $id;
                return $returnData;
            }

            Log::error("Firestore create error in '{$this->currentCollection}': " . $response->body());
            return null;
        });
    }

    /**
     * Get a document by ID with caching
     */
    public function find(string $documentId): ?array
    {
        $cacheKey = "firestore_{$this->currentCollection}_{$documentId}";
        
        return Cache::remember($cacheKey, 300, function() use ($documentId) { // Cache for 5 minutes
            return $this->executeWithRetry(function() use ($documentId) {
                $url = "{$this->baseUrl}/{$this->currentCollection}/{$documentId}";
                
                $response = Http::timeout(30)->withToken($this->accessToken)->get($url);

                if ($response->successful()) {
                    $result = $response->json();
                    $data = $this->convertFromFirestoreFormat($result['fields'] ?? []);
                    $data['id'] = $documentId;
                    
                    Log::debug("Document found in '{$this->currentCollection}' with ID: {$documentId}");
                    return $data;
                }

                if ($response->status() === 404) {
                    Log::debug("Document not found in '{$this->currentCollection}' with ID: {$documentId}");
                    return null;
                }

                Log::error("Firestore find error in '{$this->currentCollection}': " . $response->body());
                return null;
            });
        });
    }

    /**
     * Get all documents in collection with optional caching
     */
    public function getAll(int $limit = null, bool $useCache = true): array
    {
        $cacheKey = "firestore_{$this->currentCollection}_all_{$limit}";
        
        if (!$useCache) {
            return $this->fetchAllDocuments($limit);
        }
        
        return Cache::remember($cacheKey, 60, function() use ($limit) { // Cache for 1 minute
            return $this->fetchAllDocuments($limit);
        });
    }

    /**
     * Fetch all documents without caching
     */
    private function fetchAllDocuments(int $limit = null): array
    {
        return $this->executeWithRetry(function() use ($limit) {
            $url = "{$this->baseUrl}/{$this->currentCollection}";
            if ($limit) {
                $url .= "?pageSize={$limit}";
            }

            $response = Http::timeout(60)->withToken($this->accessToken)->get($url);

            if ($response->successful()) {
                $result = $response->json();
                $documents = [];

                if (isset($result['documents'])) {
                    foreach ($result['documents'] as $doc) {
                        $data = $this->convertFromFirestoreFormat($doc['fields'] ?? []);
                        $data['id'] = basename($doc['name']);
                        $documents[] = $data;
                    }
                }

                Log::debug("Retrieved " . count($documents) . " documents from '{$this->currentCollection}'");
                return $documents;
            }

            Log::error("Firestore getAll error in '{$this->currentCollection}': " . $response->body());
            return [];
        });
    }

    /**
     * Update a document with cache invalidation
     */
 /**
 * Update a document with cache invalidation
 */public function update(string $documentId, array $data): bool
{
    return $this->executeWithRetry(function() use ($documentId, $data) {
        $data['updated_at'] = now()->toDateTimeString();
        
        Log::info("FirestoreService: Updating document '{$documentId}' in '{$this->currentCollection}'", [
            'update_fields' => array_keys($data)
        ]);
        
        $firestoreData = $this->convertToFirestoreFormat($data);
        
        // Build updateMask as query parameter
        $fieldPaths = array_keys($data);
        $updateMaskParams = [];
        foreach ($fieldPaths as $field) {
            $updateMaskParams[] = 'updateMask.fieldPaths=' . urlencode($field);
        }
        $updateMaskQuery = implode('&', $updateMaskParams);
        
        $url = "{$this->baseUrl}/{$this->currentCollection}/{$documentId}?{$updateMaskQuery}";

        $response = Http::timeout(30)
            ->withToken($this->accessToken)
            ->patch($url, [
                'fields' => $firestoreData
            ]);

        if ($response->successful()) {
            Log::info("Document updated successfully in '{$this->currentCollection}' with ID: {$documentId}");
            
            // Invalidate cache
            $this->invalidateDocumentCache($documentId);
            
            return true;
        }

        Log::error("Firestore update error in '{$this->currentCollection}': " . $response->body());
        return false;
    });
}

    /**
     * Delete a document with cache invalidation
     */
    public function delete(string $documentId): bool
    {
        return $this->executeWithRetry(function() use ($documentId) {
            $url = "{$this->baseUrl}/{$this->currentCollection}/{$documentId}";

            $response = Http::timeout(30)->withToken($this->accessToken)->delete($url);

            if ($response->successful()) {
                Log::info("Document deleted from '{$this->currentCollection}' with ID: {$documentId}");
                
                // Invalidate cache
                $this->invalidateDocumentCache($documentId);
                
                return true;
            }

            Log::error("Firestore delete error in '{$this->currentCollection}': " . $response->body());
            return false;
        });
    }

    /**
     * Batch operations for multiple documents
     */
    public function batch(array $operations): array
    {
        return $this->executeWithRetry(function() use ($operations) {
            $batchUrl = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents:batchWrite";
            
            $writes = [];
            foreach ($operations as $operation) {
                $writes[] = $this->prepareBatchOperation($operation);
            }

            $response = Http::timeout(60)
                ->withToken($this->accessToken)
                ->post($batchUrl, [
                    'writes' => $writes
                ]);

            if ($response->successful()) {
                $result = $response->json();
                Log::info("Batch operation completed successfully", ['operations_count' => count($operations)]);
                
                // Invalidate related caches
                $this->invalidateCollectionCache();
                
                return $result;
            }

            Log::error("Firestore batch error: " . $response->body());
            return [];
        });
    }

    /**
     * Count documents in collection
     */
    public function count(): int
    {
        $cacheKey = "firestore_{$this->currentCollection}_count";
        
        return Cache::remember($cacheKey, 300, function() { // Cache for 5 minutes
            try {
                $documents = $this->getAll(null, false); // Don't use cache for count
                $count = count($documents);
                
                Log::debug("Count: {$count} documents in '{$this->currentCollection}'");
                return $count;
                
            } catch (\Exception $e) {
                Log::error("Firestore count error in '{$this->currentCollection}': " . $e->getMessage());
                return 0;
            }
        });
    }

    /**
     * Check if document exists
     */
    public function exists(string $documentId): bool
    {
        try {
            $document = $this->find($documentId);
            $exists = $document !== null;
            
            Log::debug("Document exists check in '{$this->currentCollection}' ID '{$documentId}': " . ($exists ? 'true' : 'false'));
            return $exists;
            
        } catch (\Exception $e) {
            Log::error("Firestore exists error in '{$this->currentCollection}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Execute operation with retry logic
     */
    private function executeWithRetry(callable $operation, int $maxRetries = 3)
    {
        $attempts = 0;
        
        while ($attempts < $maxRetries) {
            try {
                return $operation();
            } catch (\Exception $e) {
                $attempts++;
                
                if ($attempts >= $maxRetries) {
                    Log::error("Operation failed after {$maxRetries} attempts: " . $e->getMessage());
                    throw $e;
                }
                
                // Exponential backoff
                $delay = pow(2, $attempts - 1);
                Log::warning("Retry attempt {$attempts} after {$delay} seconds: " . $e->getMessage());
                sleep($delay);
                
                // Refresh token if it might be expired
                if ($attempts > 1) {
                    Cache::forget('firebase_access_token');
                    $this->accessToken = $this->getAccessToken();
                }
            }
        }
    }

    /**
     * Prepare batch operation
     */
    private function prepareBatchOperation(array $operation): array
    {
        $type = $operation['type'];
        $documentPath = "{$this->currentCollection}/{$operation['id']}";
        
        switch ($type) {
            case 'create':
            case 'update':
                return [
                    'update' => [
                        'name' => "projects/{$this->projectId}/databases/(default)/documents/{$documentPath}",
                        'fields' => $this->convertToFirestoreFormat($operation['data'])
                    ]
                ];
                
            case 'delete':
                return [
                    'delete' => "projects/{$this->projectId}/databases/(default)/documents/{$documentPath}"
                ];
                
            default:
                throw new \InvalidArgumentException("Unsupported batch operation type: {$type}");
        }
    }

    /**
     * Invalidate document cache
     */
    private function invalidateDocumentCache(string $documentId): void
    {
        $patterns = [
            "firestore_{$this->currentCollection}_{$documentId}",
            "firestore_{$this->currentCollection}_all_*",
            "firestore_{$this->currentCollection}_count"
        ];
        
        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    /**
     * Invalidate collection cache
     */
    private function invalidateCollectionCache(): void
    {
        // This is a simplified approach - in production you might want to use cache tags
        Cache::flush(); // Note: This clears ALL cache, consider using more specific invalidation
    }

    /**
     * Convert PHP array to Firestore format
     */
    private function convertToFirestoreFormat(array $data): array
    {
        $converted = [];
        
        foreach ($data as $key => $value) {
            $converted[$key] = $this->convertValueToFirestore($value);
        }
        
        return $converted;
    }

    /**
     * Convert single value to Firestore format
     */
    private function convertValueToFirestore($value): array
    {
        if (is_null($value)) {
            return ['nullValue' => null];
        } elseif (is_string($value)) {
            return ['stringValue' => $value];
        } elseif (is_int($value)) {
            return ['integerValue' => (string) $value];
        } elseif (is_float($value)) {
            return ['doubleValue' => $value];
        } elseif (is_bool($value)) {
            return ['booleanValue' => $value];
        } elseif (is_array($value)) {
            // Handle indexed arrays as arrayValue
            if (array_keys($value) === range(0, count($value) - 1)) {
                $arrayValues = [];
                foreach ($value as $item) {
                    $arrayValues[] = $this->convertValueToFirestore($item);
                }
                return ['arrayValue' => ['values' => $arrayValues]];
            } else {
                // Handle associative arrays as mapValue
                return ['mapValue' => ['fields' => $this->convertToFirestoreFormat($value)]];
            }
        } elseif ($value instanceof \DateTime || $value instanceof \DateTimeInterface) {
            return ['timestampValue' => $value->format('Y-m-d\TH:i:s\Z')];
        } else {
            // Fallback to string
            return ['stringValue' => (string) $value];
        }
    }

    /**
     * Convert Firestore format to PHP array
     */
    private function convertFromFirestoreFormat(array $fields): array
    {
        $converted = [];
        
        foreach ($fields as $key => $value) {
            $converted[$key] = $this->convertValueFromFirestore($value);
        }
        
        return $converted;
    }

    /**
     * Convert single Firestore value to PHP value
     */
    private function convertValueFromFirestore(array $value)
    {
        if (isset($value['nullValue'])) {
            return null;
        } elseif (isset($value['stringValue'])) {
            return $value['stringValue'];
        } elseif (isset($value['integerValue'])) {
            return (int) $value['integerValue'];
        } elseif (isset($value['doubleValue'])) {
            return $value['doubleValue'];
        } elseif (isset($value['booleanValue'])) {
            return $value['booleanValue'];
        } elseif (isset($value['timestampValue'])) {
            return $value['timestampValue'];
        } elseif (isset($value['arrayValue']['values'])) {
            $array = [];
            foreach ($value['arrayValue']['values'] as $item) {
                $array[] = $this->convertValueFromFirestore($item);
            }
            return $array;
        } elseif (isset($value['mapValue']['fields'])) {
            return $this->convertFromFirestoreFormat($value['mapValue']['fields']);
        } elseif (isset($value['geoPointValue'])) {
            return $value['geoPointValue'];
        } elseif (isset($value['referenceValue'])) {
            return $value['referenceValue'];
        } else {
            // Log unknown value types for debugging
            Log::warning("Unknown Firestore value type", ['value' => $value]);
            return null;
        }
    }

    /**
     * Clear all cache for this collection
     */
    public function clearCache(): void
    {
        $this->invalidateCollectionCache();
        Log::info("Cache cleared for collection '{$this->currentCollection}'");
    }

    /**
     * Get collection statistics
     */
    public function getStats(): array
    {
        try {
            return [
                'collection' => $this->currentCollection,
                'document_count' => $this->count(),
                'last_updated' => now()->toDateTimeString()
            ];
        } catch (\Exception $e) {
            Log::error("Error getting collection stats: " . $e->getMessage());
            return [
                'collection' => $this->currentCollection,
                'document_count' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Health check for Firestore connection
     */
    public function healthCheck(): array
    {
        try {
            $testCollection = 'health_check';
            $testDoc = [
                'test' => true,
                'timestamp' => now()->toDateTimeString()
            ];
            
            // Try to create a test document
            $result = $this->collection($testCollection)->create($testDoc, 'health_check_' . time());
            
            if ($result) {
                // Clean up test document
                $this->collection($testCollection)->delete($result['id']);
                
                return [
                    'status' => 'healthy',
                    'message' => 'Firestore connection is working',
                    'timestamp' => now()->toDateTimeString()
                ];
            } else {
                return [
                    'status' => 'unhealthy',
                    'message' => 'Failed to create test document',
                    'timestamp' => now()->toDateTimeString()
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Firestore connection error: ' . $e->getMessage(),
                'timestamp' => now()->toDateTimeString()
            ];
        }
    }

    /**
     * Simple query methods (for basic filtering)
     */
    public function where(string $field, string $operator, $value): self
    {
        // Note: Full query implementation would require structured queries
        // This is a placeholder for future implementation
        Log::debug("Where condition set: {$field} {$operator} {$value}");
        return $this;
    }

    public function orderBy(string $field, string $direction = 'ASC'): self
    {
        Log::debug("Order by set: {$field} {$direction}");
        return $this;
    }

    public function limit(int $limit): self
    {
        Log::debug("Limit set: {$limit}");
        return $this;
    }

    public function get(): array
    {
        // For now, just return all documents
        // Complex queries require structured query API implementation
        return $this->getAll();
    }
}