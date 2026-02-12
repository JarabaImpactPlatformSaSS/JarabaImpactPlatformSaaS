<?php

declare(strict_types=1);

namespace Drupal\jaraba_security_compliance\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de creación/edición de ComplianceAssessment.
 *
 * PROPOSITO:
 * Permite a administradores crear y editar evaluaciones de compliance,
 * agrupando campos en secciones lógicas para mejor usabilidad.
 *
 * LOGICA:
 * - Grupo 1: Información del Control (framework, control ID, nombre, tenant)
 * - Grupo 2: Evaluación (estado, evidencia, evaluador, fecha)
 * - Grupo 3: Programación (próxima revisión)
 */
class ComplianceAssessmentForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    // Grupo 1: Información del Control.
    $form['control_info'] = [
      '#type' => 'details',
      '#title' => $this->t('Información del Control'),
      '#open' => TRUE,
      '#weight' => -30,
    ];
    $form['framework']['#group'] = 'control_info';
    $form['control_id']['#group'] = 'control_info';
    $form['control_name']['#group'] = 'control_info';
    $form['tenant_id']['#group'] = 'control_info';

    // Grupo 2: Evaluación.
    $form['evaluation'] = [
      '#type' => 'details',
      '#title' => $this->t('Evaluación'),
      '#open' => TRUE,
      '#weight' => -20,
    ];
    $form['assessment_status']['#group'] = 'evaluation';
    $form['evidence_notes']['#group'] = 'evaluation';
    $form['assessed_by']['#group'] = 'evaluation';
    $form['assessed_at']['#group'] = 'evaluation';

    // Grupo 3: Programación.
    $form['scheduling'] = [
      '#type' => 'details',
      '#title' => $this->t('Programación de Revisión'),
      '#open' => TRUE,
      '#weight' => -10,
    ];
    $form['next_review']['#group'] = 'scheduling';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $entity = $this->entity;
    $frameworkLabels = [
      'soc2' => 'SOC 2',
      'iso27001' => 'ISO 27001',
      'ens' => 'ENS',
      'gdpr' => 'GDPR',
    ];
    $framework = $entity->get('framework')->value ?? '';
    $controlId = $entity->get('control_id')->value ?? '';
    $label = ($frameworkLabels[$framework] ?? $framework) . ' - ' . $controlId;

    $message = $result === SAVED_NEW
      ? $this->t('Evaluación de compliance %label creada.', ['%label' => $label])
      : $this->t('Evaluación de compliance %label actualizada.', ['%label' => $label]);

    $this->messenger()->addStatus($message);
    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $result;
  }

}
