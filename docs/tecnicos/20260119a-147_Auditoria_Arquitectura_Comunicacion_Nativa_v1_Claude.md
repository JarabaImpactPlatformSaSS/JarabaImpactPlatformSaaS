
AUDITORÍA DE ARQUITECTURA
Comunicación Nativa e Independencia de Terceros
CRM • Email Marketing • Social Media • Automatización

Versión:	1.0
Fecha:	Enero 2026
Estado:	Propuesta Estratégica
Código:	147_Auditoria_Arquitectura_Comunicacion_Nativa
Filosofía:	Sin Humo - Mínimas Dependencias Externas
 
1. Resumen Ejecutivo
Este documento audita la arquitectura actual del Ecosistema Jaraba para identificar dependencias externas y proponer una arquitectura consolidada que maximice el control interno, minimice costes recurrentes y reduzca el vendor lock-in.
1.1 Objetivo de la Auditoría
Evaluar la viabilidad de internalizar en Drupal + Make.com las funciones actualmente asignadas a:
•	CRM y gestión de pipeline de ventas B2B (¿HubSpot/Pipedrive?)
•	Email Marketing y automatización de secuencias (ActiveCampaign)
•	Publicación automatizada en Redes Sociales
•	Notificaciones multicanal (SMS, WhatsApp, Push)
1.2 Conclusión Principal
SÍ es viable y recomendable construir una arquitectura de comunicación 100% nativa usando Drupal + ECA + Make.com + proveedores de envío básicos (SendGrid/Twilio). Esta arquitectura elimina la necesidad de ActiveCampaign, HubSpot u otros CRMs externos, reduciendo costes a largo plazo y maximizando el control sobre los datos.
 
2. Inventario de Dependencias Externas Actuales
2.1 Servicios CRÍTICOS (Irremplazables)
Estos servicios son esenciales y NO se recomienda intentar reemplazarlos:
Servicio	Función	Por qué es irremplazable	Coste/mes
Stripe Connect	Pagos, split payments, KYC	Compliance financiero, escalabilidad global	~€200 (variable)
Claude API	IA generativa, copilots	Calidad de generación, strict grounding	~€150
Qdrant Cloud	Embeddings, RAG, matching	Multi-tenancy nativo, performance	~€50-100
IONOS Server	Hosting dedicado	Control total, compliance RGPD	€289
Cloudflare	CDN, WAF, DDoS	Seguridad perimetral crítica	€25
Total servicios críticos: ~€714-764/mes
2.2 Servicios REEMPLAZABLES (Candidatos a Internalización)
Servicio Actual	Función	Alternativa Nativa	Ahorro Potencial
ActiveCampaign Pro (€89-375/mes)	Email marketing, secuencias, CRM básico	Drupal ECA + SendGrid	€69-355/mes (solo ~€20 SendGrid)
HubSpot (€0-200/mes)	CRM ventas B2B, pipeline	Entidad opportunity en Drupal	€0-200/mes (100% ahorro)
Buffer/Hootsuite (€15-99/mes)	Programación social media	Make.com + APIs nativas	€15-99/mes (incluido en Make)
Mailchimp (€0-150/mes)	Newsletter masivo	AI Content Hub + SendGrid	Variable según volumen
2.3 Servicios de TRANSPORTE (Necesarios pero intercambiables)
Estos servicios son "tuberías" de envío. Son necesarios pero pueden cambiarse fácilmente:
Canal	Proveedor Recomendado	Alternativas	Coste
Email	SendGrid Pro	Amazon SES, Resend, Postmark	~€20-89/mes
SMS	Twilio	MessageBird, Vonage	~€0.07/SMS
WhatsApp	WhatsApp Business API (via Twilio)	Meta Cloud API directo	~€0.05-0.15/msg
Push Web/App	Firebase Cloud Messaging	OneSignal, Pusher	Gratis (FCM)
 
