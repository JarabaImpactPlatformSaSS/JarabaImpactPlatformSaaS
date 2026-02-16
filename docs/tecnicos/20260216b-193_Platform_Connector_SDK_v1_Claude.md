
CONNECTOR SDK
SDK para Desarrollo de Conectores, Certificacion y Marketplace
Nivel de Madurez: N2
JARABA IMPACT PLATFORM
Especificacion Tecnica para Implementacion
Version:	1.0
Fecha:	Febrero 2026
Codigo:	193_Platform_Connector_SDK_v1
Estado:	Especificacion para EDI Google Antigravity
Nivel Madurez:	N2
Compliance:	GDPR, LOPD-GDD, ENS, ISO 27001
 
1. Resumen Ejecutivo
SDK para que terceros desarrollen conectores para la plataforma: Connector Development Kit, sandbox de pruebas, proceso de certificacion, marketplace de extensiones con revenue share, versionado de APIs y documentacion auto-generada. Extiende el doc 112 (Integration Marketplace).

1.1 Arquitectura del SDK
Componente	Descripcion	Tecnologia
Connector Framework	Base para crear conectores	PHP + OpenAPI
Sandbox Environment	Entorno de pruebas aislado	Docker + Lando
Certification Pipeline	Validacion automatica de conectores	CI/CD + tests
Developer Portal	Documentacion y herramientas	Drupal + API docs
Marketplace	Publicacion y distribucion	Drupal Commerce
Revenue Share	Reparto de ingresos con developers	Stripe Connect
 
2. Modelo de Datos
2.1 connector
Campo	Tipo	Descripcion
id	UUID	Identificador
developer_id	UUID FK	Desarrollador/empresa
name	VARCHAR(100)	Nombre del conector
slug	VARCHAR(100)	URL-friendly identifier
description	TEXT	Descripcion
version	VARCHAR(20)	Version actual
api_version	VARCHAR(10)	Version API compatible
category	ENUM	crm|erp|payment|communication|analytics|custom
icon_url	VARCHAR(500)	Icono del conector
config_schema	JSON	Schema de configuracion
endpoints_provided	JSON	Endpoints que expone
webhooks_consumed	JSON	Webhooks que consume
certification_status	ENUM	draft|testing|certified|suspended
installs_count	INT	Numero de instalaciones
rating	DECIMAL(3,2)	Puntuacion media
pricing_model	ENUM	free|one_time|monthly|usage_based
price	DECIMAL(8,2)	Precio
revenue_share_pct	INT	% revenue share (default 70/30)
 
3. Connector Development Kit
3.1 Estructura de un Conector
•	connector.info.yml: Metadata del conector
•	connector.config.yml: Schema de configuracion
•	src/Plugin/Connector/MyConnector.php: Clase principal
•	src/Service/MyConnectorClient.php: Cliente API externo
•	tests/: Tests automatizados requeridos
•	README.md: Documentacion del conector

3.2 API del Conector
Metodo	Descripcion	Requerido
install()	Configuracion inicial del conector	SI
uninstall()	Limpieza al desinstalar	SI
configure($settings)	Aplicar configuracion del tenant	SI
sync()	Sincronizacion de datos	NO
handleWebhook($payload)	Procesar webhook entrante	NO
getStatus()	Estado del conector	SI
test()	Auto-test de conectividad	SI
 
4. Certificacion y Marketplace
4.1 Proceso de Certificacion
1.	Developer sube conector al sandbox
2.	Tests automatizados: seguridad, performance, API compliance
3.	Review manual: codigo, documentacion, UX
4.	Certificacion o feedback de mejoras
5.	Publicacion en marketplace

4.2 Revenue Share
Tier	Revenue Share	Requisitos
Standard	70% developer / 30% platform	Conector certificado
Premium	80% developer / 20% platform	Rating > 4.5 + 100 installs
Strategic	85% developer / 15% platform	Partnership agreement
 
5. Estimacion de Implementacion
Componente	Horas	Coste EUR	Prioridad
Connector Framework base	15-18h	675-810	CRITICA
Sandbox environment	8-10h	360-450	ALTA
Certification pipeline	10-12h	450-540	ALTA
Developer Portal	8-10h	360-450	ALTA
Marketplace UI	8-10h	360-450	MEDIA
Revenue Share (Stripe)	6-8h	270-360	MEDIA
TOTAL	55-68h	2,475-3,060	N2

--- Fin del Documento ---
Jaraba Impact Platform | Especificacion Tecnica v1.0 | Febrero 2026
