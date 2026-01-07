<?php

namespace App\Http\Controllers\Operaciones;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Operaciones\Guardias;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class GuardiasController extends Controller
{

     protected array $statusMap;

    public function __construct()
    {
        $this->statusMap = [
            1 => 'Activo',
            2 => 'Finalizado por usuario',
            3 => 'Finalizado por sistema',
        ];
    }
  public function index(Request $request)
{
    $search  = trim((string) $request->query('search', ''));
    $perPage = 10;

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

    $user = $request->user();

    $query = Guardias::query()->with('user');

    /**
     * ✅ RESTRICCIÓN POR ROLES (Área 1)
     * Administrador + Cloud Services Support ven todo; los demás solo sus guardias.
     */
    $canSeeAll = $user?->roles()
        ->whereIn('name', ['Administrador', 'Service Support Cloud Coordinator'])
        ->exists();

    if (! $canSeeAll) {
        $query->where('id_user', $user->id);
    }

    if ($search !== '') {
        $qRaw = $search;
        $q = mb_strtolower($search, 'UTF-8');

        // normaliza (quita acentos)
        $qNoAccents = Str::of($q)->ascii()->toString();

        $query->where(function ($w) use ($qRaw, $q, $qNoAccents, $months, $days) {

            // 1) ID exacto / status numérico si viene número
            if (ctype_digit($qNoAccents)) {
                $num = (int) $qNoAccents;
                $w->orWhere('id', $num)
                  ->orWhere('status', $num);
            }

            // 2) Usuario por nombre
            $w->orWhereHas('user', function ($sub) use ($qRaw) {
                $sub->where('name', 'like', "%{$qRaw}%");
            });

            // 3) Status por texto
            if (str_contains($qNoAccents, 'activo')) {
                $w->orWhere('status', 1);
            }
            if (str_contains($qNoAccents, 'usuario')) {
                $w->orWhere('status', 2);
            }
            if (str_contains($qNoAccents, 'sistema')) {
                $w->orWhere('status', 3);
            }
            if (str_contains($qNoAccents, 'final')) {
                $w->orWhereIn('status', [2, 3]);
            }

            /**
             * 4) Día de semana SOLO EN dateInit
             *    - Si viene "lunes 4" => exige lunes + día 4 en dateInit
             *    - Si viene solo "lunes" => filtra solo por lunes en dateInit
             */
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
                    $w->orWhereRaw('DAYOFWEEK(dateInit) = ? AND DAY(dateInit) = ?', [$weekdayValue, $dayOfMonth]);
                } else {
                    $w->orWhereRaw('DAYOFWEEK(dateInit) = ?', [$weekdayValue]);
                }
            }

            // 5) Fecha numérica dd-mm-yy(yy) o dd/mm/yy(yy) => SOLO dateInit
            if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2}|\d{4})$/', $qNoAccents, $m)) {
                $day  = str_pad($m[1], 2, '0', STR_PAD_LEFT);
                $mon  = str_pad($m[2], 2, '0', STR_PAD_LEFT);
                $yr   = $m[3];
                $year = strlen($yr) === 2 ? ('20' . $yr) : $yr;

                $date = "{$year}-{$mon}-{$day}";
                $w->orWhereDate('dateInit', $date);
            }

            // 6) "19 de enero del 2025" => SOLO dateInit
            if (preg_match('/\b(\d{1,2})\s+de\s+([a-záéíóú]+)\s+(?:del|de)\s+(\d{4})\b/u', $q, $m2)) {
                $day       = str_pad($m2[1], 2, '0', STR_PAD_LEFT);
                $monthName = Str::of($m2[2])->ascii()->toString();
                $year      = $m2[3];

                if (isset($months[$monthName])) {
                    $mon  = str_pad((string) $months[$monthName], 2, '0', STR_PAD_LEFT);
                    $date = "{$year}-{$mon}-{$day}";
                    $w->orWhereDate('dateInit', $date);
                }
            }

            // 7) "Enero del 2025" => SOLO dateInit
            if (preg_match('/\b([a-záéíóú]+)\s+(?:del|de)\s+(20\d{2})\b/u', $q, $m3)) {
                $monthName = Str::of($m3[1])->ascii()->toString();
                $year      = (int) $m3[2];

                if (isset($months[$monthName])) {
                    $mon = (int) $months[$monthName];
                    $w->orWhere(function ($qq) use ($mon, $year) {
                        $qq->whereMonth('dateInit', $mon)->whereYear('dateInit', $year);
                    });
                }
            }

            // 8) Mes únicamente ("enero") => SOLO dateInit
            foreach ($months as $monthName => $mon) {
                if (str_contains($qNoAccents, $monthName)) {
                    $w->orWhereMonth('dateInit', $mon);
                    break;
                }
            }

            // 9) Año únicamente ("2025") => SOLO dateInit
            if (preg_match('/^20\d{2}$/', $qNoAccents)) {
                $year = (int) $qNoAccents;
                $w->orWhereYear('dateInit', $year);
            }
        });
    }

    $guardias = $query
        ->orderByDesc('dateInit')
        ->paginate($perPage)
        ->withQueryString();

    return response()->json([
        'guardias' => $guardias,
        'statusMap' => $this->statusMap,
    ]);
}


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            abort(401, 'No autenticado.');
        }

        $guardia = Guardias::create([
            'id_user'  => $user->id,
            'dateInit' => Carbon::now(),
            'status'   => 1, // Activo SIEMPRE al crear
        ]);

        return response()->json([
            'message' => 'Guardia iniciada correctamente',
            'guardia' => $guardia->load('user'),
        ], 201);
    }

    public function active()
    {
        $user = Auth::user();

        if (! $user) abort(401, 'No autenticado.');

        $active = Guardias::where('id_user', $user->id)
            ->where('status', 1)   
            ->whereNull('dateFinish')     
            ->latest('dateInit')
            ->first();

        return response()->json([
            'hasActive' => (bool) $active,
            'guardia'   => $active?->load('user'),
        ]);
    }

    public function show(string $id)
    {
        
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
