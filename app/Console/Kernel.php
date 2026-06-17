<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
         $schedule->command('guardias:close-expired --minutes=1440')
        ->everyTwoHours()
        ->withoutOverlapping()
        ->runInBackground();

           $schedule->command('guardias:notify-missing --cooldown=180 --url=http://stratosphereoperations.com/inicio')
        ->everyThreeHours()
        ->withoutOverlapping();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
