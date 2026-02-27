<?php

namespace App\Http\Controllers\Comunicaciones;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

use App\Models\Comunicaciones\Sucursales;
use App\Models\Comunicaciones\MonitAA;

class MonitAAController extends Controller
{
   
    public function __construct()
    {
        $this->stateRed = [
            1 => 'ONLINE',
            2 => 'UPDATING/SYNCHRONIZING',
            3 => 'RESTARTING',
            4 => 'OFFLINE',
        ];

        $this->stateMonit = [
            1 => 'ACTIVO',
            2 => 'ANULADO',
            3 => 'CONCLUIDO',
        ];

        $this->sucursales = [
            '1' => 'VALLE',
            '2' => 'GDL',
            '3' => 'MTY',
            '4' => 'MER'
        ];
    }
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    
    
   public function create()
{
    $plataforma = Sucursales::whereIn('plat', [1, 2])
        ->get()
        ->map(function ($s) {
            $platLabel = match ((int) $s->plat) {
                1 => 'Aruba',
                2 => 'Alestra',
                default => 'Desconocido',
            };

            // ✅ nameS -> sucursal label desde tu catálogo
            $sucursalLabel = $this->sucursales[(string) $s->nameS] ?? null;

            return array_merge($s->toArray(), [
                'plat_label'     => $platLabel,
                'sucursal_label' => $sucursalLabel, // ej: "VALLE", "GDL", etc.
            ]);
        });

    return response()->json([
        'success' => true,
        'data' => [
            'plataforma'  => $plataforma,
            'stateRed'    => $this->stateRed,
            'stateMonit'  => $this->stateMonit,
            'sucursales'  => $this->sucursales, // ✅ catálogo completo
        ]
    ], 200);
}
    /**
     * Store a newly created resource in storage.
     */
  public function store(Request $request)
{
    $authUser = $request->user();
    $authUserId = (int) $authUser->id;

    $data = $request->validate([
        'monitoreos' => ['required', 'array', 'min:1'],

        'monitoreos.*.sucursal_id' => [
            'required',
            'integer',
            Rule::exists('sucursales', 'id'),
        ],

        'monitoreos.*.dateRed' => ['required', 'date'],

        'monitoreos.*.statusRed' => [
            'required',
            'integer',
            Rule::in(array_keys($this->stateRed)),
        ],

        // ✅ opcionales (si vienen, deben tener formato)
        'monitoreos.*.time_down' => ['nullable', 'date_format:H:i'],
        'monitoreos.*.time_up'   => ['nullable', 'date_format:H:i'],

        // ✅ afectación opcional o requerida según statusRed (lo validamos abajo)
        'monitoreos.*.affectation' => ['nullable', 'string', 'max:255'],

        // ✅ motivo siempre requerido
        'monitoreos.*.reason' => ['required', 'string', 'max:2000'],

        // ✅ notas siempre opcional
        'monitoreos.*.note' => ['nullable', 'string', 'max:2000'],

        // ✅ lo puede mandar el front, pero NO lo vamos a confiar (se fuerza por backend)
        'monitoreos.*.statusMonit' => ['nullable', 'integer'],
    ]);

    // ✅ Reglas condicionales:
    // - Si statusRed != 1 (NO ONLINE): time_down y affectation obligatorios
    foreach ($data['monitoreos'] as $i => $m) {
        $statusRed = (int) ($m['statusRed'] ?? 0);

        if ($statusRed !== 1) {
            $request->validate([
                "monitoreos.$i.time_down"   => ['required', 'date_format:H:i'],
                "monitoreos.$i.affectation" => ['required', 'string', 'max:255'],
            ]);
        }
    }

    $rows = collect($data['monitoreos'])->map(function ($m) use ($authUserId) {
        $statusRed = (int) $m['statusRed'];

        // ✅ regla solicitada:
        // statusRed = 1 (ONLINE) => statusMonit = 3 (CONCLUIDO)
        // diferente de 1        => statusMonit = 1 (ACTIVO)
        $statusMonit = $statusRed === 1 ? 3 : 1;

        return [
            'sucursal_id' => (int) $m['sucursal_id'],

            'dateRed'     => $m['dateRed'],
            'statusRed'   => $statusRed,

            // opcionales
            'time_down'   => $m['time_down'] ?? null,
            'time_up'     => $m['time_up'] ?? null,

            // afectación opcional o requerida según statusRed (ya validado arriba)
            'affectation' => isset($m['affectation']) ? (string) $m['affectation'] : null,

            // siempre requerido
            'reason'      => (string) $m['reason'],

            // opcional
            'note'        => $m['note'] ?? null,

            // ✅ forzado por backend
            'statusMonit' => $statusMonit,

            // usuarios backend
            'user_create' => $authUserId,
            'user_update' => $authUserId,

            'created_at'  => now(),
            'updated_at'  => now(),
        ];
    })->values()->all();

    DB::transaction(function () use ($rows) {
        MonitAA::insert($rows);
    });

    return response()->json([
        'count' => count($rows),
        'message' => 'Monitoreos Redes creados correctamente.',
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
