<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for local business profiles.
 */
class LocalBusinessProfileForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'negocio' => [
        'label' => $this->t('Datos del Negocio'),
        'icon' => ['category' => 'commerce', 'name' => 'store'],
        'description' => $this->t('Informacion principal del negocio para directorios y SEO local.'),
        'fields' => ['business_name', 'merchant_id', 'description_seo', 'phone', 'email', 'website_url'],
      ],
      'direccion' => [
        'label' => $this->t('Direccion'),
        'icon' => ['category' => 'ui', 'name' => 'map'],
        'description' => $this->t('Direccion fisica del negocio.'),
        'fields' => ['address_street', 'address_city', 'address_postal_code', 'address_province', 'address_country'],
      ],
      'geolocalizacion' => [
        'label' => $this->t('Geolocalizacion'),
        'icon' => ['category' => 'ui', 'name' => 'location'],
        'description' => $this->t('Coordenadas para busquedas "cerca de mi".'),
        'fields' => ['latitude', 'longitude'],
      ],
      'seo' => [
        'label' => $this->t('SEO Local'),
        'icon' => ['category' => 'analytics', 'name' => 'search'],
        'description' => $this->t('Configuracion de Google Business y Schema.org.'),
        'fields' => ['google_place_id', 'google_business_url', 'schema_type', 'opening_hours', 'nap_consistency_score'],
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

    // nap_consistency_score is computed — read-only.
    if (isset($form['premium_section_seo']['nap_consistency_score'])) {
      $form['premium_section_seo']['nap_consistency_score']['#disabled'] = TRUE;
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
