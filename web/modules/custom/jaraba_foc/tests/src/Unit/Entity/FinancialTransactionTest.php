<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_foc\Unit\Entity;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\jaraba_foc\Entity\FinancialTransaction;
use Drupal\Tests\UnitTestCase;

/**
 * Tests unitarios para la entidad FinancialTransaction.
 *
 * COBERTURA:
 * Verifica los getters de la entidad inmutable de transacciones financieras.
 * Todos los campos se simulan via mock del metodo get() ya que
 * ContentEntityBase requiere un constructor completo de Drupal.
 *
 * CAMPOS VERIFICADOS:
 * - amount: Decimal(10,4) - precision monetaria
 * - currency: ISO 4217 (default EUR)
 * - transaction_timestamp: Unix timestamp UTC
 * - transaction_type: Referencia a taxonomia
 * - source_system: Origen de la transaccion
 * - external_id: ID en sistema origen (deduplicacion)
 * - related_tenant: Referencia a Group/Tenant
 * - related_vertical: String con ID del vertical (corregido de target_id a value)
 * - is_recurring: Booleano para MRR vs one-time
 * - isRevenue(): Logica basada en signo del monto
 *
 * @group jaraba_foc
 * @coversDefaultClass \Drupal\jaraba_foc\Entity\FinancialTransaction
 */
class FinancialTransactionTest extends UnitTestCase {

  /**
   * Helper: crea un mock de FinancialTransaction con el metodo get() stubbed.
   *
   * @param array $fieldMap
   *   Mapa de nombre_campo => ['property' => valor].
   *   Cada entrada produce un FieldItemListInterface mock cuyo
   *   ->value o ->target_id devuelve lo indicado.
   *
   * @return \Drupal\jaraba_foc\Entity\FinancialTransaction|\PHPUnit\Framework\MockObject\MockObject
   */
  protected function createEntityMock(array $fieldMap): FinancialTransaction {
    $entity = $this->getMockBuilder(FinancialTransaction::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['get'])
      ->getMock();

    $entity->method('get')
      ->willReturnCallback(function (string $fieldName) use ($fieldMap) {
        $data = $fieldMap[$fieldName] ?? [];
        $valueData = array_key_exists('value', $data) ? $data['value'] : NULL;
        $targetIdData = array_key_exists('target_id', $data) ? $data['target_id'] : NULL;

        $fieldItem = $this->createMock(FieldItemListInterface::class);

        // FieldItemListInterface defines __get(), __isset(), __set().
        // PHPUnit stubs these automatically. We must configure them so that
        // the null coalescing operator (??) works correctly: PHP calls
        // __isset() before __get() when evaluating $obj->prop ?? default.
        $fieldItem->method('__get')
          ->willReturnCallback(function (string $property) use ($valueData, $targetIdData) {
            return match ($property) {
              'value' => $valueData,
              'target_id' => $targetIdData,
              default => NULL,
            };
          });

        $fieldItem->method('__isset')
          ->willReturnCallback(function (string $property) use ($data) {
            // Return TRUE if the property key exists in the field data,
            // even when the value is NULL (so ?? can evaluate the actual value).
            return match ($property) {
              'value' => array_key_exists('value', $data),
              'target_id' => array_key_exists('target_id', $data),
              default => FALSE,
            };
          });

        return $fieldItem;
      });

    return $entity;
  }

  // =========================================================================
  // TESTS: getAmount()
  // =========================================================================

  /**
   * Verifica que getAmount() devuelve el valor decimal almacenado.
   *
   * @covers ::getAmount
   */
  public function testGetAmountReturnsStoredValue(): void {
    $entity = $this->createEntityMock([
      'amount' => ['value' => '1234.5678'],
    ]);

    $this->assertSame('1234.5678', $entity->getAmount());
  }

