
LEGAL KNOWLEDGE MODULE
Base de Conocimiento Normativa BOE Actualizada
JARABA IMPACT PLATFORM
Documento Técnico de Implementación
Versión:	1.0
Fecha:	Febrero 2026
Estado:	Especificación Técnica
Código:	178_Platform_Legal_Knowledge_Module
Dependencias:	44_AI_Business_Copilot, 129_AI_Skills_System, 130_Knowledge_Training, Qdrant, Claude API, API BOE
 
1. Resumen Ejecutivo	1
1.1 Propuesta de Valor	1
1.2 Ámbito Normativo	1
1.3 Verticales Beneficiadas	1
2. Arquitectura del Sistema	1
2.1 Fuentes de Datos	1
2.2 Stack Tecnológico	1
2.3 Pipeline de Actualización	1
3. Modelo de Datos	1
3.1 Entidad: legal_norm	1
3.2 Entidad: legal_chunk	1
3.3 Entidad: legal_query_log	1
3.4 Entidad: norm_change_alert	1
4. Integración con API BOE	1
4.1 Endpoints Utilizados	1
4.2 Configuración del Scraper	1
4.3 Proceso de Ingestion	1
5. Pipeline RAG para Consultas Normativas	1
5.1 Flujo de Consulta	1
5.2 Prompt Template	1
5.3 Sistema de Citas	1
5.4 Niveles de Disclaimer	1
6. Sistema de Alertas Normativas	1
6.1 Detección de Cambios	1
6.2 Relevancia por Tenant	1
6.3 Canales de Notificación	1
6.4 Flujo ECA: Alerta de Cambio Normativo	1
7. Estrategia de Monetización	1
7.1 Tiers de Servicio	1
7.2 Features por Tier	1
7.2.1 Tier Básico	1
7.2.2 Tier Premium	1
7.2.3 Tier Enterprise+	1
7.3 ROI Esperado	1
8. APIs REST	1
8.1 Request/Response: Legal Query	1
9. Métricas y Analytics	1
9.1 KPIs del Módulo	1
9.2 Dashboard Admin	1
10. Roadmap de Implementación	1
10.1 Dependencias	1
10.2 Riesgos y Mitigaciones	1

 
1. Resumen Ejecutivo
El Legal Knowledge Module integra la API del Boletín Oficial del Estado (BOE) para proporcionar una base de conocimiento normativa actualizada automáticamente. Permite a los AI Copilots del ecosistema fundamentar respuestas sobre temas tributarios y laborales en normativa oficial vigente, con citas verificables y disclaimers apropiados.
1.1 Propuesta de Valor
Sin Módulo	Con Legal Knowledge Module
Respuestas genéricas sobre fiscalidad	Respuestas fundamentadas en normativa BOE vigente
Riesgo de información desactualizada	Actualización automática diaria desde BOE
Sin citas verificables	Citas con enlace directo al BOE oficial
Disclaimers manuales	Disclaimers automáticos según contexto
Base de conocimiento estática	Base dinámica con alertas de cambios normativos

1.2 Ámbito Normativo
•	Derecho Tributario: IRPF, IVA, Sociedades, Autónomos, Ley General Tributaria
•	Legislación Laboral: Estatuto de los Trabajadores, Seguridad Social, ERTE
•	Normativa de Autónomos: RETA, bonificaciones, deducciones
•	Subvenciones y Ayudas: Programas activos de financiación
•	Procedimiento Administrativo: LPAC, recursos, plazos
1.3 Verticales Beneficiadas
Vertical	Casos de Uso	Prioridad
Emprendimiento	Fiscalidad autónomos, obligaciones laborales, ayudas	ALTA
Empleabilidad	Contratos, despidos, prestaciones, formación bonificada	ALTA
ServiciosConecta	Normativa profesional, facturación, compliance	MEDIA
AgroConecta	PAC, ayudas agrarias, normativa agroalimentaria	MEDIA
ComercioConecta	IVA comercio, normativa retail, protección consumidor	BAJA
 
2. Arquitectura del Sistema
2.1 Fuentes de Datos
Fuente	URL/API	Contenido
API BOE Legislación	boe.es/datosabiertos/api/legislacion-consolidada	Normas consolidadas vigentes
API BOE Sumario	boe.es/datosabiertos/api/boe/sumario/{fecha}	Publicaciones diarias
Códigos Electrónicos	boe.es/biblioteca_juridica/	Compilaciones temáticas actualizadas
BOE Alertas	Mi BOE	Notificaciones de cambios normativos

