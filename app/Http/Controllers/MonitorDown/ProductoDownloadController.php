<?php

namespace App\Http\Controllers\MonitorDown;

use App\Exports\ProductosExports\ProductoExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;

class ProductoDownloadController extends Controller
{
public function ProductoDownloadByFabricante(Request $request)
{
    $validator = Validator::make($request->all(), [
        'idFabricante' => 'required|exists:t_personas,idFabricante',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors()->all(), 400);
    }

    $idFabricante = $request->input('idFabricante');
    
    return Excel::download(new ProductoExport($idFabricante), 'productos_fabricante_' . $idFabricante . '.xlsx');
}
}