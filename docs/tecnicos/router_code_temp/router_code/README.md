# Router Inteligente Multi-Proveedor - Copiloto Andalucía +ei

## 📋 Descripción

Sistema de enrutamiento inteligente que dirige las consultas del Copiloto de Emprendimiento al proveedor de IA más apropiado según el modo detectado, incluyendo sistema RAG para modos expertos normativos.

## 🏗️ Arquitectura

```
┌─────────────────────────────────────────────────────────────────┐
│                    COPILOTO ANDALUCÍA +ei                       │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│               DETECTOR DE MODO (ModeDetector.js)                │
│    Analiza triggers + emociones → Determina modo apropiado      │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                ROUTER (CopilotRouter.js)                        │
│    Selecciona proveedor + construye prompt + maneja fallback    │
└─────────────────────────────────────────────────────────────────┘
                              │
        ┌─────────────────────┼─────────────────────┐
        ▼                     ▼                     ▼
┌───────────────┐    ┌───────────────┐    ┌───────────────┐
│  TIER PREMIUM │    │  TIER ESTÁNDAR│    │ TIER EXPERTOS │
│ Claude Sonnet │    │ Gemini Flash  │    │ Gemini + RAG  │
├───────────────┤    ├───────────────┤    ├───────────────┤
│ 🩷 Coach      │    │ 🎯 Consultor  │    │ 🏛️ Tributario │
│ 🥊 Sparring   │    │               │    │ 🛡️ Seg.Social │
│ 💰 CFO        │    │               │    │               │
│ 😈 Abogado    │    │               │    │               │
└───────────────┘    └───────────────┘    └───────────────┘
```

## 📦 Estructura de Archivos

```
router_code/
├── config/
│   └── providers.js          # Configuración de proveedores de IA
├── services/
│   ├── ModeDetector.js       # Detección automática de modos
│   ├── CopilotRouter.js      # Router principal + adaptadores
│   └── RAGService.js         # Sistema RAG para modos expertos
├── database/
│   └── schema_normativa_rag.sql  # Esquema PostgreSQL + pgvector
└── README.md                 # Este archivo
```

## 🚀 Instalación

### 1. Requisitos Previos

- Node.js 18+
- PostgreSQL 14+ con extensión pgvector
- Redis (opcional, para cache)
- API Keys de:
  - Anthropic (Claude)
  - Google (Gemini)
  - OpenAI (embeddings + fallback)

### 2. Instalar Dependencias

```bash
npm install
```

### 3. Configurar Variables de Entorno

Crear archivo `.env`:

```env
# Proveedores de IA
ANTHROPIC_API_KEY=sk-ant-xxxxx
GOOGLE_API_KEY=AIzaxxxxx
OPENAI_API_KEY=sk-xxxxx

# Base de datos
DATABASE_URL=postgresql://user:pass@localhost:5432/copiloto_db

# Redis (opcional)
REDIS_URL=redis://localhost:6379
```

### 4. Configurar Base de Datos

```bash
# Crear base de datos
createdb copiloto_db

# Ejecutar esquema
psql copiloto_db < database/schema_normativa_rag.sql
```

### 5. Indexar Normativa Inicial

```javascript
const { RAGService } = require('./services/RAGService');

const rag = new RAGService({
  db: dbClient,
  openaiKey: process.env.OPENAI_API_KEY
});

// Indexar documento
await rag.indexDocument({
  source: 'BOE',
  category: 'TAX',
  subcategory: 'IVA',
  title: 'Ley 37/1992 del IVA',
  content: '... contenido del documento ...',
  effectiveDate: '1993-01-01'
});
```

## 💻 Uso

### Uso Básico

