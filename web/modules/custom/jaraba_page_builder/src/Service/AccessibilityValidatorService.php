<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Service;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Log\LoggerInterface;

/**
 * Servicio de validacion de accesibilidad WCAG para bloques del Page Builder.
 *
 * Valida HTML contra reglas ARIA y WCAG 2.1 AA.
 * Retorna violations, warnings y passes para cada regla evaluada.
 *
 * PROPOSITO:
 * Permite al Canvas Editor mostrar en tiempo real si los bloques
 * cumplen con los requisitos de accesibilidad antes de publicar.
 *
 * DIRECTRICES:
 * - Spec 20260126 §7.1 (Accesibilidad ARIA)
 * - WCAG 2.1 AA como minimo
 * - Strings traducibles con t()
 *
 * @see docs/implementacion/2026-02-12_Plan_Cierre_Gaps_Specs_20260126_v1.md P0-02
 */
class AccessibilityValidatorService {

  use StringTranslationTrait;

  /**
   * Reglas ARIA que se validan por tipo de bloque.
   *
   * Cada regla define:
   * - selector: CSS selector para encontrar el elemento
   * - attribute: Atributo ARIA requerido
   * - level: 'A' o 'AA' segun WCAG
   * - message: Descripcion del problema
   */
  protected const ARIA_RULES = [
    // Reglas globales para todos los bloques.
    'img_alt' => [
      'selector' => 'img',
      'check' => 'has_attribute',
      'attribute' => 'alt',
      'level' => 'A',
      'message' => 'Las imagenes deben tener atributo alt',
    ],
    'button_label' => [
      'selector' => 'button',
      'check' => 'has_accessible_name',
      'level' => 'A',
      'message' => 'Los botones deben tener nombre accesible (texto, aria-label, o aria-labelledby)',
    ],
    'link_label' => [
      'selector' => 'a[href]',
      'check' => 'has_accessible_name',
      'level' => 'A',
      'message' => 'Los enlaces deben tener nombre accesible',
    ],
    'heading_hierarchy' => [
      'selector' => 'h1,h2,h3,h4,h5,h6',
      'check' => 'heading_order',
      'level' => 'A',
      'message' => 'Los encabezados deben seguir una jerarquia logica',
    ],
    'interactive_focusable' => [
      'selector' => '[data-tilt],[data-spotlight],[data-typewriter]',
      'check' => 'is_focusable',
      'level' => 'AA',
      'message' => 'Los elementos interactivos deben ser focusables via teclado',
    ],
    'section_landmark' => [
      'selector' => 'section.jaraba-block',
      'check' => 'has_landmark',
      'level' => 'A',
      'message' => 'Las secciones deben tener aria-label o aria-labelledby',
    ],
    'form_labels' => [
      'selector' => 'input,select,textarea',
      'check' => 'has_label',
      'level' => 'A',
      'message' => 'Los campos de formulario deben tener etiqueta asociada',
    ],
    'color_contrast' => [
      'selector' => '.jaraba-block',
      'check' => 'contrast_ratio',
      'level' => 'AA',
      'message' => 'El ratio de contraste debe ser minimo 4.5:1 para texto normal',
    ],
  ];

  /**
   * Logger del modulo.
   */
  protected LoggerInterface $logger;

