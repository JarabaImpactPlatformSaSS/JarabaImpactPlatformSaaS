
GDPR DPA TEMPLATES
Plantillas Legales Multi-Tenant para Compliance RGPD/LOPD-GDD
Nivel de Madurez: N1
JARABA IMPACT PLATFORM
Especificacion Tecnica para Implementacion
Version:	1.0
Fecha:	Febrero 2026
Codigo:	183_Platform_GDPR_DPA_Templates_v1
Estado:	Especificacion para EDI Google Antigravity
Nivel Madurez:	N1
Compliance:	GDPR, LOPD-GDD, ENS, ISO 27001
 
1. Resumen Ejecutivo
Este documento proporciona las plantillas legales operativas requeridas para el cumplimiento del RGPD/LOPD-GDD en un entorno SaaS multi-tenant. Mientras el doc 115 cubre la seguridad tecnica, este documento aborda los instrumentos legales que permiten operar comercialmente con datos personales: DPA, politicas de privacidad, banner de cookies, registro de actividades de tratamiento (RAT) y procedimientos de ejercicio de derechos.

1.1 Instrumentos Legales Cubiertos
Instrumento	Base Legal	Obligatorio	Scope
Data Processing Agreement (DPA)	Art. 28 RGPD	SI - antes de primer tenant	Por tenant
Politica de Privacidad	Art. 13-14 RGPD	SI - visible en cada vertical	Por vertical
Banner de Cookies	LSSI-CE + Dir. ePrivacy	SI - en todos los dominios	Global + vertical
Registro de Actividades (RAT)	Art. 30 RGPD	SI - documentacion interna	Global
Procedimiento ARCO-POL	Art. 15-22 RGPD	SI - plazo 30 dias	Por tenant
Notificacion Brechas	Art. 33-34 RGPD	SI - < 72h a AEPD	Global

1.2 Dependencias
•	Doc 115: Platform_Security_Compliance_v1 (seguridad tecnica)
•	Doc 138: Platform_Security_Audit_Procedures_v1 (procedimientos auditoria)
•	Doc 07: Core_Configuracion_MultiTenant_v1 (aislamiento de datos)
•	Doc 04: Core_Permisos_RBAC_v1 (control de acceso)
 
2. Data Processing Agreement (DPA) - Template Multi-Tenant
El DPA es el contrato entre Jaraba Impact Platform (Encargado del Tratamiento) y cada tenant (Responsable del Tratamiento). Debe firmarse electronicamente antes de activar cualquier procesamiento de datos personales.

2.1 Modelo de Datos: dpa_agreement
Campo	Tipo	Requerido	Descripcion
id	UUID	SI	Identificador unico del DPA
tenant_id	UUID FK	SI	Tenant que firma el DPA
version	VARCHAR(20)	SI	Version del DPA (ej: 2.1)
signed_at	TIMESTAMP	SI	Fecha/hora de firma
signed_by	UUID FK	SI	Usuario que firmo (admin tenant)
signer_name	VARCHAR(255)	SI	Nombre completo del firmante
signer_role	VARCHAR(100)	SI	Cargo del firmante
ip_address	VARCHAR(45)	SI	IP desde la que se firmo
dpa_hash	VARCHAR(64)	SI	SHA-256 del contenido DPA firmado
status	ENUM	SI	active|superseded|terminated
pdf_file_id	UUID FK	NO	PDF firmado almacenado
subprocessors_accepted	JSON	SI	Lista subprocesadores aceptados
data_categories	JSON	SI	Categorias datos tratados
retention_policy	JSON	SI	Politica de retencion acordada
international_transfers	BOOLEAN	SI	Si hay transferencias internacionales
scc_signed	BOOLEAN	NO	Clausulas contractuales tipo firmadas

2.2 Clausulas Esenciales del DPA
2.2.1 Objeto y Duracion
•	El Encargado (Jaraba Impact Platform) tratara datos personales unicamente segun las instrucciones documentadas del Responsable (tenant)
•	Duracion: vinculada al contrato de suscripcion SaaS
•	Al finalizar: devolucion o supresion de datos segun eleccion del Responsable

