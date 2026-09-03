<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\Operaciones\Guardias;

class NotifyActiveGuardia extends Command
{
    private const DESTINATARIO = 'operationsstratosphere@stratospherecorp.com';

    protected $signature = 'guardias:notify-active';

    protected $description = 'Envía recordatorios de guardias que continúan activas.';

    public function handle(): int
    {
        $hora = now()->format('H:i');

        $recordatorios = [
            '11:00' => [
                'titulo' => 'Primer recordatorio del día.',
                'subject' => 'Recordatorio - Guardias activas 11:00 AM',
            ],
            '13:00' => [
                'titulo' => 'Segundo recordatorio del día. Las siguientes guardias continúan activas a la 01:00 PM.',
                'subject' => 'Segundo recordatorio - Guardias activas 01:00 PM',
            ],
            '16:00' => [
                'titulo' => 'Tercer recordatorio del día. Las siguientes guardias continúan activas a las 04:00 PM.',
                'subject' => 'Tercer recordatorio - Guardias activas 04:00 PM',
            ],
        ];

        // Solo ejecuta recordatorios en los horarios establecidos.
        if (!isset($recordatorios[$hora])) {
            $this->info('Fuera de horario permitido: 11:00, 13:00 o 16:00.');
            return Command::SUCCESS;
        }

        $guardias = Guardias::with('user:id,name,email')
            ->whereNull('dateFinish')
            ->where('status', 1)
            ->orderBy('dateInit')
            ->get();

        if ($guardias->isEmpty()) {
            $this->info("{$hora}: No hay guardias activas.");

            Log::info('guardias:active-reminder no-active', [
                'schedule' => $hora,
                'now' => now()->toDateTimeString(),
            ]);

            return Command::SUCCESS;
        }

        $config = $recordatorios[$hora];

        $mensaje = "RECORDATORIO DE GUARDIAS ACTIVAS\n\n";
        $mensaje .= "{$config['titulo']}\n\n";
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

        Mail::raw($mensaje, function ($message) use ($config) {
            $message
                ->to(self::DESTINATARIO)
                ->subject($config['subject']);
        });

        $this->info("{$hora}: Recordatorio enviado. Guardias activas: {$guardias->count()}.");

        Log::info('guardias:active-reminder sent', [
            'schedule' => $hora,
            'to' => self::DESTINATARIO,
            'active_guardias' => $guardias->count(),
            'guardias' => $guardias->pluck('id')->toArray(),
            'now' => now()->toDateTimeString(),
        ]);

        return Command::SUCCESS;
    }
}