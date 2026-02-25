<?php

declare(strict_types=1);

namespace Drupal\jaraba_whitelabel\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Form for creating/editing WhitelabelEmailTemplate entities.
 */
class WhitelabelEmailTemplateForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'template_info' => [
        'label' => $this->t('Template Information'),
        'icon' => ['category' => 'ui', 'name' => 'mail'],
        'description' => $this->t('Template key, subject, tenant and status.'),
        'fields' => ['template_key', 'subject', 'tenant_id', 'template_status'],
      ],
      'content' => [
        'label' => $this->t('Content'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('HTML and plain text email body. Available tokens: {{ user_name }}, {{ company_name }}, {{ site_url }}, {{ current_date }}, {{ reset_link }}, {{ invoice_number }}, {{ invoice_total }}.'),
        'fields' => ['body_html', 'body_text'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'mail'];
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
