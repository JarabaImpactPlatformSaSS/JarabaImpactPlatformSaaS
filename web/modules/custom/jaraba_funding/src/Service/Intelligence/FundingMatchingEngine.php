<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Service\Intelligence;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Motor de matching IA con 5 criterios ponderados.
 *
 * Calcula la puntuacion de coincidencia entre una suscripcion de
 * subvenciones (perfil del tenant) y una convocatoria, utilizando
 * 5 criterios ponderados: region, tipo de beneficiario, sector,
 * tamano de empresa y similitud semantica.
 *
 * ARQUITECTURA:
 * - 5 criterios con pesos configurables:
 *   region (20%), beneficiary (25%), sector (20%), size (15%), semantic (20%).
 * - Threshold minimo configurable (default 60.0).
 * - Matching bidireccional: por suscripcion y por convocatoria.
 * - Semantico como stub preparado para Qdrant.
 *
 * RELACIONES:
 * - FundingMatchingEngine -> FundingSubscription entity (perfil tenant)
 * - FundingMatchingEngine -> FundingCall entity (convocatoria)
 * - FundingMatchingEngine -> FundingMatch entity (resultado)
 * - FundingMatchingEngine -> jaraba_funding.settings (configuracion)
 */
class FundingMatchingEngine {

  /**
   * Pesos de los criterios de matching.
   *
   * @var array<string, float>
   */
  protected array $weights = [
    'region' => 0.20,
    'beneficiary' => 0.25,
    'sector' => 0.20,
    'size' => 0.15,
    'semantic' => 0.20,
  ];

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Factory de configuracion.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del modulo.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $configFactory,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Calcula el matching entre una suscripcion y una convocatoria.
   *
   * @param \Drupal\Core\Entity\EntityInterface $subscription
   *   Entidad FundingSubscription con el perfil del tenant.
   * @param \Drupal\Core\Entity\EntityInterface $call
   *   Entidad FundingCall con los datos de la convocatoria.
   *
   * @return array
   *   Resultado del matching con claves:
   *   - overall_score: (float) Puntuacion global ponderada (0-100).
   *   - region_score: (float) Puntuacion de region (0-100).
   *   - beneficiary_score: (float) Puntuacion de beneficiario (0-100).
   *   - sector_score: (float) Puntuacion de sector (0-100).
   *   - size_score: (float) Puntuacion de tamano (0-100).
   *   - semantic_score: (float) Puntuacion semantica (0-100).
   *   - breakdown: (array) Detalle de cada criterio.
   */
  public function calculateMatch(EntityInterface $subscription, EntityInterface $call): array {
    $regionScore = $this->calculateRegionScore($subscription, $call);
    $beneficiaryScore = $this->calculateBeneficiaryScore($subscription, $call);
    $sectorScore = $this->calculateSectorScore($subscription, $call);
    $sizeScore = $this->calculateSizeScore($subscription, $call);
    $semanticScore = $this->calculateSemanticScore($subscription, $call);

    $overallScore = ($regionScore * $this->weights['region'])
      + ($beneficiaryScore * $this->weights['beneficiary'])
      + ($sectorScore * $this->weights['sector'])
      + ($sizeScore * $this->weights['size'])
      + ($semanticScore * $this->weights['semantic']);

    $overallScore = round($overallScore, 2);

    return [
      'overall_score' => $overallScore,
      'region_score' => $regionScore,
      'beneficiary_score' => $beneficiaryScore,
      'sector_score' => $sectorScore,
      'size_score' => $sizeScore,
      'semantic_score' => $semanticScore,
      'breakdown' => [
        'region' => [
          'score' => $regionScore,
          'weight' => $this->weights['region'],
          'weighted' => round($regionScore * $this->weights['region'], 2),
        ],
        'beneficiary' => [
          'score' => $beneficiaryScore,
          'weight' => $this->weights['beneficiary'],
          'weighted' => round($beneficiaryScore * $this->weights['beneficiary'], 2),
        ],
        'sector' => [
          'score' => $sectorScore,
          'weight' => $this->weights['sector'],
          'weighted' => round($sectorScore * $this->weights['sector'], 2),
        ],
        'size' => [
          'score' => $sizeScore,
          'weight' => $this->weights['size'],
          'weighted' => round($sizeScore * $this->weights['size'], 2),
        ],
        'semantic' => [
          'score' => $semanticScore,
          'weight' => $this->weights['semantic'],
          'weighted' => round($semanticScore * $this->weights['semantic'], 2),
        ],
      ],
    ];
  }

