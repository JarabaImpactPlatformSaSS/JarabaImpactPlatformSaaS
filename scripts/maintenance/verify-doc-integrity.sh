#!/bin/bash
# verify-doc-integrity.sh — Verificación de integridad de documentos maestros
# DOC-THRESHOLD-001: Umbral mínimo de líneas por documento
# DOC-GUARD-001: Máximo 10% de pérdida relativa vs HEAD
#
# Uso:
#   ./scripts/maintenance/verify-doc-integrity.sh          # Verificar todos
#   ./scripts/maintenance/verify-doc-integrity.sh --strict  # Modo estricto (falla en warnings)
#
# Salida: 0 = OK, 1 = Error (umbral violado o pérdida >10%)

set -euo pipefail

STRICT=${1:-""}

declare -A DOC_THRESHOLDS=(
    ["docs/00_DIRECTRICES_PROYECTO.md"]=2000
    ["docs/00_DOCUMENTO_MAESTRO_ARQUITECTURA.md"]=2400
    ["docs/00_INDICE_GENERAL.md"]=2000
    ["docs/00_FLUJO_TRABAJO_CLAUDE.md"]=700
)

MAX_LOSS_PCT=10

errors=0
warnings=0

echo "=== Verificación de Integridad Documental ==="
echo "    DOC-THRESHOLD-001 + DOC-GUARD-001 | $(date -Iseconds)"
echo ""

for file in "${!DOC_THRESHOLDS[@]}"; do
    threshold="${DOC_THRESHOLDS[$file]}"

    if [ ! -f "$file" ]; then
        echo "  ERROR: $file no existe"
        errors=$((errors + 1))
        continue
    fi

    lines=$(wc -l < "$file")

    # CHECK 1: Absolute threshold (DOC-THRESHOLD-001).
    if [ "$lines" -lt "$threshold" ]; then
        echo "  FAIL: $file"
        echo "        Líneas: $lines (mínimo: $threshold) — UMBRAL VIOLADO"
        errors=$((errors + 1))
        continue
    elif [ "$lines" -lt $((threshold + 100)) ]; then
        echo "  WARN: $file"
        echo "        Líneas: $lines (mínimo: $threshold) — Cerca del umbral"
        warnings=$((warnings + 1))
    else
        echo "  OK:   $file"
        echo "        Líneas: $lines (mínimo: $threshold)"
    fi

    # CHECK 2: Relative loss vs HEAD (DOC-GUARD-001).
    prev_lines=$(git show HEAD:"$file" 2>/dev/null | wc -l || echo 0)
    if [ "$prev_lines" -gt 0 ] && [ "$lines" -lt "$prev_lines" ]; then
        loss=$((prev_lines - lines))
        loss_pct=$((loss * 100 / prev_lines))
        if [ "$loss_pct" -gt "$MAX_LOSS_PCT" ]; then
            echo "  FAIL: $file"
            echo "        Pérdida relativa: ${loss_pct}% ($prev_lines → $lines) — MAX ${MAX_LOSS_PCT}% EXCEDIDO"
            errors=$((errors + 1))
        elif [ "$loss_pct" -gt 5 ]; then
            echo "  WARN: $file"
            echo "        Pérdida relativa: ${loss_pct}% ($prev_lines → $lines) — Vigilar"
            warnings=$((warnings + 1))
        fi
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
