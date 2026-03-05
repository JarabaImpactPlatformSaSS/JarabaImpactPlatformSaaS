<?php

declare(strict_types=1);

namespace Drupal\jaraba_paths\Form;

use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar PathStep.
 *
 * PREMIUM-FORMS-PATTERN-001: Extiende PremiumEntityFormBase.
 */
class PathStepForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'main' => [
        'label' => $this->t('Información General'),
        'icon' => ['category' => 'business', 'name' => 'clipboard'],
        'description' => $this->t('Datos del paso de acción.'),
        'fields' => ['module_id', 'title', 'description', 'step_type'],
      ],
      'config' => [
        'label' => $this->t('Configuración'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Orden, tiempo estimado y recompensas.'),
        'fields' => ['order', 'estimated_minutes', 'is_required', 'xp_reward'],
      ],
      'resources' => [
        'label' => $this->t('Recursos'),
        'icon' => ['category' => 'ui', 'name' => 'link'],
        'description' => $this->t('Recursos externos y herramientas sugeridas.'),
        'fields' => ['resource_url', 'tool_suggestion'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'actions', 'name' => 'target'];
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
