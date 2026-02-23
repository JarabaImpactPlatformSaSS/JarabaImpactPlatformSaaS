
PLAN DE IMPLEMENTACION
SERVIDOR DEDICADO AMD EPYC ZEN 5
IONOS AE16-128 NVMe XL
Runbook Completo para Claude Code
JARABA IMPACT PLATFORM

Campo	Valor
Version:	1.0
Fecha:	Febrero 2026
Estado:	Ready for Implementation
Codigo:	131b_Server_Implementation_Runbook_AMD_v1
Prioridad:	CRITICA - Prerequisito de produccion
Destinatario:	EDI Google Antigravity / Claude Code
Sustituye a:	131_Platform_Infrastructure_Deployment_v1 (parcialmente)
Servidor:	IONOS AE16-128 NVMe XL (EPYC 4545P Zen 5)
 
Tabla de Contenidos
Tabla de Contenidos	1
1. Resumen Ejecutivo y Contexto	1
1.1 Por que esta migracion es obligatoria	1
1.2 Especificaciones del Servidor Contratado	1
1.3 Mapa de Memoria: Distribucion de 128 GB	1
2. Fase 0: Provision y Acceso Inicial	1
2.1 Contratacion en IONOS	1
2.2 Primer Acceso SSH y Validacion Hardware	1
2.3 Verificacion RAID y Particiones	1
3. Fase 1: Hardening del Sistema Operativo	1
3.1 Actualizacion Base	1
3.2 Crear Usuario Administrativo (no-root)	1
3.3 Hardening SSH	1
3.4 Firewall (UFW)	1
3.5 Fail2ban	1
3.6 Actualizaciones Automaticas de Seguridad	1
3.7 Parametros de Kernel (sysctl)	1
4. Fase 2: Docker y Estructura de Proyecto	1
4.1 Instalacion de Docker Engine	1
4.2 Configuracion del Docker Daemon	1
4.3 Estructura de Directorios del Proyecto	1
4.4 Archivo de Variables de Entorno	1
5. Fase 3: Stack de Servicios - Docker Compose	1
5.1 docker-compose.prod.yml (Archivo Completo)	1
6. Fase 4: Configuraciones de Servicios	1
6.1 MariaDB Optimizado para 128GB DDR5	1
6.2 PHP-FPM Optimizado	1
6.3 Configuracion Drupal para Redis	1
6.4 Dockerfile de Drupal Produccion	1
7. Fase 5: Backup y Disaster Recovery	1
7.1 Estrategia de Backups	1
7.2 Script Principal de Backup	1
7.3 RTO / RPO Objetivos	1
8. Fase 6: Monitoring Stack	1
8.1 docker-compose.monitoring.yml	1
8.2 Alertas Criticas Configuradas	1
9. Fase 7: DNS, Dominios y SSL	1
9.1 Configuracion de Dominios	1
9.2 Cloudflare WAF (Recomendado)	1
10. Fase 8: Migracion desde Servidor Managed	1
10.1 Inventario del Servidor Actual	1
10.2 Procedimiento de Migracion	1
Dia D-7: Preparacion	1
Dia D-1: Pre-migracion	1
Dia D (Migracion): Ventana de 30-60 minutos	1
11. Validacion Post-Implementacion	1
11.1 Checklist de Verificacion Completo	1
11.2 Test de Carga Basico	1
12. Comandos de Operacion Diaria	1
12.1 Gestion de Containers	1
12.2 Drush (Drupal CLI)	1
12.3 Base de Datos	1
13. Diferencias con Documento 131	1
14. Roadmap de Escalado	1
15. Conclusion	1

 
1. Resumen Ejecutivo y Contexto
Este documento es el runbook definitivo para la implementacion del servidor dedicado AMD EPYC 4545P (Zen 5) en IONOS, que sustituira al actual servidor managed L-16 NVMe. A diferencia del documento 131 (que especificaba una arquitectura generica para un servidor EPYC 7702P de 256GB), este documento esta calibrado especificamente para las capacidades reales del AE16-128 NVMe XL contratado, con todas las configuraciones optimizadas para 128GB DDR5 y 16 cores/32 threads.

INFO: Este documento esta disenado para ejecucion directa por Claude Code. Cada seccion contiene comandos exactos, archivos completos, y validaciones.
1.1 Por que esta migracion es obligatoria
El servidor managed actual tiene restricciones incompatibles con un SaaS multi-tenant:
Restriccion	Limite Actual	Necesidad SaaS	Impacto
Base de datos	5 GB hard limit	20-100+ GB	Bloqueo de writes al alcanzar limite
Conexiones DB	40 paralelas	200-500+	Errores bajo carga concurrente
Root access	NO disponible	Obligatorio	No Redis, no Qdrant, no Docker
Customizacion DB	NO my.cnf	Obligatorio	Sin optimizacion InnoDB posible
Alta carga	Prohibido en ToS	Es la naturaleza del SaaS	Riesgo de suspension de cuenta
1.2 Especificaciones del Servidor Contratado
Componente	Especificacion	Notas
Modelo IONOS	AE16-128 NVMe XL	Servidor dedicado unmanaged
CPU	AMD EPYC 4545P (Zen 5)	16 cores / 32 threads, 3.0-5.4 GHz
RAM	128 GB DDR5 ECC	1 canal, baja latencia
Almacenamiento	2x 1TB NVMe (2TB total)	Software RAID 1 = 1TB usable
PCIe	5.0	~14 GB/s bandwidth teorico
Red	1 Gbps garantizado	Conectividad IONOS premium
Datacenter	Logrono, Espana	Latencia minima para usuarios ES
SO Base	Ubuntu 24.04 LTS	Instalacion por IONOS o manual
Coste	171 EUR/mes (IVA excl.)	Compromiso 24 meses
1.3 Mapa de Memoria: Distribucion de 128 GB
La distribucion de memoria es critica. Con 128 GB (vs los 256 GB del doc 131), cada servicio debe estar calibrado con precision:
Servicio	RAM Asignada	% del Total	Justificacion
Sistema Operativo + overhead	4 GB	3%	Ubuntu 24.04 base + kernel buffers
MariaDB 11.2 (InnoDB buffer pool)	40 GB	31%	Buffer pool 36GB + overhead 4GB
PHP-FPM 8.3 (Drupal workers)	20 GB	16%	~40 workers x 512MB limit
Redis 7 (cache + sessions + queues)	6 GB	5%	Cache Drupal + embeddings + sessions
Qdrant 1.7 (vectores IA)	8 GB	6%	Colecciones RAG + matching
Drupal cron + background tasks	4 GB	3%	ECA, queue workers, imports
Traefik (reverse proxy)	512 MB	0.4%	Ligero, solo routing + TLS
Apache Tika (documentos)	2 GB	1.6%	Procesamiento de PDFs/docs
Minio (object storage)	2 GB	1.6%	Backup files S3-compatible
Monitoring (Prometheus+Grafana+Loki)	6 GB	5%	30 dias retencion metricas
RESERVA para picos	35.5 GB	28%	Headroom critico para estabilidad
AVISO: La reserva de 28% es intencionada. Un servidor sin headroom sufre OOM kills bajo carga. Mejor 28% libre que downtime en produccion.
 
