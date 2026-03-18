# Auditoria: Documento Maestro Consolidado v2 vs Estado Real del Codebase
# Fecha: 2026-03-18 | Version: 1.0
# Documento auditado: docs/tecnicos/20260318b-Documento_Maestro_Consolidado_Jaraba_v2_Claude.md
# Metodo: Cruce sistematico contra codebase, CLAUDE.md v1.5.4, memory files, CI/CD pipelines, git history
# Autor: Claude Opus 4.6 (1M context) — auditoria autonoma

---

## RESUMEN EJECUTIVO

El Documento Maestro Consolidado v2 es un documento estrategico-tecnico de 563 lineas que consolida el estado de la Jaraba Impact Platform a fecha 18 marzo 2026. La auditoria revela un documento **estrategicamente solido** (vision, pricing, posicionamiento bien articulados) pero con **7 errores factuales**, **4 imprecisiones** y **7 omisiones relevantes** en las secciones tecnicas.

**Puntuacion global: 7.6/10**

| Aspecto | Puntuacion | Notas |
|---------|-----------|-------|
| Precision estrategica | 9/10 | Vision, pricing, posicionamiento excelentes |
| Precision tecnica | 6/10 | 3 errores criticos, 1 servicio fantasma |
| Completitud | 7/10 | 7 omisiones, falta evolucion aprendizajes #183-#191 |
| Coherencia interna | 8/10 | 1 discrepancia numerica, FOC sobreestimado |
| Alineacion con CLAUDE.md | 8/10 | Reglas principales correctas, faltan 2 reglas recientes |

---

## 1. ERRORES FACTUALES (7 hallazgos)

Errores que contradicen directamente la evidencia del codebase o la infraestructura verificada.

### E1 — Stack de produccion: Docker Compose, NO nativo (CRITICO)

**Seccion afectada:** 6.1 (Capa 6 — Infraestructura)

**Claim del documento (linea 235):**
> Stack nativo Ubuntu (NO Docker en produccion, excepto Tika).

**Evidencia real:**
- `.github/workflows/deploy-production.yml` usa Docker Buildx para build y `docker compose up -d web-$DEPLOY_TO` para deployment
- Patron blue-green con contenedores `web-blue` / `web-green` gestionados via `docker compose`
- Nginx como reverse proxy hace upstream switch entre contenedores

**Correccion:** Produccion usa Docker Compose con estrategia blue-green. Los servicios de datos (MariaDB, Redis) pueden estar nativos, pero el aplicativo Drupal corre en contenedores Docker.

---

### E2 — Meilisearch no existe en el proyecto (ALTO)

**Seccion afectada:** 6.1 (Capa 5 — DevOps)

**Claim del documento (linea 237):**
> Lando (.lando.yml) con 7 servicios (appserver, database, redis, qdrant, tika, **meilisearch**, mailhog)

**Evidencia real:**
- `.lando.yml` define 7 servicios: appserver, database, **phpmyadmin**, redis, qdrant, tika, mailhog
- Busqueda exhaustiva: cero referencias a "meilisearch" en todo el repositorio (composer.json, *.yml, *.php, *.js)

**Correccion:** Reemplazar "meilisearch" por "phpmyadmin". Meilisearch no forma parte del stack tecnologico de la plataforma.

---

### E3 — MariaDB en CI es 10.11, no 11.2 (MEDIO)

**Seccion afectada:** 6.1 nota final

**Claim del documento (linea 247):**
> Misma nota para MariaDB: produccion = 10.11, CI services usan mariadb:11.2 (posible desalineacion).

**Evidencia real:**
- `.github/workflows/ci.yml` lineas 123 y 202: `image: mariadb:10.11`
- CI y produccion estan **alineados** en MariaDB 10.11

**Correccion:** Eliminar la nota sobre desalineacion MariaDB. CI usa 10.11, igual que produccion. No hay inconsistencia.

---

### E4 — PIIL gaps: 4 de 8 ya implementados, no "Pendiente" (CRITICO)

**Seccion afectada:** 4.3 (Gaps criticos PIIL)

**Claim del documento (lineas 176-186):**
> GAP-01 a GAP-08 todos marcados como "Pendiente" o "Parcial"

