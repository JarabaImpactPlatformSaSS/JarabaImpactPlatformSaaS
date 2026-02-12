
DOCUMENTO TÉCNICO DE IMPLEMENTACIÓN

PROGRAMA ANDALUCÍA +ei
Integración en Jaraba Impact Platform

"Emprendimiento Aumentado: De la Idea a la Facturación con IA y Estrategia Real"

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica Definitiva
Código:	45_Andalucia_ei_Implementacion_v1
Dependencias:	07_MultiTenant, 08_LMS, 31_Mentoring, FOC
 
1. Resumen Ejecutivo y Gap Analysis
Este documento especifica la implementación técnica completa del Programa Andalucía +ei en la Jaraba Impact Platform. El análisis de la documentación existente, la normativa PIIL y el Manual STO revela un ecosistema sólido con gaps específicos que requieren desarrollo adicional para garantizar el cumplimiento normativo y la operatividad completa.
1.1 Análisis de Cobertura Actual
La documentación técnica del proyecto cubre el 85% de los requisitos funcionales. Los módulos Core (01-07), Empleabilidad (08-24) y Emprendimiento (25-44) proporcionan la base arquitectónica necesaria. Sin embargo, la integración específica con el STO y las particularidades del programa PIIL requieren especificaciones adicionales.
1.2 Gaps Críticos Identificados
ID	Gap Identificado	Prioridad	Estado
GAP-01	Integración bidireccional STO-Jaraba para sincronización de participantes, acciones y fases	CRÍTICA	Pendiente
GAP-02	Sistema de cómputo de horas 50/50 (Formación/Mentoría) con validación STO	CRÍTICA	Pendiente
GAP-03	Gestión de transición Fase Atención → Fase Inserción según normativa PIIL	CRÍTICA	Pendiente
GAP-04	Entidad específica programa_participante_ei con campos STO requeridos	ALTA	Pendiente
GAP-05	Cómputo y validación de 25h de Mentoría IA (Tutor Jaraba)	ALTA	Pendiente
GAP-06	Sistema de Recibí del incentivo económico (€528)	ALTA	Pendiente
GAP-07	Módulo jaraba_learning específico para programa 100h	MEDIA	Parcial
GAP-08	Club Alumni + Demo Day (post-programa)	MEDIA	Pendiente
 
2. Arquitectura del Tenant Andalucía +ei
2.1 Configuración del Group (Multi-Tenant)
El programa Andalucía +ei se instancia como un Grupo híbrido que hereda capacidades tanto de la Vertical de Empleabilidad como de Emprendimiento. Esta configuración permite a los participantes transitar entre los "carriles" Impulso Digital (empleabilidad) y Acelera Pro (emprendimiento) según su evolución.
Parámetro	Valor/Configuración
group_type	program_ei (hereda: vertical_empleabilidad, vertical_emprendimiento)
label	Andalucía +ei 2026
field_expediente_sae	SC/ICJ/0050/2024 (vinculado a Ficha Técnica STO)
field_provincias	[Cádiz, Granada, Málaga, Sevilla] (según expediente)
field_colectivos_destino	Jóvenes 18-29 Garantía Juvenil (según convocatoria)
field_objetivo_participantes	640 (100 Cádiz + 200 Granada + 170 Málaga + 170 Sevilla)
field_objetivo_inserciones	256 (40% del total de participantes)
field_fecha_inicio	10/10/2024 (según Ficha Técnica)
field_fecha_fin	12/12/2026
field_duracion_meses	26 meses
field_importe_subvencion	€900,000 (según presupuesto aprobado)
2.2 Roles Específicos del Programa
El programa requiere roles adicionales a los definidos en 04_Core_Permisos_RBAC para cumplir con la estructura organizativa del STO:
Rol	Mapeo STO	Permisos Clave
ei_representante	Representante de la Entidad	Firmar fichas técnicas, ver FOC completo
ei_coordinador	Personal Coordinador	Gestionar fichas técnicas, supervisión
ei_tecnico	Personal Técnico	Alta participantes, registrar acciones, orientación
ei_participante	Persona Participante	Acceso LMS, mentoría, evidencias
ei_mentor	Tutor/Mentor Humano	Gestionar sesiones, notas, evaluaciones
 
