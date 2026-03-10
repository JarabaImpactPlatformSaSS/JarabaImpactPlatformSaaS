# Plan de Implementacion Integral: Andalucia +ei — Cumplimiento PIIL CV 2025, Integracion Cross-Vertical y Acceso Servicios SaaS

**Fecha de creacion:** 2026-03-10
**Ultima actualizacion:** 2026-03-10
**Autor:** IA Asistente (Claude Opus 4.6)
**Version:** 2.1.0
**Estado:** Planificado
**Categoria:** Implementacion Integral / Cumplimiento Normativo / Cross-Vertical / Acceso Servicios
**Modulos afectados:** `jaraba_andalucia_ei`, `jaraba_copilot_v2`, `jaraba_mentoring`, `jaraba_business_tools`, `jaraba_candidate`, `jaraba_matching`, `jaraba_funding`, `jaraba_skills`, `ecosistema_jaraba_core`, `ecosistema_jaraba_theme`
**Documento fuente:** `docs/analisis/2026-03-06_Auditoria_Andalucia_Ei_PIIL_CV_2025_Brechas_Normativas_v1.md`
**Especificacion referencia:** PIIL BBRR Consolidada (30/07/2025), Resolucion Concesion 202599904458144, Ficha Tecnica FT_679, Manual STO ICV25, Manual Operativo V2.1, Contenido Formativo Integral V2.1, Anexo Itinerarios Diferenciados
**Prioridad:** P0 (normativo) + P1 (operativo) + P2 (elevacion) + CROSS (integracion vertical) + ACCESS (acceso servicios)
**Directrices de aplicacion:** ZERO-REGION-001, PREMIUM-FORMS-PATTERN-001, CSS-VAR-ALL-COLORS-001, SCSS-COLORMIX-001, SCSS-001, SCSS-COMPILE-VERIFY-001, SCSS-COMPILETIME-001, SCSS-ENTRY-CONSOLIDATION-001, TENANT-001, TENANT-BRIDGE-001, TENANT-ISOLATION-ACCESS-001, UPDATE-HOOK-REQUIRED-001, UPDATE-HOOK-CATCH-001, UPDATE-HOOK-FIELDABLE-001, UPDATE-FIELD-DEF-001, TRANSLATABLE-FIELDS-INSTALL-001, OPTIONAL-CROSSMODULE-001, CONTAINER-DEPS-002, LOGGER-INJECT-001, PHANTOM-ARG-001, SLIDE-PANEL-RENDER-001, FORM-CACHE-001, ENTITY-PREPROCESS-001, ENTITY-FK-001, ENTITY-001, AUDIT-CONS-001, PRESAVE-RESILIENCE-001, ICON-CONVENTION-001, ICON-DUOTONE-001, ICON-COLOR-001, ICON-EMOJI-001, ROUTE-LANGPREFIX-001, INNERHTML-XSS-001, CSRF-JS-CACHE-001, CSRF-API-001, API-WHITELIST-001, SECRET-MGMT-001, ACCESS-STRICT-001, CONTROLLER-READONLY-001, DRUPAL11-001, LABEL-NULLSAFE-001, FIELD-UI-SETTINGS-TAB-001, KERNEL-TEST-DEPS-001, KERNEL-SYNTH-001, MOCK-METHOD-001, MOCK-DYNPROP-001, TEST-CACHE-001, DOC-GUARD-001, COMMIT-SCOPE-001, TWIG-URL-RENDER-ARRAY-001, TWIG-INCLUDE-ONLY-001, TWIG-ENTITY-METHOD-001, SERVICE-CALL-CONTRACT-001, COOKIE-CONSENT-LOCAL-FIRST, HREFLANG-CONDITIONAL-001
**Esfuerzo estimado:** 130-170 horas (16 fases)
**Relacion con plan anterior:** Extiende y sustituye `2026-03-06_Plan_Implementacion_Andalucia_Ei_Cumplimiento_PIIL_10_10_Clase_Mundial_v1.md` anadiendo integracion cross-vertical, acceso a servicios SaaS, documentacion ampliada y compliance de directrices exhaustivo

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
   - 1.1 [Que se implementa](#11-que-se-implementa)
   - 1.2 [Por que se implementa](#12-por-que-se-implementa)
   - 1.3 [Alcance y exclusiones](#13-alcance-y-exclusiones)
   - 1.4 [Filosofia de implementacion](#14-filosofia-de-implementacion)
   - 1.5 [Estimacion de esfuerzo por sprint](#15-estimacion-de-esfuerzo-por-sprint)
2. [Diagnostico del Estado Actual](#2-diagnostico-del-estado-actual)
   - 2.1 [Lo que ya existe y funciona](#21-lo-que-ya-existe-y-funciona)
   - 2.2 [Brechas identificadas (17 GAPs)](#22-brechas-identificadas)
   - 2.3 [Dependencias entre brechas](#23-dependencias-entre-brechas)
   - 2.4 [Servicios cross-vertical disponibles](#24-servicios-cross-vertical-disponibles)
3. [Arquitectura Objetivo](#3-arquitectura-objetivo)
   - 3.1 [Modelo de datos ampliado](#31-modelo-de-datos-ampliado)
   - 3.2 [Nuevas entidades con esquema completo](#32-nuevas-entidades-con-esquema-completo)
   - 3.3 [Campos modificados en entidades existentes](#33-campos-modificados-en-entidades-existentes)
   - 3.4 [Arquitectura de servicios (nuevos + consumidos)](#34-arquitectura-de-servicios)
   - 3.5 [Mapa de rutas frontend y admin](#35-mapa-de-rutas)
   - 3.6 [Diagrama de flujo del itinerario completo](#36-diagrama-de-flujo)
4. [Integracion Cross-Vertical](#4-integracion-cross-vertical)
   - 4.1 [Principio: Orquestar, no reinventar](#41-principio)
   - 4.2 [jaraba_candidate: Perfil profesional del participante](#42-jaraba-candidate)
   - 4.3 [jaraba_business_tools: BMC y validacion de negocio](#43-jaraba-business-tools)
   - 4.4 [jaraba_mentoring: Sesiones de mentoria humana](#44-jaraba-mentoring)
   - 4.5 [jaraba_matching: Matching empresa-participante](#45-jaraba-matching)
   - 4.6 [jaraba_funding: Tracking de oportunidades de financiacion](#46-jaraba-funding)
   - 4.7 [jaraba_skills: Evaluacion de competencias](#47-jaraba-skills)
   - 4.8 [Tabla de servicios consumidos y metodos](#48-tabla-servicios-consumidos)
5. [Acceso a Servicios SaaS (Programa 100% Fondos Publicos)](#5-acceso-servicios-saas)
   - 5.1 [Configuracion de roles y permisos](#51-roles-y-permisos)
   - 5.2 [Alta de participantes: flujo completo](#52-alta-participantes)
   - 5.3 [Alta de personal tecnico: coordinador, orientador, formador](#53-alta-personal-tecnico)
   - 5.4 [Mapa de acceso por rol a cada servicio SaaS](#54-mapa-acceso-por-rol)
   - 5.5 [Configuracion de tenant para el programa](#55-configuracion-tenant)
6. [Documentacion Justificativa Ampliada (mas alla de STO)](#6-documentacion-justificativa-ampliada)
   - 6.1 [Documentos obligatorios por momento del itinerario](#61-documentos-por-momento)
   - 6.2 [Documentos de justificacion economica](#62-justificacion-economica)
   - 6.3 [Documentos de evidencia para FSE+](#63-evidencia-fse)
   - 6.4 [Mapeo: documento -> entidad -> servicio generador -> template](#64-mapeo-documentos)
7. [Fases de Implementacion](#7-fases-de-implementacion)
   - 7.1 [Sprint 1 — Cumplimiento Normativo P0 (Fases 1-7)](#71-sprint-1)
     - 7.1.1 [Fase 1: Fases PIIL completas y colectivos corregidos](#711-fase-1)
     - 7.1.2 [Fase 2: Entidad ActuacionSto — tracking granular](#712-fase-2)
     - 7.1.3 [Fase 3: Indicadores FSE+](#713-fase-3)
     - 7.1.4 [Fase 4: Insercion laboral detallada](#714-fase-4)
     - 7.1.5 [Fase 5: DACI digital y flujo de acogida](#715-fase-5)
     - 7.1.6 [Fase 6: Recibo de servicio universal](#716-fase-6)
     - 7.1.7 [Fase 7: Formacion con VoBo SAE](#717-fase-7)
   - 7.2 [Sprint 2 — Funcionalidad Operativa P1 (Fases 8-11)](#72-sprint-2)
     - 7.2.1 [Fase 8: Calendario 12 semanas + DIME automatico](#721-fase-8)
     - 7.2.2 [Fase 9: Prospeccion empresarial y alertas normativas](#722-fase-9)
     - 7.2.3 [Fase 10: Integracion BMC, Copilot por fase, gamificacion Pi](#723-fase-10)
     - 7.2.4 [Fase 11: Integracion cross-vertical (Empleabilidad + Emprendimiento)](#724-fase-11)
   - 7.3 [Sprint 3 — Elevacion y Acceso (Fases 12-16)](#73-sprint-3)
     - 7.3.1 [Fase 12: Configuracion de acceso a servicios SaaS](#731-fase-12)
     - 7.3.2 [Fase 13: Documentacion justificativa ampliada](#732-fase-13)
     - 7.3.3 [Fase 14: Elevacion frontend — Templates, SCSS, accesibilidad](#733-fase-14)
     - 7.3.4 [Fase 15: Testing integral y verificacion](#734-fase-15)
     - 7.3.5 [Fase 16: Alumni, alta autonomo, mejoras finales](#735-fase-16)
8. [Tabla de Correspondencia: Especificaciones Tecnicas](#8-tabla-correspondencia)
   - 8.1 [Brechas vs entidades vs servicios vs templates vs tests](#81-brechas-entidades)
   - 8.2 [Requisitos normativos vs implementacion SaaS](#82-requisitos-normativos)
9. [Tabla de Cumplimiento de Directrices del Proyecto](#9-cumplimiento-directrices)
10. [Arquitectura Frontend y Templates](#10-arquitectura-frontend)
    - 10.1 [Templates Twig nuevos y modificados](#101-templates-nuevos)
    - 10.2 [Parciales reutilizables](#102-parciales)
    - 10.3 [SCSS y pipeline de compilacion](#103-scss)
    - 10.4 [Variables CSS inyectables desde Drupal UI](#104-variables-css)
    - 10.5 [Iconografia completa](#105-iconografia)
    - 10.6 [Accesibilidad WCAG 2.1 AA](#106-accesibilidad)
    - 10.7 [Slide-panel para CRUD de actuaciones](#107-slide-panel)
11. [Verificacion y Testing](#11-verificacion-testing)
    - 11.1 [Tests automatizados por fase](#111-tests)
    - 11.2 [Checklist RUNTIME-VERIFY-001](#112-runtime-verify)
    - 11.3 [Checklist IMPLEMENTATION-CHECKLIST-001](#113-implementation-checklist)
    - 11.4 [Validacion con scripts del proyecto](#114-scripts-validacion)
12. [Inventario Completo de Ficheros](#12-inventario-ficheros)
13. [Troubleshooting](#13-troubleshooting)
14. [Referencias](#14-referencias)
15. [Registro de Cambios](#15-registro-cambios)

---

## 1. Resumen Ejecutivo

### 1.1 Que se implementa

Plan integral de 16 fases en 3 sprints para llevar `jaraba_andalucia_ei` al cumplimiento completo de la normativa PIIL CV 2025, integrarlo con los verticales de Empleabilidad y Emprendimiento del ecosistema Jaraba, y configurar el acceso de participantes y personal tecnico a todos los servicios SaaS requeridos por el programa.

**Ambito:**
1. **Cumplimiento normativo completo** (7 GAPs P0): Fases PIIL 6/6, actuaciones STO granulares, indicadores FSE+ en 3 momentos, insercion laboral detallada por tipo, DACI digital con firma, recibos de servicio universales, VoBo SAE para formacion
2. **Funcionalidad operativa completa** (6 GAPs P1): Calendario 12 semanas con 20 pildoras y 44 experimentos, DIME automatico con asignacion de carril, BMC integrado, alertas normativas de plazos, prospeccion empresarial
3. **Integracion cross-vertical** (NUEVO): Orquestacion de `jaraba_candidate` (perfil profesional, CV, competencias), `jaraba_business_tools` (BMC, validacion, SROI), `jaraba_mentoring` (sesiones humanas), `jaraba_matching` (matching empresa-participante), `jaraba_funding` (oportunidades de financiacion), `jaraba_skills` (evaluacion de competencias IA)
4. **Acceso a servicios SaaS** (NUEVO): Configuracion de roles, permisos y acceso para 45 participantes + 8-10 personal tecnico al stack completo del SaaS (Copilot IA, mentoria, business tools, matching, expediente, formacion, candidatura)
5. **Documentacion justificativa ampliada** (NUEVO): Documentos mas alla de los campos STO — justificacion economica, evidencia FSE+, informes para la Junta de Andalucia y la Comision Europea
6. **Elevacion a clase mundial** (4 GAPs P2 + 5 diferenciadores): Gamificacion con 24 hitos y ~1.500 Puntos de Impacto, Copilot IA contextual con restriccion pedagogica por fase y carril, Club Alumni con circulos de responsabilidad, checklist de alta autonomo con 21 pasos auto-verificables, analitica predictiva de riesgo de abandono, experiencia mobile-first (PWA, bottom nav, offline-first), medicion de impacto social automatizada (SROI)

**Cifras:**
- Nuevas entidades: 5 (ActuacionSto ampliada, InsercionLaboral, IndicadorFsePlus, ProspeccionEmpresarial, CalendarioSemanalEi)
- Campos modificados/anadidos: ~30 en entidades existentes
- Servicios nuevos: 12 (incluyendo PuntosImpactoEiService, RiesgoAbandonoService)
- Servicios cross-vertical consumidos (via @?): 8
- Templates nuevos/modificados: 18
- Parciales Twig nuevos: 14 (incluyendo alumni, logros-pi, checklist-autonomo, sroi, riesgo-abandono)
- Ficheros SCSS nuevos: 6 (actuaciones, calendario, indicadores, prospeccion, alumni, logros-pi)
- Tests nuevos: ~55 (Unit + Kernel)
- Roles configurados: 5 (participante_ei, coordinador_ei, orientador_ei, formador_ei, alumni_ei)
- Permisos nuevos: ~25
- Hitos de gamificacion: 24 (hasta ~1.540 Pi por participante)
- Modos Copilot configurados por fase: 7 modos x 6 fases x 2 carriles

### 1.2 Por que se implementa

**Riesgo financiero directo:** 202.500 EUR de subvencion (172.125 EUR FSE+ + 30.375 EUR Junta) requiere justificacion documental completa. Desglose:
- Modulo economico "persona atendida": 3.500 EUR/persona x 45 = 157.500 EUR (incluye incentivo 528 EUR/persona = 23.760 EUR)
- Modulo economico "persona insertada": 2.500 EUR/persona x 18 objetivo = 45.000 EUR
- **Sin tracking granular de actuaciones (GAP-PIIL-02), NO se puede demostrar las 10h orientacion + 50h formacion requeridas para justificar los 3.500 EUR/persona**
- **Sin detalle de insercion (GAP-PIIL-05), NO se puede certificar las 18 inserciones y los 45.000 EUR del modulo insercion**
- **Sin indicadores FSE+ (GAP-PIIL-03), la Comision Europea puede rechazar la certificacion del gasto (172.125 EUR)**

**Riesgo operativo:** Los orientadores gestionan actualmente con hojas Excel. El SaaS debe ser la herramienta primaria para que la inversion tecnologica tenga sentido y la justificacion sea trazable.

**Oportunidad de diferenciacion:** Al integrar los verticales de Empleabilidad y Emprendimiento, cada participante tiene acceso a herramientas de clase mundial que ninguna otra entidad PIIL ofrece: Copilot IA con 7 modos, BMC interactivo, matching semantico empresa-candidato, CV builder, mentoria IA + humana, evaluacion de competencias con IA.

### 1.3 Alcance y exclusiones

**INCLUIDO:**
- 5 nuevas ContentEntities con PremiumEntityFormBase, AccessControlHandler, Views, Field UI, templates, preprocess
- Ampliacion de ProgramaParticipanteEi (6 fases, colectivos corregidos, campos de enlace cross-vertical)
- Servicio universal de Recibo de Servicio PDF con firma dual (participante + tecnico)
- Consumo de servicios de 6 modulos cross-vertical (via `@?` opcional)
- Configuracion de 4 roles con permisos especificos
- 12+ templates Twig con zero-region pattern
- 4+ ficheros SCSS con tokens CSS `--ej-*`
- 45+ tests PHPUnit (Unit + Kernel)
- hook_update_N() para cada cambio de esquema
- Documentacion justificativa PDF: DACI, recibo, hoja servicio, informe progreso, ficha insercion, justificacion economica

**EXCLUIDO:**
- Integracion directa con STO (sistema web-only sin API; la exportacion CSV existente cubre esta necesidad)
- Modulo de pagos/facturacion (el programa es 100% fondos publicos, no hay cobro a participantes)
- Migracion de datos desde otros sistemas (el programa inicia de cero)
- Desarrollo de nuevos verticales o modulos fuera de los existentes

### 1.4 Filosofia de implementacion

**"Sin Humo"** — Cada linea de codigo justifica su existencia:
- **Orquestar, no reinventar:** Andalucia +ei consume los servicios de Empleabilidad (jaraba_candidate, jaraba_matching, jaraba_skills) y Emprendimiento (jaraba_business_tools, jaraba_funding) via inyeccion opcional (@?). Si el modulo no esta instalado, la funcionalidad se degrada gracefully
- **Entidades granulares, no campos masivos:** Cada actuacion, insercion y recogida FSE+ es una entidad separada con su propio ciclo de vida, permisos y auditoria
- **PDF trazable:** Cada documento generado es trazable hasta la entidad que lo origino, firmable digitalmente y almacenable en Legal Vault
- **Tenant-first:** TENANT-001 sin excepciones. Cada query filtra por tenant
- **Progressive enhancement:** El sistema funciona con el modulo standalone; las integraciones cross-vertical anaden valor pero nunca son bloqueantes

### 1.5 Estimacion de esfuerzo por sprint

| Sprint | Fases | Prioridad | Horas estimadas | Objetivo |
|--------|-------|-----------|-----------------|----------|
| Sprint 1 | 1-7 | P0 | 50-65h | Cumplimiento normativo — justificar 202.500 EUR |
| Sprint 2 | 8-11 | P1 + CROSS | 35-45h | Operativo + integracion cross-vertical |
| Sprint 3 | 12-16 | P2 + ACCESS | 45-60h | Acceso servicios + elevacion clase mundial + testing |
| **Total** | **16** | — | **130-170h** | **Programa operativo clase mundial** |

---

## 2. Diagnostico del Estado Actual

### 2.1 Lo que ya existe y funciona

| Componente | Estado | Observaciones |
|-----------|--------|---------------|
| `ProgramaParticipanteEi` entity | Funcional | 22 campos, tracking horas por contadores |
| `SolicitudEi` entity | Funcional | 24 campos con triage IA |
| `ExpedienteDocumento` entity | Funcional | 23 campos, 22 categorias, revision IA |
| `ActuacionSto` entity | Existe | Tiene campos basicos pero no el detalle STO completo |
| `FaseTransitionManager` | Funcional | Solo 3 fases (atencion/insercion/baja) |
| `StoExportService` | Funcional | CSV para carga manual en STO |
| `ExpedienteService` | Funcional | CRUD documental con Legal Vault |
| `DaciService` | Existe | Servicio registrado, genera PDF |
| `ReciboServicioService` | Existe | Servicio registrado, genera recibo |
| `InformeProgresoPdfService` | Funcional | PDF de progreso con branded template |
| `HojaServicioMentoriaService` | Funcional | Hoja de servicio para mentoria |
| `AiMentorshipTracker` | Funcional | Tracking horas Copilot IA |
| `HumanMentorshipTracker` | Funcional | Tracking horas mentoria humana |
| `SolicitudTriageService` | Funcional | Triage IA de solicitudes |
| `DocumentoRevisionIaService` | Funcional | Revision IA de documentos |
| `AdaptiveDifficultyEngine` | Funcional | Motor dificultad adaptativa |
| `AndaluciaEiCopilotContextProvider` | Funcional | Contexto para Copilot v2 |
| `AndaluciaEiCopilotBridgeService` | Funcional | Bridge con Copilot v2 |
| `CoordinadorHubService` | Funcional | Hub operativo coordinador |
| `AlertasNormativasService` | Funcional | Alertas basicas (DACI, FSE+, carril, horas) |
| `CalendarioProgramaService` | Existe | Servicio registrado |
| Dashboards coordinador/orientador | Funcional | Templates y APIs |
| Portal participante | Funcional | Timeline, health score, expediente |
| Landing publica + solicitudes | Funcional | Con reclutamiento |
| Guia participante interactiva | Funcional | Con leads y CRM |
| 14 controllers, 58 rutas | Funcional | Frontend + API REST |
| TENANT-001 compliance | Recien completada | Auditoria 2026-03-10 |

### 2.2 Brechas identificadas

| ID | Descripcion | Prioridad | Riesgo financiero | Sprint |
|----|-------------|-----------|-------------------|--------|
| GAP-PIIL-01 | Fases incompletas (3/6) | P0 | Alto — no trazable en STO | 1 |
| GAP-PIIL-02 | Sin tracking granular de actuaciones | P0 | Critico — no justificable | 1 |
| GAP-PIIL-03 | Indicadores FSE+ sin tracking | P0 | Critico — 172.125 EUR | 1 |
| GAP-PIIL-04 | Formacion sin VoBo SAE | P0 | Alto — actuaciones invalidas | 1 |
| GAP-PIIL-05 | Insercion laboral sin detalle | P0 | Critico — 45.000 EUR | 1 |
| GAP-PIIL-06 | DACI digital inexistente | P0 | Alto — participacion no valida | 1 |
| GAP-PIIL-07 | Colectivos incorrectos | P0 | Medio — registro incorrecto en STO | 1 |
| GAP-PIIL-08 | Calendario 12 semanas desconectado | P1 | Bajo — operativo | 2 |
| GAP-PIIL-09 | DIME diagnostico desconectado | P1 | Bajo — carril manual | 2 |
| GAP-PIIL-10 | Validacion BMC desconectada | P1 | Bajo — sin visibilidad coordinador | 2 |
| GAP-PIIL-11 | Plazos normativos sin alertas | P1 | Medio — riesgo incumplimiento plazos | 2 |
| GAP-PIIL-12 | Prospeccion empresarial sin tracking | P1 | Medio — insercion no documentada | 2 |
| GAP-PIIL-13 | Recibo de servicio incompleto | P0 | Alto — actuaciones sin firma | 1 |
| GAP-PIIL-14 | Gamificacion Pi desconectada | P2 | Ninguno | 3 |
| GAP-PIIL-15 | Club Alumni desconectado | P2 | Ninguno | 3 |
| GAP-PIIL-16 | Alta Autonomo sin checklist | P2 | Bajo — insercion c.propia | 3 |
| GAP-PIIL-17 | Copilot sin restriccion por fase | P2 | Ninguno | 3 |

### 2.3 Dependencias entre brechas

```
GAP-01 (Fases) ──┬──> GAP-02 (Actuaciones) ──> GAP-13 (Recibos)
                  ├──> GAP-06 (DACI) ── depende de fase acogida
                  ├──> GAP-03 (FSE+) ── depende de fases entrada/salida
                  └──> GAP-05 (Insercion) ── depende de fase insercion

GAP-02 (Actuaciones) ──> GAP-04 (VoBo SAE) ── campo en actuacion formativa
                     ──> GAP-12 (Prospeccion) ── tipo actuacion adicional

GAP-08 (Calendario) ──> GAP-09 (DIME) ── semana 1 del calendario
                    ──> GAP-10 (BMC) ── semanas 3-12 del calendario

GAP-11 (Alertas) ── independiente, consume datos de GAP-01..07

CROSS-VERTICAL ── depende de GAP-01..07 completados (Sprint 2)
ACCESS ── puede comenzar en paralelo con Sprint 1
```

### 2.4 Servicios cross-vertical disponibles

Los siguientes servicios existen en el ecosistema y DEBEN ser consumidos por andalucia_ei (principio: orquestar, no reinventar):

| Modulo | Servicio | Metodos clave para andalucia_ei |
|--------|----------|--------------------------------|
| `jaraba_candidate` | `CandidateProfileService` | `getProfileByUserId($uid)`, `createProfile($uid, $data)` |
| `jaraba_candidate` | `CvBuilderService` | `generateCv($profile, $template, $format)`, `collectCvData($profile)` |
| `jaraba_candidate` | `SkillsService` | `assessSkills($uid, $answers)`, `getSkillProfile($uid)`, `suggestTraining($uid, $gaps)` |
| `jaraba_business_tools` | `CanvasService` | `createCanvas($data)`, `getCanvasWithBlocks($id)`, `updateCompletenessScore($id)` |
| `jaraba_business_tools` | `SroiCalculatorService` | `calculateSroi($options)`, `generateImpactReport($options)` |
| `jaraba_mentoring` | `SessionSchedulerService` | `getAvailableSlots($mentor, $start, $end)`, `bookSession($engagement, $datetime)` |
| `jaraba_mentoring` | `MentorMatchingService` | `findMatches($criteria, $limit)` |
| `jaraba_matching` | `MatchingService` | `getTopCandidatesForJob($job_id, $limit, $tenant_id)` |
| `jaraba_funding` | `ApplicationManagerService` | `getRecentApplications($limit)`, `getDashboardStats()` |

---

## 3. Arquitectura Objetivo

### 3.1 Modelo de datos ampliado

```
ProgramaParticipanteEi (MODIFICADA)
├── fase_actual: 6 valores (acogida, diagnostico, atencion, insercion, seguimiento, baja)
├── colectivo: 4 valores (larga_duracion, mayores_45, migrantes, perceptores_prestaciones)
├── daci_firmado: boolean
├── fse_entrada_completado: boolean
├── fse_salida_completado: boolean
├── candidate_profile_id: integer (FK opcional a jaraba_candidate)
├── canvas_id: integer (FK opcional a jaraba_business_tools)
├── semana_actual: integer
│
├── 1:N ──> ActuacionSto (NUEVA/AMPLIADA)
│           ├── tipo_actuacion: orientacion_individual, orientacion_grupal, formacion, prospeccion, insercion
│           ├── fecha, hora_inicio, hora_fin, duracion_calculada
│           ├── contenido, resultado, lugar
│           ├── orientador_id, formador_id
│           ├── firmado_participante, firmado_orientador
│           ├── recibo_servicio_id (FK a expediente_documento)
│           ├── vobo_sae_status (solo tipo formacion)
│           ├── vobo_sae_fecha, vobo_sae_documento_id
│           ├── entidad_formadora, certificacion
│           └── tenant_id
│
├── 1:N ──> IndicadorFsePlus (NUEVA)
│           ├── momento: entrada, salida, seguimiento_6m
│           ├── situacion_laboral_detallada
│           ├── nivel_educativo_isced
│           ├── discapacidad_tipo, discapacidad_grado
│           ├── pais_origen, nacionalidad
│           ├── hogar_unipersonal, hijos_cargo
│           ├── zona_rural_urbana, sin_hogar, comunidad_marginada
│           ├── cualificacion_obtenida (salida)
│           ├── mejora_situacion (salida/6m)
│           ├── tipo_contrato_actual (6m)
│           ├── inclusion_social (6m)
│           ├── fecha_recogida
│           └── tenant_id
│
├── 1:1 ──> InsercionLaboral (NUEVA)
│           ├── tipo: cuenta_ajena, cuenta_propia, agrario
│           ├── empresa_nombre, empresa_cif (cuenta_ajena/agrario)
│           ├── tipo_contrato, jornada (cuenta_ajena)
│           ├── fecha_alta_ss, codigo_cuenta_cotizacion (cuenta_ajena)
│           ├── fecha_alta_reta, cnae_sector (cuenta_propia)
│           ├── modelo_036_037 (cuenta_propia)
│           ├── tipo_cultivo, fechas_campana (agrario)
│           ├── documentacion_verificada: boolean
│           ├── fecha_verificacion
│           └── tenant_id
│
├── 1:N ──> ProspeccionEmpresarial (NUEVA)
│           ├── empresa_nombre, empresa_cif
│           ├── contacto_nombre, contacto_email, contacto_telefono
│           ├── sector, tamano_empresa
│           ├── resultado: contacto_inicial, reunion, propuesta, acuerdo, descartada
│           ├── notas, fecha_contacto, fecha_seguimiento
│           ├── orientador_id
│           └── tenant_id
│
└── via CalendarioProgramaService ──> CalendarioSemanalEi (NUEVA)
            ├── participante_id
            ├── semana_numero (1-12)
            ├── fase_metodologica: mentalidad, validacion, viabilidad, ventas, cierre
            ├── pildora_id (referencia a contenido formativo)
            ├── pildora_completada: boolean
            ├── experimentos_asignados: string_long (JSON array)
            ├── experimentos_completados: string_long (JSON array)
            ├── hito_evaluacion: string
            ├── hito_completado: boolean
            ├── fecha_inicio_semana, fecha_fin_semana
            └── tenant_id
```

### 3.2 Nuevas entidades con esquema completo

#### 3.2.1 ActuacionSto (ampliacion de la existente)

**Proposito:** Registrar cada actuacion individual del itinerario, tal como exige el STO. Cada fila = 1 sesion de orientacion, 1 taller grupal, 1 bloque de formacion, 1 visita de prospeccion, etc.

**Ruta admin:** `/admin/content/actuaciones-sto`
**Ruta frontend:** Via slide-panel desde dashboards de coordinador/orientador

**Campos a verificar/anadir respecto a la definicion existente:**

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `tipo_actuacion` | list_string | SI | orientacion_individual, orientacion_grupal, formacion, prospeccion, insercion_laboral |
| `participante_id` | entity_reference | SI | FK a programa_participante_ei |
| `fecha` | datetime (date) | SI | Fecha de la actuacion |
| `hora_inicio` | string (5) | SI | HH:MM formato 24h |
| `hora_fin` | string (5) | SI | HH:MM |
| `duracion_horas` | decimal (4,2) | Computed | Calculada automaticamente en preSave |
| `contenido` | text_long | SI | Descripcion del contenido de la actuacion |
| `resultado` | text_long | NO | Resultado/observaciones |
| `lugar` | string (255) | SI | Ubicacion (presencial/online) |
| `orientador_id` | entity_reference (user) | SI | Tecnico responsable |
| `formador_id` | entity_reference (user) | NO | Solo para tipo formacion |
| `n_participantes_grupo` | integer | NO | Solo para orientacion_grupal |
| `firmado_participante` | boolean | NO | Firma del participante |
| `firmado_orientador` | boolean | NO | Firma del orientador/formador |
| `recibo_servicio_id` | entity_reference | NO | FK a expediente_documento (recibo generado) |
| `vobo_sae_status` | list_string | NO | pendiente, aprobado, rechazado, modificaciones (solo formacion) |
| `vobo_sae_fecha` | datetime | NO | Fecha del VoBo SAE |
| `vobo_sae_documento_id` | entity_reference | NO | FK a expediente_documento (documento VoBo) |
| `entidad_formadora` | string (255) | NO | Nombre entidad formadora (solo formacion) |
| `certificacion` | string (255) | NO | Tipo de certificacion otorgada |
| `codigo_proyecto` | string (20) | NO | Codigo del proyecto PIIL |
| `tenant_id` | entity_reference (group) | SI | TENANT-001 |
| `status` | boolean | SI | Publicado |

**Logica de negocio:**
- `preSave()`: Calcular `duracion_horas` = diff(hora_fin - hora_inicio). Actualizar contadores en ProgramaParticipanteEi (horas_orientacion_ind, horas_orientacion_grup, horas_formacion) via `ActuacionStoService::recalcularHoras($participanteId)`
- `AccessControlHandler`: tenant match + permiso por tipo (`administer actuacion_sto`, `create actuacion_sto`, `view own actuacion_sto`)
- `preSave()` resiliente: PRESAVE-RESILIENCE-001 — try-catch con \Throwable para actualizacion de contadores

#### 3.2.2 IndicadorFsePlus

**Proposito:** Recoger los indicadores sociodemograficos y laborales exigidos por el Fondo Social Europeo Plus en 3 momentos del itinerario: entrada (inscripcion), salida (finalizacion/insercion), y seguimiento a 6 meses post-salida.

**Ruta admin:** `/admin/content/indicadores-fse`
**Ruta frontend:** Formulario en slide-panel desde portal participante (coordinador rellena)

| Campo | Tipo | Momento | Descripcion |
|-------|------|---------|-------------|
| `participante_id` | entity_reference | Todos | FK a programa_participante_ei |
| `momento` | list_string | — | entrada, salida, seguimiento_6m |
| `fecha_recogida` | datetime | Todos | Fecha en que se recogieron los datos |
| `situacion_laboral` | list_string | Todos | desempleado_corta, desempleado_larga, ocupado_cuenta_ajena, ocupado_cuenta_propia, inactivo, estudiante |
| `nivel_educativo_isced` | list_string | Entrada | isced_0 a isced_8 (clasificacion CINE/ISCED) |
| `discapacidad` | boolean | Entrada | Tiene discapacidad reconocida |
| `discapacidad_tipo` | list_string | Entrada | fisica, sensorial, intelectual, mental, multiple |
| `discapacidad_grado` | integer | Entrada | Grado 0-100% |
| `pais_origen` | string (2) | Entrada | Codigo ISO 3166-1 alpha-2 |
| `nacionalidad` | string (2) | Entrada | Codigo ISO |
| `hogar_unipersonal` | boolean | Entrada | Vive solo/a |
| `hijos_cargo` | integer | Entrada | Numero de hijos a cargo |
| `zona` | list_string | Entrada | urbana, periurbana, rural |
| `sin_hogar` | boolean | Entrada | Situacion de sin hogar o exclusion residencial |
| `comunidad_marginada` | boolean | Entrada | Pertenece a comunidad marginada (Roma, etc.) |
| `cualificacion_obtenida` | list_string | Salida | ninguna, certificado_asistencia, certificado_profesionalidad, titulo_oficial |
| `mejora_situacion` | list_string | Salida/6m | sin_cambio, empleo, mejora_empleo, formacion, autoempleo |
| `tipo_contrato_actual` | list_string | 6m | indefinido, temporal, practicas, formacion, autonomo |
| `inclusion_social` | boolean | 6m | Mejora percibida en inclusion social |
| `notas` | text_long | Todos | Observaciones del tecnico |
| `recogido_por` | entity_reference (user) | Todos | Tecnico que recogio los datos |
| `tenant_id` | entity_reference (group) | Todos | TENANT-001 |
| `status` | boolean | Todos | Publicado |

**Logica de negocio:**
- Al guardar un IndicadorFsePlus de momento `entrada`, actualizar `ProgramaParticipanteEi::fse_entrada_completado = TRUE`
- Al guardar uno de momento `salida`, actualizar `fse_salida_completado = TRUE`
- Validacion: solo 1 indicador de tipo `entrada` por participante, solo 1 de tipo `salida`, solo 1 de tipo `seguimiento_6m`
- AlertasNormativasService consume estos datos para generar alertas de "FSE+ pendiente"

#### 3.2.3 InsercionLaboral

**Proposito:** Documentar el detalle completo de la insercion laboral segun el tipo (cuenta ajena, cuenta propia, agrario), con todos los campos que exige la normativa para la justificacion del modulo economico de 2.500 EUR/persona insertada.

**Ruta admin:** `/admin/content/inserciones-laborales`
**Ruta frontend:** Formulario en slide-panel desde dashboard coordinador

| Campo | Tipo | Aplica a | Descripcion |
|-------|------|----------|-------------|
| `participante_id` | entity_reference | Todos | FK a programa_participante_ei |
| `tipo` | list_string | — | cuenta_ajena, cuenta_propia, agrario |
| `fecha_insercion` | datetime (date) | Todos | Fecha efectiva de la insercion |
| `empresa_nombre` | string (255) | c.ajena, agrario | Nombre de la empresa |
| `empresa_cif` | string (12) | c.ajena, agrario | CIF de la empresa |
| `tipo_contrato` | list_string | c.ajena | indefinido, temporal, practicas, formacion_alternancia |
| `jornada` | list_string | c.ajena | completa, parcial |
| `jornada_horas` | decimal (4,2) | c.ajena (parcial) | Horas semanales si parcial |
| `fecha_alta_ss` | datetime (date) | c.ajena | Fecha alta Seguridad Social |
| `codigo_cuenta_cotizacion` | string (20) | c.ajena | CCC de la empresa |
| `sector_cnae` | string (10) | Todos | Codigo CNAE del sector |
| `fecha_alta_reta` | datetime (date) | c.propia | Fecha alta RETA |
| `modelo_036_037` | boolean | c.propia | Presentado modelo censal |
| `tipo_cultivo` | string (100) | agrario | Tipo de cultivo |
| `fecha_inicio_campana` | datetime (date) | agrario | Inicio campana |
| `fecha_fin_campana` | datetime (date) | agrario | Fin campana |
| `documentacion_verificada` | boolean | Todos | El coordinador ha verificado documentacion |
| `fecha_verificacion` | datetime | Todos | Fecha de verificacion |
| `verificador_id` | entity_reference (user) | Todos | Coordinador que verifico |
| `notas` | text_long | Todos | Observaciones |
| `mantenimiento_4m` | boolean | Todos | Se ha verificado 4 meses alta SS |
| `fecha_verificacion_4m` | datetime | Todos | Fecha de verificacion de los 4 meses |
| `tenant_id` | entity_reference (group) | Todos | TENANT-001 |
| `status` | boolean | Todos | Publicado |

**Logica de negocio:**
- Al guardar con `documentacion_verificada = TRUE`, actualizar `ProgramaParticipanteEi::fecha_insercion` y `tipo_insercion`
- Cuando `mantenimiento_4m = TRUE`, el participante es certificable como "persona insertada" (2.500 EUR)
- Formulario condicional: campos visibles dependen de `tipo` (cuenta_ajena/propia/agrario)

#### 3.2.4 ProspeccionEmpresarial

**Proposito:** Documentar las acciones de prospeccion empresarial que realizan los orientadores para conseguir inserciones laborales. La normativa exige evidenciar el esfuerzo de intermediacion.

**Ruta admin:** `/admin/content/prospecciones`
**Ruta frontend:** Slide-panel desde dashboard orientador

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| `empresa_nombre` | string (255) | Nombre de la empresa |
| `empresa_cif` | string (12) | CIF |
| `contacto_nombre` | string (255) | Persona de contacto |
| `contacto_email` | email | Email |
| `contacto_telefono` | string (20) | Telefono |
| `sector` | string (100) | Sector de actividad |
| `tamano_empresa` | list_string | micro, pequena, mediana, grande |
| `resultado` | list_string | contacto_inicial, reunion_programada, reunion_realizada, propuesta_enviada, acuerdo, descartada |
| `fecha_contacto` | datetime (date) | Fecha del contacto |
| `fecha_seguimiento` | datetime (date) | Proxima fecha de seguimiento |
| `notas` | text_long | Observaciones |
| `orientador_id` | entity_reference (user) | Orientador responsable |
| `participantes_vinculados` | entity_reference (multiple) | Participantes para los que se prospecta |
| `tenant_id` | entity_reference (group) | TENANT-001 |
| `status` | boolean | Publicado |

#### 3.2.5 CalendarioSemanalEi

**Proposito:** Mapear las 12 semanas del programa con sus pildoras formativas (20), experimentos asignados (de los 44 catalogados), hitos de evaluacion y fase metodologica. Permite al participante saber exactamente en que punto del programa esta y que debe hacer esta semana.

**Ruta admin:** `/admin/content/calendario-ei`
**Ruta frontend:** Widget en portal participante

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| `participante_id` | entity_reference | FK a programa_participante_ei |
| `semana_numero` | integer | 1-12 |
| `fase_metodologica` | list_string | mentalidad (sem 1-2), validacion (sem 3-5), viabilidad (sem 6-8), ventas (sem 9-10), cierre (sem 11-12) |
| `pildora_ref` | string (50) | Referencia a la pildora formativa |
| `pildora_titulo` | string (255) | Titulo de la pildora |
| `pildora_completada` | boolean | Participante ha visto la pildora |
| `pildora_fecha_completada` | datetime | Cuando la completo |
| `experimentos_asignados` | text_long | JSON: [{"id": "D-01", "titulo": "...", "tipo": "discovery"}] |
| `experimentos_completados` | text_long | JSON: IDs completados |
| `hito_evaluacion` | string (255) | Descripcion del hito semanal |
| `hito_completado` | boolean | Hito alcanzado |
| `fecha_inicio` | datetime (date) | Lunes de la semana |
| `fecha_fin` | datetime (date) | Domingo de la semana |
| `notas_orientador` | text_long | Observaciones del orientador |
| `tenant_id` | entity_reference (group) | TENANT-001 |
| `status` | boolean | Publicado |

### 3.3 Campos modificados en entidades existentes

#### ProgramaParticipanteEi — Modificaciones

| Campo | Accion | Detalle |
|-------|--------|---------|
| `fase_actual` | MODIFICAR allowed_values | Anadir: acogida, diagnostico, seguimiento (de 3 a 6 valores) |
| `colectivo` | MODIFICAR allowed_values | Quitar: jovenes. Anadir: migrantes, perceptores_prestaciones |
| `candidate_profile_id` | NUEVO integer | FK opcional a CandidateProfile (cross-vertical) |
| `canvas_id` | NUEVO integer | FK opcional a BusinessModelCanvas (cross-vertical, solo carril Acelera) |
| `dime_score` | NUEVO integer | Score DIME 0-20 (sincronizado desde Copilot v2) |
| `dime_fecha` | NUEVO datetime | Fecha del diagnostico DIME |
| `horas_orientacion_insercion` | NUEVO decimal(8,2) | Horas de orientacion especificas para insercion (de las 40h requeridas) |
| `asistencia_porcentaje` | NUEVO decimal(5,2) | Porcentaje de asistencia calculado |
| `es_persona_atendida` | NUEVO boolean | Computed: >=10h orient + >=50h form + >=75% asist |
| `es_persona_insertada` | NUEVO boolean | Computed: atendida + >=40h orient insercion + >=4m alta SS |

**IMPORTANTE:** Cada modificacion requiere `hook_update_N()` con `EntityDefinitionUpdateManager` segun UPDATE-HOOK-REQUIRED-001. Usar `getFieldStorageDefinitions()` (NO `getBaseFieldDefinitions()`) segun UPDATE-HOOK-FIELDABLE-001.

#### SolicitudEi — Sin cambios

La entidad de solicitud esta completa para su proposito.

#### ExpedienteDocumento — Modificaciones menores

| Campo | Accion | Detalle |
|-------|--------|---------|
| `categoria` | MODIFICAR allowed_values | Anadir: recibo_orientacion, recibo_formacion, recibo_grupal, ficha_insercion, indicadores_fse_entrada, indicadores_fse_salida, indicadores_fse_6m, justificacion_economica, vobo_sae |

### 3.4 Arquitectura de servicios

#### Servicios nuevos

| Servicio ID | Clase | Responsabilidad | Dependencias |
|-------------|-------|-----------------|--------------|
| `jaraba_andalucia_ei.actuacion_sto` | ActuacionStoService (AMPLIAR) | CRUD actuaciones + recalculo horas + generacion recibo | `@entity_type.manager`, `@logger.channel.jaraba_andalucia_ei`, `@?jaraba_andalucia_ei.recibo_servicio` |
| `jaraba_andalucia_ei.indicador_fse` | IndicadorFseService | CRUD indicadores FSE+ + validacion unicidad por momento | `@entity_type.manager`, `@logger.channel.jaraba_andalucia_ei` |
| `jaraba_andalucia_ei.insercion_laboral` | InsercionLaboralService | CRUD inserciones + verificacion + actualizacion participante | `@entity_type.manager`, `@logger.channel.jaraba_andalucia_ei` |
| `jaraba_andalucia_ei.prospeccion` | ProspeccionService | CRUD prospeccion empresarial + pipeline CRM basico | `@entity_type.manager`, `@logger.channel.jaraba_andalucia_ei` |
| `jaraba_andalucia_ei.calendario_ei` | CalendarioEiService | Gestion calendario 12 semanas + tracking pildoras/experimentos | `@entity_type.manager`, `@logger.channel.jaraba_andalucia_ei` |
| `jaraba_andalucia_ei.cross_vertical_bridge` | CrossVerticalBridgeService | Orquestacion de servicios cross-vertical | `@entity_type.manager`, `@logger.channel.jaraba_andalucia_ei`, `@?jaraba_candidate.profile`, `@?jaraba_business_tools.canvas`, `@?jaraba_mentoring.session_scheduler`, `@?jaraba_matching.matching`, `@?jaraba_candidate.skills`, `@?jaraba_funding.application_manager` |
| `jaraba_andalucia_ei.justificacion_economica` | JustificacionEconomicaService | Calculo modulos economicos + generacion informes trimestrales/finales | `@entity_type.manager`, `@logger.channel.jaraba_andalucia_ei`, `@?jaraba_andalucia_ei.actuacion_sto`, `@?jaraba_andalucia_ei.insercion_laboral` |
| `jaraba_andalucia_ei.acceso_programa` | AccesoProgramaService | Configuracion automatizada de roles y permisos para participantes y personal tecnico | `@entity_type.manager`, `@current_user`, `@logger.channel.jaraba_andalucia_ei`, `@?ecosistema_jaraba_core.tenant_context` |

#### Servicios existentes a ampliar

| Servicio | Ampliacion |
|----------|-----------|
| `AlertasNormativasService` | Anadir alertas de plazos normativos (30 dias comunicacion inicio, trimestrales, justificacion) |
| `CalendarioProgramaService` | Integrar con CalendarioSemanalEi entity |
| `FaseTransitionManager` | Ampliar a 6 fases con validaciones por fase |
| `AndaluciaEiCopilotContextProvider` | Inyectar contexto de fase actual para restriccion de modos Copilot |
| `CoordinadorHubService` | Anadir metricas de actuaciones, inserciones, FSE+ |
| `StoExportService` | Exportar datos de ActuacionSto (no solo contadores) |

### 3.5 Mapa de rutas

#### Rutas admin nuevas

| Ruta | Controller/Form | Descripcion |
|------|----------------|-------------|
| `/admin/content/actuaciones-sto` | Entity list | Listado de actuaciones STO |
| `/admin/content/indicadores-fse` | Entity list | Listado indicadores FSE+ |
| `/admin/content/inserciones-laborales` | Entity list | Listado inserciones laborales |
| `/admin/content/prospecciones` | Entity list | Listado prospecciones |
| `/admin/content/calendario-ei` | Entity list | Listado calendario semanal |
| `/admin/structure/actuacion-sto/settings` | Settings form | Configuracion Field UI |
| `/admin/structure/indicador-fse/settings` | Settings form | Configuracion Field UI |
| `/admin/structure/insercion-laboral/settings` | Settings form | Configuracion Field UI |
| `/admin/structure/prospeccion-empresarial/settings` | Settings form | Configuracion Field UI |
| `/admin/structure/calendario-semanal-ei/settings` | Settings form | Configuracion Field UI |

#### Rutas frontend nuevas/modificadas

| Ruta | Metodo | Descripcion |
|------|--------|-------------|
| `/api/v1/andalucia-ei/actuaciones` | GET | Listar actuaciones del participante |
| `/api/v1/andalucia-ei/actuaciones` | POST | Crear nueva actuacion (slide-panel) |
| `/api/v1/andalucia-ei/actuaciones/{id}` | PATCH | Actualizar actuacion |
| `/api/v1/andalucia-ei/actuaciones/{id}/firmar` | POST | Firmar actuacion |
| `/api/v1/andalucia-ei/actuaciones/{id}/recibo` | GET | Descargar recibo PDF |
| `/api/v1/andalucia-ei/fse-indicadores` | POST | Guardar indicadores FSE+ |
| `/api/v1/andalucia-ei/fse-indicadores/{participante_id}` | GET | Obtener indicadores FSE+ |
| `/api/v1/andalucia-ei/insercion` | POST | Registrar insercion laboral |
| `/api/v1/andalucia-ei/insercion/{participante_id}` | GET | Obtener detalle insercion |
| `/api/v1/andalucia-ei/prospeccion` | GET/POST | CRUD prospeccion |
| `/api/v1/andalucia-ei/calendario/{participante_id}` | GET | Calendario 12 semanas |
| `/api/v1/andalucia-ei/calendario/{participante_id}/semana/{n}` | PATCH | Actualizar progreso semana |
| `/api/v1/andalucia-ei/cross-vertical/perfil` | GET | Perfil cross-vertical integrado |
| `/api/v1/andalucia-ei/justificacion-economica` | GET | Resumen justificacion economica |

#### Rutas con proteccion CSRF y permisos

Todas las rutas API usan `_csrf_request_header_token: 'TRUE'` (CSRF-API-001) y permisos granulares:

```yaml
# Ejemplo para actuaciones
jaraba_andalucia_ei.api.actuaciones.create:
  path: '/api/v1/andalucia-ei/actuaciones'
  defaults:
    _controller: '\Drupal\jaraba_andalucia_ei\Controller\ActuacionStoApiController::create'
  requirements:
    _permission: 'create actuacion_sto'
    _csrf_request_header_token: 'TRUE'
  methods: [POST]
```

### 3.6 Diagrama de flujo del itinerario completo

```
SOLICITUD
   │
   ▼
[SolicitudEi] ──triage IA──> admitido
   │
   ▼
ACOGIDA (fase_actual = 'acogida')
   ├── Alta en STO (fecha_alta_sto)
   ├── Firma DACI (DaciService → daci_firmado = TRUE)
   ├── Recogida FSE+ entrada (IndicadorFsePlus momento=entrada → fse_entrada_completado = TRUE)
   ├── Crear CandidateProfile (cross-vertical: CandidateProfileService::createProfile)
   ├── Evaluacion competencias (cross-vertical: SkillsService::assessSkills)
   │
   ▼ FaseTransitionManager::canTransit('acogida' → 'diagnostico')
     Requisitos: daci_firmado AND fse_entrada_completado

DIAGNOSTICO (fase_actual = 'diagnostico')
   ├── DIME (10 preguntas → score 0-20 → carril automatico)
   │   └── Impulso (0-9) | Acelera (10-20)
   ├── Si Acelera: crear BMC (cross-vertical: CanvasService::createCanvas)
   ├── Itinerario personalizado asignado
   │
   ▼ FaseTransitionManager::canTransit('diagnostico' → 'atencion')
     Requisitos: carril asignado AND dime_score IS NOT NULL

ATENCION (fase_actual = 'atencion')
   ├── Orientacion individual (ActuacionSto tipo=orientacion_individual)
   │   └── Recibo de servicio generado y firmado
   ├── Orientacion grupal (ActuacionSto tipo=orientacion_grupal)
   ├── Formacion (ActuacionSto tipo=formacion + vobo_sae_status)
   │   └── VoBo SAE requerido antes de impartir
   ├── Mentoria IA (AiMentorshipTracker + 7 modos Copilot)
   ├── Mentoria humana (HumanMentorshipTracker + jaraba_mentoring)
   ├── Calendario 12 semanas (CalendarioSemanalEi)
   │   ├── 20 pildoras de video
   │   ├── 44 experimentos catalogados
   │   └── Hitos semanales
   ├── BMC iterativo (cross-vertical: CanvasService, solo Acelera)
   ├── Matching competencias-mercado (cross-vertical: SkillsService::getSkillGaps)
   │
   │   Meta "persona atendida": ≥10h orientacion (≥2h ind) + ≥50h formacion + ≥75% asistencia
   │
   ▼ FaseTransitionManager::canTransit('atencion' → 'insercion')
     Requisitos: es_persona_atendida = TRUE

INSERCION (fase_actual = 'insercion')
   ├── Prospeccion empresarial (ProspeccionEmpresarial)
   ├── Matching empresa-candidato (cross-vertical: MatchingService)
   ├── CV profesional (cross-vertical: CvBuilderService::generateCv)
   ├── Orientacion para insercion (ActuacionSto, horas_orientacion_insercion)
   │   └── Meta: ≥40h orientacion para insercion
   ├── Documentar insercion (InsercionLaboral entity)
   │   ├── Cuenta ajena: CIF, contrato, SS
   │   ├── Cuenta propia: RETA, modelo 036
   │   └── Agrario: campana, cultivo
   ├── Financiacion emprendedores (cross-vertical: ApplicationManagerService)
   │
   ▼ FaseTransitionManager::canTransit('insercion' → 'seguimiento')
     Requisitos: InsercionLaboral.documentacion_verificada = TRUE

SEGUIMIENTO (fase_actual = 'seguimiento')
   ├── Verificacion 4 meses alta SS (InsercionLaboral.mantenimiento_4m)
   ├── Recogida FSE+ salida (IndicadorFsePlus momento=salida)
   ├── Recogida FSE+ 6 meses (IndicadorFsePlus momento=seguimiento_6m)
   │
   │   Meta "persona insertada": atendida + ≥40h orient insercion + ≥4m alta SS
   │
   ▼ Fin programa o FaseTransitionManager::transit → 'baja'

BAJA (fase_actual = 'baja')
   ├── Motivo documentado
   ├── FSE+ salida recogido si no se hizo
   └── Cierre expediente
```

---

## 4. Integracion Cross-Vertical

### 4.1 Principio

**"Orquestar, no reinventar."** Andalucia +ei es un programa PIIL que necesita:
- Orientacion profesional → ya existe en `jaraba_candidate`
- Herramientas de emprendimiento → ya existen en `jaraba_business_tools`
- Sesiones de mentoria → ya existen en `jaraba_mentoring`
- Matching empresa-candidato → ya existe en `jaraba_matching`
- Evaluacion de competencias → ya existe en `jaraba_skills` (para IA) y `jaraba_candidate` (para candidatos)
- Oportunidades de financiacion → ya existen en `jaraba_funding`

El nuevo servicio `CrossVerticalBridgeService` actua como orquestador:

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Orquesta servicios cross-vertical para participantes Andalucia +ei.
 *
 * Principio: consume servicios existentes via @? (opcional).
 * Si un modulo no esta instalado, la funcionalidad se degrada gracefully.
 *
 * OPTIONAL-CROSSMODULE-001: Todas las dependencias cross-modulo son @?.
 * SERVICE-CALL-CONTRACT-001: Firmas de metodo verificadas contra las interfaces.
 */
class CrossVerticalBridgeService {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
    protected ?object $candidateProfileService = NULL,
    protected ?object $canvasService = NULL,
    protected ?object $sessionSchedulerService = NULL,
    protected ?object $matchingService = NULL,
    protected ?object $skillsService = NULL,
    protected ?object $fundingApplicationManager = NULL,
    protected ?object $sroiCalculator = NULL,
    protected ?object $cvBuilderService = NULL,
  ) {}

  /**
   * Crea o vincula el perfil de candidato del participante.
   *
   * Consume: jaraba_candidate.profile::createProfile($uid, $data)
   */
  public function ensureCandidateProfile(int $uid, array $data = []): ?int { ... }

  /**
   * Crea un BMC para participantes del carril Acelera.
   *
   * Consume: jaraba_business_tools.canvas::createCanvas($data)
   */
  public function createBusinessCanvas(array $data): ?int { ... }

  /**
   * Obtiene slots disponibles de mentores para el participante.
   *
   * Consume: jaraba_mentoring.session_scheduler::getAvailableSlots($mentor, $start, $end)
   */
  public function getAvailableMentorSlots(int $mentorProfileId, string $startDate, string $endDate): array { ... }

  /**
   * Busca matches empresa-candidato para el participante.
   *
   * Consume: jaraba_matching.matching::getTopCandidatesForJob($jobId, $limit, $tenantId)
   */
  public function findJobMatches(int $jobId, int $tenantId, int $limit = 10): array { ... }

  /**
   * Evalua competencias del participante.
   *
   * Consume: jaraba_candidate.skills::assessSkills($uid, $answers)
   */
  public function assessParticipantSkills(int $uid, array $answers): ?array { ... }

  /**
   * Obtiene perfil de competencias del participante.
   *
   * Consume: jaraba_candidate.skills::getSkillProfile($uid)
   */
  public function getParticipantSkillProfile(int $uid): ?array { ... }

  /**
   * Sugiere formacion basada en gaps de competencias.
   *
   * Consume: jaraba_candidate.skills::suggestTraining($uid, $gaps)
   */
  public function suggestTrainingForParticipant(int $uid, array $gaps): array { ... }

  /**
   * Genera CV profesional del participante.
   *
   * Consume: jaraba_candidate.cv_builder::generateCv($profile, $template, $format)
   */
  public function generateParticipantCv(int $uid, string $template = 'modern', string $format = 'pdf'): ?array { ... }

  /**
   * Calcula SROI del programa.
   *
   * Consume: jaraba_business_tools.sroi_calculator::calculateSroi($options)
   */
  public function calculateProgramSroi(int $tenantId, ?string $startDate = NULL, ?string $endDate = NULL): ?array { ... }

  /**
   * Obtiene estadisticas de solicitudes de financiacion para participantes emprendedores.
   *
   * Consume: jaraba_funding.application_manager::getDashboardStats()
   */
  public function getFundingStats(): ?array { ... }

}
```

**Registro en services.yml:**

```yaml
jaraba_andalucia_ei.cross_vertical_bridge:
  class: Drupal\jaraba_andalucia_ei\Service\CrossVerticalBridgeService
  arguments:
    - '@entity_type.manager'
    - '@logger.channel.jaraba_andalucia_ei'
    - '@?jaraba_candidate.profile'
    - '@?jaraba_business_tools.canvas'
    - '@?jaraba_mentoring.session_scheduler'
    - '@?jaraba_matching.matching'
    - '@?jaraba_candidate.skills'
    - '@?jaraba_funding.application_manager'
    - '@?jaraba_business_tools.sroi_calculator'
    - '@?jaraba_candidate.cv_builder'
```

### 4.2 jaraba_candidate: Perfil profesional del participante

**Momento de activacion:** Fase Acogida (al crear el participante)

**Flujo:**
1. Al transitar a fase `acogida`, `CrossVerticalBridgeService::ensureCandidateProfile()` crea un CandidateProfile vinculado al uid del participante
2. El `candidate_profile_id` se guarda en ProgramaParticipanteEi
3. Durante la fase `atencion`, el participante puede usar el CV Builder para preparar su CV profesional
4. El perfil de competencias (via `SkillsService`) alimenta las sugerencias de formacion adaptada
5. Durante `insercion`, el CV generado se usa para matching con empresas

**Metodos consumidos:**
- `CandidateProfileService::createProfile($uid, $data)` — crear perfil
- `CandidateProfileService::getProfileByUserId($uid)` — consultar perfil
- `CvBuilderService::generateCv($profile, 'modern', 'pdf')` — generar CV
- `SkillsService::assessSkills($uid, $answers)` — evaluar competencias
- `SkillsService::getSkillProfile($uid)` — consultar perfil de competencias
- `SkillsService::suggestTraining($uid, $gaps)` — sugerir formacion

### 4.3 jaraba_business_tools: BMC y validacion de negocio

**Momento de activacion:** Fase Diagnostico (solo carril Acelera, score DIME >= 10)

**Flujo:**
1. Si DIME score >= 10 (carril Acelera), `CrossVerticalBridgeService::createBusinessCanvas()` crea un BMC
2. El `canvas_id` se guarda en ProgramaParticipanteEi
3. Durante `atencion`, el participante itera sobre el BMC via las herramientas de `jaraba_business_tools`
4. El semaforo RED/YELLOW/GREEN del BMC se refleja en el dashboard del coordinador
5. El SROI se calcula como metrica de impacto del programa

**Metodos consumidos:**
- `CanvasService::createCanvas($data)` — crear BMC
- `CanvasService::getCanvasWithBlocks($id)` — consultar BMC con bloques
- `CanvasService::updateCompletenessScore($id)` — recalcular completitud
- `SroiCalculatorService::calculateSroi($options)` — calcular impacto social
- `SroiCalculatorService::generateImpactReport($options)` — informe para la Junta

### 4.4 jaraba_mentoring: Sesiones de mentoria humana

**Momento de activacion:** Fase Atencion (una vez asignado itinerario)

**Flujo:**
1. El orientador busca mentores compatibles via `MentorMatchingService::findMatches()`
2. Se consultan slots disponibles via `SessionSchedulerService::getAvailableSlots()`
3. Se reserva sesion via `SessionSchedulerService::bookSession()`
4. Al completar la sesion, `HumanMentorshipTracker` registra las horas
5. Se genera Hoja de Servicio via `HojaServicioMentoriaService` (ya existente)

**Metodos consumidos:**
- `MentorMatchingService::findMatches($criteria, $limit)` — buscar mentores
- `SessionSchedulerService::getAvailableSlots($mentor, $start, $end)` — consultar disponibilidad
- `SessionSchedulerService::bookSession($engagement, $datetime)` — reservar

### 4.5 jaraba_matching: Matching empresa-participante

**Momento de activacion:** Fase Insercion

**Flujo:**
1. El orientador registra ofertas/empresas via ProspeccionEmpresarial
2. Se ejecuta matching semantico (Qdrant) entre el perfil del candidato y las ofertas
3. El orientador recibe top-N candidatos ordenados por score
4. Se documenta la insercion si hay match exitoso

**Metodos consumidos:**
- `MatchingService::getTopCandidatesForJob($jobId, $limit, $tenantId)` — matching hibrido

### 4.6 jaraba_funding: Tracking de oportunidades de financiacion

**Momento de activacion:** Fase Insercion (carril Acelera, emprendedores)

**Flujo:**
1. Participantes del carril Acelera que avanzan a insercion por cuenta propia
2. El orientador ayuda a identificar oportunidades de financiacion
3. Se trackean solicitudes via `ApplicationManagerService`

**Metodos consumidos:**
- `ApplicationManagerService::getRecentApplications($limit)` — consultar solicitudes
- `ApplicationManagerService::getDashboardStats()` — estadisticas

### 4.7 jaraba_skills: Evaluacion de competencias IA

**Nota:** `jaraba_skills` gestiona AI Skills (capacidades de agentes IA), no competencias de candidatos humanos. Para competencias de participantes, se usa `jaraba_candidate.skills` (SkillsService).

Sin embargo, `jaraba_skills.skill_manager::resolveSkills()` se puede usar para enriquecer los prompts del Copilot con skills especificas del vertical andalucia_ei, lo cual ya se hace via `AndaluciaEiCopilotBridgeService`.

### 4.8 Tabla de servicios consumidos y metodos

| Servicio consumido | ID en services.yml | Metodo | Llamado desde | Fase |
|--------------------|-------------------|--------|--------------|------|
| `CandidateProfileService` | `@?jaraba_candidate.profile` | `createProfile($uid, $data)` | CrossVerticalBridgeService | Acogida |
| `CandidateProfileService` | `@?jaraba_candidate.profile` | `getProfileByUserId($uid)` | CrossVerticalBridgeService | Todas |
| `CvBuilderService` | `@?jaraba_candidate.cv_builder` | `generateCv($profile, $tpl, $fmt)` | CrossVerticalBridgeService | Insercion |
| `SkillsService` | `@?jaraba_candidate.skills` | `assessSkills($uid, $answers)` | CrossVerticalBridgeService | Acogida/Diagnostico |
| `SkillsService` | `@?jaraba_candidate.skills` | `getSkillProfile($uid)` | CrossVerticalBridgeService | Atencion |
| `SkillsService` | `@?jaraba_candidate.skills` | `suggestTraining($uid, $gaps)` | CrossVerticalBridgeService | Atencion |
| `CanvasService` | `@?jaraba_business_tools.canvas` | `createCanvas($data)` | CrossVerticalBridgeService | Diagnostico (Acelera) |
| `CanvasService` | `@?jaraba_business_tools.canvas` | `getCanvasWithBlocks($id)` | CrossVerticalBridgeService | Atencion (Acelera) |
| `SroiCalculatorService` | `@?jaraba_business_tools.sroi_calculator` | `calculateSroi($options)` | JustificacionEconomicaService | Seguimiento |
| `MentorMatchingService` | `@?jaraba_mentoring.mentor_matching` | `findMatches($criteria)` | CrossVerticalBridgeService | Atencion |
| `SessionSchedulerService` | `@?jaraba_mentoring.session_scheduler` | `getAvailableSlots(...)` | CrossVerticalBridgeService | Atencion |
| `SessionSchedulerService` | `@?jaraba_mentoring.session_scheduler` | `bookSession(...)` | CrossVerticalBridgeService | Atencion |
| `MatchingService` | `@?jaraba_matching.matching` | `getTopCandidatesForJob(...)` | CrossVerticalBridgeService | Insercion |
| `ApplicationManagerService` | `@?jaraba_funding.application_manager` | `getDashboardStats()` | CrossVerticalBridgeService | Insercion (Acelera) |

---

## 5. Acceso a Servicios SaaS (Programa 100% Fondos Publicos)

### 5.1 Configuracion de roles y permisos

Al ser un programa financiado al 100% con fondos publicos, todos los participantes y personal tecnico deben tener acceso completo a los servicios del SaaS sin restricciones de plan de pago.

**Roles a crear/configurar:**

| Rol | Machine name | Descripcion | Usuarios estimados |
|-----|-------------|-------------|-------------------|
| Participante +ei | `participante_ei` | Participante activo del programa Andalucia +ei | 45 |
| Coordinador/a +ei | `coordinador_ei` | Coordinador/a del programa | 1-2 |
| Orientador/a +ei | `orientador_ei` | Orientador/a laboral | 4-6 |
| Formador/a +ei | `formador_ei` | Formador/a de talleres/pildoras | 2-4 |

**Permisos nuevos a registrar en `jaraba_andalucia_ei.permissions.yml`:**

```yaml
# Actuaciones STO
administer actuacion_sto:
  title: 'Administrar actuaciones STO'
  restrict access: true
create actuacion_sto:
  title: 'Crear actuaciones STO'
view own actuacion_sto:
  title: 'Ver sus propias actuaciones'
view any actuacion_sto:
  title: 'Ver todas las actuaciones'
edit own actuacion_sto:
  title: 'Editar sus propias actuaciones'
edit any actuacion_sto:
  title: 'Editar todas las actuaciones'
delete any actuacion_sto:
  title: 'Eliminar actuaciones'
  restrict access: true

# Indicadores FSE+
create indicador_fse_plus:
  title: 'Crear indicadores FSE+'
view any indicador_fse_plus:
  title: 'Ver todos los indicadores FSE+'
edit any indicador_fse_plus:
  title: 'Editar indicadores FSE+'

# Insercion Laboral
create insercion_laboral:
  title: 'Crear registros de insercion laboral'
view any insercion_laboral:
  title: 'Ver todas las inserciones laborales'
edit any insercion_laboral:
  title: 'Editar inserciones laborales'

# Prospeccion Empresarial
create prospeccion_empresarial:
  title: 'Crear prospecciones empresariales'
view own prospeccion_empresarial:
  title: 'Ver sus propias prospecciones'
view any prospeccion_empresarial:
  title: 'Ver todas las prospecciones'
edit own prospeccion_empresarial:
  title: 'Editar sus propias prospecciones'

# Cross-vertical
access andalucia_ei_candidate_tools:
  title: 'Acceder a herramientas de candidatura (CV, competencias)'
access andalucia_ei_business_tools:
  title: 'Acceder a herramientas de emprendimiento (BMC, SROI)'
access andalucia_ei_mentoring:
  title: 'Acceder a mentoria del programa'
access andalucia_ei_matching:
  title: 'Acceder a matching empresa-candidato'

# Justificacion
view justificacion_economica:
  title: 'Ver justificacion economica del programa'
generate justificacion_report:
  title: 'Generar informes de justificacion'
```

### 5.2 Alta de participantes: flujo completo

Cuando una solicitud es admitida (`SolicitudEi.estado = 'admitido'`), el `AccesoProgramaService` ejecuta:

1. **Crear usuario Drupal** (si no existe): email, nombre, rol `participante_ei`
2. **Asignar al grupo** (tenant): via Group module membership
3. **Crear ProgramaParticipanteEi**: fase_actual = 'acogida', datos desde SolicitudEi
4. **Crear CandidateProfile** (cross-vertical): via `CrossVerticalBridgeService::ensureCandidateProfile()`
5. **Asignar permisos del programa:** `view own actuacion_sto`, `access andalucia_ei_candidate_tools`, `access andalucia_ei_mentoring`, `access andalucia_ei_business_tools` (si carril Acelera), `access andalucia_ei_matching`
6. **Enviar email de bienvenida** con credenciales y enlace al portal
7. **Generar DACI** para firma en primera sesion

```php
/**
 * Flujo automatizado de alta de participante en el programa.
 *
 * @param \Drupal\jaraba_andalucia_ei\Entity\SolicitudEiInterface $solicitud
 *   La solicitud admitida.
 *
 * @return \Drupal\jaraba_andalucia_ei\Entity\ProgramaParticipanteEiInterface
 *   El participante creado.
 */
public function altaParticipante(SolicitudEiInterface $solicitud): ProgramaParticipanteEiInterface {
  // 1. Crear/vincular usuario
  $user = $this->ensureUserAccount($solicitud);

  // 2. Asignar rol
  if (!$user->hasRole('participante_ei')) {
    $user->addRole('participante_ei');
    $user->save();
  }

  // 3. Asignar al grupo del programa
  $this->addToGroup($user, $solicitud->getTenantId());

  // 4. Crear entidad participante
  $participante = $this->createParticipante($solicitud, $user);

  // 5. Cross-vertical: crear perfil candidato
  if ($this->crossVerticalBridge) {
    try {
      $profileId = $this->crossVerticalBridge->ensureCandidateProfile(
        (int) $user->id(),
        $this->extractProfileData($solicitud),
      );
      if ($profileId) {
        $participante->set('candidate_profile_id', $profileId);
        $participante->save();
      }
    }
    catch (\Throwable $e) {
      $this->logger->warning('Could not create candidate profile: @msg', ['@msg' => $e->getMessage()]);
    }
  }

  // 6. Enviar bienvenida
  $this->sendWelcomeEmail($user, $participante);

  return $participante;
}
```

### 5.3 Alta de personal tecnico: coordinador, orientador, formador

El personal tecnico accede al SaaS con su propio usuario Drupal. Se configura manualmente por el administrador del programa:

| Paso | Accion | Responsable |
|------|--------|-------------|
| 1 | Crear usuario en `/admin/people/create` | Admin |
| 2 | Asignar rol (`coordinador_ei`, `orientador_ei`, o `formador_ei`) | Admin |
| 3 | Anadir al grupo del programa (membership) | Admin |
| 4 | Verificar acceso a dashboards correspondientes | Admin |

**Permisos por rol de personal tecnico:**

| Permiso | Coordinador | Orientador | Formador |
|---------|:-----------:|:----------:|:--------:|
| `administer actuacion_sto` | X | | |
| `create actuacion_sto` | X | X | X |
| `view any actuacion_sto` | X | X | |
| `view own actuacion_sto` | | | X |
| `edit any actuacion_sto` | X | | |
| `edit own actuacion_sto` | | X | X |
| `create indicador_fse_plus` | X | X | |
| `view any indicador_fse_plus` | X | | |
| `create insercion_laboral` | X | X | |
| `view any insercion_laboral` | X | X | |
| `edit any insercion_laboral` | X | | |
| `create prospeccion_empresarial` | X | X | |
| `view any prospeccion_empresarial` | X | X | |
| `edit own prospeccion_empresarial` | | X | |
| `view justificacion_economica` | X | | |
| `generate justificacion_report` | X | | |
| `access andalucia_ei_candidate_tools` | X | X | |
| `access andalucia_ei_business_tools` | X | X | |
| `access andalucia_ei_mentoring` | X | X | |
| `access andalucia_ei_matching` | X | X | |
| Acceso a Copilot v2 | X | X | |
| Acceso a dashboard coordinador | X | | |
| Acceso a dashboard orientador | | X | |

### 5.4 Mapa de acceso por rol a cada servicio SaaS

| Servicio SaaS | Participante | Coordinador | Orientador | Formador |
|--------------|:------------:|:-----------:|:----------:|:--------:|
| **Portal participante** (mi-participacion) | X | | | |
| **Dashboard coordinador** | | X | | |
| **Dashboard orientador** | | | X | |
| **Copilot IA** (7 modos) | X | X | X | |
| **Mentoria IA** (horas trackeadas) | X | | | |
| **Mentoria humana** (sesiones) | X (participar) | X (gestionar) | X (impartir) | |
| **CV Builder** (cross-vertical) | X | | | |
| **Competencias** (evaluacion) | X (realizar) | X (ver) | X (ver) | |
| **BMC** (solo Acelera) | X | X (ver) | X (ver) | |
| **Matching** empresa-candidato | | X | X | |
| **Expediente** documental | X (ver propio) | X (gestionar) | X (ver asignados) | |
| **Formacion** (pildoras, cursos) | X | X (gestionar) | | X (impartir) |
| **Calendario** 12 semanas | X (ver propio) | X (gestionar) | X (ver) | |
| **Guia participante** interactiva | X | | | |
| **Recibos de servicio** | X (firmar) | X (generar) | X (generar) | X (generar) |
| **Mensajeria** interna | X | X | X | X |
| **Financiacion** (solo Acelera) | X | X (ver) | X (ver) | |
| **SROI / Impacto** | | X | | |

### 5.5 Configuracion de tenant para el programa

El programa requiere un Group entity con la siguiente configuracion:

```
Group type: programa_piil
Group name: "Andalucia +ei - PIIL CV 2025"
Tenant: vinculado via TenantBridgeService
Plan: enterprise (acceso completo a todos los servicios)
Vertical primario: andalucia_ei
Addon verticals: empleabilidad, emprendimiento (via TenantVerticalService)
```

**Clave:** Al ser un programa 100% fondos publicos, el plan del tenant debe configurarse como `enterprise` o equivalente para desbloquear todas las funcionalidades sin limites de cuota (FairUsePolicyService debe tener bypass para este tenant).

---

## 6. Documentacion Justificativa Ampliada

### 6.1 Documentos obligatorios por momento del itinerario

La documentacion va mas alla de los campos de datos del STO. La Junta de Andalucia y el FSE+ exigen documentacion firmada que acredite cada actuacion:

| Momento | Documento | Generado por | Firma requerida | Template |
|---------|-----------|-------------|-----------------|----------|
| Acogida | DACI (Aceptacion de Compromisos) | DaciService | Participante | Branded PDF |
| Acogida | Ficha de alta con datos personales | AccesoProgramaService | Participante | Branded PDF |
| Acogida | Indicadores FSE+ entrada | IndicadorFseService | Coordinador | Formulario web → PDF |
| Cada sesion orientacion | Hoja de servicio de orientacion | ReciboServicioService | Orientador + Participante | Branded PDF |
| Cada sesion grupal | Hoja de asistencia grupal | ReciboServicioService | Orientador + Participantes | Branded PDF |
| Cada bloque formacion | Recibo de formacion | ReciboServicioService | Formador + Participante | Branded PDF |
| Antes de formacion | VoBo SAE (documento de aprobacion) | Upload externo | SAE | Documento escaneado |
| Trimestral | Informe de progreso | InformeProgresoPdfService | Coordinador | Branded PDF |
| Al insertar | Ficha de insercion laboral | InsercionLaboralService | Coordinador | Branded PDF |
| Al insertar | Copia contrato / alta SS / modelo 036 | Upload externo | — | Documento escaneado |
| Post-insercion | Indicadores FSE+ salida | IndicadorFseService | Coordinador | Formulario web → PDF |
| 6 meses post | Indicadores FSE+ seguimiento | IndicadorFseService | Coordinador | Formulario web → PDF |
| Trimestral + final | Justificacion economica | JustificacionEconomicaService | Coordinador | Branded PDF |

### 6.2 Documentos de justificacion economica

El `JustificacionEconomicaService` genera informes que calculan automaticamente:

**Modulo "persona atendida" (3.500 EUR/persona):**
- Listado de participantes que cumplen: >=10h orientacion (>=2h individual) + >=50h formacion + >=75% asistencia
- Para cada uno: desglose de actuaciones, horas acumuladas, recibos firmados
- Total certificable: N personas x 3.500 EUR
- Incentivo: N personas x 528 EUR (incluido en los 3.500 EUR)

**Modulo "persona insertada" (2.500 EUR/persona):**
- Listado de personas atendidas + >=40h orientacion para insercion + >=4 meses alta SS verificada
- Para cada uno: tipo insercion, datos empresa/autonomo, fecha alta SS, fecha verificacion 4 meses
- Total certificable: N personas x 2.500 EUR

**Informe trimestral:**
- Resumen de actividad del periodo
- N participantes activos por fase
- N actuaciones por tipo
- Horas acumuladas por tipo
- Incidencias y bajas
- Alertas normativas pendientes

**Informe final:**
- Resumen completo del programa
- Resultado por modulo economico
- Indicadores FSE+ agregados
- SROI calculado (via jaraba_business_tools)
- Recomendaciones

### 6.3 Documentos de evidencia para FSE+

La Comision Europea (via la Junta de Andalucia como Autoridad de Gestion) puede solicitar:

| Evidencia | Fuente | Como se genera |
|-----------|--------|----------------|
| Base de datos anonimizada de participantes | ProgramaParticipanteEi | Export CSV con datos anonimizados (AI-GUARDRAILS-PII-001) |
| Indicadores agregados de entrada | IndicadorFsePlus (momento=entrada) | Informe estadistico por JustificacionEconomicaService |
| Indicadores agregados de salida | IndicadorFsePlus (momento=salida) | Informe estadistico |
| Indicadores de resultado a 6 meses | IndicadorFsePlus (momento=seguimiento_6m) | Informe estadistico |
| Desglose de gastos por partida | JustificacionEconomicaService | PDF con desglose |
| Muestra de expedientes individuales | ExpedienteDocumento | Export per-participant |

### 6.4 Mapeo: documento -> entidad -> servicio generador -> template

| Documento | Entidad fuente | Servicio generador | Categoria expediente | Template Twig/PDF |
|-----------|---------------|-------------------|---------------------|-------------------|
| DACI | ProgramaParticipanteEi | DaciService | sto_daci | branded-pdf-daci.html.twig |
| Recibo orientacion individual | ActuacionSto (tipo=orient_ind) | ReciboServicioService | recibo_orientacion | branded-pdf-recibo.html.twig |
| Recibo orientacion grupal | ActuacionSto (tipo=orient_grup) | ReciboServicioService | recibo_grupal | branded-pdf-recibo-grupal.html.twig |
| Recibo formacion | ActuacionSto (tipo=formacion) | ReciboServicioService | recibo_formacion | branded-pdf-recibo.html.twig |
| Hoja servicio mentoria | MentoringSession | HojaServicioMentoriaService | mentoria_hoja | branded-pdf-hoja-mentoria.html.twig |
| Informe progreso | ProgramaParticipanteEi | InformeProgresoPdfService | programa_informe | branded-pdf-informe.html.twig |
| Ficha insercion | InsercionLaboral | InsercionLaboralService | insercion_ficha | branded-pdf-insercion.html.twig |
| Indicadores FSE+ (entrada) | IndicadorFsePlus | IndicadorFseService | indicadores_fse_entrada | branded-pdf-fse.html.twig |
| Indicadores FSE+ (salida) | IndicadorFsePlus | IndicadorFseService | indicadores_fse_salida | branded-pdf-fse.html.twig |
| Indicadores FSE+ (6m) | IndicadorFsePlus | IndicadorFseService | indicadores_fse_6m | branded-pdf-fse.html.twig |
| Justificacion trimestral | Multiple | JustificacionEconomicaService | justificacion_economica | branded-pdf-justificacion.html.twig |
| VoBo SAE | Upload externo | — (upload manual) | vobo_sae | — |

---

## 7. Fases de Implementacion

### 7.1 Sprint 1 — Cumplimiento Normativo P0 (Fases 1-7)

**Objetivo:** Asegurar la justificacion de los 202.500 EUR de subvencion.
**Estimacion:** 50-65 horas

#### 7.1.1 Fase 1: Fases PIIL completas y colectivos corregidos

**GAPs cubiertos:** GAP-PIIL-01, GAP-PIIL-07

**Problema detallado:**
- `fase_actual` tiene 3 valores (atencion, insercion, baja) pero la normativa exige 6 (acogida, diagnostico, atencion, insercion, seguimiento, baja)
- `colectivo` incluye `jovenes` (no aplica a esta edicion) y le faltan `migrantes` y `perceptores_prestaciones`
- `FaseTransitionManager` solo gestiona 3 transiciones

**Solucion tecnica:**

1. **Modificar `baseFieldDefinitions()`** en ProgramaParticipanteEi:
```php
// fase_actual: ampliar a 6 valores
$fields['fase_actual'] = BaseFieldDefinition::create('list_string')
  ->setLabel(t('Fase actual'))
  ->setSetting('allowed_values', [
    'acogida' => 'Acogida',
    'diagnostico' => 'Diagnóstico',
    'atencion' => 'Atención',
    'insercion' => 'Inserción',
    'seguimiento' => 'Seguimiento',
    'baja' => 'Baja',
  ])
  ->setDefaultValue('acogida')
  // ...

// colectivo: corregir valores
$fields['colectivo'] = BaseFieldDefinition::create('list_string')
  ->setLabel(t('Colectivo'))
  ->setSetting('allowed_values', [
    'larga_duracion' => 'Larga duración (>12 meses)',
    'mayores_45' => 'Mayores de 45 años',
    'migrantes' => 'Personas migrantes',
    'perceptores_prestaciones' => 'Perceptores de prestaciones',
  ])
  // ...
```

2. **Crear `hook_update_N()`** en `jaraba_andalucia_ei.install`:
```php
/**
 * Ampliar fases del programa a 6 y corregir colectivos.
 */
function jaraba_andalucia_ei_update_10XXX(): string {
  try {
    $manager = \Drupal::entityDefinitionUpdateManager();
    $entity_type = \Drupal::entityTypeManager()
      ->getDefinition('programa_participante_ei');
    $field_definitions = \Drupal::service('entity_field.manager')
      ->getFieldStorageDefinitions('programa_participante_ei');
    $manager->updateFieldableEntityType($entity_type, $field_definitions);
    return 'Fases ampliadas a 6 y colectivos corregidos.';
  }
  catch (\Throwable $e) {
    return 'Error: ' . $e->getMessage();
  }
}
```

3. **Ampliar `FaseTransitionManager`** con mapa completo:
```php
private const TRANSITIONS = [
  'acogida' => ['diagnostico', 'baja'],
  'diagnostico' => ['atencion', 'baja'],
  'atencion' => ['insercion', 'baja'],
  'insercion' => ['seguimiento', 'baja'],
  'seguimiento' => ['baja'],
  'baja' => [],
];

private const REQUIREMENTS = [
  'diagnostico' => ['daci_firmado', 'fse_entrada_completado'],
  'atencion' => ['carril_not_empty', 'dime_score_not_null'],
  'insercion' => ['es_persona_atendida'],
  'seguimiento' => ['insercion_verificada'],
];
```

**Ficheros modificados:**
- `src/Entity/ProgramaParticipanteEi.php` — baseFieldDefinitions + nuevos campos
- `jaraba_andalucia_ei.install` — hook_update_N()
- `src/Service/FaseTransitionManager.php` — 6 fases + requisitos
- `tests/src/Unit/Service/FaseTransitionManagerTest.php` — tests ampliados

**Directrices aplicadas:** UPDATE-HOOK-REQUIRED-001, UPDATE-HOOK-CATCH-001, UPDATE-HOOK-FIELDABLE-001, UPDATE-FIELD-DEF-001, DRUPAL11-001

**Verificacion:**
- `drush updatedb` sin errores
- `drush entity:updates` limpio
- Tests FaseTransitionManager pasan con 6 fases

#### 7.1.2 Fase 2: Entidad ActuacionSto — tracking granular

**GAP cubierto:** GAP-PIIL-02

**Problema detallado:**
El SaaS solo tiene contadores decimales (horas_orientacion_ind, horas_orientacion_grup, horas_formacion) en ProgramaParticipanteEi. No hay registro individual de cada actuacion con fecha, hora, contenido, resultado y lugar. Sin esto, no se puede generar la justificacion detallada que exige la Junta ni exportar datos correctos al STO.

**Solucion tecnica:**

La entidad `actuacion_sto` ya existe pero necesita verificacion y ampliacion de campos. Hay que:

1. Verificar que los campos listados en la seccion 3.2.1 estan todos presentes en `baseFieldDefinitions()`
2. Implementar la logica de `preSave()` para calculo automatico de duracion y actualizacion de contadores
3. Crear `ActuacionStoForm` extendiendo `PremiumEntityFormBase` (PREMIUM-FORMS-PATTERN-001)
4. Configurar AccessControlHandler con tenant isolation (TENANT-ISOLATION-ACCESS-001)
5. Crear template, preprocess, Views data, Field UI settings tab
6. Rutas API para CRUD desde slide-panel

**Ficheros nuevos/modificados:**
- `src/Entity/ActuacionSto.php` — verificar/ampliar campos
- `src/Form/ActuacionStoForm.php` — PremiumEntityFormBase
- `src/ActuacionStoAccessControlHandler.php` — tenant + permisos
- `src/Service/ActuacionStoService.php` — ampliar con recalcularHoras()
- `src/Controller/ActuacionStoApiController.php` — API REST
- `templates/actuacion-sto-entity.html.twig` — template de vista
- `jaraba_andalucia_ei.module` — template_preprocess_actuacion_sto()
- `jaraba_andalucia_ei.routing.yml` — rutas API y admin
- `jaraba_andalucia_ei.install` — hook_update_N() para campos nuevos
- `tests/src/Unit/Service/ActuacionStoServiceTest.php`
- `tests/src/Kernel/Entity/ActuacionStoTest.php`

**Directrices aplicadas:** PREMIUM-FORMS-PATTERN-001, ENTITY-PREPROCESS-001, ENTITY-FK-001, ENTITY-001, AUDIT-CONS-001, TENANT-001, TENANT-ISOLATION-ACCESS-001, SLIDE-PANEL-RENDER-001, CSRF-API-001, API-WHITELIST-001, FIELD-UI-SETTINGS-TAB-001, ACCESS-STRICT-001, PRESAVE-RESILIENCE-001

#### 7.1.3 Fase 3: Indicadores FSE+

**GAP cubierto:** GAP-PIIL-03

**Problema detallado:**
El FSE+ (85% de la financiacion = 172.125 EUR) exige recogida de indicadores sociodemograficos en 3 momentos. Sin esta entidad, la Comision Europea puede rechazar la certificacion de todo el gasto FSE+.

**Solucion tecnica:**

1. Crear entidad `IndicadorFsePlus` con esquema de la seccion 3.2.2
2. Formulario condicional: campos visibles dependen del `momento` (entrada/salida/6m)
3. Validacion: unicidad por participante+momento
4. Al guardar, actualizar flags en ProgramaParticipanteEi (fse_entrada_completado, fse_salida_completado)
5. Servicio `IndicadorFseService` con metodos: crear, obtener por participante, generar PDF exportable, agregar estadisticas

**Ficheros nuevos:**
- `src/Entity/IndicadorFsePlus.php`
- `src/Form/IndicadorFsePlusForm.php`
- `src/IndicadorFsePlusAccessControlHandler.php`
- `src/Service/IndicadorFseService.php`
- `src/Controller/IndicadorFseApiController.php`
- `templates/indicador-fse-plus-entity.html.twig`
- `tests/src/Unit/Service/IndicadorFseServiceTest.php`
- `tests/src/Kernel/Entity/IndicadorFsePlusTest.php`

#### 7.1.4 Fase 4: Insercion laboral detallada

**GAP cubierto:** GAP-PIIL-05

**Problema detallado:**
La insercion laboral solo tiene `tipo_insercion` y `fecha_insercion` en ProgramaParticipanteEi. La normativa exige datos completos diferenciados por tipo (cuenta ajena: CIF, contrato, SS; cuenta propia: RETA, modelo 036; agrario: campana, cultivo). Sin estos datos, los 45.000 EUR del modulo insercion (18 personas x 2.500 EUR) no son justificables.

**Solucion tecnica:**

1. Crear entidad `InsercionLaboral` con esquema de la seccion 3.2.3
2. Formulario dinamico: campos visibles dependen del `tipo` (cuenta_ajena/propia/agrario)
3. Al guardar con `documentacion_verificada = TRUE`, actualizar ProgramaParticipanteEi
4. Servicio `InsercionLaboralService` con metodos: crear, verificar, generar ficha PDF, calcular si cumple "persona insertada"

**Ficheros nuevos:**
- `src/Entity/InsercionLaboral.php`
- `src/Form/InsercionLaboralForm.php`
- `src/InsercionLaboralAccessControlHandler.php`
- `src/Service/InsercionLaboralService.php`
- `src/Controller/InsercionLaboralApiController.php`
- `templates/insercion-laboral-entity.html.twig`
- `tests/src/Unit/Service/InsercionLaboralServiceTest.php`

#### 7.1.5 Fase 5: DACI digital y flujo de acogida

**GAP cubierto:** GAP-PIIL-06

**Problema detallado:**
El DACI es obligatorio el primer dia. Informa al participante de derechos, obligaciones y compromisos del programa y debe firmarse digitalmente. El servicio `DaciService` ya existe pero necesita integracion con el flujo de acogida y la firma digital.

**Solucion tecnica:**

1. Verificar que `DaciService` genera el PDF correcto con los contenidos PIIL
2. Integrar con `FirmaDigitalService` de ecosistema_jaraba_core
3. Al firmar, actualizar `ProgramaParticipanteEi::daci_firmado = TRUE`
4. Guardar el documento firmado en ExpedienteDocumento (categoria: sto_daci)
5. Incluir en el flujo de acogida del `AccesoProgramaService`

**Ficheros modificados:**
- `src/Service/DaciService.php` — verificar contenido PIIL
- `src/Service/AccesoProgramaService.php` — integrar DACI en flujo de alta
- `templates/branded-pdf-daci.html.twig` — template del PDF

#### 7.1.6 Fase 6: Recibo de servicio universal

**GAP cubierto:** GAP-PIIL-13

**Problema detallado:**
El recibo de servicio solo existe para mentoria (HojaServicioMentoriaService). La normativa exige un recibo firmado por cada actuacion/dia con datos del participante, DNI, expediente STO, tipo actuacion, descripcion, fecha, hora inicio/fin, firma participante, firma orientador/formador, codigo proyecto.

**Solucion tecnica:**

1. El `ReciboServicioService` ya existe. Verificar que cubre todos los tipos de actuacion
2. Generalizarlo para generar recibos de: orientacion individual, orientacion grupal, formacion
3. Integrar con `ActuacionStoService`: al crear una actuacion, generar recibo automaticamente
4. Firma dual via `FirmaDigitalService`
5. Guardar en ExpedienteDocumento con categoria apropiada

**Ficheros modificados:**
- `src/Service/ReciboServicioService.php` — generalizar para todos los tipos
- `src/Service/ActuacionStoService.php` — integrar generacion de recibo
- `templates/branded-pdf-recibo.html.twig` — template universal
- `templates/branded-pdf-recibo-grupal.html.twig` — template para sesiones grupales

#### 7.1.7 Fase 7: Formacion con VoBo SAE

**GAP cubierto:** GAP-PIIL-04

**Problema detallado:**
Toda accion formativa debe ser aprobada previamente por el SAE (Servicio Andaluz de Empleo). El STO registra el VoBo como campo obligatorio. Sin VoBo, las acciones formativas no son validas ni justificables.

**Solucion tecnica:**

1. Campos `vobo_sae_status`, `vobo_sae_fecha`, `vobo_sae_documento_id` en ActuacionSto (ya incluidos en esquema Fase 2)
2. Flujo: coordinador sube documento VoBo → se guarda en ExpedienteDocumento (categoria: vobo_sae) → se vincula a la actuacion formativa
3. Validacion: no se puede marcar como "completada" una actuacion de tipo formacion sin VoBo aprobado
4. AlertasNormativasService: alerta cuando hay formaciones pendientes de VoBo

**Ficheros modificados:**
- `src/Service/ActuacionStoService.php` — validacion VoBo
- `src/Service/AlertasNormativasService.php` — alerta VoBo pendiente (ya existente, verificar)
- `src/Form/ActuacionStoForm.php` — campo de upload VoBo

### 7.2 Sprint 2 — Funcionalidad Operativa P1 + Cross-Vertical (Fases 8-11)

**Objetivo:** Programa operativo con integracion de verticales.
**Estimacion:** 35-45 horas

#### 7.2.1 Fase 8: Calendario 12 semanas + DIME automatico

**GAPs cubiertos:** GAP-PIIL-08, GAP-PIIL-09

**Problema detallado:**
- El programa tiene una estructura de 12 semanas con 5 fases metodologicas (Mentalidad, Validacion, Viabilidad, Ventas, Cierre), 20 pildoras formativas y 44 experimentos catalogados. No hay entidad que mapee esta estructura
- El DIME existe en copilot v2 pero el score no se sincroniza automaticamente con ProgramaParticipanteEi ni asigna carril

**Solucion tecnica:**

1. Crear entidad `CalendarioSemanalEi` con esquema de la seccion 3.2.5
2. Servicio `CalendarioEiService` que:
   - Al transitar a fase `atencion`, genera automaticamente 12 registros CalendarioSemanalEi
   - Asigna pildoras segun el calendario del Manual Operativo V2.1
   - Asigna experimentos segun carril (Impulso/Acelera tienen selecciones diferentes)
3. Integrar DIME: cuando el Copilot v2 completa un diagnostico DIME, un evento dispara la actualizacion de `dime_score` y `carril` en ProgramaParticipanteEi

**Mapeo de semanas (del Manual Operativo V2.1):**

| Semana | Fase metodologica | Pildora | Experimentos tipo |
|--------|------------------|---------|-------------------|
| 1 | Mentalidad | P01: Autoconocimiento | D-01, D-02, D-03 |
| 2 | Mentalidad | P02: Mentalidad emprendedora | D-04, D-05 |
| 3 | Validacion | P03-P04: Propuesta de valor | I-01, I-02, I-03 |
| 4 | Validacion | P05-P06: Segmento cliente | I-04, I-05 |
| 5 | Validacion | P07-P08: Canales + Relaciones | I-06, I-07 |
| 6 | Viabilidad | P09-P10: Fuentes ingreso | P-01, P-02 |
| 7 | Viabilidad | P11-P12: Estructura costes | P-03, P-04 |
| 8 | Viabilidad | P13-P14: Recursos + Actividades | P-05 |
| 9 | Ventas | P15-P16: Ventas + Marketing | C-01, C-02 |
| 10 | Ventas | P17-P18: Pitch + Negociacion | C-03, C-04 |
| 11 | Cierre | P19: Plan de accion | C-05 |
| 12 | Cierre | P20: Presentacion final | — |

**Ficheros nuevos:**
- `src/Entity/CalendarioSemanalEi.php`
- `src/Form/CalendarioSemanalEiForm.php`
- `src/CalendarioSemanalEiAccessControlHandler.php`
- `src/Service/CalendarioEiService.php`
- `templates/_participante-calendario.html.twig` — parcial para el portal
- `tests/src/Unit/Service/CalendarioEiServiceTest.php`

#### 7.2.2 Fase 9: Prospeccion empresarial y alertas normativas ampliadas

**GAPs cubiertos:** GAP-PIIL-11, GAP-PIIL-12

**Solucion tecnica:**

1. Crear entidad `ProspeccionEmpresarial` con esquema de la seccion 3.2.4
2. Ampliar `AlertasNormativasService` con alertas de:
   - Plazo 30 dias comunicacion inicio programa
   - Informes trimestrales pendientes
   - Justificacion parcial/final pendiente
   - Participantes sin actividad en >2 semanas
   - Formaciones sin VoBo SAE proximo a fecha
   - Inserciones pendientes de verificacion 4 meses

**Ficheros nuevos:**
- `src/Entity/ProspeccionEmpresarial.php`
- `src/Form/ProspeccionEmpresarialForm.php`
- `src/ProspeccionEmpresarialAccessControlHandler.php`
- `src/Service/ProspeccionService.php`
- `src/Controller/ProspeccionApiController.php`

**Ficheros modificados:**
- `src/Service/AlertasNormativasService.php` — ampliar alertas

#### 7.2.3 Fase 10: Integracion BMC, Copilot contextual por fase y gamificacion Puntos de Impacto

**GAPs cubiertos:** GAP-PIIL-10, GAP-PIIL-14, GAP-PIIL-17

**Problema detallado:**
Tres funcionalidades de diferenciacion estan desconectadas: (1) el BMC de `jaraba_business_tools` no se refleja en los dashboards del programa, (2) los 7 modos del Copilot IA estan disponibles en todas las fases sin restriccion pedagogica, y (3) el sistema de Puntos de Impacto (gamificacion) existe en el ecosistema pero no esta configurado para andalucia_ei. Estas tres funcionalidades son las que convierten un PIIL convencional en una experiencia de clase mundial.

**Solucion tecnica detallada:**

##### 10.A — Dashboard BMC integrado en coordinador/orientador

El coordinador y orientador ven el estado del BMC de cada participante del carril Acelera directamente en sus dashboards, sin necesidad de navegar a `jaraba_business_tools`:

```php
// En CoordinadorDashboardController::dashboard()
// Cross-vertical: BMC status per participant (Acelera only)
$bmcStatuses = [];
if ($this->crossVerticalBridge) {
  foreach ($participantes as $p) {
    $canvasId = $p->get('canvas_id')->value;
    if ($canvasId) {
      try {
        $bmcData = $this->crossVerticalBridge->getBusinessCanvasStatus((int) $canvasId);
        $bmcStatuses[(int) $p->id()] = $bmcData;
      }
      catch (\Throwable $e) {
        $this->logger->warning('BMC status error for participant @pid: @msg', [
          '@pid' => $p->id(),
          '@msg' => $e->getMessage(),
        ]);
      }
    }
  }
}
```

El widget muestra por cada participante Acelera:
- **Completitud BMC:** Barra de progreso 0-100% con semaforo (rojo <30%, amarillo 30-70%, verde >70%)
- **Bloques validados:** 9 iconos (uno por bloque BMC) en estado rojo/amarillo/verde
- **Hipotesis activas:** Numero de hipotesis en validacion via `Hypothesis` entity de copilot_v2
- **Experimentos completados:** N de 44 catalogados
- **Enlace directo:** Click abre el BMC completo en slide-panel via `jaraba_business_tools`

Template parcial `_coordinador-bmc-widget.html.twig`:
```twig
{% if bmc_statuses is not empty %}
<section class="bmc-overview" aria-labelledby="bmc-heading">
  <h3 id="bmc-heading">{% trans %}Validación de Modelo de Negocio — Carril Acelera{% endtrans %}</h3>
  <div class="bmc-grid">
    {% for pid, bmc in bmc_statuses %}
      {% include '@jaraba_andalucia_ei/_bmc-participant-card.html.twig' with {
        participante_nombre: bmc.nombre,
        completitud: bmc.completeness_score,
        bloques: bmc.blocks,
        hipotesis_activas: bmc.hipotesis_count,
        experimentos_completados: bmc.experimentos_done,
      } only %}
    {% endfor %}
  </div>
</section>
{% endif %}
```

##### 10.B — Copilot IA contextual por fase (7 modos con restriccion pedagogica)

Los 7 modos del Copilot IA se restringen segun la fase del programa para maximizar el valor pedagogico y evitar que el participante acceda a herramientas que no corresponden a su momento del itinerario:

| Fase | Modos disponibles | Justificacion pedagogica |
|------|------------------|--------------------------|
| **Acogida** | Coach Emocional | Acompanamiento en la transicion inicial, gestion de expectativas, motivacion |
| **Diagnostico** | Coach Emocional, Consultor Tactico | Apoyo emocional + ayuda para completar DIME y definir carril |
| **Atencion (Impulso)** | Coach Emocional, Consultor Tactico, Experto Seg. Social, Experto Tributario | Orientacion laboral + conocimiento de derechos laborales y fiscales |
| **Atencion (Acelera)** | Los 7 modos | Emprendedores necesitan todo: coach, tactico, sparring, CFO, abogado diablo, tributario, seg social |
| **Insercion** | Consultor Tactico, Sparring Partner, CFO Sintetico, Experto Seg. Social | Apoyo en negociacion, plan financiero, alta autonomo, derechos laborales |
| **Seguimiento** | Coach Emocional, Consultor Tactico | Acompanamiento post-insercion, resolucion de problemas iniciales |

Implementacion en `AndaluciaEiCopilotContextProvider`:

```php
/**
 * Obtiene los modos del Copilot permitidos segun la fase del participante.
 *
 * GAP-PIIL-17: Los modos se restringen por fase para maximizar valor pedagogico.
 *
 * @param string $fase
 *   Fase actual del participante.
 * @param string $carril
 *   Carril asignado (impulso/acelera).
 *
 * @return array<string>
 *   Lista de IDs de modos permitidos.
 */
public function getModosPermitidosPorFase(string $fase, string $carril = ''): array {
  $modos = match ($fase) {
    'acogida' => ['coach_emocional'],
    'diagnostico' => ['coach_emocional', 'consultor_tactico'],
    'atencion' => $carril === 'acelera_pro'
      ? ['coach_emocional', 'consultor_tactico', 'sparring_partner', 'cfo_sintetico', 'abogado_diablo', 'experto_tributario', 'experto_seg_social']
      : ['coach_emocional', 'consultor_tactico', 'experto_seg_social', 'experto_tributario'],
    'insercion' => ['consultor_tactico', 'sparring_partner', 'cfo_sintetico', 'experto_seg_social'],
    'seguimiento' => ['coach_emocional', 'consultor_tactico'],
    default => ['coach_emocional'],
  };

  return $modos;
}
```

El contexto se inyecta en el prompt del Copilot para que la IA conozca la fase y adapte su comportamiento:

```php
public function getContext(): array {
  $participante = $this->getParticipante();
  if (!$participante) {
    return [];
  }

  $fase = $participante->getFaseActual();
  $carril = $participante->get('carril')->value ?? '';
  $semana = (int) ($participante->get('semana_actual')->value ?? 0);

  return [
    'programa' => 'Andalucía +ei (PIIL CV 2025)',
    'fase_actual' => $fase,
    'carril' => $carril,
    'semana_programa' => $semana,
    'modos_permitidos' => $this->getModosPermitidosPorFase($fase, $carril),
    'horas_orientacion' => $participante->getTotalHorasOrientacion(),
    'horas_formacion' => (float) ($participante->get('horas_formacion')->value ?? 0),
    'es_persona_atendida' => (bool) ($participante->get('es_persona_atendida')->value ?? FALSE),
    'instrucciones_fase' => $this->getInstruccionesFase($fase, $carril, $semana),
  ];
}

/**
 * Genera instrucciones contextuales para el Copilot segun la fase.
 */
private function getInstruccionesFase(string $fase, string $carril, int $semana): string {
  return match ($fase) {
    'acogida' => 'El participante acaba de incorporarse. Prioriza la bienvenida, la explicacion del programa y la resolucion de dudas. Ayuda a completar la documentacion inicial (DACI, datos FSE+). Tono: calido, acogedor, sin jerga tecnica.',
    'diagnostico' => 'El participante esta en fase de autoconocimiento. Ayuda con el diagnostico DIME (10 preguntas: Digital, Idea, Mercado, Emocional). No adelantes contenido de fases posteriores. Tono: explorador, reflexivo.',
    'atencion' => $carril === 'acelera_pro'
      ? sprintf('Semana %d de 12. Carril ACELERA (emprendimiento). Acompana en la validacion del modelo de negocio (BMC), las pildoras formativas y los experimentos de validacion. Reta constructivamente las hipotesis.', $semana)
      : sprintf('Semana %d de 12. Carril IMPULSO (empleabilidad). Acompana en el desarrollo de competencias, la busqueda activa de empleo y la preparacion de CV/entrevistas. Refuerza fortalezas.', $semana),
    'insercion' => 'El participante esta buscando activamente su insercion. Ayuda con: negociacion salarial, derechos laborales, proceso de alta como autonomo, plan financiero. Tono: practico, orientado a resultados.',
    'seguimiento' => 'El participante ya esta insertado. Acompana en la adaptacion al nuevo entorno laboral/emprendedor. Previene la recaida. Tono: refuerzo positivo, solucion de problemas.',
    default => 'Acompanamiento general del programa Andalucia +ei.',
  };
}
```

##### 10.C — Gamificacion: Sistema de Puntos de Impacto (Pi) para andalucia_ei

Los Puntos de Impacto (Pi) son el sistema de gamificacion transversal del ecosistema Jaraba. Para andalucia_ei, se configuran hitos especificos que premian el avance en el itinerario:

**Tabla de hitos y puntos:**

| Hito | Pi | Fase | Trigger automatico |
|------|:--:|------|-------------------|
| Firma del DACI | 50 | Acogida | `daci_firmado = TRUE` |
| Indicadores FSE+ entrada completados | 30 | Acogida | `fse_entrada_completado = TRUE` |
| Diagnostico DIME completado | 40 | Diagnostico | `dime_score IS NOT NULL` |
| Carril asignado | 20 | Diagnostico | `carril IS NOT EMPTY` |
| Primera sesion de orientacion individual | 30 | Atencion | Primera ActuacionSto tipo=orient_ind |
| Primera pildora formativa completada | 20 | Atencion | CalendarioSemanalEi.pildora_completada |
| 5h de orientacion alcanzadas | 40 | Atencion | horas_orientacion >= 5 |
| Primera sesion de mentoria IA | 25 | Atencion | horas_mentoria_ia > 0 |
| Primera sesion de mentoria humana | 30 | Atencion | horas_mentoria_humana > 0 |
| Primer experimento completado (Acelera) | 30 | Atencion | experimentos_completados.length >= 1 |
| BMC completitud > 50% (Acelera) | 50 | Atencion | canvas_completeness > 50 |
| 25h de formacion alcanzadas | 40 | Atencion | horas_formacion >= 25 |
| 10h de orientacion alcanzadas | 50 | Atencion | horas_orientacion >= 10 |
| 50h de formacion completadas | 60 | Atencion | horas_formacion >= 50 |
| ≥75% asistencia (umbral "persona atendida") | 100 | Atencion | asistencia_porcentaje >= 75 |
| **Persona atendida certificada** | **200** | Atencion | `es_persona_atendida = TRUE` |
| Primera prospeccion empresarial | 30 | Insercion | ProspeccionEmpresarial creada |
| CV profesional generado | 25 | Insercion | CvBuilderService usado |
| Insercion laboral documentada | 150 | Insercion | InsercionLaboral creada |
| 4 meses mantenimiento verificado | 100 | Seguimiento | `mantenimiento_4m = TRUE` |
| **Persona insertada certificada** | **300** | Seguimiento | `es_persona_insertada = TRUE` |
| FSE+ salida completados | 30 | Seguimiento | `fse_salida_completado = TRUE` |
| 12 pildoras completadas | 80 | Atencion | 12 CalendarioSemanalEi con pildora_completada |
| Todas las pildoras (20) completadas | 150 | Atencion | 20 pildoras completadas |
| **Total maximo (carril Impulso):** | **~1.430** | | |
| **Total maximo (carril Acelera):** | **~1.540** | | — BMC + experimentos extra |

**Implementacion tecnica:**

El sistema de Pi usa un servicio de gamificacion existente en el ecosistema (`ecosistema_jaraba_core` o un modulo dedicado). Si no existe, se crea un `PuntosImpactoService` ligero:

```php
/**
 * Registra puntos de impacto para hitos del programa andalucia_ei.
 *
 * Cada hito se otorga una sola vez. Los puntos se acumulan.
 * Se integra con el perfil del participante y se muestra en el portal.
 */
class PuntosImpactoEiService {

  private const HITOS = [
    'daci_firmado' => ['pi' => 50, 'label' => 'Firma del Acuerdo de Participación'],
    'fse_entrada' => ['pi' => 30, 'label' => 'Indicadores FSE+ de entrada completados'],
    'dime_completado' => ['pi' => 40, 'label' => 'Diagnóstico DIME completado'],
    'carril_asignado' => ['pi' => 20, 'label' => 'Carril de itinerario asignado'],
    'primera_orientacion' => ['pi' => 30, 'label' => 'Primera sesión de orientación'],
    'primera_pildora' => ['pi' => 20, 'label' => 'Primera píldora formativa completada'],
    'orientacion_5h' => ['pi' => 40, 'label' => '5 horas de orientación alcanzadas'],
    'primera_mentoria_ia' => ['pi' => 25, 'label' => 'Primera sesión de mentoría IA'],
    'primera_mentoria_humana' => ['pi' => 30, 'label' => 'Primera sesión de mentoría humana'],
    'primer_experimento' => ['pi' => 30, 'label' => 'Primer experimento completado'],
    'bmc_50' => ['pi' => 50, 'label' => 'BMC completitud > 50%'],
    'formacion_25h' => ['pi' => 40, 'label' => '25 horas de formación alcanzadas'],
    'orientacion_10h' => ['pi' => 50, 'label' => '10 horas de orientación completadas'],
    'formacion_50h' => ['pi' => 60, 'label' => '50 horas de formación completadas'],
    'asistencia_75' => ['pi' => 100, 'label' => 'Asistencia ≥75%'],
    'persona_atendida' => ['pi' => 200, 'label' => '🏆 Persona Atendida certificada'],
    'primera_prospeccion' => ['pi' => 30, 'label' => 'Primera prospección empresarial'],
    'cv_generado' => ['pi' => 25, 'label' => 'CV profesional generado'],
    'insercion_documentada' => ['pi' => 150, 'label' => 'Inserción laboral documentada'],
    'mantenimiento_4m' => ['pi' => 100, 'label' => '4 meses de mantenimiento verificados'],
    'persona_insertada' => ['pi' => 300, 'label' => '🏆 Persona Insertada certificada'],
    'fse_salida' => ['pi' => 30, 'label' => 'Indicadores FSE+ de salida completados'],
    '12_pildoras' => ['pi' => 80, 'label' => '12 píldoras formativas completadas'],
    '20_pildoras' => ['pi' => 150, 'label' => 'Todas las píldoras completadas'],
  ];

  /**
   * Otorga puntos por un hito si no se ha otorgado antes.
   */
  public function otorgarHito(int $participanteId, string $hitoId): bool { ... }

  /**
   * Obtiene el total de Pi y los hitos alcanzados.
   */
  public function getResumen(int $participanteId): array { ... }

  /**
   * Obtiene el ranking de participantes por Pi (anonimizado para cada participante).
   */
  public function getRanking(int $tenantId, int $limit = 10): array { ... }
}
```

**Widget en portal participante** — `_participante-logros-pi.html.twig`:
- Barra de progreso circular con Pi totales / maximo posible
- Lista de hitos alcanzados con icono verde y fecha
- Proximos hitos pendientes con barra de progreso parcial
- Posicion en ranking anonimizado ("Estas en el top 20% del programa")

**Ficheros nuevos:**
- `src/Service/PuntosImpactoEiService.php`
- `templates/_participante-logros-pi.html.twig`
- `tests/src/Unit/Service/PuntosImpactoEiServiceTest.php`

**Ficheros modificados:**
- `src/Service/AndaluciaEiCopilotContextProvider.php` — restriccion de modos por fase + instrucciones contextuales
- `src/Controller/CoordinadorDashboardController.php` — inyectar datos BMC
- `src/Controller/ParticipantePortalController.php` — inyectar Pi y logros
- `templates/coordinador-dashboard.html.twig` — widget BMC semaforo
- `templates/_participante-logros.html.twig` — integrar Pi

#### 7.2.4 Fase 11: Integracion cross-vertical completa

**Ambito:** Implementar `CrossVerticalBridgeService` y conectar todos los puntos de integracion descritos en la seccion 4.

**Solucion tecnica:**

1. Crear `CrossVerticalBridgeService` con el codigo de la seccion 4.1
2. Registrar en services.yml con todas las dependencias `@?`
3. Integrar en los flujos:
   - `AccesoProgramaService::altaParticipante()` → crear CandidateProfile
   - `FaseTransitionManager::transitToDiagnostico()` → si DIME >= 10, crear BMC
   - Dashboard orientador → widget de competencias via SkillsService
   - Dashboard coordinador → SROI via SroiCalculatorService
   - Fase insercion → matching via MatchingService, CV via CvBuilderService
4. Cada integracion envuelta en try-catch(\Throwable) (PRESAVE-RESILIENCE-001)

**Ficheros nuevos:**
- `src/Service/CrossVerticalBridgeService.php`

**Ficheros modificados:**
- `jaraba_andalucia_ei.services.yml` — registrar servicio con @?
- `src/Service/AccesoProgramaService.php` — integrar en flujo de alta
- `src/Controller/CoordinadorDashboardController.php` — widgets cross-vertical
- `src/Controller/OrientadorDashboardController.php` — widgets cross-vertical
- `src/Controller/ParticipantePortalController.php` — datos cross-vertical

### 7.3 Sprint 3 — Elevacion y Acceso (Fases 12-16)

**Objetivo:** Acceso completo a servicios + elevacion clase mundial.
**Estimacion:** 35-50 horas

#### 7.3.1 Fase 12: Configuracion de acceso a servicios SaaS

**Ambito:** Implementar todo lo descrito en la seccion 5.

**Solucion tecnica:**

1. Crear `AccesoProgramaService` con el flujo de alta automatizada
2. Registrar permisos en `jaraba_andalucia_ei.permissions.yml`
3. Crear config de roles en config/install o via hook_update_N()
4. Configurar tenant con plan enterprise y addon verticals (empleabilidad + emprendimiento)
5. Bypass de FairUsePolicyService para el tenant del programa (si aplica)

**Ficheros nuevos:**
- `src/Service/AccesoProgramaService.php`
- `jaraba_andalucia_ei.permissions.yml` — permisos granulares
- `config/install/user.role.participante_ei.yml`
- `config/install/user.role.coordinador_ei.yml`
- `config/install/user.role.orientador_ei.yml`
- `config/install/user.role.formador_ei.yml`

#### 7.3.2 Fase 13: Documentacion justificativa ampliada

**Ambito:** Implementar `JustificacionEconomicaService` y completar el mapeo de documentos de la seccion 6.

**Solucion tecnica:**

1. Crear `JustificacionEconomicaService` con metodos:
   - `calcularModuloAtendida($tenantId)` — calcula N personas atendidas certificables
   - `calcularModuloInsertada($tenantId)` — calcula N personas insertadas certificables
   - `generarInformeTrimestral($tenantId, $trimestre)` — PDF con resumen periodo
   - `generarInformeFinal($tenantId)` — PDF completo
   - `generarEvidenciaFse($tenantId)` — datos agregados para FSE+

2. Templates PDF branded para cada tipo de documento

**Ficheros nuevos:**
- `src/Service/JustificacionEconomicaService.php`
- `templates/branded-pdf-justificacion.html.twig`
- `templates/branded-pdf-insercion.html.twig`
- `templates/branded-pdf-fse.html.twig`
- `tests/src/Unit/Service/JustificacionEconomicaServiceTest.php`

#### 7.3.3 Fase 14: Elevacion frontend — Templates, SCSS, accesibilidad

**Ambito:** Templates nuevos/modificados, SCSS, iconografia, accesibilidad.

**Solucion tecnica detallada en seccion 10.**

Resumen:
- 8 parciales Twig nuevos
- 4 ficheros SCSS nuevos
- Todos los colores via `var(--ej-*, fallback)`
- Iconos via `{{ jaraba_icon() }}` con variante duotone
- Textos via `{% trans %}...{% endtrans %}`
- Slide-panel para CRUD de actuaciones, FSE+, insercion, prospeccion
- WCAG 2.1 AA: aria-labels, headings jerarquicos, focus visible, contraste 4.5:1

#### 7.3.4 Fase 15: Testing integral y verificacion

**Ambito:** 45+ tests + validacion completa.

**Detalle en seccion 11.**

#### 7.3.5 Fase 16: Alumni, alta autonomo y diferenciadores clase mundial

**GAPs cubiertos:** GAP-PIIL-15, GAP-PIIL-16 + mejoras de elevacion

**Problema detallado:**
El programa Andalucia +ei tiene el potencial de ser el PIIL mas avanzado de Espana. Los elementos que lo diferencian de un programa PIIL convencional (que se limita a Excel + formularios papel) son: (1) la red alumni post-programa que mantiene el vinculo y facilita futuras ediciones, (2) el checklist automatizado para alta de autonomos que reduce la barrera de insercion por cuenta propia, (3) la analitica predictiva que anticipa riesgo de abandono, (4) la experiencia mobile-first del participante, y (5) la medicion de impacto social automatizada (SROI).

**Solucion tecnica detallada:**

##### 16.A — Club Alumni y Circulos de Responsabilidad

Segun el Manual Operativo V2.1, el programa contempla una fase post-programa donde los participantes que completan el itinerario con exito se convierten en "alumni" que pueden:
- Mentorizar a participantes de futuras ediciones
- Participar en circulos de responsabilidad (peer groups)
- Acceder a recursos formativos actualizados
- Recibir seguimiento continuado via Copilot IA

**Implementacion:**

1. **Transicion automatica a alumni:** Cuando un participante transita de `seguimiento` a `baja` con `es_persona_insertada = TRUE`, se ejecuta automaticamente:
   - Se marca como alumni en ProgramaParticipanteEi (`is_alumni = TRUE`)
   - Se anade al grupo "Alumni Andalucia +ei" (Group entity)
   - Se cambia su rol de `participante_ei` a `alumni_ei` (mantiene acceso a Copilot modo Coach Emocional + consultas puntuales)
   - Se envía email de felicitacion con enlace al espacio alumni

2. **Espacio alumni:** Pagina dedicada `/andalucia-ei/alumni` con:
   - Directorio de alumni (nombre, sector, tipo insercion) — visible solo para otros alumni y personal tecnico
   - Recursos formativos actualizados (enlaces a pildoras, materiales complementarios)
   - Circulo de responsabilidad: foro ligero donde los alumni comparten experiencias
   - Oportunidad de convertirse en mentor para futuras ediciones (enlace a `jaraba_mentoring` MentorProfile)
   - Acceso a Copilot IA en modo Coach Emocional para consultas puntuales

3. **Circulos de responsabilidad:**
   - Grupos de 5-8 alumni que se reunen mensualmente (virtual)
   - Agenda automatica via `SessionSchedulerService` (cross-vertical jaraba_mentoring)
   - Notas de cada reunion almacenadas en expediente
   - Coordinados por un alumni senior o un orientador del programa

**Campos nuevos en ProgramaParticipanteEi:**
- `is_alumni`: boolean (FALSE por defecto)
- `alumni_fecha`: datetime (fecha en que se convirtio en alumni)
- `alumni_disponible_mentoria`: boolean (quiere mentorizar a futuros participantes)

**Rol nuevo:**
- `alumni_ei`: acceso limitado a espacio alumni + Copilot Coach Emocional + directorio

**Ficheros nuevos:**
- `src/Controller/AlumniController.php` — pagina alumni
- `templates/andalucia-ei-alumni.html.twig` — template pagina alumni
- `templates/_alumni-directory.html.twig` — parcial directorio
- `scss/_alumni.scss` — estilos pagina alumni

##### 16.B — Checklist automatizado de alta de autonomo

Para participantes del carril Acelera que se insertan por cuenta propia, el proceso de darse de alta como autonomo es una barrera significativa. El checklist automatizado guia paso a paso:

**Checklist de alta de autonomo (21 pasos):**

| Paso | Descripcion | Documentacion | Verificable |
|------|-------------|---------------|:-----------:|
| 1 | Certificado digital (FNMT o DNI-e) | Enlace a FNMT | Manual |
| 2 | Alta censal modelo 036/037 (Hacienda) | PDF explicativo | Campo `modelo_036_037` |
| 3 | Eleccion epigrafe IAE (CNAE) | Guia de epigrafes por sector | Campo `sector_cnae` |
| 4 | Eleccion regimen IVA | Guia regimen general vs simplificado | Manual |
| 5 | Alta RETA Seguridad Social | Enlace a sede electronica SS | Campo `fecha_alta_reta` |
| 6 | Eleccion cuota (tarifa plana) | Info tarifa plana autonomos | Manual |
| 7 | Base de cotizacion | Simulador cotizacion | Manual |
| 8 | Licencia de apertura (si local) | Info por municipio | Manual |
| 9 | Comunicacion apertura centro trabajo | Enlace a Junta Andalucia | Manual |
| 10 | Registro mercantil (si SL) | Guia pasos | Manual |
| 11 | Proteccion de datos (RGPD) | Checklist RGPD basico | Manual |
| 12 | Marca y nombre comercial (OEPM) | Enlace a OEPM | Manual |
| 13 | Cuenta bancaria empresarial | Recomendaciones | Manual |
| 14 | Software facturacion | Opciones compatibles con Veri*factu | Manual |
| 15 | Libro de visitas digital | Info obligaciones laborales | Manual |
| 16 | Seguro RC (si aplica) | Info por sector | Manual |
| 17 | Alta en Hacienda Autonomica (IAE local) | Info por municipio | Manual |
| 18 | Bonificaciones autonomos Junta Andalucia | Enlace a bonificaciones | Manual |
| 19 | Declaraciones trimestrales (calendario) | Calendario fiscal del ejercicio | Manual |
| 20 | Plan de negocio actualizado | Exportar BMC a formato plan negocio | Via CanvasService |
| 21 | Presupuesto de tesoreria 12 meses | Generar via CFO Sintetico | Via Copilot |

**Implementacion:**

```php
/**
 * Genera el checklist de alta de autonomo personalizado.
 *
 * Cada paso incluye: descripcion, enlace a recurso, campo verificable (si existe),
 * estado (pendiente/completado), y notas del orientador.
 *
 * @param int $participanteId
 *   ID del participante.
 *
 * @return array
 *   Checklist con estado de cada paso.
 */
public function getChecklistAutonomo(int $participanteId): array {
  $participante = $this->entityTypeManager
    ->getStorage('programa_participante_ei')
    ->load($participanteId);

  if (!$participante) {
    return [];
  }

  $insercion = $this->getInsercion($participanteId);
  $checklist = self::PASOS_AUTONOMO;

  // Auto-verificar pasos que tienen campo en InsercionLaboral
  if ($insercion) {
    if ($insercion->get('modelo_036_037')->value) {
      $checklist[2]['completado'] = TRUE;
    }
    if ($insercion->get('sector_cnae')->value) {
      $checklist[3]['completado'] = TRUE;
    }
    if ($insercion->get('fecha_alta_reta')->value) {
      $checklist[5]['completado'] = TRUE;
    }
  }

  // Auto-verificar BMC (paso 20)
  $canvasId = $participante->get('canvas_id')->value;
  if ($canvasId && $this->crossVerticalBridge) {
    try {
      $canvasData = $this->crossVerticalBridge->getBusinessCanvasStatus((int) $canvasId);
      if (($canvasData['completeness_score'] ?? 0) >= 70) {
        $checklist[20]['completado'] = TRUE;
      }
    }
    catch (\Throwable) {}
  }

  return $checklist;
}
```

Template `_checklist-autonomo.html.twig`:
- Barra de progreso: N/21 pasos completados
- Cada paso es un acordeon expandible con: descripcion, enlace a recurso, boton "marcar completado"
- Pasos auto-verificados con icono verde y "Verificado automaticamente"
- Boton "Consultar con CFO Sintetico" abre Copilot en modo CFO para dudas fiscales
- Boton "Consultar con Experto Tributario" para cuestiones fiscales especificas

##### 16.C — Analitica predictiva: Riesgo de abandono

Servicio que analiza patrones de actividad para predecir riesgo de abandono y alertar al orientador:

```php
/**
 * Calcula el riesgo de abandono de un participante.
 *
 * Factores: (1) dias sin actividad, (2) tasa de completitud de pildoras,
 * (3) asistencia a sesiones, (4) engagement con Copilot, (5) progreso en calendario.
 *
 * @return array{riesgo: string, score: float, factores: array, recomendacion: string}
 */
public function calcularRiesgoAbandono(int $participanteId): array {
  $factores = [];
  $score = 0.0;

  // Factor 1: Dias sin actividad (peso 30%)
  $ultimaActuacion = $this->getUltimaActuacion($participanteId);
  $diasInactivo = $ultimaActuacion
    ? (int) (new \DateTime())->diff(new \DateTime($ultimaActuacion))->days
    : 999;

  if ($diasInactivo > 14) {
    $score += 0.3;
    $factores[] = sprintf('Sin actividad registrada en %d días', $diasInactivo);
  }
  elseif ($diasInactivo > 7) {
    $score += 0.15;
    $factores[] = sprintf('%d días sin actividad', $diasInactivo);
  }

  // Factor 2: Tasa de completitud pildoras vs semana (peso 25%)
  // Factor 3: Asistencia a sesiones programadas (peso 20%)
  // Factor 4: Engagement Copilot — sesiones ultimas 2 semanas (peso 15%)
  // Factor 5: Progreso calendario vs semana esperada (peso 10%)
  // ... (logica similar para cada factor)

  $nivel = match (TRUE) {
    $score >= 0.7 => 'critico',
    $score >= 0.4 => 'alto',
    $score >= 0.2 => 'medio',
    default => 'bajo',
  };

  return [
    'riesgo' => $nivel,
    'score' => round($score, 2),
    'factores' => $factores,
    'recomendacion' => $this->getRecomendacion($nivel, $factores),
  ];
}
```

**Integracion:**
- Se muestra como indicador visual en el dashboard del orientador (semaforo por participante)
- Se incluye en las alertas de `AlertasNormativasService` cuando riesgo >= 'alto'
- El orientador recibe notificacion proactiva si un participante pasa a riesgo 'critico'

##### 16.D — Experiencia mobile-first (PWA optimizada)

El portal del participante se optimiza para uso desde movil (muchos participantes acceden desde smartphone):

- **Responsive breakpoints:** 320px (mobile S), 375px (mobile M), 768px (tablet), 1024px (desktop)
- **Touch targets:** Minimo 44x44px para todos los elementos interactivos (WCAG 2.5.5)
- **Bottom navigation:** En mobile, barra inferior fija con iconos de las 4 secciones principales (Mi progreso, Calendario, Expediente, Copilot)
- **Offline-first:** Las pildoras formativas se cachean en Service Worker para acceso sin conexion
- **Push notifications** (via `jaraba_pwa`): Recordatorio de sesiones, alertas de nuevos hitos alcanzados, motivacion semanal
- **Quick actions:** Acciones rapidas desde la home: "Hablar con Copilot", "Ver mi proxima sesion", "Completar pildora de hoy"
- **Swipe gestures:** En el calendario, swipe lateral para navegar entre semanas

Las variables CSS `--ej-*` ya son responsive por defecto. Los componentes nuevos usan `clamp()` para tipografia fluida:

```scss
// _calendario.scss
.calendario-semana {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 280px), 1fr));
  gap: var(--ej-spacing-md, 16px);
  padding: var(--ej-spacing-sm, 8px);

  &__card {
    background: var(--ej-surface-primary, #ffffff);
    border-radius: var(--ej-border-radius, 8px);
    box-shadow: var(--ej-shadow-card, 0 2px 8px rgba(0,0,0,0.08));
    padding: var(--ej-spacing-md, 16px);

    // Touch-friendly
    min-height: 44px;

    &__titulo {
      font-size: clamp(0.875rem, 2vw, 1rem);
      font-weight: 600;
      color: var(--ej-text-primary, #1a1a2e);
    }
  }
}

// Bottom nav en mobile
@media (max-width: 767px) {
  .participante-bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 100;
    display: flex;
    justify-content: space-around;
    background: var(--ej-surface-primary, #ffffff);
    border-top: 1px solid var(--ej-border-color, #e0e0e0);
    padding: var(--ej-spacing-xs, 4px) 0;
    box-shadow: 0 -2px 8px rgba(0,0,0,0.06);
  }
}
```

##### 16.E — Medicion de impacto social automatizada (SROI integrado)

El SROI (Social Return on Investment) se calcula automaticamente consumiendo `SroiCalculatorService` de `jaraba_business_tools`:

**Datos que alimentan el SROI:**
- Inversiones: 202.500 EUR totales del programa
- Outcomes cuantificables:
  - N personas atendidas x valor economico estimado de orientacion
  - N personas insertadas x salario medio anual x duracion estimada contrato
  - N emprendedores x ingresos proyectados primer ano (desde BusinessModelCanvas)
  - Ahorro en prestaciones desempleo: N personas x cuantia media x meses ahorrados
  - Impacto en cotizaciones SS: N insertados x cotizacion media mensual x 12

**Widget en dashboard coordinador** — `_coordinador-sroi-widget.html.twig`:
- Ratio SROI actual (ej: "Por cada 1 EUR invertido, se genera 2.3 EUR de retorno social")
- Grafico de barras: inversiones vs retornos por categoria
- Comparativa con benchmark de programas PIIL similares
- Boton "Generar informe de impacto" (PDF descargable para la Junta)

**Ficheros nuevos (Fase 16 completa):**
- `src/Service/PuntosImpactoEiService.php` (si no existe transversalmente)
- `src/Service/RiesgoAbandonoService.php`
- `src/Controller/AlumniController.php`
- `templates/andalucia-ei-alumni.html.twig`
- `templates/_alumni-directory.html.twig`
- `templates/_checklist-autonomo.html.twig`
- `templates/_participante-logros-pi.html.twig`
- `templates/_coordinador-sroi-widget.html.twig`
- `templates/_orientador-riesgo-abandono.html.twig`
- `scss/_alumni.scss`
- `scss/_logros-pi.scss`
- `tests/src/Unit/Service/PuntosImpactoEiServiceTest.php`
- `tests/src/Unit/Service/RiesgoAbandonoServiceTest.php`

**Ficheros modificados:**
- `src/Service/AlertasNormativasService.php` — integrar riesgo abandono como alerta
- `src/Controller/CoordinadorDashboardController.php` — widgets SROI + riesgo abandono
- `src/Controller/OrientadorDashboardController.php` — semaforo riesgo abandono
- `src/Controller/ParticipantePortalController.php` — Pi, logros, bottom nav
- `jaraba_andalucia_ei.services.yml` — 2 servicios nuevos
- `jaraba_andalucia_ei.routing.yml` — ruta alumni
- `jaraba_andalucia_ei.permissions.yml` — permiso alumni
- `jaraba_andalucia_ei.libraries.yml` — libraries alumni + logros

---

## 8. Tabla de Correspondencia: Especificaciones Tecnicas

### 8.1 Brechas vs entidades vs servicios vs templates vs tests

| GAP ID | Entidad(es) | Servicio(s) | Controller | Template(s) | Test(s) |
|--------|------------|-------------|------------|-------------|---------|
| GAP-01 | ProgramaParticipanteEi (mod) | FaseTransitionManager (mod) | — | — | FaseTransitionManagerTest |
| GAP-02 | ActuacionSto (amp) | ActuacionStoService (amp) | ActuacionStoApiController | actuacion-sto-entity.html.twig, _actuacion-form-slidepanel | ActuacionStoServiceTest, ActuacionStoKernelTest |
| GAP-03 | IndicadorFsePlus (new) | IndicadorFseService (new) | IndicadorFseApiController | indicador-fse-plus-entity.html.twig | IndicadorFseServiceTest, IndicadorFsePlusKernelTest |
| GAP-04 | ActuacionSto.vobo_sae_* | ActuacionStoService (mod) | — | _vobo-sae-upload.html.twig | ActuacionStoVoboTest |
| GAP-05 | InsercionLaboral (new) | InsercionLaboralService (new) | InsercionLaboralApiController | insercion-laboral-entity.html.twig | InsercionLaboralServiceTest |
| GAP-06 | ProgramaParticipanteEi.daci_* | DaciService (verify) | — | branded-pdf-daci.html.twig | DaciServiceTest |
| GAP-07 | ProgramaParticipanteEi.colectivo (mod) | — | — | — | ColectivoUpdateTest |
| GAP-08 | CalendarioSemanalEi (new) | CalendarioEiService (new) | CalendarioEiApiController | _participante-calendario.html.twig | CalendarioEiServiceTest |
| GAP-09 | ProgramaParticipanteEi.dime_* | CopilotContextProvider (mod) | — | — | DimeIntegrationTest |
| GAP-10 | — (cross-vertical) | CrossVerticalBridgeService | CoordinadorDashboard (mod) | _coordinador-bmc-widget.html.twig | CrossVerticalBridgeTest |
| GAP-11 | — | AlertasNormativasService (amp) | — | — | AlertasNormativasServiceTest (amp) |
| GAP-12 | ProspeccionEmpresarial (new) | ProspeccionService (new) | ProspeccionApiController | prospeccion-entity.html.twig | ProspeccionServiceTest |
| GAP-13 | ExpedienteDocumento (mod cat) | ReciboServicioService (amp) | — | branded-pdf-recibo.html.twig | ReciboServicioServiceTest |
| GAP-14 | ProgramaParticipanteEi (hitos) | PuntosImpactoEiService (new) | ParticipantePortalController (mod) | _participante-logros-pi.html.twig, _logros-pi.scss | PuntosImpactoEiServiceTest |
| GAP-15 | ProgramaParticipanteEi (alumni fields) | AccesoProgramaService (alumni flow) | AlumniController (new) | andalucia-ei-alumni.html.twig, _alumni-directory.html.twig, _alumni.scss | — |
| GAP-16 | InsercionLaboral | InsercionLaboralService (checklist) | — | _checklist-autonomo.html.twig | — |
| GAP-17 | — | CopilotContextProvider (mod) | — | — | CopilotFaseRestrictionTest |
| PRED | — | RiesgoAbandonoService (new) | OrientadorDashboard (mod) | _orientador-riesgo-abandono.html.twig | RiesgoAbandonoServiceTest |
| SROI | — (cross-vertical) | CrossVerticalBridgeService (SROI) | CoordinadorDashboard (mod) | _coordinador-sroi-widget.html.twig | — |
| MOBILE | — | — (SCSS + PWA) | ParticipantePortalController (mod) | bottom-nav parcial, responsive layouts | — |
| CROSS | ProgramaParticipanteEi.candidate_*, canvas_* | CrossVerticalBridgeService (new) | Multiple (mod) | Multiple widgets | CrossVerticalBridgeServiceTest |
| ACCESS | Config roles | AccesoProgramaService (new) | — | — | AccesoProgramaServiceTest |
| DOCS | ExpedienteDocumento (mod cat) | JustificacionEconomicaService (new) | JustificacionApiController | Multiple PDF templates | JustificacionEconomicaServiceTest |

### 8.2 Requisitos normativos vs implementacion SaaS

| Cod | Requisito Normativo | Fuente | Estado actual | Estado tras implementacion | GAP ID |
|-----|---------------------|--------|--------------|---------------------------|--------|
| RN-01 | Fases: acogida→diagnostico→atencion→insercion→seguimiento→baja | BBRR Art.11 | PARCIAL (3/6) | COMPLETO (6/6) | GAP-01 |
| RN-02 | Registro individual de actuaciones con fecha, hora, contenido, lugar | Manual STO | NO | COMPLETO (ActuacionSto entity) | GAP-02 |
| RN-03 | Indicadores FSE+ en 3 momentos (entrada, salida, 6m) | BBRR Art.23, Reglamento FSE+ | NO | COMPLETO (IndicadorFsePlus entity) | GAP-03 |
| RN-04 | VoBo SAE para acciones formativas | Manual STO Cap.4 | NO | COMPLETO (campos en ActuacionSto) | GAP-04 |
| RN-05 | Detalle insercion por tipo (c.ajena/propia/agrario) | BBRR Art.17 | PARCIAL | COMPLETO (InsercionLaboral entity) | GAP-05 |
| RN-06 | DACI firmado al inicio | BBRR Art.14 | PARCIAL (servicio existe) | COMPLETO (flujo acogida integrado) | GAP-06 |
| RN-07 | Colectivos: larga_duracion, mayores_45, migrantes, perceptores | Resol. Concesion | PARCIAL (2/4) | COMPLETO (4/4) | GAP-07 |
| RN-08 | Recibo firmado cada actuacion | BBRR Art.19 | PARCIAL (solo mentoria) | COMPLETO (universal) | GAP-13 |
| RN-09 | Solicitud y admision de participantes | BBRR Art.12 | COMPLETO | COMPLETO | — |
| RN-10 | Expediente documental por participante | BBRR Art.20 | COMPLETO | COMPLETO (ampliado) | — |
| RN-11 | Exportacion datos para STO | Manual STO | COMPLETO | COMPLETO (datos granulares) | — |
| RN-12 | Tracking horas por tipo | BBRR Art.15 | COMPLETO (contadores) | COMPLETO (granular + contadores) | — |
| RN-13 | Informe progreso periodico | BBRR Art.21 | COMPLETO | COMPLETO | — |
| RN-14 | Firma digital documentos | BBRR Art.22 | COMPLETO | COMPLETO | — |
| RN-15 | Triage y priorizacion solicitudes | Interno | COMPLETO | COMPLETO | — |
| RN-16 | Transicion fases con requisitos | BBRR Art.16 | PARCIAL (3 fases) | COMPLETO (6 fases) | GAP-01 |
| RN-17 | Calendario 12 semanas con hitos | Manual Operativo | NO | COMPLETO (CalendarioSemanalEi) | GAP-08 |
| RN-18 | Diagnostico DIME y asignacion carril | Manual Operativo | PARCIAL (en copilot) | COMPLETO (integrado) | GAP-09 |
| RN-19 | Dashboard BMC con validacion | Manual Operativo | PARCIAL (en copilot) | COMPLETO (cross-vertical) | GAP-10 |
| RN-20 | Alertas plazos normativos | BBRR plazos | PARCIAL | COMPLETO (ampliado) | GAP-11 |
| RN-21 | Prospeccion empresarial documentada | BBRR Art.18 | NO | COMPLETO (ProspeccionEmpresarial) | GAP-12 |
| RN-22 | Justificacion economica trimestral/final | BBRR Art.24 | NO | COMPLETO (JustificacionEconomicaService) | DOCS |
| RN-23 | Requisitos "persona atendida" | BBRR Art.15 | PARCIAL (contadores) | COMPLETO (campo computed) | GAP-02 |
| RN-24 | Requisitos "persona insertada" | BBRR Art.17 | PARCIAL | COMPLETO (campo computed) | GAP-05 |
| RN-25 | Acceso participantes a todos los servicios | Programa publico | NO | COMPLETO (roles + permisos) | ACCESS |

---

## 9. Tabla de Cumplimiento de Directrices del Proyecto

| Directriz | Descripcion | Donde aplica | Como se verifica |
|-----------|-------------|-------------|-----------------|
| TENANT-001 | Toda query filtra por tenant | Todas las queries en servicios, controllers, access handlers | `php scripts/validation/validate-tenant-isolation.php` |
| TENANT-BRIDGE-001 | Usar TenantBridgeService para Tenant<->Group | Resolucion de tenant en servicios | Code review: no hay `getStorage('group')` con Tenant IDs |
| TENANT-ISOLATION-ACCESS-001 | AccessControlHandler verifica tenant match | 5 AccessControlHandlers nuevos | Test unitario de cada handler |
| PREMIUM-FORMS-PATTERN-001 | Toda entity form extiende PremiumEntityFormBase | 5 entity forms nuevos | Grep: `extends PremiumEntityFormBase` |
| ENTITY-PREPROCESS-001 | template_preprocess_{type}() en .module | 5 nuevas entidades con view mode | Grep en .module |
| ENTITY-FK-001 | FK mismo modulo = entity_reference, cross-modulo = integer | candidate_profile_id (integer), canvas_id (integer), participante_id (entity_reference) | Code review |
| ENTITY-001 | EntityOwnerTrait → EntityOwnerInterface + EntityChangedInterface | Todas las entidades con uid | Grep: `implements EntityOwnerInterface` |
| AUDIT-CONS-001 | AccessControlHandler en anotacion | 5 entidades nuevas | `php scripts/validation/validate-entity-integrity.php` |
| PRESAVE-RESILIENCE-001 | try-catch en preSave con servicios opcionales | ActuacionSto::preSave(), InsercionLaboral::preSave() | Code review |
| UPDATE-HOOK-REQUIRED-001 | hook_update_N() para cada cambio de esquema | ~5 hooks de update | `php scripts/validation/validate-entity-integrity.php` |
| UPDATE-HOOK-CATCH-001 | catch(\Throwable) en hooks | Todos los hook_update_N() | Grep: `catch (\Throwable` |
| UPDATE-HOOK-FIELDABLE-001 | getFieldStorageDefinitions() en updateFieldableEntityType | hook_update para ProgramaParticipanteEi | Code review |
| UPDATE-FIELD-DEF-001 | setName() + setTargetEntityTypeId() en updateFieldStorageDefinition | Si se usa updateFieldStorageDefinition | Code review |
| OPTIONAL-CROSSMODULE-001 | @? para dependencias cross-modulo | CrossVerticalBridgeService (8x @?) | `php scripts/validation/validate-optional-deps.php` |
| CONTAINER-DEPS-002 | Sin dependencias circulares | Todos los services.yml | `php scripts/validation/validate-circular-deps.php` |
| LOGGER-INJECT-001 | @logger.channel.X → LoggerInterface | 10+ servicios nuevos | `php scripts/validation/validate-logger-injection.php` |
| PHANTOM-ARG-001 | Args en services.yml = params constructor | Todos los servicios | Manual: contar args vs params |
| CONTROLLER-READONLY-001 | No readonly en properties heredadas de ControllerBase | Todos los controllers | Code review |
| DRUPAL11-001 | No redeclarar typed properties del padre | Todas las clases que heredan | PHPStan |
| SLIDE-PANEL-RENDER-001 | renderPlain() + $form['#action'] | Controllers de CRUD en slide-panel | Code review |
| FORM-CACHE-001 | No setCached(TRUE) incondicional | Entity forms | Grep: `setCached` |
| ZERO-REGION-001 | Variables via hook_preprocess_page() | Templates de pagina completa | Code review de preprocess |
| CSS-VAR-ALL-COLORS-001 | Todos los colores via var(--ej-*) | 4+ ficheros SCSS nuevos | `scripts/maintenance/migrate-hex-to-tokens.php` |
| SCSS-001 | @use (no @import), scope aislado | Todos los SCSS | Grep: no `@import` |
| SCSS-COMPILE-VERIFY-001 | Recompilar tras cada edicion | Post-edicion | `stat -c '%Y'` CSS > SCSS |
| SCSS-COMPILETIME-001 | Hex estatico para color.scale/adjust | Variables SCSS | Code review |
| SCSS-COLORMIX-001 | color-mix() para alpha runtime | Transparencias en SCSS | Grep: no `rgba(var(` |
| SCSS-ENTRY-CONSOLIDATION-001 | No name.scss + _name.scss en mismo dir | Verificar al crear | `ls scss/` |
| ICON-CONVENTION-001 | jaraba_icon('cat', 'name', opts) | Todos los iconos en templates | Grep: `jaraba_icon` |
| ICON-DUOTONE-001 | Variante default: duotone | Todos los iconos | Code review |
| ICON-COLOR-001 | Solo colores de paleta Jaraba | Iconos | Code review |
| ROUTE-LANGPREFIX-001 | URLs via Url::fromRoute() | JS fetch calls | Grep: no `/es/` hardcoded |
| INNERHTML-XSS-001 | Drupal.checkPlain() para innerHTML | JS que inserta datos API | Code review JS |
| CSRF-JS-CACHE-001 | Token /session/token cacheado | JS con fetch POST | Code review JS |
| CSRF-API-001 | _csrf_request_header_token en rutas API | Todas las rutas POST/PATCH/DELETE | routing.yml review |
| API-WHITELIST-001 | ALLOWED_FIELDS en endpoints dinamicos | Controllers API | Code review |
| SECRET-MGMT-001 | No secrets en config/sync/ | Configuracion | Grep config/sync/ |
| ACCESS-STRICT-001 | Comparaciones ownership con === | Access handlers | Code review |
| LABEL-NULLSAFE-001 | Null-safe en label() | Entidades sin label key | Code review |
| FIELD-UI-SETTINGS-TAB-001 | Default local task para field_ui_base_route | 5 nuevas entidades | routing.yml review |
| TWIG-URL-RENDER-ARRAY-001 | No concatenar url() con ~ | Templates Twig | Grep: `url(` en Twig |
| TWIG-INCLUDE-ONLY-001 | `only` en includes de parciales | Todos los {% include %} | Grep |
| TWIG-ENTITY-METHOD-001 | Getters no property access en Twig | Templates que usan entidades | Code review |
| KERNEL-TEST-DEPS-001 | Listar TODOS los modulos en $modules | Kernel tests | Code review |
| MOCK-METHOD-001 | createMock con interface correcta | Unit tests | PHPUnit run |
| MOCK-DYNPROP-001 | No dynamic properties en PHP 8.4 | Unit tests | PHPUnit run |
| TEST-CACHE-001 | getCacheContexts/Tags/MaxAge en mocks | Unit tests con entity mocks | Code review |
| DOC-GUARD-001 | Edit incremental para master docs | Si se actualizan | Pre-commit hook |
| COMMIT-SCOPE-001 | Commits de docs separados de codigo | Al hacer commit | Git discipline |

---

## 10. Arquitectura Frontend y Templates

### 10.1 Templates Twig nuevos y modificados

| Template | Ruta(s) | Tipo | Directrices aplicadas |
|----------|---------|------|----------------------|
| `actuacion-sto-form-slidepanel.html.twig` | Slide-panel desde dashboards | Parcial | SLIDE-PANEL-RENDER-001, TWIG-INCLUDE-ONLY-001 |
| `indicador-fse-form.html.twig` | Slide-panel desde portal | Parcial | SLIDE-PANEL-RENDER-001 |
| `insercion-laboral-form.html.twig` | Slide-panel desde dashboard coord | Parcial | SLIDE-PANEL-RENDER-001 |
| `prospeccion-form.html.twig` | Slide-panel desde dashboard orient | Parcial | SLIDE-PANEL-RENDER-001 |
| `_participante-calendario.html.twig` | Portal participante | Parcial | TWIG-INCLUDE-ONLY-001 |
| `_coordinador-bmc-widget.html.twig` | Dashboard coordinador | Parcial | TWIG-INCLUDE-ONLY-001 |
| `_coordinador-justificacion-widget.html.twig` | Dashboard coordinador | Parcial | TWIG-INCLUDE-ONLY-001 |
| `_checklist-autonomo.html.twig` | Portal participante (c.propia) | Parcial | TWIG-INCLUDE-ONLY-001 |

**Reglas en TODOS los templates:**
- Textos: `{% trans %}texto{% endtrans %}` (NUNCA filtro `|t`)
- URLs: `{{ url('route_name') }}` (NUNCA hardcoded, TWIG-URL-RENDER-ARRAY-001)
- Entidades: `entity.getField()` o `entity.id()` (NUNCA `entity.field`, TWIG-ENTITY-METHOD-001)
- Includes: `{% include '@jaraba_andalucia_ei/_parcial.html.twig' with { var: value } only %}`
- Sin logica de negocio — solo presentacion

### 10.2 Parciales reutilizables

Verificar que existen y reutilizar antes de crear nuevos (regla del tema):
- `_skeleton.html.twig` — loading state
- `_empty-state.html.twig` — estado vacio
- `_review-card.html.twig` — tarjeta de revision
- `_slide-panel-wrapper.html.twig` — wrapper del slide-panel

### 10.3 SCSS y pipeline de compilacion

**Ficheros SCSS nuevos:**

| Fichero | Contenido | Library |
|---------|-----------|---------|
| `scss/_actuaciones.scss` | Estilos para lista y form de actuaciones | andalucia-ei-actuaciones |
| `scss/_calendario.scss` | Timeline visual de 12 semanas | andalucia-ei-calendario |
| `scss/_indicadores-fse.scss` | Formulario de indicadores | andalucia-ei-indicadores |
| `scss/_prospeccion.scss` | Lista y form de prospeccion | andalucia-ei-prospeccion |

**Reglas SCSS:**
```scss
// CORRECTO — CSS-VAR-ALL-COLORS-001
@use '../variables' as *; // SCSS-001

.actuacion-card {
  background: var(--ej-surface-primary, #ffffff);
  border-left: 4px solid var(--ej-color-corporate, #233D63);
  color: var(--ej-text-primary, #1a1a2e);

  &--orientacion {
    border-color: var(--ej-color-corporate, #233D63);
  }
  &--formacion {
    border-color: var(--ej-color-impulso, #FF8C42);
  }
  &--insercion {
    border-color: var(--ej-color-innovation, #00A9A5);
  }

  // Alpha via color-mix (SCSS-COLORMIX-001)
  &:hover {
    background: color-mix(in srgb, var(--ej-color-corporate) 5%, transparent);
  }
}
```

**Compilacion:**
```bash
# Desde Lando
lando ssh -c "cd /app/web/modules/custom/jaraba_andalucia_ei && npx sass scss/_actuaciones.scss css/actuaciones.css --style=compressed"

# Verificar timestamp (SCSS-COMPILE-VERIFY-001)
stat -c '%Y %n' scss/_actuaciones.scss css/actuaciones.css
```

### 10.4 Variables CSS inyectables desde Drupal UI

Las variables `--ej-*` se inyectan desde la UI de Apariencia (hook_preprocess_html). Los nuevos componentes consumen las variables existentes sin necesidad de crear nuevas:

| Variable | Uso en andalucia_ei | Fallback |
|----------|-------------------|----------|
| `--ej-color-corporate` | Bordes orientacion, headers | #233D63 |
| `--ej-color-impulso` | Bordes formacion, badges carril Impulso | #FF8C42 |
| `--ej-color-innovation` | Bordes insercion, badges carril Acelera | #00A9A5 |
| `--ej-surface-primary` | Fondo de cards | #ffffff |
| `--ej-surface-secondary` | Fondo de sidebars | #f8f9fa |
| `--ej-text-primary` | Texto principal | #1a1a2e |
| `--ej-text-secondary` | Texto secundario | #6c757d |
| `--ej-border-radius` | Radio de bordes | 8px |
| `--ej-shadow-card` | Sombra de cards | 0 2px 8px rgba(0,0,0,0.08) |

### 10.5 Iconografia completa

Todos los iconos via `{{ jaraba_icon('category', 'name', { variant: 'duotone', color: 'color', size: '24px' }) }}`:

| Contexto | Categoria | Nombre | Color | Fase |
|----------|-----------|--------|-------|------|
| Acogida | status | welcome | azul-corporativo | acogida |
| DACI | security | signature | azul-corporativo | acogida |
| FSE+ indicadores | data | survey | azul-corporativo | acogida/salida |
| Diagnostico DIME | assessment | diagnostic | naranja-impulso | diagnostico |
| Carril Impulso | paths | starter | naranja-impulso | diagnostico |
| Carril Acelera | paths | accelerator | verde-innovacion | diagnostico |
| Orientacion ind. | actions | consultation | azul-corporativo | atencion |
| Orientacion grup. | actions | group-session | azul-corporativo | atencion |
| Formacion | education | training | naranja-impulso | atencion |
| Mentoria IA | ai | copilot | azul-corporativo | atencion |
| Mentoria humana | people | mentor | naranja-impulso | atencion |
| Pildora video | education | video-lesson | naranja-impulso | atencion |
| Experimento | science | experiment | verde-innovacion | atencion |
| BMC | business | canvas | verde-innovacion | atencion (Acelera) |
| Recibo servicio | files | receipt | naranja-impulso | todas |
| VoBo SAE | status | approved | verde-innovacion | atencion (form) |
| VoBo pendiente | status | pending | naranja-impulso | atencion (form) |
| Prospeccion | business | networking | verde-innovacion | insercion |
| Matching | people | match | verde-innovacion | insercion |
| CV | files | resume | azul-corporativo | insercion |
| Insercion c.ajena | business | contract | verde-innovacion | insercion |
| Insercion c.propia | business | startup | verde-innovacion | insercion |
| Alerta critica | status | warning | naranja-impulso | todas |
| Alerta info | status | info | azul-corporativo | todas |
| Calendario | time | calendar | azul-corporativo | atencion |
| Progreso | status | progress | verde-innovacion | todas |

### 10.6 Accesibilidad WCAG 2.1 AA

- **Contraste:** Minimo 4.5:1 para texto normal, 3:1 para texto grande. Las variables `--ej-*` ya cumplen
- **Headings:** Jerarquicos (h1 → h2 → h3), nunca saltar niveles
- **Focus visible:** `:focus-visible` con outline de 2px solid var(--ej-color-corporate)
- **aria-labels:** En todos los elementos interactivos (botones, enlaces, formularios)
- **Formularios:** Cada input con `<label for="">`, errores con `aria-describedby`
- **Tablas:** `<caption>`, `<thead>`, `scope` en headers
- **Slide-panel:** `role="dialog"`, `aria-modal="true"`, focus trap, `Escape` para cerrar

### 10.7 Slide-panel para CRUD de actuaciones

Toda accion de crear/editar entidades del programa se abre en slide-panel:

```php
// En el controller (ejemplo ActuacionStoApiController)
public function create(Request $request): Response {
  if ($this->isSlidePanelRequest($request)) {
    $form = $this->formBuilder()->getForm(ActuacionStoForm::class);
    $form['#action'] = $request->getRequestUri();
    return new Response(
      $this->renderer->renderPlain($form),
      200,
      ['Content-Type' => 'text/html'],
    );
  }
  // Fallback para acceso directo
  return $this->redirect('entity.actuacion_sto.add_form');
}

private function isSlidePanelRequest(Request $request): bool {
  return $request->isXmlHttpRequest()
    && !$request->query->has('_wrapper_format');
}
```

---

## 11. Verificacion y Testing

### 11.1 Tests automatizados por fase

| Fase | Test file | Tipo | Assertions minimas |
|------|----------|------|-------------------|
| 1 | FaseTransitionManagerTest.php | Unit | 6 transiciones validas + 6 invalidas + requisitos |
| 1 | ColectivoUpdateTest.php | Kernel | allowed_values actualizados en DB |
| 2 | ActuacionStoServiceTest.php | Unit | CRUD + recalculo horas + recibo |
| 2 | ActuacionStoKernelTest.php | Kernel | Entity CRUD + preSave + access |
| 3 | IndicadorFseServiceTest.php | Unit | CRUD + unicidad + update flags |
| 3 | IndicadorFsePlusKernelTest.php | Kernel | Entity CRUD + access |
| 4 | InsercionLaboralServiceTest.php | Unit | CRUD + verificacion + persona insertada |
| 5 | DaciServiceTest.php | Unit | Generacion PDF + firma |
| 6 | ReciboServicioServiceTest.php | Unit | Universal para 3 tipos |
| 7 | ActuacionStoVoboTest.php | Unit | Validacion VoBo requerido |
| 8 | CalendarioEiServiceTest.php | Unit | 12 semanas + pildoras + experimentos |
| 9 | ProspeccionServiceTest.php | Unit | CRUD + pipeline |
| 9 | AlertasNormativasServiceTest.php | Unit | Alertas ampliadas (15+ tests ya existentes + nuevas) |
| 10 | CrossVerticalBridgeServiceTest.php | Unit | Cada metodo con y sin servicio disponible |
| 11 | CopilotFaseRestrictionTest.php | Unit | Modos por fase |
| 12 | AccesoProgramaServiceTest.php | Unit | Alta participante + roles + permisos |
| 13 | JustificacionEconomicaServiceTest.php | Unit | Calculo modulos + informes |

**Total estimado:** ~45 tests

### 11.2 Checklist RUNTIME-VERIFY-001

Tras cada fase de implementacion:

- [ ] CSS compilado (timestamp > SCSS)
- [ ] Tablas DB creadas (`drush entity:updates`)
- [ ] Rutas accesibles (`drush route:list | grep andalucia`)
- [ ] data-* selectores matchean entre JS y HTML
- [ ] drupalSettings inyectado via hook_preprocess_page()

### 11.3 Checklist IMPLEMENTATION-CHECKLIST-001

**Complitud:**
- [ ] Servicio registrado en services.yml Y consumido
- [ ] Rutas apuntan a clases/metodos existentes
- [ ] Entidades con: AccessControlHandler, hook_theme, template_preprocess, Views data
- [ ] SCSS compilado, library registrada, hook_page_attachments_alter

**Integridad:**
- [ ] Tests Unit para servicios
- [ ] Tests Kernel para entidades
- [ ] hook_update_N() para cada cambio de esquema
- [ ] Config export si nuevas config entities

**Consistencia:**
- [ ] PREMIUM-FORMS-PATTERN-001 en forms
- [ ] CONTROLLER-READONLY-001 en controllers
- [ ] CSS-VAR-ALL-COLORS-001 en SCSS
- [ ] TENANT-001 en queries

**Coherencia:**
- [ ] Documentacion actualizada
- [ ] Memory files actualizados

### 11.4 Validacion con scripts del proyecto

```bash
# Ejecutar validacion completa
bash scripts/validation/validate-all.sh --checklist web/modules/custom/jaraba_andalucia_ei

# Scripts individuales criticos
php scripts/validation/validate-entity-integrity.php
php scripts/validation/validate-optional-deps.php
php scripts/validation/validate-circular-deps.php
php scripts/validation/validate-logger-injection.php
php scripts/validation/validate-tenant-isolation.php
php scripts/validation/validate-service-consumers.php
php scripts/validation/validate-compiled-assets.php
php scripts/validation/validate-test-coverage-map.php
```

---

## 12. Inventario Completo de Ficheros

### Ficheros nuevos (~50)

**Entidades:**
- `src/Entity/IndicadorFsePlus.php`
- `src/Entity/InsercionLaboral.php`
- `src/Entity/ProspeccionEmpresarial.php`
- `src/Entity/CalendarioSemanalEi.php`

**Forms:**
- `src/Form/IndicadorFsePlusForm.php`
- `src/Form/InsercionLaboralForm.php`
- `src/Form/ProspeccionEmpresarialForm.php`
- `src/Form/CalendarioSemanalEiForm.php`

**Access Control Handlers:**
- `src/IndicadorFsePlusAccessControlHandler.php`
- `src/InsercionLaboralAccessControlHandler.php`
- `src/ProspeccionEmpresarialAccessControlHandler.php`
- `src/CalendarioSemanalEiAccessControlHandler.php`

**Servicios:**
- `src/Service/IndicadorFseService.php`
- `src/Service/InsercionLaboralService.php`
- `src/Service/ProspeccionService.php`
- `src/Service/CalendarioEiService.php`
- `src/Service/CrossVerticalBridgeService.php`
- `src/Service/JustificacionEconomicaService.php`
- `src/Service/AccesoProgramaService.php`
- `src/Service/PuntosImpactoEiService.php`
- `src/Service/RiesgoAbandonoService.php`

**Controllers:**
- `src/Controller/IndicadorFseApiController.php`
- `src/Controller/InsercionLaboralApiController.php`
- `src/Controller/ProspeccionApiController.php`
- `src/Controller/CalendarioEiApiController.php`
- `src/Controller/JustificacionApiController.php`
- `src/Controller/AlumniController.php`

**Templates:**
- `templates/_participante-calendario.html.twig`
- `templates/_coordinador-bmc-widget.html.twig`
- `templates/_coordinador-justificacion-widget.html.twig`
- `templates/_coordinador-sroi-widget.html.twig`
- `templates/_checklist-autonomo.html.twig`
- `templates/_participante-logros-pi.html.twig`
- `templates/_bmc-participant-card.html.twig`
- `templates/_orientador-riesgo-abandono.html.twig`
- `templates/_alumni-directory.html.twig`
- `templates/andalucia-ei-alumni.html.twig`
- `templates/branded-pdf-insercion.html.twig`
- `templates/branded-pdf-fse.html.twig`
- `templates/branded-pdf-justificacion.html.twig`

**SCSS:**
- `scss/_actuaciones.scss`
- `scss/_calendario.scss`
- `scss/_indicadores-fse.scss`
- `scss/_prospeccion.scss`
- `scss/_alumni.scss`
- `scss/_logros-pi.scss`

**Tests:**
- `tests/src/Unit/Service/IndicadorFseServiceTest.php`
- `tests/src/Unit/Service/InsercionLaboralServiceTest.php`
- `tests/src/Unit/Service/ProspeccionServiceTest.php`
- `tests/src/Unit/Service/CalendarioEiServiceTest.php`
- `tests/src/Unit/Service/CrossVerticalBridgeServiceTest.php`
- `tests/src/Unit/Service/JustificacionEconomicaServiceTest.php`
- `tests/src/Unit/Service/AccesoProgramaServiceTest.php`
- `tests/src/Unit/Service/PuntosImpactoEiServiceTest.php`
- `tests/src/Unit/Service/RiesgoAbandonoServiceTest.php`
- `tests/src/Unit/Service/CopilotFaseRestrictionTest.php`
- `tests/src/Kernel/Entity/IndicadorFsePlusTest.php`
- `tests/src/Kernel/Entity/InsercionLaboralTest.php`

**Config:**
- `config/install/user.role.participante_ei.yml`
- `config/install/user.role.coordinador_ei.yml`
- `config/install/user.role.orientador_ei.yml`
- `config/install/user.role.formador_ei.yml`

### Ficheros modificados (~15)

- `src/Entity/ProgramaParticipanteEi.php` — fases, colectivos, campos cross-vertical
- `jaraba_andalucia_ei.install` — 3-5 hook_update_N()
- `jaraba_andalucia_ei.services.yml` — 7 servicios nuevos
- `jaraba_andalucia_ei.routing.yml` — ~15 rutas nuevas
- `jaraba_andalucia_ei.permissions.yml` — ~20 permisos nuevos
- `jaraba_andalucia_ei.libraries.yml` — 4 libraries nuevas
- `jaraba_andalucia_ei.module` — preprocess functions + hook_theme
- `src/Service/FaseTransitionManager.php` — 6 fases + requisitos
- `src/Service/AlertasNormativasService.php` — alertas ampliadas
- `src/Service/ActuacionStoService.php` — recalculo + recibo
- `src/Service/ReciboServicioService.php` — universal
- `src/Service/AndaluciaEiCopilotContextProvider.php` — restriccion modos
- `src/Controller/CoordinadorDashboardController.php` — widgets cross-vertical
- `src/Controller/ParticipantePortalController.php` — datos cross-vertical
- `css/` — 4 ficheros CSS compilados nuevos

---

## 13. Troubleshooting

| Problema | Causa probable | Solucion |
|----------|---------------|----------|
| `drush updatedb` falla con TypeError | catch(\Exception) en hook | Cambiar a catch(\Throwable) |
| "entity type needs to be installed" | Falta hook_update_N() | Crear hook con installEntityType() |
| Cross-vertical service NULL | Modulo no instalado | Verificar @? en services.yml + null check |
| Campos no aparecen en form | Falta getSectionDefinitions() | Implementar en PremiumEntityFormBase |
| Slide-panel muestra BigPipe placeholders | Usar render() en vez de renderPlain() | Cambiar a renderPlain() |
| SCSS no compila | name.scss + _name.scss en mismo dir | Consolidar en entry point |
| CSS colors no cambian por tenant | Hex hardcoded en SCSS | Reemplazar por var(--ej-*) |
| Actuacion sin recibo | ReciboServicioService es @? | Verificar que modulo esta instalado |
| Query sin tenant filter | Falta ->condition('tenant_id', $tid) | Anadir condicion + guard clause |
| FSE+ no se marca completado | preSave no actualiza participante | Verificar logica en IndicadorFseService |

---

## 14. Referencias

### Normativas
- PIIL BBRR Version Consolidada 20250730 (Bases Reguladoras)
- Resolucion de Concesion 202599904458144 (19/12/2025)
- Ficha Tecnica Validada FT_679 (28/01/2026)
- Manual Gestion P.Tecnico STO ICV25 (01/2026)
- Manual Representante Entidad STO INTEGRALES ICV 25
- Manual Operativo Completo Andalucia_ei V2.1 (21/01/2026)
- Contenido Formativo Integral V2.1
- Anexo Itinerarios Diferenciados Carriles

### Documentacion interna del proyecto
- `CLAUDE.md` — directrices del proyecto (v1.4.0)
- `docs/analisis/2026-03-06_Auditoria_Andalucia_Ei_PIIL_CV_2025_Brechas_Normativas_v1.md`
- `docs/implementacion/2026-03-06_Plan_Implementacion_Andalucia_Ei_Cumplimiento_PIIL_10_10_Clase_Mundial_v1.md`
- `docs/implementacion/2026-03-06_Plan_Implementacion_Andalucia_Ei_Mentoring_Cursos_Clase_Mundial_v1.md`
- `docs/tecnicos/20260115i-45_Andalucia_ei_Implementacion_v1_Claude.md`
- `docs/tecnicos/20260115c-Programa Maestro Andalucia +ei V2.0_Gemini.md`
- `docs/tecnicos/aprendizajes/2026-02-15_andalucia_ei_elevacion_12_fases.md`
- `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md`
- `docs/arquitectura/2026-02-05_especificacion_grapesjs_saas.md`

### Servicios cross-vertical (interfaces publicas)
- `jaraba_candidate/src/Service/CandidateProfileService.php`
- `jaraba_candidate/src/Service/CvBuilderService.php`
- `jaraba_candidate/src/Service/SkillsService.php`
- `jaraba_business_tools/src/Service/CanvasService.php`
- `jaraba_business_tools/src/Service/SroiCalculatorService.php`
- `jaraba_mentoring/src/Service/SessionSchedulerService.php`
- `jaraba_mentoring/src/Service/MentorMatchingService.php`
- `jaraba_matching/src/Service/MatchingService.php`
- `jaraba_funding/src/Service/ApplicationManagerService.php`

---

## 15. Registro de Cambios

| Version | Fecha | Cambios |
|---------|-------|---------|
| 2.0.0 | 2026-03-10 | Version inicial integral: 17 GAPs + cross-vertical + acceso servicios + documentacion ampliada |
| 2.1.0 | 2026-03-10 | Ampliacion elevacion clase mundial: Fase 10 detallada (BMC dashboard, Copilot contextual con instrucciones por fase/carril, sistema Pi con 24 hitos), Fase 16 detallada (Club Alumni con circulos responsabilidad, checklist autonomo 21 pasos, analitica predictiva riesgo abandono, mobile-first PWA, SROI automatizado) |

**Nota:** Este plan extiende y sustituye la version 1.0.0 (`2026-03-06_Plan_Implementacion_Andalucia_Ei_Cumplimiento_PIIL_10_10_Clase_Mundial_v1.md`), incorporando las siguientes ampliaciones mayores:
1. Integracion cross-vertical con 6 modulos del ecosistema (seccion 4)
2. Configuracion de acceso a servicios SaaS para programa 100% fondos publicos (seccion 5)
3. Documentacion justificativa ampliada mas alla de campos STO (seccion 6)
4. Tabla exhaustiva de cumplimiento de 55+ directrices del proyecto (seccion 9)
5. Esquemas completos de 5 entidades con todos los campos (seccion 3.2)
6. Mapa de acceso por rol a todos los servicios SaaS (seccion 5.4)
7. Flujo automatizado de alta de participantes (seccion 5.2)
