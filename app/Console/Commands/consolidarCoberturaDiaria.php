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
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:consolidar-cobertura-diaria';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calcula y actualiza las estadisticas de cobertura diarias para los representantes (RFV)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('-> Iniciando el proceso de consolidacion de cobertura diaria...');
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
                ]
                );
        }
        $this->info('-> Registros de cobertura verificados y creados para ' . $representantesActivos->count() . ' representantes.');

        // --- Calcular estadisticas del dia
    $this->line('   Calculando estadísticas acumuladas del mes...');

    foreach ($representantesActivos as $representante) {
        $id = $representante->idPersona;
        
        // Calculamos los totales del mes para este RFV
        $statsMes = DB::table('t_ordenes')
            ->selectRaw('
                COALESCE(SUM(CASE WHEN idestatus = "6" THEN costoTotal ELSE 0 END), 0) as montoFact,
                COALESCE(SUM(CASE WHEN idestatus = "6" THEN 1 ELSE 0 END), 0) as cantFact,
                COALESCE(SUM(CASE WHEN idestatus NOT IN ("6","7","8") THEN costoTotal ELSE 0 END), 0) as montoEsp
            ')
            ->where('idPersona', $id)
            ->whereBetween('fechaOrden', [$inicioDeMes, $hoy])
            ->first();

        $visitasMes = DB::table('t_planificadores')
            ->selectRaw('
                COALESCE(SUM(CASE WHEN idestatus = "2" THEN 1 ELSE 0 END), 0) as visitados,
                COALESCE(SUM(CASE WHEN idestatus = "1" THEN 1 ELSE 0 END), 0) as sinVisitar
            ')
            ->where('idRFV', $id)
            ->whereBetween('fecha_agenda', [$inicioDeMes, $hoy])
            ->first();

        // --- Actualizar la tabla de cobertura
        TCoberturaRv::where('idRFV', $id)
            ->where('MesRegistro', $hoy->format('n/Y'))
            ->update([
                'cant_realizada' => $visitasMes->visitados,
                'cant_esperada' => $visitasMes->sinVisitar,
                'monto_facturado' => $statsMes->montoFact,
                'cant_ordenes_realizadas' => $statsMes->cantFact,
                'monto_esperado' => $statsMes->montoEsp,
                'fechaRegistro' => $hoy, // Actualizamos la fecha de la última consolidación
            ]);
    }
    
        // --- Recalcular Porcentajes ---
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