2. Fase 0: Provision y Acceso Inicial
Esta fase se ejecuta una sola vez, inmediatamente despues de contratar el servidor en IONOS.
2.1 Contratacion en IONOS
Acceder a ionos.es, seccion Servidores Dedicados, seleccionar:
Producto: AE16-128 NVMe XL
Ubicacion: Logrono, Espana
Sistema Operativo: Ubuntu 24.04 LTS (o sin SO para instalacion manual)
Compromiso: 24 meses (precio 171 EUR/mes)
IONOS proporcionara: IP publica, credenciales root iniciales, y acceso KVM de emergencia.
2.2 Primer Acceso SSH y Validacion Hardware
# Acceso inicial (password proporcionado por IONOS)
ssh root@<IP_PUBLICA>

# Validar hardware
lscpu | grep -E 'Model name|CPU\(s\)|Thread|Core'
free -h
lsblk
cat /proc/cpuinfo | grep 'model name' | head -1

# Resultado esperado:
# Model name: AMD EPYC 4545P 16-Core Processor
# CPU(s): 32
# Mem: 125Gi (128GB DDR5)
# 2x NVMe ~1TB cada uno
2.3 Verificacion RAID y Particiones
# Verificar estado RAID (Software RAID 1 mdadm)
cat /proc/mdstat
mdadm --detail /dev/md0  # o /dev/md127

# Si RAID no esta configurado, crear:
mdadm --create /dev/md0 --level=1 --raid-devices=2 /dev/nvme0n1 /dev/nvme1n1
mkfs.ext4 /dev/md0

# Particionado recomendado (si instalacion manual):
# /boot     1 GB   ext4
# /         50 GB  ext4   (SO + Docker images)
# /var      800 GB ext4   (Docker volumes, DB data, logs)
# swap      16 GB  swap   (= 1/8 de RAM, safety net)
# /backup   100 GB ext4   (backups locales antes de S3)
AVISO: Si IONOS instala Ubuntu con particionado automatico, verificar que /var tiene espacio suficiente (>500GB). Si no, redimensionar ANTES de instalar Docker.
 
3. Fase 1: Hardening del Sistema Operativo
3.1 Actualizacion Base
apt update && apt upgrade -y
apt install -y curl wget git unzip htop iotop net-tools \
  software-properties-common apt-transport-https ca-certificates \
  gnupg lsb-release fail2ban ufw logrotate jq

# Configurar timezone
timedatectl set-timezone Europe/Madrid

# Configurar locale
locale-gen es_ES.UTF-8
update-locale LANG=es_ES.UTF-8
3.2 Crear Usuario Administrativo (no-root)
# Crear usuario deploy
adduser --gecos '' jaraba
usermod -aG sudo jaraba
usermod -aG docker jaraba  # Se ejecuta despues de instalar Docker

# Configurar SSH key para el usuario
mkdir -p /home/jaraba/.ssh
# Copiar aqui la clave publica del equipo de desarrollo
echo 'ssh-ed25519 AAAA... deploy@jaraba' >> /home/jaraba/.ssh/authorized_keys
chmod 700 /home/jaraba/.ssh
chmod 600 /home/jaraba/.ssh/authorized_keys
chown -R jaraba:jaraba /home/jaraba/.ssh
3.3 Hardening SSH
Archivo: /etc/ssh/sshd_config.d/jaraba-hardening.conf
Port 2222                          # Puerto no estandar
PermitRootLogin no                 # NUNCA root directo
PasswordAuthentication no          # Solo claves SSH
PubkeyAuthentication yes
MaxAuthTries 3
LoginGraceTime 30
AllowUsers jaraba
X11Forwarding no
PermitEmptyPasswords no
ClientAliveInterval 300
ClientAliveCountMax 2
# Aplicar configuracion
sshd -t  # Validar sintaxis ANTES de reiniciar
systemctl restart sshd

# IMPORTANTE: Probar acceso con nuevo puerto ANTES de cerrar sesion actual
# ssh -p 2222 jaraba@<IP_PUBLICA>
CRITICO: Verificar acceso SSH por puerto 2222 ANTES de cerrar la sesion root. Si se pierde acceso, usar KVM de IONOS.
3.4 Firewall (UFW)
ufw default deny incoming
ufw default allow outgoing

# SSH (puerto custom)
ufw allow 2222/tcp comment 'SSH'

# HTTP/HTTPS (Traefik)
ufw allow 80/tcp comment 'HTTP'
ufw allow 443/tcp comment 'HTTPS'

# NO abrir puertos de servicios internos (3306, 6379, 6333, etc.)
# Todos los servicios se comunican via red Docker interna

ufw enable
ufw status verbose
3.5 Fail2ban
Archivo: /etc/fail2ban/jail.local
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 3
backend = systemd

[sshd]
enabled = true
port = 2222
filter = sshd
logpath = /var/log/auth.log
maxretry = 3
bantime = 86400

[traefik-auth]
enabled = true
port = http,https
filter = traefik-auth
logpath = /opt/jaraba/logs/traefik/access.log
maxretry = 10
bantime = 3600
systemctl enable fail2ban
systemctl start fail2ban
3.6 Actualizaciones Automaticas de Seguridad
apt install -y unattended-upgrades
dpkg-reconfigure -plow unattended-upgrades

