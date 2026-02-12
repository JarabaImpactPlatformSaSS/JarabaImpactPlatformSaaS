SISTEMA DE RESEÑAS Y VALORACIONES
Reviews de Productos, Moderación y Social Proof
Vertical ComercioConecta
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Campo	Valor
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	73_ComercioConecta_Reviews_Ratings
Dependencias:	62_Commerce_Core, 66_Product_Catalog, 71_Local_SEO
Base:	54_AgroConecta_Reviews (~65% reutilizable)
 
1. Resumen Ejecutivo
Este documento especifica el Sistema de Reseñas y Valoraciones para ComercioConecta. El sistema permite a los clientes valorar productos y comercios, proporciona social proof para aumentar conversiones, e incluye moderación automatizada con IA para mantener la calidad del contenido.
1.1 Impacto de las Reseñas en Conversión
Estadística	Valor	Fuente
Consumidores que leen reseñas	93%	BrightLocal
Aumento conversión con reseñas	+270%	Spiegel Research
Impacto de pasar de 3 a 4 estrellas	+25% ventas	Harvard Business
Usuarios que confían como recomendación personal	84%	BrightLocal
Productos con >50 reseñas vs 0	+4.6% conversión	Bazaarvoice
Efecto de responder a reseñas negativas	+33% probabilidad revisión	ReviewTrackers
1.2 Objetivos del Sistema
• Recopilar reseñas verificadas de compradores reales
• Mostrar valoraciones agregadas en productos y tiendas
• Moderar contenido automáticamente con IA
• Incentivar reseñas con puntos de fidelidad
• Generar Schema.org Review para SEO
• Permitir respuestas de comercios a reseñas
1.3 Tipos de Reseñas
Tipo	Objeto	Verificación	Visibilidad
Reseña de Producto	product_retail	Compra verificada	Página de producto
Reseña de Comercio	merchant_profile	Compra en comercio	Página de tienda
Reseña de Pedido	retail_order	Pedido completado	Perfil cliente
Q&A de Producto	product_retail	Público	Página de producto
 
2. Arquitectura del Sistema
2.1 Diagrama de Componentes
┌─────────────────────────────────────────────────────────────────────┐ │                    REVIEWS & RATINGS SYSTEM                         │ ├─────────────────────────────────────────────────────────────────────┤ │                                                                     │ │  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │ │  │   Review     │  │   Rating     │  │    Moderation            │  │ │  │   Manager    │──│   Aggregator │──│    Engine                │  │ │  │              │  │              │  │                          │  │ │  └──────────────┘  └──────────────┘  └──────────────────────────┘  │ │                                                                     │ │  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │ │  │   Incentive  │  │   Response   │  │    Media                 │  │ │  │   Service    │──│   Manager    │──│    Handler               │  │ │  │              │  │              │  │                          │  │ │  └──────────────┘  └──────────────┘  └──────────────────────────┘  │ │                                                                     │ │  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │ │  │   Schema     │  │   Q&A        │  │    Analytics             │  │ │  │   Generator  │──│   Service    │──│    Service               │  │ │  │              │  │              │  │                          │  │ │  └──────────────┘  └──────────────┘  └──────────────────────────┘  │ │                                                                     │ └───────────────────────────────────────────────────────────────────┘                               │         ┌─────────────────────┼─────────────────────┐         ▼                     ▼                     ▼  ┌────────────┐        ┌────────────┐        ┌────────────┐  │  Product   │        │  Merchant  │        │   Order    │  │   Page     │        │   Page     │        │  Complete  │  └────────────┘        └────────────┘        └────────────┘
2.2 Flujo de Reseña
┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐ │  Order   │───▶│  Request │───▶│  Submit  │───▶│ Moderate │ │ Complete │    │  Review  │    │  Review  │    │  (AI)    │ └──────────┘    └──────────┘    └──────────┘    └──────────┘                                                      │                                     ┌────────────────┼────────────────┐                                     ▼                ▼                ▼                              ┌──────────┐    ┌──────────┐    ┌──────────┐                              │ Approved │    │  Queue   │    │ Rejected │                              │ Publish  │    │  Manual  │    │  Notify  │                              └──────────┘    └──────────┘    └──────────┘                                     │                                     ▼                              ┌──────────┐    ┌──────────┐                              │  Update  │───▶│  Award   │                              │  Rating  │    │  Points  │                              └──────────┘    └──────────┘
 
