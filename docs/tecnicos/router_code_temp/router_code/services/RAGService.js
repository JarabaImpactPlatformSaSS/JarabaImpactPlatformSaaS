/**
 * RAGService - Retrieval-Augmented Generation para Modos Expertos
 * Copiloto Andalucía +ei v2.1
 * 
 * Este servicio maneja la indexación y recuperación de documentos
 * normativos para los modos TAX_EXPERT y SS_EXPERT.
 */

/**
 * Configuración del servicio RAG
 */
const RAG_CONFIG = {
  // Embeddings
  embeddingModel: 'text-embedding-ada-002',
  embeddingDimension: 1536,
  
  // Chunking
  maxChunkSize: 1000,        // Caracteres máximos por chunk
  chunkOverlap: 100,         // Solapamiento entre chunks
  
  // Retrieval
  defaultTopK: 10,           // Candidatos iniciales
  defaultMaxResults: 5,      // Resultados finales
  defaultMinScore: 0.7,      // Score mínimo de similitud
  
  // Categorías
  categories: {
    TAX: {
      name: 'Normativa Fiscal',
      subcategories: ['IVA', 'IRPF', 'CENSAL', 'DEDUCIBLES', 'FACTURACION', 'RETENCION']
    },
    SS: {
      name: 'Seguridad Social',
      subcategories: ['RETA', 'COTIZACION', 'TARIFA_PLANA', 'PRESTACIONES', 'COMPATIBILIDAD']
    },
    SUBVENCION: {
      name: 'Subvenciones',
      subcategories: ['AUTONOMOS', 'CUOTA_CERO', 'INICIO_ACTIVIDAD', 'CONVOCATORIA']
    }
  }
};

/**
 * Sinónimos para expansión de queries por categoría
 */
const QUERY_SYNONYMS = {
  TAX: {
    'iva': ['impuesto valor añadido', 'modelo 303', 'iva soportado', 'iva repercutido'],
    'irpf': ['renta', 'modelo 130', 'modelo 131', 'estimación directa', 'estimación objetiva'],
    'factura': ['facturación', 'factura electrónica', 'ticketbai', 'verifactu'],
    'autónomo': ['trabajador por cuenta propia', 'actividad económica', 'empresario individual'],
    'deducir': ['deducible', 'desgravación', 'gasto deducible'],
    'alta': ['alta censal', 'modelo 036', 'modelo 037', 'inicio actividad'],
    'trimestre': ['trimestral', 'declaración trimestral', 'pago fraccionado']
  },
  SS: {
    'cuota': ['cotización', 'base de cotización', 'tipo de cotización'],
    'tarifa plana': ['bonificación cuota', '80 euros', 'cuota reducida'],
    'alta': ['alta reta', 'inicio actividad', 'afiliación'],
    'baja': ['cese actividad', 'baja reta', 'fin actividad'],
    'paro': ['desempleo', 'prestación por desempleo', 'cese actividad'],
    'pluriactividad': ['trabajo por cuenta ajena', 'doble cotización', 'base mínima reducida'],
    'maternidad': ['nacimiento', 'cuidado menor', 'prestación nacimiento'],
    'incapacidad': ['it', 'baja médica', 'enfermedad', 'accidente']
  },
  SUBVENCION: {
    'cuota cero': ['bonificación 100%', 'línea 1', 'subvención cuota'],
    'ayuda': ['subvención', 'incentivo', 'línea 2'],
    'junta': ['junta de andalucía', 'comunidad autónoma', 'gobierno andaluz'],
    'colectivo': ['mujer', 'joven', 'discapacidad', 'víctima violencia']
  }
};

class RAGService {
  /**
   * @param {object} config - Configuración del servicio
   * @param {object} config.vectorDB - Cliente de base de datos vectorial (pgvector)
   * @param {string} config.openaiKey - API key de OpenAI para embeddings
   * @param {object} config.db - Cliente de base de datos PostgreSQL
   */
  constructor(config) {
    this.vectorDB = config.vectorDB;
    this.openaiKey = config.openaiKey;
    this.db = config.db;
    this.config = { ...RAG_CONFIG, ...config.ragConfig };
  }
  
