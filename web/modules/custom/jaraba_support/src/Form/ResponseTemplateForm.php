<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Form handler for Response Template add/edit.
 *
 * PREMIUM-FORMS-PATTERN-001: Extends PremiumEntityFormBase.
 */
class ResponseTemplateForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'content' => [
        'label' => $this->t('Template Content'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Template name and body with placeholders.'),
        'fields' => ['name', 'body'],
      ],
      'classification' => [
        'label' => $this->t('Classification'),
        'icon' => ['category' => 'ui', 'name' => 'tag'],
        'description' => $this->t('Category, vertical, and scope.'),
        'fields' => ['category', 'vertical', 'scope'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'template'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();
    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $result;
  }

}
