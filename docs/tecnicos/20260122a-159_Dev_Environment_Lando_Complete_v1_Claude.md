
ENTORNO DE DESARROLLO LOCAL
Lando + Docker + WSL2
Stack Completo: Redis, Qdrant, Drupal 11, MariaDB

JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Campo	Valor
Versión	1.0
Fecha	Enero 2026
Estado	Ready for Implementation
Código	159_Dev_Environment_Lando_Complete
Prioridad	CRÍTICO - Prerequisito de desarrollo
Destinatario	EDI Google Antigravity
 
Tabla de Contenidos
1. Resumen Ejecutivo
2. Requisitos del Sistema
3. Arquitectura del Entorno de Desarrollo
4. Archivo .lando.yml Completo
5. Configuración de Variables de Entorno
6. Configuración Drupal para Redis
7. Scripts de Inicialización
8. Comandos Tooling Disponibles
9. Flujo de Trabajo: Local → Staging → Producción
10. Troubleshooting y Diagnóstico
11. Checklist de Verificación
Anexo A: Configuración Completa de Servicios
Anexo B: Mapeo de Puertos
Anexo C: Paridad con Producción IONOS
 
1. Resumen Ejecutivo
Este documento especifica la configuración completa del entorno de desarrollo local para el Ecosistema Jaraba. El stack incluye todos los servicios necesarios para replicar el entorno de producción IONOS, permitiendo desarrollo y testing con paridad total.
1.1 Stack Tecnológico
Componente	Tecnología	Versión
Contenedorización	Docker Desktop + WSL2	latest
Orquestador Local	Lando	3.21+
CMS	Drupal	11.x
PHP	PHP-FPM	8.3
Web Server	NGINX	1.25+
Base de Datos	MariaDB	10.6
Cache	Redis	7.x Alpine
Vector Database	Qdrant	1.7.4
Document Processing	Apache Tika	2.9.1
Search (opcional)	Meilisearch	1.6
Mail Testing	MailHog	latest
Admin DB	phpMyAdmin	5.x
1.2 Propósito de Redis en el Stack IA
Redis cumple funciones críticas para el rendimiento del sistema de IA:
•	Cache de embeddings frecuentes: Reduce llamadas a OpenAI API en 60-70%
•	Cache de recomendaciones: TTL 3600s para resultados del Matching Engine
•	Session storage: Estado de conversaciones con AI Copilot
•	Queue backend: Procesamiento asíncrono de documentos para RAG
•	Rate limiting: Control de peticiones por tenant (100 req/min)

IMPACTO EN COSTES: Sin Redis, cada query de IA regeneraría embeddings constantemente. Con Redis configurado correctamente, el consumo de API de Claude/OpenAI se reduce significativamente.
 
2. Requisitos del Sistema
2.1 Hardware Mínimo Recomendado
Recurso	Mínimo	Recomendado
CPU	4 cores	8+ cores
RAM	16 GB	32 GB
Disco	50 GB SSD	100 GB NVMe
SO	Windows 10/11 Pro	Windows 11 Pro
2.2 Software Prerequisito
Software	Versión	Notas
WSL2	Ubuntu 22.04+	wsl --install -d Ubuntu
Docker Desktop	4.25+	Con integración WSL2 habilitada
Lando	3.21+	Instalar dentro de WSL2
Git	2.40+	Para control de versiones
Composer	2.6+	Gestión de dependencias PHP
Node.js	20 LTS	Para tooling frontend
2.3 Configuración WSL2
Crear archivo .wslconfig en %USERPROFILE%:
# %USERPROFILE%\.wslconfig
[wsl2]
memory=16GB
processors=8
swap=4GB
localhostForwarding=true
 
[experimental]
sparseVhd=true
autoMemoryReclaim=gradual
 
