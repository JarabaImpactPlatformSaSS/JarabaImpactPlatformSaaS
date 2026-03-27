<?php

declare(strict_types=1);

namespace Drupal\jaraba_whatsapp\DailyActions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: New WhatsApp leads pending CRM link for orientador.
 */
class WhatsAppNuevosLeadsAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'orientador_ei.whatsapp_nuevos_leads';
  }

  /**
   * {@inheritdoc}
   */
  public function getDashboardId(): string {
    return 'orientador_ei';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Nuevos leads WhatsApp');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Leads clasificados por IA pendientes de vinculacion CRM');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return ['category' => 'users', 'name' => 'lead', 'variant' => 'duotone'];
  }

  /**
   * {@inheritdoc}
   */
  public function getColor(): string {
    return 'azul-corporativo';
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
    return 'medium';
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 40;
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
          ->condition('lead_type', ['participante', 'negocio'], 'IN')
          ->notExists('linked_entity_id')
          ->count()
          ->execute();
      }
      catch (\Throwable) {
        // Fail silently.
      }
    }

    return [
      'badge' => $count > 0 ? $count : NULL,
      'badge_type' => $count > 0 ? 'warning' : 'info',
      'visible' => TRUE,
    ];
  }

}
