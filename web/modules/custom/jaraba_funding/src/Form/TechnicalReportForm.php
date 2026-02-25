<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for Technical Report entities.
 */
class TechnicalReportForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identification' => [
        'label' => $this->t('Identification'),
        'icon' => ['category' => 'ui', 'name' => 'document'],
        'fields' => ['title', 'application_id', 'report_type', 'status'],
      ],
      'content' => [
        'label' => $this->t('Content'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'fields' => ['content_sections'],
      ],
      'ai' => [
        'label' => $this->t('AI Generation'),
        'icon' => ['category' => 'ai', 'name' => 'brain'],
        'fields' => ['ai_generated', 'ai_model_used', 'file_id', 'tenant_id'],
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
