<?php

declare(strict_types=1);

namespace Drupal\jaraba_security_compliance\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad RiskAssessment (ISO 27001).
 *
 * Registro de riesgos identificados conforme a ISO 27001, incluyendo
 * activo, amenaza, vulnerabilidad, puntuacion de riesgo (likelihood x impact),
 * nivel de riesgo, estrategia de tratamiento y riesgo residual.
 *
 * @ContentEntityType(
 *   id = "risk_assessment",
 *   label = @Translation("Risk Assessment"),
 *   label_collection = @Translation("Risk Assessments"),
 *   label_singular = @Translation("risk assessment"),
 *   label_plural = @Translation("risk assessments"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_security_compliance\ListBuilder\RiskAssessmentListBuilder",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_security_compliance\Access\RiskAssessmentAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "risk_assessment",
 *   admin_permission = "administer security compliance",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/risk-assessments",
 *     "canonical" = "/admin/content/risk-assessments/{risk_assessment}",
 *     "add-form" = "/admin/content/risk-assessments/add",
 *     "edit-form" = "/admin/content/risk-assessments/{risk_assessment}/edit",
 *     "delete-form" = "/admin/content/risk-assessments/{risk_assessment}/delete",
 *   },
 * )
 */
class RiskAssessment extends ContentEntityBase implements RiskAssessmentInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Tenant asociado.
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant (grupo) al que pertenece esta evaluacion de riesgo.'))
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

    // Activo evaluado.
    $fields['asset'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Activo'))
      ->setDescription(t('Nombre del activo de informacion evaluado.'))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 255,
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

    // Amenaza identificada.
    $fields['threat'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Amenaza'))
      ->setDescription(t('Amenaza identificada para el activo.'))
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

    // Vulnerabilidad identificada.
    $fields['vulnerability'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Vulnerabilidad'))
      ->setDescription(t('Vulnerabilidad que podria ser explotada por la amenaza.'))
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

    // Probabilidad (1-5).
    $fields['likelihood'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Probabilidad'))
      ->setDescription(t('Probabilidad de ocurrencia (1=Muy baja, 5=Muy alta).'))
      ->setRequired(TRUE)
      ->setDefaultValue(1)
      ->setSetting('min', 1)
      ->setSetting('max', 5)
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

    // Impacto (1-5).
    $fields['impact'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Impacto'))
      ->setDescription(t('Impacto en caso de materializacion (1=Insignificante, 5=Catastrofico).'))
      ->setRequired(TRUE)
      ->setDefaultValue(1)
      ->setSetting('min', 1)
      ->setSetting('max', 5)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_integer',
        'weight' => 5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Puntuacion de riesgo (computed: likelihood * impact).
    $fields['risk_score'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Puntuacion de Riesgo'))
      ->setDescription(t('Puntuacion calculada: probabilidad x impacto (1-25).'))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_integer',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('view', TRUE);

    // Nivel de riesgo.
    $fields['risk_level'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Nivel de Riesgo'))
      ->setDescription(t('Clasificacion del nivel de riesgo.'))
      ->setRequired(TRUE)
      ->setDefaultValue('low')
      ->setSetting('allowed_values', [
        'low' => t('Bajo'),
        'medium' => t('Medio'),
        'high' => t('Alto'),
        'critical' => t('Critico'),
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

    // Estrategia de tratamiento.
    $fields['treatment'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tratamiento'))
      ->setDescription(t('Estrategia de tratamiento del riesgo.'))
      ->setRequired(TRUE)
      ->setDefaultValue('mitigate')
      ->setSetting('allowed_values', [
        'accept' => t('Aceptar'),
        'mitigate' => t('Mitigar'),
        'transfer' => t('Transferir'),
        'avoid' => t('Evitar'),
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 8,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Riesgo residual.
    $fields['residual_risk'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Riesgo Residual'))
      ->setDescription(t('Puntuacion de riesgo residual tras aplicar tratamiento.'))
      ->setDefaultValue(0)
      ->setSetting('min', 0)
      ->setSetting('max', 25)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_integer',
        'weight' => 9,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Plan de mitigacion.
    $fields['mitigation_plan'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Plan de Mitigacion'))
      ->setDescription(t('Descripcion del plan de mitigacion o tratamiento.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 10,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 10,
        'settings' => [
          'rows' => 6,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Propietario del riesgo.
    $fields['owner'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Propietario'))
      ->setDescription(t('Usuario responsable de gestionar este riesgo.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 11,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 11,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Fecha de revision.
    $fields['review_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de Revision'))
      ->setDescription(t('Proxima fecha de revision del riesgo.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'datetime_default',
        'weight' => 12,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 12,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Estado del riesgo.
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado actual del riesgo.'))
      ->setRequired(TRUE)
      ->setDefaultValue('open')
      ->setSetting('allowed_values', [
        'open' => t('Abierto'),
        'mitigating' => t('En Mitigacion'),
        'accepted' => t('Aceptado'),
        'closed' => t('Cerrado'),
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 13,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 13,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Timestamps.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'))
      ->setDescription(t('Fecha de creacion del registro de riesgo.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Actualizado'))
      ->setDescription(t('Fecha de ultima modificacion.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getAsset(): string {
    return $this->get('asset')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getThreat(): string {
    return $this->get('threat')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getVulnerability(): string {
    return $this->get('vulnerability')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getLikelihood(): int {
    return (int) ($this->get('likelihood')->value ?? 1);
  }

  /**
   * {@inheritdoc}
   */
  public function getImpact(): int {
    return (int) ($this->get('impact')->value ?? 1);
  }

  /**
   * {@inheritdoc}
   */
  public function getRiskScore(): int {
    return (int) ($this->get('risk_score')->value ?? 0);
  }

  /**
   * {@inheritdoc}
   */
  public function getRiskLevel(): string {
    return $this->get('risk_level')->value ?? 'low';
  }

  /**
   * {@inheritdoc}
   */
  public function getTreatment(): string {
    return $this->get('treatment')->value ?? 'mitigate';
  }

  /**
   * {@inheritdoc}
   */
  public function getResidualRisk(): int {
    return (int) ($this->get('residual_risk')->value ?? 0);
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(): string {
    return $this->get('status')->value ?? 'open';
  }

  /**
   * {@inheritdoc}
   */
  public function getTenantId(): ?int {
    $value = $this->get('tenant_id')->target_id;
    return $value !== NULL ? (int) $value : NULL;
  }

}