# Verificar configuracion:
cat /etc/apt/apt.conf.d/50unattended-upgrades | grep -v '//'
3.7 Parametros de Kernel (sysctl)
Archivo: /etc/sysctl.d/99-jaraba.conf
# Networking optimizations
net.core.somaxconn = 65535
net.core.netdev_max_backlog = 65535
net.ipv4.tcp_max_syn_backlog = 65535
net.ipv4.ip_local_port_range = 1024 65535
net.ipv4.tcp_tw_reuse = 1
net.ipv4.tcp_fin_timeout = 15
net.ipv4.tcp_keepalive_time = 300
net.ipv4.tcp_keepalive_intvl = 30
net.ipv4.tcp_keepalive_probes = 5

# Memory management
vm.swappiness = 10
vm.overcommit_memory = 1
vm.dirty_ratio = 15
vm.dirty_background_ratio = 5

# File system
fs.file-max = 2097152
fs.inotify.max_user_watches = 524288
sysctl --system  # Aplicar
 
4. Fase 2: Docker y Estructura de Proyecto
4.1 Instalacion de Docker Engine
# Instalar Docker CE
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] \
  https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | \
  tee /etc/apt/sources.list.d/docker.list > /dev/null

apt update
apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

# Verificar
docker --version
docker compose version

# Agregar usuario al grupo docker
usermod -aG docker jaraba
4.2 Configuracion del Docker Daemon
Archivo: /etc/docker/daemon.json
{
  "log-driver": "json-file",
  "log-opts": {
    "max-size": "50m",
    "max-file": "3"
  },
  "storage-driver": "overlay2",
  "default-address-pools": [
    { "base": "172.20.0.0/16", "size": 24 }
  ],
  "metrics-addr": "127.0.0.1:9323",
  "experimental": true,
  "live-restore": true,
  "default-ulimits": {
    "nofile": { "Name": "nofile", "Hard": 65536, "Soft": 65536 }
  }
}
systemctl restart docker
docker info | grep -E 'Storage|Logging|Cgroup'
4.3 Estructura de Directorios del Proyecto
mkdir -p /opt/jaraba/{
  config/{mariadb,redis,php,nginx,traefik,prometheus,grafana/provisioning,loki,promtail,alertmanager,blackbox,qdrant},
  scripts,
  logs/{traefik,drupal,mariadb},
  backups/{database,files,qdrant},
  secrets,
  drupal/{web,private,tmp}
}

# Permisos
chown -R jaraba:jaraba /opt/jaraba
chmod 700 /opt/jaraba/secrets

# Git init para versionado de configs
cd /opt/jaraba
git init
echo 'secrets/' >> .gitignore
echo '.env' >> .gitignore
echo 'backups/' >> .gitignore
echo 'logs/' >> .gitignore
4.4 Archivo de Variables de Entorno
Archivo: /opt/jaraba/.env
# ═══════════════════════════════════════════════════════
# JARABA IMPACT PLATFORM - Production Environment
# Servidor: IONOS AE16-128 NVMe XL (AMD EPYC 4545P)
# ═══════════════════════════════════════════════════════

# Database
DB_ROOT_PASSWORD=<GENERAR_CON_openssl_rand_-base64_32>
DB_PASSWORD=<GENERAR_CON_openssl_rand_-base64_32>
DB_NAME=jaraba
DB_USER=drupal

# Drupal
DRUPAL_HASH_SALT=<GENERAR_CON_openssl_rand_-hex_64>
TRUSTED_HOST_PATTERNS='^.+\.jarabaimpact\.com$|^.+\.pepejaraba\.com$|^.+\.plataformadeecosistemas\.es$'

# Redis
REDIS_PASSWORD=<GENERAR_CON_openssl_rand_-base64_24>

# Qdrant
QDRANT_API_KEY=<GENERAR_CON_openssl_rand_-base64_32>

# External APIs
STRIPE_SECRET_KEY=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
ANTHROPIC_API_KEY=sk-ant-...
GOOGLE_GEMINI_API_KEY=...
SENDGRID_API_KEY=SG...

# Monitoring
GRAFANA_PASSWORD=<GENERAR>

# Minio (S3-compatible backup)
MINIO_ACCESS_KEY=<GENERAR>
MINIO_SECRET_KEY=<GENERAR>

# Cloudflare (optional, for API)
CLOUDFLARE_API_TOKEN=...
CRITICO: Generar TODOS los passwords con: openssl rand -base64 32. NUNCA reutilizar passwords. El archivo .env NUNCA se commitea a git.
 
5. Fase 3: Stack de Servicios - Docker Compose
Este es el archivo principal de orquestacion, calibrado para 128GB DDR5 y 16c/32t.
5.1 docker-compose.prod.yml (Archivo Completo)
Archivo: /opt/jaraba/docker-compose.prod.yml
version: '3.8'

