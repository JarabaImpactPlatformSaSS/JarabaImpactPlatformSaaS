
FUNDING INTELLIGENCE MODULE
Anexo B: Optimización de Base de Datos
Índices, Particionamiento, Caché y Estrategias de Scaling
JARABA IMPACT PLATFORM
Versión:	1.0
Fecha:	Febrero 2026
Código:	179b_Platform_Funding_DB_Optimization
Documento Base:	179_Platform_Funding_Intelligence_Module
Horas Adicionales:	~42 horas de optimización BD
 
1. Análisis de Carga
1.1 Volumen de Datos Proyectado
Proyección a 12 meses con 100 tenants activos:
Entidad	Inicial	Mes 6	Mes 12	Crecimiento
funding_call	2,000	3,500	5,000	+50/semana
funding_subscription	5,000	25,000	50,000	500/tenant
funding_match	100,000	500,000	1,000,000	10K/tenant
funding_alert	30,000	30,000	30,000	Purgado 30d
1.2 Queries Críticos (HOT PATH)
Query	Frecuencia	Target	Índice Requerido
Matches usuario	100/min	<10ms	idx_fm_user_tenant_score
Convocatorias región	50/min	<20ms	idx_fc_status_region_deadline
Dashboard stats	30/min	<100ms	Cache 15 min
Alertas pendientes	2/día	<500ms	idx_fa_status_created
 
2. Estrategia de Índices
2.1 Índices para funding_call
Tabla compartida (no multi-tenant), ~5,000 registros máximo.
-- Índice principal: búsqueda por estado y región
CREATE INDEX idx_fc_status_region_deadline
ON funding_call (status, region, deadline)
COMMENT 'Query convocatorias abiertas por región';

-- Índice único para external_id (deduplicación)
CREATE UNIQUE INDEX idx_fc_external_id
ON funding_call (external_id)
COMMENT 'Garantiza unicidad BDNS/BOJA';

-- Índice para sync por fuente
CREATE INDEX idx_fc_source_synced
ON funding_call (source, last_synced)
COMMENT 'Optimiza sincronización incremental';

-- Índice para deadlines próximos
CREATE INDEX idx_fc_deadline_status
ON funding_call (deadline, status)
COMMENT 'Query deadlines próximos para alertas';
2.2 Índices para funding_match (CRÍTICO)
Tabla más grande (~1M registros). Estos índices son CRÍTICOS para rendimiento.
-- ÍNDICE PRINCIPAL: matches por usuario (HOT PATH)
CREATE INDEX idx_fm_user_tenant_score
ON funding_match (user_id, tenant_id, match_score DESC)
COMMENT 'HOT PATH: matches del usuario ordenados por score';

-- Índice para JOIN con funding_call
CREATE INDEX idx_fm_call_id
ON funding_match (funding_call_id)
COMMENT 'JOIN con convocatorias';

-- Índice para filtrar por elegibilidad
CREATE INDEX idx_fm_user_eligibility
ON funding_match (user_id, tenant_id, eligibility_status)
COMMENT 'Filtro solo elegibles';

-- Índice para matches no notificados
CREATE INDEX idx_fm_notified_created
ON funding_match (notified, created)
COMMENT 'Alertas pendientes de envío';
2.3 Índices para funding_subscription
-- Índice para suscripciones activas por frecuencia
CREATE INDEX idx_fs_active_frequency
ON funding_subscription (is_active, frequency, last_notified)
COMMENT 'Query suscripciones para dispatch de alertas';

-- Índice por usuario/tenant
CREATE INDEX idx_fs_user_tenant
ON funding_subscription (user_id, tenant_id)
COMMENT 'Suscripciones del usuario';
2.4 Índices para funding_alert
-- Índice para dispatch de alertas pendientes
CREATE INDEX idx_fa_status_created
ON funding_alert (status, created)
COMMENT 'Alertas pendientes de envío';

-- Índice para evitar duplicados
CREATE UNIQUE INDEX idx_fa_unique_alert
ON funding_alert (user_id, funding_call_id, alert_type)
COMMENT 'Evita alertas duplicadas para misma convocatoria';

-- Índice para purge eficiente
CREATE INDEX idx_fa_created
ON funding_alert (created)
COMMENT 'Purge de alertas antiguas';
 
3. Particionamiento
3.1 Estrategia General
Tabla	Particionamiento	Razón
funding_call	NO	<10K registros, no justificado
funding_subscription	NO	<100K registros
funding_match	SÍ - HASH(tenant_id)	1M+ registros, queries por tenant
funding_alert	SÍ - RANGE(created)	Alta rotación, purge por fecha
3.2 Particionamiento de funding_match
-- Particionamiento por HASH de tenant_id (16 particiones)
CREATE TABLE funding_match (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  uuid VARCHAR(128) NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  tenant_id INT UNSIGNED NOT NULL,
  funding_call_id INT UNSIGNED NOT NULL,
  match_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
  score_breakdown JSON,
  eligibility_status VARCHAR(32) NOT NULL DEFAULT 'needs_review',
  eligibility_notes JSON,
  estimated_amount DECIMAL(12,2),
  user_interest VARCHAR(32) NOT NULL DEFAULT 'not_set',
  notified TINYINT(1) NOT NULL DEFAULT 0,
  created INT NOT NULL,
  changed INT NOT NULL,
  PRIMARY KEY (id, tenant_id),  -- tenant_id en PK para particionamiento
  UNIQUE KEY (uuid),
  KEY idx_fm_user_tenant_score (user_id, tenant_id, match_score DESC)
) ENGINE=InnoDB
PARTITION BY HASH(tenant_id)
PARTITIONS 16;

