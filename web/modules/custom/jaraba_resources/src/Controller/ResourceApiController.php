<?php

declare(strict_types=1);

namespace Drupal\jaraba_resources\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_resources\Service\SubscriptionService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API Controller for Resources.
 */
class ResourceApiController extends ControllerBase
{

    /**
     * The subscription service.
     */
    protected SubscriptionService $subscriptionService;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        $instance = new static();
        $instance->subscriptionService = $container->get('jaraba_resources.subscription_service');
        return $instance;
    }

    /**
     * GET /api/v1/kits - List digital kits.
     */
    public function listKits(Request $request): JsonResponse
    {
        $storage = $this->entityTypeManager()->getStorage('digital_kit');

        $query = $storage->getQuery()
            ->condition('status', 'published')
            ->accessCheck(TRUE)
            ->sort('is_featured', 'DESC')
            ->sort('download_count', 'DESC')
            ->range(0, 50);

        // Optional filters.
        if ($category = $request->query->get('category')) {
            $query->condition('category', $category);
        }
        if ($accessLevel = $request->query->get('access_level')) {
            $query->condition('access_level', $accessLevel);
        }

        $ids = $query->execute();
        $kits = $storage->loadMultiple($ids);

        $data = [];
        foreach ($kits as $kit) {
            $data[] = $this->serializeKit($kit);
        }

        return new JsonResponse(['success' => TRUE, 'data' => $data, 'meta' => ['count' => count($data), 'timestamp' => time()]]);
    }

    /**
     * GET /api/v1/kits/{id} - Get kit details.
     */
    public function getKit(int $id): JsonResponse
    {
        $kit = $this->entityTypeManager()->getStorage('digital_kit')->load($id);

        if (!$kit) {
            return // AUDIT-CONS-N08: Standardized JSON envelope.
        new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Kit not found']], 404);
        }

        $canAccess = $this->subscriptionService->canAccessKit($kit);
        $data = $this->serializeKit($kit, TRUE);
        $data['can_download'] = $canAccess;

        if (!$canAccess) {
            $data['required_plan'] = $kit->getAccessLevel();
        }

        return new JsonResponse(['success' => TRUE, 'data' => $data, 'meta' => ['timestamp' => time()]]);
    }

    /**
     * POST /api/v1/kits/{id}/download - Download a kit.
     */
    public function downloadKit(int $id): JsonResponse
    {
        $kit = $this->entityTypeManager()->getStorage('digital_kit')->load($id);

        if (!$kit) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Kit not found']], 404);
        }

        if (!$this->subscriptionService->canAccessKit($kit)) {
            return new JsonResponse([
                'error' => 'Subscription required',
                'required_plan' => $kit->getAccessLevel(),
            ], 403);
        }

        // Increment download count.
        $kit->incrementDownloadCount();
        $kit->save();

        // Get download URLs.
        $files = $kit->get('files');
        $downloadUrls = [];

        foreach ($files as $item) {
            if ($file = $item->entity) {
                $downloadUrls[] = [
                    'filename' => $file->getFilename(),
                    'url' => \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri()),
                ];
            }
        }

        return new JsonResponse(['success' => TRUE, 'data' => ['message' => 'Download granted', 'files' => $downloadUrls], 'meta' => ['timestamp' => time()]]);
    }

    /**
     * POST /api/v1/kits/{id}/rate - Rate a kit.
     */
    public function rateKit(int $id, Request $request): JsonResponse
    {
        $kit = $this->entityTypeManager()->getStorage('digital_kit')->load($id);

        if (!$kit) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Kit not found']], 404);
        }

        $data = json_decode($request->getContent(), TRUE);
        $rating = (float) ($data['rating'] ?? 0);

        if ($rating < 1 || $rating > 5) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Rating must be between 1 and 5']], 400);
        }

        // Calculate new average.
        $currentRating = (float) $kit->get('rating')->value;
        $ratingCount = (int) $kit->get('rating_count')->value;

        $newCount = $ratingCount + 1;
        $newRating = (($currentRating * $ratingCount) + $rating) / $newCount;

        $kit->set('rating', round($newRating, 2));
        $kit->set('rating_count', $newCount);
        $kit->save();

        return new JsonResponse(['success' => TRUE, 'data' => ['message' => 'Rating submitted', 'new_rating' => $newRating,
            'total_ratings' => $newCount], 'meta' => ['timestamp' => time()]]);
    }

    /**
     * GET /api/v1/plans - List membership plans.
     */
    public function listPlans(): JsonResponse
    {
        $plans = $this->subscriptionService->getAvailablePlans();

        $data = [];
        foreach ($plans as $plan) {
            $data[] = $this->serializePlan($plan);
        }

        // Sort by display order.
        usort($data, fn($a, $b) => $a['display_order'] <=> $b['display_order']);

        return new JsonResponse(['success' => TRUE, 'data' => $data, 'meta' => ['timestamp' => time()]]);
    }

    /**
     * GET /api/v1/subscription - Get current user's subscription.
     */
    public function getSubscription(): JsonResponse
    {
        $subscription = $this->subscriptionService->getCurrentSubscription();

        if (!$subscription) {
            return new JsonResponse([
                'has_subscription' => FALSE,
                'plan_type' => 'free',
            ]);
        }

        $plan = $subscription->getPlan();

        return new JsonResponse([
            'has_subscription' => TRUE,
            'plan_type' => $plan ? $plan->getPlanType() : 'free',
            'plan_name' => $plan ? $plan->getName() : 'Gratuito',
            'status' => $subscription->getSubscriptionStatus(),
            'current_period_end' => $subscription->getCurrentPeriodEnd(),
            'remaining_mentoring_sessions' => $subscription->getRemainingMentoringSessions(),
        ]);
    }

    /**
     * POST /api/v1/subscription/trial - Start a trial.
     */
    public function startTrial(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);
        $planId = $data['plan_id'] ?? NULL;

        if (!$planId) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'plan_id required']], 400);
        }

        try {
            $subscription = $this->subscriptionService->createTrialSubscription(
                $this->currentUser()->id(),
                (int) $planId
            );

            return new JsonResponse(['success' => TRUE, 'data' => ['message' => 'Trial started', 'subscription_id' => $subscription->id(),
                'trial_end' => $subscription->get('trial_end')->value], 'meta' => ['timestamp' => time()]]);
        } catch (\Exception $e) {
            \Drupal::logger('jaraba_resources')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.']], 400);
        }
    }

    /**
     * POST /api/v1/subscription/cancel - Cancel subscription.
     */
    public function cancelSubscription(): JsonResponse
    {
        $subscription = $this->subscriptionService->getCurrentSubscription();

        if (!$subscription) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'No active subscription']], 404);
        }

        $this->subscriptionService->cancelSubscription($subscription);

        return new JsonResponse(['success' => TRUE, 'data' => ['message' => 'Subscription cancelled', 'active_until' => $subscription->getCurrentPeriodEnd()], 'meta' => ['timestamp' => time()]]);
    }

    /**
     * GET /api/v1/kits/categories - List kit categories.
     */
    public function listCategories(): JsonResponse
    {
        return new JsonResponse([
            'data' => [
                ['id' => 'business_plan', 'name' => 'Plan de Negocio'],
                ['id' => 'marketing', 'name' => 'Marketing y Ventas'],
                ['id' => 'finance', 'name' => 'Finanzas'],
                ['id' => 'legal', 'name' => 'Legal y Administrativo'],
                ['id' => 'operations', 'name' => 'Operaciones'],
                ['id' => 'digital', 'name' => 'Transformación Digital'],
                ['id' => 'hr', 'name' => 'Recursos Humanos'],
            ],
        ]);
    }

    /**
     * Serializes a kit entity.
     */
    protected function serializeKit($kit, bool $includeDetails = FALSE): array
    {
        $imageUrl = NULL;
        if ($kit->get('image')->entity) {
            $imageUrl = \Drupal::service('file_url_generator')
                ->generateAbsoluteString($kit->get('image')->entity->getFileUri());
        }

        $data = [
            'id' => $kit->id(),
            'uuid' => $kit->uuid(),
            'name' => $kit->getName(),
            'category' => $kit->getCategory(),
            'access_level' => $kit->getAccessLevel(),
            'image_url' => $imageUrl,
            'download_count' => $kit->getDownloadCount(),
            'rating' => $kit->getRating(),
            'is_featured' => (bool) $kit->get('is_featured')->value,
            'is_new' => (bool) $kit->get('is_new')->value,
        ];

        if ($includeDetails) {
            $data['description'] = $kit->get('description')->value;
            $data['sectors'] = $kit->getSectors();
            $data['rating_count'] = (int) $kit->get('rating_count')->value;
            $data['file_count'] = count($kit->get('files'));
        }

        return $data;
    }

    /**
     * Serializes a plan entity.
     */
    protected function serializePlan($plan): array
    {
        return [
            'id' => $plan->id(),
            'uuid' => $plan->uuid(),
            'name' => $plan->getName(),
            'description' => $plan->get('description')->value,
            'plan_type' => $plan->getPlanType(),
            'price' => $plan->getPrice(),
            'billing_interval' => $plan->getBillingInterval(),
            'features' => $plan->getFeatures(),
            'kit_access_level' => $plan->getKitAccessLevel(),
            'max_mentoring_sessions' => $plan->getMaxMentoringSessions(),
            'max_groups' => $plan->getMaxGroups(),
            'has_ai_features' => $plan->hasAiFeatures(),
            'has_priority_support' => $plan->hasPrioritySupport(),
            'is_featured' => (bool) $plan->get('is_featured')->value,
            'display_order' => (int) $plan->get('display_order')->value,
        ];
    }

}
