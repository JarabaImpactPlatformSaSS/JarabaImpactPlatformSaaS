<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_analytics\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests ProductMetricSnapshot entity.
 *
 * @group jaraba_analytics
 */
class ProductMetricSnapshotEntityTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

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
    'datetime',
    'views',
    'file',
    'flexible_permissions',
    'group',
    'ecosistema_jaraba_core',
    'jaraba_pixels',
    'jaraba_analytics',
  ];

  /**
   * {@inheritdoc}
   *
   * KERNEL-SYNTH-001: Register synthetic services for unloaded modules.
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);
    $container->register('jaraba_ads.conversion_tracking')->setSynthetic(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->container->set('jaraba_ads.conversion_tracking', new \stdClass());
    $this->installEntitySchema('user');
  }

  /**
   * Tests that ProductMetricSnapshot base fields are defined correctly.
   */
  public function testBaseFieldDefinitions(): void {
    $fields = \Drupal::service('entity_field.manager')
      ->getBaseFieldDefinitions('product_metric_snapshot');

    $expectedFields = [
      'id', 'uuid', 'snapshot_date', 'vertical', 'tenant_id',
      'total_users', 'activated_users', 'activation_rate',
      'retained_d7', 'retained_d30', 'retention_d30_rate',
      'nps_score', 'nps_promoters', 'nps_detractors', 'nps_passives',
      'monthly_churn_rate', 'churned_users', 'kill_criteria_triggered',
      'created',
    ];

    foreach ($expectedFields as $fieldName) {
      $this->assertArrayHasKey(
        $fieldName,
        $fields,
        "Field {$fieldName} should exist on ProductMetricSnapshot."
      );
    }
  }

  /**
   * Tests field types.
   */
  public function testFieldTypes(): void {
    $fields = \Drupal::service('entity_field.manager')
      ->getBaseFieldDefinitions('product_metric_snapshot');

    $this->assertEquals('datetime', $fields['snapshot_date']->getType());
    $this->assertEquals('string', $fields['vertical']->getType());
    $this->assertEquals('entity_reference', $fields['tenant_id']->getType());
    $this->assertEquals('integer', $fields['total_users']->getType());
    $this->assertEquals('float', $fields['activation_rate']->getType());
    $this->assertEquals('float', $fields['nps_score']->getType());
    $this->assertEquals('boolean', $fields['kill_criteria_triggered']->getType());
  }

}
