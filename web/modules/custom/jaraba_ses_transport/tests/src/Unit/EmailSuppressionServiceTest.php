<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ses_transport\Unit;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\StatementInterface;
use Drupal\jaraba_ses_transport\Service\EmailSuppressionService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Drupal\jaraba_ses_transport\Service\EmailSuppressionService
 * @group jaraba_ses_transport
 */
class EmailSuppressionServiceTest extends TestCase {

  /**
   * Tests isSuppressed returns FALSE for unknown email.
   */
  public function testIsSuppressedReturnsFalseForUnknownEmail(): void {
    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchAssoc')->willReturn(FALSE);

    $select = $this->createMock(SelectInterface::class);
    $select->method('fields')->willReturnSelf();
    $select->method('condition')->willReturnSelf();
    $select->method('range')->willReturnSelf();
    $select->method('execute')->willReturn($statement);

    $database = $this->createMock(Connection::class);
    $database->method('select')->willReturn($select);

    $logger = $this->createMock(LoggerInterface::class);

    $service = new EmailSuppressionService($database, $logger);
    self::assertFalse($service->isSuppressed('test@example.com'));
  }

  /**
   * Tests isSuppressed returns TRUE for permanently bounced email.
   */
  public function testIsSuppressedReturnsTrueForPermanentBounce(): void {
    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchAssoc')->willReturn([
      'reason' => 'bounce',
      'bounce_type' => 'Permanent',
      'created' => time() - 3600,
    ]);

    $select = $this->createMock(SelectInterface::class);
    $select->method('fields')->willReturnSelf();
    $select->method('condition')->willReturnSelf();
    $select->method('range')->willReturnSelf();
    $select->method('execute')->willReturn($statement);

    $database = $this->createMock(Connection::class);
    $database->method('select')->willReturn($select);

    $logger = $this->createMock(LoggerInterface::class);

    $service = new EmailSuppressionService($database, $logger);
    self::assertTrue($service->isSuppressed('bounced@example.com'));
  }

}
