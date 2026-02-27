<?php

namespace App\Http\Controllers\MonitorUp;

use Illuminate\Http\Request;
use App\Imports\ProductosImports\ProductosImport;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Services\ExcelImportService;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class ProductoUploadController extends Controller
{
    protected ExcelImportService $importService;

    public function __construct(ExcelImportService $importService){
        $this->importService = $importService;
    }

    public function storeProducto(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }
        $idPersona = Auth::user()->idPersona;

        $request->validate([
            'idFabricante' => 'required|string',
            'archivo_excel' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $importObject = new ProductosImport($request->idFabricante, $idPersona);

        try {
            return $this->importService->processImport(
                $importObject,
                $request->file('archivo_excel'),
                'productos'
            );
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Errores de validación',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error("Error al importar productos: " . $e->getMessage());
            return response()->json([
                'error' => 'Error interno',
                'details' => [$e->getMessage()]
            ], 500);
        }
    }
}
