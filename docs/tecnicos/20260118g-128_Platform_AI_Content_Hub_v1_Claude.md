
AI CONTENT HUB
Sistema Integral de Publicación
Blog, Newsletter y Contenido Asistido por IA

JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Campo	Valor
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	128_Platform_AI_Content_Hub
Dependencias:	114_Knowledge_Base, 108_AI_Agent_Flows, Core Modules
 
1. Resumen Ejecutivo
Este documento especifica la arquitectura técnica del Sistema AI Content Hub, un motor centralizado de publicación de contenido que integra blog, newsletter y recomendaciones con asistencia de IA nativa. El sistema representa una pieza estratégica para el Ecosistema Jaraba, permitiendo a los tenants generar, optimizar y distribuir contenido de alta calidad de forma eficiente.
1.1 Justificación Estratégica
La investigación de mercado 2025-2026 identifica tres tendencias críticas que justifican este módulo:
•	El contenido sin alineación SEO/GEO es solo ruido: publicar 20 blogs al mes no significa nada si no rankean para términos relevantes
•	El CMS está evolucionando de herramienta de publicación a sistema inteligente que unifica marketing, producto y desarrollo
•	Los agentes de IA están transformando cómo las empresas crean, optimizan y gobiernan contenido dentro de sistemas CMS SaaS
•	El mercado de Headless CMS crecerá de $3.94B (2025) a $22.28B (2034), con CAGR del 21%
1.2 Propuesta de Valor
Capacidad	Descripción	Impacto
AI Writing Assistant	Generación de contenido optimizado para SEO/GEO con voz de marca	3x más velocidad de producción
Editorial Workflow	Flujos de aprobación, versionado y colaboración multi-autor	30% menos tiempo de revisión
Newsletter Engine	Automatización de newsletters personalizadas por segmento	40% más engagement
Content Recommendations	Sugerencias personalizadas de contenido relacionado	25% más tiempo en sitio
Multi-Tenant Publishing	Cada tenant con su blog, newsletter y voz de marca propia	Escalabilidad infinita
1.3 Stack Tecnológico
Componente	Tecnología	Justificación
Core CMS	Drupal 11 + módulo jaraba_content_hub	Entidades estructuradas, multi-tenant nativo
AI Generation	Claude API (claude-sonnet-4-5)	Mejor calidad de escritura, strict grounding
Vector Search	Qdrant (ya presupuestado)	Búsqueda semántica de contenido relacionado
Newsletter	ActiveCampaign + ECA automation	Integración existente, segmentación avanzada
Editor Frontend	Gutenberg-style con React	UX familiar, bloques reutilizables
Programmatic SEO	Schema.org + Answer Capsules	Visibilidad en AI Search results
 
2. Arquitectura del Sistema
2.1 Diagrama de Alto Nivel
El AI Content Hub se estructura en 5 capas principales que interactúan de forma cohesiva:

┌─────────────────────────────────────────────────────────────┐
│              PRESENTATION LAYER (Frontend)                  │
│    Blog Public │ Editor Dashboard │ Newsletter Preview      │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│              AI GENERATION LAYER (Intelligence)             │
│  Content Writer │ SEO Optimizer │ Headline Generator       │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│              CONTENT MANAGEMENT LAYER (Drupal 11)           │
│   Articles │ Categories │ Authors │ Media │ Templates      │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│              DISTRIBUTION LAYER (Automation)                │
│   Newsletter Queue │ Social Sharing │ RSS/Sitemap          │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│              ANALYTICS LAYER (Intelligence)                 │
│   Performance │ Engagement │ Content Gaps │ AI Learning    │
└─────────────────────────────────────────────────────────────┘
2.2 Modelo Multi-Tenant
El sistema implementa aislamiento a nivel de contenido mediante el Group Module de Drupal, permitiendo que cada tenant tenga:
•	Blog propio con URL personalizada (/{tenant}/blog)
•	Categorías y taxonomías independientes
•	Templates y voz de marca configurables
•	Newsletter con lista de suscriptores aislada
•	Métricas y analytics segregados
 
