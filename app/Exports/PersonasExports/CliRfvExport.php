<?php

namespace App\Exports\PersonasExports;

use App\Models\TPersona;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CliRfvExport implements FromCollection, WithEvents, WithTitle, WithHeadings
{
    use Exportable;

    protected $idOperador;
    protected $idFabricante;

    public function __construct(string $idFabricante)
    {
        $this->idFabricante = $idFabricante;
        $this->idOperador = Auth::user()->idOperador ?? null;

        if (is_null($this->idOperador)) {
            throw new \Exception("ID de Operador no disponible para la exportación.");
        }
    }

    public function collection()
    {
        // Consulta los datos de la relación cliente-RFV
        $datos = DB::table('r_cliente_rfv')
            ->where('idOperador', $this->idOperador)
            ->where('idFabricante', $this->idFabricante)
            ->get(['id_cliente','id_RFV']);

        return $datos->isNotEmpty() ? new Collection($datos) : new Collection([['No hay datos']]);
    }

    public function title(): string
    {
        return 'Cliente_RFV';
    }

    public function headings(): array
    {
        return [
            'id_cliente',
            'id_RFV'
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $this->handleAfterSheet($event);
            },
        ];
    }

    /**
     * Aplica estilos, formato y validaciones después de crear la hoja.
     */
    public function handleAfterSheet(AfterSheet $event)
    {
        $idOperador = $this->idOperador;
        $idFabricante = $this->idFabricante;

        // Estilos de fondo amarillo y texto centrado
        $fondo = [
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => 'FFFFFF00',
                ],
            ],
        ];

        // Bordes finos
        $bordes = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ]
            ],
        ];

        // Autofiltro y ajuste de columnas principales
        $event->sheet->setAutoFilter(
            $event->sheet->calculateWorksheetDimension()
        );
        $event->sheet->getColumnDimension('A')->setAutoSize(true);
        $event->sheet->getColumnDimension('B')->setAutoSize(true);

        // Clientes (referencia)
        $clientes = TPersona::withoutGlobalScopes()
            ->where('idOperador', $idOperador)
            ->where('idFabricante', $idFabricante)
            ->where('idgrupo_persona', 'CLI')
            ->get(['idPersona', 'nombre_completo_razon_social'])
            ->toArray();

        // RFV (referencia)
        $rfvs = TPersona::withoutGlobalScopes()
            ->where('idOperador', $idOperador)
            ->where('idFabricante', $idFabricante)
            ->where('idgrupo_persona', 'RFV')
            ->get(['idPersona', 'nombre_completo_razon_social'])
            ->toArray();

        // Inserta clientes en la hoja, desde la celda D3
        $event->sheet->getDelegate()->fromArray(
            $clientes,
            NULL,
            'D3'
        );
        // Encabezados y estilos para clientes
        $event->sheet->mergeCells('D1:E1');
        $event->sheet->setCellValue('D1', 'Clientes');
        $event->sheet->setCellValue('D2', 'ID');
        $event->sheet->setCellValue('E2', 'NOMBRE');
        $event->sheet->getColumnDimension('D')->setAutoSize(true);
        $event->sheet->getColumnDimension('E')->setAutoSize(true);

        // Inserta RFV en la hoja, desde la celda G3
        $event->sheet->getDelegate()->fromArray(
            $rfvs,
            NULL,
            'G3'
        );
        // Encabezados y estilos para RFV
        $event->sheet->mergeCells('G1:H1');
        $event->sheet->setCellValue('G1', 'Representantes de venta');
        $event->sheet->setCellValue('G2', 'ID');
        $event->sheet->setCellValue('H2', 'NOMBRE');
        $event->sheet->getColumnDimension('G')->setAutoSize(true);
        $event->sheet->getColumnDimension('H')->setAutoSize(true);

        // Bordes para clientes
        $lengthClientes = count($clientes);
        $event->sheet->getStyle('D1:E'.($lengthClientes + 2))->applyFromArray($bordes);

        // Bordes para RFV
        $lengthRFV = count($rfvs);
        $event->sheet->getStyle('G1:H'.($lengthRFV + 2))->applyFromArray($bordes);

        // Validación de datos para columna A (id_cliente)
        $event->sheet->setDataValidation(
            'A:A',
            (new DataValidation())
                ->setType(DataValidation::TYPE_LIST)
                ->setShowDropDown(true)
                ->setErrorStyle(DataValidation::STYLE_STOP)
                ->setShowErrorMessage(true)
                ->setFormula1("Cliente_RFV!$"."D"."$3:D$".($lengthClientes + 2))
        );

        // Validación de datos para columna B (id_RFV)
        $event->sheet->setDataValidation(
            'B:B',
            (new DataValidation())
                ->setType(DataValidation::TYPE_LIST)
                ->setShowDropDown(true)
                ->setErrorStyle(DataValidation::STYLE_STOP)
                ->setShowErrorMessage(true)
                ->setFormula1("Cliente_RFV!$"."G"."$3:G$".($lengthRFV + 2))
        );

        // Estilos de fondo y bordes a encabezados de referencia
        $event->sheet->getStyle('D1:E2')->applyFromArray($fondo);
        $event->sheet->getStyle('G1:H2')->applyFromArray($fondo);

        // Estilos de fondo y bordes a encabezados principales
        $event->sheet->getStyle('A1:B1')->applyFromArray($fondo);
        $event->sheet->getStyle('A1:B1')->applyFromArray($bordes);

        // Reglas de formato (opcional)
        $event->sheet->mergeCells('J1:N1');
        $event->sheet->setCellValue('J1', 'REGLAS DE FORMATO');
        $event->sheet->mergeCells('J2:N2');
        $event->sheet->setCellValue('J2', 'Los clientes y RFV son los que están aquí listados');
        $event->sheet->getStyle('J1:N2')->applyFromArray($fondo);
        $event->sheet->getStyle('J1:N2')->applyFromArray($bordes);

    }
}
