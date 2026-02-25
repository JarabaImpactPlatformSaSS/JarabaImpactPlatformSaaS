<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para editar sub-pedidos agro.
 */
class SuborderAgroForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'suborder' => [
        'label' => $this->t('Sub-pedido'),
        'icon' => ['category' => 'commerce', 'name' => 'cart'],
        'fields' => ['suborder_number', 'order_id', 'producer_id', 'state'],
      ],
      'financials' => [
        'label' => $this->t('Finanzas'),
        'icon' => ['category' => 'commerce', 'name' => 'cart'],
        'fields' => ['subtotal', 'shipping_amount', 'commission_rate', 'commission_amount', 'producer_payout'],
      ],
      'shipping' => [
        'label' => $this->t('Envío'),
        'icon' => ['category' => 'commerce', 'name' => 'tag'],
        'fields' => ['tracking_number', 'producer_notes'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'commerce', 'name' => 'cart'];
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
