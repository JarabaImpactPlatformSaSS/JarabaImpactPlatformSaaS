<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Service;

/**
 * Registry for vertical-specific copilot bridges.
 *
 * GAP-COPILOT-5: Collects all CopilotBridgeInterface services
 * tagged with 'jaraba_copilot.bridge' and resolves them by vertical key.
 *
 * Injected into CopilotOrchestratorService to provide vertical-aware
 * context enrichment in the system prompt.
 */
class CopilotBridgeRegistry {

  /**
   * Registered bridges keyed by vertical.
   *
   * @var array<string, CopilotBridgeInterface>
   */
  protected array $bridges = [];

  /**
   * Registers a bridge service.
   *
   * Called by the service container via tagged service calls.
   *
   * @param CopilotBridgeInterface $bridge
   *   The bridge to register.
   */
  public function addBridge(?CopilotBridgeInterface $bridge): void {
    if ($bridge !== NULL) {
      $this->bridges[$bridge->getVerticalKey()] = $bridge;
    }
  }

  /**
   * Gets a bridge for a specific vertical.
   *
   * @param string $verticalKey
   *   The vertical key (e.g., 'empleabilidad').
   *
   * @return CopilotBridgeInterface|null
   *   The bridge, or NULL if not registered.
   */
  public function getBridge(string $verticalKey): ?CopilotBridgeInterface {
    return $this->bridges[$verticalKey] ?? NULL;
  }

  /**
   * Gets all registered bridges.
   *
   * @return array<string, CopilotBridgeInterface>
   *   Bridges keyed by vertical.
   */
  public function getAll(): array {
    return $this->bridges;
  }

  /**
   * Checks if a bridge is registered for a vertical.
   */
  public function has(string $verticalKey): bool {
    return isset($this->bridges[$verticalKey]);
  }

}
