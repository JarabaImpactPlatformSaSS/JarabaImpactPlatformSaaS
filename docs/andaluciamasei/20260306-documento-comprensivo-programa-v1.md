





PROGRAMA ANDALUCÍA +ei
Emprendimiento Aumentado con IA y Estrategia Real

Documento Comprensivo de Implementación en el SaaS
Jaraba Impact Platform


Plataforma de Ecosistemas Digitales S.L.
Entidad Gestora del Programa

Versión 1.0 — 06 de marzo de 2026
DOCUMENTO CONFIDENCIAL

# Índice de Contenidos
- 1. Resumen Ejecutivo
- 2. Marco Normativo y Contexto del Programa
- 3. Arquitectura Técnica del Módulo
- 4. Modelo de Datos: Entidades del Sistema
- 5. Itinerario PIIL CV 2025: Las 6 Fases
- 6. Servicios de Negocio
- 7. Capa de Presentación: Controladores y Vistas
- 8. APIs REST
- 9. Sistema de Permisos y Seguridad
- 10. Inteligencia Artificial Integrada
- 11. Integración Cross-Vertical
- 12. Indicadores FSE+ y Cumplimiento Normativo
- 13. Infraestructura de Testing
- 14. Diagrama de Arquitectura
- 15. Glosario

# 1. Resumen Ejecutivo
El Programa Andalucía +ei es un vertical completo dentro del ecosistema Jaraba Impact Platform, diseñado para gestionar Programas de Itinerarios Integrados y Personalizados de Inserción Laboral (PIIL) según la Convocatoria Vigente 2025 de la Junta de Andalucía. La plataforma digitaliza el ciclo completo del participante, desde la solicitud inicial hasta la inserción laboral verificada y el seguimiento posterior a 6 meses.
El módulo jaraba_andalucia_ei implementa un sistema integral que combina: gestión de participantes con tracking de horas normativas, mentoría aumentada con Inteligencia Artificial (Copilot), expediente documental digitalizado con almacenamiento encriptado, indicadores FSE+ (Fondo Social Europeo Plus) para justificación ante la Unión Europea, y sincronización con el Sistema Telemático de Orientación (STO) del Servicio Andaluz de Empleo.
## Cifras Clave del Módulo

# 2. Marco Normativo y Contexto del Programa
El programa Andalucía +ei se enmarca dentro de los Programas de Itinerarios Integrados y Personalizados de Inserción Laboral (PIIL), financiados conjuntamente por la Junta de Andalucía y el Fondo Social Europeo Plus (FSE+) del periodo de programación 2021-2027.
## Colectivos Destinatarios
- Personas desempleadas de larga duración (>12 meses)
- Personas mayores de 45 años
- Personas migrantes
- Perceptores de prestaciones y subsidios por desempleo
## Ámbito Territorial
El programa opera en 4 provincias de Andalucía: Cádiz, Granada, Málaga y Sevilla. Cada participante queda vinculado a su provincia de inscripción en el STO (Sistema Telemático de Orientación del Servicio Andaluz de Empleo).
## Carriles del Programa
- Impulso Digital: Orientado a la empleabilidad y competencias digitales.
- Acelera Pro: Orientado al emprendimiento y creación de empresa.
- Híbrido: Combinación de ambos carriles según perfil del participante.
## Incentivo Económico
Los participantes que cumplan los requisitos de horas mínimas (10h de orientación + 50h de formación) tienen derecho a un incentivo económico de 528€, gestionado y verificado a través de la plataforma.

# 3. Arquitectura Técnica del Módulo
## 3.1 Stack Tecnológico
## 3.2 Módulo jaraba_andalucia_ei
El módulo se identifica como jaraba_andalucia_ei dentro del ecosistema de más de 80 módulos custom del proyecto. Pertenece al paquete "Jaraba Programas" y depende de los siguientes módulos:
- ecosistema_jaraba_core: Módulo transversal con servicios compartidos (tenant, bridge, theme).
- jaraba_sepe_teleformacion: Integración con el SEPE y teleformación homologada.
- jaraba_lms: Sistema de gestión de aprendizaje (LMS) con cursos y tracking.
- jaraba_mentoring: Plataforma de mentoría humana con sesiones y hojas de servicio.
- jaraba_copilot_v2: Motor de IA conversacional (Copilot) con agentes Gen 2.
## 3.3 Arquitectura Multi-Tenant
El módulo opera dentro de la arquitectura multi-tenant de la plataforma, basada en el patrón Group Module para aislamiento de contenido (soft isolation). Toda entidad incluye un campo tenant_id que garantiza el aislamiento de datos entre organizaciones. El servicio TenantBridgeService resuelve la relación bidireccional entre entidades Tenant (facturación) y Group (aislamiento de contenido), siguiendo la regla TENANT-BRIDGE-001.
## 3.4 Estructura de Ficheros