```javascript
const { CopilotRouter } = require('./services/CopilotRouter');

const router = new CopilotRouter({
  apiKeys: {
    anthropic: process.env.ANTHROPIC_API_KEY,
    google: process.env.GOOGLE_API_KEY,
    openai: process.env.OPENAI_API_KEY
  },
  db: dbClient,
  cache: redisClient,    // opcional
  vectorDB: pgVectorClient
});

// Procesar mensaje
const result = await router.processMessage(
  'user-123',
  '¿Cuánto pago de cuota de autónomo con tarifa plana?',
  'session-456'
);

console.log(result);
// {
//   response: "Con la tarifa plana pagas 80€/mes...",
//   mode: "SS_EXPERT",
//   provider: "Gemini Pro",
//   citations: [...],
//   disclaimer: "Esta información es orientativa...",
//   tokensUsed: 847,
//   latency: 1234
// }
```

### Integración con Drupal

```php
// En copilot_integration.module

function copilot_integration_chat_api($request) {
  $user_id = $request->get('user_id');
  $message = $request->get('message');
  $session_id = $request->get('session_id');
  
  // Llamar al router Node.js via HTTP
  $response = \Drupal::httpClient()->post('http://localhost:3001/api/chat', [
    'json' => [
      'user_id' => $user_id,
      'message' => $message,
      'session_id' => $session_id
    ]
  ]);
  
  return new JsonResponse(json_decode($response->getBody(), TRUE));
}
```

## 🎯 Los 7 Modos del Copiloto

| Modo | Icono | Proveedor | Descripción |
|------|-------|-----------|-------------|
| Coach Emocional | 🩷 | Claude Sonnet | Soporte emocional, validación, Kit Emocional |
| Consultor Táctico | 🎯 | Gemini Flash | Instrucciones paso a paso, tutoriales |
| Sparring Partner | 🥊 | Claude Sonnet | Roleplay, simulación cliente/inversor |
| CFO Sintético | 💰 | Claude Sonnet | Cálculos financieros, precios |
| Abogado del Diablo | 😈 | Claude Sonnet | Desafía hipótesis, pide evidencia |
| Experto Tributario | 🏛️ | Gemini + RAG | Normativa fiscal, IVA, IRPF |
| Experto Seg. Social | 🛡️ | Gemini + RAG | RETA, cuotas, tarifa plana |

## 📊 Métricas

```javascript
// Obtener métricas del router
const metrics = router.getMetrics();

console.log(metrics);
// {
//   requests: 1500,
//   modeDistribution: { CONSULTOR_TACTICO: 450, COACH_EMOCIONAL: 375, ... },
//   providerCalls: { 'Claude Sonnet': 600, 'Gemini Flash': 750, ... },
//   cacheHitRate: '23.5%',
//   fallbackRate: '2.1%',
//   errorRate: '0.3%'
// }
```

## 💰 Estimación de Costes

Para 100 usuarios activos, ~6.000 llamadas/mes:

| Componente | Coste Estimado |
|------------|----------------|
| Claude Sonnet (~25%) | 25-40€ |
| Gemini Flash (~60%) | 5-10€ |
| Gemini Pro + RAG (~15%) | 10-15€ |
| OpenAI Embeddings | 5-10€ |
| **TOTAL** | **45-75€/mes** |

## 🔧 Configuración Avanzada

### Personalizar Triggers de Modo

```javascript
const { ModeDetector, MODE_TRIGGERS } = require('./services/ModeDetector');

// Añadir trigger personalizado
MODE_TRIGGERS.TAX_EXPERT.push({ word: 'verifactu', weight: 12 });

// Crear detector con triggers personalizados
const detector = new ModeDetector(MODE_TRIGGERS);
```

### Ajustar Configuración RAG

```javascript
const rag = new RAGService({
  db: dbClient,
  openaiKey: process.env.OPENAI_API_KEY,
  ragConfig: {
    maxChunkSize: 1500,      // Chunks más grandes
    defaultMinScore: 0.75,   // Score más estricto
    defaultMaxResults: 3     // Menos documentos
  }
});
```

## 📝 Licencia

Propietario - Jaraba Impact Platform / Programa Andalucía +ei

## 🤝 Soporte

Para soporte técnico, contactar con el equipo de desarrollo.