  // ═══════════════════════════════════════════════════════════
  // RETRIEVAL (Recuperación de documentos)
  // ═══════════════════════════════════════════════════════════
  
  /**
   * Recupera documentos relevantes para una query
   * @param {string} query - Pregunta del usuario
   * @param {string} category - Categoría ('TAX' | 'SS' | 'SUBVENCION')
   * @param {object} options - Opciones de búsqueda
   * @returns {array} - Documentos relevantes ordenados por relevancia
   */
  async retrieve(query, category, options = {}) {
    const {
      topK = this.config.defaultTopK,
      maxResults = this.config.defaultMaxResults,
      minScore = this.config.defaultMinScore,
      includesFaqs = true
    } = options;
    
    console.log(`[RAG] Retrieving documents for category: ${category}`);
    
    try {
      // 1. Expandir query con sinónimos
      const expandedQuery = this.expandQuery(query, category);
      console.log(`[RAG] Expanded query: ${expandedQuery.substring(0, 100)}...`);
      
      // 2. Generar embedding de la query
      const queryEmbedding = await this.generateEmbedding(expandedQuery);
      
      // 3. Buscar en chunks de documentos
      const chunkResults = await this.searchChunks(queryEmbedding, category, topK, minScore);
      console.log(`[RAG] Found ${chunkResults.length} matching chunks`);
      
      // 4. Buscar en FAQs (si está habilitado)
      let faqResults = [];
      if (includesFaqs) {
        faqResults = await this.searchFaqs(queryEmbedding, category, 3, minScore);
        console.log(`[RAG] Found ${faqResults.length} matching FAQs`);
      }
      
      // 5. Combinar y reranquear resultados
      const combined = this.combineResults(chunkResults, faqResults);
      
      // 6. Diversificar (evitar demasiados chunks del mismo documento)
      const diversified = this.diversifyResults(combined, maxResults);
      
      // 7. Formatear para el prompt
      return diversified.map(doc => ({
        text: doc.text,
        source: doc.source,
        article: doc.article || null,
        lastVerified: doc.lastVerified,
        score: doc.score,
        type: doc.type // 'chunk' | 'faq'
      }));
      
    } catch (error) {
      console.error('[RAG] Retrieval error:', error);
      return [];
    }
  }
  
  /**
   * Expande la query con sinónimos relevantes
   */
  expandQuery(query, category) {
    const synonyms = QUERY_SYNONYMS[category] || {};
    let expanded = query.toLowerCase();
    
    for (const [term, syns] of Object.entries(synonyms)) {
      if (expanded.includes(term.toLowerCase())) {
        // Añadir sinónimos al final
        expanded += ' ' + syns.join(' ');
      }
    }
    
    return expanded;
  }
  
