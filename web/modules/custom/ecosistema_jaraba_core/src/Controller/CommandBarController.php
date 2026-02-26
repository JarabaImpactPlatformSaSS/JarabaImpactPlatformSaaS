<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\CommandRegistryService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API controller for the Command Bar (Cmd+K) search.
 *
 * Endpoint: GET /api/v1/command-bar/search?q={query}
 * Requires CSRF token and authentication.
 *
 * GAP-AUD-008: Command Bar (Cmd+K)
 */
class CommandBarController extends ControllerBase {

  /**
   * Constructs a CommandBarController.
   *
   * @param \Drupal\ecosistema_jaraba_core\Service\CommandRegistryService $commandRegistry
   *   The command registry service.
   */
  public function __construct(
    protected CommandRegistryService $commandRegistry,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('ecosistema_jaraba_core.command_registry'),
    );
  }

  /**
   * Searches for commands matching the query.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request containing 'q' query parameter.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with search results.
   */
  public function search(Request $request): JsonResponse {
    $query = trim($request->query->get('q', ''));

    if (empty($query) || mb_strlen($query) < 2) {
      return new JsonResponse([
        'success' => TRUE,
        'results' => [],
      ]);
    }

    $results = $this->commandRegistry->search(
      $query,
      $this->currentUser(),
      10,
    );

    return new JsonResponse([
      'success' => TRUE,
      'results' => $results,
    ]);
  }

}
