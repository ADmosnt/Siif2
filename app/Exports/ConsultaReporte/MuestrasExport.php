<?php
namespace App\Exports\ConsultaReporte;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MuestrasExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function __construct(private Collection $data) {}

    public function collection(): Collection
    {
        return $this->data->map(fn($r) => [
            $r->Reporte,
            $r->Cliente,
            $r->Material,
            $r->Cantidad,
        ]);
    }

    public function headings(): array
    {
        return ['Reporte','Cliente','Material','Cantidad'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFFFFF00'],
                ],
            ],
        ];
    }
}