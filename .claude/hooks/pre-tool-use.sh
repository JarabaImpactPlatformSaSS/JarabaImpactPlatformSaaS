#!/usr/bin/env bash
# =============================================================================
# pre-tool-use.sh — Proteccion de archivos criticos para Claude Code
#
# PROPOSITO: Prevenir modificaciones accidentales a archivos criticos del
# proyecto por parte del agente Claude Code. Se ejecuta ANTES de que Claude
# modifique un archivo via Edit o Write.
#
# EXIT CODES:
#   0 = Permitir la operacion (opcionalmente con JSON decision)
#   2 = Bloquear la operacion (stderr se muestra como error)
#
# INPUT: JSON via stdin con tool_name y tool_input
# =============================================================================

set -euo pipefail

# Leer JSON de stdin
INPUT=$(cat)

# Extraer tool_input.file_path del JSON
FILE_PATH=$(echo "$INPUT" | python3 -c "
import sys, json
data = json.load(sys.stdin)
ti = data.get('tool_input', {})
print(ti.get('file_path', ti.get('content', {}).get('file_path', '')))
" 2>/dev/null || echo "")

# Si no hay file_path, permitir (no es una operacion de archivo)
if [ -z "$FILE_PATH" ]; then
  exit 0
fi

# Obtener ruta relativa al proyecto
PROJECT_DIR="${CLAUDE_PROJECT_DIR:-$(pwd)}"
REL_PATH="${FILE_PATH#$PROJECT_DIR/}"

# =========================================================================
# ARCHIVOS PROTEGIDOS — BLOQUEAR edicion
# =========================================================================
BLOCKED_FILES=(
  "composer.lock"
  ".lando.yml"
  "web/sites/default/settings.php"
  "config/deploy/settings.secrets.php"
  "phpstan-baseline.neon"
  "web/autoload.php"
  "web/index.php"
)

for blocked in "${BLOCKED_FILES[@]}"; do
  if [ "$REL_PATH" = "$blocked" ]; then
    echo '{"hookSpecificOutput":{"hookEventName":"PreToolUse","permissionDecision":"deny","permissionDecisionReason":"Archivo protegido: '"$blocked"'. Este archivo no debe ser modificado por Claude Code. Usa las herramientas apropiadas (composer update, lando rebuild, etc.)."}}' >&2
    exit 2
  fi
done

# =========================================================================
# ARCHIVOS DE ADVERTENCIA — Permitir pero con aviso
# =========================================================================

# Master docs protegidos por DOC-GUARD-001
if echo "$REL_PATH" | grep -qE '^docs/0[07]_.*\.md$'; then
  echo '{"hookSpecificOutput":{"hookEventName":"PreToolUse","permissionDecision":"ask","permissionDecisionReason":"Este es un master doc protegido por DOC-GUARD-001. Usa Edit incremental (NUNCA Write completo). Pre-commit hook verificara umbrales de lineas."}}'
  exit 0
fi

# Workflows de CI/CD
if echo "$REL_PATH" | grep -qE '^\.github/workflows/.*\.yml$'; then
  echo '{"hookSpecificOutput":{"hookEventName":"PreToolUse","permissionDecision":"ask","permissionDecisionReason":"Archivo de CI/CD. Modificar workflows puede afectar el pipeline completo. Verifica que los cambios son intencionales."}}'
  exit 0
fi

# settings.local.json de Claude (permisos del usuario)
if [ "$REL_PATH" = ".claude/settings.local.json" ]; then
  echo '{"hookSpecificOutput":{"hookEventName":"PreToolUse","permissionDecision":"ask","permissionDecisionReason":"Archivo de configuracion local de Claude Code. Contiene permisos personales del usuario."}}'
  exit 0
fi

# =========================================================================
# TODO LO DEMAS — Permitir sin restriccion
# =========================================================================
exit 0
