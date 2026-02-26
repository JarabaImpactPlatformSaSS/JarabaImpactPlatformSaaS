<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Session\AccountInterface;
use Drupal\ecosistema_jaraba_core\CommandBar\CommandProviderInterface;

/**
 * Central registry for command bar search providers.
 *
 * Uses the service collector pattern: providers register via the
 * 'jaraba.command_provider' service tag and are automatically
 * collected by this service.
 *
 * GAP-AUD-008: Command Bar (Cmd+K)
 */
class CommandRegistryService {

  /**
   * Registered command providers.
   *
   * @var \Drupal\ecosistema_jaraba_core\CommandBar\CommandProviderInterface[]
   */
  protected array $providers = [];

  /**
   * Adds a command provider.
   *
   * Called automatically by the service collector.
   *
   * @param \Drupal\ecosistema_jaraba_core\CommandBar\CommandProviderInterface $provider
   *   The command provider to add.
   */
  public function addProvider(CommandProviderInterface $provider): void {
    $this->providers[] = $provider;
  }

  /**
   * Searches all providers and returns merged, sorted results.
   *
   * @param string $query
   *   The search query string.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for access filtering.
   * @param int $limit
   *   Maximum number of results.
   *
   * @return array
   *   Sorted array of result items (highest score first).
   */
  public function search(string $query, AccountInterface $account, int $limit = 10): array {
    if (empty(trim($query))) {
      return [];
    }

    $allResults = [];

    foreach ($this->providers as $provider) {
      if (!$provider->isAccessible($account)) {
        continue;
      }

      try {
        $results = $provider->search($query, $limit);
        foreach ($results as $result) {
          // Filter by permission if specified.
          if (!empty($result['permission']) && !$account->hasPermission($result['permission'])) {
            continue;
          }
          unset($result['permission']);
          $allResults[] = $result;
        }
      }
      catch (\Exception $e) {
        // Skip failing providers gracefully.
        continue;
      }
    }

    // Sort by score descending.
    usort($allResults, fn(array $a, array $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

    return array_slice($allResults, 0, $limit);
  }

}
