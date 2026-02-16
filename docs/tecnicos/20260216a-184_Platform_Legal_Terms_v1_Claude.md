
LEGAL TERMS SAAS
Terminos de Servicio, SLA, AUP, Licencia de Datos y Offboarding
Nivel de Madurez: N1
JARABA IMPACT PLATFORM
Especificacion Tecnica para Implementacion
Version:	1.0
Fecha:	Febrero 2026
Codigo:	184_Platform_Legal_Terms_v1
Estado:	Especificacion para EDI Google Antigravity
Nivel Madurez:	N1
Compliance:	GDPR, LOPD-GDD, ENS, ISO 27001
 
1. Resumen Ejecutivo
Este documento especifica los terminos legales del servicio SaaS: Terminos de Servicio (ToS), Acuerdo de Nivel de Servicio (SLA), Politica de Uso Aceptable (AUP), licencia de datos, procedimiento de offboarding y canal de denuncias. Sin estos documentos, no se pueden activar pagos reales a traves de Stripe.

1.1 Documentos Legales Cubiertos
Documento	Proposito	Obligatorio Para	Base Legal
Terminos de Servicio (ToS)	Contrato SaaS completo	Activar Stripe Live	Codigo Civil + LSSI
SLA (Acuerdo Nivel Servicio)	Garantias de disponibilidad	Todos los planes de pago	Contrato
AUP (Uso Aceptable)	Limites de uso de la plataforma	Todos los usuarios	LSSI + Contrato
Licencia de Datos	Propiedad y uso de datos del tenant	Todos los tenants	RGPD + LOPD
Offboarding/Portabilidad	Proceso de baja y exportacion	RGPD Art. 20	RGPD
Canal de Denuncias	Cumplimiento Ley 2/2023	Empresas >50 empleados	Ley 2/2023
 
2. Terminos de Servicio (ToS)
2.1 Estructura del ToS por Plan
Seccion	Free/Starter	Professional	Enterprise
Usuarios incluidos	1 usuario	Hasta 10 usuarios	Ilimitados
Almacenamiento	500 MB	10 GB	100 GB+
Soporte	Email (72h SLA)	Email + chat (24h)	Dedicado (4h SLA)
SLA uptime	Best effort	99.5%	99.9%
AI Copilot tokens/mes	1,000	25,000	Ilimitados
Backups	Diario	Cada 6h	Cada hora + custom
API access	Read-only	Full CRUD	Full + webhooks
White-label	No	Logo + colores	Completo
Facturacion	No	Facturae basico	Facturae + VeriFactu

2.2 Modelo de Datos: service_agreement
Campo	Tipo	Descripcion
id	UUID	Identificador unico
tenant_id	UUID FK	Tenant contratante
plan_type	ENUM	free|starter|professional|enterprise
tos_version	VARCHAR(20)	Version ToS aceptada
accepted_at	TIMESTAMP	Fecha de aceptacion
accepted_by	UUID FK	Usuario que acepto
billing_cycle	ENUM	monthly|annual
auto_renewal	BOOLEAN	Renovacion automatica
cancellation_notice_days	INT	Dias preaviso cancelacion
custom_terms	JSON	Terminos custom para Enterprise
status	ENUM	active|suspended|cancelled|expired
trial_ends_at	TIMESTAMP	Fin del periodo de prueba

2.3 Clausulas Clave del ToS
2.3.1 Propiedad Intelectual
•	La plataforma y su codigo son propiedad de Jaraba Impact Platform
•	Los datos del tenant son propiedad exclusiva del tenant
•	El contenido generado por IA es propiedad del tenant que lo solicita
•	El tenant concede licencia limitada para procesamiento necesario del servicio

2.3.2 Limitacion de Responsabilidad
•	Responsabilidad maxima: importe pagado en los ultimos 12 meses
•	Exclusion de danos indirectos, consecuenciales o lucro cesante
•	Excepciones: negligencia grave, dolo, incumplimiento RGPD

2.3.3 Suspension y Terminacion
•	Suspension inmediata por uso prohibido (AUP violation)
•	Suspension por impago: 15 dias de gracia + notificacion
•	Terminacion voluntaria: con preaviso segun plan
•	Post-terminacion: 30 dias para exportar datos, luego supresion
 
