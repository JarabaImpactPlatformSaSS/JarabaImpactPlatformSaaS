# GO-LIVE RUNBOOK - Jaraba Impact Platform SaaS

> **DOCUMENTO EJECUTIVO**: Procedimiento completo de puesta en produccion de la plataforma.
> **Clasificacion**: Operaciones / Confidencial
> **Ultima actualizacion**: 2026-02-12
> **Version**: 1.0.0

---

## Tabla de Contenidos

1. [Informacion General](#1-informacion-general)
2. [Fase 1: Pre-Go-Live (T-7 dias)](#2-fase-1-pre-go-live-t-7-dias)
3. [Fase 2: Deploy (T-0)](#3-fase-2-deploy-t-0)
4. [Fase 3: Validacion (T+0)](#4-fase-3-validacion-t0)
5. [Fase 4: Decision Go/No-Go](#5-fase-4-decision-gono-go)
6. [Fase 5: Soft Launch (T+1 a T+7)](#6-fase-5-soft-launch-t1-a-t7)
7. [Fase 6: Public Launch (T+7+)](#7-fase-6-public-launch-t7)
8. [Roles y Responsabilidades](#8-roles-y-responsabilidades)
9. [Plan de Comunicacion](#9-plan-de-comunicacion)
10. [Procedimiento de Escalacion](#10-procedimiento-de-escalacion)
11. [Criterios de Exito](#11-criterios-de-exito)
12. [Triggers de Rollback](#12-triggers-de-rollback)
13. [Checklist Maestro](#13-checklist-maestro)
14. [Contactos de Emergencia](#14-contactos-de-emergencia)

---

## 1. Informacion General

### 1.1 Plataforma

| Atributo | Valor |
|----------|-------|
| **Nombre** | Jaraba Impact Platform SaaS |
| **CMS** | Drupal 11.x |
| **PHP** | 8.4+ |
| **Base de datos** | MariaDB 10.5+ |
| **Vector DB** | Qdrant |
| **Billing** | Stripe (Billing + Connect) |
| **Hosting** | IONOS Shared Hosting |
| **Deploy** | Git push to production |
| **Dominio** | plataformadeecosistemas.com |
| **Modulos custom** | 86 |

### 1.2 Verticales

| Vertical | Ruta principal | Modulo |
|----------|----------------|--------|
| Empleabilidad | `/empleo`, `/talento` | `jaraba_job_board`, `jaraba_candidate`, `jaraba_lms` |
| Emprendimiento | `/emprender` | `jaraba_business_tools`, `jaraba_diagnostic`, `jaraba_mentoring` |
| AgroConecta | `/marketplace` | `jaraba_agroconecta_core`, `jaraba_commerce` |
| ComercioConecta | `/comercio` | `jaraba_comercio_conecta`, `jaraba_social_commerce` |
| ServiciosConecta | `/instituciones` | `jaraba_servicios_conecta` |

### 1.3 Scripts de Go-Live

| Script | Funcion |
|--------|---------|
| `scripts/golive/01_preflight_checks.sh` | 24 validaciones pre-lanzamiento |
| `scripts/golive/02_validation_suite.sh` | Smoke tests post-deploy por vertical |
| `scripts/golive/03_rollback.sh` | Rollback automatizado con notificaciones |
| `scripts/deploy.sh` | Script de deploy estandar |

---

## 2. Fase 1: Pre-Go-Live (T-7 dias)

### 2.1 Testing Final

**Responsable**: Equipo de Desarrollo

- [ ] Ejecutar suite de tests unitarios completa (`phpunit`)
- [ ] Verificar los 86 módulos con `drush pm:list --status=enabled`
- [ ] Ejecutar preflight checks: `./scripts/golive/01_preflight_checks.sh`
- [ ] Revisar todos los watchdog errors de los ultimos 7 dias
- [ ] Validar integracion Stripe en modo test:
  - Crear suscripcion de prueba
  - Verificar webhook de facturacion
  - Probar flujo de dunning (pago fallido)
  - Confirmar portal de Stripe Customer
- [ ] Validar integracion Qdrant:
  - Verificar colecciones existentes
  - Probar busqueda vectorial (RAG)
  - Confirmar tiempos de respuesta < 200ms
- [ ] Probar los 5 flujos de registro vertical
- [ ] Verificar que no hay codigo con `dd()`, `var_dump()`, `console.log()` en produccion
- [ ] Revisar Content Security Policy y security headers

### 2.2 Estrategia de Backup

**Responsable**: DevOps Lead

- [ ] Crear backup completo de BD: `drush sql-dump --gzip > ~/backups/db_pre_golive_TIMESTAMP.sql.gz`
- [ ] Backup de `settings.local.php` (credenciales IONOS)
- [ ] Documentar commit actual: `git rev-parse HEAD`
- [ ] Verificar espacio en disco para backups (minimo 500MB libres)
- [ ] Probar restauracion de backup en entorno local:
  ```bash
  zcat ~/backups/db_pre_golive_TIMESTAMP.sql.gz | drush sql:cli
  ```
- [ ] Configurar backup automatico nocturno via cron
- [ ] Guardar copia offline del backup mas reciente

### 2.3 Briefing de Equipo

**Responsable**: Project Lead

- [ ] Reunion de equipo go-live (todos los roles presentes)
- [ ] Revisar este runbook con todo el equipo
- [ ] Asignar roles especificos para T-0 (ver seccion 8)
- [ ] Confirmar disponibilidad del equipo para T-0 y T+1
- [ ] Establecer canal de comunicacion de emergencia (Slack / WhatsApp)
- [ ] Definir ventana de deploy: **horario recomendado 06:00-08:00 CET** (menor trafico)
- [ ] Distribuir contactos de emergencia (ver seccion 14)

### 2.4 Preparacion de Infraestructura

- [ ] Verificar SSL: `echo | openssl s_client -servername plataformadeecosistemas.com -connect plataformadeecosistemas.com:443 | openssl x509 -noout -enddate`
- [ ] Confirmar DNS apuntando al servidor IONOS correcto
- [ ] Revisar `.htaccess` con RewriteBase habilitado
- [ ] Verificar permisos: `settings.php` en 444, `sites/default/files` escribible
- [ ] Confirmar cron de IONOS configurado: `setup-cron-production.sh`
- [ ] Verificar limites de PHP: `memory_limit >= 256M`, `max_execution_time >= 120`
- [ ] Confirmar que Redis/cache backend esta operativo

---

## 3. Fase 2: Deploy (T-0)

### 3.1 Checklist Pre-Deploy (30 minutos antes)

| # | Accion | Responsable | Estado |
|---|--------|-------------|--------|
| 1 | Enviar aviso a equipo: "Deploy en 30 min" | Project Lead | [ ] |
| 2 | Verificar que no hay deploys activos | DevOps | [ ] |
| 3 | Confirmar que el canal de emergencia esta activo | Project Lead | [ ] |
| 4 | Ultima revision de git log | Dev Lead | [ ] |

### 3.2 Procedimiento de Deploy Paso a Paso

**Ejecutor**: DevOps Lead
**Observador**: Dev Lead (segunda pantalla)
**Tiempo estimado**: 15-25 minutos

```bash
# PASO 1: Conectar al servidor IONOS
ssh usuario@servidor-ionos

# PASO 2: Navegar al proyecto
cd ~/JarabaImpactPlatformSaaS

# PASO 3: Ejecutar preflight checks
./scripts/golive/01_preflight_checks.sh
# >>> Si hay FAILs criticos, DETENER y reportar

# PASO 4: Crear backup pre-deploy
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
/usr/bin/php8.4-cli vendor/bin/drush.php sql-dump --gzip > ~/backups/db_pre_deploy_${TIMESTAMP}.sql.gz
echo "Backup: ${TIMESTAMP}" | tee -a ~/backups/deploy_log.txt

# PASO 5: Habilitar modo mantenimiento
/usr/bin/php8.4-cli vendor/bin/drush.php state:set system.maintenance_mode 1
/usr/bin/php8.4-cli vendor/bin/drush.php cr

# PASO 6: Desbloquear permisos para git pull
chmod 755 web/sites/default
chmod 644 web/sites/default/settings.php

# PASO 7: Pull del codigo
git fetch origin
git pull origin main

# PASO 8: Restaurar permisos seguros
chmod 555 web/sites/default
chmod 444 web/sites/default/settings.php

# PASO 9: Verificar settings.local.php (CRITICO)
ls -la web/sites/default/settings.local.php
# >>> Si no existe, restaurar desde backup antes de continuar

# PASO 10: Habilitar RewriteBase (IONOS)
sed -i 's/# RewriteBase \//RewriteBase \//' web/.htaccess 2>/dev/null || true

# PASO 11: Instalar dependencias
/usr/bin/php8.4-cli ~/bin/composer.phar install --no-dev --optimize-autoloader

# PASO 12: Ejecutar database updates
/usr/bin/php8.4-cli vendor/bin/drush.php updatedb -y

# PASO 13: Importar configuracion
/usr/bin/php8.4-cli vendor/bin/drush.php config:import -y

# PASO 14: Rebuild cache
/usr/bin/php8.4-cli vendor/bin/drush.php cr

# PASO 15: Deshabilitar modo mantenimiento
/usr/bin/php8.4-cli vendor/bin/drush.php state:set system.maintenance_mode 0
/usr/bin/php8.4-cli vendor/bin/drush.php cr

# PASO 16: Verificacion rapida
curl -s -o /dev/null -w "HTTP %{http_code}\n" https://plataformadeecosistemas.com/
```

### 3.3 Alternativa: Deploy Automatizado

```bash
# Usando el script de deploy existente (ejecuta pasos 4-15 automaticamente)
./scripts/deploy.sh
```

### 3.4 Comunicar al equipo

Enviar al canal de comunicacion:
```
DEPLOY T-0 COMPLETADO
Timestamp: [HORA]
Commit: [HASH]
Estado: Pendiente validacion
```

---

## 4. Fase 3: Validacion (T+0)

### 4.1 Ejecutar Validation Suite

**Tiempo**: Inmediatamente despues del deploy (5-10 minutos)

```bash
# Suite completa
./scripts/golive/02_validation_suite.sh --verbose

# O por vertical individual
./scripts/golive/02_validation_suite.sh --vertical=empleabilidad
./scripts/golive/02_validation_suite.sh --vertical=agroconecta
```

### 4.2 Verificaciones Manuales Criticas

| # | Verificacion | URL | Esperado | Estado |
|---|-------------|-----|----------|--------|
| 1 | Homepage carga | `/` | HTTP 200, contenido visible | [ ] |
| 2 | Login funciona | `/user/login` | Formulario visible | [ ] |
| 3 | Admin accesible | `/admin` | HTTP 200 (admin logged in) | [ ] |
| 4 | Registro vertical | `/registro/empleabilidad` | Formulario visible | [ ] |
| 5 | Marketplace | `/marketplace` | Productos visibles | [ ] |
| 6 | Stripe webhook | POST `/api/billing/stripe-webhook` | HTTP 200/400 | [ ] |
| 7 | Analytics API | `/api/v1/analytics/dashboard` | JSON response | [ ] |
| 8 | Skills API | `/api/v1/skills/search?q=test` | JSON response | [ ] |

### 4.3 Monitoreo de Dashboards

Abrir en pestanas separadas y monitorear durante los primeros 30 minutos:

- **Security Dashboard**: `/admin/seguridad` - Verificar que no hay alertas nuevas
- **Analytics Dashboard**: `/admin/jaraba/analytics` - Confirmar que eventos se registran
- **FinOps Dashboard**: `/admin/finops` - Verificar costes y metricas
- **Health Check**: `/admin/health` - Estado general de la plataforma

### 4.4 Revision de Logs

```bash
# Watchdog errors post-deploy
/usr/bin/php8.4-cli vendor/bin/drush.php watchdog:show --severity=error --count=20

# PHP error log
tail -50 /var/log/php-errors.log 2>/dev/null

# Apache error log
tail -50 /var/log/apache2/error.log 2>/dev/null
```

---

## 5. Fase 4: Decision Go/No-Go

### 5.1 Matriz de Criterios

La decision go/no-go se toma evaluando cada criterio. **Todos los criterios criticos deben pasar**.

| Criterio | Tipo | Pasa | Falla | Estado |
|----------|------|------|-------|--------|
| Homepage responde HTTP 200 | Critico | Continuar | Rollback | [ ] |
| Login/Registro funcional | Critico | Continuar | Rollback | [ ] |
| Base de datos operativa | Critico | Continuar | Rollback | [ ] |
| SSL certificado valido | Critico | Continuar | Rollback | [ ] |
| Stripe webhook responde | Critico | Continuar | Rollback | [ ] |
| 86 módulos habilitados | Critico | Continuar | Evaluar | [ ] |
| Preflight checks 0 FAIL | Critico | Continuar | Rollback | [ ] |
| Validation suite 0 FAIL | Critico | Continuar | Evaluar | [ ] |
| Qdrant conectividad | Alto | Continuar | Evaluar* | [ ] |
| Tiempo de respuesta < 3s | Alto | Continuar | Evaluar | [ ] |
| Watchdog sin errores criticos | Alto | Continuar | Evaluar | [ ] |
| Cache backend operativo | Medio | Continuar | Monitorear | [ ] |
| Cron ejecutandose | Medio | Continuar | Fix async | [ ] |
| Security headers presentes | Medio | Continuar | Fix async | [ ] |

*Qdrant: Si no esta disponible, la plataforma funciona sin IA/RAG. Evaluar si es aceptable temporalmente.

### 5.2 Arbol de Decision

```
PREFLIGHT + VALIDATION EJECUTADOS?
  |
  +-- NO --> Ejecutar antes de decidir
  |
  +-- SI --> Todos los CRITICOS pasan?
              |
              +-- NO --> ROLLBACK INMEDIATO
              |          ./scripts/golive/03_rollback.sh TIMESTAMP
              |
              +-- SI --> Algun ALTO falla?
                          |
                          +-- SI --> Se puede arreglar en < 30 min?
                          |          |
                          |          +-- SI --> Fix + re-validar
                          |          |
                          |          +-- NO --> ROLLBACK + planificar re-deploy
                          |
                          +-- NO --> GO-LIVE CONFIRMADO
```

### 5.3 Registro de Decision

```
DECISION GO-LIVE
Fecha y hora: _______________
Decidido por: _______________
Decision: [ ] GO  [ ] NO-GO  [ ] ROLLBACK
Motivo (si no-go): _______________
Siguiente accion: _______________
```

---

## 6. Fase 5: Soft Launch (T+1 a T+7)

### 6.1 Estrategia de Rollout Gradual

| Dia | Accion | Audiencia | Metricas a vigilar |
|-----|--------|-----------|-------------------|
| T+1 | Acceso interno del equipo | Equipo Jaraba (5-10 usuarios) | Errores, tiempo de carga |
| T+2 | Invitar beta testers | 10-20 usuarios seleccionados | Flujos de registro, UX |
| T+3 | Abrir vertical Empleabilidad | Candidatos y recruiters | Login, matching, skills |
| T+4 | Abrir vertical Emprendimiento | Emprendedores y mentores | Diagnostico, mentoring |
| T+5 | Abrir verticales Commerce | AgroConecta, ComercioConecta | Marketplace, pagos |
| T+6 | Abrir ServiciosConecta | Instituciones | Reservas, servicios |
| T+7 | Evaluacion completa | Todos los verticales | Dashboard global |

### 6.2 Monitorizacion Continua

**AIOps automatizado** (cada 5 minutos):
```bash
# Ya configurado via cron
*/5 * * * * ~/JarabaImpactPlatformSaaS/scripts/aiops-production.sh >> /var/log/jaraba-aiops.log 2>&1
```

**Metricas a monitorear diariamente**:

| Metrica | Umbral aceptable | Alerta |
|---------|------------------|--------|
| Tiempo de respuesta medio | < 2s | > 3s |
| Error rate | < 1% | > 5% |
| Uptime | > 99.5% | < 99% |
| Registros por dia | > 0 | 0 en 24h |
| Stripe webhooks procesados | > 0 | Fallos consecutivos |
| Cron ejecutandose | Cada hora | > 3h sin ejecucion |
| Disk usage | < 80% | > 85% |

### 6.3 Procedimiento de Quick Fixes

Para bugs no-criticos encontrados durante soft launch:

1. Documentar bug en issue tracker
2. Clasificar severidad: P0 (critico), P1 (alto), P2 (medio), P3 (bajo)
3. **P0**: Fix inmediato, deploy urgente
4. **P1**: Fix en < 24h, deploy en siguiente ventana
5. **P2-P3**: Backlog para siguiente sprint

```bash
# Deploy de hotfix (procedimiento rapido)
ssh usuario@servidor-ionos
cd ~/JarabaImpactPlatformSaaS
git fetch origin && git pull origin main
/usr/bin/php8.4-cli ~/bin/composer.phar install --no-dev --optimize-autoloader
/usr/bin/php8.4-cli vendor/bin/drush.php updb -y
/usr/bin/php8.4-cli vendor/bin/drush.php cr
```

### 6.4 Feedback Loop

- Encuesta de satisfaccion a beta testers (dia T+3)
- Revision de Watchdog diaria
- Reunion de standup diaria del equipo go-live (15 min)
- Reporte de metricas al final de cada dia

---

## 7. Fase 6: Public Launch (T+7+)

### 7.1 Prerrequisitos para Launch Publico

- [ ] Soft launch sin incidentes P0 durante 3 dias consecutivos
- [ ] Todas las metricas dentro de umbrales aceptables
- [ ] Feedback de beta testers incorporado
- [ ] Documentacion de soporte preparada
- [ ] Equipo de soporte briefado
- [ ] Paginas legales publicadas (Aviso Legal, Politica de Privacidad, Cookies)

### 7.2 Marketing y Comunicacion

| Accion | Responsable | Fecha | Estado |
|--------|-------------|-------|--------|
| Landing page de lanzamiento | Marketing | T+7 | [ ] |
| Email a lista de espera | Marketing | T+7 | [ ] |
| Post en redes sociales | Marketing | T+7 | [ ] |
| Nota de prensa (si aplica) | Comunicacion | T+7 | [ ] |
| Activacion de SEO/SEM | Marketing | T+8 | [ ] |
| Demo interactiva publica | Dev Team | T+7 | [ ] |

### 7.3 Preparacion de Soporte

- [ ] Documentacion FAQ publicada
- [ ] Canal de soporte (email/chat) operativo
- [ ] Guia de onboarding por vertical disponible
- [ ] Videos tutoriales grabados (al menos 1 por vertical)
- [ ] Equipo de soporte con acceso admin a la plataforma
- [ ] Procedimiento de escalacion documentado (seccion 10)

### 7.4 Capacidad y Escala

- [ ] Monitoreo de recursos del servidor IONOS activado
- [ ] Plan de escalamiento definido si el trafico supera expectativas
- [ ] CDN configurado para assets estaticos (si aplica)
- [ ] Rate limiting configurado (`/admin/config/system/rate-limits`)
- [ ] Caching agresivo habilitado para paginas anonimas

---

## 8. Roles y Responsabilidades

### 8.1 Matriz RACI

| Actividad | Project Lead | Dev Lead | DevOps | QA | Marketing |
|-----------|:---:|:---:|:---:|:---:|:---:|
| Decision Go/No-Go | **R/A** | C | C | I | I |
| Ejecutar deploy | I | C | **R** | I | I |
| Ejecutar preflight | I | **R** | A | C | I |
| Ejecutar validation | I | C | **R** | A | I |
| Ejecutar rollback | A | C | **R** | I | I |
| Monitoreo post-deploy | I | **R** | **R** | I | I |
| Comunicacion externa | A | I | I | I | **R** |
| Soporte usuarios | I | C | I | I | **R** |

**R** = Responsable, **A** = Accountable, **C** = Consultado, **I** = Informado

### 8.2 Turnos T-0

| Turno | Horario | Persona | Funcion |
|-------|---------|---------|---------|
| Deploy | 06:00 - 08:00 CET | DevOps Lead | Ejecutar deploy |
| Validacion | 08:00 - 10:00 CET | Dev Lead + QA | Tests y validacion |
| Monitoreo | 10:00 - 18:00 CET | Dev Team (rotacion) | Vigilancia activa |
| Guardia nocturna | 18:00 - 06:00 CET | DevOps Lead (on-call) | Respuesta emergencias |

---

## 9. Plan de Comunicacion

### 9.1 Canales

| Canal | Uso | Participantes |
|-------|-----|---------------|
| Slack #golive | Coordinacion en tiempo real | Todo el equipo |
| Slack #alertas | Alertas automaticas (AIOps) | DevOps + Dev Lead |
| Email | Comunicaciones formales | Stakeholders |
| WhatsApp (grupo) | Emergencias fuera de horario | On-call team |

### 9.2 Plantillas de Comunicacion

**Inicio de deploy**:
```
[GO-LIVE] Deploy INICIADO
Hora: HH:MM CET
Ejecutor: [nombre]
Downtime estimado: 10-15 minutos
Seguimiento en: #golive
```

**Deploy completado**:
```
[GO-LIVE] Deploy COMPLETADO
Hora: HH:MM CET
Commit: [hash]
Validation suite: PENDIENTE
Estado: En validacion
```

**Go-Live confirmado**:
```
[GO-LIVE] PLATAFORMA EN PRODUCCION
Hora: HH:MM CET
URL: https://plataformadeecosistemas.com
Todos los checks: PASS
Monitoreo activo
```

**Rollback ejecutado**:
```
[GO-LIVE] ROLLBACK EJECUTADO
Hora: HH:MM CET
Motivo: [descripcion]
Backup restaurado: [timestamp]
Proximo intento: [fecha estimada]
```

---

## 10. Procedimiento de Escalacion

### 10.1 Niveles de Escalacion

| Nivel | Criterio | Tiempo de respuesta | Quien actua |
|-------|----------|--------------------:|-------------|
| **L1** | Warning aislado, metrica fuera de umbral | 30 min | Dev on-call |
| **L2** | Error recurrente, funcionalidad degradada | 15 min | Dev Lead + DevOps |
| **L3** | Sitio caido, datos comprometidos, rollback | 5 min | Todo el equipo |

### 10.2 Flujo de Escalacion

```
INCIDENTE DETECTADO
  |
  +-- Severidad baja (L1)
  |   -> Dev on-call investiga
  |   -> Si resuelto en 30 min: cerrar
  |   -> Si no: escalar a L2
  |
  +-- Severidad media (L2)
  |   -> Dev Lead + DevOps coordinan
  |   -> Si resuelto en 15 min: cerrar
  |   -> Si no: escalar a L3
  |
  +-- Severidad alta (L3)
      -> Activar equipo completo
      -> Evaluar rollback inmediato
      -> Project Lead toma decision
      -> Ejecutar rollback si es necesario:
         ./scripts/golive/03_rollback.sh TIMESTAMP --force
```

---

## 11. Criterios de Exito

### 11.1 Metricas de Exito T-0

| Metrica | Objetivo | Minimo aceptable |
|---------|----------|------------------|
| Deploy sin errores criticos | 0 errores | 0 errores |
| Validation suite pass rate | 100% | > 95% |
| Tiempo de deploy total | < 15 min | < 30 min |
| Downtime durante deploy | < 5 min | < 15 min |

### 11.2 Metricas de Exito T+7

| Metrica | Objetivo | Minimo aceptable |
|---------|----------|------------------|
| Uptime | 99.9% | 99.5% |
| Tiempo de respuesta medio | < 1.5s | < 3s |
| Error rate | < 0.1% | < 1% |
| Registros completados | > 50 | > 10 |
| Incidentes P0 | 0 | 0 |
| Incidentes P1 | 0 | < 3 |
| NPS beta testers | > 7 | > 5 |

### 11.3 Metricas de Exito T+30

| Metrica | Objetivo | Minimo aceptable |
|---------|----------|------------------|
| Usuarios activos mensuales | > 200 | > 50 |
| Uptime acumulado | 99.9% | 99.5% |
| Stripe transacciones exitosas | > 90% | > 80% |
| Tiempo medio de primera respuesta soporte | < 4h | < 24h |
| Verticales completamente operativos | 5/5 | 3/5 |

---

## 12. Triggers de Rollback

### 12.1 Rollback Automatico (inmediato, sin consultar)

Estos escenarios requieren rollback inmediato sin esperar aprobacion:

- **Sitio completamente inaccesible** (HTTP 500/502/503 persistente > 5 min)
- **Base de datos corrupta** (queries SQL fallan consistentemente)
- **Perdida de datos de usuario** (cualquier evidencia de data loss)
- **Brecha de seguridad** (acceso no autorizado detectado)
- **Stripe webhooks fallan** y se procesan pagos duplicados o perdidos

### 12.2 Rollback Manual (consultar Project Lead)

Estos escenarios requieren evaluacion rapida (< 15 min) antes de decidir:

- Error rate > 5% sostenido durante > 10 minutos
- Tiempo de respuesta > 10s para el 50% de requests
- Mas de 3 funcionalidades criticas no operativas simultanteamente
- Errores de permisos que bloquean el acceso admin
- Config sync divergente que no se puede reconciliar

### 12.3 Procedimiento de Rollback

```bash
# 1. Ejecutar rollback automatizado
./scripts/golive/03_rollback.sh BACKUP_TIMESTAMP

# 2. Si se necesita revertir a un commit especifico
./scripts/golive/03_rollback.sh BACKUP_TIMESTAMP --commit=COMMIT_HASH

# 3. Rollback forzado (sin confirmacion interactiva)
./scripts/golive/03_rollback.sh BACKUP_TIMESTAMP --force

# 4. Verificar post-rollback
./scripts/golive/02_validation_suite.sh
```

### 12.4 Post-Rollback

1. Notificar a todo el equipo via canal de emergencia
2. Documentar la causa raiz del fallo
3. Crear plan de accion para resolver el problema
4. Definir fecha para re-intento de go-live
5. Ejecutar post-mortem dentro de las 48h siguientes

---

## 13. Checklist Maestro

### T-7: Pre-Go-Live
- [ ] Tests unitarios pasan
- [ ] 86 módulos verificados
- [ ] Integracion Stripe validada
- [ ] Integracion Qdrant validada
- [ ] 5 flujos de registro probados
- [ ] Backup completo creado
- [ ] Restauracion de backup probada
- [ ] Briefing de equipo completado
- [ ] Roles asignados
- [ ] Canal de emergencia creado
- [ ] SSL verificado
- [ ] DNS correcto
- [ ] Cron configurado
- [ ] Paginas legales listas

### T-0: Deploy
- [ ] Preflight checks: 0 FAIL
- [ ] Backup pre-deploy creado
- [ ] Deploy ejecutado
- [ ] settings.local.php verificado
- [ ] Validation suite: 0 FAIL
- [ ] Verificaciones manuales completadas
- [ ] Decision Go/No-Go tomada
- [ ] Equipo notificado

### T+1 a T+7: Soft Launch
- [ ] Monitoreo AIOps activo
- [ ] Beta testers invitados
- [ ] Vertical Empleabilidad abierto
- [ ] Vertical Emprendimiento abierto
- [ ] Verticales Commerce abiertos
- [ ] Vertical ServiciosConecta abierto
- [ ] Feedback recopilado
- [ ] Quick fixes desplegados
- [ ] Metricas T+7 evaluadas

### T+7+: Public Launch
- [ ] 3 dias sin P0
- [ ] Metricas dentro de umbral
- [ ] Documentacion de soporte lista
- [ ] Equipo de soporte briefado
- [ ] Marketing listo
- [ ] Launch publico ejecutado

---

## 14. Contactos de Emergencia

| Rol | Nombre | Telefono | Email | Disponibilidad |
|-----|--------|----------|-------|----------------|
| Project Lead | [Nombre] | [Telefono] | [Email] | 24/7 durante T-0 a T+7 |
| Dev Lead | [Nombre] | [Telefono] | [Email] | 08:00-22:00 CET |
| DevOps Lead | [Nombre] | [Telefono] | [Email] | On-call 24/7 |
| IONOS Soporte | - | - | soporte@ionos.es | 24/7 |
| Stripe Soporte | - | - | support@stripe.com | 24/7 |

### Servicios Externos

| Servicio | URL de Estado | Soporte |
|----------|---------------|---------|
| IONOS | https://status.ionos.com | Chat/Telefono |
| Stripe | https://status.stripe.com | Dashboard + Email |
| Qdrant Cloud (si aplica) | https://status.qdrant.io | Email |

---

*Documento generado para el equipo de Jaraba Impact Platform.*
*Revisar y actualizar antes de cada intento de go-live.*
