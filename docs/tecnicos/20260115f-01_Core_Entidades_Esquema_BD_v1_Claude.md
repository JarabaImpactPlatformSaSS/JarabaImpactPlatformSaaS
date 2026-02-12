
ESPECIFICACIÓN DE ENTIDADES
Y ESQUEMA DE BASE DE DATOS

Core SaaS Multi-Tenant

JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Versión:	1.0
Fecha:	Enero 2026
Estado:	Documento Técnico Definitivo
Clasificación:	Interno - Implementación
 
Tabla de Contenidos
Tabla de Contenidos	1
1. Resumen Ejecutivo	1
1.1 Principios de Diseño	1
1.2 Categorías de Entidades	1
2. Entidades Core Business	1
2.1 Entidad: tenant	1
Esquema de Campos	1
Índices	1
2.2 Entidad: user_profile_extended	1
Esquema de Campos	1
3. Entidades Financial (FOC)	1
3.1 Entidad: financial_transaction	1
Esquema de Campos	1
Restricciones de Integridad	1
3.2 Entidad: cost_allocation	1
Esquema de Campos	1
3.3 Entidad: foc_metric_snapshot	1
Esquema de Campos	1
4. Entidades Vertical Empleabilidad	1
4.1 Entidad: diagnostic_express_result	1
Esquema de Campos	1
5. Entidades AI/RAG	1
5.1 Entidad: ai_query_log	1
Esquema de Campos	1
6. Índices y Optimización	1
6.1 Índices Compuestos Críticos	1
6.2 Particionamiento Recomendado	1
7. Reglas de Negocio y Constraints	1
7.1 Triggers de Integridad	1
7.2 Check Constraints	1
8. Diagrama Entidad-Relación	1
9. Próximos Pasos de Implementación	1

 
1. Resumen Ejecutivo
Este documento define la especificación completa de las entidades personalizadas (Custom Content Entities) que conforman el núcleo del SaaS Jaraba Impact Platform. La arquitectura de datos está diseñada para soportar el modelo de negocio Triple Motor Económico con soft multi-tenancy basado en el módulo Group de Drupal 11.
1.1 Principios de Diseño
El esquema de entidades sigue los siguientes principios arquitectónicos:
Inmutabilidad Financiera: Las entidades financieras son append-only, garantizando integridad contable y auditoría.
Aislamiento Multi-Tenant: Toda entidad incluye referencia a Group ID para filtrado automático por tenant.
Trazabilidad Completa: UUIDs para sincronización externa, timestamps UTC, y external_id para reconciliación.
Precisión Monetaria: Campos monetarios como Decimal(10,4), nunca float, con soporte ISO 4217.
1.2 Categorías de Entidades
Categoría	Entidades	Propósito
Core Business	tenant, user_profile, vertical	Estructura organizativa
Financial (FOC)	financial_transaction, cost_allocation, foc_metric_snapshot	Operaciones financieras
Commerce	product, order, payment_split	E-commerce multi-tenant
Empleabilidad	diagnostic_result, learning_path, credential	Vertical empleabilidad
AI/RAG	ai_query_log, content_vector, ai_feedback	Sistema de IA
 
