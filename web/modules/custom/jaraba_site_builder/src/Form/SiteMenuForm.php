<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for site menus.
 */
class SiteMenuForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'basic' => [
        'label' => $this->t('Menu Information'),
        'icon' => ['category' => 'ui', 'name' => 'menu'],
        'description' => $this->t('Name, machine name and description.'),
        'fields' => ['label', 'machine_name', 'description'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'menu'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $machineName = $form_state->getValue(['machine_name', 0, 'value']) ?? '';
    if (!empty($machineName) && !preg_match('/^[a-z0-9_]+$/', $machineName)) {
      $form_state->setErrorByName('machine_name', $this->t('The machine name can only contain lowercase letters, numbers and underscores.'));
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
