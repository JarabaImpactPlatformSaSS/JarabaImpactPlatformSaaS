<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de experimentacion A/B para el vertical AgroConecta.
 *
 * Plan Elevacion AgroConecta Clase Mundial v1 — Fase 11 (A/B Testing)
 *
 * PROPOSITO:
 * Gestiona experimentos A/B especificos para el vertical agroconecta,
 * permitiendo probar variantes de flujos clave: checkout CTA, product cards,
 * pricing page y landing hero copy.
 *
 * LOGICA:
 * - getVariant(): obtiene la variante asignada a un usuario para un experimento.
 * - trackConversion(): registra eventos de conversion del vertical.
 * - getResults(): obtiene metricas de un experimento por variante.
 *
 * 4 experimentos iniciales:
 * - checkout_cta_color: A (--ej-color-agro) / B (--ej-color-accent)
 *   metric: order_completed
 * - product_card_layout: A (vertical) / B (horizontal)
 *   metric: add_to_cart
 * - pricing_page_variant: A (3 columns) / B (interactive slider)
 *   metric: plan_upgrade
 * - landing_hero_copy: A ("Vende tus productos") / B ("Conecta con tu mercado")
 *   metric: registration_started
 *
 * @see \Drupal\ecosistema_jaraba_core\Service\EmprendimientoExperimentService
 * @see \Drupal\ecosistema_jaraba_core\Service\AndaluciaEiExperimentService
 */
class AgroConectaExperimentService {

  /**
   * Tipo de experimento para agroconecta.
   */
  protected const EXPERIMENT_TYPE = 'agroconecta';

  /**
   * Definicion de experimentos con variantes y metricas.
   */
  protected const EXPERIMENTS = [
    'checkout_cta_color' => [
      'label' => 'Color CTA Checkout',
      'variants' => [
        'A' => ['label' => '--ej-color-agro', 'config' => ['css_var' => '--ej-color-agro']],
        'B' => ['label' => '--ej-color-accent', 'config' => ['css_var' => '--ej-color-accent']],
      ],
      'metric' => 'order_completed',
    ],
    'product_card_layout' => [
      'label' => 'Layout Tarjeta Producto',
      'variants' => [
        'A' => ['label' => 'vertical', 'config' => ['layout' => 'vertical']],
        'B' => ['label' => 'horizontal', 'config' => ['layout' => 'horizontal']],
      ],
      'metric' => 'add_to_cart',
    ],
    'pricing_page_variant' => [
      'label' => 'Variante Pagina Precios',
      'variants' => [
        'A' => ['label' => '3 columns', 'config' => ['style' => 'columns_3']],
        'B' => ['label' => 'interactive slider', 'config' => ['style' => 'slider']],
      ],
      'metric' => 'plan_upgrade',
    ],
    'landing_hero_copy' => [
      'label' => 'Copy Hero Landing',
      'variants' => [
        'A' => ['label' => 'Vende tus productos', 'config' => ['headline' => 'Vende tus productos']],
        'B' => ['label' => 'Conecta con tu mercado', 'config' => ['headline' => 'Conecta con tu mercado']],
      ],
      'metric' => 'registration_started',
    ],
  ];

  /**
   * Eventos de conversion validos para el vertical agroconecta.
   */
  protected const VALID_EVENTS = [
    'order_completed',
    'add_to_cart',
    'plan_upgrade',
    'registration_started',
  ];

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly StateInterface $state,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Obtiene la variante asignada a un usuario para un experimento.
   *
   * La asignacion es determinista basada en userId % 2 para garantizar
   * distribucion uniforme y persistente. Se cachea en State API.
   *
   * @param int $userId
   *   ID del usuario.
   * @param string $experimentKey
   *   Clave del experimento (checkout_cta_color, product_card_layout, etc.).
   *
   * @return array|null
   *   Array con variant (A/B), label, config, experiment_key, o NULL.
   */
  public function getVariant(int $userId, string $experimentKey): ?array {
    if (!isset(self::EXPERIMENTS[$experimentKey])) {
      $this->logger->warning('AgroConecta experiment not found: @key', ['@key' => $experimentKey]);
      return NULL;
    }

    $experiment = self::EXPERIMENTS[$experimentKey];
    $stateKey = "agroconecta_experiment_{$experimentKey}_{$userId}";

    // Check for cached assignment.
    $cached = $this->state->get($stateKey);
    if ($cached !== NULL) {
      return $cached;
    }

    // Deterministic assignment: userId modulo number of variants.
    $variantKeys = array_keys($experiment['variants']);
    $variantIndex = $userId % count($variantKeys);
    $variant = $variantKeys[$variantIndex];
    $variantConfig = $experiment['variants'][$variant];

    $result = [
      'variant' => $variant,
      'label' => $variantConfig['label'],
      'config' => $variantConfig['config'],
      'experiment_key' => $experimentKey,
    ];

    // Cache assignment.
    $this->state->set($stateKey, $result);

    $this->logger->info('AgroConecta: usuario @uid asignado a variante "@variant" del experimento "@experiment".', [
      '@uid' => $userId,
      '@variant' => $variant,
      '@experiment' => $experimentKey,
    ]);

    return $result;
  }

