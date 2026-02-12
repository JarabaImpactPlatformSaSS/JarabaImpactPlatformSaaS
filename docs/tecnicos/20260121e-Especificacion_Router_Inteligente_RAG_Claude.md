
ESPECIFICACIÓN TÉCNICA
Router Inteligente Multi-Proveedor
y Sistema RAG para Modos Expertos

Copiloto de Emprendimiento Andalucía +ei
Versión 2.1

Versión del Documento:	1.0
Fecha:	Enero 2026
Autor:	Jaraba Impact Platform
Estado:	Borrador Técnico
 
ÍNDICE
1. Resumen Ejecutivo
2. Arquitectura del Router Inteligente
3. Sistema de Detección de Modos
4. Configuración Multi-Proveedor
5. Sistema RAG para Modos Expertos
6. Implementación del Router (Código)
7. Esquema de Base de Datos Normativa
8. API de Integración
9. Monitorización y Métricas
10. Plan de Implementación
 
1. RESUMEN EJECUTIVO
Este documento especifica la arquitectura técnica del Router Inteligente para el Copiloto de Emprendimiento Andalucía +ei, incluyendo el sistema de enrutamiento a múltiples proveedores de IA según el modo detectado y el sistema RAG (Retrieval-Augmented Generation) para los modos de expertos normativos.
1.1 Objetivos del Sistema
•	Optimizar costes de API dirigiendo cada consulta al modelo más apropiado
•	Maximizar calidad de respuesta según las características de cada modo
•	Garantizar precisión normativa en modos Tributario y Seguridad Social
•	Proporcionar fallback automático ante fallos de proveedor
•	Reducir costes de API en aproximadamente 55% vs. usar modelo premium para todo
1.2 Estimación de Ahorro
Escenario	Coste/mes	Ahorro
Solo Claude Sonnet (100% llamadas)	~100€	-
Router Inteligente Multi-Proveedor	~45€	55%
 
2. ARQUITECTURA DEL ROUTER INTELIGENTE
2.1 Diagrama de Componentes
El sistema se compone de tres capas principales:
 
┌─────────────────────────────────────────────────────────────────────┐
│                    COPILOTO ANDALUCÍA +ei                           │
│                      (API Gateway)                                  │
└─────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    CAPA 1: DETECTOR DE MODO                         │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐     │
│  │ Clasificador    │  │ Analizador      │  │ Context         │     │
│  │ de Triggers     │  │ de Emociones    │  │ Enricher        │     │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘     │
└─────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    CAPA 2: ROUTER                                    │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐     │
│  │ Provider        │  │ Rate Limiter    │  │ Fallback        │     │
│  │ Selector        │  │ & Cache         │  │ Handler         │     │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘     │
└─────────────────────────────────────────────────────────────────────┘
                              │
        ┌─────────────────────┼─────────────────────┐
        ▼                     ▼                     ▼
┌───────────────┐    ┌───────────────┐    ┌───────────────┐
│  TIER PREMIUM │    │  TIER ESTÁNDAR│    │ TIER EXPERTOS │
│ Claude Sonnet │    │ Gemini Flash  │    │ + RAG System  │
├───────────────┤    ├───────────────┤    ├───────────────┤
│ 🩷 Coach      │    │ 🎯 Consultor  │    │ 🏛️ Tributario │
│ 🥊 Sparring   │    │               │    │ 🛡️ Seg.Social │
│ 💰 CFO        │    │               │    │               │
│ 😈 Abogado    │    │               │    │               │
└───────────────┘    └───────────────┘    └───────────────┘
 
2.2 Flujo de Procesamiento
1.	El usuario envía mensaje desde el Chat Widget
2.	El Clasificador de Triggers analiza el mensaje y detecta el modo
3.	El Analizador de Emociones identifica bloqueos emocionales (si los hay)
4.	El Context Enricher carga perfil del emprendedor (carril, fase, historial)
5.	El Provider Selector elige el proveedor óptimo según modo detectado
6.	Si modo es EXPERTO, el sistema RAG recupera documentos normativos relevantes
7.	Se construye el prompt con contexto + documentos + instrucciones de modo
8.	Se envía al proveedor seleccionado (con fallback automático si falla)
9.	La respuesta se parsea, se registran métricas y se envía al usuario
 
3. SISTEMA DE DETECCIÓN DE MODOS
3.1 Matriz de Triggers por Modo
Modo	Triggers (Palabras Clave)	Peso Base
🩷 COACH_EMOCIONAL	miedo, no puedo, agobio, bloqueo, impostor, vergüenza, culpa, fracaso, hundido	10
🎯 CONSULTOR_TACTICO	cómo hago, paso a paso, tutorial, herramienta, configurar, crear, montar	8
🥊 SPARRING_PARTNER	qué te parece, valídame, practica, simula, feedback, cliente, pitch	9
💰 CFO_SINTETICO	precio, cobrar, tarifa, margen, coste, rentable, euros, caro, barato	9
😈 ABOGADO_DIABLO	estoy seguro, todos quieren, es obvio, sin duda, funcionará, único	8
🏛️ TAX_EXPERT	hacienda, IVA, IRPF, modelo 303, factura, declaración, impuestos, AEAT	10
🛡️ SS_EXPERT	autónomo, cuota, RETA, tarifa plana, Seguridad Social, cotización, baja	10
3.2 Algoritmo de Detección
 
