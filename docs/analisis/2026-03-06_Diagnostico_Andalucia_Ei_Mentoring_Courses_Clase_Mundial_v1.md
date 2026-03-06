# Diagnostico Integral: Andalucia +ei, Mentorias y Cursos
# Jaraba Impact Platform SaaS
# Fecha: 2026-03-06 | Version: 1.0.0
# Autor: Auditoria Claude Code (Senior Consultant Multi-disciplinar)
# Estado: DIAGNOSTICO — PENDIENTE IMPLEMENTACION

---

## Resumen Ejecutivo

Auditoria de 360 grados del flujo completo del participante Andalucia +ei y sus
interacciones con los modulos transversales de mentorias (`jaraba_mentoring`) y
formacion (`jaraba_lms`). Se identifican **12 gaps criticos** que rompen la
contextualizacion del usuario dentro de su programa y edicion, generan
incumplimientos documentales y degradan la experiencia.

**Severidad global**: CRITICA — El participante percibe elementos desconectados
(marketplace generico) en lugar de un programa integrado con cumplimiento documental.

---

## Metodologia

Auditadas **5 dimensiones** con investigacion de codigo fuente real:
- **Complitud**: Existen todos los componentes necesarios?
- **Integridad**: Los datos fluyen correctamente end-to-end?
- **Consistencia**: Los sistemas paralelos son coherentes entre si?
- **Coherencia**: La arquitectura refleja la logica de negocio?
- **Experiencia**: El usuario percibe un flujo contextualizado?

Principio rector: **RUNTIME-VERIFY-001** — verificar la diferencia entre
"el codigo existe" y "el usuario lo experimenta".

Roles/Avatares analizados: participante, orientador, mentor humano, mentor IA,
coordinador/admin.

---

## SEVERIDAD CRITICA — Bloquean cumplimiento y experiencia

### GAP-AEI-01: Hoja de Servicio de Mentoria Inexistente

**El codigo NO existe**: No hay ninguna entidad, servicio ni template para la
generacion automatica de la "hoja de servicio" que debe documentar cada sesion
de mentoria humana en el contexto del programa Andalucia +ei.

**Impacto normativo**: Las hojas de servicio son un requisito documental de los
programas cofinanciados FSE/PIIL. Sin ellas, las horas de mentoria NO son
justificables ante la entidad financiadora (STO).

**Arquitectura actual de la sesion** (`MentoringSession`):
- Campos: engagement_id, mentor_id, mentee_id, scheduled_start/end, actual_start/end,
  session_type, meeting_url, agenda, status, reminder flags
- **NO tiene**: campo de firma participante, firma orientador, PDF generado,
  referencia a documento de expediente, resumen de contenidos, objetivos
  trabajados, acuerdos alcanzados

**Solucion propuesta**:
1. Nuevo servicio `HojaServicioMentoriaService` en `jaraba_andalucia_ei`
2. Listener en `mentoring_session` estado `completed` que auto-genera PDF
3. Campos nuevos en MentoringSession o entidad asociada: `notes_summary`,
   `objectives_worked`, `agreements`, `next_steps`
4. PDF generado via DomPDF con datos de la sesion + participante + mentor
5. Dos firmas electronicas via `FirmaDigitalService::signPdf()`:
   - Firma 1: Participante (via AutoFirma JS WebSocket o firma simple)
   - Firma 2: Orientador/Mentor (misma mecanica)
6. Documento almacenado como `ExpedienteDocumento` categoria nueva:
   `mentoria_hoja_servicio`
7. Enlace automatico al `ProgramaParticipanteEi` del participante

**Ficheros afectados**:
- `jaraba_mentoring/src/Entity/MentoringSession.php` (campos nuevos)
- `jaraba_andalucia_ei/src/Entity/ExpedienteDocumento.php` (nueva categoria)
- `jaraba_andalucia_ei/src/Service/HojaServicioMentoriaService.php` (NUEVO)
- `ecosistema_jaraba_core/src/Service/FirmaDigitalService.php` (consumido)

---

### GAP-AEI-02: /mentors Descontextualizada — Marketplace Generico Sin Filtro de Edicion

**El codigo existe**: `MentorCatalogController` consulta mentores activos y
renderiza un grid de tarjetas.