3. Acuerdo de Nivel de Servicio (SLA)
3.1 Niveles de Servicio por Plan
Metrica	Starter	Professional	Enterprise
Uptime garantizado	99.0%	99.5%	99.9%
Downtime maximo/mes	7.3h	3.65h	43.8min
Tiempo respuesta API (p95)	<2s	<1s	<500ms
RTO (Recovery Time)	<8h	<4h	<1h
RPO (Recovery Point)	<24h	<6h	<1h
Mantenimiento programado	4h/mes	2h/mes	1h/mes (ventana nocturna)
Credito por incumplimiento	No	10% mensualidad	25% mensualidad

3.2 Calculo de Uptime
Uptime % = ((Minutos totales - Minutos downtime) / Minutos totales) x 100

•	Se excluyen del calculo: mantenimientos programados notificados con 48h antelacion
•	Se excluyen: downtime por fuerza mayor, ataques DDoS, fallos del proveedor cloud
•	Se incluyen: fallos de aplicacion, base de datos, API, servicios core

3.3 Proceso de Creditos SLA
1.	El tenant reporta incidente de downtime via soporte
2.	El equipo verifica el downtime con logs de monitoring (Prometheus/Grafana)
3.	Si se confirma incumplimiento, se calcula el credito proporcional
4.	El credito se aplica automaticamente en la siguiente factura
5.	Credito maximo acumulable: 30% de la mensualidad
 
4. Politica de Uso Aceptable (AUP)
4.1 Usos Prohibidos
•	Almacenar, transmitir o procesar contenido ilegal bajo legislacion espanola o de la UE
•	Realizar spam, phishing, o ingenieria social a traves de la plataforma
•	Intentar acceder a datos de otros tenants o explotar vulnerabilidades
•	Usar la plataforma para criptomineria, proxies, o servicios no autorizados
•	Superar limites de uso del plan sin autorizacion (rate limiting)
•	Usar el AI Copilot para generar contenido que viole derechos de autor o sea difamatorio
•	Crear cuentas multiples para evadir restricciones del plan free

4.2 Limites de Uso por Plan
Recurso	Free	Professional	Enterprise
API calls/hora	100	5,000	50,000
AI tokens/mes	1,000	25,000	Ilimitado
Almacenamiento	500 MB	10 GB	100 GB+
Emails transaccionales/mes	100	5,000	50,000
Exportaciones/dia	5	50	Ilimitado
Webhooks activos	0	10	100
 
5. Licencia de Datos del Tenant
5.1 Principios de Propiedad
•	Los datos del tenant son y seguiran siendo propiedad exclusiva del tenant
•	Jaraba Impact Platform actua como Encargado del Tratamiento (procesador)
•	No se utilizan datos del tenant para entrenar modelos de IA propios
•	No se venden, ceden o comparten datos del tenant con terceros sin consentimiento
•	El tenant puede exportar sus datos en cualquier momento en formatos estandar

5.2 Formatos de Exportacion
Tipo de Dato	Formato Exportacion	Incluye
Usuarios y perfiles	CSV + JSON	Todos los campos del perfil
Contenido (cursos, ofertas)	JSON + HTML	Estructura y contenido
Documentos adjuntos	ZIP con estructura	Archivos originales
Configuracion tenant	YAML	Settings, workflows, permisos
Metricas e historicos	CSV	Datos de uso y analytics
Facturas y pagos	Facturae XML + PDF	Todas las facturas emitidas
 
6. Procedimiento de Offboarding
6.1 Flujo de Baja
6.	Solicitud: Tenant admin solicita baja desde Admin Center o via soporte
7.	Confirmacion: Email de confirmacion con resumen de datos a exportar
8.	Periodo de gracia: 30 dias para exportar datos y migrar
9.	Exportacion: Se genera paquete completo de datos en formatos estandar
10.	Facturacion final: Se emite factura de cierre con saldo pendiente
11.	Desactivacion: Acceso read-only durante 15 dias adicionales
12.	Supresion: Eliminacion completa de datos tras confirmar exportacion
13.	Certificado: Se emite certificado de supresion de datos

