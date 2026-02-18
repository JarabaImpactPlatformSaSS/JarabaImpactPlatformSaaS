<?php

declare(strict_types=1);

namespace Drupal\Tests\{{machine_name}}\Unit;

use Drupal\{{machine_name}}\Plugin\Connector\{{class_name}}Connector;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for the {{name}} connector.
 *
 * @group {{machine_name}}
 * @coversDefaultClass \Drupal\{{machine_name}}\Plugin\Connector\{{class_name}}Connector
 */
class {{class_name}}ConnectorTest extends UnitTestCase {

  /**
   * The connector under test.
   */
  protected {{class_name}}Connector $connector;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->connector = new {{class_name}}Connector();
  }

  /**
   * Tests that install returns TRUE.
   *
   * @covers ::install
   */
  public function testInstallReturnsTrue(): void {
    $this->assertTrue($this->connector->install());
  }

  /**
   * Tests that configure accepts settings.
   *
   * @covers ::configure
   */
  public function testConfigureAcceptsSettings(): void {
    $this->assertTrue($this->connector->configure(['api_key' => 'test-key']));
  }

  /**
   * Tests that getManifest returns valid structure.
   *
   * @covers ::getManifest
   */
  public function testGetManifestReturnsValidStructure(): void {
    $manifest = $this->connector->getManifest();
    $this->assertArrayHasKey('machine_name', $manifest);
    $this->assertArrayHasKey('display_name', $manifest);
    $this->assertArrayHasKey('version', $manifest);
    $this->assertArrayHasKey('category', $manifest);
    $this->assertEquals('{{machine_name}}', $manifest['machine_name']);
  }

  /**
   * Tests that test self-check passes.
   *
   * @covers ::test
   */
  public function testSelfCheckPasses(): void {
    $this->assertTrue($this->connector->test());
  }

  /**
   * Tests that getStatus returns active.
   *
   * @covers ::getStatus
   */
  public function testGetStatusReturnsActive(): void {
    $this->assertEquals('active', $this->connector->getStatus());
  }

}
