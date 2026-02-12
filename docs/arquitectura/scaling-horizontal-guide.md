# Scaling Horizontal — Guia de 3 Fases (F10 Doc 187)

**Fecha:** 2026-02-12
**Version:** 1.0.0
**Plataforma:** Drupal 11 + MariaDB + Redis + Qdrant

---

## 1. Resumen de Fases

| Fase | Arquitectura | Tenants | Infra | Trigger |
|------|-------------|---------|-------|---------|
| **1** | Single Server | 1-50 | IONOS L-16 (16 cores, 128GB) | Estado actual |
| **2** | Separated DB | 50-200 | App Server + DB Server | >50 tenants o DB pool >80% |
| **3** | Load Balanced | 200-1000+ | N x App + DB Primary/Replica + Redis Cluster | >200 tenants o CPU >75% sostenido |

---

## 2. Fase 1 — Single Server (Estado Actual)

### 2.1 Arquitectura

```
┌─────────────────────────────────────────┐
│           IONOS L-16 NVMe               │
│  ┌──────────┐  ┌──────────┐  ┌───────┐ │
│  │  Drupal   │  │ MariaDB  │  │ Redis │ │
│  │  (PHP 8.4)│  │  (11.x)  │  │       │ │
│  └──────────┘  └──────────┘  └───────┘ │
│  ┌──────────┐  ┌──────────┐            │
│  │  Qdrant   │  │  Tika    │            │
│  │ (Vectors) │  │ (OCR)    │            │
│  └──────────┘  └──────────┘            │
└─────────────────────────────────────────┘
```

### 2.2 Capacidad

| Recurso | Limite | Alerta |
|---------|--------|--------|
| Tenants | 50 | TenantCountScaleUp |
| DB Connections | 150 (max_connections) | DatabaseConnectionsScaleUp (>80%) |
| RAM | 128 GB | HighMemoryUsage (>90%) |
| CPU | 16 cores | HighCPUUsage (>85%) |
| Disco | 2 TB NVMe | DiskSpaceLow (>85%) |

### 2.3 Optimizaciones

- **OPcache**: preload activado, 256 MB
- **Redis**: cache bins (render, data, discovery, page)
- **MariaDB**: innodb_buffer_pool_size = 32G, query_cache_size = 256M
- **Qdrant**: persistencia RDB, 1 coleccion por tenant
- **Cron**: queue workers cada 30s (HeatmapEventProcessor, etc.)

### 2.4 Umbrales de Migracion a Fase 2

Migrar cuando **cualquiera** de estos se cumpla durante 1 semana:
- Tenants activos > 50
- DB connection pool > 80% sostenido
- Disk I/O > 85% utilizacion
- p95 response time > 1s sostenido

---

## 3. Fase 2 — Separated DB

### 3.1 Arquitectura

```
┌───────────────────────┐     ┌───────────────────────┐
│    App Server          │     │    DB Server           │
│  ┌──────────┐         │     │  ┌──────────┐         │
│  │  Drupal   │         │     │  │ MariaDB  │         │
│  │  (PHP 8.4)│◄───────►│     │  │ Primary  │         │
│  └──────────┘    TCP   │     │  └──────────┘         │
│  ┌──────────┐  3306    │     │  ┌──────────┐         │
│  │  Redis    │         │     │  │ Binlog    │         │
│  └──────────┘         │     │  │ Backup    │         │
│  ┌──────────┐         │     │  └──────────┘         │
│  │  Qdrant   │         │     │                       │
│  └──────────┘         │     │  Disco: NVMe dedicado  │
└───────────────────────┘     └───────────────────────┘
```

### 3.2 Pasos de Migracion

1. **Provisionar DB Server** (IONOS L-8 o superior)
   ```bash
   # En el nuevo servidor DB
   apt install mariadb-server
   # Copiar my.cnf optimizado
   ```

2. **Configurar replicacion temporal**
   ```ini
   # /etc/mysql/mariadb.conf.d/50-server.cnf (DB Server)
   [mysqld]
   server-id              = 1
   log_bin                = /var/log/mysql/mariadb-bin
   binlog_format          = ROW
   innodb_buffer_pool_size = 64G
   max_connections         = 300
   ```

