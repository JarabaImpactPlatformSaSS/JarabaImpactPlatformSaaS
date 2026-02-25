<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for site menu items.
 */
class SiteMenuItemForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'basic' => [
        'label' => $this->t('Item Information'),
        'icon' => ['category' => 'ui', 'name' => 'link'],
        'description' => $this->t('Title, parent menu and item type.'),
        'fields' => ['title', 'menu_id', 'parent_id', 'item_type'],
      ],
      'link' => [
        'label' => $this->t('Link'),
        'icon' => ['category' => 'ui', 'name' => 'link'],
        'description' => $this->t('URL, linked page and target behavior.'),
        'fields' => ['url', 'page_id', 'open_in_new_tab'],
      ],
      'appearance' => [
        'label' => $this->t('Appearance'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Icon, badge and highlight settings.'),
        'fields' => ['icon', 'badge_text', 'badge_color', 'highlight'],
      ],
      'advanced' => [
        'label' => $this->t('Advanced'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Mega menu content, status and ordering.'),
        'fields' => ['mega_content', 'is_enabled', 'weight', 'depth'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'link'];
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
