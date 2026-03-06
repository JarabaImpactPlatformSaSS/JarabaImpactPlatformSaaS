# Plan de Implementacion: Andalucia +ei — Contextualizacion Integral de Mentorias, Cursos, Expediente Documental y Firma Electronica

**Fecha de creacion:** 2026-03-06 09:00
**Ultima actualizacion:** 2026-03-06 09:00
**Autor:** IA Asistente (Claude Opus 4.6)
**Version:** 1.0.0
**Estado:** Planificado
**Categoria:** Elevacion Clase Mundial
**Modulos afectados:** `jaraba_andalucia_ei`, `jaraba_mentoring`, `ecosistema_jaraba_core`, `ecosistema_jaraba_theme`
**Especificacion referencia:** Diagnostico 2026-03-06 (Doc analisis), Spec 32 Mentoring Sessions v1
**Prioridad:** P0 (cumplimiento documental FSE/PIIL) + P1 (contextualizacion UX)
**Directrices de aplicacion:** ZERO-REGION-001, PREMIUM-FORMS-PATTERN-001, CSS-VAR-ALL-COLORS-001, SCSS-COLORMIX-001, TENANT-001, TENANT-BRIDGE-001, UPDATE-HOOK-REQUIRED-001, OPTIONAL-CROSSMODULE-001, SLIDE-PANEL-RENDER-001, ENTITY-PREPROCESS-001, PRESAVE-RESILIENCE-001, ICON-CONVENTION-001, ICON-DUOTONE-001, ROUTE-LANGPREFIX-001
**Documento fuente:** `docs/analisis/2026-03-06_Diagnostico_Andalucia_Ei_Mentoring_Courses_Clase_Mundial_v1.md`
**Esfuerzo estimado:** 40-55 horas
**Rutas principales:** `/programa/mentores`, `/programa/formacion`, `/programa/expediente`, `/programa/orientador`, `/programa/coordinador`, `/mentors`

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
   - 1.1 [Que se implementa](#11-que-se-implementa)
   - 1.2 [Por que se implementa](#12-por-que-se-implementa)
   - 1.3 [Alcance y exclusiones](#13-alcance-y-exclusiones)
   - 1.4 [Filosofia de implementacion](#14-filosofia-de-implementacion)
   - 1.5 [Estimacion de esfuerzo](#15-estimacion-de-esfuerzo)
2. [Diagnostico del Estado Actual](#2-diagnostico-del-estado-actual)
   - 2.1 [Inventario de sistemas existentes](#21-inventario-de-sistemas-existentes)
   - 2.2 [Gaps identificados](#22-gaps-identificados)
   - 2.3 [Cadena de renderizacion actual del participante](#23-cadena-de-renderizacion-actual-del-participante)
3. [Arquitectura Objetivo](#3-arquitectura-objetivo)
   - 3.1 [Diagrama de flujo contextualizado](#31-diagrama-de-flujo-contextualizado)
   - 3.2 [Modelo de datos ampliado](#32-modelo-de-datos-ampliado)
   - 3.3 [Arquitectura de servicios](#33-arquitectura-de-servicios)
   - 3.4 [Mapa de rutas frontend](#34-mapa-de-rutas-frontend)
4. [Requisitos Previos](#4-requisitos-previos)
5. [Fases de Implementacion](#5-fases-de-implementacion)
   - 5.1 [Fase 1 — Infraestructura de Datos (P0)](#51-fase-1--infraestructura-de-datos-p0)
   - 5.2 [Fase 2 — Mentores Contextualizados por Edicion (P0)](#52-fase-2--mentores-contextualizados-por-edicion-p0)
   - 5.3 [Fase 3 — Formacion Contextualizada por Edicion (P0)](#53-fase-3--formacion-contextualizada-por-edicion-p0)
   - 5.4 [Fase 4 — Hoja de Servicio y Firma Electronica (P0)](#54-fase-4--hoja-de-servicio-y-firma-electronica-p0)
   - 5.5 [Fase 5 — Hub Documental Role-Aware (P1)](#55-fase-5--hub-documental-role-aware-p1)
   - 5.6 [Fase 6 — Dashboard Orientador (P1)](#56-fase-6--dashboard-orientador-p1)
   - 5.7 [Fase 7 — Dashboard Coordinador (P1)](#57-fase-7--dashboard-coordinador-p1)
   - 5.8 [Fase 8 — Elevacion CSS /mentors (P1)](#58-fase-8--elevacion-css-mentors-p1)
   - 5.9 [Fase 9 — Separacion Bridges Programa vs Cross-Selling (P2)](#59-fase-9--separacion-bridges-programa-vs-cross-selling-p2)
6. [Tabla de Correspondencia con Especificaciones Tecnicas](#6-tabla-de-correspondencia-con-especificaciones-tecnicas)
7. [Tabla de Cumplimiento de Directrices del Proyecto](#7-tabla-de-cumplimiento-de-directrices-del-proyecto)
8. [Arquitectura Frontend y Templates](#8-arquitectura-frontend-y-templates)
   - 8.1 [Templates Twig nuevos](#81-templates-twig-nuevos)
   - 8.2 [Parciales reutilizables](#82-parciales-reutilizables)
   - 8.3 [SCSS y compilacion](#83-scss-y-compilacion)
   - 8.4 [Variables CSS inyectables desde Drupal UI](#84-variables-css-inyectables-desde-drupal-ui)
   - 8.5 [Iconografia](#85-iconografia)
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

Implementacion integral de 9 fases que resuelve la descontextualizacion del participante de Andalucia +ei respecto a sus mentorias, cursos y expediente documental. Los componentes principales son:

- **Mentores contextualizados**: Nueva ruta `/programa/mentores` que muestra unicamente los mentores asignados a la edicion/grupo del participante, con flujo de solicitud y aprobacion.
- **Formacion contextualizada**: Nueva ruta `/programa/formacion` que muestra cursos de la edicion, con progreso y horas acumuladas.
- **Hoja de servicio automatica**: Generacion PDF post-sesion de mentoria con firma electronica dual (participante + orientador) via `FirmaDigitalService` y AutoFirma.
- **Hub documental role-aware**: Vista de expediente organizada por fase y rol (participante, orientador, coordinador) con checklist de completitud.
- **Dashboards de gestion**: Paneles para orientador (cartera de participantes) y coordinador (metricas por edicion).
- **Elevacion CSS /mentors**: Creacion del CSS inexistente para la pagina del catalogo generico de mentores.
- **Separacion programa vs cross-selling**: Diferenciar contenido del programa de ofertas comerciales del SaaS.

### 1.2 Por que se implementa

**Cumplimiento normativo (P0):**
Las hojas de servicio de mentoria son un requisito documental de programas cofinanciados FSE/PIIL. Sin ellas, las horas de mentoria NO son justificables ante la entidad financiadora (STO). Actualmente no existe ningun mecanismo para generarlas ni firmarlas.

**Experiencia de usuario (P0-P1):**
El participante de Andalucia +ei percibe elementos desconectados: al hacer clic en "Ver cursos" aterriza en `/courses` (catalogo generico LMS con todos los cursos del SaaS), y al hacer clic en "Mentores" ve un marketplace generico con mentores de todas las verticales. Esto rompe la coherencia del programa y genera confusion.

**Gestion operativa (P1):**
Orientadores y coordinadores no tienen dashboards de gestion propios, obligandolos a usar la interfaz de administracion de Drupal para tareas cotidianas.

### 1.3 Alcance y exclusiones

**INCLUIDO:**
- Campos nuevos en `MentoringSession` y `MentorProfile`
- 5 controllers frontend nuevos (mentores, formacion, expediente hub, orientador, coordinador)
- 5 templates Twig zero-region nuevos
- Servicio `HojaServicioMentoriaService` con generacion PDF via DomPDF
- EventSubscriber para auto-generacion post-sesion
- Integracion con `FirmaDigitalService` y flujo de firma AutoFirma
- Servicio `HumanMentorshipTracker` para actualizar horas de mentoria humana
- Servicio `ExpedienteCompletenessService` para checklist documental
- SCSS nuevo para `/mentors` y rutas del programa
- `hook_update_N()` para todos los cambios de schema

**EXCLUIDO:**
- Modificaciones al motor de matching de mentores (`MentorMatchingService`)
- Nuevo sistema de pagos para mentorias (ya existe via `StripeConnectService`)
- Videollamada integrada (ya existe via `VideoMeetingService` con Jitsi/Zoom)
- Cambios al copilot IA del participante
- Migracion de datos existentes de mentoring sessions

### 1.4 Filosofia de implementacion

1. **Reutilizacion maxima**: Consumir servicios existentes (`FirmaDigitalService`, `ExpedienteService`, `AiMentorshipTracker`, `TenantContextService`) — no duplicar logica.
2. **Zero Region Pattern**: Todas las paginas frontend usan `{{ clean_content }}` con layout limpio, variables via `hook_preprocess_page()`.
3. **Presave Resilience (PRESAVE-RESILIENCE-001)**: Servicios cross-modulo con `@?` + `hasService()` + `try-catch`. La sesion de mentoria DEBE completarse aunque falle la generacion de PDF.
4. **CSS Custom Properties (CSS-VAR-ALL-COLORS-001)**: Todo SCSS usa `var(--ej-*, fallback)`. Satelite `jaraba_andalucia_ei` NO define variables SCSS propias — solo consume CSS Custom Properties.
5. **Modales/Slide-Panel (SLIDE-PANEL-RENDER-001)**: Acciones crear/editar/firmar se abren en slide-panel para no abandonar la pagina.
6. **Textos traducibles**: Todo texto visible al usuario via `$this->t()` en PHP y `{% trans %}` en Twig. NUNCA strings hardcoded.

### 1.5 Estimacion de esfuerzo

| Fase | Concepto | Horas Min | Horas Max |
|------|----------|-----------|-----------|
| 1 | Infraestructura de datos | 3h | 4h |
| 2 | Mentores contextualizados | 6h | 8h |
| 3 | Formacion contextualizada | 4h | 6h |
| 4 | Hoja de servicio + firma electronica | 8h | 10h |
| 5 | Hub documental role-aware | 5h | 7h |
| 6 | Dashboard orientador | 4h | 6h |
| 7 | Dashboard coordinador | 4h | 6h |
| 8 | Elevacion CSS /mentors | 3h | 4h |
| 9 | Separacion bridges | 2h | 3h |
| | **TOTAL** | **39h** | **54h** |

---

## 2. Diagnostico del Estado Actual

### 2.1 Inventario de sistemas existentes

#### Modulo jaraba_andalucia_ei (estado actual)

| Componente | Cantidad | Detalle |
|-----------|----------|---------|
| Entidades | 3 | `ProgramaParticipanteEi`, `SolicitudEi`, `ExpedienteDocumento` |
| Servicios | 11 | AiMentorshipTracker, StoExport, FaseTransitionManager, SolicitudTriage, Expediente, DocumentoRevisionIa, InformeProgresoPdf, CopilotContextProvider, AdaptiveDifficulty, MensajeriaIntegration, CopilotBridge |
| Controllers | 7 | Dashboard, Portal, API (14 endpoints), STO Export, Solicitud, Landing, Guia |
| Templates | 5 | dashboard, portal, landing, solicitud, guia |
| Rutas frontend | 6 | `/andalucia-ei`, `/andalucia-ei/mi-participacion`, `/andalucia-ei/programa`, `/andalucia-ei/solicitar`, `/andalucia-ei/guia-participante`, `/andalucia-ei/informe-progreso` |
| Libraries | 4 | dashboard, solicitud-form, participante-portal, guia |

#### Modulo jaraba_mentoring (estado actual)

| Componente | Cantidad | Detalle |
|-----------|----------|---------|
| Entidades | 7 | `MentorProfile`, `MentoringEngagement`, `MentoringSession`, `MentoringPackage`, `AvailabilitySlot`, `SessionNotes`, `SessionReview`, `SessionTask` |
| Servicios | 5 | MentorMatching, SessionScheduler, StripeConnect, VideoMeeting, Breadcrumb |
| Controllers | 7 | MentorCatalog, MentorProfile, MentorDashboard, MentorApi, SessionApi, PackageApi, EngagementApi, ReviewApi |
| Rutas frontend | 3 | `/mentors`, `/mentor/{id}`, `/become-mentor`, `/mentor/dashboard` |
| CSS | **0 bytes** | `css/mentoring.css` referenciado pero NO existe |

#### Servicios ecosistema_jaraba_core relevantes

| Servicio | ID | Integracion |
|----------|-----|-------------|
| `FirmaDigitalService` | `ecosistema_jaraba_core.firma_digital` | PKCS#12 + OpenSSL + TSA (FNMT) |
| `AndaluciaEiCrossVerticalBridgeService` | — (no en services.yml, instanciado inline) | 4 bridges: emprendimiento, empleabilidad, servicios, formacion |
| `TenantContextService` | `ecosistema_jaraba_core.tenant_context` | Resuelve tenant via admin_user + group |
| `TenantBridgeService` | `ecosistema_jaraba_core.tenant_bridge` | Tenant <-> Group resolution |

### 2.2 Gaps identificados

| ID | Gap | Severidad | Fase |
|----|-----|-----------|------|
| GAP-AEI-01 | Hoja de servicio de mentoria inexistente | CRITICA | 4 |
| GAP-AEI-02 | /mentors sin filtro por edicion/grupo | CRITICA | 2 |
| GAP-AEI-03 | /courses descontextualizada de edicion | CRITICA | 3 |
| GAP-AEI-04 | Hub documentos sin segmentacion por fase/rol | CRITICA | 5 |
| GAP-AEI-05 | /mentors sin CSS (fichero no existe) | ALTA | 8 |
| GAP-AEI-06 | MentoringSession sin campos contenido documental | ALTA | 1 |
| GAP-AEI-07 | Firma electronica no integrada con mentoria | ALTA | 4 |
| GAP-AEI-08 | Horas mentoria humana no se actualizan en participante | ALTA | 1 |
| GAP-AEI-09 | Bridges mezclan programa con cross-selling | MEDIA | 9 |
| GAP-AEI-10 | Orientador sin dashboard de cartera | MEDIA | 6 |
| GAP-AEI-11 | Coordinador sin vista consolidada | MEDIA | 7 |
| GAP-AEI-12 | MentorProfile sin approval flow para ediciones | MEDIA | 2 |

### 2.3 Cadena de renderizacion actual del participante

```
Participante accede /andalucia-ei/mi-participacion
  -> ParticipantePortalController::portal()
  -> Resuelve ProgramaParticipanteEi via current_user + entity query
  -> Renderiza participante-portal.html.twig con 8 parciales:
     _hero, _timeline, _formacion, _expediente, _acciones, _logros, _mensajeria, _FAB
  -> Seccion "Acciones Rapidas" incluye:
     - "Ver cursos" -> /courses (GENERICO - GAP-AEI-03)
     - Bridges cross-vertical (formacion_continua -> /courses - GAP-AEI-03)
  -> /mentors accesible desde navegacion (GENERICO - GAP-AEI-02)
  -> Expediente parcial muestra docs sin segmentacion (GAP-AEI-04)
  -> NO hay generacion de hoja de servicio post-sesion (GAP-AEI-01)
```

**Cadena objetivo:**

```
Participante accede /andalucia-ei/mi-participacion
  -> Portal con acciones contextualizadas:
     - "Mis mentores" -> /programa/mentores (FILTRADO por edicion)
     - "Mi formacion" -> /programa/formacion (FILTRADO por edicion)
     - "Mi expediente" -> /programa/expediente (ROLE-AWARE)
  -> Seccion "Descubre mas" (cross-selling separado):
     - Bridges a otros verticales del SaaS
  -> Post-sesion mentoria completada:
     -> Auto-genera hoja de servicio PDF
     -> Solicita firma participante (AutoFirma/simple)
     -> Solicita firma orientador
     -> Almacena en ExpedienteDocumento
     -> Actualiza horas_mentoria_humana
```

---

## 3. Arquitectura Objetivo

### 3.1 Diagrama de flujo contextualizado

```
                    PARTICIPANTE
                        |
         /andalucia-ei/mi-participacion
                        |
          +-------------+-------------+
          |             |             |
   /programa/     /programa/    /programa/
    mentores      formacion     expediente
     (F2)          (F3)          (F5)
          |             |             |
   Filtro por      Filtro por    Vista por
   Group(edicion)  Group(edicion) Fase+Rol
          |             |             |
   Solicitar       Ver progreso  Checklist
   mentoria        horas/certs   completitud
          |                          |
   MentoringSession              ExpedienteDocumento
   status=completed                  |
          |                    mentoria_hoja_servicio
   EventSubscriber                   |
          |                   FirmaDigitalService
   +------+------+            +------+------+
   |             |            |             |
   HojaServicio  HumanMentor  Firma         Firma
   PdfService    shipTracker   Participante  Orientador
   (DomPDF)      (horas)       (AutoFirma)   (AutoFirma)
```

### 3.2 Modelo de datos ampliado

#### Campos nuevos en MentoringSession

| Campo | Tipo | Descripcion | Obligatorio |
|-------|------|-------------|-------------|
| `session_notes` | text_long | Resumen de lo trabajado en la sesion | No |
| `objectives_worked` | text_long | Objetivos especificos abordados | No |
| `agreements` | text_long | Acuerdos y compromisos establecidos | No |
| `next_steps` | text_long | Proximos pasos acordados | No |
| `participant_rating` | integer | Valoracion del participante 1-5 | No |
| `mentor_rating` | integer | Valoracion del mentor sobre el participante 1-5 | No |
| `service_sheet_doc` | entity_reference (expediente_documento) | Referencia a hoja de servicio generada | No |
| `firma_participante_status` | list_string (pending/signed/rejected) | Estado firma del participante | No |
| `firma_orientador_status` | list_string (pending/signed/rejected) | Estado firma del orientador | No |

**Justificacion:** Estos campos son necesarios para cumplir con la documentacion FSE/PIIL. La hoja de servicio requiere: que se trabajo (objectives_worked), que se acordo (agreements), y proximos pasos (next_steps). Las firmas son obligatorias para validacion STO.

#### Campo nuevo en MentorProfile

| Campo | Tipo | Descripcion | Obligatorio |
|-------|------|-------------|-------------|
| `program_groups` | entity_reference (group, multivalue) | Ediciones/grupos del programa a los que esta asignado | No |

**Justificacion:** Sin este campo, no hay forma de filtrar mentores por edicion del programa. Un mentor puede estar asignado a multiples ediciones simultaneamente.

#### Categorias nuevas en ExpedienteDocumento::CATEGORIAS

```php
// Documentos de mentoria (nuevos).
'mentoria_hoja_servicio' => 'Hoja de servicio de mentoria',
'orientacion_hoja_servicio' => 'Hoja de servicio de orientacion',
'orientacion_informe' => 'Informe de orientacion individual',
// Documentos de insercion (nuevos).
'insercion_contrato_laboral' => 'Contrato laboral',
'insercion_alta_ss' => 'Alta Seguridad Social',
```

### 3.3 Arquitectura de servicios

#### Servicios nuevos en jaraba_andalucia_ei

| Servicio | Clase | Dependencias | Funcion |
|----------|-------|-------------|---------|
| `jaraba_andalucia_ei.hoja_servicio_mentoria` | `HojaServicioMentoriaService` | `@entity_type.manager`, `@?ecosistema_jaraba_core.firma_digital`, `@jaraba_andalucia_ei.expediente`, `@logger.channel.jaraba_andalucia_ei`, `@file_system` | Genera PDF de hoja de servicio, almacena como ExpedienteDocumento, orquesta firma dual |
| `jaraba_andalucia_ei.human_mentorship_tracker` | `HumanMentorshipTracker` | `@entity_type.manager`, `@logger.channel.jaraba_andalucia_ei` | Actualiza `horas_mentoria_humana` en ProgramaParticipanteEi cuando una MentoringSession se completa |
| `jaraba_andalucia_ei.expediente_completeness` | `ExpedienteCompletenessService` | `@entity_type.manager`, `@jaraba_andalucia_ei.expediente`, `@logger.channel.jaraba_andalucia_ei` | Calcula completitud documental por fase (atencion/insercion) y genera checklists |

**Nota sobre dependencias cross-modulo (OPTIONAL-CROSSMODULE-001):** `@?ecosistema_jaraba_core.firma_digital` usa `@?` porque `ecosistema_jaraba_core` es el modulo core y la firma digital puede no estar configurada. `@jaraba_andalucia_ei.expediente` usa `@` directo porque es del mismo modulo.

#### EventSubscriber nuevo

| Subscriber | Evento | Accion |
|-----------|--------|--------|
| `MentoringCompletedSubscriber` | `hook_entity_presave` en `mentoring_session` cuando `status` cambia a `completed` | 1. Calcula duracion, 2. Actualiza horas via `HumanMentorshipTracker`, 3. Genera hoja via `HojaServicioMentoriaService`, 4. Solicita firmas |

**Implementacion:** Se implementa como `hook_entity_presave()` en `jaraba_andalucia_ei.module` (no como EventSubscriber puro) porque necesita interceptar el cambio de estado de una entidad de otro modulo (`jaraba_mentoring`). Patron PRESAVE-RESILIENCE-001 con `\Drupal::hasService()` + try-catch.

### 3.4 Mapa de rutas frontend

| Ruta | Controller | Template | Permiso | Descripcion |
|------|-----------|----------|---------|-------------|
| `/programa/mentores` | `ProgramaMentoresController::mentores()` | `programa-mentores.html.twig` | `access content` + `_participante_access` | Mentores de la edicion del participante |
| `/programa/formacion` | `ProgramaFormacionController::formacion()` | `programa-formacion.html.twig` | `access content` + `_participante_access` | Cursos de la edicion con progreso |
| `/programa/expediente` | `ExpedienteHubController::hub()` | `expediente-hub.html.twig` | `access content` + `_participante_access` | Hub documental role-aware |
| `/programa/orientador` | `OrientadorDashboardController::dashboard()` | `orientador-dashboard.html.twig` | `view programa participante ei` | Dashboard de cartera del orientador |
| `/programa/coordinador` | `CoordinadorDashboardController::dashboard()` | `coordinador-dashboard.html.twig` | `administer andalucia ei` | Metricas por edicion |
| `/api/v1/programa/hoja-servicio/{session_id}/firmar` | `HojaServicioApiController::firmar()` | — | `access content` + CSRF | Endpoint firma electronica |

**Nota (ROUTE-LANGPREFIX-001):** Todas las URLs en JS se resuelven via `drupalSettings` inyectados en `hook_preprocess_page()`. NUNCA paths hardcoded.

---

## 4. Requisitos Previos

### 4.1 Software

| Software | Version | Verificacion |
|----------|---------|-------------|
| PHP | 8.4+ | `lando ssh -c "php -v"` |
| Drupal | 11.x | `lando drush status` |
| MariaDB | 10.11+ | `lando ssh -c "mysql --version"` |
| DomPDF | 2.0+ | `lando ssh -c "composer show dompdf/dompdf"` |
| Dart Sass | 1.71+ | `lando ssh -c "cd /app/web/modules/custom/jaraba_andalucia_ei && npx sass --version"` |
| OpenSSL | 3.x | `lando ssh -c "openssl version"` (para firma digital) |

### 4.2 Modulos dependientes

| Modulo | Obligatorio | Verificacion |
|--------|------------|-------------|
| `ecosistema_jaraba_core` | SI | Servicios core: TenantContext, TenantBridge, FirmaDigital |
| `jaraba_mentoring` | SI | Entidades MentoringSession, MentorProfile, Engagement |
| `jaraba_andalucia_ei` | SI | Entidades participante, expediente, servicios existentes |
| `jaraba_legal_vault` | Opcional (@?) | Almacenamiento encriptado de documentos |
| `jaraba_lms` | Opcional (@?) | Cursos y catalogo formativo |

### 4.3 Configuracion previa

- Certificado PKCS#12 configurado via `ECOSISTEMA_JARABA_CERT_PATH` y `ECOSISTEMA_JARABA_CERT_PASSWORD` en `settings.secrets.php` (SECRET-MGMT-001)
- Al menos un Group (edicion) creado con participantes asignados
- Mentores con `MentorProfile` activos

---

## 5. Fases de Implementacion

### 5.1 Fase 1 — Infraestructura de Datos (P0)

**Objetivo:** Ampliar el modelo de datos de `MentoringSession` y `MentorProfile` con los campos necesarios para documentacion y contextualizacion por edicion. Anadir categorias nuevas a `ExpedienteDocumento`.

**Problema:** `MentoringSession` solo tiene campos operativos (scheduling, status, meeting_url). No almacena QUE se trabajo, QUE se acordo, ni tiene referencia a documentos generados. `MentorProfile` no tiene referencia a Group (edicion).

**Impacto:** Sin estos campos, las fases 2-7 no pueden funcionar.

**Solucion tecnica:**

#### 5.1.1 Campos nuevos en MentoringSession

Modificar `web/modules/custom/jaraba_mentoring/src/Entity/MentoringSession.php`:

```php
// === CONTENIDO DOCUMENTAL (post-sesion) ===

$fields['session_notes'] = BaseFieldDefinition::create('text_long')
    ->setLabel(t('Resumen de la sesion'))
    ->setDescription(t('Descripcion de lo trabajado durante la sesion.'))
    ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 25,
    ])
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);

$fields['objectives_worked'] = BaseFieldDefinition::create('text_long')
    ->setLabel(t('Objetivos trabajados'))
    ->setDescription(t('Objetivos especificos abordados en la sesion.'))
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);

$fields['agreements'] = BaseFieldDefinition::create('text_long')
    ->setLabel(t('Acuerdos y compromisos'))
    ->setDescription(t('Acuerdos alcanzados entre mentor y participante.'))
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);

$fields['next_steps'] = BaseFieldDefinition::create('text_long')
    ->setLabel(t('Proximos pasos'))
    ->setDescription(t('Acciones a realizar antes de la siguiente sesion.'))
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);

// === VALORACIONES ===

$fields['participant_rating'] = BaseFieldDefinition::create('integer')
    ->setLabel(t('Valoracion del participante'))
    ->setDescription(t('Valoracion del participante sobre la sesion (1-5).'))
    ->setSetting('min', 1)
    ->setSetting('max', 5)
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);

$fields['mentor_rating'] = BaseFieldDefinition::create('integer')
    ->setLabel(t('Valoracion del mentor'))
    ->setDescription(t('Valoracion del mentor sobre la sesion (1-5).'))
    ->setSetting('min', 1)
    ->setSetting('max', 5)
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);

// === HOJA DE SERVICIO ===

$fields['service_sheet_doc'] = BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Hoja de servicio'))
    ->setDescription(t('Documento de hoja de servicio generado automaticamente.'))
    ->setSetting('target_type', 'expediente_documento')
    ->setDisplayConfigurable('view', TRUE);

// === ESTADO DE FIRMA ===

$fields['firma_participante_status'] = BaseFieldDefinition::create('list_string')
    ->setLabel(t('Firma participante'))
    ->setSetting('allowed_values', [
        'not_required' => t('No requerida'),
        'pending' => t('Pendiente'),
        'signed' => t('Firmada'),
        'rejected' => t('Rechazada'),
    ])
    ->setDefaultValue('not_required')
    ->setDisplayConfigurable('view', TRUE);

$fields['firma_orientador_status'] = BaseFieldDefinition::create('list_string')
    ->setLabel(t('Firma orientador'))
    ->setSetting('allowed_values', [
        'not_required' => t('No requerida'),
        'pending' => t('Pendiente'),
        'signed' => t('Firmada'),
        'rejected' => t('Rechazada'),
    ])
    ->setDefaultValue('not_required')
    ->setDisplayConfigurable('view', TRUE);
```

#### 5.1.2 Campo nuevo en MentorProfile

Modificar `web/modules/custom/jaraba_mentoring/src/Entity/MentorProfile.php`:

```php
// === ASIGNACION A EDICIONES (programa) ===

$fields['program_groups'] = BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Ediciones del programa'))
    ->setDescription(t('Ediciones o grupos del programa a los que este mentor esta asignado.'))
    ->setSetting('target_type', 'group')
    ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
    ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 15,
    ])
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);
```

#### 5.1.3 Categorias nuevas en ExpedienteDocumento

Modificar `web/modules/custom/jaraba_andalucia_ei/src/Entity/ExpedienteDocumento.php`, anadir al array `CATEGORIAS`:

```php
// Documentos de mentoria.
'mentoria_hoja_servicio' => 'Hoja de servicio de mentoria',
// Documentos de orientacion.
'orientacion_hoja_servicio' => 'Hoja de servicio de orientacion',
'orientacion_informe' => 'Informe de orientacion individual',
// Documentos de insercion.
'insercion_contrato_laboral' => 'Contrato laboral',
'insercion_alta_ss' => 'Alta Seguridad Social',
```

**Nota:** Las categorias son una constante PHP (no schema de BD), por lo que NO necesitan `hook_update_N()`. Pero el campo `categoria` con `allowed_values` callback SI debe reflejar estos nuevos valores.

#### 5.1.4 hook_update_N() obligatorios (UPDATE-HOOK-REQUIRED-001)

Crear en `jaraba_mentoring.install`:

```php
/**
 * Add content documentation and signature fields to MentoringSession.
 */
function jaraba_mentoring_update_10010(): void {
  $manager = \Drupal::entityDefinitionUpdateManager();
  $manager->installEntityType(
    \Drupal::entityTypeManager()->getDefinition('mentoring_session')
  );
}

/**
 * Add program_groups field to MentorProfile.
 */
function jaraba_mentoring_update_10011(): void {
  $manager = \Drupal::entityDefinitionUpdateManager();
  $manager->installEntityType(
    \Drupal::entityTypeManager()->getDefinition('mentor_profile')
  );
}
```

**Nota (UPDATE-HOOK-CATCH-001):** Si se usa try-catch, DEBE ser `\Throwable`, no `\Exception`.

**Ficheros modificados:**
- `web/modules/custom/jaraba_mentoring/src/Entity/MentoringSession.php`
- `web/modules/custom/jaraba_mentoring/src/Entity/MentorProfile.php`
- `web/modules/custom/jaraba_mentoring/jaraba_mentoring.install`
- `web/modules/custom/jaraba_andalucia_ei/src/Entity/ExpedienteDocumento.php`

**Verificacion:**
```bash
lando drush updatedb -y
lando drush entity:updates
```

---

### 5.2 Fase 2 — Mentores Contextualizados por Edicion (P0)

**Objetivo:** Crear ruta `/programa/mentores` que muestre solo mentores asignados a la edicion del participante, con flujo de solicitud y aprobacion por coordinador.

**Problema:** `/mentors` muestra TODOS los mentores del SaaS. Un participante de la edicion 3 de Andalucia +ei ve mentores de emprendimiento, comercio, etc.

**Impacto:** El participante vera solo mentores relevantes de su edicion. La solicitud pasa por aprobacion del coordinador/orientador.

**Solucion tecnica:**

#### 5.2.1 Controller ProgramaMentoresController

Crear `web/modules/custom/jaraba_andalucia_ei/src/Controller/ProgramaMentoresController.php`:

**Logica del controller:**

1. Resolver `ProgramaParticipanteEi` del usuario actual via entity query por `user_id`.
2. Obtener el Group (edicion) del participante via `TenantBridgeService` o consulta directa de membership del Group module.
3. Consultar `MentorProfile` filtrando por `program_groups` que contenga dicho Group.
4. Preparar array de datos para el template: nombre, avatar, especialidades, rating, bio, disponibilidad.
5. Devolver render array minimo (ZERO-REGION-001). Variables inyectadas en `hook_preprocess_page()`.

**Nota (CONTROLLER-READONLY-001):** El controller NO debe redeclarar `$entityTypeManager` con `protected readonly` porque hereda de `ControllerBase`.

#### 5.2.2 Template programa-mentores.html.twig

Crear `web/modules/custom/jaraba_andalucia_ei/templates/programa-mentores.html.twig`:

**Estructura:**
- Hero contextualizado con nombre de la edicion y stats (X mentores disponibles)
- Grid responsive de tarjetas de mentor (CSS Grid, `auto-fill`, `minmax(300px, 1fr)`)
- Cada tarjeta: avatar (o SVG placeholder), nombre, especialidades (badges), rating (estrellas), experiencia, CTA "Solicitar mentoria"
- CTA abre slide-panel con formulario de solicitud (SLIDE-PANEL-RENDER-001)
- Textos: `{% trans %}` siempre

**Iconos (ICON-CONVENTION-001):**
```twig
{{ jaraba_icon('social', 'mentor', { variant: 'duotone', color: 'azul-corporativo', size: '24px' }) }}
```

#### 5.2.3 Ruta y preprocess

En `jaraba_andalucia_ei.routing.yml`:
```yaml
jaraba_andalucia_ei.programa_mentores:
  path: '/programa/mentores'
  defaults:
    _controller: '\Drupal\jaraba_andalucia_ei\Controller\ProgramaMentoresController::mentores'
    _title: 'Mis Mentores'
  requirements:
    _permission: 'access content'
    _participante_access: 'TRUE'
```

En `jaraba_andalucia_ei.module`, hook para inyectar variables y drupalSettings:
```php
function jaraba_andalucia_ei_preprocess_page__programa_mentores(&$variables) {
  // Variables: mentores, edicion_nombre, stats
  // drupalSettings: apiEndpoints para slide-panel
}
```

Template suggestion en `ecosistema_jaraba_theme`: `page--programa--mentores.html.twig` con `{{ clean_content }}`, `{{ clean_messages }}`, includes de header/footer parciales.

**Ficheros nuevos:**
- `web/modules/custom/jaraba_andalucia_ei/src/Controller/ProgramaMentoresController.php`
- `web/modules/custom/jaraba_andalucia_ei/templates/programa-mentores.html.twig`
- `web/themes/custom/ecosistema_jaraba_theme/templates/pages/page--programa--mentores.html.twig`

**Ficheros modificados:**
- `web/modules/custom/jaraba_andalucia_ei/jaraba_andalucia_ei.routing.yml`
- `web/modules/custom/jaraba_andalucia_ei/jaraba_andalucia_ei.module` (preprocess + theme suggestions)

---

### 5.3 Fase 3 — Formacion Contextualizada por Edicion (P0)

**Objetivo:** Crear ruta `/programa/formacion` que muestre cursos de la edicion del participante con su progreso, horas acumuladas vs objetivo, y certificados obtenidos. Redirigir el bridge `formacion_continua` y la accion rapida "Ver cursos" a esta ruta.

**Problema:** El bridge `formacion_continua` en `AndaluciaEiCrossVerticalBridgeService` (linea 77) apunta a `'/courses'` — el catalogo generico del LMS. El participante ve TODOS los cursos del SaaS.

**Solucion tecnica:**

#### 5.3.1 Controller ProgramaFormacionController

Crear `web/modules/custom/jaraba_andalucia_ei/src/Controller/ProgramaFormacionController.php`:

**Logica:**
1. Resolver participante y Group.
2. Consultar cursos/contenidos formativos del LMS que pertenezcan al Group de la edicion (via Group content o campo de referencia).
3. Calcular progreso formativo: `horas_formacion` actual vs objetivo (segun carril: Impulso Digital o Acelera Pro).
4. Listar certificados obtenidos.
5. Renderizar template con hero + progreso + grid de cursos.

**Dependencia con jaraba_lms (OPTIONAL-CROSSMODULE-001):** Usar `@?jaraba_lms.catalog` si existe, con fallback a entidad query directa con `\Drupal::hasService()`.

#### 5.3.2 Corregir bridge formacion_continua

Modificar `web/modules/custom/ecosistema_jaraba_core/src/Service/AndaluciaEiCrossVerticalBridgeService.php`, linea 77:

```php
// ANTES:
'cta_url' => '/courses',
// DESPUES:
'cta_url' => '/programa/formacion',
```

#### 5.3.3 Corregir accion rapida del portal

Modificar `web/modules/custom/jaraba_andalucia_ei/src/Controller/ParticipantePortalController.php` en la seccion de acciones rapidas para que "Ver cursos" apunte a `/programa/formacion` (via `Url::fromRoute('jaraba_andalucia_ei.programa_formacion')`).

**Ficheros nuevos:**
- `web/modules/custom/jaraba_andalucia_ei/src/Controller/ProgramaFormacionController.php`
- `web/modules/custom/jaraba_andalucia_ei/templates/programa-formacion.html.twig`
- `web/themes/custom/ecosistema_jaraba_theme/templates/pages/page--programa--formacion.html.twig`

**Ficheros modificados:**
- `web/modules/custom/jaraba_andalucia_ei/jaraba_andalucia_ei.routing.yml`
- `web/modules/custom/ecosistema_jaraba_core/src/Service/AndaluciaEiCrossVerticalBridgeService.php`
- `web/modules/custom/jaraba_andalucia_ei/src/Controller/ParticipantePortalController.php`
- `web/modules/custom/jaraba_andalucia_ei/jaraba_andalucia_ei.module` (preprocess)

---

### 5.4 Fase 4 — Hoja de Servicio y Firma Electronica (P0)

**Objetivo:** Auto-generar PDF de hoja de servicio al completar una sesion de mentoria, con firma electronica dual (participante + orientador) via `FirmaDigitalService` y AutoFirma JS.

**Problema:** No existe ningun mecanismo de generacion de hojas de servicio. Las horas de mentoria NO son justificables ante STO sin documentacion firmada.

**Impacto:** Cumplimiento FSE/PIIL. Cada sesion completada produce un PDF firmado almacenado en el expediente del participante.

**Solucion tecnica:**

#### 5.4.1 HojaServicioMentoriaService

Crear `web/modules/custom/jaraba_andalucia_ei/src/Service/HojaServicioMentoriaService.php`:

**Metodos publicos:**

```php
/**
 * Genera la hoja de servicio PDF para una sesion de mentoria completada.
 *
 * PROPOSITO:
 * Crea un PDF con formato de hoja de servicio que incluye datos del
 * participante, mentor, objetivos trabajados, acuerdos y duracion.
 * Lo almacena como ExpedienteDocumento categoria 'mentoria_hoja_servicio'.
 *
 * FLUJO DE EJECUCION:
 * 1. Cargar datos de la sesion, participante y mentor
 * 2. Renderizar HTML con Twig (template hoja-servicio-mentoria.html.twig)
 * 3. Convertir a PDF via DomPDF (patron CvBuilderService)
 * 4. Almacenar en private:// via ExpedienteService::subirDocumento()
 * 5. Vincular al campo service_sheet_doc de MentoringSession
 * 6. Marcar firma_participante_status y firma_orientador_status como 'pending'
 *
 * REGLAS DE NEGOCIO:
 * - Solo genera si status = 'completed' y session_notes no vacio
 * - DomPDF con isRemoteEnabled=FALSE (seguridad)
 * - CSS embebido inline (DomPDF no soporta var(--ej-*), usar hex fallback)
 * - Tamanio A4 portrait
 *
 * @param \Drupal\jaraba_mentoring\Entity\MentoringSession $session
 *   La sesion de mentoria completada.
 *
 * @return \Drupal\jaraba_andalucia_ei\Entity\ExpedienteDocumentoInterface|null
 *   El documento creado o NULL si falla.
 */
public function generarHojaServicio(MentoringSession $session): ?ExpedienteDocumentoInterface;

/**
 * Procesa la firma electronica de un documento de hoja de servicio.
 *
 * PROPOSITO:
 * Invoca FirmaDigitalService::signPdf() para firmar el PDF y actualiza
 * el estado de firma en MentoringSession.
 *
 * @param int $documentoId
 *   ID del ExpedienteDocumento.
 * @param string $firmante
 *   Tipo de firmante: 'participante' o 'orientador'.
 *
 * @return bool
 *   TRUE si la firma se completo correctamente.
 */
public function firmarHojaServicio(int $documentoId, string $firmante): bool;
```

**Patron DomPDF (siguiendo CvBuilderService):**
```php
$options = new \Dompdf\Options();
$options->set('isRemoteEnabled', FALSE);
$options->set('isHtml5ParserEnabled', TRUE);
$options->set('defaultFont', 'Helvetica');
$options->set('dpi', 150);

$dompdf = new \Dompdf\Dompdf($options);
$dompdf->loadHtml($htmlContent);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$pdfContent = $dompdf->output();
```

**CSS en PDF:** DomPDF NO soporta CSS Custom Properties (`var(--ej-*)`). El CSS del PDF usa hex fallback directos. Esto es aceptable porque el PDF no es una pagina web — es un documento estatico.

#### 5.4.2 Template hoja-servicio-mentoria.html.twig

Template Twig para renderizar el HTML que DomPDF convierte a PDF:

```
+---------------------------------------------------+
|  LOGO JARABA          HOJA DE SERVICIO             |
|                       PROGRAMA ANDALUCIA +EI       |
+---------------------------------------------------+
|  Edicion: [nombre]    Fecha: [dd/mm/yyyy]          |
+---------------------------------------------------+
|  DATOS DEL PARTICIPANTE                            |
|  Nombre: [...]  DNI: [...]  Carril: [...]          |
+---------------------------------------------------+
|  DATOS DEL MENTOR                                  |
|  Nombre: [...]  Especialidades: [...]              |
+---------------------------------------------------+
|  SESION Nro [X]       Tipo: [Seguimiento]          |
|  Inicio: [HH:MM]     Fin: [HH:MM]                 |
|  Duracion: [X h XX min]                            |
+---------------------------------------------------+
|  OBJETIVOS TRABAJADOS                              |
|  [texto libre]                                     |
+---------------------------------------------------+
|  ACUERDOS Y COMPROMISOS                            |
|  [texto libre]                                     |
+---------------------------------------------------+
|  PROXIMOS PASOS                                    |
|  [texto libre]                                     |
+---------------------------------------------------+
|  FIRMA PARTICIPANTE        FIRMA ORIENTADOR        |
|  [estado/fecha]            [estado/fecha]          |
+---------------------------------------------------+
```

#### 5.4.3 hook_entity_presave en jaraba_andalucia_ei.module

```php
/**
 * Implements hook_entity_presave() for mentoring_session.
 *
 * PRESAVE-RESILIENCE-001: Usa hasService() + try-catch.
 * La sesion DEBE completarse aunque falle la generacion de PDF.
 */
function jaraba_andalucia_ei_mentoring_session_presave(EntityInterface $entity): void {
  if (!$entity instanceof MentoringSession) {
    return;
  }

  // Solo actuar cuando status cambia a 'completed'.
  if (!$entity->isNew() && $entity->get('status')->value === 'completed') {
    $original = $entity->original ?? NULL;
    if ($original && $original->get('status')->value !== 'completed') {
      // 1. Actualizar horas de mentoria humana.
      try {
        if (\Drupal::hasService('jaraba_andalucia_ei.human_mentorship_tracker')) {
          \Drupal::service('jaraba_andalucia_ei.human_mentorship_tracker')
            ->registrarSesion($entity);
        }
      }
      catch (\Throwable $e) {
        \Drupal::logger('jaraba_andalucia_ei')->error(
          'Error actualizando horas mentoria humana: @msg', ['@msg' => $e->getMessage()]
        );
      }

      // 2. Generar hoja de servicio PDF.
      try {
        if (\Drupal::hasService('jaraba_andalucia_ei.hoja_servicio_mentoria')) {
          \Drupal::service('jaraba_andalucia_ei.hoja_servicio_mentoria')
            ->generarHojaServicio($entity);
        }
      }
      catch (\Throwable $e) {
        \Drupal::logger('jaraba_andalucia_ei')->error(
          'Error generando hoja de servicio: @msg', ['@msg' => $e->getMessage()]
        );
      }
    }
  }
}
```

#### 5.4.4 API de firma y JS

Endpoint REST para la firma:
```yaml
jaraba_andalucia_ei.api.hoja_servicio.firmar:
  path: '/api/v1/programa/hoja-servicio/{document_id}/firmar'
  defaults:
    _controller: '\Drupal\jaraba_andalucia_ei\Controller\HojaServicioApiController::firmar'
  methods: [POST]
  requirements:
    _permission: 'access content'
    _csrf_request_header_token: 'TRUE'
    document_id: '\d+'
```

El frontend utiliza `ecosistema-jaraba-firma.js` (ya existente) que integra AutoFirma via WebSocket (puertos 63117/63217/63317). El flujo de firma se dispara desde el portal del participante o desde notificacion por email.

**Ficheros nuevos:**
- `web/modules/custom/jaraba_andalucia_ei/src/Service/HojaServicioMentoriaService.php`
- `web/modules/custom/jaraba_andalucia_ei/src/Service/HumanMentorshipTracker.php`
- `web/modules/custom/jaraba_andalucia_ei/src/Controller/HojaServicioApiController.php`
- `web/modules/custom/jaraba_andalucia_ei/templates/hoja-servicio-mentoria.html.twig`

**Ficheros modificados:**
- `web/modules/custom/jaraba_andalucia_ei/jaraba_andalucia_ei.module` (hook_entity_presave)
- `web/modules/custom/jaraba_andalucia_ei/jaraba_andalucia_ei.services.yml`
- `web/modules/custom/jaraba_andalucia_ei/jaraba_andalucia_ei.routing.yml`

---

### 5.5 Fase 5 — Hub Documental Role-Aware (P1)

**Objetivo:** Crear vista de expediente organizada por fase del programa, con checklist de completitud, diferenciada por rol.

**Problema:** El parcial `_expediente` del portal muestra documentos sin segmentar por fase (atencion/insercion) ni indicar que documentos faltan. No hay vista para orientador ni coordinador.

**Solucion tecnica:**

#### 5.5.1 ExpedienteCompletenessService

Crear `web/modules/custom/jaraba_andalucia_ei/src/Service/ExpedienteCompletenessService.php`:

**Logica:**
- Define mapa de documentos requeridos por fase:
  - **Fase Atencion**: sto_dni, sto_empadronamiento, sto_vida_laboral, sto_demanda_empleo, sto_prestaciones, sto_titulo_academico, programa_contrato, programa_consentimiento, programa_compromiso
  - **Fase Atencion (en curso)**: tarea_diagnostico, tarea_plan_empleo, tarea_cv, mentoria_hoja_servicio (al menos 1)
  - **Fase Insercion**: insercion_contrato_laboral, insercion_alta_ss, cert_formacion, cert_participacion
- Consulta documentos existentes del participante
- Genera checklist con estado: completado/pendiente/rechazado por cada documento
- Calcula porcentaje de completitud por fase y total

#### 5.5.2 ExpedienteHubController

Crear `web/modules/custom/jaraba_andalucia_ei/src/Controller/ExpedienteHubController.php`:

**Logica por rol:**
- **Participante**: Su propio expediente con checklist visual, upload de documentos (slide-panel), firmas pendientes.
- **Orientador**: Hub con todos sus participantes asignados, alertas de docs pendientes, acciones de revision rapida.
- **Coordinador**: Vista consolidada con metricas de completitud por edicion.

Deteccion de rol: Verificar permisos Drupal (`administer andalucia ei` para coordinador, `view programa participante ei` para orientador, `_participante_access` para participante).

#### 5.5.3 Template expediente-hub.html.twig

Estructura:
- Hero con titulo "Expediente Documental" + completitud global (barra de progreso circular)
- Tabs por fase: "Documentacion Inicial" | "En Curso" | "Insercion"
- Cada tab: grid de items documento con estado (check verde, reloj naranja, X rojo)
- CTA para subir documento faltante (abre slide-panel)
- Seccion "Firmas pendientes" con acciones directas

**Ficheros nuevos:**
- `web/modules/custom/jaraba_andalucia_ei/src/Service/ExpedienteCompletenessService.php`
- `web/modules/custom/jaraba_andalucia_ei/src/Controller/ExpedienteHubController.php`
- `web/modules/custom/jaraba_andalucia_ei/templates/expediente-hub.html.twig`
- `web/themes/custom/ecosistema_jaraba_theme/templates/pages/page--programa--expediente.html.twig`

---

### 5.6 Fase 6 — Dashboard Orientador (P1)

**Objetivo:** Panel de gestion para que el orientador visualice su cartera de participantes con estado, alertas y acciones rapidas.

**Solucion tecnica:**

#### Controller OrientadorDashboardController

Ruta: `/programa/orientador`

**Secciones del dashboard:**
1. **KPI cards**: Total participantes asignados, docs pendientes revision, sesiones esta semana, tasa insercion.
2. **Tabla de participantes**: Nombre, fase, horas acumuladas (barras), completitud documental (%), alertas.
3. **Alertas**: Participantes inactivos >7 dias, documentos rechazados, hojas de servicio sin firma >48h.
4. **Acciones rapidas**: Revisar documento (slide-panel), firmar hoja de servicio, programar sesion.

**Resolucion de "mis participantes":** Query `ProgramaParticipanteEi` por campo `orientador_id` = current_user (si existe) o via Group membership.

**Ficheros nuevos:**
- `web/modules/custom/jaraba_andalucia_ei/src/Controller/OrientadorDashboardController.php`
- `web/modules/custom/jaraba_andalucia_ei/templates/orientador-dashboard.html.twig`
- `web/themes/custom/ecosistema_jaraba_theme/templates/pages/page--programa--orientador.html.twig`

---

### 5.7 Fase 7 — Dashboard Coordinador (P1)

**Objetivo:** Vista consolidada por edicion con metricas agregadas para el coordinador/admin del programa.

**Solucion tecnica:**

Ruta: `/programa/coordinador`

**Secciones:**
1. **Selector de edicion**: Dropdown de Groups (ediciones) administradas.
2. **KPI scorecards**: Participantes por fase (atencion/insercion/baja), completitud documental media, horas acumuladas vs objetivo, tasa insercion.
3. **Grafico de distribucion por fase**: Donut chart o barras horizontales.
4. **Tabla de orientadores**: Con metricas de su cartera.
5. **Alertas STO**: Documentos pendientes con plazo critico, participantes sin actividad.
6. **Exportacion**: Boton "Exportar para STO" (enlaza a ruta existente `/admin/content/andalucia-ei/export-sto`).

**Ficheros nuevos:**
- `web/modules/custom/jaraba_andalucia_ei/src/Controller/CoordinadorDashboardController.php`
- `web/modules/custom/jaraba_andalucia_ei/templates/coordinador-dashboard.html.twig`
- `web/themes/custom/ecosistema_jaraba_theme/templates/pages/page--programa--coordinador.html.twig`

---

### 5.8 Fase 8 — Elevacion CSS /mentors (P1)

**Objetivo:** Crear los estilos CSS para la pagina `/mentors` que actualmente renderiza sin ningun estilo (el fichero `css/mentoring.css` referenciado en la library no existe).

**Solucion tecnica:**

#### 5.8.1 SCSS nuevo

Crear `web/modules/custom/jaraba_mentoring/scss/_mentoring.scss`:

**Estructura SCSS:**
```scss
/**
 * @file
 * Estilos para el catalogo de mentores y paginas de mentoria.
 *
 * DIRECTRIZ: Usa Design Tokens con CSS Custom Properties (var(--ej-*))
 * NO define variables SCSS propias — solo var(--ej-*) con fallbacks.
 *
 * COMPILACION:
 * docker exec jarabasaas_appserver_1 bash -c \
 *   "cd /app/web/modules/custom/jaraba_mentoring && npx sass scss/main.scss css/mentoring.css --style=compressed"
 */
```

**Componentes CSS:**
- `.mentor-catalog__hero`: Hero section con fondo gradiente `var(--ej-color-naranja-impulso, #FF8C42)` a `color-mix()`
- `.mentor-catalog__filters`: Barra de filtros (especialidad, idioma, disponibilidad) con pills
- `.mentor-catalog__grid`: CSS Grid, `repeat(auto-fill, minmax(320px, 1fr))`, gap `1.25rem`
- `.mentor-card`: Tarjeta premium con hover elevation `-6px`, shine effect, avatar circular, rating estrellas, badges de especialidad, precio, CTA
- `.mentor-card__avatar`: `width: 80px; height: 80px; border-radius: 50%; object-fit: cover;`
- `.mentor-card__rating`: Estrellas SVG con `var(--ej-color-naranja-impulso)`
- `.mentor-card__cta`: Boton primario con `var(--ej-color-primary, #FF8C42)`

**Reglas CSS-VAR-ALL-COLORS-001:**
```scss
// CORRECTO:
.mentor-card {
  background: var(--ej-color-bg-surface, #FFFFFF);
  border: 1px solid var(--ej-border-color, #E2E8F0);
  &:hover {
    box-shadow: 0 12px 32px color-mix(in srgb, var(--ej-color-dark, #1a1a2e) 10%, transparent);
  }
}

// INCORRECTO (PROHIBIDO):
// background: #FFFFFF;
// border: 1px solid #E2E8F0;
// box-shadow: 0 12px 32px rgba(0,0,0,0.1);
```

#### 5.8.2 package.json

Crear `web/modules/custom/jaraba_mentoring/package.json`:
```json
{
  "name": "jaraba-mentoring",
  "version": "1.0.0",
  "description": "Estilos SCSS para modulo de mentorias",
  "scripts": {
    "build": "sass scss/main.scss:css/mentoring.css --style=compressed",
    "watch": "sass --watch scss:css --style=compressed"
  },
  "devDependencies": {
    "sass": "^1.71.0"
  }
}
```

#### 5.8.3 Compilacion y verificacion (SCSS-COMPILE-VERIFY-001)

```bash
lando ssh -c "cd /app/web/modules/custom/jaraba_mentoring && npx sass scss/main.scss css/mentoring.css --style=compressed"
# Verificar timestamp CSS > SCSS:
lando ssh -c "stat -c '%Y %n' /app/web/modules/custom/jaraba_mentoring/css/mentoring.css /app/web/modules/custom/jaraba_mentoring/scss/main.scss"
```

**Ficheros nuevos:**
- `web/modules/custom/jaraba_mentoring/scss/main.scss`
- `web/modules/custom/jaraba_mentoring/scss/_mentoring.scss`
- `web/modules/custom/jaraba_mentoring/css/mentoring.css` (compilado)
- `web/modules/custom/jaraba_mentoring/package.json`

---

### 5.9 Fase 9 — Separacion Bridges Programa vs Cross-Selling (P2)

**Objetivo:** Diferenciar visualmente en el portal del participante entre contenido del programa (mentores, cursos, expediente de la edicion) y ofertas comerciales del SaaS (bridges a otros verticales).

**Solucion tecnica:**

Modificar `ParticipantePortalController::portal()` y el template `participante-portal.html.twig`:

**Seccion "Mi Programa" (contenido contextualizado):**
- Mis mentores -> `/programa/mentores`
- Mi formacion -> `/programa/formacion`
- Mi expediente -> `/programa/expediente`
- Timeline de fases
- Horas y progreso

**Seccion "Descubre mas" (cross-selling SaaS):**
- Card diferenciada visualmente (borde punteado, icono de descubrimiento)
- Bridges de `AndaluciaEiCrossVerticalBridgeService` (emprendimiento, empleabilidad, servicios)
- Label claro: "Otros servicios que pueden interesarte"

**Ficheros modificados:**
- `web/modules/custom/jaraba_andalucia_ei/src/Controller/ParticipantePortalController.php`
- `web/modules/custom/jaraba_andalucia_ei/templates/participante-portal.html.twig`

---

## 6. Tabla de Correspondencia con Especificaciones Tecnicas

| Especificacion | Componente Reutilizado | Seccion del Plan |
|---------------|----------------------|------------------|
| Spec 32 Mentoring Sessions v1 | `MentoringSession` entity, `SessionSchedulerService`, `VideoMeetingService` | 5.1, 5.4 |
| Arquitectura Theming SSOT | CSS Custom Properties `var(--ej-*)`, compilacion Dart Sass | 5.8, 8.3, 8.4 |
| ExpedienteDocumento 19 categorias | `ExpedienteService` CRUD, vault integration | 5.1.3, 5.5 |
| FirmaDigitalService PKCS#12 | `signPdf()`, `verifySignature()`, `getCertificateInfo()` | 5.4 |
| AutoFirma WebSocket JS | `ecosistema-jaraba-firma.js`, puertos 63117/63217/63317, PAdES | 5.4.4 |
| DomPDF patron CvBuilderService | `isRemoteEnabled=FALSE`, A4 portrait, CSS inline | 5.4.1 |
| ProgramaParticipanteEi tracking horas | Campos `horas_mentoria_humana`, `horas_formacion`, `horas_orientacion_*` | 5.4 (HumanMentorshipTracker) |
| AiMentorshipTracker patron | `registrarSesionIa()`, `getHoursToday()`, limites diarios | 5.4.1 (patron equivalente para mentoria humana) |
| AndaluciaEiCrossVerticalBridgeService | 4 bridges, `evaluateBridges()`, `presentBridge()` | 5.3.2, 5.9 |
| Group Module multi-tenancy | `TenantContextService`, `TenantBridgeService`, Group membership | 5.2.1, 5.3.1 |
| PremiumEntityFormBase | Patron para forms de entidad | 7 (cumplimiento) |
| Slide-panel render | `renderPlain()`, `isSlidePanelRequest()`, `PremiumFormAjaxTrait` | 5.2, 5.5 |
| Zero Region Pattern | `{{ clean_content }}`, `hook_preprocess_page()`, body classes | 5.2, 5.3, 5.5-5.7, 8.1 |
| InformeProgresoPdfService | Patron PDF existente para informes de progreso | 5.4.1 (patron de referencia) |

---

## 7. Tabla de Cumplimiento de Directrices del Proyecto

| Directriz | Prioridad | Estado | Donde se aplica |
|-----------|-----------|--------|-----------------|
| **ZERO-REGION-001** | P0 | Cumple | Todos los templates frontend (F2-F7) usan `{{ clean_content }}`, variables via `hook_preprocess_page()` |
| **CSS-VAR-ALL-COLORS-001** | P0 | Cumple | Todo SCSS nuevo usa `var(--ej-*, fallback)`. Sin hex hardcoded |
| **SCSS-COLORMIX-001** | P0 | Cumple | Shadows y transparencias via `color-mix(in srgb, token pct%, transparent)`. Sin `rgba()` |
| **SCSS-COMPILETIME-001** | P0 | Cumple | Variables que alimentan `color.scale/adjust/change` son hex estatico. Runtime alpha via `color-mix()` |
| **SCSS-001** | P0 | Cumple | Cada parcial SCSS incluye `@use '../variables' as *;` si necesita importar. Dart Sass moderno |
| **TENANT-001** | P0 | Cumple | Toda query filtra por tenant. `accessCheck(TRUE)` en entity queries |
| **TENANT-BRIDGE-001** | P0 | Cumple | Resolucion Group (edicion) via `TenantBridgeService`. NUNCA `getStorage('group')` con Tenant IDs |
| **UPDATE-HOOK-REQUIRED-001** | P0 | Cumple | `hook_update_N()` para campos nuevos en MentoringSession y MentorProfile (F1) |
| **OPTIONAL-CROSSMODULE-001** | P0 | Cumple | `@?ecosistema_jaraba_core.firma_digital`, `@?jaraba_legal_vault.document_vault`, `@?jaraba_lms.catalog` |
| **PRESAVE-RESILIENCE-001** | P0 | Cumple | hook_entity_presave con `hasService()` + try-catch(\Throwable). Sesion DEBE completarse aunque falle PDF |
| **UPDATE-HOOK-CATCH-001** | P0 | Cumple | try-catch en hooks usa `\Throwable`, NO `\Exception` |
| **PREMIUM-FORMS-PATTERN-001** | P1 | Cumple | Cualquier form nuevo para entities extiende `PremiumEntityFormBase` |
| **SLIDE-PANEL-RENDER-001** | P1 | Cumple | Formularios de subida doc y solicitud mentoria se abren en slide-panel. `renderPlain()` + `#action` |
| **ENTITY-PREPROCESS-001** | P1 | Cumple | Cada ruta frontend con entity data tiene `template_preprocess_{type}()` en `.module` |
| **ICON-CONVENTION-001** | P1 | Cumple | Iconos via `{{ jaraba_icon('category', 'name', { variant: 'duotone' }) }}` |
| **ICON-DUOTONE-001** | P1 | Cumple | Variante default `duotone`. Solo `outline` en contextos minimalistas |
| **ICON-COLOR-001** | P1 | Cumple | Solo colores de paleta: azul-corporativo, naranja-impulso, verde-innovacion, white, neutral |
| **ROUTE-LANGPREFIX-001** | P1 | Cumple | URLs en JS via `drupalSettings`. NUNCA paths hardcoded. `Url::fromRoute()` en PHP |
| **CONTROLLER-READONLY-001** | P1 | Cumple | Controllers NO redeclaran `$entityTypeManager` con `protected readonly` |
| **SECRET-MGMT-001** | P1 | Cumple | Certificados firma via `getenv()` en `settings.secrets.php`. NUNCA en config/sync/ |
| **FORM-CACHE-001** | P2 | Cumple | NUNCA `setCached(TRUE)` incondicional |
| **CSRF-API-001** | P2 | Cumple | API routes con `_csrf_request_header_token: 'TRUE'` |
| **INNERHTML-XSS-001** | P2 | Cumple | `Drupal.checkPlain()` para datos de API insertados via innerHTML en JS |
| **AUDIT-SEC-002** | P2 | Cumple | Rutas con datos tenant usan `_permission`, no solo `_user_is_logged_in` |
| **COMMIT-SCOPE-001** | P2 | Cumple | Commits de master docs separados. Prefijo `docs:` |

---

## 8. Arquitectura Frontend y Templates

### 8.1 Templates Twig nuevos

Cada ruta frontend tiene un template de pagina en el tema (`page--{ruta}.html.twig`) con layout limpio zero-region, y un template de contenido en el modulo.

| Template Pagina (tema) | Template Contenido (modulo) | Ruta |
|------------------------|-----------------------------|------|
| `page--programa--mentores.html.twig` | `programa-mentores.html.twig` | `/programa/mentores` |
| `page--programa--formacion.html.twig` | `programa-formacion.html.twig` | `/programa/formacion` |
| `page--programa--expediente.html.twig` | `expediente-hub.html.twig` | `/programa/expediente` |
| `page--programa--orientador.html.twig` | `orientador-dashboard.html.twig` | `/programa/orientador` |
| `page--programa--coordinador.html.twig` | `coordinador-dashboard.html.twig` | `/programa/coordinador` |

**Estructura de page template (zero-region):**
```twig
{# page--programa--mentores.html.twig #}
{% include '@ecosistema_jaraba_theme/partials/_header.html.twig' %}

<main class="programa-page programa-mentores">
  {{ clean_messages }}
  {{ clean_content }}
</main>

{% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' %}
{% include '@ecosistema_jaraba_theme/partials/_copilot-fab.html.twig' %}
```

**Body classes via hook_preprocess_html() (NO attributes.addClass):**
```php
$variables['attributes']['class'][] = 'page-programa';
$variables['attributes']['class'][] = 'page-programa-mentores';
```

### 8.2 Parciales reutilizables

Antes de crear parciales nuevos, verificar si ya existen:
- `_empty-state.html.twig` — Para estados vacios (ej: "No hay mentores asignados a esta edicion")
- `_skeleton.html.twig` — Para estados de carga
- `_review-card.html.twig` — Para ratings/reviews de mentores

**Parciales nuevos necesarios:**
- `_mentor-card-programa.html.twig` — Tarjeta de mentor contextualizada (con info de edicion, estado aprobacion)
- `_expediente-item.html.twig` — Item de documento en checklist (estado, acciones, firma)
- `_hoja-servicio-firma.html.twig` — Seccion de firma pendiente con CTA AutoFirma

### 8.3 SCSS y compilacion

**Modulo jaraba_mentoring:**
- `scss/main.scss` -> `css/mentoring.css`
- Compilacion: `npx sass scss/main.scss css/mentoring.css --style=compressed`
- Dart Sass moderno: `@use`, `color-mix()`, NUNCA `@import` ni `rgba()`

**Modulo jaraba_andalucia_ei:**
- Ya tiene `css/andalucia-ei.css` (no SCSS aun). Crear `scss/` directory:
- `scss/main.scss` -> `css/andalucia-ei.css`
- Parciales: `_programa-mentores.scss`, `_programa-formacion.scss`, `_expediente-hub.scss`, `_orientador-dashboard.scss`, `_coordinador-dashboard.scss`

**Regla SSOT:** Los parciales SCSS en modulos satelite NO definen variables `$ej-*`. Solo consumen CSS Custom Properties:
```scss
.programa-mentores__hero {
  background: linear-gradient(
    135deg,
    var(--ej-color-naranja-impulso, #ff8c42) 0%,
    color-mix(in srgb, var(--ej-color-naranja-impulso, #ff8c42) 85%, var(--ej-color-dark, #1a1a2e)) 100%
  );
}
```

### 8.4 Variables CSS inyectables desde Drupal UI

Las siguientes variables `--ej-*` ya estan configurables desde la UI de Drupal (Apariencia > Ecosistema Jaraba Theme) y se inyectan via `hook_preprocess_html()` → `<style>:root { ... }</style>`:

| Variable | Default | Uso en este plan |
|----------|---------|------------------|
| `--ej-color-primary` | `#FF8C42` | CTAs, botones, enlaces activos |
| `--ej-color-corporate` | `#233D63` | Titulos, encabezados |
| `--ej-color-naranja-impulso` | `#FF8C42` | Heroes, accents |
| `--ej-color-verde-innovacion` | `#00A9A5` | Estados positivos (completado, firmado) |
| `--ej-color-bg-surface` | `#FFFFFF` | Fondo tarjetas |
| `--ej-color-bg-body` | `#F8FAFC` | Fondo pagina |
| `--ej-border-color` | `#E2E8F0` | Bordes |
| `--ej-text-primary` | `#1A1A2E` | Texto principal |
| `--ej-text-secondary` | `#64748B` | Texto secundario |
| `--ej-text-muted` | `#94A3B8` | Texto terciario |
| `--ej-font-body` | `'Inter', sans-serif` | Cuerpo de texto |
| `--ej-font-headings` | `'Outfit', sans-serif` | Titulos |

**NO se definen variables nuevas.** Se reutilizan las existentes del sistema de tokens.

### 8.5 Iconografia

| Contexto | Categoria | Nombre | Variante | Color |
|----------|-----------|--------|----------|-------|
| Mentor | social | mentor | duotone | azul-corporativo |
| Formacion | general | graduation | duotone | naranja-impulso |
| Expediente | general | folder | duotone | azul-corporativo |
| Firma | general | pen | duotone | verde-innovacion |
| Orientador | social | briefcase | duotone | naranja-impulso |
| Coordinador | general | chart | duotone | azul-corporativo |
| Documento OK | status | check-circle | duotone | verde-innovacion |
| Documento pendiente | status | clock | duotone | naranja-impulso |
| Documento rechazado | status | x-circle | duotone | neutral |

Todos via `{{ jaraba_icon('category', 'name', { variant: 'duotone', color: 'color-name', size: '24px' }) }}`.

---

## 9. Verificacion y Testing

### 9.1 Tests automatizados

| Test | Tipo | Modulo | Que verifica |
|------|------|--------|-------------|
| `HojaServicioMentoriaServiceTest` | Unit | jaraba_andalucia_ei | Generacion PDF, vinculacion expediente, estados firma |
| `HumanMentorshipTrackerTest` | Unit | jaraba_andalucia_ei | Calculo duracion, actualizacion horas en participante |
| `ExpedienteCompletenessServiceTest` | Unit | jaraba_andalucia_ei | Checklist por fase, calculo porcentaje, categorias correctas |
| `ProgramaMentoresControllerTest` | Kernel | jaraba_andalucia_ei | Filtrado mentores por Group, acceso participante, render |
| `MentoringSessionFieldsTest` | Kernel | jaraba_mentoring | Campos nuevos existen, defaults correctos, update hook funciona |

### 9.2 Checklist RUNTIME-VERIFY-001

Tras completar cada fase, verificar 5 dependencias runtime:

1. **CSS compilado**: `stat -c '%Y' css/mentoring.css` > `stat -c '%Y' scss/main.scss`
2. **Tablas DB**: `lando drush entity:updates` sin pendientes
3. **Rutas accesibles**: `lando drush router:rebuild && curl -s -o /dev/null -w "%{http_code}" https://jaraba-saas.lndo.site/es/programa/mentores`
4. **data-* selectores**: Verificar que `data-dashboard-particles` en hero matchea con JS
5. **drupalSettings**: Inspeccionar `window.drupalSettings` en consola del navegador para `apiEndpoints`

### 9.3 Checklist IMPLEMENTATION-CHECKLIST-001

**Complitud:**
- [ ] Servicios registrados en `services.yml` Y consumidos
- [ ] Rutas en `routing.yml` apuntan a clases/metodos existentes
- [ ] SCSS compilado, library registrada, `hook_page_attachments_alter()` conectado
- [ ] `hook_update_N()` para campos nuevos (F1)

**Integridad:**
- [ ] Tests existen: Unit para servicios, Kernel para entities
- [ ] Config export si nuevas config entities (no aplica en este plan)

**Consistencia:**
- [ ] PREMIUM-FORMS-PATTERN-001 en forms
- [ ] CSS-VAR-ALL-COLORS-001 en SCSS
- [ ] TENANT-001 en queries

**Coherencia:**
- [ ] Documentacion actualizada (master docs si aplica)
- [ ] Memory files actualizados si patron nuevo

---

## 10. Inventario Completo de Ficheros

### Ficheros nuevos (22)

| # | Fichero | Modulo | Tipo | Fase |
|---|---------|--------|------|------|
| 1 | `src/Service/HojaServicioMentoriaService.php` | jaraba_andalucia_ei | Service | F4 |
| 2 | `src/Service/HumanMentorshipTracker.php` | jaraba_andalucia_ei | Service | F4 |
| 3 | `src/Service/ExpedienteCompletenessService.php` | jaraba_andalucia_ei | Service | F5 |
| 4 | `src/Controller/ProgramaMentoresController.php` | jaraba_andalucia_ei | Controller | F2 |
| 5 | `src/Controller/ProgramaFormacionController.php` | jaraba_andalucia_ei | Controller | F3 |
| 6 | `src/Controller/ExpedienteHubController.php` | jaraba_andalucia_ei | Controller | F5 |
| 7 | `src/Controller/OrientadorDashboardController.php` | jaraba_andalucia_ei | Controller | F6 |
| 8 | `src/Controller/CoordinadorDashboardController.php` | jaraba_andalucia_ei | Controller | F7 |
| 9 | `src/Controller/HojaServicioApiController.php` | jaraba_andalucia_ei | Controller API | F4 |
| 10 | `templates/programa-mentores.html.twig` | jaraba_andalucia_ei | Template | F2 |
| 11 | `templates/programa-formacion.html.twig` | jaraba_andalucia_ei | Template | F3 |
| 12 | `templates/expediente-hub.html.twig` | jaraba_andalucia_ei | Template | F5 |
| 13 | `templates/orientador-dashboard.html.twig` | jaraba_andalucia_ei | Template | F6 |
| 14 | `templates/coordinador-dashboard.html.twig` | jaraba_andalucia_ei | Template | F7 |
| 15 | `templates/hoja-servicio-mentoria.html.twig` | jaraba_andalucia_ei | Template PDF | F4 |
| 16 | `scss/main.scss` | jaraba_mentoring | SCSS entry | F8 |
| 17 | `scss/_mentoring.scss` | jaraba_mentoring | SCSS | F8 |
| 18 | `css/mentoring.css` | jaraba_mentoring | CSS compilado | F8 |
| 19 | `package.json` | jaraba_mentoring | Config | F8 |
| 20 | `page--programa--mentores.html.twig` | ecosistema_jaraba_theme | Page template | F2 |
| 21 | `page--programa--formacion.html.twig` | ecosistema_jaraba_theme | Page template | F3 |
| 22 | `page--programa--expediente.html.twig` | ecosistema_jaraba_theme | Page template | F5 |

### Ficheros modificados (10)

| # | Fichero | Cambio | Fase |
|---|---------|--------|------|
| 1 | `jaraba_mentoring/src/Entity/MentoringSession.php` | +9 campos (notes, ratings, firma) | F1 |
| 2 | `jaraba_mentoring/src/Entity/MentorProfile.php` | +1 campo (program_groups) | F1 |
| 3 | `jaraba_mentoring/jaraba_mentoring.install` | +2 hook_update_N() | F1 |
| 4 | `jaraba_andalucia_ei/src/Entity/ExpedienteDocumento.php` | +5 categorias CATEGORIAS const | F1 |
| 5 | `jaraba_andalucia_ei/jaraba_andalucia_ei.services.yml` | +3 servicios | F4-F5 |
| 6 | `jaraba_andalucia_ei/jaraba_andalucia_ei.routing.yml` | +7 rutas | F2-F7 |
| 7 | `jaraba_andalucia_ei/jaraba_andalucia_ei.module` | +hook_entity_presave, +preprocess hooks | F4 |
| 8 | `ecosistema_jaraba_core/src/Service/AndaluciaEiCrossVerticalBridgeService.php` | cta_url '/courses' -> '/programa/formacion' | F3 |
| 9 | `jaraba_andalucia_ei/src/Controller/ParticipantePortalController.php` | Acciones rapidas a rutas contextualizadas + separacion bridges | F3, F9 |
| 10 | `jaraba_andalucia_ei/templates/participante-portal.html.twig` | Seccion programa vs cross-selling | F9 |

---

## 11. Troubleshooting

### Problema 1: MentoringSession no tiene campos nuevos tras drush updatedb
**Sintomas:** Los campos `session_notes`, `objectives_worked`, etc. no aparecen en BD.
**Causa posible:** `hook_update_N()` no se ejecuto o fallo silenciosamente.
**Solucion:**
```bash
lando drush entity:updates
lando drush updatedb -y --no-post-updates
# Si persiste:
lando drush php-eval "\\Drupal::entityDefinitionUpdateManager()->applyUpdates();"
```

### Problema 2: PDF de hoja de servicio no se genera
**Sintomas:** MentoringSession se completa pero `service_sheet_doc` queda vacio.
**Causa posible:** DomPDF no instalado, o servicio `hoja_servicio_mentoria` no registrado.
**Solucion:**
```bash
lando ssh -c "composer show dompdf/dompdf"
lando drush php-eval "var_dump(\\Drupal::hasService('jaraba_andalucia_ei.hoja_servicio_mentoria'));"
# Revisar logs:
lando drush watchdog:show --severity=error --type=jaraba_andalucia_ei
```

### Problema 3: /programa/mentores no muestra mentores
**Sintomas:** Grid vacio aunque hay mentores activos.
**Causa posible:** Mentores no tienen `program_groups` asignado.
**Solucion:** Verificar en admin que los mentores tienen el Group (edicion) asignado en el campo `program_groups`.

### Problema 4: Firma AutoFirma no funciona
**Sintomas:** Modal de firma muestra "No se pudo conectar con AutoFirma".
**Causa posible:** AutoFirma no instalado o puertos 63117/63217/63317 bloqueados.
**Solucion:** Verificar AutoFirma instalado y corriendo. En local: `http://localhost:63117/AfirmaJSSocket` debe responder.

---

## 12. Referencias

### Documentos del Proyecto
- [Diagnostico Andalucia +ei](../analisis/2026-03-06_Diagnostico_Andalucia_Ei_Mentoring_Courses_Clase_Mundial_v1.md)
- [Directrices Proyecto v116](../00_DIRECTRICES_PROYECTO.md)
- [Arquitectura Theming SSOT](../arquitectura/2026-02-05_arquitectura_theming_saas_master.md)

### Archivos Clave del Codigo
- `web/modules/custom/jaraba_andalucia_ei/src/Entity/ProgramaParticipanteEi.php` — Participante (fases, horas, carril)
- `web/modules/custom/jaraba_andalucia_ei/src/Entity/ExpedienteDocumento.php` — 19+5 categorias
- `web/modules/custom/jaraba_andalucia_ei/src/Service/ExpedienteService.php` — CRUD + firma
- `web/modules/custom/jaraba_andalucia_ei/src/Service/AiMentorshipTracker.php` — Patron horas IA
- `web/modules/custom/jaraba_mentoring/src/Entity/MentoringSession.php` — Sesion (+9 campos)
- `web/modules/custom/jaraba_mentoring/src/Entity/MentorProfile.php` — Mentor (+program_groups)
- `web/modules/custom/ecosistema_jaraba_core/src/Service/FirmaDigitalService.php` — PKCS#12
- `web/modules/custom/ecosistema_jaraba_core/js/ecosistema-jaraba-firma.js` — AutoFirma WS
- `web/modules/custom/ecosistema_jaraba_core/src/Service/AndaluciaEiCrossVerticalBridgeService.php` — 4 bridges

### Reglas Nuevas Introducidas
- **MENTORING-DOC-001**: Toda sesion de mentoria completada en contexto Andalucia +ei DEBE generar automaticamente una hoja de servicio PDF con firma electronica dual.
- **MENTOR-EDITION-001**: Mentores asignados a ediciones de programas institucionales DEBEN estar vinculados via campo `program_groups` en `MentorProfile`. La ruta `/programa/mentores` filtra por este campo.
- **COURSE-EDITION-001**: Cursos del programa DEBEN mostrarse filtrados por edicion del participante. La ruta `/programa/formacion` sustituye a `/courses` como accion rapida del portal.

---

## 13. Registro de Cambios

| Fecha | Version | Autor | Descripcion |
|-------|---------|-------|-------------|
| 2026-03-06 | 1.0.0 | Claude Opus 4.6 | Creacion inicial del plan con 9 fases, 12 gaps, 22 ficheros nuevos, 10 modificados |

---

> **Nota**: Recuerda actualizar el indice general (`00_INDICE_GENERAL.md`) despues de implementar este plan. Commit de master docs separado con prefijo `docs:` (COMMIT-SCOPE-001).
