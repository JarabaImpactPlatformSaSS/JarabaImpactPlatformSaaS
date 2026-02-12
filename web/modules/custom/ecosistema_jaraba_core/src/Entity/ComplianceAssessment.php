<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad ComplianceAssessment.
 *
 * Evaluación de cumplimiento normativo para un tenant.
 * Almacena resultados de auditorías contra marcos como SOC2, ISO27001,
 * ENS y GDPR, incluyendo hallazgos y planes de remediación.
 *
 * @ContentEntityType(
 *   id = "compliance_assessment",
 *   label = @Translation("Evaluación de Compliance"),
 *   label_collection = @Translation("Evaluaciones de Compliance"),
 *   label_singular = @Translation("evaluación de compliance"),
 *   label_plural = @Translation("evaluaciones de compliance"),
 *   handlers = {
 *     "list_builder" = "Drupal\ecosistema_jaraba_core\ComplianceAssessmentListBuilder",
 *     "form" = {
 *       "default" = "Drupal\ecosistema_jaraba_core\Form\ComplianceAssessmentForm",
 *       "add" = "Drupal\ecosistema_jaraba_core\Form\ComplianceAssessmentForm",
 *       "edit" = "Drupal\ecosistema_jaraba_core\Form\ComplianceAssessmentForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\ecosistema_jaraba_core\Access\ComplianceAssessmentAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "compliance_assessment",
 *   admin_permission = "administer tenants",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/config/compliance-assessments",
 *     "canonical" = "/admin/config/compliance-assessments/{compliance_assessment}",
 *     "add-form" = "/admin/config/compliance-assessments/add",
 *     "edit-form" = "/admin/config/compliance-assessments/{compliance_assessment}/edit",
 *     "delete-form" = "/admin/config/compliance-assessments/{compliance_assessment}/delete",
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

    // Tenant asociado.
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant (grupo) evaluado.'))
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
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Fecha de evaluación.
    $fields['assessment_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de Evaluación'))
      ->setDescription(t('Fecha en que se realizó la evaluación.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'datetime_default',
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Estado de la evaluación.
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado actual de la evaluación de compliance.'))
      ->setRequired(TRUE)
      ->setDefaultValue('pending')
      ->setSetting('allowed_values', [
        'pending' => 'Pendiente',
        'in_progress' => 'En Progreso',
        'completed' => 'Completada',
        'remediation' => 'En Remediación',
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

    // Puntuación global (0-100).
    $fields['overall_score'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Puntuación Global'))
      ->setDescription(t('Puntuación de cumplimiento de 0 a 100.'))
      ->setSetting('min', 0)
      ->setSetting('max', 100)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_integer',
        'weight' => 4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Hallazgos en formato JSON.
    $fields['findings'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Hallazgos'))
      ->setDescription(t('JSON con hallazgos detallados de la evaluación.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'basic_string',
        'weight' => 5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 5,
        'settings' => [
          'rows' => 8,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Plan de remediación en formato JSON.
    $fields['remediation_plan'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Plan de Remediación'))
      ->setDescription(t('JSON con plan de acciones correctivas.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'basic_string',
        'weight' => 6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 6,
        'settings' => [
          'rows' => 8,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Evaluador responsable.
    $fields['assessor'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Evaluador'))
      ->setDescription(t('Nombre del evaluador o auditor responsable.'))
      ->setSettings([
        'max_length' => 255,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 7,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Próxima revisión.
    $fields['next_review_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Próxima Revisión'))
      ->setDescription(t('Fecha programada para la próxima revisión de compliance.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'datetime_default',
        'weight' => 8,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 8,
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
   * Obtiene el estado actual de la evaluación.
   *
   * @return string
   *   El estado: pending, in_progress, completed o remediation.
   */
  public function getStatus(): string {
    return $this->get('status')->value ?? 'pending';
  }

  /**
   * Obtiene la puntuación global.
   *
   * @return int|null
   *   La puntuación de 0 a 100, o NULL si no se ha asignado.
   */
  public function getOverallScore(): ?int {
    $value = $this->get('overall_score')->value;
    return $value !== NULL ? (int) $value : NULL;
  }

  /**
   * Obtiene los hallazgos decodificados como array.
   *
   * @return array
   *   Array con los hallazgos de la evaluación.
   */
  public function getDecodedFindings(): array {
    $raw = $this->get('findings')->value;
    if (empty($raw)) {
      return [];
    }
    $decoded = json_decode($raw, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

  /**
   * Obtiene el plan de remediación decodificado como array.
   *
   * @return array
   *   Array con el plan de acciones correctivas.
   */
  public function getDecodedRemediationPlan(): array {
    $raw = $this->get('remediation_plan')->value;
    if (empty($raw)) {
      return [];
    }
    $decoded = json_decode($raw, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

  /**
   * Obtiene el nombre del evaluador.
   *
   * @return string
   *   El nombre del evaluador responsable.
   */
  public function getAssessor(): string {
    return $this->get('assessor')->value ?? '';
  }

}
