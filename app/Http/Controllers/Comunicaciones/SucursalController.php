<?php

namespace App\Http\Controllers\Comunicaciones;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Comunicaciones\Sucursales;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;


class SucursalController extends Controller
{

    private array $sucursales = [];
    private array $plataforma = [];

    public function __construct(){
        $this->plataforma = [
            '1' => 'Aruba',
            '2' => 'Alestra',
            '3' => 'Respaldos',
        ];

         $this->sucursales = [
            '1' => 'VALLE',
            '2' => 'GDL',
            '3' => 'MTY',
            '4' => 'MER'
        ];
    }
    public function index(Request $request): JsonResponse
{
    $search  = trim(mb_strtolower((string) $request->query('search', '')));
    $perPage = 15;

    $query = Sucursales::query();

    if ($search !== '') {
        // Mapas invertidos para resolver texto => id
        $platByName = array_change_key_case(array_flip($this->plataforma), CASE_LOWER); // 'aruba' => '1'
        $sucByName  = array_change_key_case(array_flip($this->sucursales), CASE_LOWER); // 'valle' => '1'

        // Normaliza: quita espacios dobles
        $searchNorm = preg_replace('/\s+/', ' ', $search);

        // Detectar si la búsqueda contiene palabras clave (ej. "aruba", "valle")
        $tokens = array_values(array_filter(explode(' ', $searchNorm)));

        $platIds = [];
        $sucIds  = [];

        foreach ($tokens as $t) {
            if (isset($platByName[$t])) $platIds[] = (int) $platByName[$t];
            if (isset($sucByName[$t]))  $sucIds[]  = (int) $sucByName[$t];
        }

        $platIds = array_values(array_unique($platIds));
        $sucIds  = array_values(array_unique($sucIds));

        $query->where(function ($q) use ($searchNorm, $platIds, $sucIds) {

            // Buscar por nombre de sucursal (texto) -> convierte a nameS (1-4)
            if (!empty($sucIds)) {
                $q->orWhereIn('nameS', $sucIds);
            }

            // Buscar por nombre de plataforma (texto) -> convierte a plat (1-3)
            if (!empty($platIds)) {
                $q->orWhereIn('plat', $platIds);
            }

            // Buscar por host / ip / keys (texto)
            $q->orWhere('servHost', 'like', "%{$searchNorm}%")
              ->orWhere('ip', 'like', "%{$searchNorm}%")
              ->orWhere('keys', 'like', "%{$searchNorm}%");
        });
    }

    $sucursales = $query
        ->orderByDesc('id')
        ->paginate($perPage);

    return response()->json($sucursales);
}


     public function store(Request $request): JsonResponse
    {
        // Normaliza (por si mandan strings)
        $plat  = (int) $request->input('plat');
        $nameS  = (int) $request->input('nameS');
        $servHost = trim((string) $request->input('servHost', ''));

        $keys = $request->input('keys', null);
        $ip   = $request->input('ip', null);

        $keys = $keys !== null ? mb_strtolower(trim((string) $keys)) : null;
        $ip   = $ip   !== null ? trim((string) $ip) : null;

        // ✅ Validación base + condicional por plataforma
        $validated = $request->validate([
            'nameS'  => ['required', 'integer', Rule::in([1, 2, 3, 4])],
            'servHost' => ['required', 'string', 'max:255'],
            'plat'  => ['required', 'integer', Rule::in([1, 2, 3])],

            // plat 1 y 3 => ip obligatorio
            'ip' => [
                Rule::requiredIf(fn () => in_array((int) $request->input('plat'), [1, 3], true)),
                'nullable',
                'ipv4',
            ],

            // plat 2 => keys obligatorio (1..20)
            'keys' => [
                Rule::requiredIf(fn () => (int) $request->input('plat') === 2),
                'nullable',
                'string',
                'min:1',
                'max:20',
            ],
        ], [
            'nameS.required'  => 'La plataforma es obligatoria.',
            'nameS.in'        => 'La plataforma seleccionada no es válida.',

            'plat.required'  => 'La plataforma es obligatoria.',
            'plat.in'        => 'La plataforma seleccionada no es válida.',

            'servHost.required' => 'El nombre del host es obligatorio.',

            'ip.required'    => 'La IP es obligatoria para Aruba y Respaldos.',
            'ip.ipv4'        => 'La IP debe ser válida (IPv4).',

            'keys.required'  => 'La llave es obligatoria para Alestra.',
            'keys.max'       => 'La llave no puede exceder 20 caracteres.',
        ]);

        // ✅ Aplicar normalizados ya listos
        $validated['nameS'] = $nameS;
         $validated['servHost'] = $servHost;
        $validated['plat']  = $plat;

        // keys siempre en minúsculas
        $validated['keys'] = $keys;
        $validated['ip']   = $ip;

        // ✅ Limpia el campo que no aplica según plataforma
        if (in_array($plat, [1, 3], true)) {
            $validated['keys'] = null;
        }
        if ($plat === 2) {
            $validated['ip'] = null;
        }

        // ✅ (Opcional) Evitar duplicado por nombre + plataforma
        $exists = Sucursales::query()
            ->where('nameS', $validated['nameS'])
            ->where('plat', $validated['plat'])
            ->where('servHost', $validated['servHost'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Ya existe una sucursal con el mismo nombre en esta plataforma.',
                'code'    => 'DUPLICATE',
            ], 409);
        }

