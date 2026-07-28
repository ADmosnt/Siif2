/*
  Nombre: gerencial.blade.php
  Proceso: es una plantilla de vista (Blade), diseñada para generar 
            un Reporte Gerencial en formato PDF o HTML imprimible. 
            el código toma una lista de datos estadísticos 
            y los organiza en una tabla profesional y limpia. 
  Fecha creado: 13 de febrero del 2026
  Quien lo hizo: Bimodal - A.Lozada
  Ultima Modificacion:
  Ultima Modificacion por: 
*/

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte Gerencial</title>
    <style>
        /* Configuramos la página en horizontal y reducimos márgenes */
        @page { margin: 1cm; }
        
        body { font-family: sans-serif; font-size: 9px; color: #333; line-height: 1.2; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #004a99; padding-bottom: 10px; }
        .header h2 { color: #004a99; margin: 0; text-transform: uppercase; }
        
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th { background-color: #f2f2f2; border: 1px solid #ccc; padding: 4px; text-align: left; font-weight: bold; }
        td { border: 1px solid #eee; padding: 4px; word-wrap: break-word; }
        
        /* Clases de utilidad */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bg-gray { background-color: #f9f9f9; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Reporte de Consulta Gerencial</h2>
        <p>Generado el: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 12%;">Supervisor</th>
                <th style="width: 12%;">RFV</th>
                <th style="width: 8%;">Zona</th>
                <th style="width: 8%;">Brick</th>
                <th style="width: 8%;">Mes/Año</th>
                <th style="width: 8%;">% Cob.</th>
                <th style="width: 11%;">Cant. Esp.</th>
                <th style="width: 11%;">Monto Esp.</th>
                <th style="width: 11%;">Cant. Fact.</th>
                <th style="width: 11%;">Monto Fact.</th>
            </tr>
        </thead>
        <tbody>
            {{-- applyFilters() (gerencialController) trae estos nombres ya
                 resueltos por LEFT JOIN como columnas planas (supervisor_nombre,
                 rfv_nombre, zona_nombre, ruta_descripcion): su select() no
                 incluye idRFV/idsupervisor/idzona/idruta, asi que las
                 relaciones de Eloquent ($item->supervisor, etc.) siempre
                 resuelven null aunque se pida with(). Hay que usar los
                 alias planos, igual que la tabla en pantalla
                 (ConsultaGerencial.vue). --}}
            @foreach($estadisticas as $item)
                <tr class="{{ $loop->even ? 'bg-gray' : '' }}">
                    <td>{{ $item->supervisor_nombre ?? 'N/A' }}</td>
                    <td>{{ $item->rfv_nombre ?? 'N/A' }}</td>
                    <td>{{ $item->zona_nombre ?? 'N/A' }}</td>
                    <td>{{ $item->ruta_descripcion ?? 'N/A' }}</td>
                    <td class="text-center">{{ $item->MesRegistro }}</td>
                    <td class="text-right">{{ number_format($item->porce_cobertura, 2) }}%</td>
                    <td class="text-right">{{ number_format($item->productoEsperado, 0) }}</td>
                    <td class="text-right">${{ number_format($item->monto_esperado, 2) }}</td>
                    <td class="text-right">{{ number_format($item->productoFacturado, 0) }}</td>
                    <td class="text-right">${{ number_format($item->monto_facturado, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>