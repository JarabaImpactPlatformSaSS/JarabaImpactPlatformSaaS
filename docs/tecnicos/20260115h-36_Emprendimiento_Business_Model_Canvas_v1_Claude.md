CANVAS DE MODELO DE NEGOCIO DIGITAL
Business Model Canvas
Vertical de Emprendimiento Digital
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	36_Emprendimiento_Business_Model_Canvas
Dependencias:	25_Business_Diagnostic, 28_Digitalization_Paths, 29_Action_Plans
 
1. Resumen Ejecutivo
Este documento especifica la arquitectura técnica del Sistema de Canvas de Modelo de Negocio para la vertical de Emprendimiento. El sistema implementa una versión digital e interactiva del Business Model Canvas de Osterwalder, adaptada al contexto de digitalización y enriquecida con IA para validación y sugerencias.
1.1 Objetivos del Sistema
•	Canvas interactivo: Los 9 bloques del Business Model Canvas editables en tiempo real
•	Versionado histórico: Registro de evolución del modelo de negocio a lo largo del tiempo
•	Validación con IA: Análisis automático de coherencia, gaps y oportunidades
•	Templates por sector: Canvas pre-poblados según tipo de negocio y sector
•	Exportación profesional: PDF/imagen para presentaciones a inversores o mentores
•	Colaboración: Edición compartida con co-fundadores o asesores
1.2 Stack Tecnológico
Componente	Tecnología
Core CMS	Drupal 11 con módulo jaraba_canvas custom
Almacenamiento	Custom entities con JSON fields para bloques
Frontend	React/Vue con drag-and-drop para post-its virtuales
Versionado	Content moderation + revision system de Drupal
IA/Validación	Integración con LLM (Claude/GPT) vía RAG
Exportación	Puppeteer para PDF, Canvas API para imagen
Colaboración	WebSockets para edición en tiempo real
 
2. Los 9 Bloques del Business Model Canvas
El sistema implementa los 9 bloques estándar del Business Model Canvas, cada uno con campos estructurados y guías contextuales para emprendedores novatos.
#	Bloque	machine_name	Pregunta Clave	Ejemplos
1	Segmentos de Clientes	customer_segments	¿Para quién creamos valor?	Particulares, empresas, nichos específicos
2	Propuesta de Valor	value_propositions	¿Qué problema resolvemos?	Novedad, rendimiento, personalización, precio
3	Canales	channels	¿Cómo llegamos a los clientes?	Web, redes sociales, tienda física, distribuidores
4	Relación con Clientes	customer_relationships	¿Qué tipo de relación esperan?	Autoservicio, comunidad, asistencia personal
5	Fuentes de Ingresos	revenue_streams	¿Por qué pagan los clientes?	Venta, suscripción, licencia, publicidad
6	Recursos Clave	key_resources	¿Qué necesitamos para operar?	Físicos, intelectuales, humanos, financieros
7	Actividades Clave	key_activities	¿Qué hacemos principalmente?	Producción, resolución de problemas, plataforma
8	Socios Clave	key_partners	¿Quiénes son nuestros aliados?	Proveedores, alianzas, joint ventures
9	Estructura de Costes	cost_structure	¿Cuáles son los principales costes?	Fijos, variables, economías de escala
 
