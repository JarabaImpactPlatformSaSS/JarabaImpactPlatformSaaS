<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing incident tickets.
 */
class IncidentTicketForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'incidencia' => [
        'label' => $this->t('Datos de la Incidencia'),
        'icon' => ['category' => 'ui', 'name' => 'alert'],
        'description' => $this->t('Informacion principal del ticket.'),
        'fields' => ['subject', 'description', 'category', 'priority', 'order_id', 'merchant_id'],
      ],
      'resolucion' => [
        'label' => $this->t('Resolucion'),
        'icon' => ['category' => 'ui', 'name' => 'check'],
        'description' => $this->t('Estado, asignacion y notas de resolucion.'),
        'fields' => ['status', 'assigned_to', 'resolution_notes'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'alert'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
    return $result;
  }

}
