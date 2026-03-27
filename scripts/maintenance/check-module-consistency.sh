#!/bin/bash
# =============================================================================
# MODULE CONSISTENCY CHECK — MODULE-ORPHAN-001
# =============================================================================
# Detecta discrepancias entre modulos en disco y modulos activos en
# config/sync/core.extension.yml.
#
# Uso: bash scripts/maintenance/check-module-consistency.sh
# Exit: 0 = consistente, 1 = discrepancias encontradas
#
# Similar a: scripts/check-scss-orphans.js (mismo concepto: recursos huerfanos)
# =============================================================================

set -euo pipefail

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BOLD='\033[1m'
NC='\033[0m'

# Paths
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
MODULES_DIR="$PROJECT_ROOT/web/modules/custom"
CORE_EXTENSION="$PROJECT_ROOT/config/sync/core.extension.yml"

# Modulos DEPRECATED o PENDIENTES DE HABILITAR que se excluyen del check.
# jaraba_whatsapp: Módulo nuevo pendiente de drush en tras configurar Meta Business Manager.
DEPRECATED_MODULES=(
  "jaraba_blog"
  "jaraba_whatsapp"
)

errors=0

echo ""
echo -e "${BOLD}=== MODULE CONSISTENCY CHECK (MODULE-ORPHAN-001) ===${NC}"
echo -e "  Proyecto: $PROJECT_ROOT"
echo -e "  Config:   $CORE_EXTENSION"
echo ""

# Validar que existen los paths
if [ ! -d "$MODULES_DIR" ]; then
  echo -e "${RED}ERROR: $MODULES_DIR no existe${NC}"
  exit 1
fi

if [ ! -f "$CORE_EXTENSION" ]; then
  echo -e "${RED}ERROR: $CORE_EXTENSION no existe${NC}"
  exit 1
fi

# ─────────────────────────────────────────────────────────────
# 1. Recopilar modulos en disco (incluyendo submodulos)
# ─────────────────────────────────────────────────────────────
declare -a DISK_MODULES=()

while IFS= read -r info_yml; do
  # Extraer nombre del modulo del nombre del archivo .info.yml
  module_name=$(basename "$info_yml" .info.yml)
  DISK_MODULES+=("$module_name")
done < <(find "$MODULES_DIR" -name "*.info.yml" -type f | sort)

# ─────────────────────────────────────────────────────────────
# 2. Recopilar modulos activos en core.extension.yml
# ─────────────────────────────────────────────────────────────
declare -a ACTIVE_MODULES=()

# Parsear la seccion module: del YAML (lineas que empiezan con 2 espacios + nombre + : + numero)
in_module_section=false
while IFS= read -r line; do
  if [[ "$line" == "module:" ]]; then
    in_module_section=true
    continue
  fi
  if [[ "$line" == "theme:" ]] || [[ "$line" == "profile:"* ]]; then
    in_module_section=false
    continue
  fi
  if $in_module_section; then
    # Extraer nombre del modulo (formato: "  module_name: 0")
    module_name=$(echo "$line" | sed -n 's/^  \([a-zA-Z_][a-zA-Z0-9_]*\):.*/\1/p')
    if [ -n "$module_name" ]; then
      ACTIVE_MODULES+=("$module_name")
    fi
  fi
done < "$CORE_EXTENSION"

# ─────────────────────────────────────────────────────────────
# 3. Helper: check si un modulo esta en la lista de deprecated
# ─────────────────────────────────────────────────────────────
is_deprecated() {
  local module="$1"
  for dep in "${DEPRECATED_MODULES[@]}"; do
    if [ "$module" = "$dep" ]; then
      return 0
    fi
  done
  return 1
}

# ─────────────────────────────────────────────────────────────
# 4. Detectar modulos en disco pero NO activos (huerfanos)
# ─────────────────────────────────────────────────────────────
echo -e "${BOLD}Modulos en disco pero NO activos:${NC}"
orphan_count=0

for disk_mod in "${DISK_MODULES[@]}"; do
  # Saltar deprecated
  if is_deprecated "$disk_mod"; then
    continue
  fi

  # Buscar en activos
  found=false
  for active_mod in "${ACTIVE_MODULES[@]}"; do
    if [ "$disk_mod" = "$active_mod" ]; then
      found=true
      break
    fi
  done

  if ! $found; then
    echo -e "  ${RED}[ORPHAN]${NC} $disk_mod"
    orphan_count=$((orphan_count + 1))
    errors=$((errors + 1))
  fi
done

if [ "$orphan_count" -eq 0 ]; then
  echo -e "  ${GREEN}Ninguno${NC}"
fi

# ─────────────────────────────────────────────────────────────
# 5. Detectar modulos activos sin codigo en disco (fantasma)
#    Solo para modulos custom (jaraba_*, ecosistema_*, ai_provider_*)
# ─────────────────────────────────────────────────────────────
echo ""
echo -e "${BOLD}Modulos activos sin codigo en disco:${NC}"
ghost_count=0

for active_mod in "${ACTIVE_MODULES[@]}"; do
  # Solo verificar modulos custom (jaraba_*, ecosistema_*)
  # ai_provider_* se excluye porque algunos son contrib (via Composer)
  case "$active_mod" in
    jaraba_*|ecosistema_*)
      # Buscar en disco
      found=false
      for disk_mod in "${DISK_MODULES[@]}"; do
        if [ "$active_mod" = "$disk_mod" ]; then
          found=true
          break
        fi
      done

      if ! $found; then
        echo -e "  ${RED}[GHOST]${NC} $active_mod (activo pero sin codigo)"
        ghost_count=$((ghost_count + 1))
        errors=$((errors + 1))
      fi
      ;;
  esac
done

if [ "$ghost_count" -eq 0 ]; then
  echo -e "  ${GREEN}Ninguno${NC}"
fi

# ─────────────────────────────────────────────────────────────
# 6. Resumen
# ─────────────────────────────────────────────────────────────
echo ""
echo -e "${BOLD}Resumen:${NC}"
echo -e "  Modulos en disco:    ${#DISK_MODULES[@]}"
echo -e "  Modulos activos:     ${#ACTIVE_MODULES[@]} (total en core.extension.yml)"
echo -e "  Deprecated:          ${#DEPRECATED_MODULES[@]} (${DEPRECATED_MODULES[*]})"
echo -e "  Huerfanos:           $orphan_count"
echo -e "  Fantasma:            $ghost_count"

if [ "$errors" -gt 0 ]; then
  echo ""
  echo -e "${RED}${BOLD}FALLO: $errors discrepancia(s) encontrada(s).${NC}"
  echo -e "${RED}Corregir antes de continuar. Si un modulo esta deprecated, agregarlo a DEPRECATED_MODULES en este script.${NC}"
  exit 1
else
  echo ""
  echo -e "${GREEN}${BOLD}OK: Todos los modulos son consistentes.${NC}"
  exit 0
fi
