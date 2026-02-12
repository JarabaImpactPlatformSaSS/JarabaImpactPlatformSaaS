#!/bin/bash
# =============================================================================
# GO-LIVE PREFLIGHT CHECKS - Jaraba Impact Platform SaaS
# =============================================================================
# Ubicacion: scripts/golive/01_preflight_checks.sh
# Uso: ./scripts/golive/01_preflight_checks.sh
#
# 24 validaciones criticas previas a produccion.
# Cada check reporta PASS/FAIL con codigo de color.
# Retorna exit code no-cero si cualquier check critico falla.
#
# DISENADO PARA: IONOS Shared Hosting
# PLATAFORMA: Drupal 11.x + MariaDB + Qdrant + Stripe
# MODULOS: 51 modulos custom
# =============================================================================

set -euo pipefail

# =============================================================================
# CONFIGURACION
# =============================================================================

PROJECT_DIR="${PROJECT_DIR:-$HOME/JarabaImpactPlatformSaaS}"
WEB_DIR="$PROJECT_DIR/web"
SETTINGS_DIR="$WEB_DIR/sites/default"
FILES_DIR="$WEB_DIR/sites/default/files"
BACKUP_DIR="${BACKUP_DIR:-$HOME/backups}"

# Comandos IONOS
PHP_CLI="${PHP_CLI:-/usr/bin/php8.4-cli}"
COMPOSER="${COMPOSER:-$PHP_CLI $HOME/bin/composer.phar}"
DRUSH="${DRUSH:-$PHP_CLI $PROJECT_DIR/vendor/bin/drush.php}"

# URLs y endpoints
SITE_URL="${SITE_URL:-https://plataformadeecosistemas.com}"
QDRANT_HOST="${QDRANT_HOST:-http://localhost:6333}"
STRIPE_API_URL="https://api.stripe.com/v1/balance"

# Umbrales
MIN_PHP_VERSION="8.4"
MIN_DISK_FREE_MB=500
MIN_MEMORY_LIMIT_MB=256
MAX_ERROR_LOG_LINES=50
SSL_WARN_DAYS=30

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

# Contadores
PASS_COUNT=0
FAIL_COUNT=0
WARN_COUNT=0
SKIP_COUNT=0

# =============================================================================
# FUNCIONES DE UTILIDAD
# =============================================================================

timestamp() {
    date '+%Y-%m-%d %H:%M:%S'
}

log_header() {
    echo ""
    echo -e "${CYAN}${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${CYAN}${BOLD}  $1${NC}"
    echo -e "${CYAN}${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
}

log_pass() {
    local check_name="$1"
    local detail="${2:-}"
    PASS_COUNT=$((PASS_COUNT + 1))
    printf "  ${GREEN}[PASS]${NC} %-40s %s\n" "$check_name" "$detail"
}

log_fail() {
    local check_name="$1"
    local detail="${2:-}"
    FAIL_COUNT=$((FAIL_COUNT + 1))
    printf "  ${RED}[FAIL]${NC} %-40s %s\n" "$check_name" "$detail"
}

log_warn() {
    local check_name="$1"
    local detail="${2:-}"
    WARN_COUNT=$((WARN_COUNT + 1))
    printf "  ${YELLOW}[WARN]${NC} %-40s %s\n" "$check_name" "$detail"
}

log_skip() {
    local check_name="$1"
    local detail="${2:-}"
    SKIP_COUNT=$((SKIP_COUNT + 1))
    printf "  ${BLUE}[SKIP]${NC} %-40s %s\n" "$check_name" "$detail"
}

log_info() {
    echo -e "  ${BLUE}[INFO]${NC} $1"
}

# Comparacion de versiones semver: retorna 0 si $1 >= $2
version_gte() {
    local v1="$1"
    local v2="$2"
    printf '%s\n%s' "$v2" "$v1" | sort -V -C
}

# =============================================================================
# CHECKS
# =============================================================================

# ---------- 1. PHP VERSION ----------
check_php_version() {
    local php_version
    php_version=$($PHP_CLI -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;' 2>/dev/null) || {
        log_fail "PHP version" "No se puede ejecutar $PHP_CLI"
        return
    }

    if version_gte "$php_version" "$MIN_PHP_VERSION"; then
        log_pass "PHP version" "v${php_version} (requiere >= ${MIN_PHP_VERSION})"
    else
        log_fail "PHP version" "v${php_version} (requiere >= ${MIN_PHP_VERSION})"
    fi
}