3. Modelo de Datos
3.1 Entidad: content_article
Representa un artículo de blog publicable con soporte completo para optimización GEO.
Campo	Tipo	Descripción
id	UUID	Identificador único
tenant_id	entity_reference	Grupo/tenant propietario
title	string(255)	Título del artículo
slug	string(255)	URL amigable auto-generada
excerpt	text(500)	Resumen para listados y meta description
body	text_long	Contenido principal (Markdown/HTML)
answer_capsule	text(200)	Primeros 150-200 chars optimizados para AI
featured_image	entity_reference(media)	Imagen principal
category	entity_reference(taxonomy)	Categoría principal
tags	entity_reference(taxonomy) [multiple]	Etiquetas adicionales
author	entity_reference(user)	Autor del artículo
reading_time	integer	Minutos estimados de lectura
seo_title	string(70)	Título optimizado para SEO
seo_description	text(160)	Meta description
schema_json	json	Schema.org Article estructurado
ai_generated	boolean	Indica si fue generado por IA
ai_prompt_used	text	Prompt utilizado para auditoría
status	list(draft|review|scheduled|published|archived)	Estado del contenido
publish_date	datetime	Fecha de publicación programada
created_at	datetime	Fecha de creación
updated_at	datetime	Última modificación
3.2 Entidad: newsletter_campaign
Representa una campaña de newsletter enviada a suscriptores.
Campo	Tipo	Descripción
id	UUID	Identificador único
tenant_id	entity_reference	Grupo/tenant propietario
subject	string(100)	Asunto del email
preheader	string(150)	Texto de preview en bandeja
template	entity_reference(newsletter_template)	Plantilla utilizada
content_blocks	json	Bloques de contenido estructurados
article_references	entity_reference [multiple]	Artículos incluidos
segment	entity_reference(subscriber_segment)	Segmento destinatario
scheduled_at	datetime	Fecha/hora de envío programado
sent_at	datetime	Fecha/hora de envío real
status	list(draft|scheduled|sending|sent|failed)	Estado de la campaña
stats_sent	integer	Emails enviados
stats_opened	integer	Aperturas totales
stats_clicked	integer	Clicks totales
stats_unsubscribed	integer	Bajas generadas
3.3 Entidad: content_recommendation
Almacena recomendaciones generadas por el motor de IA para contenido relacionado.
Campo	Tipo	Descripción
id	UUID	Identificador único
source_article	entity_reference(content_article)	Artículo de origen
recommended_article	entity_reference(content_article)	Artículo recomendado
score	decimal(5,4)	Puntuación de relevancia (0-1)
recommendation_type	list(semantic|collaborative|trending)	Tipo de recomendación
generated_at	datetime	Momento de generación
expires_at	datetime	Validez de la recomendación
 
4. Sistema de Generación de Contenido con IA
4.1 Principios de Diseño
El sistema de generación de contenido sigue los principios establecidos en la documentación del ecosistema:
•	Strict Grounding: Solo genera contenido basado en fuentes verificables y contexto del tenant
•	Brand Voice: Cada tenant puede definir su tono, estilo y restricciones
•	Human-in-the-Loop: La IA asiste, el humano aprueba y refina
•	Sin Humo: Contenido útil y accionable, no relleno
•	GEO-First: Optimizado desde el primer draft para visibilidad en AI search
4.2 AI Writing Assistant
El asistente de escritura integra Claude API con contexto específico del tenant:
4.2.1 Flujo de Generación
•	Usuario proporciona: tema, keywords objetivo, tipo de artículo, audiencia
•	Sistema enriquece con: voz de marca, artículos relacionados, datos del sector
•	IA genera: outline estructurado con H2/H3
•	Usuario revisa y ajusta outline
•	IA genera: borrador completo sección por sección
•	Usuario edita, añade expertise, personaliza
•	Sistema valida: SEO score, legibilidad, originalidad
•	Publicación con Answer Capsule auto-generada
4.2.2 Prompt Template para Artículos

// System Prompt para AI Writing Assistant

Eres un experto redactor de contenido para {TENANT_INDUSTRY}.
Tu objetivo es crear contenido que:

1. Responda a la intención de búsqueda del usuario
2. Sea útil, específico y accionable
3. Incluya Answer Capsule en los primeros 150 caracteres
4. Siga el tono de marca: {BRAND_VOICE}
5. Evite: {TABOO_TERMS}

Contexto del tenant:
- Industria: {TENANT_INDUSTRY}
- Audiencia: {TARGET_AUDIENCE}
- Artículos relacionados: {RELATED_ARTICLES}

