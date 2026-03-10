#!/bin/bash
# =============================================================================
# SETUP INICIAL — IONOS Dedicated L-16 NVMe
# =============================================================================
# Jaraba Impact Platform — Configuracion completa del servidor dedicado.
#
# REQUISITO: Ejecutar como root o con sudo.
# REQUISITO: El codigo ya debe estar en /var/www/jaraba/
#
# Uso:
#   sudo bash scripts/setup-ionos-dedicated.sh
#
# Este script es IDEMPOTENTE: puede ejecutarse multiples veces sin riesgo.
# =============================================================================

set -euo pipefail

# Colores.
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

# Paths.
PROJECT_DIR="/var/www/jaraba"
WEB_DIR="$PROJECT_DIR/web"
DEPLOY_DIR="$PROJECT_DIR/config/deploy"
SETTINGS_DIR="$WEB_DIR/sites/default"

STEP=0
ERRORS=0

step() {
  STEP=$((STEP + 1))
  echo ""
  echo -e "${BOLD}${CYAN}[$STEP] $1${NC}"
  echo "────────────────────────────────────────"
}

ok() { echo -e "  ${GREEN}[OK]${NC} $1"; }
warn() { echo -e "  ${YELLOW}[WARN]${NC} $1"; }
fail() { echo -e "  ${RED}[FAIL]${NC} $1"; ERRORS=$((ERRORS + 1)); }

# =============================================================================
echo ""
echo "============================================================"
echo "  JARABA IMPACT PLATFORM — Server Setup"
echo "  Server: IONOS Dedicated L-16 NVMe"
echo "  Date: $(date '+%Y-%m-%d %H:%M:%S')"
echo "============================================================"

# Verificar root.
if [ "$(id -u)" -ne 0 ]; then
  echo -e "${RED}ERROR: Este script debe ejecutarse como root (sudo).${NC}"
  exit 1
fi

# Verificar que el proyecto existe.
if [ ! -f "$PROJECT_DIR/composer.json" ]; then
  echo -e "${RED}ERROR: No se encuentra el proyecto en $PROJECT_DIR${NC}"
  echo "Sube el codigo primero con rsync."
  exit 1
fi

# =============================================================================
step "Verificar stack instalado"
# =============================================================================

# PHP 8.4
if php -v 2>/dev/null | grep -q "PHP 8.4"; then
  ok "PHP $(php -v | head -1 | awk '{print $2}')"
else
  fail "PHP 8.4 no encontrado. Instalar: apt install php8.4-fpm php8.4-cli php8.4-mysql php8.4-redis php8.4-mbstring php8.4-gd php8.4-xml php8.4-curl php8.4-zip php8.4-intl php8.4-opcache"
fi

# PHP-FPM
if systemctl is-active --quiet php8.4-fpm 2>/dev/null; then
  ok "PHP-FPM 8.4 activo"
else
  warn "PHP-FPM 8.4 no activo. Intentando iniciar..."
  systemctl start php8.4-fpm 2>/dev/null && ok "PHP-FPM iniciado" || fail "No se pudo iniciar PHP-FPM"
fi

# Nginx
if command -v nginx &>/dev/null; then
  ok "Nginx $(nginx -v 2>&1 | sed 's/.*\///')"
else
  fail "Nginx no instalado. Instalar: apt install nginx"
fi

# MariaDB
if command -v mariadb &>/dev/null || command -v mysql &>/dev/null; then
  ok "MariaDB/MySQL disponible"
else
  fail "MariaDB no encontrado. Instalar: apt install mariadb-server"
fi

# Redis
if command -v redis-cli &>/dev/null; then
  if redis-cli ping 2>/dev/null | grep -q PONG; then
    ok "Redis activo ($(redis-cli info server 2>/dev/null | grep redis_version | cut -d: -f2 | tr -d '\r'))"
  else
    warn "Redis instalado pero no responde. Verificar: systemctl status redis"
  fi