  /**
   * Constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del modulo.
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * Valida un fragmento HTML contra las reglas ARIA/WCAG.
   *
   * @param string $html
   *   HTML a validar.
   * @param string $block_type
   *   Tipo de bloque (para reglas especificas).
   *
   * @return array
   *   Resultado con claves:
   *   - violations: array de violations encontradas
   *   - warnings: array de advertencias
   *   - passes: array de reglas que pasan
   *   - score: puntuacion 0-100
   *   - level: nivel WCAG alcanzado ('none', 'A', 'AA', 'AAA')
   */
  public function validate(string $html, string $block_type = ''): array {
    $violations = [];
    $warnings = [];
    $passes = [];

    if (empty(trim($html))) {
      return [
        'violations' => [
          [
            'rule' => 'empty_content',
            'level' => 'A',
            'message' => $this->t('El contenido esta vacio'),
            'selector' => '',
            'impact' => 'critical',
          ],
        ],
        'warnings' => [],
        'passes' => [],
        'score' => 0,
        'level' => 'none',
      ];
    }

    // Parsear HTML con DOMDocument.
    $dom = new \DOMDocument();
    // Suprimir warnings de HTML5 tags.
    @$dom->loadHTML(
      '<?xml encoding="UTF-8"><div id="a11y-root">' . $html . '</div>',
      LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR
    );

    $xpath = new \DOMXPath($dom);

    // Ejecutar cada regla.
    foreach (self::ARIA_RULES as $rule_id => $rule) {
      $result = $this->evaluateRule($xpath, $dom, $rule_id, $rule);

      if ($result['status'] === 'violation') {
        $violations[] = $result;
      }
      elseif ($result['status'] === 'warning') {
        $warnings[] = $result;
      }
      else {
        $passes[] = $result;
      }
    }

    // Calcular score y nivel.
    $total_rules = count(self::ARIA_RULES);
    $passed = count($passes);
    $score = $total_rules > 0 ? round(($passed / $total_rules) * 100) : 100;

    // Determinar nivel WCAG alcanzado.
    $has_a_violations = !empty(array_filter($violations, fn($v) => $v['level'] === 'A'));
    $has_aa_violations = !empty(array_filter($violations, fn($v) => $v['level'] === 'AA'));

    $level = 'AAA';
    if ($has_aa_violations) {
      $level = 'A';
    }
    if ($has_a_violations) {
      $level = 'none';
    }

    return [
      'violations' => $violations,
      'warnings' => $warnings,
      'passes' => $passes,
      'score' => $score,
      'level' => $level,
    ];
  }

  /**
   * Evalua una regla individual contra el DOM.
   *
   * @param \DOMXPath $xpath
   *   XPath evaluator.
   * @param \DOMDocument $dom
   *   Documento DOM.
   * @param string $rule_id
   *   ID de la regla.
   * @param array $rule
   *   Definicion de la regla.
   *
   * @return array
   *   Resultado con status (violation|warning|pass), regla y detalles.
   */
  protected function evaluateRule(\DOMXPath $xpath, \DOMDocument $dom, string $rule_id, array $rule): array {
    $base = [
      'rule' => $rule_id,
      'level' => $rule['level'],
      'message' => $this->t($rule['message']),
      'selector' => $rule['selector'],
    ];

    switch ($rule['check']) {
      case 'has_attribute':
        return $this->checkHasAttribute($xpath, $rule, $base);

      case 'has_accessible_name':
        return $this->checkAccessibleName($xpath, $rule, $base);

      case 'heading_order':
        return $this->checkHeadingOrder($dom, $base);

      case 'is_focusable':
        return $this->checkIsFocusable($xpath, $rule, $base);

      case 'has_landmark':
        return $this->checkHasLandmark($xpath, $rule, $base);

      case 'has_label':
        return $this->checkHasLabel($xpath, $rule, $base);

      case 'contrast_ratio':
        // Contraste solo se puede verificar client-side con computed styles.
        return array_merge($base, [
          'status' => 'warning',
          'impact' => 'moderate',
          'message' => $this->t('El contraste de color requiere verificacion client-side'),
        ]);

      default:
        return array_merge($base, ['status' => 'pass', 'impact' => 'none']);
    }
  }

  /**
   * Verifica que un elemento tiene un atributo especifico.
   */
  protected function checkHasAttribute(\DOMXPath $xpath, array $rule, array $base): array {
    // Convertir CSS selector basico a XPath.
    $xpathQuery = $this->cssToXpath($rule['selector']);
    $nodes = @$xpath->query($xpathQuery);

    if ($nodes === FALSE || $nodes->length === 0) {
      return array_merge($base, ['status' => 'pass', 'impact' => 'none']);
    }

    $missing = 0;
    foreach ($nodes as $node) {
      if (!$node->hasAttribute($rule['attribute'])) {
        $missing++;
      }
      elseif (trim($node->getAttribute($rule['attribute'])) === '') {
        $missing++;
      }
    }

    if ($missing > 0) {
      return array_merge($base, [
        'status' => 'violation',
        'impact' => 'critical',
        'count' => $missing,
      ]);
    }

    return array_merge($base, ['status' => 'pass', 'impact' => 'none']);
  }

