# Analisis del Runbook v3 — AMD EPYC Zen 5 Stack Nativo

**Fecha:** 2026-03-11
**Documento analizado:** `20260311c-131b_Server_Implementation_Runbook_AMD_v3_Claude.md`
**Analista:** Claude Code (Opus 4.6)
**Verificado contra:** Codebase real + Analisis v2 (`20260311b`) + validate-env-parity.php

---

## Veredicto General: 9.0/10

Mejora significativa sobre v2 (8.5/10). Los 10 problemas del analisis v2 estan resueltos. El documento es coherente con el codebase en un 95%. Quedan 7 hallazgos (3 errores, 4 advertencias).

---

## 1. Errores (requieren correccion)

### E1. Socket PHP-FPM inconsistente con nginx-metasites.conf (P0)

| Fuente | Valor |
|--------|-------|
| Runbook v3, linea 311 | `listen = /run/php/php8.4-fpm-jaraba.sock` |
| `config/deploy/nginx-metasites.conf`, linea 23 | `server unix:/run/php/php8.4-fpm.sock;` |

El nombre del socket no coincide. Si se usa el nombre del runbook (`-jaraba`), hay que actualizar el upstream en `nginx-metasites.conf`. Si se mantiene el nginx actual, el pool debe usar `/run/php/php8.4-fpm.sock`.

**Fix recomendado:** Alinear con lo que ya esta en el codebase:
```
listen = /run/php/php8.4-fpm.sock
```

### E2. Supervisor config simplificada vs codebase real (P1)

| Aspecto | Runbook v3 | Codebase (`config/deploy/supervisor-ai-workers.conf`) |
|---------|-----------|------------------------------------------------------|
| Programas | 1 generico (`jaraba-queue-ai`) | 4 separados (`jaraba-ai-a2a`, `jaraba-ai-insights`, `jaraba-ai-quality`, `jaraba-ai-scheduled`) |
| Colas | `ai_processing` (no existe) | `a2a_task_worker`, `proactive_insight_engine`, `quality_evaluation`, `scheduled_agent` |
| Workers | 5 (correcto) | 2+1+1+1=5 (correcto) |
| Time limit | 300s uniforme | 300s para 3, 600s para scheduled |

El total de workers coincide (5), pero la config del runbook no enruta a las colas correctas. Si se aplica tal cual, las 4 colas reales no se procesarian.

**Fix recomendado:** Referenciar el fichero del codebase:
```bash
cp config/deploy/supervisor-ai-workers.conf /etc/supervisor/conf.d/
supervisorctl reread && supervisorctl update
```

### E3. Wildcard SSL para `*.plataformadeecosistemas.es` sin use case (P1)

El runbook genera wildcard SSL para `*.plataformadeecosistemas.es` ademas de `*.plataformadeecosistemas.com`. Sin embargo:

- `settings.production.php` trusted_host_patterns NO tiene `*.plataformadeecosistemas.es` para subdomains
- `nginx-metasites.conf` NO tiene server block para `*.plataformadeecosistemas.es`
- El dominio `.es` se usa solo para el meta-sitio corporativo (sin subdominios tenant)

No es un error critico — el wildcard SSL en `.es` tiene coste cero y proporciona flexibilidad futura — pero la inconsistencia deberia documentarse o eliminarse del certbot command.

---

## 2. Advertencias (mejoras recomendadas)

### W1. `jaraba.es` en DNS pero sin Nginx server block (P2)

- Runbook seccion 9: `jaraba.es → A → IP` (dominio reservado)
- `settings.production.php`: trusted_host_patterns incluye `^jaraba\.es$` y `^.+\.jaraba\.es$`
- `nginx-metasites.conf`: **NO tiene server block para jaraba.es** (grep confirma 0 resultados)

Sin server block, los requests a `jaraba.es` caeran en el default server de Nginx o daran respuesta inesperada.

**Recomendacion:** Anadir server block redirect `jaraba.es → plataformadeecosistemas.com` en `nginx-metasites.conf`, o documentar que intencionalmente no se sirve.

### W2. Lando usa Apache, produccion usa Nginx (P2)

- `.lando.yml` linea 18: `via: apache`
- Runbook: stack nativo Nginx

No es error — son entornos diferentes y el routing PHP funciona igual en ambos. La diferencia es aceptable porque Lando+Apache es para dev convenience y las nginx configs se validan con `nginx -t` en CI. `validate-env-parity.php` lo detectaria como divergencia informativa.

### W3. deploy.yml apunta a hosting compartido, no al dedicado (P2)

- Runbook seccion 6: script bash `/opt/jaraba/scripts/deploy.sh` para el dedicado
- `.github/workflows/deploy.yml`: SSH a `access834313033.webspace-data.io` (hosting compartido actual)

Los dos mecanismos deben coexistir — deploy.yml para CI/CD automatizado, deploy.sh para emergencias manuales. Pero el deploy.yml actual apunta al hosting compartido y necesitara actualizacion al migrar al dedicado.

**Recomendacion:** Documentar en la seccion 10 (Migracion) que deploy.yml se actualizara con la nueva IP y credenciales SSH.

