<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para gestionar conversaciones de agentes.
 *
 * ESTRUCTURA:
 *   ContentEntityForm con 3 fieldsets: conversacion, contexto y metricas.
 *   Sigue el patron de AgentApprovalForm.
 *
 * LOGICA:
 *   - Fieldset conversacion: tenant_id, user_id, current_agent_id, status.
 *   - Fieldset contexto: agent_chain, shared_context.
 *   - Fieldset metricas: handoff_count, satisfaction_score, total_tokens,
 *     started_at, completed_at.
 *   AUDIT-CONS-005: tenant_id como entity_reference a group.
 */
class AgentConversationForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   *
   * ESTRUCTURA: Construye el formulario con 3 fieldsets tematicos.
   * LOGICA: Agrupa campos de conversacion, contexto y metricas.
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    // --- Fieldset 1: Conversacion ---
    $form['conversation_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Conversacion'),
      '#open' => TRUE,
      '#weight' => -20,
    ];

    $conversation_fields = ['tenant_id', 'user_id', 'current_agent_id', 'status'];
    foreach ($conversation_fields as $field) {
      if (isset($form[$field])) {
        $form['conversation_section'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    // --- Fieldset 2: Contexto ---
    $form['context_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Contexto'),
      '#open' => TRUE,
      '#weight' => -15,
    ];

    $context_fields = ['agent_chain', 'shared_context'];
    foreach ($context_fields as $field) {
      if (isset($form[$field])) {
        $form['context_section'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    // --- Fieldset 3: Metricas ---
    $form['metrics_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Metricas'),
      '#open' => FALSE,
      '#weight' => -10,
    ];

    $metrics_fields = ['handoff_count', 'satisfaction_score', 'total_tokens', 'started_at', 'completed_at'];
    foreach ($metrics_fields as $field) {
      if (isset($form[$field])) {
        $form['metrics_section'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * ESTRUCTURA: Guarda la entidad y muestra mensaje de confirmacion.
   * LOGICA: Redirige al listado de conversaciones tras guardar.
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();

    $this->messenger()->addStatus(
      $this->t('Conversacion actualizada correctamente.')
    );

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
