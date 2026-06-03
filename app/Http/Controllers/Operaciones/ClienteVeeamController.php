<?php

namespace App\Http\Controllers\Operaciones;

use App\Http\Controllers\Controller;
use App\Models\Operaciones\ClienteVeeam;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ClienteVeeamController extends Controller
{

    private array $statusVe = [];
    public function __construct() {
        $this->statusVe = [
            '1' => 'Completado Exitoso - Backup finalizado sin errores', '2' => 'Completado con Advertencias - Backup terminado pero con observaciones menores', 
            '3' => 'Fallido - Backup no se completó correctamente', '4' => ' En Progreso - Backup actualmente en ejecución',
            '5' => ' Pausado - Backup detenido temporalmente', '6' => ' Pendiente - Programado pero no iniciado',
        ];
    }
   
  public function index(Request $request)
{
    $search   = trim((string) $request->query('search', ''));
    $inactive = $request->query('inactive', null);
    $perPage  = 30;

    $query = ClienteVeeam::query()
        ->with('AppCV')
        ->select('c_veeam.*')
        ->selectSub(function ($sub) {
            $sub->from('monitoreos')
                ->select('dateRest')
                ->whereColumn('monitoreos.client_id', 'c_veeam.id')
                ->whereNotNull('dateRest')
                ->orderByDesc('dateRest')
                ->limit(1);
        }, 'last_restore_date');

    // Filtro activo/inactivo
    if ($inactive === '0') {
        $query->where('activo', 1);
    } elseif ($inactive === '1') {
        $query->where('activo', 0);
    }

    // Buscador optimizado
    if ($search !== '') {
        // Si es número puro — búsqueda exacta y por prefijo solo en numCV
        // Evita tocar nameCV, backup y la relación AppCV innecesariamente
        if (ctype_digit($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('numCV', (int) $search)
                  ->orWhere('numCV', 'like', "{$search}%"); // sin % al inicio → usa índice
            });
        } else {

            $query->where(function ($q) use ($search) {
                $q->where('nameCV', 'like', "{$search}%")  // prefijo → usa índice
                  ->orWhere('nameCV', 'like', "%{$search}%") // fallback contains
                  ->orWhere('backup', 'like', "%{$search}%")
                  ->orWhereHas('AppCV', fn ($sub) =>
                        $sub->where('nameService', 'like', "%{$search}%")
                  );
            });
        }
    }

    $clientes = $query
        ->orderBy('id')
        ->paginate($perPage);

    return response()->json($clientes);
}

  
    public function store(Request $request)
    {
        $numCV = trim((string) $request->input('numCV', ''));
        if ($numCV === '') {
            $numCV = 'NO IDENTIFICADO';
        }

        $data = $request->validate([
            'numCV'  => ['nullable', 'string', 'max:255'],
            'nameCV' => ['required', 'string', 'max:255'],

            'app' => [
                'required',
                'integer',
                Rule::exists('app_service', 'id')->where(function ($q) {
                    $q->where('nameService', 'like', '%Veeam%');
                }),
            ],

            'backup' => [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) {
                    $v = trim(preg_replace('/\s+/', ' ', (string) $value));
                    if (!preg_match('/^\d+(\.\d+)?\s+(GB|TB)$/i', $v)) {
                        $fail('El almacenamiento debe tener número y unidad (GB o TB). Ej: "256.5 GB" o "1 TB".');
                    }
                },
            ],

            'jobs'   => ['nullable', 'integer', 'min:0', 'max:300'],
            'activo' => ['nullable', 'boolean'],
        ], [
            'app.exists' => 'El aplicativo seleccionado no es válido o no pertenece al catálogo de Veeam.',
        ]);

        $data['numCV'] = $numCV;

        $data['backup'] = trim(preg_replace('/\s+/', ' ', $data['backup']));
        $data['backup'] = preg_replace_callback('/\b(gb|tb)\b/i', fn ($m) => strtoupper($m[0]), $data['backup']);

        if (!array_key_exists('activo', $data)) {
            $data['activo'] = 1;
        }

        $exists = ClienteVeeam::where('numCV', $data['numCV'])
            ->where('nameCV', $data['nameCV'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Ya existe un cliente con el mismo ID y nombre.',
                'code'    => 'DUPLICATE',
            ], 409);
        }

        try {
            $cliente = ClienteVeeam::create($data);

            return response()->json([
                'message' => 'Cliente Veeam creado correctamente.',
                'data'    => $cliente,
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al crear el cliente Veeam.',
                'code'    => 'SERVER_ERROR',
            ], 500);
        }
    }

    public function edit(string $id)
    {
        $cliente = ClienteVeeam::find($id);
        return response()->json([
            'data' => $cliente,
        ], 200);
    }

    public function update(Request $request, string $id)
    {
        $cliente = ClienteVeeam::find($id);
        $numCV = trim((string) $request->input('numCV', ''));
        if ($numCV === '') {
            $numCV = 'NO IDENTIFICADO';
        }

        $data = $request->validate([
            'numCV'  => ['nullable', 'string', 'max:255'],
            'nameCV' => ['required', 'string', 'max:255'],

            'app' => [
                'required',
                'integer',
                Rule::exists('app_service', 'id')->where(function ($q) {
                    $q->where('nameService', 'like', '%Veeam%');
                }),
            ],

            'backup' => [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) {
                    $v = trim(preg_replace('/\s+/', ' ', (string) $value));
                    if (!preg_match('/^\d+(\.\d+)?\s+(GB|TB)$/i', $v)) {
                        $fail('El almacenamiento debe tener número y unidad (GB o TB). Ej: "256.5 GB" o "1 TB".');
                    }
                },
            ],

            'jobs'   => ['nullable', 'integer', 'min:0', 'max:300'],
            'activo' => ['nullable', 'boolean'],
        ], [
            'app.exists' => 'El aplicativo seleccionado no es válido o no pertenece al catálogo de Veeam.',
        ]);

        // fuerza el numCV normalizado
        $data['numCV'] = $numCV;

        // normaliza backup (espacios y unidad en mayúscula)
        $data['backup'] = trim(preg_replace('/\s+/', ' ', $data['backup']));
        $data['backup'] = preg_replace_callback(
            '/\b(gb|tb)\b/i',
            fn ($m) => strtoupper($m[0]),
            $data['backup']
        );

        // activo: si no viene, conserva el actual
        if (!array_key_exists('activo', $data)) {
            $data['activo'] = (int) ($cliente->activo ?? 1);
        }

        // ✅ DUPLICADO (numCV + nameCV) excluyendo este registro
        $exists = ClienteVeeam::where('numCV', $data['numCV'])
            ->where('nameCV', $data['nameCV'])
            ->where('id', '!=', $cliente->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Ya existe un cliente con el mismo ID y nombre.',
                'code'    => 'DUPLICATE',
            ], 409);
        }

        try {
            $cliente->update($data);

            return response()->json([
                'message' => 'Cliente Veeam actualizado correctamente.',
                'data'    => $cliente->fresh(),
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al actualizar el cliente Veeam.',
                'code'    => 'SERVER_ERROR',
            ], 500);
        }
    }

    public function show($id)
    {
        $clienteVeeam = ClienteVeeam::with([
    'AppCV',
    'MonitV' => function ($q) {
        $q->select('client_id', 'dateRest')
        ->whereNotNull('dateRest')
        ->groupBy('client_id', 'dateRest')
        ->orderByDesc('dateRest');
    },
    ])->find($id);

        if (!$clienteVeeam) {
            return response()->json([
                'message' => 'Cliente no encontrado.',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        Log::info($clienteVeeam);

        return response()->json([
            'data' => $clienteVeeam,
        ], 200);
    }



    public function destroy($id)
    {
        try {
            $cliente = ClienteVeeam::find($id);

            if (!$cliente) {
                return response()->json([
                    'message' => 'El cliente no existe o ya fue eliminado.',
                    'code'    => 'NOT_FOUND',
                ], 404);
            }

            $cliente->delete();

            return response()->json([
                'message' => 'Cliente Veeam eliminado correctamente.',
                'code'    => 'DELETED',
                'data'    => ['id' => $id],
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al eliminar el cliente Veeam.',
                'code'    => 'SERVER_ERROR',
            ], 500);
        }
    }


    public function ClientDeactivate(int $id, Request $request)
    {
        $request->validate([
            'activo' => ['required', 'boolean'],
        ]);

        $cliente = ClienteVeeam::find($id);

        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado.',
            ], 404);
        }

        $cliente->activo = (int) $request->boolean('activo');
        $cliente->save();

        return response()->json([
            'success' => true,
            'message' => 'Estatus actualizado.',
            'data'    => $cliente,
        ]);
    }

  public function SelectVeeam()
    {
        $siteAppId = (int) DB::table('app_service')
            ->where('nameService', 'like', '%Veeam%')
            ->orderBy('id')
            ->value('id');

        if (!$siteAppId) {
            return response()->json([
                'message' => 'No existe app_service configurado para Veeam.',
                'code'    => 'VEEAM_APP_SERVICE_NOT_FOUND',
            ], 422);
        }

        $last = DB::table('monitoreos')
            ->selectRaw('client_id, siteApp, MAX(dateRest) as last_dateRest')
            ->whereNotNull('dateRest')
            ->groupBy('client_id', 'siteApp');

        $active = DB::table('monitoreos')
            ->selectRaw('client_id, siteApp, 1 as has_active')
            ->where('concluido', 1)
            ->groupBy('client_id', 'siteApp');

        $items = ClienteVeeam::query()
            ->from('c_veeam')
            ->where('c_veeam.activo', 1)

            // ✅ Join para saber “a qué veeam (app_service) pertenece”
            ->leftJoin('app_service as aps', 'aps.id', '=', 'c_veeam.app')

            // last_dateRest
            ->leftJoinSub($last, 'lr', function ($join) {
                $join->on('c_veeam.id', '=', 'lr.client_id')
                    ->on('c_veeam.app', '=', 'lr.siteApp');
            })

            // activos
            ->leftJoinSub($active, 'ac', function ($join) {
                $join->on('c_veeam.id', '=', 'ac.client_id')
                    ->on('c_veeam.app', '=', 'ac.siteApp');
            })

            // excluir los que tienen activo
            ->whereNull('ac.has_active')

            ->orderBy('c_veeam.nameCV')
            ->get([
                'c_veeam.id',
                'c_veeam.numCV',
                'c_veeam.nameCV',
                'c_veeam.backup',
                'c_veeam.jobs',
                'c_veeam.app', // ✅ id app_service (esto identifica qué veeam es)
                DB::raw('aps.nameService as veeam_name'), // ✅ nombre del veeam
                DB::raw('lr.last_dateRest as last_dateRest'),
            ])
            ->map(fn ($c) => [
                'id'            => (int) $c->id,
                'numCV'         => $c->numCV,
                'nameCV'        => $c->nameCV,
                'backup'        => $c->backup,
                'jobs'          => $c->jobs,
                'last_dateRest' => $c->last_dateRest,

                // ✅ NUEVO: a qué “Veeam” pertenece (app_service)
                'veeam_id'      => (int) $c->app,
                'veeam_name'    => $c->veeam_name ?? null,

                // ✅ NUEVO: site para que el front no lo invente
                'site'          => 'veeam',

                // label UI
                'label'         => trim($c->numCV.' - '.$c->nameCV),
            ])
            ->values();

        return response()->json([
            'items'     => $items,
            'status'    => $this->statusVe,
            'source'    => 'veeam',
            'siteAppId' => $siteAppId,
        ], 200);
    }

    public function ExportDataClientes(): StreamedResponse
    {
        $clientes = ClienteVeeam::query()
            ->with('AppCV')
            ->select('c_veeam.*')
            ->orderBy('c_veeam.id')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setTitle('Clientes Veeam');

        // Encabezados
        $headers = [
            'A1' => 'ID CLIENTE',
            'B1' => 'NOMBRE',
            'C1' => 'APLICATIVO',
            'D1' => 'REPOSITORIO',
            'E1' => 'JOBS',
            'F1' => 'ESTATUS',
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        // Estilo encabezados
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->getStyle('A1:F1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:F1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FFD9EAF7');

        // Datos
        $row = 2;

        foreach ($clientes as $cliente) {
            $estatus = ((int) $cliente->activo === 1) ? 'ACTIVO' : 'INACTIVO';

            $sheet->setCellValueExplicit(
                'A' . $row,
                (string) ($cliente->numCV ?? ''),
                DataType::TYPE_STRING
            );

            $sheet->setCellValue('B' . $row, $cliente->nameCV ?? '');

            $sheet->setCellValue(
                'C' . $row,
                $cliente->AppCV->nameService ?? 'SIN APLICATIVO'
            );

            $sheet->setCellValue('D' . $row, $cliente->backup ?? '');
            $sheet->setCellValue('E' . $row, $cliente->jobs ?? '');
            $sheet->setCellValue('F' . $row, $estatus);

            $row++;
        }

        // Auto tamaño de columnas
        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Centrar columnas específicas
        $lastRow = max($row - 1, 1);
        $sheet->getStyle("A1:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E1:F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Congelar encabezado
        $sheet->freezePane('A2');

        $fileName = 'clientes_veeam_' . now()->format('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }


   public function ImportDataClientes(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:51200'],
        ], [
            'file.required' => 'Debes seleccionar un archivo.',
            'file.mimes'    => 'Formato no válido. Solo CSV o Excel.',
            'file.max'      => 'El archivo excede el tamaño permitido (50MB).',
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
            $rows = $spreadsheet
                ->getActiveSheet()
                ->toArray(null, true, true, true);
        } catch (Throwable $e) {
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

        $headerMap = $this->buildExcelHeaderMap(array_shift($rows));

        $columns = [
            'numCV'  => $this->findExcelColumn($headerMap, ['idcliente', 'idclient', 'numcv', 'numerocliente', 'nocliente']),
            'nameCV' => $this->findExcelColumn($headerMap, ['nombre', 'nombrecliente', 'namecv', 'cliente']),
            'app'    => $this->findExcelColumn($headerMap, ['aplicativo', 'app', 'appservice', 'nameservice', 'servicio']),
            'backup' => $this->findExcelColumn($headerMap, ['repositorio', 'backup', 'almacenamiento', 'storage', 'capacidad']),
            'jobs'   => $this->findExcelColumn($headerMap, ['jobs', 'job', 'trabajos']),
            'activo' => $this->findExcelColumn($headerMap, ['estatus', 'estado', 'activo', 'status']),
        ];

        $missingHeaders = $this->missingImportHeaders($columns);

        if (!empty($missingHeaders)) {
            return response()->json([
                'message' => 'Encabezados inválidos. Debes incluir: ID CLIENTE, NOMBRE, APLICATIVO, REPOSITORIO, JOBS y ESTATUS.',
                'missing_headers' => $missingHeaders,
                'detected_headers' => array_keys($headerMap),
            ], 422);
        }

        $appMap = $this->getVeeamAppMap();

        if (empty($appMap)) {
            return response()->json([
                'message' => 'No existen aplicativos Veeam configurados en app_service.',
            ], 422);
        }

        $processed = 0;
        $inserted = 0;
        $skippedEmpty = 0;
        $skippedExisting = 0;
        $skippedDuplicatesFile = 0;

        $notProcessed = [];
        $seenInFile = [];

        DB::beginTransaction();

        try {
            foreach ($rows as $i => $row) {
                $rowNumber = $i + 2;

                $parsed = $this->parseClienteImportRow($row, $columns, $appMap);

                if ($parsed['is_empty']) {
                    $skippedEmpty++;
                    continue;
                }

                $processed++;

                $data = $parsed['data'];
                $raw = $parsed['raw'];

                $uniqueKey = $this->normalizeExcelText($data['numCV'] . '|' . $data['nameCV']);

                if (isset($seenInFile[$uniqueKey])) {
                    $skippedDuplicatesFile++;

                    $notProcessed[] = $this->makeNotProcessedRow(
                        $rowNumber,
                        $data['numCV'],
                        $data['nameCV'],
                        'Duplicado dentro del archivo. Ya apareció en la fila ' . $seenInFile[$uniqueKey] . '.'
                    );

                    continue;
                }

                $seenInFile[$uniqueKey] = $rowNumber;

                $validator = $this->validateClienteImportData($data, $raw['appName']);

                if ($validator->fails()) {
                    $messages = $validator->errors()->all();

                    $notProcessed[] = $this->makeNotProcessedRow(
                        $rowNumber,
                        $data['numCV'],
                        $data['nameCV'],
                        $messages[0] ?? 'El registro contiene datos inválidos.'
                    );

                    continue;
                }

                $exists = ClienteVeeam::query()
                    ->where('numCV', $data['numCV'])
                    ->where('nameCV', $data['nameCV'])
                    ->exists();

                if ($exists) {
                    $skippedExisting++;

                    $notProcessed[] = $this->makeNotProcessedRow(
                        $rowNumber,
                        $data['numCV'],
                        $data['nameCV'],
                        'Ya existe un cliente con el mismo ID CLIENTE y NOMBRE.'
                    );

                    continue;
                }

                ClienteVeeam::create($data);
                $inserted++;
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('[ClientesVeeamImport] Error importando clientes Veeam', [
                'error' => $e->getMessage(),
                'line'  => $e->getLine(),
                'file'  => $e->getFile(),
            ]);

            return response()->json([
                'message' => 'Error importando. Se revirtió la operación.',
                'error'   => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'message' => 'Importación completada correctamente.',
            'processed' => $processed,
            'skipped_empty' => $skippedEmpty,
            'inserted' => $inserted,
            'skipped_existing' => $skippedExisting,
            'skipped_duplicates_file' => $skippedDuplicatesFile,
            'not_processed_count' => count($notProcessed),
            'not_processed' => $notProcessed,
        ], 200);
    }

    private function buildExcelHeaderMap(array $headerRow): array
    {
        $headerMap = [];

        foreach ($headerRow as $col => $name) {
            $header = $this->normalizeExcelText($name);

            if ($header !== '') {
                $headerMap[$header] = $col;
            }
        }

        return $headerMap;
    }

    private function findExcelColumn(array $headerMap, array $aliases): ?string
    {
        foreach ($aliases as $alias) {
            if (isset($headerMap[$alias])) {
                return $headerMap[$alias];
            }
        }

        return null;
    }

    private function missingImportHeaders(array $columns): array
    {
        $labels = [
            'numCV'  => 'ID CLIENTE',
            'nameCV' => 'NOMBRE',
            'app'    => 'APLICATIVO',
            'backup' => 'REPOSITORIO',
            'jobs'   => 'JOBS',
            'activo' => 'ESTATUS',
        ];

        $missing = [];

        foreach ($columns as $key => $column) {
            if ($column === null) {
                $missing[] = $labels[$key] ?? $key;
            }
        }

        return $missing;
    }

    private function getVeeamAppMap(): array
    {
        $apps = DB::table('app_service')
            ->select('id', 'nameService')
            ->where('nameService', 'like', '%Veeam%')
            ->get();

        $map = [];

        foreach ($apps as $app) {
            $map[$this->normalizeExcelText($app->nameService)] = (int) $app->id;
        }

        return $map;
    }

    private function parseClienteImportRow(array $row, array $columns, array $appMap): array
    {
        $numCVRaw  = $row[$columns['numCV']] ?? null;
        $nameCVRaw = $row[$columns['nameCV']] ?? null;
        $appRaw    = $row[$columns['app']] ?? null;
        $backupRaw = $row[$columns['backup']] ?? null;
        $jobsRaw   = $row[$columns['jobs']] ?? null;
        $activoRaw = $row[$columns['activo']] ?? null;

        $numCV = trim((string) $numCVRaw);
        $nameCV = trim((string) $nameCVRaw);
        $appName = trim((string) $appRaw);
        $backup = $this->normalizeBackupExcel($backupRaw);
        $activo = $this->normalizeActivoExcelImport($activoRaw);

        if ($numCV === '') {
            $numCV = 'NO IDENTIFICADO';
        }

        $jobs = null;

        if ($jobsRaw !== null && trim((string) $jobsRaw) !== '') {
            $jobs = is_numeric($jobsRaw) ? (int) $jobsRaw : $jobsRaw;
        }

        $isEmpty =
            trim((string) $numCVRaw) === '' &&
            $nameCV === '' &&
            $appName === '' &&
            $backup === '' &&
            trim((string) $jobsRaw) === '' &&
            trim((string) $activoRaw) === '';

        return [
            'is_empty' => $isEmpty,
            'raw' => [
                'appName' => $appName,
                'activo' => $activoRaw,
            ],
            'data' => [
                'numCV'  => $numCV,
                'nameCV' => $nameCV,
                'app'    => $appMap[$this->normalizeExcelText($appName)] ?? null,
                'backup' => $backup,
                'jobs'   => $jobs,
                'activo' => $activo,
            ],
        ];
    }

    private function validateClienteImportData(array $data, string $appName)
    {
        $validator = Validator::make($data, [
            'numCV'  => ['nullable', 'string', 'max:255'],
            'nameCV' => ['required', 'string', 'max:255'],

            'app' => ['nullable', 'integer'],

            'backup' => [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) {
                    $v = trim(preg_replace('/\s+/', ' ', (string) $value));

                    if (!preg_match('/^\d+(\.\d+)?\s+(GB|TB)$/i', $v)) {
                        $fail('El repositorio debe tener número y unidad. Ej: "256.5 GB" o "1 TB".');
                    }
                },
            ],

            'jobs'   => ['nullable', 'integer', 'min:0', 'max:300'],
            'activo' => ['nullable', 'boolean'],
        ], [
            'nameCV.required' => 'La columna "NOMBRE" es obligatoria.',
            'backup.required' => 'La columna "REPOSITORIO" es obligatoria.',
            'jobs.integer'    => 'La columna "JOBS" debe ser numérica.',
        ]);

        $validator->after(function ($v) use ($data, $appName) {
            if ($data['app'] === null) {
                $message = trim($appName) === ''
                    ? 'La columna "APLICATIVO" es obligatoria.'
                    : 'No se encontró el aplicativo "' . $appName . '" en el catálogo de Veeam.';

                $v->errors()->add('app', $message);
            }

            if ($data['activo'] === null) {
                $v->errors()->add('activo', 'La columna "ESTATUS" debe ser ACTIVO o INACTIVO.');
            }
        });

        return $validator;
    }

    private function makeNotProcessedRow(int $row, string $idCliente, string $nombre, string $motivo): array
    {
        return [
            'row'        => $row,
            'id_cliente' => $idCliente,
            'nombre'     => $nombre,
            'motivo'     => $motivo,
        ];
    }

    private function normalizeExcelText($value): string
    {
        $value = trim((string) $value);

        $value = str_replace(
            ['Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ', 'á', 'é', 'í', 'ó', 'ú', 'ñ'],
            ['A', 'E', 'I', 'O', 'U', 'N', 'a', 'e', 'i', 'o', 'u', 'n'],
            $value
        );

        $value = strtolower($value);

        return preg_replace('/[\s\-_\.]+/', '', $value);
    }

    private function normalizeBackupExcel($value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        $value = preg_replace('/\s+/', ' ', $value);

        // Permite 500GB / 1TB y lo convierte a 500 GB / 1 TB
        $value = preg_replace('/^(\d+(?:\.\d+)?)\s*(gb|tb)$/i', '$1 $2', $value);

        return preg_replace_callback('/\b(gb|tb)\b/i', fn ($m) => strtoupper($m[0]), $value);
    }

    private function normalizeActivoExcelImport($value): ?int
    {
        $value = $this->normalizeExcelText($value);

        if (in_array($value, ['activo', 'activa', 'active', '1', 'si', 'sí', 'true', 'verdadero'], true)) {
            return 1;
        }

        if (in_array($value, ['inactivo', 'inactiva', 'inactive', '0', 'no', 'false', 'falso'], true)) {
            return 0;
        }

        return null;
    }

}
