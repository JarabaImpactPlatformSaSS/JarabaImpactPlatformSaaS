
SSO SAML/SCIM
Single Sign-On Enterprise con SAML 2.0, SCIM 2.0 y MFA
Nivel de Madurez: N3
JARABA IMPACT PLATFORM
Especificacion Tecnica para Implementacion
Version:	1.0
Fecha:	Febrero 2026
Codigo:	199_Platform_SSO_SAML_SCIM_v1
Estado:	Especificacion para EDI Google Antigravity
Nivel Madurez:	N3
Compliance:	GDPR, LOPD-GDD, ENS, ISO 27001
 
1. Resumen Ejecutivo
Integracion SSO enterprise: SAML 2.0 con IdPs corporativos (Azure AD, Okta, Google Workspace), SCIM 2.0 para provisionamiento automatico de usuarios, JIT provisioning, MFA enforcement y session management centralizado.

1.1 Protocolos Soportados
Protocolo	Version	Uso	IdPs Compatibles
SAML 2.0	2.0	SSO federado	Azure AD, Okta, OneLogin, ADFS
SCIM 2.0	2.0	User provisioning	Azure AD, Okta, OneLogin
OIDC	1.0	SSO alternativo	Google Workspace, Auth0, Keycloak
OAuth 2.0	2.0	API authorization	Todos
 
2. SAML 2.0 Integration
2.1 Modelo de Datos: sso_configuration
Campo	Tipo	Descripcion
id	UUID	Identificador
tenant_id	UUID FK	Tenant
provider_name	VARCHAR(100)	Nombre del IdP (Azure AD, Okta, etc.)
entity_id	VARCHAR(500)	Entity ID del IdP
sso_url	VARCHAR(500)	SSO Login URL
slo_url	VARCHAR(500)	Single Logout URL
certificate	TEXT	X.509 Certificate del IdP
attribute_mapping	JSON	Mapeo de atributos SAML a user fields
default_role	VARCHAR(50)	Rol por defecto para nuevos usuarios
auto_provision	BOOLEAN	Crear usuario automaticamente en primer login
force_sso	BOOLEAN	Forzar SSO (deshabilitar login local)
is_active	BOOLEAN	Configuracion activa

2.2 Flujo SAML SP-Initiated
1.	Usuario accede a la plataforma Jaraba
2.	Plataforma detecta tenant con SSO configurado
3.	Redirect a IdP con AuthnRequest SAML
4.	Usuario se autentica en IdP (con MFA si aplica)
5.	IdP envia SAMLResponse con assertion firmada
6.	Plataforma valida firma, extrae atributos
7.	JIT: Si usuario no existe, se crea con atributos del IdP
8.	Se crea sesion local y redirect a dashboard
 
3. SCIM 2.0 Provisioning
3.1 Endpoints SCIM
Endpoint	Metodo	Descripcion
/scim/v2/Users	GET	Listar usuarios del tenant
/scim/v2/Users	POST	Crear usuario
/scim/v2/Users/{id}	GET	Obtener usuario
/scim/v2/Users/{id}	PUT	Actualizar usuario
/scim/v2/Users/{id}	PATCH	Actualizar parcial
/scim/v2/Users/{id}	DELETE	Desactivar usuario
/scim/v2/Groups	GET	Listar grupos/roles
/scim/v2/Groups	POST	Crear grupo
/scim/v2/Groups/{id}	PATCH	Actualizar grupo

3.2 Schema SCIM User
•	userName: email del usuario (unico por tenant)
•	name: {givenName, familyName}
•	emails: [{value, type, primary}]
•	phoneNumbers: [{value, type}]
•	active: boolean (activar/desactivar)
•	roles: asignacion de roles Drupal
•	groups: asignacion a groups del tenant
•	externalId: ID del usuario en el IdP
 
4. MFA Enforcement
Politica	Descripcion	Configurable Por
MFA Required	Todos los usuarios deben tener MFA	Tenant admin
MFA for Admins	Solo admins requieren MFA	Platform default
MFA Methods	TOTP, WebAuthn, SMS	Tenant admin
MFA Grace Period	Dias para activar MFA despues de login	Tenant admin
Session Duration	Duracion maxima de sesion	Tenant admin
Concurrent Sessions	Maximo sesiones simultaneas	Platform config
 
5. Estimacion de Implementacion
Componente	Horas	Coste EUR	Prioridad
SAML 2.0 SP implementation	15-20h	675-900	CRITICA
SCIM 2.0 server	12-15h	540-675	ALTA
OIDC integration	8-10h	360-450	ALTA
JIT provisioning	6-8h	270-360	ALTA
MFA enforcement	6-8h	270-360	MEDIA
Session management	5-6h	225-270	MEDIA
Admin UI + testing	8-10h	360-450	ALTA
TOTAL	60-77h	2,700-3,465	N3

--- Fin del Documento ---
Jaraba Impact Platform | Especificacion Tecnica v1.0 | Febrero 2026
