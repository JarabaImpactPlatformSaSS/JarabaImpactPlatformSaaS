<?php

declare(strict_types=1);

namespace Drupal\jaraba_multiregion\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Region de Tenant (TenantRegion).
 *
 * ESTRUCTURA:
 * Entidad pivote del modulo multiregion. Representa la configuracion regional
 * de un tenant, incluyendo jurisdiccion legal, moneda base, zona de datos
 * (data residency), informacion fiscal (IVA/VIES) y representante GDPR.
 * Cada tenant tiene exactamente una region asignada (indice unico en tenant_id).
 *
 * LOGICA:
 * El campo data_region determina en que zona geografica se almacenan los datos
 * del tenant (eu-west, eu-central, us-east, latam), cumpliendo con requisitos
 * de residencia de datos. El campo base_currency define la moneda principal
 * del tenant, mientras que display_currencies permite configurar monedas
 * adicionales de visualizacion. La validacion VIES se registra con fecha
 * y estado booleano.
 *
 * SINTAXIS:
 * ContentEntityBase con EntityOwnerInterface y EntityChangedInterface.
 * Indice unico en tenant_id para garantizar una region por tenant.
 * Indices adicionales en legal_jurisdiction y data_region para consultas.
 *
 * RELACIONES:
 * - TenantRegion -> Group (tenant_id): tenant propietario (1:1).
 * - TenantRegion -> User (uid): usuario creador de la configuracion.
 *
 * @ContentEntityType(
 *   id = "tenant_region",
 *   label = @Translation("Region de Tenant"),
 *   label_collection = @Translation("Regiones de Tenant"),
 *   label_singular = @Translation("region de tenant"),
 *   label_plural = @Translation("regiones de tenant"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_multiregion\ListBuilder\TenantRegionListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_multiregion\Form\TenantRegionForm",
 *       "add" = "Drupal\jaraba_multiregion\Form\TenantRegionForm",
 *       "edit" = "Drupal\jaraba_multiregion\Form\TenantRegionForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_multiregion\Access\TenantRegionAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "tenant_region",
 *   admin_permission = "administer multiregion",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "legal_jurisdiction",
 *     "uid" = "uid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/tenant-regions",
 *     "add-form" = "/admin/content/tenant-regions/add",
 *     "canonical" = "/admin/content/tenant-regions/{tenant_region}",
 *     "edit-form" = "/admin/content/tenant-regions/{tenant_region}/edit",
 *     "delete-form" = "/admin/content/tenant-regions/{tenant_region}/delete",
 *   },
 *   field_ui_base_route = "jaraba_multiregion.tenant_region.settings",
 * )
 */
class TenantRegion extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    // Campos base heredados de ContentEntityBase (id, uuid).
    $fields = parent::baseFieldDefinitions($entity_type);
    // Campos de propietario (uid) via EntityOwnerTrait.
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // =========================================================================
    // BLOQUE 1: IDENTIFICACION Y TENANT
    // Vinculo 1:1 entre tenant y su configuracion regional.
    // =========================================================================

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Tenant'))
      ->setDescription(new TranslatableMarkup('Tenant propietario de esta configuracion regional.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 2: CONFIGURACION DE MONEDA
    // Moneda base para facturacion y monedas adicionales de display.
    // =========================================================================

    $fields['base_currency'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Moneda base'))
      ->setDescription(new TranslatableMarkup('Moneda principal del tenant para facturacion y contabilidad.'))
      ->setRequired(TRUE)
      ->setDefaultValue('EUR')
      ->setSetting('allowed_values', [
        'EUR' => 'EUR',
        'USD' => 'USD',
        'GBP' => 'GBP',
        'BRL' => 'BRL',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['display_currencies'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Monedas de display'))
      ->setDescription(new TranslatableMarkup('Monedas adicionales para visualizacion de precios (JSON o lista separada por comas).'))
      ->setRequired(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 3: CONFIGURACION STRIPE Y DATA RESIDENCY
    // Pais de la cuenta Stripe, region de datos y datacenter principal.
    // =========================================================================

    $fields['stripe_account_country'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Pais cuenta Stripe'))
      ->setDescription(new TranslatableMarkup('Codigo ISO 3166-1 alpha-2 del pais de la cuenta Stripe.'))
      ->setRequired(FALSE)
      ->setSetting('max_length', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['data_region'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Data Region'))
      ->setDescription(new TranslatableMarkup('Region geografica donde se almacenan los datos del tenant.'))
      ->setRequired(TRUE)
      ->setDefaultValue('eu-west')
      ->setSetting('allowed_values', [
        'eu-west' => 'EU West',
        'eu-central' => 'EU Central',
        'us-east' => 'US East',
        'latam' => 'LATAM',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['primary_dc'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Datacenter principal'))
      ->setDescription(new TranslatableMarkup('Identificador del datacenter principal asignado al tenant.'))
      ->setRequired(FALSE)
      ->setSetting('max_length', 50)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 4: JURISDICCION LEGAL Y FISCAL
    // Jurisdiccion, numero IVA y validacion VIES.
    // =========================================================================

    $fields['legal_jurisdiction'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Jurisdiccion legal'))
      ->setDescription(new TranslatableMarkup('Codigo ISO 3166-1 alpha-2 de la jurisdiccion legal del tenant.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['vat_number'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Numero IVA'))
      ->setDescription(new TranslatableMarkup('Numero de identificacion fiscal a efectos de IVA intracomunitario.'))
      ->setRequired(FALSE)
      ->setSetting('max_length', 20)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['vies_validated'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Validado VIES'))
      ->setDescription(new TranslatableMarkup('Indica si el numero IVA ha sido validado contra el sistema VIES de la UE.'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['vies_validated_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Fecha validacion VIES'))
      ->setDescription(new TranslatableMarkup('Fecha y hora de la ultima validacion VIES exitosa.'))
      ->setRequired(FALSE)
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 5: GDPR
    // Representante GDPR designado para la jurisdiccion del tenant.
    // =========================================================================

    $fields['gdpr_representative'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Representante GDPR'))
      ->setDescription(new TranslatableMarkup('Nombre o razon social del representante GDPR designado en la UE.'))
      ->setRequired(FALSE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 6: METADATOS TEMPORALES
    // Timestamps de creacion y ultima modificacion.
    // =========================================================================

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Fecha de creacion'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Fecha de modificacion'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   *
   * Define indices de base de datos para la tabla tenant_region.
   * - tenant_id: indice unico, cada tenant tiene exactamente una region.
   * - legal_jurisdiction: indice para filtrado por jurisdiccion.
   * - data_region: indice para filtrado por zona de datos.
   */
  public static function schema(EntityTypeInterface $entity_type): array {
    $schema = parent::schema($entity_type);

    $schema['indexes']['idx_tenant_id'] = ['tenant_id'];
    $schema['unique keys']['uniq_tenant_id'] = ['tenant_id'];
    $schema['indexes']['idx_legal_jurisdiction'] = ['legal_jurisdiction'];
    $schema['indexes']['idx_data_region'] = ['data_region'];

    return $schema;
  }

}
