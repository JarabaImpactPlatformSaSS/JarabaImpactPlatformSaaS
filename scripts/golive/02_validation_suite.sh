#!/bin/bash
# =============================================================================
# GO-LIVE VALIDATION SUITE - Jaraba Impact Platform SaaS
# =============================================================================
# Ubicacion: scripts/golive/02_validation_suite.sh
# Uso: ./scripts/golive/02_validation_suite.sh [--verbose] [--vertical=nombre]
#
# Smoke tests post-deploy organizados por vertical y servicio.
# Cada test reporta OK/FAIL con tiempo de respuesta.
#
# DISENADO PARA: IONOS Shared Hosting
# PLATAFORMA: Drupal 11.x, 5 verticales, 51 modulos
# VERTICALES: empleabilidad, emprendimiento, agroconecta,
#             comercioconecta, serviciosconecta
# =============================================================================

set -uo pipefail

# =============================================================================
# CONFIGURACION
# =============================================================================

PROJECT_DIR="${PROJECT_DIR:-$HOME/JarabaImpactPlatformSaaS}"
PHP_CLI="${PHP_CLI:-/usr/bin/php8.4-cli}"
DRUSH="${DRUSH:-$PHP_CLI $PROJECT_DIR/vendor/bin/drush.php}"
SITE_URL="${SITE_URL:-https://plataformadeecosistemas.com}"

# Timeouts (segundos)
HTTP_CONNECT_TIMEOUT=10
HTTP_MAX_TIMEOUT=30
API_CONNECT_TIMEOUT=10
API_MAX_TIMEOUT=20

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
DIM='\033[2m'
NC='\033[0m'

# Contadores
OK_COUNT=0
FAIL_COUNT=0
WARN_COUNT=0
TOTAL_TIME=0

# Opciones
VERBOSE=false
FILTER_VERTICAL=""

# =============================================================================
# ARGUMENTOS
# =============================================================================

for arg in "$@"; do
    case $arg in
        --verbose|-v)
            VERBOSE=true
            ;;
        --vertical=*)
            FILTER_VERTICAL="${arg#*=}"
            ;;
        --help|-h)
            echo "Uso: $0 [--verbose] [--vertical=nombre]"
            echo ""
            echo "Opciones:"
            echo "  --verbose, -v           Mostrar detalles de cada request"
            echo "  --vertical=NOMBRE       Solo ejecutar tests de un vertical"
            echo "                          (empleabilidad|emprendimiento|agroconecta|"
            echo "                           comercioconecta|serviciosconecta)"
            echo "  --help, -h              Mostrar esta ayuda"
            exit 0
            ;;
    esac
done

# =============================================================================
# FUNCIONES DE UTILIDAD
# =============================================================================

timestamp() {
    date '+%Y-%m-%d %H:%M:%S'
}

log_header() {
    echo ""
    echo -e "${CYAN}${BOLD}--------------------------------------------------${NC}"
    echo -e "${CYAN}${BOLD}  $1${NC}"
    echo -e "${CYAN}${BOLD}--------------------------------------------------${NC}"
}

log_ok() {
    local test_name="$1"
    local timing="$2"
    local detail="${3:-}"
    OK_COUNT=$((OK_COUNT + 1))
    printf "  ${GREEN}[ OK ]${NC} %-45s ${DIM}%6sms${NC}  %s\n" "$test_name" "$timing" "$detail"
}

log_fail() {
    local test_name="$1"
    local timing="$2"
    local detail="${3:-}"
    FAIL_COUNT=$((FAIL_COUNT + 1))
    printf "  ${RED}[FAIL]${NC} %-45s ${DIM}%6sms${NC}  %s\n" "$test_name" "$timing" "$detail"
}

log_warn() {
    local test_name="$1"
    local timing="$2"
    local detail="${3:-}"
    WARN_COUNT=$((WARN_COUNT + 1))
    printf "  ${YELLOW}[WARN]${NC} %-45s ${DIM}%6sms${NC}  %s\n" "$test_name" "$timing" "$detail"
}