        $sucursal = Sucursales::create([
            'nameS' => $validated['nameS'],
            'plat'  => $validated['plat'],
            'servHost' => $validated['servHost'],
            'keys'  => $validated['keys'],
            'ip'    => $validated['ip'],
        ]);

        return response()->json([
            'message' => 'Sucursal creada correctamente.',
            'data'    => $sucursal,
        ], 201);
    }

    public function edit(string $id): JsonResponse
{
    $sucursal = Sucursales::query()->find($id);

    if (!$sucursal) {
        return response()->json([
            'message' => 'Sucursal no encontrada.',
        ], 404);
    }

    return response()->json([
        'data' => [
            'id'       => $sucursal->id,
            'plat'     => (int) $sucursal->plat,
            'nameS'    => (int) $sucursal->nameS,
            'servHost' => (string) $sucursal->servHost,
            'ip'       => $sucursal->ip,
            'keys'     => $sucursal->keys,

            // (Opcional) Etiquetas para mostrar
            'plat_label'  => $this->plataforma[(string) $sucursal->plat] ?? null,
            'nameS_label' => $this->sucursales[(string) $sucursal->nameS] ?? null,
        ],

        // Catálogos para selects
        'catalogs' => [
            'plataforma' => $this->plataforma, // 1=>Aruba, 2=>Alestra, 3=>Respaldos
            'sucursales' => $this->sucursales, // 1=>VALLE, 2=>GDL, 3=>MTY, 4=>MER
        ],
    ], 200);
}

    /**
     * Update the specified resource in storage.
     */
   public function update(Request $request, string $id): JsonResponse
{
    $sucursal = Sucursales::query()->find($id);

    if (!$sucursal) {
        return response()->json([
            'message' => 'Sucursal no encontrada.',
        ], 404);
    }

    // Normaliza (por si mandan strings)
    $plat     = (int) $request->input('plat');
    $nameS    = (int) $request->input('nameS');
    $servHost = trim((string) $request->input('servHost', ''));

    $keys = $request->input('keys', null);
    $ip   = $request->input('ip', null);

    $keys = $keys !== null ? mb_strtolower(trim((string) $keys)) : null;
    $ip   = $ip   !== null ? trim((string) $ip) : null;

    // ✅ Validación base + condicional por plataforma
    $validated = $request->validate([
        'nameS'    => ['required', 'integer', Rule::in([1, 2, 3, 4])],
        'servHost' => ['required', 'string', 'max:255'],
        'plat'     => ['required', 'integer', Rule::in([1, 2, 3])],

        // plat 1 y 3 => ip obligatorio
        'ip' => [
            Rule::requiredIf(fn () => in_array((int) $request->input('plat'), [1, 3], true)),
            'nullable',
            'ipv4',
        ],

        // plat 2 => keys obligatorio (1..20)
        'keys' => [
            Rule::requiredIf(fn () => (int) $request->input('plat') === 2),
            'nullable',
            'string',
            'min:1',
            'max:20',
        ],
    ], [
        'nameS.required'  => 'La sucursal es obligatoria.',
        'nameS.in'        => 'La sucursal seleccionada no es válida.',

        'plat.required'   => 'La plataforma es obligatoria.',
        'plat.in'         => 'La plataforma seleccionada no es válida.',

        'servHost.required' => 'El nombre del host es obligatorio.',

        'ip.required'     => 'La IP es obligatoria para Aruba y Respaldos.',
        'ip.ipv4'         => 'La IP debe ser válida (IPv4).',

        'keys.required'   => 'La llave es obligatoria para Alestra.',
        'keys.max'        => 'La llave no puede exceder 20 caracteres.',
    ]);

    // ✅ Aplicar normalizados ya listos
    $validated['nameS']    = $nameS;
    $validated['servHost'] = $servHost;
    $validated['plat']     = $plat;

    // keys siempre en minúsculas
    $validated['keys'] = $keys;
    $validated['ip']   = $ip;

    // ✅ Limpia el campo que no aplica según plataforma
    if (in_array($plat, [1, 3], true)) {
        $validated['keys'] = null;
    }
    if ($plat === 2) {
        $validated['ip'] = null;
    }

    // ✅ Evitar duplicado por sucursal + plataforma + host, ignorando el registro actual
    $exists = Sucursales::query()
        ->where('id', '!=', $sucursal->id)
        ->where('nameS', $validated['nameS'])
        ->where('plat', $validated['plat'])
        ->where('servHost', $validated['servHost'])
        ->exists();

    if ($exists) {
        return response()->json([
            'message' => 'Ya existe una sucursal con el mismo nombre de host en esta plataforma y sucursal.',
            'code'    => 'DUPLICATE',
        ], 409);
    }

    $sucursal->update([
        'nameS'    => $validated['nameS'],
        'plat'     => $validated['plat'],
        'servHost' => $validated['servHost'],
        'keys'     => $validated['keys'],
        'ip'       => $validated['ip'],
    ]);

    return response()->json([
        'message' => 'Sucursal actualizada correctamente.',
        'data'    => $sucursal->fresh(),
    ], 200);
}


    public function show(string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $sucursal = Sucursales::query()->find($id);

        if (!$sucursal) {
            return response()->json([
                'message' => 'Sucursal no encontrada.',
            ], 404);
        }

        DB::beginTransaction();

        try {
            $sucursal->delete();

            DB::commit();

            return response()->json([
                'message' => 'Sucursal eliminada correctamente.',
                'data' => [
                    'id' => $sucursal->id,
                ],
            ], 200);

        } catch (QueryException $e) {
            DB::rollBack();

            // MySQL: 1451 = Cannot delete or update a parent row (tiene referencias)
            $sqlState = $e->errorInfo[0] ?? null;
            $sqlCode  = $e->errorInfo[1] ?? null;

            if ((string) $sqlCode === '1451') {
                return response()->json([
                    'message' => 'No se puede eliminar la sucursal porque está relacionada con otros registros.',
                    'code'    => 'FK_CONSTRAINT',
                ], 409);
            }

            return response()->json([
                'message' => 'No se pudo eliminar la sucursal.',
                'code'    => 'DELETE_FAILED',
                'error'   => $e->getMessage(),
                'sqlstate' => $sqlState,
                'sqlcode'  => $sqlCode,
            ], 500);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error inesperado al eliminar la sucursal.',
                'code'    => 'UNEXPECTED_ERROR',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function ListPlat(): JsonResponse
    {
        $options = collect($this->plataforma)
            ->map(fn ($label, $value) => [
                'value' => (int) $value,
                'label' => $label,
            ])
            ->values();

        return response()->json([
            'data' => $options,
        ]);
    }

    public function ListSucursales(): JsonResponse
    {
        $options = collect($this->sucursales)
            ->map(fn ($label, $value) => [
                'value' => (int) $value,
                'label' => $label,
            ])
            ->values();

        return response()->json([
            'data' => $options,
        ]);
    }

   public function ImportDataSucursales(Request $request): JsonResponse
{
    // 1) Validar archivo
    $request->validate([
        'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:51200'], // 50MB
    ], [
        'file.required' => 'Debes seleccionar un archivo.',
        'file.mimes'    => 'Formato no válido. Solo CSV o Excel.',
        'file.max'      => 'El archivo excede el tamaño permitido (50MB).',
    ]);

    $file = $request->file('file');

    // 2) Cargar con PhpSpreadsheet
    try {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true); // keys A,B,C...
    } catch (\Throwable $e) {
        return response()->json([
            'message' => 'No se pudo leer el archivo. Verifica que sea CSV/Excel válido.',
            'error'   => $e->getMessage(),
        ], 422);
    }

    if (count($rows) < 2) {
        return response()->json([
            'message' => 'El archivo no contiene datos para importar.',
        ], 422);
    }

    // 3) Detectar encabezados
    $headerRow = array_shift($rows);

    $normalize = static fn ($v) => strtolower(trim(preg_replace('/\s+/', '', (string) $v)));

    $headerMap = [];
    foreach ($headerRow as $col => $name) {
        $h = $normalize($name);
        if ($h !== '') {
            $headerMap[$h] = $col;
        }
    }

    $getCol = static function (array $map, array $aliases) {
        foreach ($aliases as $a) {
            if (isset($map[$a])) return $map[$a];
        }
        return null;
    };

    $colPlat     = $getCol($headerMap, ['plat', 'plataforma']);
    $colNameS    = $getCol($headerMap, ['names', 'namesucursal', 'nombresucursal', 'sucursal', 'namesucursalid', 'sucursalid']);
    $colServHost = $getCol($headerMap, ['servhost', 'host', 'hostname', 'servidorhost', 'serviciohost']);
    $colIp       = $getCol($headerMap, ['ip', 'direccionip']);
    $colKeys     = $getCol($headerMap, ['keys', 'key', 'llave', 'llaves']);

    if (!$colPlat || !$colNameS || !$colServHost) {
        return response()->json([
            'message' => 'Encabezados inválidos. Debes incluir al menos: plat, nameS, servHost. (ip/keys según aplique)',
            'detected_headers' => array_keys($headerMap),
        ], 422);
    }

    // 4) Pre-validar TODAS las filas (sin insertar), juntando errores
    $errors = [];
    $processed = 0;
    $skippedEmpty = 0;

    foreach ($rows as $i => $row) {
        $rowNumber = $i + 2; // fila real en excel

        $platRaw     = $row[$colPlat] ?? null;
        $nameSRaw    = $row[$colNameS] ?? null;
        $servHostRaw = $row[$colServHost] ?? null;
        $ipRaw       = $colIp ? ($row[$colIp] ?? null) : null;
        $keysRaw     = $colKeys ? ($row[$colKeys] ?? null) : null;

        $plat     = is_numeric($platRaw) ? (int) $platRaw : null;
        $nameS    = is_numeric($nameSRaw) ? (int) $nameSRaw : null;
        $servHost = trim((string) $servHostRaw);

        $ip   = $ipRaw !== null ? trim((string) $ipRaw) : null;
        $keys = $keysRaw !== null ? mb_strtolower(trim((string) $keysRaw)) : null;

        // saltar fila vacía
        if (
            !$plat && !$nameS && $servHost === '' &&
            ($ip === null || $ip === '') &&
            ($keys === null || $keys === '')
        ) {
            $skippedEmpty++;
            continue;
        }

        $processed++;

        $data = [
            'plat'     => $plat,
            'nameS'    => $nameS,
            'servHost' => $servHost,
            'ip'       => ($ip !== '' ? $ip : null),
            'keys'     => ($keys !== '' ? $keys : null),
        ];

        $validator = Validator::make($data, [
            'nameS'    => ['required', 'integer', Rule::in([1, 2, 3, 4])],
            'servHost' => ['required', 'string', 'max:255'],
            'plat'     => ['required', 'integer', Rule::in([1, 2, 3])],

            'ip' => [
                Rule::requiredIf(static fn () => in_array((int) ($data['plat'] ?? 0), [1, 3], true)),
                'nullable',
                'ipv4',
            ],

            'keys' => [
                Rule::requiredIf(static fn () => (int) ($data['plat'] ?? 0) === 2),
                'nullable',
                'string',
                'min:1',
                'max:20',
            ],
        ], [
            'plat.required' => 'La columna "plat" es obligatoria.',
            'plat.in' => 'La columna "plat" debe ser 1 (ARUBA), 2 (ALESTRA) o 3 (RESPALDOS).',
            'nameS.required' => 'La columna "nameS" es obligatoria.',
            'nameS.in' => 'La columna "nameS" debe ser 1 (VALLE), 2 (GDL), 3 (MTY) o 4 (MER).',
            'servHost.required' => 'La columna "servHost" es obligatoria.',
            'ip.required' => 'La IP es obligatoria para ARUBA/RESPALDOS (plat=1/3).',
            'ip.ipv4' => 'La IP debe ser válida (IPv4).',
            'keys.required' => 'La llave es obligatoria para ALESTRA (plat=2).',
            'keys.max' => 'La llave no puede exceder 20 caracteres.',
        ]);

        // Validación sólida por coherencia
        $validator->after(static function ($v) use ($data) {
            $plat = (int) ($data['plat'] ?? 0);

            $hasIp  = isset($data['ip']) && trim((string) $data['ip']) !== '';
            $hasKey = isset($data['keys']) && trim((string) $data['keys']) !== '';

            if ($hasIp && $hasKey) {
                $v->errors()->add('ip', 'Contenido inválido: no puedes enviar IP y keys al mismo tiempo.');
                $v->errors()->add('keys', 'Contenido inválido: no puedes enviar IP y keys al mismo tiempo.');
            }

            if (in_array($plat, [1, 3], true) && $hasKey) {
                $v->errors()->add('keys', 'Contenido inválido: ARUBA/RESPALDOS (plat=1/3) no acepta "keys". Debes enviar "ip".');
            }

            if ($plat === 2 && $hasIp) {
                $v->errors()->add('ip', 'Contenido inválido: ALESTRA (plat=2) no acepta "ip". Debes enviar "keys".');
            }
        });

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $fieldMap = $validator->errors()->toArray();
            $fields   = array_keys($fieldMap);

            $errors[] = [
                'row' => $rowNumber,
                'code' => 'ROW_INVALID',
                'fields' => $fields,
                'messages' => $messages,
                'data' => $data,
            ];
        }
    }

    if (!empty($errors)) {
        return response()->json([
            'message' => 'Importación cancelada: el contenido del archivo es inválido.',
            'processed' => $processed,
            'skipped_empty' => $skippedEmpty,
            'errors_count' => count($errors),
            'errors' => $errors,
            'first_error_row' => $errors[0]['row'] ?? null,
            'first_error' => $errors[0]['messages'][0] ?? null,
        ], 422);
    }

    // 5) Insert / Update en transacción
    $inserted = 0;
    $updated  = 0;

    DB::beginTransaction();

    try {
        foreach ($rows as $i => $row) {
            $platRaw     = $row[$colPlat] ?? null;
            $nameSRaw    = $row[$colNameS] ?? null;
            $servHostRaw = $row[$colServHost] ?? null;
            $ipRaw       = $colIp ? ($row[$colIp] ?? null) : null;
            $keysRaw     = $colKeys ? ($row[$colKeys] ?? null) : null;

            $plat     = is_numeric($platRaw) ? (int) $platRaw : null;
            $nameS    = is_numeric($nameSRaw) ? (int) $nameSRaw : null;
            $servHost = trim((string) $servHostRaw);

            $ip   = $ipRaw !== null ? trim((string) $ipRaw) : null;
            $keys = $keysRaw !== null ? mb_strtolower(trim((string) $keysRaw)) : null;

            if (
                !$plat && !$nameS && $servHost === '' &&
                ($ip === null || $ip === '') &&
                ($keys === null || $keys === '')
            ) {
                continue;
            }

            $data = [
                'plat'     => $plat,
                'nameS'    => $nameS,
                'servHost' => $servHost,
                'ip'       => ($ip !== '' ? $ip : null),
                'keys'     => ($keys !== '' ? $keys : null),
            ];

            // Limpieza por plataforma (ya está validado)
            if (in_array((int) $data['plat'], [1, 3], true)) $data['keys'] = null;
            if ((int) $data['plat'] === 2) $data['ip'] = null;

            $existing = Sucursales::query()
                ->where('nameS', $data['nameS'])
                ->where('plat', $data['plat'])
                ->where('servHost', $data['servHost'])
                ->first();

            if ($existing) {
                $existing->update([
                    'ip'   => $data['ip'],
                    'keys' => $data['keys'],
                ]);
                $updated++;
            } else {
                Sucursales::create($data);
                $inserted++;
            }
        }

        DB::commit();
    } catch (\Throwable $e) {
        DB::rollBack();

        // Si quieres 0 logs, borra esta línea:
        Log::error("[SucursalesImport] CRASH - rolled back", [
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
        ]);

        return response()->json([
            'message' => 'Error importando. Se revirtió la operación.',
            'error'   => $e->getMessage(),
        ], 500);
    }

    return response()->json([
        'message'  => 'Importación completada correctamente.',
        'processed' => $processed,
        'skipped_empty' => $skippedEmpty,
        'inserted' => $inserted,
        'updated'  => $updated,
    ], 200);
}

}
