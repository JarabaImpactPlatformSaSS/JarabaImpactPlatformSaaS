<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing courses.
 */
class CourseForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'basic_info' => [
        'label' => $this->t('Basic Information'),
        'icon' => ['category' => 'education', 'name' => 'book'],
        'description' => $this->t('Course title and description.'),
        'fields' => ['title', 'machine_name', 'summary', 'description'],
      ],
      'course_settings' => [
        'label' => $this->t('Course Settings'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Duration, difficulty, and category.'),
        'fields' => ['duration_minutes', 'difficulty_level', 'vertical_id', 'field_category', 'prerequisites', 'tags'],
      ],
      'publishing' => [
        'label' => $this->t('Publishing'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Publication and premium status.'),
        'fields' => ['is_published', 'is_premium', 'thumbnail', 'author_id', 'tenant_id'],
      ],
      'pricing' => [
        'label' => $this->t('Pricing'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('Price and credits.'),
        'fields' => ['price', 'currency', 'completion_credits', 'certificate_template_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'education', 'name' => 'book'];
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