3. Arquitectura de Entidades
El sistema introduce 3 entidades Drupal personalizadas para gestionar el Canvas y sus elementos.
3.1 Entidad: business_model_canvas
Representa un Canvas de Modelo de Negocio completo. Almacena metadatos y referencias a los bloques.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno autoincremental	PRIMARY KEY
uuid	UUID	Identificador único global	UNIQUE, NOT NULL, INDEX
user_id	INT	Emprendedor propietario	FK users.uid, NOT NULL
tenant_id	INT	Tenant/programa asociado	FK tenant.id, NULLABLE
business_diagnostic_id	INT	Diagnóstico origen	FK business_diagnostic.id, NULLABLE
title	VARCHAR(255)	Nombre del proyecto/negocio	NOT NULL
description	TEXT	Descripción breve del negocio	NULLABLE
sector	VARCHAR(32)	Sector del negocio	ENUM: comercio|servicios|agro|hosteleria|tech|otros
business_stage	VARCHAR(24)	Fase del negocio	ENUM: idea|validacion|crecimiento|escalado
version	INT	Número de versión actual	NOT NULL, DEFAULT 1
is_template	BOOLEAN	Es plantilla reutilizable	DEFAULT FALSE
template_source_id	INT	Template del que se derivó	FK business_model_canvas.id, NULLABLE
completeness_score	DECIMAL(5,2)	% de completitud del canvas	COMPUTED, 0-100
coherence_score	DECIMAL(5,2)	Puntuación de coherencia IA	NULLABLE, 0-100
last_ai_analysis	DATETIME	Última validación con IA	NULLABLE
shared_with	JSON	UIDs con acceso de colaboración	NULLABLE
status	VARCHAR(16)	Estado del canvas	ENUM: draft|active|archived
created	DATETIME	Fecha de creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
3.2 Entidad: canvas_block
Representa cada uno de los 9 bloques del Canvas con sus elementos (post-its virtuales).
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
canvas_id	INT	Canvas padre	FK business_model_canvas.id, NOT NULL
block_type	VARCHAR(32)	Tipo de bloque	ENUM: customer_segments|value_propositions|channels|...
items	JSON	Array de elementos del bloque	NOT NULL, DEFAULT []
notes	TEXT	Notas adicionales del bloque	NULLABLE
ai_suggestions	JSON	Sugerencias generadas por IA	NULLABLE
is_validated	BOOLEAN	Bloque validado por mentor	DEFAULT FALSE
changed	DATETIME	Última modificación	NOT NULL
3.2.1 Estructura JSON de items
Cada item del bloque sigue esta estructura:
{ "id": "uuid", "text": "Contenido del post-it", "color": "#FFE082", "priority": 1, "validated": false, "created_at": "ISO8601", "position": { "x": 0, "y": 0 } }
 
3.3 Entidad: canvas_version
Almacena snapshots históricos del Canvas para versionado y comparación.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
canvas_id	INT	Canvas padre	FK business_model_canvas.id, NOT NULL
version_number	INT	Número de versión	NOT NULL, > 0
snapshot	JSON	Copia completa de todos los bloques	NOT NULL
change_summary	VARCHAR(500)	Resumen de cambios	NULLABLE
created_by	INT	Usuario que creó versión	FK users.uid
created	DATETIME	Fecha del snapshot	NOT NULL
 
4. Motor de Validación con Inteligencia Artificial
El sistema integra un módulo de IA que analiza el Canvas para detectar incoherencias, gaps y oportunidades de mejora.
4.1 Análisis de Coherencia
La IA evalúa la coherencia entre bloques relacionados:
Relación	Validación	Ejemplo de Incoherencia
Segmentos ↔ Propuesta	La propuesta resuelve problemas del segmento	Propuesta de lujo para segmento sensible al precio
Canales ↔ Segmentos	Los canales alcanzan a los segmentos definidos	Canal solo digital para segmento sin acceso a internet
Ingresos ↔ Propuesta	El modelo de ingresos es coherente con el valor	Suscripción para producto de compra única
Recursos ↔ Actividades	Los recursos soportan las actividades clave	Plataforma tech sin recursos tecnológicos
Socios ↔ Recursos	Los socios aportan recursos faltantes	Gaps de recursos sin socios que los cubran
Costes ↔ Actividades	Los costes reflejan las actividades	Actividades de producción sin costes de materiales
4.2 Detección de Gaps
La IA identifica bloques vacíos o incompletos críticos:
•	Críticos: Propuesta de valor, Segmentos de clientes, Fuentes de ingresos
•	Importantes: Canales, Relaciones, Recursos clave
•	Secundarios: Actividades, Socios, Estructura de costes
4.3 Sugerencias Generativas
Para cada bloque, la IA puede generar sugerencias basadas en:
1.	Sector del negocio: Patrones comunes en el sector (comercio, servicios, agro...)
2.	Fase del negocio: Recomendaciones según si es idea, validación o crecimiento
3.	Diagnóstico previo: Información del business_diagnostic vinculado
4.	Otros bloques: Coherencia con lo ya definido en otros bloques
4.4 Prompt de Análisis
Ejemplo de prompt para el análisis de coherencia:
Analiza el siguiente Business Model Canvas para un negocio de {sector} en fase {stage}. Evalúa: 1) Coherencia entre bloques (escala 1-10), 2) Gaps críticos identificados, 3) Tres sugerencias de mejora prioritarias. Canvas: {json_canvas}
 
