<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for the ProactiveInsight entity.
 *
 * GAP-AUD-010: Proactive Intelligence — AI-generated insights
 * delivered to tenant admins via bell notification.
 */
interface ProactiveInsightInterface extends ContentEntityInterface, EntityChangedInterface
{

    /**
     * Gets the insight type.
     *
     * @return string
     *   One of: optimization, alert, opportunity.
     */
    public function getInsightType(): string;

    /**
     * Gets the insight title.
     */
    public function getTitle(): string;

    /**
     * Gets the severity level.
     *
     * @return string
     *   One of: high, medium, low.
     */
    public function getSeverity(): string;

    /**
     * Gets the target user ID.
     */
    public function getTargetUserId(): int;

    /**
     * Gets the tenant ID.
     */
    public function getTenantId(): int;

    /**
     * Checks if the insight has been read.
     */
    public function isRead(): bool;

    /**
     * Gets the action URL.
     */
    public function getActionUrl(): string;

    /**
     * Marks the insight as read.
     */
    public function markAsRead(): static;

}
