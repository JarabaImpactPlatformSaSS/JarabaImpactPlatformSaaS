<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\CommandBar;

use Drupal\Core\Session\AccountInterface;

/**
 * Interface for command bar search providers.
 *
 * Providers register via 'jaraba.command_provider' service tag and supply
 * search results to the CommandRegistryService.
 *
 * GAP-AUD-008: Command Bar (Cmd+K)
 */
interface CommandProviderInterface {

  /**
   * Searches for commands matching the query.
   *
   * @param string $query
   *   The search query string.
   * @param int $limit
   *   Maximum number of results to return.
   *
   * @return array
   *   Array of result items, each with:
   *   - label: string — Display text
   *   - url: string — URL to navigate to
   *   - icon: string — Material icon name
   *   - category: string — Result category
   *   - score: float — Relevance score (0-100)
   */
  public function search(string $query, int $limit = 5): array;

  /**
   * Checks if this provider is accessible to the given account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to check.
   *
   * @return bool
   *   TRUE if the user can access this provider's results.
   */
  public function isAccessible(AccountInterface $account): bool;

}