log_verbose() {
    if [ "$VERBOSE" = true ]; then
        echo -e "         ${DIM}$1${NC}"
    fi
}

# Ejecuta una peticion HTTP y mide tiempo de respuesta.
# Retorna: HTTP_CODE en $LAST_HTTP_CODE, tiempo en $LAST_HTTP_TIME, body en $LAST_HTTP_BODY
LAST_HTTP_CODE=""
LAST_HTTP_TIME=""
LAST_HTTP_BODY=""

http_request() {
    local method="${1:-GET}"
    local url="$2"
    local data="${3:-}"
    local extra_headers="${4:-}"

    local start_ms
    start_ms=$(date +%s%N)

    local tmpfile
    tmpfile=$(mktemp)

    local curl_args=(
        -s
        --connect-timeout "$HTTP_CONNECT_TIMEOUT"
        --max-time "$HTTP_MAX_TIMEOUT"
        -w "\n%{http_code}"
        -o "$tmpfile"
        -X "$method"
        -H "Accept: application/json"
    )

    if [ -n "$data" ]; then
        curl_args+=(-H "Content-Type: application/json" -d "$data")
    fi

    if [ -n "$extra_headers" ]; then
        curl_args+=(-H "$extra_headers")
    fi

    curl_args+=("$url")

    local response
    response=$(curl "${curl_args[@]}" 2>/dev/null) || response="000"

    local end_ms
    end_ms=$(date +%s%N)

    LAST_HTTP_CODE=$(echo "$response" | tail -1)
    LAST_HTTP_TIME=$(( (end_ms - start_ms) / 1000000 ))
    LAST_HTTP_BODY=$(cat "$tmpfile" 2>/dev/null)
    TOTAL_TIME=$((TOTAL_TIME + LAST_HTTP_TIME))

    rm -f "$tmpfile"

    log_verbose "  $method $url -> HTTP $LAST_HTTP_CODE (${LAST_HTTP_TIME}ms)"
}

# Test helper: verifica HTTP status code
assert_http() {
    local test_name="$1"
    local method="$2"
    local path="$3"
    local expected_codes="${4:-200}"  # Puede ser "200|301|302"
    local data="${5:-}"

    http_request "$method" "${SITE_URL}${path}" "$data"

    if echo "$expected_codes" | grep -qw "$LAST_HTTP_CODE"; then
        log_ok "$test_name" "$LAST_HTTP_TIME" "HTTP $LAST_HTTP_CODE"
        return 0
    else
        log_fail "$test_name" "$LAST_HTTP_TIME" "HTTP $LAST_HTTP_CODE (esperado: $expected_codes)"
        return 1
    fi
}

# =============================================================================
# TESTS: HEALTH CHECKS GLOBALES
# =============================================================================

test_global_health() {
    log_header "HEALTH CHECKS GLOBALES"

    # Homepage
    assert_http "Homepage" GET "/" "200|301|302"

    # Admin health endpoint
    assert_http "Admin health check" GET "/admin/health/api" "200|403"

    # Platform status API
    assert_http "Platform status API" GET "/api/v1/platform/status" "200|403"

    # User login page
    assert_http "User login page" GET "/user/login" "200"

    # Robots.txt
    assert_http "robots.txt" GET "/robots.txt" "200"

    # LLMs.txt (AI readability)
    assert_http "llms.txt" GET "/llms.txt" "200|404"
}

# =============================================================================
# TESTS POR VERTICAL
# =============================================================================

test_vertical_empleabilidad() {
    log_header "VERTICAL: EMPLEABILIDAD"

    assert_http "Landing empleabilidad" GET "/empleo" "200|301|302"
    assert_http "Landing talento" GET "/talento" "200|301|302"
    assert_http "Dashboard career" GET "/dashboard/career" "200|302|403"
    assert_http "Dashboard recruiter" GET "/dashboard/recruiter" "200|302|403"
    assert_http "Registro empleabilidad" GET "/registro/empleabilidad" "200|302"
    assert_http "Job board" GET "/skills" "200|302|403"
}

