<?php

return [
    App\Providers\AppServiceProvider::class,
        App\Providers\FirebaseServiceProvider::class,
            App\Providers\ModuleServiceProvider::class,
    Kreait\Laravel\Firebase\ServiceProvider::class,
    App\Modules\Complaint\Providers\ComplaintServiceProvider::class,


];