  /**
   * Registra un evento de conversion para un usuario y experimento.
   *
   * @param int $userId
   *   ID del usuario.
   * @param string $experimentKey
   *   Clave del experimento.
   * @param string $variant
   *   Variante asignada (A o B).
   */
  public function trackConversion(int $userId, string $experimentKey, string $variant): void {
    if (!isset(self::EXPERIMENTS[$experimentKey])) {
      $this->logger->warning('AgroConecta experiment not found for conversion: @key', ['@key' => $experimentKey]);
      return;
    }

    $experiment = self::EXPERIMENTS[$experimentKey];
    if (!isset($experiment['variants'][$variant])) {
      $this->logger->warning('AgroConecta variant "@variant" not found in experiment @key', [
        '@variant' => $variant,
        '@key' => $experimentKey,
      ]);
      return;
    }

    // Increment conversion counter.
    $conversionKey = "agroconecta_experiment_conversions_{$experimentKey}_{$variant}";
    $current = (int) $this->state->get($conversionKey, 0);
    $this->state->set($conversionKey, $current + 1);

    // Increment visitor counter (if not already counted).
    $visitorKey = "agroconecta_experiment_visitors_{$experimentKey}_{$variant}";
    $visitors = (int) $this->state->get($visitorKey, 0);
    if ($visitors === 0) {
      $this->state->set($visitorKey, 1);
    }

    $this->logger->info('AgroConecta conversion: usuario @uid, experimento "@experiment", variante "@variant", metric "@metric".', [
      '@uid' => $userId,
      '@experiment' => $experimentKey,
      '@variant' => $variant,
      '@metric' => $experiment['metric'],
    ]);
  }

  /**
   * Obtiene resultados de un experimento.
   *
   * @param string $experimentKey
   *   Clave del experimento.
   *
   * @return array
   *   Array con experiment_key, label, metric y variants con visitors,
   *   conversions y conversion_rate.
   */
  public function getResults(string $experimentKey): array {
    if (!isset(self::EXPERIMENTS[$experimentKey])) {
      $this->logger->warning('AgroConecta experiment not found for results: @key', ['@key' => $experimentKey]);
      return [];
    }

    $experiment = self::EXPERIMENTS[$experimentKey];
    $variants = [];

    foreach ($experiment['variants'] as $variantKey => $variantConfig) {
      $visitorKey = "agroconecta_experiment_visitors_{$experimentKey}_{$variantKey}";
      $conversionKey = "agroconecta_experiment_conversions_{$experimentKey}_{$variantKey}";

      $visitors = (int) $this->state->get($visitorKey, 0);
      $conversions = (int) $this->state->get($conversionKey, 0);
      $conversionRate = $visitors > 0 ? round($conversions / $visitors, 4) : 0.0;

      $variants[$variantKey] = [
        'label' => $variantConfig['label'],
        'visitors' => $visitors,
        'conversions' => $conversions,
        'conversion_rate' => $conversionRate,
        'is_control' => $variantKey === 'A',
      ];
    }

    return [
      'experiment_key' => $experimentKey,
      'label' => $experiment['label'],
      'metric' => $experiment['metric'],
      'variants' => $variants,
    ];
  }

  /**
   * Obtiene todos los experimentos definidos con sus resultados.
   *
   * @return array
   *   Array de resultados indexado por experiment_key.
   */
  public function getAllResults(): array {
    $results = [];
    foreach (array_keys(self::EXPERIMENTS) as $experimentKey) {
      $results[$experimentKey] = $this->getResults($experimentKey);
    }
    return $results;
  }

}
