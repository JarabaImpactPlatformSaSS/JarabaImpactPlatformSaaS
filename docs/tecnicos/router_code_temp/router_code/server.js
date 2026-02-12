/**
 * Servidor API - Router Inteligente Copiloto
 * 
 * Este servidor expone la API REST para el Copiloto de Emprendimiento.
 * Diseñado para ejecutarse como microservicio independiente o integrado en Drupal.
 */

require('dotenv').config();
const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const { v4: uuidv4 } = require('uuid');

const { CopilotRouter } = require('./services/CopilotRouter');
const { ModeDetector } = require('./services/ModeDetector');
const { RAGService } = require('./services/RAGService');

const app = express();
const PORT = process.env.PORT || 3001;

// ═══════════════════════════════════════════════════════════
// MIDDLEWARE
// ═══════════════════════════════════════════════════════════

app.use(helmet());
app.use(cors({
  origin: process.env.ALLOWED_ORIGINS?.split(',') || ['http://localhost:3000'],
  credentials: true
}));
app.use(express.json({ limit: '10mb' }));

// Request logging
app.use((req, res, next) => {
  const requestId = uuidv4().substring(0, 8);
  req.requestId = requestId;
  console.log(`[${requestId}] ${req.method} ${req.path}`);
  next();
});

// ═══════════════════════════════════════════════════════════
// INICIALIZACIÓN
// ═══════════════════════════════════════════════════════════

let router = null;
let ragService = null;

async function initializeServices() {
  console.log('Initializing services...');
  
  // En producción, conectar a PostgreSQL y Redis reales
  // Aquí usamos mocks para desarrollo
  const mockDB = {
    query: async (sql, params) => {
      console.log(`[DB] Query: ${sql.substring(0, 50)}...`);
      return { rows: [] };
    }
  };
  
  const mockCache = {
    get: async (key) => null,
    setex: async (key, ttl, value) => true
  };
  
  const mockVectorDB = null; // Requiere pgvector configurado
  
  // Inicializar router
  router = new CopilotRouter({
    apiKeys: {
      anthropic: process.env.ANTHROPIC_API_KEY,
      google: process.env.GOOGLE_API_KEY,
      openai: process.env.OPENAI_API_KEY
    },
    db: mockDB,
    cache: mockCache,
    vectorDB: mockVectorDB
  });
  
  // Inicializar RAG service
  ragService = new RAGService({
    db: mockDB,
    openaiKey: process.env.OPENAI_API_KEY
  });
  
  console.log('Services initialized');
}

// ═══════════════════════════════════════════════════════════
// RUTAS API
// ═══════════════════════════════════════════════════════════

/**
 * POST /api/copilot/chat
 * Endpoint principal para procesar mensajes del Copiloto
 */
app.post('/api/copilot/chat', async (req, res) => {
  const { user_id, message, session_id } = req.body;
  
  if (!user_id || !message) {
    return res.status(400).json({
      error: 'Missing required fields: user_id, message'
    });
  }
  
  try {
    const result = await router.processMessage(
      user_id,
      message,
      session_id || uuidv4()
    );
    
    res.json(result);
    
  } catch (error) {
    console.error(`[${req.requestId}] Error:`, error);
    res.status(500).json({
      error: 'Error processing message',
      message: error.message
    });
  }
});

/**
 * POST /api/copilot/detect-mode
 * Detecta el modo sin procesar el mensaje completo
 */
app.post('/api/copilot/detect-mode', (req, res) => {
  const { message, profile } = req.body;
  
  if (!message) {
    return res.status(400).json({ error: 'Missing message' });
  }
  
  const detector = new ModeDetector();
  const result = detector.detect(message, profile || {});
  
  res.json(result);
});

/**
 * GET /api/copilot/metrics
 * Obtiene métricas del router
 */
app.get('/api/copilot/metrics', (req, res) => {
  if (!router) {
    return res.status(503).json({ error: 'Service not initialized' });
  }
  
  res.json(router.getMetrics());
});

/**
 * GET /api/copilot/health
 * Health check
 */