3. Arquitectura de Comunicación Nativa Propuesta
3.1 Visión General
La arquitectura propuesta centraliza toda la lógica de comunicación y CRM en Drupal, usando Make.com como orquestador de integraciones y proveedores externos solo como "tuberías" de envío sin lógica de negocio.
 ┌─────────────────────────────────────────────────────────────────────────┐ │                           DRUPAL 11 CORE                                │ │  ┌─────────────────┐ ┌─────────────────┐ ┌─────────────────────────────┐│ │  │  jaraba_crm     │ │ jaraba_email    │ │ jaraba_social               ││ │  │  ─────────────  │ │ ─────────────   │ │ ─────────────────────────   ││ │  │  • opportunity  │ │ • email_sequence│ │ • social_post               ││ │  │  • pipeline     │ │ • email_template│ │ • social_account            ││ │  │  • activity_log │ │ • email_tracking│ │ • social_analytics          ││ │  │  • task         │ │ • subscriber    │ │ • content_calendar          ││ │  └────────┬────────┘ └────────┬────────┘ └──────────────┬──────────────┘│ │           └───────────────────┴────────────────────────┘                │ │                               │ ECA Module                              │ │                               ▼                                         │ │  ┌──────────────────────────────────────────────────────────────────┐   │ │  │                    NOTIFICATION SERVICE                          │   │ │  │  • Canal routing  • Templates MJML  • Preferencias  • Analytics  │   │ │  └──────────────────────────────────────────────────────────────────┘   │ └─────────────────────────────────┬───────────────────────────────────────┘                                   │ Webhooks                                   ▼ ┌─────────────────────────────────────────────────────────────────────────┐ │                           MAKE.COM                                      │ │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────────┐ │ │  │ Delays &    │  │ Social      │  │ Marketplace │  │ External        │ │ │  │ Scheduling  │  │ Publishing  │  │ Sync        │  │ Notifications   │ │ │  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘  └────────┬────────┘ │ └─────────┴────────────────┴────────────────┴──────────────────┴──────────┘           │                │                │                  │           ▼                ▼                ▼                  ▼     ┌──────────┐    ┌──────────┐    ┌──────────┐        ┌──────────┐     │ SendGrid │    │ Meta API │    │ Amazon   │        │ Twilio   │     │ (Email)  │    │ LinkedIn │    │ eBay     │        │ SMS/WA   │     └──────────┘    │ Twitter  │    │ Google   │        └──────────┘                     └──────────┘    └──────────┘ 
3.2 Módulo jaraba_crm: CRM Nativo
Sistema de gestión de relaciones con clientes B2B integrado en Drupal.
Entidad: opportunity
Campo	Tipo	Descripción
id	SERIAL	PRIMARY KEY
uuid	VARCHAR(36)	Identificador único
name	VARCHAR(255)	Nombre de la oportunidad
contact_id	INT	FK → users.uid (contacto principal)
company_name	VARCHAR(255)	Nombre de la empresa/institución
stage_id	INT	FK → taxonomy (opportunity_stage)
value	DECIMAL(10,2)	Valor estimado en EUR
probability	INT	Probabilidad de cierre 0-100%
expected_close	DATE	Fecha esperada de cierre
owner_id	INT	FK → users.uid (comercial asignado)
source	VARCHAR(50)	Origen: web, referral, event, cold_outreach
motor_type	VARCHAR(20)	institutional, private, license
vertical_id	INT	FK → taxonomy (vertical)
notes	TEXT	Notas internas
metadata	JSON	Datos adicionales flexibles
created / updated	DATETIME	Timestamps automáticos
Taxonomía: opportunity_stage (Pipeline)
Etapa	Probabilidad	Color	Acción Típica
lead_nuevo	10%	Gris	Calificar interés
contactado	20%	Azul	Primera reunión
reunion_agendada	30%	Cyan	Preparar demo
demo_realizada	40%	Turquesa	Enviar propuesta
propuesta_enviada	50%	Amarillo	Follow-up
negociacion	70%	Naranja	Ajustar términos
cerrado_ganado	100%	Verde	Onboarding tenant
cerrado_perdido	0%	Rojo	Post-mortem
 
