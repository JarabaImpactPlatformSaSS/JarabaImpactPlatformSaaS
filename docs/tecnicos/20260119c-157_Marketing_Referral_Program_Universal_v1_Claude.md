REFERRAL PROGRAM UNIVERSAL
Extensión jaraba_onboarding
Sistema de Referidos con Códigos, Recompensas Configurables y Leaderboard
Versión:	1.0
Fecha:	Enero 2026
Código:	157_Marketing_Referral_Program_Universal_v1
Estado:	Especificación Técnica para Implementación
Horas Estimadas:	8-12 horas
Módulo Base:	jaraba_onboarding
Dependencias:	jaraba_core, jaraba_email, Stripe Connect
1. Resumen Ejecutivo
El Referral Program Universal proporciona un sistema completo de marketing de referidos aplicable a todos los verticales de la plataforma. Permite a usuarios existentes invitar nuevos usuarios mediante códigos únicos, con recompensas configurables para ambas partes (referidor y referido) y gamificación mediante leaderboards y niveles de embajador.
1.1 Capacidades Principales
•	Códigos de referido únicos por usuario
•	Recompensas configurables: créditos, descuentos, meses gratis
•	Sistema dual: reward para referidor + bonus para referido
•	Leaderboard público con ranking de embajadores
•	Niveles de embajador con beneficios incrementales
•	Multi-tenant con configuración por vertical
•	Integración con Stripe para payouts automáticos
1.2 Modelos de Recompensa
Modelo	Descripción	Uso Típico
Credit	Créditos en cuenta para servicios	Plataformas freemium
Discount %	Descuento porcentual en próxima compra	E-commerce
Discount Fixed	Descuento fijo en euros	Suscripciones
Free Month	Mes(es) gratis de suscripción	SaaS B2B
Cash	Pago directo vía Stripe Connect	Programas afiliados
Points	Puntos canjeables por beneficios	Gamificación
2. Arquitectura Técnica
2.1 Entidad: referral_program
Configuración del programa de referidos por tenant.
Campo	Tipo	Descripción
id	SERIAL	Primary key
uuid	VARCHAR(36)	Identificador público único
tenant_id	INT FK	Referencia a tenant (único por tenant)
name	VARCHAR(100)	Nombre del programa
is_active	BOOLEAN	Programa activo/inactivo
referrer_reward_type	VARCHAR(20)	credit|discount_pct|discount_fixed|free_month|cash|points
referrer_reward_value	DECIMAL(10,2)	Valor de la recompensa (€, %, meses, puntos)
referee_reward_type	VARCHAR(20)	Tipo de bonus para el referido
referee_reward_value	DECIMAL(10,2)	Valor del bonus
conversion_event	VARCHAR(50)	signup|subscription|first_purchase|custom
min_conversion_value	DECIMAL(10,2)	Valor mínimo para activar recompensa (si aplica)
max_referrals_per_user	INT	Límite de referidos por usuario (NULL = ilimitado)
reward_expires_days	INT	Días hasta expiración de recompensa (NULL = nunca)
double_sided	BOOLEAN	Recompensa para ambas partes
terms_url	VARCHAR(500)	URL a términos del programa
created_at	TIMESTAMP	Fecha de creación
updated_at	TIMESTAMP	Última actualización
2.2 Entidad: referral_code
Códigos de referido únicos por usuario.
Campo	Tipo	Descripción
id	SERIAL	Primary key
program_id	INT FK	Referencia a referral_program
user_id	INT FK	Usuario propietario del código
code	VARCHAR(20)	Código único (ej: PEPE2024, JUAN-REF)
custom_code	BOOLEAN	Código personalizado por usuario
total_clicks	INT	Veces que se ha usado el link
total_signups	INT	Registros conseguidos
total_conversions	INT	Conversiones completadas
total_earned	DECIMAL(10,2)	Total ganado en recompensas
is_active	BOOLEAN	Código activo/desactivado
created_at	TIMESTAMP	Fecha de creación
 
