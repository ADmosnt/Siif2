<?php
//app/Services/PersonaAdminService.php
namespace App\Services;

use App\Models\TPersona;
use App\Models\TPerfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PersonaAdminService
{
    public function __construct(
        protected CompanyContextService $contextService
    ) {}

    /**
     * Filtra y pagina los datos respetando la empresa seleccionada
     */
    public function listarPaginado(string $slugTipo, $request)
    {
        $idGrupo = $this->mapTipoToGrupo($slugTipo);
        $activeId = $this->contextService->getActiveId();

        $query = TPersona::query()
            ->where('idgrupo_persona', $idGrupo)
            ->where('idestatus', 1);

        // Seguridad: Si no es SIIF, siempre filtrar por su idFabricante
        if ($activeId) {
            $query->where('idFabricante', $activeId);
        }

        if ($search = $request->search) {
            $query->where(fn($q) => 
                $q->where('nombre_persona', 'like', "%$search%")
                  ->orWhere('apellido_persona', 'like', "%$search%")
                  ->orWhere('idPersona', 'like', "%$search%")
            );
        }

        return $query->paginate($request->size ?? 15);
    }

    /**
     * Lógica de creación (El Observer se dispara al final)
     */
    public function crearPersona(string $tipo, array $data)
    {

        return DB::transaction(function () use ($tipo, $data) {
            $idFabricante = $this->contextService->getActiveId();
            $idOperador   = $this->contextService->getActiveOperador();
            
            $idGrupo = $this->mapTipoToGrupo($tipo);
            $idPersona = $idFabricante . ($data['documento'] ?? '');
            
            $datosMapeados = $this->mapearDatosPersona($data, [
                    'idgrupo_persona' => $idGrupo,
                    'idFabricante'    => $idFabricante,
                    'idOperador'      => $idOperador,
                    'idPersona'       => $idPersona
                ]);

            // Manejo de contraseña para roles de sistema
            if ($this->esRolConAcceso($idGrupo) && !empty($data['password'])) {
                $datosMapeados['password'] = Hash::make($data['password']);
                $datosMapeados['idperfil'] = $this->obtenerIdPerfil($idGrupo);
            }

            return TPersona::create($datosMapeados);
        });
    }

    /**
     * Lógica de actualización (Solo cambia contraseña si se envía una nueva)
     */
    public function actualizarPersona($id, array $data)
    {
        $persona = TPersona::findOrFail($id);
        $updateData = $this->mapearDatosPersona($data);

        // Si se envió una contraseña nueva, se encripta. Si no, se ignora.
        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        return $persona->update($updateData);
    }

    public function eliminarPersona($id)
    {
        return TPersona::where('idPersona', $id)->update(['idestatus' => 0]);
    }

    public function toggleFabricanteStatus(string $idFabricante): array
    {
        $personas = TPersona::where('idFabricante', $idFabricante)->get();

        if ($personas->isEmpty()) {
            throw new \Exception('No se encontraron personas para este fabricante.');
        }

        $currentStatus = $personas->first()->idestatus;
        $newStatus = $currentStatus == 1 ? 0 : 1;

        $affected = TPersona::where('idFabricante', $idFabricante)
            ->update(['idestatus' => $newStatus]);

        return [
            'idFabricante' => $idFabricante,
            'new_status' => $newStatus,
            'affected' => $affected,
        ];
    }

    // --- MÉTODOS AUXILIARES ---

    private function limpiarCombobox($campo)
    {
        if (is_array($campo) && array_key_exists('value', $campo)) {
            return $campo['value'];
        }
        return $campo;
    }

    private function mapearDatosPersona(array $data, array $systemValues = []): array
    {
        $result = [
            'nombre_persona'    => $data['nombre'] ?? null,
            'apellido_persona'  => $data['apellido'] ?? null,
            'nombre_completo_razon_social' => trim(($data['nombre'] ?? '') . ' ' . ($data['apellido'] ?? '')),
            'documento_identidad' => $data['documento'] ?? null,
            'email'             => $data['email'] ?? null,
            'telefono_persona'  => $data['telefono'] ?? null,
            'direccion_domicilio' => $data['direccion'] ?? null,
            'sexo_genero_persona' => $data['genero'] ?? null,

            // Limpieza de Combobox
            'cod_tipo_persona'  => $this->limpiarCombobox($data['tipo_doc'] ?? null),
            'idpais'            => $this->limpiarCombobox($data['pais'] ?? null),
            'idestado'          => $this->limpiarCombobox($data['estado'] ?? null),
            'idciudad'          => $this->limpiarCombobox($data['ciudad'] ?? null),
            'idespecialidad'    => $this->limpiarCombobox($data['especialidad'] ?? null),
            'idclase_persona'   => $this->limpiarCombobox($data['clase'] ?? null),
            'idranking'         => $this->limpiarCombobox($data['ranking'] ?? null),
            'idfrecuencia'      => $this->limpiarCombobox($data['frecuencia'] ?? null),
            'idsupervisor'      => $this->limpiarCombobox($data['supervisor'] ?? null),
            'idciclos'          => $this->limpiarCombobox($data['ciclo'] ?? null),

            'name'              => $data['username'] ?? null,
            'descuento'         => $data['descuento'] ?? 0,
        ];

        // Sólo añadir los valores del sistema si se proporcionaron (creación)
        if (!empty($systemValues)) {
            $result = array_merge($result, [
                'idgrupo_persona'   => $systemValues['idgrupo_persona'] ?? null,
                'idFabricante'      => $systemValues['idFabricante'] ?? null,
                'idOperador'        => $systemValues['idOperador'] ?? null,
                'idPersona'         => $systemValues['idPersona'] ?? null,
            ]);
        }

        return $result;
    }

    private function mapTipoToGrupo($slug): string
    {
        return match($slug) {
            'clientes'       => 'CLI',
            'representantes' => 'RFV',
            'mayoristas'     => 'MAY',
            'supervisores'   => 'SUP',
            'gerentes'       => 'GRT',
            'fabricantes'    => 'FABR',
            default          => throw new \Exception("Tipo de entidad  '$slug' no soportado")
        };
    }

    private function esRolConAcceso($grupo): bool
    {
        return in_array($grupo, ['RFV', 'SUP', 'GRT', 'SIIF']);
    }

    private function obtenerIdPerfil($grupo)
    {
        // Busca en la tabla t_perfiles el ID que corresponde al tipo
        return TPerfile::where('tipo', $grupo)->value('id');
    }
}