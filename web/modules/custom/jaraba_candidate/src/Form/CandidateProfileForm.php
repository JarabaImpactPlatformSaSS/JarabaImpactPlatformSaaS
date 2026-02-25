<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing candidate profiles.
 */
class CandidateProfileForm extends PremiumEntityFormBase {

  protected function getSectionDefinitions(): array {
    return [
      'personal' => [
        'label' => $this->t('Personal'),
        'icon' => ['category' => 'ui', 'name' => 'user'],
        'description' => $this->t('Personal identification and contact details.'),
        'fields' => ['first_name', 'last_name', 'email', 'phone', 'photo', 'date_of_birth', 'nationality'],
      ],
      'professional' => [
        'label' => $this->t('Professional'),
        'icon' => ['category' => 'business', 'name' => 'briefcase'],
        'description' => $this->t('Professional background and experience.'),
        'fields' => ['headline', 'summary', 'experience_years', 'experience_level', 'education_level'],
      ],
      'location' => [
        'label' => $this->t('Location'),
        'icon' => ['category' => 'ui', 'name' => 'map-pin'],
        'description' => $this->t('Current location and relocation preferences.'),
        'fields' => ['city', 'province', 'country', 'postal_code', 'willing_to_relocate', 'relocation_countries'],
      ],
      'preferences' => [
        'label' => $this->t('Preferences'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Job preferences and availability.'),
        'fields' => ['availability_status', 'available_from', 'preferred_job_types', 'preferred_remote_types', 'preferred_industries', 'salary_expectation'],
      ],
      'online' => [
        'label' => $this->t('Online Presence'),
        'icon' => ['category' => 'ui', 'name' => 'link'],
        'description' => $this->t('Links to online profiles and portfolio.'),
        'fields' => ['linkedin_url', 'github_url', 'portfolio_url', 'website_url', 'cv_file_id'],
      ],
      'privacy' => [
        'label' => $this->t('Privacy'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'description' => $this->t('Privacy and visibility settings.'),
        'fields' => ['is_public', 'show_photo', 'show_contact', 'is_verified', 'tenant_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormTitle() {
    $entity = $this->entity;
    if ($entity->isNew()) {
      return $this->t('Nuevo Perfil de Candidato');
    }
    $name = $entity->get('first_name')->value ?? '';
    $last = $entity->get('last_name')->value ?? '';
    $full = trim($name . ' ' . $last);
    return $this->t('Editar perfil: @name', ['@name' => $full ?: $this->t('Candidato')]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormSubtitle() {
    return $this->t('Completa todas las secciones para maximizar tu visibilidad.');
  }

  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'user'];
  }

  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->getEntity();
    if ($entity->isNew() && empty($entity->get('user_id')->target_id)) {
      $entity->set('user_id', $this->currentUser()->id());
    }
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($entity->toUrl('edit-form'));
    return $result;
  }

}
