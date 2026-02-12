PAID ADS INTEGRATION
Meta Ads & Google Ads Integration
Gestión Centralizada de Campañas Publicitarias Multi-Plataforma
Versión:	1.0
Fecha:	Enero 2026
Código:	153_Marketing_Paid_Ads_Integration_v1
Estado:	Especificación Técnica para Implementación
Horas Estimadas:	15-20 horas
Dependencias:	jaraba_core, jaraba_crm, jaraba_foc, Meta Marketing API, Google Ads API
1. Resumen Ejecutivo
El módulo Paid Ads Integration proporciona una capa de abstracción unificada para gestionar campañas publicitarias en Meta Ads (Facebook/Instagram) y Google Ads desde el panel administrativo de Jaraba. Permite sincronización de audiencias desde jaraba_crm, tracking de conversiones, importación de métricas de rendimiento y alertas automáticas de presupuesto. No reemplaza los dashboards nativos, sino que los complementa con visión consolidada y automatización.
1.1 Capacidades Principales
•	Conexión OAuth con Meta Business Manager y Google Ads
•	Dashboard consolidado de métricas clave (CPL, CPA, ROAS, CTR)
•	Sincronización de Custom Audiences desde jaraba_crm
•	Tracking de conversiones offline via API
•	Alertas automáticas de presupuesto y rendimiento
•	Integración con FOC para CAC y métricas SaaS
•	Lookalike audiences automáticas desde segmentos CRM
1.2 Plataformas Soportadas
Plataforma	API	Funcionalidades	Nivel Acceso
Meta Ads	Marketing API v18.0	Campaigns, Audiences, Conversions	ads_management
Google Ads	Google Ads API v16	Campaigns, Audiences, Conversions	Standard Access
LinkedIn Ads	Marketing API	Fase 2 - No incluido	Future
 
2. Arquitectura Técnica
2.1 Stack Tecnológico
Componente	Tecnología
Backend	Drupal 11 Custom Entities
Meta Integration	Meta Marketing API v18.0 (PHP SDK)
Google Integration	Google Ads API v16 (PHP Client Library)
Queue	Redis Queue para sync asíncrono
Caching	Redis con TTL 15min para métricas
Encryption	AES-256 para tokens OAuth
2.2 Diagrama de Integración
┌─────────────────────────────────────────────────────────────────┐ │                    JARABA PAID ADS INTEGRATION                   │ ├─────────────────────────────────────────────────────────────────┤ │   jaraba_crm         jaraba_foc         jaraba_ads_account      │ │   (Contacts)    ←→   (CAC/ROAS)    ←→   (OAuth Tokens)          │ ├─────────────────────────────────────────────────────────────────┤ │                     ↓ SYNC LAYER ↓                               │ │   ┌─────────────────────┐    ┌─────────────────────┐            │ │   │   Meta Marketing    │    │   Google Ads API    │            │ │   │     API v18.0       │    │       v16           │            │ │   ├─────────────────────┤    ├─────────────────────┤            │ │   │ • Custom Audiences  │    │ • Customer Match    │            │ │   │ • Conversions API   │    │ • Offline Conversions│           │ │   │ • Insights API      │    │ • Reporting API     │            │ │   └─────────────────────┘    └─────────────────────┘            │ └─────────────────────────────────────────────────────────────────┘
3. Esquema de Base de Datos
3.1 Entidad: ads_account
Cuentas publicitarias conectadas por tenant.
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno	PRIMARY KEY
uuid	VARCHAR(36)	UUID público	UNIQUE, NOT NULL
tenant_id	INT	Tenant propietario	FK tenants.id, NOT NULL, INDEX
platform	VARCHAR(32)	Plataforma publicitaria	ENUM: meta|google
account_id	VARCHAR(128)	ID cuenta en plataforma	NOT NULL
account_name	VARCHAR(255)	Nombre de la cuenta	NOT NULL
access_token	TEXT	Token OAuth (encriptado)	NOT NULL, ENCRYPTED
refresh_token	TEXT	Refresh token	NULLABLE, ENCRYPTED
token_expires_at	DATETIME	Expiración del token	NULLABLE
currency	VARCHAR(3)	Moneda de la cuenta	DEFAULT 'EUR'
timezone	VARCHAR(64)	Zona horaria	DEFAULT 'Europe/Madrid'
status	VARCHAR(32)	Estado conexión	ENUM: active|disconnected|error
last_sync_at	DATETIME	Última sincronización	NULLABLE
sync_errors	JSON	Errores de sincronización	DEFAULT '[]'
created_at	DATETIME	Fecha de creación	DEFAULT NOW()
updated_at	DATETIME	Última actualización	ON UPDATE NOW()
 
