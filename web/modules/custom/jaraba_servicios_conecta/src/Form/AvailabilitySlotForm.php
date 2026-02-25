<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar slots de disponibilidad.
 */
class AvailabilitySlotForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'slot' => [
        'label' => $this->t('Availability Slot'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Provider, day of week, and start/end times.'),
        'fields' => ['provider_id', 'day_of_week', 'start_time', 'end_time'],
      ],
      'validity' => [
        'label' => $this->t('Validity'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'description' => $this->t('Active status and validity date range.'),
        'fields' => ['is_active', 'valid_from', 'valid_until'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'actions', 'name' => 'calendar'];
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