  /**
   * Verifica que getAmount() devuelve '0.0000' cuando el campo es NULL.
   *
   * @covers ::getAmount
   */
  public function testGetAmountReturnsDefaultWhenNull(): void {
    $entity = $this->createEntityMock([
      'amount' => ['value' => NULL],
    ]);

    $this->assertSame('0.0000', $entity->getAmount());
  }

  /**
   * Verifica que getAmount() maneja montos negativos (gastos).
   *
   * @covers ::getAmount
   */
  public function testGetAmountHandlesNegativeValues(): void {
    $entity = $this->createEntityMock([
      'amount' => ['value' => '-500.0000'],
    ]);

    $this->assertSame('-500.0000', $entity->getAmount());
  }

  // =========================================================================
  // TESTS: getCurrency()
  // =========================================================================

  /**
   * Verifica que getCurrency() devuelve el codigo ISO 4217 almacenado.
   *
   * @covers ::getCurrency
   */
  public function testGetCurrencyReturnsStoredValue(): void {
    $entity = $this->createEntityMock([
      'currency' => ['value' => 'USD'],
    ]);

    $this->assertSame('USD', $entity->getCurrency());
  }

  /**
   * Verifica que getCurrency() devuelve 'EUR' como default.
   *
   * @covers ::getCurrency
   */
  public function testGetCurrencyReturnsDefaultEUR(): void {
    $entity = $this->createEntityMock([
      'currency' => ['value' => NULL],
    ]);

    $this->assertSame('EUR', $entity->getCurrency());
  }

  // =========================================================================
  // TESTS: getTransactionTimestamp()
  // =========================================================================

  /**
   * Verifica que getTransactionTimestamp() devuelve un entero.
   *
   * @covers ::getTransactionTimestamp
   */
  public function testGetTransactionTimestampReturnsInt(): void {
    $now = time();
    $entity = $this->createEntityMock([
      'transaction_timestamp' => ['value' => (string) $now],
    ]);

    $result = $entity->getTransactionTimestamp();
    $this->assertIsInt($result);
    $this->assertSame($now, $result);
  }

  /**
   * Verifica que getTransactionTimestamp() convierte NULL a 0.
   *
   * @covers ::getTransactionTimestamp
   */
  public function testGetTransactionTimestampReturnsZeroForNull(): void {
    $entity = $this->createEntityMock([
      'transaction_timestamp' => ['value' => NULL],
    ]);

    $this->assertSame(0, $entity->getTransactionTimestamp());
  }

  // =========================================================================
  // TESTS: getTransactionType()
  // =========================================================================

  /**
   * Verifica que getTransactionType() devuelve el target_id como string.
   *
   * @covers ::getTransactionType
   */
  public function testGetTransactionTypeReturnsTargetId(): void {
    $entity = $this->createEntityMock([
      'transaction_type' => ['target_id' => 42],
    ]);

    $this->assertSame('42', $entity->getTransactionType());
  }

  /**
   * Verifica que getTransactionType() devuelve NULL cuando no tiene tipo.
   *
   * @covers ::getTransactionType
   */
  public function testGetTransactionTypeReturnsNullWhenEmpty(): void {
    $entity = $this->createEntityMock([
      'transaction_type' => ['target_id' => NULL],
    ]);

    $this->assertNull($entity->getTransactionType());
  }

  // =========================================================================
  // TESTS: getSourceSystem()
  // =========================================================================

  /**
   * Verifica que getSourceSystem() devuelve el valor almacenado.
   *
   * @covers ::getSourceSystem
   */
  public function testGetSourceSystemReturnsStoredValue(): void {
    $entity = $this->createEntityMock([
      'source_system' => ['value' => 'stripe_connect'],
    ]);

    $this->assertSame('stripe_connect', $entity->getSourceSystem());
  }

  /**
   * Verifica que getSourceSystem() devuelve 'manual' como default.
   *
   * @covers ::getSourceSystem
   */
  public function testGetSourceSystemReturnsManualAsDefault(): void {
    $entity = $this->createEntityMock([
      'source_system' => ['value' => NULL],
    ]);

    $this->assertSame('manual', $entity->getSourceSystem());
  }

