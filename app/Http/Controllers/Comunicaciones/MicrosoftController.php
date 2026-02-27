<?php

namespace App\Http\Controllers\Comunicaciones;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\Comunicaciones\MicrosoftM;
use Illuminate\Support\Str;
class MicrosoftController extends Controller
{
    private array $servicio = [];
    private array $estado = [];
      public function __construct(){
        $this->servicio = [
            1 => 'Microsoft 365 Suite',
            2 => 'Microsoft OneDrive',
            3 => 'Microsoft Teams',
            4 => 'SharePoint Online',
            5 => 'Exchange Online',
            6 => 'Microsoft 365, Onedrive, Teams, SharePoint Online, Exchange Online',
        ];

        $this->estado = [
            1 => 'OK',
            2 => 'Advertencia',
            3 => 'Incidencia',
        ];
      }
   public function index(Request $request)
{
    $search  = trim((string) $request->query('search', ''));
    $state   = trim((string) $request->query('state', ''));    // 1/2/3
    $service = trim((string) $request->query('service', ''));  // 1..n
    $perPage = (int) $request->query('per_page', 20);
    if ($perPage <= 0 || $perPage > 200) {
        $perPage = 20;
    }

    $months = [
        'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4, 'mayo' => 5,
        'junio' => 6, 'julio' => 7, 'agosto' => 8, 'septiembre' => 9,
        'setiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12,
    ];

    $days = [
        'domingo' => 1, 'lunes' => 2, 'martes' => 3,
        'miércoles' => 4, 'miercoles' => 4,
        'jueves' => 5, 'viernes' => 6,
        'sábado' => 7, 'sabado' => 7,
    ];

    $query = MicrosoftM::query()
        ->with(['userCrea']); // relación del creador

    // ✅ FILTRO POR STATE (select)
    if ($state !== '' && ctype_digit($state)) {
        $st = (int) $state;
        if (in_array($st, [1, 2, 3], true)) {
            $query->where('state', $st);
        }
    }

    // ✅ FILTRO POR SERVICE (select)
    if ($service !== '' && ctype_digit($service)) {
        $sv = (int) $service;
        // valida contra el catálogo del controller para no filtrar basura
        if (array_key_exists($sv, $this->servicio)) {
            $query->where('serviceName', $sv);
        }
    }

    // ✅ SEARCH
    if ($search !== '') {
        $qRaw = $search;
        $q = mb_strtolower($search, 'UTF-8');
        $qNoAccents = Str::of($q)->ascii()->toString();

        $query->where(function ($w) use ($qRaw, $q, $qNoAccents, $months, $days) {

            // 1) NÚMEROS: id / state / serviceName
            if (ctype_digit($qNoAccents)) {
                $num = (int) $qNoAccents;

                $w->orWhere('id', $num)
                  ->orWhere('state', $num)
                  ->orWhere('serviceName', $num);
            }

            // 2) TEXTO: ejecución / descripción
            $w->orWhere('ejecution', 'like', "%{$qRaw}%")
              ->orWhere('description', 'like', "%{$qRaw}%");

            // 3) USUARIO CREADOR (nombre)
            $w->orWhereHas('userCrea', function ($sub) use ($qRaw) {
                $sub->where('name', 'like', "%{$qRaw}%");
            });

            // 4) ESTADO POR TEXTO
            if (str_contains($qNoAccents, 'ok')) {
                $w->orWhere('state', 1);
            }
            if (str_contains($qNoAccents, 'advertencia') || str_contains($qNoAccents, 'warning')) {
                $w->orWhere('state', 2);
            }
            if (str_contains($qNoAccents, 'incidencia') || str_contains($qNoAccents, 'error')) {
                $w->orWhere('state', 3);
            }

            // 5) FECHAS (revisionDate)
            // Día de semana
            $weekdayValue = null;
            foreach ($days as $dayName => $dayValue) {
                $dn = Str::of($dayName)->ascii()->toString();
                if (str_contains($qNoAccents, $dn)) {
                    $weekdayValue = $dayValue;
                    break;
                }
            }

            $dayOfMonth = null;
            if (preg_match('/\b([1-9]|[12]\d|3[01])\b/', $qNoAccents, $dm)) {
                $dayOfMonth = (int) $dm[1];
            }

            if ($weekdayValue !== null) {
                if ($dayOfMonth !== null) {
                    $w->orWhereRaw('DAYOFWEEK(revisionDate) = ? AND DAY(revisionDate) = ?', [$weekdayValue, $dayOfMonth]);
                } else {
                    $w->orWhereRaw('DAYOFWEEK(revisionDate) = ?', [$weekdayValue]);
                }
            }

            // dd/mm
            if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})$/', $qNoAccents, $mShort)) {
                $day = (int) $mShort[1];
                $mon = (int) $mShort[2];

                if ($day >= 1 && $day <= 31 && $mon >= 1 && $mon <= 12) {
                    $w->orWhere(function ($qq) use ($day, $mon) {
                        $qq->whereDay('revisionDate', $day)
                           ->whereMonth('revisionDate', $mon);
                    });
                }
            }

            // dd/mm/yyyy o dd-mm-yy
            if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2}|\d{4})$/', $qNoAccents, $m)) {
                $day  = str_pad($m[1], 2, '0', STR_PAD_LEFT);
                $mon  = str_pad($m[2], 2, '0', STR_PAD_LEFT);
                $yr   = $m[3];
                $year = strlen($yr) === 2 ? ('20' . $yr) : $yr;

                $date = "{$year}-{$mon}-{$day}";
                $w->orWhereDate('revisionDate', $date);
            }

            // "6 de febrero del 2026"
            if (preg_match('/\b(\d{1,2})\s+de\s+([a-záéíóú]+)\s+(?:del|de)\s+(\d{4})\b/u', $q, $m2)) {
                $day       = str_pad($m2[1], 2, '0', STR_PAD_LEFT);
                $monthName = Str::of($m2[2])->ascii()->toString();
                $year      = $m2[3];

                if (isset($months[$monthName])) {
                    $mon  = str_pad((string) $months[$monthName], 2, '0', STR_PAD_LEFT);
                    $date = "{$year}-{$mon}-{$day}";
                    $w->orWhereDate('revisionDate', $date);
                }
            }

            // "febrero 2026"
            if (preg_match('/\b([a-záéíóú]+)\s+(?:del|de)\s+(20\d{2})\b/u', $q, $m3)) {
                $monthName = Str::of($m3[1])->ascii()->toString();
                $year      = (int) $m3[2];

                if (isset($months[$monthName])) {
                    $mon = (int) $months[$monthName];
                    $w->orWhere(function ($qq) use ($mon, $year) {
                        $qq->whereMonth('revisionDate', $mon)
                           ->whereYear('revisionDate', $year);
                    });
                }
            }

            // "febrero" (sin año)
            foreach ($months as $monthName => $mon) {
                if (str_contains($qNoAccents, $monthName)) {
                    $w->orWhereMonth('revisionDate', $mon);
                    break;
                }
            }

            // "2026"
            if (preg_match('/^20\d{2}$/', $qNoAccents)) {
                $year = (int) $qNoAccents;
                $w->orWhereYear('revisionDate', $year);
            }
        });
    }

    $items = $query
        ->orderByDesc('revisionDate')
        ->orderByDesc('id')
        ->paginate($perPage)
        ->withQueryString();

    return response()->json([
        'monitoreos' => $items,
        'servicio'   => $this->servicio,
        'estado'     => $this->estado,
    ]);
}

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return response()->json([
            'success' => true,
            'servicio' => $this->servicio,
            'estado'   => $this->estado,
        ]);
    }

    public function store(Request $request)
    {
        $authUser = $request->user();
        $authUserId = (int) $authUser->id;

        // ✅ Se espera carga masiva: { monitoreos: [ ... ] }
        $data = $request->validate([
            'monitoreos' => ['required', 'array', 'min:1'],
            'monitoreos.*.serviceName' => [
                'required',
                'integer',
                Rule::in(array_keys($this->servicio)),
            ],
            'monitoreos.*.state' => [
                'required',
                'integer',
                Rule::in(array_keys($this->estado)),
            ],
            'monitoreos.*.revisionDate' => ['required', 'date'], // ideal 'Y-m-d'
            'monitoreos.*.ejecution' => ['required', 'string', 'max:255'],
            'monitoreos.*.description' => ['required', 'string', 'max:2000'],
        ]);

        $rows = collect($data['monitoreos'])->map(function ($m) use ($authUserId) {
            return [
                // Guardas IDs (recomendado porque tu model fillable usa serviceName/state como campo)
                // Si prefieres guardar texto, abajo te dejo la alternativa.
                'serviceName'  => (int) $m['serviceName'],
                'state'        => (int) $m['state'],
                'revisionDate' => $m['revisionDate'],
                'ejecution'    => $m['ejecution'],
                'description'  => $m['description'],

                // ✅ usuario creador lo pone el backend
                'id_user'      => $authUserId,

                // si tienes timestamps
                'created_at'   => now(),
                'updated_at'   => now(),
            ];
        })->values()->all();

        DB::transaction(function () use ($rows) {
            // ✅ Inserción masiva (rápida)
            MicrosoftM::insert($rows);
        });

        return response()->json([
            'count' => count($rows),
            'message' => 'Monitoreos Microsoft creados correctamente.',
        ], 201);
    }
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
