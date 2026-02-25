<?php

declare(strict_types=1);

namespace Drupal\jaraba_crm\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar empresas.
 */
class CompanyForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'general' => [
        'label' => $this->t('General'),
        'icon' => ['category' => 'ui', 'name' => 'building'],
        'fields' => ['name', 'industry', 'size'],
      ],
      'contact' => [
        'label' => $this->t('Contacto'),
        'icon' => ['category' => 'ui', 'name' => 'phone'],
        'fields' => ['email', 'phone', 'website'],
      ],
      'address' => [
        'label' => $this->t('Dirección'),
        'icon' => ['category' => 'ui', 'name' => 'map-pin'],
        'fields' => ['address', 'notes'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'building'];
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
