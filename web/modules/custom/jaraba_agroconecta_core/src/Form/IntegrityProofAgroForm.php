<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar IntegrityProofAgro.
 */
class IntegrityProofAgroForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'proof' => [
        'label' => $this->t('Prueba de integridad'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'fields' => ['batch_id', 'proof_hash', 'proof_timestamp'],
      ],
      'anchor' => [
        'label' => $this->t('Anclaje'),
        'icon' => ['category' => 'ui', 'name' => 'link'],
        'fields' => ['anchor_type', 'anchor_reference'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'shield'];
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