2. Entidades Core Business
2.1 Entidad: tenant
Representa una instancia de cliente en el sistema multi-tenant. Cada tenant es un Group en Drupal que agrupa usuarios, contenido, configuración y datos financieros.
Esquema de Campos
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno autoincremental	PRIMARY KEY, NOT NULL
uuid	UUID	Identificador único global	UNIQUE, NOT NULL, INDEX
name	VARCHAR(255)	Nombre comercial del tenant	NOT NULL
machine_name	VARCHAR(64)	Identificador de máquina	UNIQUE, NOT NULL, INDEX, [a-z0-9_]
group_id	INT	Referencia a Group entity	FOREIGN KEY (groups.id)
vertical_id	INT	Vertical de negocio	FOREIGN KEY (taxonomy_term)
plan_type	VARCHAR(32)	Tipo de suscripción	ENUM: starter|professional|enterprise
stripe_account_id	VARCHAR(64)	ID cuenta Stripe Connect	UNIQUE, NULLABLE, acct_*
stripe_onboarding_complete	BOOLEAN	Estado KYC Stripe	DEFAULT FALSE
platform_fee_percent	DECIMAL(5,2)	Comisión aplicada	DEFAULT 5.00, RANGE 0-100
status	VARCHAR(16)	Estado del tenant	ENUM: active|suspended|churned
created	DATETIME	Fecha de creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
Índices
PRIMARY KEY (id) | UNIQUE INDEX (uuid) | UNIQUE INDEX (machine_name) | INDEX (group_id) | INDEX (vertical_id, status) | INDEX (stripe_account_id)
 
2.2 Entidad: user_profile_extended
Extiende el perfil de usuario base de Drupal con campos específicos del ecosistema. Vincula usuarios con tenants, verticales, y almacena preferencias y métricas de engagement.
Esquema de Campos
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
user_id	INT	Referencia a users.uid	FOREIGN KEY, UNIQUE, NOT NULL
primary_tenant_id	INT	Tenant principal	FOREIGN KEY (tenant.id)
avatar_type	VARCHAR(32)	Tipo de usuario	ENUM: lucia|javier|marta|david|elena
onboarding_completed	BOOLEAN	Onboarding completado	DEFAULT FALSE
diagnostic_score	INT	Score diagnóstico inicial	RANGE 0-10, NULLABLE
impact_credits	INT	Créditos de impacto	DEFAULT 0, >= 0
health_score	INT	Score de salud (churn)	RANGE 0-100, DEFAULT 50
last_activity	DATETIME	Última actividad	UTC, INDEX
preferences	JSON	Preferencias usuario	NULLABLE, validación schema
 
3. Entidades Financial (FOC)
Las entidades del Centro de Operaciones Financieras implementan el modelo de Data Warehouse Operativo. Son entidades inmutables (append-only) que garantizan la integridad de datos contables.
3.1 Entidad: financial_transaction
Libro mayor contable del sistema. Registra todas las transacciones financieras de forma inmutable. Nunca se editan ni eliminan registros; las correcciones se realizan mediante asientos compensatorios.
Esquema de Campos
Campo	Tipo	Descripción	Restricciones
id	BIGSERIAL	ID secuencial	PRIMARY KEY, IMMUTABLE
uuid	UUID	ID único para sync	UNIQUE, NOT NULL
amount	DECIMAL(12,4)	Monto de transacción	NOT NULL, PRECISION 4
currency	CHAR(3)	Código ISO 4217	NOT NULL, DEFAULT 'EUR'
transaction_type	VARCHAR(32)	Tipo de transacción	ENUM: income_recurring, income_one_time, grant, cost_direct, cost_indirect, refund, chargeback
source_system	VARCHAR(32)	Sistema origen	ENUM: stripe_connect, activecampaign, manual, commerce
external_id	VARCHAR(128)	ID en sistema externo	INDEX, para reconciliación
tenant_id	INT	Tenant asociado	FOREIGN KEY, NULLABLE
vertical_id	INT	Vertical de negocio	FOREIGN KEY, NULLABLE
motor_type	VARCHAR(16)	Motor económico	ENUM: institutional, private, licenses
campaign_id	INT	Campaña marketing	NULLABLE, para CAC
net_amount	DECIMAL(12,4)	Monto neto (post-fees)	NULLABLE, calculado
platform_fee	DECIMAL(12,4)	Fee plataforma	DEFAULT 0
processor_fee	DECIMAL(12,4)	Fee Stripe/procesador	DEFAULT 0
recognition_date	DATE	Fecha reconocimiento	ASC 606 compliance
deferred_revenue_id	INT	Ingresos diferidos	NULLABLE, suscripciones anuales
metadata	JSONB	Datos adicionales	NULLABLE, índice GIN
timestamp	TIMESTAMP	Momento transacción	NOT NULL, UTC, INDEX
created	TIMESTAMP	Registro creado	NOT NULL, DEFAULT NOW()
Restricciones de Integridad
INMUTABILIDAD: La tabla no permite UPDATE ni DELETE. Todas las correcciones se realizan mediante asientos compensatorios (reversal transactions).
TRIGGER: before_update_financial_transaction() RAISES EXCEPTION para prevenir modificaciones.
 