3. **Migrar datos**
   ```bash
   # Modo mantenimiento
   drush state:set system.maintenance_mode 1 --input-format=integer

   # Dump completo
   mysqldump --single-transaction --routines --triggers \
     drupal11 | gzip > /tmp/full_backup.sql.gz

   # Importar en nuevo servidor
   gunzip -c /tmp/full_backup.sql.gz | mysql -h db-server drupal11

   # Verificar
   mysql -h db-server -e "SELECT COUNT(*) FROM groups_field_data;"
   ```

4. **Actualizar settings.php**
   ```php
   // sites/default/settings.php
   $databases['default']['default'] = [
     'driver' => 'mysql',
     'host' => 'db-server.internal',  // IP interna
     'port' => '3306',
     'database' => 'drupal11',
     'username' => 'drupal_app',
     'password' => getenv('DB_PASS'),
     'prefix' => '',
     'charset' => 'utf8mb4',
     'collation' => 'utf8mb4_unicode_ci',
   ];
   ```

5. **Desactivar mantenimiento y verificar**
   ```bash
   drush state:set system.maintenance_mode 0 --input-format=integer
   drush cr
   # Ejecutar health checks
   curl -s https://plataformadeecosistemas.com/api/health
   ```

### 3.3 Capacidad Fase 2

| Recurso | App Server | DB Server |
|---------|-----------|-----------|
| Tenants | 200 | — |
| RAM | 64 GB (app) | 128 GB (DB) |
| CPU | 8 cores | 16 cores |
| innodb_buffer_pool | — | 64 GB |
| max_connections | — | 300 |

### 3.4 Umbrales de Migracion a Fase 3

- Tenants activos > 200
- CPU App Server > 75% sostenido 30min
- Response time p95 > 1.5s
- Necesidad de read replicas por volumen de consultas

---

## 4. Fase 3 — Load Balanced

### 4.1 Arquitectura

```
                    ┌──────────────┐
                    │  Load        │
          ┌────────►│  Balancer    │◄────────┐
          │         │  (HAProxy)   │         │
          │         └──────┬───────┘         │
          │                │                 │
    ┌─────┴─────┐   ┌─────┴─────┐   ┌──────┴─────┐
    │ App Srv 1 │   │ App Srv 2 │   │ App Srv N  │
    │ Drupal    │   │ Drupal    │   │ Drupal     │
    │ PHP 8.4   │   │ PHP 8.4   │   │ PHP 8.4    │
    └─────┬─────┘   └─────┬─────┘   └──────┬─────┘
          │                │                 │
    ┌─────┴────────────────┴─────────────────┴─────┐
    │              Redis Cluster                     │
    │  (Session store + Cache bins)                  │
    └──────────────────────┬───────────────────────┘
                           │
    ┌──────────────────────┴───────────────────────┐
    │                                               │
    │  ┌──────────┐     ┌──────────┐               │
    │  │ MariaDB  │────►│ MariaDB  │               │
    │  │ Primary  │     │ Replica  │               │
    │  │ (Write)  │     │ (Read)   │               │
    │  └──────────┘     └──────────┘               │
    │                    ┌──────────┐               │
    │                    │ MariaDB  │               │
    │                    │ Replica 2│               │
    │                    │ (Read)   │               │
    │                    └──────────┘               │
    └───────────────────────────────────────────────┘
```

### 4.2 Componentes Adicionales

#### Load Balancer (HAProxy)
```
# /etc/haproxy/haproxy.cfg
frontend http_front
    bind *:443 ssl crt /etc/ssl/jaraba.pem
    default_backend drupal_servers
    option forwardfor

backend drupal_servers
    balance roundrobin
    option httpchk GET /api/health
    http-check expect status 200

    server app1 10.0.1.10:80 check inter 5s fall 3 rise 2
    server app2 10.0.1.11:80 check inter 5s fall 3 rise 2
    server app3 10.0.1.12:80 check inter 5s fall 3 rise 2
```

