
PLAN DE IMPLEMENTACION
SERVIDOR DEDICADO AMD EPYC ZEN 5
IONOS AE12-128 NVMe — Stack Nativo
Runbook Definitivo para Claude Code — v3.0
JARABA IMPACT PLATFORM

Campo	Valor
Version:	3.0 (Definitiva — Stack Nativo)
Fecha:	Marzo 2026
Estado:	Ready for Implementation
Codigo:	131b_Server_Implementation_Runbook_AMD_v3
Prioridad:	CRITICA - Prerequisito de produccion
Destinatario:	EDI Google Antigravity / Claude Code
Sustituye a:	131_v1 (Docker aspiracional) + 131b_v1 + 131b_v2
Servidor:	IONOS AE12-128 NVMe (EPYC 4465P Zen 5, 12c/24t)
Arquitectura:	STACK NATIVO (Nginx + PHP-FPM + MariaDB + Redis)
Verificado contra:	Estado SaaS v1.0.0 (03-03-2026) + Analisis Claude Code (11-03-2026)
 
Tabla de Contenidos
Tabla de Contenidos	1
1. Analisis de Infraestructura y Decisiones Estrategicas	1
1.1 Migracion obligatoria del servidor Managed	1
1.2 Seleccion: AE12-128 NVMe (EPYC 4465P Zen 5)	1
1.3 Decision: Stack Nativo (NO Docker)	1
1.4 Decision: Nginx (NO Traefik)	1
1.5 Servicios externos (NO en el servidor)	1
1.6 Almacenamiento en 3 capas	1
1.7 Proyeccion almacenamiento 5 anos (con palancas)	1
2. Previsiones Economicas Escalonadas (5 Anos)	1
2.1 Ano 1 — Lanzamiento (3 verticales, 0-500 usuarios)	1
2.2 Ano 2 — Traccion (5-7 verticales, 500-2.000 usuarios)	1
2.3 Ano 3 — Consolidacion (8-10 verticales, 2.000-5.000 usuarios)	1
2.4 Ano 4 — Escalado (5.000-10.000 usuarios)	1
2.5 Ano 5 — Madurez (10.000-25.000 usuarios)	1
2.6 Tabla Consolidada	1
3. Especificaciones y Distribucion de Memoria	1
3.1 Hardware	1
3.2 Mapa de Memoria — Ano 1 (escalonado)	1
4. Fase 0: Provision y Acceso Inicial	1
5. Fase 1: Hardening + Instalacion Stack Nativo	1
5.1 Actualizacion y paquetes base	1
5.2 Instalar MariaDB 10.11	1
5.3 Configuracion MariaDB (escalonada)	1
5.4 Instalar PHP 8.4 + extensiones	1
5.5 PHP-FPM: 24 workers (corregido para 12c/24t)	1
5.6 Instalar Redis 7.4	1
5.7 Instalar Nginx	1
5.8 Instalar Certbot + Wildcard SSL	1
5.9 SSH hardening + Firewall + Fail2ban	1
5.10 Nginx rate limiting para API	1
5.11 Instalar Supervisor (AI workers)	1
5.12 Instalar Tika (unico container Docker)	1
6. Procedimiento de Deploy	1
7. Backup y Disaster Recovery	1
7.1 Script backup (cada 6h → R2 + local 24h)	1
7.2 NAS 16TB via GoodSync	1
7.3 RTO/RPO	1
8. Monitoring	1
9. DNS y Dominios	1
10. Migracion desde Servidor Managed	1
11. Validacion Post-Implementacion	1
12. Comandos de Operacion Diaria	1
13. Problemas Resueltos del Analisis Claude Code	1
14. Conclusion	1

 
1. Analisis de Infraestructura y Decisiones Estrategicas
Este apartado documenta el razonamiento detras de cada decision, verificado contra el codebase real y el analisis de Claude Code (puntuacion 8.5/10, 10 problemas identificados, todos resueltos en esta v3).
1.1 Migracion obligatoria del servidor Managed
El servidor Managed IONOS L-16 NVMe tiene restricciones incompatibles con un SaaS de 92 modulos, 441 entidades y 925 servicios:
Restriccion	Limite	Necesidad	Impacto
Base de datos	5 GB hard limit	20-100+ GB	Bloqueo writes automatico
Conexiones DB	40 paralelas	200-500+	Errores 503
Root access	NO	Obligatorio	Sin Redis, sin tuning
my.cnf custom	NO	Obligatorio	Sin InnoDB optimization
Alta carga	Prohibido en ToS	Es la naturaleza del SaaS	Riesgo suspension
Con 3 verticales y 50 participantes PIIL, el limite de 5GB se alcanza en 2-4 meses.
1.2 Seleccion: AE12-128 NVMe (EPYC 4465P Zen 5)
El AE16-128 NVMe XL (recomendacion original) no esta disponible. De las opciones restantes:
	AE12-128 (Elegido)	AE16-128 Zen 2	AE32-256 Zen 3
