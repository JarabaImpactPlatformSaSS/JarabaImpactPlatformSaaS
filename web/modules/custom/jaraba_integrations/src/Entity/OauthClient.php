<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Define la entidad OauthClient para aplicaciones OAuth2.
 *
 * PROPÓSITO:
 * Registra aplicaciones externas que pueden autenticarse contra la plataforma
 * via OAuth2. Cada OauthClient tiene un client_id y client_secret únicos.
 *
 * FLUJO OAuth2:
 * 1. Desarrollador registra su app → OauthClient creado
 * 2. App redirige a /oauth/authorize con client_id
 * 3. Usuario autoriza → callback con code
 * 4. App intercambia code por access_token en /oauth/token
 *
 * @ContentEntityType(
 *   id = "oauth_client",
 *   label = @Translation("OAuth Client"),
 *   label_collection = @Translation("OAuth Clients"),
 *   label_singular = @Translation("OAuth client"),
 *   label_plural = @Translation("OAuth clients"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_integrations\OauthClientListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_integrations\Form\OauthClientForm",
 *       "add" = "Drupal\jaraba_integrations\Form\OauthClientForm",
 *       "edit" = "Drupal\jaraba_integrations\Form\OauthClientForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_integrations\Access\OauthClientAccessControlHandler",
 *   },
 *   base_table = "oauth_client",
 *   admin_permission = "administer integrations",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/integrations/oauth",
 *     "add-form" = "/admin/structure/integrations/oauth/add",
 *     "canonical" = "/admin/structure/integrations/oauth/{oauth_client}",
 *     "edit-form" = "/admin/structure/integrations/oauth/{oauth_client}/edit",
 *     "delete-form" = "/admin/structure/integrations/oauth/{oauth_client}/delete",
 *   },
 *   field_ui_base_route = "entity.oauth_client.settings",
 * )
 */
class OauthClient extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * Obtiene el nombre de la aplicación.
   */
  public function getName(): string {
    return $this->get('name')->value ?? '';
  }

  /**
   * Obtiene el client_id.
   */
  public function getClientId(): string {
    return $this->get('client_id')->value ?? '';
  }

  /**
   * Obtiene el client_secret.
   */
  public function getClientSecret(): string {
    return $this->get('client_secret')->value ?? '';
  }

  /**
   * Obtiene los scopes autorizados.
   */
  public function getScopes(): array {
    $scopes = $this->get('scopes')->value;
    if (empty($scopes)) {
      return [];
    }
    return array_map('trim', explode(',', $scopes));
  }

  /**
   * Verifica si el client está activo.
   */
  public function isActive(): bool {
    return (bool) ($this->get('is_active')->value ?? FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Nombre de la aplicación.
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre de la Aplicación'))
      ->setDescription(t('Nombre descriptivo del cliente OAuth2.'))
      ->setRequired(TRUE)
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

    // Client ID (generado automáticamente).
    $fields['client_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Client ID'))
      ->setDescription(t('Identificador público del cliente OAuth2.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 128)
      ->addConstraint('UniqueField')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Client Secret (generado automáticamente, hash en DB).
    $fields['client_secret'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Client Secret'))
      ->setDescription(t('Secreto del cliente OAuth2 (visible solo al crear).'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 256)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // Redirect URI(s) permitidas.
    $fields['redirect_uri'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Redirect URIs'))
      ->setDescription(t('URIs de callback autorizadas (una por línea).'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 2,
        'settings' => [
          'rows' => 4,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Scopes autorizados (CSV).
    $fields['scopes'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Scopes'))
      ->setDescription(t('Permisos autorizados separados por coma (ej: read,write,admin).'))
      ->setSetting('max_length', 500)
      ->setDefaultValue('read')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Tenant propietario.
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant propietario de este cliente OAuth2.'))
      ->setSetting('target_type', 'group');

    // Estado activo/inactivo.
    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Activo'))
      ->setDescription(t('Indica si el cliente OAuth2 está habilitado.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // Timestamps.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de creación'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de modificación'));

    return $fields;
  }

}