3. Arquitectura del Entorno de Desarrollo
3.1 Diagrama de Contenedores
┌─────────────────────────────────────────────────────────────────────────┐
│  WINDOWS 11 + WSL2 (Ubuntu 22.04)                                       │
│  ┌───────────────────────────────────────────────────────────────────┐  │
│  │  LANDO (Docker Compose Orchestration)                             │  │
│  │                                                                   │  │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐               │  │
│  │  │   NGINX     │  │   PHP-FPM   │  │   MariaDB   │               │  │
│  │  │   :80/:443  │──│    8.3      │──│    10.6     │               │  │
│  │  │   (proxy)   │  │  (Drupal)   │  │   :3306     │               │  │
│  │  └─────────────┘  └──────┬──────┘  └─────────────┘               │  │
│  │                          │                                        │  │
│  │         ┌────────────────┼────────────────┐                      │  │
│  │         │                │                │                      │  │
│  │  ┌──────▼──────┐  ┌──────▼──────┐  ┌──────▼──────┐              │  │
│  │  │    Redis    │  │   Qdrant    │  │    Tika     │              │  │
│  │  │   7-alpine  │  │   1.7.4     │  │   2.9.1     │              │  │
│  │  │    :6379    │  │ :6333/:6334 │  │    :9998    │              │  │
│  │  │   (cache)   │  │  (vectors)  │  │   (docs)    │              │  │
│  │  └─────────────┘  └─────────────┘  └─────────────┘              │  │
│  │                                                                   │  │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐               │  │
│  │  │  MailHog    │  │ phpMyAdmin  │  │ Meilisearch │               │  │
│  │  │   :8025     │  │   :8080     │  │    :7700    │               │  │
│  │  │   (mail)    │  │  (db admin) │  │  (search)   │               │  │
│  │  └─────────────┘  └─────────────┘  └─────────────┘               │  │
│  │                                                                   │  │
│  └───────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│  Volúmenes persistentes: db-data, redis-data, qdrant-data, files       │
└─────────────────────────────────────────────────────────────────────────┘
3.2 Red Interna Docker
Todos los servicios se comunican a través de la red interna de Docker. Los nombres de servicio actúan como DNS:
Servicio	Hostname interno	Puerto interno
Drupal/PHP	appserver	80
MariaDB	database	3306
Redis	redis	6379
Qdrant	qdrant	6333, 6334
Tika	tika	9998
MailHog	mailhog	1025 (SMTP), 8025 (UI)
Meilisearch	meilisearch	7700
 
4. Archivo .lando.yml Completo
ARCHIVO CRÍTICO: Este es el archivo principal de configuración. Debe colocarse en la raíz del proyecto Drupal.

# ═══════════════════════════════════════════════════════════════════════════
# .lando.yml - JARABA IMPACT PLATFORM
# Entorno de Desarrollo Local Completo con Stack IA
# Versión: 1.0 | Enero 2026
# ═══════════════════════════════════════════════════════════════════════════
 
name: jaraba
recipe: drupal11
 
config:
  php: '8.3'
  via: nginx
  webroot: web
  database: mariadb:10.6
  xdebug: true
  config:
    php: .lando/php.ini
 
# ═══════════════════════════════════════════════════════════════════════════
# SERVICIOS
# ═══════════════════════════════════════════════════════════════════════════
 
