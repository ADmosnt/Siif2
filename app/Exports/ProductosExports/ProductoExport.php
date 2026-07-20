<?php

namespace App\Exports\ProductosExports;

use App\Models\TProducto;
use App\Models\TPersona;
use App\Models\TLineaProducto;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ProductoExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents, WithTitle
{
    use Exportable;

    protected $idFabricante;

    // campos requeridos
    protected $required = [
        'idproducto',
        'idMayorista',
        'idmoneda',
        'idlinea_producto',
        'idtipo_producto',
        'idcategorias',
        'nombre_producto',
        'Precio_producto',
        'presentacion',
        'cantidad_producto_existente',
        'fechaExpedicion_producto',
        'fechaVencimiento_producto',
        'lote',
    ];

    public function __construct($idFabricante = '')
        {
            $this->idFabricante = $idFabricante;
        }

    public function collection()
    {
        $productos = TProducto::withoutGlobalScopes()->where('idFabricante', $this->idFabricante)->get([
            'idproducto',
            'idMayorista',
            'idmoneda',
            'idlinea_producto',
            'idtipo_producto',
            'idcategorias',
            'nombre_producto',
            'descripcion_producto',
            'principio_producto',
            'Precio_producto',
            'Descuento_producto',
            'presentacion',
            'cantidad_producto_existente',
            'fechaExpedicion_producto',
            'fechaVencimiento_producto',
            'lote',
        ]);
        return collect($productos);
    }

    public function title(): string
    {
        return 'Productos';
    }

    public function headings(): array
    {
        return [
            'idproducto',
            'idMayorista',
            'idmoneda',
            'idlinea_producto',
            'idtipo_producto',
            'idcategorias',
            'nombre_producto',
            'descripcion_producto',
            'principio_producto',
            'Precio_producto',
            'Descuento_producto',
            'presentacion',
            'cantidad_producto_existente',
            'fechaExpedicion_producto',
            'fechaVencimiento_producto',
            'lote',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestColumn = $sheet->getHighestColumn();
                $highestRow = $sheet->getHighestRow();

                // Cabecera: amarillo, negrita, centrado
                $sheet->getStyle('A1:' . $highestColumn . '1')->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => 'center'],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFFFFF00'],
                    ],
                ]);

                // Bordes negros delgados a toda la tabla
                $sheet->getStyle('A1:' . $highestColumn . $highestRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ]);

                // Autoajuste de columnas
                $colCount = Coordinate::columnIndexFromString($highestColumn);
                for ($i = 1; $i <= $colCount; $i++) {
                    $col = Coordinate::stringFromColumnIndex($i);
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Encabezados y estilos para Mayorista
                $mayo = TPersona::withoutGlobalScopes()
                ->where('idFabricante', $this->idFabricante)
                ->where('idgrupo_persona', 'MAY')
                ->get(['idPersona', 'nombre_completo_razon_social'])
                ->toArray();

                $startCol = 'R'; 
                $nextCol = 'S';

                $sheet->fromArray($mayo, NULL, "{$startCol}3");

                $totalmayo = count($mayo);
                $lastRowmayo = ($totalmayo > 0) ? (2 + $totalmayo) : 2; 

                $sheet->mergeCells("{$startCol}1:{$nextCol}1");
                $sheet->setCellValue("{$startCol}1", 'Mayotista');
                $sheet->setCellValue("{$startCol}2", 'ID');
                $sheet->setCellValue("{$nextCol}2", 'NOMBRE');

                $sheet->getStyle("{$startCol}1:{$nextCol}2")->applyFromArray([
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => 'center'],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFFFFF00'],
                    ],
                ]);

                $sheet->getStyle("{$startCol}1:{$nextCol}{$lastRowmayo}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ]);

                $sheet->getColumnDimension($startCol)->setAutoSize(true);
                $sheet->getColumnDimension($nextCol)->setAutoSize(true);

                // Encabezados y estilos para Linea Producto
                $line_pro = TLineaProducto::withoutGlobalScopes()
                ->where('idFabricante', $this->idFabricante)
                ->get(['id', 'descripcion_linea_producto'])
                ->toArray();

                $startCol = 'U'; 
                $nextCol = 'V';

                $sheet->fromArray($line_pro, NULL, "{$startCol}3");

                $totallinea = count($line_pro);
                $lastRowlinea = ($totallinea > 0) ? (2 + $totallinea) : 2;

                $sheet->mergeCells("{$startCol}1:{$nextCol}1");
                $sheet->setCellValue("{$startCol}1", 'Linea Producto');
                $sheet->setCellValue("{$startCol}2", 'ID');
                $sheet->setCellValue("{$nextCol}2", 'Linea');

                $sheet->getStyle("{$startCol}1:{$nextCol}2")->applyFromArray([
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => 'center'],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFFFFF00'],
                    ],
                ]);

                $sheet->getStyle("{$startCol}1:{$nextCol}{$lastRowlinea}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ]);

                $sheet->getColumnDimension($startCol)->setAutoSize(true);
                $sheet->getColumnDimension($nextCol)->setAutoSize(true);

                // Categoria productos
                $colID = 'X'; 
                $colDesc = 'Y';

                $sheet->mergeCells("{$colID}1:{$colDesc}1");
                $sheet->setCellValue("{$colID}1", 'Categoria Producto');

                $sheet->setCellValue("{$colID}2", 'ID');
                $sheet->setCellValue("{$colDesc}2", 'Descripcion');

                $sheet->setCellValue("{$colID}3", 'MUES');
                $sheet->setCellValue("{$colDesc}3", 'Muestras');
                $sheet->setCellValue("{$colID}4", 'PROD');
                $sheet->setCellValue("{$colDesc}4", 'Productos');

                $sheet->getStyle("{$colID}1:{$colDesc}2")->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => 'center'],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFFFFF00'],
                    ],
                ]);

                $sheet->getStyle("{$colID}1:{$colDesc}4")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ]);

                $sheet->getColumnDimension($colID)->setAutoSize(true);
                $sheet->getColumnDimension($colDesc)->setAutoSize(true);

                $allHeadings = $this->headings();
                $requiredFieldsMap = [];
                foreach ($this->required as $field) {
                    $requiredFieldsMap[strtolower($field)] = true;
                }
                foreach ($allHeadings as $colIndex => $heading) {
                    $columnLetter = Coordinate::stringFromColumnIndex($colIndex + 1);
                    if (isset($requiredFieldsMap[strtolower($heading)])) {
                        $sheet->getStyle($columnLetter . '2:' . $columnLetter . $highestRow)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['argb' => 'FFFFFF00'],
                            ],
                        ]);
                    }
                }
            },
        ];
    }
}