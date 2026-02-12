<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\jaraba_credentials\Entity\CredentialStack;
use Drupal\jaraba_credentials\Entity\IssuedCredential;
use Drupal\jaraba_credentials\Entity\UserStackProgress;
use Psr\Log\LoggerInterface;

/**
 * Evalúa si un usuario ha completado algún stack de credenciales.
 */
class StackEvaluationService {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected CredentialIssuer $credentialIssuer;
  protected LoggerInterface $logger;

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    CredentialIssuer $credentialIssuer,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->credentialIssuer = $credentialIssuer;
    $this->logger = $loggerFactory->get('jaraba_credentials');
  }

  /**
   * Evalúa todos los stacks activos para un usuario tras emitir credencial.
   *
   * @param int $uid
   *   ID del usuario.
   * @param int $newTemplateId
   *   ID del template recién emitido.
   *
   * @return array
   *   Array de stacks completados con credenciales emitidas.
   */
  public function evaluateForUser(int $uid, int $newTemplateId): array {
    $completedStacks = [];

    // Obtener todos los template IDs que el usuario tiene.
    $userTemplateIds = $this->getUserTemplateIds($uid);

    // Obtener stacks activos.
    $stackIds = $this->entityTypeManager->getStorage('credential_stack')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', TRUE)
      ->execute();

    if (empty($stackIds)) {
      return [];
    }

    $stacks = $this->entityTypeManager->getStorage('credential_stack')->loadMultiple($stackIds);

    foreach ($stacks as $stack) {
      /** @var \Drupal\jaraba_credentials\Entity\CredentialStack $stack */
      $requiredIds = $stack->getRequiredTemplateIds();

      // Solo evaluar stacks que incluyan el nuevo template.
      if (!in_array($newTemplateId, $requiredIds, TRUE) &&
          !in_array($newTemplateId, $stack->getOptionalTemplateIds(), TRUE)) {
        continue;
      }

      // Verificar si ya se completó este stack.
      $existingProgress = $this->getExistingProgress($stack, $uid);
      if ($existingProgress && $existingProgress->get('status')->value === UserStackProgress::STATUS_COMPLETED) {
        continue;
      }

      if ($this->checkStackCompletion($stack, $userTemplateIds)) {
        $credential = $this->issueStackCredential($stack, $uid, $userTemplateIds);
        if ($credential) {
          $completedStacks[] = [
            'stack' => $stack,
            'credential' => $credential,
          ];
        }
      }
    }

    return $completedStacks;
  }

  /**
   * Verifica si los templates del usuario cumplen el mínimo requerido.
   */
  public function checkStackCompletion(CredentialStack $stack, array $userTemplateIds): bool {
    $requiredIds = $stack->getRequiredTemplateIds();
    $matched = array_intersect($requiredIds, $userTemplateIds);
    return count($matched) >= $stack->getMinRequired();
  }

  /**
   * Emite credencial de stack + bonus.
   */
  public function issueStackCredential(CredentialStack $stack, int $uid, array $componentIds): ?IssuedCredential {
    $resultTemplateId = $stack->get('result_template_id')->target_id ?? NULL;
    if (!$resultTemplateId) {
      $this->logger->warning('Stack @name sin template resultante configurado.', [
        '@name' => $stack->get('name')->value,
      ]);
      return NULL;
    }

    $template = $this->entityTypeManager->getStorage('credential_template')->load($resultTemplateId);
    if (!$template) {
      return NULL;
    }

    try {
      $credential = $this->credentialIssuer->issueCredential($template, $uid, [
        'stack_id' => $stack->id(),
        'component_templates' => $componentIds,
      ]);

      // Actualizar progreso a completado.
      $this->markStackCompleted($stack, $uid, $credential);

      // Otorgar bonus credits y XP via servicios si están disponibles.
      $this->awardBonus($uid, $stack);

      $this->logger->info('Stack @name completado por usuario #@uid. Credencial: @uuid', [
        '@name' => $stack->get('name')->value,
        '@uid' => $uid,
        '@uuid' => $credential->uuid(),
      ]);

      return $credential;
    }
    catch (\Exception $e) {
      $this->logger->error('Error emitiendo credencial de stack @name: @msg', [
        '@name' => $stack->get('name')->value,
        '@msg' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Obtiene los IDs de template que un usuario ya tiene credenciales activas.
   */
  protected function getUserTemplateIds(int $uid): array {
    $ids = $this->entityTypeManager->getStorage('issued_credential')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('recipient_id', $uid)
      ->condition('status', IssuedCredential::STATUS_ACTIVE)
      ->execute();

    if (empty($ids)) {
      return [];
    }

    $credentials = $this->entityTypeManager->getStorage('issued_credential')->loadMultiple($ids);
    $templateIds = [];
    foreach ($credentials as $credential) {
      $tid = $credential->get('template_id')->target_id ?? NULL;
      if ($tid) {
        $templateIds[] = (int) $tid;
      }
    }

    return array_unique($templateIds);
  }

  /**
   * Obtiene el progreso existente de un usuario en un stack.
   */
  protected function getExistingProgress(CredentialStack $stack, int $uid): ?UserStackProgress {
    $ids = $this->entityTypeManager->getStorage('user_stack_progress')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('stack_id', $stack->id())
      ->condition('user_id', $uid)
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    return $this->entityTypeManager->getStorage('user_stack_progress')->load(reset($ids));
  }

  /**
   * Marca un stack como completado para un usuario.
   */
  protected function markStackCompleted(CredentialStack $stack, int $uid, IssuedCredential $credential): void {
    $progress = $this->getExistingProgress($stack, $uid);
    if (!$progress) {
      $progress = $this->entityTypeManager->getStorage('user_stack_progress')->create([
        'stack_id' => $stack->id(),
        'user_id' => $uid,
      ]);
    }

    $progress->set('status', UserStackProgress::STATUS_COMPLETED);
    $progress->set('progress_percent', 100);
    $progress->set('completed_at', date('Y-m-d\TH:i:s'));
    $progress->set('result_credential_id', $credential->id());
    $progress->set('completed_templates', json_encode($stack->getRequiredTemplateIds()));
    $progress->save();
  }

  /**
   * Otorga bonus credits y XP al usuario.
   */
  protected function awardBonus(int $uid, CredentialStack $stack): void {
    $bonusCredits = (int) ($stack->get('bonus_credits')->value ?? 0);
    $bonusXp = (int) ($stack->get('bonus_xp')->value ?? 0);

    if ($bonusCredits > 0 && \Drupal::hasService('jaraba_billing.impact_credits')) {
      try {
        $creditService = \Drupal::service('jaraba_billing.impact_credits');
        $creditService->awardCredits($uid, 'stack_completion', $bonusCredits, [
          'stack_id' => $stack->id(),
          'stack_name' => $stack->get('name')->value,
        ]);
      }
      catch (\Exception $e) {
        $this->logger->warning('Error awarding bonus credits: @msg', ['@msg' => $e->getMessage()]);
      }
    }

    if ($bonusXp > 0 && \Drupal::hasService('ecosistema_jaraba_core.badge_award')) {
      try {
        $badgeService = \Drupal::service('ecosistema_jaraba_core.badge_award');
        $badgeService->awardBadge(0, $uid, 'Stack completion XP: ' . $bonusXp);
      }
      catch (\Exception $e) {
        $this->logger->warning('Error awarding bonus XP: @msg', ['@msg' => $e->getMessage()]);
      }
    }
  }

}