**Evidencia real verificada en el codebase:**

| Gap | Claim doc | Estado real | Evidencia |
|-----|-----------|-------------|-----------|
| GAP-01 (STO sync) | Pendiente | **Implementado** | `StoExportService.php` — genera paquetes XML/SOAP, sync participantes |
| GAP-02 (Horas 50/50) | Pendiente | **Implementado** | `HumanMentorshipTracker.php` + `AiMentorshipTracker.php` con `getResumenHoras()` |
| GAP-03 (Transicion fases) | Pendiente | **Implementado** | `FaseTransitionManager.php` — 6 fases canonicas, mapa de transiciones validas, validacion normativa |
| GAP-04 (Entity participante) | Pendiente | **Implementado** | `ProgramaParticipanteEi.php` — ContentEntity completa con 6 fases y campos STO |
| GAP-05 (25h Mentoria IA) | Pendiente | **Parcial/Implementado** | `AiMentorshipTracker.php` trackea horas IA, campo `horas_mentoria_ia` en entity |
| GAP-06 (Recibi incentivo) | Pendiente | No verificado | Campo `incentivo_recibido` existe en entity |
| GAP-07 (jaraba_learning 100h) | Parcial | Parcial | jaraba_lms con H5P + xAPI, no especifico "100h" |
| GAP-08 (Club Alumni) | Pendiente | No verificado | — |

**Correccion:** Actualizar GAP-01 a GAP-05 como "Implementado". El documento subestima significativamente el avance de Andalucia +ei.

---

### E5 — SEPE SOAP esta implementado, no "Pendiente" (CRITICO)

**Seccion afectada:** 5.2, 5.4

**Claim del documento (linea 220):**
> Web Service SOAP SEPE | PENDIENTE (jaraba_sepe_teleformacion)

**Evidencia real:**
- Modulo existe: `web/modules/custom/jaraba_sepe_teleformacion/`
- `SepeSoapService.php` implementa las 6 operaciones SOAP del Anexo V (ObtenerDatosCentro, CrearAccion, ObtenerListaAcciones, ObtenerDatosAccion, ObtenerParticipantes, ObtenerSeguimiento)
- `SepeDataMapper.php` mapea entidades Drupal a payloads XML SEPE
- `SepeSoapController.php` expone los endpoints

**Correccion:** Cambiar estado a "Implementado". Las 6 operaciones SOAP estan codificadas y el modulo existe con estructura completa.

---

### E6 — FOC es 1 modulo, no 5 (ALTO)

**Seccion afectada:** 7.4

**Claim del documento (linea 333):**
> jaraba_foc + jaraba_foc_entities + jaraba_foc_etl + jaraba_foc_metrics + jaraba_foc_forecasting

**Evidencia real:**
- Solo existe `web/modules/custom/jaraba_foc/` (32 archivos PHP en src/)
- Los directorios `jaraba_foc_entities`, `jaraba_foc_etl`, `jaraba_foc_metrics`, `jaraba_foc_forecasting` **NO existen**

**Correccion:** Reescribir como "jaraba_foc (Financial Operations Center)" sin listar submodulos inexistentes. Las funcionalidades (entities, ETL, metrics, forecasting) pueden estar dentro de jaraba_foc como servicios, pero no como modulos independientes.

---

### E7 — Son 9 Marketing Add-ons, no 8 (MEDIO)

**Seccion afectada:** 3.2 titulo

**Claim del documento (linea 113):**
> 3.2 Marketing Add-ons (9 items, precio fijo, vertical-independiente)

**Nota:** El titulo de la seccion dice "9 items" correctamente, pero la linea 56 del mismo documento dice "8 Marketing Add-ons". Doc 158 (source of truth) lista 9 add-ons.

**Correccion:** Unificar en "9 Marketing Add-ons" en todas las menciones.

---

## 2. IMPRECISIONES (4 hallazgos)

Afirmaciones que no son falsas pero carecen de precision o inducen a confusion.

### I1 — SCORM no existe, solo xAPI + H5P

**Seccion afectada:** 5.4

**Claim:** "LMS con tracking SCORM/xAPI: Cumple (jaraba_lms + H5P)"

