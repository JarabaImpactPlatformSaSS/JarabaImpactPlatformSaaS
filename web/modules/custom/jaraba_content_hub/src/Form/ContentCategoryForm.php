<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form controller for the ContentCategory entity.
 *
 * Extends PremiumEntityFormBase to get glassmorphism sections, pill
 * navigation, and premium UX for category management.
 */
class ContentCategoryForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'general' => [
        'label' => $this->t('General'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Name, slug, and description.'),
        'fields' => ['name', 'slug', 'description'],
      ],
      'hierarchy' => [
        'label' => $this->t('Hierarchy'),
        'icon' => ['category' => 'ui', 'name' => 'folder'],
        'description' => $this->t('Parent category and display order.'),
        'fields' => ['parent', 'weight'],
      ],
      'appearance' => [
        'label' => $this->t('Appearance'),
        'icon' => ['category' => 'ui', 'name' => 'palette'],
        'description' => $this->t('Color, icon, and featured image.'),
        'fields' => ['color', 'icon', 'featured_image'],
      ],
      'seo' => [
        'label' => $this->t('SEO'),
        'icon' => ['category' => 'analytics', 'name' => 'search'],
        'description' => $this->t('Meta title and description for search engines.'),
        'fields' => ['meta_title', 'meta_description'],
      ],
      'settings' => [
        'label' => $this->t('Settings'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Active status and tenant assignment.'),
        'fields' => ['is_active', 'tenant_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'folder'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    // Add color picker help text.
    if (isset($form['premium_section_appearance']['color'])) {
      $form['premium_section_appearance']['color']['widget'][0]['value']['#description'] = $this->t('Enter a hex color code (e.g., #233D63).');
    }

    // Add icon help text.
    if (isset($form['premium_section_appearance']['icon'])) {
      $form['premium_section_appearance']['icon']['widget'][0]['value']['#description'] = $this->t('Enter an icon name from the icon library (e.g., folder, tag, bookmark).');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getCharacterLimits(): array {
    return [
      'meta_title' => 70,
      'meta_description' => 160,
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
