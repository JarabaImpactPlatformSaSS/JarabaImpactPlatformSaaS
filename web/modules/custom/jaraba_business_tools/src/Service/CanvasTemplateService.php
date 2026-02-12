<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Service for managing business canvas templates.
 *
 * Proporciona plantillas predefinidas para BMC, Lean Canvas,
 * Value Proposition Canvas con variantes por vertical.
 */
class CanvasTemplateService {

  /**
   * Available canvas types with their section definitions.
   */
  private const CANVAS_TYPES = [
    'bmc' => [
      'label' => 'Business Model Canvas',
      'sections' => [
        'key_partners', 'key_activities', 'key_resources',
        'value_propositions', 'customer_relationships', 'channels',
        'customer_segments', 'cost_structure', 'revenue_streams',
      ],
    ],
    'lean' => [
      'label' => 'Lean Canvas',
      'sections' => [
        'problem', 'solution', 'key_metrics', 'unique_value_proposition',
        'unfair_advantage', 'channels', 'customer_segments',
        'cost_structure', 'revenue_streams',
      ],
    ],
    'value_proposition' => [
      'label' => 'Value Proposition Canvas',
      'sections' => [
        'customer_jobs', 'pains', 'gains',
        'products_services', 'pain_relievers', 'gain_creators',
      ],
    ],
  ];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Gets a canvas template by type.
   *
   * @param string $type
   *   The canvas type: 'bmc', 'lean', or 'value_proposition'.
   *
   * @return array
   *   Template with 'type', 'label', 'sections' (each with label, description, placeholder).
   */
  public function getTemplate(string $type): array {
    if (!isset(self::CANVAS_TYPES[$type])) {
      return ['error' => 'Unknown canvas type: ' . $type];
    }

    $definition = self::CANVAS_TYPES[$type];
    $sections = [];

    foreach ($definition['sections'] as $sectionKey) {
      $sections[$sectionKey] = [
        'key' => $sectionKey,
        'label' => $this->getSectionLabel($sectionKey),
        'description' => $this->getSectionDescription($type, $sectionKey),
        'placeholder' => $this->getSectionPlaceholder($type, $sectionKey),
        'value' => '',
      ];
    }

    return [
      'type' => $type,
      'label' => $definition['label'],
      'sections' => $sections,
    ];
  }

  /**
   * Saves a user's canvas.
   *
   * @param int $userId
   *   The user ID.
   * @param string $type
   *   The canvas type.
   * @param array $data
   *   Section data keyed by section name.
   *
   * @return array
   *   Saved canvas data with 'canvas_id'.
   */
  public function saveCanvas(int $userId, string $type, array $data): array {
    if (!isset(self::CANVAS_TYPES[$type])) {
      return ['error' => 'Unknown canvas type'];
    }

    $storage = $this->entityTypeManager->getStorage('business_canvas');

    // Check for existing canvas.
    $existing = $this->findUserCanvas($userId, $type);

    if ($existing) {
      $canvas = $storage->load($existing['canvas_id']);
      if ($canvas) {
        $canvas->set('sections_data', json_encode($data, JSON_THROW_ON_ERROR));
        $canvas->set('updated_at', date('Y-m-d\TH:i:s'));
        $canvas->save();

        return [
          'canvas_id' => (int) $canvas->id(),
          'type' => $type,
          'user_id' => $userId,
          'updated' => TRUE,
          'sections' => $data,
        ];
      }
    }

    $canvas = $storage->create([
      'user_id' => $userId,
      'canvas_type' => $type,
      'title' => self::CANVAS_TYPES[$type]['label'],
      'sections_data' => json_encode($data, JSON_THROW_ON_ERROR),
      'created_at' => date('Y-m-d\TH:i:s'),
      'updated_at' => date('Y-m-d\TH:i:s'),
    ]);

    try {
      $canvas->save();
    }
    catch (\Exception $e) {
      return ['error' => 'Failed to save canvas: ' . $e->getMessage()];
    }

    return [
      'canvas_id' => (int) $canvas->id(),
      'type' => $type,
      'user_id' => $userId,
      'created' => TRUE,
      'sections' => $data,
    ];
  }