  /**
   * Verifica que un elemento tiene nombre accesible.
   */
  protected function checkAccessibleName(\DOMXPath $xpath, array $rule, array $base): array {
    $xpathQuery = $this->cssToXpath($rule['selector']);
    $nodes = @$xpath->query($xpathQuery);

    if ($nodes === FALSE || $nodes->length === 0) {
      return array_merge($base, ['status' => 'pass', 'impact' => 'none']);
    }

    $missing = 0;
    foreach ($nodes as $node) {
      $hasName = FALSE;

      // Verificar texto interno.
      if (trim($node->textContent) !== '') {
        $hasName = TRUE;
      }
      // Verificar aria-label.
      if ($node->hasAttribute('aria-label') && trim($node->getAttribute('aria-label')) !== '') {
        $hasName = TRUE;
      }
      // Verificar aria-labelledby.
      if ($node->hasAttribute('aria-labelledby')) {
        $hasName = TRUE;
      }
      // Verificar title.
      if ($node->hasAttribute('title') && trim($node->getAttribute('title')) !== '') {
        $hasName = TRUE;
      }

      if (!$hasName) {
        $missing++;
      }
    }

    if ($missing > 0) {
      return array_merge($base, [
        'status' => 'violation',
        'impact' => 'critical',
        'count' => $missing,
      ]);
    }

    return array_merge($base, ['status' => 'pass', 'impact' => 'none']);
  }

  /**
   * Verifica la jerarquia de encabezados.
   */
  protected function checkHeadingOrder(\DOMDocument $dom, array $base): array {
    $headings = [];
    foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $tag) {
      $nodes = $dom->getElementsByTagName($tag);
      foreach ($nodes as $node) {
        $headings[] = (int) substr($tag, 1);
      }
    }

    if (count($headings) <= 1) {
      return array_merge($base, ['status' => 'pass', 'impact' => 'none']);
    }

    // Verificar que no hay saltos de nivel mayor a 1.
    for ($i = 1; $i < count($headings); $i++) {
      if ($headings[$i] - $headings[$i - 1] > 1) {
        return array_merge($base, [
          'status' => 'warning',
          'impact' => 'moderate',
          'message' => $this->t('Salto de nivel de encabezado: h@from a h@to', [
            '@from' => $headings[$i - 1],
            '@to' => $headings[$i],
          ]),
        ]);
      }
    }

