<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Define la entidad Connector para el marketplace de integraciones.
 *
 * PROPÓSITO:
 * Representa un conector disponible en el marketplace (ej: Google Analytics,
 * Mailchimp, Zapier, Slack). Los tenants pueden instalar conectores
 * desde el catálogo para extender su plataforma.
 *
 * FLUJO:
 * 1. Admin de plataforma crea un Connector con metadata y config schema.
 * 2. El conector aparece en el marketplace (/integraciones).
 * 3. El tenant instala el conector → crea ConnectorInstallation.
 * 4. ConnectorInstallerService gestiona el ciclo de vida.
 *
 * @ContentEntityType(
 *   id = "connector",
 *   label = @Translation("Connector"),
 *   label_collection = @Translation("Connectors"),
 *   label_singular = @Translation("connector"),
 *   label_plural = @Translation("connectors"),
 *   label_count = @PluralTranslation(
 *     singular = "@count connector",
 *     plural = "@count connectors",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_integrations\ConnectorListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_integrations\Form\ConnectorForm",
 *       "add" = "Drupal\jaraba_integrations\Form\ConnectorForm",
 *       "edit" = "Drupal\jaraba_integrations\Form\ConnectorForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_integrations\Access\ConnectorAccessControlHandler",
 *   },
 *   base_table = "connector",
 *   data_table = "connector_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer integrations",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "collection" = "/admin/content/connectors",
 *     "add-form" = "/admin/content/connectors/add",
 *     "canonical" = "/admin/content/connectors/{connector}",
 *     "edit-form" = "/admin/content/connectors/{connector}/edit",
 *     "delete-form" = "/admin/content/connectors/{connector}/delete",
 *   },
 *   field_ui_base_route = "entity.connector.collection",
 * )
 */
