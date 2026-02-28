<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\TPersona;
use App\Http\Controllers\Controller;

class ApiAuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'name' => 'required|string',
            'password' => 'required|string',
        ], [
            'required' => 'El :attribute es requerido',
        ]);

        $user = TPersona::where('name', $credentials['name'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['error' => 'Credenciales inválidas'], 401);
        }

        // Revocar tokens anteriores para este dispositivo
        $user->tokens()->delete();

        $token = $user->createToken('mobile_app')->plainTextToken;

        Log::info('Login API exitoso', ['user' => $user->name, 'idPersona' => $user->idPersona]);

        return response()->json([
            'token' => $token,
            'name' => $user->name,
            'nombre' => $user->nombre_completo_razon_social,
            'idPersona' => $user->idPersona,
            'idFabricante' => $user->idFabricante,
            'idOperador' => $user->idOperador,
            'grupo' => $user->idgrupo_persona,
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(null, 204);
        }

        $user->currentAccessToken()->delete();

        Log::info('Logout API exitoso', ['user' => $user->name]);

        return response()->json(['message' => 'Sesión cerrada correctamente']);
    }

    public function profile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'idPersona' => $user->idPersona,
            'name' => $user->name,
            'nombre' => $user->nombre_completo_razon_social,
            'email' => $user->email,
            'telefono' => $user->telefono_persona,
            'movil' => $user->movil_persona,
            'grupo' => $user->idgrupo_persona,
            'idFabricante' => $user->idFabricante,
            'idOperador' => $user->idOperador,
        ]);
    }
}