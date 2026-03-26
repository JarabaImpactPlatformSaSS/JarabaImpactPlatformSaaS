<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\UserProfile\Section;

use Drupal\ecosistema_jaraba_core\UserProfile\AbstractUserProfileSection;

/**
 * Seccion "Notificaciones" en perfil de usuario.
 *
 * Siempre visible para usuarios autenticados.
 * OPTIONAL-CROSSMODULE-001: jaraba_notifications es modulo opcional;
 * fallback a pagina de usuario si no esta disponible.
 */
class NotificationPreferencesProfileSection extends AbstractUserProfileSection {

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'notification_prefs';
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(int $uid): string {
    return (string) $this->t('Notificaciones');
  }

  /**
   * {@inheritdoc}
   */
  public function getSubtitle(int $uid): string {
    return (string) $this->t('Gestiona tus preferencias de notificacion');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return ['category' => 'ui', 'name' => 'bell'];
  }

  /**
   * {@inheritdoc}
   */
  public function getColor(): string {
    return 'neutral';
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 85;
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(int $uid): bool {
    return $this->currentUser->isAuthenticated();
  }

  /**
   * {@inheritdoc}
   */
  public function getLinks(int $uid): array {
    $notifRoute = $this->resolveRoute('jaraba_notifications.preferences');
    $route = $notifRoute !== NULL
      ? 'jaraba_notifications.preferences'
      : 'entity.user.canonical';
    $params = $notifRoute !== NULL ? [] : ['params' => ['user' => $uid]];

    return array_values(array_filter([
      $this->makeLink(
        $this->t('Preferencias de notificacion'),
        $route,
        'ui', 'bell', 'neutral',
        array_merge(
          ['description' => $this->t('Email, push y alertas en plataforma')],
          $params,
        ),
      ),
    ]));
  }

}
