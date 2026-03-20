<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Landing de caso de éxito: Diputación de Jaén (Programa +ei).
 *
 * Storytelling de producto para conversión del vertical Andalucía EI.
 * Zero-region pattern (ZERO-REGION-001).
 */
class AndaluciaEiCaseStudyController extends ControllerBase {

  /**
   * Página de caso de éxito: Diputación de Jaén.
   *
   * @return array<string, mixed>
   *   Render array con el caso de éxito.
   */
  public function caseStudy(): array {
    $themePath = \Drupal::service('extension.list.theme')
      ->getPath('ecosistema_jaraba_theme');
    $imgBase = '/' . $themePath . '/images/andalucia-ei-case-study';

    return [
      '#theme' => 'andalucia_ei_case_study',
      '#hero_image' => $imgBase . '/jaen-agencia-hero.webp',
      '#ana_image' => $imgBase . '/ana-martinez-aedl.webp',
      '#before_after_image' => $imgBase . '/antes-despues-instituciones.webp',
      '#dashboard_image' => $imgBase . '/dashboard-impacto-ods.webp',
      '#mentoring_image' => $imgBase . '/mentoria-ia-emprendedor.webp',
      '#report_image' => $imgBase . '/informe-fse-automatico.webp',
      '#metrics' => [
        ['label' => $this->t('Participantes activos'), 'before' => '45 (Excel)', 'after' => '180 (plataforma)', 'change' => '+300%'],
        ['label' => $this->t('Horas mentoría IA'), 'before' => '0', 'after' => '1.250 h', 'change' => 'Nuevo'],
        ['label' => $this->t('Startups creadas'), 'before' => '8/año', 'after' => '45/año', 'change' => '+463%'],
        ['label' => $this->t('Tiempo informes FSE+'), 'before' => '3 semanas', 'after' => '1 clic', 'change' => '-99%'],
        ['label' => $this->t('ODS alineados y medidos'), 'before' => '0', 'after' => '6 ODS', 'change' => 'Nuevo'],
      ],
      '#testimonial' => [
        'quote' => $this->t('Antes teníamos 45 emprendedores en un Excel sin actualizar. Ahora gestionamos 180 participantes con mentoría IA incluida, tracking de fases PIIL automático y los informes para el FSE+ se generan con un clic. Lo que antes nos costaba 3 semanas ahora tarda 5 segundos. Y lo mejor: las startups creadas se han multiplicado por cinco porque el copiloto IA detecta oportunidades que nosotros no veíamos.'),
        'name' => 'Ana Martínez',
        'role' => $this->t('AEDL — Agente de Empleo y Desarrollo Local'),
        'company' => $this->t('Diputación de Jaén'),
      ],
      '#timeline' => [
        ['day' => 'M1', 'title' => $this->t('Configuración y marca'), 'text' => $this->t('Dominio propio, identidad corporativa de la Diputación, verticales activados: Emprendimiento + Empleabilidad')],
        ['day' => 'M2', 'title' => $this->t('Migración de participantes'), 'text' => $this->t('180 participantes migrados desde Excel. Fases PIIL asignadas automáticamente por IA')],
        ['day' => 'M3-4', 'title' => $this->t('Mentoría IA activa'), 'text' => $this->t('1.250 horas de mentoría augmentada. Copiloto detecta 23 oportunidades de negocio')],
        ['day' => 'M5-6', 'title' => $this->t('Primeros resultados'), 'text' => $this->t('45 startups creadas. 73% participantes activos. Dashboard ODS en tiempo real')],
        ['day' => 'M7-8', 'title' => $this->t('Informes automáticos'), 'text' => $this->t('Primer informe FSE+ generado en 1 clic. Auditoría superada sin observaciones')],
      ],
      '#pricing_url' => '/contacto',
      '#register_url' => '/contacto',
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_theme/andalucia-ei-case-study',
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
