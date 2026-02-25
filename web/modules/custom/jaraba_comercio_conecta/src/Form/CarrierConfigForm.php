<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for carrier configuration.
 */
class CarrierConfigForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'transportista' => [
        'label' => $this->t('Transportista'),
        'icon' => ['category' => 'commerce', 'name' => 'tag'],
        'description' => $this->t('Datos basicos del transportista.'),
        'fields' => ['carrier_name', 'carrier_code', 'is_active'],
      ],
      'api' => [
        'label' => $this->t('Configuracion API'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Credenciales y configuracion de la API del transportista.'),
        'fields' => ['api_url', 'api_key', 'api_secret', 'tracking_url_pattern', 'config_data'],
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
