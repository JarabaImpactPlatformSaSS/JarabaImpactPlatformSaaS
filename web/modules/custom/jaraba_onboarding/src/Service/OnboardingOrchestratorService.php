<?php

declare(strict_types=1);

namespace Drupal\jaraba_onboarding\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantOnboardingService;
use Psr\Log\LoggerInterface;

/**
 * Orquestador principal del flujo de onboarding.
 *
 * Coordina la logica de negocio del onboarding: iniciar flujos,
 * completar pasos y consultar progreso. Delega en el servicio
 * ecosistema_jaraba_core.tenant_onboarding para operaciones
 * relacionadas con el tenant.
 */
class OnboardingOrchestratorService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected TenantOnboardingService $tenantOnboarding,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Inicia el onboarding para un usuario con un template dado.
   *
   * @param int $userId
   *   ID del usuario.
   * @param int $templateId
   *   ID del template de onboarding.
   *
   * @return int|null
   *   ID del progreso creado, o NULL si falla.
   */
  public function startOnboarding(int $userId, int $templateId): ?int {
    try {
      $storage = $this->entityTypeManager->getStorage('onboarding_template');
      $template = $storage->load($templateId);

      if (!$template) {
        $this->logger->warning('Template de onboarding @id no encontrado.', ['@id' => $templateId]);
        return NULL;
      }

      // Verificar si ya existe un progreso activo para este usuario/template.
      $progressStorage = $this->entityTypeManager->getStorage('user_onboarding_progress');
      $existing = $progressStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->condition('template_id', $templateId)
        ->execute();

      if (!empty($existing)) {
        $this->logger->info('El usuario @user ya tiene progreso para template @template.', [
          '@user' => $userId,
          '@template' => $templateId,
        ]);
        return (int) reset($existing);
      }

      // Crear nuevo progreso.
      $progress = $progressStorage->create([
        'user_id' => $userId,
        'template_id' => $templateId,
        'current_step' => 0,
        'completed_steps' => json_encode([]),
        'progress_percentage' => 0,
        'started_at' => time(),
      ]);
      $progress->save();

      $this->logger->info('Onboarding iniciado para usuario @user con template @template. Progreso: @progress', [
        '@user' => $userId,
        '@template' => $templateId,
        '@progress' => $progress->id(),
      ]);

      return (int) $progress->id();
    }
    catch (\Exception $e) {
      $this->logger->error('Error iniciando onboarding para usuario @user: @error', [
        '@user' => $userId,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Marca un paso como completado en un progreso de onboarding.
   *
   * @param int $progressId
   *   ID del progreso de onboarding.
   * @param string $stepId
   *   Identificador del paso a completar.
   *
   * @return bool
   *   TRUE si el paso se completo con exito.
   */
  public function completeStep(int $progressId, string $stepId): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('user_onboarding_progress');
      /** @var \Drupal\jaraba_onboarding\Entity\UserOnboardingProgress|null $progress */
      $progress = $storage->load($progressId);

      if (!$progress) {
        $this->logger->warning('Progreso de onboarding @id no encontrado.', ['@id' => $progressId]);
        return FALSE;
      }

      // Obtener pasos completados.
      $completedSteps = $progress->getCompletedSteps();

      // Verificar si el paso ya esta completado.
      if (in_array($stepId, $completedSteps, TRUE)) {
        return TRUE;
      }

      // Agregar el paso.
      $completedSteps[] = $stepId;
      $progress->setCompletedSteps($completedSteps);

      // Calcular el porcentaje de progreso.
      $templateStorage = $this->entityTypeManager->getStorage('onboarding_template');
      $templateId = $progress->get('template_id')->target_id;
      /** @var \Drupal\jaraba_onboarding\Entity\OnboardingTemplate|null $template */
      $template = $templateId ? $templateStorage->load($templateId) : NULL;

      if ($template) {
        $stepsConfig = $template->getStepsConfig();
        $totalSteps = count($stepsConfig);
        if ($totalSteps > 0) {
          $percentage = (int) round((count($completedSteps) / $totalSteps) * 100);
          $progress->set('progress_percentage', min($percentage, 100));
          $progress->set('current_step', count($completedSteps));
        }
      }

      // Si se completo al 100%, marcar fecha de completacion.
      if ((int) $progress->get('progress_percentage')->value === 100) {
        $progress->set('completed_at', time());
      }

      $progress->save();

      $this->logger->info('Paso @step completado en progreso @progress. Porcentaje: @pct%', [
        '@step' => $stepId,
        '@progress' => $progressId,
        '@pct' => $progress->get('progress_percentage')->value,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error completando paso @step en progreso @progress: @error', [
        '@step' => $stepId,
        '@progress' => $progressId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Obtiene el progreso de onboarding de un usuario.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array
   *   Array con los datos de progreso, vacio si no hay progreso activo.
   */
  public function getProgress(int $userId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('user_onboarding_progress');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->sort('created', 'DESC')
        ->execute();

      if (empty($ids)) {
        return [];
      }

      $progresses = $storage->loadMultiple($ids);
      $result = [];

      /** @var \Drupal\jaraba_onboarding\Entity\UserOnboardingProgress $progress */
      foreach ($progresses as $progress) {
        $result[] = [
          'id' => (int) $progress->id(),
          'template_id' => (int) $progress->get('template_id')->target_id,
          'current_step' => (int) $progress->get('current_step')->value,
          'completed_steps' => $progress->getCompletedSteps(),
          'progress_percentage' => (int) $progress->get('progress_percentage')->value,
          'started_at' => (int) $progress->get('started_at')->value,
          'completed_at' => $progress->get('completed_at')->value ? (int) $progress->get('completed_at')->value : NULL,
          'is_complete' => $progress->isComplete(),
        ];
      }

      return $result;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo progreso para usuario @user: @error', [
        '@user' => $userId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

}