3. Entidades del Sistema
3.1 Entidad: product_review
Reseñas de productos por compradores.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
product_id	INT	Producto reseñado	FK product_retail.id, NOT NULL, INDEX
variation_id	INT	Variación específica	FK, NULLABLE
merchant_id	INT	Comercio del producto	FK merchant_profile.id, NOT NULL, INDEX
order_id	INT	Pedido de compra	FK retail_order.id, NULLABLE
order_item_id	INT	Línea de pedido	FK order_line_item.id, NULLABLE
user_id	INT	Usuario autor	FK, NULLABLE
author_name	VARCHAR(128)	Nombre mostrado	NOT NULL
author_email	VARCHAR(255)	Email del autor	NOT NULL, INDEX
rating	TINYINT	Puntuación 1-5	NOT NULL, CHECK (1-5)
title	VARCHAR(255)	Título de la reseña	NULLABLE
body	TEXT	Contenido de la reseña	NOT NULL, MIN 20 chars
pros	TEXT	Puntos positivos	NULLABLE
cons	TEXT	Puntos negativos	NULLABLE
is_verified_purchase	BOOLEAN	Compra verificada	DEFAULT FALSE
is_recommended	BOOLEAN	¿Recomienda?	NULLABLE
size_fit	VARCHAR(16)	Ajuste de talla	ENUM: small|true_to_size|large, NULLABLE
quality_rating	TINYINT	Calidad material	1-5, NULLABLE
value_rating	TINYINT	Relación calidad-precio	1-5, NULLABLE
status	VARCHAR(16)	Estado	ENUM: pending|approved|rejected|flagged
moderation_score	DECIMAL(3,2)	Score IA 0-1	NULLABLE
moderation_flags	JSON	Flags de moderación	NULLABLE
moderation_notes	TEXT	Notas del moderador	NULLABLE
helpful_count	INT	Votos útil	DEFAULT 0
not_helpful_count	INT	Votos no útil	DEFAULT 0
report_count	INT	Veces reportada	DEFAULT 0
merchant_response	TEXT	Respuesta del comercio	NULLABLE
merchant_response_at	DATETIME	Fecha respuesta	NULLABLE
points_awarded	INT	Puntos otorgados	DEFAULT 0
ip_address	VARCHAR(45)	IP del autor	NULLABLE
user_agent	VARCHAR(500)	User Agent	NULLABLE
created	DATETIME	Fecha creación	NOT NULL, INDEX
updated	DATETIME	Última modificación	NOT NULL
 
3.2 Entidad: merchant_review
Reseñas de comercios/tiendas (complementa Doc 71_Local_SEO).
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
merchant_id	INT	Comercio reseñado	FK merchant_profile.id, NOT NULL, INDEX
order_id	INT	Pedido de referencia	FK retail_order.id, NULLABLE
user_id	INT	Usuario autor	FK, NULLABLE
author_name	VARCHAR(128)	Nombre mostrado	NOT NULL
author_email	VARCHAR(255)	Email del autor	NOT NULL
overall_rating	TINYINT	Puntuación general 1-5	NOT NULL
service_rating	TINYINT	Atención al cliente	1-5, NULLABLE
shipping_rating	TINYINT	Envío/entrega	1-5, NULLABLE
quality_rating	TINYINT	Calidad productos	1-5, NULLABLE
title	VARCHAR(255)	Título	NULLABLE
body	TEXT	Contenido	NOT NULL
is_verified_purchase	BOOLEAN	Compra verificada	DEFAULT FALSE
visit_type	VARCHAR(16)	Tipo de visita	ENUM: online|in_store|click_collect
status	VARCHAR(16)	Estado	ENUM: pending|approved|rejected
merchant_response	TEXT	Respuesta	NULLABLE
merchant_response_at	DATETIME	Fecha respuesta	NULLABLE
source	VARCHAR(32)	Origen	ENUM: platform|google|facebook
external_id	VARCHAR(128)	ID externo (si importada)	NULLABLE
created	DATETIME	Fecha creación	NOT NULL
3.3 Entidad: review_media
Fotos y videos adjuntos a reseñas.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
review_id	INT	Reseña asociada	FK product_review.id, NOT NULL
review_type	VARCHAR(16)	Tipo de reseña	ENUM: product|merchant
file_id	INT	Archivo en Drupal	FK file_managed.fid, NOT NULL
media_type	VARCHAR(16)	Tipo de media	ENUM: image|video
caption	VARCHAR(255)	Descripción	NULLABLE
sort_order	INT	Orden de display	DEFAULT 0
is_approved	BOOLEAN	Media aprobada	DEFAULT FALSE
created	DATETIME	Fecha subida	NOT NULL
 
