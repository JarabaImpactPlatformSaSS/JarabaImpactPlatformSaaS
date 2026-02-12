
ANÁLISIS DE BRECHAS

DOCUMENTACIÓN TÉCNICA DE IMPLEMENTACIÓN

Vertical ServiciosConecta
Digitalización del Capital Intelectual y Profesional

Versión:	1.0
Fecha:	Enero 2026
Estado:	Análisis Estratégico
Vertical:	ServiciosConecta (Avatar: Elena)
Posicionamiento:	Plataforma de Confianza Digital para Profesionales
Clasificación:	Interno - Planificación Técnica
 
1. Resumen Ejecutivo
Este documento identifica las especificaciones técnicas de implementación necesarias para la vertical ServiciosConecta del Ecosistema Jaraba. ServiciosConecta es el vertical diseñado para la digitalización del capital intelectual y profesional de zonas rurales y periurbanas: abogados, clínicas, arquitectos, consultores, técnicos y otros profesionales que venden confianza, tiempo y conocimiento.
1.1 Posicionamiento Estratégico
ServiciosConecta opera bajo un paradigma diferente a los verticales de comercio: "No vendemos stock, vendemos confianza, tiempo y conocimiento". El pitch diferenciador es: "Tu consulta profesional en la nube: agenda, documentos y firmas sin salir de la plataforma".
Este vertical completa el ecosistema de impacto digital, siendo el quinto y último vertical comercializable del Ecosistema Jaraba.
1.2 Documentación Existente
La vertical de ServiciosConecta cuenta actualmente con:
•	Plan Maestro Consolidado - Definición conceptual y 5 componentes exclusivos
•	Documento Técnico Maestro v2 - Arquitectura base compartida con el ecosistema
•	Configuración Multi-Tenant - Group Type tenant_services definido conceptualmente
•	Schema.org - Mapeo conceptual a LocalBusiness/ProfessionalService
•	Sinergias documentadas con ComercioConecta para reutilización arquitectónica
1.3 Gap Crítico Identificado
A pesar de contar con ComercioConecta completamente documentado (18 especificaciones técnicas) y un alto potencial de reutilización (~70%), ServiciosConecta tiene 0 documentos específicos. Sus 5 componentes exclusivos (Booking Engine, Buzón de Confianza, Firma Digital, Triaje de Casos, Presupuestador Auto) requieren desarrollo específico que no existe en otros verticales.
1.4 Asimetría Documental vs. Otras Verticales
Vertical	Docs Técnicos	Cobertura
Empleabilidad	17 documentos	Journey completo
Emprendimiento	20 documentos	Journey completo
AgroConecta	17 documentos	Marketplace agrario completo
ComercioConecta	18 documentos	Retail + componentes exclusivos
ServiciosConecta	0 documentos	Solo menciones conceptuales

 
2. Análisis de Componentes Exclusivos
ServiciosConecta tiene 5 componentes exclusivos que no existen en otros verticales y requieren especificaciones técnicas dedicadas:
Componente	Función "Sin Humo"
Booking Engine	Gestión de citas con pago previo para evitar "no-shows". Sincronización bidireccional con Google Calendar y Outlook. Slots de disponibilidad configurables.
Buzón de Confianza	Custodia documental cifrada y área privada segura para intercambio de archivos sensibles (DNI, contratos, informes). Cifrado AES-256 + versionado.
Firma Digital PAdES	Integración con AutoFirma para contratos de servicios firmados digitalmente sin salir de la plataforma. Cumplimiento eIDAS.
Triaje de Casos	Agente de IA que realiza entrevista inicial automatizada para clasificar urgencia, tipo de servicio y derivar al profesional adecuado.
Presupuestador Auto	Asistente de IA que genera presupuestos profesionales basados en horas estimadas, materiales y tarifas configuradas por tipo de servicio.

2.1 Booking Engine - Análisis Detallado
El motor de reservas es el corazón de ServiciosConecta. A diferencia de productos como Calendly o Cal.com, se integra nativamente con el ecosistema:
•	Pago anticipado obligatorio/opcional configurable para reducir no-shows
•	Sincronización bidireccional con Google Calendar API y Microsoft Graph (Outlook)
•	Slots inteligentes que detectan colisiones y tiempos de desplazamiento
•	Videollamada integrada (Jitsi Meet) para consultas telemáticas
•	Recordatorios multicanal (email, SMS, WhatsApp) con confirmación
2.2 Buzón de Confianza - Análisis Detallado
Solución de custodia documental que diferencia a ServiciosConecta de cualquier competidor genérico:
•	Cifrado AES-256-GCM en reposo con claves derivadas por tenant
•	Área de intercambio seguro cliente-profesional con permisos granulares
•	Versionado automático de documentos con historial de cambios
•	Caducidad configurable de enlaces de descarga
•	Cumplimiento RGPD: derecho al olvido, portabilidad, auditoría de accesos
 