services:
  # ─────────────────────────────────────────────────────────────────────────
  # APPSERVER (Drupal + PHP)
  # ─────────────────────────────────────────────────────────────────────────
  appserver:
    type: php:8.3
    via: nginx
    webroot: web
    xdebug: true
    build_as_root:
      - apt-get update -y
      - apt-get install -y libmagickwand-dev ghostscript
      - pecl install imagick redis
      - docker-php-ext-enable imagick redis
    overrides:
      environment:
        # Drupal
        DRUPAL_ENV: development
        DRUPAL_HASH_SALT: ${DRUPAL_HASH_SALT:-dev_hash_salt_change_in_production}
        # Redis
        REDIS_HOST: redis
        REDIS_PORT: '6379'
        # Qdrant
        QDRANT_HOST: qdrant
        QDRANT_PORT: '6333'
        QDRANT_API_KEY: ${QDRANT_DEV_API_KEY:-dev_key_12345}
        # OpenAI/Claude
        OPENAI_API_KEY: ${OPENAI_API_KEY}
        ANTHROPIC_API_KEY: ${ANTHROPIC_API_KEY}
        # Tika
        TIKA_HOST: tika
        TIKA_PORT: '9998'
        # PHP
        PHP_MEMORY_LIMIT: 512M
        PHP_MAX_EXECUTION_TIME: '120'
        XDEBUG_MODE: debug,develop,coverage
 
  # ─────────────────────────────────────────────────────────────────────────
  # DATABASE (MariaDB)
  # ─────────────────────────────────────────────────────────────────────────
  database:
    type: mariadb:10.6
    portforward: 3307
    creds:
      user: drupal
      password: drupal
      database: drupal
    config:
      database: .lando/my.cnf
 
  # ─────────────────────────────────────────────────────────────────────────
  # REDIS - Cache para IA
  # ─────────────────────────────────────────────────────────────────────────
  redis:
    type: redis:7
    portforward: 6379
    config:
      server: .lando/redis.conf
 
  # ─────────────────────────────────────────────────────────────────────────
  # QDRANT - Base de Datos Vectorial
  # ─────────────────────────────────────────────────────────────────────────
  qdrant:
    type: compose
    services:
      image: qdrant/qdrant:v1.7.4
      command: ./qdrant
      ports:
        - '127.0.0.1:6333:6333'
        - '127.0.0.1:6334:6334'
      volumes:
        - qdrant_data:/qdrant/storage
      environment:
        - QDRANT__LOG_LEVEL=INFO
        - QDRANT__SERVICE__API_KEY=${QDRANT_DEV_API_KEY:-dev_key_12345}
      deploy:
        resources:
          limits:
            memory: 2G
          reservations:
            memory: 512M
 
  # ─────────────────────────────────────────────────────────────────────────
  # TIKA - Procesamiento de Documentos
  # ─────────────────────────────────────────────────────────────────────────
  tika:
    type: compose
    services:
      image: apache/tika:2.9.1
      ports:
        - '127.0.0.1:9998:9998'
 
  # ─────────────────────────────────────────────────────────────────────────
  # MEILISEARCH - Búsqueda Full-Text (opcional)
  # ─────────────────────────────────────────────────────────────────────────
  meilisearch:
    type: compose
    services:
      image: getmeili/meilisearch:v1.6
      ports:
        - '127.0.0.1:7700:7700'
      volumes:
        - meili_data:/meili_data
      environment:
        - MEILI_ENV=development
        - MEILI_MASTER_KEY=${MEILI_MASTER_KEY:-dev_master_key_12345}
 
  # ─────────────────────────────────────────────────────────────────────────
  # MAILHOG - Testing de Email
  # ─────────────────────────────────────────────────────────────────────────
  mailhog:
    type: mailhog
    portforward: 8025
    hogfrom:
      - appserver
 
  # ─────────────────────────────────────────────────────────────────────────
  # PHPMYADMIN - Administración BD
  # ─────────────────────────────────────────────────────────────────────────
  phpmyadmin:
    type: phpmyadmin
    hosts:
      - database
 
# ═══════════════════════════════════════════════════════════════════════════
# VOLÚMENES PERSISTENTES
# ═══════════════════════════════════════════════════════════════════════════
 
volumes:
  qdrant_data:
  meili_data:
 
# ═══════════════════════════════════════════════════════════════════════════
# TOOLING - Comandos personalizados
# ═══════════════════════════════════════════════════════════════════════════
 
tooling:
  # Drush
  drush:
    service: appserver
    cmd: drush --root=/app/web
 
  # Composer
  composer:
    service: appserver
    cmd: composer
 
  # Node/NPM
  node:
    service: appserver
    cmd: node
 
  npm:
    service: appserver
    cmd: npm
 
  # Redis CLI
  redis-cli:
    service: redis
    cmd: redis-cli
    description: Acceso directo a Redis CLI
 
  # Redis Status
  redis-status:
    service: appserver
    cmd: |
      echo "=== Redis Connection Test ==="
      php -r "
        \$redis = new Redis();
        \$redis->connect('redis', 6379);
        echo 'PING: ' . \$redis->ping() . PHP_EOL;
        echo 'INFO Memory: ' . PHP_EOL;
        print_r(\$redis->info('memory'));
      "
    description: Verificar estado de Redis
 
  # Redis Flush (desarrollo)
  redis-flush:
    service: redis
    cmd: redis-cli FLUSHALL
    description: Limpiar toda la cache Redis (solo desarrollo)
 
  # Qdrant Status
  qdrant-status:
    service: appserver
    cmd: |
      curl -s -H "api-key: ${QDRANT_DEV_API_KEY:-dev_key_12345}" \
        http://qdrant:6333/collections | jq
    description: Ver colecciones de Qdrant
 
  # Qdrant Health
  qdrant-health:
    service: appserver
    cmd: curl -s http://qdrant:6333/healthz
    description: Health check de Qdrant
 
  # Tika Test
  tika-test:
    service: appserver
    cmd: |
      echo "Testing Tika connection..."
      curl -s http://tika:9998/tika | head -20
    description: Verificar Apache Tika
 
  # Xdebug Toggle
  xdebug-on:
    service: appserver
    cmd: docker-php-ext-enable xdebug && kill -USR2 1
    user: root
    description: Habilitar Xdebug
 
  xdebug-off:
    service: appserver
    cmd: rm /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && kill -USR2 1
    user: root
    description: Deshabilitar Xdebug
 
  # Import/Export Config
  config-export:
    service: appserver
    cmd: drush --root=/app/web cex -y
    description: Exportar configuración Drupal
 
  config-import:
    service: appserver
    cmd: drush --root=/app/web cim -y
    description: Importar configuración Drupal
 
  # Cache Clear
  cr:
    service: appserver
    cmd: drush --root=/app/web cr
    description: Limpiar cache Drupal
 
  # Database dump
  db-export:
    service: appserver
    cmd: drush --root=/app/web sql-dump --gzip > /app/backups/db-$(date +%Y%m%d-%H%M%S).sql.gz
    description: Exportar base de datos
 
  # AI Stack Health Check
  ai-health:
    service: appserver
    cmd: |
      echo "═══════════════════════════════════════"
      echo "       AI STACK HEALTH CHECK"
      echo "═══════════════════════════════════════"
      echo ""
      echo "1. Redis..."
      php -r "\$r = new Redis(); \$r->connect('redis', 6379); echo \$r->ping() . PHP_EOL;"
      echo ""
      echo "2. Qdrant..."
      curl -s http://qdrant:6333/healthz && echo " OK" || echo " FAILED"
      echo ""
      echo "3. Tika..."
      curl -s -o /dev/null -w "%{http_code}" http://tika:9998/tika && echo " OK" || echo " FAILED"
      echo ""
      echo "4. Meilisearch..."
      curl -s http://meilisearch:7700/health | jq -r '.status' 2>/dev/null || echo "FAILED"
      echo ""
      echo "═══════════════════════════════════════"
    description: Verificar todo el stack de IA
 