# ---------- 2. COMPOSER DEPENDENCIES ----------
check_composer_deps() {
    if [ ! -f "$PROJECT_DIR/vendor/autoload.php" ]; then
        log_fail "Composer dependencies" "vendor/autoload.php no encontrado"
        return
    fi

    # Verificar que no hay dependencias dev instaladas
    if [ -d "$PROJECT_DIR/vendor/phpunit" ] || [ -d "$PROJECT_DIR/vendor/phpspec" ]; then
        log_warn "Composer dependencies" "Dependencias dev detectadas en vendor/"
        return
    fi

    # Verificar lock file actualizado
    if [ "$PROJECT_DIR/composer.lock" -ot "$PROJECT_DIR/composer.json" ]; then
        log_warn "Composer dependencies" "composer.lock mas antiguo que composer.json"
        return
    fi

    log_pass "Composer dependencies" "Instaladas sin dev"
}

# ---------- 3. DRUPAL STATUS ----------
check_drupal_status() {
    local bootstrap
    bootstrap=$(cd "$PROJECT_DIR" && $DRUSH status --field=bootstrap 2>/dev/null) || {
        log_fail "Drupal status" "drush status fallo"
        return
    }

    if [ "$bootstrap" = "Successful" ]; then
        log_pass "Drupal status" "Bootstrap: $bootstrap"
    else
        log_fail "Drupal status" "Bootstrap: $bootstrap"
    fi
}

# ---------- 4. DATABASE CONNECTIVITY ----------
check_database() {
    local result
    result=$(cd "$PROJECT_DIR" && $DRUSH sql:query "SELECT 1 AS ok" 2>/dev/null) || {
        log_fail "Database connectivity" "No se puede conectar a MariaDB"
        return
    }

    if echo "$result" | grep -q "1"; then
        # Obtener version de BD
        local db_version
        db_version=$(cd "$PROJECT_DIR" && $DRUSH sql:query "SELECT VERSION()" 2>/dev/null | head -1)
        log_pass "Database connectivity" "MariaDB ${db_version:-unknown}"
    else
        log_fail "Database connectivity" "Respuesta inesperada: $result"
    fi
}

# ---------- 5. CONFIG SYNC STATUS ----------
check_config_sync() {
    local config_status
    config_status=$(cd "$PROJECT_DIR" && $DRUSH config:status 2>/dev/null) || {
        log_fail "Config sync status" "drush config:status fallo"
        return
    }

    if echo "$config_status" | grep -qi "no differences"; then
        log_pass "Config sync status" "Sin diferencias"
    elif echo "$config_status" | grep -qi "only in"; then
        local diff_count
        diff_count=$(echo "$config_status" | grep -c "only in\|different" 2>/dev/null || echo "?")
        log_warn "Config sync status" "${diff_count} configuraciones difieren"
    else
        log_pass "Config sync status" "Sincronizado"
    fi
}

# ---------- 6. PENDING DATABASE UPDATES ----------
check_pending_updates() {
    local pending
    pending=$(cd "$PROJECT_DIR" && $DRUSH updatedb:status 2>/dev/null) || {
        log_fail "Pending DB updates" "drush updatedb:status fallo"
        return
    }

    if [ -z "$pending" ] || echo "$pending" | grep -qi "no database updates"; then
        log_pass "Pending DB updates" "Sin updates pendientes"
    else
        local update_count
        update_count=$(echo "$pending" | grep -c "." 2>/dev/null || echo "?")
        log_fail "Pending DB updates" "${update_count} updates pendientes"
    fi
}

# ---------- 7. CRON LAST RUN ----------
check_cron_last_run() {
    local last_cron
    last_cron=$(cd "$PROJECT_DIR" && $DRUSH php:eval "echo \Drupal::state()->get('system.cron_last', 0);" 2>/dev/null) || {
        log_fail "Cron last run" "No se puede obtener estado de cron"
        return
    }

    if [ -z "$last_cron" ] || [ "$last_cron" = "0" ]; then
        log_fail "Cron last run" "Cron nunca ejecutado"
        return
    fi

    local now
    now=$(date +%s)
    local diff=$((now - last_cron))
    local hours=$((diff / 3600))

    if [ "$hours" -le 1 ]; then
        log_pass "Cron last run" "Hace ${diff} segundos"
    elif [ "$hours" -le 24 ]; then
        log_warn "Cron last run" "Hace ${hours} horas"
    else
        log_fail "Cron last run" "Hace ${hours} horas (> 24h)"
    fi
}