**Realidad:** jaraba_lms implementa **xAPI** (XApiService + XapiController) y soporta contenido **H5P** (campo h5p_content_id, tipo TYPE_H5P). No hay implementacion SCORM en el codebase.

**Correccion:** Cambiar a "LMS con tracking xAPI + H5P: Cumple".

---

### I2 — Conteo CSS vars --ej-* impreciso

**Seccion afectada:** 11.4

**Claim:** "46+ CSS vars --ej-*"

**Realidad:** CLAUDE.md dice "35+ variables configurables". Las variables --ej-* son **runtime-injected** via `hook_preprocess_html()` → `<style>:root { --ej-* }</style>`, no pre-definidas en SCSS. El conteo exacto depende del tenant y sus configuraciones activas. Las variables SCSS ($ej-*) son ~100+, pero no son lo mismo que CSS custom properties.

**Correccion:** Clarificar: "35+ CSS custom properties --ej-* inyectadas en runtime (configurables por tenant), mas 100+ variables SCSS de compilacion".

---

### I3 — Qdrant Cloud vs self-hosted sin clarificar

**Seccion afectada:** 6.1 (Capa 6), 15 (Servicios externos)

**Claim:** "Qdrant Cloud (~25 USD/mes)" sin distincion entorno

**Realidad:** Dev (Lando) usa Qdrant Docker self-hosted (gratis, `qdrant/qdrant:latest`). Solo produccion usa Qdrant Cloud.

**Correccion:** Anadir nota: "Qdrant Cloud (~25 USD/mes) en produccion. Self-hosted Docker en desarrollo local."

---

### I4 — "202 bloques" GrapesJS no verificable

**Seccion afectada:** 6.1 (Capa 2), 9

**Claim:** "GrapesJS 5.7 (202 bloques, 24 categorias)"

**Realidad:** Los bloques GrapesJS se definen en JS plugins (no como Drupal Block plugins en PHP). No se encontro un registro o conteo verificable de "202" en el codebase. El master doc DIRECTRICES v141 tambien cita esta cifra, por lo que es la cifra oficial, pero no hay forma directa de validarla contra el codigo.

**Correccion:** Mantener la cifra pero anadir "(segun registro del Template Registry SSOT)".

---

## 3. OMISIONES RELEVANTES (7 hallazgos)

Informacion que deberia estar en un documento consolidado de esta naturaleza.

### O1 — STRIPE_WEBHOOK_SECRET no documentado

**Seccion afectada:** 10, 12.3

El documento menciona Stripe keys (public/secret) pero omite `STRIPE_WEBHOOK_SECRET`, variable critica para la verificacion HMAC de webhooks (AUDIT-SEC-001). Es un secret de produccion que debe estar en settings.secrets.php.

---

### O2 — PHANTOM-ARG-001 v2 (bidireccional)

**Seccion afectada:** 11.3

