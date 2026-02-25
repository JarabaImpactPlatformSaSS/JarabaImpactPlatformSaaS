<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Landing page de conversión para Andalucía +ei.
 *
 * Renderiza una página con Zero Region Policy que ensambla
 * secciones del page builder en orden optimizado para conversión:
 * Hero > Stats > Features > Content > Testimonials > FAQ > CTA.
 */
class AndaluciaEiLandingController extends ControllerBase {

  /**
   * Renders the landing page.
   *
   * @return array
   *   Render array.
   */
  public function landing(): array {
    $solicitarUrl = Url::fromRoute('jaraba_andalucia_ei.solicitar')->toString();

    return [
      '#theme' => 'andalucia_ei_landing',
      '#solicitar_url' => $solicitarUrl,
      '#attached' => [
        'library' => [
          'jaraba_andalucia_ei/dashboard',
        ],
      ],
      '#cache' => [
        'contexts' => ['url.path'],
        'tags' => ['config:jaraba_andalucia_ei.settings'],
        'max-age' => 3600,
      ],
    ];
  }

}
