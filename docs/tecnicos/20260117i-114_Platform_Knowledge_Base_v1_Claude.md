
KNOWLEDGE BASE & SELF-SERVICE
Especificación Técnica para Implementación
JARABA IMPACT PLATFORM

Versión:	1.0
Fecha:	Enero 2026
Código:	114_Platform_Knowledge_Base_v1
Estado:	Especificación para EDI
 
1. Resumen Ejecutivo
Sistema de Knowledge Base con Help Center público, búsqueda semántica (Qdrant), FAQ Bot con escalación, y Community Forum. Objetivo: reducir tickets de soporte 30-40% mediante self-service.
1.1 Objetivos del Sistema
Objetivo	Métrica Target	Benchmark
Ticket deflection	30-40%	Industry: 20-40%
FAQ Bot resolution	50-60%	Best: 60-70%
Search success rate	> 80%	Good: 70-80%
Time to answer	< 10 segundos	User expectation
CSAT on self-service	> 4.0/5.0	Good: 3.8+
1.2 Componentes del Sistema
•	Help Center: Portal público con artículos organizados por categoría
•	Semantic Search: Búsqueda por significado usando Qdrant (ya presupuestado)
•	FAQ Bot: Chatbot conversacional con strict grounding
•	Video Tutorials: Biblioteca de videos por rol y vertical
•	Community Forum: Soporte peer-to-peer entre usuarios
•	Contextual Help: Ayuda inline según página/contexto actual
 
2. Arquitectura del Sistema
2.1 Stack Tecnológico
Componente	Tecnología	Justificación
Help Center CMS	Drupal Content Types	Integración nativa
Vector Search	Qdrant Cloud (~€25/mes)	Ya presupuestado, excelente rendimiento
Embeddings	text-embedding-3-small	Costo-efectivo, 1536 dims
FAQ Bot	Claude 3.5 Haiku	Bajo costo, strict grounding
Video Hosting	Cloudflare Stream o Bunny	CDN incluido, bajo costo
Forum	Drupal Forum + Flag module	Integrado en ecosistema
2.2 Flujo de Búsqueda
┌─────────────────────────────────────────────────────────────────┐
│                       USER QUERY                                │
│                           │                                     │
│                           ▼                                     │
│              ┌─────────────────────────┐                        │
│              │    Query Embedding      │                        │
│              │  (text-embedding-3-small)│                       │
│              └────────────┬────────────┘                        │
│                           │                                     │
│              ┌────────────▼────────────┐                        │
│              │     Qdrant Search       │                        │
│              │  (top-k + score filter) │                        │
│              └────────────┬────────────┘                        │
│                           │                                     │
│         ┌─────────────────┼─────────────────┐                   │
│         ▼                 ▼                 ▼                   │
│   ┌──────────┐     ┌──────────┐     ┌──────────┐               │
│   │ Articles │     │  Videos  │     │   FAQs   │               │
│   └──────────┘     └──────────┘     └──────────┘               │
│                           │                                     │
│              ┌────────────▼────────────┐                        │
│              │    Rank & Present       │                        │
│              │   (score threshold)     │                        │
│              └─────────────────────────┘                        │
└─────────────────────────────────────────────────────────────────┘
 