else
  fail "Redis no instalado. Instalar: apt install redis-server"
fi

# Composer
if command -v composer &>/dev/null; then
  ok "Composer $(composer --version 2>/dev/null | awk '{print $3}')"
else
  fail "Composer no instalado"
fi

# Certbot
if command -v certbot &>/dev/null; then
  ok "Certbot disponible"
else
  warn "Certbot no instalado. Instalar: apt install certbot python3-certbot-nginx"
fi

# =============================================================================
step "Crear directorios necesarios"
# =============================================================================

mkdir -p "$PROJECT_DIR/private"
chown www-data:www-data "$PROJECT_DIR/private"
chmod 750 "$PROJECT_DIR/private"
ok "Private files: $PROJECT_DIR/private"

mkdir -p /var/log/jaraba
chown www-data:www-data /var/log/jaraba
chmod 750 /var/log/jaraba
ok "Logs: /var/log/jaraba"

mkdir -p "$WEB_DIR/sites/default/files"
chown -R www-data:www-data "$WEB_DIR/sites/default/files"
ok "Public files: $WEB_DIR/sites/default/files"

# =============================================================================
step "Instalar configuracion Nginx"
# =============================================================================

# Snippet compartido.
if [ -f "$DEPLOY_DIR/nginx-jaraba-common.conf" ]; then
  cp "$DEPLOY_DIR/nginx-jaraba-common.conf" /etc/nginx/snippets/jaraba-common.conf
  ok "Snippet: /etc/nginx/snippets/jaraba-common.conf"
else
  fail "No se encuentra: $DEPLOY_DIR/nginx-jaraba-common.conf"
fi

# Vhosts.
if [ -f "$DEPLOY_DIR/nginx-metasites.conf" ]; then
  cp "$DEPLOY_DIR/nginx-metasites.conf" /etc/nginx/sites-available/jaraba-metasites.conf
  ln -sf /etc/nginx/sites-available/jaraba-metasites.conf /etc/nginx/sites-enabled/
  ok "Vhosts: /etc/nginx/sites-available/jaraba-metasites.conf"
else
  fail "No se encuentra: $DEPLOY_DIR/nginx-metasites.conf"
fi

# Eliminar default si existe.
if [ -f /etc/nginx/sites-enabled/default ]; then
  rm -f /etc/nginx/sites-enabled/default
  ok "Eliminado site default de Nginx"
fi

# Verificar sintaxis.
if nginx -t 2>/dev/null; then
  ok "Nginx config: sintaxis OK"
  systemctl reload nginx
  ok "Nginx recargado"
else
  fail "Nginx config: ERROR de sintaxis. Ejecutar: nginx -t"
fi

# =============================================================================
step "Configurar Drupal settings"
# =============================================================================

# settings.local.php
if [ -f "$SETTINGS_DIR/settings.local.php" ]; then
  ok "settings.local.php ya existe"
else
  cat > "$SETTINGS_DIR/settings.local.php" << 'LOCALEOF'
<?php
// Production settings — IONOS Dedicated L-16 NVMe.
include $app_root . '/../config/deploy/settings.production.php';
LOCALEOF
  ok "settings.local.php creado (incluye settings.production.php)"
fi

# Verificar que settings.production.php existe.
if [ -f "$DEPLOY_DIR/settings.production.php" ]; then
  ok "settings.production.php presente"
else
  fail "Falta: $DEPLOY_DIR/settings.production.php"
fi

# Verificar que settings.secrets.php existe.
if [ -f "$DEPLOY_DIR/settings.secrets.php" ]; then
  ok "settings.secrets.php presente"
else
  fail "Falta: $DEPLOY_DIR/settings.secrets.php"
fi

# Permisos seguros.
chmod 555 "$SETTINGS_DIR"
chmod 444 "$SETTINGS_DIR/settings.php"
if [ -f "$SETTINGS_DIR/settings.local.php" ]; then
  chmod 444 "$SETTINGS_DIR/settings.local.php"
