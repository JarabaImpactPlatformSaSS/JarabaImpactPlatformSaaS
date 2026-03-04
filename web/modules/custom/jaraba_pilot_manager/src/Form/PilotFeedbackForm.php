<?php

declare(strict_types=1);

namespace Drupal\jaraba_pilot_manager\Form;

use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para la entidad Pilot Feedback.
 *
 * PREMIUM-FORMS-PATTERN-001: Extiende PremiumEntityFormBase.
 */
class PilotFeedbackForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'feedback' => [
        'label' => $this->t('Feedback'),
        'icon' => ['category' => 'ui', 'name' => 'message-square'],
        'description' => $this->t('Datos del feedback proporcionado por el tenant.'),
        'fields' => ['pilot_tenant', 'feedback_type', 'score', 'comment', 'category', 'sentiment'],
      ],
      'response' => [
        'label' => $this->t('Respuesta'),
        'icon' => ['category' => 'ui', 'name' => 'reply'],
        'description' => $this->t('Respuesta al feedback del tenant.'),
        'fields' => ['response', 'response_date', 'responded_by'],
      ],
      'admin' => [
        'label' => $this->t('Administracion'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Configuracion administrativa.'),
        'fields' => ['is_public', 'tenant_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'message-circle'];
  }

}
