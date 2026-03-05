<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface;
use Drupal\jaraba_foc\Service\StripeConnectService;
use Psr\Log\LoggerInterface;

/**
 * Sincroniza SaasPlan entities con Stripe Products y Prices.
 *
 * Principio: Drupal es Source of Truth. Stripe refleja lo que Drupal define.
 *
 * Patron de sincronizacion:
 * - Plan nuevo (sin stripe_product_id) → crea Product + 2 Prices (monthly/yearly)
 * - Plan editado (con stripe_product_id) → actualiza Product, archiva/crea Prices si precio cambio
 * - Plan desactivado → archiva Product (suscripciones existentes continuan)
 *
 * Directrices aplicadas:
 * - OPTIONAL-CROSSMODULE-001: StripeConnectService inyectado como @?
 * - LOGGER-INJECT-001: LoggerInterface directo via @logger.channel.jaraba_billing
 * - AUDIT-PERF-002: LockBackendInterface para race conditions
 * - STRIPE-ENV-UNIFY-001: Keys via StripeConnectService (settings.secrets.php)
 *
 * STRIPE-CHECKOUT-001 §4.1 / §8.1
 */
class StripeProductSyncService {

  public function __construct(
    protected ?StripeConnectService $stripeConnect,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected LockBackendInterface $lock,
  ) {}

  /**
   * Sincroniza un SaasPlan con Stripe (crea o actualiza Product + Prices).
   *
   * Se invoca automaticamente desde hook_entity_presave() de SaasPlan.
   * Usa locks para prevenir race conditions y idempotency keys para reintentos.
   *
   * @param \Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface $plan
   *   El plan SaaS a sincronizar.
   *
   * @return array{product_id: string, price_monthly_id: string, price_yearly_id: string}
   *   Los IDs de Stripe generados/actualizados.
   *
   * @throws \RuntimeException
   *   Si no se puede adquirir el lock o la comunicacion con Stripe falla.
   */
  public function syncPlan(SaasPlanInterface $plan): array {
    if (!$this->stripeConnect) {
      $this->logger->warning('StripeConnectService not available — skipping plan sync.');
      return ['product_id' => '', 'price_monthly_id' => '', 'price_yearly_id' => ''];
    }

    // Solo sincronizar planes activos con precio > 0.
    if ($plan->isFree()) {
      $this->logger->info('Plan @name is free — skipping Stripe sync.', [
        '@name' => $plan->getName(),
      ]);
      return ['product_id' => '', 'price_monthly_id' => '', 'price_yearly_id' => ''];
    }

    $lockId = 'stripe_sync_plan_' . ($plan->id() ?? 'new_' . md5($plan->getName()));
    if (!$this->lock->acquire($lockId, 30.0)) {
      throw new \RuntimeException("Could not acquire lock for plan sync: $lockId");
    }

    try {
      $productId = $this->syncProduct($plan);
      $priceMonthlyId = $this->syncPrice($plan, $productId, 'month');
      $priceYearlyId = $this->syncPrice($plan, $productId, 'year');

      $this->logger->info(
        'Stripe sync completed for plan @name: product=@prod, price_m=@pm, price_y=@py',
        [
          '@name' => $plan->getName(),
          '@prod' => $productId,
          '@pm' => $priceMonthlyId,
          '@py' => $priceYearlyId,
        ]
      );

      return [
        'product_id' => $productId,
        'price_monthly_id' => $priceMonthlyId,
        'price_yearly_id' => $priceYearlyId,
      ];
    }
    finally {
      $this->lock->release($lockId);
    }
  }

  /**
   * Sincroniza TODOS los planes activos a Stripe (bulk).
   *
   * Usado por el Drush command stripe:sync-plans para migracion inicial.
   * Procesa planes secuencialmente con delay de 100ms para respetar rate limits.
   *
   * @param bool $force
   *   Si TRUE, re-sincroniza incluso planes que ya tienen stripe_product_id.
   * @param string|null $vertical
   *   Filtrar por vertical (machine name). NULL = todos.
   * @param bool $dryRun
   *   Si TRUE, solo muestra que haria sin ejecutar.
   *
   * @return array{synced: int, skipped: int, errors: array<string>}
   *   Resumen de la operacion.
   */
  public function syncAllPlans(bool $force = FALSE, ?string $vertical = NULL, bool $dryRun = FALSE): array {
    $storage = $this->entityTypeManager->getStorage('saas_plan');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', TRUE);

    if ($vertical) {
      // Resolvemos el vertical por machine name.
      $verticalStorage = $this->entityTypeManager->getStorage('vertical');
      $verticalIds = $verticalStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('id', $vertical)
        ->execute();

      if (!empty($verticalIds)) {
        $query->condition('vertical', reset($verticalIds));
      }
    }

    $planIds = $query->execute();
    if (empty($planIds)) {
      return ['synced' => 0, 'skipped' => 0, 'errors' => []];
    }

    $plans = $storage->loadMultiple($planIds);
    $synced = 0;
    $skipped = 0;
    $errors = [];

    foreach ($plans as $plan) {
      assert($plan instanceof SaasPlanInterface);

      // Saltar planes que ya tienen product_id (a menos que --force).
      if (!$force && !empty($plan->getStripeProductId())) {
        $skipped++;
        continue;
      }

      // Saltar planes gratuitos.
      if ($plan->isFree()) {
        $skipped++;
        continue;
      }

      if ($dryRun) {
        $synced++;
        continue;
      }

      try {
        $result = $this->syncPlan($plan);

        // Guardar IDs de Stripe en el entity.
        $plan->set('stripe_product_id', $result['product_id']);
        $plan->set('stripe_price_id', $result['price_monthly_id']);
        $plan->set('stripe_price_yearly_id', $result['price_yearly_id']);
        $plan->setSyncing(TRUE);
        $plan->save();

        $synced++;

        // Rate limiting: 100ms entre llamadas.
        usleep(100000);
      }
      catch (\Throwable $e) {
        $errors[] = sprintf(
          'Plan %s (ID %s): %s',
          $plan->getName(),
          $plan->id() ?? 'NULL',
          $e->getMessage()
        );
      }
    }

    return ['synced' => $synced, 'skipped' => $skipped, 'errors' => $errors];
  }

