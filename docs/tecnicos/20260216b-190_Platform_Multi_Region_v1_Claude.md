
MULTI-REGION OPERATIONS
Expansion Multi-Pais: Fiscalidad, Multi-Currency, Data Residency
Nivel de Madurez: N2
JARABA IMPACT PLATFORM
Especificacion Tecnica para Implementacion
Version:	1.0
Fecha:	Febrero 2026
Codigo:	190_Platform_Multi_Region_v1
Estado:	Especificacion para EDI Google Antigravity
Nivel Madurez:	N2
Compliance:	GDPR, LOPD-GDD, ENS, ISO 27001
 
1. Resumen Ejecutivo
Operacion multi-pais para expansion europea: fiscalidad por pais (IVA intracomunitario), moneda multi-currency en Stripe, compliance GDPR por jurisdiccion, CDN multi-region, data residency por tenant y templates legales por pais.

1.1 Mercados Objetivo
Pais	Mercado	Fiscalidad	Idioma	Prioridad
Espana	Actual	IVA 21%	ES	Operativo
Portugal	Expansion 1	IVA 23%	PT	Q3 2026
Francia	Expansion 2	TVA 20%	FR	Q4 2026
Italia	Expansion 3	IVA 22%	IT	Q1 2027
LATAM	Expansion 4	Variable	ES	Q2 2027
 
2. Multi-Currency en Stripe
2.1 Configuracion
Campo	Tipo	Descripcion
id	UUID	Identificador
tenant_id	UUID FK	Tenant
base_currency	ENUM	EUR|USD|GBP|BRL - Moneda base del tenant
display_currencies	JSON	Monedas para display
stripe_account_country	VARCHAR(2)	Pais de la cuenta Stripe
tax_settings	JSON	Configuracion fiscal por jurisdiccion
vat_number	VARCHAR(20)	Numero IVA/NIF intracomunitario
vies_validated	BOOLEAN	Validado contra VIES

2.2 Reglas Fiscales
Escenario	IVA Aplicable	Factura
B2C Espana	21% IVA espanol	Factura con IVA
B2B Espana	21% IVA espanol	Factura con IVA
B2B UE (con VAT valido)	0% (inversion sujeto pasivo)	Factura sin IVA + mencion Art. 196
B2C UE (< umbral OSS)	IVA del pais destino	Factura con IVA destino
B2B fuera UE	Exento	Factura sin IVA
B2C fuera UE	Exento	Factura sin IVA
 
3. Data Residency
3.1 Modelo de Datos: tenant_region
Campo	Tipo	Descripcion
id	UUID	Identificador
tenant_id	UUID FK	Tenant
data_region	ENUM	eu-west|eu-central|us-east|latam
primary_dc	VARCHAR(50)	Datacenter principal
legal_jurisdiction	VARCHAR(2)	Jurisdiccion legal (ES, PT, FR, etc.)
gdpr_representative	VARCHAR(255)	Representante GDPR si aplica
data_processing_location	JSON	Ubicaciones de procesamiento
cross_border_transfers	JSON	Transferencias transfronterizas
 
4. Implementacion Tecnica
4.1 Modulo: jaraba_multiregion
•	src/Service/RegionManager.php: Gestion de regiones y data residency
•	src/Service/CurrencyConverter.php: Conversion de monedas en tiempo real
•	src/Service/TaxCalculator.php: Calculo de IVA por jurisdiccion
•	src/Service/ViesValidator.php: Validacion de VAT numbers contra VIES
•	src/Service/RegionalCompliance.php: Compliance por pais
 
5. Estimacion de Implementacion
Componente	Horas	Coste EUR	Prioridad
Multi-currency Stripe	12-15h	540-675	ALTA
Tax Calculator por pais	10-12h	450-540	ALTA
VIES Validation	4-5h	180-225	ALTA
Data Residency config	8-10h	360-450	MEDIA
i18n Templates legales	8-10h	360-450	MEDIA
CDN Multi-region	6-8h	270-360	MEDIA
Regional Compliance	6-8h	270-360	MEDIA
TOTAL	54-68h	2,430-3,060	N2

--- Fin del Documento ---
Jaraba Impact Platform | Especificacion Tecnica v1.0 | Febrero 2026
