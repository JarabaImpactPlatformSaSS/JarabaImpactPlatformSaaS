<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for stock locations.
 */
class StockLocationForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'ubicacion' => [
        'label' => $this->t('Datos de la Ubicacion'),
        'icon' => ['category' => 'commerce', 'name' => 'store'],
        'description' => $this->t('Informacion basica de la ubicacion de stock.'),
        'fields' => ['name', 'type', 'address', 'merchant_id'],
      ],
      'geolocalizacion' => [
        'label' => $this->t('Geolocalizacion'),
        'icon' => ['category' => 'ui', 'name' => 'location'],
        'description' => $this->t('Coordenadas para Click & Collect y Ship-from-Store.'),
        'fields' => ['latitude', 'longitude'],
      ],
      'omnicanal' => [
        'label' => $this->t('Capacidades Omnicanal'),
        'icon' => ['category' => 'commerce', 'name' => 'cart'],
        'description' => $this->t('Configuracion de recogida y envio desde esta ubicacion.'),
        'fields' => ['is_pickup_point', 'is_ship_from', 'priority'],
      ],
      'config' => [
        'label' => $this->t('Configuracion'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Estado y aislamiento multi-tenant.'),
        'fields' => ['is_active'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'commerce', 'name' => 'store'];
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
