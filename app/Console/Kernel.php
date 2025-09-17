<?php

namespace App\Console;

use App\Models\FirebaseSyncLog; // Add this import
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Sync unsynced records every 5 minutes
        $schedule->command('firebase:sync --batch')->everyFiveMinutes();
        
        // Monitor sync status hourly
        $schedule->command('firebase:monitor')->hourly();
        
        // Clean old sync logs weekly
        $schedule->command('model:prune', [
            '--model' => [FirebaseSyncLog::class],
        ])->weekly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}