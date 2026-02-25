<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating and editing ExperimentVariant entities.
 *
 * Spec: Doc 168 - Platform_AB_Testing_Pages_v1
 *
 * Variants are created/edited within the context of a parent experiment.
 * Extends PremiumEntityFormBase for glassmorphism sections and premium UX.
 */
class ExperimentVariantForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'basic' => [
        'label' => $this->t('Variant info'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Name, control flag, and traffic weight.'),
        'fields' => ['name', 'is_control', 'traffic_weight'],
      ],
      'content' => [
        'label' => $this->t('Content modifications'),
        'icon' => ['category' => 'ui', 'name' => 'code'],
        'description' => $this->t('Changes this variant applies relative to control.'),
        'fields' => ['content_data'],
      ],
      'metrics' => [
        'label' => $this->t('Metrics'),
        'icon' => ['category' => 'analytics', 'name' => 'gauge'],
        'description' => $this->t('Performance data (updated automatically).'),
        'fields' => ['visitors', 'conversions'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'layers'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    // Add help text to content_data.
    if (isset($form['premium_section_content']['content_data'])) {
      $form['premium_section_content']['content_data']['#description'] = $this->t(
        'JSON with modifications: texts, styles, classes, visibility. Example: {"texts": {"#hero-title": "New title"}}'
      );
    }

    // Make metric fields read-only.
    foreach (['visitors', 'conversions'] as $field) {
      if (isset($form['premium_section_metrics'][$field])) {
        $form['premium_section_metrics'][$field]['#disabled'] = TRUE;
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    // Redirect back to the parent experiment.
    $entity = $this->getEntity();
    $experimentId = $entity->getExperimentId();
    if ($experimentId) {
      $form_state->setRedirect('entity.page_experiment.canonical', [
        'page_experiment' => $experimentId,
      ]);
    }

    return $result;
  }

}
