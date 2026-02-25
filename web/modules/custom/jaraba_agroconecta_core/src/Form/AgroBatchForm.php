<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar AgroBatch.
 */
class AgroBatchForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identification' => [
        'label' => $this->t('Identificación'),
        'icon' => ['category' => 'verticals', 'name' => 'agro'],
        'fields' => ['batch_code', 'product_id', 'producer_id'],
      ],
      'origin' => [
        'label' => $this->t('Origen y variedad'),
        'icon' => ['category' => 'ui', 'name' => 'map-pin'],
        'fields' => ['origin', 'variety', 'harvest_date'],
      ],
      'production' => [
        'label' => $this->t('Producción'),
        'icon' => ['category' => 'verticals', 'name' => 'agro'],
        'fields' => ['quantity', 'unit', 'certifications'],
      ],
      'config' => [
        'label' => $this->t('Configuración'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'fields' => ['status', 'tenant_id'],
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
