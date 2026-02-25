<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para la entidad CarrierConfig.
 */
class CarrierConfigForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'carrier' => [
        'label' => $this->t('Transportista'),
        'icon' => ['category' => 'commerce', 'name' => 'tag'],
        'fields' => ['carrier_id', 'producer_id'],
      ],
      'credentials' => [
        'label' => $this->t('Credenciales'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'fields' => ['api_key', 'api_user', 'api_url'],
      ],
      'config' => [
        'label' => $this->t('Configuración'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'fields' => ['is_test_mode', 'settings', 'tenant_id'],
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
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    // Mask the API key field as password.
    if (isset($form['premium_section_credentials']['api_key'])) {
      $form['premium_section_credentials']['api_key']['#type'] = 'password';
      $form['premium_section_credentials']['api_key']['#attributes']['autocomplete'] = 'off';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirect('entity.agro_carrier_config.collection');
    return $result;
  }

}
