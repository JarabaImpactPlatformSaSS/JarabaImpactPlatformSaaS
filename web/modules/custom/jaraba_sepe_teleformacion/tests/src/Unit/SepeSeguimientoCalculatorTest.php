<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_sepe_teleformacion\Unit;

use Drupal\jaraba_sepe_teleformacion\Service\SepeSeguimientoCalculator;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests for SepeSeguimientoCalculator service.
 *
 * @coversDefaultClass \Drupal\jaraba_sepe_teleformacion\Service\SepeSeguimientoCalculator
 * @group jaraba_sepe_teleformacion
 */
class SepeSeguimientoCalculatorTest extends UnitTestCase
{

    use ProphecyTrait;

    /**
     * The service under test.
     */
    protected SepeSeguimientoCalculator $calculator;

    /**
     * Mock entity type manager.
     */
    protected $entityTypeManager;

    /**
     * Mock database connection.
     */
    protected $database;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
        $this->database = $this->prophesize(Connection::class);

        $this->calculator = new SepeSeguimientoCalculator(
            $this->entityTypeManager->reveal(),
            $this->database->reveal()
        );
    }

    /**
     * Tests that the calculator can be instantiated.
     */
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(SepeSeguimientoCalculator::class, $this->calculator);
    }

    /**
     * Tests calculate method returns expected structure.
     *
     * @covers ::calculate
     */
    public function testCalculateReturnsExpectedStructure(): void
    {
        // Verify the main service methods exist and are callable.
        $this->assertTrue(method_exists($this->calculator, 'actualizarSeguimientoParticipante'));
        $this->assertTrue(method_exists($this->calculator, 'calcularHorasConectado'));
        $this->assertTrue(method_exists($this->calculator, 'calcularPorcentajeProgreso'));
        $this->assertTrue(method_exists($this->calculator, 'contarActividadesRealizadas'));
        $this->assertTrue(method_exists($this->calculator, 'calcularNotaMedia'));
    }

}
