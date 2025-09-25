<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;
use Illuminate\Support\Facades\Log;

class FirebaseServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('firebase', function ($app) {
            try {
                Log::info('Firebase Provider: Starting registration with hardcoded values');

                // TEMPORARY: Hardcode the values to bypass environment issues
                $projectId = 'freight-transport-f8a55';
                $clientEmail = 'firebase-adminsdk-fbsvc@freight-transport-f8a55.iam.gserviceaccount.com';
                $privateKeyId = '365afcb6eea552aa746ab844dd55d4ef67cb66f6';
                $clientId = '103248421948722998043';
                $privateKey = "-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDYwybJ3Xqe8nlu\niOFgXpQKAKmE3Q4Abr/BzMiDetX9h4HBuo0UKZN+E3u1KwzU67rR3N+c5ZuqKZig\n1EU1hpJePr8WRScMPil3tMnOGQPTxhWA55BTm3TL/fz6Q2FptryPzLmE4h/GMV91\n5j9IIU/cnbbHwrNZwvecP8kVAz8HNYqOwqQTnufF4dbxEh0My+d27XqTnjpO98pD\n/3CMSHzqGKx6KkYhTYFnlq/9xd6dh/3nYqOQaKLm23cdzuU+L0pjbotTgTHjN1ZO\nj6MZ+cXnPiU++eIeJ16YPt+0D9R7DJlZomk5MmjH7cLtBeJbCzErNxNrtCShwPTN\n+pnTWma3AgMBAAECggEAIgmK5rnrjlf+73d5BHv1fRibhex8TV8Wp2Tzu4mnXpdP\nrc8QZjEdIvgGPe2Tpz+Y52lqh6Waav77I44RjBUkmL37nZCgUBDWzCBMbBuLeU6q\ng8JY7HgFwB6TAe1gt4vlUiNQomgrmyCXn9jW6QmqE7eEQxv0s8ykuxldBvqqhAP/\nGdNt/SHvWQFepwKST5+Lt1//utcgL7jKEHemVMGoAZyrBPzxYEgLYjc4IGci9PiT\nHb7JbLwW/r9KrAUTCx/IcjF+w3C/glTsooBlwZFH7oSzzdvtwnD4fk6dkm53DSUC\nnemrlIn6hkc8xb5RAzidzqRv45ngzjuFXDys5iRLiQKBgQD+YyhCoX63j2MZus9R\nk8VY80njGlVNZBIOOggOhzTt366/SngputgFkYCv7KHwfFV49fzF3NHBw1oC6YVm\nLjlS+Tz+bDuD32DmbKB3jF0myhcdoa/Gsg2+T28zr0vaM0ZcK2Nwma4wMbqnmHRB\nynHnCEg5DroyiQVKnMUFyCjOkwKBgQDaIu7W8lBvn6DnC0tYHg6oaYa1OsZZoUPF\ngqW+mT0h88jc5qkSZPsTpg9f7IqCoylpmJ4U8Dl59OfVYy9TOcgnxklO442MTk5Q\n2fkdObQqst2v3MeqkzZI9l59GeJp+9nY6DAO4cwi42vF9L5vZXKuYXcpK3QtP84o\na2siF0D5zQKBgGIRdebh/UjkhS7ZHq1zS0Q0XkqnzzTLnE5RvuNi6lu9vM9P9S0Z\nM8hJxJONpQxh0k2Uf0MEEvUgy2WOAvhWX5EGNqZasULwbZnHTMFpokue4vRwbaQq\n5jN0ygjhzlsrIzfLHkW9aTJ5KV0M39yxH+ISBk3AyLVMr3aJI0dMV7bzAoGALQzv\n5MaQpC0EjxL/EYjLoC6DGqSz2Ej89SqhTnbZcEyn3C9rFZhzXkB6hmYUyRwnbl6N\nr2dZh31z79cXLAoP8175PuiyEBsQA5Sw5T9InVTpgeuH9QuIN5NiOlYBM8BG4ow5\neKlbfo0Xcf+04M7D243XjVIjIUE/M4vTyWuiCLUCgYEA+uPY/2Nh4v7rAsmR3Xub\nSt/zwuXx0VIkG9lH8s4RMpO/Zq/nGzp1Zsdvt9lap4+Wzndq3ewK08wd0pnm5GOg\nzBsO5O2dMqr+xL2SX4nQ8qBA6i5HMziormih57gxzjGo0+XY/1L8uQPNnGwkMLx4\nUoF1MoZ1hsD1gJtWdaoeFTY=\n-----END PRIVATE KEY-----";

                Log::info('Firebase Provider: Using hardcoded credentials', [
                    'project_id' => $projectId,
                    'client_email' => $clientEmail,
                    'has_private_key' => !empty($privateKey)
                ]);

                // Build service account array
                $serviceAccount = [
                    'type' => 'service_account',
                    'project_id' => $projectId,
                    'private_key_id' => $privateKeyId,
                    'private_key' => $privateKey,
                    'client_email' => $clientEmail,
                    'client_id' => $clientId,
                    'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
                    'token_uri' => 'https://oauth2.googleapis.com/token',
                    'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
                    'client_x509_cert_url' => 'https://www.googleapis.com/robot/v1/metadata/x509/firebase-adminsdk-fbsvc%40freight-transport-f8a55.iam.gserviceaccount.com',
                    'universe_domain' => 'googleapis.com'
                ];

                Log::info('Firebase Provider: Creating Firebase Factory');
                $factory = (new Factory())->withServiceAccount($serviceAccount);

                Log::info('Firebase Provider: Firebase Factory created successfully');
                return $factory;
            } catch (\Exception $e) {
                Log::error('Firebase Provider Error: ' . $e->getMessage());
                throw new \Exception('Firebase initialization failed: ' . $e->getMessage(), 0, $e);
            }
        });

        // Register Firebase Auth
        $this->app->singleton('firebase.auth', function ($app) {
            try {
                Log::info('Firebase Provider: Creating Auth Service');
                $firebase = $app['firebase'];
                $auth = $firebase->createAuth();
                Log::info('Firebase Provider: Auth service created successfully');
                return $auth;
            } catch (\Exception $e) {
                Log::error('Firebase Auth Provider Error: ' . $e->getMessage());
                throw new \Exception('Firebase Auth initialization failed: ' . $e->getMessage(), 0, $e);
            }
        });
    }

    public function boot()
    {
        Log::info('Firebase Provider: Boot method called');
    }
}
