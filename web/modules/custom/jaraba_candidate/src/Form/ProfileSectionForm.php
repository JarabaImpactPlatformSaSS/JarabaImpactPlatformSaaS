<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Lightweight premium form for profile section entities in slide-panel.
 *
 * Renders a single glass-card with premium styling, Spanish labels,
 * and a redirect that works with the slide-panel close flow.
 *
 * Used by: candidate_experience, candidate_education, candidate_language.
 */
class ProfileSectionForm extends ContentEntityForm {

  /**
   * Section metadata per entity type.
   */
  private const SECTION_META = [
    'candidate_experience' => [
      'icon_cat' => 'business',
      'icon_name' => 'briefcase',
      'add_title' => 'Nueva experiencia laboral',
      'edit_title' => 'Editar experiencia laboral',
      'subtitle' => 'Completa los datos de tu experiencia profesional.',
    ],
    'candidate_education' => [
      'icon_cat' => 'education',
      'icon_name' => 'graduation-cap',
      'add_title' => 'Nueva formación académica',
      'edit_title' => 'Editar formación académica',
      'subtitle' => 'Indica los datos de tu formación.',
    ],
    'candidate_language' => [
      'icon_cat' => 'ui',
      'icon_name' => 'globe',
      'add_title' => 'Nuevo idioma',
      'edit_title' => 'Editar idioma',
      'subtitle' => 'Indica el idioma y tu nivel de competencia.',
    ],
  ];

  /**
   * Fields to hide from the form (internal/auto-set).
   */
  private const HIDDEN_FIELDS = [
    'user_id', 'profile_id', 'uid', 'source',
    'created', 'changed',
  ];

  /**
   * Spanish labels for fields.
   */
  private const FIELD_LABELS = [
    // Experience
    'company_name' => 'Empresa',
    'job_title' => 'Puesto',
    'description' => 'Descripción de responsabilidades y logros',
    'location' => 'Ubicación',
    'start_date' => 'Fecha de inicio',
    'end_date' => 'Fecha de fin',
    'is_current' => 'Es mi puesto actual',
    // Education
    'institution' => 'Institución',
    'degree' => 'Titulación',
    'field_of_study' => 'Área de estudio',
    // Language
    'language_code' => 'Código del idioma',
    'language_name' => 'Idioma',
    'proficiency_level' => 'Nivel general (CEFR)',
    'reading_level' => 'Nivel de lectura',
    'writing_level' => 'Nivel de escritura',
    'speaking_level' => 'Nivel de conversación',
    'listening_level' => 'Nivel de comprensión auditiva',
    'is_native' => 'Es mi lengua materna',
    'certification' => 'Certificación (ej: DELE, TOEFL, DELF)',
  ];

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    $entity_type_id = $this->entity->getEntityTypeId();
    $meta = self::SECTION_META[$entity_type_id] ?? [
      'icon_cat' => 'ui',
      'icon_name' => 'edit',
      'add_title' => 'Nuevo registro',
      'edit_title' => 'Editar registro',
      'subtitle' => '',
    ];

    $is_new = $this->entity->isNew();
    $title = $is_new ? $meta['add_title'] : $meta['edit_title'];

    // ── Attach premium forms library ─────────────────────────────────────
    $form['#attached']['library'][] = 'ecosistema_jaraba_core/premium-forms';

    // ── CSS classes ──────────────────────────────────────────────────────
    $form['#attributes']['class'][] = 'premium-entity-form';
    $form['#attributes']['class'][] = 'premium-entity-form--' . str_replace('_', '-', $entity_type_id);
    $form['#attributes']['class'][] = 'profile-section-form';