    return array_merge($base, ['status' => 'pass', 'impact' => 'none']);
  }

  /**
   * Verifica que elementos interactivos son focusables.
   */
  protected function checkIsFocusable(\DOMXPath $xpath, array $rule, array $base): array {
    $selectors = explode(',', $rule['selector']);
    $missing = 0;
    $total = 0;

    foreach ($selectors as $selector) {
      $xpathQuery = $this->cssToXpath(trim($selector));
      $nodes = @$xpath->query($xpathQuery);

      if ($nodes === FALSE) {
        continue;
      }

      foreach ($nodes as $node) {
        $total++;
        $tagName = strtolower($node->nodeName);
        $nativelyFocusable = in_array($tagName, ['a', 'button', 'input', 'select', 'textarea']);

        if (!$nativelyFocusable && !$node->hasAttribute('tabindex')) {
          $missing++;
        }
      }
    }

    if ($total === 0) {
      return array_merge($base, ['status' => 'pass', 'impact' => 'none']);
    }

    if ($missing > 0) {
      return array_merge($base, [
        'status' => 'violation',
        'impact' => 'serious',
        'count' => $missing,
      ]);
    }

    return array_merge($base, ['status' => 'pass', 'impact' => 'none']);
  }

  /**
   * Verifica que secciones tienen landmarks ARIA.
   */
  protected function checkHasLandmark(\DOMXPath $xpath, array $rule, array $base): array {
    $nodes = @$xpath->query('//section');

    if ($nodes === FALSE || $nodes->length === 0) {
      return array_merge($base, ['status' => 'pass', 'impact' => 'none']);
    }

    $missing = 0;
    foreach ($nodes as $node) {
      $hasLandmark = FALSE;

      if ($node->hasAttribute('aria-label')) {
        $hasLandmark = TRUE;
      }
      if ($node->hasAttribute('aria-labelledby')) {
        $hasLandmark = TRUE;
      }
      if ($node->hasAttribute('role')) {
        $hasLandmark = TRUE;
      }

      if (!$hasLandmark) {
        $missing++;
      }
    }

    if ($missing > 0) {
      return array_merge($base, [
        'status' => 'warning',
        'impact' => 'moderate',
        'count' => $missing,
      ]);
    }

    return array_merge($base, ['status' => 'pass', 'impact' => 'none']);
  }

  /**
   * Verifica que campos de formulario tienen etiquetas.
   */
  protected function checkHasLabel(\DOMXPath $xpath, array $rule, array $base): array {
    $selectors = ['input', 'select', 'textarea'];
    $missing = 0;
    $total = 0;

    foreach ($selectors as $tag) {
      $nodes = @$xpath->query('//' . $tag);

      if ($nodes === FALSE) {
        continue;
      }

      foreach ($nodes as $node) {
        // Ignorar inputs hidden.
        if ($node->getAttribute('type') === 'hidden') {
          continue;
        }

        $total++;
        $hasLabel = FALSE;

        // Verificar aria-label.
        if ($node->hasAttribute('aria-label')) {
          $hasLabel = TRUE;
        }
        // Verificar aria-labelledby.
        if ($node->hasAttribute('aria-labelledby')) {
          $hasLabel = TRUE;
        }
        // Verificar id + label[for].
        if ($node->hasAttribute('id')) {
          $id = $node->getAttribute('id');
          $labels = @$xpath->query('//label[@for="' . $id . '"]');
          if ($labels !== FALSE && $labels->length > 0) {
            $hasLabel = TRUE;
          }
        }
        // Verificar placeholder (warning, no label real).
        if ($node->hasAttribute('placeholder') && !$hasLabel) {
          $missing++;
          continue;
        }

        if (!$hasLabel) {
          $missing++;
        }
      }
    }

    if ($total === 0) {
      return array_merge($base, ['status' => 'pass', 'impact' => 'none']);
    }

    if ($missing > 0) {
      return array_merge($base, [
        'status' => 'violation',
        'impact' => 'critical',
        'count' => $missing,
      ]);
    }

    return array_merge($base, ['status' => 'pass', 'impact' => 'none']);
  }

  /**
   * Convierte un selector CSS basico a XPath.
   *
   * Solo soporta selectores simples: tag, .class, [attr], tag.class[attr].
   *
   * @param string $css
   *   Selector CSS.
   *
   * @return string
   *   Expresion XPath equivalente.
   */
  protected function cssToXpath(string $css): string {
    $css = trim($css);

    // Multiples selectores separados por coma.
    if (str_contains($css, ',')) {
      $parts = array_map(fn($s) => $this->cssToXpath(trim($s)), explode(',', $css));
      return implode(' | ', $parts);
    }

    // Selector por atributo: [data-something].
    if (preg_match('/^(\w*)\[([^\]]+)\]$/', $css, $m)) {
      $tag = $m[1] ?: '*';
      return '//' . $tag . '[@' . $m[2] . ']';
    }

    // Selector por clase: .class o tag.class.
    if (preg_match('/^(\w*)\.([a-zA-Z0-9_-]+)$/', $css, $m)) {
      $tag = $m[1] ?: '*';
      return '//' . $tag . '[contains(@class, "' . $m[2] . '")]';
    }

    // Selector por tag simple.
    if (preg_match('/^\w+$/', $css)) {
      return '//' . $css;
    }

    // Fallback: buscar como tag.
    return '//' . preg_replace('/[^a-zA-Z0-9]/', '', $css);
  }

}
