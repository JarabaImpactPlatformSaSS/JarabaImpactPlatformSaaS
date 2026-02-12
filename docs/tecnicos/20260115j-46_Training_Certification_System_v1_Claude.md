
SISTEMA DE FORMACIÓN, MENTORÍA
Y CERTIFICACIÓN JARABA™

Training & Certification System

Escalera de Valor + Especificación Técnica SaaS

JARABA IMPACT PLATFORM

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	46_Training_Certification_System
Dependencias:	08_LMS_Core, 31_Mentoring_Core, Stripe Connect
 
Tabla de Contenidos
Tabla de Contenidos	1
1. Resumen Ejecutivo	1
1.1 Problema Estratégico	1
1.2 Solución: Escalera de Valor SaaS	1
2. Escalera de Valor: Arquitectura Estratégica	1
2.1 Los 6 Peldaños de la Escalera	1
2.2 Distribución del Tiempo del Fundador	1
2.3 Flujo de Conversión por Peldaño	1
3. Arquitectura Técnica del Sistema	1
3.1 Stack Tecnológico	1
3.2 Entidades del Sistema de Formación	1
3.2.1 Entidad: training_product	1
3.2.2 Entidad: certification_program	1
3.2.3 Entidad: user_certification	1
4. APIs REST del Sistema	1
4.1 API de Productos Formativos	1
4.2 API de Certificación	1
4.3 API de Franquicias y Territorios	1
5. Flujos de Automatización (ECA)	1
5.1 ECA-TRAIN-001: Upsell Automático Post-Compra	1
5.2 ECA-TRAIN-002: Propuesta de Certificación	1
5.3 ECA-TRAIN-003: Emisión de Certificación	1
5.4 ECA-TRAIN-004: Tracking de Royalties	1
6. Modelo Económico del Sistema	1
6.1 Tarifas por Tipo de Certificación	1
6.2 Proyección de Ingresos por Escalera	1
7. Roadmap de Implementación	1
7.1 Criterios de Aceptación	1
Anexo A: Configuración Inicial de Productos	1
A.1 Vertical Empleabilidad	1
A.2 Vertical Emprendimiento	1

 
1. Resumen Ejecutivo
Este documento define la arquitectura completa del Sistema de Formación, Mentoría y Certificación del Ecosistema Jaraba. El sistema transforma el conocimiento y metodología propietaria en activos digitales escalables que generan ingresos recurrentes a través de los tres motores económicos del modelo de negocio.
1.1 Problema Estratégico
El modelo tradicional de formación y consultoría presenta limitaciones estructurales que impiden la escalabilidad:
•	Venta de horas: Margen alto pero techo de crecimiento limitado por tiempo disponible
•	Dependencia personal: El negocio muere cuando el fundador no puede trabajar
•	Impacto limitado: Solo se puede ayudar a quienes pueden pagar consultoría premium
•	Ingresos impredecibles: Ciclos de venta variables sin MRR estable
1.2 Solución: Escalera de Valor SaaS
El sistema implementa una Escalera de Valor que convierte el conocimiento en productos digitales con tres características clave:
1.	Escalabilidad: Productos que se venden sin intervención directa
2.	Recurrencia: Modelos de suscripción que generan MRR predecible
3.	Multiplicación: Red de certificados que extienden el alcance
 
2. Escalera de Valor: Arquitectura Estratégica
La Escalera de Valor estructura todos los productos formativos en niveles progresivos, desde contenido gratuito hasta certificación premium, optimizando tanto la experiencia del usuario como la rentabilidad del ecosistema.
2.1 Los 6 Peldaños de la Escalera
Nivel	Producto	Precio	Modelo	Objetivo
0	Lead Magnets	€0 (Gratis)	Captura	Email + Cualificación
1	Microcursos	€29 - €97	Pago único	Primera conversión
2	Club Jaraba	€19 - €99/mes	Suscripción	MRR + Comunidad
3	Mentoría Grupal	€297 - €497	Cohorte	Escalabilidad semi
4	Mentoría 1:1	€997 - €1.997	Premium	Alto margen
5	Certificación	€2.000 - €5.000	Licencia	Multiplicación

2.2 Distribución del Tiempo del Fundador
Para maximizar el impacto y la rentabilidad, el tiempo personal debe redistribuirse estratégicamente:
% Tiempo	Actividad	Justificación
5%	Mentoría 1:1 Premium (€300+/hora)	Solo clientes élite, alta rentabilidad
15%	Formación de Formadores	Multiplicar el método via certificados
20%	Mentoría Grupal y Masterclasses	Impacto escalable con presencia
60%	Creación de Activos (cursos, IP, metodología)	Inversión en productos escalables
 