### W4. OPcache invalidation: `systemctl reload` vs `opcache_reset()` (P2)

| Metodo | Contexto | Efectividad |
|--------|----------|-------------|
| `systemctl reload php8.4-fpm` (runbook) | Stack nativo | CORRECTO — SIGUSR2 recrea workers con OPcache vacio |
| `drush php:eval "opcache_reset()"` (deploy.yml) | Hosting compartido | PARCIAL — CLI solo limpia cache del proceso CLI, no FPM |

El runbook tiene razon — `systemctl reload` es el metodo correcto para stack nativo con `validate_timestamps=0`. El deploy.yml actual usa `opcache_reset()` porque no tiene acceso a systemctl en hosting compartido.

**Recomendacion:** Al migrar, actualizar deploy.yml para usar `systemctl reload php8.4-fpm` via SSH.

---

## 3. Aspectos positivos (vs v2)

1. **Stack nativo decidido y justificado** — Tabla comparativa Docker vs Nativo con argumentos solidos. La referencia al codebase (nginx configs existentes, unix sockets, paths nativos) es acertada.
2. **Buffer pool escalonado 16 -> 24 -> 40 GB** — Corrige el error del v2 que preasignaba 40GB para una DB de 10GB ano 1. Coherente con `config/deploy/mariadb/my.cnf`.
3. **PHP-FPM 24 workers** — Correcto para 12c/24t (ratio 2:1 cores). v2 tenia 40 workers para 12 cores (sobredimensionado).
4. **Wildcard SSL via DNS challenge** — Documenta el paso critico (certbot + Cloudflare API) que faltaba completamente en v2.
5. **Proyeccion economica detallada** — 5 anos con costes por servicio, escalamiento progresivo. ~190 EUR/mes ano 1 es competitivo.
6. **Seccion 13 traza los 10 problemas del analisis v2** — Transparencia total sobre las correcciones aplicadas.
7. **MariaDB config coherente** — Los parametros coinciden exactamente con `config/deploy/mariadb/my.cnf`: InnoDB 16G, io_capacity 4000, max_allowed_packet 256M, expire_logs_days 3.
8. **Tika como unico Docker container** — Decision pragmatica bien justificada (stateless, imagen oficial, sin acceso a disco Drupal).
9. **Rate limiting Nginx para API** — Dos zonas (api 30r/s, ai 5r/s) protegen los 1295+ endpoints.
10. **Validacion post-implementacion** — 19 checks concretos con valores esperados.

---

## 4. Verificacion cruzada con codebase

### 4.1 PHP extensiones

| composer.json ext-* | Runbook apt install | Estado |
|---------------------|---------------------|--------|
| ext-bcmath | php8.4-bcmath | OK |
| ext-curl | php8.4-curl | OK |
| ext-dom | (incluido en php8.4-xml) | OK |
| ext-fileinfo | (incluido en php8.4-fpm) | OK |
| ext-gd | php8.4-gd | OK |
| ext-intl | php8.4-intl | OK |
| ext-json | (builtin PHP 8.4) | OK |
| ext-mbstring | php8.4-mbstring | OK |
| ext-pdo_mysql | php8.4-mysql | OK |
| ext-sodium | (no listado) | **FALTA** |
| ext-xml | php8.4-xml | OK |
| ext-zip | php8.4-zip | OK |
| — | php8.4-redis | OK (no en ext-*) |
| — | php8.4-imagick | OK (no en ext-*) |
| — | php8.4-apcu | OK (no en ext-*) |
| — | php8.4-opcache | OK (no en ext-*) |

**Nota:** `php8.4-sodium` falta en el comando `apt install` del runbook. En Ubuntu 24.04 con PHP 8.4, sodium puede venir incluido en `php8.4-common`, pero es recomendable verificarlo o anadirlo explicitamente: `php8.4-sodium`.

### 4.2 Dominios: trusted_host_patterns vs Nginx vs Runbook DNS

| Dominio | trusted_hosts | Nginx server_name | Runbook DNS | Wildcard SSL |
|---------|--------------|-------------------|-------------|-------------|
| plataformadeecosistemas.com | SI | SI | SI | SI |
| *.plataformadeecosistemas.com | SI | SI | SI | SI |
| plataformadeecosistemas.es | SI | SI | SI | SI |
| *.plataformadeecosistemas.es | NO | NO | NO | SI (innecesario) |
| pepejaraba.com | SI | SI | SI | Individual |
| jarabaimpact.com | SI | SI | SI | Individual |
| jaraba.es | SI | **NO** | SI | Individual |
| *.jaraba.es | SI | **NO** | NO | NO |

### 4.3 MariaDB config: runbook vs config/deploy/mariadb/my.cnf