5. Templates de Canvas por Sector
El sistema incluye plantillas pre-pobladas que sirven como punto de partida según el sector.
5.1 Template: Comercio Local
Bloque	Contenido Sugerido
Segmentos	Residentes del barrio, Turistas, Compradores online locales
Propuesta	Productos locales de calidad, Atención personalizada, Entrega rápida
Canales	Tienda física, WhatsApp Business, Instagram, Marketplaces locales
Relación	Atención personal en tienda, Comunidad de clientes fieles
Ingresos	Venta directa, Envíos a domicilio, Tarjetas regalo
Recursos	Local comercial, Stock de productos, Presencia digital
Actividades	Compras a proveedores, Atención al cliente, Marketing local
Socios	Proveedores locales, Empresas de reparto, Asociación de comerciantes
Costes	Alquiler, Stock, Personal, Marketing digital
5.2 Template: Servicios Profesionales
Bloque	Contenido Sugerido
Segmentos	Pymes locales, Autónomos, Particulares con necesidad específica
Propuesta	Expertise especializado, Cercanía y confianza, Flexibilidad horaria
Canales	Web profesional, LinkedIn, Recomendación boca-oreja, Networking
Relación	Asistencia personal dedicada, Seguimiento post-servicio
Ingresos	Tarifa por hora/proyecto, Packs de servicios, Retainer mensual
Recursos	Conocimiento especializado, Herramientas del oficio, Red de contactos
Actividades	Prestación del servicio, Formación continua, Prospección
Socios	Otros profesionales complementarios, Plataformas de contratación
Costes	Formación, Herramientas/software, Marketing, Seguros profesionales
5.3 Template: Productor Agroalimentario
Bloque	Contenido Sugerido
Segmentos	Consumidores conscientes, Restaurantes km0, Tiendas gourmet
Propuesta	Producto artesanal de calidad, Trazabilidad total, Historia del productor
Canales	Venta directa en finca, Mercados locales, Plataformas de venta directa
Relación	Conexión emocional con origen, Comunidad de consumidores, Visitas
Ingresos	Venta directa, Cajas de suscripción, Eventos/experiencias
Recursos	Tierra y cultivos, Know-how tradicional, Certificaciones
Actividades	Producción, Transformación artesanal, Comunicación de marca
Socios	Cooperativas, Denominaciones de origen, Plataformas de distribución
Costes	Insumos agrícolas, Mano de obra, Certificaciones, Logística
 
6. Flujos de Automatización (ECA)
6.1 ECA-BMC-001: Inicialización desde Diagnóstico
Trigger: Usuario completa business_diagnostic con sector identificado
5.	Obtener sector y business_stage del diagnóstico
6.	Buscar template de canvas correspondiente al sector
7.	Crear business_model_canvas con datos básicos del negocio
8.	Copiar bloques del template como punto de partida
9.	Pre-poblar Propuesta de Valor desde pain points del diagnóstico
10.	Notificar al usuario con invitación a completar su Canvas
6.2 ECA-BMC-002: Trigger de Análisis IA
Trigger: Canvas alcanza completeness_score >= 60% O usuario solicita análisis
11.	Serializar canvas completo a JSON
12.	Enviar a endpoint de IA con prompt de análisis
13.	Parsear respuesta: coherence_score, gaps, suggestions
14.	Actualizar canvas.coherence_score y last_ai_analysis
15.	Guardar ai_suggestions en cada bloque relevante
16.	Mostrar notificación con resumen del análisis
6.3 ECA-BMC-003: Versionado Automático
Trigger: Cambios significativos detectados (>= 3 items modificados) O intervalo de 24h
17.	Crear snapshot JSON de todos los bloques actuales
18.	Incrementar version_number del canvas
19.	Crear registro canvas_version con snapshot
20.	Generar change_summary automático comparando con versión anterior
 
7. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/canvas	Listar canvas del usuario autenticado
GET	/api/v1/canvas/{id}	Detalle de canvas con todos los bloques
POST	/api/v1/canvas	Crear nuevo canvas (opcionalmente desde template)
PATCH	/api/v1/canvas/{id}	Actualizar metadatos del canvas
DELETE	/api/v1/canvas/{id}	Eliminar canvas (soft delete)
GET	/api/v1/canvas/{id}/blocks	Listar todos los bloques
PATCH	/api/v1/canvas/{id}/blocks/{type}	Actualizar items de un bloque
POST	/api/v1/canvas/{id}/blocks/{type}/items	Añadir item a un bloque
DELETE	/api/v1/canvas/{id}/blocks/{type}/items/{item_id}	Eliminar item de bloque
POST	/api/v1/canvas/{id}/analyze	Solicitar análisis de IA
GET	/api/v1/canvas/{id}/suggestions	Obtener sugerencias de IA por bloque
GET	/api/v1/canvas/{id}/versions	Listar historial de versiones
GET	/api/v1/canvas/{id}/versions/{version}	Obtener snapshot de versión específica
POST	/api/v1/canvas/{id}/versions/{version}/restore	Restaurar versión anterior
GET	/api/v1/canvas/{id}/export/pdf	Exportar canvas como PDF
GET	/api/v1/canvas/{id}/export/image	Exportar canvas como imagen PNG
POST	/api/v1/canvas/{id}/share	Compartir canvas con colaboradores
GET	/api/v1/canvas/templates	Listar templates disponibles por sector
 
8. Diseño de Interfaz de Usuario
8.1 Layout del Canvas
La interfaz replica el layout estándar del Business Model Canvas:
┌─────────────────────────────────────────────────────────────────────────────┐ │  Socios Clave  │ Activ. Clave │  Propuesta   │  Relaciones  │  Segmentos  │ │     (8)        │     (7)      │  de Valor    │  Clientes    │  Clientes   │ │                │──────────────│     (2)      │     (4)      │     (1)     │ │                │ Recursos     │              │──────────────│             │ │                │ Clave (6)    │              │ Canales (3)  │             │ ├────────────────┴──────────────┴──────────────┴──────────────┴─────────────┤ │     Estructura de Costes (9)          │     Fuentes de Ingresos (5)       │ └─────────────────────────────────────────────────────────────────────────────┘
8.2 Interacción con Post-its
•	Click: Editar contenido del post-it inline
•	Drag & Drop: Mover post-it entre bloques o reordenar dentro del bloque
•	Doble click: Abrir modal con opciones avanzadas (color, notas, validación)
•	Botón +: Añadir nuevo post-it al bloque
•	Swipe left: Eliminar post-it (con confirmación)
8.3 Panel de Sugerencias IA
Panel lateral que muestra las sugerencias de la IA:
•	Score de Coherencia: Gauge visual 0-100 con código de colores
•	Gaps Detectados: Lista de bloques vacíos o incompletos
•	Incoherencias: Alertas de relaciones problemáticas entre bloques
•	Sugerencias: Post-its sugeridos que se pueden arrastrar al canvas
 
9. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Entidades business_model_canvas, canvas_block. Migrations.	Fase 1 completada
Sprint 2	Semana 3-4	Frontend del Canvas. Grid layout. Post-its básicos.	Sprint 1
Sprint 3	Semana 5-6	Drag & drop. Edición inline. Guardado automático.	Sprint 2
Sprint 4	Semana 7-8	Templates por sector. Inicialización desde diagnóstico.	Sprint 3
Sprint 5	Semana 9-10	Integración IA. Motor de análisis. Panel de sugerencias.	Sprint 4 + RAG
Sprint 6	Semana 11-12	Versionado. Exportación PDF/imagen. Colaboración. QA.	Sprint 5
9.1 KPIs de Éxito
KPI	Target	Medición
Completitud media	> 70%	Promedio de completeness_score de canvas activos
Coherencia media IA	> 65/100	Promedio de coherence_score tras análisis
Canvas por usuario	> 1.5	Número medio de canvas creados por emprendedor
Exportaciones	> 30% de canvas	% de canvas que generan PDF para presentaciones
Uso de sugerencias IA	> 40%	% de sugerencias de IA aceptadas por usuarios
--- Fin del Documento ---
36_Emprendimiento_Business_Model_Canvas_v1.docx | Jaraba Impact Platform | Enero 2026
