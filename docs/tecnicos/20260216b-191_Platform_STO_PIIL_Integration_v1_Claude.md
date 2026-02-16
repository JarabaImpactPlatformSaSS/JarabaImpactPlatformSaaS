
STO/PIIL INTEGRATION
Integracion con Servicio Telematico de Orientacion y Programas PIIL
Nivel de Madurez: N2
JARABA IMPACT PLATFORM
Especificacion Tecnica para Implementacion
Version:	1.0
Fecha:	Febrero 2026
Codigo:	191_Platform_STO_PIIL_Integration_v1
Estado:	Especificacion para EDI Google Antigravity
Nivel Madurez:	N2
Compliance:	GDPR, LOPD-GDD, ENS, ISO 27001
 
1. Resumen Ejecutivo
Integracion con el Servicio Telematico de Orientacion (STO) del SAE y los programas PIIL (Proyectos Integrales para la Insercion Laboral). Incluye generacion automatizada de Fichas Tecnicas, sincronizacion de participantes, reporting FUNDAE/FSE+, justificacion economica automatizada y API con sistemas de la Junta de Andalucia.

1.1 Contexto Institucional
Programa	Entidad	Objetivo	Integracion Requerida
STO (Servicio Telematico Orientacion)	SAE	Orientacion laboral telematica	Ficha tecnica + registros
PIIL 2024-2026	SAE/CEETA	Insercion laboral integral	Participantes + justificacion
FUNDAE	Ministerio Trabajo	Formacion continua bonificada	Comunicaciones + certificados
FSE+ Andalucia	UE + Junta	Fondos europeos empleo	Indicadores impacto
 
2. Modelo de Datos
2.1 institutional_program
Campo	Tipo	Descripcion
id	UUID	Identificador
program_type	ENUM	sto|piil|fundae|fse_plus
program_code	VARCHAR(50)	Codigo oficial del programa
name	VARCHAR(255)	Nombre del programa
funding_entity	VARCHAR(255)	Entidad financiadora
start_date	DATE	Fecha inicio
end_date	DATE	Fecha fin
budget_total	DECIMAL(12,2)	Presupuesto total
budget_executed	DECIMAL(12,2)	Presupuesto ejecutado
participants_target	INT	Participantes objetivo
participants_actual	INT	Participantes reales
status	ENUM	draft|active|reporting|closed|audited
reporting_deadlines	JSON	Plazos de justificacion
tenant_id	UUID FK	Tenant operador

2.2 program_participant
Campo	Tipo	Descripcion
id	UUID	Identificador
program_id	UUID FK	Programa
user_id	UUID FK	Usuario participante
enrollment_date	DATE	Fecha alta
exit_date	DATE	Fecha baja
exit_reason	ENUM	completed|employment|dropout|other
sto_ficha_id	VARCHAR(50)	ID ficha STO
employment_outcome	ENUM	employed|self_employed|training|unemployed
employment_date	DATE	Fecha insercion laboral
hours_orientation	DECIMAL(6,2)	Horas orientacion recibidas
hours_training	DECIMAL(6,2)	Horas formacion
certifications_obtained	JSON	Certificaciones obtenidas
 
3. Ficha Tecnica STO Automatizada
3.1 Campos de la Ficha
•	Datos identificativos del participante
•	Diagnostico de empleabilidad (generado desde doc 25)
•	Itinerario personalizado de insercion (generado desde learning paths)
•	Acciones de orientacion realizadas (logs del sistema)
•	Resultados: insercion laboral, formacion completada, certificaciones

3.2 Generacion Automatica
•	Trigger: Participante completa etapa del itinerario o sale del programa
•	Datos: Se extraen automaticamente de la actividad del usuario en la plataforma
•	Formato: PDF con estructura requerida por SAE
•	Firma: Digital PAdES conforme al doc 89
•	Entrega: Upload automatico al portal STO o email al coordinador SAE
 
4. Reporting FUNDAE/FSE+
4.1 Indicadores Requeridos
Indicador	Fuente	Calculo	Periodicidad
Participantes atendidos	program_participant	COUNT por periodo	Mensual
Tasa de insercion	employment_outcome	% employed / total	Trimestral
Horas formacion impartidas	hours_training	SUM por programa	Mensual
Certificaciones emitidas	certifications_obtained	COUNT por tipo	Mensual
Coste por participante	budget_executed / participants	Division	Trimestral
Satisfaccion participantes	NPS surveys	Promedio NPS	Semestral
 
5. Estimacion de Implementacion
Componente	Horas	Coste EUR	Prioridad
Modelo institutional_program	6-8h	270-360	ALTA
Ficha Tecnica STO Generator	10-12h	450-540	CRITICA
Participant sync + tracking	8-10h	360-450	CRITICA
FUNDAE reporting module	8-10h	360-450	ALTA
FSE+ impact indicators	6-8h	270-360	MEDIA
Justificacion economica auto	6-8h	270-360	ALTA
TOTAL	44-56h	1,980-2,520	N2

--- Fin del Documento ---
Jaraba Impact Platform | Especificacion Tecnica v1.0 | Febrero 2026
