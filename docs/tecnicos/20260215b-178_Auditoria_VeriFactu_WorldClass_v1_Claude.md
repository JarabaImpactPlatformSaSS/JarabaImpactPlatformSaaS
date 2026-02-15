
AUDITORÍA VERIFACTU
& ANÁLISIS GAP NIVEL 1 CLASE MUNDIAL
Sistema de Facturación Verificable AEAT + Roadmap SaaS Enterprise
Plataforma:	Jaraba Impact Platform
Versión:	1.0
Fecha:	Febrero 2026
Código:	178_Auditoria_VeriFactu_WorldClass_v1
Estado:	Análisis Estratégico - Ready for Review
Prioridad:	CRÍTICA - Compliance Legal 2027
 
 
1. Resumen Ejecutivo
Este documento responde a dos preguntas críticas para el Ecosistema Jaraba: (A) ¿Tenemos implementado VeriFactu (el sistema de facturación verificable obligatorio de la AEAT)? (B) ¿Qué nos falta para alcanzar el Nivel 1 de Clase Mundial como plataforma SaaS?

ALERTA: VeriFactu NO está implementado como módulo independiente en el ecosistema. Solo existe una MENCIÓN de compliance en el doc 96_ServiciosConecta_Sistema_Facturacion, pero sin especificación técnica real. La obligatoriedad entra en vigor el 1 de enero de 2027 para sociedades (IS) y el 1 de julio de 2027 para autónomos (IRPF). Multas de hasta 50.000€ por incumplimiento.

1.1 Hallazgos Clave
Área	Estado	Impacto
VeriFactu - Plataforma SaaS	NO IMPLEMENTADO	Jaraba como emisor de facturas a tenants
VeriFactu - Vertical ServiciosConecta	PARCIAL	Mencionado en doc 96, sin especificación técnica
VeriFactu - Verticales Commerce	NO IMPLEMENTADO	AgroConecta/ComercioConecta no contemplan VeriFactu
Facturae 3.2.2	PARCIAL	Mencionado en ServiciosConecta, sin implementación
SII (Suministro Inmediato Información)	NO IMPLEMENTADO	Obligatorio para empresas >6M€ facturación
Nivel SaaS Clase Mundial	PARCIAL	Documentación 95% completa, implementación ~5%
 
2. Análisis VeriFactu: Estado Actual del Ecosistema
2.1 ¿Qué es VeriFactu?
VeriFactu (Veri*Factu) es el sistema de facturación verificable de la Agencia Tributaria española (AEAT), establecido por el Real Decreto 1007/2023 y la Orden HAC/1177/2024. Exige que todo software de facturación cumpla con requisitos de integridad, trazabilidad e inalterabilidad de los registros de facturación.

2.1.1 Requisitos Técnicos Clave
Requisito	Descripción	Jaraba
Hash encadenado	Cada registro de factura incluye un hash SHA-256 encadenado con el registro anterior	Implementado en Buzón de Confianza (doc 88) pero NO en facturación
Firma electrónica	Registros firmados con certificado digital reconocido	PAdES implementado en doc 89, NO conectado a facturación
Envío automático AEAT	Transmisión de registros vía Web Service SOAP/REST a la AEAT	NO existe. Ni siquiera hay especificación
QR de verificación	Código QR en cada factura que permite verificación en portal AEAT	QR existe en trazabilidad (doc 80-81), NO en facturas
Inalterabilidad	Registros append-only, prohibido borrar o modificar facturas emitidas	FOC usa append-only para transacciones, extensible
Etiqueta VERI*FACTU	Facturas generadas deben incluir la etiqueta visible	NO existe en PDFs de facturación

2.2 Plazos Legales
ALERTA: El Real Decreto-ley 15/2025, de 2 de diciembre de 2025, pospuso los plazos originales. Los nuevos plazos son: Sociedades (IS): 1 de enero de 2027. Autónomos (IRPF): 1 de julio de 2027. Multas por incumplimiento: hasta 50.000€.
Esto significa que Jaraba Impact S.L. como empresa emisora de facturas a sus tenants debe cumplir con VeriFactu antes del 1 de enero de 2027. Además, los tenants autónomos que usan ServiciosConecta o las verticales de comercio para facturar a sus clientes necesitan que la plataforma les proporcione un sistema de facturación VeriFactu-compliant antes del 1 de julio de 2027.

