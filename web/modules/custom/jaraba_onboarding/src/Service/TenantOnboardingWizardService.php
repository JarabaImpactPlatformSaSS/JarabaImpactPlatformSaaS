<?php

declare(strict_types=1);

namespace Drupal\jaraba_onboarding\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_onboarding\Entity\TenantOnboardingProgress;
use Psr\Log\LoggerInterface;

/**
 * Servicio del wizard de onboarding de tenant.
 *
 * Gestiona la creacion, avance, omision y completacion
 * del wizard de 7 pasos para configuracion inicial del tenant.
 *
 * Fase 5 — Doc 179.
 */
class TenantOnboardingWizardService {

  /**
   * Pasos que requieren datos fiscales/pagos (verticales commerce).
   */
  protected const COMMERCE_VERTICALS = [
    'agroconecta',
    'comercioconecta',
    'serviciosconecta',
  ];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Obtiene o crea el progreso del wizard para el usuario actual.
   */
  public function getOrCreateProgress(string $vertical, ?int $tenantId = NULL): ?TenantOnboardingProgress {
    try {
      $userId = (int) $this->currentUser->id();
      $storage = $this->entityTypeManager->getStorage('tenant_onboarding_progress');

      // Buscar progreso existente del usuario.
      $existing = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->sort('started_at', 'DESC')
        ->range(0, 1)
        ->execute();

      if (!empty($existing)) {
        return $storage->load(reset($existing));
      }

      // Crear nuevo progreso.
      $progress = $storage->create([
        'user_id' => $userId,
        'tenant_id' => $tenantId,
        'vertical' => $vertical,
        'current_step' => TenantOnboardingProgress::STEP_WELCOME,
        'completed_steps' => '[]',
        'step_data' => '{}',
        'skipped_steps' => '[]',
        'time_spent_seconds' => 0,
      ]);
      $progress->save();

      $this->logger->info('Wizard onboarding iniciado para usuario @user, vertical @vertical.', [
        '@user' => $userId,
        '@vertical' => $vertical,
      ]);

      return $progress;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo/creando progreso wizard: @error', [
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Obtiene el progreso existente del usuario actual.
   */
  public function getCurrentProgress(): ?TenantOnboardingProgress {
    try {
      $userId = (int) $this->currentUser->id();
      $storage = $this->entityTypeManager->getStorage('tenant_onboarding_progress');

      $existing = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->condition('completed_at', NULL, 'IS NULL')
        ->sort('started_at', 'DESC')
        ->range(0, 1)
        ->execute();

      if (empty($existing)) {
        return NULL;
      }

      return $storage->load(reset($existing));
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo progreso wizard: @error', [
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Avanza al siguiente paso, guardando datos del paso actual.
   */
  public function advanceStep(TenantOnboardingProgress $progress, array $stepData = []): bool {
    try {
      $currentStep = (int) $progress->get('current_step')->value;

      // Guardar datos del paso actual.
      if (!empty($stepData)) {
        $progress->setStepData($currentStep, $stepData);
      }

      // Marcar paso como completado.
      $completed = $progress->getCompletedSteps();
      if (!in_array($currentStep, $completed, TRUE)) {
        $completed[] = $currentStep;
        $progress->setCompletedSteps($completed);
      }

      // Determinar siguiente paso.
      $nextStep = $this->getNextStep($progress, $currentStep);
      $progress->set('current_step', $nextStep);

      // Si es el ultimo paso completado, marcar como finalizado.
      if ($currentStep === TenantOnboardingProgress::STEP_LAUNCH) {
        $progress->set('completed_at', time());
      }

      $progress->save();

      $this->logger->info('Wizard paso @step completado, avanza a @next para tenant @tenant.', [
        '@step' => $currentStep,
        '@next' => $nextStep,
        '@tenant' => $progress->get('tenant_id')->target_id ?? 'N/A',
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error avanzando paso wizard: @error', [
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Omite el paso actual (solo pasos opcionales).
   */
  public function skipStep(TenantOnboardingProgress $progress): bool {
    try {
      $currentStep = (int) $progress->get('current_step')->value;

      if (!$this->isStepSkippable($progress, $currentStep)) {
        return FALSE;
      }

      $progress->addSkippedStep($currentStep);

      $nextStep = $this->getNextStep($progress, $currentStep);
      $progress->set('current_step', $nextStep);
      $progress->save();

      $this->logger->info('Wizard paso @step omitido para tenant @tenant.', [
        '@step' => $currentStep,
        '@tenant' => $progress->get('tenant_id')->target_id ?? 'N/A',
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error omitiendo paso wizard: @error', [
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Determina el siguiente paso segun vertical y omisiones.
   */
  protected function getNextStep(TenantOnboardingProgress $progress, int $currentStep): int {
    $vertical = $progress->get('vertical')->value ?? '';
    $isCommerce = in_array($vertical, self::COMMERCE_VERTICALS, TRUE);
    $nextStep = $currentStep + 1;

    // Si no es vertical commerce, saltar pasos 3 (fiscal) y 4 (pagos).
    if (!$isCommerce) {
      if ($nextStep === TenantOnboardingProgress::STEP_FISCAL) {
        $nextStep = TenantOnboardingProgress::STEP_TEAM;
      }
      elseif ($nextStep === TenantOnboardingProgress::STEP_PAYMENTS) {
        $nextStep = TenantOnboardingProgress::STEP_TEAM;
      }
    }

    return min($nextStep, TenantOnboardingProgress::TOTAL_STEPS);
  }

  /**
   * Determina si un paso es omitible.
   */
  public function isStepSkippable(TenantOnboardingProgress $progress, int $step): bool {
    $vertical = $progress->get('vertical')->value ?? '';
    $isCommerce = in_array($vertical, self::COMMERCE_VERTICALS, TRUE);

    return match ($step) {
      // Pasos 3-4 solo aplican a commerce; si no es commerce, se auto-saltan.
      TenantOnboardingProgress::STEP_FISCAL => $isCommerce,
      TenantOnboardingProgress::STEP_PAYMENTS => $isCommerce,
      // Paso 5 siempre es saltable.
      TenantOnboardingProgress::STEP_TEAM => TRUE,
      default => FALSE,
    };
  }

  /**
   * Obtiene la configuracion de pasos para un vertical.
   */
  public function getStepsConfig(string $vertical): array {
    $isCommerce = in_array($vertical, self::COMMERCE_VERTICALS, TRUE);

    $steps = [
      TenantOnboardingProgress::STEP_WELCOME => [
        'key' => 'welcome',
        'label' => t('Bienvenida'),
        'icon' => 'hand-wave',
        'duration' => '30s',
        'required' => TRUE,
        'active' => TRUE,
      ],
      TenantOnboardingProgress::STEP_IDENTITY => [
        'key' => 'identity',
        'label' => t('Identidad'),
        'icon' => 'palette',
        'duration' => '2 min',
        'required' => TRUE,
        'active' => TRUE,
      ],
      TenantOnboardingProgress::STEP_FISCAL => [
        'key' => 'fiscal',
        'label' => t('Datos Fiscales'),
        'icon' => 'file-text',
        'duration' => '2 min',
        'required' => $isCommerce,
        'active' => $isCommerce,
      ],
      TenantOnboardingProgress::STEP_PAYMENTS => [
        'key' => 'payments',
        'label' => t('Pagos'),
        'icon' => 'credit-card',
        'duration' => '3 min',
        'required' => $isCommerce,
        'active' => $isCommerce,
      ],
      TenantOnboardingProgress::STEP_TEAM => [
        'key' => 'team',
        'label' => t('Equipo'),
        'icon' => 'users',
        'duration' => '1 min',
        'required' => FALSE,
        'active' => TRUE,
      ],
      TenantOnboardingProgress::STEP_CONTENT => [
        'key' => 'content',
        'label' => $this->getContentStepLabel($vertical),
        'icon' => $this->getContentStepIcon($vertical),
        'duration' => '3 min',
        'required' => TRUE,
        'active' => TRUE,
      ],
      TenantOnboardingProgress::STEP_LAUNCH => [
        'key' => 'launch',
        'label' => t('Lanzamiento'),
        'icon' => 'rocket',
        'duration' => '30s',
        'required' => TRUE,
        'active' => TRUE,
      ],
    ];

    return $steps;
  }

  /**
   * Obtiene la etiqueta del paso de contenido segun vertical.
   */
  protected function getContentStepLabel(string $vertical): string {
    return match ($vertical) {
      'agroconecta' => (string) t('Primer Producto'),
      'comercioconecta' => (string) t('Primer Producto'),
      'serviciosconecta' => (string) t('Primer Servicio'),
      'empleabilidad' => (string) t('Tu Perfil'),
      'emprendimiento' => (string) t('Plan de Negocio'),
      default => (string) t('Contenido Inicial'),
    };
  }

  /**
   * Obtiene el icono del paso de contenido segun vertical.
   */
  protected function getContentStepIcon(string $vertical): string {
    return match ($vertical) {
      'agroconecta', 'comercioconecta' => 'package',
      'serviciosconecta' => 'briefcase',
      'empleabilidad' => 'user-circle',
      'emprendimiento' => 'lightbulb',
      default => 'file-plus',
    };
  }

  /**
   * Valida NIF/CIF espanol (algoritmo oficial).
   */
  public function validateNif(string $nif): bool {
    $nif = strtoupper(trim($nif));
    if (strlen($nif) !== 9) {
      return FALSE;
    }

    // DNI: 8 digitos + letra.
    if (preg_match('/^[0-9]{8}[A-Z]$/', $nif)) {
      $letters = 'TRWAGMYFPDXBNJZSQVHLCKE';
      $number = (int) substr($nif, 0, 8);
      return $nif[8] === $letters[$number % 23];
    }

    // NIE: X/Y/Z + 7 digitos + letra.
    if (preg_match('/^[XYZ][0-9]{7}[A-Z]$/', $nif)) {
      $replaced = str_replace(['X', 'Y', 'Z'], ['0', '1', '2'], $nif);
      $letters = 'TRWAGMYFPDXBNJZSQVHLCKE';
      $number = (int) substr($replaced, 0, 8);
      return $nif[8] === $letters[$number % 23];
    }

    // CIF: Letra + 7 digitos + control.
    if (preg_match('/^[ABCDEFGHJKLMNPQRSUVW][0-9]{7}[A-J0-9]$/', $nif)) {
      $sum = 0;
      for ($i = 1; $i <= 7; $i++) {
        $digit = (int) $nif[$i];
        if ($i % 2 !== 0) {
          $digit *= 2;
          if ($digit > 9) {
            $digit = (int) ($digit / 10) + ($digit % 10);
          }
        }
        $sum += $digit;
      }
      $control = (10 - ($sum % 10)) % 10;
      $controlLetters = 'JABCDEFGHI';

      // Algunos tipos usan letra, otros digito.
      $letterTypes = ['K', 'P', 'Q', 'S'];
      if (in_array($nif[0], $letterTypes, TRUE)) {
        return $nif[8] === $controlLetters[$control];
      }
      $digitTypes = ['A', 'B', 'E', 'H'];
      if (in_array($nif[0], $digitTypes, TRUE)) {
        return $nif[8] === (string) $control;
      }

      // Otros: acepta ambos.
      return $nif[8] === (string) $control || $nif[8] === $controlLetters[$control];
    }

    return FALSE;
  }

  /**
   * Completa el wizard y marca el timestamp final.
   */
  public function completeWizard(TenantOnboardingProgress $progress, int $totalTimeSeconds = 0): bool {
    try {
      $progress->set('completed_at', time());
      if ($totalTimeSeconds > 0) {
        $progress->set('time_spent_seconds', $totalTimeSeconds);
      }
      $progress->save();

      $this->logger->info('Wizard onboarding completado para tenant @tenant en @time segundos.', [
        '@tenant' => $progress->get('tenant_id')->target_id ?? 'N/A',
        '@time' => $totalTimeSeconds,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error completando wizard: @error', [
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

}
