<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing customer profiles.
 */
class CustomerProfileForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'datos_personales' => [
        'label' => $this->t('Datos Personales'),
        'icon' => ['category' => 'ui', 'name' => 'user'],
        'description' => $this->t('Informacion personal del cliente.'),
        'fields' => ['display_name', 'phone', 'avatar_url'],
      ],
      'direcciones' => [
        'label' => $this->t('Direcciones'),
        'icon' => ['category' => 'ui', 'name' => 'pin'],
        'description' => $this->t('Direcciones de envio y facturacion.'),
        'fields' => ['shipping_address', 'billing_address'],
      ],
      'preferencias' => [
        'label' => $this->t('Preferencias'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Preferencias y comercios favoritos.'),
        'fields' => ['preferences', 'favorite_merchants'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'user'];
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
