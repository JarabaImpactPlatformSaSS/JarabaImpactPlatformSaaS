# ANALISIS PROFESIONAL: Runbook v2 AMD AE12-128 NVMe

| Campo | Valor |
|-------|-------|
| Fecha | 2026-03-11 |
| Documento analizado | 20260311a-131b_Server_Implementation_Runbook_AMD_v2_Claude.md |
| Analista | Claude Code (Opus 4.6) |
| Roles aplicados | Consultor de negocio, arquitecto SaaS, ingeniero de infra, ingeniero Drupal, analista financiero, ingeniero de seguridad, ingeniero IA, ingeniero SEO |

---

## VEREDICTO GENERAL: 8.5/10

Uno de los runbooks de despliegue SaaS mas completos y bien razonados. Combina vision de negocio a 5 anos con detalle tecnico ejecutable. Hay contradicciones internas, riesgos no cubiertos, y decisiones que conviene revisar.

---

## LO QUE ESTA MUY BIEN HECHO

### 1. Decision AE12 Zen 5 vs AE16 Zen 2 — ACERTADA

La tabla comparativa de la seccion 1.2 es brillante. El razonamiento de que 12 cores Zen 5 a 5.4 GHz superan a 16 cores Zen 2 a 3.3 GHz para Drupal (single-thread dominant) es tecnicamente correcto. DDR5 vs DDR4 tambien suma. Esta es una decision que muchos errarian eligiendo "mas cores" sin entender el workload.

### 2. Externalizacion de Qdrant y Minio — INTELIGENTE

Sacar Qdrant a Qdrant Cloud y sustituir Minio por Cloudflare R2 libera ~10 GB RAM y elimina complejidad operacional. El free tier de ambos cubre el ano 1 perfectamente.

### 3. Estrategia de almacenamiento en 3 capas — SOLIDA

La proyeccion a 5 anos con las "palancas" (backups a R2 dia 1, S3FS ano 2) es pragmatica. El calculo de 37% de uso en ano 5 con ambas palancas activas da un margen confortable.

### 4. Previsiones economicas escalonadas — EXCEPCIONAL

La tabla consolidada ano 1-5 con costes desglosados por servicio es exactamente lo que un inversor o partner institucional pediria. Muy bien el dato de "infra < 1% de facturacion en ano 5".

### 5. Backup 3 capas (R2 + local + NAS) — ROBUSTO

RPO < 6h con binlog continuo y RTO < 1h es realista y bien disenado. La tercera capa con GoodSync/NAS de oficina como "ultimo recurso" es pragmatica para una startup.

---

## PROBLEMAS Y CONTRADICCIONES DETECTADAS

### PROBLEMA 1: Contradiccion Nginx vs Traefik (CRITICO)

El runbook v2 introduce Traefik v3 como reverse proxy (secciones 7.1 y 7.5), pero el repositorio tiene `nginx-metasites.conf` completamente configurado en `config/deploy/`. Ademas:

- `nginx-jaraba-common.conf` existe con snippets incluidos
- Las secciones de SSL mencionan `certbot --nginx`
- El upstream apunta a `php-fpm.sock` (nativo, no Docker)

El runbook dice Traefik pero el codebase dice Nginx. Esto implica una decision no resuelta:

| Aspecto | Nginx (codebase actual) | Traefik (runbook v2) |
|---------|------------------------|---------------------|
| SSL | Certbot + nginx plugin | Let's Encrypt integrado |
| Wildcard SSL | Requiere DNS challenge manual | Requiere DNS challenge (igual) |
| Config | Ficheros .conf probados | Labels en docker-compose |
| Rendimiento | Superior para static files | Overhead por proxy layer |
| Multi-dominio | Ya configurado (6 server blocks) | Requiere reconfigurar |
| PHP-FPM | Unix socket directo | TCP via Docker network |

**Recomendacion**: Mantener Nginx. Ya existe `nginx-metasites.conf` y `nginx-jaraba-common.conf` probados. Traefik anade una capa innecesaria para un single-server. Si en ano 4-5 se migra a multi-servidor, entonces Traefik tiene sentido.

### PROBLEMA 2: Docker vs Native — Decision no justificada (IMPORTANTE)

El runbook dockeriza TODO (Drupal, MariaDB, Redis, Tika), pero el codebase actual:

- Usa Apache via Lando (`.lando.yml`: `via: apache`)
- Nginx config apunta a unix socket nativo (`/run/php/php8.4-fpm.sock`)
- Supervisor workers ejecutan `drush` desde `/var/www/jaraba/vendor/bin/drush` (path nativo)

Docker en un servidor dedicado de un solo nodo tiene tradeoffs:

