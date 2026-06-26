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
    public function masterData(): JsonResponse
    {
        $authUser = Auth::user();
        $user = TPersona::findOrFail($authUser->idPersona);
        $idFabricante = $user->idFabricante;

        return response()->json([
            'clientes' => $this->getClientes($user),
            'productos' => $this->getProductos($idFabricante),
            'muestras' => $this->getMuestras($idFabricante),
            'mayoristas' => $this->getMayoristas($idFabricante),
            'representantes' => $this->getRepresentantes($idFabricante),
            'supervisores' => $this->getSupervisores($idFabricante),
            'gerentes' => $this->getGerentes($idFabricante),
            'actividades' => $this->getActividades($idFabricante),
            'incidentes' => $this->getIncidentes($idFabricante),
            'timestamp' => now()->timestamp,
        ]);
    }

    public function cacheAuth(): JsonResponse
    {
        $authUser = Auth::user();
        $user = TPersona::findOrFail($authUser->idPersona);

        return response()->json([
            'idPersona' => $user->idPersona,
            'name' => $authUser->name,
            'password_hash' => $authUser->password,
            'nombre_completo' => $user->nombre_completo_razon_social,
            'idFabricante' => $user->idFabricante,
            'idgrupo_persona' => $user->idgrupo_persona,
            'email' => $user->email ?? '',
        ]);
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

        return $clientes->map(fn(TPersona $c) => [
            'id' => $c->idPersona,
            'nombre' => $c->nombre_completo_razon_social,
            'documento' => $c->documento_identidad,
            'telefono' => $c->telefono_persona ?? $c->movil_persona,
            'direccion' => $c->direccion_domicilio,
            'email' => $c->email,
            'ranking' => $c->idranking,
            'frecuencia' => $c->idfrecuencia,
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
                'existencia' => (int) $p->cantidad_producto_existente,
                'linea' => $p->idlinea_producto,
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
                'existencia' => (int) $p->cantidad_producto_existente,
                'linea' => $p->idlinea_producto,
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

    private function getSupervisores(string $idFabricante): array
    {
        return TPersona::where('idgrupo_persona', 'SUP')
            ->where('idFabricante', $idFabricante)
            ->where('idestatus', 1)
            ->get()
            ->map(fn(TPersona $s) => [
                'id' => $s->idPersona,
                'nombre' => $s->nombre_completo_razon_social,
            ])->toArray();
    }

    private function getGerentes(string $idFabricante): array
    {
        return TPersona::where('idgrupo_persona', 'GRT')
            ->where('idFabricante', $idFabricante)
            ->where('idestatus', 1)
            ->get()
            ->map(fn(TPersona $g) => [
                'id' => $g->idPersona,
                'nombre' => $g->nombre_completo_razon_social,
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