  // =========================================================================
  // TESTS: getExternalId()
  // =========================================================================

  /**
   * Verifica que getExternalId() devuelve el ID externo.
   *
   * @covers ::getExternalId
   */
  public function testGetExternalIdReturnsStoredValue(): void {
    $entity = $this->createEntityMock([
      'external_id' => ['value' => 'pi_1234567890'],
    ]);

    $this->assertSame('pi_1234567890', $entity->getExternalId());
  }

  /**
   * Verifica que getExternalId() devuelve NULL cuando no esta establecido.
   *
   * @covers ::getExternalId
   */
  public function testGetExternalIdReturnsNullWhenEmpty(): void {
    $entity = $this->createEntityMock([
      'external_id' => ['value' => NULL],
    ]);

    $this->assertNull($entity->getExternalId());
  }

  // =========================================================================
  // TESTS: getRelatedTenantId()
  // =========================================================================

  /**
   * Verifica que getRelatedTenantId() devuelve un entero.
   *
   * @covers ::getRelatedTenantId
   */
  public function testGetRelatedTenantIdReturnsInt(): void {
    $entity = $this->createEntityMock([
      'related_tenant' => ['target_id' => 42],
    ]);

    $result = $entity->getRelatedTenantId();
    $this->assertIsInt($result);
    $this->assertSame(42, $result);
  }

  /**
   * Verifica que getRelatedTenantId() devuelve NULL cuando no hay tenant.
   *
   * @covers ::getRelatedTenantId
   */
  public function testGetRelatedTenantIdReturnsNullWhenEmpty(): void {
    $entity = $this->createEntityMock([
      'related_tenant' => ['target_id' => NULL],
    ]);

    $this->assertNull($entity->getRelatedTenantId());
  }

  // =========================================================================
  // TESTS: getRelatedVerticalId()
  // Verificacion critica: usa ->value (string) NO ->target_id
  // =========================================================================

  /**
   * Verifica que getRelatedVerticalId() usa ->value (no target_id).
   *
   * Este test documenta el fix de target_id a value para related_vertical,
   * ya que el campo es un string field, no una entity_reference.
   *
   * @covers ::getRelatedVerticalId
   */
  public function testGetRelatedVerticalIdUsesValueNotTargetId(): void {
    $entity = $this->createEntityMock([
      'related_vertical' => ['value' => 'emprendimiento', 'target_id' => NULL],
    ]);

    $this->assertSame('emprendimiento', $entity->getRelatedVerticalId());
  }

  /**
   * Verifica que getRelatedVerticalId() devuelve NULL cuando esta vacio.
   *
   * @covers ::getRelatedVerticalId
   */
  public function testGetRelatedVerticalIdReturnsNullWhenEmpty(): void {
    $entity = $this->createEntityMock([
      'related_vertical' => ['value' => '', 'target_id' => NULL],
    ]);

    $this->assertNull($entity->getRelatedVerticalId());
  }

  /**
   * Verifica que getRelatedVerticalId() devuelve NULL cuando el campo es NULL.
   *
   * @covers ::getRelatedVerticalId
   */
  public function testGetRelatedVerticalIdReturnsNullWhenNull(): void {
    $entity = $this->createEntityMock([
      'related_vertical' => ['value' => NULL],
    ]);

    $this->assertNull($entity->getRelatedVerticalId());
  }

  // =========================================================================
  // TESTS: isRecurring()
  // =========================================================================

  /**
   * Verifica que isRecurring() devuelve TRUE para transacciones recurrentes.
   *
   * @covers ::isRecurring
   */
  public function testIsRecurringReturnsTrueWhenFlagSet(): void {
    $entity = $this->createEntityMock([
      'is_recurring' => ['value' => 1],
    ]);

    $this->assertTrue($entity->isRecurring());
  }