2.3 Flujo de Conversión por Peldaño
Cada peldaño de la escalera tiene tasas de conversión objetivo y triggers automáticos:
Transición	Conversión	Trigger	Automatización ECA
Lead → Microcurso	8-15%	Email secuencia	7 días post-descarga, oferta €29 con descuento
Microcurso → Club	20-30%	Completar curso	Oferta Club con primer mes €1 al finalizar
Club → Grupal	10-15%	3 meses activo	Invitación a cohorte con plaza reservada
Grupal → 1:1	5-10%	Solicitud o detección	Flag manual + email personalizado
1:1 → Certificación	15-25%	Perfil consultor	Propuesta automática si rol=consultant
 
3. Arquitectura Técnica del Sistema
El sistema se implementa como extensión del core SaaS de Jaraba Impact Platform, integrando LMS, Marketplace de Mentorías, y Sistema de Certificación en una arquitectura cohesiva.
3.1 Stack Tecnológico
Componente	Tecnología
Core CMS	Drupal 11 con módulo jaraba_training custom
LMS	jaraba_lms + H5P para contenido interactivo
Mentorías	jaraba_mentoring + FullCalendar.js + Jitsi Meet
Certificación	Custom entity certification_program + Open Badges 3.0
Pagos	Stripe Connect (Destination Charges) + Commerce Recurring
Video	Bunny.net Stream / Vimeo OTT para contenido premium
Automatización	ECA Module + ActiveCampaign webhooks
AI Copilot	Gemini API con Qdrant para RAG contextual
3.2 Entidades del Sistema de Formación
3.2.1 Entidad: training_product
Representa cualquier producto formativo vendible en la escalera de valor.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID autoincremental	PRIMARY KEY
uuid	UUID	Identificador único global	UNIQUE, NOT NULL
title	VARCHAR(255)	Nombre del producto	NOT NULL
machine_name	VARCHAR(64)	Slug URL-friendly	UNIQUE, INDEX
product_type	VARCHAR(32)	Tipo de producto	ENUM: lead_magnet, microcourse, membership, group_mentoring, individual_mentoring, certification
ladder_level	INT	Peldaño en la escalera	RANGE 0-5
price	DECIMAL(10,2)	Precio base	>= 0, DEFAULT 0
billing_type	VARCHAR(16)	Tipo de facturación	ENUM: free, one_time, recurring, cohort
billing_interval	VARCHAR(16)	Intervalo si recurring	ENUM: month, year, NULL
vertical_id	INT	Vertical asociada	FK taxonomy_term.tid
course_ids	JSON	Cursos LMS incluidos	Array of course.id
next_product_id	INT	Siguiente en escalera	FK training_product.id, NULL
upsell_trigger_days	INT	Días para trigger upsell	DEFAULT 7
conversion_target	DECIMAL(5,2)	% conversión objetivo	RANGE 0-100
stripe_product_id	VARCHAR(64)	ID producto en Stripe	NULLABLE
is_published	BOOLEAN	Publicado y visible	DEFAULT FALSE
created	DATETIME	Fecha creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
 