function detectMode(message, entrepreneurProfile) {
  const messageLower = message.toLowerCase();
  const scores = {};
  
  // 1. Calcular puntuación por triggers
  for (const trigger of TRIGGERS) {
    if (messageLower.includes(trigger.word)) {
      scores[trigger.mode] = (scores[trigger.mode] || 0) + trigger.weight;
    }
  }
  
  // 2. Aplicar modificadores de contexto
  if (entrepreneurProfile.carril === 'IMPULSO') {
    scores['COACH_EMOCIONAL'] = (scores['COACH_EMOCIONAL'] || 0) * 1.3;
  }
  
  // 3. Detectar emociones con análisis semántico
  const emotionScore = analyzeEmotion(message);
  if (emotionScore > 0.7) {
    scores['COACH_EMOCIONAL'] = (scores['COACH_EMOCIONAL'] || 0) + 15;
  }
  
  // 4. Seleccionar modo con mayor puntuación
  let maxMode = 'CONSULTOR_TACTICO'; // Default
  let maxScore = 0;
  
  for (const [mode, score] of Object.entries(scores)) {
    if (score > maxScore) {
      maxScore = score;
      maxMode = mode;
    }
  }
  
  // 5. Umbral mínimo para modos especiales
  if (maxScore < 5 && !['CONSULTOR_TACTICO'].includes(maxMode)) {
    return 'CONSULTOR_TACTICO';
  }
  
  return maxMode;
}
 
3.3 Reglas de Prioridad
Cuando hay conflicto entre modos (múltiples triggers detectados):
•	COACH_EMOCIONAL siempre tiene prioridad si se detecta emoción fuerte (>0.7)
•	TAX_EXPERT y SS_EXPERT tienen prioridad si hay términos normativos específicos
•	En empate, se prioriza según fase del programa (ver tabla de modos por fase)
•	Si no hay triggers claros, CONSULTOR_TACTICO es el modo por defecto
 
4. CONFIGURACIÓN MULTI-PROVEEDOR
4.1 Mapeo Modo → Proveedor
Modo	Proveedor Principal	Fallback	Justificación
🩷 COACH_EMOCIONAL	Claude Sonnet	GPT-4o	Requiere inteligencia emocional superior
🎯 CONSULTOR_TACTICO	Gemini Flash	Claude Haiku	Tareas estructuradas, coste-eficiente
🥊 SPARRING_PARTNER	Claude Sonnet	GPT-4o	Roleplay requiere profundidad
💰 CFO_SINTETICO	Claude Sonnet	GPT-4o	Cálculos + explicación clara
😈 ABOGADO_DIABLO	Claude Sonnet	GPT-4o	Balance crítica/empatía
🏛️ TAX_EXPERT	Gemini Pro + RAG	Claude + RAG	Grounding para normativa actual
🛡️ SS_EXPERT	Gemini Pro + RAG	Claude + RAG	Grounding para normativa actual
4.2 Configuración de Proveedores
 
// config/providers.js
const PROVIDER_CONFIG = {
  CLAUDE_SONNET: {
    name: 'Claude Sonnet 4',
    endpoint: 'https://api.anthropic.com/v1/messages',
    model: 'claude-sonnet-4-20250514',
    maxTokens: 4096,
    temperature: 0.7,
    costPer1KInput: 0.003,
    costPer1KOutput: 0.015,
    timeout: 30000,
    retries: 2
  },
  
  CLAUDE_HAIKU: {
    name: 'Claude Haiku 4',
    endpoint: 'https://api.anthropic.com/v1/messages',
    model: 'claude-haiku-4-20250514',
    maxTokens: 4096,
    temperature: 0.7,
    costPer1KInput: 0.00025,
    costPer1KOutput: 0.00125,
    timeout: 20000,
    retries: 2
  },
  
  GEMINI_FLASH: {
    name: 'Gemini 1.5 Flash',
    endpoint: 'https://generativelanguage.googleapis.com/v1beta/models',
    model: 'gemini-1.5-flash',
    maxTokens: 4096,
    temperature: 0.7,
    costPer1KInput: 0.000075,
    costPer1KOutput: 0.0003,
    timeout: 20000,
    retries: 2
  },
  
  GEMINI_PRO: {
    name: 'Gemini 1.5 Pro',
    endpoint: 'https://generativelanguage.googleapis.com/v1beta/models',
    model: 'gemini-1.5-pro',
    maxTokens: 4096,
    temperature: 0.5, // Más bajo para precisión normativa
    costPer1KInput: 0.00125,
    costPer1KOutput: 0.005,
    timeout: 30000,
    retries: 2,
    enableGrounding: true // Para búsqueda web
  },
  
  GPT4O: {
    name: 'GPT-4o',
    endpoint: 'https://api.openai.com/v1/chat/completions',
    model: 'gpt-4o',
    maxTokens: 4096,
    temperature: 0.7,
    costPer1KInput: 0.0025,
    costPer1KOutput: 0.01,
    timeout: 30000,
    retries: 2
  }
};
 
