<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Landing de caso de éxito: Boutique La Mariposa, Sevilla.
 *
 * Storytelling de producto para conversión del vertical ComercioConecta.
 * Zero-region pattern (ZERO-REGION-001).
 */
class ComercioCaseStudyController extends ControllerBase {

  /**
   * Página de caso de éxito: Boutique La Mariposa.
   *
   * @return array<string, mixed>
   *   Render array con el caso de éxito.
   */
  public function caseStudy(): array {
    $themePath = \Drupal::service('extension.list.theme')
      ->getPath('ecosistema_jaraba_theme');
    $imgBase = '/' . $themePath . '/images/comercioconecta-case-study';

    return [
      '#theme' => 'comercioconecta_case_study',
      '#hero_image' => $imgBase . '/sevilla-boutique-hero.webp',
      '#carmen_image' => $imgBase . '/carmen-boutique.webp',
      '#before_after_image' => $imgBase . '/antes-despues-comercio.webp',
      '#qr_image' => $imgBase . '/qr-escaparate.webp',
      '#dashboard_image' => $imgBase . '/dashboard-comerciante.webp',
      '#influencer_image' => $imgBase . '/influencer-sevilla.webp',
      '#metrics' => [
        ['label' => $this->t('Facturación mensual'), 'before' => '4.200 €', 'after' => '6.180 €', 'change' => '+47%'],
        ['label' => $this->t('Clientas activas'), 'before' => '68', 'after' => '157', 'change' => '+131%'],
        ['label' => $this->t('Ventas fuera de horario'), 'before' => '0 €', 'after' => '1.200 €/mes', 'change' => 'Nuevo'],
        ['label' => $this->t('Ticket medio'), 'before' => '38 €', 'after' => '52 €', 'change' => '+37%'],
        ['label' => $this->t('Horas admin/semana'), 'before' => '12 h', 'after' => '4 h', 'change' => '-67%'],
      ],
      '#testimonial' => [
        'quote' => $this->t('Yo pensaba que digitalizar mi tienda significaba gastarme 3.000 euros en una web que nadie iba a visitar. ComercioConecta me demostró que lo más potente es lo más simple: un QR en el cristal que vende mientras yo duermo. El primer mes gané 2.500 euros extra sin trabajar ni una hora más.'),
        'name' => 'Carmen Ruiz',
        'role' => $this->t('Propietaria'),
        'company' => $this->t('Boutique La Mariposa, Sevilla'),
      ],
      '#timeline' => [
        ['day' => 1, 'title' => $this->t('Perfil y primeros productos'), 'text' => $this->t('10 productos subidos con IA en 25 minutos')],
        ['day' => 3, 'title' => $this->t('QR en el escaparate'), 'text' => $this->t('Primera venta online: turista francés, pañuelo de seda, 45 €')],
        ['day' => 5, 'title' => $this->t('Click & Collect activo'), 'text' => $this->t('Pilar reserva un vestido desde el autobús. Ticket medio +38%')],
        ['day' => 7, 'title' => $this->t('Primera oferta flash'), 'text' => $this->t('12 vestidos de temporada anterior: 9 vendidos en 36 horas')],
        ['day' => 9, 'title' => $this->t('Copiloto de Instagram'), 'text' => $this->t('7 posts programados en 15 min. De 340 a 580 seguidores')],
        ['day' => 14, 'title' => $this->t('Decisión tomada'), 'text' => $this->t('Contrata Starter (29 €/mes). Retorno 86:1 el primer mes')],
      ],
      '#pricing' => [
        'starter_price' => '29',
        'professional_price' => '79',
        'starter_features' => $this->t('Productos ilimitados, 10 QR, click & collect'),
        'professional_features' => $this->t('Analytics, envíos integrados, carritos abandonados'),
        'free_features' => $this->t('10 productos, 1 QR, 1 oferta flash'),
      ],
      '#pricing_url' => '/planes/comercioconecta',
      '#register_url' => '/registro/comercioconecta',
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_theme/comercioconecta-case-study',
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