3.3 Módulo jaraba_email: Email Marketing Nativo
Sistema completo de email marketing con secuencias automatizadas, reemplazando ActiveCampaign.
Arquitectura del Motor de Secuencias
 [Evento Drupal] → [ECA: Evalúa condiciones] → [Enroll en Secuencia]                                                       │                                                       ▼                                           ┌─────────────────────────┐                                           │  email_enrollment       │                                           │  • user_id              │                                           │  • sequence_id          │                                           │  • current_step         │                                           │  • next_email_at        │                                           │  • status: active       │                                           └───────────┬─────────────┘                                                       │ [Cron cada 5min] → [Query: next_email_at <= NOW()] ───┘                            │                            ▼               ┌────────────────────────────┐               │ Por cada enrollment activo │               └────────────┬───────────────┘                            │                            ▼               ┌─────────────────────────────────────────┐               │ 1. Cargar email_sequence_step actual    │               │ 2. Renderizar template MJML con Twig    │               │ 3. Llamar SendGrid API                  │               │ 4. Registrar en email_tracking          │               │ 5. Calcular next_email_at o completar   │               └─────────────────────────────────────────┘ 
Secuencias por Vertical (Predefinidas)
Secuencia	Vertical	Emails	Duración
onboarding_job_seeker	Empleabilidad	5	7 días
onboarding_employer	Empleabilidad	4	5 días
onboarding_entrepreneur	Emprendimiento	6	14 días
onboarding_producer	AgroConecta	5	10 días
onboarding_merchant	ComercioConecta	5	10 días
onboarding_provider	ServiciosConecta	5	10 días
reactivation_inactive	Todas	4	21 días
churn_prevention	Todas	3	7 días
cart_abandoned	Commerce	3	72 horas
review_request	Commerce	2	14 días
 
3.4 Módulo jaraba_social: Publicación Automatizada en RRSS
Sistema de gestión y publicación automatizada en redes sociales integrado con el AI Content Hub.
Entidad: social_post
Campo	Tipo	Descripción
id / uuid	SERIAL / UUID	Identificadores
tenant_id	INT	FK → group.id
content_source_type	VARCHAR(20)	article, product, event, custom
content_source_id	INT	ID del contenido origen (si aplica)
platforms	JSON	["facebook", "instagram", "linkedin", "twitter"]
content_text	TEXT	Texto del post (puede variar por plataforma)
content_variants	JSON	{"facebook": "...", "twitter": "..."}
media_ids	JSON	Array de media entity IDs
link_url	VARCHAR(500)	URL a incluir (con UTM automático)
scheduled_at	DATETIME	Fecha/hora de publicación programada
status	VARCHAR(20)	draft, scheduled, published, failed
published_at	DATETIME	Fecha real de publicación
platform_post_ids	JSON	{"facebook": "123...", "linkedin": "456..."}
analytics	JSON	Métricas: impressions, engagement, clicks
ai_generated	BOOLEAN	Si fue generado por IA
Flujo de Publicación via Make.com
 [Drupal] Artículo publicado con social_share_enabled = TRUE          │          │ Webhook: article.published          ▼ [Make.com] Escenario: social_auto_publish          │          ├─[1]─ Generar variantes de texto por plataforma (Claude API)          │      • Twitter: max 280 chars + hashtags          │      • LinkedIn: profesional + call to action          │      • Facebook: casual + emoji          │      • Instagram: visual-first + hashtags abundantes          │          ├─[2]─ Preparar imágenes (resize si necesario)          │          ├─[3]─ Publicar en paralelo:          │      ├── Meta Graph API (FB + IG)          │      ├── LinkedIn API          │      └── Twitter API v2          │          ├─[4]─ Recoger post IDs de cada plataforma          │          └─[5]─ Callback a Drupal: actualizar social_post con IDs y status 
 
