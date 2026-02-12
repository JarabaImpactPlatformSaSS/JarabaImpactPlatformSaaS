<?php

declare(strict_types=1);

namespace Drupal\jaraba_referral\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Código de Referido.
 *
 * ESTRUCTURA:
 * Entidad que representa un código de referido único asociado a un usuario
 * y un programa. Almacena el código alfanumérico, URL personalizada,
 * contadores de tracking (clicks, signups, conversiones), revenue
 * acumulado, estado de activación y fecha de expiración.
 *
 * LÓGICA:
 * Cada código pertenece a un programa (program_id) y fue generado por un
 * usuario (user_id). El campo code es único y se usa como label de la
 * entidad. Los contadores total_clicks, total_signups y total_conversions
 * se incrementan por ReferralTrackingService. El campo total_revenue
 * acumula el valor monetario de las conversiones.
 *
 * RELACIONES:
 * - ReferralCode -> Group (tenant_id): tenant propietario
 * - ReferralCode -> ReferralProgram (program_id): programa asociado
 * - ReferralCode -> User (user_id): usuario propietario del código
 * - ReferralCode <- ReferralTrackingService: contadores actualizados por
 * - ReferralCode <- ReferralCodeListBuilder: listado en admin
 *
 * @ContentEntityType(
 *   id = "referral_code",
 *   label = @Translation("Codigo de Referido"),
 *   label_collection = @Translation("Codigos de Referido"),
 *   label_singular = @Translation("codigo de referido"),
 *   label_plural = @Translation("codigos de referido"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_referral\ListBuilder\ReferralCodeListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_referral\Form\ReferralCodeForm",
 *       "add" = "Drupal\jaraba_referral\Form\ReferralCodeForm",
 *       "edit" = "Drupal\jaraba_referral\Form\ReferralCodeForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_referral\Access\ReferralCodeAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "referral_code",
 *   fieldable = TRUE,
 *   admin_permission = "administer referral program",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "code",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/referral-codes/{referral_code}",
 *     "add-form" = "/admin/content/referral-codes/add",
 *     "edit-form" = "/admin/content/referral-codes/{referral_code}/edit",
 *     "delete-form" = "/admin/content/referral-codes/{referral_code}/delete",
 *     "collection" = "/admin/content/referral-codes",
 *   },
 *   field_ui_base_route = "entity.referral_code.settings",
 * )
 */
class ReferralCode extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- Tenant propietario ---
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant propietario de este código de referido.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Programa de referidos asociado ---
    $fields['program_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Programa de Referidos'))
      ->setDescription(t('Programa de referidos al que pertenece este código.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'referral_program')
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Usuario propietario del código ---
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Usuario'))
      ->setDescription(t('Usuario que generó y posee este código de referido.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Código alfanumérico único ---
    $fields['code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Código'))
      ->setDescription(t('Código alfanumérico único de referido para compartir.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 50)
      ->addConstraint('UniqueField')
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- URL personalizada ---
    $fields['custom_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL Personalizada'))
      ->setDescription(t('URL personalizada para compartir en lugar del código estándar.'))
      ->setSetting('max_length', 2048)
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Contador de clicks ---
    $fields['total_clicks'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Total Clicks'))
      ->setDescription(t('Número total de clicks en el enlace de referido.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Contador de registros ---
    $fields['total_signups'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Total Registros'))
      ->setDescription(t('Número total de usuarios registrados con este código.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Contador de conversiones ---
    $fields['total_conversions'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Total Conversiones'))
      ->setDescription(t('Número total de conversiones (compras, suscripciones) con este código.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Revenue total acumulado ---
    $fields['total_revenue'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Revenue Total'))
      ->setDescription(t('Valor monetario total generado por conversiones de este código.'))
      ->setDefaultValue(0)
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 8])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Código activo ---
    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Activo'))
      ->setDescription(t('Indica si el código está activo y puede recibir referidos.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['weight' => 9])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Fecha de expiración del código ---
    $fields['expires_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de Expiración'))
      ->setDescription(t('Fecha en la que el código deja de ser válido.'))
      ->setDisplayOptions('form', ['weight' => 10])
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
   * Comprueba si el código está activo y no ha expirado.
   *
   * ESTRUCTURA: Método helper que evalúa is_active + fecha de expiración.
   * LÓGICA: Devuelve TRUE si is_active es TRUE y la fecha actual no supera
   *   expires_at (si está definido).
   * RELACIONES: Consumido por ReferralTrackingService.
   *
   * @return bool
   *   TRUE si el código está activo y vigente.
   */
  public function isValid(): bool {
    if (!$this->get('is_active')->value) {
      return FALSE;
    }

    $expires_at = $this->get('expires_at')->value;
    if ($expires_at && new \DateTime($expires_at) < new \DateTime()) {
      return FALSE;
    }

    return TRUE;
  }

}
