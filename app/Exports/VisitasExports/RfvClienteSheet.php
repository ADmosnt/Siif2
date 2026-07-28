<?php

namespace App\Exports\VisitasExports;

use App\Models\RClienteRfv;
use App\Services\RepresentanteClienteService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Hoja de referencia con los RFV y sus clientes asignados, para que quien
 * llena la plantilla de carga masiva sepa que idRFV/idCliente usar.
 *
 * Reutiliza RepresentanteClienteService::getRepresentantesData(), que ya
 * resuelve la visibilidad por rol (RFV ve solo su propio idPersona;
 * GRT/SUP ven los RFV de su fabricante; SIIF ve los de la empresa activa
 * en su contexto).
 */
class RfvClienteSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    public function __construct(
        protected RepresentanteClienteService $representanteService
    ) {}

    public function title(): string
    {
        return 'RFV y Clientes';
    }

    public function headings(): array
    {
        return ['idRFV', 'Nombre RFV', 'idCliente', 'Nombre Cliente'];
    }

    public function array(): array
    {
        // Sin filtro de tamaño de página: se necesitan todos los RFV
        // visibles para este usuario, no una página de 25.
        $request = Request::create('/', 'GET', ['size' => 5000]);
        $rfvs = $this->representanteService->getRepresentantesData($request)->items();

        $rfvIds = collect($rfvs)->pluck('id')->filter()->values()->toArray();
        if (empty($rfvIds)) {
            return [];
        }

        $clientesPorRfv = RClienteRfv::whereIn('id_RFV', $rfvIds)
            ->join('t_personas', 'r_cliente_rfv.id_cliente', '=', 't_personas.idPersona')
            ->whereNotNull('t_personas.nombre_completo_razon_social')
            ->orderBy('t_personas.nombre_completo_razon_social')
            ->get([
                'r_cliente_rfv.id_RFV as idRFV',
                'r_cliente_rfv.id_cliente as idCliente',
                't_personas.nombre_completo_razon_social as nombreCliente',
            ])
            ->groupBy('idRFV');

        $filas = [];
        foreach ($rfvs as $rfv) {
            $clientes = $clientesPorRfv->get($rfv['id']);

            if (!$clientes || $clientes->isEmpty()) {
                $filas[] = [$rfv['id'], $rfv['nombre'], '', '(sin clientes asignados)'];
                continue;
            }

            foreach ($clientes as $cliente) {
                $filas[] = [$rfv['id'], $rfv['nombre'], $cliente->idCliente, $cliente->nombreCliente];
            }
        }

        return $filas;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