CPU	EPYC 4465P Zen 5, 12c/24t	EPYC 7302P Zen 2, 16c/32t	EPYC 7543P Zen 3, 32c/64t
Freq. max	5.4 GHz	3.3 GHz	3.7 GHz
RAM	128 GB DDR5	128 GB DDR4	256 GB DDR4
Disco	2x1TB NVMe RAID 1	2x960GB NVMe HW RAID	2x1920GB NVMe HW RAID
Precio/mes	144 EUR	128 EUR	264 EUR
Single-thread	EXCELENTE	BAJO	MEDIO
Drupal procesa cada request en un solo thread. 12 cores Zen 5 a 5.4 GHz superan a 16 cores Zen 2 a 3.3 GHz en latencia percibida por el usuario.
1.3 Decision: Stack Nativo (NO Docker)
Esta es la decision arquitectonica mas importante del runbook. El analisis de Claude Code (Problema 2) detecto que el codebase tiene nginx-metasites.conf, nginx-jaraba-common.conf, configuracion de unix sockets PHP-FPM, y Supervisor workers con paths nativos — todo apuntando a stack nativo, no Docker.

Aspecto	Stack Nativo (Elegido)	Docker (descartado)
Rendimiento I/O	ext4 directo	overlay2 (5-15% overhead)
PHP-FPM	Unix socket (/run/php/php8.4-fpm.sock)	TCP via Docker network (latencia)
Static files	Nginx sirve directo desde disco	Proxy layer adicional
Debugging	drush, tail logs, strace directo	docker exec + layers
Coherencia con codebase	100% (nginx configs ya existen)	Requiere reescribir configs
Deploy	git pull + composer + drush (2-5 min)	docker build + push + pull (similar)
Rollback	git checkout + drush (2-5 min)	docker tag anterior (10s codigo, DB igual)
Complejidad operacional	Baja (1 servidor, 1 app)	Media (networking, volumes, logs)

El rollback de Docker solo cubre codigo, no base de datos. Si drush updb ya ejecuto migraciones, hay que restaurar backup de DB igualmente. La ventaja real de Docker (reproducibilidad) se mitiga con el Anexo C del doc 159 (Paridad Lando-Produccion).
INFO: Docker se reconsiderara en ano 4-5 si se migra a multi-servidor. Para single-server dedicado con 1 aplicacion, nativo es superior.
1.4 Decision: Nginx (NO Traefik)
El codebase tiene nginx-metasites.conf con 6 server blocks configurados, nginx-jaraba-common.conf con snippets, y certbot --nginx para SSL. Traefik anade una capa innecesaria para single-server. Nginx sirve static files directamente y conecta a PHP-FPM via unix socket — rendimiento optimo.
1.5 Servicios externos (NO en el servidor)
Servicio	Proveedor	Justificacion	Coste
Qdrant (vectores IA)	Qdrant Cloud	Arquitectura verificada (Anexo A1): HTTPS obligatorio en produccion	Free → ~150 EUR/mes (ano 5)
Almacenamiento objetos	Cloudflare R2	Backups (Palanca 1) + CDN files publicos (Palanca 2, ano 2)	~1 → ~60 EUR/mes
Email delivery	SendGrid API / SMTP IONOS	jaraba_email es el cerebro; SendGrid/SMTP es la tuberia de entrega	Free → ~75 EUR/mes
Nota sobre email: Ano 1 usa SMTP IONOS (gratuito, ya configurado en codebase con Symfony Mailer) para transaccional basico. Cuando jaraba_email se active (ano 2+), se anade SendGrid API para tracking avanzado (opens, clicks, webhooks). Coexisten.
1.6 Almacenamiento en 3 capas
Capa	Ubicacion	Tecnologia	Retencion
1. Disco NVMe	Servidor IONOS (Logrono)	ext4 nativo	Datos activos
2. Cloud caliente	Cloudflare R2	S3 + CDN via rclone	30 dias backups, indefinido files
3. Archivo frio	NAS 16TB oficina	GoodSync → SFTP puerto 2222	Indefinida
1.7 Proyeccion almacenamiento 5 anos (con palancas)
Componente	Ano 1	Ano 2	Ano 3	Ano 4	Ano 5
SO + aplicacion Drupal	35 GB	40 GB	45 GB	48 GB	50 GB
MariaDB (data+indices)	10 GB	35 GB	70 GB	100 GB	150 GB
MariaDB binlog	3 GB	7 GB	10 GB	15 GB	20 GB
Drupal public files	15 GB	50 GB*	15 GB*	15 GB*	15 GB*
Drupal private files	5 GB	15 GB	35 GB	50 GB	70 GB
Redis persistence	3 GB	4 GB	5 GB	6 GB	7 GB
Backups locales (24h)	5 GB	10 GB	15 GB	20 GB	25 GB
Monitoring	15 GB	20 GB	25 GB	28 GB	30 GB
TOTAL	91 GB	181 GB	220 GB	282 GB	367 GB
% disco (1TB)	9%	18%	22%	28%	37%
* Palanca 2 activa desde ano 2: archivos publicos migrados a R2 via s3fs.
 
