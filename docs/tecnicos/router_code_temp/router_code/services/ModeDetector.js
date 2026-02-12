/**
 * Detector de Modos - Copiloto Andalucía +ei
 * 
 * Analiza el mensaje del usuario y determina qué modo del Copiloto
 * debe activarse para dar la mejor respuesta.
 */

/**
 * Triggers para detección de modos
 * Cada trigger tiene un peso que contribuye a la puntuación del modo
 */
const MODE_TRIGGERS = {
  COACH_EMOCIONAL: [
    { word: 'miedo', weight: 10 },
    { word: 'no puedo', weight: 10 },
    { word: 'agobio', weight: 10 },
    { word: 'agobiado', weight: 10 },
    { word: 'agobiada', weight: 10 },
    { word: 'bloqueo', weight: 10 },
    { word: 'bloqueado', weight: 10 },
    { word: 'bloqueada', weight: 10 },
    { word: 'impostor', weight: 12 },
    { word: 'no sé si', weight: 8 },
    { word: 'dudo', weight: 7 },
    { word: 'culpa', weight: 9 },
    { word: 'vergüenza', weight: 9 },
    { word: 'fracaso', weight: 10 },
    { word: 'fracasado', weight: 10 },
    { word: 'hundido', weight: 10 },
    { word: 'hundida', weight: 10 },
    { word: 'ansiedad', weight: 10 },
    { word: 'me siento', weight: 6 },
    { word: 'no valgo', weight: 12 },
    { word: 'no sirvo', weight: 12 },
    { word: 'quién soy yo', weight: 10 },
    { word: 'me da cosa', weight: 8 },
    { word: 'tengo miedo', weight: 10 },
    { word: 'me paralizo', weight: 10 }
  ],
  
  CONSULTOR_TACTICO: [
    { word: 'cómo hago', weight: 10 },
    { word: 'cómo puedo', weight: 8 },
    { word: 'paso a paso', weight: 10 },
    { word: 'tutorial', weight: 10 },
    { word: 'herramienta', weight: 8 },
    { word: 'configurar', weight: 8 },
    { word: 'crear', weight: 5 },
    { word: 'montar', weight: 6 },
    { word: 'necesito', weight: 4 },
    { word: 'ayúdame a', weight: 6 },
    { word: 'explícame', weight: 7 },
    { word: 'instrucciones', weight: 9 },
    { word: 'guía', weight: 7 },
    { word: 'no entiendo', weight: 6 },
    { word: 'cómo funciona', weight: 8 }
  ],
  
  SPARRING_PARTNER: [
    { word: 'qué te parece', weight: 10 },
    { word: 'valídame', weight: 10 },
    { word: 'valida', weight: 8 },
    { word: 'practica', weight: 10 },
    { word: 'simula', weight: 10 },
    { word: 'simulación', weight: 10 },
    { word: 'feedback', weight: 9 },
    { word: 'cliente', weight: 5 },
    { word: 'pitch', weight: 9 },
    { word: 'presentación', weight: 6 },
    { word: 'actúa como', weight: 10 },
    { word: 'haz de', weight: 10 },
    { word: 'pon objeciones', weight: 12 },
    { word: 'ensayar', weight: 9 },
    { word: 'roleplay', weight: 12 }
  ],
  
  CFO_SINTETICO: [
    { word: 'precio', weight: 10 },
    { word: 'cobrar', weight: 10 },
    { word: 'cuánto', weight: 6 },
    { word: 'tarifa', weight: 10 },
    { word: 'descuento', weight: 8 },
    { word: 'rentable', weight: 9 },
    { word: 'margen', weight: 10 },
    { word: 'coste', weight: 8 },
    { word: 'costo', weight: 8 },
    { word: 'euros', weight: 6 },
    { word: 'dinero', weight: 5 },
    { word: 'caro', weight: 8 },
    { word: 'barato', weight: 8 },
    { word: 'presupuesto', weight: 7 },
    { word: 'facturar', weight: 8 },
    { word: 'ingresos', weight: 7 },
    { word: 'gastos', weight: 7 },
    { word: 'beneficio', weight: 8 },
    { word: 'punto de equilibrio', weight: 12 },
    { word: 'break even', weight: 12 }
  ],
  
  ABOGADO_DIABLO: [
    { word: 'estoy seguro', weight: 10 },
    { word: 'seguro que', weight: 8 },
    { word: 'claramente', weight: 7 },
    { word: 'sin duda', weight: 9 },
    { word: 'todos quieren', weight: 10 },
    { word: 'todo el mundo', weight: 8 },
    { word: 'es obvio', weight: 10 },
    { word: 'obviamente', weight: 8 },
    { word: 'funcionará', weight: 8 },
    { word: 'va a funcionar', weight: 8 },
    { word: 'éxito seguro', weight: 12 },
    { word: 'no tiene competencia', weight: 12 },
    { word: 'único', weight: 6 },
    { word: 'nadie hace', weight: 10 },
    { word: 'revolucionario', weight: 9 },
    { word: 'disruptivo', weight: 9 }
  ],
  
  TAX_EXPERT: [
    { word: 'hacienda', weight: 12 },
    { word: 'iva', weight: 12 },
    { word: 'irpf', weight: 12 },
    { word: 'modelo 303', weight: 15 },
    { word: 'modelo 130', weight: 15 },
    { word: 'modelo 131', weight: 15 },
    { word: 'modelo 036', weight: 15 },
    { word: 'modelo 037', weight: 15 },
    { word: 'declaración', weight: 8 },
    { word: 'factura', weight: 8 },
    { word: 'facturación', weight: 9 },
    { word: 'impuestos', weight: 10 },
    { word: 'fiscal', weight: 10 },
    { word: 'tributario', weight: 10 },
    { word: 'aeat', weight: 12 },
    { word: 'agencia tributaria', weight: 12 },
    { word: 'epígrafe', weight: 10 },
    { word: 'iae', weight: 10 },
    { word: 'trimestre', weight: 6 },
    { word: 'trimestral', weight: 7 },
    { word: 'deducir', weight: 8 },
    { word: 'deducible', weight: 8 },
    { word: 'gastos deducibles', weight: 12 },
    { word: 'verifactu', weight: 12 },
    { word: 'ticketbai', weight: 12 },
    { word: 'retención', weight: 9 },
    { word: 'base imponible', weight: 10 },
    { word: 'estimación directa', weight: 12 },
    { word: 'estimación objetiva', weight: 12 },
    { word: 'módulos', weight: 9 }
  ],
  
  SS_EXPERT: [
    { word: 'autónomo', weight: 10 },
    { word: 'autónomos', weight: 10 },
    { word: 'cuota', weight: 9 },
    { word: 'reta', weight: 12 },
    { word: 'tarifa plana', weight: 15 },
    { word: 'seguridad social', weight: 12 },
    { word: 'cotización', weight: 10 },
    { word: 'cotizar', weight: 9 },
    { word: 'alta', weight: 5 },
    { word: 'darme de alta', weight: 12 },
    { word: 'darse de alta', weight: 12 },
    { word: 'baja', weight: 5 },
    { word: 'baja autónomo', weight: 12 },
    { word: 'pluriactividad', weight: 12 },
    { word: 'base de cotización', weight: 12 },
    { word: '80 euros', weight: 12 },
    { word: 'cuota cero', weight: 15 },
    { word: 'bonificación', weight: 9 },
    { word: 'bonificaciones', weight: 9 },
    { word: 'it autónomo', weight: 10 },
    { word: 'incapacidad temporal', weight: 10 },
    { word: 'maternidad', weight: 7 },
    { word: 'paternidad', weight: 7 },
    { word: 'cese actividad', weight: 10 },
    { word: 'paro autónomo', weight: 12 },
    { word: 'tgss', weight: 12 },
    { word: 'tesorería', weight: 8 }
  ]
};

