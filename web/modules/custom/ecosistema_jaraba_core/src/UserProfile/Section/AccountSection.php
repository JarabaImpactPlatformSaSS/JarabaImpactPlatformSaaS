<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\UserProfile\Section;

use Drupal\ecosistema_jaraba_core\UserProfile\AbstractUserProfileSection;

/**
 * Seccion "Cuenta" — siempre visible, ultima posicion.
 *
 * Migrada desde ecosistema_jaraba_theme.theme lineas 3682-3711.
 * ROUTE-LANGPREFIX-001: URL de logout ahora via Url::fromRoute().
 */
class AccountSection extends AbstractUserProfileSection {

  public function getId(): string {
    return 'account';
  }

  public function getTitle(int $uid): string {
    return (string) $this->t('Cuenta');
  }

  public function getSubtitle(int $uid): string {
    return (string) $this->t('Configuracion de tu cuenta');
  }

  public function getIcon(): array {
    return ['category' => 'actions', 'name' => 'settings'];
  }

  public function getColor(): string {
    return 'neutral';
  }

  public function getWeight(): int {
    return 100;
  }

  public function isApplicable(int $uid): bool {
    return TRUE;
  }

  public function getLinks(int $uid): array {
    $links = [];

    $editLink = $this->makeLink(
      $this->t('Editar perfil'),
      'entity.user.edit_form',
      'actions', 'edit', 'primary',
      [
        'params' => ['user' => $uid],
        'description' => $this->t('Nombre, email y configuracion'),
        'slide_panel' => TRUE,
      ],
    );
    if ($editLink) {
      $links[] = $editLink;
    }

    // ROUTE-LANGPREFIX-001: URL via Url::fromRoute() en vez de hardcoded.
    $logoutUrl = $this->resolveRoute('user.logout');
    if ($logoutUrl) {
      $links[] = [
        'label' => $this->t('Cerrar sesion'),
        'url' => $logoutUrl,
        'icon_category' => 'actions',
        'icon_name' => 'log-out',
        'color' => 'danger',
        'description' => '',
        'slide_panel' => FALSE,
        'slide_panel_title' => $this->t('Cerrar sesion'),
      ];
    }

    return $links;
  }

}
