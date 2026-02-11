<?php

namespace Drupal\jaraba_addons\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Add-on.
 *
 * ESTRUCTURA:
 * Entidad del catálogo de jaraba_addons que representa un add-on disponible
 * para suscripción por los tenants. Almacena la información del producto
 * (nombre, machine_name, descripción), clasificación (tipo), precios
 * (mensual, anual), estado de disponibilidad, y datos técnicos de
 * configuración (features_included como JSON, limits como JSON).
 *
 * LÓGICA:
 * Un Addon es un producto del catálogo global de la plataforma. Los tipos
 * soportados son: feature (funcionalidad adicional), storage (espacio extra),
 * api_calls (cuota de API adicional), support (soporte premium) y custom.
 * Los campos features_included y limits almacenan JSON serializado que
 * describe las características y límites concretos del add-on.
 * El campo is_active controla la visibilidad en el catálogo público.
 * El tenant_id asocia el add-on al tenant que lo ofrece (para marketplace
 * de add-ons entre tenants).
 *
 * RELACIONES:
 * - Addon -> Tenant (tenant_id): tenant propietario del catálogo
 * - Addon <- AddonSubscription (addon_id): suscripciones a este add-on
 * - Addon <- AddonCatalogService: gestionado por
 * - Addon <- AddonListBuilder: listado en admin
 *
 * @ContentEntityType(
 *   id = "addon",
 *   label = @Translation("Add-on"),
 *   label_collection = @Translation("Add-ons"),
 *   label_singular = @Translation("add-on"),
 *   label_plural = @Translation("add-ons"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_addons\ListBuilder\AddonListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_addons\Form\AddonForm",
 *       "add" = "Drupal\jaraba_addons\Form\AddonForm",
 *       "edit" = "Drupal\jaraba_addons\Form\AddonForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_addons\Access\AddonAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "addon",
 *   admin_permission = "administer addons settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "label",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/addon/{addon}",
 *     "add-form" = "/admin/content/addon/add",
 *     "edit-form" = "/admin/content/addon/{addon}/edit",
 *     "delete-form" = "/admin/content/addon/{addon}/delete",
 *     "collection" = "/admin/content/addons",
 *   },
 *   field_ui_base_route = "jaraba_addons.addon.settings",
 * )
 */
class Addon extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- Identificación ---
    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre del Add-on'))
      ->setDescription(t('Nombre público del add-on en el catálogo.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['machine_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Machine Name'))
      ->setDescription(t('Identificador interno del add-on (snake_case, sin espacios).'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 128)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Descripción ---
    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Descripción'))
      ->setDescription(t('Descripción completa del add-on para el catálogo público.'))
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Clasificación ---
    $fields['addon_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Add-on'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'feature' => t('Feature'),
        'storage' => t('Storage'),
        'api_calls' => t('API Calls'),
        'support' => t('Soporte'),
        'custom' => t('Personalizado'),
      ])
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Precios ---
    $fields['price_monthly'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Precio Mensual (EUR)'))
      ->setDescription(t('Precio de suscripción mensual al add-on.'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['price_yearly'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Precio Anual (EUR)'))
      ->setDescription(t('Precio de suscripción anual al add-on (con descuento).'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Estado ---
    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Activo'))
      ->setDescription(t('Indica si el add-on está visible en el catálogo público.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['weight' => 15])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Datos Técnicos (JSON) ---
    $fields['features_included'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Features Incluidas (JSON)'))
      ->setDescription(t('Array JSON con las funcionalidades incluidas en el add-on. Ej: ["crm_advanced", "reports_premium"]'))
      ->setDisplayOptions('form', ['weight' => 20])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['limits'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Límites (JSON)'))
      ->setDescription(t('Objeto JSON con los límites del add-on. Ej: {"storage_gb": 50, "api_calls_month": 10000}'))
      ->setDisplayOptions('form', ['weight' => 21])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Tenant ---
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'tenant')
      ->setDisplayOptions('form', ['weight' => 25])
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
   * Comprueba si el add-on está activo en el catálogo.
   *
   * ESTRUCTURA: Método helper que evalúa el campo is_active.
   * LÓGICA: Devuelve TRUE si el add-on está visible en el catálogo.
   * RELACIONES: Consumido por AddonCatalogService.
   *
   * @return bool
   *   TRUE si el add-on está activo.
   */
  public function isActive(): bool {
    return (bool) $this->get('is_active')->value;
  }

  /**
   * Obtiene las features incluidas como array PHP.
   *
   * ESTRUCTURA: Método helper que decodifica el campo JSON features_included.
   * LÓGICA: Decodifica el JSON almacenado y devuelve array PHP.
   *   Si el campo está vacío o no es JSON válido, devuelve array vacío.
   * RELACIONES: Consumido por AddonSubscriptionService.
   *
   * @return array
   *   Array de features incluidas.
   */
  public function getFeaturesIncluded(): array {
    $json = $this->get('features_included')->value;
    if (empty($json)) {
      return [];
    }
    $decoded = json_decode($json, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

  /**
   * Obtiene los límites del add-on como array PHP.
   *
   * ESTRUCTURA: Método helper que decodifica el campo JSON limits.
   * LÓGICA: Decodifica el JSON almacenado y devuelve array PHP.
   *   Si el campo está vacío o no es JSON válido, devuelve array vacío.
   * RELACIONES: Consumido por AddonSubscriptionService.
   *
   * @return array
   *   Array asociativo de límites.
   */
  public function getLimits(): array {
    $json = $this->get('limits')->value;
    if (empty($json)) {
      return [];
    }
    $decoded = json_decode($json, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

  /**
   * Obtiene el precio según el ciclo de facturación.
   *
   * ESTRUCTURA: Método helper que selecciona precio mensual o anual.
   * LÓGICA: Devuelve el precio mensual o anual según el parámetro.
   * RELACIONES: Consumido por AddonCatalogService y AddonSubscriptionService.
   *
   * @param string $billing_cycle
   *   Ciclo de facturación: 'monthly' o 'yearly'.
   *
   * @return float
   *   Precio del add-on para el ciclo especificado.
   */
  public function getPrice(string $billing_cycle = 'monthly'): float {
    if ($billing_cycle === 'yearly') {
      return (float) ($this->get('price_yearly')->value ?? 0);
    }
    return (float) ($this->get('price_monthly')->value ?? 0);
  }

}
