<?php

declare(strict_types=1);

namespace Drupal\jaraba_i18n\Service;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Queue\QueueFactory;
use Psr\Log\LoggerInterface;

/**
 * Determina y encola entidades para traduccion automatica con IA.
 *
 * SSOT de que entidades y campos se traducen automaticamente.
 * Dos tiers:
 * - Canvas: entidades con canvas_data/rendered_html (GrapesJS)
 * - Text: entidades con campos de texto simple
 *
 * Consumido por:
 * - jaraba_i18n_entity_insert() / jaraba_i18n_entity_update()
 * - TranslationCatchupService (para enqueue directo)
 *
 * @see \Drupal\jaraba_i18n\Service\TranslationCatchupService
 */
class TranslationTriggerService {

  /**
   * Nombre de la cola de traduccion.
   */
  public const QUEUE_NAME = 'jaraba_i18n_canvas_translation';

  /**
   * Tier 1: Entidades con canvas_data — usan CanvasTranslationService.
   *
   * @var string[]
   */
  public const CANVAS_ENTITY_TYPES = [
    'page_content',
    'content_article',
  ];

  /**
   * Campos canvas que disparan traduccion al cambiar.
   *
   * @var string[]
   */
  public const CANVAS_TRIGGER_FIELDS = [
    'canvas_data',
    'rendered_html',
    'content_data',
  ];

  /**
   * Tier 2: Entidades de solo texto — usan AITranslationService::translateBatch.
   *
   * Mapa entity_type_id => campos traducibles verificados contra codigo fuente.
   *
   * @var array<string, string[]>
   */
  public const TEXT_ENTITY_FIELDS = [
    'site_config' => [
      'site_name',
      'site_tagline',
      'header_cta_text',
      'footer_copyright',
      'footer_col1_title',
      'footer_col2_title',
      'footer_col3_title',
      'meta_title_suffix',
    ],
    'content_category' => [
      'name',
      'slug',
      'description',
      'meta_title',
      'meta_description',
    ],
    'content_author' => [
      'display_name',
      'slug',
      'bio',
    ],
    'homepage_content' => [
      'hero_eyebrow',
      'hero_title',
      'hero_subtitle',
      'hero_cta_primary_text',
      'hero_cta_secondary_text',
      'hero_scroll_text',
      'meta_title',
      'meta_description',
    ],
    'feature_card' => [
      'title',
      'description',
    ],
    'intention_card' => [
      'title',
      'description',
    ],
    'stat_item' => [
      'label',
    ],
    'tenant_faq' => [
      'question',
      'answer',
      'question_variants',
    ],
    'tenant_policy' => [
      'title',
      'content',
      'summary',
      'version_notes',
    ],
  ];

  public function __construct(
    protected QueueFactory $queueFactory,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Devuelve todos los entity type IDs soportados (Tier 1 + Tier 2).
   *
   * @return string[]
   */
  public function getSupportedEntityTypes(): array {
    return array_merge(
      self::CANVAS_ENTITY_TYPES,
      array_keys(self::TEXT_ENTITY_FIELDS),
    );
  }

  /**
   * Determina si un entity type es canvas (Tier 1).
   */
  public function isCanvasEntity(string $entityTypeId): bool {
    return in_array($entityTypeId, self::CANVAS_ENTITY_TYPES, TRUE);
  }

  /**
   * Determina si un entity type es texto (Tier 2).
   */
  public function isTextEntity(string $entityTypeId): bool {
    return isset(self::TEXT_ENTITY_FIELDS[$entityTypeId]);
  }

  /**
   * Devuelve los campos traducibles para un entity type de texto.
   *
   * @return string[]
   */
  public function getTranslatableFields(string $entityTypeId): array {
    return self::TEXT_ENTITY_FIELDS[$entityTypeId] ?? [];
  }

  /**
   * Punto de entrada principal desde hooks entity_insert/entity_update.
   *
   * PRESAVE-RESILIENCE-001: El caller ya envuelve en try-catch.
   */
  public function handleEntityChange(ContentEntityInterface $entity, bool $isInsert): void {
    // Prevenir loops: si estamos sincronizando traducciones, no re-encolar.
    if ($entity->isSyncing()) {
      return;
    }

    // Solo el idioma original (no traducciones).
    if (!$entity->isDefaultTranslation()) {
      return;
    }

    $entityTypeId = $entity->getEntityTypeId();

    // Solo tipos soportados.
    if (!in_array($entityTypeId, $this->getSupportedEntityTypes(), TRUE)) {
      return;
    }

    if ($isInsert) {
      $this->enqueue($entity);
      return;
    }

    // Para update: verificar que hay cambios en campos traducibles.
    if ($this->hasTranslatableChanges($entity)) {
      $this->enqueue($entity);
    }
  }

  /**
   * Encola una entidad para traduccion asincrona.
   */
  public function enqueue(ContentEntityInterface $entity): void {
    $entityTypeId = $entity->getEntityTypeId();
    $tier = $this->isCanvasEntity($entityTypeId) ? 'canvas' : 'text';

    $queue = $this->queueFactory->get(self::QUEUE_NAME);
    $queue->createItem([
      'entity_type' => $entityTypeId,
      'entity_id' => (int) $entity->id(),
      'changed_time' => $entity->hasField('changed')
        ? (int) $entity->get('changed')->value
        : 0,
      'tier' => $tier,
    ]);

    $this->logger->info('Enqueued @tier translation for @type/@id', [
      '@tier' => $tier,
      '@type' => $entityTypeId,
      '@id' => $entity->id(),
    ]);
  }

  /**
   * Detecta si una entidad actualizada tiene cambios en campos traducibles.
   *
   * Requiere $entity->original disponible.
   */
  protected function hasTranslatableChanges(ContentEntityInterface $entity): bool {
    // ContentEntityBase declara $original como typed property.
    if (!$entity instanceof ContentEntityBase || !isset($entity->original)) {
      return FALSE;
    }

    $entityTypeId = $entity->getEntityTypeId();

    // Canvas entities: comprobar campos canvas.
    if ($this->isCanvasEntity($entityTypeId)) {
      foreach (self::CANVAS_TRIGGER_FIELDS as $fieldName) {
        if ($this->fieldChanged($entity, $fieldName)) {
          return TRUE;
        }
      }
      return FALSE;
    }

    // Text entities: comprobar campos configurados.
    $fields = $this->getTranslatableFields($entityTypeId);
    foreach ($fields as $fieldName) {
      if ($this->fieldChanged($entity, $fieldName)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Compara un campo entre la version actual y la original.
   */
  protected function fieldChanged(ContentEntityBase $entity, string $fieldName): bool {
    if (!$entity->hasField($fieldName) || !isset($entity->original)) {
      return FALSE;
    }

    $oldValue = $entity->original->get($fieldName)->value ?? '';
    $newValue = $entity->get($fieldName)->value ?? '';

    return $oldValue !== $newValue;
  }

}
