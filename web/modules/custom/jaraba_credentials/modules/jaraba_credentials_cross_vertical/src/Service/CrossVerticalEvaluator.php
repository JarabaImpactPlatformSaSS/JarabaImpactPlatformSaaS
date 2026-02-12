<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials_cross_vertical\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\jaraba_credentials\Entity\IssuedCredential;
use Drupal\jaraba_credentials\Service\CredentialIssuer;
use Drupal\jaraba_credentials_cross_vertical\Entity\CrossVerticalProgress;
use Drupal\jaraba_credentials_cross_vertical\Entity\CrossVerticalRule;
use Psr\Log\LoggerInterface;

/**
 * Evaluador de reglas cross-vertical.
 */
class CrossVerticalEvaluator {

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
    $this->logger = $loggerFactory->get('jaraba_credentials_cross_vertical');
  }

  /**
   * Evalúa todas las reglas cross-vertical para un usuario.
   *
   * @return array
   *   Array de badges emitidos.
   */
  public function evaluateForUser(int $uid): array {
    $issued = [];

    $ruleIds = $this->entityTypeManager->getStorage('cross_vertical_rule')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', TRUE)
      ->execute();

    if (empty($ruleIds)) {
      return [];
    }

    $rules = $this->entityTypeManager->getStorage('cross_vertical_rule')->loadMultiple($ruleIds);
    $userActivity = $this->getUserActivitySummary($uid);

    foreach ($rules as $rule) {
      /** @var \Drupal\jaraba_credentials_cross_vertical\Entity\CrossVerticalRule $rule */
      $existingProgress = $this->getProgress($rule, $uid);
      if ($existingProgress && $existingProgress->get('status')->value === CrossVerticalProgress::STATUS_COMPLETED) {
        continue;
      }

      if ($this->evaluateConditions($rule, $userActivity)) {
        $credential = $this->awardCrossVerticalBadge($rule, $uid);
        if ($credential) {
          $issued[] = [
            'rule' => $rule,
            'credential' => $credential,
          ];
        }
      }
      else {
        // Update progress even if not completed.
        $this->updateProgress($rule, $uid, $userActivity);
      }
    }

    return $issued;
  }

  /**
   * Evalúa condiciones de una regla contra la actividad del usuario.
   */
  public function evaluateConditions(CrossVerticalRule $rule, array $userActivity): bool {
    $conditions = $rule->getConditions();
    $verticalsRequired = $rule->getVerticalsRequired();

    foreach ($verticalsRequired as $vertical) {
      if (!isset($conditions[$vertical])) {
        continue;
      }

      $verticalActivity = $userActivity[$vertical] ?? [];
      $verticalConditions = $conditions[$vertical];

      // Check credentials_count.
      if (isset($verticalConditions['credentials_count'])) {
        $count = $verticalActivity['credentials_count'] ?? 0;
        if ($count < $verticalConditions['credentials_count']) {
          return FALSE;
        }
      }

      // Check milestones_achieved.
      if (isset($verticalConditions['milestones_achieved'])) {
        $milestones = $verticalActivity['milestones'] ?? [];
        $required = $verticalConditions['milestones_achieved'];
        if (is_array($required)) {
          if (!empty(array_diff($required, $milestones))) {
            return FALSE;
          }
        }
        elseif (is_int($required) && count($milestones) < $required) {
          return FALSE;
        }
      }

      // Check transactions_count.
      if (isset($verticalConditions['transactions_count'])) {
        $count = $verticalActivity['transactions_count'] ?? 0;
        if ($count < $verticalConditions['transactions_count']) {
          return FALSE;
        }
      }

      // Check gmv_threshold.
      if (isset($verticalConditions['gmv_threshold'])) {
        $gmv = $verticalActivity['gmv'] ?? 0.0;
        if ($gmv < $verticalConditions['gmv_threshold']) {
          return FALSE;
        }
      }
    }

    return TRUE;
  }

  /**
   * Emite credencial cross-vertical + bonus.
   */
  public function awardCrossVerticalBadge(CrossVerticalRule $rule, int $uid): ?IssuedCredential {
    $resultTemplateId = $rule->get('result_template_id')->target_id ?? NULL;
    if (!$resultTemplateId) {
      $this->logger->warning('Regla @name sin template resultante.', [
        '@name' => $rule->get('name')->value,
      ]);
      return NULL;
    }

    $template = $this->entityTypeManager->getStorage('credential_template')->load($resultTemplateId);
    if (!$template) {
      return NULL;
    }

    // Check for duplicate.
    $existing = $this->entityTypeManager->getStorage('issued_credential')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('recipient_id', $uid)
      ->condition('template_id', $resultTemplateId)
      ->condition('status', IssuedCredential::STATUS_ACTIVE)
      ->count()
      ->execute();

    if ($existing > 0) {
      return NULL;
    }

    try {
      $credential = $this->credentialIssuer->issueCredential($template, $uid, [
        'cross_vertical_rule_id' => $rule->id(),
        'rarity' => $rule->getRarity(),
      ]);

      // Mark progress as completed.
      $this->markCompleted($rule, $uid, $credential);

      // Award bonus.
      $bonusCredits = (int) ($rule->get('bonus_credits')->value ?? 0);
      if ($bonusCredits > 0 && \Drupal::hasService('jaraba_billing.impact_credits')) {
        try {
          \Drupal::service('jaraba_billing.impact_credits')
            ->awardCredits($uid, 'cross_vertical_completion', $bonusCredits);
        }
        catch (\Exception $e) {
          // Non-critical, log and continue.
        }
      }

      $this->logger->info('Cross-vertical badge @name emitido para usuario #@uid (rareza: @rarity)', [
        '@name' => $rule->get('name')->value,
        '@uid' => $uid,
        '@rarity' => $rule->getRarity(),
      ]);

      return $credential;
    }
    catch (\Exception $e) {
      $this->logger->error('Error emitiendo badge cross-vertical @name: @msg', [
        '@name' => $rule->get('name')->value,
        '@msg' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Obtiene resumen de actividad del usuario por vertical.
   */
  protected function getUserActivitySummary(int $uid): array {
    if (\Drupal::hasService('jaraba_credentials_cross_vertical.activity_tracker')) {
      return \Drupal::service('jaraba_credentials_cross_vertical.activity_tracker')
        ->getUserActivitySummary($uid);
    }
    return [];
  }

  /**
   * Obtiene el progreso existente.
   */
  protected function getProgress(CrossVerticalRule $rule, int $uid): ?CrossVerticalProgress {
    $ids = $this->entityTypeManager->getStorage('cross_vertical_progress')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('rule_id', $rule->id())
      ->condition('user_id', $uid)
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    return $this->entityTypeManager->getStorage('cross_vertical_progress')->load(reset($ids));
  }

  /**
   * Actualiza progreso sin completar.
   */
  protected function updateProgress(CrossVerticalRule $rule, int $uid, array $userActivity): void {
    $progress = $this->getProgress($rule, $uid);
    if (!$progress) {
      $progress = $this->entityTypeManager->getStorage('cross_vertical_progress')->create([
        'rule_id' => $rule->id(),
        'user_id' => $uid,
      ]);
    }

    $verticals = $rule->getVerticalsRequired();
    $conditions = $rule->getConditions();
    $verticalPercents = [];

    foreach ($verticals as $vertical) {
      $verticalActivity = $userActivity[$vertical] ?? [];
      $verticalConds = $conditions[$vertical] ?? [];
      $met = 0;
      $total = 0;

      foreach ($verticalConds as $key => $required) {
        $total++;
        $actual = $verticalActivity[$key] ?? ($verticalActivity[str_replace('_count', '', $key)] ?? 0);
        if (is_numeric($required) && is_numeric($actual) && $actual >= $required) {
          $met++;
        }
      }

      $verticalPercents[$vertical] = $total > 0 ? (int) round(($met / $total) * 100) : 0;
    }

    $overallPercent = !empty($verticalPercents) ? (int) round(array_sum($verticalPercents) / count($verticalPercents)) : 0;

    $progress->set('vertical_progress', json_encode($verticalPercents));
    $progress->set('overall_percent', min($overallPercent, 100));
    $progress->save();
  }

  /**
   * Marca progreso como completado.
   */
  protected function markCompleted(CrossVerticalRule $rule, int $uid, IssuedCredential $credential): void {
    $progress = $this->getProgress($rule, $uid);
    if (!$progress) {
      $progress = $this->entityTypeManager->getStorage('cross_vertical_progress')->create([
        'rule_id' => $rule->id(),
        'user_id' => $uid,
      ]);
    }

    $progress->set('status', CrossVerticalProgress::STATUS_COMPLETED);
    $progress->set('overall_percent', 100);
    $progress->set('completed_at', date('Y-m-d\TH:i:s'));
    $progress->set('result_credential_id', $credential->id());
    $progress->save();
  }

}
