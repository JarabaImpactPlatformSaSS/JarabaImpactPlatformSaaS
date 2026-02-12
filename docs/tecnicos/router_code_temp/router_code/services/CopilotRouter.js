/**
 * CopilotRouter - Router Inteligente Multi-Proveedor
 * Copiloto Andalucía +ei v2.1
 * 
 * Este módulo orquesta las llamadas a diferentes proveedores de IA
 * según el modo detectado, incluyendo sistema RAG para modos expertos.
 */

const { PROVIDER_CONFIG, MODE_PROVIDER_MAP } = require('../config/providers');
const { ModeDetector } = require('./ModeDetector');

class CopilotRouter {
  /**
   * @param {object} config - Configuración del router
   * @param {object} config.apiKeys - API keys de los proveedores
   * @param {object} config.vectorDB - Cliente de base de datos vectorial
   * @param {object} config.cache - Cliente de cache (Redis)
   * @param {object} config.db - Cliente de base de datos (para perfiles)
   */
  constructor(config) {
    this.apiKeys = config.apiKeys;
    this.vectorDB = config.vectorDB;
    this.cache = config.cache;
    this.db = config.db;
    
    this.modeDetector = new ModeDetector();
    this.metrics = {
      requests: 0,
      modeDistribution: {},
      providerCalls: {},
      fallbackCount: 0,
      cacheHits: 0,
      errors: 0
    };
  }
  
  /**
   * Procesa un mensaje del usuario
   * @param {string} userId - ID del emprendedor
   * @param {string} message - Mensaje del usuario
   * @param {string} sessionId - ID de la sesión de chat
   * @returns {object} - Respuesta del Copiloto
   */
  async processMessage(userId, message, sessionId) {
    const startTime = Date.now();
    this.metrics.requests++;
    
    try {
      // 1. Cargar perfil del emprendedor
      const profile = await this.loadProfile(userId);
      
      // 2. Detectar modo
      const detection = this.modeDetector.detect(message, profile);
      const mode = detection.mode;
      
      // Actualizar métricas de distribución
      this.metrics.modeDistribution[mode] = (this.metrics.modeDistribution[mode] || 0) + 1;
      
      console.log(`[Router] Mode detected: ${mode} (confidence: ${detection.confidence.toFixed(2)})`);
      
      // 3. Verificar cache
      const cacheKey = this.generateCacheKey(message, mode, profile);
      const cached = await this.checkCache(cacheKey);
      if (cached) {
        this.metrics.cacheHits++;
        console.log(`[Router] Cache hit for key: ${cacheKey.substring(0, 20)}...`);
        return { ...cached, fromCache: true };
      }
      
      // 4. Seleccionar proveedor
      const providerMapping = MODE_PROVIDER_MAP[mode];
      const providerConfig = PROVIDER_CONFIG[providerMapping.primary];
      
      // 5. Construir contexto (incluye RAG si es modo experto)
      const context = await this.buildContext(mode, message, profile, providerMapping);
      
      // 6. Construir prompt completo
      const prompt = this.buildPrompt(mode, message, context, profile);
      
      // 7. Llamar al proveedor con fallback
      const response = await this.callProviderWithFallback(
        providerMapping,
        prompt,
        mode
      );
      
      // 8. Post-procesar respuesta
      const processed = this.postProcess(response, mode, context);
      
      // 9. Cachear respuesta si aplica
      if (this.isCacheable(mode, detection.confidence)) {
        await this.setCache(cacheKey, processed, 3600); // 1 hora
      }
      
      // 10. Registrar métricas
      const latency = Date.now() - startTime;
      console.log(`[Router] Request completed in ${latency}ms using ${response.provider}`);
      
      return {
        response: processed.text,
        mode,
        modeConfidence: detection.confidence,
        provider: response.provider,
        citations: processed.citations || [],
        disclaimer: processed.disclaimer,
        suggestedActions: processed.suggestedActions || [],
        tokensUsed: response.usage?.total || 0,
        latency,
        fromCache: false
      };
      
    } catch (error) {
      this.metrics.errors++;
      console.error(`[Router] Error processing message:`, error);
      throw error;
    }
  }
  
