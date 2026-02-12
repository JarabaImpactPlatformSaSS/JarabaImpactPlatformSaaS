JARABA_CRM
Pipeline de Ventas B2B
Módulo de CRM Nativo para Gestión Comercial
Versión:	1.0
Fecha:	Enero 2026
Código:	150_Marketing_jaraba_crm_Pipeline_v1
Estado:	Especificación Técnica para Implementación
Horas Estimadas:	40-50 horas
Dependencias:	jaraba_core, jaraba_tenant, jaraba_foc, ECA
1. Resumen Ejecutivo
El módulo jaraba_crm proporciona un sistema CRM nativo para gestión del pipeline de ventas B2B, seguimiento de oportunidades, gestión de contactos empresariales y forecasting comercial. Diseñado para reemplazar herramientas externas como HubSpot CRM, Pipedrive o Salesforce Essentials, ofreciendo integración nativa con el ecosistema Jaraba y ahorro de €600-3,000/año por tenant.
1.1 Funcionalidades Principales
•	Pipeline visual de oportunidades con etapas configurables por tenant
•	Gestión de contactos y empresas con enriquecimiento automático
•	Registro de actividades (llamadas, emails, reuniones)
•	Forecasting de ingresos con probabilidades por etapa
•	Integración con FOC para métricas de ventas y conversión
•	Automatizaciones ECA para seguimiento y alertas
1.2 Herramientas que Reemplaza
Herramienta	Coste Anual	Ahorro
HubSpot CRM Starter	€540/año	100%
Pipedrive Essential	€178/año	100%
Salesforce Essentials	€300/año	100%
 
2. Arquitectura Técnica
2.1 Stack Tecnológico
Componente	Tecnología
Backend	Drupal 11 Custom Entities + Content Moderation
Frontend Pipeline	React Kanban Board (drag & drop nativo)
Persistencia	MySQL 8.0 con índices optimizados para búsqueda
Cache	Redis para pipeline view y contadores
API	REST JSON:API + endpoints custom para pipeline
Automatización	ECA (Event-Condition-Action) module
Integración FOC	Hooks para actualización de métricas en tiempo real
2.2 Modelo de Datos Conceptual
El módulo gestiona cuatro entidades principales interrelacionadas: Company (empresas), Contact (contactos asociados a empresas), Opportunity (oportunidades de venta) y Activity (registro de interacciones). Todas las entidades soportan multi-tenancy a través del campo tenant_id con aislamiento de datos garantizado.
3. Esquema de Base de Datos
3.1 Entidad: crm_company
Almacena información de empresas/cuentas del pipeline B2B.
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno	PRIMARY KEY, AUTO_INCREMENT
uuid	VARCHAR(36)	UUID público	UNIQUE, NOT NULL, INDEX
tenant_id	INT	Tenant propietario	FK tenants.id, NOT NULL, INDEX
name	VARCHAR(255)	Nombre de la empresa	NOT NULL, INDEX
domain	VARCHAR(255)	Dominio web	NULLABLE, UNIQUE per tenant
industry	VARCHAR(100)	Sector/industria	NULLABLE, INDEX
company_size	VARCHAR(32)	Tamaño empresa	ENUM: 1-10|11-50|51-200|201-1000|1000+
annual_revenue	DECIMAL(15,2)	Facturación anual €	NULLABLE
phone	VARCHAR(32)	Teléfono principal	NULLABLE
email	VARCHAR(255)	Email corporativo	NULLABLE
website	VARCHAR(500)	URL sitio web	NULLABLE
address_line1	VARCHAR(255)	Dirección línea 1	NULLABLE
address_line2	VARCHAR(255)	Dirección línea 2	NULLABLE
city	VARCHAR(100)	Ciudad	NULLABLE, INDEX
province	VARCHAR(100)	Provincia	NULLABLE
postal_code	VARCHAR(16)	Código postal	NULLABLE
country	VARCHAR(2)	Código país ISO	DEFAULT 'ES'
linkedin_url	VARCHAR(500)	Perfil LinkedIn	NULLABLE
cif_nif	VARCHAR(20)	CIF/NIF empresa	NULLABLE
owner_id	INT	Comercial asignado	FK users.uid, NULLABLE
lead_source	VARCHAR(64)	Origen del lead	ENUM: web|referral|event|cold|inbound
status	VARCHAR(32)	Estado cuenta	ENUM: active|inactive|prospect
tags	JSON	Etiquetas libres	DEFAULT '[]'
custom_fields	JSON	Campos personalizados	DEFAULT '{}'
notes	TEXT	Notas internas	NULLABLE
created_at	DATETIME	Fecha creación	NOT NULL, DEFAULT CURRENT_TIMESTAMP
updated_at	DATETIME	Última modificación	NOT NULL, ON UPDATE CURRENT_TIMESTAMP
 