services:
  # ═══════════════════════════════════════════════════════
  # REVERSE PROXY - Traefik v3
  # ═══════════════════════════════════════════════════════
  traefik:
    image: traefik:v3.0
    container_name: jaraba-traefik
    restart: always
    command:
      - "--api.dashboard=true"
      - "--providers.docker=true"
      - "--providers.docker.exposedbydefault=false"
      - "--entrypoints.web.address=:80"
      - "--entrypoints.web.http.redirections.entryPoint.to=websecure"
      - "--entrypoints.websecure.address=:443"
      - "--certificatesresolvers.letsencrypt.acme.email=tech@jarabaimpact.com"
      - "--certificatesresolvers.letsencrypt.acme.storage=/letsencrypt/acme.json"
      - "--certificatesresolvers.letsencrypt.acme.httpchallenge.entrypoint=web"
      - "--accesslog=true"
      - "--accesslog.filepath=/var/log/traefik/access.log"
      - "--log.level=WARN"
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock:ro
      - traefik-letsencrypt:/letsencrypt
      - /opt/jaraba/logs/traefik:/var/log/traefik
    networks:
      - jaraba-network
    deploy:
      resources:
        limits:
          memory: 512M
        reservations:
          memory: 128M
  # ═══════════════════════════════════════════════════════
  # DRUPAL 11 - Aplicacion Principal
  # ═══════════════════════════════════════════════════════
  drupal:
    image: jaraba/drupal:11-prod
    container_name: jaraba-drupal
    restart: always
    environment:
      - APP_ENV=production
      - DATABASE_URL=mysql://${DB_USER}:${DB_PASSWORD}@mariadb:3306/${DB_NAME}
      - REDIS_URL=redis://:${REDIS_PASSWORD}@redis:6379
      - QDRANT_URL=http://qdrant:6333
      - QDRANT_API_KEY=${QDRANT_API_KEY}
      - TIKA_URL=http://tika:9998
      - STRIPE_SECRET_KEY=${STRIPE_SECRET_KEY}
      - STRIPE_WEBHOOK_SECRET=${STRIPE_WEBHOOK_SECRET}
      - ANTHROPIC_API_KEY=${ANTHROPIC_API_KEY}
      - GOOGLE_GEMINI_API_KEY=${GOOGLE_GEMINI_API_KEY}
      - DRUPAL_HASH_SALT=${DRUPAL_HASH_SALT}
      - TRUSTED_HOST_PATTERNS=${TRUSTED_HOST_PATTERNS}
      - PHP_MEMORY_LIMIT=512M
      - PHP_MAX_EXECUTION_TIME=120
      - PHP_UPLOAD_MAX_FILESIZE=100M
    volumes:
      - drupal-code:/var/www/html
      - drupal-files:/var/www/html/web/sites/default/files
      - drupal-private:/var/www/private
      - /opt/jaraba/config/php/www.conf:/usr/local/etc/php-fpm.d/www.conf
      - /opt/jaraba/config/php/php-prod.ini:/usr/local/etc/php/conf.d/99-prod.ini
    depends_on:
      mariadb:
        condition: service_healthy
      redis:
        condition: service_healthy
    networks:
      - jaraba-network
    labels:
      - "traefik.enable=true"
      # Multi-dominio
      - "traefik.http.routers.drupal.rule=Host(`app.jarabaimpact.com`) || Host(`pepejaraba.com`) || Host(`www.pepejaraba.com`) || Host(`plataformadeecosistemas.es`) || Host(`www.plataformadeecosistemas.es`)"
      - "traefik.http.routers.drupal.entrypoints=websecure"
      - "traefik.http.routers.drupal.tls.certresolver=letsencrypt"
      - "traefik.http.services.drupal.loadbalancer.server.port=80"
      # Security headers
      - "traefik.http.middlewares.security-headers.headers.stsSeconds=31536000"
      - "traefik.http.middlewares.security-headers.headers.stsIncludeSubdomains=true"
      - "traefik.http.middlewares.security-headers.headers.contentTypeNosniff=true"
      - "traefik.http.middlewares.security-headers.headers.frameDeny=true"
      - "traefik.http.routers.drupal.middlewares=security-headers"
    deploy:
      resources:
        limits:
          cpus: '12'
          memory: 20G
        reservations:
          cpus: '4'
          memory: 8G
  # ═══════════════════════════════════════════════════════
  # DRUPAL CRON - Tareas en Background
  # ═══════════════════════════════════════════════════════
  drupal-cron:
    image: jaraba/drupal:11-prod
    container_name: jaraba-drupal-cron
    restart: always
    entrypoint: /usr/local/bin/cron-entrypoint.sh
    environment:
      - APP_ENV=production
      - DATABASE_URL=mysql://${DB_USER}:${DB_PASSWORD}@mariadb:3306/${DB_NAME}
      - REDIS_URL=redis://:${REDIS_PASSWORD}@redis:6379
    volumes:
      - drupal-code:/var/www/html
      - drupal-files:/var/www/html/web/sites/default/files
      - drupal-private:/var/www/private
    depends_on:
      - mariadb
      - redis
    networks:
      - jaraba-network
    deploy:
      resources:
        limits:
          cpus: '4'
          memory: 4G
  # ═══════════════════════════════════════════════════════
  # MARIADB 11.2 - Base de Datos Principal
  # ═══════════════════════════════════════════════════════
  mariadb:
    image: mariadb:11.2
    container_name: jaraba-mariadb
    restart: always
    environment:
      - MARIADB_ROOT_PASSWORD=${DB_ROOT_PASSWORD}
      - MARIADB_DATABASE=${DB_NAME}
      - MARIADB_USER=${DB_USER}
      - MARIADB_PASSWORD=${DB_PASSWORD}
    volumes:
      - mariadb-data:/var/lib/mysql
      - /opt/jaraba/config/mariadb/my.cnf:/etc/mysql/conf.d/custom.cnf
      - /opt/jaraba/backups/database:/backups
    ports:
      - "127.0.0.1:3306:3306"  # Solo localhost, NO expuesto
    networks:
      - jaraba-network
    healthcheck:
      test: ["CMD", "healthcheck.sh", "--connect", "--innodb_initialized"]
      interval: 10s
      timeout: 5s
      retries: 5
    deploy:
      resources:
        limits:
          memory: 44G
        reservations:
          memory: 36G
  # ═══════════════════════════════════════════════════════
  # REDIS 7 - Cache, Sessions, Queues
  # ═══════════════════════════════════════════════════════
  redis:
    image: redis:7-alpine
    container_name: jaraba-redis
    restart: always
    command: >
      redis-server
      --maxmemory 5gb
      --maxmemory-policy allkeys-lru
      --requirepass ${REDIS_PASSWORD}
      --appendonly yes
      --appendfsync everysec
      --save 900 1
      --save 300 10
      --tcp-backlog 511
      --timeout 300
    volumes:
      - redis-data:/data
    networks:
      - jaraba-network
    healthcheck:
      test: ["CMD", "redis-cli", "-a", "${REDIS_PASSWORD}", "ping"]
      interval: 10s
      timeout: 3s
      retries: 3
    deploy:
      resources:
        limits:
          memory: 6G
        reservations:
          memory: 4G
  # ═══════════════════════════════════════════════════════
  # QDRANT 1.7 - Base de Datos Vectorial (IA/RAG)
  # ═══════════════════════════════════════════════════════
  qdrant:
    image: qdrant/qdrant:v1.7.4
    container_name: jaraba-qdrant
    restart: always
    environment:
      - QDRANT__SERVICE__API_KEY=${QDRANT_API_KEY}
      - QDRANT__LOG_LEVEL=WARN
      - QDRANT__STORAGE__PERFORMANCE__MAX_SEARCH_THREADS=4
    volumes:
      - qdrant-data:/qdrant/storage
    networks:
      - jaraba-network
    deploy:
      resources:
        limits:
          memory: 8G
        reservations:
          memory: 4G
  # ═══════════════════════════════════════════════════════
  # APACHE TIKA - Procesamiento de Documentos
  # ═══════════════════════════════════════════════════════
  tika:
    image: apache/tika:2.9.1
    container_name: jaraba-tika
    restart: always
    networks:
      - jaraba-network
    deploy:
      resources:
        limits:
          memory: 2G
  # ═══════════════════════════════════════════════════════
  # MINIO - Object Storage S3-Compatible
  # ═══════════════════════════════════════════════════════
  minio:
    image: minio/minio:latest
    container_name: jaraba-minio
    restart: always
    command: server /data --console-address ":9001"
    environment:
      - MINIO_ROOT_USER=${MINIO_ACCESS_KEY}
      - MINIO_ROOT_PASSWORD=${MINIO_SECRET_KEY}
    volumes:
      - minio-data:/data
    networks:
      - jaraba-network
    deploy:
      resources:
        limits:
          memory: 2G
