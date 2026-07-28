<?php

namespace App\Exports\VisitasExports;

use App\Services\RepresentanteClienteService;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PlantillaVisitasExport implements WithMultipleSheets
{
    public function __construct(
        protected RepresentanteClienteService $representanteService
    ) {}

    public function sheets(): array
    {
        return [
            new PlantillaSheet(),
            new RfvClienteSheet($this->representanteService),
        ];
    }
}
