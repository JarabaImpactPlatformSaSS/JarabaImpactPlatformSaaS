<?php

declare(strict_types=1);

namespace Drupal\jaraba_social\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_social\Entity\SocialPost;
use Psr\Log\LoggerInterface;

/**
 * Servicio de integracion con Make.com para publicacion automatizada.
 *
 * PROPOSITO:
 * Gestiona la comunicacion bidireccional con Make.com (antes Integromat)
 * via webhooks para automatizar la publicacion de posts en redes sociales.
 * Envia datos de posts a Make.com y recibe resultados de publicacion.
 *
 * FLUJO:
 * 1. Drupal envia datos del post al webhook de Make.com.
 * 2. Make.com procesa y publica en las plataformas sociales.
 * 3. Make.com envia resultados de vuelta via webhook entrante.
 *
 * DEPENDENCIAS:
 * - entity_type.manager: Gestión de entidades SocialPost.
 * - config.factory: Acceso a configuracion de webhooks por tenant.
 * - logger: Registro de eventos de integracion.
 */
class MakeComIntegrationService {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Factoria de configuracion.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger para registro de eventos.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $configFactory,
    protected LoggerInterface $logger,
  ) {
  }

  /**
   * Publica un post via webhook de Make.com.
   *
   * Envia los datos del post al webhook configurado para que Make.com
   * se encargue de la publicacion en las plataformas sociales.
   *
   * @param int $postId
   *   ID del post social a publicar.
   *
   * @return array
   *   Resultado de la operacion con claves:
   *   - success: TRUE si el envio fue exitoso.
   *   - message: Mensaje descriptivo.
   *   - webhook_response: Respuesta del webhook (si aplica).
   */
  public function publishViaWebhook(int $postId): array {
    try {
      $postStorage = $this->entityTypeManager->getStorage('social_post');
      $post = $postStorage->load($postId);

      if (!$post) {
        $this->logger->warning('Post social @id no encontrado para publicar via Make.com.', [
          '@id' => $postId,
        ]);
        return [
          'success' => FALSE,
          'message' => 'Post no encontrado.',
          'webhook_response' => NULL,
        ];
      }

      $tenantId = (int) ($post->get('tenant_id')->target_id ?? 0);
      $webhookUrl = $this->getWebhookUrl($tenantId);

      if (empty($webhookUrl)) {
        $this->logger->warning('No hay webhook de Make.com configurado para tenant @tid.', [
          '@tid' => $tenantId,
        ]);
        return [
          'success' => FALSE,
          'message' => 'Webhook de Make.com no configurado para este tenant.',
          'webhook_response' => NULL,
        ];
      }

      // Preparar los datos del post para Make.com.
      $payload = [
        'post_id' => $postId,
        'tenant_id' => $tenantId,
        'title' => $post->label(),
        'content' => $post->get('content')->value ?? '',
        'status' => $post->get('status')->value ?? SocialPost::STATUS_DRAFT,
        'scheduled_at' => $post->get('scheduled_at')->value ?? NULL,
        'timestamp' => time(),
      ];

      // Send the payload to Make.com webhook via HTTP POST.
      $httpClient = \Drupal::httpClient();
      $response = $httpClient->post($webhookUrl, [
        'headers' => [
          'Content-Type' => 'application/json',
          'Accept' => 'application/json',
        ],
        'json' => $payload,
        'timeout' => 30,
      ]);

      $statusCode = $response->getStatusCode();
      $responseBody = $response->getBody()->getContents();
      $webhookResponse = json_decode($responseBody, TRUE) ?? ['raw' => $responseBody];

      if ($statusCode >= 200 && $statusCode < 300) {
        $this->logger->info('Post @id enviado exitosamente a Make.com webhook para tenant @tid (HTTP @code).', [
          '@id' => $postId,
          '@tid' => $tenantId,
          '@code' => $statusCode,
        ]);

        return [
          'success' => TRUE,
          'message' => 'Post enviado a Make.com correctamente.',
          'webhook_response' => $webhookResponse,
        ];
      }

      $this->logger->error('Make.com webhook returned HTTP @code for post @id, tenant @tid.', [
        '@code' => $statusCode,
        '@id' => $postId,
        '@tid' => $tenantId,
      ]);

      return [
        'success' => FALSE,
        'message' => "Make.com webhook returned HTTP {$statusCode}.",
        'webhook_response' => $webhookResponse,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error publicando post @id via Make.com: @error', [
        '@id' => $postId,
        '@error' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'message' => $e->getMessage(),
        'webhook_response' => NULL,
      ];
    }
  }

  /**
   * Procesa datos entrantes desde un webhook de Make.com.
   *
   * Recibe los resultados de publicacion enviados por Make.com
   * y actualiza el estado de los posts correspondientes.
   *
   * @param array $data
   *   Datos recibidos del webhook con claves esperadas:
   *   - post_id: ID del post procesado.
   *   - platform: Plataforma donde se publico.
   *   - external_id: ID externo en la plataforma.
   *   - status: Estado resultante (success, failed).
   *   - metrics: Metricas iniciales (opcional).
   *
   * @return array
   *   Resultado del procesamiento con claves:
   *   - processed: TRUE si se proceso correctamente.
   *   - post_id: ID del post actualizado.
   *   - message: Mensaje descriptivo.
   */
  public function processIncomingWebhook(array $data): array {
    try {
      $postId = (int) ($data['post_id'] ?? 0);

      if ($postId === 0) {
        $this->logger->warning('Webhook entrante de Make.com sin post_id valido.');
        return [
          'processed' => FALSE,
          'post_id' => 0,
          'message' => 'post_id requerido.',
        ];
      }

      $postStorage = $this->entityTypeManager->getStorage('social_post');
      $post = $postStorage->load($postId);

      if (!$post) {
        $this->logger->warning('Post @id no encontrado al procesar webhook de Make.com.', [
          '@id' => $postId,
        ]);
        return [
          'processed' => FALSE,
          'post_id' => $postId,
          'message' => 'Post no encontrado.',
        ];
      }

      // Actualizar estado del post segun resultado de Make.com.
      $webhookStatus = $data['status'] ?? 'unknown';
      if ($webhookStatus === 'success') {
        $post->set('status', SocialPost::STATUS_PUBLISHED);
        $post->set('published_at', time());
      }
      else {
        $post->set('status', SocialPost::STATUS_FAILED);
      }

      // Guardar IDs externos si se proporcionan.
      if (!empty($data['external_id'])) {
        $platform = $data['platform'] ?? 'unknown';
        $externalIds = $post->get('external_ids')->getValue();
        $externalIds = is_array($externalIds) ? $externalIds : [];
        $externalIds[$platform] = $data['external_id'];
        $post->set('external_ids', $externalIds);
      }

      $post->save();

      $this->logger->info('Webhook de Make.com procesado para post @id: @status', [
        '@id' => $postId,
        '@status' => $webhookStatus,
      ]);

      return [
        'processed' => TRUE,
        'post_id' => $postId,
        'message' => 'Webhook procesado correctamente.',
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error procesando webhook de Make.com: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [
        'processed' => FALSE,
        'post_id' => (int) ($data['post_id'] ?? 0),
        'message' => $e->getMessage(),
      ];
    }
  }

  /**
   * Obtiene la URL del webhook de Make.com para un tenant.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return string|null
   *   URL del webhook o NULL si no esta configurado.
   */
  public function getWebhookUrl(int $tenantId): ?string {
    $config = $this->configFactory->get('jaraba_social.make_com_settings');
    $webhookUrl = $config->get("tenants.{$tenantId}.webhook_url");

    if (empty($webhookUrl)) {
      // Intentar URL global por defecto.
      $webhookUrl = $config->get('default_webhook_url');
    }

    return !empty($webhookUrl) ? (string) $webhookUrl : NULL;
  }

  /**
   * Verifica la conexion con Make.com para un tenant.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return bool
   *   TRUE si la conexion es valida.
   */
  public function testConnection(int $tenantId): bool {
    try {
      $webhookUrl = $this->getWebhookUrl($tenantId);

      if (empty($webhookUrl)) {
        $this->logger->warning('No hay webhook configurado para tenant @tid.', [
          '@tid' => $tenantId,
        ]);
        return FALSE;
      }

      // Send a lightweight test payload to verify connectivity.
      $httpClient = \Drupal::httpClient();
      $testPayload = [
        'test' => TRUE,
        'tenant_id' => $tenantId,
        'timestamp' => time(),
        'source' => 'jaraba_social_connection_test',
      ];

      $response = $httpClient->post($webhookUrl, [
        'headers' => [
          'Content-Type' => 'application/json',
          'Accept' => 'application/json',
        ],
        'json' => $testPayload,
        'timeout' => 15,
      ]);

      $statusCode = $response->getStatusCode();

      if ($statusCode >= 200 && $statusCode < 300) {
        $this->logger->info('Test de conexion con Make.com exitoso para tenant @tid (HTTP @code).', [
          '@tid' => $tenantId,
          '@code' => $statusCode,
        ]);
        return TRUE;
      }

      $this->logger->warning('Test de conexion con Make.com fallo para tenant @tid: HTTP @code.', [
        '@tid' => $tenantId,
        '@code' => $statusCode,
      ]);
      return FALSE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error en test de conexion Make.com para tenant @tid: @error', [
        '@tid' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

}