  /**
   * Calcula puntuacion de region. Peso: 20%.
   *
   * - 100 si la convocatoria es nacional o coincide exactamente con la region.
   * - 50 si la convocatoria es de una region relacionada.
   * - 0 en caso contrario.
   *
   * @param \Drupal\Core\Entity\EntityInterface $sub
   *   Entidad FundingSubscription.
   * @param \Drupal\Core\Entity\EntityInterface $call
   *   Entidad FundingCall.
   *
   * @return float
   *   Puntuacion de 0 a 100.
   */
  public function calculateRegionScore(EntityInterface $sub, EntityInterface $call): float {
    $callRegion = $call->get('region')->value ?? '';
    $subRegion = $sub->get('region')->value ?? '';

    // Convocatoria nacional: accesible para todos.
    if ($callRegion === 'nacional' || $callRegion === 'estatal') {
      return 100.0;
    }

    // Coincidencia exacta.
    if (!empty($callRegion) && $callRegion === $subRegion) {
      return 100.0;
    }

    // Regiones relacionadas (por proximidad o acuerdos).
    $related = $this->getRelatedRegions($subRegion);
    if (in_array($callRegion, $related, TRUE)) {
      return 50.0;
    }

    return 0.0;
  }

  /**
   * Calcula puntuacion de tipo de beneficiario. Peso: 25%.
   *
   * - 100 si hay coincidencia directa en tipo de beneficiario.
   * - 90 si hay inclusion (e.g., 'pyme' incluye 'autonomo').
   * - 20 si no coincide pero no hay restriccion explicita.
   * - 0 si esta explicitamente excluido.
   *
   * @param \Drupal\Core\Entity\EntityInterface $sub
   *   Entidad FundingSubscription.
   * @param \Drupal\Core\Entity\EntityInterface $call
   *   Entidad FundingCall.
   *
   * @return float
   *   Puntuacion de 0 a 100.
   */
  public function calculateBeneficiaryScore(EntityInterface $sub, EntityInterface $call): float {
    $callTypes = $call->get('beneficiary_types')->value ?? [];
    $subType = $sub->get('beneficiary_type')->value ?? '';

    if (!is_array($callTypes)) {
      $callTypes = [$callTypes];
    }
    $callTypes = array_filter($callTypes);

    // Sin restriccion de beneficiarios en la convocatoria.
    if (empty($callTypes)) {
      return 70.0;
    }

    // Coincidencia directa.
    if (in_array($subType, $callTypes, TRUE)) {
      return 100.0;
    }

    // Inclusion jerarquica.
    $inclusions = [
      'pyme' => ['autonomo', 'micropyme', 'cooperativa', 'sociedad_civil', 'comunidad_bienes'],
      'empresa' => ['pyme', 'autonomo', 'micropyme', 'gran_empresa', 'cooperativa'],
      'persona_fisica' => ['autonomo'],
      'sin_animo_lucro' => ['asociacion', 'fundacion'],
    ];

    foreach ($callTypes as $callType) {
      if (isset($inclusions[$callType]) && in_array($subType, $inclusions[$callType], TRUE)) {
        return 90.0;
      }
    }

    // No coincide pero sin restriccion explicita.
    return 20.0;
  }

