<?php

namespace App\Exports\VisitasExports;

use App\Models\TPersona;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PlantillaVisitasExport implements FromArray, WithHeadings, WithStyles, WithEvents
{
    public function headings(): array
    {
        return ['idRFV', 'idCliente', 'Fecha', 'Hora'];
    }

    public function array(): array
    {
        return [
            ['RFV001', 'CLI001', '2025-06-01', '09:00'],
            ['RFV001', 'CLI002', '2025-06-01', '11:00'],
            ['RFV002', 'CLI003', '2025-06-02', '14:30'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->agregarHojaReferencia($event);
            },
        ];
    }

    /**
     * Hoja de referencia con los RFV y clientes disponibles para quien
     * descarga la plantilla: si es RFV, solo su propio id/nombre; si es
     * SUP/GRT/SIIF, todos los RFV de la empresa. Los clientes se listan
     * junto al RFV al que pertenecen (ordenados por RFV) para saber que
     * idCliente usar segun el vendedor.
     */
    private function agregarHojaReferencia(AfterSheet $event): void
    {
        $authUser = Auth::user();
        $user = TPersona::findOrFail($authUser->idPersona);

        $rfvs = $user->esRepresentante()
            ? collect([$user])
            : TPersona::where('idgrupo_persona', TPersona::TIPO_REPRESENTANTE)
                ->where('idFabricante', $user->idFabricante)
                ->where('idestatus', 1)
                ->orderBy('nombre_completo_razon_social')
                ->get();

        $clientesPorRfv = collect();
        foreach ($rfvs as $rfv) {
            $rfv->Clientes()->get()->each(function (TPersona $cliente) use ($rfv, $clientesPorRfv) {
                $clientesPorRfv->push([
                    'idRFV' => $rfv->idPersona,
                    'nombreRFV' => $rfv->nombre_completo_razon_social,
                    'idCliente' => $cliente->idPersona,
                    'nombreCliente' => $cliente->nombre_completo_razon_social,
                ]);
            });
        }
        $clientesPorRfv = $clientesPorRfv->sortBy(['nombreRFV', 'nombreCliente'])->values();

        $spreadsheet = $event->sheet->getDelegate()->getParent();
        $hoja = $spreadsheet->createSheet();
        $hoja->setTitle('Referencia RFV-Clientes');

        // Bloque de RFV disponibles (columnas A-B)
        $hoja->setCellValue('A1', 'ID RFV');
        $hoja->setCellValue('B1', 'Nombre RFV');
        $row = 2;
        foreach ($rfvs as $rfv) {
            $hoja->setCellValue("A{$row}", $rfv->idPersona);
            $hoja->setCellValue("B{$row}", $rfv->nombre_completo_razon_social);
            $row++;
        }

        // Bloque de clientes agrupados por RFV (columnas D-G)
        $hoja->setCellValue('D1', 'ID RFV');
        $hoja->setCellValue('E1', 'Nombre RFV');
        $hoja->setCellValue('F1', 'ID Cliente');
        $hoja->setCellValue('G1', 'Nombre Cliente');
        $row = 2;
        foreach ($clientesPorRfv as $item) {
            $hoja->setCellValue("D{$row}", $item['idRFV']);
            $hoja->setCellValue("E{$row}", $item['nombreRFV']);
            $hoja->setCellValue("F{$row}", $item['idCliente']);
            $hoja->setCellValue("G{$row}", $item['nombreCliente']);
            $row++;
        }

        foreach (['A', 'B', 'D', 'E', 'F', 'G'] as $col) {
            $hoja->getColumnDimension($col)->setAutoSize(true);
        }

        $negrita = ['font' => ['bold' => true]];
        $hoja->getStyle('A1:B1')->applyFromArray($negrita);
        $hoja->getStyle('D1:G1')->applyFromArray($negrita);
    }
}