3.2.2 Entidad: certification_program
Define los programas de certificación del Método Jaraba™ para consultores y entidades.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID autoincremental	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
title	VARCHAR(255)	Nombre del programa	NOT NULL
certification_type	VARCHAR(32)	Tipo de certificación	ENUM: consultant, entity, regional_franchise
entry_fee	DECIMAL(10,2)	Fee de entrada	>= 0
annual_fee	DECIMAL(10,2)	Cuota anual renovación	>= 0
royalty_percent	DECIMAL(5,2)	% royalty sobre ventas	RANGE 0-50
required_courses	JSON	Cursos obligatorios	Array of course.id
exam_required	BOOLEAN	Requiere examen final	DEFAULT TRUE
minimum_score	INT	Score mínimo examen	RANGE 0-100, DEFAULT 70
validity_months	INT	Meses validez certificación	DEFAULT 12
toolkit_access	JSON	Recursos del toolkit	Array of resource_ids
territory_exclusive	BOOLEAN	Permite exclusividad territorial	DEFAULT FALSE
platform_fee_percent	DECIMAL(5,2)	% fee en marketplace mentorías	RANGE 10-20
3.2.3 Entidad: user_certification
Registro de certificaciones otorgadas a usuarios del ecosistema.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID autoincremental	PRIMARY KEY
uuid	UUID	ID único (Open Badge)	UNIQUE, NOT NULL
user_id	INT	Usuario certificado	FK users.uid, NOT NULL
program_id	INT	Programa de certificación	FK certification_program.id
status	VARCHAR(16)	Estado de la certificación	ENUM: enrolled, in_progress, exam_pending, certified, expired, revoked
exam_score	INT	Puntuación examen	RANGE 0-100, NULL
exam_attempts	INT	Intentos de examen	DEFAULT 0
certified_at	DATETIME	Fecha certificación	NULL hasta aprobar
expires_at	DATETIME	Fecha expiración	certified_at + validity_months
territory_code	VARCHAR(32)	Código territorio exclusivo	NULL si no aplica
stripe_subscription_id	VARCHAR(64)	ID suscripción anual	Para cuota recurrente
lifetime_revenue	DECIMAL(12,2)	Ingresos generados	Para tracking royalties
badge_json	JSON	Open Badge 3.0 assertion	Formato estándar OB3
 
4. APIs REST del Sistema
Todos los endpoints requieren autenticación OAuth2 y respetan el contexto multi-tenant del ecosistema.
4.1 API de Productos Formativos
Método	Endpoint	Descripción
GET	/api/v1/training/products	Listar productos (filtros: type, level, vertical)
GET	/api/v1/training/products/{id}	Detalle de producto con cursos y precios
GET	/api/v1/training/ladder	Obtener escalera completa con conversiones
GET	/api/v1/training/ladder/recommend	Siguiente producto recomendado para usuario
POST	/api/v1/training/products/{id}/purchase	Iniciar compra/checkout del producto
4.2 API de Certificación
Método	Endpoint	Descripción
GET	/api/v1/certification/programs	Listar programas de certificación disponibles
GET	/api/v1/certification/programs/{id}	Detalle con requisitos, fees y beneficios
POST	/api/v1/certification/programs/{id}/enroll	Inscribirse en programa de certificación
GET	/api/v1/certification/my-certifications	Mis certificaciones con estado y progreso
POST	/api/v1/certification/{id}/exam/start	Iniciar examen de certificación
POST	/api/v1/certification/{id}/exam/submit	Enviar respuestas del examen
GET	/api/v1/certification/verify/{uuid}	Verificación pública de certificación (Open Badge)
GET	/api/v1/certification/directory	Directorio público de consultores certificados
4.3 API de Franquicias y Territorios
Método	Endpoint	Descripción
GET	/api/v1/franchise/territories	Mapa de territorios (disponibles/ocupados)
GET	/api/v1/franchise/territories/{code}	Detalle de territorio con franquiciado
POST	/api/v1/franchise/territories/{code}/reserve	Reservar territorio (solo certificados)
GET	/api/v1/franchise/my-territory	Mi territorio y métricas de franquicia
GET	/api/v1/franchise/royalties	Mis royalties generados y pendientes
 
5. Flujos de Automatización (ECA)
Los flujos ECA automatizan todo el journey del usuario a través de la Escalera de Valor.
5.1 ECA-TRAIN-001: Upsell Automático Post-Compra
Trigger: Usuario completa compra de producto formativo
4.	Obtener ladder_level y next_product_id del producto comprado
5.	Si next_product_id != NULL: programar email de upsell para upsell_trigger_days
6.	Crear tag en ActiveCampaign: 'ladder_level_{n}'
7.	Si billing_type = 'recurring': crear suscripción en Stripe
8.	Actualizar user_ladder_position con nuevo nivel alcanzado
5.2 ECA-TRAIN-002: Propuesta de Certificación
Trigger: Usuario con rol 'consultant' completa mentoría grupal
9.	Verificar que user.role incluye 'consultant'
10.	Verificar que no existe user_certification activa
11.	Obtener programa de certificación correspondiente a su vertical
12.	Enviar email personalizado con propuesta y beneficios
13.	Crear task en CRM para seguimiento manual a 7 días
5.3 ECA-TRAIN-003: Emisión de Certificación
Trigger: user_certification.exam_score >= program.minimum_score
14.	Actualizar user_certification.status = 'certified'
15.	Calcular expires_at = NOW() + program.validity_months
16.	Generar Open Badge 3.0 JSON assertion
17.	Asignar rol 'certified_consultant' al usuario
18.	Habilitar acceso a toolkit del programa
19.	Crear suscripción anual para cuota de renovación
20.	Publicar perfil en directorio de consultores certificados
21.	Enviar email de bienvenida con credenciales y próximos pasos
5.4 ECA-TRAIN-004: Tracking de Royalties
Trigger: Pago completado en marketplace de mentorías
22.	Obtener mentor_id del pago y verificar si es certified_consultant
23.	Si certificado: calcular royalty = amount × (royalty_percent / 100)
24.	Crear registro en royalty_transaction con estado 'pending'
25.	Actualizar user_certification.lifetime_revenue
26.	Programar payout mensual agrupado de royalties
 