fi
ok "Permisos securizados (555/444)"

# =============================================================================
step "Variables de entorno (.env)"
# =============================================================================

if [ -f "$PROJECT_DIR/.env" ]; then
  ok ".env presente"

  # Verificar variables criticas.
  MISSING_VARS=""
  for var in DB_HOST DB_NAME DB_USER DB_PASSWORD REDIS_HOST STRIPE_SECRET_KEY RECAPTCHA_SITE_KEY RECAPTCHA_SECRET_KEY SMTP_HOST; do
    if ! grep -q "^${var}=" "$PROJECT_DIR/.env" 2>/dev/null; then
      MISSING_VARS="$MISSING_VARS $var"
    fi
  done

  if [ -n "$MISSING_VARS" ]; then
    warn "Variables faltantes en .env:$MISSING_VARS"
  else
    ok "Variables criticas presentes en .env"
  fi
else
  warn ".env no existe. Crear desde .env.example:"
  warn "  cp $PROJECT_DIR/.env.example $PROJECT_DIR/.env && nano $PROJECT_DIR/.env"
fi

# =============================================================================
step "Instalar dependencias Composer"
# =============================================================================

cd "$PROJECT_DIR"
if [ -f "vendor/autoload.php" ]; then
  ok "vendor/ ya existe"
else
  warn "vendor/ no existe. Instalando..."
fi

composer install --no-dev --optimize-autoloader --no-interaction 2>&1 | tail -3
ok "Composer install completado"

# =============================================================================
step "Permisos del proyecto"
# =============================================================================

# Ownership general.
chown -R www-data:www-data "$PROJECT_DIR"
ok "Ownership: www-data:www-data"

# Archivos ejecutables.
find "$PROJECT_DIR/vendor/bin" -type f -exec chmod +x {} \; 2>/dev/null
find "$PROJECT_DIR/scripts" -name "*.sh" -exec chmod +x {} \; 2>/dev/null
ok "Scripts ejecutables"

# =============================================================================
step "Base de datos y Drupal"
# =============================================================================

DRUSH="$PROJECT_DIR/vendor/bin/drush"

if [ -x "$DRUSH" ] || [ -f "$DRUSH" ]; then
  # Verificar conexion BD.
  if sudo -u www-data "$DRUSH" sql:query "SELECT 1" --root="$WEB_DIR" 2>/dev/null; then
    ok "Conexion a base de datos OK"

    # Ejecutar updates.
    echo "  Ejecutando drush updatedb..."
    sudo -u www-data "$DRUSH" updatedb -y --root="$WEB_DIR" 2>&1 | tail -5
    ok "Database updates aplicados"

    # Importar config.
    echo "  Ejecutando drush config:import..."
    sudo -u www-data "$DRUSH" config:import -y --root="$WEB_DIR" 2>&1 | tail -5
    ok "Config importada"

    # Cache rebuild.
    sudo -u www-data "$DRUSH" cache:rebuild --root="$WEB_DIR" 2>/dev/null
    ok "Cache reconstruida"
  else
    fail "No hay conexion a la base de datos. Verificar .env y settings."
  fi
else
  fail "Drush no encontrado en: $DRUSH"
fi

# =============================================================================
step "SSL — Certificados Let's Encrypt"
# =============================================================================