2.3 Entidad: referral
Registro de cada referido y su estado.
Campo	Tipo	Descripción
id	SERIAL	Primary key
uuid	VARCHAR(36)	Identificador público único
code_id	INT FK	Código utilizado
referrer_user_id	INT FK	Usuario que refiere
referee_user_id	INT FK NULL	Usuario referido (después de signup)
referee_email	VARCHAR(255)	Email del referido (pre-signup)
status	VARCHAR(20)	clicked|signed_up|converted|rewarded|expired
clicked_at	TIMESTAMP	Fecha de click en link
signed_up_at	TIMESTAMP	Fecha de registro
converted_at	TIMESTAMP	Fecha de conversión
conversion_value	DECIMAL(10,2)	Valor de la conversión (compra, suscripción)
referrer_reward_id	INT FK NULL	Recompensa del referidor
referee_reward_id	INT FK NULL	Bonus del referido
utm_source	VARCHAR(100)	Canal de compartición
ip_address	VARCHAR(45)	IP del referido
user_agent	VARCHAR(500)	User agent del navegador
2.4 Entidad: referral_reward
Recompensas generadas y su estado de uso.
Campo	Tipo	Descripción
id	SERIAL	Primary key
uuid	VARCHAR(36)	Identificador público único
user_id	INT FK	Usuario beneficiario
referral_id	INT FK	Referido que generó la recompensa
reward_type	VARCHAR(20)	Tipo de recompensa
reward_value	DECIMAL(10,2)	Valor
status	VARCHAR(20)	pending|available|used|expired|cancelled
available_at	TIMESTAMP	Fecha desde que está disponible
expires_at	TIMESTAMP	Fecha de expiración
used_at	TIMESTAMP	Fecha de uso
used_on_order_id	VARCHAR(100)	ID de orden donde se aplicó
stripe_payout_id	VARCHAR(100)	ID del payout (si tipo = cash)
created_at	TIMESTAMP	Fecha de creación
3. API REST Endpoints
3.1 Gestión del Programa (Admin)
Método	Endpoint	Descripción
GET	/api/v1/referral/program	Obtener configuración del programa
PUT	/api/v1/referral/program	Actualizar configuración
GET	/api/v1/referral/stats	Estadísticas globales del programa
GET	/api/v1/referral/leaderboard	Ranking de embajadores
GET	/api/v1/referral/referrals	Listar todos los referidos
3.2 Endpoints de Usuario
Método	Endpoint	Descripción
GET	/api/v1/referral/my-code	Obtener mi código de referido
PUT	/api/v1/referral/my-code	Personalizar mi código
GET	/api/v1/referral/my-referrals	Mis referidos y su estado
GET	/api/v1/referral/my-rewards	Mis recompensas disponibles
GET	/api/v1/referral/my-stats	Mi posición en el ranking
POST	/api/v1/referral/invite	Enviar invitación por email
3.3 Tracking Público
Método	Endpoint	Descripción
GET	/r/{code}	Link de referido (redirect + tracking)
POST	/api/v1/referral/validate-code	Validar código en formulario registro
POST	/api/v1/referral/apply-code	Aplicar código durante checkout
 
