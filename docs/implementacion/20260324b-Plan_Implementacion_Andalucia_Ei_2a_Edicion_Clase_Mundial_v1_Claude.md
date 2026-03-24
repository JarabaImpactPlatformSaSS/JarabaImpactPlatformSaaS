# Plan de Implementación: Andalucía +ei 2ª Edición — De 5.1/10 a 10/10 Clase Mundial

> **Versión:** 1.0.0
> **Fecha:** 2026-03-24
> **Autor:** Claude Opus 4.6 (1M context)
> **Estado:** Propuesta — pendiente de aprobación
> **Prioridad:** P0 (pre-lanzamiento 2ª Edición)
> **Score actual:** 5.1/10 (auditoría `2026-03-24_Auditoria_Integral_Andalucia_Ei_Backend_Frontend_Cross_Vertical_v1.md`)
> **Score objetivo:** 10/10 clase mundial
> **Módulos afectados:** `jaraba_andalucia_ei`, `jaraba_candidate`, `jaraba_business_tools`, `jaraba_copilot_v2`, `ecosistema_jaraba_core`, `ecosistema_jaraba_theme`
> **Documentos de referencia:** 9 documentos en `docs/andaluciamasei/` (a-i)
> **Directriz raíz:** TENANT-001, PREMIUM-FORMS-PATTERN-001, SETUP-WIZARD-DAILY-001, ZERO-REGION-001, SLIDE-PANEL-RENDER-001, CSS-VAR-ALL-COLORS-001, ICON-CONVENTION-001, SCSS-COMPILE-VERIFY-001, PIPELINE-E2E-001, IMPLEMENTATION-CHECKLIST-001, RUNTIME-VERIFY-001

---