2. Previsiones Economicas Escalonadas (5 Anos)
2.1 Ano 1 — Lanzamiento (3 verticales, 0-500 usuarios)
Servicio	Tier	Coste/mes	Notas
Servidor AE12-128 NVMe	Completo	144 EUR	Fijo 24 meses
Qdrant Cloud	Free (1GB)	0 EUR	RAG inicial
Cloudflare	Free + R2 (<10GB)	~1 EUR	CDN basico gratis
Claude API	~20K tok/dia (Haiku)	~30-50 EUR	Diagnosticos, copilot basico
Email delivery	SMTP IONOS (gratis)	0 EUR	Transaccional basico (<100/dia)
Dominios (3)	Renovaciones	~5 EUR	3 dominios
NAS 16TB	GoodSync SFTP	0 EUR	Ya disponible
TOTAL ANO 1: ~180-200 EUR/mes (~2.160-2.400 EUR/ano)
2.2 Ano 2 — Traccion (5-7 verticales, 500-2.000 usuarios)
Servicio	Tier	Coste/mes	Cambio
Servidor	Sin cambios	144 EUR	=
Qdrant Cloud	Starter (~4GB)	~25 EUR	+25
Cloudflare	Pro+WAF+R2(~50GB)	~30 EUR	+29
Claude API	~50K tok/dia	~80-120 EUR	+60
SendGrid	Essentials (40K/mes)	~15 EUR	+15 (jaraba_email activo)
Dominios	+ subdominios	~8 EUR	+3
NAS 16TB	Sin cambios	0 EUR	=
TOTAL ANO 2: ~302-342 EUR/mes (~3.624-4.104 EUR/ano)
2.3 Ano 3 — Consolidacion (8-10 verticales, 2.000-5.000 usuarios)
Servicio	Tier	Coste/mes	Cambio
Servidor	CPU ~40-50%	144 EUR	=
Qdrant Cloud	Growth (~10GB)	~50 EUR	+25
Cloudflare	Pro+WAF+R2(~200GB)	~35 EUR	+5
Claude API	~100K tok/dia (11 agentes)	~150 EUR	+50
SendGrid	Pro (100K/mes, IP dedicada)	~35 EUR	+20
Make.com	Starter (jaraba_social)	~10 EUR	+10
Dominios	Consolidado	~10 EUR	+2
TOTAL ANO 3: ~434 EUR/mes (~5.208 EUR/ano)
2.4 Ano 4 — Escalado (5.000-10.000 usuarios)
Servicio	Tier	Coste/mes	Cambio
Servidor	CPU ~60-70%, aguanta	144 EUR	=
Qdrant Cloud	Business (~25GB)	~100 EUR	+50
Cloudflare	Pro+WAF+R2(~500GB)	~40 EUR	+5
Claude API	~200K tok/dia	~250 EUR	+100
SendGrid	Pro (250K/mes)	~55 EUR	+20
Make.com	Pro	~25 EUR	+15
Dominios	+ regionales	~15 EUR	+5
TOTAL ANO 4: ~629 EUR/mes (~7.548 EUR/ano)
2.5 Ano 5 — Madurez (10.000-25.000 usuarios)
Servicio	Tier	Coste/mes	Cambio
Servidor	AE12-128 o upgrade AE32-256	144-264 EUR	0-120
Qdrant Cloud	Business+ (~50GB)	~150 EUR	+50
Cloudflare	Business+WAF+R2(~1TB)	~60 EUR	+20
Claude API	~300K+ tok/dia	~350 EUR	+100
SendGrid	Pro (500K/mes)	~75 EUR	+20
Make.com	Pro+	~35 EUR	+10
Dominios	Consolidado	~15 EUR	=
TOTAL ANO 5: ~829-949 EUR/mes (~9.948-11.388 EUR/ano)
2.6 Tabla Consolidada
	Ano 1	Ano 2	Ano 3	Ano 4	Ano 5
