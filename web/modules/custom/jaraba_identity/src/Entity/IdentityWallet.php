<?php

declare(strict_types=1);

namespace Drupal\jaraba_identity\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad IdentityWallet.
 *
 * @ContentEntityType(
 *   id = "identity_wallet",
 *   label = @Translation("Cartera de Identidad"),
 *   base_table = "identity_wallet",
 *   admin_permission = "administer jaraba identity",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "did",
 *     "owner" = "uid",
 *   },
 *   handlers = {
 *     "access" = "Drupal\jaraba_identity\Access\IdentityWalletAccessControlHandler",
 *   }
 * )
 */
class IdentityWallet extends ContentEntityBase {

  use EntityOwnerTrait;

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['did'] = BaseFieldDefinition::create('string')
      ->setLabel(t('DID'))
      ->setDescription(t('Identificador Descentralizado único.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->addConstraint('UniqueField');

    $fields['public_key'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Clave Pública'))
      ->setDescription(t('Clave pública Ed25519 en Base64.'))
      ->setRequired(TRUE);

    $fields['encrypted_private_key'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Clave Privada Encriptada'))
      ->setDescription(t('Clave privada encriptada para uso custodial (agentes).'))
      ->setRequired(TRUE);

    $fields['type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Identidad'))
      ->setSetting('allowed_values', [
        'person' => 'Persona',
        'organization' => 'Organización',
        'agent' => 'Agente Autónomo',
      ])
      ->setDefaultValue('person')
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Activa'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
