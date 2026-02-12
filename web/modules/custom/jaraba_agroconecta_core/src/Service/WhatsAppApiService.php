<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de integración con WhatsApp Business API (Cloud API).
 *
 * Permite enviar mensajes de texto, plantillas y manejar webhooks
 * para notificaciones de pedidos, recuperación de carritos y
 * atención al cliente vía WhatsApp.
 * Referencia: Doc 68 §5 — WhatsApp Business Integration.
 */
class WhatsAppApiService {

  /**
   * URL base del Graph API de Meta/Facebook.
   */
  private const API_BASE_URL = 'https://graph.facebook.com/v18.0';

  /**
   * Plantillas de WhatsApp soportadas.
   */
  private const SUPPORTED_TEMPLATES = [
    'cart_reminder',
    'order_confirmation',
    'shipping_notification',
    'delivery_confirmation',
    'review_request',
    'welcome_message',
  ];

  /**
   * Código de país por defecto (España).
   */
  private const DEFAULT_COUNTRY_CODE = '+34';

  public function __construct(
    protected ClientInterface $httpClient,
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {
  }

  /**
   * Envía un mensaje de texto simple vía WhatsApp.
   *
   * @param string $phone
   *   Número de teléfono del destinatario.
   * @param string $text
   *   Texto del mensaje.
   *
   * @return array
   *   Resultado con keys: success, message_id o error.
   */
  public function sendTextMessage(string $phone, string $text): array {
    $formattedPhone = $this->formatPhoneNumber($phone);
    $config = $this->configFactory->get('jaraba_agroconecta_core.whatsapp');
    $phoneNumberId = $config->get('whatsapp_phone_number_id');
    $accessToken = $config->get('whatsapp_access_token');

    if (empty($phoneNumberId) || empty($accessToken)) {
      $this->logger->error('WhatsApp API no configurada: faltan credenciales.');
      return ['success' => FALSE, 'error' => 'API not configured'];
    }

    $url = self::API_BASE_URL . '/' . $phoneNumberId . '/messages';

    try {
      $response = $this->httpClient->request('POST', $url, [
        'headers' => [
          'Authorization' => 'Bearer ' . $accessToken,
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'messaging_product' => 'whatsapp',
          'to' => $formattedPhone,
          'type' => 'text',
          'text' => [
            'body' => $text,
          ],
        ],
      ]);

      $body = json_decode((string) $response->getBody(), TRUE);
      $messageId = $body['messages'][0]['id'] ?? '';

      $this->logger->info('WhatsApp mensaje enviado a @phone (id: @mid).', [
        '@phone' => $formattedPhone,
        '@mid' => $messageId,
      ]);

      return [
        'success' => TRUE,
        'message_id' => $messageId,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error enviando WhatsApp a @phone: @msg', [
        '@phone' => $formattedPhone,
        '@msg' => $e->getMessage(),
      ]);

      return [
        'success' => FALSE,
        'error' => $e->getMessage(),
      ];
    }
  }

