<?php

namespace App\Http\Controllers\Operaciones;

use App\Http\Controllers\Controller;
use App\Models\Operaciones\Tickets;
use App\Models\Operaciones\Monitoreos;
use Illuminate\Support\Facades\Validator;
use App\Models\Operaciones\ClienteVeeam;
use Illuminate\Http\Request;
use App\Models\Operaciones\Guardias;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

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




    public function editContext(Request $request, int $id)
{
    $authUser = $request->user();
    if (! $authUser) {
        abort(401, 'No autenticado.');
    }

    $isAdmin = method_exists($authUser, 'isAdmin')
        ? (bool) $authUser->isAdmin()
        : $authUser->roles()->where('name', 'Administrador')->exists();

    $guardia = Guardias::with('user:id,name,email')
        ->where('id', $id)
        ->where('status', 1)
        ->whereNull('dateFinish')
        ->first();

    if (! $guardia) {
        return response()->json([
            'success' => false,
            'message' => 'No hay una guardia activa para editar.',
        ], 404);
    }

    $isOwner = (int) $guardia->id_user === (int) $authUser->id;

    if (! $isAdmin && ! $isOwner) {
        return response()->json([
            'success' => false,
            'message' => 'No tienes permisos para editar esta guardia.',
        ], 403);
    }

    $tickets = Tickets::query()
        ->select([
            'id',
            'numTicket',
            'numTicketNoct',
            'user_create_ticket',
            'assigned_user_id',
            'titleTicket',
            'descriptionTicket',
            'status',
            'id_guardia',
            'created_at',
            'updated_at',
        ])
        ->with([
            'creator:id,name,email',
            'assignedUser:id,name,email',
            'guardia:id,id_user,dateInit,dateFinish,status',
        ])
        ->where('status', 1)
        ->orderByDesc('updated_at')
        ->orderByDesc('created_at')
        ->get();

    return response()->json([
        'success' => true,
        'guardia' => $guardia,
        'tickets' => $tickets,
        'statusMap' => $this->statusMap,
        'auth' => [
            'id' => (int) $authUser->id,
            'name' => $authUser->name,
            'is_admin' => $isAdmin,
            'is_owner' => $isOwner,
        ],
    ]);
}

