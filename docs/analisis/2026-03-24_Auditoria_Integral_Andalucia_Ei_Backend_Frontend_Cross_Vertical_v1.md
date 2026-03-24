# Auditoría Integral — Andalucía +ei Backend, Frontend y Correlaciones Cross-Vertical

> **Versión:** 1.0.0
> **Fecha de creación:** 2026-03-24
> **Última actualización:** 2026-03-24
> **Autor:** Claude Opus 4.6 (1M context)
> **Estado:** Completado
> **Categoría:** Auditoría de Arquitectura + Gap Analysis Cross-Vertical
> **Módulos auditados:** `jaraba_andalucia_ei` (principal), `jaraba_candidate` (Empleabilidad), `jaraba_business_tools` (Emprendimiento), `jaraba_mentoring`, `ecosistema_jaraba_core`, `ecosistema_jaraba_theme`
> **Documentos de referencia:** 9 documentos en `docs/andaluciamasei/` (Informe Estratégico, Diseño Formativo v2, Catálogo Servicios, Integración Catálogo, Guía Didáctica Formador, Sesión Informativa, Estrategia Prospección, Specs Platform)
> **Directrices raíz:** TENANT-001, PREMIUM-FORMS-PATTERN-001, SETUP-WIZARD-DAILY-001, IMPLEMENTATION-CHECKLIST-001, PIPELINE-E2E-001, ZERO-REGION-001, CSS-VAR-ALL-COLORS-001, ICON-CONVENTION-001
> **Subvención:** SC/ICV/0111/2025 | 202.500 EUR | FSE+ 85% UE + 15% Junta de Andalucía

---

## Índice de Navegación