2.2 Stack Tecnológico
Componente	Tecnología	Función
Scraper/Ingestion	PHP + Guzzle	Consumo API BOE
Document Parser	XML Parser + DOMDocument	Extracción de texto normativo
Chunking	LangChain TextSplitter (PHP)	División en fragmentos consultables
Embeddings	text-embedding-3-small	Vectorización de normativa
Vector Store	Qdrant	Almacenamiento y búsqueda semántica
LLM	Claude 3.5 Sonnet	Generación de respuestas
Cache	Redis	Consultas frecuentes
Scheduler	Drupal Queue API + Cron	Actualización diaria
Admin UI	React + Tailwind	Dashboard de normativa

2.3 Pipeline de Actualización
El sistema mantiene la base de conocimiento sincronizada con el BOE mediante un pipeline automatizado:
1.	Detección de cambios: Consulta diaria a API BOE con parámetro 'from' (última actualización)
2.	Descarga selectiva: Solo normas nuevas o modificadas
3.	Parsing XML: Extracción de texto, metadatos y estructura
4.	Chunking inteligente: División por artículos/secciones, ~500 tokens con overlap
5.	Generación de embeddings: Vectorización con OpenAI
6.	Indexación Qdrant: Almacenamiento con metadata rica
7.	Invalidación de caché: Limpiar respuestas afectadas
8.	Notificación: Alertar a tenants afectados de cambios relevantes
 
3. Modelo de Datos
3.1 Entidad: legal_norm
Representa una norma jurídica indexada del BOE.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
boe_id	VARCHAR(32)	Identificador BOE	UNIQUE, NOT NULL, INDEX
eli	VARCHAR(255)	European Legislation Identifier	UNIQUE, NULLABLE
title	VARCHAR(500)	Título de la norma	NOT NULL
norm_type	VARCHAR(64)	Tipo (ley, real decreto, orden...)	NOT NULL, INDEX
department	VARCHAR(255)	Departamento emisor	NOT NULL
publication_date	DATE	Fecha de publicación	NOT NULL, INDEX
effective_date	DATE	Fecha de entrada en vigor	NULLABLE
consolidation_state	VARCHAR(32)	Estado de consolidación	ENUM: vigente|derogada|parcial
subject_areas	JSON	Materias (tributario, laboral...)	NOT NULL
ambits	JSON	Ámbitos de aplicación	NULLABLE
boe_url	VARCHAR(500)	URL oficial en BOE	NOT NULL
full_text	LONGTEXT	Texto completo (cifrado)	NOT NULL
chunks_count	INT	Número de chunks indexados	DEFAULT 0
last_boe_update	TIMESTAMP	Última actualización en BOE	NOT NULL
last_indexed	TIMESTAMP	Última indexación local	NOT NULL
created_at	TIMESTAMP	Fecha de creación	NOT NULL
updated_at	TIMESTAMP	Fecha de actualización	NOT NULL

3.2 Entidad: legal_chunk
Fragmento de norma indexado en Qdrant para búsqueda semántica.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
norm_id	INT	Norma padre	FK legal_norm.id, NOT NULL, INDEX
chunk_index	INT	Índice del chunk en la norma	NOT NULL
article_ref	VARCHAR(64)	Referencia de artículo (Art. 15.2)	NULLABLE
section_path	VARCHAR(255)	Ruta jerárquica (Título I > Cap 2 > Art 15)	NULLABLE
content	TEXT	Texto del chunk	NOT NULL
content_hash	VARCHAR(64)	Hash SHA256 del contenido	NOT NULL, INDEX
tokens_count	INT	Número de tokens	NOT NULL
qdrant_point_id	VARCHAR(64)	ID del punto en Qdrant	NOT NULL, INDEX
created_at	TIMESTAMP	Fecha de creación	NOT NULL

