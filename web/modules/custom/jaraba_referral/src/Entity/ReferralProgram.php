<?php

declare(strict_types=1);

namespace Drupal\jaraba_referral\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Programa de Referidos.
 *
 * ESTRUCTURA:
 * Entidad que representa un programa de referidos configurable por tenant.
 * Almacena la definición del programa: tipo de recompensa (porcentaje,
 * fijo, crédito, mes gratis, personalizado), valores de recompensa tanto
 * para el referidor como para el referido, rangos de fechas de vigencia,
 * límites de uso y términos y condiciones.
 *
 * LÓGICA:
 * Cada tenant puede tener múltiples programas activos. Un programa define
 * las reglas de recompensa para ambas partes del referido. Los campos
 * min_referrals_for_tier y max_rewards_per_user permiten gamificación
 * por niveles. is_active + starts_at/ends_at controlan la vigencia.
 *
 * RELACIONES:
 * - ReferralProgram -> Group (tenant_id): tenant propietario
 * - ReferralProgram <- ReferralCode: códigos asociados al programa
 * - ReferralProgram <- RewardProcessingService: consultado por
 * - ReferralProgram <- ReferralProgramListBuilder: listado en admin
 *
 * @ContentEntityType(
 *   id = "referral_program",
 *   label = @Translation("Programa de Referidos"),
 *   label_collection = @Translation("Programas de Referidos"),
 *   label_singular = @Translation("programa de referidos"),
 *   label_plural = @Translation("programas de referidos"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_referral\ListBuilder\ReferralProgramListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_referral\Form\ReferralProgramForm",
 *       "add" = "Drupal\jaraba_referral\Form\ReferralProgramForm",
 *       "edit" = "Drupal\jaraba_referral\Form\ReferralProgramForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_referral\Access\ReferralProgramAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "referral_program",
 *   fieldable = TRUE,
 *   admin_permission = "administer referral program",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/referral-programs/{referral_program}",
 *     "add-form" = "/admin/content/referral-programs/add",
 *     "edit-form" = "/admin/content/referral-programs/{referral_program}/edit",
 *     "delete-form" = "/admin/content/referral-programs/{referral_program}/delete",
 *     "collection" = "/admin/content/referral-programs",
 *   },
 *   field_ui_base_route = "entity.referral_program.settings",
 * )
 */
class ReferralProgram extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- Tenant propietario del programa ---
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant propietario de este programa de referidos.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Nombre del programa ---
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre del Programa'))
      ->setDescription(t('Nombre identificativo del programa de referidos.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Descripción del programa ---
    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Descripción'))
      ->setDescription(t('Descripción detallada del programa y sus beneficios.'))
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Tipo de recompensa para el referidor ---
    $fields['reward_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Recompensa'))
      ->setDescription(t('Tipo de recompensa que recibe el usuario que refiere.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'discount_percentage' => t('Descuento Porcentual'),
        'discount_fixed' => t('Descuento Fijo'),
        'credit' => t('Crédito'),
        'free_month' => t('Mes Gratis'),
        'custom' => t('Personalizado'),
      ])
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Valor de la recompensa para el referidor ---
    $fields['reward_value'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Valor de Recompensa'))
      ->setDescription(t('Valor numérico de la recompensa (porcentaje o importe).'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Moneda de la recompensa ---
    $fields['reward_currency'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Moneda'))
      ->setDescription(t('Código ISO 4217 de la moneda (ej: EUR, USD).'))
      ->setDefaultValue('EUR')
      ->setSetting('max_length', 3)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Tipo de recompensa para el referido ---
    $fields['referee_reward_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Recompensa para Referido'))
      ->setDescription(t('Tipo de recompensa que recibe el usuario referido.'))
      ->setSetting('allowed_values', [
        'discount_percentage' => t('Descuento Porcentual'),
        'discount_fixed' => t('Descuento Fijo'),
        'credit' => t('Crédito'),
        'free_month' => t('Mes Gratis'),
        'custom' => t('Personalizado'),
      ])
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Valor de recompensa para el referido ---
    $fields['referee_reward_value'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Valor de Recompensa para Referido'))
      ->setDescription(t('Valor numérico de la recompensa para el usuario referido.'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Mínimo de referidos para subir de nivel ---
    $fields['min_referrals_for_tier'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Mínimo Referidos para Nivel'))
      ->setDescription(t('Número mínimo de referidos necesarios para acceder al siguiente nivel.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 8])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Máximo de recompensas por usuario (0 = ilimitado) ---
    $fields['max_rewards_per_user'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Máximo Recompensas por Usuario'))
      ->setDescription(t('Límite de recompensas que un usuario puede acumular. 0 = ilimitado.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 9])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Programa activo ---
    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Activo'))
      ->setDescription(t('Indica si el programa está activo y aceptando referidos.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Fecha de inicio del programa ---
    $fields['starts_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de Inicio'))
      ->setDescription(t('Fecha a partir de la cual el programa está vigente.'))
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Fecha de fin del programa ---
    $fields['ends_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de Fin'))
      ->setDescription(t('Fecha en la que el programa deja de estar vigente.'))
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Términos y condiciones ---
    $fields['terms_conditions'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Términos y Condiciones'))
      ->setDescription(t('Texto legal de términos y condiciones del programa.'))
      ->setDisplayOptions('form', ['weight' => 13])
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
   * Comprueba si el programa está activo y dentro de su periodo de vigencia.
   *
   * ESTRUCTURA: Método helper que evalúa is_active + rango de fechas.
   * LÓGICA: Devuelve TRUE si is_active es TRUE y la fecha actual está
   *   dentro del rango starts_at / ends_at (si están definidos).
   * RELACIONES: Consumido por RewardProcessingService y ReferralTrackingService.
   *
   * @return bool
   *   TRUE si el programa está activo y vigente.
   */
  public function isCurrentlyActive(): bool {
    if (!$this->get('is_active')->value) {
      return FALSE;
    }

    $now = new \DateTime();

    $starts_at = $this->get('starts_at')->value;
    if ($starts_at && new \DateTime($starts_at) > $now) {
      return FALSE;
    }

    $ends_at = $this->get('ends_at')->value;
    if ($ends_at && new \DateTime($ends_at) < $now) {
      return FALSE;
    }

    return TRUE;
  }

}