# 4. Modelo de Datos: Entidades del Sistema
El módulo define 6 entidades de contenido (ContentEntity) que modelan el ciclo completo del programa. Todas siguen las convenciones del proyecto: AccessControlHandler dedicado, integración con Views, Field UI, aislamiento por tenant_id, y auditoría de timestamps.
## 4.1 ProgramaParticipanteEi
Entidad principal del módulo. Representa a un participante inscrito en el programa con todos sus datos de identificación, seguimiento de horas, fase PIIL actual, estado de sincronización STO, DACI y documentación FSE+.

Nota: La entidad implementa EntityOwnerInterface y EntityChangedInterface. La clave de etiqueta (label) es el campo dni_nie.
## 4.2 SolicitudEi
Solicitud de participación en el programa. Se crea desde el formulario público accesible sin autenticación. Incluye datos del candidato para triaje y gestión administrativa. El campo tenant_id se resuelve automáticamente mediante TenantContextService en el momento del envío.
## 4.3 ActuacionSto
Registra cada actuación individual del itinerario PIIL. Las actuaciones son la unidad fundamental de tracking: cada sesión de orientación, formación, tutoría, prospección o intermediación genera una ActuacionSto con hora de inicio/fin, duración calculada, contenido realizado y firmas de participante y orientador.
## 4.4 IndicadorFsePlus
Registra los indicadores obligatorios del Fondo Social Europeo Plus (FSE+), recogidos en tres momentos del itinerario: entrada (al incorporarse al programa), salida (al finalizar) y seguimiento a 6 meses. Los indicadores incluyen datos sociodemográficos (situación laboral, nivel educativo ISCED, discapacidad, nacionalidad, zona de residencia) y de resultado (tipo de contrato obtenido, cualificación, mejora de situación, inclusión social).
Total: 26 campos de datos + campos de sistema. Esencial para la justificación ante la UE.
## 4.5 InsercionLaboral
Registra el detalle completo de cada inserción laboral lograda por un participante. La entidad diferencia tres tipos de inserción con campos condicionales específicos:
Campos comunes a los tres tipos: participante_id, tipo_insercion, fecha_alta, verificado, documento_acreditativo_id (referencia al expediente), notas.
## 4.6 ExpedienteDocumento
Documento dentro del expediente de un participante. Almacena metadatos y referencia al archivo encriptado en el vault (jaraba_legal_vault). Incluye campos de tipo de documento, estado de revisión, firma digital, y vinculación con el participante. Soporta revisión automática por IA mediante DocumentoRevisionIaService.