app.get('/api/copilot/health', async (req, res) => {
  const health = {
    status: 'healthy',
    timestamp: new Date().toISOString(),
    services: {
      router: router ? 'ok' : 'not initialized',
      rag: ragService ? 'ok' : 'not initialized'
    }
  };
  
  // Verificar RAG si está disponible
  if (ragService) {
    try {
      const ragHealth = await ragService.healthCheck();
      health.services.ragDetails = ragHealth;
    } catch (e) {
      health.services.ragDetails = { status: 'error', message: e.message };
    }
  }
  
  res.json(health);
});

// ═══════════════════════════════════════════════════════════
// RUTAS RAG ADMIN
// ═══════════════════════════════════════════════════════════

/**
 * POST /api/admin/rag/index
 * Indexa un nuevo documento normativo
 */
app.post('/api/admin/rag/index', async (req, res) => {
  // En producción, verificar autenticación de admin
  const document = req.body;
  
  if (!document.source || !document.category || !document.title || !document.content) {
    return res.status(400).json({
      error: 'Missing required fields: source, category, title, content'
    });
  }
  
  try {
    const result = await ragService.indexDocument(document);
    res.json(result);
  } catch (error) {
    console.error('Index error:', error);
    res.status(500).json({ error: error.message });
  }
});

/**
 * POST /api/admin/rag/index-faq
 * Indexa una FAQ verificada
 */
app.post('/api/admin/rag/index-faq', async (req, res) => {
  const faq = req.body;
  
  if (!faq.category || !faq.question || !faq.answer) {
    return res.status(400).json({
      error: 'Missing required fields: category, question, answer'
    });
  }
  
  try {
    const result = await ragService.indexFaq(faq);
    res.json(result);
  } catch (error) {
    console.error('FAQ index error:', error);
    res.status(500).json({ error: error.message });
  }
});

/**
 * GET /api/admin/rag/stats
 * Obtiene estadísticas del índice RAG
 */
app.get('/api/admin/rag/stats', async (req, res) => {
  try {
    const stats = await ragService.getStats();
    res.json(stats);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

/**
 * POST /api/admin/rag/search
 * Prueba de búsqueda RAG
 */
app.post('/api/admin/rag/search', async (req, res) => {
  const { query, category, options } = req.body;
  
  if (!query || !category) {
    return res.status(400).json({ error: 'Missing query or category' });
  }
  
  try {
    const results = await ragService.retrieve(query, category, options);
    res.json({ results });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// ═══════════════════════════════════════════════════════════
// ERROR HANDLING
// ═══════════════════════════════════════════════════════════

app.use((err, req, res, next) => {
  console.error(`[${req.requestId}] Unhandled error:`, err);
  res.status(500).json({
    error: 'Internal server error',
    requestId: req.requestId
  });
});

// ═══════════════════════════════════════════════════════════
// START SERVER
// ═══════════════════════════════════════════════════════════

async function start() {
  await initializeServices();
  
  app.listen(PORT, () => {
    console.log(`
╔════════════════════════════════════════════════════════════╗
║     COPILOTO ROUTER - ANDALUCÍA +ei v2.1                   ║
╠════════════════════════════════════════════════════════════╣
║  Server running on port ${PORT}                               ║
║                                                            ║
║  Endpoints:                                                ║
║    POST /api/copilot/chat        - Procesar mensaje        ║
║    POST /api/copilot/detect-mode - Solo detectar modo      ║
║    GET  /api/copilot/metrics     - Métricas del router     ║
║    GET  /api/copilot/health      - Health check            ║
║                                                            ║
║  Admin (RAG):                                              ║
║    POST /api/admin/rag/index     - Indexar documento       ║
║    POST /api/admin/rag/index-faq - Indexar FAQ             ║
║    GET  /api/admin/rag/stats     - Estadísticas            ║
║    POST /api/admin/rag/search    - Prueba búsqueda         ║
╚════════════════════════════════════════════════════════════╝
    `);
  });
}

start().catch(err => {
  console.error('Failed to start server:', err);
  process.exit(1);
});

module.exports = app;