3.3 Entidad: legal_query_log
Registro de consultas para analytics y mejora continua.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
tenant_id	INT	Tenant que consulta	FK tenant.id, NOT NULL, INDEX
user_id	INT	Usuario que consulta	FK users.uid, NULLABLE
copilot_source	VARCHAR(32)	Copilot de origen	ENUM: business|empleabilidad|servicios
query_text	TEXT	Consulta del usuario	NOT NULL
query_embedding	VARCHAR(64)	ID embedding en caché	NULLABLE
chunks_retrieved	JSON	IDs de chunks recuperados	NOT NULL
response_text	TEXT	Respuesta generada	NOT NULL
citations	JSON	Citas incluidas en respuesta	NOT NULL
disclaimer_level	VARCHAR(16)	Nivel de disclaimer	ENUM: standard|enhanced|critical
feedback	VARCHAR(16)	Feedback del usuario	NULLABLE, ENUM: helpful|not_helpful
latency_ms	INT	Latencia total	NOT NULL
created_at	TIMESTAMP	Fecha de consulta	NOT NULL

3.4 Entidad: norm_change_alert
Alertas de cambios normativos relevantes para tenants.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
norm_id	INT	Norma afectada	FK legal_norm.id, NOT NULL
change_type	VARCHAR(32)	Tipo de cambio	ENUM: new|modified|derogated
change_summary	TEXT	Resumen del cambio (generado por IA)	NOT NULL
affected_topics	JSON	Temas afectados	NOT NULL
severity	VARCHAR(16)	Severidad	ENUM: info|important|critical
notified_tenants	JSON	Tenants notificados	DEFAULT '[]'
created_at	TIMESTAMP	Fecha de detección	NOT NULL
 
4. Integración con API BOE
4.1 Endpoints Utilizados
Endpoint	Método	Uso
/legislacion-consolidada	GET	Buscar normas por fecha de actualización
/legislacion-consolidada/id/{id}/metadatos	GET	Obtener metadatos de norma
/legislacion-consolidada/id/{id}/texto	GET	Obtener texto consolidado completo
/legislacion-consolidada/id/{id}/texto/indice	GET	Obtener índice de bloques
/boe/sumario/{fecha}	GET	Obtener sumario del día
/datos-auxiliares/materias	GET	Catálogo de materias
/datos-auxiliares/departamentos	GET	Catálogo de departamentos

4.2 Configuración del Scraper
// config/legal_knowledge.php
return [
  'boe_api_base' => 'https://www.boe.es/datosabiertos/api',
  'update_frequency' => 'daily', // daily, hourly
  'subject_areas' => [
    'tributario' => ['impuestos', 'tributos', 'fiscal'],
    'laboral' => ['trabajo', 'empleo', 'seguridad social'],
    'autonomos' => ['autónomos', 'RETA', 'trabajador independiente'],
    'subvenciones' => ['ayudas', 'subvenciones', 'financiación'],
  ],
  'norm_types' => ['ley', 'real decreto', 'orden', 'resolución'],
  'chunk_size' => 500, // tokens
  'chunk_overlap' => 50, // tokens
  'embedding_model' => 'text-embedding-3-small',
  'qdrant_collection' => 'legal_knowledge',
];
4.3 Proceso de Ingestion
// LegalKnowledgeIngestionService.php
public function ingestUpdates(): void {
  // 1. Obtener normas actualizadas desde última sincronización
  $lastSync = $this->getLastSyncDate();
  $updatedNorms = $this->boeClient->getUpdatedNorms($lastSync);
  
  foreach ($updatedNorms as $normMeta) {
    // 2. Filtrar por materias relevantes
    if (!$this->isRelevantSubject($normMeta['materias'])) continue;
    
    // 3. Descargar texto completo
    $fullText = $this->boeClient->getNormText($normMeta['id']);
    
    // 4. Crear/actualizar entidad legal_norm
    $norm = $this->upsertNorm($normMeta, $fullText);
    
    // 5. Generar chunks y embeddings
    $this->processChunks($norm, $fullText);
    
    // 6. Detectar y notificar cambios relevantes
    $this->detectAndNotifyChanges($norm);
  }
}
 
5. Pipeline RAG para Consultas Normativas
5.1 Flujo de Consulta
9.	Query Preprocessing: Extraer entidades (tipo impuesto, situación, fecha)
10.	Filter Construction: Construir filtros Qdrant (materia, ámbito, vigencia)
11.	Semantic Search: Buscar chunks relevantes (top-k = 5, threshold = 0.7)
12.	Context Assembly: Ordenar chunks por relevancia y jerarquía
13.	Prompt Construction: Ensamblar prompt con strict grounding legal
14.	LLM Inference: Generar respuesta con citas obligatorias
15.	Citation Formatting: Formatear citas con enlaces al BOE
16.	Disclaimer Injection: Añadir disclaimer según contexto
5.2 Prompt Template
SYSTEM: Eres un asistente especializado en normativa española. Tu rol es proporcionar
información objetiva basada EXCLUSIVAMENTE en la legislación vigente.