# 5. Itinerario PIIL CV 2025: Las 6 Fases
El corazón del programa es el itinerario de 6 fases canónicas definidas por la normativa PIIL CV 2025. El servicio FaseTransitionManager gestiona todas las transiciones entre fases, verificando prerrequisitos normativos antes de cada cambio.
## 5.1 Diagrama de Fases
acogida → diagnóstico → atención → inserción → seguimiento → baja
La fase "baja" es absorbente: puede alcanzarse desde cualquier fase activa, pero una vez en baja no hay transición de salida.
## 5.2 Descripción de Cada Fase
### Fase 1: Acogida
Primer contacto con el participante tras la aprobación de su solicitud. Se genera automáticamente el DACI (Documento de Aceptación de Compromisos e Información), se asigna la fecha de inicio del programa, y se inicializa la semana_actual a 0. El servicio DaciService genera el documento PDF de compromisos en el expediente.
Transiciones válidas: diagnóstico, baja.
### Fase 2: Diagnóstico
Evaluación del perfil del participante. Se recogen los indicadores FSE+ de entrada (IndicadorFsePlus con momento_recogida = "entrada") y se determina el carril más adecuado (Impulso Digital, Acelera Pro o Híbrido). El sistema verifica que el DACI esté firmado.
Transiciones válidas: atención, baja.
### Fase 3: Atención
Fase principal de intervención. El participante recibe orientación individual/grupal, formación (LMS + talleres), mentoría humana y mentoría IA (Copilot). Cada sesión genera una ActuacionSto que alimenta los contadores de horas. El CalendarioProgramaService actualiza la semana_actual vía cron.
Transiciones válidas: inserción, baja. Prerrequisito: 10h orientación + 50h formación.
### Fase 4: Inserción
El participante logra una inserción laboral verificada. Se crea una entidad InsercionLaboral con el detalle completo (tipo, empresa, contrato, documentación). El sistema verifica la documentación acreditativa antes de confirmar la transición.
Transiciones válidas: seguimiento, baja.
### Fase 5: Seguimiento
Periodo de seguimiento post-inserción (6 meses). Se recogen los indicadores FSE+ de seguimiento (IndicadorFsePlus con momento_recogida = "seguimiento_6m") y se evalúa la sostenibilidad de la inserción laboral.
Transiciones válidas: baja.
### Fase 6: Baja
Fin del itinerario. Registra el motivo de baja (6 opciones: abandono voluntario, inserción lograda, incumplimiento, fin de programa, exclusión normativa, otro). Se recogen los indicadores FSE+ de salida y se actualiza fecha_fin_programa. Es un estado absorbente.
Sin transiciones de salida.
## 5.3 Mapa de Transiciones Válidas

