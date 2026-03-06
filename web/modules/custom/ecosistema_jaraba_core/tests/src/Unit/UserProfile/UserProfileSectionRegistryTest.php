<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\UserProfile;

use Drupal\ecosistema_jaraba_core\UserProfile\UserProfileSectionInterface;
use Drupal\ecosistema_jaraba_core\UserProfile\UserProfileSectionRegistry;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for UserProfileSectionRegistry.
 *
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\UserProfile\UserProfileSectionRegistry
 * @group ecosistema_jaraba_core
 */
class UserProfileSectionRegistryTest extends UnitTestCase {

  protected UserProfileSectionRegistry $registry;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->registry = new UserProfileSectionRegistry();
  }

  /**
   * @covers ::addSection
   * @covers ::getAllSections
   */
  public function testAddSection(): void {
    $section = $this->createSectionMock('test_section', 10, TRUE);
    $this->registry->addSection($section);

    $all = $this->registry->getAllSections();
    $this->assertCount(1, $all);
    $this->assertSame('test_section', $all['test_section']->getId());
  }

  /**
   * @covers ::getSection
   */
  public function testGetSectionReturnsNullForMissing(): void {
    $this->assertNull($this->registry->getSection('nonexistent'));
  }

  /**
   * @covers ::getSection
   */
  public function testGetSectionReturnsSection(): void {
    $section = $this->createSectionMock('my_section', 10, TRUE);
    $this->registry->addSection($section);

    $found = $this->registry->getSection('my_section');
    $this->assertNotNull($found);
    $this->assertSame('my_section', $found->getId());
  }

  /**
   * @covers ::getApplicableSections
   */
  public function testGetApplicableSectionsFilters(): void {
    $applicable = $this->createSectionMock('visible', 10, TRUE);
    $notApplicable = $this->createSectionMock('hidden', 20, FALSE);

    $this->registry->addSection($applicable);
    $this->registry->addSection($notApplicable);

    $result = $this->registry->getApplicableSections(1);
    $this->assertCount(1, $result);
    $this->assertSame('visible', $result[0]->getId());
  }

  /**
   * @covers ::getApplicableSections
   */
  public function testGetApplicableSectionsSortsByWeight(): void {
    $heavy = $this->createSectionMock('heavy', 50, TRUE);
    $light = $this->createSectionMock('light', 5, TRUE);
    $medium = $this->createSectionMock('medium', 20, TRUE);

    $this->registry->addSection($heavy);
    $this->registry->addSection($light);
    $this->registry->addSection($medium);

    $result = $this->registry->getApplicableSections(1);
    $this->assertCount(3, $result);
    $this->assertSame('light', $result[0]->getId());
    $this->assertSame('medium', $result[1]->getId());
    $this->assertSame('heavy', $result[2]->getId());
  }

  /**
   * @covers ::buildSectionsArray
   */
  public function testBuildSectionsArrayProducesCorrectFormat(): void {
    $section = $this->createSectionMock('test', 10, TRUE, [
      [
        'label' => 'Link 1',
        'url' => '/test',
        'icon_category' => 'ui',
        'icon_name' => 'arrow',
        'color' => 'primary',
        'description' => 'Desc',
        'slide_panel' => FALSE,
        'slide_panel_title' => 'Link 1',
        'cross_vertical' => FALSE,
      ],
    ]);
    $this->registry->addSection($section);

    $result = $this->registry->buildSectionsArray(1);
    $this->assertCount(1, $result);

    $entry = $result[0];
    $this->assertSame('test', $entry['id']);
    $this->assertSame('Test Title', $entry['title']);
    $this->assertSame('Test Subtitle', $entry['subtitle']);
    $this->assertSame('ui', $entry['icon_category']);
    $this->assertSame('info', $entry['icon_name']);
    $this->assertSame('primary', $entry['color']);
    $this->assertCount(1, $entry['links']);
    $this->assertSame('Link 1', $entry['links'][0]['label']);
  }

  /**
   * @covers ::buildSectionsArray
   */
  public function testBuildSectionsArraySkipsEmptyLinks(): void {
    $section = $this->createSectionMock('empty', 10, TRUE, []);
    $this->registry->addSection($section);

    $result = $this->registry->buildSectionsArray(1);
    $this->assertCount(0, $result);
  }

  /**
   * @covers ::buildSectionsArray
   */
  public function testBuildSectionsArrayMergesExtraData(): void {
    $section = $this->createSectionMock('with_extra', 10, TRUE, [
      ['label' => 'L', 'url' => '/x', 'icon_category' => 'ui', 'icon_name' => 'a', 'color' => 'p', 'description' => '', 'slide_panel' => FALSE, 'slide_panel_title' => 'L', 'cross_vertical' => FALSE],
    ], ['profile_completeness' => ['percentage' => 75]]);
    $this->registry->addSection($section);

    $result = $this->registry->buildSectionsArray(1);
    $this->assertCount(1, $result);
    $this->assertArrayHasKey('profile_completeness', $result[0]);
    $this->assertSame(75, $result[0]['profile_completeness']['percentage']);
  }

  /**
   * Creates a section mock.
   */
  private function createSectionMock(string $id, int $weight, bool $applicable, array $links = [], array $extraData = []): UserProfileSectionInterface {
    $section = $this->createMock(UserProfileSectionInterface::class);
    $section->method('getId')->willReturn($id);
    $section->method('getWeight')->willReturn($weight);
    $section->method('isApplicable')->willReturn($applicable);
    $section->method('getTitle')->willReturn('Test Title');
    $section->method('getSubtitle')->willReturn('Test Subtitle');
    $section->method('getIcon')->willReturn(['category' => 'ui', 'name' => 'info']);
    $section->method('getColor')->willReturn('primary');
    $section->method('getLinks')->willReturn($links ?: [
      ['label' => 'Default', 'url' => '/d', 'icon_category' => 'ui', 'icon_name' => 'a', 'color' => 'p', 'description' => '', 'slide_panel' => FALSE, 'slide_panel_title' => 'D', 'cross_vertical' => FALSE],
    ]);
    $section->method('getExtraData')->willReturn($extraData);
    return $section;
  }

}
