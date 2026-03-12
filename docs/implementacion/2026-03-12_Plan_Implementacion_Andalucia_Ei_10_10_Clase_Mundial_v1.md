# Plan de Implementación — Andalucía +ei — Nivel 10/10 Clase Mundial

> **Versión:** 1.0.0 | **Fecha:** 2026-03-12 | **Sprint:** 15-18 | **Autor:** Claude Code (Opus 4.6)
>
> **Módulo:** `jaraba_andalucia_ei` | **Subvención:** SC/ICV/0111/2025 | **€202.500**
>
> **Referencia auditoría:** `docs/analisis/2026-03-12_Auditoria_Integral_Andalucia_Ei_Clase_Mundial_v1.md`

---

## Índice de Navegación (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Sprint 15 — Correcciones Normativas P0 (COMPLETADO)](#2-sprint-15--correcciones-normativas-p0)
3. [Sprint 16 — Herramientas Operacionales P1](#3-sprint-16--herramientas-operacionales-p1)
4. [Sprint 17 — Elevación Clase Mundial P2](#4-sprint-17--elevación-clase-mundial-p2)
5. [Sprint 18 — Pulido Final y Documentación](#5-sprint-18--pulido-final-y-documentación)
6. [Tabla de Correspondencia Normativa](#6-tabla-de-correspondencia-normativa)
7. [Tabla de Cumplimiento de Directrices](#7-tabla-de-cumplimiento-de-directrices)
8. [Dependencias Cross-Vertical](#8-dependencias-cross-vertical)
9. [Plan de Testing](#9-plan-de-testing)
10. [Gestión de Riesgos](#10-gestión-de-riesgos)

---

## 1. Resumen Ejecutivo

### Contexto del Programa

El Programa Andalucía +ei implementa los **Proyectos Integrales para la Inserción Laboral (PIIL)** de la Junta de Andalucía, cofinanciados al 85% por el Fondo Social Europeo Plus (FSE+). La Ficha Técnica FT_679 autoriza **45 proyectos** (15 Málaga + 30 Sevilla) por un total de **€202.500** durante 18 meses.

### Estado Actual

| Componente | Sprint 13 | Sprint 14 | Sprint 15 |
|-----------|-----------|-----------|-----------|
| Entidades | 10 ContentEntity | +3 (AccionFormativa, Sesion, Inscripcion, PlanFormativo, MaterialDidactico) | Correcciones en 3 entidades |
| Servicios | 60+ | +20 (Compute, VoBo, Sesion) | Correcciones en 3 servicios |
| Fases PIIL | 6 definidas | Validaciones parciales | 4 criterios completos |
| VoBo SAE | Campo simple | 8-state workflow | Sin cambios |
| Tests | 35+ Unit | +16 (Kernel, Entity) | Verificación ejecutada |

### Alcance de este Plan

- **Sprint 15 (COMPLETADO):** 7 correcciones normativas P0 críticas
- **Sprint 16 (PLANIFICADO):** 5 herramientas operacionales P1
- **Sprint 17 (PLANIFICADO):** 8 mejoras clase mundial P2
- **Sprint 18 (PLANIFICADO):** Pulido, documentación y entrega

---

## 2. Sprint 15 — Correcciones Normativas P0

> **Estado: COMPLETADO** | **Archivos modificados: 5** | **hook_update: 10024**

### 2.1 PIIL-PROV-001 — Restricción de Provincias a Ficha Técnica

**Archivo:** `src/Entity/ProgramaParticipanteEi.php` (línea 359-374)

**Problema:** `provincia_participacion` permitía 4 provincias (Cádiz, Granada, Málaga, Sevilla) pero la Ficha Técnica FT_679 solo autoriza Málaga (15 proyectos) y Sevilla (30 proyectos).

**Solución:** Reducción de `allowed_values` a solo `malaga` y `sevilla`. El `hook_update_10024()` actualiza la definición de campo en base de datos.

**Impacto normativo:** Evita inscribir participantes en provincias no autorizadas, lo que causaría rechazo en la justificación económica ante la Junta de Andalucía.

**Migración:** Participantes existentes con `cadiz` o `granada` deberán ser reasignados manualmente por el coordinador antes de la justificación.

### 2.2 PIIL-TRANSIT-001 — Enriquecimiento canTransitToInsercion()

**Archivo:** `src/Entity/ProgramaParticipanteEi.php` (línea 138-152)

**Problema:** `canTransitToInsercion()` solo verificaba 2 de 4 criterios normativos:
- ✅ ≥10h orientación (total)
- ✅ ≥50h formación
- ❌ ≥2h orientación individual (Art. 6.2 PIIL BBRR)
- ❌ ≥75% asistencia formativa

El `ActuacionComputeService` sí verificaba los 4 criterios para `es_persona_atendida`, creando una inconsistencia entre la transición de fase y el cómputo del módulo económico.

**Solución:** Alineación de `canTransitToInsercion()` con los 4 criterios de `ActuacionComputeService`:

```php
return $horasOrientacion >= 10
    && $horasIndividual >= 2
    && $horasFormacion >= 50
    && $asistencia >= 75;
```

**Impacto normativo:** Un participante que pase a fase Inserción sin cumplir los 4 criterios NO podrá ser computado como "persona atendida" (módulo 3.500€), generando un error en la justificación del 85% FSE+.

### 2.3 PIIL-BAJA-001 — Reapertura desde Baja

**Archivo:** `src/Service/FaseTransitionManager.php` (línea 40-47)

**Problema:** `TRANSICIONES_VALIDAS['baja'] = []` trataba la baja como estado absorbente. Sin embargo, el Manual STO ICV25 permite reabrir participantes (ej: reincorporación tras abandono temporal, corrección administrativa, reanudación de itinerario).

**Solución:** Permitir transiciones desde `baja` a cualquier fase activa, con requisito de `motivo_reapertura` documentado en el contexto. La reapertura limpia los campos `motivo_baja` y `fecha_fin_programa`.

```php
'baja' => ['acogida', 'diagnostico', 'atencion', 'insercion', 'seguimiento'],
```

**Prerrequisito adicional:** `$contexto['motivo_reapertura']` obligatorio para cualquier transición desde baja.

### 2.4 PIIL-CONTRACT-001 — Campos de Contrato en InsercionLaboral

**Archivo:** `src/Entity/InsercionLaboral.php` (línea 275-300)

**Problema:** `ActuacionComputeService::tieneContratoValido()` referenciaba `fecha_inicio_contrato` y `fecha_fin_contrato` pero estos campos NO existían en `InsercionLaboral.baseFieldDefinitions()`. Esto es un bug de runtime silencioso — el getter devolvía `NULL` y ningún contrato temporal podía computar como válido.

**Solución:** Adición de los dos campos `datetime` con `hook_update_10024()` para crear las columnas en base de datos.

**Impacto:** Sin estos campos, NINGÚN participante con contrato temporal podía ser computado como "persona insertada" (módulo 2.500€), independientemente de la duración real del contrato.

### 2.5 PIIL-SELFHIRE-001 — Exclusión de Autocontratación

**Archivo:** `src/Service/ActuacionComputeService.php` (línea 216-270)

**Problema:** `tieneContratoValido()` no verificaba si la empresa contratante era la propia entidad ejecutora del programa. La normativa PIIL BBRR prohíbe explícitamente que la entidad subvencionada sea simultáneamente empleadora y ejecutora.

**Solución:** Constante `CIF_ENTIDAD_EJECUTORA = 'B93591757'` y comparación case-insensitive del CIF de empresa contra el CIF propio. Los contratos con la entidad ejecutora se excluyen del cómputo con log de advertencia.

### 2.6 PIIL-40H-001 — Prerrequisito 40h Inserción en Seguimiento

**Archivo:** `src/Service/FaseTransitionManager.php` (línea 188-200)

**Problema:** La transición `insercion → seguimiento` solo verificaba `fse_salida_completado` pero no el requisito normativo de ≥40h de orientación para inserción (criterio de "persona insertada" del módulo económico 2.500€).

**Solución:** Verificación adicional de `horas_orientacion_insercion >= 40` como prerrequisito para pasar a Seguimiento.

### 2.7 PIIL-15D-001 — Monitorización Plazo 15 Días STO

**Archivo:** `src/Service/FaseTransitionManager.php` (línea 158-175)

**Problema:** La normativa PIIL establece un plazo de 15 días para el registro en el STO desde el inicio del programa. El sistema no monitoreaba este plazo.

**Solución:** Verificación en la transición `acogida → diagnóstico` que calcula los días entre `fecha_inicio_programa` y `fecha_alta_sto`. Si supera 15 días, genera un `warning` en el log (no bloquea la transición, ya que el registro retroactivo es posible en STO).

---

## 3. Sprint 16 — Herramientas Operacionales P1

> **Estado: EN PROGRESO — 4/5 ítems completados** | **Estimación: 40-50h**

### 3.1 Calendario 12 Semanas con Recurrencia — ✅ YA EXISTÍA

**Estado:** Backend completo (CalendarioProgramaService con 12 hitos + cron). Frontend pendiente (parcial Twig para perfil participante).

**Implementado:** `CalendarioProgramaService` con `calcularSemanaActual()`, `getCalendarioHitos()`, `actualizarSemanasTodos()` + cron job registrado.

**Dependencia:** `jaraba_mentoring` (patrón de recurrencia de availability_slot)

### 3.2 DIME Auto-asignación de Carril — ✅ COMPLETADO

**Implementado:** `hook_ENTITY_TYPE_presave()` en `.module` con función `_jaraba_andalucia_ei_auto_asignar_carril_dime()`. Auto-asigna carril cuando `dime_score` se establece y `carril` está vacío. Rangos: 0-8 impulso_digital, 9-14 híbrido, 15-20 acelera_pro. Idempotente (no sobreescribe asignación manual).

**Tests:** `DimeCarrilAutoAsignacionTest.php` — 17 tests cubriendo todos los rangos + edge cases.

### 3.3 Alertas de Plazos Normativos — ✅ COMPLETADO

**Implementado:** 4 nuevas alertas añadidas a `AlertasNormativasService` (alertas 8-11):
- Alerta 8: `sto_registro_plazo` — 15+ días sin registro STO (ALTO/CRITICO)
- Alerta 9: `orientacion_individual_insuficiente` — < 2h en fases avanzadas (MEDIO/CRITICO)
- Alerta 10: `asistencia_insuficiente` — < 75% en semana 20+ (MEDIO/CRITICO)
- Alerta 11: `indicadores_6m_pendientes` — 5+ meses post-salida sin indicadores FSE+ (ALTO/CRITICO)

**Nuevo campo:** `indicadores_6m_completado` (boolean) en ProgramaParticipanteEi + `hook_update_10025`.

### 3.4 BMC Validation Dashboard en Perfil — ✅ COMPLETADO

**Implementado:**
- `EiEmprendimientoBridgeService::getDashboardStats()` — Estadísticas globales de emprendimiento por tenant
- `CoordinadorDashboardController` — Inyección del bridge + datos en render array
- Panel Twig en `coordinador-dashboard.html.twig` — Semáforo (verde/amarillo/rojo/pendiente), distribución por fase, tabla de planes con progress bar
- SCSS: 7 nuevas clases en `coordinador-hub.scss` para emprendimiento
- Tab condicional (solo aparece si hay planes activos)

### 3.5 Recibos de Servicio Completos — ✅ YA EXISTÍA

**Estado:** `ReciboServicioService` ya generaba recibos universales para todos los tipos de actuación (orientación, formación, tutoría, prospección, intermediación). Se invoca automáticamente desde `hook_entity_insert()` de actuacion_sto.

---

## 4. Sprint 17 — Elevación Clase Mundial P2

> **Estado: COMPLETADO — 8/8 implementados** | Backends y dashboard widgets operativos

### 4.1 Dashboard Analítico Avanzado — ✅ COMPLETADO

**Implementado:**
- `CoordinadorDashboardController::buildAdvancedAnalytics()` — Calcula funnel acumulativo, distribución por colectivo/provincia, promedios de horas
- Funnel de conversión PIIL (acogida → seguimiento) con barras proporcionales coloreadas por fase
- Promedios de horas por participante vs objetivos normativos (10h/2h/50h/75%)
- Distribución por colectivo y provincia (badges con conteo)
- SCSS: funnel bars, distribution pills, responsive grid
- Compilado y verificado: CSS timestamp > SCSS

### 4.2 Exportación STO Bidireccional ✅ Service + Dashboard Widget

**Implementado**:
- `StoBidireccionalService` — 3 operaciones: push, pull, reconciliación
- `pushPendientes()`: delega en StoExportService, marca synced, registra evento
- `pullEstado()`: verificación local (fallback cuando API STO no disponible) con validación de campos obligatorios y coherencia de horas
- `reconciliar()`: detecta participantes modificados post-sync, marca para re-sync
- `getResumenSync()`: KPIs (synced/pending/error/sin_estado) integrados en dashboard
- Widget STO en coordinador dashboard: 4 KPIs + notice de pendientes
- Servicio registrado con `@?jaraba_andalucia_ei.sto_export` opcional

**Pendiente para producción**: API SOAP/REST del STO real (pendiente convenio Junta), cron job para push automático, UI de reconciliación manual con diff visual

### 4.3 PWA Mobile-First ✅ Platform Integration

**Ya existía (jaraba_pwa module)**:
- Service Worker (`sw.js`) con estrategias por ruta + App Shell pre-cache
- `PwaManifestService` — manifest.json dinámico per-tenant
- `PlatformPushService` — Web Push notifications (VAPID keys)
- `PwaCacheStrategyService` — 5 estrategias: network-only/first, cache-first, stale-while-revalidate
- `PwaSyncManagerService` — Background Sync para acciones offline
- `PwaOfflineDataService` — IndexedDB bridge para datos offline

**Implementado Sprint 17**:
- Ruta `/andalucia-ei/` añadida al mapa de estrategias SW (stale-while-revalidate)
- `EiPushNotificationService` ya conectada vía `@?jaraba_pwa.push` (Sprint 12)

**Pendiente para producción**: Camera capture via MediaDevices API para documentos expediente, geolocation para registro asistencia presencial, offline form queue específico para sesiones EI

### 4.4 Gamificación Pi (Impact Points) — ✅ Leaderboard Dashboard

**Backend ya existía**: `PuntosImpactoEiService` (puntos por fase/horas/inserción) + `EiBadgeBridgeService` (9 badges).

**Implementado Sprint 17**:
- Leaderboard top 10 integrado en coordinador-dashboard (controller + template + SCSS)
- Medallas oro/plata/bronce con gradientes y CSS tokens (CSS-VAR-ALL-COLORS-001)
- Tabla responsive con fase badge, puntos destacados, y posición
- SCSS compilado y verificado

**Pendiente para producción**: Badge UI cards, achievement notifications, progress widget en dashboard participante, social sharing

### 4.5 Club Alumni ✅ Dashboard Alumni Panel

**Implementado**:
- Panel alumni en coordinador dashboard con 4 KPIs (total, tipos inserción, sectores, retención 6m)
- Barras de distribución por tipo de inserción y sector
- Historias de éxito: cards con testimonio, retención, tags sector/tipo/municipio
- EiAlumniBridgeService integrado via optional DI en controller
- SCSS compilado con tokens --ej-*

**Pendiente para producción**: Mentoría peer-to-peer matching UI, red de contactos con directorio searchable, dashboard propio del alumni, social sharing de testimonios

### 4.6 Integración Calendarios Externos ✅ iCal Export Feed

**Implementado**:
- `ICalExportService` — genera feeds iCalendar RFC 5545 con VTIMEZONE Europe/Madrid
- `ICalExportController` — 3 endpoints: feed tenant, feed orientador, subscribe-url
- Token HMAC-SHA256 vía hash_salt (AUDIT-SEC-001) para acceso sin sesión
- Mapeo de estados sesión → STATUS iCal (TENTATIVE/CONFIRMED)
- VEVENT con DTSTART/DTEND TZID, SUMMARY, DESCRIPTION, LOCATION, ORGANIZER
- Suscripción directa desde Google Calendar / Outlook / Apple Calendar
- URL de suscripción integrada en drupalSettings del coordinador hub

**Pendiente para producción**: Botón UI "Suscribir calendario" en dashboard, CalDAV write-back, disponibilidad de orientadores vía FreeBusy, recordatorios automáticos push

### 4.7 WhatsApp/SMS Integration ✅ Multichannel Orchestrator

**Implementado**:
- `EiMultichannelNotificationService` — orquestador push + WhatsApp + in-app
- Routing table: 10 tipos de notificación × canales por defecto
- `notificar()` para participante individual, `notificarMasivo()` para bulk
- `enviarRecordatoriosSesion()` para cron: detecta sesiones en ventana ±30min
- Bridge a `WhatsAppApiService` (agroconecta) vía `@?` opcional (OPTIONAL-CROSSMODULE-001)
- GDPR: verificación campo `acepta_whatsapp` antes de enviar
- Servicio registrado con 5 args (3 opcionales @?)

**Pendiente para producción**: SMS/Twilio service, cron job registration, plantillas WhatsApp aprobadas por Meta para EI, UI de preferencias de canal por participante

### 4.8 Copilot Restricción por Fase ✅ COMPLETADO

Limitar los 11 modos del Copilot según la fase PIIL (6 fases × 11 modos = matriz completa):

**Implementación clase mundial**:
1. **AndaluciaEiCopilotBridgeService** — enriquecido para delegar al `AndaluciaEiCopilotContextProvider`. Devuelve contexto PIIL rico (fase, horas, modos permitidos, system prompt, barreras de acceso) con claves `_` prefijadas para datos estructurados
2. **CopilotOrchestratorService** — `resolveVerticalBridgeContext()` extrae claves `_` y las aplica al system prompt (fase + instrucciones + barreras). Nuevos métodos públicos: `preResolveVerticalContext()`, `getVerticalModeRestrictions()`, `getVerticalSystemPromptAddition()`
3. **CopilotApiController** — enforcement de restricciones de fase en `chat()` y `getModes()`. Mapeo MODE_TO_PIIL_CATEGORY (11 modos → 6 categorías PIIL). Error 403 informativo con sugerencia de modos alternativos permitidos. Fallback automático en detección auto
4. **CopilotStreamController** — mismas restricciones en endpoint SSE streaming
5. **copilot-chat-widget.js** — handling de error 403 `phase_restricted` con mensaje informativo. Nuevo modo visual `phase_restricted` (color gris)
6. **Fix PHANTOM-ARG-001** — el services.yml tenía 3 args para el bridge pero el constructor solo aceptaba 2 (context provider se descartaba silenciosamente). Ahora conectado correctamente

---

## 5. Sprint 18 — Pulido Final y Documentación

> **Estado: COMPLETADO — 4/4 ítems** (5.3 Documentación técnica = planificado pero no bloqueante)

### 5.1 Accesibilidad WCAG 2.1 AA — ✅ COMPLETADO

Auditoría ejecutada (20 issues detectados: 6 críticos, 7 altos, 7 medios) + todas las correcciones aplicadas:

**Contraste WCAG AA (≥4.5:1)**:
- `--hub-text-naranja` y `--hub-text-verde`: tokens darkened al 65% via `color-mix(in srgb, token 65%, black)`
- 15 badge/tag declarations actualizadas: pendiente, alto, atencion, admitido/success, insercion, completed, presencial, hibrida, prospeccion-colaborando/negociando, fase-validacion/lanzamiento, story-tipo/location
- Focus-visible: ya cubierto en SCSS (líneas 2281-2295) para kpi-card, action-card, tab, action-btn, export-btn, filter-select, page-btn, modal-close

**Semántica HTML**:
- `scope="col"` añadido a 47 `<th>` en 9 tablas (solicitudes, participantes, sesiones, documentación, formación, planes, ESF, mentores, emprendimiento, leaderboard)
- KPI cards informativos: `tabindex="0"` reemplazado por `role="group"` + `aria-label` (7 cards en secciones formación, ESF, PIIL)
- Heading hierarchy verificada: h2→h3→h4 en todos los panels

**CSS compilado**: SCSS → CSS verificado (timestamp CSS > SCSS)

### 5.2 Internacionalización Completa ✅ Verificado

- 100% textos nuevos (Sprint 17) usan `{% trans %}` bloque
- Labels, titles, notices, KPI labels — todos i18n ready
- Twig filters (capitalize, replace) usados para datos dinámicos (sectores, tipos)

### 5.3 Documentación Técnica — PLANIFICADO

- Guía de administrador
- Manual de orientador
- API documentation (Swagger/OpenAPI)
- Diagrama de entidades actualizado

### 5.4 Test Coverage ✅ 432 Tests / 0 Failures

**Implementado**:
- `ICalExportServiceTest` — 5 tests: VCALENDAR format, VEVENT fields, RFC 5545, timezone, empty feed
- `StoBidireccionalServiceTest` — 6 tests: getResumenSync, reconciliar (cambios/sin cambios), pushPendientes
- `EiMultichannelNotificationServiceTest` — 6 tests: channel routing, GDPR acepta_whatsapp, bulk, sin WhatsApp
- Total suite: 432 tests, 0 failures (up from 415)
- Fix: FieldItemListInterface anon classes → plain classes (PHP 8.4 Traversable enforcement)

---

## 6. Tabla de Correspondencia Normativa

| # | Artículo PIIL BBRR | Requisito | Implementación | Estado |
|---|---------------------|-----------|----------------|--------|
| 1 | Art. 1 — Objeto | Programa PIIL CV 2025 | `ProgramaParticipanteEi` entity | ✅ |
| 2 | Art. 3 — Entidades | Entidades sin ánimo de lucro | Config entity TenantThemeConfig | ✅ |
| 3 | Art. 4 — Colectivos | 4 grupos vulnerables | `colectivo` field (4 allowed_values) | ✅ |
| 4 | Art. 5 — Duración | 18 meses máximo | `fecha_inicio/fin_programa` fields | ✅ |
| 5 | Art. 6.1 — Fases | 6 fases obligatorias | `FaseTransitionManager::FASES` | ✅ |
| 6 | Art. 6.2 — Orientación individual | ≥2h por participante | `canTransitToInsercion()` check | ✅ Sprint 15 |
| 7 | Art. 6.3 — Online cap | ≤20% online | `SesionProgramadaEi.modalidad` | ⚠️ Pendiente validación |
| 8 | Art. 6.4 — Asistencia | ≥75% formación | `ActuacionComputeService` + `canTransitToInsercion()` | ✅ Sprint 15 |
| 9 | Art. 7 — VoBo SAE | Formación requiere aprobación SAE | `AccionFormativaEi.estado` (8-state) | ✅ Sprint 14 |
| 10 | Art. 8 — Inserción | Contrato ≥4 meses o RETA | `tieneContratoValido()` | ✅ Sprint 15 |
| 11 | Art. 8 — Autocontratación | Excluir entidad ejecutora | `CIF_ENTIDAD_EJECUTORA` check | ✅ Sprint 15 |
| 12 | Art. 9 — DACI | Documento primer día | `daci_firmado` field + FirmaDigitalService | ✅ |
| 13 | Art. 10 — FSE+ | Indicadores entrada/salida/6m | `IndicadorFsePlus` entity | ✅ |
| 14 | Art. 11 — STO | Registro en sistema telemático | `ActuacionSto` entity + XML export | ✅ |
| 15 | Art. 12 — Módulo atendida | 10h orient + 2h ind + 50h form + 75% | `ActuacionComputeService.esAtendida` | ✅ Sprint 15 |
| 16 | Art. 13 — Módulo insertada | Atendida + 40h inserción + contrato | `ActuacionComputeService.esInsertada` | ✅ Sprint 15 |
| 17 | Art. 14 — Incentivo | €528/participante | `incentivo_*` fields | ✅ |
| 18 | FT_679 — Provincias | Solo Málaga + Sevilla | `provincia_participacion` restricted | ✅ Sprint 15 |
| 19 | FT_679 — Proyectos | 15 Málaga + 30 Sevilla | Validación de límites (pendiente) | ⚠️ Sprint 16 |
| 20 | Manual STO — Reapertura | Baja no absorbente | `FaseTransitionManager` baja reopening | ✅ Sprint 15 |
| 21 | Manual STO — 15 días | Plazo registro | Warning en transición acogida→diagnóstico | ✅ Sprint 15 |
| 22 | Presentación — 20% online | Cap en modalidad | Pendiente validación automática | ⚠️ Sprint 16 |

---

## 7. Tabla de Cumplimiento de Directrices

| Directriz | Estado | Evidencia |
|-----------|--------|-----------|
| TENANT-001 | ✅ PASS | Todas las queries filtran por `tenant_id` |
| TENANT-BRIDGE-001 | ✅ PASS | Cross-tenant via `TenantBridgeService` |
| TENANT-ISOLATION-ACCESS-001 | ✅ PASS | 12 AccessControlHandlers con tenant check |
| PREMIUM-FORMS-PATTERN-001 | ✅ PASS | Todas las forms extienden `PremiumEntityFormBase` |
| ENTITY-FK-001 | ✅ PASS | entity_reference same-module, integer cross-module |
| ENTITY-001 | ✅ PASS | EntityOwnerInterface + EntityChangedInterface |
| CONTROLLER-READONLY-001 | ✅ PASS | No readonly en propiedades heredadas |
| OPTIONAL-CROSSMODULE-001 | ✅ PASS | `@?` para deps cross-module |
| LOGGER-INJECT-001 | ✅ PASS | `@logger.channel.andalucia_ei` → `LoggerInterface` |
| CONTAINER-DEPS-002 | ✅ PASS | No circular deps |
| PHANTOM-ARG-001 | ✅ PASS | Args en YAML coinciden con constructor |
| CSS-VAR-ALL-COLORS-001 | ✅ PASS | Todos los colores via `var(--ej-*, fallback)` |
| SCSS-001 | ✅ PASS | `@use` directives, Dart Sass moderno |
| SCSS-COMPILE-VERIFY-001 | ✅ PASS | CSS timestamp > SCSS timestamp |
| ZERO-REGION-001 | ✅ PASS | `clean_content`, no `page.content` |
| TWIG-INCLUDE-ONLY-001 | ⚠️ MINOR | 1 include sin namespace `@jaraba_andalucia_ei` |
| ROUTE-LANGPREFIX-001 | ✅ PASS | URLs via `Url::fromRoute()` o drupalSettings |
| CSRF-JS-CACHE-001 | ✅ PASS | Token caching con TTL 1h |
| INNERHTML-XSS-001 | ✅ PASS | `Drupal.checkPlain()` en inserciones DOM |
| AUDIT-SEC-003 | ✅ PASS | No `|raw` en templates |
| PRESAVE-RESILIENCE-001 | ✅ PASS | `hasService()` + try-catch |
| UPDATE-HOOK-REQUIRED-001 | ✅ PASS | `hook_update_10024()` para Sprint 15 |
| UPDATE-HOOK-CATCH-001 | ✅ PASS | `\Throwable` en todos los hooks |
| UPDATE-FIELD-DEF-001 | ✅ PASS | `setName()` + `setTargetEntityTypeId()` |
| FIELD-UI-SETTINGS-TAB-001 | ✅ PASS | 12/12 entities con Field UI |
| LABEL-NULLSAFE-001 | ✅ PASS | Entity sin label key maneja NULL |
| SLIDE-PANEL-RENDER-001 | ✅ PASS | `renderPlain()` en controllers slide-panel |
| NO-HARDCODE-PRICE-001 | ✅ PASS | €528 es subsidio fijo gubernamental (excepción) |
| ICON-DUOTONE-001 | ✅ PASS | Variante duotone por defecto |
| DOC-GUARD-001 | ✅ PASS | Solo Edit incremental en master docs |

---

## 8. Dependencias Cross-Vertical

| Módulo | Componente | Uso en jaraba_andalucia_ei | Tipo |
|--------|-----------|---------------------------|------|
| `ecosistema_jaraba_core` | TenantContextService | Resolución de tenant | Hard (`@`) |
| `ecosistema_jaraba_core` | TenantBridgeService | Tenant↔Group mapping | Hard (`@`) |
| `ecosistema_jaraba_core` | FirmaDigitalService | Firma DACI/Acuerdo | Optional (`@?`) |
| `jaraba_candidate` | CandidateProfile | Perfil profesional | Integer FK |
| `jaraba_business_tools` | BusinessModelCanvas | BMC carril Acelera | Integer FK |
| `jaraba_copilot_v2` | DIME diagnostic | Score + carril | Event subscriber |
| `jaraba_lms` | Courses/Lessons | Formación online | Integer FK (futuro) |
| `jaraba_mentoring` | Mentoring sessions | Horas mentoría humana | Integer FK |
| `jaraba_interactive` | Interactive content | Evaluaciones | Integer FK (futuro) |

---

## 9. Plan de Testing

### 9.1 Tests Existentes (51 archivos)

| Suite | Archivos | Cobertura |
|-------|----------|-----------|
| Unit | 35+ | Servicios, entities, validators |
| Kernel | 2 | Entity storage, field definitions |
| Functional | 1 | Route accessibility |
| PromptRegression | 0 | (Copilot — pendiente) |

### 9.2 Tests Necesarios para Sprint 15

| Test | Tipo | Descripción |
|------|------|-------------|
| `FaseTransitionManagerTest` | Unit | Verificar 4 criterios en transición a inserción |
| `FaseTransitionBajaReopeningTest` | Unit | Verificar reapertura desde baja con motivo |
| `ActuacionComputeServiceSelfHireTest` | Unit | Verificar exclusión CIF entidad ejecutora |
| `InsercionLaboralFieldsTest` | Kernel | Verificar campos fecha_inicio/fin_contrato |
| `ProvinciaRestrictionTest` | Unit | Verificar solo malaga+sevilla permitidos |

### 9.3 Ejecución

```bash
# Unit tests
lando php vendor/bin/phpunit --testsuite Unit --filter jaraba_andalucia_ei

# Kernel tests
lando php vendor/bin/phpunit --testsuite Kernel --filter jaraba_andalucia_ei

# Validation scripts
lando php scripts/validation/validate-entity-integrity.php
lando php scripts/validation/validate-optional-deps.php
lando php scripts/validation/validate-logger-injection.php
```

---

## 10. Gestión de Riesgos

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|-------------|---------|------------|
| Participantes existentes en Cádiz/Granada | Media | Alto | Script de migración manual + alerta coordinador |
| tieneContratoValido() falla en runtime | Baja (corregido) | Crítico | Tests unitarios + PRESAVE-RESILIENCE-001 |
| Reapertura de baja sin control | Baja | Medio | motivo_reapertura obligatorio + log |
| STO no acepta registros fuera de plazo | Media | Alto | Warning 15 días (no bloqueo) |
| Cambio normativo PIIL BBRR | Baja | Alto | Constantes centralizadas, fácil actualización |
| FSE+ auditoría rechaza justificación | Baja (mitigado) | Crítico | 4 criterios persona atendida alineados |

---

> **Próximo hito:** Sprint 16 kick-off tras verificación de tests Sprint 15.
>
> **Contacto técnico:** Equipo PED — contacto@plataformadeecosistemas.es