// Mapeo de modos a proveedores
const MODE_PROVIDER_MAP = {
  COACH_EMOCIONAL: { primary: 'CLAUDE_SONNET', fallback: 'GPT4O' },
  CONSULTOR_TACTICO: { primary: 'GEMINI_FLASH', fallback: 'CLAUDE_HAIKU' },
  SPARRING_PARTNER: { primary: 'CLAUDE_SONNET', fallback: 'GPT4O' },
  CFO_SINTETICO: { primary: 'CLAUDE_SONNET', fallback: 'GPT4O' },
  ABOGADO_DIABLO: { primary: 'CLAUDE_SONNET', fallback: 'GPT4O' },
  TAX_EXPERT: { primary: 'GEMINI_PRO', fallback: 'CLAUDE_SONNET', useRAG: true },
  SS_EXPERT: { primary: 'GEMINI_PRO', fallback: 'CLAUDE_SONNET', useRAG: true }
};
 
 
5. SISTEMA RAG PARA MODOS EXPERTOS
Los modos EXPERTO TRIBUTARIO y EXPERTO SEGURIDAD SOCIAL requieren información normativa actualizada y precisa. Para garantizar respuestas correctas, implementamos un sistema RAG (Retrieval-Augmented Generation) que recupera documentos normativos relevantes antes de generar la respuesta.
5.1 Arquitectura RAG
 
┌─────────────────────────────────────────────────────────────────────┐
│                     SISTEMA RAG EXPERTOS                            │
└─────────────────────────────────────────────────────────────────────┘
                              │
        ┌─────────────────────┼─────────────────────┐
        ▼                     ▼                     ▼
┌───────────────┐    ┌───────────────┐    ┌───────────────┐
│   INDEXADOR   │    │   RETRIEVER   │    │   GENERATOR   │
├───────────────┤    ├───────────────┤    ├───────────────┤
│ • Chunking    │    │ • Query       │    │ • Prompt      │
│ • Embedding   │    │   Embedding   │    │   Builder     │
│ • Metadata    │    │ • Vector      │    │ • LLM Call    │
│ • Storage     │    │   Search      │    │ • Citation    │
└───────────────┘    │ • Reranking   │    │   Injection   │
       │             └───────────────┘    └───────────────┘
       ▼                     │                     │
┌───────────────┐            │                     │
│ VECTOR DB     │◄───────────┘                     │
│ (pgvector)    │                                  │
├───────────────┤                                  │
│ • Normativa   │                                  │
│   Fiscal      │                                  │
│ • Normativa   │                                  │
│   SS          │                                  │
│ • Subvenc.    │──────────────────────────────────┘
│   Andalucía   │
└───────────────┘
 
5.2 Fuentes de Datos Normativa
Categoría	Fuentes	Actualización
Normativa Fiscal (AEAT)	Ley 35/2006 IRPF, Ley 37/1992 IVA, Reglamentos, Modelos 036/037/303/130/131	Trimestral + alertas BOE
Seguridad Social	LGSS, LETA (Ley Autónomos), Reglamento RETA, Tarifa Plana, Orden cotización anual	Anual + cambios normativos
Subvenciones Andalucía	PIIL BBRR, Convocatoria anual, Línea 1 Cuota Cero, Línea 2 Inicio Actividad	Por convocatoria
FAQs y Guías	Guías AEAT, Portal TGSS, InfoAutónomos, preguntas frecuentes verificadas	Mensual
5.3 Pipeline de Indexación
 
// services/rag/indexer.js
class NormativaIndexer {
  constructor(vectorDB, embeddingModel) {
    this.vectorDB = vectorDB;
    this.embedder = embeddingModel;
  }
  
  async indexDocument(document) {
    // 1. Extraer texto según tipo
    const text = await this.extractText(document);
    
    // 2. Chunking semántico (no por caracteres)
    const chunks = this.semanticChunk(text, {
      maxChunkSize: 1000,
      overlap: 100,
      splitOn: ['Artículo', 'Disposición', 'Apartado']
    });
    
    // 3. Generar embeddings
    const embeddings = await this.embedder.embed(chunks.map(c => c.text));
    
    // 4. Preparar metadata
    const records = chunks.map((chunk, i) => ({
      id: `${document.id}_chunk_${i}`,
      embedding: embeddings[i],
      text: chunk.text,
      metadata: {
        source: document.source,
        category: document.category, // 'TAX' | 'SS' | 'SUBVENCION'
        subcategory: document.subcategory, // 'IVA' | 'IRPF' | 'RETA' etc.
        article: chunk.article,
        lastVerified: document.lastVerified,
        effectiveDate: document.effectiveDate,
        expirationDate: document.expirationDate
      }
    }));
    
    // 5. Upsert en vector DB
    await this.vectorDB.upsert(records);
    
    return records.length;
  }
  