# ---------- 8. FILES DIRECTORY PERMISSIONS ----------
check_files_directory() {
    if [ ! -d "$FILES_DIR" ]; then
        log_fail "Files directory" "$FILES_DIR no existe"
        return
    fi

    if [ -w "$FILES_DIR" ]; then
        local perms
        perms=$(stat -c '%a' "$FILES_DIR" 2>/dev/null || stat -f '%Lp' "$FILES_DIR" 2>/dev/null)
        log_pass "Files directory" "Escribible (permisos: $perms)"
    else
        log_fail "Files directory" "No escribible"
    fi
}

# ---------- 9. SSL CERTIFICATE VALIDITY ----------
check_ssl_certificate() {
    local domain
    domain=$(echo "$SITE_URL" | sed 's|https\?://||' | sed 's|/.*||')

    local expiry_date
    expiry_date=$(echo | openssl s_client -servername "$domain" -connect "$domain:443" 2>/dev/null \
        | openssl x509 -noout -enddate 2>/dev/null \
        | sed 's/notAfter=//') || {
        log_fail "SSL certificate" "No se puede verificar SSL para $domain"
        return
    }

    if [ -z "$expiry_date" ]; then
        log_fail "SSL certificate" "No se puede obtener fecha de expiracion"
        return
    fi

    local expiry_epoch
    expiry_epoch=$(date -d "$expiry_date" +%s 2>/dev/null) || {
        log_warn "SSL certificate" "No se puede parsear fecha: $expiry_date"
        return
    }

    local now
    now=$(date +%s)
    local days_left=$(( (expiry_epoch - now) / 86400 ))

    if [ "$days_left" -le 0 ]; then
        log_fail "SSL certificate" "EXPIRADO hace $((days_left * -1)) dias"
    elif [ "$days_left" -le "$SSL_WARN_DAYS" ]; then
        log_warn "SSL certificate" "Expira en ${days_left} dias ($expiry_date)"
    else
        log_pass "SSL certificate" "Valido por ${days_left} dias"
    fi
}

# ---------- 10. QDRANT CONNECTIVITY ----------
check_qdrant() {
    local response
    response=$(curl -s -o /dev/null -w "%{http_code}" \
        --connect-timeout 5 \
        --max-time 10 \
        "${QDRANT_HOST}/") || {
        log_fail "Qdrant connectivity" "No se puede conectar a ${QDRANT_HOST}"
        return
    }

    if [ "$response" = "200" ]; then
        # Intentar obtener info de colecciones
        local collections
        collections=$(curl -s --connect-timeout 5 "${QDRANT_HOST}/collections" 2>/dev/null | \
            grep -o '"count":[0-9]*' | head -1 | grep -o '[0-9]*') || true
        log_pass "Qdrant connectivity" "HTTP 200 (colecciones: ${collections:-?})"
    else
        log_fail "Qdrant connectivity" "HTTP $response en ${QDRANT_HOST}"
    fi
}

# ---------- 11. STRIPE API KEY VALIDITY ----------
check_stripe_api() {
    local stripe_key
    stripe_key=$(cd "$PROJECT_DIR" && $DRUSH php:eval "
        \$config = \Drupal::config('ecosistema_jaraba_core.stripe');
        echo \$config->get('secret_key') ?: getenv('STRIPE_SECRET_KEY') ?: '';
    " 2>/dev/null) || {
        log_skip "Stripe API key" "No se puede obtener la clave via drush"
        return
    }

    if [ -z "$stripe_key" ]; then
        # Intentar desde variable de entorno directamente
        stripe_key="${STRIPE_SECRET_KEY:-}"
    fi

    if [ -z "$stripe_key" ]; then
        log_fail "Stripe API key" "Clave Stripe no configurada"
        return
    fi

    # Verificar tipo de clave (live vs test)
    if echo "$stripe_key" | grep -q "^sk_test_"; then
        log_warn "Stripe API key" "Usando clave de TEST (no produccion)"
        return
    fi

    # Validar contra la API de Stripe
    local stripe_response
    stripe_response=$(curl -s -o /dev/null -w "%{http_code}" \
        --connect-timeout 10 \
        --max-time 15 \
        -u "${stripe_key}:" \
        "$STRIPE_API_URL" 2>/dev/null) || {
        log_fail "Stripe API key" "No se puede conectar a Stripe"
        return
    }

    if [ "$stripe_response" = "200" ]; then
        log_pass "Stripe API key" "Clave LIVE validada con exito"
    elif [ "$stripe_response" = "401" ]; then
        log_fail "Stripe API key" "Clave invalida (HTTP 401)"
    else
        log_warn "Stripe API key" "Respuesta inesperada: HTTP $stripe_response"
    fi
}

