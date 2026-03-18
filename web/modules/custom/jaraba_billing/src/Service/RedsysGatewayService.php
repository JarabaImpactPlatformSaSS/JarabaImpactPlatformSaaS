<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Gateway service for Redsys API integration (Bizum + card payments).
 *
 * Handles HMAC-SHA256 signature generation/verification per Redsys spec,
 * payment initiation, and callback verification.
 *
 * SECRET-MGMT-001: Secret key resolved via getenv() first, config fallback.
 * AUDIT-SEC-001: Callback verification uses hash_equals() for timing-safe
 * comparison of HMAC signatures.
 */
class RedsysGatewayService {

  /**
   * Redsys test environment URL.
   */
  protected const TEST_URL = 'https://sis-t.redsys.es/sis/realizarPago';

  /**
   * Redsys production environment URL.
   */
  protected const LIVE_URL = 'https://sis.redsys.es/sis/realizarPago';

  /**
   * EUR currency code per ISO 4217.
   */
  protected const CURRENCY_EUR = '978';

  /**
   * Authorization transaction type.
   */
  protected const TRANSACTION_TYPE_AUTH = '0';

  /**
   * Signature version identifier.
   */
  protected const SIGNATURE_VERSION = 'HMAC_SHA256_V1';

  /**
   * Payment method code for Bizum.
   */
  protected const PAY_METHOD_BIZUM = 'z';

  /**
   * Payment method code for card.
   */
  protected const PAY_METHOD_CARD = 'C';

  /**
   * The logger service.
   */
  protected LoggerInterface $logger;

  /**
   * The config factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs a RedsysGatewayService.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel for jaraba_billing.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   */
  public function __construct(
    LoggerInterface $logger,
    ConfigFactoryInterface $configFactory,
  ) {
    $this->logger = $logger;
    $this->configFactory = $configFactory;
  }

  /**
   * Initiates a payment through Redsys.
   *
   * Generates the signed form data needed to redirect the user to Redsys.
   *
   * @param float $amount
   *   The amount in EUR (e.g., 12.50).
   * @param string $orderRef
   *   Unique order reference (max 12 chars, alphanumeric, first 4 numeric).
   * @param string $notificationUrl
   *   Server-to-server notification URL (Ds_Merchant_MerchantURL).
   * @param string $returnUrl
   *   Success return URL for the user (Ds_Merchant_UrlOK).
   * @param string $method
   *   Payment method: 'bizum' or 'card'. Defaults to 'bizum'.
   *
   * @return array
   *   Associative array with keys:
   *   - form_url: Redsys endpoint URL.
   *   - ds_signature_version: Always 'HMAC_SHA256_V1'.
   *   - ds_merchant_parameters: Base64-encoded JSON merchant parameters.
   *   - ds_signature: HMAC-SHA256 signature.
   */
  public function initiatePayment(
    float $amount,
    string $orderRef,
    string $notificationUrl,
    string $returnUrl,
    string $method = 'bizum',
  ): array {
    // Amount in cents (integer string, no decimals).
    $amountCents = (string) (int) round($amount * 100);

    // Determine payment method code.
    $payMethod = $method === 'bizum' ? self::PAY_METHOD_BIZUM : self::PAY_METHOD_CARD;

    // Build failure return URL from success URL by replacing 'success' with
    // 'failure' in the path. The caller should provide the success URL.
    $failureUrl = str_replace('/return/success', '/return/failure', $returnUrl);

    $merchantParameters = [
      'DS_MERCHANT_AMOUNT' => $amountCents,
      'DS_MERCHANT_ORDER' => $orderRef,
      'DS_MERCHANT_MERCHANTCODE' => $this->getMerchantCode(),
      'DS_MERCHANT_CURRENCY' => self::CURRENCY_EUR,
      'DS_MERCHANT_TRANSACTIONTYPE' => self::TRANSACTION_TYPE_AUTH,
      'DS_MERCHANT_TERMINAL' => $this->getTerminal(),
      'DS_MERCHANT_MERCHANTURL' => $notificationUrl,
      'DS_MERCHANT_URLOK' => $returnUrl,
      'DS_MERCHANT_URLKO' => $failureUrl,
      'DS_MERCHANT_PAYMETHODS' => $payMethod,
    ];

    $merchantParametersJson = json_encode($merchantParameters, JSON_THROW_ON_ERROR);
    $merchantParametersB64 = base64_encode($merchantParametersJson);

    $signature = $this->generateSignature($merchantParametersB64, $orderRef);

    $this->logger->info('Redsys payment initiated: order=@order, amount=@amount, method=@method', [
      '@order' => $orderRef,
      '@amount' => $amountCents,
      '@method' => $method,
    ]);

    return [
      'form_url' => $this->getRedsysUrl(),
      'ds_signature_version' => self::SIGNATURE_VERSION,
      'ds_merchant_parameters' => $merchantParametersB64,
      'ds_signature' => $signature,
    ];
  }