  /**
   * Gets all canvases for a user.
   *
   * @param int $userId
   *   The user ID.
   *
   * @return array
   *   List of canvas summaries.
   */
  public function getUserCanvases(int $userId): array {
    $storage = $this->entityTypeManager->getStorage('business_canvas');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('user_id', $userId)
      ->sort('updated_at', 'DESC')
      ->execute();

    if (empty($ids)) {
      return [];
    }

    $canvases = $storage->loadMultiple($ids);
    $result = [];

    foreach ($canvases as $canvas) {
      $type = $canvas->get('canvas_type')->value ?? 'bmc';
      $result[] = [
        'canvas_id' => (int) $canvas->id(),
        'type' => $type,
        'title' => $canvas->get('title')->value ?? self::CANVAS_TYPES[$type]['label'] ?? $type,
        'completion' => $this->calculateCompletion($canvas),
        'updated_at' => $canvas->get('updated_at')->value ?? '',
      ];
    }

    return $result;
  }

  /**
   * Exports a canvas (returns data for PDF/image rendering).
   *
   * @param int $canvasId
   *   The canvas entity ID.
   * @param string $format
   *   Export format: 'pdf' or 'png'.
   *
   * @return array
   *   Export data with 'html', 'title', 'format'.
   */
  public function exportCanvas(int $canvasId, string $format = 'pdf'): array {
    $storage = $this->entityTypeManager->getStorage('business_canvas');
    $canvas = $storage->load($canvasId);

    if (!$canvas) {
      return ['error' => 'Canvas not found'];
    }

    $type = $canvas->get('canvas_type')->value ?? 'bmc';
    $sections = json_decode($canvas->get('sections_data')->value ?? '{}', TRUE) ?: [];
    $title = $canvas->get('title')->value ?? self::CANVAS_TYPES[$type]['label'] ?? $type;

    return [
      'canvas_id' => $canvasId,
      'title' => $title,
      'type' => $type,
      'format' => $format,
      'sections' => $sections,
      'html' => $this->buildExportHtml($type, $title, $sections),
    ];
  }

  /**
   * Gets template with vertical-specific defaults.
   *
   * @param string $type
   *   The canvas type.
   * @param string $vertical
   *   The vertical: 'agroconecta', 'comercioconecta', etc.
   *
   * @return array
   *   Template with pre-filled vertical hints.
   */
  public function getTemplateDefaults(string $type, string $vertical): array {
    $template = $this->getTemplate($type);

    if (isset($template['error'])) {
      return $template;
    }

    $defaults = $this->getVerticalDefaults($type, $vertical);

    foreach ($template['sections'] as $key => &$section) {
      if (isset($defaults[$key])) {
        $section['placeholder'] = $defaults[$key];
      }
    }

    $template['vertical'] = $vertical;
    return $template;
  }

  /**
   * Finds existing canvas for user+type.
   */
  protected function findUserCanvas(int $userId, string $type): ?array {
    $storage = $this->entityTypeManager->getStorage('business_canvas');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('user_id', $userId)
      ->condition('canvas_type', $type)
      ->sort('updated_at', 'DESC')
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    return ['canvas_id' => (int) reset($ids)];
  }

  /**
   * Calculates completion percentage of a canvas.
   */
  protected function calculateCompletion(object $canvas): float {
    $type = $canvas->get('canvas_type')->value ?? 'bmc';
    $sections = json_decode($canvas->get('sections_data')->value ?? '{}', TRUE) ?: [];
    $totalSections = count(self::CANVAS_TYPES[$type]['sections'] ?? []);

    if ($totalSections === 0) {
      return 0.0;
    }

    $filled = 0;
    foreach ($sections as $value) {
      if (!empty(trim((string) $value))) {
        $filled++;
      }
    }

    return round(($filled / $totalSections) * 100, 1);
  }

