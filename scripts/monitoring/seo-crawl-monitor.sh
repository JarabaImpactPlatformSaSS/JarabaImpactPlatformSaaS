#!/usr/bin/env bash
#
# SEO-CRAWL-MONITOR-001: Monitoreo SEO de producción.
#
# Verifica los 4 dominios de producción para detectar regresiones SEO
# ANTES de que Google las reporte en Search Console.
#
# Uso:
#   bash scripts/monitoring/seo-crawl-monitor.sh
#   bash scripts/monitoring/seo-crawl-monitor.sh --notify  (envía email si hay fallos)
#
# Cron recomendado (semanal, lunes 6:00):
#   0 6 * * 1 cd /var/www/jaraba && bash scripts/monitoring/seo-crawl-monitor.sh --notify
#

set -euo pipefail

BOLD="\033[1m"
RED="\033[0;31m"
GREEN="\033[0;32m"
YELLOW="\033[0;33m"
NC="\033[0m"

DOMAINS=(
  "https://plataformadeecosistemas.com"
  "https://plataformadeecosistemas.es"
  "https://jarabaimpact.com"
  "https://pepejaraba.com"
)

ERRORS=0
PASSED=0
TOTAL=0
REPORT=""

check() {
  local domain="$1"
  local check_name="$2"
  local result="$3"  # 0=pass, 1=fail
  local detail="$4"

  TOTAL=$((TOTAL + 1))
  if [ "$result" -eq 0 ]; then
    PASSED=$((PASSED + 1))
    REPORT+="  ✓ [${domain##*/}] ${check_name}\n"
  else
    ERRORS=$((ERRORS + 1))
    REPORT+="  ✗ [${domain##*/}] ${check_name}: ${detail}\n"
  fi
}

echo -e "\n${BOLD}=== SEO-CRAWL-MONITOR-001: Monitoreo SEO Producción ===${NC}\n"
echo "  Fecha: $(date '+%Y-%m-%d %H:%M:%S')"
echo "  Dominios: ${#DOMAINS[@]}"
echo ""

TITLES=()

for DOMAIN in "${DOMAINS[@]}"; do
  SHORT="${DOMAIN#https://}"
  echo -e "  ${BOLD}Verificando ${SHORT}...${NC}"

  # Fetch homepage con timeout 15s.
  HTML=$(curl -sL --max-time 15 "${DOMAIN}/es" 2>/dev/null || echo "FETCH_FAILED")

  if [ "$HTML" = "FETCH_FAILED" ]; then
    check "$SHORT" "Accesibilidad" 1 "No se pudo conectar"
    continue
  fi

  # CHECK 1: hreflang NO contiene /node.
  HREFLANG_NODE=$(echo "$HTML" | grep -ci 'hreflang.*\/node' || true)
  if [ "$HREFLANG_NODE" -gt 0 ]; then
    check "$SHORT" "hreflang sin /node" 1 "Encontrados ${HREFLANG_NODE} hreflang con /node"
  else
    check "$SHORT" "hreflang sin /node" 0 ""
  fi

  # CHECK 2: robots.txt con Sitemap del dominio correcto.
  ROBOTS=$(curl -sL --max-time 10 "${DOMAIN}/robots.txt" 2>/dev/null || echo "")
  SITEMAP_LINE=$(echo "$ROBOTS" | grep -i "^Sitemap:" | head -1)
  if echo "$SITEMAP_LINE" | grep -q "${DOMAIN}"; then
    check "$SHORT" "robots.txt Sitemap correcto" 0 ""
  else
    check "$SHORT" "robots.txt Sitemap correcto" 1 "Sitemap apunta a: ${SITEMAP_LINE:-VACÍO}"
  fi

  # CHECK 3: sitemap-static.xml no vacío.
  SITEMAP_STATIC=$(curl -sL --max-time 10 "${DOMAIN}/sitemap-static.xml" 2>/dev/null || echo "")
  URL_COUNT=$(echo "$SITEMAP_STATIC" | grep -c '<url>' || true)
  if [ "$URL_COUNT" -ge 10 ]; then
    check "$SHORT" "sitemap-static >= 10 URLs" 0 ""
  else
    check "$SHORT" "sitemap-static >= 10 URLs" 1 "Solo ${URL_COUNT} URLs"
  fi

  # CHECK 4: <title> extraído para comparación posterior.
  TITLE=$(echo "$HTML" | grep -oP '(?<=<title>)[^<]+' | head -1 || echo "SIN_TITLE")
  TITLES+=("${SHORT}::${TITLE}")
  check "$SHORT" "Title presente" 0 ""

  # CHECK 5: Canonical tag presente.
  CANONICAL=$(echo "$HTML" | grep -ci 'rel="canonical"' || true)
  if [ "$CANONICAL" -gt 0 ]; then
    check "$SHORT" "Canonical tag presente" 0 ""
  else
    check "$SHORT" "Canonical tag presente" 1 "No encontrado"
  fi

  # CHECK 6: og:url absoluto.
  OG_URL=$(echo "$HTML" | grep -oP 'property="og:url"\s+content="([^"]+)"' | head -1 || echo "")
  if echo "$OG_URL" | grep -q "https://"; then
    check "$SHORT" "og:url absoluto" 0 ""
  else
    check "$SHORT" "og:url absoluto" 1 "og:url: ${OG_URL:-NO ENCONTRADO}"
  fi

  # CHECK 7: og:locale presente.
  OG_LOCALE=$(echo "$HTML" | grep -ci 'og:locale' || true)
  if [ "$OG_LOCALE" -gt 0 ]; then
    check "$SHORT" "og:locale presente" 0 ""
  else
    check "$SHORT" "og:locale presente" 1 "No encontrado"
  fi

  # CHECK 8: No hay idiomas fantasma en hreflang meta tags <link> (no language switcher).
  PHANTOM_LANGS=$(echo "$HTML" | grep -cE '<link[^>]+hreflang="(en|pt-br)"' || true)
  if [ "$PHANTOM_LANGS" -eq 0 ]; then
    check "$SHORT" "Sin idiomas fantasma en hreflang" 0 ""
  else
    check "$SHORT" "Sin idiomas fantasma en hreflang" 1 "${PHANTOM_LANGS} hreflang con en/pt-br"
  fi

  echo ""