Usuarios	0-500	500-2K	2K-5K	5K-10K	10K-25K
Verticales	3	5-7	8-10	10	10+
Coste/mes	~190 EUR	~320 EUR	~434 EUR	~629 EUR	~889 EUR
Coste/ano	~2.280 EUR	~3.840 EUR	~5.208 EUR	~7.548 EUR	~10.668 EUR
Acumulado	2.280 EUR	6.120 EUR	11.328 EUR	18.876 EUR	29.544 EUR
DB estimada	~10 GB	~35 GB	~70 GB	~100 GB	~150 GB
Buffer pool	16 GB	24 GB	40 GB	40 GB	40+ GB
Disco usado	9%	18%	22%	28%	37%
 
3. Especificaciones y Distribucion de Memoria
3.1 Hardware
Componente	Especificacion
Modelo	IONOS AE12-128 NVMe
CPU	AMD EPYC 4465P (Zen 5), 12c/24t, 3.4-5.4 GHz
RAM	128 GB DDR5 ECC
Disco	2x 1TB NVMe SSD, Software RAID 1 (1TB usable)
PCIe	5.0
Red	1 Gbps, trafico ilimitado
Datacenter	Logrono, Espana
Coste	144 EUR/mes (24 meses, IVA excl.)
3.2 Mapa de Memoria — Ano 1 (escalonado)
Buffer pool InnoDB escalado progresivamente (16GB ano 1 → 40GB ano 3+). Sin Qdrant ni Minio local.
Servicio	RAM Ano 1	RAM Ano 3+	Notas
Ubuntu 24.04 + kernel	4 GB	4 GB	Base
MariaDB 10.11	20 GB	44 GB	Buffer pool 16GB→40GB + overhead
PHP-FPM 8.4 (Drupal)	12 GB	12 GB	24 workers x 512MB
Nginx	512 MB	512 MB	Static files + reverse proxy PHP
Redis 7.4	6 GB	6 GB	Cache, sessions, queues
Supervisor (5 AI workers)	2.5 GB	2.5 GB	drush queue:run (5 procesos)
Apache Tika 2.9.1	2 GB	2 GB	PDF processing (puede ser Docker)
Certbot	64 MB	64 MB	SSL renewal
Monitoring (Prometheus+Grafana+Loki)	6 GB	6 GB	Metricas 30d, logs 14d
RESERVA libre	75 GB	51 GB	59% → 40%
OK: Ano 1 tiene 75GB libres (59%). Esto permite absorber picos sin ningun riesgo de OOM. Al escalar MariaDB a 40GB en ano 3, la reserva baja a 40% — sigue siendo holgada.
Nota sobre Tika: Es el unico servicio que puede mantenerse como container Docker (docker run apache/tika:2.9.1) porque es stateless, no necesita acceso a disco Drupal, y la imagen oficial es la forma mas simple de mantenerlo. El resto del stack es nativo.
 
4. Fase 0: Provision y Acceso Inicial
Producto: AE12-128 NVMe | Ubicacion: Logrono | SO: Ubuntu 24.04 LTS | 24 meses
ssh root@<IP_PUBLICA>
lscpu | grep -E 'Model name|CPU\(s\)|Thread|Core'
free -h && lsblk
# Esperado: EPYC 4465P, 24 threads, ~125Gi, 2x NVMe ~1TB

cat /proc/mdstat  # Verificar RAID 1 activo [UU]
AVISO: Verificar /var >500GB. Si particionado automatico no cumple, redimensionar ANTES de instalar servicios.
5. Fase 1: Hardening + Instalacion Stack Nativo
5.1 Actualizacion y paquetes base
apt update && apt upgrade -y
apt install -y curl wget git unzip htop iotop net-tools \
  software-properties-common ca-certificates gnupg lsb-release \
  fail2ban ufw logrotate jq rclone acl
