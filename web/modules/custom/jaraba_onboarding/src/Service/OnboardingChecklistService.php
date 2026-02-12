<?php

declare(strict_types=1);

namespace Drupal\jaraba_onboarding\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestion de checklists de onboarding.
 *
 * Proporciona la logica para construir checklists interactivos
 * basados en la configuracion del template y el progreso del usuario.
 */
class OnboardingChecklistService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Obtiene el checklist de onboarding para un usuario.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array
   *   Array de items del checklist con id, label, description, completed.
   */
  public function getChecklist(int $userId): array {
    try {
      $progressStorage = $this->entityTypeManager->getStorage('user_onboarding_progress');
      $ids = $progressStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->sort('created', 'DESC')
        ->range(0, 1)
        ->execute();

      if (empty($ids)) {
        return [];
      }

      /** @var \Drupal\jaraba_onboarding\Entity\UserOnboardingProgress $progress */
      $progress = $progressStorage->load(reset($ids));
      if (!$progress) {
        return [];
      }

      $completedSteps = $progress->getCompletedSteps();

      // Cargar el template para obtener la configuracion de pasos.
      $templateId = $progress->get('template_id')->target_id;
      if (!$templateId) {
        return [];
      }

      $templateStorage = $this->entityTypeManager->getStorage('onboarding_template');
      /** @var \Drupal\jaraba_onboarding\Entity\OnboardingTemplate|null $template */
      $template = $templateStorage->load($templateId);
      if (!$template) {
        return [];
      }

      $stepsConfig = $template->getStepsConfig();
      $checklist = [];

      foreach ($stepsConfig as $step) {
        $stepId = $step['id'] ?? '';
        $checklist[] = [
          'id' => $stepId,
          'label' => $step['label'] ?? '',
          'description' => $step['description'] ?? '',
          'completed' => in_array($stepId, $completedSteps, TRUE),
          'icon' => $step['icon'] ?? 'check',
          'order' => $step['order'] ?? 0,
        ];
      }

      // Ordenar por orden.
      usort($checklist, fn(array $a, array $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

      return $checklist;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo checklist para usuario @user: @error', [
        '@user' => $userId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Comprueba si el checklist del usuario esta completo.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return bool
   *   TRUE si todos los items estan completados.
   */
  public function isChecklistComplete(int $userId): bool {
    try {
      $checklist = $this->getChecklist($userId);

      if (empty($checklist)) {
        return FALSE;
      }

      foreach ($checklist as $item) {
        if (!$item['completed']) {
          return FALSE;
        }
      }

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error verificando checklist completo para usuario @user: @error', [
        '@user' => $userId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

}
