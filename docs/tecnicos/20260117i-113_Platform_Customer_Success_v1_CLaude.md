
CUSTOMER SUCCESS PROACTIVO
Especificación Técnica para Implementación
JARABA IMPACT PLATFORM

Versión:	1.0
Fecha:	Enero 2026
Código:	113_Platform_Customer_Success_v1
Estado:	Especificación para EDI
 
1. Resumen Ejecutivo
Sistema de Customer Success proactivo con health scores, alertas predictivas de churn, playbooks automáticos, y expansion signals. Objetivo: incrementar NRR de 100% a 115-120%.
1.1 Objetivos del Sistema
Objetivo	Métrica Target	Benchmark
Net Revenue Retention	115-120%	Best: 130%+
Churn Rate Anual	< 5%	Best B2B: 3-5%
Time to Churn Alert	30 días anticipación	Industry: 14-30
Expansion Rate	25%+ de base	Top: 30%+
Customer Health > 70	80% de clientes	Target estándar
1.2 Componentes del Sistema
•	Health Score Engine: Puntuación 0-100 basada en múltiples señales
•	Churn Prediction Model: ML para predecir abandono 30+ días antes
•	Automated Playbooks: Secuencias de acciones según triggers
•	Expansion Signals: Detectar oportunidades de upsell/cross-sell
•	Customer Journey Mapping: Visualizar touchpoints desde signup
•	CSM Dashboard: Panel de gestión para Customer Success Managers
 
2. Health Score Engine
2.1 Componentes del Health Score
El Health Score es una puntuación compuesta de 0-100 calculada en tiempo real.
Componente	Peso	Señales	Cálculo
Engagement	30%	Logins, tiempo en app, features usadas	DAU/MAU ratio × 100
Adoption	25%	Features activadas vs disponibles	(features_used/features_available) × 100
Satisfaction	20%	NPS, CSAT, reviews	Promedio ponderado
Support	15%	Tickets abiertos, tiempo resolución	100 - (open_tickets × 10)
Growth	10%	Expansión de uso, nuevos usuarios	MoM growth %
2.2 Categorización de Health
Rango	Categoría	Acción Recomendada
80-100	Healthy (Verde)	Identificar expansión, testimonios, referrals
60-79	Neutral (Amarillo)	Engagement proactivo, training adicional
40-59	At Risk (Naranja)	Playbook reactivación, llamada CSM
0-39	Critical (Rojo)	Escalación inmediata, executive sponsor
 
3. Modelo de Datos
3.1 Entidad: customer_health
Registro de health score por tenant actualizado diariamente.
Campo	Tipo	Requerido	Descripción
id	UUID	Sí	Identificador único
tenant_id	UUID FK	Sí	Tenant evaluado
overall_score	INT (0-100)	Sí	Health score total
engagement_score	INT (0-100)	Sí	Score de engagement
adoption_score	INT (0-100)	Sí	Score de adopción
satisfaction_score	INT (0-100)	Sí	Score de satisfacción
support_score	INT (0-100)	Sí	Score de soporte
growth_score	INT (0-100)	Sí	Score de crecimiento
category	ENUM	Sí	healthy|neutral|at_risk|critical
trend	ENUM	Sí	improving|stable|declining
score_breakdown	JSON	Sí	Detalle de cada métrica
churn_probability	DECIMAL(3,2)	No	Probabilidad churn 0-1
calculated_at	TIMESTAMP	Sí	Fecha de cálculo
3.2 Entidad: churn_prediction
Predicciones de churn generadas por el modelo ML.
Campo	Tipo	Requerido	Descripción
id	UUID	Sí	Identificador único
tenant_id	UUID FK	Sí	Tenant evaluado
probability	DECIMAL(3,2)	Sí	Probabilidad churn 0.00-1.00
risk_level	ENUM	Sí	low|medium|high|critical
predicted_churn_date	DATE	No	Fecha estimada de churn
top_risk_factors	JSON	Sí	Array de factores de riesgo
recommended_actions	JSON	Sí	Acciones sugeridas
model_version	VARCHAR(20)	Sí	Versión del modelo ML
confidence	DECIMAL(3,2)	Sí	Confianza de la predicción
created_at	TIMESTAMP	Sí	Fecha de predicción
3.3 Entidad: cs_playbook
Playbooks de Customer Success con secuencias de acciones.
Campo	Tipo	Requerido	Descripción
id	UUID	Sí	Identificador único
name	VARCHAR(255)	Sí	Nombre del playbook
trigger_type	ENUM	Sí	health_drop|churn_risk|expansion|onboarding
trigger_conditions	JSON	Sí	Condiciones para activar
steps	JSON	Sí	Array de pasos/acciones
auto_execute	BOOLEAN	Sí	Ejecutar automáticamente
priority	ENUM	Sí	low|medium|high|urgent
status	ENUM	Sí	active|paused|archived
3.4 Entidad: expansion_signal
Señales de oportunidad de expansión detectadas.
Campo	Tipo	Requerido	Descripción
id	UUID	Sí	Identificador único
tenant_id	UUID FK	Sí	Tenant con oportunidad
signal_type	ENUM	Sí	usage_limit|feature_request|user_growth
current_plan	VARCHAR(50)	Sí	Plan actual del tenant
recommended_plan	VARCHAR(50)	No	Plan recomendado
potential_arr	DECIMAL(10,2)	No	ARR potencial incremental
signal_details	JSON	Sí	Detalles de la señal
status	ENUM	Sí	new|contacted|won|lost|deferred
detected_at	TIMESTAMP	Sí	Fecha de detección
 
