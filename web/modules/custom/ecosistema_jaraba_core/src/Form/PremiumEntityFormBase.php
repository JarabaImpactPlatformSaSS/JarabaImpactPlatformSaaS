<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Abstract base class for premium entity forms.
 *
 * Provides a world-class UX/UI with glassmorphism glass cards, section
 * navigation pills, character counters, dirty-state tracking, and progress
 * bars. Any content entity form can extend this class and implement the
 * abstract methods to receive the premium treatment automatically.
 *
 * Usage:
 * @code
 * class MyEntityForm extends PremiumEntityFormBase {
 *   protected function getSectionDefinitions(): array {
 *     return [
 *       'content' => [
 *         'label'       => $this->t('Content'),
 *         'icon'        => ['category' => 'ui', 'name' => 'edit'],
 *         'description' => $this->t('Main content fields'),
 *         'fields'      => ['title', 'body', 'image'],
 *       ],
 *     ];
 *   }
 * }
 * @endcode
 */
abstract class PremiumEntityFormBase extends ContentEntityForm {

  /**
   * Returns section definitions for the premium form layout.
   *
   * Each section becomes a glass-card panel with its own nav pill.
   *
   * @return array
   *   Associative array keyed by section machine name. Each entry:
   *   - label: (string|\Drupal\Core\StringTranslation\TranslatableMarkup)
   *   - icon: (array) ['category' => string, 'name' => string]
   *   - description: (string|\Drupal\Core\StringTranslation\TranslatableMarkup)
   *   - fields: (string[]) Field machine names belonging to this section.
   */
  abstract protected function getSectionDefinitions(): array;

  /**
   * Returns the form header title.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   */
  protected function getFormTitle() {
    $entity = $this->entity;
    if ($entity->isNew()) {
      return $this->t('New @type', [
        '@type' => $entity->getEntityType()->getSingularLabel(),
      ]);
    }
    $label = $entity->label() ?? $entity->getEntityType()->getSingularLabel() ?? (string) $entity->id();
    return $this->t('Edit: @label', ['@label' => $label]);
  }

  /**
   * Returns the form header subtitle.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   */
  protected function getFormSubtitle() {
    return $this->t('Fill in the required fields to save.');
  }

  /**
   * Returns the duotone icon definition for the form header.
   *
   * @return array
   *   ['category' => string, 'name' => string]
   */
  abstract protected function getFormIcon(): array;

  /**
   * Returns character limits per field for the JS char-counter.
   *
   * @return array
   *   Associative array: field_name => max_chars (int).
   */
  protected function getCharacterLimits(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    $entity_type_id = $this->entity->getEntityTypeId();
    $sections = $this->getSectionDefinitions();
    $char_limits = $this->getCharacterLimits();

    // ── Attach premium library & drupalSettings ─────────────────────────
    $form['#attached']['library'][] = 'ecosistema_jaraba_core/premium-forms';
    $form['#attached']['drupalSettings']['premiumForms'] = [
      'charLimits' => $char_limits,
      'sections' => array_keys($sections),
    ];

    // ── CSS classes on form root ────────────────────────────────────────
    $form['#attributes']['class'][] = 'premium-entity-form';
    $form['#attributes']['class'][] = 'premium-entity-form--' . str_replace('_', '-', $entity_type_id);

    // ── Premium header ──────────────────────────────────────────────────
    $icon = $this->getFormIcon();
    $form['premium_header'] = [
      '#type' => 'inline_template',
      '#template' => '<div class="premium-form__header">
        <span class="premium-form__header-icon">{{ jaraba_icon(icon_cat, icon_name, { variant: "duotone", size: "32px", color: "impulse" }) }}</span>
        <div class="premium-form__header-text">
          <h2 class="premium-form__title">{{ title }}</h2>
          <p class="premium-form__subtitle">{{ subtitle }}</p>
        </div>
      </div>',
      '#context' => [
        'icon_cat' => $icon['category'],
        'icon_name' => $icon['name'],
        'title' => $this->getFormTitle(),
        'subtitle' => $this->getFormSubtitle(),
      ],
      '#weight' => -1000,
    ];

    // ── Section navigation pills ────────────────────────────────────────
    $nav_items = [];
    foreach ($sections as $key => $section) {
      $nav_items[] = [
        'key' => $key,
        'label' => $section['label'],
        'icon_cat' => $section['icon']['category'],
        'icon_name' => $section['icon']['name'],
      ];
    }

    $form['premium_nav'] = [
      '#type' => 'inline_template',
      '#template' => '<nav class="premium-form__nav" aria-label="{{ "Form sections"|t }}">
        {% for item in items %}
          <button type="button" class="premium-form__pill{% if loop.first %} is-active{% endif %}" data-premium-section="{{ item.key }}">
            <span class="premium-form__pill-icon">{{ jaraba_icon(item.icon_cat, item.icon_name, { variant: "duotone", size: "16px" }) }}</span>
            <span class="premium-form__pill-label">{{ item.label }}</span>
          </button>
        {% endfor %}
      </nav>',
      '#context' => ['items' => $nav_items],
      '#weight' => -999,
    ];

    // ── Build section containers and move fields ────────────────────────
    $assigned_fields = [];
    $section_weight = 0;

    foreach ($sections as $key => $section) {
      $form['premium_section_' . $key] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['premium-form__section', 'glass-card'],
          'id' => 'premium-section-' . $key,
          'data-premium-section' => $key,
        ],
        '#weight' => $section_weight,
      ];

