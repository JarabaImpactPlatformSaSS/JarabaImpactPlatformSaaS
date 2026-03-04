<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_pilot_manager\Kernel;

use Drupal\jaraba_pilot_manager\Entity\PilotFeedback;
use Drupal\jaraba_pilot_manager\Entity\PilotProgram;
use Drupal\jaraba_pilot_manager\Entity\PilotTenant;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Kernel tests for Pilot Manager entities.
 *
 * Verifies base field definitions, entity_keys, CRUD operations,
 * and interface compliance (ENTITY-001).
 *
 * @group jaraba_pilot_manager
 */
class PilotProgramEntityTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * KERNEL-TEST-DEPS-001: List ALL required modules explicitly.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'options',
    'datetime',
    'views',
    'flexible_permissions',
    'group',
    'ecosistema_jaraba_core',
    'jaraba_pilot_manager',
  ];

  /**
   * {@inheritdoc}
   *
   * KERNEL-SYNTH-001: Register synthetic services for unloaded modules.
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);
    $container->register('ecosistema_jaraba_core.tenant_context')->setSynthetic(TRUE);
    $container->register('jaraba_analytics.activation_tracking')->setSynthetic(TRUE);
    $container->register('jaraba_analytics.retention_calculator')->setSynthetic(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container->set(
      'ecosistema_jaraba_core.tenant_context',
      $this->getMockBuilder('Drupal\ecosistema_jaraba_core\Service\TenantContextService')
        ->disableOriginalConstructor()
        ->getMock()
    );
    $this->container->set('jaraba_analytics.activation_tracking', new \stdClass());
    $this->container->set('jaraba_analytics.retention_calculator', new \stdClass());

    $this->installEntitySchema('user');
    $this->installEntitySchema('pilot_program');
    $this->installEntitySchema('pilot_tenant');
    $this->installEntitySchema('pilot_feedback');
  }

  /**
   * Tests PilotProgram implements required interfaces (ENTITY-001).
   */
  public function testPilotProgramInterfaces(): void {
    $this->assertTrue(
      is_subclass_of(PilotProgram::class, 'Drupal\user\EntityOwnerInterface'),
      'PilotProgram must implement EntityOwnerInterface'
    );
    $this->assertTrue(
      is_subclass_of(PilotProgram::class, 'Drupal\Core\Entity\EntityChangedInterface'),
      'PilotProgram must implement EntityChangedInterface'
    );
  }

  /**
   * Tests PilotTenant implements required interfaces (ENTITY-001).
   */
  public function testPilotTenantInterfaces(): void {
    $this->assertTrue(
      is_subclass_of(PilotTenant::class, 'Drupal\user\EntityOwnerInterface'),
      'PilotTenant must implement EntityOwnerInterface'
    );
    $this->assertTrue(
      is_subclass_of(PilotTenant::class, 'Drupal\Core\Entity\EntityChangedInterface'),
      'PilotTenant must implement EntityChangedInterface'
    );
  }

  /**
   * Tests PilotFeedback implements required interfaces (ENTITY-001).
   */
  public function testPilotFeedbackInterfaces(): void {
    $this->assertTrue(
      is_subclass_of(PilotFeedback::class, 'Drupal\user\EntityOwnerInterface'),
      'PilotFeedback must implement EntityOwnerInterface'
    );
    $this->assertTrue(
      is_subclass_of(PilotFeedback::class, 'Drupal\Core\Entity\EntityChangedInterface'),
      'PilotFeedback must implement EntityChangedInterface'
    );
  }

  /**
   * Tests PilotProgram base field definitions.
   */
  public function testPilotProgramBaseFields(): void {
    $entityType = $this->container->get('entity_type.manager')
      ->getDefinition('pilot_program');
    $fields = PilotProgram::baseFieldDefinitions($entityType);

    $expectedFields = [
      'id', 'uuid', 'name', 'vertical', 'description',
      'start_date', 'end_date', 'max_tenants', 'target_plan',
      'success_criteria', 'status', 'conversion_rate', 'avg_nps',
      'total_enrolled', 'total_converted', 'assigned_csm', 'notes',
      'tenant_id', 'uid', 'created', 'changed',
    ];

    foreach ($expectedFields as $fieldName) {
      $this->assertArrayHasKey($fieldName, $fields, "Field '$fieldName' should exist in PilotProgram.");
    }

    // Verify entity_keys.
    $this->assertSame('id', $entityType->getKey('id'));
    $this->assertSame('uuid', $entityType->getKey('uuid'));
    $this->assertSame('name', $entityType->getKey('label'));
    $this->assertSame('uid', $entityType->getKey('owner'));
  }

  /**
   * Tests PilotTenant base field definitions.
   */
  public function testPilotTenantBaseFields(): void {
    $entityType = $this->container->get('entity_type.manager')
      ->getDefinition('pilot_tenant');
    $fields = PilotTenant::baseFieldDefinitions($entityType);

    $expectedFields = [
      'id', 'uuid', 'pilot_program', 'tenant_id', 'enrollment_date',
      'status', 'activation_score', 'retention_d30', 'engagement_score',
      'conversion_date', 'converted_plan', 'churn_risk', 'last_activity',
      'onboarding_completed', 'feedback_count', 'notes',
      'uid', 'created', 'changed',
    ];

    foreach ($expectedFields as $fieldName) {
      $this->assertArrayHasKey($fieldName, $fields, "Field '$fieldName' should exist in PilotTenant.");
    }
  }

  /**
   * Tests PilotFeedback base field definitions.
   */
  public function testPilotFeedbackBaseFields(): void {
    $entityType = $this->container->get('entity_type.manager')
      ->getDefinition('pilot_feedback');
    $fields = PilotFeedback::baseFieldDefinitions($entityType);

    $expectedFields = [
      'id', 'uuid', 'pilot_tenant', 'feedback_type', 'score',
      'comment', 'category', 'sentiment', 'response',
      'response_date', 'responded_by', 'is_public', 'tenant_id',
      'uid', 'created', 'changed',
    ];

    foreach ($expectedFields as $fieldName) {
      $this->assertArrayHasKey($fieldName, $fields, "Field '$fieldName' should exist in PilotFeedback.");
    }
  }

  /**
   * Tests PilotProgram CRUD operations.
   */
  public function testPilotProgramCrud(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('pilot_program');

    $program = $storage->create([
      'name' => 'Piloto Comercio Q1 2026',
      'vertical' => 'comercioconecta',
      'description' => 'Programa piloto para vertical de comercio.',
      'status' => 'draft',
      'max_tenants' => 25,
      'target_plan' => 'profesional',
    ]);
    $program->save();

    // Reload and verify.
    $loaded = $storage->load($program->id());
    $this->assertNotNull($loaded);
    $this->assertInstanceOf(PilotProgram::class, $loaded);
    $this->assertSame('Piloto Comercio Q1 2026', $loaded->get('name')->value);
    $this->assertSame('comercioconecta', $loaded->get('vertical')->value);
    $this->assertSame('draft', $loaded->get('status')->value);
    $this->assertSame(25, (int) $loaded->get('max_tenants')->value);
    $this->assertSame('profesional', $loaded->get('target_plan')->value);

    // Update.
    $loaded->set('status', 'active');
    $loaded->save();
    $reloaded = $storage->load($loaded->id());
    $this->assertSame('active', $reloaded->get('status')->value);

    // Delete.
    $id = $reloaded->id();
    $reloaded->delete();
    $this->assertNull($storage->load($id));
  }

  /**
   * Tests PilotTenant CRUD operations.
   */
  public function testPilotTenantCrud(): void {
    // Create program first.
    $programStorage = $this->container->get('entity_type.manager')
      ->getStorage('pilot_program');
    $program = $programStorage->create([
      'name' => 'Test Program',
      'vertical' => 'demo',
      'status' => 'active',
    ]);
    $program->save();

    $tenantStorage = $this->container->get('entity_type.manager')
      ->getStorage('pilot_tenant');
    $tenant = $tenantStorage->create([
      'pilot_program' => $program->id(),
      'status' => 'enrolled',
      'activation_score' => 75.5,
      'retention_d30' => 80.0,
      'engagement_score' => 60.0,
      'churn_risk' => 'low',
      'onboarding_completed' => TRUE,
    ]);
    $tenant->save();

    $loaded = $tenantStorage->load($tenant->id());
    $this->assertNotNull($loaded);
    $this->assertInstanceOf(PilotTenant::class, $loaded);
    $this->assertSame('enrolled', $loaded->get('status')->value);
    $this->assertEqualsWithDelta(75.5, (float) $loaded->get('activation_score')->value, 0.001);
    $this->assertTrue((bool) $loaded->get('onboarding_completed')->value);
    $this->assertSame('low', $loaded->get('churn_risk')->value);
  }

  /**
   * Tests PilotFeedback CRUD operations.
   */
  public function testPilotFeedbackCrud(): void {
    // Create program and tenant first.
    $programStorage = $this->container->get('entity_type.manager')
      ->getStorage('pilot_program');
    $program = $programStorage->create([
      'name' => 'Test Program',
      'vertical' => 'demo',
      'status' => 'active',
    ]);
    $program->save();

    $tenantStorage = $this->container->get('entity_type.manager')
      ->getStorage('pilot_tenant');
    $tenant = $tenantStorage->create([
      'pilot_program' => $program->id(),
      'status' => 'active',
    ]);
    $tenant->save();

    $feedbackStorage = $this->container->get('entity_type.manager')
      ->getStorage('pilot_feedback');
    $feedback = $feedbackStorage->create([
      'pilot_tenant' => $tenant->id(),
      'feedback_type' => 'nps',
      'score' => 9,
      'comment' => 'Excelente plataforma, muy intuitiva.',
      'sentiment' => 'positive',
      'is_public' => TRUE,
    ]);
    $feedback->save();

    $loaded = $feedbackStorage->load($feedback->id());
    $this->assertNotNull($loaded);
    $this->assertInstanceOf(PilotFeedback::class, $loaded);
    $this->assertSame('nps', $loaded->get('feedback_type')->value);
    $this->assertSame(9, (int) $loaded->get('score')->value);
    $this->assertSame('positive', $loaded->get('sentiment')->value);
    $this->assertTrue((bool) $loaded->get('is_public')->value);
  }

}
