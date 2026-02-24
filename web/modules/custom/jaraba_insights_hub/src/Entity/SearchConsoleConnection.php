<?php

declare(strict_types=1);

namespace Drupal\jaraba_insights_hub\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Search Console Connection.
 *
 * ESTRUCTURA:
 * Entidad que almacena las credenciales OAuth y estado de conexion
 * con Google Search Console por tenant. Cada tenant puede tener una
 * o mas conexiones a diferentes propiedades de Search Console.
 *
 * LOGICA:
 * Los tokens de acceso y refresh se almacenan cifrados. El status
 * controla el ciclo de vida de la conexion: active -> expired -> revoked.
 * El campo sync_errors acumula errores consecutivos de sincronizacion.
 *
 * RELACIONES:
 * - SearchConsoleConnection -> Tenant (tenant_id): tenant propietario
 * - SearchConsoleConnection <- SearchConsoleService: gestionado por
 * - SearchConsoleConnection <- SearchConsoleData: genera datos
 *
 * @ContentEntityType(
 *   id = "search_console_connection",
 *   label = @Translation("Search Console Connection"),
 *   label_collection = @Translation("Search Console Connections"),
 *   label_singular = @Translation("search console connection"),
 *   label_plural = @Translation("search console connections"),
 *   handlers = {
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_insights_hub\Access\InsightsAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "search_console_connection",
 *   admin_permission = "administer insights hub",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/search-console-connection/{search_console_connection}",
 *     "collection" = "/admin/content/search-console-connections",
 *   },
 *   field_ui_base_route = "entity.search_console_connection.settings",
 * )
 */
class SearchConsoleConnection extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- Tenant ---
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant propietario de esta conexion.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'tenant')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Site URL ---
    $fields['site_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL del Sitio'))
      ->setDescription(t('URL de la propiedad en Google Search Console.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Access Token (encrypted) ---
    $fields['access_token'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Access Token'))
      ->setDescription(t('Token de acceso OAuth cifrado.'));

    // --- Refresh Token (encrypted) ---
    $fields['refresh_token'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Refresh Token'))
      ->setDescription(t('Token de refresco OAuth cifrado.'));

    // --- Token Expiration ---
    $fields['token_expires_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Expiracion del Token'))
      ->setDescription(t('Timestamp de expiracion del access token.'));

    // --- Status ---
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setRequired(TRUE)
      ->setDefaultValue('active')
      ->setSetting('allowed_values', [
        'active' => 'Activa',
        'expired' => 'Expirada',
        'revoked' => 'Revocada',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Last Sync ---
    $fields['last_sync_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Ultima Sincronizacion'))
      ->setDescription(t('Timestamp de la ultima sincronizacion exitosa.'))
      ->setDisplayConfigurable('view', TRUE);

    // --- Sync Errors ---
    $fields['sync_errors'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Errores de Sincronizacion'))
      ->setDescription(t('Numero de errores consecutivos de sincronizacion.'))
      ->setDefaultValue(0);

    // --- Metadatos ---
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creacion'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificacion'));

    return $fields;
  }

}
