
AUDITORIA CLAUDE CODE READINESS
Documentacion Nivel 3 - Enterprise Class (194-200)
Nivel de Madurez: N3 - Enterprise Class
7 documentos | 475-610h estimadas | SOC 2, ISO 27001, ENS, HA, SLA, SSO, Governance
Version:	1.0
Fecha:	Febrero 2026
Codigo:	203_Auditoria_N3_ClaudeCode_Readiness_v1
Docs Auditados:	194, 195, 196, 197, 198, 199, 200
Score Global N3:	10.4% Claude Code Ready
Hallazgo Clave:	N3 es 70% gobernanza/proceso y solo 30% codigo implementable
 
1. Hallazgo Fundamental: N3 No Es Principalmente Codigo

El Nivel 3 tiene una naturaleza radicalmente distinta a N1 y N2. La mayoria de estos documentos describen frameworks de gobernanza, procesos de certificacion, y configuracion de infraestructura — no modulos Drupal.

Desglose del contenido N3:

Tipo de Contenido	% del N3	Implementable por Claude Code?	Ejemplo
Politicas y procedimientos documentales	30%	NO - Requiere decisiones humanas	Politica seguridad org.1, Declaracion conformidad ENS
Configuracion infraestructura servidor	20%	PARCIAL - Scripts bash/config files	MariaDB Galera, HAProxy, ProxySQL configs
Modulos Drupal de gestion	25%	SI - Framework C1-C12 estandar	jaraba_sla, jaraba_sso, jaraba_governance
Integraciones externas (IdP, APIs)	15%	PARCIAL - Config + adapter code	SAML SP, SCIM endpoints, VIES API
Procesos de auditoria y certificacion	10%	NO - Requiere auditor externo	Auditoria SOC 2 Type II, Certificacion ENS

Implicacion critica: Solo ~40% del contenido de N3 es implementable por Claude Code. El 60% restante son decisiones de gobierno, procesos organizacionales y auditorias externas que ningun LLM puede ejecutar. La auditoria debe distinguir claramente entre la parte 'codificable' y la parte 'procesal'.
 
2. Arquetipos Tecnologicos del Nivel 3

Arquetipo	Docs	% Codigo	% Proceso	Que Puede Hacer Claude Code
E: Compliance Framework	194, 195, 196	20%	80%	Modulo de evidence automation + entities de control
F: Infra Config	197	40%	60%	Config files (Galera, HAProxy, ProxySQL) + deploy scripts
A: Modulo Drupal Puro	198, 200	70%	30%	Modulo completo C1-C12 como N1/N2
G: Identity Integration	199	60%	40%	Modulo SSO + SAML SP + SCIM server endpoints

Nuevo arquetipo E: Compliance Framework. Los docs 194 (SOC 2), 195 (ISO 27001) y 196 (ENS) son 80% proceso y 20% codigo. El codigo que SI se puede implementar es un modulo jaraba_compliance que automatiza la recoleccion de evidencia y el tracking de controles. Pero las politicas, las decisiones de riesgo, y la certificacion son HUMANAS.
 
3. Doc 194: SOC 2 Type II Readiness
Arquetipo: E - Compliance Framework
Ratio Codigo/Proceso: 20% codigo / 80% proceso

3.1 Parte Codificable (jaraba_compliance)
Componente	Estado	Detalle
C1: info.yml	FALTA	No existe modulo jaraba_compliance definido
C2: permissions.yml	FALTA	No incluido
C3: routing.yml	FALTA	Sin rutas para dashboard compliance o auditor portal
C4: services.yml	FALTA	Sin DI para evidence collector services
C5: Entity PHP	PARCIAL	soc2_control entity en tabla pero sin PHP baseFields
C6: Service contracts	FALTA	Evidence automation descrita conceptualmente, sin metodos
C7: Controllers	FALTA	Sin auditor portal API controller
C8: Forms	FALTA	Sin control assessment forms
C9: config/install	FALTA	TSC mapping en prosa, no config YAML
C10: config/schema	FALTA	No incluido
C11: ECA recipes	FALTA	Evidence collection automation sin YAML
C12: Twig templates	FALTA	Sin compliance dashboard template

