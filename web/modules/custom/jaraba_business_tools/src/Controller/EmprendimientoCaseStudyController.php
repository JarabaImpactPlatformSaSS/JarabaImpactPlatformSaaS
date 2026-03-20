<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Landing de caso de éxito: Carlos Etxebarria, Bilbao.
 *
 * Storytelling de producto para conversión del vertical Emprendimiento.
 * Zero-region pattern (ZERO-REGION-001).
 */
class EmprendimientoCaseStudyController extends ControllerBase {

  /**
   * Página de caso de éxito: Carlos Etxebarria.
   *
   * @return array<string, mixed>
   *   Render array con el caso de éxito.
   */
  public function caseStudy(): array {
    $themePath = \Drupal::service('extension.list.theme')
      ->getPath('ecosistema_jaraba_theme');
    $imgBase = '/' . $themePath . '/images/emprendimiento-case-study';

    return [
      '#theme' => 'emprendimiento_case_study',
      '#hero_image' => $imgBase . '/bilbao-hero.webp',
      '#carlos_image' => $imgBase . '/carlos-coworking.webp',
      '#before_after_image' => $imgBase . '/antes-despues-emprendimiento.webp',
      '#canvas_image' => $imgBase . '/canvas-ia-tablet.webp',
      '#dashboard_image' => $imgBase . '/health-score-emprendedor.webp',
      '#enisa_image' => $imgBase . '/carlos-enisa-aprobado.webp',
      '#metrics' => [
        ['label' => $this->t('Tiempo idea a MVP'), 'before' => '2 años (sin avanzar)', 'after' => '60 días', 'change' => '-97%'],
        ['label' => $this->t('Hipótesis validadas'), 'before' => '0', 'after' => '3 de 5', 'change' => '+3'],
        ['label' => $this->t('Clientes piloto'), 'before' => '0', 'after' => '5 activos', 'change' => 'Nuevo'],
        ['label' => $this->t('Financiación conseguida'), 'before' => '0 €', 'after' => '47.000 € (ENISA)', 'change' => 'Nuevo'],
        ['label' => $this->t('Health Score emprendedor'), 'before' => 'N/A', 'after' => '82/100', 'change' => 'Nuevo'],
      ],
      '#testimonial' => [
        'quote' => $this->t('La calculadora de madurez me abrió los ojos. Llevaba 2 años dándole vueltas a mi idea sin hablar con un solo cliente. El Canvas con IA me ahorró meses de prueba y error. Y cuando el copilot me avisó de la financiación ENISA, no me lo podía creer. En 60 días pasé de tener una idea en una servilleta a tener un MVP con 5 pilotos y 47.000 euros aprobados.'),
        'name' => 'Carlos Etxebarria',
        'role' => $this->t('Ingeniero industrial y emprendedor'),
        'company' => $this->t('Bilbao'),
      ],
      '#timeline' => [
        ['day' => 'S1', 'title' => $this->t('Calculadora + Canvas IA'), 'text' => $this->t('Madurez: 78% conceptual, 0% validación. Canvas generado en 20 min')],
        ['day' => 'S2', 'title' => $this->t('5 hipótesis formuladas'), 'text' => $this->t('Copilot proactivo detecta 0% validación y guía las hipótesis')],
        ['day' => 'S3-4', 'title' => $this->t('Validación con clientes'), 'text' => $this->t('25 pymes contactadas, 12 responden, 3 hipótesis validadas')],
        ['day' => 'S5-6', 'title' => $this->t('MVP y pilotos'), 'text' => $this->t('5 empresas prueban el MVP. Proyecciones: break-even mes 8')],
        ['day' => 'S7-8', 'title' => $this->t('ENISA aprobado'), 'text' => $this->t('47.000 € de préstamo participativo sin aval personal')],
      ],
      '#pricing' => [
        'free_features' => $this->t('1 calculadora, 1 Canvas IA, 3 hipótesis'),
        'starter_price' => '39',
        'starter_features' => $this->t('Canvas ilimitados, 20 hipótesis, Mastermind, proyecciones'),
        'professional_price' => '99',
        'professional_features' => $this->t('Motor A/B, copilot proactivo, acceso financiación, analytics'),
      ],
      '#pricing_url' => '/planes/emprendimiento',
      '#register_url' => '/registro/emprendimiento',
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_theme/emprendimiento-case-study',
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
