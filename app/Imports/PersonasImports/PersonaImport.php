<?php

namespace App\Imports\PersonasImports;

use App\Models\TPersona;
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
use Throwable; 

use App\Exceptions\Duplicidad;

class PersonaImport implements OnEachRow, WithHeadingRow,WithMultipleSheets, WithValidation, SkipsOnFailure, SkipsOnError, WithChunkReading, SkipsEmptyRows
{
        public function onUnknownSheet($sheetName)
    {
        throw new \Exception("La hoja '$sheetName' no existe en el archivo.");
    }
    private $idFabricante;
    protected $failures = [];
    protected $errors = [];

    public function __construct($idFabricante = null){
        $this->idFabricante = $idFabricante;
    }
    public function onRow(Row $row): void
    {
        $data = $row->toArray();
        $filaNumero = $row->getIndex();

        $idOperador = Auth::user()?->idOperador;

        $idPersona = $data['idpersona'] ?? null;

        // Si falta idPersona, saltar la fila
        if (empty($idPersona)) {
            return;
        }

        // Verificar duplicidad: idPersona + idFabricante
        if (TPersona::where('idPersona', $idPersona)
            ->where('idFabricante', $this->idFabricante)
            ->exists()) {
            throw new Duplicidad(
                "La persona con ID '{$idPersona}' ya existe para el fabricante {$this->idFabricante}.",
                $filaNumero
            );
        }

        $persona = new TPersona([
            'idOperador' => $idOperador,
            'idFabricante' => $this->idFabricante,
            'idPersona' => $idPersona,
            'idempresa_Grupo' => $data['idempresa_grupo'] ?? null,
            'idsupervisor' => $data['idsupervisor'] ?? null,
            'idpais' => $data['idpais'] ?? null,
            'idmoneda' => $data['idmoneda'] ?? null,
            'cod_tipo_persona' => $data['cod_tipo_persona'] ?? null,
            'idespecialidad' => $data['idespecialidad'] ?? null,
            'idactividad_negocio' => $data['idactividad_negocio'] ?? null,
            'idsubespecialidad' => $data['idsubespecialidad'] ?? null,
            'documento_identidad' => $data['documento_identidad'] ?? null,
            'idgrupo_persona' => $data['idgrupo_persona'] ?? null,
            'idclase_persona' => $data['idclase_persona'] ?? null,
            'idranking' => $data['idranking'] ?? null,
            'nombre_persona' => $data['nombre_persona'] ?? null,
            'apellido_persona' => $data['apellido_persona'] ?? null,
            'nombre_completo_razon_social' => $data['nombre_completo_razon_social'] ?? null,
            'idfrecuencia' => $data['idfrecuencia'] ?? null,
            'direccion_domicilio' => $data['direccion_domicilio'] ?? null,
            'idestado' => $data['idestado'] ?? null,
            'persona_contacto' => $data['persona_contacto'] ?? null,
            'idcadenas' => $data['idcadenas'] ?? null,
            'ididioma' => $data['ididioma'] ?? null,
            'sexo_genero_persona' => $data['sexo_genero_persona'] ?? null,
            'fecha_nacimiento_registro' => $data['fecha_nacimiento_registro'] ?? null,
            'telefono_persona' => $data['telefono_persona'] ?? null,
            'movil_persona' => $data['movil_persona'] ?? null,
            'email' => $data['email'] ?? null,
            'idtitulo' => $data['idtitulo'] ?? null,
            'idciclos' => $data['idciclos'] ?? null,
            'idciudad' => $data['idciudad'] ?? null,
            'zona_postal' => $data['zona_postal'] ?? null,
            'banco_persona' => $data['banco_persona'] ?? null,
            'cuenta_principal_persona' => $data['cuenta_principal_persona'] ?? null,
            'banco_persona_internacional' => $data['banco_persona_internacional'] ?? null,
            'cuenta_internacional_persona' => $data['cuenta_internacional_persona'] ?? null,
            'coordenadas_l' => $data['coordenadas_l'] ?? null,
            'coordenadas_a' => $data['coordenadas_a'] ?? null,
            'telefono_contacto' => $data['telefono_contacto'] ?? null,
            'email_contacto' => $data['email_contacto'] ?? null,
            'descuento' => $data['descuento'] ?? 0
        ]);

        $persona->save();
    }
    public function sheets():array{
        return[
            'RFV_Cliente_Mayorista' => $this
        ];
    }

    public function rules(): array{
        return [
            'idpersona' => ['required', 'string'],
            'idempresa_grupo' => ['required', 'string'],
            'idsupervisor' => ['required', 'string'],
            'idpais' => ['required'],
            'idmoneda' => ['required'],
            'cod_tipo_persona' => ['required'],
            'idespecialidad' => ['required', 'string'],
            'idactividad_negocio' => ['required', 'string'],
            'idsubespecialidad' => ['required', 'string'],
            'documento_identidad' => ['required', 'string'],
            'idgrupo_persona' => ['required', 'string'],
            'idclase_persona' => ['required'],
            'idranking' => ['required'],
            'nombre_persona' => ['required', 'string'],
            'apellido_persona' => ['required', 'string'],
            'nombre_completo_razon_social' => ['required', 'string'],
            'idfrecuencia' => ['required'],
            'direccion_domicilio' => ['required', 'string'],
            'idestado' => ['required'],
            'persona_contacto' => ['required', 'string'],
        ];
    }
        public function customValidationMessages()
    {
        return [
        'idpersona.required' => 'El campo ID de Persona es obligatorio.',
        'idempresa_grupo.required' => 'El campo Empresa Grupo es obligatorio.',
        'idsupervisor.required' => 'El campo Supervisor es obligatorio.',
        'idpais.required' => 'El campo País es obligatorio.',
        'idmoneda.required' => 'El campo Moneda es obligatorio.',
        'cod_tipo_persona.required' => 'El campo Tipo de Persona es obligatorio.',
        'idespecialidad.required' => 'El campo Especialidad es obligatorio.',
        'idactividad_negocio.required' => 'El campo Actividad de Negocio es obligatorio.',
        'idsubespecialidad.required' => 'El campo Subespecialidad es obligatorio.',
        'documento_identidad.required' => 'El campo Documento de Identidad es obligatorio.',
        'idgrupo_persona.required' => 'El campo Grupo Persona es obligatorio.',
        'idclase_persona.required' => 'El campo Clase Persona es obligatorio.',
        'idranking.required' => 'El campo Ranking es obligatorio.',
        'nombre_persona.required' => 'El campo Nombre es obligatorio.',
        'apellido_persona.required' => 'El campo Apellido es obligatorio.',
        'nombre_completo_razon_social.required' => 'El campo Razón Social es obligatorio.',
        'idfrecuencia.required' => 'El campo Frecuencia es obligatorio.',
        'direccion_domicilio.required' => 'El campo Dirección Domicilio es obligatorio.',
        'idestado.required' => 'El campo Estado es obligatorio.',
        'persona_contacto.required' => 'El campo Persona Contacto es obligatorio.',
        ];
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