4. Playbooks Predefinidos
4.1 Playbook: Reactivación (Health < 60)
Día	Acción	Detalle	Canal
D+0	Alerta interna	Notificar CSM asignado	Slack/Email
D+1	Email check-in	"Hemos notado menor actividad..."	Email
D+3	Agendar llamada	Ofrecer sesión de soporte 1:1	Email + Calendly
D+7	Ofrecer training	Invitar a webinar o training personalizado	Email
D+14	Escalación	Si no responde, escalar a manager	Interno
D+21	Oferta especial	Descuento o extensión gratuita	Llamada
4.2 Playbook: Expansion (Usage > 80%)
Día	Acción	Detalle	Canal
D+0	Detectar señal	Uso > 80% de límite del plan	Automático
D+1	Email informativo	"Estás aprovechando al máximo..."	Email
D+3	Mostrar upgrade	Banner in-app con beneficios del siguiente plan	In-app
D+7	Llamada CSM	Discutir crecimiento y opciones	Llamada
D+14	Oferta limitada	Upgrade con X% descuento por 7 días	Email
 
5. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/health-scores	Listar health scores (filtrable)
GET	/api/v1/health-scores/{tenant_id}	Health score de tenant específico
GET	/api/v1/health-scores/{tenant_id}/history	Histórico de health scores
GET	/api/v1/churn-predictions	Listar predicciones de churn
GET	/api/v1/expansion-signals	Listar señales de expansión
PUT	/api/v1/expansion-signals/{id}	Actualizar estado de señal
GET	/api/v1/playbooks	Listar playbooks
POST	/api/v1/playbooks	Crear playbook
GET	/api/v1/playbooks/{id}/executions	Ejecuciones del playbook
POST	/api/v1/playbooks/{id}/execute	Ejecutar playbook manualmente
 
6. Roadmap de Implementación
Sprint	Timeline	Entregables
Sprint 1	Semana 1-2	Entidades BD. API health scores básica.
Sprint 2	Semana 3-4	Health Score Engine. Cálculo automático diario.
Sprint 3	Semana 5-6	CSM Dashboard v1. Visualización health scores.
Sprint 4	Semana 7-8	Playbooks engine. 2 playbooks predefinidos.
Sprint 5	Semana 9-10	Expansion Signals. Detección automática.
Sprint 6	Semana 11-12	Churn Prediction Model v1. Alertas proactivas.
Sprint 7	Semana 13-14	Integración ECA. Automatización completa. Go-live.
6.1 Estimación de Esfuerzo
Componente	Horas Estimadas
Health Score Engine	60-80h
CSM Dashboard	40-60h
Playbooks Engine	50-70h
Expansion Signals	30-40h
Churn Prediction Model	80-120h
Integraciones (Slack, Email)	30-40h
TOTAL	290-410h
--- Fin del Documento ---
