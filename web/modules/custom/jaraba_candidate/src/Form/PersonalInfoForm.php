<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for personal contact information.
 *
 * Focused form showing only name, email, phone, and photo.
 * Loaded via slide-panel from the profile checklist "Datos Personales" item.
 */
class PersonalInfoForm extends PremiumEntityFormBase {

  /**
   * Spanish labels for visible fields.
   */
  private const FIELD_LABELS = [
    'first_name' => 'Nombre',
    'last_name' => 'Apellidos',
    'email' => 'Correo electrónico',
    'phone' => 'Teléfono',
    'photo' => 'Foto de perfil',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'personal' => [
        'label' => $this->t('Información de Contacto'),
        'icon' => ['category' => 'ui', 'name' => 'user'],
        'description' => $this->t('Tu información de contacto básica.'),
        'fields' => ['first_name', 'last_name', 'email', 'phone', 'photo'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormTitle() {
    return $this->t('Datos Personales');
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormSubtitle() {
    return $this->t('Tu información de contacto básica.');
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

    $form['#attributes']['class'][] = 'profile-section-form';

    // Hide "Other" section — this form only shows specific fields.
    if (isset($form['premium_section_other'])) {
      $form['premium_section_other']['#access'] = FALSE;
    }

    // Apply Spanish labels.
    foreach (self::FIELD_LABELS as $field_name => $label) {
      if (!isset($form['premium_section_personal'][$field_name])) {
        continue;
      }
      $field = &$form['premium_section_personal'][$field_name];
      if (isset($field['widget'][0]['value']['#title'])) {
        $field['widget'][0]['value']['#title'] = $label;
      }
      elseif (isset($field['widget']['#title'])) {
        $field['widget']['#title'] = $label;
      }
      elseif (isset($field['widget'][0]['#title'])) {
        $field['widget'][0]['#title'] = $label;
      }
    }
    unset($field);

    // Remove delete button.
    unset($form['actions']['delete']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $this->messenger()->addStatus($this->t('Datos personales actualizados correctamente.'));
    $form_state->setRedirect('jaraba_candidate.my_profile');
    return $result;
  }

}