# ═══════════════════════════════════════════════════════
# VOLUMES
# ═══════════════════════════════════════════════════════
volumes:
  traefik-letsencrypt:
  drupal-code:
  drupal-files:
  drupal-private:
  mariadb-data:
  redis-data:
  qdrant-data:
  minio-data:

# ═══════════════════════════════════════════════════════
# NETWORK
# ═══════════════════════════════════════════════════════
networks:
  jaraba-network:
    driver: bridge
    ipam:
      config:
        - subnet: 172.20.0.0/24
 
6. Fase 4: Configuraciones de Servicios
6.1 MariaDB Optimizado para 128GB DDR5
Archivo: /opt/jaraba/config/mariadb/my.cnf
Calibrado para 40GB de asignacion total (36GB buffer pool + overhead).
[mysqld]
# ═══════════════════════════════════════════════════════
# InnoDB Engine - Optimizado para 128GB DDR5 / NVMe
# ═══════════════════════════════════════════════════════
innodb_buffer_pool_size = 36G
innodb_buffer_pool_instances = 16     # 1 por cada 2-3GB
innodb_log_file_size = 1G
innodb_log_buffer_size = 128M
innodb_flush_log_at_trx_commit = 2   # Rendimiento (1 para max durabilidad)
innodb_flush_method = O_DIRECT
innodb_file_per_table = 1
innodb_io_capacity = 4000            # NVMe puede mas, conservador
innodb_io_capacity_max = 8000
innodb_read_io_threads = 8
innodb_write_io_threads = 8
innodb_purge_threads = 4
innodb_adaptive_hash_index = ON
innodb_change_buffering = all

# ═══════════════════════════════════════════════════════
# Connections
# ═══════════════════════════════════════════════════════
max_connections = 300
max_allowed_packet = 256M
wait_timeout = 300
interactive_timeout = 300
thread_cache_size = 32

# ═══════════════════════════════════════════════════════
# Query / Performance
# ═══════════════════════════════════════════════════════
query_cache_type = 0                 # Off (Redis es nuestro cache)
query_cache_size = 0
tmp_table_size = 256M
max_heap_table_size = 256M
join_buffer_size = 4M
sort_buffer_size = 4M
read_buffer_size = 2M
read_rnd_buffer_size = 4M
table_open_cache = 4000
table_definition_cache = 2000

# ═══════════════════════════════════════════════════════
# Character Set (Drupal requirement)
# ═══════════════════════════════════════════════════════
character_set_server = utf8mb4
collation_server = utf8mb4_unicode_ci

# ═══════════════════════════════════════════════════════
# Logging
# ═══════════════════════════════════════════════════════
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
log_queries_not_using_indexes = 1
log_slow_admin_statements = 1

# ═══════════════════════════════════════════════════════
# Binary Log (para replicacion futura y PITR)
# ═══════════════════════════════════════════════════════
log_bin = /var/lib/mysql/mysql-bin
binlog_format = ROW
expire_logs_days = 7
max_binlog_size = 256M
sync_binlog = 0                      # Rendimiento (1 para max durabilidad)
6.2 PHP-FPM Optimizado
Archivo: /opt/jaraba/config/php/www.conf
[www]
user = www-data
group = www-data
listen = 0.0.0.0:9000

; Pool sizing para 16 cores / 128GB
; Formula: max_children = RAM_asignada / memory_limit_per_worker
; 20GB / 512MB = ~40 workers
pm = dynamic
pm.max_children = 40
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 1000
pm.process_idle_timeout = 10s

; Status page para monitoring
pm.status_path = /fpm-status
ping.path = /fpm-ping
ping.response = pong

Archivo: /opt/jaraba/config/php/php-prod.ini
; ═══════════════════════════════════════════════════════
; PHP 8.3 Production - Jaraba Impact Platform
; ═══════════════════════════════════════════════════════

; Memory & Execution
memory_limit = 512M
max_execution_time = 120
max_input_time = 60
max_input_vars = 5000

; Upload
upload_max_filesize = 100M
post_max_size = 100M

; OPcache (CRITICO para rendimiento Drupal)
opcache.enable = 1
opcache.memory_consumption = 512
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0       ; No validar en produccion
opcache.revalidate_freq = 0
opcache.interned_strings_buffer = 64
opcache.jit = 1255
opcache.jit_buffer_size = 256M

; Realpath cache
realpath_cache_size = 4M
realpath_cache_ttl = 600

; Sessions (Redis)
session.save_handler = redis
session.save_path = "tcp://redis:6379?auth=${REDIS_PASSWORD}&database=1"

; Error handling
display_errors = Off
log_errors = On
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT

; Timezone
date.timezone = Europe/Madrid
AVISO: opcache.validate_timestamps = 0 significa que los cambios de codigo NO se reflejan hasta reiniciar PHP-FPM. Tras cada deploy: docker restart jaraba-drupal
6.3 Configuracion Drupal para Redis
Archivo: web/sites/default/settings.php (fragmento)
// ═══════════════════════════════════════════════════════
// REDIS CONFIGURATION
// ═══════════════════════════════════════════════════════
$settings['redis.connection']['host'] = 'redis';
$settings['redis.connection']['port'] = 6379;
$settings['redis.connection']['password'] = getenv('REDIS_PASSWORD');
$settings['redis.connection']['base'] = 0;  // DB 0 para cache
$settings['redis.connection']['interface'] = 'PhpRedis';
$settings['cache']['default'] = 'cache.backend.redis';
$settings['cache_prefix'] = 'jaraba';

