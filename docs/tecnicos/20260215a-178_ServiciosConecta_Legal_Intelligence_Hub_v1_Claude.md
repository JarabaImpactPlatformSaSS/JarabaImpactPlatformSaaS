
LEGAL INTELLIGENCE HUB
Base de Conocimiento Jurisprudencial y Normativo con IA
Resoluciones Administrativas + Judiciales + Normativa Vigente
Vertical ServiciosConecta — JARABA IMPACT PLATFORM

Versión:	1.0
Fecha:	Febrero 2026
Estado:	Especificación Técnica
Código:	178_ServiciosConecta_Legal_Intelligence_Hub
Dependencias:	82_Services_Core, 88_Buzon_Confianza, 93_Copilot_Servicios, 130_Tenant_Knowledge_Training
Tecnología:	RAG (Qdrant) + Web Scraping + NLP + Gemini 2.0 Flash
Prioridad:	ALTA — Diferenciador competitivo legal
Compliance:	RGPD, Secreto profesional, Ley 18/2011 (EJIS)
 
 
Índice
Índice	1
1. Resumen Ejecutivo	1
1.1 El Problema: Profesionales sin Acceso a Información Actualizada	1
1.2 La Solución: Legal Intelligence Hub	1
1.3 Diferenciadores vs. Bases de Datos Jurídicas Tradicionales	1
2. Fuentes de Datos Oficiales	1
2.1 Catálogo de Fuentes	1
2.2 Arquitectura de Ingesta	1
2.3 Modelo de Datos: Resolución	1
3. Pipeline de Procesamiento NLP	1
3.1 Etapas del Pipeline	1
3.2 Prompt de Clasificación y Resumen	1
3.3 Taxonomías Jurídicas	1
4. Búsqueda Semántica e Interfaz del Profesional	1
4.1 Modos de Búsqueda	1
4.2 Integración con Copilot de Servicios	1
4.3 Flujo de Inserción en Expediente	1
4.4 Interfaz de Resultados	1
5. Sistema de Alertas Inteligentes	1
5.1 Tipos de Alertas	1
5.2 Entidad: legal_alert_subscription	1
6. APIs REST	1
6.1 Endpoints de Búsqueda	1
6.2 Endpoints de Integración con Expediente	1
6.3 Endpoints de Alertas	1
6.4 Endpoints de Administración	1
7. Flujos de Automatización ECA	1
7.1 ECA-LIH-001: Ingesta Programada de Fuentes	1
7.2 ECA-LIH-002: Pipeline NLP Async	1
7.3 ECA-LIH-003: Detector de Cambios Normativos	1
7.4 ECA-LIH-004: Digest Semanal Personalizado	1
8. Configuración Multi-Tenant	1
8.1 Modelo de Compartición	1
8.2 Límites por Plan	1
9. Permisos y Seguridad	1
9.1 Matriz de Permisos RBAC	1
9.2 Consideraciones de Secreto Profesional	1
10. Casos de Uso por Profesión	1
10.1 Abogado (Elena — Avatar Principal)	1
10.2 Asesoría Fiscal	1
10.3 Gestoría Administrativa	1
11. Configuración de Qdrant	1
11.1 Colección: legal_intelligence	1
11.2 Metadatos por Vector (Payload)	1
11.3 Estimación de Volumen	1
12. SEO y Visibilidad	1
12.1 Schema.org para Resoluciones	1
12.2 Estrategia SEO/GEO	1
13. Roadmap de Implementación	1
14. Consideraciones Legales y de Compliance	1
14.1 Reutilización de Información Pública	1
14.2 RGPD y Datos Personales	1
14.3 Secreto Profesional	1
14.4 Disclaimer Obligatorio	1
15. Impacto en el Ecosistema Jaraba	1
15.1 Valor para el Avatar Elena	1
15.2 Impacto en Revenue	1
15.3 Dependencias con Documentos Existentes	1
16. Conclusión	1
Control de Versiones	1

 
1. Resumen Ejecutivo
El Legal Intelligence Hub es un módulo especializado que dota a abogados, asesorías fiscales y gestorías de acceso inteligente a resoluciones administrativas, jurisprudencia y normativa vigente, integrado nativamente con el Copilot de Servicios (doc 93) y el Buzón de Confianza (doc 88) de ServiciosConecta.
A diferencia de bases de datos jurídicas tradicionales como Aranzadi, Lefebvre o vLex —que requieren suscripciones costosas de 2.000-8.000€/año—, este módulo aprovecha fuentes públicas oficiales (CENDOJ, BOE, DGT, TEAC, IGAE, BOICAC) y las enriquece con IA para ofrecer búsqueda semántica, resumen automático e inserción directa de citas en documentos del expediente del profesional.
El resultado: una Elena (avatar principal de ServiciosConecta) que puede buscar «resolución DGT sobre tributación de criptomonedas en IRPF», obtener un resumen de 3 líneas con la referencia oficial, y con un clic insertarlo como argumento fundamentado en el escrito que está redactando para su cliente.
1.1 El Problema: Profesionales sin Acceso a Información Actualizada
Situación Actual	Problema	Consecuencia
Consultas tributarias	DGT publica 5.000+ consultas/año en PDF dispersos	El asesor no encuentra la resolución que necesita o usa una derogada
Jurisprudencia laboral	CENDOJ tiene millones de sentencias sin búsqueda semántica	El abogado gasta 2-3 horas buscando precedentes relevantes
Normativa subvenciones	IGAE/ICAC publican criterios cambiantes	La gestoría aplica criterios obsoletos en justificaciones
Doctrina administrativa	TEAC/TEA publican resoluciones críticas para recursos	Se pierden argumentos de peso por desconocimiento
Coste herramientas	Aranzadi/Lefebvre cuestan 3.000-8.000€/año	Profesionales rurales y pequeños despachos no pueden permitirlo
1.2 La Solución: Legal Intelligence Hub
•	Ingesta automatizada de fuentes públicas oficiales: BOE, CENDOJ, DGT, TEAC, IGAE, BOICAC, BOJA
•	Procesamiento NLP: extracción de entidades jurídicas, clasificación temática, detección de derogaciones
•	Indexación vectorial en Qdrant con embeddings especializados en lenguaje jurídico español
•	Búsqueda semántica: el profesional pregunta en lenguaje natural y recibe resoluciones relevantes
•	Resumen IA: cada resolución tiene un abstract de 3-5 líneas generado por Gemini con strict grounding
•	Inserción en expediente: botón directo para citar la resolución en escritos, informes y formularios
•	Alertas de cambio: notificación cuando una resolución citada en un expediente es modificada o anulada
•	Integración nativa con Copilot Servicios (doc 93): «Busca jurisprudencia sobre impago de alquiler»
1.3 Diferenciadores vs. Bases de Datos Jurídicas Tradicionales
Característica	Aranzadi/Lefebvre/vLex	Legal Intelligence Hub
Coste anual	3.000-8.000€/profesional	Incluido en plan Pro/Enterprise
Búsqueda	Por palabras clave exactas	Semántica (lenguaje natural)
Integración expediente	Ninguna (copy-paste manual)	Inserción directa con un clic
Resúmenes	Manual o inexistente	Automático IA con strict grounding
Alertas de cambio	Básicas por email	Contextuales sobre expedientes activos
Copilot/IA	Emergente, limitada	Nativo: pregunta al Copilot sobre casos
Multi-tenant	Licencia individual	Compartido en la plataforma SaaS
Fuentes	Propietarias (acceso cerrado)	Públicas oficiales (Open Data)
 
