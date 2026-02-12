<?php

declare(strict_types=1);

namespace Drupal\jaraba_ads\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Cuenta de Ads.
 *
 * ESTRUCTURA:
 * Entidad que representa una cuenta de publicidad conectada a una
 * plataforma externa (Meta, Google, LinkedIn, TikTok). Almacena
 * credenciales OAuth, tokens de acceso y estado de sincronización.
 *
 * LÓGICA:
 * Una AdsAccount pertenece a un tenant (tenant_id) y almacena las
 * credenciales necesarias para comunicarse con la API de la plataforma.
 * El campo status controla el ciclo de vida: active -> inactive/expired/error.
 * Los tokens se renuevan automáticamente mediante refresh_token.
 *
 * RELACIONES:
 * - AdsAccount -> Tenant (tenant_id): tenant propietario
 * - AdsAccount <- AdsCampaignSync (account_id): campañas vinculadas
 * - AdsAccount <- AdsAudienceSync (account_id): audiencias vinculadas
 * - AdsAccount <- AdsConversionEvent (account_id): eventos de conversión
 * - AdsAccount <- MetaAdsClientService: consumido por
 * - AdsAccount <- GoogleAdsClientService: consumido por
 *
 * @ContentEntityType(
 *   id = "ads_account",
 *   label = @Translation("Cuenta de Ads"),
 *   label_collection = @Translation("Cuentas de Ads"),
 *   label_singular = @Translation("cuenta de ads"),
 *   label_plural = @Translation("cuentas de ads"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_ads\ListBuilder\AdsAccountListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_ads\Form\AdsAccountForm",
 *       "add" = "Drupal\jaraba_ads\Form\AdsAccountForm",
 *       "edit" = "Drupal\jaraba_ads\Form\AdsAccountForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_ads\Access\AdsAccountAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ads_account",
 *   fieldable = TRUE,
 *   admin_permission = "administer ads settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "account_name",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/ads-accounts/{ads_account}",
 *     "add-form" = "/admin/content/ads-accounts/add",
 *     "edit-form" = "/admin/content/ads-accounts/{ads_account}/edit",
 *     "delete-form" = "/admin/content/ads-accounts/{ads_account}/delete",
 *     "collection" = "/admin/content/ads-accounts",
 *   },
 *   field_ui_base_route = "entity.ads_account.settings",
 * )
 */
class AdsAccount extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- Tenant ---
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant propietario de esta cuenta de ads.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Plataforma ---
    $fields['platform'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Plataforma'))
      ->setDescription(t('Plataforma de publicidad asociada a esta cuenta.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'meta' => t('Meta'),
        'google' => t('Google'),
        'linkedin' => t('LinkedIn'),
        'tiktok' => t('TikTok'),
      ])
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Nombre de cuenta ---
    $fields['account_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre de Cuenta'))
      ->setDescription(t('Nombre descriptivo de la cuenta de publicidad.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- ID externo de la cuenta ---
    $fields['external_account_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ID Externo'))
      ->setDescription(t('Identificador de la cuenta en la plataforma de ads.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Token de acceso ---
    $fields['access_token'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Token de Acceso'))
      ->setDescription(t('Token OAuth de acceso para la API de la plataforma.'))
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Token de refresco ---
    $fields['refresh_token'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Token de Refresco'))
      ->setDescription(t('Token OAuth de refresco para renovar el token de acceso.'))
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Expiración del token ---
    $fields['token_expires_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Expiración del Token'))
      ->setDescription(t('Timestamp de expiración del token de acceso.'))
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Scopes OAuth ---
    $fields['oauth_scopes'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Scopes OAuth'))
      ->setDescription(t('Scopes OAuth concedidos durante la autorización.'))
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Estado ---
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado actual de la conexión de la cuenta.'))
      ->setDefaultValue('active')
      ->setSetting('allowed_values', [
        'active' => t('Activa'),
        'inactive' => t('Inactiva'),
        'expired' => t('Expirada'),
        'error' => t('Error'),
      ])
      ->setDisplayOptions('form', ['weight' => 8])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Última sincronización ---
    $fields['last_synced_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Última Sincronización'))
      ->setDescription(t('Timestamp de la última sincronización exitosa.'))
      ->setDisplayOptions('form', ['weight' => 9])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Error de sincronización ---
    $fields['sync_error'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Error de Sincronización'))
      ->setDescription(t('Último mensaje de error durante la sincronización.'))
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Metadatos ---
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creación'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificación'));

    return $fields;
  }

}
