<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para la entidad AgroShipment.
 */
class AgroShipmentForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'shipment' => [
        'label' => $this->t('Envío'),
        'icon' => ['category' => 'commerce', 'name' => 'tag'],
        'fields' => ['carrier_id', 'service_code', 'state'],
      ],
      'tracking' => [
        'label' => $this->t('Seguimiento'),
        'icon' => ['category' => 'ui', 'name' => 'link'],
        'fields' => ['tracking_number'],
      ],
      'weight' => [
        'label' => $this->t('Peso y características'),
        'icon' => ['category' => 'verticals', 'name' => 'agro'],
        'fields' => ['weight_value', 'weight_unit', 'is_refrigerated', 'shipping_cost'],
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

    /** @var \Drupal\jaraba_agroconecta_core\Entity\AgroShipmentInterface $entity */
    $entity = $this->entity;

    // Si es nuevo, el número de envío será generado automáticamente.
    if ($entity->isNew()) {
      if (isset($form['premium_section_other']['shipment_number'])) {
        $form['premium_section_other']['shipment_number']['widget'][0]['value']['#placeholder'] = $this->t('Auto-generado al guardar');
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirect('entity.agro_shipment.collection');
    return $result;
  }

}