// Cache bins especificos
$settings['cache']['bins']['render'] = 'cache.backend.redis';
$settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.redis';
$settings['cache']['bins']['page'] = 'cache.backend.redis';
$settings['cache']['bins']['bootstrap'] = 'cache.backend.redis';
$settings['cache']['bins']['discovery'] = 'cache.backend.redis';
$settings['cache']['bins']['config'] = 'cache.backend.redis';

// Flood control via Redis
$settings['queue_default'] = 'queue.redis_reliable';
6.4 Dockerfile de Drupal Produccion
Archivo: /opt/jaraba/Dockerfile.drupal
FROM php:8.3-fpm-bookworm

# System dependencies
RUN apt-get update && apt-get install -y \
    libfreetype6-dev libjpeg62-turbo-dev libpng-dev libwebp-dev \
    libzip-dev libicu-dev libxml2-dev libonig-dev \
    libmagickwand-dev ghostscript unzip git curl nginx \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions required by Drupal 11
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
    gd pdo_mysql mysqli intl opcache zip mbstring xml bcmath \
    && pecl install redis imagick apcu \
    && docker-php-ext-enable redis imagick apcu

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Nginx config
COPY config/nginx/drupal.conf /etc/nginx/sites-available/default

# Application code
WORKDIR /var/www/html
COPY drupal/ /var/www/html/

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Permissions
RUN chown -R www-data:www-data /var/www/html/web/sites/default/files
RUN mkdir -p /var/www/private && chown www-data:www-data /var/www/private

# Entrypoint
COPY scripts/docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80
CMD ["/usr/local/bin/docker-entrypoint.sh"]
 
7. Fase 5: Backup y Disaster Recovery
7.1 Estrategia de Backups
Componente	Frecuencia	Retencion	Destino
MariaDB (full dump)	Cada 6 horas	30 dias	Local + Minio/S3
MariaDB (binlog)	Continuo	7 dias	Local (PITR)
Drupal files	Diario (incremental)	14 dias	Minio/S3
Qdrant snapshots	Diario	7 dias	Minio/S3
Redis RDB	Cada 15 min (auto)	7 dias	Local
Configuracion (/opt/jaraba)	En cada cambio	Versionado	Git remoto
Server completo (imagen)	Semanal	4 semanas	IONOS Backup Space
7.2 Script Principal de Backup
Archivo: /opt/jaraba/scripts/backup.sh
#!/bin/bash
set -euo pipefail

# ═══════════════════════════════════════════════════════
# JARABA BACKUP SCRIPT - Ejecutado via cron cada 6h
# ═══════════════════════════════════════════════════════

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/opt/jaraba/backups"
LOG_FILE="/opt/jaraba/logs/backup_${DATE}.log"

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a $LOG_FILE; }

log "=== Inicio backup $DATE ==="

# 1. Database backup (hot, consistent)
log "Backing up MariaDB..."
docker exec jaraba-mariadb mariadb-dump \
  -u root -p"${DB_ROOT_PASSWORD}" \
  --single-transaction --quick --lock-tables=false \
  --routines --triggers --events \
  ${DB_NAME} | gzip > ${BACKUP_DIR}/database/db_${DATE}.sql.gz

log "DB backup size: $(du -h ${BACKUP_DIR}/database/db_${DATE}.sql.gz | cut -f1)"

# 2. Upload to Minio/S3
log "Uploading to Minio..."
docker exec jaraba-minio mc cp \
  ${BACKUP_DIR}/database/db_${DATE}.sql.gz \
  local/jaraba-backups/database/

# 3. Qdrant snapshot
log "Creating Qdrant snapshot..."
curl -s -X POST "http://localhost:6333/collections/jaraba_knowledge/snapshots" \
  -H "api-key: ${QDRANT_API_KEY}" || log "WARN: Qdrant snapshot failed"

# 4. Cleanup local (mantener 7 dias)
find ${BACKUP_DIR}/database -type f -mtime +7 -delete

log "=== Backup completado ==="
# Crontab entry:
0 */6 * * * /opt/jaraba/scripts/backup.sh >> /opt/jaraba/logs/cron.log 2>&1
7.3 RTO / RPO Objetivos
Metrica	Objetivo	Estrategia
RPO (Recovery Point Objective)	< 6 horas	Backups cada 6h + binlog continuo
RTO (Recovery Time Objective)	< 1 hora	Restore script automatizado
Failover DB	< 5 minutos	Restore desde ultimo backup + binlog replay
Restore completo	< 30 minutos	Imagen IONOS + restore datos
 
8. Fase 6: Monitoring Stack
El stack de monitoring se despliega como un Docker Compose separado, compartiendo la red.
8.1 docker-compose.monitoring.yml
Archivo: /opt/jaraba/docker-compose.monitoring.yml
Se referencia el contenido completo del doc 133_Platform_Monitoring_Alerting_v1 con las siguientes adaptaciones para 128GB:
Servicio	RAM Limit	Retencion	Puerto (solo localhost)
Prometheus	2 GB	30 dias	127.0.0.1:9090
Grafana	1 GB	Persistente	127.0.0.1:3000 (o via Traefik)
Loki	2 GB	14 dias	127.0.0.1:3100
Promtail	512 MB	-	Internal
AlertManager	256 MB	-	127.0.0.1:9093
Node Exporter	128 MB	-	127.0.0.1:9100
Blackbox Exporter	128 MB	-	127.0.0.1:9115
CRITICO: Los puertos de monitoring NUNCA se exponen al exterior. Acceso via Traefik con autenticacion o SSH tunnel.
8.2 Alertas Criticas Configuradas
Alerta	Condicion	Severidad	Accion
HighMemoryUsage	RAM > 85% durante 5min	Warning	Email al equipo
CriticalMemoryUsage	RAM > 95% durante 2min	Critical	Email + Slack
DiskSpaceLow	Disco > 80% usado	Warning	Email + cleanup auto
DiskSpaceCritical	Disco > 90% usado	Critical	Email + Slack + SMS
MariaDBDown	Container unhealthy	Critical	Auto-restart + alerta
RedisDown	Ping failed 3x	Critical	Auto-restart + alerta
HighCPU	CPU > 80% sustained 10min	Warning	Email
DrupalSlowRequests	P95 > 3s durante 5min	Warning	Email
SSLCertExpiry	< 14 dias para expirar	Warning	Email
BackupFailed	Script exit code != 0	Critical	Email + Slack
 