# ═══════════════════════════════════════════════════════════════════════════
# PROXY (URLs locales)
# ═══════════════════════════════════════════════════════════════════════════
 
proxy:
  appserver:
    - jaraba.lndo.site
    - '*.jaraba.lndo.site'
  mailhog:
    - mail.jaraba.lndo.site
  phpmyadmin:
    - pma.jaraba.lndo.site
  meilisearch:
    - search.jaraba.lndo.site:7700
 
# ═══════════════════════════════════════════════════════════════════════════
# EVENTOS
# ═══════════════════════════════════════════════════════════════════════════
 
events:
  post-start:
    - appserver: |
        echo "═══════════════════════════════════════════════════════════"
        echo "  JARABA IMPACT PLATFORM - Entorno de desarrollo listo"
        echo "═══════════════════════════════════════════════════════════"
        echo ""
        echo "  URLs disponibles:"
        echo "    - Drupal:      https://jaraba.lndo.site"
        echo "    - MailHog:     https://mail.jaraba.lndo.site"
        echo "    - phpMyAdmin:  https://pma.jaraba.lndo.site"
        echo "    - Meilisearch: http://localhost:7700"
        echo "    - Qdrant:      http://localhost:6333/dashboard"
        echo ""
        echo "  Comandos útiles:"
        echo "    lando ai-health     - Verificar stack IA"
        echo "    lando redis-status  - Estado de Redis"
        echo "    lando qdrant-status - Colecciones Qdrant"
        echo ""
        echo "═══════════════════════════════════════════════════════════"
 
5. Configuración de Variables de Entorno
5.1 Archivo .env (raíz del proyecto)
SEGURIDAD: Este archivo NUNCA debe incluirse en Git. Añadir a .gitignore inmediatamente.

# ═══════════════════════════════════════════════════════════════════════════
# .env - Variables de entorno para desarrollo local
# NUNCA COMMITEAR ESTE ARCHIVO
# ═══════════════════════════════════════════════════════════════════════════
 
# ─────────────────────────────────────────────────────────────────────────
# DRUPAL
# ─────────────────────────────────────────────────────────────────────────
DRUPAL_ENV=development
DRUPAL_HASH_SALT=generate-random-64-char-string-here-for-security
 
# ─────────────────────────────────────────────────────────────────────────
# REDIS
# ─────────────────────────────────────────────────────────────────────────
REDIS_HOST=redis
REDIS_PORT=6379
# Sin password en desarrollo local
 
# ─────────────────────────────────────────────────────────────────────────
# QDRANT (Base de datos vectorial)
# ─────────────────────────────────────────────────────────────────────────
QDRANT_HOST=qdrant
QDRANT_PORT=6333
QDRANT_DEV_API_KEY=dev_local_key_change_me_12345
 
