<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * @ContentEntityType(
 *   id = "comercio_flash_claim",
 *   label = @Translation("Canje de Oferta Flash"),
 *   label_collection = @Translation("Canjes de Ofertas Flash"),
 *   label_singular = @Translation("canje de oferta flash"),
 *   label_plural = @Translation("canjes de ofertas flash"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\FlashOfferClaimAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "comercio_flash_claim",
 *   admin_permission = "manage comercio flash offers",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-flash-claim/{comercio_flash_claim}",
 *     "collection" = "/admin/content/comercio-flash-claims",
 *   },
 *   field_ui_base_route = "entity.comercio_flash_claim.settings",
 * )
 */
class FlashOfferClaim extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['offer_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Oferta Flash'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'comercio_flash_offer')
      ->setDisplayConfigurable('form', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Usuario'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('form', TRUE);

    $fields['claim_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Codigo de canje'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 32)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'claimed' => t('Canjeado'),
        'redeemed' => t('Redimido'),
        'expired' => t('Expirado'),
      ])
      ->setDefaultValue('claimed')
      ->setDisplayConfigurable('view', TRUE);

    $fields['claimed_at'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de canje'));

    $fields['redeemed_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Fecha de redencion'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['location_lat'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Latitud'))
      ->setDisplayConfigurable('form', TRUE);

    $fields['location_lng'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Longitud'))
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

}
