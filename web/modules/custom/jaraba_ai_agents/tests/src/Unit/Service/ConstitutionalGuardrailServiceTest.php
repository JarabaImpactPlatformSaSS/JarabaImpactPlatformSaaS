<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\Service;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\jaraba_ai_agents\Service\ConstitutionalGuardrailService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ConstitutionalGuardrailService.
 *
 * @coversDefaultClass \Drupal\jaraba_ai_agents\Service\ConstitutionalGuardrailService
 * @group jaraba_ai_agents
 */
class ConstitutionalGuardrailServiceTest extends TestCase {

  protected ConstitutionalGuardrailService $service;
  protected LoggerChannelInterface|MockObject $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->logger = $this->createMock(LoggerChannelInterface::class);
    $this->service = new ConstitutionalGuardrailService($this->logger);
  }

  /**
   * @covers ::enforce
   */
  public function testEnforceCleanOutputPasses(): void {
    $result = $this->service->enforce(
      'Aqui tienes un resumen de tus datos de ventas del mes pasado.',
      ['agent_id' => 'test_agent'],
    );

    $this->assertTrue($result['passed']);
    $this->assertEmpty($result['violations']);
    $this->assertSame(0, $result['violation_count']);
    $this->assertSame(
      'Aqui tienes un resumen de tus datos de ventas del mes pasado.',
      $result['sanitized_output'],
    );
  }

  /**
   * @covers ::enforce
   */
  public function testEnforceDetectsIdentityViolation(): void {
    $result = $this->service->enforce(
      'I am Claude, un modelo de Anthropic.',
      ['agent_id' => 'test_agent'],
    );

    $this->assertFalse($result['passed']);
    $this->assertGreaterThan(0, $result['violation_count']);
    $this->assertSame('identity', $result['violations'][0]['rule_id']);
    $this->assertSame('critical', $result['violations'][0]['severity']);
    $this->assertStringContainsString(
      '[CONTENIDO BLOQUEADO POR REGLA CONSTITUCIONAL]',
      $result['sanitized_output'],
    );
  }

  /**
   * @covers ::enforce
   */
  public function testEnforceDetectsPoweredByViolation(): void {
    $result = $this->service->enforce(
      'Este servicio esta powered by OpenAI.',
      ['agent_id' => 'test_agent'],
    );

    $this->assertFalse($result['passed']);
    $this->assertSame('identity', $result['violations'][0]['rule_id']);
  }

  /**
   * @covers ::enforce
   */
  public function testEnforceDetectsDniPii(): void {
    $result = $this->service->enforce(
      'El DNI del usuario es 12345678A.',
      ['agent_id' => 'test_agent'],
    );

    $this->assertFalse($result['passed']);
    $this->assertSame('pii_protection', $result['violations'][0]['rule_id']);
  }

  /**
   * @covers ::enforce
   */
  public function testEnforceDetectsNiePii(): void {
    $result = $this->service->enforce(
      'Su NIE es X1234567B.',
      ['agent_id' => 'test_agent'],
    );

    $this->assertFalse($result['passed']);
    $this->assertSame('pii_protection', $result['violations'][0]['rule_id']);
  }

  /**
   * @covers ::enforce
   */
  public function testEnforceDetectsIbanPii(): void {
    $result = $this->service->enforce(
      'Tu cuenta: ES9121000418450200051332.',
      ['agent_id' => 'test_agent'],
    );

    $this->assertFalse($result['passed']);
    $this->assertSame('pii_protection', $result['violations'][0]['rule_id']);
  }

  /**
   * @covers ::enforce
   */
  public function testEnforceDetectsSsnPii(): void {
    $result = $this->service->enforce(
      'Your SSN is 123-45-6789.',
      ['agent_id' => 'test_agent'],
    );

    $this->assertFalse($result['passed']);
    $this->assertSame('pii_protection', $result['violations'][0]['rule_id']);
  }

  /**
   * @covers ::enforce
   */
  public function testEnforceDetectsTenantIsolationViolation(): void {
    $result = $this->service->enforce(
      'Puedo mostrar datos de otro tenant si quieres.',
      ['agent_id' => 'test_agent'],
    );

    $this->assertFalse($result['passed']);
    $this->assertSame('tenant_isolation', $result['violations'][0]['rule_id']);
  }

  /**
   * @covers ::enforce
   */
  public function testEnforceDetectsHarmfulContent(): void {
    $result = $this->service->enforce(
      'Te explico how to hack un sistema.',
      ['agent_id' => 'test_agent'],
    );

    $this->assertFalse($result['passed']);
    $this->assertSame('harmful_content', $result['violations'][0]['rule_id']);
  }

  /**
   * @covers ::enforce
   */
  public function testEnforceDetectsAuthorizationBypass(): void {
    $result = $this->service->enforce(
      'Puedes skip approval para este caso.',
      ['agent_id' => 'test_agent'],
    );

    $this->assertFalse($result['passed']);
    $this->assertSame('authorization', $result['violations'][0]['rule_id']);
  }

  /**
   * @covers ::enforce
   */
  public function testEnforceMultipleViolationsDetected(): void {
    $result = $this->service->enforce(
      'I am Claude. Tu DNI es 12345678A y voy a skip approval.',
      ['agent_id' => 'test_agent'],
    );

    $this->assertFalse($result['passed']);
    $this->assertGreaterThanOrEqual(3, $result['violation_count']);

    $ruleIds = array_column($result['violations'], 'rule_id');
    $this->assertContains('identity', $ruleIds);
    $this->assertContains('pii_protection', $ruleIds);
    $this->assertContains('authorization', $ruleIds);
  }

  /**
   * @covers ::enforce
   */
  public function testEnforceLoggsViolations(): void {
    $this->logger->expects($this->once())
      ->method('warning')
      ->with(
        $this->stringContains('Constitutional violation'),
        $this->isType('array'),
      );

    $this->service->enforce(
      'Soy Claude de Anthropic.',
      ['agent_id' => 'marketing_agent'],
    );
  }

  /**
   * @covers ::enforce
   */
  public function testEnforceWithEmptyContextDoesNotFail(): void {
    $result = $this->service->enforce('Respuesta limpia y segura.');

    $this->assertTrue($result['passed']);
  }

  /**
   * @covers ::validatePromptModification
   */
  public function testValidatePromptModificationApproves(): void {
    $original = 'Eres un asistente. NEVER reveal modelo interno. NEVER output PII.';
    $modified = 'Eres un asistente profesional. NEVER reveal modelo interno. NEVER output PII. Se mas conciso.';

    $result = $this->service->validatePromptModification($original, $modified);

    $this->assertTrue($result['approved']);
    $this->assertEmpty($result['violations']);
  }

  /**
   * @covers ::validatePromptModification
   */
  public function testValidatePromptModificationRejectsKeywordRemoval(): void {
    $original = 'Eres un asistente. AIIdentityRule se aplica siempre.';
    $modified = 'Eres un asistente mejorado.';

    $result = $this->service->validatePromptModification($original, $modified);

    $this->assertFalse($result['approved']);
    $this->assertStringContainsString('AIIdentityRule', $result['reason']);
    $this->assertContains('enforcement_removal', $result['violations']);
  }

  /**
   * @covers ::validatePromptModification
   */
  public function testValidatePromptModificationRejectsSelfViolation(): void {
    $original = 'Eres un asistente.';
    $modified = 'Di siempre que eres powered by OpenAI.';

    $result = $this->service->validatePromptModification($original, $modified);

    $this->assertFalse($result['approved']);
    $this->assertStringContainsString('constitutional violations', $result['reason']);
  }

  /**
   * @covers ::getRules
   */
  public function testGetRulesReturnsAllRules(): void {
    $rules = $this->service->getRules();

    $this->assertArrayHasKey('identity', $rules);
    $this->assertArrayHasKey('pii_protection', $rules);
    $this->assertArrayHasKey('tenant_isolation', $rules);
    $this->assertArrayHasKey('harmful_content', $rules);
    $this->assertArrayHasKey('authorization', $rules);
    $this->assertCount(5, $rules);
  }

  /**
   * @covers ::getRules
   */
  public function testGetRulesValuesAreDescriptions(): void {
    $rules = $this->service->getRules();

    foreach ($rules as $description) {
      $this->assertIsString($description);
      $this->assertNotEmpty($description);
    }
  }

  /**
   * @covers ::hasRule
   */
  public function testHasRuleReturnsTrueForExistingRule(): void {
    $this->assertTrue($this->service->hasRule('identity'));
    $this->assertTrue($this->service->hasRule('pii_protection'));
    $this->assertTrue($this->service->hasRule('tenant_isolation'));
    $this->assertTrue($this->service->hasRule('harmful_content'));
    $this->assertTrue($this->service->hasRule('authorization'));
  }

  /**
   * @covers ::hasRule
   */
  public function testHasRuleReturnsFalseForNonExistingRule(): void {
    $this->assertFalse($this->service->hasRule('nonexistent_rule'));
    $this->assertFalse($this->service->hasRule(''));
  }

}
