<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UserFirestoreService
{
    protected $projectId;
    protected $collection;
    protected $accessToken;
    protected $baseUrl;

    public function __construct()
    {
        $this->projectId = env('FIREBASE_PROJECT_ID');
        $this->accessToken = $this->getAccessToken();
        $this->baseUrl = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents";
        
        Log::debug("FirestoreService initialized for project: {$this->projectId}");
    }

    /**
     * Get Firebase access token using service account
     */
    private function getAccessToken(): string
    {
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
            $response = Http::asForm()->post($serviceAccount['token_uri'], [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $this->createJWT($serviceAccount)
            ]);

            if ($response->successful()) {
                $tokenData = $response->json();
                return $tokenData['access_token'];
            }

            Log::error('Failed to get Firebase access token', ['response' => $response->body()]);
            return '';
            
        } catch (\Exception $e) {
            Log::error('Error getting Firebase access token: ' . $e->getMessage());
            return '';
        }
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
        $this->collection = $collectionName;
        Log::debug("FirestoreService: Working with collection '{$collectionName}'");
        return $this;
    }

    /**
     * Create a new document
     */
    public function create(array $data, string $documentId = null): ?array
    {
        try {
            $data['created_at'] = now()->toDateTimeString();
            $data['updated_at'] = now()->toDateTimeString();
            
            // Convert data to Firestore format
            $firestoreData = $this->convertToFirestoreFormat($data);
            
            $url = "{$this->baseUrl}/{$this->collection}";
            if ($documentId) {
                $url .= "?documentId={$documentId}";
            }

            $response = Http::withToken($this->accessToken)
                ->post($url, [
                    'fields' => $firestoreData
                ]);

            if ($response->successful()) {
                $result = $response->json();
                $id = $documentId ?: basename($result['name']);
                
                Log::info("Document created in '{$this->collection}' with ID: {$id}");
                
                $returnData = $this->convertFromFirestoreFormat($result['fields']);
                $returnData['id'] = $id;
                return $returnData;
            }

            Log::error("Firestore create error in '{$this->collection}': " . $response->body());
            return null;
            
        } catch (\Exception $e) {
            Log::error("Firestore create error in '{$this->collection}': " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get a document by ID
     */
    public function find(string $documentId): ?array
    {
        try {
            $url = "{$this->baseUrl}/{$this->collection}/{$documentId}";
            
            $response = Http::withToken($this->accessToken)->get($url);

            if ($response->successful()) {
                $result = $response->json();
                $data = $this->convertFromFirestoreFormat($result['fields'] ?? []);
                $data['id'] = $documentId;
                
                Log::debug("Document found in '{$this->collection}' with ID: {$documentId}", ['data' => $data]);
                return $data;
            }

            if ($response->status() === 404) {
                Log::warning("Document not found in '{$this->collection}' with ID: {$documentId}");
                return null;
            }

            Log::error("Firestore find error in '{$this->collection}': " . $response->body());
            return null;
            
        } catch (\Exception $e) {
            Log::error("Firestore find error in '{$this->collection}': " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all documents in collection
     */
    public function getAll(int $limit = null): array
    {
        try {
            $url = "{$this->baseUrl}/{$this->collection}";
            if ($limit) {
                $url .= "?pageSize={$limit}";
            }

            $response = Http::withToken($this->accessToken)->get($url);

            if ($response->successful()) {
                $result = $response->json();
                $documents = [];

                if (isset($result['documents'])) {
                    foreach ($result['documents'] as $doc) {
                        $data = $this->convertFromFirestoreFormat($doc['fields'] ?? []);
                        $data['id'] = basename($doc['name']);
                        $documents[] = $data;
                        
                        // Debug log each document
                        Log::debug("Document converted", ['id' => $data['id'], 'data' => $data]);
                    }
                }

                Log::debug("Retrieved " . count($documents) . " documents from '{$this->collection}'");
                return $documents;
            }

            Log::error("Firestore getAll error in '{$this->collection}': " . $response->body());
            return [];
            
        } catch (\Exception $e) {
            Log::error("Firestore getAll error in '{$this->collection}': " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update a document
     */
    public function update(string $documentId, array $data): bool
    {
        try {
            $data['updated_at'] = now()->toDateTimeString();
            
            $firestoreData = $this->convertToFirestoreFormat($data);
            $url = "{$this->baseUrl}/{$this->collection}/{$documentId}";

            $response = Http::withToken($this->accessToken)
                ->patch($url, [
                    'fields' => $firestoreData
                ]);

            if ($response->successful()) {
                Log::info("Document updated in '{$this->collection}' with ID: {$documentId}");
                return true;
            }

            Log::error("Firestore update error in '{$this->collection}': " . $response->body());
            return false;
            
        } catch (\Exception $e) {
            Log::error("Firestore update error in '{$this->collection}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a document
     */
    public function delete(string $documentId): bool
    {
        try {
            $url = "{$this->baseUrl}/{$this->collection}/{$documentId}";

            $response = Http::withToken($this->accessToken)->delete($url);

            if ($response->successful()) {
                Log::info("Document deleted from '{$this->collection}' with ID: {$documentId}");
                return true;
            }

            Log::error("Firestore delete error in '{$this->collection}': " . $response->body());
            return false;
            
        } catch (\Exception $e) {
            Log::error("Firestore delete error in '{$this->collection}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Query documents with conditions (simplified version)
     */
    public function where(string $field, string $operator, $value): self
    {
        // For REST API, we'll implement this as a simple filter
        // This is a basic implementation - Firestore REST API requires structured queries for complex conditions
        Log::debug("Where condition set: {$field} {$operator} {$value}");
        return $this;
    }

    /**
     * Order documents (simplified version)
     */
    public function orderBy(string $field, string $direction = 'ASC'): self
    {
        Log::debug("Order by set: {$field} {$direction}");
        return $this;
    }

    /**
     * Limit documents
     */
    public function limit(int $limit): self
    {
        Log::debug("Limit set: {$limit}");
        return $this;
    }

    /**
     * Execute query and get results (simplified)
     */
    public function get(): array
    {
        // For now, just return all documents
        // Complex queries require structured query API
        return $this->getAll();
    }

    /**
     * Count documents in collection
     */
    public function count(): int
    {
        try {
            $documents = $this->getAll();
            $count = count($documents);
            
            Log::debug("Count: {$count} documents in '{$this->collection}'");
            return $count;
            
        } catch (\Exception $e) {
            Log::error("Firestore count error in '{$this->collection}': " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Check if document exists
     */
    public function exists(string $documentId): bool
    {
        try {
            $document = $this->find($documentId);
            $exists = $document !== null;
            
            Log::debug("Document exists check in '{$this->collection}' ID '{$documentId}': " . ($exists ? 'true' : 'false'));
            return $exists;
            
        } catch (\Exception $e) {
            Log::error("Firestore exists error in '{$this->collection}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Convert PHP array to Firestore format - ENHANCED VERSION
     */
    private function convertToFirestoreFormat(array $data): array
    {
        $converted = [];
        
        foreach ($data as $key => $value) {
            if (is_null($value)) {
                $converted[$key] = ['nullValue' => null];
            } elseif (is_string($value)) {
                $converted[$key] = ['stringValue' => $value];
            } elseif (is_int($value)) {
                $converted[$key] = ['integerValue' => (string) $value];
            } elseif (is_float($value)) {
                $converted[$key] = ['doubleValue' => $value];
            } elseif (is_bool($value)) {
                $converted[$key] = ['booleanValue' => $value];
            } elseif (is_array($value)) {
                // Handle indexed arrays as arrayValue
                if (array_keys($value) === range(0, count($value) - 1)) {
                    $arrayValues = [];
                    foreach ($value as $item) {
                        $arrayValues[] = $this->convertSingleValueToFirestore($item);
                    }
                    $converted[$key] = ['arrayValue' => ['values' => $arrayValues]];
                } else {
                    // Handle associative arrays as mapValue
                    $converted[$key] = ['mapValue' => ['fields' => $this->convertToFirestoreFormat($value)]];
                }
            } elseif ($value instanceof \DateTime || $value instanceof \DateTimeInterface) {
                $converted[$key] = ['timestampValue' => $value->format('Y-m-d\TH:i:s\Z')];
            } else {
                // Fallback to string
                $converted[$key] = ['stringValue' => (string) $value];
            }
        }
        
        return $converted;
    }

    /**
     * Convert single value to Firestore format
     */
    private function convertSingleValueToFirestore($value): array
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
            return ['mapValue' => ['fields' => $this->convertToFirestoreFormat($value)]];
        } else {
            return ['stringValue' => (string) $value];
        }
    }

    /**
     * Convert Firestore format to PHP array - ENHANCED VERSION
     */
    private function convertFromFirestoreFormat(array $fields): array
    {
        $converted = [];
        
        foreach ($fields as $key => $value) {
            if (isset($value['nullValue'])) {
                $converted[$key] = null;
            } elseif (isset($value['stringValue'])) {
                $converted[$key] = $value['stringValue'];
            } elseif (isset($value['integerValue'])) {
                $converted[$key] = (int) $value['integerValue'];
            } elseif (isset($value['doubleValue'])) {
                $converted[$key] = $value['doubleValue'];
            } elseif (isset($value['booleanValue'])) {
                $converted[$key] = $value['booleanValue'];
            } elseif (isset($value['timestampValue'])) {
                $converted[$key] = $value['timestampValue'];
            } elseif (isset($value['arrayValue']['values'])) {
                $array = [];
                foreach ($value['arrayValue']['values'] as $item) {
                    $array[] = $this->convertSingleValueFromFirestore($item);
                }
                $converted[$key] = $array;
            } elseif (isset($value['mapValue']['fields'])) {
                $converted[$key] = $this->convertFromFirestoreFormat($value['mapValue']['fields']);
            } elseif (isset($value['geoPointValue'])) {
                $converted[$key] = $value['geoPointValue'];
            } elseif (isset($value['referenceValue'])) {
                $converted[$key] = $value['referenceValue'];
            } else {
                // Log unknown value types for debugging
                Log::warning("Unknown Firestore value type", ['key' => $key, 'value' => $value]);
                $converted[$key] = null;
            }
        }
        
        return $converted;
    }

    /**
     * Convert single Firestore value to PHP value
     */
    private function convertSingleValueFromFirestore(array $value)
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
        } elseif (isset($value['mapValue']['fields'])) {
            return $this->convertFromFirestoreFormat($value['mapValue']['fields']);
        } else {
            return null;
        }
    }
}