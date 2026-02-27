#!/bin/bash

# -----------------------------------------------------------------------------
# DETECCIÓN DE IP UNIVERSAL (Compatible con Debian, Fedora, Arch, Ubuntu...)
# -----------------------------------------------------------------------------
# Explicación técnica: 
# 'ip route get 1.1.1.1' pregunta al kernel qué interfaz usaría para salir a internet.
# 'awk' busca la palabra "src" y toma el siguiente valor (la IP).
# Esto ignora interfaces de Docker, VPNs y Loopbacks automáticamente.
# -----------------------------------------------------------------------------

IP=$(ip route get 1.1.1.1 2>/dev/null | awk '{for(i=1;i<=NF;i++) if ($i=="src") print $(i+1)}')

# Fallback de emergencia (por si no hay internet ni ruta por defecto)
if [ -z "$IP" ]; then
    # Busca la primera IP que no sea local
    IP=$(ip -4 addr 2>/dev/null | grep -v "127.0.0.1" | grep -oP '(?<=inet\s)\d+(\.\d+){3}' | head -n 1)
fi

# Si aun así falla (sistema muy roto), usa localhost
if [ -z "$IP" ]; then
    IP="127.0.0.1"
    echo "⚠️  No se pudo detectar IP de red. Usando modo local."
else
    echo "✅ IP detectada ($HOSTTYPE): $IP"
fi

# -----------------------------------------------------------------------------
# CONFIGURACIÓN DE ENTORNO
# -----------------------------------------------------------------------------

# Actualizar APP_URL en .env
if [ -f .env ]; then
    sed -i "s|^APP_URL=.*|APP_URL=http://$IP:8080|" .env
    
    # Actualizar/Crear VITE_HMR_HOST
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

# Exportamos las variables explícitamente para este comando
APP_URL="http://$IP:8080" VITE_HMR_HOST="$IP" docker compose up -d --remove-orphans

echo ""
echo "============================================================"
echo "👉 ACCESO LOCAL:   http://localhost:8080"
echo "👉 ACCESO RED:     http://$IP:8080"
echo "============================================================"
echo "💡 Si tus compañeros no pueden entrar, revisa el FIREWALL."
echo "   - Fedora: sudo firewall-cmd --add-port=8080/tcp --permanent"
echo "   - Debian/UFW: sudo ufw allow 8080/tcp"
echo "============================================================"