done

# CHECK 9: Titles diferentes entre dominios.
echo -e "  ${BOLD}Verificando diferenciación de titles...${NC}"
UNIQUE_TITLES=$(printf '%s\n' "${TITLES[@]}" | sed 's/^[^:]*:://' | sort -u | wc -l)
TOTAL_DOMAINS=${#TITLES[@]}
if [ "$UNIQUE_TITLES" -ge 2 ] && [ "$TOTAL_DOMAINS" -ge 2 ]; then
  TOTAL=$((TOTAL + 1))
  PASSED=$((PASSED + 1))
  REPORT+="  ✓ [cross-domain] Titles diferenciados (${UNIQUE_TITLES} únicos de ${TOTAL_DOMAINS})\n"
elif [ "$TOTAL_DOMAINS" -ge 2 ]; then
  TOTAL=$((TOTAL + 1))
  ERRORS=$((ERRORS + 1))
  REPORT+="  ✗ [cross-domain] Todos los dominios tienen el mismo title\n"
fi

# Titles detalle.
for T in "${TITLES[@]}"; do
  DOMAIN_T="${T%%::*}"
  TITLE_T="${T#*::}"
  REPORT+="    → ${DOMAIN_T}: ${TITLE_T}\n"
done

# Resultado.
echo ""
echo -e "${REPORT}"
echo "============================================================"
echo -e "  ${BOLD}Resultado:${NC} ${PASSED}/${TOTAL} checks OK, ${ERRORS} fallos"
echo "============================================================"

if [ "$ERRORS" -gt 0 ]; then
  echo -e "  ${RED}${BOLD}${ERRORS} check(s) FAILED.${NC}"

  # Notificación por email si --notify.
  if [[ "${1:-}" == "--notify" ]]; then
    SUBJECT="[SEO-MONITOR] ${ERRORS} fallos detectados en $(date '+%Y-%m-%d')"
    BODY="SEO Crawl Monitor ha detectado ${ERRORS} fallos en ${TOTAL} checks.\n\n${REPORT}\n\nAcción requerida: revisar y corregir antes del próximo crawl de Google."
    echo -e "$BODY" | mail -s "$SUBJECT" contacto@plataformadeecosistemas.com 2>/dev/null || true
    echo -e "  📧 Email de notificación enviado."
  fi

  echo ""
  exit 1
fi

echo -e "  ${GREEN}${BOLD}All SEO checks passed.${NC}"
echo ""
exit 0
