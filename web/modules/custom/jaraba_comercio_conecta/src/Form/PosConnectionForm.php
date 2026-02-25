<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for POS connections.
 */
class PosConnectionForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'conexion' => [
        'label' => $this->t('Conexion TPV'),
        'icon' => ['category' => 'ui', 'name' => 'link'],
        'description' => $this->t('Datos principales de la conexion con el terminal punto de venta.'),
        'fields' => ['name', 'merchant_id', 'provider', 'status'],
      ],
      'api' => [
        'label' => $this->t('Configuracion API'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Credenciales y configuracion de la API del proveedor.'),
        'fields' => ['api_key', 'api_secret', 'webhook_url', 'location_id', 'sync_frequency'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'link'];
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
