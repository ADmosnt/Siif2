<?php
namespace App\Exports\ConsultaReporte;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class OrdenesExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function __construct(private Collection $data) {}

    public function collection(): Collection
    {
        return $this->data->map(fn($r) => [
            $r['nOrden'],
            $r['cliente'],
            $r['fecha'],
            $r['estatus'],
            $r['rfv'],
            $r['ciudad'],
            $r['estado'],
            $r['mayoristas'],
            $r['unidades'],
            $r['totalOrden'],
            $r['comentario'],
            $r['coordenadas_l'],
            $r['coordenadas_a'],
            $r['factura'],
        ]);
    }

    public function headings(): array
    {
        return ['Nº Orden','Cliente','Fecha','Estatus','RFV','Ciudad','Estado','Mayorista','Unidades','Monto','Comentario','Lat','Lng','Factura'];
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