3.2 Entidad: crm_contact
Contactos individuales asociados a empresas.
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno	PRIMARY KEY
uuid	VARCHAR(36)	UUID público	UNIQUE, NOT NULL
tenant_id	INT	Tenant propietario	FK tenants.id, NOT NULL, INDEX
company_id	INT	Empresa asociada	FK crm_company.id, INDEX
first_name	VARCHAR(100)	Nombre	NOT NULL
last_name	VARCHAR(100)	Apellidos	NOT NULL
email	VARCHAR(255)	Email principal	NOT NULL, INDEX
phone	VARCHAR(32)	Teléfono	NULLABLE
mobile	VARCHAR(32)	Móvil	NULLABLE
job_title	VARCHAR(150)	Cargo/puesto	NULLABLE
department	VARCHAR(100)	Departamento	NULLABLE
linkedin_url	VARCHAR(500)	Perfil LinkedIn	NULLABLE
is_decision_maker	BOOLEAN	¿Toma decisiones?	DEFAULT FALSE
owner_id	INT	Comercial asignado	FK users.uid, NULLABLE
lead_status	VARCHAR(32)	Estado del lead	ENUM: new|contacted|qualified|unqualified
lead_score	INT	Puntuación lead	DEFAULT 0, RANGE 0-100
gdpr_consent	BOOLEAN	Consentimiento GDPR	DEFAULT FALSE
gdpr_consent_date	DATETIME	Fecha consentimiento	NULLABLE
email_opt_out	BOOLEAN	Opt-out emails	DEFAULT FALSE
tags	JSON	Etiquetas	DEFAULT '[]'
custom_fields	JSON	Campos custom	DEFAULT '{}'
last_activity_at	DATETIME	Última actividad	NULLABLE, INDEX
created_at	DATETIME	Fecha creación	NOT NULL
updated_at	DATETIME	Última modificación	NOT NULL
 
3.3 Entidad: crm_opportunity
Oportunidades de venta en el pipeline comercial.
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno	PRIMARY KEY
uuid	VARCHAR(36)	UUID público	UNIQUE, NOT NULL
tenant_id	INT	Tenant propietario	FK tenants.id, NOT NULL, INDEX
title	VARCHAR(255)	Título oportunidad	NOT NULL
company_id	INT	Empresa cliente	FK crm_company.id, INDEX
contact_id	INT	Contacto principal	FK crm_contact.id, NULLABLE
owner_id	INT	Comercial responsable	FK users.uid, NOT NULL, INDEX
stage	VARCHAR(64)	Etapa del pipeline	FK crm_pipeline_stage.id, INDEX
value	DECIMAL(15,2)	Valor estimado €	NOT NULL, DEFAULT 0
currency	VARCHAR(3)	Moneda ISO	DEFAULT 'EUR'
probability	INT	% probabilidad cierre	RANGE 0-100, DEFAULT 10
weighted_value	DECIMAL(15,2)	Valor ponderado	COMPUTED: value * probability/100
expected_close	DATE	Fecha cierre esperado	NULLABLE, INDEX
actual_close	DATE	Fecha cierre real	NULLABLE
won_lost_reason	VARCHAR(255)	Motivo ganado/perdido	NULLABLE
product_interest	JSON	Productos de interés	DEFAULT '[]'
competitors	JSON	Competidores	DEFAULT '[]'
lead_source	VARCHAR(64)	Origen	ENUM: web|referral|event|cold|inbound
status	VARCHAR(32)	Estado	ENUM: open|won|lost|abandoned
priority	VARCHAR(16)	Prioridad	ENUM: low|medium|high|critical
next_action	VARCHAR(500)	Próxima acción	NULLABLE
next_action_date	DATE	Fecha próx. acción	NULLABLE, INDEX
description	TEXT	Descripción	NULLABLE
notes	TEXT	Notas internas	NULLABLE
custom_fields	JSON	Campos custom	DEFAULT '{}'
created_at	DATETIME	Fecha creación	NOT NULL, INDEX
updated_at	DATETIME	Última modificación	NOT NULL
stage_changed_at	DATETIME	Último cambio etapa	NULLABLE
 
