<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Motor de recomendaciones de venta cruzada para productos agrícolas.
 *
 * Genera sugerencias de cross-sell y upsell basadas en reglas de
 * complementariedad, compatibilidad de precio y popularidad.
 * Referencia: Doc 68 §4.3 — Sales Agent (Cross-Sell Engine).
 */
class CrossSellEngine {

  /**
   * Reglas de venta cruzada: categoría → categorías complementarias.
   *
   * Orden de prioridad descendente (primera = mayor afinidad).
   */
  private const CROSS_SELL_RULES = [
    'vino' => ['queso', 'embutido', 'conservas', 'aceitunas', 'pan'],
    'aceite' => ['pan', 'vinagre', 'especias', 'aceitunas', 'tomate'],
    'queso' => ['vino', 'mermelada', 'frutos_secos', 'pan', 'miel'],
    'jamón' => ['vino', 'pan', 'aceitunas', 'queso', 'cerveza'],
    'miel' => ['queso', 'frutos_secos', 'infusiones', 'yogur'],
    'café' => ['chocolate', 'galletas', 'azúcar', 'leche'],
    'chocolate' => ['café', 'frutos_secos', 'galletas', 'miel'],
    'conservas' => ['pan', 'aceite', 'vino', 'queso'],
    'cerveza' => ['embutido', 'queso', 'aceitunas', 'frutos_secos'],
    'infusiones' => ['miel', 'galletas', 'chocolate'],
  ];

  /**
   * Umbral de envío gratuito en euros.
   */
  private const FREE_SHIPPING_THRESHOLD = 20.0;

  /**
   * Umbral premium en euros.
   */
  private const PREMIUM_THRESHOLD = 50.0;

  /**
   * Máximo de sugerencias de cross-sell.
   */
  private const MAX_CROSS_SELL = 5;

