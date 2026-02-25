<?php

declare(strict_types=1);

namespace Drupal\jaraba_crm\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar contactos.
 */
class ContactForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'personal' => [
        'label' => $this->t('Personal'),
        'icon' => ['category' => 'ui', 'name' => 'user'],
        'fields' => ['first_name', 'last_name', 'email', 'phone'],
      ],
      'professional' => [
        'label' => $this->t('Profesional'),
        'icon' => ['category' => 'ui', 'name' => 'briefcase'],
        'fields' => ['job_title', 'company_id', 'source'],
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
    return ['category' => 'ui', 'name' => 'user'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    // engagement_score is computed — show as read-only.
    if (isset($form['premium_section_other']['engagement_score'])) {
      $form['premium_section_other']['engagement_score']['#disabled'] = TRUE;
    }
    elseif (isset($form['engagement_score'])) {
      $form['engagement_score']['#disabled'] = TRUE;
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
