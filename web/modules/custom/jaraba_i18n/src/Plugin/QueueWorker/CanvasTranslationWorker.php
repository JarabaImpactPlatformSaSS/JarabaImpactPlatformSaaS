<?php

declare(strict_types=1);

namespace Drupal\jaraba_i18n\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\jaraba_i18n\Service\CanvasTranslationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Procesa traducciones de canvas en background.
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
      $container->get('logger.channel.jaraba_i18n'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    if (empty($data['entity_type']) || empty($data['entity_id'])) {
      return;
    }

    $entityType = $data['entity_type'];
    $entityId = $data['entity_id'];
    $changedTime = $data['changed_time'] ?? 0;

    // Cargar la entidad.
    $storage = $this->entityTypeManager->getStorage($entityType);
    $entity = $storage->load($entityId);
    if (!$entity) {
      $this->logger->warning('Canvas translation: entity @type/@id not found, skipping.', [
        '@type' => $entityType,
        '@id' => $entityId,
      ]);
      return;
    }

    // Verificar que no se ha guardado de nuevo desde que se encolo.
    // Si changed_time actual > encolado, otro save ocurrio y habra un item mas reciente.
    if ($changedTime > 0 && $entity->hasField('changed')) {
      $currentChanged = (int) $entity->get('changed')->value;
      if ($currentChanged > $changedTime) {
        $this->logger->info('Canvas translation: entity @type/@id has newer save (@current > @queued), skipping stale item.', [
          '@type' => $entityType,
          '@id' => $entityId,
          '@current' => $currentChanged,
          '@queued' => $changedTime,
        ]);
        return;
      }
    }

    // Traducir a todos los idiomas.
    try {
      if ($entityType === 'site_config') {
        $this->translateSiteConfig($entity);
      }
      else {
        $this->canvasTranslation->syncAllTranslations($entity);
      }
    }
    catch (\Throwable $e) {
      $this->logger->error('Canvas translation failed for @type/@id: @msg', [
        '@type' => $entityType,
        '@id' => $entityId,
        '@msg' => $e->getMessage(),
      ]);
      throw $e;
    }
  }

  /**
   * Traduce campos texto de SiteConfig a todos los idiomas.
   */
  private function translateSiteConfig($entity): void {
    if (!$entity->isTranslatable()) {
      return;
    }

    $sourceLang = $entity->getUntranslated()->language()->getId();
    $languages = \Drupal::languageManager()->getLanguages();
    $translatableFields = [
      'site_name', 'site_tagline', 'header_cta_text', 'footer_copyright',
      'footer_col1_title', 'footer_col2_title', 'footer_col3_title', 'meta_title_suffix',
    ];

    /** @var \Drupal\jaraba_i18n\Service\AITranslationService|null $aiTranslation */
    $aiTranslation = \Drupal::hasService('jaraba_i18n.ai_translation')
      ? \Drupal::service('jaraba_i18n.ai_translation')
      : NULL;

    if (!$aiTranslation) {
      $this->logger->warning('AITranslationService not available for SiteConfig translation.');
      return;
    }

    $original = $entity->getUntranslated();

    foreach ($languages as $langcode => $language) {
      if ($langcode === $sourceLang) {
        continue;
      }

      // Collect texts to translate.
      $texts = [];
      foreach ($translatableFields as $fieldName) {
        $value = $original->get($fieldName)->value ?? '';
        if (!empty(trim($value))) {
          $texts[$fieldName] = $value;
        }
      }

      if (empty($texts)) {
        continue;
      }

      try {
        $translated = $aiTranslation->translateBatch($texts, $sourceLang, $langcode);

        // Create or get translation.
        if ($original->hasTranslation($langcode)) {
          $translation = $original->getTranslation($langcode);
        }
        else {
          $translation = $original->addTranslation($langcode);
        }

        foreach ($translated as $fieldName => $value) {
          $translation->set($fieldName, $value);
        }

        $entity->setSyncing(TRUE);
        $entity->save();
        $entity->setSyncing(FALSE);

        $this->logger->info('SiteConfig @id translated to @lang', [
          '@id' => $entity->id(),
          '@lang' => $langcode,
        ]);
      }
      catch (\Throwable $e) {
        $this->logger->error('SiteConfig translation to @lang failed: @msg', [
          '@lang' => $langcode,
          '@msg' => $e->getMessage(),
        ]);
      }
    }
  }

}