2.2.2 Medidas de Seguridad (Art. 32 RGPD)
Medida	Implementacion Jaraba	Referencia Doc
Cifrado en transito	TLS 1.3 obligatorio	Doc 131
Cifrado en reposo	AES-256 para datos sensibles	Doc 115
Aislamiento multi-tenant	Group Module + Row-Level Security	Doc 07
Control de acceso	RBAC + MFA para admins	Doc 04
Logging de accesos	audit_log inmutable	Doc 115
Backups cifrados	Cada 6h, retencion 30 dias	Doc 131
Pseudonimizacion	Hash de datos sensibles donde aplique	Doc 115
Pruebas de seguridad	Pentesting trimestral, ZAP semanal	Doc 138

2.2.3 Subprocesadores
Subprocesador	Servicio	Ubicacion	DPA Vigente
IONOS SE	Hosting dedicado	Alemania (UE)	SI
Stripe Inc.	Procesamiento pagos	USA (SCC)	SI
OpenAI Inc.	Embeddings IA	USA (SCC)	SI
Anthropic PBC	LLM Claude API	USA (SCC)	SI
SendGrid (Twilio)	Email transaccional	USA (SCC)	SI
Qdrant GmbH	Vector database	Alemania (UE)	SI
 
2.3 Modulo Drupal: jaraba_dpa
2.3.1 Servicios
jaraba_dpa.manager - Gestion del ciclo de vida del DPA:
•	generateDpa($tenantId): Genera DPA personalizado con datos del tenant
•	signDpa($tenantId, $userId, $ipAddress): Registra firma electronica
•	getCurrentDpa($tenantId): Obtiene DPA vigente del tenant
•	updateDpa($tenantId, $newVersion): Genera nueva version, invalida anterior
•	exportDpaPdf($dpaId): Genera PDF firmado con sello de tiempo

2.3.2 API REST
Endpoint	Metodo	Descripcion	Auth
/api/v1/dpa/current	GET	DPA vigente del tenant	Bearer + tenant_admin
/api/v1/dpa/sign	POST	Firmar DPA electronicamente	Bearer + tenant_admin
/api/v1/dpa/history	GET	Historial de DPAs del tenant	Bearer + tenant_admin
/api/v1/dpa/{id}/pdf	GET	Descargar PDF del DPA firmado	Bearer + tenant_admin
/api/v1/dpa/subprocessors	GET	Lista de subprocesadores actuales	Bearer + any

2.3.3 Flujo ECA: Firma de DPA
•	Trigger: Tenant admin accede por primera vez al panel
•	Condicion: No existe DPA firmado vigente
•	Accion: Mostrar modal bloqueante con DPA completo
•	Accion: Requerir checkbox de aceptacion + nombre + cargo
•	Accion: Registrar firma con timestamp, IP, user-agent
•	Accion: Generar PDF sellado y enviar copia por email
•	Accion: Desbloquear acceso al panel del tenant
 
3. Politica de Privacidad - Templates por Vertical
Cada vertical requiere una politica de privacidad especifica que refleje los datos concretos que se tratan. Se proporcionan templates parametrizables por tenant.

3.1 Datos Tratados por Vertical
Vertical	Datos Basicos	Datos Especificos	Base Legal Principal
Empleabilidad	Nombre, email, telefono	CV, skills, experiencia laboral, certificaciones	Consentimiento + interes legitimo
Emprendimiento	Nombre, email, NIF	Plan negocio, diagnosticos, datos financieros	Ejecucion contrato + consentimiento
AgroConecta	Nombre, email, direccion	Productos, certificaciones eco, datos finca	Ejecucion contrato
ComercioConecta	Nombre, email, NIF	Inventario, ventas POS, datos fiscales	Ejecucion contrato + obligacion legal
ServiciosConecta	Nombre, email, telefono	Expedientes, documentos confidenciales	Ejecucion contrato + secreto profesional

