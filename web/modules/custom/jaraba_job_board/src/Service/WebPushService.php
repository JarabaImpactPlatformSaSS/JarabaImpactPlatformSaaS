<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de Web Push Notifications.
 *
 * Implementa push notifications usando VAPID keys
 * y la Web Push API nativa del navegador.
 * NO requiere Firebase/FCM.
 */
class WebPushService
{

    /**
     * Database connection.
     */
    protected Connection $database;

    /**
     * Entity type manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * HTTP client.
     */
    protected ClientInterface $httpClient;

    /**
     * Logger.
     */
    protected LoggerInterface $logger;

    /**
     * VAPID keys (deben configurarse en settings).
     */
    protected ?string $vapidPublicKey = NULL;
    protected ?string $vapidPrivateKey = NULL;

    /**
     * Constructor.
     */
    public function __construct(
        Connection $database,
        EntityTypeManagerInterface $entityTypeManager,
        ClientInterface $httpClient,
        LoggerInterface $logger
    ) {
        $this->database = $database;
        $this->entityTypeManager = $entityTypeManager;
        $this->httpClient = $httpClient;
        $this->logger = $logger;

        // Cargar VAPID keys desde config
        $config = \Drupal::config('jaraba_job_board.push_settings');
        $this->vapidPublicKey = $config->get('vapid_public_key') ?: NULL;
        $this->vapidPrivateKey = $config->get('vapid_private_key') ?: NULL;
    }

    /**
     * Registra una suscripción push.
     *
     * @param int $userId
     *   ID del usuario.
     * @param array $subscription
     *   Datos de suscripción del navegador.
     *
     * @return bool
     *   TRUE si se registró correctamente.
     */
    public function subscribe(int $userId, array $subscription): bool
    {
        $this->ensureTablesExist();

        $endpoint = $subscription['endpoint'] ?? '';
        if (empty($endpoint)) {
            return FALSE;
        }

        try {
            $this->database->merge('push_subscription')
                ->keys([
                    'user_id' => $userId,
                    'endpoint' => $endpoint,
                ])
                ->fields([
                    'user_id' => $userId,
                    'endpoint' => $endpoint,
                    'p256dh_key' => $subscription['keys']['p256dh'] ?? '',
                    'auth_key' => $subscription['keys']['auth'] ?? '',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                    'is_active' => 1,
                    'created_at' => time(),
                    'last_used_at' => time(),
                ])
                ->execute();

            $this->logger->info('Push subscription registered for user @user', ['@user' => $userId]);
            return TRUE;
        } catch (\Exception $e) {
            $this->logger->error('Error registering push subscription: @error', ['@error' => $e->getMessage()]);
            return FALSE;
        }
    }

    /**
     * Elimina una suscripción.
     */
    public function unsubscribe(int $userId, string $endpoint): bool
    {
        try {
            $this->database->delete('push_subscription')
                ->condition('user_id', $userId)
                ->condition('endpoint', $endpoint)
                ->execute();

            return TRUE;
        } catch (\Exception $e) {
            return FALSE;
        }
    }

    /**
     * Envía notificación push a un usuario.
     *
     * @param int $userId
     *   ID del usuario.
     * @param array $payload
     *   Datos de la notificación.
     *
     * @return int
     *   Número de notificaciones enviadas.
     */
    public function sendToUser(int $userId, array $payload): int
    {
        $subscriptions = $this->getUserSubscriptions($userId);
        $sent = 0;

        foreach ($subscriptions as $sub) {
            if ($this->sendPush($sub, $payload)) {
                $sent++;
                $this->updateLastUsed($sub->id);
            } else {
                // Si falla, puede ser que la suscripción expiró
                $this->markInactive($sub->id);
            }
        }

        return $sent;
    }

