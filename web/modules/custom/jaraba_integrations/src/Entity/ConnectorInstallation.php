<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Define la entidad ConnectorInstallation (instalación de conector por tenant).
 *
 * PROPÓSITO:
 * Cada vez que un tenant instala un conector del marketplace, se crea una
 * ConnectorInstallation que almacena la configuración específica del tenant
 * (credenciales, opciones) y el estado de la conexión.
 *
 * AISLAMIENTO:
 * El campo tenant_id garantiza aislamiento multi-tenant. Un tenant solo
 * puede ver y gestionar sus propias instalaciones.
 *
 * FLUJO:
 * 1. Tenant selecciona conector en /integraciones
 * 2. ConnectorInstallerService crea la instalación
 * 3. Tenant configura credenciales en /integraciones/{id}/configurar
 * 4. Health check valida la conexión
 *
 * @ContentEntityType(
 *   id = "connector_installation",
 *   label = @Translation("Connector Installation"),
 *   label_collection = @Translation("Connector Installations"),
 *   label_singular = @Translation("connector installation"),
 *   label_plural = @Translation("connector installations"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_integrations\ConnectorInstallationListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_integrations\Access\ConnectorInstallationAccessControlHandler",
 *   },
 *   base_table = "connector_installation",
 *   admin_permission = "administer integrations",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/integrations/installations",
 *     "canonical" = "/admin/structure/integrations/installations/{connector_installation}",
 *     "delete-form" = "/admin/structure/integrations/installations/{connector_installation}/delete",
 *   },
 *   field_ui_base_route = "entity.connector_installation.settings",
 * )
 */
class ConnectorInstallation extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * Estados de la instalación.
   */
  public const STATUS_ACTIVE = 'active';
  public const STATUS_INACTIVE = 'inactive';
  public const STATUS_ERROR = 'error';
  public const STATUS_PENDING_CONFIG = 'pending_config';

  /**
   * Obtiene el conector asociado.
   */
  public function getConnector(): ?Connector {
    $connector = $this->get('connector_id')->entity;
    return $connector instanceof Connector ? $connector : NULL;
  }

  /**
   * Obtiene la configuración del tenant (credenciales, opciones).
   */
  public function getConfiguration(): array {
    $config = $this->get('configuration')->value;
    if (is_string($config)) {
      return json_decode($config, TRUE) ?? [];
    }
    return $config ?? [];
  }

  /**
   * Establece la configuración del tenant.
   */
  public function setConfiguration(array $config): self {
    $this->set('configuration', json_encode($config, JSON_UNESCAPED_UNICODE));
    return $this;
  }

  /**
   * Obtiene el estado de la instalación.
   */
  public function getInstallationStatus(): string {
    return $this->get('status')->value ?? self::STATUS_PENDING_CONFIG;
  }

  /**
   * Verifica si la instalación está activa.
   */
  public function isActive(): bool {
    return $this->getInstallationStatus() === self::STATUS_ACTIVE;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Conector instalado.
    $fields['connector_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Conector'))
      ->setDescription(t('Conector del marketplace que se ha instalado.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'connector')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    // Tenant que instaló el conector (aislamiento multi-tenant).
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant que instaló el conector.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('view', TRUE);

    // Configuración específica del tenant (JSON con credenciales).
    $fields['configuration'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Configuración'))
      ->setDescription(t('JSON con credenciales y opciones específicas del tenant.'));

    // Estado de la instalación.
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado actual de la instalación.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        self::STATUS_ACTIVE => 'Activa',
        self::STATUS_INACTIVE => 'Inactiva',
        self::STATUS_ERROR => 'Error',
        self::STATUS_PENDING_CONFIG => 'Pendiente de configuración',
      ])
      ->setDefaultValue(self::STATUS_PENDING_CONFIG)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    // Usuario que instaló el conector.
    $fields['installed_by'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Instalado por'))
      ->setDescription(t('Usuario que realizó la instalación.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default');

    // Último resultado del health check.
    $fields['last_health_check'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Último Health Check'))
      ->setDescription(t('Fecha del último health check exitoso.'))
      ->setSetting('datetime_type', 'datetime');

    // Resultado del health check (JSON).
    $fields['health_status'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Estado de Salud'))
      ->setDescription(t('JSON con el resultado del último health check.'));

    // Token de acceso OAuth (cifrado en producción).
    $fields['access_token'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Access Token'))
      ->setDescription(t('Token de acceso OAuth para el servicio externo.'));

    // Refresh token OAuth.
    $fields['refresh_token'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Refresh Token'))
      ->setDescription(t('Refresh token OAuth.'))
      ->setSetting('max_length', 512);

    // Fecha de expiración del token.
    $fields['token_expires'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Token Expires'))
      ->setDescription(t('Fecha de expiración del access token.'))
      ->setSetting('datetime_type', 'datetime');

    // Timestamps.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de instalación'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de modificación'));

    return $fields;
  }

}