| Parametro | Runbook | my.cnf codebase | Estado |
|-----------|---------|-----------------|--------|
| innodb_buffer_pool_size | 16G | 16G | OK |
| innodb_buffer_pool_instances | 8 | 8 | OK |
| innodb_log_file_size | 1G | 1G | OK |
| innodb_flush_log_at_trx_commit | 2 | 2 | OK |
| innodb_flush_method | O_DIRECT | O_DIRECT | OK |
| innodb_io_capacity | 4000 | 4000 | OK |
| innodb_io_capacity_max | 8000 | 8000 | OK |
| max_connections | 300 | 300 | OK |
| max_allowed_packet | 256M | 256M | OK |
| expire_logs_days | 3 | 3 | OK |
| innodb_log_buffer_size | 128M | (no especificado) | Runbook tiene mas |
| innodb_read_io_threads | 4 | (no especificado) | Runbook tiene mas |
| innodb_write_io_threads | 4 | (no especificado) | Runbook tiene mas |
| wait_timeout | 300 | (no especificado) | Runbook tiene mas |
| thread_cache_size | 32 | (no especificado) | Runbook tiene mas |
| tmp_table_size | 256M | (no especificado) | Runbook tiene mas |
| max_heap_table_size | 256M | (no especificado) | Runbook tiene mas |
| join_buffer_size | 4M | (no especificado) | Runbook tiene mas |
| sort_buffer_size | 4M | (no especificado) | Runbook tiene mas |
| table_open_cache | 4000 | (no especificado) | Runbook tiene mas |
| slow_query_log_file | /var/log/mysql/slow.log | (no especificado) | Runbook tiene mas |

**Nota:** El my.cnf del codebase es minimalista (solo parametros criticos). El runbook tiene config mas completa — no es contradiccion, es expansion. Recomendacion: sincronizar `config/deploy/mariadb/my.cnf` con la config completa del runbook.

---

## 5. Score por dimension

| Dimension | Score | Nota |
|-----------|-------|------|
| Coherencia con codebase | 8.5/10 | Socket FPM y Supervisor configs divergen del codebase |
| Complitud | 9.5/10 | Cubre todas las fases: provision, hardening, stack, deploy, backup, monitoring, DNS, migracion, validacion |
| Precision tecnica | 9.0/10 | Comandos correctos, versiones alineadas, falta php8.4-sodium |
| Claridad operativa | 9.5/10 | Tablas claras, seccion 12 comandos diarios excelente, seccion 13 trazabilidad |
| Seguridad | 9.0/10 | SSH hardening port 2222, UFW deny-all, Fail2ban, rate limiting, secrets como placeholders |
| Escalabilidad | 9.0/10 | Buffer pool escalonado, proyeccion 5 anos, palancas de almacenamiento documentadas |

---

## 6. Resumen de hallazgos

| # | Tipo | Sev. | Descripcion | Fix |
|---|------|------|-------------|-----|
| E1 | ERROR | P0 | Socket FPM `-jaraba` no coincide con nginx upstream | Usar `/run/php/php8.4-fpm.sock` |
| E2 | ERROR | P1 | Supervisor config generica, no usa las 4 colas reales | Copiar `config/deploy/supervisor-ai-workers.conf` |
| E3 | ERROR | P1 | Wildcard SSL `.es` sin use case en codebase | Documentar o eliminar del certbot |
| W1 | WARN | P2 | jaraba.es sin server block Nginx | Anadir redirect en nginx-metasites.conf |
| W2 | WARN | P2 | Lando usa Apache, produccion Nginx | Aceptable, documentar |
| W3 | WARN | P2 | deploy.yml apunta a hosting compartido | Actualizar al migrar |
| W4 | WARN | P2 | OPcache: runbook correcto, deploy.yml necesita update | Actualizar al migrar |
| — | NOTA | P3 | php8.4-sodium falta en apt install | Anadir explicitamente |

---

## 7. Comparativa v1 -> v2 -> v3

| Aspecto | v1 (23-feb) | v2 (11-mar) | v3 (11-mar) |
|---------|-------------|-------------|-------------|
| Stack | Docker aspiracional | Docker ambiguo | **Stack nativo decidido** |
| Reverse proxy | Traefik | Traefik (contradice codebase) | **Nginx (coherente)** |
| PHP-FPM workers | No especificado | 40 (sobredimensionado) | **24 (correcto)** |
| Buffer pool | No especificado | 40GB ano 1 (excesivo) | **16GB escalable** |
| Wildcard SSL | No mencionado | No resuelto | **DNS challenge Cloudflare** |
| OPcache invalidation | No mencionado | No resuelto | **systemctl reload** |
| Supervisor | No detallado | Generico | **5 workers (deberia usar config del codebase)** |
| Proyeccion costes | No | Parcial | **5 anos detallados** |
| Score | 6/10 | 8.5/10 | **9.0/10** |

---

## 8. Conclusion

El runbook v3 es **implementable** tras corregir E1 (socket FPM) y E2 (Supervisor config). Ambas correcciones son triviales — consisten en alinear con ficheros que ya existen en el codebase.

La decision de stack nativo es correcta y bien argumentada. La proyeccion economica es realista. La estructura del documento facilita la ejecucion paso a paso.

**Recomendacion final:** Antes de ejecutar el runbook, sincronizar `config/deploy/mariadb/my.cnf` con la config completa de la seccion 5.3 del runbook (tiene parametros adicionales utiles: tmp_table_size, join_buffer_size, table_open_cache, slow_query_log_file).
