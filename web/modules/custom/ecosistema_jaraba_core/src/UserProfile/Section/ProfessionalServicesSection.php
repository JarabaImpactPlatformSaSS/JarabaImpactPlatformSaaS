<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\UserProfile\Section;

use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\UserProfile\AbstractUserProfileSection;

/**
 * Profile hub section: Professional Services (Doc 181 — Pilar 3).
 *
 * Shows active bookings (if any) or CTA to explore service catalog.
 * Links to /servicios-profesionales and /mis-servicios.
 *
 * Tagged service: ecosistema_jaraba_core.user_profile_section
 */
class ProfessionalServicesSection extends AbstractUserProfileSection {

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'professional_services';
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(int $uid): string {
    return (string) $this->t('Servicios Profesionales');
  }

  /**
   * {@inheritdoc}
   */
  public function getSubtitle(int $uid): string {
    return (string) $this->t('Mentoría, workshops y programas con expertos');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return ['category' => 'business', 'name' => 'users'];
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
    return 20;
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(int $uid): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getLinks(int $uid): array {
    $links = [];

    try {
      $links[] = [
        'title' => $this->t('Explorar servicios'),
        'url' => Url::fromRoute('jaraba_mentoring.service_catalog')->toString(),
      ];
    }
    catch (\Throwable) {
      // Route may not exist if module disabled.
    }

    try {
      $links[] = [
        'title' => $this->t('Mis servicios contratados'),
        'url' => Url::fromRoute('jaraba_mentoring.my_services')->toString(),
      ];
    }
    catch (\Throwable) {
      // Route may not exist.
    }

    return $links;
  }

}
