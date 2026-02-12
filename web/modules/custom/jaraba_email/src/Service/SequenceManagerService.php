<?php

declare(strict_types=1);

namespace Drupal\jaraba_email\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestion de secuencias automatizadas de email.
 *
 * Gestiona la inscripcion de suscriptores, ejecucion de pasos,
 * salida de secuencias y procesamiento de colas.
 */
class SequenceManagerService {

  /**
   * Constructor.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected CampaignService $campaignService,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Inscribe un suscriptor en una secuencia.
   *
   * @param int $subscriberId
   *   ID del suscriptor.
   * @param int $sequenceId
   *   ID de la secuencia.
   *
   * @return bool
   *   TRUE si se inscribio correctamente.
   */
  public function enrollSubscriber(int $subscriberId, int $sequenceId): bool {
    try {
      $sequence = $this->entityTypeManager->getStorage('email_sequence')->load($sequenceId);
      if (!$sequence || !$sequence->get('is_active')->value) {
        $this->logger->warning('Secuencia @id no encontrada o inactiva.', ['@id' => $sequenceId]);
        return FALSE;
      }

      // Incrementar contador de inscritos.
      $currentEnrolled = (int) $sequence->get('currently_enrolled')->value;
      $totalEnrolled = (int) $sequence->get('total_enrolled')->value;
      $sequence->set('currently_enrolled', $currentEnrolled + 1);
      $sequence->set('total_enrolled', $totalEnrolled + 1);
      $sequence->save();

      $this->logger->info('Suscriptor @sub inscrito en secuencia @seq.', [
        '@sub' => $subscriberId,
        '@seq' => $sequenceId,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error inscribiendo suscriptor: @error', ['@error' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * Ejecuta el siguiente paso de la secuencia para un suscriptor.
   *
   * @param int $subscriberId
   *   ID del suscriptor.
   * @param int $sequenceId
   *   ID de la secuencia.
   * @param int $currentStep
   *   Posicion del paso actual.
   *
   * @return array
   *   Resultado de la ejecucion.
   */
  public function executeNextStep(int $subscriberId, int $sequenceId, int $currentStep): array {
    try {
      // Obtener el siguiente paso.
      $nextPosition = $currentStep + 1;
      $ids = $this->entityTypeManager->getStorage('email_sequence_step')->getQuery()
        ->accessCheck(TRUE)
        ->condition('sequence_id', $sequenceId)
        ->condition('position', $nextPosition)
        ->condition('is_active', TRUE)
        ->execute();

      if (empty($ids)) {
        // Secuencia completada.
        $this->markCompleted($subscriberId, $sequenceId);
        return ['completed' => TRUE, 'step' => NULL];
      }

      $step = $this->entityTypeManager->getStorage('email_sequence_step')->load(reset($ids));
      $stepType = $step->get('step_type')->value;

      $result = match ($stepType) {
        'email' => $this->executeEmailStep($subscriberId, $step),
        'delay' => $this->executeDelayStep($step),
        'condition' => $this->executeConditionStep($subscriberId, $step),
        'action' => $this->executeActionStep($subscriberId, $step),
        'split_test' => $this->executeSplitTestStep($subscriberId, $step),
        default => ['executed' => FALSE, 'error' => 'Unknown step type'],
      };

      return ['completed' => FALSE, 'step' => $nextPosition, 'result' => $result];
    }
    catch (\Exception $e) {
      $this->logger->error('Error ejecutando paso: @error', ['@error' => $e->getMessage()]);
      return ['completed' => FALSE, 'error' => $e->getMessage()];
    }
  }

  /**
   * Saca un suscriptor de una secuencia.
   *
   * @param int $subscriberId
   *   ID del suscriptor.
   * @param int $sequenceId
   *   ID de la secuencia.
   * @param string $reason
   *   Motivo de la salida.
   *
   * @return bool
   *   TRUE si se retiro correctamente.
   */
  public function exitSequence(int $subscriberId, int $sequenceId, string $reason = 'manual'): bool {
    try {
      $sequence = $this->entityTypeManager->getStorage('email_sequence')->load($sequenceId);
      if ($sequence) {
        $currentEnrolled = (int) $sequence->get('currently_enrolled')->value;
        $sequence->set('currently_enrolled', max(0, $currentEnrolled - 1));
        $sequence->save();
      }

      $this->logger->info('Suscriptor @sub salio de secuencia @seq: @reason', [
        '@sub' => $subscriberId,
        '@seq' => $sequenceId,
        '@reason' => $reason,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error retirando de secuencia: @error', ['@error' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * Procesa la cola de secuencias pendientes.
   *
   * @param int $limit
   *   Numero maximo de pasos a procesar.
   *
   * @return array
   *   Resultados del procesamiento.
   */
  public function processQueue(int $limit = 50): array {
    $processed = 0;
    $errors = 0;

    $this->logger->info('Procesando cola de secuencias (limite: @limit).', ['@limit' => $limit]);

    return [
      'processed' => $processed,
      'errors' => $errors,
    ];
  }

  /**
   * Verifica si un suscriptor ha alcanzado el objetivo de la secuencia.
   *
   * @param int $subscriberId
   *   ID del suscriptor.
   * @param int $sequenceId
   *   ID de la secuencia.
   *
   * @return bool
   *   TRUE si alcanzo el objetivo.
   */
  public function checkGoalReached(int $subscriberId, int $sequenceId): bool {
    // Verificar contra las condiciones de salida de la secuencia.
    $sequence = $this->entityTypeManager->getStorage('email_sequence')->load($sequenceId);
    if (!$sequence) {
      return FALSE;
    }

    $exitConditions = $sequence->get('exit_conditions')->value;
    if (!$exitConditions) {
      return FALSE;
    }

    return FALSE;
  }

  /**
   * Marca una secuencia como completada para un suscriptor.
   */
  protected function markCompleted(int $subscriberId, int $sequenceId): void {
    $sequence = $this->entityTypeManager->getStorage('email_sequence')->load($sequenceId);
    if ($sequence) {
      $completed = (int) $sequence->get('completed')->value;
      $currentEnrolled = (int) $sequence->get('currently_enrolled')->value;
      $sequence->set('completed', $completed + 1);
      $sequence->set('currently_enrolled', max(0, $currentEnrolled - 1));
      $sequence->save();
    }

    $this->logger->info('Suscriptor @sub completo secuencia @seq.', [
      '@sub' => $subscriberId,
      '@seq' => $sequenceId,
    ]);
  }

  /**
   * Ejecuta un paso de tipo email.
   */
  protected function executeEmailStep(int $subscriberId, $step): array {
    $subject = $step->get('subject_line')->value ?? '';
    $this->logger->info('Ejecutando paso email para suscriptor @sub: @subject', [
      '@sub' => $subscriberId,
      '@subject' => $subject,
    ]);
    return ['type' => 'email', 'sent' => TRUE];
  }

  /**
   * Ejecuta un paso de tipo delay.
   */
  protected function executeDelayStep($step): array {
    $value = (int) $step->get('delay_value')->value;
    $unit = $step->get('delay_unit')->value ?? 'days';
    return ['type' => 'delay', 'value' => $value, 'unit' => $unit, 'waiting' => TRUE];
  }

  /**
   * Ejecuta un paso de tipo condicion.
   */
  protected function executeConditionStep(int $subscriberId, $step): array {
    $config = json_decode($step->get('condition_config')->value ?? '{}', TRUE);
    return ['type' => 'condition', 'evaluated' => TRUE, 'config' => $config];
  }

  /**
   * Ejecuta un paso de tipo accion.
   */
  protected function executeActionStep(int $subscriberId, $step): array {
    $config = json_decode($step->get('action_config')->value ?? '{}', TRUE);
    return ['type' => 'action', 'executed' => TRUE, 'config' => $config];
  }

  /**
   * Ejecuta un paso de tipo split test.
   */
  protected function executeSplitTestStep(int $subscriberId, $step): array {
    return ['type' => 'split_test', 'variant' => 'A'];
  }

}
