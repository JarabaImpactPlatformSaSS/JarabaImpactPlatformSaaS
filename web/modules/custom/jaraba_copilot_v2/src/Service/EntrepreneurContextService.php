<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_copilot_v2\Entity\EntrepreneurProfileInterface;

/**
 * Servicio de contexto enriquecido para emprendedores.
 *
 * PROPÓSITO:
 * Proporciona hiperpersonalización del Copiloto v3 mediante la agregación
 * de datos del emprendedor desde múltiples fuentes de la base de datos.
 *
 * FUENTES DE DATOS:
 * 1. EntrepreneurProfile - Datos del perfil
 * 2. Hypothesis - Hipótesis validadas/invalidadas
 * 3. Experiment - Experimentos realizados
 * 4. Conversaciones - Historial de temas frecuentes
 * 5. BMC Canvas - Estado de validación
 *
 * METODOLOGÍAS INTEGRADAS:
 * - Osterwalder (6 libros): Business Model Generation, Value Proposition Design
 * - Blank/Dorf: Customer Development, Startup Owner's Manual
 * - Kaufman: MBA Personal (12 formas de valor)
 *
 * @see \Drupal\jaraba_copilot_v2\Entity\EntrepreneurProfile
 * @see \Drupal\jaraba_copilot_v2\Service\CopilotOrchestratorService
 */
class EntrepreneurContextService {

  /**
   * El gestor de tipos de entidad.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * El proxy de cuenta actual.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * El canal de logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * Cache del perfil actual para evitar consultas repetidas.
   *
   * @var \Drupal\jaraba_copilot_v2\Entity\EntrepreneurProfileInterface|null|false
   */
  protected $cachedProfile = FALSE;

  /**
   * Constructor del servicio.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    AccountProxyInterface $current_user,
    LoggerChannelInterface $logger
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->logger = $logger;
  }

  /**
   * Obtiene el contexto completo del emprendedor para hiperpersonalización.
   *
   * Este método agrega TODOS los datos relevantes del emprendedor
   * para inyectar en el prompt del Copiloto, habilitando respuestas
   * altamente personalizadas basadas en su historial y progreso.
   *
   * @param int|null $userId
   *   ID del usuario, o NULL para usar el usuario actual.
   *
   * @return array
   *   Array con contexto completo:
   *   - profile: Datos del perfil del emprendedor
   *   - recent_conversations: Últimos 10 temas de conversación
   *   - frequent_questions: Patrones de preguntas frecuentes
   *   - bmc_status: Estado de validación del Business Model Canvas
   *   - experiments_completed: Experimentos realizados
   *   - field_exits_count: Número de "salidas del edificio"
   *   - hypothesis_stats: Estadísticas de hipótesis
   *   - methodology_phase: Fase actual en Customer Development
   */
  public function getFullContext(?int $userId = NULL): array {
    $userId = $userId ?? (int) $this->currentUser->id();

    if (!$userId) {
      return $this->getEmptyContext();
    }

    try {
      return [
        'profile' => $this->getProfileData($userId),
        'recent_conversations' => $this->getRecentTopics($userId, 10),
        'frequent_questions' => $this->getFrequentPatterns($userId),
        'bmc_status' => $this->getBmcValidationStatus($userId),
        'experiments_completed' => $this->getExperiments($userId),
        'field_exits_count' => $this->getFieldExitsCount($userId),
        'hypothesis_stats' => $this->getHypothesisStats($userId),
        'methodology_phase' => $this->determineMethodologyPhase($userId),
      ];
    }
    catch (\Exception $e) {
      $this->logger->error(
        'Error obteniendo contexto emprendedor para @uid: @error',
        [
          '@uid' => $userId,
          '@error' => $e->getMessage(),
        ]
      );
      return $this->getEmptyContext();
    }
  }