3.2 Entidad: cost_allocation
Gestiona la distribución de costes compartidos entre tenants y verticales. Esencial para calcular la rentabilidad real (Unit Economics) en arquitectura multi-tenant.
Esquema de Campos
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
cost_category	VARCHAR(32)	Categoría de coste	ENUM: hosting, support, devops, licenses, marketing, payment_processing
total_cost	DECIMAL(12,4)	Coste total a distribuir	NOT NULL, >= 0
allocation_driver	VARCHAR(32)	Base de distribución	ENUM: disk_usage, bandwidth, users, revenue, flat
period_start	DATE	Inicio período	NOT NULL
period_end	DATE	Fin período	NOT NULL, > period_start
allocation_rules	JSONB	Reglas de reparto	Array de {tenant_id, percentage}
calculated_allocations	JSONB	Distribución calculada	Array de {tenant_id, amount}
status	VARCHAR(16)	Estado del allocation	ENUM: draft, calculated, applied
 
3.3 Entidad: foc_metric_snapshot
Almacena snapshots diarios de todas las métricas SaaS calculadas. Permite análisis histórico, trending, y detección de anomalías.
Esquema de Campos
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
snapshot_date	DATE	Fecha del snapshot	NOT NULL, INDEX
scope_type	VARCHAR(16)	Alcance métrica	ENUM: platform, vertical, tenant
scope_id	INT	ID del scope	NULLABLE (null = platform)
mrr	DECIMAL(12,4)	Monthly Recurring Revenue	NOT NULL
arr	DECIMAL(12,4)	Annual Recurring Revenue	= mrr * 12
revenue_churn_rate	DECIMAL(6,4)	Churn de ingresos %	RANGE 0-1
logo_churn_rate	DECIMAL(6,4)	Churn de clientes %	RANGE 0-1
nrr	DECIMAL(6,4)	Net Revenue Retention	Target > 1.0
grr	DECIMAL(6,4)	Gross Revenue Retention	Target > 0.85
cac	DECIMAL(12,4)	Customer Acq. Cost	>= 0
ltv	DECIMAL(12,4)	Lifetime Value	Calculado
ltv_cac_ratio	DECIMAL(6,2)	Ratio LTV:CAC	Target >= 3.0
gross_margin	DECIMAL(6,4)	Margen bruto %	Target >= 0.70
active_users	INT	Usuarios activos	>= 0
gmv	DECIMAL(14,4)	Gross Merch. Value	Para marketplace
metadata	JSONB	Datos adicionales	NULLABLE
 
4. Entidades Vertical Empleabilidad
4.1 Entidad: diagnostic_express_result
Almacena los resultados del Diagnóstico Express para el vertical de Empleabilidad. Permite análisis de gaps, segmentación de leads, y personalización del journey.
Esquema de Campos
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
session_id	UUID	Sesión anónima	INDEX, para pre-registro
user_id	INT	Usuario (post-registro)	NULLABLE, FK users
score_total	INT	Score 0-10	NOT NULL, RANGE 0-10
profile_type	VARCHAR(32)	Perfil asignado	ENUM: invisible, desconectado, construccion, competitivo, magnetico
primary_gap	VARCHAR(32)	Gap principal	ENUM: linkedin, cv, search_strategy
answers	JSONB	Respuestas completas	{q1: value, q2: value, q3: value}
recommended_action	VARCHAR(128)	Acción recomendada	Primera acción a realizar
action_completed	BOOLEAN	Acción completada	DEFAULT FALSE
ac_synced	BOOLEAN	Sync ActiveCampaign	DEFAULT FALSE
ttv_seconds	DECIMAL(6,2)	Time-to-Value real	Para métricas UX
created	TIMESTAMP	Fecha creación	NOT NULL, UTC
 
