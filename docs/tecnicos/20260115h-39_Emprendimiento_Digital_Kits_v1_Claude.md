SISTEMA DE KITS DIGITALES
Digital Kits
Vertical de Emprendimiento Digital
JARABA IMPACT PLATFORM

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	39_Emprendimiento_Digital_Kits
Dependencias:	28_Digitalization_Paths, 29_Action_Plans
 
1. Resumen Ejecutivo
Los Kits Digitales son paquetes de recursos listos para usar que aceleran la ejecución de tareas del itinerario de digitalización. Incluyen plantillas, checklists, guías paso a paso, herramientas y ejemplos personalizados por sector.
1.1 Tipos de Kits
Tipo	Contenido	Ejemplo
Kit de Inicio	Lo esencial para empezar online	Logo maker, plantilla web, checklist legal
Kit Presencia Digital	Establecer presencia profesional	Templates redes, guía SEO local, GMB setup
Kit E-commerce	Montar tienda online	Catálogo template, política envíos, pasarela pago
Kit Marketing	Captar primeros clientes	Plantillas ads, calendario contenido, email templates
Kit Operaciones	Digitalizar gestión	CRM config, facturación, inventario básico
Kit Sector	Recursos específicos del sector	Carta digital (hostelería), catálogo (retail)
1.2 Modelo de Negocio
•	Kits básicos: Incluidos en programa/membresía
•	Kits premium: Compra individual €29-99
•	Kits sector: Incluidos en membresía Elite
•	Desbloqueo progresivo: Se desbloquean al avanzar en itinerario
 
2. Arquitectura de Datos
2.1 Entidad: digital_kit
Campo	Tipo	Descripción
id	Serial	PRIMARY KEY
title	VARCHAR(255)	Nombre del kit
slug	VARCHAR(128)	URL amigable
description	TEXT	Descripción completa
short_description	VARCHAR(500)	Resumen para listados
kit_type	VARCHAR(24)	ENUM: starter|presence|ecommerce|marketing|operations|sector
target_sectors	JSON	Sectores aplicables
target_phases	JSON	Fases de negocio objetivo
difficulty	VARCHAR(16)	ENUM: beginner|intermediate|advanced
estimated_time	INT	Minutos estimados de implementación
thumbnail_id	INT	FK file_managed.fid
preview_video_url	VARCHAR(500)	URL video preview
access_type	VARCHAR(16)	ENUM: free|program|purchase|membership
price	DECIMAL(8,2)	Precio si purchase (€)
membership_levels	JSON	Niveles de membresía que incluyen el kit
unlock_conditions	JSON	Condiciones de desbloqueo automático
resources	JSON	Lista de recursos incluidos
related_tasks	JSON	IDs de action_tasks relacionadas
total_downloads	INT	Contador de descargas
avg_rating	DECIMAL(3,2)	Rating promedio (1-5)
total_reviews	INT	Número de reviews
is_featured	BOOLEAN	Destacado en listados
status	VARCHAR(16)	ENUM: draft|published|archived
created	DATETIME	Timestamp
 
2.2 Entidad: kit_resource
Campo	Tipo	Descripción
id	Serial	PRIMARY KEY
kit_id	INT	FK digital_kit.id
title	VARCHAR(255)	Nombre del recurso
description	TEXT	Descripción del recurso
resource_type	VARCHAR(24)	ENUM: template|checklist|guide|tool|video|example|config
file_format	VARCHAR(16)	docx|xlsx|pdf|figma|canva|video|link
file_id	INT	FK file_managed.fid (si archivo)
external_url	VARCHAR(500)	URL externa (si Canva, Figma, etc)
is_editable	BOOLEAN	TRUE si el usuario puede editar
sector_variants	JSON	Variantes por sector [{sector, file_id}]
order_weight	INT	Orden dentro del kit
download_count	INT	Descargas de este recurso
2.3 Entidad: user_kit_access
Campo	Tipo	Descripción
id	Serial	PRIMARY KEY
user_id	INT	FK users.uid
kit_id	INT	FK digital_kit.id
access_type	VARCHAR(16)	ENUM: program|purchase|membership|unlocked
granted_at	DATETIME	Fecha de acceso concedido
expires_at	DATETIME	Expiración (NULL si permanente)
order_id	INT	FK commerce_order (si compra)
progress_percent	INT	% de recursos descargados/vistos
completed_at	DATETIME	Fecha de completitud
rating	INT	Rating del usuario (1-5)
review	TEXT	Review del usuario
 