if command -v certbot &>/dev/null; then
  # Verificar si ya hay certificados.
  CERT_COUNT=0
  for domain in plataformadeecosistemas.es pepejaraba.com jarabaimpact.com plataformadeecosistemas.com; do
    if [ -d "/etc/letsencrypt/live/$domain" ]; then
      ok "SSL: $domain (certificado existente)"
      CERT_COUNT=$((CERT_COUNT + 1))
    fi
  done

  if [ "$CERT_COUNT" -lt 4 ]; then
    echo ""
    echo -e "  ${YELLOW}Faltan certificados SSL. Ejecutar MANUALMENTE:${NC}"
    echo ""
    echo "  # Meta-sitios + SaaS base (HTTP challenge):"
    echo "  certbot --nginx \\"
    echo "    -d plataformadeecosistemas.es -d www.plataformadeecosistemas.es \\"
    echo "    -d pepejaraba.com -d www.pepejaraba.com \\"
    echo "    -d jarabaimpact.com -d www.jarabaimpact.com \\"
    echo "    -d plataformadeecosistemas.com -d www.plataformadeecosistemas.com"
    echo ""
    echo "  # Wildcard para subdominios tenant (DNS challenge):"
    echo "  certbot certonly --manual --preferred-challenges dns \\"
    echo "    -d '*.plataformadeecosistemas.com'"
    echo ""
    echo "  # Activar auto-renovacion:"
    echo "  systemctl enable certbot.timer"
    echo ""
    warn "SSL requiere interaccion — ejecutar manualmente tras este script"
  else
    ok "Todos los certificados SSL presentes"
  fi
else
  warn "Certbot no instalado. Instalar: apt install certbot python3-certbot-nginx"
fi

# =============================================================================
step "Cron para Drupal"
# =============================================================================

CRON_FILE="/etc/cron.d/drupal-jaraba"
if [ -f "$CRON_FILE" ]; then
  ok "Cron ya configurado: $CRON_FILE"
else
  cat > "$CRON_FILE" << 'CRONEOF'
# Jaraba Impact Platform — Drupal cron cada 15 minutos.
*/15 * * * * www-data cd /var/www/jaraba && vendor/bin/drush cron 2>&1 | logger -t drupal-cron
CRONEOF
  chmod 644 "$CRON_FILE"
  ok "Cron creado: cada 15 minutos"
fi

# =============================================================================
step "PHP-FPM Tuning (128GB RAM)"
# =============================================================================

FPM_POOL="/etc/php/8.4/fpm/pool.d/www.conf"
if [ -f "$FPM_POOL" ]; then
  # Solo mostrar recomendaciones, no sobreescribir.
  CURRENT_MAX=$(grep "^pm.max_children" "$FPM_POOL" 2>/dev/null | awk -F= '{print $2}' | tr -d ' ')
  if [ -n "$CURRENT_MAX" ] && [ "$CURRENT_MAX" -ge 50 ]; then
    ok "PHP-FPM pm.max_children = $CURRENT_MAX (OK para 128GB)"
  else
    echo ""
    echo -e "  ${YELLOW}Recomendacion de tuning para 128GB RAM:${NC}"
    echo "  Editar $FPM_POOL:"
    echo "    pm = dynamic"
    echo "    pm.max_children = 100"
    echo "    pm.start_servers = 20"
    echo "    pm.min_spare_servers = 10"
    echo "    pm.max_spare_servers = 40"
    echo "    pm.max_requests = 500"
    echo ""
    warn "PHP-FPM puede necesitar tuning"
  fi
else
  warn "Pool FPM no encontrado en: $FPM_POOL"
fi

# =============================================================================
step "Redis Tuning"
# =============================================================================

REDIS_CONF="/etc/redis/redis.conf"
if [ -f "$REDIS_CONF" ]; then
  CURRENT_MAXMEM=$(grep "^maxmemory " "$REDIS_CONF" 2>/dev/null | awk '{print $2}')
  if [ -n "$CURRENT_MAXMEM" ]; then
    ok "Redis maxmemory = $CURRENT_MAXMEM"
  else
    echo ""
    echo -e "  ${YELLOW}Recomendacion Redis para 128GB RAM:${NC}"
    echo "  Editar $REDIS_CONF:"
    echo "    maxmemory 4gb"
    echo "    maxmemory-policy allkeys-lru"
    echo "  Reiniciar: systemctl restart redis"
    echo ""
    warn "Redis maxmemory no configurado"
  fi