3. Análisis de Reutilización Arquitectónica
ServiciosConecta puede reutilizar aproximadamente el 70% de la arquitectura de ComercioConecta, lo que representa una ventaja significativa en tiempo y coste de documentación.
3.1 Mapeo de Componentes Reutilizables
ComercioConecta	ServiciosConecta	Adaptación Requerida
Merchant Portal	Provider Portal	Mínima (~85% reuso)
Customer Portal	Client Portal	Mínima (~90% reuso)
Reviews System	Reviews System	Mínima - añadir métricas de servicio
Local SEO	Professional SEO	Media - Schema.org ProfessionalService
Flash Offers	Disponibilidad Express	Media - lógica de citas express
Notifications System	Notifications System	Mínima (~95% reuso)
Order System	Booking System	Alta - modelo de negocio diferente
N/A	Booking Engine	Nuevo - desarrollo específico
N/A	Buzón de Confianza	Nuevo - desarrollo específico
N/A	Firma Digital PAdES	Nuevo - integración AutoFirma

3.2 Estimación Cuantitativa de Reutilización
Categoría	% Reuso	Esfuerzo Relativo
Componentes compartidos (portales, reviews, notif.)	~85%	Bajo - adaptación de labels y flujos
Componentes adaptados (SEO, disponibilidad)	~60%	Medio - lógica específica de servicios
Componentes exclusivos (Booking, Buzón, Firma)	0%	Alto - desarrollo desde cero
Componentes de IA (Triaje, Presupuestador)	~30%	Medio-Alto - prompts y lógica específica
PROMEDIO PONDERADO	~70%	Optimizado vs. desarrollo nuevo

 
4. Documentos Técnicos Requeridos
Se identifican 15 especificaciones técnicas necesarias para implementar ServiciosConecta completamente, organizadas por sprint de desarrollo.
4.1 Sprint 1: Services Core (Semanas 1-2)
#	Documento	Prioridad	Horas Est.
82	ServiciosConecta_Services_Core_v1	CRÍTICA	12-16h
83	ServiciosConecta_Service_Catalog_v1	CRÍTICA	10-12h
84	ServiciosConecta_Provider_Profile_v1	CRÍTICA	10-12h

4.2 Sprint 2: Booking Engine (Semanas 3-4)
#	Documento	Prioridad	Horas Est.
85	ServiciosConecta_Booking_Engine_Core_v1	CRÍTICA	14-18h
86	ServiciosConecta_Calendar_Sync_v1	ALTA	10-14h
87	ServiciosConecta_Payment_Booking_v1	ALTA	8-10h

4.3 Sprint 3: Confianza Digital (Semanas 5-6)
#	Documento	Prioridad	Horas Est.
88	ServiciosConecta_Buzon_Confianza_v1	CRÍTICA	14-18h
89	ServiciosConecta_Firma_Digital_PAdES_v1	ALTA	12-16h
90	ServiciosConecta_Client_Portal_v1	ALTA	8-10h

4.4 Sprint 4: Inteligencia Artificial (Semanas 7-8)
#	Documento	Prioridad	Horas Est.
91	ServiciosConecta_AI_Triaje_Casos_v1	ALTA	12-14h
92	ServiciosConecta_AI_Presupuestador_v1	ALTA	12-14h
93	ServiciosConecta_Provider_Portal_v1	MEDIA	8-10h

4.5 Sprint 5: SEO, Analytics y Cierre (Semanas 9-10)
#	Documento	Prioridad	Horas Est.
94	ServiciosConecta_Professional_SEO_v1	ALTA	10-12h
95	ServiciosConecta_Reviews_Ratings_v1	MEDIA	6-8h
96	ServiciosConecta_Analytics_Dashboard_v1	MEDIA	8-10h

 
5. Avatar Principal: Elena (Profesional Liberal)
Elena representa al profesional liberal que necesita digitalizar su práctica sin perder el control de su agenda ni la confianza de sus clientes.
5.1 Perfil del Avatar
Atributo	Descripción
Nombre	Elena Martínez García
Profesión	Abogada especializada en derecho civil y familia
Ubicación	Cabra (Córdoba) - 20.000 habitantes
Edad	42 años
Situación	Despacho propio con 1 administrativa a media jornada
Facturación	~60.000€/año, quiere escalar sin contratar más personal
Pain Points	No-shows (15%), gestión manual de documentos, firmas presenciales, tiempo en presupuestos
Meta	Reducir tareas administrativas un 50% y ampliar radio de acción a comarcas cercanas

