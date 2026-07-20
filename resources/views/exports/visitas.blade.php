<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1a1a1a; padding: 20px; }
    h1 { font-size: 13px; font-weight: bold; margin-bottom: 4px; }
    p.sub { font-size: 8px; color: #555; margin-bottom: 14px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
    thead tr { background: #014a99; color: #fff; }
    thead th { padding: 5px 6px; text-align: left; font-size: 8px; font-weight: bold; }
    tbody tr:nth-child(even) { background: #f0f4fb; }
    tbody td { padding: 4px 6px; border-bottom: 1px solid #dde3ee; }
    h2 { font-size: 11px; font-weight: bold; margin: 18px 0 6px; }
  </style>
</head>
<body>
  <h1>Reporte de visitas</h1>
  <p class="sub">{{ $fechaInicio }} — {{ $fechaFin }} &nbsp;|&nbsp; Generado: {{ now()->format('d/m/Y H:i') }}</p>

  <table>
    <thead>
      <tr>
        <th>Nº Rep.</th><th>RFV</th><th>Cliente</th><th>Ciudad</th>
        <th>Estado</th><th>Fecha</th><th>Actividad</th><th>Comentarios</th>
        <th>Lat</th><th>Lng</th>
      </tr>
    </thead>
    <tbody>
      @foreach($visitas as $v)
      <tr>
        <td>{{ $v['reporte'] }}</td>
        <td>{{ $v['rfv'] }}</td>
        <td>{{ $v['cliente'] }}</td>
        <td>{{ $v['ciudad'] }}</td>
        <td>{{ $v['estado'] }}</td>
        <td>{{ $v['fecha'] }}</td>
        <td>{{ $v['actividad'] }}</td>
        <td>{{ $v['observaciones'] }}</td>
        <td>{{ $v['coordenadas_l'] }}</td>
        <td>{{ $v['coordenadas_a'] }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <h2>Muestras entregadas</h2>
  <table>
    <thead>
      <tr>
        <th>Reporte</th><th>Cliente</th><th>Material</th><th>Cantidad</th>
      </tr>
    </thead>
    <tbody>
      @foreach($muestras as $m)
      <tr>
        <td>{{ $m->Reporte }}</td>
        <td>{{ $m->Cliente }}</td>
        <td>{{ $m->Material }}</td>
        <td>{{ $m->Cantidad }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>