Keywords objetivo: {TARGET_KEYWORDS}
Tipo de contenido: {CONTENT_TYPE}
Longitud: {WORD_COUNT} palabras
4.3 Capacidades del AI Assistant
Capacidad	Descripción	Trigger
Generate Outline	Crea estructura H2/H3 basada en keyword research	Usuario inicia nuevo artículo
Expand Section	Desarrolla una sección específica del outline	Click en sección vacía
Rewrite Paragraph	Mejora claridad, tono o SEO de un párrafo	Selección + comando
Generate Headlines	Propone 5 variantes de título optimizadas	Botón en editor
Add Statistics	Busca y sugiere datos relevantes verificables	Comando en contexto
Create Summary	Genera excerpt y Answer Capsule	Pre-publicación automática
Optimize SEO	Analiza y sugiere mejoras de SEO on-page	Score bajo en análisis
Translate	Traduce manteniendo voz de marca y SEO	Selector de idioma
 
5. Sistema de Newsletter Automatizado
5.1 Arquitectura de Newsletter
El motor de newsletter integra las mejores prácticas de 2025 para automatización con IA:
•	Bloques de contenido modulares reutilizables
•	Personalización dinámica por segmento de audiencia
•	A/B testing automatizado de subject lines
•	Integración nativa con ActiveCampaign
•	Métricas en tiempo real con feedback loop para IA
5.2 Tipos de Newsletter
Tipo	Frecuencia	Contenido	Automatización
Digest Semanal	Semanal (configurable)	Top artículos de la semana + highlight	100% automática con curación IA
Nueva Publicación	Por evento	Artículo nuevo destacado	Trigger en publicación
Temática	Mensual	Deep-dive en tema específico	Semi-automática, requiere tema
Engagement	Por comportamiento	Contenido basado en intereses del user	100% automática por segmento
Re-engagement	Por inactividad	Mejores artículos para usuarios fríos	Trigger 30 días sin apertura
5.3 Flujo de Automatización (ECA)
Regla	Trigger	Condición	Acción
Weekly Digest	Cron: Lunes 8:00	Hay 3+ artículos nuevos	Generar y enviar digest
New Article	Artículo publicado	Flag 'notify_subscribers' = true	Email a segmento relevante
Welcome Series	Nuevo suscriptor	Confirmación completada	Secuencia de 3 emails
Re-engage	User inactivo 30d	No ha abierto últimos 3 emails	Email con mejores artículos
AI Curación	Pre-envío digest	Selección automática	IA ordena por relevancia
5.4 Personalización con IA
El sistema utiliza el perfil del suscriptor para personalizar:
•	Subject line optimizado por historial de aperturas
•	Orden de artículos basado en intereses detectados
•	Recomendaciones personalizadas en footer
•	Timing de envío óptimo por usuario
•	Contenido dinámico según vertical (Empleabilidad, Emprendimiento, etc.)
 
6. Motor de Recomendaciones de Contenido
6.1 Estrategia de Recomendación
El sistema combina tres enfoques para maximizar engagement:
6.1.1 Content-Based (Semántico)
Utiliza embeddings vectoriales en Qdrant para encontrar artículos similares basándose en:
•	Similitud semántica del contenido completo
•	Overlap de keywords y entidades
•	Mismo cluster temático en taxonomía
6.1.2 Collaborative Filtering
Analiza patrones de lectura de usuarios similares:
•	Usuarios que leyeron A también leyeron B
•	Secuencias comunes de consumo de contenido
•	Co-ocurrencia en sesiones de navegación
6.1.3 Trending/Recency
Prioriza contenido con momentum:
•	Artículos con pico de tráfico reciente
•	Contenido con alto engagement social
•	Nuevas publicaciones relevantes al contexto
6.2 Ubicaciones de Recomendación
Ubicación	Tipo	Cantidad	Algoritmo
Final de artículo	Relacionados	3-4 cards	Semántico + Trending
Sidebar derecha	Populares	5 links	Trending últimos 7 días
Homepage blog	Para ti	6 cards	Collaborative + Recency
Email newsletter	Destacados	3 bloques	IA curación personalizada
Exit intent popup	Más leídos	1 highlight	Trending + high conversion
6.3 Pipeline de Indexación
El contenido se indexa en Qdrant siguiendo el pipeline establecido en 20260110h-KB_AI_Nativa:
•	Chunking contextual preservando párrafos completos
•	Embedding con text-embedding-3-small (1536 dims)
•	Metadata incluye: tenant_id, category, tags, publish_date, engagement_score
•	Reindexación incremental cada hora vía cron
•	Namespace por tenant para aislamiento
 
