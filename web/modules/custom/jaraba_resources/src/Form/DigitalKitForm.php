<?php

declare(strict_types=1);

namespace Drupal\jaraba_resources\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for Digital Kit entities.
 */
class DigitalKitForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'kit' => [
        'label' => $this->t('Kit'),
        'icon' => ['category' => 'ui', 'name' => 'package'],
        'fields' => ['name', 'description', 'category', 'image', 'files'],
      ],
      'targeting' => [
        'label' => $this->t('Targeting'),
        'icon' => ['category' => 'ui', 'name' => 'target'],
        'fields' => ['access_level', 'sectors', 'tags'],
      ],
      'stats' => [
        'label' => $this->t('Statistics'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'fields' => ['download_count', 'rating', 'rating_count'],
      ],
      'publishing' => [
        'label' => $this->t('Publishing'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'fields' => ['is_featured', 'is_new', 'status'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'package'];
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
