<!-- resources/views/contact.blade.php -->
<!-- Plantilla de correo para mensajes de contacto -->

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nuevo mensaje de contacto</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px 10px 0 0;
            text-align: center;
        }
        .content {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 10px 10px;
            border: 1px solid #eaeaea;
        }
        .info-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        .label {
            font-weight: bold;
            color: #667eea;
            display: block;
            margin-bottom: 5px;
        }
        .message {
            background: white;
            padding: 20px;
            border-radius: 8px;
            white-space: pre-wrap;
            line-height: 1.8;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eaeaea;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📨 Nuevo Mensaje de Contacto</h1>
        <p>Sistema SIIF - Plataforma de Gestión</p>
    </div>
    
    <div class="content">
        <div class="info-box">
            <span class="label">👤 Remitente:</span>
            {{ $data['nombre'] }}
        </div>
        
        <div class="info-box">
            <span class="label">📧 Correo Electrónico:</span>
            <a href="mailto:{{ $data['email'] }}">{{ $data['email'] }}</a>
        </div>
        
        <div class="info-box">
            <span class="label">📝 Asunto:</span>
            {{ $data['asunto'] }}
        </div>
        
        <div style="margin: 25px 0;">
            <span class="label">💬 Mensaje:</span>
            <div class="message">
                {{ $data['mensaje'] }}
            </div>
        </div>
        
        <div class="footer">
            <p>📅 Enviado el: {{ now()->format('d/m/Y H:i') }}</p>
            <p>🚀 Este mensaje fue generado automáticamente desde el sistema SIIF</p>
        </div>
    </div>
</body>
</html>