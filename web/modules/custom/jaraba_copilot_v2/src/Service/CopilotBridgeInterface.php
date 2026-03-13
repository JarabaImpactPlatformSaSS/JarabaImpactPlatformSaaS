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
   * The returned array may include special underscore-prefixed keys that
   * are extracted by CopilotOrchestratorService::resolveVerticalBridgeContext()
   * and used to customize the copilot behavior:
   *
   * - '_system_prompt_addition': string — Full system prompt that REPLACES the
   *   generic base/mode prompts. Used for coordinador, legal, etc.
   * - '_modos_permitidos': string[] — Copilot modes allowed for this context.
   * - '_instrucciones_fase': string[] — Phase-specific instructions.
   * - '_instrucciones_barreras': string[] — Barrier-breaking instructions.
   *
   * Non-prefixed keys are formatted as context text injected into the prompt.
   *
   * For LLM-driven action buttons, include [ACTION:label|url] markers in
   * '_system_prompt_addition' with available routes. See
   * AndaluciaEiCopilotBridgeService for reference implementation.
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