/**
 * Patrones de emoción para análisis semántico
 */
const EMOTION_PATTERNS = {
  fear: [
    /tengo miedo/i,
    /me da miedo/i,
    /me asusta/i,
    /me aterroriza/i,
    /no me atrevo/i
  ],
  anxiety: [
    /estoy agobiad[oa]/i,
    /me agobia/i,
    /ansiedad/i,
    /nervios/i,
    /estoy nervios[oa]/i
  ],
  impostor: [
    /quién soy yo para/i,
    /no soy suficiente/i,
    /no valgo/i,
    /me van a pillar/i,
    /fraude/i,
    /impostor/i
  ],
  paralysis: [
    /no sé por dónde empezar/i,
    /paraliza/i,
    /bloqueado/i,
    /no puedo avanzar/i,
    /estoy atascad[oa]/i
  ],
  rejection_fear: [
    /me dirán que no/i,
    /van a rechazar/i,
    /molestar/i,
    /qué pensarán/i,
    /van a decir que no/i
  ]
};

class ModeDetector {
  constructor(customTriggers = null) {
    this.triggers = customTriggers || MODE_TRIGGERS;
  }
  
  /**
   * Detecta el modo más apropiado para el mensaje
   * @param {string} message - Mensaje del usuario
   * @param {object} profile - Perfil del emprendedor
   * @returns {object} - Modo detectado y metadata
   */
  detect(message, profile = {}) {
    const messageLower = message.toLowerCase();
    const scores = {};
    const matchedTriggers = {};
    
    // 1. Calcular puntuación por triggers
    for (const [mode, triggers] of Object.entries(this.triggers)) {
      scores[mode] = 0;
      matchedTriggers[mode] = [];
      
      for (const trigger of triggers) {
        if (messageLower.includes(trigger.word.toLowerCase())) {
          scores[mode] += trigger.weight;
          matchedTriggers[mode].push(trigger.word);
        }
      }
    }
    
    // 2. Análisis de emociones
    const emotionAnalysis = this.analyzeEmotions(message);
    if (emotionAnalysis.hasStrongEmotion) {
      // Boost significativo para Coach Emocional si hay emoción fuerte
      scores['COACH_EMOCIONAL'] = (scores['COACH_EMOCIONAL'] || 0) + 15;
    }
    
    // 3. Modificadores por perfil del emprendedor
    if (profile.carril === 'IMPULSO') {
      // En IMPULSO, priorizamos más el soporte emocional
      scores['COACH_EMOCIONAL'] = (scores['COACH_EMOCIONAL'] || 0) * 1.3;
    }
    
    if (profile.fase === 'VIABILIDAD') {
      // En fase de viabilidad, priorizamos CFO y Expertos
      scores['CFO_SINTETICO'] = (scores['CFO_SINTETICO'] || 0) * 1.2;
      scores['TAX_EXPERT'] = (scores['TAX_EXPERT'] || 0) * 1.2;
      scores['SS_EXPERT'] = (scores['SS_EXPERT'] || 0) * 1.2;
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
    
    // 5. Umbral mínimo
    if (maxScore < 5) {
      maxMode = 'CONSULTOR_TACTICO';
    }
    
    // 6. Reglas de prioridad especiales
    // Coach Emocional siempre gana si hay emoción fuerte
    if (emotionAnalysis.score > 0.7 && scores['COACH_EMOCIONAL'] > 5) {
      maxMode = 'COACH_EMOCIONAL';
    }
    
    // Expertos siempre ganan si hay términos normativos específicos (modelos, leyes)
    const normativeTerms = /modelo \d{3}|art(ículo|\.)\s*\d+|ley \d+|real decreto/i;
    if (normativeTerms.test(message)) {
      if (scores['TAX_EXPERT'] > scores['SS_EXPERT']) {
        maxMode = 'TAX_EXPERT';
      } else if (scores['SS_EXPERT'] > 0) {
        maxMode = 'SS_EXPERT';
      }
    }
    
    return {
      mode: maxMode,
      confidence: this.calculateConfidence(maxScore, scores),
      scores,
      matchedTriggers: matchedTriggers[maxMode],
      emotionAnalysis,
      reasoning: this.generateReasoning(maxMode, matchedTriggers[maxMode], emotionAnalysis)
    };
  }
  
  /**
   * Analiza emociones en el mensaje
   */
  analyzeEmotions(message) {
    const emotions = {};
    let totalMatches = 0;
    
    for (const [emotion, patterns] of Object.entries(EMOTION_PATTERNS)) {
      emotions[emotion] = 0;
      for (const pattern of patterns) {
        if (pattern.test(message)) {
          emotions[emotion]++;
          totalMatches++;
        }
      }
    }
    
    // Calcular score de emoción (0-1)
    const score = Math.min(totalMatches / 3, 1); // Normalizado, max 1 con 3+ matches
    
    // Encontrar emoción dominante
    let dominantEmotion = null;
    let maxEmotionScore = 0;
    for (const [emotion, count] of Object.entries(emotions)) {
      if (count > maxEmotionScore) {
        maxEmotionScore = count;
        dominantEmotion = emotion;
      }
    }
    
    return {
      score,
      hasStrongEmotion: score > 0.5,
      dominantEmotion,
      emotions
    };
  }
  
  /**
   * Calcula confianza en la detección
   */
  calculateConfidence(maxScore, allScores) {
    if (maxScore === 0) return 0;
    
    // Calcular suma de todos los scores
    const totalScore = Object.values(allScores).reduce((a, b) => a + b, 0);
    if (totalScore === 0) return 0;
    
    // Confianza = proporción del score máximo respecto al total
    const confidence = maxScore / totalScore;
    
    // Ajustar por score absoluto
    const absoluteBonus = Math.min(maxScore / 30, 0.3); // Max 0.3 bonus
    
    return Math.min(confidence + absoluteBonus, 1);
  }
  
  /**
   * Genera explicación del razonamiento
   */
  generateReasoning(mode, triggers, emotionAnalysis) {
    const parts = [];
    
    if (triggers && triggers.length > 0) {
      parts.push(`Triggers detectados: ${triggers.join(', ')}`);
    }
    
    if (emotionAnalysis.hasStrongEmotion) {
      parts.push(`Emoción detectada: ${emotionAnalysis.dominantEmotion} (score: ${emotionAnalysis.score.toFixed(2)})`);
    }
    
    if (parts.length === 0) {
      parts.push('Modo por defecto (sin triggers específicos)');
    }
    
    return parts.join(' | ');
  }
}

module.exports = {
  ModeDetector,
  MODE_TRIGGERS,
  EMOTION_PATTERNS
};
