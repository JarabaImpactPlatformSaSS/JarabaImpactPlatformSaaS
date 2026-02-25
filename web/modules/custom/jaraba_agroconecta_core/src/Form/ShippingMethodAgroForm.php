<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario de creación/edición de ShippingMethodAgro.
 */
class ShippingMethodAgroForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'basic' => [
        'label' => $this->t('Información básica'),
        'icon' => ['category' => 'commerce', 'name' => 'tag'],
        'fields' => ['name', 'description', 'delivery_estimate'],
      ],
      'pricing' => [
        'label' => $this->t('Tarifas'),
        'icon' => ['category' => 'commerce', 'name' => 'cart'],
        'fields' => ['calculation_type', 'base_rate', 'rate_table', 'free_threshold'],
      ],
      'restrictions' => [
        'label' => $this->t('Restricciones'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'fields' => ['max_weight', 'zone_ids', 'requires_cold_chain'],
      ],
      'config' => [
        'label' => $this->t('Configuración'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'fields' => ['position', 'is_active', 'tenant_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'commerce', 'name' => 'tag'];
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