  /**
   * Calcula puntuacion de sector. Peso: 20%.
   *
   * - 70 si la convocatoria no tiene restriccion de sector.
   * - 100 si coincidencia total entre sectores.
   * - 60-90 segun grado de interseccion.
   * - 30 si hay baja relacion.
   *
   * @param \Drupal\Core\Entity\EntityInterface $sub
   *   Entidad FundingSubscription.
   * @param \Drupal\Core\Entity\EntityInterface $call
   *   Entidad FundingCall.
   *
   * @return float
   *   Puntuacion de 0 a 100.
   */
  public function calculateSectorScore(EntityInterface $sub, EntityInterface $call): float {
    $callSectors = $call->get('sectors')->value ?? [];
    $subSectors = $sub->get('sectors')->value ?? [];

    if (!is_array($callSectors)) {
      $callSectors = [$callSectors];
    }
    if (!is_array($subSectors)) {
      $subSectors = [$subSectors];
    }

    $callSectors = array_filter($callSectors);
    $subSectors = array_filter($subSectors);

    // Convocatoria sin restriccion de sector.
    if (empty($callSectors)) {
      return 70.0;
    }

    // Suscripcion sin sectores definidos.
    if (empty($subSectors)) {
      return 30.0;
    }

    // Calcular interseccion.
    $intersection = array_intersect($subSectors, $callSectors);
    $intersectionCount = count($intersection);

    if ($intersectionCount === 0) {
      return 30.0;
    }

    // Ratio de coincidencia sobre los sectores de la convocatoria.
    $ratio = $intersectionCount / count($callSectors);

    // Escalar entre 60 y 100.
    return round(60.0 + ($ratio * 40.0), 2);
  }

  /**
   * Calcula puntuacion de tamano de empresa. Peso: 15%.
   *
   * Evalua si el tamano de la empresa (empleados + facturacion)
   * coincide con los requisitos de la convocatoria.
   *
   * @param \Drupal\Core\Entity\EntityInterface $sub
   *   Entidad FundingSubscription.
   * @param \Drupal\Core\Entity\EntityInterface $call
   *   Entidad FundingCall.
   *
   * @return float
   *   Puntuacion de 0 a 100.
   */
  public function calculateSizeScore(EntityInterface $sub, EntityInterface $call): float {
    $score = 0.0;
    $checks = 0;

    // Verificar rango de empleados.
    $subEmployees = (int) ($sub->get('employees')->value ?? 0);
    $callMinEmployees = (int) ($call->get('min_employees')->value ?? 0);
    $callMaxEmployees = (int) ($call->get('max_employees')->value ?? 0);

    if ($callMinEmployees > 0 || $callMaxEmployees > 0) {
      $checks++;
      if ($subEmployees >= $callMinEmployees && ($callMaxEmployees === 0 || $subEmployees <= $callMaxEmployees)) {
        $score += 100.0;
      }
      elseif ($subEmployees > 0) {
        // Puntuacion parcial por cercania.
        if ($callMaxEmployees > 0 && $subEmployees > $callMaxEmployees) {
          $ratio = $callMaxEmployees / $subEmployees;
          $score += max(0.0, $ratio * 50.0);
        }
        elseif ($callMinEmployees > 0 && $subEmployees < $callMinEmployees) {
          $ratio = $subEmployees / $callMinEmployees;
          $score += max(0.0, $ratio * 50.0);
        }
      }
    }

    // Verificar rango de facturacion.
    $subRevenue = (float) ($sub->get('annual_revenue')->value ?? 0);
    $callMinRevenue = (float) ($call->get('min_revenue')->value ?? 0);
    $callMaxRevenue = (float) ($call->get('max_revenue')->value ?? 0);

    if ($callMinRevenue > 0 || $callMaxRevenue > 0) {
      $checks++;
      if ($subRevenue >= $callMinRevenue && ($callMaxRevenue == 0 || $subRevenue <= $callMaxRevenue)) {
        $score += 100.0;
      }
      elseif ($subRevenue > 0) {
        // Puntuacion parcial por cercania.
        if ($callMaxRevenue > 0 && $subRevenue > $callMaxRevenue) {
          $ratio = $callMaxRevenue / $subRevenue;
          $score += max(0.0, $ratio * 50.0);
        }
        elseif ($callMinRevenue > 0 && $subRevenue < $callMinRevenue) {
          $ratio = $subRevenue / $callMinRevenue;
          $score += max(0.0, $ratio * 50.0);
        }
      }
    }

    // Si no hay requisitos de tamano, puntuar neutro.
    if ($checks === 0) {
      return 70.0;
    }

    return round($score / $checks, 2);
  }

