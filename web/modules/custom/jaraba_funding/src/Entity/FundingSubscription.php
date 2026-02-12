<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Funding Subscription (Suscripcion a Alertas).
 *
 * ESTRUCTURA:
 * Entidad que almacena perfiles de suscripcion de usuarios para recibir
 * alertas de convocatorias que coincidan con sus criterios. Incluye
 * filtros por region, sector, tipo de beneficiario e importes.
 *
 * LOGICA:
 * Cada suscripcion define un perfil de matching que se compara contra
 * las convocatorias nuevas. El campo profile_embedding_id almacena
 * el embedding semantico de la descripcion de la empresa para matching
 * avanzado. El campo is_active permite pausar suscripciones.
 *
 * RELACIONES:
 * - FundingSubscription -> Tenant (tenant_id): tenant propietario
 * - FundingSubscription -> User (user_id): usuario suscriptor
 * - FundingSubscription <- FundingMatch (subscription_id): matches generados
 * - FundingSubscription <- FundingAlert (subscription_id): alertas enviadas
 *
 * @ContentEntityType(
 *   id = "funding_subscription",
 *   label = @Translation("Funding Subscription"),
 *   label_collection = @Translation("Funding Subscriptions"),
 *   label_singular = @Translation("funding subscription"),
 *   label_plural = @Translation("funding subscriptions"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_funding\ListBuilder\FundingSubscriptionListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "storage_schema" = "Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema",
 *     "access" = "Drupal\jaraba_funding\Access\FundingCallAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "funding_subscription",
 *   admin_permission = "administer jaraba funding",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/funding-subscriptions/{funding_subscription}",
 *     "collection" = "/admin/content/funding-subscriptions",
 *     "add-form" = "/admin/content/funding-subscriptions/add",
 *     "edit-form" = "/admin/content/funding-subscriptions/{funding_subscription}/edit",
 *     "delete-form" = "/admin/content/funding-subscriptions/{funding_subscription}/delete",
 *   },
 * )
 */
class FundingSubscription extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // --- Tenant ---
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant propietario de este registro.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- User ---
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Usuario'))
      ->setDescription(t('Usuario suscriptor.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Label ---
    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Etiqueta'))
      ->setDescription(t('Nombre descriptivo de la suscripcion.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Regions (JSON array) ---
    $fields['regions'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Regiones'))
      ->setDescription(t('JSON array de regiones de interes.'));

    // --- Sectors (JSON array) ---
    $fields['sectors'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Sectores'))
      ->setDescription(t('JSON array de sectores de interes.'));

    // --- Beneficiary Type ---
    $fields['beneficiary_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tipo de Beneficiario'))
      ->setDescription(t('Tipo de beneficiario: autonomo, pyme, micropyme, etc.'))
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Min Amount ---
    $fields['min_amount'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Importe Minimo'))
      ->setDescription(t('Importe minimo de interes.'))
      ->setSetting('precision', 14)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Max Amount (nullable) ---
    $fields['max_amount'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Importe Maximo'))
      ->setDescription(t('Importe maximo de interes. Sin filtro si NULL.'))
      ->setSetting('precision', 14)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Employee Count ---
    $fields['employee_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Numero de Empleados'))
      ->setDescription(t('Numero de empleados de la empresa.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Annual Revenue ---
    $fields['annual_revenue'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Facturacion Anual'))
      ->setDescription(t('Facturacion anual de la empresa.'))
      ->setSetting('precision', 14)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Company Description ---
    $fields['company_description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Descripcion de Empresa'))
      ->setDescription(t('Descripcion de la empresa para matching semantico.'));

    // --- Profile Embedding ID ---
    $fields['profile_embedding_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Profile Embedding ID'))
      ->setDescription(t('Qdrant point ID del perfil de la empresa.'))
      ->setSetting('max_length', 128)
      ->setDisplayConfigurable('view', TRUE);

    // --- Alert Channel ---
    $fields['alert_channel'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Canal de Alerta'))
      ->setDescription(t('Canal por el que recibir alertas.'))
      ->setRequired(TRUE)
      ->setDefaultValue('both')
      ->setSetting('allowed_values', [
        'email' => 'Email',
        'platform' => 'Plataforma',
        'both' => 'Ambos',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Alert Frequency ---
    $fields['alert_frequency'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Frecuencia de Alerta'))
      ->setDescription(t('Frecuencia de envio de alertas.'))
      ->setRequired(TRUE)
      ->setDefaultValue('daily')
      ->setSetting('allowed_values', [
        'immediate' => 'Inmediata',
        'daily' => 'Diaria',
        'weekly' => 'Semanal',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Is Active ---
    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Activa'))
      ->setDescription(t('Indica si la suscripcion esta activa.'))
      ->setDefaultValue(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Metadatos ---
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creacion'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificacion'));

    return $fields;
  }

}