  /**
   * Gets human-readable section label.
   */
  protected function getSectionLabel(string $key): string {
    $labels = [
      'key_partners' => 'Socios Clave',
      'key_activities' => 'Actividades Clave',
      'key_resources' => 'Recursos Clave',
      'value_propositions' => 'Propuesta de Valor',
      'customer_relationships' => 'Relación con Clientes',
      'channels' => 'Canales',
      'customer_segments' => 'Segmentos de Clientes',
      'cost_structure' => 'Estructura de Costes',
      'revenue_streams' => 'Fuentes de Ingresos',
      'problem' => 'Problema',
      'solution' => 'Solución',
      'key_metrics' => 'Métricas Clave',
      'unique_value_proposition' => 'Propuesta de Valor Única',
      'unfair_advantage' => 'Ventaja Competitiva',
      'customer_jobs' => 'Tareas del Cliente',
      'pains' => 'Frustraciones',
      'gains' => 'Alegrías',
      'products_services' => 'Productos y Servicios',
      'pain_relievers' => 'Aliviadores de Frustraciones',
      'gain_creators' => 'Creadores de Alegrías',
    ];

    return $labels[$key] ?? ucfirst(str_replace('_', ' ', $key));
  }

  /**
   * Gets section description for a canvas type.
   */
  protected function getSectionDescription(string $type, string $key): string {
    return match ($key) {
      'problem' => 'Top 3 problemas que resuelves para tu cliente.',
      'solution' => 'Top 3 características de tu solución.',
      'key_metrics' => 'Las métricas que miden el éxito de tu negocio.',
      'unique_value_proposition' => 'Mensaje claro que explica por qué eres diferente y vale la pena.',
      'unfair_advantage' => 'Algo que no se puede copiar o comprar fácilmente.',
      'customer_jobs' => 'Qué intenta lograr tu cliente en su trabajo o vida.',
      'pains' => 'Qué le frustra o le impide completar sus tareas.',
      'gains' => 'Qué resultados y beneficios espera tu cliente.',
      default => '',
    };
  }

  /**
   * Gets placeholder text.
   */
  protected function getSectionPlaceholder(string $type, string $key): string {
    return 'Describe aquí...';
  }

  /**
   * Gets vertical-specific default hints.
   */
  protected function getVerticalDefaults(string $type, string $vertical): array {
    $defaults = [
      'agroconecta' => [
        'customer_segments' => 'Productores agrícolas locales, cooperativas, distribuidores food service',
        'channels' => 'Marketplace, WhatsApp Business, ferias agrícolas, reparto directo',
        'problem' => 'Intermediarios reducen márgenes, falta visibilidad online, logística compleja',
      ],
      'comercioconecta' => [
        'customer_segments' => 'Comercios de barrio, tiendas especializadas, hostelería local',
        'channels' => 'Google My Business, redes sociales, delivery, web propia',
        'problem' => 'Competencia online, falta presencia digital, gestión manual de inventario',
      ],
      'emprendimiento' => [
        'customer_segments' => 'Emprendedores en fase idea, startups early-stage, equipos fundadores',
        'channels' => 'Mentoring, incubadoras, eventos de networking, plataforma online',
        'problem' => 'Validación de idea incierta, falta de financiación, equipo incompleto',
      ],
    ];

    return $defaults[$vertical] ?? [];
  }

  /**
   * Builds export HTML for print.
   */
  protected function buildExportHtml(string $type, string $title, array $sections): string {
    $html = '<div class="canvas-export"><h1>' . htmlspecialchars($title) . '</h1>';
    $html .= '<div class="canvas-grid">';

    foreach (self::CANVAS_TYPES[$type]['sections'] as $key) {
      $label = $this->getSectionLabel($key);
      $value = htmlspecialchars($sections[$key] ?? '');
      $html .= "<div class='canvas-cell'><h3>{$label}</h3><p>{$value}</p></div>";
    }

    $html .= '</div></div>';
    return $html;
  }

}