2.3 Doble Impacto en el Ecosistema
Escenario	Afecta a	Deadline	Prioridad
Jaraba como empresa	Facturas SaaS a tenants (suscripciones, add-ons, comisiones)	1 enero 2027	CRÍTICO
ServiciosConecta	Profesionales autónomos que facturan a clientes finales desde la plataforma	1 julio 2027	CRÍTICO
AgroConecta / ComercioConecta	Productores/comerciantes que venden y necesitan emitir facturas/tickets	1 julio 2027	ALTO
 
3. Componentes Existentes Reutilizables para VeriFactu
La buena noticia es que el ecosistema ya tiene varios componentes técnicos que pueden reutilizarse para una implementación VeriFactu eficiente:
Componente	Doc Existente	Reutilizable	Adaptación Necesaria
Hash encadenado SHA-256	88_Buzon_Confianza (IntegrityService, verifyIntegrity())	80%	Adaptar de documentos a registros de facturación
Firma digital PAdES	89_Firma_Digital_PAdES (certificados, AutoFirma, TSA)	60%	Adaptar firma de documentos a firma de registros XML
Generación PDF factura	96_Sistema_Facturacion (InvoiceService, PDF generation)	70%	Añadir QR VeriFactu y etiqueta VERI*FACTU
QR dinámicos	65_Dynamic_QR / 81_QR_Dynamic (QrGeneratorService)	50%	Generar QR con URL de verificación AEAT
Append-only logs	FOC (financial_transaction inmutable)	90%	Extender modelo para registros VeriFactu
Stripe Invoicing	134_Stripe_Billing (auto-invoicing, Stripe Tax)	40%	Stripe genera facturas pero NO cumple VeriFactu
ECA Automations	06_Flujos_ECA, 96_Facturación	85%	Añadir triggers de envío AEAT post-emisión

INFO: El ecosistema tiene piezas técnicas sólidas (hash chains, firma digital, QR, append-only). Lo que falta es un módulo jaraba_verifactu que las orqueste según las especificaciones exactas del RD 1007/2023 y la Orden HAC/1177/2024.
 
4. Especificación: Módulo jaraba_verifactu (Propuesta)
4.1 Alcance Funcional
Función	Descripción	Horas Est.
Registro facturación	Entidad verifactu_record con hash chain, datos obligatorios RD 1007/2023	20-25h
Web Service AEAT	Cliente SOAP para envío automático de registros al endpoint AEAT (Art. 16.1)	30-40h
Firma electrónica	Firma de registros con certificado digital (reutilizar 89_PAdES adaptado a XAdES)	15-20h
QR verificación	Generación QR con URL de verificación AEAT en cada factura PDF	8-12h
Etiqueta VERI*FACTU	Inclusión automática de la etiqueta en PDFs y pantallas de factura	3-5h
Log inmutable	Registro de eventos del sistema con hash chain (reutilizar IntegrityService)	10-15h
Dashboard compliance	Panel de estado de envíos, errores, reintentos, estadísticas AEAT	15-20h
Multi-tenant	Cada tenant puede configurar su certificado y gestionar sus registros	10-15h
TOTAL ESTIMADO		111-152 horas
4.2 Dependencias Técnicas
El módulo jaraba_verifactu se integra con: jaraba_stripe (eventos de factura), 96_Sistema_Facturacion (generación PDF), 89_Firma_Digital (firma XAdES), 88_Buzon_Confianza (IntegrityService para hash chains), y jaraba_foc_entities (registros financieros inmutables).
 
