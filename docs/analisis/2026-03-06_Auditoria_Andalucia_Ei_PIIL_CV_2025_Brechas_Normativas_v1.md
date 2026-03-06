# Auditoria: Programa Andalucia +ei vs Normativa PIIL CV 2025 — Analisis de Brechas

**Fecha de creacion:** 2026-03-06 14:00
**Ultima actualizacion:** 2026-03-06 14:00
**Autor:** IA Asistente (Claude Opus 4.6)
**Version:** 1.0.0
**Estado:** Completado
**Categoria:** Auditoria Normativa / Gap Analysis
**Modulos auditados:** `jaraba_andalucia_ei`, `jaraba_copilot_v2`, `jaraba_mentoring`, `ecosistema_jaraba_core`
**Fuentes normativas:** PIIL BBRR Consolidada (30/07/2025), Resolucion Concesion 202599904458144, Ficha Tecnica FT_679, Manual STO ICV25, Manual Operativo V2.1
**Subvencion:** 202.500 EUR | 45 participantes | 18 meses | Junta de Andalucia + FSE+ (85%/15%)

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Metodologia de Auditoria](#2-metodologia-de-auditoria)
3. [Inventario del Modulo jaraba_andalucia_ei](#3-inventario-del-modulo-jaraba_andalucia_ei)
   - 3.1 [Entidades y campos](#31-entidades-y-campos)
   - 3.2 [Servicios](#32-servicios)
   - 3.3 [Controllers y rutas](#33-controllers-y-rutas)
   - 3.4 [Templates y frontend](#34-templates-y-frontend)
4. [Requisitos Normativos PIIL CV 2025](#4-requisitos-normativos-piil-cv-2025)
   - 4.1 [Fases del programa segun normativa](#41-fases-del-programa-segun-normativa)
   - 4.2 [Actuaciones obligatorias por tipo](#42-actuaciones-obligatorias-por-tipo)
   - 4.3 [Documentacion justificativa obligatoria](#43-documentacion-justificativa-obligatoria)
   - 4.4 [Indicadores FSE+ obligatorios](#44-indicadores-fse-obligatorios)
   - 4.5 [Colectivos y requisitos de elegibilidad](#45-colectivos-y-requisitos-de-elegibilidad)
5. [Analisis de Brechas](#5-analisis-de-brechas)
   - 5.1 [Brechas P0 — Criticas (riesgo de incumplimiento normativo)](#51-brechas-p0--criticas)
   - 5.2 [Brechas P1 — Importantes (funcionalidad operativa)](#52-brechas-p1--importantes)
   - 5.3 [Brechas P2 — Mejoras (elevacion a clase mundial)](#53-brechas-p2--mejoras)
6. [Tabla Cruzada: Requisito Normativo vs Estado SaaS](#6-tabla-cruzada)
7. [Integracion con Copilot v2](#7-integracion-con-copilot-v2)
8. [Conclusiones y Recomendaciones](#8-conclusiones-y-recomendaciones)
9. [Referencias](#9-referencias)

---

## 1. Resumen Ejecutivo

Se ha realizado una auditoria cruzada entre:
- **50+ documentos** de la carpeta `F:\DATOS\PED S.L\Economico-Financiero\Subvenciones\Junta de Andalucia\2025 PIIL CV` (normativa, resolucion de concesion, ficha tecnica, manuales STO, manual operativo, contenido formativo, itinerarios diferenciados, calendario, marketing, copilot specs)
- **El modulo `jaraba_andalucia_ei`** y modulos relacionados (`jaraba_copilot_v2`, `jaraba_mentoring`, `ecosistema_jaraba_core`)

**Resultado global:** El modulo tiene una base solida para gestion de participantes, solicitudes y expediente documental, pero le falta el **tracking granular de actuaciones** que exige el STO y la **documentacion justificativa detallada** que exige la Junta/FSE+.

**Brechas identificadas:**
- **7 brechas P0** (criticas — riesgo de incumplimiento normativo)
- **6 brechas P1** (importantes — funcionalidad operativa)
- **4 brechas P2** (mejoras — elevacion)

---

## 2. Metodologia de Auditoria

1. **Lectura exhaustiva** de toda la documentacion normativa: Bases Reguladoras PIIL consolidadas, Resolucion de Concesion, Ficha Tecnica validada FT_679, Manuales STO (tecnico y representante), Manual Operativo V2.1, Contenido Formativo, Itinerarios Diferenciados
2. **Inventario completo** del modulo: 3 entidades (87 campos total), 15 servicios, 14 controllers, 58 rutas, 11 templates, 7 parciales, 6 ficheros JS
3. **Cruce sistematico** requisito-por-requisito entre lo que exige la normativa y lo que implementa el SaaS
4. **Verificacion de integracion** con copilot v2 (EntrepreneurProfile, Hypothesis, Experiment, DIME) y mentoring (MentoringSession, MentorProfile)

---

## 3. Inventario del Modulo jaraba_andalucia_ei

### 3.1 Entidades y campos

#### ProgramaParticipanteEi (entity_type: `programa_participante_ei`)

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| `uid` | entity_reference (user) | Usuario Drupal (owner, EntityOwnerTrait) |
| `tenant_id` | entity_reference (group) | Tenant/grupo propietario |
| `group_id` | entity_reference (group) | Grupo Andalucia +ei de la edicion |
| `dni_nie` | string (12, unique) | Documento identificativo |
| `colectivo` | list_string | jovenes, mayores_45, larga_duracion |
| `provincia_participacion` | list_string | cadiz, granada, malaga, sevilla |
| `fecha_alta_sto` | datetime (date) | Fecha de registro en STO |
| `fase_actual` | list_string | **atencion, insercion, baja** |
| `horas_orientacion_ind` | decimal (8,2) | Orientacion individual acumulada |
| `horas_orientacion_grup` | decimal (8,2) | Orientacion grupal acumulada |
| `horas_formacion` | decimal (8,2) | Formacion acumulada |
| `horas_mentoria_ia` | decimal (8,2) | Mentoria IA (Copilot) acumulada |
| `horas_mentoria_humana` | decimal (8,2) | Mentoria humana acumulada |
| `carril` | list_string | impulso_digital, acelera_pro, hibrido |
| `incentivo_recibido` | boolean | Incentivo 528 EUR recibido |
| `tipo_insercion` | list_string | cuenta_ajena, cuenta_propia, agrario |
| `fecha_insercion` | datetime (date) | Fecha de insercion verificada |
| `sto_sync_status` | list_string | pending, synced, error |
| `created` / `changed` | timestamps | Automaticos |

**Total: 19 campos + uid + created + changed = 22 campos**

#### SolicitudEi (entity_type: `solicitud_ei`)

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| `nombre` | string | Nombre completo |
| `email` | email | Email de contacto |
| `telefono` | string | Telefono |
| `fecha_nacimiento` | datetime (date) | Fecha de nacimiento |
| `dni_nie` | string | DNI/NIE |
| `provincia` | list_string | 8 provincias andaluzas |
| `municipio` | string | Municipio |
| `situacion_laboral` | list_string | desempleado, ocupado, etc. |
| `tiempo_desempleo` | list_string | menos_6m, 6_12m, mas_12m |
| `nivel_estudios` | list_string | sin_estudios a doctorado |
| `es_migrante` | boolean | Indicador de migrante |
| `percibe_prestacion` | boolean | Percibe prestacion |
| `experiencia_sector` | string_long | Experiencia |
| `motivacion` | string_long | Motivacion |
| `estado` | list_string | pendiente, contactado, admitido, rechazado, lista_espera |
| `colectivo_inferido` | list_string | Colectivo inferido por IA |
| `notas_admin` | string_long | Notas administrativas |
| `ip_address` | string | IP del solicitante |
| `tenant_id` | entity_reference (group) | Tenant |
| `ai_score` | integer | Puntuacion IA |
| `ai_justificacion` | string_long | Justificacion IA |
| `ai_recomendacion` | list_string | Recomendacion IA |
| `created` / `changed` | timestamps | Automaticos |

**Total: 22 campos + created + changed = 24 campos**

#### ExpedienteDocumento (entity_type: `expediente_documento`)

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| `participante_id` | entity_reference (programa_participante_ei) | Participante propietario |
| `tenant_id` | entity_reference (group) | Tenant |
| `titulo` | string | Titulo del documento |
| `categoria` | list_string | 22 categorias (sto_*, programa_*, tarea_*, cert_*, mentoria_*, orientacion_*, insercion_*) |
| `archivo_vault_id` | string | Referencia a Legal Vault |
| `archivo_nombre` | string | Nombre original |
| `archivo_mime` | string | Tipo MIME |
| `archivo_tamano` | integer | Tamano en bytes |
| `estado_revision` | list_string | pendiente, aprobado, rechazado, en_revision |
| `revision_ia_score` | decimal | Puntuacion revision IA |
| `revision_ia_feedback` | text_long | Feedback IA |
| `revision_humana_notas` | text_long | Notas revision humana |
| `revisor_id` | entity_reference (user) | Revisor |
| `firmado` | boolean | Si esta firmado |
| `firma_fecha` | datetime | Fecha de firma |
| `firma_certificado_info` | text_long | Info del certificado digital |
| `requerido_sto` | boolean | Requerido por STO |
| `sto_sincronizado` | boolean | Sincronizado con STO |
| `fecha_vencimiento` | datetime | Fecha de vencimiento |
| `status` | boolean | Publicado |
| `created` / `changed` | timestamps | Automaticos |

**Total: 20 campos + uid + created + changed = 23 campos**

### 3.2 Servicios

| Servicio | Clase | Funcion principal |
|----------|-------|-------------------|
| `jaraba_andalucia_ei.ai_mentorship_tracker` | AiMentorshipTracker | Tracking horas mentoria IA via Copilot |
| `jaraba_andalucia_ei.sto_export` | StoExportService | Exportacion CSV para STO |
| `jaraba_andalucia_ei.fase_transition_manager` | FaseTransitionManager | Transiciones atencion->insercion->baja |
| `jaraba_andalucia_ei.solicitud_triage` | SolicitudTriageService | Triage IA de solicitudes |
| `jaraba_andalucia_ei.expediente` | ExpedienteService | CRUD de expediente documental |
| `jaraba_andalucia_ei.documento_revision_ia` | DocumentoRevisionIaService | Revision IA de documentos |
| `jaraba_andalucia_ei.informe_progreso_pdf` | InformeProgresoPdfService | PDF de informe de progreso |
| `jaraba_andalucia_ei.copilot_context` | AndaluciaEiCopilotContextProvider | Contexto para Copilot |
| `jaraba_andalucia_ei.adaptive_difficulty` | AdaptiveDifficultyEngine | Motor de dificultad adaptativa |
| `jaraba_andalucia_ei.mensajeria_integration` | MensajeriaIntegrationService | Integracion mensajeria |
| `jaraba_andalucia_ei.copilot_bridge` | AndaluciaEiCopilotBridgeService | Bridge con Copilot v2 |
| `jaraba_andalucia_ei.coordinador_hub` | CoordinadorHubService | Hub operativo coordinador |
| `jaraba_andalucia_ei.expediente_completeness` | ExpedienteCompletenessService | Completitud documental |
| `jaraba_andalucia_ei.hoja_servicio_mentoria` | HojaServicioMentoriaService | Generacion hoja servicio mentoria |
| `jaraba_andalucia_ei.human_mentorship_tracker` | HumanMentorshipTracker | Tracking horas mentoria humana |

### 3.3 Controllers y rutas

| Controller | Ruta principal | Rol |
|-----------|---------------|-----|
| AndaluciaEiController | `/andalucia-ei`, `/andalucia-ei/mi-participacion` | Dashboard participante |
| ParticipantePortalController | `/andalucia-ei/mi-participacion` | Portal completo participante |
| AndaluciaEiLandingController | `/andalucia-ei/landing` | Landing publica |
| CoordinadorDashboardController | `/programa/coordinador` | Dashboard coordinador |
| CoordinadorHubApiController | 7 endpoints API `/api/v1/andalucia-ei/hub/*` | API REST hub |
| OrientadorDashboardController | `/programa/orientador` | Dashboard orientador |
| ExpedienteHubController | `/programa/expediente` | Hub documental |
| ProgramaMentoresController | `/programa/mentores` | Mentores por edicion |
| ProgramaFormacionController | `/programa/formacion` | Formacion por edicion |
| HojaServicioApiController | `/api/v1/programa/hoja-servicio/*/firmar` | API firma electronica |
| GuiaParticipanteController | `/andalucia-ei/guia-participante` | Guia interactiva |
| SolicitudEiController | `/andalucia-ei/solicitar` | Formulario solicitud |
| StoExportController | `/admin/content/andalucia-ei/export-sto` | Exportacion STO |
| AndaluciaEiApiController | 14 endpoints `/api/v1/andalucia-ei/*` | API general |

### 3.4 Templates y frontend

**Templates principales (11):**
- `andalucia-ei-dashboard.html.twig`, `participante-portal.html.twig`
- `coordinador-dashboard.html.twig`, `orientador-dashboard.html.twig`
- `programa-mentores.html.twig`, `programa-formacion.html.twig`
- `expediente-hub.html.twig`, `solicitud-ei-page.html.twig`
- `andalucia-ei-landing.html.twig`, `andalucia-ei-guia-participante.html.twig`

**Parciales (7):**
- `_participante-hero.html.twig`, `_participante-timeline.html.twig`
- `_participante-formacion.html.twig`, `_participante-expediente.html.twig`
- `_participante-acciones.html.twig`, `_participante-logros.html.twig`
- `_participante-mensajeria.html.twig`

**JS (6):**
- `andalucia-ei-dashboard.js`, `coordinador-hub.js`, `guia-form.js`
- `participante-portal.js`, `programa-formacion.js`, `programa-mentores.js`

---

## 4. Requisitos Normativos PIIL CV 2025

### 4.1 Fases del programa segun normativa

La normativa PIIL define un itinerario con fases diferenciadas:

| Fase | Descripcion | Duracion | Actividades |
|------|-------------|----------|-------------|
| **Acogida** | Primera entrevista, firma DACI, recogida indicadores FSE+ | 1-2 sesiones | Entrevista inicial, firma documentos, evaluacion preliminar |
| **Diagnostico** | Evaluacion competencias, elaboracion IPI | 2-3 sesiones | DIME (10 preguntas), test competencias, asignacion carril |
| **Atencion** | Orientacion + formacion intensiva | 10h orient. + 50h form. | Sesiones individuales/grupales, talleres, mentoria |
| **Insercion** | Seguimiento laboral activo | 40h orient. + 4 meses empleo | Prospección, matching empresa, alta SS |
| **Seguimiento** | Verificacion post-insercion | 4 meses minimo | Revision mantenimiento empleo, indicadores FSE+ salida |
| **Baja** | Abandono o finalizacion | — | Documentar motivo, indicadores FSE+ salida |

**En el SaaS:** `fase_actual` solo tiene 3 valores: `atencion`, `insercion`, `baja`. Faltan `acogida`, `diagnostico`, `seguimiento`.

### 4.2 Actuaciones obligatorias por tipo

El STO exige registrar cada actuacion individualmente:

| Tipo Actuacion | Campos STO Obligatorios | Existe Entidad |
|----------------|------------------------|----------------|
| Orientacion individual | fecha, hora_inicio, hora_fin, contenido, resultado, lugar | NO (solo contadores) |
| Orientacion grupal | fecha, hora_inicio, hora_fin, n participantes, contenido, lugar | NO |
| Formacion | fecha, horas, contenido, VoBo SAE, entidad formadora, certificacion | NO |
| Insercion laboral | tipo, empresa, CIF, contrato, jornada, fecha alta SS, sector | PARCIAL |
| Prospección empresarial | empresa, CIF, contacto, sector, resultado, fecha | NO |

### 4.3 Documentacion justificativa obligatoria

| Documento | Momento | Firma | Existe |
|-----------|---------|-------|--------|
| DACI (Documento Aceptacion Compromisos e Informacion) | Al inicio (acogida) | Participante | NO |
| Recibo de Servicio | Cada actuacion/dia | Participante + orientador/formador | PARCIAL (solo mentoria) |
| Hoja de servicio de orientacion | Cada sesion orientacion | Orientador | SI (HojaServicioMentoriaService) |
| Informe de progreso | Trimestral | Coordinador | SI (InformeProgresoPdfService) |
| Ficha de insercion laboral | Al insertar | Orientador | NO (campo basico) |
| Indicadores FSE+ entrada | Al iniciar | — | NO |
| Indicadores FSE+ salida | Al finalizar/insertar | — | NO |
| Indicadores FSE+ 6 meses | 6 meses post-salida | — | NO |
| Justificacion economica | Trimestral + final | Coordinador | NO |

### 4.4 Indicadores FSE+ obligatorios

El Fondo Social Europeo Plus exige recogida de datos en 3 momentos:

**Entrada (al inscribirse):**
- Situacion laboral detallada
- Nivel educativo (ISCED)
- Discapacidad (tipo y grado)
- Pais de origen / nacionalidad
- Hogar unipersonal (si/no)
- Hijos a cargo (numero)
- Zona rural/urbana
- Situacion de sin hogar
- Comunidad marginada

**Salida (al terminar/insertar):**
- Situacion laboral al terminar
- Tipo de cualificacion obtenida
- Mejora de situacion laboral

**6 meses post-salida:**
- Situacion laboral actual
- Tipo de contrato (indefinido/temporal)
- Mejora de cualificacion
- Inclusion social

### 4.5 Colectivos y requisitos de elegibilidad

Segun la Resolucion de Concesion y Ficha Tecnica FT_679:

| Colectivo | Definicion | Verificacion | En SaaS |
|-----------|-----------|-------------|---------|
| Larga duracion | >12 meses desempleado | Certificado SAE | SI (larga_duracion) |
| Mayores 45 | Edad >= 45 al inicio | DNI/fecha nacimiento | SI (mayores_45) |
| Migrantes | Extranjeros con permiso | NIE + permiso residencia | NO en allowed_values |
| Perceptores prestaciones | Cobran subsidio/prestacion | Certificado SEPE | NO en allowed_values |

**Nota critica:** El campo `colectivo` del SaaS tiene `jovenes`, `mayores_45`, `larga_duracion` pero NO incluye `migrantes` ni `perceptores_prestaciones` que son colectivos activos en la Resolucion de Concesion. Ademas incluye `jovenes` que NO es un colectivo de esta edicion (es de Garantia Juvenil, no PIIL CV).

---

## 5. Analisis de Brechas

### 5.1 Brechas P0 — Criticas

#### GAP-PIIL-01: Fases incompletas

**Normativa:** acogida -> diagnostico -> atencion -> insercion -> seguimiento -> baja
**SaaS:** atencion, insercion, baja (3 de 6)

**Impacto:** No se puede registrar en el STO que un participante esta en fase de acogida (recogida de documentos, firma DACI) ni en diagnostico (evaluacion DIME, asignacion de carril). El seguimiento post-insercion (obligatorio 4 meses minimo) no tiene representacion.

**Solucion:** Ampliar `fase_actual` a 6 valores. Actualizar `FaseTransitionManager` con las transiciones validas extendidas. Crear logica para cada subfase.

#### GAP-PIIL-02: Sin entidad de Actuacion STO

**Normativa:** Cada actuacion (orientacion individual, grupal, formacion, prospeccion) debe registrarse con fecha, hora inicio, hora fin, contenido, resultado, lugar.
**SaaS:** Solo existen contadores decimales (`horas_orientacion_ind`, `horas_orientacion_grup`, `horas_formacion`). No hay tracking granular.

**Impacto:** No se pueden generar los informes de seguimiento trimestral ni la justificacion final. Cada actuacion sin documentar es una actuacion no justificable ante la Junta.

**Solucion:** Crear entidad `ActuacionSto` con campos: tipo_actuacion, participante_id, fecha, hora_inicio, hora_fin, duracion_calculada, contenido, resultado, lugar, orientador_id, firmado_participante, firmado_orientador, recibo_servicio_id.

#### GAP-PIIL-03: Indicadores FSE+ sin tracking

**Normativa:** Recogida obligatoria en 3 momentos (entrada, salida, 6 meses post-salida) de datos sociodemograficos y laborales detallados.
**SaaS:** No existe ninguna entidad ni campo para indicadores FSE+.

**Impacto:** Sin estos datos, la subvencion FSE+ (85% de 202.500 EUR = 172.125 EUR) no es certificable ante la UE.

**Solucion:** Crear entidad `IndicadorFsePlus` con campos por cada momento de recogida, o anadir campos a ProgramaParticipanteEi para los indicadores de entrada, y crear entidad separada para salida y seguimiento.

#### GAP-PIIL-04: Formacion sin flujo VoBo SAE

**Normativa:** Toda accion formativa debe ser aprobada por el SAE (Visto Bueno) antes de impartirse. El STO registra el VoBo como un campo obligatorio.
**SaaS:** No existe ningun flujo de aprobacion de formacion.

**Impacto:** Las acciones formativas realizadas sin VoBo del SAE no son validas ni justificables.

**Solucion:** Crear campo `vobo_sae_status` (pendiente/aprobado/rechazado/modificaciones) en la entidad de actuacion formativa, con flujo de subida de documento VoBo y fecha de aprobacion.

#### GAP-PIIL-05: Insercion Laboral sin detalle

**Normativa:** La insercion requiere documentacion diferenciada segun tipo:
- Cuenta ajena: CIF empresa, nombre empresa, tipo contrato (indefinido/temporal/practicas), jornada (completa/parcial), fecha alta SS, codigo cuenta cotizacion
- Cuenta propia: fecha alta RETA, CNAE, sector, modelo 036/037
- Agrario: empresa agraria, fechas campana, tipo cultivo

**SaaS:** Solo existe `tipo_insercion` (cuenta_ajena/cuenta_propia/agrario) y `fecha_insercion` en ProgramaParticipanteEi. Faltan todos los campos de detalle.

**Impacto:** La insercion no se puede verificar ni justificar sin estos datos de detalle.

**Solucion:** Crear entidad `InsercionLaboral` con campos diferenciados por tipo, vinculada a ProgramaParticipanteEi.

#### GAP-PIIL-06: DACI digital inexistente

**Normativa:** El DACI (Documento de Aceptacion de Compromisos e Informacion) es obligatorio en el primer dia. Informa al participante de derechos, obligaciones, compromisos del programa. Debe firmarse digitalmente.
**SaaS:** No existe entidad ni flujo para el DACI.

**Impacto:** Requisito obligatorio del primer dia. Sin DACI firmado, la participacion no es valida formalmente.

**Solucion:** Crear flujo de generacion y firma de DACI como parte de la fase de acogida, usando FirmaDigitalService.

#### GAP-PIIL-07: Colectivos incorrectos

**Normativa:** Los colectivos activos de esta edicion son: `larga_duracion`, `mayores_45`, `migrantes`, `perceptores_prestaciones`.
**SaaS:** El campo `colectivo` tiene: `jovenes` (NO aplica), `mayores_45`, `larga_duracion`. Faltan `migrantes` y `perceptores_prestaciones`.

**Impacto:** No se pueden registrar participantes migrantes ni perceptores de prestaciones, que son colectivos diana del programa.

**Solucion:** Actualizar allowed_values del campo `colectivo` eliminando `jovenes` y anadiendo `migrantes`, `perceptores_prestaciones`. Crear hook_update_N().

### 5.2 Brechas P1 — Importantes

#### GAP-PIIL-08: Calendario de 12 semanas desconectado

**Manual Operativo V2.1:** Programa de 12 semanas con 5 fases metodologicas (Mentalidad->Validacion->Viabilidad->Ventas->Cierre), 20 pildoras formativas, 44 experimentos, hitos de evaluacion por semana.
**SaaS:** No existe entidad de calendario que mapee semanas a sesiones, pildoras, experimentos y entregables.

#### GAP-PIIL-09: DIME Diagnostico desconectado

**Manual Operativo:** Diagnostico DIME (10 preguntas D1-D2/I1-I3/M1-M2/E1-E3, escala 0-20) asigna carril automaticamente (IMPULSO <=9 / ACELERA >=10).
**SaaS:** El DIME existe en `jaraba_copilot_v2` pero NO esta integrado en `jaraba_andalucia_ei`. El campo `carril` no se rellena automaticamente desde el DIME.

#### GAP-PIIL-10: Validacion BMC desconectada

**Manual Operativo:** Dashboard de validacion BMC con 9 bloques y semaforo RED/YELLOW/GREEN.
**SaaS:** Existe en copilot v2 pero no se refleja en el perfil del participante ni en los dashboards del coordinador/orientador.

#### GAP-PIIL-11: Plazos normativos sin alertas

**Normativa:** Plazos criticos: 30 dias para comunicar inicio, informes trimestrales, justificacion parcial/final.
**SaaS:** No existe sistema de alertas/recordatorios de plazos.

#### GAP-PIIL-12: Prospeccion empresarial sin tracking

**Normativa:** Documentar acciones de prospeccion: empresas contactadas, sector, resultado, matching.
**SaaS:** Sin entidad dedicada.

#### GAP-PIIL-13: Recibo de servicio incompleto

**Normativa:** Cada actuacion/dia requiere Recibo de Servicio firmado con: datos participante, DNI, expediente STO, tipo actuacion, descripcion, fecha, hora inicio, hora fin, firma participante, firma orientador, codigo proyecto.
**SaaS:** Solo existe para sesiones de mentoria (HojaServicioMentoriaService). Falta para orientacion individual/grupal y formacion.

### 5.3 Brechas P2 — Mejoras

#### GAP-PIIL-14: Gamificacion Pi desconectada

Puntos de Impacto disenados pero no integrados en contexto andalucia_ei.

#### GAP-PIIL-15: Club Alumni / Circulos de Responsabilidad

Descritos en manual operativo como fase post-programa. Sin implementacion.

#### GAP-PIIL-16: Alta Autonomo checklist

Estrategia documentada pero sin checklist automatizado ni integracion con flujo de insercion cuenta propia.

#### GAP-PIIL-17: 7 Modos Copilot sin restriccion por fase

Los 7 modos existen pero no se restringen segun la fase del programa (ej: CFO Sintetico solo en fase Viabilidad).

---

## 6. Tabla Cruzada: Requisito Normativo vs Estado SaaS

| Cod | Requisito Normativo | Fuente | Implementado | Gap ID |
|-----|---------------------|--------|-------------|--------|
| RN-01 | Fases: acogida, diagnostico, atencion, insercion, seguimiento, baja | BBRR Art.11 | PARCIAL (3/6) | GAP-PIIL-01 |
| RN-02 | Registro individual de cada actuacion (orient. ind/grup, formacion) | Manual STO | NO | GAP-PIIL-02 |
| RN-03 | Indicadores FSE+ en 3 momentos | BBRR Art.23, Reglamento FSE+ | NO | GAP-PIIL-03 |
| RN-04 | VoBo SAE para acciones formativas | Manual STO Cap.4 | NO | GAP-PIIL-04 |
| RN-05 | Detalle insercion laboral por tipo | BBRR Art.17 | PARCIAL | GAP-PIIL-05 |
| RN-06 | DACI firmado al inicio | BBRR Art.14 | NO | GAP-PIIL-06 |
| RN-07 | Colectivos: larga_duracion, mayores_45, migrantes, perceptores | Resolucion Concesion | PARCIAL (2/4) | GAP-PIIL-07 |
| RN-08 | Recibo de servicio firmado cada actuacion | BBRR Art.19 | PARCIAL (solo mentoria) | GAP-PIIL-13 |
| RN-09 | Solicitud y admision de participantes | BBRR Art.12 | SI | — |
| RN-10 | Expediente documental por participante | BBRR Art.20 | SI | — |
| RN-11 | Exportacion datos para STO | Manual STO | SI | — |
| RN-12 | Tracking horas por tipo de actuacion | BBRR Art.15 | SI (contadores) | — |
| RN-13 | Informe de progreso periodico | BBRR Art.21 | SI (PDF) | — |
| RN-14 | Firma digital de documentos | BBRR Art.22 | SI (FirmaDigitalService) | — |
| RN-15 | Triage y priorizacion de solicitudes | Interno | SI (IA) | — |
| RN-16 | Transicion de fases con requisitos | BBRR Art.16 | SI (FaseTransitionManager) | — |
| RN-17 | Calendario 12 semanas con hitos | Manual Operativo | NO | GAP-PIIL-08 |
| RN-18 | Diagnostico DIME y asignacion carril | Manual Operativo | PARCIAL (en copilot) | GAP-PIIL-09 |
| RN-19 | Dashboard BMC con validacion | Manual Operativo | PARCIAL (en copilot) | GAP-PIIL-10 |
| RN-20 | Alertas de plazos normativos | BBRR plazos | NO | GAP-PIIL-11 |

---

## 7. Integracion con Copilot v2

El modulo `jaraba_copilot_v2` contiene entidades y logica que son relevantes para andalucia_ei pero no estan integradas:

| Componente Copilot v2 | Relevancia | Integracion actual |
|-----------------------|-----------|-------------------|
| EntrepreneurProfile | Perfil emprendedor (carril ACELERA) | Via CopilotBridgeService |
| Hypothesis | Hipotesis de negocio BMC | Sin integracion directa |
| Experiment | Experimentos de validacion (44 tipos) | Sin integracion directa |
| DIME Diagnostic | Asignacion automatica de carril | Sin integracion (campo carril manual) |
| BMC Dashboard | Semaforo de validacion por bloque | Sin reflejo en andalucia_ei |
| 7 Copilot Modes | Mentoria IA contextualizada por fase | Via CopilotContextProvider |

---

## 8. Conclusiones y Recomendaciones

### Lo que funciona bien
- Modelo de datos base (ProgramaParticipanteEi con tracking de horas)
- Flujo de solicitudes con triage IA
- Expediente documental con 22 categorias y revision IA
- Dashboards de coordinador y orientador (recien implementados)
- Hub de mentores y formacion contextualizados por edicion
- Firma digital via FirmaDigitalService
- Exportacion STO

### Lo que falta (por prioridad)
1. **P0 — Obligatorio por normativa:** Fases completas, actuaciones STO, indicadores FSE+, VoBo formacion, detalle insercion, DACI, colectivos correctos
2. **P1 — Operativo:** Calendario 12 semanas, DIME automatico, BMC integrado, alertas plazos, prospeccion empresarial, recibos universales
3. **P2 — Elevacion:** Gamificacion, alumni, alta autonomo, restriccion copilot por fase

### Recomendacion
Implementar primero las 7 brechas P0 para asegurar el cumplimiento normativo y la justificacion de la subvencion de 202.500 EUR. Las brechas P1 y P2 se pueden abordar en sprints posteriores.

---

## 9. Referencias

- PIIL BBRR Version Consolidada 20250730 (Bases Reguladoras)
- Resolucion de Concesion 202599904458144 (19/12/2025)
- Ficha Tecnica Validada FT_679 (28/01/2026)
- Manual Gestion P.Tecnico STO ICV25 (01/2026)
- Manual Representante Entidad STO INTEGRALES ICV 25
- Manual Operativo Completo Andalucia_ei V2.1 (21/01/2026)
- Contenido Formativo Integral V2.1
- Anexo Itinerarios Diferenciados Carriles
- Plan de implementacion: `docs/implementacion/2026-03-06_Plan_Implementacion_Andalucia_Ei_Mentoring_Cursos_Clase_Mundial_v1.md`