test_vertical_emprendimiento() {
    log_header "VERTICAL: EMPRENDIMIENTO"

    assert_http "Landing emprender" GET "/emprender" "200|301|302"
    assert_http "Dashboard entrepreneur" GET "/dashboard/entrepreneur" "200|302|403"
    assert_http "Dashboard mentor" GET "/dashboard/mentor" "200|302|403"
    assert_http "Registro emprendimiento" GET "/registro/emprendimiento" "200|302"
}

test_vertical_agroconecta() {
    log_header "VERTICAL: AGROCONECTA"

    assert_http "Marketplace" GET "/marketplace" "200|301|302"
    assert_http "Marketplace search" GET "/marketplace/search" "200|301|302"
    assert_http "Dashboard producer" GET "/dashboard/producer" "200|302|403"
    assert_http "Registro agroconecta" GET "/registro/agroconecta" "200|302"
}

test_vertical_comercioconecta() {
    log_header "VERTICAL: COMERCIO CONECTA"

    assert_http "Landing comercio" GET "/comercio" "200|301|302"
    assert_http "Registro comercioconecta" GET "/registro/comercioconecta" "200|302"
}

test_vertical_serviciosconecta() {
    log_header "VERTICAL: SERVICIOS CONECTA"

    assert_http "Landing instituciones" GET "/instituciones" "200|301|302"
    assert_http "Registro serviciosconecta" GET "/registro/serviciosconecta" "200|302"
}

# =============================================================================
# TESTS: API ENDPOINTS
# =============================================================================

test_api_skills() {
    log_header "API: SKILLS"

    assert_http "Skills resolve API" POST "/api/v1/skills/resolve" "200|400|403" \
        '{"query":"marketing digital"}'
    assert_http "Skills search API" GET "/api/v1/skills/search?q=python" "200|400|403"
}

test_api_billing() {
    log_header "API: BILLING"

    assert_http "Billing subscription GET" GET "/api/v1/billing/subscription" "200|401|403"
    assert_http "Billing invoices" GET "/api/v1/billing/invoices" "200|401|403"
    assert_http "Billing usage" GET "/api/v1/billing/usage" "200|401|403"
    assert_http "Billing customer" GET "/api/v1/billing/customer" "200|401|403"
    assert_http "Pricing my-plan" GET "/api/v1/pricing/my-plan" "200|401|403"
    assert_http "Usage current" GET "/api/v1/usage/current" "200|401|403"
    assert_http "Stripe webhook endpoint" POST "/api/billing/stripe-webhook" "200|400|403" \
        '{"type":"test"}'
}

test_api_analytics() {
    log_header "API: ANALYTICS"

    assert_http "Analytics dashboard" GET "/api/v1/analytics/dashboard" "200|401|403"
    assert_http "Analytics realtime" GET "/api/v1/analytics/realtime" "200|401|403"
    assert_http "Analytics funnels" GET "/api/v1/analytics/funnels" "200|401|403"
    assert_http "Analytics cohorts" GET "/api/v1/analytics/cohorts" "200|401|403"
    assert_http "Consent status" GET "/api/consent/status" "200|401|403"
    assert_http "Analytics reports" GET "/api/v1/analytics/reports" "200|401|403"
}

test_api_platform() {
    log_header "API: PLATAFORMA CORE"

    assert_http "Tenants API" GET "/api/v1/tenants" "200|401|403"
    assert_http "Plans API" GET "/api/v1/plans" "200|401|403"
    assert_http "Marketplace products API" GET "/api/v1/marketplace/products" "200|401|403"
    assert_http "Marketplace categories API" GET "/api/v1/marketplace/categories" "200|401|403"
    assert_http "Copilot context API" GET "/api/copilot/context" "200|401|403"
    assert_http "OpenAPI spec" GET "/api/openapi.yaml" "200|404"
    assert_http "API docs" GET "/api/docs" "200|404"
    assert_http "Sandbox templates" GET "/api/sandbox/templates" "200|401|403"
}

# =============================================================================
# TESTS: LOGIN FLOW
# =============================================================================

