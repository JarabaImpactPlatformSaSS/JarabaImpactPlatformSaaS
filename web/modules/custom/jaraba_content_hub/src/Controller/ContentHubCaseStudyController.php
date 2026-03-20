<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Landing de caso de éxito: Bodega Montilla, Córdoba.
 *
 * Storytelling de producto para conversión del vertical Content Hub.
 * Zero-region pattern (ZERO-REGION-001).
 */
class ContentHubCaseStudyController extends ControllerBase {

  /**
   * Página de caso de éxito: Bodega Montilla.
   *
   * @return array<string, mixed>
   *   Render array con el caso de éxito.
   */
  public function caseStudy(): array {
    $themePath = \Drupal::service('extension.list.theme')
      ->getPath('ecosistema_jaraba_theme');
    $imgBase = '/' . $themePath . '/images/contenthub-case-study';

    return [
      '#theme' => 'contenthub_case_study',
      '#hero_image' => $imgBase . '/bodega-montilla-hero.webp',
      '#luis_image' => $imgBase . '/luis-moreno-bodega.webp',
      '#before_after_image' => $imgBase . '/antes-despues-contenido.webp',
      '#editor_image' => $imgBase . '/editor-ia-seo.webp',
      '#analytics_image' => $imgBase . '/analytics-seo-dashboard.webp',
      '#multichannel_image' => $imgBase . '/contenido-multicanal.webp',
      '#metrics' => [
        ['label' => $this->t('Tráfico orgánico mensual'), 'before' => '0', 'after' => '12.000 visitas', 'change' => 'Nuevo'],
        ['label' => $this->t('Artículos publicados'), 'before' => '0', 'after' => '45', 'change' => '+45'],
        ['label' => $this->t('Keywords en top 3 Google'), 'before' => '0', 'after' => '8', 'change' => '+8'],
        ['label' => $this->t('Coste adquisición cliente'), 'before' => '28 €/lead (ads)', 'after' => '3 €/lead (SEO)', 'change' => '-89%'],
        ['label' => $this->t('Suscriptores newsletter'), 'before' => '0', 'after' => '2.400', 'change' => 'Nuevo'],
      ],
      '#testimonial' => [
        'quote' => $this->t('Gastábamos todo el presupuesto de marketing en Google Ads sin construir nada a largo plazo. Con el Content Hub, la IA me genera borradores optimizados para SEO y yo solo tengo que añadir nuestro toque personal. En 6 meses pasamos de 0 a 12.000 visitas orgánicas al mes. Ahora el blog nos trae más clientes que los anuncios, y el coste por lead ha bajado un 89%.'),
        'name' => 'Luis Moreno',
        'role' => $this->t('Director de marketing'),
        'company' => $this->t('Bodega Montilla, Córdoba'),
      ],
      '#timeline' => [
        ['day' => 'M1', 'title' => $this->t('Estrategia y primeros artículos'), 'text' => $this->t('Calendario editorial IA: 12 keywords estratégicas. 8 artículos publicados la primera semana')],
        ['day' => 'M2', 'title' => $this->t('SEO en marcha'), 'text' => $this->t('2 keywords en top 10. 1.200 visitas orgánicas. Newsletter con 340 suscriptores')],
        ['day' => 'M3-4', 'title' => $this->t('Escalado multicanal'), 'text' => $this->t('Distribución automática: blog → LinkedIn → Instagram → newsletter. 25 artículos acumulados')],
        ['day' => 'M5', 'title' => $this->t('Resultados SEO'), 'text' => $this->t('5 keywords en top 3. 8.000 visitas/mes. Primeros leads orgánicos superan a ads')],
        ['day' => 'M6', 'title' => $this->t('ROI demostrado'), 'text' => $this->t('12.000 visitas/mes, 8 keywords top 3, coste lead a 3 euros. Presupuesto ads reducido 60%')],
      ],
      '#pricing' => [
        'free_features' => $this->t('5 artículos, editor básico, 1 categoría'),
        'starter_price' => '19',
        'starter_features' => $this->t('Artículos ilimitados, editor IA, SEO score, calendario editorial'),
        'professional_price' => '49',
        'professional_features' => $this->t('Multicanal automático, analytics avanzado, A/B headlines, copilot proactivo'),
      ],
      '#pricing_url' => '/planes/content-hub',
      '#register_url' => '/registro/content-hub',
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_theme/contenthub-case-study',
          'ecosistema_jaraba_theme/scroll-animations',
        ],
      ],
      '#cache' => [
        'max-age' => 86400,
        'tags' => ['case_study_list'],
      ],
    ];
  }

}