  /**
   * Verifies a notification callback from Redsys.
   *
   * AUDIT-SEC-001: Uses hash_equals() for timing-safe HMAC comparison.
   *
   * @param string $dsSignatureVersion
   *   The signature version from the callback.
   * @param string $dsMerchantParameters
   *   The base64-encoded merchant parameters from the callback.
   * @param string $dsSignature
   *   The signature from the callback to verify.
   *
   * @return array|null
   *   Decoded merchant parameters array if signature is valid, NULL otherwise.
   */
  public function verifyNotification(
    string $dsSignatureVersion,
    string $dsMerchantParameters,
    string $dsSignature,
  ): ?array {
    try {
      // Decode merchant parameters.
      $decodedJson = base64_decode($dsMerchantParameters, TRUE);
      if ($decodedJson === FALSE) {
        $this->logger->warning('Redsys notification: invalid base64 in Ds_MerchantParameters.');
        return NULL;
      }

      $decoded = json_decode($decodedJson, TRUE, 512, JSON_THROW_ON_ERROR);
      if (!is_array($decoded)) {
        $this->logger->warning('Redsys notification: Ds_MerchantParameters is not a JSON object.');
        return NULL;
      }

      // Extract order number for signature verification.
      $orderNumber = $decoded['Ds_Order'] ?? $decoded['DS_MERCHANT_ORDER'] ?? '';
      if ($orderNumber === '') {
        $this->logger->warning('Redsys notification: missing order number in parameters.');
        return NULL;
      }

      // Generate expected signature and compare (timing-safe).
      $expectedSignature = $this->generateSignature($dsMerchantParameters, $orderNumber);

      // Redsys uses URL-safe base64 in some responses; normalize both.
      $normalizedExpected = strtr($expectedSignature, '-_', '+/');
      $normalizedReceived = strtr($dsSignature, '-_', '+/');

      if (!hash_equals($normalizedExpected, $normalizedReceived)) {
        $this->logger->warning('Redsys notification: HMAC signature mismatch for order @order.', [
          '@order' => $orderNumber,
        ]);
        return NULL;
      }

      $this->logger->info('Redsys notification verified: order=@order, response=@response', [
        '@order' => $orderNumber,
        '@response' => $decoded['Ds_Response'] ?? 'unknown',
      ]);

      return $decoded;
    }
    catch (\Throwable $e) {
      $this->logger->error('Redsys notification verification error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Returns the Redsys endpoint URL based on environment config.
   *
   * @return string
   *   The Redsys form submission URL.
   */
  public function getRedsysUrl(): string {
    $config = $this->configFactory->get('jaraba_billing.redsys');
    $environment = $config->get('environment') ?? 'test';

    return $environment === 'live' ? self::LIVE_URL : self::TEST_URL;
  }

  /**
   * Generates HMAC-SHA256 signature per Redsys specification.
   *
   * Steps per Redsys integration guide:
   * 1. Decode the merchant secret key from base64.
   * 2. 3DES-encrypt the order number using the decoded key.
   * 3. HMAC-SHA256 the merchantParameters using the 3DES-encrypted key.
   * 4. Base64-encode the HMAC result.
   *
   * @param string $merchantParameters
   *   The base64-encoded merchant parameters string.
   * @param string $orderNumber
   *   The order reference number.
   *
   * @return string
   *   The base64-encoded HMAC-SHA256 signature.
   */
  public function generateSignature(string $merchantParameters, string $orderNumber): string {
    // Step 1: Decode the base64-encoded secret key.
    $secretKey = base64_decode($this->getSecretKey(), TRUE);
    if ($secretKey === FALSE) {
      throw new \RuntimeException('Redsys secret key is not valid base64.');
    }

    // Step 2: 3DES-encrypt the order number with the secret key.
    // Redsys requires zero-padding to 8-byte blocks for 3DES-CBC.
    $orderPadded = str_pad($orderNumber, 8, "\0");
    $iv = str_repeat("\0", 8);
    $encryptedKey = openssl_encrypt(
      $orderPadded,
      'des-ede3-cbc',
      $secretKey,
      OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
      $iv,
    );

    if ($encryptedKey === FALSE) {
      throw new \RuntimeException('Redsys 3DES encryption failed.');
    }

    // Step 3: HMAC-SHA256 the merchant parameters with the encrypted key.
    $hmac = hash_hmac('sha256', $merchantParameters, $encryptedKey, TRUE);

    // Step 4: Base64-encode the result.
    return base64_encode($hmac);
  }

  /**
   * Returns the merchant code from config.
   *
   * @return string
   *   The Redsys merchant code.
   */
  protected function getMerchantCode(): string {
    $config = $this->configFactory->get('jaraba_billing.redsys');
    return (string) ($config->get('merchant_code') ?? '');
  }

  /**
   * Returns the terminal number from config.
   *
   * @return string
   *   The Redsys terminal number.
   */
  protected function getTerminal(): string {
    $config = $this->configFactory->get('jaraba_billing.redsys');
    return (string) ($config->get('terminal') ?? '001');
  }

  /**
   * Returns the secret key, preferring environment variable.
   *
   * SECRET-MGMT-001: getenv() first, config fallback.
   *
   * @return string
   *   The Redsys HMAC secret key (base64-encoded).
   */
  protected function getSecretKey(): string {
    $envKey = getenv('REDSYS_SECRET_KEY');
    if ($envKey !== FALSE && $envKey !== '') {
      return $envKey;
    }

    $config = $this->configFactory->get('jaraba_billing.redsys');
    $configKey = $config->get('secret_key');
    if ($configKey !== NULL && $configKey !== '') {
      return (string) $configKey;
    }

    $this->logger->error('Redsys secret key not configured. Set REDSYS_SECRET_KEY env var or jaraba_billing.redsys.secret_key config.');
    return '';
  }

}
