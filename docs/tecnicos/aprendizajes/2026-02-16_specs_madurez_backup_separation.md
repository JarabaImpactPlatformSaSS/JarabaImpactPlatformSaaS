# Aprendizajes: Specs Madurez N1/N2/N3 + Backup Separation

| Campo | Valor      |
|-------|------------|
| Fecha | 2026-02-16 |

---

## Patron Principal

La sesion documento 21 especificaciones tecnicas (docs 183-203) organizadas en 3 niveles de madurez plataforma (N1 Foundation, N2 Growth Ready, N3 Enterprise Class), con auditorias de readiness por nivel. Ademas se implemento la separacion de directorios de backup en IONOS (`~/backups/daily/` y `~/backups/pre_deploy/`) para facilitar la configuracion de GoodSync, incluyendo un paso de migracion one-time idempotente en GitHub Actions.

---

## Aprendizajes Clave

### 1. Organizacion de specs por niveles de madurez permite priorizar implementacion

**Situacion:** La plataforma tiene 21 documentos de especificacion tecnica que cubren areas muy diversas (legal, AI, mobile, security, infrastructure). Sin un esquema de organizacion, no queda claro que implementar primero.

**Aprendizaje:** Organizar los documentos en niveles de madurez (N1 Foundation → N2 Growth → N3 Enterprise) permite priorizar: N1 son requisitos legales/compliance que pueden bloquear contratos (GDPR DPA, Legal Terms, DR Plan), N2 son capacidades de crecimiento (AI agents, mobile, analytics), N3 son requisitos enterprise (SOC 2, ISO 27001, HA 99.99%). Las auditorias de readiness por nivel (docs 201-203) proporcionan un score objetivo de cuanto falta.

**Regla:** DOC-NIVEL-001: Especificaciones tecnicas de capacidades futuras DEBEN organizarse por nivel de madurez (N1/N2/N3) — cada nivel con su auditoria de readiness independiente.

### 2. Separacion de directorios de backup por tipo facilita sincronizacion selectiva

**Situacion:** Los backups automaticos diarios y los pre-deploy se guardaban en el mismo directorio `~/backups/`. Esto impedia configurar GoodSync para sincronizar solo los automaticos (los mas criticos para DR) sin incluir los pre-deploy.

**Aprendizaje:** Separar en `~/backups/daily/` y `~/backups/pre_deploy/` permite que herramientas de sincronizacion como GoodSync apunten selectivamente a un tipo. El cron de rotacion en `daily-backup.yml` maneja ambos directorios. El workflow `verify-backups.yml` busca en ambos subdirectorios con glob patterns.

**Regla:** BACKUP-003: Diferentes tipos de backup DEBEN almacenarse en subdirectorios separados — nunca mezclar automaticos con manuales o pre-deploy en el mismo directorio.

### 3. Migracion one-time idempotente via GitHub Actions para cambios de estructura en servidor

**Situacion:** Al cambiar la estructura de directorios de backup, los 78 backups existentes quedaban en la ubicacion antigua (`~/backups/` plano). No tenia acceso SSH directo al servidor.

**Aprendizaje:** Anadir un paso de migracion al inicio del workflow que sea idempotente: `mv ~/backups/db_daily_*.sql.gz ~/backups/daily/ 2>/dev/null` con `[ -e "$F" ] || continue`. En la primera ejecucion migra los archivos; en ejecuciones posteriores simplemente dice "No legacy backups to migrate". No requiere intervencion manual ni acceso SSH.

**Regla:** BACKUP-004: Cambios de estructura en directorios del servidor DEBEN incluir un paso de migracion idempotente en el workflow — nunca asumir que la estructura nueva ya existe.

### 4. Auditorias de readiness con score numerico objetivo son mas utiles que evaluaciones subjetivas

**Situacion:** Los 21 docs de especificacion necesitaban una evaluacion de viabilidad de implementacion inmediata.

**Aprendizaje:** Las auditorias de readiness (docs 201-203) con scores numericos (N1: NOT READY con 12 gaps, N2: 15.6%, N3: 10.4%) son mas accionables que evaluaciones cualitativas. Permiten tracking de progreso medible y priorizacion objetiva: si N1 no esta ready, no tiene sentido empezar N2.

**Regla:** DOC-NIVEL-002: Cada nivel de madurez DEBE tener una auditoria de readiness con score numerico — usar como gate de decision para iniciar implementacion del siguiente nivel.

---

## Metricas de la Sesion

| Metrica | Valor |
|---------|-------|
| Docs tecnicos nuevos registrados | 21 (183-203) |
| Auditorias readiness | 3 (N1, N2, N3) |
| Planes implementacion nuevos | 1 (Stack Fiscal v1) |
| Backups migrados | 78 |
| Workflows modificados | 3 (daily-backup, deploy, verify-backups) |
| Docs maestros actualizados | 3 (Arquitectura v33, Directrices v33, Indice v49) |
| Aprendizajes faltantes restaurados en indice | 6 |
| Reglas nuevas | BACKUP-003, BACKUP-004, DOC-NIVEL-001, DOC-NIVEL-002 |

---

## Patrones Reutilizables

| Patron | Origen | Reutilizado en |
|--------|--------|----------------|
| Migracion idempotente via workflow | daily-backup.yml | Cualquier cambio de estructura servidor |
| Organizacion por niveles N1/N2/N3 | Gap Analysis doc 182 | Seccion 7.4e del Indice General |
| Auditoria readiness con score | docs 201-203 | Gate de decision por nivel |
| Separacion directorios por tipo backup | GoodSync best practice | daily-backup.yml + verify-backups.yml |