  /**
   * Calcula puntuacion semantica (stub). Peso: 20%.
   *
   * Actualmente retorna 50.0 como valor neutro. Preparado para
   * conectar con Qdrant para busqueda semantica basada en embeddings.
   *
   * @param \Drupal\Core\Entity\EntityInterface $sub
   *   Entidad FundingSubscription.
   * @param \Drupal\Core\Entity\EntityInterface $call
   *   Entidad FundingCall.
   *
   * @return float
   *   Puntuacion de 0 a 100 (actualmente siempre 50.0).
   */
  public function calculateSemanticScore(EntityInterface $sub, EntityInterface $call): float {
    try {
      // Check if embedding service is available.
      if (!\Drupal::hasService('jaraba_matching.embedding_service')) {
        return 50.0;
      }

      /** @var \Drupal\jaraba_matching\Service\EmbeddingService $embeddingService */
      $embeddingService = \Drupal::service('jaraba_matching.embedding_service');

      // Build text representations for subscription and call.
      $subParts = [];
      if ($sub->hasField('description') && $sub->get('description')->value) {
        $subParts[] = strip_tags($sub->get('description')->value);
      }
      if ($sub->hasField('sectors') && $sub->get('sectors')->value) {
        $sectors = is_array($sub->get('sectors')->value) ? $sub->get('sectors')->value : [$sub->get('sectors')->value];
        $subParts[] = 'Sectors: ' . implode(', ', array_filter($sectors));
      }
      if ($sub->hasField('beneficiary_type') && $sub->get('beneficiary_type')->value) {
        $subParts[] = 'Type: ' . $sub->get('beneficiary_type')->value;
      }

      $callParts = [];
      if ($call->hasField('title') && $call->label()) {
        $callParts[] = $call->label();
      }
      if ($call->hasField('description') && $call->get('description')->value) {
        $callParts[] = strip_tags($call->get('description')->value);
      }
      if ($call->hasField('sectors') && $call->get('sectors')->value) {
        $sectors = is_array($call->get('sectors')->value) ? $call->get('sectors')->value : [$call->get('sectors')->value];
        $callParts[] = 'Sectors: ' . implode(', ', array_filter($sectors));
      }

      $subText = implode(' ', $subParts);
      $callText = implode(' ', $callParts);

      if (empty(trim($subText)) || empty(trim($callText))) {
        return 50.0;
      }

      // Generate embeddings.
      $subEmbedding = $embeddingService->generate($subText);
      $callEmbedding = $embeddingService->generate($callText);

      if (empty($subEmbedding) || empty($callEmbedding)) {
        return 50.0;
      }

      // Calculate cosine similarity.
      $similarity = $this->cosineSimilarity($subEmbedding, $callEmbedding);

      // Normalize from [-1, 1] range to [0, 100] scale.
      // Cosine similarity for text embeddings typically falls in [0, 1].
      $score = max(0.0, min(100.0, $similarity * 100.0));

      return round($score, 2);
    }
    catch (\Exception $e) {
      $this->logger->debug('Semantic score calculation failed: @error', [
        '@error' => $e->getMessage(),
      ]);
      return 50.0;
    }
  }

  /**
   * Calcula la similitud coseno entre dos vectores.
   *
   * @param array $vectorA
   *   Primer vector.
   * @param array $vectorB
   *   Segundo vector.
   *
   * @return float
   *   Similitud coseno entre -1 y 1.
   */
  protected function cosineSimilarity(array $vectorA, array $vectorB): float {
    if (count($vectorA) !== count($vectorB) || empty($vectorA)) {
      return 0.0;
    }

    $dotProduct = 0.0;
    $normA = 0.0;
    $normB = 0.0;

    for ($i = 0, $len = count($vectorA); $i < $len; $i++) {
      $dotProduct += $vectorA[$i] * $vectorB[$i];
      $normA += $vectorA[$i] * $vectorA[$i];
      $normB += $vectorB[$i] * $vectorB[$i];
    }

    $normA = sqrt($normA);
    $normB = sqrt($normB);

    if ($normA == 0.0 || $normB == 0.0) {
      return 0.0;
    }

    return $dotProduct / ($normA * $normB);
  }