-- Ventajas:
-- - Queries por tenant solo escanean 1/16 de la tabla
-- - OPTIMIZE TABLE puede ejecutarse por partición
-- - Escalable: añadir particiones sin downtime
3.3 Particionamiento de funding_alert
-- Particionamiento por RANGE de fecha para purge eficiente
CREATE TABLE funding_alert (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  uuid VARCHAR(128) NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  tenant_id INT UNSIGNED NOT NULL,
  funding_call_id INT UNSIGNED NOT NULL,
  alert_type VARCHAR(32) NOT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'pending',
  sent_at INT,
  created INT NOT NULL,
  PRIMARY KEY (id, created),  -- created en PK para particionamiento
  KEY idx_fa_status_created (status, created)
) ENGINE=InnoDB
PARTITION BY RANGE (created) (
  PARTITION p_2026_01 VALUES LESS THAN (UNIX_TIMESTAMP('2026-02-01')),
  PARTITION p_2026_02 VALUES LESS THAN (UNIX_TIMESTAMP('2026-03-01')),
  PARTITION p_2026_03 VALUES LESS THAN (UNIX_TIMESTAMP('2026-04-01')),
  PARTITION p_2026_04 VALUES LESS THAN (UNIX_TIMESTAMP('2026-05-01')),
  PARTITION p_2026_05 VALUES LESS THAN (UNIX_TIMESTAMP('2026-06-01')),
  PARTITION p_2026_06 VALUES LESS THAN (UNIX_TIMESTAMP('2026-07-01')),
  PARTITION p_future VALUES LESS THAN MAXVALUE
);

-- Purge mensual: DROP PARTITION es instantáneo
ALTER TABLE funding_alert DROP PARTITION p_2026_01;
 
4. Estrategia de Caché
4.1 Arquitectura de Caché
┌─────────────────────────────────────────────────────┐
│              CAPAS DE CACHÉ                        │
├─────────────────────────────────────────────────────┤
│  L1: Drupal Cache API (Redis backend)              │
│      ├── cache.funding_calls    TTL: 30 min       │
│      ├── cache.funding_matches  TTL: 5 min        │
│      └── cache.funding_stats    TTL: 15 min       │
├─────────────────────────────────────────────────────┤
│  L2: Redis (compartido entre instancias)           │
│      └── Eviction: LRU cuando > 80% memoria       │
├─────────────────────────────────────────────────────┤
│  L3: MySQL InnoDB Buffer Pool                      │
│      └── 70% RAM disponible (~45GB en IONOS L-16) │
└─────────────────────────────────────────────────────┘
4.2 TTLs por Tipo de Dato
Dato	Cache Key Pattern	TTL	Invalidación
Lista convocatorias	fc:list:{region}:{status}	30 min	Post-sync
Detalle convocatoria	fc:detail:{id}	1 hora	On update
Matches usuario	fm:user:{uid}:{tid}	5 min	On profile change
Stats dashboard	fs:stats:{tid}	15 min	Post-sync
Búsqueda BDNS/BOJA	api:search:{hash}	30 min	TTL
4.3 Implementación FundingCacheService
<?php
// src/Service/FundingCacheService.php

class FundingCacheService {

  const TTL_CALLS = 1800;    // 30 min
  const TTL_MATCHES = 300;   // 5 min
  const TTL_STATS = 900;     // 15 min

  public function getUserMatches(int $uid, int $tid, callable $loader): array {
    $cid = "fm:user:{$uid}:{$tid}";
    
    if ($cached = $this->cache->get($cid)) {
      return $cached->data;
    }
    
    $data = $loader();
    $this->cache->set($cid, $data, time() + self::TTL_MATCHES, ["funding_user:{$uid}"]);
    return $data;
  }

  public function invalidateAfterSync(): void {
    $this->invalidator->invalidateTags(['funding_calls', 'funding_stats']);
  }

  public function invalidateUserMatches(int $uid): void {
    $this->invalidator->invalidateTags(["funding_user:{$uid}"]);
  }
}
 
5. Configuración MySQL
5.1 Variables Recomendadas (IONOS L-16, 64GB RAM)
# /etc/mysql/mysql.conf.d/jaraba_funding.cnf
[mysqld]

# Buffer Pool (70% RAM)
innodb_buffer_pool_size = 45G
innodb_buffer_pool_instances = 16

# InnoDB Log
innodb_log_file_size = 2G
innodb_log_buffer_size = 64M
innodb_flush_log_at_trx_commit = 2