timedatectl set-timezone Europe/Madrid
locale-gen es_ES.UTF-8
update-locale LANG=es_ES.UTF-8
5.2 Instalar MariaDB 10.11
# Repositorio oficial MariaDB 10.11
curl -sS https://downloads.mariadb.com/MariaDB/mariadb_repo_setup | bash -s -- --mariadb-server-version=10.11
apt install -y mariadb-server mariadb-client

systemctl enable mariadb
mariadb-secure-installation

# Crear base de datos y usuario
mariadb -u root -p <<EOF
CREATE DATABASE jaraba CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'drupal'@'localhost' IDENTIFIED BY '<DB_PASSWORD>';
GRANT ALL PRIVILEGES ON jaraba.* TO 'drupal'@'localhost';
FLUSH PRIVILEGES;
EOF
5.3 Configuracion MariaDB (escalonada)
Archivo: /etc/mysql/mariadb.conf.d/99-jaraba.cnf
[mysqld]
# ═══ InnoDB — Ano 1: 16GB (escalar a 24GB ano 2, 40GB ano 3+) ═══
innodb_buffer_pool_size = 16G
innodb_buffer_pool_instances = 8
innodb_log_file_size = 1G
innodb_log_buffer_size = 128M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
innodb_file_per_table = 1
innodb_io_capacity = 4000
innodb_io_capacity_max = 8000
innodb_read_io_threads = 4
innodb_write_io_threads = 4

# ═══ Conexiones ═══
max_connections = 300
max_allowed_packet = 256M
wait_timeout = 300
thread_cache_size = 32

# ═══ Query ═══
tmp_table_size = 256M
max_heap_table_size = 256M
join_buffer_size = 4M
sort_buffer_size = 4M
table_open_cache = 4000

# ═══ Character Set ═══
character_set_server = utf8mb4
collation_server = utf8mb4_unicode_ci

# ═══ Logging ═══
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2

# ═══ Binlog (PITR, expire 3 dias ano 1) ═══
log_bin = /var/lib/mysql/mysql-bin
binlog_format = ROW
expire_logs_days = 3
max_binlog_size = 256M
systemctl restart mariadb
mariadb -u root -p -e "SHOW VARIABLES LIKE 'innodb_buffer_pool_size';"
5.4 Instalar PHP 8.4 + extensiones
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.4-fpm php8.4-cli php8.4-mysql php8.4-gd \
  php8.4-curl php8.4-xml php8.4-mbstring php8.4-zip php8.4-intl \
  php8.4-bcmath php8.4-redis php8.4-imagick php8.4-apcu php8.4-opcache

systemctl enable php8.4-fpm
5.5 PHP-FPM: 24 workers (corregido para 12c/24t)
Archivo: /etc/php/8.4/fpm/pool.d/jaraba.conf
[jaraba]
user = www-data
group = www-data
listen = /run/php/php8.4-fpm-jaraba.sock
listen.owner = www-data
listen.group = www-data

pm = dynamic
pm.max_children = 24
pm.start_servers = 6
pm.min_spare_servers = 4
pm.max_spare_servers = 12
pm.max_requests = 1000
pm.process_idle_timeout = 10s
pm.status_path = /fpm-status

Archivo: /etc/php/8.4/fpm/conf.d/99-jaraba-prod.ini
memory_limit = 512M
max_execution_time = 120
upload_max_filesize = 100M
post_max_size = 100M

opcache.enable = 1
opcache.memory_consumption = 512
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0
opcache.revalidate_freq = 0
opcache.jit = 1255
opcache.jit_buffer_size = 256M

realpath_cache_size = 4M
realpath_cache_ttl = 600

session.save_handler = redis
session.save_path = "tcp://127.0.0.1:6379?auth=<REDIS_PASSWORD>&database=1"

