<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Premium form for creating/editing Badge entities.
 */
class BadgeForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'info' => [
        'label' => $this->t('Información'),
        'icon' => ['category' => 'ui', 'name' => 'award'],
        'description' => $this->t('Nombre, descripción, icono y categoría.'),
        'fields' => ['name', 'description', 'icon', 'category'],
      ],
      'criteria' => [
        'label' => $this->t('Criterios'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Criterios de otorgamiento.'),
        'fields' => ['criteria_type', 'criteria_config', 'points'],
      ],
      'status' => [
        'label' => $this->t('Estado'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Activación de la insignia.'),
        'fields' => ['active'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'award'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    // Force textarea for JSON criteria config.
    $section = 'premium_section_criteria';
    if (isset($form[$section]['criteria_config']['widget'][0]['value'])) {
      $form[$section]['criteria_config']['widget'][0]['value']['#type'] = 'textarea';
      $form[$section]['criteria_config']['widget'][0]['value']['#rows'] = 5;
    }

    return $form;
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