5. Entidades AI/RAG
5.1 Entidad: ai_query_log
Registra todas las consultas realizadas al sistema de IA/RAG. Esencial para análisis de content gaps, mejora continua del sistema, y compliance GDPR.
Esquema de Campos
Campo	Tipo	Descripción	Restricciones
id	BIGSERIAL	ID secuencial	PRIMARY KEY
tenant_id	INT	Tenant de origen	FOREIGN KEY, NOT NULL
user_id	INT	Usuario	NULLABLE (anónimo permitido)
query_text	TEXT	Consulta original	NOT NULL
query_embedding	VECTOR(1536)	Embedding de consulta	Para similitud
intent_classification	VARCHAR(64)	Intención detectada	ENUM: product_info, support, purchase_intent, general
response_text	TEXT	Respuesta generada	NOT NULL
source_chunks	JSONB	Chunks utilizados	Array de {chunk_id, score}
grounding_score	DECIMAL(4,3)	Score de grounding	RANGE 0-1, NLI validation
hallucination_detected	BOOLEAN	Alucinación detectada	DEFAULT FALSE
user_feedback	VARCHAR(16)	Feedback usuario	ENUM: helpful, not_helpful, null
latency_ms	INT	Tiempo respuesta	Milisegundos
model_used	VARCHAR(32)	Modelo LLM usado	gpt-4, claude-3, gemini-pro
tokens_input	INT	Tokens entrada	Para cost tracking
tokens_output	INT	Tokens salida	Para cost tracking
created	TIMESTAMP	Timestamp	NOT NULL, UTC, INDEX
 
6. Índices y Optimización
6.1 Índices Compuestos Críticos
Tabla	Índice	Caso de Uso
financial_transaction	idx_ft_tenant_date (tenant_id, timestamp)	Dashboard por tenant
financial_transaction	idx_ft_vertical_motor (vertical_id, motor_type, timestamp)	Análisis Triple Motor
financial_transaction	idx_ft_external (source_system, external_id)	Reconciliación
foc_metric_snapshot	idx_fms_scope_date (scope_type, scope_id, snapshot_date)	Trending histórico
ai_query_log	idx_aql_tenant_created (tenant_id, created DESC)	Historial queries
ai_query_log	idx_aql_intent (intent_classification, created)	Análisis intenciones
diagnostic_express_result	idx_der_profile_gap (profile_type, primary_gap)	Segmentación leads
6.2 Particionamiento Recomendado
Para tablas de alto volumen, se recomienda particionamiento por rango de fecha:
financial_transaction: Partición mensual por campo timestamp. Retención mínima 7 años (compliance fiscal).
ai_query_log: Partición mensual por campo created. Retención 2 años (GDPR + análisis).
foc_metric_snapshot: Partición anual por snapshot_date. Sin límite de retención (datos históricos estratégicos).
 
7. Reglas de Negocio y Constraints
7.1 Triggers de Integridad
Trigger	Tabla	Comportamiento
prevent_ft_modification	financial_transaction	BEFORE UPDATE/DELETE: Raise exception. La tabla es append-only.
calculate_net_amount	financial_transaction	BEFORE INSERT: net_amount = amount - platform_fee - processor_fee
update_tenant_health	user_profile_extended	AFTER UPDATE on last_activity: Recalcular health_score del tenant
sync_diagnostic_to_ac	diagnostic_express_result	AFTER INSERT WHERE user_id IS NOT NULL: Queue webhook a ActiveCampaign
detect_hallucination	ai_query_log	BEFORE INSERT: Si grounding_score < 0.7, set hallucination_detected = TRUE
7.2 Check Constraints
Constraint	Tabla.Campo	Validación
chk_positive_amount	financial_transaction.amount	amount != 0 (puede ser negativo para refunds)
chk_valid_currency	financial_transaction.currency	currency IN ('EUR', 'USD', 'GBP')
chk_fee_range	tenant.platform_fee_percent	platform_fee_percent BETWEEN 0 AND 100
chk_score_range	diagnostic_express_result.score_total	score_total BETWEEN 0 AND 10
chk_grounding_range	ai_query_log.grounding_score	grounding_score BETWEEN 0 AND 1
chk_period_order	cost_allocation	period_end > period_start
 
