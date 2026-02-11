<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_geo\Unit\Service;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_geo\Service\EeatService;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the EeatService service.
 *
 * @coversDefaultClass \Drupal\jaraba_geo\Service\EeatService
 * @group jaraba_geo
 */
class EeatServiceTest extends TestCase
{

    /**
     * The EeatService under test.
     */
    protected EeatService $eeatService;

    /**
     * Mock entity type manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * Mock date formatter.
     */
    protected DateFormatterInterface $dateFormatter;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
        $this->dateFormatter = $this->createMock(DateFormatterInterface::class);
        $this->dateFormatter->method('format')
            ->willReturn('11 Feb 2026');

        $this->eeatService = new EeatService(
            $this->entityTypeManager,
            $this->dateFormatter,
        );
    }

    /**
     * Helper to create a producer mock entity.
     *
     * @param string $name
     *   The label for the entity.
     *
     * @return \Drupal\Core\Entity\EntityInterface
     *   A mocked entity interface.
     */
    protected function createProducerMock(string $name = 'Finca Los Olivos'): ContentEntityInterface
    {
        $producer = $this->createMock(ContentEntityInterface::class);
        $producer->method('label')->willReturn($name);
        $producer->method('bundle')->willReturn('producer');
        $producer->method('hasField')->willReturn(false);

        return $producer;
    }

    /**
     * Tests that generateProducerBio returns a non-empty string in summary.
     *
     * @covers ::generateProducerBio
     */
    public function testGenerateProducerBioReturnsString(): void
    {
        $producer = $this->createProducerMock();

        $result = $this->eeatService->generateProducerBio($producer);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertIsString($result['summary']);
        $this->assertNotEmpty($result['summary']);
    }

    /**
     * Tests that generateProducerBio contains the producer name.
     *
     * @covers ::generateProducerBio
     */
    public function testGenerateProducerBioContainsName(): void
    {
        $name = 'Bodegas Sierra Mágina';
        $producer = $this->createProducerMock($name);

        $result = $this->eeatService->generateProducerBio($producer);

        $this->assertArrayHasKey('name', $result);
        $this->assertSame($name, $result['name']);
        $this->assertStringContainsString($name, $result['summary']);
    }

    /**
     * Tests that generateCaseStudy returns an array with expected keys.
     *
     * @covers ::generateCaseStudy
     */
    public function testGenerateCaseStudyReturnsArray(): void
    {
        $entity = $this->createProducerMock('Cooperativa La Unión');

        $result = $this->eeatService->generateCaseStudy($entity);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('challenge', $result);
        $this->assertArrayHasKey('solution', $result);
        $this->assertArrayHasKey('results', $result);
        $this->assertArrayHasKey('quote', $result);
        $this->assertArrayHasKey('last_updated', $result);
    }

}
