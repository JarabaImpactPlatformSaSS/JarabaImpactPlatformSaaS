<?php

declare(strict_types=1);

namespace Drupal\jaraba_training\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Interface para la entidad TrainingProduct.
 */
interface TrainingProductInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface
{

    /**
     * Obtiene el título del producto.
     */
    public function getTitle(): string;

    /**
     * Obtiene el tipo de producto.
     */
    public function getProductType(): string;

    /**
     * Obtiene el nivel en la escalera (0-5).
     */
    public function getLadderLevel(): int;

    /**
     * Obtiene el precio base.
     */
    public function getPrice(): float;

    /**
     * Obtiene el tipo de facturación.
     */
    public function getBillingType(): string;

    /**
     * Obtiene el siguiente producto en la escalera.
     */
    public function getNextProduct(): ?TrainingProductInterface;

    /**
     * Verifica si es gratuito.
     */
    public function isFree(): bool;

}