2. Fuentes de Datos Oficiales
El sistema ingesta y procesa información de fuentes públicas oficiales del Estado español. Todas estas fuentes publican sus contenidos bajo licencias de reutilización conforme a la Ley 37/2007 de Reutilización de Información del Sector Público y el Real Decreto 1495/2011.
2.1 Catálogo de Fuentes
Fuente	URL/API	Contenido	Frecuencia	Prioridad
CENDOJ	cendoj.ramajudicial.es	Jurisprudencia: TS, AN, TSJ, AP	Diaria	CRÍTICA
BOE	boe.es/datosabiertos	Legislación, disposiciones, convenios	Diaria	CRÍTICA
DGT (Consultas Vinculantes)	tributos.hacienda.gob.es	Consultas tributarias vinculantes	Semanal	ALTA
TEAC	tribunaleseconomicoadm.hacienda.gob.es	Resoluciones económico-administrativas	Semanal	ALTA
IGAE	igae.pap.hacienda.gob.es	Informes de auditoría y control financiero	Mensual	MEDIA
BOICAC	icac.gob.es	Consultas contables, normas de auditoría	Trimestral	MEDIA
BOJA	juntadeandalucia.es/boja	Legislación autonómica andaluza	Diaria	ALTA
DGRN/DGSJFP	mjusticia.gob.es	Resoluciones registrales y mercantiles	Semanal	MEDIA
AEPD	aepd.es/resoluciones	Resoluciones protección de datos	Semanal	MEDIA
Tribunal Constitucional	tribunalconstitucional.es	Sentencias y autos del TC	Semanal	ALTA
2.2 Arquitectura de Ingesta
Cada fuente tiene un conector (spider/adapter) específico que gestiona la descarga, deduplicación y normalización de documentos:
                    ┌─────────────────┐
                    │ Scheduler (Cron) │
                    └───────┬─────────┘
                            │
    ┌────────────┬──────┴─────┬────────────┐
    │            │            │            │
 Spider_BOE  Spider_CENDOJ  Spider_DGT  Spider_TEAC
    │            │            │            │
    └────┬───────┴───────┬────┘
         │              │
    Raw Storage     NLP Pipeline
    (MariaDB)       (Apache Tika + spaCy)
         │              │
         └──────┬───────┘
                │
        Qdrant Vector DB
    (collection: legal_intelligence)
