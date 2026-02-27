<?php

declare(strict_types=1);

namespace Drupal\jaraba_success_cases\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Form handler for the Success Case entity add/edit forms.
 *
 * HAL-DEMO-V3-BACK-007: Migrado de ContentEntityForm a PremiumEntityFormBase
 * (PREMIUM-FORMS-PATTERN-001, Patrón D — Custom Logic con redirect).
 *
 * 6 secciones premium: Details, Narrative, Quotes, Metrics, Program, SEO.
 * Fieldsets #type=details eliminados — PremiumEntityFormBase gestiona
 * glass-cards, nav pills y secciones automáticamente.
 */
class SuccessCaseForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'details' => [
        'label' => $this->t('Personal Details'),
        'icon' => ['category' => 'users', 'name' => 'user'],
        'description' => $this->t('Core identity information for the success case protagonist.'),
        'fields' => ['name', 'slug', 'hero_image', 'profession', 'company', 'sector', 'location', 'website', 'linkedin'],
      ],
      'narrative' => [
        'label' => $this->t('History (Challenge → Solution → Result)'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('The narrative arc: what was the challenge, how was it solved, and what was achieved.'),
        'fields' => ['challenge_before', 'solution_during', 'result_after'],
      ],
      'quotes' => [
        'label' => $this->t('Testimonial Quotes'),
        'icon' => ['category' => 'ui', 'name' => 'chat'],
        'description' => $this->t('Direct quotes from the protagonist for cards and detail pages.'),
        'fields' => ['quote_short', 'quote_long'],
      ],
      'metrics' => [
        'label' => $this->t('Quantifiable Metrics'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('Key-value metrics and satisfaction rating.'),
        'fields' => ['metrics_json', 'rating'],
      ],
      'program' => [
        'label' => $this->t('Program / Vertical'),
        'icon' => ['category' => 'ui', 'name' => 'link'],
        'description' => $this->t('Program association and vertical classification.'),
        'fields' => ['program_name', 'vertical', 'program_funder', 'program_year'],
      ],
      'seo_control' => [
        'label' => $this->t('SEO & Display Control'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('SEO metadata, display weight, and publication status.'),
        'fields' => ['meta_description', 'weight', 'featured', 'status'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'star'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getCharacterLimits(): array {
    return [
      'quote_short' => 150,
      'meta_description' => 320,
    ];
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
