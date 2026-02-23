<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controlador para páginas informativas públicas.
 *
 * Renderiza: Sobre Nosotros, Contacto.
 * Contenido configurable desde theme settings de ecosistema_jaraba_theme.
 *
 * DIRECTRICES:
 * - i18n: $this->t() en todos los textos
 * - Templates limpios sin regiones Drupal (zero-region)
 * - Contenido configurable desde UI (theme settings)
 * - PHP 8.4 strict types
 */
class InfoPagesController extends ControllerBase {

  /**
   * Página Sobre Nosotros.
   */
  public function about(): array {
    $content = theme_get_setting('about_content', 'ecosistema_jaraba_theme') ?: '';

    return [
      '#theme' => 'info_page_about',
      '#content' => $content,
      '#cache' => [
        'tags' => ['config:ecosistema_jaraba_theme.settings'],
        'max-age' => 3600,
      ],
    ];
  }

  /**
   * Página de Contacto.
   */
  public function contact(): array {
    $content = theme_get_setting('contact_content', 'ecosistema_jaraba_theme') ?: '';
    $email = theme_get_setting('contact_email', 'ecosistema_jaraba_theme') ?: '';
    $phone = theme_get_setting('contact_phone', 'ecosistema_jaraba_theme') ?: '';
    $address = theme_get_setting('contact_address', 'ecosistema_jaraba_theme') ?: '';

    return [
      '#theme' => 'info_page_contact',
      '#content' => $content,
      '#contact_email' => $email,
      '#contact_phone' => $phone,
      '#contact_address' => $address,
      '#cache' => [
        'tags' => ['config:ecosistema_jaraba_theme.settings'],
        'max-age' => 3600,
      ],
    ];
  }

}