  semanticChunk(text, options) {
    const chunks = [];
    const sections = text.split(new RegExp(`(${options.splitOn.join('|')})`, 'gi'));
    
    let currentChunk = '';
    for (const section of sections) {
      if (currentChunk.length + section.length > options.maxChunkSize) {
        if (currentChunk.length > 0) {
          chunks.push({
            text: currentChunk.trim(),
            article: this.extractArticle(currentChunk)
          });
        }
        currentChunk = section;
      } else {
        currentChunk += section;
      }
    }
    
    if (currentChunk.length > 0) {
      chunks.push({
        text: currentChunk.trim(),
        article: this.extractArticle(currentChunk)
      });
    }
    
    return chunks;
  }
}
 
 
5.4 Pipeline de Retrieval
 
// services/rag/retriever.js
class NormativaRetriever {
  constructor(vectorDB, embeddingModel, reranker) {
    this.vectorDB = vectorDB;
    this.embedder = embeddingModel;
    this.reranker = reranker;
  }
  
  async retrieve(query, mode, options = {}) {
    const { topK = 10, minScore = 0.7, maxResults = 5 } = options;
    
    // 1. Determinar categoría según modo
    const category = mode === 'TAX_EXPERT' ? 'TAX' : 'SS';
    
    // 2. Expandir query con términos relacionados
    const expandedQuery = await this.expandQuery(query, category);
    
    // 3. Generar embedding de la query
    const queryEmbedding = await this.embedder.embed(expandedQuery);
    
    // 4. Búsqueda vectorial con filtro de categoría
    const candidates = await this.vectorDB.query({
      embedding: queryEmbedding,
      topK: topK,
      filter: {
        category: category,
        // Solo documentos vigentes
        $or: [
          { expirationDate: { $exists: false } },
          { expirationDate: { $gte: new Date() } }
        ]
      }
    });
    
    // 5. Filtrar por score mínimo
    const filtered = candidates.filter(c => c.score >= minScore);
    
    // 6. Reranking para precisión
    const reranked = await this.reranker.rerank(query, filtered);
    
    // 7. Devolver top results con metadata
    return reranked.slice(0, maxResults).map(doc => ({
      text: doc.text,
      source: doc.metadata.source,
      article: doc.metadata.article,
      lastVerified: doc.metadata.lastVerified,
      score: doc.score
    }));
  }
  
  async expandQuery(query, category) {
    // Expansión con sinónimos normativos
    const synonyms = {
      TAX: {
        'IVA': ['impuesto valor añadido', 'modelo 303'],
        'IRPF': ['renta', 'modelo 130', 'estimación directa'],
        'factura': ['facturación', 'TicketBAI', 'Verifactu'],
        'autónomo': ['trabajador por cuenta propia', 'actividad económica']
      },
      SS: {
        'cuota': ['cotización', 'base de cotización'],
        'tarifa plana': ['bonificación cuota', '80 euros'],
        'alta': ['alta RETA', 'inicio actividad'],
        'baja': ['cese actividad', 'baja RETA']
      }
    };
    
    let expanded = query;
    for (const [term, syns] of Object.entries(synonyms[category] || {})) {
      if (query.toLowerCase().includes(term.toLowerCase())) {
        expanded += ' ' + syns.join(' ');
      }
    }
    
    return expanded;
  }
}
 
5.5 Construcción del Prompt con Contexto Normativo
 
// services/rag/promptBuilder.js
function buildExpertPrompt(mode, query, retrievedDocs, entrepreneurProfile) {
  const systemPrompt = mode === 'TAX_EXPERT' 
    ? TAX_EXPERT_SYSTEM_PROMPT 
    : SS_EXPERT_SYSTEM_PROMPT;
  
  // Formatear documentos recuperados
  const contextSection = retrievedDocs.map((doc, i) => `
[FUENTE ${i + 1}]
Referencia: ${doc.source} - ${doc.article || 'General'}
Última verificación: ${doc.lastVerified}
---
${doc.text}
`).join('\n\n');
  
  // Construir prompt final
  return `
${systemPrompt}
 
## CONTEXTO NORMATIVO RECUPERADO
 
${contextSection}
 
## REGLAS DE RESPUESTA
 
1. Basa tu respuesta EXCLUSIVAMENTE en la normativa proporcionada arriba
2. Si la información no está en el contexto, indica claramente que no puedes confirmar
3. Siempre cita la fuente específica (artículo, ley, orden)
4. Incluye fechas de vigencia cuando sean relevantes
5. Añade SIEMPRE el disclaimer de orientación general
6. Adapta el lenguaje al carril del emprendedor: ${entrepreneurProfile.carril}
 
## PREGUNTA DEL EMPRENDEDOR
 
${query}
 
## TU RESPUESTA (incluye citas)
`;
}
 