class Connector extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * Estados del conector en el marketplace.
   */
  public const STATUS_DRAFT = 'draft';
  public const STATUS_PUBLISHED = 'published';
  public const STATUS_DEPRECATED = 'deprecated';

  /**
   * Categorías de conectores.
   */
  public const CATEGORY_ANALYTICS = 'analytics';
  public const CATEGORY_MARKETING = 'marketing';
  public const CATEGORY_CRM = 'crm';
  public const CATEGORY_ECOMMERCE = 'ecommerce';
  public const CATEGORY_COMMUNICATION = 'communication';
  public const CATEGORY_PRODUCTIVITY = 'productivity';
  public const CATEGORY_PAYMENT = 'payment';
  public const CATEGORY_AI = 'ai';
  public const CATEGORY_CUSTOM = 'custom';

  /**
   * Tipos de autenticación soportados.
   */
  public const AUTH_NONE = 'none';
  public const AUTH_API_KEY = 'api_key';
  public const AUTH_OAUTH2 = 'oauth2';
  public const AUTH_BASIC = 'basic';
  public const AUTH_BEARER = 'bearer';

  /**
   * Obtiene el nombre del conector.
   */
  public function getName(): string {
    return $this->get('name')->value ?? '';
  }

  /**
   * Obtiene la categoría del conector.
   */
  public function getCategory(): string {
    return $this->get('category')->value ?? self::CATEGORY_CUSTOM;
  }

  /**
   * Obtiene el tipo de autenticación.
   */
  public function getAuthType(): string {
    return $this->get('auth_type')->value ?? self::AUTH_NONE;
  }

  /**
   * Obtiene el estado de publicación.
   */
  public function getPublishStatus(): string {
    return $this->get('publish_status')->value ?? self::STATUS_DRAFT;
  }

  /**
   * Verifica si el conector está publicado.
   */
  public function isPublished(): bool {
    return $this->getPublishStatus() === self::STATUS_PUBLISHED;
  }

  /**
   * Obtiene el esquema de configuración (JSON Schema).
   */
  public function getConfigSchema(): array {
    $schema = $this->get('config_schema')->value;
    if (is_string($schema)) {
      return json_decode($schema, TRUE) ?? [];
    }
    return $schema ?? [];
  }

  /**
   * Obtiene la URL base de la API del conector.
   */
  public function getApiBaseUrl(): string {
    return $this->get('api_base_url')->value ?? '';
  }

  /**
   * Obtiene el icono del conector.
   */
  public function getIcon(): string {
    return $this->get('icon')->value ?? 'puzzle-piece';
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Nombre del conector.
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre'))
      ->setDescription(t('Nombre del conector (ej: Google Analytics, Mailchimp).'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 200)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Slug único para URLs.
    $fields['machine_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Machine Name'))
      ->setDescription(t('Identificador único para URLs y API (ej: google_analytics).'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 128)
      ->addConstraint('UniqueField')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // Descripción corta.
    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Descripción'))
      ->setDescription(t('Descripción del conector para el marketplace.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Categoría.
    $fields['category'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Categoría'))
      ->setDescription(t('Categoría del conector en el marketplace.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        self::CATEGORY_ANALYTICS => 'Analytics',
        self::CATEGORY_MARKETING => 'Marketing',
        self::CATEGORY_CRM => 'CRM',
        self::CATEGORY_ECOMMERCE => 'E-Commerce',
        self::CATEGORY_COMMUNICATION => 'Comunicación',
        self::CATEGORY_PRODUCTIVITY => 'Productividad',
        self::CATEGORY_PAYMENT => 'Pagos',
        self::CATEGORY_AI => 'Inteligencia Artificial',
        self::CATEGORY_CUSTOM => 'Personalizado',
      ])
      ->setDefaultValue(self::CATEGORY_CUSTOM)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Icono SVG (nombre del icono en el sistema jaraba_icon).
    $fields['icon'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Icono'))
      ->setDescription(t('Nombre del icono SVG (ej: puzzle-piece, chart-bar, mail).'))
      ->setSetting('max_length', 64)
      ->setDefaultValue('puzzle-piece')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // URL del logo del proveedor (para marketplace).
    $fields['logo_url'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('URL del Logo'))
      ->setDescription(t('URL del logo del servicio (ej: SVG de Google Analytics).'))
      ->setDisplayOptions('form', [
        'type' => 'uri',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // Tipo de autenticación.
    $fields['auth_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Autenticación'))
      ->setDescription(t('Método de autenticación requerido por la API.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        self::AUTH_NONE => 'Sin autenticación',
        self::AUTH_API_KEY => 'API Key',
        self::AUTH_OAUTH2 => 'OAuth 2.0',
        self::AUTH_BASIC => 'HTTP Basic',
        self::AUTH_BEARER => 'Bearer Token',
      ])
      ->setDefaultValue(self::AUTH_API_KEY)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // URL base de la API del servicio externo.
    $fields['api_base_url'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('URL Base de la API'))
      ->setDescription(t('Endpoint base de la API del servicio (ej: https://api.mailchimp.com/3.0).'))
      ->setDisplayOptions('form', [
        'type' => 'uri',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // Esquema de configuración JSON Schema.
    $fields['config_schema'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Esquema de Configuración'))
      ->setDescription(t('JSON Schema que define los campos de configuración del conector.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 10,
        'settings' => [
          'rows' => 12,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    // URL de documentación externa.
    $fields['docs_url'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('URL de Documentación'))
      ->setDescription(t('Enlace a la documentación del conector.'))
      ->setDisplayOptions('form', [
        'type' => 'uri',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // Versión del conector.
    $fields['version'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Versión'))
      ->setDescription(t('Versión actual del conector (semver, ej: 1.2.0).'))
      ->setSetting('max_length', 32)
      ->setDefaultValue('1.0.0')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 12,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Autor / proveedor.
    $fields['provider'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Proveedor'))
      ->setDescription(t('Nombre del proveedor o desarrollador del conector.'))
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 200)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 13,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Estado de publicación.
    $fields['publish_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado de publicación en el marketplace.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        self::STATUS_DRAFT => 'Borrador',
        self::STATUS_PUBLISHED => 'Publicado',
        self::STATUS_DEPRECATED => 'Obsoleto',
      ])
      ->setDefaultValue(self::STATUS_DRAFT)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 15,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Planes mínimos requeridos (JSON array: ["pro", "enterprise"]).
    $fields['required_plans'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Planes Requeridos'))
      ->setDescription(t('JSON array de planes que tienen acceso (ej: ["pro","enterprise"]). Vacío = todos.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 16,
        'settings' => [
          'rows' => 3,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    // Eventos soportados para webhooks (JSON array).
    $fields['supported_events'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Eventos Soportados'))
      ->setDescription(t('JSON array de eventos que este conector puede emitir (ej: ["order.created","payment.received"]).'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 17,
        'settings' => [
          'rows' => 4,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    // Número de instalaciones activas (caché denormalizado).
    $fields['install_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Instalaciones'))
      ->setDescription(t('Número de instalaciones activas (caché).'))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_integer',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    // Timestamps.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de creación'))
      ->setDescription(t('Fecha en que se creó el conector.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de modificación'))
      ->setDescription(t('Fecha de la última modificación.'));

    return $fields;
  }

}