public function closeData(Request $request)
{
    $authUser = $request->user();
    if (! $authUser) {
        abort(401, 'No autenticado.');
    }

    $isAdmin = method_exists($authUser, 'isAdmin')
        ? (bool) $authUser->isAdmin()
        : $authUser->roles()->where('name', 'Administrador')->exists();

    $data = $request->validate([
        'guardia_id' => ['required', 'integer', Rule::exists('info_guard', 'id')],
    ]);

    $guardia = Guardias::with('user:id,name,email')
        ->where('id', (int) $data['guardia_id'])
        ->where('status', 1)
        ->whereNull('dateFinish')
        ->first();

    if (! $guardia) {
        return response()->json([
            'hasActive' => false,
            'guardia'   => null,
            'tickets'   => [],
            'statusMap' => $this->statusMap,
            'message'   => 'No hay guardia activa para mostrar.',
        ], 200);
    }

    // ✅ Si no es admin, solo puede consultar su propia guardia
    if (! $isAdmin && (int) $guardia->id_user !== (int) $authUser->id) {
        return response()->json([
            'message' => 'No puedes consultar una guardia que no te pertenece.',
        ], 403);
    }

    // ✅ TODOS los tickets abiertos del sistema
    $tickets = Tickets::query()
        ->select([
            'id',
            'numTicket',
            'numTicketNoct',
            'user_create_ticket',
            'assigned_user_id',
            'titleTicket',
            'descriptionTicket',
            'status',
            'id_guardia',
            'created_at',
            'updated_at',
        ])
        ->with([
            'creator:id,name,email',
            'assignedUser:id,name,email',
            'guardia:id,id_user,dateInit,dateFinish,status',
        ])
        ->where('status', 1)
        ->orderByDesc('updated_at')
        ->orderByDesc('created_at')
        ->get();

    return response()->json([
        'hasActive' => true,
        'guardia'   => $guardia,
        'tickets'   => $tickets,
        'statusMap' => $this->statusMap,
    ]);
}
public function closeFinal(Request $request)
{
    $traceId = (string) Str::uuid();

    $authUser = $request->user();
    if (! $authUser) {
        abort(401, 'No autenticado.');
    }

    $authUserId = (int) $authUser->id;

    $isAdmin = method_exists($authUser, 'isAdmin')
        ? (bool) $authUser->isAdmin()
        : $authUser->roles()->where('name', 'Administrador')->exists();

    $data = $request->validate([
        'guardia_id' => ['required', 'integer', Rule::exists('info_guard', 'id')],
    ]);

    $guardiaId = (int) $data['guardia_id'];

    $guardia = Guardias::query()
        ->where('id', $guardiaId)
        ->where('status', 1)
        ->whereNull('dateFinish')
        ->first();

    if (! $guardia) {
        return response()->json([
            'trace_id' => $traceId,
            'message' => 'No hay guardia activa para cerrar.',
            'closed' => false,
        ], 200);
    }

    // ✅ Si no es admin, solo puede cerrar su propia guardia
    if (! $isAdmin && (int) $guardia->id_user !== $authUserId) {
        return response()->json([
            'trace_id' => $traceId,
            'message' => 'No puedes cerrar una guardia que no fue iniciada por ti.',
            'closed' => false,
        ], 403);
    }

    DB::transaction(function () use ($guardiaId, $traceId, $authUserId, $isAdmin) {
        $g = Guardias::query()
            ->where('id', $guardiaId)
            ->lockForUpdate()
            ->firstOrFail();

        if ($g->dateFinish === null) {
            $g->dateFinish = now();
            $g->status = 2;
            $g->save();
        }

        Log::info('[closeFinal] Guardia closed', [
            'trace_id' => $traceId,
            'guardia_id' => (int) $g->id,
            'guardia_owner_user_id' => (int) $g->id_user,
            'closed_by_user_id' => $authUserId,
            'closed_by_is_admin' => $isAdmin,
            'dateFinish' => (string) $g->dateFinish,
        ]);
    });

    return response()->json([
        'trace_id' => $traceId,
        'message' => 'Guardia cerrada correctamente.',
        'closed' => true,
        'guardia_id' => (int) $guardia->id,
        'guardia_owner_user_id' => (int) $guardia->id_user,
    ], 200);
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

    public function storeTicketFromGuardia(Request $request, int $id)
{
    $authUser = $request->user();
    if (! $authUser) {
        abort(401, 'No autenticado.');
    }

    $authUserId = (int) $authUser->id;

    $isAdmin = method_exists($authUser, 'isAdmin')
        ? (bool) $authUser->isAdmin()
        : $authUser->roles()->where('name', 'Administrador')->exists();

    $guardia = Guardias::query()
        ->with('user:id,name,email')
        ->where('id', $id)
        ->where('status', 1)
        ->whereNull('dateFinish')
        ->first();

    if (! $guardia) {
        return response()->json([
            'success' => false,
            'message' => 'No hay una guardia activa válida para crear el ticket.',
        ], 404);
    }

    $isOwner = (int) $guardia->id_user === $authUserId;

    if (! $isAdmin && ! $isOwner) {
        return response()->json([
            'success' => false,
            'message' => 'No tienes permisos para crear tickets en esta guardia.',
        ], 403);
    }

    $data = $request->validate([
        'numTicket' => [
            'required',
            'integer',
            Rule::unique('tickets', 'numTicket'),
        ],
        'numTicketNoct' => ['nullable', 'integer'],
        'titleTicket' => ['required', 'string', 'max:100'],
        'descriptionTicket' => ['required', 'string', 'max:2000'],
    ]);

    $guardiaOwnerUserId = (int) $guardia->id_user;

    $ticket = Tickets::create([
        'numTicket'          => (int) $data['numTicket'],
        'numTicketNoct'      => $data['numTicketNoct'] !== null ? (int) $data['numTicketNoct'] : null,
        'user_create_ticket' => $guardiaOwnerUserId,
        'assigned_user_id'   => $guardiaOwnerUserId,
        'titleTicket'        => $data['titleTicket'],
        'descriptionTicket'  => $data['descriptionTicket'],
        'status'             => 1,
        'id_guardia'         => (int) $guardia->id,
    ]);

    $ticket->load([
        'creator:id,name,email',
        'assignedUser:id,name,email',
        'guardia:id,id_user,dateInit,dateFinish,status',
    ]);

    return response()->json([
        'success' => true,
        'ticket' => $ticket,
        'guardia_id' => (int) $guardia->id,
        'guardia_owner_user_id' => $guardiaOwnerUserId,
        'message' => 'Ticket creado correctamente dentro de la guardia.',
    ], 201);
}


public function storeMonitoreosFromGuardia(Request $request, int $id)
{
    $authUser = $request->user();
    if (! $authUser) {
        return response()->json([
            'message' => 'No autenticado. Envía tu token.',
            'code'    => 'UNAUTHENTICATED',
        ], 401);
    }

    $authUserId = (int) $authUser->id;

    $isAdmin = method_exists($authUser, 'isAdmin')
        ? (bool) $authUser->isAdmin()
        : $authUser->roles()->where('name', 'Administrador')->exists();

    $guardia = Guardias::query()
        ->where('id', $id)
        ->where('status', 1)
        ->whereNull('dateFinish')
        ->first();

    if (! $guardia) {
        return response()->json([
            'message' => 'No se encontró una guardia activa válida.',
        ], 404);
    }

    $guardiaOwnerId = (int) $guardia->id_user;
    $isOwner = $guardiaOwnerId === $authUserId;

    if (! $isAdmin && ! $isOwner) {
        return response()->json([
            'message' => 'No tienes permisos para registrar monitoreos en esta guardia.',
        ], 403);
    }

    $validator = Validator::make($request->all(), [
        'site' => ['required', 'string', Rule::in(['veeam', 'site24', 'sophos'])],
        'rows' => ['required', 'array', 'min:1'],

        'rows.*.client_id'   => ['required', 'integer', 'min:1'],
        'rows.*.siteApp'     => ['nullable', 'integer', 'min:1'],
        'rows.*.estatus'     => ['required', 'string'],
        'rows.*.observacion' => ['nullable', 'string', 'max:5000'],
        'rows.*.dateRest'    => ['nullable', 'date_format:Y-m-d'],
    ], [
        'rows.required' => 'No se recibieron registros.',
        'rows.*.dateRest.date_format' => 'dateRest debe venir en formato YYYY-MM-DD.',
    ]);

    $validator->after(function ($v) use ($request) {
        $site = (string) $request->input('site', '');
        $rows = (array) $request->input('rows', []);

        if ($site === 'veeam') {
            $veeamAppIds = DB::table('app_service')
                ->where('nameService', 'like', '%Veeam%')
                ->pluck('id')
                ->map(fn ($x) => (int) $x)
                ->all();

            $clientIds = collect($rows)
                ->pluck('client_id')
                ->filter()
                ->map(fn ($x) => (int) $x)
                ->unique()
                ->values()
                ->all();

            $appsByClient = ClienteVeeam::query()
                ->whereIn('id', $clientIds)
                ->pluck('app', 'id')
                ->map(fn ($x) => (int) $x)
                ->all();

            foreach ($rows as $i => $row) {
                $clientId = (int) ($row['client_id'] ?? 0);
                $estatus  = (string) ($row['estatus'] ?? '');

                $realApp = (int) ($appsByClient[$clientId] ?? 0);

                if (! $realApp) {
                    $v->errors()->add("rows.$i.client_id", 'Cliente Veeam no encontrado o sin app asignada.');
                    continue;
                }

                if (! in_array($realApp, $veeamAppIds, true)) {
                    $v->errors()->add("rows.$i.client_id", 'El cliente tiene un app no válido para Veeam.');
                }

                if (! in_array($estatus, ['1','2','3','4','5','6'], true)) {
                    $v->errors()->add("rows.$i.estatus", 'Estatus inválido para Veeam (solo 1..6).');
                }

                if (! empty($row['siteApp'])) {
                    $sent = (int) $row['siteApp'];
                    if ($sent !== $realApp) {
                        $v->errors()->add("rows.$i.siteApp", 'siteApp enviado no coincide con el app real del cliente.');
                    }
                }
            }

            return;
        }

        foreach ($rows as $i => $row) {
            $siteApp = (int) ($row['siteApp'] ?? 0);
            if ($siteApp <= 0) {
                $v->errors()->add("rows.$i.siteApp", 'siteApp es obligatorio para este site.');
            }
        }
    });

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validación fallida.',
            'errors'  => $validator->errors(),
        ], 422);
    }

    $site = (string) $request->input('site');
    $rows = (array) $request->input('rows');

    try {
        DB::transaction(function () use ($rows, $site, $guardia, $guardiaOwnerId) {
            $now = now();
            $insert = [];

            $appsByClient = [];
            if ($site === 'veeam') {
                $clientIds = collect($rows)
                    ->pluck('client_id')
                    ->filter()
                    ->map(fn ($x) => (int) $x)
                    ->unique()
                    ->values()
                    ->all();

                $appsByClient = ClienteVeeam::query()
                    ->whereIn('id', $clientIds)
                    ->pluck('app', 'id')
                    ->map(fn ($x) => (int) $x)
                    ->all();
            }

            foreach ($rows as $r) {
                $estatus = (string) ($r['estatus'] ?? '');
                $clientId = (int) ($r['client_id'] ?? 0);

                $concluido = in_array($estatus, ['1', '2'], true) ? 2 : 1;

                $siteApp = $site === 'veeam'
                    ? (int) ($appsByClient[$clientId] ?? 0)
                    : (int) ($r['siteApp'] ?? 0);

                $insert[] = [
                    'siteApp'     => $siteApp,
                    'client_id'   => $clientId,
                    'dateRest'    => $r['dateRest'] ?? null,
                    'estatus'     => $estatus,
                    'observacion' => $r['observacion'] ?? null,
                    'concluido'   => $concluido,
                    'id_guard'    => (int) $guardia->id,
                    'user_Cre'    => $guardiaOwnerId,
                    'user_Upd'    => $guardiaOwnerId,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }

            Monitoreos::insert($insert);
        });

        return response()->json([
            'message'    => 'Monitoreos guardados correctamente en la guardia.',
            'count'      => count($rows),
            'guardia_id' => (int) $guardia->id,
            'user_id'    => $guardiaOwnerId,
        ], 201);

    } catch (Throwable $e) {
        Log::error('storeMonitoreosFromGuardia failed', [
            'error'      => $e->getMessage(),
            'file'       => $e->getFile(),
            'line'       => $e->getLine(),
            'rows_count' => count($rows),
            'auth_user'  => $authUserId,
            'guardia_id' => (int) $guardia->id,
            'guardia_user_id' => $guardiaOwnerId,
            'site'       => $site,
        ]);

        return response()->json([
            'message' => 'Error al guardar monitoreos de la guardia.',
            'code'    => 'SERVER_ERROR',
            'debug'   => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }
}
}
