<?php

declare(strict_types=1);

namespace Drupal\jaraba_whatsapp\SetupWizard;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Setup Wizard step: Configure WhatsApp integration.
 *
 * Checks:
 * - Webhook env var configured (WHATSAPP_PHONE_NUMBER_ID).
 * - At least 1 approved WaTemplate.
 * - Agent enabled in config.
 *
 * Auto-complete when all 3 conditions are met.
 */
class CoordinadorConfigWhatsAppStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'coordinador_ei.config_whatsapp';
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardId(): string {
    return 'coordinador_ei';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Configurar WhatsApp');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Verificar webhook, aprobar templates y activar el agente IA');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 50;
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return [
      'category' => 'communication',
      'name' => 'chat',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'jaraba_whatsapp.settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function useSlidePanel(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSlidePanelSize(): string {
    return 'medium';
  }

  /**
   * {@inheritdoc}
   */
  public function isComplete(int $tenantId): bool {
    // Check 1: Env var configured.
    $phoneNumberId = getenv('WHATSAPP_PHONE_NUMBER_ID');
    if ($phoneNumberId === false || $phoneNumberId === '') {
      return FALSE;
    }

    // Check 2: At least 1 approved template.
    if ($this->entityTypeManager->hasDefinition('wa_template')) {
      try {
        $approvedCount = (int) $this->entityTypeManager
          ->getStorage('wa_template')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('status_meta', 'approved')
          ->condition('status', TRUE)
          ->count()
          ->execute();

        if ($approvedCount < 1) {
          return FALSE;
        }
      }
      catch (\Throwable) {
        return FALSE;
      }
    }

    // Check 3: Agent enabled.
    $config = $this->configFactory->get('jaraba_whatsapp.settings');
    return (bool) $config->get('agent_enabled');
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    $isComplete = $this->isComplete($tenantId);
    return [
      'count' => $isComplete ? 3 : 0,
      'label' => $isComplete
        ? $this->t('WhatsApp configurado')
        : $this->t('Pendiente de configuracion'),
      'progress' => $isComplete ? 100 : 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return TRUE;
  }

}