  /**
   * Genera embedding usando OpenAI
   */
  async generateEmbedding(text) {
    const response = await fetch('https://api.openai.com/v1/embeddings', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${this.openaiKey}`
      },
      body: JSON.stringify({
        model: this.config.embeddingModel,
        input: text.substring(0, 8000) // Limitar longitud
      })
    });
    
    if (!response.ok) {
      throw new Error(`OpenAI Embeddings error: ${response.status}`);
    }
    
    const data = await response.json();
    return data.data[0].embedding;
  }
  
  /**
   * Busca chunks similares en la base de datos
   */
  async searchChunks(embedding, category, topK, minScore) {
    const query = `
      SELECT 
        c.id,
        c.chunk_text as text,
        c.article_reference as article,
        d.source,
        d.title,
        d.last_verified as "lastVerified",
        1 - (c.embedding <=> $1::vector) as score
      FROM normativa_chunks c
      JOIN normativa_documents d ON c.document_id = d.id
      WHERE d.category = $2
        AND (d.expiration_date IS NULL OR d.expiration_date > CURRENT_DATE)
        AND 1 - (c.embedding <=> $1::vector) >= $3
      ORDER BY c.embedding <=> $1::vector
      LIMIT $4
    `;
    
    const result = await this.db.query(query, [
      `[${embedding.join(',')}]`,
      category,
      minScore,
      topK
    ]);
    
    return result.rows.map(row => ({
      ...row,
      type: 'chunk',
      source: `${row.source} - ${row.title}`
    }));
  }
  
  /**
   * Busca FAQs similares
   */
  async searchFaqs(embedding, category, topK, minScore) {
    const query = `
      SELECT 
        f.id,
        f.question,
        f.answer as text,
        f.source_references as sources,
        f.last_verified as "lastVerified",
        1 - (f.embedding <=> $1::vector) as score
      FROM normativa_faqs f
      WHERE f.category = $2
        AND 1 - (f.embedding <=> $1::vector) >= $3
      ORDER BY f.embedding <=> $1::vector
      LIMIT $4
    `;
    
    const result = await this.db.query(query, [
      `[${embedding.join(',')}]`,
      category,
      minScore,
      topK
    ]);
    
    return result.rows.map(row => ({
      ...row,
      type: 'faq',
      source: row.sources ? row.sources.join(', ') : 'FAQ Verificada',
      text: `Pregunta: ${row.question}\n\nRespuesta: ${row.text}`
    }));
  }
  
  /**
   * Combina y ordena resultados de chunks y FAQs
   */
  combineResults(chunks, faqs) {
    // FAQs tienen un pequeño boost porque son respuestas verificadas
    const boostedFaqs = faqs.map(faq => ({
      ...faq,
      score: Math.min(faq.score * 1.1, 1.0) // 10% boost, max 1.0
    }));
    
    return [...boostedFaqs, ...chunks]
      .sort((a, b) => b.score - a.score);
  }
  
  /**
   * Diversifica resultados para evitar redundancia
   */
  diversifyResults(results, maxResults) {
    const selected = [];
    const seenSources = new Set();
    
    for (const result of results) {
      // Permitir máximo 2 chunks del mismo documento
      const sourceKey = result.source.split(' - ')[0];
      const sourceCount = Array.from(seenSources).filter(s => s.startsWith(sourceKey)).length;
      
      if (sourceCount < 2) {
        selected.push(result);
        seenSources.add(result.source);
      }
      
      if (selected.length >= maxResults) break;
    }
    
    return selected;
  }
  
  // ═══════════════════════════════════════════════════════════
  // INDEXACIÓN (Carga de documentos)
  // ═══════════════════════════════════════════════════════════
  
  /**
   * Indexa un nuevo documento normativo
   * @param {object} document - Documento a indexar
   * @returns {object} - Resultado de la indexación
   */
  async indexDocument(document) {
    console.log(`[RAG] Indexing document: ${document.title}`);
    
    try {
      // 1. Insertar documento en la tabla principal
      const docResult = await this.db.query(`
        INSERT INTO normativa_documents 
          (source, category, subcategory, title, description, original_url, 
           raw_content, effective_date, expiration_date)
        VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)
        RETURNING id
      `, [
        document.source,
        document.category,
        document.subcategory,
        document.title,
        document.description,
        document.originalUrl,
        document.content,
        document.effectiveDate,
        document.expirationDate
      ]);
      
      const documentId = docResult.rows[0].id;
      
      // 2. Hacer chunking del contenido
      const chunks = this.chunkDocument(document.content, document);
      console.log(`[RAG] Created ${chunks.length} chunks`);
      
      // 3. Generar embeddings para cada chunk
      const embeddings = await this.generateEmbeddings(chunks.map(c => c.text));
      
      // 4. Insertar chunks con embeddings
      for (let i = 0; i < chunks.length; i++) {
        await this.db.query(`
          INSERT INTO normativa_chunks 
            (document_id, chunk_index, chunk_text, article_reference, embedding)
          VALUES ($1, $2, $3, $4, $5::vector)
        `, [
          documentId,
          i,
          chunks[i].text,
          chunks[i].article,
          `[${embeddings[i].join(',')}]`
        ]);
      }
      
      console.log(`[RAG] Document indexed successfully: ${documentId}`);
      
      return {
        success: true,
        documentId,
        chunksCreated: chunks.length
      };
      
    } catch (error) {
      console.error('[RAG] Indexing error:', error);
      throw error;
    }
  }
  
  /**
   * Divide el documento en chunks semánticos
   */
  chunkDocument(content, metadata) {
    const chunks = [];
    const { maxChunkSize, chunkOverlap } = this.config;
    
    // Patrones para detectar secciones legales
    const sectionPatterns = [
      /(?:^|\n)(Artículo\s+\d+[º.]?\s*[.-]?\s*)/gi,
      /(?:^|\n)(Art\.\s*\d+[º.]?\s*)/gi,
      /(?:^|\n)(Disposición\s+(?:adicional|transitoria|final)\s+\w+)/gi,
      /(?:^|\n)(Apartado\s+\d+)/gi,
      /(?:^|\n)(Capítulo\s+[IVXLC]+)/gi,
      /(?:^|\n)(Sección\s+\d+)/gi
    ];
    
    // Intentar dividir por secciones legales primero
    let sections = [{ text: content, article: null }];
    
    for (const pattern of sectionPatterns) {
      const newSections = [];
      for (const section of sections) {
        const parts = section.text.split(pattern);
        let currentArticle = section.article;
        
        for (let i = 0; i < parts.length; i++) {
          const part = parts[i].trim();
          if (!part) continue;
          
          // Detectar si es un marcador de sección
          if (pattern.test(part)) {
            currentArticle = part.replace(/\s+/g, ' ').trim();
          } else {
            newSections.push({
              text: part,
              article: currentArticle
            });
          }
        }
      }
      if (newSections.length > sections.length) {
        sections = newSections;
      }
    }
    
    // Dividir secciones grandes en chunks
    for (const section of sections) {
      if (section.text.length <= maxChunkSize) {
        chunks.push(section);
      } else {
        // Dividir por párrafos
        const paragraphs = section.text.split(/\n\n+/);
        let currentChunk = '';
        
        for (const para of paragraphs) {
          if (currentChunk.length + para.length > maxChunkSize && currentChunk.length > 0) {
            chunks.push({
              text: currentChunk.trim(),
              article: section.article
            });
            // Mantener overlap
            const words = currentChunk.split(' ');
            currentChunk = words.slice(-Math.floor(chunkOverlap / 10)).join(' ') + '\n\n' + para;
          } else {
            currentChunk += (currentChunk ? '\n\n' : '') + para;
          }
        }
        
        if (currentChunk.trim()) {
          chunks.push({
            text: currentChunk.trim(),
            article: section.article
          });
        }
      }
    }
    
    return chunks;
  }
  
  /**
   * Genera embeddings en batch
   */
  async generateEmbeddings(texts) {
    const batchSize = 20;
    const embeddings = [];
    
    for (let i = 0; i < texts.length; i += batchSize) {
      const batch = texts.slice(i, i + batchSize);
      
      const response = await fetch('https://api.openai.com/v1/embeddings', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${this.openaiKey}`
        },
        body: JSON.stringify({
          model: this.config.embeddingModel,
          input: batch.map(t => t.substring(0, 8000))
        })
      });
      
      if (!response.ok) {
        throw new Error(`OpenAI Embeddings batch error: ${response.status}`);
      }
      
      const data = await response.json();
      embeddings.push(...data.data.map(d => d.embedding));
      
      // Rate limiting
      if (i + batchSize < texts.length) {
        await new Promise(resolve => setTimeout(resolve, 100));
      }
    }
    
    return embeddings;
  }
  
  /**
   * Indexa una FAQ verificada
   */
  async indexFaq(faq) {
    console.log(`[RAG] Indexing FAQ: ${faq.question.substring(0, 50)}...`);
    
    // Generar embedding de la pregunta + respuesta
    const textForEmbedding = `${faq.question} ${faq.answer}`;
    const embedding = await this.generateEmbedding(textForEmbedding);
    
    const result = await this.db.query(`
      INSERT INTO normativa_faqs 
        (category, subcategory, question, answer, source_references, 
         embedding, verified_by)
      VALUES ($1, $2, $3, $4, $5, $6::vector, $7)
      RETURNING id
    `, [
      faq.category,
      faq.subcategory,
      faq.question,
      faq.answer,
      faq.sourceReferences || [],
      `[${embedding.join(',')}]`,
      faq.verifiedBy
    ]);
    
    return {
      success: true,
      faqId: result.rows[0].id
    };
  }
  
  // ═══════════════════════════════════════════════════════════
  // MANTENIMIENTO
  // ═══════════════════════════════════════════════════════════
  
  /**
   * Obtiene estadísticas del índice
   */
  async getStats() {
    const result = await this.db.query(`
      SELECT 
        d.category,
        COUNT(DISTINCT d.id) as document_count,
        COUNT(DISTINCT c.id) as chunk_count,
        (SELECT COUNT(*) FROM normativa_faqs f WHERE f.category = d.category) as faq_count,
        MAX(d.updated_at) as last_update
      FROM normativa_documents d
      LEFT JOIN normativa_chunks c ON d.id = c.document_id
      GROUP BY d.category
    `);
    
    return result.rows;
  }
  
  /**
   * Reindexar un documento específico
   */
  async reindexDocument(documentId) {
    // Obtener documento
    const docResult = await this.db.query(
      'SELECT * FROM normativa_documents WHERE id = $1',
      [documentId]
    );
    
    if (docResult.rows.length === 0) {
      throw new Error(`Document not found: ${documentId}`);
    }
    
    const doc = docResult.rows[0];
    
    // Eliminar chunks existentes
    await this.db.query(
      'DELETE FROM normativa_chunks WHERE document_id = $1',
      [documentId]
    );
    
    // Reindexar
    const chunks = this.chunkDocument(doc.raw_content, doc);
    const embeddings = await this.generateEmbeddings(chunks.map(c => c.text));
    
    for (let i = 0; i < chunks.length; i++) {
      await this.db.query(`
        INSERT INTO normativa_chunks 
          (document_id, chunk_index, chunk_text, article_reference, embedding)
        VALUES ($1, $2, $3, $4, $5::vector)
      `, [
        documentId,
        i,
        chunks[i].text,
        chunks[i].article,
        `[${embeddings[i].join(',')}]`
      ]);
    }
    
    // Actualizar timestamp
    await this.db.query(
      'UPDATE normativa_documents SET updated_at = CURRENT_TIMESTAMP WHERE id = $1',
      [documentId]
    );
    
    return {
      success: true,
      chunksCreated: chunks.length
    };
  }
  
  /**
   * Verificar salud del índice
   */
  async healthCheck() {
    try {
      // Verificar conexión a DB
      await this.db.query('SELECT 1');
      
      // Verificar que hay documentos
      const stats = await this.getStats();
      const totalDocs = stats.reduce((sum, s) => sum + parseInt(s.document_count), 0);
      const totalChunks = stats.reduce((sum, s) => sum + parseInt(s.chunk_count), 0);
      
      // Verificar API de embeddings
      const testEmbedding = await this.generateEmbedding('test');
      const embeddingsOk = testEmbedding.length === this.config.embeddingDimension;
      
      return {
        status: 'healthy',
        database: 'connected',
        embeddings: embeddingsOk ? 'ok' : 'error',
        documents: totalDocs,
        chunks: totalChunks,
        categories: stats.map(s => s.category)
      };
      
    } catch (error) {
      return {
        status: 'unhealthy',
        error: error.message
      };
    }
  }
}

module.exports = {
  RAGService,
  RAG_CONFIG,
  QUERY_SYNONYMS
};
