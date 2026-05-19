<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1a1a1a; padding: 20px; }
    h1 { font-size: 13px; font-weight: bold; margin-bottom: 4px; }
    p.sub { font-size: 8px; color: #555; margin-bottom: 14px; }
    .totales { display: flex; gap: 24px; margin-bottom: 14px; }
    .totales span { font-size: 9px; }
    .totales strong { font-size: 10px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
    thead tr { background: #014a99; color: #fff; }
    thead th { padding: 5px 6px; text-align: left; font-size: 8px; font-weight: bold; }
    tbody tr:nth-child(even) { background: #f0f4fb; }
    tbody td { padding: 4px 6px; border-bottom: 1px solid #dde3ee; }
    h2 { font-size: 11px; font-weight: bold; margin: 18px 0 6px; }
  </style>
</head>
<body>
  <h1>Reporte de órdenes</h1>
  <p class="sub">{{ $fechaInicio }} — {{ $fechaFin }} &nbsp;|&nbsp; Generado: {{ now()->format('d/m/Y H:i') }}</p>

  <div class="totales">
    <span>Unidades totales: <strong>{{ number_format($totalUnidades) }}</strong></span>
    <span>Monto total: <strong>{{ number_format($montoTotal, 2) }}</strong></span>
  </div>

  <table>
    <thead>
      <tr>
        <th>Nº Orden</th><th>Cliente</th><th>Fecha</th><th>Estatus</th>
        <th>RFV</th><th>Ciudad</th><th>Estado</th><th>Mayorista</th>
        <th>Unidades</th><th>Monto</th><th>Factura</th>
      </tr>
    </thead>
    <tbody>
      @foreach($ordenes as $o)
      <tr>
        <td>{{ $o['nOrden'] }}</td>
        <td>{{ $o['cliente'] }}</td>
        <td>{{ $o['fecha'] }}</td>
        <td>{{ $o['estatus'] }}</td>
        <td>{{ $o['rfv'] }}</td>
        <td>{{ $o['ciudad'] }}</td>
        <td>{{ $o['estado'] }}</td>
        <td>{{ $o['mayoristas'] }}</td>
        <td>{{ $o['unidades'] }}</td>
        <td>{{ number_format($o['totalOrden'], 2) }}</td>
        <td>{{ $o['factura'] }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <h2>Estadísticas de productos</h2>
  <table>
    <thead>
      <tr>
        <th>Nº Orden</th><th>Producto</th><th>Cliente</th><th>RFV</th>
        <th>Mayorista</th><th>Solicitado</th><th>Monto Solicitado</th>
        <th>Conciliado</th><th>Faltante</th>
      </tr>
    </thead>
    <tbody>
      @foreach($productos as $p)
      <tr>
        <td>{{ $p->Orden }}</td>
        <td>{{ $p->Nombre }}</td>
        <td>{{ $p->Cliente }}</td>
        <td>{{ $p->RFV }}</td>
        <td>{{ $p->Mayorista }}</td>
        <td>{{ $p->Solicitado }}</td>
        <td>{{ number_format($p->Monto_Solicitado, 2) }}</td>
        <td>{{ $p->Conciliado }}</td>
        <td>{{ $p->Faltante }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>