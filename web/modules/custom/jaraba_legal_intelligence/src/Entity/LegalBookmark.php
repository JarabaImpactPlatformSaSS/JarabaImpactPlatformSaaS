<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Legal Bookmark.
 *
 * ESTRUCTURA:
 * Favorito de una resolucion guardado por un profesional. Permite a los
 * usuarios marcar resoluciones como favoritas, anadir notas personales
 * y organizarlas en carpetas/etiquetas para consulta rapida.
 *
 * LOGICA:
 * Un profesional puede guardar resoluciones como favoritos y organizarlas
 * por carpetas. La combinacion user_id + resolution_id deberia ser unica
 * (un profesional no puede guardar la misma resolucion dos veces).
 * Las notas son privadas del profesional y no se comparten con otros usuarios.
 *
 * RELACIONES:
 * - LegalBookmark -> User (user_id): profesional que guardo el favorito.
 * - LegalBookmark -> LegalResolution (resolution_id): resolucion guardada.
 *
 * @ContentEntityType(
 *   id = "legal_bookmark",
 *   label = @Translation("Legal Bookmark"),
 *   label_collection = @Translation("Legal Bookmarks"),
 *   label_singular = @Translation("legal bookmark"),
 *   label_plural = @Translation("legal bookmarks"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_legal_intelligence\ListBuilder\LegalBookmarkListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "storage_schema" = "Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema",
 *     "access" = "Drupal\jaraba_legal_intelligence\Access\LegalBookmarkAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "legal_bookmark",
 *   admin_permission = "administer legal intelligence",
 *   field_ui_base_route = "jaraba_legal.bookmark.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/legal-bookmarks/{legal_bookmark}",
 *     "collection" = "/admin/content/legal-bookmarks",
 *     "add-form" = "/admin/content/legal-bookmarks/add",
 *     "edit-form" = "/admin/content/legal-bookmarks/{legal_bookmark}/edit",
 *     "delete-form" = "/admin/content/legal-bookmarks/{legal_bookmark}/delete",
 *   },
 * )
 */
class LegalBookmark extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // =========================================================================
    // BLOQUE 1: REFERENCIAS
    // Profesional que guardo el favorito y resolucion guardada.
    // La combinacion user_id + resolution_id deberia ser unica.
    // =========================================================================

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('Profesional que guardo la resolucion como favorito.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['resolution_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Resolution'))
      ->setDescription(t('Resolucion guardada como favorito.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'legal_resolution')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 2: DATOS DEL FAVORITO
    // Notas personales y carpeta organizativa del profesional.
    // =========================================================================

    $fields['notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Notes'))
      ->setDescription(t('Notas personales del profesional sobre la resolucion.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['folder'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Folder'))
      ->setDescription(t('Carpeta o etiqueta organizativa para clasificar favoritos.'))
      ->setSetting('max_length', 128)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 3: TIMESTAMPS
    // =========================================================================

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('Timestamp de creacion del registro en el sistema.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('Timestamp de ultima modificacion.'));

    return $fields;
  }

}
