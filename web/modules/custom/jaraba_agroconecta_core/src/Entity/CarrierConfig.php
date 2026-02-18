<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad CarrierConfig.
 *
 * Almacena las credenciales y configuración de cada transportista
 * para un Productor o Tenant.
 *
 * @ContentEntityType(
 *   id = "agro_carrier_config",
 *   label = @Translation("Configuración de Transportista"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_agroconecta_core\Form\CarrierConfigForm",
 *       "add" = "Drupal\jaraba_agroconecta_core\Form\CarrierConfigForm",
 *       "edit" = "Drupal\jaraba_agroconecta_core\Form\CarrierConfigForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_agroconecta_core\Entity\AgroShippingAccessControlHandler",
 *   },
 *   base_table = "agro_carrier_config",
 *   admin_permission = "manage agro shipping",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "carrier_id",
 *   },
 * )
 */
class CarrierConfig extends ContentEntityBase {

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setRequired(TRUE);

    $fields['producer_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Productor'))
      ->setSetting('target_type', 'producer_profile')
      ->setDescription(t('Si está vacío, es la configuración por defecto del Tenant.'));

    $fields['carrier_id'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Transportista'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'mrw' => 'MRW',
        'seur' => 'SEUR',
      ]);

    $fields['api_key'] = BaseFieldDefinition::create('string')
      ->setLabel(t('API Key / Password'))
      ->setRequired(TRUE);

    $fields['api_user'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Usuario / Código Abonado'));

    $fields['api_url'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('URL del Endpoint (Sandbox/Pro)'))
      ->setRequired(TRUE);

    $fields['is_test_mode'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Modo Pruebas (Sandbox)'))
      ->setDefaultValue(TRUE);

    $fields['settings'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Ajustes específicos (JSON)'))
      ->setDescription(t('Configuración extra como códigos de servicio por defecto.'));

    return $fields;
  }

}
