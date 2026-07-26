<?php

namespace App\Imports\VisitasImports;

use App\Models\TTmpPlanificadore;
use App\Models\RClienteRfv;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

class VisitaMasivaImport implements OnEachRow, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsOnError
{
    protected array $failures = [];
    protected array $errors = [];
    protected int $importados = 0;

    public function onRow(Row $row): void
    {
        $data = $row->toArray();
        $user = Auth::user();

        $idRFV = trim($data['idrfv'] ?? '');
        $idCliente = trim($data['idcliente'] ?? '');
        $fecha = trim($data['fecha'] ?? '');
        $hora = trim($data['hora'] ?? '');

        if (empty($idRFV) || empty($idCliente) || empty($fecha) || empty($hora)) {
            return;
        }

    try {
        // Verificar si la fecha viene como el número de serie de Excel
        if (is_numeric($fecha)) {
            // Excel cuenta desde 1900-01-01. Convertimos ese número a una fecha real.
            $fechaParsed = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($fecha))
                ->format('Y-m-d');
        } else {
            // Si viene como string estándar ('2026-05-16' o '16/05/2026')
            $fechaParsed = Carbon::parse($fecha)->format('Y-m-d');
        }
    } catch (\Exception $e) {
        $this->errors[] = "Fila {$row->getIndex()}: Fecha inválida '{$fecha}'";
        return;
    }

        try {
            $horaParsed = Carbon::parse($hora)->format('H:i:s');
        } catch (\Exception $e) {
            $horaParsed = $hora . ':00';
        }

        if (!RClienteRfv::where('id_RFV', $idRFV)->where('id_cliente', $idCliente)->exists()) {
            $this->errors[] = "Fila {$row->getIndex()}: Cliente '{$idCliente}' no asociado al RFV '{$idRFV}'";
            return;
        }

        $duplicado = TTmpPlanificadore::where('idCliente', $idCliente)
            ->where('Fecha', $fechaParsed)
            ->where('Hora', $horaParsed)
            ->exists();

        if ($duplicado) {
            $this->errors[] = "Fila {$row->getIndex()}: Ya existe visita para '{$idCliente}' el {$fechaParsed} a las {$horaParsed}";
            return;
        }

        TTmpPlanificadore::create([
            'idOperador' => $user->idOperador,
            'idFabricante' => $user->idFabricante,
            'idRFV' => $idRFV,
            'idCliente' => $idCliente,
            'Fecha' => $fechaParsed,
            'Hora' => $horaParsed,
            'idSupervisor' => $user->idgrupo_persona === 'RFV' ? $user->idsupervisor : $user->idPersona,
            'idstatus' => 1,
            'idCreador' => $user->idPersona,
            'idstatus' => TTmpPlanificadore::ESTATUS_TEMPORAL,
        ]);

        $this->importados++;
    }

    public function rules(): array
    {
        return [
            'idrfv' => 'required|string',
            'idcliente' => 'required|string',
            'fecha' => 'required',
            'hora' => 'required',
        ];
    }

    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            $this->failures[] = $failure;
        }
    }

    public function onError(Throwable $e): void
    {
        $this->errors[] = $e->getMessage();
    }

    public function getFailures(): array
    {
        return $this->failures;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getImportados(): int
    {
        return $this->importados;
    }
}
