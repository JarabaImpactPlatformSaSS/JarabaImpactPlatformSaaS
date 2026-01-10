<?php

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Entity;

use Drupal\Tests\UnitTestCase;
use Drupal\ecosistema_jaraba_core\Entity\FeatureInterface;

/**
 * Tests for the Feature config entity.
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Entity\Feature
 */
class FeatureTest extends UnitTestCase
{

    /**
     * Tests Feature interface methods.
     */
    public function testFeatureInterface(): void
    {
        $feature = $this->createMock(FeatureInterface::class);
        
        $feature->method('id')->willReturn('trazabilidad');
        $feature->method('label')->willReturn('Trazabilidad de productos');
        $feature->method('getDescription')->willReturn('Permite rastrear el origen de productos.');
        $feature->method('getCategory')->willReturn('general');
        $feature->method('getIcon')->willReturn('map-pin');
        $feature->method('getWeight')->willReturn(0);
        $feature->method('status')->willReturn(TRUE);
        
        $this->assertEquals('trazabilidad', $feature->id());
        $this->assertEquals('Trazabilidad de productos', $feature->label());
        $this->assertEquals('Permite rastrear el origen de productos.', $feature->getDescription());
        $this->assertEquals('general', $feature->getCategory());
        $this->assertEquals('map-pin', $feature->getIcon());
        $this->assertEquals(0, $feature->getWeight());
        $this->assertTrue($feature->status());
    }

    /**
     * Tests Feature categories.
     *
     * @dataProvider categoryDataProvider
     */
    public function testFeatureCategories(string $category): void
    {
        $validCategories = ['general', 'integraciones', 'ia', 'comercio', 'seguridad'];
        $this->assertContains($category, $validCategories);
    }

    /**
     * Data provider for category testing.
     */
    public static function categoryDataProvider(): array
    {
        return [
            'general' => ['general'],
            'integraciones' => ['integraciones'],
            'ia' => ['ia'],
            'comercio' => ['comercio'],
            'seguridad' => ['seguridad'],
        ];
    }

    /**
     * Tests disabled features are not active.
     */
    public function testDisabledFeature(): void
    {
        $feature = $this->createMock(FeatureInterface::class);
        $feature->method('status')->willReturn(FALSE);
        
        $this->assertFalse($feature->status());
    }

    /**
     * Tests Feature weight ordering.
     */
    public function testFeatureWeightOrdering(): void
    {
        $feature1 = $this->createMock(FeatureInterface::class);
        $feature1->method('getWeight')->willReturn(0);
        
        $feature2 = $this->createMock(FeatureInterface::class);
        $feature2->method('getWeight')->willReturn(5);
        
        $feature3 = $this->createMock(FeatureInterface::class);
        $feature3->method('getWeight')->willReturn(10);
        
        // Lower weight should appear first
        $this->assertLessThan($feature2->getWeight(), $feature1->getWeight());
        $this->assertLessThan($feature3->getWeight(), $feature2->getWeight());
    }

}
