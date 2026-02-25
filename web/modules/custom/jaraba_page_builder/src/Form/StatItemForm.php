<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para StatItem.
 */
class StatItemForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'stat' => [
        'label' => $this->t('Statistic'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Numeric value, suffix, and label.'),
        'fields' => ['value', 'suffix', 'label'],
      ],
      'display' => [
        'label' => $this->t('Display'),
        'icon' => ['category' => 'ui', 'name' => 'layout'],
        'description' => $this->t('Display weight for ordering.'),
        'fields' => ['weight'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'analytics', 'name' => 'chart'];
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
