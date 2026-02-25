<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar reservas.
 *
 * Estructura: Extiende PremiumEntityFormBase con secciones premium.
 *
 * Lógica: Agrupa campos por: datos de la cita, cliente, estado,
 *   pago, videollamada y notas.
 */
class BookingForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'appointment' => [
        'label' => $this->t('Appointment Details'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Provider, offering, date, duration, and modality.'),
        'fields' => ['provider_id', 'offering_id', 'booking_date', 'duration_minutes', 'modality'],
      ],
      'client' => [
        'label' => $this->t('Client Details'),
        'icon' => ['category' => 'users', 'name' => 'users'],
        'description' => $this->t('Client name, email, phone, and notes.'),
        'fields' => ['client_name', 'client_email', 'client_phone', 'client_notes'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'description' => $this->t('Booking status and cancellation reason.'),
        'fields' => ['status', 'cancellation_reason'],
      ],
      'payment' => [
        'label' => $this->t('Payment'),
        'icon' => ['category' => 'commerce', 'name' => 'commerce'],
        'description' => $this->t('Price, payment status, and Stripe payment intent.'),
        'fields' => ['price', 'payment_status', 'stripe_payment_intent'],
      ],
      'video_call' => [
        'label' => $this->t('Video Call'),
        'icon' => ['category' => 'social', 'name' => 'social'],
        'description' => $this->t('Meeting URL for online sessions.'),
        'fields' => ['meeting_url'],
      ],
      'provider_notes' => [
        'label' => $this->t('Provider Notes'),
        'icon' => ['category' => 'ui', 'name' => 'document'],
        'description' => $this->t('Internal notes from the provider.'),
        'fields' => ['provider_notes'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'actions', 'name' => 'calendar'];
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
