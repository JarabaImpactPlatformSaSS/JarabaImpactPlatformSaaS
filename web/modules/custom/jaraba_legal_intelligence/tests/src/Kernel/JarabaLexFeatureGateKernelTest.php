<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_intelligence\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Kernel tests for JarabaLex module installation and feature gate readiness.
 *
 * Verifies that the module installs correctly, configuration is seeded,
 * and database tables are created as expected.
 *
 * @group jaraba_legal_intelligence
 */
class JarabaLexFeatureGateKernelTest extends KernelTestBase {

  /**
   * Modules required for this test.
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
    'taxonomy',
    'jaraba_legal_intelligence',
  ];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);
    $container->register('ai.provider')->setSynthetic(TRUE);
    $container->register('ecosistema_jaraba_core.tenant_context')->setSynthetic(TRUE);
    $container->register('ecosistema_jaraba_core.jarabalex_feature_gate')->setSynthetic(TRUE);
    $container->register('jaraba_ai_agents.tenant_brand_voice')->setSynthetic(TRUE);
    $container->register('jaraba_ai_agents.observability')->setSynthetic(TRUE);
    $container->register('ecosistema_jaraba_core.unified_prompt_builder')->setSynthetic(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container->set('ai.provider', new \stdClass());
    $this->container->set(
      'ecosistema_jaraba_core.tenant_context',
      $this->getMockBuilder('Drupal\ecosistema_jaraba_core\Service\TenantContextService')
        ->disableOriginalConstructor()
        ->getMock()
    );
    $this->container->set(
      'ecosistema_jaraba_core.jarabalex_feature_gate',
      $this->getMockBuilder('Drupal\ecosistema_jaraba_core\Service\JarabaLexFeatureGateService')
        ->disableOriginalConstructor()
        ->getMock()
    );
    $this->container->set('jaraba_ai_agents.tenant_brand_voice', new \stdClass());
    $this->container->set('jaraba_ai_agents.observability', new \stdClass());
    $this->container->set('ecosistema_jaraba_core.unified_prompt_builder', new \stdClass());

    $this->installEntitySchema('user');
    $this->installConfig(['jaraba_legal_intelligence']);
  }

  /**
   * Tests that the module installs without errors.
   */
  public function testModuleInstallation(): void {
    $moduleHandler = $this->container->get('module_handler');
    $this->assertTrue(
      $moduleHandler->moduleExists('jaraba_legal_intelligence'),
      'The jaraba_legal_intelligence module should be installed.'
    );
  }

  /**
   * Tests that the jaraba_legal_intelligence.settings config is installed.
   */
  public function testConfigInstalled(): void {
    $config = $this->config('jaraba_legal_intelligence.settings');
    $this->assertNotNull($config, 'Settings config must exist.');

    // Verify key configuration values from the installed YAML.
    $this->assertNotEmpty(
      $config->get('qdrant_url'),
      'qdrant_url should be set in the installed configuration.'
    );
    $this->assertNotEmpty(
      $config->get('score_threshold'),
      'score_threshold should be set in the installed configuration.'
    );
    $this->assertNotEmpty(
      $config->get('max_results'),
      'max_results should be set in the installed configuration.'
    );
  }

  /**
   * Tests that the legal_citation_graph table schema is declared.
   */
  public function testSchemaDeclaresLegalCitationGraph(): void {
    // Load the .install file which declares hook_schema().
    $this->container->get('module_handler')->loadInclude('jaraba_legal_intelligence', 'install');
    $schema = jaraba_legal_intelligence_schema();
    $this->assertArrayHasKey('legal_citation_graph', $schema, 'hook_schema() must declare legal_citation_graph table.');
    $this->assertArrayHasKey('fields', $schema['legal_citation_graph']);
    $this->assertArrayHasKey('source_resolution_id', $schema['legal_citation_graph']['fields']);
    $this->assertArrayHasKey('target_resolution_id', $schema['legal_citation_graph']['fields']);
    $this->assertArrayHasKey('relation_type', $schema['legal_citation_graph']['fields']);
  }

}
