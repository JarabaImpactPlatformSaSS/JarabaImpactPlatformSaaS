#!/usr/bin/env bash
# =============================================================================
# Jaraba Impact Platform SaaS - Developer Onboarding Script
# Spec: Doc 159, seccion 7.2
#
# Uso: bash scripts/setup-dev.sh
# =============================================================================

set -e

# Colores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color
BOLD='\033[1m'

print_step() {
  echo -e "${GREEN}[STEP]${NC} $1"
}

print_warn() {
  echo -e "${YELLOW}[WARN]${NC} $1"
}

print_error() {
  echo -e "${RED}[ERROR]${NC} $1"
}

print_ok() {
  echo -e "${GREEN}[OK]${NC} $1"
}

echo -e "${BOLD}=============================================${NC}"
echo -e "${BOLD} Jaraba Impact Platform - Setup Development  ${NC}"
echo -e "${BOLD}=============================================${NC}"
echo ""

# =============================================================================
# 1. Verificar prerequisitos
# =============================================================================
print_step "Verificando prerequisitos..."

if ! command -v docker &> /dev/null; then
  print_error "Docker no esta instalado. Instalar desde https://docs.docker.com/get-docker/"
  exit 1
fi
print_ok "Docker encontrado"

if ! command -v lando &> /dev/null; then
  print_error "Lando no esta instalado. Instalar desde https://lando.dev/download/"
  exit 1
fi
print_ok "Lando encontrado"

# =============================================================================
# 2. Crear directorios necesarios
# =============================================================================
print_step "Creando directorios necesarios..."

mkdir -p web/sites/default/files/private
mkdir -p web/sites/default/files/tmp
mkdir -p .lando/backups

print_ok "Directorios creados"

# =============================================================================
# 3. Configurar .env
# =============================================================================
print_step "Configurando variables de entorno..."

if [ ! -f .env ]; then
  cp .env.example .env
  print_warn ".env creado desde .env.example - EDITAR con credenciales reales"
else
  print_ok ".env ya existe"
fi

# =============================================================================
# 4. Permisos de archivos
# =============================================================================
print_step "Configurando permisos..."

chmod -R 775 web/sites/default/files 2>/dev/null || true

print_ok "Permisos configurados"

# =============================================================================
# 5. Arrancar Lando
# =============================================================================
print_step "Arrancando Lando..."

lando start

print_ok "Lando arrancado"

# =============================================================================
# 6. Instalar dependencias
# =============================================================================
print_step "Instalando dependencias Composer..."

lando composer install --no-interaction

print_ok "Dependencias instaladas"

# =============================================================================
# 7. Health check de servicios
# =============================================================================
print_step "Verificando servicios..."

echo "  - Redis..."
lando redis-cli ping > /dev/null 2>&1 && print_ok "Redis OK" || print_warn "Redis no responde"

echo "  - Qdrant..."
lando ssh -c "curl -sf http://qdrant:6333/collections > /dev/null 2>&1" && print_ok "Qdrant OK" || print_warn "Qdrant no responde"

echo "  - Tika..."
lando ssh -c "curl -sf http://tika:9998/tika > /dev/null 2>&1" && print_ok "Tika OK" || print_warn "Tika no responde"

# =============================================================================
# 8. Limpiar cache
# =============================================================================
print_step "Limpiando cache de Drupal..."

lando drush cr 2>/dev/null || print_warn "drush cr fallo (puede ser normal en primera instalacion)"

# =============================================================================
# 9. Resumen
# =============================================================================
echo ""
echo -e "${BOLD}=============================================${NC}"
echo -e "${BOLD} Setup completado                            ${NC}"
echo -e "${BOLD}=============================================${NC}"
echo ""
echo -e "  Frontend:   ${GREEN}https://jaraba-saas.lndo.site${NC}"
echo -e "  Admin:      ${GREEN}https://jaraba-saas.lndo.site/admin${NC}"
echo -e "  PHPMyAdmin: ${GREEN}https://pma.jaraba-saas.lndo.site${NC}"
echo -e "  Mailhog:    ${GREEN}https://mail.jaraba-saas.lndo.site${NC}"
echo ""
echo -e "  ${YELLOW}Recuerda editar .env con tus API keys reales${NC}"
echo ""