7. Optimización GEO (Generative Engine Optimization)
7.1 Cambio de Paradigma
El SEO tradicional ya no es suficiente. Con ChatGPT Search, Perplexity y Google AI Overviews, la visibilidad depende de que los LLMs puedan extraer y citar el contenido. Y Combinator predice -25% de tráfico de búsqueda tradicional para 2026.
7.2 Answer Capsules
Cada artículo incluye una Answer Capsule optimizada para extracción por IA:
•	Primeros 150-200 caracteres responden directamente a la intención de búsqueda
•	Formato: [Qué es] + [Por qué importa] + [Beneficio concreto]
•	Campo dedicado field_answer_capsule en Drupal
•	Se renderiza en código fuente pero puede ocultarse visualmente
7.2.1 Ejemplo de Answer Capsule
Tema: "¿Cómo digitalizar mi negocio agrícola?"

Answer Capsule: La digitalización agrícola comienza con tres pasos: crear presencia online con ficha de producto profesional, implementar trazabilidad QR para generar confianza, y conectar con marketplaces B2B. El 78% de compradores mayoristas ya prefieren proveedores digitalizados.
7.3 Schema.org para Artículos
Cada artículo genera automáticamente JSON-LD completo:
•	@type: Article + author (Person/Organization)
•	datePublished, dateModified para señales de frescura
•	wordCount, timeRequired para rich snippets
•	about con entidades de Schema.org relacionadas
•	FAQPage schema para secciones de preguntas
7.4 Archivo /llms.txt
El sistema genera dinámicamente /llms.txt por tenant siguiendo el estándar emergente:
# /llms.txt - Blog de {TENANT_NAME}

## Sobre este blog
{TENANT_DESCRIPTION}

## Categorías principales
- /blog/empleabilidad: Artículos sobre búsqueda de empleo
- /blog/emprendimiento: Guías para emprendedores

## Artículos destacados
{TOP_10_ARTICLES_BY_ENGAGEMENT}
 
8. Editorial Workflow
8.1 Estados de Contenido
Estado	Descripción	Permisos	Transiciones Permitidas
draft	Borrador en edición	Autor, Editor	review, archived
review	En revisión editorial	Editor, Admin	draft, scheduled, published
scheduled	Programado para publicación	Editor, Admin	draft, published
published	Publicado y visible	Admin	draft, archived
archived	Archivado, no visible	Admin	draft, published
8.2 Roles y Permisos
Rol	Crear	Editar Propio	Editar Todos	Publicar	Eliminar
Contributor	Sí	Sí	No	No	No
Author	Sí	Sí	No	Propio	Propio
Editor	Sí	Sí	Sí	Sí	No
Admin	Sí	Sí	Sí	Sí	Sí
8.3 Automatizaciones de Workflow (ECA)
Regla	Trigger	Acción
Notify Editor	Artículo pasa a review	Email a editores del tenant
Schedule Check	Llega fecha programada	Cambiar estado a published
AI Quality Gate	Pre-review	Verificar SEO score > 70
Auto Archive	Artículo sin visitas 180d	Sugerir archivado
Translation Queue	Publicación en idioma principal	Crear tarea de traducción
 
9. APIs y Endpoints
9.1 REST API
Endpoint	Método	Descripción
/api/content/articles	GET	Lista artículos con filtros (category, tag, status)
/api/content/articles/{id}	GET	Detalle de artículo con recomendaciones
/api/content/articles	POST	Crear nuevo artículo (requiere auth)
/api/content/articles/{id}	PATCH	Actualizar artículo
/api/content/articles/{id}/generate	POST	Generar contenido con IA
/api/content/recommendations/{id}	GET	Recomendaciones para artículo
/api/newsletter/campaigns	GET/POST	Gestión de campañas
/api/newsletter/subscribers	GET/POST	Gestión de suscriptores
9.2 AI Generation Endpoint
POST /api/content/articles/generate
// Request
{
  "topic": "Cómo preparar tu CV para el sector tech",
  "keywords": ["CV tech", "curriculum vitae", "empleo IT"],
  "content_type": "guide",  // guide | tutorial | listicle | comparison
  "word_count": 1500,
  "target_audience": "junior_developers",
  "generation_mode": "outline"  // outline | full | section
}
9.3 Webhook Events
Evento	Payload	Uso típico
article.published	article_id, tenant_id, url	Trigger newsletter, social sharing
article.engagement	article_id, views, time_on_page	Actualizar ranking, recomendaciones
newsletter.sent	campaign_id, recipients_count	Logging, analytics
newsletter.opened	campaign_id, subscriber_id	Engagement tracking, lead scoring
subscriber.new	subscriber_id, source	Welcome series, CRM sync
 
