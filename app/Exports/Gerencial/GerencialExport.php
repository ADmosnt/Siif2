<?php
//app/Exports/Gerencial/GerencialExport.php
namespace App\Exports\Gerencial;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class GerencialExport implements 
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithStyles,
    WithColumnFormatting
{
    /** @var mixed Colección de datos a exportar */
    protected $data;

    /**
     * Constructor de la clase.
     * * @param mixed $data Recibe la colección de registros (usualmente Eloquent models).
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Retorna la colección de datos que se procesará.
     * * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->data;
    }

    /**
     * Define los títulos de la primera fila del archivo Excel.
     * * @return array
     */
    public function headings(): array
    {
        return [
            'Supervisor',
            'RFV',
            'Zona',
            'Brick',
            'Mes/Año',
            '% Cobertura',
            'Cant. Esperada',
            'Monto Esperado',
            'Cant. Facturada',
            'Monto Facturado'
        ];
    }

    public function map($item): array
    {
        return [
            // applyFilters() (gerencialController) ya resuelve estos nombres
            // por LEFT JOIN como columnas planas: su select() no incluye
            // idRFV/idsupervisor/idzona/idruta, asi que las relaciones de
            // Eloquent ($item->supervisor, etc.) siempre daban null aunque
            // se pidiera with(). Hay que usar los alias planos, igual que
            // la tabla en pantalla (ConsultaGerencial.vue).
            $item->supervisor_nombre ?? 'Sin Supervisor',
            $item->rfv_nombre ?? 'Sin RFV',
            $item->zona_nombre ?? 'N/A',
            $item->ruta_descripcion ?? 'N/A',
            $item->MesRegistro ?? 'N/A',
            
            // Formateo manual de porcentaje
            number_format((float) ($item->porce_cobertura ?? 0), 2) . '%',
            
            // Conversiones de tipo para asegurar que Excel los reconozca como números
            (int) ($item->productoEsperado ?? 0),
            (float) ($item->monto_esperado ?? 0),
            (int) ($item->productoFacturado ?? 0),
            (float) ($item->monto_facturado ?? 0),
        ];
    }

    /**
     * Aplica estilos estéticos a la hoja.
     * En este caso, pone la primera fila (encabezados) en negrita.
     * * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Fila 1 en estilo negrita
            1 => ['font' => ['bold' => true]],
        ];
    }

    /**
     * Define el formato de datos para columnas específicas.
     * Esto asegura que Excel trate las columnas como números y no como texto.
     * * @return array
     */
    public function columnFormats(): array
    {
        return [
            'G' => NumberFormat::FORMAT_NUMBER,
            'H' => NumberFormat::FORMAT_NUMBER_00,
            'I' => NumberFormat::FORMAT_NUMBER,
            'J' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }
}