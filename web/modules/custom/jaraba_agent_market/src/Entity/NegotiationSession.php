<?php

declare(strict_types=1);

namespace Drupal\jaraba_agent_market\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad NegotiationSession.
 *
 * @ContentEntityType(
 *   id = "negotiation_session",
 *   label = @Translation("Sesión de Negociación"),
 *   base_table = "negotiation_session",
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   }
 * )
 */
class NegotiationSession extends ContentEntityBase {

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['initiator_did'] = BaseFieldDefinition::create('string')
      ->setLabel(t('DID Iniciador'))
      ->setRequired(TRUE);

    $fields['responder_did'] = BaseFieldDefinition::create('string')
      ->setLabel(t('DID Receptor'))
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setSetting('allowed_values', [
        'active' => 'Activa',
        'closed_won' => 'Acuerdo Alcanzado',
        'closed_lost' => 'Rechazada',
      ])
      ->setDefaultValue('active');

    $fields['ledger'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Ledger de Mensajes (JSON)'))
      ->setDescription(t('Historial de pasos firmados criptográficamente.'))
      ->setRequired(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    return $fields;
  }

}
