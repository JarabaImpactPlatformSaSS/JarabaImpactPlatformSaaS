<?php

declare(strict_types=1);

namespace Drupal\jaraba_referral\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Recompensa de Referido.
 *
 * ESTRUCTURA:
 * Entidad que almacena recompensas individuales generadas por el programa
 * de referidos. Cada recompensa está vinculada a un referido (referral_id),
 * un código (code_id) y un usuario beneficiario (user_id). Incluye
 * información de pago via Stripe y estado del ciclo de vida.
 *
 * LÓGICA:
 * El ciclo de vida de una recompensa es:
 * pending -> approved (validación manual o automática) -> paid (pago
 * procesado via Stripe) o rejected/expired. El campo stripe_payout_id
 * vincula el pago con Stripe Connect. paid_at y expires_at controlan
 * el tracking temporal del pago y la vigencia.
 *
 * RELACIONES:
 * - ReferralReward -> Group (tenant_id): tenant propietario
 * - ReferralReward -> Referral (referral_id): referido asociado
 * - ReferralReward -> ReferralCode (code_id): código que generó la recompensa
 * - ReferralReward -> User (user_id): usuario beneficiario
 * - ReferralReward <- RewardProcessingService: gestionado por
 * - ReferralReward <- ReferralRewardListBuilder: listado en admin
 *
 * @ContentEntityType(
 *   id = "referral_reward",
 *   label = @Translation("Recompensa de Referido"),
 *   label_collection = @Translation("Recompensas de Referidos"),
 *   label_singular = @Translation("recompensa de referido"),
 *   label_plural = @Translation("recompensas de referidos"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_referral\ListBuilder\ReferralRewardListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_referral\Form\ReferralRewardForm",
 *       "add" = "Drupal\jaraba_referral\Form\ReferralRewardForm",
 *       "edit" = "Drupal\jaraba_referral\Form\ReferralRewardForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_referral\Access\ReferralRewardAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "referral_reward",
 *   fieldable = TRUE,
 *   admin_permission = "administer referral program",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "id",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/referral-rewards/{referral_reward}",
 *     "add-form" = "/admin/content/referral-rewards/add",
 *     "edit-form" = "/admin/content/referral-rewards/{referral_reward}/edit",
 *     "delete-form" = "/admin/content/referral-rewards/{referral_reward}/delete",
 *     "collection" = "/admin/content/referral-rewards",
 *   },
 *   field_ui_base_route = "entity.referral_reward.settings",
 * )
 */
class ReferralReward extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- Tenant propietario ---
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant propietario de esta recompensa de referido.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Referido asociado ---
    $fields['referral_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Referido'))
      ->setDescription(t('Entidad de referido que originó esta recompensa.'))
      ->setSetting('target_type', 'referral')
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Código de referido asociado ---
    $fields['code_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Código de Referido'))
      ->setDescription(t('Código de referido que generó esta recompensa.'))
      ->setSetting('target_type', 'referral_code')
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Usuario beneficiario de la recompensa ---
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Usuario Beneficiario'))
      ->setDescription(t('Usuario que recibe esta recompensa.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Tipo de recompensa ---
    $fields['reward_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Recompensa'))
      ->setDescription(t('Tipo de recompensa otorgada al usuario.'))
      ->setSetting('allowed_values', [
        'discount_percentage' => t('Descuento Porcentual'),
        'discount_fixed' => t('Descuento Fijo'),
        'credit' => t('Crédito'),
        'free_month' => t('Mes Gratis'),
        'custom' => t('Personalizado'),
      ])
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Valor de la recompensa ---
    $fields['reward_value'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Valor de Recompensa'))
      ->setDescription(t('Valor numérico de la recompensa (porcentaje o importe).'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Moneda ---
    $fields['currency'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Moneda'))
      ->setDescription(t('Código ISO 4217 de la moneda de la recompensa.'))
      ->setDefaultValue('EUR')
      ->setSetting('max_length', 3)
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Estado de la recompensa ---
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado actual del ciclo de vida de la recompensa.'))
      ->setRequired(TRUE)
      ->setDefaultValue('pending')
      ->setSetting('allowed_values', [
        'pending' => t('Pendiente'),
        'approved' => t('Aprobada'),
        'paid' => t('Pagada'),
        'rejected' => t('Rechazada'),
        'expired' => t('Expirada'),
      ])
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- ID de pago en Stripe ---
    $fields['stripe_payout_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Stripe Payout ID'))
      ->setDescription(t('Identificador del payout en Stripe Connect (po_xxx).'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 8])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Fecha de pago ---
    $fields['paid_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Fecha de Pago'))
      ->setDescription(t('Timestamp del momento en que se procesó el pago.'))
      ->setDisplayOptions('form', ['weight' => 9])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Fecha de expiración ---
    $fields['expires_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Fecha de Expiración'))
      ->setDescription(t('Timestamp a partir del cual la recompensa expira si no se ha cobrado.'))
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Notas internas ---
    $fields['notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Notas'))
      ->setDescription(t('Notas internas sobre la recompensa (motivo de rechazo, etc.).'))
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Metadatos temporales ---
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creación'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificación'));

    return $fields;
  }

  /**
   * Comprueba si la recompensa está pendiente de aprobación.
   *
   * @return bool
   *   TRUE si el estado es 'pending'.
   */
  public function isPending(): bool {
    return $this->get('status')->value === 'pending';
  }

  /**
   * Comprueba si la recompensa ha sido pagada.
   *
   * @return bool
   *   TRUE si el estado es 'paid'.
   */
  public function isPaid(): bool {
    return $this->get('status')->value === 'paid';
  }

  /**
   * Comprueba si la recompensa ha sido rechazada.
   *
   * @return bool
   *   TRUE si el estado es 'rejected'.
   */
  public function isRejected(): bool {
    return $this->get('status')->value === 'rejected';
  }

}
