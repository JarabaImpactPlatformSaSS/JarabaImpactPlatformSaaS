# Plan de Implementacion: Diseno del Programa Formativo Andalucia +ei — Sprint 13

**Fecha de creacion:** 2026-03-11
**Ultima actualizacion:** 2026-03-11
**Autor:** IA Asistente (Claude Opus 4.6)
**Version:** 2.0.0
**Estado:** Planificado
**Categoria:** Implementacion Integral / Entidades / Servicios / Frontend / IA / ESF+ Compliance / UX Inclusivo
**Modulos afectados:** `jaraba_andalucia_ei`, `ecosistema_jaraba_theme`, `ecosistema_jaraba_core`, `jaraba_billing`
**Documento fuente:** `docs/analisis/2026-03-11_Auditoria_Andalucia_Ei_Diseno_Programa_Formativo_Sprint13_v1.md`
**Especificacion referencia:** Orden 29/09/2023 BBRR PIIL, Manual STO ICV25, Manual Operativo V2.1, Contenido Formativo 1a Edicion, Reglamento ESF+ (UE) 2021/1057, WCAG 2.2 AA, Ley Europea Accesibilidad (EAA)
**Prioridad:** P0 (normativo VoBo SAE + ESF+ compliance) + P1 (operativo calendarizacion/inscripcion + UX inclusivo) + P2 (elevacion IA nativa + outcomes)
**Directrices de aplicacion:** TENANT-001, TENANT-BRIDGE-001, TENANT-ISOLATION-ACCESS-001, PREMIUM-FORMS-PATTERN-001, ENTITY-FK-001, ENTITY-PREPROCESS-001, ACCESS-RETURN-TYPE-001, CONTROLLER-READONLY-001, UPDATE-HOOK-REQUIRED-001, UPDATE-HOOK-CATCH-001, OPTIONAL-CROSSMODULE-001, CONTAINER-DEPS-002, LOGGER-INJECT-001, PHANTOM-ARG-001, ZERO-REGION-001, ZERO-REGION-002, ZERO-REGION-003, SLIDE-PANEL-RENDER-001, FORM-CACHE-001, CSS-VAR-ALL-COLORS-001, SCSS-COMPILE-VERIFY-001, SCSS-ENTRY-CONSOLIDATION-001, SCSS-COLORMIX-001, SCSS-COMPILETIME-001, SCSS-001, ICON-CONVENTION-001, ICON-DUOTONE-001, ICON-COLOR-001, ICON-CANVAS-INLINE-001, ICON-EMOJI-001, TWIG-INCLUDE-ONLY-001, TWIG-URL-RENDER-ARRAY-001, ROUTE-LANGPREFIX-001, CSRF-API-001, CSRF-JS-CACHE-001, API-WHITELIST-001, INNERHTML-XSS-001, FIELD-UI-SETTINGS-TAB-001, LABEL-NULLSAFE-001, PRESAVE-RESILIENCE-001, AUDIT-CONS-001, KERNEL-TEST-DEPS-001, MOCK-DYNPROP-001, DOC-GUARD-001, RUNTIME-VERIFY-001, IMPLEMENTATION-CHECKLIST-001, NO-HARDCODE-PRICE-001, DOMAIN-ROUTE-CACHE-001
**Benchmarks de referencia:** Bonterra Apricot (case management), Unite Us (closed-loop referrals), Lightcast (competency taxonomy), Degreed (LXP), WIOA Performance Indicators (outcome measurement)
**Esfuerzo estimado:** 120-150 horas (16 fases en 1 sprint)
**Relacion con plan anterior:** Extiende `2026-03-10_Plan_Implementacion_Andalucia_Ei_Cumplimiento_Integral_PIIL_v2.md` anadiendo la capa de diseno del programa que faltaba (Sprint 13). v2.0 incorpora auditoria de clase mundial: correcciones tecnicas P0, ESF+ compliance, UX inclusivo para colectivos vulnerables, outcomes measurement, y vertical temporal

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
   - 1.1 [Que se implementa](#11-que-se-implementa)
   - 1.2 [Por que se implementa](#12-por-que-se-implementa)
   - 1.3 [Alcance y exclusiones](#13-alcance-y-exclusiones)
   - 1.4 [Filosofia de implementacion](#14-filosofia-de-implementacion)
   - 1.5 [Estimacion de esfuerzo por fase](#15-estimacion-de-esfuerzo-por-fase)
2. [Diagnostico del Estado Actual](#2-diagnostico-del-estado-actual)
   - 2.1 [Lo que ya existe y funciona](#21-lo-que-ya-existe-y-funciona)
   - 2.2 [Brechas identificadas](#22-brechas-identificadas)
   - 2.3 [Dependencias entre brechas](#23-dependencias-entre-brechas)
   - 2.4 [Servicios cross-vertical disponibles](#24-servicios-cross-vertical-disponibles)
3. [Arquitectura Objetivo](#3-arquitectura-objetivo)
   - 3.1 [Modelo de datos ampliado](#31-modelo-de-datos-ampliado)
   - 3.2 [Entidad AccionFormativaEi](#32-entidad-accionformativaei)
   - 3.3 [Entidad SesionProgramadaEi — Tabla completa de campos](#33-entidad-sesionprogramadaei)
   - 3.4 [Entidad InscripcionSesionEi — Tabla completa de campos](#34-entidad-inscripcionsesionei)
   - 3.5 [Entidad PlanFormativoEi](#35-entidad-planformativoei)
   - 3.6 [Arquitectura de servicios](#36-arquitectura-de-servicios)
   - 3.7 [Mapa de rutas](#37-mapa-de-rutas)
   - 3.8 [Diagrama de flujo del coordinador](#38-diagrama-de-flujo-del-coordinador)
   - 3.9 [Diagrama de flujo de la participante](#39-diagrama-de-flujo-de-la-participante)
   - 3.10 [Decisiones arquitectonicas explicitas](#310-decisiones-arquitectonicas)
   - 3.11 [Permisos granulares por rol](#311-permisos-granulares)
4. [Integracion Cross-Vertical](#4-integracion-cross-vertical)
   - 4.1 [Reutilizacion de jaraba_lms](#41-reutilizacion-de-jaraba_lms)
   - 4.2 [Patron de jaraba_mentoring](#42-patron-de-jaraba_mentoring)
   - 4.3 [Patron de jaraba_sepe_teleformacion](#43-patron-de-jaraba_sepe_teleformacion)
   - 4.4 [Integracion con jaraba_legal_calendar](#44-integracion-con-jaraba_legal_calendar)
   - 4.5 [Andalucia +ei como Vertical Temporal: Estrategia de Acceso Completo](#45-andalucia-ei-como-vertical-temporal)
   - 4.6 [Inventario de herramientas a habilitar por carril](#46-inventario-herramientas-por-carril)
   - 4.7 [Arquitectura tecnica del acceso por programa](#47-arquitectura-tecnica-acceso-programa)
5. [Workflow VoBo SAE](#5-workflow-vobo-sae)
   - 5.1 [Maquina de estados](#51-maquina-de-estados)
   - 5.2 [Generacion de documentacion](#52-generacion-de-documentacion)
   - 5.3 [Alertas y escalamiento](#53-alertas-y-escalamiento)
6. [Integracion IA Nativa](#6-integracion-ia-nativa)
   - 6.1 [IA para el coordinador](#61-ia-para-el-coordinador)
   - 6.2 [IA para la participante](#62-ia-para-la-participante)
   - 6.3 [IA para la justificacion](#63-ia-para-la-justificacion)
6b. [Compliance ESF+ e Indicadores de Impacto](#6b-compliance-esf)
   - 6b.1 [Indicadores comunes ESF+ nativos](#6b1-indicadores-esf)
   - 6b.2 [Plan Individual de Insercion (PII) digital](#6b2-pii-digital)
   - 6b.3 [Seguimiento a 6 meses post-programa](#6b3-seguimiento-6-meses)
   - 6b.4 [Dashboard de resultados para financiador](#6b4-dashboard-financiador)
6c. [UX Inclusivo para Colectivos Vulnerables](#6c-ux-inclusivo)
   - 6c.1 [Lenguaje claro (nivel B1/A2)](#6c1-lenguaje-claro)
   - 6c.2 [Ayuda contextual en formularios](#6c2-ayuda-contextual)
   - 6c.3 [Validacion en tiempo real con refuerzo positivo](#6c3-validacion-rt)
   - 6c.4 [Mobile-first y touch-friendly](#6c4-mobile-first)
   - 6c.5 [Preferencias de accesibilidad del usuario](#6c5-preferencias-a11y)
   - 6c.6 [Confirmacion y deshacer](#6c6-confirmacion-deshacer)
7. [Arquitectura Frontend y Templates](#7-arquitectura-frontend-y-templates)
   - 7.1 [Templates Twig nuevos y modificados](#71-templates-twig-nuevos-y-modificados)
   - 7.2 [Parciales reutilizables](#72-parciales-reutilizables)
   - 7.3 [SCSS y pipeline de compilacion](#73-scss-y-pipeline-de-compilacion)
   - 7.4 [Variables CSS inyectables](#74-variables-css-inyectables)
   - 7.5 [Iconografia](#75-iconografia)
   - 7.6 [Accesibilidad WCAG 2.2 AA](#76-accesibilidad-wcag-22-aa)
   - 7.7 [Slide-panel para CRUD](#77-slide-panel-para-crud)
   - 7.8 [Internacionalizacion](#78-internacionalizacion)
8. [Navegacion Admin y Field UI](#8-navegacion-admin-y-field-ui)
   - 8.1 [Rutas admin/structure](#81-rutas-adminstructure)
   - 8.2 [Rutas admin/content](#82-rutas-admincontent)
   - 8.3 [Field UI base routes](#83-field-ui-base-routes)
   - 8.4 [Links de menu, task y action](#84-links-de-menu-task-y-action)
9. [Fases de Implementacion](#9-fases-de-implementacion)
   - 9.1 [Fase 1: Entidad AccionFormativaEi](#91-fase-1)
   - 9.2 [Fase 2: VoboSaeWorkflowService](#92-fase-2)
   - 9.3 [Fase 3: Entidad PlanFormativoEi](#93-fase-3)
   - 9.4 [Fase 4: Entidad SesionProgramadaEi](#94-fase-4)
   - 9.5 [Fase 5: Entidad InscripcionSesionEi](#95-fase-5)
   - 9.6 [Fase 6: Servicios de negocio](#96-fase-6)
   - 9.6b [Fase 6b: Acceso Vertical Temporal](#96b-fase-6b)
   - 9.7 [Fase 7: Integracion CoordinadorHub](#97-fase-7)
   - 9.8 [Fase 8: Portal participante](#98-fase-8)
   - 9.9 [Fase 9: Alertas normativas extendidas](#99-fase-9)
   - 9.10 [Fase 10: Integracion IA](#910-fase-10)
   - 9.11 [Fase 11: Tests](#911-fase-11)
   - 9.12 [Fase 12: Verificacion runtime](#912-fase-12)
10. [Tabla de Correspondencia: Especificaciones Tecnicas](#10-tabla-de-correspondencia)
11. [Tabla de Cumplimiento de Directrices](#11-tabla-de-cumplimiento-de-directrices)
12. [Inventario Completo de Ficheros](#12-inventario-completo-de-ficheros)
13. [Verificacion y Testing](#13-verificacion-y-testing)
   - 13.1 [Tests automatizados por fase](#131-tests-automatizados)
   - 13.2 [Checklist RUNTIME-VERIFY-001](#132-checklist-runtime-verify)
   - 13.3 [Checklist IMPLEMENTATION-CHECKLIST-001](#133-checklist-implementation)
   - 13.4 [Validacion con scripts](#134-validacion-con-scripts)
14. [Riesgos y Mitigaciones](#14-riesgos-y-mitigaciones)
15. [Referencias](#15-referencias)
16. [Registro de Cambios](#16-registro-de-cambios)

---

## 1. Resumen Ejecutivo

### 1.1 Que se implementa

Plan integral de **12 fases en 1 sprint** para dotar al modulo `jaraba_andalucia_ei` de la **capa de diseno del programa formativo** que actualmente falta. Esta capa permite al Coordinador definir en que consisten las 50h de formacion, calendarizar sesiones, gestionar inscripciones de participantes, y orquestar el workflow completo de VoBo (Visto Bueno) del Servicio Andaluz de Empleo.

**Ambito:**

1. **4 nuevas ContentEntities** (P0): `AccionFormativaEi`, `SesionProgramadaEi`, `InscripcionSesionEi`, `PlanFormativoEi` — cada una con AccessControlHandler, PremiumEntityFormBase, hook_update_N(), Views data, Field UI, template_preprocess
2. **5 nuevos servicios** (P0/P1): `AccionFormativaService`, `VoboSaeWorkflowService`, `SesionProgramadaService`, `InscripcionSesionService`, `ProgramaVerticalAccessService`
3. **Workflow VoBo SAE completo** (P0): Maquina de 8 estados con generacion automatica de documentacion, tracking de fechas, alertas por timeout, ciclo de subsanacion
4. **Acceso Vertical Temporal** (P0): Andalucia +ei como "vertical temporal" — las participantes acceden a TODAS las herramientas de empleabilidad y emprendimiento segun su carril (Impulso Digital / Acelera Pro / Hibrido) durante la vigencia del programa, sin necesidad de addons de pago. Post-programa: conversion a cliente de pago con datos migrados
5. **Calendarizacion de sesiones** (P1): Soporte de recurrencia (JSON patron), expansion automatica, control de plazas, sincronizacion con Google Calendar/Outlook
6. **Inscripcion de participantes** (P1): Self-service desde portal, generacion automatica de `ActuacionSto` al confirmar asistencia, computo de horas integrado con sistema existente
7. **Integracion IA nativa** (P2): Generacion de esquemas formativos, recomendacion de sesiones, evaluacion de competencias, documentacion VoBo asistida por IA
8. **Frontend limpio** (P1): 6+ templates Twig zero-region, 5+ parciales reutilizables, SCSS con tokens `--ej-*`, slide-panel para CRUD, mobile-first, banner de conversion
9. **Navegacion admin completa** (P1): 4 entidades en /admin/structure (Field UI) + /admin/content (colecciones), con pestanas, acciones y links de menu

**Cifras:**

| Componente | Cantidad |
|-----------|----------|
| Nuevas ContentEntities | 4 |
| Nuevos campos (total 4 entidades) | ~95 |
| Nuevos servicios | 6 (4 dominio + 1 acceso vertical + 1 indicadores ESF+) |
| Extensiones a servicios existentes | 7 (incluye FeatureAccessService) |
| Nuevas rutas (admin + API + frontend) | ~38 (incluye dashboard financiador) |
| Nuevos permisos | ~16 (granulares por rol) |
| Templates Twig nuevos/modificados | ~13 (incluye dashboard financiador + PII) |
| Parciales Twig nuevos | ~7 (incluye _programa-expiration-banner, _pii-digital-summary) |
| Ficheros SCSS nuevos | 2 (route-specific + _ux-inclusivo) |
| Ficheros JS nuevos | 1 (validacion RT con refuerzo positivo) |
| Tests Unit + Kernel | ~22+ (incluye ESF+, PII, WCAG) |
| hook_update_N() | 4 (uno por entidad) |
| hook_requirements() | Extensiones a existente |

### 1.2 Por que se implementa

**Riesgo normativo directo:**
- Sin VoBo SAE aprobado, las acciones formativas **no son legalmente validas** y no pueden computarse para la justificacion economica (€3.500 por persona atendida). En la 1a edicion, el SAE corrigio recibos de formacion en junio 2025 y hubo multiples incidencias de validacion en STO (agosto-septiembre 2025). Un workflow digital completo con trazabilidad elimina este riesgo.

**Riesgo operativo:**
- La 1a edicion se gestiono con Excel + Word + Email (14.000+ archivos, 43GB). Esto es insostenible para la 2a edicion con potencialmente mas participantes y mayor exigencia de trazabilidad FSE+.
- El Coordinador actualmente no puede responder "¿en que consisten las 50h de formacion?" desde la plataforma.
- Las participantes no pueden inscribirse en sesiones ni ver su calendario.
- No existe automatizacion entre "la participante asistio a la sesion" → "se registra la actuacion" → "se incrementan sus horas" → "se genera la hoja de servicio para firma".

**Oportunidad de diferenciacion:**
- El programa es mixto (empleabilidad + emprendimiento). La plataforma refleja esta dualidad con carriles diferenciados (Impulso Digital / Acelera Pro / Hibrido) y planes formativos por carril.
- La IA nativa transforma la experiencia: el Coordinador recibe sugerencias al disenar acciones formativas, las participantes tienen tutor IA sobre contenidos, la justificacion se genera asistida por IA.

### 1.3 Alcance y exclusiones

**INCLUIDO:**

- 4 nuevas ContentEntities con `PremiumEntityFormBase`, `AccessControlHandler` con tenant isolation, Views data, Field UI con `field_ui_base_route`, templates con `template_preprocess_{type}()`, routing completo
- Consumo de servicios de 6 modulos cross-vertical via `@?` opcional: `jaraba_lms`, `jaraba_mentoring`, `jaraba_interactive`, `jaraba_legal_calendar`, `jaraba_pwa`, `jaraba_support`
- Workflow VoBo SAE con 8 estados, generacion de documentacion PDF, alertas por timeout
- Calendarizacion con recurrencia JSON (patron simplificado, no iCalendar completo)
- Inscripcion self-service con generacion automatica de `ActuacionSto`
- Templates Twig zero-region para nuevas vistas del coordinador y portal participante
- 1 fichero SCSS route-specific (`coordinador-programa.scss`) compilado con Dart Sass moderno
- Todos los textos de interfaz con `{% trans %}` bloques (NUNCA filtro `|t`)
- Todas las variables CSS con prefijo `--ej-*` y fallbacks (CSS-VAR-ALL-COLORS-001)
- Iconos via `jaraba_icon()` con variante duotone y colores de paleta Jaraba
- Slide-panel para crear/editar acciones formativas y sesiones desde el hub del coordinador
- hook_update_N() para cada nueva entidad con `\Throwable` en try-catch
- 30+ tests PHPUnit (Unit + Kernel)
- Validacion con scripts del proyecto (validate-all.sh)

**EXCLUIDO:**

- Conversion de contenidos PPTX de la 1a edicion a lecciones LMS (fase posterior)
- Integracion SOAP con STO (ya existe `StoExportService`, no se modifica)
- Nuevas funcionalidades de firma electronica (se reutiliza `FirmaWorkflowService` existente)
- Cambios en el sistema de expediente documental (se reutiliza `ExpedienteService`)
- Creacion de nuevos agentes IA Gen 2 (se usa Copilot existente con contexto enriquecido)
- Modificaciones al tema base `ecosistema_jaraba_theme` (salvo SCSS route-specific y parciales)

### 1.4 Filosofia de implementacion

> **Orquestar lo existente, crear solo lo especifico del PIIL.**

El ecosistema Jaraba ya tiene LMS, mentoring, eventos, calendario, firma electronica, expediente, IA. Este sprint NO recrea estos modulos — los referencia via FKs opcionales (ENTITY-FK-001) y servicios `@?` (OPTIONAL-CROSSMODULE-001).

Las 4 nuevas entidades son **especificas del dominio PIIL**: accion formativa (con VoBo SAE), sesion programada (con control de plazas), inscripcion (con generacion automatica de actuacion), plan formativo (con composicion por carril). Estas entidades no existen en ningun otro modulo del ecosistema porque su logica de negocio es exclusiva del programa de insercion laboral.

### 1.5 Estimacion de esfuerzo por fase

| Fase | Descripcion | Prioridad | Horas | Objetivo |
|------|-------------|-----------|-------|----------|
| 1 | Entidad AccionFormativaEi (con revision tables para audit trail VoBo) | P0 | 8-10 | Coordinador define modulos formativos |
| 2 | VoboSaeWorkflowService | P0 | 8-10 | Ciclo completo VoBo con documentacion |
| 3 | Entidad PlanFormativoEi | P1 | 6-8 | Composicion de acciones por carril |
| 4 | Entidad SesionProgramadaEi (tabla completa 25 campos) | P1 | 8-10 | Calendarizacion con recurrencia |
| 5 | Entidad InscripcionSesionEi (tabla completa 15 campos) | P1 | 8-10 | Inscripcion + generacion ActuacionSto |
| 6 | Servicios de negocio | P1 | 8-10 | AccionFormativaService, SesionProgramadaService, InscripcionSesionService |
| 6b | **Acceso Vertical Temporal** | **P0** | **6-8** | **ProgramaVerticalAccessService + integracion FeatureAccess + metricas** |
| 6c | **Compliance ESF+ e Indicadores** | **P0** | **8-10** | **14 indicadores output + 6 resultado + PII digital + seguimiento 6 meses** |
| 6d | **UX Inclusivo Vulnerables** | **P1** | **8-10** | **Lenguaje claro + ayuda contextual + validacion RT + mobile-first + a11y prefs** |
| 7 | Integracion CoordinadorHub | P1 | 6-8 | Pestanas "Plan Formativo" + "Calendario" + Dashboard financiador |
| 8 | Portal participante | P1 | 6-8 | "Mis Sesiones" + herramientas cross-vertical + banner expiracion + next-step CTA |
| 9 | Alertas normativas extendidas | P1 | 4-5 | VoBo timeout, horas insuficientes, ESF+ deadlines |
| 10 | Integracion IA | P2 | 6-8 | Generacion esquemas, recomendaciones, revisora IA VoBo |
| 11 | Tests | P0 | 8-10 | Unit + Kernel para 4 entidades, 6 servicios, indicadores ESF+ |
| 12 | Verificacion runtime + WCAG 2.2 AA | P0 | 6-8 | RUNTIME-VERIFY-001 + audit accesibilidad completo |
| **Total** | | | **120-150** | |

---

## 2. Diagnostico del Estado Actual

### 2.1 Lo que ya existe y funciona

| Componente | Estado | Observaciones |
|-----------|--------|---------------|
| `ProgramaParticipanteEi` (45 campos) | Funcional | Entidad central, tracking de horas, fases, incentivos |
| `ActuacionSto` (22 campos) | Funcional | Registro post-hoc de actuaciones. Campo `vobo_sae_status` existe (4 valores) pero sin workflow |
| `FaseTransitionManager` | Funcional | 6 fases con prerequisitos. Valida: diagnostico requiere Acuerdo+DACI, insercion requiere 10h orient+50h form |
| `CalendarioProgramaService` | Funcional | 12 hitos en 52 semanas. Hardcoded pero funcional |
| `FirmaWorkflowService` | Funcional | 8 estados, 3 metodos (tactil, autofirma, sello), audit trail inmutable |
| `ExpedienteService` | Funcional | 37 categorias documentales, vault integration |
| `CoordinadorHubService` | Funcional | 5 pestanas (Solicitudes, Participantes, Sesiones, Documentacion, Alertas) |
| `AlertasNormativasService` | Funcional | Alertas FSE+, training sin VoBo, horas insuficientes |
| `ActuacionStoService` | Funcional | CRUD actuaciones + `incrementarHorasParticipante()` |
| `AdaptacionItinerarioService` | Funcional | 8 barreras, 20+ adaptaciones por tipo |
| `AndaluciaEiCopilotContextProvider` | Funcional | Contexto participante para Copilot IA |
| Template `page--andalucia-ei.html.twig` | Funcional | Zero-region con `clean_content`, `_header.html.twig`, `_footer.html.twig` |
| SCSS `routes/coordinador-hub.scss` | Funcional | 44KB, estilos del hub coordinador |

### 2.2 Brechas identificadas

| ID | Descripcion | Prioridad | Riesgo | Fase |
|----|-------------|-----------|--------|------|
| GAP-PROG-01 | No existe entidad para acciones formativas del programa | P0 | Normativo: sin definicion formal de formacion | 1 |
| GAP-PROG-02 | No existe workflow VoBo SAE real (solo campo sin maquina de estados) | P0 | Normativo: sin VoBo no hay formacion legal | 2 |
| GAP-PROG-03 | No existe plan formativo por carril (50h no desglosadas) | P1 | Operativo: cada carril sin contenido definido | 3 |
| GAP-PROG-04 | No existe calendarizacion de sesiones futuras | P1 | Operativo: participantes no saben cuando hay sesiones | 4 |
| GAP-PROG-05 | No existe inscripcion en sesiones | P1 | Operativo: no hay control de plazas ni self-service | 5 |
| GAP-PROG-06 | Las actuaciones se registran post-hoc manualmente | P1 | Operativo: propenso a errores, sin automatizacion | 5 |
| GAP-PROG-07 | Portal participante no muestra sesiones futuras | P1 | UX: participante no ve su calendario | 8 |
| GAP-PROG-08 | No hay recordatorios automaticos de sesiones | P2 | UX: baja asistencia | 8 |
| GAP-PROG-09 | Sin IA para disenar contenidos formativos | P2 | Diferenciacion: manual vs. asistido | 10 |
| GAP-PROG-10 | Sin vinculacion con cursos LMS | P2 | Contenidos no reutilizables digitalmente | 1 |

### 2.3 Dependencias entre brechas

```
GAP-PROG-01 (AccionFormativa) ──┬──> GAP-PROG-02 (VoBo SAE) ── requiere accion definida
                                 ├──> GAP-PROG-03 (PlanFormativo) ── compone acciones
                                 └──> GAP-PROG-04 (SesionProgramada) ── sesiones de una accion
                                         │
                                         └──> GAP-PROG-05 (Inscripcion) ── participante se inscribe
                                                │
                                                ├──> GAP-PROG-06 (ActuacionSto auto) ── generada al asistir
                                                └──> GAP-PROG-07 (Portal) ── muestra sesiones + inscripcion
                                                         │
                                                         └──> GAP-PROG-08 (Recordatorios) ── push/email
```

### 2.4 Servicios cross-vertical disponibles

| Modulo | Servicio/Entidad | Uso en Sprint 13 | Patron de Integracion |
|--------|-----------------|-------------------|----------------------|
| `jaraba_lms` | `lms_course`, `EnrollmentService` | Vincular accion formativa a curso online | `course_id` integer FK (ENTITY-FK-001) |
| `jaraba_mentoring` | `availability_slot` | Patron de recurrencia para sesiones | Solo referencia arquitectonica |
| `jaraba_sepe_teleformacion` | `SepeAccionFormativa` | Patron de workflow con SAE | Solo referencia arquitectonica |
| `jaraba_interactive` | `interactive_content` | Evaluaciones vinculadas a acciones | `interactive_content_id` integer FK |
| `jaraba_legal_calendar` | `CalendarConnection` | Sincronizacion Google Calendar | `@?jaraba_legal_calendar.calendar_sync` |
| `jaraba_pwa` | Push notifications | Recordatorios de sesiones | `@?jaraba_pwa.push` |

---

## 3. Arquitectura Objetivo

### 3.1 Modelo de datos ampliado

```
                      ┌───────────────────────────┐
                      │    PlanFormativoEi         │
                      │  (plantilla por carril)    │
                      │  carril: impulso_digital   │
                      │  acciones_formativas: [N]  │
                      └───────────┬───────────────┘
                                  │ multi-value entity_reference
                    ┌─────────────┼─────────────────────┐
                    │             │                       │
            ┌───────▼──────┐ ┌───▼──────────┐ ┌─────────▼──────┐
            │AccionForm. A │ │AccionForm. B │ │AccionForm. C   │
            │20h, online   │ │15h, presenc. │ │15h, mixta      │
            │vobo: aprobado│ │vobo: pend.   │ │vobo: borrador  │
            │course_id: 42 │ │              │ │                │
            └──────┬───────┘ └──────────────┘ └────────────────┘
                   │ accion_formativa_id (optional)
        ┌──────────┼──────────────────┐
        │          │                   │
 ┌──────▼───────┐ ┌▼──────────────┐ ┌─▼──────────────┐
 │SesionProg. 1 │ │SesionProg. 2  │ │SesionProg. 3   │
 │Lun 10:00     │ │Mie 16:00     │ │Vie 09:00       │
 │individual    │ │grupal        │ │formacion       │
 │plazas: 1     │ │plazas: 12    │ │plazas: 15      │
 └──────┬───────┘ └──────┬───────┘ └──────┬──────────┘
        │                 │                │
 ┌──────▼───────┐ ┌──────▼───────┐ ┌──────▼──────────┐
 │Inscripcion 1 │ │Inscripcion 2 │ │Inscripcion 3    │
 │Ana: asistio  │ │Ana: inscrita │ │Pedro: inscrito  │
 │→ ActuacionSto│ │              │ │                 │
 │→ +1.5h orient│ │              │ │                 │
 └──────────────┘ └──────────────┘ └─────────────────┘
        │
        ▼ (generada automaticamente)
 ┌──────────────┐
 │ActuacionSto  │ ← ENTIDAD EXISTENTE
 │orient_indiv. │
 │1.5h          │
 │→ increment   │
 │  horas_orient│
 └──────────────┘
        │
        ▼ (actualiza)
 ┌──────────────┐
 │ProgramaPart. │ ← ENTIDAD EXISTENTE
 │horas_orient: │
 │  6.5h → 8.0h │
 └──────────────┘
```

### 3.2 Entidad AccionFormativaEi

**Proposito:** Representa un modulo o accion formativa del programa que el Coordinador disena antes de su ejecucion. Es la unidad atomica que requiere VoBo SAE para las acciones de tipo `formacion`. Cada accion tiene un titulo, objetivos competenciales, horas previstas, modalidad, carril asociado, y opcionalmente se vincula a un curso LMS existente para la parte online.

**Ejemplo real (1a edicion):** "Curso de Emprendimiento — Modulo 1: Fundamentos" (20h, mixta, carril Acelera Pro, vinculado a curso LMS #42). Este modulo formo parte del curso de 5 modulos que se impartio en 4 grupos (F01-F04) con presentacion PPTX de 105MB.

**Ruta admin:** `/admin/content/andalucia-ei/acciones-formativas`
**Ruta admin Field UI:** `/admin/structure/accion-formativa-ei`
**Ruta frontend (slide-panel):** Desde Hub Coordinador, pestana "Plan Formativo"

**Anotacion del entity type (corregida v2.0 — incluye route_provider, view_builder, owner key, revision tables):**

```php
/**
 * @ContentEntityType(
 *   id = "accion_formativa_ei",
 *   label = @Translation("Accion Formativa EI"),
 *   label_collection = @Translation("Acciones Formativas EI"),
 *   label_singular = @Translation("accion formativa"),
 *   label_plural = @Translation("acciones formativas"),
 *   handlers = {
 *     "access" = "Drupal\jaraba_andalucia_ei\Access\AccionFormativaEiAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\jaraba_andalucia_ei\Form\AccionFormativaEiForm",
 *       "add" = "Drupal\jaraba_andalucia_ei\Form\AccionFormativaEiForm",
 *       "edit" = "Drupal\jaraba_andalucia_ei\Form\AccionFormativaEiForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "accion_formativa_ei",
 *   revision_table = "accion_formativa_ei_revision",
 *   admin_permission = "administer andalucia ei",
 *   field_ui_base_route = "entity.accion_formativa_ei.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "uuid" = "uuid",
 *     "label" = "titulo",
 *     "owner" = "uid",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/andalucia-ei/acciones-formativas/{accion_formativa_ei}",
 *     "add-form" = "/admin/content/andalucia-ei/acciones-formativas/add",
 *     "edit-form" = "/admin/content/andalucia-ei/acciones-formativas/{accion_formativa_ei}/edit",
 *     "delete-form" = "/admin/content/andalucia-ei/acciones-formativas/{accion_formativa_ei}/delete",
 *     "collection" = "/admin/content/andalucia-ei/acciones-formativas",
 *     "version-history" = "/admin/content/andalucia-ei/acciones-formativas/{accion_formativa_ei}/revisions",
 *     "revision" = "/admin/content/andalucia-ei/acciones-formativas/{accion_formativa_ei}/revision/{accion_formativa_ei_revision}/view",
 *   },
 * )
 */
```

**Declaracion de clase (ENTITY-001 compliant):**

```php
class AccionFormativaEi extends ContentEntityBase
  implements AccionFormativaEiInterface, EntityOwnerInterface, EntityChangedInterface, RevisionLogInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;
  use RevisionLogEntityTrait;
}
```

**Nota sobre revisiones:** AccionFormativaEi es la UNICA de las 4 entidades nuevas con soporte de revisiones. Justificacion: el workflow VoBo SAE requiere audit trail inmutable de cada cambio de estado — quien cambio que, cuando, y por que. Las otras 3 entidades (SesionProgramadaEi, InscripcionSesionEi, PlanFormativoEi) NO tienen revision tables porque su historial se traza via timestamps (created/changed) y la relacion con ActuacionSto/expediente.

**Campos completos:**

| Campo | Tipo | Req | Peso | Descripcion |
|-------|------|-----|------|-------------|
| `id` | serial | auto | — | Primary key |
| `uuid` | uuid | auto | — | Identificador unico universal |
| `uid` | entity_reference (user) | si | -25 | Creador (EntityOwnerTrait) |
| `tenant_id` | entity_reference (group) | si | -20 | Tenant. TENANT-001: filtrado obligatorio en queries |
| `titulo` | string(255) | si | -15 | Titulo de la accion formativa. Entity key label |
| `descripcion` | text_long | si | -10 | Contenido programatico detallado (temas, subtemas, metodologia) |
| `objetivos_competenciales` | text_long | si | -5 | Objetivos de aprendizaje y competencias a adquirir. Requerido para VoBo SAE |
| `horas_previstas` | decimal(8,2) | si | 0 | Horas totales de la accion. Se valida contra minimo PIIL |
| `modalidad` | list_string | si | 5 | presencial_sede, presencial_empresa, online_videoconf, online_plataforma, mixta. Mismos 5 valores que ActuacionSto + 'mixta' |
| `carril` | list_string | si | 10 | impulso_digital, acelera_pro, hibrido, todos. Determina que participantes pueden inscribirse |
| `tipo_formacion` | list_string | si | 15 | orientacion_individual, orientacion_grupal, formacion, tutoria, taller, evaluacion. Determina si requiere VoBo SAE |
| `fase_programa` | list_string | si | 20 | acogida, diagnostico, atencion, insercion. En que fase del itinerario PIIL se imparte |
| `formador_nombre` | string(255) | no | 25 | Nombre del formador/a. Requerido para documentacion VoBo |
| `formador_titulacion` | text_long | no | 30 | Titulacion y experiencia profesional del formador |
| `formador_id` | entity_reference (user) | no | 35 | Si el formador es usuario de la plataforma |
| `max_participantes` | integer | no | 40 | Plazas maximas. NULL = sin limite |
| `course_id` | integer | no | 45 | FK opcional a `lms_course` (ENTITY-FK-001). Si la accion tiene componente online, vincula al curso LMS |
| `interactive_content_id` | integer | no | 50 | FK opcional a `interactive_content`. Para evaluacion asociada |
| `vobo_sae_status` | list_string | si | 55 | borrador, pendiente_documentacion, enviado_sae, pendiente_vobo, aprobado, rechazado, en_subsanacion, caducado. Default: borrador |
| `vobo_sae_codigo` | string(50) | no | 60 | Codigo asignado por SAE al aprobar la accion |
| `vobo_sae_fecha_solicitud` | datetime (date) | no | 65 | Fecha de envio de la solicitud al SAE |
| `vobo_sae_fecha_respuesta` | datetime (date) | no | 70 | Fecha de la respuesta del SAE |
| `vobo_sae_observaciones` | text_long | no | 75 | Observaciones del SAE (motivo de rechazo, correcciones solicitadas) |
| `vobo_sae_expediente_id` | entity_reference (expediente_documento) | no | 80 | Documento PDF generado para enviar al SAE |
| `vobo_sae_respuesta_id` | entity_reference (expediente_documento) | no | 85 | Documento de respuesta del SAE (escaneado o digital) |
| `activa` | boolean | si | 90 | Default TRUE. Permite desactivar sin eliminar |
| `orden` | integer | no | 95 | Peso para ordenacion dentro de un plan formativo |
| `created` | created | auto | — | Timestamp de creacion |
| `changed` | changed | auto | — | Timestamp de ultima modificacion |

**Metodos de la interfaz `AccionFormativaEiInterface`:**

```php
public function getTitulo(): string;
public function getDescripcion(): ?string;
public function getHorasPrevistas(): float;
public function getCarril(): string;
public function getTipoFormacion(): string;
public function getFasePrograma(): string;
public function getModalidad(): string;
public function requiereVoboSae(): bool;  // TRUE si tipo_formacion === 'formacion'
public function isVoboAprobado(): bool;    // vobo_sae_status === 'aprobado'
public function canExecute(): bool;        // !requiereVoboSae() || isVoboAprobado()
public function getCourseId(): ?int;
public function isActiva(): bool;
```

**Logica de negocio:**

- `requiereVoboSae()` devuelve `TRUE` solo si `tipo_formacion === 'formacion'`. Las orientaciones, tutorias y talleres NO requieren VoBo del SAE segun la normativa PIIL.
- `canExecute()` determina si se pueden calendarizar sesiones: las acciones que no requieren VoBo siempre pueden ejecutarse; las que si lo requieren, solo tras `vobo_sae_status === 'aprobado'`.
- Al cambiar `vobo_sae_status`, se genera alerta normativa si el VoBo lleva mas de 15 dias sin respuesta.

**PremiumEntityFormBase — getSectionDefinitions():**

```php
protected function getSectionDefinitions(): array {
  return [
    'general' => [
      'label' => $this->t('Informacion General'),
      'icon' => ['category' => 'ui', 'name' => 'file-text'],
      'description' => $this->t('Titulo, descripcion y objetivos de la accion formativa.'),
      'fields' => ['titulo', 'descripcion', 'objetivos_competenciales', 'horas_previstas'],
    ],
    'clasificacion' => [
      'label' => $this->t('Clasificacion'),
      'icon' => ['category' => 'ui', 'name' => 'filter'],
      'description' => $this->t('Tipo, modalidad, carril y fase del programa.'),
      'fields' => ['tipo_formacion', 'modalidad', 'carril', 'fase_programa', 'max_participantes'],
    ],
    'formador' => [
      'label' => $this->t('Formador/a'),
      'icon' => ['category' => 'users', 'name' => 'user'],
      'description' => $this->t('Datos del profesional que impartira la formacion.'),
      'fields' => ['formador_nombre', 'formador_titulacion', 'formador_id'],
    ],
    'vobo_sae' => [
      'label' => $this->t('VoBo SAE'),
      'icon' => ['category' => 'status', 'name' => 'check-circle'],
      'description' => $this->t('Estado del Visto Bueno del Servicio Andaluz de Empleo.'),
      'fields' => ['vobo_sae_status', 'vobo_sae_codigo', 'vobo_sae_fecha_solicitud', 'vobo_sae_fecha_respuesta', 'vobo_sae_observaciones', 'vobo_sae_expediente_id', 'vobo_sae_respuesta_id'],
    ],
    'integraciones' => [
      'label' => $this->t('Integraciones'),
      'icon' => ['category' => 'tools', 'name' => 'code'],
      'description' => $this->t('Vincular a curso LMS o contenido interactivo.'),
      'fields' => ['course_id', 'interactive_content_id'],
    ],
    'config' => [
      'label' => $this->t('Configuracion'),
      'icon' => ['category' => 'ui', 'name' => 'settings'],
      'fields' => ['activa', 'orden', 'tenant_id'],
    ],
  ];
}

protected function getFormIcon(): array {
  return ['category' => 'business', 'name' => 'pathway'];
}
```

### 3.3 Entidad SesionProgramadaEi — Tabla completa de campos

**Proposito:** Instancia calendarizada de una sesion (individual o grupal, formacion u orientacion). Puede pertenecer a una `AccionFormativaEi` o ser independiente (sesiones sueltas de orientacion individual). Soporta recurrencia via patron JSON simplificado. Tiene control de plazas: las participantes ven cuantas plazas quedan y pueden inscribirse.

**Ejemplo real (1a edicion):** "Grupal Malaga 2024 02 20y22 Regus" — sesion grupal de orientacion inicial en Regus Malaga Centro, 20-22 febrero 2024. En el SaaS, esto seria una SesionProgramadaEi con `tipo_sesion: orientacion_grupal`, `lugar_descripcion: "Regus Malaga Centro"`, `max_plazas: 15`.

**Ruta admin:** `/admin/content/andalucia-ei/sesiones-programadas`
**Ruta admin Field UI:** `/admin/structure/sesion-programada-ei`
**Ruta frontend:** Calendario en Hub Coordinador + Portal participante

**Anotacion del entity type (corregida v2.0):**

```php
/**
 * @ContentEntityType(
 *   id = "sesion_programada_ei",
 *   label = @Translation("Sesion Programada EI"),
 *   label_collection = @Translation("Sesiones Programadas EI"),
 *   label_singular = @Translation("sesion programada"),
 *   label_plural = @Translation("sesiones programadas"),
 *   handlers = {
 *     "access" = "Drupal\jaraba_andalucia_ei\Access\SesionProgramadaEiAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\jaraba_andalucia_ei\Form\SesionProgramadaEiForm",
 *       "add" = "Drupal\jaraba_andalucia_ei\Form\SesionProgramadaEiForm",
 *       "edit" = "Drupal\jaraba_andalucia_ei\Form\SesionProgramadaEiForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "sesion_programada_ei",
 *   admin_permission = "administer andalucia ei",
 *   field_ui_base_route = "entity.sesion_programada_ei.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "titulo",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/andalucia-ei/sesiones-programadas/{sesion_programada_ei}",
 *     "add-form" = "/admin/content/andalucia-ei/sesiones-programadas/add",
 *     "edit-form" = "/admin/content/andalucia-ei/sesiones-programadas/{sesion_programada_ei}/edit",
 *     "delete-form" = "/admin/content/andalucia-ei/sesiones-programadas/{sesion_programada_ei}/delete",
 *     "collection" = "/admin/content/andalucia-ei/sesiones-programadas",
 *   },
 * )
 */
```

**Declaracion de clase:**

```php
class SesionProgramadaEi extends ContentEntityBase
  implements SesionProgramadaEiInterface, EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;
}
```

**Campos completos (25):**

| # | Campo | Tipo | Req | Peso | Descripcion |
|---|-------|------|-----|------|-------------|
| 1 | `id` | serial | auto | — | Primary key |
| 2 | `uuid` | uuid | auto | — | Identificador unico universal |
| 3 | `uid` | entity_reference (user) | si | -25 | Creador (EntityOwnerTrait) |
| 4 | `tenant_id` | entity_reference (group) | si | -20 | Tenant. TENANT-001 |
| 5 | `titulo` | string(255) | si | -15 | Titulo descriptivo. Entity key label |
| 6 | `descripcion` | text_long | no | -10 | Descripcion del contenido/objetivos de la sesion |
| 7 | `accion_formativa_id` | entity_reference (accion_formativa_ei) | no | -5 | FK a accion formativa padre. NULL para sesiones independientes (orient. individual) |
| 8 | `tipo_sesion` | list_string | si | 0 | orientacion_individual, orientacion_grupal, formacion, tutoria, taller, evaluacion. Compatible con ActuacionSto.tipo_actuacion |
| 9 | `fase_programa` | list_string | si | 5 | acogida, diagnostico, atencion, insercion. En que fase PIIL se programa |
| 10 | `fecha` | datetime (date only) | si | 10 | Fecha de la sesion (YYYY-MM-DD) |
| 11 | `hora_inicio` | string(5) | si | 15 | Hora inicio HH:MM (formato 24h). String porque Drupal no tiene tipo time nativo |
| 12 | `hora_fin` | string(5) | si | 20 | Hora fin HH:MM |
| 13 | `modalidad` | list_string | si | 25 | presencial_sede, presencial_empresa, online_videoconf, online_plataforma, mixta. Mismos valores que AccionFormativaEi |
| 14 | `lugar_descripcion` | string(255) | no | 30 | Nombre/direccion del lugar. Ej: "Regus Malaga Centro" |
| 15 | `lugar_url` | string(500) | no | 35 | URL de videoconferencia (Zoom, Meet, Teams) si modalidad online |
| 16 | `facilitador_id` | entity_reference (user) | no | 40 | Formador/orientador que facilita la sesion |
| 17 | `facilitador_nombre` | string(255) | no | 45 | Nombre del facilitador si no es usuario de la plataforma |
| 18 | `max_plazas` | integer | no | 50 | Plazas maximas. NULL = sin limite (orientacion individual siempre 1) |
| 19 | `plazas_ocupadas` | integer | no | 55 | Computed en preSave desde count de InscripcionSesionEi con estado IN (inscrito, confirmado, asistio). NO usar setComputed — se almacena para queries eficientes |
| 20 | `estado` | list_string | si | 60 | programada, confirmada, en_curso, completada, cancelada, aplazada. Default: programada |
| 21 | `es_recurrente` | boolean | si | 65 | Default FALSE. Si TRUE, recurrencia_patron es JSON valido |
| 22 | `recurrencia_patron` | text_long | no | 70 | JSON: {"freq":"weekly","interval":1,"days":[1,3],"until":"2026-06-30"} |
| 23 | `sesion_padre_id` | entity_reference (sesion_programada_ei) | no | 75 | Self-referencing FK. Sesiones expandidas desde recurrencia apuntan a la sesion template |
| 24 | `created` | created | auto | — | Timestamp de creacion |
| 25 | `changed` | changed | auto | — | Timestamp de ultima modificacion |

**Nota sobre `plazas_ocupadas`:** Se almacena en DB (NO es computed puro) porque se necesita en queries de filtrado (ej: "sesiones con plazas disponibles"). Se recalcula en `SesionProgramadaEi::preSave()` contando inscripciones activas. Alternativa: recalcular en `InscripcionSesionService` al crear/cancelar inscripcion via `$sesion->recalcularPlazas()->save()`.

**Patron de recurrencia JSON:**

```json
{
  "freq": "weekly",
  "interval": 1,
  "days": [1, 3],
  "until": "2026-06-30"
}
```

Valores de `freq`: `daily`, `weekly`, `biweekly`, `monthly`. `days` es array de numeros 0-6 (lunes=1, domingo=0). `until` es fecha ISO limite. Al publicar la sesion, se expanden las sesiones hijas con `sesion_padre_id` apuntando a la sesion template. Las sesiones hijas son independientes (se pueden cancelar individualmente).

**Metodos de la interfaz `SesionProgramadaEiInterface`:**

```php
public function getTitulo(): string;
public function getTipoSesion(): string;
public function getFecha(): ?string;
public function getHoraInicio(): ?string;
public function getHoraFin(): ?string;
public function getDuracionHoras(): float;   // Calcula diferencia hora_fin - hora_inicio
public function getModalidad(): string;
public function getFacilitadorId(): ?int;
public function getMaxPlazas(): ?int;
public function getPlazasOcupadas(): int;
public function getPlazasDisponibles(): ?int; // max_plazas - plazas_ocupadas, NULL si sin limite
public function hayPlazasDisponibles(): bool; // max_plazas === NULL || plazas_ocupadas < max_plazas
public function isGrupal(): bool;             // tipo_sesion contiene 'grupal' o max_plazas > 1
public function getEstado(): string;
public function getAccionFormativaId(): ?int;
public function isRecurrente(): bool;
public function getSesionPadreId(): ?int;
public function recalcularPlazas(): static;   // Fluent: recuenta inscripciones y actualiza campo
```

### 3.4 Entidad InscripcionSesionEi — Tabla completa de campos

**Proposito:** Registra que una participante se ha inscrito en una sesion programada. Es el puente entre "la sesion existe" y "la participante asistio y se le computan las horas". Al confirmar asistencia, genera automaticamente una `ActuacionSto` y actualiza las horas del `ProgramaParticipanteEi`.

**Ejemplo real (1a edicion):** En la carpeta de la 1a edicion, la asistencia se registraba en "Metricas.xlsx" con filas por participante y columnas por sesion. Los recibos de formacion se firmaban en papel uno a uno ("RECIBO DE FORMACION-ALVARO SANCHEZ ROMAN", 5 recibos diarios firmados). El SaaS automatiza todo este flujo.

**Ruta admin:** `/admin/content/andalucia-ei/inscripciones-sesion`
**Ruta admin Field UI:** `/admin/structure/inscripcion-sesion-ei`
**Ruta frontend (participante):** Portal > "Mis Sesiones" (inscripcion + visualizacion)
**Ruta frontend (facilitador):** Hub Coordinador > Calendario > Sesion > "Marcar asistencia"

**Anotacion del entity type (corregida v2.0):**

```php
/**
 * @ContentEntityType(
 *   id = "inscripcion_sesion_ei",
 *   label = @Translation("Inscripcion Sesion EI"),
 *   label_collection = @Translation("Inscripciones Sesion EI"),
 *   label_singular = @Translation("inscripcion"),
 *   label_plural = @Translation("inscripciones"),
 *   handlers = {
 *     "access" = "Drupal\jaraba_andalucia_ei\Access\InscripcionSesionEiAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\jaraba_andalucia_ei\Form\InscripcionSesionEiForm",
 *       "add" = "Drupal\jaraba_andalucia_ei\Form\InscripcionSesionEiForm",
 *       "edit" = "Drupal\jaraba_andalucia_ei\Form\InscripcionSesionEiForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "inscripcion_sesion_ei",
 *   admin_permission = "administer andalucia ei",
 *   field_ui_base_route = "entity.inscripcion_sesion_ei.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/andalucia-ei/inscripciones-sesion/{inscripcion_sesion_ei}",
 *     "add-form" = "/admin/content/andalucia-ei/inscripciones-sesion/add",
 *     "edit-form" = "/admin/content/andalucia-ei/inscripciones-sesion/{inscripcion_sesion_ei}/edit",
 *     "delete-form" = "/admin/content/andalucia-ei/inscripciones-sesion/{inscripcion_sesion_ei}/delete",
 *     "collection" = "/admin/content/andalucia-ei/inscripciones-sesion",
 *   },
 * )
 */
```

**Nota:** InscripcionSesionEi NO tiene entity key `"label"` — no tiene un titulo natural. LABEL-NULLSAFE-001 aplica: `$entity->label()` devolvera NULL. `PremiumEntityFormBase` tiene fallback null-safe.

**Declaracion de clase:**

```php
class InscripcionSesionEi extends ContentEntityBase
  implements InscripcionSesionEiInterface, EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;
}
```

**Campos completos (15):**

| # | Campo | Tipo | Req | Peso | Descripcion |
|---|-------|------|-----|------|-------------|
| 1 | `id` | serial | auto | — | Primary key |
| 2 | `uuid` | uuid | auto | — | Identificador unico universal |
| 3 | `uid` | entity_reference (user) | si | -25 | Quien creo la inscripcion (puede ser la participante o el facilitador). EntityOwnerTrait |
| 4 | `tenant_id` | entity_reference (group) | si | -20 | Tenant. TENANT-001 |
| 5 | `sesion_id` | entity_reference (sesion_programada_ei) | si | -15 | FK obligatoria a la sesion. Mismo modulo → entity_reference (ENTITY-FK-001) |
| 6 | `participante_id` | entity_reference (programa_participante_ei) | si | -10 | FK obligatoria a la participante. Mismo modulo → entity_reference |
| 7 | `estado` | list_string | si | 0 | inscrito, confirmado, asistio, no_asistio, cancelado, justificado. Default: inscrito |
| 8 | `fecha_inscripcion` | datetime | si | 5 | Cuando la participante se inscribio |
| 9 | `fecha_asistencia` | datetime | no | 10 | Cuando el facilitador confirmo la asistencia |
| 10 | `asistencia_verificada` | boolean | si | 15 | Default FALSE. TRUE cuando facilitador marca asistencia. Trigger de generacion ActuacionSto |
| 11 | `horas_computadas` | decimal(8,2) | no | 20 | Horas que se computan para esta inscripcion. Puede diferir de la duracion de la sesion (ej: llego tarde) |
| 12 | `actuacion_sto_id` | entity_reference (actuacion_sto) | no | 25 | FK a la ActuacionSto generada automaticamente al confirmar asistencia. NULL hasta que se confirme |
| 13 | `motivo_cancelacion` | text_long | no | 30 | Si estado = cancelado o justificado, el motivo |
| 14 | `created` | created | auto | — | Timestamp de creacion |
| 15 | `changed` | changed | auto | — | Timestamp de ultima modificacion |

**Metodos de la interfaz `InscripcionSesionEiInterface`:**

```php
public function getSesionId(): int;
public function getParticipanteId(): int;
public function getEstado(): string;
public function isAsistenciaVerificada(): bool;
public function getHorasComputadas(): ?float;
public function getActuacionStoId(): ?int;
public function hasSesion(): bool;          // Verifica que sesion_id referencia entidad existente
public function hasParticipante(): bool;    // Verifica que participante_id referencia entidad existente
```

**Logica critica de confirmacion de asistencia:**

Cuando el facilitador marca `estado = 'asistio'`:

1. `InscripcionSesionService::confirmarAsistencia()` crea una nueva `ActuacionSto` con datos de la sesion:
   - `tipo_actuacion` = mapeo de `sesion.tipo_sesion` a tipos ActuacionSto (orientacion_individual → orientacion_individual, formacion → formacion, etc.)
   - `fecha` = `sesion.fecha`
   - `hora_inicio` / `hora_fin` = `sesion.hora_inicio` / `sesion.hora_fin`
   - `participante_id` = `inscripcion.participante_id`
   - `orientador_id` = `sesion.facilitador_id`
   - `fase_participante` = `participante.fase_actual` (snapshot de la fase en ese momento)
2. Llama a `ActuacionStoService::incrementarHorasParticipante()` para actualizar los campos de horas del `ProgramaParticipanteEi` (orient_ind, orient_grup, formacion, mentoria, segun tipo)
3. Actualiza `inscripcion.actuacion_sto_id` con el ID de la actuacion creada
4. Actualiza `inscripcion.horas_computadas`
5. Si es sesion grupal con firma dual: solicita generacion de hoja de servicio via `ReciboServicioService`
6. Actualiza `sesion.plazas_ocupadas` (decrement si cancelacion, no-op si asistencia)

### 3.5 Entidad PlanFormativoEi

**Proposito:** Plantilla de itinerario formativo por carril. Compone acciones formativas en un plan coherente que suma las horas requeridas por la normativa PIIL (>=50h formacion + >=10h orientacion). Permite al Coordinador visualizar si un carril tiene cobertura suficiente y detectar deficiencias antes de ejecutar.

**Ejemplo real (1a edicion):** El carril "Acelera Pro" se componia de: Curso Emprendimiento 5 modulos (50h formacion), Orientacion Inicial Grupal (4h orient), Orientacion Individual continua (~6-8h orient individual). Esto no estaba formalizado — estaba implicito en la estructura de carpetas.

**Ruta admin:** `/admin/content/andalucia-ei/planes-formativos`
**Ruta admin Field UI:** `/admin/structure/plan-formativo-ei`
**Ruta frontend:** Hub Coordinador > Pestana "Plan Formativo"

**Anotacion del entity type (corregida v2.0):**

```php
/**
 * @ContentEntityType(
 *   id = "plan_formativo_ei",
 *   label = @Translation("Plan Formativo EI"),
 *   label_collection = @Translation("Planes Formativos EI"),
 *   label_singular = @Translation("plan formativo"),
 *   label_plural = @Translation("planes formativos"),
 *   handlers = {
 *     "access" = "Drupal\jaraba_andalucia_ei\Access\PlanFormativoEiAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\jaraba_andalucia_ei\Form\PlanFormativoEiForm",
 *       "add" = "Drupal\jaraba_andalucia_ei\Form\PlanFormativoEiForm",
 *       "edit" = "Drupal\jaraba_andalucia_ei\Form\PlanFormativoEiForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "plan_formativo_ei",
 *   admin_permission = "administer andalucia ei",
 *   field_ui_base_route = "entity.plan_formativo_ei.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "label",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/andalucia-ei/planes-formativos/{plan_formativo_ei}",
 *     "add-form" = "/admin/content/andalucia-ei/planes-formativos/add",
 *     "edit-form" = "/admin/content/andalucia-ei/planes-formativos/{plan_formativo_ei}/edit",
 *     "delete-form" = "/admin/content/andalucia-ei/planes-formativos/{plan_formativo_ei}/delete",
 *     "collection" = "/admin/content/andalucia-ei/planes-formativos",
 *   },
 * )
 */
```

**Declaracion de clase:**

```php
class PlanFormativoEi extends ContentEntityBase
  implements PlanFormativoEiInterface, EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;
}
```

**Campos completos (17):**

| # | Campo | Tipo | Req | Peso | Almacenado | Descripcion |
|---|-------|------|-----|------|-----------|-------------|
| 1 | `id` | serial | auto | — | Si | Primary key |
| 2 | `uuid` | uuid | auto | — | Si | Identificador unico universal |
| 3 | `uid` | entity_reference (user) | si | -25 | Si | Creador (EntityOwnerTrait) |
| 4 | `tenant_id` | entity_reference (group) | si | -20 | Si | Tenant. TENANT-001 |
| 5 | `label` | string(255) | si | -15 | Si | Titulo del plan. Entity key label. Ej: "Plan Impulso Digital — Cohorte 2" |
| 6 | `descripcion` | text_long | no | -10 | Si | Descripcion del itinerario formativo |
| 7 | `carril` | list_string | si | -5 | Si | impulso_digital, acelera_pro, hibrido. Determina que participantes aplica |
| 8 | `acciones_formativas` | entity_reference (accion_formativa_ei) | si | 0 | Si | Multi-value (cardinality -1). Acciones que componen el plan |
| 9 | `edicion` | string(50) | no | 5 | Si | Ej: "2a edicion 2026". Permite varios planes por carril en distintas ediciones |
| 10 | `fecha_inicio_prevista` | datetime (date) | no | 10 | Si | Inicio previsto del plan formativo |
| 11 | `fecha_fin_prevista` | datetime (date) | no | 15 | Si | Fin previsto del plan formativo |
| 12 | `activo` | boolean | si | 20 | Si | Default TRUE. Permite desactivar planes de ediciones anteriores |
| 13 | `horas_formacion_previstas` | decimal(8,2) | no | 25 | **Si** | Recalculado en preSave. Se almacena para queries/views eficientes |
| 14 | `horas_orientacion_previstas` | decimal(8,2) | no | 30 | **Si** | Recalculado en preSave. Se almacena para queries/views eficientes |
| 15 | `cumple_minimo_formacion` | boolean | no | 35 | **Si** | Recalculado en preSave: TRUE si horas_formacion >= 50.0 |
| 16 | `cumple_minimo_orientacion` | boolean | no | 40 | **Si** | Recalculado en preSave: TRUE si horas_orientacion >= 10.0 |
| 17 | `created` | created | auto | — | Si | Timestamp de creacion |
| 18 | `changed` | changed | auto | — | Si | Timestamp de ultima modificacion |

**NOTA CRITICA sobre campos "computed" (correccion v2.0):** Los campos 13-16 NO usan `setComputed(TRUE)` ni `setNotStorable(TRUE)`. Se **almacenan en DB** y se recalculan en `preSave()`. Razon: estos campos se necesitan en Views, entity queries con condiciones (`->condition('cumple_minimo_formacion', TRUE)`), y listados admin. Los campos `setComputed(TRUE)` no se pueden filtrar en queries. El patron es identico al `plazas_ocupadas` de SesionProgramadaEi.

**Validacion en preSave:**

```php
public function preSave(EntityStorageInterface $storage): void {
  parent::preSave($storage);

  // Recalcular horas totales desde acciones vinculadas.
  $horasFormacion = 0.0;
  $horasOrientacion = 0.0;

  foreach ($this->get('acciones_formativas') as $item) {
    $accion = $item->entity;
    if (!$accion instanceof AccionFormativaEiInterface) {
      continue;
    }
    $tipo = $accion->getTipoFormacion();
    $horas = $accion->getHorasPrevistas();

    if ($tipo === 'formacion' || $tipo === 'taller') {
      $horasFormacion += $horas;
    }
    elseif (str_starts_with($tipo, 'orientacion') || $tipo === 'tutoria') {
      $horasOrientacion += $horas;
    }
  }

  $this->set('horas_formacion_previstas', $horasFormacion);
  $this->set('horas_orientacion_previstas', $horasOrientacion);
  $this->set('cumple_minimo_formacion', $horasFormacion >= 50.0);
  $this->set('cumple_minimo_orientacion', $horasOrientacion >= 10.0);
}
```

### 3.6 Arquitectura de servicios

**Nuevos servicios (4):**

| Servicio | Machine name | Dependencias hard | Dependencias optional (@?) |
|----------|-------------|-------------------|---------------------------|
| `AccionFormativaService` | `jaraba_andalucia_ei.accion_formativa` | `@entity_type.manager`, `@logger.channel.jaraba_andalucia_ei` | `@?jaraba_andalucia_ei.vobo_sae_workflow` |
| `VoboSaeWorkflowService` | `jaraba_andalucia_ei.vobo_sae_workflow` | `@entity_type.manager`, `@logger.channel.jaraba_andalucia_ei` | `@?jaraba_andalucia_ei.expediente`, `@?jaraba_andalucia_ei.alertas_normativas` |
| `SesionProgramadaService` | `jaraba_andalucia_ei.sesion_programada` | `@entity_type.manager`, `@logger.channel.jaraba_andalucia_ei` | `@?jaraba_andalucia_ei.inscripcion_sesion`, `@?jaraba_legal_calendar.calendar_sync`, `@?jaraba_andalucia_ei.ei_push_notification` |
| `InscripcionSesionService` | `jaraba_andalucia_ei.inscripcion_sesion` | `@entity_type.manager`, `@logger.channel.jaraba_andalucia_ei`, `@jaraba_andalucia_ei.actuacion_sto` | `@?jaraba_andalucia_ei.firma_workflow`, `@?jaraba_andalucia_ei.ei_push_notification` |

**Verificacion CONTAINER-DEPS-002:** No hay dependencias circulares:
- `InscripcionSesionService` → `ActuacionStoService` (direccion unica)
- `SesionProgramadaService` → `InscripcionSesionService` via `@?` (opcional, no circular)
- `AccionFormativaService` → `VoboSaeWorkflowService` via `@?` (opcional, no circular)
- `ActuacionStoService` NO conoce `InscripcionSesionService` (aislamiento)

**Verificacion PHANTOM-ARG-001:** Cada arg en services.yml tendra su parametro correspondiente en el constructor PHP.

**Verificacion LOGGER-INJECT-001:** Todos usan `@logger.channel.jaraba_andalucia_ei` + `LoggerInterface $logger` en constructor (NUNCA `->get('channel')`).

### 3.7 Mapa de rutas

**Rutas admin (CRUD estandar):**

```yaml
# AccionFormativaEi
entity.accion_formativa_ei.collection:     /admin/content/andalucia-ei/acciones-formativas
entity.accion_formativa_ei.add_form:       /admin/content/andalucia-ei/acciones-formativas/add
entity.accion_formativa_ei.canonical:      /admin/content/andalucia-ei/acciones-formativas/{id}
entity.accion_formativa_ei.edit_form:      /admin/content/andalucia-ei/acciones-formativas/{id}/edit
entity.accion_formativa_ei.delete_form:    /admin/content/andalucia-ei/acciones-formativas/{id}/delete
entity.accion_formativa_ei.settings:       /admin/structure/accion-formativa-ei

# SesionProgramadaEi
entity.sesion_programada_ei.collection:    /admin/content/andalucia-ei/sesiones-programadas
entity.sesion_programada_ei.settings:      /admin/structure/sesion-programada-ei

# InscripcionSesionEi
entity.inscripcion_sesion_ei.collection:   /admin/content/andalucia-ei/inscripciones-sesion
entity.inscripcion_sesion_ei.settings:     /admin/structure/inscripcion-sesion-ei

# PlanFormativoEi
entity.plan_formativo_ei.collection:       /admin/content/andalucia-ei/planes-formativos
entity.plan_formativo_ei.settings:         /admin/structure/plan-formativo-ei
```

**Rutas API (JSON, CSRF-API-001):**

```yaml
# Acciones formativas
jaraba_andalucia_ei.api.acciones_formativas:               GET  /api/v1/andalucia-ei/acciones-formativas
jaraba_andalucia_ei.api.accion_formativa.vobo:             POST /api/v1/andalucia-ei/accion-formativa/{id}/vobo

# Sesiones programadas
jaraba_andalucia_ei.api.sesiones_programadas:              GET  /api/v1/andalucia-ei/sesiones
jaraba_andalucia_ei.api.sesion.completar:                  POST /api/v1/andalucia-ei/sesion/{id}/completar

# Inscripciones
jaraba_andalucia_ei.api.inscripcion.inscribir:             POST /api/v1/andalucia-ei/sesion/{id}/inscribir
jaraba_andalucia_ei.api.inscripcion.cancelar:              POST /api/v1/andalucia-ei/inscripcion/{id}/cancelar
jaraba_andalucia_ei.api.inscripcion.asistencia:            POST /api/v1/andalucia-ei/sesion/{id}/asistencia
jaraba_andalucia_ei.api.inscripcion.mis_sesiones:          GET  /api/v1/andalucia-ei/mis-sesiones

# Planes formativos
jaraba_andalucia_ei.api.planes_formativos:                 GET  /api/v1/andalucia-ei/planes-formativos
```

**Todas las rutas POST con:** `_csrf_request_header_token: 'TRUE'` (CSRF-API-001)
**Todas las rutas con:** `_permission: 'administer andalucia ei'` o permiso especifico

### 3.8 Diagrama de flujo del coordinador

(Ver seccion 10 de la auditoria — `docs/analisis/2026-03-11_...v1.md`)

### 3.9 Diagrama de flujo de la participante

(Ver seccion 11 de la auditoria — `docs/analisis/2026-03-11_...v1.md`)

### 3.10 Decisiones arquitectonicas explicitas

Decisiones tomadas durante la auditoria de clase mundial v2.0 para resolver ambiguedades del plan v1.x:

| Decision | Opcion elegida | Justificacion |
|----------|---------------|---------------|
| **Translatable** | **NO** para las 4 entidades nuevas | Programa es monolingue (espanol). Anade complejidad innecesaria (data_table, content_translation hooks). Si se internacionaliza en futuro, se migra con UPDATE-HOOK-FIELDABLE-001 + TRANSLATABLE-FIELDS-INSTALL-001 |
| **Revisiones** | **SI** solo para AccionFormativaEi | Audit trail VoBo SAE requiere historial inmutable de quien cambio que. Las otras 3 entidades trazan historial via timestamps + relacion con ActuacionSto/expediente |
| **Hook update numbering** | 10019, 10020, 10021, 10022 | Ultimo existente es 10018. Una por entidad en orden de dependencia: AccionFormativa(19) → SesionProgramada(20) → InscripcionSesion(21) → PlanFormativo(22) |
| **Campos computed** | **Almacenados en DB**, recalculados en `preSave()` | plazas_ocupadas, horas_formacion_previstas, horas_orientacion_previstas, cumple_minimo_*. Se necesitan en Views/queries. `setComputed(TRUE)` impide filtrar |
| **AccessControlHandler DI** | Patron `EntityHandlerInterface` + `createInstance()` | Consistente con PlanEmprendimientoEiAccessControlHandler existente. Inyecta `@?ecosistema_jaraba_core.tenant_context` |
| **API endpoint naming** | Guiones: `/api/v1/andalucia-ei/` | Consistente con rutas existentes del modulo. NO underscores en URLs |
| **InscripcionSesionEi label** | **NO tiene label** | No tiene titulo natural. LABEL-NULLSAFE-001 aplica. `PremiumEntityFormBase` tiene fallback |
| **ProgramaVerticalAccessService integracion** | DI `@?` en FeatureAccessService (jaraba_billing) | NO EventSubscriber. Inyeccion directa como paso 5 de la cadena canAccess(). Direccion unica: billing ← andalucia_ei (CONTAINER-DEPS-002 ok) |

**Logica de `getParticipanteActivo()` en ProgramaVerticalAccessService (definicion explicita):**

```php
private function getParticipanteActivo(int $uid): ?ProgramaParticipanteEiInterface {
  $storage = $this->entityTypeManager->getStorage('programa_participante_ei');
  $ids = $storage->getQuery()
    ->accessCheck(FALSE)
    ->condition('uid', $uid)
    ->condition('fase_actual', ['alumni', 'baja'], 'NOT IN')
    ->sort('created', 'DESC')  // Si multiples, tomar el mas reciente
    ->range(0, 1)
    ->execute();

  if (empty($ids)) {
    return NULL;
  }

  $participante = $storage->load(reset($ids));
  return $participante instanceof ProgramaParticipanteEiInterface ? $participante : NULL;
}
```

**Nota:** NO filtra por `tenant_id` porque `uid` ya es unico por tenant en el contexto PIIL (una persona no puede estar en 2 programas PIIL de 2 tenants simultaneamente). Si cambiara, anadir `->condition('tenant_id', $tenantId)`.

### 3.11 Permisos granulares por rol

**v1.x usaba `administer andalucia ei` para todo. v2.0 define permisos granulares por rol del programa:**

**Roles del programa:**

| Rol | Descripcion | Drupal Role |
|-----|-------------|-------------|
| Coordinador/a | Gestiona programa completo, aprueba, configura | `coordinador_ei` o permiso `administer andalucia ei` |
| Orientador/a | Registra actuaciones, acompana participantes, facilita sesiones | `orientador_ei` |
| Formador/a | Imparte formacion, marca asistencia en sus sesiones | `formador_ei` |
| Participante | Se inscribe, asiste, consulta su portal | `participante_ei` (via `ParticipanteAccessCheck`) |

**Nuevos permisos (extensiones a .permissions.yml):**

```yaml
# AccionFormativaEi
create accion_formativa_ei:
  title: 'Crear accion formativa'
  description: 'Permite crear acciones formativas del programa.'
view accion_formativa_ei:
  title: 'Ver acciones formativas'
edit accion_formativa_ei:
  title: 'Editar acciones formativas'
delete accion_formativa_ei:
  title: 'Eliminar acciones formativas'
  restrict access: true
manage vobo_sae:
  title: 'Gestionar VoBo SAE'
  description: 'Permite enviar solicitudes y registrar respuestas del SAE.'

# SesionProgramadaEi
create sesion_programada_ei:
  title: 'Crear sesion programada'
view sesion_programada_ei:
  title: 'Ver sesiones programadas'
edit sesion_programada_ei:
  title: 'Editar sesiones programadas'
delete sesion_programada_ei:
  title: 'Eliminar sesiones programadas'
  restrict access: true

# InscripcionSesionEi
inscribir participante sesion:
  title: 'Inscribir participante en sesion'
  description: 'Permite inscribir a una participante (o a si misma) en una sesion.'
marcar asistencia sesion:
  title: 'Marcar asistencia en sesion'
  description: 'Permite al facilitador confirmar la asistencia.'
view inscripcion_sesion_ei:
  title: 'Ver inscripciones de sesion'
cancelar inscripcion sesion:
  title: 'Cancelar inscripcion en sesion'

# PlanFormativoEi
create plan_formativo_ei:
  title: 'Crear plan formativo'
view plan_formativo_ei:
  title: 'Ver planes formativos'
edit plan_formativo_ei:
  title: 'Editar planes formativos'

# Compliance ESF+
view indicadores_esf:
  title: 'Ver indicadores ESF+'
  description: 'Acceso al dashboard de indicadores para financiador.'
export indicadores_esf:
  title: 'Exportar indicadores ESF+'
  description: 'Permite descargar datos ESF+ en formato SFC2021.'
```

**Mapeo rol → permisos:**

| Permiso | Coordinador | Orientador | Formador | Participante |
|---------|:-----------:|:----------:|:--------:|:------------:|
| create accion_formativa_ei | X | | | |
| view accion_formativa_ei | X | X | X | |
| edit accion_formativa_ei | X | | | |
| manage vobo_sae | X | | | |
| create sesion_programada_ei | X | X | | |
| view sesion_programada_ei | X | X | X | X |
| edit sesion_programada_ei | X | X | | |
| inscribir participante sesion | X | X | | X (self) |
| marcar asistencia sesion | X | X | X | |
| view inscripcion_sesion_ei | X | X | X | X (own) |
| cancelar inscripcion sesion | X | X | | X (own) |
| create plan_formativo_ei | X | | | |
| view plan_formativo_ei | X | X | | |
| edit plan_formativo_ei | X | | | |
| view indicadores_esf | X | | | |
| export indicadores_esf | X | | | |

**Nota:** `administer andalucia ei` sigue siendo el super-permiso que otorga todo. Los permisos granulares permiten roles intermedios.

---

## 4. Integracion Cross-Vertical

### 4.1 Reutilizacion de jaraba_lms

**Patron:** `AccionFormativaEi.course_id` es un integer FK (ENTITY-FK-001) que referencia opcionalmente un `lms_course`. Esto permite que las acciones formativas con componente online vinculen a un curso LMS existente sin crear dependencia dura.

**Flujo:** Cuando una participante se inscribe en una sesion de una accion formativa con `course_id`:
1. `InscripcionSesionService` detecta `accion.getCourseId() !== null`
2. Si `jaraba_lms` esta disponible (`\Drupal::hasService('jaraba_lms.enrollment')`), crea una `lms_enrollment` via try-catch (PRESAVE-RESILIENCE-001)
3. La participante accede al curso desde su portal

**No se reutiliza `lms_enrollment` para la asistencia presencial** — `InscripcionSesionEi` es un concepto distinto (asistencia a sesion sincrona, no matricula en curso autoestudio).

### 4.2 Patron de jaraba_mentoring

**Se usa como referencia arquitectonica** para la recurrencia de sesiones. `availability_slot` tiene `day_of_week` (0-6), `start_time`, `end_time`, `is_recurring`, `specific_date`. Nuestro `SesionProgramadaEi` adapta este patron con JSON `recurrencia_patron` que es mas flexible.

**No se crea FK directa** porque `mentoring_session` modela una relacion 1-a-1 (mentor-mentee) que no aplica a sesiones grupales PIIL.

### 4.3 Patron de jaraba_sepe_teleformacion

**`SepeAccionFormativa` es el patron exacto** para el workflow VoBo SAE. Tiene:
- `id_accion_sepe` (codigo gobierno) → nuestro `vobo_sae_codigo`
- `estado` (pendiente → autorizada → en_curso → finalizada) → nuestro `vobo_sae_status` (mas estados para subsanacion)
- `numero_horas` → nuestro `horas_previstas`
- `modalidad` → nuestro `modalidad`
- `course_id` (integer FK) → nuestro `course_id`

### 4.4 Integracion con jaraba_legal_calendar

**`CalendarConnection`** (OAuth con Google Calendar / Outlook) se usa opcionalmente para sincronizar sesiones programadas con el calendario externo del facilitador y/o la participante.

**Inyeccion:** `@?jaraba_legal_calendar.calendar_sync` en `SesionProgramadaService`. Si el servicio no esta disponible, la calendarizacion funciona sin sincronizacion externa.

### 4.5 Andalucia +ei como Vertical Temporal: Estrategia de Acceso Completo

#### El concepto de Vertical Temporal

Andalucia +ei **NO es un vertical permanente** como `empleabilidad` o `emprendimiento`. Es un **programa subvencionado con duracion limitada** (52 semanas por cohorte, extensible a 18 meses con seguimiento). Esto lo convierte en un **vertical temporal** con tres funciones estrategicas:

1. **Funcion social (obligacion normativa):** Maximizar el impacto en colectivos vulnerables (mujeres, +45, larga duracion, discapacidad). La normativa PIIL CV 2025 exige que las personas atendidas reciban formacion (50h), orientacion (10h), y apoyo a la insercion laboral o al emprendimiento. Cada participante atendida genera un modulo economico de 3.500 EUR de justificacion. **Cuantas mas herramientas usen, mayor impacto demostrable y mejor justificacion economica.**

2. **Funcion de testing (valor para el SaaS):** Las participantes del programa son **usuarios reales con necesidades reales** en un contexto de acompanamiento profesional (orientadores, coordinador, formadores). Es el escenario ideal para probar en produccion las herramientas de empleabilidad y emprendimiento con usuarios reales que ademas reciben soporte humano. El feedback de estas participantes es oro puro para mejorar el SaaS.

3. **Funcion de adquisicion (semilla de clientes de pago):** Tras finalizar el programa (fase seguimiento → alumni), las participantes conservan sus cuentas. Si durante el programa han construido un Perfil Profesional completo, un CV de calidad, un Business Model Canvas, un plan financiero, y han recibido mentoring — **el coste de abandono es alto**. La transicion natural es convertirse en usuarias de pago del plan Starter (9.90 EUR/mes) o Professional (29.90 EUR/mes). Las herramientas que usen durante el programa con financiacion publica se convierten en **lock-in positivo**.

#### Diagnostico: Estado actual del acceso

**Problema detectado:** Las participantes estan **aisladas** en el vertical `andalucia_ei`:

| Herramienta | Estado actual | Impacto |
|-------------|--------------|---------|
| Portal participante (+ei) | Accesible | Solo muestra datos del programa PIIL |
| Perfil Profesional (jaraba_candidate) | NO accesible | Participantes no construyen su marca profesional |
| CV Builder (5 plantillas) | NO accesible | No generan CV profesional durante el programa |
| Bolsa de Empleo (jaraba_job_board) | NO accesible | No buscan empleo desde la plataforma |
| Matching Semantico (Qdrant) | NO accesible | No reciben recomendaciones de empleo |
| Self-Discovery (RIASEC, Life Wheel) | NO accesible | Herramientas de autoconocimiento inutilizadas |
| Copilot de Empleabilidad (Gen 2) | NO accesible | 6 modos de IA desaprovechados |
| Business Model Canvas | Solo via bridge | Limitado a plan emprendimiento basico |
| MVP Validation | NO accesible | No validan hipotesis de negocio |
| Proyecciones Financieras | NO accesible | No modelan flujos de caja |
| SROI Calculator | NO accesible | No miden impacto social |
| Diagnostico Digital | NO accesible | No evaluan madurez digital |
| Rutas de Digitalizacion | NO accesible | No siguen itinerarios adaptados |
| Credenciales Emprendimiento | NO accesible | No obtienen insignias de progreso |
| Marketplace de Mentores | NO accesible | Solo orientadores asignados |
| Alertas de Empleo | NO accesible | No reciben oportunidades proactivas |
| LinkedIn Import | NO accesible | No importan perfil profesional existente |

**Resultado:** El 80% del ecosistema SaaS esta inaccesible para las personas que mas lo necesitan y que ademas estan financiadas publicamente para usarlo.

#### Decision estrategica: Acceso total por programa

**Las participantes de Andalucia +ei DEBEN tener acceso completo a las herramientas de empleabilidad y emprendimiento durante la vigencia del programa**, sin necesidad de que el tenant compre addons adicionales. El programa subvencionado actua como un **"Professional Plan temporal"** que desbloquea todas las herramientas relevantes segun el carril de la participante:

| Carril | Herramientas empleabilidad | Herramientas emprendimiento |
|--------|---------------------------|----------------------------|
| **Impulso Digital** (empleabilidad) | TODAS | Diagnostico Digital + Rutas Digitalizacion (complementario) |
| **Acelera Pro** (emprendimiento) | Perfil + CV + Self-Discovery (base) | TODAS |
| **Hibrido** (mixto) | TODAS | TODAS |

**Justificacion normativa:** El programa es **mixto (empleabilidad + emprendimiento)** por diseno. La Orden 29/09/2023 establece tres vias de insercion: cuenta ajena, cuenta propia, y cooperativa. Las herramientas de emprendimiento son tan necesarias como las de empleabilidad — negarlas contradice el proposito del programa.

#### Ciclo de vida del acceso

```
INGRESO AL PROGRAMA                    FIN PROGRAMA              CONVERSION
       │                                     │                       │
       ▼                                     ▼                       ▼
┌──────────────┐  52 semanas  ┌──────────────────────┐  ┌───────────────────────┐
│ Fase Acogida │─────────────>│ Fase Seguimiento     │─>│ Alumni / Cliente pago  │
│              │              │ (6 meses post)       │  │                       │
│ Se activa:   │              │                      │  │ Conserva:             │
│ - Plan Prof. │              │ Mantiene acceso      │  │ - Perfil Profesional  │
│   temporal   │              │ completo con banner: │  │ - CV (solo lectura)   │
│ - Todas las  │              │ "Tu acceso expira    │  │ - BMC (solo lectura)  │
│   herramient.│              │  el [fecha]"         │  │ - Datos Self-Discovery│
│   del carril │              │                      │  │ - Badges obtenidos    │
└──────────────┘              │ Ofrece:              │  │                       │
                              │ - Plan Starter 9.90€ │  │ Si paga:              │
                              │ - Plan Prof. 29.90€  │  │ - Acceso total        │
                              │ - Trial 30 dias      │  │ - Datos migrados      │
                              └──────────────────────┘  └───────────────────────┘
```

**Post-programa (sin conversion):** Las participantes conservan sus datos en modo lectura (CV, BMC, Self-Discovery). Pueden verlos pero no editarlos. Las herramientas activas (matching, job board, copilot) se desactivan. Esto crea una friccion positiva: "tienes un CV excelente creado con IA, pero necesitas un plan para seguir actualizandolo".

### 4.6 Inventario de herramientas a habilitar por carril

#### Carril Impulso Digital (empleabilidad pura)

**Modulo `jaraba_candidate` (Perfil Profesional + CV Builder):**
- Perfil completo: datos personales, experiencia, educacion, idiomas, habilidades
- CV Builder con 5 plantillas (modern, classic, creative, minimal, tech)
- Importacion de LinkedIn
- Profile Completion % (indicador de progreso)
- Perfil publico compartible (URL unica)

**Modulo `jaraba_job_board` (Bolsa de Empleo):**
- Busqueda avanzada de ofertas con filtros
- Postulacion directa (one-click apply)
- Ofertas guardadas y favoritas
- Alertas de empleo automaticas por criterios
- Tracking de candidaturas enviadas
- Web Push notifications de nuevas ofertas

**Modulo `jaraba_matching` (Matching Semantico):**
- Matching bidireccional candidato ↔ oferta via embeddings Qdrant
- Score de compatibilidad visible en cada oferta
- Recomendaciones IA de ofertas relevantes
- Feedback loop para mejorar recomendaciones

**Modulo `jaraba_self_discovery` (Autoconocimiento Profesional):**
- Test RIASEC de intereses profesionales
- Life Wheel (rueda de la vida laboral)
- Timeline profesional (hitos de carrera)
- Evaluacion de fortalezas
- Resultados integrados con Copilot para orientacion personalizada

**Modulo `jaraba_copilot_v2` con SmartEmployabilityCopilotAgent (Gen 2):**
- 6 modos: orientacion profesional, optimizacion CV, preparacion entrevistas, desarrollo competencias, estrategia busqueda empleo, marca profesional
- Modelo routing (Haiku/Sonnet/Opus segun complejidad)
- Memoria a largo plazo (contexto acumulado)
- Acciones proactivas (sugerencias push)

**Modulo `jaraba_diagnostic` (Diagnostico Empleabilidad Express):**
- Scoring express: LinkedIn 40%, CV 35%, Posicionamiento 25%
- Puntuacion 0-10 con recomendaciones
- Plan de mejora personalizado

**Complementarios del emprendimiento:**
- Diagnostico de Madurez Digital (jaraba_diagnostic)
- Rutas de Digitalizacion personalizadas (jaraba_paths)

#### Carril Acelera Pro (emprendimiento puro)

**Modulo `jaraba_business_tools` (Motor de Emprendimiento):**
- Business Model Canvas interactivo (9 bloques, versionado, exportacion PDF)
- Analisis IA del canvas (sugerencias por bloque)
- Generacion IA de canvas desde idea de negocio
- Templates de canvas por sector
- MVP Validation (6 tipos: landing, smoke test, concierge, wizard of oz, entrevista, encuesta)
- Proyecciones Financieras (3 escenarios: pesimista, realista, optimista)
- Calculadora SROI (retorno social de la inversion)
- Analisis competitivo
- Business Copilot (5 capacidades: canvas analysis, competitive insights, digitalization, financials, mvp coaching)

**Modulo `jaraba_diagnostic` (Diagnostico Madurez Digital):**
- Evaluacion multidimensional (presencia, operaciones, ventas, equipo)
- Scoring 0-100 por sector
- Recomendaciones personalizadas con plan de accion
- Informe PDF exportable

**Modulo `jaraba_paths` (Rutas de Digitalizacion):**
- Itinerarios personalizados por nivel de madurez
- 3 fases Jaraba: Diagnostico → Accion → Optimizacion
- Modulos con pasos accionables
- Tracking de progreso per-user

**Modulo `jaraba_credentials_emprendimiento` (Credenciales):**
- Catalogo de credenciales de emprendimiento
- Tracking de progreso hacia cada credencial
- Journey milestones (hitos del viaje emprendedor)
- Nivel de expertise visible

**Modulo `jaraba_funding` (Financiacion Europea):**
- Tracker de convocatorias activas (Kit Digital, PRTR, FSE+)
- Gestion de solicitudes
- Generacion automatica de memorias tecnicas
- Control presupuestario
- Calculadora de impacto

**Complementarios de empleabilidad:**
- Perfil Profesional basico (nombre, experiencia, habilidades)
- CV Builder (para presentarse a inversores, partners, clientes)
- Self-Discovery (autoconocimiento del perfil emprendedor)

#### Carril Hibrido (acceso total)

Acceso a TODAS las herramientas de ambos carriles sin restriccion. Es el carril por defecto para participantes que aun no han definido su via de insercion.

### 4.7 Arquitectura tecnica del acceso por programa

#### Nuevo servicio: `ProgramaVerticalAccessService`

**Machine name:** `jaraba_andalucia_ei.programa_vertical_access`

**Proposito:** Resuelve si una participante del programa tiene acceso a herramientas de un vertical determinado, sin requerir addon de pago. Es un override del gate normal de `FeatureAccessService` para participantes activas del programa.

**Dependencias:**
- `@entity_type.manager` (hard)
- `@logger.channel.jaraba_andalucia_ei` (hard, LOGGER-INJECT-001)
- `@?jaraba_billing.feature_access` (opcional, OPTIONAL-CROSSMODULE-001)
- `@?ecosistema_jaraba_core.tenant_context` (opcional)

**Interfaz:**

```php
interface ProgramaVerticalAccessInterface {

  /**
   * Determina si el usuario tiene acceso a un vertical via programa activo.
   *
   * @param int $uid User ID.
   * @param string $vertical Vertical machine name (empleabilidad, emprendimiento).
   *
   * @return bool TRUE si el usuario es participante activo y su carril incluye el vertical.
   */
  public function hasAccessViaPrograma(int $uid, string $vertical): bool;

  /**
   * Devuelve los verticales desbloqueados para una participante activa.
   *
   * @param int $uid User ID.
   *
   * @return array<string, array{vertical: string, reason: string, expires: ?string}>
   *   Verticales activos con motivo y fecha de expiracion.
   */
  public function getVerticalesDesbloqueados(int $uid): array;

  /**
   * Determina si el acceso via programa ha expirado.
   *
   * @param int $uid User ID.
   *
   * @return bool TRUE si el programa ha finalizado y el acceso temporal ha caducado.
   */
  public function hasAccessExpired(int $uid): bool;

  /**
   * Devuelve la fecha de expiracion del acceso al programa.
   *
   * @param int $uid User ID.
   *
   * @return ?\DateTimeInterface NULL si no tiene programa activo.
   */
  public function getAccessExpiration(int $uid): ?\DateTimeInterface;
}
```

**Logica de resolucion por carril:**

```php
private const CARRIL_VERTICALS = [
  'impulso_digital' => ['empleabilidad', 'emprendimiento_basico'],
  'acelera_pro' => ['emprendimiento', 'empleabilidad_basico'],
  'hibrido' => ['empleabilidad', 'emprendimiento'],
];

// empleabilidad_basico = perfil + cv + self-discovery (sin job board, sin matching)
// emprendimiento_basico = diagnostico + rutas digitalizacion (sin BMC, sin projections)
```

**Calculo de expiracion:**

```php
public function getAccessExpiration(int $uid): ?\DateTimeInterface {
  $participante = $this->getParticipanteActivo($uid);
  if (!$participante) {
    return NULL;
  }

  // Si fase activa (acogida...insercion): acceso indefinido mientras dure
  $fase = $participante->get('fase_actual')->value;
  if (in_array($fase, ['acogida', 'diagnostico', 'atencion', 'insercion'], TRUE)) {
    return NULL; // Sin fecha de expiracion
  }

  // Si fase seguimiento: 6 meses desde fecha de insercion
  if ($fase === 'seguimiento') {
    $fechaInsercion = $participante->get('fecha_insercion')->value;
    if ($fechaInsercion) {
      return (new \DateTimeImmutable($fechaInsercion))->modify('+6 months');
    }
  }

  // Si fase alumni o baja: expirado
  return new \DateTimeImmutable('yesterday');
}
```

#### Integracion con `FeatureAccessService`

La integracion es limpia: `FeatureAccessService` ya tiene un chain de resolucion (plan base → addon → legacy → vertical). Se anade un paso intermedio via EventSubscriber o hook:

**Patron recomendado:** Modificar `AndaluciaEiCrossVerticalBridgeService` para registrar un `access_override` en el State API cuando la participante se activa. `FeatureAccessService` ya consulta overrides.

**Alternativa mas limpia (preferida):** Inyectar `@?jaraba_andalucia_ei.programa_vertical_access` en `FeatureAccessService` como dependencia opcional, y consultarlo como ultimo recurso antes de denegar:

```php
// En FeatureAccessService::canAccess()
// ... despues de verificar plan, addons, legacy, verticals ...

// Paso 5: Acceso via programa subvencionado (vertical temporal)
if ($this->programaVerticalAccess
    && $this->programaVerticalAccess->hasAccessViaPrograma($userId, $feature)) {
  return TRUE;
}

return FALSE;
```

**Verificacion CONTAINER-DEPS-002:** No crea ciclo: `FeatureAccessService` (jaraba_billing) ← `ProgramaVerticalAccessService` (jaraba_andalucia_ei). Direccion unica.

**Verificacion OPTIONAL-CROSSMODULE-001:** `@?jaraba_andalucia_ei.programa_vertical_access` en services.yml de `jaraba_billing`.

#### Metricas de uso para justificacion

`ProgramaVerticalAccessService` registra cada acceso desbloqueado via State API:

```php
$key = "programa_vertical_access:{$uid}:{$vertical}";
$state->set($key, [
  'first_access' => $state->get($key)['first_access'] ?? time(),
  'last_access' => time(),
  'access_count' => ($state->get($key)['access_count'] ?? 0) + 1,
]);
```

Estas metricas alimentan:
- **Justificacion economica:** "X participantes usaron herramientas de empleabilidad Y veces"
- **KPIs FSE+:** Indicadores de impacto digital demostrable
- **Conversion:** "X% de participantes con >50 accesos se convirtieron en clientes de pago"

#### Banner de expiracion y conversion

Cuando `getAccessExpiration()` devuelve una fecha a <30 dias del presente:

```twig
{# _programa-expiration-banner.html.twig #}
{% if programa_days_remaining is not null and programa_days_remaining <= 30 %}
<div class="programa-expiration-banner" role="alert">
  {{ jaraba_icon('status', 'clock', { variant: 'duotone', color: 'naranja-impulso', size: '20px' }) }}
  <div class="programa-expiration-banner__content">
    <strong>{% trans %}Tu acceso al programa expira en {{ programa_days_remaining }} dias{% endtrans %}</strong>
    <p>{% trans %}Conserva todas tus herramientas y datos con un plan profesional.{% endtrans %}</p>
  </div>
  <a href="{{ upgrade_url }}" class="ej-btn ej-btn--primary ej-btn--sm">
    {% trans %}Ver planes{% endtrans %}
  </a>
</div>
{% endif %}
```

---

## 5. Workflow VoBo SAE

### 5.1 Maquina de estados

```
                    ┌─────────────┐
                    │  borrador   │ ← Estado inicial al crear accion
                    └──────┬──────┘
                           │ prepararSolicitud()
                    ┌──────▼──────────────────┐
                    │ pendiente_documentacion  │ ← Documentacion generada (PDF)
                    └──────┬──────────────────┘
                           │ enviarASae()
                    ┌──────▼──────┐
                    │ enviado_sae │ ← Fecha envio registrada
                    └──────┬──────┘
                           │ (SAE recibe y registra)
                    ┌──────▼──────────┐
                    │ pendiente_vobo  │ ← Esperando respuesta SAE
                    └──────┬──────────┘
                           │
              ┌────────────┼────────────┐
              │            │            │
       ┌──────▼──────┐ ┌──▼────────┐ ┌─▼────────┐
       │  aprobado   │ │ rechazado │ │ caducado  │
       │ (codigo SAE)│ │ (motivo)  │ │ (>30 dias)│
       └─────────────┘ └──────┬────┘ └─────┬─────┘
                              │             │
                       ┌──────▼──────────┐  │
                       │ en_subsanacion  │  │
                       └──────┬──────────┘  │
                              │             │
                       ┌──────▼──────┐      │
                       │ enviado_sae │◄─────┘ (reenvio)
                       └─────────────┘
```

**Transiciones validas (constante en `VoboSaeWorkflowService`):**

```php
private const TRANSITIONS = [
  'borrador' => ['pendiente_documentacion'],
  'pendiente_documentacion' => ['enviado_sae'],
  'enviado_sae' => ['pendiente_vobo'],
  'pendiente_vobo' => ['aprobado', 'rechazado', 'caducado'],
  'rechazado' => ['en_subsanacion'],
  'en_subsanacion' => ['enviado_sae'],
  'caducado' => ['enviado_sae'],
  'aprobado' => [],  // Estado terminal
];
```

### 5.2 Generacion de documentacion

`VoboSaeWorkflowService::generarDocumentoSolicitud()` genera un PDF con los datos de la accion formativa que el Coordinador debe enviar al SAE. El PDF se almacena como `ExpedienteDocumento` con categoria `'formacion_vobo_sae'` (ya definida en `ExpedienteDocumento::CATEGORIAS`).

**Datos incluidos en el PDF:**
- Titulo y descripcion de la accion
- Objetivos competenciales
- Nombre y titulacion del formador
- Calendario previsto (si hay sesiones programadas)
- Modalidad y lugar
- Numero de horas
- Listado de participantes previstos (DNI/NIE, colectivo)

### 5.3 Alertas y escalamiento

Extension de `AlertasNormativasService` con nuevas alertas:

| Alerta | Trigger | Nivel | Accion |
|--------|---------|-------|--------|
| VoBo pendiente > 15 dias | `vobo_sae_status === 'pendiente_vobo'` AND fecha_solicitud + 15 dias < hoy | alto | "Accion [titulo] lleva [N] dias sin VoBo SAE" |
| VoBo caducado | `vobo_sae_status === 'pendiente_vobo'` AND fecha_solicitud + 30 dias < hoy | critico | Auto-transiciona a `caducado` |
| Acciones sin VoBo proximo inicio | Sesiones programadas en < 20 dias AND accion no aprobada | critico | "Sesiones de [titulo] programadas sin VoBo aprobado" |
| Plan formativo incompleto | Plan con `cumple_minimo_formacion === FALSE` | medio | "Plan [carril] no alcanza 50h de formacion" |

---

## 6. Integracion IA Nativa

### 6.1 IA para el coordinador

**Generacion de esquemas formativos:** El Coordinador al crear una `AccionFormativaEi` puede solicitar asistencia IA (boton "Sugerir con IA" en el formulario). El sistema envia al Copilot (tier balanced — Sonnet 4.6) los datos del carril, la fase, y el tipo de formacion, y recibe sugerencias de:
- Objetivos competenciales
- Contenido programatico (temas y subtemas)
- Duracion recomendada
- Metodologia (Kolb, De Bono — referenciando los materiales de la 1a edicion)

**Implementacion:** Extension de `AndaluciaEiCopilotContextProvider` con nuevo metodo `getAccionFormativaContext()` que enriquece el prompt con datos del programa.

### 6.2 IA para la participante

**Tutor IA formativo:** Si la accion formativa tiene `course_id`, el `LearningTutorAgent` (existente en `jaraba_lms`) proporciona tutoria IA sobre los contenidos del curso. El contexto del participante PIIL (carril, fase, horas, barreras) se inyecta via `AndaluciaEiCopilotContextProvider`.

**Recomendacion de sesiones:** Copilot (tier fast — Haiku 4.5) analiza las horas faltantes del participante, su carril, y las sesiones disponibles, y recomienda a cuales inscribirse.

### 6.3 IA para la justificacion

**Documentacion VoBo asistida:** Al generar el PDF para SAE, la IA (tier balanced) revisa la documentacion y sugiere mejoras en los objetivos competenciales para alinearse con la normativa PIIL.

---

## 6b. Compliance ESF+ e Indicadores de Impacto

> **Benchmark:** Reglamento ESF+ (UE) 2021/1057, WIOA Performance Indicators (EE.UU.), Bonterra Apricot (case management + outcomes). La mayoria de entidades espanolas usan Excel para reportar a la CE. Automatizar esto es un game-changer.

### 6b.1 Indicadores comunes ESF+ nativos

El Fondo Social Europeo Plus exige **14 indicadores de output** y **6 de resultado** por participante. Estos se calculan automaticamente desde campos existentes de `ProgramaParticipanteEi` + nuevos campos del Sprint 13.

**Indicadores de output (al entrar al programa) — ya disponibles o facilmente derivables:**

| # | Indicador ESF+ | Campo fuente en ProgramaParticipanteEi | Derivacion |
|---|---------------|---------------------------------------|------------|
| CO01 | Desempleados (incl. larga duracion) | `situacion_laboral` | valor IN ('desempleado', 'larga_duracion') |
| CO02 | Desempleados de larga duracion | `situacion_laboral` | valor = 'larga_duracion' |
| CO03 | Inactivos | `situacion_laboral` | valor = 'inactivo' |
| CO04 | Empleados (incl. por cuenta propia) | `situacion_laboral` | valor IN ('cuenta_ajena', 'cuenta_propia') |
| CO05 | Menores de 30 | `fecha_nacimiento` | edad < 30 al inicio |
| CO06 | Mayores de 54 | `fecha_nacimiento` | edad > 54 al inicio |
| CO07 | Con educacion primaria (ISCED 0-2) | `nivel_educativo` | valor IN ('sin_estudios', 'primaria', 'eso') |
| CO08 | Con educacion secundaria (ISCED 3-4) | `nivel_educativo` | valor IN ('bachillerato', 'fp_medio') |
| CO09 | Con educacion terciaria (ISCED 5-8) | `nivel_educativo` | valor IN ('fp_superior', 'grado', 'master', 'doctorado') |
| CO10 | Nacionales de terceros paises | `nacionalidad` | pais NOT IN (UE-27 + EEE) |
| CO11 | De origen extranjero / minorias | `colectivo_vulnerable` | contiene 'migrante' o 'minoria_etnica' |
| CO12 | Con discapacidad | `discapacidad` | valor > 0 o campo boolean TRUE |
| CO13 | Sin hogar o exclusion vivienda | `colectivo_vulnerable` | contiene 'sin_hogar' o 'exclusion_vivienda' |
| CO14 | De zonas rurales | `codigo_postal` + lookup INE | postal en municipio < 20.000 hab. |

**Indicadores de resultado (a la salida / 6 meses) — nuevos campos necesarios:**

| # | Indicador ESF+ | Donde se registra | Momento |
|---|---------------|-------------------|---------|
| CR01 | Participantes en educacion/formacion a la salida | `InsercionLaboral.tipo_insercion` = 'formacion' | Al crear InsercionLaboral |
| CR02 | Participantes que obtienen cualificacion | `ProgramaParticipanteEi.cualificacion_obtenida` (nuevo campo boolean) | Al finalizar formacion |
| CR03 | Participantes empleados a la salida | `InsercionLaboral.tipo_insercion` IN ('cuenta_ajena', 'cuenta_propia', 'cooperativa') | Al crear InsercionLaboral |
| CR04 | Participantes empleados 6 meses despues | `SeguimientoPostPrograma.situacion_6m` (nuevo) | Encuesta automatica a 6 meses |
| CR05 | Participantes con mejora situacional | `SeguimientoPostPrograma.mejora_situacion` (nuevo) | Encuesta automatica a 6 meses |
| CR06 | Participantes en busqueda empleo a la salida | `ProgramaParticipanteEi.fase_actual` = 'insercion' AND no insertado | Al finalizar programa |

**Implementacion tecnica:** Nuevo servicio `IndicadoresEsfService` con metodo `calcularIndicadores(int $tenantId, ?string $edicion): array` que devuelve los 20 indicadores. Se expone en dashboard financiador y en export CSV/JSON formato SFC2021.

### 6b.2 Plan Individual de Insercion (PII) digital

> **Benchmark:** Bonterra Apricot — "Service Plan" con objetivos, acciones, plazos, responsables. WIOA — "Individual Employment Plan (IEP)".

La normativa PIIL exige que cada participante tenga un itinerario individualizado documentado. En la 1a edicion se hacia en Word. El SaaS lo digitaliza.

**Implementacion:** Nuevo campo JSON `plan_individual_insercion` en `ProgramaParticipanteEi` (NO nueva entidad — evitar proliferacion innecesaria). Estructura:

```json
{
  "version": 1,
  "fecha_elaboracion": "2026-04-15",
  "orientador_id": 42,
  "objetivo_general": "Insercion por cuenta ajena en sector tecnologico",
  "objetivos_especificos": [
    {
      "id": 1,
      "descripcion": "Completar formacion digital (50h)",
      "plazo": "2026-06-30",
      "responsable": "orientador",
      "estado": "en_progreso",
      "acciones": [
        {"descripcion": "Inscribirse en modulo Impulso Digital", "completada": true},
        {"descripcion": "Completar evaluacion competencias", "completada": false}
      ]
    },
    {
      "id": 2,
      "descripcion": "Actualizar CV y perfil profesional",
      "plazo": "2026-05-15",
      "responsable": "participante",
      "estado": "pendiente",
      "acciones": []
    }
  ],
  "barreras_identificadas": ["conciliacion", "transporte"],
  "adaptaciones": ["horario_flexible", "sesiones_online"],
  "firma_participante": null,
  "firma_orientador": null,
  "revisiones": [
    {"fecha": "2026-04-15", "autor": "orientador", "cambios": "Elaboracion inicial"}
  ]
}
```

**Frontend:** Seccion dedicada en el portal de la participante + tab en el hub del coordinador. Edicion via slide-panel con formulario dinámico que renderiza el JSON como UI usable.

**Indicador ESF+ vinculado:** El PII es evidencia de "acompanamiento individualizado" — requisito para justificar el modulo economico de 3.500 EUR.

### 6b.3 Seguimiento a 6 meses post-programa

**Problema:** El ESF+ exige resultado a 6 meses (CR04, CR05) pero actualmente no existe mecanismo automatizado.

**Implementacion:**

1. **Campo `fecha_fin_programa`** en ProgramaParticipanteEi (populated al pasar a fase `seguimiento`)
2. **Cron job** via `hook_cron()`: A los 6 meses de `fecha_fin_programa`, genera encuesta automatica
3. **Encuesta minima** (3 preguntas, formulario Drupal, enlace unico por participante):
   - "¿Cual es tu situacion laboral actual?" (desempleado / cuenta ajena / cuenta propia / formacion / inactivo)
   - "¿Ha mejorado tu situacion respecto a antes del programa?" (si / parcialmente / no)
   - "¿Recomendarias el programa?" (0-10, NPS)
4. **Canales de envio:** Email + SMS (via EiPushNotificationService, prioridad ALTA). Recordatorio a los 7 dias si no responde. Segundo recordatorio a los 14 dias.
5. **Almacenamiento:** Campo JSON `seguimiento_post_programa` en ProgramaParticipanteEi (similar al PII)
6. **Fallback:** Si no responde en 30 dias, el orientador contacta por telefono y registra manualmente

**Metrica de conversion (seccion 4.5):** La encuesta de 6 meses incluye CTA: "Sigue usando las herramientas del programa — Plan Starter desde 9.90 EUR/mes"

### 6b.4 Dashboard de resultados para financiador

**Ruta:** `/andalucia-ei/impacto/financiador` (acceso con permiso `view indicadores_esf`)

**Contenido:**

| Seccion | Datos | Visualizacion |
|---------|-------|---------------|
| KPIs principales | Total participantes, tasa insercion, horas formacion media | 4 tarjetas KPI (_kpi-card.html.twig reutilizado) |
| Indicadores ESF+ output | 14 indicadores CO01-CO14 con desagregacion sexo | Tabla con barras de progreso |
| Indicadores ESF+ resultado | 6 indicadores CR01-CR06 | Tabla con % y variacion vs periodo anterior |
| Distribucion por carril | Impulso Digital / Acelera Pro / Hibrido | Donut chart (SVG simple, sin dependencia JS pesada) |
| Timeline del programa | Hitos, fases completadas, VoBo SAE | Timeline vertical (_participante-timeline.html.twig adaptado) |
| Cohort analysis | Comparacion entre ediciones (si aplica) | Tabla comparativa |
| Export | CSV, JSON (formato SFC2021), PDF resumen | 3 botones de descarga |

**Patron zero-region** (ZERO-REGION-001): Template `coordinador-impacto-financiador.html.twig` con `clean_content`. Variables via `hook_preprocess_page()`.

---

## 6c. UX Inclusivo para Colectivos Vulnerables

> **Contexto critico:** Las participantes del programa son colectivos vulnerables (mujeres, +45, larga duracion, discapacidad, migrantes). El 70%+ accedera desde movil. Muchas tienen baja alfabetizacion digital. La Ley Europea de Accesibilidad (EAA) esta en vigor desde junio 2025, haciendo WCAG 2.2 AA legalmente obligatorio.
>
> **Benchmark:** WCAG 2.2 AA (9 criterios nuevos vs 2.1), Bonterra (formularios accesibles para servicios sociales), plain language guidelines (nivel B1/A2).

### 6c.1 Lenguaje claro (nivel B1/A2)

**Principio:** Todo texto de interfaz DEBE ser comprensible por una persona con nivel B1 de espanol. Frases cortas (max 20 palabras), vocabulario cotidiano, sin jerga tecnica.

**Reglas para labels y descripciones en las 4 nuevas entidades:**

| Jerga tecnica | Lenguaje claro |
|--------------|----------------|
| "Accion formativa" | "Curso o taller" (en contexto participante) |
| "Inscripcion sesion" | "Apuntarme a una sesion" |
| "VoBo SAE" | "Aprobacion del Servicio de Empleo" (en contexto participante) |
| "Modalidad online_videoconf" | "Por videollamada" |
| "Fase diagnostico" | "Fase de conocerte mejor" |
| "Expediente documental" | "Tus documentos" |
| "Actuacion STO" | (invisible para participante — solo admin) |
| "Campo requerido" | "Este dato es necesario" |
| "Validacion fallida" | "Hay algo que corregir" |

**Implementacion:** Labels de admin mantienen terminologia tecnica. Labels de frontend (portal participante) usan vocabulario claro. Dos capas de $this->t():
- Admin: `$this->t('Accion Formativa')` (default)
- Frontend: preprocess inyecta labels amigables en variables Twig

### 6c.2 Ayuda contextual en formularios

**Patron:** Todo campo visible para participantes incluye `setDescription()` con texto de ayuda claro + ejemplo.

**Ejemplo en BaseFieldDefinition:**

```php
$fields['titulo'] = BaseFieldDefinition::create('string')
  ->setLabel(t('Titulo del curso o taller'))
  ->setDescription(t('Un nombre corto que describa el contenido. Ejemplo: "Informatica basica para el empleo"'))
  ->setRequired(TRUE);
```

**En formularios PremiumEntityFormBase**, los campos con ayuda muestran icono de interrogacion expandible (patron ya existente en el tema via `.form-item__description`).

**Regla para Sprint 13:** CADA campo de las 4 entidades nuevas DEBE tener `setDescription()` con texto util. No se acepta description vacia o generica ("Introduzca el valor").

### 6c.3 Validacion en tiempo real con refuerzo positivo

**Patron JS (Vanilla, Drupal.behaviors):**

```javascript
Drupal.behaviors.realtimeValidation = {
  attach(context) {
    const fields = once('rt-validate', '[data-validate]', context);
    fields.forEach(field => {
      field.addEventListener('blur', () => {
        const rule = field.dataset.validate;
        const isValid = validateField(field, rule);
        const wrapper = field.closest('.form-item');
        wrapper.classList.toggle('form-item--valid', isValid);
        wrapper.classList.toggle('form-item--invalid', !isValid && field.value);
        // Refuerzo positivo
        if (isValid) {
          const feedback = wrapper.querySelector('.form-item__feedback');
          if (feedback) feedback.textContent = Drupal.t('Correcto');
        }
      });
    });
  }
};
```

**Campos con validacion RT en las 4 entidades nuevas:** horas_previstas (>0, <=200), fecha (futuro para sesiones), hora_inicio < hora_fin, max_plazas (>0).

**Accesibilidad:** `aria-invalid="true/false"` + `aria-describedby` apuntando al mensaje de feedback. `role="alert"` en mensajes de error para lectores de pantalla.

### 6c.4 Mobile-first y touch-friendly

**Reglas para Sprint 13 (todos los templates nuevos):**

1. **Touch targets minimo 48x48px** (WCAG 2.5.8 Target Size — objetivo 44px minimo, 48px recomendado). Botones "Inscribirme", "Cancelar", "Marcar asistencia" DEBEN tener `min-height: 48px; min-width: 48px`
2. **Layout mobile-first** en SCSS: estilos base para movil, `@media (min-width: 768px)` para desktop
3. **Formularios de 1 columna** en movil: `grid-template-columns: 1fr` en `@media (max-width: 768px)`
4. **Bottom-sticky CTA** en portal participante: "Proximo paso: [accion]" fijo en la parte inferior
5. **Scroll horizontal PROHIBIDO**: tablas responsive con patron card-stack en movil
6. **Swipe-to-action** en tarjetas de sesion: swipe derecha = inscribirse, swipe izquierda = mas info (Vanilla JS, NO dependencia externa)

**SCSS Sprint 13 (adicion a `coordinador-programa.scss`):**

```scss
// Touch-friendly (WCAG 2.5.8)
.session-card__enroll,
.session-card__cancel,
.inscripcion-button {
  min-block-size: 48px;
  min-inline-size: 48px;
  padding: var(--ej-spacing-sm, $ej-spacing-sm) var(--ej-spacing-md, $ej-spacing-md);
}

// Bottom-sticky CTA (mobile)
@media (max-width: 768px) {
  .participante-next-step {
    position: sticky;
    inset-block-end: 0;
    z-index: 100;
    background: var(--ej-bg-surface, $ej-bg-surface);
    border-block-start: 1px solid var(--ej-color-border, $ej-color-border);
    padding: var(--ej-spacing-sm, $ej-spacing-sm);
    box-shadow: var(--ej-shadow-lg, $ej-shadow-lg);
  }
}
```

### 6c.5 Preferencias de accesibilidad del usuario

**Nuevo parcial** `_a11y-preferences.html.twig` (incluido en perfil de usuario):

| Preferencia | Default | Opciones | Persistencia |
|-------------|---------|----------|-------------|
| Tamano de fuente | Normal | Normal / Grande (+20%) / Muy grande (+40%) | localStorage + campo user |
| Contraste | Normal | Normal / Alto (forzar APCA > 90) | localStorage + campo user |
| Movimiento reducido | Auto | Auto (respeta `prefers-reduced-motion`) / Siempre reducido | localStorage |
| Fuente dislexia | OFF | ON activa OpenDyslexic via `@font-face` | localStorage + campo user |

**Implementacion:** CSS custom properties inyectadas via `hook_preprocess_html()`:

```php
// Si usuario tiene preferencia de fuente grande:
$variables['html_attributes']['class'][] = 'ej-font-lg';
// En CSS:
// .ej-font-lg { --ej-font-base: 1.2rem; }
// .ej-font-xl { --ej-font-base: 1.4rem; }
// .ej-contrast-high { --ej-color-text: #000; --ej-bg-surface: #fff; }
```

### 6c.6 Confirmacion y deshacer

**Reglas para acciones destructivas o irreversibles en Sprint 13:**

| Accion | Tipo | Patron |
|--------|------|--------|
| Eliminar accion formativa | Destructiva | Modal de confirmacion: "¿Seguro? Se perderan las sesiones vinculadas" |
| Cancelar inscripcion | Semi-reversible | Toast con Deshacer (30 segundos): "Inscripcion cancelada. Deshacer?" |
| Marcar asistencia | Irreversible (genera ActuacionSto) | Doble confirmacion: "Esto registrara X horas para la participante. ¿Confirmar?" |
| Enviar VoBo SAE | Irreversible | Modal con resumen del documento + checkbox "He revisado los datos" |
| Eliminar sesion con inscripciones | Destructiva | Modal con lista de participantes afectadas |

**Patron JS (toast con deshacer):**

```javascript
function showUndoToast(message, undoCallback, timeoutMs = 30000) {
  const toast = document.createElement('div');
  toast.className = 'ej-toast ej-toast--undo';
  toast.setAttribute('role', 'alert');
  toast.innerHTML = `
    <span>${Drupal.checkPlain(message)}</span>
    <button class="ej-toast__undo">${Drupal.t('Deshacer')}</button>
  `;
  // ... auto-dismiss + undo handler
}
```

---

## 7. Arquitectura Frontend y Templates

### 7.1 Templates Twig nuevos y modificados

| Template | Tipo | Proposito | Directrices |
|----------|------|----------|------------|
| `coordinador-plan-formativo.html.twig` | Nuevo | Pestana "Plan Formativo" en hub coordinador | ZERO-REGION-001, `{% trans %}` |
| `coordinador-calendario.html.twig` | Nuevo | Pestana "Calendario" en hub coordinador | ZERO-REGION-001, `{% trans %}` |
| `participante-mis-sesiones.html.twig` | Nuevo | Seccion "Mis Sesiones" en portal participante | ZERO-REGION-001, `{% trans %}` |
| `participante-sesiones-disponibles.html.twig` | Nuevo | Lista de sesiones disponibles para inscripcion | ZERO-REGION-001, `{% trans %}` |
| `coordinador-dashboard.html.twig` | Modificado | Anadir pestanas "Plan Formativo" + "Calendario" | Extender sin romper existente |
| `participante-portal.html.twig` | Modificado | Anadir seccion "Mis Sesiones" | Extender sin romper existente |

**Patron de template zero-region (cada nuevo template):**

```twig
{#
/**
 * @file Pestana Plan Formativo del Hub Coordinador.
 *
 * Variables inyectadas desde hook_preprocess_page():
 * - planes_formativos: array de PlanFormativoEi por carril
 * - acciones_formativas: array de AccionFormativaEi
 * - vobo_stats: estadisticas VoBo SAE
 *
 * Directrices: ZERO-REGION-001, TWIG-INCLUDE-ONLY-001, CSS-VAR-ALL-COLORS-001
 */
#}

<div class="hub-plan-formativo">
  <div class="hub-plan-formativo__header">
    {{ jaraba_icon('business', 'pathway', { variant: 'duotone', color: 'azul-corporativo', size: '28px' }) }}
    <h2>{% trans %}Plan Formativo{% endtrans %}</h2>
  </div>

  {# Tarjetas KPI de horas por carril #}
  <div class="hub-plan-formativo__kpis">
    {% for plan in planes_formativos %}
      {% include '@ecosistema_jaraba_theme/partials/_kpi-card.html.twig' with {
        'label': plan.titulo,
        'value': plan.horas_formacion_previstas ~ 'h',
        'target': '50h',
        'status': plan.cumple_minimo_formacion ? 'success' : 'warning',
      } only %}
    {% endfor %}
  </div>

  {# Tabla de acciones formativas con estado VoBo #}
  <div class="hub-plan-formativo__acciones">
    {# ... tabla con datos via drupalSettings + JS #}
  </div>
</div>
```

### 7.2 Parciales reutilizables

**Verificar si existen antes de crear:**
- `_kpi-card.html.twig` — ya existe en coordinador-dashboard (reutilizar)
- `_session-card.html.twig` — NUEVO parcial para mostrar sesion programada (reutilizable en hub coordinador Y portal participante)
- `_vobo-badge.html.twig` — NUEVO parcial para mostrar estado VoBo como badge colorido
- `_inscripcion-button.html.twig` — NUEVO parcial para boton de inscripcion/cancelacion con estado

**Patron de parcial (TWIG-INCLUDE-ONLY-001):**

```twig
{# _session-card.html.twig #}
{#
 * @file Tarjeta de sesion programada.
 *
 * Variables:
 * - session_title: string
 * - session_date: string (formateada)
 * - session_time: string (HH:MM - HH:MM)
 * - session_type: string (orientacion_individual, etc.)
 * - session_modality: string
 * - session_location: string
 * - session_facilitator: string
 * - session_spots_available: int|null
 * - session_spots_total: int|null
 * - session_status: string
 * - session_id: int
 * - can_enroll: bool
 #}

<div class="session-card session-card--{{ session_status }}" data-session-id="{{ session_id }}">
  <div class="session-card__header">
    <span class="session-card__date">{{ session_date }}</span>
    <span class="session-card__time">{{ session_time }}</span>
  </div>
  <h3 class="session-card__title">{{ session_title }}</h3>
  <div class="session-card__meta">
    <span class="session-card__type">{% trans %}{{ session_type }}{% endtrans %}</span>
    <span class="session-card__modality">{% trans %}{{ session_modality }}{% endtrans %}</span>
    {% if session_location %}
      <span class="session-card__location">{{ session_location }}</span>
    {% endif %}
  </div>
  {% if session_spots_total %}
    <div class="session-card__spots">
      {% trans %}{{ session_spots_available }} de {{ session_spots_total }} plazas{% endtrans %}
    </div>
  {% endif %}
  {% if can_enroll %}
    <button class="session-card__enroll ej-btn ej-btn--primary" data-action="enroll" data-session-id="{{ session_id }}">
      {% trans %}Inscribirme{% endtrans %}
    </button>
  {% endif %}
</div>
```

### 7.3 SCSS y pipeline de compilacion

**Nuevo fichero SCSS route-specific:**

Fichero: `scss/routes/coordinador-programa.scss`

```scss
@use '../variables' as *;

// =============================================
// Hub Plan Formativo + Calendario (Sprint 13)
// Usa tokens CSS --ej-* (CSS-VAR-ALL-COLORS-001)
// Dart Sass moderno: @use (NO @import)
// =============================================

.hub-plan-formativo {
  padding: var(--ej-spacing-lg, $ej-spacing-lg);

  &__header {
    display: flex;
    align-items: center;
    gap: var(--ej-spacing-sm, $ej-spacing-sm);
    margin-block-end: var(--ej-spacing-lg, $ej-spacing-lg);

    h2 {
      color: var(--ej-color-headings, $ej-color-headings);
      font-family: var(--ej-font-headings, $ej-font-headings);
      margin: 0;
    }
  }

  &__kpis {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: var(--ej-spacing-md, $ej-spacing-md);
    margin-block-end: var(--ej-spacing-xl, $ej-spacing-xl);
  }
}

.session-card {
  background: var(--ej-bg-surface, $ej-bg-surface);
  border-radius: var(--ej-card-radius, $ej-card-radius);
  box-shadow: var(--ej-shadow-sm, $ej-shadow-sm);
  padding: var(--ej-spacing-md, $ej-spacing-md);
  transition: box-shadow var(--ej-transition-fast, $ej-transition-fast);

  &:hover {
    box-shadow: var(--ej-shadow-md, $ej-shadow-md);
  }

  &__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-block-end: var(--ej-spacing-xs, $ej-spacing-xs);
  }

  &__date {
    font-weight: 600;
    color: var(--ej-color-corporate, $ej-color-corporate);
  }

  &__title {
    color: var(--ej-color-headings, $ej-color-headings);
    font-size: 1.1rem;
    margin: 0 0 var(--ej-spacing-xs, $ej-spacing-xs);
  }

  &__meta {
    display: flex;
    flex-wrap: wrap;
    gap: var(--ej-spacing-xs, $ej-spacing-xs);
    margin-block-end: var(--ej-spacing-sm, $ej-spacing-sm);
  }

  &__spots {
    font-size: 0.875rem;
    color: var(--ej-color-muted, $ej-color-muted);
    margin-block-end: var(--ej-spacing-sm, $ej-spacing-sm);
  }

  &__enroll {
    inline-size: 100%;
  }

  &--completada {
    opacity: 0.7;
  }

  &--cancelada {
    border-inline-start: 3px solid var(--ej-color-danger, #EF4444);
  }
}

// Responsive: mobile-first
@media (max-width: 768px) {
  .hub-plan-formativo__kpis {
    grid-template-columns: 1fr;
  }

  .session-card {
    padding: var(--ej-spacing-sm, $ej-spacing-sm);
  }
}
```

**Compilacion:** Anadir al `package.json` del tema en `build:routes`:

```bash
sass scss/routes/coordinador-programa.scss css/routes/coordinador-programa.css --style=compressed
```

**Registro en `ecosistema_jaraba_theme.libraries.yml`:**

```yaml
route-coordinador-programa:
  version: 1.0.0
  css:
    theme:
      css/routes/coordinador-programa.css: {}
  dependencies:
    - ecosistema_jaraba_theme/global-styling
```

**Attachment en `hook_page_attachments_alter()`:**

```php
if (str_starts_with($route, 'jaraba_andalucia_ei.coordinador')) {
  $attachments['#attached']['library'][] = 'ecosistema_jaraba_theme/route-coordinador-programa';
}
```

**Verificacion SCSS-COMPILE-VERIFY-001:** Tras compilar, verificar que `css/routes/coordinador-programa.css` tiene timestamp posterior a `scss/routes/coordinador-programa.scss`.

### 7.4 Variables CSS inyectables

Todas las variables usadas en los nuevos estilos son tokens `--ej-*` ya definidos en el sistema:

| Variable | Fallback | Uso |
|----------|----------|-----|
| `--ej-bg-surface` | `#FFFFFF` | Fondo de tarjetas de sesion |
| `--ej-card-radius` | `12px` | Bordes redondeados |
| `--ej-shadow-sm/md` | Sombras definidas | Elevacion de tarjetas |
| `--ej-color-corporate` | `#233D63` | Fechas y elementos destacados |
| `--ej-color-headings` | `#1A1A2E` | Titulos |
| `--ej-color-muted` | `#64748B` | Texto secundario |
| `--ej-color-danger` | `#EF4444` | Sesiones canceladas |
| `--ej-spacing-*` | 4px-48px | Espaciado consistente |
| `--ej-transition-fast` | `150ms ease` | Transiciones hover |
| `--ej-font-headings` | `"Outfit", sans-serif` | Tipografia titulos |

**SCSS-COMPILETIME-001:** Ninguna variable `var()` se usa dentro de funciones Sass `color.adjust/scale/change`. Para alpha se usa `color-mix(in srgb, ...)`.

**Ningun color hardcoded.** Todos con `var(--ej-*, $fallback)`.

### 7.5 Iconografia

| Contexto | Categoria | Nombre | Variante | Color |
|----------|-----------|--------|----------|-------|
| Plan Formativo (header) | business | pathway | duotone | azul-corporativo |
| Accion Formativa (form) | business | pathway | duotone | azul-corporativo |
| VoBo SAE aprobado | status | check-circle | duotone | verde-innovacion |
| VoBo SAE pendiente | status | clock | duotone | naranja-impulso |
| VoBo SAE rechazado | status | alert-circle | duotone | danger |
| Sesion programada | ui | calendar | duotone | azul-corporativo |
| Inscripcion | actions | plus | outline | azul-corporativo |
| Formador | users | user | duotone | azul-corporativo |
| Calendario | ui | calendar | duotone | naranja-impulso |

**Todos via `jaraba_icon()` en Twig (ICON-CONVENTION-001). Variante default duotone (ICON-DUOTONE-001). Colores SOLO de paleta Jaraba (ICON-COLOR-001).**

### 7.6 Accesibilidad WCAG 2.2 AA (Ley Europea de Accesibilidad — EAA)

> La Ley Europea de Accesibilidad (Directiva (UE) 2019/882) esta en vigor desde **28 junio 2025**. WCAG 2.2 AA es legalmente obligatorio para servicios digitales. WCAG 2.2 anade 9 criterios nuevos respecto a 2.1.

**Cumplimiento WCAG 2.1 AA (ya cubierto):**
- `aria-label` en todos los botones interactivos (Inscribirme, Cancelar, Marcar asistencia)
- Headings jerarquicos (`h2` → `h3` → `h4`) en templates
- Focus visible en tarjetas de sesion (`:focus-visible` con `--ej-focus-ring`)
- Navegacion por teclado en pestanas del hub (arrows, tab)
- `role="main"` en main content
- Contraste minimo 4.5:1 en todos los textos (validado con tokens `--ej-*` por defecto)

**Criterios nuevos WCAG 2.2 AA a verificar en Sprint 13:**

| Criterio | Nivel | Verificacion en Sprint 13 |
|----------|-------|--------------------------|
| 2.4.11 Focus Not Obscured | AA | Verificar que slide-panel no oculta el foco del elemento activo |
| 2.4.13 Focus Appearance | AAA (aspiracional) | Focus ring doble (outline + box-shadow) ya implementado en `_accessibility.scss` |
| 2.5.7 Dragging Movements | AA | Toda funcionalidad drag-and-drop tiene alternativa de clic. Reordenar acciones en PlanFormativo: botones arriba/abajo ademas de drag |
| 2.5.8 Target Size Minimum | AA | Todos los botones de accion >= 24x24px minimo (aspiramos a 48x48px — ver 6c.4) |
| 3.2.6 Consistent Help | A | Boton de ayuda (?) en posicion consistente top-right de cada formulario |
| 3.3.7 Redundant Entry | A | No pedir datos ya proporcionados: si participante ya tiene nombre en perfil, no pedir de nuevo en inscripcion |
| 3.3.8 Accessible Authentication | AA | Google OAuth no requiere test cognitivo. Verificar que login form tampoco |
| 3.3.9 Accessible Authentication (Enhanced) | AAA (aspiracional) | No CAPTCHA en formularios de participante |

**Herramienta de audit:** `axe-core` integrado en CI via `@axe-core/cli` + custom rule set para WCAG 2.2 AA

### 7.7 Slide-panel para CRUD

**Todas las acciones crear/editar en frontend se abren en slide-panel:**

- "Crear Accion Formativa" → slide-panel con formulario `AccionFormativaEiForm` (PremiumEntityFormBase)
- "Editar Sesion" → slide-panel con formulario `SesionProgramadaEiForm`
- "Ver detalle inscripcion" → slide-panel con vista detalle

**Patron SLIDE-PANEL-RENDER-001:**

```php
public function addAccionFormativa(Request $request): array|Response {
  $entity = $this->entityTypeManager->getStorage('accion_formativa_ei')->create([]);
  $form = $this->entityFormBuilder()->getForm($entity, 'default');

  if ($this->isSlidePanelRequest($request)) {
    $form['#action'] = $request->getRequestUri();
    $html = $this->renderer->renderPlain($form);
    return new Response((string) $html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
  }

  return $form;
}
```

**JS frontend:** URLs de slide-panel via `drupalSettings` (ROUTE-LANGPREFIX-001, NUNCA hardcoded). CSRF token cacheado 1h (CSRF-JS-CACHE-001).

### 7.8 Internacionalizacion

**Todos los textos de interfaz con bloques `{% trans %}`:**

```twig
{% trans %}Plan Formativo{% endtrans %}
{% trans %}Acciones Formativas{% endtrans %}
{% trans %}VoBo SAE{% endtrans %}
{% trans %}Inscribirme{% endtrans %}
{% trans %}{{ spots_available }} de {{ spots_total }} plazas{% endtrans %}
{% trans %}Sesiones Programadas{% endtrans %}
{% trans %}Mis Proximas Sesiones{% endtrans %}
```

**NUNCA filtro `|t`.** Solo bloques `{% trans %}`.

**PHP:** `$this->t('Accion formativa')` en formularios y controllers.

---

## 8. Navegacion Admin y Field UI

### 8.1 Rutas admin/structure

| Entidad | Ruta Settings | Titulo |
|---------|--------------|--------|
| AccionFormativaEi | `/admin/structure/accion-formativa-ei` | Configuracion Accion Formativa EI |
| SesionProgramadaEi | `/admin/structure/sesion-programada-ei` | Configuracion Sesion Programada EI |
| InscripcionSesionEi | `/admin/structure/inscripcion-sesion-ei` | Configuracion Inscripcion Sesion EI |
| PlanFormativoEi | `/admin/structure/plan-formativo-ei` | Configuracion Plan Formativo EI |

Cada una con SettingsForm (FormBase) + `field_ui_base_route` en anotacion del entity type + default local task tab (FIELD-UI-SETTINGS-TAB-001).

### 8.2 Rutas admin/content

| Entidad | Ruta Collection | Titulo en Menu |
|---------|----------------|----------------|
| AccionFormativaEi | `/admin/content/andalucia-ei/acciones-formativas` | Acciones Formativas +ei |
| SesionProgramadaEi | `/admin/content/andalucia-ei/sesiones-programadas` | Sesiones Programadas +ei |
| InscripcionSesionEi | `/admin/content/andalucia-ei/inscripciones-sesion` | Inscripciones Sesion +ei |
| PlanFormativoEi | `/admin/content/andalucia-ei/planes-formativos` | Planes Formativos +ei |

### 8.3 Field UI base routes

Cada entidad declara `field_ui_base_route` apuntando a su settings route. Esto habilita automaticamente las pestanas "Administrar campos", "Administrar formulario", "Administrar visualizacion" en la UI de Drupal.

### 8.4 Links de menu, task y action

**jaraba_andalucia_ei.links.menu.yml** (extensiones):

```yaml
jaraba_andalucia_ei.accion_formativa_structure:
  title: 'Accion Formativa Andalucia +ei'
  description: 'Administrar campos y visualizacion de acciones formativas.'
  route_name: entity.accion_formativa_ei.settings
  parent: system.admin_structure
  weight: 36

# ... (3 mas para las otras entidades)
```

**jaraba_andalucia_ei.links.task.yml** (extensiones):

```yaml
entity.accion_formativa_ei.collection:
  title: 'Acciones Formativas'
  route_name: entity.accion_formativa_ei.collection
  base_route: system.admin_content
  weight: 30

entity.accion_formativa_ei.settings_tab:
  title: 'Configuracion'
  route_name: entity.accion_formativa_ei.settings
  base_route: entity.accion_formativa_ei.settings

# Tabs View/Edit/Delete para cada entidad
# ... (patron identico a ProgramaParticipanteEi)
```

**jaraba_andalucia_ei.links.action.yml** (extensiones):

```yaml
entity.accion_formativa_ei.add_form:
  title: 'Anadir Accion Formativa'
  route_name: entity.accion_formativa_ei.add_form
  appears_on:
    - entity.accion_formativa_ei.collection

# ... (3 mas para las otras entidades)
```

---

## 9. Fases de Implementacion

### 9.1 Fase 1: Entidad AccionFormativaEi

**Objetivo:** El Coordinador puede crear, editar y ver acciones formativas del programa.
**Entrada:** Modulo `jaraba_andalucia_ei` con 8 entidades existentes.
**Salida:** Entidad `AccionFormativaEi` completa con form, access, views, routing, hook_update.
**Esfuerzo:** 8-10 horas
**Dependencias:** Ninguna (base)

**Tareas:**

1. Crear `src/Entity/AccionFormativaEi.php` con anotacion completa y `baseFieldDefinitions()` (26 campos)
2. Crear `src/Entity/AccionFormativaEiInterface.php` con metodos publicos
3. Crear `src/Access/AccionFormativaEiAccessControlHandler.php` (TENANT-ISOLATION-ACCESS-001, ACCESS-RETURN-TYPE-001)
4. Crear `src/Form/AccionFormativaEiForm.php` extiende `PremiumEntityFormBase` (PREMIUM-FORMS-PATTERN-001)
5. Crear `src/Form/AccionFormativaEiSettingsForm.php` (FormBase para Field UI)
6. Anadir rutas en `jaraba_andalucia_ei.routing.yml` (collection, add, canonical, edit, delete, settings)
7. Anadir links en `.links.menu.yml`, `.links.task.yml`, `.links.action.yml`
8. Anadir permisos en `.permissions.yml`
9. Anadir `template_preprocess_accion_formativa_ei()` en `.module` (ENTITY-PREPROCESS-001)
10. Crear `hook_update_N()` con `installEntityType()` + `\Throwable` catch (UPDATE-HOOK-REQUIRED-001, UPDATE-HOOK-CATCH-001)

**Verificacion:**
- [ ] Entidad se crea en DB tras `drush updb`
- [ ] Formulario renderiza con secciones PremiumEntityFormBase
- [ ] Field UI accesible en `/admin/structure/accion-formativa-ei`
- [ ] Coleccion visible en `/admin/content/andalucia-ei/acciones-formativas`
- [ ] Access handler verifica tenant_id para update/delete

### 9.2 Fase 2: VoboSaeWorkflowService

**Objetivo:** El Coordinador puede gestionar el ciclo completo VoBo SAE desde la plataforma.
**Entrada:** Entidad `AccionFormativaEi` con campo `vobo_sae_status`.
**Salida:** Servicio con maquina de estados, generacion de documentacion, alertas.
**Esfuerzo:** 8-10 horas
**Dependencias:** Fase 1

**Tareas:**

1. Crear `src/Service/VoboSaeWorkflowService.php` con maquina de estados (8 transiciones)
2. Registrar en `jaraba_andalucia_ei.services.yml` con dependencias `@?` opcionales
3. Metodo `generarDocumentoSolicitud()` — crea PDF via ExpedienteService
4. Metodo `enviarASae()` — registra fecha envio + transiciona estado
5. Metodo `registrarRespuesta()` — aprobado (con codigo) / rechazado (con motivo)
6. Metodo `getAccionesSinVobo()` — para alertas por timeout
7. Extension de `AlertasNormativasService` con nuevas alertas VoBo

**Verificacion:**
- [ ] Transiciones validas: borrador → pendiente_doc → enviado → pendiente → aprobado
- [ ] Transiciones invalidas rechazadas con exception
- [ ] PDF generado como ExpedienteDocumento
- [ ] Alerta tras 15 dias sin respuesta

### 9.3 Fase 6b: Acceso Vertical Temporal (ProgramaVerticalAccessService)

**Objetivo:** Las participantes activas del programa acceden automaticamente a herramientas de empleabilidad y emprendimiento segun su carril, sin requerir addons de pago.
**Entrada:** Entidades existentes ProgramaParticipanteEi (con campo carril) + FeatureAccessService (jaraba_billing).
**Salida:** Servicio ProgramaVerticalAccessService integrado en la cadena de resolucion de acceso.
**Esfuerzo:** 6-8 horas
**Dependencias:** Fase 1 (para que la participante tenga contexto de acciones formativas)
**Prioridad:** P0 — Sin esto, el 80% del ecosistema SaaS es inaccesible para participantes financiadas publicamente

**Tareas:**

1. Crear `src/Service/ProgramaVerticalAccessService.php` implementando `ProgramaVerticalAccessInterface`
2. Crear `src/Service/ProgramaVerticalAccessInterface.php` con 4 metodos publicos
3. Registrar en `jaraba_andalucia_ei.services.yml` con dependencias `@?` opcionales (OPTIONAL-CROSSMODULE-001)
4. Mapeo `CARRIL_VERTICALS`: impulso_digital → empleabilidad + emprendimiento_basico, acelera_pro → emprendimiento + empleabilidad_basico, hibrido → ambos completos
5. Logica de expiracion: fases activas = indefinido, seguimiento = +6 meses, alumni/baja = expirado
6. Metricas de uso via State API (first_access, last_access, access_count por vertical)
7. Inyectar `@?jaraba_andalucia_ei.programa_vertical_access` en `FeatureAccessService` (jaraba_billing) como paso 5 de la cadena
8. Crear parcial Twig `_programa-expiration-banner.html.twig` con banner de conversion a 30 dias de expiracion
9. Extension de `ParticipantePortalController` para inyectar links a herramientas desbloqueadas
10. Extension de `AccesoProgramaService` para exponer verticales activos en contexto

**Verificacion:**

- [ ] Participante carril `impulso_digital` accede a `/my-profile`, `/jobs`, `/my-profile/self-discovery`
- [ ] Participante carril `acelera_pro` accede a `/entrepreneur/dashboard`, BMC, MVP, Projections
- [ ] Participante carril `hibrido` accede a TODAS las herramientas de ambos verticales
- [ ] Participante en fase `baja` NO accede a herramientas cross-vertical
- [ ] Banner de expiracion aparece a 30 dias del fin de seguimiento
- [ ] Metricas de acceso registradas en State API
- [ ] `FeatureAccessService::canAccess()` resuelve TRUE para participantes activas del programa

### 9.4 Fase 3: Entidad PlanFormativoEi

**Objetivo:** El Coordinador puede componer planes formativos por carril (Impulso Digital / Acelera Pro / Hibrido) con desglose de horas y acciones formativas asociadas.
**Entrada:** Entidad AccionFormativaEi (Fase 1).
**Salida:** Entidad `PlanFormativoEi` con campos computed almacenados y validacion de minimos normativos.
**Esfuerzo:** 6-8 horas
**Dependencias:** Fase 1

**Tareas:**

1. Crear `src/Entity/PlanFormativoEi.php` con 18 campos (ver seccion 3.5), computed fields almacenados con recalculo en preSave()
2. Crear `src/Entity/PlanFormativoEiInterface.php` con metodos: cumpleMinimos(), getHorasTotales(), getAccionesFormativas()
3. Crear `src/Access/PlanFormativoEiAccessControlHandler.php` (TENANT-ISOLATION-ACCESS-001)
4. Crear `src/Form/PlanFormativoEiForm.php` extiende PremiumEntityFormBase
5. Crear `src/Form/PlanFormativoEiSettingsForm.php`
6. Registrar en routing.yml, links.menu.yml, links.task.yml, links.action.yml
7. Anadir permisos en .permissions.yml (ver seccion 3.11)
8. Crear `template_preprocess_plan_formativo_ei()` en .module
9. hook_update_10021() con installEntityType() + \Throwable catch

**Verificacion:**
- [ ] Campos computed recalculados correctamente en preSave()
- [ ] cumple_minimo_formacion = TRUE cuando horas_formacion >= 50
- [ ] cumple_minimo_orientacion = TRUE cuando horas_orientacion >= 10
- [ ] Field UI en /admin/structure/plan-formativo-ei

### 9.5 Fase 4: Entidad SesionProgramadaEi

**Objetivo:** El Coordinador puede calendarizar sesiones con soporte de recurrencia y control de plazas.
**Entrada:** Entidades AccionFormativaEi + PlanFormativoEi.
**Salida:** Entidad `SesionProgramadaEi` con 25 campos, expansion de recurrencia, sincronizacion calendar.
**Esfuerzo:** 8-10 horas
**Dependencias:** Fase 1 (FK a AccionFormativaEi opcional)

**Tareas:**

1. Crear `src/Entity/SesionProgramadaEi.php` con 25 campos (ver seccion 3.3), FK self-referencing sesion_padre_id
2. Crear `src/Entity/SesionProgramadaEiInterface.php`
3. Crear `src/Access/SesionProgramadaEiAccessControlHandler.php`
4. Crear `src/Form/SesionProgramadaEiForm.php` extiende PremiumEntityFormBase
5. Crear `src/Form/SesionProgramadaEiSettingsForm.php`
6. Crear `src/Service/SesionProgramadaService.php` con expandirRecurrencia(), generarSesionesFuturas()
7. Registrar servicio en services.yml con @?jaraba_legal_calendar dependencia opcional
8. Rutas, links, permisos
9. Crear `template_preprocess_sesion_programada_ei()` en .module
10. Crear parcial `_session-card.html.twig` con fecha, hora, modalidad, plazas, estado
11. hook_update_10020() con installEntityType() + \Throwable catch

**Verificacion:**
- [ ] Recurrencia weekly genera 4 sesiones hijas correctamente
- [ ] plazas_ocupadas recalculado en preSave() desde inscripciones activas
- [ ] Sesiones pasadas auto-transicionan a estado completada/cancelada
- [ ] Parcial _session-card renderiza con iconos duotone

### 9.6 Fase 5: Entidad InscripcionSesionEi

**Objetivo:** Las participantes pueden inscribirse en sesiones y la asistencia genera automaticamente ActuacionSto.
**Entrada:** SesionProgramadaEi (Fase 4) + ProgramaParticipanteEi existente.
**Salida:** Entidad `InscripcionSesionEi` con 15 campos, generacion automatica de actuaciones.
**Esfuerzo:** 8-10 horas
**Dependencias:** Fase 4

**Tareas:**

1. Crear `src/Entity/InscripcionSesionEi.php` con 15 campos (ver seccion 3.4), sin label en entity_keys (LABEL-NULLSAFE-001)
2. Crear `src/Entity/InscripcionSesionEiInterface.php`
3. Crear `src/Access/InscripcionSesionEiAccessControlHandler.php` — participantes solo ven/modifican propias
4. Crear `src/Form/InscripcionSesionEiForm.php` extiende PremiumEntityFormBase
5. Crear `src/Form/InscripcionSesionEiSettingsForm.php`
6. Crear `src/Service/InscripcionSesionService.php` con inscribir(), confirmarAsistencia(), generarActuacion()
7. Logica en confirmarAsistencia(): asistencia_verificada=TRUE → crear ActuacionSto → incrementarHorasParticipante()
8. Registrar servicio con @?jaraba_andalucia_ei.actuacion_sto dependencia
9. Rutas, links, permisos (self-service para participantes)
10. Crear parcial `_inscripcion-button.html.twig` con estados visuales
11. hook_update_10019() con installEntityType() + \Throwable catch

**Verificacion:**
- [ ] Inscripcion self-service funciona desde portal participante
- [ ] asistencia_verificada = TRUE genera ActuacionSto automaticamente
- [ ] horas_computadas incrementa el total de horas del participante
- [ ] Doble inscripcion a misma sesion rechazada con mensaje claro

### 9.7 Fase 6: Servicios de negocio

**Objetivo:** Logica de dominio encapsulada en servicios reutilizables.
**Entrada:** 4 entidades creadas (Fases 1-5).
**Salida:** AccionFormativaService con metodos de consulta/validacion.
**Esfuerzo:** 8-10 horas
**Dependencias:** Fases 1-5

**Tareas:**

1. Crear `src/Service/AccionFormativaService.php` con getAccionesPorPrograma(), validarRequisitos()
2. Registrar en services.yml con dependencias opcionales
3. Integrar servicios en controllers existentes (CoordinadorHubController, ParticipantePortalController)
4. Endpoints API REST para CRUD de las 4 entidades via `ProgramaFormativoApiController.php`
5. Respetar API-WHITELIST-001 con ALLOWED_FIELDS y CSRF-API-001

**Verificacion:**
- [ ] API endpoints responden con formato JSON correcto
- [ ] ALLOWED_FIELDS filtra campos no autorizados
- [ ] CSRF token requerido en POST/PATCH/DELETE

### 9.8 Fase 6b: Acceso Vertical Temporal (ProgramaVerticalAccessService)

(Ya detallado en seccion 9.3 anterior)

### 9.9 Fase 6c: Compliance ESF+ e Indicadores de Impacto

**Objetivo:** La plataforma cumple los requisitos de reporting del Fondo Social Europeo Plus para justificacion ante la autoridad de gestion.
**Entrada:** ProgramaParticipanteEi con datos sociodemograficos + InscripcionSesionEi con asistencia.
**Salida:** IndicadoresEsfService + PII digital + cron seguimiento 6 meses + dashboard financiador.
**Esfuerzo:** 8-10 horas
**Dependencias:** Fases 1-5 (entidades con datos de participacion)
**Prioridad:** P0 — Sin indicadores ESF+, la justificacion economica ante la Junta de Andalucia es incompleta

**Tareas:**

1. Crear `src/Service/IndicadoresEsfService.php` con getIndicadoresOutput(), getIndicadoresResultado(), exportCSV()
2. Mapear 14 indicadores output (CO01-CO14) a campos existentes de ProgramaParticipanteEi (ver seccion 6b.1)
3. Implementar 6 indicadores resultado (CR01-CR06) con logica de calculo desde seguimiento post-programa
4. Anadir campo `plan_individual_insercion` (JSON) a ProgramaParticipanteEi si no existe, via hook_update_N()
5. Implementar esquema JSON del PII digital (ver seccion 6b.2) con validacion en preSave()
6. Crear cron handler `_jaraba_andalucia_ei_cron_seguimiento()` para encuesta automatica a 6 meses
7. Crear `src/Controller/DashboardFinanciadorController.php` con ruta `/andalucia-ei/impacto/financiador`
8. Dashboard: KPIs globales + indicadores ESF+ tabulados + analisis de cohorte + export (CSV/JSON/PDF)
9. Registrar IndicadoresEsfService en services.yml con logger channel
10. Permisos: `view andalucia ei esf indicators` (coordinador + admin)

**Verificacion:**
- [ ] 14 indicadores output calculados correctamente desde datos reales de participantes
- [ ] PII digital se guarda como JSON valido con schema completo
- [ ] Cron genera encuestas de seguimiento a 6 meses para participantes en fase `alumni`
- [ ] Dashboard renderiza con graficos y tablas exportables
- [ ] Export CSV cumple formato requerido por la Junta de Andalucia

### 9.10 Fase 6d: UX Inclusivo para Colectivos Vulnerables

**Objetivo:** La interfaz es usable por personas con baja alfabetizacion digital, diversidad funcional, y colectivos vulnerables del programa.
**Entrada:** Formularios y templates de las Fases 1-8.
**Salida:** Capa UX inclusiva aplicada transversalmente.
**Esfuerzo:** 8-10 horas
**Dependencias:** Fases 1-8 (aplica sobre formularios y templates existentes)
**Prioridad:** P1 — Diferenciador de calidad y obligacion legal EAA desde junio 2025

**Tareas:**

1. Revisar TODOS los textos de interfaz contra tabla de lenguaje claro (seccion 6c.1): nivel B1/A2
2. Anadir setDescription() con ejemplos contextuales en TODOS los campos de las 4 entidades (seccion 6c.2)
3. Implementar JS de validacion en tiempo real con refuerzo positivo (seccion 6c.3): Drupal.behaviors pattern
4. Aplicar reglas mobile-first en SCSS (seccion 6c.4): touch targets 48px, 1 columna en mobile, CTA bottom-sticky
5. Implementar preferencias de accesibilidad via CSS custom properties (seccion 6c.5): font-size, contrast, reduced-motion, fuente dislexia
6. Implementar confirmacion modal para acciones destructivas + toast con undo para semi-reversibles (seccion 6c.6)
7. Crear SCSS parcial `_ux-inclusivo.scss` con tokens y utilidades reutilizables
8. Verificar WCAG 2.2 AA completo (seccion 7.6): 9 criterios nuevos sobre WCAG 2.1

**Verificacion:**
- [ ] Textos validados contra tabla B1/A2 (sin jerga tecnica)
- [ ] Touch targets >= 48px en todos los interactivos (WCAG 2.5.8)
- [ ] Mensajes de validacion con refuerzo positivo y aria-live
- [ ] Preferencias de accesibilidad guardadas en localStorage y aplicadas via :root CSS vars
- [ ] 0 errores criticos en audit WCAG 2.2 AA con axe-core
- [ ] Focus visible sin ocultamiento (WCAG 2.4.11)

### 9.11 Fase 7: Integracion CoordinadorHub

**Objetivo:** El Coordinador accede a todo el programa formativo desde el hub existente.
**Entrada:** Entidades y servicios de Fases 1-6d.
**Salida:** Pestanas "Plan Formativo" + "Calendario" + "Dashboard Financiador" en CoordinadorHub.
**Esfuerzo:** 6-8 horas
**Dependencias:** Fases 1-6d

**Tareas:**

1. Anadir pestana "Plan Formativo" a CoordinadorHubService con resumen de acciones y estado VoBo
2. Anadir pestana "Calendario" con vista mensual de sesiones (renderizada server-side)
3. Integrar link a Dashboard Financiador (Fase 6c) desde pestana "Documentacion"
4. Crear template `coordinador-plan-formativo.html.twig` zero-region
5. drupalSettings via hook_preprocess_page() (ZERO-REGION-003)
6. Slide-panel para crear/editar acciones formativas y sesiones

**Verificacion:**
- [ ] Pestanas visibles y funcionales en CoordinadorHub
- [ ] Slide-panel abre con renderPlain() (SLIDE-PANEL-RENDER-001)
- [ ] Dashboard financiador accesible desde hub

### 9.12 Fase 8: Portal participante

**Objetivo:** La participante ve sus sesiones, herramientas desbloqueadas, y next-step CTAs.
**Entrada:** InscripcionSesionEi + ProgramaVerticalAccessService.
**Salida:** Seccion "Mis Sesiones" + herramientas cross-vertical + banner expiracion.
**Esfuerzo:** 6-8 horas
**Dependencias:** Fases 5, 6b

**Tareas:**

1. Anadir seccion "Mis Sesiones" a ParticipantePortalController con listado de inscripciones
2. Anadir seccion "Mis Herramientas" con enlaces a herramientas desbloqueadas por carril
3. Integrar parcial `_programa-expiration-banner.html.twig` a 30 dias de expiracion
4. Next-step CTA dinamico segun fase actual del participante
5. Mobile-first layout con bottom-sticky CTA (seccion 6c.4)

**Verificacion:**
- [ ] Participante ve sesiones futuras con boton inscripcion
- [ ] Herramientas mostradas segun carril del participante
- [ ] Banner de expiracion visible a 30 dias

### 9.13 Fase 9: Alertas normativas extendidas

**Objetivo:** Alertas proactivas para deadlines criticos del programa.
**Entrada:** VoboSaeWorkflowService + InscripcionSesionService + IndicadoresEsfService.
**Salida:** Alertas extendidas: VoBo timeout, horas insuficientes, deadlines ESF+.
**Esfuerzo:** 4-5 horas
**Dependencias:** Fases 2, 5, 6c

### 9.14 Fase 10: Integracion IA

**Objetivo:** IA asiste en diseno de acciones formativas, recomendaciones, y revisora VoBo.
**Entrada:** Entidades y servicios de Fases 1-9.
**Salida:** Contexto enriquecido para Copilot + 3 herramientas IA nativas.
**Esfuerzo:** 6-8 horas
**Dependencias:** Fases 1-9

### 9.15 Fase 11: Tests

**Objetivo:** Cobertura completa Unit + Kernel para 4 entidades, 6 servicios, indicadores ESF+.
**Entrada:** Todo el codigo de Fases 1-10.
**Salida:** 32+ tests PHPUnit (Unit + Kernel).
**Esfuerzo:** 8-10 horas
**Dependencias:** Fases 1-10

**Tests adicionales v2.0:**
- Unit: IndicadoresEsfServiceTest — calculo de 14 indicadores output, 6 resultado
- Unit: PiiDigitalValidationTest — validacion JSON schema del PII
- Kernel: IndicadoresEsfServiceKernelTest — agregacion con datos reales
- Kernel: DashboardFinanciadorTest — renderizado + export CSV
- Unit: ProgramaVerticalAccessServiceTest — mapeo carril→verticales, expiracion
- WCAG: Auditoria automatizada con axe-core en CI (si aplica)

### 9.16 Fase 12: Verificacion runtime + WCAG 2.2 AA

**Objetivo:** Verificacion completa RUNTIME-VERIFY-001 + auditoria WCAG 2.2 AA.
**Entrada:** Todo implementado.
**Salida:** 0 errores criticos, documentacion actualizada.
**Esfuerzo:** 6-8 horas
**Dependencias:** Fases 1-11

**Tareas adicionales v2.0:**
- Auditoria WCAG 2.2 AA: 9 criterios nuevos (ver seccion 7.6)
- Verificacion touch targets >= 48px
- Verificacion focus visible sin ocultamiento
- Verificacion contraste minimo 4.5:1 (3:1 texto grande)
- Verificacion reduced-motion respetado
- Verificacion de indicadores ESF+ con datos de prueba realistas

---

## 10. Tabla de Correspondencia: Especificaciones Tecnicas

| Especificacion | Seccion Aplicable | Entidad/Servicio | Estado |
|---------------|-------------------|------------------|--------|
| PIIL BBRR Ord.29/09/2023 Art.5 (formacion) | 3.2 AccionFormativaEi | AccionFormativaEi.horas_previstas, tipo_formacion | Planificado |
| PIIL BBRR Art.7 (VoBo SAE) | 5.1 Maquina estados | VoboSaeWorkflowService | Planificado |
| PIIL BBRR Art.9 (orientacion 10h) | 3.5 PlanFormativoEi | cumple_minimo_orientacion (computed) | Planificado |
| PIIL BBRR Art.10 (formacion 50h) | 3.5 PlanFormativoEi | cumple_minimo_formacion (computed) | Planificado |
| PIIL CV Ficha Tecnica FT_679 | 3.2-3.5 Entidades | Todas | Planificado |
| FSE+ Indicadores entrada/salida | 3.4 InscripcionSesionEi | asistencia_verificada → ActuacionSto | Planificado |
| Manual STO ICV25 (registro actuaciones) | 3.4 InscripcionSesionEi | generacion automatica ActuacionSto | Planificado |
| Manual Operativo V2.1 (itinerarios) | 3.5 PlanFormativoEi | composicion por carril | Planificado |
| Contenido Formativo 1a Ed (5 modulos) | 4.1 jaraba_lms | AccionFormativaEi.course_id | Planificado |
| DACI compromisos (asistencia) | 3.4 InscripcionSesionEi | estado inscripcion + asistencia | Planificado |
| €528 incentivo (horas minimas) | 3.4 InscripcionSesionEi | incremento horas via ActuacionStoService | Reutiliza existente |

---

## 11. Tabla de Cumplimiento de Directrices

| Directriz | Donde se Aplica | Verificacion |
|-----------|----------------|--------------|
| TENANT-001 | Todas las queries con `->condition('tenant_id', $tenantId)` | `validate-tenant-isolation.php` |
| TENANT-ISOLATION-ACCESS-001 | 4 AccessControlHandlers verifican tenant match para update/delete | Code review |
| PREMIUM-FORMS-PATTERN-001 | 4 forms extienden PremiumEntityFormBase con getSectionDefinitions() + getFormIcon() | Code review |
| ENTITY-FK-001 | `course_id`, `interactive_content_id` son integer (cross-module) | phpstan |
| ACCESS-RETURN-TYPE-001 | `checkAccess()` retorna `AccessResultInterface` (no `AccessResult`) | phpstan level 6 |
| CONTROLLER-READONLY-001 | Ningun `protected readonly` en propiedades heredadas de ControllerBase | phpstan |
| UPDATE-HOOK-REQUIRED-001 | 4 hook_update_N() con installEntityType() | `validate-entity-integrity.php` |
| UPDATE-HOOK-CATCH-001 | try-catch con `\Throwable` (no `\Exception`) | Code review |
| OPTIONAL-CROSSMODULE-001 | `@?jaraba_lms.*`, `@?jaraba_legal_calendar.*`, etc. | `validate-optional-deps.php` |
| CONTAINER-DEPS-002 | Sin dependencias circulares entre los 4 servicios nuevos | `validate-circular-deps.php` |
| LOGGER-INJECT-001 | `@logger.channel.jaraba_andalucia_ei` + `LoggerInterface $logger` | `validate-logger-injection.php` |
| PHANTOM-ARG-001 | args en services.yml coinciden con constructor params | Code review |
| ZERO-REGION-001 | Templates con `clean_content`, sin `page.content` | Template review |
| ZERO-REGION-003 | drupalSettings via hook_preprocess, NO en controller #attached | Code review |
| SLIDE-PANEL-RENDER-001 | renderPlain() + $form['#action'] en slide-panel requests | Code review |
| CSS-VAR-ALL-COLORS-001 | SCSS con `var(--ej-*, $fallback)`. Sin hex hardcoded | SCSS review |
| SCSS-COMPILE-VERIFY-001 | CSS timestamp > SCSS timestamp | `npm run build` + verify |
| SCSS-ENTRY-CONSOLIDATION-001 | Sin `name.scss` + `_name.scss` en mismo directorio | `check-scss-orphans.js` |
| ICON-CONVENTION-001 | `jaraba_icon()` con categoria/nombre/variante/color | Template review |
| ICON-DUOTONE-001 | Variante default duotone en todos los iconos | Template review |
| ICON-COLOR-001 | Solo colores de paleta Jaraba | Template review |
| TWIG-INCLUDE-ONLY-001 | `{% include ... with {} only %}` en todos los includes de parciales | Template review |
| ROUTE-LANGPREFIX-001 | URLs via drupalSettings + Url::fromRoute() | JS review |
| CSRF-API-001 | `_csrf_request_header_token: 'TRUE'` en rutas POST | routing.yml review |
| API-WHITELIST-001 | ALLOWED_FIELDS constantes en controllers API | Controller review |
| INNERHTML-XSS-001 | `Drupal.checkPlain()` para datos de API en innerHTML | JS review |
| FIELD-UI-SETTINGS-TAB-001 | `field_ui_base_route` + default local task tab | Entity annotation |
| ENTITY-PREPROCESS-001 | `template_preprocess_{type}()` en .module | .module review |
| LABEL-NULLSAFE-001 | Null-safe en label() de entidades | Entity methods |
| PRESAVE-RESILIENCE-001 | try-catch en servicios opcionales presave | Service review |
| NO-HARDCODE-PRICE-001 | Sin precios hardcoded (si aplica) | Template review |
| WCAG-2.2-AA | 9 criterios nuevos: focus-not-obscured, dragging, target-size, consistent-help, redundant-entry, accessible-auth, focus-appearance | axe-core + manual audit |
| ESF+-INDICATORS-001 | 14 indicadores output + 6 resultado mapeados a campos existentes | IndicadoresEsfServiceTest |
| PII-DIGITAL-001 | Plan Individual Insercion como JSON validado con schema | PiiDigitalValidationTest |
| EAA-COMPLIANCE-001 | European Accessibility Act (Directiva 2019/882) desde junio 2025 | WCAG 2.2 AA audit |
| PLAIN-LANGUAGE-B1-001 | Textos interfaz participante nivel B1/A2 MCER | Review manual + tabla seccion 6c.1 |
| RUNTIME-VERIFY-001 | 5 checks post-implementacion | Fase 12 |
| IMPLEMENTATION-CHECKLIST-001 | Complitud + Integridad + Consistencia + Coherencia | Fase 12 |

---

## 12. Inventario Completo de Ficheros

### Nuevos ficheros (estimados ~42)

**Entidades (8 ficheros):**
- `src/Entity/AccionFormativaEi.php`
- `src/Entity/AccionFormativaEiInterface.php`
- `src/Entity/SesionProgramadaEi.php`
- `src/Entity/SesionProgramadaEiInterface.php`
- `src/Entity/InscripcionSesionEi.php`
- `src/Entity/InscripcionSesionEiInterface.php`
- `src/Entity/PlanFormativoEi.php`
- `src/Entity/PlanFormativoEiInterface.php`

**Access Handlers (4):**
- `src/Access/AccionFormativaEiAccessControlHandler.php`
- `src/Access/SesionProgramadaEiAccessControlHandler.php`
- `src/Access/InscripcionSesionEiAccessControlHandler.php`
- `src/Access/PlanFormativoEiAccessControlHandler.php`

**Forms (8):**
- `src/Form/AccionFormativaEiForm.php`
- `src/Form/AccionFormativaEiSettingsForm.php`
- `src/Form/SesionProgramadaEiForm.php`
- `src/Form/SesionProgramadaEiSettingsForm.php`
- `src/Form/InscripcionSesionEiForm.php`
- `src/Form/InscripcionSesionEiSettingsForm.php`
- `src/Form/PlanFormativoEiForm.php`
- `src/Form/PlanFormativoEiSettingsForm.php`

**Servicios (6 + 2 interfaces):**
- `src/Service/AccionFormativaService.php`
- `src/Service/VoboSaeWorkflowService.php`
- `src/Service/SesionProgramadaService.php`
- `src/Service/InscripcionSesionService.php`
- `src/Service/ProgramaVerticalAccessService.php`
- `src/Service/ProgramaVerticalAccessInterface.php`
- `src/Service/IndicadoresEsfService.php` **(NUEVO v2.0 — Fase 6c)**
- `src/Service/IndicadoresEsfServiceInterface.php` **(NUEVO v2.0 — Fase 6c)**

**Controllers (2 nuevos + extensiones):**
- `src/Controller/ProgramaFormativoApiController.php` (endpoints API de las 4 entidades)
- `src/Controller/DashboardFinanciadorController.php` **(NUEVO v2.0 — Fase 6c)** (ruta `/andalucia-ei/impacto/financiador`)

**Templates (7 nuevos parciales + extensiones):**
- `templates/partials/_session-card.html.twig`
- `templates/partials/_vobo-badge.html.twig`
- `templates/partials/_inscripcion-button.html.twig`
- `templates/partials/_programa-expiration-banner.html.twig`
- `templates/partials/_pii-digital-summary.html.twig` **(NUEVO v2.0 — PII resumen en portal participante)**
- `templates/coordinador-plan-formativo.html.twig`
- `templates/dashboard-financiador.html.twig` **(NUEVO v2.0 — Dashboard ESF+ con KPIs y graficos)**

**SCSS (2 nuevos):**
- `web/themes/custom/ecosistema_jaraba_theme/scss/routes/coordinador-programa.scss`
- `web/themes/custom/ecosistema_jaraba_theme/scss/components/_ux-inclusivo.scss` **(NUEVO v2.0 — Fase 6d)** (tokens touch-target, a11y-prefs, plain-language utilities)

**JS (1 nuevo):**
- `js/andalucia-ei-rt-validation.js` **(NUEVO v2.0 — Fase 6d)** (validacion tiempo real con refuerzo positivo, Drupal.behaviors)

**Tests (~22 nuevos):**
- `tests/src/Unit/Entity/AccionFormativaEiTest.php`
- `tests/src/Unit/Entity/SesionProgramadaEiTest.php`
- `tests/src/Unit/Entity/InscripcionSesionEiTest.php`
- `tests/src/Unit/Entity/PlanFormativoEiTest.php`
- `tests/src/Unit/Service/VoboSaeWorkflowServiceTest.php`
- `tests/src/Unit/Service/AccionFormativaServiceTest.php`
- `tests/src/Unit/Service/SesionProgramadaServiceTest.php`
- `tests/src/Unit/Service/InscripcionSesionServiceTest.php`
- `tests/src/Unit/Service/IndicadoresEsfServiceTest.php` **(NUEVO v2.0)**
- `tests/src/Unit/Service/PiiDigitalValidationTest.php` **(NUEVO v2.0)**
- `tests/src/Kernel/Entity/AccionFormativaEiKernelTest.php`
- `tests/src/Kernel/Entity/SesionProgramadaEiKernelTest.php`
- `tests/src/Kernel/Entity/InscripcionSesionEiKernelTest.php`
- `tests/src/Kernel/Entity/PlanFormativoEiKernelTest.php`
- `tests/src/Kernel/Service/VoboSaeWorkflowServiceKernelTest.php`
- `tests/src/Kernel/Service/InscripcionSesionServiceKernelTest.php`
- `tests/src/Kernel/Service/SesionProgramadaServiceKernelTest.php`
- `tests/src/Kernel/Service/IndicadoresEsfServiceKernelTest.php` **(NUEVO v2.0)**
- `tests/src/Kernel/Controller/DashboardFinanciadorKernelTest.php` **(NUEVO v2.0)**
- `tests/src/Unit/Service/ProgramaVerticalAccessServiceTest.php`
- `tests/src/Kernel/Service/ProgramaVerticalAccessServiceKernelTest.php`
- `tests/src/Unit/Validation/WcagComplianceTest.php` **(NUEVO v2.0 — verificacion automatizada criterios WCAG 2.2)**

### Ficheros modificados (~14)

- `jaraba_andalucia_ei.services.yml` — 6 nuevos servicios (incluye IndicadoresEsfService)
- `jaraba_andalucia_ei.routing.yml` — ~38 nuevas rutas (incluye dashboard financiador)
- `jaraba_andalucia_ei.permissions.yml` — ~16 nuevos permisos granulares por rol
- `jaraba_andalucia_ei.links.menu.yml` — 5 nuevas entradas (incluye dashboard financiador)
- `jaraba_andalucia_ei.links.task.yml` — ~18 nuevas entradas (4 entidades × 4 tabs + financiador)
- `jaraba_andalucia_ei.links.action.yml` — 4 nuevas entradas
- `jaraba_andalucia_ei.module` — hook_update, preprocess (6 nuevas funciones), page_attachments, cron (seguimiento 6 meses)
- `jaraba_andalucia_ei.install` — hook_update_N() × 4 (10019-10022) + posible 10023 para campo PII si no existe
- `jaraba_andalucia_ei.libraries.yml` — library andalucia-ei-rt-validation (JS validacion tiempo real)
- `ecosistema_jaraba_theme.libraries.yml` — 2 nuevas libraries (route + component)
- `ecosistema_jaraba_theme/scss/main.scss` — @use del nuevo parcial _ux-inclusivo
- `ecosistema_jaraba_theme/package.json` — build:routes extension
- `jaraba_billing/src/Service/FeatureAccessService.php` — Paso 5: acceso via programa subvencionado
- `jaraba_billing/jaraba_billing.services.yml` — Inyeccion `@?jaraba_andalucia_ei.programa_vertical_access`

---

## 13. Verificacion y Testing

### 13.1 Tests automatizados

| Tipo | Entidad/Servicio | Que valida |
|------|-----------------|------------|
| Unit | AccionFormativaEi | requiereVoboSae(), canExecute(), getHorasPrevistas() |
| Unit | SesionProgramadaEi | isGrupal(), hayPlazasDisponibles(), getDuracionHoras() |
| Unit | InscripcionSesionEi | Metodos de estado |
| Unit | PlanFormativoEi | Computed fields (horas, cumple minimos) |
| Unit | VoboSaeWorkflowService | Transiciones validas/invalidas, estados terminales |
| Kernel | AccionFormativaEi | CRUD + tenant isolation |
| Kernel | SesionProgramadaEi | Recurrencia expansion |
| Kernel | InscripcionSesionEi | Generacion automatica ActuacionSto |
| Kernel | PlanFormativoEi | Computed horas en presave |
| Kernel | VoboSaeWorkflowService | Workflow completo con DB |
| Kernel | InscripcionSesionService | Incremento horas participante |
| Unit | ProgramaVerticalAccessService | Mapeo carril→verticales, expiracion por fase, metricas |
| Kernel | ProgramaVerticalAccessService | Integracion con FeatureAccessService, acceso real cross-vertical |
| Unit | IndicadoresEsfService | Calculo correcto de 14 indicadores output CO01-CO14 |
| Unit | IndicadoresEsfService | Calculo correcto de 6 indicadores resultado CR01-CR06 |
| Unit | PiiDigitalValidation | Schema JSON del PII: campos requeridos, formato fechas, objetivos validos |
| Kernel | IndicadoresEsfService | Agregacion con datos reales de participantes, filtro por cohorte |
| Kernel | DashboardFinanciadorController | Renderizado completo + export CSV formato Junta Andalucia |
| Unit | WcagComplianceTest | Touch targets >= 48px en templates, aria-labels en interactivos, focus visible |

### 13.2 Checklist RUNTIME-VERIFY-001

- [ ] CSS compilado: `css/routes/coordinador-programa.css` timestamp > SCSS
- [ ] Tablas DB creadas: `accion_formativa_ei`, `sesion_programada_ei`, `inscripcion_sesion_ei`, `plan_formativo_ei`
- [ ] Rutas accesibles: `/admin/content/andalucia-ei/acciones-formativas`, etc.
- [ ] data-* selectores matchean entre JS y HTML (session-card, enroll button)
- [ ] drupalSettings inyectado correctamente (apiUrls para slide-panel)

### 13.3 Checklist IMPLEMENTATION-CHECKLIST-001

**Complitud:**
- [ ] 4 servicios registrados en services.yml Y consumidos por controllers/otros servicios
- [ ] Rutas en routing.yml apuntan a clases/metodos existentes
- [ ] 4 entidades con AccessControlHandler, hook_theme, template_preprocess, Views data
- [ ] SCSS compilado, library registrada, hook_page_attachments_alter

**Integridad:**
- [ ] Tests existen: Unit para servicios, Kernel para entities
- [ ] hook_update_N() para cada nueva entidad
- [ ] Verificar: `php scripts/validation/validate-entity-integrity.php`

**Consistencia:**
- [ ] PREMIUM-FORMS-PATTERN-001 en forms
- [ ] CONTROLLER-READONLY-001 en controllers
- [ ] CSS-VAR-ALL-COLORS-001 en SCSS
- [ ] TENANT-001 en queries

**Coherencia:**
- [ ] Documentacion actualizada (este plan + auditoria)
- [ ] Memory files actualizados si patron nuevo

### 13.4 Validacion con scripts

```bash
# Ejecutar dentro del contenedor Lando
lando php scripts/validation/validate-all.sh --checklist web/modules/custom/jaraba_andalucia_ei
lando php scripts/validation/validate-entity-integrity.php
lando php scripts/validation/validate-optional-deps.php
lando php scripts/validation/validate-circular-deps.php
lando php scripts/validation/validate-logger-injection.php
lando php scripts/validation/validate-service-consumers.php
lando php scripts/validation/validate-tenant-isolation.php
```

---

## 14. Riesgos y Mitigaciones

| Riesgo | Prob. | Impacto | Mitigacion |
|--------|-------|---------|------------|
| Duplicar lms_enrollment con InscripcionSesionEi | Media | Alto | Documentar diferencia: enrollment = matricula curso (autoestudio), inscripcion = asistencia sesion sincrona |
| VoBo SAE como cuello de botella | Alta | Critico | Workflow con alertas proactivas a 15 y 30 dias + generacion automatica de documentacion |
| Dependencia circular InscripcionSesion ↔ ActuacionSto | Media | Alto | Direccion unica: InscripcionSesion → ActuacionSto. Nunca al reves |
| 4 entidades en modulo con 8 existentes | Baja | Bajo | Coherentes con dominio PIIL. Patron consistente con Sprints 1-12 |
| Complejidad recurrencia JSON | Media | Medio | Patron simple (no iCalendar). Solo weekly/biweekly/monthly. Expansion lazy (4 semanas adelante) |
| Performance con muchas inscripciones | Baja | Medio | Entity queries con indices en (sesion_id, participante_id). Paginacion en APIs |
| Participantes no descubren herramientas cross-vertical | Alta | Alto | Onboarding guiado por carril. Portal con enlaces directos a cada herramienta. Copilot sugiere herramientas contextualmente. Banner en dashboard |
| Conversion post-programa baja | Media | Alto | Datos conservados en modo lectura. Banner a 30 dias de expiracion. Trial 30 dias gratis. Metricas de uso para remarketing |
| Acceso temporal desborda costes IA (Copilot, embeddings) | Media | Medio | FairUsePolicyService ya aplica quotas por plan. ProgramaVerticalAccess usa cuotas del plan "professional" temporal con burst tolerance |
| Indicadores ESF+ incompletos en justificacion | Alta | Critico | IndicadoresEsfService mapea 20 indicadores a campos existentes. Dashboard con export CSV en formato oficial. Alerta si indicador sin datos a 30 dias de deadline |
| Formato export ESF+ cambia entre convocatorias | Media | Medio | Exportador configurable (CSV template). Solo ajustar cabeceras/columnas sin tocar logica de calculo |
| UX inclusivo insuficiente para colectivos vulnerables | Media | Alto | Capa UX transversal: lenguaje B1/A2, touch 48px, a11y prefs, validacion RT positiva. Testar con usuarios reales en piloto |
| WCAG 2.2 AA fallo en auditoria | Baja | Alto | Criterios verificados por fase (seccion 7.6). axe-core en CI. Focus visible, target size, y auth accesible priorizados |
| PII digital con datos sensibles (GDPR) | Media | Alto | JSON almacenado en tabla de entidad con tenant isolation. Sin datos de salud (excluidos explicitamente). Acceso solo coordinador/orientador |
| Encuesta 6 meses sin respuesta (tasa baja) | Alta | Medio | Multi-canal: email + SMS (si PWA) + notificacion in-app. Recordatorio a 7 y 14 dias. Incentivo: acceso extendido 30 dias a herramientas |

---

## 15. Referencias

### Documentacion del Proyecto
- `CLAUDE.md` v1.4.0 — Directrices maestras
- `docs/analisis/2026-03-11_Auditoria_Andalucia_Ei_Diseno_Programa_Formativo_Sprint13_v1.md` — Auditoria fuente
- `docs/analisis/2026-03-06_Auditoria_Andalucia_Ei_PIIL_CV_2025_Brechas_Normativas_v1.md` — Brechas normativas
- `docs/implementacion/2026-03-10_Plan_Implementacion_Andalucia_Ei_Cumplimiento_Integral_PIIL_v2.md` — Plan integral previo
- `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md` — Arquitectura theming

### Fuentes Normativas
- Orden 29/09/2023 — Bases Reguladoras PIIL
- Manual STO ICV25 — Gestion tecnica del STO
- Manual Operativo V2.1 — Itinerarios diferenciados
- Reglamento (UE) 2021/1057 — Fondo Social Europeo Plus (ESF+)
- Anexo I Reglamento ESF+ — Indicadores comunes de output y resultado
- WCAG 2.2 (W3C Recommendation 2023-10-05) — Web Content Accessibility Guidelines
- Directiva (UE) 2019/882 — European Accessibility Act (EAA), aplicable desde junio 2025
- EN 301 549 v3.2.1 — Requisitos accesibilidad TIC (norma armonizada EAA)

### Fuentes Operativas (1a Edicion)
- `F:\DATOS\PED S.L\Economico-Financiero\Subvenciones\Junta de Andalucia\2023 PIIL`
- Curso Emprendimiento v2 (5 modulos, 105 MB PPTX)
- Orientacion Inicial v2 (21 MB PPTX)
- Insercion Autoempleo (47 MB PPTX)

---

## 16. Registro de Cambios

| Version | Fecha | Descripcion |
|---------|-------|-------------|
| 1.0.0 | 2026-03-11 | Creacion inicial del plan de implementacion Sprint 13 |
| 1.1.0 | 2026-03-11 | Seccion 4.5-4.7: Andalucia +ei como Vertical Temporal — estrategia de acceso completo a herramientas de empleabilidad y emprendimiento, inventario de 25+ herramientas por carril, ProgramaVerticalAccessService, integracion FeatureAccessService, metricas de uso, banner de conversion. Fase 6b anadida al plan |
| 2.0.0 | 2026-03-11 | **Actualizacion clase mundial (10/10).** Auditoria exhaustiva con 13 gaps P0 corregidos. Cambios principales: (1) Anotaciones entidades corregidas — route_provider, "owner" en entity_keys, EntityOwnerInterface+EntityChangedInterface en todas, revision_metadata_keys en AccionFormativaEi; (2) Tablas completas de campos — SesionProgramadaEi 25 campos, InscripcionSesionEi 15 campos, PlanFormativoEi 18 campos con computed fields almacenados; (3) Seccion 3.10 Decisiones arquitectonicas — 8 decisiones explicitas, logica getParticipanteActivo(); (4) Seccion 3.11 Permisos granulares — 16 permisos por rol (Coordinador/Orientador/Formador/Participante); (5) Seccion 6b Compliance ESF+ — 14 indicadores output, 6 resultado, PII digital JSON, seguimiento 6 meses, dashboard financiador; (6) Seccion 6c UX Inclusivo — lenguaje B1/A2, ayuda contextual, validacion RT positiva, mobile-first 48px, preferencias a11y, confirmacion/undo; (7) Seccion 7.6 WCAG 2.2 AA — 9 criterios nuevos verificados; (8) Fases 6c+6d detalladas en seccion 9; (9) Inventario actualizado ~42 ficheros; (10) Tests ampliados a 22+; (11) 7 nuevos riesgos ESF+/UX/WCAG; (12) Referencias normativas ESF+, EAA, WCAG 2.2, EN 301 549. Benchmarks: Bonterra, WIOA, Lightcast, Degreed |
