<?php

namespace App\Exports\VisitasExports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PlantillaSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'Plantilla';
    }

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
}
