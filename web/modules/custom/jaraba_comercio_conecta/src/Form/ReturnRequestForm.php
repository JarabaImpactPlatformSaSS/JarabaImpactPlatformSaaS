<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for return requests.
 */
class ReturnRequestForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'devolucion' => [
        'label' => $this->t('Informacion de la Devolucion'),
        'icon' => ['category' => 'ui', 'name' => 'document'],
        'description' => $this->t('Datos del pedido y motivo de la devolucion.'),
        'fields' => ['order_id', 'suborder_id', 'reason', 'description'],
      ],
      'resolucion' => [
        'label' => $this->t('Resolucion'),
        'icon' => ['category' => 'commerce', 'name' => 'tag'],
        'description' => $this->t('Estado y reembolso de la solicitud.'),
        'fields' => ['status', 'refund_amount'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'document'];
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
