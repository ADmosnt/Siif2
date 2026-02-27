<?php
namespace App\Http\Controllers\MonitorUp;

use Illuminate\Http\Request;
use App\Imports\PersonasImports\PersonaImport;
use App\Imports\PersonasImports\CliRfvImport;
//use App\Imports\PersonasImports\DiasVisitasImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\ValidationException;
use App\Services\ExcelImportService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class PersonaUploadController extends Controller
{
    protected ExcelImportService $importService;

    public function __construct(ExcelImportService $importService){
        $this->importService = $importService;
    }

    public function storeNewPersonas(Request $request)
    {
        $request->validate([
            'idFabricante' => 'required|string',
            'archivo_excel' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $importObject = new PersonaImport($request->idFabricante);
        
        return $this->importService->processImport(
            $importObject,
            $request->file('archivo_excel'),
            'nuevas personas'
        );
    }

    public function storeClientesRfv(Request $request)
    {
        $request->validate([
            'idFabricante' => 'required|string',
            'archivo_excel' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $importObject = new CliRfvImport($request->idFabricante);

        return $this->importService->processImport(
            $importObject,
            $request->file('archivo_excel'),
            'clientes RFV'
        );
    }
}