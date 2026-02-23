<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Kernel;

use Drupal\ecosistema_jaraba_core\Entity\SaasPlanFeatures;
use Drupal\ecosistema_jaraba_core\Entity\SaasPlanTier;
use Drupal\ecosistema_jaraba_core\Service\PlanResolverService;
use Drupal\KernelTests\KernelTestBase;

/**
 * Contract tests for SaasPlanTier and SaasPlanFeatures ConfigEntities.
 *
 * Verifies that the configurable pricing architecture works as expected:
 * - Tier CRUD + alias resolution
 * - Features cascade (specific → default → NULL)
 * - Limit checks
 * - PlanResolverService integration
 *
 * @group plan-config-contract
 * @group ecosistema_jaraba_core
 */
class PlanConfigContractTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'options',
    'datetime',
    'file',
    'ecosistema_jaraba_core',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
  }

  /**
   * Test 1: SaasPlanTier entity can be created and loaded.
   */
  public function testTierCrud(): void {
    $tier = SaasPlanTier::create([
      'id' => 'test_starter',
      'label' => 'Test Starter',
      'tier_key' => 'starter',
      'aliases' => ['basico', 'free', 'basic'],
      'stripe_price_monthly' => 'price_test_monthly',
      'stripe_price_yearly' => 'price_test_yearly',
      'description' => 'Test tier',
      'weight' => 0,
    ]);
    $tier->save();

    $loaded = SaasPlanTier::load('test_starter');
    $this->assertNotNull($loaded);
    $this->assertEquals('starter', $loaded->getTierKey());
    $this->assertEquals(['basico', 'free', 'basic'], $loaded->getAliases());
    $this->assertEquals('price_test_monthly', $loaded->getStripePriceMonthly());
    $this->assertEquals('price_test_yearly', $loaded->getStripePriceYearly());
    $this->assertEquals(0, $loaded->getWeight());
  }

  /**
   * Test 2: SaasPlanFeatures entity can be created with features and limits.
   */
  public function testFeaturesCrud(): void {
    $features = SaasPlanFeatures::create([
      'id' => 'test_vertical_starter',
      'label' => 'Test Vertical Starter',
      'vertical' => 'test_vertical',
      'tier' => 'starter',
      'features' => ['seo_advanced', 'analytics'],
      'limits' => [
        'max_pages' => 5,
        'storage_gb' => 1,
      ],
      'description' => 'Test features config',
    ]);
    $features->save();

    $loaded = SaasPlanFeatures::load('test_vertical_starter');
    $this->assertNotNull($loaded);
    $this->assertEquals('test_vertical', $loaded->getVertical());
    $this->assertEquals('starter', $loaded->getTier());
    $this->assertCount(2, $loaded->getFeatures());
    $this->assertTrue($loaded->hasFeature('seo_advanced'));
    $this->assertFalse($loaded->hasFeature('ab_testing'));
    $this->assertEquals(5, $loaded->getLimit('max_pages'));
    $this->assertEquals(0, $loaded->getLimit('nonexistent_key'));
    $this->assertEquals(99, $loaded->getLimit('nonexistent_key', 99));
  }

  /**
   * Test 3: PlanResolverService normalizes plan names via aliases.
   */
  public function testNormalizePlanName(): void {
    SaasPlanTier::create([
      'id' => 'starter',
      'label' => 'Starter',
      'tier_key' => 'starter',
      'aliases' => ['basico', 'free', 'basic', 'gratis'],
      'weight' => 0,
    ])->save();

    SaasPlanTier::create([
      'id' => 'professional',
      'label' => 'Professional',
      'tier_key' => 'professional',
      'aliases' => ['pro', 'profesional', 'premium'],
      'weight' => 10,
    ])->save();

    /** @var \Drupal\ecosistema_jaraba_core\Service\PlanResolverService $resolver */
    $resolver = \Drupal::service('ecosistema_jaraba_core.plan_resolver');

    // Direct match.
    $this->assertEquals('starter', $resolver->normalize('starter'));
    $this->assertEquals('professional', $resolver->normalize('professional'));

    // Alias resolution.
    $this->assertEquals('starter', $resolver->normalize('basico'));
    $this->assertEquals('starter', $resolver->normalize('FREE'));
    $this->assertEquals('professional', $resolver->normalize('Pro'));
    $this->assertEquals('professional', $resolver->normalize('PREMIUM'));

    // Unknown name returns lowercased.
    $this->assertEquals('unknown_plan', $resolver->normalize('unknown_plan'));

    // Empty returns starter.
    $this->assertEquals('starter', $resolver->normalize(''));
  }

  /**
   * Test 4: PlanResolverService cascade: specific → default → NULL.
   */
  public function testFeaturesCascade(): void {
    // Create default config.
    SaasPlanFeatures::create([
      'id' => '_default_starter',
      'label' => 'Default Starter',
      'vertical' => '_default',
      'tier' => 'starter',
      'features' => [],
      'limits' => ['max_pages' => 5, 'storage_gb' => 1],
    ])->save();

    // Create specific vertical config.
    SaasPlanFeatures::create([
      'id' => 'agroconecta_starter',
      'label' => 'AgroConecta Starter',
      'vertical' => 'agroconecta',
      'tier' => 'starter',
      'features' => ['marketplace'],
      'limits' => ['max_pages' => 3, 'products' => 10],
    ])->save();

    /** @var \Drupal\ecosistema_jaraba_core\Service\PlanResolverService $resolver */
    $resolver = \Drupal::service('ecosistema_jaraba_core.plan_resolver');

    // Specific vertical resolves to vertical-specific config.
    $agro = $resolver->getFeatures('agroconecta', 'starter');
    $this->assertNotNull($agro);
    $this->assertEquals('agroconecta', $agro->getVertical());
    $this->assertEquals(3, $agro->getLimit('max_pages'));

    // Unknown vertical falls back to _default.
    $unknown = $resolver->getFeatures('unknown_vertical', 'starter');
    $this->assertNotNull($unknown);
    $this->assertEquals('_default', $unknown->getVertical());
    $this->assertEquals(5, $unknown->getLimit('max_pages'));

    // Non-existent tier returns NULL.
    $noTier = $resolver->getFeatures('agroconecta', 'nonexistent_tier');
    $this->assertNull($noTier);
  }

  /**
   * Test 5: checkLimit returns correct values through cascade.
   */
  public function testCheckLimit(): void {
    SaasPlanFeatures::create([
      'id' => '_default_professional',
      'label' => 'Default Professional',
      'vertical' => '_default',
      'tier' => 'professional',
      'features' => ['seo_advanced', 'analytics'],
      'limits' => ['max_pages' => 25, 'storage_gb' => 10],
    ])->save();

    /** @var \Drupal\ecosistema_jaraba_core\Service\PlanResolverService $resolver */
    $resolver = \Drupal::service('ecosistema_jaraba_core.plan_resolver');

    // Existing limit.
    $this->assertEquals(25, $resolver->checkLimit('any_vertical', 'professional', 'max_pages'));

    // Non-existing limit key returns default.
    $this->assertEquals(0, $resolver->checkLimit('any_vertical', 'professional', 'nonexistent'));
    $this->assertEquals(42, $resolver->checkLimit('any_vertical', 'professional', 'nonexistent', 42));

    // Non-existing tier returns default.
    $this->assertEquals(99, $resolver->checkLimit('any_vertical', 'missing_tier', 'max_pages', 99));
  }

  /**
   * Test 6: hasFeature checks feature presence correctly.
   */
  public function testHasFeature(): void {
    SaasPlanFeatures::create([
      'id' => '_default_enterprise',
      'label' => 'Default Enterprise',
      'vertical' => '_default',
      'tier' => 'enterprise',
      'features' => ['seo_advanced', 'analytics', 'ab_testing', 'schema_org', 'api_access'],
      'limits' => ['max_pages' => -1],
    ])->save();

    /** @var \Drupal\ecosistema_jaraba_core\Service\PlanResolverService $resolver */
    $resolver = \Drupal::service('ecosistema_jaraba_core.plan_resolver');

    $this->assertTrue($resolver->hasFeature('any_vertical', 'enterprise', 'api_access'));
    $this->assertTrue($resolver->hasFeature('any_vertical', 'enterprise', 'schema_org'));
    $this->assertFalse($resolver->hasFeature('any_vertical', 'enterprise', 'nonexistent_feature'));

    // Non-existing tier returns FALSE.
    $this->assertFalse($resolver->hasFeature('any_vertical', 'missing_tier', 'api_access'));
  }

  /**
   * Test 7: resolveFromStripePriceId resolves tier from Stripe Price ID.
   */
  public function testResolveFromStripePriceId(): void {
    SaasPlanTier::create([
      'id' => 'starter',
      'label' => 'Starter',
      'tier_key' => 'starter',
      'aliases' => [],
      'stripe_price_monthly' => 'price_starter_monthly_123',
      'stripe_price_yearly' => 'price_starter_yearly_123',
      'weight' => 0,
    ])->save();

    SaasPlanTier::create([
      'id' => 'professional',
      'label' => 'Professional',
      'tier_key' => 'professional',
      'aliases' => [],
      'stripe_price_monthly' => 'price_pro_monthly_456',
      'stripe_price_yearly' => 'price_pro_yearly_456',
      'weight' => 10,
    ])->save();

    /** @var \Drupal\ecosistema_jaraba_core\Service\PlanResolverService $resolver */
    $resolver = \Drupal::service('ecosistema_jaraba_core.plan_resolver');

    // Monthly match.
    $this->assertEquals('starter', $resolver->resolveFromStripePriceId('price_starter_monthly_123'));

    // Yearly match.
    $this->assertEquals('professional', $resolver->resolveFromStripePriceId('price_pro_yearly_456'));

    // No match.
    $this->assertNull($resolver->resolveFromStripePriceId('price_unknown_999'));

    // Empty returns NULL.
    $this->assertNull($resolver->resolveFromStripePriceId(''));
  }

  /**
   * Test 8: getPlanCapabilities merges features and limits.
   */
  public function testGetPlanCapabilities(): void {
    SaasPlanFeatures::create([
      'id' => '_default_professional',
      'label' => 'Default Professional',
      'vertical' => '_default',
      'tier' => 'professional',
      'features' => ['seo_advanced', 'analytics', 'ab_testing'],
      'limits' => [
        'max_pages' => 25,
        'basic_templates' => 25,
        'premium_templates' => 8,
        'ab_test_limit' => 3,
      ],
    ])->save();

    /** @var \Drupal\ecosistema_jaraba_core\Service\PlanResolverService $resolver */
    $resolver = \Drupal::service('ecosistema_jaraba_core.plan_resolver');

    $capabilities = $resolver->getPlanCapabilities('any_vertical', 'professional');

    // Limits are present.
    $this->assertEquals(25, $capabilities['max_pages']);
    $this->assertEquals(8, $capabilities['premium_templates']);
    $this->assertEquals(3, $capabilities['ab_test_limit']);

    // Features are present as boolean TRUE.
    $this->assertTrue($capabilities['seo_advanced']);
    $this->assertTrue($capabilities['analytics']);
    $this->assertTrue($capabilities['ab_testing']);

    // Non-configured feature is not present.
    $this->assertArrayNotHasKey('schema_org', $capabilities);
  }

}
