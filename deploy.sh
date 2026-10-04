#!/usr/bin/env bash
# ==============================================================================
# Deploy Script para Servimática App en BanaHosting (servimatica.ribersoft.com)
# ==============================================================================

set -e

PHP_BIN="/opt/cpanel/ea-php82/root/usr/bin/php"

echo "=========================================================="
echo "🚀 Iniciando despliegue de Servimática App..."
echo "=========================================================="

# 1. Poner la aplicación en mantenimiento brevemente si ya existe artisan
if [ -f "artisan" ]; then
    echo "⏸️  Activando modo mantenimiento..."
    $PHP_BIN artisan down --retry=15 || true
fi

# 2. Descargar los últimos cambios de GitHub
echo "📥 Obteniendo cambios de GitHub (main)..."
git fetch origin main
git reset --hard origin/main

# 3. Asegurar composer.phar local si no existe
if [ ! -f "composer.phar" ]; then
    echo "📦 Descargando composer.phar con PHP 8.2..."
    $PHP_BIN -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    $PHP_BIN composer-setup.php
    $PHP_BIN -r "unlink('composer-setup.php');"
fi

# 4. Instalar / actualizar dependencias de producción de PHP
echo "📦 Instalando dependencias de Composer (producción)..."
$PHP_BIN composer.phar install --no-dev --optimize-autoloader --no-interaction

# 5. Generar claves de seguridad si aún no existen
if [ -f ".env" ]; then
    if ! grep -q "^APP_KEY=base64:" .env; then
        echo "🔑 Generando APP_KEY..."
        $PHP_BIN artisan key:generate --force
    fi
    if ! grep -q "^JWT_SECRET=" .env || grep -q "^JWT_SECRET=$" .env; then
        echo "🔑 Generando JWT_SECRET..."
        $PHP_BIN artisan jwt:secret --force
    fi
fi

# 6. Migraciones de Base de Datos
echo "🗄️  Ejecutando migraciones de base de datos..."
$PHP_BIN artisan migrate --force

# 7. Asegurar symlink de almacenamiento para imágenes y vitrina 360°
echo "🔗 Verificando enlace simbólico de storage..."
$PHP_BIN artisan storage:link || true

# 8. Limpiar y regenerar cachés optimizados de producción
echo "⚡ Optimizando caché de Laravel para producción..."
$PHP_BIN artisan config:clear
$PHP_BIN artisan route:clear
$PHP_BIN artisan view:clear
$PHP_BIN artisan cache:clear

$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache

# 9. Ajustar permisos en carpetas escribibles
echo "🔒 Ajustando permisos de carpetas storage y cache..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# 10. Desactivar modo mantenimiento
if [ -f "artisan" ]; then
    echo "▶️  Desactivando modo mantenimiento..."
    $PHP_BIN artisan up || true
fi

echo "=========================================================="
echo "✅ ¡Despliegue completado con éxito en servimatica.ribersoft.com!"
echo "=========================================================="