| Pro Docker | Contra Docker |
|-----------|--------------|
| Reproducibilidad | 5-15% overhead en I/O (overlay2 vs ext4 directo) |
| Rollback facil | MariaDB en Docker pierde rendimiento vs nativo |
| Aislamiento | Complejidad de networking (DNS, sockets) |
| | PHP-FPM via TCP en vez de Unix socket (latencia) |
| | Debugging mas complejo (exec en containers) |

Para un single-server dedicado con 1 sola aplicacion, native stack (Nginx + PHP-FPM + MariaDB + Redis nativos) es mas eficiente y mas simple de operar. Docker brilla en multi-servidor o cuando hay multiples aplicaciones.

**Recomendacion**: Evaluar seriamente el approach nativo. El nginx-metasites.conf ya esta listo para ello.

### PROBLEMA 3: PHP-FPM 40 workers con 12 cores — SOBREDIMENSIONADO

La seccion 7.3 configura `pm.max_children = 40` con el calculo "20GB / 512MB = 40". Pero:

- Solo hay 12 cores / 24 threads
- Drupal requests son CPU-bound (entity loading, rendering, hooks)
- Con 40 workers y 12 cores, habra context switching masivo bajo carga
- 5 workers Supervisor ya consumen cores

Calculo correcto: `threads * 1.5 = 24 * 1.5 = 36` como maximo absoluto. Pero realistamente, con MariaDB, Redis, Tika y Supervisor compitiendo:

```
Recomendado: pm.max_children = 24
             pm.start_servers = 6
             pm.min_spare_servers = 4
             pm.max_spare_servers = 12
```

### PROBLEMA 4: InnoDB buffer pool 40 GB — EXCESIVO para ano 1

La DB sera ~10 GB en ano 1. Un buffer pool de 40 GB significa 30 GB desperdiciados en RAM que no almacena nada. InnoDB reserva esta memoria al arranque.

**Recomendacion**: Empezar con `innodb_buffer_pool_size = 16G` y escalar progresivamente:

- Ano 1: 16 GB (DB ~10 GB, cabe entera + indices)
- Ano 2: 24 GB (DB ~35 GB, hot pages en RAM)
- Ano 3+: 40 GB

Esto libera 24 GB para el sistema en ano 1, mejorando la reserva real.

### PROBLEMA 5: Dominio principal — Incoherencia (IMPORTANTE)

El runbook usa `app.jarabaimpact.com` como URL principal de la aplicacion SaaS (secciones 10 y 11). Pero:

- `settings.production.php` define `jaraba_base_domain = 'plataformadeecosistemas.com'`
- `trusted_host_patterns` no incluye `jarabaimpact.com` con subdominios wildcard
- Nginx config trata `jarabaimpact.com` como meta-sitio B2B, no como SaaS base
- Los tenant subdomains usan `*.plataformadeecosistemas.com`

**El SaaS base es `plataformadeecosistemas.com`, no `app.jarabaimpact.com`**. El runbook debe corregir todas las referencias al dominio principal.

### PROBLEMA 6: Falta jaraba.es en Traefik labels

La seccion 7.5 lista los dominios en Traefik pero omite `jaraba.es`, que si esta en `trusted_host_patterns` de settings.production.php como reservado.

### PROBLEMA 7: OPcache validate_timestamps = 0 sin estrategia de invalidacion

`opcache.validate_timestamps = 0` significa que PHP nunca recarga archivos modificados. En deploy, necesitas:

- `opcache_reset()` via script
- O reiniciar PHP-FPM
- O `php-fpm reload`

El runbook menciona `docker restart jaraba-drupal` pero no explica que esto es obligatorio tras cada deploy por esta configuracion. Deberia estar en la seccion de deploy como paso explicito.

### PROBLEMA 8: Supervisor workers no mencionados en docker-compose

El `supervisor-ai-workers.conf` define 5 procesos que ejecutan `drush queue:run`. El docker-compose.prod.yml define un container `drupal-cron` para background tasks, pero no queda claro si Supervisor corre dentro del container drupal o como servicio nativo.

Si corre dentro del container Docker, necesita ser instalado en la imagen. Si corre nativo, contradice la estrategia de dockerizacion completa.

### PROBLEMA 9: SendGrid vs SMTP IONOS — Contradiccion con codebase

El runbook especifica SendGrid, pero `config/deploy/` tiene configuracion de transporte SMTP IONOS (`smtp_ionos`). El codebase actual usa Symfony Mailer con SMTP nativo de IONOS. El cambio a SendGrid API es viable pero requiere migracion del transport layer.

### PROBLEMA 10: Falta wildcard SSL strategy

