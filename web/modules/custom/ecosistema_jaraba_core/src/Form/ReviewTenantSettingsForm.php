<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for ReviewTenantSettings config entity.
 *
 * B-10: Per-tenant review configuration form.
 */
class ReviewTenantSettingsForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\ecosistema_jaraba_core\Entity\ReviewTenantSettings $settings */
    $settings = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $settings->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $settings->id(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
      ],
      '#disabled' => !$settings->isNew(),
    ];

    $form['tenant_id'] = [
      '#type' => 'number',
      '#title' => $this->t('Tenant ID'),
      '#default_value' => $settings->getTenantId(),
      '#min' => 0,
      '#required' => TRUE,
    ];

    // Moderation settings.
    $form['moderation'] = [
      '#type' => 'details',
      '#title' => $this->t('Moderation'),
      '#open' => TRUE,
    ];

    $form['moderation']['auto_approve'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto-approve reviews'),
      '#default_value' => $settings->isAutoApprove(),
    ];

    $form['moderation']['moderation_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable moderation queue'),
      '#default_value' => $settings->isModerationEnabled(),
    ];

    $form['moderation']['fake_detection_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable fake review detection'),
      '#default_value' => $settings->isFakeDetectionEnabled(),
    ];

    $form['moderation']['sentiment_analysis_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable sentiment analysis'),
      '#default_value' => $settings->isSentimentAnalysisEnabled(),
    ];

    // Content settings.
    $form['content'] = [
      '#type' => 'details',
      '#title' => $this->t('Content Settings'),
      '#open' => TRUE,
    ];

    $form['content']['min_review_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum review length'),
      '#default_value' => $settings->getMinReviewLength(),
      '#min' => 0,
      '#max' => 1000,
    ];

    $form['content']['max_review_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum review length'),
      '#default_value' => $settings->getMaxReviewLength(),
      '#min' => 100,
      '#max' => 50000,
    ];

    $form['content']['require_rating'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Require rating'),
      '#default_value' => $settings->isRatingRequired(),
    ];

    $form['content']['allow_photos'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow photo uploads'),
      '#default_value' => $settings->arePhotosAllowed(),
    ];

    $form['content']['max_photos'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum photos per review'),
      '#default_value' => $settings->getMaxPhotos(),
      '#min' => 1,
      '#max' => 20,
    ];

    // Features.
    $form['features'] = [
      '#type' => 'details',
      '#title' => $this->t('Features'),
      '#open' => TRUE,
    ];

    $form['features']['response_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable owner responses'),
      '#default_value' => $settings->isResponseEnabled(),
    ];

    $form['features']['helpfulness_voting_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable helpfulness voting'),
      '#default_value' => $settings->isHelpfulnessVotingEnabled(),
    ];

    $form['features']['reviews_per_page'] = [
      '#type' => 'number',
      '#title' => $this->t('Reviews per page'),
      '#default_value' => $settings->getReviewsPerPage(),
      '#min' => 5,
      '#max' => 50,
    ];

    // Notifications.
    $form['notifications'] = [
      '#type' => 'details',
      '#title' => $this->t('Notifications'),
      '#open' => TRUE,
    ];

    $form['notifications']['notify_owner_new_review'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Notify owner on new review'),
      '#default_value' => $settings->shouldNotifyOwnerNewReview(),
    ];

    $form['notifications']['notify_author_approved'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Notify author on approval'),
      '#default_value' => $settings->shouldNotifyAuthorApproved(),
    ];

    $form['notifications']['notify_author_responded'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Notify author on owner response'),
      '#default_value' => $settings->shouldNotifyAuthorResponded(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $this->messenger()->addStatus($this->t('Review settings @label saved.', [
      '@label' => $this->entity->label(),
    ]));

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

  /**
   * Check if entity exists.
   */
  public function exists(string $id): bool {
    return (bool) $this->entityTypeManager->getStorage('review_tenant_settings')->load($id);
  }

}