9. Fase 7: DNS, Dominios y SSL
9.1 Configuracion de Dominios
Dominio	Tipo	Destino	Proposito
app.jarabaimpact.com	A	<IP_SERVIDOR>	Aplicacion principal SaaS
api.jarabaimpact.com	A	<IP_SERVIDOR>	API REST endpoints
jarabaimpact.com	A	<IP_SERVIDOR>	Web corporativa B2B
www.jarabaimpact.com	CNAME	jarabaimpact.com	Redirect www
pepejaraba.com	A	<IP_SERVIDOR>	Marca personal
www.pepejaraba.com	CNAME	pepejaraba.com	Redirect www
plataformadeecosistemas.es	A	<IP_SERVIDOR>	Portal SaaS operativo
grafana.jarabaimpact.com	A	<IP_SERVIDOR>	Dashboard monitoring
traefik.jarabaimpact.com	A	<IP_SERVIDOR>	Dashboard Traefik (restringido)
9.2 Cloudflare WAF (Recomendado)
Si se usa Cloudflare como proxy (recomendado), configurar:
Regla	Accion	Proposito
OWASP Core Ruleset	Challenge/Block	Proteccion general contra ataques web
SQL Injection Detection	Block	Prevenir inyeccion SQL
XSS Protection	Block	Prevenir cross-site scripting
Rate Limiting	Challenge @ 100 req/10s	Anti-DDoS basico
Bot Management	Challenge	Filtrar bots maliciosos
Geo Blocking	Allow ES, PT, EU	Limitar geografia inicial
SSL en Cloudflare: modo Full (Strict). Traefik genera certificados Let's Encrypt; Cloudflare verifica que el origen usa HTTPS valido.
 
10. Fase 8: Migracion desde Servidor Managed
10.1 Inventario del Servidor Actual
Antes de migrar, documentar todo lo que existe en el servidor managed:
Elemento	Comando de Inventario	Destino en Nuevo Servidor
Base de datos Drupal	mysqldump (via panel IONOS)	MariaDB container
Archivos sites/default/files	rsync o tar	Volume drupal-files
Archivos private	rsync o tar	Volume drupal-private
Configuracion Drupal (settings.php)	Copiar + adaptar	Build de imagen Docker
Cronjobs	crontab -l	drupal-cron container
Certificados SSL	NO migrar	Traefik genera nuevos via LE
Dominios/DNS	Actualizar registros A	Apuntar a nueva IP
10.2 Procedimiento de Migracion
Secuencia paso a paso, disenada para minimizar downtime:
Dia D-7: Preparacion
# En servidor NUEVO (ya configurado con todo este runbook)
# Hacer un test completo con datos de ejemplo
docker compose -f docker-compose.prod.yml up -d
# Verificar que todos los containers arrancan
docker compose ps
Dia D-1: Pre-migracion
# En servidor ACTUAL (managed)
# Exportar base de datos completa
# (usar panel IONOS o phpMyAdmin para export)

# En servidor NUEVO
# Importar DB como test
docker exec -i jaraba-mariadb mariadb -u root -p"${DB_ROOT_PASSWORD}" ${DB_NAME} < dump.sql

# Sincronizar archivos (primera pasada, puede tardar)
rsync -avz --progress user@old-server:/path/to/files/ /opt/jaraba/drupal-files/
Dia D (Migracion): Ventana de 30-60 minutos
# 1. Poner sitio actual en mantenimiento
#    (via panel IONOS o drush state:set system.maintenance_mode 1)

# 2. Export FINAL de base de datos (con sitio en mantenimiento)

# 3. Import en servidor nuevo
docker exec -i jaraba-mariadb mariadb -u root -p"${DB_ROOT_PASSWORD}" ${DB_NAME} < final_dump.sql

# 4. Rsync final (solo deltas, rapido)
rsync -avz --delete user@old-server:/path/to/files/ /opt/jaraba/drupal-files/

# 5. Rebuild caches Drupal
docker exec jaraba-drupal drush cr
docker exec jaraba-drupal drush updb -y
docker exec jaraba-drupal drush cim -y  # Si hay config pendiente

# 6. Actualizar DNS (cambiar A records a nueva IP)
#    TTL previo debe haberse reducido a 300s dias antes

# 7. Verificar que el sitio funciona en nuevo servidor
curl -I https://app.jarabaimpact.com

# 8. Quitar modo mantenimiento
docker exec jaraba-drupal drush state:set system.maintenance_mode 0
CRITICO: Reducir TTL de DNS a 300 segundos AL MENOS 48h antes de la migracion. Si no, los usuarios seguiran llegando al servidor viejo durante horas.
 
11. Validacion Post-Implementacion
11.1 Checklist de Verificacion Completo
#	Verificacion	Comando	Resultado Esperado
01	CPU detectada correctamente	lscpu | grep 'Model name'	AMD EPYC 4545P
02	RAM disponible	free -h | grep Mem	~125Gi
03	RAID1 operativo	cat /proc/mdstat	[UU] (ambos discos activos)
04	Docker running	docker info | head -5	Server Version: 27.x
05	Todos containers UP	docker compose ps	Todos: Up (healthy)
06	MariaDB acepta conexiones	docker exec jaraba-mariadb mariadb -u drupal -p... -e 'SELECT 1'	1
07	Redis responde	docker exec jaraba-redis redis-cli -a ... ping	PONG
08	Qdrant health	curl localhost:6333/healthz	{"title":"qdrant..."}
09	Drupal homepage	curl -I https://app.jarabaimpact.com	HTTP/2 200
10	SSL valido	curl -vI https://app.jarabaimpact.com 2>&1 | grep 'SSL certificate verify ok'	OK
11	PHP OPcache activo	docker exec jaraba-drupal php -i | grep opcache.enable	On
12	Redis como cache Drupal	docker exec jaraba-drupal drush ev "print \Drupal::cache()->getBackendClass();"	Redis
13	Backup script funciona	/opt/jaraba/scripts/backup.sh	Exit code 0, archivo creado
14	Monitoring accesible	curl -I http://127.0.0.1:3000	HTTP 200 (Grafana)
15	Firewall activo	ufw status	Status: active, solo 2222/80/443
16	Fail2ban activo	fail2ban-client status sshd	Currently banned: 0
11.2 Test de Carga Basico
# Instalar Apache Bench (si no esta)
apt install -y apache2-utils

