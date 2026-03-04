<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Kernel\Entity;

use Drupal\ecosistema_jaraba_core\Entity\FairUsePolicy;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests FairUsePolicy ConfigEntity CRUD and schema.
 *
 * @group ecosistema_jaraba_core
 */
class FairUsePolicyTest extends KernelTestBase {

  /**
   * KERNEL-TEST-DEPS-001: List ALL required modules.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'options',
    'flexible_permissions',
    'group',
    'ecosistema_jaraba_core',
  ];

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
  }

  /**
   * Tests that FairUsePolicy entity type exists.
   */
  public function testEntityTypeExists(): void {
    $this->assertTrue(
      \Drupal::entityTypeManager()->hasDefinition('fair_use_policy')
    );
  }

  /**
   * Tests CRUD operations for FairUsePolicy.
   */
  public function testCrudOperations(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('fair_use_policy');

    // Create.
    $policy = $storage->create([
      'id' => 'test_policy',
      'label' => 'Test Policy',
      'tier' => 'professional',
      'warning_thresholds' => [70, 85, 95],
      'enforcement_actions' => [
        '_default' => [
          'warning' => 'warn',
          'critical' => 'throttle',
          'exceeded' => 'soft_block',
        ],
      ],
      'overage_unit_prices' => [
        'ai_tokens' => 0.00002,
        'api_calls' => 0.0001,
      ],
      'burst_tolerance_pct' => 5,
      'grace_period_hours' => 12,
      'description' => 'Test policy for professional tier.',
    ]);
    $this->assertInstanceOf(FairUsePolicy::class, $policy);
    $policy->save();

    // Read.
    $loaded = $storage->load('test_policy');
    $this->assertNotNull($loaded);
    $this->assertInstanceOf(FairUsePolicy::class, $loaded);
    $this->assertEquals('Test Policy', $loaded->label());
    $this->assertEquals('professional', $loaded->getTier());
    $this->assertEquals([70, 85, 95], $loaded->getWarningThresholds());
    $this->assertEquals(5, $loaded->getBurstTolerancePct());
    $this->assertEquals(12, $loaded->getGracePeriodHours());
    $this->assertEquals('Test policy for professional tier.', $loaded->getDescription());

    // Enforcement actions.
    $this->assertEquals('warn', $loaded->getEnforcementAction('_default', 'warning'));
    $this->assertEquals('throttle', $loaded->getEnforcementAction('_default', 'critical'));
    $this->assertEquals('soft_block', $loaded->getEnforcementAction('_default', 'exceeded'));

    // Overage prices.
    $this->assertEquals(0.00002, $loaded->getOverageUnitPrice('ai_tokens'));
    $this->assertEquals(0.0001, $loaded->getOverageUnitPrice('api_calls'));
    $this->assertEquals(0.0, $loaded->getOverageUnitPrice('nonexistent'));

    // Update.
    $loaded->setBurstTolerancePct(10);
    $loaded->setGracePeriodHours(24);
    $loaded->save();

    $reloaded = $storage->load('test_policy');
    $this->assertEquals(10, $reloaded->getBurstTolerancePct());
    $this->assertEquals(24, $reloaded->getGracePeriodHours());

    // Delete.
    $reloaded->delete();
    $this->assertNull($storage->load('test_policy'));
  }

  /**
   * Tests default values on new entity.
   */
  public function testDefaultValues(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('fair_use_policy');
    $policy = $storage->create([
      'id' => 'defaults_test',
      'label' => 'Defaults Test',
    ]);

    $this->assertEquals('_global', $policy->getTier());
    $this->assertEquals(0, $policy->getBurstTolerancePct());
    $this->assertEquals(6, $policy->getGracePeriodHours());
    $this->assertEquals('', $policy->getDescription());
    $this->assertEmpty($policy->getWarningThresholds());
    $this->assertEmpty($policy->getEnforcementActions());
    $this->assertEmpty($policy->getOverageUnitPrices());
  }

  /**
   * Tests enforcement action fallback to _default.
   */
  public function testEnforcementActionFallback(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('fair_use_policy');
    $policy = $storage->create([
      'id' => 'fallback_test',
      'label' => 'Fallback Test',
      'enforcement_actions' => [
        '_default' => [
          'warning' => 'warn',
          'critical' => 'throttle',
          'exceeded' => 'hard_block',
        ],
      ],
    ]);
    $policy->save();

    $loaded = $storage->load('fallback_test');

    // Resource not specified → falls back to _default.
    $this->assertEquals('warn', $loaded->getEnforcementAction('ai_queries', 'warning'));
    $this->assertEquals('throttle', $loaded->getEnforcementAction('ai_queries', 'critical'));
    $this->assertEquals('hard_block', $loaded->getEnforcementAction('ai_queries', 'exceeded'));

    // Unknown level → falls back to 'warn'.
    $this->assertEquals('warn', $loaded->getEnforcementAction('ai_queries', 'nonexistent'));
  }

  /**
   * Tests that thresholds are returned sorted.
   */
  public function testThresholdsSorted(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('fair_use_policy');
    $policy = $storage->create([
      'id' => 'sort_test',
      'label' => 'Sort Test',
      'warning_thresholds' => [95, 70, 85],
    ]);
    $policy->save();

    $loaded = $storage->load('sort_test');
    $this->assertEquals([70, 85, 95], $loaded->getWarningThresholds());
  }

  /**
   * Tests config export keys.
   */
  public function testConfigExport(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('fair_use_policy');
    $policy = $storage->create([
      'id' => 'export_test',
      'label' => 'Export Test',
      'tier' => 'enterprise',
      'warning_thresholds' => [70, 85, 95],
      'burst_tolerance_pct' => 15,
      'grace_period_hours' => 48,
    ]);
    $policy->save();

    // Verify config object.
    $config = \Drupal::config('ecosistema_jaraba_core.fair_use_policy.export_test');
    $this->assertFalse($config->isNew());
    $this->assertEquals('Export Test', $config->get('label'));
    $this->assertEquals('enterprise', $config->get('tier'));
    $this->assertEquals([70, 85, 95], $config->get('warning_thresholds'));
    $this->assertEquals(15, $config->get('burst_tolerance_pct'));
    $this->assertEquals(48, $config->get('grace_period_hours'));
  }

}