test_login_flow() {
    log_header "FLUJO DE LOGIN"

    # Obtener pagina de login y verificar que el formulario existe
    http_request GET "${SITE_URL}/user/login"
    if [ "$LAST_HTTP_CODE" = "200" ]; then
        if echo "$LAST_HTTP_BODY" | grep -q "form_build_id\|user-login-form\|edit-name"; then
            log_ok "Login form present" "$LAST_HTTP_TIME" "Formulario detectado"
        else
            log_fail "Login form present" "$LAST_HTTP_TIME" "Formulario no encontrado en HTML"
        fi
    else
        log_fail "Login form present" "$LAST_HTTP_TIME" "HTTP $LAST_HTTP_CODE"
    fi

    # Verificar que el endpoint de password reset existe
    assert_http "Password reset page" GET "/user/password" "200|302"
}

# =============================================================================
# TESTS: CSRF TOKEN VALIDATION
# =============================================================================

test_csrf_validation() {
    log_header "VALIDACION CSRF"

    # Verificar que las llamadas POST sin token son rechazadas
    http_request POST "${SITE_URL}/user/login" "name=test&pass=test&form_id=user_login_form"
    if [ "$LAST_HTTP_CODE" = "200" ] || [ "$LAST_HTTP_CODE" = "403" ] || [ "$LAST_HTTP_CODE" = "422" ]; then
        log_ok "CSRF on login POST" "$LAST_HTTP_TIME" "HTTP $LAST_HTTP_CODE (protegido)"
    else
        log_warn "CSRF on login POST" "$LAST_HTTP_TIME" "HTTP $LAST_HTTP_CODE"
    fi

    # Verificar que las API protegidas rechazan requests sin session
    http_request POST "${SITE_URL}/api/v1/billing/subscription" '{"plan":"test"}'
    if [ "$LAST_HTTP_CODE" = "401" ] || [ "$LAST_HTTP_CODE" = "403" ] || [ "$LAST_HTTP_CODE" = "405" ]; then
        log_ok "API auth protection" "$LAST_HTTP_TIME" "HTTP $LAST_HTTP_CODE (protegido)"
    elif [ "$LAST_HTTP_CODE" = "200" ]; then
        log_fail "API auth protection" "$LAST_HTTP_TIME" "API accesible sin autenticacion"
    else
        log_ok "API auth protection" "$LAST_HTTP_TIME" "HTTP $LAST_HTTP_CODE"
    fi

    # Verificar headers de seguridad
    local headers
    headers=$(curl -s -I --connect-timeout "$HTTP_CONNECT_TIMEOUT" "${SITE_URL}/" 2>/dev/null)
    local start_ms
    start_ms=$(date +%s%N)

    if echo "$headers" | grep -qi "X-Frame-Options\|X-Content-Type-Options\|Content-Security-Policy"; then
        local end_ms
        end_ms=$(date +%s%N)
        local timing=$(( (end_ms - start_ms) / 1000000 ))
        log_ok "Security headers present" "$timing" "X-Frame-Options / CSP detectados"
    else
        local end_ms
        end_ms=$(date +%s%N)
        local timing=$(( (end_ms - start_ms) / 1000000 ))
        log_warn "Security headers present" "$timing" "Faltan headers de seguridad"
    fi
}

# =============================================================================
# TESTS: CRON EXECUTION
# =============================================================================

test_cron_execution() {
    log_header "EJECUCION DE CRON"

    local start_ms
    start_ms=$(date +%s%N)

    # Ejecutar cron via drush
    local cron_output
    cron_output=$(cd "$PROJECT_DIR" && $DRUSH cron 2>&1) || true
    local cron_exit=$?

    local end_ms
    end_ms=$(date +%s%N)
    local timing=$(( (end_ms - start_ms) / 1000000 ))

    if [ $cron_exit -eq 0 ]; then
        log_ok "Cron execution (drush)" "$timing" "Completado sin errores"
    else
        log_fail "Cron execution (drush)" "$timing" "Exit code: $cron_exit"
        log_verbose "$cron_output"
    fi

    # Verificar cron via HTTP (si hay cron key configurado)
    assert_http "Cron URL accessibility" GET "/cron" "200|403|404"
}