3.4 Entidad: review_vote
Votos de utilidad en reseñas.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
review_id	INT	Reseña votada	FK product_review.id, NOT NULL
review_type	VARCHAR(16)	Tipo de reseña	ENUM: product|merchant
user_id	INT	Usuario que vota	FK, NULLABLE
session_id	VARCHAR(64)	Sesión (si anónimo)	NULLABLE
vote_type	VARCHAR(16)	Tipo de voto	ENUM: helpful|not_helpful|report
report_reason	VARCHAR(32)	Motivo reporte	NULLABLE
created	DATETIME	Fecha del voto	NOT NULL
UNIQUE: (review_id, review_type, user_id) o (review_id, review_type, session_id)
3.5 Entidad: product_question
Preguntas y respuestas sobre productos.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
product_id	INT	Producto	FK product_retail.id, NOT NULL, INDEX
merchant_id	INT	Comercio	FK merchant_profile.id, NOT NULL
user_id	INT	Usuario que pregunta	FK, NULLABLE
author_name	VARCHAR(128)	Nombre autor	NOT NULL
author_email	VARCHAR(255)	Email autor	NOT NULL
question	TEXT	Pregunta	NOT NULL
status	VARCHAR(16)	Estado	ENUM: pending|published|answered|closed
answer	TEXT	Respuesta	NULLABLE
answered_by	VARCHAR(16)	Quién responde	ENUM: merchant|community|ai
answered_at	DATETIME	Fecha respuesta	NULLABLE
helpful_count	INT	Votos útil	DEFAULT 0
created	DATETIME	Fecha creación	NOT NULL
 
3.6 Entidad: rating_summary
Resumen agregado de valoraciones por producto/comercio (caché desnormalizado).
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
entity_type	VARCHAR(32)	Tipo de entidad	ENUM: product|merchant
entity_id	INT	ID de la entidad	NOT NULL
average_rating	DECIMAL(3,2)	Media de valoraciones	NOT NULL
total_reviews	INT	Total de reseñas	NOT NULL
verified_reviews	INT	Reseñas verificadas	NOT NULL
rating_1_count	INT	Reseñas 1 estrella	DEFAULT 0
rating_2_count	INT	Reseñas 2 estrellas	DEFAULT 0
rating_3_count	INT	Reseñas 3 estrellas	DEFAULT 0
rating_4_count	INT	Reseñas 4 estrellas	DEFAULT 0
rating_5_count	INT	Reseñas 5 estrellas	DEFAULT 0
recommend_percent	INT	% que recomienda	NULLABLE
avg_quality_rating	DECIMAL(3,2)	Media calidad	NULLABLE
avg_value_rating	DECIMAL(3,2)	Media valor	NULLABLE
with_photos_count	INT	Con fotos	DEFAULT 0
last_review_at	DATETIME	Última reseña	NULLABLE
updated	DATETIME	Última actualización	NOT NULL
UNIQUE: (entity_type, entity_id)
 
