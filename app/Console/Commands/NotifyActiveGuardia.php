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
    private const DESTINATARIO = 'operationsstratosphere@stratospherecorp.com';

    protected $signature = 'guardias:notify-missing
                            {--cooldown=180}
                            {--url=http://stratosphereoperations.com/inicio}';

    protected $description = 'Verifica guardias faltantes y envía recordatorios de guardias activas.';

    public function handle(): int
    {
        $hm = now()->format('H:i');

        $recordatorios = [
            '08:00' => [
                'titulo' => 'Primer recordatorio del día.',
                'subject' => 'Recordatorio - Guardias activas 08:00 AM',
            ],
            '12:00' => [
                'titulo' => 'Segundo recordatorio del día. Las siguientes guardias continúan activas al mediodía.',
                'subject' => 'Segundo recordatorio - Guardias activas 12:00 PM',
            ],
            '16:00' => [
                'titulo' => 'Tercer recordatorio del día. Las siguientes guardias continúan activas a las 04:00 PM.',
                'subject' => 'Tercer recordatorio - Guardias activas 04:00 PM',
            ],
        ];

        // Envía recordatorios de guardias activas en los horarios establecidos.
        if (isset($recordatorios[$hm])) {
            $this->notifyActiveGuardias(
                $hm,
                $recordatorios[$hm]['titulo'],
                $recordatorios[$hm]['subject']
            );

            if ($hm === '08:00') {
                return $this->notifyMissingGuardia();
            }

            return Command::SUCCESS;
        }

        // A las 21:00 verifica si falta una guardia del día.
        if ($hm === '21:00') {
            return $this->notifyMissingGuardia();
        }

        $this->info('Fuera de horario permitido: 08:00, 12:00, 16:00 o 21:00.');

        return Command::SUCCESS;
    }

    private function notifyActiveGuardias(
        string $horario,
        string $titulo,
        string $subject
    ): int {
        $guardias = Guardias::with('user:id,name,email')
            ->whereNull('dateFinish')
            ->where('status', 1)
            ->orderBy('dateInit')
            ->get();

        // Si no existen guardias activas no se envía correo.
        if ($guardias->isEmpty()) {
            $this->info("{$horario}: No hay guardias activas.");

            Log::info('guardias:active-reminder no-active', [
                'schedule' => $horario,
                'now' => now()->toDateTimeString(),
            ]);

            return Command::SUCCESS;
        }

        $mensaje = "RECORDATORIO DE GUARDIAS ACTIVAS\n\n";
        $mensaje .= "{$titulo}\n\n";
        $mensaje .= "Actualmente existen las siguientes guardias activas:\n\n";

        foreach ($guardias as $guardia) {
            $nombre = $guardia->user?->name ?? 'Usuario no encontrado';
            $email = $guardia->user?->email ?? 'Sin correo';
            $fechaInicio = $guardia->dateInit ?? 'Sin fecha';

            $mensaje .= "----------------------------------------\n";
            $mensaje .= "Guardia ID: {$guardia->id}\n";
            $mensaje .= "Usuario: {$nombre}\n";
            $mensaje .= "Correo: {$email}\n";
            $mensaje .= "Inicio: {$fechaInicio}\n";
            $mensaje .= "Estado: ACTIVA\n";
        }

        $mensaje .= "\n----------------------------------------\n\n";
        $mensaje .= "Esto es un recordatorio diario de guardias activas.\n";
        $mensaje .= "No olvides cerrar tu guardia si se encuentra activa.";

        Mail::raw($mensaje, function ($message) use ($subject) {
            $message
                ->to(self::DESTINATARIO)
                ->subject($subject);
        });

        $this->info("{$horario}: Recordatorio enviado. Guardias activas: {$guardias->count()}.");

        Log::info('guardias:active-reminder sent', [
            'schedule' => $horario,
            'to' => self::DESTINATARIO,
            'active_guardias' => $guardias->count(),
            'guardias' => $guardias->pluck('id')->toArray(),
            'now' => now()->toDateTimeString(),
        ]);

        return Command::SUCCESS;
    }

    private function notifyMissingGuardia(): int
    {
        $url = (string) $this->option('url');
        $cooldownMinutes = (int) $this->option('cooldown');

        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        // Verifica si existe una guardia activa iniciada durante el día actual.
        $hasActiveToday = Guardias::query()
            ->whereNull('dateFinish')
            ->where('status', 1)
            ->whereBetween('dateInit', [$todayStart, $todayEnd])
            ->exists();

        if ($hasActiveToday) {
            $this->info('Sí hay guardia activa del día actual.');

            return Command::SUCCESS;
        }

        $cacheKey = 'guardias:missing:cooldown:' . now()->format('Y-m-d-H');

        if (Cache::has($cacheKey)) {
            $this->info('Sin guardia activa del día, pero la notificación está en cooldown.');

            return Command::SUCCESS;
        }

        if ($cooldownMinutes > 0) {
            Cache::put(
                $cacheKey,
                true,
                now()->addMinutes($cooldownMinutes)
            );
        }

        Mail::to(self::DESTINATARIO)->send(
            new GuardiaMissingMail($url)
        );

        $this->info('Notificación enviada: no hay guardia activa del día actual.');

        Log::info('guardias:notify-missing sent', [
            'to' => self::DESTINATARIO,
            'url' => $url,
            'cooldown' => $cooldownMinutes,
            'today_start' => $todayStart->toDateTimeString(),
            'today_end' => $todayEnd->toDateTimeString(),
            'now' => now()->toDateTimeString(),
        ]);

        return Command::SUCCESS;
    }
}