2.3 Modelo de Datos: Resolución
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
source_id	VARCHAR(32)	Fuente (cendoj, boe, dgt...)	NOT NULL, INDEX
external_ref	VARCHAR(128)	Referencia oficial (ej: V0123-24)	NOT NULL, UNIQUE
title	VARCHAR(512)	Título de la resolución	NOT NULL
resolution_type	VARCHAR(64)	Tipo: sentencia, consulta, resolución, informe	NOT NULL, INDEX
issuing_body	VARCHAR(128)	Tribunal Supremo, DGT, TEAC, etc.	NOT NULL, INDEX
jurisdiction	VARCHAR(64)	civil, penal, laboral, fiscal, admin	NOT NULL, INDEX
date_issued	DATE	Fecha de emisión	NOT NULL, INDEX
date_published	DATE	Fecha de publicación oficial	INDEX
date_ingested	DATETIME	Fecha de ingesta en el sistema	NOT NULL
status	VARCHAR(32)	vigente, derogada, anulada, superada	NOT NULL, INDEX
abstract_ai	TEXT	Resumen IA (3-5 líneas)	Generated
key_holdings	JSON	Ratio decidendi / doctrina extraída	Generated
topics	JSON	Array de temas: [irpf, criptomonedas, ...]	Generated, INDEX
cited_legislation	JSON	Normas citadas: [{ley, artículo}]	Generated
cited_by	JSON	IDs de resoluciones que la citan	Relational
cites	JSON	IDs de resoluciones citadas	Relational
full_text	LONGTEXT	Texto íntegro	NOT NULL
full_text_hash	VARCHAR(64)	SHA-256 para deduplicación	UNIQUE
source_url	VARCHAR(512)	URL pública original	NOT NULL
pdf_file_id	INT	FK al PDF original almacenado	NULLABLE
vector_ids	JSON	IDs de chunks en Qdrant	Generated
relevance_score	FLOAT	Score de relevancia calculado	DEFAULT 0
 
3. Pipeline de Procesamiento NLP
Cada documento ingresado pasa por un pipeline de procesamiento en múltiples etapas que extrae información estructurada del texto legal no estructurado.
3.1 Etapas del Pipeline
Etapa	Tecnología	Descripción	Output
1. Extracción	Apache Tika	PDF/HTML a texto plano limpio	raw_text
2. Normalización	Python/regex	Limpieza encoding, saltos, cabeceras estándar	clean_text
3. Segmentación	spaCy (es_core_news_lg)	División en secciones: antecedentes, fundamentos, fallo	sections[]
4. NER Jurídico	Modelo custom spaCy	Extracción de entidades: leyes, artículos, tribunales, fechas	entities[]
5. Clasificación	Gemini 2.0 Flash	Asignación de temas, jurisdicción, tipo resolución	metadata
6. Resumen	Gemini 2.0 Flash	Generación de abstract y ratio decidendi	abstract_ai, key_holdings
7. Embeddings	text-embedding-3-large	Vectorización para búsqueda semántica	vectors[]
8. Indexación	Qdrant	Inserción en colección legal_intelligence	vector_ids
9. Grafos	MariaDB	Construcción de red de citas entre resoluciones	cited_by, cites
3.2 Prompt de Clasificación y Resumen
El siguiente prompt se utiliza con Gemini 2.0 Flash para clasificar y resumir cada resolución:
Eres un analista jurídico experto en derecho español.
Analiza la siguiente resolución y proporciona:
1. TIPO: sentencia|auto|consulta_vinculante|resolucion|informe|circular
2. ORGANO: nombre exacto del órgano emisor
3. JURISDICCION: civil|penal|laboral|contencioso|fiscal|mercantil|social
4. TEMAS: array de 3-8 palabras clave específicas
5. RESUMEN: 3-5 líneas en lenguaje técnico-jurídico claro
6. RATIO_DECIDENDI: doctrina principal establecida (1-2 frases)
7. LEGISLACION_CITADA: array de [{norma, articulos}]
8. ESTADO: vigente|derogada|superada (si hay indicios)

REGLAS DE GROUNDING:
- Solo extrae información presente en el texto
- No inventes contenido ni hagas inferencias
- Si no puedes determinar un campo, indica 'indeterminado'
- Cita textualmente la ratio decidendi cuando sea posible

RESOLUCION:
{resolution_text}
3.3 Taxonomías Jurídicas
Vocabulario	Machine Name	Ejemplos
Jurisdicción	legal_jurisdiction	Civil, Penal, Laboral, Contencioso-Administrativo, Fiscal, Mercantil, Social
Tipo Resolución	legal_resolution_type	Sentencia, Auto, Consulta Vinculante, Resolución TEAC, Informe IGAE, Consulta BOICAC, Circular
Órgano Emisor	legal_issuing_body	TS, TC, AN, TSJ, AP, DGT, TEAC, IGAE, ICAC, DGRN, AEPD
Materia Fiscal	legal_topic_fiscal	IRPF, IS, IVA, ITP, Sucesiones, Aduanas, Procedimiento tributario
Materia Laboral	legal_topic_laboral	Despido, Salarios, SS, Prevención, Negociación colectiva, ERE/ERTE
Materia Civil	legal_topic_civil	Contratos, Familia, Herencias, Propiedad, Responsabilidad, Arrendamientos
Materia Mercantil	legal_topic_mercantil	Sociedades, Concursal, Propiedad industrial, Competencia, Bancario
Materia Subvenciones	legal_topic_subvenciones	Justificación, Reintegro, Auditoría, Bases reguladoras, Convocatoria
 
