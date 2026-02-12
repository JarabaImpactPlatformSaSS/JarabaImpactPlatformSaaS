<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad SecurityPolicy.
 *
 * Permite definir políticas de seguridad a nivel global o por tenant,
 * cubriendo contraseñas, sesiones, acceso y datos. Cada política
 * puede aplicarse en modo enforce, audit o disabled.
 *
 * @ContentEntityType(
 *   id = "security_policy",
 *   label = @Translation("Política de Seguridad"),
 *   label_collection = @Translation("Políticas de Seguridad"),
 *   label_singular = @Translation("política de seguridad"),
 *   label_plural = @Translation("políticas de seguridad"),
 *   handlers = {
 *     "list_builder" = "Drupal\ecosistema_jaraba_core\SecurityPolicyListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\ecosistema_jaraba_core\Form\SecurityPolicyForm",
 *       "add" = "Drupal\ecosistema_jaraba_core\Form\SecurityPolicyForm",
 *       "edit" = "Drupal\ecosistema_jaraba_core\Form\SecurityPolicyForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\ecosistema_jaraba_core\Access\SecurityPolicyAccessControlHandler",
 *   },
 *   base_table = "security_policy",
 *   admin_permission = "administer tenants",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/config/security-policies",
 *     "canonical" = "/admin/config/security-policies/{security_policy}",
 *     "add-form" = "/admin/config/security-policies/add",
 *     "edit-form" = "/admin/config/security-policies/{security_policy}/edit",
 *     "delete-form" = "/admin/config/security-policies/{security_policy}/delete",
 *   },
 * )
 */
class SecurityPolicy extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Nombre de la política.
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre'))
      ->setDescription(t('Nombre descriptivo de la política de seguridad.'))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 255,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -10,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ]);

    // Tenant asociado (vacío = política global).
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Dejar vacío para política global.'))
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => -9,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -9,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ]);

    // Ámbito de la política.
    $fields['scope'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Ámbito'))
      ->setDescription(t('Alcance de aplicación de la política.'))
      ->setRequired(TRUE)
      ->setDefaultValue('global')
      ->setSetting('allowed_values', [
        'global' => t('Global'),
        'tenant' => t('Tenant'),
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => -8,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -8,
      ]);

    // Tipo de política.
    $fields['policy_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Política'))
      ->setDescription(t('Categoría de la política de seguridad.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'password' => t('Contraseña'),
        'session' => t('Sesión'),
        'access' => t('Acceso'),
        'data' => t('Datos'),
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => -7,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -7,
      ]);

    // Reglas en formato JSON.
    $fields['rules'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Reglas'))
      ->setDescription(t('Reglas en formato JSON.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'basic_string',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -6,
        'settings' => [
          'rows' => 6,
        ],
      ]);

    // Modo de aplicación.
    $fields['enforcement'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Modo de Aplicación'))
      ->setDescription(t('Define cómo se aplica la política.'))
      ->setRequired(TRUE)
      ->setDefaultValue('audit')
      ->setSetting('allowed_values', [
        'enforce' => t('Aplicar'),
        'audit' => t('Auditar'),
        'disabled' => t('Desactivado'),
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -5,
      ]);

    // Estado activo/inactivo.
    $fields['active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Activa'))
      ->setDescription(t('Indica si la política está activa.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'boolean',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -4,
      ]);

    // Timestamps.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'))
      ->setDescription(t('Fecha de creación de la política.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Actualizado'))
      ->setDescription(t('Fecha de la última modificación.'));

    return $fields;
  }

  /**
   * Obtiene el nombre de la política.
   *
   * @return string
   *   El nombre descriptivo de la política.
   */
  public function getName(): string {
    return $this->get('name')->value ?? '';
  }

  /**
   * Verifica si la política está activa.
   *
   * @return bool
   *   TRUE si la política está activa.
   */
  public function isActive(): bool {
    return (bool) $this->get('active')->value;
  }

  /**
   * Obtiene el ámbito de la política.
   *
   * @return string
   *   El ámbito: 'global' o 'tenant'.
   */
  public function getScope(): string {
    return $this->get('scope')->value ?? 'global';
  }

  /**
   * Obtiene el tipo de política.
   *
   * @return string
   *   El tipo: 'password', 'session', 'access' o 'data'.
   */
  public function getPolicyType(): string {
    return $this->get('policy_type')->value ?? '';
  }

  /**
   * Obtiene las reglas decodificadas como array.
   *
   * @return array
   *   Array asociativo con las reglas de la política.
   */
  public function getDecodedRules(): array {
    $raw = $this->get('rules')->value;
    if (empty($raw)) {
      return [];
    }
    $decoded = json_decode($raw, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

  /**
   * Obtiene el modo de aplicación.
   *
   * @return string
   *   El modo: 'enforce', 'audit' o 'disabled'.
   */
  public function getEnforcement(): string {
    return $this->get('enforcement')->value ?? 'audit';
  }

}