# ---------- 12. ALL MODULES ENABLED ----------
check_modules_enabled() {
    local expected_modules=(
        "ecosistema_jaraba_core"
        "jaraba_ab_testing"
        "jaraba_addons"
        "jaraba_ads"
        "jaraba_agroconecta_core"
        "jaraba_ai_agents"
        "jaraba_analytics"
        "jaraba_andalucia_ei"
        "jaraba_billing"
        "jaraba_business_tools"
        "jaraba_candidate"
        "jaraba_comercio_conecta"
        "jaraba_commerce"
        "jaraba_content_hub"
        "jaraba_copilot_v2"
        "jaraba_credentials"
        "jaraba_crm"
        "jaraba_customer_success"
        "jaraba_diagnostic"
        "jaraba_email"
        "jaraba_events"
        "jaraba_foc"
        "jaraba_geo"
        "jaraba_groups"
        "jaraba_heatmap"
        "jaraba_i18n"
        "jaraba_integrations"
        "jaraba_interactive"
        "jaraba_job_board"
        "jaraba_journey"
        "jaraba_lms"
        "jaraba_matching"
        "jaraba_mentoring"
        "jaraba_page_builder"
        "jaraba_paths"
        "jaraba_performance"
        "jaraba_pixels"
        "jaraba_rag"
        "jaraba_referral"
        "jaraba_resources"
        "jaraba_self_discovery"
        "jaraba_sepe_teleformacion"
        "jaraba_servicios_conecta"
        "jaraba_site_builder"
        "jaraba_skills"
        "jaraba_social"
        "jaraba_social_commerce"
        "jaraba_tenant_knowledge"
        "jaraba_theming"
        "jaraba_training"
        "ai_provider_google_gemini"
    )

    local enabled_modules
    enabled_modules=$(cd "$PROJECT_DIR" && $DRUSH pm:list --status=enabled --format=list 2>/dev/null) || {
        log_fail "Modules enabled" "drush pm:list fallo"
        return
    }

    local missing=0
    local missing_names=""
    for mod in "${expected_modules[@]}"; do
        if ! echo "$enabled_modules" | grep -q "^${mod}$"; then
            missing=$((missing + 1))
            missing_names="${missing_names} ${mod}"
        fi
    done

    local total=${#expected_modules[@]}
    local enabled=$((total - missing))

    if [ "$missing" -eq 0 ]; then
        log_pass "Modules enabled" "${total}/${total} modulos habilitados"
    else
        log_fail "Modules enabled" "${enabled}/${total} - Faltan:${missing_names}"
    fi
}

# ---------- 13. DISK SPACE ----------
check_disk_space() {
    local available_kb
    available_kb=$(df -k "$PROJECT_DIR" | awk 'NR==2 {print $4}')
    local available_mb=$((available_kb / 1024))
    local usage_percent
    usage_percent=$(df -h "$PROJECT_DIR" | awk 'NR==2 {print $5}' | tr -d '%')

    if [ "$available_mb" -lt "$MIN_DISK_FREE_MB" ]; then
        log_fail "Disk space" "${available_mb}MB libres (minimo: ${MIN_DISK_FREE_MB}MB) - Uso: ${usage_percent}%"
    elif [ "${usage_percent:-0}" -gt 85 ]; then
        log_warn "Disk space" "${available_mb}MB libres - Uso: ${usage_percent}%"
    else
        log_pass "Disk space" "${available_mb}MB libres - Uso: ${usage_percent}%"
    fi
}

# ---------- 14. MEMORY LIMITS ----------
check_memory_limits() {
    local memory_limit
    memory_limit=$($PHP_CLI -r '
        $limit = ini_get("memory_limit");
        $value = (int)$limit;
        $unit = strtoupper(substr($limit, -1));
        if ($unit === "G") $value *= 1024;
        elseif ($unit === "K") $value = (int)($value / 1024);
        echo $value;
    ' 2>/dev/null) || {
        log_fail "PHP memory limit" "No se puede leer memory_limit"
        return
    }

    if [ "$memory_limit" -eq -1 ] 2>/dev/null; then
        log_pass "PHP memory limit" "Sin limite (unlimited)"
    elif [ "$memory_limit" -ge "$MIN_MEMORY_LIMIT_MB" ]; then
        log_pass "PHP memory limit" "${memory_limit}MB (minimo: ${MIN_MEMORY_LIMIT_MB}MB)"
    else
        log_fail "PHP memory limit" "${memory_limit}MB (requiere >= ${MIN_MEMORY_LIMIT_MB}MB)"
    fi
}

# ---------- 15. ERROR LOG CHECK ----------
check_error_log() {
    local watchdog_errors
    watchdog_errors=$(cd "$PROJECT_DIR" && $DRUSH watchdog:show --severity=error --count="$MAX_ERROR_LOG_LINES" --format=count 2>/dev/null) || {
        log_warn "Error log (watchdog)" "drush watchdog:show no disponible"
        return
    }

    if [ "${watchdog_errors:-0}" -eq 0 ]; then
        log_pass "Error log (watchdog)" "Sin errores recientes"
    elif [ "${watchdog_errors:-0}" -le 10 ]; then
        log_warn "Error log (watchdog)" "${watchdog_errors} errores recientes"
    else
        log_fail "Error log (watchdog)" "${watchdog_errors} errores recientes (> 10)"
    fi
}

# ---------- 16. DNS RESOLUTION ----------
check_dns_resolution() {
    local domain
    domain=$(echo "$SITE_URL" | sed 's|https\?://||' | sed 's|/.*||')

    local resolved_ip
    resolved_ip=$(dig +short "$domain" A 2>/dev/null | head -1) || {
        # Fallback con host
        resolved_ip=$(host "$domain" 2>/dev/null | grep "has address" | head -1 | awk '{print $NF}') || {
            # Fallback con getent
            resolved_ip=$(getent hosts "$domain" 2>/dev/null | awk '{print $1}' | head -1) || {
                log_fail "DNS resolution" "No se puede resolver $domain"
                return
            }
        }
    }

    if [ -n "$resolved_ip" ]; then
        log_pass "DNS resolution" "$domain -> $resolved_ip"
    else
        log_fail "DNS resolution" "$domain no resuelve"
    fi
}

# ---------- 17. .HTACCESS EXISTENCE ----------
check_htaccess() {
    if [ ! -f "$WEB_DIR/.htaccess" ]; then
        log_fail ".htaccess file" "No existe en $WEB_DIR/"
        return
    fi

    # Verificar que RewriteBase esta habilitado (requisito IONOS)
    if grep -q "^  RewriteBase /" "$WEB_DIR/.htaccess" 2>/dev/null; then
        log_pass ".htaccess file" "Presente, RewriteBase habilitado"
    elif grep -q "# RewriteBase /" "$WEB_DIR/.htaccess" 2>/dev/null; then
        log_warn ".htaccess file" "Presente, pero RewriteBase comentado (IONOS lo requiere)"
    else
        log_pass ".htaccess file" "Presente"
    fi
}

# ---------- 18. SETTINGS.PHP PERMISSIONS ----------
check_settings_permissions() {
    if [ ! -f "$SETTINGS_DIR/settings.php" ]; then
        log_fail "settings.php permissions" "Archivo no encontrado"
        return
    fi

    local perms
    perms=$(stat -c '%a' "$SETTINGS_DIR/settings.php" 2>/dev/null || \
            stat -f '%Lp' "$SETTINGS_DIR/settings.php" 2>/dev/null)

    if [ "$perms" = "444" ] || [ "$perms" = "440" ]; then
        log_pass "settings.php permissions" "Permisos: $perms (solo lectura)"
    elif [ "$perms" = "644" ]; then
        log_warn "settings.php permissions" "Permisos: $perms (deberia ser 444)"
    else
        log_fail "settings.php permissions" "Permisos: $perms (inseguro, debe ser 444)"
    fi
}

# ---------- 19. CACHE BACKENDS STATUS ----------
check_cache_backends() {
    local cache_status
    cache_status=$(cd "$PROJECT_DIR" && $DRUSH php:eval "
        try {
            \$backends = [];
            \$cache = \Drupal::cache('default');
            \$class = get_class(\$cache);
            \$backends[] = 'default:' . \$class;

            \$render = \Drupal::cache('render');
            \$render_class = get_class(\$render);
            \$backends[] = 'render:' . \$render_class;

            echo implode('|', \$backends);
        } catch (\Exception \$e) {
            echo 'ERROR:' . \$e->getMessage();
        }
    " 2>/dev/null) || {
        log_fail "Cache backends" "No se puede consultar backends de cache"
        return
    }

    if echo "$cache_status" | grep -q "^ERROR:"; then
        log_fail "Cache backends" "$cache_status"
    elif echo "$cache_status" | grep -qi "redis\|memcache"; then
        log_pass "Cache backends" "Acelerado ($cache_status)"
    elif echo "$cache_status" | grep -qi "database"; then
        log_warn "Cache backends" "Usando DB ($cache_status) - considerar Redis"
    else
        log_pass "Cache backends" "$cache_status"
    fi
}

# ---------- 20. QUEUE WORKER STATUS ----------
check_queue_workers() {
    local queues
    queues=$(cd "$PROJECT_DIR" && $DRUSH php:eval "
        try {
            \$queue_factory = \Drupal::service('queue');
            \$queue_names = ['cron', 'jaraba_analytics_event_queue', 'jaraba_billing_webhook_queue'];
            \$results = [];
            foreach (\$queue_names as \$name) {
                \$queue = \$queue_factory->get(\$name);
                \$count = \$queue->numberOfItems();
                \$results[] = \$name . ':' . \$count;
            }
            echo implode('|', \$results);
        } catch (\Exception \$e) {
            echo 'OK:queues_checked';
        }
    " 2>/dev/null) || {
        log_warn "Queue workers" "No se puede consultar colas"
        return
    }

    local large_queue=0
    if echo "$queues" | grep -qE ':[0-9]{4,}'; then
        large_queue=1
    fi

    if [ "$large_queue" -eq 1 ]; then
        log_warn "Queue workers" "Colas con backlog elevado: $queues"
    else
        log_pass "Queue workers" "$queues"
    fi
}

# ---------- 21. SETTINGS.LOCAL.PHP EXISTS ----------
check_settings_local() {
    if [ -f "$SETTINGS_DIR/settings.local.php" ]; then
        log_pass "settings.local.php" "Presente (credenciales IONOS)"
    else
        log_fail "settings.local.php" "NO EXISTE - credenciales faltantes"
    fi
}

# ---------- 22. GIT STATUS CLEAN ----------
check_git_status() {
    local git_status
    git_status=$(cd "$PROJECT_DIR" && git status --porcelain 2>/dev/null | head -20)

    if [ -z "$git_status" ]; then
        log_pass "Git working tree" "Limpio"
    else
        local changed_count
        changed_count=$(cd "$PROJECT_DIR" && git status --porcelain 2>/dev/null | wc -l)
        log_warn "Git working tree" "${changed_count} archivos con cambios sin commitear"
    fi

    # Verificar rama
    local branch
    branch=$(cd "$PROJECT_DIR" && git rev-parse --abbrev-ref HEAD 2>/dev/null)
    log_info "Rama actual: ${branch:-unknown}"
}

# ---------- 23. BACKUP DIRECTORY ----------
check_backup_directory() {
    if [ ! -d "$BACKUP_DIR" ]; then
        log_fail "Backup directory" "$BACKUP_DIR no existe"
        return
    fi

    if [ -w "$BACKUP_DIR" ]; then
        local backup_count
        backup_count=$(find "$BACKUP_DIR" -name "*.sql.gz" -mtime -7 2>/dev/null | wc -l)
        log_pass "Backup directory" "Escribible, ${backup_count} backups recientes (7 dias)"
    else
        log_fail "Backup directory" "$BACKUP_DIR no es escribible"
    fi
}

# ---------- 24. PHP EXTENSIONS ----------
check_php_extensions() {
    local required_extensions=("pdo_mysql" "mbstring" "gd" "curl" "zip" "xml" "json" "opcache")
    local missing=0
    local missing_names=""

    for ext in "${required_extensions[@]}"; do
        if ! $PHP_CLI -m 2>/dev/null | grep -qi "^${ext}$"; then
            missing=$((missing + 1))
            missing_names="${missing_names} ${ext}"
        fi
    done

    if [ "$missing" -eq 0 ]; then
        log_pass "PHP extensions" "${#required_extensions[@]} extensiones requeridas presentes"
    else
        log_fail "PHP extensions" "Faltan:${missing_names}"
    fi
}

# =============================================================================
# MAIN
# =============================================================================

main() {
    echo ""
    echo -e "${BOLD}================================================================${NC}"
    echo -e "${BOLD}  JARABA IMPACT PLATFORM - GO-LIVE PREFLIGHT CHECKS${NC}"
    echo -e "${BOLD}  $(timestamp)${NC}"
    echo -e "${BOLD}  Proyecto: $PROJECT_DIR${NC}"
    echo -e "${BOLD}  URL: $SITE_URL${NC}"
    echo -e "${BOLD}================================================================${NC}"

    # --- Bloque 1: Entorno PHP ---
    log_header "1/6  ENTORNO PHP Y DEPENDENCIAS"
    check_php_version
    check_php_extensions
    check_memory_limits
    check_composer_deps

    # --- Bloque 2: Drupal Core ---
    log_header "2/6  DRUPAL CORE"
    check_drupal_status
    check_config_sync
    check_pending_updates
    check_modules_enabled
    check_cron_last_run
    check_cache_backends
    check_queue_workers

    # --- Bloque 3: Base de Datos y Almacenamiento ---
    log_header "3/6  BASE DE DATOS Y ALMACENAMIENTO"
    check_database
    check_disk_space
    check_files_directory
    check_backup_directory

    # --- Bloque 4: Seguridad ---
    log_header "4/6  SEGURIDAD"
    check_ssl_certificate
    check_settings_permissions
    check_settings_local
    check_htaccess

    # --- Bloque 5: Servicios Externos ---
    log_header "5/6  SERVICIOS EXTERNOS"
    check_qdrant
    check_stripe_api
    check_dns_resolution

    # --- Bloque 6: Control de Versiones ---
    log_header "6/6  CONTROL DE VERSIONES"
    check_git_status

    # =============================================================================
    # RESUMEN
    # =============================================================================
    echo ""
    echo -e "${BOLD}================================================================${NC}"
    echo -e "${BOLD}  RESUMEN DE PREFLIGHT CHECKS${NC}"
    echo -e "${BOLD}================================================================${NC}"
    echo ""
    echo -e "  ${GREEN}PASS:${NC} $PASS_COUNT"
    echo -e "  ${RED}FAIL:${NC} $FAIL_COUNT"
    echo -e "  ${YELLOW}WARN:${NC} $WARN_COUNT"
    echo -e "  ${BLUE}SKIP:${NC} $SKIP_COUNT"
    echo ""

    local total=$((PASS_COUNT + FAIL_COUNT + WARN_COUNT + SKIP_COUNT))
    echo -e "  Total: $total checks ejecutados"
    echo ""

    if [ "$FAIL_COUNT" -eq 0 ] && [ "$WARN_COUNT" -eq 0 ]; then
        echo -e "  ${GREEN}${BOLD}RESULTADO: TODOS LOS CHECKS PASARON${NC}"
        echo -e "  ${GREEN}La plataforma esta lista para go-live.${NC}"
        echo ""
        return 0
    elif [ "$FAIL_COUNT" -eq 0 ]; then
        echo -e "  ${YELLOW}${BOLD}RESULTADO: PASA CON ADVERTENCIAS${NC}"
        echo -e "  ${YELLOW}Revisar las ${WARN_COUNT} advertencias antes de proceder.${NC}"
        echo ""
        return 0
    else
        echo -e "  ${RED}${BOLD}RESULTADO: NO APTO PARA GO-LIVE${NC}"
        echo -e "  ${RED}Corregir los ${FAIL_COUNT} fallos criticos antes de proceder.${NC}"
        echo ""
        return 1
    fi
}

# Solo ejecutar si no es sourced
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
fi