4. Búsqueda Semántica e Interfaz del Profesional
4.1 Modos de Búsqueda
Modo	Ejemplo de Query	Mecanismo	Resultado
Lenguaje Natural	¿Cómo tributa la ganancia por venta de criptomonedas en IRPF?	Embedding query → Qdrant cosine similarity	Top 10 resoluciones más relevantes con score
Referencia Exacta	V0123-24 DGT	Búsqueda por external_ref en MariaDB	Resolución específica con texto íntegro
Filtros Combinados	Sentencias TS laboral despido improcedente 2024	Faceted search: órgano + jurisdicción + tema + fecha	Listado filtrado con facetas
Similar a…	(desde una resolución abierta) Buscar similares	Vector de la resolución actual → Qdrant	Resoluciones con doctrina afín
Copilot (chat)	Busca jurisprudencia reciente sobre cláusula suelo	Intent detection + RAG legal_intelligence	Respuesta conversacional con citas
4.2 Integración con Copilot de Servicios
El Copilot de Servicios (doc 93) se extiende con un nuevo skill de búsqueda jurídica:
// Nuevo intent: legal_search
// Trigger: 'busca jurisprudencia|resolución|sentencia|consulta DGT|doctrina'

Profesional: 'Busca resoluciones del TEAC sobre deducibilidad
             de gastos de representación en el Impuesto de Sociedades'

Copilot (RAG legal_intelligence):
  1. Detecta intent: legal_search
  2. Extrae entidades: TEAC + gastos representación + IS
  3. Query Qdrant: embedding + filtros órgano + tema
  4. Recupera top 5 resoluciones
  5. Genera respuesta con citas fundamentadas
  6. Ofrece: [Insertar en expediente] [Ver texto completo] [Buscar similares]
4.3 Flujo de Inserción en Expediente
Cuando el profesional encuentra una resolución relevante, puede insertarla directamente en su expediente con formato de cita profesional:
Botón: [Insertar como argumento en expediente]

Genera automáticamente:

  'Según establece la Consulta Vinculante V0123-24
   de la Dirección General de Tributos, de fecha
   15 de marzo de 2024: [ratio decidendi extraída].'

  Referencia: DGT V0123-24 (15/03/2024)
  Enlace: https://petete.tributos.hacienda.gob.es/...

Formatos disponibles:
  - Cita formal (para escritos judiciales/administrativos)
  - Cita resumida (para informes internos)
  - Referencia bibliográfica (para artículos/publicaciones)
  - Nota al pie (para documentos académicos)
4.4 Interfaz de Resultados
Cada resultado de búsqueda muestra una tarjeta con la información esencial:
Elemento	Contenido	Acción
Cabecera	Tipo + Órgano + Referencia + Fecha	Click: abre texto completo
Badge Estado	Vigente (verde) / Derogada (rojo) / Superada (amarillo)	Tooltip: detalle
Resumen IA	Abstract 3-5 líneas generado	Copiar / Insertar en expediente
Ratio Decidendi	Doctrina principal (destacada en azul)	Copiar como argumento
Temas	Tags clicables por tema	Click: filtra por tema
Red de Citas	Mini-grafo: cita a / citada por	Click: navega a relacionadas
Relevancia	Score 0-100% + indicador visual	Ordenar por relevancia
Acciones	Insertar | Guardar | Compartir | PDF	Ejecución directa
 
5. Sistema de Alertas Inteligentes
El sistema monitoriza cambios en la base de conocimiento jurídico que puedan afectar a expedientes activos del profesional.
5.1 Tipos de Alertas
Tipo Alerta	Trigger	Canal	Urgencia
Resolución anulada	Una resolución citada en expediente activo es anulada	Email + Push + In-app	CRÍTICA
Cambio de criterio	Nueva resolución del mismo órgano contradice una citada	Email + In-app	ALTA
Nueva doctrina relevante	Resolución indexada coincide con temas de expedientes activos	In-app + Digest semanal	MEDIA
Normativa modificada	Ley/reglamento citado en expedientes sufre modificación	Email + Push	ALTA
Plazo procesal	Resolución con plazo de recurso próximo a vencer	Email + SMS + Push	CRÍTICA
Digest semanal	Resumen de novedades por área de práctica del profesional	Email	BAJA
5.2 Entidad: legal_alert_subscription
Campo	Tipo	Descripción
id	Serial	PRIMARY KEY
provider_id	INT	FK professional_provider.id
alert_type	VARCHAR(32)	tipo de alerta suscrita
filters	JSON	Filtros: jurisdicción, temas, órganos
channels	JSON	[email, push, sms, in_app]
frequency	VARCHAR(16)	realtime | daily | weekly
is_active	BOOLEAN	DEFAULT TRUE
last_triggered	DATETIME	Última vez que se activó
expediente_ids	JSON	Expedientes vinculados (opcional)
 
