<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;

class FirebaseServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Keep only Firebase Auth (no Firestore client dependency)
        $this->app->singleton('firebase', function ($app) {
            $serviceAccount = [
                'type' => 'service_account',
                'project_id' => env('FIREBASE_PROJECT_ID'),
                'private_key_id' => env('FIREBASE_PRIVATE_KEY_ID'),
                'private_key' => str_replace('\\n', "\n", env('FIREBASE_PRIVATE_KEY')),
                'client_email' => env('FIREBASE_CLIENT_EMAIL'),
                'client_id' => env('FIREBASE_CLIENT_ID'),
                'auth_uri' => env('FIREBASE_AUTH_URI'),
                'token_uri' => env('FIREBASE_TOKEN_URI'),
                'auth_provider_x509_cert_url' => env('FIREBASE_AUTH_PROVIDER_CERT_URL'),
                'client_x509_cert_url' => env('FIREBASE_CLIENT_CERT_URL'),
            ];

            return (new Factory)->withServiceAccount($serviceAccount);
        });

        // Register Firebase Auth
        $this->app->singleton('firebase.auth', function ($app) {
            return $app['firebase']->createAuth();
        });

        // Remove Firestore dependency - we'll use REST API instead
    }

    public function boot()
    {
        //
    }
}