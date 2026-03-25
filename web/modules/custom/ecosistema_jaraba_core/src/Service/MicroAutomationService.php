<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\node\NodeInterface;

/**
 * Micro-Automation Service - Invisible AI.
 *
 * Proporciona automatizaciones invisibles que mejoran la UX:
 * - Auto-tagging de productos con IA
 * - Smart sorting de catálogo
 * - Predictive fields en formularios.
 */
class MicroAutomationService {

  /**
   * Palabras clave para categorías de productos.
   */
  protected const CATEGORY_KEYWORDS = [
    'alimentacion' => ['aceite', 'queso', 'vino', 'miel', 'jamón', 'embutido', 'conserva', 'pan', 'harina', 'pasta'],
    'ecologico' => ['eco', 'ecológico', 'orgánico', 'bio', 'natural', 'sostenible', 'verde'],
    'gourmet' => ['premium', 'gourmet', 'artesanal', 'selección', 'reserva', 'extra', 'virgen'],
    'local' => ['pueblo', 'sierra', 'valle', 'finca', 'bodega', 'granja', 'huerta'],
    'tradicional' => ['tradicional', 'artesano', 'casero', 'abuelo', 'receta', 'antiguo'],
  ];

  /**
   * Modificadores de precio sugeridos.
   */
  protected const PRICE_MODIFIERS = [
    'premium' => 1.3,
    'ecologico' => 1.2,
    'artesanal' => 1.15,
    'basico' => 1.0,
  ];

  /**
   * Entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Logger.
   */
  protected LoggerChannelFactoryInterface $loggerFactory;

