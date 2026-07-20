<?php

namespace App\Imports\ProductosImports;

use App\Models\TProducto;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;
use Throwable; 

use App\Exceptions\Duplicidad;

class ProductosImport implements OnEachRow, WithHeadingRow,WithMultipleSheets, WithValidation, SkipsOnFailure, SkipsOnError, WithChunkReading, SkipsEmptyRows
{
    public function onUnknownSheet($sheetName){
        throw new \Exception("La hoja '$sheetName' no existe en el archivo.");
    }

    private $idFabricante;
    private $idPersona;
    protected $failures = [];
    protected $errors = [];

    public function __construct($idFabricante, $idPersona)
    {
        $this->idFabricante = $idFabricante;
        $this->idPersona = $idPersona;
    }

    public function onRow(Row $row): void
    {
        $data = $row->toArray();
        $filaNumero = $row->getIndex();

        // Helper para convertir fechas de Excel
        $parseDate = function ($value) {
            if ($value === null || $value === '') {
                return null;
            }
            // Si es un número (fecha serial de Excel)
            if (is_numeric($value)) {
                try {
                    $date = Date::excelToDateTimeObject($value);
                    return $date->format('Y-m-d');
                } catch (\Exception $e) {
                    return null;
                }
            }
            // Si ya es una fecha en formato string (ej: "2018-03-01")
            try {
                return Carbon::parse($value)->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        };

        $idOperador = Auth::user()?->idOperador;

        $idproducto = $data['idproducto'] ?? null;
        $idmayorista = $data['idmayorista'] ?? null;

        // Validar campos mínimos: si faltan identificador o mayorista, saltamos
        if (empty($idproducto) || empty($idmayorista)) {
            return;
        }

        // Verificar duplicidad: el criterio asumido es idproducto + idfabricante
        if (TProducto::where('idproducto', $idproducto)
            ->where('idfabricante', $this->idFabricante)
            ->exists()) {
            throw new Duplicidad(
                "El producto con identificador '{$idproducto}' ya existe para el fabricante {$this->idFabricante}.",
                $filaNumero
            );
        }

        $producto = new TProducto([
            'idOperador' => $idOperador,
            'idfabricante' => $this->idFabricante,
            'idPersona' => $this->idPersona,
            'idproducto' => $idproducto,
            'idMayorista' => $idmayorista,
            'idlinea_producto' => $data['idlinea_producto'] ?? null,
            'idtipo_producto' => $data['idtipo_producto'] ?? null,
            'idcategorias' => $data['idcategorias'] ?? null,
            'nombre_producto' => $data['nombre_producto'] ?? null,
            'descripcion_producto' => $data['descripcion_producto'] ?? null,
            'principio_producto' => $data['principio_producto'] ?? null,
            'Precio_producto' => $data['precio_producto'] ?? null,
            'Descuento_producto' => $data['descuento_producto'] ?? null,
            'presentacion' => $data['presentacion'] ?? null,
            'cantidad_producto_existente' => $data['cantidad_producto_existente'] ?? null,
            'fechaRegistro_producto' => now(),
            'fechaExpedicion_producto' => $parseDate($data['fechaexpedicion_producto'] ?? null),
            'fechaVencimiento_producto' => $parseDate($data['fechavencimiento_producto'] ?? null),
            'estatus_producto' => 1
        ]);

        $producto->save();
    }

    public function rules(): array
    {
        return [
            'idproducto' => ['required', 'string', 'max:255'],
            'idmayorista' => ['required', 'exists:t_personas,idPersona'],
            'idlinea_producto' => ['required', 'exists:t_linea_productos,id'],
            'idtipo_producto' => ['required', 'exists:t_tipo_productos,idtipo_producto'],
            'idcategorias' => ['required', 'exists:t_categorias,idcategorias'],
            'nombre_producto' => ['required', 'string', 'max:100'],
            'descripcion_producto' => ['required', 'string', 'max:1000'],
            'principio_producto' => ['required', 'string', 'max:1000'],
            'precio_producto' => ['required', 'numeric'],
            'descuento_producto' => ['nullable', 'numeric'],
            'presentacion' => ['required', 'string', 'max:20'],
            'cantidad_producto_existente' => ['required', 'integer'],
        ];
    }

        public function customValidationMessages()
    {
        return [
            'idproducto.required' => 'El campo Identificador es obligatorio.',
            'idmayorista.required' => 'El campo Mayorista es obligatorio.',
            'idmayorista.exists' => 'El mayorista seleccionado no existe.',
            'idlinea_producto.required' => 'El campo Línea es obligatorio.',
            'idlinea_producto.exists' => 'La línea seleccionada no existe.',
            'idtipo_producto.required' => 'El campo Tipo es obligatorio.',
            'idtipo_producto.exists' => 'El tipo seleccionado no existe.',
            'idcategorias.required' => 'El campo Categoría es obligatorio.',
            'idcategorias.exists' => 'La categoría seleccionada no existe.',
            'nombre_producto.required' => 'El campo Nombre es obligatorio.',
            'descripcion_producto.required' => 'El campo Descripción es obligatorio.',
            'principio_producto.required' => 'El campo Principio es obligatorio.',
            'precio_producto.required' => 'El campo Precio es obligatorio.',
            'precio_producto.numeric' => 'El campo Precio debe ser numérico.',
            'descuento_producto.numeric' => 'El campo Descuento debe ser numérico.',
            'presentacion.required' => 'El campo Presentación es obligatorio.',
            'cantidad_producto_existente.required' => 'El campo Cantidad es obligatorio.',
            'cantidad_producto_existente.integer' => 'El campo Cantidad debe ser un número entero.',
            'fechaexpedicion_producto.required' => 'El campo Fecha de Expedición es obligatorio.',
            'fechaexpedicion_producto.date' => 'El campo Fecha de Expedición debe ser una fecha válida.',
            'fechavencimiento_producto.required' => 'El campo Fecha de Vencimiento es obligatorio.',
            'fechavencimiento_producto.date' => 'El campo Fecha de Vencimiento debe ser una fecha válida.',
        ];
    }

    public function sheets(): array
    {
        return ['Productos' => $this];
    }

        public function onFailure(Failure ...$failures){
    $this->failures = array_merge($this->failures, $failures);
    }

    public function getFailures(){
        return $this->failures;
    }

    public function onError(Throwable $e){
        $this->errors[] = $e;
    }

    public function getErrors(){
        return $this->errors;
    }
        public function chunkSize(): int
    {
        return 100; // Procesa 100 filas a la vez
    }
}