4. Servicios Principales
4.1 ReviewService
<?php namespace Drupal\jaraba_reviews\Service;  class ReviewService {    // CRUD   public function createProductReview(array $data): ProductReview;   public function createMerchantReview(array $data): MerchantReview;   public function update(Review $review, array $data): Review;   public function delete(Review $review): bool;      // Consultas   public function getProductReviews(int $productId, array $filters = []): array;   public function getMerchantReviews(int $merchantId, array $filters = []): array;   public function getUserReviews(int $userId): array;   public function getPendingReviews(int $merchantId): array;      // Verificación   public function verifyPurchase(int $productId, string $email): ?RetailOrder;   public function canReview(int $productId, string $email): bool;   public function hasReviewed(int $productId, string $email): bool;      // Respuestas   public function addMerchantResponse(Review $review, string $response): void;   public function updateMerchantResponse(Review $review, string $response): void;      // Utilidades   public function markAsHelpful(Review $review, int $userId): void;   public function reportReview(Review $review, string $reason, int $userId): void; }
4.2 RatingAggregatorService
<?php namespace Drupal\jaraba_reviews\Service;  class RatingAggregatorService {    // Agregación   public function recalculate(string $entityType, int $entityId): RatingSummary;   public function recalculateAll(string $entityType): int;      // Consultas   public function getSummary(string $entityType, int $entityId): ?RatingSummary;   public function getProductRating(int $productId): ?RatingSummary;   public function getMerchantRating(int $merchantId): ?RatingSummary;      // Estadísticas   public function getDistribution(string $entityType, int $entityId): array;   public function getTrend(string $entityType, int $entityId, int $months = 6): array;   public function getTopRated(string $entityType, int $limit = 10): array;      // Cálculos   public function calculateWeightedAverage(array $reviews): float;   public function calculateBayesianAverage(array $reviews, float $prior = 3.5): float; }
 
4.3 ModerationService
<?php namespace Drupal\jaraba_reviews\Service;  class ModerationService {    // Moderación automática   public function moderate(Review $review): ModerationResult;   public function analyzeContent(string $text): ContentAnalysis;   public function detectSpam(Review $review): float;   public function detectProfanity(string $text): array;   public function detectSentiment(string $text): string;      // Moderación manual   public function approve(Review $review, int $moderatorId): void;   public function reject(Review $review, string $reason, int $moderatorId): void;   public function flag(Review $review, string $reason): void;      // Cola de moderación   public function getQueue(int $merchantId = null): array;   public function getPriority(Review $review): int;      // Configuración   public function getAutoApproveThreshold(): float;   public function getAutoRejectThreshold(): float;   public function getBannedWords(): array; }
4.4 Reglas de Moderación Automática
// Configuración de moderación automática con IA $moderationConfig = [   // Umbrales de aprobación automática   'auto_approve' => [     'min_score' => 0.85,           // Score IA > 85%     'verified_purchase' => true,   // Solo compras verificadas     'min_rating' => 3,             // Rating >= 3     'no_links' => true,            // Sin URLs     'no_profanity' => true,        // Sin palabrotas     'min_length' => 50,            // Mínimo 50 caracteres   ],      // Rechazo automático   'auto_reject' => [     'spam_score' => 0.9,           // Score spam > 90%     'profanity_count' => 3,        // 3+ palabrotas     'competitor_mention' => true,  // Menciona competidores     'contact_info' => true,        // Incluye teléfono/email   ],      // Flags para revisión manual   'manual_review_flags' => [     'low_rating_verified',         // 1-2 estrellas compra verificada     'first_review_merchant',       // Primera reseña del comercio     'contains_question',           // Contiene pregunta     'media_attached',              // Tiene fotos/videos     'high_report_count',           // Reportada 2+ veces   ],      // Palabras prohibidas (ejemplos)   'banned_words' => [     'estafa', 'timo', 'fraude', 'robo',     // + lista de palabrotas   ],      // Patrones sospechosos   'suspicious_patterns' => [     'all_caps' => 0.3,             // Más del 30% mayúsculas     'repeated_chars' => 3,         // 3+ caracteres repetidos     'excessive_punctuation' => 5,  // 5+ signos seguidos   ] ];
 
4.5 IncentiveService
<?php namespace Drupal\jaraba_reviews\Service;  class IncentiveService {    // Puntos por reseñas   public function calculatePoints(Review $review): int;   public function awardPoints(Review $review): void;   public function revokePoints(Review $review): void;      // Configuración de puntos   private array $pointsConfig = [     'basic_review' => 10,          // Reseña básica     'verified_purchase' => 15,     // +15 si compra verificada     'with_photo' => 20,            // +20 por cada foto (max 3)     'with_video' => 50,            // +50 por video     'detailed_review' => 10,       // +10 si >200 caracteres     'first_review' => 25,          // +25 primera reseña del usuario     'helpful_bonus' => 5,          // +5 por cada 10 votos útil   ];      // Solicitud de reseñas   public function sendReviewRequest(RetailOrder $order): void;   public function scheduleReminder(RetailOrder $order, int $days = 7): void;   public function getOptimalRequestTime(RetailOrder $order): \DateTime;      // Gamificación   public function getReviewerLevel(int $userId): string;   public function getReviewerBadges(int $userId): array;   public function checkBadgeUnlock(int $userId): ?Badge; }
4.6 Sistema de Badges para Reviewers
Badge	Requisito	Puntos Bonus	Icono
Novato	1 reseña	0	⭐
Colaborador	5 reseñas	+10	✨
Experto	20 reseñas	+25	🌟
Top Reviewer	50 reseñas	+50	👑
Fotógrafo	10 reseñas con foto	+15	📸
Videógrafo	5 reseñas con video	+30	🎬
Útil	50 votos útil recibidos	+20	👍
Verificado	10 compras verificadas	+15	✓
 
5. Sistema de Solicitud de Reseñas
5.1 Flujo Post-Compra
// Flujo automatizado de solicitud de reseñas  // Timeline óptimo: // Día 0: Pedido entregado // Día 3: Email de satisfacción (sin pedir reseña) // Día 7: Email solicitando reseña + incentivo puntos // Día 14: Reminder si no ha dejado reseña // Día 21: Último reminder  $reviewRequestFlow = [   // Email 1: Satisfacción (día 3)   [     'delay_days' => 3,     'template' => 'order_satisfaction',     'subject' => '¿Qué tal tu compra en {merchant}?',     'ask_review' => false,     'include_nps' => true,  // Net Promoter Score   ],      // Email 2: Solicitud de reseña (día 7)   [     'delay_days' => 7,     'template' => 'review_request',     'subject' => 'Tu opinión vale: Cuéntanos qué tal {product}',     'ask_review' => true,     'incentive' => '¡Gana 50 puntos por tu reseña!',     'direct_link' => true,  // Link directo al formulario   ],      // Email 3: Reminder (día 14)   [     'delay_days' => 14,     'condition' => 'no_review_submitted',     'template' => 'review_reminder',     'subject' => 'Aún estás a tiempo: comparte tu experiencia',     'incentive' => '¡+10 puntos extra si lo haces esta semana!',   ],      // Email 4: Último intento (día 21)   [     'delay_days' => 21,     'condition' => 'no_review_submitted',     'template' => 'review_final_reminder',     'subject' => 'Última oportunidad para ganar puntos',     'urgency' => true,   ] ];
5.2 ReviewRequestService
<?php namespace Drupal\jaraba_reviews\Service;  class ReviewRequestService {    // Solicitud   public function sendRequest(RetailOrder $order): void;   public function scheduleRequest(RetailOrder $order): void;   public function cancelScheduledRequests(RetailOrder $order): void;      // Generación de links   public function generateReviewLink(RetailOrder $order, int $productId): string;   public function generateOneClickReviewLink(RetailOrder $order, int $rating): string;   public function validateReviewToken(string $token): ?ReviewContext;      // Optimización   public function getOptimalSendTime(RetailOrder $order): \DateTime;   public function shouldSendRequest(RetailOrder $order): bool;      // Métricas   public function getRequestStats(int $merchantId): array;   public function getConversionRate(int $merchantId): float;   public function getAverageResponseTime(int $merchantId): float; }
5.3 One-Click Review (Email)
// Sistema de valoración rápida desde el email // El usuario puede dar estrellas directamente sin abrir el formulario  // En el email: "¿Cómo valorarías {producto}?"  ⭐ ⭐ ⭐ ⭐ ⭐ [1] [2] [3] [4] [5]  ← Links clickeables  // Cada estrella es un link: https://comercioconecta.es/review/quick?   token=abc123   &product=456   &rating=5   &order=789  // Al hacer clic: // 1. Se pre-selecciona el rating // 2. Se abre formulario para completar texto // 3. Si rating >= 4: sugerir compartir en Google // 4. Si rating <= 2: capturar feedback privado primero
 
6. Sistema de Preguntas y Respuestas
6.1 QAService
<?php namespace Drupal\jaraba_reviews\Service;  class QAService {    // Preguntas   public function askQuestion(int $productId, array $data): ProductQuestion;   public function getQuestions(int $productId, array $filters = []): array;   public function searchQuestions(int $productId, string $query): array;      // Respuestas   public function answerByMerchant(ProductQuestion $question, string $answer): void;   public function answerByCommunity(ProductQuestion $question, string $answer, int $userId): void;   public function suggestAIAnswer(ProductQuestion $question): ?string;      // Moderación   public function moderateQuestion(ProductQuestion $question): ModerationResult;   public function flagQuestion(ProductQuestion $question, string $reason): void;      // Notificaciones   public function notifyMerchant(ProductQuestion $question): void;   public function notifyAsker(ProductQuestion $question): void;      // Métricas   public function getUnansweredCount(int $merchantId): int;   public function getAverageResponseTime(int $merchantId): float; }
6.2 Respuesta Automática con IA
// Sistema de sugerencia de respuestas con IA  public function suggestAIAnswer(ProductQuestion $question): ?string {   $product = $this->productService->load($question->product_id);      // Contexto para la IA   $context = [     'product_title' => $product->title,     'product_description' => $product->body,     'product_attributes' => $product->getAttributes(),     'existing_qa' => $this->getQuestions($product->id, ['answered' => true]),     'product_reviews' => $this->reviewService->getProductReviews($product->id),   ];      // Prompt para generar respuesta   $prompt = $this->buildQAPrompt($question->question, $context);      // Llamar a la IA con strict grounding   $response = $this->aiService->generate($prompt, [     'temperature' => 0.3,  // Baja creatividad, alta precisión     'max_tokens' => 200,     'grounding' => 'strict',  // Solo info del producto   ]);      // Si la IA no puede responder con certeza   if ($response->confidence < 0.7) {     return null;  // Requiere respuesta humana   }      return $response->text; }  // La respuesta se sugiere al comercio, no se publica automáticamente // El comercio puede aprobar, editar o escribir su propia respuesta
6.3 Detección de Preguntas Frecuentes
// Detectar y agrupar preguntas similares  public function detectFAQs(int $productId): array {   $questions = $this->getQuestions($productId, ['status' => 'answered']);      // Agrupar por similitud semántica   $clusters = $this->clusterSimilarQuestions($questions);      $faqs = [];   foreach ($clusters as $cluster) {     if (count($cluster) >= 3) {  // 3+ preguntas similares = FAQ       $faqs[] = [         'canonical_question' => $this->selectBestQuestion($cluster),         'canonical_answer' => $this->selectBestAnswer($cluster),         'variants' => count($cluster),         'helpful_count' => array_sum(array_column($cluster, 'helpful_count')),       ];     }   }      // Ordenar por relevancia   usort($faqs, fn($a, $b) => $b['helpful_count'] - $a['helpful_count']);      return $faqs; }  // Las FAQs detectadas se pueden: // 1. Mostrar destacadas en la página del producto // 2. Usar para generar Schema.org FAQPage // 3. Auto-responder preguntas nuevas similares
 
7. Schema.org para Reseñas
7.1 ReviewSchemaService
<?php namespace Drupal\jaraba_reviews\Service;  class ReviewSchemaService {    // Generación de schemas   public function generateProductReviewSchema(ProductReview $review): array;   public function generateAggregateRating(int $productId): array;   public function generateMerchantAggregateRating(int $merchantId): array;      // Renderizado   public function injectIntoPage(int $productId): void;   public function toJsonLd(array $schema): string; }
7.2 Schema Review + AggregateRating
// Schema.org para producto con reseñas {   "@context": "https://schema.org",   "@type": "Product",   "name": "Camiseta Básica Algodón Orgánico",   "image": "https://comercioconecta.es/.../camiseta.jpg",   "description": "Camiseta de algodón 100% orgánico...",   "sku": "CAM-BAS-001",   "brand": {     "@type": "Brand",     "name": "EcoBasics"   },      "aggregateRating": {     "@type": "AggregateRating",     "ratingValue": "4.6",     "reviewCount": "127",     "bestRating": "5",     "worstRating": "1"   },      "review": [     {       "@type": "Review",       "author": {         "@type": "Person",         "name": "María G."       },       "datePublished": "2026-01-10",       "reviewRating": {         "@type": "Rating",         "ratingValue": "5",         "bestRating": "5"       },       "reviewBody": "Excelente calidad, muy cómoda y el algodón se siente premium.",       "itemReviewed": {         "@type": "Product",         "name": "Camiseta Básica Algodón Orgánico"       }     },     {       "@type": "Review",       "author": {         "@type": "Person",         "name": "Carlos R."       },       "datePublished": "2026-01-08",       "reviewRating": {         "@type": "Rating",         "ratingValue": "4",         "bestRating": "5"       },       "reviewBody": "Buena camiseta, aunque talla un poco grande."     }   ] }
 
8. Componentes Frontend
8.1 Arquitectura de Componentes
src/ ├── components/ │   ├── reviews/ │   │   ├── ReviewList.jsx          // Lista de reseñas │   │   ├── ReviewCard.jsx          // Tarjeta individual │   │   ├── ReviewForm.jsx          // Formulario de reseña │   │   ├── ReviewSummary.jsx       // Resumen agregado │   │   ├── RatingStars.jsx         // Componente de estrellas │   │   ├── RatingDistribution.jsx  // Barras de distribución │   │   ├── ReviewFilters.jsx       // Filtros de reseñas │   │   ├── ReviewMedia.jsx         // Galería de fotos │   │   ├── MerchantResponse.jsx    // Respuesta del comercio │   │   └── ReviewHelpful.jsx       // Botones de utilidad │   │ │   ├── qa/ │   │   ├── QuestionList.jsx        // Lista de Q&A │   │   ├── QuestionForm.jsx        // Hacer pregunta │   │   ├── AnswerForm.jsx          // Responder pregunta │   │   └── FAQSection.jsx          // FAQs destacadas │   │ │   └── widgets/ │       ├── ProductRatingBadge.jsx  // Badge en cards │       ├── MerchantRatingBadge.jsx // Badge de tienda │       └── ReviewSnippet.jsx       // Snippet para SEO
8.2 ReviewSummary Component
// ReviewSummary.jsx export function ReviewSummary({ productId }) {   const { data: summary } = useQuery(['rating-summary', productId]);      if (!summary) return <ReviewSummarySkeleton />;      return (     <div className="review-summary">       <div className="summary-main">         <span className="average-rating">{summary.average_rating.toFixed(1)}</span>         <RatingStars rating={summary.average_rating} size="lg" />         <span className="total-reviews">           {summary.total_reviews} reseñas         </span>       </div>              <RatingDistribution distribution={{         5: summary.rating_5_count,         4: summary.rating_4_count,         3: summary.rating_3_count,         2: summary.rating_2_count,         1: summary.rating_1_count,       }} />              {summary.recommend_percent && (         <div className="recommend-stat">           <ThumbsUpIcon />           <span>{summary.recommend_percent}% lo recomienda</span>         </div>       )}              <div className="additional-ratings">         {summary.avg_quality_rating && (           <MiniRating label="Calidad" value={summary.avg_quality_rating} />         )}         {summary.avg_value_rating && (           <MiniRating label="Precio" value={summary.avg_value_rating} />         )}       </div>     </div>   ); }
 
8.3 ReviewForm Component
// ReviewForm.jsx export function ReviewForm({ productId, orderId, onSubmit }) {   const [rating, setRating] = useState(0);   const [title, setTitle] = useState('');   const [body, setBody] = useState('');   const [pros, setPros] = useState('');   const [cons, setCons] = useState('');   const [sizeFit, setSizeFit] = useState(null);   const [photos, setPhotos] = useState([]);   const [recommend, setRecommend] = useState(null);      const handleSubmit = async (e) => {     e.preventDefault();          const formData = new FormData();     formData.append('product_id', productId);     formData.append('order_id', orderId);     formData.append('rating', rating);     formData.append('title', title);     formData.append('body', body);     formData.append('pros', pros);     formData.append('cons', cons);     formData.append('size_fit', sizeFit);     formData.append('is_recommended', recommend);          photos.forEach((photo, i) => {       formData.append(`photos[${i}]`, photo);     });          await onSubmit(formData);   };      return (     <form onSubmit={handleSubmit} className="review-form">       <div className="rating-input">         <label>Tu valoración *</label>         <RatingStarsInput value={rating} onChange={setRating} />       </div>              <div className="title-input">         <label>Título de tu reseña</label>         <input value={title} onChange={e => setTitle(e.target.value)}                 placeholder="Resume tu experiencia" maxLength={100} />       </div>              <div className="body-input">         <label>Tu reseña *</label>         <textarea value={body} onChange={e => setBody(e.target.value)}                   placeholder="Cuéntanos tu experiencia con el producto..."                   minLength={20} rows={5} />         <span className="char-count">{body.length}/2000</span>       </div>              <div className="pros-cons">         <div>           <label>👍 Lo mejor</label>           <textarea value={pros} onChange={e => setPros(e.target.value)} />         </div>         <div>           <label>👎 A mejorar</label>           <textarea value={cons} onChange={e => setCons(e.target.value)} />         </div>       </div>              <SizeFitSelector value={sizeFit} onChange={setSizeFit} />              <PhotoUploader photos={photos} onChange={setPhotos} max={5} />              <RecommendToggle value={recommend} onChange={setRecommend} />              <button type="submit" disabled={rating === 0 || body.length < 20}>         Enviar reseña       </button>              <p className="incentive-note">         🎁 ¡Gana hasta 50 puntos por tu reseña!       </p>     </form>   ); }
 
9. APIs REST
9.1 Endpoints Públicos
Método	Endpoint	Descripción	Auth
GET	/api/v1/products/{id}/reviews	Reseñas de producto	Público
GET	/api/v1/products/{id}/rating	Rating agregado	Público
GET	/api/v1/merchants/{id}/reviews	Reseñas de comercio	Público
GET	/api/v1/products/{id}/questions	Q&A de producto	Público
POST	/api/v1/products/{id}/reviews	Crear reseña	Session
POST	/api/v1/products/{id}/questions	Hacer pregunta	Session
9.2 Endpoints de Usuario
Método	Endpoint	Descripción	Auth
GET	/api/v1/my/reviews	Mis reseñas	User
GET	/api/v1/my/reviews/pending	Productos pendientes de reseña	User
PATCH	/api/v1/reviews/{id}	Editar mi reseña	User
DELETE	/api/v1/reviews/{id}	Eliminar mi reseña	User
POST	/api/v1/reviews/{id}/vote	Votar reseña	Session
POST	/api/v1/reviews/{id}/report	Reportar reseña	Session
9.3 Endpoints de Comercio
Método	Endpoint	Descripción	Auth
GET	/api/v1/merchant/reviews	Reseñas del comercio	Merchant
GET	/api/v1/merchant/reviews/pending	Cola de moderación	Merchant
POST	/api/v1/reviews/{id}/respond	Responder reseña	Merchant
POST	/api/v1/reviews/{id}/approve	Aprobar reseña	Merchant
POST	/api/v1/reviews/{id}/reject	Rechazar reseña	Merchant
GET	/api/v1/merchant/questions/unanswered	Preguntas sin responder	Merchant
POST	/api/v1/questions/{id}/answer	Responder pregunta	Merchant
 
10. Flujos de Automatización (ECA)
10.1 ECA-REV-001: Pedido Completado
Trigger: Order state = 'completed'
1. Programar solicitud de reseña (día 3, 7, 14, 21)
2. Generar token único para review link
3. Marcar productos como 'pendientes de reseña'
10.2 ECA-REV-002: Reseña Enviada
Trigger: POST /api/v1/products/{id}/reviews
1. Ejecutar moderación automática (IA)
2. Si auto-approve → publicar y actualizar rating
3. Si queue → notificar a comercio para moderación
4. Calcular y asignar puntos de fidelidad
5. Cancelar solicitudes pendientes para este producto
10.3 ECA-REV-003: Reseña Aprobada
Trigger: Review status = 'approved'
1. Recalcular rating_summary del producto
2. Recalcular rating_summary del comercio
3. Actualizar Schema.org en página de producto
4. Notificar al autor: 'Tu reseña ha sido publicada'
5. Verificar y otorgar badges si aplica
10.4 ECA-REV-004: Reseña Negativa (1-2 ⭐)
Trigger: Review con rating <= 2 AND verified_purchase = true
1. Alertar al comercio inmediatamente (email + push)
2. Crear tarea de seguimiento en dashboard
3. Sugerir plantilla de respuesta empática
4. Programar reminder si no hay respuesta en 24h
10.5 ECA-REV-005: Pregunta Sin Responder
Trigger: Question sin answer después de 24h
1. Intentar generar respuesta con IA
2. Si confianza > 0.7 → sugerir al comercio
3. Enviar reminder al comercio
 
11. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Entidades: product_review, rating_summary. ReviewService básico. CRUD de reseñas.	66_Product_Catalog
Sprint 2	Semana 3-4	RatingAggregatorService. Cálculo de medias. ReviewSummary component.	Sprint 1
Sprint 3	Semana 5-6	ModerationService. Integración IA. Cola de moderación. Dashboard comercio.	Sprint 2
Sprint 4	Semana 7-8	IncentiveService. Sistema de puntos. ReviewRequestService. Emails post-compra.	Sprint 3
Sprint 5	Semana 9-10	QAService. Preguntas y respuestas. Sugerencias IA. FAQs automáticas.	Sprint 4
Sprint 6	Semana 11-12	ReviewSchemaService. Schema.org. Media upload. Flujos ECA. QA y go-live.	Sprint 5
11.1 Criterios de Aceptación Sprint 2
✓ Crear reseña con rating, título y cuerpo
✓ Verificar compra antes de permitir reseña
✓ Calcular y mostrar rating agregado
✓ Filtrar reseñas por rating
✓ Componentes React funcionales
11.2 Dependencias
• 66_Product_Catalog (productos)
• 71_Local_SEO (merchant_review base)
• Sistema de fidelización (puntos)
• AI Service para moderación
• Drupal Media module para fotos
--- Fin del Documento ---
73_ComercioConecta_Reviews_Ratings_v1.docx | Jaraba Impact Platform | Enero 2026
