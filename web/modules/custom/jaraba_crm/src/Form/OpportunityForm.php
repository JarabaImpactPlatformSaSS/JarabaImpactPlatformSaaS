<?php

declare(strict_types=1);

namespace Drupal\jaraba_crm\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar oportunidades.
 */
class OpportunityForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'opportunity' => [
        'label' => $this->t('Oportunidad'),
        'icon' => ['category' => 'ui', 'name' => 'target'],
        'fields' => ['title', 'contact_id', 'value', 'stage', 'probability'],
      ],
      'planning' => [
        'label' => $this->t('Planificación'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'fields' => ['expected_close'],
      ],
      'bant' => [
        'label' => $this->t('Calificación BANT'),
        'icon' => ['category' => 'analytics', 'name' => 'gauge'],
        'fields' => ['bant_budget', 'bant_authority', 'bant_need', 'bant_timeline'],
      ],
      'notes' => [
        'label' => $this->t('Notas'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'fields' => ['notes'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'target'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    // bant_score is computed in preSave — show as read-only.
    if (isset($form['premium_section_other']['bant_score'])) {
      $form['premium_section_other']['bant_score']['#disabled'] = TRUE;
    }
    elseif (isset($form['bant_score'])) {
      $form['bant_score']['#disabled'] = TRUE;
    }

    return $form;
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
