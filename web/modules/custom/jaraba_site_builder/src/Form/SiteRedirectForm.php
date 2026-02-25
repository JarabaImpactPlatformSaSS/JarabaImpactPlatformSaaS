<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for site redirects.
 */
class SiteRedirectForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'urls' => [
        'label' => $this->t('URLs'),
        'icon' => ['category' => 'ui', 'name' => 'link'],
        'description' => $this->t('Source path, destination and redirect type.'),
        'fields' => ['source_path', 'destination_path', 'redirect_type'],
      ],
      'options' => [
        'label' => $this->t('Options'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Reason, active status and expiration.'),
        'fields' => ['reason', 'is_active', 'expires_at'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'link'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    // Hide technical fields.
    if (isset($form['premium_section_other']['hit_count'])) {
      $form['premium_section_other']['hit_count']['#access'] = FALSE;
    }
    if (isset($form['premium_section_other']['last_hit'])) {
      $form['premium_section_other']['last_hit']['#access'] = FALSE;
    }
    if (isset($form['premium_section_other']['is_auto_generated'])) {
      $form['premium_section_other']['is_auto_generated']['#access'] = FALSE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $sourcePath = $form_state->getValue(['source_path', 0, 'value']) ?? '';
    if (!empty($sourcePath) && !str_starts_with($sourcePath, '/')) {
      $form_state->setErrorByName('source_path', $this->t('The source URL must start with /'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirect('jaraba_site_builder.redirects');
    return $result;
  }

}
