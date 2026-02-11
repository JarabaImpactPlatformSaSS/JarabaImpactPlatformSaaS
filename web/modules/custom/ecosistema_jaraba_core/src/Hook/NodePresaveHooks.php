<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\ecosistema_jaraba_core\Service\MicroAutomationService;
use Drupal\node\NodeInterface;
use Psr\Log\LoggerInterface;

/**
 * BE-09: OOP hook for node_presave with proper DI.
 *
 * Replaces procedural hook_node_presave() service locator pattern.
 * Auto-tags products on save using MicroAutomationService.
 */
class NodePresaveHooks {

  public function __construct(
    protected readonly MicroAutomationService $microAutomation,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Auto-tags products on presave.
   */
  #[Hook('node_presave')]
  public function autoTagProduct(NodeInterface $node): void {
    if ($node->bundle() !== 'product') {
      return;
    }

    $hasTags = $node->hasField('field_tags') && !$node->get('field_tags')->isEmpty();

    if ($node->isNew() || !$hasTags) {
      try {
        $suggestedTags = $this->microAutomation->autoTagProduct($node);

        if ($node->hasField('field_auto_tags')) {
          $node->set('field_auto_tags', implode(', ', $suggestedTags));
        }

        $this->logger->info(
          'Auto-tagged product "@title" on save',
          ['@title' => $node->getTitle()]
        );
      }
      catch (\Exception $e) {
        $this->logger->warning(
          'Failed to auto-tag product: @error',
          ['@error' => $e->getMessage()]
        );
      }
    }
  }

}
