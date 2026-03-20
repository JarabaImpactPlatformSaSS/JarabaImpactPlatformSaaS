<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Landing de caso de éxito: María López, Madrid.
 *
 * Storytelling de producto para conversión del vertical Formación.
 * Zero-region pattern (ZERO-REGION-001).
 */
class FormacionCaseStudyController extends ControllerBase {

  /**
   * Página de caso de éxito: María López.
   *
   * @return array<string, mixed>
   *   Render array con el caso de éxito.
   */
  public function caseStudy(): array {
    $themePath = \Drupal::service('extension.list.theme')
      ->getPath('ecosistema_jaraba_theme');
    $imgBase = '/' . $themePath . '/images/formacion-case-study';

    return [
      '#theme' => 'formacion_case_study',
      '#hero_image' => $imgBase . '/aula-digital-hero.webp',
      '#maria_image' => $imgBase . '/maria-coworking.webp',
      '#before_after_image' => $imgBase . '/antes-despues-formacion.webp',
      '#course_builder_image' => $imgBase . '/copilot-course-builder.webp',
      '#certificate_image' => $imgBase . '/certificado-badge.webp',
      '#dashboard_image' => $imgBase . '/lms-dashboard.webp',
      '#metrics' => [
        ['label' => $this->t('Tiempo crear un curso'), 'before' => '3 semanas', 'after' => '2 días', 'change' => '-90%'],
        ['label' => $this->t('Alumnos activos'), 'before' => '12 (presencial)', 'after' => '340 (online + presencial)', 'change' => '+2.733%'],
        ['label' => $this->t('Tasa de finalización'), 'before' => '45%', 'after' => '78%', 'change' => '+33pp'],
        ['label' => $this->t('Ingresos recurrentes'), 'before' => '1.800 €/mes', 'after' => '8.400 €/mes', 'change' => '+367%'],
        ['label' => $this->t('NPS alumnos'), 'before' => 'N/A', 'after' => '72', 'change' => 'Nuevo'],
      ],
      '#testimonial' => [
        'quote' => $this->t('Llevaba años dando formación presencial a 12 personas por curso. Con el constructor de cursos de IA, monté mi primer curso online en 2 días. Lo que más me sorprendió fue la gamificación: los alumnos compiten entre ellos, se motivan solos y la tasa de finalización se disparó al 78%. En 4 meses pasé de facturar 1.800 euros al mes a más de 8.400 con 340 alumnos activos.'),
        'name' => 'María López',
        'role' => $this->t('Formadora de marketing digital'),
        'company' => $this->t('Madrid'),
      ],
      '#timeline' => [
        ['day' => 'S1', 'title' => $this->t('Primer curso online'), 'text' => $this->t('Constructor IA genera estructura, lecciones y quizzes. Curso de 12 módulos en 2 días')],
        ['day' => 'S2-3', 'title' => $this->t('Primeras matrículas'), 'text' => $this->t('45 alumnos en la primera cohorte. Pasarela de pago integrada')],
        ['day' => 'S4-6', 'title' => $this->t('Gamificación activa'), 'text' => $this->t('Badges, ranking semanal, retos. Tasa de finalización sube del 45% al 71%')],
        ['day' => 'S7-10', 'title' => $this->t('Certificados y badges'), 'text' => $this->t('Open Badges verificables. Los alumnos los comparten en LinkedIn')],
        ['day' => 'S11-16', 'title' => $this->t('Escalado y analytics'), 'text' => $this->t('4 cursos publicados, 340 alumnos, analytics por lección y cohorte')],
      ],
      '#pricing' => [
        'free_features' => $this->t('1 curso, 10 alumnos, certificados básicos'),
        'starter_price' => '29',
        'starter_features' => $this->t('Cursos ilimitados, 100 alumnos, constructor IA, quizzes'),
        'professional_price' => '79',
        'professional_features' => $this->t('Alumnos ilimitados, gamificación, Open Badges, analytics, copilot'),
      ],
      '#pricing_url' => '/planes/formacion',
      '#register_url' => '/registro/formacion',
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_theme/formacion-case-study',
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