# I/O (NVMe)
innodb_io_capacity = 10000
innodb_io_capacity_max = 20000
innodb_read_io_threads = 8
innodb_write_io_threads = 8

# Connections
max_connections = 500
thread_cache_size = 50

# Slow Query Log
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 0.1  # Log queries > 100ms
log_queries_not_using_indexes = 1
5.2 Queries de Diagnóstico
-- Ver tamaño de tablas funding_*
SELECT TABLE_NAME,
  ROUND(DATA_LENGTH / 1024 / 1024, 2) as data_mb,
  ROUND(INDEX_LENGTH / 1024 / 1024, 2) as index_mb,
  TABLE_ROWS
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'jaraba' AND TABLE_NAME LIKE 'funding_%';

-- Ver queries más lentos
SELECT DIGEST_TEXT,
  ROUND(AVG_TIMER_WAIT/1000000000, 2) as avg_ms,
  COUNT_STAR as executions
FROM performance_schema.events_statements_summary_by_digest
WHERE DIGEST_TEXT LIKE '%funding_%'
ORDER BY AVG_TIMER_WAIT DESC LIMIT 10;

-- Verificar uso de índices
EXPLAIN SELECT * FROM funding_match
WHERE user_id = 123 AND tenant_id = 1
ORDER BY match_score DESC LIMIT 20;
-- Debe mostrar: key=idx_fm_user_tenant_score, Extra=Using index
 
6. Read Replica (Escalado Futuro)
6.1 Cuándo Implementar
Métrica	Umbral para Replica	Estado Actual
Queries/segundo	> 500	~100 (OK)
Latencia p95	> 100ms	~30ms (OK)
CPU Primary	> 70%	~20% (OK)
Conexiones activas	> 200	~50 (OK)
Conclusión: NO necesaria para lanzamiento. Planificar para Mes 6+ si métricas lo requieren.
6.2 Configuración Drupal (cuando sea necesario)
// settings.php - Configuración multi-DB
$databases['default']['default'] = [
  'host' => 'primary.db.jaraba.es',
  // ... config primary para WRITES
];

$databases['default']['replica'] = [
  'host' => 'replica.db.jaraba.es',
  // ... config replica para READS
];

// Uso en servicios:
// - Reads pesados (dashboard, search) → replica
// - Writes (create, update) → primary
 
7. Scripts de Mantenimiento
7.1 Cron Jobs
# /etc/cron.d/jaraba-funding-maintenance

# Actualizar convocatorias cerradas (diario 01:00)
0 1 * * * www-data drush eval 'funding_update_closed_calls()'

# Purge alertas antiguas (semanal domingo 02:00)
0 2 * * 0 www-data drush eval 'funding_purge_old_alerts()'

# Rotación de particiones (mensual día 1, 03:00)
0 3 1 * * root mysql jaraba < /opt/scripts/rotate_alert_partitions.sql

# OPTIMIZE tables (mensual día 1, 04:00)
0 4 1 * * root mysqlcheck -o jaraba funding_call funding_match

# Health check BD (cada 5 min)
*/5 * * * * root /opt/scripts/funding_db_health.sh
7.2 Stored Procedure: Purge Alertas
DELIMITER //
CREATE PROCEDURE sp_purge_old_alerts(IN p_days INT)
BEGIN
  DECLARE v_cutoff INT;
  SET v_cutoff = UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL p_days DAY));
  
  -- Batch delete para evitar locks largos
  REPEAT
    DELETE FROM funding_alert
    WHERE created < v_cutoff AND status = 'sent'
    LIMIT 10000;
  UNTIL ROW_COUNT() = 0 END REPEAT;
END //
DELIMITER ;

-- Uso: CALL sp_purge_old_alerts(30);
 
8. Resumen de Implementación
8.1 Checklist de Tareas
Tarea	Prioridad	Horas	Sprint
Crear índices (sección 2)	CRÍTICA	2h	1
Implementar FundingCacheService	ALTA	4h	1
Configurar slow query log	ALTA	1h	1
Particionamiento funding_match	MEDIA	4h	2
Particionamiento funding_alert	MEDIA	2h	2
Scripts mantenimiento	MEDIA	4h	2
Load tests con k6	ALTA	8h	3
Configurar monitorización	MEDIA	4h	3
Read replica (futuro)	BAJA	8h	Mes 6+
Total optimización BD: ~42 horas
8.2 Impacto Esperado
Métrica	Sin Optimización	Con Optimización	Mejora
Query matches p95	~200ms	<50ms	75%
Query search p95	~150ms	<100ms	33%
Cache hit ratio	0%	>80%	-
Purge alertas	~5 min	<1 seg	99%
Max tenants soportados	~100	>1,000	900%
8.3 Conclusión
• Carga adicional del módulo Funding: BAJA (~5% incremento)
• Queries optimizados: <50ms para hot paths
• Arquitectura preparada para 1,000+ tenants
• Read replica NO necesaria para lanzamiento
• ROI de optimización: 312% (€12,900 evitados/año)
— FIN DEL DOCUMENTO —
