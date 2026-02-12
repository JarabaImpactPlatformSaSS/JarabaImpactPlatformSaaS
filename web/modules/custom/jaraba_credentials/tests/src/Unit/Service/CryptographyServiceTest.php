<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_credentials\Unit\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\jaraba_credentials\Service\CryptographyService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para CryptographyService.
 *
 * Verifica operaciones Ed25519: generacion de claves, firma,
 * verificacion y encriptacion/desencriptacion simetrica.
 *
 * @group jaraba_credentials
 * @coversDefaultClass \Drupal\jaraba_credentials\Service\CryptographyService
 */
class CryptographyServiceTest extends TestCase {

  /**
   * El servicio bajo test.
   */
  protected CryptographyService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    if (!extension_loaded('sodium')) {
      $this->markTestSkipped('La extension sodium no esta disponible.');
    }

    $logger = $this->createMock(LoggerInterface::class);
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')->willReturn($logger);

    $this->service = new CryptographyService($loggerFactory);
  }

  /**
   * Tests que se genera un par de claves Ed25519 valido.
   *
   * @covers ::generateKeyPair
   */
  public function testGenerateKeyPairReturnsValidKeys(): void {
    $keyPair = $this->service->generateKeyPair();

    $this->assertArrayHasKey('public', $keyPair);
    $this->assertArrayHasKey('private', $keyPair);

    // Verificar longitudes decodificadas.
    $publicKey = base64_decode($keyPair['public'], TRUE);
    $privateKey = base64_decode($keyPair['private'], TRUE);

    $this->assertSame(SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES, strlen($publicKey));
    $this->assertSame(SODIUM_CRYPTO_SIGN_SECRETKEYBYTES, strlen($privateKey));
  }

  /**
   * Tests que dos pares de claves son diferentes.
   *
   * @covers ::generateKeyPair
   */
  public function testGenerateKeyPairProducesUniqueKeys(): void {
    $keyPair1 = $this->service->generateKeyPair();
    $keyPair2 = $this->service->generateKeyPair();

    $this->assertNotSame($keyPair1['public'], $keyPair2['public']);
    $this->assertNotSame($keyPair1['private'], $keyPair2['private']);
  }

  /**
   * Tests firma y verificacion round-trip exitosa.
   *
   * @covers ::sign
   * @covers ::verify
   */
  public function testSignAndVerifyRoundTrip(): void {
    $keyPair = $this->service->generateKeyPair();
    $message = 'Credencial de prueba para verificacion';

    $signature = $this->service->sign($message, $keyPair['private']);

    $this->assertNotEmpty($signature);
    $this->assertTrue(
      $this->service->verify($message, $signature, $keyPair['public'])
    );
  }

  /**
   * Tests que la firma falla con clave privada invalida.
   *
   * @covers ::sign
   */
  public function testSignThrowsOnInvalidPrivateKey(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->service->sign('mensaje', base64_encode('clave_invalida'));
  }

  /**
   * Tests que la verificacion falla con mensaje alterado.
   *
   * @covers ::verify
   */
  public function testVerifyFailsWithTamperedMessage(): void {
    $keyPair = $this->service->generateKeyPair();
    $signature = $this->service->sign('mensaje original', $keyPair['private']);

    $this->assertFalse(
      $this->service->verify('mensaje alterado', $signature, $keyPair['public'])
    );
  }

  /**
   * Tests que la verificacion falla con clave publica incorrecta.
   *
   * @covers ::verify
   */
  public function testVerifyFailsWithWrongPublicKey(): void {
    $keyPair1 = $this->service->generateKeyPair();
    $keyPair2 = $this->service->generateKeyPair();

    $signature = $this->service->sign('mensaje', $keyPair1['private']);

    $this->assertFalse(
      $this->service->verify('mensaje', $signature, $keyPair2['public'])
    );
  }

  /**
   * Tests que la verificacion falla con firma corrupta.
   *
   * @covers ::verify
   */
  public function testVerifyFailsWithCorruptSignature(): void {
    $keyPair = $this->service->generateKeyPair();

    // Firma con longitud incorrecta.
    $this->assertFalse(
      $this->service->verify('mensaje', base64_encode('firma_corta'), $keyPair['public'])
    );
  }

  /**
   * Tests que la verificacion falla con base64 invalido.
   *
   * @covers ::verify
   */
  public function testVerifyFailsWithInvalidBase64(): void {
    $keyPair = $this->service->generateKeyPair();
    $this->assertFalse(
      $this->service->verify('mensaje', '!!!invalid!!!', $keyPair['public'])
    );
  }

  /**
   * Tests que la verificacion falla con clave publica de longitud incorrecta.
   *
   * @covers ::verify
   */
  public function testVerifyFailsWithWrongPublicKeyLength(): void {
    $keyPair = $this->service->generateKeyPair();
    $signature = $this->service->sign('mensaje', $keyPair['private']);

    $this->assertFalse(
      $this->service->verify('mensaje', $signature, base64_encode('clave_corta'))
    );
  }

  /**
   * Tests que isSodiumAvailable retorna true cuando sodium esta cargado.
   *
   * @covers ::isSodiumAvailable
   */
  public function testIsSodiumAvailable(): void {
    $this->assertTrue($this->service->isSodiumAvailable());
  }

  /**
   * Tests firma con mensaje vacio.
   *
   * @covers ::sign
   * @covers ::verify
   */
  public function testSignAndVerifyEmptyMessage(): void {
    $keyPair = $this->service->generateKeyPair();
    $signature = $this->service->sign('', $keyPair['private']);

    $this->assertNotEmpty($signature);
    $this->assertTrue($this->service->verify('', $signature, $keyPair['public']));
  }

  /**
   * Tests firma con mensaje UTF-8 largo.
   *
   * @covers ::sign
   * @covers ::verify
   */
  public function testSignAndVerifyUtf8Message(): void {
    $keyPair = $this->service->generateKeyPair();
    $message = str_repeat('Credencial con caracteres especiales: ñ, ü, é. ', 100);
    $signature = $this->service->sign($message, $keyPair['private']);

    $this->assertTrue($this->service->verify($message, $signature, $keyPair['public']));
  }

  /**
   * Tests firma con JSON (caso de uso real con OB3).
   *
   * @covers ::sign
   * @covers ::verify
   */
  public function testSignAndVerifyJsonPayload(): void {
    $keyPair = $this->service->generateKeyPair();
    $payload = json_encode([
      '@context' => ['https://www.w3.org/2018/credentials/v1'],
      'type' => ['VerifiableCredential', 'OpenBadgeCredential'],
      'issuer' => ['name' => 'Test Issuer'],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $signature = $this->service->sign($payload, $keyPair['private']);
    $this->assertTrue($this->service->verify($payload, $signature, $keyPair['public']));
  }

}
