<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Cierra automáticamente guardias vencidas.
        $schedule->command('guardias:close-expired --minutes=1440')
            ->everyTwoHours()
            ->withoutOverlapping()
            ->runInBackground();

        // Revisa guardias del periodo 17:00 a 09:00.
        $schedule->command('guardias:notify-missing --url=http://stratosphereoperations.com/inicio')
            ->dailyAt('21:00')
            ->withoutOverlapping();

        $schedule->command('guardias:notify-missing --url=http://stratosphereoperations.com/inicio')
            ->dailyAt('00:00')
            ->withoutOverlapping();

        $schedule->command('guardias:notify-missing --url=http://stratosphereoperations.com/inicio')
            ->dailyAt('09:00')
            ->withoutOverlapping();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}