  /**
   * Obtiene los datos del perfil del emprendedor.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array
   *   Datos del perfil.
   */
  protected function getProfileData(int $userId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('entrepreneur_profile');
      $profiles = $storage->loadByProperties(['user_id' => $userId]);

      if (empty($profiles)) {
        return ['exists' => FALSE];
      }

      /** @var \Drupal\jaraba_copilot_v2\Entity\EntrepreneurProfileInterface $profile */
      $profile = reset($profiles);

      return [
        'exists' => TRUE,
        'business_name' => $profile->get('business_name')->value ?? '',
        'sector' => $profile->get('sector')->value ?? '',
        'business_stage' => $profile->get('business_stage')->value ?? 'idea',
        'team_size' => (int) ($profile->get('team_size')->value ?? 1),
        'monthly_revenue' => (float) ($profile->get('monthly_revenue')->value ?? 0),
        'has_validated_problem' => (bool) ($profile->get('has_validated_problem')->value ?? FALSE),
        'has_validated_solution' => (bool) ($profile->get('has_validated_solution')->value ?? FALSE),
        'customer_interviews' => (int) ($profile->get('customer_interviews')->value ?? 0),
        'created' => $profile->get('created')->value,
      ];
    }
    catch (\Exception $e) {
      return ['exists' => FALSE];
    }
  }

  /**
   * Obtiene los últimos N temas de conversación.
   *
   * @param int $userId
   *   ID del usuario.
   * @param int $limit
   *   Número de temas a devolver.
   *
   * @return array
   *   Lista de temas recientes.
   */
  protected function getRecentTopics(int $userId, int $limit = 10): array {
    // Query conversation_log entity by user_id for recent topics.
    try {
      $storage = $this->entityTypeManager->getStorage('conversation_log');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->sort('created', 'DESC')
        ->range(0, $limit)
        ->execute();

      if (empty($ids)) {
        return [];
      }

      $topics = [];
      foreach ($storage->loadMultiple($ids) as $log) {
        $topics[] = [
          'topic' => $log->get('topic')->value ?? '',
          'summary' => $log->get('summary')->value ?? '',
          'created' => $log->get('created')->value,
        ];
      }

      return $topics;
    }
    catch (\Exception $e) {
      // Entity conversation_log may not exist yet.
      return [];
    }
  }

  /**
   * Obtiene patrones de preguntas frecuentes.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array
   *   Patrones identificados.
   */
  protected function getFrequentPatterns(int $userId): array {
    // Basic pattern analysis: topic frequency over conversation history.
    try {
      $storage = $this->entityTypeManager->getStorage('conversation_log');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->sort('created', 'DESC')
        ->range(0, 50)
        ->execute();

      if (empty($ids)) {
        return [];
      }

      $topicCounts = [];
      foreach ($storage->loadMultiple($ids) as $log) {
        $topic = $log->get('topic')->value ?? '';
        if ($topic) {
          $topicCounts[$topic] = ($topicCounts[$topic] ?? 0) + 1;
        }
      }

      // Sort by frequency descending and return top patterns.
      arsort($topicCounts);
      $patterns = [];
      foreach (array_slice($topicCounts, 0, 5, TRUE) as $topic => $count) {
        $patterns[] = [
          'topic' => $topic,
          'frequency' => $count,
        ];
      }

      return $patterns;
    }
    catch (\Exception $e) {
      // Entity conversation_log may not exist yet.
      return [];
    }
  }

  /**
   * Obtiene el estado de validación del Business Model Canvas.
   *
   * FIX-013: Usa BmcValidationService::CODE_TO_KEY para convertir los
   * códigos 2-letras almacenados en la entidad Hypothesis (CS, VP, etc.)
   * a las claves snake_case usadas en este servicio.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array
   *   Estado por cada bloque del BMC.
   */
  protected function getBmcValidationStatus(int $userId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('hypothesis');
      $hypotheses = $storage->loadByProperties(['user_id' => $userId]);

      $bmcBlocks = [
        'customer_segments' => ['validated' => 0, 'invalidated' => 0, 'pending' => 0],
        'value_propositions' => ['validated' => 0, 'invalidated' => 0, 'pending' => 0],
        'channels' => ['validated' => 0, 'invalidated' => 0, 'pending' => 0],
        'customer_relationships' => ['validated' => 0, 'invalidated' => 0, 'pending' => 0],
        'revenue_streams' => ['validated' => 0, 'invalidated' => 0, 'pending' => 0],
        'key_resources' => ['validated' => 0, 'invalidated' => 0, 'pending' => 0],
        'key_activities' => ['validated' => 0, 'invalidated' => 0, 'pending' => 0],
        'key_partnerships' => ['validated' => 0, 'invalidated' => 0, 'pending' => 0],
        'cost_structure' => ['validated' => 0, 'invalidated' => 0, 'pending' => 0],
      ];

      foreach ($hypotheses as $hypothesis) {
        $blockCode = $hypothesis->get('bmc_block')->value ?? '';
        $status = $hypothesis->get('status')->value ?? 'pending';

        // FIX-013: Convertir código 2-letras (CS, VP...) a snake_case.
        $blockKey = BmcValidationService::CODE_TO_KEY[$blockCode] ?? $blockCode;

        if (isset($bmcBlocks[$blockKey])) {
          $bmcBlocks[$blockKey][$status] = ($bmcBlocks[$blockKey][$status] ?? 0) + 1;
        }
      }

      // Calcular porcentaje global de validación
      $totalValidated = 0;
      $totalHypotheses = 0;

      foreach ($bmcBlocks as $block) {
        $totalValidated += $block['validated'];
        $totalHypotheses += $block['validated'] + $block['invalidated'] + $block['pending'];
      }

      return [
        'blocks' => $bmcBlocks,
        'total_hypotheses' => $totalHypotheses,
        'total_validated' => $totalValidated,
        'validation_percentage' => $totalHypotheses > 0
          ? round(($totalValidated / $totalHypotheses) * 100)
          : 0,
      ];
    }
    catch (\Exception $e) {
      return ['blocks' => [], 'total_hypotheses' => 0, 'validation_percentage' => 0];
    }
  }

  /**
   * Obtiene los experimentos completados.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array
   *   Lista de experimentos con resultados.
   */
  protected function getExperiments(int $userId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('experiment');
      $experiments = $storage->loadByProperties([
        'user_id' => $userId,
        'status' => 'completed',
      ]);

      $result = [];
      foreach ($experiments as $experiment) {
        $result[] = [
          'id' => $experiment->id(),
          'type' => $experiment->get('experiment_type')->value ?? '',
          'hypothesis_tested' => $experiment->get('hypothesis_tested')->value ?? '',
          'result' => $experiment->get('result')->value ?? '',
          'learnings' => $experiment->get('learnings')->value ?? '',
          'completed_at' => $experiment->get('completed_at')->value ?? NULL,
        ];
      }

      return [
        'count' => count($result),
        'recent' => array_slice($result, -5),
      ];
    }
    catch (\Exception $e) {
      return ['count' => 0, 'recent' => []];
    }
  }

  /**
   * Cuenta las "salidas del edificio" (customer discovery interviews).
   *
   * Siguiendo la metodología de Steve Blank, cada contacto con
   * clientes potenciales fuera de la oficina es una "salida".
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return int
   *   Número de salidas registradas.
   */
  protected function getFieldExitsCount(int $userId): int {
    try {
      // Intentar desde field_exit entity si existe
      $storage = $this->entityTypeManager->getStorage('field_exit');
      $count = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('entrepreneur_id', $userId)
        ->count()
        ->execute();

      return (int) $count;
    }
    catch (\Exception $e) {
      // Si la entidad no existe, usar datos del perfil
      try {
        $storage = $this->entityTypeManager->getStorage('entrepreneur_profile');
        $profiles = $storage->loadByProperties(['user_id' => $userId]);

        if (!empty($profiles)) {
          $profile = reset($profiles);
          return (int) ($profile->get('customer_interviews')->value ?? 0);
        }
      }
      catch (\Exception $e2) {
        // Ignore
      }

      return 0;
    }
  }

  /**
   * Obtiene estadísticas de hipótesis.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array
   *   Estadísticas de hipótesis.
   */
  protected function getHypothesisStats(int $userId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('hypothesis');

      $validated = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->condition('status', 'validated')
        ->count()
        ->execute();

      $invalidated = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->condition('status', 'invalidated')
        ->count()
        ->execute();

      $pending = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->condition('status', 'pending')
        ->count()
        ->execute();

      return [
        'validated' => (int) $validated,
        'invalidated' => (int) $invalidated,
        'pending' => (int) $pending,
        'total' => (int) $validated + (int) $invalidated + (int) $pending,
      ];
    }
    catch (\Exception $e) {
      return ['validated' => 0, 'invalidated' => 0, 'pending' => 0, 'total' => 0];
    }
  }

  /**
   * Determina la fase actual del emprendedor en Customer Development.
   *
   * Las 4 fases de Blank/Dorf:
   * 1. Customer Discovery - Validando problema
   * 2. Customer Validation - Validando solución
   * 3. Customer Creation - Escalando demanda
   * 4. Company Building - Construyendo empresa
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array
   *   Fase actual y progreso.
   */
  protected function determineMethodologyPhase(int $userId): array {
    $profile = $this->getProfileData($userId);
    $hypStats = $this->getHypothesisStats($userId);
    $exits = $this->getFieldExitsCount($userId);

    // Lógica de determinación de fase
    if (!($profile['has_validated_problem'] ?? FALSE)) {
      $phase = 'customer_discovery';
      $progress = min(100, $exits * 5); // 5% por cada salida, max 100
    }
    elseif (!($profile['has_validated_solution'] ?? FALSE)) {
      $phase = 'customer_validation';
      $progress = min(100, ($hypStats['validated'] ?? 0) * 10);
    }
    elseif (($profile['monthly_revenue'] ?? 0) < 1000) {
      $phase = 'customer_creation';
      $progress = min(100, (int) (($profile['monthly_revenue'] ?? 0) / 10));
    }
    else {
      $phase = 'company_building';
      $progress = 100;
    }

    return [
      'current_phase' => $phase,
      'phase_progress' => $progress,
      'recommended_focus' => $this->getPhaseRecommendations($phase),
    ];
  }

  /**
   * Obtiene recomendaciones basadas en la fase actual.
   *
   * @param string $phase
   *   Fase actual.
   *
   * @return array
   *   Recomendaciones.
   */
  protected function getPhaseRecommendations(string $phase): array {
    $recommendations = [
      'customer_discovery' => [
        'primary_action' => 'Entrevistar clientes potenciales',
        'tools' => ['Customer Interview Script', 'Problem Canvas'],
        'target' => 'Completar 20+ entrevistas de descubrimiento',
      ],
      'customer_validation' => [
        'primary_action' => 'Probar MVP con early adopters',
        'tools' => ['Value Proposition Canvas', 'Test Cards'],
        'target' => 'Conseguir 5 clientes pagando',
      ],
      'customer_creation' => [
        'primary_action' => 'Escalar adquisición de clientes',
        'tools' => ['Growth Experiments', 'Channel Map'],
        'target' => 'Alcanzar €10K MRR',
      ],
      'company_building' => [
        'primary_action' => 'Construir organización escalable',
        'tools' => ['Team Canvas', 'Culture Map'],
        'target' => 'Documentar procesos clave',
      ],
    ];

    return $recommendations[$phase] ?? $recommendations['customer_discovery'];
  }

  /**
   * Devuelve un contexto vacío cuando no hay datos.
   *
   * @return array
   *   Contexto vacío.
   */
  protected function getEmptyContext(): array {
    return [
      'profile' => ['exists' => FALSE],
      'recent_conversations' => [],
      'frequent_questions' => [],
      'bmc_status' => ['validation_percentage' => 0],
      'experiments_completed' => ['count' => 0, 'recent' => []],
      'field_exits_count' => 0,
      'hypothesis_stats' => ['total' => 0],
      'methodology_phase' => [
        'current_phase' => 'customer_discovery',
        'phase_progress' => 0,
      ],
    ];
  }

  /**
   * Genera un resumen de contexto para inyección en el prompt.
   *
   * @param int|null $userId
   *   ID del usuario.
   *
   * @return string
   *   Texto resumido para incluir en system prompt.
   */
  public function getContextSummaryForPrompt(?int $userId = NULL): string {
    $context = $this->getFullContext($userId);

    if (!($context['profile']['exists'] ?? FALSE)) {
      return "Usuario nuevo sin perfil de emprendedor configurado.";
    }

    $profile = $context['profile'];
    $phase = $context['methodology_phase'] ?? [];
    $stats = $context['hypothesis_stats'] ?? [];
    $bmc = $context['bmc_status'] ?? [];

    $summary = sprintf(
      "CONTEXTO DEL EMPRENDEDOR:\n" .
      "- Negocio: %s (Sector: %s)\n" .
      "- Etapa: %s\n" .
      "- Equipo: %d persona(s)\n" .
      "- Fase Customer Development: %s (progreso: %d%%)\n" .
      "- Hipótesis: %d validadas, %d invalidadas, %d pendientes\n" .
      "- BMC validado: %d%%\n" .
      "- Salidas del edificio: %d\n\n" .
      "ENFOQUE RECOMENDADO: %s",
      $profile['business_name'] ?: 'Sin nombre',
      $profile['sector'] ?: 'Sin definir',
      $profile['business_stage'] ?? 'idea',
      $profile['team_size'] ?? 1,
      str_replace('_', ' ', ucfirst($phase['current_phase'] ?? 'customer_discovery')),
      $phase['phase_progress'] ?? 0,
      $stats['validated'] ?? 0,
      $stats['invalidated'] ?? 0,
      $stats['pending'] ?? 0,
      $bmc['validation_percentage'] ?? 0,
      $context['field_exits_count'] ?? 0,
      $phase['recommended_focus']['primary_action'] ?? 'Validar problema con clientes'
    );

    return $summary;
  }

}