3.2 Modelo de Datos: privacy_policy
Campo	Tipo	Descripcion
id	UUID	Identificador unico
tenant_id	UUID FK	Tenant al que aplica
vertical	ENUM	empleabilidad|emprendimiento|agro|comercio|servicios
version	VARCHAR(20)	Version de la politica
content_html	LONGTEXT	Contenido HTML renderizable
content_hash	VARCHAR(64)	SHA-256 para detectar cambios
published_at	TIMESTAMP	Fecha de publicacion
is_active	BOOLEAN	Politica vigente
custom_sections	JSON	Secciones personalizadas por tenant
dpo_contact	VARCHAR(255)	Contacto DPO del tenant

3.3 Secciones Obligatorias de la Politica
1.	Identidad del Responsable: nombre, NIF, direccion, contacto DPO
2.	Finalidades del tratamiento: listado exhaustivo por vertical
3.	Base legal: consentimiento, ejecucion contrato, interes legitimo, obligacion legal
4.	Categorias de datos: datos identificativos, profesionales, economicos, etc.
5.	Destinatarios: subprocesadores listados con finalidad
6.	Transferencias internacionales: mecanismo (SCC, adecuacion)
7.	Plazos de conservacion: por tipo de dato y finalidad
8.	Derechos: acceso, rectificacion, supresion, portabilidad, oposicion, limitacion
9.	Reclamacion AEPD: derecho a presentar reclamacion ante la autoridad de control
10.	Cookies: referencia a la politica de cookies especifica
 
4. Banner de Cookies - Configuracion Granular
Implementacion de banner de cookies conforme a la LSSI-CE y la Directiva ePrivacy, con consentimiento granular por categoria de cookies.

4.1 Categorias de Cookies
Categoria	Consentimiento	Ejemplos	Duracion Maxima
Tecnicas/Esenciales	NO requerido	Sesion, CSRF, preferencias idioma	Sesion o 1 anio
Analiticas	SI requerido	Google Analytics, Plausible, metricas uso	2 anios
Marketing	SI requerido	Meta Pixel, Google Ads, retargeting	90 dias
Funcionales	SI requerido	Chat widget, preferencias UI, A/B testing	1 anio
Third-party	SI requerido	Stripe, YouTube embeds, mapas	Variable

4.2 Modelo de Datos: cookie_consent
Campo	Tipo	Descripcion
id	UUID	Identificador unico
user_id	UUID FK	Usuario (null si anonimo)
session_id	VARCHAR(64)	ID de sesion para anonimos
consent_analytics	BOOLEAN	Acepta cookies analiticas
consent_marketing	BOOLEAN	Acepta cookies marketing
consent_functional	BOOLEAN	Acepta cookies funcionales
consent_thirdparty	BOOLEAN	Acepta cookies terceros
ip_address	VARCHAR(45)	IP del consentimiento
user_agent	TEXT	Navegador y dispositivo
consented_at	TIMESTAMP	Fecha/hora del consentimiento
withdrawn_at	TIMESTAMP	Fecha/hora de retirada (si aplica)
tenant_id	UUID FK	Tenant donde se dio el consentimiento

4.3 Implementacion Tecnica
4.3.1 Modulo: jaraba_cookies
•	Componente React: CookieBanner con UI configurable por tenant
•	Almacenamiento: Cookie httpOnly 'jaraba_consent' con JSON cifrado
•	Server-side: Middleware que inyecta/bloquea scripts segun consentimiento
•	Google Tag Manager: Activacion condicional via dataLayer
•	API: /api/v1/cookies/consent (POST para registrar, GET para verificar)

4.3.2 Configuracion por Tenant
•	Colores y textos personalizables desde Admin Center
•	Posicion del banner: bottom-bar, modal, corner-popup
•	Links a politica de cookies y privacidad del tenant
•	Opcion de 'Aceptar todas' + 'Rechazar no esenciales' + 'Configurar'
 
5. Registro de Actividades de Tratamiento (RAT)
El RAT documenta todas las actividades de tratamiento de datos personales realizadas por la plataforma, conforme al Art. 30 RGPD. Es un documento interno obligatorio.

