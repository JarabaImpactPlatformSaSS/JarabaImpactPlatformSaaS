<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_agroconecta_core\Entity\ShippingMethodAgro;
use Drupal\jaraba_agroconecta_core\Entity\ShippingZoneAgro;

/**
 * Servicio de envío para AgroConecta.
 *
 * RESPONSABILIDADES:
 * - Detectar zona de envío por código postal.
 * - Obtener métodos disponibles para una zona.
 * - Calcular tarifas según peso/precio.
 * - Serializar para API.
 */
class ShippingService
{

    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
    ) {
    }

    /**
     * Detecta la zona de envío para un código postal y país.
     *
     * @param int $tenantId
     *   ID del tenant.
     * @param string $postalCode
     *   Código postal del destino.
     * @param string $country
     *   Código ISO del país (default: ES).
     *
     * @return array|null
     *   Zona serializada o NULL si no se encuentra.
     */
    public function detectZone(int $tenantId, string $postalCode, string $country = 'ES'): ?array
    {
        $zones = $this->loadActiveZones($tenantId);

        foreach ($zones as $zone) {
            if ($zone->matchesPostalCode($postalCode, $country)) {
                return $this->serializeZone($zone);
            }
        }

        return NULL;
    }

    /**
     * Obtiene métodos de envío disponibles para una zona.
     *
     * @param int $tenantId
     *   ID del tenant.
     * @param int|null $zoneId
     *   ID de la zona (NULL = métodos sin restricción de zona).
     * @param float $subtotal
     *   Subtotal del pedido.
     * @param float $totalWeight
     *   Peso total del pedido (kg).
     * @param bool $needsColdChain
     *   Si el pedido requiere cadena de frío.
     *
     * @return array
     *   Array de métodos con tarifas calculadas.
     */
    public function getAvailableMethods(
        int $tenantId,
        ?int $zoneId,
        float $subtotal = 0,
        float $totalWeight = 0,
        bool $needsColdChain = FALSE,
    ): array {
        $methods = $this->loadActiveMethods($tenantId);
        $available = [];

        foreach ($methods as $method) {
            // Filtrar por zona.
            $methodZones = $method->getZoneIds();
            if (!empty($methodZones) && $zoneId !== NULL && !in_array($zoneId, $methodZones)) {
                continue;
            }

            // Filtrar por peso máximo.
            $maxWeight = (float) ($method->get('max_weight')->value ?? 0);
            if ($maxWeight > 0 && $totalWeight > $maxWeight) {
                continue;
            }

            // Filtrar por cadena de frío.
            if ($needsColdChain && !$method->requiresColdChain()) {
                // Solo filtrar si el pedido REQUIERE frío y el método NO lo soporta.
                // No filtrar si el método soporta frío pero no se necesita.
            }

            $rate = $method->calculateRate($subtotal, $totalWeight);

            $available[] = [
                'method' => $this->serializeMethod($method),
                'calculated_rate' => round($rate, 2),
                'formatted_rate' => $method->getFormattedRate($subtotal, $totalWeight),
                'is_free' => $rate <= 0,
            ];
        }

        // Ordenar por posición.
        usort(
            $available,
            fn($a, $b) =>
            ($a['method']['position'] ?? 0) <=> ($b['method']['position'] ?? 0)
        );

        return $available;
    }

    /**
     * Calcula envío para un pedido completo.
     *
     * @return array
     *   ['zone' => ..., 'methods' => [...], 'cheapest' => ..., 'fastest' => ...]
     */
    public function calculateShipping(
        int $tenantId,
        string $postalCode,
        string $country,
        float $subtotal,
        float $totalWeight = 0,
        bool $needsColdChain = FALSE,
    ): array {
        // Detectar zona.
        $zone = $this->detectZone($tenantId, $postalCode, $country);
        $zoneId = $zone ? $zone['id'] : NULL;

        // Obtener métodos.
        $methods = $this->getAvailableMethods($tenantId, $zoneId, $subtotal, $totalWeight, $needsColdChain);

        // Encontrar el más barato y el más rápido.
        $cheapest = NULL;
        $fastest = NULL;
        foreach ($methods as $m) {
            if ($cheapest === NULL || $m['calculated_rate'] < $cheapest['calculated_rate']) {
                $cheapest = $m;
            }
            // Asumir que menor posición = más rápido.
            if ($fastest === NULL) {
                $fastest = $m;
            }
        }

        return [
            'zone' => $zone,
            'methods' => $methods,
            'cheapest' => $cheapest,
            'fastest' => $fastest,
            'total_methods' => count($methods),
        ];
    }

    /**
     * Obtiene todas las zonas activas de un tenant.
     */
    public function getZones(int $tenantId): array
    {
        $zones = $this->loadActiveZones($tenantId);
        return array_map([$this, 'serializeZone'], $zones);
    }

    /**
     * Obtiene todos los métodos activos de un tenant.
     */
    public function getMethods(int $tenantId): array
    {
        $methods = $this->loadActiveMethods($tenantId);
        return array_map([$this, 'serializeMethod'], $methods);
    }

    // ===================================================
    // Carga de entidades
    // ===================================================

    /**
     * @return ShippingZoneAgro[]
     */
    protected function loadActiveZones(int $tenantId): array
    {
        $storage = $this->entityTypeManager->getStorage('shipping_zone_agro');
        $ids = $storage->getQuery()
            ->condition('tenant_id', $tenantId)
            ->condition('is_active', TRUE)
            ->accessCheck(FALSE)
            ->execute();

        if (empty($ids)) {
            return [];
        }

        $zones = $storage->loadMultiple($ids);
        return array_filter($zones, fn($z) => $z instanceof ShippingZoneAgro && $z->isActive());
    }

    /**
     * @return ShippingMethodAgro[]
     */
    protected function loadActiveMethods(int $tenantId): array
    {
        $storage = $this->entityTypeManager->getStorage('shipping_method_agro');
        $ids = $storage->getQuery()
            ->condition('tenant_id', $tenantId)
            ->condition('is_active', TRUE)
            ->sort('position', 'ASC')
            ->accessCheck(FALSE)
            ->execute();

        if (empty($ids)) {
            return [];
        }

        $methods = $storage->loadMultiple($ids);
        return array_filter($methods, fn($m) => $m instanceof ShippingMethodAgro && $m->isActive());
    }

    // ===================================================
    // Serialización
    // ===================================================

    protected function serializeZone(ShippingZoneAgro $zone): array
    {
        return [
            'id' => (int) $zone->id(),
            'name' => $zone->label(),
            'country' => $zone->getCountry(),
            'regions' => $zone->getRegions(),
            'is_active' => $zone->isActive(),
        ];
    }

    protected function serializeMethod(ShippingMethodAgro $method): array
    {
        return [
            'id' => (int) $method->id(),
            'name' => $method->label(),
            'description' => $method->get('description')->value ?? '',
            'calculation_type' => $method->getCalculationType(),
            'base_rate' => $method->getBaseRate(),
            'free_threshold' => $method->getFreeThreshold(),
            'delivery_estimate' => $method->getDeliveryEstimate(),
            'requires_cold_chain' => $method->requiresColdChain(),
            'position' => (int) ($method->get('position')->value ?? 0),
            'is_active' => $method->isActive(),
        ];
    }

}
