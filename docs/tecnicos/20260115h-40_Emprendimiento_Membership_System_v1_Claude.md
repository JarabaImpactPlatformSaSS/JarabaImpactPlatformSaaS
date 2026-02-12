SISTEMA DE MEMBRESÍAS
Club Jaraba
Vertical de Emprendimiento Digital
JARABA IMPACT PLATFORM

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	40_Emprendimiento_Membership_System
Dependencias:	Stripe Subscriptions, Group Module
 
1. Resumen Ejecutivo
El Sistema de Membresías 'Club Jaraba' proporciona acceso continuo a recursos, comunidad y soporte para emprendedores que completan el programa inicial o desean acceso premium. Genera ingresos recurrentes (MRR) para el ecosistema mientras mantiene el engagement post-programa.
1.1 Niveles de Membresía
Nivel	Precio/Mes	Target	Descripción
Básico	€19/mes	Post-programa	Acceso comunidad + recursos básicos
Pro	€49/mes	Negocio activo	Kits premium + mentoría grupal mensual
Elite	€99/mes	Escalando	Todo + mentoría 1:1 + kits sector
Anual Básico	€179/año	Compromiso largo	10 meses por precio de 12
Anual Elite	€899/año	Power users	Elite con 2 meses gratis
1.2 Modelo de Negocio
•	Ingresos recurrentes: MRR predecible para sostenibilidad del ecosistema
•	Retención post-programa: Mantener engagement de graduados
•	Upsell natural: Progresión Básico → Pro → Elite
•	Lifetime Value: Incrementar LTV con retención mensual
 
2. Matriz de Beneficios
Beneficio	Básico	Pro	Elite
Acceso comunidad (Groups)	✓	✓	✓
Foro de discusión	✓	✓	✓
Biblioteca de recursos	Básicos	Premium	Todo
Kits digitales	1-2	Todos básicos	Todos + Sector
Webinars mensuales	Grabaciones	En vivo	En vivo + Q&A privado
Mentoría grupal	—	1/mes	2/mes
Mentoría 1:1	—	—	1 hora/mes
Descuento cursos	10%	20%	40%
Descuento consultoría	—	15%	30%
Eventos networking	Acceso	Prioritario	VIP + Ponente invitado
Directorio de miembros	✓	✓ + Destacado	✓ + Badge Elite
AI Copilot	5 queries/día	20 queries/día	Ilimitado
Soporte	Email	Email + Chat	Email + Chat + Prioritario
 
3. Arquitectura de Datos
3.1 Entidad: membership_plan
Campo	Tipo	Descripción
id	Serial	PRIMARY KEY
name	VARCHAR(64)	Nombre del plan (Básico, Pro, Elite)
slug	VARCHAR(32)	Identificador URL
description	TEXT	Descripción completa
level	INT	Nivel jerárquico (1=básico, 2=pro, 3=elite)
billing_period	VARCHAR(16)	ENUM: monthly|quarterly|yearly
price	DECIMAL(8,2)	Precio en €
stripe_price_id	VARCHAR(64)	ID del Price en Stripe
stripe_product_id	VARCHAR(64)	ID del Product en Stripe
benefits	JSON	Lista estructurada de beneficios
kit_access	JSON	IDs de kits incluidos
group_ids	JSON	Groups a los que da acceso
ai_query_limit	INT	Límite queries AI copilot/día
mentoring_hours	INT	Horas mentoría incluidas/mes
discount_courses	INT	% descuento en cursos
discount_consulting	INT	% descuento en consultoría
is_active	BOOLEAN	Plan disponible para compra
sort_order	INT	Orden en pricing page
3.2 Entidad: user_membership
Campo	Tipo	Descripción
id	Serial	PRIMARY KEY
user_id	INT	FK users.uid
plan_id	INT	FK membership_plan.id
stripe_subscription_id	VARCHAR(64)	ID de Subscription en Stripe
stripe_customer_id	VARCHAR(64)	ID de Customer en Stripe
status	VARCHAR(16)	ENUM: active|past_due|canceled|paused|trialing
started_at	DATETIME	Inicio de la membresía
current_period_start	DATE	Inicio período actual
current_period_end	DATE	Fin período actual
canceled_at	DATETIME	Fecha de cancelación
cancel_at_period_end	BOOLEAN	Cancelará al final del período
trial_ends_at	DATETIME	Fin del trial (si aplica)
mentoring_hours_used	INT	Horas mentoría usadas este mes
ai_queries_today	INT	Queries AI usadas hoy
ai_queries_reset_at	DATE	Fecha reset contador AI
upgrade_from	INT	Plan anterior si upgrade
referral_code	VARCHAR(32)	Código de referido usado
 