- [1. Resumen Ejecutivo](#1-resumen-ejecutivo)
- [2. Metodología de Auditoría](#2-metodología-de-auditoría)
- [3. Inventario del Backend](#3-inventario-del-backend)
  - [3.1 Andalucía +ei: módulo principal](#31-andalucía-ei-módulo-principal)
  - [3.2 Empleabilidad (jaraba_candidate)](#32-empleabilidad-jaraba_candidate)
  - [3.3 Emprendimiento (jaraba_business_tools)](#33-emprendimiento-jaraba_business_tools)
  - [3.4 Cross-Vertical Bridges](#34-cross-vertical-bridges)
- [4. Inventario del Frontend](#4-inventario-del-frontend)
  - [4.1 Templates y parciales (31 ficheros)](#41-templates-y-parciales-31-ficheros)
  - [4.2 SCSS/CSS (13 módulos SCSS)](#42-scsscss-13-módulos-scss)
  - [4.3 JavaScript (11 ficheros)](#43-javascript-11-ficheros)
  - [4.4 Integración con el tema](#44-integración-con-el-tema)
- [5. Confrontación: Documentación vs Implementación](#5-confrontación-documentación-vs-implementación)
  - [5.1 Entidades: documentadas vs implementadas](#51-entidades-documentadas-vs-implementadas)
  - [5.2 Requisitos funcionales: documentados vs implementados](#52-requisitos-funcionales-documentados-vs-implementados)
  - [5.3 Roles y permisos: documentados vs implementados](#53-roles-y-permisos-documentados-vs-implementados)
  - [5.4 Agentes IA: documentados vs implementados](#54-agentes-ia-documentados-vs-implementados)
  - [5.5 Cross-Vertical: documentado vs implementado](#55-cross-vertical-documentado-vs-implementado)
  - [5.6 Frontend/UX: documentado vs implementado](#56-frontendux-documentado-vs-implementado)
  - [5.7 Compliance FSE+: documentado vs implementado](#57-compliance-fse-documentado-vs-implementado)
- [6. Gaps P0/P1/P2 Consolidados](#6-gaps-p0p1p2-consolidados)
- [7. Tabla de Cumplimiento de Directrices del Proyecto](#7-tabla-de-cumplimiento-de-directrices-del-proyecto)
- [8. Evaluación 10/10 Clase Mundial](#8-evaluación-1010-clase-mundial)
- [9. Verificación RUNTIME-VERIFY-001](#9-verificación-runtime-verify-001)
- [10. Recomendaciones Priorizadas](#10-recomendaciones-priorizadas)
- [11. Glosario de Siglas](#11-glosario-de-siglas)

---

## 1. Resumen Ejecutivo

Esta auditoría integral confronta el estado real del SaaS (backend + frontend + cross-vertical) con los 9 documentos de configuración de la 2ª Edición del Programa Andalucía +ei (`docs/andaluciamasei/`). El análisis cubre 3 verticales (Andalucía +ei, Empleabilidad, Emprendimiento), 42.000+ líneas de PHP, 31 templates Twig, 13 módulos SCSS, 11 ficheros JS, y 80+ requisitos funcionales extraídos de la documentación.

### Métricas de estado

| Dimensión | Implementado | Requerido por docs | Cobertura |
|-----------|-------------|-------------------|-----------|
| Entidades (Content) | 16 | 21 | **76%** |
| Servicios registrados | 65+ | 75+ estimados | **~85%** |
| Requisitos funcionales B (features) | ~55 | 80+ | **~68%** |
| Roles del programa | 4/4 (recién corregidos) | 4/4 | **100%** |
| Agentes IA configurados por fase | 1 genérico | 6 fases distintas | **17%** |
| Cross-vertical bridges | 7 services | 8 verticales referenciados | **~75%** |
| Templates frontend | 31 | 35+ estimados | **~88%** |
| Compliance FSE+ | 7/9 requisitos | 9 requisitos | **78%** |
| 5 Packs catálogo digital | 0 | 5 packs × 3 modalidades | **0%** |
| Portfolio 29 entregables | 0 (entity existe, UI no) | Portfolio completo con validación | **0%** |
| CRM Prospección clientes piloto | Parcial (ProspeccionEmpresarial entity) | Pipeline Kanban 6 fases | **30%** |
| Control asistencia por sesión | Parcial (InscripcionSesionEi) | Completo con hojas PDF + alertas | **50%** |

### Hallazgo principal

El SaaS tiene una **base arquitectónica sólida** (16 entities, 65 servicios, 31 templates, patrón premium) pero presenta **3 gaps estructurales** que la documentación de la 2ª Edición exige resolver:

1. **Sistema de Entregables y Portfolio** (REQ-POR-001 a 004): No existe UI para que el participante vea y gestione sus 29 entregables. La entity `ExpedienteDocumento` existe pero está orientada a documentos administrativos, no a entregables formativos.
2. **Catálogo Digital de 5 Packs** (REQ-CAT-001 a 004): Los 5 packs de servicios profesionales (Contenido Digital, Asistente Virtual, Presencia Online, Tienda Digital, Community Manager) no tienen representación ni en entities ni en UI. Es el eje vertebrador de toda la 2ª Edición.
3. **Copiloto IA contextualizado por fase** (REQ-IA-001 a 007): El copiloto existe (`AndaluciaEiCopilotContextProvider`) pero no diferencia entre las 6 fases del programa. Los system prompts prediseñados por sesión no están implementados.

---

## 2. Metodología de Auditoría

1. **Exploración exhaustiva del backend** — inventario completo de 3 módulos via 4 agentes especializados en paralelo
2. **Exploración exhaustiva del frontend** — templates, SCSS, JS, page templates, integración tema
3. **Extracción de requisitos de documentación** — lectura íntegra de 9 documentos (288K chars) → 80+ requisitos codificados
4. **Tabla cruzada** — confrontación requisito por requisito entre docs y código
5. **Verificación RUNTIME-VERIFY-001** — contraste "el código existe" vs "el usuario lo experimenta"
6. **Evaluación cross-vertical** — análisis de bridges entre Andalucía +ei, Empleabilidad y Emprendimiento

---

## 3. Inventario del Backend

### 3.1 Andalucía +ei: módulo principal

**Módulo:** `jaraba_andalucia_ei` | **Código:** 42.288 líneas PHP | **Dependencias:** ecosistema_jaraba_core, jaraba_sepe_teleformacion, jaraba_lms, jaraba_mentoring, jaraba_copilot_v2

| Componente | Cantidad | Detalles |
|------------|----------|---------|
| Content Entities | 16 | programa_participante_ei, solicitud_ei, expediente_documento, actuacion_sto, indicador_fse_plus, insercion_laboral, prospeccion_empresarial, accion_formativa_ei, sesion_programada_ei, inscripcion_sesion_ei, plan_formativo_ei, material_didactico_ei, plan_emprendimiento_ei, ficha_tecnica_ei, rol_programa_log, staff_profile_ei |
| Services | 65+ | Incluye: FaseTransitionManager, VoboSaeWorkflowService, CoordinadorHubService, SolicitudTriageService, ExpedienteCompletenessService, FirmaWorkflowService, RolProgramaService, y 55+ más |
| Controllers | 26 | Dashboards (Coord/Orient/Formador/Participante), FormControllers (slide-panel), API, Landing, Reclutamiento, Firma |
| Forms | 35 | Todos extienden PremiumEntityFormBase (PREMIUM-FORMS-PATTERN-001) |
| Access Handlers | 16 | Uno por entity, todos verifican tenant (TENANT-ISOLATION-ACCESS-001) |
| Setup Wizard Steps | 10 | Coordinador: 4, Orientador: 3, Formador: 3 |
| Daily Actions | 13 | Coordinador: 6, Orientador: 4, Formador: 3 |
| Routes | 40+ | /andalucia-ei/*, /admin/content/*, /api/v1/andalucia-ei/* |
| Permissions | 50+ | Granulares por entity + 6 de gateway por rol |
| Templates | 31 | 20 principales + 11 parciales |
| JS files | 11 | Dashboard, portal, formularios, firma, calendario, reclutamiento |
| Update hooks | 31 | 10001-10031, cubren todas las entities |

### 3.2 Empleabilidad (jaraba_candidate)

**Módulo:** `jaraba_candidate` | **Entities:** 6 (candidate_profile, candidate_experience, candidate_education, candidate_skill, candidate_language, copilot_conversation)

**Servicios clave para Andalucía +ei:**
- `CandidateProfileService` — gestión del perfil profesional
- `SkillsService` — evaluación de competencias
- `CvGeneratorService` — generación de CV con IA
- `InterviewPrepService` — preparación de entrevistas
- `EmployabilityCopilotBridgeService` — copiloto contextualizado

**Relevancia para el programa:** Los participantes de **ruta B (empleo)** necesitan acceso completo a este vertical para CV, matching, y preparación de entrevistas.

### 3.3 Emprendimiento (jaraba_business_tools)

**Módulo:** `jaraba_business_tools` | **Entities:** 8 (business_canvas, business_plan, financial_projection, mvp_validation, sroi_calculator, business_idea, canvas_hypothesis, pitch_deck)

**Servicios clave para Andalucía +ei:**
- `CanvasService` — Lean Canvas interactivo (REQ-LCA-001)
- `MvpValidationService` — validación de hipótesis (REQ-LCA-003)
- `FinancialProjectionService` — previsión financiera (REQ-FIN-003)
- `SroiCalculatorService` — retorno social de inversión
- `EmprendimientoCopilotBridgeService` — copiloto startup

**Relevancia para el programa:** Los participantes de **ruta A (autoempleo)** necesitan Lean Canvas, validación, y proyecciones financieras. Los de **ruta híbrida** necesitan acceso parcial.

### 3.4 Cross-Vertical Bridges

| Bridge Service | Módulo fuente | Conecta con | Estado |
|---------------|--------------|-------------|--------|
| `EiEmprendimientoBridgeService` | andalucia_ei | jaraba_business_tools (Canvas, MVP, Projection, SROI) | **Implementado** — 4 servicios opcionales (@?) |
| `EiMatchingBridgeService` | andalucia_ei | jaraba_matching + jaraba_candidate (profile, skills) | **Implementado** — 4 servicios opcionales |
| `EiAlumniBridgeService` | andalucia_ei | jaraba_mentoring (mentor matching) | **Implementado** |
| `EiBadgeBridgeService` | andalucia_ei | ecosistema_jaraba_core (badge award) | **Implementado** |
| `ProgramaVerticalAccessService` | andalucia_ei | state (acceso temporal a verticales) | **Implementado** — gestiona permisos temporales por carril |
| Content Hub bridge | andalucia_ei | jaraba_content_hub | **NO IMPLEMENTADO** — docs exigen integración Módulo 4 |
| ComercioConecta bridge | andalucia_ei | jaraba_comercioconecta | **NO IMPLEMENTADO** — docs exigen para Pack 4 |
| JarabaLex bridge | andalucia_ei | jaraba_jarabalex | **NO IMPLEMENTADO** — docs exigen para Módulo 3 |

---

## 4. Inventario del Frontend

### 4.1 Templates y parciales (31 ficheros)

**Dashboards y portales (5):**
- `coordinador-dashboard.html.twig` — Hub coordinador con 314 {% trans %}
- `orientador-dashboard.html.twig` — Dashboard orientador
- `formador-dashboard.html.twig` — Dashboard formador (recién creado)
- `participante-portal.html.twig` — Portal participante con health score, timeline, expediente
- `andalucia-ei-dashboard.html.twig` — Dashboard principal vertical

**Landing y conversión (5):**
- `andalucia-ei-landing.html.twig` — 7 secciones: Hero > Stats > Features > Content > Testimonials > FAQ > CTA
- `andalucia-ei-reclutamiento.html.twig` — Flujo de reclutamiento (189 {% trans %})
- `solicitud-ei-page.html.twig` — Formulario público
- `solicitud-confirmada.html.twig` — Confirmación
- `andalucia-ei-leads-guia.html.twig` — Guía leads

**Contenido educativo (4):**
- `programa-formacion.html.twig` — Programa formativo
- `programa-mentores.html.twig` — Programa de mentoría
- `andalucia-ei-guia-participante.html.twig` — Guía del participante
- `comunidad-alumni.html.twig` — Club alumni

**Admin y tracking (5):**
- `expediente-hub.html.twig` — Hub documental
- `insercion-laboral.html.twig` — Tracking inserción
- `indicador-fse-plus.html.twig` — Indicadores FSE+
- `actuacion-sto.html.twig` — Actuaciones STO
- `andalucia-ei-impacto-publico.html.twig` — Métricas de impacto

**Firma electrónica (2):**
- `andalucia-ei-verificacion-documento.html.twig` — Verificación
- `andalucia-ei-firma-masiva.html.twig` — Firma masiva

**Parciales (11):**
- `_participante-hero.html.twig` — Hero con health score SVG
- `_participante-acciones.html.twig` — Tarjetas de acción
- `_participante-expediente.html.twig` — Sección expediente
- `_participante-formacion.html.twig` — Progreso formación
- `_participante-logros.html.twig` — Logros y badges
- `_participante-mensajeria.html.twig` — Mensajería copilot
- `_participante-timeline.html.twig` — Timeline de fases
- `_firma-pad.html.twig` — Interfaz firma táctil
- `_firma-pendientes.html.twig` — Firmas pendientes
- `_firma-masiva.html.twig` — Firma masiva UI
- `_historia-exito.html.twig` — Caso de éxito

**Compliance i18n:** 882 bloques {% trans %} en 20 ficheros. **100% compliance** (block-style, no filter-style).
**Compliance iconos:** 190 llamadas jaraba_icon() en 15 ficheros. **100% compliance** (ICON-CONVENTION-001).
**Compliance include-only:** Todos los parciales usan `{% include ... with { ... } only %}`. **100%**.

### 4.2 SCSS/CSS (13 módulos SCSS)

Entry point: `scss/main.scss` con `@use` (Dart Sass moderno). Compila a `css/andalucia-ei.css` (165 KB).

Módulos: `_landing`, `_participante-portal`, `_dashboard`, `_solicitud-form`, `_reclutamiento`, `_reclutamiento-popup`, `_guia`, `_entities`, `_firmas`, `_firma-masiva`, `_alumni`, `_impacto`, `_recurrence-form`, `_leads-guia`.

**CSS-VAR-ALL-COLORS-001:** Tokens propios `--aei-primary: var(--ej-color-impulse)`, `--aei-secondary: var(--ej-color-innovation)`. **0 hex hardcoded. 100% compliance.**

### 4.3 JavaScript (11 ficheros)

Dashboard (2), Portal (1), Forms (3), Firma (2), Calendario (2), Reclutamiento (1). Todos usan `Drupal.behaviors` + `once()`. drupalSettings inyectado via `hook_page_attachments()`.

### 4.4 Integración con el tema

- 3 page templates en el tema: `page--andalucia-ei.html.twig`, `page--andalucia-ei--programa.html.twig`, `page--andalucia-ei--case-study.html.twig`
- ZERO-REGION-001: `clean_content` + `clean_messages` (no `page.content`)
- Body classes: `.page-andalucia-ei`, `.vertical-andalucia-ei`
- Header/footer incluidos con `only` keyword (MEGAMENU-INJECT-001 compliance)

---

## 5. Confrontación: Documentación vs Implementación

### 5.1 Entidades: documentadas vs implementadas

| Entity (documentación) | Entity (SaaS) | Estado | Gaps |
|------------------------|--------------|--------|------|
| Participante (30+ campos) | `programa_participante_ei` (30+ campos) | **✅ Implementada** | Faltan: `ruta` (A/B/híbrida), `pack_preseleccionado`, `pack_confirmado`, `nivel_digital`, `objetivos_smart` |
| Solicitud | `solicitud_ei` (25+ campos) | **✅ Implementada** | OK |
| Expediente/Documentos | `expediente_documento` (18+ campos) | **✅ Implementada** | Orientada a admin, no a entregables formativos |
| Actuación STO | `actuacion_sto` (22+ campos) | **✅ Implementada** | OK |
| Indicador FSE+ | `indicador_fse_plus` (15+ campos) | **✅ Implementada** | OK |
| Inserción Laboral | `insercion_laboral` (16+ campos) | **✅ Implementada** | Falta: `meses_ss_acumulados` como campo calculado auto |
| Prospección Empresarial | `prospeccion_empresarial` (14+ campos) | **✅ Implementada** | Falta: `urgencia_necesidad_digital`, `pack_compatible`, pipeline de 6 fases |
| Acción Formativa | `accion_formativa_ei` | **✅ Implementada** | OK |
| Sesión Programada | `sesion_programada_ei` | **✅ Implementada** | Falta: `tipo_modalidad` (presencial/online_sincronico), enlace videoconferencia |
| Inscripción Sesión | `inscripcion_sesion_ei` | **✅ Implementada** | Falta: distinguir asistencia presencial vs online sincrónica |
| Plan Formativo | `plan_formativo_ei` | **✅ Implementada** | OK |
| Material Didáctico | `material_didactico_ei` | **✅ Implementada** | OK |
| Plan Emprendimiento | `plan_emprendimiento_ei` | **✅ Implementada** | OK |
| Ficha Técnica PIIL | `ficha_tecnica_ei` | **✅ Implementada** | OK |
| Negocio Prospectado | — | **❌ NO EXISTE** | Entity dedicada para clientes piloto con pipeline 6 fases, matching, acuerdo prueba |
| Entregable Formativo | — | **❌ NO EXISTE** | 29 entregables por participante, con estado (pendiente/completado/validado), por módulo/sesión |
| Evaluación Competencia IA | — | **❌ NO EXISTE** | Rúbrica 4 niveles, por participante, acumulativa |
| Asistencia (detallada) | `inscripcion_sesion_ei` (parcial) | **⚠️ Parcial** | Falta: tipo_asistencia (presencial/online_sincronico), evidencia (foto/log conexión) |
| Pack Servicio (catálogo) | — | **❌ NO EXISTE** | 5 packs × 3 modalidades, personalizable, publicable, con Stripe |
| Ficha Producto/Servicio | — | **❌ NO EXISTE** | Precio, coste variable, margen bruto. Pre-rellena con pack |

**Resumen: 16/21 entities implementadas (76%). 5 entities nuevas requeridas.**

### 5.2 Requisitos funcionales: documentados vs implementados

#### Categoría: Gestión del Programa (REQ-AEI)

| REQ | Descripción | Estado | Detalle |
|-----|-------------|--------|---------|
| AEI-001 | Dashboard con KPIs tiempo real | **✅** | CoordinadorHubService + template |
| AEI-002 | Lista participantes con filtros | **✅** | Views integration + entity list |
| AEI-003 | Ficha individual participante | **✅** | Entity form + portal |
| AEI-004 | Control asistencia digital | **⚠️ Parcial** | InscripcionSesionEi existe pero sin distinguir presencial/online ni alertas <75% |
| AEI-005 | Generador informes FSE+ | **✅** | IndicadoresEsfService + StoExportService |
| AEI-006 | Registro horas equipo | **❌** | No existe entity ni UI para que el equipo registre horas de dedicación |

#### Categoría: Portfolio y Entregables (REQ-POR)

| REQ | Descripción | Estado | Detalle |
|-----|-------------|--------|---------|
| POR-001 | Portfolio digital 29 entregables | **❌** | No existe UI de portfolio. ExpedienteDocumento es admin, no entregables formativos |
| POR-002 | Validación entregables por formador | **❌** | No existe flujo de validación formador → entregable |
| POR-003 | Autoevaluación rúbrica IA | **❌** | No existe entity EvaluaciónCompetenciaIA ni formulario |
| POR-004 | Portfolio público compartible | **❌** | No existe URL pública de portfolio |

#### Categoría: Copiloto IA (REQ-COP)

| REQ | Descripción | Estado | Detalle |
|-----|-------------|--------|---------|
| COP-001 | Contexto persistente participante | **⚠️ Parcial** | AndaluciaEiCopilotContextProvider existe pero no incluye pack ni ruta |
| COP-002 | Modo formación (mentor) | **❌** | System prompt genérico, no diferenciado por fase |
| COP-003 | Prompts prediseñados por sesión | **❌** | No existen prompts sugeridos por sesión (OI-1.1, M0-1, etc.) |
| COP-004 | Historial revisable por formador | **⚠️ Parcial** | copilot_conversation entity existe pero sin UI formador |
| COP-005 | Detección alucinaciones | **⚠️ Parcial** | AI-GUARDRAILS-PII-001 existe pero no cubre datos factuales/numéricos |

#### Categoría: CRM Prospección (REQ-CRM)

| REQ | Descripción | Estado | Detalle |
|-----|-------------|--------|---------|
| CRM-001 | Pipeline visual Kanban 6 fases | **❌** | ProspeccionEmpresarial entity existe pero sin UI Kanban |
| CRM-002 | Ficha negocio con historial | **⚠️ Parcial** | Entity tiene campos básicos pero sin historial interacciones |
| CRM-003 | Clasificación por colores urgencia | **❌** | No existe campo urgencia_necesidad_digital |
| CRM-004 | Matching participante-negocio | **⚠️ Parcial** | EiMatchingBridgeService existe pero para empleo, no para clientes piloto |
| CRM-005 | Acuerdo prueba digital | **❌** | No existe plantilla de acuerdo + firma digital para prueba gratuita |
| CRM-006 | Seguimiento post-prueba | **❌** | No existe tracking de satisfacción/conversión |
| CRM-007 | KPIs prospección dashboard | **❌** | No existe sección de KPIs de prospección en coordinador |

#### Categoría: Catálogo Digital Packs (REQ-CAT)

| REQ | Descripción | Estado | Detalle |
|-----|-------------|--------|---------|
| CAT-001 | Plantillas 5 packs pre-configuradas | **❌** | No existen. Los 5 packs son el eje vertebrador de la 2ª Edición |
| CAT-002 | Publicación catálogo 1 clic | **❌** | No existe UI de publicación |
| CAT-003 | Cobro recurrente Stripe | **❌** | Stripe Connect existe en el SaaS pero no conectado a packs |
| CAT-004 | Botón contratación en ficha | **❌** | No existe ficha pública de pack |

#### Categoría: Herramientas Financieras (REQ-FIN)

| REQ | Descripción | Estado | Detalle |
|-----|-------------|--------|---------|
| FIN-001 | Fichas producto/servicio | **❌** | No existe entity ni UI |
| FIN-002 | Calculadora punto equilibrio | **⚠️ Parcial** | FinancialProjectionService en jaraba_business_tools, no integrado |
| FIN-003 | Previsión 12 meses 3 escenarios | **⚠️ Parcial** | Existe en business_tools, no contextualizado para packs |
| FIN-004 | Mapa ayudas personalizado | **❌** | No existe |

#### Categoría: Lean Canvas (REQ-LCA)

| REQ | Descripción | Estado | Detalle |
|-----|-------------|--------|---------|
| LCA-001 | Canvas digital versionable | **✅** | business_canvas entity en jaraba_business_tools |
| LCA-002 | Asistencia IA por bloque | **⚠️ Parcial** | CopilotBridge existe pero sin prompts contextuales por bloque |
| LCA-003 | Fichas prueba hipótesis | **✅** | canvas_hypothesis entity en jaraba_business_tools |

### 5.3 Roles y permisos: documentados vs implementados

| Rol (docs) | Rol (SaaS) | Estado | Gap |
|------------|-----------|--------|-----|
| Director/a de programa | `coordinador_ei` | **✅** | Mapeo correcto (coordinador = director programa) |
| Formador/a principal | `formador_ei` | **✅** | Rol + dashboard + wizard + daily actions (recién implementados) |
| Orientador/a laboral | `orientador_ei` | **✅** | Rol + dashboard + wizard + daily actions |
| Soporte técnico | `administrator` (Drupal) | **✅** | No necesita rol de programa dedicado |
| Participante | Entity-driven (programa_participante_ei) | **✅** | |

**Nota:** Los docs especifican 3-4 formadores (1 principal + 2 especializados). El sistema actual permite asignar `formador_ei` a múltiples usuarios, lo cual cubre este requisito.

### 5.4 Agentes IA: documentados vs implementados

| Fase programa | System prompt requerido (docs) | Estado SaaS |
|---------------|-------------------------------|-------------|
| Orientación Inicial | Exploratorio: preguntas, descubrimiento, sin empujar a pack | **❌** No existe |
| Módulo 0 | Didáctico: explica razonamiento, enseña a formular instrucciones | **❌** No existe |
| Módulos 1-3 | Mentor: cuestiona decisiones débiles, usa contexto pack | **❌** No existe |
| Módulo 4 | Productivo: genera contenido profesional, modo producción | **❌** No existe |
| Módulo 5 | Operativo: genera facturas/emails/propuestas reales | **❌** No existe |
| Acompañamiento | Autónomo: copiloto de negocio continuo | **❌** No existe |

**Estado actual:** Un único `AndaluciaEiCopilotContextProvider` que inyecta contexto básico (nombre, fase, horas) sin diferenciar el comportamiento del agente por fase del programa.

**REQ-IA-001 (6 system prompts por fase):** **0% implementado.**

### 5.5 Cross-Vertical: documentado vs implementado

| Integración requerida (docs) | Bridge existente | Estado |
|-----------------------------|-----------------|--------|
| Empleabilidad → ruta B (CV, matching, entrevistas) | `EiMatchingBridgeService` + `ProgramaVerticalAccessService` | **⚠️ Parcial** — bridge existe pero no conecta CV generation |
| Emprendimiento → ruta A (Canvas, finanzas, validación) | `EiEmprendimientoBridgeService` | **✅** — 4 servicios opcionales conectados |
| Content Hub → Módulo 4 (calendario editorial, publicación) | — | **❌ No existe** bridge |
| ComercioConecta → Pack 4 (tienda digital, pagos) | — | **❌ No existe** bridge |
| AgroConecta → Pack 4 agrario (trazabilidad QR) | — | **❌ No existe** bridge |
| JarabaLex → Módulo 3 (consultas legales, BOE/BOJA) | — | **❌ No existe** bridge |
| ServiciosConecta → Pack 2/5 (CRM clientes, presupuestos) | — | **❌ No existe** bridge |
| Alumni → post-programa (mentoría continuada) | `EiAlumniBridgeService` | **✅** |

### 5.6 Frontend/UX: documentado vs implementado

| Flujo UX (docs) | Implementado | Gap |
|-----------------|-------------|-----|
| Dashboard participante con: progreso módulos, entregables, Canvas, portfolio, catálogo, asistencia %, fecha inserción estimada | **⚠️ Parcial** | Falta: entregables, Canvas integrado, catálogo, fecha inserción |
| 8 fichas autoconocimiento (Orientación Inicial) | **❌** | No existen formularios de fichas 1-8 |
| Editor visual Lean Canvas 9 bloques | **✅** | Via jaraba_business_tools (cross-vertical) |
| Dashboard formador: asistencia rápida, entregables pendientes, KPIs, copilot usage | **⚠️ Parcial** | Dashboard recién creado, falta: entregables, copilot usage |
| Dashboard orientador: notas sesión, tracking inserción, CRM prospección | **⚠️ Parcial** | Dashboard existe, falta: notas sesión individual, CRM prospección |
| Mobile-responsive todas las vistas | **✅** | CSS mobile-first verificado |
| Logos FSE+ en footer | **❌** | No existe parcial con logos cofinanciación |

### 5.7 Compliance FSE+: documentado vs implementado

| Requisito normativo | Estado | Detalle |
|--------------------|--------|---------|
| ≥ 10h orientación por participante (≥ 2h individual) | **✅** | actuacion_sto trackea horas tipo |
| ≥ 50h formación (≤ 20% online = 10h) | **⚠️ Parcial** | Horas formación trackeadas pero sin distinguir presencial/online |
| ≥ 75% asistencia para curso completado | **⚠️ Parcial** | Inscripción existe pero sin alerta automática <75% |
| ≥ 40h acompañamiento por persona insertada | **✅** | actuacion_sto con tipo acompañamiento |
| ≥ 40% inserción (≥ 18 de 45) | **✅** | InsercionLaboral entity + InsercionValidatorService |
| ≥ 4 meses SS (3 agrario) | **⚠️ Parcial** | Campo fecha_alta_ss existe, meses_acumulados no es auto-calculado |
| Hojas servicio PDF firmadas | **✅** | FirmaWorkflowService + HojaServicioMentoriaService |
| Indicadores FSE+ por género, colectivo, resultado | **✅** | IndicadorFsePlus entity + IndicadoresEsfService |
| Logos cofinanciación en pantallas del programa | **❌** | No existe parcial con logos EU/Junta/SAE/FSE+ |

---

## 6. Gaps P0/P1/P2 Consolidados

### P0 — Críticos (bloquean la operación de la 2ª Edición)

| # | Gap | Docs afectados | Impacto |
|---|-----|---------------|---------|
| **GAP-2E-001** | **5 Packs de servicios profesionales no existen** (entity, UI, catálogo, publicación, cobro) | d, e, i | Los packs son el eje vertebrador de toda la 2ª Edición. Sin ellos, la formación no tiene proyecto práctico |
| **GAP-2E-002** | **Portfolio de 29 entregables no existe** (entity EntregableFormativo, UI portfolio, validación formador) | f, i | El formador no puede evaluar, el participante no puede ver su progreso de entregables |
| **GAP-2E-003** | **Campos críticos faltan en ProgramaParticipanteEi**: ruta (A/B/híbrida), pack_preseleccionado, pack_confirmado, nivel_digital, objetivos_smart | c, i | Sin ruta ni pack, el sistema no puede personalizar la experiencia del participante |
| **GAP-2E-004** | **Control asistencia no distingue presencial/online** sincrónico, ni genera alertas <75%, ni exporta hojas PDF por sesión | c, f | Incumplimiento normativo: máximo 20% online debe verificarse automáticamente |
| **GAP-2E-005** | **6 system prompts por fase del copiloto no existen** — comportamiento genérico para todas las fases | f, i | Copiloto no actúa como mentor pedagógico diferenciado. REQ-IA-001 al 0% |

### P1 — Importantes (afectan la calidad clase mundial)

| # | Gap | Docs afectados | Impacto |
|---|-----|---------------|---------|
| **GAP-2E-006** | **CRM prospección sin pipeline Kanban** — ProspeccionEmpresarial entity existe pero sin UI de embudo 6 fases | h, i | Prospección de 50-80 negocios sin herramienta visual |
| **GAP-2E-007** | **Matching participante-negocio para clientes piloto** — bridge existe para empleo pero no para autoempleo/packs | h, i | No hay asignación inteligente participante→negocio |
| **GAP-2E-008** | **8 fichas autoconocimiento OI** — no existen formularios de las fichas 1-8 de la Orientación Inicial | f | Primer contacto del participante sin estructura digital |
| **GAP-2E-009** | **3 bridges cross-vertical faltantes**: Content Hub (M4), ComercioConecta (Pack 4), JarabaLex (M3) | i | Participantes sin acceso a verticales complementarios |
| **GAP-2E-010** | **Prompts prediseñados por sesión** — 3-5 sugerencias contextualizadas por pack por cada una de las 29 sesiones | f, i | ~145 prompts sugeridos no existen |
| **GAP-2E-011** | **Logos cofinanciación FSE+** en footer/header de pantallas del programa | i | Requisito Art. 50 Reg. UE 2021/1060 |
| **GAP-2E-012** | **Evaluación competencia IA** — rúbrica 4 niveles (novel, aprendiz, competente, autónomo) | f, i | Sin medición del progreso de competencia IA del participante |
| **GAP-2E-013** | **Registro horas equipo** — director, formador, orientador registran dedicación (justificación económica) | i | Sin tracking de coste de personal |

### P2 — Mejoras

| # | Gap | Docs afectados | Impacto |
|---|-----|---------------|---------|
| **GAP-2E-014** | **Mapa de ayudas personalizado** — Tarifa Plana, Cuota Cero, ayudas por colectivo/provincia | d, i | Sin orientación financiera automatizada |
| **GAP-2E-015** | **Portfolio público** — URL compartible con entregables seleccionados | i | Sin evidencia de competencia para empleadores/clientes |
| **GAP-2E-016** | **Historial copilot revisable por formador** — UI para que formador vea interacciones IA del participante | i | Sin supervisión de la calidad de supervisión IA |
| **GAP-2E-017** | **Calculadora punto equilibrio contextualizada** — pre-rellenada con datos del pack | d | Tool existe en business_tools pero no contextualizada |
| **GAP-2E-018** | **Sesiones online sincrónicas** — enlace videoconferencia en SesionProgramadaEi, registro conexión | c | Sesión no tiene campo para enlace de videollamada |

---

## 7. Tabla de Cumplimiento de Directrices del Proyecto

| Directriz | Compliance | Nota |
|-----------|-----------|------|
| **TENANT-001** | ✅ 100% | Todas las queries filtran por tenant_id |
| **TENANT-ISOLATION-ACCESS-001** | ✅ 100% | 16 AccessControlHandlers verifican tenant |
| **PREMIUM-FORMS-PATTERN-001** | ✅ 100% | 35 forms extienden PremiumEntityFormBase |
| **SETUP-WIZARD-DAILY-001** | ✅ 100% | 10 wizard steps + 13 daily actions para 3 roles |
| **ZERO-REGION-001** | ✅ 100% | clean_content en page templates |
| **ZERO-REGION-003** | ✅ 100% | drupalSettings via preprocess |
| **SLIDE-PANEL-RENDER-001** | ✅ 100% | renderPlain() en 5 controllers |
| **ICON-CONVENTION-001** | ✅ 100% | 190 jaraba_icon() en 15 templates |
| **CSS-VAR-ALL-COLORS-001** | ✅ 100% | 0 hex hardcoded en SCSS |
| **SCSS-COMPILE-VERIFY-001** | ✅ | CSS timestamp > SCSS timestamp |
| **TWIG-INCLUDE-ONLY-001** | ✅ 100% | Todos los parciales con `only` |
| **CONTROLLER-READONLY-001** | ✅ 100% | Verificado en 26 controllers |
| **ACCESS-RETURN-TYPE-001** | ✅ 100% | AccessResultInterface en 16 handlers |
| **UPDATE-HOOK-REQUIRED-001** | ✅ 100% | 31 update hooks cubren todas las entities |
| **ENTITY-PREPROCESS-001** | ✅ 100% | 23 preprocess functions |
| **OPTIONAL-CROSSMODULE-001** | ✅ 100% | @? para todas las dependencias opcionales |
| **IMPLEMENTATION-CHECKLIST-001** | ⚠️ 95% | 5 entities nuevas necesarias (docs 2ª Edición) |
| **PIPELINE-E2E-001** | ⚠️ 90% | L1-L4 verificado para componentes existentes; gaps en portfolio y packs |

---

## 8. Evaluación 10/10 Clase Mundial

### Score por categoría (confrontación con docs 2ª Edición)

| Categoría | Score | Justificación |
|-----------|-------|---------------|
| **Arquitectura backend** | 9/10 | 16 entities, 65 services, 40+ routes. Sólida. Faltan 5 entities nuevas |
| **Frontend/UX** | 8/10 | 31 templates, mobile-first, i18n 100%, iconos. Falta portfolio, packs |
| **Roles y permisos** | 10/10 | 4 roles completos con dashboard, wizard, daily actions, auditoría |
| **Cross-vertical** | 6/10 | 4/8 bridges implementados. Faltan Content Hub, Comercio, JarabaLex, Servicios |
| **Copiloto IA** | 3/10 | Contexto básico existe. Faltan 6 prompts por fase, prompts por sesión, historial formador |
| **Compliance FSE+** | 7/10 | Indicadores y STO implementados. Falta: asistencia presencial/online, logos, horas equipo |
| **Catálogo 5 Packs** | 0/10 | No existe. Es el eje vertebrador de la 2ª Edición |
| **Portfolio entregables** | 0/10 | No existe UI. Los 29 entregables son la columna vertebral de la formación |
| **CRM Prospección** | 3/10 | Entity existe, falta pipeline Kanban, matching, acuerdos prueba |
| **Herramientas financieras** | 5/10 | Existen en business_tools, no contextualizadas para packs |

**Score global: 5.1/10 — NO es clase mundial para la 2ª Edición**

La arquitectura base es excelente (9/10), pero los **3 pilares nuevos** de la 2ª Edición (Packs, Portfolio, Copiloto contextual) están al 0-3%.

---

## 9. Verificación RUNTIME-VERIFY-001

| # | Check | Estado | Detalle |
|---|-------|--------|---------|
| 1 | CSS compilado (timestamp > SCSS) | **✅** | andalucia-ei.css (165K, Mar 21) > main.scss (Mar 12) |
| 2 | Tablas DB (16 entities) | **✅** | Verificado via drush entity:updates |
| 3 | Rutas accesibles (40+) | **✅** | Verificado via Url::fromRoute() |
| 4 | 3 roles Drupal existen | **✅** | coordinador_ei, orientador_ei, formador_ei |
| 5 | 4 dashboards HTTP 200 | **✅** | Coord, Orient, Formador, Participante |
| 6 | drupalSettings inyectado | **✅** | aeiReclutamiento, jarabaAndaluciaEi |
| 7 | Slide-panel funcional | **✅** | 5 controllers con renderPlain() |
| 8 | Setup Wizard renderiza | **✅** | 10 steps en 3 wizards |
| 9 | Daily Actions renderiza | **✅** | 13 actions en 3 dashboards |
| 10 | Auditoría roles funcional | **✅** | 2 entries en rol_programa_log |
| 11 | Status report OK | **✅** | 3/3 roles, 14/14 entities, 14/14 services |
| 12 | Templates usan {% trans %} | **✅** | 882 bloques verificados |

---

## 10. Recomendaciones Priorizadas

### Sprint A (Pre-lanzamiento — Crítico)

1. **GAP-2E-003** — Añadir 5 campos a ProgramaParticipanteEi: `ruta`, `pack_preseleccionado`, `pack_confirmado`, `nivel_digital`, `objetivos_smart`
2. **GAP-2E-004** — Asistencia presencial/online + alertas <75% + hojas PDF
3. **GAP-2E-005** — 6 system prompts por fase del copiloto
4. **GAP-2E-011** — Parcial logos cofinanciación FSE+

### Sprint B (Lanzamiento formación)

5. **GAP-2E-001** — Entity `PackServicio` + catálogo digital + Stripe
6. **GAP-2E-002** — Entity `EntregableFormativo` + UI portfolio + validación formador
7. **GAP-2E-008** — 8 fichas autoconocimiento Orientación Inicial
8. **GAP-2E-012** — Entity `EvaluacionCompetenciaIA` + rúbrica 4 niveles

### Sprint C (Post-formación)

9. **GAP-2E-006** — CRM pipeline Kanban para clientes piloto
10. **GAP-2E-007** — Matching participante-negocio
11. **GAP-2E-009** — 3 bridges cross-vertical faltantes
12. **GAP-2E-010** — 145 prompts prediseñados por sesión

### Sprint D (Mejoras continuas)

13-18. Gaps P2 (portfolio público, mapa ayudas, historial copilot, calculadora, etc.)

---

## 11. Glosario de Siglas

| Sigla | Significado |
|-------|------------|
| ACH | Access Control Handler — clase PHP que controla permisos de acceso a entities |
| BMC | Business Model Canvas — lienzo de modelo de negocio (9 bloques) |
| BOJA | Boletín Oficial de la Junta de Andalucía |
| CNAE | Clasificación Nacional de Actividades Económicas |
| CRM | Customer Relationship Management — gestión de relaciones con clientes |
| CSS | Cascading Style Sheets — hojas de estilo |
| DACI | Documento de Aceptación y Compromiso Individual |
| DI | Dependency Injection — inyección de dependencias |
| FSE+ | Fondo Social Europeo Plus — cofinancia el programa al 85% |
| IA | Inteligencia Artificial |
| IAE | Impuesto de Actividades Económicas |
| ICV25 | Inserción CV 2025 — formato de exportación de datos |
| KPI | Key Performance Indicator — indicador clave de rendimiento |
| MEI | Mecanismo de Equidad Intergeneracional — cotización adicional SS |
| MVP | Minimum Viable Product — producto mínimo viable |
| OI | Orientación Inicial — primer componente del programa (10h) |
| PIIL | Programa Integral de Inserción Laboral |
| RETA | Régimen Especial de Trabajadores Autónomos |
| RIASEC | Holland's occupational themes — modelo de intereses vocacionales |
| RGPD | Reglamento General de Protección de Datos |
| SAE | Servicio Andaluz de Empleo |
| SCSS | Sassy CSS — preprocesador CSS |
| SMART | Specific, Measurable, Achievable, Relevant, Time-bound — criterios de objetivos |
| SROI | Social Return on Investment — retorno social de la inversión |
| SS | Seguridad Social |
| SSOT | Single Source of Truth — fuente única de verdad |
| STO | Servicio Técnico de Orientación — sistema de la Junta |
| UX | User Experience — experiencia de usuario |
| VoBo | Visto Bueno — aprobación formal |
