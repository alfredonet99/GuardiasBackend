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
                            {--url=http://localhost:5173/inicio}';

    protected $description = 'Si NO hay guardia activa, envía email para iniciar guardia (solo 18:00 a 09:00, cada 3h).';

    public function handle(): int
    {
        // Ventana: 18:00 -> 09:00 (cruza medianoche)
        if (! $this->isWithinWindow()) {
            $this->info("🕒 Fuera de ventana (18:00-09:00). No se evalúa.");
            return Command::SUCCESS;
        }

        $url = (string) $this->option('url');
        $cooldownMinutes = (int) $this->option('cooldown');

        // ¿Hay guardia activa?
        $hasActive = Guardias::query()
            ->whereNull('dateFinish')
            ->where('status', 1)
            ->exists();

        if ($hasActive) {
            $this->info("✅ Sí hay guardia activa. No se notifica.");
            return Command::SUCCESS;
        }

        // Anti-spam: 1 aviso cada X minutos
        $cacheKey = 'guardias:missing:cooldown';

        if (Cache::has($cacheKey)) {
            $this->info("⏳ Sin guardia, pero en cooldown. No se notifica.");
            return Command::SUCCESS;
        }

        // Si cooldown=0, no bloquees
        if ($cooldownMinutes > 0) {
            Cache::put($cacheKey, true, now()->addMinutes($cooldownMinutes));
        }

        // Destino fijo
        $to = 'avillavicencio@teamnet.com.mx';

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

    private function isWithinWindow(): bool
    {
        // Usa timezone de Laravel (config/app.php)
        $now = now();
        $hour = (int) $now->format('H');

        // 18:00 a 23:59  => hour >= 18
        // 00:00 a 08:59  => hour < 9
        // 09:00 exacto   => incluye 9:00? si quieres exacto a las 09:00, mejor validar por H:i.
        // Para permitir envío a las 09:00 exacto con scheduler (que corre 09:00), usamos H:i:
        $hm = $now->format('H:i');

        return ($hm >= '18:00' && $hm <= '23:59') || ($hm >= '00:00' && $hm <= '09:00');
    }
}
