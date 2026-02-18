<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_agent_flows\Unit\Service;

use Drupal\jaraba_agent_flows\Service\AgentFlowValidatorService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for AgentFlowValidatorService.
 *
 * Covers flow configuration validation: structural checks, step type
 * validation, duplicate name detection, parameter validation per step type,
 * and global settings validation.
 *
 * @coversDefaultClass \Drupal\jaraba_agent_flows\Service\AgentFlowValidatorService
 * @group jaraba_agent_flows
 */
class AgentFlowValidatorServiceTest extends UnitTestCase {

  /**
   * The service under test.
   */
  protected AgentFlowValidatorService $service;

  /**
   * Mock logger.
   */
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->logger = $this->createMock(LoggerInterface::class);
    $this->service = new AgentFlowValidatorService($this->logger);
  }

  /**
   * @covers ::validateFlowConfig
   */
  public function testValidConfigReturnsNoErrors(): void {
    $config = [
      'steps' => [
        [
          'name' => 'step_generate',
          'type' => 'generate',
          'params' => ['prompt' => 'Create a summary'],
        ],
        [
          'name' => 'step_publish',
          'type' => 'publish',
          'params' => ['target' => 'content_hub'],
        ],
      ],
    ];

    $errors = $this->service->validateFlowConfig($config);

    $this->assertEmpty($errors, 'A valid config should produce no errors.');
  }

  /**
   * @covers ::validateFlowConfig
   */
  public function testMissingStepsKeyReturnsError(): void {
    $config = ['name' => 'My Flow'];

    $errors = $this->service->validateFlowConfig($config);

    $this->assertNotEmpty($errors);
    $this->assertCount(1, $errors);
    $this->assertStringContainsString('steps', $errors[0]);
  }

  /**
   * @covers ::validateFlowConfig
   */
  public function testStepsNotArrayReturnsError(): void {
    $config = ['steps' => 'not_an_array'];

    $errors = $this->service->validateFlowConfig($config);

    $this->assertNotEmpty($errors);
    $this->assertStringContainsString('array', $errors[0]);
  }

  /**
   * @covers ::validateFlowConfig
   */
  public function testEmptyStepsArrayReturnsError(): void {
    $config = ['steps' => []];

    $errors = $this->service->validateFlowConfig($config);

    $this->assertNotEmpty($errors);
    $this->assertStringContainsString('al menos un paso', $errors[0]);
  }

  /**
   * @covers ::validateFlowConfig
   */
  public function testStepNotArrayReturnsError(): void {
    $config = [
      'steps' => ['just_a_string'],
    ];

    $errors = $this->service->validateFlowConfig($config);

    $this->assertNotEmpty($errors);
    $this->assertStringContainsString('Paso 1', $errors[0]);
    $this->assertStringContainsString('objeto/array', $errors[0]);
  }

  /**
   * @covers ::validateFlowConfig
   */
  public function testMissingStepNameReturnsError(): void {
    $config = [
      'steps' => [
        ['type' => 'generate', 'params' => ['prompt' => 'test']],
      ],
    ];

    $errors = $this->service->validateFlowConfig($config);

    $this->assertNotEmpty($errors);
    $this->assertStringContainsString('name', $errors[0]);
  }

  /**
   * @covers ::validateFlowConfig
   */
  public function testMissingStepTypeReturnsError(): void {
    $config = [
      'steps' => [
        ['name' => 'step1', 'params' => ['prompt' => 'test']],
      ],
    ];

    $errors = $this->service->validateFlowConfig($config);

    $this->assertNotEmpty($errors);
    $this->assertStringContainsString('type', $errors[0]);
  }

  /**
   * @covers ::validateFlowConfig
   */
  public function testInvalidStepTypeReturnsError(): void {
    $config = [
      'steps' => [
        [
          'name' => 'step1',
          'type' => 'invalid_type',
          'params' => [],
        ],
      ],
    ];

    $errors = $this->service->validateFlowConfig($config);

    $this->assertNotEmpty($errors);
    $this->assertStringContainsString('invalid_type', $errors[0]);
    $this->assertStringContainsString('Tipos permitidos', $errors[0]);
  }

  /**
   * @covers ::validateFlowConfig
   * @dataProvider validStepTypesProvider
   */
  public function testAllValidStepTypesAreAccepted(string $type): void {
    $config = [
      'steps' => [
        [
          'name' => 'step1',
          'type' => $type,
          'params' => $this->getMinimalParamsForType($type),
        ],
      ],
    ];

    $errors = $this->service->validateFlowConfig($config);

    // Filter out only type-related errors (there should be none).
    $typeErrors = array_filter($errors, fn(string $e) => str_contains($e, 'no valido'));
    $this->assertEmpty($typeErrors, "Step type '$type' should be valid.");
  }

  /**
   * Data provider for valid step types.
   */
  public static function validStepTypesProvider(): array {
    return [
      'generate' => ['generate'],
      'validate' => ['validate'],
      'transform' => ['transform'],
      'publish' => ['publish'],
      'condition' => ['condition'],
      'wait' => ['wait'],
      'notify' => ['notify'],
      'api_call' => ['api_call'],
    ];
  }

  /**
   * @covers ::validateFlowConfig
   */
  public function testDuplicateStepNamesReturnsError(): void {
    $config = [
      'steps' => [
        ['name' => 'duplicated', 'type' => 'generate', 'params' => ['prompt' => 'a']],
        ['name' => 'duplicated', 'type' => 'publish', 'params' => ['target' => 'x']],
      ],
    ];

    $errors = $this->service->validateFlowConfig($config);

    $duplicateErrors = array_filter($errors, fn(string $e) => str_contains($e, 'duplicado'));
    $this->assertNotEmpty($duplicateErrors, 'Duplicate step names should produce an error.');
  }

  /**
   * @covers ::validateFlowConfig
   */
  public function testGenerateStepRequiresPromptOrTemplate(): void {
    $config = [
      'steps' => [
        ['name' => 'gen', 'type' => 'generate', 'params' => []],
      ],
    ];

    $errors = $this->service->validateFlowConfig($config);

    $paramErrors = array_filter($errors, fn(string $e) => str_contains($e, 'prompt'));
    $this->assertNotEmpty($paramErrors, 'Generate step without prompt or template should error.');
  }

  /**
   * @covers ::validateFlowConfig
   */
  public function testGenerateStepAcceptsTemplate(): void {
    $config = [
      'steps' => [
        ['name' => 'gen', 'type' => 'generate', 'params' => ['template' => 'newsletter_v1']],
      ],
    ];

    $errors = $this->service->validateFlowConfig($config);

    $paramErrors = array_filter($errors, fn(string $e) => str_contains($e, 'prompt'));
    $this->assertEmpty($paramErrors, 'Generate step with template should not error on prompt.');
  }

  /**
   * @covers ::validateFlowConfig
   */
  public function testApiCallStepRequiresUrlAndMethod(): void {
    $config = [
      'steps' => [
        ['name' => 'api', 'type' => 'api_call', 'params' => []],
      ],
    ];

    $errors = $this->service->validateFlowConfig($config);

    $urlErrors = array_filter($errors, fn(string $e) => str_contains($e, 'url'));
    $methodErrors = array_filter($errors, fn(string $e) => str_contains($e, 'method'));
    $this->assertNotEmpty($urlErrors, 'api_call step without url should error.');
    $this->assertNotEmpty($methodErrors, 'api_call step without method should error.');
  }

  /**
   * @covers ::validateFlowConfig
   */
  public function testConditionStepRequiresExpression(): void {
    $config = [
      'steps' => [
        ['name' => 'cond', 'type' => 'condition', 'params' => []],
      ],
    ];

    $errors = $this->service->validateFlowConfig($config);

    $exprErrors = array_filter($errors, fn(string $e) => str_contains($e, 'expression'));
    $this->assertNotEmpty($exprErrors, 'Condition step without expression should error.');
  }

  /**
   * @covers ::validateFlowConfig
   */
  public function testWaitStepRequiresNumericSeconds(): void {
    $config = [
      'steps' => [
        ['name' => 'w', 'type' => 'wait', 'params' => ['seconds' => 'not_a_number']],
      ],
    ];

    $errors = $this->service->validateFlowConfig($config);

    $secondsErrors = array_filter($errors, fn(string $e) => str_contains($e, 'seconds'));
    $this->assertNotEmpty($secondsErrors, 'Wait step with non-numeric seconds should error.');
  }

  /**
   * @covers ::validateFlowConfig
   */
  public function testWaitStepAcceptsNumericSeconds(): void {
    $config = [
      'steps' => [
        ['name' => 'w', 'type' => 'wait', 'params' => ['seconds' => 30]],
      ],
    ];

    $errors = $this->service->validateFlowConfig($config);

    $secondsErrors = array_filter($errors, fn(string $e) => str_contains($e, 'seconds'));
    $this->assertEmpty($secondsErrors, 'Wait step with numeric seconds should not error.');
  }

  /**
   * @covers ::validateFlowConfig
   */
  public function testPublishStepRequiresTarget(): void {
    $config = [
      'steps' => [
        ['name' => 'pub', 'type' => 'publish', 'params' => []],
      ],
    ];

    $errors = $this->service->validateFlowConfig($config);

    $targetErrors = array_filter($errors, fn(string $e) => str_contains($e, 'target'));
    $this->assertNotEmpty($targetErrors, 'Publish step without target should error.');
  }

  /**
   * @covers ::validateFlowConfig
   */
  public function testSettingsNotArrayReturnsError(): void {
    $config = [
      'steps' => [
        ['name' => 's1', 'type' => 'notify', 'params' => []],
      ],
      'settings' => 'invalid_string',
    ];

    $errors = $this->service->validateFlowConfig($config);

    $settingsErrors = array_filter($errors, fn(string $e) => str_contains($e, 'settings'));
    $this->assertNotEmpty($settingsErrors, 'Non-array settings should error.');
  }

  /**
   * @covers ::validateFlowConfig
   */
  public function testSettingsNonNumericTimeoutReturnsError(): void {
    $config = [
      'steps' => [
        ['name' => 's1', 'type' => 'notify', 'params' => []],
      ],
      'settings' => ['timeout' => 'slow'],
    ];

    $errors = $this->service->validateFlowConfig($config);

    $timeoutErrors = array_filter($errors, fn(string $e) => str_contains($e, 'timeout'));
    $this->assertNotEmpty($timeoutErrors, 'Non-numeric timeout should error.');
  }

  /**
   * @covers ::validateFlowConfig
   */
  public function testSettingsNonNumericMaxRetriesReturnsError(): void {
    $config = [
      'steps' => [
        ['name' => 's1', 'type' => 'notify', 'params' => []],
      ],
      'settings' => ['max_retries' => 'many'],
    ];

    $errors = $this->service->validateFlowConfig($config);

    $retryErrors = array_filter($errors, fn(string $e) => str_contains($e, 'max_retries'));
    $this->assertNotEmpty($retryErrors, 'Non-numeric max_retries should error.');
  }

  /**
   * @covers ::validateFlowConfig
   */
  public function testSettingsWithValidNumericValuesAccepted(): void {
    $config = [
      'steps' => [
        ['name' => 's1', 'type' => 'notify', 'params' => []],
      ],
      'settings' => ['timeout' => 60, 'max_retries' => 3],
    ];

    $errors = $this->service->validateFlowConfig($config);

    $settingsErrors = array_filter($errors, fn(string $e) => str_contains($e, 'settings.'));
    $this->assertEmpty($settingsErrors, 'Valid numeric settings should not error.');
  }

  /**
   * @covers ::validateFlowConfig
   */
  public function testMultipleErrorsAreCollectedFromMultipleSteps(): void {
    $config = [
      'steps' => [
        // Missing name and invalid type.
        ['type' => 'invalid'],
        // Missing type.
        ['name' => 'step2'],
        // Valid step.
        ['name' => 'step3', 'type' => 'notify', 'params' => []],
      ],
    ];

    $errors = $this->service->validateFlowConfig($config);

    // At least 3 errors: missing name, invalid type, missing type.
    $this->assertGreaterThanOrEqual(3, count($errors));
  }

  /**
   * @covers ::validateFlowConfig
   */
  public function testLoggerCalledOnValidationFailure(): void {
    $this->logger->expects($this->once())
      ->method('notice')
      ->with(
        $this->stringContains('Validacion de flujo fallida'),
        $this->callback(fn(array $ctx) => $ctx['@count'] > 0)
      );

    $config = ['steps' => []];
    $this->service->validateFlowConfig($config);
  }

  /**
   * @covers ::validateFlowConfig
   */
  public function testLoggerNotCalledOnValidConfig(): void {
    $this->logger->expects($this->never())->method('notice');

    $config = [
      'steps' => [
        ['name' => 's1', 'type' => 'notify', 'params' => []],
      ],
    ];

    $this->service->validateFlowConfig($config);
  }

  /**
   * Returns minimal valid params for a given step type.
   *
   * @param string $type
   *   The step type.
   *
   * @return array
   *   Minimal params that satisfy validation.
   */
  protected function getMinimalParamsForType(string $type): array {
    return match ($type) {
      'generate' => ['prompt' => 'test'],
      'api_call' => ['url' => 'https://example.com', 'method' => 'GET'],
      'condition' => ['expression' => 'result.status == "ok"'],
      'wait' => ['seconds' => 10],
      'publish' => ['target' => 'content_hub'],
      default => [],
    };
  }

}
