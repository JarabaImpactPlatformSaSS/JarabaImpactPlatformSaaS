<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Landing de caso de éxito: Rosa Fernández, Málaga.
 *
 * Storytelling de producto para conversión del vertical Empleabilidad.
 * Zero-region pattern (ZERO-REGION-001).
 */
class EmpleabilidadCaseStudyController extends ControllerBase {

  /**
   * Página de caso de éxito: Rosa Fernández.
   *
   * @return array<string, mixed>
   *   Render array con el caso de éxito.
   */
  public function caseStudy(): array {
    $themePath = \Drupal::service('extension.list.theme')
      ->getPath('ecosistema_jaraba_theme');
    $imgBase = '/' . $themePath . '/images/empleabilidad-case-study';

    return [
      '#theme' => 'empleabilidad_case_study',
      '#hero_image' => $imgBase . '/malaga-hero.webp',
      '#rosa_image' => $imgBase . '/rosa-oficina.webp',
      '#before_after_image' => $imgBase . '/antes-despues-empleo.webp',
      '#diagnostic_image' => $imgBase . '/diagnostico-movil.webp',
      '#dashboard_image' => $imgBase . '/health-score-dashboard.webp',
      '#interview_image' => $imgBase . '/entrevista-video.webp',
      '#metrics' => [
        ['label' => $this->t('CVs para 1 entrevista'), 'before' => '87', 'after' => '3', 'change' => '-97%'],
        ['label' => $this->t('Tiempo crear CV'), 'before' => '3 días', 'after' => '25 min (IA)', 'change' => '-99%'],
        ['label' => $this->t('Tiempo hasta empleo'), 'before' => '6 meses (sin éxito)', 'after' => '21 días', 'change' => '-88%'],
        ['label' => $this->t('Health Score profesional'), 'before' => 'N/A', 'after' => '91/100', 'change' => 'Nuevo'],
        ['label' => $this->t('Salario'), 'before' => '1.430 €/mes', 'after' => '1.650 €/mes', 'change' => '+15%'],
      ],
      '#testimonial' => [
        'quote' => $this->t('A los 52 pensé que no volvería a trabajar. Llevaba 6 meses enviando currículums sin recibir ni una llamada. El diagnóstico de 3 minutos me mostró fortalezas que yo ni sabía que tenía. El CV Builder me hizo un currículum que por fin hablaba mi idioma profesional. Y el simulador de entrevistas me quitó los nervios. En 3 semanas estaba contratada, cobrando más que antes.'),
        'name' => 'Rosa Fernández',
        'role' => $this->t('Coordinadora administrativa'),
        'company' => $this->t('Torremolinos, Málaga'),
      ],
      '#timeline' => [
        ['day' => 1, 'title' => $this->t('Diagnóstico Express'), 'text' => $this->t('3 minutos, 3 preguntas. Resultado: "Perfil Coordinator — empleabilidad latente 72%"')],
        ['day' => 3, 'title' => $this->t('CV Builder con IA'), 'text' => $this->t('De 3 páginas en Word a 1 página optimizada para ATS en 25 minutos')],
        ['day' => 5, 'title' => $this->t('LinkedIn Import'), 'text' => $this->t('7 competencias adicionales detectadas por IA. Health Score: 58/100')],
        ['day' => 8, 'title' => $this->t('Matching inteligente'), 'text' => $this->t('5 ofertas con match >75%. Aplica a 3. Health Score: 67/100')],
        ['day' => 11, 'title' => $this->t('Simulador de entrevistas'), 'text' => $this->t('10 preguntas del sector. Feedback en tiempo real. Score: 8,2/10')],
        ['day' => 15, 'title' => $this->t('Primera entrevista real'), 'text' => $this->t('"La mejor entrevista que hemos tenido esta semana" — Dra. Martínez')],
        ['day' => 21, 'title' => $this->t('Contratada'), 'text' => $this->t('Coordinadora administrativa. Jornada completa. +15% de salario')],
      ],
      '#pricing' => [
        'free_features' => $this->t('1 diagnóstico + 1 CV con IA + 5 mensajes copilot/día'),
        'starter_price' => '19',
        'starter_features' => $this->t('3 diagnósticos, 15 candidaturas/día, simulador entrevistas'),
        'professional_price' => '79',
        'professional_features' => $this->t('Copilot 6 modos, LinkedIn Import, push notifications'),
      ],
      '#pricing_url' => '/planes/empleabilidad',
      '#register_url' => '/registro/empleabilidad',
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_theme/empleabilidad-case-study',
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