**El usuario lo experimenta MAL**: Un participante de la edicion 3 de Andalucia +ei
ve TODOS los mentores del SaaS (emprendimiento, comercio, etc.), no solo los
asignados a su grupo/edicion del programa.

**Causa raiz arquitectural**: `MentorProfile` NO tiene campo `program_groups`
(entity_reference a `group`). No hay mecanismo para vincular un mentor a una
edicion especifica del programa.

**Campos actuales de MentorProfile**: user_id, sectors (list_string),
experience_years, bio, hourly_rate, stripe_account_id, rating, session_count,
availability_status, languages, specializations — NINGUNO referencia Group.

**Solucion propuesta**:
1. Nuevo campo `program_groups` en `MentorProfile` (entity_reference multivalue a `group`)
2. Nueva ruta `/programa/mentores` en `jaraba_andalucia_ei` con controller
   `ProgramaMentoresController`
3. Controller resuelve Group del participante via `TenantContextService` +
   Group membership, filtra mentores por `program_groups` que contenga dicho Group
4. Template contextualizado con info del programa, edicion y carril
5. Flujo de solicitud de mentoria integrado con aprobacion del orientador
6. `/mentors` sigue existiendo como marketplace generico para emprendimiento/otros

**Ficheros afectados**:
- `jaraba_mentoring/src/Entity/MentorProfile.php` (campo program_groups)
- `jaraba_andalucia_ei/src/Controller/ProgramaMentoresController.php` (NUEVO)
- `jaraba_andalucia_ei/jaraba_andalucia_ei.routing.yml` (nueva ruta)
- `jaraba_andalucia_ei/templates/programa-mentores.html.twig` (NUEVO)

---

### GAP-AEI-03: /courses Descontextualizada — Catalogo LMS Generico

**El codigo existe**: `AndaluciaEiCrossVerticalBridgeService` define el bridge
`formacion_continua` con `cta_url => '/courses'`. `CatalogController` del LMS
renderiza el catalogo completo sin filtro por grupo/edicion.

**El usuario lo experimenta MAL**: El participante hace clic en "Catalogo Formativo"
(cross-vertical bridge) y aterriza en `/courses` — el catalogo generico del LMS
con TODOS los cursos del SaaS. No ve los cursos especificos de su edicion de
Andalucia +ei, que pueden variar entre ediciones.

**Causa raiz**: No existe filtro por Group en `CatalogController`. Las acciones
rapidas del portal del participante enlazan a `/courses` hardcoded.

**Solucion propuesta**:
1. Nueva ruta `/programa/formacion` en `jaraba_andalucia_ei` con controller
   `ProgramaFormacionController`
2. Controller filtra cursos por Group membership del participante (los cursos se
   asignan al Group de la edicion como contenido del grupo)
3. Template contextualizado con progreso formativo, horas acumuladas vs objetivo,
   certificados obtenidos
4. Bridge `formacion_continua` redirige a `/programa/formacion` en vez de `/courses`
5. `/courses` permanece como catalogo general en navegacion secundaria
   (cross-selling SaaS, NO contenido del programa)
6. Accion rapida "Ver cursos" del portal redirige a ruta contextualizada

**Ficheros afectados**:
- `ecosistema_jaraba_core/src/Service/AndaluciaEiCrossVerticalBridgeService.php`
  (linea 77: cambiar `'/courses'` a `'/programa/formacion'`)
- `jaraba_andalucia_ei/src/Controller/ProgramaFormacionController.php` (NUEVO)
- `jaraba_andalucia_ei/jaraba_andalucia_ei.routing.yml` (nueva ruta)
- `jaraba_andalucia_ei/templates/programa-formacion.html.twig` (NUEVO)
- `jaraba_andalucia_ei/src/Controller/ParticipantePortalController.php`
  (accion rapida "Ver cursos" a nueva ruta)

---

### GAP-AEI-04: Hub de Documentos por Rol/Estado Inexistente

**El codigo existe**: `ExpedienteDocumento` con 19 categorias y
`ExpedienteService` con CRUD basico.