4. Comparativa de Costes: 3 Años
4.1 Escenario A: Con Servicios Externos (Actual)
Servicio	Setup	Año 1	Año 2	Año 3
ActiveCampaign Pro	€500	€1,188	€3,000	€4,500
HubSpot Starter (opcional)	€0	€240	€240	€240
Buffer/Hootsuite	€0	€180	€360	€600
Integración/Configuración	€2,000	€0	€500	€500
TOTAL ESCENARIO A	€2,500	€1,608	€4,100	€5,840
Total 3 años Escenario A: €14,048 + crecimiento por contactos
4.2 Escenario B: Arquitectura Nativa (Propuesta)
Concepto	Setup	Año 1	Año 2	Año 3
Desarrollo jaraba_crm	€4,000	€0	€0	€0
Desarrollo jaraba_email	€8,000	€0	€0	€0
Desarrollo jaraba_social	€3,000	€0	€0	€0
Templates MJML (30)	€2,000	€0	€500	€500
SendGrid Pro	€0	€240	€480	€720
Make.com Pro (ya incluido)	€0	€348	€348	€348
TOTAL ESCENARIO B	€17,000	€588	€1,328	€1,568
Total 3 años Escenario B: €20,484 (coste fijo, no escala con contactos)
4.3 Análisis de Break-Even
El Escenario B tiene mayor inversión inicial pero costes recurrentes mínimos. Con el crecimiento proyectado de contactos (de 1K a 30K en 3 años), ActiveCampaign escalaría de €99/mes a €500+/mes, mientras que SendGrid se mantiene en ~€60/mes. El break-even se alcanza aproximadamente al mes 30 (Año 2.5).
Beneficio adicional: El Escenario B elimina completamente el vendor lock-in. Todos los datos, lógica y automatizaciones son propiedad 100% de Jaraba.
 
5. Roadmap de Implementación
5.1 Fases de Desarrollo
Fase	Módulo	Entregables	Horas Est.
1	jaraba_crm Core	Entidades opportunity, task, activity_log. Admin UI. Views pipeline Kanban.	40-50h
2	jaraba_email Core	Entidades sequence, step, enrollment, tracking. SendGrid integration.	60-80h
3	jaraba_email ECA	Flujos ECA: enroll, process, goal_reached. Cron processing.	30-40h
4	Templates MJML	30 templates por vertical. Compilación MJML→HTML. Twig integration.	25-35h
5	jaraba_social Core	Entidades social_post, social_account. Admin UI calendario.	30-40h
6	Make.com Social	Escenarios: auto_publish, analytics_sync. Conexión APIs sociales.	20-30h
7	Analytics & Dashboard	Dashboard unificado CRM+Email+Social. Integración FOC.	30-40h
	TOTAL		235-315h (~€18.8K-25.2K)
5.2 Priorización Sugerida
Orden de implementación basado en dependencias y valor inmediato:
1.	Fase 2-4 (jaraba_email): Reemplaza ActiveCampaign inmediatamente. Mayor ahorro.
2.	Fase 1 (jaraba_crm): Necesario cuando haya >20 oportunidades B2B activas.
3.	Fase 5-6 (jaraba_social): Implementar junto con AI Content Hub (doc 128).
4.	Fase 7 (Analytics): Después de tener datos reales de uso.
 
6. Recomendaciones Finales
6.1 Decisión Estratégica
✅ RECOMENDADO: Arquitectura Nativa	❌ NO RECOMENDADO: Servicios Externos
• Control total sobre datos y lógica • Costes predecibles a largo plazo • Sin vendor lock-in • Personalización ilimitada • Coherente con filosofía 'Sin Humo' • Permite ofrecer como feature a tenants	• Dependencia de roadmap externo • Costes escalan con uso • Datos en silos externos • Limitaciones de personalización • Complejidad de integraciones • No diferencia el producto
6.2 Plan de Acción Inmediato
5.	Aprobar esta propuesta y asignar presupuesto para Fases 2-4 (jaraba_email)
6.	NO contratar ActiveCampaign Pro - usar plan gratuito temporal si es urgente
7.	Configurar cuenta SendGrid Pro (~€20/mes) para comenzar envíos transaccionales
8.	Incluir jaraba_crm en roadmap para Q2 2026 (cuando haya pipeline B2B real)
9.	Planificar jaraba_social junto con AI Content Hub (doc 128)
6.3 Documentos Relacionados
•	145_ActiveCampaign_Automation_Flows_v1 - Flujos de referencia (usar como base)
•	146_SendGrid_ECA_Architecture_v1 - Arquitectura técnica de email nativo
•	128_Platform_AI_Content_Hub_v2 - Integración con blog y newsletter
•	06_Core_Flujos_ECA_v1 - Patrones ECA del core
•	76/98_Notifications_System - Arquitectura multicanal existente

--- Fin del Documento ---
Jaraba Impact Platform | Auditoría Arquitectura Comunicación Nativa v1.0 | Enero 2026