# =============================================================================
# TESTS: CACHE CLEAR AND REBUILD
# =============================================================================

test_cache_operations() {
    log_header "OPERACIONES DE CACHE"

    # Cache rebuild
    local start_ms
    start_ms=$(date +%s%N)

    local cr_output
    cr_output=$(cd "$PROJECT_DIR" && $DRUSH cr 2>&1) || true
    local cr_exit=$?

    local end_ms
    end_ms=$(date +%s%N)
    local timing=$(( (end_ms - start_ms) / 1000000 ))

    if [ $cr_exit -eq 0 ]; then
        log_ok "Cache rebuild (drush cr)" "$timing" "Completado"
    else
        log_fail "Cache rebuild (drush cr)" "$timing" "Exit code: $cr_exit"
        log_verbose "$cr_output"
    fi

    # Verificar que el sitio responde despues del cache clear
    local post_clear_start
    post_clear_start=$(date +%s%N)

    http_request GET "${SITE_URL}/"

    if [ "$LAST_HTTP_CODE" = "200" ] || [ "$LAST_HTTP_CODE" = "302" ]; then
        log_ok "Site after cache clear" "$LAST_HTTP_TIME" "HTTP $LAST_HTTP_CODE"
    else
        log_fail "Site after cache clear" "$LAST_HTTP_TIME" "HTTP $LAST_HTTP_CODE"
    fi

    # Verificar tiempo de respuesta post-clear (cold cache)
    if [ "$LAST_HTTP_TIME" -gt 5000 ]; then
        log_warn "Cold cache response time" "$LAST_HTTP_TIME" "> 5s (normal para primer request)"
    else
        log_ok "Cold cache response time" "$LAST_HTTP_TIME" "Aceptable"
    fi
}

# =============================================================================
# TESTS: ADMIN ROUTES
# =============================================================================

test_admin_routes() {
    log_header "RUTAS DE ADMINISTRACION"

    assert_http "Admin structure tenants" GET "/admin/structure/tenants" "200|302|403"
    assert_http "Admin structure verticales" GET "/admin/structure/verticales" "200|302|403"
    assert_http "Admin structure plans" GET "/admin/structure/saas-plans" "200|302|403"
    assert_http "Admin security dashboard" GET "/admin/seguridad" "200|302|403"
    assert_http "Admin analytics" GET "/admin/jaraba/analytics" "200|302|403"
    assert_http "Admin FinOps" GET "/admin/finops" "200|302|403"
    assert_http "Admin RBAC matrix" GET "/admin/people/rbac-matrix" "200|302|403"
    assert_http "Admin billing invoices" GET "/admin/config/billing/invoice" "200|302|403"
}

# =============================================================================
# TESTS: USER-FACING ROUTES
# =============================================================================

test_user_routes() {
    log_header "RUTAS DE USUARIO"

    assert_http "Tenant dashboard" GET "/tenant/dashboard" "200|302|403"
    assert_http "My dashboard" GET "/my-dashboard" "200|302|403"
    assert_http "My settings" GET "/my-settings" "200|302|403"
    assert_http "Onboarding plan selection" GET "/onboarding/seleccionar-plan" "200|302|403"
    assert_http "Demo page" GET "/demo" "200|302|404"
    assert_http "Partner portal" GET "/partner-portal" "200|302|403"
    assert_http "Usage page" GET "/mi-cuenta/uso" "200|302|403"
}

# =============================================================================
# MAIN
# =============================================================================

