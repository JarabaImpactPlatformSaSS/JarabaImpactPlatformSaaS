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
 * Define la entidad Legal Source.
 *
 * ESTRUCTURA:
 * Entidad que representa cada fuente de datos juridicos configurada en el
 * sistema. Cada fuente tiene un machine_name unico (cendoj, boe, dgt, teac,
 * eurlex, curia, hudoc, edpb) y una URL base desde la que el spider extrae
 * resoluciones. Los campos de monitorizacion (error_count, last_error,
 * last_sync_at) permiten supervisar la salud de cada fuente desde el admin.
 *
 * LOGICA:
 * El campo is_active controla si el spider se ejecuta en cron. frequency
 * determina la cadencia de ingesta (daily, weekly, monthly). error_count y
 * last_error permiten monitorizar la salud de cada fuente. priority determina
 * el orden de ejecucion de los spiders (menor = mas prioritario). El campo
 * total_documents se actualiza automaticamente tras cada sincronizacion exitosa.
 *
 * RELACIONES:
 * - LegalSource -> spiders (spider_class): clase PHP del spider asociado.
 * - LegalSource <- LegalResolution (source_id conceptual): resoluciones
 *   importadas desde esta fuente.
 *
 * @ContentEntityType(
 *   id = "legal_source",
 *   label = @Translation("Legal Source"),
 *   label_collection = @Translation("Legal Sources"),
 *   label_singular = @Translation("legal source"),
 *   label_plural = @Translation("legal sources"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_legal_intelligence\ListBuilder\LegalSourceListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "storage_schema" = "Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema",
 *     "access" = "Drupal\jaraba_legal_intelligence\Access\LegalSourceAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "legal_source",
 *   admin_permission = "administer legal intelligence",
 *   field_ui_base_route = "jaraba_legal.source.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/legal-sources/{legal_source}",
 *     "collection" = "/admin/content/legal-sources",
 *     "add-form" = "/admin/content/legal-sources/add",
 *     "edit-form" = "/admin/content/legal-sources/{legal_source}/edit",
 *     "delete-form" = "/admin/content/legal-sources/{legal_source}/delete",
 *   },
 * )
 */
class LegalSource extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // =========================================================================
    // BLOQUE 1: IDENTIFICACION
    // Campos que identifican la fuente de forma unica en el sistema.
    // machine_name es la clave de negocio; name es la etiqueta visible.
    // =========================================================================

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('Nombre visible de la fuente de datos juridicos.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['machine_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Machine Name'))
      ->setDescription(t('ID maquina unico de la fuente: cendoj, boe, dgt, teac, eurlex, curia, hudoc, edpb.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 64)
      ->addConstraint('UniqueField')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 2: CONFIGURACION DEL SPIDER
    // URL base, clase PHP del spider y cadencia de ejecucion.
    // =========================================================================

    $fields['base_url'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Base URL'))
      ->setDescription(t('URL base de la fuente de datos (ej: https://www.poderjudicial.es/search/).'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['spider_class'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Spider Class'))
      ->setDescription(t('Clase PHP FQCN del spider asociado (ej: Drupal\jaraba_legal_intelligence\Spider\CendojSpider).'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['frequency'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Sync Frequency'))
      ->setDescription(t('Cadencia de ejecucion del spider en cron.'))
      ->setRequired(TRUE)
      ->setDefaultValue('daily')
      ->setSetting('allowed_values', [
        'daily' => 'Diaria',
        'weekly' => 'Semanal',
        'monthly' => 'Mensual',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDescription(t('Si la fuente esta habilitada. Solo las fuentes activas se ejecutan en cron.'))
      ->setDefaultValue(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['priority'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Priority'))
      ->setDescription(t('Prioridad de ingesta (menor = mas prioritario). Determina el orden de ejecucion de los spiders.'))
      ->setDefaultValue(10)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 3: MONITORIZACION
    // Campos que permiten supervisar la salud y el rendimiento de cada fuente.
    // =========================================================================

    $fields['last_sync_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Last Sync'))
      ->setDescription(t('Timestamp de la ultima sincronizacion exitosa.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['total_documents'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Total Documents'))
      ->setDescription(t('Total de resoluciones importadas desde esta fuente.'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', TRUE);

    $fields['error_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Error Count'))
      ->setDescription(t('Errores acumulados durante las sincronizaciones.'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', TRUE);

    $fields['last_error'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Last Error'))
      ->setDescription(t('Detalle del ultimo error ocurrido durante la sincronizacion.'))
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 4: TIMESTAMPS
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
