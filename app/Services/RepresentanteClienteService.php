<?php
// app/Services/RepresentanteClienteService.php
namespace App\Services;

use App\Models\TPersona;
use App\Models\RClienteRfv;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Traits\FabricanteTrait;

class RepresentanteClienteService
{

    public function __construct(
        
    protected CompanyContextService $contextService)
    {}

    use FabricanteTrait;

    /**
     * Obtiene los representantes (RFV) basados en el rol del usuario.
     */
    public function getRepresentantesData(Request $request): LengthAwarePaginator
    {
        $user = Auth::user();
        $activeFabricante = $this->contextService->getActiveId();
        $userGroup = $user->idgrupo_persona;
        $userIdPersona = $user->idPersona;
        $search = $request->input('search');
        $size = $request->input('size', 25);
        $page = $request->input('page', 1);
        $query = TPersona::where('idgrupo_persona', 'RFV')
            ->whereNotNull('idPersona')
            ->where('idPersona', '!=', '');

        if ($userGroup === 'RFV') {
            $representantes = collect([
                [
                    'id' => $userIdPersona,
                    'nombre' => $user->nombre_completo_razon_social
                ]
            ]);
            return new LengthAwarePaginator(
                $representantes,
                $representantes->count(),
                $size,
                $page
            );
        }
        // FILTRO DE CONTEXTO (SIIF/GRT/SUP)
        // El ContextService ya nos da el ID correcto según el rol y la selección
        if (!($user->idgrupo_persona === 'SIIF' && is_null($activeFabricante))) {
            $query->where('idFabricante', $activeFabricante);
        }

        // Búsqueda y Paginación
        return $query->whereNotNull('nombre_completo_razon_social')
            ->when($search, function($q) use ($search) {
                $q->where('nombre_completo_razon_social', 'like', "%{$search}%");
            })
            ->orderBy('nombre_completo_razon_social')
            ->paginate($request->input('size', 25))
            ->through(fn ($rep) => [
            // AQUÍ ESTÁ EL TRUCO: Mapeamos el nombre de la DB al nombre de tu Interfaz
            'id'     => (string) $rep->idPersona, 
            'nombre' => $rep->nombre_completo_razon_social,
        ]);
    }

