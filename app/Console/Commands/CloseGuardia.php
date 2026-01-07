<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\Operaciones\Guardias;
use App\Mail\GuardiaAutoClosedMail;

class CloseGuardia extends Command
{
    // ✅ por defecto 27 horas
    protected $signature = 'guardias:close-expired {--hours=27} {--minutes=}';
    protected $description = 'Cierra guardias activas con más de X horas (o minutos) y envía email (cierre por sistema).';

    public function handle(): int
    {
        $hours   = $this->option('hours');
        $minutes = $this->option('minutes');

        // ✅ prioridad a minutes si lo pasas (útil para pruebas)
        if (!is_null($minutes) && $minutes !== '') {
            $cutoff = Carbon::now()->subMinutes((int) $minutes);
            $mode = 'minutes';
            $value = (int) $minutes;
        } else {
            $cutoff = Carbon::now()->subHours((int) $hours);
            $mode = 'hours';
            $value = (int) $hours;
        }

        $closedCount = 0;

        $candidates = Guardias::with('user:id,name,email')
            ->whereNull('dateFinish')
            ->where('status', 1)
            ->where('dateInit', '<=', $cutoff)
            ->orderBy('dateInit')
            ->get();

        foreach ($candidates as $g) {
            DB::transaction(function () use ($g, &$closedCount) {
                $fresh = Guardias::whereKey($g->id)->lockForUpdate()->first();

                if (! $fresh) return;
                if ((int)$fresh->status !== 1) return;
                if (! is_null($fresh->dateFinish)) return;

                $fresh->dateFinish = now();
                $fresh->status = 3; // ✅ cerrado por sistema
                $fresh->save();

                $closedCount++;

                $fresh->load('user:id,name,email');

                // ✅ destino fijo (como pediste)
                $to = 'avillavicencio@teamnet.com.mx';
                Mail::to($to)->send(new GuardiaAutoClosedMail($fresh));
            });
        }

        $this->info("✅ Guardias cerradas por sistema: {$closedCount}");
        Log::info('guardias:close-expired done', [
            'mode'   => $mode,
            'value'  => $value,
            'cutoff' => $cutoff->toDateTimeString(),
            'closed' => $closedCount,
        ]);

        return Command::SUCCESS;
    }
}