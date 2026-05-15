<?php
// app/Http/Controllers/Gerencial/gps/gpsController.php
namespace App\Http\Controllers\Gerencial\gps;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActividadGpsResource;
use Illuminate\Support\Carbon;
use App\Http\Requests\ObtenerRutaRequest;
use Illuminate\Support\Facades\Log;

use App\Models\TActividadesRepresentante;



class gpsController extends Controller
{
    public function obtenerRuta(obtenerRutaRequest $request)
    {

        $datos = $request->validated();

        $fechaDesde = Carbon::parse($datos['fechaDesde']);
        $fechaHasta = Carbon::parse($datos['fechaHasta']);
        $desde = min($fechaDesde, $fechaHasta)->startOfDay();
        $hasta = max($fechaDesde, $fechaHasta)->endOfDay();

        $actividades = TActividadesRepresentante::with([
            'cliente:idPersona,nombre_completo_razon_social,idespecialidad',
            'cliente.especialidad:id,descripcion_especialidad',
            'tipoActividad:idtipo_actividades,descripcion_tipo_actividades',
        ])
        ->where('idRFV', $datos['idRFV'])
        ->whereBetween('fecha_actividad', [$desde, $hasta])
        ->whereNotNull('coordenadas_l')
        ->whereNotNull('coordenadas_a')
        ->get();


        $colores = ['#FF5733', '#33FF57', '#3357FF', '#F333FF', '#FF33A1', '#33FFF6'];
        $colorIndex = 0;

        $leyenda = $actividades
            ->groupBy('tipoActividad.descripcion_tipo_actividades')
            ->map(function ($group, $nombreActividad) use (&$colores, &$colorIndex){
                return[
                    'actividad' => $nombreActividad ?: 'Sin Actividad',
                    'cantidad'  => $group->count(),
                    'color'     => $colores[$colorIndex++ % count($colores)],
                ];
            })
            ->values();

        return response()->json([
            'ruta' => ActividadGpsResource::collection($actividades),
            'leyenda' => $leyenda,
            'totalVisitas' => $actividades->count()
        ]);

        Log:: info('datos vainas gps' , [
            'ruta' => ActividadGpsResource::collection($actividades),
            'leyenda' => $leyenda,
            'totalVisitas' => $actividades->count()
            ]);
    }

}