date.timezone = Europe/Madrid
display_errors = Off
log_errors = On
AVISO: opcache.validate_timestamps = 0: tras cada deploy es OBLIGATORIO ejecutar: systemctl reload php8.4-fpm
5.6 Instalar Redis 7.4
curl -fsSL https://packages.redis.io/gpg | gpg --dearmor -o /usr/share/keyrings/redis-archive-keyring.gpg
echo "deb [signed-by=/usr/share/keyrings/redis-archive-keyring.gpg] https://packages.redis.io/deb $(lsb_release -cs) main" | tee /etc/apt/sources.list.d/redis.list
apt update && apt install -y redis-server
Archivo: /etc/redis/redis.conf (modificaciones):
bind 127.0.0.1
requirepass <REDIS_PASSWORD>
maxmemory 5gb
maxmemory-policy allkeys-lru
appendonly yes
appendfsync everysec
systemctl enable redis-server && systemctl restart redis-server
5.7 Instalar Nginx
apt install -y nginx
systemctl enable nginx
Copiar configuraciones existentes del codebase:
cp config/deploy/nginx-metasites.conf /etc/nginx/sites-available/jaraba-metasites.conf
cp config/deploy/nginx-jaraba-common.conf /etc/nginx/snippets/jaraba-common.conf
ln -s /etc/nginx/sites-available/jaraba-metasites.conf /etc/nginx/sites-enabled/
rm /etc/nginx/sites-enabled/default

nginx -t && systemctl reload nginx
5.8 Instalar Certbot + Wildcard SSL
Wildcard SSL para *.plataformadeecosistemas.com requiere DNS challenge (NO HTTP challenge):
apt install -y certbot python3-certbot-nginx python3-certbot-dns-cloudflare

# Crear credenciales Cloudflare
mkdir -p /root/.secrets && chmod 700 /root/.secrets
cat > /root/.secrets/cloudflare.ini << EOF
dns_cloudflare_api_token = <CLOUDFLARE_API_TOKEN>
EOF
chmod 600 /root/.secrets/cloudflare.ini

# Generar wildcard cert
certbot certonly \
  --dns-cloudflare \
  --dns-cloudflare-credentials /root/.secrets/cloudflare.ini \
  -d plataformadeecosistemas.com \
  -d '*.plataformadeecosistemas.com' \
  -d plataformadeecosistemas.es \
  -d '*.plataformadeecosistemas.es' \
  --agree-tos -m tech@jarabaimpact.com

# Certs individuales para otros dominios (HTTP challenge)
certbot --nginx -d jarabaimpact.com -d www.jarabaimpact.com
certbot --nginx -d pepejaraba.com -d www.pepejaraba.com
certbot --nginx -d jaraba.es -d www.jaraba.es
CRITICO: Sin wildcard SSL via DNS challenge, los subdominios de tenant NO tendran HTTPS. Este paso es BLOQUEANTE para multi-tenancy.
5.9 SSH hardening + Firewall + Fail2ban
# /etc/ssh/sshd_config.d/jaraba-hardening.conf
Port 2222
PermitRootLogin no
PasswordAuthentication no
AllowUsers jaraba
MaxAuthTries 3

sshd -t && systemctl restart sshd
ufw default deny incoming && ufw default allow outgoing
ufw allow 2222/tcp  # SSH
ufw allow 80/tcp    # HTTP
ufw allow 443/tcp   # HTTPS
ufw enable
CRITICO: Probar SSH por puerto 2222 ANTES de cerrar sesion root.
5.10 Nginx rate limiting para API
Anadir a nginx-jaraba-common.conf:
# Rate limiting zones
limit_req_zone $binary_remote_addr zone=api:10m rate=30r/s;
limit_req_zone $binary_remote_addr zone=ai:10m rate=5r/s;

# Aplicar en locations
location /api/v1/ {
    limit_req zone=api burst=60 nodelay;
    # ... proxy pass a PHP-FPM
}

location /api/v1/ai/ {
    limit_req zone=ai burst=10 nodelay;
    # ... proxy pass a PHP-FPM
}
Protege los 1.295+ endpoints y especialmente los endpoints IA (costosos en tokens API).
5.11 Instalar Supervisor (AI workers)
apt install -y supervisor

# /etc/supervisor/conf.d/jaraba-ai-workers.conf
[program:jaraba-queue-ai]
command=/var/www/jaraba/vendor/bin/drush queue:run ai_processing --time-limit=300
directory=/var/www/jaraba
user=www-data
numprocs=5
process_name=%(program_name)s_%(process_num)02d
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/supervisor/jaraba-queue-%(process_num)02d.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=3

supervisorctl reread && supervisorctl update
5.12 Instalar Tika (unico container Docker)
# Docker solo para Tika (stateless, sin acceso a disco Drupal)
apt install -y docker.io
docker run -d --name tika --restart always -p 127.0.0.1:9998:9998 apache/tika:2.9.1
 
