<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_email\Unit\Service;

use Drupal\jaraba_ai_agents\Service\AgentOrchestrator;
use Drupal\jaraba_email\Service\EmailAIService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para EmailAIService.
 *
 * @covers \Drupal\jaraba_email\Service\EmailAIService
 * @group jaraba_email
 */
class EmailAIServiceTest extends UnitTestCase {

  protected EmailAIService $service;
  protected AgentOrchestrator $orchestrator;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->orchestrator = $this->createMock(AgentOrchestrator::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new EmailAIService(
      $this->orchestrator,
      $this->logger,
    );
  }

  /**
   * Tests generar subject lines exitosamente.
   */
  public function testGenerateSubjectLinesSuccess(): void {
    $this->orchestrator->method('execute')
      ->with('marketing', 'generate_email_subjects', $this->anything())
      ->willReturn([
        'success' => TRUE,
        'data' => [
          'subjects' => [
            'Descubre las novedades de esta semana',
            'No te pierdas estas ofertas exclusivas',
            'Tu resumen semanal ha llegado',
          ],
        ],
      ]);

    $result = $this->service->generateSubjectLines('ofertas semanales', [], 3);

    $this->assertTrue($result['success']);
    $this->assertCount(3, $result['subjects']);
  }

  /**
   * Tests generar subject lines con error del orquestador.
   */
  public function testGenerateSubjectLinesFailure(): void {
    $this->orchestrator->method('execute')
      ->willReturn([
        'success' => FALSE,
        'error' => 'Agent unavailable.',
      ]);

    $result = $this->service->generateSubjectLines('tema de prueba');

    $this->assertFalse($result['success']);
    $this->assertEquals('Agent unavailable.', $result['error']);
    $this->assertEmpty($result['subjects']);
  }

  /**
   * Tests generar subject lines con excepción del orquestador.
   */
  public function testGenerateSubjectLinesException(): void {
    $this->orchestrator->method('execute')
      ->willThrowException(new \Exception('Connection timeout'));

    $result = $this->service->generateSubjectLines('tema');

    $this->assertFalse($result['success']);
    $this->assertEquals('Connection timeout', $result['error']);
    $this->assertEmpty($result['subjects']);
  }

  /**
   * Tests personalizar contenido con datos de contacto.
   */
  public function testPersonalizeContentReplacesPlaceholders(): void {
    $content = 'Hola {{nombre}}, tu empresa {{empresa}} tiene nuevas oportunidades.';
    $contactData = [
      'name' => 'María',
      'company' => 'TechImpacto',
      'email' => 'maria@techimpacto.com',
    ];

    $result = $this->service->personalizeContent($content, $contactData);

    $this->assertStringContainsString('María', $result);
    $this->assertStringContainsString('TechImpacto', $result);
    $this->assertStringNotContainsString('{{nombre}}', $result);
    $this->assertStringNotContainsString('{{empresa}}', $result);
  }

  /**
   * Tests generar variantes A/B exitosamente.
   */
  public function testGenerateABVariantsSuccess(): void {
    $this->orchestrator->method('execute')
      ->with('marketing', 'generate_ab_variants', $this->anything())
      ->willReturn([
        'success' => TRUE,
        'data' => [
          'variants' => [
            'Variante A: Ofertas exclusivas para ti',
            'Variante B: Solo hoy: descuentos especiales',
          ],
        ],
      ]);

    $result = $this->service->generateABVariants('Ofertas de la semana', 2);

    $this->assertTrue($result['success']);
    $this->assertEquals('Ofertas de la semana', $result['original']);
    $this->assertCount(2, $result['variants']);
  }

}
