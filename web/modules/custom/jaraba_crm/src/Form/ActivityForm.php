<?php

declare(strict_types=1);

namespace Drupal\jaraba_crm\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar actividades.
 */
class ActivityForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'activity' => [
        'label' => $this->t('Actividad'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'fields' => ['subject', 'type', 'activity_date', 'duration'],
      ],
      'relations' => [
        'label' => $this->t('Relaciones'),
        'icon' => ['category' => 'ui', 'name' => 'link'],
        'fields' => ['contact_id', 'opportunity_id'],
      ],
      'notes' => [
        'label' => $this->t('Notas'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'fields' => ['notes'],
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