4. Flujos ECA (Automatización)
4.1 ECA: Generación de Código al Registrarse
Trigger: Usuario completa registro en plataforma
1.	Verificar que programa de referidos está activo
2.	Generar código único: NOMBRE + 4 random chars (ej: PEPE-X7K2)
3.	Verificar unicidad del código, regenerar si existe
4.	Crear referral_code asociado al usuario
5.	Enviar email de bienvenida con link de referido
4.2 ECA: Tracking de Click en Link
Trigger: GET /r/{code}
6.	Buscar referral_code por código
7.	Si no existe o inactivo → Redirect a home
8.	Incrementar total_clicks en referral_code
9.	Crear referral con status = 'clicked'
10.	Setear cookie jaraba_ref = code (30 días)
11.	Redirect a landing de registro con ?ref={code}
4.3 ECA: Conversión de Referido
Trigger: Evento de conversión según program.conversion_event
12.	Buscar referral por referee_user_id con status = 'signed_up'
13.	Verificar min_conversion_value si aplica
14.	Actualizar referral.status = 'converted'
15.	Guardar conversion_value
16.	Si program.double_sided = true:
•	Crear referral_reward para referrer
•	Crear referral_reward para referee
17.	Actualizar contadores en referral_code
18.	Notificar a referrer por email/push
19.	Actualizar leaderboard
4.4 ECA: Procesamiento de Payout (Cash Rewards)
Trigger: Semanal (lunes 09:00) o manual
20.	Buscar rewards con type='cash' y status='available'
21.	Agrupar por user_id
22.	Para cada usuario con Stripe Connect account:
•	Crear payout vía Stripe Connect
•	Guardar stripe_payout_id en cada reward
•	Marcar status = 'used'
23.	Enviar resumen de payout por email
5. Gamificación y Leaderboard
5.1 Niveles de Embajador
Nivel	Requisito	Beneficios
🌱 Semilla	0-4 conversiones	Recompensa base por referido
🌿 Brote	5-14 conversiones	+10% bonus en recompensas
🌳 Árbol	15-29 conversiones	+20% bonus + badge público
🏆 Embajador	30-49 conversiones	+30% bonus + código personalizado
⭐ Embajador Elite	50+ conversiones	+50% bonus + acceso anticipado + swag
5.2 Leaderboard Público
Ranking visible con los top referidores del mes/trimestre/año:
•	Top 10 visible públicamente
•	Avatar + nombre + nivel + conversiones del periodo
•	Opción de mostrar/ocultar en perfil de usuario
•	Filtros: mes actual, trimestre, año, all-time
•	Premios especiales para top 3 del mes
 
6. Componentes Frontend
6.1 Dashboard de Referidos (Usuario)
•	Mi código con botones de copiar y compartir
•	Link directo formateado para compartir
•	Estadísticas: clicks, registros, conversiones, ganado
•	Lista de referidos con status visual
•	Mis recompensas disponibles y usadas
•	Progreso hacia siguiente nivel
•	Formulario para invitar por email
6.2 Widget de Invitación
Componente embebible en cualquier página para promover el programa:
•	Botones de compartir: WhatsApp, LinkedIn, Twitter, Email, Copy
•	Mensaje pre-formateado optimizado por canal
•	QR code para compartir en persona
•	Personalizable por tenant (colores, textos)
6.3 Leaderboard Widget
•	Top 10 embajadores con avatar y estadísticas
•	Tu posición actual destacada
•	Filtro de periodo temporal
•	Animación de confetti al subir de posición
7. Emails del Sistema
Email	Trigger
Bienvenida con código	Usuario completa registro
¡Tienes un nuevo referido!	Referido hace signup
¡Has ganado una recompensa!	Referido convierte
Tu recompensa está disponible	Reward cambia a 'available'
Resumen mensual de referidos	Primer día del mes
¡Subiste de nivel!	Usuario alcanza nuevo nivel
Tu recompensa expira pronto	7 días antes de expires_at
Payout procesado	Stripe payout completado
8. Roadmap de Implementación
Sprint	Entregables	Horas
Sprint 1	Entidades DB, API programa y códigos, generación automática	3-4h
Sprint 2	Tracking (link redirect, cookies), registro de referidos	2-3h
Sprint 3	Sistema de recompensas, ECA flows, notificaciones	2-3h
Sprint 4	Frontend (dashboard, widgets), leaderboard, QA	1-2h
Total estimado: 8-12 horas
9. Configuración por Vertical
Vertical	Reward Referrer	Bonus Referee	Conversion
Empleabilidad	1 mes gratis Pro	20% dto primer mes	Subscription
Emprendimiento	€50 crédito	€25 crédito	First purchase
AgroConecta	5% comisión reducida	Envío gratis 1ª compra	First order
ComercioConecta	€20 por referido	10% dto 1ª compra	First order
ServiciosConecta	10% de 1ª factura	Consulta gratis	First booking
10. Métricas y KPIs
•	Viral Coefficient: referrals / users (objetivo: >1)
•	Referral Conversion Rate: conversions / signups
•	Cost per Acquisition (via referral) vs otros canales
•	LTV de usuarios referidos vs no referidos
•	% de usuarios activos que comparten código
•	Share rate por canal (WhatsApp vs email vs LinkedIn)
•	Tiempo medio click → signup → conversion
