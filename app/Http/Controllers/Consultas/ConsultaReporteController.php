<?php

namespace App\Http\Controllers\Consultas;

use App\Http\Controllers\Controller;
use App\Models\TEstatusOrdene;
use App\Models\TEspecialidade;
use App\Models\TRankingCliente;
use App\Models\TTipoActividade;
use App\Models\TTipoIncidente;
use Inertia\Inertia;

class ConsultaReporteController extends Controller
{
    public function index()
    {
        $ranking = TRankingCliente::select('id', 'descripcion_ranking_cliente')
            ->get()
            ->map(fn($r) => [
                'value' => (string) $r->id,
                'label' => $r->descripcion_ranking_cliente,
            ]);

        $especialidad = TEspecialidade::select('id', 'descripcion_especialidad')
            ->get()
            ->map(fn($e) => [
                'value' => (string) $e->id,
                'label' => $e->descripcion_especialidad,
            ]);

        $actividad = TTipoActividade::select('idtipo_actividades', 'descripcion_tipo_actividades')
            ->get()
            ->map(fn($a) => [
                'value' => (string) $a->idtipo_actividades,
                'label' => $a->descripcion_tipo_actividades,
            ]);

        $evento = TTipoIncidente::select('idtipo_incidentes', 'descripcion_tipo_incidentes')
            ->get()
            ->map(fn($i) => [
                'value' => (string) $i->idtipo_incidentes,
                'label' => $i->descripcion_tipo_incidentes,
            ]);

        $estatus = TEstatusOrdene::select('idestatus', 'descripcion')
            ->get()
            ->map(fn($s) => [
                'value' => (string) $s->idestatus,
                'label' => $s->descripcion,
            ]);

        return Inertia::render('ConsultaReportes', [
            'options' => [
                'visitas' => [
                    'ranking'      => $ranking,
                    'especialidad' => $especialidad,
                    'actividad'    => $actividad,
                    'evento'       => $evento,
                ],
                'ordenes' => [
                    'estatus' => $estatus,
                ],
            ],
        ]);
    }
}
