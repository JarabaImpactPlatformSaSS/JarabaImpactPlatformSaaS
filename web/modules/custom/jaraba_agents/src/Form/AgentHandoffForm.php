<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para gestionar handoffs de agentes.
 *
 * ESTRUCTURA:
 *   ContentEntityForm con 2 fieldsets: transferencia y detalles.
 *   Sigue el patron de AgentApprovalForm.
 *
 * LOGICA:
 *   - Fieldset transferencia: conversation_id, from_agent_id, to_agent_id.
 *   - Fieldset detalles: reason, context_transferred, confidence, handoff_at.
 *   AUDIT-CONS-005: tenant_id como entity_reference a group.
 */
class AgentHandoffForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   *
   * ESTRUCTURA: Construye el formulario con 2 fieldsets tematicos.
   * LOGICA: Agrupa campos de transferencia y detalles del handoff.
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    // --- Fieldset 1: Transferencia ---
    $form['transfer_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Transferencia'),
      '#open' => TRUE,
      '#weight' => -20,
    ];

    $transfer_fields = ['conversation_id', 'from_agent_id', 'to_agent_id'];
    foreach ($transfer_fields as $field) {
      if (isset($form[$field])) {
        $form['transfer_section'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    // --- Fieldset 2: Detalles ---
    $form['details_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Detalles'),
      '#open' => TRUE,
      '#weight' => -15,
    ];

    $details_fields = ['reason', 'context_transferred', 'confidence', 'handoff_at'];
    foreach ($details_fields as $field) {
      if (isset($form[$field])) {
        $form['details_section'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * ESTRUCTURA: Guarda la entidad y muestra mensaje de confirmacion.
   * LOGICA: Redirige al listado de handoffs tras guardar.
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();

    $this->messenger()->addStatus(
      $this->t('Handoff registrado correctamente.')
    );

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
