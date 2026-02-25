<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar paquetes de servicios.
 */
class ServicePackageForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'package_data' => [
        'label' => $this->t('Package Data'),
        'icon' => ['category' => 'business', 'name' => 'briefcase'],
        'description' => $this->t('Title, provider, offering, and description.'),
        'fields' => ['title', 'provider_id', 'offering_id', 'description'],
      ],
      'pricing_sessions' => [
        'label' => $this->t('Pricing & Sessions'),
        'icon' => ['category' => 'commerce', 'name' => 'commerce'],
        'description' => $this->t('Number of sessions, price, discount, and validity period.'),
        'fields' => ['total_sessions', 'price', 'discount_percent', 'validity_days'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'description' => $this->t('Publication status of the package.'),
        'fields' => ['is_published'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'business', 'name' => 'briefcase'];
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
