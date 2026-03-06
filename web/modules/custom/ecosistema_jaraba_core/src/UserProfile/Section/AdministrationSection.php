<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\UserProfile\Section;

use Drupal\ecosistema_jaraba_core\UserProfile\AbstractUserProfileSection;

/**
 * Seccion "Administracion" — solo rol administrator.
 *
 * Migrada desde ecosistema_jaraba_theme.theme lineas 3652-3680.
 */
class AdministrationSection extends AbstractUserProfileSection {

  public function getId(): string {
    return 'administration';
  }

  public function getTitle(int $uid): string {
    return (string) $this->t('Administracion');
  }

  public function getSubtitle(int $uid): string {
    return (string) $this->t('Herramientas de gestion de la plataforma');
  }

  public function getIcon(): array {
    return ['category' => 'ui', 'name' => 'shield'];
  }

  public function getColor(): string {
    return 'corporate';
  }

  public function getWeight(): int {
    return 80;
  }

  public function isApplicable(int $uid): bool {
    return \in_array('administrator', $this->currentUser->getRoles(), TRUE);
  }

  public function getLinks(int $uid): array {
    return array_values(array_filter([
      $this->makeLink(
        $this->t('Admin Center'),
        'ecosistema_jaraba_core.admin.center',
        'ui', 'settings', 'corporate',
        ['description' => $this->t('Panel de control del ecosistema')],
      ),
      $this->makeLink(
        $this->t('Gestion Tenants'),
        'ecosistema_jaraba_core.admin.center.tenants',
        'business', 'building', 'corporate',
        ['description' => $this->t('Organizaciones y suscripciones')],
      ),
      $this->makeLink(
        $this->t('Gestion Usuarios'),
        'ecosistema_jaraba_core.admin.center.users',
        'ui', 'users', 'corporate',
        ['description' => $this->t('Cuentas, roles y permisos')],
      ),
      $this->makeLink(
        $this->t('Finanzas'),
        'ecosistema_jaraba_core.admin.center.finance',
        'finance', 'coins', 'corporate',
        ['description' => $this->t('Ingresos, facturacion y metricas')],
      ),
    ]));
  }

}
