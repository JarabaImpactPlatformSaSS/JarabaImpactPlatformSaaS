<?php

declare(strict_types=1);

namespace Drupal\jaraba_groups\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Form controller for Group Resource forms.
 */
class GroupResourceForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'resource' => [
        'label' => $this->t('Resource'),
        'icon' => ['category' => 'ui', 'name' => 'document'],
        'description' => $this->t('Resource title, description, and type.'),
        'fields' => ['group_id', 'title', 'description', 'resource_type'],
      ],
      'files' => [
        'label' => $this->t('Files'),
        'icon' => ['category' => 'media', 'name' => 'file'],
        'description' => $this->t('Upload a file or provide an external URL.'),
        'fields' => ['file', 'external_url'],
      ],
      'metadata' => [
        'label' => $this->t('Metadata'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Tags, pinned status, and state.'),
        'fields' => ['tags', 'is_pinned', 'status'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'document'];
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
