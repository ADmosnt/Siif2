<?php

namespace App\Exports\ProductosExports;

use App\Models\TProducto;
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

    static $fabricante;

    // Define aquí los campos requeridos
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
        // Agrega aquí los campos que realmente sean requeridos según tus reglas
    ];

    public function __construct($Fabricante = '')
    {
        self::$fabricante = $Fabricante;
    }

    public function collection()
    {
        $productos = TProducto::withoutGlobalScopes()->where('idFabricante', self::$fabricante)->get([
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

                // 1. Cabecera: amarillo, negrita, centrado
                $sheet->getStyle('A1:' . $highestColumn . '1')->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => 'center'],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFFFFF00'],
                    ],
                ]);

                // 2. Bordes negros delgados a toda la tabla
                $sheet->getStyle('A1:' . $highestColumn . $highestRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ]);

                // 3. Autoajuste de columnas
                $colCount = Coordinate::columnIndexFromString($highestColumn);
                for ($i = 1; $i <= $colCount; $i++) {
                    $col = Coordinate::stringFromColumnIndex($i);
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // 4. Resalta columnas requeridas (toda la columna menos encabezado)
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