El documento menciona PHANTOM-ARG-001 pero no documenta la evolucion a v2 (aprendizaje #191):
- Deteccion bidireccional: args de MAS (phantom) Y de MENOS (missing)
- Missing es mas peligroso que phantom (`$container->has()` devuelve TRUE pero `get()` lanza TypeError transitivo)
- 12 tests de regresion en `tests/test-phantom-args-parser.php`
- 13 servicios descubiertos con args faltantes en v2

---

### O3 — CSRF-LOGIN-FIX-001 v2 no documentado

**Seccion afectada:** 12.3 (Cadena de seguridad)

Fix critico de produccion ausente:
- IONOS termina SSL; Apache/PHP recibe HTTP
- `SessionConfiguration.php:55` overrides `cookie_secure` con `$request->isSecure()`
- Si isSecure() es inconsistente → session perdida → CSRF falla
- Fix: `$_SERVER['HTTPS']='on'` desde X-Forwarded-Proto ANTES del bootstrap Drupal
- `patch-settings-csrf.php` se ejecuta en cada deploy

---

### O4 — Copilot Bridges (aprendizaje #183)

**Seccion afectada:** 7 (Stack IA)

Faltan los bridge services que conectan los copilots verticales con el orquestador:
- `DemoCopilotBridgeService`: bridge para vertical demo
- `LegalCopilotBridgeService`: bridge para jarabalex
- COPILOT-BRIDGE-COVERAGE-001 y STREAMING-PARITY-001 como reglas

---

### O5 — ZEIGARNIK-PRELOAD-001

**Seccion afectada:** 6.4 (Setup Wizard)

Patron psicologico clave omitido:
- 2 auto-complete global steps (`__global__` wizard ID) inyectados en TODOS los wizards
- Los wizards arrancan al 25-33% de completion (efecto Zeigarnik: +12-28% tasa de finalizacion)
- Implementado via tagged services con wizard_id = `__global__`

---

### O6 — ACCESS-RETURN-TYPE-001

**Seccion afectada:** 11.3 (Directrices P0)

Regla critica para PHPStan omitida:
- `checkAccess()` DEBE declarar `: AccessResultInterface` (NO `: AccessResult`)
- `parent::checkAccess()` devuelve `AccessResultInterface`; return type mas restrictivo causa error PHPStan
- 68 AccessControlHandlers migrados en commit `f09dbaa95`

---

### O7 — SAFEGUARD-CANVAS-001

**Seccion afectada:** 9 (Page Builder)

Proteccion de 4 capas para el canvas GrapesJS omitida:
- Capa 1: Backup automatico pre-save (canvas_data snapshot)
- Capa 2: Restore point via revision history
- Capa 3: Presave validation (JSON schema check)
- Capa 4: Post-save verification (rendered output matches)

---

## 4. SECCIONES VERIFICADAS CORRECTAS

Las siguientes secciones fueron auditadas y resultan **correctas y bien documentadas:**

| Seccion | Contenido | Verificacion |
|---------|-----------|--------------|
| 1. Resumen Ejecutivo | Metricas, indicadores Q1 2026 | Correcto — cifras alineadas con codebase |
| 2.1 Vision | Propuesta valor dual, diferenciadores | Correcto — 8 diferenciadores verificados |
| 2.2 Triple Motor | Mix institucional/mercado/licencias | Correcto — modelo estrategico coherente |
| 2.3 Escaleras de valor | Impulso Digital + Legado Jaraba | Correcto — bien articulado |
| 2.4 Submarinas con Periscopio | Sub-branding por vertical | Correcto — patron verificado en Domain entities |
| 2.5 Avatares | 7 personas tipo, AvatarWizardBridgeService | Correcto — service verificado en codebase |
| 3.1 Pricing | Tabla Doc 158, regla de oro #131 | Correcto — alineado con SSOT |
| 3.3 Bundles | 3 bundles con descuentos | Correcto — cifras de Doc 158 |
| 3.4 Verticales composables | TenantVerticalService, addon billing | Correcto — arquitectura verificada |
| 6.2 Multi-tenancy | TenantBridge, 4 reglas cardinales | Correcto — alineado con CLAUDE.md |
| 6.3 Estado por vertical | Tabla madurez 10 verticales | Correcto — entity counts verificados |
| 7.1 Agentes Gen 2 | 11 agentes, tabla propositos | Correcto — clases verificadas |
| 7.2 Servicios IA | 16 servicios listados | Correcto — todos existen en codebase |
| 7.3 LCIS | 9 capas, EU AI Act | Correcto — documentado en memory |
| 7.4 Copilot v2 | 5 modos, 44 experimentos Osterwalder | **Verificado** — ExperimentLibraryService confirma 44 |
| 8. Credentials | Open Badges 3.0, Ed25519 | **Verificado** — CryptographyService con sodium_crypto_sign |
| 10. PLG | 11 componentes operativos | Correcto — servicios verificados |
| 11.1-11.10 Directrices | Stack, patrones, reglas | Correcto en lo fundamental |
| 12. CI/CD | 8 workflows, 6 capas safeguard | Correcto — 2.803 LOC verificado |
| 13. Inventario modulos | 94 modulos, 7 categorias | **Verificado** — conteo correcto |
| 15. Servicios externos | 9 proveedores con costes | Correcto |

---

## 5. ANALISIS RAIZ DE LAS DISCREPANCIAS

### Por que ocurren los errores E4 y E5 (PIIL/SEPE como "Pendiente")

El documento consolida informacion de multiples fuentes (docs anteriores, specs tecnicas, analisis previos). Los gaps PIIL y SEPE se definieron en documentos de analisis de febrero-marzo 2026 (antes de la implementacion), y el documento consolidado copio esos estados sin verificar contra el codebase actual. **El codebase evoluciona mas rapido que la documentacion.**

### Por que ocurre el error E1 (Docker vs nativo)

Posible confusion entre la **intencion original** (stack nativo) y la **implementacion real** (Docker Compose para blue-green). El deploy-production.yml evoluciono hacia Docker despues de que se redactaron las specs iniciales de infraestructura.

### Por que ocurre el error E2 (Meilisearch)

Meilisearch aparece en algunos documentos de especificacion como componente planificado (posiblemente para Search API). Nunca se implemento — el stack de busqueda usa Search API con Drupal DB backend + Qdrant para busqueda semantica.

---

## 6. PLAN DE CORRECCIONES RECOMENDADO

### Prioridad 1 — Criticos (corregir inmediatamente)

| # | Accion | Seccion | Impacto |
|---|--------|---------|---------|
| 1 | Reescribir Capa 6: produccion usa Docker Compose blue-green | 6.1 | Evita confusion operativa |
| 2 | Actualizar PIIL gaps: GAP-01 a GAP-05 como "Implementado" | 4.3 | Evita replanificacion innecesaria |
| 3 | Actualizar SEPE SOAP como "Implementado" | 5.2, 5.4 | Evita doble trabajo |

### Prioridad 2 — Altos (corregir antes de compartir)

| # | Accion | Seccion |
|---|--------|---------|
| 4 | Reemplazar "meilisearch" por "phpmyadmin" | 6.1 |
| 5 | FOC: 1 modulo (jaraba_foc), no 5 submodulos | 7.4 |
| 6 | Unificar "9 Marketing Add-ons" | 3.2 titulo, otras menciones |

### Prioridad 3 — Medios (mejorar precision)

| # | Accion | Seccion |
|---|--------|---------|
| 7 | Eliminar nota MariaDB CI 11.2 | 6.1 nota |
| 8 | SCORM → xAPI + H5P | 5.4 |
| 9 | Clarificar CSS vars (35+ runtime, 100+ SCSS) | 11.4 |
| 10 | Qdrant: Cloud produccion, Docker dev | 6.1, 15 |
| 11 | "202 bloques (segun Template Registry SSOT)" | 9 |

### Prioridad 4 — Incorporar omisiones

| # | Contenido a anadir | Seccion destino |
|---|-------------------|----------------|
| 12 | STRIPE_WEBHOOK_SECRET en variables | 10 o 12 |
| 13 | PHANTOM-ARG-001 v2 bidireccional | 11.3 |
| 14 | CSRF-LOGIN-FIX-001 v2 (patch-settings-csrf.php) | 12.3 |
| 15 | Copilot Bridges (Demo, Legal) | 7 |
| 16 | ZEIGARNIK-PRELOAD-001 (auto-complete global) | 6.4 |
| 17 | ACCESS-RETURN-TYPE-001 (68 handlers) | 11.3 |
| 18 | SAFEGUARD-CANVAS-001 (4-layer protection) | 9 |

---

## 7. VALORACION FINAL

El Documento Maestro Consolidado v2 es un **buen documento estrategico** que captura correctamente la vision, pricing, posicionamiento y la mayor parte de la arquitectura tecnica. Su principal debilidad es la **desincronizacion con el codebase en componentes que evolucionaron despues de las specs iniciales** (PIIL, SEPE, Docker production, FOC).

**Recomendacion:** Aplicar las 18 correcciones listadas antes de usar el documento como referencia externa o para toma de decisiones. Las correcciones criticas (1-3) afectan la percepcion del estado de avance del proyecto — el documento subestima el progreso real de Andalucia +ei y SEPE.

---

*Auditoria realizada el 2026-03-18 por Claude Opus 4.6 (1M context).*
*Metodo: Cruce exhaustivo contra codebase (94 modulos, 1.131 servicios), CLAUDE.md v1.5.4, 18 memory files, 8 CI/CD workflows, git history (653 commits).*
*Documento auditado: docs/tecnicos/20260318b-Documento_Maestro_Consolidado_Jaraba_v2_Claude.md (563 lineas, 15 secciones).*