5.1 Modelo de Datos: processing_activity
Campo	Tipo	Descripcion
id	UUID	Identificador unico
activity_name	VARCHAR(255)	Nombre de la actividad
purpose	TEXT	Finalidad del tratamiento
legal_basis	ENUM	consent|contract|legal_obligation|legitimate_interest|vital_interest|public_task
data_categories	JSON	Categorias de datos: identificativos, profesionales, etc.
data_subjects	JSON	Categorias de interesados: candidatos, emprendedores, etc.
recipients	JSON	Destinatarios: subprocesadores, AAPP, etc.
international_transfers	JSON	Paises y mecanismo (SCC, adecuacion)
retention_period	VARCHAR(100)	Plazo de conservacion
security_measures	JSON	Medidas tecnicas y organizativas
dpia_required	BOOLEAN	Requiere evaluacion de impacto
dpia_reference	VARCHAR(100)	Referencia al DPIA si existe
vertical	ENUM	Vertical donde aplica
is_active	BOOLEAN	Actividad vigente
last_reviewed	DATE	Ultima revision

5.2 Actividades Pre-configuradas por Vertical
•	Empleabilidad: Gestion de CVs, matching laboral, certificaciones, alertas empleo
•	Emprendimiento: Diagnosticos negocio, planes de accion, mentoring, business canvas
•	AgroConecta: Catalogo productos, pedidos, trazabilidad, certificaciones eco
•	ComercioConecta: Inventario, ventas POS, ofertas flash, QR dinamicos
•	ServiciosConecta: Expedientes, reservas, firma digital, facturacion
•	Transversales: Registro usuarios, facturacion, emails, analytics, IA copilots
 
6. Procedimiento ARCO-POL
Procedimiento para el ejercicio de derechos de Acceso, Rectificacion, Cancelacion/Supresion, Oposicion, Portabilidad, Olvido y Limitacion del tratamiento.

6.1 Modelo de Datos: data_rights_request
Campo	Tipo	Descripcion
id	UUID	Identificador unico
requester_email	VARCHAR(255)	Email del solicitante
requester_name	VARCHAR(255)	Nombre completo
right_type	ENUM	access|rectification|erasure|portability|objection|restriction
description	TEXT	Descripcion de la solicitud
identity_verified	BOOLEAN	Identidad verificada
verification_method	ENUM	email_otp|id_document|existing_session
received_at	TIMESTAMP	Fecha de recepcion
deadline	TIMESTAMP	Plazo maximo (30 dias)
status	ENUM	received|in_progress|completed|rejected|extended
response	TEXT	Respuesta proporcionada
completed_at	TIMESTAMP	Fecha de resolucion
tenant_id	UUID FK	Tenant afectado
handler_id	UUID FK	Usuario que gestiona

6.2 API REST: Derechos del Interesado
Endpoint	Metodo	Descripcion
/api/v1/privacy/rights/request	POST	Crear solicitud de ejercicio de derechos
/api/v1/privacy/rights/{id}/status	GET	Consultar estado de la solicitud
/api/v1/privacy/data-export	POST	Solicitar exportacion de datos (portabilidad)
/api/v1/privacy/data-deletion	POST	Solicitar supresion de datos
/api/v1/privacy/consent/withdraw	POST	Retirar consentimiento

6.3 Flujo ECA: Gestion de Solicitud ARCO-POL
11.	Recepcion: Usuario envia solicitud via formulario web o email
12.	Verificacion identidad: OTP por email o verificacion de sesion activa
13.	Registro: Se crea data_rights_request con plazo de 30 dias
14.	Notificacion: Email al DPO y admin del tenant con alerta de plazo
15.	Evaluacion: DPO evalua legitimidad y alcance de la solicitud
16.	Ejecucion: Se ejecuta la accion solicitada (export, delete, etc.)
17.	Respuesta: Se notifica al solicitante con resultado detallado
18.	Registro: Se documenta todo el proceso para auditoria
 