const TAX_EXPERT_SYSTEM_PROMPT = `
Eres el Experto Tributario del Copiloto Andalucía +ei. Tu rol es orientar 
a emprendedores sobre obligaciones fiscales de autónomos en España.
 
ESPECIALIDADES:
- Alta censal (modelos 036/037)
- Régimen de IVA (modelo 303)
- Régimen de IRPF (modelos 130/131)
- Retenciones e ingresos a cuenta
- Gastos deducibles para autónomos
- Calendario fiscal trimestral
- Facturación electrónica (TicketBAI, Verifactu)
 
LIMITACIONES:
- No puedes dar asesoramiento fiscal personalizado
- No puedes calcular impuestos concretos (sugiere gestoría)
- No puedes recomendar estructuras de evasión/elusión
`;
 
const SS_EXPERT_SYSTEM_PROMPT = `
Eres el Experto en Seguridad Social del Copiloto Andalucía +ei. Tu rol es 
orientar a emprendedores sobre el régimen de autónomos (RETA) en España.
 
ESPECIALIDADES:
- Alta y baja en RETA
- Tarifa plana y bonificaciones
- Bases de cotización y cuotas
- Pluriactividad
- IT, maternidad/paternidad
- Compatibilidad con prestaciones (paro, IMV)
- Subvenciones Cuota Cero Andalucía
 
LIMITACIONES:
- No puedes dar asesoramiento legal personalizado
- No puedes calcular cuotas exactas sin datos completos
- Siempre recomienda verificar con TGSS o asesor laboral
`;
 
 
6. IMPLEMENTACIÓN DEL ROUTER (CÓDIGO)
6.1 Clase Principal del Router
 
// services/router/CopilotRouter.js
class CopilotRouter {
  constructor(config) {
    this.providers = config.providers;
    this.modeDetector = new ModeDetector(config.triggers);
    this.ragService = new RAGService(config.vectorDB, config.embedder);
    this.cache = new ResponseCache(config.redis);
    this.metrics = new MetricsCollector();
  }
  
  async processMessage(userId, message, sessionId) {
    const startTime = Date.now();
    
    try {
      // 1. Cargar perfil del emprendedor
      const profile = await this.loadProfile(userId);
      
      // 2. Detectar modo
      const mode = this.modeDetector.detect(message, profile);
      this.metrics.recordModeDetection(mode);
      
      // 3. Verificar cache
      const cacheKey = this.generateCacheKey(message, mode, profile);
      const cached = await this.cache.get(cacheKey);
      if (cached) {
        this.metrics.recordCacheHit();
        return cached;
      }
      
      // 4. Seleccionar proveedor
      const providerConfig = this.selectProvider(mode);
      
      // 5. Construir contexto (incluye RAG si es modo experto)
      const context = await this.buildContext(mode, message, profile);
      
      // 6. Construir prompt
      const prompt = this.buildPrompt(mode, message, context, profile);
      
      // 7. Llamar al proveedor con fallback
      const response = await this.callProviderWithFallback(
        providerConfig, 
        prompt,
        mode
      );
      
      // 8. Post-procesar respuesta
      const processed = this.postProcess(response, mode);
      
      // 9. Registrar métricas
      this.metrics.recordRequest({
        mode,
        provider: providerConfig.primary,
        latency: Date.now() - startTime,
        tokensUsed: response.usage
      });
      
      // 10. Cachear si aplica
      if (this.isCacheable(mode)) {
        await this.cache.set(cacheKey, processed, 3600);
      }
      
      return {
        response: processed.text,
        mode,
        provider: response.provider,
        citations: processed.citations,
        suggestedActions: processed.actions,
        tokensUsed: response.usage.total
      };
      
    } catch (error) {
      this.metrics.recordError(error);
      throw error;
    }
  }
  
  selectProvider(mode) {
    const mapping = MODE_PROVIDER_MAP[mode];
    return {
      primary: this.providers[mapping.primary],
      fallback: this.providers[mapping.fallback],
      useRAG: mapping.useRAG || false
    };
  }
  
  async callProviderWithFallback(config, prompt, mode) {
    try {
      // Intentar proveedor principal
      const response = await this.callProvider(config.primary, prompt);
      return { ...response, provider: config.primary.name };
      
    } catch (primaryError) {
      console.warn(`Primary provider failed: ${primaryError.message}`);
      this.metrics.recordFallback(config.primary.name, config.fallback.name);
      
      try {
        // Intentar fallback
        const response = await this.callProvider(config.fallback, prompt);
        return { ...response, provider: config.fallback.name };
        
      } catch (fallbackError) {
        console.error(`Fallback also failed: ${fallbackError.message}`);
        throw new Error('All providers failed');
      }
    }
  }
  
  async callProvider(provider, prompt) {
    const adapter = this.getProviderAdapter(provider);
    return await adapter.complete(prompt, {
      maxTokens: provider.maxTokens,
      temperature: provider.temperature,
      timeout: provider.timeout
    });
  }
  
  async buildContext(mode, message, profile) {
    const context = {
      entrepreneurName: profile.name,
      carril: profile.carril,
      fase: profile.fase,
      dimeScore: profile.dimeScore,
      bloqueos: profile.bloqueos,
      hipotesisActivas: profile.hipotesis.filter(h => h.status === 'active'),
      bmcStatus: profile.bmcValidation
    };
    
    // Añadir documentos RAG si es modo experto
    if (['TAX_EXPERT', 'SS_EXPERT'].includes(mode)) {
      context.retrievedDocs = await this.ragService.retrieve(message, mode);
    }
    
    return context;
  }
}
 
 
6.2 Adaptadores de Proveedores
 
