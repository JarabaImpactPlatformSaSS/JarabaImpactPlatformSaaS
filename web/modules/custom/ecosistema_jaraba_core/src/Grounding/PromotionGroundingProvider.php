<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Grounding;

use Drupal\ecosistema_jaraba_core\Service\ActivePromotionServiceInterface;
use Drupal\jaraba_copilot_v2\Grounding\GroundingProviderInterface;

/**
 * Grounding provider que inyecta promociones activas en el copilot.
 *
 * Prioridad 100 (maxima): las promociones activas SIEMPRE aparecen primero
 * en los resultados de grounding, por encima del contenido regular.
 *
 * Este provider resuelve el caso "busco curso con incentivo" → Andalucia +ei.
 */
class PromotionGroundingProvider implements GroundingProviderInterface {

  public function __construct(
    protected ActivePromotionServiceInterface $activePromotionService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getVerticalKey(): string {
    // Global: se ejecuta SIEMPRE independiente del vertical.
    return 'global';
  }

  /**
   * {@inheritdoc}
   */
  public function search(array $keywords, int $limit = 3): array {
    try {
      $promotions = $this->activePromotionService->getActivePromotions();
    }
    catch (\Throwable) {
      return [];
    }
    if ($promotions === []) {
      return [];
    }

    $results = [];

    foreach ($promotions as $promo) {
      // Verificar si algun keyword matchea con la promocion.
      if ($this->matchesKeywords($promo, $keywords) === FALSE) {
        continue;
      }

      // Construir highlights como texto.
      $highlights = [];
      foreach ($promo['highlight_values'] as $key => $value) {
        $highlights[] = ucfirst($key) . ': ' . $value;
      }

      $results[] = [
        'title' => $promo['title'],
        'summary' => $promo['description'],
        'url' => $promo['cta_url'],
        'type' => 'Promoción activa — ' . ucfirst($promo['vertical']),
        'metadata' => [
          'datos_destacados' => implode(' | ', $highlights),
          'cta' => $promo['cta_label'] . ': ' . $promo['cta_url'],
          'instruccion' => $promo['copilot_instruction'],
        ],
      ];

      if (count($results) >= $limit) {
        break;
      }
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function getPriority(): int {
    return 100;
  }

  /**
   * Determina si una promocion matchea con los keywords del usuario.
   *
   * @param array<string, mixed> $promo
   * @param array<string> $keywords
   */
  protected function matchesKeywords(array $promo, array $keywords): bool {
    // Construir texto buscable de la promocion.
    $searchableText = mb_strtolower(implode(' ', [
      $promo['title'],
      $promo['description'],
      $promo['vertical'],
      $promo['type'],
      implode(' ', array_keys($promo['highlight_values'])),
      implode(' ', array_values($promo['highlight_values'])),
    ]));

    // Al menos 1 keyword debe aparecer.
    foreach ($keywords as $keyword) {
      if (str_contains($searchableText, mb_strtolower($keyword))) {
        return TRUE;
      }
    }

    // Keywords compuestos: detectar sinonimos comunes.
    $synonymMap = [
      'curso' => ['formación', 'formacion', 'programa', 'inserción', 'insercion'],
      'incentivo' => ['ayuda', 'subvención', 'subvencion', 'beca', 'gratuito', '528'],
      'empleo' => ['trabajo', 'inserción', 'insercion', 'laboral', 'piil'],
      'gratis' => ['gratuito', 'free', 'sin coste'],
    ];

    foreach ($keywords as $keyword) {
      $kw = mb_strtolower($keyword);
      if (isset($synonymMap[$kw])) {
        foreach ($synonymMap[$kw] as $synonym) {
          if (str_contains($searchableText, $synonym)) {
            return TRUE;
          }
        }
      }
    }

    return FALSE;
  }

}
