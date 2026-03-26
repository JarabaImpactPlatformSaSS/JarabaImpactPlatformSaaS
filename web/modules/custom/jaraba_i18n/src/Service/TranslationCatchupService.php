<?php

declare(strict_types=1);

namespace Drupal\jaraba_i18n\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * Detecta periodicamente entidades sin traduccion y las encola.
 *
 * Patron: identico a ProactiveInsightsService::runCron() de jaraba_ai_agents.
 * Ejecutado desde jaraba_i18n_cron() cada 6 horas.
 *
 * Escanea todas las entidades Tier 1+2 definidas en TranslationTriggerService
 * y encola las que no tienen traduccion para todos los idiomas configurados.
 */
class TranslationCatchupService {

  /**
   * Intervalo entre ejecuciones: 6 horas.
   */
  protected const RUN_INTERVAL = 21600;

  /**
   * Clave de estado para rastrear la ultima ejecucion.
   */
  protected const STATE_KEY = 'jaraba_i18n.catchup_last_run';

  /**
   * Maximo de items por ejecucion para no saturar la cola.
   */
  protected const MAX_ITEMS_PER_RUN = 50;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LanguageManagerInterface $languageManager,
    protected QueueFactory $queueFactory,
    protected StateInterface $state,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Ejecutado desde hook_cron. Respeta RUN_INTERVAL.
   */
  public function runCron(): void {
    $lastRun = (int) $this->state->get(self::STATE_KEY, 0);
    $now = time();

    if (($now - $lastRun) < self::RUN_INTERVAL) {
      return;
    }

    $this->state->set(self::STATE_KEY, $now);

    $missing = $this->findEntitiesMissingTranslations();

    if ($missing === []) {
      $this->logger->info('Translation catchup: all entities have translations.');
      return;
    }

    $enqueued = $this->enqueueMissing($missing);

    $this->logger->info('Translation catchup: enqueued @count entities for translation.', [
      '@count' => $enqueued,
    ]);
  }

  /**
   * Escanea entidades Tier 1+2 sin traduccion en algun idioma.
   *
   * Usa entity queries (count) para eficiencia — NO carga entidades completas.
   *
   * @return array<int, array{entity_type: string, entity_id: int, tier: string}>
   *   Lista de entidades sin traduccion.
   */
  public function findEntitiesMissingTranslations(): array {
    $targetLangcodes = $this->getTargetLanguages();

    if ($targetLangcodes === []) {
      return [];
    }

    $missing = [];
    $supportedTypes = array_merge(
      TranslationTriggerService::CANVAS_ENTITY_TYPES,
      array_keys(TranslationTriggerService::TEXT_ENTITY_FIELDS),
    );

    foreach ($supportedTypes as $entityTypeId) {
      try {
        $storage = $this->entityTypeManager->getStorage($entityTypeId);
      }
      catch (\Throwable) {
        // Modulo que define este entity type no esta instalado.
        continue;
      }

      // Obtener la definicion para verificar que es translatable.
      $entityType = $this->entityTypeManager->getDefinition($entityTypeId, FALSE);
      if ($entityType === NULL || !$entityType->isTranslatable()) {
        continue;
      }

      // Cross-tenant by design: el catch-up escanea TODAS las entidades
      // de todos los tenants. AITranslationService resuelve el brand voice
      // per-tenant en runtime. No filtramos por tenant_id porque el cron
      // se ejecuta como tarea administrativa global.
      $defaultLangcode = $this->languageManager->getDefaultLanguage()->getId();
      $allIds = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('langcode', $defaultLangcode)
        ->execute();

      if ($allIds === []) {
        continue;
      }

      $isCanvas = in_array($entityTypeId, TranslationTriggerService::CANVAS_ENTITY_TYPES, TRUE);
      $tier = $isCanvas ? 'canvas' : 'text';

      foreach ($targetLangcodes as $langcode) {
        // Buscar IDs que YA tienen traduccion en este idioma.
        $translatedIds = $storage->getQuery()
          ->accessCheck(FALSE)
          ->condition('langcode', $langcode)
          ->execute();

        // IDs sin traduccion = diferencia.
        $missingIds = array_diff($allIds, $translatedIds);

        foreach ($missingIds as $entityId) {
          $missing[] = [
            'entity_type' => $entityTypeId,
            'entity_id' => (int) $entityId,
            'tier' => $tier,
          ];
        }
      }
    }

    return $missing;
  }

  /**
   * Encola entidades descubiertas, con limite maximo.
   *
   * @param array<int, array{entity_type: string, entity_id: int, tier: string}> $missing
   *   Lista de entidades sin traduccion.
   *
   * @return int
   *   Numero de items encolados.
   */
  protected function enqueueMissing(array $missing): int {
    $queue = $this->queueFactory->get(TranslationTriggerService::QUEUE_NAME);
    $count = 0;

    // Deduplicar por entidad: una entidad puede aparecer multiples veces
    // (una por cada idioma faltante). El worker procesara 1 item y traducira
    // a TODOS los idiomas configurados en esa unica ejecucion.
    $seen = [];

    foreach ($missing as $item) {
      $key = $item['entity_type'] . ':' . $item['entity_id'];
      if (isset($seen[$key])) {
        continue;
      }
      $seen[$key] = TRUE;

      if ($count >= self::MAX_ITEMS_PER_RUN) {
        $this->logger->info('Translation catchup: cap reached at @max items, @remaining pending.', [
          '@max' => self::MAX_ITEMS_PER_RUN,
          '@remaining' => count($missing) - $count,
        ]);
        break;
      }

      $queue->createItem([
        'entity_type' => $item['entity_type'],
        'entity_id' => $item['entity_id'],
        'changed_time' => 0,
        'tier' => $item['tier'],
      ]);
      $count++;
    }

    return $count;
  }

  /**
   * Devuelve los langcodes objetivo (todos excepto el por defecto).
   *
   * @return string[]
   */
  protected function getTargetLanguages(): array {
    $defaultLangcode = $this->languageManager->getDefaultLanguage()->getId();
    $languages = $this->languageManager->getLanguages(LanguageInterface::STATE_CONFIGURABLE);

    $targets = [];
    foreach ($languages as $langcode => $language) {
      if ($langcode !== $defaultLangcode) {
        $targets[] = $langcode;
      }
    }

    return $targets;
  }

}
