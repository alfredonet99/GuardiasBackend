<?php

namespace App\Http\Controllers\Operaciones;

use App\Http\Controllers\Controller;
use App\Services\NetSuiteService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NetSuiteController extends Controller
{
    public function index(Request $request, NetSuiteService $netS)
    {
        $raw      = (string) $request->get('search', '');
        $search   = trim(Str::lower($raw));
        $inactive = $request->has('inactive') ? (string)$request->get('inactive') : null; 
        $perPage  = max(1, min(1000, (int) $request->get('perPage', default: 100)));
        $page     = max(1, (int) $request->get('page', 1));
        $offset   = ($page - 1) * $perPage;

        Log::info('📥 [NetSuite clientes] params', compact('raw','search','inactive','perPage','page','offset'));

        $esc = fn ($s) => str_replace("'", "''", $s);

        $whereParts = [];

        /**
         * ✅ Allowlist fija desde config/operaciones.php
         * 'netsuite_client_ids' => [ ... ]
         */
        $allowedIds = config('idNetsuite.netsuite_client_ids', []);
        $allowedIds = array_values(array_unique(array_map('intval', $allowedIds)));

        if (empty($allowedIds)) {
            return response()->json([
                'success' => true,
                'data'    => [],
                'meta'    => [
                    'page'     => $page,
                    'perPage'  => $perPage,
                    'total'    => 0,
                    'lastPage' => 1,
                    'hasMore'  => false,
                    'search'   => $raw,
                    'inactive' => $inactive,
                ],
                'message' => 'Sin IDs permitidos configurados (operaciones.netsuite_client_ids)',
            ]);
        }

        // Evita IN gigantes (robusto si mañana sube el número de IDs)
        $chunks = array_chunk($allowedIds, 500);
        $inSql = implode(' OR ', array_map(
            fn ($c) => 'id IN (' . implode(',', $c) . ')',
            $chunks
        ));

        // 👇 Este filtro SIEMPRE aplica, aunque no haya search/inactive
        $whereParts[] = '(' . $inSql . ')';

        // Filtro por búsqueda (opcional)
        if ($search !== '') {
            if (ctype_digit($search)) {
                $whereParts[] = "id = " . (int) $search;
            } else {
                $whereParts[] = "LOWER(name) LIKE '%" . $esc($search) . "%'";
            }
        }

        // Filtro por inactivos (opcional)
        if ($inactive === '0') {
            $whereParts[] = "isinactive = 'F'";
        } elseif ($inactive === '1') {
            $whereParts[] = "isinactive = 'T'";
        }

        $where = !empty($whereParts) ? ('WHERE ' . implode(' AND ', $whereParts)) : '';

        $sql = "
            SELECT id, name, isinactive, custrecord_rfc_cte_final_crm
            FROM customrecord_cliente_final_crm
            $where
            ORDER BY id
        ";

        $resp = $netS->query($sql, $perPage, $offset);

        // ❌ Error NetSuite
        if (!empty($resp['error'])) {
            return response()->json([
                'success' => false,
                'message' => $resp['body'] ?? 'Error desconocido',
                'status'  => $resp['status'] ?? null,
                'meta'    => [
                    'page'     => $page,
                    'perPage'  => $perPage,
                    'search'   => $raw,
                    'inactive' => $inactive,
                ],
                'data' => [],
            ], 502);
        }

        $items   = $resp['items'] ?? [];
        $hasMore = (bool)($resp['hasMore'] ?? false);

        $total = $resp['totalResults']
            ?? ($hasMore ? ($page + 1) * $perPage : ($offset + count($items)));

        $paginator = new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path'  => url()->current(),
            'query' => $request->query(),
        ]);

        Log::info('✅ operaciones ids count', [
    'count' => count(config('operaciones.netsuite_client_ids', [])),
    'first' => array_slice(config('operaciones.netsuite_client_ids', []), 0, 5),
    'path'  => base_path('config/operaciones.php'),
    'exists'=> file_exists(base_path('config/operaciones.php')),
]);


        return response()->json([
            'success' => true,
            'data'    => $paginator->items(),
            'meta'    => [
                'page'     => $paginator->currentPage(),
                'perPage'  => $paginator->perPage(),
                'total'    => $paginator->total(),
                'lastPage' => $paginator->lastPage(),
                'hasMore'  => $hasMore,
                'search'   => $raw,
                'inactive' => $inactive,
            ],
        ]);
    }

    public function show(int $id, Request $request, NetSuiteService $netS)
    {
        if ($id <= 0) {
            throw new NotFoundHttpException('ID inválido.');
        }

        // ✅ Allowlist (mismo criterio que index)
        $allowedIds = config('idNetsuite.netsuite_client_ids', []);
        $allowedIds = array_values(array_unique(array_map('intval', $allowedIds)));

        if (!in_array((int)$id, $allowedIds, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no permitido (fuera de allowlist).',
                'data'    => null,
            ], 403);
        }

        Log::info('🔎 [NetSuite clientes] show', ['id' => $id]);

        // 1) Cliente
        $fieldsCliente = "id, name, isinactive, custrecord_rfc_cte_final_crm, custrecord_mailcc_clientfin";
        $sqlCliente = "
            SELECT $fieldsCliente
            FROM customrecord_cliente_final_crm
            WHERE id = $id
        ";

        $respCliente = $netS->query($sqlCliente, 1, 0);

        if (!empty($respCliente['error'])) {
            return response()->json([
                'success' => false,
                'message' => $respCliente['body'] ?? 'Error al consultar NetSuite (cliente)',
                'status'  => $respCliente['status'] ?? null,
                'data'    => null,
            ], 502);
        }

        $cliente = ($respCliente['items'][0] ?? null);

        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado.',
                'data'    => null,
            ], 404);
        }

        // 2) Forms del cliente
        $sqlForms = "
            SELECT
                customrecord830.id                                   AS id_formserv,
                customrecord830.custrecord_datactr_formserv          AS datacenter_id,
                customlistlist_datacenter_formserv.name              AS datacenter_name
            FROM customrecord830
            LEFT JOIN customlistlist_datacenter_formserv
                ON customlistlist_datacenter_formserv.id = customrecord830.custrecord_datactr_formserv
            WHERE customrecord830.custrecordcustrecord_clienfin_formserv = $id
            ORDER BY customrecord830.id
        ";

        $respForms = $netS->query($sqlForms, null, null);

        if (!empty($respForms['error'])) {
            Log::warning('⚠️ [NetSuite clientes] show forms error', [
                'id'     => $id,
                'status' => $respForms['status'] ?? null,
                'body'   => $respForms['body'] ?? null,
            ]);
            $forms = [];
        } else {
            $forms = collect($respForms['items'] ?? [])->map(function ($row) {
                return [
                    'id_formserv'     => $row['id_formserv'] ?? null,
                    'datacenter_id'   => $row['datacenter_id'] ?? null,
                    'datacenter_name' => $row['datacenter_name'] ?? null,
                ];
            })->values()->all();
        }

        // ✅ Respuesta API final
        return response()->json([
            'success' => true,
            'data' => [
                'cliente' => $cliente,
                'forms'   => $forms,
            ],
            'meta' => [
                'id' => $id,
            ],
        ]);
    }
}
