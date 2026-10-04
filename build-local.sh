#!/usr/bin/env bash
# ==============================================================================
# Script para compilar el frontend localmente antes de subir a GitHub
# ==============================================================================

set -e

echo "🔨 Limpiando assets antiguos y compilando frontend de producción..."
rm -rf public/app/assets
cd admin-starter-kit
pnpm run build
cd ..

echo "✅ Compilación terminada en public/app. Listo para git commit y git push."
