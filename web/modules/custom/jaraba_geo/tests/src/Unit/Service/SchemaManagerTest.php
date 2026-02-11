<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_geo\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\jaraba_geo\Service\SchemaManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests for the SchemaManager service.
 *
 * @coversDefaultClass \Drupal\jaraba_geo\Service\SchemaManager
 * @group jaraba_geo
 */
class SchemaManagerTest extends TestCase
{

    /**
     * The SchemaManager service under test.
     */
    protected SchemaManager $schemaManager;

    /**
     * Mock entity type manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * Mock file URL generator.
     */
    protected FileUrlGeneratorInterface $fileUrlGenerator;

    /**
     * Mock request stack.
     */
    protected RequestStack $requestStack;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
        $this->fileUrlGenerator = $this->createMock(FileUrlGeneratorInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);

        $request = $this->createMock(Request::class);
        $request->method('getSchemeAndHttpHost')
            ->willReturn('https://jaraba-saas.lndo.site');
        $this->requestStack->method('getCurrentRequest')
            ->willReturn($request);

        $this->schemaManager = new SchemaManager(
            $this->entityTypeManager,
            $this->fileUrlGenerator,
            $this->requestStack,
        );
    }

    /**
     * Tests that buildWebSiteSchema contains required fields.
     *
     * @covers ::buildWebSiteSchema
     */
    public function testBuildWebSiteSchemaHasRequiredFields(): void
    {
        $schema = $this->schemaManager->buildWebSiteSchema();

        $this->assertArrayHasKey('@context', $schema);
        $this->assertArrayHasKey('@type', $schema);
        $this->assertArrayHasKey('name', $schema);
        $this->assertArrayHasKey('url', $schema);
    }

    /**
     * Tests that buildWebSiteSchema returns @type WebSite.
     *
     * @covers ::buildWebSiteSchema
     */
    public function testBuildWebSiteSchemaTypeIsWebSite(): void
    {
        $schema = $this->schemaManager->buildWebSiteSchema();

        $this->assertSame('WebSite', $schema['@type']);
    }

    /**
     * Tests that buildLocalBusinessSchema has required fields.
     *
     * @covers ::buildLocalBusinessSchema
     */
    public function testBuildLocalBusinessSchemaHasRequiredFields(): void
    {
        $producer = $this->createMock(ContentEntityInterface::class);
        $producer->method('label')->willReturn('Aceites García');

        $url = $this->createMock(\Drupal\Core\Url::class);
        $url->method('setAbsolute')->willReturnSelf();
        $url->method('toString')->willReturn('https://jaraba-saas.lndo.site/producer/1');
        $producer->method('toUrl')->willReturn($url);

        $producer->method('hasField')->willReturn(false);

        $schema = $this->schemaManager->buildLocalBusinessSchema($producer);

        $this->assertArrayHasKey('@context', $schema);
        $this->assertArrayHasKey('@type', $schema);
        $this->assertArrayHasKey('name', $schema);
        $this->assertSame('LocalBusiness', $schema['@type']);
        $this->assertSame('Aceites García', $schema['name']);
    }

    /**
     * Tests that buildHowToSchema has required fields.
     *
     * @covers ::buildHowToSchema
     */
    public function testBuildHowToSchemaHasRequiredFields(): void
    {
        $entity = $this->createMock(ContentEntityInterface::class);
        $entity->method('label')->willReturn('Cómo vender aceite online');
        $entity->method('hasField')->willReturn(false);

        $steps = ['Registrarse', 'Subir productos', 'Publicar'];

        $schema = $this->schemaManager->buildHowToSchema($entity, $steps);

        $this->assertArrayHasKey('@type', $schema);
        $this->assertSame('HowTo', $schema['@type']);
    }

    /**
     * Tests that buildBreadcrumbSchema returns BreadcrumbList type.
     *
     * @covers ::buildBreadcrumbSchema
     */
    public function testBuildBreadcrumbSchemaType(): void
    {
        $breadcrumbs = [
            ['name' => 'Inicio', 'url' => 'https://jaraba-saas.lndo.site'],
            ['name' => 'Productores', 'url' => 'https://jaraba-saas.lndo.site/productores'],
        ];

        $schema = $this->schemaManager->buildBreadcrumbSchema($breadcrumbs);

        $this->assertArrayHasKey('@type', $schema);
        $this->assertSame('BreadcrumbList', $schema['@type']);
    }

}