3.2 Entidad: ads_campaign_sync
Campañas sincronizadas desde plataformas publicitarias (solo lectura).
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno	PRIMARY KEY
ads_account_id	INT	Cuenta publicitaria	FK ads_account.id
campaign_id	VARCHAR(128)	ID campaña en plataforma	NOT NULL, INDEX
campaign_name	VARCHAR(255)	Nombre de la campaña	NOT NULL
objective	VARCHAR(64)	Objetivo de campaña	NULLABLE
status	VARCHAR(32)	Estado de la campaña	ENUM: active|paused|archived
daily_budget	DECIMAL(12,2)	Presupuesto diario	NULLABLE
lifetime_budget	DECIMAL(12,2)	Presupuesto total	NULLABLE
start_date	DATE	Fecha inicio	NULLABLE
end_date	DATE	Fecha fin	NULLABLE
synced_at	DATETIME	Última sincronización	NOT NULL
3.3 Entidad: ads_metrics_daily
Métricas diarias por campaña para análisis histórico.
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno	PRIMARY KEY
campaign_sync_id	INT	Campaña sincronizada	FK ads_campaign_sync.id
date	DATE	Fecha de las métricas	NOT NULL, INDEX
impressions	INT	Impresiones	DEFAULT 0
clicks	INT	Clics	DEFAULT 0
spend	DECIMAL(12,2)	Gasto en moneda cuenta	DEFAULT 0
conversions	INT	Conversiones	DEFAULT 0
conversion_value	DECIMAL(12,2)	Valor de conversiones	DEFAULT 0
reach	INT	Alcance (Meta)	DEFAULT 0
frequency	DECIMAL(5,2)	Frecuencia media	DEFAULT 0
cpc	DECIMAL(8,4)	Costo por clic	COMPUTED
cpm	DECIMAL(8,4)	Costo por mil	COMPUTED
ctr	DECIMAL(6,4)	Click-through rate	COMPUTED
roas	DECIMAL(8,4)	Return on ad spend	COMPUTED
 
3.4 Entidad: ads_audience_sync
Audiencias sincronizadas con plataformas desde segmentos CRM.
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno	PRIMARY KEY
ads_account_id	INT	Cuenta publicitaria	FK ads_account.id
crm_segment_id	INT	Segmento CRM origen	FK jaraba_crm.segment_id, NULLABLE
audience_id	VARCHAR(128)	ID audiencia en plataforma	NOT NULL
audience_name	VARCHAR(255)	Nombre de la audiencia	NOT NULL
audience_type	VARCHAR(32)	Tipo de audiencia	ENUM: custom|lookalike|saved
size_estimate	INT	Tamaño estimado	NULLABLE
sync_direction	VARCHAR(16)	Dirección de sync	ENUM: push|pull
last_sync_at	DATETIME	Última sincronización	NULLABLE
sync_status	VARCHAR(32)	Estado de sync	ENUM: synced|pending|error
3.5 Entidad: ads_conversion_event
Eventos de conversión offline enviados a plataformas.
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno	PRIMARY KEY
ads_account_id	INT	Cuenta publicitaria	FK ads_account.id
event_name	VARCHAR(64)	Nombre del evento	NOT NULL
event_time	DATETIME	Timestamp del evento	NOT NULL
email_hash	VARCHAR(64)	Email SHA-256	NULLABLE
phone_hash	VARCHAR(64)	Teléfono SHA-256	NULLABLE
value	DECIMAL(12,2)	Valor de conversión	NULLABLE
currency	VARCHAR(3)	Moneda	DEFAULT 'EUR'
crm_opportunity_id	INT	Oportunidad CRM origen	FK crm_opportunity.id, NULLABLE
sent_at	DATETIME	Enviado a plataforma	NULLABLE
send_status	VARCHAR(32)	Estado de envío	ENUM: pending|sent|error
platform_response	JSON	Respuesta de la API	NULLABLE
 
4. APIs REST
4.1 Endpoints de Cuentas
Método	Endpoint	Descripción
GET	/api/v1/ads/accounts	Listar cuentas conectadas
GET	/api/v1/ads/accounts/{uuid}	Detalle de cuenta
POST	/api/v1/ads/accounts/connect/meta	Iniciar OAuth Meta
POST	/api/v1/ads/accounts/connect/google	Iniciar OAuth Google
DELETE	/api/v1/ads/accounts/{uuid}	Desconectar cuenta
POST	/api/v1/ads/accounts/{uuid}/sync	Forzar sincronización
4.2 Endpoints de Campañas y Métricas
Método	Endpoint	Descripción
GET	/api/v1/ads/campaigns	Listar campañas (filtro por cuenta)
GET	/api/v1/ads/campaigns/{id}/metrics	Métricas de campaña (date_from, date_to)
GET	/api/v1/ads/dashboard	Dashboard consolidado cross-platform
GET	/api/v1/ads/performance/summary	Resumen de rendimiento por período
4.3 Endpoints de Audiencias
Método	Endpoint	Descripción
GET	/api/v1/ads/audiences	Listar audiencias
POST	/api/v1/ads/audiences/sync-from-crm	Crear/actualizar audiencia desde segmento CRM
POST	/api/v1/ads/audiences/{id}/lookalike	Crear lookalike desde audiencia existente
4.4 Endpoints de Conversiones
Método	Endpoint	Descripción
POST	/api/v1/ads/conversions	Registrar conversión offline
GET	/api/v1/ads/conversions	Listar conversiones enviadas
POST	/api/v1/ads/conversions/batch	Enviar lote de conversiones
4.5 Ejemplo: Sincronizar Audiencia desde CRM
POST /api/v1/ads/audiences/sync-from-crm
{   "ads_account_uuid": "acc-123e4567-e89b",   "crm_segment_id": 42,   "audience_name": "High-Value Leads Q1",   "create_lookalike": true,   "lookalike_country": "ES",   "lookalike_ratio": 0.01 }  Response 201: {   "success": true,   "data": {     "audience_uuid": "aud-987fcdeb-51a2",     "audience_id": "23842971650123",     "platform": "meta",     "size_estimate": 15420,     "sync_status": "synced",     "lookalike_created": true,     "lookalike_id": "23842971650456"   } }
 
