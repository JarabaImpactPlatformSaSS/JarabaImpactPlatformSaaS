
DATA GOVERNANCE
Gobernanza de Datos: Clasificacion, Retencion, Lineage y KMS
Nivel de Madurez: N3
JARABA IMPACT PLATFORM
Especificacion Tecnica para Implementacion
Version:	1.0
Fecha:	Febrero 2026
Codigo:	200_Platform_Data_Governance_v1
Estado:	Especificacion para EDI Google Antigravity
Nivel Madurez:	N3
Compliance:	GDPR, LOPD-GDD, ENS, ISO 27001
 
1. Resumen Ejecutivo
Gobernanza de datos enterprise: clasificacion de datos, politicas de retencion por tipo y jurisdiccion, data lineage tracking, right to be forgotten automatizado, exportacion en formatos estandar, encryption con key management (KMS) y data masking para entornos de desarrollo.

1.1 Framework de Gobernanza
Pilar	Descripcion	Responsable
Clasificacion	Categorizar datos por sensibilidad	DPO
Retencion	Politicas de conservacion y eliminacion	DPO + Legal
Lineage	Trazabilidad del ciclo de vida del dato	Data Engineer
Quality	Integridad y precision de datos	Data Engineer
Security	Cifrado, acceso, auditoria	CISO
Compliance	GDPR, LOPD, ENS, SOC 2	DPO + CISO
 
2. Clasificacion de Datos
2.1 Niveles de Clasificacion
Nivel	Etiqueta	Descripcion	Controles	Ejemplos
C1	PUBLIC	Datos publicables	Ninguno especial	Web content, precios
C2	INTERNAL	Uso interno	Acceso autenticado	Metricas, configs
C3	CONFIDENTIAL	Datos personales	Cifrado + RBAC	CVs, perfiles, pedidos
C4	RESTRICTED	Datos sensibles	Cifrado + MFA + audit	Datos salud, financieros, passwords

2.2 Modelo de Datos: data_classification
Campo	Tipo	Descripcion
id	UUID	Identificador
entity_type	VARCHAR(100)	Tipo de entidad Drupal
field_name	VARCHAR(100)	Campo especifico
classification	ENUM	public|internal|confidential|restricted
pii	BOOLEAN	Es dato personal (PII)
sensitive	BOOLEAN	Es dato sensible (Art. 9 GDPR)
retention_days	INT	Dias de retencion
encryption_required	BOOLEAN	Requiere cifrado en reposo
masking_required	BOOLEAN	Requiere masking en dev/staging
cross_border_allowed	BOOLEAN	Puede transferirse fuera UE
legal_basis	VARCHAR(100)	Base legal del tratamiento
 
3. Politicas de Retencion
3.1 Retencion por Tipo de Dato
Tipo de Dato	Retencion	Accion Post-Retencion	Base Legal
Datos de sesion/logs	90 dias	Eliminacion automatica	Interes legitimo
Datos de usuario activo	Mientras cuenta activa	Ofrecido + 30d gracia	Contrato
CVs y perfiles empleo	2 anos post-inactividad	Anonimizacion	Consentimiento
Datos fiscales/facturas	4 anos (LGT)	Archivo frio	Obligacion legal
Expedientes legales	5 anos (Ley 2/2023)	Archivo frio	Obligacion legal
Backups	30 dias	Rotacion automatica	Interes legitimo
Datos anonimizados	Indefinido	N/A	No aplica GDPR
 
4. Data Lineage
4.1 Modelo de Datos: data_lineage_event
Campo	Tipo	Descripcion
id	UUID	Identificador
entity_type	VARCHAR(100)	Tipo de entidad
entity_id	UUID	ID de la entidad
event_type	ENUM	created|updated|read|exported|deleted|anonymized|transferred
actor_id	UUID FK	Quien realizo la accion
actor_type	ENUM	user|system|agent|api_client
source_system	VARCHAR(100)	Sistema de origen
destination_system	VARCHAR(100)	Sistema de destino (si transferencia)
timestamp	TIMESTAMP	Momento del evento
details	JSON	Detalles adicionales
tenant_id	UUID FK	Tenant
 
5. Key Management (KMS)
5.1 Estrategia de Cifrado
Dato	Algoritmo	Key Rotation	Almacenamiento Key
Datos en transito	TLS 1.3	Certificado anual	Let's Encrypt
Datos en reposo (DB)	AES-256-GCM	Anual	Vault/env encrypted
Backups	AES-256-CBC	Anual	Vault + offline backup
Buzon Confianza	AES-256-GCM per-doc	Per-document	User-derived key
API keys/secrets	AES-256	En cada rotacion	Vault/env
Tokens de sesion	HMAC-SHA256	Diario (auto)	Redis
 
6. Data Masking para Desarrollo
6.1 Reglas de Masking
Campo	Tipo Dato	Tecnica Masking	Ejemplo
email	PII	Fake email con dominio test	user123@test.jaraba.dev
nombre	PII	Faker library	Maria Garcia Lopez
telefono	PII	Mantener formato, random digits	+34 6XX XXX XXX
NIF/CIF	PII	Formato valido, datos falsos	12345678Z
IBAN	Financiero	Formato valido, datos falsos	ES00 0000 0000 0000 0000
direccion	PII	Direccion generada aleatoria	Calle Falsa 123, Cordoba
CV content	PII	Lorem ipsum con estructura	Experiencia simulada
 
7. Estimacion de Implementacion
Componente	Horas	Coste EUR	Prioridad
Data classification framework	8-10h	360-450	CRITICA
Retention policies + automation	8-10h	360-450	CRITICA
Data lineage tracking	10-12h	450-540	ALTA
Right to be forgotten workflow	6-8h	270-360	CRITICA
KMS + key rotation	6-8h	270-360	ALTA
Data masking for dev	8-10h	360-450	ALTA
Data export formats	5-6h	225-270	MEDIA
TOTAL	51-64h	2,295-2,880	N3

--- Fin del Documento ---
Jaraba Impact Platform | Especificacion Tecnica v1.0 | Febrero 2026
