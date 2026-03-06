<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Trait;

use Drupal\Core\Render\Markup;
use Drupal\Core\Url;

/**
 * Trait for tenant settings sub-page forms.
 *
 * Provides a premium hero header with particles canvas, consistent
 * with the /my-settings hub page, and full-width form wrapper.
 *
 * Usage in buildForm():
 *   $this->attachTenantFormHero($form, 'globe', 'Dominio', 'Configura tu dominio personalizado.');
 *
 * ROUTE-LANGPREFIX-001: Back URL via Url::fromRoute().
 * CSS-VAR-ALL-COLORS-001: Colors via var(--ej-*) tokens in SCSS.
 * ICON-CONVENTION-001: Icons from ecosistema_jaraba_core/images/icons/ui/.
 */
trait TenantFormHeroPremiumTrait {

  /**
   * Attaches premium hero header and full-width wrapper to a tenant form.
   *
   * @param array &$form
   *   The form render array.
   * @param string $iconName
   *   Icon name from ui category (e.g., 'globe', 'palette', 'code', 'webhook').
   * @param string $title
   *   Already-translated title string.
   * @param string $subtitle
   *   Already-translated subtitle/description string.
   */
  protected function attachTenantFormHero(array &$form, string $iconName, string $title, string $subtitle): void {
    $backUrl = Url::fromRoute('ecosistema_jaraba_core.tenant_self_service.settings')->toString();
    $iconsPath = '/' . \Drupal::service('extension.list.module')->getPath('ecosistema_jaraba_core') . '/images/icons/ui';

    // Replace narrow tenant-form wrapper with full-width tenant-settings wrapper.
    $form['#prefix'] = '<div class="tenant-settings tenant-form-page">';
    $form['#suffix'] = '</div>';

    $form['hero_header'] = [
      '#markup' => Markup::create(
        '<header class="tenant-settings__hero">'
        . '<canvas class="tenant-settings__particles" data-dashboard-particles aria-hidden="true"></canvas>'
        . '<div class="tenant-settings__hero-content">'
        .   '<div class="tenant-settings__hero-icon">'
        .     '<img src="' . $iconsPath . '/' . $iconName . '-duotone.svg" alt="" width="40" height="40" loading="lazy" aria-hidden="true" style="filter:brightness(0) invert(1)" />'
        .   '</div>'
        .   '<div class="tenant-settings__hero-text">'
        .     '<h1 class="tenant-settings__title">' . $title . '</h1>'
        .     '<p class="tenant-settings__subtitle">' . $subtitle . '</p>'
        .   '</div>'
        . '</div>'
        . '<div class="tenant-settings__hero-actions">'
        .   '<a href="' . $backUrl . '" class="tenant-settings__back-link"'
        .   ' aria-label="' . $this->t('Volver a Configuracion') . '">'
        .     '<img src="' . $iconsPath . '/arrow-left-duotone.svg" alt="" width="18" height="18" loading="lazy" aria-hidden="true" style="filter:brightness(0) invert(1)" />'
        .     ' ' . $this->t('Configuracion')
        .   '</a>'
        . '</div>'
        . '<div class="tenant-settings__hero-glow" aria-hidden="true"></div>'
        . '</header>'
      ),
      '#weight' => -100,
    ];
  }

}