## Índice de Navegación (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Diagnóstico: Por qué 5.1/10 no basta](#2-diagnóstico-por-qué-5110-no-basta)
   - 2.1 [El cambio de paradigma de la 2ª Edición](#21-el-cambio-de-paradigma-de-la-2ª-edición)
   - 2.2 [Los 3 pilares ausentes](#22-los-3-pilares-ausentes)
   - 2.3 [Caso de uso fallido: María completa el Módulo 5](#23-caso-de-uso-fallido-maría-completa-el-módulo-5)
3. [Arquitectura de la Solución](#3-arquitectura-de-la-solución)
   - 3.1 [Principio: Pack como entidad vertebradora](#31-principio-pack-como-entidad-vertebradora)
   - 3.2 [Principio: Portfolio de entregables como evidencia acumulativa](#32-principio-portfolio-de-entregables-como-evidencia-acumulativa)
   - 3.3 [Principio: Copiloto IA adaptativo por fase](#33-principio-copiloto-ia-adaptativo-por-fase)
   - 3.4 [Principio: CRM de prospección con pipeline visual](#34-principio-crm-de-prospección-con-pipeline-visual)
   - 3.5 [Principio: Asistencia dual presencial/online con compliance](#35-principio-asistencia-dual-presencialonline-con-compliance)
   - 3.6 [Diagrama de flujo completo del participante](#36-diagrama-de-flujo-completo-del-participante)
4. [Especificaciones Técnicas Detalladas](#4-especificaciones-técnicas-detalladas)
   - 4.1 [SPEC-2E-001: Campos nuevos en ProgramaParticipanteEi](#41-spec-2e-001-campos-nuevos-en-programaparticipanteei)
   - 4.2 [SPEC-2E-002: Entity PackServicioEi (5 packs × 3 tiers)](#42-spec-2e-002-entity-packservicioei-5-packs--3-tiers)
   - 4.3 [SPEC-2E-003: Entity EntregableFormativoEi (29 entregables)](#43-spec-2e-003-entity-entregableformativoei-29-entregables)
   - 4.4 [SPEC-2E-004: Entity NegocioProspectadoEi (CRM clientes piloto)](#44-spec-2e-004-entity-negocioprospectadoei-crm-clientes-piloto)
   - 4.5 [SPEC-2E-005: Entity EvaluacionCompetenciaIaEi (rúbrica 4 niveles)](#45-spec-2e-005-entity-evaluacioncompetenciaiaei-rúbrica-4-niveles)
   - 4.6 [SPEC-2E-006: Entity AsistenciaDetalladaEi (presencial/online)](#46-spec-2e-006-entity-asistenciadetalladaei-presencialonline)
   - 4.7 [SPEC-2E-007: CopilotPhaseConfigService (6 system prompts)](#47-spec-2e-007-copilotphaseconfigservice-6-system-prompts)
   - 4.8 [SPEC-2E-008: PortfolioEntregablesService + UI](#48-spec-2e-008-portfolioentregablesservice--ui)
   - 4.9 [SPEC-2E-009: CatalogoPacksService + UI pública](#49-spec-2e-009-catalogopacksservice--ui-pública)
   - 4.10 [SPEC-2E-010: ProspeccionPipelineService + UI Kanban](#410-spec-2e-010-prospeccionpipelineservice--ui-kanban)
   - 4.11 [SPEC-2E-011: AsistenciaComplianceService + alertas](#411-spec-2e-011-asistenciacomplianceservice--alertas)
   - 4.12 [SPEC-2E-012: 3 Bridges cross-vertical nuevos](#412-spec-2e-012-3-bridges-cross-vertical-nuevos)
   - 4.13 [SPEC-2E-013: Parcial logos cofinanciación FSE+](#413-spec-2e-013-parcial-logos-cofinanciación-fse)
   - 4.14 [SPEC-2E-014: 8 fichas autoconocimiento OI](#414-spec-2e-014-8-fichas-autoconocimiento-oi)
   - 4.15 [SPEC-2E-015: Prompts prediseñados por sesión](#415-spec-2e-015-prompts-prediseñados-por-sesión)
5. [Pipeline E2E por Componente](#5-pipeline-e2e-por-componente)
6. [Tabla de Correspondencia con Directrices](#6-tabla-de-correspondencia-con-directrices)
7. [Tabla de Correspondencia con Especificaciones Técnicas](#7-tabla-de-correspondencia-con-especificaciones-técnicas)
8. [Plan de Fases (4 Sprints)](#8-plan-de-fases-4-sprints)
   - 8.1 [Sprint A — Pre-lanzamiento (P0 críticos)](#81-sprint-a--pre-lanzamiento-p0-críticos)
   - 8.2 [Sprint B — Lanzamiento formación (Packs + Portfolio)](#82-sprint-b--lanzamiento-formación-packs--portfolio)
   - 8.3 [Sprint C — Post-formación (CRM + Bridges + Prompts)](#83-sprint-c--post-formación-crm--bridges--prompts)
   - 8.4 [Sprint D — Mejoras continuas](#84-sprint-d--mejoras-continuas)
9. [Verificación RUNTIME-VERIFY-001 por Sprint](#9-verificación-runtime-verify-001-por-sprint)
10. [Salvaguardas y Validadores Propuestos](#10-salvaguardas-y-validadores-propuestos)
11. [Riesgos y Mitigaciones](#11-riesgos-y-mitigaciones)
12. [Criterios de Aceptación 10/10 Clase Mundial](#12-criterios-de-aceptación-1010-clase-mundial)
13. [Glosario de Siglas](#13-glosario-de-siglas)

---

## 1. Resumen Ejecutivo

La 2ª Edición del Programa Andalucía +ei introduce un **cambio de paradigma**: de un programa de orientación/formación clásico a un programa donde la **supervisión de agentes IA es la competencia profesional central** y los **5 Packs de servicios profesionales** son el proyecto vertebrador de toda la formación. El SaaS tiene una arquitectura base excelente (16 entities, 65 servicios, 31 templates) pero le falta el "layer de producto" de la 2ª Edición.

### Objetivo

Elevar de **5.1/10** a **10/10** clase mundial implementando los 18 gaps identificados en la auditoría, organizados en 4 sprints:

| Sprint | Foco | Gaps cubiertos | Impacto en score |
|--------|------|---------------|-----------------|
| **A** (pre-lanzamiento) | Campos participante + asistencia + copiloto + logos | GAP-2E-003, 004, 005, 011 | 5.1 → 6.8 |
| **B** (lanzamiento formación) | Packs + portfolio + fichas OI + rúbrica | GAP-2E-001, 002, 008, 012 | 6.8 → 8.5 |
| **C** (post-formación) | CRM Kanban + matching + bridges + prompts | GAP-2E-006, 007, 009, 010 | 8.5 → 9.5 |
| **D** (mejoras) | Portfolio público + ayudas + calculadora + historial | GAP-2E-013 a 018 | 9.5 → 10.0 |

### Ficheros nuevos estimados: ~45 PHP + ~8 Twig + ~5 SCSS + ~3 JS + ~3 YAML
### Ficheros modificados estimados: ~15

---

## 2. Diagnóstico: Por qué 5.1/10 no basta

### 2.1 El cambio de paradigma de la 2ª Edición

La 1ª Edición era un programa de inserción laboral convencional: orientación + formación en emprendimiento genérico + acompañamiento. Logró un 46% de inserción (23 de 50 participantes).

La 2ª Edición cambia radicalmente:

| Dimensión | 1ª Edición | 2ª Edición |
|-----------|-----------|-----------|
| **Competencia central** | Emprendimiento genérico | Supervisión de agentes IA |
| **Proyecto práctico** | Ejercicios académicos | Pack de servicios real con cliente piloto |
| **Herramienta de trabajo** | Excel, Word, plantillas | Jaraba Impact Platform con IA |
| **Resultado tangible** | Cuaderno de ejercicios | Negocio facturando con Stripe |
| **Modalidad formación** | 100% videoconferencia | 80% presencial + 20% online sincrónico |
| **Copiloto IA** | No existía | 6 modos por fase, prompts por sesión |
| **Evaluación** | Asistencia | Rúbrica de competencia IA + 29 entregables |

### 2.2 Los 3 pilares ausentes

1. **Catálogo de 5 Packs** (docs d, e) — El participante elige un pack (Contenido Digital, Asistente Virtual, Presencia Online, Tienda Digital, Community Manager) que se convierte en el hilo conductor de TODA la formación. Cada módulo construye una pieza operativa del pack. Sin los packs, la formación es genérica y no produce negocio real.

2. **Portfolio de 29 entregables** (doc f) — Cada sesión genera un entregable tangible: desde el perfil profesional (OI-1.1) hasta el plan de 30 días post-formación (M5-3). El formador valida, el participante acumula, y al final tiene un negocio documentado. Sin el portfolio, no hay medición del progreso.

3. **Copiloto IA contextual** (docs f, i) — El copiloto debe cambiar de comportamiento según la fase: exploratorio en Orientación, didáctico en Módulo 0, mentor en Módulos 1-3, productivo en Módulo 4, operativo en Módulo 5, autónomo en Acompañamiento. Sin esta diferenciación, el IA es genérica y no refuerza el aprendizaje.

### 2.3 Caso de uso fallido: María completa el Módulo 5

**Escenario:** María, participante en Sevilla, ha elegido Pack 1 (Contenido Digital). Acaba de completar el Módulo 5 y debe presentar su proyecto piloto con un cliente real.

**Flujo esperado (según docs):**
```
1. María abre /andalucia-ei/mi-participacion
2. Ve su portfolio: 24/29 entregables completados, 5 validados por formador
3. Ve su Pack publicado en catálogo digital con URL compartible
4. Ve su cliente piloto asignado (Peluquería María José — pipeline: "acuerdo firmado")
5. Genera propuesta comercial personalizada con copiloto IA (modo operativo)
6. Envía factura via Stripe al cliente piloto → primera facturación real
7. Formador supervisa las interacciones IA de María → valida competencia nivel 3
```

**Flujo actual (SaaS):**
```
1. María abre /andalucia-ei/mi-participacion
2. Ve timeline de fases, expediente documental, health score → CORRECTO
3. No ve portfolio de entregables → NO EXISTE
4. No ve pack publicado → NO EXISTE
5. No ve cliente piloto asignado → PROSPECCIÓN sin pipeline visual
6. Copiloto IA responde igual que en Orientación → SIN CONTEXTUALIZACIÓN POR FASE
7. Formador no puede ver interacciones IA → SIN UI DE SUPERVISIÓN
```

**Resultado:** María tiene la infraestructura de gestión (fases, expediente, horas) pero le falta la infraestructura de PRODUCTO (pack, portfolio, catálogo, copiloto contextual).

---

## 3. Arquitectura de la Solución

### 3.1 Principio: Pack como entidad vertebradora

El Pack de servicios profesionales es la **pieza central** de la 2ª Edición. Cada participante selecciona 1-2 packs durante la Orientación Inicial y todo el programa se construye alrededor de ese pack.

**Entity `PackServicioEi`** (ConfigEntity o ContentEntity):
- 5 packs predefinidos, cada uno con 2-3 modalidades (Básico/Estándar/Premium)
- El participante personaliza sobre la plantilla (descripción, precio ±15%)
- Se publica en catálogo digital con URL pública
- Se conecta con cobro recurrente via Stripe Connect

**Relación con el participante:** Campo `pack_confirmado` en `ProgramaParticipanteEi` como `list_string` referenciando el pack seleccionado.

**Relación con la formación:** Cada sesión del programa genera un entregable PARA el pack del participante. El Lean Canvas, la previsión financiera, la web, el calendario editorial — todo es sobre el pack real, no ejercicios genéricos.

### 3.2 Principio: Portfolio de entregables como evidencia acumulativa

Los 29 entregables son la **columna vertebral** de la evaluación. Cada sesión produce un output tangible que se acumula en el portfolio del participante.

**Entity `EntregableFormativoEi`** (ContentEntity):
- 29 entregables predefinidos, cada uno vinculado a una sesión (OI-1.1, M0-1, etc.)
- Estados: `pendiente` → `en_progreso` → `completado` → `validado`
- Validación por formador con timestamp y nota
- Archivo adjunto (PDF, imagen, URL)
- Flag `generado_con_ia` para tracking de uso de copiloto

**UI Portfolio:** Parcial `_portfolio-entregables.html.twig` incluido en el portal del participante y visible para el formador en la ficha individual.

### 3.3 Principio: Copiloto IA adaptativo por fase

El copiloto IA de Andalucía +ei debe cambiar su comportamiento según la fase del programa del participante. Esto se implementa via `CopilotPhaseConfigService` que genera system prompts dinámicos.

**6 configuraciones de system prompt:**

| Fase | Comportamiento | Variables inyectadas |
|------|---------------|---------------------|
| **Orientación Inicial** | Exploratorio: preguntas, descubrimiento, no empujar a pack | nombre, colectivo, provincia |
| **Módulo 0** | Didáctico: explica razonamiento, enseña a formular instrucciones | + nivel_digital, pack_preseleccionado |
| **Módulos 1-3** | Mentor: cuestiona decisiones débiles, usa contexto del pack | + pack_confirmado, ruta, canvas_id |
| **Módulo 4** | Productivo: genera contenido profesional, SEO, calendario | + pack_confirmado, sector, tipo_cliente |
| **Módulo 5** | Operativo: facturas, emails, propuestas reales | + negocio_piloto, precios_pack |
| **Acompañamiento** | Autónomo: copiloto de negocio continuo | + ingresos, clientes, meses_ss |

**Implementación:** `AndaluciaEiCopilotContextProvider` se extiende para llamar a `CopilotPhaseConfigService::getSystemPromptForPhase($fase)`. El cambio de prompt es automático según `estado_programa` del participante.

### 3.4 Principio: CRM de prospección con pipeline visual

La prospección de 50-80 negocios locales requiere un **pipeline visual tipo Kanban** con 6 fases:

```
Identificado → Contactado → Interesado → Propuesta → Acuerdo → Conversión
```

**Entity `NegocioProspectadoEi`** (ContentEntity) con campo `estado_embudo` que permite drag-and-drop en UI Kanban.

**Matching participante-negocio:** Basado en pack compatible, proximidad geográfica y nivel digital del participante.

### 3.5 Principio: Asistencia dual presencial/online con compliance

La 2ª Edición tiene restricción normativa: máximo 20% online (10h de 50h formación). El sistema debe distinguir tipos de asistencia y calcular automáticamente.

**Entity `AsistenciaDetalladaEi`** (ContentEntity) por sesión + participante:
- `modalidad`: presencial / online_sincronica
- `horas`: decimal
- `evidencia`: firma_hoja / conexion_videoconferencia
- Cálculo automático: `horas_presencial` vs `horas_online` → alerta si online > 10h

### 3.6 Diagrama de flujo completo del participante

```
INSCRIPCIÓN
├── Formulario público → solicitud_ei
├── Triage coordinador → aprobación
└── Creación programa_participante_ei (estado: inscrito)
    │
    ▼
ORIENTACIÓN INICIAL (10h presencial)
├── OI-1.1: Perfil + evaluación digital → Entregable #1
├── OI-1.2: Fichas 1-8 autoconocimiento → Entregable #2
├── OI-2.1: Pack exploración (5 packs) → pack_preseleccionado (2-3)
├── OI-2.2: Ruta + 3 objetivos SMART → Entregable #3
│   ├── ruta = A (autoempleo) → activar Emprendimiento
│   ├── ruta = B (empleo) → activar Empleabilidad
│   └── ruta = híbrida → activar ambos
└── estado → orientacion
    │
    ▼
FORMACIÓN (50h: 40h presencial + 10h online sincrónico)
├── M0: Fundamentos IA (8h) → Entregables #4-6
│   └── Copiloto: modo DIDÁCTICO
├── M1: Propuesta de Valor (8h) → Entregables #7-8
│   ├── pack_confirmado ← elección definitiva
│   └── Lean Canvas v2 (jaraba_business_tools)
├── M2: Finanzas (8h) → Entregables #9-13
│   └── Previsión financiera (jaraba_business_tools)
├── M3: Trámites (7h) → Entregables #14-17
│   └── JarabaLex bridge (ayudas, BOE)
├── M4: Marketing Digital (10h) → Entregables #18-23
│   ├── Content Hub bridge (calendario editorial)
│   └── Copiloto: modo PRODUCTIVO
├── M5: Integración (9h) → Entregables #24-29
│   ├── Pack publicado en catálogo digital
│   ├── Cliente piloto asignado (matching)
│   ├── Proyecto piloto documentado
│   └── Copiloto: modo OPERATIVO
├── Evaluación: rúbrica competencia IA (4 niveles)
└── estado → formacion
    │
    ▼
ACOMPAÑAMIENTO (40h: 24h presencial + 16h online)
├── Fase 1 (semanas 1-4): Lanzamiento
│   ├── Alta autónomo (si ruta A)
│   ├── Primera facturación
│   └── Copiloto: modo AUTÓNOMO
├── Fase 2 (semanas 5-12): Ejecución
│   ├── Seguimiento clientes
│   └── Prospección activa
├── Fase 3 (semanas 13-18): Consolidación
│   ├── 4 meses SS → inserción verificada
│   └── estado → insertado
└── Alumni (post-programa)
```

---

## 4. Especificaciones Técnicas Detalladas

### 4.1 SPEC-2E-001: Campos nuevos en ProgramaParticipanteEi

**Objetivo:** Añadir los campos que la 2ª Edición exige y que no existen en la entity actual.

**Campos que YA EXISTEN y mapean correctamente:**
- `carril` → se reutiliza como `ruta` (renombrar label via Field UI, no cambiar machine name)
- `canvas_id` → ya linkea con Lean Canvas de Emprendimiento
- `candidate_profile_id` → ya linkea con Empleabilidad
- `asistencia_porcentaje` → ya existe
- `es_persona_atendida`, `es_persona_insertada` → ya existen

**Campos NUEVOS a añadir (hook_update_N):**

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `ruta_programa` | list_string | Valores: `autoempleo` / `empleo` / `hibrida`. Define qué verticales se activan |
| `nivel_digital` | list_string | Valores: `autonomo` / `apoyo` / `nivelacion`. Evaluado en OI-1.1 |
| `pack_preseleccionado` | string(255) | JSON array de 1-3 pack IDs preseleccionados en OI |
| `pack_confirmado` | list_string | Valores: `contenido_digital` / `asistente_virtual` / `presencia_online` / `tienda_digital` / `community_manager`. Confirmado en M1 |
| `objetivos_smart` | text_long | JSON array de 3 objetivos SMART {objetivo, indicador, plazo} |
| `perfil_riasec` | string(255) | JSON resultado del test RIASEC (6 dimensiones) |
| `compromiso_firmado` | boolean | Firma del compromiso de participación |
| `compromiso_fecha` | datetime | Fecha de firma |
| `estado_programa_2e` | list_string | Valores: `inscrito` / `orientacion` / `formacion` / `acompanamiento` / `insertado` / `baja`. Más granular que `fase_actual` |
| `meses_ss_acumulados` | integer | Meses de alta SS acumulados (objetivo ≥4). Calculado mensualmente |
| `negocio_piloto_id` | integer | FK a NegocioProspectadoEi (cross-módulo: integer, no entity_reference) |
| `pack_servicio_id` | integer | FK al PackServicioEi publicado por el participante |

**Nota ENTITY-FK-001:** `negocio_piloto_id` y `pack_servicio_id` son integer (cross-entity dentro del mismo módulo) porque la entity origen está en el mismo módulo. Si estuviera en otro módulo sería integer obligatorio.

**hook_update_N:** `jaraba_andalucia_ei_update_10032()` con `updateFieldableEntityType()` + `getFieldStorageDefinitions()` (UPDATE-HOOK-FIELDABLE-001).

### 4.2 SPEC-2E-002: Entity PackServicioEi (5 packs × 3 tiers)

**Objetivo:** Representar los 5 packs de servicios profesionales con sus modalidades, precios y contenido personalizable por cada participante.

**Entity type:** `pack_servicio_ei` (ContentEntity)

**Campos:**

| Campo | Tipo | Required | Descripción |
|-------|------|----------|-------------|
| `id` | integer (auto) | — | PK |
| `uuid` | uuid | — | |
| `participante_id` | entity_reference(programa_participante_ei) | Sí | Participante propietario |
| `pack_tipo` | list_string | Sí | `contenido_digital` / `asistente_virtual` / `presencia_online` / `tienda_digital` / `community_manager` |
| `modalidad` | list_string | Sí | `basico` / `estandar` / `premium` |
| `titulo_personalizado` | string(255) | Sí | Nombre personalizado del servicio |
| `descripcion` | text_long | No | Descripción personalizada (generada con copiloto IA) |
| `precio_mensual` | decimal(8,2) | Sí | Precio €/mes (±15% del sugerido) |
| `precio_setup` | decimal(8,2) | No | Coste de alta inicial (Pack 3, 4) |
| `entregables_mensuales` | text_long | No | JSON array de entregables que ofrece al cliente |
| `sector_cliente` | list_string | No | Sector del cliente tipo |
| `publicado` | boolean | No | Visible en catálogo digital público |
| `stripe_product_id` | string(64) | No | ID de producto Stripe para cobro recurrente |
| `stripe_price_id` | string(64) | No | ID de precio Stripe |
| `url_catalogo` | string(255) | No | URL pública del catálogo (slug generado) |
| `tenant_id` | entity_reference(group) | Sí | TENANT-001 |
| `uid` | entity_reference(user) | Sí | Owner (EntityOwnerTrait) |
| `created` | created | — | |
| `changed` | changed | — | |

**Precios sugeridos por pack/modalidad (NO-HARDCODE-PRICE-001):**

Los precios base se almacenan en config (`jaraba_andalucia_ei.pack_precios`) y se inyectan via `MetaSitePricingService` pattern. El participante puede ajustar ±15%.

| Pack | Básico | Estándar | Premium |
|------|--------|----------|---------|
| Contenido Digital | 150€ | 250€ | 400€ |
| Asistente Virtual | 150€ | 250€ | 350€ |
| Presencia Online | 150€ (+ 300€ setup) | — | — |
| Tienda Digital | 300€ (+ 500€ setup) | — | — |
| Community Manager | 150€ | 200€ | 350€ |

**Admin:** `/admin/content/pack-servicios-ei` (collection) + `/admin/structure/pack-servicio-ei` (Field UI)

**Frontend:** `/andalucia-ei/mi-catalogo` (listado del participante) + `/catalogo/{participante_slug}/{pack_slug}` (página pública)

**Patrón Stripe:** Reutiliza `StripeConnectService` existente en el SaaS. Destination charges con plataforma como intermediaria.

### 4.3 SPEC-2E-003: Entity EntregableFormativoEi (29 entregables)

**Objetivo:** Representar cada uno de los 29 entregables formativos del programa, vinculados a sesiones específicas.

**Entity type:** `entregable_formativo_ei` (ContentEntity)

**Campos:**

| Campo | Tipo | Required | Descripción |
|-------|------|----------|-------------|
| `id` | integer (auto) | — | PK |
| `uuid` | uuid | — | |
| `participante_id` | entity_reference(programa_participante_ei) | Sí | Participante |
| `numero` | integer | Sí | 1-29. Número ordinal del entregable |
| `titulo` | string(255) | Sí | Título del entregable (predefinido) |
| `sesion_origen` | string(20) | Sí | ID sesión: `OI-1.1`, `M0-1`, `M1-2`, etc. |
| `modulo` | list_string | Sí | `orientacion` / `modulo_0` / `modulo_1` / `modulo_2` / `modulo_3` / `modulo_4` / `modulo_5` |
| `estado` | list_string | Sí | `pendiente` / `en_progreso` / `completado` / `validado` |
| `generado_con_ia` | boolean | No | TRUE si se usó copiloto IA para generar |
| `archivo_url` | string(2048) | No | URL del fichero adjunto (PDF, imagen, link) |
| `notas_participante` | text_long | No | Comentarios del participante |
| `validado_por` | entity_reference(user) | No | Formador que validó |
| `validado_fecha` | datetime | No | Fecha de validación |
| `notas_validacion` | text_long | No | Feedback del formador |
| `tenant_id` | entity_reference(group) | Sí | TENANT-001 |
| `uid` | entity_reference(user) | Sí | Owner |
| `created` | created | — | |
| `changed` | changed | — | |

**Semilla de datos:** Al crear un `ProgramaParticipanteEi`, se crean automáticamente los 29 `EntregableFormativoEi` con estado `pendiente`, pre-rellenando `numero`, `titulo`, `sesion_origen`, `modulo` desde un array constante.

**Los 29 entregables (constante en servicio):**

```php
public const ENTREGABLES = [
  1  => ['titulo' => 'Perfil profesional + evaluación digital', 'sesion' => 'OI-1.1', 'modulo' => 'orientacion'],
  2  => ['titulo' => 'Fichas autoconocimiento 1-8', 'sesion' => 'OI-1.2', 'modulo' => 'orientacion'],
  3  => ['titulo' => 'Ruta personalizada + 3 objetivos SMART', 'sesion' => 'OI-2.2', 'modulo' => 'orientacion'],
  4  => ['titulo' => '10 interacciones IA documentadas', 'sesion' => 'M0-1', 'modulo' => 'modulo_0'],
  5  => ['titulo' => 'Dashboard personalizado + web básica', 'sesion' => 'M0-2', 'modulo' => 'modulo_0'],
  6  => ['titulo' => '3 tareas productivas del pack', 'sesion' => 'M0-3', 'modulo' => 'modulo_0'],
  7  => ['titulo' => 'Propuesta de valor (3 versiones)', 'sesion' => 'M1-1', 'modulo' => 'modulo_1'],
  8  => ['titulo' => 'Lean Canvas v2 validado', 'sesion' => 'M1-3', 'modulo' => 'modulo_1'],
  9  => ['titulo' => 'Portfolio servicios con fichas y precios', 'sesion' => 'M2-1', 'modulo' => 'modulo_2'],
  10 => ['titulo' => 'Punto de equilibrio calculado', 'sesion' => 'M2-2', 'modulo' => 'modulo_2'],
  11 => ['titulo' => 'Previsión financiera 12 meses', 'sesion' => 'M2-2', 'modulo' => 'modulo_2'],
  12 => ['titulo' => 'Mapa de ayudas + línea temporal', 'sesion' => 'M2-3', 'modulo' => 'modulo_2'],
  13 => ['titulo' => 'Plan Financiero Básico consolidado', 'sesion' => 'M2-4', 'modulo' => 'modulo_2'],
  14 => ['titulo' => 'Secuencia de alta + IAE/CNAE', 'sesion' => 'M3-1', 'modulo' => 'modulo_3'],
  15 => ['titulo' => 'Factura modelo del pack', 'sesion' => 'M3-2', 'modulo' => 'modulo_3'],
  16 => ['titulo' => 'Calendario fiscal personalizado', 'sesion' => 'M3-2', 'modulo' => 'modulo_3'],
  17 => ['titulo' => 'Solicitudes L1 y L2 simuladas', 'sesion' => 'M3-3', 'modulo' => 'modulo_3'],
  18 => ['titulo' => 'Web profesional publicada', 'sesion' => 'M4-1', 'modulo' => 'modulo_4'],
  19 => ['titulo' => 'Perfil red social configurado', 'sesion' => 'M4-1', 'modulo' => 'modulo_4'],
  20 => ['titulo' => '5 piezas de contenido publicadas', 'sesion' => 'M4-2', 'modulo' => 'modulo_4'],
  21 => ['titulo' => 'Calendario editorial 4 semanas', 'sesion' => 'M4-4', 'modulo' => 'modulo_4'],
  22 => ['titulo' => 'Embudo de captación diseñado', 'sesion' => 'M4-3', 'modulo' => 'modulo_4'],
  23 => ['titulo' => 'CRM con 5+ contactos reales', 'sesion' => 'M4-3', 'modulo' => 'modulo_4'],
  24 => ['titulo' => 'Packs publicados en catálogo digital', 'sesion' => 'M5-1', 'modulo' => 'modulo_5'],
  25 => ['titulo' => 'Programa semanal de trabajo', 'sesion' => 'M5-1', 'modulo' => 'modulo_5'],
  26 => ['titulo' => 'Proyecto piloto documentado', 'sesion' => 'M5-2', 'modulo' => 'modulo_5'],
  27 => ['titulo' => 'Pitch de venta ensayado (3 versiones)', 'sesion' => 'M5-3', 'modulo' => 'modulo_5'],
  28 => ['titulo' => 'CV actualizado o CMI básico', 'sesion' => 'M5-3', 'modulo' => 'modulo_5'],
  29 => ['titulo' => 'Plan de 30 días post-formación', 'sesion' => 'M5-3', 'modulo' => 'modulo_5'],
];
```

### 4.4 SPEC-2E-004: Entity NegocioProspectadoEi (CRM clientes piloto)

**Objetivo:** Representar los negocios locales prospectados como clientes piloto para los participantes del programa.

**Entity type:** `negocio_prospectado_ei` (ContentEntity)

**Campos clave:**

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `nombre_negocio` | string(255) | Nombre comercial |
| `sector` | list_string | hosteleria/comercio/profesional/agro/salud/educacion/turismo/servicios |
| `direccion` | string(255) | Dirección física |
| `provincia` | list_string | malaga/sevilla |
| `persona_contacto` | string(255) | Nombre del contacto |
| `telefono` | string(20) | Teléfono |
| `email` | string(255) | Email |
| `url_web` | string(2048) | Web actual (puede estar vacía — señal de necesidad) |
| `url_google_maps` | string(2048) | Enlace a Google Maps |
| `valoracion_google` | decimal(2,1) | Puntuación Google (0-5) |
| `num_resenas` | integer | Número de reseñas Google |
| `clasificacion_urgencia` | list_string | `rojo` (urgente) / `amarillo` (moderado) / `verde` (bajo) |
| `estado_embudo` | list_string | `identificado` / `contactado` / `interesado` / `propuesta` / `acuerdo` / `conversion` |
| `pack_compatible` | string(255) | JSON array de packs compatibles |
| `participante_asignado` | entity_reference(programa_participante_ei) | Matching |
| `fecha_primer_contacto` | datetime | |
| `fecha_acuerdo_prueba` | datetime | |
| `satisfaccion_prueba` | list_string | `muy_satisfecho` / `satisfecho` / `neutro` / `insatisfecho` |
| `convertido_a_pago` | boolean | |
| `notas` | text_long | Historial de interacciones |
| `prospectado_por` | entity_reference(user) | Orientador/coordinador que prospectó |
| `tenant_id` | entity_reference(group) | TENANT-001 |

**UI Kanban:** Template `_prospeccion-pipeline.html.twig` con 6 columnas drag-and-drop via JS vanilla + Drupal.behaviors.

### 4.5 SPEC-2E-005: Entity EvaluacionCompetenciaIaEi (rúbrica 4 niveles)

**Entity type:** `evaluacion_competencia_ia_ei` (ContentEntity)

**Campos:**

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `participante_id` | entity_reference | FK |
| `tipo` | list_string | `inicial` / `intermedia` / `final` |
| `nivel_global` | list_string | `novel` / `aprendiz` / `competente` / `autonomo` |
| `indicadores` | text_long | JSON con 6 dimensiones evaluadas |
| `evaluador` | list_string | `formador` / `autoevaluacion` |
| `notas` | text_long | Observaciones |

**Rúbrica (4 niveles × 6 dimensiones):**
1. Formulación de instrucciones: ¿Sabe dar instrucciones claras al agente?
2. Evaluación crítica: ¿Detecta errores/alucinaciones del agente?
3. Iteración: ¿Refina instrucciones basándose en resultados?
4. Integración en flujo: ¿Usa IA como parte natural de su trabajo?
5. Productividad: ¿Produce resultados profesionales en tiempo razonable?
6. Autonomía: ¿Resuelve problemas nuevos sin guía del formador?

### 4.6 SPEC-2E-006: Entity AsistenciaDetalladaEi (presencial/online)

**Entity type:** `asistencia_detallada_ei` (ContentEntity)

Complementa a `InscripcionSesionEi` con tracking granular de modalidad.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `participante_id` | entity_reference | FK |
| `sesion_id` | string(20) | ID sesión: OI-1.1, M0-1, etc. |
| `fecha` | datetime | Fecha y hora |
| `modalidad` | list_string | `presencial` / `online_sincronica` |
| `horas` | decimal(4,2) | Horas de la sesión |
| `asistio` | boolean | |
| `evidencia` | list_string | `firma_hoja` / `conexion_videoconferencia` / `ambas` |
| `registrado_por` | entity_reference(user) | Formador que registró |

**AsistenciaComplianceService:** Calcula automáticamente:
- Total horas presencial (debe ser ≥40h formación)
- Total horas online sincrónico (debe ser ≤10h formación)
- % asistencia (alerta si <80%, bloqueo si <75%)

### 4.7 SPEC-2E-007: CopilotPhaseConfigService (6 system prompts)

**Fichero:** `jaraba_andalucia_ei/src/Service/CopilotPhaseConfigService.php`

**Responsabilidad:** Genera el system prompt apropiado según la fase del participante.

**Integración:** `AndaluciaEiCopilotContextProvider::getContext()` llama a `CopilotPhaseConfigService::getSystemPromptForPhase($estadoPrograma)` y lo inyecta en el copiloto.

**6 prompts almacenados en config YAML** (`jaraba_andalucia_ei.copilot_phase_prompts`), editables desde admin sin tocar código:

```yaml
orientacion:
  system_prompt: |
    Eres un orientador laboral del Programa Andalucía +ei...
    Tu objetivo es ayudar al participante a DESCUBRIR...
  behavior: exploratory
  max_tokens: 1000

modulo_0:
  system_prompt: |
    Eres un mentor de competencias digitales...
    Cuando el participante te pide algo, EXPLICA tu razonamiento...
  behavior: didactic
  max_tokens: 1500
# ... etc para los 6
```

### 4.8 SPEC-2E-008: PortfolioEntregablesService + UI

**Service:** `PortfolioEntregablesService` — CRUD + progreso + seed

**UI (portal participante):** Parcial `_portfolio-entregables.html.twig` con:
- Vista de módulos colapsables (acordeón)
- Cada entregable: número, título, estado (badge color), archivo adjunto, botón "Marcar completado"
- Barra de progreso global: X/29 completados, Y validados
- Iconos duotone por módulo (ICON-CONVENTION-001)

**UI (dashboard formador):** Parcial `_formador-entregables-pendientes.html.twig` con:
- Lista de entregables completados pendientes de validación
- Botón "Validar" que abre slide-panel con feedback

### 4.9 SPEC-2E-009: CatalogoPacksService + UI pública

**Service:** `CatalogoPacksService` — publicación, despublicación, URL pública, conexión Stripe

**Ruta pública:** `/catalogo/{slug}` — página del catálogo del participante con:
- Header con nombre del profesional
- 1-2 packs publicados con ficha, precio, entregables
- Botón "Contratar" → formulario de contacto o pago Stripe
- Footer con logos FSE+ (SPEC-2E-013)

**Template:** `catalogo-publico.html.twig` — página frontend limpia, mobile-first

### 4.10 SPEC-2E-010: ProspeccionPipelineService + UI Kanban

**Service:** `ProspeccionPipelineService` — agrupación por estado_embudo, estadísticas, matching

**UI:** Parcial `_prospeccion-pipeline.html.twig` — 6 columnas, tarjetas de negocio, drag-and-drop con JS vanilla

**Integración:** Sección en dashboard coordinador + sección en dashboard orientador

### 4.11 SPEC-2E-011: AsistenciaComplianceService + alertas

**Service:** `AsistenciaComplianceService` — cálculos de compliance normativo

**Alertas automáticas:**
- Participante falta 2 sesiones consecutivas → notificación formador
- % asistencia < 80% → warning al participante y formador
- Horas online > 10h → bloqueo de marcaje online (compliance 20% máximo)

**Integración:** `AlertasNormativasService` existente se extiende con estos checks adicionales.

### 4.12 SPEC-2E-012: 3 Bridges cross-vertical nuevos

| Bridge | Conecta con | Casos de uso |
|--------|------------|-------------|
| `EiContentHubBridgeService` | `jaraba_content_hub` | M4: calendario editorial, publicación contenido. Pack 1 y 5 |
| `EiComercioConectaBridgeService` | `jaraba_comercioconecta` | Pack 4: catálogo productos, tienda digital, pagos online |
| `EiJarabaLexBridgeService` | `jaraba_jarabalex` | M3: consultas legales, alertas BOE/BOJA, plantillas laborales |

**Patrón:** `@?` (OPTIONAL-CROSSMODULE-001). Si el módulo destino no está habilitado, el bridge retorna datos vacíos sin error.

### 4.13 SPEC-2E-013: Parcial logos cofinanciación FSE+

**Fichero:** `templates/partials/_logos-cofinanciacion.html.twig`

**Contenido:** Logos de cofinanciación EU + Ministerio + Junta + SAE + FSE+ con texto legal:
```twig
<div class="aei-cofinanciacion">
  <p class="aei-cofinanciacion__text">
    {% trans %}Programa cofinanciado por el Fondo Social Europeo Plus (FSE+){% endtrans %}
  </p>
  <div class="aei-cofinanciacion__logos">
    {# Logos institucionales — NUNCA emojis, siempre SVG/PNG #}
  </div>
</div>
```

**Inclusión:** En footer de TODAS las páginas `/andalucia-ei/*` y en la página pública del catálogo.

### 4.14 SPEC-2E-014: 8 fichas autoconocimiento OI

**8 formularios paso a paso** que el participante completa durante la Orientación Inicial:

1. Mi historia personal (narrativa libre)
2. Mis experiencias laborales (estructurado)
3. Mis competencias y habilidades (checklist + autoevaluación)
4. Mi situación actual (empleo, formación, barreras)
5. Mis intereses profesionales (RIASEC simplificado)
6. Mi relación con la tecnología (nivel digital: A/B/C)
7. Mis recursos y red de contactos (mapa de recursos)
8. Mi hipótesis de trabajo (pack preseleccionado + sector + cliente tipo)

**Implementación:** Un wizard multi-step con 8 pasos, usando el patrón de formularios progresivos ya existente en el SaaS. Los datos se almacenan en campos JSON del `ProgramaParticipanteEi` + `perfil_riasec`.

### 4.15 SPEC-2E-015: Prompts prediseñados por sesión

**~87 prompts sugeridos** (3 por cada una de las 29 sesiones) almacenados en config YAML:

```yaml
# jaraba_andalucia_ei.copilot_session_prompts
OI-1.1:
  - "Ayúdame a redactar mi perfil profesional destacando mis experiencias más relevantes"
  - "Evalúa mi nivel de competencias digitales según estas respuestas: {respuestas_ficha_6}"
  - "Sugiere 3 objetivos profesionales realistas basados en mi perfil"
M1-1:
  - "Genera 3 versiones de mi propuesta de valor para Pack {pack_confirmado}"
  - "Compara mi propuesta con las de otros profesionales del sector {sector}"
  - "Identifica los puntos débiles de esta propuesta de valor"
# ... etc
```

**UI:** En el chat del copiloto, aparecen como chips/botones sugeridos. Al hacer clic, se insertan en el input del chat con las variables `{pack_confirmado}`, `{sector}`, etc. ya resueltas.

---

## 5. Pipeline E2E por Componente

### PackServicioEi

| Capa | Verificación |
|------|-------------|
| **L1** | `CatalogoPacksService` inyectado en `CatalogoPacksController` |
| **L2** | Controller pasa pack data + stripe info al render array |
| **L3** | `hook_theme()` declara `catalogo_publico` con variables |
| **L4** | `catalogo-publico.html.twig` con {% trans %}, jaraba_icon(), `only` |

### EntregableFormativoEi

| Capa | Verificación |
|------|-------------|
| **L1** | `PortfolioEntregablesService` inyectado en `ParticipantePortalController` |
| **L2** | Controller pasa `#entregables` via preprocess |
| **L3** | `hook_theme()` declara `portfolio_entregables` |
| **L4** | `_portfolio-entregables.html.twig` con progreso, badges, slide-panel validación |

### CopilotPhaseConfig

| Capa | Verificación |
|------|-------------|
| **L1** | `CopilotPhaseConfigService` inyectado en `AndaluciaEiCopilotContextProvider` |
| **L2** | Provider resuelve fase → prompt dinámico |
| **L3** | Prompt inyectado en copilot session via `ChatInput::setSystemPrompt()` |
| **L4** | Participante experimenta comportamiento diferenciado |

---

## 6. Tabla de Correspondencia con Directrices

| Directriz | Cumplimiento garantizado | Cómo |
|-----------|-------------------------|------|
| TENANT-001 | ✅ | Todas las entities nuevas tienen `tenant_id`. Todas las queries filtran |
| PREMIUM-FORMS-PATTERN-001 | ✅ | Todas las forms nuevas extienden PremiumEntityFormBase |
| ZERO-REGION-001 | ✅ | Catálogo público y portfolio usan clean_content |
| ZERO-REGION-003 | ✅ | Datos via drupalSettings en preprocess |
| SLIDE-PANEL-RENDER-001 | ✅ | Validación entregables, edición pack, marcaje asistencia |
| SLIDE-PANEL-RENDER-002 | ✅ | Rutas con _controller (no _form) |
| ICON-CONVENTION-001 | ✅ | Todos los iconos via jaraba_icon() duotone |
| CSS-VAR-ALL-COLORS-001 | ✅ | SCSS con var(--ej-*, fallback) |
| SCSS-001 | ✅ | @use '../variables' as * en cada parcial |
| SCSS-COMPILE-VERIFY-001 | ✅ | npm run build + verificación timestamp |
| TWIG-INCLUDE-ONLY-001 | ✅ | Todos los parciales con `only` |
| UPDATE-HOOK-REQUIRED-001 | ✅ | hook_update_N para 6 entities nuevas + campos |
| UPDATE-HOOK-FIELDABLE-001 | ✅ | getFieldStorageDefinitions() (no getBaseFieldDefinitions()) |
| UPDATE-HOOK-CATCH-001 | ✅ | \Throwable en todos los hooks |
| ENTITY-FK-001 | ✅ | tenant_id como entity_reference, cross-entity como integer |
| ENTITY-001 | ✅ | EntityOwnerInterface + EntityChangedInterface |
| AUDIT-CONS-001 | ✅ | AccessControlHandler en anotación |
| ACCESS-RETURN-TYPE-001 | ✅ | AccessResultInterface en checkAccess() |
| OPTIONAL-CROSSMODULE-001 | ✅ | @? para 3 bridges nuevos |
| NO-HARDCODE-PRICE-001 | ✅ | Precios packs desde config, no hardcoded |
| ROUTE-LANGPREFIX-001 | ✅ | URLs via Url::fromRoute() |
| CONTROLLER-READONLY-001 | ✅ | Sin readonly en props heredadas |
| FIELD-UI-SETTINGS-TAB-001 | ✅ | field_ui_base_route en entities con Field UI |
| MODEL-ROUTING-CONFIG-001 | ✅ | Copilot phase prompts via config YAML |
| MARKETING-TRUTH-001 | ✅ | Precios en catálogo desde entities, no hardcoded |
| IMPLEMENTATION-CHECKLIST-001 | ✅ | Pipeline E2E verificado por componente |
| RUNTIME-VERIFY-001 | ✅ | 15 checks post-implementación por sprint |

---

## 7. Tabla de Correspondencia con Especificaciones Técnicas

| SPEC ID | Título | Gaps cubiertos | Sprint | Entities/Services | Ficheros estimados |
|---------|--------|---------------|--------|-------------------|-------------------|
| SPEC-2E-001 | Campos ProgramaParticipanteEi | GAP-2E-003 | A | hook_update + 12 campos | 1 .install |
| SPEC-2E-002 | PackServicioEi | GAP-2E-001 | B | Entity + ACH + Form + ListBuilder + Service | 6 PHP |
| SPEC-2E-003 | EntregableFormativoEi | GAP-2E-002 | B | Entity + ACH + Form + ListBuilder + Service | 6 PHP |
| SPEC-2E-004 | NegocioProspectadoEi | GAP-2E-006 | C | Entity + ACH + Form + ListBuilder + Service | 6 PHP |
| SPEC-2E-005 | EvaluacionCompetenciaIaEi | GAP-2E-012 | B | Entity + ACH + Form | 4 PHP |
| SPEC-2E-006 | AsistenciaDetalladaEi | GAP-2E-004 | A | Entity + ACH + Service | 4 PHP |
| SPEC-2E-007 | CopilotPhaseConfigService | GAP-2E-005 | A | Service + Config YAML | 2 PHP + 1 YAML |
| SPEC-2E-008 | PortfolioEntregablesService | GAP-2E-002 | B | Service + 2 Twig + 1 SCSS | 4 ficheros |
| SPEC-2E-009 | CatalogoPacksService | GAP-2E-001 | B | Service + Controller + 2 Twig + 1 SCSS | 5 ficheros |
| SPEC-2E-010 | ProspeccionPipelineService | GAP-2E-006,007 | C | Service + 1 Twig + 1 JS + 1 SCSS | 4 ficheros |
| SPEC-2E-011 | AsistenciaComplianceService | GAP-2E-004 | A | Service | 1 PHP |
| SPEC-2E-012 | 3 Bridges cross-vertical | GAP-2E-009 | C | 3 Services | 3 PHP |
| SPEC-2E-013 | Logos cofinanciación | GAP-2E-011 | A | 1 Twig + 1 SCSS | 2 ficheros |
| SPEC-2E-014 | 8 fichas autoconocimiento | GAP-2E-008 | B | Controller + 1 Twig + 1 JS | 3 ficheros |
| SPEC-2E-015 | Prompts por sesión | GAP-2E-010 | C | Config YAML + Service update | 1 YAML + 1 PHP |

---

## 8. Plan de Fases (4 Sprints)

### 8.1 Sprint A — Pre-lanzamiento (P0 críticos)

**Objetivo:** Preparar el SaaS para recibir participantes de la 2ª Edición. Sin esto, no se puede arrancar.

**SPEC implementadas:** 2E-001, 2E-006, 2E-007, 2E-011, 2E-013

**Ficheros a crear:**
- `src/Service/CopilotPhaseConfigService.php`
- `src/Service/AsistenciaComplianceService.php`
- `src/Entity/AsistenciaDetalladaEi.php`
- `src/Access/AsistenciaDetalladaEiAccessControlHandler.php`
- `config/install/jaraba_andalucia_ei.copilot_phase_prompts.yml`
- `templates/partials/_logos-cofinanciacion.html.twig`
- `scss/components/_cofinanciacion.scss`

**Ficheros a modificar:**
- `jaraba_andalucia_ei.install` — hook_update_10032 (campos) + hook_update_10033 (AsistenciaDetalladaEi)
- `jaraba_andalucia_ei.services.yml` — 2 servicios nuevos
- `jaraba_andalucia_ei.routing.yml` — rutas entity AsistenciaDetallada
- `src/Service/AndaluciaEiCopilotContextProvider.php` — integrar CopilotPhaseConfig
- `src/Service/AlertasNormativasService.php` — extender con checks asistencia
- Templates de dashboard (coord, orient, formador) — incluir _logos-cofinanciacion

**Criterios de aceptación:**
- [ ] 12 campos nuevos en ProgramaParticipanteEi accesibles via Field UI
- [ ] AsistenciaDetalladaEi permite registrar presencial/online con evidencia
- [ ] AsistenciaComplianceService calcula horas y genera alertas
- [ ] Copiloto IA cambia de comportamiento según fase del participante (6 modos)
- [ ] Logos FSE+ visibles en todas las páginas /andalucia-ei/*

### 8.2 Sprint B — Lanzamiento formación (Packs + Portfolio)

**Objetivo:** Los participantes pueden trabajar con packs y entregables durante la formación.

**SPEC implementadas:** 2E-002, 2E-003, 2E-005, 2E-008, 2E-009, 2E-014

**Ficheros a crear (estimados): ~25**
- 4 entities nuevas (Pack, Entregable, Evaluación) + ACH + Forms + ListBuilders
- PortfolioEntregablesService + CatalogoPacksService
- Templates: portfolio, catálogo público, fichas OI
- SCSS: portfolio, catálogo, fichas
- JS: portfolio interacciones, fichas wizard

**Criterios de aceptación:**
- [ ] 5 packs de servicios con 2-3 modalidades cada uno
- [ ] Participante puede personalizar y publicar su pack con URL pública
- [ ] Stripe Connect activable para cobro recurrente
- [ ] 29 entregables se crean automáticamente al inscribir participante
- [ ] Portfolio visible en portal participante con progreso
- [ ] Formador puede validar entregables desde su dashboard (slide-panel)
- [ ] 8 fichas autoconocimiento como wizard multi-step
- [ ] Rúbrica de competencia IA con 4 niveles × 6 dimensiones

### 8.3 Sprint C — Post-formación (CRM + Bridges + Prompts)

**Objetivo:** Prospección de clientes piloto y matching con participantes.

**SPEC implementadas:** 2E-004, 2E-010, 2E-012, 2E-015

**Criterios de aceptación:**
- [ ] NegocioProspectadoEi con pipeline visual Kanban 6 fases
- [ ] Matching inteligente participante→negocio basado en pack compatible
- [ ] 3 bridges cross-vertical (Content Hub, ComercioConecta, JarabaLex)
- [ ] ~87 prompts prediseñados por sesión como chips sugeridos

### 8.4 Sprint D — Mejoras continuas

**Gaps restantes:** GAP-2E-013 a 018

- Portfolio público con URL compartible
- Mapa de ayudas personalizado (Tarifa Plana, Cuota Cero)
- Calculadora punto equilibrio contextualizada por pack
- Historial copilot revisable por formador
- Sesiones online con enlace videoconferencia
- Registro horas equipo (justificación económica)

---

## 9. Verificación RUNTIME-VERIFY-001 por Sprint

### Sprint A (6 checks)

| # | Check | Método |
|---|-------|--------|
| 1 | 12 campos nuevos visibles en entity form | Visitar `/admin/content/programa-participante-ei/*/edit` |
| 2 | AsistenciaDetalladaEi tabla creada | `lando drush entity:updates` |
| 3 | Copiloto cambia prompt por fase | Login como participante, verificar system prompt |
| 4 | Alertas asistencia <80% funcionan | Crear participante con baja asistencia |
| 5 | Logos FSE+ visibles | Navegar a `/andalucia-ei/formador` |
| 6 | SCSS compilado | Timestamp CSS > SCSS |

### Sprint B (8 checks)

| # | Check | Método |
|---|-------|--------|
| 1 | 5 packs plantilla disponibles | Crear pack como participante |
| 2 | Catálogo público accesible | Visitar `/catalogo/{slug}` sin auth |
| 3 | Stripe Connect para pack | Verificar stripe_product_id |
| 4 | 29 entregables creados al inscribir | Crear participante → contar entregables |
| 5 | Portfolio visible en portal | Login como participante |
| 6 | Validación por formador (slide-panel) | Login como formador → validar entregable |
| 7 | 8 fichas OI como wizard | Login como participante nuevo |
| 8 | Rúbrica IA funcional | Crear evaluación con 4 niveles |

---

## 10. Salvaguardas y Validadores Propuestos

### Nuevos validadores

| Validador | Checks | Tipo |
|-----------|--------|------|
| `validate-andalucia-ei-2e-packs.php` | 5 packs definidos, precios en config, Stripe mapping | `run_check` |
| `validate-andalucia-ei-2e-entregables.php` | 29 entregables seed correctos, array constante completo | `run_check` |
| `validate-andalucia-ei-2e-copilot-phases.php` | 6 prompts en config, CopilotPhaseConfigService registrado | `run_check` |
| `validate-andalucia-ei-2e-asistencia.php` | AsistenciaDetalladaEi instalada, compliance service operativo | `run_check` |
| `validate-andalucia-ei-2e-bridges.php` | 3 bridges nuevos registrados con @? | `run_check` |
| `validate-andalucia-ei-2e-campos.php` | 12 campos nuevos en ProgramaParticipanteEi | `run_check` |
| `validate-andalucia-ei-2e-logos-fse.php` | Parcial _logos-cofinanciacion incluido en templates AEI | `warn_check` |

### Pre-commit hooks adicionales

Si se modifica `CopilotPhaseConfigService.php` o `copilot_phase_prompts.yml`, ejecutar `validate-andalucia-ei-2e-copilot-phases.php`.

### Runtime hook_requirements

Añadir check: "Andalucía +ei: 2ª Edición" que verifica:
- 5 packs template definidos
- CopilotPhaseConfigService disponible
- AsistenciaDetalladaEi entity instalada

---

## 11. Riesgos y Mitigaciones

| # | Riesgo | Prob. | Impacto | Mitigación |
|---|--------|-------|---------|------------|
| 1 | **Stripe Connect no aprobado a tiempo** para packs | Media | Alto | Implementar catálogo sin cobro online primero; Stripe como fase B+ |
| 2 | **Copilot prompts demasiado genéricos** | Media | Alto | Testing con participantes reales; iteración rápida via config YAML (sin deploy) |
| 3 | **Performance pipeline Kanban** con 80+ negocios | Baja | Medio | Lazy-load por fase del embudo; Views integration para queries pesadas |
| 4 | **Campos ProgramaParticipanteEi** rompen forms existentes | Baja | Alto | Todos los campos nuevos son opcionales; forms existentes no se afectan |
| 5 | **Modalidad asistencia online** difícil de verificar automáticamente | Media | Medio | Log de conexión manual por formador; futuro: integración Jitsi/Zoom API |
| 6 | **29 entregables seed** ocupan N×29 filas por participante | Baja | Bajo | Max 45 participantes × 29 = 1.305 entregables totales. Escala trivial |
| 7 | **Cross-vertical modules** no habilitados en todos los tenants | Media | Medio | @? pattern asegura degradación graceful; bridge retorna datos vacíos |

---

## 12. Criterios de Aceptación 10/10 Clase Mundial

| # | Criterio | Sprint | Peso |
|---|---------|--------|------|
| 1 | **5 Packs servicios** con catálogo digital, publicación, URL pública y cobro Stripe | B | 1.5 |
| 2 | **29 entregables** con portfolio, progreso, validación formador, semilla automática | B | 1.5 |
| 3 | **Copiloto IA adaptativo** con 6 modos por fase + prompts sugeridos por sesión | A+C | 1.0 |
| 4 | **CRM prospección** con pipeline Kanban 6 fases + matching participante-negocio | C | 1.0 |
| 5 | **Asistencia dual** presencial/online con compliance 75% + alertas automáticas | A | 0.5 |
| 6 | **Campos participante** ruta, pack, nivel_digital, objetivos SMART | A | 0.5 |
| 7 | **8 fichas autoconocimiento** como wizard multi-step en OI | B | 0.5 |
| 8 | **Rúbrica competencia IA** con 4 niveles × 6 dimensiones | B | 0.5 |
| 9 | **3 bridges cross-vertical** (Content Hub, Comercio, JarabaLex) | C | 0.5 |
| 10 | **Logos cofinanciación FSE+** en todas las páginas del programa | A | 0.5 |
| 11 | **Portfolio público** compartible + mapa ayudas + calculadora contextualizada | D | 0.5 |
| 12 | **7 validadores** + runtime checks + tests ≥30 nuevos | Todos | 0.5 |
| | **TOTAL** | | **10.0** |

---

## 13. Glosario de Siglas

| Sigla | Significado |
|-------|------------|
| ACH | Access Control Handler — clase PHP que controla permisos de acceso a entities |
| BMC | Business Model Canvas — lienzo de modelo de negocio (9 bloques de Osterwalder) |
| BOJA | Boletín Oficial de la Junta de Andalucía |
| CNAE | Clasificación Nacional de Actividades Económicas |
| CRM | Customer Relationship Management — gestión de relaciones con clientes |
| CSS | Cascading Style Sheets — hojas de estilo en cascada |
| DI | Dependency Injection — inyección de dependencias en el contenedor de Drupal |
| FSE+ | Fondo Social Europeo Plus — instrumento UE que cofinancia el programa al 85% |
| IA | Inteligencia Artificial |
| IAE | Impuesto de Actividades Económicas — código fiscal de actividad |
| M0-M5 | Módulo 0 al 5 del curso de formación (50h) |
| OI | Orientación Inicial — primer componente del programa (10h presenciales) |
| PIIL | Programa Integral de Inserción Laboral — tipo de programa regulado por la Junta |
| RETA | Régimen Especial de Trabajadores Autónomos de la Seguridad Social |
| RIASEC | Holland's occupational themes — modelo de 6 dimensiones de intereses vocacionales |
| SAE | Servicio Andaluz de Empleo — organismo de la Junta que gestiona el empleo |
| SCSS | Sassy CSS — preprocesador CSS (Dart Sass moderno con @use) |
| SMART | Specific, Measurable, Achievable, Relevant, Time-bound — criterios para objetivos |
| SPEC | Specification — especificación técnica numerada de un componente |
| SS | Seguridad Social — sistema español de protección social |
| SSOT | Single Source of Truth — fuente única de verdad para un dato |
| STO | Servicio Técnico de Orientación — sistema informático de la Junta para orientación |
| UX | User Experience — experiencia de usuario |
| YAML | YAML Ain't Markup Language — formato de serialización usado en config Drupal |
