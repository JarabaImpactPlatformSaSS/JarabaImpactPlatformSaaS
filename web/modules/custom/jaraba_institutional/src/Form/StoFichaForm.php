<?php

declare(strict_types=1);

namespace Drupal\jaraba_institutional\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for STO Ficha entities.
 */
class StoFichaForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identification' => [
        'label' => $this->t('Identification'),
        'icon' => ['category' => 'ui', 'name' => 'document'],
        'fields' => ['participant_id', 'ficha_type', 'ficha_number'],
      ],
      'content' => [
        'label' => $this->t('Content'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'fields' => ['diagnostico_empleabilidad', 'itinerario_insercion', 'acciones_orientacion', 'resultados'],
      ],
      'generation' => [
        'label' => $this->t('Generation'),
        'icon' => ['category' => 'ai', 'name' => 'brain'],
        'fields' => ['ai_generated', 'ai_model_used', 'pdf_file_id', 'signature_status', 'signed_at', 'tenant_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'document'];
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
