<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for EmployerProfile entities.
 */
interface EmployerProfileInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface
{

    /**
     * Gets the company name.
     */
    public function getCompanyName(): string;

    /**
     * Gets the tenant ID.
     */
    public function getTenantId(): ?int;

    /**
     * Checks if the employer is verified.
     */
    public function isVerified(): bool;

    /**
     * Checks if the employer is featured.
     */
    public function isFeatured(): bool;

}
