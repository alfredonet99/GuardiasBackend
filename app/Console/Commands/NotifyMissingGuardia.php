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

    protected $description = 'Si NO hay guardia activa, envía email para iniciar guardia (solo a las 08:00 y 21:00, con cooldown).';

    public function handle(): int
    {
        // ✅ Solo ejecuta la lógica a las 08:00 AM y 09:00 PM
        if (! $this->isAllowedTime()) {
            $this->info("🕒 Fuera de horario permitido (08:00 / 21:00). No se evalúa.");
            return Command::SUCCESS;
        }

        $url = (string) $this->option('url');
        $cooldownMinutes = (int) $this->option('cooldown');

        // ✅ ¿Hay guardia activa?
        $hasActive = Guardias::query()
            ->whereNull('dateFinish')
            ->where('status', 1)
            ->exists();

        if ($hasActive) {
            $this->info("✅ Sí hay guardia activa. No se notifica.");
            return Command::SUCCESS;
        }

        // ✅ Anti-spam: 1 aviso cada X minutos
        $cacheKey = 'guardias:missing:cooldown';

        if (Cache::has($cacheKey)) {
            $this->info("⏳ Sin guardia, pero en cooldown. No se notifica.");
            return Command::SUCCESS;
        }

        // Si cooldown=0, no bloquees
        if ($cooldownMinutes > 0) {
            Cache::put($cacheKey, true, now()->addMinutes($cooldownMinutes));
        }

        // ✅ Destino fijo
        $to = 'operationsstratosphere@stratospherecorp.com';

        Mail::to($to)->send(new GuardiaMissingMail($url));

        $this->info("📩 Notificación enviada: NO hay guardia activa.");

        Log::info('guardias:notify-missing sent', [
            'to' => $to,
            'url' => $url,
            'cooldown' => $cooldownMinutes,
            'now' => now()->toDateTimeString(),
        ]);

        return Command::SUCCESS;
    }

    private function isAllowedTime(): bool
    {
        // Usa timezone de Laravel (config/app.php)
        // Solo permitir exactamente 08:00 y 21:00
        $hm = now()->format('H:i');

        return $hm === '08:00' || $hm === '21:00';
    }
}