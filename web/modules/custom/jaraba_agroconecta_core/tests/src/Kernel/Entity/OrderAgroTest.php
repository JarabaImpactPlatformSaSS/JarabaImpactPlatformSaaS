<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_agroconecta_core\Kernel\Entity;

use Drupal\KernelTests\KernelTestBase;
use Drupal\jaraba_agroconecta_core\Entity\OrderAgro;

/**
 * Kernel tests for the OrderAgro entity.
 *
 * @group jaraba_agroconecta_core
 * @coversDefaultClass \Drupal\jaraba_agroconecta_core\Entity\OrderAgro
 */
class OrderAgroTest extends KernelTestBase
{

    /**
     * {@inheritdoc}
     */
    protected static $modules = [
        'system',
        'user',
        'taxonomy',
        'jaraba_agroconecta_core',
    ];

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->installEntitySchema('user');
        if ($this->container->get('entity_type.manager')->hasDefinition('order_agro')) {
            $this->installEntitySchema('order_agro');
        }
    }

    /**
     * Tests that getStateLabels() returns all expected states.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function stateLabelsContainAllStates(): void
    {
        $labels = OrderAgro::getStateLabels();

        $this->assertIsArray($labels);
        $this->assertNotEmpty($labels);
        $this->assertArrayHasKey(OrderAgro::STATE_PENDING, $labels);
        $this->assertArrayHasKey(OrderAgro::STATE_PAID, $labels);
        $this->assertArrayHasKey(OrderAgro::STATE_PROCESSING, $labels);
        $this->assertArrayHasKey(OrderAgro::STATE_SHIPPED, $labels);
        $this->assertArrayHasKey(OrderAgro::STATE_DELIVERED, $labels);
        $this->assertArrayHasKey(OrderAgro::STATE_COMPLETED, $labels);
        $this->assertArrayHasKey(OrderAgro::STATE_CANCELLED, $labels);
    }

    /**
     * Tests that state constants are string values.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function stateConstantsAreStrings(): void
    {
        $this->assertIsString(OrderAgro::STATE_PENDING);
        $this->assertIsString(OrderAgro::STATE_PAID);
        $this->assertIsString(OrderAgro::STATE_PROCESSING);
        $this->assertIsString(OrderAgro::STATE_SHIPPED);
        $this->assertIsString(OrderAgro::STATE_DELIVERED);
        $this->assertIsString(OrderAgro::STATE_COMPLETED);
        $this->assertIsString(OrderAgro::STATE_CANCELLED);
    }

    /**
     * Tests entity type definition exists.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function entityTypeDefinitionExists(): void
    {
        $entityTypeManager = $this->container->get('entity_type.manager');
        $this->assertTrue($entityTypeManager->hasDefinition('order_agro'));
    }
}
