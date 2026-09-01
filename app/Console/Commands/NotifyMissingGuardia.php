<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\Operaciones\Guardias;
use App\Mail\GuardiaMissingMail;

class NotifyMissingGuardia extends Command
{
    private const DESTINATARIO = 'operationsstratosphere@stratospherecorp.com';

    protected $signature = 'guardias:notify-missing
                            {--url=http://stratosphereoperations.com/inicio}';

    protected $description = 'Evalúa guardias entre las 17:00 y las 09:00 del día siguiente.';

    public function handle(): int
    {
        $hora = now()->format('H:i');

        if (!in_array($hora, ['21:00', '00:00', '09:00'], true)) {
            $this->info('Fuera de horario de evaluación: 21:00, 00:00 o 09:00.');
            return Command::SUCCESS;
        }

        [$inicio, $fin] = $this->getPeriodo($hora);

        // A las 09:00 confirma si hubo algún inicio durante todo el periodo.
        if ($hora === '09:00') {
            $huboInicio = Guardias::query()
                ->whereBetween('dateInit', [$inicio, $fin])
                ->exists();

            if ($huboInicio) {
                $this->info('Se registró al menos un inicio de guardia durante el periodo.');
                return Command::SUCCESS;
            }

            return $this->notifyFinalMissing($inicio, $fin);
        }

        // A las 21:00 y 00:00 verifica si existe una guardia activa del periodo.
        $hasActiveGuardia = Guardias::query()
            ->whereNull('dateFinish')
            ->where('status', 1)
            ->whereBetween('dateInit', [$inicio, now()])
            ->exists();

        if ($hasActiveGuardia) {
            $this->info('Existe una guardia activa dentro del periodo.');
            return Command::SUCCESS;
        }

        return $this->notifyReminder($hora, $inicio, $fin);
    }

    private function getPeriodo(string $hora): array
    {
        if ($hora === '21:00') {
            $inicio = now()->copy()->startOfDay()->setTime(17, 0);
            $fin = now()->copy()->addDay()->startOfDay()->setTime(9, 0);

            return [$inicio, $fin];
        }

        $inicio = now()->copy()->subDay()->startOfDay()->setTime(17, 0);
        $fin = now()->copy()->startOfDay()->setTime(9, 0);

        return [$inicio, $fin];
    }

    private function notifyReminder(string $hora, $inicio, $fin): int
    {
        $url = (string) $this->option('url');

        Mail::to(self::DESTINATARIO)->send(
            new GuardiaMissingMail($url)
        );

        $this->info("{$hora}: No hay guardia activa. Recordatorio enviado.");

        Log::warning('guardias:missing-reminder', [
            'schedule' => $hora,
            'to' => self::DESTINATARIO,
            'period_start' => $inicio->toDateTimeString(),
            'period_end' => $fin->toDateTimeString(),
            'now' => now()->toDateTimeString(),
        ]);

        return Command::SUCCESS;
    }

    private function notifyFinalMissing($inicio, $fin): int
    {
        $mensaje = "NO HUBO INICIO DE GUARDIA\n\n";
        $mensaje .= "Se confirma que no se registró ningún inicio de guardia durante el periodo establecido.\n\n";
        $mensaje .= "Periodo evaluado:\n";
        $mensaje .= "Desde: {$inicio->format('d/m/Y H:i')}\n";
        $mensaje .= "Hasta: {$fin->format('d/m/Y H:i')}\n\n";
        $mensaje .= "El periodo concluyó sin registro de inicio de guardia.";

        Mail::raw($mensaje, function ($message) {
            $message
                ->to(self::DESTINATARIO)
                ->subject('Alerta - No hubo inicio de guardia');
        });

        $this->info('09:00: Se confirma que no hubo inicio de guardia.');

        Log::warning('guardias:missing-final', [
            'to' => self::DESTINATARIO,
            'period_start' => $inicio->toDateTimeString(),
            'period_end' => $fin->toDateTimeString(),
            'now' => now()->toDateTimeString(),
        ]);

        return Command::SUCCESS;
    }
}