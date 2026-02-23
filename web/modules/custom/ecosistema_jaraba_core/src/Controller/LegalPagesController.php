<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controlador para páginas legales estáticas.
 *
 * Renderiza las páginas de Política de Privacidad, Términos de Uso
 * y Política de Cookies usando templates del tema.
 */
class LegalPagesController extends ControllerBase {

  /**
   * Página de Política de Privacidad.
   */
  public function privacy(): array {
    return [
      '#theme' => 'legal_page',
      '#page_type' => 'privacy',
      '#title' => $this->t('Política de Privacidad'),
    ];
  }

  /**
   * Página de Términos de Uso.
   */
  public function terms(): array {
    return [
      '#theme' => 'legal_page',
      '#page_type' => 'terms',
      '#title' => $this->t('Términos de Uso'),
    ];
  }

  /**
   * Página de Política de Cookies.
   */
  public function cookies(): array {
    return [
      '#theme' => 'legal_page',
      '#page_type' => 'cookies',
      '#title' => $this->t('Política de Cookies'),
    ];
  }

}
