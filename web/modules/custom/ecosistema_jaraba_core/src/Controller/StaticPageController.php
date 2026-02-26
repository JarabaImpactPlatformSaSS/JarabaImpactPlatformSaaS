<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controlador para páginas estáticas del meta-sitio.
 *
 * Renderiza contenido desde Theme Settings (TAB 14: Páginas Legales).
 * Todas las páginas usan full-width landing layout sin regiones Drupal.
 *
 * THEME HOOKS USADOS (registrados en ecosistema_jaraba_core.module):
 * - info_page_contact: plantilla info-page-contact.html.twig
 * - info_page_about: plantilla info-page-about.html.twig
 * - legal_page: plantilla legal-page.html.twig
 *
 * DIRECTRICES:
 * - FRONTEND-PAGE-001: Full-width sin regiones /frontend-page-pattern
 * - i18n: Títulos con $this->t()
 * - LEGAL-CONFIG-001: Contenido editable desde admin
 *
 * @see ecosistema_jaraba_theme.theme (TAB 14: legal_pages)
 */
class StaticPageController extends ControllerBase {

  /**
   * Gets theme settings config.
   */
  protected function getThemeConfig(): array {
    $config = \Drupal::config('ecosistema_jaraba_theme.settings');
    return $config->getRawData();
  }

  /**
   * Renders the /contacto page.
   *
   * @return array
   *   Render array using info_page_contact theme hook.
   */
  public function contacto(): array {
    $config = $this->getThemeConfig();

    return [
      '#theme' => 'info_page_contact',
      '#content' => $config['contact_content'] ?? '',
      '#contact_email' => $config['contact_email'] ?? 'info@jarabaimpact.com',
      '#contact_phone' => $config['contact_phone'] ?? '+34 623 174 304',
      '#contact_address' => $config['contact_address'] ?? '',
      '#cache' => [
        'tags' => ['config:ecosistema_jaraba_theme.settings'],
        'max-age' => 3600,
      ],
    ];
  }

  /**
   * Renders the /sobre-nosotros page.
   *
   * @return array
   *   Render array using info_page_about theme hook.
   */
  public function sobreNosotros(): array {
    $config = $this->getThemeConfig();

    return [
      '#theme' => 'info_page_about',
      '#content' => $config['about_content'] ?? '',
      '#cache' => [
        'tags' => ['config:ecosistema_jaraba_theme.settings'],
        'max-age' => 3600,
      ],
    ];
  }

  /**
   * Renders the /politica-privacidad page.
   *
   * @return array
   *   Render array using legal_page theme hook.
   */
  public function privacidad(): array {
    $config = $this->getThemeConfig();

    return [
      '#theme' => 'legal_page',
      '#page_type' => 'privacy',
      '#title' => $this->t('Política de privacidad'),
      '#content' => $config['legal_privacy_content'] ?? '',
      '#last_updated' => $config['legal_privacy_updated'] ?? '',
      '#cache' => [
        'tags' => ['config:ecosistema_jaraba_theme.settings'],
        'max-age' => 3600,
      ],
    ];
  }

  /**
   * Renders the /terminos-uso page.
   *
   * @return array
   *   Render array using legal_page theme hook.
   */
  public function terminos(): array {
    $config = $this->getThemeConfig();

    return [
      '#theme' => 'legal_page',
      '#page_type' => 'terms',
      '#title' => $this->t('Términos de uso'),
      '#content' => $config['legal_terms_content'] ?? '',
      '#last_updated' => $config['legal_terms_updated'] ?? '',
      '#cache' => [
        'tags' => ['config:ecosistema_jaraba_theme.settings'],
        'max-age' => 3600,
      ],
    ];
  }

  /**
   * Renders the /politica-cookies page.
   *
   * @return array
   *   Render array using legal_page theme hook.
   */
  public function cookies(): array {
    $config = $this->getThemeConfig();

    return [
      '#theme' => 'legal_page',
      '#page_type' => 'cookies',
      '#title' => $this->t('Política de cookies'),
      '#content' => $config['legal_cookies_content'] ?? '',
      '#last_updated' => $config['legal_cookies_updated'] ?? '',
      '#cache' => [
        'tags' => ['config:ecosistema_jaraba_theme.settings'],
        'max-age' => 3600,
      ],
    ];
  }
  /**
   * Renders the /blog placeholder page.
   *
   * Sprint 5 — Optimización Continua (#19).
   * Placeholder until full blog content type is implemented.
   *
   * @return array
   *   Render array.
   */
  public function blog(): array {
    return [
      '#theme' => 'info_page_about',
      '#content' => '<p>' . $this->t('Próximamente publicaremos artículos sobre empleo, emprendimiento, tecnología e impacto social. ¡Suscríbete para no perderte nada!') . '</p>',
      '#cache' => [
        'tags' => ['config:ecosistema_jaraba_theme.settings'],
        'max-age' => 3600,
      ],
    ];
  }

}
