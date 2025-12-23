<?php

namespace App\Http\Controllers\Operaciones;

use App\Http\Controllers\Controller;
use App\Models\ClienteVeeam;
use Illuminate\Http\Request;

class ClienteVeeamController extends Controller
{
   
    public function index(Request $request)
    {
        $search   = trim((string) $request->query('search', ''));
        $inactive = $request->query('inactive', null); 
        $perPage  = 30;

        $query = ClienteVeeam::query();

        if ($inactive === '0') {
            $query->where('activo', 1);
        } elseif ($inactive === '1') {
            $query->where('activo', 0);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                if (ctype_digit($search)) {
                    $q->orWhere('numCV', $search);
                }

                $q->orWhere('numCV', 'like', "%{$search}%")
                ->orWhere('nameCV', 'like', "%{$search}%")
                ->orWhere('app', 'like', "%{$search}%")
                ->orWhere('backup', 'like', "%{$search}%");
                });
        }

        $clientes = $query
            ->orderBy('id')
            ->paginate($perPage);

        return response()->json($clientes);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ClienteVeeam $clienteVeeam)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ClienteVeeam $clienteVeeam)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ClienteVeeam $clienteVeeam)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ClienteVeeam $clienteVeeam)
    {
        //
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
}
