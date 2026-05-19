<?php
namespace App\Exports\ConsultaReporte;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class VisitasExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function __construct(private Collection $data) {}

    public function collection(): Collection
    {
        return $this->data->map(fn($r) => [
            $r['reporte'],
            $r['rfv'],
            $r['cliente'],
            $r['ciudad'],
            $r['estado'],
            $r['fecha'],
            $r['actividad'],
            $r['observaciones'],
            $r['coordenadas_l'],
            $r['coordenadas_a'],
        ]);
    }

    public function headings(): array
    {
        return ['Nº Rep.','RFV','Cliente','Ciudad','Estado','Fecha','Actividad','Comentarios','Lat','Lng'];
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