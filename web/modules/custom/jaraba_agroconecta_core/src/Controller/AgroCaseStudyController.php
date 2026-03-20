<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Landing de caso de éxito: Cooperativa Sierra de Cazorla.
 *
 * Storytelling de producto para conversión del vertical AgroConecta.
 * Zero-region pattern (ZERO-REGION-001).
 */
class AgroCaseStudyController extends ControllerBase {

  /**
   * Página de caso de éxito: Cooperativa Sierra de Cazorla.
   *
   * @return array<string, mixed>
   *   Render array con el caso de éxito.
   */
  public function caseStudy(): array {
    $themePath = \Drupal::service('extension.list.theme')
      ->getPath('ecosistema_jaraba_theme');
    $imgBase = '/' . $themePath . '/images/agroconecta-case-study';

    return [
      '#theme' => 'agroconecta_case_study',
      '#hero_image' => $imgBase . '/jaen-olivares-hero.webp',
      '#antonio_image' => $imgBase . '/antonio-olivar.webp',
      '#before_after_image' => $imgBase . '/antes-despues-agro.webp',
      '#qr_image' => $imgBase . '/qr-trazabilidad.webp',
      '#dashboard_image' => $imgBase . '/dashboard-productor.webp',
      '#chef_image' => $imgBase . '/chef-madrid.webp',
      '#metrics' => [
        ['label' => $this->t('Registro partidas'), 'before' => '45 min', 'after' => '5 min', 'change' => '-89%'],
        ['label' => $this->t('Trazabilidad cliente'), 'before' => '1 día', 'after' => '0 min (QR)', 'change' => '-100%'],
        ['label' => $this->t('Margen por botella'), 'before' => '2,10 €', 'after' => '8,50 €', 'change' => '+305%'],
        ['label' => $this->t('Ventas directas/mes'), 'before' => '0 €', 'after' => '18.750 €', 'change' => 'Nuevo'],
        ['label' => $this->t('Clientes directos'), 'before' => '0', 'after' => '47', 'change' => '+47'],
      ],
      '#testimonial' => [
        'quote' => $this->t('Llevamos 60 años vendiendo nuestro aceite a granel a 2 euros el litro para que otros lo vendan a 15. Ahora lo vendemos nosotros directamente a 17 euros el litro. Y el cliente puede ver desde su móvil que este aceite viene de nuestros olivos.'),
        'name' => 'Antonio Morales',
        'role' => $this->t('Presidente'),
        'company' => $this->t('Cooperativa Sierra de Cazorla, Jaén'),
      ],
      '#timeline' => [
        ['day' => 1, 'title' => $this->t('Perfil de la cooperativa'), 'text' => $this->t('35 socios, 1.200 hectáreas, certificación ecológica')],
        ['day' => 3, 'title' => $this->t('Primer producto'), 'text' => $this->t('AOVE Picual Ecológico en el marketplace público')],
        ['day' => 5, 'title' => $this->t('El QR que lo cambió todo'), 'text' => $this->t('Trazabilidad inmutable: del olivo a la botella')],
        ['day' => 8, 'title' => $this->t('Primer pedido Barcelona'), 'text' => $this->t('500 botellas con envío MRW automático')],
        ['day' => 10, 'title' => $this->t('Copiloto y ayudas PAC'), 'text' => $this->t('IA informa sobre ayudas agroambientales vigentes')],
        ['day' => 14, 'title' => $this->t('Decisión tomada'), 'text' => $this->t('Contratan Professional. Margen × 4.')],
      ],
      '#pricing_url' => '/planes/agroconecta',
      '#register_url' => '/registro/agroconecta',
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_theme/agroconecta-case-study',
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
