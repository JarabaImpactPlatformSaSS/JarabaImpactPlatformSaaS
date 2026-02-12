<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad RevocationEntry.
 *
 * Registro de auditoría para revocaciones de credenciales.
 *
 * @ContentEntityType(
 *   id = "revocation_entry",
 *   label = @Translation("Entrada de Revocación"),
 *   label_collection = @Translation("Entradas de Revocación"),
 *   label_singular = @Translation("entrada de revocación"),
 *   label_plural = @Translation("entradas de revocación"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_credentials\RevocationEntryListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_credentials\Form\RevocationEntryForm",
 *       "add" = "Drupal\jaraba_credentials\Form\RevocationEntryForm",
 *       "edit" = "Drupal\jaraba_credentials\Form\RevocationEntryForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_credentials\RevocationEntryAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "revocation_entry",
 *   admin_permission = "administer credentials",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.revocation_entry.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "id",
 *     "owner" = "revoked_by_uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/revocation-entries/{revocation_entry}",
 *     "add-form" = "/admin/content/revocation-entries/add",
 *     "edit-form" = "/admin/content/revocation-entries/{revocation_entry}/edit",
 *     "delete-form" = "/admin/content/revocation-entries/{revocation_entry}/delete",
 *     "collection" = "/admin/content/revocation-entries",
 *   },
 * )
 */
class RevocationEntry extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * Razones de revocación.
   */
  public const REASON_FRAUD = 'fraud';
  public const REASON_ERROR = 'error';
  public const REASON_REQUEST = 'request';
  public const REASON_POLICY = 'policy';

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['credential_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Credencial'))
      ->setDescription(t('Credencial que fue revocada.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'issued_credential')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['revoked_by_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Revocado por'))
      ->setDescription(t('Usuario que realizó la revocación.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['reason'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Razón'))
      ->setDescription(t('Motivo de la revocación.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        self::REASON_FRAUD => t('Fraude'),
        self::REASON_ERROR => t('Error'),
        self::REASON_REQUEST => t('Solicitud del usuario'),
        self::REASON_POLICY => t('Política institucional'),
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Notas'))
      ->setDescription(t('Notas adicionales sobre la revocación.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -7,
        'settings' => [
          'rows' => 4,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Grupo/organización propietaria.'))
      ->setSetting('target_type', 'group')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['revoked_at'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Revocado en'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

  /**
   * Obtiene el ID de la credencial revocada.
   */
  public function getCredentialId(): ?int {
    $value = $this->get('credential_id')->target_id;
    return $value ? (int) $value : NULL;
  }

  /**
   * Obtiene la razón de revocación.
   */
  public function getReason(): string {
    return $this->get('reason')->value ?? '';
  }

  /**
   * Obtiene las notas de revocación.
   */
  public function getNotes(): string {
    return $this->get('notes')->value ?? '';
  }

}