**El usuario lo experimenta PARCIALMENTE**: El portal del participante muestra
una seccion de expediente, pero:
- NO organiza documentos por estado del participante (fase atencion vs insercion)
- NO diferencia la vista por rol (participante ve sus docs, orientador ve docs
  de todos sus participantes, coordinador ve todo)
- NO indica que documentos faltan por fase ni cual es el siguiente requerido
- NO tiene vista para el orientador como "hub documental" de su cartera

**19 categorias actuales** (sin segmentacion por fase):
- STO (7): DNI/NIE, empadronamiento, vida laboral, demanda empleo, prestaciones,
  titulo academico, otros STO
- Programa (3): contrato, consentimiento RGPD, compromiso
- Tareas (6): diagnostico, plan empleo, CV, carta motivacion, proyecto, entregable
- Certificaciones (3): formacion, competencias, participacion

**Categorias FALTANTES**:
- `mentoria_hoja_servicio` — Hoja de servicio de sesion de mentoria (GAP-AEI-01)
- `orientacion_informe` — Informe de orientacion individual
- `orientacion_hoja_servicio` — Hoja de servicio de sesion de orientacion
- `insercion_contrato_laboral` — Contrato laboral (fase insercion)
- `insercion_alta_ss` — Alta Seguridad Social (fase insercion)

**Solucion propuesta**:
1. Definir mapa de documentos requeridos por fase:
   - Fase Atencion: STO completo + programa (contrato, RGPD, compromiso)
   - Fase Atencion (en curso): tareas + hojas servicio
   - Fase Insercion: contrato laboral + alta SS + certificaciones
2. Nuevo controller `ExpedienteHubController` con vista role-aware:
   - Participante: su expediente con checklist de completitud por fase
   - Orientador: hub de todos sus participantes con alertas de docs pendientes
   - Coordinador: vista consolidada con metricas de completitud por edicion
3. Widget de completitud documental en el portal del participante
4. Notificaciones automaticas cuando un documento esta proximo a caducar o falta

**Ficheros afectados**:
- `jaraba_andalucia_ei/src/Entity/ExpedienteDocumento.php` (nuevas categorias)
- `jaraba_andalucia_ei/src/Controller/ExpedienteHubController.php` (NUEVO)
- `jaraba_andalucia_ei/src/Service/ExpedienteCompletenessService.php` (NUEVO)
- `jaraba_andalucia_ei/jaraba_andalucia_ei.routing.yml` (nuevas rutas)
- `jaraba_andalucia_ei/templates/expediente-hub.html.twig` (NUEVO)

---

## SEVERIDAD ALTA — Degradan experiencia significativamente

### GAP-AEI-05: /mentors Sin CSS — Pagina Sin Estilos

**El codigo existe**: `jaraba_mentoring.libraries.yml` referencia `css/mentoring.css`.

**El usuario lo experimenta**: La pagina `/mentors` renderiza sin NINGUN estilo.
El fichero `css/mentoring.css` **no existe en disco**. La library se carga pero
el CSS 404.

**Impacto**: Percepcion de pagina rota. Cero profesionalidad.

**Solucion**:
1. Crear `jaraba_mentoring/scss/_mentoring.scss` con design system `var(--ej-*)`
2. Compilar a `jaraba_mentoring/css/mentoring.css`
3. Disenar: hero section, grid de tarjetas mentor premium (avatar, rating stars,
   especialidades badges, precio, CTA), filtros laterales, vista detalle modal

**Fichero**: `jaraba_mentoring/css/mentoring.css` (CREAR)

---

### GAP-AEI-06: MentoringSession Sin Campos de Contenido Documental

**El codigo existe**: `MentoringSession` tiene campos operativos (scheduling,
status, meeting_url, reminders).

**NO tiene**: Campos para documentar el CONTENIDO de la sesion, necesarios para
la hoja de servicio y la justificacion ante STO.

**Campos faltantes**:
- `session_notes` (text_long): Resumen de la sesion
- `objectives_worked` (text_long): Objetivos trabajados
- `agreements` (text_long): Acuerdos y compromisos
- `next_steps` (text_long): Proximos pasos
- `participant_rating` (integer): Valoracion del participante (1-5)
- `mentor_rating` (integer): Valoracion del mentor (1-5)
- `service_sheet_doc` (entity_reference a expediente_documento): Ref a hoja generada
- `firma_participante` (string): Estado firma (pending/signed/rejected)
- `firma_orientador` (string): Estado firma (pending/signed/rejected)

