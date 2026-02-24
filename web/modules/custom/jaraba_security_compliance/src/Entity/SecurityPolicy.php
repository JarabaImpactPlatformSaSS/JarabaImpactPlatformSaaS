<?php

declare(strict_types=1);

namespace Drupal\jaraba_security_compliance\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad SecurityPolicy (v2).
 *
 * Permite definir políticas de seguridad a nivel global o por tenant,
 * cubriendo controles de acceso, protección de datos, respuesta a
 * incidentes, cifrado y retención. Cada política tiene versionado.
 *
 * Migrada desde ecosistema_jaraba_core\Entity\SecurityPolicy con
 * campos ampliados (content, version, effective_date, review_date,
 * policy_status) y tabla security_policy_v2.
 *
 * @ContentEntityType(
 *   id = "security_policy_v2",
 *   label = @Translation("Política de Seguridad"),
 *   label_collection = @Translation("Políticas de Seguridad"),
 *   label_singular = @Translation("política de seguridad"),
 *   label_plural = @Translation("políticas de seguridad"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_security_compliance\ListBuilder\SecurityPolicyListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_security_compliance\Form\SecurityPolicyForm",
 *       "add" = "Drupal\jaraba_security_compliance\Form\SecurityPolicyForm",
 *       "edit" = "Drupal\jaraba_security_compliance\Form\SecurityPolicyForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_security_compliance\Access\SecurityPolicyAccessControlHandler",
 *   },
 *   base_table = "security_policy_v2",
 *   admin_permission = "manage security policies",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/security-policies",
 *     "canonical" = "/admin/content/security-policies/{security_policy_v2}",
 *     "add-form" = "/admin/content/security-policies/add",
 *     "edit-form" = "/admin/content/security-policies/{security_policy_v2}/edit",
 *     "delete-form" = "/admin/content/security-policies/{security_policy_v2}/delete",
 *   },
 *   field_ui_base_route = "entity.security_policy_v2.settings",
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
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Tipo de política.
    $fields['policy_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Política'))
      ->setDescription(t('Categoría de la política de seguridad.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'access_control' => t('Control de Acceso'),
        'data_protection' => t('Protección de Datos'),
        'incident_response' => t('Respuesta a Incidentes'),
        'encryption' => t('Cifrado'),
        'retention' => t('Retención'),
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => -9,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Contenido de la política.
    $fields['content'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Contenido'))
      ->setDescription(t('Texto completo de la política de seguridad.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => -8,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -8,
        'settings' => [
          'rows' => 12,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Versión de la política.
    $fields['version'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Versión'))
      ->setDescription(t('Número de versión de la política (e.g. 1.0, 2.1).'))
      ->setSettings([
        'max_length' => 32,
      ])
      ->setDefaultValue('1.0')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -7,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Fecha efectiva.
    $fields['effective_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha Efectiva'))
      ->setDescription(t('Fecha desde la cual la política entra en vigor.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'datetime_default',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Fecha de revisión.
    $fields['review_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de Revisión'))
      ->setDescription(t('Fecha programada para la próxima revisión de la política.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'datetime_default',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Estado de la política.
    $fields['policy_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado actual de la política.'))
      ->setRequired(TRUE)
      ->setDefaultValue('draft')
      ->setSetting('allowed_values', [
        'draft' => t('Borrador'),
        'active' => t('Activa'),
        'archived' => t('Archivada'),
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Tenant asociado (vacío = política global).
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Dejar vacío para política global.'))
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -3,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

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
   * Obtiene el tipo de política.
   *
   * @return string
   *   El tipo: access_control, data_protection, incident_response,
   *   encryption, o retention.
   */
  public function getPolicyType(): string {
    return $this->get('policy_type')->value ?? '';
  }

  /**
   * Obtiene la versión de la política.
   *
   * @return string
   *   El número de versión.
   */
  public function getVersion(): string {
    return $this->get('version')->value ?? '1.0';
  }

  /**
   * Obtiene el estado de la política.
   *
   * @return string
   *   El estado: draft, active, o archived.
   */
  public function getPolicyStatus(): string {
    return $this->get('policy_status')->value ?? 'draft';
  }

  /**
   * Verifica si la política está activa.
   *
   * @return bool
   *   TRUE si la política tiene estado 'active'.
   */
  public function isActive(): bool {
    return $this->getPolicyStatus() === 'active';
  }

  /**
   * Obtiene el contenido de la política.
   *
   * @return string
   *   El texto completo de la política.
   */
  public function getContent(): string {
    return $this->get('content')->value ?? '';
  }

  /**
   * Obtiene el ID del tenant.
   *
   * @return int|null
   *   El ID del tenant, o NULL para política global.
   */
  public function getTenantId(): ?int {
    $value = $this->get('tenant_id')->target_id;
    return $value !== NULL ? (int) $value : NULL;
  }

}
