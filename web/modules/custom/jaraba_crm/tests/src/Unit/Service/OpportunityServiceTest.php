<?php

declare(strict_types=1);

// Define the function stub in the global namespace for unit tests, since
// the real implementation lives in jaraba_crm.allowed_values.inc and is
// not autoloaded in a unit test context.
namespace {
  if (!function_exists('jaraba_crm_get_opportunity_stage_values')) {

    /**
     * Stub for jaraba_crm_get_opportunity_stage_values().
     */
    function jaraba_crm_get_opportunity_stage_values(): array {
      return [
        'lead' => 'Lead',
        'qualification' => 'Qualification',
        'proposal' => 'Proposal',
        'negotiation' => 'Negotiation',
        'closed_won' => 'Closed Won',
        'closed_lost' => 'Closed Lost',
      ];
    }

  }
}

namespace Drupal\Tests\jaraba_crm\Unit\Service {

  use Drupal\Core\Entity\EntityStorageInterface;
  use Drupal\Core\Entity\EntityTypeManagerInterface;
  use Drupal\Core\Entity\Query\QueryInterface;
  use Drupal\jaraba_crm\Entity\Opportunity;
  use Drupal\jaraba_crm\Service\OpportunityService;
  use Drupal\Tests\UnitTestCase;

  /**
   * Tests para OpportunityService.
   *
   * @covers \Drupal\jaraba_crm\Service\OpportunityService
   * @group jaraba_crm
   */
  class OpportunityServiceTest extends UnitTestCase {

    /**
     * El entity type manager mockeado.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * El storage mockeado para la entidad opportunity.
     */
    protected EntityStorageInterface $storage;

    /**
     * El servicio bajo prueba.
     */
    protected OpportunityService $service;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void {
      parent::setUp();

      $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
      $this->storage = $this->createMock(EntityStorageInterface::class);

      $this->entityTypeManager->method('getStorage')
        ->with('crm_opportunity')
        ->willReturn($this->storage);

      $this->service = new OpportunityService(
        $this->entityTypeManager,
      );
    }

    /**
     * Tests create() crea una entidad oportunidad con los valores correctos.
     */
    public function testCreateReturnsOpportunityEntity(): void {
      $values = [
        'title' => 'Venta de licencias',
        'company_id' => 5,
        'stage' => 'qualification',
        'value' => 15000.00,
        'tenant_id' => 1,
      ];

      $entity = $this->createMock(Opportunity::class);
      $entity->expects($this->once())
        ->method('save');

      $this->storage->expects($this->once())
        ->method('create')
        ->with($this->callback(function (array $v) use ($values): bool {
          return $v['title'] === 'Venta de licencias'
            && $v['stage'] === 'qualification'
            && $v['value'] === 15000.00;
        }))
        ->willReturn($entity);

      $result = $this->service->create($values);

      $this->assertSame($entity, $result);
    }

    /**
     * Tests load() devuelve la entidad cuando existe.
     */
    public function testLoadReturnsEntityWhenFound(): void {
      $entity = $this->createMock(Opportunity::class);

      $this->storage->expects($this->once())
        ->method('load')
        ->with(12)
        ->willReturn($entity);

      $result = $this->service->load(12);

      $this->assertSame($entity, $result);
    }

    /**
     * Tests load() devuelve NULL cuando la oportunidad no existe.
     */
    public function testLoadReturnsNullWhenNotFound(): void {
      $this->storage->expects($this->once())
        ->method('load')
        ->with(999)
        ->willReturn(NULL);

      $result = $this->service->load(999);

      $this->assertNull($result);
    }

    /**
     * Tests moveToStage() actualiza el stage de la oportunidad.
     */
    public function testMoveToStageUpdatesStage(): void {
      $entity = $this->createMock(Opportunity::class);
      $entity->expects($this->once())
        ->method('set')
        ->with('stage', 'negotiation');
      $entity->expects($this->once())
        ->method('save');

      $this->storage->expects($this->once())
        ->method('load')
        ->with(10)
        ->willReturn($entity);

      $result = $this->service->moveToStage(10, 'negotiation');

      $this->assertTrue($result);
    }

    /**
     * Tests moveToStage() devuelve FALSE cuando la oportunidad no existe.
     */
    public function testMoveToStageReturnsFalseWhenNotFound(): void {
      $this->storage->expects($this->once())
        ->method('load')
        ->with(999)
        ->willReturn(NULL);

      $result = $this->service->moveToStage(999, 'closed_won');

      $this->assertFalse($result);
    }

    /**
     * Tests getByStage() devuelve oportunidades agrupadas por stage.
     */
    public function testGetByStageReturnsGroupedOpportunities(): void {
      $entity1 = $this->createMock(Opportunity::class);
      $entity1->method('get')
        ->willReturnCallback(function (string $field) {
          if ($field === 'stage') {
            return (object) ['value' => 'qualification'];
          }
          return (object) ['value' => NULL];
        });

      $entity2 = $this->createMock(Opportunity::class);
      $entity2->method('get')
        ->willReturnCallback(function (string $field) {
          if ($field === 'stage') {
            return (object) ['value' => 'negotiation'];
          }
          return (object) ['value' => NULL];
        });

      $query = $this->createMock(QueryInterface::class);
      $query->method('accessCheck')->willReturnSelf();
      $query->method('condition')->willReturnSelf();
      $query->method('sort')->willReturnSelf();
      $query->method('execute')->willReturn([1 => 1, 2 => 2]);

      $this->storage->method('getQuery')
        ->willReturn($query);
      $this->storage->method('loadMultiple')
        ->with([1 => 1, 2 => 2])
        ->willReturn([$entity1, $entity2]);

      $result = $this->service->getByStage();

      $this->assertIsArray($result);
      $this->assertArrayHasKey('qualification', $result);
      $this->assertArrayHasKey('negotiation', $result);
      $this->assertCount(1, $result['qualification']);
      $this->assertCount(1, $result['negotiation']);
    }

    /**
     * Tests count() devuelve el numero total de oportunidades.
     */
    public function testCountReturnsTotalOpportunities(): void {
      $query = $this->createMock(QueryInterface::class);
      $query->method('accessCheck')->willReturnSelf();
      $query->method('condition')->willReturnSelf();
      $query->method('count')->willReturnSelf();
      $query->method('execute')->willReturn(28);

      $this->storage->method('getQuery')
        ->willReturn($query);

      $result = $this->service->count();

      $this->assertSame(28, $result);
    }

  }

}
