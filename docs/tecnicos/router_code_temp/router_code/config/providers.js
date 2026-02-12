/**
 * Configuración de Proveedores de IA - Copiloto Andalucía +ei
 * 
 * Este archivo define los proveedores disponibles y el mapeo
 * de modos del copiloto a proveedores óptimos.
 */

const PROVIDER_CONFIG = {
  // ═══════════════════════════════════════════════════════════
  // TIER PREMIUM - Para modos que requieren alta calidad
  // ═══════════════════════════════════════════════════════════
  
  CLAUDE_SONNET: {
    name: 'Claude Sonnet 4',
    provider: 'anthropic',
    endpoint: 'https://api.anthropic.com/v1/messages',
    model: 'claude-sonnet-4-20250514',
    maxTokens: 4096,
    temperature: 0.7,
    // Costes por 1K tokens (USD)
    costPer1KInput: 0.003,
    costPer1KOutput: 0.015,
    // Timeouts y reintentos
    timeout: 30000,
    retries: 2,
    retryDelay: 1000,
    // Capacidades
    capabilities: ['emotional_intelligence', 'roleplay', 'complex_reasoning', 'spanish_fluency']
  },
  
  CLAUDE_HAIKU: {
    name: 'Claude Haiku 4',
    provider: 'anthropic',
    endpoint: 'https://api.anthropic.com/v1/messages',
    model: 'claude-haiku-4-20250514',
    maxTokens: 4096,
    temperature: 0.7,
    costPer1KInput: 0.00025,
    costPer1KOutput: 0.00125,
    timeout: 20000,
    retries: 2,
    retryDelay: 500,
    capabilities: ['fast_response', 'basic_instructions', 'cost_efficient']
  },
  
  // ═══════════════════════════════════════════════════════════
  // TIER ESTÁNDAR - Para tareas estructuradas, coste-eficiente
  // ═══════════════════════════════════════════════════════════
  
  GEMINI_FLASH: {
    name: 'Gemini 1.5 Flash',
    provider: 'google',
    endpoint: 'https://generativelanguage.googleapis.com/v1beta/models',
    model: 'gemini-1.5-flash',
    maxTokens: 4096,
    temperature: 0.7,
    costPer1KInput: 0.000075,
    costPer1KOutput: 0.0003,
    timeout: 20000,
    retries: 2,
    retryDelay: 500,
    capabilities: ['fast_response', 'structured_output', 'cost_efficient', 'long_context']
  },
  
  // ═══════════════════════════════════════════════════════════
  // TIER EXPERTOS - Para modos normativos con RAG/Grounding
  // ═══════════════════════════════════════════════════════════
  
  GEMINI_PRO: {
    name: 'Gemini 1.5 Pro',
    provider: 'google',
    endpoint: 'https://generativelanguage.googleapis.com/v1beta/models',
    model: 'gemini-1.5-pro',
    maxTokens: 4096,
    temperature: 0.5, // Más bajo para precisión normativa
    costPer1KInput: 0.00125,
    costPer1KOutput: 0.005,
    timeout: 30000,
    retries: 2,
    retryDelay: 1000,
    // Grounding habilitado para búsqueda web
    enableGrounding: true,
    capabilities: ['grounding', 'long_context', 'complex_reasoning', 'citation']
  },
  
  // ═══════════════════════════════════════════════════════════
  // FALLBACK - OpenAI como respaldo universal
  // ═══════════════════════════════════════════════════════════
  
  GPT4O: {
    name: 'GPT-4o',
    provider: 'openai',
    endpoint: 'https://api.openai.com/v1/chat/completions',
    model: 'gpt-4o',
    maxTokens: 4096,
    temperature: 0.7,
    costPer1KInput: 0.0025,
    costPer1KOutput: 0.01,
    timeout: 30000,
    retries: 2,
    retryDelay: 1000,
    capabilities: ['universal', 'reliable', 'function_calling']
  },
  
  GPT4O_MINI: {
    name: 'GPT-4o Mini',
    provider: 'openai',
    endpoint: 'https://api.openai.com/v1/chat/completions',
    model: 'gpt-4o-mini',
    maxTokens: 4096,
    temperature: 0.7,
    costPer1KInput: 0.00015,
    costPer1KOutput: 0.0006,
    timeout: 20000,
    retries: 2,
    retryDelay: 500,
    capabilities: ['fast_response', 'cost_efficient', 'function_calling']
  }
};

/**
 * Mapeo de Modos del Copiloto a Proveedores
 * 
 * Cada modo tiene:
 * - primary: Proveedor principal (mejor calidad para ese caso de uso)
 * - fallback: Proveedor de respaldo si el principal falla
 * - useRAG: Si debe usar el sistema RAG para recuperar documentos
 * - tier: Categoría de coste (premium/standard/expert)
 */
