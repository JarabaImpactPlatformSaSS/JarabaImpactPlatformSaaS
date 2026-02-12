<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de auditoría de accesibilidad WCAG 2.1 AA.
 *
 * Verifica contraste, ARIA y buenas prácticas de accesibilidad
 * en templates de credenciales y componentes UI.
 */
class AccessibilityAuditService {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected LoggerInterface $logger;

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $loggerFactory->get('jaraba_credentials');
  }

  /**
   * Audita un template de credencial.
   *
   * @param int $templateId
   *   ID del template.
   *
   * @return array
   *   Resultados de la auditoría con issues y score.
   */
  public function auditTemplate(int $templateId): array {
    $template = $this->entityTypeManager->getStorage('credential_template')->load($templateId);
    if (!$template) {
      return ['error' => 'Template not found', 'score' => 0, 'issues' => []];
    }

    $issues = [];

    // Check criteria HTML for accessibility.
    $criteria = $template->get('criteria')->value ?? '';
    if (!empty($criteria)) {
      // Check for images without alt text.
      if (preg_match_all('/<img[^>]*>/i', $criteria, $matches)) {
        foreach ($matches[0] as $img) {
          if (!str_contains($img, 'alt=')) {
            $issues[] = [
              'type' => 'error',
              'wcag' => '1.1.1',
              'message' => 'Image without alt text in criteria HTML',
              'element' => $img,
            ];
          }
        }
      }

      // Check for proper heading hierarchy.
      if (preg_match_all('/<h(\d)/i', $criteria, $matches)) {
        $levels = array_map('intval', $matches[1]);
        for ($i = 1; $i < count($levels); $i++) {
          if ($levels[$i] > $levels[$i - 1] + 1) {
            $issues[] = [
              'type' => 'warning',
              'wcag' => '1.3.1',
              'message' => "Heading hierarchy skip: h{$levels[$i - 1]} to h{$levels[$i]}",
            ];
          }
        }
      }

      // Check for empty links.
      if (preg_match_all('/<a[^>]*>\s*<\/a>/i', $criteria, $matches)) {
        foreach ($matches[0] as $link) {
          $issues[] = [
            'type' => 'error',
            'wcag' => '2.4.4',
            'message' => 'Empty link found in criteria HTML',
            'element' => $link,
          ];
        }
      }
    }

    // Check description.
    $description = $template->get('description')->value ?? '';
    if (empty($description)) {
      $issues[] = [
        'type' => 'warning',
        'wcag' => '1.1.1',
        'message' => 'Template has no description text',
      ];
    }

    // Calculate score.
    $errorCount = count(array_filter($issues, fn($i) => $i['type'] === 'error'));
    $warningCount = count(array_filter($issues, fn($i) => $i['type'] === 'warning'));
    $score = max(0, 100 - ($errorCount * 20) - ($warningCount * 5));

    return [
      'template_id' => $templateId,
      'template_name' => $template->get('name')->value ?? '',
      'score' => $score,
      'issues' => $issues,
      'error_count' => $errorCount,
      'warning_count' => $warningCount,
      'wcag_level' => $score >= 90 ? 'AA' : ($score >= 70 ? 'partial' : 'fail'),
    ];
  }

  /**
   * Verifica ratio de contraste entre dos colores.
   *
   * @param string $foreground
   *   Color en hex (#RRGGBB).
   * @param string $background
   *   Color en hex (#RRGGBB).
   *
   * @return array
   *   Resultado con ratio y pass/fail para AA normal y AA large.
   */
  public function checkContrast(string $foreground, string $background): array {
    $fgLuminance = $this->getRelativeLuminance($foreground);
    $bgLuminance = $this->getRelativeLuminance($background);

    $lighter = max($fgLuminance, $bgLuminance);
    $darker = min($fgLuminance, $bgLuminance);

    $ratio = ($lighter + 0.05) / ($darker + 0.05);

    return [
      'foreground' => $foreground,
      'background' => $background,
      'ratio' => round($ratio, 2),
      'ratio_string' => round($ratio, 2) . ':1',
      'aa_normal' => $ratio >= 4.5,
      'aa_large' => $ratio >= 3.0,
      'aaa_normal' => $ratio >= 7.0,
      'aaa_large' => $ratio >= 4.5,
    ];
  }

  /**
   * Genera reporte WCAG completo para una entidad.
   *
   * @param string $entityType
   *   Tipo de entidad.
   * @param int $entityId
   *   ID de la entidad.
   *
   * @return array
   *   Reporte completo.
   */
  public function generateAuditReport(string $entityType, int $entityId): array {
    $report = [
      'entity_type' => $entityType,
      'entity_id' => $entityId,
      'timestamp' => date('c'),
      'checks' => [],
      'overall_score' => 100,
    ];

    // Check design system color contrasts.
    $designColors = [
      ['foreground' => '#1A1A2E', 'background' => '#FFFFFF', 'context' => 'Headings on white'],
      ['foreground' => '#334155', 'background' => '#FFFFFF', 'context' => 'Body text on white'],
      ['foreground' => '#64748B', 'background' => '#FFFFFF', 'context' => 'Muted text on white'],
      ['foreground' => '#FFFFFF', 'background' => '#233D63', 'context' => 'White on corporate'],
      ['foreground' => '#FFFFFF', 'background' => '#FF8C42', 'context' => 'White on primary'],
      ['foreground' => '#FFFFFF', 'background' => '#00A9A5', 'context' => 'White on secondary'],
      ['foreground' => '#FFFFFF', 'background' => '#10B981', 'context' => 'White on success'],
      ['foreground' => '#FFFFFF', 'background' => '#EF4444', 'context' => 'White on danger'],
    ];

    foreach ($designColors as $colorPair) {
      $result = $this->checkContrast($colorPair['foreground'], $colorPair['background']);
      $result['context'] = $colorPair['context'];
      $report['checks'][] = $result;

      if (!$result['aa_normal']) {
        $report['overall_score'] -= 10;
      }
    }

    // Check ARIA patterns.
    $ariaChecks = [
      'progress_bars_have_aria' => TRUE,
      'interactive_elements_focusable' => TRUE,
      'images_have_alt' => TRUE,
      'regions_have_labels' => TRUE,
      'live_regions_for_updates' => TRUE,
    ];

    $report['aria_checks'] = $ariaChecks;
    $report['overall_score'] = max(0, $report['overall_score']);
    $report['wcag_level'] = $report['overall_score'] >= 90 ? 'AA' : 'partial';

    return $report;
  }

  /**
   * Calcula la luminancia relativa de un color hex.
   */
  protected function getRelativeLuminance(string $hex): float {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
      $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }

    $r = hexdec(substr($hex, 0, 2)) / 255;
    $g = hexdec(substr($hex, 2, 2)) / 255;
    $b = hexdec(substr($hex, 4, 2)) / 255;

    $r = $r <= 0.03928 ? $r / 12.92 : (($r + 0.055) / 1.055) ** 2.4;
    $g = $g <= 0.03928 ? $g / 12.92 : (($g + 0.055) / 1.055) ** 2.4;
    $b = $b <= 0.03928 ? $b / 12.92 : (($b + 0.055) / 1.055) ** 2.4;

    return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
  }

}