5. Análisis Gap: Nivel 1 de Clase Mundial
5.1 Definición de Niveles
Basándonos en el benchmark de plataformas SaaS enterprise (Salesforce, HubSpot, Stripe, Shopify Plus) y el documento de Investigación de Mercado, definimos 4 niveles de madurez:
Nivel	Nombre	Características	Jaraba
Nivel 0	MVP Funcional	Funcionalidades core operativas, 1-2 verticales	PARCIAL
Nivel 1	Production-Ready	Compliance, seguridad, onboarding, billing, analytics básico	OBJETIVO ACTUAL
Nivel 2	Growth-Ready	PLG, marketplace integraciones, agentes IA, mobile	Futuro (6-12 meses)
Nivel 3	Enterprise-Class	SOC 2 Type II, ISO 27001, multi-región, 99.99% SLA	Futuro (12-24 meses)

5.2 Requisitos Nivel 1: Production-Ready
El Nivel 1 significa que la plataforma puede recibir clientes de pago con confianza, cumplir regulaciones, procesar pagos de forma fiable, y operar con métricas de negocio reales.

Requisito	Doc/Módulo	Estado	Gap	Prioridad
COMPLIANCE & LEGAL
VeriFactu compliance	NO EXISTE	NO IMPLEMENTADO	Módulo completo necesario	CRÍTICO
GDPR/LOPD-GDD	115_Security	ESPECIFICADO	Implementar consentimientos, DPO	CRÍTICO
Política privacidad activa	115_Security	PARCIAL	Templates legales multi-tenant	CRÍTICO
WCAG 2.1 AA	170_Accessibility	ESPECIFICADO	Auditoría real + fixes	ALTO
BILLING & PAYMENTS
Stripe Connect operativo	134_Stripe_Billing	ESPECIFICADO	Implementar y testear	CRÍTICO
Planes y pricing activos	111_UsageBased, 158_Matrix	ESPECIFICADO	Configurar en Stripe Dashboard	CRÍTICO
Customer Portal Stripe	134_Stripe_Billing	ESPECIFICADO	Integrar portal self-service	ALTO
Facturación legal española	134 (Sec 8)	PARCIAL	VeriFactu + Facturae 3.2.2	CRÍTICO
SEGURIDAD & INFRAESTRUCTURA
HTTPS + TLS 1.3	131_Infrastructure	ESPECIFICADO	Verificar configuración	CRÍTICO
Backups automáticos	131_Infrastructure	ESPECIFICADO	Implementar y testear DR	CRÍTICO
MFA para admins	115_Security	ESPECIFICADO	Implementar TOTP/WebAuthn	ALTO
Rate limiting APIs	03_APIs, 137_Gateway	ESPECIFICADO	Implementar en API Gateway	ALTO
Audit logging	115_Security	ESPECIFICADO	Implementar audit_log entity	ALTO
CORE PLATFORM
Multi-tenancy operativo	07_MultiTenant	DEFINIDO	Implementar Group + test aislamiento	CRÍTICO
Auth + RBAC funcional	04_Permisos_RBAC	DEFINIDO	Implementar roles por vertical	CRÍTICO
Admin Center básico	104_Admin_Center	ESPECIFICADO	Implementar dashboard + tenant mgmt	CRÍTICO
1 vertical operativa completa	Empleabilidad (08-24)	ESPECIFICADO	Implementar end-to-end	CRÍTICO
UX & ONBOARDING
Registro + activación tenant	110_Onboarding	ESPECIFICADO	Implementar flow signup-to-active	CRÍTICO
TTV < 5 minutos	Diagnostico_Express_TTV	ESPECIFICADO	Implementar wizard de setup	ALTO
Email transaccional	136_Email_Templates	ESPECIFICADO	Configurar SendGrid + templates	CRÍTICO
ANALYTICS & MÉTRICAS
Métricas SaaS básicas	FOC (jaraba_foc_metrics)	DEFINIDO	Implementar MRR, Churn, LTV	ALTO
Dashboard por rol	22-24, 41-43	ESPECIFICADO	Implementar dashboards	ALTO
 
6. Scorecard: Distancia al Nivel 1
Categoría	Peso	Puntuación	Ponderado	Nota
Compliance Legal	25%	15/100	3.75	VeriFactu es bloqueante
Billing & Payments	20%	30/100	6.00	Stripe especificado, no operativo
Seguridad	20%	25/100	5.00	Diseñada, no implementada
Core Platform	20%	20/100	4.00	Multi-tenant sin code real
UX & Onboarding	10%	10/100	1.00	Solo demos HTML
Analytics	5%	20/100	1.00	FOC definido, no operativo
TOTAL PONDERADO	100%		20.75 / 100	Nivel 0 incompleto

