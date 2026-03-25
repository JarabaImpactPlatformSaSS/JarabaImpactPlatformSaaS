<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\UserProfile\Section;

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

  /**
   * {@inheritdoc}
   *
   * Usa users/users (existe). NUNCA business/users (no existe → 📌).
   */
  public function getIcon(): array {
    return ['category' => 'users', 'name' => 'users'];
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
   *
   * Usa makeLink() de AbstractUserProfileSection para generar links con
   * el schema correcto (label, icon_category, icon_name, color, description).
   * El schema anterior (title, url) no era compatible con el template.
   */
  public function getLinks(int $uid): array {
    return array_values(array_filter([
      $this->makeLink(
        $this->t('Explorar servicios'),
        'jaraba_mentoring.service_catalog',
        'users', 'users', 'innovation',
        ['description' => $this->t('Encuentra expertos y mentores')],
      ),
      $this->makeLink(
        $this->t('Mis servicios contratados'),
        'jaraba_mentoring.my_services',
        'actions', 'bookmark', 'innovation',
        ['description' => $this->t('Reservas y sesiones activas')],
      ),
    ]));
  }

}
