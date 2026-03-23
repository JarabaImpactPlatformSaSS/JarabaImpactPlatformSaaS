<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Landing de caso de éxito: PED S.L. (Programa Andalucía +ei).
 *
 * Storytelling de producto para conversión del vertical Andalucía EI.
 * Zero-region pattern (ZERO-REGION-001).
 */
class AndaluciaEiCaseStudyController extends ControllerBase {

  /**
   * Página de caso de éxito: PED S.L.
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
      '#hero_image' => $imgBase . '/ped-equipo-hero.webp',
      '#protagonist_image' => $imgBase . '/equipo-tecnico-andalucia-ei.webp',
      '#before_after_image' => $imgBase . '/antes-despues-instituciones.webp',
      '#dashboard_image' => $imgBase . '/dashboard-impacto-ods.webp',
      '#mentoring_image' => $imgBase . '/mentoria-ia-emprendedor.webp',
      '#report_image' => $imgBase . '/informe-fse-automatico.webp',
      '#metrics' => [
        ['label' => $this->t('Participantes gestionados'), 'before' => '50 (Excel, 8 prov.)', 'after' => '45 (SaaS, 2 sedes)', 'change' => 'Centralizado'],
        ['label' => $this->t('Provincias coordinadas'), 'before' => '8 hojas Excel', 'after' => '1 panel único', 'change' => '-87%'],
        ['label' => $this->t('Itinerarios IPAE'), 'before' => 'Manual', 'after' => 'Asistido por IA', 'change' => 'Nuevo'],
        ['label' => $this->t('Tiempo informes SAE'), 'before' => 'Semanas', 'after' => 'Tiempo real', 'change' => '-99%'],
        ['label' => $this->t('Inserción laboral 1ª ed.'), 'before' => '21 personas', 'after' => '42% tasa', 'change' => 'Verificado'],
      ],
      '#testimonial_hero' => [
        'quote' => $this->t('Construimos Andalucía +ei porque nosotros mismos sufrimos el problema. La primera edición la gestionamos con Excel — esa experiencia nos mostró exactamente qué necesitábamos.'),
        'name' => $this->t('José Jaraba'),
        'role' => $this->t('Director del programa'),
        'company' => $this->t('PED S.L.'),
      ],
      '#testimonial' => [
        'quote' => $this->t('En la primera edición coordinábamos 8 provincias con una hoja Excel compartida que nadie actualizaba a tiempo. Los informes para el SAE tardaban semanas. Ahora el SaaS nos da visibilidad en tiempo real de cada participante y su itinerario de inserción.'),
        'name' => $this->t('Equipo técnico del programa Andalucía +ei'),
        'role' => $this->t('Gestión de programas de inserción laboral'),
        'company' => $this->t('PED S.L.'),
      ],
      '#timeline' => [
        ['day' => '2023', 'title' => $this->t('1ª edición — el problema'), 'text' => $this->t('PIIL con 50 participantes en 8 provincias. 8 orientadores coordinados por email. Hojas Excel por provincia. Informes manuales al SAE')],
        ['day' => '2024', 'title' => $this->t('Diseño del SaaS'), 'text' => $this->t('La experiencia de gestionar con Excel inspira la creación de Andalucía +ei: fichas, itinerarios IPAE, formación, seguimiento e informes automáticos')],
        ['day' => '2025', 'title' => $this->t('2ª edición — la solución'), 'text' => $this->t('Concesión PIIL Colectivos Vulnerables. 45 participantes, 2 sedes (Sevilla y Córdoba). Gestión íntegra desde el SaaS')],
        ['day' => '2026', 'title' => $this->t('Reclutamiento y ejecución'), 'text' => $this->t('Ficha técnica validada. Reclutamiento en curso con copilot IA para matching participante-itinerario')],
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

  /**
   * Redirect 301 desde el slug antiguo (diputacion-jaen).
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect permanente a la nueva URL.
   */
  public function legacyRedirect(): RedirectResponse {
    try {
      $url = Url::fromRoute('jaraba_andalucia_ei.case_study.ped')->toString();
    }
    catch (\Exception $e) {
      $url = '/andalucia-ei/caso-de-exito/plataforma-ecosistemas-digitales';
    }
    return new RedirectResponse($url, 301);
  }

}