10. Roadmap de Implementación
10.1 Fase 1: Core Blog (Sprints 1-2)
Semanas 1-4 | 80-100 horas desarrollo
•	Entidad content_article con todos los campos
•	Taxonomías: categories, tags, authors
•	Views para listados: homepage, category, tag, author
•	Templates Twig para artículo y listados
•	SEO básico: metatags, sitemap, Schema.org Article
•	Integración con tema jaraba_theme existente
10.2 Fase 2: AI Writing Assistant (Sprints 3-4)
Semanas 5-8 | 100-120 horas desarrollo
•	Módulo jaraba_content_ai con integración Claude API
•	UI de generación en editor (React component)
•	Prompt templates por tipo de contenido
•	Brand voice configuration por tenant
•	Answer Capsule auto-generation
•	SEO scoring en tiempo real
10.3 Fase 3: Newsletter Engine (Sprints 5-6)
Semanas 9-12 | 80-100 horas desarrollo
•	Entidades newsletter_campaign, subscriber, template
•	Integración ActiveCampaign vía API
•	ECA rules para automatizaciones
•	Templates de email responsive
•	Editor de bloques para newsletter
•	Dashboard de métricas de campaña
10.4 Fase 4: Recommendations & Analytics (Sprints 7-8)
Semanas 13-16 | 60-80 horas desarrollo
•	Pipeline de indexación en Qdrant
•	Motor de recomendaciones híbrido
•	Widgets de contenido relacionado
•	Dashboard de analytics de contenido
•	A/B testing framework básico
•	Content Gap Analyzer integration
10.5 Estimación de Inversión
Fase	Horas Est.	Costo €	Entregables Clave
Fase 1: Core Blog	80-100h	€6,400-8,000	Blog funcional multi-tenant
Fase 2: AI Assistant	100-120h	€8,000-9,600	Generación IA integrada
Fase 3: Newsletter	80-100h	€6,400-8,000	Newsletter automatizado
Fase 4: Analytics	60-80h	€4,800-6,400	Recomendaciones + métricas
TOTAL	320-400h	€25,600-32,000	Sistema completo
* Basado en tarifa €80/hora desarrollo senior
 
11. Conclusiones y Recomendación
11.1 Respuesta a la Pregunta Inicial
SÍ, el Ecosistema Jaraba necesita un sistema de blog, recomendaciones y newsletter integrado en el núcleo del SaaS. Las razones son:
•	Diferenciación competitiva: El 73% de empresas planean implementar agentes IA para contenido en 2025
•	Autoridad semántica: El contenido estructurado con GEO genera visibilidad en AI Search
•	Escalabilidad multi-tenant: Cada vertical puede tener su blog sin duplicar infraestructura
•	Revenue enablement: El contenido alimenta el funnel del Triple Motor Económico
•	Reúso de arquitectura: Aprovecha Qdrant, ECA, ActiveCampaign ya presupuestados
11.2 Prioridad de Implementación
Recomendación: Implementar como módulo del Core Platform (documento 128) con prioridad ALTA después de completar las verticales principales. El Content Hub potencia todas las verticales al generar tráfico orgánico y nurturing automatizado.
11.3 Siguientes Pasos
•	Aprobar inclusión en roadmap Q2 2026
•	Definir tenant piloto para validación (sugerencia: pepejaraba.com)
•	Especificar brand voice guidelines para cada vertical
•	Configurar tracking de métricas GEO en Google Search Console
•	Iniciar Sprint 1 con Core Blog

--- Fin del Documento ---

Jaraba Impact Platform | AI Content Hub v1.0 | Enero 2026
