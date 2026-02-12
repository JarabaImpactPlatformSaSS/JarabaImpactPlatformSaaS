<?php

declare(strict_types=1);

namespace Drupal\jaraba_resources\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Service for managing digital kit access and downloads.
 */
class KitService
{

    /**
     * The entity type manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * The current user.
     */
    protected AccountProxyInterface $currentUser;

    /**
     * The subscription service.
     */
    protected SubscriptionService $subscriptionService;

    /**
     * Constructs a new KitService.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        AccountProxyInterface $current_user,
        SubscriptionService $subscription_service
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->currentUser = $current_user;
        $this->subscriptionService = $subscription_service;
    }

    /**
     * Gets all available kits for a category.
     *
     * @param string|null $category
     *   Optional category filter.
     * @param string|null $access_level
     *   Optional access level filter.
     *
     * @return array
     *   Array of DigitalKit entities.
     */
    public function getKits(?string $category = NULL, ?string $access_level = NULL): array
    {
        $storage = $this->entityTypeManager->getStorage('digital_kit');
        $query = $storage->getQuery()
            ->condition('status', 'published')
            ->accessCheck(TRUE)
            ->sort('is_featured', 'DESC')
            ->sort('created', 'DESC');

        if ($category) {
            $query->condition('category', $category);
        }

        if ($access_level) {
            $query->condition('access_level', $access_level);
        }

        $ids = $query->execute();
        return $ids ? $storage->loadMultiple($ids) : [];
    }

    /**
     * Gets featured kits.
     *
     * @param int $limit
     *   Maximum number of kits to return.
     *
     * @return array
     *   Array of featured DigitalKit entities.
     */
    public function getFeaturedKits(int $limit = 6): array
    {
        $storage = $this->entityTypeManager->getStorage('digital_kit');
        $ids = $storage->getQuery()
            ->condition('status', 'published')
            ->condition('is_featured', TRUE)
            ->accessCheck(TRUE)
            ->sort('created', 'DESC')
            ->range(0, $limit)
            ->execute();

        return $ids ? $storage->loadMultiple($ids) : [];
    }

    /**
     * Gets new kits (created in the last 30 days).
     *
     * @param int $limit
     *   Maximum number of kits to return.
     *
     * @return array
     *   Array of new DigitalKit entities.
     */
    public function getNewKits(int $limit = 6): array
    {
        $storage = $this->entityTypeManager->getStorage('digital_kit');
        $thirty_days_ago = strtotime('-30 days');

        $ids = $storage->getQuery()
            ->condition('status', 'published')
            ->condition('created', $thirty_days_ago, '>=')
            ->accessCheck(TRUE)
            ->sort('created', 'DESC')
            ->range(0, $limit)
            ->execute();

        return $ids ? $storage->loadMultiple($ids) : [];
    }

    /**
     * Gets related kits by category.
     *
     * @param int $kit_id
     *   The current kit ID.
     * @param string $category
     *   The category to search in.
     * @param int $limit
     *   Maximum number of related kits.
     *
     * @return array
     *   Array of related DigitalKit entities.
     */
    public function getRelatedKits(int $kit_id, string $category, int $limit = 3): array
    {
        $storage = $this->entityTypeManager->getStorage('digital_kit');
        $ids = $storage->getQuery()
            ->condition('status', 'published')
            ->condition('category', $category)
            ->condition('id', $kit_id, '<>')
            ->accessCheck(TRUE)
            ->sort('rating', 'DESC')
            ->range(0, $limit)
            ->execute();

        return $ids ? $storage->loadMultiple($ids) : [];
    }

    /**
     * Checks if the current user can access a kit.
     *
     * @param \Drupal\jaraba_resources\Entity\DigitalKit $kit
     *   The kit to check access for.
     *
     * @return bool
     *   TRUE if user can access the kit.
     */
    public function userCanAccess($kit): bool
    {
        $required_level = $kit->getAccessLevel();

        // Free kits are accessible to all authenticated users.
        if ($required_level === 'free' && $this->currentUser->isAuthenticated()) {
            return TRUE;
        }

        // Check user's subscription level.
        return $this->subscriptionService->hasKitAccess(
            (int) $this->currentUser->id(),
            $required_level
        );
    }

    /**
     * Records a download and increments the counter.
     *
     * @param int $kit_id
     *   The kit ID.
     *
     * @return bool
     *   TRUE if download was recorded successfully.
     */
    public function recordDownload(int $kit_id): bool
    {
        $storage = $this->entityTypeManager->getStorage('digital_kit');
        $kit = $storage->load($kit_id);

        if (!$kit) {
            return FALSE;
        }

        $kit->incrementDownloadCount();
        $kit->save();

        return TRUE;
    }

    /**
     * Adds a user rating for a kit.
     *
     * @param int $kit_id
     *   The kit ID.
     * @param int $rating
     *   The rating (1-5).
     *
     * @return bool
     *   TRUE if rating was recorded successfully.
     */
    public function rateKit(int $kit_id, int $rating): bool
    {
        if ($rating < 1 || $rating > 5) {
            return FALSE;
        }

        $storage = $this->entityTypeManager->getStorage('digital_kit');
        $kit = $storage->load($kit_id);

        if (!$kit) {
            return FALSE;
        }

        // Calculate new average rating.
        $current_rating = (float) $kit->get('rating')->value;
        $current_count = (int) $kit->get('rating_count')->value;

        $new_count = $current_count + 1;
        $new_rating = (($current_rating * $current_count) + $rating) / $new_count;

        $kit->set('rating', round($new_rating, 2));
        $kit->set('rating_count', $new_count);
        $kit->save();

        return TRUE;
    }

    /**
     * Gets all available categories with counts.
     *
     * @return array
     *   Array of categories with their counts.
     */
    public function getCategories(): array
    {
        $storage = $this->entityTypeManager->getStorage('digital_kit');
        $kits = $storage->getQuery()
            ->condition('status', 'published')
            ->accessCheck(TRUE)
            ->execute();

        $categories = [];
        foreach ($storage->loadMultiple($kits) as $kit) {
            $category = $kit->getCategory();
            if (!isset($categories[$category])) {
                $categories[$category] = 0;
            }
            $categories[$category]++;
        }

        return $categories;
    }

    /**
     * Searches kits by keyword.
     *
     * @param string $keyword
     *   The search keyword.
     *
     * @return array
     *   Array of matching DigitalKit entities.
     */
    public function searchKits(string $keyword): array
    {
        $storage = $this->entityTypeManager->getStorage('digital_kit');

        $query = $storage->getQuery()
            ->condition('status', 'published')
            ->accessCheck(TRUE);

        // Search in name and description.
        $group = $query->orConditionGroup()
            ->condition('name', '%' . $keyword . '%', 'LIKE')
            ->condition('description', '%' . $keyword . '%', 'LIKE');

        $query->condition($group);
        $query->sort('rating', 'DESC');

        $ids = $query->execute();
        return $ids ? $storage->loadMultiple($ids) : [];
    }

}
