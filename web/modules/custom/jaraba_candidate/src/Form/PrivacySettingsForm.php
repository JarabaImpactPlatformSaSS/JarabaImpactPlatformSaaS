<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for profile privacy and visibility settings.
 *
 * Focused form showing only privacy-related fields: public profile,
 * show photo, and show contact info.
 * Loaded via slide-panel from the profile checklist "Privacidad" item.
 */
class PrivacySettingsForm extends PremiumEntityFormBase {

  /**
   * Spanish labels for visible fields.
   */
  private const FIELD_LABELS = [
    'is_public' => 'Perfil público',
    'show_photo' => 'Mostrar foto',
    'show_contact' => 'Mostrar datos de contacto',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'privacy' => [
        'label' => $this->t('Visibilidad del Perfil'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'description' => $this->t('Controla quién puede ver tu perfil y tu información.'),
        'fields' => ['is_public', 'show_photo', 'show_contact'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormTitle() {
    return $this->t('Privacidad');
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormSubtitle() {
    return $this->t('Controla quién puede ver tu perfil y tu información.');
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
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    $form['#attributes']['class'][] = 'profile-section-form';

    // Hide "Other" section — this form only shows privacy fields.
    if (isset($form['premium_section_other'])) {
      $form['premium_section_other']['#access'] = FALSE;
    }

    // Apply Spanish labels.
    foreach (self::FIELD_LABELS as $field_name => $label) {
      if (!isset($form['premium_section_privacy'][$field_name])) {
        continue;
      }
      $field = &$form['premium_section_privacy'][$field_name];
      if (isset($field['widget']['value']['#title'])) {
        $field['widget']['value']['#title'] = $label;
      }
      elseif (isset($field['widget']['#title'])) {
        $field['widget']['#title'] = $label;
      }
      elseif (isset($field['widget'][0]['value']['#title'])) {
        $field['widget'][0]['value']['#title'] = $label;
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
    $this->messenger()->addStatus($this->t('Configuración de privacidad actualizada correctamente.'));
    $form_state->setRedirect('jaraba_candidate.my_profile.privacy');
    return $result;
  }

}
