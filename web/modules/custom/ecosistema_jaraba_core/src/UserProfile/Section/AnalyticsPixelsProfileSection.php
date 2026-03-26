<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\UserProfile\Section;

use Drupal\ecosistema_jaraba_core\UserProfile\AbstractUserProfileSection;

/**
 * Seccion "Analytics y Pixels" en perfil de usuario.
 *
 * OPTIONAL-CROSSMODULE-001: jaraba_pixels es modulo opcional.
 * Visible si el usuario tiene permiso 'administer pixels'.
 */
class AnalyticsPixelsProfileSection extends AbstractUserProfileSection {

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'analytics_pixels';
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(int $uid): string {
    return (string) $this->t('Analytics y Pixels');
  }

  /**
   * {@inheritdoc}
   */
  public function getSubtitle(int $uid): string {
    return (string) $this->t('Configura pixels de seguimiento y analytics');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return ['category' => 'analytics', 'name' => 'chart-pie'];
  }

  /**
   * {@inheritdoc}
   */
  public function getColor(): string {
    return 'impulse';
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 55;
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(int $uid): bool {
    if (!$this->currentUser->hasPermission('administer pixels')) {
      return FALSE;
    }
    return $this->resolveRoute('jaraba_pixels.settings_page') !== NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLinks(int $uid): array {
    return array_values(array_filter([
      $this->makeLink(
        $this->t('Configurar Pixels'),
        'jaraba_pixels.settings_page',
        'analytics', 'chart-pie', 'impulse',
        ['description' => $this->t('Google Analytics, Meta Pixel, GTM y mas')],
      ),
    ]));
  }

}
