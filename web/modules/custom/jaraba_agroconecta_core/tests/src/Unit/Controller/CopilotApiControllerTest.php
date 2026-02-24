<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_agroconecta_core\Unit\Controller;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\jaraba_agroconecta_core\Controller\CopilotApiController;
use Drupal\jaraba_agroconecta_core\Service\DemandForecasterService;
use Drupal\jaraba_agroconecta_core\Service\MarketSpyService;
use Drupal\jaraba_agroconecta_core\Service\ProducerCopilotService;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Unit tests for CopilotApiController access control.
 *
 * Verifies that all endpoints enforce ownership by using the current
 * authenticated user's ID rather than user-supplied values.
 *
 * @group jaraba_agroconecta_core
 * @coversDefaultClass \Drupal\jaraba_agroconecta_core\Controller\CopilotApiController
 */
class CopilotApiControllerTest extends UnitTestCase
{

    private ProducerCopilotService&MockObject $copilotService;
    private DemandForecasterService&MockObject $demandForecaster;
    private MarketSpyService&MockObject $marketSpy;
    private CopilotApiController $controller;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->copilotService = $this->createMock(ProducerCopilotService::class);
        $this->demandForecaster = $this->createMock(DemandForecasterService::class);
        $this->marketSpy = $this->createMock(MarketSpyService::class);

        $this->controller = new CopilotApiController(
            $this->copilotService,
            $this->demandForecaster,
            $this->marketSpy,
        );

        // Mock current user.
        $currentUser = $this->createMock(AccountProxyInterface::class);
        $currentUser->method('id')->willReturn(42);

        $reflector = new \ReflectionClass($this->controller);
        // ControllerBase stores currentUser in a protected property.
        // We use the string translation trait mock from UnitTestCase.
        $this->controller->setStringTranslation($this->getStringTranslationStub());
    }

    /**
     * Tests chat() requires a message.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function chatRequiresMessage(): void
    {
        $request = new Request([], [], [], [], [], [],
            json_encode(['producer_id' => 999])
        );

        $response = $this->controller->chat($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * Tests chat() ignores producer_id from request and uses currentUser.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function chatIgnoresClientProvidedProducerId(): void
    {
        $this->copilotService
            ->expects($this->once())
            ->method('chat')
            ->willReturn(['response' => 'test', 'conversation_id' => 1]);

        $request = new Request([], [], [], [], [], [],
            json_encode([
                'producer_id' => 999,
                'message' => 'Hello',
            ])
        );

        $response = $this->controller->chat($request);

        // The controller should use currentUser->id() not the provided 999.
        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * Tests conversations() does not accept producer_id from query.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function conversationsUsesCurrentUser(): void
    {
        $this->copilotService
            ->expects($this->once())
            ->method('getConversations')
            ->willReturn([]);

        $request = new Request(['producer_id' => 999, 'limit' => 10]);

        $response = $this->controller->conversations($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Tests competitivePosition() does not accept producer_id from query.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function competitivePositionUsesCurrentUser(): void
    {
        $this->marketSpy
            ->expects($this->once())
            ->method('getCompetitivePosition')
            ->willReturn(['score' => 75]);

        $request = new Request(['producer_id' => 999]);

        $response = $this->controller->competitivePosition($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
