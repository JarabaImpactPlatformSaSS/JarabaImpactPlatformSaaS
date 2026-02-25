<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para IntentionCard.
 */
class IntentionCardForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'content' => [
        'label' => $this->t('Content'),
        'icon' => ['category' => 'ui', 'name' => 'target'],
        'description' => $this->t('Card title, description, icon, and URL.'),
        'fields' => ['title', 'description', 'icon', 'url'],
      ],
      'display' => [
        'label' => $this->t('Display'),
        'icon' => ['category' => 'ui', 'name' => 'layout'],
        'description' => $this->t('Color class and display weight.'),
        'fields' => ['color_class', 'weight'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'target'];
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
