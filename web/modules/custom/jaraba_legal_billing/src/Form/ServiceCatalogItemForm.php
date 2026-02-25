<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_billing\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario de creacion/edicion de Servicios del Catalogo.
 */
class ServiceCatalogItemForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'references' => [
        'label' => $this->t('References'),
        'icon' => ['category' => 'business', 'name' => 'briefcase'],
        'description' => $this->t('Tenant, professional and category assignment.'),
        'fields' => ['tenant_id', 'provider_id', 'category_tid'],
      ],
      'service_info' => [
        'label' => $this->t('Service Information'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Name and descriptions of the catalog service.'),
        'fields' => ['name', 'description', 'short_description'],
      ],
      'pricing' => [
        'label' => $this->t('Pricing Model'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('Pricing model, base price, ranges and hourly rates.'),
        'fields' => [
          'pricing_model', 'base_price', 'price_min', 'price_max',
          'hourly_rate', 'estimated_hours_min', 'estimated_hours_max',
          'success_fee_percent',
        ],
      ],
      'status_order' => [
        'label' => $this->t('Status & Display'),
        'icon' => ['category' => 'ui', 'name' => 'package'],
        'description' => $this->t('Active status and display ordering.'),
        'fields' => ['is_active', 'display_order'],
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