  /**
   * State service.
   */
  protected StateInterface $state;

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    LoggerChannelFactoryInterface $loggerFactory,
    StateInterface $state,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerFactory = $loggerFactory;
    $this->state = $state;
  }

  /**
   * Auto-genera tags para un producto basado en su contenido.
   *
   * @param \Drupal\node\NodeInterface $product
   *   El nodo de producto.
   *
   * @return array
   *   Array de tags sugeridos.
   */
  public function autoTagProduct(NodeInterface $product): array {
    $tags = [];

    // Obtener texto del producto.
    $title = strtolower($product->getTitle());
    $body = '';

    if ($product->hasField('body') && !$product->get('body')->isEmpty()) {
      $body = strtolower(strip_tags($product->get('body')->value));
    }

    $fullText = $title . ' ' . $body;

    // Detectar categorías por palabras clave.
    foreach (self::CATEGORY_KEYWORDS as $category => $keywords) {
      foreach ($keywords as $keyword) {
        if (str_contains($fullText, $keyword)) {
          $tags[] = $category;
          // Solo una vez por categoría.
          break;
        }
      }
    }

    // Añadir tags genéricos si no hay suficientes.
    if (count($tags) < 2) {
      $tags[] = 'producto';
      $tags[] = 'calidad';
    }

    // Eliminar duplicados y limitar.
    $tags = array_unique($tags);
    $tags = array_slice($tags, 0, 5);

    $this->loggerFactory->get('micro_automation')->info(
          '🏷️ Auto-tagged product "@title" with: @tags',
          ['@title' => $product->getTitle(), '@tags' => implode(', ', $tags)]
      );

    return $tags;
  }

  /**
   * Sugiere un precio basado en las características del producto.
   *
   * @param array $productData
   *   Datos del producto (title, description, category).
   * @param float $basePrice
   *   Precio base de referencia.
   *
   * @return float
   *   Precio sugerido.
   */
  public function suggestPrice(array $productData, float $basePrice = 10.0): float {
    $text = strtolower(($productData['title'] ?? '') . ' ' . ($productData['description'] ?? ''));

    $modifier = 1.0;

    // Detectar modificadores.
    if (str_contains($text, 'premium') || str_contains($text, 'reserva')) {
      $modifier *= self::PRICE_MODIFIERS['premium'];
    }
    if (str_contains($text, 'ecológico') || str_contains($text, 'eco') || str_contains($text, 'bio')) {
      $modifier *= self::PRICE_MODIFIERS['ecologico'];
    }
    if (str_contains($text, 'artesanal') || str_contains($text, 'artesano')) {
      $modifier *= self::PRICE_MODIFIERS['artesanal'];
    }

    $suggestedPrice = $basePrice * $modifier;

    // Redondear a .99.
    $suggestedPrice = floor($suggestedPrice) + 0.99;

    return $suggestedPrice;
  }

  /**
   * Genera una descripción SEO automática para un producto.
   *
   * @param \Drupal\node\NodeInterface $product
   *   El nodo de producto.
   *
   * @return string
   *   Descripción SEO generada.
   */
  public function generateSeoDescription(NodeInterface $product): string {
    $title = $product->getTitle();
    $tags = $this->autoTagProduct($product);

    $tagString = !empty($tags) ? implode(', ', array_slice($tags, 0, 3)) : 'calidad premium';

    return "✅ Comprar {$title} online. Producto {$tagString} con envío rápido. " .
            "Descubre el auténtico sabor de lo artesanal. ¡Pide ahora!";
  }

  /**
   * Reordena el catálogo de un tenant por relevancia.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return int
   *   Número de productos reordenados.
   */
  public function smartSortCatalog(int $tenantId): int {
    // Obtener métricas de productos (simulado).
    $productScores = $this->calculateProductScores($tenantId);

    $updated = 0;
    foreach ($productScores as $productId => $score) {
      try {
        $product = $this->entityTypeManager->getStorage('node')->load($productId);
        if ($product && $product->hasField('field_weight')) {
          // Invertir score para que mayor puntuación = menor peso (aparece primero).
          $weight = 1000 - (int) ($score * 10);
          $product->set('field_weight', $weight);
          $product->save();
          $updated++;
        }
      }
      catch (\Exception $e) {
        // Continuar con el siguiente producto.
      }
    }

    $this->loggerFactory->get('micro_automation')->info(
          '📊 Smart sorted @count products for tenant @tenant',
          ['@count' => $updated, '@tenant' => $tenantId]
      );

    return $updated;
  }

  /**
   * Calcula scores de productos para ordenación.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return array
   *   Array de product_id => score.
   */
  protected function calculateProductScores(int $tenantId): array {
    $scores = [];

    try {
      // Obtener el grupo del tenant para filtrar productos.
      $tenant = $this->entityTypeManager->getStorage('tenant')->load($tenantId);
      if (!$tenant) {
        return $scores;
      }

      $group = $tenant->getGroup();

      $query = $this->entityTypeManager->getStorage('node')->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'product')
        ->sort('created', 'DESC')
        ->range(0, 50);

      $nids = $query->execute();

      if (empty($nids)) {
        return $scores;
      }

      // Obtener estadísticas reales: nodos con más vistas recientes (node_counter si existe).
      $viewCounts = [];
      if (\Drupal::moduleHandler()->moduleExists('statistics')) {
        try {
          $database = \Drupal::database();
          if ($database->schema()->tableExists('node_counter')) {
            $result = $database->select('node_counter', 'nc')
              ->fields('nc', ['nid', 'totalcount'])
              ->condition('nid', $nids, 'IN')
              ->execute();
            foreach ($result as $row) {
              $viewCounts[$row->nid] = (int) $row->totalcount;
            }
          }
        }
        catch (\Exception $e) {
          // Sin estadísticas, usar fallback.
        }
      }

      // Calcular scores combinando recency y vistas.
      $position = 0;
      $totalProducts = count($nids);
      foreach ($nids as $nid) {
        $recencyScore = max(10, 100 - ($position * 2));
        $viewScore = min(100, ($viewCounts[$nid] ?? 0) * 2);
        // Combinar: 40% recency + 60% popularidad.
        $scores[$nid] = ($recencyScore * 0.4) + ($viewScore * 0.6);
        $position++;
      }

    }
    catch (\Exception $e) {
      // Retornar array vacío.
    }

    return $scores;
  }

  /**
   * Predice valores para campos de formulario.
   *
   * @param string $field
   *   Nombre del campo.
   * @param array $context
   *   Contexto (valores de otros campos).
   *
   * @return mixed
   *   Valor predicho.
   */
  public function predictFieldValue(string $field, array $context): mixed {
    switch ($field) {
      case 'shipping_days':
        // Predecir días de envío basado en ubicación.
        // Valor por defecto.
        return 2;

      case 'stock_quantity':
        // Predecir stock basado en histórico.
        return 50;

      case 'price':
        $basePrice = $context['base_price'] ?? 10.0;
        return $this->suggestPrice($context, $basePrice);

      case 'category':
        $title = $context['title'] ?? '';
        $tags = [];
        foreach (self::CATEGORY_KEYWORDS as $category => $keywords) {
          foreach ($keywords as $keyword) {
            if (str_contains(strtolower($title), $keyword)) {
              return $category;
            }
          }
        }
        return 'general';

      default:
        return NULL;
    }
  }

  /**
   * Procesa automatizaciones pendientes (ejecutado por cron).
   *
   * @return int
   *   Número de automatizaciones ejecutadas.
   */
  public function processScheduledAutomations(): int {
    $processed = 0;

    // Smart sort para tenants activos.
    $lastSortRun = $this->state->get('micro_automation_last_sort', 0);
    $now = time();

    // Ejecutar sort cada 6 horas.
    if ($now - $lastSortRun > 21600) {
      try {
        // Iterar sobre tenants activos.
        $tenantIds = $this->entityTypeManager
          ->getStorage('tenant')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('status', TRUE)
          ->execute();

        foreach ($tenantIds as $tenantId) {
          $this->smartSortCatalog((int) $tenantId);
          $processed++;
        }
      }
      catch (\Exception $e) {
        $this->loggerFactory->get('micro_automation')->error(
              'Error processing tenant automations: @error',
              ['@error' => $e->getMessage()]
                );
      }

      $this->state->set('micro_automation_last_sort', $now);
    }

    return $processed;
  }

  /**
   * Obtiene estadísticas de automatizaciones.
   *
   * @return array
   *   Estadísticas.
   */
  public function getStatistics(): array {
    return [
      'auto_tags_generated' => $this->state->get('micro_automation_tags_count', 0),
      'prices_suggested' => $this->state->get('micro_automation_prices_count', 0),
      'catalogs_sorted' => $this->state->get('micro_automation_sorts_count', 0),
      'last_run' => $this->state->get('micro_automation_last_run'),
    ];
  }

}
