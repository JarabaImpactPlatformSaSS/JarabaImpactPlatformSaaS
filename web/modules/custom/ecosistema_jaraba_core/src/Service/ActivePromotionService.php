<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio centralizado de conciencia de promociones activas.
 *
 * Resuelve "que promociones/programas estan activos ahora" para
 * cualquier componente del SaaS: copilot IA, templates Twig, emails, etc.
 *
 * Forma parte del Nivel 1 (SIEMPRE) de la cascada de busqueda IA:
 * datos cacheables con coste ~0, inyectados en cada system prompt.
 *
 * Cache: tag 'promotion_config_list', max-age 300s (5 min).
 */
class ActivePromotionService implements ActivePromotionServiceInterface {

  /**
   * Cache ID para las promociones activas.
   */
  private const CACHE_CID = 'active_promotions_context';

  /**
   * Max-age del cache en segundos (5 minutos).
   */
  private const CACHE_MAX_AGE = 300;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected CacheBackendInterface $cache,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getActivePromotions(): array {
    try {
      $storage = $this->entityTypeManager->getStorage('promotion_config');
    }
    catch (\Throwable $e) {
      $this->logger->warning('PromotionConfig entity type not available: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }

    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', TRUE)
      ->sort('priority', 'DESC')
      ->execute();

    if ($ids === []) {
      return [];
    }

    /** @var \Drupal\ecosistema_jaraba_core\Entity\PromotionConfigInterface[] $entities */
    $entities = $storage->loadMultiple($ids);

    $promotions = [];
    foreach ($entities as $entity) {
      if (!$entity->isCurrentlyActive()) {
        continue;
      }

      $promotions[] = [
        'id' => $entity->id(),
        'title' => $entity->label(),
        'description' => $entity->getDescription(),
        'vertical' => $entity->getVertical(),
        'type' => $entity->getType(),
        'highlight_values' => $entity->getHighlightValues(),
        'cta_url' => $entity->getCtaUrl(),
        'cta_label' => $entity->getCtaLabel(),
        'secondary_cta_url' => $entity->getSecondaryCtaUrl(),
        'secondary_cta_label' => $entity->getSecondaryCtaLabel(),
        'priority' => $entity->getPriority(),
        'copilot_instruction' => $entity->getCopilotInstruction(),
        'expires' => $entity->getDateEnd() !== '' ? $entity->getDateEnd() : NULL,
      ];
    }

    return $promotions;
  }

  /**
   * {@inheritdoc}
   *
   * @return array<int, array<string, mixed>>
   */
  public function getActivePromotionsByVertical(string $verticalKey): array {
    $all = $this->getActivePromotions();
    return array_values(array_filter($all, static function (array $promo) use ($verticalKey): bool {
      return $promo['vertical'] === $verticalKey || $promo['vertical'] === 'global';
    }));
  }

  /**
   * {@inheritdoc}
   */
  public function buildPromotionContextForCopilot(): string {
    // Intentar cache primero.
    $cached = $this->cache->get(self::CACHE_CID);
    if ($cached !== FALSE) {
      return (string) $cached->data;
    }

    $promotions = $this->getActivePromotions();
    if ($promotions === []) {
      $this->cache->set(self::CACHE_CID, '', $this->cacheExpireTimestamp(), ['promotion_config_list']);
      return '';
    }

    $lines = ["PROMOCIONES Y PROGRAMAS ACTIVOS EN ESTE MOMENTO:\n"];
    $i = 1;

    foreach ($promotions as $promo) {
      $lines[] = "{$i}. " . mb_strtoupper($promo['title']);

      // Datos destacados.
      if ($promo['highlight_values'] !== []) {
        $highlights = [];
        foreach ($promo['highlight_values'] as $key => $value) {
          $highlights[] = ucfirst($key) . ': ' . $value;
        }
        $lines[] = '   - ' . implode(' | ', $highlights);
      }

      // Descripcion breve.
      if ($promo['description'] !== '') {
        $desc = mb_substr($promo['description'], 0, 250);
        $lines[] = '   - ' . $desc;
      }

      // CTAs.
      if ($promo['cta_url'] !== '' && $promo['cta_label'] !== '') {
        $ctas = $promo['cta_label'] . ': ' . $promo['cta_url'];
        if ($promo['secondary_cta_url'] !== '' && $promo['secondary_cta_label'] !== '') {
          $ctas .= ' | ' . $promo['secondary_cta_label'] . ': ' . $promo['secondary_cta_url'];
        }
        $lines[] = '   - ' . $ctas;
      }

      // Instruccion especial para el copilot.
      if ($promo['copilot_instruction'] !== '') {
        $lines[] = '   INSTRUCCIÓN: ' . $promo['copilot_instruction'];
      }

      $lines[] = '';
      $i++;
    }

    $context = implode("\n", $lines);

    $this->cache->set(self::CACHE_CID, $context, $this->cacheExpireTimestamp(), ['promotion_config_list']);

    return $context;
  }

  /**
   * Calcula el timestamp de expiracion del cache.
   */
  private function cacheExpireTimestamp(): int {
    return \Drupal::time()->getRequestTime() + self::CACHE_MAX_AGE;
  }

}
