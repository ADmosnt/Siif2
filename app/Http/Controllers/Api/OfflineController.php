<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TPersona;
use App\Models\TProducto;
use App\Models\TTipoActividade;
use App\Models\TTipoIncidente;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class OfflineController extends Controller
{
    /**
     * El modo offline solo tiene sentido para RFV: son los unicos que
     * trabajan en campo sin conexion garantizada. Los demas roles
     * siempre operan con internet disponible.
     */
    private function assertOfflineEligible(TPersona $user): ?JsonResponse
    {
        if ($user->idgrupo_persona !== TPersona::TIPO_REPRESENTANTE) {
            return response()->json([
                'message' => 'El modo offline solo esta disponible para representantes (RFV).',
            ], 403);
        }

        return null;
    }

    public function masterData(): JsonResponse
    {
        $authUser = Auth::user();
        $user = TPersona::findOrFail($authUser->idPersona);

        if ($denegado = $this->assertOfflineEligible($user)) {
            return $denegado;
        }

        $idFabricante = $user->idFabricante;

        return response()->json([
            'clientes' => $this->getClientes($user),
            'productos' => $this->getProductos($idFabricante),
            'muestras' => $this->getMuestras($idFabricante),
            'mayoristas' => $this->getMayoristas($idFabricante),
            'representantes' => $this->getRepresentantes($idFabricante),
            'actividades' => $this->getActividades($idFabricante),
            'incidentes' => $this->getIncidentes($idFabricante),
            'timestamp' => now()->timestamp,
        ]);
    }

    /**
     * Emite un token de login offline firmado (HMAC), sin depender de la
     * contrasena real del usuario. El dispositivo guarda este token en
     * IndexedDB y lo usa para "iniciar sesion" localmente mientras no haya
     * internet; nunca se guarda la clave ni su hash en el navegador.
     */
    public function issueOfflineToken(): JsonResponse
    {
        $authUser = Auth::user();
        $user = TPersona::findOrFail($authUser->idPersona);

        if ($denegado = $this->assertOfflineEligible($user)) {
            return $denegado;
        }

        $ttlHours = (int) config('offline.token_ttl_hours', 12);
        $issuedAt = now()->timestamp;
        $expiresAt = now()->addHours($ttlHours)->timestamp;

        $payload = [
            'idPersona' => $user->idPersona,
            'name' => $authUser->name,
            'nombre_completo' => $user->nombre_completo_razon_social,
            'idFabricante' => $user->idFabricante,
            'idgrupo_persona' => $user->idgrupo_persona,
            'email' => $user->email ?? '',
            'issued_at' => $issuedAt,
            'expires_at' => $expiresAt,
        ];

        $token = $this->signPayload($payload);

        return response()->json([
            'token' => $token,
            'expires_at' => $expiresAt,
            'idPersona' => $user->idPersona,
            'name' => $authUser->name,
            'nombre_completo' => $user->nombre_completo_razon_social,
            'idFabricante' => $user->idFabricante,
            'idgrupo_persona' => $user->idgrupo_persona,
            'email' => $user->email ?? '',
        ]);
    }

    private function signPayload(array $payload): string
    {
        $encoded = $this->base64UrlEncode(json_encode($payload));
        $signature = hash_hmac('sha256', $encoded, (string) config('app.key'));

        return "{$encoded}.{$signature}";
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function getClientes(TPersona $user): array
    {
        if ($user->esRepresentante()) {
            $clientes = $user->Clientes()->get();
        } else {
            $clientes = TPersona::where('idgrupo_persona', 'CLI')
                ->where('idFabricante', $user->idFabricante)
                ->where('idestatus', 1)
                ->get();
        }

        // Solo lo estrictamente necesario para el selector de cliente en
        // Toma de Pedidos / Nuevo Reporte: id (FK) + nombre (display).
        // No se envian documento, telefono, direccion ni email: esos campos
        // no los usa ninguno de los dos flujos offline.
        return $clientes->map(fn(TPersona $c) => [
            'id' => $c->idPersona,
            'nombre' => $c->nombre_completo_razon_social,
        ])->toArray();
    }

    private function getProductos(string $idFabricante): array
    {
        return TProducto::where('idfabricante', $idFabricante)
            ->where('estatus_producto', 1)
            ->where('idcategorias', '!=', 'MUES')
            ->get()
            ->map(fn(TProducto $p) => [
                'id' => $p->idproducto,
                'codigo' => $p->idproducto,
                'nombre' => $p->nombre_producto,
                'precio' => (float) $p->Precio_producto,
                'lote' => $p->lote ?? '',
                'categoria' => 'PROD',
            ])->toArray();
    }

    private function getMuestras(string $idFabricante): array
    {
        return TProducto::where('idfabricante', $idFabricante)
            ->where('estatus_producto', 1)
            ->where('idcategorias', 'MUES')
            ->get()
            ->map(fn(TProducto $p) => [
                'id' => $p->idproducto,
                'codigo' => $p->idproducto,
                'nombre' => $p->nombre_producto,
                'precio' => (float) $p->Precio_producto,
                'lote' => $p->lote ?? '',
                'categoria' => 'MUES',
            ])->toArray();
    }

    private function getMayoristas(string $idFabricante): array
    {
        return TPersona::where('idgrupo_persona', 'MAY')
            ->where('idFabricante', $idFabricante)
            ->where('idestatus', 1)
            ->get()
            ->map(fn(TPersona $m) => [
                'id' => $m->idPersona,
                'nombre' => $m->nombre_completo_razon_social,
            ])->toArray();
    }

    private function getRepresentantes(string $idFabricante): array
    {
        return TPersona::where('idgrupo_persona', 'RFV')
            ->where('idFabricante', $idFabricante)
            ->where('idestatus', 1)
            ->get()
            ->map(fn(TPersona $r) => [
                'id' => $r->idPersona,
                'nombre' => $r->nombre_completo_razon_social,
            ])->toArray();
    }

    private function getActividades(string $idFabricante): array
    {
        return TTipoActividade::where('idestatus', 1)
            ->where('idfabricante', $idFabricante)
            ->get()
            ->map(fn($a) => [
                'idtipo_actividad' => $a->idtipo_actividades,
                'descripcionActividad' => $a->descripcion_tipo_actividades,
            ])->toArray();
    }

    private function getIncidentes(string $idFabricante): array
    {
        return TTipoIncidente::where('idestatus', 1)
            ->where('idfabricante', $idFabricante)
            ->get()
            ->map(fn($i) => [
                'idtipo_incidentes' => $i->idtipo_incidentes,
                'descripcionIncidente' => $i->descripcion_tipo_incidentes,
            ])->toArray();
    }
}