# 6. Servicios de Negocio
El módulo registra 21 servicios en el contenedor de dependencias de Drupal, todos con inyección de dependencias vía constructor y servicios opcionales cross-módulo con el patrón @?. A continuación se describen los servicios principales agrupados por funcionalidad.
## 6.1 Gestión del Itinerario PIIL
### FaseTransitionManager
ID: jaraba_andalucia_ei.fase_transition_manager
Orquesta las transiciones entre las 6 fases canónicas del PIIL. Verifica prerrequisitos normativos (horas mínimas, documentación, DACI firmado) antes de permitir cada cambio de fase. Despacha eventos Symfony para que otros módulos reaccionen a las transiciones.
### CalendarioProgramaService
ID: jaraba_andalucia_ei.calendario_programa
Gestiona la temporalización del programa. Calcula la semana_actual de cada participante desde su fecha_inicio_programa. El cron ejecuta actualizarSemanasTodos() cada 6 horas para mantener sincronizado el campo semana_actual de todos los participantes activos.
### ActuacionStoService
ID: jaraba_andalucia_ei.actuacion_sto
Lógica de negocio para las actuaciones del itinerario. Calcula duración en minutos/horas, valida tipos de actuación, gestiona la relación entre actuaciones y contadores de horas del participante.
### AlertasNormativasService
ID: jaraba_andalucia_ei.alertas_normativas
Sistema proactivo de detección de riesgos de incumplimiento normativo. Evalúa todos los participantes activos y genera alertas con severidad (critical, high, medium, low) cuando detecta: horas insuficientes, DACI sin firmar, FSE+ incompleto, tiempo excesivo en una fase, etc. El resumen se muestra en el dashboard del coordinador.
## 6.2 Documentación y Expedientes
### DaciService
ID: jaraba_andalucia_ei.daci
Genera el Documento de Aceptación de Compromisos e Información (DACI) como PDF branded. Se invoca automáticamente al aprobar una solicitud (fase Acogida). El PDF se almacena como ExpedienteDocumento enlazado al participante.
### ReciboServicioService
ID: jaraba_andalucia_ei.recibo_servicio
Genera recibos de servicio para cada actuación del itinerario. Se dispara automáticamente al insertar una ActuacionSto (via hook_ENTITY_TYPE_insert). El recibo documenta la prestación del servicio con datos del participante, orientador, horario y contenido.
### ExpedienteService
ID: jaraba_andalucia_ei.expediente
Gestión integral del expediente documental del participante. Interactúa con el Legal Vault (jaraba_legal_vault) para almacenamiento encriptado y con el servicio de firma digital.
### ExpedienteCompletenessService
ID: jaraba_andalucia_ei.expediente_completeness
Evalúa el grado de completitud del expediente de un participante. Determina qué documentos obligatorios faltan según la fase actual y el carril del programa.
### DocumentoRevisionIaService
ID: jaraba_andalucia_ei.documento_revision_ia
Revisión automática de documentos mediante IA. Analiza documentos subidos al expediente para verificar completitud, detectar inconsistencias y sugerir correcciones.
### InformeProgresoPdfService
ID: jaraba_andalucia_ei.informe_progreso_pdf
Genera informes de progreso en PDF branded descargables por el participante desde su portal. Incluye resumen de horas, fase actual, logros y próximos pasos.
### HojaServicioMentoriaService
ID: jaraba_andalucia_ei.hoja_servicio_mentoria
Gestiona las hojas de servicio de sesiones de mentoría. Integra firma digital de participante y orientador.
## 6.3 Gestión Operativa
### CoordinadorHubService
ID: jaraba_andalucia_ei.coordinador_hub
Lógica de negocio del Hub del Coordinador. Gestiona la aprobación/rechazo de solicitudes, la creación automática de participantes (incluyendo generación de DACI, asignación de fecha de inicio y semana 0), y el cambio de fase vía FaseTransitionManager.
### SolicitudTriageService
ID: jaraba_andalucia_ei.solicitud_triage
Triaje automático de solicitudes mediante IA. Analiza los datos del candidato y sugiere una priorización y carril recomendado.
### StoExportService
ID: jaraba_andalucia_ei.sto_export
Exportación de datos para el Sistema Telemático de Orientación (STO) del SAE. Genera paquetes de datos en el formato requerido por el SEPE.
## 6.4 Mentoría y Aprendizaje
### AiMentorshipTracker
ID: jaraba_andalucia_ei.ai_mentorship_tracker
Tracker de sesiones de mentoría con el Tutor IA (Copilot). Registra horas, temas tratados y progreso. Integra el motor de dificultad adaptativa y la revisión de documentos por IA.
### HumanMentorshipTracker
ID: jaraba_andalucia_ei.human_mentorship_tracker
Tracker de sesiones de mentoría con mentor humano. Registra horas y sesiones realizadas.
### AdaptiveDifficultyEngine
ID: jaraba_andalucia_ei.adaptive_difficulty
Motor de dificultad adaptativa para las sesiones de mentoría IA. Ajusta el nivel de complejidad de las interacciones según el progreso y las respuestas del participante.
### MensajeriaIntegrationService
ID: jaraba_andalucia_ei.mensajeria_integration
Integración con el sistema de mensajería interna (jaraba_messaging). Permite comunicación directa entre participante y orientador.
## 6.5 Copilot e IA
### AndaluciaEiCopilotBridgeService
ID: jaraba_andalucia_ei.copilot_bridge
Puente entre el vertical Andalucía +ei y el sistema Copilot IA. Proporciona contexto del participante al agente de IA para personalizar las respuestas.
### AndaluciaEiCopilotContextProvider
ID: jaraba_andalucia_ei.copilot_context_provider
Proveedor de contexto específico del programa para el Copilot. Inyecta datos del participante (fase actual, horas, carril, expediente) en las conversaciones con IA.

# 7. Capa de Presentación: Controladores y Vistas
El módulo implementa 13 controladores que cubren todos los flujos de usuario: frontend público, portales de participante/orientador/coordinador, APIs REST, y páginas administrativas.
## 7.1 Controladores Frontend
## 7.2 Templates Twig
El módulo incluye 12 templates de página principales y 7 parciales reutilizables, todos siguiendo el patrón Zero Region (ZERO-REGION-001) con clean_content y clean_messages. Los parciales incluyen:
- _participante-hero.html.twig: Cabecera del portal con datos resumidos
- _participante-timeline.html.twig: Línea temporal de fases PIIL
- _participante-formacion.html.twig: Resumen de formación y horas
- _participante-expediente.html.twig: Estado del expediente documental
- _participante-logros.html.twig: Logros y badges conseguidos
- _participante-mensajeria.html.twig: Integración de mensajería
- _participante-acciones.html.twig: Acciones disponibles según fase

