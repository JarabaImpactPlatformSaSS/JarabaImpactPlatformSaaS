<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controlador para páginas legales públicas.
 *
 * Renderiza: Política de Privacidad, Términos de Uso, Política de Cookies.
 * Contenido configurable desde theme settings de ecosistema_jaraba_theme.
 *
 * DIRECTRICES:
 * - i18n: $this->t() en todos los textos
 * - Templates limpios sin regiones Drupal (zero-region)
 * - Contenido configurable desde UI (theme settings)
 * - PHP 8.4 strict types
 */
class LegalPagesController extends ControllerBase {

  /**
   * Página de Política de Privacidad.
   */
  public function privacy(): array {
    return $this->buildLegalPage('privacy', $this->t('Política de Privacidad'));
  }

  /**
   * Página de Términos de Uso.
   */
  public function terms(): array {
    return $this->buildLegalPage('terms', $this->t('Términos de Uso'));
  }

  /**
   * Página de Política de Cookies.
   */
  public function cookies(): array {
    return $this->buildLegalPage('cookies', $this->t('Política de Cookies'));
  }

  /**
   * Construye el render array para una página legal.
   *
   * @param string $pageType
   *   Tipo de página: 'privacy', 'terms', 'cookies'.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|string $title
   *   Título de la página.
   *
   * @return array
   *   Render array.
   */
  protected function buildLegalPage(string $pageType, $title): array {
    $contentKey = 'legal_' . $pageType . '_content';
    $content = theme_get_setting($contentKey, 'ecosistema_jaraba_theme') ?: '';
    $lastUpdated = theme_get_setting('legal_' . $pageType . '_updated', 'ecosistema_jaraba_theme') ?: '';

    return [
      '#theme' => 'legal_page',
      '#page_type' => $pageType,
      '#title' => $title,
      '#content' => $content,
      '#last_updated' => $lastUpdated,
      '#cache' => [
        'tags' => ['config:ecosistema_jaraba_theme.settings'],
        'max-age' => 3600,
      ],
    ];
  }

}
