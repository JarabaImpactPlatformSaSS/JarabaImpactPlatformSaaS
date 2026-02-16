<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_calendar\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Conexion de Calendario (CalendarConnection).
 *
 * Almacena credenciales OAuth cifradas para sincronizacion bidireccional
 * con Google Calendar o Microsoft Outlook. Tokens almacenados cifrados
 * con libsodium secretbox.
 *
 * @ContentEntityType(
 *   id = "calendar_connection",
 *   label = @Translation("Conexion de Calendario"),
 *   label_collection = @Translation("Conexiones de Calendario"),
 *   label_singular = @Translation("conexion de calendario"),
 *   label_plural = @Translation("conexiones de calendario"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_legal_calendar\Access\CalendarConnectionAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "calendar_connection",
 *   admin_permission = "manage calendar connections",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "account_email",
 *     "owner" = "provider_id",
 *   },
 *   links = {
 *     "delete-form" = "/admin/content/calendar-connections/{calendar_connection}/delete",
 *   },
 * )
 */
class CalendarConnection extends ContentEntityBase implements EntityOwnerInterface {

  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['provider_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Profesional'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['platform'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Plataforma'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'google' => new TranslatableMarkup('Google Calendar'),
        'microsoft' => new TranslatableMarkup('Microsoft Outlook'),
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['account_email'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Email de la Cuenta'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('view', TRUE);

    $fields['access_token'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Access Token'))
      ->setDescription(new TranslatableMarkup('Token cifrado con libsodium secretbox.'));

    $fields['refresh_token'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Refresh Token'))
      ->setDescription(new TranslatableMarkup('Refresh token cifrado.'));

    $fields['token_expires_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Expiracion del Token'))
      ->setSetting('datetime_type', 'datetime');

    $fields['scopes'] = BaseFieldDefinition::create('map')
      ->setLabel(new TranslatableMarkup('Scopes OAuth'))
      ->setDescription(new TranslatableMarkup('JSON array de scopes concedidos.'));

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Estado'))
      ->setRequired(TRUE)
      ->setDefaultValue('active')
      ->setSetting('allowed_values', [
        'active' => new TranslatableMarkup('Activa'),
        'expired' => new TranslatableMarkup('Expirada'),
        'revoked' => new TranslatableMarkup('Revocada'),
        'error' => new TranslatableMarkup('Error'),
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['last_sync_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Ultima Sincronizacion'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayConfigurable('view', TRUE);

    $fields['sync_errors'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Errores Consecutivos'))
      ->setDefaultValue(0);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Creado'));

    return $fields;
  }

}
