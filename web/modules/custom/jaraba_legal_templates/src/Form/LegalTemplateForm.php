<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_templates\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing legal templates.
 */
class LegalTemplateForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'template' => [
        'label' => $this->t('Template'),
        'icon' => ['category' => 'ui', 'name' => 'document'],
        'description' => $this->t('Name, type, and classification.'),
        'fields' => ['name', 'template_type', 'practice_area_tid', 'jurisdiction_tid'],
      ],
      'content' => [
        'label' => $this->t('Content'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Template body and merge fields.'),
        'fields' => ['template_body', 'merge_fields', 'ai_instructions'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Activation and tenant.'),
        'fields' => ['is_system', 'is_active', 'tenant_id'],
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
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    // usage_count is computed — show as read-only.
    if (isset($form['premium_section_other']['usage_count'])) {
      $form['premium_section_other']['usage_count']['#disabled'] = TRUE;
    }
    elseif (isset($form['usage_count'])) {
      $form['usage_count']['#disabled'] = TRUE;
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