3. Modelo de Datos
3.1 Entidad: kb_article
Artículos del Help Center con versionado y traducciones.
Campo	Tipo	Requerido	Descripción
id	UUID	Sí	Identificador único
title	VARCHAR(255)	Sí	Título del artículo
slug	VARCHAR(255)	Sí	URL slug único
content	TEXT	Sí	Contenido en Markdown
excerpt	VARCHAR(500)	Sí	Resumen para búsqueda
category_id	UUID FK	Sí	Categoría del artículo
vertical	ENUM	No	agro|comercio|empleabilidad|emprendimiento|servicios
target_roles	JSON	No	Roles a los que aplica
status	ENUM	Sí	draft|published|archived
view_count	INT	No	Vistas totales
helpful_yes	INT	No	Votos positivos
helpful_no	INT	No	Votos negativos
embedding_id	VARCHAR(100)	No	ID en Qdrant
language	VARCHAR(5)	Sí	es|en|pt
created_at	TIMESTAMP	Sí	Fecha creación
updated_at	TIMESTAMP	Sí	Última actualización
3.2 Entidad: kb_category
Categorías para organizar artículos jerárquicamente.
Campo	Tipo	Requerido	Descripción
id	UUID	Sí	Identificador único
name	VARCHAR(100)	Sí	Nombre de categoría
slug	VARCHAR(100)	Sí	URL slug
description	TEXT	No	Descripción
parent_id	UUID FK	No	Categoría padre
icon	VARCHAR(50)	No	Icono (ej: heroicons)
order	INT	Sí	Orden de visualización
article_count	INT	No	Artículos en categoría
3.3 Entidad: kb_video
Video tutoriales con transcripción para búsqueda.
Campo	Tipo	Requerido	Descripción
id	UUID	Sí	Identificador único
title	VARCHAR(255)	Sí	Título del video
description	TEXT	Sí	Descripción
video_url	VARCHAR(500)	Sí	URL del video (CDN)
thumbnail_url	VARCHAR(500)	Sí	URL de miniatura
duration_seconds	INT	Sí	Duración en segundos
transcript	TEXT	No	Transcripción completa
category_id	UUID FK	Sí	Categoría
target_roles	JSON	No	Roles objetivo
view_count	INT	No	Reproducciones
embedding_id	VARCHAR(100)	No	ID en Qdrant
3.4 Entidad: faq_conversation
Conversaciones con el FAQ Bot para análisis y mejora.
Campo	Tipo	Requerido	Descripción
id	UUID	Sí	Identificador único
user_id	UUID FK	No	Usuario (si autenticado)
session_id	VARCHAR(100)	Sí	ID de sesión anónima
messages	JSON	Sí	Array de mensajes
resolution	ENUM	No	resolved|escalated|abandoned
satisfaction	INT (1-5)	No	Rating del usuario
escalated_ticket_id	UUID FK	No	Ticket si escaló
created_at	TIMESTAMP	Sí	Inicio conversación
 
4. FAQ Bot con Strict Grounding
4.1 Principios del FAQ Bot
•	Solo responde con información presente en la Knowledge Base
•	Cita fuentes específicas (artículos, videos) en cada respuesta
•	Declara explícitamente cuando no tiene información
•	Ofrece escalación a humano cuando no puede resolver
•	Aprende de conversaciones marcadas como "no resueltas"
4.2 Flujo de Escalación
Trigger	Condición	Acción
No match	Score < 0.7 en búsqueda	Ofrecer contacto con soporte
User request	"Quiero hablar con alguien"	Crear ticket + notificar
Frustration	3+ mensajes sin resolución	Proactivamente ofrecer escalación
Negative feedback	Rating < 3	Escalar para revisión
 
5. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/kb/articles	Listar artículos (filtros: category, vertical, role)
GET	/api/v1/kb/articles/{slug}	Detalle de artículo
POST	/api/v1/kb/articles/{id}/feedback	Enviar feedback (helpful yes/no)
GET	/api/v1/kb/categories	Listar categorías
GET	/api/v1/kb/videos	Listar videos
GET	/api/v1/kb/search	Búsqueda semántica (query, limit, filters)
POST	/api/v1/kb/faq-bot/message	Enviar mensaje al FAQ Bot
POST	/api/v1/kb/faq-bot/escalate	Escalar a soporte humano
POST	/api/v1/kb/faq-bot/feedback	Feedback de la conversación
GET	/api/v1/kb/contextual-help	Ayuda contextual por page_path
 
6. Roadmap de Implementación
Sprint	Timeline	Entregables
Sprint 1	Semana 1-2	Entidades BD. Help Center básico con categorías.
Sprint 2	Semana 3-4	Integración Qdrant. Pipeline de embeddings.
Sprint 3	Semana 5-6	Búsqueda semántica UI. Resultados rankeados.
Sprint 4	Semana 7-8	FAQ Bot v1. Strict grounding. Escalación básica.
Sprint 5	Semana 9-10	Video tutorials. Transcripción + embedding.
Sprint 6	Semana 11-12	Contextual help. Analytics de uso. Go-live.
6.1 Estimación de Esfuerzo
Componente	Horas Estimadas
Help Center CMS + UI	60-80h
Qdrant Integration + Embeddings	40-60h
Semantic Search UI	30-40h
FAQ Bot + Escalación	60-80h
Video Tutorials System	40-50h
Contextual Help	20-30h
TOTAL	250-340h
--- Fin del Documento ---
