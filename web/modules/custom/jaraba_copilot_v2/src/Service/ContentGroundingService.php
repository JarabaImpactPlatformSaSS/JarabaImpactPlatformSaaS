<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Grounding\GroundingProviderInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de grounding en contenido Drupal para el Copiloto.
 *
 * v2: Usa GroundingProviders (tagged services) para buscar contenido
 * en TODOS los entity types del ecosistema, no solo 3.
 *
 * Forma parte del Nivel 2 (POR KEYWORD MATCH) de la cascada de busqueda IA.
 * Se ejecuta SOLO cuando hay mensaje del usuario que analizar.
 *
 * Los providers se registran via CompilerPass (GroundingProviderCompilerPass)
 * con tag 'jaraba_copilot_v2.grounding_provider'.
 *
 * Mantiene retrocompatibilidad: si no hay providers, usa busqueda legacy.
 */
class ContentGroundingService {

  /**
   * Providers registrados via CompilerPass.
   *
   * @var \Drupal\jaraba_copilot_v2\Grounding\GroundingProviderInterface[]
   */
  protected array $providers = [];

  /**
   * Constructor.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Registra un grounding provider (llamado por CompilerPass).
   */
  public function addProvider(GroundingProviderInterface $provider): void {
    $this->providers[] = $provider;
    // Ordenar por prioridad DESC.
    usort($this->providers, static function (GroundingProviderInterface $a, GroundingProviderInterface $b): int {
      return $b->getPriority() <=> $a->getPriority();
    });
  }

