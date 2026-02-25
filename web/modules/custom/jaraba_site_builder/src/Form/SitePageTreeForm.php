<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for page tree nodes.
 */
class SitePageTreeForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'hierarchy' => [
        'label' => $this->t('Tree Position'),
        'icon' => ['category' => 'ui', 'name' => 'layout'],
        'description' => $this->t('Page, parent node and ordering.'),
        'fields' => ['page_id', 'parent_id', 'weight'],
      ],
      'visibility' => [
        'label' => $this->t('Visibility'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Where this page appears in the site.'),
        'fields' => ['show_in_navigation', 'show_in_sitemap', 'show_in_footer', 'show_in_breadcrumbs'],
      ],
      'nav_override' => [
        'label' => $this->t('Menu Customization'),
        'icon' => ['category' => 'ui', 'name' => 'menu'],
        'description' => $this->t('Navigation title, icon and external URL overrides.'),
        'fields' => ['nav_title', 'nav_icon', 'nav_highlight', 'nav_external_url'],
      ],
      'publishing' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Publication status and date.'),
        'fields' => ['status', 'published_at'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'layout'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    // Hide technical fields.
    if (isset($form['premium_section_other']['depth'])) {
      $form['premium_section_other']['depth']['#access'] = FALSE;
    }
    if (isset($form['premium_section_other']['path'])) {
      $form['premium_section_other']['path']['#access'] = FALSE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirect('jaraba_site_builder.tree');
    return $result;
  }

}
