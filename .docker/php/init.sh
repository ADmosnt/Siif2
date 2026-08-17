#!/bin/bash
set -euo pipefail

echo "=== Iniciando contenedor Laravel ==="

# ---- Permisos mínimos seguros
find /var/www/storage /var/www/bootstrap/cache -type d -exec chmod 775 {} \; 2>/dev/null || true
find /var/www/storage /var/www/bootstrap/cache -type f -exec chmod 664 {} \; 2>/dev/null || true
chmod -R 755 /var/www/public 2>/dev/null || true
chown -R laravel:laravel /var/www/storage /var/www/bootstrap/cache /var/www/public 2>/dev/null || true

# ---- Composer (idempotente)
if [ ! -f /var/www/vendor/autoload.php ] || [ ! -d /var/www/vendor ]; then
  echo "Instalando dependencias de Composer..."
  composer install --no-interaction --optimize-autoloader --no-dev
else
  echo "Dependencias de Composer ya instaladas."
fi

# ---- Espera por DB (puerto + PDO contra la BD real)
DB_READY=false
DB_HOST="${DB_HOST:-db}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_DATABASE:-}"
DB_USER="${DB_USERNAME:-}"
DB_PASS="${DB_PASSWORD:-}"

if [ -n "$DB_HOST" ] && [ -n "$DB_NAME" ] && [ -n "$DB_USER" ]; then
  echo "Verificando conexión a la base de datos..."
  timeout=90
  counter=0
  while [ $counter -lt $timeout ]; do
    if command -v nc >/dev/null 2>&1 && nc -z "$DB_HOST" "$DB_PORT" >/dev/null 2>&1; then
      echo "✅ Puerto de base de datos accesible!"
      # ¡OJO!: en -r no se usan etiquetas <?php
      if php -r 'try{$pdo=new PDO("mysql:host='"$DB_HOST"';port='"$DB_PORT"';dbname='"$DB_NAME"'", "'"$DB_USER"'", "'"$DB_PASS"'");$pdo->query("SELECT 1");exit(0);}catch(PDOException $e){exit(1);}'; then
        echo "✅ Conexión a la base de datos exitosa!"
        DB_READY=true
        break
      else
        echo "⏳ Puerto accesible pero base de datos aún no lista..."
      fi
    fi
    counter=$((counter+1))
    echo "⏳ Esperando por la base de datos... ($counter/$timeout)"
    sleep 1
  done
  if [ "$DB_READY" != true ]; then
    echo "❌ Timeout: La base de datos no está disponible después de $timeout segundos"
  fi
else
  echo "⚠️  Variables de base de datos incompletas; omitiendo verificación."
fi

# ---- Migraciones sólo si la DB está lista Y están habilitadas
# RUN_MIGRATIONS=false (lo que pone docker-compose.prod.yml) evita que cada
# arranque del contenedor le cambie el esquema a la base de datos. En
# produccion esa base puede estar compartida con otra instalacion en vivo, y
# migrar sin supervision puede romperla. Ahi se corre a mano:
#   php artisan migrate:status   (ver que falta)
#   php artisan migrate --force  (aplicarlas)
RUN_MIGRATIONS="${RUN_MIGRATIONS:-true}"

if [ "$RUN_MIGRATIONS" != "true" ]; then
  echo "⏭️  RUN_MIGRATIONS=$RUN_MIGRATIONS, omitiendo migraciones automáticas."
elif [ "$DB_READY" = true ]; then
  echo "Ejecutando migraciones de base de datos..."
  php artisan migrate --force || true
else
  echo "⚠️  Base de datos no disponible, omitiendo migraciones."
fi

# ---- Limpiezas/optimizaciones
php artisan optimize:clear || true
[ "${APP_ENV:-local}" = "production" ] && php artisan optimize || true

echo "✅ Inicialización completada exitosamente!"
echo "🚀 Iniciando PHP-FPM..."
exec php-fpm