# 8. APIs REST
El módulo expone más de 20 endpoints REST bajo el prefijo /api/v1/andalucia-ei/. Todas las rutas requieren autenticación, permisos granulares, protección CSRF para métodos destructivos, y formato JSON obligatorio.

# 9. Sistema de Permisos y Seguridad
## 9.1 Permisos Granulares
El módulo define 18 permisos granulares organizados por entidad y operación CRUD. El permiso "administer andalucia ei" tiene restrict access: true y proporciona acceso completo.
## 9.2 Control de Acceso
Cada entidad tiene su propio AccessControlHandler dedicado que verifica: (1) permisos granulares por operación, (2) aislamiento multi-tenant (TENANT-ISOLATION-ACCESS-001), y (3) ownership para operaciones de edición/eliminación. Adicionalmente, ParticipanteAccessCheck verifica que el usuario actual es un participante activo del programa antes de permitir acceso a rutas protegidas como el portal personal.
## 9.3 Protección CSRF
Todas las rutas API con métodos destructivos (POST, DELETE) incluyen _csrf_request_header_token: TRUE. El JavaScript del frontend obtiene el token de /session/token y lo envía como header X-CSRF-Token en cada petición (regla CSRF-API-001).
## 9.4 Almacenamiento Seguro
Los documentos del expediente se almacenan encriptados en el Legal Vault (jaraba_legal_vault). Los datos personales sensibles (DNI, NIE) están protegidos por el sistema de guardrails PII (AI-GUARDRAILS-PII-001) que detecta patrones de DNI, NIE, IBAN ES, NIF/CIF y teléfonos +34 tanto en entrada como en salida de las interacciones con IA.

# 10. Inteligencia Artificial Integrada
El programa integra IA en tres niveles:
## 10.1 Tutor IA (Copilot)
Cada participante tiene acceso a un asistente de IA conversacional (Copilot) que actúa como tutor virtual. El Copilot conoce la fase actual del participante, su carril, horas acumuladas y estado del expediente gracias a AndaluciaEiCopilotContextProvider. Las horas de interacción con el Copilot se registran como horas_mentoria_ia en el perfil del participante.
## 10.2 Motor de Dificultad Adaptativa
AdaptiveDifficultyEngine ajusta dinámicamente la complejidad de las interacciones de mentoría IA según el perfil y progreso del participante. Evalúa respuestas previas, tiempo de respuesta y resultados para personalizar el nivel de profundidad.
## 10.3 Revisión de Documentos por IA
DocumentoRevisionIaService analiza automáticamente los documentos subidos al expediente. Verifica completitud, detecta inconsistencias en formularios y sugiere correcciones, reduciendo la carga administrativa del equipo técnico.
## 10.4 Triaje Automático de Solicitudes
SolicitudTriageService analiza las solicitudes entrantes mediante IA para sugerir priorización y carril recomendado, optimizando el proceso de admisión.

# 11. Integración Cross-Vertical
Andalucía +ei se integra con el ecosistema de 10 verticales de la plataforma a través de bridges cross-verticales gestionados por AndaluciaEiCrossVerticalBridgeService (ubicado en ecosistema_jaraba_core). Los 4 bridges evalúan condiciones del participante para recomendar otros verticales:
Máximo 2 bridges activos simultáneamente por participante, con dismiss tracking vía State API. Los bridges se evalúan en el dashboard del participante y en las sesiones de mentoría.
## 11.1 Integración con Otros Módulos