6. APIs REST
6.1 Endpoints de Búsqueda
Método	Endpoint	Descripción	Permisos
GET	/api/v1/legal/search	Búsqueda semántica por query	provider, admin
GET	/api/v1/legal/search/facets	Facetas disponibles para filtros	provider, admin
GET	/api/v1/legal/resolutions/{id}	Detalle completo de resolución	provider, admin
GET	/api/v1/legal/resolutions/{id}/similar	Resoluciones similares	provider, admin
GET	/api/v1/legal/resolutions/{id}/citations	Red de citas (cita a / citada por)	provider, admin
GET	/api/v1/legal/resolutions/{id}/pdf	PDF original de la resolución	provider, admin
POST	/api/v1/legal/search/advanced	Búsqueda avanzada con filtros JSON	provider, admin
6.2 Endpoints de Integración con Expediente
Método	Endpoint	Descripción	Permisos
POST	/api/v1/legal/cite	Generar cita formateada para insertar	provider
POST	/api/v1/legal/expediente/{id}/attach	Vincular resolución a expediente	provider
GET	/api/v1/legal/expediente/{id}/references	Resoluciones vinculadas a expediente	provider
DELETE	/api/v1/legal/expediente/{id}/references/{ref}	Desvincular resolución	provider
POST	/api/v1/legal/bookmark	Guardar resolución en favoritos	provider
GET	/api/v1/legal/bookmarks	Listar favoritos del profesional	provider
6.3 Endpoints de Alertas
Método	Endpoint	Descripción	Permisos
GET	/api/v1/legal/alerts	Listar suscripciones de alertas	provider
POST	/api/v1/legal/alerts	Crear suscripción de alerta	provider
PUT	/api/v1/legal/alerts/{id}	Actualizar suscripción	provider
DELETE	/api/v1/legal/alerts/{id}	Eliminar suscripción	provider
GET	/api/v1/legal/alerts/history	Historial de alertas disparadas	provider
GET	/api/v1/legal/digest/preview	Previsualización del digest semanal	provider
6.4 Endpoints de Administración
Método	Endpoint	Descripción	Permisos
GET	/api/v1/admin/legal/sources	Estado de las fuentes de ingesta	platform_admin
POST	/api/v1/admin/legal/sources/{id}/sync	Forzar sincronización de fuente	platform_admin
GET	/api/v1/admin/legal/stats	Estadísticas: total resoluciones, por fuente, freshness	platform_admin
GET	/api/v1/admin/legal/pipeline/status	Estado del pipeline NLP	platform_admin
POST	/api/v1/admin/legal/reindex	Reindexar toda la colección Qdrant	platform_admin
 
7. Flujos de Automatización ECA
7.1 ECA-LIH-001: Ingesta Programada de Fuentes
Componente	Configuración
Trigger	Cron configurable por fuente (diario/semanal/mensual)
Condición	source.is_active = TRUE AND source.last_error_count < 5
Acción 1	Ejecutar spider específico de la fuente
Acción 2	Deduplicar por full_text_hash (SHA-256)
Acción 3	Encolar nuevos documentos al pipeline NLP
Acción 4	Actualizar source.last_sync_at y source.total_documents
On Error	Incrementar error_count, notificar a platform_admin si count >= 3
7.2 ECA-LIH-002: Pipeline NLP Async
Componente	Configuración
Trigger	Entity Insert: legal_resolution WHERE status = 'pending_nlp'
Acción 1	Extraer texto con Apache Tika (si PDF)
Acción 2	Normalizar y segmentar con spaCy
Acción 3	Clasificar y resumir con Gemini 2.0 Flash (prompt 3.2)
Acción 4	Generar embeddings (text-embedding-3-large)
Acción 5	Insertar chunks en Qdrant (collection: legal_intelligence)
Acción 6	Construir relaciones de citas con resoluciones existentes
Acción 7	Actualizar status = 'indexed', actualizar vector_ids
On Error	status = 'nlp_failed', guardar error para review manual
7.3 ECA-LIH-003: Detector de Cambios Normativos
Componente	Configuración
Trigger	Entity Insert/Update: legal_resolution
Condición	resolution.type IN ('sentencia', 'resolucion') AND resolution.modifies IS NOT NULL
Acción 1	Identificar resoluciones afectadas en la base de datos
Acción 2	Actualizar status de la resolución afectada (anulada/superada)
Acción 3	Query expedientes que citan la resolución afectada
Acción 4	Para cada expediente: crear alerta con urgencia CRÍTICA/ALTA
Acción 5	Enviar notificación multicanal según preferencias del profesional
7.4 ECA-LIH-004: Digest Semanal Personalizado
Componente	Configuración
Trigger	Cron: lunes 07:00 UTC
Acción 1	Para cada profesional con digest_enabled: obtener áreas de práctica
Acción 2	Query resoluciones indexadas en últimos 7 días que coincidan con sus áreas
Acción 3	Generar resumen personalizado con Gemini (max 10 resoluciones)
Acción 4	Enviar email con template legal_weekly_digest (doc 136)
 
