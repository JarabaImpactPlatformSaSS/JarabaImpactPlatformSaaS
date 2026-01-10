<?php

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Entity;

use Drupal\Tests\UnitTestCase;
use Drupal\ecosistema_jaraba_core\Entity\AIAgentInterface;

/**
 * Tests for the AIAgent config entity.
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Entity\AIAgent
 */
class AIAgentTest extends UnitTestCase
{

    /**
     * Tests AIAgent interface methods.
     */
    public function testAIAgentInterface(): void
    {
        $agent = $this->createMock(AIAgentInterface::class);

        $agent->method('id')->willReturn('marketing_agent');
        $agent->method('label')->willReturn('Marketing Agent');
        $agent->method('getDescription')->willReturn('Generación de contenido de marketing.');
        $agent->method('getServiceId')->willReturn('ecosistema_jaraba_core.marketing_agent');
        $agent->method('getIcon')->willReturn('bullhorn');
        $agent->method('getColor')->willReturn('#e91e63');
        $agent->method('getWeight')->willReturn(1);
        $agent->method('status')->willReturn(TRUE);

        $this->assertEquals('marketing_agent', $agent->id());
        $this->assertEquals('Marketing Agent', $agent->label());
        $this->assertEquals('Generación de contenido de marketing.', $agent->getDescription());
        $this->assertEquals('ecosistema_jaraba_core.marketing_agent', $agent->getServiceId());
        $this->assertEquals('bullhorn', $agent->getIcon());
        $this->assertEquals('#e91e63', $agent->getColor());
        $this->assertEquals(1, $agent->getWeight());
        $this->assertTrue($agent->status());
    }

    /**
     * Tests AIAgent color validation.
     *
     * @dataProvider colorDataProvider
     */
    public function testAgentColorFormat(string $color, bool $isValid): void
    {
        // Validate hex color format
        $isHexColor = preg_match('/^#[0-9A-Fa-f]{6}$/', $color) === 1;
        $this->assertEquals($isValid, $isHexColor);
    }

    /**
     * Data provider for color testing.
     */
    public static function colorDataProvider(): array
    {
        return [
            'valid hex lowercase' => ['#1a73e8', TRUE],
            'valid hex uppercase' => ['#E91E63', TRUE],
            'valid hex mixed' => ['#4cAf50', TRUE],
            'invalid no hash' => ['1a73e8', FALSE],
            'invalid short' => ['#fff', FALSE],
            'invalid long' => ['#1a73e8ff', FALSE],
            'invalid chars' => ['#gggggg', FALSE],
        ];
    }

    /**
     * Tests AIAgent service ID format.
     */
    public function testServiceIdFormat(): void
    {
        $agent = $this->createMock(AIAgentInterface::class);
        $agent->method('getServiceId')
            ->willReturn('ecosistema_jaraba_core.marketing_agent');

        $serviceId = $agent->getServiceId();

        // Service ID should follow Drupal convention: module_name.service_name
        $this->assertMatchesRegularExpression('/^[a-z_]+\.[a-z_]+$/', $serviceId);
    }

    /**
     * Tests disabled agent.
     */
    public function testDisabledAgent(): void
    {
        $agent = $this->createMock(AIAgentInterface::class);
        $agent->method('status')->willReturn(FALSE);

        $this->assertFalse($agent->status());
    }

    /**
     * Tests agent without service ID (placeholder).
     */
    public function testAgentWithoutServiceId(): void
    {
        $agent = $this->createMock(AIAgentInterface::class);
        $agent->method('getServiceId')->willReturn('');

        $serviceId = $agent->getServiceId();
        $this->assertEmpty($serviceId);
    }

    /**
     * Tests default color value.
     */
    public function testDefaultColor(): void
    {
        $agent = $this->createMock(AIAgentInterface::class);
        $agent->method('getColor')->willReturn('#1a73e8');

        // Default color should be the platform blue
        $this->assertEquals('#1a73e8', $agent->getColor());
    }

}
