<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Entidad Reseller para el programa de partners/revendedores.
 *
 * PROPOSITO:
 * Representa un socio revendedor (reseller) que gestiona uno o mas
 * tenants en la plataforma Jaraba SaaS. Cada reseller tiene una
 * configuracion comercial propia (comisiones, territorio, modelo de
 * revenue share) y acceso a un portal de partner dedicado.
 *
 * LOGICA:
 * - Cada reseller se asocia a uno o mas tenants via managed_tenant_ids
 * - El modelo de revenue share determina como se calculan comisiones
 * - El portal de partner (/partner-portal) identifica al reseller
 *   por el email del usuario autenticado (contact_email)
 * - Los estados controlan el ciclo de vida: pending -> active -> suspended
 *
 * DIRECTRICES:
 * - commission_rate se expresa como porcentaje (e.g. 15.00 = 15%)
 * - territory almacena JSON con configuracion geografica/sectorial
 * - contract_start/contract_end definen la vigencia del contrato
 * - ResellerCommissionService calcula comisiones a partir de los datos
 *   de facturacion de los tenants gestionados
 *
 * @ContentEntityType(
 *   id = "reseller",
 *   label = @Translation("Reseller"),
 *   label_collection = @Translation("Resellers"),
 *   label_singular = @Translation("reseller"),
 *   label_plural = @Translation("resellers"),
 *   label_count = @PluralTranslation(
 *     singular = "@count reseller",
 *     plural = "@count resellers",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\ecosistema_jaraba_core\ResellerListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\ecosistema_jaraba_core\Form\ResellerForm",
 *       "add" = "Drupal\ecosistema_jaraba_core\Form\ResellerForm",
 *       "edit" = "Drupal\ecosistema_jaraba_core\Form\ResellerForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\ecosistema_jaraba_core\Access\ResellerAccessControlHandler",
 *   },
 *   base_table = "reseller",
 *   admin_permission = "administer tenants",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/config/resellers",
 *     "add-form" = "/admin/config/resellers/add",
 *     "canonical" = "/admin/config/resellers/{reseller}",
 *     "edit-form" = "/admin/config/resellers/{reseller}/edit",
 *     "delete-form" = "/admin/config/resellers/{reseller}/delete",
 *   },
 *   field_ui_base_route = "entity.reseller.settings",
 * )
 */
class Reseller extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * Estados posibles del reseller.
   */
  public const STATUS_ACTIVE = 'active';
  public const STATUS_SUSPENDED = 'suspended';
  public const STATUS_PENDING = 'pending';

  /**
   * Modelos de revenue share.
   */
  public const REVENUE_PERCENTAGE = 'percentage';
  public const REVENUE_FLAT_FEE = 'flat_fee';
  public const REVENUE_TIERED = 'tiered';

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Nombre del reseller.
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre'))
      ->setDescription(t('Nombre identificativo del reseller o partner.'))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 255,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -10,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Nombre de empresa.
    $fields['company_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre de Empresa'))
      ->setDescription(t('Razon social o nombre comercial de la empresa del reseller.'))
      ->setSettings([
        'max_length' => 255,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -9,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Email de contacto.
    $fields['contact_email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email de Contacto'))
      ->setDescription(t('Email principal del reseller. Se usa para identificar al usuario en el portal de partner.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'email_mailto',
        'weight' => -8,
      ])
      ->setDisplayOptions('form', [
        'type' => 'email_default',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Tasa de comision.
    $fields['commission_rate'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Tasa de Comision (%)'))
      ->setDescription(t('Porcentaje de comision sobre los ingresos de los tenants gestionados (ej: 15.00 = 15%).'))
      ->setSettings([
        'precision' => 5,
        'scale' => 2,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_decimal',
        'weight' => -7,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Territorio (JSON).
    $fields['territory'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Territorio'))
      ->setDescription(t('JSON con configuracion de territorio (regiones, sectores, exclusividad).'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'basic_string',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -6,
        'settings' => [
          'rows' => 4,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Estado del reseller.
    $fields['status_reseller'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado actual del reseller en la plataforma.'))
      ->setRequired(TRUE)
      ->setDefaultValue(self::STATUS_PENDING)
      ->setSettings([
        'allowed_values' => [
          self::STATUS_ACTIVE => 'Activo',
          self::STATUS_SUSPENDED => 'Suspendido',
          self::STATUS_PENDING => 'Pendiente',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Tenants gestionados (entity_reference a group, cardinalidad ilimitada).
    $fields['managed_tenant_ids'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenants Gestionados'))
      ->setDescription(t('Tenants (grupos) que este reseller gestiona.'))
      ->setSetting('target_type', 'group')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -4,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Modelo de revenue share.
    $fields['revenue_share_model'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Modelo de Revenue Share'))
      ->setDescription(t('Modelo para calcular las comisiones del reseller.'))
      ->setRequired(TRUE)
      ->setDefaultValue(self::REVENUE_PERCENTAGE)
      ->setSettings([
        'allowed_values' => [
          self::REVENUE_PERCENTAGE => 'Porcentaje',
          self::REVENUE_FLAT_FEE => 'Tarifa fija',
          self::REVENUE_TIERED => 'Escalonado',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Inicio de contrato.
    $fields['contract_start'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Inicio de Contrato'))
      ->setDescription(t('Fecha de inicio del contrato con el reseller.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'datetime_default',
        'weight' => -2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Fin de contrato.
    $fields['contract_end'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fin de Contrato'))
      ->setDescription(t('Fecha de finalizacion del contrato con el reseller.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'datetime_default',
        'weight' => -1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Timestamps.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de creacion'))
      ->setDescription(t('Fecha en que se creo el reseller.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de modificacion'))
      ->setDescription(t('Fecha de la ultima modificacion.'));

    return $fields;
  }

  /**
   * Obtiene la configuracion de territorio decodificada.
   *
   * @return array
   *   Array con la configuracion de territorio.
   */
  public function getDecodedTerritory(): array {
    $raw = $this->get('territory')->value;
    if (empty($raw)) {
      return [];
    }
    $decoded = json_decode($raw, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

}
