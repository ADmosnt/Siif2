<?php

namespace App\Services;

use App\Models\TProducto;
use App\Models\TLineaProducto;
use App\Models\TTipoProducto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProductoAdminService
{
    public function __construct(
        protected CompanyContextService $contextService
    ) {}

    /**
     * Filtra y pagina los datos respetando la empresa seleccionada y el tipo de recurso
     */
    public function listarProductoPaginado(string $slugTipo, $request)
    {
        $activeId = $this->contextService->getActiveId();
        $perPage = $request->size ?? 15;
        $search = $request->search;

        if (in_array($slugTipo, ['lineas_productos', 'tipos_productos'])) {
            if ($slugTipo === 'lineas_productos') {
                return TLineaProducto::where('idFabricante', $activeId)
                    ->where('idestatus', 1)
                    ->when($search, function ($q) use ($search) {
                        $q->where('descripcion_linea_producto', 'like', "%{$search}%")
                          ->orWhere('id', 'like', "%{$search}%");
                    })
                    ->paginate($perPage);
            } else {
                return TTipoProducto::withoutGlobalScopes()
                    ->where('idFabricante', $activeId)
                    ->where('idestatus', 1)
                    ->when($search, function ($q) use ($search) {
                        $q->where('descripcion_tipo_producto', 'like', "%{$search}%")
                          ->orWhere('idtipo_producto', 'like', "%{$search}%");
                    })
                    ->paginate($perPage);
            }
        }

        // FLUJO TRADICIONAL: Muestras y Productos (Tabla t_productos)
        $query = TProducto::query();

        if ($slugTipo === 'muestras') {
            $query->where('idcategorias', 'MUES');
        } elseif ($slugTipo === 'productos') {
            $query->where('idcategorias', 'PROD');
        }

        if ($activeId) {
            $query->where('idfabricante', $activeId);
        }

        if ($search) {
            $query->where(fn($q) => 
                $q->where('nombre_producto', 'like', "%$search%")
                  ->orWhere('idproducto', 'like', "%$search%")
            );
        }

        return $query->paginate($perPage);
    }

    /**
     * Crea un nuevo registro encauzándolo al modelo correcto
     */
    public function crearProducto(string $tipo, array $data)
    {
        if (in_array($tipo, ['lineas_productos', 'tipos_productos'])) {
            return $this->crearRecurso($tipo, $data);
        }

        $user = Auth::user();
        return DB::transaction(function () use ($tipo, $data, $user) {
            $idFabricante = $this->contextService->getActiveId();
            $idOperador   = $this->contextService->getActiveOperador();
            
            $idProductoBase = $data['id'] ?? '';
            $idProductoFull = $idFabricante . $idProductoBase;

            $datosMapeados = $this->mapearDatosProductos($data, [
                'idFabricante' => $idFabricante,
                'idOperador'   => $idOperador,
                'idPersona'    => $user->idPersona,
            ]);

            $datosMapeados['idcategorias'] = ($tipo === 'muestras') ? 'MUES' : 'PROD';
            $datosMapeados['idproducto'] = $idProductoFull;

            return TProducto::create($datosMapeados);
        });
    }

    /**
     * Actualiza un registro existente según su tipo
     */
    public function actualizarProducto(string $tipo, $id, array $data)
    {
        if (in_array($tipo, ['lineas_productos', 'tipos_productos'])) {
            return $this->actualizarRecurso($tipo, $id, $data);
        }

        $producto = TProducto::findOrFail($id);
        $updateData = $this->mapearDatosProductos($data);

        return $producto->update($updateData);
    }

    /**
     * Eliminación lógica pasando el tipo de recurso
     */
    public function eliminarProducto(string $tipo, $id)
    {
        if (in_array($tipo, ['lineas_productos', 'tipos_productos'])) {
            return $this->eliminarRecurso($tipo, $id);
        }

        return TProducto::where('idproducto', $id)->update(['estatus_producto' => 0]);
    }
    
    private function limpiarCombobox($campo)
    {
        if (is_array($campo) && array_key_exists('value', $campo)) {
            return $campo['value'];
        }
        return $campo;
    }

    private function mapearDatosProductos(array $data, array $systemValues = []): array
    {
        $result = [
            'nombre_producto'             => $data['producto'] ?? null,
            'Precio_producto'             => $data['precio'] ?? 0,
            'descripcion_producto'        => trim(($data['producto'] ?? '')),
            'Descuento_producto'          => $data['descuento'] ?? 0,
            'cantidad_producto_existente' => $data['existencia'] ?? null,
            'lote'                        => $data['lote'] ?? null,
            'fechaVencimiento_producto'   => $data['fecha_vencimiento'] ?? null,
            'fechaRegistro_producto'      => $data['fecha_registro'] ?? null,
            'presentacion'                => $data['presentacion'] ?? null,

            // Limpieza de Combobox
            'idlinea_producto'            => $this->limpiarCombobox($data['linea'] ?? null),
            'idtipo_producto'             => $this->limpiarCombobox($data['tipo_producto'] ?? null),
            'idMayorista'                 => $this->limpiarCombobox($data['mayorista'] ?? null),
        ];
        
        if (!empty($systemValues)) {
            $result = array_merge($result, [
                'idfabricante'            => $systemValues['idFabricante'] ?? null,
                'idOperador'              => $systemValues['idOperador'] ?? null,
                'idPersona'               => $systemValues['idPersona'] ?? null,
            ]);
        }

        return $result;
    }

    /**
     * Métodos auxiliares de procesamiento interno para sub-recursos
     */
    private function crearRecurso(string $tipo, array $data)
    {
        return DB::transaction(function () use ($tipo, $data) {
            $idOperador   = auth()->user()->idOperador ?? '01';
            $idFabricante = $this->contextService->getActiveId();

            if ($tipo === 'lineas_productos') {
                return TLineaProducto::create([
                    'id'                         => $data['id'],
                    'idOperador'                 => $idOperador,
                    'idFabricante'               => $idFabricante,
                    'descripcion_linea_producto' => $data['descripcion_linea_producto'],
                    'idestatus'                  => 1,
                    'alias'                      => $data['id']
                ]);
            }

            if ($tipo === 'tipos_productos') {
                return TTipoProducto::create([
                    'idOperador'                => $idOperador,
                    'idtipo_producto'           => $data['idtipo_producto'],
                    'idFabricante'              => $idFabricante,
                    'descripcion_tipo_producto' => $data['descripcion_tipo_producto'],
                    'idestatus'                 => 1
                ]);
            }
        });
    }

    private function actualizarRecurso(string $tipo, $id, array $data)
    {
        if ($tipo === 'lineas_productos') {
            return TLineaProducto::where('id', $id)->update([
                'descripcion_linea_producto' => $data['descripcion_linea_producto']
            ]);
        }

        if ($tipo === 'tipos_productos') {
            return TTipoProducto::withoutGlobalScopes()
                ->where('idtipo_producto', $id)
                ->update([
                    'descripcion_tipo_producto' => $data['descripcion_tipo_producto']
                ]);
        }
    }

    private function eliminarRecurso(string $tipo, $id)
    {
        if ($tipo === 'lineas_productos') {
            return TLineaProducto::where('id', $id)->update(['idestatus' => 0]);
        }

        if ($tipo === 'tipos_productos') {
            return TTipoProducto::withoutGlobalScopes()
                ->where('idtipo_producto', $id)
                ->update(['idestatus' => 0]);
        }
    }
}