3.2 Parte NO Codificable (proceso humano)
•	Declaracion de alcance del SOC 2 (decision de management)
•	Seleccion de Trust Service Criteria aplicables (decision de management)
•	Redaccion de politicas de seguridad (legal + management)
•	Contratacion de auditor CPA acreditado
•	Periodo de observacion de 6 meses (ejecucion operacional)
•	Respuesta a findings del auditor (decision de management)
•	Publicacion del informe SOC 2 (auditor externo)

Score codificable: 1/12 parcial = 4.2%
Score total (ponderado 20% codigo): ~0.8% implementable
 
4. Doc 195: ISO 27001 SGSI
Arquetipo: E - Compliance Framework
Ratio Codigo/Proceso: 20% codigo / 80% proceso

4.1 Parte Codificable
Componente	Estado	Detalle
C5: Entity PHP	PARCIAL	risk_assessment entity en tabla, sin PHP
C6: Service contracts	FALTA	Risk calculator sin metodos tipados
C11: ECA recipes	FALTA	Quarterly review trigger sin YAML
Otros C1-C12	FALTA	Nada de info.yml, routing, services, etc.

4.2 Parte NO Codificable
•	Declaracion de alcance del SGSI y Statement of Applicability (SoA)
•	Analisis de riesgos con evaluacion de likelihood/impact (humano)
•	Decision de tratamiento: aceptar, mitigar, transferir, evitar (management)
•	93 controles Annex A: cada uno requiere evaluacion individual
•	Auditoria interna + auditoria de certificacion (auditor acreditado AENOR/BSI)
•	Revision por la direccion (acta de reunion, no codigo)

Score codificable: 1/12 parcial = 4.2% | ~0.8% implementable

NOTA: Doc 195 y doc 194 deberian compartir el MISMO modulo jaraba_compliance. No tiene sentido crear un modulo para SOC 2 y otro para ISO 27001 — ambos son frameworks de controles con entities de control + evidence + assessment.
 
5. Doc 196: ENS Compliance
Arquetipo: E - Compliance Framework
Ratio Codigo/Proceso: 15% codigo / 85% proceso

5.1 Parte Codificable
Componente	Estado	Detalle
C5: Entity PHP	PARCIAL	ens_compliance entity en tabla, sin PHP
C11: ECA recipes	FALTA	Biennial audit trigger sin YAML
Otros C1-C12	FALTA	Sin modulo Drupal definido

5.2 Parte NO Codificable
•	Categorizacion del sistema: decision de Responsable de Seguridad
•	75+ medidas ENS categoria MEDIA: cada una requiere evaluacion manual
•	Declaracion de Conformidad (documento legal firmado)
•	Auditoria bienal por auditor acreditado CCN
•	Plan de Adecuacion si hay incumplimientos

Score codificable: 1/12 parcial = 4.2% | ~0.6% implementable

RECOMENDACION CRITICA: FUSIONAR docs 194+195+196 en un unico modulo jaraba_compliance con entities polymorficas: compliance_control(framework=soc2|iso27001|ens), compliance_evidence, compliance_assessment, compliance_finding. Esto reduce 3 implementaciones a 1.
 
6. Doc 197: HA Multi-Region
Arquetipo: F - Infraestructura Config
Ratio Codigo/Proceso: 40% config files / 60% arquitectura/proceso

6.1 Parte Codificable (config files, NO modulo Drupal)
Componente Infra	Estado	Detalle
MariaDB Galera config	PARCIAL	Parametros wsrep_ listados pero sin my.cnf completo
ProxySQL config	PARCIAL	Read/write splitting descrito, sin proxysql.cnf
HAProxy/Nginx LB config	FALTA	Mencion conceptual sin nginx.conf o haproxy.cfg
Redis Sentinel config	FALTA	Sin sentinel.conf
Blue-Green deploy script	PARCIAL	6 pasos descritos pero sin bash script
DB migration rules	TIENE	Bien definidas: additive only, backward compat
Health check endpoints	FALTA	Sin Drupal health check controller
Monitoring extensions	FALTA	Sin Prometheus exporters config para cluster