REGLAS CRÍTICAS (STRICT LEGAL GROUNDING):
1. SOLO puedes usar información de los DOCUMENTOS NORMATIVOS proporcionados
2. SIEMPRE cita la norma específica: 'Según el Art. X de la Ley Y...'
3. Incluye SIEMPRE el enlace al BOE oficial
4. Si la información NO está en los documentos, di: 'No encuentro normativa
   específica sobre esto. Te recomiendo consultar con un profesional.'
5. NUNCA inventes artículos, fechas o importes
6. SIEMPRE incluye el disclaimer al final

DOCUMENTOS NORMATIVOS RECUPERADOS:
{chunks_with_metadata}

CONSULTA DEL USUARIO:
{user_query}

FORMATO DE RESPUESTA:
- Respuesta clara y estructurada
- Citas en formato: [Art. X, Ley Y] (enlace BOE)
- Disclaimer obligatorio al final
5.3 Sistema de Citas
Cada respuesta incluye citas verificables con enlaces al BOE:
// Ejemplo de respuesta formateada
{
  "content": "Los autónomos pueden deducir los gastos de suministros del hogar...",
  "citations": [
    {
      "text": "Art. 30.2.5ª LIRPF",
      "norm_title": "Ley 35/2006, de 28 de noviembre, del IRPF",
      "boe_url": "https://www.boe.es/eli/es/l/2006/11/28/35/con",
      "article": "30.2.5ª"
    }
  ],
  "disclaimer": "Esta información es orientativa. Consulte con un asesor..."
}
5.4 Niveles de Disclaimer
Nivel	Trigger	Texto
standard	Consulta general	Esta información es orientativa y no constituye asesoramiento profesional.
enhanced	Consulta sobre situación específica	Cada caso es único. Recomendamos consultar con un asesor fiscal/laboral.
critical	Consulta con implicaciones legales graves	IMPORTANTE: Esta información no sustituye el asesoramiento profesional. Consulte con un abogado/asesor antes de actuar.
 
6. Sistema de Alertas Normativas
6.1 Detección de Cambios
El sistema detecta automáticamente cambios normativos relevantes:
•	Nueva norma: Publicación que afecta a materias monitorizadas
•	Modificación: Actualización de norma existente en base de conocimiento
•	Derogación: Norma que deja de estar vigente
6.2 Relevancia por Tenant
Cada tenant recibe alertas personalizadas según su perfil:
Perfil Tenant	Alertas Relevantes
Autónomo individual	IRPF autónomos, RETA, bonificaciones
SL/SLU	Impuesto Sociedades, IVA, obligaciones mercantiles
Empleador	Estatuto Trabajadores, convenios, cotizaciones
Agrario	PAC, ayudas agrarias, normativa agroalimentaria
6.3 Canales de Notificación
•	Email: Resumen semanal de cambios + alertas críticas inmediatas
•	Dashboard: Badge de notificación en Admin Center
•	Copilot: Mención proactiva en próxima conversación relevante
6.4 Flujo ECA: Alerta de Cambio Normativo
Componente	Configuración
Event	legal_norm:updated OR legal_norm:created
Condition	norm.subject_areas INTERSECTS tenant.monitored_subjects
Action	Crear norm_change_alert, enviar notificación según severidad
 
7. Estrategia de Monetización
7.1 Tiers de Servicio
Tier	Características	Precio Sugerido
BÁSICO (Incluido)	Orientación general, deep linking a AEAT/TGSS, disclaimers básicos	€0
PREMIUM	Base BOE actualizada, citas verificables, alertas normativas, calculadoras fiscales	+€29/mes
ENTERPRISE+	Marketplace asesores, revisión humana, API integración, histórico auditable	+€99/mes

