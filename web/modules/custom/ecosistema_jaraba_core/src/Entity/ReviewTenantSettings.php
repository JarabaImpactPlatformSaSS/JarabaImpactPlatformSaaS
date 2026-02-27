<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Review Tenant Settings config entity.
 *
 * B-10: Per-tenant review configuration.
 * Allows each tenant to configure review behavior (auto-approve,
 * moderation, minimum length, notification preferences).
 *
 * @ConfigEntityType(
 *   id = "review_tenant_settings",
 *   label = @Translation("Review Tenant Settings"),
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\ecosistema_jaraba_core\Form\ReviewTenantSettingsForm",
 *     },
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *   },
 *   config_prefix = "review_tenant_settings",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "tenant_id",
 *     "auto_approve",
 *     "moderation_enabled",
 *     "min_review_length",
 *     "max_review_length",
 *     "require_rating",
 *     "allow_photos",
 *     "max_photos",
 *     "notify_owner_new_review",
 *     "notify_author_approved",
 *     "notify_author_responded",
 *     "fake_detection_enabled",
 *     "sentiment_analysis_enabled",
 *     "response_enabled",
 *     "helpfulness_voting_enabled",
 *     "reviews_per_page",
 *   },
 * )
 */
class ReviewTenantSettings extends ConfigEntityBase {

  /**
   * The machine name.
   */
  protected string $id = '';

  /**
   * Human-readable label.
   */
  protected string $label = '';

  /**
   * Tenant ID (group ID).
   */
  protected int $tenant_id = 0;

  /**
   * Whether to auto-approve reviews.
   */
  protected bool $auto_approve = FALSE;

  /**
   * Whether moderation is enabled.
   */
  protected bool $moderation_enabled = TRUE;

  /**
   * Minimum review body length.
   */
  protected int $min_review_length = 10;

  /**
   * Maximum review body length.
   */
  protected int $max_review_length = 5000;

  /**
   * Whether rating is required.
   */
  protected bool $require_rating = TRUE;

  /**
   * Whether photo uploads are allowed.
   */
  protected bool $allow_photos = TRUE;

  /**
   * Maximum photos per review.
   */
  protected int $max_photos = 5;

  /**
   * Notify owner on new review.
   */
  protected bool $notify_owner_new_review = TRUE;

  /**
   * Notify author on approval.
   */
  protected bool $notify_author_approved = TRUE;

  /**
   * Notify author on owner response.
   */
  protected bool $notify_author_responded = TRUE;

  /**
   * Enable fake review detection.
   */
  protected bool $fake_detection_enabled = TRUE;

  /**
   * Enable sentiment analysis.
   */
  protected bool $sentiment_analysis_enabled = TRUE;

  /**
   * Enable owner responses.
   */
  protected bool $response_enabled = TRUE;

  /**
   * Enable helpfulness voting.
   */
  protected bool $helpfulness_voting_enabled = TRUE;

  /**
   * Reviews per page.
   */
  protected int $reviews_per_page = 10;

  /**
   * Get tenant ID.
   */
  public function getTenantId(): int {
    return $this->tenant_id;
  }

  /**
   * Whether auto-approve is enabled.
   */
  public function isAutoApprove(): bool {
    return $this->auto_approve;
  }

  /**
   * Whether moderation is enabled.
   */
  public function isModerationEnabled(): bool {
    return $this->moderation_enabled;
  }

  /**
   * Get minimum review length.
   */
  public function getMinReviewLength(): int {
    return $this->min_review_length;
  }

  /**
   * Get maximum review length.
   */
  public function getMaxReviewLength(): int {
    return $this->max_review_length;
  }

  /**
   * Whether rating is required.
   */
  public function isRatingRequired(): bool {
    return $this->require_rating;
  }

  /**
   * Whether photos are allowed.
   */
  public function arePhotosAllowed(): bool {
    return $this->allow_photos;
  }

  /**
   * Get max photos count.
   */
  public function getMaxPhotos(): int {
    return $this->max_photos;
  }

  /**
   * Whether to notify owner on new review.
   */
  public function shouldNotifyOwnerNewReview(): bool {
    return $this->notify_owner_new_review;
  }

  /**
   * Whether to notify author on approval.
   */
  public function shouldNotifyAuthorApproved(): bool {
    return $this->notify_author_approved;
  }

  /**
   * Whether to notify author on owner response.
   */
  public function shouldNotifyAuthorResponded(): bool {
    return $this->notify_author_responded;
  }

  /**
   * Whether fake detection is enabled.
   */
  public function isFakeDetectionEnabled(): bool {
    return $this->fake_detection_enabled;
  }

  /**
   * Whether sentiment analysis is enabled.
   */
  public function isSentimentAnalysisEnabled(): bool {
    return $this->sentiment_analysis_enabled;
  }

  /**
   * Whether owner responses are enabled.
   */
  public function isResponseEnabled(): bool {
    return $this->response_enabled;
  }

  /**
   * Whether helpfulness voting is enabled.
   */
  public function isHelpfulnessVotingEnabled(): bool {
    return $this->helpfulness_voting_enabled;
  }

  /**
   * Get reviews per page.
   */
  public function getReviewsPerPage(): int {
    return $this->reviews_per_page;
  }

}