6.2 Parte NO Codificable
•	Decision de arquitectura: cuando migrar de single-node a cluster (negocio)
•	Seleccion de proveedor multi-region (IONOS vs AWS vs Hetzner)
•	Budget approval para infraestructura adicional (~3x coste actual)
•	Testing de failover en produccion (operacion humana supervisada)

Score codificable: 1 TIENE + 3 PARCIAL de 8 = 31.3%

NOTA IMPORTANTE: Este doc NO produce un modulo Drupal. Produce config files de servidor (my.cnf, proxysql.cnf, haproxy.cfg, sentinel.conf) + scripts de deployment. Claude Code puede generar estos ficheros pero el contexto es distinto al de un modulo. El unico componente Drupal seria un health check controller en jaraba_core.
 
7. Doc 198: SLA Management
Arquetipo: A - Modulo Drupal Puro (jaraba_sla)
Ratio Codigo/Proceso: 70% codigo / 30% proceso

Componente	Estado	Detalle
C1: info.yml	FALTA	No incluido
C2: permissions.yml	FALTA	No incluido
C3: routing.yml	FALTA	Status page y admin sin routing YAML
C4: services.yml	FALTA	SlaCalculator, UptimeMonitor sin DI
C5: Entity PHP	PARCIAL	sla_agreement + sla_measurement en tablas
C6: Service contracts	PARCIAL	Uptime calculation formula bien definida, sin PHP
C7: Controllers	FALTA	Status page API sin controller
C8: Forms	FALTA	Sin SLA config form
C9: config/install	PARCIAL	SLA tiers bien definidos (99.9/99.95/99.99) pero en prosa
C10: config/schema	FALTA	No incluido
C11: ECA recipes	PARCIAL	Credit calculation + postmortem trigger descritos
C12: Twig templates	FALTA	Sin status page template

Gap especifico: El doc 198 es el mas 'implementable' de N3. La formula de uptime, los SLA tiers, y la logica de creditos estan bien definidos. Falta convertirlo todo a codigo Drupal. Bonus: el status page template debe integrarse con doc 133 (Monitoring/Prometheus).
Score: 4/12 parciales = 16.7%
 
8. Doc 199: SSO SAML SCIM
Arquetipo: G - Identity Integration (jaraba_sso)
Ratio Codigo/Proceso: 60% codigo / 40% proceso/config IdP

Componente	Estado	Detalle
C1: info.yml	FALTA	No incluido
C2: permissions.yml	FALTA	No incluido
C3: routing.yml	PARCIAL	SCIM endpoints bien listados, sin routing YAML
C4: services.yml	FALTA	SamlManager, ScimHandler sin DI
C5: Entity PHP	PARCIAL	sso_configuration entity con campos detallados
C6: Service contracts	PARCIAL	SAML SP-initiated flow bien descrito, sin PHP
C7: Controllers	FALTA	Sin SAML ACS controller ni SCIM endpoint controllers
C8: Forms	FALTA	Sin SSO config form per tenant
C9: config/install	PARCIAL	MFA policies bien definidas pero en prosa
C10: config/schema	FALTA	No incluido
C11: ECA recipes	PARCIAL	JIT provisioning descrito, sin YAML
C12: Twig templates	FALTA	Sin SSO login page template

Gap especifico: Doc 199 es el segundo mas implementable de N3 y tambien el mas complejo tecnicamente. El flujo SAML SP-Initiated esta bien descrito pero falta el controlador ACS (Assertion Consumer Service) que procesa la SAMLResponse. Los SCIM endpoints necesitan un REST Resource Plugin de Drupal completo. Dependencia critica: modulo 'simplesamlphp_auth' o 'saml_sp' de Drupal contrib.
Score: 5/12 parciales = 20.8%
 
9. Doc 200: Data Governance
Arquetipo: A - Modulo Drupal Puro (jaraba_governance)
Ratio Codigo/Proceso: 65% codigo / 35% proceso/politica

