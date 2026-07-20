#!/bin/bash

# -----------------------------------------------------------------------------
# DETECCIÓN DE IP UNIVERSAL (Compatible con Debian, Fedora, Arch, Ubuntu...)
# -----------------------------------------------------------------------------
IP=$(ip route get 1.1.1.1 2>/dev/null | awk '{for(i=1;i<=NF;i++) if ($i=="src") print $(i+1)}')

if [ -z "$IP" ]; then
    IP=$(ip -4 addr 2>/dev/null | grep -v "127.0.0.1" | grep -oP '(?<=inet\s)\d+(\.\d+){3}' | head -n 1)
fi

if [ -z "$IP" ]; then
    IP="127.0.0.1"
    echo "⚠️  No se pudo detectar IP de red. Usando modo local."
else
    echo "✅ IP detectada ($HOSTTYPE): $IP"
fi

# -----------------------------------------------------------------------------
# CONFIGURACIÓN DE ENTORNO
# -----------------------------------------------------------------------------
if [ -f .env ]; then
    sed -i "s|^APP_URL=.*|APP_URL=http://$IP:8080|" .env

    if grep -q "VITE_HMR_HOST=" .env; then
        sed -i "s|^VITE_HMR_HOST=.*|VITE_HMR_HOST=$IP|" .env
    else
        echo "" >> .env
        echo "VITE_HMR_HOST=$IP" >> .env
    fi
    echo "🔄 Configuración actualizada para: $IP"
else
    echo "❌ ERROR: No se encontró el archivo .env"
    exit 1
fi

# -----------------------------------------------------------------------------
# INICIO DE DOCKER
# -----------------------------------------------------------------------------
echo "🚀 Levantando contenedores..."
APP_URL="http://$IP:8080" VITE_HMR_HOST="$IP" docker compose up -d --remove-orphans

# -----------------------------------------------------------------------------
# POST-CONFIGURACIÓN (Key Generate & PWA Build)
# -----------------------------------------------------------------------------
echo "🔧 Ejecutando tareas de configuración final..."

# Generar la APP_KEY (solo si no existe o para asegurar configuración)
# Usamos -T para evitar problemas de terminal en scripts de automatización
docker compose exec -T app php artisan key:generate --force

echo "📦 Compilando assets de producción para PWA..."
# Ejecutamos el build y eliminamos el archivo 'hot' inmediatamente después
docker compose exec -T node npm run build && rm -f public/hot

echo ""
echo "============================================================"
echo "👉 ACCESO LOCAL:   http://localhost:8080"
echo "👉 ACCESO RED:     http://$IP:8080"
echo "============================================================"
echo "✅ PWA Compilada y Service Worker listo."
echo "💡 Si tus compañeros no pueden entrar, revisa el FIREWALL."
echo "   - Fedora: sudo firewall-cmd --add-port=8080/tcp --permanent"
echo "============================================================"
