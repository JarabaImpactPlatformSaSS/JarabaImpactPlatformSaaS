<?php

declare(strict_types=1);

namespace Drupal\jaraba_i18n\Plugin\QueueWorker;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\jaraba_i18n\Service\AITranslationService;
use Drupal\jaraba_i18n\Service\CanvasTranslationService;
use Drupal\jaraba_i18n\Service\TranslationTriggerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Procesa traducciones de entidades en background.
 *
 * Soporta dos tiers:
 * - Canvas (Tier 1): page_content, content_article — usa CanvasTranslationService
 * - Text (Tier 2): site_config, content_category, etc. — usa AITranslationService
 *
 * @QueueWorker(
 *   id = "jaraba_i18n_canvas_translation",
 *   title = @Translation("Canvas Translation Worker"),
 *   cron = {"time" = 120}
 * )
 */
class CanvasTranslationWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected CanvasTranslationService $canvasTranslation,
    protected AITranslationService $aiTranslation,
    protected LanguageManagerInterface $languageManager,
    protected LoggerInterface $logger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('jaraba_i18n.canvas_translation'),
      $container->get('jaraba_i18n.ai_translation'),
      $container->get('language_manager'),
      $container->get('logger.channel.jaraba_i18n'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    if (!isset($data['entity_type']) || $data['entity_type'] === ''
      || !isset($data['entity_id']) || $data['entity_id'] === 0
    ) {
      return;
    }

    $entityType = (string) $data['entity_type'];
    $entityId = (int) $data['entity_id'];
    $changedTime = (int) ($data['changed_time'] ?? 0);

    // Cargar la entidad.
    $storage = $this->entityTypeManager->getStorage($entityType);
    /** @var \Drupal\Core\Entity\ContentEntityInterface|null $entity */
    $entity = $storage->load($entityId);
    if ($entity === NULL) {
      $this->logger->warning('Translation worker: entity @type/@id not found, skipping.', [
        '@type' => $entityType,
        '@id' => $entityId,
      ]);
      return;
    }

    // Verificar que no se ha guardado de nuevo desde que se encolo.
    if ($changedTime > 0 && $entity->hasField('changed')) {
      $currentChanged = (int) $entity->get('changed')->value;
      if ($currentChanged > $changedTime) {
        $this->logger->info('Translation worker: entity @type/@id has newer save, skipping stale item.', [
          '@type' => $entityType,
          '@id' => $entityId,
        ]);
        return;
      }
    }

    // Determinar tier: desde queue item o por deteccion.
    $tier = $data['tier'] ?? $this->detectTier($entityType);

    try {
      if ($tier === 'canvas') {
        $this->canvasTranslation->syncAllTranslations($entity);
      }
      else {
        $this->translateTextEntity($entity, $entityType);
      }
    }
    catch (\Throwable $e) {
      $this->logger->error('Translation failed for @type/@id: @msg', [
        '@type' => $entityType,
        '@id' => $entityId,
        '@msg' => $e->getMessage(),
      ]);
      throw $e;
    }
  }

  /**
   * Detecta el tier de un entity type (backward-compat con items sin key 'tier').
   */
  protected function detectTier(string $entityType): string {
    return in_array($entityType, TranslationTriggerService::CANVAS_ENTITY_TYPES, TRUE)
      ? 'canvas'
      : 'text';
  }

  /**
   * Traduce campos de texto de una entidad a todos los idiomas configurados.
   *
   * Metodo generico que reemplaza el anterior translateSiteConfig().
   * Lee los campos desde TranslationTriggerService::TEXT_ENTITY_FIELDS.
   */
  protected function translateTextEntity(ContentEntityInterface $entity, string $entityType): void {
    if (!$entity->isTranslatable()) {
      return;
    }

    $fields = TranslationTriggerService::TEXT_ENTITY_FIELDS[$entityType] ?? [];
    if ($fields === []) {
      $this->logger->warning('Translation worker: no fields configured for @type, skipping.', [
        '@type' => $entityType,
      ]);
      return;
    }

    $sourceLang = $entity->getUntranslated()->language()->getId();
    $languages = $this->languageManager->getLanguages();
    $original = $entity->getUntranslated();

    foreach ($languages as $langcode => $language) {
      if ($langcode === $sourceLang) {
        continue;
      }

      // Recopilar textos a traducir.
      $texts = [];
      foreach ($fields as $fieldName) {
        if (!$original->hasField($fieldName)) {
          continue;
        }
        $value = $original->get($fieldName)->value ?? '';
        if (!empty(trim($value))) {
          $texts[$fieldName] = $value;
        }
      }

      if (empty($texts)) {
        continue;
      }

      try {
        $translated = $this->aiTranslation->translateBatch($texts, $sourceLang, $langcode);

        // Crear o obtener traduccion.
        if ($original->hasTranslation($langcode)) {
          $translation = $original->getTranslation($langcode);
        }
        else {
          $translation = $original->addTranslation($langcode);
        }

        foreach ($translated as $fieldName => $value) {
          if ($translation->hasField($fieldName)) {
            $translation->set($fieldName, $value);
          }
        }

        // Save sobre $original (NO $entity) — la traduccion se
        // anadio a $original via addTranslation/getTranslation.
        $original->setSyncing(TRUE);
        $original->save();
        $original->setSyncing(FALSE);

        $this->logger->info('@type @id translated to @lang', [
          '@type' => $entityType,
          '@id' => $entity->id(),
          '@lang' => $langcode,
        ]);
      }
      catch (\Throwable $e) {
        $this->logger->error('@type @id translation to @lang failed: @msg', [
          '@type' => $entityType,
          '@id' => $entity->id(),
          '@lang' => $langcode,
          '@msg' => $e->getMessage(),
        ]);
      }
    }
  }

}
