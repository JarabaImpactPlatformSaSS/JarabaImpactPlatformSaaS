<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_knowledge\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Legal Chunk.
 *
 * ESTRUCTURA:
 * Fragmento de texto de una norma legal, preparado para el pipeline RAG.
 * Cada chunk se almacena con su embedding ID de Qdrant para busqueda
 * por similitud semantica.
 *
 * LOGICA:
 * Los chunks se generan a partir del full_text de LegalNorm, divididos
 * por articulos/capitulos. El token_count permite controlar el tamano
 * para los limites del modelo de embedding. El embedding_id referencia
 * el punto correspondiente en la coleccion Qdrant.
 *
 * RELACIONES:
 * - LegalChunk -> LegalNorm (norm_id): norma origen
 * - LegalChunk <- RAG pipeline: consultado por similitud
 *
 * @ContentEntityType(
 *   id = "legal_chunk",
 *   label = @Translation("Legal Chunk"),
 *   label_collection = @Translation("Legal Chunks"),
 *   label_singular = @Translation("legal chunk"),
 *   label_plural = @Translation("legal chunks"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "storage_schema" = "Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema",
 *     "access" = "Drupal\jaraba_legal_knowledge\Access\LegalNormAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "legal_chunk",
 *   admin_permission = "administer legal knowledge",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/legal-chunks/{legal_chunk}",
 *     "collection" = "/admin/content/legal-chunks",
 *   },
 *   field_ui_base_route = "entity.legal_chunk.settings",
 * )
 */
class LegalChunk extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- Norm Reference ---
    $fields['norm_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Norma'))
      ->setDescription(t('Norma legal de la que proviene este fragmento.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'legal_norm')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Chunk Index ---
    $fields['chunk_index'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Indice de Chunk'))
      ->setDescription(t('Posicion ordinal del chunk dentro de la norma.'))
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', TRUE);

    // --- Section Title ---
    $fields['section_title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Titulo de Seccion'))
      ->setDescription(t('Titulo de la seccion o articulo.'))
      ->setSetting('max_length', 512)
      ->setDisplayConfigurable('view', TRUE);

    // --- Content ---
    $fields['content'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Contenido'))
      ->setDescription(t('Texto del fragmento.'))
      ->setRequired(TRUE);

    // --- Token Count ---
    $fields['token_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Cantidad de Tokens'))
      ->setDescription(t('Numero de tokens del fragmento.'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', TRUE);

    // --- Embedding ID (Qdrant point ID) ---
    $fields['embedding_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Embedding ID'))
      ->setDescription(t('Identificador del punto en Qdrant.'))
      ->setSetting('max_length', 128);

    // --- Article Number (nullable) ---
    $fields['article_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Numero de Articulo'))
      ->setDescription(t('Numero del articulo legal, si aplica.'))
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('view', TRUE);

    // --- Chapter (nullable) ---
    $fields['chapter'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Capitulo'))
      ->setDescription(t('Capitulo al que pertenece el fragmento, si aplica.'))
      ->setSetting('max_length', 256)
      ->setDisplayConfigurable('view', TRUE);

    // --- Metadatos ---
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creacion'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificacion'));

    return $fields;
  }

}