7. Notificacion de Brechas de Seguridad
Procedimiento para la notificacion de brechas de datos personales conforme a los Art. 33-34 RGPD. Plazo maximo: 72 horas desde la deteccion para notificar a la AEPD.

7.1 Modelo de Datos: security_breach
Campo	Tipo	Descripcion
id	UUID	Identificador unico
detected_at	TIMESTAMP	Momento de deteccion
detected_by	UUID FK	Quien detecto la brecha
severity	ENUM	low|medium|high|critical
breach_type	ENUM	confidentiality|integrity|availability|combined
description	TEXT	Descripcion detallada
data_affected	JSON	Tipos de datos afectados
records_affected	INT	Numero estimado de registros
tenants_affected	JSON	Lista de tenants afectados
containment_actions	JSON	Acciones de contencion
aepd_notified	BOOLEAN	Notificado a AEPD
aepd_notification_date	TIMESTAMP	Fecha de notificacion AEPD
users_notified	BOOLEAN	Afectados notificados
root_cause	TEXT	Causa raiz
remediation_plan	JSON	Plan de remediacion
status	ENUM	detected|contained|investigating|resolved|closed

7.2 Timeline de Respuesta
Fase	Plazo	Acciones	Responsable
Deteccion	T+0	Identificar, evaluar severidad, activar protocolo	Security Lead
Contencion	T+15min	Aislar sistemas, preservar evidencia	DevOps + CTO
Evaluacion	T+2h	Determinar alcance, datos afectados, riesgo	DPO + CTO
Notificacion AEPD	T+72h max	Formulario AEPD con detalles completos	DPO
Notificacion usuarios	Sin demora	Si riesgo alto para derechos y libertades	DPO + Marketing
Remediacion	Segun plan	Parchear, rotar credenciales, mejorar controles	DevOps
Post-mortem	T+7d	Lecciones aprendidas, actualizacion procedimientos	Todo equipo
 
8. Implementacion Tecnica
8.1 Modulo: jaraba_privacy
Modulo Drupal centralizado que gestiona todo el compliance de privacidad.

8.1.1 Estructura del Modulo
•	jaraba_privacy.info.yml: Definicion del modulo
•	jaraba_privacy.services.yml: Servicios DPA, Privacy Policy, Cookies, Rights, Breach
•	jaraba_privacy.routing.yml: Rutas admin y API REST
•	jaraba_privacy.permissions.yml: Permisos RBAC especificos
•	src/Service/DpaManager.php: Gestion ciclo vida DPA
•	src/Service/PrivacyPolicyGenerator.php: Generacion politicas por vertical
•	src/Service/CookieConsentManager.php: Gestion consentimiento cookies
•	src/Service/DataRightsHandler.php: Procesamiento solicitudes ARCO-POL
•	src/Service/BreachNotificationService.php: Gestion brechas seguridad

8.2 Configuracion Admin
•	Seccion en Admin Center (/admin/config/jaraba/privacy)
•	Configuracion DPO: nombre, email, telefono por tenant
•	Templates de politica de privacidad editables
•	Dashboard de solicitudes ARCO-POL con semaforo de plazos
•	Registro de brechas con timeline visual
•	Export de RAT en formato Excel/PDF para auditoria
 
9. Estimacion de Implementacion
Componente	Horas	Coste EUR	Prioridad
Modulo jaraba_dpa (DPA Manager)	10-12h	450-540	CRITICA
Privacy Policy Generator por vertical	8-10h	360-450	CRITICA
Cookie Banner + Consent Manager	8-10h	360-450	CRITICA
RAT (Registro Actividades Tratamiento)	5-6h	225-270	ALTA
ARCO-POL Handler + API	8-10h	360-450	CRITICA
Breach Notification System	6-8h	270-360	ALTA
Admin UI + Dashboard	5-6h	225-270	MEDIA
TOTAL	50-62h	2,250-2,790	N1 BLOQUEANTE

--- Fin del Documento ---
Jaraba Impact Platform | Especificacion Tecnica v1.0 | Febrero 2026