  /**
   * Verifica que isRecurring() devuelve FALSE para transacciones puntuales.
   *
   * @covers ::isRecurring
   */
  public function testIsRecurringReturnsFalseWhenNotSet(): void {
    $entity = $this->createEntityMock([
      'is_recurring' => ['value' => 0],
    ]);

    $this->assertFalse($entity->isRecurring());
  }

  /**
   * Verifica que isRecurring() trata NULL como FALSE.
   *
   * @covers ::isRecurring
   */
  public function testIsRecurringReturnsFalseForNull(): void {
    $entity = $this->createEntityMock([
      'is_recurring' => ['value' => NULL],
    ]);

    $this->assertFalse($entity->isRecurring());
  }

  // =========================================================================
  // TESTS: isRevenue()
  // =========================================================================

  /**
   * Verifica que isRevenue() devuelve TRUE para montos positivos.
   *
   * @covers ::isRevenue
   */
  public function testIsRevenueReturnsTrueForPositiveAmount(): void {
    $entity = $this->createEntityMock([
      'amount' => ['value' => '100.0000'],
    ]);

    $this->assertTrue($entity->isRevenue());
  }

  /**
   * Verifica que isRevenue() devuelve FALSE para montos negativos (gastos).
   *
   * @covers ::isRevenue
   */
  public function testIsRevenueReturnsFalseForNegativeAmount(): void {
    $entity = $this->createEntityMock([
      'amount' => ['value' => '-50.0000'],
    ]);

    $this->assertFalse($entity->isRevenue());
  }

  /**
   * Verifica que isRevenue() devuelve FALSE para monto cero.
   *
   * @covers ::isRevenue
   */
  public function testIsRevenueReturnsFalseForZeroAmount(): void {
    $entity = $this->createEntityMock([
      'amount' => ['value' => '0.0000'],
    ]);

    $this->assertFalse($entity->isRevenue());
  }

  /**
   * Verifica que isRevenue() devuelve FALSE para NULL (default 0.0000).
   *
   * @covers ::isRevenue
   */
  public function testIsRevenueReturnsFalseForNullAmount(): void {
    $entity = $this->createEntityMock([
      'amount' => ['value' => NULL],
    ]);

    $this->assertFalse($entity->isRevenue());
  }

  // =========================================================================
  // TESTS: Escenario completo de una transaccion
  // =========================================================================

  /**
   * Verifica un escenario completo con todos los campos establecidos.
   *
   * Simula una transaccion recurrente de Stripe Connect por 49.99 EUR
   * para el tenant 42 en el vertical emprendimiento.
   */
  public function testFullTransactionScenario(): void {
    $timestamp = 1708300800; // 2024-02-19 00:00:00 UTC
    $entity = $this->createEntityMock([
      'amount' => ['value' => '49.9900'],
      'currency' => ['value' => 'EUR'],
      'transaction_timestamp' => ['value' => (string) $timestamp],
      'transaction_type' => ['target_id' => 1],
      'source_system' => ['value' => 'stripe_connect'],
      'external_id' => ['value' => 'pi_3AbCdEfGhIjKlMnO'],
      'related_tenant' => ['target_id' => 42],
      'related_vertical' => ['value' => 'emprendimiento'],
      'is_recurring' => ['value' => 1],
    ]);

    $this->assertSame('49.9900', $entity->getAmount());
    $this->assertSame('EUR', $entity->getCurrency());
    $this->assertSame($timestamp, $entity->getTransactionTimestamp());
    $this->assertSame('1', $entity->getTransactionType());
    $this->assertSame('stripe_connect', $entity->getSourceSystem());
    $this->assertSame('pi_3AbCdEfGhIjKlMnO', $entity->getExternalId());
    $this->assertSame(42, $entity->getRelatedTenantId());
    $this->assertSame('emprendimiento', $entity->getRelatedVerticalId());
    $this->assertTrue($entity->isRecurring());
    $this->assertTrue($entity->isRevenue());
  }

}