  /**
   * Carga el perfil del emprendedor desde la base de datos
   */
  async loadProfile(userId) {
    // En implementación real, cargar desde Drupal/MySQL
    // Aquí devolvemos un perfil de ejemplo
    if (this.db) {
      const result = await this.db.query(
        `SELECT * FROM entrepreneur_profile WHERE user_id = ?`,
        [userId]
      );
      if (result.length > 0) {
        return result[0];
      }
    }
    
    // Perfil por defecto para testing
    return {
      userId,
      name: 'Emprendedor',
      carril: 'IMPULSO',
      fase: 'VALIDACION',
      dimeScore: 8,
      bloqueos: [],
      hipotesis: [],
      bmcValidation: {}
    };
  }
  
  /**
   * Construye el contexto para el prompt
   */
  async buildContext(mode, message, profile, providerMapping) {
    const context = {
      entrepreneurName: profile.name,
      carril: profile.carril,
      fase: profile.fase,
      dimeScore: profile.dimeScore,
      bloqueos: profile.bloqueos || [],
      hipotesisActivas: (profile.hipotesis || []).filter(h => h.status === 'active'),
      bmcStatus: profile.bmcValidation || {}
    };
    
    // Añadir documentos RAG si es modo experto
    if (providerMapping.useRAG && this.vectorDB) {
      console.log(`[Router] Retrieving RAG documents for mode: ${mode}`);
      context.retrievedDocs = await this.retrieveRAGDocuments(
        message,
        providerMapping.ragCategory
      );
      console.log(`[Router] Retrieved ${context.retrievedDocs.length} documents`);
    }
    
    return context;
  }
  
  /**
   * Recupera documentos normativos relevantes usando RAG
   */
  async retrieveRAGDocuments(query, category) {
    if (!this.vectorDB) {
      console.warn('[Router] VectorDB not configured, skipping RAG');
      return [];
    }
    
    try {
      // 1. Generar embedding de la query
      const queryEmbedding = await this.generateEmbedding(query);
      
      // 2. Buscar documentos similares
      const results = await this.vectorDB.query({
        vector: queryEmbedding,
        topK: 10,
        filter: {
          category: category,
          // Solo documentos vigentes
          $or: [
            { expiration_date: { $exists: false } },
            { expiration_date: { $gte: new Date().toISOString() } }
          ]
        },
        includeMetadata: true
      });
      
      // 3. Filtrar por score mínimo
      const MIN_SCORE = 0.7;
      const filtered = results.filter(r => r.score >= MIN_SCORE);
      
      // 4. Limitar a top 5
      return filtered.slice(0, 5).map(doc => ({
        text: doc.text,
        source: doc.metadata.source,
        article: doc.metadata.article_reference,
        lastVerified: doc.metadata.last_verified,
        score: doc.score
      }));
      
    } catch (error) {
      console.error('[Router] RAG retrieval error:', error);
      return [];
    }
  }
  