  /**
   * Ejecuta matching para una suscripcion contra todas las convocatorias abiertas.
   *
   * @param int $subscriptionId
   *   ID de la entidad FundingSubscription.
   *
   * @return array
   *   Array de entidades FundingMatch creadas.
   */
  public function runMatchingForSubscription(int $subscriptionId): array {
    $matches = [];
    $threshold = $this->getThreshold();

    try {
      $subStorage = $this->entityTypeManager->getStorage('funding_subscription');
      $subscription = $subStorage->load($subscriptionId);

      if (!$subscription) {
        $this->logger->warning('Suscripcion @id no encontrada para matching.', [
          '@id' => $subscriptionId,
        ]);
        return [];
      }

      // Obtener convocatorias abiertas.
      $callStorage = $this->entityTypeManager->getStorage('funding_call');
      $callIds = $callStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'abierta')
        ->execute();

      if (empty($callIds)) {
        return [];
      }

      $calls = $callStorage->loadMultiple($callIds);
      $matchStorage = $this->entityTypeManager->getStorage('funding_match');

      foreach ($calls as $call) {
        $result = $this->calculateMatch($subscription, $call);

        if ($result['overall_score'] >= $threshold) {
          // Verificar si ya existe un match.
          $existingIds = $matchStorage->getQuery()
            ->accessCheck(FALSE)
            ->condition('subscription_id', $subscriptionId)
            ->condition('call_id', (int) $call->id())
            ->range(0, 1)
            ->execute();

          if (!empty($existingIds)) {
            // Actualizar match existente.
            $existingMatch = $matchStorage->load(reset($existingIds));
            $existingMatch->set('score', $result['overall_score']);
            $existingMatch->set('breakdown', json_encode($result['breakdown']));
            $existingMatch->save();
            $matches[] = $existingMatch;
          }
          else {
            // Crear nuevo match.
            $match = $matchStorage->create([
              'subscription_id' => $subscriptionId,
              'call_id' => (int) $call->id(),
              'score' => $result['overall_score'],
              'breakdown' => json_encode($result['breakdown']),
              'status' => 'new',
              'created' => \Drupal::time()->getRequestTime(),
            ]);
            $match->save();
            $matches[] = $match;
          }
        }
      }

      $this->logger->info('Matching para suscripcion @id: @count matches (threshold @threshold).', [
        '@id' => $subscriptionId,
        '@count' => count($matches),
        '@threshold' => $threshold,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error ejecutando matching para suscripcion @id: @error', [
        '@id' => $subscriptionId,
        '@error' => $e->getMessage(),
      ]);
    }