// services/router/adapters/ClaudeAdapter.js
class ClaudeAdapter {
  constructor(apiKey) {
    this.apiKey = apiKey;
    this.baseUrl = 'https://api.anthropic.com/v1/messages';
  }
  
  async complete(prompt, options) {
    const response = await fetch(this.baseUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'x-api-key': this.apiKey,
        'anthropic-version': '2023-06-01'
      },
      body: JSON.stringify({
        model: options.model || 'claude-sonnet-4-20250514',
        max_tokens: options.maxTokens,
        temperature: options.temperature,
        messages: [{ role: 'user', content: prompt }]
      }),
      signal: AbortSignal.timeout(options.timeout)
    });
    
    if (!response.ok) {
      throw new Error(`Claude API error: ${response.status}`);
    }
    
    const data = await response.json();
    
    return {
      text: data.content[0].text,
      usage: {
        input: data.usage.input_tokens,
        output: data.usage.output_tokens,
        total: data.usage.input_tokens + data.usage.output_tokens
      }
    };
  }
}
 
// services/router/adapters/GeminiAdapter.js
class GeminiAdapter {
  constructor(apiKey) {
    this.apiKey = apiKey;
    this.baseUrl = 'https://generativelanguage.googleapis.com/v1beta';
  }
  
  async complete(prompt, options) {
    const model = options.model || 'gemini-1.5-flash';
    const url = `${this.baseUrl}/models/${model}:generateContent?key=${this.apiKey}`;
    
    const body = {
      contents: [{ parts: [{ text: prompt }] }],
      generationConfig: {
        maxOutputTokens: options.maxTokens,
        temperature: options.temperature
      }
    };
    
    // Añadir grounding si está habilitado
    if (options.enableGrounding) {
      body.tools = [{
        googleSearch: {}
      }];
    }
    
    const response = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
      signal: AbortSignal.timeout(options.timeout)
    });
    
    if (!response.ok) {
      throw new Error(`Gemini API error: ${response.status}`);
    }
    
    const data = await response.json();
    
    return {
      text: data.candidates[0].content.parts[0].text,
      groundingMetadata: data.candidates[0].groundingMetadata,
      usage: {
        input: data.usageMetadata?.promptTokenCount || 0,
        output: data.usageMetadata?.candidatesTokenCount || 0,
        total: data.usageMetadata?.totalTokenCount || 0
      }
    };
  }
}
 
 
7. ESQUEMA DE BASE DE DATOS NORMATIVA
7.1 Tablas Principales
 
-- Tabla principal de documentos normativos
CREATE TABLE normativa_documents (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    source VARCHAR(255) NOT NULL,        -- 'BOE', 'BOJA', 'AEAT', 'TGSS'
    category ENUM('TAX', 'SS', 'SUBVENCION') NOT NULL,
    subcategory VARCHAR(100),            -- 'IVA', 'IRPF', 'RETA', etc.
    title TEXT NOT NULL,
    original_url TEXT,
    effective_date DATE,
    expiration_date DATE,                -- NULL si vigente
    last_verified TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    raw_content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_category (category),
    INDEX idx_subcategory (subcategory),
    INDEX idx_effective_date (effective_date)
);
 
-- Tabla de chunks con embeddings (usando pgvector)
CREATE TABLE normativa_chunks (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    document_id UUID REFERENCES normativa_documents(id) ON DELETE CASCADE,
    chunk_index INT NOT NULL,
    chunk_text TEXT NOT NULL,
    article_reference VARCHAR(100),      -- 'Art. 92.1', 'DA 5ª', etc.
    embedding vector(1536),              -- OpenAI ada-002 dimension
    metadata JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_document (document_id),
    INDEX idx_embedding_cosine (embedding vector_cosine_ops)
);
 
-- Tabla de preguntas frecuentes verificadas
CREATE TABLE normativa_faqs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    category ENUM('TAX', 'SS', 'SUBVENCION') NOT NULL,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    source_references TEXT[],            -- Array de referencias normativas
    last_verified TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_by VARCHAR(100),
    embedding vector(1536),
    
    INDEX idx_faq_category (category),
    INDEX idx_faq_embedding (embedding vector_cosine_ops)
);
 
-- Tabla de actualizaciones normativas
CREATE TABLE normativa_updates (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    document_id UUID REFERENCES normativa_documents(id),
    change_type ENUM('NEW', 'MODIFIED', 'DEPRECATED') NOT NULL,
    change_description TEXT,
    detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    applied_at TIMESTAMP,
    notified BOOLEAN DEFAULT FALSE
);
 
7.2 Datos Iniciales: Normativa Fiscal
 
-- Insertar normativa fiscal básica
INSERT INTO normativa_documents (source, category, subcategory, title, effective_date) VALUES
 
