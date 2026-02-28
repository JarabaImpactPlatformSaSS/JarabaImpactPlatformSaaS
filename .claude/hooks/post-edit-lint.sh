#!/usr/bin/env bash
# =============================================================================
# post-edit-lint.sh — Lint automatico post-edicion para Claude Code
#
# PROPOSITO: Ejecutar validaciones ligeras inmediatamente despues de que Claude
# edita un archivo, sin esperar al commit. Detecta problemas comunes antes de
# que se acumulen. Debe completarse en < 5 segundos.
#
# EXIT CODES:
#   0 = OK (lint pasado o no aplicable)
#   2 = Error de lint detectado (feedback al agente)
#
# INPUT: JSON via stdin con tool_name y tool_input
# =============================================================================

set -euo pipefail

# Leer JSON de stdin
INPUT=$(cat)

# Extraer file_path
FILE_PATH=$(echo "$INPUT" | python3 -c "
import sys, json
data = json.load(sys.stdin)
ti = data.get('tool_input', {})
print(ti.get('file_path', ''))
" 2>/dev/null || echo "")

# Si no hay file_path, salir OK
if [ -z "$FILE_PATH" ] || [ ! -f "$FILE_PATH" ]; then
  exit 0
fi

# Obtener extension
EXT="${FILE_PATH##*.}"
PROJECT_DIR="${CLAUDE_PROJECT_DIR:-$(pwd)}"
ERRORS=""

case "$EXT" in
  # =======================================================================
  # PHP: Verificar sintaxis y patrones criticos
  # =======================================================================
  php)
    # 1. Verificar sintaxis PHP (rapido, < 1s)
    SYNTAX_CHECK=$(php -l "$FILE_PATH" 2>&1) || {
      ERRORS="Error de sintaxis PHP: $SYNTAX_CHECK"
    }

    # 2. Verificar patrones criticos (grep rapido)
    if [ -z "$ERRORS" ]; then
      # Detectar extends ContentEntityForm (debe ser PremiumEntityFormBase)
      if grep -qn 'extends ContentEntityForm' "$FILE_PATH" 2>/dev/null; then
        ERRORS="PREMIUM-FORMS-PATTERN-001: Entity form extiende ContentEntityForm. DEBE extender PremiumEntityFormBase."
      fi

      # Detectar eval/exec/shell_exec (baneados por phpstan-security)
      if grep -qnE '\beval\s*\(|\bexec\s*\(|\bshell_exec\s*\(' "$FILE_PATH" 2>/dev/null; then
        ERRORS="${ERRORS:+$ERRORS\n}SECURITY: Uso de eval()/exec()/shell_exec() detectado. Prohibido por phpstan-security.neon."
      fi
    fi
    ;;

  # =======================================================================
  # SCSS: Verificar patrones de color y consolidacion
  # =======================================================================
  scss)
    # 1. Detectar colores hex hardcoded (debe ser var(--ej-*))
    # Excluir: fallbacks dentro de var(), variables SCSS ($var), comentarios
    HEX_ISSUES=$(grep -n '#[0-9a-fA-F]\{3,8\}\b' "$FILE_PATH" 2>/dev/null | \
      grep -v 'var(--ej-' | \
      grep -v '^\s*//' | \
      grep -v '^\s*\*' | \
      grep -v '^\s*\$' | \
      head -3) || true

    if [ -n "$HEX_ISSUES" ]; then
      ERRORS="CSS-VAR-ALL-COLORS-001: Colores hex hardcoded detectados. Usar var(--ej-*, #fallback):\n$HEX_ISSUES"
    fi

    # 2. Detectar @import (debe ser @use)
    if grep -qn '@import ' "$FILE_PATH" 2>/dev/null; then
      ERRORS="${ERRORS:+$ERRORS\n}SCSS: Usa @import (deprecado). Migrar a @use (Dart Sass moderno)."
    fi

    # 3. Detectar rgba() (debe ser color-mix)
    if grep -qnE 'rgba\(\s*#' "$FILE_PATH" 2>/dev/null; then
      ERRORS="${ERRORS:+$ERRORS\n}SCSS-COLORMIX-001: Usa rgba() con hex. Migrar a color-mix(in srgb, var(--ej-*) pct%, transparent)."
    fi
    ;;

  # =======================================================================
  # JS: Verificar patrones criticos
  # =======================================================================
  js)
    # 1. Detectar URLs hardcoded (debe ser drupalSettings)
    if grep -qnE "fetch\s*\(\s*['\"]/(es|api)/" "$FILE_PATH" 2>/dev/null; then
      ERRORS="ROUTE-LANGPREFIX-001: URL hardcoded en fetch(). Usar drupalSettings para obtener URLs del servidor."
    fi

    # 2. Detectar innerHTML sin checkPlain
    if grep -qn 'innerHTML\s*=' "$FILE_PATH" 2>/dev/null; then
      if ! grep -q 'Drupal.checkPlain\|checkPlain' "$FILE_PATH" 2>/dev/null; then
        ERRORS="${ERRORS:+$ERRORS\n}INNERHTML-XSS-001: innerHTML usado sin Drupal.checkPlain(). Verificar que los datos insertados estan sanitizados."
      fi
    fi
    ;;

  # =======================================================================
  # Twig: Verificar patrones criticos
  # =======================================================================
  twig)
    # 1. Detectar |raw sin contexto seguro
    RAW_USES=$(grep -n '|raw' "$FILE_PATH" 2>/dev/null | \
      grep -v '{#' | \
      grep -v '^\s*{#' | \
      head -3) || true

    if [ -n "$RAW_USES" ]; then
      ERRORS="AUDIT-SEC-003: |raw detectado en template. Verificar que el contenido fue sanitizado en PHP:\n$RAW_USES"
    fi

    # 2. Detectar textos sin {% trans %}
    # Solo para strings literales en espanol fuera de atributos
    UNTRANS=$(grep -nE '>\s*[A-ZÁÉÍÓÚ][a-záéíóúñ ]{5,}[^{]*</' "$FILE_PATH" 2>/dev/null | \
      grep -v 'trans' | \
      grep -v '{#' | \
      head -3) || true

    if [ -n "$UNTRANS" ]; then
      ERRORS="${ERRORS:+$ERRORS\n}I18N: Posibles textos sin {% trans %} detectados. Verificar:\n$UNTRANS"
    fi
    ;;
esac

# Reportar errores si los hay
if [ -n "$ERRORS" ]; then
  echo -e "$ERRORS" >&2
  exit 2
fi

exit 0
