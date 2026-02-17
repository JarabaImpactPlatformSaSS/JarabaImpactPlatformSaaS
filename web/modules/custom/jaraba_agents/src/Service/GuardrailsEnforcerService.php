<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Log\LoggerInterface;

/**
 * Servicio de verificacion y aplicacion de guardrails de seguridad.
 *
 * ESTRUCTURA:
 *   Verifica limites de acciones, presupuesto de tokens,
 *   whitelist de capacidades y restricciones configuradas por agente.
 *
 * LOGICA:
 *   Cada accion del agente pasa por check() antes de ejecutarse.
 *   Los guardrails se definen en el campo guardrails (JSON) de la
 *   entidad AutonomousAgent y en la configuracion global del modulo.
 *   enforce() realiza validacion completa de todos los limites activos.
 */
class GuardrailsEnforcerService {

  /**
   * Nombre de la configuracion del modulo jaraba_agents.
   */
  protected const CONFIG_NAME = 'jaraba_agents.settings';

  /**
   * Presupuesto de tokens por defecto cuando no esta configurado.
   */
  protected const DEFAULT_TOKEN_BUDGET = 50000;

  /**
   * Umbral de alerta de coste por defecto en USD.
   */
  protected const DEFAULT_COST_THRESHOLD = 10.0;

  /**
   * Construye el servicio de guardrails de seguridad.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para acceso a almacenamiento.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del canal jaraba_agents.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Factoria de configuracion para acceso a ajustes del modulo.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
    protected readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Verifica si una accion esta permitida para un agente.
   *
   * Evalua la accion contra la whitelist de capacidades del agente,
   * los guardrails configurados y las restricciones globales.
   *
   * @param object $agent
   *   Entidad AutonomousAgent a evaluar.
   * @param string $action
   *   Identificador de la accion a verificar.
   * @param array $context
   *   Contexto adicional para la evaluacion (datos del trigger, etc.).
   *
   * @return array
   *   Array con claves:
   *   - 'allowed': bool indicando si la accion esta permitida.
   *   - 'reason': string con el motivo del resultado.
   *   - 'requires_approval': bool indicando si necesita aprobacion humana.
   */
  public function check(object $agent, string $action, array $context = []): array {
    // Verificar si la accion esta en la whitelist de capacidades.
    if (!$this->isActionAllowed($agent, $action)) {
      $this->logger->warning('Accion @action no permitida para agente @id.', [
        '@action' => $action,
        '@id' => $agent->id(),
      ]);
      return [
        'allowed' => FALSE,
        'reason' => (string) new TranslatableMarkup(
          'La accion "@action" no esta en la lista de capacidades permitidas del agente.',
          ['@action' => $action],
        ),
        'requires_approval' => FALSE,
      ];
    }

    // Obtener guardrails configurados del agente (campo JSON).
    $guardrailsData = $this->getAgentGuardrails($agent);

    // Verificar si la accion requiere aprobacion humana.
    $requiresApproval = FALSE;
    $approvalActions = $guardrailsData['requires_approval'] ?? [];
    if (in_array($action, $approvalActions, TRUE)) {
      $requiresApproval = TRUE;
    }

    // Verificar nivel de autonomia para determinar si bloquear.
    $level = $this->getLevel($agent);
    $levelNumeric = (int) substr($level, 1);

    // L0: Solo informativo, no puede ejecutar acciones.
    if ($levelNumeric === 0) {
      return [
        'allowed' => FALSE,
        'reason' => (string) new TranslatableMarkup('El agente opera en nivel L0 (solo informativo). No puede ejecutar acciones.'),
        'requires_approval' => FALSE,
      ];
    }

    // Verificar restricciones de horario si existen.
    $scheduleRestrictions = $guardrailsData['schedule_restrictions'] ?? [];
    if (!empty($scheduleRestrictions)) {
      $currentHour = (int) date('H');
      $allowedStart = $scheduleRestrictions['start_hour'] ?? 0;
      $allowedEnd = $scheduleRestrictions['end_hour'] ?? 24;
      if ($currentHour < $allowedStart || $currentHour >= $allowedEnd) {
        return [
          'allowed' => FALSE,
          'reason' => (string) new TranslatableMarkup(
            'La accion esta fuera del horario permitido (@start:00 - @end:00).',
            ['@start' => $allowedStart, '@end' => $allowedEnd],
          ),
          'requires_approval' => FALSE,
        ];
      }
    }

    return [
      'allowed' => TRUE,
      'reason' => (string) new TranslatableMarkup('Accion permitida por los guardrails configurados.'),
      'requires_approval' => $requiresApproval,
    ];
  }

