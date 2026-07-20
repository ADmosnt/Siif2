<?php
namespace App\Exports\ConsultaReporte;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ProductosOrdenExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function __construct(private Collection $data) {}

    public function collection(): Collection
    {
        return $this->data->map(fn($r) => [
            $r->Orden,
            $r->Nombre,
            $r->Cliente,
            $r->RFV,
            $r->Mayorista,
            $r->Solicitado,
            $r->Monto_Solicitado,
            $r->Conciliado,
            $r->Faltante,
        ]);
    }

    public function headings(): array
    {
        return ['Nº Orden','Producto','Cliente','RFV','Mayorista','Solicitado','Monto Solicitado','Conciliado','Faltante'];
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