6. Procedimiento de Deploy
Con stack nativo y opcache.validate_timestamps=0, cada deploy requiere estos pasos exactos:
#!/bin/bash
# /opt/jaraba/scripts/deploy.sh
set -euo pipefail

cd /var/www/jaraba

echo '[1/7] Activando modo mantenimiento...'
vendor/bin/drush state:set system.maintenance_mode 1

echo '[2/7] Pulling codigo...'
git pull origin main

echo '[3/7] Instalando dependencias...'
composer install --no-dev --optimize-autoloader --no-interaction

echo '[4/7] Actualizando base de datos...'
vendor/bin/drush updb -y

echo '[5/7] Importando configuracion...'
vendor/bin/drush cim -y

echo '[6/7] Reconstruyendo caches + recargando PHP-FPM...'
vendor/bin/drush cr
systemctl reload php8.4-fpm   # OBLIGATORIO: invalida OPcache

echo '[7/7] Desactivando modo mantenimiento...'
vendor/bin/drush state:set system.maintenance_mode 0

echo 'Deploy completado.'

Rollback:
cd /var/www/jaraba
vendor/bin/drush state:set system.maintenance_mode 1
git checkout <TAG_ANTERIOR>
composer install --no-dev --optimize-autoloader
vendor/bin/drush updb -y
vendor/bin/drush cim -y
vendor/bin/drush cr
systemctl reload php8.4-fpm
vendor/bin/drush state:set system.maintenance_mode 0
AVISO: Si drush updb ejecuto migraciones irreversibles, el rollback de codigo no es suficiente. Restaurar backup de DB previo al deploy.
 
7. Backup y Disaster Recovery
7.1 Script backup (cada 6h → R2 + local 24h)
#!/bin/bash
# /opt/jaraba/scripts/backup.sh
set -euo pipefail
DATE=$(date +%Y%m%d_%H%M%S)

mariadb-dump -u root --single-transaction --quick --routines --triggers jaraba | gzip > /tmp/db_${DATE}.sql.gz
rclone copy /tmp/db_${DATE}.sql.gz jaraba-r2:jaraba-backups/database/
cp /tmp/db_${DATE}.sql.gz /opt/jaraba/backups/latest.sql.gz
rm /tmp/db_${DATE}.sql.gz

# Crontab: 0 */6 * * * /opt/jaraba/scripts/backup.sh
7.2 NAS 16TB via GoodSync
Job GoodSync: SFTP → IP:2222 (usuario jaraba) → /opt/jaraba/backups/ → NAS local. Diario, unidireccional.
7.3 RTO/RPO
Metrica	Objetivo	Estrategia
RPO	< 6 horas	Backup 6h + binlog
RTO	< 1 hora	Restore desde R2
 
8. Monitoring
Prometheus + Grafana + Loki instalados como servicios nativos o como containers Docker ligeros (sin impacto en la app principal). Todos los puertos solo en 127.0.0.1. Grafana accesible via Nginx reverse proxy con autenticacion.
 
9. DNS y Dominios
Dominio	Tipo	Proposito
plataformadeecosistemas.com	A → IP	Base SaaS (dominio principal)
*.plataformadeecosistemas.com	A → IP	Subdominios tenant (wildcard SSL)
plataformadeecosistemas.es	A → IP	Variante .es
*.plataformadeecosistemas.es	A → IP	Subdominios tenant .es
jarabaimpact.com	A → IP	Web institucional B2B
pepejaraba.com	A → IP	Marca personal
jaraba.es	A → IP	Dominio reservado
cdn.jarabaimpact.com	CNAME → R2	CDN archivos publicos (ano 2+)
SSL: Wildcard via certbot + Cloudflare DNS challenge para *.plataformadeecosistemas.*. Certs individuales para jarabaimpact.com, pepejaraba.com, jaraba.es via HTTP challenge.
 
10. Migracion desde Servidor Managed
# D-7: Configurar servidor nuevo completo con este runbook
# D-2: Reducir TTL DNS a 300s
# D-1: Test import DB + rsync files

# Dia D (ventana 30-60 min)
# 1. Modo mantenimiento servidor actual
# 2. Export FINAL DB
# 3. Import en nuevo: mariadb -u root -p jaraba < final_dump.sql
# 4. Rsync final: rsync -avz --delete old:/path/files/ /var/www/jaraba/web/sites/default/files/
# 5. drush cr && drush updb -y
# 6. Actualizar DNS A records → nueva IP
# 7. Verificar: curl -I https://plataformadeecosistemas.com
# 8. Desactivar mantenimiento
 