**Fichero**: `jaraba_mentoring/src/Entity/MentoringSession.php`

---

### GAP-AEI-07: Flujo de Firma Electronica No Integrado con Mentoria

**El codigo existe**: `FirmaDigitalService::signPdf()` con PKCS#12 + TSA.
`ecosistema-jaraba-firma.js` con AutoFirma WebSocket. `ExpedienteDocumento` con
campos `firma_digital` y `firma_fecha`.

**NO esta conectado**: Ningun flujo de mentoria invoca la firma. No hay trigger
post-sesion que genere PDF + solicite firma dual (participante + orientador).

**Solucion propuesta**: EventSubscriber en `jaraba_andalucia_ei` que escucha
`mentoring_session` presave (status → completed) y dispara:
1. Genera PDF hoja de servicio
2. Almacena como ExpedienteDocumento (categoria `mentoria_hoja_servicio`)
3. Envia notificacion a participante para firmar
4. Envia notificacion a orientador para firmar
5. Actualiza estado de firma en MentoringSession
6. Al completar ambas firmas, marca documento como `firmado_completo`

---

### GAP-AEI-08: AiMentorshipTracker Desconectado de Horas Formativas

**El codigo existe**: `AiMentorshipTracker` registra sesiones IA con limites
diarios y actualiza `horas_mentoria_ia` en `ProgramaParticipanteEi`.

**Gap**: Las sesiones de mentoria HUMANA (`MentoringSession` status=completed)
NO actualizan `horas_mentoria_humana` en `ProgramaParticipanteEi`. No existe
listener equivalente al `AiMentorshipTracker` para mentoria humana.

**Impacto**: Los dashboards de progreso del participante muestran horas de
mentoria IA pero NO las de mentoria humana. El STO requiere ambas.

**Solucion**: Listener en presave de `MentoringSession` (status → completed)
que calcule duracion (actual_end - actual_start) y actualice
`horas_mentoria_humana` del `ProgramaParticipanteEi` correspondiente.

---

## SEVERIDAD MEDIA — Afectan coherencia y cross-selling

### GAP-AEI-09: Cross-Vertical Bridges Mezclan Contenido Programa con Cross-Selling SaaS

**El codigo existe**: `AndaluciaEiCrossVerticalBridgeService` define 4 bridges.
El portal del participante muestra estos bridges como acciones sugeridas.

**Incoherencia**: Los bridges `formacion_continua` y `emprendimiento_avanzado`
se presentan al mismo nivel que el contenido del programa. El participante no
distingue entre "esto es parte de tu programa Andalucia +ei" y "esto es una
oferta comercial de otros servicios del SaaS".

**Solucion**: Separar en el portal:
- **Seccion programa**: Mentores de tu edicion, cursos de tu edicion, expediente,
  horas, timeline — todo contextualizado
- **Seccion descubrimiento** (cross-selling): "Explora mas servicios de Jaraba"
  con bridges a otros verticales, claramente diferenciado visualmente

---

### GAP-AEI-10: Orientador Sin Dashboard de Gestion de Cartera

**El codigo NO existe**: No hay controller ni template para que el orientador vea:
- Sus participantes asignados con estado de cada uno
- Documentos pendientes de firma
- Sesiones de mentoria programadas y completadas
- Progreso formativo agregado de su cartera
- Alertas (participantes inactivos, docs caducados, horas insuficientes)

**Impacto**: El orientador gestiona via admin Drupal, sin vision contextualizada.

**Solucion**: Nuevo `OrientadorDashboardController` en `jaraba_andalucia_ei`
con template premium zero-region, accesible desde `/programa/orientador`.

---

### GAP-AEI-11: Coordinador Sin Vista Consolidada por Edicion

**El codigo NO existe**: No hay dashboard para el coordinador/admin que muestre
metricas agregadas por edicion:
- Participantes por fase (atencion/insercion/baja)
- Completitud documental media
- Horas acumuladas vs objetivo por tipo
- Tasa de insercion
- Alertas STO (documentos pendientes, plazos proximos)

