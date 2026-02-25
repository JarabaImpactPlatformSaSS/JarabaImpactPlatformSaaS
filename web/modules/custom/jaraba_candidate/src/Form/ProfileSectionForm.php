<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing profile sections.
 *
 * Handles candidate_education, candidate_experience, and candidate_language
 * entities with entity-type-aware section definitions.
 */
class ProfileSectionForm extends PremiumEntityFormBase {

  /**
   * Section definitions per entity type.
   */
  private const SECTIONS_BY_TYPE = [
    'candidate_experience' => [
      'section' => [
        'icon' => ['category' => 'business', 'name' => 'briefcase'],
        'fields' => ['company_name', 'job_title', 'description', 'location', 'start_date', 'end_date', 'is_current'],
      ],
    ],
    'candidate_education' => [
      'section' => [
        'icon' => ['category' => 'ui', 'name' => 'award'],
        'fields' => ['institution', 'degree', 'field_of_study', 'start_date', 'end_date'],
      ],
    ],
    'candidate_language' => [
      'section' => [
        'icon' => ['category' => 'ui', 'name' => 'globe'],
        'fields' => ['language_name', 'language_code', 'proficiency_level', 'is_native', 'certification'],
      ],
    ],
  ];

  /**
   * Labels per entity type.
   */
  private const LABELS_BY_TYPE = [
    'candidate_experience' => [
      'title_new' => 'Añadir Experiencia',
      'title_edit' => 'Editar Experiencia',
      'subtitle' => 'Detalla tu experiencia laboral.',
      'section' => 'Datos de la Experiencia',
    ],
    'candidate_education' => [
      'title_new' => 'Añadir Formación',
      'title_edit' => 'Editar Formación',
      'subtitle' => 'Detalla tu formación académica.',
      'section' => 'Datos Académicos',
    ],
    'candidate_language' => [
      'title_new' => 'Añadir Idioma',
      'title_edit' => 'Editar Idioma',
      'subtitle' => 'Indica el idioma y tu nivel de competencia.',
      'section' => 'Datos del Idioma',
    ],
  ];

  /**
   * Gets the current entity type ID.
   */
  protected function getEntityTypeId(): string {
    return $this->entity->getEntityTypeId();
  }

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    $type = $this->getEntityTypeId();
    $config = self::SECTIONS_BY_TYPE[$type] ?? NULL;
    $labels = self::LABELS_BY_TYPE[$type] ?? NULL;

    if (!$config) {
      return [
        'section' => [
          'label' => $this->t('Section'),
          'icon' => ['category' => 'ui', 'name' => 'edit'],
          'description' => $this->t('Profile section details.'),
          'fields' => [],
        ],
      ];
    }

    return [
      'section' => [
        'label' => $this->t($labels['section'] ?? 'Section'),
        'icon' => $config['section']['icon'],
        'description' => $this->t($labels['subtitle'] ?? 'Fill in the details.'),
        'fields' => $config['section']['fields'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormTitle() {
    $type = $this->getEntityTypeId();
    $labels = self::LABELS_BY_TYPE[$type] ?? NULL;

    if ($this->entity->isNew()) {
      return $this->t($labels['title_new'] ?? 'Add');
    }
    return $this->t($labels['title_edit'] ?? 'Edit');
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormSubtitle() {
    $type = $this->getEntityTypeId();
    $labels = self::LABELS_BY_TYPE[$type] ?? NULL;
    return $this->t($labels['subtitle'] ?? 'Fill in the details.');
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    $type = $this->getEntityTypeId();
    $config = self::SECTIONS_BY_TYPE[$type] ?? NULL;
    return $config['section']['icon'] ?? ['category' => 'ui', 'name' => 'edit'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    // Hide user_id field — set automatically in save().
    if (isset($form['premium_section_other']['user_id'])) {
      $form['premium_section_other']['user_id']['#access'] = FALSE;
    }
    if (isset($form['user_id'])) {
      $form['user_id']['#access'] = FALSE;
    }

    // Hide "Other" section if it only contains hidden/system fields.
    if (isset($form['premium_section_other'])) {
      $has_visible = FALSE;
      foreach ($form['premium_section_other'] as $key => $element) {
        if (str_starts_with($key, '#') || !is_array($element)) {
          continue;
        }
        if (!isset($element['#access']) || $element['#access'] !== FALSE) {
          $has_visible = TRUE;
          break;
        }
      }
      if (!$has_visible) {
        $form['premium_section_other']['#access'] = FALSE;
      }
    }

    // Remove delete button.
    unset($form['actions']['delete']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->getEntity();
    if ($entity->isNew() && $entity->hasField('user_id') && empty($entity->get('user_id')->target_id)) {
      $entity->set('user_id', $this->currentUser()->id());
    }
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
