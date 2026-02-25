<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing shipping methods.
 */
class ShippingMethodRetailForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'metodo' => [
        'label' => $this->t('Metodo de Envio'),
        'icon' => ['category' => 'commerce', 'name' => 'tag'],
        'description' => $this->t('Configuracion del metodo de envio.'),
        'fields' => ['name', 'machine_name', 'description', 'base_price', 'free_above', 'estimated_days_min', 'estimated_days_max', 'is_active', 'sort_order'],
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