6.2 Modelo de Datos: offboarding_request
Campo	Tipo	Descripcion
id	UUID	Identificador unico
tenant_id	UUID FK	Tenant que solicita baja
requested_by	UUID FK	Admin que solicita
reason	ENUM	cost|features|competitor|closing_business|other
reason_detail	TEXT	Motivo detallado (opcional)
requested_at	TIMESTAMP	Fecha solicitud
grace_period_ends	TIMESTAMP	Fin periodo de gracia (30d)
data_exported	BOOLEAN	Datos exportados
export_package_url	VARCHAR(500)	URL temporal del paquete exportado
final_invoice_id	UUID FK	Factura de cierre
deletion_confirmed	BOOLEAN	Supresion confirmada por admin
deletion_certificate_url	VARCHAR(500)	Certificado de supresion
status	ENUM	requested|grace_period|exporting|completed|cancelled
 
7. Canal de Denuncias (Ley 2/2023)
La Ley 2/2023, de 20 de febrero, reguladora de la proteccion de las personas que informen sobre infracciones normativas y de lucha contra la corrupcion, obliga a disponer de un canal de denuncias interno.

7.1 Modelo de Datos: whistleblower_report
Campo	Tipo	Descripcion
id	UUID	Identificador unico
report_code	VARCHAR(20)	Codigo anonimo de seguimiento
reporter_type	ENUM	anonymous|identified
reporter_name	VARCHAR(255)	Nombre (si identified)
reporter_email	VARCHAR(255)	Email (si identified)
category	ENUM	fraud|corruption|harassment|data_breach|compliance|other
description	TEXT	Descripcion detallada del hecho
evidence_files	JSON	Archivos adjuntos cifrados
received_at	TIMESTAMP	Fecha de recepcion
acknowledged_at	TIMESTAMP	Fecha de acuse de recibo (max 7 dias)
investigation_status	ENUM	received|investigating|resolved|archived
resolution	TEXT	Resolucion adoptada
resolved_at	TIMESTAMP	Fecha de resolucion (max 3 meses)
handler_id	UUID FK	Responsable del canal

7.2 Requisitos de Implementacion
•	Acceso anonimo: formulario sin requerir autenticacion
•	Cifrado end-to-end de las comunicaciones
•	Codigo de seguimiento anonimo para consultar estado
•	Acuse de recibo automatico en maximo 7 dias
•	Plazo de resolucion: maximo 3 meses desde acuse
•	Proteccion del informante: prohibicion de represalias
•	Responsable del canal: designado e independiente
 
8. Implementacion Tecnica
8.1 Modulo: jaraba_legal
•	jaraba_legal.info.yml: Definicion del modulo
•	src/Service/TosManager.php: Gestion de versiones y aceptacion de ToS
•	src/Service/SlaCalculator.php: Calculo automatico de uptime y creditos
•	src/Service/AupEnforcer.php: Verificacion de limites de uso en tiempo real
•	src/Service/OffboardingManager.php: Orquestacion del proceso de baja
•	src/Service/WhistleblowerChannel.php: Canal de denuncias cifrado

8.2 Flujos ECA
•	ToS Update: Cuando se publica nueva version, notificar a todos los tenants y requerir re-aceptacion
•	SLA Breach: Cuando uptime cae por debajo del umbral, calcular credito y notificar
•	AUP Violation: Cuando se detecta uso prohibido, suspender + notificar + log
•	Offboarding: Workflow completo de 30 dias con reminders automaticos
•	Whistleblower: Acuse de recibo automatico + escalacion a responsable del canal
 
9. Estimacion de Implementacion
Componente	Horas	Coste EUR	Prioridad
ToS Manager + Versionado	8-10h	360-450	CRITICA
SLA Calculator + Dashboard	6-8h	270-360	ALTA
AUP Enforcer + Rate Limiting	6-8h	270-360	ALTA
Offboarding Manager + Export	8-10h	360-450	CRITICA
Canal de Denuncias	6-8h	270-360	OBLIGATORIO
Templates legales HTML/PDF	4-5h	180-225	CRITICA
TOTAL	38-49h	1,710-2,205	N1 BLOQUEANTE

--- Fin del Documento ---
Jaraba Impact Platform | Especificacion Tecnica v1.0 | Febrero 2026
