<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Commands;

use Drupal\jaraba_billing\Service\StripeProductSyncService;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for Stripe synchronization.
 *
 * STRIPE-CHECKOUT-001 §4.5: Migracion inicial de los 40+ planes SaaS
 * existentes a Stripe Products/Prices.
 */
class StripeSyncCommands extends DrushCommands {

  public function __construct(
    protected StripeProductSyncService $syncService,
  ) {
    parent::__construct();
  }

  /**
   * Sincroniza todos los SaasPlan activos con Stripe Products/Prices.
   *
   * Crea Products y Prices en Stripe para cada plan activo que no tenga
   * stripe_product_id (o todos si se usa --force). Los IDs generados se
   * guardan automaticamente en el entity.
   */
  #[CLI\Command(name: 'stripe:sync-plans', aliases: ['ssp'])]
  #[CLI\Option(name: 'force', description: 'Re-sincroniza incluso planes que ya tienen stripe_product_id.')]
  #[CLI\Option(name: 'dry-run', description: 'Muestra que haria sin ejecutar.')]
  #[CLI\Option(name: 'vertical', description: 'Filtrar por vertical (machine name).')]
  #[CLI\Usage(name: 'drush stripe:sync-plans', description: 'Sincroniza planes sin stripe_product_id.')]
  #[CLI\Usage(name: 'drush stripe:sync-plans --force', description: 'Re-sincroniza todos los planes.')]
  #[CLI\Usage(name: 'drush stripe:sync-plans --dry-run', description: 'Muestra que haria sin ejecutar.')]
  #[CLI\Usage(name: 'drush stripe:sync-plans --vertical=agroconecta', description: 'Solo planes de un vertical.')]
  public function syncPlans(
    array $options = [
      'force' => FALSE,
      'dry-run' => FALSE,
      'vertical' => NULL,
    ],
  ): void {
    $force = (bool) $options['force'];
    $dryRun = (bool) $options['dry-run'];
    $vertical = $options['vertical'];

    if ($dryRun) {
      $this->io()->note('Modo dry-run: no se ejecutaran cambios en Stripe.');
    }

    if ($force) {
      $this->io()->note('Modo force: se re-sincronizaran TODOS los planes.');
    }

    if ($vertical) {
      $this->io()->note("Filtrando por vertical: $vertical");
    }

    $result = $this->syncService->syncAllPlans($force, $vertical, $dryRun);

    $this->io()->success(sprintf(
      '%s: %d, Skipped: %d, Errors: %d',
      $dryRun ? 'Would sync' : 'Synced',
      $result['synced'],
      $result['skipped'],
      count($result['errors'])
    ));

    if (!empty($result['errors'])) {
      $this->io()->warning('Errores encontrados:');
      foreach ($result['errors'] as $error) {
        $this->io()->writeln("  - $error");
      }
    }
  }

}
