<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing job postings.
 */
class JobPostingForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'job_details' => [
        'label' => $this->t('Job Details'),
        'icon' => ['category' => 'business', 'name' => 'briefcase'],
        'description' => $this->t('Position title and description.'),
        'fields' => ['title', 'reference_code', 'slug', 'description', 'requirements', 'responsibilities', 'employer_id', 'category_id'],
      ],
      'location' => [
        'label' => $this->t('Location'),
        'icon' => ['category' => 'ui', 'name' => 'map-pin'],
        'description' => $this->t('Work location and remote options.'),
        'fields' => ['location_city', 'location_province', 'location_country', 'location_lat', 'location_lng', 'remote_type', 'job_type'],
      ],
      'requirements' => [
        'label' => $this->t('Requirements'),
        'icon' => ['category' => 'ui', 'name' => 'check'],
        'description' => $this->t('Experience, education, skills, and languages.'),
        'fields' => ['experience_level', 'education_level', 'skills_required', 'skills_preferred', 'languages'],
      ],
      'compensation' => [
        'label' => $this->t('Compensation'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('Salary range and benefits.'),
        'fields' => ['salary_min', 'salary_max', 'salary_currency', 'salary_period', 'salary_visible', 'benefits'],
      ],
      'application' => [
        'label' => $this->t('Application'),
        'icon' => ['category' => 'ui', 'name' => 'send'],
        'description' => $this->t('How candidates can apply.'),
        'fields' => ['application_method', 'external_url', 'application_email', 'vacancies'],
      ],
      'publishing' => [
        'label' => $this->t('Publishing'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Publication status and dates.'),
        'fields' => ['status', 'visibility', 'is_featured', 'featured_until', 'published_at', 'expires_at', 'closed_at', 'tenant_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'business', 'name' => 'briefcase'];
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