ALERTA: La documentación está al 95% de completitud (170+ docs), pero la implementación real está al ~5%. Para alcanzar Nivel 1 se necesita pasar de especificaciones a código funcional. El gap principal no es de diseño sino de EJECUCIÓN.
 
7. Roadmap Prioritario: De Nivel 0 a Nivel 1
7.1 Fase 0: Cimientos Legales (Meses 1-2)
BLOQUEA TODO LO DEMÁS. Sin esto no se puede operar comercialmente en España.
Tarea	Horas	Bloquea	Responsable
Implementar jaraba_verifactu core	111-152h	Facturación legal	Backend Senior
Configurar Stripe Connect producción	20-30h	Revenue	Full-stack
GDPR: consentimientos + DPA	15-20h	Datos personales	Legal + Dev
Email transaccional (SendGrid)	10-15h	Comunicación	DevOps + Backend

7.2 Fase 1: Core Operativo (Meses 3-5)
Tarea	Horas	Bloquea	Responsable
Core Platform (01-07 implementados)	310-390h	Todo	Backend Team
Admin Center básico (104 simplificado)	40-60h	Gestión	Full-stack
1 vertical piloto: Empleabilidad LMS	200-280h	Primer tenant	Backend + Frontend
Frontend Architecture (100 base)	80-120h	UX	Frontend Team

7.3 Fase 2: Production-Ready = Nivel 1 (Meses 6-8)
Tarea	Horas	Bloquea	Responsable
Onboarding Product-Led (110 simplificado)	60-80h	Activación	UX + Backend
FOC métricas SaaS operativas	80-100h	Unit Economics	Backend
Security hardening + audit log	40-60h	Compliance	DevOps
Testing E2E + QA	60-80h	Calidad	QA Team
Knowledge Base mínima (114 lite)	30-40h	Soporte	Content + Dev
 
8. Inversión Estimada: Nivel 1
Fase	Horas Min	Horas Max	Coste (€45/h)
Fase 0: Cimientos Legales	156	217	€7,020 - €9,765
Fase 1: Core Operativo	630	850	€28,350 - €38,250
Fase 2: Production-Ready	270	360	€12,150 - €16,200
TOTAL NIVEL 1	1,056	1,427	€47,520 - €64,215

Esto representa aproximadamente el 25-30% del presupuesto total del ecosistema (€189K-€243K) y cubre los 8 primeros meses del roadmap de 18 meses. El VeriFactu por sí solo representa ~€5K-€7K, una inversión menor comparada con las multas de €50K por incumplimiento.

9. Conclusiones y Recomendaciones
9.1 VeriFactu: Acción Inmediata Requerida
ALERTA: RECOMENDACIÓN: Crear el documento técnico 178_Platform_VeriFactu_v1 como especificación prioritaria P0, incorporarlo al Sprint 1-2 del roadmap, y comenzar desarrollo en paralelo con el Core Platform. El deadline legal (1 enero 2027) no es negociable.

9.2 Nivel 1: Foco en Ejecución
El ecosistema Jaraba tiene una documentación excepcional (170+ documentos técnicos que cubren el 95% de las necesidades). Sin embargo, la distancia al Nivel 1 no es un problema de diseño sino de velocidad de ejecución. Las recomendaciones clave son:
1. VeriFactu PRIMERO. Es la única pieza que tiene un deadline legal inamovible.
2. Vertical piloto única. Empleabilidad como primera vertical completa end-to-end, no 5 verticales parciales.
3. Stripe antes que FOC completo. Cobrar es más urgente que tener analytics sofisticados.
4. Sin Humo en la implementación. Aplicar la propia filosofía del proyecto: progreso antes que perfección.

--- Fin del Documento ---
Jaraba Impact Platform | Auditoría VeriFactu & World-Class v1.0 | Febrero 2026
