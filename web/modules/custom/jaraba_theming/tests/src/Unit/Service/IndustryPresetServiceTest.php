<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_theming\Unit\Service;

use Drupal\jaraba_theming\Service\IndustryPresetService;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the IndustryPresetService service.
 *
 * @coversDefaultClass \Drupal\jaraba_theming\Service\IndustryPresetService
 * @group jaraba_theming
 */
class IndustryPresetServiceTest extends TestCase
{

    /**
     * The IndustryPresetService under test.
     */
    protected IndustryPresetService $presetService;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->presetService = new IndustryPresetService();
    }

    /**
     * Tests that getAllPresets returns a non-empty array.
     *
     * @covers ::getAllPresets
     */
    public function testGetAllPresetsReturnsNonEmpty(): void
    {
        $presets = $this->presetService->getAllPresets();

        $this->assertIsArray($presets);
        $this->assertNotEmpty($presets);
        $this->assertGreaterThanOrEqual(1, count($presets));
    }

    /**
     * Tests that getAllPresets returns exactly 15 presets.
     *
     * @covers ::getAllPresets
     */
    public function testGetAllPresetsCount(): void
    {
        $presets = $this->presetService->getAllPresets();

        $this->assertCount(15, $presets);
    }

    /**
     * Tests that getPreset returns data for a known preset.
     *
     * The 'agro_oliva' preset should exist and contain the expected
     * structure keys: label, vertical, colors, typography, ui.
     *
     * @covers ::getPreset
     */
    public function testGetPresetReturnsData(): void
    {
        $preset = $this->presetService->getPreset('agro_oliva');

        $this->assertIsArray($preset);
        $this->assertArrayHasKey('label', $preset);
        $this->assertArrayHasKey('vertical', $preset);
        $this->assertArrayHasKey('colors', $preset);
        $this->assertArrayHasKey('typography', $preset);
        $this->assertArrayHasKey('ui', $preset);
    }

    /**
     * Tests that getPreset returns null for an unknown preset.
     *
     * @covers ::getPreset
     */
    public function testGetPresetReturnsNullForUnknown(): void
    {
        $result = $this->presetService->getPreset('nonexistent');

        $this->assertNull($result);
    }

    /**
     * Tests that getPresetsByVertical filters correctly.
     *
     * All presets returned for 'agroconecta' should have vertical = 'agroconecta'.
     *
     * @covers ::getPresetsByVertical
     */
    public function testGetPresetsByVerticalFilters(): void
    {
        $presets = $this->presetService->getPresetsByVertical('agroconecta');

        $this->assertIsArray($presets);
        $this->assertNotEmpty($presets);

        foreach ($presets as $id => $preset) {
            $this->assertSame(
                'agroconecta',
                $preset['vertical'],
                "Preset '{$id}' should belong to vertical 'agroconecta'."
            );
        }
    }

    /**
     * Tests that generateCss returns a non-empty CSS string for a known preset.
     *
     * @covers ::generateCss
     */
    public function testGenerateCssReturnsString(): void
    {
        $css = $this->presetService->generateCss('agro_oliva');

        $this->assertIsString($css);
        $this->assertNotEmpty($css);
        $this->assertStringContainsString(':root', $css);
        $this->assertStringContainsString('--ej-color-primary', $css);
    }

}