# Test basico: 100 requests, 10 concurrentes
ab -n 100 -c 10 https://app.jarabaimpact.com/

# Resultado esperado:
# Requests per second: > 50 req/s
# Time per request: < 200ms (mean)
# Failed requests: 0

# Test de stress: 1000 requests, 50 concurrentes
ab -n 1000 -c 50 https://app.jarabaimpact.com/
# Verificar que no hay errores 5xx
 
12. Comandos de Operacion Diaria
Referencia rapida para el equipo de operaciones y Claude Code.
12.1 Gestion de Containers
Operacion	Comando
Ver estado de todos los servicios	docker compose -f docker-compose.prod.yml ps
Ver logs en tiempo real	docker compose -f docker-compose.prod.yml logs -f --tail=100
Ver logs de un servicio	docker logs -f jaraba-drupal --tail=200
Reiniciar Drupal (tras deploy)	docker restart jaraba-drupal
Rebuild y restart Drupal	docker compose -f docker-compose.prod.yml up -d --build drupal
Parar todo	docker compose -f docker-compose.prod.yml down
Parar todo + borrar volumes	docker compose -f docker-compose.prod.yml down -v  (PELIGRO)
Limpiar imagenes no usadas	docker image prune -a
12.2 Drush (Drupal CLI)
Operacion	Comando
Rebuild cache	docker exec jaraba-drupal drush cr
Login como admin	docker exec jaraba-drupal drush uli
Ejecutar updates DB	docker exec jaraba-drupal drush updb -y
Importar config	docker exec jaraba-drupal drush cim -y
Exportar config	docker exec jaraba-drupal drush cex -y
Ver logs (watchdog)	docker exec jaraba-drupal drush ws --count=50
Modo mantenimiento ON	docker exec jaraba-drupal drush state:set system.maintenance_mode 1
Modo mantenimiento OFF	docker exec jaraba-drupal drush state:set system.maintenance_mode 0
Verificar estado Redis	docker exec jaraba-drupal drush ev "var_dump(\Drupal::service('redis.factory')->getClient()->ping());"
12.3 Base de Datos
Operacion	Comando
Conectar a MySQL CLI	docker exec -it jaraba-mariadb mariadb -u root -p
Tamano de la BD	SELECT table_schema, ROUND(SUM(data_length+index_length)/1024/1024,2) AS 'MB' FROM information_schema.tables GROUP BY table_schema;
Tablas mas grandes	SELECT table_name, ROUND((data_length+index_length)/1024/1024,2) AS 'MB' FROM information_schema.tables WHERE table_schema='jaraba' ORDER BY (data_length+index_length) DESC LIMIT 20;
Conexiones activas	SHOW PROCESSLIST;
Estado InnoDB	SHOW ENGINE INNODB STATUS\G
Backup manual	docker exec jaraba-mariadb mariadb-dump -u root -p${DB_ROOT_PASSWORD} --single-transaction jaraba | gzip > backup_manual.sql.gz
 
13. Diferencias con Documento 131
Este documento (131b) sustituye parcialmente al doc 131_Platform_Infrastructure_Deployment_v1. Las diferencias clave son:
Aspecto	Doc 131 (Original)	Doc 131b (Este documento)
Servidor target	EPYC 7702P, 256GB DDR4	EPYC 4545P Zen 5, 128GB DDR5
MariaDB buffer pool	24 GB	36 GB (right-sized para 128GB)
Redis maxmemory	8 GB	5 GB (suficiente, reserva para otros)
PHP-FPM max_children	100	40 (ajustado a RAM disponible)
Drupal memory limit	16G container	20G container (optimizado)
Qdrant memory	16 GB	8 GB (suficiente para fase inicial)
Datacenter	Frankfurt, DE	Logrono, ES (menor latencia)
Nivel de detalle	Arquitectura general	Runbook ejecutable por Claude Code
Hardening	Checklist basico	Comandos completos paso a paso
Migracion	No incluida	Procedimiento detallado incluido

INFO: El doc 131 sigue siendo valido como referencia arquitectonica. Este doc 131b es la guia de implementacion practica.
 
14. Roadmap de Escalado
Plan de crecimiento desde la configuracion inicial hasta la saturacion del servidor.
Fase	Trigger	Accion	Usuarios estimados
Inicial (actual)	Deploy de produccion	Config de este documento	0-500 usuarios
Fase 1	CPU > 60% sostenido	Aumentar PHP-FPM workers a 60	500-2.000
Fase 2	DB > 50GB	Optimizar queries + indices	2.000-5.000
Fase 3	RAM > 85% sostenido	Reducir Redis a 4GB, ajustar buffer pool	5.000-10.000
Fase 4	Limite del servidor	Migrar a AE32-256 NVMe (32c, 256GB, 264 EUR/mes)	> 10.000
Fase 5	Multi-region	Segundo servidor + DB replica	> 25.000

Nota Sin Humo: Con 16 cores Zen 5 a 5.4 GHz y 128GB DDR5, este servidor es mas que suficiente para los primeros 2-3 anos de operacion. El cuello de botella sera siempre el numero de usuarios concurrentes, no la capacidad del hardware. Optimizar codigo y queries es mas rentable que comprar mas servidor.

15. Conclusion
Este runbook proporciona un camino completo desde servidor vacio hasta plataforma SaaS operativa. Cada comando, cada archivo de configuracion, y cada parametro esta calibrado para el hardware real contratado (AMD EPYC 4545P, 128GB DDR5, 2TB NVMe) y para las necesidades reales del ecosistema Jaraba.

Las Fases 0-4 (hardening + Docker + servicios) se pueden ejecutar en un dia de trabajo. La Fase 5 (backup) requiere verificacion durante 48h. La Fase 6 (monitoring) se despliega en paralelo. La Fase 7 (DNS) se coordina con los registradores de dominio. La Fase 8 (migracion) se ejecuta cuando el codigo este listo para produccion.

Resultado esperado: Un servidor de produccion hardened, monitorizado, con backups automaticos, SSL automatico, y capacidad para servir las verticales de Empleabilidad, Emprendimiento y Andalucia +ei sin restricciones de base de datos, conexiones, o servicios auxiliares.

--- Fin del Documento ---
