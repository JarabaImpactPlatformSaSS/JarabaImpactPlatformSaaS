<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de experimentacion A/B para el vertical ComercioConecta.
 *
 * Plan Elevacion ComercioConecta Clase Mundial v1 — Fase 17 (A/B Testing)
 *
 * PROPOSITO:
 * Gestiona experimentos A/B especificos para el vertical comercioconecta,
 * permitiendo probar variantes de flujos clave: checkout CTA, product cards,
 * pricing page, landing hero copy y flash offer urgency.
 *
 * LOGICA:
 * - getVariant(): obtiene la variante asignada a un usuario para un experimento.
 * - trackConversion(): registra eventos de conversion del vertical.
 * - getResults(): obtiene metricas de un experimento por variante.
 *
 * 5 experimentos iniciales:
 * - checkout_cta_color: A (--ej-color-comercio) / B (--ej-color-accent)
 *   metric: order_completed
 * - product_card_layout: A (grid) / B (list)
 *   metric: add_to_cart
 * - pricing_page_variant: A (3 columns) / B (interactive slider)
 *   metric: plan_upgrade
 * - landing_hero_copy: A ("Tu barrio, tu comercio") / B ("Compra local, vive mejor")
 *   metric: registration_started
 * - flash_offer_urgency: A (timer countdown) / B (badge limited)
 *   metric: offer_claimed
 *
 * @see \Drupal\ecosistema_jaraba_core\Service\AgroConectaExperimentService
 * @see \Drupal\ecosistema_jaraba_core\Service\EmprendimientoExperimentService
 */
class ComercioConectaExperimentService {

  /**
   * Tipo de experimento para comercioconecta.
   */
  protected const EXPERIMENT_TYPE = 'comercioconecta';

  /**
   * Definicion de experimentos con variantes y metricas.
   */
  protected const EXPERIMENTS = [
    'checkout_cta_color' => [
      'label' => 'Color CTA Checkout',
      'variants' => [
        'A' => ['label' => '--ej-color-comercio', 'config' => ['css_var' => '--ej-color-comercio']],
        'B' => ['label' => '--ej-color-accent', 'config' => ['css_var' => '--ej-color-accent']],
      ],
      'metric' => 'order_completed',
    ],
    'product_card_layout' => [
      'label' => 'Layout Tarjeta Producto',
      'variants' => [
        'A' => ['label' => 'grid', 'config' => ['layout' => 'grid']],
        'B' => ['label' => 'list', 'config' => ['layout' => 'list']],
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
        'A' => ['label' => 'Tu barrio, tu comercio', 'config' => ['headline' => 'Tu barrio, tu comercio']],
        'B' => ['label' => 'Compra local, vive mejor', 'config' => ['headline' => 'Compra local, vive mejor']],
      ],
      'metric' => 'registration_started',
    ],
    'flash_offer_urgency' => [
      'label' => 'Urgencia Oferta Flash',
      'variants' => [
        'A' => ['label' => 'timer countdown', 'config' => ['urgency_type' => 'timer']],
        'B' => ['label' => 'badge limited', 'config' => ['urgency_type' => 'badge']],
      ],
      'metric' => 'offer_claimed',
    ],
  ];

  /**
   * Eventos de conversion validos para el vertical comercioconecta.
   */
  protected const VALID_EVENTS = [
    'order_completed',
    'add_to_cart',
    'plan_upgrade',
    'registration_started',
    'offer_claimed',
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
      $this->logger->warning('ComercioConecta experiment not found: @key', ['@key' => $experimentKey]);
      return NULL;
    }

    $experiment = self::EXPERIMENTS[$experimentKey];
    $stateKey = "comercioconecta_experiment_{$experimentKey}_{$userId}";

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

    $this->logger->info('ComercioConecta: usuario @uid asignado a variante "@variant" del experimento "@experiment".', [
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
      $this->logger->warning('ComercioConecta experiment not found for conversion: @key', ['@key' => $experimentKey]);
      return;
    }

    $experiment = self::EXPERIMENTS[$experimentKey];
    if (!isset($experiment['variants'][$variant])) {
      $this->logger->warning('ComercioConecta variant "@variant" not found in experiment @key', [
        '@variant' => $variant,
        '@key' => $experimentKey,
      ]);
      return;
    }

    // Increment conversion counter.
    $conversionKey = "comercioconecta_experiment_conversions_{$experimentKey}_{$variant}";
    $current = (int) $this->state->get($conversionKey, 0);
    $this->state->set($conversionKey, $current + 1);

    // Increment visitor counter (if not already counted).
    $visitorKey = "comercioconecta_experiment_visitors_{$experimentKey}_{$variant}";
    $visitors = (int) $this->state->get($visitorKey, 0);
    if ($visitors === 0) {
      $this->state->set($visitorKey, 1);
    }

    $this->logger->info('ComercioConecta conversion: usuario @uid, experimento "@experiment", variante "@variant", metric "@metric".', [
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
      $this->logger->warning('ComercioConecta experiment not found for results: @key', ['@key' => $experimentKey]);
      return [];
    }

    $experiment = self::EXPERIMENTS[$experimentKey];
    $variants = [];

    foreach ($experiment['variants'] as $variantKey => $variantConfig) {
      $visitorKey = "comercioconecta_experiment_visitors_{$experimentKey}_{$variantKey}";
      $conversionKey = "comercioconecta_experiment_conversions_{$experimentKey}_{$variantKey}";

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