const MODE_PROVIDER_MAP = {
  // ═══════════════════════════════════════════════════════════
  // MODOS TIER PREMIUM - Requieren alta calidad de respuesta
  // ═══════════════════════════════════════════════════════════
  
  COACH_EMOCIONAL: {
    primary: 'CLAUDE_SONNET',
    fallback: 'GPT4O',
    useRAG: false,
    tier: 'premium',
    description: 'Requiere inteligencia emocional, empatía, tono cálido'
  },
  
  SPARRING_PARTNER: {
    primary: 'CLAUDE_SONNET',
    fallback: 'GPT4O',
    useRAG: false,
    tier: 'premium',
    description: 'Roleplay convincente, mantener personaje, feedback matizado'
  },
  
  CFO_SINTETICO: {
    primary: 'CLAUDE_SONNET',
    fallback: 'GPT4O',
    useRAG: false,
    tier: 'premium',
    description: 'Cálculos precisos + explicación clara en español'
  },
  
  ABOGADO_DIABLO: {
    primary: 'CLAUDE_SONNET',
    fallback: 'GPT4O',
    useRAG: false,
    tier: 'premium',
    description: 'Balance entre crítica constructiva y empatía'
  },
  
  // ═══════════════════════════════════════════════════════════
  // MODOS TIER ESTÁNDAR - Tareas estructuradas, coste-eficiente
  // ═══════════════════════════════════════════════════════════
  
  CONSULTOR_TACTICO: {
    primary: 'GEMINI_FLASH',
    fallback: 'CLAUDE_HAIKU',
    useRAG: false,
    tier: 'standard',
    description: 'Instrucciones paso a paso, enlaces, estructura clara'
  },
  
  // ═══════════════════════════════════════════════════════════
  // MODOS TIER EXPERTOS - Requieren RAG + precisión normativa
  // ═══════════════════════════════════════════════════════════
  
  TAX_EXPERT: {
    primary: 'GEMINI_PRO',
    fallback: 'CLAUDE_SONNET',
    useRAG: true,
    ragCategory: 'TAX',
    tier: 'expert',
    description: 'Normativa fiscal AEAT, requiere citas y precisión'
  },
  
  SS_EXPERT: {
    primary: 'GEMINI_PRO',
    fallback: 'CLAUDE_SONNET',
    useRAG: true,
    ragCategory: 'SS',
    tier: 'expert',
    description: 'Normativa Seguridad Social, requiere citas y precisión'
  }
};

/**
 * Distribución esperada de uso por modo
 * (Para estimación de costes y capacity planning)
 */
const MODE_USAGE_DISTRIBUTION = {
  CONSULTOR_TACTICO: 0.30,    // 30% - Más común
  COACH_EMOCIONAL: 0.25,      // 25% - Especialmente en IMPULSO
  SPARRING_PARTNER: 0.15,     // 15%
  CFO_SINTETICO: 0.10,        // 10%
  TAX_EXPERT: 0.08,           // 8%
  SS_EXPERT: 0.07,            // 7%
  ABOGADO_DIABLO: 0.05        // 5% - Menos común
};

/**
 * Calcula el coste estimado por llamada según el modo
 */
function estimateCostPerCall(mode, avgInputTokens = 500, avgOutputTokens = 400) {
  const mapping = MODE_PROVIDER_MAP[mode];
  const provider = PROVIDER_CONFIG[mapping.primary];
  
  const inputCost = (avgInputTokens / 1000) * provider.costPer1KInput;
  const outputCost = (avgOutputTokens / 1000) * provider.costPer1KOutput;
  
  return {
    mode,
    provider: provider.name,
    inputCost,
    outputCost,
    totalCost: inputCost + outputCost
  };
}

/**
 * Calcula el coste mensual estimado dado un volumen de llamadas
 */
function estimateMonthlyCost(callsPerMonth) {
  let totalCost = 0;
  const breakdown = {};
  
  for (const [mode, distribution] of Object.entries(MODE_USAGE_DISTRIBUTION)) {
    const modeCalls = callsPerMonth * distribution;
    const costPerCall = estimateCostPerCall(mode);
    const modeCost = modeCalls * costPerCall.totalCost;
    
    breakdown[mode] = {
      calls: Math.round(modeCalls),
      costPerCall: costPerCall.totalCost.toFixed(6),
      totalCost: modeCost.toFixed(2)
    };
    
    totalCost += modeCost;
  }
  
  return {
    callsPerMonth,
    breakdown,
    totalCost: totalCost.toFixed(2)
  };
}

module.exports = {
  PROVIDER_CONFIG,
  MODE_PROVIDER_MAP,
  MODE_USAGE_DISTRIBUTION,
  estimateCostPerCall,
  estimateMonthlyCost
};
