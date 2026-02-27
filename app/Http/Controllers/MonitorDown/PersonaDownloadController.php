<?php

namespace App\Http\Controllers\MonitorDown;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PersonasExports\CliRfvExport;
use App\Exports\PersonasExports\PersonaExport;
//use App\Exports\PersonasExports\DiasVisitasExport;
use App\Http\Controllers\Controller;

class PersonaDownloadController extends Controller
{
    /// PERSONAS EXPORT
    public function PersonaDownload(Request $request)
    {
    $validator = Validator::make($request->all(),[
        'idFabricante' => 'required|exists:t_personas,idFabricante',
    ]);
    
    if($validator->fails()){
        return response()->json($validator->errors()->all(), 400);
    }
    
    $idFabricante = $request->input('idFabricante');

    return Excel::download(new PersonaExport($idFabricante), 'PersonaFormato_' . $idFabricante . '.xlsx');

    }

    ///CLIENTES POR RFV EXPORT
    public function ClientByRFVDownload(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'idFabricante' => 'required|exists:t_personas,idFabricante',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->all(), 400);
        }

        $idFabricante = $request->input('idFabricante');
                // Usa la nueva clase CliRfvExport para descargar los datos
            return Excel::download(new CliRfvExport($idFabricante), 'CliRfvformato_' . $idFabricante . '.xlsx');
    }

}