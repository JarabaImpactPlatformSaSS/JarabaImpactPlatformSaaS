
WHITE-LABEL & RESELLER PLATFORM
Especificación Técnica para Implementación
JARABA IMPACT PLATFORM

Versión:	1.0
Fecha:	Enero 2026
Código:	117_Platform_WhiteLabel_v1
Estado:	Especificación para EDI
 
1. Resumen Ejecutivo
Sistema de White-Label completo para franquicias y resellers. Incluye custom domains, emails brandados, PDF templates personalizados, y portal de gestión de sub-tenants.
1.1 Objetivos del Sistema
Objetivo	Métrica Target	Benchmark
Franquicias activas	10+ en Y1	Modelo territorial
Time to white-label	< 24 horas	Setup automatizado
Customization depth	90%+ brandable	Full white-label
Partner revenue share	20-40%	Industry standard
Sub-tenant satisfaction	> 4.5/5	Partner success
1.2 Componentes del Sistema
•	Custom Domains: Dominios propios con SSL automático
•	Brand Customization: Logo, colores, tipografía configurables
•	Email Templates: Emails transaccionales brandados (MJML)
•	PDF Templates: Facturas, certificados, reportes con branding
•	Reseller Portal: Gestión de sub-tenants y comisiones
•	Territory Management: Asignación de zonas geográficas
 
2. Arquitectura del Sistema
2.1 Stack Tecnológico
Componente	Tecnología	Justificación
Custom Domains	Caddy Server + Let's Encrypt	SSL automático gratuito
DNS Verification	Cloudflare API	DNS-01 challenge
Email Templates	MJML + Handlebars	Responsive + dinámico
Email Delivery	Postmark/SendGrid	Deliverability alta
PDF Generation	Puppeteer + Handlebars	HTML to PDF
Theme Engine	CSS Variables + Drupal Theme	Dinámico por tenant
2.2 Flujo de Custom Domain
┌─────────────────────────────────────────────────────────────────┐
│                 CUSTOM DOMAIN FLOW                              │
├─────────────────────────────────────────────────────────────────┤
│  1. Partner solicita dominio: partner.example.com              │
│                           │                                    │
│                           ▼                                    │
│  2. Sistema genera registro CNAME: cname.jaraba.io             │
│                           │                                    │
│                           ▼                                    │
│  3. Partner configura DNS en su proveedor                      │
│                           │                                    │
│                           ▼                                    │
│  4. Sistema verifica DNS propagation (polling)                 │
│                           │                                    │
│                           ▼                                    │
│  5. Caddy solicita certificado Let's Encrypt                  │
│                           │                                    │
│                           ▼                                    │
│  6. Dominio activo con SSL. Redirect configurado.              │
└─────────────────────────────────────────────────────────────────┘
 
