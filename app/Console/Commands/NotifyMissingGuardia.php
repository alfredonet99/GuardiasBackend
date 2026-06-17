<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\Operaciones\Guardias;
use App\Mail\GuardiaMissingMail;

class NotifyMissingGuardia extends Command
{
    protected $signature = 'guardias:notify-missing
                            {--cooldown=180}
                            {--url=http://stratosphereoperations.com/inicio}';

    protected $description = 'Si NO hay guardia activa del día actual, envía email para iniciar guardia en horarios establecidos.';

    public function handle(): int
    {
        if (! $this->isAllowedTime()) {
            $this->info("🕒 Fuera de horario permitido (08:00 / 21:00). No se evalúa.");
            return Command::SUCCESS;
        }

        $url = (string) $this->option('url');
        $cooldownMinutes = (int) $this->option('cooldown');

        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        /*
         * Nueva regla:
         * Solo se considera guardia activa si fue iniciada HOY.
         *
         * Esto evita que una guardia activa de ayer bloquee
         * la notificación del día actual.
         */
        $hasActiveToday = Guardias::query()
            ->whereNull('dateFinish')
            ->where('status', 1)
            ->whereBetween('dateInit', [$todayStart, $todayEnd])
            ->exists();

        if ($hasActiveToday) {
            $this->info("✅ Sí hay guardia activa del día actual. No se notifica.");
            return Command::SUCCESS;
        }

        $cacheKey = 'guardias:missing:cooldown:' . now()->format('Y-m-d-H');

        if (Cache::has($cacheKey)) {
            $this->info("⏳ Sin guardia activa del día, pero en cooldown. No se notifica.");
            return Command::SUCCESS;
        }

        if ($cooldownMinutes > 0) {
            Cache::put($cacheKey, true, now()->addMinutes($cooldownMinutes));
        }

        $to = 'operationsstratosphere@stratospherecorp.com';

        Mail::to($to)->send(new GuardiaMissingMail($url));

        $this->info("📩 Notificación enviada: NO hay guardia activa del día actual.");

        Log::info('guardias:notify-missing sent', [
            'to' => $to,
            'url' => $url,
            'cooldown' => $cooldownMinutes,
            'today_start' => $todayStart->toDateTimeString(),
            'today_end' => $todayEnd->toDateTimeString(),
            'now' => now()->toDateTimeString(),
        ]);

        return Command::SUCCESS;
    }

    private function isAllowedTime(): bool
    {
        $hm = now()->format('H:i');

        return $hm === '08:00' || $hm === '21:00';
    }
}