-- IVA
('BOE', 'TAX', 'IVA', 'Ley 37/1992, de 28 de diciembre, del IVA', '1993-01-01'),
('AEAT', 'TAX', 'IVA', 'Modelo 303 - Autoliquidación IVA', '2023-01-01'),
('AEAT', 'TAX', 'IVA', 'Tipos impositivos IVA vigentes', '2024-01-01'),
 
-- IRPF
('BOE', 'TAX', 'IRPF', 'Ley 35/2006, de 28 de noviembre, del IRPF', '2007-01-01'),
('AEAT', 'TAX', 'IRPF', 'Modelo 130 - Pago fraccionado IRPF', '2023-01-01'),
('AEAT', 'TAX', 'IRPF', 'Modelo 131 - Estimación objetiva (módulos)', '2023-01-01'),
 
-- Alta censal
('AEAT', 'TAX', 'CENSAL', 'Modelo 036 - Declaración censal completa', '2023-01-01'),
('AEAT', 'TAX', 'CENSAL', 'Modelo 037 - Declaración censal simplificada', '2023-01-01'),
 
-- Gastos deducibles
('AEAT', 'TAX', 'DEDUCIBLES', 'Guía gastos deducibles autónomos', '2024-01-01'),
 
-- Facturación
('BOE', 'TAX', 'FACTURACION', 'RD 1619/2012 Reglamento facturación', '2013-01-01'),
('AEAT', 'TAX', 'FACTURACION', 'Requisitos Verifactu 2025', '2025-07-01');
 
7.3 Datos Iniciales: Seguridad Social
 
-- Insertar normativa Seguridad Social
INSERT INTO normativa_documents (source, category, subcategory, title, effective_date) VALUES
 
-- RETA General
('BOE', 'SS', 'RETA', 'RD Legislativo 8/2015 LGSS - RETA', '2015-10-31'),
('BOE', 'SS', 'RETA', 'Ley 20/2007 Estatuto Trabajo Autónomo', '2007-07-12'),
 
-- Cotización
('BOE', 'SS', 'COTIZACION', 'Orden cotización 2025', '2025-01-01'),
('TGSS', 'SS', 'COTIZACION', 'Bases y tipos cotización RETA 2025', '2025-01-01'),
('TGSS', 'SS', 'COTIZACION', 'Sistema cotización por ingresos reales', '2023-01-01'),
 
-- Tarifa plana
('BOE', 'SS', 'TARIFA_PLANA', 'RDL 13/2022 - Nueva tarifa plana autónomos', '2023-01-01'),
('TGSS', 'SS', 'TARIFA_PLANA', 'Requisitos tarifa plana 80€', '2023-01-01'),
 
-- Prestaciones
('TGSS', 'SS', 'PRESTACIONES', 'Cese actividad autónomos', '2023-01-01'),
('TGSS', 'SS', 'PRESTACIONES', 'IT autónomos', '2023-01-01'),
('TGSS', 'SS', 'PRESTACIONES', 'Maternidad/paternidad autónomos', '2023-01-01'),
 
-- Compatibilidades
('SEPE', 'SS', 'COMPATIBILIDAD', 'Compatibilidad paro y trabajo autónomo', '2023-01-01'),
('MITES', 'SS', 'COMPATIBILIDAD', 'Compatibilidad IMV y trabajo autónomo', '2023-01-01');
 
-- Subvenciones Andalucía
INSERT INTO normativa_documents (source, category, subcategory, title, effective_date) VALUES
('BOJA', 'SUBVENCION', 'AUTONOMOS', 'PIIL Bases Reguladoras', '2023-01-01'),
('BOJA', 'SUBVENCION', 'CUOTA_CERO', 'Línea 1 - Cuota Cero Andalucía', '2025-01-01'),
('BOJA', 'SUBVENCION', 'INICIO_ACTIVIDAD', 'Línea 2 - Inicio Actividad', '2025-01-01'),
('SAE', 'SUBVENCION', 'CONVOCATORIA', 'Convocatoria Proyectos Integrales 2025', '2025-11-19');
 
 
8. API DE INTEGRACIÓN
8.1 Endpoint Principal: /api/copilot/chat
 
// Request
POST /api/copilot/chat
Content-Type: application/json
Authorization: Bearer {jwt_token}
 
{
  "user_id": "uuid-entrepreneur-123",
  "session_id": "session-abc-456",
  "message": "¿Cuánto tengo que pagar de cuota de autónomo con la tarifa plana?"
}
 
// Response
{
  "response": "Con la tarifa plana actual, pagas 80€/mes durante los primeros 12 meses...",
  "mode_detected": "SS_EXPERT",
  "provider_used": "Gemini Pro",
  "citations": [
    {
      "source": "RDL 13/2022 - Nueva tarifa plana autónomos",
      "article": "Art. 1",
      "text": "La cuota reducida será de 80 euros mensuales...",
      "last_verified": "2025-01-15"
    }
  ],
  "disclaimer": "Esta información es orientativa. Consulta con un profesional para tu caso concreto.",
  "suggested_actions": [
    {
      "type": "TOOL",
      "label": "Abrir Checklist Alta Autónomo",
      "url": "/tools/checklist-alta"
    }
  ],
  "tokens_used": 847,
  "latency_ms": 1234
}
 