6. Modelo Económico del Sistema
6.1 Tarifas por Tipo de Certificación
Tipo Franquicia	Activación	Cuota Anual	Royalties	Fee Marketplace
Consultor Individual	€690	€190/año	10%	15%
ONG / Asociación	€990	€290/año	0%	12%
Ayuntamiento / Entidad	€1.500	€490/año	5%	12%
Franquicia Regional	€5.000	€990/año	8%	10%
6.2 Proyección de Ingresos por Escalera
Escenario conservador con 1.000 leads/mes y tasas de conversión objetivo:
Peldaño	Entrada	Conversión	Usuarios	Precio	Revenue
Lead Magnet	1.000	100%	1.000	€0	€0
Microcurso	1.000	12%	120	€49	€5.880
Club Jaraba	120	25%	30	€39/m	€1.170/m
Grupal	30	12%	4	€397	€1.588
1:1 Premium	4	8%	1	€1.497	€1.497
Certificación	4	20%	1	€2.500	€2.500

Total Mensual Puntual	€11.465
MRR (Club Jaraba)	€1.170
ARR Proyectado	€151.620
 
7. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Sem 1-2	Entidad training_product, APIs básicas, integración Stripe	08_LMS_Core, Commerce
Sprint 2	Sem 3-4	Entidad certification_program, flujo de inscripción	Sprint 1, 04_Permisos_RBAC
Sprint 3	Sem 5-6	Sistema de exámenes, emisión Open Badge 3.0	Sprint 2, H5P
Sprint 4	Sem 7-8	Flujos ECA completos, tracking de royalties	Sprint 3, 06_Core_Flujos_ECA
Sprint 5	Sem 9-10	Dashboard de certificados, directorio público	Sprint 4, Frontend
Sprint 6	Sem 11-12	Sistema de territorios, mapa de franquicias	Sprint 5, GIS/Maps
7.1 Criterios de Aceptación
•	100% de endpoints documentados en OpenAPI 3.0
•	Cobertura de tests > 80% en módulos críticos
•	Flujo completo Lead → Certificación funcional en staging
•	Integración Stripe Connect validada con pagos reales
•	Open Badges verificables en plataformas externas
•	WCAG 2.1 AA compliance en todas las interfaces
 
Anexo A: Configuración Inicial de Productos
Productos pre-configurados para la Escalera de Valor de las verticales principales:
A.1 Vertical Empleabilidad
Producto	Nivel	Precio	Modelo	machine_name
Guía LinkedIn Optimización	0	€0	Lead	lead_linkedin_guide
CV que Convierte (microcurso)	1	€49	One-time	micro_cv_converts
Club Impulso Empleo	2	€29/m	Recurring	club_impulso_empleo
Bootcamp Búsqueda Activa (8 sem)	3	€397	Cohort	bootcamp_busqueda_activa
Mentoría Recolocación Premium	4	€1.497	Premium	mentoria_recolocacion
Certificación Orientador Jaraba™	5	€2.500	License	cert_orientador_jaraba
A.2 Vertical Emprendimiento
Producto	Nivel	Precio	Modelo	machine_name
Kit Validación de Idea	0	€0	Lead	lead_validacion_idea
Modelo de Negocio Canvas	1	€67	One-time	micro_canvas_negocio
Club Emprendedores Jaraba	2	€49/m	Recurring	club_emprendedores
Aceleradora Digital (12 sem)	3	€497	Cohort	aceleradora_digital
Mentoría Lanzamiento 1:1	4	€1.997	Premium	mentoria_lanzamiento
Certificación Mentor Jaraba™	5	€3.500	License	cert_mentor_jaraba

— Fin del Documento —
Ecosistema Jaraba | Sistema de Formación y Certificación | v1.0
