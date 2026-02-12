<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_email\Unit\Service;

use Drupal\jaraba_email\Service\MjmlCompilerService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para MjmlCompilerService.
 *
 * @covers \Drupal\jaraba_email\Service\MjmlCompilerService
 * @group jaraba_email
 */
class MjmlCompilerServiceTest extends UnitTestCase {

  protected MjmlCompilerService $service;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new MjmlCompilerService(
      $this->logger,
    );
  }

  /**
   * Tests validar MJML válido retorna sin errores.
   */
  public function testValidateValidMjml(): void {
    $mjml = '<mjml><mj-body><mj-section><mj-column><mj-text>Hola</mj-text></mj-column></mj-section></mj-body></mjml>';

    $result = $this->service->validate($mjml);

    $this->assertTrue($result['valid']);
    $this->assertEmpty($result['errors']);
  }

  /**
   * Tests validar MJML sin tag raíz mjml.
   */
  public function testValidateMissingMjmlRootTag(): void {
    $mjml = '<mj-body><mj-section><mj-column><mj-text>Hola</mj-text></mj-column></mj-section></mj-body>';

    $result = $this->service->validate($mjml);

    $this->assertFalse($result['valid']);
    $this->assertContains('Falta el tag raíz <mjml>.', $result['errors']);
  }

  /**
   * Tests validar MJML sin tag mj-body.
   */
  public function testValidateMissingMjBody(): void {
    $mjml = '<mjml><mj-section><mj-column><mj-text>Hola</mj-text></mj-column></mj-section></mjml>';

    $result = $this->service->validate($mjml);

    $this->assertFalse($result['valid']);
    $this->assertContains('Falta el tag <mj-body>.', $result['errors']);
  }

  /**
   * Tests validar MJML con tags desbalanceados.
   */
  public function testValidateUnbalancedTags(): void {
    $mjml = '<mjml><mj-body><mj-section><mj-column><mj-text>Hola</mj-text></mj-column></mj-body></mjml>';

    $result = $this->service->validate($mjml);

    $this->assertFalse($result['valid']);
    $this->assertContains('Se detectaron tags MJML desbalanceados.', $result['errors']);
  }

  /**
   * Tests compilar usa fallback cuando binario no disponible.
   */
  public function testCompileFallbackReturnsHtml(): void {
    $mjml = '<mjml><mj-body><mj-section><mj-column><mj-text>Contenido</mj-text></mj-column></mj-section></mj-body></mjml>';

    // En entorno de test, el binario MJML no estará disponible,
    // por lo que se usará la conversión fallback.
    $result = $this->service->compile($mjml);

    // El fallback debe producir HTML con contenido.
    $this->assertStringContainsString('Contenido', $result);
    $this->assertStringContainsString('<!DOCTYPE html>', $result);
  }

}