# ─────────────────────────────────────────────────────────────────────────
# OPENAI (Embeddings)
# Obtener en: https://platform.openai.com/api-keys
# ─────────────────────────────────────────────────────────────────────────
OPENAI_API_KEY=sk-proj-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
 
# ─────────────────────────────────────────────────────────────────────────
# ANTHROPIC (Claude API)
# Obtener en: https://console.anthropic.com/
# ─────────────────────────────────────────────────────────────────────────
ANTHROPIC_API_KEY=sk-ant-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
 
# ─────────────────────────────────────────────────────────────────────────
# MEILISEARCH
# ─────────────────────────────────────────────────────────────────────────
MEILI_MASTER_KEY=dev_master_key_12345
 
# ─────────────────────────────────────────────────────────────────────────
# TIKA
# ─────────────────────────────────────────────────────────────────────────
TIKA_HOST=tika
TIKA_PORT=9998
5.2 Archivo .env.example (para Git)
Crear un archivo .env.example con placeholders para documentar las variables requeridas:
# .env.example - Template de variables de entorno
# Copiar a .env y rellenar con valores reales
 
DRUPAL_ENV=development
DRUPAL_HASH_SALT=your-64-char-hash-here
 
REDIS_HOST=redis
REDIS_PORT=6379
 
QDRANT_HOST=qdrant
QDRANT_PORT=6333
QDRANT_DEV_API_KEY=your-dev-api-key
 
OPENAI_API_KEY=sk-proj-your-key-here
ANTHROPIC_API_KEY=sk-ant-your-key-here
 
MEILI_MASTER_KEY=your-master-key
5.3 Archivo .gitignore
# .gitignore - Archivos a excluir del repositorio
 
# Variables de entorno (CRÍTICO)
.env
.env.*
!.env.example
 
# Configuración local Drupal
web/sites/*/settings.local.php
web/sites/*/services.local.yml
 
# Lando local overrides
.lando.local.yml
 
# IDE
.idea/
.vscode/
*.code-workspace
 
# Logs
*.log
logs/
 
# Backups locales
backups/*.sql
backups/*.gz
 
# Archivos de usuario
web/sites/*/files/
private/
 
# Node
node_modules/
 
# Composer vendor (opcional - se regenera)
# vendor/
 
6. Configuración Drupal para Redis
6.1 Instalación del Módulo Redis
# Instalar módulo y extensión PHP
lando composer require drupal/redis
lando drush en redis -y
 
# Verificar que la extensión PHP está activa
lando php -m | grep redis
6.2 Archivo settings.local.php
<?php
// web/sites/default/settings.local.php
// Configuración de desarrollo local
 
/**
 * Detección de entorno Lando
 */
$is_lando = getenv('LANDO') === 'ON';
 