  /**
   * Obtiene contexto de contenido basado en el mensaje del usuario.
   *
   * v2: Usa providers si estan disponibles. Fallback a busqueda legacy.
   *
   * @param string $userMessage
   *   Mensaje del usuario para extraer keywords.
   * @param string $vertical
   *   Vertical actual o 'all' para buscar en todos.
   *
   * @return string
   *   Contexto formateado para incluir en el prompt del LLM.
   */
  public function getContentContext(string $userMessage, string $vertical = 'all'): string {
    try {
      $keywords = $this->extractKeywords($userMessage);
      if ($keywords === []) {
        return '';
      }

      // v2: Si hay providers registrados, usar el sistema nuevo.
      if ($this->providers !== []) {
        return $this->getContextFromProviders($keywords, $vertical);
      }

      // Fallback legacy: busqueda directa en 3 entity types.
      return $this->getContextLegacy($keywords, $vertical);
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error en ContentGroundingService: @error', [
        '@error' => $e->getMessage(),
      ]);
      return '';
    }
  }

  /**
   * Busqueda via GroundingProviders (v2).
   *
   * @param array<string, mixed> $keywords
   *   Keywords extraidas del mensaje.
   */
  protected function getContextFromProviders(array $keywords, string $vertical): string {
    $results = [];

    foreach ($this->providers as $provider) {
      // Filtrar por vertical si no es 'all'.
      if ($vertical !== 'all' && $provider->getVerticalKey() !== $vertical && $provider->getVerticalKey() !== 'global') {
        continue;
      }

      try {
        $providerResults = $provider->search($keywords, 3);
        foreach ($providerResults as $result) {
          $results[] = $result;
        }
      }
      catch (\Throwable $e) {
        $this->logger->warning('Grounding provider @provider failed: @msg', [
          '@provider' => $provider->getVerticalKey(),
          '@msg' => $e->getMessage(),
        ]);
      }
    }

    if ($results === []) {
      return '';
    }

    // Limitar a 8 resultados para no saturar context window.
    $results = array_slice($results, 0, 8);

    return $this->formatGroundingResults($results);
  }

  /**
   * Formatea resultados de grounding para el prompt del LLM.
   *
   * @param array<int, array<string, mixed>> $results
   *   Resultados de los providers.
   */
  protected function formatGroundingResults(array $results): string {
    $lines = ["CONTENIDO REAL DISPONIBLE EN LA PLATAFORMA:\n"];

    foreach ($results as $result) {
      $line = "- {$result['title']}";
      if (($result['type'] ?? '') !== '') {
        $line .= " ({$result['type']})";
      }
      $lines[] = $line;

      if (($result['summary'] ?? '') !== '') {
        $lines[] = "  " . mb_substr($result['summary'], 0, 200);
      }

      // Metadata destacada.
      if (isset($result['metadata']) && $result['metadata'] !== []) {
        $meta = [];
        foreach ($result['metadata'] as $key => $value) {
          if ($value !== '' && $value !== NULL) {
            $meta[] = ucfirst((string) $key) . ': ' . $value;
          }
        }
        if ($meta !== []) {
          $lines[] = "  " . implode(' | ', $meta);
        }
      }

      if (($result['url'] ?? '') !== '') {
        $lines[] = "  Enlace: {$result['url']}";
      }
      $lines[] = '';
    }

    return implode("\n", $lines);
  }

  /**
   * Busqueda legacy (3 entity types).
   *
   * Mantiene retrocompatibilidad cuando no hay providers.
   *
   * @param array<string, mixed> $keywords
   *   Keywords extraidas del mensaje.
   */
  protected function getContextLegacy(array $keywords, string $vertical): string {
    $context = '';

    switch ($vertical) {
      case 'empleabilidad':
        $context = $this->searchOffers($keywords);
        break;

      case 'emprendimiento':
        $context = $this->searchEmprendimientos($keywords);
        break;

      case 'comercio':
        $context = $this->searchProducts($keywords);
        break;

      default:
        $offers = $this->searchOffers($keywords, 5);
        $emprendimientos = $this->searchEmprendimientos($keywords, 5);
        $products = $this->searchProducts($keywords, 5);
        $context = $offers . $emprendimientos . $products;
    }

    return $context ?: '';
  }

  /**
   * Busca ofertas de empleo relevantes (legacy).
   */
  protected function searchOffers(array $keywords, int $limit = 10): string {
    try {
      $nodeStorage = $this->entityTypeManager->getStorage('node');
      $query = $nodeStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('type', 'offer')
        ->condition('status', 1)
        ->range(0, $limit)
        ->sort('created', 'DESC');

      if ($keywords !== []) {
        $orGroup = $query->orConditionGroup();
        foreach ($keywords as $keyword) {
          $orGroup->condition('title', $keyword, 'CONTAINS');
          $orGroup->condition('body.value', $keyword, 'CONTAINS');
        }
        $query->condition($orGroup);
      }

      $nids = $query->execute();
      if ($nids === []) {
        return '';
      }

      $nodes = $nodeStorage->loadMultiple($nids);
      $context = "OFERTAS DE EMPLEO DISPONIBLES:\n";

      foreach ($nodes as $node) {
        $title = $node->getTitle();
        $url = '/node/' . $node->id();
        try {
          $url = $node->toUrl('canonical', ['absolute' => FALSE])->toString();
        }
        catch (\Throwable $e) {
          // Fallback to /node/NID.
        }

        $company = '';
        if ($node->hasField('field_company') && !$node->get('field_company')->isEmpty()) {
          $company = $node->get('field_company')->value;
        }

        $location = '';
        if ($node->hasField('field_location') && !$node->get('field_location')->isEmpty()) {
          $location = $node->get('field_location')->value;
        }

        $context .= "- {$title}";
        if ($company) {
          $context .= " en {$company}";
        }
        if ($location) {
          $context .= " ({$location})";
        }
        $context .= " [Ver oferta]({$url})\n";
      }

      return $context . "\n";
    }
    catch (\Throwable $e) {
      return '';
    }
  }

  /**
   * Busca emprendimientos/ideas de negocio (legacy).
   */
  protected function searchEmprendimientos(array $keywords, int $limit = 10): string {
    try {
      $nodeStorage = $this->entityTypeManager->getStorage('node');
      $query = $nodeStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('type', 'emprendimiento')
        ->condition('status', 1)
        ->range(0, $limit)
        ->sort('created', 'DESC');

      if ($keywords !== []) {
        $orGroup = $query->orConditionGroup();
        foreach ($keywords as $keyword) {
          $orGroup->condition('title', $keyword, 'CONTAINS');
        }
        $query->condition($orGroup);
      }

      $nids = $query->execute();
      if ($nids === []) {
        return '';
      }

      $nodes = $nodeStorage->loadMultiple($nids);
      $context = "EMPRENDIMIENTOS EN LA PLATAFORMA:\n";

      foreach ($nodes as $node) {
        $title = $node->getTitle();
        $url = '/node/' . $node->id();
        try {
          $url = $node->toUrl('canonical', ['absolute' => FALSE])->toString();
        }
        catch (\Throwable $e) {
          // Fallback.
        }

        $sector = '';
        if ($node->hasField('field_sector') && !$node->get('field_sector')->isEmpty()) {
          $sector = $node->get('field_sector')->entity?->getName() ?? '';
        }

        $context .= "- {$title}";
        if ($sector) {
          $context .= " (Sector: {$sector})";
        }
        $context .= " [Ver más]({$url})\n";
      }

      return $context . "\n";
    }
    catch (\Throwable $e) {
      return '';
    }
  }

  /**
   * Busca productos del marketplace (legacy).
   */
  protected function searchProducts(array $keywords, int $limit = 10): string {
    try {
      $productStorage = NULL;
      try {
        $productStorage = $this->entityTypeManager->getStorage('commerce_product');
      }
      catch (\Throwable $e) {
        return $this->searchProductNodes($keywords, $limit);
      }

      $query = $productStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', 1)
        ->range(0, $limit)
        ->sort('created', 'DESC');

      if ($keywords !== []) {
        $orGroup = $query->orConditionGroup();
        foreach ($keywords as $keyword) {
          $orGroup->condition('title', $keyword, 'CONTAINS');
        }
        $query->condition($orGroup);
      }

      $pids = $query->execute();
      if ($pids === []) {
        return '';
      }

      $products = $productStorage->loadMultiple($pids);
      $context = "PRODUCTOS DEL MARKETPLACE:\n";

      foreach ($products as $product) {
        $title = $product->getTitle();
        $url = '/product/' . $product->id();
        try {
          $url = $product->toUrl('canonical', ['absolute' => FALSE])->toString();
        }
        catch (\Throwable $e) {
          // Fallback.
        }

        $price = '';
        $variations = $product->getVariations();
        if ($variations !== []) {
          $firstVariation = reset($variations);
          if ($firstVariation && $firstVariation->hasField('price')) {
            $priceField = $firstVariation->get('price')->first();
            if ($priceField) {
              $price = number_format((float) $priceField->number, 2) . '€';
            }
          }
        }

        $context .= "- {$title}";
        if ($price) {
          $context .= ": {$price}";
        }
        $context .= " [Ver producto]({$url})\n";
      }

      return $context . "\n";
    }
    catch (\Throwable $e) {
      return '';
    }
  }

  /**
   * Busca nodos de tipo product (fallback sin Commerce).
   */
  protected function searchProductNodes(array $keywords, int $limit = 10): string {
    try {
      $nodeStorage = $this->entityTypeManager->getStorage('node');
      $query = $nodeStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('type', 'product')
        ->condition('status', 1)
        ->range(0, $limit);

      $nids = $query->execute();
      if ($nids === []) {
        return '';
      }

      $nodes = $nodeStorage->loadMultiple($nids);
      $context = "PRODUCTOS:\n";

      foreach ($nodes as $node) {
        $context .= "- {$node->getTitle()}\n";
      }

      return $context . "\n";
    }
    catch (\Throwable $e) {
      return '';
    }
  }

  /**
   * Extrae palabras clave del mensaje del usuario.
   */
  public function extractKeywords(string $message): array {
    $stopWords = [
      'el', 'la', 'los', 'las', 'un', 'una', 'unos', 'unas',
      'de', 'del', 'al', 'a', 'en', 'con', 'por', 'para',
      'que', 'qué', 'como', 'cómo', 'donde', 'dónde',
      'quiero', 'busco', 'necesito', 'ver', 'hay', 'tiene',
      'me', 'te', 'se', 'nos', 'os', 'le', 'les',
      'y', 'o', 'pero', 'si', 'no', 'es', 'son', 'está',
    ];

    $message = mb_strtolower($message);
    $message = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $message);
    $words = preg_split('/\s+/', $message, -1, PREG_SPLIT_NO_EMPTY);

    $keywords = array_filter($words, static function ($word) use ($stopWords) {
      return mb_strlen($word) > 2 && !in_array($word, $stopWords, TRUE);
    });

    return array_values(array_unique($keywords));
  }

}
