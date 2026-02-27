<?php

declare(strict_types=1);

namespace Drupal\jaraba_workflows\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface for WorkflowRule config entities.
 */
interface WorkflowRuleInterface extends ConfigEntityInterface
{

    /**
     * Gets the trigger type.
     */
    public function getTriggerType(): string;

    /**
     * Gets the trigger configuration.
     */
    public function getTriggerConfig(): array;

    /**
     * Gets the conditions.
     */
    public function getConditions(): array;

    /**
     * Gets the actions.
     */
    public function getActions(): array;

    /**
     * Gets the tenant ID.
     */
    public function getTenantId(): int;

    /**
     * Gets the weight.
     */
    public function getWeight(): int;

    /**
     * Gets the description.
     */
    public function getDescription(): string;

}
