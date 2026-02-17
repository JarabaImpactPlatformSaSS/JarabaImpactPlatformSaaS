<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para gestionar aprobaciones de acciones de agentes.
 *
 * Estructura: ContentEntityForm con 3 fieldsets: solicitud,
 *   evaluacion y revision. Permite aprobar o rechazar acciones
 *   que requieren intervencion humana.
 *
 * Logica: La solicitud contiene los datos generados por el agente.
 *   La evaluacion permite al revisor valorar el riesgo y decidir.
 *   La revision registra quien y cuando se tomo la decision.
 */
class AgentApprovalForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   *
   * Estructura: Construye el formulario con 3 fieldsets tematicos.
   * Logica: Agrupa campos de solicitud (generados por el agente),
   *   evaluacion (decision humana) y revision (metadatos).
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    // --- Fieldset 1: Solicitud ---
    $form['request_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Solicitud'),
      '#open' => TRUE,
      '#weight' => -20,
    ];

    $request_fields = ['execution_id', 'agent_id', 'action_description', 'reasoning'];
    foreach ($request_fields as $field) {
      if (isset($form[$field])) {
        $form['request_section'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    // --- Fieldset 2: Evaluacion ---
    $form['evaluation_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Evaluacion'),
      '#open' => TRUE,
      '#weight' => -15,
    ];

    $evaluation_fields = ['risk_assessment', 'status', 'review_notes'];
    foreach ($evaluation_fields as $field) {
      if (isset($form[$field])) {
        $form['evaluation_section'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    // --- Fieldset 3: Revision ---
    $form['review_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Revision'),
      '#open' => FALSE,
      '#weight' => -10,
    ];

    $review_fields = ['reviewed_by', 'reviewed_at', 'expires_at'];
    foreach ($review_fields as $field) {
      if (isset($form[$field])) {
        $form['review_section'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Estructura: Guarda la entidad y muestra mensaje de confirmacion.
   * Logica: Mensaje generico de actualizacion ya que las aprobaciones
   *   se crean automaticamente por el orquestador.
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();

    $this->messenger()->addStatus(
      $this->t('Aprobacion actualizada correctamente.')
    );

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
