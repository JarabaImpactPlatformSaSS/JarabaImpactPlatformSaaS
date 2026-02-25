<?php

declare(strict_types=1);

namespace Drupal\jaraba_events\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing event landing pages.
 */
class EventLandingPageForm extends PremiumEntityFormBase {

  protected function getSectionDefinitions(): array {
    return [
      'page' => [
        'label' => $this->t('Page'),
        'icon' => ['category' => 'ui', 'name' => 'layout'],
        'description' => $this->t('Landing page content and layout.'),
        'fields' => ['event_id', 'title', 'slug', 'layout', 'hero_image', 'hero_video_url', 'description'],
      ],
      'cta' => [
        'label' => $this->t('Call to Action'),
        'icon' => ['category' => 'ui', 'name' => 'target'],
        'description' => $this->t('Call to action and display options.'),
        'fields' => ['cta_text', 'cta_color', 'show_speakers', 'show_schedule', 'show_testimonials'],
      ],
      'custom' => [
        'label' => $this->t('Custom Code'),
        'icon' => ['category' => 'ui', 'name' => 'code'],
        'description' => $this->t('Custom CSS and JavaScript.'),
        'fields' => ['custom_css', 'custom_js'],
      ],
      'seo' => [
        'label' => $this->t('SEO'),
        'icon' => ['category' => 'analytics', 'name' => 'search'],
        'description' => $this->t('Search engine optimization settings.'),
        'fields' => ['seo_title', 'seo_description', 'og_image', 'schema_json'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Publication and tenant settings.'),
        'fields' => ['is_published', 'tenant_id'],
      ],
    ];
  }

  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'layout'];
  }

  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->getEntity();
    if ($entity->isNew() && empty($entity->get('slug')->value)) {
      $title = $entity->get('title')->value ?? '';
      $slug = mb_strtolower(trim($title));
      $slug = preg_replace('/[áàäâ]/u', 'a', $slug);
      $slug = preg_replace('/[éèëê]/u', 'e', $slug);
      $slug = preg_replace('/[íìïî]/u', 'i', $slug);
      $slug = preg_replace('/[óòöô]/u', 'o', $slug);
      $slug = preg_replace('/[úùüû]/u', 'u', $slug);
      $slug = preg_replace('/ñ/u', 'n', $slug);
      $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
      $slug = trim($slug, '-');
      $entity->set('slug', $slug);
    }
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