8. Configuración Multi-Tenant
La base de conocimiento legal es compartida a nivel de plataforma (no por tenant), ya que las resoluciones oficiales son públicas. Sin embargo, las personalizaciones (favoritos, alertas, citas en expedientes) son específicas de cada tenant/profesional.
8.1 Modelo de Compartición
Componente	Scope	Justificación
Resoluciones (base de datos)	Plataforma (global)	Fuentes públicas, no varían por tenant
Qdrant collection	Plataforma (legal_intelligence)	Un solo índice optimiza costes y frescura
Favoritos/Bookmarks	Profesional (user_id)	Personalización individual
Alertas/Suscripciones	Profesional (provider_id)	Cada profesional elige sus alertas
Citas en expedientes	Tenant (group_id)	Vinculadas al expediente del tenant
Digest semanal	Profesional (provider_id)	Personalizado por áreas de práctica
Estadísticas de uso	Tenant + Plataforma	Analytics por tenant y agregado
8.2 Límites por Plan
Funcionalidad	Plan Starter	Plan Pro	Plan Enterprise
Búsquedas/mes	50	Ilimitadas	Ilimitadas
Fuentes disponibles	BOE + CENDOJ	Todas las fuentes	Todas + fuentes custom
Alertas activas	3	20	Ilimitadas
Digest semanal	No	Sí	Sí + personalizable
Inserción en expediente	Manual (copy)	Botón directo	Botón + API
Búsqueda Copilot	No	Sí	Sí + prioridad
Red de citas	No	Sí	Sí + exportable
API programmatic	No	No	Sí
 
9. Permisos y Seguridad
9.1 Matriz de Permisos RBAC
Acción	Permission	Roles	Notas
Buscar resoluciones	search legal_resolution	provider, admin	Respeta límite del plan
Ver texto completo	view legal_resolution_full	provider (Pro+), admin	Starter solo ve abstract
Insertar en expediente	cite legal_resolution	provider (Pro+)	Genera cita formateada
Gestionar alertas	manage legal_alerts	provider	Respeta límite del plan
Ver red de citas	view legal_citation_graph	provider (Pro+), admin	Grafo interactivo
Admin fuentes	administer legal_sources	platform_admin	Solo administradores plataforma
Forzar reindex	reindex legal_collection	platform_admin	Operación pesada
9.2 Consideraciones de Secreto Profesional
El sistema garantiza que las búsquedas y favoritos de un profesional nunca son visibles para otros profesionales ni para la plataforma, cumpliendo con el secreto profesional del artículo 542.3 LOPJ y el Código Deontológico de la Abogacía:
•	Las queries de búsqueda se registran anonimizadas para analytics agregados, sin vincular a profesional concreto
•	Los favoritos y alertas se almacenan cifrados con clave derivada del tenant (AES-256-GCM)
•	Las citas vinculadas a expedientes heredan la seguridad del Buzón de Confianza (doc 88)
•	El Copilot en modo legal no registra el contenido de las consultas en logs accesibles
•	El digest semanal se genera y envía sin almacenar copia en la plataforma
 
10. Casos de Uso por Profesión
10.1 Abogado (Elena — Avatar Principal)
Escenario	Query al Sistema	Resultado Esperado
Preparar demanda de desahucio	Jurisprudencia TS sobre desahucio por impago post-COVID	Sentencias relevantes con doctrina sobre vulnerabilidad y plazos
Recurrir sanción administrativa	Resoluciones TEAC sobre sanciones en IVA por facturas falsas	Criterios del TEAC + sentencias AN que las confirman/anulan
Negociar acuerdo laboral	Sentencias TSJ Andalucía despido improcedente 2024-2025	Indemnizaciones concedidas y criterios de proporcionalidad
Asesorar sobre herencia	Consultas DGT sobre tributación de herencia en especie IRPF	Consultas vinculantes con doctrina aplicable + legislación
10.2 Asesoría Fiscal
Escenario	Query al Sistema	Resultado Esperado
Declaración IRPF compleja	Tributación de stock options en IRPF consultas DGT	Consultas vinculantes con criterio actualizado
Recurso frente a liquidación	TEAC deducibilidad gastos vehículo autónomo IS	Resoluciones TEAC + sentencias que fijan doctrina
Planificación fiscal sociedades	Consultas DGT régimen especial pymes IS 2024	Consultas recientes + cambios normativos
IVA operaciones inmobiliarias	DGT IVA segunda entrega inmueble renuncia exención	Consultas vinculantes con requisitos y límites
10.3 Gestoría Administrativa
Escenario	Query al Sistema	Resultado Esperado
Justificación de subvención	IGAE criterios justificación subvenciones gastos elegibles	Informes IGAE + circulares con criterios vigentes
Auditoría de cuentas anuales	BOICAC consultas sobre deterioro de créditos comerciales	Consultas contables con tratamiento correcto
Constitución de sociedad	DGRN resoluciones sobre denominación social y objeto social	Criterios registrales actualizados
Protección de datos	AEPD resoluciones sanción videovigilancia RGPD	Resoluciones AEPD con criterios y cuantías
 
