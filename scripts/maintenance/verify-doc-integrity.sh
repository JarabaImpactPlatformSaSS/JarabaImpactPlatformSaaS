#!/bin/bash
# verify-doc-integrity.sh — Verificación de integridad de documentos maestros
# DOC-THRESHOLD-001: Umbral mínimo de líneas por documento
#
# Uso:
#   ./scripts/maintenance/verify-doc-integrity.sh          # Verificar todos
#   ./scripts/maintenance/verify-doc-integrity.sh --strict  # Modo estricto (falla en warnings)
#
# Salida: 0 = OK, 1 = Error (umbral violado)

set -euo pipefail

STRICT=${1:-""}

declare -A DOC_THRESHOLDS=(
    ["docs/00_DIRECTRICES_PROYECTO.md"]=2000
    ["docs/00_DOCUMENTO_MAESTRO_ARQUITECTURA.md"]=2400
    ["docs/00_INDICE_GENERAL.md"]=2000
    ["docs/00_FLUJO_TRABAJO_CLAUDE.md"]=700
)

errors=0
warnings=0

echo "=== Verificación de Integridad Documental ==="
echo "    DOC-THRESHOLD-001 | $(date -Iseconds)"
echo ""

for file in "${!DOC_THRESHOLDS[@]}"; do
    threshold="${DOC_THRESHOLDS[$file]}"

    if [ ! -f "$file" ]; then
        echo "  ERROR: $file no existe"
        errors=$((errors + 1))
        continue
    fi

    lines=$(wc -l < "$file")

    if [ "$lines" -lt "$threshold" ]; then
        echo "  FAIL: $file"
        echo "        Líneas: $lines (mínimo: $threshold) — UMBRAL VIOLADO"
        errors=$((errors + 1))
    elif [ "$lines" -lt $((threshold + 100)) ]; then
        echo "  WARN: $file"
        echo "        Líneas: $lines (mínimo: $threshold) — Cerca del umbral"
        warnings=$((warnings + 1))
    else
        echo "  OK:   $file"
        echo "        Líneas: $lines (mínimo: $threshold)"
    fi
done

echo ""
echo "--- Resumen ---"
echo "  Documentos verificados: ${#DOC_THRESHOLDS[@]}"
echo "  Errores: $errors"
echo "  Warnings: $warnings"

if [ "$errors" -gt 0 ]; then
    echo ""
    echo "  FALLO: $errors documento(s) por debajo del umbral mínimo."
    exit 1
fi

if [ "$STRICT" = "--strict" ] && [ "$warnings" -gt 0 ]; then
    echo ""
    echo "  FALLO (modo estricto): $warnings warning(s) detectado(s)."
    exit 1
fi

echo ""
echo "  ÉXITO: Todos los documentos superan los umbrales mínimos."
exit 0
