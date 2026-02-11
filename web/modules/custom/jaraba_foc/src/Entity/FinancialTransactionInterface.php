<?php

declare(strict_types=1);

namespace Drupal\jaraba_foc\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Interfaz para la entidad Transacción Financiera.
 *
 * PROPÓSITO:
 * Define el contrato para la entidad inmutable de transacciones financieras.
 * Esta entidad actúa como libro mayor contable digital (append-only).
 *
 * INMUTABILIDAD:
 * Las transacciones financieras NO pueden ser editadas ni eliminadas.
 * Los ajustes se realizan mediante asientos compensatorios.
 *
 * @see \Drupal\jaraba_foc\Entity\FinancialTransaction
 */
interface FinancialTransactionInterface extends ContentEntityInterface, EntityChangedInterface
{

    /**
     * Obtiene el monto de la transacción.
     *
     * NOTA: El monto se almacena como Decimal(10,4) para máxima precisión.
     * NUNCA usar float para valores monetarios.
     *
     * @return string
     *   El monto con precisión decimal (ej: "1234.5678").
     */
    public function getAmount(): string;

    /**
     * Obtiene el código de moneda ISO 4217.
     *
     * @return string
     *   Código ISO 4217 (ej: "EUR", "USD").
     */
    public function getCurrency(): string;

    /**
     * Obtiene el timestamp de la transacción en UTC.
     *
     * NOTA: Siempre almacenamos en UTC para evitar conflictos de timezone.
     *
     * @return int
     *   Timestamp Unix de la transacción.
     */
    public function getTransactionTimestamp(): int;

    /**
     * Obtiene el tipo de transacción.
     *
     * @return string|null
     *   ID del término de taxonomía del tipo de transacción.
     */
    public function getTransactionType(): ?string;

    /**
     * Obtiene el sistema de origen de la transacción.
     *
     * @return string
     *   Identificador del sistema origen (ej: "stripe_connect", "manual").
     */
    public function getSourceSystem(): string;

    /**
     * Obtiene el ID externo de la transacción.
     *
     * NOTA: Este ID previene duplicados y permite auditorías cruzadas.
     *
     * @return string|null
     *   ID en el sistema origen (ej: ID de PaymentIntent de Stripe).
     */
    public function getExternalId(): ?string;

    /**
     * Obtiene el ID del tenant relacionado.
     *
     * @return int|null
     *   ID del tenant (Group) relacionado.
     */
    public function getRelatedTenantId(): ?int;

    /**
     * Obtiene el ID del vertical relacionado.
     *
     * @return int|null
     *   ID del vertical relacionado.
     */
    public function getRelatedVerticalId(): ?int;

    /**
     * Indica si la transacción es un ingreso (positivo).
     *
     * @return bool
     *   TRUE si es ingreso, FALSE si es gasto.
     */
    public function isRevenue(): bool;

    /**
     * Indica si la transacción es recurrente (MRR).
     *
     * @return bool
     *   TRUE si es ingreso recurrente.
     */
    public function isRecurring(): bool;

}