    /**
     * Envía notificación push a múltiples usuarios.
     *
     * @param array $userIds
     *   IDs de usuarios.
     * @param array $payload
     *   Datos de la notificación.
     *
     * @return int
     *   Número total de notificaciones enviadas.
     */
    public function sendToUsers(array $userIds, array $payload): int
    {
        $total = 0;
        foreach ($userIds as $userId) {
            $total += $this->sendToUser($userId, $payload);
        }
        return $total;
    }

    /**
     * Obtiene suscripciones activas de un usuario.
     */
    protected function getUserSubscriptions(int $userId): array
    {
        if (!$this->database->schema()->tableExists('push_subscription')) {
            return [];
        }

        return $this->database->select('push_subscription', 's')
            ->fields('s')
            ->condition('user_id', $userId)
            ->condition('is_active', 1)
            ->execute()
            ->fetchAll();
    }

    /**
     * Envía una notificación push individual.
     */
    protected function sendPush(object $subscription, array $payload): bool
    {
        if (empty($this->vapidPublicKey) || empty($this->vapidPrivateKey)) {
            $this->logger->warning('VAPID keys not configured, cannot send push');
            return FALSE;
        }

        try {
            // Preparar payload
            $jsonPayload = json_encode([
                'title' => $payload['title'] ?? 'Notificación',
                'body' => $payload['body'] ?? '',
                'icon' => $payload['icon'] ?? '/themes/custom/agroconecta_theme/images/icon-192.png',
                'badge' => $payload['badge'] ?? '/themes/custom/agroconecta_theme/images/badge-72.png',
                'data' => $payload['data'] ?? [],
                'actions' => $payload['actions'] ?? [],
                'tag' => $payload['tag'] ?? 'default',
                'requireInteraction' => $payload['requireInteraction'] ?? FALSE,
            ]);

            // Crear JWT para VAPID
            $jwt = $this->createVapidJwt($subscription->endpoint);

            // Headers Web Push
            $headers = [
                'Content-Type' => 'application/octet-stream',
                'Content-Encoding' => 'aes128gcm',
                'TTL' => $payload['ttl'] ?? 86400,
                'Authorization' => 'vapid t=' . $jwt . ', k=' . $this->vapidPublicKey,
            ];

            // Encriptar payload (simplificado - en producción usar biblioteca web-push)
            $encryptedPayload = $this->encryptPayload(
                $jsonPayload,
                $subscription->p256dh_key,
                $subscription->auth_key
            );

            // Enviar
            $response = $this->httpClient->post($subscription->endpoint, [
                'headers' => $headers,
                'body' => $encryptedPayload,
                'timeout' => 10,
                'http_errors' => FALSE,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode >= 200 && $statusCode < 300) {
                return TRUE;
            }

            if ($statusCode === 410 || $statusCode === 404) {
                // Subscription expired
                return FALSE;
            }

            $this->logger->warning('Push failed with status @status', ['@status' => $statusCode]);
            return FALSE;
        } catch (\Exception $e) {
            $this->logger->error('Push error: @error', ['@error' => $e->getMessage()]);
            return FALSE;
        }
    }

    /**
     * Crea JWT para VAPID.
     */
    protected function createVapidJwt(string $endpoint): string
    {
        $audience = parse_url($endpoint, PHP_URL_SCHEME) . '://' . parse_url($endpoint, PHP_URL_HOST);

        $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $payload = base64_encode(json_encode([
            'aud' => $audience,
            'exp' => time() + 86400,
            'sub' => 'mailto:admin@jaraba.es',
        ]));

        // En producción, firmar con clave privada ECDSA
        // Por ahora, retornamos un placeholder
        $signature = base64_encode(hash('sha256', $header . '.' . $payload . $this->vapidPrivateKey, TRUE));

        return $header . '.' . $payload . '.' . $signature;
    }

    /**
     * Encripta payload para Web Push.
     *
     * Nota: En producción, usar biblioteca como minishlink/web-push
     */
    protected function encryptPayload(string $payload, string $p256dh, string $auth): string
    {
        // Placeholder - en producción usar encriptación ECDH + AES-128-GCM
        // La implementación completa requiere la biblioteca web-push
        return $payload;
    }

    /**
     * Actualiza timestamp de último uso.
     */
    protected function updateLastUsed(int $subscriptionId): void
    {
        $this->database->update('push_subscription')
            ->fields(['last_used_at' => time()])
            ->condition('id', $subscriptionId)
            ->execute();
    }

    /**
     * Marca suscripción como inactiva.
     */
    protected function markInactive(int $subscriptionId): void
    {
        $this->database->update('push_subscription')
            ->fields(['is_active' => 0])
            ->condition('id', $subscriptionId)
            ->execute();
    }

    /**
     * Obtiene la clave pública VAPID para el frontend.
     */
    public function getVapidPublicKey(): ?string
    {
        return $this->vapidPublicKey;
    }

    /**
     * Envía notificación de nuevo job match.
     */
    public function notifyJobMatch(int $userId, object $job, float $matchScore): bool
    {
        $payload = [
            'title' => 'Nueva oferta: ' . $job->label(),
            'body' => sprintf('Match: %d%% - %s', round($matchScore), $job->get('location')->value ?? ''),
            'icon' => '/themes/custom/agroconecta_theme/images/icon-job.png',
            'tag' => 'job-match-' . $job->id(),
            'data' => [
                'url' => '/jobs/' . $job->id(),
                'job_id' => $job->id(),
                'match_score' => $matchScore,
            ],
            'actions' => [
                ['action' => 'view', 'title' => 'Ver oferta'],
                ['action' => 'dismiss', 'title' => 'Ignorar'],
            ],
            'requireInteraction' => TRUE,
        ];

        return $this->sendToUser($userId, $payload) > 0;
    }

    /**
     * Envía notificación de cambio de estado de aplicación.
     */
    public function notifyApplicationStatus(int $userId, string $status, string $jobTitle): bool
    {
        $messages = [
            'shortlisted' => ['title' => '¡Preseleccionado!', 'emoji' => '🎉'],
            'interviewed' => ['title' => 'Entrevista programada', 'emoji' => '📅'],
            'offered' => ['title' => '¡Oferta recibida!', 'emoji' => '🎁'],
            'hired' => ['title' => '¡Contratado!', 'emoji' => '🏆'],
        ];

        $msg = $messages[$status] ?? ['title' => 'Actualización', 'emoji' => '📬'];

        $payload = [
            'title' => $msg['emoji'] . ' ' . $msg['title'],
            'body' => $jobTitle,
            'tag' => 'app-status-' . $status,
            'data' => [
                'url' => '/my-applications',
                'status' => $status,
            ],
            'requireInteraction' => TRUE,
        ];

        return $this->sendToUser($userId, $payload) > 0;
    }

    /**
     * Asegura que existan las tablas.
     */
    protected function ensureTablesExist(): void
    {
        $schema = $this->database->schema();

        if (!$schema->tableExists('push_subscription')) {
            $schema->createTable('push_subscription', [
                'fields' => [
                    'id' => ['type' => 'serial', 'unsigned' => TRUE, 'not null' => TRUE],
                    'user_id' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE],
                    'endpoint' => ['type' => 'text', 'not null' => TRUE],
                    'p256dh_key' => ['type' => 'varchar', 'length' => 255, 'not null' => TRUE],
                    'auth_key' => ['type' => 'varchar', 'length' => 255, 'not null' => TRUE],
                    'user_agent' => ['type' => 'varchar', 'length' => 255],
                    'is_active' => ['type' => 'int', 'size' => 'tiny', 'default' => 1],
                    'created_at' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE],
                    'last_used_at' => ['type' => 'int', 'unsigned' => TRUE],
                ],
                'primary key' => ['id'],
                'indexes' => [
                    'user_id' => ['user_id'],
                    'is_active' => ['is_active'],
                ],
            ]);
        }
    }

}