11. Validacion Post-Implementacion
#	Verificacion	Esperado
01	lscpu | grep 'Model name'	EPYC 4465P
02	free -h	~125Gi
03	cat /proc/mdstat	[UU]
04	mariadb -u drupal -p -e 'SELECT 1'	OK
05	redis-cli -a ... ping	PONG
06	curl https://<qdrant-cloud>:6333/healthz	OK (externo)
07	php -v	PHP 8.4.x
08	nginx -t	syntax ok
09	systemctl status php8.4-fpm	active (running)
10	curl -I https://plataformadeecosistemas.com	HTTP/2 200
11	SSL wildcard: curl https://test.plataformadeecosistemas.com	Cert valid
12	OPcache: php -i | grep opcache.enable	On
13	Redis cache Drupal: drush ev ...	cache.backend.redis
14	Backup script: /opt/jaraba/scripts/backup.sh	Exit 0 + archivo en R2
15	GoodSync pull via SFTP	OK
16	ufw status	Solo 2222/80/443
17	Supervisor: supervisorctl status	5 workers RUNNING
18	Tika: curl http://127.0.0.1:9998/tika	200
19	Rate limiting: ab -n 200 -c 50 /api/v1/	429 tras burst
 
12. Comandos de Operacion Diaria
Operacion	Comando
Deploy completo	/opt/jaraba/scripts/deploy.sh
Rebuild cache	drush cr
Login admin	drush uli
Import config	drush cim -y
Ver watchdog	drush ws --count=50
Tamano DB	mariadb -u root -p -e "SELECT ROUND(SUM(data_length+index_length)/1024/1024,2) AS 'MB' FROM information_schema.tables WHERE table_schema='jaraba';"
Backup manual	mariadb-dump -u root --single-transaction jaraba | gzip > manual.sql.gz
Modo mantenimiento	drush state:set system.maintenance_mode 1
Reload PHP-FPM	systemctl reload php8.4-fpm
Estado Supervisor	supervisorctl status
Logs Nginx	tail -f /var/log/nginx/access.log
Slow queries	tail -f /var/log/mysql/slow.log
Escalar buffer pool	# Editar /etc/mysql/mariadb.conf.d/99-jaraba.cnf → systemctl restart mariadb
 
13. Problemas Resueltos del Analisis Claude Code
El analisis de Claude Code (11-03-2026) identifico 10 problemas. Estado de resolucion en esta v3:
#	Problema	Resolucion en v3
1	Nginx vs Traefik	Nginx nativo (configs del codebase)
2	Docker vs Nativo	Stack nativo (Nginx+PHP-FPM+MariaDB+Redis nativos)
3	PHP-FPM 40 workers	Corregido a 24 (12c/24t x 1.5, ajustado)
4	Buffer pool 40GB ano 1	Escalonado: 16GB→24GB→40GB
5	Dominio principal	Corregido a plataformadeecosistemas.com
6	Falta jaraba.es	Anadido en DNS y trusted_host_patterns
7	OPcache invalidation	systemctl reload php8.4-fpm en deploy script
8	Supervisor workers	Nativo con supervisor conf documentada
9	SendGrid vs SMTP	SMTP IONOS ano 1, SendGrid API desde ano 2
10	Wildcard SSL	DNS challenge via certbot + Cloudflare API
 
14. Conclusion
Este runbook v3 resuelve las 10 observaciones del analisis de Claude Code y proporciona un plan de implementacion coherente con el codebase real (92 modulos, 441 entidades, stack nativo con Nginx + PHP-FPM unix socket).

Stack final: Ubuntu 24.04 + Nginx + PHP-FPM 8.4 (unix socket, 24 workers) + MariaDB 10.11 (buffer pool escalonado 16→40GB) + Redis 7.4 + Supervisor (5 AI workers) + Tika (unico Docker container) + Certbot (wildcard SSL via Cloudflare DNS).

Servicios externos: Qdrant Cloud + Cloudflare R2/CDN + SendGrid API (desde ano 2) + Claude API + Make.com (desde ano 3).

Coste ano 1: ~190 EUR/mes. Menos que el servidor managed actual, con un stack completo production-ready para 3 verticales y 500 usuarios.

--- Fin del Documento ---
