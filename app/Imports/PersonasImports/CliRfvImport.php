<?php

namespace App\Imports\PersonasImports;

use App\Exceptions\Duplicidad;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Facades\Auth;
use App\Models\TPersona;
use App\Models\RClienteRfv;
use Illuminate\Support\Facades\Log;
use Throwable;


class CliRfvImport implements OnEachRow, WithHeadingRow, WithMultipleSheets, WithValidation, SkipsOnError, SkipsOnFailure, WithChunkReading
{
    private $idFabricante;
    protected $failures = [];
    protected $errors = [];

    public function __construct($idFabricante = null){
        $this->idFabricante = $idFabricante;
    }

    public function onUnknownSheet($sheetName)
    {
        throw new \Exception("La hoja '$sheetName' no existe en el archivo.");
    }

public function onRow(Row $row): void
{
    $data = $row->toArray();
    $filaNumero = $row->getIndex();

    $idOperador = Auth::user()?->idOperador;
    $clienteId = $data['id_cliente'] ?? $data['A'] ?? null;
    $rfvId = $data['id_rfv'] ?? $data['B'] ?? null;

    // Validar que los IDs existan
    if (empty($clienteId) || empty($rfvId)) {
        return; // saltar fila
    }

    // Verificar si la relación ya existe
    if (RClienteRfv::where('id_cliente', $clienteId)
            ->where('id_RFV', $rfvId)
            ->exists()) {
        throw new Duplicidad(
            "La relación entre cliente '$clienteId' y RFV '$rfvId' ya existe.",
            $filaNumero
        );
    }

    // Buscar cliente
    $cliente = TPersona::withoutGlobalScopes()->find($clienteId);
    if (!$cliente) {
        throw new \Exception("Cliente con ID '$clienteId' no encontrado.");
    }

    // Crear y guardar la relación
    $rel = new RClienteRfv([
        'idOperador' => $idOperador,
        'idFabricante' => $this->idFabricante,
        'id_cliente' => $clienteId,
        'id_RFV' => $rfvId,
        'idprofesion' => $cliente->idespecialidad ?? null,
        'idespecialidad' => $cliente->idactividad_negocio ?? null,
        'idsubespecialidad' => $cliente->idsubespecialidad ?? null,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    $rel->save();
}

    public function sheets(): array
    {
        return [
            'Cliente_RFV' => $this
        ];
    }

    public function rules(): array
    {
        return [
            'id_cliente' => ['required', 'exists:t_personas,idPersona'],
            'id_rfv' => ['required', 'exists:t_personas,idPersona'],
        ];
    }

    public function customValidationMessages()
    {
        return [
            'id_cliente.required' => 'El campo Cliente es obligatorio.',
            'id_cliente.exists' => 'El Cliente seleccionado no existe.',
            'id_rfv.required' => 'El campo RFV es obligatorio.',
            'id_rfv.exists' => 'El RFV seleccionado no existe.',
        ];
    }

    public function onFailure(Failure ...$failures)
    {
        $this->failures = array_merge($this->failures, $failures);
    }

    public function getFailures()
    {
        return $this->failures;
    }

    public function onError(Throwable $e)
    {
        $this->errors[] = $e;
        Log::error("Error en importación CliRfv: " . $e->getMessage());
    }

    public function getErrors()
    {
        return $this->errors;
    }

        public function chunkSize(): int
    {
        return 100; // Procesa 100 filas a la vez
    }
}