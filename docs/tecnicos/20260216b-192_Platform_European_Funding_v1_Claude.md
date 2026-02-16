
EUROPEAN FUNDING MODULE
Gestion de Fondos Europeos, Subvenciones y Convocatorias
Nivel de Madurez: N2
JARABA IMPACT PLATFORM
Especificacion Tecnica para Implementacion
Version:	1.0
Fecha:	Febrero 2026
Codigo:	192_Platform_European_Funding_v1
Estado:	Especificacion para EDI Google Antigravity
Nivel Madurez:	N2
Compliance:	GDPR, LOPD-GDD, ENS, ISO 27001
 
1. Resumen Ejecutivo
Modulo de gestion de fondos europeos y subvenciones: tracking de convocatorias (Kit Digital, PRTR, FSE+), generacion automatica de memorias tecnicas, control presupuestario por proyecto, reporting de indicadores de impacto y alertas de plazos.

1.1 Convocatorias Relevantes
Convocatoria	Entidad	Importe	Plazo	Estado
Kit Digital Segmento I	Red.es	Hasta 12,000 EUR	Abierto	Activo
Kit Digital Segmento II	Red.es	Hasta 6,000 EUR	Abierto	Activo
PRTR Digitalizacion PYME	MINECO	Variable	Periodico	Activo
FSE+ Empleo Joven	UE/SEPE	Variable	Convocatoria	Activo
Programa Andalucia +ei	Junta Andalucia	Variable	Anual	Activo
Erasmus+ KA2	UE	Hasta 400,000 EUR	Anual	Activo
 
2. Modelo de Datos
2.1 funding_opportunity
Campo	Tipo	Descripcion
id	UUID	Identificador
name	VARCHAR(255)	Nombre convocatoria
funding_body	VARCHAR(255)	Organismo convocante
program	VARCHAR(100)	Programa (Kit Digital, FSE+, etc.)
max_amount	DECIMAL(12,2)	Importe maximo
deadline	TIMESTAMP	Plazo solicitud
requirements	JSON	Requisitos de elegibilidad
documentation_required	JSON	Documentacion requerida
status	ENUM	upcoming|open|closed|resolved
url	VARCHAR(500)	URL oficial de la convocatoria
alert_days_before	INT	Dias antes para alertar

2.2 funding_application
Campo	Tipo	Descripcion
id	UUID	Identificador
opportunity_id	UUID FK	Convocatoria
tenant_id	UUID FK	Tenant solicitante
status	ENUM	draft|submitted|approved|rejected|justifying|closed
amount_requested	DECIMAL(12,2)	Importe solicitado
amount_approved	DECIMAL(12,2)	Importe aprobado
submission_date	TIMESTAMP	Fecha envio
resolution_date	TIMESTAMP	Fecha resolucion
technical_report	JSON	Memoria tecnica generada
budget_breakdown	JSON	Desglose presupuestario
justification_docs	JSON	Documentos de justificacion
impact_indicators	JSON	Indicadores de impacto
next_deadline	TIMESTAMP	Proximo plazo
 
3. Generacion Automatica de Memorias Tecnicas
•	Templates por tipo de convocatoria pre-configurados
•	Auto-fill de datos del tenant (nombre, NIF, actividad, facturacion)
•	Descripcion del proyecto generada via IA (Claude) con grounding en datos reales
•	Presupuesto desglosado desde datos de la plataforma
•	Indicadores de impacto calculados desde metricas reales
•	Export a PDF con formato requerido por cada convocatoria
 
4. Estimacion de Implementacion
Componente	Horas	Coste EUR	Prioridad
Modelo funding_opportunity + tracking	8-10h	360-450	ALTA
Modelo funding_application	6-8h	270-360	ALTA
Memoria tecnica generator	10-12h	450-540	ALTA
Alertas de plazos	4-5h	180-225	MEDIA
Dashboard de fondos	6-8h	270-360	MEDIA
Control presupuestario	6-8h	270-360	MEDIA
TOTAL	40-51h	1,800-2,295	N2

--- Fin del Documento ---
Jaraba Impact Platform | Especificacion Tecnica v1.0 | Febrero 2026
