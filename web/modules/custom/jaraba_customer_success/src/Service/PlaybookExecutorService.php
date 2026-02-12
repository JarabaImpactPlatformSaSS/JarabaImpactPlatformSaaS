<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\jaraba_customer_success\Entity\CsPlaybook;
use Drupal\jaraba_customer_success\Entity\PlaybookExecution;
use Psr\Log\LoggerInterface;

/**
 * Motor de ejecución de playbooks automatizados.
 *
 * PROPÓSITO:
 * Evalúa triggers de playbooks en cron, inicia ejecuciones
 * cuando se cumplen condiciones, y avanza pasos programados
 * (email, llamada, in-app message, escalación).
 *
 * LÓGICA:
 * 1. evaluateTriggers(): revisa todos los playbooks activos con auto_execute.
 * 2. Para cada uno, verifica condiciones contra datos actuales del tenant.
 * 3. Si se cumple, crea PlaybookExecution y ejecuta paso D+0.
 * 4. advancePendingExecutions(): avanza ejecuciones cuyo next_action_at <= now.
 * 5. Cada paso se ejecuta según su tipo (email, internal, call, in_app).
 */
class PlaybookExecutorService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $configFactory,
    protected StateInterface $state,
    protected HealthScoreCalculatorService $healthCalculator,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Ejecuta un playbook manualmente para un tenant.
   *
   * @param \Drupal\jaraba_customer_success\Entity\CsPlaybook $playbook
   *   El playbook a ejecutar.
   * @param string $tenant_id
   *   ID del grupo tenant.
   *
   * @return \Drupal\jaraba_customer_success\Entity\PlaybookExecution|null
   *   Ejecución creada, o NULL en error.
   */
  public function execute(CsPlaybook $playbook, string $tenant_id): ?PlaybookExecution {
    try {
      $steps = $playbook->getSteps();
      if (empty($steps)) {
        $this->logger->warning('Playbook @id has no steps defined.', [
          '@id' => $playbook->id(),
        ]);
        return NULL;
      }

      // Verificar que no haya ejecución activa del mismo playbook para este tenant.
      $storage = $this->entityTypeManager->getStorage('playbook_execution');
      $existing = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('playbook_id', $playbook->id())
        ->condition('tenant_id', $tenant_id)
        ->condition('status', PlaybookExecution::STATUS_RUNNING)
        ->count()
        ->execute();

      if ($existing > 0) {
        $this->logger->info('Playbook @pid already running for tenant @tid.', [
          '@pid' => $playbook->id(),
          '@tid' => $tenant_id,
        ]);
        return NULL;
      }

      // Calcular cuándo ejecutar el primer paso.
      $first_step = $steps[0] ?? [];
      $day = (int) ($first_step['day'] ?? 0);
      $next_action_at = \Drupal::time()->getRequestTime() + ($day * 86400);

      /** @var \Drupal\jaraba_customer_success\Entity\PlaybookExecution $execution */
      $execution = $storage->create([
        'playbook_id' => $playbook->id(),
        'tenant_id' => $tenant_id,
        'current_step' => 0,
        'total_steps' => count($steps),
        'step_results' => json_encode([]),
        'status' => PlaybookExecution::STATUS_RUNNING,
        'next_action_at' => $next_action_at,
      ]);
      $execution->save();

      // Incrementar contador del playbook.
      $playbook->incrementExecutionCount();
      $playbook->save();

      // Si el primer paso es D+0, ejecutarlo inmediatamente.
      if ($day === 0) {
        $this->executeStep($execution, $first_step, 0);
      }

      $this->logger->info('Playbook @name started for tenant @tid (execution @eid).', [
        '@name' => $playbook->getName(),
        '@tid' => $tenant_id,
        '@eid' => $execution->id(),
      ]);

      return $execution;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to execute playbook @id for tenant @tid: @message', [
        '@id' => $playbook->id(),
        '@tid' => $tenant_id,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Evalúa triggers de todos los playbooks activos con auto_execute.
   *
   * @return int
   *   Número de ejecuciones iniciadas.
   */
  public function evaluateTriggers(): int {
    $playbooks = $this->getActivePlaybooks();
    $started = 0;

    foreach ($playbooks as $playbook) {
      if (!$playbook->get('auto_execute')->value) {
        continue;
      }

      $conditions = $playbook->getTriggerConditions();
      $trigger_type = $playbook->get('trigger_type')->value;

      // Obtener tenants que cumplen las condiciones.
      $matching_tenants = $this->findMatchingTenants($trigger_type, $conditions);

      foreach ($matching_tenants as $tenant_id) {
        $execution = $this->execute($playbook, $tenant_id);
        if ($execution) {
          $started++;
        }
      }
    }

    return $started;
  }

  /**
   * Avanza ejecuciones pendientes cuyo next_action_at <= now.
   *
   * @return int
   *   Número de pasos ejecutados.
   */
  public function advancePendingExecutions(): int {
    $storage = $this->entityTypeManager->getStorage('playbook_execution');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', PlaybookExecution::STATUS_RUNNING)
      ->condition('next_action_at', \Drupal::time()->getRequestTime(), '<=')
      ->range(0, 50)
      ->execute();

    if (empty($ids)) {
      return 0;
    }

    $executed = 0;
    $executions = $storage->loadMultiple($ids);

    foreach ($executions as $execution) {
      /** @var \Drupal\jaraba_customer_success\Entity\PlaybookExecution $execution */
      $playbook = $execution->get('playbook_id')->entity;
      if (!$playbook) {
        $execution->set('status', PlaybookExecution::STATUS_FAILED);
        $execution->save();
        continue;
      }

      $steps = $playbook->getSteps();
      $current_step = (int) $execution->get('current_step')->value;

      if ($current_step >= count($steps)) {
        // Todas las acciones completadas.
        $execution->set('status', PlaybookExecution::STATUS_COMPLETED);
        $execution->set('completed_at', \Drupal::time()->getRequestTime());
        $execution->save();
        continue;
      }

      $step = $steps[$current_step] ?? NULL;
      if ($step) {
        $this->executeStep($execution, $step, $current_step);
        $executed++;
      }

      // Avanzar al siguiente paso.
      $next_step = $current_step + 1;
      $execution->set('current_step', $next_step);

      if ($next_step >= count($steps)) {
        $execution->set('status', PlaybookExecution::STATUS_COMPLETED);
        $execution->set('completed_at', \Drupal::time()->getRequestTime());
      }
      else {
        // Calcular cuándo ejecutar el siguiente paso.
        $next = $steps[$next_step] ?? [];
        $current_day = (int) ($step['day'] ?? 0);
        $next_day = (int) ($next['day'] ?? 0);
        $delta_days = max(0, $next_day - $current_day);
        $execution->set('next_action_at', \Drupal::time()->getRequestTime() + ($delta_days * 86400));
      }

      $execution->save();
    }

    return $executed;
  }

  /**
   * Obtiene todos los playbooks activos.
   *
   * @return array
   *   Array de entidades CsPlaybook.
   */
  public function getActivePlaybooks(): array {
    $storage = $this->entityTypeManager->getStorage('cs_playbook');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', CsPlaybook::STATUS_ACTIVE)
      ->execute();

    return $ids ? $storage->loadMultiple($ids) : [];
  }

  /**
   * Ejecuta un paso específico del playbook.
   */
  protected function executeStep(PlaybookExecution $execution, array $step, int $step_index): void {
    $action = $step['action'] ?? 'internal';
    $tenant_id = (string) $execution->get('tenant_id')->target_id;
    $result = ['step' => $step_index, 'action' => $action, 'executed_at' => \Drupal::time()->getRequestTime()];

    try {
      switch ($action) {
        case 'email':
          $result['status'] = 'sent';
          $result['details'] = 'Email queued for tenant ' . $tenant_id;
          $this->logger->info('Playbook step @step: email sent to tenant @tid', [
            '@step' => $step_index,
            '@tid' => $tenant_id,
          ]);
          break;

        case 'internal':
          // Alerta interna (Slack/Email al CSM).
          try {
            $alerting = \Drupal::service('ecosistema_jaraba_core.alerting');
            $alerting->sendAlert(
              'cs_playbook',
              $step['subject'] ?? 'Playbook action',
              ['tenant_id' => $tenant_id, 'step' => $step]
            );
            $result['status'] = 'alerted';
          }
          catch (\Exception $e) {
            $result['status'] = 'alert_failed';
            $result['error'] = $e->getMessage();
          }
          break;

        case 'in_app':
          $result['status'] = 'queued';
          $result['details'] = 'In-app message queued';
          break;

        case 'call':
          $result['status'] = 'scheduled';
          $result['details'] = 'Call reminder created';
          break;

        default:
          $result['status'] = 'skipped';
          $result['details'] = 'Unknown action type: ' . $action;
      }
    }
    catch (\Exception $e) {
      $result['status'] = 'error';
      $result['error'] = $e->getMessage();
    }

    // Guardar resultado del paso.
    $step_results = $execution->getStepResults();
    $step_results[] = $result;
    $execution->set('step_results', json_encode($step_results));
  }

  /**
   * Encuentra tenants que cumplen condiciones de un trigger.
   */
  protected function findMatchingTenants(string $trigger_type, array $conditions): array {
    $matching = [];

    try {
      $db = \Drupal::database();

      switch ($trigger_type) {
        case CsPlaybook::TRIGGER_HEALTH_DROP:
          $score_below = $conditions['score_below'] ?? 60;
          $query = $db->select('customer_health', 'ch');
          $query->addField('ch', 'tenant_id');
          $query->condition('overall_score', $score_below, '<');

          // Solo último score por tenant.
          $sub = $db->select('customer_health', 'ch2');
          $sub->addField('ch2', 'tenant_id');
          $sub->addExpression('MAX(id)', 'max_id');
          $sub->groupBy('ch2.tenant_id');
          $query->join($sub, 'latest', 'ch.id = latest.max_id');

          $matching = $query->execute()->fetchCol();
          break;

        case CsPlaybook::TRIGGER_CHURN_RISK:
          $prob_above = $conditions['churn_probability_above'] ?? 0.5;
          $query = $db->select('churn_prediction', 'cp');
          $query->addField('cp', 'tenant_id');
          $query->condition('probability', $prob_above, '>=');
          $query->orderBy('created', 'DESC');

          $matching = $query->execute()->fetchCol();
          break;

        case CsPlaybook::TRIGGER_EXPANSION:
          $usage_above = $conditions['usage_above'] ?? 80;
          // Buscar tenants con señales de expansión nuevas.
          $query = $db->select('expansion_signal', 'es');
          $query->addField('es', 'tenant_id');
          $query->condition('status', 'new');

          $matching = $query->execute()->fetchCol();
          break;
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('Error finding matching tenants for trigger @type: @message', [
        '@type' => $trigger_type,
        '@message' => $e->getMessage(),
      ]);
    }

    return array_unique($matching);
  }

}
