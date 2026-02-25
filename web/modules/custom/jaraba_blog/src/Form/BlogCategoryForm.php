<?php

declare(strict_types=1);

namespace Drupal\jaraba_blog\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing blog categories.
 */
class BlogCategoryForm extends PremiumEntityFormBase {

  protected function getSectionDefinitions(): array {
    return [
      'general' => [
        'label' => $this->t('General'),
        'icon' => ['category' => 'ui', 'name' => 'folder'],
        'description' => $this->t('Basic category information.'),
        'fields' => ['name', 'slug', 'description', 'parent_id'],
      ],
      'appearance' => [
        'label' => $this->t('Appearance'),
        'icon' => ['category' => 'ui', 'name' => 'palette'],
        'description' => $this->t('Visual customization options.'),
        'fields' => ['icon', 'color', 'weight'],
      ],
      'seo' => [
        'label' => $this->t('SEO'),
        'icon' => ['category' => 'analytics', 'name' => 'search'],
        'description' => $this->t('Search engine optimization settings.'),
        'fields' => ['meta_title', 'meta_description'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Activation and tenant settings.'),
        'fields' => ['is_active', 'tenant_id'],
      ],
    ];
  }

  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'folder'];
  }

  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
    return $result;
  }

}
