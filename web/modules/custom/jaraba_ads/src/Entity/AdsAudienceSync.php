<?php

declare(strict_types=1);

namespace Drupal\jaraba_ads\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Sincronización de Audiencia.
 *
 * ESTRUCTURA:
 * Entidad que representa una audiencia personalizada sincronizada
 * con una plataforma de publicidad. Permite subir listas de contactos
 * del CRM, emails o visitantes web como audiencias custom en Meta,
 * Google o LinkedIn para targeting avanzado.
 *
 * LÓGICA:
 * Una AdsAudienceSync pertenece a una AdsAccount y a un tenant.
 * El source_type indica el origen de los datos (crm_contacts, email_list,
 * website_visitors, custom). El source_config almacena la configuración
 * del origen en formato JSON. El sync_status controla el flujo:
 * pending -> syncing -> synced/error.
 *
 * RELACIONES:
 * - AdsAudienceSync -> AdsAccount (account_id): cuenta vinculada
 * - AdsAudienceSync -> Tenant (tenant_id): tenant propietario
 * - AdsAudienceSync <- AdsAudienceSyncService: sincronizado por
 *
 * @ContentEntityType(
 *   id = "ads_audience_sync",
 *   label = @Translation("Sincronizacion de Audiencia"),
 *   label_collection = @Translation("Sincronizaciones de Audiencia"),
 *   label_singular = @Translation("sincronizacion de audiencia"),
 *   label_plural = @Translation("sincronizaciones de audiencia"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_ads\ListBuilder\AdsAudienceSyncListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_ads\Form\AdsAudienceSyncForm",
 *       "add" = "Drupal\jaraba_ads\Form\AdsAudienceSyncForm",
 *       "edit" = "Drupal\jaraba_ads\Form\AdsAudienceSyncForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_ads\Access\AdsAudienceSyncAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ads_audience_sync",
 *   fieldable = TRUE,
 *   admin_permission = "administer ads settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "audience_name",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/ads-audience-sync/{ads_audience_sync}",
 *     "add-form" = "/admin/content/ads-audience-sync/add",
 *     "edit-form" = "/admin/content/ads-audience-sync/{ads_audience_sync}/edit",
 *     "delete-form" = "/admin/content/ads-audience-sync/{ads_audience_sync}/delete",
 *     "collection" = "/admin/content/ads-audience-sync",
 *   },
 *   field_ui_base_route = "entity.ads_audience_sync.settings",
 * )
 */
class AdsAudienceSync extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- Tenant ---
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant propietario de esta audiencia sincronizada.'))
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Cuenta de ads ---
    $fields['account_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Cuenta de Ads'))
      ->setDescription(t('Cuenta de ads asociada a esta audiencia.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'ads_account')
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Nombre de audiencia ---
    $fields['audience_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre de Audiencia'))
      ->setDescription(t('Nombre descriptivo de la audiencia personalizada.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Plataforma ---
    $fields['platform'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Plataforma'))
      ->setDescription(t('Plataforma de destino para la audiencia.'))
      ->setSetting('allowed_values', [
        'meta' => t('Meta'),
        'google' => t('Google'),
        'linkedin' => t('LinkedIn'),
      ])
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- ID externo de la audiencia ---
    $fields['external_audience_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ID Externo de Audiencia'))
      ->setDescription(t('Identificador de la audiencia en la plataforma de ads.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Tipo de origen ---
    $fields['source_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Origen'))
      ->setDescription(t('Origen de los datos de la audiencia.'))
      ->setSetting('allowed_values', [
        'crm_contacts' => t('Contactos CRM'),
        'email_list' => t('Lista de emails'),
        'website_visitors' => t('Visitantes web'),
        'custom' => t('Personalizado'),
      ])
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Configuración del origen ---
    $fields['source_config'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Configuración del Origen'))
      ->setDescription(t('Configuración del origen de datos en formato JSON.'))
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Número de miembros ---
    $fields['member_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Número de Miembros'))
      ->setDescription(t('Cantidad de miembros en la audiencia.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Estado de sincronización ---
    $fields['sync_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado de Sincronización'))
      ->setDescription(t('Estado actual de la sincronización de la audiencia.'))
      ->setSetting('allowed_values', [
        'pending' => t('Pendiente'),
        'syncing' => t('Sincronizando'),
        'synced' => t('Sincronizada'),
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
