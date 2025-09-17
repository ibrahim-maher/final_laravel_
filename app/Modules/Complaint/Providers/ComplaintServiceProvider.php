<?php

namespace App\Modules\Complaint\Providers;

use Illuminate\Support\ServiceProvider;

class ComplaintServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        
        // Load views with namespace
        $this->loadViewsFrom(__DIR__ . '/../Views', 'complaint');
    }

    public function register()
    {
        //
    }
}