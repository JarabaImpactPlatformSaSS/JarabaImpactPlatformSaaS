<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Service;

/**
 * Interface for vertical-specific copilot context bridges.
 *
 * GAP-COPILOT-5: Each vertical implements this interface to inject
 * domain-specific context into the copilot's system prompt.
 *
 * Services are collected via 'jaraba_copilot.bridge' tag and resolved
 * dynamically by the CopilotBridgeRegistry based on the user's vertical.
 */
interface CopilotBridgeInterface {

  /**
   * Returns the vertical key this bridge serves.
   *
   * @return string
   *   One of VERTICAL-CANONICAL-001 values.
   */
  public function getVerticalKey(): string;

  /**
   * Gets context relevant to the copilot for a specific user.
   *
   * @param int $userId
   *   The Drupal user ID.
   *
   * @return array
   *   Associative array of vertical-specific context data.
   */
  public function getRelevantContext(int $userId): array;

  /**
   * Gets a soft upsell/upgrade suggestion if applicable.
   *
   * @param int $userId
   *   The Drupal user ID.
   *
   * @return array|null
   *   Suggestion data or NULL if no suggestion.
   */
  public function getSoftSuggestion(int $userId): ?array;

}
