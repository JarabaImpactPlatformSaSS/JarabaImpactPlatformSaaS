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
 * Define la entidad Legal Citation.
 *
 * ESTRUCTURA:
 * Cita legal insertada en un expediente del Buzon de Confianza. Almacena
 * el texto de cita generado en distintos formatos (formal, resumida,
 * bibliografica, nota al pie) y vincula la resolucion origen con el
 * expediente destino y el usuario que realizo la insercion.
 *
 * LOGICA:
 * Cuando el profesional pulsa "Insertar como argumento" en una resolucion,
 * se genera el texto de cita via LegalResolution::formatCitation() y se
 * almacena aqui vinculada al expediente. El campo citation_format determina
 * el estilo de la cita generada. expediente_id es un integer FK al
 * expediente del Buzon de Confianza (doc 88), ya que la entidad destino
 * puede no existir aun en el schema.
 *
 * RELACIONES:
 * - LegalCitation -> LegalResolution (resolution_id): resolucion citada.
 * - LegalCitation -> Expediente (expediente_id): expediente del Buzon de
 *   Confianza donde se inserto la cita.
 * - LegalCitation -> User (inserted_by): usuario que inserto la cita.
 *
 * @ContentEntityType(
 *   id = "legal_citation",
 *   label = @Translation("Legal Citation"),
 *   label_collection = @Translation("Legal Citations"),
 *   label_singular = @Translation("legal citation"),
 *   label_plural = @Translation("legal citations"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_legal_intelligence\ListBuilder\LegalCitationListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "storage_schema" = "Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema",
 *     "access" = "Drupal\jaraba_legal_intelligence\Access\LegalCitationAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "legal_citation",
 *   admin_permission = "administer legal intelligence",
 *   field_ui_base_route = "jaraba_legal.citation.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/legal-citations/{legal_citation}",
 *     "collection" = "/admin/content/legal-citations",
 *     "add-form" = "/admin/content/legal-citations/add",
 *     "edit-form" = "/admin/content/legal-citations/{legal_citation}/edit",
 *     "delete-form" = "/admin/content/legal-citations/{legal_citation}/delete",
 *   },
 * )
 */
class LegalCitation extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

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
    // Resolucion citada, expediente destino y usuario que inserto la cita.
    // =========================================================================

    $fields['resolution_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Resolution'))
      ->setDescription(t('Resolucion citada desde la que se genero el texto de cita.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'legal_resolution')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['expediente_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Expediente'))
      ->setDescription(t('Expediente vinculado (client_case). Migrado de integer a entity_reference en FASE A3.'))
      ->setSetting('target_type', 'client_case')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['inserted_by'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Inserted By'))
      ->setDescription(t('Usuario que inserto la cita en el expediente.'))
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 2: DATOS DE LA CITA
    // Formato y texto generado de la cita legal.
    // =========================================================================

    $fields['citation_format'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Citation Format'))
      ->setDescription(t('Formato de cita utilizado al generar el texto.'))
      ->setDefaultValue('formal')
      ->setSetting('allowed_values', [
        'formal' => 'Formal',
        'resumida' => 'Resumida',
        'bibliografica' => 'Bibliografica',
        'nota_al_pie' => 'Nota al pie',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['citation_text'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Citation Text'))
      ->setDescription(t('Texto de cita generado por LegalResolution::formatCitation(). Listo para insertar en escritos juridicos.'))
      ->setRequired(TRUE)
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
