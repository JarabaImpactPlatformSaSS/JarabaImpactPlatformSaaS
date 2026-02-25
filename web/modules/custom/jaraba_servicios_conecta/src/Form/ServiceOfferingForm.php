<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar servicios ofertados.
 *
 * Estructura: Extiende PremiumEntityFormBase con secciones premium.
 *
 * Lógica: Agrupa campos por: datos del servicio, precio y duración,
 *   modalidad, configuración de reserva, estado e imagen.
 */
class ServiceOfferingForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'service_data' => [
        'label' => $this->t('Service Data'),
        'icon' => ['category' => 'business', 'name' => 'briefcase'],
        'description' => $this->t('Title, provider, description, and category.'),
        'fields' => ['title', 'provider_id', 'description', 'category'],
      ],
      'pricing' => [
        'label' => $this->t('Pricing & Duration'),
        'icon' => ['category' => 'commerce', 'name' => 'commerce'],
        'description' => $this->t('Price, pricing model, and session duration.'),
        'fields' => ['price', 'price_type', 'duration_minutes'],
      ],
      'modality' => [
        'label' => $this->t('Modality'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Service delivery modality and participant limits.'),
        'fields' => ['modality', 'max_participants'],
      ],
      'booking_config' => [
        'label' => $this->t('Booking Configuration'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Prepayment requirements and advance booking settings.'),
        'fields' => ['requires_prepayment', 'advance_booking_min'],
      ],
      'status' => [
        'label' => $this->t('Status & Order'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'description' => $this->t('Publication status, featured flag, and sort weight.'),
        'fields' => ['is_published', 'is_featured', 'sort_weight'],
      ],
      'media' => [
        'label' => $this->t('Image'),
        'icon' => ['category' => 'media', 'name' => 'media'],
        'description' => $this->t('Service offering image.'),
        'fields' => ['image'],
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