if ($is_lando) {
  
  // ═══════════════════════════════════════════════════════════════════════
  // REDIS CONFIGURATION
  // ═══════════════════════════════════════════════════════════════════════
  
  $settings['redis.connection']['interface'] = 'PhpRedis';
  $settings['redis.connection']['host'] = getenv('REDIS_HOST') ?: 'redis';
  $settings['redis.connection']['port'] = getenv('REDIS_PORT') ?: 6379;
  
  // Cache backend principal
  $settings['cache']['default'] = 'cache.backend.redis';
  
  // Cache bins específicos para IA
  $settings['cache']['bins']['ai_embeddings'] = 'cache.backend.redis';
  $settings['cache']['bins']['ai_recommendations'] = 'cache.backend.redis';
  $settings['cache']['bins']['ai_tenant_knowledge'] = 'cache.backend.redis';
  $settings['cache']['bins']['matching_results'] = 'cache.backend.redis';
  
  // Prefijo para evitar colisiones
  $settings['cache_prefix'] = 'jaraba_dev_';
  
  // ═══════════════════════════════════════════════════════════════════════
  // QDRANT CONFIGURATION
  // ═══════════════════════════════════════════════════════════════════════
  
  $config['jaraba_rag.settings']['qdrant_cluster_url'] = 
    'http://' . (getenv('QDRANT_HOST') ?: 'qdrant') . ':' . (getenv('QDRANT_PORT') ?: '6333');
  $config['jaraba_rag.settings']['qdrant_api_key'] = 
    getenv('QDRANT_DEV_API_KEY') ?: 'dev_key_12345';
  
  // ═══════════════════════════════════════════════════════════════════════
  // OPENAI / ANTHROPIC CONFIGURATION
  // ═══════════════════════════════════════════════════════════════════════
  
  $config['jaraba_ai.settings']['openai_api_key'] = getenv('OPENAI_API_KEY');
  $config['jaraba_ai.settings']['anthropic_api_key'] = getenv('ANTHROPIC_API_KEY');
  $config['jaraba_ai.settings']['embedding_model'] = 'text-embedding-3-small';
  $config['jaraba_ai.settings']['llm_model'] = 'claude-sonnet-4-5-20250929';
  
  // ═══════════════════════════════════════════════════════════════════════
  // TIKA CONFIGURATION
  // ═══════════════════════════════════════════════════════════════════════
  
  $config['jaraba_rag.settings']['tika_url'] = 
    'http://' . (getenv('TIKA_HOST') ?: 'tika') . ':' . (getenv('TIKA_PORT') ?: '9998');
  
  // ═══════════════════════════════════════════════════════════════════════
  // DEVELOPMENT SETTINGS
  // ═══════════════════════════════════════════════════════════════════════
  
  // Deshabilitar cache de render en desarrollo
  $settings['cache']['bins']['render'] = 'cache.backend.null';
  $settings['cache']['bins']['page'] = 'cache.backend.null';
  $settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';
  
  // Mostrar errores
  $config['system.logging']['error_level'] = 'verbose';
  
  // Deshabilitar CSS/JS aggregation
  $config['system.performance']['css']['preprocess'] = FALSE;
  $config['system.performance']['js']['preprocess'] = FALSE;
  
  // Trusted host patterns para Lando
  $settings['trusted_host_patterns'] = [
    '^jaraba\.lndo\.site$',
    '^.+\.jaraba\.lndo\.site$',
    '^localhost$',
  ];
  
  // File paths
  $settings['file_private_path'] = '/app/private';
  $settings['file_temp_path'] = '/tmp';
  
}
 
7. Scripts de Inicialización
7.1 Directorio .lando/ (configuraciones)
Crear directorio .lando/ en la raíz del proyecto con los siguientes archivos:
7.1.1 php.ini
; .lando/php.ini
; Configuración PHP optimizada para desarrollo
 
[PHP]
memory_limit = 512M
max_execution_time = 120
max_input_time = 120
post_max_size = 100M
upload_max_filesize = 100M
max_file_uploads = 50
 
; OPcache (deshabilitado para desarrollo)
opcache.enable = 0
opcache.enable_cli = 0
 
; Error reporting
display_errors = On
display_startup_errors = On
error_reporting = E_ALL
log_errors = On
 
; Xdebug
[xdebug]
xdebug.mode = debug,develop,coverage
xdebug.start_with_request = yes
xdebug.client_host = host.docker.internal
xdebug.client_port = 9003
xdebug.log = /tmp/xdebug.log
xdebug.idekey = PHPSTORM
7.1.2 my.cnf (MariaDB)
# .lando/my.cnf
# Configuración MariaDB para desarrollo
 
[mysqld]
# Performance
innodb_buffer_pool_size = 256M
innodb_log_file_size = 64M
innodb_flush_log_at_trx_commit = 2
 
# Connections
max_connections = 100
max_allowed_packet = 64M
wait_timeout = 300
 
# Slow query log (útil para debugging)
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
 
# Character set
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci
7.1.3 redis.conf
# .lando/redis.conf
# Configuración Redis para desarrollo
 
# Memoria máxima (ajustar según RAM disponible)
maxmemory 1gb
 
# Política de evicción (LRU para cache)
maxmemory-policy allkeys-lru
 
# Persistencia (opcional en desarrollo)
save 900 1
save 300 10
save 60 10000
 
# Logging
loglevel notice
 
# Timeout
timeout 0
 
# TCP keepalive
tcp-keepalive 300
7.2 Script de Setup Inicial
#!/bin/bash
# scripts/setup-dev.sh
# Script de configuración inicial del entorno de desarrollo
 
set -e
 
echo "═══════════════════════════════════════════════════════════════"
echo "  JARABA IMPACT PLATFORM - Setup de Desarrollo"
echo "═══════════════════════════════════════════════════════════════"
 
# Verificar que estamos en el directorio correcto
if [ ! -f ".lando.yml" ]; then
    echo "ERROR: Ejecutar desde la raíz del proyecto (donde está .lando.yml)"
    exit 1
fi
 