3. Catálogo de Kits
3.1 Kit de Inicio Digital
Recurso	Formato	Descripción
Logo Maker Guide	PDF + Canva	Guía para crear logo con Canva/herramientas gratuitas
Plantilla Web Básica	Template	Landing page ready-to-use en WordPress/Wix
Checklist Legal	PDF	Lista de requisitos legales (LOPD, cookies, aviso legal)
Textos Legales	DOCX	Plantillas de política privacidad, términos, cookies
Dominio & Hosting Guide	PDF	Guía para contratar dominio y hosting
Email Profesional Setup	Video	Tutorial configurar email con dominio propio
3.2 Kit Presencia Digital
Recurso	Formato	Descripción
Google My Business Setup	Video + Checklist	Tutorial completo GMB con checklist
Templates Redes Sociales	Canva	50 plantillas Instagram/Facebook editables
Calendario de Contenido	XLSX	Plantilla 3 meses con ideas por sector
Guía SEO Local	PDF	Optimización para búsquedas locales
Bio Generator	Tool	Generador de bios profesionales con IA
Banco de Hashtags	XLSX	Hashtags organizados por sector/tema
3.3 Kit E-commerce
Recurso	Formato	Descripción
Plantilla Catálogo	XLSX	Estructura de productos con campos SEO
Fotografía de Producto	PDF + Video	Guía DIY para fotos profesionales
Políticas de Tienda	DOCX	Envíos, devoluciones, garantías
Configuración Pasarela	Video	Tutorial Stripe/PayPal/Redsys
Email de Compra	HTML	Templates transaccionales
Checklist Lanzamiento	PDF	50 puntos antes de publicar tienda
 
4. Sistema de Desbloqueo Progresivo
Los kits se desbloquean automáticamente según el avance en el itinerario:
Kit	Condición de Desbloqueo	Alternativa
Kit de Inicio	Diagnóstico completado	Compra €29
Kit Presencia Digital	Fase 1 completada (25%)	Membresía Básica
Kit E-commerce	Fase 2 al 50%	Compra €49
Kit Marketing	Fase 2 completada	Compra €59
Kit Operaciones	Fase 3 iniciada	Membresía Elite
Kit Sector	Membresía Elite activa	Compra €99
4.1 ECA-KIT-001: Auto-Desbloqueo
1.	Trigger: progress_milestone.status = 'completed'
2.	Evaluar unlock_conditions de todos los kits
3.	Si condición cumplida AND no tiene acceso → crear user_kit_access
4.	Notificar usuario: '¡Has desbloqueado el Kit X!'
5.	Otorgar créditos de impacto (+50)
 
5. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/kits	Lista de kits con filtros
GET	/api/v1/kits/{id}	Detalle de kit
GET	/api/v1/kits/{id}/resources	Recursos del kit
GET	/api/v1/kits/{id}/resources/{rid}/download	Descargar recurso
GET	/api/v1/kits/my-kits	Kits del usuario (con acceso)
GET	/api/v1/kits/available	Kits disponibles para desbloquear
POST	/api/v1/kits/{id}/purchase	Comprar kit
POST	/api/v1/kits/{id}/review	Dejar review
GET	/api/v1/kits/recommended	Kits recomendados según perfil
6. Roadmap de Implementación
Sprint	Timeline	Entregables
Sprint 1	Semana 1-2	Entidades digital_kit, kit_resource, user_kit_access.
Sprint 2	Semana 3-4	CRUD kits. Sistema de descarga. Variantes por sector.
Sprint 3	Semana 5-6	Desbloqueo progresivo. Integración itinerarios.
Sprint 4	Semana 7-8	Compra individual (Stripe). Reviews. QA.
--- Fin del Documento ---
