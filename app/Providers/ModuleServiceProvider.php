<?php
// app/Providers/ModuleServiceProvider.php - UPDATED WITH ADMIN MODULE

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Log::debug('ModuleServiceProvider: Registering modules');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Log::debug('ModuleServiceProvider: Booting modules');
        $this->loadModuleRoutes();
        $this->loadModuleViews();
    }

    /**
     * Load routes from modules
     */
    protected function loadModuleRoutes(): void
    {
        $modules = [
            'Auth' => '', // No prefix for auth routes
            'Admin' => 'admin', // Admin routes with admin prefix
            'User' => 'user',
            'Document' => 'document',
            'Core' => 'core',
            'Driver' => 'driver',
            'TaxSetting' => 'tax-setting', // Tax Setting module
            'Commission' => 'commission',
            'Coupon' => 'coupon',
            'Page' => 'page',

            'Foq' => 'foq'
        ];

        foreach ($modules as $module => $prefix) {
            $routePath = app_path("Modules/{$module}/routes.php");
            if (file_exists($routePath)) {
                Log::debug("Loading routes for module: {$module} with prefix: {$prefix}");

                // Apply web middleware group to ensure session works
                Route::group([
                    'prefix' => $prefix,
                    'namespace' => "App\\Modules\\{$module}\\Controllers",
                    'middleware' => ['web'] // THIS IS CRITICAL
                ], function () use ($routePath, $module) {
                    require $routePath;
                });
            } else {
                Log::warning("Routes file not found for module: {$module}");
            }
        }
    }

    /**
     * Load views from modules
     */
    protected function loadModuleViews(): void
    {
        $modules = ['Auth', 'Admin', 'User', 'Document', 'Core', 'Driver', 'Coupon', 'Foq', 'TaxSetting', 'Commission', 'Page'];

        foreach ($modules as $module) {
            $viewPath = app_path("Modules/{$module}/Views");
            if (is_dir($viewPath)) {
                $this->loadViewsFrom($viewPath, strtolower($module));
                Log::debug("Loading views for module: {$module}");
            }
        }
    }
}