3.4 Entidad: crm_activity
Registro de actividades e interacciones con contactos y oportunidades.
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno	PRIMARY KEY
uuid	VARCHAR(36)	UUID público	UNIQUE, NOT NULL
tenant_id	INT	Tenant propietario	FK tenants.id, NOT NULL, INDEX
activity_type	VARCHAR(32)	Tipo de actividad	ENUM: call|email|meeting|note|task
subject	VARCHAR(255)	Asunto	NOT NULL
description	TEXT	Descripción detallada	NULLABLE
company_id	INT	Empresa relacionada	FK crm_company.id, NULLABLE
contact_id	INT	Contacto relacionado	FK crm_contact.id, NULLABLE
opportunity_id	INT	Oportunidad relacionada	FK crm_opportunity.id, NULLABLE
owner_id	INT	Usuario creador	FK users.uid, NOT NULL
assigned_to	INT	Usuario asignado	FK users.uid, NULLABLE
due_date	DATETIME	Fecha vencimiento	NULLABLE, INDEX
completed_at	DATETIME	Fecha completado	NULLABLE
status	VARCHAR(32)	Estado	ENUM: pending|completed|cancelled
priority	VARCHAR(16)	Prioridad	ENUM: low|medium|high
duration_minutes	INT	Duración en minutos	NULLABLE (para llamadas/meetings)
outcome	VARCHAR(64)	Resultado	ENUM: positive|neutral|negative|no_answer
next_steps	TEXT	Siguientes pasos	NULLABLE
reminder_at	DATETIME	Recordatorio	NULLABLE
is_automated	BOOLEAN	¿Creado por ECA?	DEFAULT FALSE
created_at	DATETIME	Fecha creación	NOT NULL, INDEX
updated_at	DATETIME	Última modificación	NOT NULL
 