  /**
   * Envía un mensaje de plantilla vía WhatsApp.
   *
   * @param string $phone
   *   Número de teléfono del destinatario.
   * @param string $templateName
   *   Nombre de la plantilla aprobada por Meta.
   * @param array $parameters
   *   Parámetros para rellenar la plantilla.
   * @param string $languageCode
   *   Código de idioma (por defecto 'es').
   *
   * @return array
   *   Resultado con keys: success, message_id o error.
   */
  public function sendTemplateMessage(
    string $phone,
    string $templateName,
    array $parameters = [],
    string $languageCode = 'es',
  ): array {
    // Validar que la plantilla está soportada.
    if (!in_array($templateName, self::SUPPORTED_TEMPLATES, TRUE)) {
      $this->logger->warning('Plantilla WhatsApp no soportada: @template.', [
        '@template' => $templateName,
      ]);
      return [
        'success' => FALSE,
        'error' => 'Unsupported template: ' . $templateName,
      ];
    }

    $formattedPhone = $this->formatPhoneNumber($phone);
    $config = $this->configFactory->get('jaraba_agroconecta_core.whatsapp');
    $phoneNumberId = $config->get('whatsapp_phone_number_id');
    $accessToken = $config->get('whatsapp_access_token');

    if (empty($phoneNumberId) || empty($accessToken)) {
      return ['success' => FALSE, 'error' => 'API not configured'];
    }

    $url = self::API_BASE_URL . '/' . $phoneNumberId . '/messages';

    // Construir componentes de la plantilla.
    $components = [];
    if (!empty($parameters)) {
      $templateParams = array_map(
        fn(string $value): array => ['type' => 'text', 'text' => $value],
        $parameters
      );
      $components[] = [
        'type' => 'body',
        'parameters' => $templateParams,
      ];
    }

    try {
      $response = $this->httpClient->request('POST', $url, [
        'headers' => [
          'Authorization' => 'Bearer ' . $accessToken,
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'messaging_product' => 'whatsapp',
          'to' => $formattedPhone,
          'type' => 'template',
          'template' => [
            'name' => $templateName,
            'language' => [
              'code' => $languageCode,
            ],
            'components' => $components,
          ],
        ],
      ]);

      $body = json_decode((string) $response->getBody(), TRUE);
      $messageId = $body['messages'][0]['id'] ?? '';

      $this->logger->info('WhatsApp plantilla "@template" enviada a @phone (id: @mid).', [
        '@template' => $templateName,
        '@phone' => $formattedPhone,
        '@mid' => $messageId,
      ]);

      return [
        'success' => TRUE,
        'message_id' => $messageId,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error enviando plantilla WhatsApp "@template" a @phone: @msg', [
        '@template' => $templateName,
        '@phone' => $formattedPhone,
        '@msg' => $e->getMessage(),
      ]);

      return [
        'success' => FALSE,
        'error' => $e->getMessage(),
      ];
    }
  }

  /**
   * Verifica la firma del webhook de WhatsApp.
   *
   * @param string $payload
   *   Payload crudo del webhook.
   * @param string $signature
   *   Firma del header X-Hub-Signature-256.
   *
   * @return bool
   *   TRUE si la firma es válida.
   */
  public function verifyWebhookSignature(string $payload, string $signature): bool {
    $config = $this->configFactory->get('jaraba_agroconecta_core.whatsapp');
    $appSecret = $config->get('whatsapp_app_secret');

    if (empty($appSecret)) {
      $this->logger->error('WhatsApp app_secret no configurado.');
      return FALSE;
    }

    $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $appSecret);

    return hash_equals($expectedSignature, $signature);
  }

  /**
   * Procesa un mensaje entrante del webhook.
   *
   * @param array $payload
   *   Payload decodificado del webhook.
   *
   * @return array
   *   Resultado con keys: processed, sender, message_type, text.
   */
  public function handleIncomingMessage(array $payload): array {
    if (empty($payload)) {
      return ['processed' => FALSE, 'error' => 'Empty payload'];
    }

    $entry = $payload['entry'][0] ?? [];
    $changes = $entry['changes'][0] ?? [];
    $value = $changes['value'] ?? [];
    $messages = $value['messages'] ?? [];

    if (empty($messages)) {
      return ['processed' => FALSE, 'error' => 'No messages in payload'];
    }

    $message = $messages[0];
    $sender = $message['from'] ?? '';
    $messageType = $message['type'] ?? 'unknown';
    $text = '';

    if ($messageType === 'text') {
      $text = $message['text']['body'] ?? '';
    }

    // Registrar log del mensaje entrante.
    $this->logger->info('WhatsApp mensaje entrante de @sender (tipo: @type).', [
      '@sender' => $sender,
      '@type' => $messageType,
    ]);

    // Guardar en entity para auditoría.
    try {
      $storage = $this->entityTypeManager->getStorage('whatsapp_message_agro');
      $entity = $storage->create([
        'sender' => $sender,
        'message_type' => $messageType,
        'content' => $text,
        'raw_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        'direction' => 'incoming',
      ]);
      $entity->save();
    }
    catch (\Exception $e) {
      $this->logger->warning('No se pudo guardar mensaje WhatsApp entrante: @msg', [
        '@msg' => $e->getMessage(),
      ]);
    }

    return [
      'processed' => TRUE,
      'sender' => $sender,
      'message_type' => $messageType,
      'text' => $text,
    ];
  }

  /**
   * Formatea un número de teléfono al formato internacional.
   *
   * @param string $phone
   *   Número de teléfono (puede ser local o internacional).
   *
   * @return string
   *   Número en formato internacional (+34XXXXXXXXX).
   */
  public function formatPhoneNumber(string $phone): string {
    // Eliminar espacios, guiones y paréntesis.
    $clean = preg_replace('/[\s\-\(\)]/', '', $phone);

    // Si ya tiene prefijo internacional, devolver limpio.
    if (str_starts_with($clean, '+')) {
      return $clean;
    }

    // Si empieza con 00, reemplazar por +.
    if (str_starts_with($clean, '00')) {
      return '+' . substr($clean, 2);
    }

    // Asumir código de país por defecto (España).
    return self::DEFAULT_COUNTRY_CODE . $clean;
  }

}