**Solucion**: Nuevo `CoordinadorDashboardController` con metricas por Group
(edicion). Ruta `/programa/coordinador`.

---

### GAP-AEI-12: MentorProfile Sin Approval Flow para Andalucia +ei

**El codigo existe**: `MentorProfile` con `availability_status` (available/busy/
unavailable). `MentorMatchingService` con scoring 6 criterios.

**Gap**: No hay flujo de aprobacion para que un mentor sea asignado a una edicion
de Andalucia +ei. Cualquier mentor activo apareceria. En programas institucionales,
la asignacion la hace el coordinador, no el marketplace.

**Solucion**:
1. Campo `approval_status` en la relacion MentorProfile-Group (approved/pending/rejected)
2. El coordinador aprueba mentores para su edicion desde el dashboard
3. Solo mentores aprobados aparecen en `/programa/mentores`

---

## Mapa de Dependencias de Implementacion

```
FASE 1 — Infraestructura Base (prerequisitos)
  |-- GAP-AEI-06: Campos contenido en MentoringSession
  |-- GAP-AEI-05: CSS para /mentors
  |-- ExpedienteDocumento: nuevas categorias (GAP-AEI-04 parcial)
  |-- MentorProfile: campo program_groups (GAP-AEI-02 parcial)
  '-- hook_update_N() para todos los campos nuevos

FASE 2 — Contextualizacion
  |-- GAP-AEI-02: /programa/mentores (controller + template + ruta)
  |-- GAP-AEI-03: /programa/formacion (controller + template + ruta)
  |-- GAP-AEI-12: Approval flow mentor-edicion
  '-- GAP-AEI-09: Separar bridges de contenido programa

FASE 3 — Cumplimiento Documental
  |-- GAP-AEI-01: HojaServicioMentoriaService + PDF + generacion auto
  |-- GAP-AEI-07: Firma electronica dual integrada
  |-- GAP-AEI-08: Listener horas mentoria humana
  '-- GAP-AEI-04: Hub documental role-aware completo

FASE 4 — Dashboards de Gestion
  |-- GAP-AEI-10: Dashboard orientador
  '-- GAP-AEI-11: Dashboard coordinador
```

---

## Inventario de Ficheros Nuevos Estimados

| # | Fichero | Modulo | Tipo |
|---|---------|--------|------|
| 1 | `src/Service/HojaServicioMentoriaService.php` | jaraba_andalucia_ei | Service |
| 2 | `src/Service/ExpedienteCompletenessService.php` | jaraba_andalucia_ei | Service |
| 3 | `src/Service/HumanMentorshipTracker.php` | jaraba_andalucia_ei | Service |
| 4 | `src/Controller/ProgramaMentoresController.php` | jaraba_andalucia_ei | Controller |
| 5 | `src/Controller/ProgramaFormacionController.php` | jaraba_andalucia_ei | Controller |
| 6 | `src/Controller/ExpedienteHubController.php` | jaraba_andalucia_ei | Controller |
| 7 | `src/Controller/OrientadorDashboardController.php` | jaraba_andalucia_ei | Controller |
| 8 | `src/Controller/CoordinadorDashboardController.php` | jaraba_andalucia_ei | Controller |
| 9 | `src/EventSubscriber/MentoringCompletedSubscriber.php` | jaraba_andalucia_ei | EventSubscriber |
| 10 | `templates/programa-mentores.html.twig` | jaraba_andalucia_ei | Template |
| 11 | `templates/programa-formacion.html.twig` | jaraba_andalucia_ei | Template |
| 12 | `templates/expediente-hub.html.twig` | jaraba_andalucia_ei | Template |
| 13 | `templates/orientador-dashboard.html.twig` | jaraba_andalucia_ei | Template |
| 14 | `templates/coordinador-dashboard.html.twig` | jaraba_andalucia_ei | Template |
| 15 | `templates/hoja-servicio-mentoria.html.twig` | jaraba_andalucia_ei | Template PDF |
| 16 | `scss/_mentoring.scss` | jaraba_mentoring | SCSS |
| 17 | `css/mentoring.css` | jaraba_mentoring | CSS compilado |
| 18 | `scss/_programa-mentores.scss` | jaraba_andalucia_ei | SCSS |
| 19 | `scss/_expediente-hub.scss` | jaraba_andalucia_ei | SCSS |
| 20 | `scss/_orientador-dashboard.scss` | jaraba_andalucia_ei | SCSS |

