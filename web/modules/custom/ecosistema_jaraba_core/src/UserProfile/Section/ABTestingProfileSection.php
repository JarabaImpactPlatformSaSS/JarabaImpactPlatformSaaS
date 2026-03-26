<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\UserProfile\Section;

use Drupal\ecosistema_jaraba_core\UserProfile\AbstractUserProfileSection;

/**
 * Seccion "Experimentos A/B" en perfil de usuario.
 *
 * OPTIONAL-CROSSMODULE-001: jaraba_ab_testing es modulo opcional.
 * Visible si el usuario tiene permiso 'view experiment dashboard'.
 */
class ABTestingProfileSection extends AbstractUserProfileSection {

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'ab_testing';
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(int $uid): string {
    return (string) $this->t('Experimentos A/B');
  }

  /**
   * {@inheritdoc}
   */
  public function getSubtitle(int $uid): string {
    return (string) $this->t('Gestiona tests y optimizacion de conversion');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return ['category' => 'analytics', 'name' => 'flask'];
  }

  /**
   * {@inheritdoc}
   */
  public function getColor(): string {
    return 'innovation';
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
  public function isApplicable(int $uid): bool {
    if (!$this->currentUser->hasPermission('view experiment dashboard')) {
      return FALSE;
    }
    return $this->resolveRoute('jaraba_ab_testing.dashboard') !== NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLinks(int $uid): array {
    return array_values(array_filter([
      $this->makeLink(
        $this->t('Dashboard A/B'),
        'jaraba_ab_testing.dashboard',
        'analytics', 'flask', 'innovation',
        ['description' => $this->t('Resultados y metricas de experimentos')],
      ),
    ]));
  }

}
