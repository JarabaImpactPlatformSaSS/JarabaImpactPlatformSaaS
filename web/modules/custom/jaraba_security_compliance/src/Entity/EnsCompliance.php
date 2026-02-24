<?php

declare(strict_types=1);

namespace Drupal\jaraba_security_compliance\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad EnsCompliance.
 *
 * Tracking de cumplimiento del Esquema Nacional de Seguridad (ENS),
 * Real Decreto 311/2022. Almacena el estado de implementacion de
 * cada medida de seguridad por tenant, con evidencias, tipo de
 * verificacion y fechas de auditoria.
 *
 * @ContentEntityType(
 *   id = "ens_compliance",
 *   label = @Translation("ENS Compliance Measure"),
 *   label_collection = @Translation("ENS Compliance Measures"),
 *   label_singular = @Translation("ENS compliance measure"),
 *   label_plural = @Translation("ENS compliance measures"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_security_compliance\ListBuilder\EnsComplianceListBuilder",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_security_compliance\Access\EnsComplianceAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "ens_compliance",
 *   admin_permission = "administer security compliance",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ens-compliance",
 *     "canonical" = "/admin/content/ens-compliance/{ens_compliance}",
 *     "add-form" = "/admin/content/ens-compliance/add",
 *     "edit-form" = "/admin/content/ens-compliance/{ens_compliance}/edit",
 *     "delete-form" = "/admin/content/ens-compliance/{ens_compliance}/delete",
 *   },
 *   field_ui_base_route = "entity.ens_compliance.settings",
 * )
 */
class EnsCompliance extends ContentEntityBase implements EnsComplianceInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Tenant asociado.
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant (grupo) al que pertenece esta medida ENS.'))
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Identificador de la medida ENS (e.g. "org.1", "op.acc.5").
    $fields['measure_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ID de Medida'))
      ->setDescription(t('Identificador de la medida ENS (e.g. org.1, op.acc.5, mp.com.1).'))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 20,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Categoria de la medida.
    $fields['category'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Categoria'))
      ->setDescription(t('Categoria de la medida ENS.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'organizational' => t('Marco Organizativo'),
        'operational' => t('Marco Operacional'),
        'protection' => t('Medidas de Proteccion'),
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Nombre de la medida.
    $fields['measure_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre de Medida'))
      ->setDescription(t('Nombre descriptivo de la medida de seguridad.'))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 255,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Nivel ENS requerido.
    $fields['required_level'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Nivel Requerido'))
      ->setDescription(t('Nivel ENS requerido para esta medida.'))
      ->setRequired(TRUE)
      ->setDefaultValue('medium')
      ->setSetting('allowed_values', [
        'basic' => t('Basico'),
        'medium' => t('Medio'),
        'high' => t('Alto'),
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Estado actual de implementacion.
    $fields['current_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado Actual'))
      ->setDescription(t('Estado de implementacion de la medida.'))
      ->setRequired(TRUE)
      ->setDefaultValue('not_implemented')
      ->setSetting('allowed_values', [
        'implemented' => t('Implementada'),
        'partial' => t('Parcialmente implementada'),
        'not_implemented' => t('No implementada'),
        'not_applicable' => t('No aplica'),
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Evidencia (JSON).
    $fields['evidence'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Evidencia'))
      ->setDescription(t('Evidencia de cumplimiento en formato JSON.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 6,
        'settings' => [
          'rows' => 6,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Tipo de evidencia.
    $fields['evidence_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Evidencia'))
      ->setDescription(t('Metodo de verificacion de la evidencia.'))
      ->setDefaultValue('manual')
      ->setSetting('allowed_values', [
        'automated' => t('Automatizada'),
        'manual' => t('Manual'),
        'hybrid' => t('Hibrida'),
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 7,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Responsable.
    $fields['responsible'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Responsable'))
      ->setDescription(t('Persona o equipo responsable de esta medida.'))
      ->setSettings([
        'max_length' => 255,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 8,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Ultima auditoria.
    $fields['last_audit'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Ultima Auditoria'))
      ->setDescription(t('Fecha de la ultima auditoria realizada.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'datetime_default',
        'weight' => 9,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Proxima auditoria.
    $fields['next_audit'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Proxima Auditoria'))
      ->setDescription(t('Fecha programada para la proxima auditoria.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'datetime_default',
        'weight' => 10,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Notas.
    $fields['notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Notas'))
      ->setDescription(t('Notas y observaciones adicionales.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 11,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 11,
        'settings' => [
          'rows' => 4,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Timestamps.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'))
      ->setDescription(t('Fecha de creacion del registro.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Actualizado'))
      ->setDescription(t('Fecha de ultima modificacion.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getMeasureId(): string {
    return $this->get('measure_id')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getCategory(): string {
    return $this->get('category')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getMeasureName(): string {
    return $this->get('measure_name')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredLevel(): string {
    return $this->get('required_level')->value ?? 'medium';
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentStatus(): string {
    return $this->get('current_status')->value ?? 'not_implemented';
  }

  /**
   * {@inheritdoc}
   */
  public function getEvidenceType(): string {
    return $this->get('evidence_type')->value ?? 'manual';
  }

  /**
   * {@inheritdoc}
   */
  public function getResponsible(): string {
    return $this->get('responsible')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getTenantId(): ?int {
    $value = $this->get('tenant_id')->target_id;
    return $value !== NULL ? (int) $value : NULL;
  }

}
