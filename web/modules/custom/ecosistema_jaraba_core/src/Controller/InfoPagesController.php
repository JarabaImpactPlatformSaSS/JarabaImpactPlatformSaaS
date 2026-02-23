<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controlador para páginas informativas estáticas.
 *
 * Renderiza páginas como Sobre Nosotros y Contacto.
 */
class InfoPagesController extends ControllerBase {

  /**
   * Página Sobre Nosotros.
   */
  public function about(): array {
    return [
      '#theme' => 'info_page',
      '#page_type' => 'about',
      '#title' => $this->t('Sobre Nosotros'),
    ];
  }

  /**
   * Página de Contacto.
   */
  public function contact(): array {
    return [
      '#theme' => 'info_page',
      '#page_type' => 'contact',
      '#title' => $this->t('Contacto'),
    ];
  }

}
