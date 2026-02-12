<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_agroconecta_core\Entity\CouponAgro;
use Drupal\jaraba_agroconecta_core\Entity\PromotionAgro;

/**
 * Servicio para gestión de promociones y cupones.
 *
 * RESPONSABILIDADES:
 * - Evaluar qué promociones aplican a un carrito/producto.
 * - Calcular descuentos respetando topes, mínimos y prioridades.
 * - Validar y canjear cupones.
 * - Serializar datos para la API REST.
 * - Gestionar usos por usuario.
 */
class PromotionService
{

    /**
     * Constructor del servicio.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected Connection $database,
        protected AccountProxyInterface $currentUser,
    ) {
    }

    // ===================================================
    // Consultas de promociones
    // ===================================================

    /**
     * Obtiene promociones activas y vigentes para un tenant.
     *
     * @param int $tenantId
     *   ID del tenant.
     *
     * @return array
     *   Array de promociones serializadas.
     */
    public function getActivePromotions(int $tenantId): array
    {
        $storage = $this->entityTypeManager->getStorage('promotion_agro');

        $query = $storage->getQuery()
            ->condition('tenant_id', $tenantId)
            ->condition('is_active', TRUE)
            ->sort('priority', 'DESC')
            ->sort('created', 'DESC')
            ->accessCheck(FALSE);

        $ids = $query->execute();
        if (empty($ids)) {
            return [];
        }

        $promotions = $storage->loadMultiple($ids);
        $results = [];

        foreach ($promotions as $promo) {
            /** @var \Drupal\jaraba_agroconecta_core\Entity\PromotionAgro $promo */
            if ($promo->isWithinDateRange() && $promo->hasUsesRemaining()) {
                $results[] = $this->serializePromotion($promo);
            }
        }

        return $results;
    }

