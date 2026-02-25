<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for retail shipments.
 */
class ShipmentRetailForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'envio' => [
        'label' => $this->t('Datos del Envio'),
        'icon' => ['category' => 'commerce', 'name' => 'tag'],
        'description' => $this->t('Pedido, transportista y seguimiento del envio.'),
        'fields' => ['order_id', 'suborder_id', 'carrier_id', 'shipping_method_id', 'tracking_number', 'tracking_url'],
      ],
      'paquete' => [
        'label' => $this->t('Paquete'),
        'icon' => ['category' => 'commerce', 'name' => 'store'],
        'description' => $this->t('Peso, dimensiones y coste del paquete.'),
        'fields' => ['weight_kg', 'dimensions', 'shipping_cost'],
      ],
      'estado' => [
        'label' => $this->t('Fechas y Estado'),
        'icon' => ['category' => 'ui', 'name' => 'calendar'],
        'description' => $this->t('Fechas de entrega estimada/real y estado actual.'),
        'fields' => ['estimated_delivery', 'actual_delivery', 'status', 'notes'],
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
