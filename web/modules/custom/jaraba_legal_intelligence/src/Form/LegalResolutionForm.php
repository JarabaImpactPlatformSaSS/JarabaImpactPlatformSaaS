<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing legal resolutions.
 */
class LegalResolutionForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identification' => [
        'label' => $this->t('Identification'),
        'icon' => ['category' => 'ui', 'name' => 'tag'],
        'description' => $this->t('Source and reference identifiers.'),
        'fields' => ['source_id', 'external_ref', 'content_hash'],
      ],
      'metadata' => [
        'label' => $this->t('Metadata'),
        'icon' => ['category' => 'ui', 'name' => 'document'],
        'description' => $this->t('Resolution title, type, court, and dates.'),
        'fields' => ['title', 'resolution_type', 'issuing_body', 'jurisdiction', 'date_issued', 'date_published', 'status_legal'],
      ],
      'content' => [
        'label' => $this->t('Content'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Full text and source URL.'),
        'fields' => ['full_text', 'original_url'],
      ],
      'ai_generated' => [
        'label' => $this->t('AI Generated'),
        'icon' => ['category' => 'ai', 'name' => 'brain'],
        'description' => $this->t('AI-generated summaries and classifications.'),
        'fields' => ['abstract_ai', 'key_holdings', 'topics', 'cited_legislation'],
      ],
      'eu_fields' => [
        'label' => $this->t('EU Fields'),
        'icon' => ['category' => 'ui', 'name' => 'globe'],
        'description' => $this->t('European Union specific fields.'),
        'fields' => ['celex_number', 'ecli', 'case_number', 'procedure_type', 'respondent_state', 'cedh_articles', 'eu_legal_basis', 'advocate_general', 'importance_level', 'language_original', 'impact_spain'],
      ],
      'qdrant' => [
        'label' => $this->t('Qdrant / NLP'),
        'icon' => ['category' => 'analytics', 'name' => 'search'],
        'description' => $this->t('Vector search and NLP processing data.'),
        'fields' => ['vector_ids', 'qdrant_collection', 'last_nlp_processed'],
      ],
      'seo' => [
        'label' => $this->t('SEO'),
        'icon' => ['category' => 'analytics', 'name' => 'search'],
        'description' => $this->t('Search engine optimization.'),
        'fields' => ['seo_slug'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'document'];
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
