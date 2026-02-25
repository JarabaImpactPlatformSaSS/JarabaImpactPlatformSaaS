<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing merchant profiles.
 */
class MerchantProfileForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'datos_negocio' => [
        'label' => $this->t('Datos del Negocio'),
        'icon' => ['category' => 'commerce', 'name' => 'store'],
        'description' => $this->t('Informacion principal del comercio.'),
        'fields' => ['business_name', 'slug', 'business_type', 'description'],
      ],
      'contacto' => [
        'label' => $this->t('Contacto'),
        'icon' => ['category' => 'ui', 'name' => 'phone'],
        'description' => $this->t('Datos de contacto del comercio.'),
        'fields' => ['tax_id', 'phone', 'email', 'website'],
      ],
      'direccion' => [
        'label' => $this->t('Direccion'),
        'icon' => ['category' => 'ui', 'name' => 'pin'],
        'description' => $this->t('Direccion fisica del comercio.'),
        'fields' => ['address_street', 'address_city', 'address_postal_code', 'address_province', 'address_country'],
      ],
      'geolocalizacion' => [
        'label' => $this->t('Geolocalizacion'),
        'icon' => ['category' => 'ui', 'name' => 'map'],
        'description' => $this->t('Coordenadas para el mapa.'),
        'fields' => ['latitude', 'longitude'],
      ],
      'configuracion' => [
        'label' => $this->t('Configuracion'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Horarios, reparto y comisiones.'),
        'fields' => ['opening_hours', 'accepts_click_collect', 'delivery_radius_km', 'commission_rate'],
      ],
      'stripe' => [
        'label' => $this->t('Stripe Connect'),
        'icon' => ['category' => 'commerce', 'name' => 'wallet'],
        'description' => $this->t('Configuracion de pagos con Stripe.'),
        'fields' => ['stripe_account_id', 'stripe_onboarding_complete'],
      ],
      'estado' => [
        'label' => $this->t('Estado'),
        'icon' => ['category' => 'ui', 'name' => 'check'],
        'description' => $this->t('Verificacion y activacion del comercio.'),
        'fields' => ['verification_status', 'is_active'],
      ],
      'media' => [
        'label' => $this->t('Imagenes'),
        'icon' => ['category' => 'media', 'name' => 'image'],
        'description' => $this->t('Logo, portada y galeria del comercio.'),
        'fields' => ['logo', 'cover_image', 'gallery'],
      ],
      'estadisticas' => [
        'label' => $this->t('Estadisticas'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Metricas calculadas automaticamente.'),
        'fields' => ['average_rating', 'total_reviews'],
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
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    // Computed fields: average_rating and total_reviews are read-only.
    if (isset($form['premium_section_estadisticas']['average_rating'])) {
      $form['premium_section_estadisticas']['average_rating']['#disabled'] = TRUE;
    }
    elseif (isset($form['average_rating'])) {
      $form['average_rating']['#disabled'] = TRUE;
    }

    if (isset($form['premium_section_estadisticas']['total_reviews'])) {
      $form['premium_section_estadisticas']['total_reviews']['#disabled'] = TRUE;
    }
    elseif (isset($form['total_reviews'])) {
      $form['total_reviews']['#disabled'] = TRUE;
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