8.2 Endpoint de Administración: /api/admin/rag
 
// Indexar nuevo documento
POST /api/admin/rag/index
Content-Type: application/json
Authorization: Bearer {admin_token}
 
{
  "source": "BOE",
  "category": "TAX",
  "subcategory": "IVA",
  "title": "Nueva Orden sobre tipos IVA 2026",
  "effective_date": "2026-01-01",
  "content": "Artículo 1. Se modifican los tipos..."
}
 
// Verificar estado del índice
GET /api/admin/rag/status
 
{
  "total_documents": 45,
  "total_chunks": 1234,
  "by_category": {
    "TAX": { "documents": 20, "chunks": 567 },
    "SS": { "documents": 18, "chunks": 489 },
    "SUBVENCION": { "documents": 7, "chunks": 178 }
  },
  "last_updated": "2025-01-20T10:30:00Z",
  "pending_updates": 3
}
 
// Forzar re-indexación
POST /api/admin/rag/reindex
Content-Type: application/json
 
{
  "category": "SS",  // Opcional: solo una categoría
  "force": true      // Re-indexa aunque no haya cambios
}
 
 
9. MONITORIZACIÓN Y MÉTRICAS
9.1 Métricas Clave
Métrica	Objetivo	Alerta
Latencia P50	< 2s	WARN > 3s, CRIT > 5s
Latencia P99	< 5s	WARN > 8s, CRIT > 15s
Error Rate	< 1%	WARN > 2%, CRIT > 5%
Fallback Rate	< 5%	WARN > 10%, CRIT > 20%
Cache Hit Rate	> 20%	INFO si < 10%
Coste diario API	< €2	WARN > €3, CRIT > €5
RAG Retrieval Score	> 0.75	WARN < 0.6 (revisar índice)
9.2 Dashboard de Distribución de Modos
 
// Query para distribución de modos (últimos 7 días)
SELECT 
    mode_detected,
    COUNT(*) as total_calls,
    ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER (), 2) as percentage,
    AVG(latency_ms) as avg_latency,
    AVG(tokens_used) as avg_tokens,
    SUM(estimated_cost) as total_cost
FROM copilot_requests
WHERE created_at > NOW() - INTERVAL '7 days'
GROUP BY mode_detected
ORDER BY total_calls DESC;
 
-- Resultado esperado:
-- mode_detected      | total_calls | percentage | avg_latency | avg_tokens | total_cost
-- -------------------|-------------|------------|-------------|------------|------------
-- CONSULTOR_TACTICO  | 2400        | 30.0%      | 1234        | 450        | 0.45
-- COACH_EMOCIONAL    | 2000        | 25.0%      | 1567        | 520        | 2.10
-- SPARRING_PARTNER   | 1200        | 15.0%      | 1890        | 680        | 1.89
-- CFO_SINTETICO      | 800         | 10.0%      | 1456        | 590        | 1.26
-- TAX_EXPERT         | 640         | 8.0%       | 2100        | 720        | 0.98
-- SS_EXPERT          | 560         | 7.0%       | 2050        | 700        | 0.86
-- ABOGADO_DIABLO     | 400         | 5.0%       | 1678        | 540        | 0.81
 
 
10. PLAN DE IMPLEMENTACIÓN
10.1 Fases del Proyecto
Fase	Duración	Entregables
1. MVP	2 semanas	Router básico con 2 proveedores (Claude + Gemini), sin RAG
2. RAG Básico	2 semanas	Indexación normativa fiscal y SS, retrieval básico, 50 docs
3. Optimización	1 semana	Cache Redis, rate limiting, métricas básicas
4. Piloto	4 semanas	Deploy con 20 usuarios reales, iteración basada en feedback
5. Producción	Ongoing	Escalado a 100+ usuarios, monitorización completa, alertas
10.2 Checklist de Lanzamiento
•	API Keys configuradas para todos los proveedores (Anthropic, Google, OpenAI)
•	Base de datos PostgreSQL con extensión pgvector instalada
•	Redis configurado para cache de respuestas
•	Normativa inicial indexada (mínimo 50 documentos)
•	Métricas exportando a Grafana/Prometheus
•	Alertas configuradas en Slack/Email
•	Tests de integración pasando (>90% coverage)
•	Documentación de API publicada en Swagger/OpenAPI
•	Plan de rollback documentado
10.3 Presupuesto Estimado Mensual
Concepto	Coste/mes	Notas
API Claude (Sonnet + Haiku)	30-50€	~25% llamadas premium
API Gemini (Flash + Pro)	10-20€	~75% llamadas + RAG
API Embeddings (OpenAI ada-002)	5-10€	Indexación + queries
PostgreSQL (managed)	20-40€	Con pgvector
Redis (managed)	10-20€	Cache
Hosting (Drupal + Node)	30-60€	VPS o contenedores
TOTAL ESTIMADO	105-200€/mes	Para 100 usuarios

— Fin del Documento —
Programa Andalucía +ei | Jaraba Impact Platform | Enero 2026
