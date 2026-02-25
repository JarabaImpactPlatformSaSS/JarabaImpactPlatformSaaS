<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar ProducerProfile.
 */
class ProducerProfileForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identity' => [
        'label' => $this->t('Identidad'),
        'icon' => ['category' => 'verticals', 'name' => 'agro'],
        'fields' => ['farm_name', 'producer_name', 'description', 'image'],
      ],
      'location' => [
        'label' => $this->t('Ubicación'),
        'icon' => ['category' => 'ui', 'name' => 'map-pin'],
        'fields' => ['location', 'latitude', 'longitude', 'area_hectares'],
      ],
      'production' => [
        'label' => $this->t('Producción'),
        'icon' => ['category' => 'verticals', 'name' => 'agro'],
        'fields' => ['production_type'],
      ],
      'contact' => [
        'label' => $this->t('Contacto'),
        'icon' => ['category' => 'ui', 'name' => 'phone'],
        'fields' => ['email', 'phone'],
      ],
      'config' => [
        'label' => $this->t('Configuración'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'fields' => ['stripe_account_id', 'tenant_id', 'status'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'verticals', 'name' => 'agro'];
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