---

## Entidades Modificadas

| Entidad | Campo/Cambio | hook_update_N |
|---------|-------------|---------------|
| `MentoringSession` | +session_notes, +objectives_worked, +agreements, +next_steps, +participant_rating, +mentor_rating, +service_sheet_doc, +firma_participante, +firma_orientador | SI |
| `MentorProfile` | +program_groups (entity_reference multivalue a group) | SI |
| `ExpedienteDocumento` | +5 categorias en CATEGORIAS const | NO (const, no schema) |

---

## Reglas de Proyecto Aplicables

| Regla | Aplicacion |
|-------|-----------|
| TENANT-001 | Toda query de mentores/cursos DEBE filtrar por tenant |
| TENANT-BRIDGE-001 | Resolver Group (edicion) via TenantBridgeService |
| CSS-VAR-ALL-COLORS-001 | Todo SCSS nuevo con `var(--ej-*, fallback)` |
| SCSS-COLORMIX-001 | rgba() → color-mix() |
| PREMIUM-FORMS-PATTERN-001 | Forms nuevos extienden PremiumEntityFormBase |
| UPDATE-HOOK-REQUIRED-001 | Campos nuevos en entities requieren hook_update_N() |
| ROUTE-LANGPREFIX-001 | URLs via Url::fromRoute() |
| OPTIONAL-CROSSMODULE-001 | jaraba_andalucia_ei → jaraba_mentoring con @? |
| ZERO-REGION-001 | Controllers frontend devuelven markup, variables en preprocess |
| SLIDE-PANEL-RENDER-001 | Acciones crear/editar en slide-panel |
| ENTITY-PREPROCESS-001 | Preprocess para templates con entity data |

---

## Metricas de Exito

| KPI | Valor Actual | Objetivo |
|-----|-------------|----------|
| Hojas de servicio auto-generadas | 0 | 100% de sesiones completadas |
| Hojas firmadas electronicamente | 0 | >90% en 48h post-sesion |
| Mentores contextualizados por edicion | 0% | 100% |
| Cursos contextualizados por edicion | 0% | 100% |
| Completitud documental visible al participante | Parcial | 100% por fase |
| Orientador con dashboard propio | NO | SI |
| Coordinador con metricas por edicion | NO | SI |
| CSS en /mentors | 0 bytes | Clase mundial |

---

## Referencias

- `jaraba_mentoring/src/Entity/MentoringSession.php` — Entidad sesion
- `jaraba_mentoring/src/Entity/MentorProfile.php` — Entidad perfil mentor
- `jaraba_mentoring/src/Entity/MentoringEngagement.php` — Entidad engagement
- `jaraba_mentoring/src/Service/MentorMatchingService.php` — Matching 6 criterios
- `jaraba_mentoring/src/Controller/MentorCatalogController.php` — Catalogo generico
- `jaraba_andalucia_ei/src/Entity/ProgramaParticipanteEi.php` — Participante (fases, horas, carril)
- `jaraba_andalucia_ei/src/Entity/ExpedienteDocumento.php` — Documento expediente (19 categorias)
- `jaraba_andalucia_ei/src/Service/ExpedienteService.php` — CRUD documentos
- `jaraba_andalucia_ei/src/Service/AiMentorshipTracker.php` — Tracking horas IA
- `jaraba_andalucia_ei/src/Controller/ParticipantePortalController.php` — Portal participante
- `ecosistema_jaraba_core/src/Service/AndaluciaEiCrossVerticalBridgeService.php` — 4 bridges
- `ecosistema_jaraba_core/src/Service/FirmaDigitalService.php` — Firma PDF PKCS#12
- `ecosistema_jaraba_core/js/ecosistema-jaraba-firma.js` — AutoFirma WebSocket
- `jaraba_lms/src/Controller/CatalogController.php` — Catalogo LMS generico
- `docs/especificaciones/32_Emprendimiento_Mentoring_Sessions_v1.md` — Spec mentoring
