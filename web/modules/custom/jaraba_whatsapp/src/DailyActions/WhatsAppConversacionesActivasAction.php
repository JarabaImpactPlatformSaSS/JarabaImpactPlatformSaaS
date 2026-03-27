<?php

declare(strict_types=1);

namespace Drupal\jaraba_whatsapp\DailyActions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: Active WhatsApp conversations for coordinador.
 */
class WhatsAppConversacionesActivasAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'coordinador_ei.whatsapp_conversaciones';
  }

  /**
   * {@inheritdoc}
   */
  public function getDashboardId(): string {
    return 'coordinador_ei';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Conversaciones WhatsApp');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Conversaciones activas gestionadas por el agente IA');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return ['category' => 'communication', 'name' => 'chat', 'variant' => 'duotone'];
  }

  /**
   * {@inheritdoc}
   */
  public function getColor(): string {
    return 'verde-innovacion';
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'jaraba_whatsapp.panel';
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
  public function getHrefOverride(): ?string {
    return NULL;
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
    return 'large';
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 46;
  }

  /**
   * {@inheritdoc}
   */
  public function isPrimary(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getContext(int $tenantId): array {
    $count = 0;

    if ($this->entityTypeManager->hasDefinition('wa_conversation')) {
      try {
        $count = (int) $this->entityTypeManager
          ->getStorage('wa_conversation')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('tenant_id', $tenantId)
          ->condition('status', 'active')
          ->count()
          ->execute();
      }
      catch (\Throwable) {
        // Fail silently.
      }
    }

    return [
      'badge' => $count > 0 ? $count : NULL,
      'badge_type' => 'info',
      'visible' => TRUE,
    ];
  }

}