Componente	Estado	Detalle
C1: info.yml	FALTA	No incluido
C2: permissions.yml	FALTA	No incluido
C3: routing.yml	FALTA	Sin rutas para data catalog o lineage viewer
C4: services.yml	FALTA	Classification, Retention, Lineage services sin DI
C5: Entity PHP	PARCIAL	data_classification + data_lineage_event en tablas detalladas
C6: Service contracts	PARCIAL	KMS strategy + masking rules bien definidos
C7: Controllers	FALTA	Sin governance API controller
C8: Forms	FALTA	Sin classification management form
C9: config/install	PARCIAL	Retention policies + classification levels bien definidos en prosa
C10: config/schema	FALTA	No incluido
C11: ECA recipes	PARCIAL	Auto-deletion cron + lineage tracking descritos
C12: Twig templates	FALTA	Sin data catalog dashboard template

Gap especifico: Doc 200 tiene el contenido mas rico de N3 en terminos de datos estructurados: 4 niveles de clasificacion (C1-C4), 7 politicas de retencion con plazos concretos, reglas de masking por tipo de dato, y estrategia KMS completa. Todo esto se traduce directamente a config YAML + services PHP. El data lineage tracker es un event subscriber que registra cada operacion CRUD.
Score: 4/12 parciales = 16.7%
 
10. Resumen Consolidado Nivel 3

10.1 Score por Documento
Doc	Modulo/Output	Arquetipo	% Codificable	Score Code	Ready?
194	jaraba_compliance (compartido)	E: Compliance	20%	4.2%	NO
195	jaraba_compliance (compartido)	E: Compliance	20%	4.2%	NO
196	jaraba_compliance (compartido)	E: Compliance	15%	4.2%	NO
197	Config files servidor	F: Infra	40%	31.3%	NO
198	jaraba_sla	A: Drupal	70%	16.7%	NO
199	jaraba_sso	G: Identity	60%	20.8%	NO
200	jaraba_governance	A: Drupal	65%	16.7%	NO
MEDIA N3			41%	14.0%	NO

10.2 Score Ponderado por Codificabilidad
El score bruto es enganoso porque incluye contenido no codificable. El score real ponderado:

Doc	Score Bruto C1-C12	% Codificable	Score Ponderado	Score Efectivo
194 SOC 2	4.2%	20%	4.2% x 0.20	0.8%
195 ISO 27001	4.2%	20%	4.2% x 0.20	0.8%
196 ENS	4.2%	15%	4.2% x 0.15	0.6%
197 HA	31.3%	40%	31.3% x 0.40	12.5%
198 SLA	16.7%	70%	16.7% x 0.70	11.7%
199 SSO	20.8%	60%	20.8% x 0.60	12.5%
200 Governance	16.7%	65%	16.7% x 0.65	10.8%
TOTAL N3				Promedio: 7.1%

N3 es el nivel con MENOS codigo implementable por Claude Code: 7.1% efectivo vs 12.5% de N1 y 15.6% de N2. Esto es logico: Enterprise Class es fundamentalmente gobernanza, no codigo.
 
11. Comparativa Consolidada N1 + N2 + N3

Dimension	N1 Production	N2 Growth	N3 Enterprise
Documentos	3	8	7
Score bruto medio	12.5%	15.6%	14.0%
Score efectivo ponderado	12.5%	15.6%	7.1%
% contenido codificable	95%	85%	41%
Arquetipos tecnologicos	1 (Drupal)	4 (Drupal/Python/Mobile/SDK)	4 (Compliance/Infra/Drupal/Identity)
Modulos Drupal resultantes	3	7-8	3 (+config files)
Sesiones Claude para v2	3	14-17	8-10
Dependencias docs existentes	Baja	Alta	Muy alta (115,133,138)
Riesgo implementacion	BAJO	MEDIO-ALTO	ALTO (compliance)
 
12. Recomendacion Critica: Fusion de Compliance

Los docs 194 + 195 + 196 deben generar UN UNICO modulo jaraba_compliance, no tres modulos separados.

Razon: SOC 2, ISO 27001 y ENS son tres frameworks distintos que auditan lo mismo (controles de seguridad) con diferentes nomenclaturas. La entidad base es identica:

•	compliance_control: framework(soc2|iso27001|ens), control_id, description, status, evidence
•	compliance_evidence: control_id, evidence_type, source, timestamp, automated(bool)
•	compliance_assessment: framework, assessor, date, score, findings
•	compliance_finding: severity, description, remediation, deadline, status

