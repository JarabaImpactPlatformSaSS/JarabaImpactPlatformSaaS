<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for professional branding with native AI assistance.
 *
 * Star form of the SaaS: headline + summary with inline AI buttons
 * that invoke the EmployabilityCopilotAgent in profile_coach mode.
 */
class ProfessionalBrandForm extends PremiumEntityFormBase {

  /**
   * Spanish labels for visible fields.
   */
  private const FIELD_LABELS = [
    'headline' => 'Titular profesional',
    'summary' => 'Resumen profesional',
    'experience_years' => 'Años de experiencia',
    'experience_level' => 'Nivel de experiencia',
    'education_level' => 'Nivel de formación',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'headline' => [
        'label' => $this->t('Titular Profesional'),
        'icon' => ['category' => 'ai', 'name' => 'sparkles'],
        'description' => $this->t('Tu titular es lo primero que ven los reclutadores. Sé conciso y transmite tu valor diferencial.'),
        'fields' => ['headline'],
      ],
      'summary' => [
        'label' => $this->t('Resumen Profesional'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Incluye logros cuantificables, palabras clave de tu sector y tu propuesta de valor única.'),
        'fields' => ['summary'],
      ],
      'level' => [
        'label' => $this->t('Nivel y Trayectoria'),
        'icon' => ['category' => 'ui', 'name' => 'award'],
        'description' => $this->t('Tu experiencia y formación.'),
        'fields' => ['experience_years', 'experience_level', 'education_level'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormTitle() {
    return $this->t('Marca Profesional');
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormSubtitle() {
    return $this->t('Define tu identidad profesional con ayuda de la IA.');
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ai', 'name' => 'sparkles'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    $form['#attached']['library'][] = 'jaraba_candidate/brand_professional';
    // Pass copilot URL with CSRF token to JS.
    // Route has _csrf_token: TRUE → token must be in URL query, generated
    // with csrfToken->get(path). We generate it directly to avoid BigPipe
    // placeholder issues (Url::fromRoute uses lazy_builder in HTML context).
    $copilot_path = 'api/v1/copilot/employability/chat';
    $csrf_token = \Drupal::csrfToken()->get($copilot_path);
    $form['#attached']['drupalSettings']['brandProfessional'] = [
      'copilotUrl' => '/' . $copilot_path . '?_format=json&token=' . $csrf_token,
    ];
    $form['#attributes']['class'][] = 'profile-section-form';

    // Hide "Other" section — this form only shows branding fields.
    if (isset($form['premium_section_other'])) {
      $form['premium_section_other']['#access'] = FALSE;
    }

    // Apply Spanish labels.
    $section_map = [
      'headline' => 'premium_section_headline',
      'summary' => 'premium_section_summary',
      'experience_years' => 'premium_section_level',
      'experience_level' => 'premium_section_level',
      'education_level' => 'premium_section_level',
    ];
    foreach (self::FIELD_LABELS as $field_name => $label) {
      $section = $section_map[$field_name] ?? NULL;
      if (!$section || !isset($form[$section][$field_name])) {
        continue;
      }
      $field = &$form[$section][$field_name];
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

    // AI button for headline generation.
    $form['premium_section_headline']['ai_headline'] = [
      '#type' => 'inline_template',
      '#template' => '<div class="ai-assist-actions">
        <button type="button" class="ai-assist-btn" data-ai-action="generate_headline" data-ai-target="headline">
          {{ jaraba_icon("ai", "sparkles", { variant: "duotone", size: "16px", color: "naranja-impulso" }) }}
          {{ label }}
        </button>
      </div>
      <div class="ai-suggestions-container" data-ai-suggestions-for="headline" hidden></div>',
      '#context' => ['label' => (string) $this->t('Generar con IA')],
      '#weight' => 100,
    ];

    // AI buttons for summary (optimize + generate).
    $form['premium_section_summary']['ai_summary'] = [
      '#type' => 'inline_template',
      '#template' => '<div class="ai-assist-actions">
        <button type="button" class="ai-assist-btn" data-ai-action="optimize_summary" data-ai-target="summary">
          {{ jaraba_icon("ai", "sparkles", { variant: "duotone", size: "16px", color: "naranja-impulso" }) }}
          {{ label_optimize }}
        </button>
        <button type="button" class="ai-assist-btn ai-assist-btn--secondary" data-ai-action="generate_summary" data-ai-target="summary">
          {{ jaraba_icon("ai", "copilot", { variant: "duotone", size: "16px", color: "naranja-impulso" }) }}
          {{ label_generate }}
        </button>
      </div>
      <div class="ai-comparison-container" data-ai-comparison-for="summary" hidden></div>',
      '#context' => [
        'label_optimize' => (string) $this->t('Optimizar con IA'),
        'label_generate' => (string) $this->t('Generar desde cero'),
      ],
      '#weight' => 100,
    ];

    // Remove delete button.
    unset($form['actions']['delete']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $this->messenger()->addStatus($this->t('Marca profesional actualizada correctamente.'));
    $form_state->setRedirect('jaraba_candidate.my_profile.brand');
    return $result;
  }

}