3.5 Entidad: crm_pipeline_stage
Configuración de etapas del pipeline por tenant.
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno	PRIMARY KEY
tenant_id	INT	Tenant propietario	FK tenants.id, NOT NULL, INDEX
name	VARCHAR(64)	Nombre etapa	NOT NULL
machine_name	VARCHAR(64)	Nombre máquina	NOT NULL, UNIQUE per tenant
color	VARCHAR(7)	Color hex (#RRGGBB)	NOT NULL, DEFAULT '#3498db'
position	INT	Orden en pipeline	NOT NULL, DEFAULT 0
default_probability	INT	% probabilidad default	RANGE 0-100, DEFAULT 10
is_won_stage	BOOLEAN	¿Etapa de cierre?	DEFAULT FALSE
is_lost_stage	BOOLEAN	¿Etapa perdida?	DEFAULT FALSE
is_active	BOOLEAN	¿Activa?	DEFAULT TRUE
rotting_days	INT	Días para 'stale'	NULLABLE (alerta si excede)
created_at	DATETIME	Fecha creación	NOT NULL
Etapas por Defecto (Seed Data):
Etapa	machine_name	Probabilidad	Color
Nuevo Lead	new_lead	10%	#9b59b6
Contactado	contacted	20%	#3498db
Cualificado	qualified	40%	#2ecc71
Propuesta Enviada	proposal_sent	60%	#f39c12
Negociación	negotiation	80%	#e67e22
Ganado	won	100%	#27ae60
Perdido	lost	0%	#e74c3c
 
4. APIs REST
4.1 Endpoints de Empresas
Método	Endpoint	Descripción
GET	/api/v1/crm/companies	Listar empresas (paginado, filtros)
GET	/api/v1/crm/companies/{uuid}	Obtener empresa por UUID
POST	/api/v1/crm/companies	Crear nueva empresa
PATCH	/api/v1/crm/companies/{uuid}	Actualizar empresa
DELETE	/api/v1/crm/companies/{uuid}	Eliminar empresa (soft delete)
GET	/api/v1/crm/companies/{uuid}/contacts	Listar contactos de empresa
GET	/api/v1/crm/companies/{uuid}/opportunities	Listar oportunidades de empresa
GET	/api/v1/crm/companies/{uuid}/activities	Timeline de actividades
4.2 Endpoints de Oportunidades
Método	Endpoint	Descripción
GET	/api/v1/crm/opportunities	Listar oportunidades (paginado)
GET	/api/v1/crm/opportunities/pipeline	Vista pipeline (agrupado por etapa)
GET	/api/v1/crm/opportunities/{uuid}	Obtener oportunidad por UUID
POST	/api/v1/crm/opportunities	Crear nueva oportunidad
PATCH	/api/v1/crm/opportunities/{uuid}	Actualizar oportunidad
PATCH	/api/v1/crm/opportunities/{uuid}/stage	Mover a otra etapa (drag & drop)
POST	/api/v1/crm/opportunities/{uuid}/won	Marcar como ganada
POST	/api/v1/crm/opportunities/{uuid}/lost	Marcar como perdida
GET	/api/v1/crm/opportunities/forecast	Forecast de ventas
4.3 Ejemplo JSON: Crear Oportunidad
POST /api/v1/crm/opportunities
{   "title": "Implementación SaaS AgroConecta - Cooperativa El Olivar",   "company_id": "550e8400-e29b-41d4-a716-446655440000",   "contact_id": "660e8400-e29b-41d4-a716-446655440001",   "stage": "qualified",   "value": 12500.00,   "currency": "EUR",   "probability": 40,   "expected_close": "2026-03-15",   "lead_source": "referral",   "priority": "high",   "product_interest": ["agroconecta_pro", "training_pack"],   "description": "Cooperativa de 45 socios interesada en digitalización completa",   "next_action": "Presentación demo con junta directiva",   "next_action_date": "2026-02-01" }
4.4 Respuesta: Vista Pipeline
{   "pipeline": {     "stages": [       {         "id": "new_lead",         "name": "Nuevo Lead",         "color": "#9b59b6",         "count": 12,         "total_value": 45000.00,         "opportunities": [...]       },       {         "id": "qualified",         "name": "Cualificado",         "color": "#2ecc71",         "count": 8,         "total_value": 125000.00,         "opportunities": [...]       }     ],     "summary": {       "total_opportunities": 45,       "total_value": 380000.00,       "weighted_value": 142000.00,       "avg_deal_size": 8444.44,       "conversion_rate": 0.23     }   } }
 
5. Flujos de Automatización (ECA)
5.1 ECA-CRM-001: Lead Scoring Automático
Trigger: crm_contact.created OR crm_activity.created
1.	Obtener todas las actividades del contacto en últimos 30 días
2.	Calcular score: email_opened (+5), link_clicked (+10), form_filled (+20), meeting_scheduled (+30)
3.	Si score > 50 → lead_status = 'qualified'
4.	Actualizar crm_contact.lead_score
5.	Si lead_status cambió → notificar owner_id vía email
5.2 ECA-CRM-002: Alerta Oportunidad Estancada
Trigger: Cron diario 09:00
6.	SELECT oportunidades WHERE stage_changed_at < NOW() - rotting_days
7.	Para cada oportunidad estancada:
•	Crear crm_activity tipo 'task' con subject 'Revisar oportunidad estancada'
•	Enviar email a owner_id con resumen y enlace directo
•	Incrementar contador rotting_alerts en opportunity
5.3 ECA-CRM-003: Actualización FOC en Cierre
Trigger: crm_opportunity.status = 'won'
8.	Calcular valor final de la oportunidad
9.	Crear registro en foc_revenue con tipo 'sale' y datos de oportunidad
10.	Actualizar métricas de tenant: deals_won_count++, total_revenue +=
11.	Disparar webhook a integraciones externas (Make.com) si configurado
12.	Enviar email de felicitación al owner_id con summary
5.4 ECA-CRM-004: Seguimiento Automático Post-Reunión
Trigger: crm_activity (type='meeting', status='completed')
13.	Esperar 24 horas (delayed action)
14.	Verificar si existe actividad posterior con mismo contact_id
15.	Si NO existe → Crear task de seguimiento automático
16.	Sugerir email de seguimiento usando AI con contexto de la reunión
 
6. Integración con Financial Operations Center
El módulo jaraba_crm se integra bidireccionalmente con jaraba_foc para proporcionar métricas de ventas en tiempo real y forecasting financiero.
6.1 Métricas Enviadas al FOC
Métrica	Cálculo	Frecuencia
Pipeline Value	SUM(value) WHERE status='open'	Tiempo real
Weighted Pipeline	SUM(value * probability/100)	Tiempo real
Deals Won MTD	COUNT WHERE won this month	Al cerrar deal
Revenue MTD	SUM(value) WHERE won this month	Al cerrar deal
Win Rate	won / (won + lost) * 100	Diario
Avg Deal Size	AVG(value) WHERE won	Diario
Sales Cycle Days	AVG(actual_close - created_at)	Diario
Lead Conversion	qualified / total_leads * 100	Diario
6.2 Forecasting de Ingresos
El sistema genera forecasts automáticos basados en el pipeline actual y tasas históricas de conversión:
forecast_month = Σ (opportunity.value × stage.probability × historical_conversion_rate)  Donde: - stage.probability: Probabilidad configurada por etapa (10%-100%) - historical_conversion_rate: Tasa real de cierre del owner_id últimos 6 meses - Ajuste estacional: Factor 0.8-1.2 según patrones históricos del tenant
 
7. Roadmap de Implementación
Sprint	Timeline	Entregables	Horas
Sprint 1	Semana 1-2	Entidades base: crm_company, crm_contact. Migraciones DB. CRUD básico.	12-15h
Sprint 2	Semana 3-4	Entidad crm_opportunity. Pipeline stages config. Vista Kanban React.	12-15h
Sprint 3	Semana 5-6	Entidad crm_activity. Timeline de interacciones. APIs REST completas.	10-12h
Sprint 4	Semana 7-8	Flujos ECA. Integración FOC. Forecasting. Testing y QA.	8-10h
Total Estimado: 40-50 horas
--- Fin del Documento ---
150_Marketing_jaraba_crm_Pipeline_v1 | Jaraba Impact Platform | Enero 2026