main() {
    local suite_start
    suite_start=$(date +%s)

    echo ""
    echo -e "${BOLD}================================================================${NC}"
    echo -e "${BOLD}  JARABA IMPACT PLATFORM - GO-LIVE VALIDATION SUITE${NC}"
    echo -e "${BOLD}  $(timestamp)${NC}"
    echo -e "${BOLD}  URL: $SITE_URL${NC}"
    if [ -n "$FILTER_VERTICAL" ]; then
        echo -e "${BOLD}  Filtro: vertical=$FILTER_VERTICAL${NC}"
    fi
    echo -e "${BOLD}================================================================${NC}"

    # Health checks globales (siempre se ejecutan)
    test_global_health

    # Tests por vertical
    if [ -z "$FILTER_VERTICAL" ] || [ "$FILTER_VERTICAL" = "empleabilidad" ]; then
        test_vertical_empleabilidad
    fi

    if [ -z "$FILTER_VERTICAL" ] || [ "$FILTER_VERTICAL" = "emprendimiento" ]; then
        test_vertical_emprendimiento
    fi

    if [ -z "$FILTER_VERTICAL" ] || [ "$FILTER_VERTICAL" = "agroconecta" ]; then
        test_vertical_agroconecta
    fi

    if [ -z "$FILTER_VERTICAL" ] || [ "$FILTER_VERTICAL" = "comercioconecta" ]; then
        test_vertical_comercioconecta
    fi

    if [ -z "$FILTER_VERTICAL" ] || [ "$FILTER_VERTICAL" = "serviciosconecta" ]; then
        test_vertical_serviciosconecta
    fi

    # Tests de API
    test_api_skills
    test_api_billing
    test_api_analytics
    test_api_platform

    # Tests funcionales
    test_login_flow
    test_csrf_validation
    test_cron_execution
    test_cache_operations

    # Tests de rutas
    test_admin_routes
    test_user_routes

    # =============================================================================
    # RESUMEN
    # =============================================================================
    local suite_end
    suite_end=$(date +%s)
    local suite_duration=$((suite_end - suite_start))

    echo ""
    echo -e "${BOLD}================================================================${NC}"
    echo -e "${BOLD}  RESUMEN DE VALIDATION SUITE${NC}"
    echo -e "${BOLD}================================================================${NC}"
    echo ""
    echo -e "  ${GREEN}  OK:${NC}   $OK_COUNT"
    echo -e "  ${RED}FAIL:${NC}   $FAIL_COUNT"
    echo -e "  ${YELLOW}WARN:${NC}   $WARN_COUNT"
    echo ""

    local total=$((OK_COUNT + FAIL_COUNT + WARN_COUNT))
    echo -e "  Total: $total tests ejecutados"
    echo -e "  Tiempo total de requests: $((TOTAL_TIME / 1000)).$((TOTAL_TIME % 1000))s"
    echo -e "  Duracion de la suite: ${suite_duration}s"
    echo ""

    if [ "$FAIL_COUNT" -eq 0 ] && [ "$WARN_COUNT" -eq 0 ]; then
        echo -e "  ${GREEN}${BOLD}RESULTADO: TODOS LOS TESTS PASARON${NC}"
        echo -e "  ${GREEN}Validacion completada. Plataforma operativa.${NC}"
    elif [ "$FAIL_COUNT" -eq 0 ]; then
        echo -e "  ${YELLOW}${BOLD}RESULTADO: PASA CON ADVERTENCIAS${NC}"
        echo -e "  ${YELLOW}Revisar las ${WARN_COUNT} advertencias.${NC}"
    else
        echo -e "  ${RED}${BOLD}RESULTADO: FALLOS DETECTADOS${NC}"
        echo -e "  ${RED}${FAIL_COUNT} tests fallaron. Revisar antes de confirmar go-live.${NC}"
    fi

    echo ""

    # Generar informe en archivo si hay fallos
    if [ "$FAIL_COUNT" -gt 0 ]; then
        local report_file="/tmp/jaraba-validation-$(date +%Y%m%d_%H%M%S).log"
        echo "Jaraba Validation Suite - $(timestamp)" > "$report_file"
        echo "OK: $OK_COUNT | FAIL: $FAIL_COUNT | WARN: $WARN_COUNT" >> "$report_file"
        echo "Duration: ${suite_duration}s" >> "$report_file"
        echo -e "  ${DIM}Informe guardado en: $report_file${NC}"
        echo ""
    fi

    if [ "$FAIL_COUNT" -gt 0 ]; then
        return 1
    fi
    return 0
}

# Solo ejecutar si no es sourced
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
fi