11. Configuración de Qdrant
11.1 Colección: legal_intelligence
Parámetro	Valor	Justificación
vector_size	3072	text-embedding-3-large (OpenAI)
distance	Cosine	Estándar para búsqueda semántica textual
on_disk	true	Volumen alto de documentos, optimiza RAM
hnsw_m	32	Balance precision/velocidad para corpus legal grande
ef_construct	200	Alta precisión en construcción del índice
payload_indexes	source_id, jurisdiction, resolution_type, date_issued, issuing_body, topics	Filtros principales en queries
11.2 Metadatos por Vector (Payload)
Campo Payload	Tipo	Uso en Filtros
resolution_id	integer	Vincular con MariaDB
source_id	keyword	Filtro por fuente: cendoj, boe, dgt...
jurisdiction	keyword	civil, penal, laboral, fiscal...
resolution_type	keyword	sentencia, consulta, resolucion...
issuing_body	keyword	TS, DGT, TEAC, AN...
date_issued	datetime	Filtro por rango de fechas
topics	keyword[]	Filtro por múltiples temas
chunk_index	integer	Posición del chunk dentro del documento
section_type	keyword	antecedentes, fundamentos, fallo
11.3 Estimación de Volumen
Fuente	Documentos Estimados (año 1)	Chunks Estimados	Espacio Qdrant
CENDOJ	~200.000 sentencias	~2.000.000	~24 GB
BOE (legislación vigente)	~15.000 normas	~150.000	~1.8 GB
DGT (consultas)	~5.000/año + histórico	~100.000	~1.2 GB
TEAC	~2.000/año + histórico	~40.000	~0.5 GB
Otras fuentes	~5.000 documentos	~50.000	~0.6 GB
TOTAL AÑO 1	~227.000 documentos	~2.340.000 chunks	~28 GB
 
12. SEO y Visibilidad
Las páginas públicas de resoluciones (abstracts y metadatos, no texto completo) generan tráfico orgánico cualificado hacia ServiciosConecta.
12.1 Schema.org para Resoluciones
{
  "@context": "https://schema.org",
  "@type": "LegalForceDocument",
  "name": "Consulta Vinculante V0123-24",
  "description": "Tributación de criptomonedas en IRPF...",
  "legislationIdentifier": "V0123-24",
  "legislationDate": "2024-03-15",
  "legislationLegalForce": "InForce",
  "legislationJurisdiction": {
    "@type": "AdministrativeArea",
    "name": "España"
  },
  "author": {
    "@type": "GovernmentOrganization",
    "name": "Dirección General de Tributos"
  }
}
12.2 Estrategia SEO/GEO
•	Páginas de resumen públicas (abstract + metadatos) indexables por Google
•	Texto completo solo accesible para usuarios autenticados (lead generation)
•	Answer Capsules optimizadas para featured snippets de consultas legales
•	URLs semánticas: /legal/dgt/V0123-24-tributacion-criptomonedas-irpf
•	Sitemap XML específico para resoluciones con lastmod actualizado
 
13. Roadmap de Implementación
Sprint	Timeline	Entregables	Horas Est.
Sprint 1	Semana 1-3	Modelo de datos. Spiders BOE + CENDOJ. Pipeline NLP básico (Tika + spaCy). Almacenamiento MariaDB.	80-100
Sprint 2	Semana 4-6	Clasificación IA (Gemini). Generación embeddings. Indexación Qdrant. Búsqueda semántica básica.	70-90
Sprint 3	Semana 7-9	UI de búsqueda: resultados, filtros, facetas. Spiders DGT + TEAC. Vista detalle de resolución.	80-100
Sprint 4	Semana 10-12	Inserción en expediente. Favoritos. Red de citas. Integración con Copilot (doc 93).	70-90
Sprint 5	Semana 13-15	Sistema de alertas. Digest semanal. Spiders restantes (IGAE, BOICAC, AEPD, DGRN).	60-80
Sprint 6	Semana 16-18	SEO/GEO páginas públicas. Analytics de uso. QA y testing. Go-live.	50-70

Total estimado: 410-530 horas (18 semanas / 4.5 meses)
Coste estimado: 18.450-23.850€ (a 45€/hora equipo EDI)
 
