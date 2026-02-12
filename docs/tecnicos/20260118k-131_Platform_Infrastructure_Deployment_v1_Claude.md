INFRASTRUCTURE & DEPLOYMENT
Arquitectura de Producción en IONOS

Campo	Valor
Versión:	1.0
Fecha:	Enero 2026
Estado:	Ready for Implementation
Código:	131_Platform_Infrastructure_Deployment
Prioridad:	🔴 CRÍTICO
 
1. Resumen de Infraestructura
Arquitectura de producción optimizada para el ecosistema Jaraba en servidor dedicado IONOS con capacidad de escalar horizontalmente.
1.1 Servidor Principal
Componente	Especificación	Notas
Proveedor	IONOS	Dedicado L-16 NVMe
CPU	AMD EPYC 7702P 64-Core	128 threads
RAM	256 GB DDR4 ECC	Ampliable a 512 GB
Storage	2x 1.92TB NVMe SSD	RAID 1 para OS, RAID 0 para data
Red	1 Gbps garantizado	10 Gbps burst
OS	Ubuntu 24.04 LTS	Kernel 6.x
Ubicación	Frankfurt, DE	GDPR compliant

1.2 Arquitectura de Contenedores
┌─────────────────────────────────────────────────────────────────────────────┐
│                    IONOS DEDICATED SERVER                                   │
│                    (Ubuntu 24.04 + Docker)                                  │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                      TRAEFIK (Reverse Proxy)                        │   │
│  │                      :80, :443 (Let's Encrypt)                      │   │
│  └───────────┬─────────────────────────────────────┬───────────────────┘   │
│              │                                     │                        │
│   ┌──────────▼──────────┐           ┌──────────────▼──────────┐            │
│   │   DRUPAL CLUSTER    │           │      SERVICES           │            │
│   │                     │           │                         │            │
│   │  ┌───────────────┐  │           │  ┌─────────────────┐   │            │
│   │  │ Drupal Web 1  │  │           │  │ Apache Tika     │   │            │
│   │  │ (PHP-FPM 8.3) │  │           │  │ :9998           │   │            │
│   │  └───────────────┘  │           │  └─────────────────┘   │            │
│   │  ┌───────────────┐  │           │  ┌─────────────────┐   │            │
│   │  │ Drupal Web 2  │  │           │  │ Typesense       │   │            │
│   │  │ (PHP-FPM 8.3) │  │           │  │ :8108           │   │            │
│   │  └───────────────┘  │           │  └─────────────────┘   │            │
│   │  ┌───────────────┐  │           │  ┌─────────────────┐   │            │
│   │  │ Drupal Cron   │  │           │  │ Meilisearch     │   │            │
│   │  │ (Background)  │  │           │  │ :7700           │   │            │
│   │  └───────────────┘  │           │  └─────────────────┘   │            │
│   └─────────────────────┘           └─────────────────────────┘            │
│              │                                     │                        │
│   ┌──────────▼─────────────────────────────────────▼───────────────────┐   │
│   │                        DATA LAYER                                  │   │
│   │                                                                    │   │
│   │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌───────────┐ │   │
│   │  │  MariaDB    │  │   Redis     │  │  Qdrant     │  │ Minio S3  │ │   │
│   │  │  :3306      │  │   :6379     │  │  :6333      │  │ :9000     │ │   │
│   │  │  (Primary)  │  │  (Cache)    │  │  (Vectors)  │  │ (Files)   │ │   │
│   │  └─────────────┘  └─────────────┘  └─────────────┘  └───────────┘ │   │
│   │                                                                    │   │
│   └────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                      MONITORING STACK                               │   │
│  │  Prometheus :9090 | Grafana :3000 | Loki :3100 | AlertManager       │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
 
EXTERNAL SERVICES (Cloud):
• Qdrant Cloud (vectors) - Opcional, backup del local
• Stripe (payments)
• Claude API / Gemini (AI)
• ActiveCampaign (email)
• Cloudflare (CDN + WAF)
 
2. Docker Compose de Producción
2.1 docker-compose.prod.yml
version: '3.8'
 
services:
  # ═══════════════════════════════════════════════════════════════
  # REVERSE PROXY
  # ═══════════════════════════════════════════════════════════════
  traefik:
    image: traefik:v3.0
    container_name: traefik
    restart: always
    command:
      - "--api.dashboard=true"
      - "--providers.docker=true"
      - "--providers.docker.exposedbydefault=false"
      - "--entrypoints.web.address=:80"
      - "--entrypoints.websecure.address=:443"
      - "--certificatesresolvers.letsencrypt.acme.email=tech@jarabaimpact.com"
      - "--certificatesresolvers.letsencrypt.acme.storage=/letsencrypt/acme.json"
      - "--certificatesresolvers.letsencrypt.acme.httpchallenge.entrypoint=web"
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock:ro
      - traefik-letsencrypt:/letsencrypt
    networks:
      - jaraba-network
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.dashboard.rule=Host(`traefik.jarabaimpact.com`)"
      - "traefik.http.routers.dashboard.service=api@internal"
      - "traefik.http.routers.dashboard.middlewares=auth"
 
  # ═══════════════════════════════════════════════════════════════
  # DRUPAL APPLICATION
  # ═══════════════════════════════════════════════════════════════
  drupal:
    image: jaraba/drupal:11-prod
    container_name: drupal
    restart: always
    deploy:
      replicas: 2
      resources:
        limits:
          cpus: '8'
          memory: 16G
        reservations:
          cpus: '4'
          memory: 8G
    environment:
      - APP_ENV=production
      - DATABASE_URL=mysql://drupal:$DB_PASSWORD@mariadb:3306/jaraba
      - REDIS_URL=redis://redis:6379
      - QDRANT_URL=http://qdrant:6333
      - STRIPE_SECRET_KEY=$STRIPE_SECRET_KEY
      - CLAUDE_API_KEY=$CLAUDE_API_KEY
      - TRUSTED_HOST_PATTERNS=^.+\.jarabaimpact\.com$
    volumes:
      - drupal-files:/var/www/html/web/sites/default/files
      - drupal-private:/var/www/private
    depends_on:
      - mariadb
      - redis
    networks:
      - jaraba-network
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.drupal.rule=Host(`app.jarabaimpact.com`) || HostRegexp(`{subdomain:[a-z]+}.jarabaimpact.com`)"
      - "traefik.http.routers.drupal.entrypoints=websecure"
      - "traefik.http.routers.drupal.tls.certresolver=letsencrypt"
      - "traefik.http.services.drupal.loadbalancer.server.port=80"
 
  drupal-cron:
    image: jaraba/drupal:11-prod
    container_name: drupal-cron
    restart: always
    command: ["crond", "-f", "-d", "8"]
    environment:
      - APP_ENV=production
      - DATABASE_URL=mysql://drupal:$DB_PASSWORD@mariadb:3306/jaraba
    volumes:
      - drupal-files:/var/www/html/web/sites/default/files
    depends_on:
      - mariadb
    networks:
      - jaraba-network
 
  # ═══════════════════════════════════════════════════════════════
  # DATABASE
  # ═══════════════════════════════════════════════════════════════
  mariadb:
    image: mariadb:11.2
    container_name: mariadb
    restart: always
    environment:
      - MARIADB_ROOT_PASSWORD=$DB_ROOT_PASSWORD
      - MARIADB_DATABASE=jaraba
      - MARIADB_USER=drupal
      - MARIADB_PASSWORD=$DB_PASSWORD
    volumes:
      - mariadb-data:/var/lib/mysql
      - ./config/mariadb/my.cnf:/etc/mysql/conf.d/custom.cnf
    networks:
      - jaraba-network
    deploy:
      resources:
        limits:
          memory: 32G
 
  # ═══════════════════════════════════════════════════════════════
  # CACHE
  # ═══════════════════════════════════════════════════════════════
  redis:
    image: redis:7-alpine
    container_name: redis
    restart: always
    command: redis-server --maxmemory 8gb --maxmemory-policy allkeys-lru
    volumes:
      - redis-data:/data
    networks:
      - jaraba-network
 
  # ═══════════════════════════════════════════════════════════════
  # VECTOR DATABASE
  # ═══════════════════════════════════════════════════════════════
  qdrant:
    image: qdrant/qdrant:v1.7.4
    container_name: qdrant
    restart: always
    volumes:
      - qdrant-data:/qdrant/storage
    networks:
      - jaraba-network
    deploy:
      resources:
        limits:
          memory: 16G
 
  # ═══════════════════════════════════════════════════════════════
  # DOCUMENT PROCESSING
  # ═══════════════════════════════════════════════════════════════
  tika:
    image: apache/tika:2.9.1
    container_name: tika
    restart: always
    networks:
      - jaraba-network
 
  # ═══════════════════════════════════════════════════════════════
  # OBJECT STORAGE
  # ═══════════════════════════════════════════════════════════════
  minio:
    image: minio/minio:latest
    container_name: minio
    restart: always
    command: server /data --console-address ":9001"
    environment:
      - MINIO_ROOT_USER=$MINIO_ACCESS_KEY
      - MINIO_ROOT_PASSWORD=$MINIO_SECRET_KEY
    volumes:
      - minio-data:/data
    networks:
      - jaraba-network
 
volumes:
  traefik-letsencrypt:
  drupal-files:
  drupal-private:
  mariadb-data:
  redis-data:
  qdrant-data:
  minio-data:
 
networks:
  jaraba-network:
    driver: bridge
 
3. Configuraciones de Producción
3.1 MariaDB Optimizado (my.cnf)
[mysqld]
# Memory
innodb_buffer_pool_size = 24G
innodb_buffer_pool_instances = 24
innodb_log_file_size = 2G
innodb_log_buffer_size = 256M
 
# Performance
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
innodb_file_per_table = 1
innodb_io_capacity = 4000
innodb_io_capacity_max = 8000
 
# Connections
max_connections = 500
max_allowed_packet = 256M
wait_timeout = 300
interactive_timeout = 300
 
# Query cache (off for high-write)
query_cache_type = 0
query_cache_size = 0
 
# Slow query log
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2

3.2 PHP-FPM Optimizado
; /etc/php/8.3/fpm/pool.d/www.conf
 
[www]
pm = dynamic
pm.max_children = 100
pm.start_servers = 20
pm.min_spare_servers = 10
pm.max_spare_servers = 30
pm.max_requests = 1000
 
; Memory
php_admin_value[memory_limit] = 512M
php_admin_value[max_execution_time] = 120
php_admin_value[upload_max_filesize] = 100M
php_admin_value[post_max_size] = 100M
 
; OPcache
php_admin_value[opcache.enable] = 1
php_admin_value[opcache.memory_consumption] = 512
php_admin_value[opcache.max_accelerated_files] = 20000
php_admin_value[opcache.validate_timestamps] = 0
php_admin_value[opcache.jit] = 1255
php_admin_value[opcache.jit_buffer_size] = 256M
 
4. Backup & Disaster Recovery
4.1 Estrategia de Backups
Componente	Frecuencia	Retención	Destino
Database (MariaDB)	Cada 6 horas	30 días	IONOS Backup Space + S3
Files (Drupal)	Diario	14 días	S3 (Minio replica)
Qdrant Vectors	Diario	7 días	Qdrant Cloud snapshot
Full Server	Semanal	4 semanas	IONOS Backup
Config/Secrets	En cada cambio	Versionado	Git + Vault

4.2 Script de Backup
#!/bin/bash
# /opt/jaraba/scripts/backup.sh
 
set -e
 
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups"
S3_BUCKET="jaraba-backups"
 
# Database backup
echo "Backing up database..."
docker exec mariadb mysqldump -u root -p$DB_ROOT_PASSWORD \
  --single-transaction --quick --lock-tables=false jaraba \
  | gzip > $BACKUP_DIR/db_$DATE.sql.gz
 
# Upload to S3
aws s3 cp $BACKUP_DIR/db_$DATE.sql.gz s3://$S3_BUCKET/database/
 
# Files backup (incremental with rsync)
echo "Backing up files..."
rsync -avz --delete /var/lib/docker/volumes/drupal-files/_data/ \
  $BACKUP_DIR/files/
 
# Qdrant snapshot
echo "Creating Qdrant snapshot..."
curl -X POST "http://localhost:6333/collections/jaraba_knowledge/snapshots"
 
# Cleanup old backups (local)
find $BACKUP_DIR -type f -mtime +7 -delete
 
echo "Backup completed: $DATE"
4.3 RTO y RPO
Métrica	Objetivo	Estrategia
RPO (Recovery Point Objective)	< 6 horas	Backups cada 6h + WAL archiving
RTO (Recovery Time Objective)	< 1 hora	Restore automatizado + infra as code
Failover Database	< 5 minutos	MariaDB replica en standby
Failover Completo	< 30 minutos	IONOS Image + restore de datos
 
5. Seguridad
5.1 Hardening del Servidor
•	SSH: Solo key-based auth, puerto no estándar, fail2ban activo
•	Firewall (ufw): Solo puertos 80, 443, SSH custom abiertos
•	Updates: Unattended-upgrades para security patches
•	Docker: Rootless mode, seccomp profiles, no privileged containers
•	Secrets: Variables de entorno via Docker secrets, nunca en código
5.2 Cloudflare WAF
Regla	Acción	Propósito
OWASP Core Ruleset	Challenge/Block	Protección general
SQL Injection	Block	Prevenir inyección SQL
XSS	Block	Prevenir cross-site scripting
Rate Limiting	Challenge @ 100 req/10s	Anti-DDoS
Bot Management	Challenge	Filtrar bots maliciosos
Geo Blocking	Allow ES, PT, EU	Limitar geografía inicial
5.3 SSL/TLS
•	Certificados: Let's Encrypt via Traefik (auto-renovación)
•	Protocolo: TLS 1.3 only (1.2 para legacy si necesario)
•	HSTS: max-age=31536000; includeSubDomains; preload
•	Cipher suites: Solo AEAD (AES-GCM, ChaCha20-Poly1305)
 
6. Escalabilidad
6.1 Horizontal Scaling Path
Fase	Trigger	Acción	Estimación
Actual	Baseline	1 servidor, 2 Drupal containers	~10K usuarios
Fase 1	CPU > 70% sustained	Añadir 3er/4to Drupal container	~25K usuarios
Fase 2	DB bottleneck	MariaDB replica + read routing	~50K usuarios
Fase 3	Storage IOPS	Separar DB a servidor dedicado	~100K usuarios
Fase 4	Geographic expansion	Multi-region (IONOS Spain)	>100K usuarios
6.2 Auto-scaling con Docker Swarm
# Migrar a Swarm cuando sea necesario
 
# Inicializar Swarm
docker swarm init
 
# Deploy stack
docker stack deploy -c docker-compose.prod.yml jaraba
 
# Scale Drupal
docker service scale jaraba_drupal=4
 
# Auto-scaling con métricas (futuro)
# Integrar con Prometheus + custom scaler
 
7. Checklist de Implementación
7.1 Setup Inicial
•	[ ] Provisionar servidor IONOS L-16
•	[ ] Instalar Ubuntu 24.04 LTS
•	[ ] Configurar SSH hardening
•	[ ] Instalar Docker + Docker Compose
•	[ ] Configurar firewall (ufw)
•	[ ] Configurar Cloudflare (DNS + WAF)
•	[ ] Crear estructura de directorios
7.2 Deploy Inicial
•	[ ] Crear archivo .env con secrets
•	[ ] Build imagen Docker de Drupal
•	[ ] docker-compose up -d
•	[ ] Verificar certificados SSL
•	[ ] Importar base de datos
•	[ ] Sincronizar files
•	[ ] Verificar Drupal funciona
7.3 Monitoring
•	[ ] Deploy stack de monitoring (Prometheus + Grafana)
•	[ ] Configurar alertas críticas
•	[ ] Configurar log aggregation (Loki)
•	[ ] Test de alertas
7.4 Backup
•	[ ] Configurar cron de backups
•	[ ] Verificar upload a S3
•	[ ] Test de restore completo
•	[ ] Documentar procedimiento de DR

--- Fin del Documento ---
