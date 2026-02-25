<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar reseñas AgroConecta.
 */
class ReviewAgroForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'review' => [
        'label' => $this->t('Detalles de la reseña'),
        'icon' => ['category' => 'ui', 'name' => 'star'],
        'fields' => ['type', 'target_entity_type', 'target_entity_id', 'rating', 'title', 'body'],
      ],
      'moderation' => [
        'label' => $this->t('Moderación'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'fields' => ['state', 'verified_purchase'],
      ],
      'response' => [
        'label' => $this->t('Respuesta del productor'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'fields' => ['response', 'response_by'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'star'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    // Validar que el rating está entre 1 y 5.
    $rating = $form_state->getValue(['rating', 0, 'value']);
    if ($rating !== NULL && ($rating < 1 || $rating > 5)) {
      $form_state->setErrorByName('rating', $this->t('La valoración debe estar entre 1 y 5 estrellas.'));
    }

    // Validar tipo válido.
    $valid_types = ['product', 'producer', 'order'];
    $type = $form_state->getValue(['type', 0, 'value']);
    if ($type && !in_array($type, $valid_types)) {
      $form_state->setErrorByName('type', $this->t('Tipo de reseña no válido. Tipos permitidos: @types.', [
        '@types' => implode(', ', $valid_types),
      ]));
    }

    // Validar estado válido.
    $valid_states = ['pending', 'approved', 'rejected', 'flagged'];
    $state = $form_state->getValue(['state', 0, 'value']);
    if ($state && !in_array($state, $valid_states)) {
      $form_state->setErrorByName('state', $this->t('Estado no válido.'));
    }
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