3. Entidad: programa_participante_ei
Esta entidad extiende el esquema base de group_membership para incluir los campos específicos requeridos por el STO y la gestión de fases PIIL.
3.1 Esquema de Base de Datos
Campo	Tipo	Descripción	Restricción
id	INT	PK autoincremental	NOT NULL
user_id	INT	FK users.uid	NOT NULL
group_id	INT	FK groups.id (Andalucía +ei)	NOT NULL
dni_nie	VARCHAR(12)	Documento identificativo STO	UNIQUE, NOT NULL
colectivo	VARCHAR(32)	jovenes | mayores_45 | larga_duracion	NOT NULL
provincia_participacion	VARCHAR(32)	Provincia de inscripción STO	NOT NULL
fecha_alta_sto	DATETIME	Fecha registro en STO (inmutable)	NOT NULL, UTC
fase_actual	VARCHAR(16)	atencion | insercion | baja	DEFAULT atencion
horas_orientacion_ind	DECIMAL(5,2)	Horas orientación individual acumuladas	DEFAULT 0
horas_orientacion_grup	DECIMAL(5,2)	Horas orientación grupal acumuladas	DEFAULT 0
horas_formacion	DECIMAL(5,2)	Horas formación acumuladas	DEFAULT 0
horas_mentoria_ia	DECIMAL(5,2)	Horas con Tutor IA (conversaciones)	DEFAULT 0
horas_mentoria_humana	DECIMAL(5,2)	Horas con mentor humano	DEFAULT 0
carril	VARCHAR(16)	impulso_digital | acelera_pro | hibrido	NULLABLE
incentivo_recibido	BOOLEAN	€528 recibido y firmado recibí	DEFAULT FALSE
tipo_insercion	VARCHAR(32)	cuenta_ajena | cuenta_propia | agrario	NULLABLE
fecha_insercion	DATETIME	Fecha de inserción laboral verificada	NULLABLE, UTC
sto_sync_status	VARCHAR(16)	pending | synced | error	DEFAULT pending
 
4. Sistema de Cómputo de Horas 50/50
El programa requiere tracking preciso de 100 horas distribuidas en 50h de formación y 50h de mentoría/acción. El sistema debe generar evidencias válidas para la justificación STO.
4.1 Distribución de Horas por Tipo
Tipo de Actividad	Horas	Categoría STO	Mecanismo Tracking
Píldoras Asíncronas (LMS)	20h	FORMACIÓN	xAPI statements + completion
Talleres Síncronos (Live)	30h	FORMACIÓN	Check-in QR + geolocalización
Tutor Jaraba IA (24/7)	25h	ORIENTACIÓN	Logs conversación + tiempo sesión
Mentoría Humana (1:1 y grupal)	25h	ORIENTACIÓN	mentoring_session + validación
TOTAL PROGRAMA	100h	-	-
4.2 Algoritmo de Cómputo de Mentoría IA
Las 25 horas de mentoría IA se computan mediante el análisis de las interacciones con el AI Business Copilot (44_Emprendimiento_AI_Business_Copilot). El algoritmo considera:
1.	Tiempo de sesión activa: Diferencia entre primera y última interacción de una conversación, con timeout de inactividad de 15 minutos.
2.	Profundidad de interacción: Mínimo 3 intercambios (query-response) para contabilizar como sesión válida.
3.	Calidad del engagement: Sesiones donde el usuario ejecuta acciones sugeridas (deep links) reciben multiplicador 1.2x.
4.	Tope diario: Máximo 2 horas contabilizables por día para evitar gaming del sistema.
4.3 Requisitos Mínimos Fase de Atención (STO)
Según la normativa PIIL, para completar la Fase de Atención y transitar a Fase de Inserción, el participante debe acumular:
Requisito STO	Mínimo	Mapeo Programa
Orientación (individual o grupal)	10 horas	25h IA + 25h Humana = 50h (excede)
Formación	50 horas	20h async + 30h sync = 50h (cumple)
 
5. Integración Bidireccional con STO
La integración con el Servicio Telemático de Orientación (STO) de la Junta de Andalucía es crítica y no negociable. El sistema debe sincronizar altas de participantes, registro de acciones y transiciones de fase.
5.1 Arquitectura de Sincronización
Dado que el STO no expone una API pública documentada, la estrategia de integración adopta un modelo de exportación preparada + intervención manual mínima:
•	Jaraba → STO: Generación de paquetes CSV/PDF con datos pre-validados para carga manual o via RPA (Robot Process Automation).
•	STO → Jaraba: Importación de estado de validación mediante webhook manual o archivo de confirmación.
•	Reconciliación: Proceso diario de comparación de estados para detectar discrepancias.
5.2 Flujos de Sincronización
5.2.1 Alta de Participante
Proceso híbrido que inicia en Jaraba y se completa en STO:
5.	Pre-validación en Jaraba: Sistema verifica requisitos básicos (edad, documento válido, provincia).
6.	Generación de paquete: PDF con Documento de Participación + datos estructurados en CSV.
7.	Registro en STO: Técnico usa credenciales STO para dar alta con DNI/NIE.
8.	Confirmación en Jaraba: Técnico marca alta_sto_confirmada = TRUE, activa acceso LMS.
5.2.2 Registro de Acciones
El STO requiere registro de 3 tipos de acciones. Jaraba genera los datos y el técnico los carga:
Tipo Acción STO	Fuente de Datos Jaraba	Formato Exportación
Orientación Individual	mentoring_session (tipo: individual) + AI conversation logs	CSV + PDF resumen
Orientación Grupal	Círculos de Responsabilidad + talleres grupales síncronos	CSV + lista asistencia
Formación	enrollment completions (LMS) + attendance records (talleres)	CSV + certificados
 
