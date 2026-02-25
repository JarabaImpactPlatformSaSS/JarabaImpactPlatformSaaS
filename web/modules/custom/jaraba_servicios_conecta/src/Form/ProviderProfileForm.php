<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar perfiles de profesional.
 *
 * Estructura: Extiende PremiumEntityFormBase con secciones premium.
 *
 * Lógica: Las secciones agrupan campos por función: identidad, credenciales,
 *   contacto, dirección, geolocalización, configuración, pagos, estado y media.
 */
class ProviderProfileForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identity' => [
        'label' => $this->t('Professional Identity'),
        'icon' => ['category' => 'users', 'name' => 'users'],
        'description' => $this->t('Display name, title, category, specialties, and description.'),
        'fields' => ['display_name', 'slug', 'professional_title', 'service_category', 'specialties', 'description'],
      ],
      'credentials' => [
        'label' => $this->t('Credentials'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'description' => $this->t('License, tax ID, insurance, and experience.'),
        'fields' => ['license_number', 'tax_id', 'insurance_policy', 'years_experience'],
      ],
      'contact' => [
        'label' => $this->t('Contact'),
        'icon' => ['category' => 'social', 'name' => 'social'],
        'description' => $this->t('Phone, email, and website.'),
        'fields' => ['phone', 'email', 'website'],
      ],
      'address' => [
        'label' => $this->t('Address'),
        'icon' => ['category' => 'business', 'name' => 'briefcase'],
        'description' => $this->t('Street address, city, postal code, province, and country.'),
        'fields' => ['address_street', 'address_city', 'address_postal_code', 'address_province', 'address_country'],
      ],
      'geolocation' => [
        'label' => $this->t('Geolocation'),
        'icon' => ['category' => 'verticals', 'name' => 'verticals'],
        'description' => $this->t('Latitude and longitude coordinates.'),
        'fields' => ['latitude', 'longitude'],
      ],
      'service_config' => [
        'label' => $this->t('Service Configuration'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Radius, session duration, buffer time, booking, cancellation, and modality settings.'),
        'fields' => ['service_radius_km', 'default_session_duration', 'buffer_time', 'advance_booking_days', 'cancellation_hours', 'requires_prepayment', 'accepts_online'],
      ],
      'stripe' => [
        'label' => $this->t('Stripe Connect'),
        'icon' => ['category' => 'commerce', 'name' => 'commerce'],
        'description' => $this->t('Stripe account ID and onboarding status.'),
        'fields' => ['stripe_account_id', 'stripe_onboarding_complete'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Verification status and active flag.'),
        'fields' => ['verification_status', 'is_active'],
      ],
      'media' => [
        'label' => $this->t('Images'),
        'icon' => ['category' => 'media', 'name' => 'media'],
        'description' => $this->t('Profile photo and cover image.'),
        'fields' => ['photo', 'cover_image'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'business', 'name' => 'briefcase'];
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
