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
                            {--cooldown=240}
                            {--url=http://localhost:5173/inicio}';

    protected $description = 'Si NO hay guardia activa, envía email para iniciar guardia (con link).';

    public function handle(): int
    {
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

        // ✅ Anti-spam: 1 aviso cada X minutos (por defecto 240 = 4h)
        $cacheKey = 'guardias:missing:cooldown';

        if (Cache::has($cacheKey)) {
            $this->info("⏳ Sin guardia, pero en cooldown. No se notifica.");
            return Command::SUCCESS;
        }

        Cache::put($cacheKey, true, now()->addMinutes($cooldownMinutes));

        // ✅ Mismo destino que CloseGuardia
        $to = 'alfredo10000z@gmail.com';

        Mail::to($to)->send(new GuardiaMissingMail($url));

        $this->info("📩 Notificación enviada: NO hay guardia activa.");

        Log::info('guardias:notify-missing sent', [
            'to' => $to,
            'url' => $url,
            'cooldown' => $cooldownMinutes,
        ]);

        return Command::SUCCESS;
    }
}