14. Consideraciones Legales y de Compliance
14.1 Reutilización de Información Pública
La ingesta de datos se ampara en la Ley 37/2007 de Reutilización de Información del Sector Público y el Real Decreto 1495/2011. Todas las fuentes utilizadas publican datos bajo licencias de reutilización. No se accede a información restringida ni se vulneran términos de servicio.
•	CENDOJ: Publicación oficial de jurisprudencia, acceso público por mandato legal (Ley 18/2011 EJIS)
•	BOE: Datos abiertos bajo licencia Creative Commons Reconocimiento 4.0 (CC BY 4.0)
•	DGT: Consultas vinculantes publicadas por obligación legal (art. 89 LGT)
•	TEAC: Resoluciones publicadas conforme al art. 239.8 LGT
•	BOJA: Publicación oficial bajo normativa andaluza de transparencia
14.2 RGPD y Datos Personales
Las resoluciones judiciales publicadas ya están anonimizadas por CENDOJ conforme al Reglamento 1/2023 del CGPJ. El sistema no almacena datos personales de las partes en los procesos. Las únicas PII son las del profesional usuario (gestionadas por el core RGPD de la plataforma, doc 115).
14.3 Secreto Profesional
El diseño garantiza que las búsquedas, favoritos y patrones de consulta de un profesional nunca son accesibles por otros usuarios, la plataforma, ni terceros. Esto es crítico para cumplir con las obligaciones deontológicas de abogados (art. 542.3 LOPJ), asesores fiscales y gestores administrativos.
14.4 Disclaimer Obligatorio
El sistema incluye un disclaimer permanente en toda la interfaz: "La información proporcionada tiene carácter meramente informativo. No constituye asesoramiento legal, fiscal ni profesional. Consulte siempre las fuentes oficiales y verifique la vigencia de las resoluciones antes de utilizarlas en procedimientos formales."
 
15. Impacto en el Ecosistema Jaraba
15.1 Valor para el Avatar Elena
Métrica	Sin Legal Intelligence Hub	Con Legal Intelligence Hub
Tiempo de búsqueda de precedentes	2-3 horas por caso	5-15 minutos (reducción 90%)
Coste herramientas jurídicas	3.000-8.000€/año	Incluido en plan Pro (ahorro 100%)
Calidad de argumentación	Limitada a lo que encuentra manualmente	Exhaustiva con red de citas y similares
Riesgo de doctrina obsoleta	Alto (no detecta cambios)	Bajo (alertas automáticas)
Productividad en escritos	30-60 min redactar cita manual	2 min con inserción automática
15.2 Impacto en Revenue
•	Justificación del plan Pro vs Starter: feature killer para upgrade (conversión estimada +15%)
•	Reducción de churn: profesionales legales con alertas activas tienen 3x mayor retención
•	Lead generation: páginas públicas de resoluciones atraen tráfico orgánico cualificado
•	Diferenciación competitiva: ningún SaaS de gestión de despachos incluye inteligencia jurídica nativa
•	Cross-sell: profesionales de ServiciosConecta adoptan más fácilmente el vertical de Emprendimiento para asesorar clientes
15.3 Dependencias con Documentos Existentes
Documento	Relación	Tipo
82_Services_Core	Base arquitectónica del vertical	Dependencia
88_Buzon_Confianza	Almacenamiento seguro de citas en expedientes	Integración
90_Portal_Cliente_Documental	Visualización de escritos con citas	Integración
91_AI_Triaje_Casos	Enriquecimiento del triaje con normativa relevante	Extensión
93_Copilot_Servicios	Nuevo skill de búsqueda jurídica en el Copilot	Extensión
96_Sistema_Facturacion	Referencia a normativa fiscal en facturas	Consulta
114_Knowledge_Base	Infraestructura RAG base	Dependencia
130_Tenant_Knowledge_Training	Integración con sistema de entrenamiento IA	Complemento
16. Conclusión
El Legal Intelligence Hub cierra el mayor GAP funcional identificado en ServiciosConecta para el segmento legal/financiero. Mientras las herramientas existentes (Copilot, Buzón, Presupuestador, Firma Digital) resuelven la operativa diaria del despacho, este módulo resuelve el problema nuclear de los profesionales del derecho: acceder rápidamente a la información jurídica actualizada que fundamenta su trabajo.
La combinación de fuentes públicas oficiales + procesamiento NLP + búsqueda semántica + integración nativa con el expediente digital crea una propuesta de valor que no existe en el mercado español de SaaS para despachos profesionales. Productos como Aranzadi o Lefebvre ofrecen la base de datos, pero no la integración con la gestión del despacho. Productos como Sage Despachos o A3 gestionan el despacho, pero no incluyen inteligencia jurídica.
ServiciosConecta con Legal Intelligence Hub sería el primer SaaS español que une ambos mundos en una sola plataforma, con IA nativa y a un coste accesible para el despacho rural de Cabra que Elena representa.

Control de Versiones
Versión	Fecha	Autor	Cambios
1.0	Febrero 2026	Claude (Anthropic) / Pepe Jaraba	Especificación técnica inicial completa

——— Fin del Documento ———
