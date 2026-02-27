<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TTmpPlanificadore;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcesarVisitasVencidas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'visitas:procesar-vencidas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Marca como "Perdidas" las visitas temporales cuya fecha programada ha vencido hace más de una semana.';

    /**
     * Execute the console command.
     */

    public function handle(): void
    {
        $this->info('Iniciando proceso de actualización de visitas vencidas...');

        // --- Encontrar visitas vencidas ---
        // donde estatus_visita = 1 (Pendiente)
        // y Fecha + 1 semana < Fecha y hora actual
        $fechaLimite = Carbon::now()->subWeek();

        $visitasVencidas = TTmpPlanificadore::where('estatus_visita', TTmpPlanificadore::ESTATUS_TEMPORAL)
            ->where('Fecha', '<', $fechaLimite->toDateString())
            ->get();

        $contadorActualizadas = 0;
        $contadorErrores = 0;

        if ($visitasVencidas->isEmpty()) {
            $this->info('No se encontraron visitas temporales vencidas para actualizar.');
        } else {
            $this->info("Se encontraron {$visitasVencidas->count()} visitas vencidas. Procesando...");

            foreach ($visitasVencidas as $visita) {
                try {
                    // --- Actualizar el estatus ---
                    $visita->update(['estatus_visita' => TTmpPlanificadore::ESTATUS_PERDIDA]);

                    // Opcional: Registrar la acción en un log específico
                    Log::info("Visita temporal ID {$visita->Id} marcada como perdida automáticamente.", [
                        'fecha_visita' => $visita->Fecha,
                        'fecha_limite_calculada' => $fechaLimite->toDateString(),
                        'fecha_actual' => Carbon::now()->toDateString(),
                    ]);

                    $contadorActualizadas++;

                } catch (\Exception $e) {
                    $contadorErrores++;
                    Log::error("Error al marcar visita ID {$visita->Id} como perdida: " . $e->getMessage(), [
                        'trace' => $e->getTraceAsString(),
                        'visita_data' => $visita->toArray()
                    ]);

                    // Opcional: Mostrar error en consola
                    $this->error("Error al procesar visita ID {$visita->Id}: " . $e->getMessage());
                }
            }

            $this->info("Proceso completado. Visitas actualizadas: {$contadorActualizadas}. Errores: {$contadorErrores}.");
        }

        // --- 3. (Opcional) Notificaciones o tareas posteriores ---
        // esta parte probablemente podría servir para la app movil, tal vez, seguramente no sé
    }
}