7.2 Features por Tier
7.2.1 Tier Básico
•	Respuestas basadas en conocimiento general del LLM
•	Enlaces a herramientas oficiales (AEAT, TGSS, SEPE)
•	Disclaimer estándar
•	Sin acceso a normativa actualizada
7.2.2 Tier Premium
•	Base de conocimiento BOE actualizada diariamente
•	Citas con enlaces directos al BOE oficial
•	Alertas de cambios normativos relevantes
•	Calculadoras fiscales integradas (IRPF estimación, IVA)
•	Histórico de consultas normativas
•	Soporte prioritario
7.2.3 Tier Enterprise+
•	Todas las features Premium
•	Marketplace de asesores fiscales/laborales verificados
•	Human-in-the-Loop para consultas complejas
•	API para integración con software de contabilidad
•	Exportación de informes normativos
•	Auditoría completa de consultas (LOPD, ISO 27001)
•	SLA garantizado
7.3 ROI Esperado
•	Conversión a Premium: +30% de tenants en Emprendimiento
•	Reducción churn: -15% por valor añadido
•	Upsell Enterprise+: €50-150/mes adicionales por tenant
•	Diferenciación: Único SaaS con normativa BOE actualizada automáticamente
 
8. APIs REST
Método	Endpoint	Descripción
POST	/api/v1/legal/query	Consulta normativa con RAG
GET	/api/v1/legal/norms	Listar normas indexadas (admin)
GET	/api/v1/legal/norms/{id}	Detalle de norma
GET	/api/v1/legal/alerts	Alertas de cambios para tenant
POST	/api/v1/legal/alerts/{id}/acknowledge	Marcar alerta como leída
GET	/api/v1/legal/history	Historial de consultas del tenant
POST	/api/v1/legal/calculators/irpf	Calculadora IRPF autónomos
POST	/api/v1/legal/calculators/iva	Calculadora IVA
GET	/api/v1/legal/stats	Estadísticas de uso (admin)

8.1 Request/Response: Legal Query
// POST /api/v1/legal/query
// Request
{
  "query": "¿Puedo deducir los gastos de la línea de internet de casa?",
  "context": {
    "tenant_type": "autonomo",
    "activity": "programador",
    "epigraph": "831"
  }
}

// Response
{
  "answer": "Sí, los autónomos que trabajan desde casa pueden deducir...",
  "citations": [
    {
      "article": "Art. 30.2.5ª LIRPF",
      "text": "...gastos de suministros de la vivienda...",
      "boe_url": "https://www.boe.es/eli/es/l/2006/11/28/35/con"
    }
  ],
  "disclaimer": "Esta información es orientativa...",
  "related_topics": ["Gastos deducibles autónomos", "Afectación vivienda"],
  "confidence": 0.92
}
 
9. Métricas y Analytics
9.1 KPIs del Módulo
Métrica	Objetivo	Descripción
Actualización BOE	<24h	Tiempo entre publicación BOE e indexación
Precisión de citas	>95%	Citas verificables y correctas
Cobertura normativa	>90%	Consultas respondidas con normativa
Satisfacción	CSAT >4.0	Feedback positivo en consultas
Conversión Premium	>25%	Tenants que upgraden por esta feature
Alertas útiles	>80%	Alertas marcadas como relevantes
9.2 Dashboard Admin
•	Normas indexadas por materia
•	Consultas por día/semana/mes
•	Temas más consultados
•	Gaps de cobertura (consultas sin respuesta)
•	Últimas actualizaciones BOE procesadas
•	Alertas pendientes por tenant
 
10. Roadmap de Implementación
Sprint	Timeline	Entregables	Horas Est.
Sprint 1	Sem 1-2	Integración API BOE, scraper, parser XML	80-100
Sprint 2	Sem 3-4	Chunking, embeddings, indexación Qdrant	60-80
Sprint 3	Sem 5-6	Pipeline RAG, prompt engineering, citas	80-100
Sprint 4	Sem 7-8	Sistema de alertas, notificaciones	60-80
Sprint 5	Sem 9-10	Calculadoras fiscales, dashboard admin	60-80
Sprint 6	Sem 11-12	Integración con Copilots, QA, Go-live	60-80
TOTAL	12 semanas	Legal Knowledge Module completo	400-520
10.1 Dependencias
•	Qdrant configurado y operativo
•	Claude API con cuota suficiente
•	AI Skills System (doc 129) para skills legales
•	Knowledge Training (doc 130) para FAQs interpretativas
10.2 Riesgos y Mitigaciones
Riesgo	Impacto	Mitigación
API BOE no disponible	Alto	Caché agresivo, fallback a última versión
Cambios en formato XML BOE	Medio	Parser flexible, alertas de parsing errors
Respuestas incorrectas	Alto	Strict grounding, validación de citas, disclaimers
Sobrecarga de consultas	Medio	Rate limiting, caché de respuestas, queue
Normativa compleja mal interpretada	Alto	Human-in-the-loop para casos críticos

— Fin del Documento —