#### Redis Cluster (Sesiones compartidas)
```php
// sites/default/settings.php
$settings['redis.connection']['interface'] = 'PhpRedis';
$settings['redis.connection']['host'] = 'redis-cluster.internal';
$settings['redis.connection']['port'] = 6379;

// Session handler via Redis
$settings['session_handler_class'] = '\Drupal\redis\Session\PhpRedisSessionHandler';
```

#### Read Replicas (Drupal)
```php
// sites/default/settings.php
$databases['default']['default'] = [
  'host' => 'db-primary.internal',
  'database' => 'drupal11',
  // ... write connection
];

$databases['default']['replica'][] = [
  'host' => 'db-replica-1.internal',
  'database' => 'drupal11',
  // ... read connection
];

$databases['default']['replica'][] = [
  'host' => 'db-replica-2.internal',
  'database' => 'drupal11',
  // ... read connection
];
```

### 4.3 Shared Storage (Archivos)

```bash
# NFS mount en cada App Server
# /etc/fstab
nfs-server.internal:/drupal-files /var/www/html/sites/default/files nfs4 defaults 0 0
```

Alternativa: S3-compatible storage con `s3fs` o Flysystem.

### 4.4 Capacidad Fase 3

| Recurso | Valor |
|---------|-------|
| Tenants | 1000+ |
| App Servers | 2-N (auto-scale) |
| DB Primary | 1 (write) |
| DB Replicas | 2+ (read) |
| Redis | Cluster (3 nodos minimo) |
| Load Balancer | HAProxy / Cloud LB |

---

## 5. Monitoreo de Escalado

### 5.1 Prometheus Recording Rules

Las siguientes metricas se registran automaticamente para
planificacion de capacidad (ver `scaling_alerts.yml`):

| Metrica | Descripcion |
|---------|-------------|
| `jaraba:tenant_count:active` | Tenants activos |
| `jaraba:requests_per_tenant:avg_1h` | Req/s promedio por tenant |
| `jaraba:db_connections_percent` | % pool de conexiones BD |
| `jaraba:memory_usage_percent` | % uso de memoria |
| `jaraba:cpu_usage_percent` | % uso de CPU |

### 5.2 Dashboard Grafana

Crear panel de "Capacity Planning" con:
- Grafico de tenants activos vs umbrales (50, 200)
- Tendencia de uso de CPU/RAM con proyeccion a 30 dias
- Pool de conexiones BD con linea de alerta al 80%
- Latencia p95 por tenant con linea de alerta a 500ms

### 5.3 Alertas por Fase

| Alerta | Severity | Fase |
|--------|----------|------|
| TenantCountScaleUp (>50) | warning | 1→2 |
| DatabaseConnectionsScaleUp (>80%) | warning | 1→2 |
| DiskIOSaturation (>85%) | warning | 1→2 |
| TenantCountLoadBalancer (>200) | warning | 2→3 |
| CPUSustainedHigh (>75%, 30min) | warning | 2→3 |
| MemoryPressureHigh (>80%, 30min) | warning | 2→3 |
| APIResponseDegradation (p95 >2s) | critical | Inmediata |
| QueueDepthCritical (>1000) | critical | Workers |
| DatabaseReplicationLagScaling (>60s) | critical | Fase 3 |

---

## 6. Checklist de Migracion

### Fase 1 → Fase 2
- [ ] Provisionar DB Server
- [ ] Configurar binlog y replicacion
- [ ] Migrar datos con mysqldump
- [ ] Actualizar settings.php con host remoto
- [ ] Ejecutar health checks
- [ ] Actualizar monitoring (scrape targets)
- [ ] Ejecutar k6 multi_tenant_load_test.js
- [ ] Actualizar runbook de backup (restore_tenant.sh con DB_HOST)

### Fase 2 → Fase 3
- [ ] Provisionar N App Servers identicos
- [ ] Configurar Redis Cluster para sesiones
- [ ] Configurar HAProxy / Cloud Load Balancer
- [ ] Configurar shared filesystem (NFS o S3)
- [ ] Configurar read replicas en MariaDB
- [ ] Actualizar settings.php con replica config
- [ ] Ejecutar k6 scaling breakpoint test
- [ ] Validar tenant isolation bajo carga
- [ ] Actualizar CI/CD para deploy multi-servidor
- [ ] Actualizar monitoring con todos los targets
