<?php

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface para la entidad Plan de Suscripción SaaS.
 *
 * Define los límites y características disponibles para un Tenant.
 */
interface SaasPlanInterface extends ContentEntityInterface
{

    /**
     * Obtiene el nombre del plan.
     *
     * @return string
     *   El nombre del plan (Básico, Profesional, Enterprise).
     */
    public function getName(): string;

    /**
     * Obtiene la vertical asociada.
     *
     * @return \Drupal\ecosistema_jaraba_core\Entity\VerticalInterface|null
     *   La vertical o NULL si aplica a todas.
     */
    public function getVertical(): ?VerticalInterface;

    /**
     * Obtiene el precio mensual.
     *
     * @return float
     *   El precio mensual en EUR.
     */
    public function getPriceMonthly(): float;

    /**
     * Obtiene el precio anual.
     *
     * @return float
     *   El precio anual en EUR (con descuento).
     */
    public function getPriceYearly(): float;

    /**
     * Obtiene los límites del plan.
     *
     * @return array
     *   Array con límites: productores, storage_gb, ai_queries, etc.
     */
    public function getLimits(): array;

    /**
     * Obtiene un límite específico.
     *
     * @param string $key
     *   Clave del límite (ej: 'productores').
     * @param mixed $default
     *   Valor por defecto si no existe.
     *
     * @return mixed
     *   El valor del límite o el default.
     */
    public function getLimit(string $key, $default = 0);

    /**
     * Obtiene las features incluidas.
     *
     * @return array
     *   Lista de features incluidas en el plan.
     */
    public function getFeatures(): array;

    /**
     * Verifica si el plan incluye una feature.
     *
     * @param string $feature
     *   El identificador de la feature.
     *
     * @return bool
     *   TRUE si la feature está incluida.
     */
    public function hasFeature(string $feature): bool;

    /**
     * Obtiene el ID del precio en Stripe.
     *
     * @return string|null
     *   El ID del precio en Stripe o NULL.
     */
    public function getStripePriceId(): ?string;

    /**
     * Verifica si el plan es gratuito.
     *
     * @return bool
     *   TRUE si el precio mensual es 0.
     */
    public function isFree(): bool;

}
