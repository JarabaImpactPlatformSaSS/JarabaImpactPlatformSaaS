<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\ecosistema_jaraba_core\Service\ReviewTenantSettingsResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Item 18: Public review submission controller.
 *
 * Renders the review submission form for authenticated users.
 * Supports slide-panel rendering (SLIDE-PANEL-RENDER-001).
 */
class ReviewSubmitController extends ControllerBase
{

  /**
   * Target ID field per review entity type.
   */
  private const TARGET_ID_FIELD_MAP = [
    'comercio_review' => 'entity_id_ref',
    'review_agro' => 'target_entity_id',
    'review_servicios' => 'provider_id',
    'session_review' => 'session_id',
    'course_review' => 'course_id',
  ];

  /**
   * Body field per review entity type.
   */
  private const BODY_FIELD_MAP = [
    'comercio_review' => 'body',
    'review_agro' => 'comment',
    'review_servicios' => 'comment',
    'session_review' => 'body',
    'course_review' => 'body',
  ];

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    protected readonly FloodInterface $flood,
    protected readonly ?ReviewTenantSettingsResolver $settingsResolver,
  ) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static
  {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('flood'),
      $container->has('ecosistema_jaraba_core.review_tenant_settings_resolver')
      ? $container->get('ecosistema_jaraba_core.review_tenant_settings_resolver')
      : NULL,
    );
  }

  /**
   * Render the review submission form.
   */
  public function submitForm(string $entity_id, Request $request): array|Response
  {
    $reviewEntityType = $request->attributes->get('review_entity_type');
    $targetEntityType = $request->attributes->get('target_entity_type');
    $vertical = $request->attributes->get('vertical');
    $entityId = (int) $entity_id;

    if ($reviewEntityType === NULL || $targetEntityType === NULL) {
      throw new NotFoundHttpException();
    }

    // Verify target entity exists.
    try {
      $target = $this->entityTypeManager()->getStorage($targetEntityType)->load($entityId);
    } catch (\Exception) {
      $target = NULL;
    }
    if ($target === NULL) {
      throw new NotFoundHttpException();
    }

    $uid = (int) $this->currentUser()->id();

    // Flood protection: 1 review per user/target/24h.
    $floodId = 'review_submit_' . $reviewEntityType . '_' . $entityId . '_' . $uid;
    if (!$this->flood->isAllowed($floodId, 1, 86400)) {
      throw new AccessDeniedHttpException($this->t('Ya has enviado una resena para este recurso recientemente. Intenta de nuevo en 24 horas.'));
    }

    // Check if user already reviewed this target.
    $targetField = self::TARGET_ID_FIELD_MAP[$reviewEntityType] ?? NULL;
    if ($targetField !== NULL) {
      try {
        $existing = $this->entityTypeManager()->getStorage($reviewEntityType)->getQuery()
          ->accessCheck(FALSE)
          ->condition('uid', $uid)
          ->condition($targetField, $entityId)
          ->count()
          ->execute();
        if ((int) $existing > 0) {
          throw new AccessDeniedHttpException($this->t('Ya has dejado una resena para este recurso.'));
        }
      } catch (AccessDeniedHttpException $e) {
        throw $e;
      } catch (\Exception) {
      }
    }

    // Resolve tenant settings.
    $tenantGroupId = 0;
    if ($target->hasField('tenant_id') && !$target->get('tenant_id')->isEmpty()) {
      $tenantGroupId = (int) ($target->get('tenant_id')->target_id ?? $target->get('tenant_id')->value ?? 0);
    }
    $settings = $this->settingsResolver?->getSettingsForTenant($tenantGroupId);

    $build = [
      '#theme' => 'review_submit_form',
      '#vertical' => $vertical,
      '#target_label' => $target->label() ?? '',
      '#target_id' => $entityId,
      '#review_entity_type' => $reviewEntityType,
      '#settings' => [
        'min_length' => $settings?->getMinReviewLength() ?? 10,
        'max_length' => $settings?->getMaxReviewLength() ?? 5000,
        'require_rating' => $settings?->isRatingRequired() ?? TRUE,
        'allow_photos' => $settings?->arePhotosAllowed() ?? TRUE,
        'max_photos' => $settings?->getMaxPhotos() ?? 5,
      ],
      '#attached' => [
        'library' => ['ecosistema_jaraba_core/review-interactions'],
        'drupalSettings' => [
          'reviewSubmit' => [
            'vertical' => $vertical,
            'targetId' => $entityId,
            'reviewEntityType' => $reviewEntityType,
            'bodyField' => self::BODY_FIELD_MAP[$reviewEntityType] ?? 'body',
            'targetField' => $targetField,
            'minLength' => $settings?->getMinReviewLength() ?? 10,
            'maxLength' => $settings?->getMaxReviewLength() ?? 5000,
          ],
        ],
      ],
      '#cache' => [
        'contexts' => ['user', 'url.path'],
        'max-age' => 0,
      ],
    ];

    return $build;
  }

}