Un mapping entre frameworks permite mantener UN control que satisface MULTIPLES frameworks:

Control Jaraba	SOC 2 (TSC)	ISO 27001 (Annex A)	ENS
MFA para admins	CC6.1 Logical Access	A.9.4.2 Secure log-on	op.acc.5 Autenticacion
Cifrado en transito	CC6.7 Data in Transit	A.10.1.1 Cryptographic controls	mp.com.2 Proteccion comunicaciones
Backup diario	A1.2 Recovery Mechanisms	A.12.3.1 Information backup	op.cont.1 Analisis impacto
Audit logs	CC4.1 Monitoring	A.12.4.1 Event logging	op.exp.8 Registro actividad
Vulnerability scan	CC7.1 System Ops	A.12.6.1 Tech vulnerability mgmt	op.exp.4 Mantenimiento

Ahorro estimado: 1 modulo en vez de 3 = ~60% reduccion de codigo duplicado. 79+93+68 = 240h estimadas separadas vs ~100h fusionadas.
 
13. Plan de Accion: Upgrade N3 a v2

13.1 Orden Optimo
Fase	Doc(s)	Output	Sesiones	Pre-requisitos
Fase 1	198 SLA	jaraba_sla completo	1	Doc 133 (Monitoring)
Fase 1	200 Governance	jaraba_governance completo	1-2	Doc 183 (GDPR)
Fase 2	199 SSO	jaraba_sso completo	2	simplesamlphp_auth contrib
Fase 2	197 HA	Config files + deploy scripts	1-2	Acceso server IONOS
Fase 3	194+195+196	jaraba_compliance unificado	3-4	Docs 115+138 existentes

13.2 Estimacion Total N3
Fase	Sesiones	Horas	Modulos Resultantes
Fase 1: Quick Wins	2-3	~6h	jaraba_sla + jaraba_governance
Fase 2: Identity + Infra	3-4	~8h	jaraba_sso + server configs
Fase 3: Compliance Unificado	3-4	~8h	jaraba_compliance (SOC2+ISO+ENS)
TOTAL N3	8-11	~22h	3 modulos Drupal + config files

13.3 Lo Que NO Puede Hacer Claude Code en N3
Es importante ser explicito sobre los limites:

•	NO puede: Redactar la Politica de Seguridad corporativa (requiere decisiones de management)
•	NO puede: Ejecutar el analisis de riesgos ISO 27001 (requiere evaluacion humana de amenazas)
•	NO puede: Categorizar el sistema bajo ENS (requiere Responsable de Seguridad designado)
•	NO puede: Realizar la auditoria SOC 2 (requiere CPA acreditado)
•	NO puede: Decidir la arquitectura HA (requiere analisis de coste-beneficio con datos reales)

•	SI puede: Crear modulos Drupal que AUTOMATIZAN la recoleccion de evidencia
•	SI puede: Generar config files de servidor (Galera, ProxySQL, HAProxy)
•	SI puede: Implementar SSO/SAML/SCIM completo como modulo Drupal
•	SI puede: Crear el framework de data governance con classification + lineage + retention
•	SI puede: Implementar status page + SLA calculator + credit automation
 
14. Resumen Ejecutivo Global: Los 3 Niveles

Nivel	Docs	Score Efectivo	Sesiones v2	Modulos Drupal	Otros Outputs
N1 Production	183-185	12.5%	3	3	---
N2 Growth	186-193	15.6%	14-17	7-8	Python pipeline, Capacitor app, SDK
N3 Enterprise	194-200	7.1%	8-11	3	Server configs, policy docs (humano)
TOTAL	183-200	11.7%	25-31	13-14	

Conclusion final: Los 18 documentos N1-N3 requieren entre 25 y 31 sesiones de Claude Code para alcanzar el nivel 'implementable'. Esto producira 13-14 modulos Drupal operativos + config files de servidor + una app Capacitor + un SDK de conectores. El esfuerzo total estimado es de ~60-70 horas de Claude, distribuidas optimamente en 4-6 semanas.

--- Fin del Documento ---
