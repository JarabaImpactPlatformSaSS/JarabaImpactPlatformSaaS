<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar TraceEventAgro.
 */
class TraceEventAgroForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'event' => [
        'label' => $this->t('Evento'),
        'icon' => ['category' => 'verticals', 'name' => 'agro'],
        'fields' => ['batch_id', 'event_type', 'event_timestamp', 'description'],
      ],
      'context' => [
        'label' => $this->t('Contexto'),
        'icon' => ['category' => 'ui', 'name' => 'map-pin'],
        'fields' => ['location', 'actor', 'metadata'],
      ],
      'evidence' => [
        'label' => $this->t('Evidencia'),
        'icon' => ['category' => 'ui', 'name' => 'document'],
        'fields' => ['evidence_url', 'shipment_id'],
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