  /**
   * Máximo de sugerencias de upsell.
   */
  private const MAX_UPSELL = 3;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $database,
    protected LoggerInterface $logger,
  ) {
  }

  /**
   * Genera sugerencias de venta cruzada.
   *
   * @param int $productId
   *   Product just added/viewed.
   * @param array $cartItems
   *   Current cart [{product_id, category, price}].
   * @param float $cartTotal
   *   Current cart total.
   * @param string $trigger
   *   When suggestion triggered: post-add|pre-checkout|threshold.
   *
   * @return array
   *   [{product_id, product_name, category, price, reason, confidence, timing}]
   */
  public function generateCrossSellSuggestions(
    int $productId,
    array $cartItems = [],
    float $cartTotal = 0.0,
    string $trigger = 'post-add',
  ): array {
    // 1. Cargar el producto que disparó la sugerencia.
    $product = $this->entityTypeManager->getStorage('product_agro')->load($productId);
    if (!$product) {
      $this->logger->warning('Cross-sell solicitado para producto inexistente: @id', [
        '@id' => $productId,
      ]);
      return [];
    }

    $triggerCategory = $this->normalizeCategory($product->get('category')->value ?? '');
    $triggerPrice = (float) ($product->get('price')->value ?? 0);

    if (empty($triggerCategory)) {
      $this->logger->info('Cross-sell: producto @id sin categoría asignada.', [
        '@id' => $productId,
      ]);
      return [];
    }

    // 2. Obtener categorías complementarias desde las reglas.
    $complementaryCategories = $this->getCrossSellCategories($triggerCategory);
    if (empty($complementaryCategories)) {
      return [];
    }

    // 3. Recopilar IDs de productos ya en el carrito para excluirlos.
    $cartProductIds = array_map(
      fn(array $item): int => (int) ($item['product_id'] ?? 0),
      $cartItems
    );
    $cartProductIds[] = $productId;
    $cartProductIds = array_filter($cartProductIds);

    // 4. Consultar productos en las categorías complementarias.
    $productStorage = $this->entityTypeManager->getStorage('product_agro');
    $candidates = [];

    foreach ($complementaryCategories as $position => $complementaryCategory) {
      $query = $productStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('category', $complementaryCategory)
        ->condition('status', 1)
        ->range(0, 10);

      if (!empty($cartProductIds)) {
        $query->condition('id', $cartProductIds, 'NOT IN');
      }

      $candidateIds = $query->execute();
      if (empty($candidateIds)) {
        continue;
      }

      $loadedProducts = $productStorage->loadMultiple($candidateIds);
      foreach ($loadedProducts as $candidateProduct) {
        $candidates[] = [
          'product' => $candidateProduct,
          'rule_position' => $position,
          'complementary_category' => $complementaryCategory,
        ];
      }
    }

    if (empty($candidates)) {
      return [];
    }

    // 5. Puntuar cada candidato.
    $scored = [];
    foreach ($candidates as $candidate) {
      $candidateProduct = $candidate['product'];
      $candidateId = (int) $candidateProduct->id();
      $candidatePrice = (float) ($candidateProduct->get('price')->value ?? 0);

      // Peso por posición en las reglas (primera = 1.0, segunda = 0.9, ...).
      $ruleWeight = max(0.1, 1.0 - ($candidate['rule_position'] * 0.1));

      // Compatibilidad de precio (distribución gaussiana).
      $priceCompat = $this->getPriceCompatibility($triggerPrice, $candidatePrice);

      // Popularidad (pedidos en últimos 30 días, normalizado).
      $popularity = $this->getPopularityScore($candidateId);

      // Confianza final: ponderación de los tres factores.
      $confidence = ($ruleWeight * 0.5) + ($priceCompat * 0.2) + ($popularity * 0.3);

      $scored[] = [
        'product_id' => $candidateId,
        'product_name' => $candidateProduct->label(),
        'category' => $candidate['complementary_category'],
        'price' => round($candidatePrice, 2),
        'confidence' => round($confidence, 4),
        'rule_weight' => $ruleWeight,
        'trigger_category' => $triggerCategory,
      ];
    }

    // 6. Ordenar por confianza descendente y limitar.
    usort($scored, fn(array $a, array $b): int => $b['confidence'] <=> $a['confidence']);
    $scored = array_slice($scored, 0, self::MAX_CROSS_SELL);

    // 7. Agregar timing y razón a cada sugerencia.
    $suggestions = [];
    foreach ($scored as $item) {
      $timing = $this->determineTiming($trigger, $cartTotal);
      $reason = $this->generateReason($item['trigger_category'], $item['category'], $item['product_name']);

      $suggestions[] = [
        'product_id' => $item['product_id'],
        'product_name' => $item['product_name'],
        'category' => $item['category'],
        'price' => $item['price'],
        'reason' => $reason,
        'confidence' => $item['confidence'],
        'timing' => $timing,
      ];
    }

    $this->logger->info('Cross-sell generado para producto @id (@cat): @n sugerencias, trigger @trigger.', [
      '@id' => $productId,
      '@cat' => $triggerCategory,
      '@n' => count($suggestions),
      '@trigger' => $trigger,
    ]);

    return $suggestions;
  }

  /**
   * Obtiene sugerencias de upsell basadas en el carrito completo.
   *
   * @param array $cartItems
   *   Cart items [{product_id, category, price, quantity}].
   * @param float $cartTotal
   *   Cart total.
   *
   * @return array
   *   [{product_id, product_name, reason, potential_value, urgency}]
   */
  public function getUpsellSuggestions(array $cartItems, float $cartTotal = 0.0): array {
    if (empty($cartItems)) {
      return [];
    }

    // 1. Analizar composición del carrito.
    $categories = [];
    $prices = [];
    $cartProductIds = [];
    $totalItems = 0;

    foreach ($cartItems as $item) {
      $category = $this->normalizeCategory($item['category'] ?? '');
      if (!empty($category)) {
        $categories[] = $category;
      }
      $prices[] = (float) ($item['price'] ?? 0);
      $cartProductIds[] = (int) ($item['product_id'] ?? 0);
      $totalItems += (int) ($item['quantity'] ?? 1);
    }

    $cartProductIds = array_filter($cartProductIds);
    $avgPrice = count($prices) > 0 ? array_sum($prices) / count($prices) : 0.0;
    $suggestions = [];

    $productStorage = $this->entityTypeManager->getStorage('product_agro');

    // 2. Carrito con solo 1 artículo: sugerir productos complementarios.
    if (count($cartItems) === 1) {
      $mainCategory = $categories[0] ?? '';
      $complementary = $this->getCrossSellCategories($mainCategory);

      if (!empty($complementary)) {
        $targetCategory = $complementary[0];
        $query = $productStorage->getQuery()
          ->accessCheck(FALSE)
          ->condition('category', $targetCategory)
          ->condition('status', 1)
          ->sort('created', 'DESC')
          ->range(0, 3);

        if (!empty($cartProductIds)) {
          $query->condition('id', $cartProductIds, 'NOT IN');
        }

        $ids = $query->execute();
        if (!empty($ids)) {
          $products = $productStorage->loadMultiple($ids);
          foreach (array_slice($products, 0, 1) as $product) {
            $productPrice = (float) ($product->get('price')->value ?? 0);
            $suggestions[] = [
              'product_id' => (int) $product->id(),
              'product_name' => $product->label(),
              'reason' => sprintf(
                'Complemento perfecto para tu %s. ¡Muchos clientes los compran juntos!',
                $mainCategory
              ),
              'potential_value' => round($productPrice, 2),
              'urgency' => 'medium',
            ];
          }
        }
      }
    }

    // 3. Carrito < 20€: sugerir productos para alcanzar envío gratuito.
    if ($cartTotal < self::FREE_SHIPPING_THRESHOLD) {
      $remaining = self::FREE_SHIPPING_THRESHOLD - $cartTotal;
      $query = $productStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 1)
        ->condition('price', $remaining * 1.5, '<=')
        ->condition('price', 1.0, '>=')
        ->sort('price', 'ASC')
        ->range(0, 5);

      if (!empty($cartProductIds)) {
        $query->condition('id', $cartProductIds, 'NOT IN');
      }

      $ids = $query->execute();
      if (!empty($ids)) {
        $products = $productStorage->loadMultiple($ids);
        // Seleccionar el producto cuyo precio se acerque más al restante.
        $bestMatch = NULL;
        $bestDiff = PHP_FLOAT_MAX;

        foreach ($products as $product) {
          $productPrice = (float) ($product->get('price')->value ?? 0);
          $diff = abs($productPrice - $remaining);
          if ($diff < $bestDiff) {
            $bestDiff = $diff;
            $bestMatch = $product;
          }
        }

        if ($bestMatch !== NULL) {
          $matchPrice = (float) ($bestMatch->get('price')->value ?? 0);
          $suggestions[] = [
            'product_id' => (int) $bestMatch->id(),
            'product_name' => $bestMatch->label(),
            'reason' => sprintf(
              'Añade este producto por %.2f€ y consigue envío gratuito (te faltan %.2f€).',
              $matchPrice,
              $remaining
            ),
            'potential_value' => round($matchPrice, 2),
            'urgency' => 'high',
          ];
        }
      }
    }

    // 4. Carrito > 50€: sugerir alternativas premium/artesanales.
    if ($cartTotal > self::PREMIUM_THRESHOLD) {
      $query = $productStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 1)
        ->condition('price', $avgPrice * 1.5, '>=')
        ->sort('price', 'DESC')
        ->range(0, 5);

      if (!empty($cartProductIds)) {
        $query->condition('id', $cartProductIds, 'NOT IN');
      }

      // Filtrar por categorías del carrito para relevancia.
      if (!empty($categories)) {
        $query->condition('category', $categories, 'IN');
      }

      $ids = $query->execute();
      if (!empty($ids)) {
        $products = $productStorage->loadMultiple($ids);
        foreach (array_slice($products, 0, 1) as $product) {
          $productPrice = (float) ($product->get('price')->value ?? 0);
          $suggestions[] = [
            'product_id' => (int) $product->id(),
            'product_name' => $product->label(),
            'reason' => sprintf(
              'Versión premium artesanal de %s. Calidad superior para completar tu selección gourmet.',
              $product->get('category')->value ?? 'producto'
            ),
            'potential_value' => round($productPrice - $avgPrice, 2),
            'urgency' => 'low',
          ];
        }
      }
    }

    // Limitar a MAX_UPSELL sugerencias.
    $suggestions = array_slice($suggestions, 0, self::MAX_UPSELL);

    if (!empty($suggestions)) {
      $this->logger->info('Upsell generado: @n sugerencias para carrito de @total€ (@items artículos).', [
        '@n' => count($suggestions),
        '@total' => round($cartTotal, 2),
        '@items' => $totalItems,
      ]);
    }

    return $suggestions;
  }

  /**
   * Obtiene las reglas de venta cruzada para una categoría.
   *
   * @param string $category
   *   Category name.
   *
   * @return array
   *   List of complementary categories.
   */
  public function getCrossSellCategories(string $category): array {
    $normalized = $this->normalizeCategory($category);

    return self::CROSS_SELL_RULES[$normalized] ?? [];
  }

  /**
   * Normaliza el nombre de una categoría.
   *
   * Convierte a minúsculas, elimina acentos, recorta espacios y aplica
   * mapeos básicos para variantes comunes.
   *
   * @param string $category
   *   Nombre de categoría sin normalizar.
   *
   * @return string
   *   Categoría normalizada.
   */
  private function normalizeCategory(string $category): string {
    $normalized = mb_strtolower(trim($category));

    // Eliminar acentos comunes del español (str_replace para UTF-8 robustez).
    $normalized = str_replace(
      ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ü', 'Ñ'],
      ['a', 'e', 'i', 'o', 'u', 'u', 'n', 'a', 'e', 'i', 'o', 'u', 'u', 'n'],
      $normalized,
    );

    // Mapeo de variantes comunes a claves de reglas.
    $aliasMap = [
      'jamon' => 'jamón',
      'jamon iberico' => 'jamón',
      'jamon serrano' => 'jamón',
      'aceite de oliva' => 'aceite',
      'aceite oliva' => 'aceite',
      'aove' => 'aceite',
      'vino tinto' => 'vino',
      'vino blanco' => 'vino',
      'vino rosado' => 'vino',
      'queso curado' => 'queso',
      'queso manchego' => 'queso',
      'queso fresco' => 'queso',
      'cerveza artesanal' => 'cerveza',
      'cerveza artesana' => 'cerveza',
      'cafe' => 'café',
      'te' => 'infusiones',
      'tisana' => 'infusiones',
      'tisanas' => 'infusiones',
      'mermeladas' => 'conservas',
      'embutidos' => 'embutido',
      'frutos secos' => 'frutos_secos',
      'chocolate artesanal' => 'chocolate',
      'chocolate artesano' => 'chocolate',
    ];

    if (isset($aliasMap[$normalized])) {
      $normalized = $aliasMap[$normalized];
    }

    // Restaurar la ñ para jamón (la regla la tiene con acento en la ó).
    // Las claves de CROSS_SELL_RULES usan la forma con acento para jamón.
    // Necesitamos que la clave final coincida con las de CROSS_SELL_RULES.
    // Las demás claves ya están sin acentos.

    return $normalized;
  }

  /**
   * Puntúa un producto candidato para cross-sell.
   *
   * @param object $product
   *   Producto candidato.
   * @param string $triggerCategory
   *   Categoría del producto disparador.
   * @param float $triggerPrice
   *   Precio del producto disparador.
   * @param array $cartProductIds
   *   IDs de productos ya en el carrito.
   *
   * @return float
   *   Puntuación combinada 0.0 - 1.0.
   */
  private function scoreProduct(object $product, string $triggerCategory, float $triggerPrice, array $cartProductIds): float {
    $productId = (int) $product->id();
    $productPrice = (float) ($product->get('price')->value ?? 0);
    $productCategory = $this->normalizeCategory($product->get('category')->value ?? '');

    // Obtener posición en las reglas de cross-sell.
    $rules = self::CROSS_SELL_RULES[$triggerCategory] ?? [];
    $position = array_search($productCategory, $rules, TRUE);
    $ruleWeight = $position !== FALSE ? max(0.1, 1.0 - ($position * 0.1)) : 0.1;

    // Compatibilidad de precio.
    $priceCompat = $this->getPriceCompatibility($triggerPrice, $productPrice);

    // Popularidad.
    $popularity = $this->getPopularityScore($productId);

    return ($ruleWeight * 0.5) + ($priceCompat * 0.2) + ($popularity * 0.3);
  }

  /**
   * Calcula la popularidad de un producto (pedidos últimos 30 días, normalizado).
   *
   * @param int $productId
   *   Product ID.
   *
   * @return float
   *   Puntuación de popularidad normalizada (0.0 - 1.0).
   */
  private function getPopularityScore(int $productId): float {
    $startTimestamp = strtotime('-30 days midnight');

    try {
      // Contar pedidos de este producto en los últimos 30 días.
      $orderItemStorage = $this->entityTypeManager->getStorage('order_item_agro');

      $productCount = (int) $orderItemStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('product_id', $productId)
        ->condition('created', $startTimestamp, '>=')
        ->count()
        ->execute();

      if ($productCount === 0) {
        return 0.0;
      }

      // Obtener el máximo de pedidos de cualquier producto para normalizar.
      $maxCount = (int) $this->database->query(
        "SELECT MAX(cnt) FROM (
          SELECT COUNT(*) AS cnt
          FROM {order_item_agro}
          WHERE created >= :start
          GROUP BY product_id
        ) AS sub",
        [':start' => $startTimestamp]
      )->fetchField();

      if ($maxCount <= 0) {
        return 0.0;
      }

      return min(1.0, $productCount / $maxCount);
    }
    catch (\Exception $e) {
      $this->logger->warning('Error calculando popularidad para producto @id: @msg', [
        '@id' => $productId,
        '@msg' => $e->getMessage(),
      ]);
      return 0.0;
    }
  }

  /**
   * Calcula la compatibilidad de precio entre dos productos (gaussiana 0-1).
   *
   * Cuanto más cercanos los precios, mayor la puntuación.
   * Sigma calibrada para que productos con ±50% de diferencia de precio
   * tengan ~0.6 de compatibilidad.
   *
   * @param float $price1
   *   Precio del primer producto.
   * @param float $price2
   *   Precio del segundo producto.
   *
   * @return float
   *   Compatibilidad de precio (0.0 - 1.0).
   */
  private function getPriceCompatibility(float $price1, float $price2): float {
    if ($price1 <= 0 || $price2 <= 0) {
      return 0.5;
    }

    // Diferencia relativa al precio medio.
    $avgPrice = ($price1 + $price2) / 2;
    $diff = abs($price1 - $price2) / $avgPrice;

    // Distribución gaussiana con sigma = 0.6 (calibrado para agro marketplace).
    $sigma = 0.6;

    return exp(-($diff ** 2) / (2 * $sigma ** 2));
  }

  /**
   * Determina el timing de la sugerencia según el trigger.
   *
   * @param string $trigger
   *   Tipo de trigger: post-add|pre-checkout|threshold.
   * @param float $cartTotal
   *   Total del carrito actual.
   *
   * @return string
   *   Timing de la sugerencia.
   */
  private function determineTiming(string $trigger, float $cartTotal): string {
    return match ($trigger) {
      'post-add' => 'immediate',
      'pre-checkout' => 'checkout',
      'threshold' => $cartTotal > 30.0 ? 'premium_upsell' : 'bundle_suggestion',
      default => 'immediate',
    };
  }

  /**
   * Genera una razón en español para la sugerencia de cross-sell.
   *
   * @param string $triggerCategory
   *   Categoría del producto disparador.
   * @param string $complementaryCategory
   *   Categoría del producto sugerido.
   * @param string $productName
   *   Nombre del producto sugerido.
   *
   * @return string
   *   Razón de la sugerencia en español.
   */
  private function generateReason(string $triggerCategory, string $complementaryCategory, string $productName): string {
    // Frases contextuales por combinación de categorías.
    $reasonTemplates = [
      'vino+queso' => 'Un buen queso es el acompañante perfecto para tu vino. ¡Maridaje clásico!',
      'vino+embutido' => 'El embutido artesanal marida de maravilla con un buen vino.',
      'aceite+pan' => 'Pan artesanal con aceite de oliva: el desayuno mediterráneo por excelencia.',
      'queso+vino' => 'Nada como un buen vino para acompañar tu selección de quesos.',
      'queso+mermelada' => 'La mermelada artesanal es el complemento ideal para tu queso.',
      'queso+miel' => 'Queso con miel: una combinación gourmet irresistible.',
      'jamón+vino' => 'Un buen vino eleva la experiencia de degustar jamón ibérico.',
      'jamón+pan' => 'Pan artesanal recién horneado para tu jamón. ¡Imprescindible!',
      'miel+queso' => 'Prueba miel artesanal sobre queso curado. ¡Te encantará!',
      'café+chocolate' => 'Chocolate artesanal y café: la combinación perfecta para la sobremesa.',
      'chocolate+café' => 'Un buen café para acompañar tu chocolate artesanal.',
      'cerveza+embutido' => 'Embutido artesanal: el mejor acompañante para una cerveza artesana.',
      'cerveza+queso' => 'Queso curado y cerveza artesana: un maridaje que sorprende.',
      'infusiones+miel' => 'Endulza tus infusiones con miel artesanal de productores locales.',
      'conservas+pan' => 'Pan artesanal para disfrutar tus conservas gourmet.',
    ];

    $key = $triggerCategory . '+' . $complementaryCategory;
    if (isset($reasonTemplates[$key])) {
      return $reasonTemplates[$key];
    }

    // Razón genérica si no hay plantilla específica.
    return sprintf(
      'Los clientes que compran %s también disfrutan de %s. ¡Prueba "%s"!',
      $triggerCategory,
      $complementaryCategory,
      $productName
    );
  }

}
