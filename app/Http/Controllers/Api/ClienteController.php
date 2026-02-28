<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TPersona;
use App\Models\RClienteRfv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ClienteController extends Controller
{
    /**
     * Lista los clientes del representante autenticado.
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->esRepresentante()) {
            $clientes = $user->Clientes()->get(['idPersona', 'nombre_completo_razon_social']);
        } elseif ($user->esSupervisor()) {
            $clientes = TPersona::where('idgrupo_persona', 'CLI')
                ->where('idFabricante', $user->idFabricante)
                ->get(['idPersona', 'nombre_completo_razon_social']);
        } else {
            $clientes = TPersona::where('idgrupo_persona', 'CLI')
                ->where('idFabricante', $user->idFabricante)
                ->get(['idPersona', 'nombre_completo_razon_social']);
        }

        return response()->json(['clientes' => $clientes]);
    }

    /**
     * Busca un cliente por su ID.
     */
    public function searchById(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Data inválida'], 400);
        }

        $cliente = TPersona::find($request->id);
        if (!$cliente) {
            return response()->json(['error' => 'Cliente no encontrado'], 404);
        }

        return response()->json($cliente);
    }

    /**
     * Busca clientes por nombre.
     */
    public function searchByName(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search' => 'required|string|min:2',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = Auth::user();
        $search = $request->search;

        if ($user->esRepresentante()) {
            $clientes = $user->Clientes()
                ->where('nombre_completo_razon_social', 'like', "%{$search}%")
                ->get(['idPersona', 'nombre_completo_razon_social']);
        } else {
            $clientes = TPersona::where('idgrupo_persona', 'CLI')
                ->where('idFabricante', $user->idFabricante)
                ->where('nombre_completo_razon_social', 'like', "%{$search}%")
                ->get(['idPersona', 'nombre_completo_razon_social']);
        }

        return response()->json($clientes);
    }

    /**
     * Obtiene los materiales/productos asignados a un cliente.
     */
    public function materiales(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Data inválida'], 400);
        }

        $cliente = TPersona::find($request->id);
        if (!$cliente) {
            return response()->json(['error' => 'Cliente no encontrado'], 404);
        }

        $productos = $cliente->Materiales()->get();

        foreach ($productos as $p) {
            $p->idproducto = $p->pivot->idproducto;
        }

        return response()->json(['materiales' => $productos]);
    }
}