# 12. Indicadores FSE+ y Cumplimiento Normativo
La entidad IndicadorFsePlus implementa los indicadores obligatorios del Fondo Social Europeo Plus, alineados con el Reglamento (UE) 2021/1057 del Parlamento Europeo y del Consejo. Los indicadores se recogen en tres momentos clave del itinerario y permiten la justificación del programa ante las instituciones europeas.
## 12.1 Indicadores de Entrada
Se recogen al incorporarse al programa (fase Diagnóstico). Incluyen: situación laboral (5 categorías), nivel educativo ISCED (9 niveles), discapacidad (tipo y grado), nacionalidad y país de origen, composición del hogar (unipersonal, hijos a cargo), zona de residencia (urbana/rural/intermedia), situación de sinhogarismo y pertenencia a comunidad marginada.
## 12.2 Indicadores de Resultado
Se recogen al finalizar la intervención (fase Baja) y a los 6 meses de seguimiento. Incluyen: situación laboral alcanzada, tipo de contrato obtenido (indefinido/temporal/sin contrato), cualificación obtenida (5 tipos), mejora de situación e inclusión social. Estos indicadores permiten medir el impacto real del programa.
## 12.3 Exportación para Justificación
StoExportService genera paquetes de datos en el formato requerido por el SEPE y los órganos de justificación FSE+. La exportación incluye datos anonimizados de participantes, actuaciones realizadas e indicadores de resultado.
## 12.4 Alertas Normativas
AlertasNormativasService monitoriza continuamente el cumplimiento normativo de todos los participantes activos. Detecta riesgos como: horas insuficientes respecto al plazo transcurrido, documentación obligatoria pendiente, DACI sin firmar, participantes estancados en una fase, o indicadores FSE+ incompletos. Las alertas se clasifican por severidad (critical, high, medium, low) y se muestran en tiempo real en el dashboard del coordinador.

# 13. Infraestructura de Testing
El módulo cuenta con una cobertura de testing exhaustiva que incluye tests unitarios, de integración (Kernel) y funcionales. Todos los tests se ejecutan en CI con PHP 8.4 y MariaDB 10.11.
Los tests siguen las convenciones del proyecto: KERNEL-TEST-DEPS-001 para dependencias explícitas, MOCK-DYNPROP-001 para compatibilidad PHP 8.4, y TEST-CACHE-001 para mocks de entidades con cache.

# 14. Diagrama de Arquitectura
A continuación se presenta la arquitectura lógica del módulo jaraba_andalucia_ei y sus relaciones con el ecosistema de la plataforma.
+─────────────────────────────────────────────────────────────────+
|                    FRONTEND (Usuario Final)                     |
|  Landing ─> Solicitud ─> Portal Participante ─> Expediente     |
|  Dashboard Orientador    Dashboard Coordinador    Guía          |
+──────────────────────┬──────────────────────────────────────────+
                       │
+──────────────────────┴──────────────────────────────────────────+
|                   CONTROLADORES (13)                            |
|  AndaluciaEiController, ParticipantePortalController,           |
|  CoordinadorDashboardController, AndaluciaEiApiController,      |
|  CoordinadorHubApiController, SolicitudEiController, ...        |
+──────────────────────┬──────────────────────────────────────────+
                       │
+──────────────────────┴──────────────────────────────────────────+
|                   SERVICIOS DE NEGOCIO (21)                     |
|  FaseTransitionManager  │  CalendarioProgramaService            |
|  AlertasNormativasService│  ActuacionStoService                  |
|  DaciService            │  ReciboServicioService                |
|  ExpedienteService      │  ExpedienteCompletenessService        |
|  CoordinadorHubService  │  SolicitudTriageService               |
|  AiMentorshipTracker   │  HumanMentorshipTracker               |
|  AdaptiveDifficultyEngine│ AndaluciaEiCopilotBridgeService      |
|  StoExportService       │  InformeProgresoPdfService            |
+──────────────────────┬──────────────────────────────────────────+
                       │
+──────────────────────┴──────────────────────────────────────────+
|                   ENTIDADES (6)                                 |
|  ProgramaParticipanteEi │ SolicitudEi │ ActuacionSto            |
|  IndicadorFsePlus       │ InsercionLaboral │ ExpedienteDocumento|
+──────────────────────┬──────────────────────────────────────────+
                       │
+──────────────────────┴──────────────────────────────────────────+
|              MÓDULOS DEL ECOSISTEMA                             |
|  ecosistema_jaraba_core  │  jaraba_lms                          |
|  jaraba_mentoring        │  jaraba_copilot_v2                   |
|  jaraba_sepe_teleformacion│ jaraba_legal_vault                  |
|  jaraba_messaging        │  jaraba_ai_agents                    |
+─────────────────────────────────────────────────────────────────+

# 15. Glosario


—— Fin del Documento ——
Documento generado automáticamente por Jaraba Impact Platform.
© 2026 Plataforma de Ecosistemas Digitales S.L. Todos los derechos reservados.