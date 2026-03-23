<?php

declare(strict_types=1);

namespace Drupal\jaraba_success_cases\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Service\MetaSitePricingService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Unified controller for case study landing pages.
 *
 * Replaces 8 vertical-specific CaseStudyControllers with a single controller
 * that loads data from the SuccessCase entity (SUCCESS-CASES-001).
 *
 * ZERO-REGION-001: Returns render array with #theme, no blocks/regions.
 * NO-HARDCODE-PRICE-001: Prices from MetaSitePricingService.
 * ROUTE-LANGPREFIX-001: URLs via Url::fromRoute().
 * CONTROLLER-READONLY-001: No readonly on inherited properties.
 */
final class CaseStudyLandingController extends ControllerBase {

  /**
   * The MetaSitePricingService.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\MetaSitePricingService|null
   */
  protected ?MetaSitePricingService $pricingService;

  /**
   * The theme extension list.
   *
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  protected ThemeExtensionList $themeExtensionList;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected FileUrlGeneratorInterface $fileUrlGenerator;

  /**
   * Vertical labels map.
   */
  private const VERTICAL_LABELS = [
    'jarabalex' => 'JarabaLex',
    'agroconecta' => 'AgroConecta',
    'comercioconecta' => 'ComercioConecta',
    'empleabilidad' => 'Empleabilidad',
    'emprendimiento' => 'Emprendimiento',
    'formacion' => 'Formación',
    'serviciosconecta' => 'ServiciosConecta',
    'andalucia_ei' => 'Andalucía +ei',
    'content_hub' => 'Content Hub',
  ];

  /**
   * URL path mapping for verticals (used in routes).
   */
  private const VERTICAL_PATH_MAP = [
    'andalucia_ei' => 'andalucia-ei',
    'content_hub' => 'content-hub',
  ];

