USAGE-BASED PRICING
Infraestructura de Precios Basados en Uso
Plataforma Core - Gap #4 Crítico
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	111_Platform_UsageBased_Pricing
Dependencias:	104_SaaS_Admin_Center, Stripe Billing
 
1. Resumen Ejecutivo
Este documento especifica la infraestructura técnica para implementar modelos de precios basados en uso (Usage-Based Pricing) en la Jaraba Impact Platform. El objetivo es complementar los planes de suscripción tradicionales con opciones flexibles que alineen el precio con el valor recibido por el cliente.
1.1 Modelos de Pricing Soportados
Modelo	Descripción	Ejemplo
Pure Subscription	Precio fijo mensual/anual	€49/mes Plan Pro
Subscription + Overage	Base fija + excedentes	€29 base + €0.05/transacción extra
Tiered Usage	Precio por tramos de uso	0-100 txn: €0.10, 101-500: €0.07
Seat-Based + Usage	Por usuario + consumo	€10/usuario + AI tokens
Credits Model	Compra de créditos prepago	100 créditos = €50
Revenue Share	Comisión por transacción	8% del GMV procesado
1.2 Métricas de Uso Billables
Métrica	Descripción	Vertical
transactions	Pedidos/ventas procesados	AgroConecta, ComercioConecta
gmv	Valor bruto de mercancía	AgroConecta, ComercioConecta
job_postings	Ofertas de empleo publicadas	Empleabilidad
applications	Candidaturas recibidas	Empleabilidad
ai_tokens	Tokens de IA consumidos	Todas
storage_gb	Almacenamiento usado	Todas
api_calls	Llamadas API externas	Todas
active_users	MAU del tenant	Todas
2. Arquitectura Técnica
2.1 Stack Tecnológico
Componente	Tecnología
Billing Platform	Stripe Billing con Usage Records
Metering Service	Custom Drupal module + Redis counters
Event Streaming	Redis Streams para eventos de uso
Aggregation	Cron jobs para agregación diaria/horaria
Reporting	Dashboards en FOC (Financial Operations Center)
Alerts	Notificaciones de umbral de uso
 
3. Modelo de Datos
3.1 Entidad: usage_event
Eventos de uso en tiempo real.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
tenant_id	INT	Tenant	FK groups.id, INDEX
metric_type	VARCHAR(64)	Tipo de métrica	transactions|ai_tokens|storage|...
quantity	DECIMAL(15,4)	Cantidad	NOT NULL
unit	VARCHAR(32)	Unidad	count|bytes|tokens|currency
metadata	JSON	Datos adicionales	order_id, user_id, etc
idempotency_key	VARCHAR(128)	Prevenir duplicados	UNIQUE, NULLABLE
recorded_at	DATETIME	Cuando ocurrió	NOT NULL, UTC, INDEX
billed	BOOLEAN	Ya facturado	DEFAULT FALSE
billing_period	VARCHAR(7)	Periodo de facturación	Format: YYYY-MM
3.2 Entidad: usage_aggregate
Agregaciones de uso por periodo.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
tenant_id	INT	Tenant	FK groups.id
metric_type	VARCHAR(64)	Tipo de métrica	INDEX
period_type	VARCHAR(16)	Tipo periodo	hourly|daily|monthly
period_start	DATETIME	Inicio periodo	NOT NULL, UTC
period_end	DATETIME	Fin periodo	NOT NULL, UTC
total_quantity	DECIMAL(15,4)	Total del periodo	NOT NULL
event_count	INT	Número de eventos	NOT NULL
computed_cost	DECIMAL(10,4)	Coste calculado	Based on pricing rules
synced_to_stripe	BOOLEAN	Enviado a Stripe	DEFAULT FALSE
3.3 Entidad: pricing_rule
Reglas de pricing por métrica y plan.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
plan_id	VARCHAR(64)	Plan de suscripción	FK to subscription plan
metric_type	VARCHAR(64)	Métrica aplicable	NOT NULL
pricing_model	VARCHAR(32)	Modelo de precio	flat|tiered|volume|package
included_quantity	DECIMAL(15,4)	Cantidad incluida en plan	DEFAULT 0
tiers	JSON	Definición de tramos	Array of tier objects
currency	VARCHAR(3)	Moneda	DEFAULT EUR
is_active	BOOLEAN	Regla activa	DEFAULT TRUE
 
4. Metering Pipeline
4.1 Flujo de Eventos
•	1. Evento ocurre: Pedido creado, AI tokens consumidos, archivo subido
•	2. Hook ECA dispara: Envía evento a Redis Stream
•	3. Worker procesa: Valida, deduplica, almacena en usage_event
•	4. Aggregator: Cron cada hora agrega eventos en usage_aggregate
•	5. Sync to Stripe: Cron diario envía usage records a Stripe
•	6. Invoice generation: Stripe genera factura al final del periodo
4.2 Ejemplo de Evento
{
  "event_type": "usage_recorded",
  "tenant_id": 123,
  "metric_type": "ai_tokens",
  "quantity": 1500,
  "metadata": {
    "model": "claude-3-sonnet",
    "feature": "copilot_chat",
    "user_id": 456
  },
  "idempotency_key": "chat_msg_789_tokens",
  "recorded_at": "2026-01-17T14:30:00Z"
}
5. APIs REST
Método	Endpoint	Descripción
POST	/api/v1/usage/record	Registrar evento de uso
GET	/api/v1/usage/current	Uso actual del periodo
GET	/api/v1/usage/history?months=6	Historial de uso
GET	/api/v1/usage/breakdown?metric={type}	Desglose por métrica
GET	/api/v1/usage/forecast	Proyección de uso/coste
GET	/api/v1/pricing/my-plan	Reglas de pricing del plan actual
GET	/api/v1/pricing/estimate?usage={json}	Estimar coste para uso dado
6. Dashboard de Uso
6.1 Componentes UI para Tenant
•	Usage Meter: Barra de progreso con uso actual vs incluido
•	Cost Projection: Estimación de factura basada en tendencia
•	Usage Charts: Gráficos de uso diario/semanal/mensual por métrica
•	Alerts Config: Configurar notificaciones de umbral (80%, 100%)
•	Invoice Preview: Vista previa de factura del periodo actual
7. Roadmap de Implementación
Sprint	Timeline	Entregables	Deps
Sprint 1	Semana 1-2	Entidades BD. Metering service. Redis integration.	Stripe Billing
Sprint 2	Semana 3-4	Hooks ECA para métricas. Aggregation jobs.	Sprint 1
Sprint 3	Semana 5-6	Stripe Usage Records sync. Pricing rules engine.	Sprint 2
Sprint 4	Semana 7-8	Dashboard UI. Alerts. Forecast.	Sprint 3
Sprint 5	Semana 9-10	Testing. Migración planes existentes. Go-live.	Sprint 4
7.1 Estimación de Esfuerzo
Componente	Horas	Prioridad
Metering Service + Events	40-50	P0
Aggregation Pipeline	30-40	P0
Stripe Usage Records Integration	30-40	P0
Pricing Rules Engine	25-35	P1
Dashboard + Alerts	30-40	P1
TOTAL	155-205	-
— Fin del Documento —