5. Flujos de Automatización ECA
5.1 Sync Diario de Métricas
Componente	Configuración
Trigger	Cron: 06:00 UTC diario
Condición	ads_account.status = 'active'
Acción 1	Para cada cuenta: llamar API de métricas (yesterday)
Acción 2	Insertar/actualizar ads_metrics_daily
Acción 3	Actualizar ads_account.last_sync_at
Error Handler	Registrar en sync_errors, notificar si 3 errores consecutivos
5.2 Alerta de Presupuesto Agotándose
Componente	Configuración
Trigger	Post-sync métricas diarias
Condición	(daily_budget - spend) / daily_budget < 0.2 (menos de 20% restante)
Acción 1	Enviar email al admin del tenant con alerta
Acción 2	Crear notificación in-app
Throttle	Máximo 1 alerta por campaña cada 24h
5.3 Conversión Offline al Cerrar Oportunidad CRM
Componente	Configuración
Trigger	crm_opportunity.stage cambia a 'won'
Condición	opportunity.source_channel IN ('meta_ads', 'google_ads')
Acción 1	Obtener contact.email y contact.phone
Acción 2	Hash SHA-256 de email y phone
Acción 3	Crear ads_conversion_event con status='pending'
Acción 4	Encolar job para enviar a Conversions API
5.4 Sync Semanal de Audiencias CRM
Componente	Configuración
Trigger	Cron: Lunes 03:00 UTC
Condición	ads_audience_sync.sync_direction = 'push' AND vinculado a crm_segment
Acción 1	Obtener contactos actualizados del segmento CRM
Acción 2	Hash emails/phones con SHA-256
Acción 3	Llamar Custom Audience API con replace mode
Acción 4	Actualizar size_estimate y last_sync_at
 
6. Integración con FOC
El módulo Paid Ads Integration alimenta métricas críticas de adquisición al Financial Operations Center (jaraba_foc) para el cálculo preciso de CAC (Customer Acquisition Cost) y atribución de ingresos.
6.1 Métricas Enviadas a FOC
Métrica	Cálculo	Frecuencia
Ad Spend MTD	SUM(ads_metrics_daily.spend) WHERE date >= first_of_month	Diario 07:00 UTC
Ad Spend by Channel	Spend agrupado por platform (meta/google)	Diario 07:00 UTC
Conversions MTD	COUNT conversiones offline enviadas	Diario 07:00 UTC
Blended CAC	Total Ad Spend / New Customers (desde CRM)	Calculado en FOC
ROAS by Campaign	conversion_value / spend por campaña	Semanal
6.2 API Callback a FOC
POST /api/v1/foc/metrics/ads-performance {   "tenant_id": 1,   "period": "2026-01",   "metrics": {     "total_spend": 5420.50,     "spend_by_platform": {       "meta": 3200.00,       "google": 2220.50     },     "total_conversions": 127,     "total_conversion_value": 45600.00,     "blended_cpl": 42.68,     "blended_roas": 8.41   } }
7. Roadmap de Implementación
7.1 Sprint 1: Core Setup (5-6h)
•	Crear entidades ads_account, ads_campaign_sync, ads_metrics_daily (2h)
•	Implementar OAuth flow para Meta Business (2h)
•	UI básica de conexión de cuentas (1-2h)
7.2 Sprint 2: Google Ads + Sync (5-6h)
•	Implementar OAuth flow para Google Ads (2h)
•	Crear job de sincronización diaria de métricas (2h)
•	Dashboard básico con KPIs consolidados (1-2h)
7.3 Sprint 3: Audiences + Conversions (5-8h)
•	Crear entidades ads_audience_sync, ads_conversion_event (1h)
•	Implementar Custom Audience sync desde CRM (2-3h)
•	Implementar Conversions API (Meta + Google) (2-3h)
•	ECA: conversión offline al cerrar oportunidad (1h)
7.4 Resumen de Horas
Sprint	Horas Min	Horas Max
Sprint 1: Core Setup	5h	6h
Sprint 2: Google Ads + Sync	5h	6h
Sprint 3: Audiences + Conversions	5h	8h
TOTAL	15h	20h
--- Fin del Documento ---
Jaraba Impact Platform | Marketing AI Stack | Enero 2026
