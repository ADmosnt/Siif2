<?php

namespace App\Services;

use App\Models\TProducto;
use Illuminate\Support\Facades\DB;

class ProductoAdminService
{
    public function __construct(
        protected CompanyContextService $contextService
    ) {}


    /**
     * Filtra y pagina los datos respetando la empresa seleccionada
     */
    public function listarProductoPaginado(string $slugTipo, $request)
    {
        $activeId = $this->contextService->getActiveId();
        $query = TProducto::query();

        if ($slugTipo === 'muestras') {
            $query->where('idcategorias', 'MUES');
        } if ($slugTipo === 'productos-lista') {

            $query->where('idcategorias', 'PROD');
        }

        if ($activeId) {
            $query->where('idfabricante', $activeId);
        }

        if ($search = $request->search) {
            $query->where(fn($q) => 
                $q->where('nombre_producto', 'like', "%$search%")
                  ->orWhere('idproducto', 'like', "%$search%")
            );
        }

        return $query->paginate($request->size ?? 15);
    }

    public function crearProducto(string $tipo, array $data)
    {
    return DB::transaction(function () use ($tipo, $data) {
        $idFabricante = $this->contextService->getActiveId();
        $idOperador   = $this->contextService->getActiveOperador();
        
        // Se genera el id de producto combinando Fabricante + Código ingresado

        $idProductoBase = $data['id'] ?? '';
        $idPersonaFull = $idFabricante . $idProductoBase;

        $datosMapeados = $this->mapearDatosProductos($data, [
            'idFabricante' => $idFabricante,
            'idOperador'   => $idOperador,
            'idPersona'    => $idPersonaFull,
        ]);

        // Asigna la categoría según el slug de la ruta
        $datosMapeados['idcategorias'] = ($tipo === 'muestras') ? 'MUES' : 'PROD';
        $datosMapeados['idproducto'] = $idProductoBase;

        return TProducto::create($datosMapeados);
        });
    }

    public function actualizarProducto($id, array $data)
    {
        $producto = TProducto::findOrFail($id);
        $updateData = $this->mapearDatosProductos($data);

        return $producto->update($updateData);
    }

    public function eliminarProducto($id)
    {
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
            'nombre_producto'    => $data['producto'] ?? null,
            'Precio_producto'  => $data['precio'] ?? 0,
            'descripcion_producto' => trim(($data['producto'] ?? '')),
            'Descuento_producto' => $data['descuento'] ?? 0,
            'cantidad_producto_existente' => $data['existencia'] ?? null,
            'lote' => $data['lote'] ?? null,
            'fechaVencimiento_producto' => $data['fecha_vencimiento'] ?? null,
            'fechaRegistro_producto' => $data['fecha_registro'] ?? null,
            'presentacion' => $data['presentacion'] ?? null,

            // Limpieza de Combobox
            'idlinea_producto'  => $this->limpiarCombobox($data['linea'] ?? null),
            'idtipo_producto'   => $this->limpiarCombobox($data['tipo_producto'] ?? null),
            'idMayorista'       => $this->limpiarCombobox($data['mayorista'] ?? null),
        ];
        
        // Sólo añadir los valores del sistema si se proporcionaron (creación)
        if (!empty($systemValues)) {
            $result = array_merge($result, [
                'idfabricante'      => $systemValues['idFabricante'] ?? null,
                'idOperador'        => $systemValues['idOperador'] ?? null,
                'idPersona'         => $systemValues['idPersona'] ?? null,
            ]);
        }

        return $result;
    }
}
