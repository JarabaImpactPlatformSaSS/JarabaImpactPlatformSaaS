<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Form;

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
 * - Grupo 1: Información General (tenant, marco, fecha, evaluador, estado)
 * - Grupo 2: Resultados (puntuación, hallazgos en JSON)
 * - Grupo 3: Remediación (plan de remediación en JSON, próxima revisión)
 */
class ComplianceAssessmentForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    // Grupo 1: Información General.
    $form['general_info'] = [
      '#type' => 'details',
      '#title' => $this->t('Información General'),
      '#open' => TRUE,
      '#weight' => -30,
    ];
    $form['tenant_id']['#group'] = 'general_info';
    $form['framework']['#group'] = 'general_info';
    $form['assessment_date']['#group'] = 'general_info';
    $form['assessor']['#group'] = 'general_info';
    $form['status']['#group'] = 'general_info';

    // Grupo 2: Resultados.
    $form['results'] = [
      '#type' => 'details',
      '#title' => $this->t('Resultados de la Evaluación'),
      '#open' => TRUE,
      '#weight' => -20,
    ];
    $form['overall_score']['#group'] = 'results';
    $form['findings']['#group'] = 'results';

    // Descripción adicional para campos JSON.
    if (isset($form['findings']['widget'][0]['value'])) {
      $form['findings']['widget'][0]['value']['#description'] = $this->t('Introduzca los hallazgos en formato JSON. Ejemplo: [{"control":"AC-1","status":"fail","detail":"Falta política de acceso"}]');
    }

    // Grupo 3: Remediación.
    $form['remediation'] = [
      '#type' => 'details',
      '#title' => $this->t('Plan de Remediación'),
      '#open' => TRUE,
      '#weight' => -10,
    ];
    $form['remediation_plan']['#group'] = 'remediation';
    $form['next_review_date']['#group'] = 'remediation';

    // Descripción adicional para plan de remediación.
    if (isset($form['remediation_plan']['widget'][0]['value'])) {
      $form['remediation_plan']['widget'][0]['value']['#description'] = $this->t('Introduzca el plan de acciones en formato JSON. Ejemplo: [{"action":"Implementar MFA","responsible":"CTO","deadline":"2025-06-01","priority":"high"}]');
    }

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
    $label = $frameworkLabels[$framework] ?? $framework;

    $message = $result === SAVED_NEW
      ? $this->t('Evaluación de compliance %label creada.', ['%label' => $label])
      : $this->t('Evaluación de compliance %label actualizada.', ['%label' => $label]);

    $this->messenger()->addStatus($message);
    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $result;
  }

}
