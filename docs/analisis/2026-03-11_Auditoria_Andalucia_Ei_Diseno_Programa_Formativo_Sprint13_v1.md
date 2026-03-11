# Auditoria: Diseno del Programa Formativo Andalucia +ei — Sprint 13

**Fecha de creacion:** 2026-03-11 22:00
**Ultima actualizacion:** 2026-03-11 22:00
**Autor:** IA Asistente (Claude Opus 4.6)
**Version:** 1.0.0
**Estado:** Completado
**Categoria:** Auditoria Estrategica / Gap Analysis / Diseno Arquitectonico
**Modulos auditados:** `jaraba_andalucia_ei` (38 servicios, 8 entidades, 24 controllers, 70+ rutas), `jaraba_lms`, `jaraba_mentoring`, `jaraba_events`, `jaraba_training`, `jaraba_paths`, `jaraba_interactive`, `jaraba_legal_calendar`
**Fuentes operativas:** Carpeta 1a edicion `F:\DATOS\PED S.L\Economico-Financiero\Subvenciones\Junta de Andalucia\2023 PIIL` (14.000+ archivos, 43GB, gestion completa de la 1a edicion)
**Fuentes normativas:** Orden 29/09/2023 BBRR PIIL, Resolucion 31/10/2023 Convocatoria, Resolucion 01/12/2023 Ampliacion Credito, Resolucion Concesion 15/12/2023, Orden 23/07/2025 Modificaciones, Resolucion 05/08/2025 CV 2025
**Subvencion 1a edicion:** 202.500 EUR | 45 participantes | Junta de Andalucia + FSE+ (85%/15%)
**Perspectivas aplicadas:** Consultoria de negocio senior, desarrollo de carreras profesionales, analisis financiero, desarrollo de productos, marketing, arquitectura SaaS, ingenieria de software, UX, Drupal, theming, GrapesJS, SEO/GEO, IA

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Radiografia de la 1a Edicion: Como se Gestiono sin SaaS](#2-radiografia-de-la-1a-edicion)
3. [Estado Actual del Modulo jaraba_andalucia_ei](#3-estado-actual-del-modulo)
4. [Naturaleza Mixta del Programa: Empleabilidad + Emprendimiento](#4-naturaleza-mixta-del-programa)
5. [Analisis del Ciclo VoBo SAE](#5-analisis-del-ciclo-vobo-sae)
6. [Infraestructura Existente Reutilizable](#6-infraestructura-existente-reutilizable)
7. [Gap Analysis: Lo que Falta para Clase Mundial](#7-gap-analysis)
8. [Propuesta Arquitectonica: 4 Entidades + 4 Servicios](#8-propuesta-arquitectonica)
9. [Integracion IA Nativa](#9-integracion-ia-nativa)
10. [Flujo Completo del Coordinador](#10-flujo-completo-del-coordinador)
11. [Flujo Completo de la Participante](#11-flujo-completo-de-la-participante)
12. [Validacion de Complitud, Integridad, Consistencia y Coherencia](#12-validacion)
13. [Plan de Implementacion Sprint 13](#13-plan-de-implementacion)
14. [Riesgos y Mitigaciones](#14-riesgos-y-mitigaciones)
15. [Conclusiones](#15-conclusiones)

---

## 1. Resumen Ejecutivo

### Contexto

El Programa Andalucia +ei es un **programa mixto de empleabilidad y emprendimiento** para colectivos vulnerables en Andalucia, financiado por FSE+ y la Junta de Andalucia. La 1a edicion (2023-2025) se gestiono enteramente con herramientas manuales: Excel (candidatos, participantes, metricas), Word/PPTX (contenidos formativos, sesiones), email (comunicacion con SAE), y carpetas de archivos (14.000+ documentos, 43GB).

El modulo `jaraba_andalucia_ei` del SaaS ya digitaliza la **gestion de participantes** (solicitudes, fases, expediente documental, firma electronica, indicadores FSE+, justificacion economica). Sin embargo, le falta la **capa de diseno del programa**: definir en que consisten las 50h de formacion, calendarizar sesiones, gestionar inscripciones, y orquestar el workflow VoBo SAE.

### Hallazgos Principales

| Dimension | Estado | Detalle |
|-----------|--------|---------|
| Gestion de participantes | **Implementado** | 8 entidades, 38 servicios, workflow completo |
| Diseno formativo del programa | **NO implementado** | No hay entidad para modulos formativos |
| Calendarizacion de sesiones | **NO implementado** | No hay forma de programar sesiones futuras |
| Inscripcion de participantes en sesiones | **NO implementado** | No hay entidad de inscripcion |
| Workflow VoBo SAE | **Parcial** | Campo `vobo_sae_status` existe pero sin workflow real |
| Contenidos didacticos reutilizables | **Disponibles** | 5 modulos formativos de la 1a edicion (PPTX/PDF) |
| Integracion con LMS existente | **NO conectado** | `jaraba_lms` existe pero no se referencia |
| Integracion con Mentoring existente | **NO conectado** | `jaraba_mentoring` existe pero no se referencia |
| IA nativa en diseno formativo | **NO implementado** | Sin asistencia IA para generar/adaptar contenidos |

### Impacto de la Brecha

Sin la capa de diseno del programa, el Coordinador **no puede responder** desde la plataforma:
- ¿En que consisten las 50h de formacion? → Respuesta: "consulta la carpeta de Word/PPTX"
- ¿Que sesiones hay programadas esta semana? → Respuesta: "consulta el Excel de planificacion"
- ¿Cuantas plazas quedan en la sesion del miercoles? → Respuesta: "no hay sistema"
- ¿El SAE ha dado el visto bueno a la accion formativa? → Respuesta: "consulta el email"
- ¿Que contenidos son para emprendimiento vs. empleabilidad? → Respuesta: "depende del carril"

---

## 2. Radiografia de la 1a Edicion: Como se Gestiono sin SaaS

### 2.1 Estructura Operativa Real (Carpeta 2023 PIIL)

La carpeta de la 1a edicion revela la operativa completa del programa:

```
2023 PIIL/ (43GB, 14.000+ archivos)
├── 0. Configuracion/           — Setup inicial y materiales de referencia
│   ├── Normativa/              — 6.8 MB, ordenes y resoluciones
│   ├── Disenos y plantillas/   — 65+ archivos (DOTX, PPTX, InDesign)
│   ├── Materiales/             — 1023 MB, 26 subcategorias de contenido formativo
│   └── Datos/                  — Estadisticas IECA, referencias CADEs
├── 1. Solicitudes/             — 4.1 MB, expediente completo de solicitud
│   ├── Resoluciones/           — Concesion, aceptacion, acceso STO
│   └── Correspondencia SAE/    — Emails (.msg) con registros de consulta
├── 2. Ejecucion/ (35GB)        — Operativa diaria del programa
│   ├── 01. Difusion/           — 67 MB, videos, InDesign, Google Ads, Facebook
│   ├── 02. Candidatos/         — Excel por provincia y fecha
│   ├── 03. Participantes/      — Excel master + carpetas por provincia
│   ├── 04. Acciones Atencion/  — SESIONES DE ORIENTACION Y FORMACION
│   │   ├── 1. Orientacion Inicial/
│   │   │   ├── Presentaciones (v1 y v2, PPTX + PDF)
│   │   │   ├── Actividades (PPTX + PDF)
│   │   │   ├── Planificacion fechas.xlsx
│   │   │   ├── Sesiones Grupales/ (8 provincias, fechas concretas)
│   │   │   └── Sesiones Individuales/ (50+ carpetas por participante)
│   │   └── 2. Formacion/
│   │       ├── Curso de Emprendimiento v2 (105 MB PPTX, 5 modulos)
│   │       ├── Fichas tematicas (Maslow, De Bono)
│   │       ├── Documentacion/Metricas.xlsx
│   │       ├── Grupos F01-F04 (asistencia, materiales)
│   │       ├── Recibos de formacion (PDFs firmados por sesion)
│   │       └── Certificados formacion/
│   ├── 05. Acciones Insercion/
│   │   ├── Autoempleo (47 MB PPTX, variantes redes sociales)
│   │   ├── Carpetas individuales (50+ participantes)
│   │   ├── Sesiones grupales (I02, I03, Malaga-Jaen)
│   │   ├── Certificados de Insercion/
│   │   └── Recibis Incentivo/ (6 pagos de €528)
│   ├── 06. Profesionales/      — Personal tecnico
│   ├── 07. Verificacion Material/ — Evidencias fotograficas
│   └── 08. Testimonios/        — Videos de participantes
├── 3. Justificacion/ (3.0 GB)  — Memoria tecnica + economica + STO
│   ├── MEM_TECNICA285.pdf      — Memoria tecnica firmada
│   ├── MEM_ECONOMICA285.pdf    — Memoria economica firmada
│   ├── DEC_JURADA_285.pdf      — Declaracion jurada
│   ├── Listados personas atendidas/insertadas (XLSX)
│   └── Correspondencia extensa SAE sobre validacion STO
└── 4. Anticipos y pagos/       — 80% anticipo recibido
```

### 2.2 Herramientas de Gestion Utilizadas (1a Edicion)

| Herramienta | Funcion | Limitacion | Equivalente SaaS |
|-------------|---------|------------|-------------------|
| **Candidatos.xlsx** | Tracking de leads por provincia y fuente | Sin deduplicacion, sin scoring | `SolicitudEi` + `SolicitudTriageService` ✓ |
| **Participantes.xlsx** | Listado maestro + snapshots historicos | Sin actualizacion en tiempo real | `ProgramaParticipanteEi` ✓ |
| **Planificacion fechas.xlsx** | Calendario de sesiones grupales | Sin inscripcion, sin plazas | **NO EXISTE EN SAAS** |
| **Metricas.xlsx** | Tracking de asistencia por grupo | Manual, propenso a errores | **PARCIAL** (horas se acumulan pero sin detalle por sesion) |
| **PPTX presentaciones** | Contenidos formativos (orientacion, formacion, insercion) | No reutilizables digitalmente | **NO INTEGRADO** (el LMS existe pero no se conecta) |
| **Carpetas individuales** | Expediente por participante | Sin busqueda, sin versionado | `ExpedienteDocumento` + vault ✓ |
| **Emails .msg** | Comunicacion SAE, VoBo, incidencias | Sin trazabilidad, sin workflow | **NO EXISTE** (campo existe sin workflow) |
| **Recibos PDF firmados** | Justificacion de asistencia | Firma manual en papel escaneado | `FirmaWorkflowService` ✓ |
| **Certificados** | Formacion y participacion | Generados uno a uno en Word | **PARCIAL** (plantillas existen pero no automatizado) |
| **PIIL-Configuracion.xlsx** | Parametros del programa | Unica fuente de verdad en Excel | `AndaluciaEiSettingsForm` ✓ (parcial) |

### 2.3 Contenidos Formativos de la 1a Edicion (Activos Reutilizables)

**Orientacion Inicial (Fase Acogida/Atencion):**
- `AndaluciaEI-Atenciones-Orientacion inicial_v2.pptx` (21 MB) — Presentacion master
- `AndaluciaEI-Atenciones-Orientacion inicial-Actividades.pptx` — Guia de actividades
- Formato: Sesiones grupales de 2 dias por provincia + sesiones individuales continuadas

**Formacion — Curso de Emprendimiento (50h):**
- `AndaluciaEI-Atenciones-Formacion-Curso de Emprendimiento_v2.pptx` (105 MB) — Curso completo
- 5 modulos desglosados en PDFs independientes:
  - Modulo 1: Fundamentos (1.6 MB)
  - Modulo 2: Producto/Mercado (579 KB) + Ficha producto (168 KB)
  - Modulo 3: Estrategia/Objetivos/Procesos (2.0 MB)
  - Modulo 4: Marketing y Ventas (1.3 MB)
  - Modulo 5: Gestion y Consolidacion (1.8 MB)
- Fichas tematicas: Maslow (necesidades-actividades), De Bono (sombreros)
- 4 grupos de formacion (F01-F04) con tracking de asistencia

**Insercion — Autoempleo:**
- `AndaluciaEI-Insercion-Autoempleo.pptx` (47 MB) — Guia completa de autoempleo
- Variantes para redes sociales (LinkedIn, Meta, Mobirise, Vidnoz)
- Sesiones grupales (I02, I03) + individuales (50+ participantes)

**Biblioteca de Materiales (26 categorias, 1023 MB):**
- Ayudas y Subvenciones (legislacion autonomos, RD, leyes)
- Andalucia Emprende (carta servicios, kit emprendimiento, manual emprender)
- Autoempleo como opcion
- Finanzas para emprender (modelos Excel)
- Gestion administrativa (formularios AEAT, calendarios fiscales)
- Gestion comercial y marketing para emprender
- Planes de negocio
- Personalidad y desarrollo profesional
- CIRCE, CEA, UPTA, FNMT (certificacion digital)

### 2.4 Proceso VoBo SAE en la 1a Edicion (Evidencia Real)

De la correspondencia SAE extraida (28+ emails .msg):
1. El VoBo para acciones formativas se tramito via email y STO
2. Los **recibos de formacion** requieren firma dual (participante + tecnico) — en la 1a edicion se firmaron en papel y se escanearon
3. El SAE corrigio recibos de formacion en junio 2025 (email `Recibos de formacion corregidos.msg`)
4. La validacion de inserciones en STO fue problematica (multiples emails agosto-septiembre 2025)
5. Se necesito una **ampliacion de plazo de justificacion** (julio 2025)
6. La firma electronica seria validada via capturas del visor de Adobe (`Capturas del visor de firmas digitales de Adobe.pdf`)

**Leccion clave:** El VoBo SAE NO es un simple checkbox. Es un proceso documental con ida y vuelta que necesita:
- Documentacion del programa formativo (contenidos, objetivos, competencias)
- Datos del formador
- Listado de participantes previstos
- Fechas y horarios
- Respuesta del SAE (aprobacion, rechazo, subsanacion)
- Trazabilidad completa para justificacion

---

## 3. Estado Actual del Modulo jaraba_andalucia_ei

### 3.1 Inventario Completo

| Componente | Cantidad | Estado |
|------------|----------|--------|
| ContentEntities | 8 | Produccion |
| Servicios | 38 | Produccion |
| Controllers | 24 | Produccion |
| Rutas | 70+ | Produccion |
| Permisos | 25 | Produccion |
| Forms | 16 | PremiumEntityFormBase |
| Access Handlers | 8 | TENANT-ISOLATION-ACCESS-001 |
| Tests | 25+ | Unit/Kernel |
| Sprints implementados | 1-12 | Completos |

### 3.2 Entidades Existentes y su Rol

| Entidad | Campos | Rol |
|---------|--------|-----|
| `solicitud_ei` | 30 | Solicitud publica de participacion |
| `programa_participante_ei` | 45 | Participante central (fases, horas, incentivos) |
| `expediente_documento` | 35 | Documentos del participante + firmas |
| `actuacion_sto` | 22 | Registro post-hoc de actuaciones realizadas |
| `insercion_laboral` | 20 | Registro de insercion laboral verificada |
| `plan_emprendimiento_ei` | 25 | Plan de emprendimiento (carril Acelera) |
| `indicador_fse_plus` | 30 | Indicadores FSE+ (entrada/salida/6m) |
| `prospeccion_empresarial` | 18 | Prospecciones de empresas para insercion |

### 3.3 Servicios Agrupados por Funcion

**Gestion del Itinerario (6):**
- `FaseTransitionManager` — Maquina de estados 6 fases
- `CalendarioProgramaService` — Timeline 52 semanas, 12 hitos
- `ActuacionStoService` — CRUD actuaciones + incremento horas
- `AdaptacionItinerarioService` — 8 barreras, adaptaciones por colectivo
- `RiesgoAbandonoService` — Early warning abandono (5 factores)
- `AccesoProgramaService` — Control de acceso al programa

**Justificacion Economica y Normativa (3):**
- `JustificacionEconomicaService` — Modulos €3.500/€2.500, presupuesto €202.500
- `AlertasNormativasService` — Alertas de cumplimiento PIIL
- `PuntosImpactoEiService` — KPIs de impacto

**Documentacion y Firma (5):**
- `ExpedienteService` — Repositorio documental con vault
- `FirmaWorkflowService` — 8 estados firma, 3 metodos (tactil, autofirma, sello)
- `DocumentoFirmaOrchestrator` — 37 categorias → 4 workflows firma
- `DocumentoRevisionIaService` — Revision IA de documentos
- `ExpedienteCompletenessService` — Validacion completitud documental

**Generadores de Documentos (5):**
- `AcuerdoParticipacionService` — Acuerdo_participacion_ICV25.odt
- `DaciService` — Anexo_DACI_ICV25.odt (8 compromisos, 7 derechos)
- `ReciboServicioService` — Recibos universales
- `IncentiveReceiptService` — Recibi_Incentivo_ICV25.odt (€528)
- `IncentiveWaiverService` — Renuncia_Incentivo_ICV25.odt

**Mentoria e IA (4):**
- `AiMentorshipTracker` — Tracking horas IA Copilot (0.25h/sesion, 4h/dia max)
- `HumanMentorshipTracker` — Tracking horas mentor humano
- `HojaServicioMentoriaService` — Hojas servicio de mentoria
- `AndaluciaEiCopilotBridgeService` — Bridge con Copilot IA

**Bridges Cross-Vertical (5):**
- `EiEmprendimientoBridgeService` → jaraba_business_tools (canvas, MVP, proyecciones)
- `EiMatchingBridgeService` → jaraba_matching + jaraba_candidate
- `EiAlumniBridgeService` → jaraba_mentoring (alumni como mentores)
- `EiBadgeBridgeService` → ecosistema_jaraba_core (badges)
- `EiPushNotificationService` → jaraba_pwa + jaraba_support

**Hub del Coordinador (3):**
- `CoordinadorHubService` — CRUD solicitudes, participantes, KPIs
- `SolicitudTriageService` — IA-assisted triage
- `StoExportService` — Exportacion SOAP a STO

**Otros (7):**
- `ProspeccionService`, `MensajeriaIntegrationService`, `AdaptiveDifficultyEngine`, `AndaluciaEiCopilotContextProvider`, `InformeProgresoPdfService`, `AndaluciaEiSettingsForm`, `SesionGroupService`

---

## 4. Naturaleza Mixta del Programa: Empleabilidad + Emprendimiento

### 4.1 Evidencia de la 1a Edicion

La carpeta de la 1a edicion confirma que Andalucia +ei **NO es un programa solo de empleabilidad**. Es un programa **mixto** con dos vias de insercion:

| Via | Contenidos 1a Edicion | Insercion Objetivo | Entidad Existente |
|-----|----------------------|--------------------|--------------------|
| **Empleabilidad** | Orientacion individual, busqueda empleo, CV, entrevistas | Cuenta ajena (contrato laboral) | `InsercionLaboral` (tipo: cuenta_ajena) |
| **Emprendimiento** | Curso 5 modulos, plan negocio, finanzas, marketing, gestion | Cuenta propia (autonomo) o cooperativa | `InsercionLaboral` (tipo: cuenta_propia) + `PlanEmprendimientoEi` |

### 4.2 Los 3 Carriles y su Contenido Formativo

| Carril | Perfil | Formacion (50h) | Orientacion (10h) | Insercion |
|--------|--------|-----------------|--------------------|-----------|
| **Impulso Digital** | Persona con brecha digital, desempleada | Alfabetizacion digital, herramientas cloud, CV digital, busqueda empleo online | Individual: perfil profesional, objetivos. Grupal: dinamicas grupales, networking | Cuenta ajena: intermediacion laboral |
| **Acelera Pro** | Persona con idea de negocio | Curso Emprendimiento 5 modulos (plan negocio, marketing, finanzas, gestion) | Individual: coaching emprendedor, modelo canvas. Grupal: networking emprendedores | Cuenta propia: alta RETA, modelo fiscal |
| **Hibrido** | Perfil mixto | 25h digital + 25h profesional adaptadas al perfil | Mix individual + grupal | Cualquier via |

### 4.3 Implicaciones para el Diseno

1. **Las acciones formativas deben asociarse a carriles** — No todo participante recibe la misma formacion
2. **Los planes formativos son diferentes** por carril — Un plan "Impulso Digital" y otro "Acelera Pro"
3. **El VoBo SAE aplica a TODAS las acciones formativas** — independiente del carril
4. **La orientacion tiene componentes comunes** (grupales) y especificos (individuales por carril)
5. **Los modulos del Curso de Emprendimiento (1a ed.)** son contenido reutilizable que puede cargarse en el LMS
6. **Los materiales de Autoempleo** son contenido de insercion especifico del carril Acelera

---

## 5. Analisis del Ciclo VoBo SAE

### 5.1 Workflow Completo (Derivado de la 1a Edicion)

```
COORDINADOR: Disena accion formativa en la plataforma
    │
    ├── Datos requeridos por SAE:
    │   - Titulo y descripcion de la accion
    │   - Objetivos competenciales
    │   - Contenido programatico (modulos/temas)
    │   - Formador/a (nombre, titulacion, experiencia)
    │   - Calendario previsto (fechas, horarios, lugar)
    │   - Modalidad (presencial/online/mixta)
    │   - Participantes previstos (DNI/NIE, colectivo)
    │   - Numero de horas
    │
    ▼
ESTADO: borrador → El coordinador prepara la documentacion
    │
    ▼
ESTADO: pendiente_documentacion → Se genera documento PDF para SAE
    │
    ▼  (Coordinador envia via STO o presencialmente)
ESTADO: enviado_sae → Registrado en plataforma con fecha envio
    │
    ▼  (Plazo SAE: 15-30 dias)
ESTADO: pendiente_vobo → Esperando respuesta
    │
    ├──► aprobado → Se puede ejecutar. Codigo VoBo registrado
    │      │
    │      ▼
    │    ESTADO: en_ejecucion → Sesiones calendarizadas, asistencia
    │      │
    │      ▼
    │    ESTADO: completada → Hojas servicio firmadas, certificados
    │
    ├──► rechazado → Motivo registrado
    │      │
    │      ▼
    │    ESTADO: en_subsanacion → Coordinador corrige y reenvia
    │
    └──► caducado → Sin respuesta SAE (alerta escalamiento)
```

### 5.2 Estado Actual vs. Necesario

| Aspecto | Estado Actual | Estado Necesario |
|---------|---------------|------------------|
| Campo VoBo en ActuacionSto | `vobo_sae_status` (4 valores: no_requerido, pendiente, aprobado, rechazado) | Insuficiente — el VoBo se solicita ANTES de la actuacion, no despues |
| Documentacion para SAE | No existe | Generacion automatica de PDF con datos de la accion formativa |
| Tracking envio/respuesta | No existe | Fechas, codigos, observaciones, documentos adjuntos |
| Alertas por timeout | Mencion en AlertasNormativasService | Necesita workflow real con fechas limite |
| Subsanacion | No existe | Ciclo de correccion y reenvio |
| Vinculacion con sesiones | No existe | Las sesiones se calendarizan DESPUES del VoBo aprobado |

---

## 6. Infraestructura Existente Reutilizable (NO Duplicar)

### 6.1 Modulos del Ecosistema con Funcionalidad Relevante

| Modulo | Entidad/Servicio | Reuso para Andalucia +ei | Patron de Integracion |
|--------|-----------------|--------------------------|----------------------|
| `jaraba_lms` | `lms_course`, `lms_lesson`, `lms_enrollment` | Contenidos formativos online (50h). Modulos del curso de emprendimiento → lecciones LMS | `course_id` (integer FK, ENTITY-FK-001) |
| `jaraba_lms` | `EnrollmentService`, `ProgressTrackingService` | Matriculacion y progreso en cursos | Reusar servicio existente |
| `jaraba_lms` | `LearningTutorAgent`, `AdaptiveLearningService` | Tutoria IA sobre contenidos formativos | Reusar agente IA existente |
| `jaraba_mentoring` | `mentoring_session`, `availability_slot` | Patron de calendarizacion y disponibilidad | Patron de referencia (no FK directa) |
| `jaraba_events` | `marketing_event`, `event_registration` | Sesiones grupales como "eventos" internos | Patron de referencia |
| `jaraba_interactive` | `interactive_content` | Evaluaciones DIME, cuestionarios, ejercicios | `interactive_content_id` (integer FK) |
| `jaraba_training` | `TrainingProduct` (Value Ladder) | Patron de composicion curso → producto | Solo referencia arquitectonica |
| `jaraba_paths` | `path_phase`, `path_module`, `path_step` | Patron de itinerario jerarquico | Solo referencia arquitectonica |
| `jaraba_legal_calendar` | `CalendarConnection` | Sincronizacion Google Calendar / Outlook | Servicio optional `@?` |
| `jaraba_sepe_teleformacion` | `SepeAccionFormativa` | **Patron exacto** para VoBo SAE (estado, codigo, horas, modalidad) | Referencia de diseno |
| `jaraba_business_tools` | Canvas, MVP, Proyecciones | Ya integrado via `EiEmprendimientoBridgeService` | Bridge existente |

### 6.2 Reutilizacion Concreta (No Duplicar)

**SI reutilizar:**
- `lms_course` + `lms_lesson` para los contenidos formativos online
- `lms_enrollment` para la matriculacion en cursos LMS
- `ProgressTrackingService` para tracking de progreso en formacion online
- Patron de `availability_slot` (recurrencia) para calendarizacion
- Patron de `SepeAccionFormativa` (workflow estados con SAE) para VoBo
- `LearningTutorAgent` para tutoria IA sobre contenidos
- `CalendarConnection` para sincronizacion con calendarios externos

**NO duplicar:**
- NO crear nueva entidad de "enrollment" — usar `InscripcionSesionEi` solo para sesiones presenciales/sincronas
- NO crear nuevo sistema de firma — reusar `FirmaWorkflowService` existente
- NO crear nuevo sistema de documentos — reusar `ExpedienteService` + vault
- NO crear nuevo tracking de horas — reusar `ActuacionStoService::incrementarHorasParticipante()`
- NO crear nuevo sistema de alertas — extender `AlertasNormativasService`

---

## 7. Gap Analysis: Lo que Falta para Clase Mundial

### 7.1 Brechas P0 — Criticas (Impiden la operativa del Coordinador)

| # | Brecha | Impacto | Entidad/Servicio Propuesto |
|---|--------|---------|----------------------------|
| G1 | **No existe entidad para acciones formativas** | El Coordinador no puede definir los modulos formativos del programa, ni solicitar VoBo | `AccionFormativaEi` |
| G2 | **No existe workflow VoBo SAE real** | Sin VoBo no hay formacion legal. Campo existe sin maquina de estados | `VoboSaeWorkflowService` |
| G3 | **No existe calendarizacion de sesiones** | Las participantes no saben cuando hay sesiones, no pueden inscribirse | `SesionProgramadaEi` |
| G4 | **No existe inscripcion en sesiones** | No hay control de plazas ni asistencia automatizada | `InscripcionSesionEi` |

### 7.2 Brechas P1 — Importantes (Funcionalidad operativa avanzada)

| # | Brecha | Impacto | Solucion |
|---|--------|---------|----------|
| G5 | **No existe plan formativo por carril** | Cada carril deberia tener su composicion de acciones formativas predefinida | `PlanFormativoEi` |
| G6 | **No hay vinculacion con cursos LMS** | Los 5 modulos del curso de emprendimiento no estan en el LMS | Cargar contenidos 1a ed. en `lms_course` + FK |
| G7 | **Las actuaciones se registran post-hoc** | El flujo es: calendarizar → inscribir → asistir → registrar automaticamente | `InscripcionSesionService` → genera `ActuacionSto` |
| G8 | **No hay recurrencia en sesiones** | "Orientacion individual cada lunes" no se puede configurar | Campo `recurrencia_patron` (JSON) en `SesionProgramadaEi` |
| G9 | **El portal de la participante no muestra sesiones futuras** | La participante no ve su calendario ni puede inscribirse | Extension de `ParticipantePortalController` |
| G10 | **No hay recordatorios automaticos** | Ni email ni push notification antes de sesiones | Extension de `EiPushNotificationService` |

### 7.3 Brechas P2 — Elevacion a Clase Mundial con IA Nativa

| # | Brecha | Impacto | Solucion |
|---|--------|---------|----------|
| G11 | **Sin IA para generar contenidos formativos** | El coordinador crea contenidos manualmente en Word/PPTX | Agente IA `FormacionDesignAgent` que genera esquemas de modulos |
| G12 | **Sin IA para adaptar contenidos por perfil** | Un participante con brecha digital severa recibe el mismo contenido | `AdaptiveLearningService` + contexto PIIL |
| G13 | **Sin IA para predecir necesidad de VoBo** | El coordinador no sabe cuando solicitar VoBo anticipadamente | Alerta predictiva basada en calendario |
| G14 | **Sin IA para optimizar la calendarizacion** | Conflictos de horarios, baja asistencia no se detectan proactivamente | `SchedulingOptimizationService` con IA |
| G15 | **Sin evaluacion IA de competencias adquiridas** | La formacion no evalua competencias automaticamente | `InteractiveContent` + evaluacion IA |

---

## 8. Propuesta Arquitectonica: 4 Entidades + 4 Servicios

### 8.1 Entidades Nuevas

#### 8.1.1 AccionFormativaEi (ContentEntity)

**Proposito:** Representa un modulo o accion formativa del programa que el Coordinador disena antes de ejecutar. Es la unidad que requiere VoBo SAE.

**Campos:**

| Campo | Tipo | Req | Descripcion |
|-------|------|-----|-------------|
| `id` | serial | auto | Primary key |
| `uuid` | uuid | auto | Identificador unico |
| `uid` | entity_reference (user) | si | Creador |
| `tenant_id` | entity_reference (group) | si | Tenant (TENANT-001) |
| `titulo` | string(255) | si | "Alfabetizacion Digital Basica" |
| `descripcion` | text_long | si | Contenido programatico detallado |
| `objetivos_competenciales` | text_long | si | Objetivos y competencias a adquirir |
| `horas_previstas` | decimal(8,2) | si | Horas totales de la accion |
| `modalidad` | list_string | si | presencial_sede, presencial_empresa, online_videoconf, online_plataforma, mixta |
| `carril` | list_string | si | impulso_digital, acelera_pro, hibrido, todos |
| `tipo_formacion` | list_string | si | orientacion_individual, orientacion_grupal, formacion, tutoria, taller, evaluacion |
| `fase_programa` | list_string | si | acogida, diagnostico, atencion, insercion (en que fase se imparte) |
| `formador_nombre` | string(255) | no | Nombre del formador/a |
| `formador_titulacion` | text_long | no | Titulacion y experiencia |
| `formador_id` | entity_reference (user) | no | Usuario Drupal del formador (si existe) |
| `max_participantes` | integer | no | Plazas maximas |
| `course_id` | integer | no | FK a lms_course (ENTITY-FK-001, cross-module) |
| `interactive_content_id` | integer | no | FK a interactive_content (evaluacion) |
| `vobo_sae_status` | list_string | si | borrador, pendiente_documentacion, enviado_sae, pendiente_vobo, aprobado, rechazado, en_subsanacion, caducado |
| `vobo_sae_codigo` | string(50) | no | Codigo asignado por SAE al aprobar |
| `vobo_sae_fecha_solicitud` | datetime (date) | no | Fecha de envio al SAE |
| `vobo_sae_fecha_respuesta` | datetime (date) | no | Fecha de respuesta SAE |
| `vobo_sae_observaciones` | text_long | no | Observaciones del SAE (motivo rechazo, etc.) |
| `vobo_sae_expediente_id` | entity_reference (expediente_documento) | no | Documento generado para SAE |
| `vobo_sae_respuesta_id` | entity_reference (expediente_documento) | no | Documento de respuesta SAE |
| `activa` | boolean | si | Default TRUE |
| `orden` | integer | no | Peso para ordenacion dentro del plan |
| `created` | created | auto | Timestamp creacion |
| `changed` | changed | auto | Timestamp modificacion |

**Metodos:**
- `requiereVoboSae(): bool` — TRUE si tipo_formacion == 'formacion' (las orientaciones no requieren VoBo)
- `isVoboAprobado(): bool` — vobo_sae_status === 'aprobado'
- `canExecute(): bool` — !requiereVoboSae() || isVoboAprobado()
- `getHorasPrevistas(): float`
- `getCarril(): string`

**Patron de referencia:** `SepeAccionFormativa` (jaraba_sepe_teleformacion)

---

#### 8.1.2 SesionProgramadaEi (ContentEntity)

**Proposito:** Instancia calendarizada de una sesion (individual o grupal, formacion u orientacion). Puede pertenecer a una AccionFormativaEi o ser independiente.

**Campos:**

| Campo | Tipo | Req | Descripcion |
|-------|------|-----|-------------|
| `id` | serial | auto | Primary key |
| `uuid` | uuid | auto | Identificador unico |
| `uid` | entity_reference (user) | si | Creador |
| `tenant_id` | entity_reference (group) | si | Tenant (TENANT-001) |
| `accion_formativa_id` | entity_reference (accion_formativa_ei) | no | Accion formativa padre (opcional, puede ser sesion suelta) |
| `titulo` | string(255) | si | "Orient. Individual — Ana Mairena" o "Taller Emprendimiento Grupal" |
| `descripcion` | text_long | no | Descripcion detallada de la sesion |
| `tipo_sesion` | list_string | si | orientacion_individual, orientacion_grupal, formacion, tutoria, taller, evaluacion |
| `fecha` | datetime (date) | si | Fecha de la sesion |
| `hora_inicio` | string(5) | si | HH:MM |
| `hora_fin` | string(5) | si | HH:MM |
| `duracion_minutos` | integer | no | Computed (hora_fin - hora_inicio) |
| `modalidad` | list_string | si | presencial_sede, presencial_empresa, online_videoconf, online_plataforma, telefonico |
| `lugar_descripcion` | string(255) | no | "Aula 3, Sede Malaga" o "Regus Malaga Centro" |
| `enlace_videoconferencia` | uri | no | URL de Zoom/Meet/Teams |
| `facilitador_id` | entity_reference (user) | si | Orientador/formador que dirige la sesion |
| `fase_programa` | list_string | no | acogida, diagnostico, atencion, insercion, seguimiento |
| `carril` | list_string | no | impulso_digital, acelera_pro, hibrido, todos (filtro para participantes) |
| `max_plazas` | integer | no | Plazas disponibles (NULL = sin limite para individuales) |
| `plazas_ocupadas` | integer | no | Computed desde InscripcionSesionEi |
| `estado` | list_string | si | programada, confirmada, en_curso, completada, cancelada, aplazada |
| `es_recurrente` | boolean | no | Default FALSE |
| `recurrencia_patron` | text_long | no | JSON: {"freq":"weekly","interval":1,"days":[1,3],"until":"2026-06-30"} |
| `sesion_padre_id` | entity_reference (sesion_programada_ei) | no | Si fue generada por recurrencia, referencia a la sesion template |
| `notas_facilitador` | text_long | no | Notas post-sesion del facilitador |
| `created` | created | auto | Timestamp creacion |
| `changed` | changed | auto | Timestamp modificacion |

**Metodos:**
- `isGrupal(): bool` — tipo_sesion contiene 'grupal' o 'taller' o 'formacion'
- `hayPlazasDisponibles(): bool` — max_plazas === NULL || plazas_ocupadas < max_plazas
- `getDuracionHoras(): float` — duracion_minutos / 60
- `isPast(): bool` — fecha < hoy
- `canReceiveInscripciones(): bool` — estado in [programada, confirmada] && hayPlazas && !isPast

**Patron de referencia:** `mentoring_session` (jaraba_mentoring) + `availability_slot` (recurrencia)

---

#### 8.1.3 InscripcionSesionEi (ContentEntity)

**Proposito:** Registra que una participante se ha inscrito en una sesion programada. Al marcar asistencia, genera automaticamente una ActuacionSto y actualiza las horas del participante.

**Campos:**

| Campo | Tipo | Req | Descripcion |
|-------|------|-----|-------------|
| `id` | serial | auto | Primary key |
| `uuid` | uuid | auto | Identificador unico |
| `uid` | entity_reference (user) | si | Usuario que creo la inscripcion |
| `tenant_id` | entity_reference (group) | si | Tenant (TENANT-001) |
| `sesion_id` | entity_reference (sesion_programada_ei) | si | Sesion a la que se inscribe |
| `participante_id` | entity_reference (programa_participante_ei) | si | Participante inscrito |
| `estado` | list_string | si | inscrito, confirmado, asistio, no_asistio, cancelado, justificado |
| `fecha_inscripcion` | datetime | si | Timestamp de inscripcion |
| `asistencia_verificada` | boolean | no | Default FALSE. El facilitador confirma |
| `horas_computadas` | decimal(8,2) | no | Horas que se imputan (puede diferir de duracion sesion) |
| `actuacion_sto_id` | entity_reference (actuacion_sto) | no | ActuacionSto generada al confirmar asistencia |
| `notas` | text_long | no | Observaciones sobre la participacion |
| `cancelacion_motivo` | string(255) | no | Motivo si estado = cancelado |
| `created` | created | auto | Timestamp creacion |
| `changed` | changed | auto | Timestamp modificacion |

**Metodos:**
- `confirmarAsistencia(): void` — cambia estado a 'asistio', genera ActuacionSto
- `marcarInasistencia(bool $justificada): void` — 'no_asistio' o 'justificado'
- `cancelar(string $motivo): void` — 'cancelado' + libera plaza
- `getHorasComputadas(): float`

**Logica critica:** Al llamar a `confirmarAsistencia()`:
1. Crea `ActuacionSto` con datos de la sesion (tipo, fecha, horas, facilitador)
2. Llama a `ActuacionStoService::incrementarHorasParticipante()` para actualizar horas del `ProgramaParticipanteEi`
3. Si aplica, solicita generacion de hoja de servicio para firma dual

---

#### 8.1.4 PlanFormativoEi (ContentEntity)

**Proposito:** Plantilla de itinerario formativo por carril. Compone acciones formativas en un plan coherente que suma las horas requeridas (>=50h formacion + >=10h orientacion).

**Campos:**

| Campo | Tipo | Req | Descripcion |
|-------|------|-----|-------------|
| `id` | serial | auto | Primary key |
| `uuid` | uuid | auto | Identificador unico |
| `uid` | entity_reference (user) | si | Creador |
| `tenant_id` | entity_reference (group) | si | Tenant (TENANT-001) |
| `titulo` | string(255) | si | "Plan Formativo Impulso Digital 2025" |
| `descripcion` | text_long | no | Descripcion del plan |
| `carril` | list_string | si | impulso_digital, acelera_pro, hibrido |
| `acciones_formativas` | entity_reference (accion_formativa_ei) | si | Multi-value, cardinality -1 |
| `horas_formacion_previstas` | decimal(8,2) | no | Computed: suma horas de acciones tipo 'formacion' |
| `horas_orientacion_previstas` | decimal(8,2) | no | Computed: suma horas de acciones tipo 'orientacion_*' |
| `cumple_minimo_formacion` | boolean | no | Computed: horas_formacion >= 50 |
| `cumple_minimo_orientacion` | boolean | no | Computed: horas_orientacion >= 10 |
| `fase_programa` | list_string | si | atencion, insercion, ambas |
| `requisitos_previos` | text_long | no | JSON con prerequisitos |
| `activo` | boolean | si | Default TRUE |
| `orden` | integer | no | Peso para ordenacion |
| `created` | created | auto | Timestamp creacion |
| `changed` | changed | auto | Timestamp modificacion |

**Metodos:**
- `getHorasFormacionPrevistas(): float` — suma computed
- `getHorasOrientacionPrevistas(): float` — suma computed
- `cumpleMinimosPIIL(): bool` — formacion >= 50 AND orientacion >= 10
- `getAccionesFormativas(): array` — lista ordenada de acciones

---

### 8.2 Servicios Nuevos

#### 8.2.1 AccionFormativaService

**Responsabilidad:** CRUD de acciones formativas + calculo horas por carril + validacion coherencia con plan.

**Metodos principales:**
- `create(array $data): AccionFormativaEi`
- `getByCarril(string $carril, ?int $tenantId): array`
- `getByFase(string $fase, ?int $tenantId): array`
- `calcularHorasTotalesPorCarril(string $carril, ?int $tenantId): array`
- `vincularCursoLms(int $accionId, int $courseId): void`
- `getAccionesPendientesVobo(?int $tenantId): array`
- `getAccionesAprobadas(?int $tenantId): array`

**Dependencias:** EntityTypeManager, LoggerInterface, @?jaraba_andalucia_ei.vobo_sae_workflow

#### 8.2.2 VoboSaeWorkflowService

**Responsabilidad:** Maquina de estados para el ciclo completo VoBo SAE. Generacion de documentacion, tracking de fechas, alertas por timeout.

**Metodos principales:**
- `prepararSolicitud(int $accionId): array` — Genera datos para documentacion SAE
- `generarDocumentoSolicitud(int $accionId): int` — Crea PDF con datos accion → ExpedienteDocumento
- `enviarASae(int $accionId, ?string $codigo_envio): void` — Marca como enviado + fecha
- `registrarRespuesta(int $accionId, string $resultado, ?string $codigo, ?string $observaciones): void`
- `solicitarSubsanacion(int $accionId, string $motivo): void`
- `getAccionesSinVobo(?int $tenantId, int $diasSinRespuesta): array` — Para alertas
- `getEstadisticasVobo(?int $tenantId): array` — KPIs de VoBo para dashboard

**Transiciones validas:**
```
borrador → pendiente_documentacion
pendiente_documentacion → enviado_sae
enviado_sae → pendiente_vobo
pendiente_vobo → aprobado | rechazado | caducado
rechazado → en_subsanacion
en_subsanacion → enviado_sae (ciclo)
caducado → enviado_sae (reenvio)
```

**Dependencias:** EntityTypeManager, LoggerInterface, @?jaraba_andalucia_ei.expediente, @?jaraba_andalucia_ei.alertas_normativas

#### 8.2.3 SesionProgramadaService

**Responsabilidad:** Calendarizacion de sesiones (incluye expansion de recurrencia), control de plazas, publicacion para participantes, recordatorios.

**Metodos principales:**
- `create(array $data): SesionProgramadaEi`
- `expandirRecurrencia(int $sesionId): array` — Genera sesiones hijas desde patron recurrencia
- `getSesionesDisponibles(int $participanteId, ?string $carril, ?string $fase): array`
- `getSesionesPorFecha(string $desde, string $hasta, ?int $tenantId): array`
- `getSesionesPorFacilitador(int $facilitadorId, string $desde, string $hasta): array`
- `cancelarSesion(int $sesionId, string $motivo): void` — Notifica inscritos
- `aplazarSesion(int $sesionId, string $nuevaFecha, string $nuevaHoraInicio, string $nuevaHoraFin): void`
- `completarSesion(int $sesionId): void` — Marca completada, trigger asistencia
- `getCalendarioSemanal(?int $tenantId, string $semana): array` — Vista semanal
- `actualizarPlazasOcupadas(int $sesionId): void` — Recalcula desde inscripciones

**Dependencias:** EntityTypeManager, LoggerInterface, @?jaraba_andalucia_ei.inscripcion_sesion, @?jaraba_legal_calendar.calendar_sync, @?jaraba_andalucia_ei.ei_push_notification

#### 8.2.4 InscripcionSesionService

**Responsabilidad:** Inscripcion/cancelacion de participantes, control de asistencia, generacion automatica de ActuacionSto al confirmar asistencia, computo de horas.

**Metodos principales:**
- `inscribir(int $sesionId, int $participanteId): InscripcionSesionEi`
- `cancelar(int $inscripcionId, string $motivo): void` — Libera plaza
- `confirmarAsistencia(int $inscripcionId, ?float $horasComputadas): void` — Genera ActuacionSto
- `confirmarAsistenciaGrupal(int $sesionId, array $participanteIds): void` — Bulk para grupos
- `marcarInasistencia(int $inscripcionId, bool $justificada): void`
- `getInscripcionesPorSesion(int $sesionId): array`
- `getInscripcionesPorParticipante(int $participanteId, ?string $estado): array`
- `getMisSesionesProximas(int $participanteId, int $dias): array`
- `getResumenAsistencia(int $participanteId): array` — % asistencia global
- `generarActuacionSto(InscripcionSesionEi $inscripcion): ActuacionSto` — Puente automatico

**Logica de confirmacion de asistencia:**
```
confirmarAsistencia(inscripcionId):
  1. Cargar inscripcion + sesion + participante
  2. Calcular horas (sesion.duracion o override)
  3. Crear ActuacionSto:
     - tipo_actuacion = sesion.tipo_sesion (mapeado a tipos ActuacionSto)
     - fecha = sesion.fecha
     - hora_inicio/fin = sesion.hora_inicio/fin
     - participante_id = inscripcion.participante_id
     - orientador_id = sesion.facilitador_id
     - fase_participante = participante.fase_actual
  4. ActuacionStoService::incrementarHorasParticipante()
  5. Actualizar inscripcion.estado = 'asistio'
  6. Actualizar inscripcion.actuacion_sto_id
  7. Actualizar inscripcion.horas_computadas
  8. Si sesion grupal: generar hoja servicio para firma dual
```

**Dependencias:** EntityTypeManager, LoggerInterface, @jaraba_andalucia_ei.actuacion_sto, @?jaraba_andalucia_ei.firma_workflow, @?jaraba_andalucia_ei.ei_push_notification

---

### 8.3 Extensiones a Servicios Existentes

| Servicio Existente | Extension |
|--------------------|-----------|
| `CoordinadorHubService` | Nuevos metodos: `getAccionesFormativas()`, `getSesionesCalendario()`, `getInscripcionesPorSesion()` |
| `CoordinadorDashboardController` | Nuevas pestanas: "Plan Formativo" (acciones + VoBo) + "Calendario" (sesiones semanales) |
| `AlertasNormativasService` | Nuevas alertas: VoBo pendiente >15 dias, acciones sin VoBo, plan <50h, sesiones sin asistencia |
| `ParticipantePortalController` | Nueva seccion: "Mis Sesiones" (proximas + inscripcion + historial) |
| `ActuacionStoService` | Nuevo metodo: `crearDesdeInscripcion(InscripcionSesionEi $inscripcion)` |
| `CalendarioProgramaService` | Integrar sesiones programadas en los hitos del timeline |

---

## 9. Integracion IA Nativa

### 9.1 IA en el Diseno Formativo (Coordinador)

| Funcionalidad IA | Agente/Servicio | Descripcion |
|-------------------|-----------------|-------------|
| **Generacion de esquemas formativos** | Copilot (balanced tier) | El coordinador describe un modulo y la IA genera: objetivos, competencias, temario, duracion recomendada |
| **Adaptacion de contenidos por colectivo** | `AdaptacionItinerarioService` + IA | Dado un modulo base + barreras del participante, la IA sugiere adaptaciones especificas |
| **Optimizacion de calendario** | Copilot (fast tier) | Detecta conflictos de horarios, baja ocupacion de sesiones, sugiere reagrupaciones |
| **Prediccion de necesidad VoBo** | `AlertasNormativasService` | Calcula automaticamente cuando se necesita enviar solicitud VoBo basado en fecha inicio prevista |

### 9.2 IA en la Experiencia de la Participante

| Funcionalidad IA | Agente/Servicio | Descripcion |
|-------------------|-----------------|-------------|
| **Tutor IA formativo** | `LearningTutorAgent` (existente) | Tutoria sobre contenidos del curso LMS vinculado a la accion formativa |
| **Recomendacion de sesiones** | Copilot (fast tier) | Recomienda sesiones disponibles segun carril, fase, horas faltantes |
| **Evaluacion de competencias** | `InteractiveContent` + IA | Evaluaciones adaptativas generadas por IA segun modulo completado |
| **Copilot contextual** | `AndaluciaEiCopilotContextProvider` (existente) | El Copilot ya conoce el contexto del participante. Se enriquece con sesiones y progreso formativo |

### 9.3 IA en la Justificacion (SAE/FSE+)

| Funcionalidad IA | Agente/Servicio | Descripcion |
|-------------------|-----------------|-------------|
| **Generacion de documentacion VoBo** | Copilot (balanced tier) | Genera borrador de documentacion para SAE a partir de datos de la accion formativa |
| **Revision IA de documentos** | `DocumentoRevisionIaService` (existente) | Revisa documentos subidos para completitud y coherencia normativa |
| **Prediccion de justificacion** | `JustificacionEconomicaService` + IA | Proyecta modulos economicos basados en progreso actual de participantes |

---

## 10. Flujo Completo del Coordinador (Propuesto)

```
FASE 0: DISENO DEL PROGRAMA (SPRINT 13 — LO NUEVO)
══════════════════════════════════════════════════════

  ┌── El Coordinador accede a "Planificacion del Programa" ──┐
  │                                                           │
  │  PASO 1: DEFINIR ACCIONES FORMATIVAS                     │
  │  ────────────────────────────────────                     │
  │  a) "Alfabetizacion Digital" — 20h, online, Impulso Dig. │
  │  b) "Curso Emprendimiento M1-M5" — 50h, mixta, Acelera  │
  │  c) "Tecnicas Busqueda Empleo" — 15h, presencial, todos  │
  │  d) "Orientacion Inicial Grupal" — 4h, presencial, todos │
  │  → Opcionalmente vincula curso LMS existente              │
  │  → IA sugiere: objetivos, competencias, temario           │
  │                                                           │
  │  PASO 2: COMPONER PLANES FORMATIVOS POR CARRIL           │
  │  ──────────────────────────────────────────────           │
  │  Plan "Impulso Digital": a + c + d = 39h form + 4h orient│
  │  Plan "Acelera Pro": b + d = 50h form + 4h orient        │
  │  → La plataforma valida: ¿cumple minimos PIIL?           │
  │  → Alerta si plan < 50h formacion o < 10h orientacion    │
  │                                                           │
  │  PASO 3: SOLICITAR VoBo SAE (solo acciones 'formacion')  │
  │  ─────────────────────────────────────────────────────    │
  │  Accion "Curso Emprendimiento" → Generar PDF para SAE    │
  │  → Estado: borrador → enviado_sae → pendiente_vobo       │
  │  → Alerta automatica si sin respuesta > 15 dias          │
  │  → Respuesta SAE: aprobado (codigo) / rechazado (motivo) │
  │                                                           │
  │  PASO 4: CALENDARIZAR SESIONES                           │
  │  ───────────────────────────────                          │
  │  "Orient. Individual" — Lunes 10:00-11:30, recurrente    │
  │  "Orient. Grupal" — Mier 16:00-18:00, semanal            │
  │  "Formacion Digital" — L/X/V 09:00-12:00, 4 semanas      │
  │  → Solo si VoBo aprobado (para acciones formacion)        │
  │  → Expansion automatica de recurrencia                    │
  │  → Sincronizacion con Google Calendar (opcional)          │
  │                                                           │
  │  PASO 5: PUBLICAR PARA INSCRIPCION                       │
  │  ─────────────────────────────────                        │
  │  Las participantes ven sesiones disponibles de su carril  │
  │  → Se inscriben, ven plazas disponibles                   │
  │  → Reciben confirmacion + recordatorio (email/push)       │
  │                                                           │
  └───────────────────────────────────────────────────────────┘

FASES 1-5: EJECUCION (EXISTENTE, AHORA ENRIQUECIDO)
═══════════════════════════════════════════════════════

  ┌── Sesion programada se ejecuta ───────────────────────────┐
  │                                                           │
  │  PASO 6: MARCAR ASISTENCIA                               │
  │  ──────────────────────────                               │
  │  Facilitador abre sesion en la plataforma                 │
  │  → Ve lista de inscritos                                  │
  │  → Marca: asistio / no asistio / justificado              │
  │  → AUTOMATICAMENTE:                                       │
  │     a) Crea ActuacionSto por participante                 │
  │     b) Incrementa horas del participante                  │
  │     c) Genera hoja servicio para firma dual               │
  │     d) Actualiza % asistencia                             │
  │                                                           │
  │  PASO 7: FIRMA DUAL (EXISTENTE)                          │
  │  ──────────────────────────────                           │
  │  Hoja servicio → firma tactil participante                │
  │  → firma tactil orientador                                │
  │  → Documento firmado al expediente                        │
  │                                                           │
  │  PASO 8: VERIFICACION AUTOMATICA DE PROGRESO             │
  │  ────────────────────────────────────────────             │
  │  Tras cada sesion, el sistema verifica:                   │
  │  → ¿Participante cumple minimos para transicion de fase?  │
  │  → ¿Plan formativo avanza segun calendario?               │
  │  → ¿Hay riesgo de abandono?                              │
  │  → Alertas al Coordinador si desvios                      │
  │                                                           │
  └───────────────────────────────────────────────────────────┘
```

---

## 11. Flujo Completo de la Participante (Propuesto)

```
PORTAL DE LA PARTICIPANTE (Enriquecido)
════════════════════════════════════════

  ┌── La participante accede a /andalucia-ei/mi-participacion ┐
  │                                                            │
  │  SECCION: MI PROGRESO (EXISTENTE, MEJORADO)               │
  │  ─────────────────────────────────────────                 │
  │  Horas orientacion: 6.5h / 10h [████████░░] 65%           │
  │  Horas formacion:   32h / 50h  [██████░░░░] 64%           │
  │  Asistencia global:             [█████████░] 90%           │
  │  Fase actual: Atencion | Semana: 20 de 52                 │
  │                                                            │
  │  SECCION: MIS PROXIMAS SESIONES (NUEVO)                   │
  │  ──────────────────────────────────────                    │
  │  Lun 17 Mar — 10:00 Orient. Individual (Sede Malaga) [✓]  │
  │  Mie 19 Mar — 16:00 Orient. Grupal (Online) [✓]           │
  │  Vie 21 Mar — 09:00 Formacion Digital M3 (Online) [↗]     │
  │  → [Inscribirme en mas sesiones]                           │
  │                                                            │
  │  SECCION: SESIONES DISPONIBLES (NUEVO)                    │
  │  ────────────────────────────────────                      │
  │  Filtro: Mi carril (Impulso Digital) | Fase: Atencion     │
  │  ┌──────────────────────────────────────────────────┐      │
  │  │ Jue 20 Mar 10:00 — Taller CV Digital             │      │
  │  │ Plazas: 8/12 disponibles | Presencial Sede       │      │
  │  │ [Inscribirme]                                     │      │
  │  └──────────────────────────────────────────────────┘      │
  │  ┌──────────────────────────────────────────────────┐      │
  │  │ Lun 24 Mar 16:00 — Formacion Digital M4          │      │
  │  │ Plazas: 5/15 disponibles | Online                │      │
  │  │ [Inscribirme]                                     │      │
  │  └──────────────────────────────────────────────────┘      │
  │                                                            │
  │  SECCION: MI FORMACION ONLINE (NUEVO — si hay curso LMS) │
  │  ────────────────────────────────────────────────────────  │
  │  Curso: Emprendimiento M1-M5 [██████░░░░] 60%             │
  │  → [Acceder al curso]                                      │
  │  → [Preguntar al Tutor IA]                                │
  │                                                            │
  │  SECCION: MI EXPEDIENTE (EXISTENTE)                       │
  │  ──────────────────────────────────                        │
  │  Documentos, firmas pendientes, etc.                       │
  │                                                            │
  └────────────────────────────────────────────────────────────┘
```

---

## 12. Validacion de Complitud, Integridad, Consistencia y Coherencia

### 12.1 Complitud

| Verificacion | Estado |
|--------------|--------|
| Las 4 entidades cubren el gap: diseno → VoBo → calendarizacion → inscripcion | ✓ |
| Se conectan con las 8 entidades existentes sin orfandad | ✓ |
| Cada entidad tendra: AccessControlHandler, PremiumEntityFormBase, hook_update_N(), Views data, preprocess | Planificado |
| Los 4 servicios tienen constructor DI completo sin circular deps | ✓ |
| Las extensiones a servicios existentes NO rompen interfaces actuales | ✓ |
| Los contenidos de la 1a edicion tienen ruta de digitalizacion (LMS) | ✓ |
| El VoBo SAE tiene workflow completo con subsanacion | ✓ |
| La participante tiene flujo completo: ver sesiones → inscribirse → asistir → ver progreso | ✓ |

### 12.2 Integridad

| Verificacion | Estado |
|--------------|--------|
| `AccionFormativaEi.course_id` usa integer FK (ENTITY-FK-001, cross-module) | ✓ |
| `InscripcionSesionEi.actuacion_sto_id` usa entity_reference (mismo modulo) | ✓ |
| `PlanFormativoEi.acciones_formativas` usa entity_reference multi-value (mismo modulo) | ✓ |
| `SesionProgramadaEi.accion_formativa_id` usa entity_reference (mismo modulo) | ✓ |
| Todas las entidades tienen `tenant_id` obligatorio entity_reference → group | ✓ |
| Todas las entidades tienen `uid` via EntityOwnerTrait | ✓ |
| No se duplica enrollment (InscripcionSesionEi ≠ lms_enrollment) | ✓ |
| No se duplica mentoring_session (SesionProgramadaEi es mas amplia) | ✓ |

### 12.3 Consistencia

| Verificacion | Estado |
|--------------|--------|
| Sigue patron SepeAccionFormativa para workflow VoBo | ✓ |
| Sigue patron AvailabilitySlot para recurrencia | ✓ |
| Sigue los 6 tipos de ActuacionSto (reusar mismos valores) | ✓ |
| Formularios PremiumEntityFormBase (PREMIUM-FORMS-PATTERN-001) | ✓ |
| AccessControlHandler con tenant verification (TENANT-ISOLATION-ACCESS-001) | ✓ |
| PHP 8.4 strict_types, ACCESS-RETURN-TYPE-001, CONTROLLER-READONLY-001 | ✓ |
| Servicios opcionales cross-modulo con @? (OPTIONAL-CROSSMODULE-001) | ✓ |
| Loggers via @logger.channel (LOGGER-INJECT-001) | ✓ |

### 12.4 Coherencia

| Verificacion | Estado |
|--------------|--------|
| El flujo diseno → VoBo → calendarizacion → inscripcion → asistencia → horas cierra el ciclo | ✓ |
| Las horas fluyen al mismo ProgramaParticipanteEi existente via ActuacionStoService | ✓ |
| Las alertas normativas se extienden organicamente | ✓ |
| El Coordinador Hub gana 2 pestanas coherentes con las existentes | ✓ |
| El portal de la participante se enriquece sin romper el existente | ✓ |
| La naturaleza mixta (empleabilidad + emprendimiento) se refleja en carriles | ✓ |
| La IA se integra nativamente en 3 capas: diseno, experiencia participante, justificacion | ✓ |
| Los contenidos de la 1a edicion tienen camino de digitalizacion via LMS | ✓ |

---

## 13. Plan de Implementacion Sprint 13

| Paso | Descripcion | Dependencia | Reglas Clave |
|------|-------------|-------------|--------------|
| 1 | Entidad `AccionFormativaEi` + Interface + AccessControlHandler + Form + hook_update_N() | Base | PREMIUM-FORMS-PATTERN-001, ACCESS-RETURN-TYPE-001, UPDATE-HOOK-REQUIRED-001 |
| 2 | `VoboSaeWorkflowService` (maquina de estados) + tests | Paso 1 | PRESAVE-RESILIENCE-001, UPDATE-HOOK-CATCH-001 |
| 3 | Entidad `PlanFormativoEi` + composicion acciones por carril + validacion minimos | Paso 1 | ENTITY-FK-001, TENANT-001 |
| 4 | Entidad `SesionProgramadaEi` + recurrencia + control plazas | Paso 1 | OPTIONAL-CROSSMODULE-001 (calendar_sync) |
| 5 | Entidad `InscripcionSesionEi` + generacion automatica ActuacionSto | Paso 4 | CONTAINER-DEPS-002 (no circular con ActuacionStoService) |
| 6 | `AccionFormativaService` + `SesionProgramadaService` + `InscripcionSesionService` | Pasos 1-5 | LOGGER-INJECT-001, PHANTOM-ARG-001 |
| 7 | Integracion CoordinadorHub: pestanas "Plan Formativo" + "Calendario" | Pasos 1-6 | ZERO-REGION-001, drupalSettings |
| 8 | Portal participante: seccion "Mis Sesiones" + inscripcion | Pasos 4-6 | SLIDE-PANEL-RENDER-001, CSRF-API-001 |
| 9 | Extension AlertasNormativasService (VoBo timeout, horas insuficientes, plan incompleto) | Pasos 1-6 | — |
| 10 | Integracion IA: generacion esquemas formativos, recomendacion sesiones | Pasos 1-6 | MODEL-ROUTING-CONFIG-001, AGENT-GEN2-PATTERN-001 |
| 11 | Tests: Unit + Kernel para entidades y servicios | Pasos 1-6 | KERNEL-TEST-DEPS-001, MOCK-DYNPROP-001 |
| 12 | RUNTIME-VERIFY-001 + IMPLEMENTATION-CHECKLIST-001 | Todo | Verificar 5 capas runtime |

---

## 14. Riesgos y Mitigaciones

| Riesgo | Prob. | Impacto | Mitigacion |
|--------|-------|---------|------------|
| Duplicar lms_enrollment con InscripcionSesionEi | Media | Alto | Son conceptos distintos: enrollment = matricula en curso, inscripcion = asistencia a sesion puntual. Documentar claramente la diferencia |
| VoBo SAE como cuello de botella | Alta | Critico | Workflow con alertas proactivas + documentacion pregenerada + estados subsanacion |
| Complejidad recurrencia sesiones | Media | Medio | JSON simple (no iCalendar completo). Expansion al publicar, sesiones hijas inmutables |
| 4 entidades en modulo con 8 existentes | Baja | Bajo | Coherentes con el dominio PIIL. Mismo patron que Sprints 1-12 |
| Circular dependency InscripcionSesion ↔ ActuacionSto | Media | Alto | InscripcionSesionService depende de ActuacionStoService (direccion unica, NO circular). ActuacionStoService NO conoce InscripcionSesionService |
| Performance con muchas sesiones recurrentes | Baja | Medio | Expansion lazy: solo generar sesiones para las proximas 4 semanas. Cron para expansion anticipada |
| Contenidos 1a edicion en formato PPTX, no LMS | Media | Medio | Fase posterior: convertir PPTX a lecciones LMS. Sprint 13 solo crea la estructura; los contenidos se cargan despues |

---

## 15. Conclusiones

### 15.1 La Brecha es Clara y Resoluble

El modulo `jaraba_andalucia_ei` tiene una **base tecnica de nivel enterprise** (38 servicios, 8 entidades, firma electronica, justificacion economica). Sin embargo, le falta la **capa de diseno del programa** que permite al Coordinador responder: "¿En que consisten las 50h de formacion?" desde la plataforma.

La 1a edicion se gestiono con Excel, Word, PPTX y email. El SaaS debe digitalizar todo ese flujo manteniendo la riqueza operativa (recibos firmados, certificados, tracking por provincia) pero anadiendo:
- Automatizacion (asistencia → horas → ActuacionSto)
- Calendarizacion (sesiones recurrentes, plazas)
- Self-service (participante se inscribe)
- VoBo SAE como workflow real
- IA nativa (generacion de contenidos, recomendaciones, evaluaciones)

### 15.2 La Propuesta Reutiliza, No Duplica

Las 4 entidades nuevas (`AccionFormativaEi`, `SesionProgramadaEi`, `InscripcionSesionEi`, `PlanFormativoEi`) NO duplican modulos existentes:
- El LMS se **reutiliza** via FK `course_id`
- El mentoring se **referencia** por patron (no por FK)
- Las actuaciones STO se **generan automaticamente** desde inscripciones
- La firma electronica se **reutiliza** intacta
- Las alertas normativas se **extienden** organicamente

### 15.3 La Naturaleza Mixta Queda Reflejada

Los carriles (Impulso Digital / Acelera Pro / Hibrido) + los planes formativos por carril reflejan la dualidad empleabilidad/emprendimiento con:
- Contenidos diferenciados por carril
- VoBo SAE aplicado a formacion de ambas vias
- Insercion dual (cuenta ajena para empleabilidad, cuenta propia para emprendimiento)
- Bridge cross-vertical con `jaraba_business_tools` para emprendedores

### 15.4 IA Nativa, No Accesoria

La IA no es un "extra" — es parte integral del flujo:
- El Coordinador recibe sugerencias IA al disenar acciones formativas
- La participante tiene tutor IA sobre los contenidos de su curso
- La justificacion se enriquece con IA para generacion documental
- Las alertas predictivas usan IA para anticipar problemas

---

## Referencias

### Documentacion del Proyecto
- `CLAUDE.md` v1.4.0 — Directrices maestras del SaaS
- `docs/analisis/2026-03-06_Auditoria_Andalucia_Ei_PIIL_CV_2025_Brechas_Normativas_v1.md` — Auditoria previa de brechas normativas
- `docs/analisis/2026-03-06_Diagnostico_Andalucia_Ei_Mentoring_Courses_Clase_Mundial_v1.md` — Diagnostico de mentorias y cursos
- `docs/implementacion/2026-03-10_Plan_Implementacion_Andalucia_Ei_Cumplimiento_Integral_PIIL_v2.md` — Plan de implementacion integral

### Fuentes Normativas
- Orden 29/09/2023 — Bases Reguladoras PIIL (BOJA)
- Resolucion 31/10/2023 — Convocatoria 2023 ICV
- Resolucion 01/12/2023 — Ampliacion de credito
- Resolucion 15/12/2023 — Concesion subvencion
- Orden 23/07/2025 — Modificaciones programa
- Resolucion 05/08/2025 — Convocatoria 2025 CV

### Fuentes Operativas (1a Edicion)
- `F:\DATOS\PED S.L\Economico-Financiero\Subvenciones\Junta de Andalucia\2023 PIIL` — 14.000+ archivos
- Subcarpetas clave: 04. Acciones Atencion (orientacion + formacion), 05. Acciones Insercion
- Curso de Emprendimiento v2 (5 modulos, 105 MB PPTX)
- Orientacion Inicial v2 (21 MB PPTX)
- Insercion Autoempleo (47 MB PPTX)
- Materiales de referencia (26 categorias, 1023 MB)

### Modulos del Ecosistema Referenciados
- `jaraba_andalucia_ei` — Modulo principal (Sprints 1-12)
- `jaraba_lms` — LMS (Course, Lesson, Enrollment)
- `jaraba_mentoring` — Mentoring (MentoringSession, AvailabilitySlot)
- `jaraba_events` — Eventos (MarketingEvent, EventRegistration)
- `jaraba_training` — Formacion comercial (TrainingProduct, CertificationProgram)
- `jaraba_paths` — Rutas digitalizacion (PathPhase, PathModule, PathStep)
- `jaraba_interactive` — Contenido interactivo (InteractiveContent)
- `jaraba_legal_calendar` — Calendario legal (CalendarConnection)
- `jaraba_sepe_teleformacion` — Integracion SEPE (SepeAccionFormativa — patron de referencia)
- `jaraba_business_tools` — Herramientas emprendimiento (Canvas, MVP, Proyecciones)
