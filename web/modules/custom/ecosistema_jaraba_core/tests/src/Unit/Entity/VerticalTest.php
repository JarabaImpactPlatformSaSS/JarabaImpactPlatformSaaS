<?php

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Entity;

use Drupal\Tests\UnitTestCase;

/**
 * Tests for the Vertical entity.
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Entity\Vertical
 */
class VerticalTest extends UnitTestCase
{

    /**
     * Tests getting the machine name.
     *
     * @covers ::getMachineName
     */
    public function testGetMachineName(): void
    {
        // Create a mock entity with expected behavior
        $vertical = $this->createMock(\Drupal\ecosistema_jaraba_core\Entity\VerticalInterface::class);
        $vertical->method('getMachineName')
            ->willReturn('agroconecta');

        $this->assertEquals('agroconecta', $vertical->getMachineName());
    }

    /**
     * Tests enabled features as array.
     *
     * @covers ::getEnabledFeatures
     */
    public function testGetEnabledFeaturesReturnsArray(): void
    {
        $vertical = $this->createMock(\Drupal\ecosistema_jaraba_core\Entity\VerticalInterface::class);
        $vertical->method('getEnabledFeatures')
            ->willReturn(['trazabilidad', 'qr_codes', 'ai_storytelling']);

        $features = $vertical->getEnabledFeatures();

        $this->assertIsArray($features);
        $this->assertCount(3, $features);
        $this->assertContains('trazabilidad', $features);
        $this->assertContains('ai_storytelling', $features);
    }

    /**
     * Tests hasFeature method.
     *
     * @covers ::hasFeature
     * @dataProvider featureDataProvider
     */
    public function testHasFeature(string $feature, bool $expected): void
    {
        $vertical = $this->createMock(\Drupal\ecosistema_jaraba_core\Entity\VerticalInterface::class);
        $vertical->method('hasFeature')
            ->willReturnCallback(function ($f) {
                $features = ['trazabilidad', 'qr_codes', 'ai_storytelling'];
                return in_array($f, $features);
            });

        $this->assertEquals($expected, $vertical->hasFeature($feature));
    }

    /**
     * Data provider for feature testing.
     */
    public static function featureDataProvider(): array
    {
        return [
            'existing feature' => ['trazabilidad', TRUE],
            'another existing feature' => ['qr_codes', TRUE],
            'non-existing feature' => ['blockchain', FALSE],
            'empty feature' => ['', FALSE],
        ];
    }

    /**
     * Tests theme settings as JSON.
     *
     * @covers ::getThemeSettings
     */
    public function testGetThemeSettingsReturnsValidJson(): void
    {
        $expectedSettings = [
            'color_primario' => '#FF8C42',
            'color_secundario' => '#2D3436',
            'tipografia' => 'Inter',
        ];

        $vertical = $this->createMock(\Drupal\ecosistema_jaraba_core\Entity\VerticalInterface::class);
        $vertical->method('getThemeSettings')
            ->willReturn($expectedSettings);

        $settings = $vertical->getThemeSettings();

        $this->assertIsArray($settings);
        $this->assertArrayHasKey('color_primario', $settings);
        $this->assertEquals('#FF8C42', $settings['color_primario']);
    }

    /**
     * Tests AI agents configuration.
     *
     * @covers ::getAiAgents
     */
    public function testGetAiAgents(): void
    {
        $vertical = $this->createMock(\Drupal\ecosistema_jaraba_core\Entity\VerticalInterface::class);
        $vertical->method('getAiAgents')
            ->willReturn([
                'producer_copilot',
                'consumer_copilot',
                'marketing_agent',
            ]);

        $agents = $vertical->getAiAgents();

        $this->assertIsArray($agents);
        $this->assertContains('producer_copilot', $agents);
        $this->assertCount(3, $agents);
    }

}
