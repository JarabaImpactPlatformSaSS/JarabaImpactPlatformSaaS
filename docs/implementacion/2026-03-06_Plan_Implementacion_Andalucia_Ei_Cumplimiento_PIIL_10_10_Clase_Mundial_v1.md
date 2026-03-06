# Plan de Implementacion: Andalucia +ei 10/10 — Cumplimiento Normativo PIIL CV 2025 y Elevacion Clase Mundial

**Fecha de creacion:** 2026-03-06 14:30
**Ultima actualizacion:** 2026-03-06 14:30
**Autor:** IA Asistente (Claude Opus 4.6)
**Version:** 1.0.0
**Estado:** Planificado
**Categoria:** Elevacion Clase Mundial / Cumplimiento Normativo
**Modulos afectados:** `jaraba_andalucia_ei`, `jaraba_copilot_v2`, `jaraba_mentoring`, `ecosistema_jaraba_core`, `ecosistema_jaraba_theme`
**Documento fuente:** `docs/analisis/2026-03-06_Auditoria_Andalucia_Ei_PIIL_CV_2025_Brechas_Normativas_v1.md`
**Especificacion referencia:** PIIL BBRR Consolidada, Resolucion Concesion FT_679, Manual STO ICV25, Manual Operativo V2.1
**Prioridad:** P0 (cumplimiento normativo) + P1 (operativo) + P2 (elevacion)
**Directrices de aplicacion:** ZERO-REGION-001, PREMIUM-FORMS-PATTERN-001, CSS-VAR-ALL-COLORS-001, SCSS-COLORMIX-001, SCSS-001, SCSS-COMPILE-VERIFY-001, TENANT-001, TENANT-BRIDGE-001, UPDATE-HOOK-REQUIRED-001, UPDATE-HOOK-CATCH-001, UPDATE-FIELD-DEF-001, OPTIONAL-CROSSMODULE-001, CONTAINER-DEPS-002, LOGGER-INJECT-001, PHANTOM-ARG-001, SLIDE-PANEL-RENDER-001, FORM-CACHE-001, ENTITY-PREPROCESS-001, ENTITY-FK-001, ENTITY-001, AUDIT-CONS-001, PRESAVE-RESILIENCE-001, ICON-CONVENTION-001, ICON-DUOTONE-001, ICON-COLOR-001, ROUTE-LANGPREFIX-001, INNERHTML-XSS-001, CSRF-JS-CACHE-001, CSRF-API-001, API-WHITELIST-001, SECRET-MGMT-001, ACCESS-STRICT-001, CONTROLLER-READONLY-001, DRUPAL11-001, LABEL-NULLSAFE-001, FIELD-UI-SETTINGS-TAB-001, KERNEL-TEST-DEPS-001, MOCK-METHOD-001, DOC-GUARD-001, COMMIT-SCOPE-001
**Esfuerzo estimado:** 80-110 horas (12 fases)

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
   - 1.1 [Que se implementa](#11-que-se-implementa)
   - 1.2 [Por que se implementa](#12-por-que-se-implementa)
   - 1.3 [Alcance y exclusiones](#13-alcance-y-exclusiones)
   - 1.4 [Filosofia de implementacion](#14-filosofia-de-implementacion)
   - 1.5 [Estimacion de esfuerzo](#15-estimacion-de-esfuerzo)
2. [Diagnostico del Estado Actual](#2-diagnostico-del-estado-actual)
   - 2.1 [Lo que ya existe y funciona](#21-lo-que-ya-existe-y-funciona)
   - 2.2 [Brechas identificadas](#22-brechas-identificadas)
   - 2.3 [Dependencias entre brechas](#23-dependencias-entre-brechas)
3. [Arquitectura Objetivo](#3-arquitectura-objetivo)
   - 3.1 [Modelo de datos ampliado](#31-modelo-de-datos-ampliado)
   - 3.2 [Nuevas entidades](#32-nuevas-entidades)
   - 3.3 [Arquitectura de servicios](#33-arquitectura-de-servicios)
   - 3.4 [Mapa de rutas frontend](#34-mapa-de-rutas-frontend)
   - 3.5 [Diagrama de flujo del itinerario completo](#35-diagrama-de-flujo-del-itinerario-completo)
4. [Requisitos Previos](#4-requisitos-previos)
5. [Fases de Implementacion](#5-fases-de-implementacion)
   - 5.1 [Fase 1 — Fases PIIL Completas y Colectivos Corregidos (P0)](#51-fase-1)
   - 5.2 [Fase 2 — Entidad ActuacionSto: Tracking Granular de Actuaciones (P0)](#52-fase-2)
   - 5.3 [Fase 3 — Indicadores FSE+ (P0)](#53-fase-3)
   - 5.4 [Fase 4 — Insercion Laboral Detallada (P0)](#54-fase-4)
   - 5.5 [Fase 5 — DACI Digital y Flujo de Acogida (P0)](#55-fase-5)
   - 5.6 [Fase 6 — Recibo de Servicio Universal (P0)](#56-fase-6)
   - 5.7 [Fase 7 — Formacion con VoBo SAE (P0)](#57-fase-7)
   - 5.8 [Fase 8 — Calendario 12 Semanas e Integracion DIME (P1)](#58-fase-8)
   - 5.9 [Fase 9 — Prospeccion Empresarial y Alertas Normativas (P1)](#59-fase-9)
   - 5.10 [Fase 10 — Integracion BMC, Copilot por Fase y Gamificacion Pi (P1-P2)](#510-fase-10)
   - 5.11 [Fase 11 — Elevacion Frontend: Templates, SCSS, Accesibilidad (P1)](#511-fase-11)
   - 5.12 [Fase 12 — Testing, Verificacion y Documentacion (P0)](#512-fase-12)
6. [Tabla de Correspondencia con Especificaciones Tecnicas](#6-tabla-de-correspondencia-con-especificaciones-tecnicas)
7. [Tabla de Cumplimiento de Directrices del Proyecto](#7-tabla-de-cumplimiento-de-directrices-del-proyecto)
8. [Arquitectura Frontend y Templates](#8-arquitectura-frontend-y-templates)
   - 8.1 [Templates Twig nuevos](#81-templates-twig-nuevos)
   - 8.2 [Parciales reutilizables](#82-parciales-reutilizables)
   - 8.3 [SCSS y compilacion](#83-scss-y-compilacion)
   - 8.4 [Variables CSS inyectables desde Drupal UI](#84-variables-css-inyectables-desde-drupal-ui)
   - 8.5 [Iconografia](#85-iconografia)
   - 8.6 [Accesibilidad WCAG 2.1 AA](#86-accesibilidad)
9. [Verificacion y Testing](#9-verificacion-y-testing)
   - 9.1 [Tests automatizados](#91-tests-automatizados)
   - 9.2 [Checklist RUNTIME-VERIFY-001](#92-checklist-runtime-verify-001)
   - 9.3 [Checklist IMPLEMENTATION-CHECKLIST-001](#93-checklist-implementation-checklist-001)
10. [Inventario Completo de Ficheros](#10-inventario-completo-de-ficheros)
11. [Troubleshooting](#11-troubleshooting)
12. [Referencias](#12-referencias)
13. [Registro de Cambios](#13-registro-de-cambios)

---

## 1. Resumen Ejecutivo

### 1.1 Que se implementa

Plan de 12 fases para llevar el modulo `jaraba_andalucia_ei` de su estado actual (funcional pero incompleto respecto a la normativa PIIL CV 2025) al 10/10 de clase mundial, asegurando:

1. **Cumplimiento normativo completo** (7 brechas P0): Fases PIIL completas, tracking granular de actuaciones STO, indicadores FSE+, insercion laboral detallada, DACI digital, recibos de servicio universales, VoBo formacion SAE
2. **Funcionalidad operativa completa** (6 brechas P1): Calendario 12 semanas, DIME automatico, BMC integrado, alertas de plazos, prospeccion empresarial
3. **Elevacion a clase mundial** (4 brechas P2): Gamificacion Pi, restriccion copilot por fase, alumni, alta autonomo
4. **Frontend impecable**: Templates zero-region, SCSS con tokens CSS, accesibilidad WCAG 2.1 AA, slide-panel para toda accion CRUD, responsive mobile-first

**Nuevas entidades:** 4 (ActuacionSto, InsercionLaboral, IndicadorFsePlus, ProspeccionEmpresarial)
**Campos modificados:** ~15 en entidades existentes
**Servicios nuevos:** 8
**Templates nuevos:** 6
**Tests nuevos:** ~30

### 1.2 Por que se implementa

**Riesgo financiero:** La subvencion de 202.500 EUR (85% FSE+ = 172.125 EUR) requiere justificacion documental completa ante la Junta de Andalucia y la Comision Europea. Sin las 7 brechas P0 resueltas, la justificacion es incompleta y el reembolso esta en riesgo.

**Riesgo operativo:** Los orientadores y coordinadores necesitan herramientas para gestionar el programa dia a dia: registrar actuaciones, generar recibos, hacer seguimiento de plazos, documentar inserciones.

**Riesgo reputacional:** Andalucia +ei es la vertical mas visible del ecosistema Jaraba y su exito determina la capacidad de conseguir futuras subvenciones.

### 1.3 Alcance y exclusiones

**INCLUIDO:**
- 4 nuevas ContentEntities con PremiumEntityFormBase, AccessControlHandler, Views, Field UI, templates, preprocess
- Ampliacion de ProgramaParticipanteEi (fases, colectivos, campos FSE+)
- Servicio universal de Recibo de Servicio PDF con firma dual
- Integracion DIME->carril automatica via CopilotBridgeService
- Flujo de acogida completo (DACI, recogida indicadores FSE+)
- Calendario semanal con mapping a sesiones, pildoras, experimentos
- Prospeccion empresarial y alertas normativas
- SCSS route-specific para nuevas rutas
- Tests Unit + Kernel para cada entidad y servicio
- hook_update_N() para todos los cambios de schema

**EXCLUIDO:**
- Integracion directa con el STO web (el STO es un sistema externo de la Junta; nos limitamos a exportar datos compatibles)
- Modificaciones a jaraba_copilot_v2 (usamos sus APIs via bridges)
- Modificaciones al GrapesJS Page Builder
- Migracion de datos historicos (los participantes actuales se actualizan manualmente en la interfaz admin)
- Nuevos copilot modes (solo restriccion de los existentes por fase)

### 1.4 Filosofia de implementacion

1. **Reutilizacion maxima**: Consumir servicios existentes (FirmaDigitalService, ExpedienteService, CopilotBridgeService, InformeProgresoPdfService). No duplicar logica.
2. **Zero Region Pattern**: Todas las paginas frontend nuevas usan `{{ clean_content }}` con layout limpio. Variables via `hook_preprocess_page()`.
3. **Presave Resilience (PRESAVE-RESILIENCE-001)**: Servicios cross-modulo con `@?` + `hasService()` + `try-catch`. Las entidades DEBEN salvarse aunque fallen servicios opcionales.
4. **CSS Custom Properties (CSS-VAR-ALL-COLORS-001)**: Todo SCSS nuevo usa `var(--ej-*, fallback)`. NUNCA hex hardcoded. Colores configurables desde Drupal UI.
5. **Textos traducibles**: Todo texto visible al usuario via `$this->t()` en PHP y `{% trans %}` en Twig. NUNCA strings hardcoded en templates.
6. **Slide-Panel (SLIDE-PANEL-RENDER-001)**: Toda accion crear/editar/ver se abre en slide-panel. Usa `renderPlain()`, NO `render()`.
7. **Dart SCSS moderno**: `@use` (NUNCA `@import`), `@use 'sass:color'`, `color-mix()` para alpha.
8. **Entidades con integracion completa**: AccessControlHandler, Views data, Field UI, hook_theme, template_preprocess, PremiumEntityFormBase (NUNCA ContentEntityForm).
9. **Mobile-first responsive**: Breakpoints 480px, 768px, 1024px. Touch targets minimo 44x44px.
10. **WCAG 2.1 AA**: Contraste 4.5:1, focus visible, aria-labels, headings jerarquicos, keyboard navigation.

### 1.5 Estimacion de esfuerzo

| Fase | Concepto | Prioridad | Horas Min | Horas Max |
|------|----------|-----------|-----------|-----------|
| 1 | Fases PIIL completas + colectivos | P0 | 3h | 4h |
| 2 | Entidad ActuacionSto | P0 | 10h | 14h |
| 3 | Indicadores FSE+ | P0 | 6h | 8h |
| 4 | Insercion Laboral detallada | P0 | 6h | 8h |
| 5 | DACI digital + acogida | P0 | 5h | 7h |
| 6 | Recibo de Servicio universal | P0 | 8h | 10h |
| 7 | Formacion VoBo SAE | P0 | 4h | 6h |
| 8 | Calendario 12 semanas + DIME | P1 | 8h | 10h |
| 9 | Prospeccion + alertas | P1 | 5h | 7h |
| 10 | BMC + Copilot fase + Pi | P1-P2 | 5h | 7h |
| 11 | Elevacion frontend | P1 | 10h | 14h |
| 12 | Testing + verificacion | P0 | 10h | 15h |
| | **TOTAL** | | **80h** | **110h** |

---

## 2. Diagnostico del Estado Actual

### 2.1 Lo que ya existe y funciona

| Componente | Estado | Observacion |
|-----------|--------|-------------|
| ProgramaParticipanteEi (22 campos) | Funcional | Tracking horas, carril, insercion basica |
| SolicitudEi (24 campos) | Funcional | Triage IA, colectivo inferido |
| ExpedienteDocumento (23 campos, 22 categorias) | Funcional | Revision IA, firma digital, vault |
| FaseTransitionManager | Funcional | Transiciones atencion->insercion->baja con requisitos |
| StoExportService | Funcional | CSV compatible STO |
| CoordinadorHubService + Dashboard | Funcional | Hub operativo con tabs, API REST |
| OrientadorDashboardController | Funcional | Cartera participantes, alertas |
| ParticipantePortalController | Funcional | Portal con 7 parciales |
| HojaServicioMentoriaService | Funcional | PDF mentoria con firma dual |
| ProgramaMentoresController | Funcional | Mentores filtrados por edicion |
| ProgramaFormacionController | Funcional | Formacion filtrada por edicion |
| ExpedienteHubController | Funcional | Hub documental role-aware |
| SolicitudTriageService | Funcional | IA scoring con recomendacion |
| AndaluciaEiCopilotBridgeService | Funcional | Bridge con Copilot v2 |
| InformeProgresoPdfService | Funcional | PDF de progreso |

### 2.2 Brechas identificadas

Ver documento completo: `docs/analisis/2026-03-06_Auditoria_Andalucia_Ei_PIIL_CV_2025_Brechas_Normativas_v1.md`

| ID | Brecha | Prioridad | Fase Plan |
|----|--------|-----------|-----------|
| GAP-PIIL-01 | Fases incompletas (3/6) | P0 | 1 |
| GAP-PIIL-02 | Sin entidad ActuacionSto | P0 | 2 |
| GAP-PIIL-03 | Sin indicadores FSE+ | P0 | 3 |
| GAP-PIIL-04 | Sin VoBo SAE formacion | P0 | 7 |
| GAP-PIIL-05 | Insercion sin detalle | P0 | 4 |
| GAP-PIIL-06 | DACI digital inexistente | P0 | 5 |
| GAP-PIIL-07 | Colectivos incorrectos | P0 | 1 |
| GAP-PIIL-08 | Calendario desconectado | P1 | 8 |
| GAP-PIIL-09 | DIME desconectado | P1 | 8 |
| GAP-PIIL-10 | BMC desconectado | P1 | 10 |
| GAP-PIIL-11 | Plazos sin alertas | P1 | 9 |
| GAP-PIIL-12 | Prospeccion sin tracking | P1 | 9 |
| GAP-PIIL-13 | Recibo solo mentoria | P1 | 6 |
| GAP-PIIL-14 | Gamificacion Pi | P2 | 10 |
| GAP-PIIL-15 | Club Alumni | P2 | 10 |
| GAP-PIIL-16 | Alta autonomo checklist | P2 | 10 |
| GAP-PIIL-17 | Copilot sin restriccion fase | P2 | 10 |

### 2.3 Dependencias entre brechas

```
Fase 1 (fases + colectivos)
  |
  +---> Fase 2 (actuaciones — necesita fases completas para categorizar)
  |       |
  |       +---> Fase 6 (recibos — se genera por cada actuacion)
  |       +---> Fase 7 (VoBo — es un tipo de actuacion formativa)
  |
  +---> Fase 3 (FSE+ — recogida vinculada a fases acogida/salida)
  |
  +---> Fase 4 (insercion — vinculada a fase insercion)
  |
  +---> Fase 5 (DACI — vinculado a fase acogida)
  |
  +---> Fase 8 (calendario — usa fases para mapear semanas)
          |
          +---> Fase 10 (BMC, copilot — depende de calendario y DIME)

Fase 9 (prospeccion + alertas) — independiente, ejecutable en paralelo

Fase 11 (frontend) — depende de entidades y servicios de fases 1-10

Fase 12 (testing) — final, cubre todo
```

---

## 3. Arquitectura Objetivo

### 3.1 Modelo de datos ampliado

#### Cambios en ProgramaParticipanteEi

**Campos a modificar:**

| Campo | Cambio | Justificacion |
|-------|--------|---------------|
| `fase_actual` | Ampliar allowed_values a 6 | Normativa exige 6 fases |
| `colectivo` | Corregir allowed_values | Colectivos de esta edicion |

**Nuevo allowed_values para `fase_actual`:**
```
'acogida' => 'Acogida'
'diagnostico' => 'Diagnóstico'
'atencion' => 'Atención'
'insercion' => 'Inserción'
'seguimiento' => 'Seguimiento'
'baja' => 'Baja'
```

**Nuevo allowed_values para `colectivo`:**
```
'larga_duracion' => 'Desempleados larga duración (>12 meses)'
'mayores_45' => 'Mayores de 45 años'
'migrantes' => 'Migrantes con permiso de residencia'
'perceptores_prestaciones' => 'Perceptores de prestaciones/subsidios'
```

**Campos nuevos en ProgramaParticipanteEi:**

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| `daci_firmado` | boolean | DACI firmado (acogida) |
| `daci_fecha_firma` | datetime | Fecha firma DACI |
| `fse_entrada_completado` | boolean | Indicadores FSE+ entrada recogidos |
| `fse_salida_completado` | boolean | Indicadores FSE+ salida recogidos |
| `fecha_inicio_programa` | datetime (date) | Fecha real inicio programa |
| `fecha_fin_programa` | datetime (date) | Fecha real fin programa |
| `motivo_baja` | list_string | Motivo si fase=baja (abandono, expulsion, finalizacion, derivacion) |
| `semana_actual` | integer | Semana del calendario (1-12, 0=no iniciado) |

#### Transiciones de fase actualizadas

```
acogida -> diagnostico (requisito: DACI firmado + FSE+ entrada)
diagnostico -> atencion (requisito: DIME completado + carril asignado)
atencion -> insercion (requisito: 10h orient. + 50h form. + tipo_insercion)
atencion -> baja (sin requisitos previos, documenta motivo)
insercion -> seguimiento (requisito: 4 meses empleo verificado)
insercion -> baja (sin requisitos previos, documenta motivo)
seguimiento -> baja (finalizacion natural del programa)
```

### 3.2 Nuevas entidades

#### ActuacionSto (entity_type: `actuacion_sto`)

**Proposito:** Registrar cada actuacion individual con todos los campos que exige el STO para la justificacion ante la Junta. Cada fila de esta entidad equivale a una linea en el informe de seguimiento trimestral del STO.

**Campos:**

| Campo | Tipo | Descripcion | Requerido |
|-------|------|-------------|-----------|
| `id` | integer (autoincrement) | Clave primaria | Auto |
| `uid` | entity_reference (user) | Owner (EntityOwnerTrait) | SI |
| `tenant_id` | entity_reference (group) | Tenant (TENANT-001) | NO |
| `participante_id` | entity_reference (programa_participante_ei) | Participante vinculado | SI |
| `tipo_actuacion` | list_string | orientacion_individual, orientacion_grupal, formacion, prospección_empresarial, tutoria_ia, sesion_mentoria | SI |
| `fecha` | datetime (date) | Fecha de la actuacion | SI |
| `hora_inicio` | string (5, HH:MM) | Hora de inicio | SI |
| `hora_fin` | string (5, HH:MM) | Hora de fin | SI |
| `duracion_minutos` | integer | Duracion calculada automaticamente en presave | Auto |
| `contenido` | text_long | Descripcion de lo realizado | SI |
| `resultado` | text_long | Resultado/conclusiones | NO |
| `lugar` | list_string | presencial_sede, presencial_empresa, online_videoconf, online_plataforma, telefonico | SI |
| `orientador_id` | entity_reference (user) | Orientador/formador que realiza la actuacion | SI |
| `fase_participante` | list_string | Fase del participante al momento de la actuacion | Auto (presave) |
| `recibo_servicio_id` | entity_reference (expediente_documento) | Recibo de servicio generado | NO |
| `firmado_participante` | boolean | Firma del participante en recibo | NO |
| `firmado_orientador` | boolean | Firma del orientador en recibo | NO |
| `vobo_sae_status` | list_string | Solo para tipo formacion: pendiente, aprobado, rechazado | Cond. |
| `vobo_sae_fecha` | datetime | Fecha del VoBo SAE | NO |
| `vobo_sae_documento_id` | entity_reference (expediente_documento) | Documento VoBo adjunto | NO |
| `grupo_participantes_ids` | string_long | Para actuaciones grupales: IDs separados por coma | Cond. |
| `sto_exportado` | boolean | Si fue incluido en exportacion STO | NO |
| `created` / `changed` | timestamps | Automaticos | Auto |

**Entity keys:** id, label (contenido truncado), uuid, owner (uid)
**Access control:** ActuacionStoAccessControlHandler (tenant isolation + owner check)
**Views data:** SI (`"views_data" = "Drupal\views\EntityViewsData"`)
**Field UI:** SI (`field_ui_base_route = "entity.actuacion_sto.settings"`)
**Form:** ActuacionStoForm extends PremiumEntityFormBase

**Logica de presave:**
- `duracion_minutos` se calcula automaticamente desde hora_inicio y hora_fin
- `fase_participante` se copia desde el ProgramaParticipanteEi vinculado
- Al guardar, incrementa el contador correspondiente en ProgramaParticipanteEi (horas_orientacion_ind, horas_orientacion_grup, horas_formacion) segun tipo_actuacion

#### InsercionLaboral (entity_type: `insercion_laboral`)

**Proposito:** Registrar el detalle completo de cada insercion laboral conseguida, diferenciando por tipo (cuenta ajena, cuenta propia, agrario) con todos los campos que exige la normativa para justificacion.

**Campos:**

| Campo | Tipo | Descripcion | Requerido |
|-------|------|-------------|-----------|
| `id` | integer | Clave primaria | Auto |
| `uid` | entity_reference (user) | Owner | SI |
| `tenant_id` | entity_reference (group) | Tenant | NO |
| `participante_id` | entity_reference (programa_participante_ei) | Participante | SI |
| `tipo_insercion` | list_string | cuenta_ajena, cuenta_propia, agrario | SI |
| `fecha_alta` | datetime (date) | Fecha alta SS/RETA | SI |
| `verificado` | boolean | Insercion verificada documentalmente | NO |
| **Cuenta Ajena:** | | | |
| `empresa_nombre` | string (255) | Nombre de la empresa | Cond. |
| `empresa_cif` | string (12) | CIF de la empresa | Cond. |
| `tipo_contrato` | list_string | indefinido, temporal, practicas, obra_servicio | Cond. |
| `jornada` | list_string | completa, parcial | Cond. |
| `horas_semanales` | integer | Horas/semana si parcial | NO |
| `codigo_cuenta_cotizacion` | string (20) | CCC de la empresa | NO |
| `sector_actividad` | string (128) | CNAE sector | NO |
| **Cuenta Propia:** | | | |
| `fecha_alta_reta` | datetime (date) | Fecha alta en RETA | Cond. |
| `cnae_actividad` | string (10) | Codigo CNAE | Cond. |
| `sector_emprendimiento` | string (128) | Sector de actividad | Cond. |
| `modelo_fiscal` | list_string | 036, 037 | NO |
| **Agrario:** | | | |
| `empresa_agraria` | string (255) | Empresa agraria | Cond. |
| `tipo_cultivo` | string (128) | Tipo de cultivo/actividad | Cond. |
| `fecha_inicio_campana` | datetime (date) | Inicio de campana | Cond. |
| `fecha_fin_campana` | datetime (date) | Fin de campana | NO |
| **Comunes:** | | | |
| `documento_acreditativo_id` | entity_reference (expediente_documento) | Contrato/alta SS adjunto | NO |
| `notas` | text_long | Observaciones | NO |
| `created` / `changed` | timestamps | | Auto |

**Entity keys:** id, label (empresa_nombre o 'Autónomo'), uuid, owner (uid)
**Access control:** InsercionLaboralAccessControlHandler (tenant isolation)
**Form:** InsercionLaboralForm extends PremiumEntityFormBase (con sections condicionales por tipo_insercion)

#### IndicadorFsePlus (entity_type: `indicador_fse_plus`)

**Proposito:** Registrar los indicadores obligatorios del Fondo Social Europeo Plus en los 3 momentos de recogida (entrada, salida, 6 meses post-salida). Estos datos son necesarios para la certificacion de gastos ante la Comision Europea.

**Campos:**

| Campo | Tipo | Descripcion | Requerido |
|-------|------|-------------|-----------|
| `id` | integer | Clave primaria | Auto |
| `uid` | entity_reference (user) | Owner | SI |
| `tenant_id` | entity_reference (group) | Tenant | NO |
| `participante_id` | entity_reference (programa_participante_ei) | Participante | SI |
| `momento_recogida` | list_string | entrada, salida, seguimiento_6m | SI |
| `fecha_recogida` | datetime | Fecha efectiva de recogida | SI |
| **Datos sociodemograficos (entrada):** | | | |
| `situacion_laboral` | list_string | desempleado_larga, desempleado_corta, inactivo, ocupado_cuenta_ajena, ocupado_cuenta_propia | Cond. |
| `nivel_educativo_isced` | list_string | isced_0 a isced_8 (clasificacion internacional) | Cond. |
| `discapacidad` | boolean | Tiene discapacidad reconocida | Cond. |
| `discapacidad_tipo` | list_string | fisica, sensorial, intelectual, mental, multiple | Cond. |
| `discapacidad_grado` | integer | Grado (33-100%) | Cond. |
| `pais_origen` | string (3) | Codigo ISO pais | Cond. |
| `nacionalidad` | string (3) | Codigo ISO nacionalidad | Cond. |
| `hogar_unipersonal` | boolean | Vive solo/a | Cond. |
| `hijos_a_cargo` | integer | Numero de hijos a cargo | Cond. |
| `zona_residencia` | list_string | urbana, rural, intermedia | Cond. |
| `situacion_sin_hogar` | boolean | Persona sin hogar | Cond. |
| `comunidad_marginada` | boolean | Pertenece a comunidad marginada | Cond. |
| **Datos de resultado (salida/6m):** | | | |
| `situacion_laboral_resultado` | list_string | Situacion al salir/6m | Cond. |
| `tipo_contrato_resultado` | list_string | indefinido, temporal, sin_contrato | Cond. |
| `cualificacion_obtenida` | boolean | Obtuvo cualificacion | Cond. |
| `tipo_cualificacion` | list_string | certificado_profesionalidad, titulo_fp, titulo_universitario, certificado_competencias, otro | Cond. |
| `mejora_situacion` | boolean | Mejoro su situacion laboral | Cond. |
| `inclusion_social` | boolean | Mejoro inclusion social | Cond. |
| **Sistema:** | | | |
| `completado` | boolean | Todos los campos requeridos rellenados | Auto |
| `notas` | text_long | Observaciones | NO |
| `created` / `changed` | timestamps | | Auto |

**Entity keys:** id, label (momento_recogida + fecha), uuid, owner (uid)
**Access control:** IndicadorFsePlusAccessControlHandler (tenant isolation)
**Form:** IndicadorFsePlusForm extends PremiumEntityFormBase (sections por momento_recogida con visibility condicional)

#### ProspeccionEmpresarial (entity_type: `prospeccion_empresarial`)

**Proposito:** Documentar las acciones de intermediacion laboral: empresas contactadas, ofertas gestionadas, matching participante-empresa. Necesario para justificar las acciones de insercion ante la Junta.

**Campos:**

| Campo | Tipo | Descripcion | Requerido |
|-------|------|-------------|-----------|
| `id` | integer | Clave primaria | Auto |
| `uid` | entity_reference (user) | Owner (orientador/coordinador) | SI |
| `tenant_id` | entity_reference (group) | Tenant | NO |
| `empresa_nombre` | string (255) | Nombre empresa contactada | SI |
| `empresa_cif` | string (12) | CIF | NO |
| `persona_contacto` | string (255) | Nombre de contacto | NO |
| `telefono_contacto` | string (20) | Telefono | NO |
| `email_contacto` | email | Email | NO |
| `sector` | string (128) | Sector actividad | SI |
| `ubicacion` | string (255) | Ubicacion (municipio, provincia) | NO |
| `tipo_accion` | list_string | llamada, email, visita_presencial, evento_networking, portal_empleo | SI |
| `fecha_contacto` | datetime (date) | Fecha de la accion | SI |
| `resultado` | list_string | interesado, no_interesado, oferta_generada, contratacion, seguimiento | SI |
| `oferta_descripcion` | text_long | Descripcion de la oferta (si aplica) | NO |
| `participantes_matcheados` | string_long | IDs de participantes propuestos (si aplica) | NO |
| `notas` | text_long | Observaciones | NO |
| `created` / `changed` | timestamps | | Auto |

**Entity keys:** id, label (empresa_nombre), uuid, owner (uid)

### 3.3 Arquitectura de servicios

#### Servicios nuevos

| Servicio | Clase | Dependencias | Funcion |
|----------|-------|-------------|---------|
| `jaraba_andalucia_ei.actuacion_sto` | ActuacionStoService | `@entity_type.manager`, `@logger.channel.jaraba_andalucia_ei`, `@?jaraba_andalucia_ei.recibo_servicio` | CRUD actuaciones, calculo duracion, incremento contadores horas en participante |
| `jaraba_andalucia_ei.recibo_servicio` | ReciboServicioService | `@entity_type.manager`, `@?ecosistema_jaraba_core.firma_digital`, `@jaraba_andalucia_ei.expediente`, `@logger.channel.jaraba_andalucia_ei`, `@file_system` | Generacion PDF recibo de servicio universal (orientacion + formacion + mentoria), firma dual |
| `jaraba_andalucia_ei.indicadores_fse` | IndicadoresFseService | `@entity_type.manager`, `@logger.channel.jaraba_andalucia_ei` | CRUD indicadores FSE+, validacion completitud, exportacion para certificacion |
| `jaraba_andalucia_ei.insercion_laboral` | InsercionLaboralService | `@entity_type.manager`, `@jaraba_andalucia_ei.fase_transition_manager`, `@logger.channel.jaraba_andalucia_ei` | Registro insercion con validacion por tipo, trigger transicion a insercion |
| `jaraba_andalucia_ei.daci` | DaciService | `@entity_type.manager`, `@?ecosistema_jaraba_core.firma_digital`, `@jaraba_andalucia_ei.expediente`, `@logger.channel.jaraba_andalucia_ei` | Generacion PDF DACI, flujo firma, transicion acogida->diagnostico |
| `jaraba_andalucia_ei.calendario_programa` | CalendarioProgramaService | `@entity_type.manager`, `@?jaraba_andalucia_ei.copilot_bridge`, `@logger.channel.jaraba_andalucia_ei` | Mapping semanas a sesiones/pildoras/experimentos, progreso semanal |
| `jaraba_andalucia_ei.alertas_normativas` | AlertasNormativasService | `@entity_type.manager`, `@logger.channel.jaraba_andalucia_ei` | Deteccion plazos criticos, participantes inactivos, docs vencidos |
| `jaraba_andalucia_ei.prospeccion` | ProspeccionEmpresarialService | `@entity_type.manager`, `@logger.channel.jaraba_andalucia_ei` | CRUD prospeccion, matching participante-empresa |

#### Servicios existentes a modificar

| Servicio | Modificacion |
|----------|-------------|
| `FaseTransitionManager` | Ampliar transiciones validas a 6 fases. Anadir requisitos por fase (DACI para acogida->diagnostico, DIME para diagnostico->atencion, etc.) |
| `StoExportService` | Incluir ActuacionSto en exportacion CSV. Ampliar formato con campos FSE+ e insercion detallada |
| `ExpedienteCompletenessService` | Ampliar checklists por fase con nuevos documentos (DACI, indicadores FSE+, insercion detallada) |
| `CoordinadorHubService` | Anadir KPIs de actuaciones, indicadores FSE+, prospeccion |
| `AndaluciaEiCopilotBridgeService` | Anadir bridge para resultado DIME -> carril automatico |

### 3.4 Mapa de rutas frontend

#### Rutas nuevas

| Ruta | Controller | Metodo | Template | Permiso |
|------|-----------|--------|----------|---------|
| `/programa/actuaciones` | ActuacionesStoController | listActuaciones | actuaciones-sto.html.twig | view programa participante ei |
| `/programa/actuaciones/nueva` | (slide-panel) | formActuacion | (PremiumEntityFormBase) | create actuacion sto |
| `/programa/insercion/{participante_id}` | InsercionLaboralController | detalleInsercion | insercion-laboral.html.twig | view programa participante ei |
| `/programa/fse-indicadores/{participante_id}` | IndicadoresFseController | formulario | indicadores-fse.html.twig | edit programa participante ei |
| `/programa/prospeccion` | ProspeccionController | listProspeccion | prospeccion-empresarial.html.twig | view prospeccion empresarial |
| `/programa/calendario` | CalendarioController | calendario | calendario-programa.html.twig | access content |

#### Rutas API nuevas

| Ruta | Metodo | Controller | CSRF | Descripcion |
|------|--------|-----------|------|-------------|
| `GET /api/v1/andalucia-ei/actuaciones` | GET | ActuacionesStoApiController::list | NO | Lista actuaciones filtrada |
| `POST /api/v1/andalucia-ei/actuacion` | POST | ActuacionesStoApiController::create | SI | Crear actuacion |
| `GET /api/v1/andalucia-ei/alertas` | GET | AlertasApiController::getAlertas | NO | Alertas normativas activas |
| `POST /api/v1/andalucia-ei/recibo/{actuacion_id}/generar` | POST | ReciboServicioApiController::generar | SI | Generar recibo PDF |
| `POST /api/v1/andalucia-ei/recibo/{documento_id}/firmar` | POST | ReciboServicioApiController::firmar | SI | Firmar recibo |
| `POST /api/v1/andalucia-ei/daci/{participante_id}/generar` | POST | DaciApiController::generar | SI | Generar DACI |
| `GET /api/v1/andalucia-ei/calendario/{participante_id}` | GET | CalendarioApiController::get | NO | Estado calendario |

### 3.5 Diagrama de flujo del itinerario completo

```
SOLICITUD (SolicitudEi)
  |
  v
TRIAGE IA (SolicitudTriageService)
  |
  +-- Rechazado --> FIN
  |
  +-- Admitido
        |
        v
ALTA PARTICIPANTE (ProgramaParticipanteEi, fase=acogida)
  |
  v
FASE ACOGIDA
  |-- Firma DACI (DaciService)
  |-- Recogida Indicadores FSE+ Entrada (IndicadoresFseService)
  |-- Documentacion inicial (ExpedienteDocumento: DNI, empadronamiento, etc.)
  |
  [DACI firmado + FSE+ entrada OK]
  |
  v
FASE DIAGNOSTICO
  |-- DIME (10 preguntas via CopilotBridgeService)
  |-- Asignacion carril automatica (IMPULSO <=9 / ACELERA >=10)
  |-- IPI (Itinerario Personalizado de Insercion)
  |
  [DIME completado + carril asignado]
  |
  v
FASE ATENCION (12 semanas)
  |-- Orientacion individual (ActuacionSto tipo=orientacion_individual)
  |     +-- Recibo de Servicio firmado
  |-- Orientacion grupal (ActuacionSto tipo=orientacion_grupal)
  |     +-- Recibo de Servicio firmado
  |-- Formacion (ActuacionSto tipo=formacion)
  |     +-- VoBo SAE previo
  |     +-- Recibo de Servicio firmado
  |-- Mentoria humana (MentoringSession via jaraba_mentoring)
  |     +-- Hoja de servicio firmada (HojaServicioMentoriaService)
  |-- Mentoria IA (AiMentorshipTracker)
  |-- Sesiones semanales (CalendarioProgramaService)
  |-- Pildoras formativas (20 pildoras)
  |-- Experimentos de validacion (44 experimentos)
  |
  [10h orient. + 50h form. + insercion conseguida]
  |
  v
FASE INSERCION
  |-- Registro InsercionLaboral (detalle por tipo)
  |-- Orientacion de seguimiento (40h)
  |-- Prospeccion empresarial (ProspeccionEmpresarial)
  |-- 4 meses de empleo verificado
  |
  [4 meses empleo verificado]
  |
  v
FASE SEGUIMIENTO
  |-- Indicadores FSE+ Salida
  |-- Indicadores FSE+ 6 meses post
  |-- Informe final de progreso
  |
  v
BAJA / FINALIZACION
  |-- Indicadores FSE+ Salida (si no en seguimiento)
  |-- Documentar motivo
  |-- Justificacion economica
```

---

## 4. Requisitos Previos

### 4.1 Software

| Software | Version | Verificacion |
|----------|---------|-------------|
| PHP | 8.4+ | `lando ssh -c "php -v"` |
| Drupal | 11.x | `lando drush status` |
| MariaDB | 10.11+ | `lando ssh -c "mysql --version"` |
| DomPDF | 2.0+ | `lando ssh -c "composer show dompdf/dompdf"` |
| Dart Sass | 1.71+ | `npx sass --version` (desde web/themes/custom/ecosistema_jaraba_theme/) |

### 4.2 Modulos dependientes

| Modulo | Obligatorio | Servicios consumidos |
|--------|------------|---------------------|
| `ecosistema_jaraba_core` | SI | TenantContext, TenantBridge, FirmaDigital, PremiumEntityFormBase |
| `jaraba_mentoring` | Opcional (@?) | MentoringSession para hoja de servicio |
| `jaraba_copilot_v2` | Opcional (@?) | DIME, BMC, EntrepreneurProfile |
| `jaraba_lms` | Opcional (@?) | Cursos y catalogos formativos |
| `jaraba_legal_vault` | Opcional (@?) | Almacenamiento encriptado |

### 4.3 Configuracion previa

- Certificado PKCS#12 para firma digital (SECRET-MGMT-001)
- Al menos un Group (edicion) creado
- Permisos configurados: `administer andalucia ei`, `view programa participante ei`, `create actuacion sto`, `view actuacion sto`, `create insercion laboral`, `create indicador fse plus`, `create prospeccion empresarial`

---

## 5. Fases de Implementacion

### 5.1 Fase 1 — Fases PIIL Completas y Colectivos Corregidos (P0)

**Resuelve:** GAP-PIIL-01 (fases incompletas), GAP-PIIL-07 (colectivos incorrectos)

**Objetivo:** Ampliar el campo `fase_actual` de 3 a 6 valores para reflejar el itinerario completo del PIIL. Corregir el campo `colectivo` para reflejar los colectivos activos de esta edicion. Actualizar FaseTransitionManager con las nuevas transiciones validas.

**Problema detallado:** El campo `fase_actual` solo tiene `atencion`, `insercion`, `baja`. Esto impide registrar que un participante esta en fase de acogida (recogida de documentos, firma DACI), en diagnostico (evaluacion DIME, asignacion de carril), o en seguimiento post-insercion (verificacion de mantenimiento del empleo). El STO exige conocer la fase exacta del participante en todo momento.

El campo `colectivo` incluye `jovenes` (Garantia Juvenil, que NO es de esta edicion) y omite `migrantes` y `perceptores_prestaciones` que SI son colectivos diana.

**Solucion tecnica:**

#### 5.1.1 Modificar ProgramaParticipanteEi::baseFieldDefinitions()

En `web/modules/custom/jaraba_andalucia_ei/src/Entity/ProgramaParticipanteEi.php`:

**Campo `fase_actual`** — ampliar allowed_values:
```php
$fields['fase_actual'] = BaseFieldDefinition::create('list_string')
    ->setLabel(t('Fase PIIL'))
    ->setDescription(t('Fase actual del participante en el programa PIIL.'))
    ->setRequired(TRUE)
    ->setSetting('allowed_values', [
        'acogida' => t('Acogida'),
        'diagnostico' => t('Diagnóstico'),
        'atencion' => t('Atención'),
        'insercion' => t('Inserción'),
        'seguimiento' => t('Seguimiento'),
        'baja' => t('Baja'),
    ])
    ->setDefaultValue('acogida')
    ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -5,
    ])
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);
```

**Campo `colectivo`** — corregir allowed_values:
```php
$fields['colectivo'] = BaseFieldDefinition::create('list_string')
    ->setLabel(t('Colectivo'))
    ->setDescription(t('Colectivo destino del participante según la convocatoria PIIL.'))
    ->setRequired(TRUE)
    ->setSetting('allowed_values', [
        'larga_duracion' => t('Desempleados larga duración (>12 meses)'),
        'mayores_45' => t('Mayores de 45 años'),
        'migrantes' => t('Migrantes con permiso de residencia'),
        'perceptores_prestaciones' => t('Perceptores de prestaciones/subsidios'),
    ])
    ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -8,
    ])
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);
```

**Campos nuevos** — anadir al final de baseFieldDefinitions():
```php
// === CAMPOS DE CONTROL DE ITINERARIO ===

$fields['daci_firmado'] = BaseFieldDefinition::create('boolean')
    ->setLabel(t('DACI Firmado'))
    ->setDescription(t('Indica si el Documento de Aceptación de Compromisos e Información ha sido firmado.'))
    ->setDefaultValue(FALSE)
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);

$fields['daci_fecha_firma'] = BaseFieldDefinition::create('datetime')
    ->setLabel(t('Fecha Firma DACI'))
    ->setDescription(t('Fecha de firma del DACI.'))
    ->setSetting('datetime_type', 'datetime')
    ->setDisplayConfigurable('view', TRUE);

$fields['fse_entrada_completado'] = BaseFieldDefinition::create('boolean')
    ->setLabel(t('FSE+ Entrada Completado'))
    ->setDescription(t('Indicadores FSE+ de entrada recogidos.'))
    ->setDefaultValue(FALSE)
    ->setDisplayConfigurable('view', TRUE);

$fields['fse_salida_completado'] = BaseFieldDefinition::create('boolean')
    ->setLabel(t('FSE+ Salida Completado'))
    ->setDescription(t('Indicadores FSE+ de salida recogidos.'))
    ->setDefaultValue(FALSE)
    ->setDisplayConfigurable('view', TRUE);

$fields['fecha_inicio_programa'] = BaseFieldDefinition::create('datetime')
    ->setLabel(t('Fecha Inicio Programa'))
    ->setDescription(t('Fecha real de inicio en el programa.'))
    ->setSetting('datetime_type', 'date')
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);

$fields['fecha_fin_programa'] = BaseFieldDefinition::create('datetime')
    ->setLabel(t('Fecha Fin Programa'))
    ->setDescription(t('Fecha de finalizacion o baja del programa.'))
    ->setSetting('datetime_type', 'date')
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);

$fields['motivo_baja'] = BaseFieldDefinition::create('list_string')
    ->setLabel(t('Motivo de Baja'))
    ->setDescription(t('Motivo si el participante está en fase de baja.'))
    ->setSetting('allowed_values', [
        'abandono' => t('Abandono voluntario'),
        'expulsion' => t('Expulsión por incumplimiento'),
        'finalizacion' => t('Finalización del programa'),
        'derivacion' => t('Derivación a otro programa'),
        'insercion_temprana' => t('Inserción laboral antes de completar'),
    ])
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);

$fields['semana_actual'] = BaseFieldDefinition::create('integer')
    ->setLabel(t('Semana del Programa'))
    ->setDescription(t('Semana actual del calendario del programa (1-12, 0=no iniciado).'))
    ->setDefaultValue(0)
    ->setSetting('min', 0)
    ->setSetting('max', 12)
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);
```

#### 5.1.2 Actualizar FaseTransitionManager

Modificar `web/modules/custom/jaraba_andalucia_ei/src/Service/FaseTransitionManager.php`:

**Ampliar transiciones validas:**
```php
$transiciones_validas = [
    'acogida' => ['diagnostico', 'baja'],
    'diagnostico' => ['atencion', 'baja'],
    'atencion' => ['insercion', 'baja'],
    'insercion' => ['seguimiento', 'baja'],
    'seguimiento' => ['baja'],
    'baja' => [],
];
```

**Requisitos por transicion:**
```php
// Acogida -> Diagnostico: DACI firmado + FSE+ entrada.
if ($nueva_fase === 'diagnostico') {
    if (!$participante->get('daci_firmado')->value) {
        return [
            'success' => FALSE,
            'message' => t('El DACI debe estar firmado antes de pasar a diagnóstico.'),
        ];
    }
    if (!$participante->get('fse_entrada_completado')->value) {
        return [
            'success' => FALSE,
            'message' => t('Los indicadores FSE+ de entrada deben estar completados.'),
        ];
    }
}

// Diagnostico -> Atencion: Carril asignado.
if ($nueva_fase === 'atencion') {
    if (empty($participante->get('carril')->value)) {
        return [
            'success' => FALSE,
            'message' => t('El carril del programa debe estar asignado (diagnóstico DIME).'),
        ];
    }
}

// Insercion -> Seguimiento: Verificar 4 meses.
if ($nueva_fase === 'seguimiento') {
    // Verificar que existe InsercionLaboral con fecha_alta >= 4 meses.
    // (logica delegada a InsercionLaboralService)
}
```

**Documentar motivo de baja:**
```php
if ($nueva_fase === 'baja') {
    if (empty($contexto['motivo_baja'])) {
        return [
            'success' => FALSE,
            'message' => t('Debe especificar el motivo de baja.'),
        ];
    }
    $participante->set('motivo_baja', $contexto['motivo_baja']);
    $participante->set('fecha_fin_programa', date('Y-m-d'));
}
```

#### 5.1.3 Actualizar CoordinadorHubService y API

Actualizar `VALID_PHASES` en CoordinadorHubService:
```php
private const VALID_PHASES = ['acogida', 'diagnostico', 'atencion', 'insercion', 'seguimiento', 'baja'];
```

Actualizar `CoordinadorHubApiController` y `coordinador-hub.js` con las nuevas fases.

Actualizar `drupalSettings.jarabaAndaluciaEi.hub.phases` en CoordinadorDashboardController.

#### 5.1.4 hook_update_N() (UPDATE-HOOK-REQUIRED-001)

En `jaraba_andalucia_ei.install`:
```php
/**
 * Expand fase_actual to 6 phases, fix colectivo values, add itinerary control fields.
 */
function jaraba_andalucia_ei_update_10020(): void {
  try {
    $manager = \Drupal::entityDefinitionUpdateManager();
    $manager->installEntityType(
      \Drupal::entityTypeManager()->getDefinition('programa_participante_ei')
    );
  }
  catch (\Throwable $e) {
    \Drupal::logger('jaraba_andalucia_ei')->error(
      'Error updating ProgramaParticipanteEi: @msg', ['@msg' => $e->getMessage()]
    );
  }
}
```

**Nota (UPDATE-HOOK-CATCH-001):** Usa `\Throwable`, NUNCA `\Exception`.

**Migracion de datos existentes:**
Los participantes actuales que tienen `fase_actual = 'atencion'` no necesitan cambio (el valor sigue existiendo). Los que tienen `colectivo = 'jovenes'` necesitan actualizacion manual por el coordinador (no se puede inferir automaticamente a que colectivo pertenecen realmente).

**Ficheros modificados:**
- `web/modules/custom/jaraba_andalucia_ei/src/Entity/ProgramaParticipanteEi.php`
- `web/modules/custom/jaraba_andalucia_ei/src/Service/FaseTransitionManager.php`
- `web/modules/custom/jaraba_andalucia_ei/src/Service/CoordinadorHubService.php`
- `web/modules/custom/jaraba_andalucia_ei/src/Controller/CoordinadorHubApiController.php`
- `web/modules/custom/jaraba_andalucia_ei/src/Controller/CoordinadorDashboardController.php`
- `web/modules/custom/jaraba_andalucia_ei/js/coordinador-hub.js`
- `web/modules/custom/jaraba_andalucia_ei/jaraba_andalucia_ei.install`

**Verificacion:**
```bash
lando drush updatedb -y
lando drush entity:updates
# Verificar que las 6 fases aparecen en el formulario admin de ProgramaParticipanteEi
```

---

### 5.2 Fase 2 — Entidad ActuacionSto: Tracking Granular de Actuaciones (P0)

**Resuelve:** GAP-PIIL-02 (sin entidad actuaciones STO)

**Objetivo:** Crear la entidad `ActuacionSto` para registrar cada actuacion individual (orientacion individual/grupal, formacion, prospeccion, tutoria IA, sesion mentoria) con todos los campos que exige el STO. Cada guardado de esta entidad incrementa automaticamente los contadores de horas en ProgramaParticipanteEi.

**Problema detallado:** El SaaS solo tiene contadores decimales de horas (`horas_orientacion_ind`, `horas_orientacion_grup`, `horas_formacion`), pero NO registra las actuaciones individuales. El STO exige saber CADA actuacion: que dia fue, a que hora empezo, a que hora termino, que se hizo, donde fue, quien la realizo. Sin este registro granular, no se puede generar el informe de seguimiento trimestral ni la justificacion final de la subvencion.

**Solucion tecnica:**

#### 5.2.1 Crear la entidad ActuacionSto

Crear `web/modules/custom/jaraba_andalucia_ei/src/Entity/ActuacionSto.php`:

**Anotacion (cumple AUDIT-CONS-001, Views, Field UI):**
```php
/**
 * Defines the Actuacion STO entity.
 *
 * Registra cada actuacion individual del programa PIIL con los campos
 * obligatorios del STO (Sistema Telemático de Orientación).
 *
 * @ContentEntityType(
 *   id = "actuacion_sto",
 *   label = @Translation("Actuación STO"),
 *   label_collection = @Translation("Actuaciones STO"),
 *   label_singular = @Translation("actuación STO"),
 *   label_plural = @Translation("actuaciones STO"),
 *   label_count = @PluralTranslation(
 *     singular = "@count actuación STO",
 *     plural = "@count actuaciones STO",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\jaraba_andalucia_ei\Access\ActuacionStoAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\jaraba_andalucia_ei\Form\ActuacionStoForm",
 *       "add" = "Drupal\jaraba_andalucia_ei\Form\ActuacionStoForm",
 *       "edit" = "Drupal\jaraba_andalucia_ei\Form\ActuacionStoForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "actuacion_sto",
 *   admin_permission = "administer andalucia ei",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "contenido",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/actuacion-sto/{actuacion_sto}",
 *     "add-form" = "/admin/content/actuacion-sto/add",
 *     "edit-form" = "/admin/content/actuacion-sto/{actuacion_sto}/edit",
 *     "delete-form" = "/admin/content/actuacion-sto/{actuacion_sto}/delete",
 *     "collection" = "/admin/content/actuacion-sto",
 *   },
 *   field_ui_base_route = "entity.actuacion_sto.settings",
 * )
 */
```

**Reglas de presave (en entidad o modulo):**
```php
public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);

    // Calcular duracion_minutos automaticamente.
    $inicio = $this->get('hora_inicio')->value;
    $fin = $this->get('hora_fin')->value;
    if ($inicio && $fin) {
        $diff = (strtotime("1970-01-01 $fin") - strtotime("1970-01-01 $inicio")) / 60;
        $this->set('duracion_minutos', max(0, (int) $diff));
    }

    // Copiar fase_participante desde el participante vinculado.
    $participante = $this->get('participante_id')->entity;
    if ($participante) {
        $this->set('fase_participante', $participante->get('fase_actual')->value);
    }
}
```

**Hook en .module para incrementar contadores (PRESAVE-RESILIENCE-001):**
```php
function jaraba_andalucia_ei_actuacion_sto_insert(EntityInterface $entity): void {
    try {
        $participante = $entity->get('participante_id')->entity;
        if (!$participante) {
            return;
        }
        $tipo = $entity->get('tipo_actuacion')->value;
        $horas = ($entity->get('duracion_minutos')->value ?? 0) / 60;

        $campo_map = [
            'orientacion_individual' => 'horas_orientacion_ind',
            'orientacion_grupal' => 'horas_orientacion_grup',
            'formacion' => 'horas_formacion',
            'tutoria_ia' => 'horas_mentoria_ia',
            'sesion_mentoria' => 'horas_mentoria_humana',
        ];

        if (isset($campo_map[$tipo])) {
            $campo = $campo_map[$tipo];
            $actual = (float) ($participante->get($campo)->value ?? 0);
            $participante->set($campo, round($actual + $horas, 2));
            $participante->save();
        }
    }
    catch (\Throwable $e) {
        \Drupal::logger('jaraba_andalucia_ei')->error(
            'Error incrementando horas participante desde actuación: @msg',
            ['@msg' => $e->getMessage()]
        );
    }
}
```

#### 5.2.2 AccessControlHandler (TENANT-ISOLATION-ACCESS-001)

Crear `web/modules/custom/jaraba_andalucia_ei/src/Access/ActuacionStoAccessControlHandler.php`:

```php
// Implementar EntityHandlerInterface para DI.
// checkAccess() verifica:
// - 'view': tenant match (ACCESS-STRICT-001: (int) comparisons)
// - 'update': tenant match + (owner match OR 'administer andalucia ei')
// - 'delete': 'administer andalucia ei' + tenant match
// checkCreateAccess() verifica permiso 'create actuacion sto'
```

#### 5.2.3 Form (PREMIUM-FORMS-PATTERN-001)

Crear `web/modules/custom/jaraba_andalucia_ei/src/Form/ActuacionStoForm.php`:

```php
class ActuacionStoForm extends PremiumEntityFormBase {
    public function getSectionDefinitions(): array {
        return [
            'datos_actuacion' => [
                'title' => $this->t('Datos de la Actuación'),
                'icon' => ['category' => 'actions', 'name' => 'record'],
                'fields' => ['participante_id', 'tipo_actuacion', 'fecha', 'hora_inicio', 'hora_fin', 'lugar'],
            ],
            'contenido' => [
                'title' => $this->t('Contenido y Resultado'),
                'icon' => ['category' => 'files', 'name' => 'document'],
                'fields' => ['contenido', 'resultado'],
            ],
            'firma' => [
                'title' => $this->t('Firma y Recibo'),
                'icon' => ['category' => 'security', 'name' => 'signature'],
                'fields' => ['firmado_participante', 'firmado_orientador', 'recibo_servicio_id'],
            ],
            'vobo_sae' => [
                'title' => $this->t('VoBo SAE (solo formación)'),
                'icon' => ['category' => 'status', 'name' => 'approved'],
                'fields' => ['vobo_sae_status', 'vobo_sae_fecha', 'vobo_sae_documento_id'],
            ],
        ];
    }

    public function getFormIcon(): array {
        return ['category' => 'actions', 'name' => 'record'];
    }
}
```

#### 5.2.4 hook_update_N(), routing, permissions, services

**Install hook:**
```php
function jaraba_andalucia_ei_update_10021(): void {
  try {
    $manager = \Drupal::entityDefinitionUpdateManager();
    $manager->installEntityType(
      \Drupal::entityTypeManager()->getDefinition('actuacion_sto')
    );
  }
  catch (\Throwable $e) {
    \Drupal::logger('jaraba_andalucia_ei')->error(
      'Error installing ActuacionSto: @msg', ['@msg' => $e->getMessage()]
    );
  }
}
```

**Permisos nuevos en .permissions.yml:**
```yaml
create actuacion sto:
  title: 'Crear actuaciones STO'
  description: 'Permite crear nuevas actuaciones de orientación, formación, etc.'
view actuacion sto:
  title: 'Ver actuaciones STO'
  description: 'Permite ver las actuaciones registradas.'
edit any actuacion sto:
  title: 'Editar cualquier actuación STO'
  description: 'Permite editar actuaciones de cualquier orientador.'
delete any actuacion sto:
  title: 'Eliminar actuaciones STO'
  description: 'Permite eliminar actuaciones.'
```

**Ruta settings (FIELD-UI-SETTINGS-TAB-001):**
```yaml
entity.actuacion_sto.settings:
  path: '/admin/structure/actuacion-sto'
  defaults:
    _form: '\Drupal\Core\Entity\EntityTypeForm'
    _title: 'Actuaciones STO Settings'
    entity_type_id: actuacion_sto
  requirements:
    _permission: 'administer andalucia ei'
```

**Preprocess (ENTITY-PREPROCESS-001) en .module:**
```php
function jaraba_andalucia_ei_preprocess_actuacion_sto(&$variables) {
  $entity = $variables['elements']['#actuacion_sto'];
  $variables['tipo_actuacion'] = $entity->get('tipo_actuacion')->value ?? '';
  $variables['fecha'] = $entity->get('fecha')->value ?? '';
  // ... etc
}
```

**Ficheros nuevos:**
- `web/modules/custom/jaraba_andalucia_ei/src/Entity/ActuacionSto.php`
- `web/modules/custom/jaraba_andalucia_ei/src/Entity/ActuacionStoInterface.php`
- `web/modules/custom/jaraba_andalucia_ei/src/Access/ActuacionStoAccessControlHandler.php`
- `web/modules/custom/jaraba_andalucia_ei/src/Form/ActuacionStoForm.php`
- `web/modules/custom/jaraba_andalucia_ei/src/Service/ActuacionStoService.php`
- `web/modules/custom/jaraba_andalucia_ei/templates/actuacion-sto.html.twig`

**Ficheros modificados:**
- `jaraba_andalucia_ei.module` (hook_theme + preprocess + insert hook)
- `jaraba_andalucia_ei.routing.yml` (rutas admin + settings + API)
- `jaraba_andalucia_ei.permissions.yml`
- `jaraba_andalucia_ei.services.yml`
- `jaraba_andalucia_ei.install` (hook_update_10021)

---

### 5.3 Fase 3 — Indicadores FSE+ (P0)

**Resuelve:** GAP-PIIL-03 (sin indicadores FSE+)

**Objetivo:** Crear la entidad `IndicadorFsePlus` para registrar los datos sociodemograficos y de resultado exigidos por el Fondo Social Europeo Plus en 3 momentos: entrada, salida, y 6 meses post-salida. Sin estos datos, la subvencion FSE+ (172.125 EUR) no es certificable.

**Problema detallado:** El Reglamento FSE+ (2021/1057) y las Bases Reguladoras del PIIL exigen la recogida de indicadores comunes en 3 momentos. Estos datos se agregan a nivel regional y nacional para reportar a la Comision Europea. Actualmente no existe ningun campo ni entidad para recoger esta informacion en el SaaS.

**Solucion tecnica:**

Seguir el mismo patron de Fase 2 (entidad, access handler, PremiumEntityFormBase, Views, Field UI, hook_update_N, preprocess, template). La entidad IndicadorFsePlus tendra sections condicionales en el formulario segun el `momento_recogida`:

- **`entrada`**: Muestra campos sociodemograficos (situacion laboral, nivel educativo, discapacidad, pais origen, hogar, hijos, zona, sin hogar, comunidad marginada)
- **`salida`**: Muestra campos de resultado inmediato (situacion laboral resultado, tipo contrato, cualificacion obtenida)
- **`seguimiento_6m`**: Muestra campos de resultado a 6 meses (situacion laboral, contrato, mejora, inclusion social)

**Integracion con fase de participante:**
- Al transitar acogida->diagnostico: verificar que existe IndicadorFsePlus con momento=entrada y completado=TRUE
- Al transitar a baja o seguimiento: solicitar automaticamente recogida de IndicadorFsePlus con momento=salida
- Cron job para detectar participantes con salida hace 5.5 meses y alertar sobre recogida de seguimiento_6m

**Ficheros nuevos:**
- `src/Entity/IndicadorFsePlus.php`, `src/Entity/IndicadorFsePlusInterface.php`
- `src/Access/IndicadorFsePlusAccessControlHandler.php`
- `src/Form/IndicadorFsePlusForm.php`
- `src/Service/IndicadoresFseService.php`
- `templates/indicador-fse-plus.html.twig`

---

### 5.4 Fase 4 — Insercion Laboral Detallada (P0)

**Resuelve:** GAP-PIIL-05 (insercion sin detalle)

**Objetivo:** Crear la entidad `InsercionLaboral` con campos diferenciados por tipo (cuenta ajena, cuenta propia, agrario). Vincular con ProgramaParticipanteEi y trigger transicion de fase.

**Problema detallado:** El campo `tipo_insercion` de ProgramaParticipanteEi solo almacena el tipo (cuenta_ajena/cuenta_propia/agrario) sin ningun detalle. La normativa exige documentar empresa, CIF, tipo de contrato, jornada, fecha alta SS, CNAE, etc. segun el tipo de insercion.

**Solucion tecnica:**

La entidad InsercionLaboral (descrita en seccion 3.2) se crea con el patron completo. El formulario PremiumEntityFormBase tendra sections condicionales via JS:

```javascript
// En el form, mostrar/ocultar sections segun tipo_insercion:
// tipo_insercion = 'cuenta_ajena' -> mostrar section empresa + contrato
// tipo_insercion = 'cuenta_propia' -> mostrar section autonomo
// tipo_insercion = 'agrario' -> mostrar section agrario
```

**Integracion con FaseTransitionManager:**
Al crear una InsercionLaboral, si el participante esta en fase `atencion`, se dispara automaticamente la transicion a `insercion` (si cumple requisitos de horas).

**Ficheros nuevos:**
- `src/Entity/InsercionLaboral.php`, `src/Entity/InsercionLaboralInterface.php`
- `src/Access/InsercionLaboralAccessControlHandler.php`
- `src/Form/InsercionLaboralForm.php`
- `src/Service/InsercionLaboralService.php`
- `templates/insercion-laboral.html.twig`

---

### 5.5 Fase 5 — DACI Digital y Flujo de Acogida (P0)

**Resuelve:** GAP-PIIL-06 (DACI digital inexistente)

**Objetivo:** Implementar el flujo completo de acogida: generacion del DACI (Documento de Aceptacion de Compromisos e Informacion), firma digital del participante, y marcado del flag `daci_firmado` en ProgramaParticipanteEi.

**Problema detallado:** El DACI es el primer documento que firma el participante al incorporarse al programa. Contiene informacion sobre derechos, obligaciones, tratamiento de datos (RGPD), compromisos de asistencia, y consecuencias del incumplimiento. Sin DACI firmado, la participacion no tiene validez formal.

**Solucion tecnica:**

#### 5.5.1 DaciService

Crear `web/modules/custom/jaraba_andalucia_ei/src/Service/DaciService.php`:

**Metodos:**
- `generarDaci(ProgramaParticipanteEi $participante): ?ExpedienteDocumentoInterface` — Genera PDF DACI con datos del participante, fecha, texto legal del programa, espacio para firma. Almacena como ExpedienteDocumento categoria `programa_contrato`.
- `firmarDaci(int $documentoId): bool` — Procesa firma via FirmaDigitalService. Al firmar exitosamente, marca `daci_firmado=TRUE` y `daci_fecha_firma=now()` en ProgramaParticipanteEi.

**Template PDF:**
Crear `templates/daci-documento.html.twig` con el texto legal del programa, datos del participante, fecha, espacio de firma.

**Nota (CSS en PDF):** DomPDF NO soporta var(--ej-*). Usar hex directos en el CSS embebido del PDF. Esto es aceptable porque el PDF no es una pagina web.

**Flujo en frontend:**
1. Orientador accede a la ficha del participante (fase=acogida)
2. Boton "Generar DACI" (abre slide-panel con preview del documento)
3. Boton "Firmar" que invoca DaciService::firmarDaci() via API
4. Al firmar, la fase queda habilitada para transitar a `diagnostico`

#### 5.5.2 Nuevo endpoint API

```yaml
jaraba_andalucia_ei.api.daci.generar:
  path: '/api/v1/andalucia-ei/daci/{participante_id}/generar'
  defaults:
    _controller: '\Drupal\jaraba_andalucia_ei\Controller\DaciApiController::generar'
  methods: [POST]
  requirements:
    _permission: 'view programa participante ei'
    _csrf_request_header_token: 'TRUE'
    participante_id: '\d+'
```

**Ficheros nuevos:**
- `src/Service/DaciService.php`
- `src/Controller/DaciApiController.php`
- `templates/daci-documento.html.twig`

---

### 5.6 Fase 6 — Recibo de Servicio Universal (P0)

**Resuelve:** GAP-PIIL-13 (recibo solo para mentoria)

**Objetivo:** Crear un servicio universal de generacion de Recibos de Servicio PDF con firma dual (participante + orientador/formador) para TODA actuacion, no solo mentoria. El Recibo de Servicio es el documento que acredita que la actuacion se realizo y tiene valor justificativo ante la Junta.

**Problema detallado:** `HojaServicioMentoriaService` solo genera recibos para sesiones de mentoria. Pero la normativa exige un recibo firmado por CADA actuacion (orientacion individual, grupal, formacion). Sin estos recibos, las horas no son justificables.

**Solucion tecnica:**

#### 5.6.1 ReciboServicioService

Crear `web/modules/custom/jaraba_andalucia_ei/src/Service/ReciboServicioService.php`:

**Metodos:**
- `generarRecibo(ActuacionSto $actuacion): ?ExpedienteDocumentoInterface` — Genera PDF con datos de la actuacion (tipo, fecha, horas, contenido), datos del participante (nombre, DNI, expediente), datos del orientador, codigo proyecto. Almacena como ExpedienteDocumento y vincula via `recibo_servicio_id`.
- `firmarRecibo(int $documentoId, string $firmante): bool` — Firma dual (participante + orientador). Al firmar ambos, marca `firmado_participante=TRUE` y `firmado_orientador=TRUE` en la ActuacionSto.
- `generarReciboDiario(int $participanteId, string $fecha): ?ExpedienteDocumentoInterface` — Para dias con multiples actuaciones, genera un recibo consolidado diario.

**Template PDF `recibo-servicio.html.twig`:**
```
+---------------------------------------------------+
|  LOGO JARABA          RECIBO DE SERVICIO            |
|  Código Proyecto: 679  PIIL CV 2025                 |
+---------------------------------------------------+
|  DATOS DEL PARTICIPANTE                             |
|  Nombre: [...]  DNI: [...]  Expediente STO: [...]   |
|  Colectivo: [...]  Provincia: [...]                  |
+---------------------------------------------------+
|  DATOS DE LA ACTUACIÓN                              |
|  Tipo: [Orientación Individual/Grupal/Formación]     |
|  Fecha: [dd/mm/yyyy]                                 |
|  Hora inicio: [HH:MM]  Hora fin: [HH:MM]            |
|  Duración: [X h XX min]                              |
|  Lugar: [Presencial sede/Online/...]                 |
+---------------------------------------------------+
|  CONTENIDO DE LA ACTUACIÓN                          |
|  [texto libre]                                       |
+---------------------------------------------------+
|  RESULTADO / OBSERVACIONES                          |
|  [texto libre]                                       |
+---------------------------------------------------+
|                                                      |
|  FIRMA PARTICIPANTE        FIRMA ORIENTADOR/FORMADOR |
|  [estado/fecha]            [estado/fecha]            |
+---------------------------------------------------+
|  Entidad ejecutora: PED S.L.                        |
|  Programa: PIIL CV 2025 - Andalucía +ei             |
|  Cofinanciado por Junta de Andalucía + FSE+          |
+---------------------------------------------------+
```

**Integracion con ActuacionSto:**
En `jaraba_andalucia_ei_actuacion_sto_insert()`, despues de incrementar contadores, auto-generar recibo:
```php
try {
    if (\Drupal::hasService('jaraba_andalucia_ei.recibo_servicio')) {
        \Drupal::service('jaraba_andalucia_ei.recibo_servicio')
            ->generarRecibo($entity);
    }
}
catch (\Throwable $e) {
    // La actuacion se guarda aunque falle el recibo.
}
```

**Ficheros nuevos:**
- `src/Service/ReciboServicioService.php`
- `src/Controller/ReciboServicioApiController.php`
- `templates/recibo-servicio.html.twig`

---

### 5.7 Fase 7 — Formacion con VoBo SAE (P0)

**Resuelve:** GAP-PIIL-04 (sin VoBo SAE)

**Objetivo:** Implementar el flujo de aprobacion VoBo (Visto Bueno) del SAE para acciones formativas. Una actuacion de tipo `formacion` no puede ejecutarse hasta que el SAE haya dado su aprobacion.

**Solucion tecnica:**

Los campos `vobo_sae_status`, `vobo_sae_fecha`, `vobo_sae_documento_id` ya estan en la entidad ActuacionSto (definidos en Fase 2, solo aplican cuando `tipo_actuacion = 'formacion'`).

**Flujo:**
1. Orientador/coordinador crea ActuacionSto con `tipo_actuacion = 'formacion'` y `vobo_sae_status = 'pendiente'`
2. Sube documento de solicitud de VoBo al SAE
3. Cuando el SAE responde, actualiza `vobo_sae_status` a `aprobado` o `rechazado`
4. Solo con `vobo_sae_status = 'aprobado'` se permite ejecutar la formacion (marcar como realizada)

**Validacion en presave de ActuacionSto:**
```php
// Si es formacion y se intenta marcar como firmada sin VoBo.
if ($entity->get('tipo_actuacion')->value === 'formacion') {
    $vobo = $entity->get('vobo_sae_status')->value;
    if ($vobo !== 'aprobado' && $entity->get('firmado_participante')->value) {
        throw new \InvalidArgumentException(
            'La acción formativa requiere VoBo SAE aprobado antes de ejecutarse.'
        );
    }
}
```

**UI:**
En la tabla de actuaciones del coordinador hub, las actuaciones formativas sin VoBo muestran badge "Pendiente VoBo" en amarillo. Las aprobadas muestran badge verde.

**Ficheros modificados:**
- `src/Entity/ActuacionSto.php` (validacion en preSave)
- `templates/coordinador-dashboard.html.twig` (badge VoBo)
- `js/coordinador-hub.js` (renderizado badge)

---

### 5.8 Fase 8 — Calendario 12 Semanas e Integracion DIME (P1)

**Resuelve:** GAP-PIIL-08 (calendario desconectado), GAP-PIIL-09 (DIME desconectado)

**Objetivo:** Crear el servicio CalendarioProgramaService que mapea las 12 semanas del programa a sesiones, pildoras formativas, experimentos y entregables. Integrar el diagnostico DIME de jaraba_copilot_v2 para asignacion automatica de carril.

**Solucion tecnica:**

#### 5.8.1 CalendarioProgramaService

No se crea una entidad nueva — se usa la configuracion existente del Manual Operativo (20 pildoras, 44 experimentos, 5 fases metodologicas) como datos de referencia.

```php
class CalendarioProgramaService {
    /**
     * Mapa canónico de las 12 semanas del programa.
     */
    private const SEMANAS = [
        1 => ['fase_metodologica' => 'mentalidad', 'pildoras' => ['P01', 'P02'], 'experimentos' => ['E01', 'E02'], 'hito' => 'Primer diagnóstico DIME'],
        2 => ['fase_metodologica' => 'mentalidad', 'pildoras' => ['P03', 'P04'], 'experimentos' => ['E03', 'E04', 'E05'], 'hito' => NULL],
        // ... semanas 3-12
    ];

    public function getCalendarioParticipante(ProgramaParticipanteEi $participante): array;
    public function getProgresoSemana(ProgramaParticipanteEi $participante, int $semana): array;
    public function avanzarSemana(ProgramaParticipanteEi $participante): void;
}
```

#### 5.8.2 Integracion DIME -> Carril

Modificar `AndaluciaEiCopilotBridgeService` para consumir el resultado del diagnostico DIME de copilot v2:

```php
public function procesarResultadoDime(int $participanteId, int $puntuacionDime): array {
    $participante = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->load($participanteId);

    if (!$participante) {
        return ['success' => FALSE, 'message' => 'Participante no encontrado.'];
    }

    // DIME: 0-9 = IMPULSO (empleabilidad), 10-20 = ACELERA (emprendimiento).
    $carril = $puntuacionDime <= 9 ? 'impulso_digital' : 'acelera_pro';
    $participante->set('carril', $carril);
    $participante->save();

    return [
        'success' => TRUE,
        'carril' => $carril,
        'puntuacion' => $puntuacionDime,
    ];
}
```

**Ficheros nuevos:**
- `src/Service/CalendarioProgramaService.php`
- `src/Controller/CalendarioController.php`
- `templates/calendario-programa.html.twig`

**Ficheros modificados:**
- `src/Service/AndaluciaEiCopilotBridgeService.php`

---

### 5.9 Fase 9 — Prospeccion Empresarial y Alertas Normativas (P1)

**Resuelve:** GAP-PIIL-11 (plazos sin alertas), GAP-PIIL-12 (prospeccion sin tracking)

**Objetivo:** Crear la entidad ProspeccionEmpresarial para documentar acciones de intermediacion laboral. Crear AlertasNormativasService para detectar plazos criticos, participantes inactivos, y documentos vencidos.

**Solucion tecnica:**

#### 5.9.1 ProspeccionEmpresarial

Seguir patron de Fase 2 (entidad, access handler, form, Views, hook_update_N).

#### 5.9.2 AlertasNormativasService

```php
class AlertasNormativasService {
    public function getAlertasActivas(?int $tenantId): array {
        $alertas = [];

        // 1. Participantes en acogida >7 dias sin DACI.
        // 2. Participantes sin actuacion en >14 dias.
        // 3. Formaciones pendientes VoBo >5 dias.
        // 4. Recibos de servicio sin firma >48h.
        // 5. Indicadores FSE+ salida pendientes (participantes en baja sin FSE+ salida).
        // 6. Seguimiento 6m: participantes con salida hace 5.5 meses.
        // 7. Documentos con fecha_vencimiento proxima (<30 dias).

        return $alertas;
    }
}
```

**Integracion con coordinador hub:**
Las alertas se muestran en una nueva seccion del dashboard del coordinador y como badge en la navegacion.

**Ficheros nuevos:**
- `src/Entity/ProspeccionEmpresarial.php`, `src/Entity/ProspeccionEmpresarialInterface.php`
- `src/Access/ProspeccionEmpresarialAccessControlHandler.php`
- `src/Form/ProspeccionEmpresarialForm.php`
- `src/Service/ProspeccionEmpresarialService.php`
- `src/Service/AlertasNormativasService.php`
- `templates/prospeccion-empresarial.html.twig`

---

### 5.10 Fase 10 — Integracion BMC, Copilot por Fase y Gamificacion Pi (P1-P2)

**Resuelve:** GAP-PIIL-10 (BMC desconectado), GAP-PIIL-14 (gamificacion), GAP-PIIL-17 (copilot sin restriccion)

**Objetivo:** Integrar el dashboard BMC de copilot v2 en el contexto de andalucia_ei. Restringir los 7 modos del copilot segun la fase del programa. Conectar el sistema de Puntos de Impacto.

**Solucion tecnica:**

#### 5.10.1 BMC en contexto andalucia_ei

Modificar `AndaluciaEiCopilotContextProvider` para incluir el estado BMC del participante (si existe) en las variables del portal:

```php
public function getBmcStatus(int $userId): ?array {
    if (!\Drupal::hasService('jaraba_copilot_v2.entrepreneur_profile')) {
        return NULL;
    }
    // Consultar EntrepreneurProfile y sus Hypothesis para generar semaforo BMC.
}
```

Mostrar en el portal del participante (carril ACELERA) un widget resumen del BMC con semaforo por bloque.

#### 5.10.2 Restriccion de modos Copilot por fase

Modificar `AndaluciaEiCopilotContextProvider::getAvailableModes()`:

```php
private const MODES_POR_FASE = [
    'acogida' => ['coach_emocional'],
    'diagnostico' => ['coach_emocional', 'consultor_tactico'],
    'atencion' => ['coach_emocional', 'consultor_tactico', 'sparring_partner', 'cfo_sintetico', 'abogado_diablo', 'experto_tributario', 'experto_ss'],
    'insercion' => ['coach_emocional', 'consultor_tactico', 'experto_tributario', 'experto_ss'],
    'seguimiento' => ['coach_emocional'],
    'baja' => [],
];
```

#### 5.10.3 Puntos de Impacto

Enriquecer el sistema Pi existente con eventos del programa:
- +5 Pi por completar actuacion
- +10 Pi por firmar recibo de servicio
- +20 Pi por completar pildora formativa
- +50 Pi por completar experimento
- +100 Pi por insercion laboral

**Ficheros modificados:**
- `src/Service/AndaluciaEiCopilotContextProvider.php`
- `src/Service/AndaluciaEiCopilotBridgeService.php`
- `templates/partials/_participante-logros.html.twig`

---

### 5.11 Fase 11 — Elevacion Frontend: Templates, SCSS, Accesibilidad (P1)

**Objetivo:** Crear templates zero-region, SCSS route-specific, y garantizar WCAG 2.1 AA para todas las nuevas rutas y funcionalidades.

#### 5.11.1 Templates Twig nuevos (Zero Region Pattern)

Cada nueva ruta necesita:
1. Template de contenido en `jaraba_andalucia_ei/templates/` (datos del controller)
2. Template de pagina en `ecosistema_jaraba_theme/templates/pages/page--programa--{ruta}.html.twig` (layout limpio)

**Template de pagina (patron comun):**
```twig
{# page--programa--actuaciones.html.twig #}
{# Zero Region Pattern: clean layout sin page.content #}
{% include '@ecosistema_jaraba_theme/partials/_header.html.twig' %}
<main class="programa-actuaciones" role="main">
  {{ clean_messages }}
  {{ clean_content }}
</main>
{% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' %}
```

**Body class via hook_preprocess_html() (NO attributes.addClass()):**
```php
function jaraba_andalucia_ei_preprocess_html(&$variables) {
  $route = \Drupal::routeMatch()->getRouteName();
  $route_classes = [
    'jaraba_andalucia_ei.actuaciones_sto' => 'page--programa-actuaciones',
    'jaraba_andalucia_ei.insercion_laboral' => 'page--programa-insercion',
    'jaraba_andalucia_ei.indicadores_fse' => 'page--programa-fse',
    'jaraba_andalucia_ei.prospeccion' => 'page--programa-prospeccion',
    'jaraba_andalucia_ei.calendario' => 'page--programa-calendario',
  ];
  if (isset($route_classes[$route])) {
    $variables['attributes']['class'][] = $route_classes[$route];
  }
}
```

#### 5.11.2 SCSS route-specific

Crear `web/themes/custom/ecosistema_jaraba_theme/scss/routes/_programa-piil.scss`:

**Estructura:**
```scss
@use '../variables' as *;

// Estilos compartidos para todas las rutas del programa PIIL.
// Reutiliza tokens CSS existentes, NUNCA hex hardcoded.

.programa-actuaciones,
.programa-insercion,
.programa-fse,
.programa-prospeccion,
.programa-calendario {
  // Layout base
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem 1.5rem;
}

// === KPI Cards compartidas ===
.piil-kpi-card {
  background: var(--ej-color-bg-surface, #FFFFFF);
  border: 1px solid var(--ej-border-color, #E2E8F0);
  border-radius: var(--ej-radius-lg, 12px);
  padding: 1.5rem;
  // ... hover, shadow con color-mix()
}

// === Badges de fase ===
.piil-badge-fase {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.75rem;
  border-radius: var(--ej-radius-full, 9999px);
  font-size: 0.75rem;
  font-weight: 600;

  &--acogida { background: color-mix(in srgb, var(--ej-color-info, #3B82F6) 15%, transparent); color: var(--ej-color-info, #3B82F6); }
  &--diagnostico { background: color-mix(in srgb, var(--ej-color-warning, #F59E0B) 15%, transparent); color: var(--ej-color-warning, #F59E0B); }
  &--atencion { background: color-mix(in srgb, var(--ej-color-primary, #FF8C42) 15%, transparent); color: var(--ej-color-primary, #FF8C42); }
  &--insercion { background: color-mix(in srgb, var(--ej-color-success, #10B981) 15%, transparent); color: var(--ej-color-success, #10B981); }
  &--seguimiento { background: color-mix(in srgb, var(--ej-color-azul-corporativo, #233D63) 15%, transparent); color: var(--ej-color-azul-corporativo, #233D63); }
  &--baja { background: color-mix(in srgb, var(--ej-color-error, #EF4444) 15%, transparent); color: var(--ej-color-error, #EF4444); }
}

// === Tabla de actuaciones ===
.piil-actuaciones-table {
  width: 100%;
  border-collapse: collapse;

  th {
    background: var(--ej-color-bg-muted, #F8FAFC);
    padding: 0.75rem 1rem;
    text-align: left;
    font-weight: 600;
    font-size: 0.8125rem;
    color: var(--ej-color-text-secondary, #64748B);
    border-bottom: 2px solid var(--ej-border-color, #E2E8F0);
  }

  td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--ej-border-color-light, #F1F5F9);
  }
}

// === Responsive ===
@media (max-width: 768px) {
  .piil-actuaciones-table {
    display: block;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }
}

@media (max-width: 480px) {
  .programa-actuaciones,
  .programa-insercion {
    padding: 1rem;
  }
}
```

**Registrar library:**
```yaml
# En ecosistema_jaraba_theme.libraries.yml
route-programa-piil:
  version: 1.0.0
  css:
    theme:
      css/routes/programa-piil.css: {}
  dependencies:
    - ecosistema_jaraba_theme/global-styling
```

**Compilacion (SCSS-COMPILE-VERIFY-001):**
```bash
lando ssh -c "cd /app/web/themes/custom/ecosistema_jaraba_theme && npx sass scss/routes/_programa-piil.scss css/routes/programa-piil.css --style=compressed"
```

#### 5.11.3 Iconografia (ICON-CONVENTION-001)

Iconos necesarios para las nuevas funcionalidades:

| Contexto | Categoria | Nombre | Variante | Color |
|----------|-----------|--------|----------|-------|
| Actuacion orientacion | actions | consultation | duotone | azul-corporativo |
| Actuacion formacion | education | training | duotone | naranja-impulso |
| Insercion laboral | business | contract | duotone | verde-innovacion |
| Indicadores FSE+ | data | survey | duotone | azul-corporativo |
| DACI firma | security | signature | duotone | azul-corporativo |
| Recibo servicio | files | receipt | duotone | naranja-impulso |
| Prospeccion | business | networking | duotone | verde-innovacion |
| Calendario | calendar | schedule | duotone | naranja-impulso |
| Alerta | status | warning | duotone | naranja-impulso |
| VoBo aprobado | status | approved | duotone | verde-innovacion |
| VoBo pendiente | status | pending | duotone | naranja-impulso |
| VoBo rechazado | status | rejected | duotone | error |

**Uso en Twig:**
```twig
{{ jaraba_icon('actions', 'consultation', { variant: 'duotone', color: 'azul-corporativo', size: '24px' }) }}
```

#### 5.11.4 Accesibilidad WCAG 2.1 AA

**Requisitos minimos para todas las nuevas interfaces:**
- Contraste texto: 4.5:1 minimo (verificar con var(--ej-*) en modo claro y oscuro)
- Focus visible: `outline: 2px solid var(--ej-color-primary)` en todos los interactivos
- ARIA: `role="tabpanel"`, `aria-selected`, `aria-controls`, `aria-labelledby` en tabs
- ARIA live region: `aria-live="polite"` para notificaciones dinamicas
- Keyboard navigation: Tab order logico, Enter/Space para acciones, Escape para cerrar modales
- Touch targets: minimo 44x44px
- Headings jerarquicos: h1 > h2 > h3 sin saltos
- Alt text: en todas las imagenes (o `aria-hidden="true"` si decorativas)

#### 5.11.5 Variables CSS inyectables desde Drupal UI

Las nuevas rutas del programa consumen los mismos tokens CSS (`--ej-*`) que ya se configuran desde Apariencia > Ecosistema Jaraba Theme. No se crean nuevas variables de tema, pero se documenta que los colores de badges de fase son derivados de los tokens existentes:

- `--ej-color-info` -> Acogida
- `--ej-color-warning` -> Diagnostico
- `--ej-color-primary` (naranja-impulso) -> Atencion
- `--ej-color-success` -> Insercion
- `--ej-color-azul-corporativo` -> Seguimiento
- `--ej-color-error` -> Baja

**Ficheros nuevos:**
- `ecosistema_jaraba_theme/scss/routes/_programa-piil.scss`
- `ecosistema_jaraba_theme/css/routes/programa-piil.css` (compilado)
- `ecosistema_jaraba_theme/templates/pages/page--programa--actuaciones.html.twig`
- `ecosistema_jaraba_theme/templates/pages/page--programa--insercion.html.twig`
- `ecosistema_jaraba_theme/templates/pages/page--programa--fse-indicadores.html.twig`
- `ecosistema_jaraba_theme/templates/pages/page--programa--prospeccion.html.twig`
- `ecosistema_jaraba_theme/templates/pages/page--programa--calendario.html.twig`
- `jaraba_andalucia_ei/templates/actuaciones-sto.html.twig`
- `jaraba_andalucia_ei/templates/insercion-laboral.html.twig`
- `jaraba_andalucia_ei/templates/indicadores-fse.html.twig`
- `jaraba_andalucia_ei/templates/prospeccion-empresarial.html.twig`
- `jaraba_andalucia_ei/templates/calendario-programa.html.twig`
- `jaraba_andalucia_ei/js/actuaciones-sto.js`
- `jaraba_andalucia_ei/js/insercion-laboral.js`
- `jaraba_andalucia_ei/js/calendario-programa.js`

**Ficheros modificados:**
- `ecosistema_jaraba_theme.libraries.yml` (nueva library route-programa-piil)
- `jaraba_andalucia_ei.libraries.yml` (nuevas libraries)
- `jaraba_andalucia_ei.module` (preprocess_html, theme hooks)

---

### 5.12 Fase 12 — Testing, Verificacion y Documentacion (P0)

**Objetivo:** Garantizar cobertura de tests adecuada, ejecutar RUNTIME-VERIFY-001, IMPLEMENTATION-CHECKLIST-001, y actualizar documentacion.

#### 5.12.1 Tests automatizados

**Unit Tests (para cada nuevo servicio):**

| Test | Clase | Metodos testeados |
|------|-------|-------------------|
| ActuacionStoServiceTest | Unit | Calculo duracion, validacion tipo, incremento horas |
| ReciboServicioServiceTest | Unit | Generacion PDF, validacion firmante, firma dual |
| IndicadoresFseServiceTest | Unit | Validacion completitud, campos por momento |
| InsercionLaboralServiceTest | Unit | Validacion por tipo, campos condicionales |
| DaciServiceTest | Unit | Generacion PDF, flujo firma |
| CalendarioProgramaServiceTest | Unit | Mapping semanas, progreso |
| AlertasNormativasServiceTest | Unit | Deteccion alertas, plazos |
| FaseTransitionManagerTest | Unit | 6 fases, transiciones validas/invalidas, requisitos |

**Kernel Tests (para cada nueva entidad):**

| Test | Clase | Verificaciones |
|------|-------|---------------|
| ActuacionStoKernelTest | Kernel | CRUD, presave duracion, presave fase, tenant isolation |
| InsercionLaboralKernelTest | Kernel | CRUD, campos condicionales por tipo |
| IndicadorFsePlusKernelTest | Kernel | CRUD, campos por momento, completitud |
| ProspeccionEmpresarialKernelTest | Kernel | CRUD, tenant isolation |
| ProgramaParticipanteEiFasesKernelTest | Kernel | 6 fases, transiciones, colectivos actualizados |

**Reglas de testing:**
- KERNEL-TEST-DEPS-001: $modules lista TODOS los modulos requeridos
- MOCK-METHOD-001: createMock() solo con metodos de la interface
- UPDATE-HOOK-CATCH-001: \Throwable en catch
- TEST-CACHE-001: Entity mocks con getCacheContexts, getCacheTags, getCacheMaxAge

#### 5.12.2 Checklist RUNTIME-VERIFY-001

Tras completar cada fase, verificar:

| Check | Comando | Esperado |
|-------|---------|----------|
| 1. CSS compilado | `stat -c '%Y' css/routes/programa-piil.css` vs SCSS | CSS timestamp > SCSS |
| 2. Tablas DB | `lando drush entity:updates` | "No pending updates" |
| 3. Rutas accesibles | `lando drush router:rebuild && curl -s -o /dev/null -w '%{http_code}' https://jaraba-saas.lndo.site/programa/actuaciones` | 200 o 403 |
| 4. data-* selectores | Inspeccionar HTML vs JS | Coinciden |
| 5. drupalSettings | `Drupal.settings.jarabaAndaluciaEi.hub.phases` en consola | 6 fases |

#### 5.12.3 Checklist IMPLEMENTATION-CHECKLIST-001

- [ ] Cada servicio registrado en services.yml Y consumido
- [ ] Rutas apuntan a clases existentes
- [ ] Cada nueva entidad tiene: AccessControlHandler, hook_theme, preprocess, Views data, Field UI, PremiumEntityFormBase
- [ ] Cada nuevo SCSS compilado, library registrada, hook_page_attachments_alter
- [ ] Tests existen: Unit para servicios, Kernel para entities
- [ ] hook_update_N() para cada nueva entidad y campo modificado
- [ ] Permisos registrados en .permissions.yml
- [ ] Documentacion actualizada (master docs si aplica)

#### 5.12.4 Validacion automatizada

```bash
# Ejecutar todas las validaciones
bash scripts/validation/validate-all.sh --checklist web/modules/custom/jaraba_andalucia_ei

# Validaciones especificas
php scripts/validation/validate-entity-integrity.php
php scripts/validation/validate-service-consumers.php
php scripts/validation/validate-compiled-assets.php
php scripts/validation/validate-tenant-isolation.php
php scripts/validation/validate-optional-deps.php
php scripts/validation/validate-circular-deps.php
php scripts/validation/validate-logger-injection.php
```

---

## 6. Tabla de Correspondencia con Especificaciones Tecnicas

| Fase | Gap IDs | Entidades | Servicios | Controllers | Templates | Tests |
|------|---------|-----------|-----------|-------------|-----------|-------|
| 1 | 01, 07 | ProgramaParticipanteEi (mod) | FaseTransitionManager (mod) | CoordinadorHub (mod) | coordinador-dashboard (mod) | FaseTransitionManagerTest |
| 2 | 02 | ActuacionSto (new) | ActuacionStoService (new) | ActuacionesStoController (new), ActuacionesStoApiController (new) | actuaciones-sto (new), actuacion-sto (new) | ActuacionStoServiceTest, ActuacionStoKernelTest |
| 3 | 03 | IndicadorFsePlus (new) | IndicadoresFseService (new) | IndicadoresFseController (new) | indicadores-fse (new), indicador-fse-plus (new) | IndicadoresFseServiceTest, IndicadorFsePlusKernelTest |
| 4 | 05 | InsercionLaboral (new) | InsercionLaboralService (new) | InsercionLaboralController (new) | insercion-laboral (new) | InsercionLaboralServiceTest, InsercionLaboralKernelTest |
| 5 | 06 | (campos ParticipanteEi) | DaciService (new) | DaciApiController (new) | daci-documento (new) | DaciServiceTest |
| 6 | 13 | (ExpedienteDocumento) | ReciboServicioService (new) | ReciboServicioApiController (new) | recibo-servicio (new) | ReciboServicioServiceTest |
| 7 | 04 | (campos ActuacionSto) | (validacion en presave) | — | — | VoBoSaeTest |
| 8 | 08, 09 | (campos ParticipanteEi) | CalendarioProgramaService (new), CopilotBridge (mod) | CalendarioController (new) | calendario-programa (new) | CalendarioProgramaServiceTest |
| 9 | 11, 12 | ProspeccionEmpresarial (new) | AlertasNormativasService (new), ProspeccionService (new) | ProspeccionController (new), AlertasApiController (new) | prospeccion-empresarial (new) | AlertasNormativasServiceTest, ProspeccionKernelTest |
| 10 | 10, 14, 17 | — | CopilotContextProvider (mod), CopilotBridge (mod) | — | partials mod | — |
| 11 | — | — | — | — | 5 page templates, 5 content templates, SCSS | — |
| 12 | — | — | — | — | — | 30+ tests |

---

## 7. Tabla de Cumplimiento de Directrices del Proyecto

| Directriz | Descripcion | Donde se aplica | Como se verifica |
|-----------|-------------|-----------------|-----------------|
| TENANT-001 | Toda query filtra por tenant | Todos los servicios y controllers | `validate-tenant-isolation.php` |
| TENANT-BRIDGE-001 | TenantBridgeService para Tenant<->Group | Resolucion de tenant en controllers | Code review |
| PREMIUM-FORMS-PATTERN-001 | Forms extienden PremiumEntityFormBase | 4 nuevas entity forms | Grep `extends PremiumEntityFormBase` |
| UPDATE-HOOK-REQUIRED-001 | hook_update_N para cada nueva entidad | 4 entidades + campos modificados | `validate-entity-integrity.php` |
| UPDATE-HOOK-CATCH-001 | \Throwable en catch de hook_update_N | Todos los hooks | Grep `\Throwable` |
| OPTIONAL-CROSSMODULE-001 | @? para dependencias cross-modulo | services.yml | `validate-optional-deps.php` |
| CONTAINER-DEPS-002 | Sin dependencias circulares | services.yml | `validate-circular-deps.php` |
| LOGGER-INJECT-001 | LoggerInterface con @logger.channel | Todos los servicios | `validate-logger-injection.php` |
| CSS-VAR-ALL-COLORS-001 | var(--ej-*) en todo SCSS | _programa-piil.scss | Grep hex en SCSS |
| SCSS-001 | @use (no @import) | Todos los .scss | Grep `@import` (0 results) |
| SCSS-COLORMIX-001 | color-mix() para alpha | _programa-piil.scss | Grep `rgba` (0 results) |
| SCSS-COMPILE-VERIFY-001 | Timestamp CSS > SCSS | Post-compilacion | `validate-compiled-assets.php` |
| ZERO-REGION-001 | clean_content, variables via preprocess | Todas las page templates | Template inspection |
| SLIDE-PANEL-RENDER-001 | renderPlain(), $form['#action'] | Forms en slide-panel | Code review |
| ENTITY-PREPROCESS-001 | template_preprocess_{type} en .module | 4 nuevas entidades | Grep `_preprocess_` |
| AUDIT-CONS-001 | AccessControlHandler en anotacion | 4 nuevas entidades | `validate-entity-integrity.php` |
| ENTITY-FK-001 | entity_reference mismo modulo, integer cross-modulo | Nuevas entidades | Code review |
| ICON-CONVENTION-001 | jaraba_icon() con categoria/nombre | Templates | Template inspection |
| ICON-DUOTONE-001 | Variante duotone por defecto | Templates | Template inspection |
| ICON-COLOR-001 | Solo colores paleta Jaraba | Templates | Template inspection |
| ROUTE-LANGPREFIX-001 | Url::fromRoute(), drupalSettings | JS y controllers | Grep hardcoded paths |
| INNERHTML-XSS-001 | Drupal.checkPlain() | JS con innerHTML | JS code review |
| CSRF-API-001 | _csrf_request_header_token en POST | routing.yml | Grep CSRF |
| API-WHITELIST-001 | ALLOWED_FIELDS | API controllers | Code review |
| ACCESS-STRICT-001 | (int) comparisons | Access handlers | Code review |
| CONTROLLER-READONLY-001 | No readonly en entityTypeManager | Controllers | Grep `protected readonly` |
| FIELD-UI-SETTINGS-TAB-001 | field_ui_base_route + default local task | 4 nuevas entidades | routing.yml |
| PRESAVE-RESILIENCE-001 | hasService() + try-catch | Hook entity presave | Code review |
| LABEL-NULLSAFE-001 | Null-safe label() | Entity usage | Code review |

---

## 8. Arquitectura Frontend y Templates

### 8.1 Templates Twig nuevos

| Template | Ubicacion | Proposito |
|----------|-----------|-----------|
| `actuaciones-sto.html.twig` | jaraba_andalucia_ei/templates/ | Lista de actuaciones con filtros y tabla |
| `insercion-laboral.html.twig` | jaraba_andalucia_ei/templates/ | Detalle insercion con sections condicionales |
| `indicadores-fse.html.twig` | jaraba_andalucia_ei/templates/ | Formulario indicadores por momento |
| `prospeccion-empresarial.html.twig` | jaraba_andalucia_ei/templates/ | Lista prospeccion con resultado |
| `calendario-programa.html.twig` | jaraba_andalucia_ei/templates/ | Vista calendario 12 semanas |
| `recibo-servicio.html.twig` | jaraba_andalucia_ei/templates/ | PDF recibo (DomPDF) |
| `daci-documento.html.twig` | jaraba_andalucia_ei/templates/ | PDF DACI (DomPDF) |

### 8.2 Parciales reutilizables

Verificar parciales existentes antes de crear nuevos:

| Parcial existente | Reutilizable en |
|-------------------|----------------|
| `_participante-hero.html.twig` | Todas las rutas /programa/* |
| `_participante-timeline.html.twig` | Calendario (timeline por semana) |
| `_participante-expediente.html.twig` | Hub documental (ya existe) |

**Parciales nuevos necesarios:**

| Parcial | Proposito | Reutilizado en |
|---------|-----------|----------------|
| `_piil-badge-fase.html.twig` | Badge de fase con color por tipo | Actuaciones, portal, coordinador hub |
| `_piil-alerta-card.html.twig` | Card de alerta normativa | Coordinador hub, orientador dashboard |
| `_piil-recibo-preview.html.twig` | Preview de recibo en slide-panel | Actuaciones, portal |

### 8.3 SCSS y compilacion

**Fichero SCSS:** `ecosistema_jaraba_theme/scss/routes/_programa-piil.scss`
**Compilacion:**
```bash
lando ssh -c "cd /app/web/themes/custom/ecosistema_jaraba_theme && npx sass scss/routes/_programa-piil.scss css/routes/programa-piil.css --style=compressed"
```
**Verificacion:**
```bash
lando ssh -c "stat -c '%Y %n' /app/web/themes/custom/ecosistema_jaraba_theme/css/routes/programa-piil.css /app/web/themes/custom/ecosistema_jaraba_theme/scss/routes/_programa-piil.scss"
```

### 8.4 Variables CSS inyectables desde Drupal UI

No se crean nuevas variables de tema. Las nuevas interfaces consumen los tokens existentes:
- `--ej-color-primary` (naranja-impulso #FF8C42) — CTAs, highlights
- `--ej-color-azul-corporativo` (#233D63) — headers, texto principal
- `--ej-color-verde-innovacion` (#00A9A5) — success, aprobado
- `--ej-color-bg-surface` — fondos de cards
- `--ej-border-color` — bordes
- `--ej-radius-lg` — border-radius

Si un tenant quiere personalizar estos colores, lo hace desde Apariencia > Ecosistema Jaraba Theme > Colores. No requiere cambio de codigo.

### 8.5 Iconografia

Ver tabla de iconos en seccion 5.11.3. Todos los iconos usan:
- Funcion: `{{ jaraba_icon('category', 'name', { variant: 'duotone', color: 'azul-corporativo', size: '24px' }) }}`
- Variante: duotone (ICON-DUOTONE-001)
- Colores: solo paleta Jaraba (ICON-COLOR-001)
- NO emojis Unicode (ICON-EMOJI-001)

### 8.6 Accesibilidad

Ver requisitos en seccion 5.11.4. Resumen:
- WCAG 2.1 AA minimo
- Contraste 4.5:1
- Focus visible en todos los interactivos
- ARIA completo en tabs, modales, notificaciones
- Keyboard navigation
- Touch targets 44x44px
- Headings jerarquicos
- Textos traducibles (`{% trans %}`)

---

## 9. Verificacion y Testing

### 9.1 Tests automatizados

Ver seccion 5.12.1 para lista completa. Total estimado: ~30 tests (15 Unit + 15 Kernel).

### 9.2 Checklist RUNTIME-VERIFY-001

Ver seccion 5.12.2.

### 9.3 Checklist IMPLEMENTATION-CHECKLIST-001

Ver seccion 5.12.3.

---

## 10. Inventario Completo de Ficheros

### Ficheros nuevos (~40)

**Entidades (8):**
- `src/Entity/ActuacionSto.php`
- `src/Entity/ActuacionStoInterface.php`
- `src/Entity/InsercionLaboral.php`
- `src/Entity/InsercionLaboralInterface.php`
- `src/Entity/IndicadorFsePlus.php`
- `src/Entity/IndicadorFsePlusInterface.php`
- `src/Entity/ProspeccionEmpresarial.php`
- `src/Entity/ProspeccionEmpresarialInterface.php`

**Access Handlers (4):**
- `src/Access/ActuacionStoAccessControlHandler.php`
- `src/Access/InsercionLaboralAccessControlHandler.php`
- `src/Access/IndicadorFsePlusAccessControlHandler.php`
- `src/Access/ProspeccionEmpresarialAccessControlHandler.php`

**Forms (4):**
- `src/Form/ActuacionStoForm.php`
- `src/Form/InsercionLaboralForm.php`
- `src/Form/IndicadorFsePlusForm.php`
- `src/Form/ProspeccionEmpresarialForm.php`

**Servicios (8):**
- `src/Service/ActuacionStoService.php`
- `src/Service/ReciboServicioService.php`
- `src/Service/IndicadoresFseService.php`
- `src/Service/InsercionLaboralService.php`
- `src/Service/DaciService.php`
- `src/Service/CalendarioProgramaService.php`
- `src/Service/AlertasNormativasService.php`
- `src/Service/ProspeccionEmpresarialService.php`

**Controllers (5):**
- `src/Controller/ActuacionesStoApiController.php`
- `src/Controller/InsercionLaboralController.php`
- `src/Controller/DaciApiController.php`
- `src/Controller/ReciboServicioApiController.php`
- `src/Controller/CalendarioController.php`

**Templates (10):**
- `templates/actuaciones-sto.html.twig`
- `templates/actuacion-sto.html.twig` (entity view)
- `templates/insercion-laboral.html.twig`
- `templates/indicadores-fse.html.twig`
- `templates/indicador-fse-plus.html.twig` (entity view)
- `templates/prospeccion-empresarial.html.twig`
- `templates/calendario-programa.html.twig`
- `templates/recibo-servicio.html.twig` (PDF)
- `templates/daci-documento.html.twig` (PDF)
- `templates/partials/_piil-badge-fase.html.twig`

**JS (3):**
- `js/actuaciones-sto.js`
- `js/insercion-laboral.js`
- `js/calendario-programa.js`

**Page Templates theme (5):**
- `ecosistema_jaraba_theme/templates/pages/page--programa--actuaciones.html.twig`
- `ecosistema_jaraba_theme/templates/pages/page--programa--insercion.html.twig`
- `ecosistema_jaraba_theme/templates/pages/page--programa--fse-indicadores.html.twig`
- `ecosistema_jaraba_theme/templates/pages/page--programa--prospeccion.html.twig`
- `ecosistema_jaraba_theme/templates/pages/page--programa--calendario.html.twig`

**SCSS/CSS (2):**
- `ecosistema_jaraba_theme/scss/routes/_programa-piil.scss`
- `ecosistema_jaraba_theme/css/routes/programa-piil.css` (compilado)

### Ficheros modificados (~15)

- `src/Entity/ProgramaParticipanteEi.php` (fases, colectivos, campos nuevos)
- `src/Service/FaseTransitionManager.php` (6 fases, requisitos)
- `src/Service/CoordinadorHubService.php` (VALID_PHASES, nuevos KPIs)
- `src/Service/AndaluciaEiCopilotBridgeService.php` (DIME -> carril)
- `src/Service/AndaluciaEiCopilotContextProvider.php` (BMC, modos por fase)
- `src/Service/StoExportService.php` (actuaciones en export)
- `src/Service/ExpedienteCompletenessService.php` (checklists ampliados)
- `src/Controller/CoordinadorDashboardController.php` (6 fases en drupalSettings)
- `src/Controller/CoordinadorHubApiController.php` (6 fases)
- `jaraba_andalucia_ei.module` (hooks, preprocess, theme)
- `jaraba_andalucia_ei.routing.yml` (nuevas rutas)
- `jaraba_andalucia_ei.services.yml` (nuevos servicios)
- `jaraba_andalucia_ei.permissions.yml` (nuevos permisos)
- `jaraba_andalucia_ei.libraries.yml` (nuevas libraries)
- `jaraba_andalucia_ei.install` (hook_update_N)
- `ecosistema_jaraba_theme.libraries.yml` (route-programa-piil)
- `js/coordinador-hub.js` (6 fases)

---

## 11. Troubleshooting

| Problema | Causa | Solucion |
|----------|-------|---------|
| `Entity type actuacion_sto needs to be installed` | hook_update_N no ejecutado | `lando drush updatedb -y` |
| CSS no se aplica | SCSS no compilado | Compilar + verificar timestamp |
| 404 en rutas nuevas | Cache de rutas | `lando drush cr` |
| TypeError en FaseTransitionManager | \Exception en catch en vez de \Throwable | Cambiar a \Throwable |
| drupalSettings sin fases nuevas | Controller no actualizado | Verificar `CoordinadorDashboardController::dashboard()` |
| Formulario sin sections | Form no extiende PremiumEntityFormBase | Verificar herencia |
| Entity sin Views | Falta `views_data` en anotacion | Anadir a la anotacion |
| Field UI no aparece | Falta `field_ui_base_route` y local task | Anadir ruta settings |
| Recibo PDF con var(--ej-*) | DomPDF no soporta CSS variables | Usar hex fallback directo |
| Slide-panel con BigPipe placeholders | Usando render() en vez de renderPlain() | Cambiar a renderPlain() |

---

## 12. Referencias

| Documento | Ubicacion |
|-----------|-----------|
| Auditoria de brechas | `docs/analisis/2026-03-06_Auditoria_Andalucia_Ei_PIIL_CV_2025_Brechas_Normativas_v1.md` |
| Plan Mentoring/Cursos | `docs/implementacion/2026-03-06_Plan_Implementacion_Andalucia_Ei_Mentoring_Cursos_Clase_Mundial_v1.md` |
| Plan UserProfileSection | `docs/implementacion/2026-03-06_Plan_Implementacion_UserProfileSectionProvider_Hub_Andalucia_Ei_Clase_Mundial_v1.md` |
| PIIL BBRR Consolidada | `F:\DATOS\...\00. Normativa y formularios\PIIL BBRR Versión Consolidada_20250730.pdf` |
| Resolucion Concesion | `F:\DATOS\...\01. Solicitud y Concesión\20251219b-Resolución de Concesión*.pdf` |
| Ficha Tecnica FT_679 | `F:\DATOS\...\03. Inicio\20260128d-Ficha Técnica Validada_FT_679.pdf` |
| Manual STO | `F:\DATOS\...\03. Inicio\Documentación técnica\..\Manual Gestión P.Técnico_STO*.pdf` |
| Manual Operativo V2.1 | `F:\DATOS\...\02. Configuración\20260121c-Manual_Operativo_Completo*.docx` |
| Contenido Formativo | `F:\DATOS\...\02. Configuración\20260121e-Contenido_Formativo_Integral*.docx` |
| Itinerarios Diferenciados | `F:\DATOS\...\02. Configuración\20260121f-Anexo_Itinerarios_Diferenciados*.docx` |
| CLAUDE.md | `/home/PED/JarabaImpactPlatformSaaS/CLAUDE.md` |

---

## 13. Registro de Cambios

| Version | Fecha | Cambio |
|---------|-------|--------|
| 1.0.0 | 2026-03-06 | Creacion inicial: 12 fases, 17 brechas, 4 entidades nuevas |