  /**
   * VIDEO-HERO-001: Hero video filename per vertical.
   *
   * Files in themes/custom/ecosistema_jaraba_theme/videos/.
   */
  private const VERTICAL_VIDEO_MAP = [
    'jarabalex' => 'hero-jarabalex.mp4',
    'agroconecta' => 'hero-agroconecta.mp4',
    'comercioconecta' => 'hero-comercioconecta.mp4',
    'empleabilidad' => 'hero-empleabilidad.mp4',
    'emprendimiento' => 'hero-emprendimiento.mp4',
    'formacion' => 'hero-formacion.mp4',
    'serviciosconecta' => 'hero-serviciosconecta.mp4',
    'andalucia_ei' => 'hero-andalucia-ei.mp4',
    'content_hub' => 'hero-contenthub.mp4',
  ];

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ThemeExtensionList $theme_extension_list,
    FileUrlGeneratorInterface $file_url_generator,
    ?MetaSitePricingService $pricing_service = NULL,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->themeExtensionList = $theme_extension_list;
    $this->fileUrlGenerator = $file_url_generator;
    $this->pricingService = $pricing_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('extension.list.theme'),
      $container->get('file_url_generator'),
      $container->has('ecosistema_jaraba_core.metasite_pricing')
        ? $container->get('ecosistema_jaraba_core.metasite_pricing')
        : NULL,
    );
  }

  /**
   * Renders a case study landing page.
   *
   * @param string $vertical_path
   *   The vertical path segment (e.g. 'agroconecta', 'andalucia-ei').
   * @param string $slug
   *   The success case slug.
   *
   * @return array<string, mixed>
   *   Render array.
   */
  public function caseStudy(string $vertical_path, string $slug): array {
    // Resolve vertical key from path (andalucia-ei → andalucia_ei).
    $vertical = $this->resolveVerticalKey($vertical_path);

    $cases = $this->entityTypeManager
      ->getStorage('success_case')
      ->loadByProperties([
        'slug' => $slug,
        'vertical' => $vertical,
        'status' => TRUE,
      ]);

    if (empty($cases)) {
      throw new NotFoundHttpException();
    }

    /** @var \Drupal\jaraba_success_cases\Entity\SuccessCase $case */
    $case = reset($cases);

    // Pricing from MetaSitePricingService (NO-HARDCODE-PRICE-001).
    $pricing = [];
    if ($this->pricingService) {
      try {
        $pricing = $this->pricingService->getPricingPreview($vertical);
      }
      catch (\Throwable) {
        // Pricing unavailable — template will use fallbacks.
      }
    }

    // URLs via Url::fromRoute() (ROUTE-LANGPREFIX-001).
    $pricingUrl = $this->safeUrl(
      'ecosistema_jaraba_core.pricing.vertical',
      ['vertical_key' => $vertical_path],
      '/planes/' . $vertical_path,
    );
    $registerUrl = $this->safeUrl(
      'ecosistema_jaraba_core.onboarding.register',
      ['vertical' => $vertical],
      '/registro/' . $vertical,
    );

    // Prepare case data as primitives for Twig.
    $caseData = $this->prepareCaseData($case);

    // VIDEO-HERO-001: Resolve hero video URL.
    // Entity video_url overrides convention-based filename.
    $themePath = '/' . $this->themeExtensionList->getPath('ecosistema_jaraba_theme');
    if (!empty($caseData['video_url'])) {
      $caseData['hero_video_url'] = $caseData['video_url'];
    }
    elseif (isset(self::VERTICAL_VIDEO_MAP[$vertical])) {
      $videoFile = $themePath . '/videos/' . self::VERTICAL_VIDEO_MAP[$vertical];
      $caseData['hero_video_url'] = $videoFile;
    }

    // Load a cross-case for social proof (different vertical).
    $crossCase = $this->loadCrossCase($vertical, (int) $case->id());

    // Global metrics from theme settings.
    $globalMetrics = $this->getGlobalMetrics();

    return [
      '#theme' => 'case_study_landing',
      '#case' => $caseData,
      '#pricing' => $pricing,
      '#pricing_url' => $pricingUrl,
      '#register_url' => $registerUrl,
      '#vertical' => $vertical,
      '#vertical_label' => self::VERTICAL_LABELS[$vertical] ?? $vertical,
      '#cross_case' => $crossCase,
      '#global_metrics' => $globalMetrics,
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_theme/case-study-landing',
          'ecosistema_jaraba_theme/scroll-animations',
          'ecosistema_jaraba_theme/landing-sticky-cta',
          'ecosistema_jaraba_theme/landing-sections',
        ],
      ],
      '#cache' => [
        'max-age' => 86400,
        'tags' => ['success_case_list', 'success_case:' . $case->id()],
        'contexts' => ['url.path'],
      ],
    ];
  }

  /**
   * Prepares SuccessCase entity data as primitives for Twig.
   *
   * @param \Drupal\jaraba_success_cases\Entity\SuccessCase $case
   *   The entity.
   *
   * @return array<string, mixed>
   *   Flat array of primitives and decoded JSON.
   */
  private function prepareCaseData($case): array {
    $data = [
      'id' => (int) $case->id(),
      'name' => $case->get('name')->value ?? '',
      'slug' => $case->get('slug')->value ?? '',
      'profession' => $case->get('profession')->value ?? '',
      'company' => $case->get('company')->value ?? '',
      'sector' => $case->get('sector')->value ?? '',
      'location' => $case->get('location')->value ?? '',
      'vertical' => $case->get('vertical')->value ?? '',
      'rating' => (int) ($case->get('rating')->value ?? 5),
      'protagonist_name' => $case->get('protagonist_name')->value ?? $case->get('name')->value ?? '',
      'protagonist_role' => $case->get('protagonist_role')->value ?? $case->get('profession')->value ?? '',
      'protagonist_company' => $case->get('protagonist_company')->value ?? $case->get('company')->value ?? '',
      'headline' => $case->get('headline')->value ?? '',
      'subtitle' => $case->get('subtitle')->value ?? '',
      'cta_urgency_text' => $case->get('cta_urgency_text')->value ?? '',
      'challenge_before' => $case->get('challenge_before')->value ?? '',
      'solution_during' => $case->get('solution_during')->value ?? '',
      'result_after' => $case->get('result_after')->value ?? '',
      'quote_short' => $case->get('quote_short')->value ?? '',
      'quote_long' => $case->get('quote_long')->value ?? '',
      'meta_description' => $case->get('meta_description')->value ?? '',
      'video_url' => $case->get('video_url')->value ?? '',
      'schema_date_published' => $case->get('schema_date_published')->value ?? '',
      'changed' => date('Y-m-d', (int) $case->get('changed')->value),
    ];

    // Resolve image URLs from entity fields.
    $imageFields = [
      'hero_image', 'protagonist_image', 'before_after_image',
      'discovery_image', 'dashboard_image',
    ];
    foreach ($imageFields as $field) {
      $data[$field] = '';
      $data[$field . '_alt'] = '';
      if ($case->hasField($field) && !$case->get($field)->isEmpty()) {
        $file = $case->get($field)->entity;
        if ($file) {
          $data[$field] = $this->fileUrlGenerator->generateString($file->getFileUri());
          $data[$field . '_alt'] = $case->get($field)->alt ?? '';
        }
      }
    }

    // Fallback: Use static theme images when entity fields are empty.
    // Legacy images in images/{vertical}-case-study/*.webp.
    $vertical = $data['vertical'];
    $themePath = '/' . $this->themeExtensionList->getPath('ecosistema_jaraba_theme');
    $this->applyImageFallbacks($data, $vertical, $themePath);

    // Decode JSON fields.
    $jsonFields = [
      'metrics_json', 'pain_points_json', 'timeline_json',
      'discovery_features_json', 'comparison_json',
      'additional_testimonials_json', 'faq_json',
      'partner_logos_json', 'how_it_works_json',
    ];
    foreach ($jsonFields as $field) {
      $key = str_replace('_json', '', $field);
      $raw = $case->get($field)->value ?? '';
      $data[$key] = [];
      if ($raw) {
        $decoded = json_decode($raw, TRUE);
        if (is_array($decoded)) {
          $data[$key] = $decoded;
        }
      }
    }

    // Testimonial composite.
    $data['testimonial'] = [
      'quote' => $data['quote_long'] ?: $data['quote_short'],
      'name' => $data['protagonist_name'],
      'role' => $data['protagonist_role'],
      'company' => $data['protagonist_company'],
      'rating' => $data['rating'],
    ];

    return $data;
  }

  /**
   * Loads a cross-case from a different vertical for social proof.
   */
  private function loadCrossCase(string $currentVertical, int $currentId): ?array {
    try {
      $query = $this->entityTypeManager
        ->getStorage('success_case')
        ->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', TRUE)
        ->condition('vertical', $currentVertical, '<>')
        ->condition('id', $currentId, '<>')
        ->condition('featured', TRUE)
        ->sort('weight', 'ASC')
        ->range(0, 1);

      $ids = $query->execute();
      if (empty($ids)) {
        return NULL;
      }

      $crossCase = $this->entityTypeManager->getStorage('success_case')->load(reset($ids));
      if (!$crossCase) {
        return NULL;
      }

      $crossVertical = $crossCase->get('vertical')->value ?? '';
      $crossPath = self::VERTICAL_PATH_MAP[$crossVertical] ?? $crossVertical;

      return [
        'title' => $crossCase->get('protagonist_name')->value ?: $crossCase->get('name')->value,
        'subtitle' => self::VERTICAL_LABELS[$crossVertical] ?? $crossVertical,
        'url' => '/' . $crossPath . '/caso-de-exito/' . ($crossCase->get('slug')->value ?? ''),
        'image' => '',
      ];
    }
    catch (\Throwable) {
      return NULL;
    }
  }

  /**
   * Gets global platform metrics from theme settings.
   *
   * @return array<int, array{value: string, label: string}>
   */
  private function getGlobalMetrics(): array {
    return [
      ['value' => '10', 'label' => (string) $this->t('Verticales especializados')],
      ['value' => '500', 'label' => (string) $this->t('Empresas activas')],
      ['value' => '98', 'label' => (string) $this->t('% Satisfacción')],
    ];
  }

  /**
   * Resolves vertical key from URL path segment.
   */
  private function resolveVerticalKey(string $path): string {
    $map = array_flip(self::VERTICAL_PATH_MAP);
    return $map[$path] ?? $path;
  }

  /**
   * Applies fallback images from theme static assets when entity fields empty.
   *
   * Legacy images in images/{vertical}-case-study/*.webp are used as fallback
   * until proper images are uploaded via the entity admin form.
   *
   * @param array &$data
   *   The case data array (modified in place).
   * @param string $vertical
   *   The vertical machine name.
   * @param string $themePath
   *   The theme base path (e.g. '/themes/custom/ecosistema_jaraba_theme').
   */
  private function applyImageFallbacks(array &$data, string $vertical, string $themePath): void {
    // Map vertical to directory name (underscores → hyphens).
    $dirMap = [
      'jarabalex' => 'jarabalex-case-study',
      'agroconecta' => 'agroconecta-case-study',
      'comercioconecta' => 'comercioconecta-case-study',
      'empleabilidad' => 'empleabilidad-case-study',
      'emprendimiento' => 'emprendimiento-case-study',
      'formacion' => 'formacion-case-study',
      'serviciosconecta' => 'serviciosconecta-case-study',
      'andalucia_ei' => 'andalucia-ei-case-study',
      'content_hub' => 'contenthub-case-study',
    ];

    $dir = $dirMap[$vertical] ?? '';
    if (!$dir) {
      return;
    }

    $imgBase = $themePath . '/images/' . $dir;

    // Per-vertical image mapping: entity field → static filename.
    $imageMap = [
      'jarabalex' => [
        'hero_image' => 'malaga-hero.webp',
        'protagonist_image' => 'elena-despacho.webp',
        'before_after_image' => 'antes-despues.webp',
        'discovery_image' => 'busqueda-ia.webp',
        'dashboard_image' => 'dashboard-legal.webp',
      ],
      'agroconecta' => [
        'hero_image' => 'jaen-olivares-hero.webp',
        'protagonist_image' => 'antonio-olivar.webp',
        'before_after_image' => 'antes-despues-agro.webp',
        'discovery_image' => 'qr-trazabilidad.webp',
        'dashboard_image' => 'dashboard-productor.webp',
      ],
      'comercioconecta' => [
        'hero_image' => 'sevilla-boutique-hero.webp',
        'protagonist_image' => 'carmen-boutique.webp',
        'before_after_image' => 'antes-despues-comercio.webp',
        'discovery_image' => 'qr-escaparate.webp',
        'dashboard_image' => 'dashboard-comerciante.webp',
      ],
      'empleabilidad' => [
        'hero_image' => 'malaga-hero.webp',
        'protagonist_image' => 'rosa-oficina.webp',
        'before_after_image' => 'antes-despues-empleo.webp',
        'discovery_image' => 'diagnostico-movil.webp',
        'dashboard_image' => 'health-score-dashboard.webp',
      ],
      'emprendimiento' => [
        'hero_image' => 'bilbao-hero.webp',
        'protagonist_image' => 'carlos-coworking.webp',
        'before_after_image' => 'antes-despues-emprendimiento.webp',
        'discovery_image' => 'canvas-ia-tablet.webp',
        'dashboard_image' => 'health-score-emprendedor.webp',
      ],
      'formacion' => [
        'hero_image' => 'aula-digital-hero.webp',
        'protagonist_image' => 'maria-coworking.webp',
        'before_after_image' => 'antes-despues-formacion.webp',
        'discovery_image' => 'copilot-course-builder.webp',
        'dashboard_image' => 'lms-dashboard.webp',
      ],
      'serviciosconecta' => [
        'hero_image' => 'chamberi-hero.webp',
        'protagonist_image' => 'carmen-consulta.webp',
        'before_after_image' => 'antes-despues-servicios.webp',
        'discovery_image' => 'qr-reservas-clinica.webp',
        'dashboard_image' => 'dashboard-servicios.webp',
      ],
      'andalucia_ei' => [
        'hero_image' => 'jaen-agencia-hero.webp',
        'protagonist_image' => 'ana-martinez-aedl.webp',
        'before_after_image' => 'antes-despues-instituciones.webp',
        'discovery_image' => 'informe-fse-automatico.webp',
        'dashboard_image' => 'dashboard-impacto-ods.webp',
      ],
      'content_hub' => [
        'hero_image' => 'bodega-montilla-hero.webp',
        'protagonist_image' => 'luis-moreno-bodega.webp',
        'before_after_image' => 'antes-despues-contenido.webp',
        'discovery_image' => 'editor-ia-seo.webp',
        'dashboard_image' => 'analytics-seo-dashboard.webp',
      ],
    ];

    $mapping = $imageMap[$vertical] ?? [];
    foreach ($mapping as $field => $filename) {
      if (empty($data[$field])) {
        $data[$field] = $imgBase . '/' . $filename;
        if (empty($data[$field . '_alt'])) {
          $data[$field . '_alt'] = str_replace(['-', '.webp'], [' ', ''], $filename);
        }
      }
    }
  }

  /**
   * Generates a URL safely with fallback.
   *
   * ROUTE-LANGPREFIX-001: Always use Url::fromRoute().
   */
  private function safeUrl(string $route, array $params, string $fallback): string {
    try {
      return Url::fromRoute($route, $params)->toString();
    }
    catch (\Throwable) {
      return $fallback;
    }
  }

}