La seccion 10 dice "Let's Encrypt via Traefik (auto-renovacion)" pero los wildcard certs (`*.plataformadeecosistemas.com`) requieren DNS challenge, no HTTP challenge. Esto necesita:

- Cloudflare API token configurado en certbot/Traefik
- O IONOS DNS API integration
- O cert comercial wildcard

Esto no esta documentado y es bloqueante para multi-tenancy.

---

## RIESGOS NO CUBIERTOS

### 1. Single Point of Failure total

Un solo servidor = si cae, todo cae. Para ano 1-2 es aceptable, pero el runbook deberia documentar:

- Procedimiento de rebuild desde cero (con tiempos estimados)
- IONOS server replacement SLA (cuanto tarda en darte un nuevo dedicado si falla hardware)

### 2. Ausencia de rate limiting en Nginx/Traefik

1.295+ API endpoints publicos sin rate limiting = vulnerable a abuse. Especialmente los endpoints IA (streaming, agentes) que son costosos.

### 3. No hay log rotation para Docker

Los logs de contenedores Docker crecen indefinidamente si no configuras `--log-opt max-size`. Con 52 endpoints SSE y 53 cron hooks, esto puede llenar disco.

### 4. Redis sin password en desarrollo, con password en produccion

La transicion dev a prod de Redis auth necesita ser explicita en settings.php con fallback.

### 5. MariaDB binlog consume disco sin replica

`log_bin` esta habilitado pero no hay replica configurada en ano 1-3. El binlog solo sirve para point-in-time recovery, que es util, pero consume 5-25 GB sin replica activa. Considerar `expire_logs_days = 3` en vez de 7 para ano 1.

---

## MEJORAS CONCRETAS SUGERIDAS

| # | Mejora | Impacto | Esfuerzo |
|---|--------|---------|----------|
| 1 | Resolver Nginx vs Traefik (recomiendo Nginx nativo) | Elimina ambiguedad, simplifica stack | Medio |
| 2 | Evaluar native stack vs Docker para single-server | 5-15% mejor rendimiento | Alto (decision arquitectonica) |
| 3 | Corregir dominio principal a `plataformadeecosistemas.com` | Coherencia con codebase | Bajo |
| 4 | Reducir pm.max_children a 24 | Evita context switching | Bajo |
| 5 | InnoDB buffer pool escalonado (16-24-40 GB) | Mejor uso de RAM ano 1 | Bajo |
| 6 | Documentar wildcard SSL via DNS challenge | Desbloquea multi-tenancy | Medio |
| 7 | Anadir Docker log rotation | Previene disk fill | Bajo |
| 8 | Anadir Nginx rate limiting para /api/v1/* | Protege API y costes IA | Medio |
| 9 | Clarificar donde corre Supervisor (container vs nativo) | Elimina ambiguedad operacional | Bajo |
| 10 | Documentar OPcache invalidation en deploy pipeline | Previene bugs post-deploy | Bajo |

---

## VALORACION POR ROL

| Rol | Puntuacion | Comentario |
|-----|-----------|------------|
| Consultor de negocio | 9/10 | Previsiones a 5 anos excelentes, ARPU realista, cost-to-revenue ratio bien calculado |
| Arquitecto SaaS | 8/10 | Externalizacion de Qdrant y R2 acertada, falta HA planning para ano 3+ |
| Ingeniero de infra | 7/10 | Nginx/Traefik y Docker/nativo sin resolver; PHP-FPM oversized |
| Ingeniero Drupal | 8/10 | Buena alineacion con stack real (PHP 8.4, MariaDB 10.11), falta OPcache strategy |
| Analista financiero | 9/10 | Costes escalonados realistas, palancas de ahorro bien identificadas |
| Ingeniero de seguridad | 7/10 | Hardening basico correcto, falta rate limiting y wildcard SSL strategy |
| Ingeniero IA | 8/10 | Qdrant Cloud + Supervisor workers bien dimensionados, falta circuit breaker docs |
| Ingeniero SEO | 8/10 | Datacenter Espana (latencia), CDN desde ano 2, NVMe para TTFB bajo |

---

## CONCLUSION

El runbook v2 es production-ready con las correcciones mencionadas. Las 3 correcciones mas urgentes antes de ejecutar son:

1. **Decidir Nginx nativo vs Traefik** (recomiendo Nginx — ya esta configurado)
2. **Documentar wildcard SSL via DNS challenge** (bloqueante para tenants)
3. **Corregir dominio principal** en todo el documento a `plataformadeecosistemas.com`

El resto son optimizaciones que pueden hacerse durante la implementacion. La base economica y estrategica del documento es solida.

--- Fin del Analisis ---
