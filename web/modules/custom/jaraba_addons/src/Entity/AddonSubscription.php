<?php

namespace Drupal\jaraba_addons\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Suscripción a Add-on.
 *
 * ESTRUCTURA:
 * Entidad que representa la suscripción activa de un tenant a un add-on
 * específico del catálogo. Almacena la referencia al add-on (addon_id),
 * al tenant suscrito (tenant_id), el estado de la suscripción, el ciclo
 * de facturación (mensual/anual), el periodo de vigencia y el precio
 * efectivamente pagado.
 *
 * LÓGICA:
 * Una AddonSubscription vincula un Addon con un Tenant. El ciclo de vida
 * del estado es: trial -> active -> cancelled/expired. El billing_cycle
 * determina si la renovación es mensual o anual. El precio pagado puede
 * diferir del precio del catálogo si hay descuentos o promociones.
 * Las fechas start_date y end_date definen el periodo de vigencia.
 * Un cron o servicio externo debe verificar end_date para expirar
 * suscripciones vencidas.
 *
 * RELACIONES:
 * - AddonSubscription -> Addon (addon_id): add-on suscrito
 * - AddonSubscription -> Tenant (tenant_id): tenant suscrito
 * - AddonSubscription <- AddonSubscriptionService: gestionado por
 * - AddonSubscription <- AddonSubscriptionListBuilder: listado en admin
 *
 * @ContentEntityType(
 *   id = "addon_subscription",
 *   label = @Translation("Suscripción Add-on"),
 *   label_collection = @Translation("Suscripciones Add-ons"),
 *   label_singular = @Translation("suscripción add-on"),
 *   label_plural = @Translation("suscripciones add-ons"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_addons\ListBuilder\AddonSubscriptionListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_addons\Form\AddonSubscriptionForm",
 *       "add" = "Drupal\jaraba_addons\Form\AddonSubscriptionForm",
 *       "edit" = "Drupal\jaraba_addons\Form\AddonSubscriptionForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_addons\Access\AddonSubscriptionAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "addon_subscription",
 *   admin_permission = "administer addons settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/addon-subscription/{addon_subscription}",
 *     "add-form" = "/admin/content/addon-subscription/add",
 *     "edit-form" = "/admin/content/addon-subscription/{addon_subscription}/edit",
 *     "delete-form" = "/admin/content/addon-subscription/{addon_subscription}/delete",
 *     "collection" = "/admin/content/addon-subscriptions",
 *   },
 *   field_ui_base_route = "jaraba_addons.addon_subscription.settings",
 * )
 */
class AddonSubscription extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- Referencia al Add-on ---
    $fields['addon_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Add-on'))
      ->setDescription(t('Add-on del catálogo al que se suscribe.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'addon')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Tenant ---
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant que tiene la suscripción activa.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'tenant')
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Estado de la Suscripción ---
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setRequired(TRUE)
      ->setDefaultValue('active')
      ->setSetting('allowed_values', [
        'active' => t('Activa'),
        'cancelled' => t('Cancelada'),
        'expired' => t('Expirada'),
        'trial' => t('Prueba'),
      ])
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Ciclo de Facturación ---
    $fields['billing_cycle'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Ciclo de Facturación'))
      ->setRequired(TRUE)
      ->setDefaultValue('monthly')
      ->setSetting('allowed_values', [
        'monthly' => t('Mensual'),
        'yearly' => t('Anual'),
      ])
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Periodo de Vigencia ---
    $fields['start_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de Inicio'))
      ->setDescription(t('Inicio del periodo de suscripción activa.'))
      ->setDisplayOptions('form', ['weight' => 15])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['end_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de Fin'))
      ->setDescription(t('Fin del periodo de suscripción (renovación o expiración).'))
      ->setDisplayOptions('form', ['weight' => 16])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Precio Pagado ---
    $fields['price_paid'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Precio Pagado (EUR)'))
      ->setDescription(t('Precio efectivamente cobrado al tenant (puede incluir descuentos).'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 20])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Metadatos ---
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creación'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificación'));

    return $fields;
  }

  /**
   * Comprueba si la suscripción está activa.
   *
   * ESTRUCTURA: Método helper que evalúa el campo status.
   * LÓGICA: Devuelve TRUE si el estado es 'active' o 'trial'.
   * RELACIONES: Consumido por AddonSubscriptionService.
   *
   * @return bool
   *   TRUE si la suscripción está activa o en periodo de prueba.
   */
  public function isActive(): bool {
    return in_array($this->get('status')->value, ['active', 'trial']);
  }

  /**
   * Comprueba si la suscripción ha expirado por fecha.
   *
   * ESTRUCTURA: Método helper que evalúa el campo end_date.
   * LÓGICA: Compara la fecha de fin con la fecha actual.
   *   Si end_date es NULL, la suscripción no expira.
   * RELACIONES: Consumido por cron y AddonSubscriptionService.
   *
   * @return bool
   *   TRUE si la suscripción ha superado su fecha de fin.
   */
  public function hasExpiredByDate(): bool {
    $end_date = $this->get('end_date')->value;
    if (empty($end_date)) {
      return FALSE;
    }
    return strtotime($end_date) < time();
  }

}