  /**
   * Genera embedding para una query (usando OpenAI ada-002)
   */
  async generateEmbedding(text) {
    const response = await fetch('https://api.openai.com/v1/embeddings', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${this.apiKeys.openai}`
      },
      body: JSON.stringify({
        model: 'text-embedding-ada-002',
        input: text
      })
    });
    
    const data = await response.json();
    return data.data[0].embedding;
  }
  
  /**
   * Construye el prompt completo según el modo
   */
  buildPrompt(mode, message, context, profile) {
    // Obtener system prompt según modo
    const systemPrompt = this.getSystemPrompt(mode, profile.carril);
    
    // Añadir contexto del emprendedor
    const contextSection = `
## CONTEXTO DEL EMPRENDEDOR
- Nombre: ${context.entrepreneurName}
- Carril: ${context.carril}
- Fase actual: ${context.fase}
- Score DIME: ${context.dimeScore}/15
${context.bloqueos.length > 0 ? `- Bloqueos detectados: ${context.bloqueos.join(', ')}` : ''}
`;
    
    // Añadir documentos RAG si existen
    let ragSection = '';
    if (context.retrievedDocs && context.retrievedDocs.length > 0) {
      ragSection = `
## DOCUMENTACIÓN NORMATIVA RELEVANTE

${context.retrievedDocs.map((doc, i) => `
[FUENTE ${i + 1}]
Referencia: ${doc.source}${doc.article ? ` - ${doc.article}` : ''}
Verificado: ${doc.lastVerified}
---
${doc.text}
`).join('\n')}

IMPORTANTE: Basa tu respuesta en la documentación proporcionada. 
Cita las fuentes específicas cuando sea relevante.
`;
    }
    
    // Construir prompt final
    return `${systemPrompt}

${contextSection}
${ragSection}
## MENSAJE DEL EMPRENDEDOR

${message}

## TU RESPUESTA
`;
  }
  
  /**
   * Obtiene el system prompt según el modo
   */
  getSystemPrompt(mode, carril) {
    const prompts = {
      COACH_EMOCIONAL: `Eres el Coach Emocional del Copiloto Andalucía +ei.

Tu rol es apoyar emocionalmente a emprendedores cuando expresan miedos, dudas o bloqueos.

PROTOCOLO:
1. VALIDAR la emoción primero (nunca minimizar)
2. NORMALIZAR ("El 80% de emprendedores sienten lo mismo...")
3. OFRECER el Kit Emocional apropiado según el bloqueo
4. PROPONER una micro-acción que pueda hacer en 30 minutos

Tono: Cálido, empático, cercano. Como un mentor que ha pasado por lo mismo.
${carril === 'IMPULSO' ? 'NOTA: Este emprendedor necesita más apoyo emocional que técnico.' : ''}`,

      CONSULTOR_TACTICO: `Eres el Consultor Táctico del Copiloto Andalucía +ei.

Tu rol es dar instrucciones claras y paso a paso para tareas prácticas.

PROTOCOLO:
1. CONFIRMAR el objetivo ("Entiendo que quieres...")
2. DAR instrucciones numeradas, con tiempos estimados
3. RECOMENDAR herramientas específicas (con enlaces si es posible)
4. ANTICIPAR problemas comunes

${carril === 'IMPULSO' 
  ? 'NOTA: Usa lenguaje muy simple. Máximo 3 pasos. Herramientas básicas (Canva, Google Forms).' 
  : 'NOTA: Puedes usar terminología técnica y sugerir herramientas avanzadas.'}`,

      SPARRING_PARTNER: `Eres el Sparring Partner del Copiloto Andalucía +ei.

Tu rol es actuar como cliente, inversor o crítico para que el emprendedor practique.

PROTOCOLO:
1. PREGUNTAR qué rol quiere que adoptes
2. MANTENER el personaje durante la simulación
3. ESCALAR la dificultad de las objeciones
4. DAR feedback honesto al terminar

Haz preguntas difíciles, pero constructivas. El objetivo es preparar, no desanimar.`,

      CFO_SINTETICO: `Eres el CFO Sintético del Copiloto Andalucía +ei.

Tu rol es ayudar con precios, costes y viabilidad financiera.

FÓRMULA PRECIO HORA MÍNIMO:
(Gastos Fijos + Salario Deseado + 30% Imprevistos) ÷ Horas Facturables

REGLAS DE ORO:
- Si estás cómodo con el precio, es demasiado bajo
- El precio comunica valor
- Mejor pocos clientes buenos que muchos que te exprimen

${carril === 'ACELERA' ? 'Puedes hablar de unit economics, CAC, LTV, márgenes.' : 'Simplifica los cálculos al máximo.'}`,

      ABOGADO_DIABLO: `Eres el Abogado del Diablo del Copiloto Andalucía +ei.

Tu rol es desafiar hipótesis y pedir evidencia cuando el emprendedor está muy seguro.

PROTOCOLO:
1. RECONOCER el entusiasmo (no seas condescendiente)
2. DESAFIAR con preguntas incómodas pero constructivas
3. PEDIR evidencia específica ("¿Cuántos han pagado ya?")
4. SUGERIR un experimento para validar

Objetivo: Proteger al emprendedor de sus propios sesgos de confirmación.`,

      TAX_EXPERT: `Eres el Experto Tributario del Copiloto Andalucía +ei.

Tu rol es orientar sobre obligaciones fiscales de autónomos en España.

ESPECIALIDADES:
- Alta censal (modelos 036/037)
- IVA (modelo 303) e IRPF (modelos 130/131)
- Gastos deducibles, retenciones
- Calendario fiscal, facturación electrónica

IMPORTANTE:
- Basa tus respuestas en la normativa proporcionada
- SIEMPRE cita la fuente (artículo, ley, orden)
- Incluye el disclaimer de orientación general
- Recomienda consultar con profesional para casos complejos`,

      SS_EXPERT: `Eres el Experto en Seguridad Social del Copiloto Andalucía +ei.

Tu rol es orientar sobre el régimen de autónomos (RETA) en España.

ESPECIALIDADES:
- Alta y baja en RETA
- Tarifa plana y bonificaciones
- Bases de cotización y cuotas
- Compatibilidad con prestaciones (paro, IMV)
- Subvenciones Cuota Cero Andalucía

IMPORTANTE:
- Basa tus respuestas en la normativa proporcionada
- SIEMPRE cita la fuente (artículo, ley, orden)
- Incluye el disclaimer de orientación general
- Recomienda verificar con TGSS o asesor laboral`
    };
    
    return prompts[mode] || prompts.CONSULTOR_TACTICO;
  }
  
  /**
   * Llama al proveedor con fallback automático
   */
  async callProviderWithFallback(providerMapping, prompt, mode) {
    const primaryConfig = PROVIDER_CONFIG[providerMapping.primary];
    const fallbackConfig = PROVIDER_CONFIG[providerMapping.fallback];
    
    try {
      // Intentar proveedor principal
      const response = await this.callProvider(primaryConfig, prompt);
      this.metrics.providerCalls[primaryConfig.name] = 
        (this.metrics.providerCalls[primaryConfig.name] || 0) + 1;
      return { ...response, provider: primaryConfig.name };
      
    } catch (primaryError) {
      console.warn(`[Router] Primary provider ${primaryConfig.name} failed:`, primaryError.message);
      this.metrics.fallbackCount++;
      
      try {
        // Intentar fallback
        const response = await this.callProvider(fallbackConfig, prompt);
        this.metrics.providerCalls[fallbackConfig.name] = 
          (this.metrics.providerCalls[fallbackConfig.name] || 0) + 1;
        return { ...response, provider: fallbackConfig.name, usedFallback: true };
        
      } catch (fallbackError) {
        console.error(`[Router] Fallback ${fallbackConfig.name} also failed:`, fallbackError.message);
        throw new Error(`All providers failed: ${primaryError.message}, ${fallbackError.message}`);
      }
    }
  }
  
  /**
   * Llama a un proveedor específico
   */
  async callProvider(config, prompt) {
    const adapter = this.getProviderAdapter(config.provider);
    return adapter.complete(prompt, {
      apiKey: this.apiKeys[config.provider],
      model: config.model,
      maxTokens: config.maxTokens,
      temperature: config.temperature,
      timeout: config.timeout,
      enableGrounding: config.enableGrounding
    });
  }
  
  /**
   * Obtiene el adaptador para un proveedor
   */
  getProviderAdapter(provider) {
    const adapters = {
      anthropic: new ClaudeAdapter(),
      google: new GeminiAdapter(),
      openai: new OpenAIAdapter()
    };
    return adapters[provider];
  }
  
  /**
   * Post-procesa la respuesta
   */
  postProcess(response, mode, context) {
    const result = {
      text: response.text,
      citations: [],
      disclaimer: null,
      suggestedActions: []
    };
    
    // Añadir disclaimer para modos expertos
    if (['TAX_EXPERT', 'SS_EXPERT'].includes(mode)) {
      result.disclaimer = 'Esta información es orientativa y de carácter general. La normativa puede cambiar y cada situación es única. Para decisiones importantes, consulta con un profesional colegiado.';
      
      // Extraer citas de los documentos RAG
      if (context.retrievedDocs) {
        result.citations = context.retrievedDocs.map(doc => ({
          source: doc.source,
          article: doc.article,
          lastVerified: doc.lastVerified
        }));
      }
    }
    
    // Sugerir acciones según modo
    result.suggestedActions = this.getSuggestedActions(mode, context);
    
    return result;
  }
  
  /**
   * Obtiene acciones sugeridas según el modo
   */
  getSuggestedActions(mode, context) {
    const actions = {
      COACH_EMOCIONAL: [
        { type: 'TOOL', label: 'Abrir Kit de Primeros Auxilios', url: '/tools/kit-emocional' }
      ],
      CFO_SINTETICO: [
        { type: 'TOOL', label: 'Abrir Calculadora de la Verdad', url: '/tools/calculadora-verdad' }
      ],
      TAX_EXPERT: [
        { type: 'TOOL', label: 'Ver Checklist Alta Autónomo', url: '/tools/checklist-alta' },
        { type: 'EXTERNAL', label: 'Acceder a Sede AEAT', url: 'https://sede.agenciatributaria.gob.es' }
      ],
      SS_EXPERT: [
        { type: 'TOOL', label: 'Ver Checklist Alta Autónomo', url: '/tools/checklist-alta' },
        { type: 'EXTERNAL', label: 'Acceder a Sede TGSS', url: 'https://sede.seg-social.gob.es' }
      ]
    };
    
    return actions[mode] || [];
  }
  
  /**
   * Genera clave de cache
   */
  generateCacheKey(message, mode, profile) {
    const normalized = message.toLowerCase().trim().substring(0, 100);
    const hash = this.simpleHash(`${normalized}|${mode}|${profile.carril}`);
    return `copilot:${hash}`;
  }
  
  simpleHash(str) {
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
      const char = str.charCodeAt(i);
      hash = ((hash << 5) - hash) + char;
      hash = hash & hash;
    }
    return Math.abs(hash).toString(16);
  }
  
  async checkCache(key) {
    if (!this.cache) return null;
    try {
      const cached = await this.cache.get(key);
      return cached ? JSON.parse(cached) : null;
    } catch (e) {
      return null;
    }
  }
  
  async setCache(key, value, ttl) {
    if (!this.cache) return;
    try {
      await this.cache.setex(key, ttl, JSON.stringify(value));
    } catch (e) {
      console.warn('[Router] Cache set failed:', e.message);
    }
  }
  
  isCacheable(mode, confidence) {
    // No cachear respuestas emocionales o de baja confianza
    if (mode === 'COACH_EMOCIONAL') return false;
    if (confidence < 0.7) return false;
    return true;
  }
  
  /**
   * Obtiene métricas del router
   */
  getMetrics() {
    return {
      ...this.metrics,
      cacheHitRate: this.metrics.requests > 0 
        ? (this.metrics.cacheHits / this.metrics.requests * 100).toFixed(2) + '%'
        : '0%',
      fallbackRate: this.metrics.requests > 0
        ? (this.metrics.fallbackCount / this.metrics.requests * 100).toFixed(2) + '%'
        : '0%',
      errorRate: this.metrics.requests > 0
        ? (this.metrics.errors / this.metrics.requests * 100).toFixed(2) + '%'
        : '0%'
    };
  }
}

// ═══════════════════════════════════════════════════════════
// ADAPTADORES DE PROVEEDORES
// ═══════════════════════════════════════════════════════════

class ClaudeAdapter {
  async complete(prompt, options) {
    const response = await fetch('https://api.anthropic.com/v1/messages', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'x-api-key': options.apiKey,
        'anthropic-version': '2023-06-01'
      },
      body: JSON.stringify({
        model: options.model,
        max_tokens: options.maxTokens,
        temperature: options.temperature,
        messages: [{ role: 'user', content: prompt }]
      }),
      signal: AbortSignal.timeout(options.timeout)
    });
    
    if (!response.ok) {
      const error = await response.text();
      throw new Error(`Claude API error ${response.status}: ${error}`);
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

class GeminiAdapter {
  async complete(prompt, options) {
    const url = `https://generativelanguage.googleapis.com/v1beta/models/${options.model}:generateContent?key=${options.apiKey}`;
    
    const body = {
      contents: [{ parts: [{ text: prompt }] }],
      generationConfig: {
        maxOutputTokens: options.maxTokens,
        temperature: options.temperature
      }
    };
    
    // Añadir grounding si está habilitado
    if (options.enableGrounding) {
      body.tools = [{ googleSearch: {} }];
    }
    
    const response = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
      signal: AbortSignal.timeout(options.timeout)
    });
    
    if (!response.ok) {
      const error = await response.text();
      throw new Error(`Gemini API error ${response.status}: ${error}`);
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

class OpenAIAdapter {
  async complete(prompt, options) {
    const response = await fetch('https://api.openai.com/v1/chat/completions', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${options.apiKey}`
      },
      body: JSON.stringify({
        model: options.model,
        max_tokens: options.maxTokens,
        temperature: options.temperature,
        messages: [{ role: 'user', content: prompt }]
      }),
      signal: AbortSignal.timeout(options.timeout)
    });
    
    if (!response.ok) {
      const error = await response.text();
      throw new Error(`OpenAI API error ${response.status}: ${error}`);
    }
    
    const data = await response.json();
    
    return {
      text: data.choices[0].message.content,
      usage: {
        input: data.usage.prompt_tokens,
        output: data.usage.completion_tokens,
        total: data.usage.total_tokens
      }
    };
  }
}

module.exports = {
  CopilotRouter,
  ClaudeAdapter,
  GeminiAdapter,
  OpenAIAdapter
};
