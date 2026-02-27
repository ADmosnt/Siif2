<?php

namespace App\Exports\PersonasExports;

use App\Models\TPersona;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use App\Imports\PersonasImports\PersonaImport;

class PersonaExport implements WithMultipleSheets
{
    use Exportable;

    protected $fabricante;

    public function __construct($Fabricante = '')
    {
        $this->fabricante = $Fabricante;
    }

    public function sheets(): array
    {
        return [
            new ClienteRFVSheet($this->fabricante),
            new AyudaSheet(),
        ];
    }
}

/**
 * Clase base para hojas con estilos comunes
 */
abstract class BaseSheet implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents, WithTitle
{
    use Exportable;

    /**
     * Aplica estilos comunes a la hoja
     */
    protected function applyCommonStyles($sheet, $headerRange, $contentRange)
    {
        $yellowStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFFFFF00'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ];

        // Encabezados amarillos y negritas
        $sheet->getStyle($headerRange)->applyFromArray($yellowStyle);

        // Bordes a toda la hoja con contenido
        $sheet->getStyle($contentRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);
    }
}

class ClienteRFVSheet extends BaseSheet
{
    protected $fabricante;

    public function __construct($fabricante)
    {
        $this->fabricante = $fabricante;
    }

    public function title(): string
    {
        return 'RFV_Cliente_Mayorista';
    }

    public function collection()
    {
        return TPersona::withoutGlobalScopes()
            ->where('idFabricante', $this->fabricante)
            ->get([
                'idPersona', 'idempresa_Grupo', 'idsupervisor', 'idcadenas', 'idpais', 'ididioma', 'idmoneda',
                'cod_tipo_persona', 'idespecialidad', 'idactividad_negocio', 'idsubespecialidad', 'documento_identidad',
                'idgrupo_persona', 'idclase_persona', 'idranking', 'nombre_persona', 'apellido_persona',
                'nombre_completo_razon_social', 'sexo_genero_persona', 'fecha_nacimiento_registro', 'telefono_persona',
                'movil_persona', 'email', 'idtitulo', 'idciclos', 'idfrecuencia', 'direccion_domicilio', 'idestado',
                'idciudad', 'zona_postal', 'banco_persona', 'cuenta_principal_persona', 'banco_persona_internacional',
                'cuenta_internacional_persona', 'coordenadas_l', 'coordenadas_a', 'persona_contacto',
                'telefono_contacto', 'email_contacto', 'descuento'
            ]);
    }

    public function headings(): array
    {
        return [
            'idPersona', 'idempresa_Grupo', 'idsupervisor', 'idcadenas', 'idpais', 'ididioma', 'idmoneda',
            'cod_tipo_persona', 'idespecialidad', 'idactividad_negocio', 'idsubespecialidad', 'documento_identidad',
            'idgrupo_persona', 'idclase_persona', 'idranking', 'nombre_persona', 'apellido_persona',
            'nombre_completo_razon_social', 'sexo_genero_persona', 'fecha_nacimiento_registro', 'telefono_persona',
            'movil_persona', 'email', 'idtitulo', 'idciclos', 'idfrecuencia', 'direccion_domicilio', 'idestado',
            'idciudad', 'zona_postal', 'banco_persona', 'cuenta_principal_persona', 'banco_persona_internacional',
            'cuenta_internacional_persona', 'coordenadas_l', 'coordenadas_a', 'persona_contacto',
            'telefono_contacto', 'email_contacto', 'descuento'
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestColumn = $sheet->getHighestColumn();
                $highestRow = $sheet->getHighestRow();

                // Aplica estilos comunes
                $this->applyCommonStyles($sheet, 'A1:' . $highestColumn . '1', 'A1:' . $highestColumn . $highestRow);

                // Resalta columnas requeridas
                $personaImport = new PersonaImport($this->fabricante);
                $requiredRules = $personaImport->rules();
                $allHeadings = $this->headings();

                $requiredFieldsMap = [];
                foreach ($requiredRules as $ruleField => $rules) {
                    if (in_array('required', (array)$rules)) {
                        if (strtolower($ruleField) === 'nombre_razon_social') {
                            $requiredFieldsMap['nombre_completo_razon_social'] = true;
                        } else {
                            $requiredFieldsMap[strtolower($ruleField)] = true;
                        }
                    }
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

class AyudaSheet extends BaseSheet
{
    public function title(): string
    {
        return 'Ayuda';
    }

    public function collection()
    {
        return collect([
            // Tabla de ayuda
            ['AYUDA PARA CARGAR LA INFORMACION A SIFF'],
            ['1. Los campos que estan en color amarillo es necesario que tengan informacion'],
            ['2. El campo Idpersona siempre debe llevar un codigo que empiece por KC seguido del correlativo'],
            ['3. Los campos que estan en color azul no son necesario llenarlos'],
            ['4. Para colocar la ciudad/estado de un cliente consultar la tabla de ciudades y despues agregar el Id correspondiente'],
            ['5. Los campos: nombre_persona, apellido_persona, y nombre razon social deben de tener un maximo de 40 caracteres'],
            ['6. El nombre de la hoja debe de ser RFV_Cliente_Mayorista, sino tiene ese nombre no sera aceptado por el sistema'],
            [],
            // Tabla de especialidades
            ['IDESPECIALIDADES', ''],
            ['ID', 'DESCRIPCION'],
            ['DRO', 'Drogueria'],
            ['FAR', 'Farmaceutica'],
            ['MED', 'Medico'],
            ['PER', 'Perfumeria'],
            ['SUP', 'Supermercado'],
        ]);
    }

    public function headings(): array
    {
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Aplica estilos comunes a ambas tablas
                $this->applyCommonStyles($sheet, 'A1', 'A1:A7');
                $this->applyCommonStyles($sheet, 'A9', 'A9:B15');
                $this->applyCommonStyles($sheet, 'A10:B10', 'A10:B10');
            },
        ];
    }
}