3. Modelo de Datos
3.1 Entidad: whitelabel_config
Configuración de white-label por tenant/partner.
Campo	Tipo	Requerido	Descripción
id	UUID	Sí	Identificador único
tenant_id	UUID FK	Sí	Tenant con white-label
brand_name	VARCHAR(255)	Sí	Nombre de marca
logo_url	VARCHAR(500)	Sí	URL del logo principal
logo_dark_url	VARCHAR(500)	No	Logo para modo oscuro
favicon_url	VARCHAR(500)	No	URL del favicon
primary_color	VARCHAR(7)	Sí	Color primario (#HEX)
secondary_color	VARCHAR(7)	Sí	Color secundario
accent_color	VARCHAR(7)	No	Color de acento
font_family	VARCHAR(100)	No	Familia tipográfica
custom_css	TEXT	No	CSS personalizado adicional
footer_text	TEXT	No	Texto de footer personalizado
support_email	VARCHAR(255)	No	Email de soporte
support_phone	VARCHAR(50)	No	Teléfono de soporte
created_at	TIMESTAMP	Sí	Fecha creación
3.2 Entidad: custom_domain
Dominios personalizados asociados a tenants.
Campo	Tipo	Requerido	Descripción
id	UUID	Sí	Identificador único
tenant_id	UUID FK	Sí	Tenant propietario
domain	VARCHAR(255)	Sí	Dominio completo
verification_token	VARCHAR(100)	Sí	Token para DNS verification
status	ENUM	Sí	pending|verifying|active|error
ssl_status	ENUM	Sí	pending|provisioning|active|error
ssl_expires_at	TIMESTAMP	No	Expiración del certificado
is_primary	BOOLEAN	Sí	¿Es el dominio principal?
last_verified_at	TIMESTAMP	No	Última verificación exitosa
error_message	TEXT	No	Mensaje de error si aplica
created_at	TIMESTAMP	Sí	Fecha registro
3.3 Entidad: email_template
Templates de email personalizables por tenant.
Campo	Tipo	Requerido	Descripción
id	UUID	Sí	Identificador único
tenant_id	UUID FK	No	Tenant (null = global)
template_key	VARCHAR(100)	Sí	welcome|order_confirm|password_reset
subject	VARCHAR(255)	Sí	Asunto del email
body_mjml	TEXT	Sí	Contenido en MJML
body_text	TEXT	Sí	Versión texto plano
variables	JSON	Sí	Variables disponibles
is_active	BOOLEAN	Sí	¿Activo?
language	VARCHAR(5)	Sí	es|en|pt
3.4 Entidad: reseller
Partners resellers con sub-tenants.
Campo	Tipo	Requerido	Descripción
id	UUID	Sí	Identificador único
user_id	UUID FK	Sí	Usuario administrador
company_name	VARCHAR(255)	Sí	Nombre de empresa
territories	JSON	No	Zonas geográficas asignadas
commission_rate	DECIMAL(4,2)	Sí	% de comisión (ej: 20.00)
payment_method	ENUM	Sí	stripe|bank_transfer
stripe_account_id	VARCHAR(100)	No	Stripe Connect account
status	ENUM	Sí	pending|active|suspended
total_revenue	DECIMAL(12,2)	No	Revenue total generado
total_commission	DECIMAL(12,2)	No	Comisiones totales
tenant_count	INT	No	Sub-tenants activos
created_at	TIMESTAMP	Sí	Fecha registro
 
4. Email Templates Predefinidos
Template Key	Propósito	Variables
welcome	Bienvenida a nuevo usuario	user_name, brand_name, login_url
password_reset	Recuperar contraseña	user_name, reset_url, expires_in
order_confirmation	Confirmar pedido	order_id, items, total, delivery_date
order_shipped	Pedido enviado	order_id, tracking_number, tracking_url
invoice	Factura/recibo	invoice_number, items, total, pdf_url
job_application	Nueva aplicación	job_title, candidate_name, cv_url
job_match	Match de empleo	job_title, company, match_score
mentoring_reminder	Recordatorio sesión	mentor_name, datetime, meeting_url
certificate	Certificado emitido	course_name, credential_url
 
5. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/whitelabel/config	Obtener configuración actual
PUT	/api/v1/whitelabel/config	Actualizar configuración
POST	/api/v1/whitelabel/logo	Subir logo
GET	/api/v1/domains	Listar dominios del tenant
POST	/api/v1/domains	Añadir dominio personalizado
POST	/api/v1/domains/{id}/verify	Verificar DNS
DELETE	/api/v1/domains/{id}	Eliminar dominio
GET	/api/v1/email-templates	Listar templates
PUT	/api/v1/email-templates/{key}	Editar template
POST	/api/v1/email-templates/{key}/preview	Preview del email
GET	/api/v1/reseller/dashboard	Dashboard de reseller
GET	/api/v1/reseller/sub-tenants	Listar sub-tenants
POST	/api/v1/reseller/sub-tenants	Crear sub-tenant
GET	/api/v1/reseller/commissions	Historial de comisiones
 
6. Roadmap de Implementación
Sprint	Timeline	Entregables
Sprint 1	Semana 1-2	Entidades BD. Configuración básica de branding.
Sprint 2	Semana 3-4	Theme Engine dinámico. CSS variables por tenant.
Sprint 3	Semana 5-6	Custom Domains. Caddy + Let's Encrypt integration.
Sprint 4	Semana 7-8	Email Templates MJML. Editor visual básico.
Sprint 5	Semana 9-10	PDF Templates. Facturas y certificados brandados.
Sprint 6	Semana 11-12	Reseller Portal. Sub-tenant management. Go-live.
6.1 Estimación de Esfuerzo
Componente	Horas Estimadas
Brand Customization + Theme Engine	50-70h
Custom Domains + SSL	60-80h
Email Templates System	50-70h
PDF Templates	40-50h
Reseller Portal	60-80h
Commission Tracking	30-40h
TOTAL	290-390h
--- Fin del Documento ---