    /**
     * Obtiene una promoción por ID.
     *
     * @param int $promotionId
     *   ID de la promoción.
     *
     * @return array|null
     *   Datos serializados o NULL.
     */
    public function getPromotion(int $promotionId): ?array
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\PromotionAgro|null $promo */
        $promo = $this->entityTypeManager->getStorage('promotion_agro')->load($promotionId);
        if (!$promo) {
            return NULL;
        }
        return $this->serializePromotion($promo);
    }

    // ===================================================
    // Evaluación de descuentos
    // ===================================================

    /**
     * Evalúa qué promociones aplican a un carrito.
     *
     * @param int $tenantId
     *   ID del tenant.
     * @param array $cartItems
     *   Array de items: [['product_id' => int, 'category_id' => int, 'price' => float, 'quantity' => int], ...]
     * @param float $subtotal
     *   Subtotal del carrito.
     *
     * @return array
     *   Array de promociones aplicables con descuentos calculados.
     */
    public function evaluateCart(int $tenantId, array $cartItems, float $subtotal): array
    {
        $activePromotions = $this->loadActivePromotionEntities($tenantId);
        $applicable = [];

        foreach ($activePromotions as $promo) {
            $discount = $this->calculateDiscount($promo, $cartItems, $subtotal);
            if ($discount !== NULL) {
                $applicable[] = [
                    'promotion' => $this->serializePromotion($promo),
                    'discount' => $discount,
                ];
            }
        }

        // Resolver conflictos: si hay no-acumulables, solo la de mayor prioridad.
        return $this->resolveConflicts($applicable);
    }

    /**
     * Calcula el descuento de una promoción para un carrito.
     *
     * @return array|null
     *   ['amount' => float, 'description' => string] o NULL si no aplica.
     */
    protected function calculateDiscount(PromotionAgro $promo, array $cartItems, float $subtotal): ?array
    {
        // Verificar pedido mínimo.
        if ($promo->getMinimumOrder() > 0 && $subtotal < $promo->getMinimumOrder()) {
            return NULL;
        }

        // Verificar productos/categorías objetivo.
        $targetProducts = $promo->getTargetProducts();
        $targetCategories = $promo->getTargetCategories();
        $eligibleSubtotal = $subtotal;

        if (!empty($targetProducts) || !empty($targetCategories)) {
            $eligibleSubtotal = 0;
            foreach ($cartItems as $item) {
                $matches = FALSE;
                if (!empty($targetProducts) && in_array($item['product_id'] ?? 0, $targetProducts)) {
                    $matches = TRUE;
                }
                if (!empty($targetCategories) && in_array($item['category_id'] ?? 0, $targetCategories)) {
                    $matches = TRUE;
                }
                if ($matches) {
                    $eligibleSubtotal += ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
                }
            }
            if ($eligibleSubtotal <= 0) {
                return NULL;
            }
        }

        // Calcular según tipo.
        $type = $promo->getDiscountType();
        $value = $promo->getDiscountValue();
        $amount = 0.0;
        $description = '';

        switch ($type) {
            case PromotionAgro::TYPE_PERCENTAGE:
                $amount = $eligibleSubtotal * ($value / 100);
                $description = number_format($value, 0) . '% de descuento';
                break;

            case PromotionAgro::TYPE_FIXED:
                $amount = min($value, $eligibleSubtotal);
                $description = number_format($value, 2, ',', '.') . '€ de descuento';
                break;

            case PromotionAgro::TYPE_FREE_SHIPPING:
                $amount = 0; // Se gestiona a nivel de envío.
                $description = 'Envío gratis';
                break;

            case PromotionAgro::TYPE_BUY_X_GET_Y:
                $config = $promo->getBxgyConfig();
                $buyQty = (int) ($config['buy_quantity'] ?? 0);
                $getQty = (int) ($config['get_quantity'] ?? 0);
                if ($buyQty > 0) {
                    foreach ($cartItems as $item) {
                        $qty = (int) ($item['quantity'] ?? 0);
                        if ($qty >= $buyQty) {
                            $freeItems = intdiv($qty, $buyQty) * $getQty;
                            $amount += $freeItems * ($item['price'] ?? 0);
                        }
                    }
                    $description = "Compra $buyQty lleva $getQty gratis";
                }
                break;
        }

        // Aplicar tope máximo.
        $maxDiscount = $promo->getMaxDiscount();
        if ($maxDiscount > 0 && $amount > $maxDiscount) {
            $amount = $maxDiscount;
        }

        if ($amount <= 0 && $type !== PromotionAgro::TYPE_FREE_SHIPPING) {
            return NULL;
        }

        return [
            'amount' => round($amount, 2),
            'description' => $description,
            'type' => $type,
            'free_shipping' => ($type === PromotionAgro::TYPE_FREE_SHIPPING),
        ];
    }

    /**
     * Resuelve conflictos entre promociones acumulables y no acumulables.
     */
    protected function resolveConflicts(array $applicable): array
    {
        if (count($applicable) <= 1) {
            return $applicable;
        }

        $stackable = [];
        $nonStackable = [];

        foreach ($applicable as $item) {
            if ($item['promotion']['stackable'] ?? FALSE) {
                $stackable[] = $item;
            } else {
                $nonStackable[] = $item;
            }
        }

        // Si hay no-acumulables, usar la de mayor prioridad (ya ordenadas).
        $best = [];
        if (!empty($nonStackable)) {
            $best[] = $nonStackable[0]; // Mayor prioridad.
        }

        // Las acumulables siempre se aplican.
        return array_merge($best, $stackable);
    }

    // ===================================================
    // Cupones
    // ===================================================

    /**
     * Valida un código de cupón.
     *
     * @param string $code
     *   Código del cupón.
     * @param int $tenantId
     *   ID del tenant.
     * @param float $subtotal
     *   Subtotal del carrito.
     *
     * @return array
     *   ['valid' => bool, 'message' => string, 'coupon' => array|null, 'promotion' => array|null].
     */
    public function validateCoupon(string $code, int $tenantId, float $subtotal = 0): array
    {
        $code = strtoupper(trim($code));

        // Buscar cupón por código.
        $coupon = $this->findCouponByCode($code, $tenantId);
        if (!$coupon) {
            return ['valid' => FALSE, 'message' => 'Código de cupón no válido.', 'coupon' => NULL, 'promotion' => NULL];
        }

        // Verificar activo.
        if (!$coupon->isActive()) {
            return ['valid' => FALSE, 'message' => 'Este cupón está desactivado.', 'coupon' => NULL, 'promotion' => NULL];
        }

        // Verificar fechas.
        if (!$coupon->isWithinDateRange()) {
            return ['valid' => FALSE, 'message' => 'Este cupón ha expirado o aún no es válido.', 'coupon' => NULL, 'promotion' => NULL];
        }

        // Verificar usos.
        if (!$coupon->hasUsesRemaining()) {
            return ['valid' => FALSE, 'message' => 'Este cupón ha alcanzado su límite de usos.', 'coupon' => NULL, 'promotion' => NULL];
        }

        // Verificar usos por usuario.
        $userId = (int) $this->currentUser->id();
        $maxPerUser = (int) $coupon->get('max_uses_per_user')->value;
        if ($maxPerUser > 0 && $userId > 0) {
            $userUses = $this->getUserCouponUses($coupon, $userId);
            if ($userUses >= $maxPerUser) {
                return ['valid' => FALSE, 'message' => 'Ya has usado este cupón el número máximo de veces.', 'coupon' => NULL, 'promotion' => NULL];
            }
        }

        // Verificar promoción asociada.
        $promo = $coupon->getPromotion();
        if (!$promo || !$promo->isActive() || !$promo->isWithinDateRange() || !$promo->hasUsesRemaining()) {
            return ['valid' => FALSE, 'message' => 'La promoción asociada a este cupón ya no está disponible.', 'coupon' => NULL, 'promotion' => NULL];
        }

        // Verificar pedido mínimo.
        $minOrder = $coupon->getEffectiveMinimumOrder();
        if ($minOrder > 0 && $subtotal < $minOrder) {
            return [
                'valid' => FALSE,
                'message' => 'Pedido mínimo de ' . number_format($minOrder, 2, ',', '.') . '€ requerido.',
                'coupon' => NULL,
                'promotion' => NULL,
            ];
        }

        return [
            'valid' => TRUE,
            'message' => '¡Cupón aplicado!',
            'coupon' => $this->serializeCoupon($coupon),
            'promotion' => $this->serializePromotion($promo),
        ];
    }

    /**
     * Canjea un cupón (incrementa contadores).
     *
     * @param string $code
     *   Código del cupón.
     * @param int $tenantId
     *   ID del tenant.
     *
     * @return bool
     *   TRUE si se canjeó correctamente.
     */
    public function redeemCoupon(string $code, int $tenantId): bool
    {
        $code = strtoupper(trim($code));
        $coupon = $this->findCouponByCode($code, $tenantId);
        if (!$coupon) {
            return FALSE;
        }

        $promo = $coupon->getPromotion();
        if (!$promo) {
            return FALSE;
        }

        // Incrementar usos del cupón y la promoción.
        $coupon->incrementUses();
        $coupon->save();

        $promo->incrementUses();
        $promo->save();

        // Registrar uso por usuario.
        $userId = (int) $this->currentUser->id();
        if ($userId > 0) {
            $this->recordCouponUse($coupon, $userId);
        }

        return TRUE;
    }

    /**
     * Busca un cupón por código y tenant.
     */
    protected function findCouponByCode(string $code, int $tenantId): ?CouponAgro
    {
        $storage = $this->entityTypeManager->getStorage('coupon_agro');
        $ids = $storage->getQuery()
            ->condition('code', $code)
            ->condition('tenant_id', $tenantId)
            ->range(0, 1)
            ->accessCheck(FALSE)
            ->execute();

        if (empty($ids)) {
            return NULL;
        }

        /** @var \Drupal\jaraba_agroconecta_core\Entity\CouponAgro|null $coupon */
        $coupon = $storage->load(reset($ids));
        return $coupon;
    }

    /**
     * Obtiene los usos de un cupón por un usuario.
     */
    protected function getUserCouponUses(CouponAgro $coupon, int $userId): int
    {
        try {
            return (int) $this->database->select('coupon_agro_usage', 'u')
                ->condition('u.coupon_id', $coupon->id())
                ->condition('u.uid', $userId)
                ->countQuery()
                ->execute()
                ->fetchField();
        } catch (\Exception) {
            return 0;
        }
    }

    /**
     * Registra un uso del cupón por un usuario.
     */
    protected function recordCouponUse(CouponAgro $coupon, int $userId): void
    {
        try {
            $this->database->insert('coupon_agro_usage')
                ->fields([
                    'coupon_id' => $coupon->id(),
                    'uid' => $userId,
                    'used_at' => \Drupal::time()->getRequestTime(),
                ])
                ->execute();
        } catch (\Exception) {
            // La tabla puede no existir todavía.
        }
    }

    // ===================================================
    // Helpers de carga
    // ===================================================

    /**
     * Carga entidades PromotionAgro activas para un tenant.
     *
     * @return PromotionAgro[]
     */
    protected function loadActivePromotionEntities(int $tenantId): array
    {
        $storage = $this->entityTypeManager->getStorage('promotion_agro');
        $ids = $storage->getQuery()
            ->condition('tenant_id', $tenantId)
            ->condition('is_active', TRUE)
            ->sort('priority', 'DESC')
            ->accessCheck(FALSE)
            ->execute();

        if (empty($ids)) {
            return [];
        }

        $entities = $storage->loadMultiple($ids);
        $result = [];
        foreach ($entities as $entity) {
            /** @var \Drupal\jaraba_agroconecta_core\Entity\PromotionAgro $entity */
            if ($entity->isWithinDateRange() && $entity->hasUsesRemaining()) {
                $result[] = $entity;
            }
        }

        return $result;
    }

    /**
     * Obtiene cupones activos para un tenant.
     */
    public function getActiveCoupons(int $tenantId): array
    {
        $storage = $this->entityTypeManager->getStorage('coupon_agro');
        $ids = $storage->getQuery()
            ->condition('tenant_id', $tenantId)
            ->condition('is_active', TRUE)
            ->sort('created', 'DESC')
            ->accessCheck(FALSE)
            ->execute();

        if (empty($ids)) {
            return [];
        }

        $coupons = $storage->loadMultiple($ids);
        $results = [];
        foreach ($coupons as $coupon) {
            /** @var \Drupal\jaraba_agroconecta_core\Entity\CouponAgro $coupon */
            if ($coupon->isWithinDateRange() && $coupon->hasUsesRemaining()) {
                $results[] = $this->serializeCoupon($coupon);
            }
        }

        return $results;
    }

    // ===================================================
    // Serialización
    // ===================================================

    /**
     * Serializa una promoción para la API.
     */
    protected function serializePromotion(PromotionAgro $promo): array
    {
        return [
            'id' => (int) $promo->id(),
            'name' => $promo->label(),
            'description' => $promo->get('description')->value ?? '',
            'discount_type' => $promo->getDiscountType(),
            'discount_type_label' => $promo->getDiscountTypeLabel(),
            'discount_value' => $promo->getDiscountValue(),
            'formatted_discount' => $promo->getFormattedDiscount(),
            'minimum_order' => $promo->getMinimumOrder(),
            'max_discount' => $promo->getMaxDiscount(),
            'start_date' => $promo->get('start_date')->value,
            'end_date' => $promo->get('end_date')->value,
            'stackable' => $promo->isStackable(),
            'priority' => $promo->getPriority(),
            'target_categories' => $promo->getTargetCategories(),
            'target_products' => $promo->getTargetProducts(),
        ];
    }

    /**
     * Serializa un cupón para la API.
     */
    protected function serializeCoupon(CouponAgro $coupon): array
    {
        $promo = $coupon->getPromotion();

        return [
            'id' => (int) $coupon->id(),
            'code' => $coupon->getCode(),
            'promotion_id' => $promo ? (int) $promo->id() : NULL,
            'promotion_name' => $promo ? $promo->label() : NULL,
            'formatted_discount' => $promo ? $promo->getFormattedDiscount() : '',
            'max_uses' => (int) $coupon->get('max_uses')->value,
            'current_uses' => (int) $coupon->get('current_uses')->value,
            'start_date' => $coupon->get('start_date')->value,
            'end_date' => $coupon->get('end_date')->value,
            'is_active' => $coupon->isActive(),
        ];
    }

}
