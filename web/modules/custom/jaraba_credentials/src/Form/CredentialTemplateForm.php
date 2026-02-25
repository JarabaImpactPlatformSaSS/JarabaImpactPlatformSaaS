<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar CredentialTemplate.
 */
class CredentialTemplateForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'general' => [
        'label' => $this->t('General'),
        'icon' => ['category' => 'ui', 'name' => 'award'],
        'description' => $this->t('Template name, machine name and type.'),
        'fields' => ['name', 'machine_name', 'description', 'credential_type', 'level'],
      ],
      'visual' => [
        'label' => $this->t('Visual'),
        'icon' => ['category' => 'media', 'name' => 'image'],
        'description' => $this->t('Badge image and visual presentation.'),
        'fields' => ['image'],
      ],
      'criteria' => [
        'label' => $this->t('Criteria'),
        'icon' => ['category' => 'education', 'name' => 'course'],
        'description' => $this->t('Requirements and skills certified by this credential.'),
        'fields' => ['criteria', 'skills_certified'],
      ],
      'issuer' => [
        'label' => $this->t('Issuer'),
        'icon' => ['category' => 'business', 'name' => 'company'],
        'description' => $this->t('Issuer profile and certification program.'),
        'fields' => ['issuer_id', 'certification_program_id', 'validity_period_months'],
      ],
      'automation' => [
        'label' => $this->t('Automation'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Automatic issuance from LMS or interactive content.'),
        'fields' => ['lms_course_id', 'interactive_activity_id', 'passing_score'],
      ],
      'settings' => [
        'label' => $this->t('Settings'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Activation status.'),
        'fields' => ['is_active'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'award'];
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