6. Flujos de Automatización ECA Específicos
Los siguientes flujos ECA complementan los definidos en 06_Core_Flujos_ECA para la gestión específica del programa Andalucía +ei:
6.1 ECA-EI-001: Transición Atención → Inserción
Trigger:	programa_participante_ei.horas_orientacion >= 10 AND horas_formacion >= 50
Condición:	fase_actual = 'atencion' AND sto_sync_status = 'synced'
Acciones:	1. Cambiar fase_actual = 'insercion'. 2. Notificar al técnico. 3. Habilitar registro de inserciones. 4. Generar reporte de transición para STO.
6.2 ECA-EI-002: Registro de Inserción Laboral
Trigger:	Técnico marca tipo_insercion y fecha_insercion
Condición:	fase_actual = 'insercion' AND evidencia_contrato IS NOT NULL
Acciones:	1. Crear financial_transaction tipo 'persona_insertada' (€2,500). 2. Actualizar métricas FOC. 3. Generar informe para STO. 4. Celebración badge + Alumni.
6.3 ECA-EI-003: Gestión del Incentivo €528
Trigger:	fase_actual cambia a 'insercion' (completó atención)
Condición:	incentivo_recibido = FALSE AND documentacion_completa = TRUE
Acciones:	1. Generar Webform de Recibí con firma digital. 2. Notificar al participante. 3. Tras firma: incentivo_recibido = TRUE. 4. Crear financial_transaction.
 
7. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Entidad programa_participante_ei. Configuración Tenant Group. Roles específicos.	07_MultiTenant
Sprint 2	Semana 3-4	Sistema cómputo horas. Tracking xAPI + AI sessions. Dashboard técnico.	Sprint 1, 08_LMS
Sprint 3	Semana 5-6	Flujos ECA (transición fases). Módulo exportación STO. Webforms asistencia.	Sprint 2, 06_ECA
Sprint 4	Semana 7-8	Gestión incentivos (€528). Sistema recibí digital. Integración FOC-Grant.	Sprint 3, FOC
Sprint 5	Semana 9-10	Club Alumni. Demo Day workflow. Métricas impacto programa. QA completo.	Sprint 4, 40_Membership
 
8. Métricas de Éxito e Indicadores
KPI	Target	Medición
Tasa de inserción laboral	>= 40%	Insertados / Total Atendidos
Sincronización STO	100%	Registros sin discrepancia
Completitud Fase Atención	>= 85%	% que completan 60h mínimas
Engagement Mentoría IA	>= 15h/usuario	Promedio horas_mentoria_ia
Grant Burn Rate	< 1.1	(% gasto) / (% tiempo transcurrido)
NPS del programa	>= 50	Encuesta satisfacción graduados

9. Conclusiones y Recomendaciones
9.1 Cambios Requeridos al Ecosistema
La documentación técnica existente (01-44) proporciona una base sólida. Los cambios requeridos son:
•	Nueva entidad: programa_participante_ei extiende group_membership con campos STO específicos.
•	Nuevos flujos ECA: ECA-EI-001 a ECA-EI-003 para gestión de fases y transiciones.
•	Algoritmo cómputo IA: Extender 44_AI_Business_Copilot para tracking de horas.
•	Módulo exportación STO: Nuevo módulo jaraba_sto_export para generación de paquetes.
•	Dashboard FOC: Extensión con métricas Grant Burn Rate y justificación PIIL.
9.2 Riesgos Identificados
•	Integración STO: Sin API pública, dependemos de exportación manual. Mitigación: Diseñar RPA futuro.
•	Validación Mentoría IA: El SAE podría cuestionar horas IA como 'orientación'. Mitigación: Logs detallados + supervisión humana.
•	Complejidad híbrida: Gestionar dos verticales en un tenant añade complejidad. Mitigación: UX simplificada por carril.

--- Fin del Documento ---
45_Andalucia_ei_Implementacion_v1.docx | Jaraba Impact Platform | Enero 2026