# Crear directorios necesarios
echo "→ Creando directorios..."
mkdir -p .lando
mkdir -p backups
mkdir -p private
mkdir -p web/sites/default/files
 
# Copiar .env si no existe
if [ ! -f ".env" ]; then
    echo "→ Creando archivo .env desde template..."
    cp .env.example .env
    echo "  ⚠️  IMPORTANTE: Editar .env con las API keys reales"
fi
 
# Permisos
echo "→ Configurando permisos..."
chmod 755 web/sites/default
chmod 755 web/sites/default/files
chmod 644 web/sites/default/settings.php 2>/dev/null || true
 
# Iniciar Lando
echo "→ Iniciando Lando..."
lando start
 
# Instalar dependencias
echo "→ Instalando dependencias Composer..."
lando composer install
 
# Verificar servicios
echo "→ Verificando servicios..."
lando ai-health
 
# Instalar Drupal si es necesario
if [ ! -f "web/sites/default/settings.local.php" ]; then
    echo "→ Drupal no instalado. Ejecutar:"
    echo "  lando drush site:install --db-url=mysql://drupal:drupal@database/drupal"
fi
 
echo ""
echo "═══════════════════════════════════════════════════════════════"
echo "  ✓ Setup completado"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "  Próximos pasos:"
echo "  1. Editar .env con las API keys de OpenAI y Anthropic"
echo "  2. Ejecutar: lando drush site:install (si es instalación nueva)"
echo "  3. Importar config: lando config-import"
echo "  4. Acceder a: https://jaraba.lndo.site"
echo ""
 
8. Comandos Tooling Disponibles
Resumen de todos los comandos disponibles con lando <comando>:
Comando	Descripción	Uso típico
drush	Drush CLI	lando drush cr
composer	Composer PHP	lando composer require drupal/redis
npm	Node Package Manager	lando npm install
redis-cli	Cliente Redis directo	lando redis-cli INFO
redis-status	Estado de Redis	lando redis-status
redis-flush	Limpiar cache Redis	lando redis-flush
qdrant-status	Ver colecciones Qdrant	lando qdrant-status
qdrant-health	Health check Qdrant	lando qdrant-health
tika-test	Verificar Apache Tika	lando tika-test
ai-health	Health check completo IA	lando ai-health
xdebug-on	Habilitar Xdebug	lando xdebug-on
xdebug-off	Deshabilitar Xdebug	lando xdebug-off
config-export	Exportar config Drupal	lando config-export
config-import	Importar config Drupal	lando config-import
cr	Cache rebuild Drupal	lando cr
db-export	Exportar base de datos	lando db-export
 
9. Flujo de Trabajo: Local → Staging → Producción
9.1 Workflow Diario de Desarrollo
# 1. Iniciar entorno
cd /path/to/jaraba
lando start
 
# 2. Verificar que todo funciona
lando ai-health
 
# 3. Pull cambios del repo
git pull origin develop
 
# 4. Actualizar dependencias
lando composer install
 
# 5. Importar configuración
lando config-import
 
# 6. Limpiar cache
lando cr
 
# 7. Desarrollar...
 
# 8. Exportar configuración antes de commit
lando config-export
 
# 9. Commit y push
git add .
git commit -m "feat: nueva funcionalidad"
git push origin feature/mi-feature
9.2 Despliegue a Staging (IONOS)
# Desde el entorno local
 
# 1. Exportar base de datos
lando db-export
 
# 2. Subir código (excluyendo archivos locales)
rsync -avz --exclude 'vendor/' --exclude 'node_modules/' \
  --exclude '.env' --exclude 'web/sites/*/files/' \
  --exclude '.lando*' --exclude 'backups/' \
  ./ dev_staging@ionos.server:/var/www/staging/jaraba/
 
# 3. En el servidor staging
ssh dev_staging@ionos.server
cd /var/www/staging/jaraba
composer install --no-dev
drush cim -y
drush cr
 
# 4. Importar BD si es necesario
gunzip < backups/latest.sql.gz | drush sql-cli
9.3 Promoción a Producción
# Solo después de validar en staging
 
# 1. Tag de versión
git tag -a v1.2.0 -m "Release 1.2.0"
git push origin v1.2.0
 
# 2. Despliegue a producción
ssh dev_prod@ionos.server
cd /var/www/jaraba
git fetch --tags
git checkout v1.2.0
composer install --no-dev --optimize-autoloader
drush cim -y
drush cr
drush updb -y
 
