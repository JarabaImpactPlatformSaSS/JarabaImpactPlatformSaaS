<?php

declare(strict_types=1);

namespace Drupal\jaraba_paths\Form;

use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar PathPhase.
 *
 * PREMIUM-FORMS-PATTERN-001: Extiende PremiumEntityFormBase.
 */
class PathPhaseForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'main' => [
        'label' => $this->t('Información General'),
        'icon' => ['category' => 'business', 'name' => 'clipboard'],
        'description' => $this->t('Datos principales de la fase del itinerario.'),
        'fields' => ['path_id', 'name', 'phase_type', 'description'],
      ],
      'config' => [
        'label' => $this->t('Configuración'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Configuración de orden, icono y duración.'),
        'fields' => ['icon', 'order', 'estimated_days'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'business', 'name' => 'clipboard'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $status = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $status;
  }

}
