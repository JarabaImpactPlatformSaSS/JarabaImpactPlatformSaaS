<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_sepe_teleformacion\Unit;

use Drupal\jaraba_sepe_teleformacion\Service\SepeSoapService;
use Drupal\jaraba_sepe_teleformacion\Service\SepeDataMapper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests for SepeSoapService.
 *
 * @coversDefaultClass \Drupal\jaraba_sepe_teleformacion\Service\SepeSoapService
 * @group jaraba_sepe_teleformacion
 */
class SepeSoapServiceTest extends UnitTestCase
{

    use ProphecyTrait;

    /**
     * The service under test.
     */
    protected SepeSoapService $soapService;

    /**
     * Mock entity type manager.
     */
    protected $entityTypeManager;

    /**
     * Mock data mapper.
     */
    protected $dataMapper;

    /**
     * Mock config factory.
     */
    protected $configFactory;

    /**
     * Mock logger.
     */
    protected $logger;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
        $this->dataMapper = $this->prophesize(SepeDataMapper::class);
        $this->configFactory = $this->prophesize(ConfigFactoryInterface::class);
        $this->logger = $this->prophesize(LoggerChannelInterface::class);

        // Mock config.
        $config = $this->prophesize(ImmutableConfig::class);
        $config->get('centro_activo_id')->willReturn(NULL);
        $this->configFactory->get('jaraba_sepe_teleformacion.settings')
            ->willReturn($config->reveal());

        $this->soapService = new SepeSoapService(
            $this->entityTypeManager->reveal(),
            $this->dataMapper->reveal(),
            $this->configFactory->reveal(),
            $this->logger->reveal()
        );
    }

    /**
     * Tests that the service can be instantiated.
     */
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(SepeSoapService::class, $this->soapService);
    }

    /**
     * Tests obtenerDatosCentro returns error when no centro configured.
     *
     * @covers ::obtenerDatosCentro
     */
    public function testObtenerDatosCentroReturnsErrorWhenNoCentro(): void
    {
        $result = $this->soapService->obtenerDatosCentro();
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Centro no configurado', $result['error']);
    }

    /**
     * Tests obtenerListaAcciones returns empty array when no centro.
     *
     * @covers ::obtenerListaAcciones
     */
    public function testObtenerListaAccionesReturnsEmptyWhenNoCentro(): void
    {
        $result = $this->soapService->obtenerListaAcciones();
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Tests all 6 SEPE operations exist.
     *
     * @covers ::obtenerDatosCentro
     * @covers ::crearAccion
     * @covers ::obtenerListaAcciones
     * @covers ::obtenerDatosAccion
     * @covers ::obtenerParticipantes
     * @covers ::obtenerSeguimiento
     */
    public function testAllSepeOperationsExist(): void
    {
        $operations = [
            'obtenerDatosCentro',
            'crearAccion',
            'obtenerListaAcciones',
            'obtenerDatosAccion',
            'obtenerParticipantes',
            'obtenerSeguimiento',
        ];

        foreach ($operations as $operation) {
            $this->assertTrue(
                method_exists($this->soapService, $operation),
                "Operation $operation should exist"
            );
        }
    }

}
