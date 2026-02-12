<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Add-on de Tenant.
 *
 * Representa un add-on activo en la suscripción de un tenant.
 * Permite extender funcionalidad del plan base con módulos adicionales.
 *
 * @ContentEntityType(
 *   id = "tenant_addon",
 *   label = @Translation("Add-on de Tenant"),
 *   label_collection = @Translation("Add-ons de Tenant"),
 *   label_singular = @Translation("add-on de tenant"),
 *   label_plural = @Translation("add-ons de tenant"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_billing\ListBuilder\TenantAddonListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_billing\Form\TenantAddonForm",
 *       "add" = "Drupal\jaraba_billing\Form\TenantAddonForm",
 *       "edit" = "Drupal\jaraba_billing\Form\TenantAddonForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_billing\Access\TenantAddonAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "tenant_addon",
 *   admin_permission = "administer billing",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "addon_code",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/tenant-addon/{tenant_addon}",
 *     "add-form" = "/admin/content/tenant-addon/add",
 *     "edit-form" = "/admin/content/tenant-addon/{tenant_addon}/edit",
 *     "delete-form" = "/admin/content/tenant-addon/{tenant_addon}/delete",
 *     "collection" = "/admin/content/tenant-addons",
 *   },
 *   field_ui_base_route = "jaraba_billing.tenant_addon.settings",
 * )
 */
class TenantAddon extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['addon_code'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Código de Add-on'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'jaraba_crm' => t('CRM'),
        'jaraba_email' => t('Email Marketing'),
        'jaraba_email_plus' => t('Email Marketing Plus'),
        'jaraba_social' => t('Social Media'),
        'paid_ads_sync' => t('Paid Ads Sync'),
        'retargeting_pixels' => t('Retargeting Pixels'),
        'events_webinars' => t('Events & Webinars'),
        'ab_testing' => t('A/B Testing'),
        'referral_program' => t('Referral Program'),
      ])
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['stripe_subscription_item_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Stripe Subscription Item ID'))
      ->setDescription(t('ID del item de suscripción en Stripe para este add-on.'))
      ->setSetting('max_length', 100)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['price'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Precio Mensual'))
      ->setDescription(t('Precio mensual del add-on en EUR.'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDefaultValue('0.00')
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setRequired(TRUE)
      ->setDefaultValue('active')
      ->setSetting('allowed_values', [
        'active' => t('Activo'),
        'canceled' => t('Cancelado'),
        'pending' => t('Pendiente'),
      ])
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['activated_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Fecha de Activación'))
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['canceled_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Fecha de Cancelación'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creación'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificación'));

    return $fields;
  }

}
