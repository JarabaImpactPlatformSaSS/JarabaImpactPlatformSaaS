HERRAMIENTA DE ANÁLISIS COMPETITIVO
Competitive Analysis Tool
Vertical de Emprendimiento Digital
JARABA IMPACT PLATFORM

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	27_Emprendimiento_Competitive_Analysis_Tool
Dependencias:	25_Business_Diagnostic, 36_Business_Model_Canvas
 
1. Resumen Ejecutivo
La Herramienta de Análisis Competitivo permite a emprendedores identificar, analizar y posicionarse frente a competidores de su mercado. Incluye matriz de posicionamiento, análisis de gaps de mercado, oportunidades de diferenciación y monitoreo básico de competencia.
1.1 Funcionalidades Principales
Funcionalidad	Descripción	Nivel
Identificación de competidores	Búsqueda guiada + sugerencias automáticas	Básico
Perfil de competidor	Ficha con datos clave de cada competidor	Básico
Matriz de posicionamiento	Gráfico 2D con ejes personalizables	Intermedio
Análisis FODA comparativo	Fortalezas/debilidades vs competencia	Intermedio
Gap Analysis	Identificación de huecos de mercado	Avanzado
Monitoreo web	Alertas de cambios en competidores	Premium
1.2 Filosofía 'Sin Humo'
•	Datos reales: Basado en información verificable, no en suposiciones
•	Accionable: Cada análisis termina en acciones concretas de diferenciación
•	Actualizable: La competencia cambia, el análisis debe actualizarse
 
2. Arquitectura de Datos
2.1 Entidad: competitor
Campo	Tipo	Descripción
id	Serial	PRIMARY KEY
user_id	INT	FK users.uid (quien lo creó)
name	VARCHAR(255)	Nombre del competidor
website	VARCHAR(500)	URL del sitio web
location	VARCHAR(255)	Ubicación/territorio
competitor_type	VARCHAR(24)	ENUM: direct|indirect|substitute|potential
size	VARCHAR(16)	ENUM: micro|small|medium|large
years_in_market	INT	Años operando
price_positioning	VARCHAR(16)	ENUM: low|mid_low|mid|mid_high|high|premium
target_audience	TEXT	Público objetivo
main_products	JSON	Productos/servicios principales
strengths	JSON	Fortalezas identificadas
weaknesses	JSON	Debilidades identificadas
differentiators	JSON	Factores diferenciadores
digital_presence_score	INT	Puntuación presencia digital 0-100
social_media	JSON	{instagram, facebook, linkedin, tiktok}
google_rating	DECIMAL(2,1)	Rating en Google
google_reviews_count	INT	Número de reseñas
notes	TEXT	Notas adicionales
last_updated	DATETIME	Última actualización
is_active	BOOLEAN	Sigue activo en el mercado
2.2 Entidad: competitive_analysis
Campo	Tipo	Descripción
id	Serial	PRIMARY KEY
user_id	INT	FK users.uid
analysis_date	DATETIME	Fecha del análisis
competitors	JSON	Array de competitor IDs incluidos
positioning_axes	JSON	{x_axis, y_axis} para matriz
positioning_data	JSON	Posiciones de cada competidor en matriz
my_position	JSON	Mi posición en la matriz
gaps_identified	JSON	Oportunidades de mercado detectadas
differentiation_opportunities	JSON	Acciones de diferenciación
swot_comparison	JSON	FODA comparativo
conclusions	TEXT	Conclusiones del análisis
action_items	JSON	Acciones recomendadas
status	VARCHAR(16)	ENUM: draft|completed
 
3. Identificación de Competidores
3.1 Tipos de Competidores
Tipo	Definición	Ejemplo (panadería artesanal)
Directo	Mismo producto, mismo mercado	Otras panaderías artesanales locales
Indirecto	Producto diferente, misma necesidad	Supermercados con sección panadería
Sustituto	Solución alternativa a la necesidad	Cadenas de franquicia, pan industrial
Potencial	Podría entrar al mercado	Cafeterías que podrían añadir panadería
3.2 Fuentes de Identificación
•	Google Maps: Búsqueda por categoría en el territorio
•	Google Search: Keywords del sector + localidad
•	Redes Sociales: Hashtags locales, menciones, ubicaciones
•	Directorios sectoriales: Cámaras de comercio, asociaciones
•	Input del emprendedor: Conocimiento local del mercado
 
4. Matriz de Posicionamiento
4.1 Ejes Disponibles
Eje	Descripción	Escala
Precio	Nivel de precios relativo	Bajo ←→ Alto
Calidad	Calidad percibida de producto/servicio	Básica ←→ Premium
Variedad	Amplitud de catálogo	Especialista ←→ Generalista
Conveniencia	Facilidad de acceso/compra	Tradicional ←→ Conveniente
Digitalización	Nivel de presencia/venta digital	Offline ←→ Digital-first
Personalización	Grado de customización	Estándar ←→ A medida
Sostenibilidad	Compromiso ambiental/social	Convencional ←→ Sostenible
4.2 Visualización
Gráfico de dispersión 2D donde cada competidor es un punto:
                    CALIDAD ALTA                          ▲           Premium    ●C   │   ●B    Gourmet                           │        ───────────────────┼───────────────────▶ PRECIO                           │                    ALTO           Budget     ●D   │   ★TÚ                           │                     CALIDAD BAJA  ● = Competidores    ★ = Tu posición actual/deseada
 
5. Análisis de Gaps de Mercado
Identificación de espacios no cubiertos por la competencia:
5.1 Tipos de Gaps
Tipo de Gap	Descripción	Oportunidad
Gap de precio	Rango de precio no cubierto	Posicionarse en ese rango
Gap de producto	Producto/servicio no ofrecido	Introducir el producto
Gap de canal	Canal de venta no explotado	Ser pionero en el canal
Gap geográfico	Zona no atendida	Expandir a esa zona
Gap de segmento	Público no atendido	Especializarse en ese segmento
Gap de servicio	Nivel de servicio no ofrecido	Diferenciarse por servicio
5.2 Evaluación de Gaps
Cada gap se evalúa con:
•	Tamaño de oportunidad: ¿Cuánto mercado hay?
•	Capacidad de captura: ¿Podemos atenderlo?
•	Barreras de entrada: ¿Qué necesitamos para entrar?
•	Sostenibilidad: ¿Podemos mantener la ventaja?
 
6. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/competitors	Lista de competidores del usuario
GET	/api/v1/competitors/{id}	Detalle de competidor
POST	/api/v1/competitors	Crear competidor
PUT	/api/v1/competitors/{id}	Actualizar competidor
DELETE	/api/v1/competitors/{id}	Eliminar competidor
GET	/api/v1/competitive-analysis	Lista de análisis
POST	/api/v1/competitive-analysis	Crear nuevo análisis
GET	/api/v1/competitive-analysis/{id}	Detalle de análisis
GET	/api/v1/competitive-analysis/{id}/matrix	Datos de matriz
GET	/api/v1/competitive-analysis/{id}/gaps	Gaps identificados
POST	/api/v1/competitors/suggest	Sugerir competidores (AI)
7. Roadmap de Implementación
Sprint	Timeline	Entregables
Sprint 1	Semana 1-2	Entidades competitor, competitive_analysis. CRUD.
Sprint 2	Semana 3-4	Matriz de posicionamiento. Visualización.
Sprint 3	Semana 5-6	Gap analysis. SWOT comparativo.
Sprint 4	Semana 7-8	Sugerencias AI. Exportación PDF. QA.
--- Fin del Documento ---