  /**
   * Aplica todos los guardrails a una ejecucion completa.
   *
   * Verifica presupuesto de tokens, conteo de acciones y limites
   * de coste para determinar si la ejecucion puede continuar.
   *
   * @param object $execution
   *   Entidad AgentExecution o AutonomousAgent a evaluar.
   *
   * @return array
   *   Array con claves:
   *   - 'passed': bool indicando si todos los guardrails pasaron.
   *   - 'violations': array de strings con las violaciones encontradas.
   */
  public function enforce(object $execution): array {
    $violations = [];

    try {
      // Obtener el agente asociado (puede recibir agente o ejecucion).
      $agent = $execution;
      if (method_exists($execution, 'hasField') && $execution->hasField('agent_id')) {
        $agentId = $execution->get('agent_id')->target_id;
        $agentStorage = $this->entityTypeManager->getStorage('autonomous_agent');
        $agent = $agentStorage->load($agentId);
        if (!$agent) {
          return [
            'passed' => FALSE,
            'violations' => [(string) new TranslatableMarkup('Agente asociado no encontrado.')],
          ];
        }
      }

      $guardrailsData = $this->getAgentGuardrails($agent);

      // Verificar presupuesto de tokens.
      $tokenBudget = $this->getTokenBudget($agent);
      if (method_exists($execution, 'hasField') && $execution->hasField('tokens_used')) {
        $tokensUsed = (int) ($execution->get('tokens_used')->value ?? 0);
        if ($tokensUsed >= $tokenBudget) {
          $violations[] = (string) new TranslatableMarkup(
            'Presupuesto de tokens excedido: @used/@budget.',
            ['@used' => $tokensUsed, '@budget' => $tokenBudget],
          );
        }
      }

      // Verificar limite de acciones por ejecucion.
      $maxActions = $guardrailsData['max_actions_per_execution'] ?? 100;
      if (method_exists($execution, 'hasField') && $execution->hasField('actions_taken')) {
        $actionsTaken = json_decode($execution->get('actions_taken')->value ?? '[]', TRUE) ?: [];
        if (count($actionsTaken) >= $maxActions) {
          $violations[] = (string) new TranslatableMarkup(
            'Limite de acciones por ejecucion alcanzado: @count/@max.',
            ['@count' => count($actionsTaken), '@max' => $maxActions],
          );
        }
      }

      // Verificar limite de coste.
      $costThreshold = $this->getCostBudget();
      if (method_exists($execution, 'hasField') && $execution->hasField('cost')) {
        $cost = (float) ($execution->get('cost')->value ?? 0.0);
        if ($cost >= $costThreshold) {
          $violations[] = (string) new TranslatableMarkup(
            'Umbral de coste alcanzado: $@cost (limite: $@limit).',
            ['@cost' => number_format($cost, 4), '@limit' => number_format($costThreshold, 4)],
          );
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error al aplicar guardrails: @message', [
        '@message' => $e->getMessage(),
      ]);
      $violations[] = (string) new TranslatableMarkup('Error interno al verificar guardrails.');
    }

    if (!empty($violations)) {
      $this->logger->warning('Guardrails violados: @violations', [
        '@violations' => implode(' | ', $violations),
      ]);
    }

    return [
      'passed' => empty($violations),
      'violations' => $violations,
    ];
  }

  /**
   * Obtiene el nivel de autonomia de un agente.
   *
   * Los niveles van de L0 (informativo) a L4 (completamente autonomo).
   *
   * @param object $agent
   *   Entidad AutonomousAgent.
   *
   * @return string
   *   Nivel de autonomia: 'L0', 'L1', 'L2', 'L3' o 'L4'.
   */
  public function getLevel(object $agent): string {
    try {
      if (method_exists($agent, 'hasField') && $agent->hasField('autonomy_level')) {
        return $agent->get('autonomy_level')->value ?? 'L0';
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error al obtener nivel de autonomia: @message', [
        '@message' => $e->getMessage(),
      ]);
    }

    return 'L0';
  }

  /**
   * Verifica si una accion esta en la whitelist de capacidades del agente.
   *
   * @param object $agent
   *   Entidad AutonomousAgent.
   * @param string $action
   *   Identificador de la accion a verificar.
   *
   * @return bool
   *   TRUE si la accion esta permitida, FALSE en caso contrario.
   */
  public function isActionAllowed(object $agent, string $action): bool {
    try {
      if (method_exists($agent, 'hasField') && $agent->hasField('capabilities')) {
        $capabilitiesRaw = $agent->get('capabilities')->value ?? '[]';
        $capabilities = json_decode($capabilitiesRaw, TRUE) ?: [];
        return in_array($action, $capabilities, TRUE);
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error al verificar capacidades del agente: @message', [
        '@message' => $e->getMessage(),
      ]);
    }

    // Si no hay campo de capacidades, permitir por defecto (modo permisivo).
    return TRUE;
  }

  /**
   * Obtiene el presupuesto maximo de tokens para un agente.
   *
   * @param object $agent
   *   Entidad AutonomousAgent.
   *
   * @return int
   *   Numero maximo de tokens permitidos por ejecucion.
   */
  public function getTokenBudget(object $agent): int {
    try {
      $guardrailsData = $this->getAgentGuardrails($agent);
      return (int) ($guardrailsData['max_tokens'] ?? self::DEFAULT_TOKEN_BUDGET);
    }
    catch (\Exception $e) {
      $this->logger->error('Error al obtener presupuesto de tokens: @message', [
        '@message' => $e->getMessage(),
      ]);
      return self::DEFAULT_TOKEN_BUDGET;
    }
  }

  /**
   * Obtiene el umbral de alerta de coste desde la configuracion.
   *
   * @return float
   *   Umbral de coste en USD.
   */
  public function getCostBudget(): float {
    try {
      $config = $this->configFactory->get(self::CONFIG_NAME);
      return (float) ($config->get('cost_alert_threshold') ?? self::DEFAULT_COST_THRESHOLD);
    }
    catch (\Exception $e) {
      $this->logger->error('Error al obtener umbral de coste: @message', [
        '@message' => $e->getMessage(),
      ]);
      return self::DEFAULT_COST_THRESHOLD;
    }
  }

  /**
   * Extrae los guardrails configurados del campo JSON del agente.
   *
   * @param object $agent
   *   Entidad AutonomousAgent.
   *
   * @return array
   *   Array decodificado de guardrails o array vacio si no existe.
   */
  protected function getAgentGuardrails(object $agent): array {
    try {
      if (method_exists($agent, 'hasField') && $agent->hasField('guardrails')) {
        $raw = $agent->get('guardrails')->value ?? '{}';
        return json_decode($raw, TRUE) ?: [];
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error al leer guardrails del agente: @message', [
        '@message' => $e->getMessage(),
      ]);
    }

    return [];
  }

}
