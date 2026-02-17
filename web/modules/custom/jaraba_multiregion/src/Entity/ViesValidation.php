<?php

declare(strict_types=1);

namespace Drupal\jaraba_multiregion\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Define la entidad Validacion VIES (ViesValidation).
 *
 * ESTRUCTURA:
 * Entidad que registra el resultado de una consulta al sistema VIES (VAT
 * Information Exchange System) de la Comision Europea. Almacena el numero
 * IVA consultado, el resultado de validacion, los datos de la empresa
 * devueltos por VIES y el identificador de la consulta. Vinculada a un
 * tenant pero sin propietario usuario directo.
 *
 * LOGICA:
 * Cada validacion VIES es un registro inmutable que documenta una consulta
 * concreta al servicio VIES. El campo is_valid indica si el numero IVA
 * fue reconocido como valido por la autoridad fiscal del pais. Los campos
 * company_name y company_address se rellenan con los datos devueltos por
 * VIES cuando la validacion es exitosa. El request_identifier permite
 * trazabilidad ante la administracion tributaria.
 *
 * SINTAXIS:
 * ContentEntityBase sin EntityOwnerInterface ni EntityChangedInterface.
 * Entidad de registro inmutable vinculada a tenant. Indices en tenant_id,
 * vat_number y validated_at para consultas de historial de validaciones.
 *
 * RELACIONES:
 * - ViesValidation -> Group (tenant_id): tenant que solicito la validacion.
 *
 * @ContentEntityType(
 *   id = "vies_validation",
 *   label = @Translation("Validacion VIES"),
 *   label_collection = @Translation("Validaciones VIES"),
 *   label_singular = @Translation("validacion VIES"),
 *   label_plural = @Translation("validaciones VIES"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_multiregion\ListBuilder\ViesValidationListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_multiregion\Form\ViesValidationForm",
 *       "add" = "Drupal\jaraba_multiregion\Form\ViesValidationForm",
 *       "edit" = "Drupal\jaraba_multiregion\Form\ViesValidationForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_multiregion\Access\ViesValidationAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "vies_validation",
 *   admin_permission = "administer multiregion",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "vat_number",
 *   },
 *   links = {
 *     "collection" = "/admin/content/vies-validations",
 *     "canonical" = "/admin/content/vies-validations/{vies_validation}",
 *     "delete-form" = "/admin/content/vies-validations/{vies_validation}/delete",
 *   },
 * )
 */
class ViesValidation extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    // Campos base heredados de ContentEntityBase (id, uuid).
    $fields = parent::baseFieldDefinitions($entity_type);

    // =========================================================================
    // BLOQUE 1: VINCULO CON TENANT
    // Referencia al tenant que solicito la validacion VIES.
    // =========================================================================

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Tenant'))
      ->setDescription(new TranslatableMarkup('Tenant que solicito la validacion VIES.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 2: DATOS DE LA CONSULTA VIES
    // Numero IVA, codigo de pais y resultado de la validacion.
    // =========================================================================

    $fields['vat_number'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Numero VAT'))
      ->setDescription(new TranslatableMarkup('Numero de identificacion fiscal consultado en VIES.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 20)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['country_code'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Codigo pais'))
      ->setDescription(new TranslatableMarkup('Codigo ISO 3166-1 alpha-2 del pais del numero IVA consultado.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_valid'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Valido'))
      ->setDescription(new TranslatableMarkup('Resultado de la validacion: TRUE si el numero IVA es valido segun VIES.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 3: DATOS DEVUELTOS POR VIES
    // Nombre y direccion de la empresa segun registro VIES.
    // =========================================================================

    $fields['company_name'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Nombre empresa'))
      ->setDescription(new TranslatableMarkup('Nombre o razon social de la empresa devuelto por VIES.'))
      ->setRequired(FALSE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['company_address'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Direccion empresa'))
      ->setDescription(new TranslatableMarkup('Direccion fiscal de la empresa devuelta por VIES.'))
      ->setRequired(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 4: TRAZABILIDAD
    // Identificador de consulta VIES y fecha de la validacion.
    // =========================================================================

    $fields['request_identifier'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('ID consulta VIES'))
      ->setDescription(new TranslatableMarkup('Identificador unico de la consulta VIES para trazabilidad tributaria.'))
      ->setRequired(FALSE)
      ->setSetting('max_length', 50)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['validated_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Fecha validacion'))
      ->setDescription(new TranslatableMarkup('Fecha y hora en que se realizo la consulta al servicio VIES.'))
      ->setRequired(TRUE)
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 5: METADATOS TEMPORALES
    // Solo timestamp de creacion (entidad de registro inmutable).
    // =========================================================================

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Fecha de creacion'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   *
   * Define indices de base de datos para la tabla vies_validation.
   * - tenant_id: indice para filtrado por tenant.
   * - vat_number: indice para busqueda por numero IVA.
   * - validated_at: indice para ordenacion temporal del historial.
   */
  public static function schema(EntityTypeInterface $entity_type): array {
    $schema = parent::schema($entity_type);

    $schema['indexes']['idx_tenant_id'] = ['tenant_id'];
    $schema['indexes']['idx_vat_number'] = ['vat_number'];
    $schema['indexes']['idx_validated_at'] = ['validated_at'];

    return $schema;
  }

}
