<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing flash offers.
 */
class FlashOfferForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'info_oferta' => [
        'label' => $this->t('Informacion de la Oferta'),
        'icon' => ['category' => 'commerce', 'name' => 'tag'],
        'description' => $this->t('Datos principales de la oferta flash.'),
        'fields' => ['title', 'description', 'merchant_id', 'product_id', 'image_url'],
      ],
      'descuento' => [
        'label' => $this->t('Descuento'),
        'icon' => ['category' => 'commerce', 'name' => 'wallet'],
        'description' => $this->t('Tipo y valor del descuento.'),
        'fields' => ['discount_type', 'discount_value', 'original_price', 'offer_price'],
      ],
      'programacion' => [
        'label' => $this->t('Programacion'),
        'icon' => ['category' => 'ui', 'name' => 'calendar'],
        'description' => $this->t('Periodo de vigencia y limites.'),
        'fields' => ['start_time', 'end_time', 'max_claims', 'status'],
      ],
      'estadisticas' => [
        'label' => $this->t('Estadisticas'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Metricas calculadas automaticamente.'),
        'fields' => ['current_claims'],
      ],
      'geolocalizacion' => [
        'label' => $this->t('Geolocalizacion'),
        'icon' => ['category' => 'ui', 'name' => 'map'],
        'description' => $this->t('Radio de accion geografico.'),
        'fields' => ['location_lat', 'location_lng', 'radius_km'],
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

    // Computed field: current_claims is read-only.
    if (isset($form['premium_section_estadisticas']['current_claims'])) {
      $form['premium_section_estadisticas']['current_claims']['#disabled'] = TRUE;
    }
    elseif (isset($form['current_claims'])) {
      $form['current_claims']['#disabled'] = TRUE;
    }

    return $form;
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