      // Section header.
      $form['premium_section_' . $key]['section_header'] = [
        '#type' => 'inline_template',
        '#template' => '<div class="premium-form__section-header">
          <span class="premium-form__section-icon">{{ jaraba_icon(icon_cat, icon_name, { variant: "duotone", size: "20px", color: "corporate" }) }}</span>
          <div>
            <h3 class="premium-form__section-title">{{ label }}</h3>
            {% if description %}<p class="premium-form__section-desc">{{ description }}</p>{% endif %}
          </div>
        </div>',
        '#context' => [
          'icon_cat' => $section['icon']['category'],
          'icon_name' => $section['icon']['name'],
          'label' => $section['label'],
          'description' => $section['description'] ?? '',
        ],
        '#weight' => -100,
      ];

      // Move each declared field into this section.
      foreach ($section['fields'] as $field_name) {
        if (isset($form[$field_name])) {
          // Remove any previous #group assignment.
          unset($form[$field_name]['#group']);
          $form['premium_section_' . $key][$field_name] = $form[$field_name];
          unset($form[$field_name]);
          $assigned_fields[] = $field_name;
        }
      }

      $section_weight += 10;
    }

    // ── Auto-group remaining fields into "Other" section ────────────────
    $remaining = [];
    $skip = ['premium_header', 'premium_nav', 'actions', 'form_build_id', 'form_token', 'form_id', 'advanced', 'tabs'];
    foreach ($form as $field_name => $field) {
      if (str_starts_with($field_name, '#') || str_starts_with($field_name, 'premium_section_')) {
        continue;
      }
      if (in_array($field_name, $skip, TRUE) || in_array($field_name, $assigned_fields, TRUE)) {
        continue;
      }
      // Only move render-array elements (arrays with keys).
      if (is_array($field)) {
        $remaining[] = $field_name;
      }
    }

    if ($remaining) {
      $form['premium_section_other'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['premium-form__section', 'glass-card'],
          'id' => 'premium-section-other',
          'data-premium-section' => 'other',
        ],
        '#weight' => $section_weight,
      ];
      $form['premium_section_other']['section_header'] = [
        '#type' => 'inline_template',
        '#template' => '<div class="premium-form__section-header">
          <span class="premium-form__section-icon">{{ jaraba_icon("ui", "settings", { variant: "duotone", size: "20px", color: "corporate" }) }}</span>
          <div>
            <h3 class="premium-form__section-title">{{ label }}</h3>
          </div>
        </div>',
        '#context' => ['label' => $this->t('Other')],
        '#weight' => -100,
      ];

      foreach ($remaining as $field_name) {
        unset($form[$field_name]['#group']);
        $form['premium_section_other'][$field_name] = $form[$field_name];
        unset($form[$field_name]);
      }
    }

    // ── Remove vertical-tabs artifacts left by parent ───────────────────
    unset($form['tabs'], $form['advanced']);
    // Remove any group containers the parent created.
    foreach (array_keys($form) as $key) {
      if (str_ends_with($key, '_group') && isset($form[$key]['#type']) && $form[$key]['#type'] === 'details') {
        unset($form[$key]);
      }
    }

    // ── Actions bar ─────────────────────────────────────────────────────
    if (isset($form['actions'])) {
      $form['actions']['#weight'] = 9999;
      $form['actions']['#attributes']['class'][] = 'premium-form__actions';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->getEntity();
    $type_label = $entity->getEntityType()->getSingularLabel();

    $label = $entity->label() ?? $entity->getEntityType()->getSingularLabel() ?? (string) $entity->id();
    $message_args = [
      '%label' => $label,
      '@type' => $type_label,
    ];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('@type %label has been created.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('@type %label has been updated.', $message_args));
    }

    return $result;
  }

}
