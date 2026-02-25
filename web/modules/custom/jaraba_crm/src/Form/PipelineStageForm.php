<?php

declare(strict_types=1);

namespace Drupal\jaraba_crm\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar etapas del pipeline.
 */
class PipelineStageForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'general' => [
        'label' => $this->t('General'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'fields' => ['name', 'machine_name', 'color', 'position'],
      ],
      'behavior' => [
        'label' => $this->t('Comportamiento'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'fields' => ['default_probability', 'is_won_stage', 'is_lost_stage'],
      ],
      'config' => [
        'label' => $this->t('Configuración'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'fields' => ['is_active', 'rotting_days'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'layers'];
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