4. Integración Stripe Subscriptions
4.1 Flujo de Suscripción
1.	Usuario selecciona plan en /membership/pricing
2.	Sistema crea Checkout Session de Stripe con mode='subscription'
3.	Usuario completa pago en Stripe Checkout
4.	Webhook checkout.session.completed dispara creación de user_membership
5.	Sistema otorga acceso a Groups, kits y beneficios del plan
6.	Email de bienvenida con guía de beneficios
4.2 Webhooks Gestionados
Webhook	Acción
checkout.session.completed	Crear user_membership, otorgar accesos
invoice.paid	Renovar período, resetear contadores
invoice.payment_failed	status='past_due', email de aviso
customer.subscription.updated	Actualizar plan si upgrade/downgrade
customer.subscription.deleted	status='canceled', revocar accesos
4.3 Gestión de Cambios
•	Upgrade: Prorrateo automático, acceso inmediato a nuevo nivel
•	Downgrade: Efectivo al final del período actual
•	Cancelación: Mantiene acceso hasta fin de período pagado
•	Pausa: Máximo 3 meses, luego cancela automáticamente
 
5. Métricas de Membresía
Métrica	Fórmula	Target
MRR	SUM(precio mensual de activos)	Crecimiento 10% MoM
Churn Rate	Cancelaciones / Activos inicio mes	< 5% mensual
Net Revenue Retention	(MRR fin - Churn + Expansion) / MRR inicio	> 100%
ARPU	MRR / Miembros activos	> €35
Conversion to Paid	Membresías / Graduados programa	> 30%
Upgrade Rate	Upgrades / Total membresías	> 10% anual
Trial to Paid	Conversiones / Trials iniciados	> 50%
 
6. Automatizaciones ECA
6.1 ECA-MEM-001: Onboarding Miembro
7.	Trigger: user_membership.status = 'active' (nuevo)
8.	Añadir usuario a Groups del plan
9.	Otorgar acceso a kits incluidos
10.	Enviar email bienvenida con guía de beneficios
11.	Programar secuencia onboarding (días 1, 3, 7)
12.	Añadir badge de nivel al perfil
6.2 ECA-MEM-002: Retención Pre-Churn
13.	Trigger: cancel_at_period_end = TRUE
14.	Enviar email de retención con oferta especial
15.	Si plan Pro/Elite: asignar llamada de retención a gestor
16.	Ofrecer pausa como alternativa a cancelación
17.	Encuesta de motivo de cancelación
 
7. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/membership/plans	Lista de planes disponibles
GET	/api/v1/membership/my-membership	Membresía actual del usuario
POST	/api/v1/membership/subscribe	Crear checkout session
POST	/api/v1/membership/upgrade	Upgrade de plan
POST	/api/v1/membership/downgrade	Downgrade de plan
POST	/api/v1/membership/cancel	Cancelar al final del período
POST	/api/v1/membership/pause	Pausar suscripción
POST	/api/v1/membership/resume	Reactivar suscripción pausada
GET	/api/v1/membership/invoices	Historial de facturas
POST	/api/v1/membership/update-payment	Actualizar método de pago
8. Roadmap de Implementación
Sprint	Timeline	Entregables
Sprint 1	Semana 1-2	Entidades. Integración Stripe Subscriptions.
Sprint 2	Semana 3-4	Webhooks. Gestión de accesos automática.
Sprint 3	Semana 5-6	Pricing page. Checkout flow. Portal cliente.
Sprint 4	Semana 7-8	Upgrade/downgrade. Cancelación. Pausa.
Sprint 5	Semana 9-10	ECA automations. Métricas. QA.
--- Fin del Documento ---
