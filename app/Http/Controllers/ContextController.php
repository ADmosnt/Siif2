<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CompanyContextService;

class ContextController extends Controller
{
    public function __construct( 
        protected CompanyContextService $contextService
        ){}

    public function switchFabricante(Request $request)
    {
        // Obtenemos el ID, si es un string vacío o no existe, lo tratamos como null
        $id = $request->input('id');
        $idFinal = ($id === '' || !$id) ? null : $id;

        $this->contextService->setContext($idFinal);

        return back();
    }
}