# 3. Verificar
curl -I https://plataformadeecosistemas.es
 
10. Troubleshooting y Diagnóstico
10.1 Problemas Comunes
Problema	Causa probable	Solución
Redis no conecta	Servicio no iniciado	lando restart && lando redis-status
Qdrant timeout	Memoria insuficiente	Aumentar Docker memory en WSL
API key inválida	.env no cargado	Verificar .env y lando rebuild
Lando muy lento	Xdebug activo	lando xdebug-off
Errores de permisos	Archivos con UID incorrecto	lando ssh -c 'chown -R www-data:www-data web/sites/default/files'
Port conflict 3307	MySQL local activo	Detener MySQL local o cambiar puerto en .lando.yml
10.2 Comandos de Diagnóstico
# Ver logs de todos los servicios
lando logs
 
# Ver logs de un servicio específico
lando logs -s redis
lando logs -s qdrant
 
# Entrar al contenedor para debugging
lando ssh
lando ssh -s redis
 
# Ver estado de contenedores
lando info
 
# Reiniciar completamente
lando rebuild -y
 
# Ver uso de recursos Docker
docker stats
 
11. Checklist de Verificación
Usar esta lista antes de considerar el entorno listo para desarrollo:
11.1 Prerequisitos
•	WSL2 instalado con Ubuntu 22.04+
•	Docker Desktop con integración WSL2
•	Lando 3.21+ instalado
•	Archivo .wslconfig con memoria suficiente
11.2 Configuración
•	Archivo .lando.yml en raíz del proyecto
•	Archivo .env con API keys reales
•	Directorio .lando/ con php.ini, my.cnf, redis.conf
•	settings.local.php configurado
•	.gitignore actualizado
11.3 Servicios
•	lando ai-health pasa todos los checks
•	Redis responde a PING
•	Qdrant dashboard accesible en localhost:6333
•	Tika responde en puerto 9998
•	MailHog accesible en mail.jaraba.lndo.site
11.4 Drupal
•	Módulo Redis habilitado
•	drush status muestra Redis como cache backend
•	Configuración importada correctamente
•	Login de administrador funcional
 
Anexo A: Configuración Completa de Servicios
Servicio	Imagen	Versión	Memoria	Propósito
appserver	devwithlando/php	8.3-fpm	512MB	Drupal + PHP
database	mariadb	10.6	256MB	Base de datos relacional
redis	redis	7-alpine	1GB	Cache IA y sesiones
qdrant	qdrant/qdrant	1.7.4	2GB	Vectores/embeddings
tika	apache/tika	2.9.1	512MB	Extracción de texto
meilisearch	getmeili/meilisearch	1.6	256MB	Búsqueda full-text
mailhog	mailhog/mailhog	latest	64MB	Testing email
phpmyadmin	phpmyadmin	5.x	128MB	Admin BD
Anexo B: Mapeo de Puertos
Servicio	Puerto Interno	Puerto Host	Acceso
Drupal	80	dinamico	https://jaraba.lndo.site
MariaDB	3306	3307	localhost:3307
Redis	6379	6379	localhost:6379
Qdrant HTTP	6333	6333	http://localhost:6333
Qdrant gRPC	6334	6334	localhost:6334
Tika	9998	9998	http://localhost:9998
Meilisearch	7700	7700	http://localhost:7700
MailHog UI	8025	8025	https://mail.jaraba.lndo.site
phpMyAdmin	80	dinamico	https://pma.jaraba.lndo.site
Anexo C: Paridad con Producción IONOS
Comparativa de configuración entre desarrollo local y producción:
Aspecto	Desarrollo (Lando)	Producción (IONOS)
Redis memoria	1GB	8GB
Qdrant memoria	2GB	16GB
MariaDB buffer	256MB	24GB
PHP memory_limit	512MB	512MB
Xdebug	Habilitado	Deshabilitado
Cache render	Deshabilitado	Habilitado
SSL	Self-signed (Lando)	Let's Encrypt
Qdrant	Container local	Qdrant Cloud
Redis password	Sin password	Con password
Multi-instancia	1 Drupal	2-4 Drupal containers

PARIDAD DE CÓDIGO: El código PHP/Drupal es idéntico entre entornos. Solo difieren las variables de entorno y la configuración de infraestructura, gestionadas por settings.local.php y variables de entorno del sistema.
 
— Fin del Documento —
JARABA IMPACT PLATFORM
Documento Técnico para EDI Google Antigravity
Enero 2026 | Versión 1.0