    return $matches;
  }

  /**
   * Ejecuta matching para una nueva convocatoria contra todas las suscripciones activas.
   *
   * @param int $callId
   *   ID de la entidad FundingCall.
   *
   * @return array
   *   Array de entidades FundingMatch creadas.
   */
  public function runMatchingForCall(int $callId): array {
    $matches = [];
    $threshold = $this->getThreshold();

    try {
      $callStorage = $this->entityTypeManager->getStorage('funding_call');
      $call = $callStorage->load($callId);

      if (!$call) {
        $this->logger->warning('Convocatoria @id no encontrada para matching.', [
          '@id' => $callId,
        ]);
        return [];
      }

      // Obtener suscripciones activas.
      $subStorage = $this->entityTypeManager->getStorage('funding_subscription');
      $subIds = $subStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'active')
        ->execute();

      if (empty($subIds)) {
        return [];
      }

      $subscriptions = $subStorage->loadMultiple($subIds);
      $matchStorage = $this->entityTypeManager->getStorage('funding_match');

      foreach ($subscriptions as $subscription) {
        $result = $this->calculateMatch($subscription, $call);

        if ($result['overall_score'] >= $threshold) {
          $match = $matchStorage->create([
            'subscription_id' => (int) $subscription->id(),
            'call_id' => $callId,
            'score' => $result['overall_score'],
            'breakdown' => json_encode($result['breakdown']),
            'status' => 'new',
            'created' => \Drupal::time()->getRequestTime(),
          ]);
          $match->save();
          $matches[] = $match;
        }
      }

      $this->logger->info('Matching para convocatoria @id: @count matches (threshold @threshold).', [
        '@id' => $callId,
        '@count' => count($matches),
        '@threshold' => $threshold,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error ejecutando matching para convocatoria @id: @error', [
        '@id' => $callId,
        '@error' => $e->getMessage(),
      ]);
    }

    return $matches;
  }

  /**
   * Obtiene el threshold minimo de matching desde configuracion.
   *
   * @return float
   *   Threshold minimo (default 60.0).
   */
  public function getThreshold(): float {
    $config = $this->configFactory->get('jaraba_funding.settings');

    return (float) ($config->get('matching_threshold') ?: 60.0);
  }

  /**
   * Enriquece una suscripcion con datos del Business Model Canvas.
   *
   * Consulta jaraba_business_tools.canvas_service para obtener el ultimo
   * canvas del usuario propietario de la suscripcion. Extrae sector,
   * modelo de ingresos y segmento de cliente para mejorar la calidad
   * del matching de financiacion.
   *
   * @param \Drupal\Core\Entity\EntityInterface $subscription
   *   Entidad FundingSubscription.
   *
   * @return array
   *   Contexto del canvas con claves:
   *   - has_canvas: (bool) Si se encontro canvas.
   *   - sector: (string) Sector del canvas.
   *   - revenue_streams: (array) Modelos de ingresos.
   *   - customer_segments: (array) Segmentos de cliente.
   *   - business_stage: (string) Etapa del negocio.
   */
  public function getCanvasContext(EntityInterface $subscription): array {
    $result = ['has_canvas' => FALSE];

    try {
      if (!\Drupal::hasService('jaraba_business_tools.canvas_service')) {
        return $result;
      }

      // Obtener el user_id del propietario de la suscripcion.
      $userId = (int) ($subscription->get('user_id')->value ?? 0);
      if (!$userId) {
        return $result;
      }

      /** @var \Drupal\jaraba_business_tools\Service\CanvasService $canvasService */
      $canvasService = \Drupal::service('jaraba_business_tools.canvas_service');

      // Buscar el ultimo canvas del usuario.
      $canvasStorage = $this->entityTypeManager->getStorage('business_model_canvas');
      $canvasIds = $canvasStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->condition('is_template', 0)
        ->sort('changed', 'DESC')
        ->range(0, 1)
        ->execute();

      if (empty($canvasIds)) {
        return $result;
      }

      $canvasId = reset($canvasIds);
      $canvasData = $canvasService->getCanvasWithBlocks($canvasId);

      if (!$canvasData || empty($canvasData['canvas'])) {
        return $result;
      }

      $canvas = $canvasData['canvas'];
      $blocks = $canvasData['blocks'] ?? [];

      // Extraer sector del canvas.
      $sector = '';
      if (method_exists($canvas, 'getSector')) {
        $sector = $canvas->getSector() ?? '';
      }
      elseif ($canvas->hasField('sector')) {
        $sector = $canvas->get('sector')->value ?? '';
      }

      // Extraer revenue streams del bloque correspondiente.
      $revenueStreams = [];
      if (isset($blocks['revenue_streams'])) {
        $block = $blocks['revenue_streams'];
        if (method_exists($block, 'getItems')) {
          $revenueStreams = $block->getItems();
        }
      }

      // Extraer customer segments del bloque correspondiente.
      $customerSegments = [];
      if (isset($blocks['customer_segments'])) {
        $block = $blocks['customer_segments'];
        if (method_exists($block, 'getItems')) {
          $customerSegments = $block->getItems();
        }
      }

      // Extraer etapa del negocio.
      $businessStage = '';
      if ($canvas->hasField('business_stage')) {
        $businessStage = $canvas->get('business_stage')->value ?? '';
      }

      $result = [
        'has_canvas' => TRUE,
        'sector' => $sector,
        'revenue_streams' => $revenueStreams,
        'customer_segments' => $customerSegments,
        'business_stage' => $businessStage,
      ];

      $this->logger->debug('Canvas context para suscripcion @sub: sector=@sector, stage=@stage', [
        '@sub' => $subscription->id(),
        '@sector' => $sector,
        '@stage' => $businessStage,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->debug('Canvas context unavailable: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $result;
  }

  /**
   * Ejecuta matching enriquecido con datos de canvas para emprendedores.
   *
   * Extiende runMatchingForSubscription() inyectando contexto del canvas
   * en los criterios de sector y semantico para mejorar la precision.
   *
   * @param int $subscriptionId
   *   ID de la entidad FundingSubscription.
   *
   * @return array
   *   Array de entidades FundingMatch creadas.
   */
  public function runEnrichedMatchingForSubscription(int $subscriptionId): array {
    try {
      $subStorage = $this->entityTypeManager->getStorage('funding_subscription');
      $subscription = $subStorage->load($subscriptionId);

      if (!$subscription) {
        return [];
      }

      // Obtener contexto del canvas.
      $canvasContext = $this->getCanvasContext($subscription);

      if ($canvasContext['has_canvas'] && !empty($canvasContext['sector'])) {
        // Si el canvas tiene sector y la suscripcion no, enriquecer.
        $subSectors = $subscription->get('sectors')->value ?? [];
        if (!is_array($subSectors)) {
          $subSectors = [$subSectors];
        }
        $subSectors = array_filter($subSectors);

        if (empty($subSectors)) {
          // Inyectar sector del canvas en la suscripcion en memoria.
          $subscription->set('sectors', [$canvasContext['sector']]);

          $this->logger->info('Enriquecida suscripcion @id con sector @sector del canvas.', [
            '@id' => $subscriptionId,
            '@sector' => $canvasContext['sector'],
          ]);
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->debug('Canvas enrichment failed, using standard matching: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    // Ejecutar matching estandar (con suscripcion enriquecida si aplica).
    return $this->runMatchingForSubscription($subscriptionId);
  }

  /**
   * Obtiene regiones relacionadas a una region dada.
   *
   * @param string $region
   *   Codigo de la region.
   *
   * @return array
   *   Lista de regiones relacionadas.
   */
  protected function getRelatedRegions(string $region): array {
    $relatedMap = [
      'andalucia' => ['extremadura', 'murcia', 'castilla_la_mancha'],
      'aragon' => ['cataluna', 'navarra', 'castilla_y_leon', 'comunidad_valenciana', 'la_rioja'],
      'asturias' => ['cantabria', 'galicia', 'castilla_y_leon'],
      'baleares' => ['comunidad_valenciana', 'cataluna'],
      'canarias' => [],
      'cantabria' => ['asturias', 'pais_vasco', 'castilla_y_leon'],
      'castilla_y_leon' => ['galicia', 'asturias', 'cantabria', 'pais_vasco', 'la_rioja', 'aragon', 'castilla_la_mancha', 'madrid', 'extremadura'],
      'castilla_la_mancha' => ['madrid', 'comunidad_valenciana', 'murcia', 'andalucia', 'extremadura', 'castilla_y_leon'],
      'cataluna' => ['aragon', 'comunidad_valenciana', 'baleares'],
      'comunidad_valenciana' => ['cataluna', 'aragon', 'castilla_la_mancha', 'murcia', 'baleares'],
      'extremadura' => ['andalucia', 'castilla_la_mancha', 'castilla_y_leon'],
      'galicia' => ['asturias', 'castilla_y_leon'],
      'madrid' => ['castilla_la_mancha', 'castilla_y_leon'],
      'murcia' => ['andalucia', 'comunidad_valenciana', 'castilla_la_mancha'],
      'navarra' => ['pais_vasco', 'aragon', 'la_rioja'],
      'pais_vasco' => ['navarra', 'cantabria', 'castilla_y_leon', 'la_rioja'],
      'la_rioja' => ['navarra', 'pais_vasco', 'castilla_y_leon', 'aragon'],
      'ceuta' => ['andalucia'],
      'melilla' => ['andalucia'],
    ];

    return $relatedMap[$region] ?? [];
  }

}