8. Diagrama Entidad-Relación
Representación ASCII del modelo de datos principal:
 ┌─────────────────────────────────────────────────────────────────────────┐ │                        JARABA IMPACT PLATFORM                           │ │                     Entity-Relationship Diagram                         │ └─────────────────────────────────────────────────────────────────────────┘  ┌─────────────┐     1:N     ┌─────────────────────┐     N:1     ┌─────────┐ │   groups    │◄────────────│       tenant        │────────────►│vertical │ │  (Drupal)   │             │  - stripe_account   │             │(taxonmy)│ └─────────────┘             │  - platform_fee     │             └─────────┘       │                     └─────────────────────┘       │                              │       │ 1:N                          │ 1:N       ▼                              ▼ ┌─────────────┐             ┌─────────────────────┐ │    users    │◄────────────│ user_profile_extend │ │  (Drupal)   │    1:1      │  - avatar_type      │ └─────────────┘             │  - health_score     │       │                     └─────────────────────┘       │ 1:N                          │       ▼                              │ 1:N ┌─────────────────────────────────────────────────────────────────────────┐ │                         financial_transaction                            │ │  (APPEND-ONLY LEDGER)                                                   │ │  - amount, currency, transaction_type, motor_type                       │ │  - external_id (Stripe), platform_fee, net_amount                       │ └─────────────────────────────────────────────────────────────────────────┘       │       │ Aggregates to       ▼ ┌─────────────────────────────────────────────────────────────────────────┐ │                         foc_metric_snapshot                             │ │  - mrr, arr, churn_rate, nrr, grr, cac, ltv                            │ │  - scope: platform | vertical | tenant                                  │ └─────────────────────────────────────────────────────────────────────────┘  ┌─────────────────────────────────────────────────────────────────────────┐ │                            AI / RAG LAYER                               │ ├─────────────────────────────────────────────────────────────────────────┤ │  ai_query_log ──────► content_vector (Qdrant) ──────► ai_feedback      │ │  - intent_class        - tenant namespace              - thumbs up/down │ │  - grounding_score     - metadata filtering            - corrections    │ └─────────────────────────────────────────────────────────────────────────┘  ┌─────────────────────────────────────────────────────────────────────────┐ │                      EMPLEABILIDAD VERTICAL                             │ ├─────────────────────────────────────────────────────────────────────────┤ │  diagnostic_express_result ──► learning_path ──► credential            │ │  - score 0-10                  - progress %        - verified           │ │  - profile_type                - modules           - blockchain_hash    │ └─────────────────────────────────────────────────────────────────────────┘ 
 
9. Próximos Pasos de Implementación
Fase	Timeline	Entregables	Dependencias
1	Semana 1-2	Módulo jaraba_core_entities: Definición de todas las entidades en código Drupal. Migrations para schema inicial.	Drupal 11 instalado
2	Semana 3	Triggers y constraints SQL. Tests unitarios de integridad. Seed data para desarrollo.	Fase 1
3	Semana 4	Integración con Group Module. Pruebas de aislamiento multi-tenant. Access control por grupo.	Fase 2
4	Semana 5-6	Índices de producción. Particionamiento. Load testing con datos sintéticos.	Fase 3

FIN DEL DOCUMENTO
Especificación de Entidades Core v1.0 | Jaraba Impact Platform | Enero 2026