5.2 Tipos de Profesionales Target
Categoría	Profesiones	Schema.org Type
Legal	Abogados, procuradores, notarios	Attorney, LegalService
Salud	Médicos, fisios, psicólogos, odontólogos	Physician, Dentist, MedicalBusiness
Técnico	Arquitectos, ingenieros, peritos	ProfessionalService
Financiero	Asesores fiscales, gestores, contables	AccountingService, FinancialService
Consultoría	Consultores, coaches, formadores	ProfessionalService, EducationalOrganization
Bienestar	Nutricionistas, entrenadores, terapeutas	HealthAndBeautyBusiness

 
6. Modelo de Datos Principal
ServiciosConecta introduce entidades específicas que extienden el modelo base del ecosistema. Las siguientes son las entidades clave a especificar:
6.1 Entidades Core de Servicios
Entidad	Propósito	Campos Clave
provider_profile	Perfil del profesional	specialties, credentials, hourly_rate, service_area
service_offering	Servicio ofertado	duration, price, modality (presencial/online/hybrid)
booking	Reserva de cita	datetime, client_id, provider_id, status, payment_id
availability_slot	Slot de disponibilidad	day_of_week, start_time, end_time, recurrence
secure_document	Documento en Buzón	file_encrypted, access_list, expiry, version
digital_signature	Firma digital	document_id, signer_id, signature_data, timestamp
quote_request	Solicitud de presupuesto	service_type, description, ai_estimate, final_quote
case_triage	Resultado de triaje IA	urgency, category, suggested_provider, confidence

6.2 Nuevo Group Type: tenant_services
Se requiere definir un nuevo Group Type específico para profesionales de servicios con los siguientes campos adicionales:
•	field_service_categories: Taxonomía de tipos de servicio (Legal, Salud, Técnico...)
•	field_professional_license: Número de colegiación o licencia profesional
•	field_insurance_policy: Póliza de responsabilidad civil (obligatoria para ciertos profesionales)
•	field_calendar_integrations: Configuración de Google Calendar y Outlook
•	field_signature_certificate: Certificado digital para firma electrónica
 
7. Integraciones Externas Requeridas
Integración	API/Protocolo	Propósito
Google Calendar	Google Calendar API v3	Sincronización bidireccional de citas y disponibilidad
Microsoft Outlook	Microsoft Graph API	Sincronización bidireccional de citas (alternativa a Google)
AutoFirma	AutoFirma WebStart / @firma	Firma digital avanzada con certificado (DNIe, FNMT)
Jitsi Meet	Jitsi IFrame API	Videollamadas integradas para consultas online
Stripe Connect	Stripe API (existente)	Pagos anticipados de citas con Destination Charges
Twilio/SMS	Twilio API / SMSC	Recordatorios SMS de citas (reducción no-shows)
WhatsApp Business	WhatsApp Cloud API	Notificaciones y confirmaciones de citas

8. Inversión Estimada en Documentación
Concepto	Documentos	Horas Est.
Especificaciones técnicas completas	15 documentos	130-160 horas
Diagramas y flujos	~30 diagramas	15-20 horas
Revisión y validación	-	10-15 horas
TOTAL ESTIMADO	15 docs + anexos	155-195 horas

8.1 Comparativa de Esfuerzo por Vertical
Vertical	Docs	Horas	% Reutilización
Empleabilidad	17	~200	0% (primero)
Emprendimiento	20	~220	15% (Core)
AgroConecta	17	~180	20% (Core + Emp)
ComercioConecta	18	~200	65% (AgroConecta)
ServiciosConecta	15	~175	70% (Comercio + Core)

 
9. Conclusión y Recomendaciones
ServiciosConecta completa el ecosistema de 5 verticales de la Jaraba Impact Platform. Su alto porcentaje de reutilización (~70%) reduce significativamente el esfuerzo de documentación, pero sus 5 componentes exclusivos requieren especificaciones técnicas detalladas que no existen en ningún otro vertical.
9.1 Acciones Inmediatas
1.	Iniciar con Services Core (doc 82) definiendo el modelo de datos base
2.	Priorizar Booking Engine (docs 85-87) como diferenciador competitivo clave
3.	Desarrollar Buzón de Confianza (doc 88) para profesionales regulados (abogados, médicos)
4.	Especificar integración AutoFirma (doc 89) para cumplimiento eIDAS
9.2 Riesgos de No Documentar
•	Pérdida del posicionamiento diferencial "Plataforma de Confianza Digital"
•	Ecosistema incompleto: 4 de 5 verticales no es un "ecosistema"
•	Deuda técnica por desarrollar sin especificaciones claras
•	Dificultad para justificar inversiones ante fondos Kit Digital y FEDER
•	Imposibilidad de ofrecer solución integral a administraciones públicas
9.3 Diferenciador Estratégico
ServiciosConecta permite a Jaraba ofrecer una propuesta integral única en el mercado: "Desde tu primera idea de negocio hasta la gestión de tu consulta profesional, todo en una plataforma". Este cierre del ecosistema multiplica el valor de cada vertical individual.

--- Fin del Documento ---
