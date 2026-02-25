<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for Business Model Canvas entities.
 */
class BusinessModelCanvasForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'canvas' => [
        'label' => $this->t('Canvas'),
        'icon' => ['category' => 'business', 'name' => 'chart'],
        'fields' => ['title', 'description', 'sector', 'business_stage', 'version'],
      ],
      'references' => [
        'label' => $this->t('References'),
        'icon' => ['category' => 'ui', 'name' => 'link'],
        'fields' => ['business_diagnostic_id', 'template_source_id'],
      ],
      'analysis' => [
        'label' => $this->t('Analysis'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'fields' => ['completeness_score', 'coherence_score', 'last_ai_analysis'],
      ],
      'sharing' => [
        'label' => $this->t('Sharing'),
        'icon' => ['category' => 'users', 'name' => 'group'],
        'fields' => ['shared_with', 'is_template'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'fields' => ['status', 'tenant_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'business', 'name' => 'chart'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    $canCreate = $this->currentUser()->hasPermission('create business model canvas template')
        || $this->currentUser()->hasPermission('administer business model canvas');

    if (!$canCreate) {
      foreach (['is_template', 'tenant_id'] as $field) {
        if (isset($form['premium_section_sharing'][$field])) {
          $form['premium_section_sharing'][$field]['#access'] = FALSE;
        }
        elseif (isset($form['premium_section_status'][$field])) {
          $form['premium_section_status'][$field]['#access'] = FALSE;
        }
        elseif (isset($form['premium_section_other'][$field])) {
          $form['premium_section_other'][$field]['#access'] = FALSE;
        }
      }
    }

    return $form;
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
