<?php

declare(strict_types=1);

namespace Drupal\jaraba_paths\Form;

use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar PathModule.
 *
 * PREMIUM-FORMS-PATTERN-001: Extiende PremiumEntityFormBase.
 */
class PathModuleForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'main' => [
        'label' => $this->t('Información General'),
        'icon' => ['category' => 'business', 'name' => 'clipboard'],
        'description' => $this->t('Datos principales del módulo del itinerario.'),
        'fields' => ['phase_id', 'title', 'description'],
      ],
      'config' => [
        'label' => $this->t('Configuración'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Configuración de orden y duración estimada.'),
        'fields' => ['order', 'estimated_hours', 'is_optional'],
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
