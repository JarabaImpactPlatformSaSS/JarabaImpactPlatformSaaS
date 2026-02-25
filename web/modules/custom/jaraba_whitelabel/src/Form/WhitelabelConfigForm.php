<?php

declare(strict_types=1);

namespace Drupal\jaraba_whitelabel\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Form for creating/editing WhitelabelConfig entities.
 */
class WhitelabelConfigForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identity' => [
        'label' => $this->t('Identity'),
        'icon' => ['category' => 'business', 'name' => 'building'],
        'description' => $this->t('Core identification and tenant assignment.'),
        'fields' => ['config_key', 'tenant_id', 'company_name'],
      ],
      'branding' => [
        'label' => $this->t('Branding'),
        'icon' => ['category' => 'ui', 'name' => 'palette'],
        'description' => $this->t('Logo, favicon and brand colours.'),
        'fields' => ['logo_url', 'favicon_url', 'primary_color', 'secondary_color'],
      ],
      'customisation' => [
        'label' => $this->t('Customisation'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Custom CSS, footer HTML and visibility options.'),
        'fields' => ['custom_css', 'custom_footer_html', 'hide_powered_by', 'config_status'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'palette'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    // Validate hex colour format.
    foreach (['primary_color', 'secondary_color'] as $field) {
      $value = $form_state->getValue($field)[0]['value'] ?? '';
      if (!empty($value) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
        $form_state->setErrorByName($field, $this->t('Colour must be a valid hex code (e.g. #FF8C42).'));
      }
    }
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