    /**
     * Obtiene clientes por RFV con búsqueda
     */
    public function searchClientesData(Request $request): array
    {
        $searchTerm = $request->input('search', '');
        $idRfv = $request->input('idRfv');
        $user = Auth::user();

        $resolved = $this->resolveIdRfvForRequest($request, $idRfv, $user, 25, 1);
        if (!$resolved['idRfv']) {
            return [];
        }

        $idRfv = $resolved['idRfv'];

        $query = $this->baseClientesQuery($idRfv)
            ->select('r_cliente_rfv.id_cliente as id_cliente', 't_personas.nombre_completo_razon_social as nombre_completo_razon_social')
            ->orderBy('t_personas.nombre_completo_razon_social');

        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('t_personas.nombre_completo_razon_social', 'like', "%{$searchTerm}%")
                    ->orWhere('r_cliente_rfv.id_cliente', 'like', "%{$searchTerm}%");
            });
        }

        try {
            $results = $query->limit(25)->get();

            return $results->map(fn ($item) => [
                'value' => $item->id_cliente,
                'label' => $item->nombre_completo_razon_social ?? 'Cliente no encontrado'
            ])->toArray();

        } catch (\Exception $e) {
            Log::error('Error obteniendo clientes en el servicio (searchClientesData):', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'idRfv' => $idRfv,
                'search_term' => $searchTerm
            ]);
            return [];
        }
    }

    /**
     * Obtiene clientes paginados para un RFV
     */
    public function getClientesData(Request $request, ?string $idRfv = null): LengthAwarePaginator
    {
        $user = Auth::user();
        $search = $request->input('search');
        $size = $request->input('size', 15);
        $page = $request->input('page', 1);

        $resolved = $this->resolveIdRfvForRequest($request, $idRfv, $user, $size, $page);
        if (!$resolved['idRfv']) {
            return new LengthAwarePaginator([], 0, $size, $page);
        }

        $idRfv = $resolved['idRfv'];

        try {
            $query = $this->baseClientesQuery($idRfv)
                ->select(
                    'r_cliente_rfv.id_cliente as id_cliente',
                    't_personas.nombre_completo_razon_social as nombre_completo_razon_social',
                    't_personas.direccion',
                    't_personas.telefono'
                );

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('t_personas.nombre_completo_razon_social', 'like', "%{$search}%")
                        ->orWhere('r_cliente_rfv.id_cliente', 'like', "%{$search}%");
                });
            }

            return $query->orderBy('t_personas.nombre_completo_razon_social')
                ->paginate($size, ['*'], 'page', $page)
                ->withQueryString()
                ->through(fn ($item) => [
                    'id' => $item->id_cliente,
                    'nombre' => $item->nombre_completo_razon_social,
                    'direccion' => $item->direccion,
                    'telefono' => $item->telefono
                ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo clientes paginados:', [
                'message' => $e->getMessage(),
                'idRfv' => $idRfv
            ]);
            return new LengthAwarePaginator([], 0, $size, $page);
        }
    }

    /**
     * Resuelve y valida el idRfv a usar para peticiones de clientes.
     */
    private function resolveIdRfvForRequest(Request $request, ?string $idRfv, $user, int $size, int $page): array
    {
        if (is_null($idRfv)) {
            $idRfv = $request->input('idRfv');
        }

        if (!$idRfv) {
            Log::warning('ID de RFV no proporcionado en getClientesData', [
                'user_id' => $user->idPersona ?? null,
                'user_group' => $user->idgrupo_persona ?? null
            ]);

            $idRfv = ($user->idgrupo_persona === 'RFV') ? $user->idPersona : null;
            if (!$idRfv) {
                return ['idRfv' => null];
            }
        }

        if ($user->idgrupo_persona === 'RFV' && $user->idPersona !== $idRfv) {
            return ['idRfv' => null];
        }
        if ($user->idgrupo_persona === 'GRT' && $this->esRfvDeOtroFabricante($idRfv, $user->idFabricante)) {
            return ['idRfv' => null];
        }

        return ['idRfv' => $idRfv];
    }

    /**
     * Construye la query base para obtener clientes de un RFV.
     */
    private function baseClientesQuery(string $idRfv): Builder
    {
        return RClienteRfv::where('id_RFV', $idRfv)
            ->whereHas('cliente')
            ->join('t_personas', 'r_cliente_rfv.id_cliente', '=', 't_personas.idPersona');
    }

    /**
     * Helper para verificar si un RFV pertenece a otro fabricante.
     */
    private function esRfvDeOtroFabricante(?string $idRfv, ?string $userFabricanteId): bool
    {
        if (!$idRfv) {
            Log::warning('idRfv es null en esRfvDeOtroFabricante');
            return true;
        }

        $rfv = TPersona::where('idPersona', $idRfv)
            ->where('idgrupo_persona', 'RFV')
            ->where('idFabricante', $userFabricanteId)
            ->first();
            
        return !$rfv;
    }

    /**
     * Obtiene el ID de fabricante para un RFV
     */
    public function obtenerIdFabricante(?string $idRfv): ?string
    {
        if (is_null($idRfv)) {
            $idRfv = Auth::user()->idPersona;
        }
        return $this->getFabricanteId($idRfv);
    }

    /**
     * Verifica si el usuario actual tiene permisos para acceder a un RFV específico
     */
    public function tienePermisosRfv(?string $idRfv = null): bool
    {
        $user = Auth::user();
        
        if (is_null($idRfv)) {
            $idRfv = request()->input('idRfv');
        }

        if (!$idRfv) {
            return $user->idgrupo_persona === 'RFV';
        }

        // RFV: Solo su propio ID
        if ($user->idgrupo_persona === 'RFV') {
            return $user->idPersona === $idRfv;
        }

        // GRT/SUP: Solo RFVs de su empresa
        if (in_array($user->idgrupo_persona, ['GRT', 'SUP'])) {
            return !$this->esRfvDeOtroFabricante($idRfv, $user->idFabricante);
        }

        // SIIF: Acceso a todos los RFVs
        if ($user->idgrupo_persona === 'SIIF') {
            return true;
        }

        return false;
    }
}