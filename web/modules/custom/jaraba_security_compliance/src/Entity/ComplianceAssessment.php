<?php

declare(strict_types=1);

namespace Drupal\jaraba_security_compliance\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad ComplianceAssessment (v2).
 *
 * Evaluación de cumplimiento normativo a nivel de control individual.
 * Almacena resultados de auditorías contra marcos como SOC2, ISO27001,
 * ENS y GDPR, con estado por control y notas de evidencia.
 *
 * Migrada desde ecosistema_jaraba_core\Entity\ComplianceAssessment
 * con campos ampliados (control_id, control_name, assessment_status,
 * evidence_notes) y tabla compliance_assessment_v2.
 *
 * @ContentEntityType(
 *   id = "compliance_assessment_v2",
 *   label = @Translation("Evaluación de Compliance"),
 *   label_collection = @Translation("Evaluaciones de Compliance"),
 *   label_singular = @Translation("evaluación de compliance"),
 *   label_plural = @Translation("evaluaciones de compliance"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_security_compliance\ListBuilder\ComplianceAssessmentListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_security_compliance\Form\ComplianceAssessmentForm",
 *       "add" = "Drupal\jaraba_security_compliance\Form\ComplianceAssessmentForm",
 *       "edit" = "Drupal\jaraba_security_compliance\Form\ComplianceAssessmentForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_security_compliance\Access\ComplianceAssessmentAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "compliance_assessment_v2",
 *   admin_permission = "administer security compliance",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/compliance-assessments",
 *     "canonical" = "/admin/content/compliance-assessments/{compliance_assessment_v2}",
 *     "add-form" = "/admin/content/compliance-assessments/add",
 *     "edit-form" = "/admin/content/compliance-assessments/{compliance_assessment_v2}/edit",
 *     "delete-form" = "/admin/content/compliance-assessments/{compliance_assessment_v2}/delete",
 *   },
 * )
 */
class ComplianceAssessment extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Marco normativo.
    $fields['framework'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Marco Normativo'))
      ->setDescription(t('Marco de cumplimiento evaluado.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'soc2' => 'SOC 2',
        'iso27001' => 'ISO 27001',
        'ens' => 'ENS',
        'gdpr' => 'GDPR',
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Identificador del control.
    $fields['control_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ID de Control'))
      ->setDescription(t('Identificador único del control (e.g. SOC2-CC6.1, A.5.1).'))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 64,
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

    // Nombre del control.
    $fields['control_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre del Control'))
      ->setDescription(t('Nombre descriptivo del control evaluado.'))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 255,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Estado de la evaluación del control.
    $fields['assessment_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado de Evaluación'))
      ->setDescription(t('Resultado de la evaluación del control.'))
      ->setRequired(TRUE)
      ->setDefaultValue('not_assessed')
      ->setSetting('allowed_values', [
        'pass' => t('Cumple'),
        'fail' => t('No Cumple'),
        'warning' => t('Advertencia'),
        'not_assessed' => t('No Evaluado'),
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Notas de evidencia.
    $fields['evidence_notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Notas de Evidencia'))
      ->setDescription(t('Descripción de la evidencia y observaciones de la evaluación.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 4,
        'settings' => [
          'rows' => 6,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Evaluador (usuario).
    $fields['assessed_by'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Evaluado por'))
      ->setDescription(t('Usuario que realizó la evaluación.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Fecha de evaluación.
    $fields['assessed_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Fecha de Evaluación'))
      ->setDescription(t('Momento en que se realizó la evaluación.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'settings' => [
          'date_format' => 'medium',
        ],
        'weight' => 6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Próxima revisión.
    $fields['next_review'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Próxima Revisión'))
      ->setDescription(t('Fecha programada para la próxima revisión.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'settings' => [
          'date_format' => 'medium',
        ],
        'weight' => 7,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Tenant asociado.
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant (grupo) evaluado.'))
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 8,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 8,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Timestamps estándar.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'))
      ->setDescription(t('Fecha de creación del registro.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Actualizado'))
      ->setDescription(t('Fecha de última modificación.'));

    return $fields;
  }

  /**
   * Obtiene el marco normativo.
   *
   * @return string
   *   El código del marco normativo (soc2, iso27001, ens, gdpr).
   */
  public function getFramework(): string {
    return $this->get('framework')->value ?? '';
  }

  /**
   * Obtiene el identificador del control.
   *
   * @return string
   *   El ID del control evaluado.
   */
  public function getControlId(): string {
    return $this->get('control_id')->value ?? '';
  }

  /**
   * Obtiene el nombre del control.
   *
   * @return string
   *   El nombre descriptivo del control.
   */
  public function getControlName(): string {
    return $this->get('control_name')->value ?? '';
  }

  /**
   * Obtiene el estado de la evaluación.
   *
   * @return string
   *   El estado: pass, fail, warning o not_assessed.
   */
  public function getAssessmentStatus(): string {
    return $this->get('assessment_status')->value ?? 'not_assessed';
  }

  /**
   * Obtiene las notas de evidencia.
   *
   * @return string
   *   Las notas de evidencia de la evaluación.
   */
  public function getEvidenceNotes(): string {
    return $this->get('evidence_notes')->value ?? '';
  }

  /**
   * Obtiene el ID del tenant.
   *
   * @return int|null
   *   El ID del tenant, o NULL si no hay tenant asociado.
   */
  public function getTenantId(): ?int {
    $value = $this->get('tenant_id')->target_id;
    return $value !== NULL ? (int) $value : NULL;
  }

}
