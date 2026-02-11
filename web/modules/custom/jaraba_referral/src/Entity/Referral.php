<?php

namespace Drupal\jaraba_referral\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Referido.
 *
 * ESTRUCTURA:
 * Entidad central de jaraba_referral que representa una relación de referido
 * entre dos usuarios. Almacena quién refirió (referrer_uid), quién fue
 * referido (referred_uid), el código único de 8 caracteres alfanuméricos,
 * el estado del referido (pending/confirmed/rewarded/expired), el tipo de
 * recompensa (discount/credit/feature/custom) y su valor monetario.
 *
 * LÓGICA:
 * Un Referral pertenece a un tenant (tenant_id) y vincula dos usuarios.
 * El referral_code es generado por ReferralManagerService como cadena
 * alfanumérica única de 8 caracteres. El ciclo de vida del estado es:
 * pending -> confirmed (cuando el referido completa registro) ->
 * rewarded (cuando se otorga la recompensa) o expired (timeout).
 * El campo reward_value almacena el valor numérico de la recompensa
 * que puede ser un porcentaje de descuento, créditos, etc.
 *
 * RELACIONES:
 * - Referral -> User (referrer_uid): usuario que refirió
 * - Referral -> User (referred_uid): usuario referido
 * - Referral -> Tenant (tenant_id): tenant propietario
 * - Referral <- ReferralManagerService: gestionado por
 * - Referral <- ReferralListBuilder: listado en admin
 *
 * @ContentEntityType(
 *   id = "referral",
 *   label = @Translation("Referido"),
 *   label_collection = @Translation("Referidos"),
 *   label_singular = @Translation("referido"),
 *   label_plural = @Translation("referidos"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_referral\ListBuilder\ReferralListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_referral\Form\ReferralForm",
 *       "add" = "Drupal\jaraba_referral\Form\ReferralForm",
 *       "edit" = "Drupal\jaraba_referral\Form\ReferralForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_referral\Access\ReferralAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "referral",
 *   admin_permission = "administer referral settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "referral_code",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/referral/{referral}",
 *     "add-form" = "/admin/content/referral/add",
 *     "edit-form" = "/admin/content/referral/{referral}/edit",
 *     "delete-form" = "/admin/content/referral/{referral}/delete",
 *     "collection" = "/admin/content/referrals",
 *   },
 *   field_ui_base_route = "jaraba_referral.referral.settings",
 * )
 */
class Referral extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- Usuario Referidor ---
    $fields['referrer_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Referidor'))
      ->setDescription(t('Usuario que generó el código de referido y compartió la invitación.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Usuario Referido ---
    $fields['referred_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Referido'))
      ->setDescription(t('Usuario que se registró usando el código de referido.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Código de Referido ---
    $fields['referral_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Código de Referido'))
      ->setDescription(t('Código único alfanumérico de 8 caracteres para compartir.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 8)
      ->addConstraint('UniqueField')
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Estado del Referido ---
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setRequired(TRUE)
      ->setDefaultValue('pending')
      ->setSetting('allowed_values', [
        'pending' => t('Pendiente'),
        'confirmed' => t('Confirmado'),
        'rewarded' => t('Recompensado'),
        'expired' => t('Expirado'),
      ])
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Tipo de Recompensa ---
    $fields['reward_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Recompensa'))
      ->setDefaultValue('discount')
      ->setSetting('allowed_values', [
        'discount' => t('Descuento'),
        'credit' => t('Crédito'),
        'feature' => t('Feature Premium'),
        'custom' => t('Personalizado'),
      ])
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Valor de la Recompensa ---
    $fields['reward_value'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Valor de Recompensa'))
      ->setDescription(t('Valor numérico de la recompensa (porcentaje, EUR, etc.).'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Tenant ---
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'tenant')
      ->setDisplayOptions('form', ['weight' => 15])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Metadatos ---
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creación'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificación'));

    return $fields;
  }

  /**
   * Comprueba si el referido está confirmado.
   *
   * ESTRUCTURA: Método helper que evalúa el campo status.
   * LÓGICA: Devuelve TRUE solo cuando el estado es 'confirmed'.
   * RELACIONES: Consumido por ReferralManagerService.
   *
   * @return bool
   *   TRUE si el referido tiene estado 'confirmed'.
   */
  public function isConfirmed(): bool {
    return $this->get('status')->value === 'confirmed';
  }

  /**
   * Comprueba si el referido ya fue recompensado.
   *
   * ESTRUCTURA: Método helper que evalúa el campo status.
   * LÓGICA: Devuelve TRUE solo cuando el estado es 'rewarded'.
   * RELACIONES: Consumido por ReferralManagerService.
   *
   * @return bool
   *   TRUE si el referido tiene estado 'rewarded'.
   */
  public function isRewarded(): bool {
    return $this->get('status')->value === 'rewarded';
  }

  /**
   * Comprueba si el referido ha expirado.
   *
   * ESTRUCTURA: Método helper que evalúa el campo status.
   * LÓGICA: Devuelve TRUE solo cuando el estado es 'expired'.
   * RELACIONES: Consumido por ReferralManagerService.
   *
   * @return bool
   *   TRUE si el referido tiene estado 'expired'.
   */
  public function isExpired(): bool {
    return $this->get('status')->value === 'expired';
  }

}
