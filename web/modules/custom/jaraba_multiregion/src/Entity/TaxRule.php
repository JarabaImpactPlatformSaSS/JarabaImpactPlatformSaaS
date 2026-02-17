<?php

declare(strict_types=1);

namespace Drupal\jaraba_multiregion\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Define la entidad Regla Fiscal (TaxRule).
 *
 * ESTRUCTURA:
 * Entidad de configuracion a nivel de sistema que almacena las reglas fiscales
 * por pais. Incluye tipos impositivos (general, reducido, superreducido,
 * servicios digitales), umbral OSS, inversion de sujeto pasivo y pertenencia
 * a la UE. No es propiedad de ningun tenant: es compartida globalmente.
 *
 * LOGICA:
 * Cada regla fiscal tiene una vigencia temporal definida por effective_from y
 * effective_to, permitiendo mantener historico de tipos impositivos y aplicar
 * automaticamente el tipo vigente segun la fecha de la transaccion. El campo
 * reverse_charge_enabled controla si se aplica inversion de sujeto pasivo
 * en transacciones B2B intracomunitarias. El umbral OSS (oss_threshold)
 * determina cuando un vendedor debe registrarse en el pais de destino.
 *
 * SINTAXIS:
 * ContentEntityBase con EntityChangedInterface. Sin EntityOwnerInterface
 * porque es una entidad de configuracion del sistema, no vinculada a usuarios.
 * Indices en country_code, effective_from y eu_member para consultas fiscales.
 *
 * RELACIONES:
 * - TaxRule es una entidad independiente sin FK directas.
 * - Consumida por servicios de calculo fiscal que la consultan por country_code
 *   y rango de fechas de vigencia.
 *
 * @ContentEntityType(
 *   id = "tax_rule",
 *   label = @Translation("Regla Fiscal"),
 *   label_collection = @Translation("Reglas Fiscales"),
 *   label_singular = @Translation("regla fiscal"),
 *   label_plural = @Translation("reglas fiscales"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_multiregion\ListBuilder\TaxRuleListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_multiregion\Form\TaxRuleForm",
 *       "add" = "Drupal\jaraba_multiregion\Form\TaxRuleForm",
 *       "edit" = "Drupal\jaraba_multiregion\Form\TaxRuleForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_multiregion\Access\TaxRuleAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "tax_rule",
 *   admin_permission = "administer multiregion",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "tax_name",
 *   },
 *   links = {
 *     "collection" = "/admin/content/tax-rules",
 *     "add-form" = "/admin/content/tax-rules/add",
 *     "canonical" = "/admin/content/tax-rules/{tax_rule}",
 *     "edit-form" = "/admin/content/tax-rules/{tax_rule}/edit",
 *     "delete-form" = "/admin/content/tax-rules/{tax_rule}/delete",
 *   },
 *   field_ui_base_route = "jaraba_multiregion.tax_rule.settings",
 * )
 */
class TaxRule extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    // Campos base heredados de ContentEntityBase (id, uuid).
    $fields = parent::baseFieldDefinitions($entity_type);

    // =========================================================================
    // BLOQUE 1: IDENTIFICACION DEL PAIS E IMPUESTO
    // Codigo de pais y nombre del impuesto que actua como etiqueta de la entidad.
    // =========================================================================

    $fields['country_code'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Codigo pais'))
      ->setDescription(new TranslatableMarkup('Codigo ISO 3166-1 alpha-2 del pais al que aplica la regla fiscal.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tax_name'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Nombre impuesto'))
      ->setDescription(new TranslatableMarkup('Nombre del impuesto (IVA, TVA, VAT, MwSt, etc.).'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 50)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 2: TIPOS IMPOSITIVOS
    // Tipo general, reducido, superreducido y especial para servicios digitales.
    // =========================================================================

    $fields['standard_rate'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Tipo general'))
      ->setDescription(new TranslatableMarkup('Tipo impositivo general en porcentaje (ej: 21.00 para 21%).'))
      ->setRequired(TRUE)
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['reduced_rate'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Tipo reducido'))
      ->setDescription(new TranslatableMarkup('Tipo impositivo reducido en porcentaje.'))
      ->setRequired(FALSE)
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['super_reduced_rate'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Tipo superreducido'))
      ->setDescription(new TranslatableMarkup('Tipo impositivo superreducido en porcentaje.'))
      ->setRequired(FALSE)
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['digital_services_rate'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Tipo servicios digitales'))
      ->setDescription(new TranslatableMarkup('Tipo impositivo especial para servicios digitales en porcentaje.'))
      ->setRequired(FALSE)
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 3: CONFIGURACION OSS Y REVERSE CHARGE
    // Umbral de ventana unica (OSS) e inversion de sujeto pasivo.
    // =========================================================================

    $fields['oss_threshold'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Umbral OSS'))
      ->setDescription(new TranslatableMarkup('Umbral en euros para el regimen de ventana unica OSS (One-Stop Shop).'))
      ->setRequired(FALSE)
      ->setDefaultValue(10000)
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['reverse_charge_enabled'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Inversion sujeto pasivo'))
      ->setDescription(new TranslatableMarkup('Indica si se aplica inversion de sujeto pasivo en transacciones B2B intracomunitarias.'))
      ->setRequired(TRUE)
      ->setDefaultValue(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 4: PERTENENCIA UE Y VIGENCIA
    // Flag de miembro UE y rango temporal de vigencia de la regla fiscal.
    // =========================================================================

    $fields['eu_member'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Miembro UE'))
      ->setDescription(new TranslatableMarkup('Indica si el pais es miembro de la Union Europea.'))
      ->setRequired(TRUE)
      ->setDefaultValue(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['effective_from'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Vigente desde'))
      ->setDescription(new TranslatableMarkup('Fecha desde la que esta regla fiscal es aplicable.'))
      ->setRequired(TRUE)
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['effective_to'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Vigente hasta'))
      ->setDescription(new TranslatableMarkup('Fecha hasta la que esta regla fiscal es aplicable. Null si sigue vigente.'))
      ->setRequired(FALSE)
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 5: METADATOS TEMPORALES
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
   * Define indices de base de datos para la tabla tax_rule.
   * - country_code: indice para busqueda por pais.
   * - effective_from: indice para consultas por vigencia temporal.
   * - eu_member: indice para filtrado por pertenencia a la UE.
   */
  public static function schema(EntityTypeInterface $entity_type): array {
    $schema = parent::schema($entity_type);

    $schema['indexes']['idx_country_code'] = ['country_code'];
    $schema['indexes']['idx_effective_from'] = ['effective_from'];
    $schema['indexes']['idx_eu_member'] = ['eu_member'];

    return $schema;
  }

}
