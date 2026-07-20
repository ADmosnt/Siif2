<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

use App\Models\TPersona;
use App\Models\TBrickRutaPersona;
use App\Models\TCiclo;
use App\Models\TCoberturaRv;

class ConsolidarCoberturaDiaria extends Command
{
    protected $signature = 'app:consolidar-cobertura-diaria';
    protected $description = 'Calcula y actualiza las estadísticas de cobertura diarias para los representantes (RFV)';

    public function handle()
    {
        $this->info('-> Iniciando el proceso de consolidación de cobertura diaria...');
        $hoy = Carbon::now();
        $inicioDeMes = $hoy->copy()->startOfMonth();

        // --- Crear registros de cobertura mensuales si no existen
        $this->line('-> Verificando y creando registros de cobertura...');
        $representantesActivos = TPersona::where('idgrupo_persona', TPersona::TIPO_REPRESENTANTE)
                                        ->where('idestatus', 1)
                                        ->get();
        foreach ($representantesActivos as $representante){
            $brickInfo = TBrickRutaPersona::where('idPersona', $representante->idPersona)->first();
            TCoberturaRv::updateOrCreate(
                [
                    'idRFV'         => $representante->idPersona,
                    'MesRegistro'   => $hoy->format('n/Y'),
                ],
                [
                    'idOperador'    => $representante->idOperador,
                    'idFabricante'  => $representante->idFabricante,
                    'idsupervisor'  => $representante->idsupervisor,
                    'idzona'        => $brickInfo->idzona ?? null,
                    'idruta'        => $brickInfo->idbrick ?? null,
                    'idestado'      => $representante->idestado,
                    'idciudad'      => $representante->idciudad,
                    'fechaRegistro' => $hoy,
                    'numero_ciclo'  => TCiclo::where('idFabricante', $representante->idFabricante)->value('id'),
                    'idestatus'     => 1,
                    'cant_esperada' => 0,
                    'cant_realizada' => 0,
                    'monto_facturado' => 0,
                    'monto_esperado' => 0,
                    'cant_ordenes_realizadas' => 0,
                    'cant_ordenes_parciales' => 0,
                    'productoFacturado' => 0,
                    'productoEsperado' => 0,
                    'porce_cobertura' => 0,
                ]
            );
        }
        $this->info('-> Registros de cobertura verificados y creados para ' . $representantesActivos->count() . ' representantes.');

        // --- Calcular estadísticas del mes
        $this->line('   Calculando estadísticas acumuladas del mes...');

        foreach ($representantesActivos as $representante) {
            $id = $representante->idPersona;

            // Totales de órdenes del mes
            $statsMes = DB::table('t_ordenes')
                ->selectRaw('
                    COALESCE(SUM(CASE WHEN idestatus = "6" THEN costoTotal ELSE 0 END), 0) as montoFact,
                    COALESCE(SUM(CASE WHEN idestatus = "6" THEN 1 ELSE 0 END), 0) as cantFact,
                    COALESCE(SUM(CASE WHEN idestatus = "6" THEN TotalUnidades ELSE 0 END), 0) as unidFact,
                    COALESCE(SUM(CASE WHEN idestatus NOT IN ("6","7","8") THEN costoTotal ELSE 0 END), 0) as montoEsp,
                    COALESCE(SUM(CASE WHEN idestatus NOT IN ("6","7","8") THEN 1 ELSE 0 END), 0) as cantEsp,
                    COALESCE(SUM(CASE WHEN idestatus NOT IN ("6","7","8") THEN TotalUnidades ELSE 0 END), 0) as unidEsp
                ')
                ->where('idPersona', $id)
                ->whereBetween('fechaOrden', [$inicioDeMes, $hoy])
                ->first();

            // Visitas del mes
            $visitasMes = DB::table('t_planificadores')
                ->selectRaw('
                    COALESCE(SUM(CASE WHEN idestatus = "2" THEN 1 ELSE 0 END), 0) as visitados,
                    COALESCE(SUM(CASE WHEN idestatus = "1" THEN 1 ELSE 0 END), 0) as sinVisitar
                ')
                ->where('idRFV', $id)
                ->whereBetween('fecha_agenda', [$inicioDeMes, $hoy])
                ->first();

            // Actualizar la tabla de cobertura
            TCoberturaRv::where('idRFV', $id)
                ->where('MesRegistro', $hoy->format('n/Y'))
                ->update([
                    'cant_realizada'            => $visitasMes->visitados,
                    'cant_esperada'              => $visitasMes->sinVisitar,
                    'monto_facturado'            => $statsMes->montoFact,
                    'monto_esperado'              => $statsMes->montoEsp,
                    'cant_ordenes_realizadas'    => $statsMes->cantFact,
                    'cant_ordenes_parciales'     => $statsMes->cantEsp,
                    'productoFacturado'          => $statsMes->unidFact,
                    'productoEsperado'           => $statsMes->unidEsp,
                    'fechaRegistro'               => $hoy, // Última consolidación
                ]);
        }

        // --- Recalcular Porcentajes de cobertura (evitando división por cero)
        $this->line('   Recalculando porcentajes de cobertura...');
        TCoberturaRv::where('MesRegistro', $hoy->format('n/Y'))
            ->whereRaw('cant_realizada + cant_esperada > 0')
            ->update([
                'porce_cobertura' => DB::raw('cant_realizada * 100 / (cant_realizada + cant_esperada)')
            ]);

        $this->info('-> ¡Proceso de consolidación completado con éxito!');
        return self::SUCCESS;
    }
}