else
  warn "redis.conf no encontrado en: $REDIS_CONF"
fi

# =============================================================================
step "Supervisor (AI Queue Workers) — OPCIONAL"
# =============================================================================

if command -v supervisorctl &>/dev/null; then
  if [ -f "$DEPLOY_DIR/supervisor-ai-workers.conf" ]; then
    cp "$DEPLOY_DIR/supervisor-ai-workers.conf" /etc/supervisor/conf.d/jaraba-ai-workers.conf
    supervisorctl reread 2>/dev/null
    supervisorctl update 2>/dev/null
    ok "Supervisor: AI workers configurados"
  else
    warn "supervisor-ai-workers.conf no encontrado"
  fi
else
  ok "Supervisor no instalado (AI workers opcionales para arranque inicial)"
fi

# =============================================================================
step "Logrotate"
# =============================================================================

LOGROTATE_CONF="/etc/logrotate.d/jaraba"
if [ ! -f "$LOGROTATE_CONF" ]; then
  cat > "$LOGROTATE_CONF" << 'LREOF'
/var/log/jaraba/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
LREOF
  ok "Logrotate configurado: $LOGROTATE_CONF"
else
  ok "Logrotate ya existe: $LOGROTATE_CONF"
fi

# =============================================================================
step "Verificacion final"
# =============================================================================

echo ""

# Test HTTP local.
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/ 2>/dev/null || echo "000")
if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "302" ] || [ "$HTTP_CODE" = "301" ]; then
  ok "HTTP localhost: $HTTP_CODE"
else
  warn "HTTP localhost: $HTTP_CODE (puede ser normal antes de SSL)"
fi

# Test dominios.
for domain in plataformadeecosistemas.es pepejaraba.com jarabaimpact.com plataformadeecosistemas.com; do
  HTTP_CODE=$(curl -sk -o /dev/null -w "%{http_code}" "https://$domain/" 2>/dev/null || echo "000")
  if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "302" ]; then
    ok "https://$domain -> $HTTP_CODE"
  elif [ "$HTTP_CODE" = "301" ]; then
    ok "https://$domain -> $HTTP_CODE (redirect)"
  else
    warn "https://$domain -> $HTTP_CODE (verificar DNS + SSL)"
  fi
done

# Drush status.
if [ -x "$DRUSH" ] || [ -f "$DRUSH" ]; then
  DB_STATUS=$(sudo -u www-data "$DRUSH" core:status --field=db-status --root="$WEB_DIR" 2>/dev/null || echo "unknown")
  if [ "$DB_STATUS" = "Connected" ]; then
    ok "Drupal DB: Connected"
  else
    warn "Drupal DB: $DB_STATUS"
  fi
fi

# =============================================================================
# RESUMEN
# =============================================================================
echo ""
echo "============================================================"
if [ "$ERRORS" -gt 0 ]; then
  echo -e "  ${RED}${BOLD}SETUP COMPLETADO CON $ERRORS ERROR(ES)${NC}"
  echo "  Revisa los errores arriba y corrigelos."
else
  echo -e "  ${GREEN}${BOLD}SETUP COMPLETADO EXITOSAMENTE${NC}"
fi
echo "============================================================"
echo ""
echo "Pasos manuales pendientes:"
echo "  1. Certificados SSL:  certbot --nginx -d ... (ver paso anterior)"
echo "  2. Variables .env:    nano $PROJECT_DIR/.env"
echo "  3. PHP-FPM tuning:   nano /etc/php/8.4/fpm/pool.d/www.conf"
echo "  4. Redis tuning:     nano /etc/redis/redis.conf"
echo ""
echo "Tras SSL, verificar:"
echo "  curl -sI https://pepejaraba.com"
echo "  curl -sI https://jarabaimpact.com"
echo "  curl -sI https://plataformadeecosistemas.es"
echo "  curl -sI https://plataformadeecosistemas.com"
echo ""

exit $ERRORS