  /**
   * Archiva un Product en Stripe cuando el plan se desactiva.
   *
   * No elimina el producto — las suscripciones existentes continuan.
   *
   * @param string $stripeProductId
   *   ID del producto Stripe (prod_xxx).
   */
  public function archiveProduct(string $stripeProductId): void {
    if (!$this->stripeConnect || empty($stripeProductId)) {
      return;
    }

    try {
      $this->stripeConnect->stripeRequest('POST', '/v1/products/' . $stripeProductId, [
        'active' => 'false',
      ]);
      $this->logger->info('Archived Stripe product @id.', ['@id' => $stripeProductId]);
    }
    catch (\Throwable $e) {
      $this->logger->error('Failed to archive Stripe product @id: @msg', [
        '@id' => $stripeProductId,
        '@msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Crea o actualiza un Product en Stripe para el plan dado.
   *
   * @param \Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface $plan
   *   El plan SaaS.
   *
   * @return string
   *   El Stripe Product ID (prod_xxx).
   */
  protected function syncProduct(SaasPlanInterface $plan): string {
    $existingProductId = $plan->getStripeProductId();
    $vertical = $plan->getVertical();

    $productData = [
      'name' => $plan->getName(),
      'metadata' => [
        'drupal_plan_id' => (string) ($plan->id() ?? ''),
        'vertical' => $vertical?->id() ?? '_default',
        'source' => 'jaraba_saas',
      ],
    ];

    if ($existingProductId) {
      // Actualizar producto existente.
      $response = $this->stripeConnect->stripeRequest(
        'POST',
        '/v1/products/' . $existingProductId,
        $productData
      );
      return $response['id'];
    }

    // Crear nuevo producto.
    $idempotencyKey = 'jaraba_product_' . ($plan->id() ?? md5($plan->getName()));
    $response = $this->stripeConnect->stripeRequest(
      'POST',
      '/v1/products',
      $productData,
      $idempotencyKey
    );

    return $response['id'];
  }

  /**
   * Sincroniza un Price (monthly o yearly) para el plan dado.
   *
   * Los precios en Stripe son INMUTABLES. Si el precio cambia, se archiva
   * el viejo y se crea uno nuevo. Las suscripciones existentes con el precio
   * viejo continuan sin cambios (Stripe lo maneja).
   *
   * @param \Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface $plan
   *   El plan SaaS.
   * @param string $productId
   *   El Stripe Product ID.
   * @param string $interval
   *   'month' o 'year'.
   *
   * @return string
   *   El Stripe Price ID (price_xxx).
   */
  protected function syncPrice(SaasPlanInterface $plan, string $productId, string $interval): string {
    $amount = $interval === 'year'
      ? $plan->getPriceYearly()
      : $plan->getPriceMonthly();

    // Si no hay precio definido para este intervalo, retornar vacio.
    if ($amount <= 0) {
      return '';
    }

    // Convertir a centimos (Stripe usa la unidad mas pequena).
    $unitAmount = (int) round($amount * 100);

    // Obtener el Price ID existente para este intervalo.
    $existingPriceId = $interval === 'year'
      ? $plan->getStripePriceYearlyId()
      : $plan->getStripePriceId();

    // Si ya existe un Price, verificar si el monto cambio.
    if ($existingPriceId) {
      try {
        $existingPrice = $this->stripeConnect->stripeRequest(
          'GET',
          '/v1/prices/' . $existingPriceId
        );

        // Si el monto no cambio, no hacer nada.
        if (isset($existingPrice['unit_amount']) && (int) $existingPrice['unit_amount'] === $unitAmount) {
          return $existingPriceId;
        }

        // Monto cambio: archivar el precio viejo.
        $this->stripeConnect->stripeRequest('POST', '/v1/prices/' . $existingPriceId, [
          'active' => 'false',
        ]);

        $this->logger->info('Archived old Stripe price @id (amount changed).', [
          '@id' => $existingPriceId,
        ]);
      }
      catch (\Throwable $e) {
        // Si no podemos verificar el precio existente, creamos uno nuevo.
        $this->logger->warning('Could not verify existing price @id: @msg', [
          '@id' => $existingPriceId,
          '@msg' => $e->getMessage(),
        ]);
      }
    }

    // Crear nuevo Price.
    $idempotencyKey = sprintf(
      'jaraba_price_%s_%s_%d',
      $plan->id() ?? md5($plan->getName()),
      $interval,
      $unitAmount
    );

    $response = $this->stripeConnect->stripeRequest(
      'POST',
      '/v1/prices',
      [
        'product' => $productId,
        'unit_amount' => $unitAmount,
        'currency' => 'eur',
        'recurring' => [
          'interval' => $interval,
        ],
        'metadata' => [
          'drupal_plan_id' => (string) ($plan->id() ?? ''),
          'interval' => $interval,
          'source' => 'jaraba_saas',
        ],
      ],
      $idempotencyKey
    );

    return $response['id'];
  }

}
