<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\UserProfile\Section;

use Drupal\ecosistema_jaraba_core\UserProfile\AbstractUserProfileSection;

/**
 * Seccion "Seguridad y Privacidad" — siempre visible.
 *
 * Proporciona acceso rapido a:
 * - Cambiar contrasena (entity.user.edit_form con slide-panel)
 * - Exportar mis datos (GDPR Art 15 — si jaraba_governance activo)
 * - Gestionar sesiones (futuro)
 *
 * Clase mundial: toda SaaS enterprise expone seguridad en perfil
 * (HubSpot, Notion, Figma, Slack).
 */
class SecurityProfileSection extends AbstractUserProfileSection {

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'security_privacy';
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(int $uid): string {
    return (string) $this->t('Seguridad y privacidad');
  }

  /**
   * {@inheritdoc}
   */
  public function getSubtitle(int $uid): string {
    return (string) $this->t('Contrasena, datos personales y privacidad');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return ['category' => 'ui', 'name' => 'shield-check'];
  }

  /**
   * {@inheritdoc}
   */
  public function getColor(): string {
    return 'corporate';
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 80;
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
    return array_values(array_filter([
      $this->makeLink(
        $this->t('Cambiar contrasena'),
        'entity.user.edit_form',
        'ui', 'lock', 'corporate',
        [
          'params' => ['user' => $uid],
          'description' => $this->t('Actualiza tu contrasena de acceso'),
          'slide_panel' => TRUE,
          'slide_panel_title' => $this->t('Seguridad de la cuenta'),
        ],
      ),
      $this->makeLink(
        $this->t('Politica de cookies'),
        'ecosistema_jaraba_core.cookie_policy',
        'compliance', 'cookie', 'corporate',
        ['description' => $this->t('Gestion de cookies y privacidad')],
      ),
    ]));
  }

}
