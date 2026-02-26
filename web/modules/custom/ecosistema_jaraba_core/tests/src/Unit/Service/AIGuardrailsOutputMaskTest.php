<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\ecosistema_jaraba_core\Service\AIGuardrailsService;
use PHPUnit\Framework\TestCase;

/**
 * Tests AIGuardrailsService::maskOutputPII() with a comprehensive data provider.
 *
 * @group ecosistema_jaraba_core
 * @covers \Drupal\ecosistema_jaraba_core\Service\AIGuardrailsService::maskOutputPII
 */
class AIGuardrailsOutputMaskTest extends TestCase {

  /**
   * The service under test.
   */
  protected AIGuardrailsService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $database = $this->createMock(Connection::class);
    $this->service = new AIGuardrailsService($database);
  }

  /**
   * Tests that various PII patterns are correctly masked in output.
   *
   * @dataProvider piiPatternProvider
   */
  public function testMaskOutputPII(string $input, string $assertionType, string $message): void {
    $result = $this->service->maskOutputPII($input);

    switch ($assertionType) {
      case 'contains_masked':
        $this->assertStringContainsString('[DATO PROTEGIDO]', $result, $message);
        $this->assertNotSame($input, $result, 'Output should differ from input when PII is present.');
        break;

      case 'unchanged':
        $this->assertSame($input, $result, $message);
        $this->assertStringNotContainsString('[DATO PROTEGIDO]', $result, 'Clean text should not contain mask placeholder.');
        break;

      case 'all_masked':
        // For multiple PII, count occurrences of the mask.
        $maskCount = substr_count($result, '[DATO PROTEGIDO]');
        $this->assertGreaterThanOrEqual(2, $maskCount, $message);
        break;
    }
  }

  /**
   * Data provider for PII masking tests.
   *
   * @return array<string, array{string, string, string}>
   *   Each entry: [input text, assertion type, assertion message].
   */
  public static function piiPatternProvider(): array {
    return [
      'email' => [
        'Send to john.doe@example.com please',
        'contains_masked',
        'Email addresses should be masked.',
      ],
      'phone_us' => [
        'Call me at 555-123-4567',
        'contains_masked',
        'US phone numbers should be masked.',
      ],
      'ssn' => [
        'SSN: 123-45-6789',
        'contains_masked',
        'Social Security Numbers should be masked.',
      ],
      'credit_card' => [
        'Card 4111 1111 1111 1111',
        'contains_masked',
        'Credit card numbers should be masked.',
      ],
      'dni' => [
        'DNI: 12345678A',
        'contains_masked',
        'Spanish DNI should be masked.',
      ],
      'nie' => [
        'NIE: X1234567Z',
        'contains_masked',
        'Spanish NIE should be masked.',
      ],
      'iban_es' => [
        'Transfer to ES9121000418450200051332',
        'contains_masked',
        'Spanish IBAN should be masked.',
      ],
      'nif_cif' => [
        'NIF: A12345678',
        'contains_masked',
        'Spanish NIF/CIF should be masked.',
      ],
      'phone_es_0034' => [
        'Llama al 0034 612345678',
        'contains_masked',
        'Spanish phone numbers (0034 prefix) should be masked.',
      ],
      'phone_es_0034_no_space' => [
        'Telefono: 0034612345678',
        'contains_masked',
        'Spanish phone numbers (0034, no space) should be masked.',
      ],
      'clean_text' => [
        'Hello, how are you? The temperature is 72 degrees.',
        'unchanged',
        'Clean text without PII should remain unchanged.',
      ],
      'clean_text_with_numbers' => [
        'The project has 42 milestones and 7 sprints remaining.',
        'unchanged',
        'Text with non-PII numbers should remain unchanged.',
      ],
      'multiple_pii' => [
        'Email: a@b.com, DNI: 12345678A',
        'all_masked',
        'Multiple PII instances should all be masked.',
      ],
      'mixed_pii_and_text' => [
        'Please contact maria@empresa.es or call 0034 612345678 for details.',
        'all_masked',
        'Mixed PII (email + Spanish phone) should all be masked.',
      ],
    ];
  }

  /**
   * Tests that maskOutputPII returns empty string for empty input.
   */
  public function testMaskOutputPIIEmptyString(): void {
    $result = $this->service->maskOutputPII('');
    $this->assertSame('', $result);
  }

  /**
   * Tests that non-PII content around masked PII is preserved.
   */
  public function testMaskPreservesSurroundingText(): void {
    $input = 'Please send to john@example.com for review.';
    $result = $this->service->maskOutputPII($input);

    $this->assertStringStartsWith('Please send to ', $result);
    $this->assertStringEndsWith(' for review.', $result);
    $this->assertStringContainsString('[DATO PROTEGIDO]', $result);
  }

}
