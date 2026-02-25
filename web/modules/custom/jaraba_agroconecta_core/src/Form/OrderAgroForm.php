<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar pedidos agro.
 */
class OrderAgroForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'order_info' => [
        'label' => $this->t('Información del pedido'),
        'icon' => ['category' => 'commerce', 'name' => 'cart'],
        'fields' => ['order_number', 'customer_id', 'state'],
      ],
      'contact' => [
        'label' => $this->t('Contacto'),
        'icon' => ['category' => 'ui', 'name' => 'phone'],
        'fields' => ['email', 'phone'],
      ],
      'delivery' => [
        'label' => $this->t('Entrega'),
        'icon' => ['category' => 'commerce', 'name' => 'tag'],
        'fields' => ['billing_address', 'shipping_address', 'delivery_method', 'delivery_date_preferred', 'delivery_notes'],
      ],
      'totals' => [
        'label' => $this->t('Totales'),
        'icon' => ['category' => 'commerce', 'name' => 'cart'],
        'fields' => ['subtotal', 'total'],
      ],
      'notes' => [
        'label' => $this->t('Notas'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'fields' => ['customer_notes', 'internal_notes'],
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
