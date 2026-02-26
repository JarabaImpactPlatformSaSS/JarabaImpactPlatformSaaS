<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Controller;

use Drupal\Core\Session\AccountInterface;
use Drupal\ecosistema_jaraba_core\Controller\CommandBarController;
use Drupal\ecosistema_jaraba_core\Service\CommandRegistryService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the CommandBarController search endpoint logic.
 *
 * The controller extends ControllerBase which requires a Drupal container for
 * currentUser(). We use a testable subclass that overrides currentUser() to
 * return a mock, avoiding the container dependency.
 *
 * @group ecosistema_jaraba_core
 * @covers \Drupal\ecosistema_jaraba_core\Controller\CommandBarController
 */
class CommandBarControllerTest extends TestCase {

  /**
   * Mocked command registry service.
   */
  protected CommandRegistryService $commandRegistry;

  /**
   * Mocked current user account.
   */
  protected AccountInterface $currentUser;

  /**
   * The controller under test.
   */
  protected TestableCommandBarController $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->commandRegistry = $this->createMock(CommandRegistryService::class);
    $this->currentUser = $this->createMock(AccountInterface::class);

    $this->controller = new TestableCommandBarController(
      $this->commandRegistry,
      $this->currentUser,
    );
  }

  /**
   * Tests that search returns results when query is valid.
   */
  public function testSearchReturnsResults(): void {
    $expectedResults = [
      [
        'label' => 'Create Article',
        'url' => '/content-hub/articles/add',
        'icon' => 'add_circle',
        'category' => 'Actions',
        'score' => 95,
      ],
    ];

    $this->commandRegistry
      ->expects($this->once())
      ->method('search')
      ->with('article', $this->currentUser, 10)
      ->willReturn($expectedResults);

    $request = new Request(['q' => 'article']);
    $response = $this->controller->search($request);

    $data = json_decode($response->getContent(), TRUE);

    $this->assertSame(200, $response->getStatusCode());
    $this->assertTrue($data['success']);
    $this->assertCount(1, $data['results']);
    $this->assertSame('Create Article', $data['results'][0]['label']);
  }

  /**
   * Tests that search returns empty results for an empty query.
   */
  public function testSearchEmptyQuery(): void {
    $this->commandRegistry
      ->expects($this->never())
      ->method('search');

    $request = new Request(['q' => '']);
    $response = $this->controller->search($request);

    $data = json_decode($response->getContent(), TRUE);

    $this->assertTrue($data['success']);
    $this->assertSame([], $data['results']);
  }

  /**
   * Tests that search returns empty results when query is shorter than 2 chars.
   */
  public function testSearchMinLength(): void {
    $this->commandRegistry
      ->expects($this->never())
      ->method('search');

    $request = new Request(['q' => 'a']);
    $response = $this->controller->search($request);

    $data = json_decode($response->getContent(), TRUE);

    $this->assertTrue($data['success']);
    $this->assertSame([], $data['results']);
  }

  /**
   * Tests that search returns empty results when 'q' parameter is missing.
   */
  public function testSearchMissingQueryParameter(): void {
    $this->commandRegistry
      ->expects($this->never())
      ->method('search');

    $request = new Request();
    $response = $this->controller->search($request);

    $data = json_decode($response->getContent(), TRUE);

    $this->assertTrue($data['success']);
    $this->assertSame([], $data['results']);
  }

  /**
   * Tests that whitespace-only queries are treated as empty.
   */
  public function testSearchWhitespaceOnlyQuery(): void {
    $this->commandRegistry
      ->expects($this->never())
      ->method('search');

    $request = new Request(['q' => '   ']);
    $response = $this->controller->search($request);

    $data = json_decode($response->getContent(), TRUE);

    $this->assertTrue($data['success']);
    $this->assertSame([], $data['results']);
  }

}

/**
 * Testable subclass that overrides the container-dependent currentUser().
 */
class TestableCommandBarController extends CommandBarController {

  /**
   * The mock user to return from currentUser().
   */
  protected AccountInterface $mockUser;

  /**
   * Constructs the testable controller.
   *
   * @param \Drupal\ecosistema_jaraba_core\Service\CommandRegistryService $commandRegistry
   *   The command registry service.
   * @param \Drupal\Core\Session\AccountInterface $mockUser
   *   The mock current user.
   */
  public function __construct(
    CommandRegistryService $commandRegistry,
    AccountInterface $mockUser,
  ) {
    parent::__construct($commandRegistry);
    $this->mockUser = $mockUser;
  }

  /**
   * {@inheritdoc}
   */
  protected function currentUser(): AccountInterface {
    return $this->mockUser;
  }

}
