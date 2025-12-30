<?php

namespace App\Http\Controllers\Operaciones;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Operaciones\Guardias;

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
    public function index()
    {
        $guardias = Guardias::with('user')
            ->orderByDesc('dateInit')
            ->get();

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