    // ── Premium header ───────────────────────────────────────────────────
    $form['section_header'] = [
      '#type' => 'inline_template',
      '#template' => '<div class="premium-form__header">
        <span class="premium-form__header-icon">{{ jaraba_icon(icon_cat, icon_name, { variant: "duotone", size: "28px", color: "impulse" }) }}</span>
        <div class="premium-form__header-text">
          <h2 class="premium-form__title">{{ title }}</h2>
          {% if subtitle %}<p class="premium-form__subtitle">{{ subtitle }}</p>{% endif %}
        </div>
      </div>',
      '#context' => [
        'icon_cat' => $meta['icon_cat'],
        'icon_name' => $meta['icon_name'],
        'title' => $title,
        'subtitle' => $meta['subtitle'],
      ],
      '#weight' => -1000,
    ];

    // ── Single glass-card section wrapping all fields ────────────────────
    $form['fields_card'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['premium-form__section', 'glass-card'],
      ],
      '#weight' => 0,
    ];

    // ── Move visible fields into the card, hide internal fields ─────────
    $skip_keys = ['section_header', 'fields_card', 'actions',
      'form_build_id', 'form_token', 'form_id', 'advanced', 'tabs'];

    foreach (array_keys($form) as $field_name) {
      if (str_starts_with($field_name, '#') || in_array($field_name, $skip_keys, TRUE)) {
        continue;
      }
      if (!is_array($form[$field_name])) {
        continue;
      }

      // Hide internal fields.
      if (in_array($field_name, self::HIDDEN_FIELDS, TRUE)) {
        $form[$field_name]['#access'] = FALSE;
        continue;
      }

      // Apply Spanish label if available.
      if (isset(self::FIELD_LABELS[$field_name])) {
        if (isset($form[$field_name]['widget'][0]['value']['#title'])) {
          $form[$field_name]['widget'][0]['value']['#title'] = self::FIELD_LABELS[$field_name];
        }
        elseif (isset($form[$field_name]['widget']['#title'])) {
          $form[$field_name]['widget']['#title'] = self::FIELD_LABELS[$field_name];
        }
        elseif (isset($form[$field_name]['widget'][0]['#title'])) {
          $form[$field_name]['widget'][0]['#title'] = self::FIELD_LABELS[$field_name];
        }
      }

      // Move into glass card.
      unset($form[$field_name]['#group']);
      $form['fields_card'][$field_name] = $form[$field_name];
      unset($form[$field_name]);
    }

    // ── Actions bar — premium styling ──────────────────────────────────
    if (isset($form['actions'])) {
      $form['actions']['#weight'] = 9999;
      $form['actions']['#attributes']['class'][] = 'premium-form__actions';

      if (isset($form['actions']['submit'])) {
        $form['actions']['submit']['#value'] = $is_new
          ? $this->t('Guardar')
          : $this->t('Actualizar');
        $form['actions']['submit']['#attributes']['class'][] = 'button--primary';
      }

      // Remove delete button from edit form (handled separately via JS).
      unset($form['actions']['delete']);
    }

    // Remove vertical-tabs artifacts.
    unset($form['tabs'], $form['advanced']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    // Ensure user_id is set to current user.
    $entity = $this->entity;
    if ($entity->isNew() || !$entity->get('user_id')->target_id) {
      $entity->set('user_id', $this->currentUser()->id());
    }

    $result = parent::save($form, $form_state);

    $labels = [
      'candidate_experience' => 'Experiencia',
      'candidate_education' => 'Formación',
      'candidate_language' => 'Idioma',
    ];
    $type_label = $labels[$entity->getEntityTypeId()] ?? 'Registro';

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('@type guardada correctamente.', ['@type' => $type_label]));
    }
    else {
      $this->messenger()->addStatus($this->t('@type actualizada correctamente.', ['@type' => $type_label]));
    }

    // Redirect to the parent section route to trigger slide-panel reload.
    $section_routes = [
      'candidate_experience' => 'jaraba_candidate.my_profile.experience',
      'candidate_education' => 'jaraba_candidate.my_profile.education',
      'candidate_language' => 'jaraba_candidate.my_profile.languages',
    ];
    $route = $section_routes[$entity->getEntityTypeId()] ?? 'jaraba_candidate.my_profile';
    $form_state->setRedirect($route);

    return $result;
  }

}
